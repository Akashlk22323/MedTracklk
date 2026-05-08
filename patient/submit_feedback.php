<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'patient') {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $stmt = $conn->prepare("SELECT id FROM patients WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if (!$row) { echo json_encode(['success'=>false]); exit(); }
    $patient_id = $row['id'];

    $appointment_id = intval($_POST['appointment_id']);
    $doctor_id = intval($_POST['doctor_id']);
    $rating = intval($_POST['rating']);
    $comment = sanitize($conn, $_POST['comment']);

    // Verify appointment belongs to patient and is completed
    $stmt = $conn->prepare("SELECT id FROM appointments WHERE id = ? AND patient_id = ? AND doctor_id = ? AND status = 'completed'");
    $stmt->bind_param("iii", $appointment_id, $patient_id, $doctor_id);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        // Check if feedback already exists
        $stmt = $conn->prepare("SELECT id FROM feedbacks WHERE appointment_id = ?");
        $stmt->bind_param("i", $appointment_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows == 0) {
            $stmt = $conn->prepare("INSERT INTO feedbacks (appointment_id, patient_id, doctor_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiis", $appointment_id, $patient_id, $doctor_id, $rating, $comment);
            
            if ($stmt->execute()) {
                logActivity($conn, $_SESSION['user_id'], 'Submit Feedback', 'Patient submitted feedback for appointment');
                $_SESSION['success'] = "Thank you for your feedback!";
            }
        } else {
            $_SESSION['error'] = "You have already submitted feedback for this appointment.";
        }
    } else {
        $_SESSION['error'] = "Invalid appointment.";
    }

    header("Location: my_appointments.php");
    exit();
}
?>
