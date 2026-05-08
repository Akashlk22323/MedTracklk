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
    <title>Activity Logs - Admin Panel</title>
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
                <li><a href="manage_appointments.php">📅 Appointments</a></li>
                <li><a href="manage_blogs.php">📰 Blogs</a></li>
                <li><a href="activity_logs.php" class="active">📋 Activity Logs</a></li>
                <li><a href="reports.php">📊 Reports</a></li>
                <li><a href="manage_admins.php">⚙️ Administrators</a></li>
            </ul>
        </div></div>

        <main >
            <div class="page-header">
                <h1>Activity Logs</h1>
                <p>Monitor all system activities</p>
            </div>

            <div class="card">
                <div class="card-header">Recent Activities</div>
                <div class="table-container">
                    <?php
                    $result = $conn->query("SELECT al.*, u.username, u.role 
                                           FROM activity_logs al 
                                           JOIN users u ON al.user_id = u.id 
                                           ORDER BY al.created_at DESC 
                                           LIMIT 100");
                    
                    if ($result->num_rows > 0) {
                        echo '<table>';
                        echo '<thead><tr><th>ID</th><th>User</th><th>Role</th><th>Activity</th><th>Description</th><th>IP Address</th><th>Date & Time</th></tr></thead>';
                        echo '<tbody>';
                        while ($log = $result->fetch_assoc()) {
                            echo '<tr>';
                            echo '<td>' . $log['id'] . '</td>';
                            echo '<td>' . htmlspecialchars($log['username']) . '</td>';
                            echo '<td><span class="badge badge-info">' . ucfirst($log['role']) . '</span></td>';
                            echo '<td>' . htmlspecialchars($log['activity_type']) . '</td>';
                            echo '<td>' . htmlspecialchars($log['description']) . '</td>';
                            echo '<td>' . htmlspecialchars($log['ip_address']) . '</td>';
                            echo '<td>' . date('M d, Y H:i:s', strtotime($log['created_at'])) . '</td>';
                            echo '</tr>';
                        }
                        echo '</tbody></table>';
                    } else {
                        echo '<p>No activity logs found.</p>';
                    }
                    ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
