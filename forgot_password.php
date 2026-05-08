<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - HealthCare Plus</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700;900&family=Sora:wght@400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Sora', sans-serif;
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            padding: 20px;
            background: linear-gradient(145deg, #8b1e3f 0%, #c27187 100%);
        }
        .box {
            background: white;
            border-radius: 20px;
            padding: 44px 40px;
            width: 100%; max-width: 420px;
            box-shadow: 0 24px 64px rgba(0,0,0,0.16);
            text-align: center;
        }
        .icon  { font-size: 52px; margin-bottom: 12px; }
        h1     { font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 900; color: #1a1a2e; margin-bottom: 8px; }
        p.sub  { color: #888; font-size: 14px; margin-bottom: 28px; line-height: 1.6; }
        label  { display: block; font-size: 11px; font-weight: 700; color: #555; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px; text-align: left; }
        input  { width: 100%; padding: 12px 15px; background: #f5f5f5; border: 2px solid transparent; border-radius: 8px; font-size: 14px; font-family: 'Sora', sans-serif; transition: all 0.2s; margin-bottom: 18px; }
        input:focus { outline: none; background: white; border-color: #8b1e3f; box-shadow: 0 0 0 3px rgba(226,36,84,0.10); }
        .btn   { width: 100%; padding: 13px; border: none; border-radius: 50px; font-family: 'Outfit', sans-serif; font-size: 14px; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase; color: white; background: #8b1e3f; box-shadow: 0 6px 20px rgba(226,36,84,0.38); cursor: pointer; transition: all 0.2s; }
        .btn:hover { background: #6d1732; transform: translateY(-1px); }
        .alert-s { background: #d1fae5; color: #065f46; border-left: 3px solid #10b981; padding: 12px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 18px; text-align: left; }
        .alert-e { background: #fee2e2; color: #991b1b; border-left: 3px solid #ef4444; padding: 12px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 18px; text-align: left; }
        .back   { margin-top: 20px; font-size: 13px; }
        .back a { color: #8b1e3f; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
<div class="box">
    <div class="icon">🔑</div>
    <h1>Forgot Password?</h1>
    <p class="sub">Enter your registered email and we'll send you a link to reset your password.</p>

    <?php
    if (isset($_SESSION['success'])) { echo '<div class="alert-s">' . $_SESSION['success'] . '</div>'; unset($_SESSION['success']); }
    if (isset($_SESSION['error']))   { echo '<div class="alert-e">' . $_SESSION['error']   . '</div>'; unset($_SESSION['error']); }
    ?>

    <form method="POST" action="process_forgot.php">
        <label>Email Address</label>
        <input type="email" name="email" required placeholder="Enter your email">
        <button type="submit" class="btn">Send Reset Link</button>
    </form>

    <div class="back"><a href="login.php">← Back to Login</a></div>
</div>
</body>
</html>
