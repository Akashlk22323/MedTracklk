<?php
session_start();
include 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = sanitize($conn, $_POST['full_name']);
    $username = sanitize($conn, $_POST['username']);
    $email = sanitize($conn, $_POST['email']);
    $password = md5($_POST['password']);
    $phone = sanitize($conn, $_POST['phone']);
    $gender = sanitize($conn, $_POST['gender']);
    $date_of_birth = sanitize($conn, $_POST['date_of_birth']);
    $address = sanitize($conn, $_POST['address']);

    // Check if username already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['error'] = "Username or email already exists!";
        header("Location: login.php");
        exit();
    }

    // Insert user
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'patient')");
    $stmt->bind_param("sss", $username, $email, $password);
    
    if ($stmt->execute()) {
        $user_id = $conn->insert_id;

        // Insert patient details
        $stmt = $conn->prepare("INSERT INTO patients (user_id, full_name, phone, address, date_of_birth, gender) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssss", $user_id, $full_name, $phone, $address, $date_of_birth, $gender);
        
        if ($stmt->execute()) {
            // Log activity
            logActivity($conn, $user_id, 'Registration', 'New patient registered');

            // Check if user was trying to book a doctor
            $doctor_id = isset($_POST['doctor_id']) ? intval($_POST['doctor_id']) : 0;
            
            if ($doctor_id > 0) {
                // Auto-login the user
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = 'patient';
                
                // Set booking session
                $_SESSION['booking_doctor_id'] = $doctor_id;
                $_SESSION['success'] = "Account created! Let's book your appointment.";
                
                header("Location: patient/quick_booking.php");
                exit();
            } else {
                $_SESSION['success'] = "Registration successful! Please login.";
                header("Location: login.php");
                exit();
            }
        } else {
            $_SESSION['error'] = "Error creating patient profile!";
            header("Location: login.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Error during registration!";
        header("Location: login.php");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}
?>
