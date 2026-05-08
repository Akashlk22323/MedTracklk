<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

// Get statistics with COALESCE for proper NULL handling
$total_revenue = 0;
$monthly_revenue = 0;

// Total revenue from all appointments
$total_query = "SELECT COALESCE(SUM(payment_amount), 0) as total FROM appointments WHERE payment_status = 'paid'";
$total_result = $conn->query($total_query);
if ($total_result) {
    $total_row = $total_result->fetch_assoc();
    $total_revenue = floatval($total_row['total']);
}

// Monthly revenue
$monthly_query = "SELECT COALESCE(SUM(payment_amount), 0) as total FROM appointments WHERE payment_status = 'paid' AND MONTH(appointment_date) = MONTH(CURDATE()) AND YEAR(appointment_date) = YEAR(CURDATE())";
$monthly_result = $conn->query($monthly_query);
if ($monthly_result) {
    $monthly_row = $monthly_result->fetch_assoc();
    $monthly_revenue = floatval($monthly_row['total']);
}

$total_appointments = $conn->query("SELECT COUNT(*) as count FROM appointments")->fetch_assoc()['count'];
$completed_appointments = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE status = 'completed'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports - Admin Panel</title>
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
                <li><a href="activity_logs.php">📋 Activity Logs</a></li>
                <li><a href="reports.php" class="active">📊 Reports</a></li>
                <li><a href="manage_admins.php">⚙️ Administrators</a></li>
            </ul>
        </div></div>

        <main >
            <div class="page-header">
                <h1>System Reports</h1>
                <p>Financial and operational reports</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card-glass">
                    <div class="stat-card-content">
                        <h3>Rs. <?php echo number_format($total_revenue, 2); ?></h3>
                        <p>Total Revenue</p>
                    </div>
                    <div class="stat-card-icon">💰</div>
                </div>
                <div class="stat-card-glass">
                    <div class="stat-card-content">
                        <h3>Rs. <?php echo number_format($monthly_revenue, 2); ?></h3>
                        <p>This Month</p>
                    </div>
                    <div class="stat-card-icon">📅</div>
                </div>
                <div class="stat-card-glass">
                    <div class="stat-card-content">
                        <h3><?php echo $total_appointments; ?></h3>
                        <p>Total Appointments</p>
                    </div>
                    <div class="stat-card-icon">📋</div>
                </div>
                <div class="stat-card-glass">
                    <div class="stat-card-content">
                        <h3><?php echo $completed_appointments; ?></h3>
                        <p>Completed</p>
                    </div>
                    <div class="stat-card-icon">✅</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Doctor Earnings Report</div>
                <div class="table-container">
                    <?php
                    $result = $conn->query("SELECT d.id, d.full_name, d.specialization,
                                           COUNT(a.id) as total_appointments,
                                           SUM(a.payment_amount) as total_earnings
                                           FROM doctors d
                                           LEFT JOIN appointments a ON d.id = a.doctor_id AND a.payment_status = 'paid'
                                           GROUP BY d.id
                                           ORDER BY total_earnings DESC");
                    
                    if ($result->num_rows > 0) {
                        echo '<table>';
                        echo '<thead><tr><th>Doctor</th><th>Specialization</th><th>Primary Hospital</th><th>Appointments</th><th>Total Earnings</th></tr></thead>';
                        echo '<tbody>';
                        while ($row = $result->fetch_assoc()) {
                            // Get primary hospital for this doctor
                            $stmt = $conn->prepare("SELECT hospital_name FROM doctor_hospitals WHERE doctor_id = ? AND is_primary = 1 LIMIT 1");
                            $stmt->bind_param("i", $row['id']);
                            $stmt->execute();
                            $hospital_result = $stmt->get_result();
                            $primary_hospital = $hospital_result->num_rows > 0 ? $hospital_result->fetch_assoc()['hospital_name'] : 'N/A';
                            
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($row['full_name']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['specialization']) . '</td>';
                            echo '<td>' . htmlspecialchars($primary_hospital) . '</td>';
                            echo '<td>' . $row['total_appointments'] . '</td>';
                            echo '<td>Rs. ' . number_format($row['total_earnings'] ?? 0, 2) . '</td>';
                            echo '</tr>';
                        }
                        echo '</tbody></table>';
                    } else {
                        echo '<p style="text-align: center; padding: 30px; color: #999;">No doctor earnings data available.</p>';
                    }
                    ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Monthly Appointment Trend</div>
                <?php
                $result = $conn->query("SELECT DATE_FORMAT(appointment_date, '%Y-%m') as month, COUNT(*) as count
                                       FROM appointments
                                       GROUP BY month
                                       ORDER BY month DESC
                                       LIMIT 12");
                
                if ($result->num_rows > 0) {
                    echo '<table>';
                    echo '<thead><tr><th>Month</th><th>Appointments</th></tr></thead>';
                    echo '<tbody>';
                    while ($row = $result->fetch_assoc()) {
                        echo '<tr>';
                        echo '<td>' . date('F Y', strtotime($row['month'] . '-01')) . '</td>';
                        echo '<td>' . $row['count'] . '</td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table>';
                }
                ?>
            </div>
        </main>
    </div>
</body>
</html>
