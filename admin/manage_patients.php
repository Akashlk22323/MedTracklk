<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $user_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'patient'");
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        logActivity($conn, $_SESSION['user_id'], 'Delete Patient', 'Admin deleted a patient');
        $_SESSION['success'] = "Patient deleted successfully!";
    }
    header("Location: manage_patients.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Patients - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="admin-theme">
    <nav class="navbar">
        <div class="main-content">
            <a href="dashboard.php" class="navbar-brand">🏥 Admin Panel</a>
            <ul class="navbar-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="manage_doctors.php">Doctors</a></li>
                <li><a href="manage_patients.php" style="background: rgba(255,255,255,0.2);">Patients</a></li>
                <li><a href="manage_pharmacists.php">Pharmacists</a></li>
                <li><a href="manage_blogs.php">Blogs</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="dashboard-container">
<div class="dashboard-container">
        <div ><div class="sidebar">
            <ul>
                <li><a href="dashboard.php">📊 Dashboard</a></li>
                <li><a href="manage_patients.php" class="active">👥 Patients</a></li>
                <li><a href="manage_doctors.php">👨‍⚕️ Doctors</a></li>
                <li><a href="manage_pharmacists.php">💊 Pharmacists</a></li>
                <li><a href="manage_appointments.php">📅 Appointments</a></li>
                <li><a href="manage_blogs.php">📰 Blogs</a></li>
                <li><a href="activity_logs.php">📋 Activity Logs</a></li>
                <li><a href="reports.php">📊 Reports</a></li>
                <li><a href="manage_admins.php">⚙️ Administrators</a></li>
            </ul>
        </div></div>

        <main >
            <div class="page-header">
                <h1>Manage Patients</h1>
                <p>View and monitor all patient activities</p>
            </div>

            <?php
            if (isset($_SESSION['success'])) {
                echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
                unset($_SESSION['success']);
            }
            ?>

            <div class="card">
                <div class="card-header">All Patients</div>
                <div class="table-container">
                    <?php
                    $result = $conn->query("SELECT p.*, u.username, u.email, u.status, u.created_at 
                                           FROM patients p 
                                           JOIN users u ON p.user_id = u.id 
                                           ORDER BY p.id DESC");
                    
                    if ($result->num_rows > 0) {
                        echo '<table>';
                        echo '<thead><tr><th>ID</th><th>Name</th><th>Username</th><th>Email</th><th>Phone</th><th>Gender</th><th>DOB</th><th>Status</th><th>Registered</th><th>Actions</th></tr></thead>';
                        echo '<tbody>';
                        while ($patient = $result->fetch_assoc()) {
                            // Get patient stats
                            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE patient_id = ?");
                            $stmt->bind_param("i", $patient['id']);
                            $stmt->execute();
                            $appt_count = $stmt->get_result()->fetch_assoc()['count'];
                            
                            echo '<tr>';
                            echo '<td>' . $patient['id'] . '</td>';
                            echo '<td>' . htmlspecialchars($patient['full_name']) . '</td>';
                            echo '<td>' . htmlspecialchars($patient['username']) . '</td>';
                            echo '<td>' . htmlspecialchars($patient['email']) . '</td>';
                            echo '<td>' . htmlspecialchars($patient['phone']) . '</td>';
                            echo '<td>' . htmlspecialchars($patient['gender']) . '</td>';
                            echo '<td>' . date('M d, Y', strtotime($patient['date_of_birth'])) . '</td>';
                            echo '<td><span class="badge badge-success">' . ucfirst($patient['status']) . '</span></td>';
                            echo '<td>' . date('M d, Y', strtotime($patient['created_at'])) . '</td>';
                            echo '<td>';
                            echo '<button class="btn btn-sm btn-info" onclick="viewDetails(' . $patient['id'] . ')">View</button> ';
                            echo '<button class="btn btn-sm btn-primary" onclick="editPatient(' . $patient['id'] . ')">Edit</button> ';
                            echo '<a href="?delete=' . $patient['user_id'] . '" class="btn btn-sm btn-danger" onclick="return confirm(\'Delete this patient?\')">Delete</a>';
                            echo '</td>';
                            echo '</tr>';
                        }
                        echo '</tbody></table>';
                    } else {
                        echo '<p>No patients found.</p>';
                    }
                    ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Patient Details Modal -->
    <div class="modal" id="detailsModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Patient Details</h2>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <div id="patientDetails">Loading...</div>
        </div>
    </div>

    <!-- Edit Patient Modal -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Patient</h2>
                <button class="close-modal" onclick="closeEditModal()">&times;</button>
            </div>
            <div id="editForm">Loading...</div>
        </div>
    </div>

    <script>
        function viewDetails(patientId) {
            document.getElementById('detailsModal').classList.add('active');
            document.getElementById('patientDetails').innerHTML = 'Loading...';
            
            fetch('get_patient_details.php?id=' + patientId)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('patientDetails').innerHTML = data;
                });
        }

        function editPatient(patientId) {
            document.getElementById('editModal').classList.add('active');
            document.getElementById('editForm').innerHTML = 'Loading...';
            
            fetch('get_patient_edit.php?id=' + patientId)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('editForm').innerHTML = data;
                });
        }

        function closeModal() {
            document.getElementById('detailsModal').classList.remove('active');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }
    </script>
</body>
</html>
