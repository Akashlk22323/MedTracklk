<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'doctor') {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

$stmt = $conn->prepare("SELECT d.* FROM doctors d WHERE d.user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$doctor = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$doctor) { header("Location: ../logout.php"); exit(); }

// Get doctor's assigned hospitals
$stmt = $conn->prepare("SELECT hospital_name FROM doctor_hospitals WHERE doctor_id = ? ORDER BY is_primary DESC");
$stmt->bind_param("i", $doctor['id']);
$stmt->execute();
$assigned_hospitals = $stmt->get_result();

// Handle location update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_hospital = sanitize($conn, $_POST['current_hospital']);
    $next_hospital = sanitize($conn, $_POST['next_hospital']);
    $ongoing_number = intval($_POST['ongoing_number']);

    $stmt = $conn->prepare("UPDATE doctors SET current_hospital = ?, next_hospital = ?, ongoing_number = ? WHERE id = ?");
    $stmt->bind_param("ssii", $current_hospital, $next_hospital, $ongoing_number, $doctor['id']);
    
    if ($stmt->execute()) {
        logActivity($conn, $_SESSION['user_id'], 'Update Location', 'Doctor updated current hospital and queue number');
        $_SESSION['success'] = "Location updated successfully!";
        header("Location: update_location.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Location - Doctor Panel</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="doctor-theme">
    <nav class="navbar">
        <div class="main-content">
            <a href="dashboard.php" class="navbar-brand">🏥 Doctor Panel</a>
            <ul class="navbar-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="appointments.php">Appointments</a></li>
                <li><a href="update_location.php" style="background: rgba(255,255,255,0.2);">Update Location</a></li>
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
                <li><a href="appointments.php">📅 My Appointments</a></li>
                <li><a href="update_location.php" class="active">📍 Update Location</a></li>
                <li><a href="messages.php">💬 Patient Messages</a></li>
                <li><a href="profile.php">👤 My Profile</a></li>
            </ul>
        </div></div>

        <main >
            <div class="page-header">
                <h1>Update Current Location</h1>
                <p>Let patients know where you are and your queue status</p>
            </div>

            <?php
            if (isset($_SESSION['success'])) {
                echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
                unset($_SESSION['success']);
            }
            ?>

            <div class="card">
                <div class="card-header">Current Status</div>
                <div style="background: #e3f2fd; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                        <div>
                            <strong style="color: #1565c0;">Current Hospital:</strong><br>
                            <span style="font-size: 18px;"><?php echo htmlspecialchars($doctor['current_hospital'] ?? 'Not Set'); ?></span>
                        </div>
                        <div>
                            <strong style="color: #1565c0;">Next Hospital:</strong><br>
                            <span style="font-size: 18px;"><?php echo htmlspecialchars($doctor['next_hospital'] ?? 'Not Set'); ?></span>
                        </div>
                        <div>
                            <strong style="color: #1565c0;">Ongoing Patient Number:</strong><br>
                            <span style="font-size: 32px; font-weight: bold; color: #2e7d32;">#<?php echo $doctor['ongoing_number']; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Update Your Location</div>
                
                <form method="POST">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Current Hospital *</label>
                            <select name="current_hospital" required style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 15px;">
                                <option value="">Select Current Hospital</option>
                                <?php
                                $hospitals_array = [];
                                while ($hosp = $assigned_hospitals->fetch_assoc()) {
                                    $hospitals_array[] = $hosp;
                                    $selected = ($doctor['current_hospital'] == $hosp['hospital_name']) ? 'selected' : '';
                                    echo '<option value="' . htmlspecialchars($hosp['hospital_name']) . '" ' . $selected . '>' . htmlspecialchars($hosp['hospital_name']) . '</option>';
                                }
                                ?>
                            </select>
                            <small>Select from your assigned hospitals</small>
                        </div>

                        <div class="form-group">
                            <label>Next Hospital (Optional)</label>
                            <select name="next_hospital" style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 15px;">
                                <option value="">Select Next Hospital</option>
                                <?php
                                foreach ($hospitals_array as $hosp) {
                                    $selected = ($doctor['next_hospital'] == $hosp['hospital_name']) ? 'selected' : '';
                                    echo '<option value="' . htmlspecialchars($hosp['hospital_name']) . '" ' . $selected . '>' . htmlspecialchars($hosp['hospital_name']) . '</option>';
                                }
                                ?>
                            </select>
                            <small>Where you're heading next</small>
                        </div>

                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>Ongoing Patient Number *</label>
                            <input type="number" name="ongoing_number" value="<?php echo $doctor['ongoing_number']; ?>" required min="0" style="width: 200px; padding: 12px; font-size: 18px; font-weight: bold;">
                            <small>Which patient number is currently being attended</small>
                        </div>
                    </div>

                    <div style="background: #fff9c4; padding: 15px; border-radius: 8px; margin: 20px 0;">
                        <p style="margin: 0; color: #f57f17;">
                            ℹ️ <strong>Note:</strong> Your location and queue number help patients track wait times. Each slot is 45 minutes.
                        </p>
                    </div>

                    <button type="submit" class="btn btn-glass-primary" style="width: 100%; font-size: 18px; padding: 15px;">
                        📍 Update Location & Queue Number
                    </button>
                </form>
            </div>

            <div class="card">
                <div class="card-header">Quick Hospital Presets</div>
                <p style="margin-bottom: 15px;">Click to quickly set your current location:</p>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 10px;">
                    <?php
                    if (!empty($hospitals_array)) {
                        foreach ($hospitals_array as $hosp) {
                            echo '<button type="button" class="btn btn-glass" onclick="setQuickLocation(\'' . htmlspecialchars($hosp['hospital_name'], ENT_QUOTES) . '\')">';
                            echo '🏥 ' . htmlspecialchars($hosp['hospital_name']);
                            echo '</button>';
                        }
                    } else {
                        echo '<p style="color: #999;">No hospitals assigned. Contact admin.</p>';
                    }
                    ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        function setQuickLocation(hospitalName) {
            const select = document.querySelector('select[name="current_hospital"]');
            select.value = hospitalName;
            select.style.background = '#fff9c4';
            setTimeout(() => { select.style.background = ''; }, 1000);
            document.querySelector('form').scrollIntoView({ behavior: 'smooth' });
        }
    </script>
</body>
</html>
