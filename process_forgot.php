<?php
session_start();
include 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: forgot_password.php"); exit();
}

$email = sanitize($conn, $_POST['email']);

// ── Always show the same message (don't reveal if email exists) ──
$_SESSION['success'] = "If that email is registered, a reset link has been sent.";

// Check email exists
$s = $conn->prepare("SELECT id FROM users WHERE email = ?");
$s->bind_param("s", $email); $s->execute();
$user = $s->get_result()->fetch_assoc(); $s->close();

if (!$user) {
    header("Location: forgot_password.php"); exit();
}

// Generate token + expiry (1 hour)
$token   = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

// Save token (replace if already exists for this email)
$s = $conn->prepare("REPLACE INTO password_resets (email, token, expires_at) VALUES (?,?,?)");
$s->bind_param("sss", $email, $token, $expires); $s->execute(); $s->close();

// ── Send email using PHP mail() ──────────────────────────────────
// For local testing use PHPMailer with Gmail SMTP (see comments below)

$site_url  = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
$reset_url = $site_url . '/reset_password.php?token=' . $token;

$subject = "Password Reset - HealthCare Plus";
$message = "Hello,\n\n"
         . "You requested a password reset for your HealthCare Plus account.\n\n"
         . "Click the link below to reset your password:\n"
         . $reset_url . "\n\n"
         . "This link expires in 1 hour.\n\n"
         . "If you did not request this, ignore this email.\n\n"
         . "HealthCare Plus Team";

$headers = "From: no-reply@healthcareplus.com\r\nContent-Type: text/plain; charset=UTF-8";

mail($email, $subject, $message, $headers);

/*
 * ── TO USE GMAIL SMTP INSTEAD ────────────────────────────────────
 * 1. composer require phpmailer/phpmailer
 * 2. Replace the mail() call above with:
 *
 * use PHPMailer\PHPMailer\PHPMailer;
 * require 'vendor/autoload.php';
 * $mail = new PHPMailer(true);
 * $mail->isSMTP();
 * $mail->Host       = 'smtp.gmail.com';
 * $mail->SMTPAuth   = true;
 * $mail->Username   = 'yourgmail@gmail.com';
 * $mail->Password   = 'your-16-char-app-password';
 * $mail->SMTPSecure = 'tls';
 * $mail->Port       = 587;
 * $mail->setFrom('yourgmail@gmail.com', 'HealthCare Plus');
 * $mail->addAddress($email);
 * $mail->Subject = $subject;
 * $mail->Body    = $message;
 * $mail->send();
 * ─────────────────────────────────────────────────────────────────
 */

header("Location: forgot_password.php"); exit();
