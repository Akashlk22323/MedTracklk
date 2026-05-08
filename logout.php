<?php
session_start();
include 'includes/config.php';

if (isset($_SESSION['user_id'])) {
    logActivity($conn, $_SESSION['user_id'], 'Logout', 'User logged out');
}

session_destroy();
header("Location: login.php");
exit();
?>
