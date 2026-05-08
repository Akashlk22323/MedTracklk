<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php"); exit();
}
include '../includes/config.php';

$doctors = $conn->query("SELECT COUNT(*) c FROM doctors")->fetch_assoc()['c'];
$patients = $conn->query("SELECT COUNT(*) c FROM patients")->fetch_assoc()['c'];
$appointments = $conn->query("SELECT COUNT(*) c FROM appointments")->fetch_assoc()['c'];
$today_appts = $conn->query("SELECT COUNT(*) c FROM appointments WHERE appointment_date = CURDATE()")->fetch_assoc()['c'];

$revenue_stmt = $conn->query("SELECT SUM(payment_amount) as total FROM appointments WHERE payment_status='paid'");
$revenue = $revenue_stmt->fetch_assoc()['total'] ?? 0;

$recent = $conn->query("SELECT a.*, d.full_name as doc, p.full_name as pat FROM appointments a JOIN doctors d ON a.doctor_id=d.id JOIN patients p ON a.patient_id=p.id ORDER BY a.created_at DESC LIMIT 6");

// Get monthly data for chart
$months_data = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $count = $conn->query("SELECT COUNT(*) c FROM appointments WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month'")->fetch_assoc()['c'];
    $months_data[] = ['month' => date('M', strtotime($month.'-01')), 'count' => $count];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body class="admin-theme">

<nav class="navbar">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">🏥 Admin Panel</a>
        <div class="navbar-search">
            <input type="text" placeholder="Search...">
        </div>
        <ul class="navbar-menu">
            <li><a href="manage_doctors.php">Doctors</a></li>
            <li><a href="manage_appointments.php">Appointments</a></li>
            <li><a href="../logout.php">Logout</a></li>
        </ul>
    </div>
</nav>

<div class="dashboard-container">
    <aside class="sidebar">
        <ul>
            <li><a href="dashboard.php" class="active"><span>📊</span> Dashboard</a></li>
            <li><a href="manage_doctors.php"><span>👨‍⚕️</span> Doctors</a></li>
            <li><a href="manage_patients.php"><span>👥</span> Patients</a></li>
            <li><a href="manage_appointments.php"><span>📅</span> Appointments</a></li>
            <li><a href="manage_hospitals.php"><span>🏥</span> Hospitals</a></li>
            <li><a href="manage_blogs.php"><span>📰</span> Blogs</a></li>
            <li><a href="reports.php"><span>📊</span> Reports</a></li>
            <li><a href="activity_logs.php"><span>📋</span> Activity Logs</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <h1>Admin Dashboard</h1>
            <p>System Overview • <?php echo $today_appts; ?> appointments today</p>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-content">
                    <p>Doctors</p>
                    <h3><?php echo $doctors; ?></h3>
                    <span class="change up">▲ Active</span>
                </div>
                <div class="stat-card-icon">👨‍⚕️</div>
            </div>

            <div class="stat-card">
                <div class="stat-card-content">
                    <p>Patients</p>
                    <h3><?php echo $patients; ?></h3>
                    <span class="change up">▲ Registered</span>
                </div>
                <div class="stat-card-icon">👥</div>
            </div>

            <div class="stat-card">
                <div class="stat-card-content">
                    <p>Appointments</p>
                    <h3><?php echo $appointments; ?></h3>
                    <span class="change up">▲ Total</span>
                </div>
                <div class="stat-card-icon">📅</div>
            </div>

            <div class="stat-card">
                <div class="stat-card-content">
                    <p>Today</p>
                    <h3><?php echo $today_appts; ?></h3>
                    <span class="change">⏰ Live</span>
                </div>
                <div class="stat-card-icon">📊</div>
            </div>
        </div>

        <div class="modern-grid">
            <!-- Chart -->
            <div class="col-8">
                <div class="chart-card">
                    <div class="chart-card-header">
                        <div>
                            <h3>Overview</h3>
                            <h2>Appointment Trends</h2>
                        </div>
                        <div class="chart-tabs">
                            <button class="active">Month</button>
                            <button>Week</button>
                        </div>
                    </div>
                    <canvas id="appointmentChart" style="max-height:300px"></canvas>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="col-4">
                <div class="card">
                    <div class="card-header">
                        <h3>⚡ Quick Actions</h3>
                    </div>
                    <div class="card-body" style="display:flex;flex-direction:column;gap:12px">
                        <a href="manage_doctors.php?action=add" class="btn btn-primary btn-sm">➕ Add Doctor</a>
                        <a href="manage_appointments.php" class="btn btn-secondary btn-sm">📅 View Appointments</a>
                        <a href="manage_hospitals.php" class="btn btn-secondary btn-sm">🏥 Manage Hospitals</a>
                        <a href="reports.php" class="btn btn-secondary btn-sm">📊 View Reports</a>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3>📋 Recent Appointments</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($recent->num_rows > 0): ?>
                            <?php while ($appt = $recent->fetch_assoc()): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">#<?php echo $appt['booking_number']; ?></div>
                                    <div class="activity-content">
                                        <div class="activity-title"><?php echo htmlspecialchars($appt['pat']); ?> → Dr. <?php echo htmlspecialchars($appt['doc']); ?></div>
                                        <div class="activity-sub"><?php echo date('M d, Y', strtotime($appt['appointment_date'])); ?></div>
                                    </div>
                                    <div class="activity-time">
                                        <span class="badge badge-<?php 
                                            echo $appt['status'] == 'confirmed' ? 'success' : 
                                                ($appt['status'] == 'completed' ? 'info' : 'danger'); 
                                        ?>"><?php echo ucfirst($appt['status']); ?></span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p style="text-align:center;color:#636e72;padding:20px">No recent appointments</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
// Chart.js
const ctx = document.getElementById('appointmentChart');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($months_data, 'month')); ?>,
        datasets: [{
            label: 'Appointments',
            data: <?php echo json_encode(array_column($months_data, 'count')); ?>,
            backgroundColor: 'rgba(139,30,63,0.8)',
            borderColor: 'rgba(139,30,63,1)',
            borderWidth: 2,
            borderRadius: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1 },
                grid: { color: 'rgba(0,0,0,0.05)' }
            },
            x: {
                grid: { display: false }
            }
        }
    }
});
</script>

</body>
</html>
