<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login / Register - HealthCare Plus</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;900&family=Sora:wght@400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

        html, body { width:100%; overflow-x:hidden; }

        body {
            font-family: 'Sora', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 20px;
            background: #f0f0f0;
        }

        /* ── OUTER CARD — compact, fixed height like reference ── */
        .card {
            width: 100%;
            max-width: 660px;
            height: 420px;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 16px 48px rgba(0,0,0,0.18);
            display: flex;
            position: relative;
        }

        /* ─────────────────────────────────────
           WHITE SIDE  (always full width behind)
        ───────────────────────────────────── */
        .white-side {
            width: 100%;
            background: white;
            display: flex;
        }

        /* Each form half takes 50% of white-side — scroll inside */
        .form-half {
            width: 50%;
            flex-shrink: 0;
            padding: 30px 28px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            overflow-y: auto;
            height: 100%;
        }

        /* Hide scrollbar visually but keep scrolling */
        .form-half::-webkit-scrollbar { width: 0; }
        .form-half { scrollbar-width: none; }

        h2 {
            font-family: 'Outfit', sans-serif;
            font-size: 20px;
            font-weight: 900;
            color: #1a1a2e;
            margin-bottom: 16px;
            text-align: center;
        }

        .field { margin-bottom: 9px; }

        .field label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .field input,
        .field select,
        .field textarea {
            width: 100%;
            padding: 11px 15px;
            background: #f5f5f5;
            border: 2px solid transparent;
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Sora', sans-serif;
            color: #1a1a2e;
            transition: all 0.2s;
        }

        .field input:focus,
        .field select:focus,
        .field textarea:focus {
            outline: none;
            background: white;
            border-color: #8b1e3f;
            box-shadow: 0 0 0 3px rgba(226,36,84,0.10);
        }

        .field textarea { resize: vertical; min-height: 60px; }

        .btn-main {
            width: 100%;
            padding: 12px;
            margin-top: 8px;
            border: none;
            border-radius: 50px;
            font-family: 'Outfit', sans-serif;
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: white;
            background: #8b1e3f;
            box-shadow: 0 6px 20px rgba(226,36,84,0.40);
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-main:hover {
            background: #6d1732;
            transform: translateY(-2px);
            box-shadow: 0 10px 28px rgba(226,36,84,0.50);
        }

        .btn-main:active { transform: scale(0.97); }

        .role-note {
            background: #ffe4ea;
            border-radius: 8px;
            padding: 9px 12px;
            font-size: 12px;
            color: #6d1732;
            margin-bottom: 13px;
        }

        .back-link { display:none; } /* replaced by home-btn */

        .home-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 14px;
            padding: 9px 18px;
            background: #f5f5f5;
            border: 2px solid #e8e8e8;
            border-radius: 50px;
            color: #555;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }

        .home-btn:hover {
            background: #ffe4ea;
            border-color: #8b1e3f;
            color: #8b1e3f;
        }

        /* Alerts */
        .alert { padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 12px; }
        .alert-error   { background: #fee2e2; color: #991b1b; border-left: 3px solid #ef4444; }
        .alert-success { background: #d1fae5; color: #065f46; border-left: 3px solid #10b981; }
        .alert-info    { background: #ffe4ea; color: #6d1732; border-left: 3px solid #8b1e3f; }

        /* ─────────────────────────────────────
           COLORED PANEL  — sits on top, slides
           Default: right half (covering register form)
           Register mode: left half (covering login form)
        ───────────────────────────────────── */
        .color-panel {
            position: absolute;
            top: 0;
            right: 0;              /* start on the RIGHT */
            width: 50%;
            height: 100%;
            background: linear-gradient(145deg, #8b1e3f 0%, #c27187 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 48px 36px;
            text-align: center;
            color: white;
            z-index: 10;
            transition: right 0.65s cubic-bezier(0.68, -0.4, 0.27, 1.4);
            /* decorative circles */
            overflow: hidden;
        }

        /* Slide to LEFT in register mode */
        .card.register-mode .color-panel {
            right: 50%;
        }

        /* decorative bg circles */
        .color-panel::before {
            content: '';
            position: absolute;
            width: 260px; height: 260px;
            border-radius: 50%;
            border: 2px solid rgba(255,255,255,0.15);
            top: -70px; right: -70px;
            pointer-events: none;
        }

        .color-panel::after {
            content: '';
            position: absolute;
            width: 180px; height: 180px;
            border-radius: 50%;
            border: 2px solid rgba(255,255,255,0.10);
            bottom: -50px; left: -50px;
            pointer-events: none;
        }

        .color-panel .logo {
            font-size: 50px;
            margin-bottom: 14px;
            position: relative; z-index: 1;
            filter: drop-shadow(0 4px 12px rgba(0,0,0,0.20));
        }

        .color-panel h3 {
            font-family: 'Outfit', sans-serif;
            font-size: 26px;
            font-weight: 900;
            margin-bottom: 12px;
            letter-spacing: -0.5px;
            position: relative; z-index: 1;
        }

        .color-panel p {
            font-size: 14px;
            opacity: 0.88;
            line-height: 1.65;
            margin-bottom: 30px;
            position: relative; z-index: 1;
        }

        .btn-outline {
            padding: 11px 34px;
            border: 2.5px solid white;
            border-radius: 50px;
            background: transparent;
            color: white;
            font-family: 'Outfit', sans-serif;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.2s;
            position: relative; z-index: 1;
        }

        .btn-outline:hover {
            background: white;
            color: #8b1e3f;
        }

        /* Login side — center content vertically */
        #loginHalf { justify-content: center; }

        /* Register side — start from top, scrolls for extra fields */
        #registerHalf { justify-content: flex-start; padding-top: 28px; }

        /* panel content fade when switching */
        .panel-content { transition: opacity 0.2s; }

        /* ── TABLET: 600–700px ── */
        @media (max-width: 700px) {
            body { padding: 16px; }
            .card { border-radius: 12px; height: 400px; }
            .form-half { padding: 24px 18px; }
            .color-panel { padding: 24px 18px; }
            .color-panel h3 { font-size: 18px; }
            .color-panel p  { font-size: 12px; margin-bottom: 16px; }
        }

        /* ── MOBILE: under 600px — stack vertically, full screen ── */
        @media (max-width: 600px) {
            body { padding: 0; align-items: flex-start; background: white; }

            .card {
                flex-direction: column;
                border-radius: 0;
                box-shadow: none;
                height: auto;
                min-height: 100vh;
            }

            .white-side { display: block; position: relative; }

            .form-half {
                width: 100%;
                padding: 32px 24px 24px;
                display: none;
                height: auto;
                overflow-y: visible;
            }

            #loginHalf  { display: flex; }

            .color-panel {
                position: relative;
                width: 100%;
                height: auto;
                min-height: 180px;
                right: auto !important;
                order: -1;
                border-radius: 0;
                padding: 28px 24px;
                transition: none;
            }

            .card.register-mode #loginHalf    { display: none; }
            .card.register-mode #registerHalf { display: flex; }
        }
    </style>
</head>
<body>

<div class="card" id="mainCard">

    <!-- WHITE SIDE — login + register forms side by side -->
    <div class="white-side">

        <!-- LOGIN FORM (left half) -->
        <div class="form-half" id="loginHalf">
            <h2>Sign In</h2>

            <?php
            if (isset($_GET['book_doctor'])) {
                echo '<div class="alert alert-info">📅 Login to book with Dr. ' . htmlspecialchars($_GET['doctor_name'] ?? 'selected doctor') . '</div>';
            }
            if (isset($_SESSION['error']))   { echo '<div class="alert alert-error">'   . $_SESSION['error']   . '</div>'; unset($_SESSION['error']); }
            if (isset($_SESSION['success'])) { echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>'; unset($_SESSION['success']); }
            ?>

            <form method="POST" action="process_login.php<?php echo isset($_GET['book_doctor']) ? '?book_doctor='.intval($_GET['book_doctor']).'&doctor_name='.urlencode($_GET['doctor_name']??'').'&fee='.floatval($_GET['fee']??0) : ''; ?>">
                <div class="field">
                    <label>Login As</label>
                    <select name="role" required>
                        <option value="">Select your role</option>
                        <option value="patient" <?php echo isset($_GET['book_doctor']) ? 'selected' : ''; ?>>👤 Patient</option>
                        <option value="doctor">👨‍⚕️ Doctor</option>
                        <option value="admin">⚙️ Administrator</option>
                        <option value="pharmacist">💊 Pharmacist</option>
                    </select>
                </div>
                <div class="field">
                    <label>Username or Email</label>
                    <input type="text" name="username" required placeholder="Username or email">
                </div>
                <div class="field">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="Password">
                    <div style="text-align:right;margin-top:6px;">
                        <a href="forgot_password.php" style="font-size:12px;color:#8b1e3f;text-decoration:none;font-weight:600;">Forgot password?</a>
                    </div>
                </div>
                <button type="submit" class="btn-main">Sign In</button>
            </form>
            <a href="novena/index.html" class="home-btn">🏠 Back to Home</a>
        </div>

        <!-- REGISTER FORM (right half — hidden behind color panel by default) -->
        <div class="form-half" id="registerHalf">
            <h2>Create Account</h2>
            <div class="role-note">ⓘ Only patients can self-register. Doctors &amp; pharmacists are added by admins.</div>
            <form method="POST" action="process_register.php">
                <div class="field">
                    <label>Full Name</label>
                    <input type="text" name="full_name" required placeholder="Your full name">
                </div>
                <div class="field">
                    <label>Username</label>
                    <input type="text" name="username" required placeholder="Choose a username">
                </div>
                <div class="field">
                    <label>Email</label>
                    <input type="email" name="email" required placeholder="Your email">
                </div>
                <div class="field">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="Min 6 characters" minlength="6">
                </div>
                <div class="field">
                    <label>Phone</label>
                    <input type="tel" name="phone" placeholder="Phone number">
                </div>
                <div class="field">
                    <label>Gender</label>
                    <select name="gender" required>
                        <option value="">Select gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="field">
                    <label>Date of Birth</label>
                    <input type="date" name="date_of_birth" required>
                </div>
                <div class="field">
                    <label>Address</label>
                    <textarea name="address" placeholder="Your address"></textarea>
                </div>
                <button type="submit" class="btn-main">Create Account</button>
            </form>
            <a href="index.php" class="home-btn">🏠 Back to Home</a>
        </div>

    </div><!-- /white-side -->

    <!-- COLORED PANEL — slides left/right over the forms -->
    <div class="color-panel" id="colorPanel">
        <div class="panel-content" id="panelContent">
            <!-- Login state: panel on right, tells user to sign up -->
            <div id="msgLogin">
                <div class="logo">🏥</div>
                <h3>Hello, Friend!</h3>
                <p>Don't have an account?<br>Join HealthCare Plus today.</p>
                <button class="btn-outline" onclick="switchTo('register')">Sign Up</button>
            </div>
            <!-- Register state: panel on left, tells user to sign in -->
            <div id="msgRegister" style="display:none;">
                <div class="logo">👋</div>
                <h3>Welcome Back!</h3>
                <p>Already have an account?<br>Sign in to your dashboard.</p>
                <button class="btn-outline" onclick="switchTo('login')">Sign In</button>
            </div>
        </div>
    </div>

</div><!-- /card -->

<script>
    function switchTo(mode) {
        const card       = document.getElementById('mainCard');
        const msgLogin   = document.getElementById('msgLogin');
        const msgReg     = document.getElementById('msgRegister');
        const content    = document.getElementById('panelContent');

        // Fade out panel text
        content.style.opacity = '0';

        setTimeout(function() {
            if (mode === 'register') {
                card.classList.add('register-mode');
                msgLogin.style.display = 'none';
                msgReg.style.display   = 'block';
            } else {
                card.classList.remove('register-mode');
                msgReg.style.display   = 'none';
                msgLogin.style.display = 'block';
            }
            content.style.opacity = '1';
        }, 300);
    }

    // Auto switch if ?register=1
    if (new URLSearchParams(window.location.search).get('register') === '1') {
        switchTo('register');
    }
</script>

</body>
</html>
