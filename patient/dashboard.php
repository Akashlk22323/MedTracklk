<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'patient') {
    header("Location: ../login.php"); exit();
}
include '../includes/config.php';

$s = $conn->prepare("SELECT p.*, u.username, u.email FROM patients p JOIN users u ON p.user_id = u.id WHERE p.user_id = ?");
$s->bind_param("i", $_SESSION['user_id']); $s->execute();
$patient = $s->get_result()->fetch_assoc(); $s->close();
if (!$patient) { header("Location: ../logout.php"); exit(); }

$hour = date('G');
$greeting = $hour < 12 ? 'Good Morning' : ($hour < 18 ? 'Good Afternoon' : 'Good Evening');

// Stats
$s = $conn->prepare("SELECT COUNT(*) c FROM appointments WHERE patient_id = ?");
$s->bind_param("i", $patient['id']); $s->execute();
$total = $s->get_result()->fetch_assoc()['c']; $s->close();

$s = $conn->prepare("SELECT COUNT(*) c FROM appointments WHERE patient_id = ? AND status='confirmed' AND appointment_date >= CURDATE()");
$s->bind_param("i", $patient['id']); $s->execute();
$upcoming = $s->get_result()->fetch_assoc()['c']; $s->close();

$s = $conn->prepare("SELECT COUNT(*) c FROM appointments WHERE patient_id = ? AND status='completed'");
$s->bind_param("i", $patient['id']); $s->execute();
$completed = $s->get_result()->fetch_assoc()['c']; $s->close();

$s = $conn->prepare("SELECT COUNT(*) c FROM lab_reports WHERE patient_id = ?");
$s->bind_param("i", $patient['id']); $s->execute();
$reports = $s->get_result()->fetch_assoc()['c']; $s->close();

// Monthly data
$months_data = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $s = $conn->prepare("SELECT COUNT(*) c FROM appointments WHERE patient_id = ? AND DATE_FORMAT(appointment_date, '%Y-%m') = ?");
    $s->bind_param("is", $patient['id'], $month); $s->execute();
    $count = $s->get_result()->fetch_assoc()['c']; $s->close();
    $months_data[] = ['month' => date('M', strtotime($month.'-01')), 'count' => $count];
}

// Recent appointments
$s = $conn->prepare("SELECT a.*, d.full_name as doc, d.specialization FROM appointments a JOIN doctors d ON a.doctor_id = d.id WHERE a.patient_id = ? ORDER BY a.appointment_date DESC LIMIT 5");
$s->bind_param("i", $patient['id']); $s->execute();
$recent = $s->get_result(); $s->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body class="patient-theme">

<nav class="navbar">
    <div class="container">
        <a href="dashboard.php" class="navbar-brand">🏥 HealthCare Plus</a>
        <div class="navbar-search">
            <input type="text" placeholder="Search...">
        </div>
        <div class="navbar-user">
            <span><?php echo htmlspecialchars($patient['full_name']); ?></span>
            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($patient['full_name']); ?>&background=8b1e3f&color=fff&size=42" alt="Avatar">
        </div>
    </div>
</nav>

<div class="dashboard-container">
    <aside class="sidebar">
        <ul>
            <li><a href="dashboard.php" class="active"><span>📊</span> Dashboard</a></li>
            <li><a href="search_doctors.php"><span>🔍</span> Search Doctors</a></li>
            <li><a href="my_appointments.php"><span>📅</span> Appointments</a></li>
            <li><a href="doctor_location.php"><span>📍</span> Doctor Locations</a></li>
            <li><a href="messages.php"><span>💬</span> Messages</a></li>
            <li><a href="lab_reports.php"><span>📋</span> Lab Reports</a></li>
            <li><a href="prescriptions.php"><span>💊</span> Prescriptions</a></li>
            <li><a href="profile.php"><span>👤</span> Profile</a></li>
            <li><a href="../logout.php"><span>🚪</span> Logout</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <h1><?php echo $greeting?>, <?php echo htmlspecialchars($patient['full_name']); ?>!</h1>
            <p>Welcome back to your health dashboard</p>
        </div>

        <!-- Stat Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-info">
                        <h6>Total Visits</h6>
                        <h2><?php echo $total; ?></h2>
                    </div>
                    <div class="stat-card-icon bg-gradient-red">📅</div>
                </div>
                <div class="stat-card-footer">
                    <span class="stat-change positive">▲ 3.48%</span>
                    <span>Since last month</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-info">
                        <h6>Upcoming</h6>
                        <h2><?php echo $upcoming; ?></h2>
                    </div>
                    <div class="stat-card-icon bg-gradient-orange">⏰</div>
                </div>
                <div class="stat-card-footer">
                    <span class="stat-change positive">▲ 12.18%</span>
                    <span>Since last month</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-info">
                        <h6>Completed</h6>
                        <h2><?php echo $completed; ?></h2>
                    </div>
                    <div class="stat-card-icon bg-gradient-green">✅</div>
                </div>
                <div class="stat-card-footer">
                    <span class="stat-change positive">▲ 5.72%</span>
                    <span>Since last month</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-info">
                        <h6>Lab Reports</h6>
                        <h2><?php echo $reports; ?></h2>
                    </div>
                    <div class="stat-card-icon bg-gradient-blue">📋</div>
                </div>
                <div class="stat-card-footer">
                    <span class="stat-change positive">▲ 54.8%</span>
                    <span>Since last month</span>
                </div>
            </div>
        </div>

        <div class="dashboard-container">
            <!-- Chart -->
            <div class="col-md-8">
                <div class="chart-card">
                    <div class="chart-card-header">
                        <h6>Overview</h6>
                        <h2>Appointment Trends</h2>
                        <p>Last 6 months performance</p>
                    </div>
                    <div class="chart-card-body">
                        <canvas id="appointmentChart" height="100"></canvas>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3>Recent Activity</h3>
                    </div>
                    <div class="card-body" style="padding:0">
                        <?php if ($recent->num_rows > 0): ?>
                            <?php while ($r = $recent->fetch_assoc()): ?>
                            <div style="padding:16px;border-bottom:1px solid #e9ecef;transition:all 0.15s">
                                <div style="font-size:13px;font-weight:600;color:#32325d;margin-bottom:4px">
                                    Dr. <?php echo htmlspecialchars($r['doc']); ?>
                                </div>
                                <div style="font-size:12px;color:#8898aa">
                                    <?php echo htmlspecialchars($r['specialization']); ?> • 
                                    <?php echo date('M d', strtotime($r['appointment_date'])); ?>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div style="padding:40px;text-align:center;color:#8898aa">No recent activity</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h3>Quick Actions</h3>
            </div>
            <div class="card-body" style="display:flex;gap:12px;flex-wrap:wrap">
                <a href="search_doctors.php" class="btn btn-primary">🔍 Find Doctor</a>
                <a href="my_appointments.php" class="btn btn-success">📅 My Appointments</a>
                <a href="messages.php" class="btn btn-info">💬 Messages</a>
                <a href="lab_reports.php" class="btn btn-warning">📋 Lab Reports</a>
            </div>
        </div>
    </main>
</div>

<script>
const ctx = document.getElementById('appointmentChart');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: [<?php foreach($months_data as $m) echo "'".$m['month']."',"; ?>],
        datasets: [{
            label: 'Appointments',
            data: [<?php foreach($months_data as $m) echo $m['count'].","; ?>],
            backgroundColor: 'rgba(139,30,63,0.1)',
            borderColor: 'rgba(139,30,63,1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: 'rgba(139,30,63,1)',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 5
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
            x: { grid: { display: false } }
        }
    }
});
</script>
</body>
</html>
