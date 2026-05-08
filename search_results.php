<?php
session_start();
include 'includes/config.php';

// Get search parameters
$doctor_id = isset($_GET['doctor_name']) ? intval($_GET['doctor_name']) : 0; // 'doctor_name' is actually doctor ID from select
$hospital = isset($_GET['hospital']) ? sanitize($conn, $_GET['hospital']) : '';
$specialization = isset($_GET['specialization']) ? sanitize($conn, $_GET['specialization']) : '';
$appointment_date = isset($_GET['appointment_date']) ? sanitize($conn, $_GET['appointment_date']) : '';

// Build search query
if (!empty($hospital)) {
    // If searching by hospital, join with doctor_hospitals table
    $query = "SELECT DISTINCT d.*, u.email FROM doctors d 
             JOIN users u ON d.user_id = u.id 
             JOIN doctor_hospitals dh ON d.id = dh.doctor_id
             WHERE 1=1";
    $query .= " AND dh.hospital_name = '" . $conn->real_escape_string($hospital) . "'";
} else {
    $query = "SELECT d.*, u.email FROM doctors d JOIN users u ON d.user_id = u.id WHERE 1=1";
}

if ($doctor_id > 0) {
    $query .= " AND d.id = " . $doctor_id;
}
if (!empty($specialization)) {
    $query .= " AND d.specialization = '" . $conn->real_escape_string($specialization) . "'";
}

$query .= " ORDER BY d.full_name";
$doctors = $conn->query($query);

// Get doctor name for display if selected
$selected_doctor_name = '';
if ($doctor_id > 0) {
    $stmt = $conn->prepare("SELECT full_name FROM doctors WHERE id = ?");
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $selected_doctor_name = $result->fetch_assoc()['full_name'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - HealthCare Plus</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        .search-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .search-header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .doctor-result {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            gap: 20px;
            align-items: center;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .doctor-result:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        .doctor-avatar {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: white;
        }
        .doctor-info {
            flex: 1;
        }
        .doctor-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
    </style>
</head>
<body>
    <nav style="background: rgba(0,0,0,0.8); padding: 15px 0; position: sticky; top: 0; z-index: 1000;">
        <div class="container" style="display: flex; justify-content: space-between; align-items: center;">
            <a href="index.php" style="color: white; text-decoration: none; font-size: 24px; font-weight: bold;">🏥 HealthCare Plus</a>
            <div>
                <a href="index.php" style="color: white; text-decoration: none; padding: 10px 20px; margin: 0 5px;">Home</a>
                <a href="login.php" style="color: white; text-decoration: none; padding: 10px 20px; margin: 0 5px; background: #4CAF50; border-radius: 5px;">Login / Register</a>
            </div>
        </div>
    </nav>

    <div class="search-container">
        <div class="search-header">
            <h1 style="margin-bottom: 10px; color: #333;">🔍 Search Results</h1>
            <p style="color: #666; margin-bottom: 20px;">
                <?php 
                $filters = [];
                if (!empty($selected_doctor_name)) $filters[] = "Doctor: <strong>Dr. " . htmlspecialchars($selected_doctor_name) . "</strong>";
                if (!empty($hospital)) $filters[] = "Hospital: <strong>" . htmlspecialchars($hospital) . "</strong>";
                if (!empty($specialization)) $filters[] = "Specialization: <strong>" . htmlspecialchars($specialization) . "</strong>";
                if (!empty($appointment_date)) $filters[] = "Date: <strong>" . date('F d, Y', strtotime($appointment_date)) . "</strong>";
                
                if (!empty($filters)) {
                    echo "Filtering by: " . implode(" | ", $filters);
                } else {
                    echo "Showing all available doctors";
                }
                ?>
            </p>
            <a href="index.php" class="btn btn-secondary">← New Search</a>
        </div>

        <?php if ($doctors->num_rows > 0): ?>
            <div style="background: white; padding: 20px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); margin-bottom: 20px;">
                <h3 style="color: #333; margin: 0;">Found <?php echo $doctors->num_rows; ?> doctor(s)</h3>
            </div>

            <?php while ($doctor = $doctors->fetch_assoc()): 
                // Get doctor's hospitals
                $stmt_hosp = $conn->prepare("SELECT hospital_name, is_primary FROM doctor_hospitals WHERE doctor_id = ? ORDER BY is_primary DESC");
                $stmt_hosp->bind_param("i", $doctor['id']);
                $stmt_hosp->execute();
                $doc_hospitals = $stmt_hosp->get_result();
                
                $hospital_list = [];
                while ($h = $doc_hospitals->fetch_assoc()) {
                    $label = $h['is_primary'] ? ' (Main)' : '';
                    $hospital_list[] = htmlspecialchars($h['hospital_name']) . $label;
                }
                $hospital_display = !empty($hospital_list) ? implode(', ', $hospital_list) : 'Not assigned';
            ?>
                <div class="doctor-result">
                    <div class="doctor-avatar">👨‍⚕️</div>
                    <div class="doctor-info">
                        <h2 style="margin: 0 0 10px 0; color: #333;">Dr. <?php echo htmlspecialchars($doctor['full_name']); ?></h2>
                        <p style="margin: 5px 0; color: #666;"><strong>Specialization:</strong> <?php echo htmlspecialchars($doctor['specialization']); ?></p>
                        <p style="margin: 5px 0; color: #666;"><strong>Hospitals:</strong> <?php echo $hospital_display; ?></p>
                        <p style="margin: 5px 0; color: #666;"><strong>Phone:</strong> <?php echo htmlspecialchars($doctor['phone'] ?? 'N/A'); ?></p>
                        <p style="margin: 5px 0; color: #666;"><strong>Consultation Fee:</strong> Rs. <?php echo number_format($doctor['consultation_fee'], 2); ?></p>
                        
                        <?php
                        // Get doctor's schedule
                        $stmt = $conn->prepare("SELECT * FROM schedules WHERE doctor_id = ? ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')");
                        $stmt->bind_param("i", $doctor['id']);
                        $stmt->execute();
                        $schedules = $stmt->get_result();
                        
                        if ($schedules->num_rows > 0):
                        ?>
                            <details style="margin-top: 10px;">
                                <summary style="cursor: pointer; color: #667eea; font-weight: bold;">📅 View Schedule</summary>
                                <ul style="margin: 10px 0 0 20px; padding: 0;">
                                    <?php while ($schedule = $schedules->fetch_assoc()): ?>
                                        <li style="margin: 5px 0; color: #666;">
                                            <?php echo $schedule['day_of_week']; ?>: 
                                            <?php echo date('h:i A', strtotime($schedule['start_time'])); ?> - 
                                            <?php echo date('h:i A', strtotime($schedule['end_time'])); ?>
                                            at <?php echo htmlspecialchars($schedule['hospital_name']); ?>
                                        </li>
                                    <?php endwhile; ?>
                                </ul>
                            </details>
                        <?php endif; ?>
                    </div>
                    <div class="doctor-actions">
                        <a href="login.php?book_doctor=<?php echo $doctor['id']; ?>&doctor_name=<?php echo urlencode($doctor['full_name']); ?>&fee=<?php echo $doctor['consultation_fee']; ?>" class="btn btn-primary" style="white-space: nowrap; padding: 12px 24px; text-decoration: none;">
                            📅 Book Appointment
                        </a>
                        <button class="btn btn-success btn-sm" onclick="viewLocation(<?php echo $doctor['id']; ?>)">
                            📍 View Location
                        </button>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="background: white; padding: 60px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); text-align: center;">
                <div style="font-size: 64px; margin-bottom: 20px;">🔍</div>
                <h2 style="color: #333; margin-bottom: 10px;">No Doctors Found</h2>
                <p style="color: #666; margin-bottom: 30px;">Try adjusting your search filters</p>
                <a href="index.php" class="btn btn-primary">← Back to Search</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function viewLocation(doctorId) {
            window.open('view_doctor_location.php?doctor_id=' + doctorId, '_blank');
        }
    </script>
</body>
</html>
