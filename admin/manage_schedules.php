<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

// Get selected doctor
$selected_doctor = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;

// Handle delete schedule
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $schedule_id = intval($_GET['delete']);
    $del = $conn->prepare("DELETE FROM schedules WHERE id = ?");
    $del->bind_param("i", $schedule_id);
    $del->execute();
    $del->close();
    logActivity($conn, $_SESSION['user_id'], 'Delete Schedule', 'Admin deleted schedule #' . $schedule_id);
    $_SESSION['success'] = "Schedule deleted successfully!";
    header("Location: manage_schedules.php?doctor_id=$selected_doctor");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Schedules - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="admin-theme">
    <nav class="navbar">
        <div class="main-content">
            <a href="dashboard.php" class="navbar-brand">🏥 Admin Panel</a>
            <ul class="navbar-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="manage_doctors.php">Doctors</a></li>
                <li><a href="manage_schedules.php" style="background: rgba(255,255,255,0.2);">Schedules</a></li>
                <li><a href="manage_patients.php">Patients</a></li>
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
                <li><a href="manage_hospitals.php">🏥 Hospitals</a></li>
                <li><a href="manage_schedules.php" class="active">📅 Doctor Schedules</a></li>
                <li><a href="manage_pharmacists.php">💊 Pharmacists</a></li>
                <li><a href="manage_appointments.php">📋 Appointments</a></li>
                <li><a href="manage_blogs.php">📰 Blogs</a></li>
                <li><a href="activity_logs.php">📋 Activity Logs</a></li>
                <li><a href="reports.php">📊 Reports</a></li>
                <li><a href="manage_admins.php">⚙️ Administrators</a></li>
            </ul>
        </div></div>

        <main >
            <div class="page-header">
                <h1>Manage Doctor Schedules</h1>
                <p>Add, edit, and manage doctor availability schedules</p>
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

            <!-- Doctor Selector -->
            <div class="card">
                <div class="card-header">Select Doctor</div>
                <form method="GET" action="">
                    <div class="form-group">
                        <select name="doctor_id" onchange="this.form.submit()" style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 15px;">
                            <option value="">Choose a doctor...</option>
                            <?php
                            $doctors = $conn->query("SELECT d.id, d.full_name, d.specialization FROM doctors d ORDER BY d.full_name");
                            while ($doc = $doctors->fetch_assoc()) {
                                $selected = ($selected_doctor == $doc['id']) ? 'selected' : '';
                                echo '<option value="' . $doc['id'] . '" ' . $selected . '>Dr. ' . htmlspecialchars($doc['full_name']) . ' (' . htmlspecialchars($doc['specialization']) . ')</option>';
                            }
                            ?>
                        </select>
                    </div>
                </form>
            </div>

            <?php if ($selected_doctor > 0): ?>
                <?php
                // Get doctor details
                $stmt = $conn->prepare("SELECT * FROM doctors WHERE id = ?");
                $stmt->bind_param("i", $selected_doctor);
                $stmt->execute();
                $doctor = $stmt->get_result()->fetch_assoc();
                ?>

                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <div class="card-header" style="margin: 0;">Schedules for Dr. <?php echo htmlspecialchars($doctor['full_name']); ?></div>
                        <button class="btn btn-glass-primary" onclick="openAddModal()">+ Add New Schedule</button>
                    </div>

                    <div class="table-container">
                        <?php
                        $stmt = $conn->prepare("SELECT * FROM schedules WHERE doctor_id = ? ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), start_time");
                        $stmt->bind_param("i", $selected_doctor);
                        $stmt->execute();
                        $schedules = $stmt->get_result();

                        if ($schedules->num_rows > 0) {
                            echo '<table>';
                            echo '<thead><tr><th>Day</th><th>Time</th><th>Hospital</th><th>Max Patients</th><th>Slot Duration</th><th>Actions</th></tr></thead>';
                            echo '<tbody>';
                            while ($schedule = $schedules->fetch_assoc()) {
                                echo '<tr>';
                                echo '<td><strong>' . $schedule['day_of_week'] . '</strong></td>';
                                echo '<td>' . date('h:i A', strtotime($schedule['start_time'])) . ' - ' . date('h:i A', strtotime($schedule['end_time'])) . '</td>';
                                echo '<td>' . htmlspecialchars($schedule['hospital_name']) . '</td>';
                                echo '<td>' . $schedule['max_patients'] . ' patients</td>';
                                echo '<td>' . $schedule['slot_duration'] . ' minutes</td>';
                                echo '<td>';
                                echo '<button class="btn btn-sm btn-primary" onclick="editSchedule(' . $schedule['id'] . ')">Edit</button> ';
                                echo '<a href="?doctor_id=' . $selected_doctor . '&delete=' . $schedule['id'] . '" class="btn btn-sm btn-danger" onclick="return confirm(\'Delete this schedule?\')">Delete</a>';
                                echo '</td>';
                                echo '</tr>';
                            }
                            echo '</tbody></table>';
                        } else {
                            echo '<p>No schedules found for this doctor.</p>';
                        }
                        ?>
                    </div>
                </div>

                <!-- Doctor's Hospitals -->
                <div class="card">
                    <div class="card-header">Assigned Hospitals</div>
                    <?php
                    $stmt = $conn->prepare("SELECT hospital_name, is_primary FROM doctor_hospitals WHERE doctor_id = ?");
                    $stmt->bind_param("i", $selected_doctor);
                    $stmt->execute();
                    $hospitals = $stmt->get_result();

                    if ($hospitals->num_rows > 0) {
                        echo '<div style="display: flex; flex-wrap: wrap; gap: 10px;">';
                        while ($hosp = $hospitals->fetch_assoc()) {
                            $badge = $hosp['is_primary'] ? 'badge-success' : 'badge-info';
                            $label = $hosp['is_primary'] ? 'Primary' : 'Secondary';
                            echo '<span class="badge ' . $badge . '">' . htmlspecialchars($hosp['hospital_name']) . ' (' . $label . ')</span>';
                        }
                        echo '</div>';
                    } else {
                        echo '<p>No hospitals assigned.</p>';
                    }
                    ?>
                    <button class="btn btn-glass-primary" style="margin-top: 15px;" onclick="openHospitalModal()">Manage Hospitals</button>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Add Schedule Modal -->
    <div class="modal" id="addModal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h2>Add New Schedule</h2>
                <button class="close-modal" onclick="closeAddModal()">&times;</button>
            </div>
            <form method="POST" action="process_add_schedule.php">
                <input type="hidden" name="doctor_id" value="<?php echo $selected_doctor; ?>">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Day of Week *</label>
                        <select name="day_of_week" required>
                            <option value="">Select day...</option>
                            <option value="Monday">Monday</option>
                            <option value="Tuesday">Tuesday</option>
                            <option value="Wednesday">Wednesday</option>
                            <option value="Thursday">Thursday</option>
                            <option value="Friday">Friday</option>
                            <option value="Saturday">Saturday</option>
                            <option value="Sunday">Sunday</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Hospital *</label>
                        <select name="hospital_name" required>
                            <option value="">Select hospital...</option>
                            <?php
                            $hospitals = $conn->query("SELECT name FROM hospitals ORDER BY name");
                            while ($h = $hospitals->fetch_assoc()) {
                                echo '<option value="' . htmlspecialchars($h['name']) . '">' . htmlspecialchars($h['name']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Start Time *</label>
                        <input type="time" name="start_time" required>
                    </div>

                    <div class="form-group">
                        <label>End Time *</label>
                        <input type="time" name="end_time" required>
                    </div>

                    <div class="form-group">
                        <label>Max Patients Per Session</label>
                        <input type="number" name="max_patients" value="20" min="1" max="50">
                    </div>

                    <div class="form-group">
                        <label>Slot Duration (minutes)</label>
                        <select name="slot_duration">
                            <option value="30">30 minutes</option>
                            <option value="45" selected>45 minutes</option>
                            <option value="60">60 minutes</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-glass-primary" style="width: 100%; margin-top: 20px;">Add Schedule</button>
            </form>
        </div>
    </div>

    <!-- Edit Schedule Modal -->
    <div class="modal" id="editModal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h2>Edit Schedule</h2>
                <button class="close-modal" onclick="closeEditModal()">&times;</button>
            </div>
            <div id="editForm">Loading...</div>
        </div>
    </div>

    <!-- Manage Hospitals Modal -->
    <div class="modal" id="hospitalModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Manage Doctor's Hospitals</h2>
                <button class="close-modal" onclick="closeHospitalModal()">&times;</button>
            </div>
            <div id="hospitalForm">
                <form method="POST" action="process_doctor_hospitals.php">
                    <input type="hidden" name="doctor_id" value="<?php echo $selected_doctor; ?>">
                    
                    <div class="form-group">
                        <label>Select Hospitals (Hold Ctrl/Cmd to select multiple)</label>
                        <select name="hospitals[]" multiple size="10" style="width: 100%; padding: 10px;">
                            <?php
                            $all_hospitals = $conn->query("SELECT name FROM hospitals ORDER BY name");
                            while ($h = $all_hospitals->fetch_assoc()) {
                                echo '<option value="' . htmlspecialchars($h['name']) . '">' . htmlspecialchars($h['name']) . '</option>';
                            }
                            ?>
                        </select>
                        <small>Hold Ctrl (Windows) or Cmd (Mac) to select multiple hospitals</small>
                    </div>

                    <div class="form-group">
                        <label>Primary Hospital</label>
                        <select name="primary_hospital">
                            <option value="">Select primary hospital...</option>
                            <?php
                            $all_hospitals = $conn->query("SELECT name FROM hospitals ORDER BY name");
                            while ($h = $all_hospitals->fetch_assoc()) {
                                echo '<option value="' . htmlspecialchars($h['name']) . '">' . htmlspecialchars($h['name']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-glass-primary" style="width: 100%;">Save Hospitals</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('addModal').classList.add('active');
        }

        function closeAddModal() {
            document.getElementById('addModal').classList.remove('active');
        }

        function editSchedule(scheduleId) {
            document.getElementById('editModal').classList.add('active');
            fetch('get_schedule_edit.php?id=' + scheduleId)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('editForm').innerHTML = data;
                });
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }

        function openHospitalModal() {
            document.getElementById('hospitalModal').classList.add('active');
        }

        function closeHospitalModal() {
            document.getElementById('hospitalModal').classList.remove('active');
        }
    </script>
</body>
</html>
