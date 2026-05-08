<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pharmacist') {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

$stmt = $conn->prepare("SELECT ph.* FROM pharmacists ph WHERE ph.user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$pharmacist = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$pharmacist) { header("Location: ../logout.php"); exit(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Prescriptions - Pharmacist Panel</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="pharmacist-theme">
    <nav class="navbar">
        <div class="main-content">
            <a href="dashboard.php" class="navbar-brand">🏥 Pharmacist Panel</a>
            <ul class="navbar-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="prescriptions.php" style="background: rgba(255,255,255,0.2);">Prescriptions</a></li>
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
                <li><a href="prescriptions.php" class="active">💊 Prescriptions</a></li>
                <li><a href="profile.php">👤 My Profile</a></li>
            </ul>
        </div></div>

        <main >
            <div class="page-header">
                <h1>Prescription Management</h1>
                <p>Review and confirm patient prescriptions</p>
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

            <!-- Filter Tabs -->
            <div style="margin-bottom: 20px;">
                <button class="btn btn-warning" onclick="filterPrescriptions('pending')">🟡 Pending</button>
                <button class="btn btn-info" onclick="filterPrescriptions('confirmed')">🔵 Confirmed</button>
                <button class="btn btn-success" onclick="filterPrescriptions('completed')">🟢 Completed</button>
                <button class="btn btn-glass" onclick="filterPrescriptions('all')">All</button>
            </div>

            <div class="card">
                <div class="card-header">All Prescriptions</div>
                <div class="table-container">
                    <?php
                    $stmt = $conn->prepare("SELECT pr.*, 
                                           p.full_name as patient_name, 
                                           p.phone as patient_phone,
                                           p.address as patient_address,
                                           u.email as patient_email
                                           FROM prescriptions pr 
                                           JOIN patients p ON pr.patient_id = p.id 
                                           JOIN users u ON p.user_id = u.id
                                           WHERE pr.pharmacist_id = ? 
                                           ORDER BY pr.created_at DESC");
                    $stmt->bind_param("i", $pharmacist['id']);
                    $stmt->execute();
                    $prescriptions = $stmt->get_result();

                    if ($prescriptions->num_rows > 0) {
                        echo '<table id="prescriptionsTable">';
                        echo '<thead><tr><th>ID</th><th>Patient</th><th>Contact</th><th>Prescription</th><th>Received</th><th>Status</th><th>Actions</th></tr></thead>';
                        echo '<tbody>';
                        while ($presc = $prescriptions->fetch_assoc()) {
                            $badge_class = 'badge-warning';
                            if ($presc['status'] == 'confirmed') $badge_class = 'badge-info';
                            elseif ($presc['status'] == 'completed') $badge_class = 'badge-success';
                            
                            echo '<tr class="presc-row" data-status="' . $presc['status'] . '">';
                            echo '<td>#' . $presc['id'] . '</td>';
                            echo '<td><strong>' . htmlspecialchars($presc['patient_name']) . '</strong><br>';
                            echo '<small>' . htmlspecialchars($presc['patient_email']) . '</small></td>';
                            echo '<td>' . htmlspecialchars($presc['patient_phone']) . '<br>';
                            echo '<small>' . htmlspecialchars(substr($presc['patient_address'], 0, 30)) . '...</small></td>';
                            echo '<td><a href="../uploads/prescriptions/' . htmlspecialchars($presc['prescription_file']) . '" target="_blank" class="btn btn-sm btn-success">📄 View File</a></td>';
                            echo '<td>' . date('M d, Y h:i A', strtotime($presc['created_at'])) . '</td>';
                            echo '<td><span class="badge ' . $badge_class . '">' . ucfirst($presc['status']) . '</span></td>';
                            echo '<td>';
                            
                            if ($presc['status'] == 'pending') {
                                echo '<button class="btn btn-sm btn-primary" onclick="confirmPrescription(' . $presc['id'] . ', ' . $presc['patient_id'] . ', \'' . htmlspecialchars($presc['patient_name']) . '\', \'' . htmlspecialchars($presc['patient_email']) . '\')">✅ Confirm</button> ';
                            } elseif ($presc['status'] == 'confirmed') {
                                echo '<button class="btn btn-sm btn-success" onclick="completePrescription(' . $presc['id'] . ')">✓ Mark Complete</button> ';
                            }
                            
                            echo '<button class="btn btn-sm btn-info" onclick="viewDetails(' . $presc['id'] . ')">👁️ Details</button>';
                            echo '</td>';
                            echo '</tr>';
                        }
                        echo '</tbody></table>';
                    } else {
                        echo '<div style="text-align: center; padding: 50px;">';
                        echo '<div style="font-size: 64px; margin-bottom: 20px;">💊</div>';
                        echo '<h3>No prescriptions yet</h3>';
                        echo '<p>Prescriptions sent to your pharmacy will appear here.</p>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Confirm Prescription Modal -->
    <div class="modal" id="confirmModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Confirm Prescription</h2>
                <button class="close-modal" onclick="closeConfirmModal()">&times;</button>
            </div>
            <form method="POST" action="process_confirmation.php">
                <input type="hidden" name="prescription_id" id="confirm_prescription_id">
                <input type="hidden" name="patient_id" id="confirm_patient_id">
                <input type="hidden" name="patient_email" id="confirm_patient_email">
                
                <div class="alert alert-info">
                    <strong>Patient:</strong> <span id="confirm_patient_name"></span>
                </div>

                <div class="form-group">
                    <label>Confirmation Message for Patient *</label>
                    <textarea name="confirmation_message" required rows="5" placeholder="Dear Patient,

Your prescription has been reviewed and the medicines are ready for collection.

Available medicines:
- [List medicines here]

Total cost: Rs. [Amount]

Please collect from our pharmacy during working hours.

Thank you!"></textarea>
                </div>

                <div class="form-group">
                    <label>Internal Notes (Optional - Not sent to patient)</label>
                    <textarea name="internal_notes" rows="3" placeholder="Internal pharmacy notes..."></textarea>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="send_email" value="1" checked>
                        Send confirmation email to patient
                    </label>
                </div>

                <button type="submit" name="confirm_prescription" class="btn btn-glass-primary" style="width: 100%;">✅ Confirm & Notify Patient</button>
            </form>
        </div>
    </div>

    <!-- Prescription Details Modal -->
    <div class="modal" id="detailsModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Prescription Details</h2>
                <button class="close-modal" onclick="closeDetailsModal()">&times;</button>
            </div>
            <div id="prescriptionDetails">Loading...</div>
        </div>
    </div>

    <!-- Complete Prescription Form (hidden) -->
    <form id="completeForm" method="POST" action="process_confirmation.php" style="display: none;">
        <input type="hidden" name="prescription_id" id="complete_prescription_id">
        <input type="hidden" name="complete_prescription" value="1">
    </form>

    <script>
        function filterPrescriptions(status) {
            const rows = document.querySelectorAll('.presc-row');
            rows.forEach(row => {
                if (status === 'all' || row.dataset.status === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function confirmPrescription(prescId, patientId, patientName, patientEmail) {
            document.getElementById('confirm_prescription_id').value = prescId;
            document.getElementById('confirm_patient_id').value = patientId;
            document.getElementById('confirm_patient_email').value = patientEmail;
            document.getElementById('confirm_patient_name').textContent = patientName;
            document.getElementById('confirmModal').classList.add('active');
        }

        function closeConfirmModal() {
            document.getElementById('confirmModal').classList.remove('active');
        }

        function completePrescription(prescId) {
            if (confirm('Mark this prescription as completed?')) {
                document.getElementById('complete_prescription_id').value = prescId;
                document.getElementById('completeForm').submit();
            }
        }

        function viewDetails(prescId) {
            document.getElementById('detailsModal').classList.add('active');
            fetch('get_prescription_details.php?id=' + prescId)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('prescriptionDetails').innerHTML = data;
                });
        }

        function closeDetailsModal() {
            document.getElementById('detailsModal').classList.remove('active');
        }

        // Show pending prescriptions by default
        window.addEventListener('load', function() {
            filterPrescriptions('pending');
        });
    </script>
</body>
</html>
