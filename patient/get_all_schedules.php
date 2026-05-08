<?php
session_start();
header('Content-Type: application/json');

include '../includes/config.php';

$doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;

if ($doctor_id > 0) {
    $stmt = $conn->prepare("SELECT DISTINCT day_of_week FROM schedules WHERE doctor_id = ?");
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $days = [];
    while ($row = $result->fetch_assoc()) {
        $days[] = $row['day_of_week'];
    }
    
    echo json_encode(['days' => $days]);
} else {
    echo json_encode(['days' => []]);
}
?>
