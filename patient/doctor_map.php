<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'patient') {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

$doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;

if ($doctor_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM doctors WHERE id = ?");
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $doctor = $stmt->get_result()->fetch_assoc();
} else {
    header("Location: search_doctors.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Doctor Location - <?php echo htmlspecialchars($doctor['full_name']); ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        #map { height: 500px; border-radius: 10px; margin: 20px 0; box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .info-card { background: white; padding: 20px; border-radius: 10px; margin: 10px 0; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .status-badge { display: inline-block; padding: 8px 15px; border-radius: 20px; font-weight: bold; }
        .status-current { background: #4caf50; color: white; }
        .status-next { background: #ff9800; color: white; }
    </style>
</head>
<body class="patient-theme">
    <nav class="navbar">
        <div class="main-content">
            <a href="dashboard.php" class="navbar-brand">🏥 HealthCare Plus</a>
            <ul class="navbar-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="search_doctors.php">Search Doctors</a></li>
                <li><a href="my_appointments.php">My Appointments</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div style="max-width: 1200px; margin: 30px auto; padding: 20px;">
        <div class="card">
            <div class="card-header">
                <h2 style="margin: 0;">📍 Dr. <?php echo htmlspecialchars($doctor['full_name']); ?> - Live Location</h2>
                <p style="margin: 5px 0 0 0; color: #666;"><?php echo htmlspecialchars($doctor['specialization']); ?></p>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">
                <div class="info-card">
                    <h3 style="margin: 0 0 10px 0; color: #4caf50;">🏥 Current Hospital</h3>
                    <p style="font-size: 18px; font-weight: bold; margin: 0;">
                        <?php echo htmlspecialchars($doctor['current_hospital'] ?? 'Not updated'); ?>
                    </p>
                </div>

                <div class="info-card">
                    <h3 style="margin: 0 0 10px 0; color: #ff9800;">➡️ Next Hospital</h3>
                    <p style="font-size: 18px; font-weight: bold; margin: 0;">
                        <?php echo htmlspecialchars($doctor['next_hospital'] ?? 'Not set'); ?>
                    </p>
                </div>

                <div class="info-card">
                    <h3 style="margin: 0 0 10px 0; color: #2196f3;">📊 Queue Status</h3>
                    <p style="font-size: 36px; font-weight: bold; margin: 0; color: #2196f3;">
                        #<?php echo $doctor['ongoing_number']; ?>
                    </p>
                    <small>Currently attending patient</small>
                </div>
            </div>

            <!-- OpenStreetMap -->
            <div id="map"></div>

            <div style="background: #fff9c4; padding: 15px; border-radius: 8px; margin: 20px 0;">
                <p style="margin: 0;">
                    ℹ️ <strong>Live tracking:</strong> The map shows the doctor's current and next hospital locations. 
                    Queue number indicates which patient is currently being attended. Each slot is 45 minutes.
                </p>
            </div>

            <!-- Weekly Schedule -->
            <h3>📅 Weekly Schedule</h3>
            <div class="table-container">
                <?php
                $stmt = $conn->prepare("SELECT * FROM schedules WHERE doctor_id = ? ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), start_time");
                $stmt->bind_param("i", $doctor_id);
                $stmt->execute();
                $schedules = $stmt->get_result();

                if ($schedules->num_rows > 0) {
                    echo '<table>';
                    echo '<thead><tr><th>Day</th><th>Time</th><th>Hospital</th><th>Max Patients</th></tr></thead>';
                    echo '<tbody>';
                    while ($schedule = $schedules->fetch_assoc()) {
                        echo '<tr>';
                        echo '<td><strong>' . $schedule['day_of_week'] . '</strong></td>';
                        echo '<td>' . date('h:i A', strtotime($schedule['start_time'])) . ' - ' . date('h:i A', strtotime($schedule['end_time'])) . '</td>';
                        echo '<td>🏥 ' . htmlspecialchars($schedule['hospital_name']) . '</td>';
                        echo '<td>' . $schedule['max_patients'] . ' patients</td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table>';
                } else {
                    echo '<p style="text-align: center; color: #999; padding: 30px;">No schedule available</p>';
                }
                ?>
            </div>

            <div style="margin-top: 30px; text-align: center;">
                <a href="book_appointment.php?doctor_id=<?php echo $doctor['id']; ?>" class="btn btn-glass-primary" style="padding: 15px 40px; font-size: 18px;">
                    📅 Book Appointment
                </a>
            </div>
        </div>
    </div>

    <script>
        // Initialize map centered on Sri Lanka
        const map = L.map('map').setView([6.9271, 79.8612], 11);

        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);

        // Function to add hospital marker
        function addHospitalMarker(lat, lng, name, type, color) {
            const icon = L.divIcon({
                className: 'custom-marker',
                html: `<div style="background: ${color}; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; border: 3px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);">${type === 'current' ? '📍' : '➡️'}</div>`,
                iconSize: [40, 40]
            });

            const marker = L.marker([lat, lng], { icon: icon }).addTo(map);
            marker.bindPopup(`<strong>${type === 'current' ? 'Current Location' : 'Next Destination'}</strong><br>${name}`);
            
            if (type === 'current') {
                marker.openPopup();
            }
            
            return marker;
        }

        // Load current hospital location
        <?php
        if (!empty($doctor['current_hospital'])) {
            $stmt = $conn->prepare("SELECT latitude, longitude FROM hospitals WHERE name = ?");
            $stmt->bind_param("s", $doctor['current_hospital']);
            $stmt->execute();
            $current_hosp = $stmt->get_result()->fetch_assoc();
            
            if ($current_hosp && $current_hosp['latitude'] && $current_hosp['longitude']) {
                echo "const currentMarker = addHospitalMarker({$current_hosp['latitude']}, {$current_hosp['longitude']}, '" . addslashes($doctor['current_hospital']) . "', 'current', '#4caf50');\n";
                echo "map.setView([{$current_hosp['latitude']}, {$current_hosp['longitude']}], 13);\n";
            }
        }
        ?>

        // Load next hospital location
        <?php
        if (!empty($doctor['next_hospital'])) {
            $stmt = $conn->prepare("SELECT latitude, longitude FROM hospitals WHERE name = ?");
            $stmt->bind_param("s", $doctor['next_hospital']);
            $stmt->execute();
            $next_hosp = $stmt->get_result()->fetch_assoc();
            
            if ($next_hosp && $next_hosp['latitude'] && $next_hosp['longitude']) {
                echo "const nextMarker = addHospitalMarker({$next_hosp['latitude']}, {$next_hosp['longitude']}, '" . addslashes($doctor['next_hospital']) . "', 'next', '#ff9800');\n";
                
                // Draw route between current and next
                if ($current_hosp && $current_hosp['latitude'] && $current_hosp['longitude']) {
                    echo "const route = L.polyline([[{$current_hosp['latitude']}, {$current_hosp['longitude']}], [{$next_hosp['latitude']}, {$next_hosp['longitude']}]], {color: '#ff9800', weight: 3, dashArray: '10, 10'}).addTo(map);\n";
                }
            }
        }
        ?>
    </script>
</body>
</html>
