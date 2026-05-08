<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'patient') {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

$stmt = $conn->prepare("SELECT p.* FROM patients p WHERE p.user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$patient) { header("Location: ../logout.php"); exit(); }

// Handle prescription upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_prescription'])) {
    $pharmacist_id = intval($_POST['pharmacist_id']);
    $notes = sanitize($conn, $_POST['notes']);
    
    // Handle file upload
    if (isset($_FILES['prescription_file']) && $_FILES['prescription_file']['error'] == 0) {
        $file = $_FILES['prescription_file'];
        $allowed_types = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png', 'text/plain'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowed_types)) {
            $_SESSION['error'] = "Invalid file type. Only PDF, JPG, PNG, and TXT files are allowed.";
        } elseif ($file['size'] > $max_size) {
            $_SESSION['error'] = "File too large. Maximum size is 5MB.";
        } else {
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'prescription_' . $patient['id'] . '_' . time() . '.' . $extension;
            $upload_path = '../uploads/prescriptions/' . $filename;
            
            // Create directory if it doesn't exist
            if (!file_exists('../uploads/prescriptions/')) {
                mkdir('../uploads/prescriptions/', 0777, true);
            }
            
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                $stmt = $conn->prepare("INSERT INTO prescriptions (patient_id, pharmacist_id, prescription_file, notes) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiss", $patient['id'], $pharmacist_id, $filename, $notes);
                
                if ($stmt->execute()) {
                    logActivity($conn, $_SESSION['user_id'], 'Upload Prescription', 'Patient uploaded prescription to pharmacist');
                    $_SESSION['success'] = "Prescription sent to pharmacist successfully!";
                } else {
                    $_SESSION['error'] = "Error sending prescription.";
                }
            } else {
                $_SESSION['error'] = "Error uploading file.";
            }
        }
    } else {
        $_SESSION['error'] = "Please select a file to upload.";
    }
    
    header("Location: prescriptions.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Prescriptions - HealthCare Plus</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="patient-theme">
    <nav class="navbar">
        <div class="main-content">
            <a href="dashboard.php" class="navbar-brand">🏥 HealthCare Plus</a>
            <ul class="navbar-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="search_doctors.php">Search Doctors</a></li>
                <li><a href="my_appointments.php">My Appointments</a></li>
                <li><a href="messages.php">Messages</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="dashboard-container">
<div class="dashboard-container">
        <div ><div class="sidebar">
            <ul>
                <li><a href="dashboard.php">📊 Dashboard</a></li>
                <li><a href="search_doctors.php">🔍 Search Doctors</a></li>
                <li><a href="my_appointments.php">📅 My Appointments</a></li>
                <li><a href="doctor_location.php">📍 Doctor Locations</a></li>
                <li><a href="messages.php">💬 Ask Doctor</a></li>
                <li><a href="lab_reports.php">📋 Lab Reports</a></li>
                <li><a href="prescriptions.php" class="active">💊 Prescriptions</a></li>
                <li><a href="profile.php">👤 My Profile</a></li>
            </ul>
        </div></div>

        <main >
            <div class="page-header">
                <h1>My Prescriptions</h1>
                <p>Send prescriptions to pharmacies electronically</p>
            </div>

            <?php
            if (isset($_SESSION['success'])) {
                echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
                unset($_SESSION['success']);
            }
            if (isset($_SESSION['error'])) {
                echo '<div class="alert alert-error">' . $_SESSION['error'] . '</div>';
                unset($_SESSION['error']);
            }
            ?>

            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <div class="card-header" style="margin: 0;">Send New Prescription</div>
                    <button class="btn btn-glass-primary" onclick="openUploadModal()">+ Upload Prescription</button>
                </div>

                <div class="alert alert-info">
                    ℹ️ Supported formats: PDF, JPG, PNG, TXT | Maximum size: 5MB
                </div>
            </div>

            <div class="card">
                <div class="card-header">My Prescriptions History</div>
                <div class="table-container">
                    <?php
                    $stmt = $conn->prepare("SELECT pr.*, ph.full_name as pharmacist_name, ph.pharmacy_name 
                                           FROM prescriptions pr 
                                           LEFT JOIN pharmacists ph ON pr.pharmacist_id = ph.id 
                                           WHERE pr.patient_id = ? 
                                           ORDER BY pr.created_at DESC");
                    $stmt->bind_param("i", $patient['id']);
                    $stmt->execute();
                    $prescriptions = $stmt->get_result();

                    if ($prescriptions->num_rows > 0) {
                        echo '<table>';
                        echo '<thead><tr><th>Date</th><th>Pharmacy</th><th>Pharmacist</th><th>File</th><th>Status</th><th>Notes</th></tr></thead>';
                        echo '<tbody>';
                        while ($presc = $prescriptions->fetch_assoc()) {
                            $badge_class = 'badge-warning';
                            if ($presc['status'] == 'confirmed') $badge_class = 'badge-info';
                            elseif ($presc['status'] == 'completed') $badge_class = 'badge-success';
                            
                            echo '<tr>';
                            echo '<td>' . date('M d, Y', strtotime($presc['created_at'])) . '</td>';
                            echo '<td>' . htmlspecialchars($presc['pharmacy_name'] ?? 'Not assigned') . '</td>';
                            echo '<td>' . htmlspecialchars($presc['pharmacist_name'] ?? 'Pending') . '</td>';
                            echo '<td><a href="../uploads/prescriptions/' . htmlspecialchars($presc['prescription_file']) . '" target="_blank" class="btn btn-sm btn-success">View</a></td>';
                            echo '<td><span class="badge ' . $badge_class . '">' . ucfirst($presc['status']) . '</span></td>';
                            echo '<td>' . htmlspecialchars($presc['notes']) . '</td>';
                            echo '</tr>';
                        }
                        echo '</tbody></table>';
                    } else {
                        echo '<p>No prescriptions sent yet.</p>';
                    }
                    ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Upload Modal -->
    <div class="modal" id="uploadModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Upload Prescription</h2>
                <button class="close-modal" onclick="closeUploadModal()">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Select Pharmacist/Pharmacy *</label>
                    <select name="pharmacist_id" required>
                        <option value="">Choose a pharmacy</option>
                        <?php
                        $result = $conn->query("SELECT id, full_name, pharmacy_name FROM pharmacists ORDER BY pharmacy_name");
                        while ($pharma = $result->fetch_assoc()) {
                            echo '<option value="' . $pharma['id'] . '">' . htmlspecialchars($pharma['pharmacy_name']) . ' - ' . htmlspecialchars($pharma['full_name']) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Prescription File * (PDF, JPG, PNG, TXT - Max 5MB)</label>
                    <input type="file" name="prescription_file" required accept=".pdf,.jpg,.jpeg,.png,.txt">
                </div>

                <div class="form-group">
                    <label>Additional Notes</label>
                    <textarea name="notes" placeholder="Any special instructions or notes for the pharmacist..."></textarea>
                </div>

                <div class="alert alert-info">
                    📋 The pharmacist will review your prescription and send you a confirmation email once the medicines are ready.
                </div>

                <button type="submit" name="upload_prescription" class="btn btn-glass-primary" style="width: 100%;">Send to Pharmacy</button>
            </form>
        </div>
    </div>

    <script>
        function openUploadModal() {
            document.getElementById('uploadModal').classList.add('active');
        }

        function closeUploadModal() {
            document.getElementById('uploadModal').classList.remove('active');
        }
    </script>
</body>
</html>
