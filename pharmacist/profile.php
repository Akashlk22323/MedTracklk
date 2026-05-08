<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pharmacist') {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

$stmt = $conn->prepare("SELECT ph.*, u.username, u.email FROM pharmacists ph JOIN users u ON ph.user_id = u.id WHERE ph.user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$pharmacist = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$pharmacist) { header("Location: ../logout.php"); exit(); }

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $full_name = sanitize($conn, $_POST['full_name']);
    $pharmacy_name = sanitize($conn, $_POST['pharmacy_name']);
    $phone = sanitize($conn, $_POST['phone']);
    $address = sanitize($conn, $_POST['address']);
    
    $stmt = $conn->prepare("UPDATE pharmacists SET full_name = ?, pharmacy_name = ?, phone = ?, address = ? WHERE user_id = ?");
    $stmt->bind_param("ssssi", $full_name, $pharmacy_name, $phone, $address, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        logActivity($conn, $_SESSION['user_id'], 'Update Profile', 'Pharmacist updated profile');
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
            logActivity($conn, $_SESSION['user_id'], 'Change Password', 'Pharmacist changed password');
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
    logActivity($conn, $_SESSION['user_id'], 'Delete Account', 'Pharmacist deleted account');
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
    <title>My Profile - Pharmacist Panel</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="pharmacist-theme">
    <nav class="navbar">
        <div class="main-content">
            <a href="dashboard.php" class="navbar-brand">🏥 Pharmacist Panel</a>
            <ul class="navbar-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="prescriptions.php">Prescriptions</a></li>
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
                <li><a href="prescriptions.php">💊 Prescriptions</a></li>
                <li><a href="profile.php" class="active">👤 My Profile</a></li>
            </ul>
        </div></div>

        <main >
            <div class="page-header">
                <h1>My Profile</h1>
                <p>Manage your pharmacy account settings</p>
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
                <div class="card-header">Pharmacy Information</div>
                <form method="POST">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($pharmacist['full_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Pharmacy Name *</label>
                            <input type="text" name="pharmacy_name" value="<?php echo htmlspecialchars($pharmacist['pharmacy_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($pharmacist['phone']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Email (Read-only)</label>
                            <input type="email" value="<?php echo htmlspecialchars($pharmacist['email']); ?>" readonly style="background: #f5f5f5;">
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>Pharmacy Address</label>
                            <textarea name="address" rows="3"><?php echo htmlspecialchars($pharmacist['address']); ?></textarea>
                        </div>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-glass-primary">💾 Save Changes</button>
                </form>
            </div>

            <div class="card">
                <div class="card-header">Pharmacy Statistics</div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                    <?php
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM prescriptions WHERE pharmacist_id = ?");
                    $stmt->bind_param("i", $pharmacist['id']);
                    $stmt->execute();
                    $total_prescriptions = $stmt->get_result()->fetch_assoc()['count'];
                    
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM prescriptions WHERE pharmacist_id = ? AND status = 'pending'");
                    $stmt->bind_param("i", $pharmacist['id']);
                    $stmt->execute();
                    $pending_prescriptions = $stmt->get_result()->fetch_assoc()['count'];
                    
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM prescriptions WHERE pharmacist_id = ? AND status = 'completed'");
                    $stmt->bind_param("i", $pharmacist['id']);
                    $stmt->execute();
                    $completed_prescriptions = $stmt->get_result()->fetch_assoc()['count'];
                    ?>
                    <div style="background: linear-gradient(135deg, #efebe9 0%, #d7ccc8 100%); padding: 20px; border-radius: 10px; text-align: center;">
                        <div style="font-size: 32px; font-weight: bold; color: #5d4037;"><?php echo $total_prescriptions; ?></div>
                        <div style="color: #555;">Total Prescriptions</div>
                    </div>
                    <div style="background: linear-gradient(135deg, #fff9c4 0%, #fff59d 100%); padding: 20px; border-radius: 10px; text-align: center;">
                        <div style="font-size: 32px; font-weight: bold; color: #f57f17;"><?php echo $pending_prescriptions; ?></div>
                        <div style="color: #555;">Pending Review</div>
                    </div>
                    <div style="background: linear-gradient(135deg, #c8e6c9 0%, #a5d6a7 100%); padding: 20px; border-radius: 10px; text-align: center;">
                        <div style="font-size: 32px; font-weight: bold; color: #2e7d32;"><?php echo $completed_prescriptions; ?></div>
                        <div style="color: #555;">Completed</div>
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
                    All your data including prescription history will be permanently deleted.
                </p>
                <button class="btn btn-danger" onclick="confirmDelete()">🗑️ Delete My Account Permanently</button>
            </div>
        </main>
    </div>

    <script>
        function confirmDelete() {
            if (confirm('⚠️ ARE YOU ABSOLUTELY SURE?\n\nThis will permanently delete:\n• Your account\n• All prescription records\n• Pharmacy information\n\nThis action CANNOT be undone!')) {
                if (confirm('⚠️ FINAL WARNING!\n\nClick OK to permanently delete your account, or Cancel to keep it.')) {
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
