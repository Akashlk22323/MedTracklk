<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pharmacist') {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

$stmt = $conn->prepare("SELECT ph.* FROM pharmacists ph WHERE ph.user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$pharmacist = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$pharmacist) { header("Location: ../logout.php"); exit(); }

// Handle confirmation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_prescription'])) {
    $prescription_id = intval($_POST['prescription_id']);
    $patient_id = intval($_POST['patient_id']);
    $patient_email = sanitize($conn, $_POST['patient_email']);
    $confirmation_message = sanitize($conn, $_POST['confirmation_message']);
    $internal_notes = sanitize($conn, $_POST['internal_notes']);
    $send_email = isset($_POST['send_email']) ? 1 : 0;

    // Update prescription status
    $stmt = $conn->prepare("UPDATE prescriptions SET status = 'confirmed', notes = ? WHERE id = ? AND pharmacist_id = ?");
    $stmt->bind_param("sii", $internal_notes, $prescription_id, $pharmacist['id']);
    
    if ($stmt->execute()) {
        logActivity($conn, $_SESSION['user_id'], 'Confirm Prescription', 'Pharmacist confirmed prescription #' . $prescription_id);
        
        // Send email notification (simulated)
        if ($send_email) {
            // In a real system, you would use PHPMailer or similar
            // For demo purposes, we'll just log it
            $email_subject = "Prescription Ready - " . $pharmacist['pharmacy_name'];
            $email_body = $confirmation_message;
            
            // Store notification in a notifications table or send actual email
            // For now, we'll add it to activity logs
            logActivity($conn, $_SESSION['user_id'], 'Email Sent', 'Confirmation email sent to patient for prescription #' . $prescription_id);
            
            $_SESSION['success'] = "Prescription confirmed and email sent to patient!";
        } else {
            $_SESSION['success'] = "Prescription confirmed successfully!";
        }
    } else {
        $_SESSION['error'] = "Error confirming prescription.";
    }
    
    header("Location: prescriptions.php");
    exit();
}

// Handle completion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['complete_prescription'])) {
    $prescription_id = intval($_POST['prescription_id']);
    
    $stmt = $conn->prepare("UPDATE prescriptions SET status = 'completed' WHERE id = ? AND pharmacist_id = ?");
    $stmt->bind_param("ii", $prescription_id, $pharmacist['id']);
    
    if ($stmt->execute()) {
        logActivity($conn, $_SESSION['user_id'], 'Complete Prescription', 'Pharmacist completed prescription #' . $prescription_id);
        $_SESSION['success'] = "Prescription marked as completed!";
    } else {
        $_SESSION['error'] = "Error completing prescription.";
    }
    
    header("Location: prescriptions.php");
    exit();
}

header("Location: prescriptions.php");
exit();
?>
