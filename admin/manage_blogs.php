<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php"); exit();
}
include '../includes/config.php';

// ── DELETE ──
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $s = $conn->prepare("SELECT image FROM blogs WHERE id = ?");
    $s->bind_param("i", $id); $s->execute();
    $row = $s->get_result()->fetch_assoc(); $s->close();
    if (!empty($row['image'])) @unlink('../uploads/blogs/' . $row['image']);
    $d = $conn->prepare("DELETE FROM blogs WHERE id = ?");
    $d->bind_param("i", $id); $d->execute(); $d->close();
    $_SESSION['success'] = "Blog deleted.";
    header("Location: manage_blogs.php"); exit();
}

// ── ADD ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $title   = sanitize($conn, $_POST['title']);
    $author  = sanitize($conn, $_POST['author']);
    $content = sanitize($conn, $_POST['content']);
    $image   = '';
    if (!empty($_FILES['image']['name'])) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','webp']) && $_FILES['image']['size'] < 3*1024*1024) {
            $image = 'blog_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], '../uploads/blogs/' . $image);
        }
    }
    $s = $conn->prepare("INSERT INTO blogs (title, content, author, image) VALUES (?,?,?,?)");
    $s->bind_param("ssss", $title, $content, $author, $image); $s->execute(); $s->close();
    logActivity($conn, $_SESSION['user_id'], 'Add Blog', 'Published: ' . $title);
    $_SESSION['success'] = "Blog published!";
    header("Location: manage_blogs.php"); exit();
}

// ── EDIT ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit'])) {
    $id      = intval($_POST['id']);
    $title   = sanitize($conn, $_POST['title']);
    $author  = sanitize($conn, $_POST['author']);
    $content = sanitize($conn, $_POST['content']);
    $s = $conn->prepare("SELECT image FROM blogs WHERE id = ?");
    $s->bind_param("i", $id); $s->execute();
    $image = $s->get_result()->fetch_assoc()['image'] ?? ''; $s->close();
    if (!empty($_FILES['image']['name'])) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','webp']) && $_FILES['image']['size'] < 3*1024*1024) {
            if ($image) @unlink('../uploads/blogs/' . $image);
            $image = 'blog_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], '../uploads/blogs/' . $image);
        }
    }
    $s = $conn->prepare("UPDATE blogs SET title=?, content=?, author=?, image=? WHERE id=?");
    $s->bind_param("ssssi", $title, $content, $author, $image, $id); $s->execute(); $s->close();
    logActivity($conn, $_SESSION['user_id'], 'Edit Blog', 'Edited: ' . $title);
    $_SESSION['success'] = "Blog updated!";
    header("Location: manage_blogs.php"); exit();
}

$blogs = $conn->query("SELECT * FROM blogs ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Blogs - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .blog-row { display:flex; gap:16px; align-items:flex-start; padding:18px 0; border-bottom:1px solid #f0f0f0; }
        .blog-row:last-child { border:none; }
        .blog-thumb { width:100px; height:72px; border-radius:10px; flex-shrink:0; overflow:hidden; background:linear-gradient(135deg,#fde8ee,#ffc2d1); display:flex; align-items:center; justify-content:center; font-size:26px; }
        .blog-thumb img { width:100%; height:100%; object-fit:cover; }
        .blog-info { flex:1; min-width:0; }
        .blog-info h3 { font-size:15px; font-weight:700; color:#1a1a2e; margin-bottom:3px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .blog-info .meta { font-size:12px; color:#aaa; margin-bottom:5px; }
        .blog-info .excerpt { font-size:13px; color:#777; line-height:1.5; }
        .blog-actions { display:flex; gap:6px; flex-shrink:0; align-items:flex-start; }

        /* Modal */
        .modal-bg { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.55); backdrop-filter:blur(5px); z-index:1000; align-items:center; justify-content:center; padding:20px; }
        .modal-bg.open { display:flex; }
        .modal-box { background:white; border-radius:20px; padding:32px 30px; width:100%; max-width:600px; max-height:88vh; overflow-y:auto; box-shadow:0 24px 80px rgba(0,0,0,0.22); animation:mIn 0.28s cubic-bezier(0.34,1.56,0.64,1); position:relative; }
        @keyframes mIn { from{opacity:0;transform:translateY(24px) scale(0.97)} to{opacity:1;transform:none} }
        .modal-close { position:absolute; top:16px; right:16px; width:30px; height:30px; border-radius:50%; border:none; background:#f5f5f5; cursor:pointer; font-size:16px; color:#888; display:flex; align-items:center; justify-content:center; transition:all 0.2s; }
        .modal-close:hover { background:#fee2e2; color:#e22454; }
        .modal-box h2 { font-family:'Outfit',sans-serif; font-size:20px; font-weight:900; color:#1a1a2e; margin-bottom:22px; padding-right:36px; }
        .mfield { margin-bottom:14px; }
        .mfield label { display:block; font-size:11px; font-weight:700; color:#888; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:5px; }
        .mfield input, .mfield textarea { width:100%; padding:11px 14px; background:#f6f6f6; border:2px solid transparent; border-radius:9px; font-size:14px; font-family:'Sora',sans-serif; color:#1a1a2e; transition:all 0.2s; }
        .mfield input:focus, .mfield textarea:focus { outline:none; background:white; border-color:#dc2626; box-shadow:0 0 0 3px rgba(220,38,38,0.09); }
        .mfield textarea { resize:vertical; min-height:160px; }
        .img-upload-box { border:2px dashed #e0e0e0; border-radius:10px; padding:16px; text-align:center; cursor:pointer; transition:border-color 0.2s; margin-top:4px; }
        .img-upload-box:hover { border-color:#dc2626; }
        .img-upload-box input { display:none; }
        .img-preview-wrap { margin-top:10px; display:none; }
        .img-preview-wrap img { width:100%; max-height:180px; object-fit:cover; border-radius:8px; }
        .modal-foot { display:flex; gap:10px; justify-content:flex-end; margin-top:22px; }
    </style>
</head>
<body class="admin-theme">
<nav class="navbar">
    <div class="main-content">
        <a href="dashboard.php" class="navbar-brand">🏥 Admin Panel</a>
        <ul class="navbar-menu">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="manage_doctors.php">Doctors</a></li>
            <li><a href="manage_blogs.php" style="background:rgba(255,255,255,0.2);">Blogs</a></li>
            <li><a href="../logout.php">Logout</a></li>
        </ul>
    </div>
</nav>
<div class="dashboard-container">
<div class="dashboard-container">
    <div ><div class="sidebar">
        <ul>
            <li><a href="dashboard.php">📊 Dashboard</a></li>
            <li><a href="manage_patients.php">👥 Patients</a></li>
            <li><a href="manage_doctors.php">👨‍⚕️ Doctors</a></li>
            <li><a href="manage_pharmacists.php">💊 Pharmacists</a></li>
            <li><a href="manage_appointments.php">📅 Appointments</a></li>
            <li><a href="manage_hospitals.php">🏥 Hospitals</a></li>
            <li><a href="manage_blogs.php" class="active">📰 Blogs</a></li>
            <li><a href="activity_logs.php">📋 Activity Logs</a></li>
            <li><a href="reports.php">📊 Reports</a></li>
        </ul>
    </div></div>
    <main >
        <div class="page-header">
            <h1>📰 Manage Blogs</h1>
            <p>Publish and manage health articles for patients</p>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
                All Blogs <span style="background:#dc2626;color:white;padding:2px 12px;border-radius:12px;font-size:13px;"><?php echo $blogs->num_rows; ?></span>
                <div style="display:flex;gap:8px;">
                    <a href="../blogs.php" target="_blank" class="btn btn-secondary btn-sm">🌐 View Live</a>
                    <button class="btn btn-primary btn-sm" onclick="openAdd()">+ New Blog</button>
                </div>
            </div>

            <?php if ($blogs->num_rows > 0): while ($b = $blogs->fetch_assoc()):
                $img = !empty($b['image']) ? '../uploads/blogs/'.htmlspecialchars($b['image']) : null;
                $excerpt = substr(strip_tags($b['content']), 0, 110).'...';
            ?>
            <div class="blog-row">
                <div class="blog-thumb">
                    <?php if ($img && file_exists($img)): ?>
                        <img src="<?php echo $img; ?>">
                    <?php else: ?> 🏥 <?php endif; ?>
                </div>
                <div class="blog-info">
                    <h3><?php echo htmlspecialchars($b['title']); ?></h3>
                    <div class="meta">✍️ <?php echo htmlspecialchars($b['author'] ?? '—'); ?> &nbsp;·&nbsp; 📅 <?php echo date('M d, Y', strtotime($b['created_at'])); ?></div>
                    <div class="excerpt"><?php echo htmlspecialchars($excerpt); ?></div>
                </div>
                <div class="blog-actions">
                    <button class="btn btn-info btn-sm" onclick='openEdit(<?php echo json_encode($b, JSON_HEX_QUOT|JSON_HEX_APOS); ?>)'>✏️ Edit</button>
                    <a href="?delete=<?php echo $b['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this blog?')">🗑️</a>
                    <a href="../blog_post.php?id=<?php echo $b['id']; ?>" target="_blank" class="btn btn-secondary btn-sm">👁️</a>
                </div>
            </div>
            <?php endwhile; else: ?>
                <p style="color:#bbb;text-align:center;padding:48px;">No blogs yet. Publish your first article!</p>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- ADD MODAL -->
<div class="modal-bg" id="addModal">
    <div class="modal-box">
        <button class="modal-close" onclick="closeModal('addModal')">×</button>
        <h2>+ Publish New Blog</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="add" value="1">
            <div class="mfield"><label>Title *</label><input type="text" name="title" required placeholder="Blog title"></div>
            <div class="mfield"><label>Author</label><input type="text" name="author" placeholder="e.g. Dr. Nimal Silva"></div>
            <div class="mfield"><label>Content *</label><textarea name="content" required placeholder="Write your article..."></textarea></div>
            <div class="mfield">
                <label>Cover Image <small style="font-weight:normal;color:#bbb;">JPG/PNG/WEBP · max 3MB</small></label>
                <div class="img-upload-box" onclick="document.getElementById('addImgInput').click()">
                    <input type="file" id="addImgInput" name="image" accept="image/*" onchange="previewImg(this,'addPreviewWrap','addPreviewImg')">
                    <div>📷 Click to choose image</div>
                </div>
                <div class="img-preview-wrap" id="addPreviewWrap"><img id="addPreviewImg"></div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-glass" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" class="btn btn-glass-primary">Publish</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal-bg" id="editModal">
    <div class="modal-box">
        <button class="modal-close" onclick="closeModal('editModal')">×</button>
        <h2>✏️ Edit Blog</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="edit" value="1">
            <input type="hidden" name="id" id="eId">
            <div class="mfield"><label>Title *</label><input type="text" name="title" id="eTitle" required></div>
            <div class="mfield"><label>Author</label><input type="text" name="author" id="eAuthor"></div>
            <div class="mfield"><label>Content *</label><textarea name="content" id="eContent" required></textarea></div>
            <div class="mfield">
                <label>Replace Image <small style="font-weight:normal;color:#bbb;">leave blank to keep current</small></label>
                <div class="img-upload-box" onclick="document.getElementById('editImgInput').click()">
                    <input type="file" id="editImgInput" name="image" accept="image/*" onchange="previewImg(this,'editPreviewWrap','editPreviewImg')">
                    <div>📷 Click to change image</div>
                </div>
                <div class="img-preview-wrap" id="editPreviewWrap"><img id="editPreviewImg"></div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-glass" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="btn btn-glass-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAdd()  { document.getElementById('addModal').classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

function openEdit(b) {
    document.getElementById('eId').value      = b.id;
    document.getElementById('eTitle').value   = b.title;
    document.getElementById('eAuthor').value  = b.author  || '';
    document.getElementById('eContent').value = b.content || '';
    document.getElementById('editPreviewWrap').style.display = 'none';
    document.getElementById('editModal').classList.add('open');
}

function previewImg(input, wrapId, imgId) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById(imgId).src          = e.target.result;
        document.getElementById(wrapId).style.display = 'block';
    };
    reader.readAsDataURL(input.files[0]);
}

// Close on backdrop click
['addModal','editModal'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) closeModal(id);
    });
});
</script>
</body>
</html>
