<?php
require_once '../includes/config.php';
requireLogin('recruiter');

$uid = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if ($uid === 0) { header('Location: ../index.php'); exit(); }
$stCo = $conn->prepare("SELECT * FROM companies WHERE user_id=?");
$stCo->bind_param('i',$uid); $stCo->execute();
$company = $stCo->get_result()->fetch_assoc(); $stCo->close();
$cid = (int)($company['id'] ?? 0);

function recCount($conn, $sql, $types='', ...$vals) {
    $st = $conn->prepare($sql); if ($types) $st->bind_param($types, ...$vals);
    $st->execute(); $c = (int)$st->get_result()->fetch_assoc()['c']; $st->close(); return $c;
}
$stats = [
    'applications' => recCount($conn, "SELECT COUNT(*) as c FROM applications a JOIN jobs j ON a.job_id=j.id WHERE j.company_id=?", 'i', $cid),
    'shortlisted'  => recCount($conn, "SELECT COUNT(*) as c FROM applications a JOIN jobs j ON a.job_id=j.id WHERE j.company_id=? AND a.status='shortlisted'", 'i', $cid),
    'selected'     => recCount($conn, "SELECT COUNT(*) as c FROM applications a JOIN jobs j ON a.job_id=j.id WHERE j.company_id=? AND a.status='selected'", 'i', $cid),
    'rejected'     => recCount($conn, "SELECT COUNT(*) as c FROM applications a JOIN jobs j ON a.job_id=j.id WHERE j.company_id=? AND a.status='rejected'", 'i', $cid),
];

$ivTotal = 0; $ivScheduled = 0;
if ($conn->query("SHOW TABLES LIKE 'interviews'")->num_rows > 0) {
    $ivTotal     = recCount($conn, "SELECT COUNT(*) as c FROM interviews WHERE company_id=?", 'i', $cid);
    $ivScheduled = recCount($conn, "SELECT COUNT(*) as c FROM interviews WHERE company_id=? AND status='scheduled'", 'i', $cid);
}

$feedback_given = recCount($conn, "SELECT COUNT(*) as c FROM applications a JOIN jobs j ON a.job_id=j.id WHERE j.company_id=? AND a.feedback IS NOT NULL AND a.feedback != ''", 'i', $cid);

$stRI = $conn->prepare("SELECT i.*, u.name as student_name, j.title as job_title, a.status as app_status FROM interviews i JOIN users u ON i.student_id=u.id JOIN jobs j ON i.job_id=j.id JOIN applications a ON i.application_id=a.id WHERE i.company_id=? ORDER BY i.scheduled_at DESC LIMIT 6");
$stRI->bind_param('i',$cid); $stRI->execute();
$recent_interviews = $stRI->get_result(); $stRI->close();

$companyName = htmlspecialchars($company['company_name'] ?? $_SESSION['name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Recruiter Dashboard - CampusRecruit</title>
<link rel="stylesheet" href="../css/style.css">
<style>
/* ── Layout ── */
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',sans-serif;background:#f0f4f8;color:#333}
.app-layout{display:flex;min-height:100vh}

/* ── Sidebar — teal/dark theme ── */
.sidebar{
    width:250px;min-height:100vh;flex-shrink:0;
    background:linear-gradient(180deg,#004d40 0%,#00695c 50%,#00796b 100%);
    display:flex;flex-direction:column;
    position:fixed;top:0;left:0;z-index:200;
    box-shadow:3px 0 20px rgba(0,0,0,0.25);
    overflow-y:auto;
}

.sidebar-brand{
    padding:22px 20px 16px;
    border-bottom:1px solid rgba(255,255,255,0.12);
}
.sidebar-brand .brand-name{
    color:#fff;font-size:1.25rem;font-weight:800;
    text-decoration:none;display:block;
}
.sidebar-brand .brand-name span{color:#80cbc4}
.sidebar-brand .brand-sub{
    color:#80cbc4;font-size:0.72rem;margin-top:4px;
    display:flex;align-items:center;gap:5px;
}

.sidebar-company{
    padding:14px 20px;
    border-bottom:1px solid rgba(255,255,255,0.1);
    display:flex;align-items:center;gap:12px;
}
.company-avatar{
    width:42px;height:42px;border-radius:10px;
    background:linear-gradient(135deg,#80cbc4,#4db6ac);
    display:flex;align-items:center;justify-content:center;
    font-size:1.1rem;font-weight:800;color:#004d40;flex-shrink:0;
}
.company-name{color:#fff;font-size:0.88rem;font-weight:700;line-height:1.3}
.company-role{color:#80cbc4;font-size:0.72rem;margin-top:2px}

.sec-label{
    padding:14px 20px 5px;
    color:#80cbc4;font-size:0.68rem;font-weight:700;
    text-transform:uppercase;letter-spacing:1.2px;
}

.sidebar-nav a{
    display:flex;align-items:center;gap:11px;
    padding:10px 20px;color:#b2dfdb;
    text-decoration:none;font-size:0.875rem;font-weight:500;
    transition:all 0.2s;border-left:3px solid transparent;
}
.sidebar-nav a:hover{background:rgba(255,255,255,0.08);color:#fff;border-left-color:rgba(255,255,255,0.3)}
.sidebar-nav a.active{background:rgba(255,255,255,0.15);color:#e0f2f1;border-left-color:#80cbc4;font-weight:700}
.sidebar-nav a .ni{font-size:1rem;width:20px;text-align:center;flex-shrink:0}
.sidebar-nav a .nbadge{
    margin-left:auto;background:#ff7043;color:#fff;
    border-radius:10px;padding:1px 7px;font-size:0.65rem;font-weight:800;
}

.sidebar-footer{
    margin-top:auto;padding:15px 20px;
    border-top:1px solid rgba(255,255,255,0.1);
}
.sidebar-footer a{
    display:flex;align-items:center;gap:8px;
    color:#ef9a9a;font-size:0.85rem;font-weight:600;
    text-decoration:none;padding:9px 12px;border-radius:8px;transition:all 0.2s;
}
.sidebar-footer a:hover{background:rgba(239,83,80,0.15);color:#ef5350}

/* ── Topbar ── */
.topbar{
    position:fixed;top:0;left:250px;right:0;height:60px;z-index:100;
    background:#fff;border-bottom:2px solid #e0f2f1;
    display:flex;align-items:center;justify-content:space-between;
    padding:0 28px;box-shadow:0 2px 10px rgba(0,0,0,0.06);
}
.topbar-left{display:flex;align-items:center;gap:14px}
.topbar-left .page-title{font-size:1.1rem;font-weight:700;color:#004d40}
.topbar-right{display:flex;align-items:center;gap:12px}
.topbar-company-badge{
    background:#e0f2f1;color:#00695c;
    padding:5px 14px;border-radius:20px;
    font-size:0.82rem;font-weight:700;
}
.hamburger{display:none;background:none;border:none;cursor:pointer;font-size:1.4rem;color:#004d40}

/* ── Main ── */
.main-content{margin-left:250px;margin-top:60px;padding:26px;flex:1;min-width:0}

/* ── Stats ── */
.stats-row{display:grid;grid-template-columns:repeat(5,1fr);gap:16px;margin-bottom:24px}
.stat-box{
    background:#fff;border-radius:12px;padding:20px 16px;
    box-shadow:0 2px 10px rgba(0,0,0,0.07);text-align:center;
    border-top:4px solid #00897b;transition:transform 0.2s;
}
.stat-box:hover{transform:translateY(-3px)}
.stat-box .num{font-size:2rem;font-weight:800;color:#004d40}
.stat-box .lbl{color:#666;font-size:0.82rem;margin-top:5px}
.stat-box.orange{border-top-color:#fb8c00}
.stat-box.blue{border-top-color:#1565c0}
.stat-box.green{border-top-color:#2e7d32}
.stat-box.red{border-top-color:#e53935}

/* ── Quick action cards ── */
.quick-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px;margin-bottom:24px}
.quick-card{
    border-radius:12px;padding:18px 20px;
    display:flex;justify-content:space-between;align-items:center;
    gap:10px;transition:transform 0.2s,box-shadow 0.2s;
}
.quick-card:hover{transform:translateY(-3px);box-shadow:0 6px 20px rgba(0,0,0,0.15)}
.quick-card .qc-info h3{font-size:0.92rem;font-weight:700;margin-bottom:4px}
.quick-card .qc-info p{font-size:0.78rem;opacity:0.85;margin:0}
.quick-card .qc-info small{font-size:0.75rem;opacity:0.9;display:block;margin-top:3px}
.quick-card .qc-btn{
    padding:7px 16px;border-radius:20px;background:#fff;
    font-weight:700;font-size:0.8rem;text-decoration:none;
    white-space:nowrap;flex-shrink:0;
}

/* ── Table card ── */
.table-card{background:#fff;border-radius:12px;padding:22px;box-shadow:0 2px 10px rgba(0,0,0,0.07)}
.table-card h2{color:#004d40;font-size:1.05rem;margin-bottom:16px;padding-bottom:10px;border-bottom:2px solid #e0f2f1;display:flex;justify-content:space-between;align-items:center}
.table-card h2 a{font-size:0.82rem;color:#00897b;font-weight:600;text-decoration:none}
.table-card h2 a:hover{text-decoration:underline}

/* ── Post job CTA ── */
.post-cta{
    background:linear-gradient(135deg,#004d40,#00695c);
    border-radius:12px;padding:22px 26px;color:#fff;
    display:flex;justify-content:space-between;align-items:center;
    flex-wrap:wrap;gap:15px;margin-bottom:24px;
}
.post-cta h3{color:#80cbc4;font-size:1rem;margin-bottom:4px}
.post-cta p{color:#b2dfdb;font-size:0.88rem}
.post-cta a{
    background:#80cbc4;color:#004d40;padding:10px 24px;
    border-radius:20px;font-weight:800;text-decoration:none;
    font-size:0.9rem;transition:all 0.2s;white-space:nowrap;
}
.post-cta a:hover{background:#4db6ac;transform:translateY(-2px)}

@media(max-width:960px){
    .sidebar{transform:translateX(-100%)}
    .sidebar.open{transform:translateX(0)}
    .topbar{left:0}
    .main-content{margin-left:0}
    .hamburger{display:block}
    .stats-row{grid-template-columns:repeat(2,1fr)}
}
@media(max-width:500px){
    .stats-row{grid-template-columns:1fr 1fr}
    .quick-grid{grid-template-columns:1fr}
}
</style>
</head>
<body>
<div class="app-layout">

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:199"></div>

<!-- ── SIDEBAR ── -->
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

    <!-- Jobs -->
    <div class="sec-label">Hiring</div>
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="active">
            <span class="ni">🏠</span> Dashboard
        </a>
        <a href="interviews/index.php">
            <span class="ni">🎥</span> Interviews
            <?php if ($ivScheduled > 0): ?><span class="nbadge"><?= $ivScheduled ?></span><?php endif; ?>
        </a>
        <a href="feedback.php">
            <span class="ni">💬</span> Student Feedback
        </a>
    </nav>

    <!-- Account -->
    <div class="sec-label">Account</div>
    <nav class="sidebar-nav">
        <a href="profile.php">
            <span class="ni">🏢</span> Company Profile
        </a>
        <a href="../security/index.php">
            <span class="ni">🔒</span> Security
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="logout.php"><span>🚪</span> Logout</a>
    </div>
</aside>

<!-- ── TOPBAR ── -->
<div class="topbar">
    <div class="topbar-left">
        <button class="hamburger" onclick="toggleSidebar()">☰</button>
        <span class="page-title">Dashboard</span>
    </div>
    <div class="topbar-right">
        <span class="topbar-company-badge">🏢 <?= $companyName ?></span>
        <?php require_once '../notifications/widget.php'; ?>
    </div>
</div>

<!-- ── MAIN CONTENT ── -->
<main class="main-content">

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-box blue">
            <div class="num"><?= $ivTotal ?></div>
            <div class="lbl">🎥 Total Interviews</div>
        </div>
        <div class="stat-box">
            <div class="num"><?= $ivScheduled ?></div>
            <div class="lbl">📅 Upcoming Interviews</div>
        </div>
        <div class="stat-box green">
            <div class="num"><?= $stats['selected'] ?></div>
            <div class="lbl">✅ Selected</div>
        </div>
        <div class="stat-box red">
            <div class="num"><?= $stats['rejected'] ?></div>
            <div class="lbl">❌ Rejected</div>
        </div>
        <div class="stat-box orange">
            <div class="num"><?= $stats['shortlisted'] ?></div>
            <div class="lbl">⭐ Shortlisted</div>
        </div>
    </div>

    <!-- Quick Action Cards -->
    <div class="quick-grid">
        <div class="quick-card" style="background:linear-gradient(135deg,#1b5e20,#2e7d32);color:#fff">
            <div class="qc-info">
                <h3>🎥 Interviews</h3>
                <p>View minutes & recordings</p>
                <small><?= $ivTotal ?> total · <?= $ivScheduled ?> upcoming</small>
            </div>
            <a href="interviews/index.php" class="qc-btn" style="color:#1b5e20">Open</a>
        </div>

        <div class="quick-card" style="background:linear-gradient(135deg,#006064,#00838f);color:#fff">
            <div class="qc-info">
                <h3>💬 Student Feedback</h3>
                <p>Write & manage feedback</p>
                <small><?= $feedback_given ?> given · <?= ($stats['applications'] - $feedback_given) ?> pending</small>
            </div>
            <a href="feedback.php" class="qc-btn" style="color:#006064">Open</a>
        </div>
    </div>

    <!-- Recent Interviews -->
    <div class="table-card">
        <h2>
            Recent Interviews
            <a href="interviews/index.php">View All →</a>
        </h2>
        <?php if ($recent_interviews->num_rows === 0): ?>
        <div style="text-align:center;padding:40px;color:#999">
            <div style="font-size:3rem;margin-bottom:10px">🎥</div>
            <p>No interviews scheduled yet.</p>
        </div>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Student</th>
                    <th>Job Title</th>
                    <th>Interview Date</th>
                    <th>Status</th>
                    <th>Decision</th>
                    <th>Action</th>
                </tr>
                <?php while($iv = $recent_interviews->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($iv['student_name']) ?></strong></td>
                    <td><?= htmlspecialchars($iv['job_title']) ?></td>
                    <td><?= date('d M Y, h:i A', strtotime($iv['scheduled_at'])) ?></td>
                    <td><span class="badge badge-<?= $iv['status'] ?>"><?= ucfirst($iv['status']) ?></span></td>
                    <td>
                        <?php if ($iv['app_status'] === 'selected'): ?>
                        <span class="badge" style="background:#e8f5e9;color:#2e7d32">✅ Selected</span>
                        <?php elseif ($iv['app_status'] === 'rejected'): ?>
                        <span class="badge" style="background:#ffebee;color:#c62828">❌ Rejected</span>
                        <?php else: ?>
                        <span style="color:#999;font-size:0.82rem">Pending</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="interviews/index.php" class="btn btn-primary btn-sm"
                           style="background:#00897b;border-radius:20px">Manage</a>
                    </td>
                </tr>
                <?php endwhile; ?>
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
