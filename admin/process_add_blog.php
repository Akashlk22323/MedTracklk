<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = sanitize($conn, $_POST['title']);
    $author = sanitize($conn, $_POST['author']);
    $content = sanitize($conn, $_POST['content']);

    $stmt = $conn->prepare("INSERT INTO blogs (title, content, author) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $title, $content, $author);
    
    if ($stmt->execute()) {
        logActivity($conn, $_SESSION['user_id'], 'Add Blog', 'Admin published new blog: ' . $title);
        $_SESSION['success'] = "Blog published successfully!";
    }

    header("Location: manage_blogs.php");
    exit();
}
?>
