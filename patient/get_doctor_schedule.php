<?php
session_start();
header('Content-Type: application/json');

include '../includes/config.php';

$doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;
$day = isset($_GET['day']) ? sanitize($conn, $_GET['day']) : '';

if ($doctor_id > 0 && !empty($day)) {
    $stmt = $conn->prepare("SELECT * FROM schedules WHERE doctor_id = ? AND day_of_week = ? ORDER BY start_time");
    $stmt->bind_param("is", $doctor_id, $day);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $schedules = [];
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }
    
    echo json_encode(['schedules' => $schedules]);
} else {
    echo json_encode(['schedules' => []]);
}
?>
