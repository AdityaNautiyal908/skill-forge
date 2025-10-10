<?php
session_start();
require_once "config/db_mysql.php";
require_once "includes/mailer.php"; // Added to use the send_mail function

$message = "";
$token = $_GET['token'] ?? '';
$is_valid_token = false;
$user_id = null; 
$username = null; // Variable to store username
$email = null;    // Variable to store email

// --- 1. Token Validation ---
if (!empty($token)) {
    // MODIFIED: Fetch id, username, and email to use for the confirmation email later
    $stmt = $conn->prepare("SELECT id, username, email FROM users WHERE reset_token = ? AND reset_expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        $is_valid_token = true;
        $user_id = $user['id']; 
        $username = $user['username']; // Store the username
        $email = $user['email'];       // Store the email
    } else {
        $message = "Invalid or expired password reset link. Please request a new one.";
    }
} else {
    $message = "No password reset token provided.";
}

// --- 2. Handle Password Reset Submission ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $is_valid_token) {
    // Retrieve user ID from a hidden field to ensure we update the correct user
    $submitted_user_id = $_POST['user_id'] ?? null;
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Re-verify the user_id matches the token's user_id for security
    if ($submitted_user_id != $user_id) {
        $message = "Security error: Token-User mismatch.";
        $is_valid_token = false; // Invalidate the form if this happens
    } elseif (empty($new_password) || empty($confirm_password)) {
        $message = "Please fill in both password fields.";
    } elseif ($new_password !== $confirm_password) {
        $message = "The passwords you entered do not match.";
    } elseif (strlen($new_password) < 8) {
        $message = "Your new password must be at least 8 characters long.";
    } else {
        // Hash the new password securely
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);

        // Update the password and clear the token/expiry fields in one query
        $stmt = $conn->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires_at = NULL WHERE id = ?");
        $stmt->bind_param("si", $password_hash, $user_id);
        
        if ($stmt->execute()) {
            
            // --- NEW: SEND PASSWORD CHANGE CONFIRMATION EMAIL ---
            $subject = "Security Alert: Your SkillForge Password Has Been Changed";
            $body = "
                <h2>Password Changed Successfully</h2>
                <p>Hello " . htmlspecialchars($username) . ",</p>
                <p>This is a confirmation that the password for your SkillForge account was successfully changed at " . date('Y-m-d H:i:s') . ".</p>
                <p>If you did not make this change, please secure your account immediately by resetting your password again and contacting our support.</p>
                <p>Thank you,<br>The SkillForge Team</p>
            ";
            
            // Use your existing mailer function to send the email
            send_mail($email, $subject, $body);
            // --- END OF NEW EMAIL LOGIC ---

            $message = "Success! Your password has been reset. You will be redirected to the login page.";
            // Redirect the user to the login page after a delay
            header("refresh:5;url=login.php");
            $is_valid_token = false; // Prevent the form from being shown after success
        } else {
            $message = "Database error: Failed to update password. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SkillForge — Reset Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets\css\reset_password.css">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="stars"></div>
    <canvas id="webLogin" class="web"></canvas>
    <div class="orb o1"></div>
    <div class="orb o2"></div>

    <div class="auth-card mx-3">
        <span class="brand">SkillForge</span>
        <h2 class="title" align="center">Set New Password</h2>
        
        <?php if ($is_valid_token): ?>
        <p class="subtitle">Please enter and confirm your new password.</p>
        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>
            <input type="hidden" name="user_id" value="<?= htmlspecialchars($user_id) ?>">
            <button type="submit" class="btn btn-primary-glow">Reset Password</button>
        </form>
        <?php else: ?>
        <p class="subtitle text-center"><?= htmlspecialchars($message) ?></p>
        <p class="mt-3 text-center alt"><a href="forgot_password.php">Request a new link</a></p>
        <?php endif; ?>
    </div>
    
    <script>
        // Pass PHP variables to JavaScript
        const PHP_MESSAGE = '<?= $message ? htmlspecialchars($message, ENT_QUOTES) : '' ?>';
    </script>
    <script src="assets\js\reset_password.js"></script>
</body>
</html>