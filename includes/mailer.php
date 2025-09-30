<?php
// includes/mailer.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once 'vendor/phpmailer/src/PHPMailer.php';
require_once 'vendor/phpmailer/src/SMTP.php';
require_once 'vendor/phpmailer/src/Exception.php';


const DEBUG_EMAIL = 'nautiyaladitya7@gmail.com';

function send_mail($to, $subject, $body) {
    $mail = new PHPMailer(true);

    try {
        $mail->SMTPDebug = 0;
        
        // Server settings (replace with your own)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        
 
        $mail->Username   = DEBUG_EMAIL; 
        $mail->Password   = 'etbqmnkwdxyfjjmi'; 
        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // Recipients
        $mail->setFrom(DEBUG_EMAIL, 'SkillForge Support');
        $mail->addAddress($to);

        // --- 3. BCC IS CORRECTLY SET TO THE DEBUG_EMAIL ---
        // Every email, regardless of recipient, will send a copy here.
        $mail->addBCC(DEBUG_EMAIL);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);

        $mail->send();
        return true;
    } catch (Exception $e) {
        // We will enable debugging if it still fails to get the specific error.
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return $mail->ErrorInfo; 
    }
}
?>