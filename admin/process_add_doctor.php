<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php"); exit();
}
include '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: manage_doctors.php"); exit();
}

$full_name        = sanitize($conn, $_POST['full_name']);
$username         = sanitize($conn, $_POST['username']);
$email            = sanitize($conn, $_POST['email']);
$password         = md5($_POST['password']);
$specialization   = sanitize($conn, $_POST['specialization']);
$phone            = sanitize($conn, $_POST['phone'] ?? '');
$license_number   = sanitize($conn, $_POST['license_number'] ?? '');
$consultation_fee = floatval($_POST['consultation_fee']);
$accepts_video    = isset($_POST['accepts_video_call']) ? 1 : 0;
$hospitals        = isset($_POST['hospitals']) && is_array($_POST['hospitals']) ? $_POST['hospitals'] : [];
$primary_hospital = sanitize($conn, $_POST['primary_hospital'] ?? '');

// Must have at least one hospital
if (empty($hospitals)) {
    $_SESSION['error'] = "Please select at least one hospital.";
    header("Location: manage_doctors.php"); exit();
}

// Check duplicate username / email
$chk = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
$chk->bind_param("ss", $username, $email);
$chk->execute();
if ($chk->get_result()->num_rows > 0) {
    $_SESSION['error'] = "Username or email already exists!";
    header("Location: manage_doctors.php"); exit();
}
$chk->close();

// Use first hospital as primary if none chosen
$primary = in_array($primary_hospital, $hospitals) ? $primary_hospital : $hospitals[0];

// Create user account
$u = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'doctor')");
$u->bind_param("sss", $username, $email, $password);
if (!$u->execute()) {
    $_SESSION['error'] = "Failed to create user account.";
    header("Location: manage_doctors.php"); exit();
}
$user_id = $conn->insert_id;
$u->close();

// Create doctor profile — try with accepts_video_call, fall back if column missing
$d = $conn->prepare("INSERT INTO doctors (user_id, full_name, specialization, phone, license_number, consultation_fee, current_hospital, accepts_video_call) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
if ($d === false) {
    // Column doesn't exist yet — insert without it
    $d = $conn->prepare("INSERT INTO doctors (user_id, full_name, specialization, phone, license_number, consultation_fee, current_hospital) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $d->bind_param("issssds", $user_id, $full_name, $specialization, $phone, $license_number, $consultation_fee, $primary);
} else {
    $d->bind_param("issssdsi", $user_id, $full_name, $specialization, $phone, $license_number, $consultation_fee, $primary, $accepts_video);
}
if (!$d->execute()) {
    $_SESSION['error'] = "Failed to create doctor profile.";
    header("Location: manage_doctors.php"); exit();
}
$doctor_id = $conn->insert_id;
$d->close();

// Assign hospitals (many-to-many)
$ins = $conn->prepare("INSERT IGNORE INTO doctor_hospitals (doctor_id, hospital_name, is_primary) VALUES (?, ?, ?)");
foreach ($hospitals as $hosp) {
    $hosp    = sanitize($conn, $hosp);
    $is_prim = ($hosp === $primary) ? 1 : 0;
    $ins->bind_param("isi", $doctor_id, $hosp, $is_prim);
    $ins->execute();
}
$ins->close();

logActivity($conn, $_SESSION['user_id'], 'Add Doctor', 'Added: ' . $full_name . ' with ' . count($hospitals) . ' hospital(s)');
$_SESSION['success'] = "Dr. $full_name added with " . count($hospitals) . " hospital(s)!";
header("Location: manage_doctors.php");
exit();
