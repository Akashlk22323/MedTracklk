<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'patient') {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

$patient_stmt = $conn->prepare("SELECT * FROM patients WHERE user_id = ?");
$patient_stmt->bind_param("i", $_SESSION['user_id']);
$patient_stmt->execute();
$patient = $patient_stmt->get_result()->fetch_assoc();
$patient_stmt->close();
if (!$patient) { header("Location: ../logout.php"); exit(); }

$doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;

if ($doctor_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM doctors WHERE id = ?");
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $doctor = $stmt->get_result()->fetch_assoc();
    if (!$doctor) { header("Location: search_doctors.php"); exit(); }
    $accepts_video = !empty($doctor['accepts_video_call']);
} else {
    $_SESSION['error'] = "Please select a doctor";
    header("Location: search_doctors.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book Appointment - HealthCare Plus</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .schedule-calendar { display: grid; grid-template-columns: repeat(7, 1fr); gap: 10px; margin: 20px 0; }
        .day-slot { padding: 15px; border: 2px solid #ddd; border-radius: 8px; text-align: center; cursor: pointer; transition: all 0.3s; position: relative; }
        .day-slot:hover { border-color: #2e7d32; background: #f1f8f4; }
        .day-slot.selected { border-color: #2e7d32; background: #c8e6c9; }
        .day-slot.disabled { opacity: 0.5; cursor: not-allowed; background: #f5f5f5; }
        .day-slot.has-schedule { border-color: #4caf50; }
        .availability-indicator { 
            position: absolute; 
            top: 5px; 
            right: 5px; 
            width: 12px; 
            height: 12px; 
            border-radius: 50%; 
            animation: pulse 2s infinite;
        }
        .available-dot { background: #4caf50; box-shadow: 0 0 5px rgba(76, 175, 80, 0.5); }
        .unavailable-dot { background: #f44336; }
        .no-schedule-dot { background: #9e9e9e; }
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.2); }
        }
        .legend { display: flex; gap: 20px; justify-content: center; margin: 20px 0; padding: 15px; background: #f5f5f5; border-radius: 8px; }
        .legend-item { display: flex; align-items: center; gap: 8px; }
        .legend-dot { width: 12px; height: 12px; border-radius: 50%; }
        .time-slots { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 10px; margin: 20px 0; }
        .time-slot { padding: 12px; border: 2px solid #ddd; border-radius: 8px; text-align: center; cursor: pointer; transition: all 0.3s; }
        .time-slot:hover:not(.booked) { border-color: #2e7d32; background: #f1f8f4; }
        .time-slot.selected { border-color: #2e7d32; background: #c8e6c9; font-weight: bold; }
        .time-slot.booked { background: #ffebee; border-color: #ef5350; cursor: not-allowed; }
        .hospital-card { padding: 15px; border: 2px solid #ddd; border-radius: 8px; margin: 10px 0; cursor: pointer; transition: all 0.3s; }
        .hospital-card:hover { border-color: #2e7d32; }
        .hospital-card.selected { border-color: #2e7d32; background: #c8e6c9; }
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
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div style="max-width: 1200px; margin: 50px auto; padding: 20px;">
        <div class="card">
            <div class="card-header">Book Appointment with Dr. <?php echo htmlspecialchars($doctor['full_name']); ?></div>
            
            <div style="background: #e3f2fd; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                <h3 style="margin: 0 0 15px 0;">Doctor Information</h3>
                <p style="margin: 5px 0;"><strong>Specialization:</strong> <?php echo htmlspecialchars($doctor['specialization']); ?></p>
                <p style="margin: 5px 0;"><strong>Consultation Fee:</strong> Rs. <?php echo number_format($doctor['consultation_fee'], 2); ?></p>
            </div>

            <form method="POST" action="process_booking.php" id="bookingForm">
                <input type="hidden" name="doctor_id" value="<?php echo $doctor['id']; ?>">
                <input type="hidden" name="appointment_date" id="selected_date">
                <input type="hidden" name="appointment_time" id="selected_time">
                <input type="hidden" name="hospital_name" id="selected_hospital">
                <input type="hidden" name="appointment_type" id="appointment_type" value="">
                <input type="hidden" name="payment_method" id="payment_method" value="">

                <!-- ── Step 0: Appointment Type ── -->
                <div class="form-group" id="type-section">
                    <label style="font-size: 18px; font-weight: bold;">Step 1: Choose Appointment Type</label>
                    <div style="display:grid; grid-template-columns:1fr<?php echo $accepts_video ? ' 1fr' : ''; ?>; gap:14px; margin-top:12px;">
                        <div class="type-card" id="type-hospital" onclick="selectType('at_hospital')"
                             style="border:2px solid #ddd; border-radius:12px; padding:20px; text-align:center; cursor:pointer; transition:all 0.2s;">
                            <div style="font-size:40px; margin-bottom:8px;">🏥</div>
                            <div style="font-size:16px; font-weight:bold; color:#1565c0;">At Hospital</div>
                            <div style="font-size:13px; color:#888; margin-top:4px;">Visit in person<br>Pay at hospital or by card</div>
                        </div>
                        <?php if ($accepts_video): ?>
                        <div class="type-card" id="type-video" onclick="selectType('video_call')"
                             style="border:2px solid #ddd; border-radius:12px; padding:20px; text-align:center; cursor:pointer; transition:all 0.2s;">
                            <div style="font-size:40px; margin-bottom:8px;">📹</div>
                            <div style="font-size:16px; font-weight:bold; color:#7b1fa2;">Video Call</div>
                            <div style="font-size:13px; color:#888; margin-top:4px;">Consult from home<br>Card payment required</div>
                        </div>
                        <?php else: ?>
                        <div style="border:2px dashed #ddd; border-radius:12px; padding:20px; text-align:center; opacity:0.5;">
                            <div style="font-size:40px; margin-bottom:8px;">📹</div>
                            <div style="font-size:15px; font-weight:bold; color:#999;">Video Call</div>
                            <div style="font-size:12px; color:#bbb; margin-top:4px;">Not offered by this doctor</div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ── At Hospital flow ── -->
                <div id="hospital-flow" style="display:none;">
                    <!-- Date -->
                    <div class="form-group">
                        <label style="font-size: 18px; font-weight: bold;">Step 2: Select Date</label>
                        <div class="legend">
                            <div class="legend-item"><div class="legend-dot available-dot"></div><span>Available</span></div>
                            <div class="legend-item"><div class="legend-dot unavailable-dot"></div><span>Fully Booked</span></div>
                            <div class="legend-item"><div class="legend-dot no-schedule-dot"></div><span>No Schedule</span></div>
                        </div>
                        <div class="schedule-calendar" id="calendar"></div>
                    </div>
                    <!-- Hospital -->
                    <div class="form-group" id="hospital-section" style="display: none;">
                        <label style="font-size: 18px; font-weight: bold;">Step 3: Select Hospital</label>
                        <div id="hospital-list"></div>
                    </div>
                    <!-- Time -->
                    <div class="form-group" id="time-section" style="display: none;">
                        <label style="font-size: 18px; font-weight: bold;">Step 4: Select Time Slot</label>
                        <div class="time-slots" id="time-slots"></div>
                    </div>
                    <!-- Summary -->
                    <div id="summary" style="display: none; background: #c8e6c9; padding: 20px; border-radius: 10px; margin: 20px 0;">
                        <h3>Booking Summary</h3>
                        <p><strong>Type:</strong> 🏥 Hospital Visit</p>
                        <p><strong>Doctor:</strong> Dr. <?php echo htmlspecialchars($doctor['full_name']); ?></p>
                        <p><strong>Date:</strong> <span id="summary-date"></span></p>
                        <p><strong>Time:</strong> <span id="summary-time"></span></p>
                        <p><strong>Hospital:</strong> <span id="summary-hospital"></span></p>
                        <p><strong>Fee:</strong> Rs. <?php echo number_format($doctor['consultation_fee'], 2); ?></p>
                    </div>
                    <div class="form-group">
                        <label>Additional Notes (Optional)</label>
                        <textarea name="notes" rows="3" placeholder="Any special requests or information for the doctor"></textarea>
                    </div>
                    <!-- Payment buttons — shown after time selected -->
                    <div id="paymentButtons" style="display:none; grid-template-columns:1fr 1fr; gap:15px; margin-top:10px;">
                        <button type="button" onclick="bookAdvance()"
                            style="padding:16px; font-size:15px; background:#1565c0; color:white; border:none; border-radius:8px; cursor:pointer; line-height:1.4;">
                            🏥 Pay at Hospital<br>
                            <small style="font-size:12px; opacity:0.85;">Advance Booking — Pay when you arrive</small>
                        </button>
                        <button type="button" onclick="openCardModal()"
                            style="padding:16px; font-size:15px; background:#2e7d32; color:white; border:none; border-radius:8px; cursor:pointer; line-height:1.4;">
                            💳 Pay by Card<br>
                            <small style="font-size:12px; opacity:0.85;">Pay now — Rs. <?php echo number_format($doctor['consultation_fee'], 2); ?></small>
                        </button>
                    </div>
                </div>

                <!-- ── Video Call flow ── -->
                <div id="video-flow" style="display:none;">
                    <div style="background:#f3e5f5; border:2px solid #ce93d8; border-radius:12px; padding:20px; margin-bottom:18px;">
                        <div style="font-size:15px; font-weight:bold; color:#6a1b9a; margin-bottom:8px;">📹 Video Consultation</div>
                        <div style="font-size:14px; color:#555; line-height:1.7;">
                            • A secure video room link will be available in My Appointments<br>
                            • Join from any device on the appointment date<br>
                            • Card payment is required to confirm your slot
                        </div>
                    </div>
                    <div class="form-group">
                        <label style="font-size: 18px; font-weight: bold;">Step 2: Select Date</label>
                        <div class="schedule-calendar" id="video-calendar"></div>
                    </div>
                    <div id="video-summary" style="display:none; background:#e8eaf6; padding:20px; border-radius:10px; margin:18px 0;">
                        <h3>Video Booking Summary</h3>
                        <p><strong>Type:</strong> 📹 Video Call</p>
                        <p><strong>Doctor:</strong> Dr. <?php echo htmlspecialchars($doctor['full_name']); ?></p>
                        <p><strong>Date:</strong> <span id="video-summary-date"></span></p>
                        <p><strong>Fee:</strong> Rs. <?php echo number_format($doctor['consultation_fee'], 2); ?></p>
                    </div>
                    <div class="form-group">
                        <label>Additional Notes (Optional)</label>
                        <textarea name="notes_video" rows="3" placeholder="Describe your symptoms or reason for consultation"></textarea>
                    </div>
                    <div id="video-pay-btn" style="display:none; margin-top:10px;">
                        <button type="button" onclick="openCardModal()"
                            style="width:100%; padding:16px; font-size:15px; background:#7b1fa2; color:white; border:none; border-radius:8px; cursor:pointer;">
                            💳 Pay &amp; Confirm Video Consultation — Rs. <?php echo number_format($doctor['consultation_fee'], 2); ?>
                        </button>
                    </div>
                </div>

            </form><!-- form ends here -->
        </div>
    </div>

    <!-- ── CARD PAYMENT MODAL ── -->
    <div id="cardModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
        <div style="background:white; border-radius:16px; padding:30px; width:100%; max-width:420px; margin:20px; box-shadow:0 10px 40px rgba(0,0,0,0.3);">

            <h2 style="margin:0 0 6px; color:#333;">💳 Card Payment</h2>
            <p style="color:#888; margin:0 0 20px; font-size:14px;">Demo only — no real payment is processed</p>

            <!-- Amount -->
            <div style="background:#e8f5e9; border-radius:8px; padding:14px 16px; margin-bottom:20px; text-align:center;">
                <div style="font-size:13px; color:#555;">Amount to pay</div>
                <div style="font-size:28px; font-weight:bold; color:#2e7d32;">Rs. <?php echo number_format($doctor['consultation_fee'], 2); ?></div>
            </div>

            <!-- Card form -->
            <div style="margin-bottom:14px;">
                <label style="font-size:13px; font-weight:bold; color:#555;">Card Number</label>
                <input id="cardNum" type="text" maxlength="19" placeholder="1234  5678  9012  3456"
                    oninput="formatCard(this)"
                    style="width:100%; padding:12px; border:2px solid #ddd; border-radius:8px; font-size:16px; letter-spacing:2px; margin-top:4px; box-sizing:border-box;">
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px;">
                <div>
                    <label style="font-size:13px; font-weight:bold; color:#555;">Expiry Date</label>
                    <input id="cardExp" type="text" maxlength="5" placeholder="MM/YY"
                        oninput="formatExpiry(this)"
                        style="width:100%; padding:12px; border:2px solid #ddd; border-radius:8px; font-size:15px; margin-top:4px; box-sizing:border-box;">
                </div>
                <div>
                    <label style="font-size:13px; font-weight:bold; color:#555;">CVV</label>
                    <input id="cardCvv" type="password" maxlength="3" placeholder="123"
                        style="width:100%; padding:12px; border:2px solid #ddd; border-radius:8px; font-size:15px; margin-top:4px; box-sizing:border-box;">
                </div>
            </div>
            <div style="margin-bottom:20px;">
                <label style="font-size:13px; font-weight:bold; color:#555;">Cardholder Name</label>
                <input id="cardName" type="text" placeholder="Name on card"
                    style="width:100%; padding:12px; border:2px solid #ddd; border-radius:8px; font-size:15px; margin-top:4px; box-sizing:border-box;">
            </div>

            <!-- Processing state -->
            <div id="processing" style="display:none; text-align:center; padding:10px 0 16px;">
                <div style="font-size:28px; margin-bottom:8px;">⏳</div>
                <div style="font-weight:bold; color:#555;">Processing payment...</div>
            </div>

            <div style="display:flex; gap:10px;">
                <button onclick="closeCardModal()"
                    style="flex:1; padding:13px; background:#f5f5f5; border:2px solid #ddd; border-radius:8px; cursor:pointer; font-size:15px;">
                    Cancel
                </button>
                <button onclick="submitCardPayment()" class="pay-btn"
                    style="flex:2; padding:13px; background:#2e7d32; color:white; border:none; border-radius:8px; cursor:pointer; font-size:15px; font-weight:bold;">
                    💳 Pay Now
                </button>
            </div>

            <p style="text-align:center; font-size:12px; color:#bbb; margin:14px 0 0;">
                🔒 Demo system — no real card data is stored
            </p>
        </div>
    </div>

    <script>
        const doctorId = <?php echo $doctor['id']; ?>;
        let selectedDate = null;
        let selectedHospital = null;
        let selectedTime = null;
        let dateAvailability = {}; // Store availability info for each date

        // Generate calendar for next 14 days
        function generateCalendar() {
            const calendar = document.getElementById('calendar');
            const today = new Date();
            
            // First, fetch all schedules for the doctor
            fetch(`get_all_schedules.php?doctor_id=${doctorId}`)
                .then(response => response.json())
                .then(scheduleData => {
                    // Create calendar
                    for (let i = 0; i < 14; i++) {
                        const date = new Date(today);
                        date.setDate(date.getDate() + i);
                        
                        const daySlot = document.createElement('div');
                        daySlot.className = 'day-slot';
                        
                        const dateStr = date.toISOString().split('T')[0];
                        const dayName = date.toLocaleDateString('en-US', {weekday: 'long'});
                        
                        // Check if doctor has schedule for this day
                        const hasSchedule = scheduleData.days.includes(dayName);
                        
                        // Create indicator dot
                        let indicator = '';
                        if (hasSchedule) {
                            daySlot.classList.add('has-schedule');
                            // Will check availability asynchronously
                            checkDateAvailability(dateStr, dayName, daySlot);
                            indicator = '<div class="availability-indicator available-dot" id="indicator-' + dateStr + '"></div>';
                        } else {
                            indicator = '<div class="availability-indicator no-schedule-dot"></div>';
                        }
                        
                        daySlot.innerHTML = `
                            ${indicator}
                            <div style="font-size: 12px; color: #666;">${date.toLocaleDateString('en-US', {weekday: 'short'})}</div>
                            <div style="font-size: 20px; font-weight: bold; margin: 5px 0;">${date.getDate()}</div>
                            <div style="font-size: 12px; color: #666;">${date.toLocaleDateString('en-US', {month: 'short'})}</div>
                        `;
                        
                        if (hasSchedule) {
                            daySlot.onclick = () => selectDate(dateStr, dayName, daySlot);
                        } else {
                            daySlot.style.cursor = 'not-allowed';
                            daySlot.style.opacity = '0.6';
                        }
                        
                        calendar.appendChild(daySlot);
                    }
                });
        }

        // Check if a specific date has available slots
        function checkDateAvailability(dateStr, dayName, daySlot) {
            fetch(`check_date_availability.php?doctor_id=${doctorId}&date=${dateStr}&day=${dayName}`)
                .then(response => response.json())
                .then(data => {
                    const indicator = document.getElementById('indicator-' + dateStr);
                    if (indicator) {
                        if (data.available_slots > 0) {
                            indicator.className = 'availability-indicator available-dot';
                            dateAvailability[dateStr] = 'available';
                        } else {
                            indicator.className = 'availability-indicator unavailable-dot';
                            dateAvailability[dateStr] = 'full';
                            // Make the slot less prominent but still clickable (to show waitlist option)
                            daySlot.style.opacity = '0.7';
                        }
                    }
                });
        }

        function selectDate(date, dayName, element) {
            // Remove previous selection
            document.querySelectorAll('.day-slot').forEach(el => el.classList.remove('selected'));
            element.classList.add('selected');
            
            selectedDate = date;
            document.getElementById('selected_date').value = date;
            document.getElementById('summary-date').textContent = new Date(date).toLocaleDateString('en-US', {weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'});
            
            // Load hospitals for this day
            loadHospitals(dayName);
        }

        function loadHospitals(dayName) {
            fetch(`get_doctor_schedule.php?doctor_id=${doctorId}&day=${dayName}`)
                .then(response => response.json())
                .then(data => {
                    const hospitalList = document.getElementById('hospital-list');
                    hospitalList.innerHTML = '';
                    
                    if (data.schedules && data.schedules.length > 0) {
                        data.schedules.forEach(schedule => {
                            const card = document.createElement('div');
                            card.className = 'hospital-card';
                            card.innerHTML = `
                                <h4 style="margin: 0 0 10px 0;">🏥 ${schedule.hospital_name}</h4>
                                <p style="margin: 5px 0;">⏰ ${schedule.start_time} - ${schedule.end_time}</p>
                                <p style="margin: 5px 0;">👥 Max ${schedule.max_patients} patients</p>
                            `;
                            card.onclick = () => selectHospital(schedule, card);
                            hospitalList.appendChild(card);
                        });
                        
                        document.getElementById('hospital-section').style.display = 'block';
                        document.getElementById('time-section').style.display = 'none';
                        document.getElementById('summary').style.display = 'none';
                    } else {
                        hospitalList.innerHTML = '<p style="color: #999;">No schedule available for this day</p>';
                        document.getElementById('hospital-section').style.display = 'block';
                    }
                });
        }

        function selectHospital(schedule, element) {
            document.querySelectorAll('.hospital-card').forEach(el => el.classList.remove('selected'));
            element.classList.add('selected');
            
            selectedHospital = schedule.hospital_name;
            document.getElementById('selected_hospital').value = schedule.hospital_name;
            document.getElementById('summary-hospital').textContent = schedule.hospital_name;
            
            // Generate time slots
            generateTimeSlots(schedule);
        }

        function generateTimeSlots(schedule) {
            const slotsContainer = document.getElementById('time-slots');
            slotsContainer.innerHTML = '';
            
            const [startHour, startMin] = schedule.start_time.split(':');
            const [endHour, endMin] = schedule.end_time.split(':');
            
            let currentTime = new Date();
            currentTime.setHours(parseInt(startHour), parseInt(startMin), 0);
            
            const endTime = new Date();
            endTime.setHours(parseInt(endHour), parseInt(endMin), 0);
            
            const slotDuration = parseInt(schedule.slot_duration);
            
            // Check existing appointments for this date and hospital
            fetch(`check_availability.php?doctor_id=${doctorId}&date=${selectedDate}&hospital=${encodeURIComponent(schedule.hospital_name)}`)
                .then(response => response.json())
                .then(bookedSlots => {
                    while (currentTime < endTime) {
                        const timeStr = currentTime.toTimeString().slice(0, 5);
                        const slot = document.createElement('div');
                        slot.className = 'time-slot';
                        
                        const isBooked = bookedSlots.includes(timeStr);
                        if (isBooked) {
                            slot.classList.add('booked');
                            slot.innerHTML = `${formatTime(timeStr)}<br><small>Booked</small>`;
                        } else {
                            slot.innerHTML = formatTime(timeStr);
                            slot.onclick = () => selectTime(timeStr, slot);
                        }
                        
                        slotsContainer.appendChild(slot);
                        currentTime.setMinutes(currentTime.getMinutes() + slotDuration);
                    }
                    
                    document.getElementById('time-section').style.display = 'block';
                });
        }

        function selectTime(time, element) {
            document.querySelectorAll('.time-slot').forEach(el => el.classList.remove('selected'));
            element.classList.add('selected');
            selectedTime = time;
            document.getElementById('selected_time').value = time;
            document.getElementById('summary-time').textContent = formatTime(time);
            document.getElementById('summary').style.display = 'block';
            document.getElementById('paymentButtons').style.display = 'grid';
        }

        function formatTime(time) {
            const [hour, min] = time.split(':');
            const h = parseInt(hour);
            return `${h % 12 || 12}:${min} ${h >= 12 ? 'PM' : 'AM'}`;
        }

        // ── TYPE SELECTION ──
        let currentType = '';

        function selectType(type) {
            currentType = type;
            document.getElementById('appointment_type').value = type;

            // Highlight selected card
            document.querySelectorAll('.type-card').forEach(c => {
                c.style.border = '2px solid #ddd';
                c.style.background = '';
            });
            const card = document.getElementById('type-' + (type === 'at_hospital' ? 'hospital' : 'video'));
            if (card) {
                card.style.border = type === 'at_hospital' ? '2px solid #1565c0' : '2px solid #7b1fa2';
                card.style.background = type === 'at_hospital' ? '#e3f2fd' : '#f3e5f5';
            }

            if (type === 'at_hospital') {
                document.getElementById('hospital-flow').style.display = 'block';
                document.getElementById('video-flow').style.display = 'none';
                generateCalendar();
            } else {
                document.getElementById('hospital-flow').style.display = 'none';
                document.getElementById('video-flow').style.display = 'block';
                generateVideoCalendar();
            }
        }

        // ── VIDEO CALENDAR (simple date picker, no schedule constraint) ──
        function generateVideoCalendar() {
            const cal = document.getElementById('video-calendar');
            if (cal.children.length > 0) return; // already built
            cal.className = 'schedule-calendar';
            const today = new Date();
            for (let i = 1; i <= 14; i++) {
                const date = new Date(today);
                date.setDate(date.getDate() + i);
                const dateStr = date.toISOString().split('T')[0];
                const slot = document.createElement('div');
                slot.className = 'day-slot has-schedule';
                slot.innerHTML = `
                    <div style="font-size:12px;color:#666;">${date.toLocaleDateString('en-US',{weekday:'short'})}</div>
                    <div style="font-size:20px;font-weight:bold;margin:5px 0;">${date.getDate()}</div>
                    <div style="font-size:12px;color:#666;">${date.toLocaleDateString('en-US',{month:'short'})}</div>
                `;
                slot.onclick = () => selectVideoDate(dateStr, slot);
                cal.appendChild(slot);
            }
        }

        function selectVideoDate(date, element) {
            document.querySelectorAll('#video-calendar .day-slot').forEach(el => el.classList.remove('selected'));
            element.classList.add('selected');
            document.getElementById('selected_date').value = date;
            document.getElementById('selected_time').value = '09:00:00';
            document.getElementById('video-summary-date').textContent =
                new Date(date).toLocaleDateString('en-US', {weekday:'long', year:'numeric', month:'long', day:'numeric'});
            document.getElementById('video-summary').style.display = 'block';
            document.getElementById('video-pay-btn').style.display = 'block';
        }

        // ── VALIDATION ──
        function readyToBook() {
            if (!currentType) { alert('Please select an appointment type first.'); return false; }
            if (currentType === 'at_hospital') {
                if (!selectedDate || !selectedHospital || !selectedTime) {
                    alert('Please complete all steps: date, hospital, and time slot.');
                    return false;
                }
            } else {
                if (!document.getElementById('selected_date').value) {
                    alert('Please select a date for your video consultation.');
                    return false;
                }
            }
            return true;
        }

        function bookAdvance() {
            if (!readyToBook()) return;
            document.getElementById('payment_method').value = 'pay_at_hospital';
            document.getElementById('bookingForm').submit();
        }

        function openCardModal() {
            if (!readyToBook()) return;
            document.getElementById('cardModal').style.display = 'flex';
        }

        function closeCardModal() {
            document.getElementById('cardModal').style.display = 'none';
            document.getElementById('processing').style.display = 'none';
        }

        function formatCard(input) {
            let v = input.value.replace(/\D/g, '').slice(0, 16);
            input.value = v.match(/.{1,4}/g)?.join('  ') || v;
        }

        function formatExpiry(input) {
            let v = input.value.replace(/\D/g, '').slice(0, 4);
            if (v.length >= 3) v = v.slice(0,2) + '/' + v.slice(2);
            input.value = v;
        }

        function submitCardPayment() {
            const num  = document.getElementById('cardNum').value.replace(/\s/g, '');
            const exp  = document.getElementById('cardExp').value;
            const cvv  = document.getElementById('cardCvv').value;
            const name = document.getElementById('cardName').value.trim();
            if (num.length < 16) { alert('Please enter a valid 16-digit card number.'); return; }
            if (exp.length < 5)  { alert('Please enter a valid expiry date (MM/YY).'); return; }
            if (cvv.length < 3)  { alert('Please enter a valid 3-digit CVV.'); return; }
            if (!name)           { alert('Please enter the cardholder name.'); return; }
            document.getElementById('processing').style.display = 'block';
            document.querySelector('#cardModal .pay-btn').style.display = 'none';
            setTimeout(function() {
                document.getElementById('payment_method').value = 'card';
                document.getElementById('bookingForm').submit();
            }, 2000);
        }

        // Don't auto-generate calendar — wait for type selection
    </script>
</body>
</html>
