<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $patient_id = intval($_POST['patient_id']);
    $user_id = intval($_POST['user_id']);
    $full_name = sanitize($conn, $_POST['full_name']);
    $phone = sanitize($conn, $_POST['phone']);
    $email = sanitize($conn, $_POST['email']);
    $gender = sanitize($conn, $_POST['gender']);
    $date_of_birth = sanitize($conn, $_POST['date_of_birth']);
    $address = sanitize($conn, $_POST['address']);

    // Update user email
    $stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();

    // Update patient details
    $stmt = $conn->prepare("UPDATE patients SET full_name = ?, phone = ?, gender = ?, date_of_birth = ?, address = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $full_name, $phone, $gender, $date_of_birth, $address, $patient_id);
    
    if ($stmt->execute()) {
        logActivity($conn, $_SESSION['user_id'], 'Edit Patient', 'Admin edited patient #' . $patient_id);
        $_SESSION['success'] = "Patient updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating patient.";
    }

    header("Location: manage_patients.php");
    exit();
}
?>
