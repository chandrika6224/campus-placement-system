<?php
require_once '../includes/config.php';
require_once '../includes/notify.php';
requireLogin();

$uid  = (int)$_SESSION['user_id'];
$role = $_SESSION['role'];

ensureNotificationsTable($conn);

// Mark all read if requested
if (isset($_GET['mark_all'])) {
    $conn->query("UPDATE notifications SET is_read=1 WHERE user_id=$uid");
    header("Location: index.php");
    exit();
}

$filter = $_GET['filter'] ?? 'all';
$where  = "WHERE user_id=$uid";
if ($filter !== 'all') $where .= " AND type='$filter'";

$notifications = $conn->query("SELECT * FROM notifications $where ORDER BY created_at DESC LIMIT 100");
$unread = $conn->query("SELECT COUNT(*) as c FROM notifications WHERE user_id=$uid AND is_read=0")->fetch_assoc()['c'];

$typeIcons  = ['job'=>'💼','application'=>'📋','interview'=>'🎥','test'=>'📝','notice'=>'📢','system'=>'🔔'];
$typeColors = ['job'=>'#1565c0','application'=>'#2e7d32','interview'=>'#6a1b9a','test'=>'#e65100','notice'=>'#c62828','system'=>'#455a64'];
$typeBg     = ['job'=>'#e3f2fd','application'=>'#e8f5e9','interview'=>'#f3e5f5','test'=>'#fff8e1','notice'=>'#ffebee','system'=>'#eceff1'];

// Dashboard link based on role
$dashLink = $role === 'admin' ? '../admin/dashboard.php' : ($role === 'recruiter' ? '../recruiter/dashboard.php' : '../student/dashboard.php');

function timeAgo($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'Just now';
    if ($diff < 3600)   return floor($diff/60) . ' min ago';
    if ($diff < 86400)  return floor($diff/3600) . ' hours ago';
    if ($diff < 604800) return floor($diff/86400) . ' days ago';
    return date('d M Y', strtotime($datetime));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notifications</title>
<link rel="stylesheet" href="../css/style.css">
<style>
.notif-item { display:flex;gap:14px;align-items:flex-start;padding:15px 18px;border-radius:10px;margin-bottom:10px;border:1px solid #e0e0e0;transition:all 0.2s;cursor:pointer; }
.notif-item:hover { box-shadow:0 2px 10px rgba(0,0,0,0.08);transform:translateY(-1px); }
.notif-item.unread { border-left:4px solid #3f51b5;background:#f8f9ff; }
.notif-item.read { background:#fff;opacity:0.8; }
.notif-icon { width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0; }
.notif-body { flex:1; }
.notif-title { font-weight:700;color:#1a237e;font-size:0.95rem;margin-bottom:4px; }
.notif-msg { color:#555;font-size:0.88rem;line-height:1.5; }
.notif-time { font-size:0.78rem;color:#999;margin-top:5px; }
.filter-tab { padding:7px 18px;border-radius:20px;border:2px solid #e0e0e0;background:#fff;color:#555;font-weight:600;cursor:pointer;font-size:0.85rem;text-decoration:none;transition:all 0.2s;display:inline-block; }
.filter-tab:hover,.filter-tab.active { background:#3f51b5;color:#fff;border-color:#3f51b5; }
</style>
</head>
<body>
<nav class="navbar">
    <a href="<?= $dashLink ?>" class="brand">🎓 Campus<span>Recruit</span></a>
    <div class="nav-links">
        <a href="<?= $dashLink ?>">← Dashboard</a>
        <a href="index.php" class="active">🔔 Notifications</a>
    </div>
</nav>

<div class="container">
    <div class="card" style="background:linear-gradient(135deg,#1a237e,#3949ab);color:#fff;margin-bottom:25px">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:15px">
            <div>
                <h2 style="color:#ffd54f;margin-bottom:8px">🔔 Notifications</h2>
                <p style="color:#c5cae9">Stay updated on jobs, applications, interviews, and tests.</p>
            </div>
            <?php if ($unread > 0): ?>
            <a href="?mark_all=1" class="btn" style="background:#ffd54f;color:#1a237e;border-radius:20px">✓ Mark All Read (<?= $unread ?>)</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px">
        <a href="?filter=all" class="filter-tab <?= $filter==='all'?'active':'' ?>">All</a>
        <a href="?filter=job" class="filter-tab <?= $filter==='job'?'active':'' ?>">💼 Jobs</a>
        <a href="?filter=application" class="filter-tab <?= $filter==='application'?'active':'' ?>">📋 Applications</a>
        <a href="?filter=interview" class="filter-tab <?= $filter==='interview'?'active':'' ?>">🎥 Interviews</a>
        <a href="?filter=test" class="filter-tab <?= $filter==='test'?'active':'' ?>">📝 Tests</a>
        <a href="?filter=notice" class="filter-tab <?= $filter==='notice'?'active':'' ?>">📢 Notices</a>
    </div>

    <?php if ($notifications->num_rows === 0): ?>
    <div class="card" style="text-align:center;padding:50px">
        <div style="font-size:4rem;margin-bottom:15px">🔔</div>
        <h3 style="color:#1a237e">No Notifications Yet</h3>
        <p style="color:#666;margin-top:8px">You'll be notified about new jobs, application updates, interviews, and tests.</p>
        <a href="<?= $dashLink ?>" class="btn btn-primary" style="margin-top:20px">← Back to Dashboard</a>
    </div>
    <?php else: ?>
    <div class="card">
        <?php while($n = $notifications->fetch_assoc()):
            $type = $n['type'];
            $isUnread = !$n['is_read'];
        ?>
        <div class="notif-item <?= $isUnread ? 'unread' : 'read' ?>"
             onclick="markRead(<?= $n['id'] ?>, '<?= htmlspecialchars(addslashes($n['link'])) ?>')">
            <div class="notif-icon" style="background:<?= $typeBg[$type] ?? '#f5f5f5' ?>">
                <?= $typeIcons[$type] ?? '🔔' ?>
            </div>
            <div class="notif-body">
                <div class="notif-title">
                    <?= htmlspecialchars($n['title']) ?>
                    <?php if ($isUnread): ?>
                    <span style="display:inline-block;width:8px;height:8px;background:#3f51b5;border-radius:50%;margin-left:6px;vertical-align:middle"></span>
                    <?php endif; ?>
                </div>
                <div class="notif-msg"><?= htmlspecialchars($n['message']) ?></div>
                <div class="notif-time">
                    <span style="background:<?= $typeBg[$type] ?? '#f5f5f5' ?>;color:<?= $typeColors[$type] ?? '#555' ?>;padding:2px 8px;border-radius:10px;font-size:0.75rem;font-weight:700;margin-right:8px"><?= ucfirst($type) ?></span>
                    <?= timeAgo($n['created_at']) ?>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once '../chatbot/widget.php'; ?>

<script>
function markRead(id, link) {
    fetch('../notifications/mark_read.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'action=one&id=' + id
    }).then(() => {
        if (link) window.location.href = link;
    });
}
</script>
</body>
</html>
