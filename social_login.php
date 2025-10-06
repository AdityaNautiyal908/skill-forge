<?php
session_start();
require_once 'vendor/autoload.php'; 

// --- ADD THESE TWO LINES AT THE TOP ---
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__); // Points to your project root
$dotenv->load();
// ------------------------------------

// Configuration array
$config = [
    'callback' => 'http://localhost/skill-forge/social_login.php',
    'providers' => [
        'Google' => [
            'enabled' => true,
            'keys'    => ['id' => $_ENV['GOOGLE_CLIENT_ID'], 'secret' => $_ENV['GOOGLE_CLIENT_SECRET']],
        ],
        'GitHub' => [
            'enabled' => true,
            'keys'    => ['id' => $_ENV['GITHUB_CLIENT_ID'], 'secret' => $_ENV['GITHUB_CLIENT_SECRET']],
            'authorize_url_parameters' => [
                'prompt' => 'consent',
            ],
        ],
    ],
];

try {
    // Get the provider from the URL (?provider=Google)
    // 1. If a provider is specified in the URL, save it to the session.
    if (isset($_GET['provider'])) {
        $_SESSION['provider'] = $_GET['provider'];
    }

    // 2. Use the provider from the session. If it's not set, something is wrong.
    if (empty($_SESSION['provider'])) {
        die("Error: No provider specified or session has expired. Please try again.");
    }
    $providerName = $_SESSION['provider'];
    
    // Instantiate Hybridauth
    $hybridauth = new Hybridauth\Hybridauth($config);
    
    // Select the provider adapter and authenticate. This will redirect user and handle callback.
    $adapter = $hybridauth->authenticate($providerName);
    
    // Get user profile data
    $userProfile = $adapter->getUserProfile();
    
    // --- DATABASE LOGIC STARTS HERE ---

    // 1. Get user data from the provider
    $email = $userProfile->email;
    $username = $userProfile->displayName;
    $provider_uid = $userProfile->identifier; 

    // 2. Connect to your MySQL database (make sure your connection details are correct)
    $pdo = new PDO('mysql:host=localhost;dbname=coding_platform', 'root', 'admin'); // Replace with your DB details

    // 3. Check if the user already exists in your database
    $stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE email = ?"); // Also fetch username
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // User exists - Log them in
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username']; // Set the username from your database
        $_SESSION['role'] = $user['role'];
        header('Location: dashboard.php'); // Redirect to their dashboard
        exit();

    } else {
        
        // For social logins, they don't have a password in your system.
        $randomPassword = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        
        // Make sure your `users` table has columns `auth_provider` and `provider_uid`
        $insertStmt = $pdo->prepare(
            "INSERT INTO users (username, email, password_hash, auth_provider, provider_uid) VALUES (?, ?, ?, ?, ?)"
        );
        $insertStmt->execute([$username, $email, $randomPassword, $providerName, $provider_uid]);

        // Get the ID of the new user
        $newUserId = $pdo->lastInsertId();

        // Log the new user in
        $_SESSION['user_id'] = $newUserId;
        $_SESSION['username'] = $username; // Set the username from the social profile
        header('Location: dashboard.php'); // Redirect to their dashboard
        exit();
    }


} catch (\Exception $e) {
    // --- THIS BLOCK IS UPDATED ---
    // Check if the user denied access from the provider
    if (isset($_GET['error']) && $_GET['error'] === 'access_denied') {
        // User canceled the authorization, redirect them back to the registration page.
        // You can change 'register.php' to your desired page (e.g., 'login.php').
        header('Location: register.php');
        exit();
    } else {
        // For all other errors, display a generic message
        echo 'Oops, we ran into an issue: ' . $e->getMessage();
    }
}

