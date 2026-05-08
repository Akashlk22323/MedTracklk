<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'patient') {
    header("Location: ../login.php");
    exit();
}
include '../includes/config.php';

$stmt = $conn->prepare("SELECT * FROM patients WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$patient) { header("Location: ../logout.php"); exit(); }
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['report_file'])) {
    $report_name = sanitize($conn, $_POST['report_name']);
    $test_date   = sanitize($conn, $_POST['test_date']);
    $notes       = sanitize($conn, $_POST['notes']);
    $doctor_id   = intval($_POST['doctor_id']);

    $file = $_FILES['report_file'];
    $allowed = ['pdf','jpg','jpeg','png'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        $_SESSION['error'] = "Only PDF, JPG, PNG files allowed!";
    } elseif ($file['size'] > 5 * 1024 * 1024) {
        $_SESSION['error'] = "File size must be under 5MB!";
    } else {
        $filename = 'lab_' . $patient['id'] . '_' . time() . '.' . $ext;
        $dest = '../uploads/lab_reports/' . $filename;
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $stmt = $conn->prepare("INSERT INTO lab_reports (patient_id, doctor_id, report_name, report_file, test_date, notes, shared_by) VALUES (?,NULLIF(?,0),?,?,?,?,'patient')");
            $stmt->bind_param("iissss", $patient['id'], $doctor_id, $report_name, $filename, $test_date, $notes);
            $stmt->execute();
            $_SESSION['success'] = $doctor_id ? "Report uploaded and sent to doctor!" : "Report uploaded successfully!";
        } else {
            $_SESSION['error'] = "Upload failed. Please try again.";
        }
    }
    header("Location: lab_reports.php");
    exit();
}

// Handle delete
if (isset($_GET['delete'])) {
    $rid = intval($_GET['delete']);
    $stmt = $conn->prepare("SELECT report_file FROM lab_reports WHERE id = ? AND patient_id = ?");
    $stmt->bind_param("ii", $rid, $patient['id']);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    if ($r) {
        @unlink('../uploads/lab_reports/' . $r['report_file']);
        $conn->prepare("DELETE FROM lab_reports WHERE id = ?")->execute() || $conn->prepare("DELETE FROM lab_reports WHERE id = ?")->bind_param("i", $rid);
        $del = $conn->prepare("DELETE FROM lab_reports WHERE id = ?");
        $del->bind_param("i", $rid);
        $del->execute();
        $_SESSION['success'] = "Report deleted.";
    }
    header("Location: lab_reports.php");
    exit();
}

// Get my doctors (from appointments)
$doc_stmt = $conn->prepare("SELECT DISTINCT d.id, d.full_name, d.specialization FROM doctors d JOIN appointments a ON d.id = a.doctor_id WHERE a.patient_id = ? ORDER BY d.full_name");
$doc_stmt->bind_param("i", $patient['id']);
$doc_stmt->execute();
$my_doctors = $doc_stmt->get_result();

// Get reports
$rep_stmt = $conn->prepare("SELECT lr.*, d.full_name as doctor_name FROM lab_reports lr LEFT JOIN doctors d ON lr.doctor_id = d.id WHERE lr.patient_id = ? ORDER BY lr.uploaded_at DESC");
$rep_stmt->bind_param("i", $patient['id']);
$rep_stmt->execute();
$reports = $rep_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lab Reports - HealthCare Plus</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .report-card { background: #f8f9fa; border: 2px solid #e0e0e0; border-radius: 10px; padding: 20px; margin-bottom: 15px; display: flex; align-items: center; gap: 20px; transition: all 0.3s; }
        .report-card:hover { border-color: #2e7d32; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .report-icon { font-size: 48px; flex-shrink: 0; }
        .report-info { flex: 1; }
        .report-actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .upload-zone { border: 3px dashed #2e7d32; border-radius: 12px; padding: 40px; text-align: center; cursor: pointer; transition: all 0.3s; background: #f9fff9; }
        .upload-zone:hover { background: #e8f5e9; }
        .badge-sent { background: #1565c0; color: white; padding: 3px 10px; border-radius: 12px; font-size: 12px; }
        .badge-local { background: #555; color: white; padding: 3px 10px; border-radius: 12px; font-size: 12px; }
    </style>
</head>
<body class="patient-theme">
<nav class="navbar">
    <div class="main-content">
        <a href="dashboard.php" class="navbar-brand">🏥 HealthCare Plus</a>
        <ul class="navbar-menu">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="search_doctors.php">Find Doctor</a></li>
            <li><a href="my_appointments.php">Appointments</a></li>
            <li><a href="messages.php">Messages</a></li>
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
            <li><a href="messages.php">💬 Ask Doctor</a></li>
            <li><a href="lab_reports.php" class="active">🧪 Lab Reports</a></li>
            <li><a href="prescriptions.php">💊 Prescriptions</a></li>
            <li><a href="profile.php">👤 My Profile</a></li>
        </ul>
    </div></div>
    <main >
        <div class="page-header">
            <h1>🧪 My Lab Reports</h1>
            <p>Upload, manage and send lab reports to your doctors</p>
        </div>

        <?php
        if (isset($_SESSION['success'])) { echo '<div class="alert alert-success">'.$_SESSION['success'].'</div>'; unset($_SESSION['success']); }
        if (isset($_SESSION['error']))   { echo '<div class="alert alert-error">'.$_SESSION['error'].'</div>'; unset($_SESSION['error']); }
        ?>

        <!-- Upload Card -->
        <div class="card">
            <div class="card-header">📤 Upload & Send Lab Report</div>
            <form method="POST" enctype="multipart/form-data">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label>Report Name *</label>
                        <input type="text" name="report_name" required placeholder="e.g. Blood Test, CBC, X-Ray">
                    </div>
                    <div class="form-group">
                        <label>Test Date *</label>
                        <input type="date" name="test_date" required max="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Send to Doctor (Optional)</label>
                        <select name="doctor_id">
                            <option value="">Keep Private (Don't Send)</option>
                            <?php
                            $my_doctors->data_seek(0);
                            while ($d = $my_doctors->fetch_assoc()) {
                                echo '<option value="'.$d['id'].'">Dr. '.htmlspecialchars($d['full_name']).' - '.htmlspecialchars($d['specialization']).'</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Notes (Optional)</label>
                        <input type="text" name="notes" placeholder="Any notes for the doctor...">
                    </div>
                    <div class="form-group" style="grid-column:1/-1;">
                        <label>Report File * (PDF, JPG, PNG — max 5MB)</label>
                        <div class="upload-zone" onclick="document.getElementById('report_file').click()">
                            <div style="font-size:48px;">📁</div>
                            <p style="margin:10px 0 5px;font-weight:bold;">Click to choose file</p>
                            <p style="color:#999;font-size:14px;" id="file-name">PDF, JPG or PNG accepted</p>
                        </div>
                        <input type="file" id="report_file" name="report_file" required accept=".pdf,.jpg,.jpeg,.png" style="display:none" onchange="document.getElementById('file-name').textContent = this.files[0].name">
                    </div>
                </div>
                <button type="submit" class="btn btn-glass-primary" style="width:100%;padding:15px;font-size:16px;">📤 Upload Report</button>
            </form>
        </div>

        <!-- Reports List -->
        <div class="card">
            <div class="card-header">
                📋 My Reports
                <span style="float:right;background:#2e7d32;color:white;padding:4px 12px;border-radius:20px;font-size:13px;"><?php echo $reports->num_rows; ?> Total</span>
            </div>
            <?php if ($reports->num_rows > 0): ?>
                <?php while ($r = $reports->fetch_assoc()): ?>
                    <div class="report-card">
                        <div class="report-icon">
                            <?php echo strtolower(pathinfo($r['report_file'], PATHINFO_EXTENSION)) == 'pdf' ? '📄' : '🖼️'; ?>
                        </div>
                        <div class="report-info">
                            <h3 style="margin:0 0 6px;"><?php echo htmlspecialchars($r['report_name']); ?></h3>
                            <p style="margin:2px 0;color:#555;">📅 Test Date: <?php echo date('M d, Y', strtotime($r['test_date'])); ?></p>
                            <p style="margin:2px 0;color:#555;">🕐 Uploaded: <?php echo date('M d, Y h:i A', strtotime($r['uploaded_at'])); ?></p>
                            <?php if ($r['doctor_name']): ?>
                                <p style="margin:4px 0;"><span class="badge-sent">📨 Sent to Dr. <?php echo htmlspecialchars($r['doctor_name']); ?></span></p>
                            <?php else: ?>
                                <p style="margin:4px 0;"><span class="badge-local">🔒 Private</span></p>
                            <?php endif; ?>
                            <?php if ($r['notes']): ?>
                                <p style="margin:4px 0;color:#666;font-size:13px;">📝 <?php echo htmlspecialchars($r['notes']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="report-actions">
                            <a href="../uploads/lab_reports/<?php echo htmlspecialchars($r['report_file']); ?>" target="_blank" class="btn btn-sm btn-primary">👁️ View</a>
                            <a href="../uploads/lab_reports/<?php echo htmlspecialchars($r['report_file']); ?>" download class="btn btn-sm btn-success">⬇️ Download</a>
                            <a href="lab_reports.php?delete=<?php echo $r['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this report?')">🗑️ Delete</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align:center;padding:50px;color:#999;">
                    <div style="font-size:64px;">🧪</div>
                    <h3>No lab reports yet</h3>
                    <p>Upload your first lab report above</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
