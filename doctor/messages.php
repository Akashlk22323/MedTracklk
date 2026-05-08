<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'doctor') {
    header("Location: ../login.php"); exit();
}
include '../includes/config.php';

$ds = $conn->prepare("SELECT * FROM doctors WHERE user_id = ?");
$ds->bind_param("i", $_SESSION['user_id']);
$ds->execute();
$doctor = $ds->get_result()->fetch_assoc();
$ds->close();
if (!$doctor) { header("Location: ../logout.php"); exit(); }

$uid = $_SESSION['user_id'];

// Send message (doctor replies — preserve chat_type from conversation)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $receiver_id = intval($_POST['receiver_id']);
    $message     = sanitize($conn, $_POST['message']);
    $chat_type   = ($_POST['chat_type'] === 'appointment') ? 'appointment' : 'general';

    $ins = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message, chat_type) VALUES (?,?,?,?)");
    $ins->bind_param("iiss", $uid, $receiver_id, $message, $chat_type);
    $ins->execute();
    $ins->close();
    header("Location: messages.php?tab={$chat_type}&chat={$receiver_id}");
    exit();
}

$active_tab   = (isset($_GET['tab']) && $_GET['tab'] === 'general') ? 'general' : 'appointment';
$chat_uid     = intval($_GET['chat'] ?? 0);
$inner_tab    = in_array($_GET['inner'] ?? '', ['chat','labs','appts']) ? $_GET['inner'] : 'chat';

// ── Appointment patients: patients who have appointments with this doctor ──
$appt_pat_stmt = $conn->prepare("
    SELECT DISTINCT p.id, p.full_name, p.phone, p.date_of_birth, u.id AS user_id,
        (SELECT COUNT(*) FROM messages
         WHERE sender_id=u.id AND receiver_id=? AND is_read=0 AND chat_type='appointment') AS unread,
        (SELECT message FROM messages
         WHERE ((sender_id=u.id AND receiver_id=?) OR (sender_id=? AND receiver_id=u.id))
           AND chat_type='appointment'
         ORDER BY created_at DESC LIMIT 1) AS last_msg,
        (SELECT created_at FROM messages
         WHERE ((sender_id=u.id AND receiver_id=?) OR (sender_id=? AND receiver_id=u.id))
           AND chat_type='appointment'
         ORDER BY created_at DESC LIMIT 1) AS last_time
    FROM patients p
    JOIN users u ON p.user_id = u.id
    JOIN appointments a ON a.patient_id = p.id
    WHERE a.doctor_id = ?
    ORDER BY p.full_name
");
$appt_pat_stmt->bind_param("iiiiii", $uid, $uid, $uid, $uid, $uid, $doctor['id']);
$appt_pat_stmt->execute();
$appt_patients = $appt_pat_stmt->get_result();
$appt_pat_stmt->close();

// ── General patients: any patient who sent a general message ──
$gen_pat_stmt = $conn->prepare("
    SELECT DISTINCT p.id, p.full_name, p.phone, p.date_of_birth, u.id AS user_id,
        (SELECT COUNT(*) FROM messages
         WHERE sender_id=u.id AND receiver_id=? AND is_read=0 AND chat_type='general') AS unread,
        (SELECT message FROM messages
         WHERE ((sender_id=u.id AND receiver_id=?) OR (sender_id=? AND receiver_id=u.id))
           AND chat_type='general'
         ORDER BY created_at DESC LIMIT 1) AS last_msg,
        (SELECT created_at FROM messages
         WHERE ((sender_id=u.id AND receiver_id=?) OR (sender_id=? AND receiver_id=u.id))
           AND chat_type='general'
         ORDER BY created_at DESC LIMIT 1) AS last_time
    FROM patients p
    JOIN users u ON p.user_id = u.id
    JOIN messages m ON (m.sender_id=u.id AND m.receiver_id=?)
                    OR (m.sender_id=? AND m.receiver_id=u.id)
    WHERE m.chat_type = 'general'
    ORDER BY p.full_name
");
$gen_pat_stmt->bind_param("iiiiiii", $uid, $uid, $uid, $uid, $uid, $uid, $uid);
$gen_pat_stmt->execute();
$gen_patients = $gen_pat_stmt->get_result();
$gen_pat_stmt->close();

// ── Load selected patient ──
$chat_patient = null;
if ($chat_uid > 0) {
    $cp = $conn->prepare("SELECT p.*, u.id AS user_id FROM patients p JOIN users u ON p.user_id=u.id WHERE u.id=?");
    $cp->bind_param("i", $chat_uid);
    $cp->execute();
    $chat_patient = $cp->get_result()->fetch_assoc();
    $cp->close();
    // Mark read
    $mr = $conn->prepare("UPDATE messages SET is_read=1 WHERE sender_id=? AND receiver_id=? AND chat_type=?");
    $mr->bind_param("iis", $chat_uid, $uid, $active_tab);
    $mr->execute();
    $mr->close();
}

// ── Load chat messages ──
$chat_msgs = [];
if ($chat_patient) {
    $cm = $conn->prepare("
        SELECT m.*, u.role AS sender_role
        FROM messages m JOIN users u ON u.id=m.sender_id
        WHERE ((m.sender_id=? AND m.receiver_id=?) OR (m.sender_id=? AND m.receiver_id=?))
          AND m.chat_type = ?
        ORDER BY m.created_at ASC
    ");
    $cm->bind_param("iiiis", $uid, $chat_uid, $chat_uid, $uid, $active_tab);
    $cm->execute();
    $r = $cm->get_result();
    while ($row = $r->fetch_assoc()) $chat_msgs[] = $row;
    $cm->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Patient Messages - Doctor Panel</title>
<link rel="stylesheet" href="../css/style.css">
<style>
/* ── Outer grid ── */
.msg-wrap    { display:grid; grid-template-columns:300px 1fr; height:calc(100vh - 160px); border:2px solid #e0e0e0; border-radius:14px; overflow:hidden; box-shadow:0 4px 16px rgba(0,0,0,.08); }

/* ── Left sidebar ── */
.msg-sidebar { display:flex; flex-direction:column; background:#fff; border-right:2px solid #e8f5e9; }
.tab-strip   { display:grid; grid-template-columns:1fr 1fr; flex-shrink:0; }
.t-btn       { padding:13px 6px; border:none; background:#f5f5f5; font-size:12px; font-weight:700; cursor:pointer; border-bottom:3px solid transparent; text-decoration:none; color:#666; display:flex; align-items:center; justify-content:center; transition:.2s; }
.t-btn.on    { background:#fff; border-bottom-color:#2e7d32; color:#2e7d32; }
.tab-notice  { padding:9px 14px; font-size:12px; line-height:1.5; flex-shrink:0; }
.tab-notice.appt{ background:#e3f2fd; color:#1565c0; border-bottom:1px solid #bbdefb; }
.tab-notice.gen { background:#e8f5e9; color:#2e7d32; border-bottom:1px solid #c8e6c9; }
.pat-list    { flex:1; overflow-y:auto; }
.pat-row     { display:flex; align-items:center; gap:10px; padding:12px 14px; border-bottom:1px solid #f0f0f0; text-decoration:none; color:inherit; transition:background .15s; }
.pat-row:hover{ background:#f8fffe; }
.pat-row.on  { background:#e8f5e9; border-left:4px solid #2e7d32; }
.pat-avatar  { width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0; }
.av-appt     { background:linear-gradient(135deg,#1565c0,#42a5f5); }
.av-gen      { background:linear-gradient(135deg,#2e7d32,#66bb6a); }
.pat-info    { flex:1; min-width:0; }
.pat-name    { font-weight:700; font-size:14px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.pat-last    { font-size:11px; color:#aaa; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-top:2px; }
.unread-dot  { background:#e53935; color:#fff; border-radius:50%; min-width:20px; height:20px; font-size:11px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.empty-list  { padding:28px 14px; text-align:center; color:#bbb; font-size:13px; line-height:1.8; }

/* ── Right chat area ── */
.chat-col    { display:flex; flex-direction:column; background:#f9f9f9; overflow:hidden; }

/* Header */
.chat-hdr    { padding:14px 18px; background:#fff; border-bottom:2px solid #e8f5e9; display:flex; align-items:center; gap:12px; flex-shrink:0; }
.chat-hdr .pname{ font-size:17px; font-weight:700; }
.chat-hdr .pmeta{ font-size:12px; color:#888; margin-top:2px; }
.type-chip   { padding:4px 12px; border-radius:10px; font-size:12px; font-weight:700; white-space:nowrap; }
.chip-appt   { background:#e3f2fd; color:#1565c0; }
.chip-gen    { background:#e8f5e9; color:#2e7d32; }

/* Inner tabs */
.inner-tabs  { display:flex; background:#fff; border-bottom:2px solid #e8f5e9; flex-shrink:0; }
.in-btn      { flex:1; padding:11px 4px; border:none; background:none; font-size:12px; font-weight:700; cursor:pointer; color:#888; border-bottom:3px solid transparent; transition:.2s; }
.in-btn.on   { color:#2e7d32; border-bottom-color:#2e7d32; }

/* Chat messages */
.chat-body   { flex:1; overflow-y:auto; padding:18px; display:flex; flex-direction:column; gap:11px; }
.bw          { display:flex; flex-direction:column; }
.bw.sent     { align-items:flex-end; }
.bw.recv     { align-items:flex-start; }
.bubble      { max-width:65%; padding:11px 15px; border-radius:16px; font-size:14px; line-height:1.55; word-break:break-word; }
.bubble.sent { background:#2e7d32; color:#fff; border-bottom-right-radius:4px; }
.bubble.recv { background:#fff; color:#333; border:1px solid #e0e0e0; border-bottom-left-radius:4px; }
.btime       { font-size:11px; color:#aaa; margin-top:4px; }
.day-div     { text-align:center; font-size:11px; color:#bbb; margin:4px 0; }

.chat-foot   { padding:12px 16px; background:#fff; border-top:2px solid #e8f5e9; display:flex; gap:10px; flex-shrink:0; }
.chat-foot textarea { flex:1; padding:10px 13px; border:2px solid #ddd; border-radius:9px; resize:none; font-size:14px; font-family:inherit; }
.chat-foot textarea:focus { outline:none; border-color:#2e7d32; }

/* Inner panels */
.inner-panel { flex:1; overflow-y:auto; padding:18px; }
.lab-card    { background:#fff; border:2px solid #e0e0e0; border-radius:10px; padding:15px; margin-bottom:12px; display:flex; align-items:center; gap:14px; transition:border-color .2s; }
.lab-card:hover { border-color:#2e7d32; }
.hist-row    { background:#fff; border:1px solid #e0e0e0; border-radius:9px; padding:12px 15px; margin-bottom:10px; }
.hist-row.dr { border-left:4px solid #2e7d32; }
.hist-row.pa { border-left:4px solid #1565c0; }

.empty-chat  { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; color:#ccc; gap:8px; }
.empty-chat .ico{ font-size:56px; }
</style>
</head>
<body class="doctor-theme">
<nav class="navbar">
    <div class="main-content">
        <a href="dashboard.php" class="navbar-brand">🏥 Doctor Panel</a>
        <ul class="navbar-menu">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="appointments.php">Appointments</a></li>
            <li><a href="messages.php" style="background:rgba(255,255,255,0.2);">Messages</a></li>
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
            <li><a href="manage_slots.php">🕐 Time Slots</a></li>
            <li><a href="lab_reports.php">🧪 Lab Reports</a></li>
            <li><a href="../video_room.php">📹 Video</a></li>
            <li><a href="messages.php" class="active">💬 Messages</a></li>
            <li><a href="update_location.php">📍 Location</a></li>
            <li><a href="profile.php">👤 Profile</a></li>
        </ul>
    </div></div>
    <main  style="padding:16px 20px;">
        <div class="page-header" style="margin-bottom:14px;">
            <h1>💬 Patient Messages</h1>
        </div>

        <div class="msg-wrap">

            <!-- ══ LEFT SIDEBAR ══ -->
            <div class="msg-sidebar">
                <!-- Tab strip -->
                <div class="tab-strip">
                    <a href="messages.php?tab=appointment<?php echo $chat_uid?'&chat='.$chat_uid:''; ?>"
                       class="t-btn <?php echo $active_tab==='appointment'?'on':''; ?>">📅 Appointments</a>
                    <a href="messages.php?tab=general<?php echo $chat_uid?'&chat='.$chat_uid:''; ?>"
                       class="t-btn <?php echo $active_tab==='general'?'on':''; ?>">💬 General Q&A</a>
                </div>

                <!-- Context notice -->
                <?php if ($active_tab === 'appointment'): ?>
                <div class="tab-notice appt">
                    🔒 Patients who have <strong>appointments</strong> with you
                </div>
                <?php else: ?>
                <div class="tab-notice gen">
                    🌐 Patients asking <strong>general questions</strong>
                </div>
                <?php endif; ?>

                <!-- Patient list -->
                <div class="pat-list">
                    <?php
                    $list = ($active_tab === 'appointment') ? $appt_patients : $gen_patients;
                    if ($list->num_rows === 0):
                    ?>
                        <div class="empty-list">
                            <?php echo $active_tab==='appointment'
                                ? 'No appointment patients yet.'
                                : 'No general Q&A messages yet.'; ?>
                        </div>
                    <?php else:
                        while ($p = $list->fetch_assoc()):
                            $is_on  = ($chat_uid == $p['user_id']);
                            $av_cls = $active_tab === 'appointment' ? 'av-appt' : 'av-gen';
                    ?>
                        <a href="messages.php?tab=<?php echo $active_tab; ?>&chat=<?php echo $p['user_id']; ?>"
                           class="pat-row <?php echo $is_on?'on':''; ?>">
                            <div class="pat-avatar <?php echo $av_cls; ?>">👤</div>
                            <div class="pat-info">
                                <div class="pat-name"><?php echo htmlspecialchars($p['full_name']); ?></div>
                                <?php if ($p['last_msg']): ?>
                                <div class="pat-last"><?php echo htmlspecialchars(mb_substr($p['last_msg'],0,34)); ?>…</div>
                                <?php endif; ?>
                            </div>
                            <?php if ($p['unread'] > 0): ?>
                                <div class="unread-dot"><?php echo $p['unread']; ?></div>
                            <?php endif; ?>
                        </a>
                    <?php endwhile; endif; ?>
                </div>
            </div>

            <!-- ══ RIGHT CHAT AREA ══ -->
            <div class="chat-col">
                <?php if ($chat_patient && $chat_uid > 0):
                    $age = $chat_patient['date_of_birth']
                        ? date_diff(date_create($chat_patient['date_of_birth']), date_create('today'))->y . ' yrs'
                        : '';
                ?>

                    <!-- Header -->
                    <div class="chat-hdr">
                        <div class="pat-avatar <?php echo $active_tab==='appointment'?'av-appt':'av-gen'; ?>" style="width:46px;height:46px;font-size:22px;">👤</div>
                        <div>
                            <div class="pname"><?php echo htmlspecialchars($chat_patient['full_name']); ?></div>
                            <div class="pmeta">
                                <?php echo htmlspecialchars($chat_patient['phone'] ?? ''); ?>
                                <?php echo $age ? ' · ' . $age : ''; ?>
                            </div>
                        </div>
                        <span class="type-chip <?php echo $active_tab==='appointment'?'chip-appt':'chip-gen'; ?>" style="margin-left:auto;">
                            <?php echo $active_tab==='appointment' ? '📅 Appointment' : '💬 General Q&A'; ?>
                        </span>
                    </div>

                    <!-- Inner tabs (Chat / History / Labs / Appointments) -->
                    <div class="inner-tabs">
                        <button class="in-btn <?php echo $inner_tab==='chat'?'on':''; ?>" onclick="showInner('chat')">💬 Chat</button>
                        <button class="in-btn <?php echo $inner_tab==='history'?'on':''; ?>" onclick="showInner('history')">📜 History</button>
                        <button class="in-btn <?php echo $inner_tab==='labs'?'on':''; ?>" onclick="showInner('labs')">🧪 Lab Reports</button>
                        <button class="in-btn <?php echo $inner_tab==='appts'?'on':''; ?>" onclick="showInner('appts')">📅 Appointments</button>
                    </div>

                    <!-- ── CHAT PANEL ── -->
                    <div id="p-chat" style="display:<?php echo $inner_tab==='chat'?'flex':'none'; ?>;flex-direction:column;flex:1;overflow:hidden;">
                        <div class="chat-body" id="chatBody">
                            <?php if (empty($chat_msgs)): ?>
                                <div class="empty-chat">
                                    <div class="ico">💬</div>
                                    <strong style="color:#bbb;">No messages yet</strong>
                                </div>
                            <?php else:
                                $last_day = '';
                                foreach ($chat_msgs as $msg):
                                    $day = date('l, F j, Y', strtotime($msg['created_at']));
                                    if ($day !== $last_day): $last_day = $day; ?>
                                        <div class="day-div">── <?php echo $day; ?> ──</div>
                                    <?php endif;
                                    $sent = ($msg['sender_id'] == $uid);
                            ?>
                                <div class="bw <?php echo $sent?'sent':'recv'; ?>">
                                    <div class="bubble <?php echo $sent?'sent':'recv'; ?>"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                                    <div class="btime"><?php echo date('h:i A', strtotime($msg['created_at'])); ?></div>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>
                        <form method="POST" class="chat-foot" onsubmit="return !!document.getElementById('msgIn').value.trim()">
                            <input type="hidden" name="receiver_id" value="<?php echo $chat_uid; ?>">
                            <input type="hidden" name="chat_type"   value="<?php echo $active_tab; ?>">
                            <textarea id="msgIn" name="message" rows="2"
                                placeholder="Reply to <?php echo htmlspecialchars($chat_patient['full_name']); ?>..."
                                required
                                onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();this.form.submit();}"></textarea>
                            <button type="submit" name="send_message" class="btn btn-glass-primary" style="padding:12px 20px;align-self:flex-end;">Send ➤</button>
                        </form>
                    </div>

                    <!-- ── HISTORY PANEL ── -->
                    <div id="p-history" class="inner-panel" style="display:<?php echo $inner_tab==='history'?'block':'none'; ?>;">
                        <h3 style="margin-top:0;color:#555;">📜 All messages with <?php echo htmlspecialchars($chat_patient['full_name']); ?></h3>
                        <?php
                        // Load ALL messages (both types) for full history
                        $hq = $conn->prepare("
                            SELECT m.*, u.role AS sender_role
                            FROM messages m JOIN users u ON u.id=m.sender_id
                            WHERE (m.sender_id=? AND m.receiver_id=?) OR (m.sender_id=? AND m.receiver_id=?)
                            ORDER BY m.created_at ASC
                        ");
                        $hq->bind_param("iiii", $uid, $chat_uid, $chat_uid, $uid);
                        $hq->execute();
                        $all_hist = $hq->get_result();
                        $hq->close();
                        if ($all_hist->num_rows === 0) {
                            echo '<p style="color:#aaa;text-align:center;padding:30px;">No messages yet.</p>';
                        } else {
                            $hday = '';
                            while ($hm = $all_hist->fetch_assoc()) {
                                $d = date('l, F j, Y', strtotime($hm['created_at']));
                                if ($d !== $hday) {
                                    echo '<div class="day-div" style="text-align:center;font-size:12px;color:#bbb;margin:10px 0;">── '.$d.' ──</div>';
                                    $hday = $d;
                                }
                                $by_me = ($hm['sender_id'] == $uid);
                                $type_label = $hm['chat_type'] === 'appointment'
                                    ? '<span style="background:#e3f2fd;color:#1565c0;padding:1px 6px;border-radius:6px;font-size:10px;">📅 appt</span>'
                                    : '<span style="background:#e8f5e9;color:#2e7d32;padding:1px 6px;border-radius:6px;font-size:10px;">💬 general</span>';
                                echo '<div class="hist-row '.($by_me?'dr':'pa').'">';
                                echo '<div style="font-size:11px;color:#aaa;margin-bottom:5px;">'.
                                     ($by_me?'👨‍⚕️ You':'👤 '.htmlspecialchars($chat_patient['full_name'])).
                                     ' · '.date('h:i A',strtotime($hm['created_at'])).
                                     ' '.$type_label.'</div>';
                                echo '<div style="font-size:14px;">'.nl2br(htmlspecialchars($hm['message'])).'</div>';
                                echo '</div>';
                            }
                        }
                        ?>
                    </div>

                    <!-- ── LAB REPORTS PANEL ── -->
                    <div id="p-labs" class="inner-panel" style="display:<?php echo $inner_tab==='labs'?'block':'none'; ?>;">
                        <h3 style="margin-top:0;color:#555;">🧪 Lab Reports from <?php echo htmlspecialchars($chat_patient['full_name']); ?></h3>
                        <?php
                        $lq = $conn->prepare("SELECT * FROM lab_reports WHERE patient_id=? AND doctor_id=? ORDER BY uploaded_at DESC");
                        $lq->bind_param("ii", $chat_patient['id'], $doctor['id']);
                        $lq->execute();
                        $labs = $lq->get_result();
                        $lq->close();
                        if ($labs->num_rows === 0) {
                            echo '<div style="text-align:center;color:#bbb;padding:40px;"><div style="font-size:48px;">🧪</div><p>No lab reports sent to you yet.</p></div>';
                        } else {
                            while ($lab = $labs->fetch_assoc()) {
                                $icon = strtolower(pathinfo($lab['report_file'],PATHINFO_EXTENSION))==='pdf'?'📄':'🖼️';
                                echo '<div class="lab-card">';
                                echo '<div style="font-size:40px;">'.$icon.'</div>';
                                echo '<div style="flex:1;">';
                                echo '<strong>'.htmlspecialchars($lab['report_name']).'</strong><br>';
                                echo '<small style="color:#888;">📅 '.date('M d, Y',strtotime($lab['test_date'])).' · 🕐 '.date('M d, Y h:i A',strtotime($lab['uploaded_at'])).'</small>';
                                if ($lab['notes']) echo '<br><small style="background:#fff9c4;padding:2px 8px;border-radius:5px;">📝 '.htmlspecialchars($lab['notes']).'</small>';
                                echo '</div>';
                                echo '<div style="display:flex;gap:6px;flex-direction:column;">';
                                echo '<a href="../uploads/lab_reports/'.htmlspecialchars($lab['report_file']).'" target="_blank" class="btn btn-sm btn-primary">👁 View</a>';
                                echo '<a href="../uploads/lab_reports/'.htmlspecialchars($lab['report_file']).'" download class="btn btn-sm btn-success">⬇ Save</a>';
                                echo '</div></div>';
                            }
                        }
                        ?>
                    </div>

                    <!-- ── APPOINTMENTS PANEL ── -->
                    <div id="p-appts" class="inner-panel" style="display:<?php echo $inner_tab==='appts'?'block':'none'; ?>;">
                        <h3 style="margin-top:0;color:#555;">📅 Appointments with <?php echo htmlspecialchars($chat_patient['full_name']); ?></h3>
                        <?php
                        $aq = $conn->prepare("SELECT * FROM appointments WHERE doctor_id=? AND patient_id=? ORDER BY appointment_date DESC");
                        $aq->bind_param("ii", $doctor['id'], $chat_patient['id']);
                        $aq->execute();
                        $apts = $aq->get_result();
                        $aq->close();
                        if ($apts->num_rows === 0) {
                            echo '<p style="color:#bbb;text-align:center;padding:30px;">No appointments yet.</p>';
                        } else {
                            echo '<table><thead><tr><th>Date</th><th>Time</th><th>Hospital</th><th>Status</th></tr></thead><tbody>';
                            while ($ap = $apts->fetch_assoc()) {
                                $badge = ['confirmed'=>'badge-success','completed'=>'badge-info','cancelled'=>'badge-danger','pending'=>'badge-warning'][$ap['status']] ?? 'badge-success';
                                echo '<tr>';
                                echo '<td>'.date('M d, Y',strtotime($ap['appointment_date'])).'</td>';
                                echo '<td>'.date('h:i A',strtotime($ap['appointment_time'])).'</td>';
                                echo '<td>'.htmlspecialchars($ap['hospital_name']).'</td>';
                                echo '<td><span class="badge '.$badge.'">'.ucfirst($ap['status']).'</span></td>';
                                echo '</tr>';
                            }
                            echo '</tbody></table>';
                        }
                        ?>
                    </div>

                <?php else: ?>
                    <div class="empty-chat">
                        <div class="ico"><?php echo $active_tab==='appointment'?'📅':'💬'; ?></div>
                        <strong style="color:#bbb;"><?php echo $active_tab==='appointment'?'Select an appointment patient':'Select a patient'; ?></strong>
                        <span style="font-size:13px;color:#ccc;text-align:center;max-width:240px;">
                            <?php echo $active_tab==='appointment'
                                ? 'Choose a patient from your appointment list to chat'
                                : 'Choose a patient who asked a general question'; ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
<script>
function showInner(tab) {
    ['chat','history','labs','appts'].forEach(t => {
        document.getElementById('p-'+t).style.display = 'none';
    });
    document.getElementById('p-'+tab).style.display = (tab === 'chat') ? 'flex' : 'block';
    document.querySelectorAll('.in-btn').forEach((b,i) => {
        b.classList.toggle('on', ['chat','history','labs','appts'][i] === tab);
    });
    const url = new URL(window.location);
    url.searchParams.set('inner', tab);
    window.history.replaceState({}, '', url);
}

// Scroll chat to bottom
const cb = document.getElementById('chatBody');
if (cb) cb.scrollTop = cb.scrollHeight;

// Poll for new messages
<?php if ($chat_uid > 0): ?>
setInterval(() => {
    fetch('../patient/get_new_messages.php?doctor_id=<?php echo $uid; ?>&type=<?php echo $active_tab; ?>')
        .catch(()=>{});
}, 8000);
<?php endif; ?>
</script>
</body>
</html>
