<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pharmacist') {
    header("Location: ../login.php"); exit();
}
include '../includes/config.php';

$stmt = $conn->prepare("SELECT p.*, u.username, u.email FROM pharmacists p JOIN users u ON p.user_id = u.id WHERE p.user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']); $stmt->execute();
$pharmacist = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$pharmacist) { header("Location: ../logout.php"); exit(); }

// Stats
$pending = $conn->query("SELECT COUNT(*) c FROM prescriptions WHERE status='pending'")->fetch_assoc()['c'];
$completed = $conn->query("SELECT COUNT(*) c FROM prescriptions WHERE status='completed'")->fetch_assoc()['c'];
$total = $conn->query("SELECT COUNT(*) c FROM prescriptions")->fetch_assoc()['c'];
$today = $conn->query("SELECT COUNT(*) c FROM prescriptions WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['c'];

// Recent prescriptions
$recent = $conn->query("SELECT pr.*, p.full_name as patient, d.full_name as doctor 
    FROM prescriptions pr 
    LEFT JOIN patients p ON pr.patient_id = p.id 
    LEFT JOIN doctors d ON pr.doctor_id = d.id 
    ORDER BY pr.created_at DESC LIMIT 6");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacist Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="pharmacist-theme">

<nav class="navbar">
    <div class="container">
        <a href="dashboard.php" class="navbar-brand">🏥 HealthCare Plus</a>
        <div class="navbar-search">
            <input type="text" placeholder="Search...">
        </div>
        <div class="navbar-user">
            <span><?php echo htmlspecialchars($pharmacist['full_name']); ?></span>
            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($pharmacist['full_name']); ?>&background=8b1e3f&color=fff&size=42" alt="Avatar">
        </div>
    </div>
</nav>

<div class="dashboard-container">
    <aside class="sidebar">
        <ul>
            <li><a href="dashboard.php" class="active"><span>📊</span> Dashboard</a></li>
            <li><a href="prescriptions.php"><span>💊</span> Prescriptions</a></li>
            <li><a href="inventory.php"><span>📦</span> Inventory</a></li>
            <li><a href="profile.php"><span>👤</span> Profile</a></li>
            <li><a href="../logout.php"><span>🚪</span> Logout</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <h1>Pharmacist Dashboard</h1>
            <p>Manage prescriptions and inventory • <?php echo $pending; ?> pending</p>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-content">
                    <p>Pending</p>
                    <h3><?php echo $pending; ?></h3>
                    <span class="change">⏳ To Process</span>
                </div>
                <div class="stat-card-icon">⏳</div>
            </div>

            <div class="stat-card">
                <div class="stat-card-content">
                    <p>Completed</p>
                    <h3><?php echo $completed; ?></h3>
                    <span class="change up">▲ Processed</span>
                </div>
                <div class="stat-card-icon">✅</div>
            </div>

            <div class="stat-card">
                <div class="stat-card-content">
                    <p>Total</p>
                    <h3><?php echo $total; ?></h3>
                    <span class="change">📋 All Time</span>
                </div>
                <div class="stat-card-icon">💊</div>
            </div>

            <div class="stat-card">
                <div class="stat-card-content">
                    <p>Today</p>
                    <h3><?php echo $today; ?></h3>
                    <span class="change">📅 New</span>
                </div>
                <div class="stat-card-icon">📅</div>
            </div>
        </div>

        <!-- Recent Prescriptions -->
        <div class="card">
            <div class="card-header">
                <h3>💊 Recent Prescriptions</h3>
                <a href="prescriptions.php" class="btn btn-sm btn-secondary">View All</a>
            </div>
            <div class="card-body">
                <?php if ($recent->num_rows > 0): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Patient</th>
                                    <th>Doctor</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($rx = $recent->fetch_assoc()): ?>
                                <tr>
                                    <td><strong>#<?php echo $rx['id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($rx['patient'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($rx['doctor'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($rx['created_at'])); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $rx['status'] == 'completed' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($rx['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="prescriptions.php?view=<?php echo $rx['id']; ?>" class="btn btn-sm btn-primary">View</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="text-align:center;color:#636e72;padding:40px">No prescriptions yet</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h3>⚡ Quick Actions</h3>
            </div>
            <div class="card-body" style="display:flex;gap:16px;flex-wrap:wrap">
                <a href="prescriptions.php?filter=pending" class="btn btn-primary">⏳ View Pending</a>
                <a href="prescriptions.php" class="btn btn-secondary">💊 All Prescriptions</a>
                <a href="inventory.php" class="btn btn-secondary">📦 Manage Inventory</a>
            </div>
        </div>
    </main>
</div>

</body>
</html>
