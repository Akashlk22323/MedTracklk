<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) { echo json_encode(['error'=>'Unauthorized']); exit(); }

include 'includes/config.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$room   = isset($_POST['room']) ? sanitize($conn, $_POST['room']) : (isset($_GET['room']) ? sanitize($conn, $_GET['room']) : '');
$role   = $_SESSION['role'] === 'doctor' ? 'doctor' : 'patient';

switch ($action) {

    case 'join':
        // Mark room as active, return room info
        $stmt = $conn->prepare("UPDATE video_rooms SET status='active', started_at=NOW() WHERE room_code=? AND status='waiting'");
        $stmt->bind_param("s", $room);
        $stmt->execute();
        echo json_encode(['ok' => true]);
        break;

    case 'send_signal':
        $type = sanitize($conn, $_POST['type']);
        $data = $_POST['data'];
        // Delete old signals of same type from this role
        $del = $conn->prepare("DELETE FROM video_signals WHERE room_code=? AND sender_role=? AND signal_type=?");
        $del->bind_param("sss", $room, $role, $type);
        $del->execute();
        // Insert new signal
        $ins = $conn->prepare("INSERT INTO video_signals (room_code, sender_role, signal_type, signal_data) VALUES (?,?,?,?)");
        $ins->bind_param("ssss", $room, $role, $type, $data);
        $ins->execute();
        echo json_encode(['ok' => true]);
        break;

    case 'get_signals':
        $other = ($role === 'doctor') ? 'patient' : 'doctor';
        $stmt  = $conn->prepare("SELECT signal_type, signal_data FROM video_signals WHERE room_code=? AND sender_role=? ORDER BY id ASC");
        $stmt->bind_param("ss", $room, $other);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['signals' => $rows]);
        break;

    case 'clear_ice':
        // Clear ICE candidates after consuming
        $other = ($role === 'doctor') ? 'patient' : 'doctor';
        $del2  = $conn->prepare("DELETE FROM video_signals WHERE room_code=? AND sender_role=? AND signal_type='ice-candidate'");
        $del2->bind_param("ss", $room, $other);
        $del2->execute();
        echo json_encode(['ok' => true]);
        break;

    case 'end':
        $stmt = $conn->prepare("UPDATE video_rooms SET status='ended', ended_at=NOW() WHERE room_code=?");
        $stmt->bind_param("s", $room);
        $stmt->execute();
        $conn->prepare("DELETE FROM video_signals WHERE room_code=?")->execute() ||
        ($cl = $conn->prepare("DELETE FROM video_signals WHERE room_code=?")) && $cl->bind_param("s", $room) && $cl->execute();
        echo json_encode(['ok' => true]);
        break;

    case 'status':
        $stmt = $conn->prepare("SELECT status FROM video_rooms WHERE room_code=?");
        $stmt->bind_param("s", $room);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        echo json_encode(['status' => $row['status'] ?? 'none']);
        break;
}
?>
