<?php
// ═══════════════════════════════════════════════════════════
//  DATABASE CONNECTION
// ═══════════════════════════════════════════════════════════

$host = "localhost:3307";
$user = "root";
$pass = "";
$db   = "tcl";

// Connect to database
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Use UTF-8 character set
$conn->set_charset("utf8mb4");

// ═══════════════════════════════════════════════════════════
//  HELPER FUNCTIONS
// ═══════════════════════════════════════════════════════════

// Clean user input to prevent XSS attacks
function sanitize($conn, $data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Log user activity
function logActivity($conn, $user_id, $activity_type, $description) {
    $ip   = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, activity_type, description, ip_address) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("isss", $user_id, $activity_type, $description, $ip);
        $stmt->execute();
        $stmt->close();
    }
}
?>
