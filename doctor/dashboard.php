<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'doctor') {
    header("Location: ../login.php"); exit();
}
include '../includes/config.php';

$stmt = $conn->prepare("SELECT * FROM doctors WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']); $stmt->execute();
$doctor = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$doctor) { header("Location: ../logout.php"); exit(); }

$hour = date('G');
$greeting = $hour < 12 ? 'Good Morning' : ($hour < 18 ? 'Good Afternoon' : 'Good Evening');

// Stats
$s = $conn->prepare("SELECT COUNT(*) c FROM appointments WHERE doctor_id = ?");
$s->bind_param("i", $doctor['id']); $s->execute();
$total = $s->get_result()->fetch_assoc()['c']; $s->close();

$s = $conn->prepare("SELECT COUNT(*) c FROM appointments WHERE doctor_id = ? AND appointment_date = CURDATE() AND status='confirmed'");
$s->bind_param("i", $doctor['id']); $s->execute();
$today = $s->get_result()->fetch_assoc()['c']; $s->close();

$s = $conn->prepare("SELECT SUM(payment_amount) t FROM appointments WHERE doctor_id = ? AND status='completed' AND payment_status='paid'");
$s->bind_param("i", $doctor['id']); $s->execute();
$earnings = $s->get_result()->fetch_assoc()['t'] ?? 0; $s->close();

$s = $conn->prepare("SELECT COUNT(*) c FROM lab_reports WHERE doctor_id = ?");
$s->bind_param("i", $doctor['id']); $s->execute();
$reports = $s->get_result()->fetch_assoc()['c']; $s->close();

// Today's appointments
$s = $conn->prepare("SELECT a.*, p.full_name as patient FROM appointments a JOIN patients p ON a.patient_id = p.id WHERE a.doctor_id = ? AND a.appointment_date = CURDATE() ORDER BY a.appointment_time LIMIT 6");
$s->bind_param("i", $doctor['id']); $s->execute();
$today_appts = $s->get_result(); $s->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="doctor-theme">

<nav class="navbar">
    <div class="container">
        <a href="dashboard.php" class="navbar-brand">🏥 HealthCare Plus</a>
        <div class="navbar-search">
            <input type="text" placeholder="Search...">
        </div>
        <div class="navbar-user">
            <span>Dr. <?php echo htmlspecialchars($doctor['full_name']); ?></span>
            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($doctor['full_name']); ?>&background=8b1e3f&color=fff&size=42" alt="Avatar">
        </div>
    </div>
</nav>

<div class="dashboard-container">
    <aside class="sidebar">
        <ul>
            <li><a href="dashboard.php" class="active"><span>📊</span> Dashboard</a></li>
            <li><a href="appointments.php"><span>📅</span> Appointments</a></li>
            <li><a href="manage_slots.php"><span>🕐</span> Schedule</a></li>
            <li><a href="update_location.php"><span>📍</span> Location</a></li>
            <li><a href="messages.php"><span>💬</span> Messages</a></li>
            <li><a href="lab_reports.php"><span>📋</span> Lab Reports</a></li>
            <li><a href="profile.php"><span>👤</span> Profile</a></li>
            <li><a href="../logout.php"><span>🚪</span> Logout</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <h1><?php echo $greeting; ?>, Dr. <?php echo htmlspecialchars($doctor['full_name']); ?>!</h1>
            <p><?php echo htmlspecialchars($doctor['specialization']); ?> • <?php echo $today; ?> appointments today</p>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-content">
                    <p>Today's Patients</p>
                    <h3><?php echo $today; ?></h3>
                    <span class="change">📅 Scheduled</span>
                </div>
                <div class="stat-card-icon">📅</div>
            </div>

            <div class="stat-card">
                <div class="stat-card-content">
                    <p>Total Patients</p>
                    <h3><?php echo $total; ?></h3>
                    <span class="change up">▲ All Time</span>
                </div>
                <div class="stat-card-icon">👥</div>
            </div>

            <div class="stat-card">
                <div class="stat-card-content">
                    <p>Earnings</p>
                    <h3>Rs. <?php echo number_format($earnings, 0); ?></h3>
                    <span class="change up">▲ Total</span>
                </div>
                <div class="stat-card-icon">💰</div>
            </div>

            <div class="stat-card">
                <div class="stat-card-content">
                    <p>Lab Reports</p>
                    <h3><?php echo $reports; ?></h3>
                    <span class="change">📋 Pending</span>
                </div>
                <div class="stat-card-icon">📋</div>
            </div>
        </div>

        <div class="modern-grid">
            <!-- Current Status -->
            <div class="col-4">
                <div class="card">
                    <div class="card-header">
                        <h3>⏰ Current Status</h3>
                    </div>
                    <div class="card-body" style="text-align:center;padding:40px 20px">
                        <div style="font-size:56px;font-weight:900;color:#8b1e3f;margin-bottom:10px;">
                            <?php echo $doctor['ongoing_number'] ?? 0; ?>
                        </div>
                        <p style="font-size:16px;color:#636e72;font-weight:600;margin-bottom:24px;">Now Serving</p>
                        <a href="update_location.php" class="btn btn-primary">Update Number</a>
                    </div>
                </div>
            </div>

            <!-- Today's Appointments -->
            <div class="col-8">
                <div class="card">
                    <div class="card-header">
                        <h3>📅 Today's Appointments</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($today_appts->num_rows > 0): ?>
                            <?php while ($appt = $today_appts->fetch_assoc()): ?>
                                <div class="appt-item">
                                    <div class="appt-icon">#<?php echo $appt['booking_number']; ?></div>
                                    <div class="appt-content">
                                        <div class="appt-title"><?php echo htmlspecialchars($appt['patient']); ?></div>
                                        <div class="appt-sub"><?php echo date('h:i A', strtotime($appt['appointment_time'] ?? '00:00')); ?></div>
                                    </div>
                                    <div class="appt-time">
                                        <span class="badge badge-success"><?php echo ucfirst($appt['status']); ?></span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p style="text-align:center;color:#636e72;padding:40px">No appointments today</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3>⚡ Quick Actions</h3>
                    </div>
                    <div class="card-body" style="display:flex;gap:16px;flex-wrap:wrap">
                        <a href="appointments.php" class="btn btn-primary">📅 View All Appointments</a>
                        <a href="manage_slots.php" class="btn btn-secondary">🕐 Manage Schedule</a>
                        <a href="update_location.php" class="btn btn-secondary">📍 Update Location</a>
                        <a href="lab_reports.php" class="btn btn-secondary">📋 Lab Reports</a>
                        <a href="messages.php" class="btn btn-secondary">💬 Messages</a>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

</body>
</html>
