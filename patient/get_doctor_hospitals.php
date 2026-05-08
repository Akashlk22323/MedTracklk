<?php
session_start();
include '../includes/config.php';

if (isset($_GET['doctor_id'])) {
    $doctor_id = intval($_GET['doctor_id']);
    
    $stmt = $conn->prepare("SELECT DISTINCT hospital_name FROM schedules WHERE doctor_id = ?");
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $hospitals = array();
    while ($row = $result->fetch_assoc()) {
        $hospitals[] = $row['hospital_name'];
    }
    
    header('Content-Type: application/json');
    echo json_encode($hospitals);
}
?>
