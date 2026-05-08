<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'patient') {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

$stmt = $conn->prepare("SELECT p.*, u.username, u.email FROM patients p JOIN users u ON p.user_id = u.id WHERE p.user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$patient) { header("Location: ../logout.php"); exit(); }

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $full_name = sanitize($conn, $_POST['full_name']);
    $phone = sanitize($conn, $_POST['phone']);
    $address = sanitize($conn, $_POST['address']);
    $date_of_birth = sanitize($conn, $_POST['date_of_birth']);
    $gender = sanitize($conn, $_POST['gender']);
    
    $stmt = $conn->prepare("UPDATE patients SET full_name = ?, phone = ?, address = ?, date_of_birth = ?, gender = ? WHERE user_id = ?");
    $stmt->bind_param("sssssi", $full_name, $phone, $address, $date_of_birth, $gender, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        logActivity($conn, $_SESSION['user_id'], 'Update Profile', 'Patient updated profile information');
        $_SESSION['success'] = "Profile updated successfully!";
        header("Location: profile.php");
        exit();
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = md5($_POST['current_password']);
    $new_password = md5($_POST['new_password']);
    
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stored_password = $stmt->get_result()->fetch_assoc()['password'];
    
    if ($current_password == $stored_password) {
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $new_password, $_SESSION['user_id']);
        if ($stmt->execute()) {
            logActivity($conn, $_SESSION['user_id'], 'Change Password', 'Patient changed password');
            $_SESSION['success'] = "Password changed successfully!";
            header("Location: profile.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Current password is incorrect!";
    }
}

// Handle account deletion
if (isset($_GET['delete_account']) && $_GET['delete_account'] == 'confirm') {
    logActivity($conn, $_SESSION['user_id'], 'Delete Account', 'Patient deleted their account');
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    if ($stmt->execute()) {
        session_destroy();
        header("Location: ../login.php?deleted=1");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile - HealthCare Plus</title>
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
                <li><a href="profile.php" style="background: rgba(255,255,255,0.2);">Profile</a></li>
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
                <li><a href="doctor_location.php">📍 Doctor Locations</a></li>
                <li><a href="messages.php">💬 Ask Doctor</a></li>
                <li><a href="lab_reports.php">📋 Lab Reports</a></li>
                <li><a href="prescriptions.php">💊 Prescriptions</a></li>
                <li><a href="profile.php" class="active">👤 My Profile</a></li>
            </ul>
        </div></div>

        <main >
            <div class="page-header">
                <h1>My Profile</h1>
                <p>Manage your personal information and account settings</p>
            </div>

            <?php
            if (isset($_SESSION['success'])) {
                echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
                unset($_SESSION['success']);
            }
            if (isset($_SESSION['error'])) {
                echo '<div class="alert alert-error">' . $_SESSION['error'] . '</div>';
                unset($_SESSION['error']);
            }
            ?>

            <div class="card">
                <div class="card-header">Personal Information</div>
                <form method="POST">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($patient['full_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($patient['phone']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Date of Birth *</label>
                            <input type="date" name="date_of_birth" value="<?php echo $patient['date_of_birth']; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Gender *</label>
                            <select name="gender" required>
                                <option value="Male" <?php echo $patient['gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo $patient['gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo $patient['gender'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>Address</label>
                            <textarea name="address" rows="3"><?php echo htmlspecialchars($patient['address']); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Email (Read-only)</label>
                            <input type="email" value="<?php echo htmlspecialchars($patient['email']); ?>" readonly style="background: #f5f5f5;">
                        </div>
                        <div class="form-group">
                            <label>Username (Read-only)</label>
                            <input type="text" value="<?php echo htmlspecialchars($patient['username']); ?>" readonly style="background: #f5f5f5;">
                        </div>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-glass-primary">💾 Save Changes</button>
                </form>
            </div>

            <div class="card">
                <div class="card-header">Account Statistics</div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                    <?php
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE patient_id = ?");
                    $stmt->bind_param("i", $patient['id']);
                    $stmt->execute();
                    $total_appointments = $stmt->get_result()->fetch_assoc()['count'];
                    
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM lab_reports WHERE patient_id = ?");
                    $stmt->bind_param("i", $patient['id']);
                    $stmt->execute();
                    $total_reports = $stmt->get_result()->fetch_assoc()['count'];
                    
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM prescriptions WHERE patient_id = ?");
                    $stmt->bind_param("i", $patient['id']);
                    $stmt->execute();
                    $total_prescriptions = $stmt->get_result()->fetch_assoc()['count'];
                    ?>
                    <div style="background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); padding: 20px; border-radius: 10px; text-align: center;">
                        <div style="font-size: 32px; font-weight: bold; color: #1565c0;"><?php echo $total_appointments; ?></div>
                        <div style="color: #555;">Total Appointments</div>
                    </div>
                    <div style="background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); padding: 20px; border-radius: 10px; text-align: center;">
                        <div style="font-size: 32px; font-weight: bold; color: #2e7d32;"><?php echo $total_reports; ?></div>
                        <div style="color: #555;">Lab Reports</div>
                    </div>
                    <div style="background: linear-gradient(135deg, #fff9c4 0%, #fff59d 100%); padding: 20px; border-radius: 10px; text-align: center;">
                        <div style="font-size: 32px; font-weight: bold; color: #f57f17;"><?php echo $total_prescriptions; ?></div>
                        <div style="color: #555;">Prescriptions</div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Change Password</div>
                <form method="POST">
                    <div style="max-width: 500px;">
                        <div class="form-group">
                            <label>Current Password *</label>
                            <input type="password" name="current_password" required placeholder="Enter current password">
                        </div>
                        <div class="form-group">
                            <label>New Password * (minimum 6 characters)</label>
                            <input type="password" name="new_password" id="new_password" required minlength="6" placeholder="Enter new password">
                        </div>
                        <div class="form-group">
                            <label>Confirm New Password *</label>
                            <input type="password" name="confirm_password" id="confirm_password" required minlength="6" placeholder="Re-enter new password">
                        </div>
                        <button type="submit" name="change_password" class="btn btn-glass-primary">🔒 Change Password</button>
                    </div>
                </form>
            </div>

            <div class="card">
                <div class="card-header" style="color: #c62828;">⚠️ Danger Zone</div>
                <p style="color: #c62828; margin-bottom: 15px;">
                    <strong>Warning:</strong> Deleting your account is permanent and cannot be undone. 
                    All your data including appointments, messages, and medical records will be permanently deleted.
                </p>
                <button class="btn btn-danger" onclick="confirmDelete()">🗑️ Delete My Account Permanently</button>
            </div>
        </main>
    </div>

    <script>
        function confirmDelete() {
            if (confirm('⚠️ ARE YOU ABSOLUTELY SURE?\n\nThis will permanently delete:\n• Your account\n• All appointments\n• Medical history\n• Lab reports\n• Messages\n• Prescriptions\n\nThis action CANNOT be undone!')) {
                if (confirm('⚠️ FINAL WARNING!\n\nYou are about to permanently delete your account and ALL associated data.\n\nClick OK to proceed with deletion, or Cancel to keep your account.')) {
                    window.location.href = 'profile.php?delete_account=confirm';
                }
            }
        }

        // Validate password match
        document.querySelector('form').addEventListener('submit', function(e) {
            const newPass = document.getElementById('new_password');
            const confirmPass = document.getElementById('confirm_password');
            
            if (newPass && confirmPass && newPass.value !== confirmPass.value) {
                e.preventDefault();
                alert('❌ New passwords do not match! Please re-enter.');
                confirmPass.focus();
            }
        });
    </script>
</body>
</html>
