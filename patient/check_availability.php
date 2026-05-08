<?php
session_start();
header('Content-Type: application/json');

include '../includes/config.php';

$doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;
$date = isset($_GET['date']) ? sanitize($conn, $_GET['date']) : '';
$hospital = isset($_GET['hospital']) ? sanitize($conn, $_GET['hospital']) : '';

if ($doctor_id > 0 && !empty($date) && !empty($hospital)) {
    $stmt = $conn->prepare("SELECT appointment_time FROM appointments 
                           WHERE doctor_id = ? 
                           AND appointment_date = ? 
                           AND hospital_name = ? 
                           AND status != 'cancelled'");
    $stmt->bind_param("iss", $doctor_id, $date, $hospital);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $bookedSlots = [];
    while ($row = $result->fetch_assoc()) {
        $bookedSlots[] = substr($row['appointment_time'], 0, 5); // HH:MM format
    }
    
    echo json_encode($bookedSlots);
} else {
    echo json_encode([]);
}
?>
