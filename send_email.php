<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer files
require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

// Function to send email
function sendEmail($to, $subject, $message) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Gmail SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'abhiram09882@gmail.com'; // Your Gmail address
        $mail->Password   = 'ghimrdolykuvvrkg'; // Your Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('no-reply@helpdesk.com', 'Helpdesk System');
        $mail->addAddress($to); // Recipient's email

        // Content
        $mail->isHTML(false); // Set to true if you want to send HTML emails
        $mail->Subject = $subject;
        $mail->Body    = $message;

        // Send email
        $mail->send();
        return true; // Email sent successfully
    } catch (Exception $e) {
        return "Failed to send email. Error: " . $mail->ErrorInfo; // Email failed to send
    }
}
?>