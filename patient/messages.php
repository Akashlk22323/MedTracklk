<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'patient') {
    header("Location: ../login.php"); exit();
}
include '../includes/config.php';

// Load patient record
$ps = $conn->prepare("SELECT * FROM patients WHERE user_id = ?");
$ps->bind_param("i", $_SESSION['user_id']);
$ps->execute();
$patient = $ps->get_result()->fetch_assoc();
$ps->close();

if (!$patient) { header("Location: ../logout.php"); exit(); }

// Send message
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $receiver_id = intval($_POST['receiver_id']);
    $message     = sanitize($conn, $_POST['message']);
    $chat_type   = ($_POST['chat_type'] === 'appointment') ? 'appointment' : 'general';

    $ins = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message, chat_type) VALUES (?,?,?,?)");
    $ins->bind_param("iiss", $_SESSION['user_id'], $receiver_id, $message, $chat_type);
    $ins->execute();
    $ins->close();
    header("Location: messages.php?tab={$chat_type}&chat={$receiver_id}");
    exit();
}

$active_tab   = (isset($_GET['tab']) && $_GET['tab'] === 'general') ? 'general' : 'appointment';
$chat_uid     = intval($_GET['chat'] ?? 0);
$uid          = $_SESSION['user_id'];

// ── Appointment doctors: only doctors patient has appointments with ──
$appt_stmt = $conn->prepare("
    SELECT DISTINCT d.id, d.full_name, d.specialization, u.id AS user_id,
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
    FROM doctors d
    JOIN users u ON d.user_id = u.id
    JOIN appointments a ON a.doctor_id = d.id
    WHERE a.patient_id = ?
    ORDER BY d.full_name
");
$appt_stmt->bind_param("iiiiii", $uid, $uid, $uid, $uid, $uid, $patient['id']);
$appt_stmt->execute();
$appt_doctors = $appt_stmt->get_result();
$appt_stmt->close();

// ── General doctors: all doctors ──
$gen_stmt = $conn->prepare("
    SELECT d.id, d.full_name, d.specialization, u.id AS user_id,
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
    FROM doctors d
    JOIN users u ON d.user_id = u.id
    ORDER BY d.full_name
");
$gen_stmt->bind_param("iiiii", $uid, $uid, $uid, $uid, $uid);
$gen_stmt->execute();
$gen_doctors = $gen_stmt->get_result();
$gen_stmt->close();

// ── Load selected doctor ──
$chat_doctor = null;
if ($chat_uid > 0) {
    $cd = $conn->prepare("SELECT d.*, u.id AS user_id FROM doctors d JOIN users u ON d.user_id=u.id WHERE u.id=?");
    $cd->bind_param("i", $chat_uid);
    $cd->execute();
    $chat_doctor = $cd->get_result()->fetch_assoc();
    $cd->close();
    // Mark as read
    $mr = $conn->prepare("UPDATE messages SET is_read=1 WHERE sender_id=? AND receiver_id=? AND chat_type=?");
    $mr->bind_param("iis", $chat_uid, $uid, $active_tab);
    $mr->execute();
    $mr->close();
}

// ── Load chat messages ──
$chat_msgs = [];
if ($chat_doctor) {
    $cm = $conn->prepare("
        SELECT m.*, u.role AS sender_role
        FROM messages m JOIN users u ON u.id = m.sender_id
        WHERE ((m.sender_id=? AND m.receiver_id=?) OR (m.sender_id=? AND m.receiver_id=?))
          AND m.chat_type = ?
        ORDER BY m.created_at ASC
    ");
    $cm->bind_param("iiiis", $uid, $chat_uid, $chat_uid, $uid, $active_tab);
    $cm->execute();
    $res = $cm->get_result();
    while ($row = $res->fetch_assoc()) $chat_msgs[] = $row;
    $cm->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Messages - HealthCare Plus</title>
<link rel="stylesheet" href="../css/style.css">
<style>
/* ── Layout ── */
.msg-wrap   { display:grid; grid-template-columns:300px 1fr; height:calc(100vh - 160px); border:2px solid #e0e0e0; border-radius:14px; overflow:hidden; box-shadow:0 4px 16px rgba(0,0,0,.08); }

/* ── Left sidebar ── */
.msg-sidebar{ display:flex; flex-direction:column; background:#fff; border-right:2px solid #e8f5e9; }

.tab-strip  { display:grid; grid-template-columns:1fr 1fr; flex-shrink:0; }
.t-btn      { padding:14px 6px; border:none; background:#f5f5f5; font-size:13px; font-weight:700; cursor:pointer; border-bottom:3px solid transparent; transition:.2s; }
.t-btn.on   { background:#fff; border-bottom-color:#2e7d32; color:#2e7d32; }

.tab-notice { padding:10px 14px; font-size:12px; line-height:1.5; flex-shrink:0; }
.tab-notice.appt{ background:#e3f2fd; color:#1565c0; border-bottom:1px solid #bbdefb; }
.tab-notice.gen { background:#e8f5e9; color:#2e7d32; border-bottom:1px solid #c8e6c9; }

.doc-list   { flex:1; overflow-y:auto; }
.doc-row    { display:flex; align-items:center; gap:10px; padding:13px 14px; border-bottom:1px solid #f0f0f0; text-decoration:none; color:inherit; transition:background .15s; }
.doc-row:hover { background:#f8fffe; }
.doc-row.on { background:#e8f5e9; border-left:4px solid #2e7d32; }
.doc-avatar { width:42px; height:42px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0; }
.av-appt    { background:linear-gradient(135deg,#1565c0,#42a5f5); }
.av-gen     { background:linear-gradient(135deg,#2e7d32,#66bb6a); }
.doc-info   { flex:1; min-width:0; }
.doc-name   { font-weight:700; font-size:14px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.doc-spec   { font-size:12px; color:#888; }
.doc-last   { font-size:11px; color:#aaa; margin-top:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.unread-dot { background:#e53935; color:#fff; border-radius:50%; min-width:20px; height:20px; font-size:11px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.empty-list { padding:30px 16px; text-align:center; color:#bbb; font-size:13px; line-height:1.8; }

/* ── Right chat area ── */
.chat-col   { display:flex; flex-direction:column; background:#f9f9f9; }
.chat-hdr   { padding:15px 20px; background:#fff; border-bottom:2px solid #e8f5e9; display:flex; align-items:center; gap:14px; flex-shrink:0; }
.chat-hdr .dname { font-size:17px; font-weight:700; }
.chat-hdr .dspec  { font-size:13px; color:#888; }
.type-chip  { margin-left:auto; padding:5px 12px; border-radius:12px; font-size:12px; font-weight:700; flex-shrink:0; }
.chip-appt  { background:#e3f2fd; color:#1565c0; }
.chip-gen   { background:#e8f5e9; color:#2e7d32; }

.chat-body  { flex:1; overflow-y:auto; padding:20px; display:flex; flex-direction:column; gap:12px; }
.bw         { display:flex; flex-direction:column; }
.bw.sent    { align-items:flex-end; }
.bw.recv    { align-items:flex-start; }
.bubble     { max-width:66%; padding:11px 16px; border-radius:16px; font-size:14px; line-height:1.55; word-break:break-word; }
.bubble.sent{ background:#2e7d32; color:#fff; border-bottom-right-radius:4px; }
.bubble.recv{ background:#fff; color:#333; border:1px solid #e0e0e0; border-bottom-left-radius:4px; }
.btime      { font-size:11px; color:#aaa; margin-top:4px; }

.chat-foot  { padding:13px 16px; background:#fff; border-top:2px solid #e8f5e9; display:flex; gap:10px; flex-shrink:0; }
.chat-foot textarea { flex:1; padding:11px 14px; border:2px solid #ddd; border-radius:10px; resize:none; font-size:14px; font-family:inherit; transition:border-color .2s; }
.chat-foot textarea:focus { outline:none; border-color:#2e7d32; }

.empty-chat { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; color:#ccc; gap:8px; }
.empty-chat .ico { font-size:60px; }
.empty-chat .lbl { font-size:18px; font-weight:700; color:#bbb; }
.empty-chat .sub { font-size:13px; text-align:center; max-width:260px; }

.day-div    { text-align:center; font-size:12px; color:#bbb; margin:6px 0; }
</style>
</head>
<body class="patient-theme">
<nav class="navbar">
    <div class="main-content">
        <a href="dashboard.php" class="navbar-brand">🏥 HealthCare Plus</a>
        <ul class="navbar-menu">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="my_appointments.php">Appointments</a></li>
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
            <li><a href="search_doctors.php">🔍 Search Doctors</a></li>
            <li><a href="my_appointments.php">📅 My Appointments</a></li>
            <li><a href="../video_room.php">📹 Video Consultation</a></li>
            <li><a href="messages.php" class="active">💬 Messages</a></li>
            <li><a href="lab_reports.php">🧪 Lab Reports</a></li>
            <li><a href="prescriptions.php">💊 Prescriptions</a></li>
            <li><a href="profile.php">👤 My Profile</a></li>
        </ul>
    </div></div>
    <main  style="padding:16px 20px;">
        <div class="page-header" style="margin-bottom:14px;">
            <h1>💬 Messages</h1>
            <p>Chat with your doctors</p>
        </div>

        <div class="msg-wrap">

            <!-- ══ LEFT SIDEBAR ══ -->
            <div class="msg-sidebar">

                <!-- Tab strip -->
                <div class="tab-strip">
                    <a href="messages.php?tab=appointment<?php echo $chat_uid?'&chat='.$chat_uid:''; ?>"
                       class="t-btn <?php echo $active_tab==='appointment'?'on':''; ?>">
                        📅 My Doctors
                    </a>
                    <a href="messages.php?tab=general<?php echo $chat_uid?'&chat='.$chat_uid:''; ?>"
                       class="t-btn <?php echo $active_tab==='general'?'on':''; ?>">
                        💬 Ask Anyone
                    </a>
                </div>

                <!-- Context notice -->
                <?php if ($active_tab === 'appointment'): ?>
                <div class="tab-notice appt">
                    🔒 <strong>Appointment chats</strong> — only doctors you've had appointments with
                </div>
                <?php else: ?>
                <div class="tab-notice gen">
                    🌐 <strong>General Q&A</strong> — ask any doctor a quick question, no appointment needed
                </div>
                <?php endif; ?>

                <!-- Doctor list -->
                <div class="doc-list">
                    <?php
                    $list = ($active_tab === 'appointment') ? $appt_doctors : $gen_doctors;
                    if ($list->num_rows === 0):
                    ?>
                        <div class="empty-list">
                            <?php if ($active_tab === 'appointment'): ?>
                                No appointments found.<br>
                                <a href="search_doctors.php" style="color:#1565c0;">Book an appointment</a> to chat here.
                            <?php else: ?>
                                No doctors available.
                            <?php endif; ?>
                        </div>
                    <?php else:
                        while ($d = $list->fetch_assoc()):
                            $is_on  = ($chat_uid == $d['user_id']);
                            $av_cls = $active_tab === 'appointment' ? 'av-appt' : 'av-gen';
                    ?>
                        <a href="messages.php?tab=<?php echo $active_tab; ?>&chat=<?php echo $d['user_id']; ?>"
                           class="doc-row <?php echo $is_on ? 'on' : ''; ?>">
                            <div class="doc-avatar <?php echo $av_cls; ?>">👨‍⚕️</div>
                            <div class="doc-info">
                                <div class="doc-name">Dr. <?php echo htmlspecialchars($d['full_name']); ?></div>
                                <div class="doc-spec"><?php echo htmlspecialchars($d['specialization']); ?></div>
                                <?php if ($d['last_msg']): ?>
                                <div class="doc-last"><?php echo htmlspecialchars(mb_substr($d['last_msg'], 0, 35)); ?>…</div>
                                <?php endif; ?>
                            </div>
                            <?php if ($d['unread'] > 0): ?>
                                <div class="unread-dot"><?php echo $d['unread']; ?></div>
                            <?php endif; ?>
                        </a>
                    <?php endwhile; endif; ?>
                </div>
            </div>

            <!-- ══ RIGHT CHAT AREA ══ -->
            <div class="chat-col">
                <?php if ($chat_doctor && $chat_uid > 0): ?>

                    <!-- Header -->
                    <div class="chat-hdr">
                        <div class="doc-avatar <?php echo $active_tab==='appointment'?'av-appt':'av-gen'; ?>" style="width:46px;height:46px;font-size:22px;">👨‍⚕️</div>
                        <div>
                            <div class="dname">Dr. <?php echo htmlspecialchars($chat_doctor['full_name']); ?></div>
                            <div class="dspec"><?php echo htmlspecialchars($chat_doctor['specialization']); ?></div>
                        </div>
                        <span class="type-chip <?php echo $active_tab==='appointment'?'chip-appt':'chip-gen'; ?>">
                            <?php echo $active_tab==='appointment' ? '📅 Appointment Chat' : '💬 General Q&A'; ?>
                        </span>
                    </div>

                    <!-- Messages -->
                    <div class="chat-body" id="chatBody">
                        <?php if (empty($chat_msgs)): ?>
                            <div class="empty-chat" style="flex:1;">
                                <div class="ico"><?php echo $active_tab==='appointment'?'📅':'💬'; ?></div>
                                <div class="lbl">No messages yet</div>
                                <div class="sub">
                                    <?php if ($active_tab==='appointment'): ?>
                                        Ask Dr. <?php echo htmlspecialchars($chat_doctor['full_name']); ?> about your appointment or health
                                    <?php else: ?>
                                        Ask Dr. <?php echo htmlspecialchars($chat_doctor['full_name']); ?> any medical question
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else:
                            $last_day = '';
                            foreach ($chat_msgs as $msg):
                                $day = date('l, F j, Y', strtotime($msg['created_at']));
                                if ($day !== $last_day):
                                    $last_day = $day;
                        ?>
                                <div class="day-div">── <?php echo $day; ?> ──</div>
                        <?php      endif;
                                $sent = ($msg['sender_id'] == $uid);
                        ?>
                            <div class="bw <?php echo $sent?'sent':'recv'; ?>">
                                <div class="bubble <?php echo $sent?'sent':'recv'; ?>">
                                    <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                </div>
                                <div class="btime"><?php echo date('h:i A', strtotime($msg['created_at'])); ?></div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>

                    <!-- Send form -->
                    <form method="POST" class="chat-foot" onsubmit="return !!document.getElementById('msgIn').value.trim()">
                        <input type="hidden" name="receiver_id" value="<?php echo $chat_uid; ?>">
                        <input type="hidden" name="chat_type"   value="<?php echo $active_tab; ?>">
                        <textarea id="msgIn" name="message" rows="2"
                            placeholder="<?php echo $active_tab==='appointment'?'Message about your appointment or health...':'Ask a medical question...'; ?>"
                            required
                            onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();this.form.submit();}"></textarea>
                        <button type="submit" name="send_message" class="btn btn-glass-primary" style="padding:12px 22px;align-self:flex-end;">Send ➤</button>
                    </form>

                <?php else: ?>
                    <div class="empty-chat">
                        <div class="ico"><?php echo $active_tab==='appointment'?'📅':'💬'; ?></div>
                        <div class="lbl"><?php echo $active_tab==='appointment'?'Select your doctor':'Pick a doctor to ask'; ?></div>
                        <div class="sub">
                            <?php if ($active_tab==='appointment'): ?>
                                Choose a doctor from your appointment history on the left
                            <?php else: ?>
                                Select any doctor and ask your medical question — no appointment needed
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
<script>
const cb = document.getElementById('chatBody');
if (cb) cb.scrollTop = cb.scrollHeight;

// Poll for new messages every 5s
<?php if ($chat_uid > 0): ?>
setInterval(() => {
    fetch('get_new_messages.php?doctor_id=<?php echo $chat_uid; ?>&type=<?php echo $active_tab; ?>')
        .then(r => r.json())
        .then(d => { if (d.new_messages) location.reload(); })
        .catch(() => {});
}, 5000);
<?php endif; ?>
</script>
</body>
</html>
