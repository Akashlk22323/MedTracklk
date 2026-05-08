<?php
session_start();
if (!isset($_SESSION['user_id'])) { echo json_encode(['new_messages'=>false]); exit(); }
include '../includes/config.php';

$other_uid = intval($_GET['doctor_id'] ?? 0);
$type      = (isset($_GET['type']) && $_GET['type']==='appointment') ? 'appointment' : 'general';
$my_uid    = $_SESSION['user_id'];

if ($other_uid <= 0) { echo json_encode(['new_messages'=>false]); exit(); }

$stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM messages WHERE sender_id=? AND receiver_id=? AND is_read=0 AND chat_type=?");
$stmt->bind_param("iis", $other_uid, $my_uid, $type);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
echo json_encode(['new_messages' => ($row['cnt'] > 0)]);
