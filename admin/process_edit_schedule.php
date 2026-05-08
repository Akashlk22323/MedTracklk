<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php"); exit();
}
include '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: manage_schedules.php"); exit();
}

$schedule_id   = intval($_POST['schedule_id']);
$doctor_id     = intval($_POST['doctor_id']);
$day_of_week   = sanitize($conn, $_POST['day_of_week']);
$start_time    = sanitize($conn, $_POST['start_time']);
$end_time      = sanitize($conn, $_POST['end_time']);
$hospital_name = sanitize($conn, $_POST['hospital_name']);
$max_patients  = intval($_POST['max_patients']);
$slot_duration = intval($_POST['slot_duration']);

// Basic validation
if ($schedule_id <= 0 || $doctor_id <= 0) {
    $_SESSION['error'] = "Invalid schedule.";
    header("Location: manage_schedules.php"); exit();
}
if (empty($day_of_week) || empty($start_time) || empty($end_time) || empty($hospital_name)) {
    $_SESSION['error'] = "Please fill all required fields.";
    header("Location: manage_schedules.php?doctor_id=$doctor_id"); exit();
}
if ($start_time >= $end_time) {
    $_SESSION['error'] = "End time must be after start time.";
    header("Location: manage_schedules.php?doctor_id=$doctor_id"); exit();
}

$stmt = $conn->prepare("UPDATE schedules SET day_of_week=?, start_time=?, end_time=?, hospital_name=?, max_patients=?, slot_duration=? WHERE id=? AND doctor_id=?");
$stmt->bind_param("ssssiiii", $day_of_week, $start_time, $end_time, $hospital_name, $max_patients, $slot_duration, $schedule_id, $doctor_id);

if ($stmt->execute()) {
    logActivity($conn, $_SESSION['user_id'], 'Edit Schedule', 'Updated schedule #' . $schedule_id);
    $_SESSION['success'] = "Schedule updated successfully!";
} else {
    $_SESSION['error'] = "Failed to update schedule: " . $conn->error;
}
$stmt->close();

header("Location: manage_schedules.php?doctor_id=$doctor_id");
exit();
