<?php
header('Content-Type: application/json');
include 'includes/config.php';

$doctor = isset($_GET['doctor']) ? sanitize($conn, $_GET['doctor']) : '';
$hospital = isset($_GET['hospital']) ? sanitize($conn, $_GET['hospital']) : '';
$specialization = isset($_GET['specialization']) ? sanitize($conn, $_GET['specialization']) : '';
$date = isset($_GET['date']) ? sanitize($conn, $_GET['date']) : '';

// Build query
$query = "SELECT DISTINCT d.id, d.full_name, d.specialization, d.consultation_fee FROM doctors d";

$conditions = [];
$params = [];
$types = '';

if (!empty($doctor)) {
    $conditions[] = "d.full_name LIKE ?";
    $params[] = "%$doctor%";
    $types .= 's';
}

if (!empty($specialization)) {
    $conditions[] = "d.specialization = ?";
    $params[] = $specialization;
    $types .= 's';
}

if (!empty($hospital) || !empty($date)) {
    $query .= " JOIN schedules s ON d.id = s.doctor_id";
    
    if (!empty($hospital)) {
        $conditions[] = "s.hospital_name = ?";
        $params[] = $hospital;
        $types .= 's';
    }
    
    if (!empty($date)) {
        $dayName = date('l', strtotime($date));
        $conditions[] = "s.day_of_week = ?";
        $params[] = $dayName;
        $types .= 's';
    }
}

if (count($conditions) > 0) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$query .= " ORDER BY d.full_name";

$stmt = $conn->prepare($query);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$doctors = [];
while ($row = $result->fetch_assoc()) {
    // Get hospitals for this doctor
    $hospital_stmt = $conn->prepare("SELECT hospital_name FROM doctor_hospitals WHERE doctor_id = ? ORDER BY is_primary DESC LIMIT 3");
    $hospital_stmt->bind_param("i", $row['id']);
    $hospital_stmt->execute();
    $hospital_result = $hospital_stmt->get_result();
    
    $hospitals = [];
    while ($h = $hospital_result->fetch_assoc()) {
        $hospitals[] = $h['hospital_name'];
    }
    $row['hospitals'] = implode(', ', $hospitals);
    
    // Calculate available slots if date is provided
    if (!empty($date)) {
        $dayName = date('l', strtotime($date));
        
        // Get total capacity for this day
        $capacity_stmt = $conn->prepare("SELECT SUM(max_patients) as total FROM schedules WHERE doctor_id = ? AND day_of_week = ?");
        $capacity_stmt->bind_param("is", $row['id'], $dayName);
        $capacity_stmt->execute();
        $capacity = $capacity_stmt->get_result()->fetch_assoc();
        
        // Get booked count for this date
        $booked_stmt = $conn->prepare("SELECT COUNT(*) as booked FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND status != 'cancelled'");
        $booked_stmt->bind_param("is", $row['id'], $date);
        $booked_stmt->execute();
        $booked = $booked_stmt->get_result()->fetch_assoc();
        
        $row['available_slots'] = max(0, ($capacity['total'] ?? 0) - ($booked['booked'] ?? 0));
    } else {
        $row['available_slots'] = null;
    }
    
    $doctors[] = $row;
}

echo json_encode(['doctors' => $doctors]);
?>
