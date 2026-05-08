<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Appointments - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="admin-theme">
    <nav class="navbar">
        <div class="main-content">
            <a href="dashboard.php" class="navbar-brand">🏥 Admin Panel</a>
            <ul class="navbar-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="manage_doctors.php">Doctors</a></li>
                <li><a href="manage_patients.php">Patients</a></li>
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
                <li><a href="manage_patients.php">👥 Patients</a></li>
                <li><a href="manage_doctors.php">👨‍⚕️ Doctors</a></li>
                <li><a href="manage_pharmacists.php">💊 Pharmacists</a></li>
                <li><a href="manage_appointments.php" class="active">📅 Appointments</a></li>
                <li><a href="manage_blogs.php">📰 Blogs</a></li>
                <li><a href="activity_logs.php">📋 Activity Logs</a></li>
                <li><a href="reports.php">📊 Reports</a></li>
                <li><a href="manage_admins.php">⚙️ Administrators</a></li>
            </ul>
        </div></div>

        <main >
            <div class="page-header">
                <h1>All Appointments</h1>
                <p>View and monitor all appointments</p>
            </div>

            <div class="card">
                <div class="card-header">Appointment List</div>
                <div class="table-container">
                    <?php
                    $result = $conn->query("SELECT a.*, 
                                           p.full_name as patient_name, 
                                           d.full_name as doctor_name,
                                           d.specialization
                                           FROM appointments a 
                                           JOIN patients p ON a.patient_id = p.id 
                                           JOIN doctors d ON a.doctor_id = d.id 
                                           ORDER BY a.appointment_date DESC, a.appointment_time DESC 
                                           LIMIT 100");
                    
                    if ($result->num_rows > 0) {
                        echo '<table>';
                        echo '<thead><tr><th>ID</th><th>Patient</th><th>Doctor</th><th>Date</th><th>Time</th><th>Type</th><th>Hospital</th><th>Fee</th><th>Status</th><th>Payment</th></tr></thead>';
                        echo '<tbody>';
                        while ($appt = $result->fetch_assoc()) {
                            $status_badge = 'badge-success';
                            if ($appt['status'] == 'cancelled') $status_badge = 'badge-danger';
                            elseif ($appt['status'] == 'completed') $status_badge = 'badge-info';

                            $type = $appt['appointment_type'] ?? 'at_hospital';
                            $type_badge = $type === 'video_call'
                                ? '<span class="badge" style="background:#f3e5f5;color:#6a1b9a;">📹 Video</span>'
                                : '<span class="badge" style="background:#e3f2fd;color:#1565c0;">🏥 Hospital</span>';

                            echo '<tr>';
                            echo '<td>#' . $appt['booking_number'] . '</td>';
                            echo '<td>' . htmlspecialchars($appt['patient_name']) . '</td>';
                            echo '<td>' . htmlspecialchars($appt['doctor_name']) . '<br><small>' . htmlspecialchars($appt['specialization']) . '</small></td>';
                            echo '<td>' . date('M d, Y', strtotime($appt['appointment_date'])) . '</td>';
                            echo '<td>' . date('h:i A', strtotime($appt['appointment_time'])) . '</td>';
                            echo '<td>' . $type_badge . '</td>';
                            echo '<td>' . ($type === 'video_call' ? '—' : htmlspecialchars($appt['hospital_name'])) . '</td>';
                            echo '<td>Rs. ' . number_format($appt['payment_amount'], 2) . '</td>';
                            echo '<td><span class="badge ' . $status_badge . '">' . ucfirst($appt['status']) . '</span></td>';
                            echo '<td><span class="badge badge-success">' . ucfirst($appt['payment_status']) . '</span></td>';
                            echo '</tr>';
                        }
                        echo '</tbody></table>';
                    } else {
                        echo '<p>No appointments found.</p>';
                    }
                    ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
