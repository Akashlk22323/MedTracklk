<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog - HealthCare Plus</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,900;1,700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
        :root { --red:#8b1e3f; --dark:#0f0f14; --ink:#1c1c28; --muted:#6b7280; --line:#e8e8ed; --bg:#fafafa; }
        body { font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--ink); }

        .nav { position:sticky; top:0; z-index:100; background:rgba(255,255,255,0.92); backdrop-filter:blur(14px); border-bottom:1px solid var(--line); padding:0 40px; height:62px; display:flex; align-items:center; justify-content:space-between; }
        .nav-brand { display:flex; align-items:center; gap:10px; text-decoration:none; }
        .nav-brand-icon { width:34px; height:34px; background:var(--red); border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:18px; }
        .nav-brand-text { font-family:'Playfair Display',serif; font-size:17px; font-weight:700; color:var(--ink); }
        .nav-right { display:flex; gap:6px; }
        .nav-right a { color:var(--muted); text-decoration:none; padding:7px 13px; border-radius:7px; font-size:14px; font-weight:500; transition:all 0.18s; }
        .nav-right a:hover { color:var(--ink); background:#f3f3f6; }

        /* HERO IMAGE */
        .post-hero { width:100%; height:480px; overflow:hidden; position:relative; background:var(--dark); }
        .post-hero img { width:100%; height:100%; object-fit:cover; opacity:0.85; }
        .post-hero-ph { width:100%; height:480px; background:linear-gradient(145deg,#1c1c28,#2d1540,#1a0a1e); display:flex; align-items:center; justify-content:center; font-size:100px; }
        .post-hero-overlay { position:absolute; inset:0; background:linear-gradient(to top, rgba(15,15,20,0.75) 0%, transparent 60%); }

        /* CONTENT */
        .wrap { max-width:720px; margin:0 auto; padding:56px 24px 80px; }

        .back { display:inline-flex; align-items:center; gap:7px; color:var(--muted); text-decoration:none; font-size:13px; font-weight:600; margin-bottom:36px; transition:color 0.2s; }
        .back:hover { color:var(--red); }

        .post-meta { display:flex; align-items:center; gap:10px; margin-bottom:18px; flex-wrap:wrap; }
        .meta-chip { background:#ffe4ea; color:var(--red); font-size:11px; font-weight:700; letter-spacing:1.5px; text-transform:uppercase; padding:4px 10px; border-radius:4px; }
        .meta-author { font-size:14px; font-weight:600; color:var(--ink); }
        .meta-dot { width:4px; height:4px; border-radius:50%; background:var(--muted); }
        .meta-date { font-size:14px; color:var(--muted); }

        .post-title {
            font-family:'Playfair Display',serif;
            font-size: clamp(28px, 4vw, 44px);
            font-weight:900; letter-spacing:-1.5px;
            line-height:1.18; color:var(--ink);
            margin-bottom:24px;
        }

        .post-divider { display:flex; gap:6px; margin-bottom:36px; }
        .post-divider span:nth-child(1) { width:48px; height:4px; background:var(--red); border-radius:2px; }
        .post-divider span:nth-child(2) { width:12px; height:4px; background:#fca5a5; border-radius:2px; }
        .post-divider span:nth-child(3) { width:6px;  height:4px; background:#ffe4ea; border-radius:2px; }

        .post-body {
            font-size:17px; line-height:1.88; color:#374151;
            white-space:pre-wrap;
        }
        .post-body p { margin-bottom:22px; }

        /* MORE POSTS */
        .more { margin-top:64px; padding-top:40px; border-top:1px solid var(--line); }
        .more h3 { font-family:'Playfair Display',serif; font-size:22px; font-weight:700; margin-bottom:24px; }
        .more-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:20px; }
        .more-card { text-decoration:none; color:inherit; display:block; }
        .more-card-img { height:130px; border-radius:10px; overflow:hidden; background:linear-gradient(135deg,#ffe4ea,#ffe0ec); display:flex; align-items:center; justify-content:center; font-size:36px; margin-bottom:10px; }
        .more-card-img img { width:100%; height:100%; object-fit:cover; }
        .more-card-title { font-family:'Playfair Display',serif; font-size:14px; font-weight:700; line-height:1.4; color:var(--ink); transition:color 0.2s; }
        .more-card:hover .more-card-title { color:var(--red); }

        .footer { background:var(--dark); color:rgba(255,255,255,0.35); text-align:center; padding:32px; font-size:13px; }
        .footer a { color:rgba(255,255,255,0.55); text-decoration:none; }

        @media(max-width:600px) {
            .post-hero { height:260px; }
            .post-hero-ph { height:260px; }
            .nav { padding:0 20px; }
            .more-grid { grid-template-columns:1fr 1fr; }
        }
    </style>
</head>
<body>

<?php
include 'includes/config.php';
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header("Location: blogs.php"); exit(); }

$s = $conn->prepare("SELECT * FROM blogs WHERE id = ?");
$s->bind_param("i", $id); $s->execute();
$b = $s->get_result()->fetch_assoc(); $s->close();
if (!$b) { header("Location: blogs.php"); exit(); }

$img = !empty($b['image']) ? 'uploads/blogs/'.htmlspecialchars($b['image']) : null;

// More posts (exclude current)
$ms = $conn->prepare("SELECT id, title, image FROM blogs WHERE id != ? ORDER BY created_at DESC LIMIT 3");
$ms->bind_param("i", $id); $ms->execute();
$more = $ms->get_result()->fetch_all(MYSQLI_ASSOC); $ms->close();
?>

<nav class="nav">
    <a href="index.php" class="nav-brand">
        <div class="nav-brand-icon">🏥</div>
        <span class="nav-brand-text">HealthCare Plus</span>
    </a>
    <div class="nav-right">
        <a href="blogs.php">← All Articles</a>
        <a href="login.php">Login</a>
    </div>
</nav>

<!-- HERO IMAGE -->
<div class="post-hero">
    <?php if ($img && file_exists($img)): ?>
        <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($b['title']); ?>">
        <div class="post-hero-overlay"></div>
    <?php else: ?>
        <div class="post-hero-ph">🩺</div>
    <?php endif; ?>
</div>

<div class="wrap">
    <a href="blogs.php" class="back">← Back to Blog</a>

    <div class="post-meta">
        <span class="meta-chip">Health Article</span>
        <span class="meta-author">✍️ <?php echo htmlspecialchars($b['author'] ?? 'HealthCare Plus'); ?></span>
        <span class="meta-dot"></span>
        <span class="meta-date"><?php echo date('F d, Y', strtotime($b['created_at'])); ?></span>
    </div>

    <h1 class="post-title"><?php echo htmlspecialchars($b['title']); ?></h1>

    <div class="post-divider"><span></span><span></span><span></span></div>

    <div class="post-body"><?php echo nl2br(htmlspecialchars($b['content'])); ?></div>

    <!-- MORE POSTS -->
    <?php if (!empty($more)): ?>
    <div class="more">
        <h3>More Articles</h3>
        <div class="more-grid">
            <?php foreach ($more as $m):
                $mi = !empty($m['image']) ? 'uploads/blogs/'.htmlspecialchars($m['image']) : null;
            ?>
            <a href="blog_post.php?id=<?php echo $m['id']; ?>" class="more-card">
                <div class="more-card-img">
                    <?php if ($mi && file_exists($mi)): ?>
                        <img src="<?php echo $mi; ?>">
                    <?php else: ?> 🏥 <?php endif; ?>
                </div>
                <div class="more-card-title"><?php echo htmlspecialchars($m['title']); ?></div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="footer">
    © <?php echo date('Y'); ?> HealthCare Plus &nbsp;·&nbsp; <a href="index.php">Find a Doctor</a> &nbsp;·&nbsp; <a href="blogs.php">Blog</a>
</div>

</body>
</html>
