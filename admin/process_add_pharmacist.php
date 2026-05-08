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
    $pharmacy_name = sanitize($conn, $_POST['pharmacy_name']);
    $phone = sanitize($conn, $_POST['phone']);
    $address = sanitize($conn, $_POST['address']);

    // Insert user
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'pharmacist')");
    $stmt->bind_param("sss", $username, $email, $password);
    
    if ($stmt->execute()) {
        $user_id = $conn->insert_id;
        $stmt = $conn->prepare("INSERT INTO pharmacists (user_id, full_name, pharmacy_name, phone, address) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $user_id, $full_name, $pharmacy_name, $phone, $address);
        
        if ($stmt->execute()) {
            logActivity($conn, $_SESSION['user_id'], 'Add Pharmacist', 'Admin added new pharmacist');
            $_SESSION['success'] = "Pharmacist added successfully!";
        }
    }

    header("Location: manage_pharmacists.php");
    exit();
}
?>
