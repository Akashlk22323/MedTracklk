<?php include 'includes/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MedTrack LK - Book Your Doctor</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #8b1e3f 0%, #6d1732 100%);
      min-height: 100vh;
    }
    
    /* Header */
    .header {
      background: rgba(255,255,255,0.1);
      backdrop-filter: blur(10px);
      padding: 12px 0;
      border-bottom: 1px solid rgba(255,255,255,0.2);
    }
    .header-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .logo { color: white; font-size: 24px; font-weight: 800; text-decoration: none; }
    .nav a {
      color: rgba(255,255,255,0.9);
      text-decoration: none;
      margin-left: 24px;
      font-size: 14px;
      font-weight: 600;
    }
    .nav a:hover { color: white; }
    
    /* Hero Section */
    .hero {
      max-width: 1200px;
      margin: 0 auto;
      padding: 60px 20px;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 40px;
      align-items: center;
    }
    .hero-text h1 {
      font-size: 48px;
      font-weight: 800;
      color: white;
      line-height: 1.2;
      margin-bottom: 20px;
      text-shadow: 0 4px 12px rgba(0,0,0,0.3);
    }
    .hero-text p {
      font-size: 18px;
      color: rgba(255,255,255,0.9);
      margin-bottom: 30px;
      line-height: 1.6;
    }
    .hero-search {
      background: rgba(255,255,255,0.98);
      border-radius: 24px;
      padding: 36px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    }
    
    /* Features */
    .features {
      max-width: 1200px;
      margin: 40px auto;
      padding: 0 20px;
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 24px;
    }
    .feature-card {
      background: rgba(255,255,255,0.12);
      backdrop-filter: blur(10px);
      padding: 28px;
      border-radius: 16px;
      border: 1px solid rgba(255,255,255,0.2);
      color: white;
    }
    .feature-icon { font-size: 40px; margin-bottom: 16px; }
    .feature-card h3 { font-size: 20px; margin-bottom: 10px; }
    .feature-card p { font-size: 14px; opacity: 0.9; line-height: 1.6; }
    
    /* Footer */
    .footer {
      background: rgba(0,0,0,0.2);
      color: rgba(255,255,255,0.8);
      text-align: center;
      padding: 24px;
      margin-top: 60px;
    }
    
    @media(max-width: 968px) {
      .hero { grid-template-columns: 1fr; }
      .features { grid-template-columns: 1fr; }
      .hero-text h1 { font-size: 36px; }
    }
  </style>
</head>
<body>

<!-- Header -->
<div class="header">
  <div class="header-container">
    <a href="landing.php" class="logo">🏥 MedTrack LK</a>
    <nav class="nav">
      <a href="blogs.php">Blog</a>
      <a href="login.php">Login</a>
    </nav>
  </div>
</div>

<!-- Hero Section -->
<div class="hero">
  <div class="hero-text">
    <h1>Your Most Trusted Health Partner</h1>
    <p>Book appointments with Sri Lanka's top doctors instantly. Get quality healthcare at your fingertips with real-time availability and instant confirmation.</p>
    <div style="display:flex;gap:12px;flex-wrap:wrap">
      <span style="background:rgba(255,255,255,0.2);padding:10px 20px;border-radius:50px;font-size:14px;font-weight:600">✓ 24/7 Support</span>
      <span style="background:rgba(255,255,255,0.2);padding:10px 20px;border-radius:50px;font-size:14px;font-weight:600">✓ Top Doctors</span>
      <span style="background:rgba(255,255,255,0.2);padding:10px 20px;border-radius:50px;font-size:14px;font-weight:600">✓ Instant Booking</span>
    </div>
  </div>
  
  <div class="hero-search">
    <?php
    $widget_root     = '';
    $widget_title    = 'Book Your Doctor';
    $widget_redirect = 'login';
    $widget_compact  = false;
    include 'includes/search_widget.php';
    ?>
  </div>
</div>

<!-- Features -->
<div class="features">
  <div class="feature-card">
    <div class="feature-icon">🩺</div>
    <h3>24/7 Online Booking</h3>
    <p>Book appointments anytime, anywhere. Get instant confirmation and never miss your appointment.</p>
  </div>
  
  <div class="feature-card">
    <div class="feature-icon">👨‍⚕️</div>
    <h3>Expert Doctors</h3>
    <p>Access to Sri Lanka's leading specialists across all medical fields with verified credentials.</p>
  </div>
  
  <div class="feature-card">
    <div class="feature-icon">📍</div>
    <h3>Live Location Tracking</h3>
    <p>Track your doctor's real-time location and queue status for better time management.</p>
  </div>
</div>

<!-- Footer -->
<div class="footer">
  <p>&copy; <?php echo date('Y'); ?> MedTrack LK. All rights reserved.</p>
</div>

</body>
</html>
