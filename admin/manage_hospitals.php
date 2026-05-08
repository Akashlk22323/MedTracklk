<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php"); exit();
}
include '../includes/config.php';

// Add hospital
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_hospital'])) {
    $name     = sanitize($conn, $_POST['name']);
    $address  = sanitize($conn, $_POST['address']);
    $city     = sanitize($conn, $_POST['city']);
    $district = sanitize($conn, $_POST['district']);
    $phone    = sanitize($conn, $_POST['phone']);
    $lat      = !empty($_POST['latitude'])  ? floatval($_POST['latitude'])  : null;
    $lng      = !empty($_POST['longitude']) ? floatval($_POST['longitude']) : null;

    if (empty($name)) {
        $_SESSION['error'] = "Hospital name is required.";
    } else {
        // Check duplicate
        $chk = $conn->prepare("SELECT id FROM hospitals WHERE name = ?");
        $chk->bind_param("s", $name);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $_SESSION['error'] = "Hospital '$name' already exists.";
        } else {
            $ins = $conn->prepare("INSERT INTO hospitals (name, address, city, district, phone, latitude, longitude) VALUES (?,?,?,?,?,?,?)");
            $ins->bind_param("sssssdd", $name, $address, $city, $district, $phone, $lat, $lng);
            if ($ins->execute()) {
                logActivity($conn, $_SESSION['user_id'], 'Add Hospital', 'Added: ' . $name);
                $_SESSION['success'] = "Hospital '$name' added successfully!";
            } else {
                $_SESSION['error'] = "Failed to add hospital.";
            }
            $ins->close();
        }
        $chk->close();
    }
    header("Location: manage_hospitals.php"); exit();
}

// Delete hospital
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $hid = intval($_GET['delete']);
    // Check if any doctors are assigned
    $chk = $conn->prepare("SELECT COUNT(*) as cnt FROM doctor_hospitals WHERE hospital_name = (SELECT name FROM hospitals WHERE id = ?)");
    $chk->bind_param("i", $hid);
    $chk->execute();
    $cnt = $chk->get_result()->fetch_assoc()['cnt'];
    $chk->close();
    if ($cnt > 0) {
        $_SESSION['error'] = "Cannot delete — $cnt doctor(s) are assigned to this hospital.";
    } else {
        $del = $conn->prepare("DELETE FROM hospitals WHERE id = ?");
        $del->bind_param("i", $hid);
        $del->execute();
        $del->close();
        $_SESSION['success'] = "Hospital deleted.";
    }
    header("Location: manage_hospitals.php"); exit();
}

// Load all hospitals with doctor count
$hospitals = $conn->query("
    SELECT h.*, 
        (SELECT COUNT(*) FROM doctor_hospitals dh WHERE dh.hospital_name = h.name) as doctor_count
    FROM hospitals h 
    ORDER BY h.city, h.name
");

// Sri Lanka major hospitals for quick-add
$quick_hospitals = [
    ["National Hospital of Sri Lanka",      "Regent Street",              "Colombo",      "Colombo",      6.9215, 79.8648],
    ["Colombo General Hospital",            "Regent Street",              "Colombo",      "Colombo",      6.9215, 79.8648],
    ["Asiri Central Hospital",              "Norris Canal Road",          "Colombo",      "Colombo",      6.9101, 79.8654],
    ["Asiri Surgical Hospital",             "Kirimandala Mawatha",        "Colombo",      "Colombo",      6.9008, 79.8661],
    ["Nawaloka Hospital",                   "Sri Saugathhodaya Mawatha",  "Colombo",      "Colombo",      6.8985, 79.8566],
    ["Durdans Hospital",                    "Alfred Place",               "Colombo",      "Colombo",      6.9012, 79.8553],
    ["Lanka Hospital",                      "Elvitigala Mawatha",         "Colombo",      "Colombo",      6.8972, 79.8621],
    ["Ninewells Hospital",                  "Kalubowila",                 "Dehiwala",     "Colombo",      6.8491, 79.8713],
    ["Oasis Hospital",                      "Sulaiman Terrace",           "Colombo",      "Colombo",      6.9105, 79.8530],
    ["Browns Hospital",                     "Flower Road",                "Colombo",      "Colombo",      6.9050, 79.8490],
    ["Hemas Hospital Wattala",              "Negombo Road",               "Wattala",      "Gampaha",      7.0108, 79.8930],
    ["Hemas Hospital Thalahena",            "Malabe Road",                "Malabe",       "Colombo",      6.9175, 79.9764],
    ["Teaching Hospital Karapitiya",        "Karapitiya",                 "Galle",        "Galle",        6.0367, 80.2170],
    ["Ruhunu Hospital Galle",               "Wakwella Road",              "Galle",        "Galle",        6.0316, 80.2190],
    ["District General Hospital Matara",    "Hospital Road",              "Matara",       "Matara",       5.9524, 80.5520],
    ["Kandy General Hospital",              "William Gopallawa Mawatha",  "Kandy",        "Kandy",        7.2906, 80.6337],
    ["Teaching Hospital Peradeniya",        "Peradeniya",                 "Kandy",        "Kandy",        7.2668, 80.5966],
    ["District General Hospital Kurunegala","Colombo Road",               "Kurunegala",   "Kurunegala",   7.4867, 80.3647],
    ["Teaching Hospital Jaffna",            "Hospital Road",              "Jaffna",       "Jaffna",       9.6615, 80.0255],
    ["District General Hospital Badulla",   "Hospital Road",              "Badulla",      "Badulla",      6.9895, 81.0557],
    ["District General Hospital Ratnapura", "Colombo Road",               "Ratnapura",    "Ratnapura",    6.6828, 80.4014],
    ["Teaching Hospital Anuradhapura",      "Maithripala Senanayake Mw",  "Anuradhapura", "North Central", 8.3114, 80.4037],
    ["District General Hospital Trincomalee","Hospital Road",             "Trincomalee",  "Trincomalee"],
    ["District General Hospital Batticaloa","Bar Road",                   "Batticaloa",   "Batticaloa"],
    ["Base Hospital Negombo",               "Colombo Road",               "Negombo",      "Gampaha"],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Hospitals - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .quick-btns  { display:flex; flex-wrap:wrap; gap:8px; margin:10px 0 16px; }
        .quick-btn   { padding:6px 14px; background:#e8f5e9; border:2px solid #a5d6a7; border-radius:20px; cursor:pointer; font-size:13px; color:#2e7d32; transition:all 0.2s; }
        .quick-btn:hover { background:#2e7d32; color:white; border-color:#2e7d32; }
        .hosp-grid   { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:15px; margin-top:16px; }
        .hosp-card   { background:white; border:2px solid #e0e0e0; border-radius:10px; padding:16px; }
        .hosp-card:hover { border-color:#2e7d32; }
        .hosp-name   { font-weight:bold; font-size:15px; color:#1565c0; margin-bottom:6px; }
        .hosp-meta   { font-size:13px; color:#666; margin:2px 0; }
        .doc-badge   { display:inline-block; background:#e8f5e9; color:#2e7d32; padding:2px 10px; border-radius:10px; font-size:12px; font-weight:bold; margin-top:8px; }
    </style>
</head>
<body class="admin-theme">
<nav class="navbar">
    <div class="main-content">
        <a href="dashboard.php" class="navbar-brand">🏥 Admin Panel</a>
        <ul class="navbar-menu">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="manage_doctors.php">Doctors</a></li>
            <li><a href="manage_hospitals.php" style="background:rgba(255,255,255,0.2);">Hospitals</a></li>
            <li><a href="manage_schedules.php">Schedules</a></li>
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
            <li><a href="manage_hospitals.php" class="active">🏥 Hospitals</a></li>
            <li><a href="manage_schedules.php">📅 Schedules</a></li>
            <li><a href="manage_pharmacists.php">💊 Pharmacists</a></li>
            <li><a href="manage_appointments.php">📋 Appointments</a></li>
            <li><a href="reports.php">📊 Reports</a></li>
            <li><a href="manage_admins.php">⚙️ Admins</a></li>
        </ul>
    </div></div>
    <main >
        <div class="page-header">
            <h1>🏥 Manage Hospitals</h1>
            <p>Add Sri Lankan hospitals that doctors can be assigned to</p>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <!-- Add Hospital Form -->
        <div class="card">
            <div class="card-header">➕ Add New Hospital</div>

            <!-- Quick-add buttons -->
            <p style="font-weight:bold; margin-bottom:6px;">⚡ Quick Add — click any to fill the form instantly:</p>
            <div class="quick-btns">
                <?php foreach ($quick_hospitals as $q): ?>
                <button type="button" class="quick-btn"
                    onclick="fillForm(<?php echo htmlspecialchars(json_encode($q), ENT_QUOTES); ?>)">
                    <?php echo htmlspecialchars($q[0]); ?>
                </button>
                <?php endforeach; ?>
            </div>

            <form method="POST">
                <input type="hidden" name="add_hospital" value="1">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                    <div class="form-group" style="grid-column:1/-1;">
                        <label>Hospital Name *</label>
                        <input type="text" name="name" id="h_name" required placeholder="e.g. Colombo General Hospital">
                    </div>
                    <div class="form-group" style="grid-column:1/-1;">
                        <label>Address</label>
                        <input type="text" name="address" id="h_address" placeholder="Street address">
                    </div>
                    <div class="form-group">
                        <label>City *</label>
                        <input type="text" name="city" id="h_city" required placeholder="e.g. Colombo">
                    </div>
                    <div class="form-group">
                        <label>District *</label>
                        <input type="text" name="district" id="h_district" required placeholder="e.g. Colombo">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" id="h_phone" placeholder="e.g. 011-2691111">
                    </div>
                    <div class="form-group">
                        <label>Latitude <small style="color:#888;font-weight:normal;">(for map)</small></label>
                        <input type="number" name="latitude" id="h_lat" step="any" placeholder="e.g. 6.9271">
                    </div>
                    <div class="form-group">
                        <label>Longitude <small style="color:#888;font-weight:normal;">(for map)</small></label>
                        <input type="number" name="longitude" id="h_lng" step="any" placeholder="e.g. 79.8612">
                    </div>
                </div>
                <button type="submit" class="btn btn-glass-primary" style="width:100%; margin-top:10px; padding:13px;">
                    ➕ Add Hospital
                </button>
            </form>
        </div>

        <!-- Hospitals List -->
        <div class="card">
            <div class="card-header">
                All Hospitals
                <span style="float:right; background:#1565c0; color:white; padding:3px 12px; border-radius:12px; font-size:13px;">
                    <?php echo $hospitals->num_rows; ?> total
                </span>
            </div>
            <div class="hosp-grid">
                <?php while ($h = $hospitals->fetch_assoc()): ?>
                <div class="hosp-card">
                    <div class="hosp-name">🏥 <?php echo htmlspecialchars($h['name']); ?></div>
                    <?php if ($h['address']): ?>
                        <div class="hosp-meta">📍 <?php echo htmlspecialchars($h['address']); ?></div>
                    <?php endif; ?>
                    <div class="hosp-meta">🏙️ <?php echo htmlspecialchars($h['city']); ?>, <?php echo htmlspecialchars($h['district']); ?></div>
                    <?php if ($h['phone']): ?>
                        <div class="hosp-meta">📞 <?php echo htmlspecialchars($h['phone']); ?></div>
                    <?php endif; ?>
                    <div><span class="doc-badge">👨‍⚕️ <?php echo $h['doctor_count']; ?> doctors</span></div>
                    <div style="margin-top:10px;">
                        <?php if ($h['doctor_count'] == 0): ?>
                            <a href="?delete=<?php echo $h['id']; ?>" class="btn btn-sm btn-danger"
                               onclick="return confirm('Delete <?php echo addslashes($h['name']); ?>?')">🗑️ Delete</a>
                        <?php else: ?>
                            <small style="color:#aaa;">Cannot delete — doctors assigned</small>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </main>
</div>
<script>
function fillForm(data) {
    document.getElementById('h_name').value    = data[0];
    document.getElementById('h_address').value = data[1];
    document.getElementById('h_city').value    = data[2];
    document.getElementById('h_district').value= data[3];
    document.getElementById('h_phone').value   = '';
    document.getElementById('h_lat').value     = data[4] || '';
    document.getElementById('h_lng').value     = data[5] || '';
    document.getElementById('h_name').focus();
    document.getElementById('h_name').scrollIntoView({behavior:'smooth', block:'center'});
}
</script>
</body>
</html>
