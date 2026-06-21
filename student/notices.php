<?php
require_once '../includes/config.php';
requireLogin('student');
$notices = $conn->query("SELECT n.*, u.name as posted_by FROM notices n JOIN users u ON n.posted_by=u.id ORDER BY n.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Notices - Student</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php require_once 'sidebar.php'; ?>
<div class="topbar">
    <div class="topbar-left">
        <button class="hamburger" onclick="toggleSidebar()">☰</button>
        <span class="page-title">Notices</span>
    </div>
    <div class="topbar-right"><?php require_once '../notifications/widget.php'; ?></div>
</div>
<div class="main-content">
    <div class="card">
        <h2>📢 Notice Board</h2>
        <?php while($n = $notices->fetch_assoc()): ?>
        <div class="notice-item">
            <h4><?= htmlspecialchars($n['title']) ?></h4>
            <p style="margin:8px 0;color:#555"><?= nl2br(htmlspecialchars($n['content'])) ?></p>
            <div class="date">Posted by <?= htmlspecialchars($n['posted_by']) ?> · <?= date('d M Y', strtotime($n['created_at'])) ?></div>
        </div>
        <?php endwhile; ?>
    </div>
</div>
</div><!-- app-layout -->
<script>
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('show');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('sidebarOverlay').classList.remove('show');}
</script>
</body>
</html>
