<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['doctor','patient'])) {
    header("Location: login.php"); exit();
}
include 'includes/config.php';

$role     = $_SESSION['role'];
$room     = isset($_GET['room']) ? preg_replace('/[^A-Z0-9]/', '', strtoupper($_GET['room'])) : '';
$is_host  = isset($_GET['host']) && $_GET['host'] == '1';

// Validate room exists
if (!empty($room)) {
    $stmt = $conn->prepare("SELECT vr.*, d.full_name as doctor_name, p.full_name as patient_name FROM video_rooms vr JOIN doctors d ON vr.doctor_id = d.id JOIN patients p ON vr.patient_id = p.id WHERE vr.room_code = ?");
    $stmt->bind_param("s", $room);
    $stmt->execute();
    $video_room = $stmt->get_result()->fetch_assoc();
    if (!$video_room) { die('Invalid room code.'); }
} else {
    die('No room code provided.');
}

$partner_name = ($role === 'doctor') ? $video_room['patient_name'] : 'Dr. ' . $video_room['doctor_name'];
$my_name      = ($role === 'doctor') ? 'Dr. ' . $video_room['doctor_name'] : $video_room['patient_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Consultation - HealthCare Plus</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background:#0d1117; color:white; font-family:'Segoe UI',sans-serif; height:100vh; overflow:hidden; }

        #call-screen { display:flex; flex-direction:column; height:100vh; }

        /* Top bar */
        .call-header { background:rgba(255,255,255,0.05); padding:12px 24px; display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid rgba(255,255,255,0.1); flex-shrink:0; }
        .call-header .brand { font-size:18px; font-weight:bold; color:#4caf50; }
        .call-header .info  { font-size:14px; color:#aaa; }
        .call-header .timer { font-size:20px; font-weight:bold; color:#4caf50; font-variant-numeric:tabular-nums; }

        /* Video area */
        .video-area { flex:1; position:relative; background:#161b22; overflow:hidden; }

        #remoteVideo {
            width:100%; height:100%; object-fit:cover;
            background:#1a1e26;
            display:block;
        }

        #localVideo {
            position:absolute; bottom:20px; right:20px;
            width:200px; height:150px;
            border-radius:12px; object-fit:cover;
            border:3px solid #4caf50;
            background:#0d1117;
            cursor:move;
            box-shadow:0 8px 24px rgba(0,0,0,0.5);
            z-index:10;
        }

        /* Waiting overlay */
        #waitingOverlay {
            position:absolute; inset:0;
            background:rgba(13,17,23,0.92);
            display:flex; flex-direction:column;
            align-items:center; justify-content:center;
            gap:20px; z-index:20;
        }
        .waiting-avatar { width:100px; height:100px; background:linear-gradient(135deg,#2e7d32,#4caf50); border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:48px; animation:pulse 2s infinite; }
        @keyframes pulse { 0%,100%{box-shadow:0 0 0 0 rgba(76,175,80,0.4)} 50%{box-shadow:0 0 0 20px rgba(76,175,80,0)} }
        .waiting-text { font-size:22px; font-weight:bold; }
        .waiting-sub  { font-size:14px; color:#aaa; }
        .waiting-dots span { animation:blink 1.4s infinite; display:inline-block; }
        .waiting-dots span:nth-child(2) { animation-delay:.2s; }
        .waiting-dots span:nth-child(3) { animation-delay:.4s; }
        @keyframes blink { 0%,80%,100%{opacity:0} 40%{opacity:1} }

        /* Controls */
        .controls { background:rgba(0,0,0,0.85); padding:16px 24px; display:flex; align-items:center; justify-content:center; gap:16px; flex-shrink:0; }
        .ctrl-btn { width:56px; height:56px; border-radius:50%; border:none; cursor:pointer; font-size:22px; display:flex; align-items:center; justify-content:center; transition:all 0.2s; }
        .ctrl-btn:hover { transform:scale(1.1); }
        .ctrl-btn.green  { background:#4caf50; }
        .ctrl-btn.red    { background:#e53935; }
        .ctrl-btn.grey   { background:#37474f; color:white; }
        .ctrl-btn.muted  { background:#b71c1c; }
        .ctrl-btn.off    { background:#1565c0; }
        .ctrl-label { font-size:11px; color:#aaa; margin-top:6px; text-align:center; }
        .ctrl-wrap  { display:flex; flex-direction:column; align-items:center; }

        /* Status chip */
        .status-chip { position:absolute; top:16px; left:50%; transform:translateX(-50%); background:rgba(0,0,0,0.6); padding:6px 16px; border-radius:20px; font-size:13px; z-index:10; display:flex; align-items:center; gap:8px; }
        .status-dot  { width:8px; height:8px; border-radius:50%; background:#4caf50; animation:pulse 1.5s infinite; }

        /* Name tag on remote */
        .remote-name { position:absolute; bottom:200px; left:16px; background:rgba(0,0,0,0.6); padding:6px 14px; border-radius:8px; font-size:14px; z-index:10; }

        /* Chat panel */
        .chat-panel { display:none; position:absolute; right:0; top:0; bottom:0; width:300px; background:#161b22; border-left:1px solid rgba(255,255,255,0.1); flex-direction:column; z-index:15; }
        .chat-panel.open { display:flex; }
        .chat-msgs { flex:1; overflow-y:auto; padding:15px; display:flex; flex-direction:column; gap:10px; }
        .chat-msg   { background:rgba(255,255,255,0.07); padding:10px 12px; border-radius:10px; font-size:13px; }
        .chat-msg.me { background:rgba(76,175,80,0.2); align-self:flex-end; max-width:80%; }
        .chat-msg .who { font-size:11px; color:#aaa; margin-bottom:4px; }
        .chat-input-row { padding:12px; border-top:1px solid rgba(255,255,255,0.1); display:flex; gap:8px; }
        .chat-input-row input { flex:1; background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.15); border-radius:8px; padding:10px; color:white; font-size:14px; }
        .chat-input-row input::placeholder { color:#555; }
        .chat-input-row button { background:#4caf50; border:none; border-radius:8px; padding:10px 14px; color:white; cursor:pointer; font-size:16px; }

        /* Setup screen */
        #setupScreen { position:fixed; inset:0; background:#0d1117; display:flex; align-items:center; justify-content:center; z-index:100; }
        .setup-card { background:#161b22; border:1px solid rgba(255,255,255,0.1); border-radius:16px; padding:40px; max-width:500px; width:100%; text-align:center; }
        .setup-card h2 { margin-bottom:8px; font-size:26px; }
        .setup-card p  { color:#aaa; margin-bottom:24px; }
        #setupPreview  { width:100%; border-radius:12px; max-height:250px; object-fit:cover; background:#0d1117; margin-bottom:20px; transform:scaleX(-1); }
        .setup-row { display:flex; gap:10px; align-items:center; margin-bottom:16px; background:rgba(255,255,255,0.05); padding:12px; border-radius:10px; font-size:14px; }
        .setup-row .dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
        .dot-green { background:#4caf50; }
        .dot-red   { background:#e53935; }
        .join-btn  { width:100%; padding:15px; background:linear-gradient(135deg,#2e7d32,#4caf50); border:none; border-radius:10px; color:white; font-size:18px; font-weight:bold; cursor:pointer; transition:0.2s; }
        .join-btn:hover { opacity:0.9; transform:translateY(-1px); }
        .join-btn:disabled { opacity:0.4; cursor:not-allowed; transform:none; }
    </style>
</head>
<body>

<!-- SETUP SCREEN -->
<div id="setupScreen">
    <div class="setup-card">
        <div style="font-size:48px;margin-bottom:12px;">🏥</div>
        <h2>Ready to Join?</h2>
        <p>Consultation with <strong><?php echo htmlspecialchars($partner_name); ?></strong></p>

        <video id="setupPreview" autoplay muted playsinline></video>

        <div class="setup-row">
            <div class="dot" id="camDot"></div>
            <span id="camStatus">Checking camera...</span>
        </div>
        <div class="setup-row">
            <div class="dot" id="micDot"></div>
            <span id="micStatus">Checking microphone...</span>
        </div>

        <div style="background:rgba(76,175,80,0.1);border:1px solid #2e7d32;border-radius:10px;padding:14px;margin-bottom:20px;font-size:13px;color:#aaa;text-align:left;">
            📋 <strong style="color:white;">Room Code:</strong>
            <span style="font-family:monospace;font-size:16px;color:#4caf50;letter-spacing:2px;"><?php echo htmlspecialchars($room); ?></span>
            <br><small>Share this code with your <?php echo $role === 'doctor' ? 'patient' : 'doctor'; ?></small>
        </div>

        <button class="join-btn" id="joinBtn" disabled onclick="joinCall()">
            📹 Join Consultation
        </button>
    </div>
</div>

<!-- CALL SCREEN -->
<div id="call-screen" style="display:none;">
    <div class="call-header">
        <div class="brand">🏥 HealthCare Plus</div>
        <div class="info">
            <?php echo htmlspecialchars($my_name); ?> &nbsp;·&nbsp;
            <span style="color:<?php echo $role==='doctor'?'#4caf50':'#2196f3'; ?>">
                <?php echo ucfirst($role); ?>
            </span>
        </div>
        <div class="timer" id="callTimer">00:00</div>
    </div>

    <div class="video-area">
        <div id="waitingOverlay">
            <div class="waiting-avatar">👤</div>
            <div class="waiting-text">Waiting for <?php echo htmlspecialchars($partner_name); ?></div>
            <div class="waiting-sub">Share the room code: <strong style="color:#4caf50;font-size:18px;letter-spacing:3px;"><?php echo htmlspecialchars($room); ?></strong></div>
            <div class="waiting-dots"><span>.</span><span>.</span><span>.</span></div>
        </div>

        <div class="status-chip" id="statusChip" style="display:none;">
            <div class="status-dot"></div>
            <span id="statusText">Connected</span>
        </div>

        <video id="remoteVideo" autoplay playsinline></video>
        <video id="localVideo"  autoplay muted playsinline></video>
        <div class="remote-name" id="remoteName" style="display:none;"><?php echo htmlspecialchars($partner_name); ?></div>

        <!-- Chat Panel -->
        <div class="chat-panel" id="chatPanel">
            <div style="padding:14px;border-bottom:1px solid rgba(255,255,255,0.1);font-weight:bold;">💬 Chat</div>
            <div class="chat-msgs" id="chatMsgs"></div>
            <div class="chat-input-row">
                <input id="chatInput" placeholder="Type a message..." onkeydown="if(event.key==='Enter')sendChat()">
                <button onclick="sendChat()">➤</button>
            </div>
        </div>
    </div>

    <div class="controls">
        <div class="ctrl-wrap">
            <button class="ctrl-btn grey" id="btnMic" onclick="toggleMic()">🎙️</button>
            <div class="ctrl-label" id="micLabel">Mute</div>
        </div>
        <div class="ctrl-wrap">
            <button class="ctrl-btn grey" id="btnCam" onclick="toggleCam()">📷</button>
            <div class="ctrl-label" id="camLabel">Cam Off</div>
        </div>
        <div class="ctrl-wrap">
            <button class="ctrl-btn grey" onclick="toggleChat()">💬</button>
            <div class="ctrl-label">Chat</div>
        </div>
        <div class="ctrl-wrap">
            <button class="ctrl-btn grey" onclick="flipCamera()">🔄</button>
            <div class="ctrl-label">Flip</div>
        </div>
        <div class="ctrl-wrap">
            <button class="ctrl-btn red" onclick="endCall()">📵</button>
            <div class="ctrl-label">End Call</div>
        </div>
    </div>
</div>

<script>
const ROOM = '<?php echo $room; ?>';
const ROLE = '<?php echo $role; ?>';
const IS_HOST = <?php echo $is_host ? 'true' : 'false'; ?>;
const PARTNER = '<?php echo htmlspecialchars($partner_name, ENT_QUOTES); ?>';

let localStream   = null;
let peerConn      = null;
let micOn         = true;
let camOn         = true;
let callStartTime = null;
let timerInterval = null;
let pollInterval  = null;
let facingMode    = 'user';
let chatOpen      = false;

const ICE_SERVERS = {
    iceServers: [
        { urls: 'stun:stun.l.google.com:19302' },
        { urls: 'stun:stun1.l.google.com:19302' }
    ]
};

// ── SETUP ─────────────────────────────────────────────
async function setup() {
    try {
        localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
        document.getElementById('setupPreview').srcObject = localStream;
        document.getElementById('camDot').className = 'dot dot-green';
        document.getElementById('camStatus').textContent = 'Camera ready ✓';
        document.getElementById('micDot').className = 'dot dot-green';
        document.getElementById('micStatus').textContent = 'Microphone ready ✓';
        document.getElementById('joinBtn').disabled = false;
    } catch(e) {
        if (e.name === 'NotFoundError') {
            document.getElementById('camDot').className = 'dot dot-red';
            document.getElementById('camStatus').textContent = 'No camera found';
        } else if (e.name === 'NotAllowedError') {
            document.getElementById('camDot').className = 'dot dot-red';
            document.getElementById('camStatus').textContent = 'Camera/mic permission denied';
        }
        // Try audio only
        try {
            localStream = await navigator.mediaDevices.getUserMedia({ video: false, audio: true });
            document.getElementById('micDot').className = 'dot dot-green';
            document.getElementById('micStatus').textContent = 'Microphone ready ✓';
            document.getElementById('joinBtn').disabled = false;
        } catch(e2) {
            document.getElementById('micDot').className = 'dot dot-red';
            document.getElementById('micStatus').textContent = 'Microphone denied';
        }
    }
}

async function joinCall() {
    document.getElementById('setupScreen').style.display = 'none';
    document.getElementById('call-screen').style.display = 'flex';
    document.getElementById('localVideo').srcObject = localStream;

    // Notify server
    await signal('join');

    // Create peer connection
    setupPeer();

    if (IS_HOST) {
        // Doctor (host) creates offer
        await createOffer();
    }

    // Start polling for signals
    pollInterval = setInterval(pollSignals, 1500);
}

// ── WebRTC ─────────────────────────────────────────────
function setupPeer() {
    peerConn = new RTCPeerConnection(ICE_SERVERS);

    // Add local tracks
    localStream.getTracks().forEach(track => peerConn.addTrack(track, localStream));

    // Remote stream → remoteVideo
    peerConn.ontrack = e => {
        document.getElementById('remoteVideo').srcObject = e.streams[0];
        connected();
    };

    // ICE candidates → send to other peer
    peerConn.onicecandidate = async e => {
        if (e.candidate) {
            await sendSignal('ice-candidate', JSON.stringify(e.candidate));
        }
    };

    peerConn.onconnectionstatechange = () => {
        if (peerConn.connectionState === 'disconnected' || peerConn.connectionState === 'failed') {
            document.getElementById('statusText').textContent = 'Connection lost...';
        }
    };
}

async function createOffer() {
    const offer = await peerConn.createOffer();
    await peerConn.setLocalDescription(offer);
    await sendSignal('offer', JSON.stringify(offer));
}

async function pollSignals() {
    const res  = await fetch(`video_signal.php?action=get_signals&room=${ROOM}`);
    const data = await res.json();
    if (!data.signals) return;

    for (const sig of data.signals) {
        const parsed = JSON.parse(sig.signal_data);

        if (sig.signal_type === 'offer' && !IS_HOST) {
            await peerConn.setRemoteDescription(new RTCSessionDescription(parsed));
            const answer = await peerConn.createAnswer();
            await peerConn.setLocalDescription(answer);
            await sendSignal('answer', JSON.stringify(answer));
        }
        else if (sig.signal_type === 'answer' && IS_HOST) {
            if (peerConn.signalingState !== 'stable') {
                await peerConn.setRemoteDescription(new RTCSessionDescription(parsed));
            }
        }
        else if (sig.signal_type === 'ice-candidate') {
            try {
                await peerConn.addIceCandidate(new RTCIceCandidate(parsed));
                // Clear consumed ICE candidates
                await fetch(`video_signal.php?action=clear_ice&room=${ROOM}`);
            } catch(e) {}
        }
        else if (sig.signal_type === 'chat') {
            appendChat(PARTNER, parsed.text, false);
        }
        else if (sig.signal_type === 'end') {
            callEnded(false);
        }
    }
}

// ── CONTROLS ──────────────────────────────────────────
function toggleMic() {
    micOn = !micOn;
    localStream.getAudioTracks().forEach(t => t.enabled = micOn);
    document.getElementById('btnMic').textContent   = micOn ? '🎙️' : '🔇';
    document.getElementById('btnMic').className     = micOn ? 'ctrl-btn grey' : 'ctrl-btn muted';
    document.getElementById('micLabel').textContent = micOn ? 'Mute' : 'Unmute';
}

function toggleCam() {
    camOn = !camOn;
    localStream.getVideoTracks().forEach(t => t.enabled = camOn);
    document.getElementById('btnCam').textContent   = camOn ? '📷' : '📵';
    document.getElementById('btnCam').className     = camOn ? 'ctrl-btn grey' : 'ctrl-btn off';
    document.getElementById('camLabel').textContent = camOn ? 'Cam Off' : 'Cam On';
}

async function flipCamera() {
    facingMode = facingMode === 'user' ? 'environment' : 'user';
    localStream.getVideoTracks().forEach(t => t.stop());
    try {
        const newStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode }, audio: false });
        const newTrack  = newStream.getVideoTracks()[0];
        document.getElementById('localVideo').srcObject = newStream;
        // Replace track in peer connection
        if (peerConn) {
            const sender = peerConn.getSenders().find(s => s.track && s.track.kind === 'video');
            if (sender) sender.replaceTrack(newTrack);
        }
        // Merge into localStream
        localStream.getVideoTracks().forEach(t => localStream.removeTrack(t));
        localStream.addTrack(newTrack);
    } catch(e) { facingMode = facingMode === 'user' ? 'environment' : 'user'; }
}

function toggleChat() {
    chatOpen = !chatOpen;
    document.getElementById('chatPanel').classList.toggle('open', chatOpen);
}

async function sendChat() {
    const input = document.getElementById('chatInput');
    const text  = input.value.trim();
    if (!text) return;
    input.value = '';
    appendChat('You', text, true);
    await sendSignal('chat', JSON.stringify({ text }));
}

function appendChat(who, text, isMe) {
    const msgs = document.getElementById('chatMsgs');
    const div  = document.createElement('div');
    div.className = 'chat-msg' + (isMe ? ' me' : '');
    div.innerHTML = `<div class="who">${who}</div><div>${escapeHtml(text)}</div>`;
    msgs.appendChild(div);
    msgs.scrollTop = msgs.scrollHeight;
    if (!chatOpen && !isMe) {
        // Flash chat button
        document.querySelector('[onclick="toggleChat()"]').style.background = '#e53935';
        setTimeout(() => document.querySelector('[onclick="toggleChat()"]').style.background = '#37474f', 2000);
    }
}

function escapeHtml(t) {
    return t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

async function endCall() {
    if (!confirm('End the consultation?')) return;
    await sendSignal('end', '{}');
    await signal('end');
    callEnded(true);
}

function callEnded(byMe) {
    clearInterval(pollInterval);
    clearInterval(timerInterval);
    if (localStream) localStream.getTracks().forEach(t => t.stop());
    if (peerConn)    peerConn.close();
    const back = ROLE === 'doctor' ? 'doctor/appointments.php' : 'patient/my_appointments.php';
    alert(byMe ? 'Consultation ended.' : `${PARTNER} has ended the consultation.`);
    window.location.href = back;
}

// ── HELPERS ───────────────────────────────────────────
function connected() {
    document.getElementById('waitingOverlay').style.display = 'none';
    document.getElementById('statusChip').style.display     = 'flex';
    document.getElementById('remoteName').style.display     = 'block';
    callStartTime = Date.now();
    timerInterval = setInterval(() => {
        const s = Math.floor((Date.now() - callStartTime) / 1000);
        const m = Math.floor(s / 60);
        document.getElementById('callTimer').textContent =
            String(m).padStart(2,'0') + ':' + String(s % 60).padStart(2,'0');
    }, 1000);
}

async function sendSignal(type, data) {
    const fd = new FormData();
    fd.append('action', 'send_signal');
    fd.append('room', ROOM);
    fd.append('type', type);
    fd.append('data', data);
    await fetch('video_signal.php', { method:'POST', body:fd });
}

async function signal(action) {
    await fetch(`video_signal.php?action=${action}&room=${ROOM}`);
}

// Make local video draggable
const lv = document.getElementById('localVideo');
let dragging = false, ox = 0, oy = 0;
lv.addEventListener('mousedown', e => { dragging=true; ox=e.clientX-lv.getBoundingClientRect().left; oy=e.clientY-lv.getBoundingClientRect().top; });
document.addEventListener('mousemove', e => {
    if (!dragging) return;
    const va = document.querySelector('.video-area').getBoundingClientRect();
    lv.style.left   = Math.max(0, Math.min(e.clientX - va.left - ox, va.width - lv.offsetWidth)) + 'px';
    lv.style.top    = Math.max(0, Math.min(e.clientY - va.top  - oy, va.height- lv.offsetHeight)) + 'px';
    lv.style.right  = 'auto';
    lv.style.bottom = 'auto';
});
document.addEventListener('mouseup', () => dragging=false);

// Boot
setup();
</script>
</body>
</html>
