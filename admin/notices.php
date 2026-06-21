<?php
require_once '../includes/config.php';
requireLogin('admin');
require_once '../includes/notify.php';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $title   = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $uid = $_SESSION['user_id'];
        $stmtN = $conn->prepare("INSERT INTO notices (title,content,posted_by) VALUES (?,?,?)");
        $stmtN->bind_param('ssi', $title, $content, $uid);
        $stmtN->execute(); $stmtN->close();
        notifyAllStudentsNewNotice($conn, $title);
        $msg = '<div class="alert alert-success">Notice posted successfully.</div>';
    }
    if (isset($_POST['delete'])) {
        $id = (int)$_POST['delete'];
        $conn->query("DELETE FROM notices WHERE id=$id");
        $msg = '<div class="alert alert-success">Notice deleted.</div>';
    }
}

$notices = $conn->query("SELECT n.*, u.name as posted_by_name FROM notices n JOIN users u ON n.posted_by=u.id ORDER BY n.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Notices - Admin</title>
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
    <?= $msg ?>
    <div class="card">
        <h2>Post New Notice</h2>
        <form method="POST">
            <div class="form-group">
                <label>Notice Title</label>
                <input type="text" name="title" placeholder="Enter notice title" required>
            </div>
            <div class="form-group">
                <label>Content</label>
                <textarea name="content" rows="4" placeholder="Enter notice content" required></textarea>
            </div>
            <button type="submit" name="add" class="btn btn-primary">Post Notice</button>
        </form>
    </div>

    <div class="card">
        <h2>All Notices</h2>
        <?php while($n = $notices->fetch_assoc()): ?>
        <div class="notice-item" style="display:flex;justify-content:space-between;align-items:flex-start">
            <div>
                <h4><?= htmlspecialchars($n['title']) ?></h4>
                <p style="font-size:0.9rem;color:#555;margin:5px 0"><?= nl2br(htmlspecialchars($n['content'])) ?></p>
                <div class="date">Posted by <?= htmlspecialchars($n['posted_by_name']) ?> on <?= date('d M Y', strtotime($n['created_at'])) ?></div>
            </div>
            <form method="POST" onsubmit="return confirm('Delete this notice?')">
                <input type="hidden" name="delete" value="<?= $n['id'] ?>">
                <button class="btn btn-danger btn-sm">Delete</button>
            </form>
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
