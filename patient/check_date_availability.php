<?php
session_start();
header('Content-Type: application/json');

include '../includes/config.php';

$doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;
$date = isset($_GET['date']) ? sanitize($conn, $_GET['date']) : '';
$day = isset($_GET['day']) ? sanitize($conn, $_GET['day']) : '';

if ($doctor_id > 0 && !empty($date) && !empty($day)) {
    // Get total available slots for this day
    $stmt = $conn->prepare("SELECT SUM(max_patients) as total_capacity FROM schedules WHERE doctor_id = ? AND day_of_week = ?");
    $stmt->bind_param("is", $doctor_id, $day);
    $stmt->execute();
    $capacity = $stmt->get_result()->fetch_assoc();
    $total_capacity = $capacity['total_capacity'] ?? 0;
    
    // Get number of existing bookings for this date
    $stmt = $conn->prepare("SELECT COUNT(*) as booked FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND status != 'cancelled'");
    $stmt->bind_param("is", $doctor_id, $date);
    $stmt->execute();
    $booked = $stmt->get_result()->fetch_assoc();
    $booked_count = $booked['booked'] ?? 0;
    
    $available_slots = $total_capacity - $booked_count;
    
    echo json_encode([
        'total_capacity' => $total_capacity,
        'booked' => $booked_count,
        'available_slots' => max(0, $available_slots)
    ]);
} else {
    echo json_encode([
        'total_capacity' => 0,
        'booked' => 0,
        'available_slots' => 0
    ]);
}
?>
