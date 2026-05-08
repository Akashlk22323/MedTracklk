<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $doctor_id = intval($_POST['doctor_id']);
    $user_id = intval($_POST['user_id']);
    $full_name = sanitize($conn, $_POST['full_name']);
    $email = sanitize($conn, $_POST['email']);
    $specialization = sanitize($conn, $_POST['specialization']);
    $phone = sanitize($conn, $_POST['phone']);
    $license_number = sanitize($conn, $_POST['license_number']);
    $consultation_fee = floatval($_POST['consultation_fee']);

    // Update user email
    $stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();

    // Update doctor details
    $stmt = $conn->prepare("UPDATE doctors SET full_name = ?, specialization = ?, phone = ?, license_number = ?, consultation_fee = ? WHERE id = ?");
    $stmt->bind_param("ssssdi", $full_name, $specialization, $phone, $license_number, $consultation_fee, $doctor_id);
    $stmt->execute();

    // Update hospital assignments if submitted
    if (isset($_POST['hospitals'])) {
        $hospitals       = $_POST['hospitals'];
        $primary_hospital = sanitize($conn, $_POST['primary_hospital'] ?? '');

        // Remove old assignments
        $del = $conn->prepare("DELETE FROM doctor_hospitals WHERE doctor_id = ?");
        $del->bind_param("i", $doctor_id); $del->execute(); $del->close();

        // Insert new ones
        foreach ($hospitals as $hosp_name) {
            $hosp_name  = sanitize($conn, $hosp_name);
            $is_primary = ($hosp_name === $primary_hospital) ? 1 : 0;
            $ins = $conn->prepare("INSERT INTO doctor_hospitals (doctor_id, hospital_name, is_primary) VALUES (?,?,?)");
            $ins->bind_param("isi", $doctor_id, $hosp_name, $is_primary); $ins->execute(); $ins->close();
        }
    }

    if (true) {
        logActivity($conn, $_SESSION['user_id'], 'Edit Doctor', 'Admin edited doctor #' . $doctor_id);
        $_SESSION['success'] = "Doctor updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating doctor.";
    }

    header("Location: manage_doctors.php");
    exit();
}
?>
