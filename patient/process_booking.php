<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'patient') {
    header("Location: ../login.php"); exit();
}
include '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: search_doctors.php"); exit();
}

// Get patient ID
$ps = $conn->prepare("SELECT id FROM patients WHERE user_id = ?");
$ps->bind_param("i", $_SESSION['user_id']); $ps->execute();
$pat_row = $ps->get_result()->fetch_assoc(); $ps->close();
if (!$pat_row) { header("Location: ../logout.php"); exit(); }
$patient_id = $pat_row['id'];

// Core values
$doctor_id        = intval($_POST['doctor_id']);
$appointment_date = sanitize($conn, $_POST['appointment_date']);
$appointment_time = sanitize($conn, $_POST['appointment_time'] ?? '09:00:00');
$notes            = sanitize($conn, $_POST['notes'] ?? '');
$payment_method   = ($_POST['payment_method'] ?? '') === 'card' ? 'card' : 'pay_at_hospital';
$appointment_type = ($_POST['appointment_type'] ?? '') === 'video_call' ? 'video_call' : 'at_hospital';

// For video call — no physical hospital
$hospital_name = $appointment_type === 'video_call'
    ? 'Online (Video Consultation)'
    : sanitize($conn, $_POST['hospital_name'] ?? '');

// Get fee from DB
$fs = $conn->prepare("SELECT consultation_fee FROM doctors WHERE id = ?");
$fs->bind_param("i", $doctor_id); $fs->execute();
$fee = floatval($fs->get_result()->fetch_assoc()['consultation_fee'] ?? 0);
$fs->close();

// Video call: always card payment
if ($appointment_type === 'video_call') {
    $payment_method = 'card';
}

$payment_status = $payment_method === 'card' ? 'paid' : 'pending';
$booking_number = rand(1000, 9999);

// Insert appointment
$stmt = $conn->prepare("
    INSERT INTO appointments
        (patient_id, doctor_id, appointment_date, appointment_time, hospital_name,
         notes, payment_amount, payment_status, payment_method, booking_number,
         status, appointment_type)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', ?)
");
$stmt->bind_param(
    "iissssdssis",
    $patient_id, $doctor_id, $appointment_date, $appointment_time,
    $hospital_name, $notes, $fee, $payment_status, $payment_method,
    $booking_number, $appointment_type
);

if ($stmt->execute()) {
    $appt_id = $conn->insert_id;
    logActivity($conn, $_SESSION['user_id'], 'Appointment Booked',
        ucfirst(str_replace('_',' ',$appointment_type)) . ' booking via ' . $payment_method);

    if ($appointment_type === 'video_call') {
        $_SESSION['success'] = "✅ Video consultation booked! Booking #$booking_number — join from My Appointments on the appointment date.";
    } elseif ($payment_method === 'card') {
        $_SESSION['success'] = "✅ Payment successful! Hospital appointment confirmed. Booking #$booking_number";
    } else {
        $_SESSION['success'] = "✅ Appointment booked! Pay Rs. " . number_format($fee, 2) . " at the hospital. Booking #$booking_number";
    }
} else {
    $_SESSION['error'] = "Something went wrong. Please try again.";
}
$stmt->close();

header("Location: my_appointments.php"); exit();
