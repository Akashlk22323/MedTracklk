<?php
session_start();
include 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php"); exit();
}

$token    = sanitize($conn, $_POST['token'] ?? '');
$password = $_POST['password'] ?? '';
$confirm  = $_POST['password_confirm'] ?? '';

// Basic checks
if (strlen($token) !== 64) {
    header("Location: login.php"); exit();
}

if (strlen($password) < 6) {
    $_SESSION['error'] = "Password must be at least 6 characters.";
    header("Location: reset_password.php?token=" . urlencode($token)); exit();
}

if ($password !== $confirm) {
    $_SESSION['error'] = "Passwords do not match.";
    header("Location: reset_password.php?token=" . urlencode($token)); exit();
}

// Verify token still valid
$s = $conn->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW()");
$s->bind_param("s", $token); $s->execute();
$row = $s->get_result()->fetch_assoc(); $s->close();

if (!$row) {
    $_SESSION['error'] = "Reset link expired. Please request a new one.";
    header("Location: forgot_password.php"); exit();
}

$email = $row['email'];

// Update password (md5 to match existing system)
$new_password = md5($password);
$s = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
$s->bind_param("ss", $new_password, $email); $s->execute(); $s->close();

// Delete used token
$s = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
$s->bind_param("s", $email); $s->execute(); $s->close();

$_SESSION['success'] = "Password reset successfully! You can now log in.";
header("Location: login.php"); exit();
