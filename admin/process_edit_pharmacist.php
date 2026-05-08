<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pharmacist_id = intval($_POST['pharmacist_id']);
    $user_id = intval($_POST['user_id']);
    $full_name = sanitize($conn, $_POST['full_name']);
    $email = sanitize($conn, $_POST['email']);
    $pharmacy_name = sanitize($conn, $_POST['pharmacy_name']);
    $phone = sanitize($conn, $_POST['phone']);
    $address = sanitize($conn, $_POST['address']);

    // Update user email
    $stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();

    // Update pharmacist details
    $stmt = $conn->prepare("UPDATE pharmacists SET full_name = ?, pharmacy_name = ?, phone = ?, address = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $full_name, $pharmacy_name, $phone, $address, $pharmacist_id);
    
    if ($stmt->execute()) {
        logActivity($conn, $_SESSION['user_id'], 'Edit Pharmacist', 'Admin edited pharmacist #' . $pharmacist_id);
        $_SESSION['success'] = "Pharmacist updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating pharmacist.";
    }

    header("Location: manage_pharmacists.php");
    exit();
}
?>
