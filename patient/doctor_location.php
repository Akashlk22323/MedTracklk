<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'patient') {
    header("Location: ../login.php"); exit();
}
include '../includes/config.php';

// Get patient id
$ps = $conn->prepare("SELECT id FROM patients WHERE user_id = ?");
$ps->bind_param("i", $_SESSION['user_id']); $ps->execute();
$pat_row = $ps->get_result()->fetch_assoc(); $ps->close();
if (!$pat_row) { header("Location: ../logout.php"); exit(); }
$patient_id = $pat_row['id'];

$doctor_id = intval($_GET['doctor_id'] ?? 0);

// ── NO doctor_id → show list of ALL doctors this patient has booked ──
if ($doctor_id <= 0) {
    // Fetch distinct doctors the patient has ANY appointment with (any status, any date)
    $ds = $conn->prepare("
        SELECT DISTINCT d.id, d.full_name, d.specialization, d.current_hospital,
               d.consultation_fee, d.accepts_video_call,
               COUNT(a.id) as total_appts,
               MAX(a.appointment_date) as latest_date,
               SUM(CASE WHEN a.status='confirmed' THEN 1 ELSE 0 END) as confirmed_count
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.id
        WHERE a.patient_id = ?
        GROUP BY d.id
        ORDER BY latest_date DESC
    ");
    $ds->bind_param("i", $patient_id); $ds->execute();
    $booked_doctors = $ds->get_result()->fetch_all(MYSQLI_ASSOC); $ds->close();
    $today_name = date('l');
    $now_time   = date('H:i:s');
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Locations - HealthCare Plus</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .doc-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:18px; }
        .doc-card {
            background:white; border-radius:14px; padding:20px;
            border:2px solid #e8e8e8; transition:all 0.2s;
            display:flex; flex-direction:column; gap:10px;
        }
        .doc-card:hover { border-color:#e22454; box-shadow:0 6px 20px rgba(226,36,84,0.10); transform:translateY(-2px); }
        .doc-avatar { width:52px; height:52px; border-radius:50%; background:linear-gradient(135deg,#e22454,#ff6b8a); display:flex; align-items:center; justify-content:center; font-size:24px; flex-shrink:0; }
        .doc-head { display:flex; align-items:center; gap:12px; }
        .doc-name { font-size:16px; font-weight:700; color:#1a1a2e; }
        .doc-spec { font-size:13px; color:#888; margin-top:2px; }
        .status-pill { display:inline-flex; align-items:center; gap:5px; padding:5px 12px; border-radius:20px; font-size:12px; font-weight:700; }
        .pill-at    { background:#e8f5e9; color:#2e7d32; }
        .pill-trav  { background:#fff8e1; color:#f57c00; }
        .pill-off   { background:#f5f5f5; color:#999; }
        .loc-btn { display:block; width:100%; padding:11px; background:#e22454; color:white; border:none; border-radius:10px; font-size:14px; font-weight:700; text-align:center; text-decoration:none; cursor:pointer; transition:background 0.2s; }
        .loc-btn:hover { background:#c41e48; }
        .empty-state { text-align:center; padding:60px 20px; color:#aaa; }
        .empty-state .icon { font-size:56px; margin-bottom:14px; }
    </style>
</head>
<body class="patient-theme">
<nav class="navbar">
    <div class="main-content">
        <a href="dashboard.php" class="navbar-brand">🏥 HealthCare Plus</a>
        <ul class="navbar-menu">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="search_doctors.php">Search Doctors</a></li>
            <li><a href="my_appointments.php">My Appointments</a></li>
            <li><a href="messages.php">Messages</a></li>
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
            <li><a href="search_doctors.php">🔍 Search Doctors</a></li>
            <li><a href="my_appointments.php">📅 My Appointments</a></li>
            <li><a href="doctor_location.php" class="active">📍 Doctor Locations</a></li>
            <li><a href="messages.php">💬 Ask Doctor</a></li>
            <li><a href="lab_reports.php">📋 Lab Reports</a></li>
            <li><a href="prescriptions.php">💊 Prescriptions</a></li>
            <li><a href="profile.php">👤 My Profile</a></li>
        </ul>
    </div></div>
    <main >
        <div class="page-header">
            <h1>📍 Doctor Locations</h1>
            <p>Track your doctors in real time — available for all appointments you've booked</p>
        </div>

        <?php if (empty($booked_doctors)): ?>
        <div class="card">
            <div class="empty-state">
                <div class="icon">📍</div>
                <p style="font-size:16px;font-weight:bold;color:#888;">No booked doctors yet</p>
                <p style="font-size:14px;margin-top:6px;">Book an appointment to track your doctor's location</p>
                <a href="search_doctors.php" class="btn btn-glass-primary" style="margin-top:16px;">Find a Doctor</a>
            </div>
        </div>
        <?php else: ?>
        <div class="doc-grid">
            <?php foreach ($booked_doctors as $doc):
                // Quick live status check for today
                $qs = $conn->prepare("
                    SELECT s.start_time, s.end_time, s.hospital_name
                    FROM schedules s
                    WHERE s.doctor_id = ? AND s.day_of_week = ?
                    ORDER BY s.start_time ASC
                ");
                $qs->bind_param("is", $doc['id'], $today_name); $qs->execute();
                $sessions = $qs->get_result()->fetch_all(MYSQLI_ASSOC); $qs->close();

                $live_status = 'off';
                $live_label  = 'Off schedule today';
                $live_where  = '';
                foreach ($sessions as $sess) {
                    if ($now_time >= $sess['start_time'] && $now_time <= $sess['end_time']) {
                        $live_status = 'at'; $live_where = $sess['hospital_name']; break;
                    }
                    if ($now_time < $sess['start_time'] && $live_status !== 'at') {
                        $live_status = 'travelling'; $live_where = $sess['hospital_name']; break;
                    }
                }
                if ($live_status === 'at')        $live_label = '🏥 At ' . htmlspecialchars($live_where);
                elseif ($live_status === 'travelling') $live_label = '🚗 Heading to ' . htmlspecialchars($live_where);
            ?>
            <div class="doc-card">
                <div class="doc-head">
                    <div class="doc-avatar">👨‍⚕️</div>
                    <div>
                        <div class="doc-name">Dr. <?php echo htmlspecialchars($doc['full_name']); ?></div>
                        <div class="doc-spec"><?php echo htmlspecialchars($doc['specialization']); ?></div>
                    </div>
                </div>
                <div>
                    <span class="status-pill <?php echo $live_status==='at' ? 'pill-at' : ($live_status==='travelling' ? 'pill-trav' : 'pill-off'); ?>">
                        <?php echo $live_status==='at' ? '🟢' : ($live_status==='travelling' ? '🟡' : '⚫'); ?>
                        <?php echo $live_label; ?>
                    </span>
                </div>
                <div style="font-size:12px;color:#aaa;">
                    <?php echo $doc['confirmed_count']; ?> confirmed · <?php echo $doc['total_appts']; ?> total appointments
                    · Last: <?php echo date('M d, Y', strtotime($doc['latest_date'])); ?>
                </div>
                <a href="doctor_location.php?doctor_id=<?php echo $doc['id']; ?>" class="loc-btn">
                    📍 View Live Location
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
    <?php
    exit(); // Stop — don't fall through to single-doctor view
}

// ── HAS doctor_id → validate patient has booked this doctor (any time, any status) ──
$auth = $conn->prepare("
    SELECT COUNT(*) as c FROM appointments
    WHERE patient_id = ? AND doctor_id = ?
");
$auth->bind_param("ii", $patient_id, $doctor_id); $auth->execute();
$auth_count = $auth->get_result()->fetch_assoc()['c']; $auth->close();

if ($auth_count === 0) {
    // Patient never booked this doctor — redirect to their locations list
    $_SESSION['error'] = "You can only view locations of doctors you have booked.";
    header("Location: doctor_location.php"); exit();
}

// Load doctor
$stmt = $conn->prepare("SELECT * FROM doctors WHERE id = ?");
$stmt->bind_param("i", $doctor_id); $stmt->execute();
$doctor = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$doctor) { header("Location: doctor_location.php"); exit(); }

// ── Schedule-based location logic ──────────────────────────────
// Get today's day name and current time
$today     = date('l');        // e.g. "Monday"
$now_time  = date('H:i:s');    // e.g. "14:30:00"

// Get ALL of today's sessions ordered by time
$sched_stmt = $conn->prepare("
    SELECT s.*, h.latitude, h.longitude, h.address
    FROM schedules s
    LEFT JOIN hospitals h ON h.name = s.hospital_name
    WHERE s.doctor_id = ? AND s.day_of_week = ?
    ORDER BY s.start_time ASC
");
$sched_stmt->bind_param("is", $doctor_id, $today);
$sched_stmt->execute();
$all_sessions = $sched_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$sched_stmt->close();

// Determine status
$status        = 'private';
$current_hosp  = null;
$next_hosp     = null;
$last_hosp     = null;

foreach ($all_sessions as $session) {
    // Session happening RIGHT NOW
    if ($now_time >= $session['start_time'] && $now_time <= $session['end_time']) {
        $status       = 'at_hospital';
        $current_hosp = $session;
        // DO NOT break — continue to find next_hosp after this session
        continue;
    }
    // Session already finished
    if ($now_time > $session['end_time']) {
        // Only update last_hosp if we haven't found current yet
        if ($status !== 'at_hospital') {
            $last_hosp = $session;
        }
    }
    // Next upcoming session (first future one found)
    if ($now_time < $session['start_time'] && $next_hosp === null) {
        $next_hosp = $session;
    }
}

// If not at hospital but has a future session today → travelling
if ($status === 'private' && $next_hosp !== null) {
    $status = 'travelling';
}

// ── FALLBACK: if still private, use doctor's manually-set current_hospital ──
// This covers cases where doctor has no schedule slots entered but did set their location manually
if ($status === 'private') {
    $manual_hosp = trim($doctor['current_hospital'] ?? '');
    if ($manual_hosp && $manual_hosp !== '') {
        // Try to load coords from hospitals table
        $mh = $conn->prepare("SELECT * FROM hospitals WHERE name = ? LIMIT 1");
        $mh->bind_param("s", $manual_hosp); $mh->execute();
        $mh_row = $mh->get_result()->fetch_assoc(); $mh->close();

        $status = 'at_hospital';
        $current_hosp = [
            'hospital_name' => $manual_hosp,
            'start_time'    => '00:00:00',
            'end_time'      => '23:59:00',
            'latitude'      => $mh_row['latitude']  ?? null,
            'longitude'     => $mh_row['longitude'] ?? null,
            'address'       => $mh_row['address']   ?? '',
        ];
        // Also check for next_hospital set manually
        $manual_next = trim($doctor['next_hospital'] ?? '');
        if ($manual_next && $manual_next !== '') {
            $mn = $conn->prepare("SELECT * FROM hospitals WHERE name = ? LIMIT 1");
            $mn->bind_param("s", $manual_next); $mn->execute();
            $mn_row = $mn->get_result()->fetch_assoc(); $mn->close();
            $next_hosp = [
                'hospital_name' => $manual_next,
                'start_time'    => '00:00:00',
                'end_time'      => '23:59:00',
                'latitude'      => $mn_row['latitude']  ?? null,
                'longitude'     => $mn_row['longitude'] ?? null,
                'address'       => $mn_row['address']   ?? '',
            ];
        }
    }
}

// Helper: safe time arithmetic — convert HH:MM:SS to today's timestamp
function timeToday($time_str) {
    return strtotime(date('Y-m-d') . ' ' . $time_str);
}

// Load weekly schedule for display

// Load weekly schedule for display
$week_stmt = $conn->prepare("
    SELECT * FROM schedules WHERE doctor_id = ?
    ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), start_time
");
$week_stmt->bind_param("i", $doctor_id);
$week_stmt->execute();
$weekly = $week_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$week_stmt->close();

// ── Travel time estimation ─────────────────────────────────────
// Haversine formula: distance in km between two lat/lng points
function haversine($lat1, $lon1, $lat2, $lon2) {
    $R = 6371; // Earth radius km
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2)
       + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
       * sin($dLon/2) * sin($dLon/2);
    return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
}

// Sri Lanka realistic road factor:
// Straight-line × 1.4 ≈ actual road distance (winding roads, city routes)
// Urban speed (< 20km apart): ~25 km/h average with traffic
// Inter-city  (≥ 20km apart): ~55 km/h average on A-roads
function estimateTravelTime($dist_km) {
    $road_dist = $dist_km * 1.4; // road factor
    $speed     = $road_dist < 20 ? 25 : 55;
    $minutes   = round(($road_dist / $speed) * 60);
    // Round to nearest 5 for natural display
    $minutes   = ceil($minutes / 5) * 5;
    if ($minutes < 5)  $minutes = 5;
    return [
        'minutes'   => $minutes,
        'road_km'   => round($road_dist, 1),
        'label'     => $minutes >= 60
            ? floor($minutes/60) . 'h ' . ($minutes%60 > 0 ? ($minutes%60) . 'min' : '')
            : $minutes . ' min'
    ];
}

$travel_info  = null;
$next_dist    = null;

// Travelling: estimate from last → next
// last_hosp may be null if this is the first session of the day (doctor hasn't started yet)
if ($status === 'travelling' && $next_hosp && $next_hosp['latitude']) {

    // If last_hosp exists and has coords, calculate from there; otherwise no distance line
    if ($last_hosp && $last_hosp['latitude']) {
        $dist_km     = haversine($last_hosp['latitude'], $last_hosp['longitude'],
                                 $next_hosp['latitude'], $next_hosp['longitude']);
        $travel_info = estimateTravelTime($dist_km);
        $travel_info['raw_km'] = round($dist_km, 1);

        // Minutes elapsed since left last hospital
        $mins_elapsed = round((timeToday($now_time) - timeToday($last_hosp['end_time'])) / 60);
        $travel_info['mins_elapsed']   = max(0, $mins_elapsed);
        $travel_info['mins_remaining'] = max(0, $travel_info['minutes'] - $mins_elapsed);
        $travel_info['progress_pct']   = $travel_info['minutes'] > 0
            ? min(100, round(($mins_elapsed / $travel_info['minutes']) * 100))
            : 100;
    } else {
        // No prior hospital — doctor is heading to first session
        $travel_info = ['no_origin' => true];
    }

    // Minutes until next session starts (always calculable)
    $mins_until = round((timeToday($next_hosp['start_time']) - timeToday($now_time)) / 60);
    $travel_info['mins_until_session'] = max(0, $mins_until);
}

// At hospital: distance/time to NEXT hospital
if ($status === 'at_hospital' && $next_hosp
    && $current_hosp['latitude'] && $next_hosp['latitude']) {
    $dist_km   = haversine($current_hosp['latitude'], $current_hosp['longitude'],
                           $next_hosp['latitude'],   $next_hosp['longitude']);
    $next_dist = estimateTravelTime($dist_km);
    $next_dist['raw_km'] = round($dist_km, 1);
}

// Time remaining in current session
$mins_left_session = null;
if ($status === 'at_hospital' && $current_hosp['end_time'] !== '23:59:00') {
    $mins_left_session = max(0, round((timeToday($current_hosp['end_time']) - timeToday($now_time)) / 60));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Location - HealthCare Plus</title>
    <link rel="stylesheet" href="../css/style.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        #map { height: 440px; border-radius: 12px; border: 2px solid #e0e0e0; }

        .status-box { border-radius: 12px; padding: 22px 24px; margin-bottom: 20px; display:flex; align-items:flex-start; gap:16px; }
        .status-at   { background: #e8f5e9; border: 2px solid #a5d6a7; }
        .status-trav { background: #fff8e1; border: 2px solid #ffe082; }
        .status-priv { background: #f5f5f5; border: 2px solid #e0e0e0; }

        .status-icon { font-size: 40px; flex-shrink:0; }
        .status-title{ font-size: 18px; font-weight: bold; margin-bottom: 5px; }
        .status-sub  { font-size: 14px; color: #666; }

        .hosp-row    { display:flex; align-items:center; gap:12px; background:white; border:2px solid #e0e0e0; border-radius:10px; padding:14px 16px; margin-bottom:10px; }
        .hosp-dot    { width:14px; height:14px; border-radius:50%; flex-shrink:0; }
        .dot-green   { background:#2e7d32; }
        .dot-blue    { background:#1565c0; }
        .dot-grey    { background:#bbb; }
        .hosp-info   { flex:1; }
        .hosp-name   { font-weight:bold; font-size:15px; }
        .hosp-time   { font-size:13px; color:#888; margin-top:2px; }

        .route-arrow { text-align:center; font-size:22px; color:#f57c00; margin:4px 0; }

        .sched-table { width:100%; border-collapse:collapse; font-size:14px; }
        .sched-table th { background:#f5f5f5; padding:10px 12px; text-align:left; border-bottom:2px solid #e0e0e0; }
        .sched-table td { padding:10px 12px; border-bottom:1px solid #f0f0f0; }
        .today-row   { background:#fffde7; }
        .now-row     { background:#e8f5e9; font-weight:bold; }

        .privacy-note{ background:#fff3cd; border:1px solid #ffc107; border-radius:8px; padding:12px 16px; font-size:13px; color:#856404; margin-top:16px; }
    </style>
</head>
<body class="patient-theme">
<nav class="navbar">
    <div class="main-content">
        <a href="dashboard.php" class="navbar-brand">🏥 HealthCare Plus</a>
        <ul class="navbar-menu">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="search_doctors.php">Search Doctors</a></li>
            <li><a href="my_appointments.php">Appointments</a></li>
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
            <li><a href="doctor_location.php" class="active">📍 Doctor Locations</a></li>
            <li><a href="../video_room.php">📹 Video Consultation</a></li>
            <li><a href="messages.php">💬 Messages</a></li>
            <li><a href="lab_reports.php">🧪 Lab Reports</a></li>
            <li><a href="prescriptions.php">💊 Prescriptions</a></li>
            <li><a href="profile.php">👤 My Profile</a></li>
        </ul>
    </div></div>

    <main >
        <div class="page-header">
            <h1>📍 Dr. <?php echo htmlspecialchars($doctor['full_name']); ?></h1>
            <p><?php echo htmlspecialchars($doctor['specialization']); ?> &nbsp;·&nbsp; Today: <?php echo $today; ?>, <?php echo date('h:i A'); ?></p>
        </div>

        <!-- ── STATUS BOX ── -->
        <?php if ($status === 'at_hospital'): ?>
        <div class="status-box status-at">
            <div class="status-icon">🏥</div>
            <div style="flex:1;">
                <div class="status-title" style="color:#2e7d32;">Currently at Hospital</div>
                <div class="status-sub">
                    Dr. <?php echo htmlspecialchars($doctor['full_name']); ?> is now at
                    <strong><?php echo htmlspecialchars($current_hosp['hospital_name']); ?></strong><br>
                    <?php if ($current_hosp['end_time'] !== '23:59:00'): ?>
                    Session: <?php echo date('h:i A', strtotime($current_hosp['start_time'])); ?>
                    – <?php echo date('h:i A', strtotime($current_hosp['end_time'])); ?>
                    <?php endif; ?>
                    <?php if ($mins_left_session !== null): ?>
                    &nbsp;·&nbsp; <span style="background:#2e7d32;color:white;padding:2px 8px;border-radius:8px;font-size:12px;font-weight:bold;">
                        ⏱️ <?php echo $mins_left_session > 0 ? $mins_left_session . ' min left' : 'Ending soon'; ?>
                    </span>
                    <?php endif; ?>
                </div>
                <?php if ($next_dist && $next_hosp): ?>
                <div style="margin-top:10px;padding:10px 14px;background:rgba(255,255,255,0.7);border-radius:8px;font-size:13px;color:#555;">
                    🚗 Next stop: <strong><?php echo htmlspecialchars($next_hosp['hospital_name']); ?></strong>
                    at <?php echo date('h:i A', strtotime($next_hosp['start_time'])); ?>
                    &nbsp;·&nbsp; ~<?php echo $next_dist['road_km']; ?> km
                    &nbsp;·&nbsp; ~<?php echo $next_dist['label']; ?> drive
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php elseif ($status === 'travelling'): ?>
        <div class="status-box status-trav">
            <div class="status-icon">🚗</div>
            <div style="flex:1;">
                <div class="status-title" style="color:#f57c00;">Currently Travelling</div>
                <div class="status-sub">
                    Dr. <?php echo htmlspecialchars($doctor['full_name']); ?> is en route to
                    <strong><?php echo htmlspecialchars($next_hosp['hospital_name']); ?></strong>
                    — session starts at <strong><?php echo date('h:i A', strtotime($next_hosp['start_time'])); ?></strong>
                    <?php if ($travel_info && isset($travel_info['mins_until_session'])): ?>
                    &nbsp;·&nbsp; <span style="background:#f57c00;color:white;padding:2px 8px;border-radius:8px;font-size:12px;font-weight:bold;">
                        ⏱️ <?php echo $travel_info['mins_until_session'] > 0 ? $travel_info['mins_until_session'] . ' min away' : 'Starting now'; ?>
                    </span>
                    <?php endif; ?>
                </div>

                <?php if ($travel_info && !isset($travel_info['no_origin'])): ?>
                <div style="margin-top:12px;">
                    <!-- Stats row -->
                    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:10px;">
                        <div style="background:rgba(255,255,255,0.75);border-radius:8px;padding:8px 14px;text-align:center;font-size:13px;min-width:90px;">
                            📏 <strong><?php echo $travel_info['road_km']; ?> km</strong><br>
                            <span style="font-size:11px;color:#888;">road distance</span>
                        </div>
                        <div style="background:rgba(255,255,255,0.75);border-radius:8px;padding:8px 14px;text-align:center;font-size:13px;min-width:90px;">
                            🕐 <strong>~<?php echo $travel_info['label']; ?></strong><br>
                            <span style="font-size:11px;color:#888;">total drive</span>
                        </div>
                        <div style="background:rgba(255,255,255,0.75);border-radius:8px;padding:8px 14px;text-align:center;font-size:13px;min-width:90px;">
                            ⏳ <strong><?php echo $travel_info['mins_remaining'] > 0 ? '~'.$travel_info['mins_remaining'].' min' : 'Arriving soon'; ?></strong><br>
                            <span style="font-size:11px;color:#888;">est. remaining</span>
                        </div>
                        <div style="background:rgba(255,255,255,0.75);border-radius:8px;padding:8px 14px;text-align:center;font-size:13px;min-width:90px;">
                            📅 <strong><?php echo $travel_info['mins_until_session'] > 0 ? $travel_info['mins_until_session'].' min' : 'Now'; ?></strong><br>
                            <span style="font-size:11px;color:#888;">until session</span>
                        </div>
                    </div>
                    <!-- Progress bar -->
                    <div style="background:rgba(255,255,255,0.5);border-radius:20px;height:8px;overflow:hidden;">
                        <div style="background:#f57c00;height:100%;border-radius:20px;width:<?php echo $travel_info['progress_pct']; ?>%;"></div>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:11px;color:#888;margin-top:4px;">
                        <span>🏥 <?php echo htmlspecialchars(substr($last_hosp['hospital_name'], 0, 24)); ?></span>
                        <span><?php echo $travel_info['progress_pct']; ?>% of journey</span>
                        <span><?php echo htmlspecialchars(substr($next_hosp['hospital_name'], 0, 24)); ?> 🏥</span>
                    </div>
                </div>
                <?php elseif ($travel_info && isset($travel_info['no_origin'])): ?>
                <div style="margin-top:10px;padding:10px 14px;background:rgba(255,255,255,0.7);border-radius:8px;font-size:13px;color:#555;">
                    📅 First session of the day — arriving at
                    <strong><?php echo htmlspecialchars($next_hosp['hospital_name']); ?></strong>
                    in ~<?php echo $travel_info['mins_until_session']; ?> min
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php else: ?>
        <div class="status-box status-priv">
            <div class="status-icon">🕐</div>
            <div>
                <div class="status-title" style="color:#666;">Not On Duty Right Now</div>
                <div class="status-sub">
                    Dr. <?php echo htmlspecialchars($doctor['full_name']); ?> has no active session at this time today.<br>
                    <?php
                    // Show next upcoming session from weekly schedule
                    $days_order = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
                    $today_idx  = array_search(date('l'), $days_order);
                    $found_next = false;
                    // Check remaining days this week including today
                    for ($d = 0; $d < 7; $d++) {
                        $check_day = $days_order[($today_idx + $d) % 7];
                        foreach ($weekly as $ws) {
                            if ($ws['day_of_week'] === $check_day) {
                                if ($d === 0 && $ws['start_time'] <= $now_time) continue; // past today
                                echo 'Next session: <strong>' . $check_day . '</strong> at '
                                   . date('h:i A', strtotime($ws['start_time']))
                                   . ' — ' . date('h:i A', strtotime($ws['end_time']))
                                   . ' at <strong>' . htmlspecialchars($ws['hospital_name']) . '</strong>';
                                $found_next = true;
                                break 2;
                            }
                        }
                    }
                    if (!$found_next) echo 'No upcoming sessions found in the schedule.';
                    ?>
                </div>
            </div>
        </div>
        <!-- ── ONGOING NUMBER (Queue Status) ── -->        <div class="card" style="margin-top:24px;">            <div class="card-header" style="background:linear-gradient(135deg,#8b1e3f,#6d1732);color:white;border-radius:12px 12px 0 0;">                ⏰ Current Queue Number            </div>            <div class="card-body" style="text-align:center;padding:40px 20px;">                <div style="font-size:72px;font-weight:900;color:#8b1e3f;line-height:1;margin-bottom:16px;">                    <?php echo $doctor["ongoing_number"] ?? 0; ?>                </div>                <p style="font-size:18px;color:#636e72;font-weight:600;margin:0;">                    Now Serving Patient                </p>                <p style="font-size:14px;color:#999;margin-top:12px;">                    Last updated: <?php echo date("h:i A"); ?>                </p>            </div>        </div>
        <?php endif; ?>

        <!-- ── MAP ── -->
        <?php if ($status !== 'private'): ?>
        <div class="card">
            <div class="card-header">
                <?php if ($status === 'at_hospital'): ?>
                    📍 Current Location
                <?php else: ?>
                    🗺️ Route Between Hospitals
                <?php endif; ?>
            </div>

            <!-- Hospital route display -->
            <?php if ($status === 'at_hospital'): ?>
            <div class="hosp-row">
                <div class="hosp-dot dot-green"></div>
                <div class="hosp-info">
                    <div class="hosp-name">🏥 <?php echo htmlspecialchars($current_hosp['hospital_name']); ?></div>
                    <div class="hosp-time">
                        Session: <?php echo date('h:i A', strtotime($current_hosp['start_time'])); ?>
                        – <?php echo date('h:i A', strtotime($current_hosp['end_time'])); ?>
                        &nbsp;·&nbsp; <?php echo htmlspecialchars($current_hosp['address'] ?? ''); ?>
                    </div>
                </div>
                <span style="background:#2e7d32;color:white;padding:4px 12px;border-radius:10px;font-size:13px;font-weight:bold;">HERE NOW</span>
            </div>

            <?php else: // travelling ?>

            <?php if ($last_hosp): ?>
            <div class="hosp-row">
                <div class="hosp-dot dot-grey"></div>
                <div class="hosp-info">
                    <div class="hosp-name">🏥 <?php echo htmlspecialchars($last_hosp['hospital_name']); ?></div>
                    <div class="hosp-time">
                        Finished at <?php echo date('h:i A', strtotime($last_hosp['end_time'])); ?>
                    </div>
                </div>
                <span style="background:#e0e0e0;color:#666;padding:4px 12px;border-radius:10px;font-size:13px;">LEFT</span>
            </div>
            <div class="route-arrow">↓ &nbsp; travelling &nbsp; ↓</div>
            <?php endif; ?>

            <div class="hosp-row" style="border-color:#ffc107;">
                <div class="hosp-dot dot-blue"></div>
                <div class="hosp-info">
                    <div class="hosp-name">🏥 <?php echo htmlspecialchars($next_hosp['hospital_name']); ?></div>
                    <div class="hosp-time">
                        Session starts: <?php echo date('h:i A', strtotime($next_hosp['start_time'])); ?>
                        &nbsp;·&nbsp; <?php echo htmlspecialchars($next_hosp['address'] ?? ''); ?>
                        <?php if ($travel_info): ?>
                        &nbsp;·&nbsp; ~<?php echo $travel_info['road_km']; ?> km · ~<?php echo $travel_info['label']; ?> drive
                        <?php endif; ?>
                    </div>
                </div>
                <span style="background:#1565c0;color:white;padding:4px 12px;border-radius:10px;font-size:13px;font-weight:bold;">NEXT STOP</span>
            </div>
            <?php endif; ?>

            <!-- Map -->
            <div id="map" style="margin-top:16px;"></div>
        </div>
        <?php endif; ?>

        <!-- Privacy note -->
        <div class="privacy-note">
            🔒 <strong>Privacy Notice:</strong> This system only shows hospital locations based on the doctor's schedule.
            The doctor's personal real-time GPS location is never tracked or shared.
        </div>

        <div style="margin-top:20px; display:flex; gap:12px;">
            <a href="search_doctors.php" class="btn btn-glass">← Back</a>
            <a href="dashboard.php" class="btn btn-glass-primary">🏠 Dashboard</a>
        </div>
    </main>
</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
<?php if ($status === 'at_hospital' && $current_hosp['latitude']): ?>

// ── AT HOSPITAL ──
var map = L.map('map');

L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
    attribution: 'Tiles © Esri — Source: Esri, Maxar, Earthstar Geographics'
}).addTo(map);

<?php if ($next_hosp && $next_hosp['latitude']): ?>

// ── Two hospitals — show route line between them ──
var curLat = <?php echo $current_hosp['latitude']; ?>;
var curLng = <?php echo $current_hosp['longitude']; ?>;
var nxtLat = <?php echo $next_hosp['latitude']; ?>;
var nxtLng = <?php echo $next_hosp['longitude']; ?>;

// Fit map to show both markers with padding
var bounds = L.latLngBounds([[curLat, curLng], [nxtLat, nxtLng]]);
map.fitBounds(bounds, {padding: [60, 60]});

// ── Show markers only (no route lines) ──

// Distance label on midpoint
var midLat = (curLat + nxtLat) / 2;
var midLng = (curLng + nxtLng) / 2;
<?php if ($next_dist): ?>
var distLabel = L.divIcon({
    html: '<div style="background:white;border:2px solid #1565c0;border-radius:20px;padding:4px 10px;font-size:12px;font-weight:bold;color:#1565c0;white-space:nowrap;box-shadow:0 2px 6px rgba(0,0,0,0.2);">~<?php echo $next_dist['road_km']; ?> km · ~<?php echo $next_dist['label']; ?></div>',
    className: '', iconAnchor: [50, 12]
});
L.marker([midLat, midLng], {icon: distLabel, interactive: false}).addTo(map);
<?php endif; ?>

// Green marker — doctor HERE NOW (larger, pulsing ring)
var greenIcon = L.divIcon({
    html: '<div style="position:relative;">'
        + '<div style="position:absolute;top:-6px;left:-6px;width:50px;height:50px;border-radius:50%;background:rgba(46,125,50,0.2);animation:pulse 2s infinite;"></div>'
        + '<div style="position:relative;background:#2e7d32;width:38px;height:38px;border-radius:50%;border:3px solid white;display:flex;align-items:center;justify-content:center;font-size:18px;box-shadow:0 3px 10px rgba(0,0,0,0.4);">🏥</div>'
        + '<div style="position:absolute;top:40px;left:50%;transform:translateX(-50%);background:#2e7d32;color:white;padding:3px 8px;border-radius:8px;font-size:11px;font-weight:bold;white-space:nowrap;box-shadow:0 2px 4px rgba(0,0,0,0.2);">HERE NOW</div>'
        + '</div>',
    className: '', iconSize: [38, 38], iconAnchor: [19, 19]
});
L.marker([curLat, curLng], {icon: greenIcon})
    .addTo(map)
    .bindPopup('<strong>✅ Dr. <?php echo addslashes($doctor['full_name']); ?> is here</strong><br><?php echo addslashes($current_hosp['hospital_name']); ?><br>Session until <?php echo date('h:i A', strtotime($current_hosp['end_time'])); ?>');

// Blue marker — next stop
var blueIcon = L.divIcon({
    html: '<div style="position:relative;">'
        + '<div style="position:relative;background:#1565c0;width:34px;height:34px;border-radius:50%;border:3px solid white;display:flex;align-items:center;justify-content:center;font-size:16px;box-shadow:0 3px 8px rgba(0,0,0,0.3);">🏥</div>'
        + '<div style="position:absolute;top:36px;left:50%;transform:translateX(-50%);background:#1565c0;color:white;padding:3px 8px;border-radius:8px;font-size:11px;font-weight:bold;white-space:nowrap;box-shadow:0 2px 4px rgba(0,0,0,0.2);">NEXT STOP</div>'
        + '</div>',
    className: '', iconSize: [34, 34], iconAnchor: [17, 17]
});
L.marker([nxtLat, nxtLng], {icon: blueIcon})
    .addTo(map)
    .bindPopup('<strong>🔜 Next stop</strong><br><?php echo addslashes($next_hosp['hospital_name']); ?><br>Session at <?php echo date('h:i A', strtotime($next_hosp['start_time'])); ?><?php if ($next_dist): ?><br>~<?php echo $next_dist['road_km']; ?> km · ~<?php echo $next_dist['label']; ?> drive<?php endif; ?>');

<?php else: ?>

// ── Single hospital — just show current ──
map.setView([<?php echo $current_hosp['latitude']; ?>, <?php echo $current_hosp['longitude']; ?>], 15);

var greenIcon = L.divIcon({
    html: '<div style="position:relative;">'
        + '<div style="position:absolute;top:-6px;left:-6px;width:50px;height:50px;border-radius:50%;background:rgba(46,125,50,0.2);animation:pulse 2s infinite;"></div>'
        + '<div style="position:relative;background:#2e7d32;width:38px;height:38px;border-radius:50%;border:3px solid white;display:flex;align-items:center;justify-content:center;font-size:18px;box-shadow:0 3px 10px rgba(0,0,0,0.4);">🏥</div>'
        + '</div>',
    className: '', iconSize: [38, 38], iconAnchor: [19, 19]
});
L.marker([<?php echo $current_hosp['latitude']; ?>, <?php echo $current_hosp['longitude']; ?>], {icon: greenIcon})
    .addTo(map)
    .bindPopup('<strong>✅ Dr. <?php echo addslashes($doctor['full_name']); ?> is here</strong><br><?php echo addslashes($current_hosp['hospital_name']); ?><br>Session until <?php echo date('h:i A', strtotime($current_hosp['end_time'])); ?>')
    .openPopup();

<?php endif; ?>

<?php elseif ($status === 'travelling'): ?>

// ── TRAVELLING ──
<?php
$from_lat = $last_hosp ? floatval($last_hosp['latitude'])  : null;
$from_lng = $last_hosp ? floatval($last_hosp['longitude']) : null;
$to_lat   = floatval($next_hosp['latitude']);
$to_lng   = floatval($next_hosp['longitude']);
?>

var map = L.map('map');

L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
    attribution: 'Tiles © Esri — Source: Esri, Maxar, Earthstar Geographics'
}).addTo(map);

<?php if ($from_lat): ?>

var fromLat = <?php echo $from_lat; ?>;
var fromLng = <?php echo $from_lng; ?>;
var toLat   = <?php echo $to_lat; ?>;
var toLng   = <?php echo $to_lng; ?>;

// Fit both markers
var bounds = L.latLngBounds([[fromLat, fromLng], [toLat, toLng]]);
map.fitBounds(bounds, {padding: [70, 70]});

// ── Route line: glow + solid + animated dashes ──
L.polyline([[fromLat, fromLng], [toLat, toLng]], {
    color: '#e65100', weight: 10, opacity: 0.20, lineJoin: 'round'
}).addTo(map);

L.polyline([[fromLat, fromLng], [toLat, toLng]], {
    color: '#f57c00', weight: 5, opacity: 0.85, lineJoin: 'round'
}).addTo(map);

var dashLine = L.polyline([[fromLat, fromLng], [toLat, toLng]], {
    color: '#ffffff', weight: 3, opacity: 0.75,
    dashArray: '10, 14', dashOffset: '0', lineJoin: 'round'
}).addTo(map);

var offset = 0;
setInterval(function() {
    offset -= 2;
    dashLine.setStyle({ dashOffset: offset + '' });
}, 60);

// ── Estimated doctor position on the line ──
<?php if ($travel_info && !isset($travel_info['no_origin'])): ?>
var progress = <?php echo min(0.95, max(0.05, ($travel_info['progress_pct'] / 100))); ?>;
var carLat = fromLat + (toLat - fromLat) * progress;
var carLng = fromLng + (toLng - fromLng) * progress;

var carIcon = L.divIcon({
    html: '<div style="background:#f57c00;width:32px;height:32px;border-radius:50%;border:3px solid white;display:flex;align-items:center;justify-content:center;font-size:16px;box-shadow:0 3px 10px rgba(245,124,0,0.6);">🚗</div>',
    className: '', iconSize: [32, 32], iconAnchor: [16, 16]
});
L.marker([carLat, carLng], {icon: carIcon})
    .addTo(map)
    .bindPopup('<strong>🚗 Doctor en route</strong><br><?php echo $travel_info['progress_pct']; ?>% of journey<br>~<?php echo $travel_info['mins_remaining']; ?> min remaining')
    .openPopup();
<?php endif; ?>

// Distance label at midpoint
var midLat = (fromLat + toLat) / 2;
var midLng = (fromLng + toLng) / 2;
<?php if ($travel_info && !isset($travel_info['no_origin'])): ?>
var distLabel = L.divIcon({
    html: '<div style="background:white;border:2px solid #f57c00;border-radius:20px;padding:4px 10px;font-size:12px;font-weight:bold;color:#e65100;white-space:nowrap;box-shadow:0 2px 6px rgba(0,0,0,0.2);">~<?php echo $travel_info['road_km']; ?> km · ~<?php echo $travel_info['label']; ?></div>',
    className: '', iconAnchor: [50, 12]
});
L.marker([midLat, midLng], {icon: distLabel, interactive: false}).addTo(map);
<?php endif; ?>

// Grey marker — left from here
var greyIcon = L.divIcon({
    html: '<div style="position:relative;">'
        + '<div style="background:#757575;width:32px;height:32px;border-radius:50%;border:3px solid white;display:flex;align-items:center;justify-content:center;font-size:15px;box-shadow:0 2px 6px rgba(0,0,0,0.3);">🏥</div>'
        + '<div style="position:absolute;top:34px;left:50%;transform:translateX(-50%);background:#757575;color:white;padding:2px 7px;border-radius:8px;font-size:11px;font-weight:bold;white-space:nowrap;">LEFT</div>'
        + '</div>',
    className: '', iconSize: [32, 32], iconAnchor: [16, 16]
});
L.marker([fromLat, fromLng], {icon: greyIcon})
    .addTo(map)
    .bindPopup('<strong>Departed from here</strong><br><?php echo addslashes($last_hosp['hospital_name']); ?><br>Session ended <?php echo date('h:i A', strtotime($last_hosp['end_time'])); ?>');

<?php else: ?>
// No prior hospital — just show destination
map.setView([<?php echo $to_lat; ?>, <?php echo $to_lng; ?>], 14);
<?php endif; ?>

// Blue marker — destination
var blueIcon = L.divIcon({
    html: '<div style="position:relative;">'
        + '<div style="background:#1565c0;width:36px;height:36px;border-radius:50%;border:3px solid white;display:flex;align-items:center;justify-content:center;font-size:18px;box-shadow:0 3px 10px rgba(21,101,192,0.5);">🏥</div>'
        + '<div style="position:absolute;top:38px;left:50%;transform:translateX(-50%);background:#1565c0;color:white;padding:3px 8px;border-radius:8px;font-size:11px;font-weight:bold;white-space:nowrap;box-shadow:0 2px 4px rgba(0,0,0,0.2);">NEXT STOP</div>'
        + '</div>',
    className: '', iconSize: [36, 36], iconAnchor: [18, 18]
});
L.marker([<?php echo $to_lat; ?>, <?php echo $to_lng; ?>], {icon: blueIcon})
    .addTo(map)
    .bindPopup('<strong>🔜 Heading here</strong><br><?php echo addslashes($next_hosp['hospital_name']); ?><br>Session starts <?php echo date('h:i A', strtotime($next_hosp['start_time'])); ?>');

<?php endif; ?>

// Auto-refresh every 2 minutes
setTimeout(function() { location.reload(); }, 120000);
</script>

<style>
@keyframes pulse {
    0%   { transform: scale(1);   opacity: 0.6; }
    50%  { transform: scale(1.5); opacity: 0.1; }
    100% { transform: scale(1);   opacity: 0.6; }
}
</style>
</body>
</html>
