<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'patient') {
    header("Location: ../login.php"); exit();
}
include '../includes/config.php';

$s = $conn->prepare("SELECT id FROM patients WHERE user_id = ?");
$s->bind_param("i", $_SESSION['user_id']); $s->execute();
$pat = $s->get_result()->fetch_assoc(); $s->close();
if (!$pat) { header("Location: ../logout.php"); exit(); }

// Fetch all doctors
$doctors = $conn->query("SELECT * FROM doctors ORDER BY full_name");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Doctors - HealthCare Plus</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="patient-theme">

<nav class="navbar navbar-glass navbar-expand-lg sticky-top">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="dashboard.php">🏥 HealthCare Plus</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="my_appointments.php">Appointments</a></li>
                <li class="nav-item"><a class="nav-link" href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="dashboard-container">
    <div class="dashboard-container">
        <div >
            <div class="sidebar">
                <ul class="nav flex-column">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">📊 Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link active" href="search_doctors.php">🔍 Search</a></li>
                    <li class="nav-item"><a class="nav-link" href="my_appointments.php">📅 Appointments</a></li>
                    <li class="nav-item"><a class="nav-link" href="doctor_location.php">📍 Locations</a></li>
                    <li class="nav-item"><a class="nav-link" href="messages.php">💬 Messages</a></li>
                    <li class="nav-item"><a class="nav-link" href="lab_reports.php">📋 Reports</a></li>
                    <li class="nav-item"><a class="nav-link" href="prescriptions.php">💊 Prescriptions</a></li>
                    <li class="nav-item"><a class="nav-link" href="profile.php">👤 Profile</a></li>
                </ul>
            </div>
        </div>

        <div >
            <div class="page-header-glass mb-4">
                <h1>🔍 Search Doctors</h1>
                <p>Find and book appointments with top doctors</p>
            </div>

            <div class="row g-3">
                <?php while($doctor = $doctors->fetch_assoc()): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="card">
                        <div class="mb-3">
                            <h5 class="text-white mb-1">Dr. <?php echo htmlspecialchars($doctor['full_name']); ?></h5>
                            <p class="text-white opacity-75 mb-0"><?php echo htmlspecialchars($doctor['specialization']); ?></p>
                        </div>
                        <div class="mb-3">
                            <p class="text-white mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($doctor['phone'] ?? 'N/A'); ?></p>
                            <p class="text-white mb-0"><strong>Fee:</strong> Rs. <?php echo number_format($doctor['consultation_fee'], 2); ?></p>
                        </div>
                        <a href="book_appointment.php?doctor_id=<?php echo $doctor['id']; ?>" class="btn btn-glass-primary w-100">
                            📅 Book Appointment
                        </a>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
