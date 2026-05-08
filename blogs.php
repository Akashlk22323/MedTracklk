<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Blog - HealthCare Plus</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,900;1,700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

        :root {
            --red:   #8b1e3f;
            --dark:  #0f0f14;
            --ink:   #1c1c28;
            --muted: #6b7280;
            --line:  #e8e8ed;
            --bg:    #fafafa;
        }

        body { font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--ink); }

        /* ── NAV ── */
        .nav {
            position: sticky; top:0; z-index:100;
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid var(--line);
            padding: 0 40px;
            height: 62px;
            display: flex; align-items:center; justify-content:space-between;
        }
        .nav-brand { display:flex; align-items:center; gap:10px; text-decoration:none; }
        .nav-brand-icon { width:34px; height:34px; background:var(--red); border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:18px; }
        .nav-brand-text { font-family:'Playfair Display',serif; font-size:17px; font-weight:700; color:var(--ink); letter-spacing:-0.3px; }
        .nav-right { display:flex; align-items:center; gap:6px; }
        .nav-right a { color:var(--muted); text-decoration:none; padding:7px 13px; border-radius:7px; font-size:14px; font-weight:500; transition:all 0.18s; }
        .nav-right a:hover { color:var(--ink); background:#f3f3f6; }
        .nav-right .btn-login { background:var(--red); color:white; padding:8px 18px; border-radius:8px; font-weight:600; }
        .nav-right .btn-login:hover { background:#6d1732; }

        /* ── HERO ── */
        .hero {
            background: var(--dark);
            padding: 90px 40px 80px;
            position: relative; overflow:hidden;
        }
        .hero-bg {
            position:absolute; inset:0;
            background:
                radial-gradient(ellipse 700px 400px at 10% 60%, rgba(226,36,84,0.18), transparent),
                radial-gradient(ellipse 500px 500px at 90% 20%, rgba(226,36,84,0.10), transparent);
        }
        /* decorative lines */
        .hero-lines {
            position:absolute; right:0; top:0; bottom:0; width:40%;
            background: repeating-linear-gradient(0deg, rgba(255,255,255,0.025) 0px, rgba(255,255,255,0.025) 1px, transparent 1px, transparent 40px);
        }
        .hero-inner { position:relative; z-index:1; max-width:1120px; margin:0 auto; }
        .hero-tag { display:inline-block; background:rgba(226,36,84,0.18); color:#ff7a9a; font-size:11px; font-weight:700; letter-spacing:2px; text-transform:uppercase; padding:5px 12px; border-radius:4px; margin-bottom:20px; border:1px solid rgba(226,36,84,0.25); }
        .hero h1 {
            font-family:'Playfair Display',serif;
            font-size: clamp(36px, 5vw, 62px);
            font-weight:900; color:white;
            letter-spacing:-2px; line-height:1.12;
            max-width:600px; margin-bottom:20px;
        }
        .hero h1 em { color:var(--red); font-style:italic; }
        .hero-sub { color:rgba(255,255,255,0.50); font-size:16px; line-height:1.7; max-width:480px; }

        /* ── CONTENT ── */
        .wrap { max-width:1120px; margin:0 auto; padding:64px 40px 80px; }

        /* ── FEATURED POST ── */
        .featured {
            display:grid; grid-template-columns:1fr 1fr;
            gap:0; border-radius:22px; overflow:hidden;
            box-shadow:0 8px 40px rgba(0,0,0,0.10);
            margin-bottom:64px; background:white;
            border:1px solid var(--line);
        }
        .featured-img {
            position:relative; overflow:hidden; min-height:400px;
        }
        .featured-img img { width:100%; height:100%; object-fit:cover; transition:transform 0.6s; }
        .featured:hover .featured-img img { transform:scale(1.04); }
        .featured-img-ph { width:100%; height:100%; min-height:400px; background:linear-gradient(145deg,#1c1c28,#2d1540); display:flex; align-items:center; justify-content:center; font-size:80px; }
        .featured-body { padding:48px 44px; display:flex; flex-direction:column; justify-content:center; }
        .featured-label { font-size:11px; font-weight:700; letter-spacing:2px; text-transform:uppercase; color:var(--red); margin-bottom:14px; }
        .featured-title { font-family:'Playfair Display',serif; font-size:28px; font-weight:900; color:var(--ink); line-height:1.3; margin-bottom:16px; letter-spacing:-0.5px; }
        .featured-excerpt { color:var(--muted); font-size:15px; line-height:1.75; flex:1; margin-bottom:28px; }
        .featured-meta { display:flex; align-items:center; gap:14px; font-size:13px; color:var(--muted); border-top:1px solid var(--line); padding-top:20px; margin-bottom:24px; }
        .author-dot { width:7px; height:7px; background:var(--red); border-radius:50%; }

        /* ── GRID ── */
        .section-label {
            font-size:11px; font-weight:700; letter-spacing:2.5px; text-transform:uppercase;
            color:var(--muted); margin-bottom:28px;
            display:flex; align-items:center; gap:12px;
        }
        .section-label::after { content:''; flex:1; height:1px; background:var(--line); }

        .grid { display:grid; grid-template-columns:repeat(3,1fr); gap:28px; }

        .card {
            background:white; border-radius:16px;
            border:1px solid var(--line);
            overflow:hidden;
            transition:transform 0.25s, box-shadow 0.25s;
            display:flex; flex-direction:column;
        }
        .card:hover { transform:translateY(-5px); box-shadow:0 18px 48px rgba(0,0,0,0.11); }

        .card-img { height:185px; overflow:hidden; position:relative; }
        .card-img img { width:100%; height:100%; object-fit:cover; transition:transform 0.5s; }
        .card:hover .card-img img { transform:scale(1.06); }
        .card-img-ph { width:100%; height:185px; background:linear-gradient(135deg,#ffe4ea,#ffe0ec); display:flex; align-items:center; justify-content:center; font-size:44px; }

        .card-body { padding:22px 22px 20px; flex:1; display:flex; flex-direction:column; }
        .card-meta { font-size:11px; color:var(--muted); margin-bottom:10px; display:flex; gap:10px; }
        .card-title { font-family:'Playfair Display',serif; font-size:17px; font-weight:700; color:var(--ink); line-height:1.4; margin-bottom:10px; letter-spacing:-0.2px; }
        .card-excerpt { font-size:13px; color:var(--muted); line-height:1.7; flex:1; margin-bottom:18px; }
        .read-btn {
            display:inline-flex; align-items:center; gap:6px;
            font-size:13px; font-weight:600; color:var(--red);
            text-decoration:none; transition:gap 0.2s;
        }
        .read-btn:hover { gap:10px; }

        /* ── EMPTY ── */
        .empty { text-align:center; padding:80px 20px; color:var(--muted); grid-column:1/-1; }
        .empty .e-icon { font-size:60px; margin-bottom:14px; }
        .empty h3 { font-family:'Playfair Display',serif; font-size:22px; color:var(--ink); margin-bottom:8px; }

        /* ── FOOTER ── */
        .footer { background:var(--dark); color:rgba(255,255,255,0.35); text-align:center; padding:32px; font-size:13px; margin-top:0; }
        .footer a { color:rgba(255,255,255,0.55); text-decoration:none; }

        @media(max-width:960px) {
            .featured { grid-template-columns:1fr; }
            .featured-img { min-height:260px; }
            .grid { grid-template-columns:1fr 1fr; }
        }
        @media(max-width:600px) {
            .wrap { padding:40px 20px 60px; }
            .nav  { padding:0 20px; }
            .hero { padding:60px 20px; }
            .featured-body { padding:28px 24px; }
            .grid { grid-template-columns:1fr; }
        }
    </style>
</head>
<body>

<?php include 'includes/config.php';
$all = $conn->query("SELECT * FROM blogs ORDER BY created_at DESC");
$posts = $all->fetch_all(MYSQLI_ASSOC);
$featured = !empty($posts) ? $posts[0] : null;
$rest = array_slice($posts, 1);
?>

<nav class="nav">
    <a href="index.php" class="nav-brand">
        <div class="nav-brand-icon">🏥</div>
        <span class="nav-brand-text">HealthCare Plus</span>
    </a>
    <div class="nav-right">
        <a href="search.php">Find Doctor</a>
        <a href="novena/index.html">HOME</a>
        <a href="login.php" class="btn-login">Sign In</a>
    </div>
</nav>

<div class="hero">
    <div class="hero-bg"></div>
    <div class="hero-lines"></div>
    <div class="hero-inner">
        <div class="hero-tag">Health & Wellness</div>
        <h1>Expert Insights for <em>Better Health</em></h1>
        <p class="hero-sub">Doctor-authored articles on health, wellness, prevention and care — written to help you live better.</p>
    </div>
</div>

<div class="wrap">

<?php if (empty($posts)): ?>
    <div class="grid">
        <div class="empty">
            <div class="e-icon">📰</div>
            <h3>No articles yet</h3>
            <p>Check back soon for health tips and insights.</p>
        </div>
    </div>

<?php else: ?>

    <?php
    // FEATURED
    $f     = $featured;
    $f_img = !empty($f['image']) ? 'uploads/blogs/'.htmlspecialchars($f['image']) : null;
    $f_exc = substr(strip_tags($f['content']), 0, 180).'...';
    ?>
    <div class="featured">
        <div class="featured-img">
            <?php if ($f_img && file_exists($f_img)): ?>
                <img src="<?php echo $f_img; ?>" alt="<?php echo htmlspecialchars($f['title']); ?>">
            <?php else: ?>
                <div class="featured-img-ph">🩺</div>
            <?php endif; ?>
        </div>
        <div class="featured-body">
            <div class="featured-label">Featured Article</div>
            <h2 class="featured-title"><?php echo htmlspecialchars($f['title']); ?></h2>
            <p class="featured-excerpt"><?php echo htmlspecialchars($f_exc); ?></p>
            <div class="featured-meta">
                <div class="author-dot"></div>
                <span><?php echo htmlspecialchars($f['author'] ?? 'HealthCare Plus'); ?></span>
                <span>·</span>
                <span><?php echo date('M d, Y', strtotime($f['created_at'])); ?></span>
            </div>
            <a href="blog_post.php?id=<?php echo $f['id']; ?>" class="read-btn" style="background:var(--red);color:white;padding:12px 24px;border-radius:50px;font-weight:700;font-size:14px;text-decoration:none;align-self:flex-start;letter-spacing:0.3px;">
                Read Article →
            </a>
        </div>
    </div>

    <?php if (!empty($rest)): ?>
    <div class="section-label">More Articles</div>
    <div class="grid">
    <?php foreach ($rest as $b):
        $img = !empty($b['image']) ? 'uploads/blogs/'.htmlspecialchars($b['image']) : null;
        $exc = substr(strip_tags($b['content']), 0, 110).'...';
    ?>
        <div class="card">
            <div class="card-img">
                <?php if ($img && file_exists($img)): ?>
                    <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($b['title']); ?>">
                <?php else: ?>
                    <div class="card-img-ph">🏥</div>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="card-meta">
                    <span>✍️ <?php echo htmlspecialchars($b['author'] ?? 'HealthCare Plus'); ?></span>
                    <span>·</span>
                    <span><?php echo date('M d', strtotime($b['created_at'])); ?></span>
                </div>
                <h3 class="card-title"><?php echo htmlspecialchars($b['title']); ?></h3>
                <p class="card-excerpt"><?php echo htmlspecialchars($exc); ?></p>
                <a href="blog_post.php?id=<?php echo $b['id']; ?>" class="read-btn">Read more →</a>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>

<?php endif; ?>
</div>

<div class="footer">
    © <?php echo date('Y'); ?> HealthCare Plus &nbsp;·&nbsp; <a href="index.php">Find a Doctor</a> &nbsp;·&nbsp; <a href="login.php">Login</a>
</div>

</body>
</html>
