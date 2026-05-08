<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'doctor') {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

$stmt = $conn->prepare("SELECT d.*, u.username, u.email FROM doctors d JOIN users u ON d.user_id = u.id WHERE d.user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$doctor = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$doctor) { header("Location: ../logout.php"); exit(); }

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $full_name = sanitize($conn, $_POST['full_name']);
    $phone = sanitize($conn, $_POST['phone']);
    $specialization = sanitize($conn, $_POST['specialization']);
    $consultation_fee = floatval($_POST['consultation_fee']);
    
    $stmt = $conn->prepare("UPDATE doctors SET full_name = ?, phone = ?, specialization = ?, consultation_fee = ? WHERE user_id = ?");
    $stmt->bind_param("sssdi", $full_name, $phone, $specialization, $consultation_fee, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        logActivity($conn, $_SESSION['user_id'], 'Update Profile', 'Doctor updated profile');
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
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    if ($stmt->execute()) {
        session_destroy();
        header("Location: ../login.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile - Doctor Panel</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="doctor-theme">
    <nav class="navbar">
        <div class="main-content">
            <a href="dashboard.php" class="navbar-brand">🏥 Doctor Panel</a>
            <ul class="navbar-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="appointments.php">Appointments</a></li>
                <li><a href="messages.php">Messages</a></li>
                <li><a href="update_location.php">Update Location</a></li>
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
                <li><a href="appointments.php">📅 My Appointments</a></li>
                <li><a href="update_location.php">📍 Update Location</a></li>
                <li><a href="messages.php">💬 Patient Messages</a></li>
                <li><a href="profile.php" class="active">👤 My Profile</a></li>
            </ul>
        </div></div>

        <main >
            <div class="page-header">
                <h1>My Profile</h1>
                <p>Manage your account settings</p>
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
                <div class="card-header">Edit Profile Information</div>
                <form method="POST">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($doctor['full_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($doctor['phone']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Specialization</label>
                            <input type="text" name="specialization" value="<?php echo htmlspecialchars($doctor['specialization']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Consultation Fee (Rs.)</label>
                            <input type="number" name="consultation_fee" step="0.01" value="<?php echo $doctor['consultation_fee']; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email (Read-only)</label>
                            <input type="email" value="<?php echo htmlspecialchars($doctor['email']); ?>" readonly style="background: #f5f5f5;">
                        </div>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-glass-primary">Save Changes</button>
                </form>
            </div>

            <div class="card">
                <div class="card-header">Change Password</div>
                <form method="POST">
                    <div style="max-width: 500px;">
                        <div class="form-group">
                            <label>Current Password</label>
                            <input type="password" name="current_password" required>
                        </div>
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" name="new_password" required minlength="6">
                        </div>
                        <div class="form-group">
                            <label>Confirm New Password</label>
                            <input type="password" name="confirm_password" required minlength="6">
                        </div>
                        <button type="submit" name="change_password" class="btn btn-glass-primary">Change Password</button>
                    </div>
                </form>
            </div>

            <div class="card">
                <div class="card-header">Danger Zone</div>
                <p style="color: #c62828; margin-bottom: 15px;">⚠️ Warning: This action cannot be undone. Your account and all associated data will be permanently deleted.</p>
                <button class="btn btn-danger" onclick="confirmDelete()">Delete My Account</button>
            </div>
        </main>
    </div>

    <script>
        function confirmDelete() {
            if (confirm('Are you sure you want to delete your account? This action cannot be undone!')) {
                if (confirm('This is your final warning. All your data will be permanently deleted. Continue?')) {
                    window.location.href = 'profile.php?delete_account=confirm';
                }
            }
        }

        // Validate password confirmation
        document.querySelector('form[name="change_password"]')?.addEventListener('submit', function(e) {
            const newPass = document.querySelector('input[name="new_password"]').value;
            const confirmPass = document.querySelector('input[name="confirm_password"]').value;
            if (newPass !== confirmPass) {
                e.preventDefault();
                alert('New passwords do not match!');
            }
        });
    </script>
</body>
</html>
