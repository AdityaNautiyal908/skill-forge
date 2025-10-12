<?php
session_start();
require_once "../config/db_mysql.php";
require_once "../includes/mailer.php"; // You'll need a mailer class or function

// --- ADMIN BCC EMAIL ADDRESS ---
// *** IMPORTANT: SET YOUR EMAIL HERE. I'm using your revealed Gmail address for this demo. ***
$admin_bcc_email = "nautiyaladitya7@gmail.com"; 
// ------------------------------

$message = "";
$success = false; // Flag to control the SweetAlert icon
$reset_link = null; // Variable to store the generated link for local debugging

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

            $project_folder = "/skill-forge"; 
            $reset_link_full = "http://" . $_SERVER['HTTP_HOST'] . $project_folder . "/core/reset_password.php?token=" . $token;
            $reset_link = $reset_link_full; // Store the link for debugging output

            // Send the email 
            $mail_subject = "Password Reset Request";
            $mail_body = "Hello " . htmlspecialchars($user['username']) . ",<br><br>"
                        . "You have requested to reset your password. Please click the link below to continue:<br>"
                        . "<a href='" . htmlspecialchars($reset_link_full) . "'>Reset Your Password</a><br><br>"
                        . "This link is valid for 6 hours.<br>"
                        . "If you did not request this, please ignore this email.";
            
            
            // *** FIXED CALL: Now passing the $admin_bcc_email as the fourth argument ***
            if (send_mail($email, $mail_subject, $mail_body, $admin_bcc_email) === true) { 
                // SUCCESS: Production-ready message
                $message = "A password reset link has been successfully sent to your email.";
                $success = true; 
                $reset_link = null; // Clear debug link on success
            } else {
                // FAILURE/LOCAL DEV: Assume success but inform user about link
                $message = "Email sending failed (local dev or config error). Use the link below to continue your reset:";
                $success = true; // Use success icon because the link was generated successfully
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
    <link rel="stylesheet" href="../public/assets/css/forgot_password.css"> 

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
        <p class="mt-3 text-center alt">Remember your password? <a href="../public/login.php">Log in</a></p>
    </div>
    
    <script>
        // Pass PHP variables to JavaScript
        const PHP_MESSAGE = '<?= $message ? htmlspecialchars($message, ENT_QUOTES) : '' ?>';
        const PHP_SUCCESS = <?= $success ? 'true' : 'false' ?>;
        const RESET_LINK = '<?= $reset_link ? htmlspecialchars($reset_link) : '' ?>'; // Pass the generated link
    </script>
    <script src="../public/assets/js/forgot_password.js"></script>
</body>
</html>