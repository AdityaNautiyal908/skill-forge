<?php
// includes/mailer.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once '../vendor/phpmailer/src/PHPMailer.php';
require_once '../vendor/phpmailer/src/SMTP.php';
require_once '../vendor/phpmailer/src/Exception.php';


const ADMIN_SENDER_EMAIL = 'nautiyaladitya7@gmail.com'; 
const ADMIN_SENDER_PASSWORD = 'etbqmnkwdxyfjjmi'; // Your App Password

// MODIFIED FUNCTION: Added optional $bcc argument
function send_mail($to, $subject, $body, $bcc = null) {
    $mail = new PHPMailer(true);

    try {
        // KEEP DEBUGGING ON until it works, then set to 0
        $mail->SMTPDebug = 0; 
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        
        // Credentials
        $mail->Username   = ADMIN_SENDER_EMAIL; 
        $mail->Password   = ADMIN_SENDER_PASSWORD; 
        
        // Use TLS on Port 587 (the recommended configuration)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587; 
        $mail->SMTPKeepAlive = true; 

        // *** FIX: Set the Sender (Return-Path) to match the authenticated user ***
        $mail->setFrom(ADMIN_SENDER_EMAIL, 'SkillForge Support');
        $mail->addAddress($to);

        // This line is often necessary for reliable delivery with strict SMTP servers like Gmail
        $mail->Sender = ADMIN_SENDER_EMAIL; 

        // BCC Logic
        if ($bcc) {
            $mail->addBCC($bcc);
        }

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);

        $mail->send();
        
        // If successful, close connection and return true
        $mail->SMTPClose();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent to {$to}. Mailer Error: {$mail->ErrorInfo}");
        
        // Display the error during local development
        echo "Mailer Error: " . $mail->ErrorInfo; 
        
        return false; 
    }
}