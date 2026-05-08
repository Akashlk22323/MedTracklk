<?php
session_start();
include 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($conn, $_POST['username']);
    $password = $_POST['password'];
    $remember_me = isset($_POST['remember_me']) ? true : false;
    $selected_role = isset($_POST['role']) ? sanitize($conn, $_POST['role']) : '';
    
    $redirect_doctor_id = isset($_POST['redirect_doctor_id']) ? intval($_POST['redirect_doctor_id']) : 0;
    $redirect_doctor_name = isset($_POST['redirect_doctor_name']) ? sanitize($conn, $_POST['redirect_doctor_name']) : '';
    $redirect_fee = isset($_POST['redirect_fee']) ? floatval($_POST['redirect_fee']) : 0;

    // Validate role selection
    if (empty($selected_role)) {
        $_SESSION['error'] = "Please select your role!";
        header("Location: search_results.php?" . http_build_query($_GET));
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
                $_SESSION['error'] = "Invalid role! You are registered as " . ucfirst($user['role']) . ".";
                header("Location: search_results.php?" . http_build_query($_GET));
                exit();
            }

            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Remember Me functionality
            if ($remember_me) {
                // Create secure token
                $token = bin2hex(random_bytes(32));
                $user_id = $user['id'];
                $expiry = time() + (30 * 24 * 60 * 60); // 30 days
                
                // Store token in cookie (expires in 30 days)
                setcookie('remember_token', $token, $expiry, '/', '', false, true); // httponly for security
                setcookie('remember_user', $user_id, $expiry, '/', '', false, true);
                
                // In production, you'd store the token hash in database for verification
                // For this demo, we're using cookies directly
            }

            // Log activity
            logActivity($conn, $user['id'], 'Login', 'User logged in via quick login as ' . $user['role']);

            // Redirect based on role and booking intent
            if ($user['role'] == 'patient' && $redirect_doctor_id > 0) {
                // Patient trying to book - redirect to booking page with doctor pre-selected
                $_SESSION['booking_doctor_id'] = $redirect_doctor_id;
                $_SESSION['booking_doctor_name'] = $redirect_doctor_name;
                $_SESSION['booking_fee'] = $redirect_fee;
                header("Location: patient/quick_booking.php");
                exit();
            } else {
                // Normal role-based redirect
                switch ($user['role']) {
                    case 'admin':
                        header("Location: admin/dashboard.php");
                        break;
                    case 'doctor':
                        header("Location: doctor/dashboard.php");
                        break;
                    case 'patient':
                        header("Location: patient/dashboard.php");
                        break;
                    case 'pharmacist':
                        header("Location: pharmacist/dashboard.php");
                        break;
                    default:
                        header("Location: login.php");
                }
                exit();
            }
        } else {
            $_SESSION['error'] = "Invalid password!";
            header("Location: search_results.php?" . http_build_query($_GET));
            exit();
        }
    } else {
        $_SESSION['error'] = "No " . ucfirst($selected_role) . " account found! Please check your role selection.";
        
        // Redirect to registration if booking a doctor
        if ($redirect_doctor_id > 0) {
            header("Location: login.php?register=1&doctor_id=" . $redirect_doctor_id);
        } else {
            header("Location: login.php");
        }
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
?>
