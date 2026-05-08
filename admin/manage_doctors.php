<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $user_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'doctor'");
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        logActivity($conn, $_SESSION['user_id'], 'Delete Doctor', 'Admin deleted a doctor');
        $_SESSION['success'] = "Doctor deleted successfully!";
    }
    header("Location: manage_doctors.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Doctors - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="admin-theme">
    <nav class="navbar">
        <div class="main-content">
            <a href="dashboard.php" class="navbar-brand">🏥 Admin Panel</a>
            <ul class="navbar-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="manage_doctors.php" style="background: rgba(255,255,255,0.2);">Doctors</a></li>
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
                <li><a href="manage_doctors.php" class="active">👨‍⚕️ Doctors</a></li>
                <li><a href="manage_hospitals.php">🏥 Hospitals</a></li>
                <li><a href="manage_pharmacists.php">💊 Pharmacists</a></li>
                <li><a href="manage_appointments.php">📅 Appointments</a></li>
                <li><a href="manage_blogs.php">📰 Blogs</a></li>
                <li><a href="activity_logs.php">📋 Activity Logs</a></li>
                <li><a href="reports.php">📊 Reports</a></li>
                <li><a href="manage_admins.php">⚙️ Administrators</a></li>
            </ul>
        </div></div>

        <main >
            <div class="page-header">
                <h1>Manage Doctors</h1>
                <p>Add, edit, and remove doctors from the system</p>
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
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <div class="card-header" style="margin: 0;">All Doctors</div>
                    <button class="btn btn-glass-primary" onclick="openAddModal()">+ Add New Doctor</button>
                </div>

                <div class="table-container">
                    <?php
                    $result = $conn->query("SELECT d.*, u.username, u.email, u.status 
                                           FROM doctors d 
                                           JOIN users u ON d.user_id = u.id 
                                           ORDER BY d.id DESC");
                    
                    if ($result->num_rows > 0) {
                        echo '<table>';
                        echo '<thead><tr><th>ID</th><th>Name</th><th>Specialization</th><th>Hospitals</th><th>Phone</th><th>License</th><th>Fee</th><th>Appointments</th><th>Earnings</th><th>Actions</th></tr></thead>';
                        echo '<tbody>';
                        while ($doctor = $result->fetch_assoc()) {
                            // Get doctor stats
                            $stmt = $conn->prepare("SELECT COUNT(*) as count, SUM(payment_amount) as earnings FROM appointments WHERE doctor_id = ?");
                            $stmt->bind_param("i", $doctor['id']);
                            $stmt->execute();
                            $stats = $stmt->get_result()->fetch_assoc();
                            
                            // Get doctor's hospitals
                            $stmt = $conn->prepare("SELECT hospital_name, is_primary FROM doctor_hospitals WHERE doctor_id = ? ORDER BY is_primary DESC");
                            $stmt->bind_param("i", $doctor['id']);
                            $stmt->execute();
                            $hospitals = $stmt->get_result();
                            
                            $hospital_list = [];
                            while ($hosp = $hospitals->fetch_assoc()) {
                                $label = $hosp['is_primary'] ? ' (Primary)' : '';
                                $hospital_list[] = htmlspecialchars($hosp['hospital_name']) . $label;
                            }
                            $hospital_display = !empty($hospital_list) ? implode('<br>', $hospital_list) : '<em>No hospitals assigned</em>';
                            
                            echo '<tr>';
                            echo '<td>' . $doctor['id'] . '</td>';
                            echo '<td>' . htmlspecialchars($doctor['full_name']) . '</td>';
                            echo '<td>' . htmlspecialchars($doctor['specialization']) . '</td>';
                            echo '<td>' . $hospital_display . '</td>';
                            echo '<td>' . htmlspecialchars($doctor['phone'] ?? '') . '</td>';
                            echo '<td>' . htmlspecialchars($doctor['license_number'] ?? '') . '</td>';
                            echo '<td>Rs. ' . number_format($doctor['consultation_fee'], 2) . '</td>';
                            echo '<td>' . ($stats['count'] ?? 0) . '</td>';
                            echo '<td>Rs. ' . number_format($stats['earnings'] ?? 0, 2) . '</td>';
                            echo '<td>';
                            echo '<button class="btn btn-sm btn-primary" onclick="editDoctor(' . $doctor['id'] . ')">Edit</button> ';
                            echo '<a href="?delete=' . $doctor['user_id'] . '" class="btn btn-sm btn-danger" onclick="return confirm(\'Delete this doctor?\')">Delete</a>';
                            echo '</td>';
                            echo '</tr>';
                        }
                        echo '</tbody></table>';
                    } else {
                        echo '<p>No doctors found.</p>';
                    }
                    ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Doctor Modal -->
    <div class="modal" id="addModal">
        <div class="modal-content" style="max-width:820px; max-height:90vh; overflow-y:auto;">
            <div class="modal-header">
                <h2>Add New Doctor</h2>
                <button class="close-modal" onclick="closeAddModal()">&times;</button>
            </div>
            <form method="POST" action="process_add_doctor.php" onsubmit="return checkHospitals()">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
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
                        <label>Specialization *</label>
                        <select name="specialization" required>
                            <option value="">Select specialization</option>
                            <option>Cardiologist</option>
                            <option>Dermatologist</option>
                            <option>Endocrinologist</option>
                            <option>ENT Specialist</option>
                            <option>Gastroenterologist</option>
                            <option>General Physician</option>
                            <option>General Surgeon</option>
                            <option>Gynecologist</option>
                            <option>Hematologist</option>
                            <option>Nephrologist</option>
                            <option>Neurologist</option>
                            <option>Neurosurgeon</option>
                            <option>Obstetrician</option>
                            <option>Oncologist</option>
                            <option>Ophthalmologist</option>
                            <option>Orthopedic Surgeon</option>
                            <option>Pediatrician</option>
                            <option>Psychiatrist</option>
                            <option>Pulmonologist</option>
                            <option>Radiologist</option>
                            <option>Rheumatologist</option>
                            <option>Urologist</option>
                            <option>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="tel" name="phone">
                    </div>
                    <div class="form-group">
                        <label>License Number</label>
                        <input type="text" name="license_number">
                    </div>
                    <div class="form-group">
                        <label>Consultation Fee (Rs.) *</label>
                        <input type="number" name="consultation_fee" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Appointment Types</label>
                        <div style="display:flex;gap:16px;margin-top:6px;flex-wrap:wrap;">
                            <label style="display:flex;align-items:center;gap:8px;font-weight:normal;font-size:14px;background:#e3f2fd;padding:10px 16px;border-radius:8px;border:2px solid #90caf9;cursor:default;">
                                🏥 At Hospital <span style="font-size:12px;color:#888;">(always enabled)</span>
                            </label>
                            <label style="display:flex;align-items:center;gap:8px;font-weight:normal;font-size:14px;background:#f3e5f5;padding:10px 16px;border-radius:8px;border:2px solid #ce93d8;cursor:pointer;">
                                <input type="checkbox" name="accepts_video_call" value="1" style="width:16px;height:16px;">
                                📹 Video Call Consultations
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Hospital selection -->
                <div class="form-group" style="margin-top:10px;">
                    <label style="font-weight:bold;">🏥 Assign Hospitals * <small style="font-weight:normal; color:#888;">(select one or more)</small></label>

                    <!-- Quick-pick buttons -->
                    <div style="margin:8px 0 6px;">
                        <small style="color:#555; font-weight:bold;">⚡ Quick select:</small>
                        <div id="quickHospBtns" style="display:flex; flex-wrap:wrap; gap:6px; margin-top:5px;">
                            <?php
                            $hlist = $conn->query("SELECT name FROM hospitals ORDER BY name");
                            while ($h = $hlist->fetch_assoc()):
                            ?>
                            <button type="button"
                                style="padding:5px 12px; background:#e8f5e9; border:2px solid #a5d6a7; border-radius:20px; cursor:pointer; font-size:12px; color:#2e7d32;"
                                onclick="toggleHosp(this, '<?php echo addslashes(htmlspecialchars($h['name'])); ?>')">
                                <?php echo htmlspecialchars($h['name']); ?>
                            </button>
                            <?php endwhile; ?>
                        </div>
                        <?php if ($conn->query("SELECT COUNT(*) as c FROM hospitals")->fetch_assoc()['c'] == 0): ?>
                            <p style="color:#e53935; font-size:13px;">⚠️ No hospitals added yet. <a href="manage_hospitals.php">Add hospitals first</a>.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Selected hospital tags + hidden inputs -->
                    <div id="selectedHosps" style="min-height:38px; padding:8px 10px; background:#f8fff8; border:2px solid #c8e6c9; border-radius:8px; display:flex; flex-wrap:wrap; gap:6px; align-items:center;">
                        <span id="noHospMsg" style="color:#aaa; font-size:13px;">No hospitals selected yet</span>
                    </div>

                    <!-- Primary hospital picker -->
                    <div id="primaryWrap" style="display:none; margin-top:10px;">
                        <label style="font-size:13px; font-weight:bold;">⭐ Primary Hospital:</label>
                        <select name="primary_hospital" id="primarySel" style="padding:8px; border:2px solid #ddd; border-radius:6px; width:100%; margin-top:4px;">
                            <option value="">Select primary...</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-glass-primary" style="width:100%; margin-top:18px; padding:13px; font-size:15px;">
                    ➕ Add Doctor
                </button>
            </form>
        </div>
    </div>

    <!-- Edit Doctor Modal -->
    <div class="modal" id="editModal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h2>Edit Doctor</h2>
                <button class="close-modal" onclick="closeEditModal()">&times;</button>
            </div>
            <div id="editForm">Loading...</div>
        </div>
    </div>

    <script>
        function openAddModal()  { document.getElementById('addModal').classList.add('active'); }
        function closeAddModal() { document.getElementById('addModal').classList.remove('active'); }
        function editDoctor(id)  {
            document.getElementById('editModal').classList.add('active');
            fetch('get_doctor_edit.php?id=' + id)
                .then(r => r.text())
                .then(d => { document.getElementById('editForm').innerHTML = d; });
        }
        function closeEditModal() { document.getElementById('editModal').classList.remove('active'); }

        // Track selected hospitals
        const picked = new Set();

        function toggleHosp(btn, name) {
            if (picked.has(name)) {
                picked.delete(name);
                btn.style.background   = '#e8f5e9';
                btn.style.borderColor  = '#a5d6a7';
                btn.style.color        = '#2e7d32';
                btn.style.fontWeight   = 'normal';
            } else {
                picked.add(name);
                btn.style.background   = '#2e7d32';
                btn.style.borderColor  = '#2e7d32';
                btn.style.color        = 'white';
                btn.style.fontWeight   = 'bold';
            }
            renderHosps();
        }

        function renderHosps() {
            const box     = document.getElementById('selectedHosps');
            const noMsg   = document.getElementById('noHospMsg');
            const wrap    = document.getElementById('primaryWrap');
            const sel     = document.getElementById('primarySel');

            // Remove old tags and hidden inputs
            box.querySelectorAll('.htag, input[type=hidden]').forEach(e => e.remove());

            if (picked.size === 0) {
                noMsg.style.display = 'inline';
                wrap.style.display  = 'none';
                return;
            }
            noMsg.style.display = 'none';

            picked.forEach(name => {
                // Hidden input
                const inp   = document.createElement('input');
                inp.type    = 'hidden';
                inp.name    = 'hospitals[]';
                inp.value   = name;
                box.appendChild(inp);

                // Tag chip
                const tag   = document.createElement('span');
                tag.className = 'htag';
                tag.style.cssText = 'background:#2e7d32;color:white;padding:4px 10px;border-radius:12px;font-size:13px;display:inline-flex;align-items:center;gap:5px;';
                tag.innerHTML = '🏥 ' + name + ' <span style="cursor:pointer;" onclick="removeHosp(\'' + name.replace(/'/g,"\\'") + '\')">&times;</span>';
                box.appendChild(tag);
            });

            // Rebuild primary select
            const prev = sel.value;
            sel.innerHTML = '<option value="">Select primary...</option>';
            picked.forEach(name => {
                const opt   = document.createElement('option');
                opt.value   = name;
                opt.text    = name;
                if (name === prev) opt.selected = true;
                sel.appendChild(opt);
            });
            if (!prev || !picked.has(prev)) sel.value = [...picked][0];
            wrap.style.display = 'block';
        }

        function removeHosp(name) {
            picked.delete(name);
            // Deactivate button
            document.querySelectorAll('#quickHospBtns button').forEach(btn => {
                if (btn.textContent.trim() === name) {
                    btn.style.background  = '#e8f5e9';
                    btn.style.borderColor = '#a5d6a7';
                    btn.style.color       = '#2e7d32';
                    btn.style.fontWeight  = 'normal';
                }
            });
            renderHosps();
        }

        function checkHospitals() {
            if (picked.size === 0) {
                alert('Please select at least one hospital.');
                return false;
            }
            return true;
        }
    </script>
</body>
</html>
