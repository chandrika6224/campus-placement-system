<?php
$current = basename($_SERVER['PHP_SELF']);
$dir     = basename(dirname($_SERVER['PHP_SELF']));

function isActive($pages) {
    global $current, $dir;
    foreach ((array)$pages as $p) {
        if (strpos($p, '/') !== false) {
            [$d, $f] = explode('/', $p, 2);
            if ($dir === $d && ($f === '*' || $current === $f)) return true;
        } elseif ($current === $p) return true;
    }
    return false;
}

$root = '/placement';
?>
<style>
.app-layout { display: flex; min-height: 100vh; }
.sidebar {
    width: 245px; height: 100vh; flex-shrink: 0;
    background: linear-gradient(180deg, #1a237e 0%, #283593 60%, #3949ab 100%);
    display: flex; flex-direction: column;
    position: fixed; top: 0; left: 0; z-index: 200;
    box-shadow: 3px 0 15px rgba(0,0,0,0.2);
    overflow-y: scroll;
    scrollbar-width: thin;
    scrollbar-color: rgba(255,255,255,0.3) transparent;
}
.sidebar::-webkit-scrollbar { width: 5px; }
.sidebar::-webkit-scrollbar-track { background: transparent; }
.sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.3); border-radius: 4px; }
.sidebar::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.5); }
.sidebar-brand { padding: 18px 18px 14px; border-bottom: 1px solid rgba(255,255,255,0.1); }
.sidebar-brand a { color: #fff; font-size: 1.15rem; font-weight: 800; text-decoration: none; display: block; }
.sidebar-brand a span { color: #ffd54f; }
.sidebar-brand .brand-sub { color: #90caf9; font-size: 0.72rem; margin-top: 3px; }
.sidebar-user { padding: 12px 18px; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; gap: 10px; }
.user-avatar { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg,#ffd54f,#ffca28); display: flex; align-items: center; justify-content: center; font-size: 0.95rem; font-weight: 800; color: #1a237e; flex-shrink: 0; }
.user-info .user-name { color: #fff; font-size: 0.85rem; font-weight: 700; }
.user-info .user-role { color: #90caf9; font-size: 0.7rem; }
.sec-label { padding: 12px 18px 4px; color: #9fa8da; font-size: 0.67rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
.sidebar-nav a { display: flex; align-items: center; gap: 10px; padding: 8px 18px; color: #c5cae9; text-decoration: none; font-size: 0.875rem; font-weight: 500; transition: all 0.2s; border-left: 3px solid transparent; }
.sidebar-nav a:hover { background: rgba(255,255,255,0.08); color: #fff; border-left-color: rgba(255,255,255,0.3); }
.sidebar-nav a.active { background: rgba(255,255,255,0.15); color: #ffd54f; border-left-color: #ffd54f; font-weight: 700; }
.sidebar-nav a .ni { font-size: 1rem; width: 20px; text-align: center; flex-shrink: 0; }
.sidebar-footer { margin-top: auto; padding: 14px 18px; border-top: 1px solid rgba(255,255,255,0.1); }
.sidebar-footer a { display: flex; align-items: center; gap: 8px; color: #ef9a9a; font-size: 0.85rem; font-weight: 600; text-decoration: none; padding: 8px 10px; border-radius: 8px; transition: all 0.2s; }
.sidebar-footer a:hover { background: rgba(239,83,80,0.15); color: #ef5350; }
.topbar { position: fixed; top: 0; left: 245px; right: 0; height: 58px; z-index: 100; background: #fff; border-bottom: 1px solid #e8eaf6; display: flex; align-items: center; justify-content: space-between; padding: 0 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.topbar-left { display: flex; align-items: center; gap: 12px; }
.topbar-left .page-title { font-size: 1.1rem; font-weight: 700; color: #1a237e; }
.topbar-right { display: flex; align-items: center; gap: 10px; }
.main-content { margin-left: 245px; margin-top: 58px; padding: 24px; flex: 1; min-width: 0; position: relative; z-index: 1; }
.hamburger { display: none; background: none; border: none; cursor: pointer; font-size: 1.4rem; color: #1a237e; }
.sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 199; pointer-events: none; }
@media(max-width:900px) {
    .sidebar { transform: translateX(-100%); }
    .sidebar.open { transform: translateX(0); }
    .sidebar-overlay.show { display: block; pointer-events: all; }
    .topbar { left: 0; }
    .main-content { margin-left: 0; }
    .hamburger { display: block; }
}
</style>

<div class="app-layout">
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <a href="<?= $root ?>/admin/dashboard.php">🎓 Campus<span>Recruit</span></a>
        <div class="brand-sub">🛡️ Admin Panel</div>
    </div>
    <div class="sidebar-user">
        <div class="user-avatar">A</div>
        <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($_SESSION['name']) ?></div>
            <div class="user-role">Administrator</div>
        </div>
    </div>

    <div class="sec-label">Overview</div>
    <nav class="sidebar-nav">
        <a href="<?= $root ?>/admin/dashboard.php" class="<?= isActive('dashboard.php') ? 'active' : '' ?>"><span class="ni">🏠</span> Dashboard</a>
        <a href="<?= $root ?>/admin/reports.php"   class="<?= isActive('reports.php')   ? 'active' : '' ?>"><span class="ni">📊</span> Reports</a>
        <a href="<?= $root ?>/admin/notices.php"   class="<?= isActive('notices.php')   ? 'active' : '' ?>"><span class="ni">📢</span> Notices</a>
    </nav>

    <div class="sec-label">Users</div>
    <nav class="sidebar-nav">
        <a href="<?= $root ?>/admin/students.php"        class="<?= isActive('students.php')        ? 'active' : '' ?>"><span class="ni">🧑‍🎓</span> Students</a>
        <a href="<?= $root ?>/admin/import_students.php" class="<?= isActive('import_students.php') ? 'active' : '' ?>"><span class="ni">📥</span> Import Students</a>
        <a href="<?= $root ?>/admin/recruiters.php"      class="<?= isActive('recruiters.php')      ? 'active' : '' ?>"><span class="ni">🏢</span> Recruiters</a>
    </nav>

    <div class="sec-label">Placements</div>
    <nav class="sidebar-nav">
        <a href="<?= $root ?>/admin/jobs_internships.php"     class="<?= isActive('jobs_internships.php') ? 'active' : '' ?>"><span class="ni">💼</span> Jobs & Internships</a>
        <a href="<?= $root ?>/admin/applications.php"         class="<?= isActive('applications.php')   ? 'active' : '' ?>"><span class="ni">📋</span> Applications</a>
        <a href="<?= $root ?>/admin/shortlist/index.php"      class="<?= isActive('shortlist/*')        ? 'active' : '' ?>"><span class="ni">⚡</span> Eligibility</a>
        <a href="<?= $root ?>/admin/eligibility/index.php"    class="<?= isActive('eligibility/*')      ? 'active' : '' ?>"><span class="ni">✅</span> Approval</a>
        <a href="<?= $root ?>/admin/interviews/index.php"     class="<?= isActive('interviews/*')       ? 'active' : '' ?>"><span class="ni">🎥</span> Interviews</a>
        <a href="<?= $root ?>/admin/interviews_minutes.php"    class="<?= isActive('interviews_minutes.php') ? 'active' : '' ?>"><span class="ni">📝</span> Minutes & Feedback</a>
        <a href="<?= $root ?>/admin/placement_rounds/index.php" class="<?= isActive('placement_rounds/*')  ? 'active' : '' ?>"><span class="ni">🎯</span> Placement Rounds</a>
        <a href="<?= $root ?>/admin/documents/index.php"        class="<?= isActive('documents/*')         ? 'active' : '' ?>"><span class="ni">📄</span> Documents</a>    </nav>

    <div class="sec-label">AI & Tests</div>
    <nav class="sidebar-nav">
        <a href="<?= $root ?>/admin/aptitude/index.php"            class="<?= isActive('aptitude/*')            ? 'active' : '' ?>"><span class="ni">📝</span> Aptitude Tests</a>
        <a href="<?= $root ?>/admin/coding/index.php"              class="<?= isActive('coding/*')              ? 'active' : '' ?>"><span class="ni">💻</span> Coding</a>
        <a href="<?= $root ?>/admin/placement_prediction/index.php" class="<?= isActive('placement_prediction/*') ? 'active' : '' ?>"><span class="ni">🔮</span> Predictions</a>
        <a href="<?= $root ?>/admin/skill_gap/index.php"           class="<?= isActive('skill_gap/*')           ? 'active' : '' ?>"><span class="ni">🧩</span> Skill Gaps</a>
    </nav>

    <div class="sec-label">Community</div>
    <nav class="sidebar-nav">
        <a href="<?= $root ?>/forum/index.php"    class="<?= isActive('forum/*')    ? 'active' : '' ?>"><span class="ni">💬</span> Forum</a>
        <a href="<?= $root ?>/calendar/index.php" class="<?= isActive('calendar/*') ? 'active' : '' ?>"><span class="ni">📅</span> Calendar</a>
    </nav>

    <div class="sidebar-footer">
        <a href="<?= $root ?>/admin/logout.php"><span>🚪</span> Logout</a>
    </div>
</aside>
