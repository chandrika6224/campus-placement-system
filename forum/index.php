<?php
require_once '../includes/config.php';
requireLogin();

$uid  = $_SESSION['user_id'];
$role = $_SESSION['role'];

// ── Tables ──────────────────────────────────────────────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS forum_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(10) DEFAULT '💬',
    description VARCHAR(255),
    sort_order INT DEFAULT 0
)");
$conn->query("CREATE TABLE IF NOT EXISTS forum_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    views INT DEFAULT 0,
    is_pinned TINYINT DEFAULT 0,
    is_locked TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES forum_categories(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");
$conn->query("CREATE TABLE IF NOT EXISTS forum_replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    is_solution TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES forum_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");
$conn->query("CREATE TABLE IF NOT EXISTS forum_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT DEFAULT NULL,
    reply_id INT DEFAULT NULL,
    user_id INT NOT NULL
)");
$conn->query("CREATE TABLE IF NOT EXISTS interview_experiences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    company_name VARCHAR(150) NOT NULL,
    job_role VARCHAR(150),
    interview_date DATE,
    difficulty ENUM('Easy','Medium','Hard') DEFAULT 'Medium',
    outcome ENUM('Selected','Rejected','Pending') DEFAULT 'Pending',
    rounds TEXT,
    experience TEXT NOT NULL,
    tips TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

if ($conn->query("SELECT COUNT(*) as c FROM forum_categories")->fetch_assoc()['c'] == 0) {
    $conn->query("INSERT INTO forum_categories (name, icon, description, sort_order) VALUES
        ('Interview Experiences','🎤','Share your interview experiences and tips',1),
        ('Technical Help','💻','Get help with coding and technical questions',2),
        ('General Discussion','💬','General placement-related discussions',3)");
}

$msg = '';
$activeTab = isset($_GET['tab']) && $_GET['tab'] === 'experiences' ? 'experiences' : 'forum';

// ── Interview Experience actions ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'post_exp') {
    $company = $conn->real_escape_string(trim($_POST['company_name']));
    $erole   = $conn->real_escape_string(trim($_POST['job_role']));
    $date    = $conn->real_escape_string($_POST['interview_date'] ?? '');
    $diff    = in_array($_POST['difficulty'], ['Easy','Medium','Hard']) ? $_POST['difficulty'] : 'Medium';
    $outcome = in_array($_POST['outcome'], ['Selected','Rejected','Pending']) ? $_POST['outcome'] : 'Pending';
    $rounds  = $conn->real_escape_string(trim($_POST['rounds']));
    $exp     = $conn->real_escape_string(trim($_POST['experience']));
    $tips    = $conn->real_escape_string(trim($_POST['tips']));
    if ($company && $exp) {
        $conn->query("INSERT INTO interview_experiences (user_id, company_name, job_role, interview_date, difficulty, outcome, rounds, experience, tips)
            VALUES ($uid, '$company', '$erole', " . ($date ? "'$date'" : "NULL") . ", '$diff', '$outcome', '$rounds', '$exp', '$tips')");
        $msg = '<div class="alert alert-success">✅ Your experience has been shared!</div>';
    }
    $activeTab = 'experiences';
}

if (isset($_GET['del_exp'])) {
    $did = (int)$_GET['del_exp'];
    $conn->query("DELETE FROM interview_experiences WHERE id=$did AND user_id=$uid");
    header("Location: index.php?tab=experiences"); exit();
}

// ── Forum actions ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_post'])) {
    $catId   = (int)$_POST['category_id'];
    $title   = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    if ($title && $content && $catId) {
        $stmtNP = $conn->prepare("INSERT INTO forum_posts (category_id, user_id, title, content) VALUES (?, ?, ?, ?)");
        $stmtNP->bind_param('iiss', $catId, $uid, $title, $content);
        $stmtNP->execute(); $stmtNP->close();
        $msg = '<div class="alert alert-success">Post created successfully!</div>';
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_reply'])) {
    $postId  = (int)$_POST['post_id'];
    $content = trim($_POST['content'] ?? '');
    if ($content && $postId) {
        $stmtNR = $conn->prepare("INSERT INTO forum_replies (post_id, user_id, content) VALUES (?, ?, ?)");
        $stmtNR->bind_param('iis', $postId, $uid, $content);
        $stmtNR->execute(); $stmtNR->close();
        header("Location: index.php?post=$postId"); exit();
    }
}
if (isset($_GET['solution'], $_GET['post'])) {
    $rid = (int)$_GET['solution']; $postId = (int)$_GET['post'];
    $post = $conn->query("SELECT user_id FROM forum_posts WHERE id=$postId")->fetch_assoc();
    if ($post && ($post['user_id'] == $uid || $role === 'admin')) {
        $conn->query("UPDATE forum_replies SET is_solution=0 WHERE post_id=$postId");
        $conn->query("UPDATE forum_replies SET is_solution=1 WHERE id=$rid");
    }
    header("Location: index.php?post=$postId"); exit();
}
if (isset($_GET['like_post'])) {
    $pid = (int)$_GET['like_post'];
    $exists = $conn->query("SELECT id FROM forum_likes WHERE post_id=$pid AND user_id=$uid AND reply_id IS NULL")->num_rows;
    if ($exists) $conn->query("DELETE FROM forum_likes WHERE post_id=$pid AND user_id=$uid AND reply_id IS NULL");
    else         $conn->query("INSERT INTO forum_likes (post_id, user_id) VALUES ($pid, $uid)");
    header("Location: index.php?post=$pid"); exit();
}
if (isset($_GET['del_post'])) {
    $pid = (int)$_GET['del_post'];
    $p   = $conn->query("SELECT user_id FROM forum_posts WHERE id=$pid")->fetch_assoc();
    if ($p && ($p['user_id'] == $uid || $role === 'admin')) $conn->query("DELETE FROM forum_posts WHERE id=$pid");
    header("Location: index.php"); exit();
}
if (isset($_GET['del_reply'])) {
    $rid = (int)$_GET['del_reply']; $postId = (int)($_GET['post'] ?? 0);
    $r   = $conn->query("SELECT user_id FROM forum_replies WHERE id=$rid")->fetch_assoc();
    if ($r && ($r['user_id'] == $uid || $role === 'admin')) $conn->query("DELETE FROM forum_replies WHERE id=$rid");
    header("Location: index.php?post=$postId"); exit();
}
if ($role === 'admin' && isset($_GET['pin'])) {
    $pid = (int)$_GET['pin'];
    $conn->query("UPDATE forum_posts SET is_pinned = 1 - is_pinned WHERE id=$pid");
    header("Location: index.php"); exit();
}
if ($role === 'admin' && isset($_GET['lock'])) {
    $pid = (int)$_GET['lock'];
    $conn->query("UPDATE forum_posts SET is_locked = 1 - is_locked WHERE id=$pid");
    header("Location: index.php?post=$pid"); exit();
}

// ── Single post view ─────────────────────────────────────────────────────────
$viewPost = null; $replies = null;
if (isset($_GET['post'])) {
    $pid = (int)$_GET['post'];
    $conn->query("UPDATE forum_posts SET views=views+1 WHERE id=$pid");
    $viewPost = $conn->query("SELECT fp.*, u.name, u.role as user_role,
        (SELECT COUNT(*) FROM forum_likes WHERE post_id=fp.id AND reply_id IS NULL) as likes,
        (SELECT COUNT(*) FROM forum_likes WHERE post_id=fp.id AND user_id=$uid AND reply_id IS NULL) as i_liked
        FROM forum_posts fp JOIN users u ON fp.user_id=u.id WHERE fp.id=$pid")->fetch_assoc();
    if ($viewPost) {
        $replies = $conn->query("SELECT fr.*, u.name, u.role as user_role,
            (SELECT COUNT(*) FROM forum_likes WHERE reply_id=fr.id) as likes
            FROM forum_replies fr JOIN users u ON fr.user_id=u.id
            WHERE fr.post_id=$pid ORDER BY fr.is_solution DESC, fr.created_at ASC");
    }
}

// ── Forum posts list ─────────────────────────────────────────────────────────
$catFilter  = (int)($_GET['cat'] ?? 0);
$search     = trim($_GET['q'] ?? '');
$where      = "WHERE 1";
if ($catFilter) $where .= " AND fp.category_id=$catFilter";
$postsTypes = ''; $postsArgs = [];
if ($search) {
    $like = '%'.$search.'%';
    $where .= " AND (fp.title LIKE ? OR fp.content LIKE ?)";
    $postsTypes .= 'ss'; $postsArgs[] = $like; $postsArgs[] = $like;
}
$postsSql = "SELECT fp.*, u.name, fc.name as cat_name, fc.icon as cat_icon,
    (SELECT COUNT(*) FROM forum_replies WHERE post_id=fp.id) as reply_count,
    (SELECT COUNT(*) FROM forum_likes WHERE post_id=fp.id AND reply_id IS NULL) as likes
    FROM forum_posts fp JOIN users u ON fp.user_id=u.id JOIN forum_categories fc ON fp.category_id=fc.id
    $where ORDER BY fp.is_pinned DESC, fp.created_at DESC LIMIT 30";
$stmtPosts = $conn->prepare($postsSql);
if ($stmtPosts) {
    if ($postsTypes) $stmtPosts->bind_param($postsTypes, ...$postsArgs);
    $stmtPosts->execute();
    $posts = $stmtPosts->get_result();
    $stmtPosts->close();
} else {
    // fallback: direct query when no params
    $posts = $conn->query($postsSql);
}
$categoriesRes = $conn->query("SELECT fc.*, (SELECT COUNT(*) FROM forum_posts WHERE category_id=fc.id) as post_count FROM forum_categories fc ORDER BY sort_order");
$categoriesArr = [];
while ($crow = $categoriesRes->fetch_assoc()) $categoriesArr[] = $crow;

// ── Interview experiences data ───────────────────────────────────────────────
$fc = trim($_GET['company'] ?? '');
$fo = trim($_GET['outcome'] ?? '');
$fd = trim($_GET['difficulty'] ?? '');
$ewhere = "WHERE 1";
if ($fc) $ewhere .= " AND ie.company_name LIKE '%".$conn->real_escape_string($fc)."%'";
if ($fo) $ewhere .= " AND ie.outcome='".$conn->real_escape_string($fo)."'";
if ($fd) $ewhere .= " AND ie.difficulty='".$conn->real_escape_string($fd)."'";
$experiences = $conn->query("SELECT ie.*, u.name as author FROM interview_experiences ie JOIN users u ON ie.user_id=u.id $ewhere ORDER BY ie.created_at DESC");
$my_exp_count    = $conn->query("SELECT COUNT(*) as c FROM interview_experiences WHERE user_id=$uid")->fetch_assoc()['c'];
$total_exp_count = $conn->query("SELECT COUNT(*) as c FROM interview_experiences")->fetch_assoc()['c'];

$diff_colors    = ['Easy'=>'#2e7d32','Medium'=>'#e65100','Hard'=>'#c62828'];
$diff_bg        = ['Easy'=>'#e8f5e9','Medium'=>'#fff8e1','Hard'=>'#ffebee'];
$outcome_colors = ['Selected'=>'#2e7d32','Rejected'=>'#c62828','Pending'=>'#1565c0'];
$outcome_bg     = ['Selected'=>'#e8f5e9','Rejected'=>'#ffebee','Pending'=>'#e3f2fd'];
$outcome_icon   = ['Selected'=>'✅','Rejected'=>'❌','Pending'=>'⏳'];

$dashLink   = ($role==='admin') ? '../admin/dashboard.php' : (($role==='recruiter') ? '../recruiter/dashboard.php' : '../student/dashboard.php');
$logoutLink = '../'.$role.'/logout.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forum & Interview Experiences</title>
<link rel="stylesheet" href="../css/style.css">
<style>
/* Tabs */
.tab-bar{display:flex;gap:0;border-bottom:2px solid #e8eaf6;margin-bottom:24px}
.tab-btn{padding:12px 28px;border:none;background:none;cursor:pointer;font-size:0.95rem;font-weight:600;color:#666;border-bottom:3px solid transparent;margin-bottom:-2px;transition:all 0.2s}
.tab-btn.active{color:#1a237e;border-bottom-color:#1a237e}
.tab-btn:hover{color:#1a237e}
.tab-pane{display:none}.tab-pane.active{display:block}
/* Forum */
.forum-layout{display:grid;grid-template-columns:230px 1fr;gap:20px}
.cat-item{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:8px;text-decoration:none;color:#333;margin-bottom:3px;transition:all 0.2s}
.cat-item:hover,.cat-item.active{background:#e8eaf6;color:#1a237e}
.post-card{background:#fff;border-radius:10px;padding:15px 18px;box-shadow:0 2px 8px rgba(0,0,0,0.07);margin-bottom:10px;border-left:4px solid #e0e0e0;transition:all 0.2s}
.post-card:hover{border-left-color:#3f51b5;transform:translateX(2px)}
.post-card.pinned{border-left-color:#ffd54f;background:#fffde7}
.post-title{font-size:0.98rem;font-weight:700;color:#1a237e;text-decoration:none}
.post-title:hover{text-decoration:underline}
.post-meta{font-size:0.77rem;color:#999;margin-top:5px;display:flex;gap:12px;flex-wrap:wrap}
.reply-card{background:#f8f9ff;border-radius:8px;padding:14px 16px;margin-bottom:10px;border-left:3px solid #c5cae9}
.reply-card.solution{border-left-color:#43a047;background:#e8f5e9}
.rbadge{font-size:0.7rem;padding:2px 7px;border-radius:10px;font-weight:700}
.rbadge.admin{background:#ffebee;color:#c62828}
.rbadge.student{background:#e3f2fd;color:#1565c0}
.rbadge.recruiter{background:#e8f5e9;color:#2e7d32}
/* Experience */
.exp-layout{display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start}
.exp-card{background:#fff;border-radius:12px;padding:22px;box-shadow:0 2px 12px rgba(0,0,0,0.07);margin-bottom:16px;border-left:5px solid #3f51b5;transition:transform 0.2s}
.exp-card:hover{transform:translateY(-2px)}
.badge-sm{display:inline-block;padding:3px 10px;border-radius:20px;font-size:0.78rem;font-weight:700}
.round-tag{display:inline-block;background:#e8eaf6;color:#3f51b5;padding:3px 10px;border-radius:20px;font-size:0.78rem;font-weight:600;margin:2px}
@media(max-width:900px){.forum-layout{grid-template-columns:1fr}.exp-layout{grid-template-columns:1fr}}
</style>
</head>
<body>
<nav class="navbar">
    <a href="<?= $dashLink ?>" class="brand">🎓 Campus<span>Recruit</span></a>
    <div class="nav-links">
        <a href="<?= $dashLink ?>">Dashboard</a>
        <a href="index.php" class="active">💬 Forum</a>
        <?php require_once '../notifications/widget.php'; ?>
        <a href="<?= $logoutLink ?>" class="btn-logout">Logout</a>
    </div>
</nav>

<div class="container">
    <?= $msg ?>

    <?php if ($viewPost): ?>
    <!-- ── Single Post View ── -->
    <div style="margin-bottom:15px">
        <a href="index.php" class="btn btn-sm" style="background:#e8eaf6;color:#333">← Back to Forum</a>
        <?php if ($role === 'admin'): ?>
        <a href="?lock=<?= $viewPost['id'] ?>" class="btn btn-sm btn-warning" style="margin-left:8px"><?= $viewPost['is_locked'] ? '🔓 Unlock' : '🔒 Lock' ?></a>
        <?php endif; ?>
    </div>
    <div class="card" style="margin-bottom:15px">
        <?php if ($viewPost['is_pinned']): ?><span style="background:#fff8e1;color:#f57f17;padding:2px 8px;border-radius:10px;font-size:0.75rem;font-weight:700;margin-right:6px">📌 Pinned</span><?php endif; ?>
        <?php if ($viewPost['is_locked']): ?><span style="background:#ffebee;color:#c62828;padding:2px 8px;border-radius:10px;font-size:0.75rem;font-weight:700;margin-right:6px">🔒 Locked</span><?php endif; ?>
        <h2 style="border:none;padding:0;margin-bottom:10px"><?= htmlspecialchars($viewPost['title']) ?></h2>
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;flex-wrap:wrap">
            <strong style="color:#1a237e"><?= htmlspecialchars($viewPost['name']) ?></strong>
            <span class="rbadge <?= $viewPost['user_role'] ?>"><?= ucfirst($viewPost['user_role']) ?></span>
            <span style="font-size:0.78rem;color:#999"><?= date('d M Y, h:i A', strtotime($viewPost['created_at'])) ?></span>
            <span style="font-size:0.78rem;color:#999">👁️ <?= $viewPost['views'] ?></span>
        </div>
        <div style="color:#444;line-height:1.75;font-size:0.95rem"><?= nl2br(htmlspecialchars($viewPost['content'])) ?></div>
        <div style="display:flex;gap:10px;margin-top:14px;padding-top:12px;border-top:1px solid #f0f0f0;flex-wrap:wrap">
            <a href="?like_post=<?= $viewPost['id'] ?>" class="btn btn-sm" style="background:<?= $viewPost['i_liked'] ? '#e8eaf6' : '#f5f5f5' ?>;color:#333">
                👍 <?= $viewPost['likes'] ?> Like<?= $viewPost['likes'] != 1 ? 's' : '' ?>
            </a>
            <?php if ($viewPost['user_id'] == $uid || $role === 'admin'): ?>
            <a href="?del_post=<?= $viewPost['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this post and all replies?')">🗑️ Delete Post</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="card">
        <h2><?= $replies->num_rows ?> Repl<?= $replies->num_rows != 1 ? 'ies' : 'y' ?></h2>
        <?php if ($replies->num_rows === 0): ?>
        <p style="color:#999;text-align:center;padding:20px">No replies yet. Be the first!</p>
        <?php else: while($r = $replies->fetch_assoc()): ?>
        <div class="reply-card <?= $r['is_solution'] ? 'solution' : '' ?>">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px">
                <div style="flex:1">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;flex-wrap:wrap">
                        <strong style="color:#1a237e;font-size:0.88rem"><?= htmlspecialchars($r['name']) ?></strong>
                        <span class="rbadge <?= $r['user_role'] ?>"><?= ucfirst($r['user_role']) ?></span>
                        <?php if ($r['is_solution']): ?><span style="background:#e8f5e9;color:#2e7d32;padding:2px 8px;border-radius:10px;font-size:0.72rem;font-weight:700">✅ Solution</span><?php endif; ?>
                        <span style="font-size:0.75rem;color:#999"><?= date('d M Y, h:i A', strtotime($r['created_at'])) ?></span>
                    </div>
                    <div style="color:#444;line-height:1.7;font-size:0.92rem"><?= nl2br(htmlspecialchars($r['content'])) ?></div>
                    <div style="margin-top:8px;font-size:0.78rem;color:#999">👍 <?= $r['likes'] ?> likes</div>
                </div>
                <div style="display:flex;flex-direction:column;gap:5px">
                    <?php if ($viewPost['user_id'] == $uid && !$r['is_solution'] && !$viewPost['is_locked']): ?>
                    <a href="?solution=<?= $r['id'] ?>&post=<?= $viewPost['id'] ?>" class="btn btn-success btn-sm">✅ Solution</a>
                    <?php endif; ?>
                    <?php if ($r['user_id'] == $uid || $role === 'admin'): ?>
                    <a href="?del_reply=<?= $r['id'] ?>&post=<?= $viewPost['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete reply?')">🗑️</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endwhile; endif; ?>
        <?php if (!$viewPost['is_locked']): ?>
        <div style="margin-top:20px;padding-top:15px;border-top:2px solid #e8eaf6">
            <h3 style="color:#1a237e;margin-bottom:12px;font-size:1rem">💬 Add Reply</h3>
            <form method="POST">
                <input type="hidden" name="post_id" value="<?= $viewPost['id'] ?>">
                <div class="form-group"><textarea name="content" rows="4" placeholder="Write your reply..." required style="resize:vertical"></textarea></div>
                <button name="new_reply" class="btn btn-primary">Post Reply</button>
            </form>
        </div>
        <?php else: ?>
        <div class="alert alert-info" style="margin-top:15px">🔒 This post is locked.</div>
        <?php endif; ?>
    </div>

    <?php else: ?>
    <!-- ── Tab Bar ── -->
    <div class="tab-bar">
        <button class="tab-btn <?= $activeTab==='forum'?'active':'' ?>" data-tab="forum" onclick="switchTab('forum')">💬 Discussion Forum</button>
        <button class="tab-btn <?= $activeTab==='experiences'?'active':'' ?>" data-tab="experiences" onclick="switchTab('experiences')">🎤 Interview Experiences</button>
    </div>

    <!-- ══════════════ FORUM TAB ══════════════ -->
    <div class="tab-pane <?= $activeTab==='forum'?'active':'' ?>" id="tab-forum">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:10px">
            <h2 style="color:#1a237e;font-size:1.3rem;margin:0">💬 Discussion Forum</h2>
            <button onclick="document.getElementById('npModal').style.display='flex'" class="btn btn-primary">✏️ New Post</button>
        </div>
        <form method="GET" style="display:flex;gap:10px;margin-bottom:20px">
            <input type="hidden" name="tab" value="forum">
            <input type="text" name="q" placeholder="🔍 Search discussions..." value="<?= htmlspecialchars($search) ?>" style="flex:1;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:0.95rem">
            <?php if ($catFilter): ?><input type="hidden" name="cat" value="<?= $catFilter ?>"><?php endif; ?>
            <button type="submit" class="btn btn-primary">Search</button>
            <?php if ($search): ?><a href="index.php" class="btn" style="background:#e8eaf6;color:#333">Clear</a><?php endif; ?>
        </form>
        <div class="forum-layout">
            <div>
                <div class="card" style="padding:15px">
                    <div style="font-weight:700;color:#1a237e;margin-bottom:10px;font-size:0.88rem">📂 CATEGORIES</div>
                    <a href="index.php?tab=forum" class="cat-item <?= !$catFilter?'active':'' ?>"><span>🌐</span><div><div style="font-weight:600;font-size:0.88rem">All Posts</div></div></a>
                    <?php foreach ($categoriesArr as $c):
                        $catLink = (strtolower($c['name']) === 'interview experiences')
                            ? 'index.php?tab=experiences'
                            : '?tab=forum&cat='.$c['id'];
                        $catActive = (strtolower($c['name']) === 'interview experiences')
                            ? $activeTab === 'experiences'
                            : $catFilter == $c['id'];
                    ?>
                    <a href="<?= $catLink ?>" class="cat-item <?= $catActive?'active':'' ?>">
                        <span><?= $c['icon'] ?></span>
                        <div><div style="font-weight:600;font-size:0.88rem"><?= htmlspecialchars($c['name']) ?></div><div style="font-size:0.73rem;color:#999"><?= $c['post_count'] ?> posts</div></div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <div class="card" style="padding:15px;margin-top:0">
                    <div style="font-weight:700;color:#1a237e;margin-bottom:8px;font-size:0.88rem">📊 STATS</div>
                    <div style="font-size:0.85rem;color:#555;line-height:2.2">
                        <div>💬 <?= $conn->query("SELECT COUNT(*) as c FROM forum_posts")->fetch_assoc()['c'] ?> Discussions</div>
                        <div>↩️ <?= $conn->query("SELECT COUNT(*) as c FROM forum_replies")->fetch_assoc()['c'] ?> Replies</div>
                    </div>
                </div>
            </div>
            <div>
                <?php if ($posts->num_rows === 0): ?>
                <div class="card" style="text-align:center;padding:50px;color:#999">
                    <div style="font-size:3rem;margin-bottom:10px">💬</div>
                    <p><?= $search ? 'No results found.' : 'No discussions yet. Start the first one!' ?></p>
                </div>
                <?php else: while($p = $posts->fetch_assoc()): ?>
                <div class="post-card <?= $p['is_pinned']?'pinned':'' ?>">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px">
                        <div style="flex:1">
                            <div style="margin-bottom:5px">
                                <span style="background:#e8eaf6;color:#3f51b5;padding:2px 8px;border-radius:10px;font-size:0.72rem;font-weight:700"><?= $p['cat_icon'] ?> <?= htmlspecialchars($p['cat_name']) ?></span>
                                <?php if ($p['is_pinned']): ?><span style="background:#fff8e1;color:#f57f17;padding:2px 8px;border-radius:10px;font-size:0.72rem;font-weight:700;margin-left:5px">📌 Pinned</span><?php endif; ?>
                            </div>
                            <a href="?post=<?= $p['id'] ?>" class="post-title"><?= htmlspecialchars($p['title']) ?></a>
                            <div class="post-meta">
                                <span>👤 <?= htmlspecialchars($p['name']) ?></span>
                                <span>💬 <?= $p['reply_count'] ?> replies</span>
                                <span>👍 <?= $p['likes'] ?></span>
                                <span>👁️ <?= $p['views'] ?></span>
                                <span>🕐 <?= date('d M Y', strtotime($p['created_at'])) ?></span>
                            </div>
                        </div>
                        <div style="display:flex;gap:5px">
                            <?php if ($role === 'admin'): ?>
                            <a href="?pin=<?= $p['id'] ?>" class="btn btn-sm" style="background:#fff8e1;color:#f57f17"><?= $p['is_pinned']?'📌':'📍' ?></a>
                            <?php endif; ?>
                            <?php if ($p['user_id'] == $uid || $role === 'admin'): ?>
                            <a href="?del_post=<?= $p['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete?')">🗑️</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; endif; ?>
            </div>
        </div>
    </div>

    <!-- ══════════════ INTERVIEW EXPERIENCES TAB ══════════════ -->
    <div class="tab-pane <?= $activeTab==='experiences'?'active':'' ?>" id="tab-experiences">
        <!-- Header banner -->
        <div style="background:linear-gradient(135deg,#1a237e,#3949ab);color:#fff;border-radius:12px;padding:22px 28px;margin-bottom:24px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:15px">
            <div>
                <h2 style="color:#ffd54f;margin:0 0 6px;font-size:1.2rem">🎤 Interview Experiences</h2>
                <p style="color:#c5cae9;font-size:0.88rem;margin:0">Share your interview experience and help fellow students prepare better.</p>
            </div>
            <div style="display:flex;gap:15px">
                <div style="background:rgba(255,255,255,0.1);border-radius:10px;padding:10px 18px;text-align:center">
                    <div style="font-size:1.6rem;font-weight:800;color:#ffd54f"><?= $total_exp_count ?></div>
                    <div style="font-size:0.75rem;color:#c5cae9">Total Shared</div>
                </div>
                <div style="background:rgba(255,255,255,0.1);border-radius:10px;padding:10px 18px;text-align:center">
                    <div style="font-size:1.6rem;font-weight:800;color:#69f0ae"><?= $my_exp_count ?></div>
                    <div style="font-size:0.75rem;color:#c5cae9">My Shares</div>
                </div>
            </div>
        </div>

        <div class="exp-layout">
            <!-- Left: filters + feed -->
            <div>
                <form method="GET" style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap">
                    <input type="hidden" name="tab" value="experiences">
                    <input type="text" name="company" placeholder="🔍 Search company..." value="<?= htmlspecialchars($fc) ?>" style="flex:1;padding:9px 14px;border:1px solid #ddd;border-radius:8px;min-width:140px">
                    <select name="outcome" style="padding:9px 12px;border:1px solid #ddd;border-radius:8px">
                        <option value="">All Outcomes</option>
                        <option value="Selected"  <?= $fo==='Selected'?'selected':'' ?>>✅ Selected</option>
                        <option value="Rejected"  <?= $fo==='Rejected'?'selected':'' ?>>❌ Rejected</option>
                        <option value="Pending"   <?= $fo==='Pending' ?'selected':'' ?>>⏳ Pending</option>
                    </select>
                    <select name="difficulty" style="padding:9px 12px;border:1px solid #ddd;border-radius:8px">
                        <option value="">All Difficulty</option>
                        <option value="Easy"   <?= $fd==='Easy'  ?'selected':'' ?>>🟢 Easy</option>
                        <option value="Medium" <?= $fd==='Medium'?'selected':'' ?>>🟡 Medium</option>
                        <option value="Hard"   <?= $fd==='Hard'  ?'selected':'' ?>>🔴 Hard</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="index.php?tab=experiences" class="btn btn-warning">Clear</a>
                </form>

                <?php if ($experiences->num_rows === 0): ?>
                <div class="card" style="text-align:center;padding:50px;color:#999">
                    <div style="font-size:3.5rem;margin-bottom:12px">🎤</div>
                    <p>No experiences shared yet. Be the first to share!</p>
                </div>
                <?php else: while($e = $experiences->fetch_assoc()): ?>
                <div class="exp-card" style="border-left-color:<?= $outcome_colors[$e['outcome']] ?>">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px;margin-bottom:12px">
                        <div>
                            <h3 style="color:#1a237e;margin:0 0 6px"><?= htmlspecialchars($e['company_name']) ?></h3>
                            <?php if ($e['job_role']): ?><div style="color:#555;font-size:0.88rem;margin-bottom:6px">💼 <?= htmlspecialchars($e['job_role']) ?></div><?php endif; ?>
                            <div style="display:flex;gap:6px;flex-wrap:wrap">
                                <span class="badge-sm" style="background:<?= $outcome_bg[$e['outcome']] ?>;color:<?= $outcome_colors[$e['outcome']] ?>"><?= $outcome_icon[$e['outcome']] ?> <?= $e['outcome'] ?></span>
                                <span class="badge-sm" style="background:<?= $diff_bg[$e['difficulty']] ?>;color:<?= $diff_colors[$e['difficulty']] ?>"><?= $e['difficulty'] ?></span>
                                <?php if ($e['interview_date']): ?><span class="badge-sm" style="background:#f5f5f5;color:#666">📅 <?= date('d M Y', strtotime($e['interview_date'])) ?></span><?php endif; ?>
                            </div>
                        </div>
                        <div style="text-align:right;font-size:0.78rem;color:#999">
                            <div>By <strong style="color:#3f51b5"><?= htmlspecialchars($e['author']) ?></strong></div>
                            <div><?= date('d M Y', strtotime($e['created_at'])) ?></div>
                            <?php if ($e['user_id'] == $uid): ?>
                            <a href="?tab=experiences&del_exp=<?= $e['id'] ?>" onclick="return confirm('Delete?')" style="color:#e53935;font-weight:600">🗑️ Delete</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($e['rounds']): ?>
                    <div style="margin-bottom:10px">
                        <strong style="font-size:0.82rem;color:#555">Interview Rounds:</strong><br>
                        <?php foreach (explode(',', $e['rounds']) as $r): ?>
                        <span class="round-tag"><?= htmlspecialchars(trim($r)) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <div style="color:#444;font-size:0.9rem;line-height:1.7;margin-bottom:10px"><?= nl2br(htmlspecialchars($e['experience'])) ?></div>
                    <?php if ($e['tips']): ?>
                    <div style="background:#e8f5e9;border-radius:8px;padding:10px 14px;font-size:0.87rem;color:#2e7d32;border-left:3px solid #43a047">
                        💡 <strong>Tips:</strong> <?= nl2br(htmlspecialchars($e['tips'])) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endwhile; endif; ?>
            </div>

            <!-- Right: share form -->
            <div>
                <div class="card" style="position:sticky;top:80px">
                    <h2 style="font-size:1.1rem;margin-bottom:16px">✍️ Share Your Experience</h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="post_exp">
                        <div class="form-group">
                            <label>Company Name *</label>
                            <input type="text" name="company_name" placeholder="e.g. TCS, Infosys" required>
                        </div>
                        <div class="form-group">
                            <label>Job Role</label>
                            <input type="text" name="job_role" placeholder="e.g. Software Engineer">
                        </div>
                        <div class="form-group">
                            <label>Interview Date</label>
                            <input type="date" name="interview_date">
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                            <div class="form-group">
                                <label>Difficulty</label>
                                <select name="difficulty">
                                    <option value="Easy">🟢 Easy</option>
                                    <option value="Medium" selected>🟡 Medium</option>
                                    <option value="Hard">🔴 Hard</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Outcome</label>
                                <select name="outcome">
                                    <option value="Selected">✅ Selected</option>
                                    <option value="Rejected">❌ Rejected</option>
                                    <option value="Pending" selected>⏳ Pending</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Interview Rounds <small style="color:#999">(comma separated)</small></label>
                            <input type="text" name="rounds" placeholder="e.g. Aptitude, Technical, HR">
                        </div>
                        <div class="form-group">
                            <label>Your Experience *</label>
                            <textarea name="experience" rows="5" placeholder="Describe the interview process, questions asked..." required></textarea>
                        </div>
                        <div class="form-group">
                            <label>Tips for Others</label>
                            <textarea name="tips" rows="3" placeholder="What would you advise others?"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width:100%">📤 Share Experience</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- New Post Modal -->
    <div id="npModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;padding:20px">
        <div style="background:#fff;border-radius:12px;padding:28px;width:100%;max-width:580px;max-height:90vh;overflow-y:auto">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px">
                <h2 style="color:#1a237e;border:none;padding:0;margin:0;font-size:1.2rem">✏️ New Discussion</h2>
                <button onclick="document.getElementById('npModal').style.display='none'" style="background:none;border:none;font-size:1.5rem;cursor:pointer;color:#999;line-height:1">×</button>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label>Category *</label>
                    <select name="category_id" required>
                        <option value="">-- Select --</option>
                        <?php foreach ($categoriesArr as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= $c['icon'] ?> <?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Title *</label>
                    <input type="text" name="title" placeholder="What's your question or topic?" required>
                </div>
                <div class="form-group">
                    <label>Content *</label>
                    <textarea name="content" rows="5" placeholder="Describe in detail..." required style="resize:vertical"></textarea>
                </div>
                <div style="display:flex;gap:10px">
                    <button name="new_post" class="btn btn-primary">📤 Post</button>
                    <button type="button" onclick="document.getElementById('npModal').style.display='none'" class="btn" style="background:#e8eaf6;color:#333">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once '../chatbot/widget.php'; ?>
<script>
function switchTab(tab) {
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    document.querySelector('.tab-btn[data-tab="' + tab + '"]').classList.add('active');
}
</script>
</body>
</html>
