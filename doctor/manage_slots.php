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

// Handle ADD only — NO DELETE for doctors
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_slot'])) {
    $day        = sanitize($conn, $_POST['day_of_week']);
    $start      = sanitize($conn, $_POST['start_time']);
    $end        = sanitize($conn, $_POST['end_time']);
    $hospital   = sanitize($conn, $_POST['hospital_name']);
    $max_pat    = intval($_POST['max_patients']);
    $slot_dur   = intval($_POST['slot_duration']);

    // Validate end > start
    if ($end <= $start) {
        $_SESSION['error'] = "End time must be after start time!";
    } else {
        // Check for duplicate
        $chk = $conn->prepare("SELECT id FROM schedules WHERE doctor_id=? AND day_of_week=? AND start_time=? AND hospital_name=?");
        $chk->bind_param("isss", $doctor['id'], $day, $start, $hospital);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $_SESSION['error'] = "A slot for this day/time/hospital already exists!";
        } else {
            $ins = $conn->prepare("INSERT INTO schedules (doctor_id, day_of_week, start_time, end_time, hospital_name, max_patients, slot_duration) VALUES (?,?,?,?,?,?,?)");
            $ins->bind_param("issssii", $doctor['id'], $day, $start, $end, $hospital, $max_pat, $slot_dur);
            if ($ins->execute()) {
                logActivity($conn, $_SESSION['user_id'], 'Add Slot', "Doctor added slot: $day $start at $hospital");
                $_SESSION['success'] = "Time slot added! Contact admin to remove slots.";
            }
        }
    }
    header("Location: manage_slots.php");
    exit();
}

// Get assigned hospitals
$hosp_stmt = $conn->prepare("SELECT hospital_name, is_primary FROM doctor_hospitals WHERE doctor_id = ? ORDER BY is_primary DESC");
$hosp_stmt->bind_param("i", $doctor['id']);
$hosp_stmt->execute();
$hospitals = $hosp_stmt->get_result();

// Get current schedules grouped by day
$sched_stmt = $conn->prepare("SELECT * FROM schedules WHERE doctor_id = ? ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), start_time");
$sched_stmt->bind_param("i", $doctor['id']);
$sched_stmt->execute();
$schedules = $sched_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Time Slots - Doctor Panel</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .slot-card { background: #f8f9fa; border: 2px solid #e0e0e0; border-radius: 10px; padding: 18px; margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between; gap: 15px; transition: border-color 0.2s; }
        .slot-card:hover { border-color: #43a047; }
        .day-group-header { background: linear-gradient(135deg, #2e7d32, #43a047); color: white; padding: 12px 18px; border-radius: 8px; margin: 20px 0 12px; font-size: 17px; font-weight: bold; }
        .no-delete-notice { background: #fff3cd; border-left: 4px solid #ffc107; padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; }
        .slot-info { flex: 1; }
        .slot-meta { display: flex; gap: 15px; flex-wrap: wrap; margin-top: 6px; font-size: 13px; color: #666; }
        .slot-meta span { background: #e8f5e9; padding: 3px 10px; border-radius: 10px; color: #2e7d32; font-weight: 600; }
        .calc-info { background: #e3f2fd; padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-top: 8px; }
    </style>
</head>
<body class="doctor-theme">
<nav class="navbar">
    <div class="main-content">
        <a href="dashboard.php" class="navbar-brand">🏥 Doctor Panel</a>
        <ul class="navbar-menu">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="appointments.php">Appointments</a></li>
            <li><a href="manage_slots.php" style="background:rgba(255,255,255,0.2);">Time Slots</a></li>
            <li><a href="update_location.php">Location</a></li>
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
            <li><a href="appointments.php">📅 Appointments</a></li>
            <li><a href="manage_slots.php" class="active">🕐 My Time Slots</a></li>
            <li><a href="update_location.php">📍 Update Location</a></li>
            <li><a href="messages.php">💬 Messages</a></li>
            <li><a href="profile.php">👤 Profile</a></li>
        </ul>
    </div></div>
    <main >
        <div class="page-header">
            <h1>🕐 My Time Slots</h1>
            <p>Add your availability slots for patients to book</p>
        </div>

        <?php
        if (isset($_SESSION['success'])) { echo '<div class="alert alert-success">'.$_SESSION['success'].'</div>'; unset($_SESSION['success']); }
        if (isset($_SESSION['error']))   { echo '<div class="alert alert-error">'.$_SESSION['error'].'</div>'; unset($_SESSION['error']); }
        ?>

        <!-- Important Notice -->
        <div class="no-delete-notice">
            ℹ️ <strong>Note:</strong> You can <strong>add</strong> time slots yourself. To <strong>remove</strong> a slot, please contact the admin — this prevents cancellation of already-booked appointments.
        </div>

        <!-- Add Slot Form -->
        <div class="card">
            <div class="card-header">➕ Add New Time Slot</div>
            <form method="POST">
                <input type="hidden" name="add_slot" value="1">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label>Day of Week *</label>
                        <select name="day_of_week" required>
                            <option value="">Select Day</option>
                            <?php foreach (['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $d): ?>
                                <option value="<?php echo $d; ?>"><?php echo $d; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Hospital *</label>
                        <select name="hospital_name" required>
                            <option value="">Select Hospital</option>
                            <?php
                            $hospitals->data_seek(0);
                            while ($h = $hospitals->fetch_assoc()):
                            ?>
                                <option value="<?php echo htmlspecialchars($h['hospital_name']); ?>">
                                    <?php echo htmlspecialchars($h['hospital_name']); ?><?php echo $h['is_primary'] ? ' ★' : ''; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Start Time *</label>
                        <input type="time" name="start_time" required>
                    </div>
                    <div class="form-group">
                        <label>End Time *</label>
                        <input type="time" name="end_time" required>
                    </div>
                    <div class="form-group">
                        <label>Slot Duration (minutes) *</label>
                        <select name="slot_duration" required>
                            <option value="30">30 minutes</option>
                            <option value="45" selected>45 minutes</option>
                            <option value="60">60 minutes</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Max Patients *</label>
                        <input type="number" name="max_patients" value="20" min="1" max="60" required>
                    </div>
                </div>
                <!-- Live slot calculator -->
                <div class="calc-info" id="slot-calc">
                    ℹ️ Select times above to see how many slots will be generated.
                </div>
                <button type="submit" class="btn btn-glass-primary" style="width:100%;padding:14px;font-size:16px;margin-top:15px;">
                    ➕ Add Time Slot
                </button>
            </form>
        </div>

        <!-- Current Slots -->
        <div class="card">
            <div class="card-header">
                📅 My Current Slots
                <span style="float:right;background:#2e7d32;color:white;padding:4px 12px;border-radius:20px;font-size:13px;"><?php echo $schedules->num_rows; ?> Slots</span>
            </div>
            <?php
            if ($schedules->num_rows > 0) {
                $grouped = [];
                while ($s = $schedules->fetch_assoc()) $grouped[$s['day_of_week']][] = $s;

                foreach ($grouped as $day => $slots) {
                    $total_patients = array_sum(array_column($slots, 'max_patients'));
                    echo '<div class="day-group-header">📅 '.$day.' &nbsp;<small style="font-weight:normal;opacity:0.85;">('.count($slots).' sessions · '.$total_patients.' max patients)</small></div>';
                    foreach ($slots as $slot) {
                        $start_fmt = date('h:i A', strtotime($slot['start_time']));
                        $end_fmt   = date('h:i A', strtotime($slot['end_time']));
                        // Calculate number of time slots
                        $mins = (strtotime($slot['end_time']) - strtotime($slot['start_time'])) / 60;
                        $num_slots = floor($mins / $slot['slot_duration']);
                        echo '<div class="slot-card">';
                        echo '<div class="slot-info">';
                        echo '<strong>🕐 '.$start_fmt.' – '.$end_fmt.'</strong>';
                        echo '<div class="slot-meta">';
                        echo '<span>🏥 '.htmlspecialchars($slot['hospital_name']).'</span>';
                        echo '<span>👥 Max '.$slot['max_patients'].' patients</span>';
                        echo '<span>⏱ '.$slot['slot_duration'].' min/slot</span>';
                        echo '<span>📋 '.$num_slots.' time slots</span>';
                        echo '</div>';
                        echo '</div>';
                        echo '<div style="color:#aaa;font-size:13px;text-align:right;">Contact admin<br>to remove</div>';
                        echo '</div>';
                    }
                }
            } else {
                echo '<div style="text-align:center;padding:40px;color:#999;"><div style="font-size:48px;">🕐</div><h3>No time slots yet</h3><p>Add your first slot above.</p></div>';
            }
            ?>
        </div>
    </main>
</div>
<script>
    function calcSlots() {
        const start = document.querySelector('[name="start_time"]').value;
        const end   = document.querySelector('[name="end_time"]').value;
        const dur   = parseInt(document.querySelector('[name="slot_duration"]').value) || 45;
        const el    = document.getElementById('slot-calc');
        if (start && end && end > start) {
            const startMins = parseInt(start.split(':')[0])*60 + parseInt(start.split(':')[1]);
            const endMins   = parseInt(end.split(':')[0])*60   + parseInt(end.split(':')[1]);
            const count     = Math.floor((endMins - startMins) / dur);
            el.innerHTML = '✅ This will generate <strong>'+count+' time slots</strong> of '+dur+' minutes each.';
            el.style.background = '#e8f5e9';
        } else if (end && start && end <= start) {
            el.innerHTML = '❌ End time must be after start time!';
            el.style.background = '#ffebee';
        } else {
            el.innerHTML = 'ℹ️ Select times above to see how many slots will be generated.';
            el.style.background = '#e3f2fd';
        }
    }
    document.querySelector('[name="start_time"]').addEventListener('change', calcSlots);
    document.querySelector('[name="end_time"]').addEventListener('change', calcSlots);
    document.querySelector('[name="slot_duration"]').addEventListener('change', calcSlots);
</script>
</body>
</html>
