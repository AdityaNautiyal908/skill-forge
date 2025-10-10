<?php
// includes/mailer.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once 'vendor/phpmailer/src/PHPMailer.php';
require_once 'vendor/phpmailer/src/SMTP.php';
require_once 'vendor/phpmailer/src/Exception.php';


// NOTE: It's safer practice to load credentials from a .env file,
// but we will keep the constant since it's already defined here.
const ADMIN_SENDER_EMAIL = 'nautiyaladitya7@gmail.com'; 
const ADMIN_SENDER_PASSWORD = 'etbqmnkwdxyfjjmi'; // Your App Password

// *** MODIFIED FUNCTION: Added optional $bcc argument ***
function send_mail($to, $subject, $body, $bcc = null) {
    $mail = new PHPMailer(true);

    try {
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Uncomment for debugging
        $mail->SMTPDebug = 0;
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        
        // Credentials
        $mail->Username   = ADMIN_SENDER_EMAIL; 
        $mail->Password   = ADMIN_SENDER_PASSWORD; 
        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // Recipients
        $mail->setFrom(ADMIN_SENDER_EMAIL, 'SkillForge Support');
        $mail->addAddress($to);

        // --- FIXED BCC LOGIC ---
        if ($bcc) {
            $mail->addBCC($bcc);
        }

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent to {$to}. Mailer Error: {$mail->ErrorInfo}");
        return false; // Return false on failure, simplifying check in register.php
    }
}
?>