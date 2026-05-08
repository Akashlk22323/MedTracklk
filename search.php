<?php
session_start();
include 'includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Doctors - MedTrack</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #8b1e3f 0%, #6d1732 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .top-nav {
            max-width: 1200px;
            margin: 0 auto 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
        }

        .logo {
            font-size: 28px;
            font-weight: 900;
            color: white;
            text-decoration: none;
            text-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }

        .nav-links {
            display: flex;
            gap: 20px;
        }

        .nav-links a {
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 50px;
            background: rgba(255,255,255,0.1);
            transition: all 0.3s;
        }

        .nav-links a:hover {
            background: rgba(255,255,255,0.2);
            color: white;
            transform: translateY(-2px);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .search-section {
            background: white;
            border-radius: 28px;
            padding: 50px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            margin-bottom: 40px;
        }

        .page-title {
            text-align: center;
            margin-bottom: 40px;
        }

        .page-title h1 {
            font-size: 42px;
            font-weight: 900;
            background: linear-gradient(135deg, #8b1e3f 0%, #6d1732 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
            letter-spacing: -1px;
        }

        .page-title p {
            color: #636e72;
            font-size: 18px;
            font-weight: 500;
        }

        .search-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            align-items: start;
        }

        .search-form-area {
            background: linear-gradient(135deg, rgba(139,30,63,0.05), rgba(109,23,50,0.05));
            border-radius: 24px;
            padding: 40px;
            border: 2px solid rgba(139,30,63,0.1);
        }

        .results-area {
            min-height: 400px;
        }

        .feature-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            margin-top: 40px;
        }

        .feature-card {
            background: white;
            padding: 32px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 16px 40px rgba(139,30,63,0.2);
        }

        .feature-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }

        .feature-card h3 {
            font-size: 20px;
            font-weight: 700;
            color: #2d3436;
            margin-bottom: 8px;
        }

        .feature-card p {
            color: #636e72;
            font-size: 14px;
            line-height: 1.6;
        }

        @media (max-width: 968px) {
            .search-grid {
                grid-template-columns: 1fr;
            }
            
            .feature-cards {
                grid-template-columns: 1fr;
            }

            .search-section {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>

    <div class="top-nav">
        <a href="novena/index.html" class="logo">🏥 MedTrack</a>
        <div class="nav-links">
            <a href="novena/index.html">Home</a>
            <a href="blogs.php">Blog</a>
            <a href="login.php">Login</a>
        </div>
    </div>

    <div class="container">
        <div class="search-section">
            <div class="page-title">
                <h1>Find Your Doctor</h1>
                <p>Search by name, hospital, specialization, or date</p>
            </div>

            <div class="search-grid">
                <!-- Search Form -->
                <div class="search-form-area">
                    <?php
                    $widget_root = '';
                    $widget_title = 'Search Doctors';
                    $widget_redirect = 'login';
                    $widget_compact = false;
                    include 'includes/search_widget.php';
                    ?>
                </div>

                <!-- Info Panel -->
                <div class="results-area">
                    <div style="background: linear-gradient(135deg, #8b1e3f, #6d1732); padding: 40px; border-radius: 24px; color: white; height: 100%;">
                        <h2 style="font-size: 32px; font-weight: 800; margin-bottom: 20px;">Why Choose Us?</h2>
                        
                        <div style="margin-bottom: 24px;">
                            <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 16px;">
                                <div style="width: 48px; height: 48px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px;">✓</div>
                                <div>
                                    <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 4px;">Top Doctors</h3>
                                    <p style="opacity: 0.9; font-size: 14px;">Access Sri Lanka's leading specialists</p>
                                </div>
                            </div>

                            <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 16px;">
                                <div style="width: 48px; height: 48px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px;">✓</div>
                                <div>
                                    <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 4px;">Instant Booking</h3>
                                    <p style="opacity: 0.9; font-size: 14px;">Book appointments in seconds</p>
                                </div>
                            </div>

                            <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 16px;">
                                <div style="width: 48px; height: 48px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px;">✓</div>
                                <div>
                                    <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 4px;">Real-Time Updates</h3>
                                    <p style="opacity: 0.9; font-size: 14px;">Track doctor locations and queue status</p>
                                </div>
                            </div>

                            <div style="display: flex; align-items: center; gap: 16px;">
                                <div style="width: 48px; height: 48px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px;">✓</div>
                                <div>
                                    <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 4px;">24/7 Support</h3>
                                    <p style="opacity: 0.9; font-size: 14px;">We're here whenever you need us</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Feature Cards -->
        <div class="feature-cards">
            <div class="feature-card">
                <div class="feature-icon">🩺</div>
                <h3>Multiple Specializations</h3>
                <p>Access doctors across 20+ medical specializations from cardiology to pediatrics</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">📍</div>
                <h3>25+ Hospitals</h3>
                <p>Find doctors at Sri Lanka's top hospitals across all major cities</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">💬</div>
                <h3>Video Consultations</h3>
                <p>Get medical advice from home with secure video consultations</p>
            </div>
        </div>
    </div>

</body>
</html>
