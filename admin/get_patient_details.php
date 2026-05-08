<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    exit('Unauthorized');
}

include '../includes/config.php';

if (isset($_GET['id'])) {
    $patient_id = intval($_GET['id']);
    
    $stmt = $conn->prepare("SELECT p.*, u.username, u.email, u.status, u.created_at 
                           FROM patients p 
                           JOIN users u ON p.user_id = u.id 
                           WHERE p.id = ?");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $patient = $stmt->get_result()->fetch_assoc();
    
    if ($patient) {
        // Get appointment count
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE patient_id = ?");
        $stmt->bind_param("i", $patient_id);
        $stmt->execute();
        $appt_count = $stmt->get_result()->fetch_assoc()['count'];
        
        echo '<div style="line-height: 2;">';
        echo '<h3>Patient Information</h3>';
        echo '<p><strong>ID:</strong> #' . $patient['id'] . '</p>';
        echo '<p><strong>Full Name:</strong> ' . htmlspecialchars($patient['full_name']) . '</p>';
        echo '<p><strong>Username:</strong> ' . htmlspecialchars($patient['username']) . '</p>';
        echo '<p><strong>Email:</strong> ' . htmlspecialchars($patient['email']) . '</p>';
        echo '<p><strong>Phone:</strong> ' . htmlspecialchars($patient['phone']) . '</p>';
        echo '<p><strong>Gender:</strong> ' . htmlspecialchars($patient['gender']) . '</p>';
        echo '<p><strong>Date of Birth:</strong> ' . date('F d, Y', strtotime($patient['date_of_birth'])) . '</p>';
        echo '<p><strong>Address:</strong> ' . htmlspecialchars($patient['address']) . '</p>';
        echo '<p><strong>Account Status:</strong> <span class="badge badge-success">' . ucfirst($patient['status']) . '</span></p>';
        echo '<p><strong>Registered:</strong> ' . (!empty($patient['created_at']) ? date('F d, Y', strtotime($patient['created_at'])) : 'N/A') . '</p>';
        
        echo '<hr style="margin: 20px 0;">';
        
        echo '<h3>Activity Statistics</h3>';
        echo '<p><strong>Total Appointments:</strong> ' . $appt_count . '</p>';
        
        echo '<hr style="margin: 20px 0;">';
        
        echo '<div style="margin-top: 20px;">';
        echo '<button class="btn btn-glass-primary" onclick="closeModal(); editPatient(' . $patient_id . ')">Edit Patient</button>';
        echo '</div>';
        
        echo '</div>';
    } else {
        echo '<p>Patient not found.</p>';
    }
}
?>
