<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'doctor') {
    header("Location: ../login.php");
    exit();
}
include '../includes/config.php';

$stmt = $conn->prepare("SELECT * FROM doctors WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$doctor = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$doctor) { header("Location: ../logout.php"); exit(); }

// Filter parameters
$filter_patient = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;
$filter_date    = isset($_GET['date']) ? sanitize($conn, $_GET['date']) : '';

// Build query - ONLY reports sent to this doctor
$query = "SELECT lr.*, p.full_name as patient_name, p.phone, p.date_of_birth
          FROM lab_reports lr
          JOIN patients p ON lr.patient_id = p.id
          WHERE lr.doctor_id = ?";
$params = [$doctor['id']];
$types  = 'i';

if ($filter_patient > 0) {
    $query  .= " AND lr.patient_id = ?";
    $params[] = $filter_patient;
    $types   .= 'i';
}
if (!empty($filter_date)) {
    $query  .= " AND DATE(lr.uploaded_at) = ?";
    $params[] = $filter_date;
    $types   .= 's';
}
$query .= " ORDER BY lr.uploaded_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$reports = $stmt->get_result();

// Stats
$total_stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM lab_reports WHERE doctor_id = ?");
$total_stmt->bind_param("i", $doctor['id']);
$total_stmt->execute();
$total_count = $total_stmt->get_result()->fetch_assoc()['cnt'];

$new_stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM lab_reports WHERE doctor_id = ? AND DATE(uploaded_at) = CURDATE()");
$new_stmt->bind_param("i", $doctor['id']);
$new_stmt->execute();
$new_today = $new_stmt->get_result()->fetch_assoc()['cnt'];

// Unique patients who sent reports
$patients_stmt = $conn->prepare("SELECT DISTINCT p.id, p.full_name FROM lab_reports lr JOIN patients p ON lr.patient_id = p.id WHERE lr.doctor_id = ? ORDER BY p.full_name");
$patients_stmt->bind_param("i", $doctor['id']);
$patients_stmt->execute();
$patients_list = $patients_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Lab Reports - Doctor Panel</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .stat-box { background: white; border-radius: 10px; padding: 20px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .stat-box .num { font-size: 36px; font-weight: bold; color: #2e7d32; }
        .stat-box .lbl { font-size: 13px; color: #666; margin-top: 4px; }
        .filter-bar { background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; }
        .filter-bar .f-group { display: flex; flex-direction: column; gap: 5px; min-width: 200px; }
        .filter-bar label { font-weight: bold; font-size: 13px; color: #555; }
        .filter-bar select, .filter-bar input { padding: 10px; border: 2px solid #ddd; border-radius: 8px; font-size: 14px; }
        .filter-bar select:focus, .filter-bar input:focus { outline: none; border-color: #2e7d32; }
        .report-card { background: white; border: 2px solid #e8f5e9; border-radius: 12px; padding: 20px; margin-bottom: 15px; display: flex; align-items: center; gap: 20px; box-shadow: 0 2px 6px rgba(0,0,0,0.05); transition: all 0.25s; }
        .report-card:hover { border-color: #2e7d32; box-shadow: 0 4px 16px rgba(46,125,50,0.15); transform: translateY(-1px); }
        .report-card .file-icon { font-size: 52px; flex-shrink: 0; }
        .report-card .info { flex: 1; }
        .report-card .info h3 { margin: 0 0 6px; font-size: 17px; }
        .report-card .meta { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 8px; }
        .report-card .meta span { background: #f1f8f4; color: #2e7d32; padding: 3px 10px; border-radius: 10px; font-size: 12px; font-weight: 600; }
        .report-card .actions { display: flex; flex-direction: column; gap: 8px; }
        .patient-tag { background: #e3f2fd; color: #1565c0; padding: 4px 10px; border-radius: 8px; font-size: 13px; font-weight: 600; display: inline-block; margin-bottom: 6px; }
        .new-badge { background: #e53935; color: white; font-size: 11px; padding: 2px 8px; border-radius: 10px; margin-left: 8px; }
        .empty-state { text-align: center; padding: 60px 20px; color: #999; }
        .empty-state .icon { font-size: 80px; margin-bottom: 15px; }
        .preview-modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 1000; align-items: center; justify-content: center; }
        .preview-modal.active { display: flex; }
        .preview-box { background: white; border-radius: 15px; padding: 20px; max-width: 90vw; max-height: 90vh; overflow: auto; position: relative; }
        .preview-box img { max-width: 100%; border-radius: 8px; }
    </style>
</head>
<body class="doctor-theme">
<nav class="navbar">
    <div class="main-content">
        <a href="dashboard.php" class="navbar-brand">🏥 Doctor Panel</a>
        <ul class="navbar-menu">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="appointments.php">Appointments</a></li>
            <li><a href="manage_slots.php">Time Slots</a></li>
            <li><a href="lab_reports.php" style="background:rgba(255,255,255,0.2);">Lab Reports</a></li>
            <li><a href="messages.php">Messages</a></li>
            <li><a href="update_location.php">Location</a></li>
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
            <li><a href="appointments.php">📅 My Appointments</a></li>
            <li><a href="manage_slots.php">🕐 My Time Slots</a></li>
            <li><a href="lab_reports.php" class="active">🧪 Patient Lab Reports</a></li>
            <li><a href="messages.php">💬 Messages</a></li>
            <li><a href="update_location.php">📍 Update Location</a></li>
            <li><a href="profile.php">👤 My Profile</a></li>
        </ul>
    </div></div>
    <main >
        <div class="page-header">
            <h1>🧪 Patient Lab Reports</h1>
            <p>Lab reports that patients have sent directly to you</p>
        </div>

        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-box">
                <div class="num"><?php echo $total_count; ?></div>
                <div class="lbl">Total Reports Received</div>
            </div>
            <div class="stat-box">
                <div class="num"><?php echo $new_today; ?></div>
                <div class="lbl">New Today</div>
            </div>
            <div class="stat-box">
                <div class="num"><?php echo $patients_list->num_rows; ?></div>
                <div class="lbl">Patients Shared</div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <form method="GET" style="display:flex;gap:15px;align-items:flex-end;flex-wrap:wrap;width:100%;">
                <div class="f-group">
                    <label>👤 Filter by Patient</label>
                    <select name="patient_id" onchange="this.form.submit()">
                        <option value="">All Patients</option>
                        <?php
                        $patients_list->data_seek(0);
                        while ($p = $patients_list->fetch_assoc()) {
                            $sel = ($filter_patient == $p['id']) ? 'selected' : '';
                            echo '<option value="'.$p['id'].'" '.$sel.'>'.htmlspecialchars($p['full_name']).'</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="f-group">
                    <label>📅 Filter by Upload Date</label>
                    <input type="date" name="date" value="<?php echo htmlspecialchars($filter_date); ?>" onchange="this.form.submit()">
                </div>
                <?php if ($filter_patient || $filter_date): ?>
                    <a href="lab_reports.php" class="btn btn-danger" style="padding:10px 20px;text-decoration:none;">✕ Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Reports -->
        <?php if ($reports->num_rows > 0): ?>
            <p style="color:#666;margin-bottom:15px;">Showing <strong><?php echo $reports->num_rows; ?></strong> report(s)</p>
            <?php while ($r = $reports->fetch_assoc()):
                $ext     = strtolower(pathinfo($r['report_file'], PATHINFO_EXTENSION));
                $is_pdf  = $ext === 'pdf';
                $icon    = $is_pdf ? '📄' : '🖼️';
                $age     = !empty($r['date_of_birth']) ? date_diff(date_create($r['date_of_birth']), date_create('today'))->y . 'Y' : 'N/A';
                $is_new  = (date('Y-m-d', strtotime($r['uploaded_at'])) == date('Y-m-d'));
                $file_path = '../uploads/lab_reports/' . $r['report_file'];
            ?>
            <div class="report-card">
                <div class="file-icon"><?php echo $icon; ?></div>
                <div class="info">
                    <div class="patient-tag">
                        👤 <?php echo htmlspecialchars($r['patient_name']); ?>
                        <?php if ($is_new): ?><span class="new-badge">NEW</span><?php endif; ?>
                    </div>
                    <h3>
                        <?php echo htmlspecialchars($r['report_name']); ?>
                    </h3>
                    <div class="meta">
                        <span>📅 Test: <?php echo date('M d, Y', strtotime($r['test_date'])); ?></span>
                        <span>🕐 Sent: <?php echo date('M d, Y h:i A', strtotime($r['uploaded_at'])); ?></span>
                        <span>📞 <?php echo htmlspecialchars($r['phone'] ?? 'N/A'); ?></span>
                        <span>🎂 Age: <?php echo $age; ?></span>
                    </div>
                    <?php if ($r['notes']): ?>
                        <div style="margin-top:8px;background:#fff9c4;padding:8px 12px;border-radius:8px;font-size:13px;color:#5d4037;">
                            📝 <strong>Patient Note:</strong> <?php echo htmlspecialchars($r['notes']); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="actions">
                    <?php if (!$is_pdf): ?>
                        <button class="btn btn-sm btn-info" onclick="previewImage('<?php echo htmlspecialchars($file_path); ?>', '<?php echo htmlspecialchars($r['report_name']); ?>')">
                            🔍 Preview
                        </button>
                    <?php endif; ?>
                    <a href="<?php echo $file_path; ?>" target="_blank" class="btn btn-sm btn-primary">👁️ View</a>
                    <a href="<?php echo $file_path; ?>" download class="btn btn-sm btn-success">⬇️ Download</a>
                    <a href="messages.php?chat=<?php echo $r['patient_id'] ?>&tab=chat" class="btn btn-sm btn-secondary" style="font-size:12px;padding:6px 10px;">💬 Chat</a>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <div class="icon">🧪</div>
                <h2>No Lab Reports Yet</h2>
                <p>Patients can send lab reports to you from their <strong>Lab Reports</strong> page.</p>
                <p style="font-size:13px;color:#bbb;margin-top:10px;">Reports will only appear here when a patient specifically selects <em>your name</em> when uploading.</p>
            </div>
        <?php endif; ?>
    </main>
</div>

<!-- Image Preview Modal -->
<div class="preview-modal" id="previewModal" onclick="closePreview()">
    <div class="preview-box" onclick="event.stopPropagation()">
        <button onclick="closePreview()" style="position:absolute;top:10px;right:10px;background:#e53935;color:white;border:none;border-radius:50%;width:32px;height:32px;font-size:18px;cursor:pointer;">✕</button>
        <h3 id="previewTitle" style="margin:0 0 15px;padding-right:40px;"></h3>
        <img id="previewImg" src="" alt="Lab Report Preview">
    </div>
</div>

<script>
function previewImage(src, title) {
    document.getElementById('previewImg').src = src;
    document.getElementById('previewTitle').textContent = title;
    document.getElementById('previewModal').classList.add('active');
}
function closePreview() {
    document.getElementById('previewModal').classList.remove('active');
}
</script>
</body>
</html>
