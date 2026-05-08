<?php
session_start();
include 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($conn, $_POST['username']);
    $password = $_POST['password'];
    $selected_role = isset($_POST['role']) ? sanitize($conn, $_POST['role']) : '';

    // Validate role selection
    if (empty($selected_role)) {
        $_SESSION['error'] = "Please select your role!";
        header("Location: login.php");
        exit();
    }

    // Check if user exists with the selected role
    $stmt = $conn->prepare("SELECT id, username, email, password, role, status FROM users WHERE (username = ? OR email = ?) AND role = ? AND status = 'active'");
    $stmt->bind_param("sss", $username, $username, $selected_role);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (md5($password) == $user['password']) {
            // Double-check role matches
            if ($user['role'] !== $selected_role) {
                $_SESSION['error'] = "Invalid role selected! You are registered as " . ucfirst($user['role']) . ".";
                header("Location: login.php");
                exit();
            }

            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Log activity
            logActivity($conn, $user['id'], 'Login', 'User logged in as ' . $user['role']);

            // Check for booking parameters from landing page search
            $book_doctor = isset($_GET['book_doctor']) ? intval($_GET['book_doctor']) : 0;
            $doctor_name = isset($_GET['doctor_name']) ? sanitize($conn, $_GET['doctor_name']) : '';
            $fee = isset($_GET['fee']) ? floatval($_GET['fee']) : 0;

            // Check if this is a booking redirect (patient only)
            if ($user['role'] == 'patient' && $book_doctor > 0) {
                // Store booking info in session
                $_SESSION['booking_doctor_id'] = $book_doctor;
                $_SESSION['booking_doctor_name'] = $doctor_name;
                $_SESSION['booking_fee'] = $fee;
                // Redirect to quick booking
                header("Location: patient/quick_booking.php");
                exit();
            } elseif ($user['role'] == 'patient') {
                // Normal patient login
                header("Location: patient/dashboard.php");
            } else {
                // Redirect based on role
                switch ($user['role']) {
                    case 'admin':
                        header("Location: admin/dashboard.php");
                        break;
                    case 'doctor':
                        header("Location: doctor/dashboard.php");
                        break;
                    case 'pharmacist':
                        header("Location: pharmacist/dashboard.php");
                        break;
                    default:
                        header("Location: login.php");
                }
            }
            exit();
        } else {
            $_SESSION['error'] = "Invalid password!";
            header("Location: login.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "No " . ucfirst($selected_role) . " account found with these credentials!";
        header("Location: login.php");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}
?>
