<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Administrators - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="admin-theme">
    <nav class="navbar">
        <div class="main-content">
            <a href="dashboard.php" class="navbar-brand">🏥 Admin Panel</a>
            <ul class="navbar-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="manage_doctors.php">Doctors</a></li>
                <li><a href="manage_patients.php">Patients</a></li>
                <li><a href="manage_pharmacists.php">Pharmacists</a></li>
                <li><a href="manage_blogs.php">Blogs</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="dashboard-container">
<div class="dashboard-container">
        <div ><div class="sidebar">
            <ul>
                <li><a href="dashboard.php">📊 Dashboard</a></li>
                <li><a href="manage_patients.php">👥 Patients</a></li>
                <li><a href="manage_doctors.php">👨‍⚕️ Doctors</a></li>
                <li><a href="manage_pharmacists.php">💊 Pharmacists</a></li>
                <li><a href="manage_appointments.php">📅 Appointments</a></li>
                <li><a href="manage_blogs.php">📰 Blogs</a></li>
                <li><a href="activity_logs.php">📋 Activity Logs</a></li>
                <li><a href="reports.php">📊 Reports</a></li>
                <li><a href="manage_admins.php" class="active">⚙️ Administrators</a></li>
            </ul>
        </div></div>

        <main >
            <div class="page-header">
                <h1>Manage Administrators</h1>
                <p>Add and manage system administrators</p>
            </div>

            <?php
            if (isset($_SESSION['success'])) {
                echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
                unset($_SESSION['success']);
            }
            ?>

            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <div class="card-header" style="margin: 0;">All Administrators</div>
                    <button class="btn btn-glass-primary" onclick="openAddModal()">+ Add New Admin</button>
                </div>

                <div class="table-container">
                    <?php
                    $result = $conn->query("SELECT a.*, u.username, u.email, u.created_at 
                                           FROM admins a 
                                           JOIN users u ON a.user_id = u.id 
                                           ORDER BY a.id ASC");
                    
                    if ($result->num_rows > 0) {
                        echo '<table>';
                        echo '<thead><tr><th>ID</th><th>Name</th><th>Username</th><th>Email</th><th>Phone</th><th>Created</th></tr></thead>';
                        echo '<tbody>';
                        while ($admin = $result->fetch_assoc()) {
                            echo '<tr>';
                            echo '<td>' . $admin['id'] . '</td>';
                            echo '<td>' . htmlspecialchars($admin['full_name']) . '</td>';
                            echo '<td>' . htmlspecialchars($admin['username']) . '</td>';
                            echo '<td>' . htmlspecialchars($admin['email']) . '</td>';
                            echo '<td>' . htmlspecialchars($admin['phone']) . '</td>';
                            echo '<td>' . date('M d, Y', strtotime($admin['created_at'])) . '</td>';
                            echo '</tr>';
                        }
                        echo '</tbody></table>';
                    }
                    ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Admin Modal -->
    <div class="modal" id="addModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Administrator</h2>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST" action="process_add_admin.php">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="full_name" required>
                </div>
                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" required minlength="6">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="tel" name="phone">
                </div>
                <button type="submit" class="btn btn-glass-primary" style="width: 100%;">Add Administrator</button>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('addModal').classList.add('active');
        }
        function closeModal() {
            document.getElementById('addModal').classList.remove('active');
        }
    </script>
</body>
</html>
