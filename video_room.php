<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['doctor','patient'])) {
    header("Location: login.php"); exit();
}
include 'includes/config.php';

$role = $_SESSION['role'];
$err  = '';

// Load current user
$s = $conn->prepare($role === 'doctor'
    ? "SELECT * FROM doctors  WHERE user_id = ?"
    : "SELECT * FROM patients WHERE user_id = ?");
$s->bind_param("i", $_SESSION['user_id']); $s->execute();
$me = $s->get_result()->fetch_assoc(); $s->close();
if (!$me) { session_destroy(); header("Location: login.php"); exit(); }

// Doctor: create room
if ($role === 'doctor' && isset($_POST['create_room'])) {
    $patient_id = intval($_POST['patient_id']);
    $appt_id    = intval($_POST['appointment_id'] ?? 0);

    // Unique room code
    $code = '';
    for ($i = 0; $i < 10; $i++) {
        $c   = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
        $chk = $conn->prepare("SELECT id FROM video_rooms WHERE room_code = ?");
        $chk->bind_param("s", $c); $chk->execute();
        if ($chk->get_result()->num_rows === 0) { $code = $c; $chk->close(); break; }
        $chk->close();
    }
    if (!$code) die("Could not generate room code.");

    // Insert room
    if ($appt_id > 0) {
        $ins = $conn->prepare("INSERT INTO video_rooms (room_code, doctor_id, patient_id, appointment_id) VALUES (?,?,?,?)");
        $ins->bind_param("siii", $code, $me['id'], $patient_id, $appt_id);
    } else {
        $ins = $conn->prepare("INSERT INTO video_rooms (room_code, doctor_id, patient_id) VALUES (?,?,?)");
        $ins->bind_param("sii",  $code, $me['id'], $patient_id);
    }
    $ins->execute(); $ins->close();

    // Message patient with code
    $pu = $conn->prepare("SELECT u.id FROM users u JOIN patients p ON p.user_id = u.id WHERE p.id = ?");
    $pu->bind_param("i", $patient_id); $pu->execute();
    $patient_user = $pu->get_result()->fetch_assoc(); $pu->close();
    if ($patient_user) {
        $sid = intval($_SESSION['user_id']);
        $rid = intval($patient_user['id']);
        $msg = "Video Consultation Invitation - Room Code: $code - Enter this code in your Video Consultation page to join.";
        $ms  = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?,?,?)");
        $ms->bind_param("iis", $sid, $rid, $msg); $ms->execute(); $ms->close();
    }

    header("Location: video_call.php?room=" . urlencode($code) . "&host=1"); exit();
}

// Patient: join room
if ($role === 'patient' && isset($_POST['join_room'])) {
    $code = strtoupper(preg_replace('/[^A-Z0-9]/', '', trim($_POST['room_code'] ?? '')));
    if (strlen($code) === 8) {
        // Must have a confirmed appointment with the doctor who owns this room
        $chk = $conn->prepare("
            SELECT vr.id FROM video_rooms vr
            JOIN appointments a ON a.doctor_id = vr.doctor_id AND a.patient_id = vr.patient_id
            WHERE vr.room_code = ?
              AND vr.patient_id = ?
              AND vr.status != 'ended'
              AND a.status = 'confirmed'
        ");
        $chk->bind_param("si", $code, $me['id']); $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $chk->close();
            header("Location: video_call.php?room=" . urlencode($code) . "&host=0"); exit();
        }
        $err = "Access denied. You must have a confirmed appointment to join this consultation.";
        $chk->close();
    } else {
        $err = "Please enter a valid 8-character room code.";
    }
}

// Doctor: upcoming patients + active rooms
$upcoming = $active_rooms = null;
if ($role === 'doctor') {
    $s = $conn->prepare("SELECT DISTINCT p.id, p.full_name, p.phone, a.id AS appointment_id, a.appointment_date, a.appointment_time, a.hospital_name FROM patients p JOIN appointments a ON a.patient_id = p.id WHERE a.doctor_id = ? AND a.status IN ('confirmed','pending') AND a.appointment_date >= CURDATE() ORDER BY a.appointment_date, a.appointment_time");
    $s->bind_param("i", $me['id']); $s->execute(); $upcoming = $s->get_result(); $s->close();

    $s = $conn->prepare("SELECT vr.*, p.full_name AS patient_name FROM video_rooms vr JOIN patients p ON vr.patient_id = p.id WHERE vr.doctor_id = ? AND vr.status != 'ended' ORDER BY vr.created_at DESC");
    $s->bind_param("i", $me['id']); $s->execute(); $active_rooms = $s->get_result(); $s->close();
}

// Patient: active rooms — only if confirmed appointment exists
if ($role === 'patient') {
    $s = $conn->prepare("
        SELECT vr.*, d.full_name AS doctor_name, d.specialization
        FROM video_rooms vr
        JOIN doctors d ON vr.doctor_id = d.id
        JOIN appointments a ON a.doctor_id = vr.doctor_id AND a.patient_id = vr.patient_id
        WHERE vr.patient_id = ?
          AND vr.status != 'ended'
          AND a.status = 'confirmed'
        ORDER BY vr.created_at DESC
    ");
    $s->bind_param("i", $me['id']); $s->execute(); $active_rooms = $s->get_result(); $s->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Video Consultation - HealthCare Plus</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .vc-wrap  { max-width:1100px; margin:32px auto; padding:0 20px; }
        .vc-hero  { background:linear-gradient(135deg,#0d1b2a,#1a3a4a,#0d2b1a); border-radius:16px; padding:36px; text-align:center; color:white; margin-bottom:24px; }
        .vc-hero h1 { font-size:28px; margin:10px 0 6px; }
        .vc-hero p  { color:#aac; font-size:14px; }
        .vc-grid  { display:grid; grid-template-columns:1fr 1fr; gap:22px; }
        .vc-card  { background:white; border-radius:14px; padding:26px; box-shadow:0 4px 20px rgba(0,0,0,0.07); }
        .vc-card h2 { font-size:18px; margin-bottom:16px; display:flex; align-items:center; gap:8px; }
        .p-row    { display:flex; align-items:center; justify-content:space-between; padding:12px 14px; background:#f8fffe; border:2px solid #e0f0e8; border-radius:10px; margin-bottom:10px; gap:10px; flex-wrap:wrap; }
        .p-row:hover { border-color:#4caf50; }
        .p-name   { font-weight:700; font-size:14px; }
        .p-meta   { font-size:12px; color:#888; margin-top:2px; }
        .r-row    { display:flex; align-items:center; justify-content:space-between; padding:12px 14px; background:#f0f9ff; border:2px solid #b3d9f0; border-radius:10px; margin-bottom:10px; gap:10px; flex-wrap:wrap; }
        .r-code   { font-family:monospace; font-size:20px; font-weight:700; letter-spacing:4px; color:#1565c0; background:#e3f2fd; padding:5px 12px; border-radius:7px; }
        .badge-w  { background:#fff3cd; color:#856404; padding:3px 9px; border-radius:9px; font-size:11px; font-weight:700; }
        .badge-a  { background:#d4edda; color:#155724; padding:3px 9px; border-radius:9px; font-size:11px; font-weight:700; }
        .code-inp { display:flex; gap:10px; margin-top:14px; }
        .code-inp input { flex:1; padding:13px; border:2px solid #ddd; border-radius:10px; font-size:17px; font-family:monospace; text-transform:uppercase; letter-spacing:4px; text-align:center; }
        .code-inp input:focus { outline:none; border-color:#4caf50; }
        .btn-vc   { background:linear-gradient(135deg,#1565c0,#42a5f5); color:white; border:none; padding:10px 16px; border-radius:8px; cursor:pointer; font-weight:700; font-size:13px; white-space:nowrap; display:inline-flex; align-items:center; gap:5px; text-decoration:none; }
        .btn-vc:hover { opacity:0.9; transform:translateY(-1px); }
        .btn-green { background:linear-gradient(135deg,#2e7d32,#4caf50); }
        .btn-ora   { background:linear-gradient(135deg,#e65100,#ff9800); }
        .flist    { list-style:none; padding:0; }
        .flist li { padding:9px 0; border-bottom:1px solid #f3f3f3; font-size:13px; color:#555; display:flex; align-items:center; gap:10px; }
        .flist li:last-child { border:none; }
        .empty    { text-align:center; color:#bbb; padding:28px; font-size:13px; }
        @media(max-width:768px){ .vc-grid{ grid-template-columns:1fr; } }
    </style>
</head>
<body class="<?php echo $role; ?>-theme">

<?php if ($role === 'doctor'): ?>
<nav class="navbar"><div class="container">
    <a href="doctor/dashboard.php" class="navbar-brand">🏥 Doctor Panel</a>
    <ul class="navbar-menu">
        <li><a href="doctor/dashboard.php">Dashboard</a></li>
        <li><a href="doctor/appointments.php">Appointments</a></li>
        <li><a href="doctor/messages.php">Messages</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</div></nav>
<?php else: ?>
<nav class="navbar"><div class="container">
    <a href="patient/dashboard.php" class="navbar-brand">🏥 HealthCare Plus</a>
    <ul class="navbar-menu">
        <li><a href="patient/dashboard.php">Dashboard</a></li>
        <li><a href="patient/my_appointments.php">Appointments</a></li>
        <li><a href="patient/messages.php">Messages</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</div></nav>
<?php endif; ?>

<div class="vc-wrap">
    <div class="vc-hero">
        <div style="font-size:52px;">📹</div>
        <h1>Video Consultation</h1>
        <p>Secure real-time video calls between doctors and patients</p>
    </div>

    <?php if ($err): ?>
        <div class="alert alert-error" style="margin-bottom:18px;"><?php echo htmlspecialchars($err); ?></div>
    <?php endif; ?>

    <?php if ($role === 'doctor'): ?>
    <div class="vc-grid">
        <!-- Start call -->
        <div class="vc-card">
            <h2>📞 Start Video Call</h2>
            <?php if ($upcoming && $upcoming->num_rows > 0): ?>
                <?php while ($p = $upcoming->fetch_assoc()): ?>
                <form method="POST">
                    <input type="hidden" name="create_room" value="1">
                    <input type="hidden" name="patient_id"  value="<?php echo $p['id']; ?>">
                    <input type="hidden" name="appointment_id" value="<?php echo $p['appointment_id']; ?>">
                    <div class="p-row">
                        <div>
                            <div class="p-name">👤 <?php echo htmlspecialchars($p['full_name']); ?></div>
                            <div class="p-meta">📅 <?php echo date('M d', strtotime($p['appointment_date'])); ?> &nbsp;⏰ <?php echo date('h:i A', strtotime($p['appointment_time'])); ?></div>
                        </div>
                        <button type="submit" class="btn-vc btn-green">📹 Start</button>
                    </div>
                </form>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty">No upcoming appointments found.</div>
            <?php endif; ?>
        </div>

        <div style="display:flex;flex-direction:column;gap:22px;">
            <!-- Active rooms -->
            <div class="vc-card">
                <h2>🔴 Active Rooms</h2>
                <?php if ($active_rooms && $active_rooms->num_rows > 0): ?>
                    <?php while ($r = $active_rooms->fetch_assoc()): ?>
                    <div class="r-row">
                        <div>
                            <div style="font-weight:700;font-size:14px;">👤 <?php echo htmlspecialchars($r['patient_name']); ?></div>
                            <div class="r-code"><?php echo htmlspecialchars($r['room_code']); ?></div>
                            <span class="badge-<?php echo $r['status'] === 'active' ? 'a' : 'w'; ?>" style="margin-top:5px;display:inline-block;"><?php echo ucfirst($r['status']); ?></span>
                        </div>
                        <a href="video_call.php?room=<?php echo $r['room_code']; ?>&host=1" class="btn-vc btn-ora">🔁 Rejoin</a>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty">No active rooms.</div>
                <?php endif; ?>
            </div>
            <!-- Features -->
            <div class="vc-card">
                <h2>✨ Features</h2>
                <ul class="flist">
                    <li>📹 HD video & audio</li>
                    <li>🎙️ Mute / unmute mic</li>
                    <li>📷 Toggle camera</li>
                    <li>🔄 Flip front/back camera</li>
                    <li>💬 In-call text chat</li>
                    <li>🔒 Peer-to-peer encrypted</li>
                </ul>
            </div>
        </div>
    </div>

    <?php else: ?>
    <div class="vc-grid">
        <!-- Join with code -->
        <div class="vc-card">
            <h2>🔑 Join Consultation</h2>
            <p style="color:#666;font-size:14px;">Enter the 8-character room code your doctor sent you.</p>
            <form method="POST">
                <input type="hidden" name="join_room" value="1">
                <div class="code-inp">
                    <input type="text" name="room_code" maxlength="8" placeholder="XXXXXXXX" required autocomplete="off">
                    <button type="submit" class="btn-vc btn-green" style="padding:13px 20px;">📹 Join</button>
                </div>
            </form>

            <?php if ($active_rooms && $active_rooms->num_rows > 0): ?>
            <div style="margin-top:22px;">
                <p style="font-weight:700;margin-bottom:10px;font-size:14px;">📬 Open Invitations:</p>
                <?php while ($r = $active_rooms->fetch_assoc()): ?>
                <div class="r-row">
                    <div>
                        <div style="font-weight:700;font-size:14px;">Dr. <?php echo htmlspecialchars($r['doctor_name']); ?></div>
                        <div style="font-size:12px;color:#888;"><?php echo htmlspecialchars($r['specialization']); ?></div>
                        <div class="r-code" style="margin-top:6px;"><?php echo htmlspecialchars($r['room_code']); ?></div>
                    </div>
                    <a href="video_call.php?room=<?php echo $r['room_code']; ?>&host=0" class="btn-vc btn-green">📹 Join Now</a>
                </div>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
                <div class="empty" style="margin-top:18px;">No active invitations yet.<br>Your doctor will send a room code.</div>
            <?php endif; ?>
        </div>

        <div style="display:flex;flex-direction:column;gap:22px;">
            <div class="vc-card">
                <h2>📋 How It Works</h2>
                <ul class="flist">
                    <li>1️⃣ Doctor starts a call from their panel</li>
                    <li>2️⃣ You receive a room code via Messages</li>
                    <li>3️⃣ Enter the code above or click invitation</li>
                    <li>4️⃣ Allow camera & microphone access</li>
                    <li>5️⃣ Consult in real-time</li>
                </ul>
            </div>
            <div class="vc-card">
                <h2>✨ Features</h2>
                <ul class="flist">
                    <li>📹 HD video from your camera</li>
                    <li>🎙️ Mute / unmute anytime</li>
                    <li>📷 Toggle camera on/off</li>
                    <li>💬 In-call text chat</li>
                    <li>🔒 Secure peer-to-peer connection</li>
                </ul>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
