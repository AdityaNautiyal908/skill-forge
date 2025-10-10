<?php
session_start();
// NOTE: Make sure these file paths are correct for your project structure.
require_once "config/db_mysql.php"; 
require_once "includes/mailer.php"; // You'll need a mailer class or function

// --- 1. CHECK FOR REGISTRATION SUCCESS (FLASH MESSAGE) ---
$show_success_alert = false;
if (isset($_SESSION['registration_success'])) {
    $show_success_alert = true;
    unset($_SESSION['registration_success']); // Unset it so it doesn't show again on refresh
}

$message = "";

// Check if the guest button was clicked
if (isset($_POST['guest_register'])) {
    $_SESSION['user_id'] = 'guest'; // Use the same guest identifier
    $_SESSION['username'] = 'Guest';
    $_SESSION['role'] = 'guest';
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['username'])) { // Check for username to avoid conflict with guest form
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirmPassword']); // Assuming you've added this to your form

    // Server-side password strength validation
    $hasUpper = preg_match('/[A-Z]/', $password);
    $hasLower = preg_match('/[a-z]/', $password);
    $hasDigit = preg_match('/[0-9]/', $password);
    $hasSymbol = preg_match('/[^A-Za-z0-9]/', $password);

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $message = "Please fill in all the details.";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
    } elseif (strlen($password) < 8 || !$hasUpper || !$hasLower || !$hasDigit || !$hasSymbol) {
        $message = "Password must be at least 8 chars and include uppercase, lowercase, number, and symbol.";
    }

    if ($message === "") {
        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        // Check if email or username already exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE email=? OR username=?");
        $stmt->bind_param("ss", $email, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $message = "Username or Email already exists!";
        } else {
            // Insert user
            $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $password_hash);
            
            if ($stmt->execute()) {
                // --- NEW WELCOME EMAIL LOGIC ---
                $welcome_subject = "Welcome to SkillForge, " . $username . "!";
                $welcome_body = "
                    <h2>Welcome to SkillForge!</h2>
                    <p>Hello **" . htmlspecialchars($username) . "**,</p>
                    <p>Thank you for signing up and starting your coding journey with us. Your account is now active.</p>
                    <p>You can start practicing immediately by visiting your dashboard:</p>
                    <p><a href='http://" . $_SERVER['HTTP_HOST'] . "/skill-forge/dashboard.php' style='padding: 10px 20px; background-color: #6d7cff; color: white; text-decoration: none; border-radius: 5px; display: inline-block;'>Go to Dashboard</a></p>
                    <p>Happy Coding!</p>
                    <p>— The SkillForge Team</p>
                ";
                
                // Send the welcome email (Requires mailer.php to have a send_mail function)
                send_mail($email, $welcome_subject, $welcome_body);

                // --- 2. SET SESSION FOR SUCCESS ALERT AND REDIRECT ---
                $_SESSION['user_id'] = $stmt->insert_id;
                $_SESSION['username'] = $username;
                $_SESSION['show_preloader'] = true; // For the loading page
                $_SESSION['registration_success'] = true; // Our new flash message trigger
                header("Location: " . $_SERVER['PHP_SELF']); // Redirect back to this same page
                exit;

            } else {
                $message = "Registration failed. Try again!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SkillForge — Create Account</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="register.css">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="assets\css\register.css">
</head>
<body>
<div class="stars"></div>
<canvas id="webReg" class="web"></canvas>
<div class="orb o1"></div>
<div class="orb o2"></div>

<div class="auth-card mx-3">
    <div class="intro-section">
        <span class="brand">SkillForge</span>
        <h2 class="title">Create your account</h2>
        <p class="subtitle">Join SkillForge and start building your coding superpowers.</p>
        <img src="https://media3.giphy.com/media/v1.Y2lkPTc5MGI3NjExOWgxOHlnN3ZnamFidTIybTZmZnN3NmM4NXNxbTFubmNld3BicWtweCZlcD12MV9pbnRlcm5hbF9naWZfYnlfaWQmY3Q9Zw/cKKilkePjBAh0tbmBU/giphy.gif" alt="Registration GIF" class="register-gif">
    </div>
    
    <div class="form-section">
        
        <div class="social-login-options">
            <a href="social_login.php?provider=Google" class="btn-social">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M21.35,11.1H12.18V13.83H18.69C18.36,17.64 15.19,19.27 12.19,19.27C8.36,19.27 5,16.25 5,12C5,7.75 8.36,4.73 12.19,4.73C14.03,4.73 15.6,5.33 16.8,6.48L19.09,4.2C17.2,2.44 14.8,1.5 12.19,1.5C6.92,1.5 2.73,6.09 2.73,12C2.73,17.91 6.92,22.5 12.19,22.5C17.8,22.5 21.6,18.35 21.6,12.27C21.6,11.76 21.5,11.43 21.35,11.1V11.1Z"></path></svg>
                <span>Google</span>
            </a>
            <a href="social_login.php?provider=GitHub" class="btn-social">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12,2A10,10 0 0,0 2,12C2,16.42 4.87,20.17 8.84,21.5C9.34,21.58 9.5,21.27 9.5,21V19.21C6.73,19.64 6.13,17.59 6.13,17.59C5.61,16.22 4.63,15.76 4.63,15.76C3.6,15.05 4.7,15.07 4.7,15.07C5.8,15.15 6.4,16.2 6.4,16.2C7.3,17.75 8.9,17.29 9.5,17C9.58,16.45 9.83,16.08 10.1,15.82C7.6,15.54 5,14.54 5,10.74C5,9.61 5.4,8.69 6.03,8C5.9,7.72 5.5,6.67 6.15,5.17C6.15,5.17 7.08,4.88 9.5,6.5C10.4,6.23 11.42,6.1 12.43,6.1C13.44,6.1 14.46,6.23 15.36,6.5C17.78,4.88 18.71,5.17 18.71,5.17C19.36,6.67 18.96,7.72 18.83,8C19.46,8.69 19.86,9.61 19.86,10.74C19.86,14.55 17.26,15.54 14.76,15.82C15.13,16.16 15.46,16.84 15.46,17.81V21C15.46,21.27 15.62,21.59 16.12,21.5C20.09,20.17 22.86,16.42 22.86,12A10,10 0 0,0 12,2Z"></path></svg>
                <span>GitHub</span>
            </a>
        </div>
        
        <div class="divider">Or</div>
        <form method="POST" action="" id="registerForm" novalidate>
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" id="username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" id="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="password-input-wrapper">
                    <input type="password" name="password" id="password" class="form-control" required minlength="8" autocomplete="on">
                    <button type="button" class="password-toggle" id="togglePassword" title="Show/Hide Password">
                        <span class="eye-svg" aria-hidden="true"></span>
                    </button>
                </div>
                <ul class="password-requirements">
                    <li id="lengthReq">At least 8 characters</li>
                    <li id="uppercaseReq">At least 1 uppercase letter</li>
                    <li id="lowercaseReq">At least 1 lowercase letter</li>
                    <li id="numberReq">At least 1 number</li>
                    <li id="symbolReq">At least 1 symbol</li>
                </ul>
            </div>
            <div class="mb-3">
                <label class="form-label">Confirm Password</label>
                <div class="password-input-wrapper">
                    <input type="password" name="confirmPassword" id="confirmPassword" class="form-control" required autocomplete="on">
                    <button type="button" class="password-toggle" id="toggleConfirmPassword" title="Show/Hide Password">
                        <span class="eye-svg" aria-hidden="true"></span>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary-glow" id="submitBtn">Create account</button>
            <button type="button" class="btn btn-guest btn-generate-password" id="generatePasswordBtn">Generate Strong Password</button>
            <p class="mt-3 text-center alt">Already have an account? <a href="login.php">Login</a></p>
        </form>
        <form method="POST" action="">
            <button type="submit" name="guest_register" class="btn btn-guest">Continue as Guest</button>
        </form>
    </div>
</div>

<script>
    const SHOW_SUCCESS_ALERT = <?= $show_success_alert ? 'true' : 'false' ?>;
    const PHP_ERROR_MESSAGE = '<?= $message ? htmlspecialchars($message, ENT_QUOTES, 'UTF-8') : '' ?>';
</script>
<script src="assets\js\register.js"></script>
</body>
</html>