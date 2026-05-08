<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'patient') {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

// Get patient details
$stmt = $conn->prepare("SELECT * FROM patients WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$patient) { header("Location: ../logout.php"); exit(); }

// Get pre-selected doctor from session
$doctor_id = isset($_SESSION['booking_doctor_id']) ? intval($_SESSION['booking_doctor_id']) : 0;
$doctor_name = isset($_SESSION['booking_doctor_name']) ? $_SESSION['booking_doctor_name'] : '';
$consultation_fee = isset($_SESSION['booking_fee']) ? $_SESSION['booking_fee'] : 0;

// Get doctor details
if ($doctor_id > 0) {
    $stmt = $conn->prepare("SELECT d.*, u.email FROM doctors d JOIN users u ON d.user_id = u.id WHERE d.id = ?");
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $doctor = $stmt->get_result()->fetch_assoc();
} else {
    // No doctor selected, redirect to search
    header("Location: search_doctors.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quick Booking - HealthCare Plus</title>
    <link rel="stylesheet" href="../css/style.css">
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

    <div style="max-width: 800px; margin: 50px auto; padding: 20px;">
        <div style="background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.1);">
            <div style="text-align: center; margin-bottom: 30px;">
                <div style="font-size: 64px; margin-bottom: 15px;">✅</div>
                <h1 style="color: #2e7d32; margin-bottom: 10px;">Welcome Back!</h1>
                <p style="color: #666; font-size: 18px;">You're logged in. Let's book your appointment!</p>
            </div>

            <div style="background: #e8f5e9; padding: 20px; border-radius: 10px; margin-bottom: 30px;">
                <h3 style="color: #2e7d32; margin-bottom: 15px;">📋 Appointment Details</h3>
                <p style="margin: 8px 0;"><strong>Doctor:</strong> Dr. <?php echo htmlspecialchars($doctor['full_name']); ?></p>
                <p style="margin: 8px 0;"><strong>Specialization:</strong> <?php echo htmlspecialchars($doctor['specialization']); ?></p>
                <p style="margin: 8px 0;"><strong>Consultation Fee:</strong> Rs. <?php echo number_format($doctor['consultation_fee'], 2); ?></p>
                <?php
                // Get doctor's hospitals
                $stmt_h = $conn->prepare("SELECT hospital_name, is_primary FROM doctor_hospitals WHERE doctor_id = ? ORDER BY is_primary DESC");
                $stmt_h->bind_param("i", $doctor['id']);
                $stmt_h->execute();
                $hospitals_list = $stmt_h->get_result();
                if ($hospitals_list->num_rows > 0) {
                    echo '<p style="margin: 8px 0;"><strong>Available Hospitals:</strong><br>';
                    while ($h = $hospitals_list->fetch_assoc()) {
                        $badge = $h['is_primary'] ? ' (Primary)' : '';
                        echo '<span style="display: inline-block; margin: 3px 5px 3px 0; padding: 5px 10px; background: #c8e6c9; border-radius: 5px; font-size: 13px;">' . htmlspecialchars($h['hospital_name']) . $badge . '</span>';
                    }
                    echo '</p>';
                }
                ?>
            </div>

            <form method="POST" action="process_booking.php">
                <input type="hidden" name="doctor_id" value="<?php echo $doctor['id']; ?>">
                <input type="hidden" name="consultation_fee" value="<?php echo $doctor['consultation_fee']; ?>">
                
                <div class="form-group">
                    <label>Appointment Date *</label>
                    <input type="date" name="appointment_date" required min="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label>Appointment Time *</label>
                    <input type="time" name="appointment_time" required>
                </div>

                <div class="form-group">
                    <label>Select Hospital *</label>
                    <select name="hospital_name" required>
                        <option value="">Choose hospital...</option>
                        <?php
                        // Get doctor's hospitals from schedule
                        $stmt = $conn->prepare("SELECT DISTINCT hospital_name FROM schedules WHERE doctor_id = ?");
                        $stmt->bind_param("i", $doctor['id']);
                        $stmt->execute();
                        $hospitals = $stmt->get_result();
                        while ($h = $hospitals->fetch_assoc()) {
                            echo '<option value="' . htmlspecialchars($h['hospital_name']) . '">' . htmlspecialchars($h['hospital_name']) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Additional Notes (Optional)</label>
                    <textarea name="notes" rows="4" placeholder="Any special requirements or health concerns..."></textarea>
                </div>

                <div style="background: #fff9c4; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <p style="margin: 0; color: #f57f17;"><strong>💳 Payment:</strong> Consultation fee will be processed. You'll receive a booking number after confirmation.</p>
                </div>

                <div style="display: flex; gap: 15px;">
                    <button type="submit" class="btn btn-glass-primary" style="flex: 1;">Confirm & Pay Rs. <?php echo number_format($doctor['consultation_fee'], 2); ?></button>
                    <a href="search_doctors.php" class="btn btn-glass">Choose Different Doctor</a>
                </div>
            </form>
        </div>
    </div>

    <?php
    // Clear booking session data after displaying
    unset($_SESSION['booking_doctor_id']);
    unset($_SESSION['booking_doctor_name']);
    unset($_SESSION['booking_fee']);
    ?>
</body>
</html>
