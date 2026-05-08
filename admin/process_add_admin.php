<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = sanitize($conn, $_POST['full_name']);
    $username = sanitize($conn, $_POST['username']);
    $email = sanitize($conn, $_POST['email']);
    $password = md5($_POST['password']);
    $phone = sanitize($conn, $_POST['phone']);

    // Insert user
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'admin')");
    $stmt->bind_param("sss", $username, $email, $password);
    
    if ($stmt->execute()) {
        $user_id = $conn->insert_id;
        $stmt = $conn->prepare("INSERT INTO admins (user_id, full_name, phone) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $full_name, $phone);
        
        if ($stmt->execute()) {
            logActivity($conn, $_SESSION['user_id'], 'Add Administrator', 'Admin added new administrator');
            $_SESSION['success'] = "Administrator added successfully!";
        }
    }

    header("Location: manage_admins.php");
    exit();
}
?>
