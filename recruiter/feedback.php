<?php
require_once '../includes/config.php';
requireLogin('recruiter');
require_once '../includes/notify.php';

$uid = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if ($uid === 0) { header('Location: ../index.php'); exit(); }
$stCo = $conn->prepare("SELECT * FROM companies WHERE user_id=?");
$stCo->bind_param('i', $uid); $stCo->execute();
$company = $stCo->get_result()->fetch_assoc(); $stCo->close();
$cid = (int)($company['id'] ?? 0);

// Ensure columns exist
$conn->query("ALTER TABLE applications ADD COLUMN IF NOT EXISTS feedback TEXT DEFAULT NULL");
$conn->query("ALTER TABLE applications ADD COLUMN IF NOT EXISTS feedback_at TIMESTAMP NULL DEFAULT NULL");

$msg = '';

// Save feedback
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_feedback'])) {
    $app_id   = (int)$_POST['app_id'];
    $feedback = trim($_POST['feedback'] ?? '');
    $st = $conn->prepare("UPDATE applications a JOIN jobs j ON a.job_id=j.id SET a.feedback=?, a.feedback_at=NOW() WHERE a.id=? AND j.company_id=?");
    $st->bind_param('sii', $feedback, $app_id, $cid); $st->execute(); $st->close();
    $msg = '<div class="alert alert-success">✅ Feedback saved successfully.</div>';
}

// Delete feedback
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_feedback'])) {
    $app_id = (int)$_POST['app_id'];
    $st = $conn->prepare("UPDATE applications a JOIN jobs j ON a.job_id=j.id SET a.feedback=NULL, a.feedback_at=NULL WHERE a.id=? AND j.company_id=?");
    $st->bind_param('ii', $app_id, $cid); $st->execute(); $st->close();
    $msg = '<div class="alert alert-success">Feedback deleted.</div>';
}

$allowedFilters = ['all', 'given', 'pending'];
$filter     = isset($_GET['filter']) && in_array($_GET['filter'], $allowedFilters) ? $_GET['filter'] : 'all';
$job_filter = isset($_GET['job']) ? (int)$_GET['job'] : 0;

$sql = "SELECT a.*, u.name as student_name, u.email, sp.department, sp.cgpa, j.title as job_title FROM applications a JOIN users u ON a.student_id=u.id LEFT JOIN student_profiles sp ON u.id=sp.user_id JOIN jobs j ON a.job_id=j.id WHERE j.company_id=?";
$bTypes = 'i'; $bVals = [$cid];
if ($filter === 'given')   { $sql .= " AND a.feedback IS NOT NULL AND a.feedback != ''"; }
if ($filter === 'pending') { $sql .= " AND (a.feedback IS NULL OR a.feedback = '')"; }
if ($job_filter)           { $sql .= " AND j.id=?"; $bTypes .= 'i'; $bVals[] = $job_filter; }
$sql .= " ORDER BY a.applied_at DESC";
$stA = $conn->prepare($sql); $stA->bind_param($bTypes, ...$bVals); $stA->execute();
$apps = $stA->get_result(); $stA->close();

$stMJ = $conn->prepare("SELECT id, title FROM jobs WHERE company_id=? ORDER BY created_at DESC");
$stMJ->bind_param('i', $cid); $stMJ->execute();
$my_jobs = $stMJ->get_result(); $stMJ->close();

$stTA = $conn->prepare("SELECT COUNT(*) as c FROM applications a JOIN jobs j ON a.job_id=j.id WHERE j.company_id=?");
$stTA->bind_param('i', $cid); $stTA->execute();
$total_apps = (int)$stTA->get_result()->fetch_assoc()['c']; $stTA->close();

$stGC = $conn->prepare("SELECT COUNT(*) as c FROM applications a JOIN jobs j ON a.job_id=j.id WHERE j.company_id=? AND a.feedback IS NOT NULL AND a.feedback != ''");
$stGC->bind_param('i', $cid); $stGC->execute();
$given_count = (int)$stGC->get_result()->fetch_assoc()['c']; $stGC->close();

$pending_count = $total_apps - $given_count;

$companyName = htmlspecialchars($company['company_name'] ?? $_SESSION['name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Feedback - CampusRecruit</title>
<link rel="stylesheet" href="../css/style.css">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',sans-serif;background:#f0f4f8;color:#333}
.app-layout{display:flex;min-height:100vh}

.sidebar{
    width:250px;min-height:100vh;flex-shrink:0;
    background:linear-gradient(180deg,#004d40 0%,#00695c 50%,#00796b 100%);
    display:flex;flex-direction:column;
    position:fixed;top:0;left:0;z-index:200;
    box-shadow:3px 0 20px rgba(0,0,0,0.25);overflow-y:auto;
}
.sidebar-brand{padding:22px 20px 16px;border-bottom:1px solid rgba(255,255,255,0.12)}
.sidebar-brand .brand-name{color:#fff;font-size:1.25rem;font-weight:800;text-decoration:none;display:block}
.sidebar-brand .brand-name span{color:#80cbc4}
.sidebar-brand .brand-sub{color:#80cbc4;font-size:0.72rem;margin-top:4px}
.sidebar-company{padding:14px 20px;border-bottom:1px solid rgba(255,255,255,0.1);display:flex;align-items:center;gap:12px}
.company-avatar{width:42px;height:42px;border-radius:10px;background:linear-gradient(135deg,#80cbc4,#4db6ac);display:flex;align-items:center;justify-content:center;font-size:1.1rem;font-weight:800;color:#004d40;flex-shrink:0}
.company-name{color:#fff;font-size:0.88rem;font-weight:700;line-height:1.3}
.company-role{color:#80cbc4;font-size:0.72rem;margin-top:2px}
.sec-label{padding:14px 20px 5px;color:#80cbc4;font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:1.2px}
.sidebar-nav a{display:flex;align-items:center;gap:11px;padding:10px 20px;color:#b2dfdb;text-decoration:none;font-size:0.875rem;font-weight:500;transition:all 0.2s;border-left:3px solid transparent}
.sidebar-nav a:hover{background:rgba(255,255,255,0.08);color:#fff;border-left-color:rgba(255,255,255,0.3)}
.sidebar-nav a.active{background:rgba(255,255,255,0.15);color:#e0f2f1;border-left-color:#80cbc4;font-weight:700}
.sidebar-nav a .ni{font-size:1rem;width:20px;text-align:center;flex-shrink:0}
.sidebar-footer{margin-top:auto;padding:15px 20px;border-top:1px solid rgba(255,255,255,0.1)}
.sidebar-footer a{display:flex;align-items:center;gap:8px;color:#ef9a9a;font-size:0.85rem;font-weight:600;text-decoration:none;padding:9px 12px;border-radius:8px;transition:all 0.2s}
.sidebar-footer a:hover{background:rgba(239,83,80,0.15);color:#ef5350}

.topbar{position:fixed;top:0;left:250px;right:0;height:60px;z-index:100;background:#fff;border-bottom:2px solid #e0f2f1;display:flex;align-items:center;justify-content:space-between;padding:0 28px;box-shadow:0 2px 10px rgba(0,0,0,0.06)}
.topbar-left{display:flex;align-items:center;gap:14px}
.topbar-left .page-title{font-size:1.1rem;font-weight:700;color:#004d40}
.topbar-right{display:flex;align-items:center;gap:12px}
.topbar-company-badge{background:#e0f2f1;color:#00695c;padding:5px 14px;border-radius:20px;font-size:0.82rem;font-weight:700}
.hamburger{display:none;background:none;border:none;cursor:pointer;font-size:1.4rem;color:#004d40}

.main-content{margin-left:250px;margin-top:60px;padding:26px;flex:1;min-width:0}

.stats-row{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px}
.stat-box{background:#fff;border-radius:12px;padding:20px 16px;box-shadow:0 2px 10px rgba(0,0,0,0.07);text-align:center;border-top:4px solid #00897b;transition:transform 0.2s}
.stat-box:hover{transform:translateY(-3px)}
.stat-box .num{font-size:2rem;font-weight:800;color:#004d40}
.stat-box .lbl{color:#666;font-size:0.82rem;margin-top:5px}
.stat-box.orange{border-top-color:#fb8c00}
.stat-box.blue{border-top-color:#1565c0}

.filter-bar{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:18px;align-items:center}
.filter-bar a,.filter-bar select{padding:7px 16px;border-radius:20px;font-size:0.85rem;font-weight:600;text-decoration:none;border:2px solid #e0e0e0;background:#fff;color:#555;cursor:pointer;transition:all 0.2s}
.filter-bar a:hover{border-color:#00897b;color:#00897b}
.filter-bar a.active{background:#00897b;color:#fff;border-color:#00897b}
.filter-bar select{padding:6px 12px}

.table-card{background:#fff;border-radius:12px;padding:22px;box-shadow:0 2px 10px rgba(0,0,0,0.07)}
.table-card h2{color:#004d40;font-size:1.05rem;margin-bottom:16px;padding-bottom:10px;border-bottom:2px solid #e0f2f1}

.feedback-row td{vertical-align:top;padding:14px 10px}
.student-info strong{font-size:0.95rem;color:#004d40}
.student-info small{display:block;color:#888;font-size:0.78rem;margin-top:2px}
.feedback-box{width:100%;padding:8px 10px;border:1px solid #ddd;border-radius:8px;font-size:0.85rem;resize:vertical;min-height:70px;font-family:inherit;transition:border-color 0.2s}
.feedback-box:focus{outline:none;border-color:#00897b;box-shadow:0 0 0 3px rgba(0,137,123,0.1)}
.has-feedback{border-color:#43a047;background:#f1f8e9}
.feedback-meta{font-size:0.75rem;color:#888;margin-top:4px}
.btn-save{background:#00897b;color:#fff;border:none;padding:7px 18px;border-radius:20px;font-size:0.82rem;font-weight:700;cursor:pointer;transition:all 0.2s}
.btn-save:hover{background:#00695c}
.btn-del{background:none;border:none;color:#e53935;font-size:0.78rem;cursor:pointer;padding:4px 8px;border-radius:4px;transition:all 0.2s}
.btn-del:hover{background:#ffebee}
.action-row{display:flex;justify-content:space-between;align-items:center;margin-top:6px}
.badge{padding:3px 10px;border-radius:12px;font-size:0.75rem;font-weight:700}
.badge-applied{background:#e3f2fd;color:#1565c0}
.badge-shortlisted{background:#fff8e1;color:#f57f17}
.badge-selected{background:#e8f5e9;color:#2e7d32}
.badge-rejected{background:#ffebee;color:#c62828}

.empty-state{text-align:center;padding:50px 20px;color:#999}
.empty-state .icon{font-size:3.5rem;margin-bottom:15px}

@media(max-width:960px){
    .sidebar{transform:translateX(-100%)}
    .sidebar.open{transform:translateX(0)}
    .topbar{left:0}
    .main-content{margin-left:0}
    .hamburger{display:block}
    .stats-row{grid-template-columns:repeat(2,1fr)}
}
</style>
</head>
<body>
<div class="app-layout">

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:199"></div>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <a href="dashboard.php" class="brand-name">🎓 Campus<span>Recruit</span></a>
        <div class="brand-sub">🏢 Recruiter Portal</div>
    </div>
    <div class="sidebar-company">
        <div class="company-avatar"><?= strtoupper(substr($company['company_name'] ?? $_SESSION['name'], 0, 1)) ?></div>
        <div>
            <div class="company-name"><?= $companyName ?></div>
            <div class="company-role"><?= htmlspecialchars($company['industry'] ?? 'Recruiter') ?></div>
        </div>
    </div>

    <div class="sec-label">Hiring</div>
    <nav class="sidebar-nav">
        <a href="dashboard.php"><span class="ni">🏠</span> Dashboard</a>
        <a href="interviews/index.php"><span class="ni">🎥</span> Interviews</a>
        <a href="feedback.php" class="active"><span class="ni">💬</span> Student Feedback</a>
    </nav>

    <div class="sec-label">Account</div>
    <nav class="sidebar-nav">
        <a href="profile.php"><span class="ni">🏢</span> Company Profile</a>
        <a href="../security/index.php"><span class="ni">🔒</span> Security</a>
    </nav>

    <div class="sidebar-footer">
        <a href="logout.php"><span>🚪</span> Logout</a>
    </div>
</aside>

<div class="topbar">
    <div class="topbar-left">
        <button class="hamburger" onclick="toggleSidebar()">☰</button>
        <span class="page-title">💬 Student Feedback</span>
    </div>
    <div class="topbar-right">
        <span class="topbar-company-badge">🏢 <?= $companyName ?></span>
        <?php require_once '../notifications/widget.php'; ?>
    </div>
</div>

<main class="main-content">

    <?= $msg ?>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-box blue">
            <div class="num"><?= $total_apps ?></div>
            <div class="lbl">📋 Total Applicants</div>
        </div>
        <div class="stat-box">
            <div class="num"><?= $given_count ?></div>
            <div class="lbl">✅ Feedback Given</div>
        </div>
        <div class="stat-box orange">
            <div class="num"><?= $pending_count ?></div>
            <div class="lbl">⏳ Pending Feedback</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-bar">
        <a href="?filter=all<?= $job_filter ? "&job=$job_filter" : '' ?>" class="<?= $filter==='all' ? 'active' : '' ?>">All</a>
        <a href="?filter=given<?= $job_filter ? "&job=$job_filter" : '' ?>" class="<?= $filter==='given' ? 'active' : '' ?>">✅ Feedback Given</a>
        <a href="?filter=pending<?= $job_filter ? "&job=$job_filter" : '' ?>" class="<?= $filter==='pending' ? 'active' : '' ?>">⏳ Pending</a>
        <select onchange="location='?filter=<?= $filter ?>&job='+this.value">
            <option value="0">All Jobs</option>
            <?php
            $my_jobs->data_seek(0);
            while ($j = $my_jobs->fetch_assoc()):
            ?>
            <option value="<?= $j['id'] ?>" <?= $job_filter == $j['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($j['title']) ?>
            </option>
            <?php endwhile; ?>
        </select>
    </div>

    <!-- Feedback Table -->
    <div class="table-card">
        <h2>Student Feedback</h2>
        <?php if ($apps->num_rows === 0): ?>
        <div class="empty-state">
            <div class="icon">💬</div>
            <p>No applicants found for the selected filter.</p>
        </div>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Job</th>
                        <th>Status</th>
                        <th style="min-width:300px">Feedback</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($a = $apps->fetch_assoc()): ?>
                <tr class="feedback-row">
                    <td>
                        <div class="student-info">
                            <strong><?= htmlspecialchars($a['student_name']) ?></strong>
                            <small><?= htmlspecialchars($a['email']) ?></small>
                            <small><?= htmlspecialchars($a['department'] ?? '') ?> · CGPA: <?= $a['cgpa'] ?? 'N/A' ?></small>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($a['job_title']) ?></td>
                    <td><span class="badge badge-<?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span></td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="app_id" value="<?= $a['id'] ?>">
                            <textarea name="feedback" class="feedback-box <?= !empty($a['feedback']) ? 'has-feedback' : '' ?>"
                                placeholder="Write feedback for this student (strengths, areas to improve, interview performance...)..."><?= htmlspecialchars($a['feedback'] ?? '') ?></textarea>
                            <div class="action-row">
                                <div>
                                    <?php if ($a['feedback_at']): ?>
                                    <span class="feedback-meta">Last updated: <?= date('d M Y, h:i A', strtotime($a['feedback_at'])) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div style="display:flex;gap:8px;align-items:center">
                                    <?php if (!empty($a['feedback'])): ?>
                                    <button type="submit" name="delete_feedback" class="btn-del"
                                        onclick="return confirm('Delete this feedback?')">🗑 Clear</button>
                                    <?php endif; ?>
                                    <button type="submit" name="save_feedback" class="btn-save">💬 Save Feedback</button>
                                </div>
                            </div>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

</main>
</div>

<?php require_once '../chatbot/widget.php'; ?>
<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').style.display =
        document.getElementById('sidebar').classList.contains('open') ? 'block' : 'none';
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').style.display = 'none';
}
</script>
</body>
</html>
