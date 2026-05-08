<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "patient") {
    header("Location: ../login.php"); exit();
}
include "../includes/config.php";

$stmt = $conn->prepare("SELECT id FROM patients WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION["user_id"]); $stmt->execute();
$pat_row = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$pat_row) { header("Location: ../logout.php"); exit(); }
$patient_id = $pat_row["id"];

if (isset($_GET["cancel"]) && is_numeric($_GET["cancel"])) {
    $appt_id = intval($_GET["cancel"]);
    $stmt = $conn->prepare("UPDATE appointments SET status='cancelled' WHERE id=? AND patient_id=?");
    $stmt->bind_param("ii", $appt_id, $patient_id); $stmt->execute(); $stmt->close();
    $_SESSION["success"] = "Appointment cancelled.";
    header("Location: my_appointments.php"); exit();
}

$f_status = isset($_GET["status"]) && in_array($_GET["status"], ["confirmed","completed","cancelled"]) ? $_GET["status"] : "";
$f_type   = isset($_GET["type"])   && in_array($_GET["type"],   ["at_hospital","video_call"])         ? $_GET["type"]   : "";
$f_date   = isset($_GET["date"])   && preg_match("/^\d{4}-\d{2}-\d{2}$/", $_GET["date"])           ? $_GET["date"]   : "";

$where  = "WHERE a.patient_id = ?";
$params = [$patient_id];
$types  = "i";

if ($f_status) { $where .= " AND a.status = ?";           $params[] = $f_status; $types .= "s"; }
if ($f_type)   { $where .= " AND a.appointment_type = ?"; $params[] = $f_type;   $types .= "s"; }
if ($f_date)   { $where .= " AND a.appointment_date = ?"; $params[] = $f_date;   $types .= "s"; }

$sql  = "SELECT a.*, d.full_name as doctor_name, d.specialization
         FROM appointments a
         JOIN doctors d ON a.doctor_id = d.id
         $where
         ORDER BY a.created_at DESC, a.id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$appointments = $stmt->get_result();
$total_rows   = $appointments->num_rows;
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Appointments - HealthCare Plus</title>
    <link rel="stylesheet" href="../css/style.css">
<body class="patient-theme">
<nav class="navbar">
    <div class="main-content">
        <a href="dashboard.php" class="navbar-brand">🏥 HealthCare Plus</a>
        <ul class="navbar-menu">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="search_doctors.php">Search Doctors</a></li>
            <li><a href="my_appointments.php" style="background:rgba(255,255,255,0.2);">My Appointments</a></li>
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
            <li><a href="my_appointments.php" class="active">📅 My Appointments</a></li>
            <li><a href="doctor_location.php">📍 Doctor Locations</a></li>
            <li><a href="messages.php">💬 Ask Doctor</a></li>
            <li><a href="lab_reports.php">📋 Lab Reports</a></li>
            <li><a href="prescriptions.php">💊 Prescriptions</a></li>
            <li><a href="profile.php">👤 My Profile</a></li>
        </ul>
    </div></div>

    <main >
        <div class="page-header">
            <h1>My Appointments</h1>
            <p>Newest appointments shown first</p>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <!-- ── Filter bar ── -->
        <form method="GET" action="my_appointments.php">
            <div class="filter-bar">
                <div class="fg">
                    <label>Status</label>
                    <select name="status">
                        <option value="">All Statuses</option>
                        <option value="confirmed"  <?php if ($f_status==='confirmed')  echo 'selected'; ?>>✅ Confirmed</option>
                        <option value="completed"  <?php if ($f_status==='completed')  echo 'selected'; ?>>🏁 Completed</option>
                        <option value="cancelled"  <?php if ($f_status==='cancelled')  echo 'selected'; ?>>❌ Cancelled</option>
                    </select>
                </div>
                <div class="fg">
                    <label>Type</label>
                    <select name="type">
                        <option value="">All Types</option>
                        <option value="at_hospital" <?php if ($f_type==='at_hospital') echo 'selected'; ?>>🏥 At Hospital</option>
                        <option value="video_call"  <?php if ($f_type==='video_call')  echo 'selected'; ?>>📹 Video Call</option>
                    </select>
                </div>
                <div class="fg">
                    <label>Date</label>
                    <input type="date" name="date" value="<?php echo htmlspecialchars($f_date); ?>">
                </div>
                <button type="submit" class="filter-btn filter-btn-apply">🔍 Filter</button>
                <?php if ($f_status || $f_type || $f_date): ?>
                    <a href="my_appointments.php" class="filter-btn filter-btn-reset">✕ Clear</a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Active filter chips + count -->
        <div class="results-bar">
            <div>
                <strong><?php echo $total_rows; ?></strong> appointment<?php echo $total_rows !== 1 ? 's' : ''; ?> found
                <?php if ($f_status): ?><span class="chip">Status: <?php echo ucfirst($f_status); ?></span><?php endif; ?>
                <?php if ($f_type):   ?><span class="chip">Type: <?php echo $f_type === 'video_call' ? 'Video Call' : 'Hospital'; ?></span><?php endif; ?>
                <?php if ($f_date):   ?><span class="chip">Date: <?php echo date('M d, Y', strtotime($f_date)); ?></span><?php endif; ?>
            </div>
            <small style="color:#aaa;">Sorted: newest first</small>
        </div>

        <div class="card">
            <?php if ($total_rows > 0): ?>
            <div class="table-container"><table>
                <thead><tr>
                    <th>Booking #</th>
                    <th>Doctor</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Type</th>
                    <th>Hospital</th>
                    <th>Fee</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr></thead>
                <tbody>
                <?php
                $today = date('Y-m-d');
                while ($appt = $appointments->fetch_assoc()):
                    $badge_class = 'badge-success';
                    if ($appt['status'] === 'cancelled') $badge_class = 'badge-danger';
                    elseif ($appt['status'] === 'completed') $badge_class = 'badge-info';

                    $type     = $appt['appointment_type'] ?? 'at_hospital';
                    $is_video = $type === 'video_call';

                    $type_badge = $is_video
                        ? '<span style="background:#f3e5f5;color:#6a1b9a;padding:3px 8px;border-radius:8px;font-size:12px;font-weight:bold;">📹 Video</span>'
                        : '<span style="background:#e3f2fd;color:#1565c0;padding:3px 8px;border-radius:8px;font-size:12px;font-weight:bold;">🏥 Hospital</span>';

                    // Highlight row if today
                    $row_style = ($appt['appointment_date'] === $today && $appt['status'] === 'confirmed')
                        ? 'style="background:#fff8e1;"' : '';
                ?>
                <tr <?php echo $row_style; ?>>
                    <td><strong>#<?php echo $appt['booking_number']; ?></strong><br>
                        <small style="color:#aaa;font-size:11px;"><?php echo date('M d', strtotime($appt['created_at'] ?? $appt['appointment_date'])); ?></small>
                        
                        <?php if (!$is_video && $appt['appointment_date'] === $today && $appt['status'] === 'confirmed'): ?>
                            <?php
                            // Get doctor's current ongoing number
                            $doc_stmt = $conn->prepare("SELECT ongoing_number FROM doctors WHERE id = ?");
                            $doc_stmt->bind_param("i", $appt['doctor_id']);
                            $doc_stmt->execute();
                            $doc_result = $doc_stmt->get_result()->fetch_assoc();
                            $doc_stmt->close();
                            
                            $ongoing = $doc_result['ongoing_number'] ?? 0;
                            $my_number = $appt['booking_number'];
                            
                            if ($ongoing > 0 && $my_number > $ongoing) {
                                // Patient is waiting
                                $people_ahead = $my_number - $ongoing;
                                $wait_mins = $people_ahead * 10; // 10 min per patient
                                $wait_hrs = floor($wait_mins / 60);
                                $wait_mins_left = $wait_mins % 60;
                                
                                $wait_text = '';
                                if ($wait_hrs > 0) $wait_text .= $wait_hrs . 'h ';
                                $wait_text .= $wait_mins_left . 'min';
                                
                                echo '<div style="margin-top:6px;padding:4px 8px;background:#fff3cd;border:1px solid #ffc107;border-radius:6px;font-size:11px;">';
                                echo '<div style="font-weight:700;color:#856404;">⏳ Queue Status</div>';
                                echo '<div style="color:#666;margin-top:2px;">Current: #' . $ongoing . '</div>';
                                echo '<div style="color:#666;">People ahead: ' . $people_ahead . '</div>';
                                echo '<div style="color:#f57c00;font-weight:700;margin-top:2px;">~' . $wait_text . ' wait</div>';
                                echo '</div>';
                            } elseif ($ongoing > 0 && $my_number == $ongoing) {
                                // Patient's turn NOW
                                echo '<div style="margin-top:6px;padding:4px 8px;background:#d1f4e0;border:1px solid #10b981;border-radius:6px;font-size:11px;font-weight:700;color:#065f46;text-align:center;">';
                                echo '✅ YOUR TURN NOW!';
                                echo '</div>';
                            } elseif ($ongoing > 0 && $my_number < $ongoing) {
                                // Patient missed their turn
                                echo '<div style="margin-top:6px;padding:4px 8px;background:#fee2e2;border:1px solid #ef4444;border-radius:6px;font-size:11px;font-weight:700;color:#991b1b;text-align:center;">';
                                echo '⚠️ Missed Turn';
                                echo '</div>';
                            }
                            ?>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($appt['doctor_name']); ?><br>
                        <small><?php echo htmlspecialchars($appt['specialization']); ?></small>
                    </td>
                    <td>
                        <?php echo date('M d, Y', strtotime($appt['appointment_date'])); ?>
                        <?php if ($appt['appointment_date'] === $today && $appt['status'] === 'confirmed'):?>
                            <br><span style="background:#f57c00;color:white;padding:1px 6px;border-radius:6px;font-size:11px;font-weight:bold;">TODAY</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($is_video): ?>
                            —
                        <?php else: ?>
                            <?php echo date('h:i A', strtotime($appt['appointment_time'])); ?>
                            
                            <?php if ($appt['appointment_date'] === $today && $appt['status'] === 'confirmed'): ?>
                                <?php
                                // Show queue position for today's hospital appointments
                                $doc_stmt2 = $conn->prepare("SELECT ongoing_number FROM doctors WHERE id = ?");
                                $doc_stmt2->bind_param("i", $appt['doctor_id']);
                                $doc_stmt2->execute();
                                $doc_result2 = $doc_stmt2->get_result()->fetch_assoc();
                                $doc_stmt2->close();
                                
                                $ongoing2 = $doc_result2['ongoing_number'] ?? 0;
                                $my_num2 = $appt['booking_number'];
                                
                                if ($ongoing2 > 0) {
                                    if ($my_num2 == $ongoing2) {
                                        echo '<br><span style="background:#10b981;color:white;padding:2px 8px;border-radius:6px;font-size:10px;font-weight:700;">NOW SERVING</span>';
                                    } elseif ($my_num2 > $ongoing2) {
                                        $ahead2 = $my_num2 - $ongoing2;
                                        echo '<br><span style="background:#fbbf24;color:#78350f;padding:2px 8px;border-radius:6px;font-size:10px;font-weight:700;">'. $ahead2 . ' ahead</span>';
                                    } elseif ($my_num2 < $ongoing2) {
                                        echo '<br><span style="background:#ef4444;color:white;padding:2px 8px;border-radius:6px;font-size:10px;font-weight:700;">MISSED</span>';
                                    }
                                }
                                ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $type_badge; ?></td>
                    <td><?php echo $is_video ? '<span style="color:#aaa;">Online</span>' : htmlspecialchars($appt['hospital_name']); ?></td>
                    <td>Rs. <?php echo number_format($appt['payment_amount'], 2); ?><br>
                        <?php if (($appt['payment_method'] ?? '') === 'card'): ?>
                            <small style="background:#e8f5e9;color:#2e7d32;padding:1px 6px;border-radius:6px;">💳 Paid</small>
                        <?php else: ?>
                            <small style="background:#e3f2fd;color:#1565c0;padding:1px 6px;border-radius:6px;">🏥 Pay there</small>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst($appt['status']); ?></span></td>
                    <td>
                        <?php if ($appt['status'] === 'confirmed'): ?>

                            <?php if ($is_video): ?>
                                <?php if ($appt['appointment_date'] === $today): ?>
                                    <a href="../video_room.php" class="btn btn-sm"
                                       style="background:linear-gradient(135deg,#7b1fa2,#ab47bc);color:white;text-decoration:none;padding:5px 10px;border-radius:5px;font-size:12px;display:inline-block;margin-bottom:4px;">
                                        📹 Join Now
                                    </a><br>
                                <?php else: ?>
                                    <span style="font-size:12px;color:#888;display:inline-block;margin-bottom:4px;">📹 Join on <?php echo date('M d', strtotime($appt['appointment_date'])); ?></span><br>
                                <?php endif; ?>
                            <?php else: ?>
                                <!-- ✅ Location always visible for any confirmed hospital appointment -->
                                <a href="doctor_location.php?doctor_id=<?php echo $appt['doctor_id']; ?>"
                                   class="btn btn-success btn-sm"
                                   style="display:inline-block;margin-bottom:4px;">📍 Location</a><br>
                            <?php endif; ?>

                            <?php if ($appt['appointment_date'] >= $today): ?>
                                <a href="?cancel=<?php echo $appt['id']; ?><?php echo $f_status||$f_type||$f_date ? '&status='.$f_status.'&type='.$f_type.'&date='.$f_date : ''; ?>"
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Cancel this appointment?')">Cancel</a>
                            <?php endif; ?>

                        <?php elseif ($appt['status'] === 'completed'): ?>
                            <?php
                            $stmt2 = $conn->prepare("SELECT id FROM feedbacks WHERE appointment_id = ?");
                            $stmt2->bind_param("i", $appt['id']); $stmt2->execute();
                            $has_feedback = $stmt2->get_result()->num_rows > 0; $stmt2->close();
                            ?>
                            <?php if (!$has_feedback): ?>
                                <button class="btn btn-primary btn-sm"
                                        onclick="giveFeedback(<?php echo $appt['id']; ?>, <?php echo $appt['doctor_id']; ?>)">
                                    ⭐ Feedback
                                </button>
                            <?php else: ?>
                                <span class="badge badge-success">✓ Reviewed</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table></div>
            <?php else: ?>
                <div style="text-align:center;padding:50px 20px;color:#aaa;">
                    <div style="font-size:48px;margin-bottom:12px;">📅</div>
                    <p style="font-size:16px;font-weight:bold;color:#888;">No appointments found</p>
                    <?php if ($f_status || $f_type || $f_date): ?>
                        <p style="font-size:14px;">Try clearing the filters</p>
                        <a href="my_appointments.php" class="btn btn-glass" style="margin-top:10px;">Clear Filters</a>
                    <?php else: ?>
                        <p style="font-size:14px;">Book your first appointment with a doctor</p>
                        <a href="search_doctors.php" class="btn btn-glass-primary" style="margin-top:10px;">Find a Doctor</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Feedback Modal -->
<div class="modal" id="feedbackModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>⭐ Give Feedback</h2>
            <button class="close-modal" onclick="closeFeedbackModal()">&times;</button>
        </div>
        <form method="POST" action="submit_feedback.php">
            <input type="hidden" name="appointment_id" id="feedback_appointment_id">
            <input type="hidden" name="doctor_id"      id="feedback_doctor_id">
            <div class="form-group">
                <label>Rating</label>
                <select name="rating" required>
                    <option value="5">⭐⭐⭐⭐⭐ Excellent</option>
                    <option value="4">⭐⭐⭐⭐ Very Good</option>
                    <option value="3">⭐⭐⭐ Good</option>
                    <option value="2">⭐⭐ Fair</option>
                    <option value="1">⭐ Poor</option>
                </select>
            </div>
            <div class="form-group">
                <label>Comment</label>
                <textarea name="comment" required placeholder="Share your experience..."></textarea>
            </div>
            <button type="submit" class="btn btn-glass-primary" style="width:100%;">Submit Feedback</button>
        </form>
    </div>
</div>

<script>
function giveFeedback(apptId, docId) {
    document.getElementById('feedback_appointment_id').value = apptId;
    document.getElementById('feedback_doctor_id').value = docId;
    document.getElementById('feedbackModal').classList.add('active');
}
function closeFeedbackModal() {
    document.getElementById('feedbackModal').classList.remove('active');
}
</script>
</body>
</html>
