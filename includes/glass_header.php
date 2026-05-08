<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'HealthCare Plus'; ?></title>
    <link rel="stylesheet" href="<?php echo $widget_root ?? '..'; ?>/css/style.css">
</head>
<body class="<?php echo $theme_class ?? 'patient-theme'; ?>">

<!-- Navbar -->
<nav class="navbar navbar-glass navbar-expand-lg sticky-top">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="dashboard.php">🏥 HealthCare Plus</a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon" style="filter:invert(1)"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php echo $nav_links ?? ''; ?>
                <li class="nav-item"><a class="nav-link" href="<?php echo $widget_root ?? '..'; ?>/logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-2 col-md-3 p-0">
            <div class="sidebar-glass">
                <ul class="nav flex-column">
                    <?php echo $sidebar_links ?? ''; ?>
                </ul>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-10 col-md-9 p-4">
