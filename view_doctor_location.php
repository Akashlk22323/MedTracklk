<?php
include 'includes/config.php';

$doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;

if ($doctor_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM doctors WHERE id = ?");
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $doctor = $stmt->get_result()->fetch_assoc();
} else {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Doctor Location - <?php echo htmlspecialchars($doctor['full_name']); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        #map { height: 500px; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); }
    </style>
</head>
<body>
    <div style="max-width: 1000px; margin: 0 auto;">
        <div style="background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); margin-bottom: 20px;">
            <h1 style="margin-bottom: 10px;">📍 Dr. <?php echo htmlspecialchars($doctor['full_name']); ?></h1>
            <p style="color: #666; margin-bottom: 20px;"><?php echo htmlspecialchars($doctor['specialization']); ?></p>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px;">
                <div>
                    <strong>Current Hospital:</strong><br>
                    <?php echo htmlspecialchars($doctor['current_hospital'] ?? 'Not updated'); ?>
                </div>
                <div>
                    <strong>Next Hospital:</strong><br>
                    <?php echo htmlspecialchars($doctor['next_hospital'] ?? 'Not set'); ?>
                </div>
                <div>
                    <strong>Ongoing Number:</strong><br>
                    <span style="font-size: 32px; color: #2e7d32; font-weight: bold;">#<?php echo $doctor['ongoing_number']; ?></span>
                </div>
            </div>

            <div style="background: #e3f2fd; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <p style="margin: 0; color: #1565c0;">
                    ℹ️ Each appointment slot is 45 minutes. The ongoing number shows which patient is currently being attended.
                </p>
            </div>

            <div id="map"></div>

            <div style="margin-top: 20px; text-align: center;">
                <a href="javascript:history.back()" class="btn btn-secondary">← Back to Search</a>
                <a href="index.php" class="btn btn-primary">🏠 Home</a>
            </div>
        </div>
    </div>

    <script>
        const lat = <?php echo $doctor['current_latitude'] ?? 6.9271; ?>;
        const lng = <?php echo $doctor['current_longitude'] ?? 79.8612; ?>;
        
        const map = L.map('map').setView([lat, lng], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);

        const marker = L.marker([lat, lng]).addTo(map);
        marker.bindPopup('<b>Dr. <?php echo htmlspecialchars($doctor['full_name']); ?></b><br><?php echo htmlspecialchars($doctor['current_hospital'] ?? 'Current Location'); ?>').openPopup();
    </script>
</body>
</html>
