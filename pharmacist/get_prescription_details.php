<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pharmacist') {
    exit('Unauthorized');
}

include '../includes/config.php';

if (isset($_GET['id'])) {
    $prescription_id = intval($_GET['id']);
    
    $stmt = $conn->prepare("SELECT pr.*, 
                           p.full_name as patient_name, 
                           p.phone as patient_phone,
                           p.address as patient_address,
                           p.date_of_birth,
                           p.gender,
                           u.email as patient_email
                           FROM prescriptions pr 
                           JOIN patients p ON pr.patient_id = p.id 
                           JOIN users u ON p.user_id = u.id
                           WHERE pr.id = ?");
    $stmt->bind_param("i", $prescription_id);
    $stmt->execute();
    $presc = $stmt->get_result()->fetch_assoc();
    
    if ($presc) {
        $age = date_diff(date_create($presc['date_of_birth']), date_create('today'))->y;
        
        echo '<div style="line-height: 1.8;">';
        echo '<h3>Patient Information</h3>';
        echo '<p><strong>Name:</strong> ' . htmlspecialchars($presc['patient_name']) . '</p>';
        echo '<p><strong>Age/Gender:</strong> ' . $age . ' years / ' . htmlspecialchars($presc['gender']) . '</p>';
        echo '<p><strong>Email:</strong> ' . htmlspecialchars($presc['patient_email']) . '</p>';
        echo '<p><strong>Phone:</strong> ' . htmlspecialchars($presc['patient_phone']) . '</p>';
        echo '<p><strong>Address:</strong> ' . htmlspecialchars($presc['patient_address']) . '</p>';
        
        echo '<hr style="margin: 20px 0;">';
        
        echo '<h3>Prescription Details</h3>';
        echo '<p><strong>Received:</strong> ' . date('F d, Y h:i A', strtotime($presc['created_at'])) . '</p>';
        echo '<p><strong>Status:</strong> <span class="badge badge-info">' . ucfirst($presc['status']) . '</span></p>';
        echo '<p><strong>File:</strong> <a href="../uploads/prescriptions/' . htmlspecialchars($presc['prescription_file']) . '" target="_blank" class="btn btn-sm btn-success">📄 View Prescription</a></p>';
        
        if ($presc['notes']) {
            echo '<p><strong>Notes:</strong> ' . nl2br(htmlspecialchars($presc['notes'])) . '</p>';
        }
        
        echo '</div>';
    } else {
        echo '<p>Prescription not found.</p>';
    }
}
?>
