<?php
session_start();
require_once "config/db_mysql.php";
require_once "includes/mailer.php"; // Required for send_mail() function

$message = "";
$error = "";
$success_count = 0;

// 1. Authentication Check: ONLY ADMINS CAN ACCESS THIS PAGE
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] === 'guest' || (isset($_SESSION['role']) && $_SESSION['role'] !== 'admin')) {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $recipient_type = $_POST['recipient_type']; // 'all' or 'single'
    $single_email = trim($_POST['single_email'] ?? '');
    $subject = trim($_POST['subject']);
    $body = $_POST['body']; // HTML body content

    if (empty($subject) || empty($body)) {
        $error = "Subject and message body are required.";
    }

    if (empty($error)) {
        $recipients = [];

        if ($recipient_type === 'all') {
            // Get all user emails from the database (exclude admin and guests if necessary)
            $stmt = $conn->prepare("SELECT email, username FROM users WHERE role != 'admin'");
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $recipients[] = ['email' => $row['email'], 'username' => $row['username']];
            }
        } elseif ($recipient_type === 'single') {
            if (!filter_var($single_email, FILTER_VALIDATE_EMAIL)) {
                $error = "Invalid email format for single recipient.";
            } else {
                // Look up user by email to ensure they exist (and get their username)
                $stmt = $conn->prepare("SELECT email, username FROM users WHERE email=?");
                $stmt->bind_param("s", $single_email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 1) {
                    $recipients[] = $result->fetch_assoc();
                } else {
                    $error = "No registered user found with that email address.";
                }
            }
        }
    }

    if (empty($error) && !empty($recipients)) {
        $mail_sent = 0;
        foreach ($recipients as $user) {
            // Personalize the body content
            $personalized_body = str_replace('{{USERNAME}}', htmlspecialchars($user['username']), $body);
            
            // Assuming send_mail() takes email, subject, and HTML body
            if (send_mail($user['email'], $subject, $personalized_body)) {
                $mail_sent++;
            }
        }
        $success_count = $mail_sent;
        $message = "Successfully sent {$success_count} emails.";
    } elseif (empty($error)) {
        $error = "No recipients found for the selected criteria.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SkillForge — Admin Mailer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="assets\css\admin_mail.css"> 
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<div class="container">
    <h2 class="mb-4 text-center">Admin Mailer Tool</h2>
    
    <a href="dashboard.php" class="back-link-animated">&larr; Back to Dashboard</a>

    <form method="POST" action="">
        <div class="mb-4">
            <label for="recipient_type" class="form-label">Send To:</label>
            <select class="form-select" id="recipient_type" name="recipient_type" required>
                <option value="all" selected>All Registered Users (excluding admins)</option>
                <option value="single">Single User (by Email)</option>
            </select>
        </div>

        <div class="mb-4" id="singleEmailDiv" style="display: none;">
            <label for="single_email" class="form-label">Recipient Email Address:</label>
            <input type="email" class="form-control" id="single_email" name="single_email">
            <div class="form-text text-light">This email must belong to a registered user.</div>
        </div>

        <div class="mb-4">
            <label for="subject" class="form-label">Subject:</label>
            <input type="text" class="form-control" id="subject" name="subject" required>
        </div>

        <div class="mb-4">
            <label for="body" class="form-label">Email Body (HTML accepted):</label>
            <textarea class="form-control" id="body" name="body" rows="10" required></textarea>
            <div class="form-text text-light">Use **{{USERNAME}}** to personalize the email.</div>
        </div>

        <button type="submit" class="btn btn-primary-glow w-100">Send Email(s)</button>
    </form>
</div>

<script>
    // Pass PHP messages to JavaScript
    const PHP_ERROR = '<?= $error ? htmlspecialchars($error, ENT_QUOTES) : '' ?>';
    const PHP_MESSAGE = '<?= $message ? htmlspecialchars($message, ENT_QUOTES) : '' ?>';
</script>

<script src="assets\js\admin_mail.js"></script>
</body>
</html>