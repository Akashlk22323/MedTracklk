
MedTracklk

---

## 📋 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Screenshots](#screenshots)
- [Tech Stack](#tech-stack)
- [Installation](#installation)
- [User Roles](#user-roles)
- [Database Schema](#database-schema)
- [Design System](#design-system)
- [Contributing](#contributing)
- [License](#license)
- [Support](#support)

---

## 🌟 Overview

**HealthCare Plus** is a comprehensive doctor appointment management system built with PHP and MySQL. It features a stunning **professional maroon and white theme**, providing a clean, modern, and trustworthy user experience for healthcare providers and patients.

The system manages the complete appointment lifecycle from booking to completion, with real-time doctor location tracking using Google Maps, queue management, digital prescriptions, and multi-role dashboards.

### 🎯 Key Highlights

- 🎨 **Professional Maroon + White Theme** - Clean, modern, medical-grade design
- 📱 **Fully Responsive** - Works seamlessly on desktop, tablet, and mobile devices
- 🔐 **Role-Based Access Control** - 4 distinct user roles with specific permissions
- 🗺️ **Real-Time Location Tracking** - Google Maps integration for doctor location
- 📊 **Analytics Dashboard** - Comprehensive statistics and reporting
- 💊 **Digital Prescriptions** - Paperless prescription management
- 📋 **Lab Reports** - Digital medical report handling
- 🔔 **Queue Management** - Real-time appointment queue tracking

---

## ✨ Features

### 👨‍⚕️ For Doctors

- 📊 **Professional Dashboard** - Overview of today's appointments and patient queue
- 🕐 **Schedule Management** - Manage working hours and time slot availability
- 👥 **Patient Management** - Access patient history and medical records
- 💊 **Prescription System** - Issue and manage digital prescriptions
- 📋 **Lab Report Creation** - Create and share medical test reports
- 💬 **Patient Messaging** - Direct communication with patients
- 📈 **Earnings Tracking** - Monitor consultation fees and revenue

### 🏥 For Patients

- 🔍 **Advanced Doctor Search** - Filter by name, specialization, and hospital location
- 📅 **Easy Appointment Booking** - Book with available doctors and time slots
- 📍 **Doctor Location Tracking** - Real-time location on Google Maps with travel time
- 💬 **Messaging System** - Communicate directly with doctors
- 📋 **Lab Reports Access** - View and download medical test results
- 💊 **Prescription Management** - Digital prescription access and history
- 👤 **Profile Management** - Update personal and medical information

### 👨‍💼 For Administrators

- 📊 **System Overview** - Complete analytics and statistics dashboard
- 👨‍⚕️ **Doctor Management** - Add, edit, and manage doctor accounts
- 👥 **Patient Management** - Manage patient registrations and profiles
- 📅 **Appointment Oversight** - Monitor and manage all appointments
- 🏥 **Hospital Management** - Manage hospital/clinic locations
- 📍 **Location Control** - Update doctor current and next locations with queue numbers
- 📰 **Blog Management** - Manage healthcare blog posts and content
- 📊 **Reports & Analytics** - Generate comprehensive system reports
- 📋 **Activity Logs** - Track all system activities and changes

### 💊 For Pharmacists

- 💊 **Prescription Management** - View and process digital prescriptions
- 📊 **Dashboard Analytics** - Pending and completed prescription statistics
- 🔍 **Prescription Search** - Find and filter prescriptions by various criteria
- ✅ **Status Updates** - Mark prescriptions as processed or completed
- 📋 **Prescription History** - Access complete prescription records

---



## 🛠️ Tech Stack

### Backend
- **PHP 7.4+** - Server-side scripting and business logic
- **MySQL 5.7+** - Relational database management
- **Apache/WAMP** - Web server environment

### Frontend
- **HTML5** - Semantic markup structure
- **CSS3** - Professional maroon + white styling
- **JavaScript (Vanilla)** - Interactive functionality
- **Chart.js** - Data visualization and analytics
- **LeafletJs map** - Location tracking and mapping

### Design
- **Custom CSS Framework** - Maroon (#800000) + White (#FFFFFF) theme
- **Inter Font** - Modern, professional typography
- **Responsive Grid System** - Mobile-first design approach
- **CSS Variables** - Easy theme customization

---

## 📥 Installation

### Prerequisites

- **WAMP/XAMPP/LAMP** server (Apache + MySQL + PHP)
- **PHP 7.4** or higher
- **MySQL 5.7** or higher
- Modern web browser (Chrome, Firefox, Safari, Edge)


### Quick Installation Guide

#### Step 1: Clone the Repository

```bash
git clone https://github.com/yourusername/healthcare-plus.git
cd healthcare-plus
```

#### Step 2: Database Setup

1. Open **phpMyAdmin** at `http://localhost/phpmyadmin`
2. Create a new database named `healthcare_db`
3. Import the SQL file:
   - Click on `healthcare_db` database
   - Go to **Import** tab
   - Choose file: `database.sql`
   - Click **Go** to import

#### Step 3: Configure Database Connection

Edit `includes/config.php`:

```php
<?php
$host = "localhost";
$username = "root";
$password = "";  // Your MySQL password (leave empty for WAMP default)
$database = "healthcare_db";

// Create connection
$conn = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
```

#### Step 4: Configure Google Maps API (Optional)

If using location tracking features, add your Google Maps API key:

1. Get API key from [Google Cloud Console](https://console.cloud.google.com/)
2. Edit files that use maps and replace `YOUR_API_KEY`:

```javascript
<script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY"></script>
```

#### Step 5: Deploy to Web Server

**For WAMP:**
```bash
# Move project to WAMP www directory
C:/wamp64/www/healthcare-plus/
```

**For XAMPP:**
```bash
# Move project to XAMPP htdocs directory
C:/xampp/htdocs/healthcare-plus/
```

**For Linux (LAMP):**
```bash
# Move project to web directory
sudo cp -r healthcare-plus /var/www/html/
sudo chmod -R 755 /var/www/html/healthcare-plus
```

#### Step 6: Start the Application

1. Start **Apache** and **MySQL** services
2. Open browser and navigate to:
   ```
   http://localhost/healthcare-plus/
   ```

### Default Login Credentials

| Role | Username | Password | Access Level |
|------|----------|----------|--------------|
| **Admin** | `admin` | `admin123` | Full system control |
| **Doctor** | `doctor1` | `doctor123` | Doctor dashboard |
| **Patient** | `patient1` | `patient123` | Patient portal |
| **Pharmacist** | `pharmacist1` | `pharma123` | Pharmacy panel |

⚠️ **IMPORTANT:** Change all default passwords immediately after first login for security!

---

## 👥 User Roles

### 1. 👨‍💼 Administrator
**Full system control with:**
- User management (create, edit, delete)
- System configuration
- Analytics and reporting
- Activity monitoring
- Doctor location management
- Hospital/clinic management

### 2. 👨‍⚕️ Doctor
**Medical professional access to:**
- Appointment management
- Patient medical records
- Prescription creation
- Lab report generation
- Schedule management
- Patient communication

### 3. 🏥 Patient
**Healthcare consumer access to:**
- Doctor search and booking
- Appointment history
- Medical records viewing
- Prescription access
- Lab report downloads
- Doctor messaging

### 4. 💊 Pharmacist
**Pharmacy operations including:**
- Prescription processing
- Medication dispensing
- Inventory management (if implemented)
- Prescription verification

---

## 🗄️ Database Schema

### Core Tables

**Users & Authentication:**
- `users` - User accounts and authentication
- `patients` - Patient profile information
- `doctors` - Doctor profiles and credentials
- `pharmacists` - Pharmacist information

**Appointments:**
- `appointments` - Appointment records
- `schedules` - Doctor availability slots

**Medical Records:**
- `prescriptions` - Digital prescriptions
- `lab_reports` - Medical test results
- `messages` - Doctor-patient communication

**System Data:**
- `hospitals` - Hospital/clinic locations
- `specializations` - Medical specializations
- `activity_logs` - System audit trail
- `blog_posts` - Healthcare blog content

### Database Diagram

```
users (1) ─── (many) appointments
  │
  ├── (1:1) patients
  ├── (1:1) doctors
  └── (1:1) pharmacists

doctors (1) ─── (many) prescriptions
doctors (1) ─── (many) lab_reports
doctors (1) ─── (many) schedules

hospitals (1) ─── (many) doctors
```

---

## 📁 Project Structure

```
healthcare-plus/
├── admin/                   # Admin dashboard and management
│   ├── dashboard.php
│   ├── manage_doctors.php
│   ├── manage_patients.php
│   ├── doctor_locations.php
│   ├── manage_appointments.php
│   ├── manage_hospitals.php
│   ├── manage_blogs.php
│   ├── reports.php
│   └── activity_logs.php
├── doctor/                  # Doctor dashboard and features
│   ├── dashboard.php
│   ├── appointments.php
│   ├── manage_slots.php
│   ├── prescriptions.php
│   ├── lab_reports.php
│   ├── messages.php
│   └── profile.php
├── patient/                 # Patient dashboard and features
│   ├── dashboard.php
│   ├── search_doctors.php
│   ├── book_appointment.php
│   ├── doctor_location.php
│   ├── my_appointments.php
│   ├── prescriptions.php
│   ├── lab_reports.php
│   ├── messages.php
│   └── profile.php
├── pharmacist/              # Pharmacist dashboard
│   ├── dashboard.php
│   ├── prescriptions.php
│   └── profile.php
├── includes/                # Common PHP files
│   ├── config.php           # Database configuration
│   ├── functions.php        # Helper functions
│   └── search_widget.php    # Search component
├── css/                     # Stylesheets
│   ├── main.css             # Main maroon + white theme (937 lines)
│   └── style.css            # Style imports
├── js/                      # JavaScript files
│   └── scripts.js
├── uploads/                 # User uploaded files
│   ├── prescriptions/
│   └── lab_reports/
├── screenshots/             # Application screenshots
├── database.sql             # Database schema and sample data
├── index.php                # Landing page
├── login.php                # Login system
├── register.php             # User registration
├── logout.php               # Logout handler
├── README.md                # This file
├── LICENSE                  # MIT License
└── CONTRIBUTING.md          # Contribution guidelines
```

---

## 🎨 Design System

### Color Palette

**Primary Maroon:**
```css
Maroon:         #800000  /* Primary actions, headers, icons */
Maroon Dark:    #600000  /* Hover states */
Maroon Light:   #a00000  /* Gradients */
Maroon Lighter: #c84040  /* Accents */
```

**Pure White:**
```css
White: #ffffff  /* Backgrounds, cards, text on maroon */
```

**Gray Scale:**
```css
Gray 50:  #fafafa  /* Page background */
Gray 100: #f5f5f5  /* Hover states */
Gray 200: #eeeeee  /* Borders */
Gray 600: #757575  /* Secondary text */
Gray 900: #212121  /* Headings */
```

**Status Colors:**
```css
Success: #4caf50  /* Completed, active */
Warning: #ff9800  /* Pending, attention */
Danger:  #f44336  /* Errors, cancelled */
Info:    #2196f3  /* Information */
```

### Typography

- **Font Family:** Inter (Google Fonts)
- **Base Size:** 15px
- **Headings:** 700-900 weight
- **Body:** 400-500 weight

### Components

- **Navbar:** Fixed top, 65px height, white background
- **Sidebar:** Fixed left, 260px width, white background, maroon active states
- **Stat Cards:** White with 4px maroon top border, large maroon numbers
- **Buttons:** Maroon primary, white secondary, various states
- **Forms:** Clean inputs with maroon focus rings
- **Tables:** White background, gray headers, row hover effects

---

## 🚀 Features Roadmap

### Phase 1 (Completed ✅)
- [x] User authentication and role-based access
- [x] Doctor appointment booking system
- [x] Real-time location tracking
- [x] Digital prescription management
- [x] Lab report system
- [x] Patient-doctor messaging
- [x] Professional maroon + white theme

### Phase 2 (Planned 📋)
- [ ] Email notifications for appointments
- [ ] SMS reminders via Twilio
- [ ] Payment gateway integration (PayPal, Stripe)
- [ ] Video consultation (WebRTC)
- [ ] Push notifications
- [ ] Multi-language support

### Phase 3 (Future 🔮)
- [ ] Mobile app (React Native)
- [ ] AI chatbot for patient support
- [ ] Telemedicine features
- [ ] Electronic Health Records (EHR) integration
- [ ] Insurance claim processing
- [ ] Inventory management for pharmacies

---

## 🤝 Contributing

Contributions are welcome! Please follow these guidelines:

### How to Contribute

1. **Fork the repository**
   ```bash
   git fork https://github.com/yourusername/healthcare-plus.git
   ```

2. **Create a feature branch**
   ```bash
   git checkout -b feature/AmazingFeature
   ```

3. **Make your changes**
   - Write clean, documented code
   - Follow existing code style
   - Add comments where necessary

4. **Commit your changes**
   ```bash
   git commit -m 'Add some AmazingFeature'
   ```

5. **Push to the branch**
   ```bash
   git push origin feature/AmazingFeature
   ```

6. **Open a Pull Request**
   - Provide clear description of changes
   - Reference any related issues
   - Wait for review

### Development Guidelines

- Follow PSR-12 coding standards for PHP
- Use meaningful variable and function names
- Comment complex logic
- Test thoroughly before submitting
- Update documentation as needed

For detailed guidelines, see [CONTRIBUTING.md](CONTRIBUTING.md)

---

## 📝 License

This project is licensed under the **MIT License** - see the [LICENSE](LICENSE) file for details.

### MIT License Summary

```
Copyright (c) 2026 [Your Name]

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software...
```

---

## 👨‍💻 Author

**Your Name**
- GitHub: [@yourusername](https://github.com/yourusername)
- LinkedIn: [Your LinkedIn](https://linkedin.com/in/yourprofile)
- Email: your.email@example.com
- Portfolio: [yourwebsite.com](https://yourwebsite.com)

---

## 🙏 Acknowledgments

- **Inter Font** by Rasmus Andersson
- **Chart.js** for data visualization
- **Google Maps API** for location services
- **PHP Community** for excellent documentation
- **Stack Overflow** for troubleshooting help
- All contributors who helped improve this project

---

## 📧 Support

### Need Help?

- 📖 **Documentation:** Check the [Wiki](https://github.com/yourusername/healthcare-plus/wiki)
- 🐛 **Bug Reports:** [Create an issue](https://github.com/yourusername/healthcare-plus/issues)
- 💬 **Questions:** [Discussions](https://github.com/yourusername/healthcare-plus/discussions)
- 📧 **Email:** support@yourproject.com

### Common Issues

**Can't login?**
- Verify database connection in `includes/config.php`
- Check if database was imported correctly
- Ensure default credentials are correct

**Theme not loading?**
- Clear browser cache (Ctrl+Shift+Del)
- Verify CSS files exist in `/css` folder
- Check browser console for errors

**Location tracking not working?**
- Add Google Maps API key
- Enable billing on Google Cloud Console
- Check API restrictions

---

## ⭐ Star This Repository

If you found this project helpful, please consider giving it a star! It helps others discover the project.

[![GitHub stars](https://img.shields.io/github/stars/yourusername/healthcare-plus?style=social)](https://github.com/yourusername/healthcare-plus/stargazers)

---

## 📊 Project Stats

- **Lines of Code:** 15,000+
- **CSS Lines:** 937 (main.css)
- **Development Time:** 4 months
- **Contributors:** 1+
- **Version:** 4.0 Final

---

**Made with ❤️ for better healthcare management**

*Last Updated: May 5, 2026*
ENDREADME

cat /mnt/user-data/outputs/README.md && echo -e "\n✅ GitHub README.md created!"
Output

# 🏥 HealthCare Plus - Doctor Appointment System

> A modern, full-featured healthcare appointment management system with professional maroon and white theme, designed for hospitals, clinics, and medical practices.

[![License: MIT](https://img.shields.io/badge/License-MIT-maroon.svg)](https://opensource.org/licenses/MIT)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?logo=php)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)

![HealthCare Plus Dashboard](https://via.placeholder.com/1200x600/800000/FFFFFF?text=HealthCare+Plus+Dashboard)

---

## 📋 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Screenshots](#screenshots)
- [Tech Stack](#tech-stack)
- [Installation](#installation)
- [User Roles](#user-roles)
- [Database Schema](#database-schema)
- [Design System](#design-system)
- [Contributing](#contributing)
- [License](#license)
- [Support](#support)

---

## 🌟 Overview

**HealthCare Plus** is a comprehensive doctor appointment management system built with PHP and MySQL. It features a stunning **professional maroon and white theme**, providing a clean, modern, and trustworthy user experience for healthcare providers and patients.

The system manages the complete appointment lifecycle from booking to completion, with real-time doctor location tracking using Google Maps, queue management, digital prescriptions, and multi-role dashboards.

### 🎯 Key Highlights

- 🎨 **Professional Maroon + White Theme** - Clean, modern, medical-grade design
- 📱 **Fully Responsive** - Works seamlessly on desktop, tablet, and mobile devices
- 🔐 **Role-Based Access Control** - 4 distinct user roles with specific permissions
- 🗺️ **Real-Time Location Tracking** - Google Maps integration for doctor location
- 📊 **Analytics Dashboard** - Comprehensive statistics and reporting
- 💊 **Digital Prescriptions** - Paperless prescription management
- 📋 **Lab Reports** - Digital medical report handling
- 🔔 **Queue Management** - Real-time appointment queue tracking

---

## ✨ Features

### 👨‍⚕️ For Doctors

- 📊 **Professional Dashboard** - Overview of today's appointments and patient queue
- 🕐 **Schedule Management** - Manage working hours and time slot availability
- 👥 **Patient Management** - Access patient history and medical records
- 💊 **Prescription System** - Issue and manage digital prescriptions
- 📋 **Lab Report Creation** - Create and share medical test reports
- 💬 **Patient Messaging** - Direct communication with patients
- 📈 **Earnings Tracking** - Monitor consultation fees and revenue

### 🏥 For Patients

- 🔍 **Advanced Doctor Search** - Filter by name, specialization, and hospital location
- 📅 **Easy Appointment Booking** - Book with available doctors and time slots
- 📍 **Doctor Location Tracking** - Real-time location on Google Maps with travel time
- 💬 **Messaging System** - Communicate directly with doctors
- 📋 **Lab Reports Access** - View and download medical test results
- 💊 **Prescription Management** - Digital prescription access and history
- 👤 **Profile Management** - Update personal and medical information

### 👨‍💼 For Administrators

- 📊 **System Overview** - Complete analytics and statistics dashboard
- 👨‍⚕️ **Doctor Management** - Add, edit, and manage doctor accounts
- 👥 **Patient Management** - Manage patient registrations and profiles
- 📅 **Appointment Oversight** - Monitor and manage all appointments
- 🏥 **Hospital Management** - Manage hospital/clinic locations
- 📍 **Location Control** - Update doctor current and next locations with queue numbers
- 📰 **Blog Management** - Manage healthcare blog posts and content
- 📊 **Reports & Analytics** - Generate comprehensive system reports
- 📋 **Activity Logs** - Track all system activities and changes

### 💊 For Pharmacists

- 💊 **Prescription Management** - View and process digital prescriptions
- 📊 **Dashboard Analytics** - Pending and completed prescription statistics
- 🔍 **Prescription Search** - Find and filter prescriptions by various criteria
- ✅ **Status Updates** - Mark prescriptions as processed or completed
- 📋 **Prescription History** - Access complete prescription records

---

## 📸 Screenshots

### Dashboard Overview
![Dashboard](screenshots/dashboard.png)

### Doctor Search with Filters
![Search Doctors](screenshots/search-doctors.png)

### Real-Time Location Tracking
![Doctor Location](screenshots/doctor-location.png)

### Appointment Booking
![Book Appointment](screenshots/book-appointment.png)

### Mobile Responsive Design
![Mobile View](screenshots/mobile-view.png)

> **Note:** Add your actual screenshots to the `/screenshots` folder

---

## 🛠️ Tech Stack

### Backend
- **PHP 7.4+** - Server-side scripting and business logic
- **MySQL 5.7+** - Relational database management
- **Apache/WAMP** - Web server environment

### Frontend
- **HTML5** - Semantic markup structure
- **CSS3** - Professional maroon + white styling
- **JavaScript (Vanilla)** - Interactive functionality
- **Chart.js** - Data visualization and analytics
- **Google Maps API** - Location tracking and mapping

### Design
- **Custom CSS Framework** - Maroon (#800000) + White (#FFFFFF) theme
- **Inter Font** - Modern, professional typography
- **Responsive Grid System** - Mobile-first design approach
- **CSS Variables** - Easy theme customization

---

## 📥 Installation

### Prerequisites

- **WAMP/XAMPP/LAMP** server (Apache + MySQL + PHP)
- **PHP 7.4** or higher
- **MySQL 5.7** or higher
- Modern web browser (Chrome, Firefox, Safari, Edge)
- **Google Maps API Key** (optional, for location features)

### Quick Installation Guide

#### Step 1: Clone the Repository

```bash
git clone https://github.com/yourusername/healthcare-plus.git
cd healthcare-plus
```

#### Step 2: Database Setup

1. Open **phpMyAdmin** at `http://localhost/phpmyadmin`
2. Create a new database named `healthcare_db`
3. Import the SQL file:
   - Click on `healthcare_db` database
   - Go to **Import** tab
   - Choose file: `database.sql`
   - Click **Go** to import

#### Step 3: Configure Database Connection

Edit `includes/config.php`:

```php
<?php
$host = "localhost";
$username = "root";
$password = "";  // Your MySQL password (leave empty for WAMP default)
$database = "healthcare_db";

// Create connection
$conn = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
```

#### Step 4: Configure Google Maps API (Optional)

If using location tracking features, add your Google Maps API key:

1. Get API key from [Google Cloud Console](https://console.cloud.google.com/)
2. Edit files that use maps and replace `YOUR_API_KEY`:

```javascript
<script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY"></script>
```

#### Step 5: Deploy to Web Server

**For WAMP:**
```bash
# Move project to WAMP www directory
C:/wamp64/www/healthcare-plus/
```

**For XAMPP:**
```bash
# Move project to XAMPP htdocs directory
C:/xampp/htdocs/healthcare-plus/
```

**For Linux (LAMP):**
```bash
# Move project to web directory
sudo cp -r healthcare-plus /var/www/html/
sudo chmod -R 755 /var/www/html/healthcare-plus
```

#### Step 6: Start the Application

1. Start **Apache** and **MySQL** services
2. Open browser and navigate to:
   ```
   http://localhost/healthcare-plus/
   ```

### Default Login Credentials

| Role | Username | Password | Access Level |
|------|----------|----------|--------------|
| **Admin** | `admin` | `admin123` | Full system control |
| **Doctor** | `doctor1` | `doctor123` | Doctor dashboard |
| **Patient** | `patient1` | `patient123` | Patient portal |
| **Pharmacist** | `pharmacist1` | `pharma123` | Pharmacy panel |

⚠️ **IMPORTANT:** Change all default passwords immediately after first login for security!

---

## 👥 User Roles

### 1. 👨‍💼 Administrator
**Full system control with:**
- User management (create, edit, delete)
- System configuration
- Analytics and reporting
- Activity monitoring
- Doctor location management
- Hospital/clinic management

### 2. 👨‍⚕️ Doctor
**Medical professional access to:**
- Appointment management
- Patient medical records
- Prescription creation
- Lab report generation
- Schedule management
- Patient communication

### 3. 🏥 Patient
**Healthcare consumer access to:**
- Doctor search and booking
- Appointment history
- Medical records viewing
- Prescription access
- Lab report downloads
- Doctor messaging

### 4. 💊 Pharmacist
**Pharmacy operations including:**
- Prescription processing
- Medication dispensing
- Inventory management (if implemented)
- Prescription verification

---

## 🗄️ Database Schema

### Core Tables

**Users & Authentication:**
- `users` - User accounts and authentication
- `patients` - Patient profile information
- `doctors` - Doctor profiles and credentials
- `pharmacists` - Pharmacist information

**Appointments:**
- `appointments` - Appointment records
- `schedules` - Doctor availability slots

**Medical Records:**
- `prescriptions` - Digital prescriptions
- `lab_reports` - Medical test results
- `messages` - Doctor-patient communication

**System Data:**
- `hospitals` - Hospital/clinic locations
- `specializations` - Medical specializations
- `activity_logs` - System audit trail
- `blog_posts` - Healthcare blog content

### Database Diagram

```
users (1) ─── (many) appointments
  │
  ├── (1:1) patients
  ├── (1:1) doctors
  └── (1:1) pharmacists

doctors (1) ─── (many) prescriptions
doctors (1) ─── (many) lab_reports
doctors (1) ─── (many) schedules

hospitals (1) ─── (many) doctors
```

---

## 📁 Project Structure

```
healthcare-plus/
├── admin/                   # Admin dashboard and management
│   ├── dashboard.php
│   ├── manage_doctors.php
│   ├── manage_patients.php
│   ├── doctor_locations.php
│   ├── manage_appointments.php
│   ├── manage_hospitals.php
│   ├── manage_blogs.php
│   ├── reports.php
│   └── activity_logs.php
├── doctor/                  # Doctor dashboard and features
│   ├── dashboard.php
│   ├── appointments.php
│   ├── manage_slots.php
│   ├── prescriptions.php
│   ├── lab_reports.php
│   ├── messages.php
│   └── profile.php
├── patient/                 # Patient dashboard and features
│   ├── dashboard.php
│   ├── search_doctors.php
│   ├── book_appointment.php
│   ├── doctor_location.php
│   ├── my_appointments.php
│   ├── prescriptions.php
│   ├── lab_reports.php
│   ├── messages.php
│   └── profile.php
├── pharmacist/              # Pharmacist dashboard
│   ├── dashboard.php
│   ├── prescriptions.php
│   └── profile.php
├── includes/                # Common PHP files
│   ├── config.php           # Database configuration
│   ├── functions.php        # Helper functions
│   └── search_widget.php    # Search component
├── css/                     # Stylesheets
│   ├── main.css             # Main maroon + white theme (937 lines)
│   └── style.css            # Style imports
├── js/                      # JavaScript files
│   └── scripts.js
├── uploads/                 # User uploaded files
│   ├── prescriptions/
│   └── lab_reports/
├── screenshots/             # Application screenshots
├── database.sql             # Database schema and sample data
├── index.php                # Landing page
├── login.php                # Login system
├── register.php             # User registration
├── logout.php               # Logout handler
├── README.md                # This file
├── LICENSE                  # MIT License
└── CONTRIBUTING.md          # Contribution guidelines
```

---

## 🎨 Design System

### Color Palette

**Primary Maroon:**
```css
Maroon:         #800000  /* Primary actions, headers, icons */
Maroon Dark:    #600000  /* Hover states */
Maroon Light:   #a00000  /* Gradients */
Maroon Lighter: #c84040  /* Accents */
```

**Pure White:**
```css
White: #ffffff  /* Backgrounds, cards, text on maroon */
```

**Gray Scale:**
```css
Gray 50:  #fafafa  /* Page background */
Gray 100: #f5f5f5  /* Hover states */
Gray 200: #eeeeee  /* Borders */
Gray 600: #757575  /* Secondary text */
Gray 900: #212121  /* Headings */
```

**Status Colors:**
```css
Success: #4caf50  /* Completed, active */
Warning: #ff9800  /* Pending, attention */
Danger:  #f44336  /* Errors, cancelled */
Info:    #2196f3  /* Information */
```

### Typography

- **Font Family:** Inter (Google Fonts)
- **Base Size:** 15px
- **Headings:** 700-900 weight
- **Body:** 400-500 weight

### Components

- **Navbar:** Fixed top, 65px height, white background
- **Sidebar:** Fixed left, 260px width, white background, maroon active states
- **Stat Cards:** White with 4px maroon top border, large maroon numbers
- **Buttons:** Maroon primary, white secondary, various states
- **Forms:** Clean inputs with maroon focus rings
- **Tables:** White background, gray headers, row hover effects

---

## 🚀 Features Roadmap

### Phase 1 (Completed ✅)
- [x] User authentication and role-based access
- [x] Doctor appointment booking system
- [x] Real-time location tracking
- [x] Digital prescription management
- [x] Lab report system
- [x] Patient-doctor messaging
- [x] Professional maroon + white theme

### Phase 2 (Planned 📋)
- [ ] Email notifications for appointments
- [ ] SMS reminders via Twilio
- [ ] Payment gateway integration (PayPal, Stripe)
- [ ] Video consultation (WebRTC)
- [ ] Push notifications
- [ ] Multi-language support

### Phase 3 (Future 🔮)
- [ ] Mobile app (React Native)
- [ ] AI chatbot for patient support
- [ ] Telemedicine features
- [ ] Electronic Health Records (EHR) integration
- [ ] Insurance claim processing
- [ ] Inventory management for pharmacies

---

## 🤝 Contributing

Contributions are welcome! Please follow these guidelines:

### How to Contribute

1. **Fork the repository**
   ```bash
   git fork https://github.com/yourusername/healthcare-plus.git
   ```

2. **Create a feature branch**
   ```bash
   git checkout -b feature/AmazingFeature
   ```

3. **Make your changes**
   - Write clean, documented code
   - Follow existing code style
   - Add comments where necessary

4. **Commit your changes**
   ```bash
   git commit -m 'Add some AmazingFeature'
   ```

5. **Push to the branch**
   ```bash
   git push origin feature/AmazingFeature
   ```

6. **Open a Pull Request**
   - Provide clear description of changes
   - Reference any related issues
   - Wait for review

### Development Guidelines

- Follow PSR-12 coding standards for PHP
- Use meaningful variable and function names
- Comment complex logic
- Test thoroughly before submitting
- Update documentation as needed

For detailed guidelines, see [CONTRIBUTING.md](CONTRIBUTING.md)

---

## 📝 License

This project is licensed under the **MIT License** - see the [LICENSE](LICENSE) file for details.

### MIT License Summary

```
Copyright (c) 2026 [Your Name]

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software...
```

---

## 👨‍💻 Author

**Your Name**
- GitHub: [@yourusername](https://github.com/yourusername)
- LinkedIn: [Your LinkedIn](https://linkedin.com/in/yourprofile)
- Email: your.email@example.com
- Portfolio: [yourwebsite.com](https://yourwebsite.com)

**P.G.K Lakshan**
- GitHub: [@lakshan-star-sudo](https://github.com/lakshan-star-sudo)
- Email:kavilakshan48@gmail.com


---

## 🙏 Acknowledgments

- **Inter Font** by Rasmus Andersson
- **Chart.js** for data visualization
- **Google Maps API** for location services
- **PHP Community** for excellent documentation
- **Stack Overflow** for troubleshooting help
- All contributors who helped improve this project

---

## 📧 Support

### Need Help?

- 📖 **Documentation:** Check the [Wiki](https://github.com/yourusername/healthcare-plus/wiki)
- 🐛 **Bug Reports:** [Create an issue](https://github.com/yourusername/healthcare-plus/issues)
- 💬 **Questions:** [Discussions](https://github.com/yourusername/healthcare-plus/discussions)
- 📧 **Email:** support@yourproject.com

### Common Issues

**Can't login?**
- Verify database connection in `includes/config.php`
- Check if database was imported correctly
- Ensure default credentials are correct

**Theme not loading?**
- Clear browser cache (Ctrl+Shift+Del)
- Verify CSS files exist in `/css` folder
- Check browser console for errors

**Location tracking not working?**
- Add Google Maps API key
- Enable billing on Google Cloud Console
- Check API restrictions

---

## ⭐ Star This Repository

If you found this project helpful, please consider giving it a star! It helps others discover the project.

[![GitHub stars](https://img.shields.io/github/stars/yourusername/healthcare-plus?style=social)](https://github.com/yourusername/healthcare-plus/stargazers)

---

## 📊 Project Stats

- **Lines of Code:** 15,000+
- **CSS Lines:** 937 (main.css)
- **Development Time:** 4 months
- **Contributors:** 1+
- **Version:** 4.0 Final

---

**Made with ❤️ for better healthcare management**



## Screenshots

| Admin Dashboard | Doctor Dashboard | Patient Dashboard |
|----------------|-----------------|------------------|
| username: admin | username: Dr.silva | patient can register |
| Password: admin123 | Password: doctor123 | Password:  |
| ![Admin](screenshotsadmin.png) | ![Doctor](screenshotsdoctor.png) | ![Patient](screenshotspatient.png) |
