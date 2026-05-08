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

// Handle status update
if (isset($_POST['update_status'])) {
    $appt_id = intval($_POST['appointment_id']);
    $new_status = sanitize($conn, $_POST['status']);
    
    $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ? AND doctor_id = ?");
    $stmt->bind_param("sii", $new_status, $appt_id, $doctor['id']);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Appointment status updated!";
    }
    header("Location: appointments.php");
    exit();
}

// Get sorting parameters
$sort_by = isset($_GET['sort']) ? sanitize($conn, $_GET['sort']) : 'date';
$order = isset($_GET['order']) ? sanitize($conn, $_GET['order']) : 'desc';
$hospital_filter = isset($_GET['hospital']) ? sanitize($conn, $_GET['hospital']) : '';
$date_filter = isset($_GET['date']) ? sanitize($conn, $_GET['date']) : '';
$status_filter = isset($_GET['status']) ? sanitize($conn, $_GET['status']) : '';

// Build query
$query = "SELECT a.*, p.full_name as patient_name, p.phone, p.gender, p.date_of_birth
          FROM appointments a 
          JOIN patients p ON a.patient_id = p.id 
          WHERE a.doctor_id = ?";

$params = [$doctor['id']];
$types = 'i';

if (!empty($hospital_filter)) {
    $query .= " AND a.hospital_name = ?";
    $params[] = $hospital_filter;
    $types .= 's';
}

if (!empty($date_filter)) {
    $query .= " AND a.appointment_date = ?";
    $params[] = $date_filter;
    $types .= 's';
}

if (!empty($status_filter)) {
    $query .= " AND a.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

// Add sorting
switch($sort_by) {
    case 'hospital':
        $query .= " ORDER BY a.hospital_name " . ($order == 'asc' ? 'ASC' : 'DESC') . ", a.appointment_date DESC";
        break;
    case 'date':
        $query .= " ORDER BY a.appointment_date " . ($order == 'asc' ? 'ASC' : 'DESC') . ", a.appointment_time " . ($order == 'asc' ? 'ASC' : 'DESC');
        break;
    case 'time':
        $query .= " ORDER BY a.appointment_time " . ($order == 'asc' ? 'ASC' : 'DESC');
        break;
    default:
        $query .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";
}

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$appointments = $stmt->get_result();

// Get unique hospitals for filter
$hospital_stmt = $conn->prepare("SELECT DISTINCT hospital_name FROM appointments WHERE doctor_id = ? ORDER BY hospital_name");
$hospital_stmt->bind_param("i", $doctor['id']);
$hospital_stmt->execute();
$hospitals = $hospital_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Appointments - Doctor Panel</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .filter-panel {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .filter-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        .filter-field {
            display: flex;
            flex-direction: column;
        }
        .filter-field label {
            font-weight: bold;
            margin-bottom: 5px;
            color: #2c3e50;
        }
        .filter-field select,
        .filter-field input {
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        .sort-controls {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .sort-btn {
            padding: 8px 15px;
            border: 2px solid #ddd;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .sort-btn:hover {
            border-color: #2e7d32;
            background: #f1f8f4;
        }
        .sort-btn.active {
            background: #2e7d32;
            color: white;
            border-color: #2e7d32;
        }
        .appointment-group {
            margin-bottom: 30px;
        }
        .group-header {
            background: linear-gradient(135deg, #2e7d32 0%, #43a047 100%);
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 18px;
            font-weight: bold;
        }
    </style>
</head>
<body class="doctor-theme">
    <nav class="navbar">
        <div class="main-content">
            <a href="dashboard.php" class="navbar-brand">🏥 Doctor Panel</a>
            <ul class="navbar-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="appointments.php" style="background: rgba(255,255,255,0.2);">Appointments</a></li>
                <li><a href="messages.php">Messages</a></li>
                <li><a href="update_location.php">Update Location</a></li>
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
                <li><a href="appointments.php" class="active">📅 My Appointments</a></li>
                <li><a href="update_location.php">📍 Update Location</a></li>
                <li><a href="messages.php">💬 Patient Messages</a></li>
                <li><a href="profile.php">👤 My Profile</a></li>
            </ul>
        </div></div>

        <main >
            <div class="page-header">
                <h1>My Appointments</h1>
                <p>View and manage your patient appointments</p>
            </div>

            <?php
            if (isset($_SESSION['success'])) {
                echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
                unset($_SESSION['success']);
            }
            ?>

            <!-- Filter Panel -->
            <div class="filter-panel">
                <h3 style="margin-top: 0;">🔍 Filter & Sort Appointments</h3>
                
                <form method="GET" action="appointments.php">
                    <div class="filter-group">
                        <div class="filter-field">
                            <label>🏥 Hospital</label>
                            <select name="hospital" onchange="this.form.submit()">
                                <option value="">All Hospitals</option>
                                <?php
                                $hospitals->data_seek(0);
                                while ($h = $hospitals->fetch_assoc()) {
                                    $selected = ($hospital_filter == $h['hospital_name']) ? 'selected' : '';
                                    echo '<option value="' . htmlspecialchars($h['hospital_name']) . '" ' . $selected . '>' . htmlspecialchars($h['hospital_name']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="filter-field">
                            <label>📅 Date</label>
                            <input type="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>" onchange="this.form.submit()">
                        </div>

                        <div class="filter-field">
                            <label>📊 Status</label>
                            <select name="status" onchange="this.form.submit()">
                                <option value="">All Status</option>
                                <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                    </div>

                    <div style="margin-top: 15px;">
                        <label style="font-weight: bold; margin-bottom: 10px; display: block;">Sort By:</label>
                        <div class="sort-controls">
                            <button type="submit" name="sort" value="date" class="sort-btn <?php echo $sort_by == 'date' ? 'active' : ''; ?>">
                                📅 Date <?php echo $sort_by == 'date' ? ($order == 'asc' ? '↑' : '↓') : ''; ?>
                            </button>
                            <button type="submit" name="sort" value="hospital" class="sort-btn <?php echo $sort_by == 'hospital' ? 'active' : ''; ?>">
                                🏥 Hospital <?php echo $sort_by == 'hospital' ? ($order == 'asc' ? '↑' : '↓') : ''; ?>
                            </button>
                            <button type="submit" name="sort" value="time" class="sort-btn <?php echo $sort_by == 'time' ? 'active' : ''; ?>">
                                ⏰ Time <?php echo $sort_by == 'time' ? ($order == 'asc' ? '↑' : '↓') : ''; ?>
                            </button>
                            
                            <input type="hidden" name="order" value="<?php echo $order == 'asc' ? 'desc' : 'asc'; ?>">
                            <?php if (!empty($hospital_filter)) echo '<input type="hidden" name="hospital" value="' . htmlspecialchars($hospital_filter) . '">'; ?>
                            <?php if (!empty($date_filter)) echo '<input type="hidden" name="date" value="' . htmlspecialchars($date_filter) . '">'; ?>
                            <?php if (!empty($status_filter)) echo '<input type="hidden" name="status" value="' . htmlspecialchars($status_filter) . '">'; ?>
                            
                            <a href="appointments.php" class="sort-btn" style="text-decoration: none; background: #e74c3c; color: white; border-color: #e74c3c;">
                                🔄 Clear Filters
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Appointments List -->
            <div class="card">
                <div class="card-header">
                    Appointments List 
                    <?php if ($appointments->num_rows > 0): ?>
                        <span style="float: right; background: #2e7d32; color: white; padding: 5px 15px; border-radius: 20px; font-size: 14px;">
                            <?php echo $appointments->num_rows; ?> Total
                        </span>
                    <?php endif; ?>
                </div>
                <div class="table-container">
                    <?php
                    if ($appointments->num_rows > 0) {
                        // Group by hospital if sorted by hospital, or by date if sorted by date
                        if ($sort_by == 'hospital') {
                            $appointments_array = [];
                            while ($appt = $appointments->fetch_assoc()) {
                                $appointments_array[$appt['hospital_name']][] = $appt;
                            }
                            
                            foreach ($appointments_array as $hospital => $appts) {
                                echo '<div class="appointment-group">';
                                echo '<div class="group-header">🏥 ' . htmlspecialchars($hospital) . ' (' . count($appts) . ' appointments)</div>';
                                displayAppointmentsTable($appts);
                                echo '</div>';
                            }
                        } elseif ($sort_by == 'date') {
                            $appointments_array = [];
                            $appointments->data_seek(0);
                            while ($appt = $appointments->fetch_assoc()) {
                                $appointments_array[$appt['appointment_date']][] = $appt;
                            }
                            
                            foreach ($appointments_array as $date => $appts) {
                                echo '<div class="appointment-group">';
                                echo '<div class="group-header">📅 ' . date('l, F d, Y', strtotime($date)) . ' (' . count($appts) . ' appointments)</div>';
                                displayAppointmentsTable($appts);
                                echo '</div>';
                            }
                        } else {
                            $appointments->data_seek(0);
                            $all_appts = [];
                            while ($appt = $appointments->fetch_assoc()) {
                                $all_appts[] = $appt;
                            }
                            displayAppointmentsTable($all_appts);
                        }
                    } else {
                        echo '<p style="text-align: center; padding: 40px; color: #999;">No appointments found matching your filters.</p>';
                    }

                    function displayAppointmentsTable($appts) {
                        echo '<table>';
                        echo '<thead><tr><th>Booking #</th><th>Patient</th><th>Age/Gender</th><th>Phone</th><th>Date</th><th>Time</th><th>Hospital</th><th>Status</th><th>Actions</th></tr></thead>';
                        echo '<tbody>';
                        
                        foreach ($appts as $appt) {
                            $age = date_diff(date_create($appt['date_of_birth']), date_create('today'))->y;
                            $badge_class = 'badge-success';
                            if ($appt['status'] == 'cancelled') $badge_class = 'badge-danger';
                            elseif ($appt['status'] == 'completed') $badge_class = 'badge-info';
                            elseif ($appt['status'] == 'pending') $badge_class = 'badge-warning';
                            
                            echo '<tr>';
                            echo '<td><strong>#' . $appt['booking_number'] . '</strong></td>';
                            echo '<td>' . htmlspecialchars($appt['patient_name']) . '</td>';
                            echo '<td>' . $age . 'Y / ' . htmlspecialchars($appt['gender']) . '</td>';
                            echo '<td>' . htmlspecialchars($appt['phone']) . '</td>';
                            echo '<td>' . date('M d, Y', strtotime($appt['appointment_date'])) . '</td>';
                            echo '<td><strong>' . date('h:i A', strtotime($appt['appointment_time'])) . '</strong></td>';
                            echo '<td>🏥 ' . htmlspecialchars($appt['hospital_name']) . '</td>';
                            echo '<td><span class="badge ' . $badge_class . '">' . ucfirst($appt['status']) . '</span></td>';
                            echo '<td>';
                            if ($appt['status'] == 'confirmed' || $appt['status'] == 'pending') {
                                echo '<form method="POST" style="display: inline;">';
                                echo '<input type="hidden" name="appointment_id" value="' . $appt['id'] . '">';
                                echo '<input type="hidden" name="status" value="completed">';
                                echo '<button type="submit" name="update_status" class="btn btn-sm btn-success">✓ Complete</button>';
                                echo '</form>';
                                echo ' <a href="../video_room.php" class="btn btn-sm" style="background:linear-gradient(135deg,#1565c0,#42a5f5);color:white;text-decoration:none;">📹 Video</a>';
                            }
                            if (!empty($appt['notes'])) {
                                echo ' <button class="btn btn-sm btn-info" onclick="alert(\'Notes: ' . htmlspecialchars(addslashes($appt['notes'])) . '\')">📝 Notes</button>';
                            }
                            echo '</td>';
                            echo '</tr>';
                        }
                        
                        echo '</tbody></table>';
                    }
                    ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
