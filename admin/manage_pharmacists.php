<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $user_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'pharmacist'");
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        logActivity($conn, $_SESSION['user_id'], 'Delete Pharmacist', 'Admin deleted a pharmacist');
        $_SESSION['success'] = "Pharmacist deleted successfully!";
    }
    header("Location: manage_pharmacists.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Pharmacists - Admin Panel</title>
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
                <li><a href="manage_pharmacists.php" style="background: rgba(255,255,255,0.2);">Pharmacists</a></li>
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
                <li><a href="manage_pharmacists.php" class="active">💊 Pharmacists</a></li>
                <li><a href="manage_appointments.php">📅 Appointments</a></li>
                <li><a href="manage_blogs.php">📰 Blogs</a></li>
                <li><a href="activity_logs.php">📋 Activity Logs</a></li>
                <li><a href="reports.php">📊 Reports</a></li>
                <li><a href="manage_admins.php">⚙️ Administrators</a></li>
            </ul>
        </div></div>

        <main >
            <div class="page-header">
                <h1>Manage Pharmacists</h1>
                <p>Add, edit, and remove pharmacists</p>
            </div>

            <?php
            if (isset($_SESSION['success'])) {
                echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
                unset($_SESSION['success']);
            }
            ?>

            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <div class="card-header" style="margin: 0;">All Pharmacists</div>
                    <button class="btn btn-glass-primary" onclick="openAddModal()">+ Add New Pharmacist</button>
                </div>

                <div class="table-container">
                    <?php
                    $result = $conn->query("SELECT ph.*, u.username, u.email 
                                           FROM pharmacists ph 
                                           JOIN users u ON ph.user_id = u.id 
                                           ORDER BY ph.id DESC");
                    
                    if ($result->num_rows > 0) {
                        echo '<table>';
                        echo '<thead><tr><th>ID</th><th>Name</th><th>Pharmacy</th><th>Phone</th><th>Email</th><th>Prescriptions</th><th>Actions</th></tr></thead>';
                        echo '<tbody>';
                        while ($pharma = $result->fetch_assoc()) {
                            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM prescriptions WHERE pharmacist_id = ?");
                            $stmt->bind_param("i", $pharma['id']);
                            $stmt->execute();
                            $presc_count = $stmt->get_result()->fetch_assoc()['count'];
                            
                            echo '<tr>';
                            echo '<td>' . $pharma['id'] . '</td>';
                            echo '<td>' . htmlspecialchars($pharma['full_name']) . '</td>';
                            echo '<td>' . htmlspecialchars($pharma['pharmacy_name']) . '</td>';
                            echo '<td>' . htmlspecialchars($pharma['phone']) . '</td>';
                            echo '<td>' . htmlspecialchars($pharma['email']) . '</td>';
                            echo '<td>' . $presc_count . '</td>';
                            echo '<td>';
                            echo '<button class="btn btn-sm btn-primary" onclick="editPharmacist(' . $pharma['id'] . ')">Edit</button> ';
                            echo '<a href="?delete=' . $pharma['user_id'] . '" class="btn btn-sm btn-danger" onclick="return confirm(\'Delete?\')">Delete</a>';
                            echo '</td>';
                            echo '</tr>';
                        }
                        echo '</tbody></table>';
                    } else {
                        echo '<p>No pharmacists found.</p>';
                    }
                    ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Modal -->
    <div class="modal" id="addModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Pharmacist</h2>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST" action="process_add_pharmacist.php">
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
                    <label>Pharmacy Name *</label>
                    <input type="text" name="pharmacy_name" required>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="tel" name="phone">
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address"></textarea>
                </div>
                <button type="submit" class="btn btn-glass-primary" style="width: 100%;">Add Pharmacist</button>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Pharmacist</h2>
                <button class="close-modal" onclick="closeEditModal()">&times;</button>
            </div>
            <div id="editForm">Loading...</div>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('addModal').classList.add('active');
        }
        function closeModal() {
            document.getElementById('addModal').classList.remove('active');
        }
        function editPharmacist(pharmacistId) {
            document.getElementById('editModal').classList.add('active');
            fetch('get_pharmacist_edit.php?id=' + pharmacistId)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('editForm').innerHTML = data;
                });
        }
        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }
    </script>
</body>
</html>
