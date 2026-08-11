<?php
require_once __DIR__ . '/../vendor/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

define('SMTP_USERNAME', 'ortaciopioluhh@gmail.com');
define('SMTP_PASSWORD', 'mdizgpdsgtnfcyde');

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_FROM_NAME', 'CDSGA HUB');

function sendVerificationEmail(string $toEmail, string $toName, string $code): bool {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;

        $mail->setFrom(SMTP_USERNAME, SMTP_FROM_NAME);
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = 'CDSGA HUB - Email Verification Code';
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 480px; margin: auto;'>
                <h2 style='color:#7c5cff;'>CDSGA HUB</h2>
                <p>Hi {$toName},</p>
                <p>Your verification code is:</p>
                <div style='font-size: 28px; font-weight: bold; letter-spacing: 8px; background:#f4f4f4; padding: 16px; text-align:center; border-radius:8px;'>{$code}</div>
                <p style='color:#888; font-size:13px;'>This code expires in 10 minutes. If you didn't request this, ignore this email.</p>
            </div>
        ";
        $mail->AltBody = "Your CDSGA HUB verification code is: {$code} (expires in 10 minutes)";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mail error: " . $mail->ErrorInfo);
        return false;
    }
}

function sendPasswordResetEmail(string $toEmail, string $toName, string $code): bool {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;

        $mail->setFrom(SMTP_USERNAME, SMTP_FROM_NAME);
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = 'CDSGA HUB - Password Reset Code';
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 480px; margin: auto;'>
                <h2 style='color:#7c5cff;'>CDSGA HUB</h2>
                <p>Hi {$toName},</p>
                <p>We received a request to reset your password. Use the code below:</p>
                <div style='font-size: 28px; font-weight: bold; letter-spacing: 8px; background:#f4f4f4; padding: 16px; text-align:center; border-radius:8px;'>{$code}</div>
                <p style='color:#888; font-size:13px;'>This code expires in 10 minutes. If you didn't request this, you can safely ignore this email.</p>
            </div>
        ";
        $mail->AltBody = "Your CDSGA HUB password reset code is: {$code} (expires in 10 minutes)";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mail error: " . $mail->ErrorInfo);
        return false;
    }
}