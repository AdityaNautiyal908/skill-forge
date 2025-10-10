<?php
session_start();
require_once "config/db_mysql.php";
require_once "includes/mailer.php"; // Required for send_mail() function

$message = "";
$success = false; // Flag to control the SweetAlert icon

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $message = "Please enter your email address.";
    } else {
        $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            // Generate a unique token
            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+6 hours')); 

            // Store the token and expiry in the database
            $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires_at = ? WHERE id = ?");
            $stmt->bind_param("ssi", $token, $expires_at, $user['id']);
            $stmt->execute();

            $project_folder = "/skill-forge"; // Define your project folder name (adjust if your project root is different)
            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . $project_folder . "/reset_password.php?token=" . $token;

            // Send the email (assuming send_mail() returns true on success, false/error string on failure)
            $mail_subject = "Password Reset Request";
            $mail_body = "Hello " . htmlspecialchars($user['username']) . ",<br><br>"
                        . "You have requested to reset your password. Please click the link below to continue:<br>"
                        . "<a href='" . htmlspecialchars($reset_link) . "'>Reset Your Password</a><br><br>"
                        . "This link is valid for 6 hours.<br>"
                        . "If you did not request this, please ignore this email.";
            
            
            // NOTE: We now check the return value of send_mail().
            if (send_mail($email, $mail_subject, $mail_body) === true) {
                // CHANGED: Direct success message and set flag for SweetAlert
                $message = "A password reset link has been successfully sent to your email.";
                $success = true; 
            } else {
                // FAILURE: Show the generic message (log error in mailer.php)
                $message = "If an account with that email exists, a password reset link has been sent.";
                $success = false;
            }

        } else {
            // Email not found: Still show the generic message for security
            $message = "If an account with that email exists, a password reset link has been sent.";
            $success = false;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SkillForge — Forgot Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets\css\forgot_password.css">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="stars"></div>
    <canvas id="webLogin" class="web"></canvas>
    <div class="orb o1"></div>
    <div class="orb o2"></div>

    <div class="auth-card mx-3">
        <span class="brand">SkillForge</span>
        <h2 class="title" align="center">Forgot Password</h2>
        <p class="subtitle">Enter your email to receive a password reset link.</p>
        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary-glow">Send Reset Link</button>
        </form>
        <p class="mt-3 text-center alt">Remember your password? <a href="login.php">Log in</a></p>
    </div>
    
    <script>
        // Pass PHP variables to JavaScript
        const PHP_MESSAGE = '<?= $message ? htmlspecialchars($message, ENT_QUOTES) : '' ?>';
        const PHP_SUCCESS = <?= $success ? 'true' : 'false' ?>;
    </script>
    <script src="assets\js\forgot_password.js"></script>
</body>
</html>