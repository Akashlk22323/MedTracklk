<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $doctor_id = intval($_POST['doctor_id']);
    $day_of_week = sanitize($conn, $_POST['day_of_week']);
    $start_time = sanitize($conn, $_POST['start_time']);
    $end_time = sanitize($conn, $_POST['end_time']);
    $hospital_name = sanitize($conn, $_POST['hospital_name']);
    $max_patients = intval($_POST['max_patients']);
    $slot_duration = intval($_POST['slot_duration']);

    $stmt = $conn->prepare("INSERT INTO schedules (doctor_id, day_of_week, start_time, end_time, hospital_name, max_patients, slot_duration) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssii", $doctor_id, $day_of_week, $start_time, $end_time, $hospital_name, $max_patients, $slot_duration);
    
    if ($stmt->execute()) {
        logActivity($conn, $_SESSION['user_id'], 'Add Schedule', 'Admin added schedule for doctor #' . $doctor_id);
        $_SESSION['success'] = "Schedule added successfully!";
    } else {
        $_SESSION['error'] = "Error adding schedule.";
    }

    header("Location: manage_schedules.php?doctor_id=" . $doctor_id);
    exit();
}
?>
