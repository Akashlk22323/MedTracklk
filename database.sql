-- Doctor Appointment System Database Schema
-- Drop database if exists and create new
DROP DATABASE IF EXISTS doctor_appointment_system;
CREATE DATABASE doctor_appointment_system;
USE doctor_appointment_system;

-- Users table (for all user types)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'doctor', 'patient', 'pharmacist') NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Hospitals table (Pre-populated with Sri Lankan hospitals)
CREATE TABLE hospitals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL UNIQUE,
    address TEXT,
    city VARCHAR(100),
    district VARCHAR(100),
    phone VARCHAR(20),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Patient details
CREATE TABLE patients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    date_of_birth DATE,
    gender ENUM('Male', 'Female', 'Other'),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Doctor details (Updated - removed lat/lng, simplified hospital)
CREATE TABLE doctors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    specialization VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    license_number VARCHAR(50),
    consultation_fee DECIMAL(10,2) DEFAULT 0.00,
    current_hospital VARCHAR(200),
    accepts_video_call TINYINT(1) DEFAULT 0,
    next_hospital VARCHAR(200),
    ongoing_number INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Doctor-Hospital association (Many-to-Many)
CREATE TABLE doctor_hospitals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    doctor_id INT NOT NULL,
    hospital_name VARCHAR(200) NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    UNIQUE KEY unique_doctor_hospital (doctor_id, hospital_name)
);

-- Pharmacist details
CREATE TABLE pharmacists (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    pharmacy_name VARCHAR(200) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Admin details
CREATE TABLE admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    phone VARCHAR(20),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Doctor schedules (Enhanced with specific dates)
CREATE TABLE schedules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    doctor_id INT NOT NULL,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    hospital_name VARCHAR(200) NOT NULL,
    max_patients INT DEFAULT 20,
    slot_duration INT DEFAULT 45 COMMENT 'Duration in minutes',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    INDEX idx_doctor_day (doctor_id, day_of_week)
);

-- Appointments
CREATE TABLE password_resets (
    email      VARCHAR(191) NOT NULL,
    token      VARCHAR(64)  NOT NULL,
    expires_at DATETIME     NOT NULL,
    PRIMARY KEY (email)
);

CREATE TABLE appointments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    hospital_name VARCHAR(200) NOT NULL,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'confirmed',
    payment_status ENUM('pending', 'paid') DEFAULT 'paid',
    payment_method ENUM('card', 'pay_at_hospital') DEFAULT 'card',
    appointment_type ENUM('at_hospital','video_call') DEFAULT 'at_hospital',
    payment_amount DECIMAL(10,2),
    booking_number INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
);

-- Feedbacks
CREATE TABLE feedbacks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    appointment_id INT NOT NULL,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
);

-- Messages (Patient-Doctor chat)
CREATE TABLE messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    chat_type ENUM('appointment','general') DEFAULT 'general',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Lab Reports
CREATE TABLE lab_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    doctor_id INT,
    report_name VARCHAR(200) NOT NULL,
    report_file VARCHAR(255) NOT NULL,
    test_date DATE,
    notes TEXT,
    shared_by ENUM('patient', 'doctor', 'admin') DEFAULT 'patient',
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE SET NULL
);

-- Prescriptions
CREATE TABLE prescriptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    pharmacist_id INT,
    prescription_file VARCHAR(255) NOT NULL,
    status ENUM('pending', 'confirmed', 'completed') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (pharmacist_id) REFERENCES pharmacists(id) ON DELETE SET NULL
);

-- Blogs
CREATE TABLE blogs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    author VARCHAR(100),
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Activity Logs
CREATE TABLE activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    activity_type VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ========================================
-- SAMPLE DATA
-- ========================================

-- Insert Sri Lankan Hospitals (Colombo and Galle areas)
INSERT INTO hospitals (name, address, city, district, phone, latitude, longitude) VALUES
-- Colombo District
('Colombo General Hospital', 'Regent Street, Colombo 08', 'Colombo', 'Colombo', '011-2691111', 6.927079, 79.861244),
('National Hospital of Sri Lanka', 'Regent Street, Colombo 07', 'Colombo', 'Colombo', '011-2691111', 6.917536, 79.861050),
('Asiri Central Hospital', 'No.114, Norris Canal Road, Colombo 10', 'Colombo', 'Colombo', '011-4665500', 6.918187, 79.877319),
('Asiri Surgical Hospital', 'No.21, Kirimandala Mawatha, Colombo 05', 'Colombo', 'Colombo', '011-4524400', 6.890080, 79.858780),
('Nawaloka Hospital', 'No.23, Sri Saugathhodaya Mawatha, Colombo 02', 'Colombo', 'Colombo', '011-5577111', 6.925960, 79.854820),
('Durdans Hospital', 'No.03, Alfred Place, Colombo 03', 'Colombo', 'Colombo', '011-2140000', 6.907260, 79.851730),
('Lanka Hospital', 'No.578, Elvitigala Mawatha, Colombo 05', 'Colombo', 'Colombo', '011-5430000', 6.885510, 79.880050),
('Hemas Hospital Wattala', 'No.389, Negombo Road, Wattala', 'Wattala', 'Gampaha', '011-7888888', 6.983190, 79.892430),
('Oasis Hospital', 'No.32, Sulaiman Terrace, Colombo 05', 'Colombo', 'Colombo', '011-2508888', 6.893760, 79.859920),

-- Galle District
('Teaching Hospital Karapitiya', 'Karapitiya, Galle', 'Galle', 'Galle', '091-2232261', 6.061420, 80.212870),
('Ruhunu Hospital Galle', 'Wakwella Road, Galle', 'Galle', 'Galle', '091-2232395', 6.053520, 80.218100),
('Hemas Hospital Thalapitiya', 'Matara Road, Thalapitiya, Galle', 'Galle', 'Galle', '091-2244444', 6.042130, 80.219850),
('Asiri Surgical Hospital Matara', 'No.92, Anagarika Dharmapala Mawatha, Matara', 'Matara', 'Matara', '041-2222271', 5.949130, 80.548720),
('District General Hospital Matara', 'Hospital Road, Matara', 'Matara', 'Matara', '041-2222261', 5.948870, 80.535290),
('Lanka Hospitals Galle', 'No.123, Wakwella Road, Galle', 'Galle', 'Galle', '091-2245678', 6.053520, 80.218100),

-- Additional Major Hospitals
('Apollo Hospital', 'No.578, Elvitigala Mawatha, Colombo 05', 'Colombo', 'Colombo', '011-5304444', 6.885510, 79.880050),
('Central Hospital', 'No.114, Norris Canal Road, Colombo 10', 'Colombo', 'Colombo', '011-5200000', 6.918187, 79.877319),
('Browns Hospital', 'Flower Road, Colombo 07', 'Colombo', 'Colombo', '011-2686868', 6.914830, 79.857410),
('Golden Key Hospital', 'No.408, Deans Road, Colombo 10', 'Colombo', 'Colombo', '011-2775100', 6.909240, 79.878650),
('Ninewells Hospital', 'Kalubowila, Dehiwala', 'Dehiwala', 'Colombo', '011-2763000', 6.856570, 79.882370);

-- Insert default admin
INSERT INTO users (username, email, password, role) VALUES 
('admin', 'admin@hospital.com', MD5('admin123'), 'admin');

INSERT INTO admins (user_id, full_name, phone) VALUES 
(1, 'System Administrator', '0771234567');

-- Sample doctors (Updated - removed lat/lng)
INSERT INTO users (username, email, password, role) VALUES 
('dr.silva', 'silva@hospital.com', MD5('doctor123'), 'doctor'),
('dr.fernando', 'fernando@hospital.com', MD5('doctor123'), 'doctor');

INSERT INTO doctors (user_id, full_name, specialization, phone, license_number, consultation_fee, current_hospital, ongoing_number) VALUES 
(2, 'Dr. Nimal Silva', 'Cardiologist', '0771234568', 'LIC001', 2500.00, 'Colombo General Hospital', 5),
(3, 'Dr. Kamala Fernando', 'Pediatrician', '0771234569', 'LIC002', 2000.00, 'Asiri Central Hospital', 3);

-- Doctor-Hospital associations (Multiple hospitals per doctor)
INSERT INTO doctor_hospitals (doctor_id, hospital_name, is_primary) VALUES
(1, 'Colombo General Hospital', TRUE),
(1, 'Nawaloka Hospital', FALSE),
(1, 'Durdans Hospital', FALSE),
(2, 'Asiri Central Hospital', TRUE),
(2, 'Asiri Surgical Hospital', FALSE),
(2, 'Lanka Hospital', FALSE);

-- Sample schedules (Enhanced with max_patients and slot_duration)
INSERT INTO schedules (doctor_id, day_of_week, start_time, end_time, hospital_name, max_patients, slot_duration) VALUES
-- Dr. Silva's schedule
(1, 'Monday', '09:00:00', '12:00:00', 'Colombo General Hospital', 20, 45),
(1, 'Monday', '14:00:00', '17:00:00', 'Nawaloka Hospital', 15, 45),
(1, 'Wednesday', '09:00:00', '12:00:00', 'Colombo General Hospital', 20, 45),
(1, 'Friday', '09:00:00', '12:00:00', 'Durdans Hospital', 18, 45),
(1, 'Friday', '15:00:00', '18:00:00', 'Colombo General Hospital', 15, 45),

-- Dr. Fernando's schedule
(2, 'Tuesday', '10:00:00', '13:00:00', 'Asiri Central Hospital', 25, 45),
(2, 'Tuesday', '15:00:00', '18:00:00', 'Lanka Hospital', 15, 45),
(2, 'Thursday', '09:00:00', '12:00:00', 'Asiri Surgical Hospital', 20, 45),
(2, 'Thursday', '14:00:00', '17:00:00', 'Asiri Central Hospital', 20, 45),
(2, 'Saturday', '09:00:00', '12:00:00', 'Lanka Hospital', 25, 45);

-- Sample pharmacist
INSERT INTO users (username, email, password, role) VALUES 
('pharma1', 'pharma1@pharmacy.com', MD5('pharma123'), 'pharmacist');

INSERT INTO pharmacists (user_id, full_name, pharmacy_name, phone, address) VALUES 
(4, 'Sunil Perera', 'City Pharmacy', '0771234570', '123 Galle Road, Colombo 03');

-- Sample blogs
INSERT INTO blogs (title, content, author, image) VALUES 
('10 Tips for a Healthy Heart', 'Maintaining a healthy heart is crucial for overall well-being. Here are 10 essential tips: 1. Exercise regularly - aim for at least 30 minutes daily. 2. Eat a balanced diet rich in fruits and vegetables. 3. Limit sodium intake. 4. Manage stress effectively. 5. Get adequate sleep. 6. Avoid smoking. 7. Limit alcohol consumption. 8. Monitor blood pressure. 9. Maintain healthy weight. 10. Regular health checkups.', 'Dr. Nimal Silva', 'heart-health.jpg'),
('Common Childhood Illnesses and Prevention', 'As parents, understanding common childhood illnesses helps in better care. Common conditions include: Common cold, Ear infections, Stomach bugs, Hand-foot-mouth disease. Prevention includes: Regular handwashing, Vaccinations on schedule, Healthy diet, Adequate sleep, Regular exercise. Always consult your pediatrician for proper diagnosis and treatment.', 'Dr. Kamala Fernando', 'child-health.jpg'),
('Understanding Diabetes Management', 'Diabetes management requires consistent effort and monitoring. Key aspects include: Blood sugar monitoring, Medication adherence, Healthy eating habits, Regular physical activity, Stress management, Regular doctor visits. With proper management, people with diabetes can lead full, healthy lives.', 'Admin', 'diabetes.jpg');

-- Sample appointments (to show revenue)
INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, hospital_name, status, payment_status, payment_amount, booking_number) VALUES
(1, 1, CURDATE(), '09:00:00', 'Colombo General Hospital', 'confirmed', 'paid', 2500.00, 1),
(1, 1, CURDATE() + INTERVAL 1 DAY, '10:30:00', 'Nawaloka Hospital', 'confirmed', 'paid', 2500.00, 2),
(1, 2, CURDATE() + INTERVAL 2 DAY, '10:00:00', 'Asiri Central Hospital', 'confirmed', 'paid', 2000.00, 3),
(1, 1, CURDATE() + INTERVAL 3 DAY, '11:15:00', 'Colombo General Hospital', 'pending', 'paid', 2500.00, 4),
(1, 2, CURDATE() + INTERVAL 4 DAY, '14:00:00', 'Lanka Hospital', 'completed', 'paid', 2000.00, 5),
(1, 1, CURDATE() + INTERVAL 5 DAY, '09:45:00', 'Durdans Hospital', 'confirmed', 'paid', 2500.00, 6),
(1, 2, CURDATE() + INTERVAL 6 DAY, '10:30:00', 'Asiri Surgical Hospital', 'confirmed', 'paid', 2000.00, 7),
(1, 1, CURDATE() + INTERVAL 7 DAY, '14:00:00', 'Colombo General Hospital', 'confirmed', 'paid', 2500.00, 8);

-- Video Consultation Rooms
CREATE TABLE video_rooms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_code VARCHAR(20) NOT NULL UNIQUE,
    doctor_id INT NOT NULL,
    patient_id INT NOT NULL,
    appointment_id INT,
    status ENUM('waiting','active','ended') DEFAULT 'waiting',
    started_at TIMESTAMP NULL,
    ended_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
);

-- WebRTC Signaling (for peer connection negotiation)
CREATE TABLE video_signals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_code VARCHAR(20) NOT NULL,
    sender_role ENUM('doctor','patient') NOT NULL,
    signal_type VARCHAR(20) NOT NULL COMMENT 'offer, answer, ice-candidate',
    signal_data LONGTEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_room (room_code),
    INDEX idx_room_type (room_code, signal_type)
);

-- ── Migration: Add appointment type support ────────────────────
ALTER TABLE appointments ADD COLUMN IF NOT EXISTS appointment_type ENUM('at_hospital','video_call') DEFAULT 'at_hospital';
ALTER TABLE doctors ADD COLUMN IF NOT EXISTS accepts_video_call TINYINT(1) DEFAULT 0;
