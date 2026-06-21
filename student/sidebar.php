<?php
$current = basename($_SERVER['PHP_SELF']);
$dir     = basename(dirname($_SERVER['PHP_SELF']));

// Base path from any subfolder back to student/
$base = ($dir !== 'student') ? '../' : '';
$root = '/placement/student';

function sidebarActive($pages) {
    global $current, $dir;
    foreach ((array)$pages as $p) {
        if (strpos($p, '/') !== false) {
            [$d, $f] = explode('/', $p, 2);
            if ($dir === $d && ($f === '*' || $current === $f)) return true;
        } elseif ($current === $p) return true;
    }
    return false;
}

// Fetch upcoming interviews count
$sid = $_SESSION['user_id'];
$upcomingIV = 0;
if ($conn->query("SHOW TABLES LIKE 'interviews'")->num_rows > 0) {
    $upcomingIV = (int)$conn->query("SELECT COUNT(*) as c FROM interviews WHERE student_id=$sid AND status='scheduled' AND scheduled_at > NOW()")->fetch_assoc()['c'];
}
?>
<style>
.app-layout { display: flex; min-height: 100vh; }
body { overflow-x: hidden; }
.sidebar {
    width: 240px; height: 100vh; flex-shrink: 0;
    background: linear-gradient(180deg, #1a237e 0%, #283593 60%, #3949ab 100%);
    display: flex; flex-direction: column;
    position: fixed; top: 0; left: 0; z-index: 200;
    box-shadow: 3px 0 15px rgba(0,0,0,0.2);
    overflow-y: auto;
    overflow-x: hidden;
    scrollbar-width: thin;
    scrollbar-color: rgba(255,255,255,0.3) transparent;
}
.sidebar::-webkit-scrollbar { width: 4px; }
.sidebar::-webkit-scrollbar-track { background: transparent; }
.sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.3); border-radius: 4px; }
.sidebar::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.5); }
.sidebar-footer { flex-shrink: 0; }
.sidebar-brand { padding: 20px 18px 15px; border-bottom: 1px solid rgba(255,255,255,0.1); flex-shrink: 0; }
.sidebar-brand .brand-text { color: #fff; font-size: 1.2rem; font-weight: 800; text-decoration: none; display: block; }
.sidebar-brand .brand-text span { color: #ffd54f; }
.sidebar-brand .brand-sub { color: #9fa8da; font-size: 0.72rem; margin-top: 3px; }
.sidebar-user { padding: 14px 18px; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; gap: 10px; }
.user-avatar { width: 38px; height: 38px; border-radius: 50%; background: linear-gradient(135deg,#ffd54f,#ffca28); display: flex; align-items: center; justify-content: center; font-size: 1rem; font-weight: 800; color: #1a237e; flex-shrink: 0; }
.user-info .user-name { color: #fff; font-size: 0.85rem; font-weight: 700; }
.user-info .user-role { color: #9fa8da; font-size: 0.72rem; }
.sidebar-section-label { padding: 14px 18px 5px; color: #9fa8da; font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
.sidebar-nav a { display: flex; align-items: center; gap: 10px; padding: 9px 18px; color: #c5cae9; text-decoration: none; font-size: 0.875rem; font-weight: 500; transition: all 0.2s; border-left: 3px solid transparent; }
.sidebar-nav a:hover { background: rgba(255,255,255,0.08); color: #fff; border-left-color: rgba(255,255,255,0.3); }
.sidebar-nav a.active { background: rgba(255,255,255,0.15); color: #ffd54f; border-left-color: #ffd54f; font-weight: 700; }
.sidebar-nav a .nav-icon { font-size: 1rem; width: 20px; text-align: center; flex-shrink: 0; }
.sidebar-nav a .nav-badge { margin-left: auto; background: #e53935; color: #fff; border-radius: 10px; padding: 1px 6px; font-size: 0.65rem; font-weight: 800; }
.sidebar-footer { margin-top: auto; padding: 15px 18px; border-top: 1px solid rgba(255,255,255,0.1); }
.sidebar-footer a { display: flex; align-items: center; gap: 8px; color: #ef9a9a; font-size: 0.85rem; font-weight: 600; text-decoration: none; padding: 8px 10px; border-radius: 8px; transition: all 0.2s; }
.sidebar-footer a:hover { background: rgba(239,83,80,0.15); color: #ef5350; }
.topbar { position: fixed; top: 0; left: 240px; right: 0; height: 58px; z-index: 100; background: #fff; border-bottom: 1px solid #e8eaf6; display: flex; align-items: center; justify-content: space-between; padding: 0 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.topbar-left { display: flex; align-items: center; gap: 12px; }
.topbar-left .page-title { font-size: 1.1rem; font-weight: 700; color: #1a237e; }
.topbar-right { display: flex; align-items: center; gap: 10px; }
.main-content { margin-left: 240px; margin-top: 58px; padding: 24px; flex: 1; min-width: 0; position: relative; z-index: 1; }
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
        <a href="<?= $root ?>/dashboard.php" class="brand-text">🎓 Campus<span>Recruit</span></a>
        <div class="brand-sub">Student Portal</div>
    </div>
    <div class="sidebar-user">
        <div class="user-avatar"><?= strtoupper(substr($_SESSION['name'], 0, 1)) ?></div>
        <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($_SESSION['name']) ?></div>
            <div class="user-role">Student</div>
        </div>
    </div>

    <div class="sidebar-section-label">Main</div>
    <nav class="sidebar-nav">
        <a href="<?= $root ?>/dashboard.php"    class="<?= sidebarActive('dashboard.php')    ? 'active' : '' ?>"><span class="nav-icon">🏠</span> Dashboard</a>
        <a href="<?= $root ?>/jobs.php"         class="<?= sidebarActive('jobs.php')         ? 'active' : '' ?>"><span class="nav-icon">💼</span> Browse Jobs</a>
        <a href="<?= $root ?>/applications.php" class="<?= sidebarActive('applications.php') ? 'active' : '' ?>"><span class="nav-icon">📋</span> My Applications</a>
        <a href="<?= $root ?>/profile.php"      class="<?= sidebarActive('profile.php')      ? 'active' : '' ?>"><span class="nav-icon">👤</span> My Profile</a>
        <a href="<?= $root ?>/notices.php"      class="<?= sidebarActive('notices.php')      ? 'active' : '' ?>"><span class="nav-icon">📢</span> Notices</a>
    </nav>

    <div class="sidebar-section-label">AI & Smart</div>
    <nav class="sidebar-nav">
        <a href="<?= $root ?>/resume_analyzer/index.php"      class="<?= sidebarActive('resume_analyzer/*')      ? 'active' : '' ?>"><span class="nav-icon">🤖</span> AI Resume</a>
        <a href="<?= $root ?>/job_recommendation/index.php"   class="<?= sidebarActive('job_recommendation/*')   ? 'active' : '' ?>"><span class="nav-icon">🎯</span> AI Job Match</a>
        <a href="<?= $root ?>/skill_gap/index.php"            class="<?= sidebarActive('skill_gap/*')            ? 'active' : '' ?>"><span class="nav-icon">🧩</span> Skill Gap</a>
    </nav>

    <div class="sidebar-section-label">Tests & Coding</div>
    <nav class="sidebar-nav">
        <a href="<?= $root ?>/coding/index.php" class="<?= sidebarActive('coding/*') ? 'active' : '' ?>"><span class="nav-icon">💻</span> Coding Practice</a>
    </nav>

    <div class="sidebar-section-label">Career</div>
    <nav class="sidebar-nav">
        <a href="<?= $root ?>/interviews/index.php" class="<?= sidebarActive('interviews/index.php') ? 'active' : '' ?>">
            <span class="nav-icon">🎥</span> Interviews
            <?php if ($upcomingIV > 0): ?><span class="nav-badge"><?= $upcomingIV ?></span><?php endif; ?>
        </a>
        <a href="<?= $root ?>/interviews/minutes.php" class="<?= sidebarActive('interviews/minutes.php') ? 'active' : '' ?>"><span class="nav-icon">📝</span> Minutes & Feedback</a>
        <a href="<?= $root ?>/internships/index.php" class="<?= sidebarActive('internships/*') ? 'active' : '' ?>"><span class="nav-icon">🏢</span> Internships</a>
    </nav>

    <div class="sidebar-section-label">Community</div>
    <nav class="sidebar-nav">
        <a href="/placement/forum/index.php" class="<?= sidebarActive('forum/*') ? 'active' : '' ?>"><span class="nav-icon">💬</span> Forum</a>
    </nav>

    <div class="sidebar-section-label">Account</div>
    <nav class="sidebar-nav">
        <a href="<?= $root ?>/performance/index.php"  class="<?= sidebarActive('performance/*')  ? 'active' : '' ?>"><span class="nav-icon">📊</span> Performance</a>
        <a href="<?= $root ?>/gamification/index.php" class="<?= sidebarActive('gamification/*') ? 'active' : '' ?>"><span class="nav-icon">🏆</span> Achievements</a>
    </nav>

    <div class="sidebar-footer">
        <a href="<?= $root ?>/logout.php"><span>🚪</span> Logout</a>
    </div>
</aside>

<!-- Settings Panel -->
<style>
#settings-overlay{
    display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:9990;
}
#settings-panel{
    position:fixed;top:0;right:-520px;width:500px;height:100vh;z-index:9991;
    background:#f0f2f5;box-shadow:-4px 0 20px rgba(0,0,0,0.15);
    display:flex;flex-direction:column;transition:right 0.3s ease;
}
#settings-panel.open{right:0;}
#settings-panel-header{
    background:linear-gradient(135deg,#1a237e,#3949ab);
    padding:14px 18px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;
}
#settings-panel-header span{color:#fff;font-weight:700;font-size:1rem;}
#settings-close{
    background:none;border:none;color:rgba(255,255,255,0.8);
    font-size:1.3rem;cursor:pointer;padding:4px 8px;border-radius:4px;transition:all 0.2s;
}
#settings-close:hover{color:#fff;background:rgba(255,255,255,0.15);}
#settings-frame{flex:1;border:none;width:100%;}
@media(max-width:600px){
    #settings-panel{width:100%;right:-100%;}
}
</style>

<div id="settings-overlay" onclick="toggleSettings()"></div>
<div id="settings-panel">
    <div id="settings-panel-header">
        <span>🔒 Security &amp; Settings</span>
        <button id="settings-close" onclick="toggleSettings()">✕</button>
    </div>
    <iframe id="settings-frame" src="" title="Security Settings"></iframe>
</div>
<script>
let settingsOpen = false;
function toggleSettings() {
    settingsOpen = !settingsOpen;
    const panel = document.getElementById('settings-panel');
    const overlay = document.getElementById('settings-overlay');
    const frame = document.getElementById('settings-frame');
    panel.classList.toggle('open', settingsOpen);
    overlay.style.display = settingsOpen ? 'block' : 'none';
    if (settingsOpen && !frame.src.includes('security')) {
        frame.src = '/placement/security/index.php';
    }
}
</script>

<!-- Floating Calendar Button -->
<style>
#cal-btn {
    position:fixed;bottom:95px;right:25px;z-index:9998;
    width:58px;height:58px;border-radius:50%;
    background:#fff;
    color:#1a237e;border:none;cursor:pointer;
    box-shadow:0 4px 20px rgba(0,0,0,0.15);
    font-size:1.6rem;display:flex;align-items:center;justify-content:center;
    transition:all 0.3s;
}
#cal-btn:hover{transform:scale(1.1);box-shadow:0 6px 25px rgba(0,0,0,0.25);}
#cal-window {
    position:fixed;bottom:165px;right:25px;z-index:9998;
    width:820px;height:560px;
    background:#fff;border-radius:16px;
    box-shadow:0 10px 40px rgba(0,0,0,0.2);
    display:none;flex-direction:column;overflow:hidden;
    animation:calSlideUp 0.3s ease;
}
@keyframes calSlideUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
#cal-header {
    background:linear-gradient(135deg,#2e7d32,#43a047);
    padding:12px 18px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;
}
#cal-header span{color:#fff;font-weight:700;font-size:1rem;}
#cal-close{
    background:none;border:none;color:rgba(255,255,255,0.8);
    font-size:1.2rem;cursor:pointer;padding:4px 8px;border-radius:4px;transition:all 0.2s;
}
#cal-close:hover{color:#fff;background:rgba(255,255,255,0.15);}
#cal-frame{flex:1;border:none;width:100%;}
@media(max-width:900px){
    #cal-window{width:calc(100vw - 20px);right:10px;bottom:160px;height:70vh;}
}
</style>

<button id="cal-btn" onclick="toggleCal()" title="Open Calendar">📅</button>
<div id="cal-window">
    <div id="cal-header">
        <span>📅 Calendar</span>
        <button id="cal-close" onclick="toggleCal()">✕</button>
    </div>
    <iframe id="cal-frame" src="" title="Calendar"></iframe>
</div>
<script>
let calOpen = false;
function toggleCal() {
    calOpen = !calOpen;
    const win = document.getElementById('cal-window');
    const frame = document.getElementById('cal-frame');
    win.style.display = calOpen ? 'flex' : 'none';
    if (calOpen && !frame.src.includes('calendar')) {
        frame.src = '/placement/calendar/index.php';
    }
}
</script>
