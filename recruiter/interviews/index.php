<?php
require_once '../../includes/config.php';
requireLogin('recruiter');
require_once '../../includes/notify.php';

$uid = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if ($uid === 0) { header('Location: ../../index.php'); exit(); }
$stCo = $conn->prepare("SELECT * FROM companies WHERE user_id=?");
$stCo->bind_param('i', $uid); $stCo->execute();
$company = $stCo->get_result()->fetch_assoc(); $stCo->close();
$cid = (int)($company['id'] ?? 0);

// Ensure columns exist
$conn->query("ALTER TABLE interviews ADD COLUMN IF NOT EXISTS minutes TEXT DEFAULT NULL");
$conn->query("ALTER TABLE interviews ADD COLUMN IF NOT EXISTS recording_url VARCHAR(500) DEFAULT NULL");

$msg = '';

// Schedule interview
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule'])) {
    $app_id     = (int)$_POST['application_id'];
    $student_id = (int)$_POST['student_id'];
    $job_id     = (int)$_POST['job_id'];
    $scheduled  = trim($_POST['scheduled_at'] ?? '');
    $duration   = (int)$_POST['duration'];
    $platform   = trim($_POST['platform'] ?? '');
    $link       = trim($_POST['meeting_link'] ?? '');
    $notes      = trim($_POST['notes'] ?? '');

    if (empty($link) && $platform === 'jitsi') {
        $room = 'CampusRecruit-' . strtoupper(substr(md5($app_id . time()), 0, 8));
        $link = "https://meet.jit.si/$room";
    }

    $stI = $conn->prepare("INSERT INTO interviews (application_id,job_id,student_id,company_id,scheduled_at,duration,meeting_link,platform,notes) VALUES (?,?,?,?,?,?,?,?,?)");
    $stI->bind_param('iiiisisss', $app_id, $job_id, $student_id, $cid, $scheduled, $duration, $link, $platform, $notes);
    $stI->execute(); $stI->close();

    $stUpA = $conn->prepare("UPDATE applications SET status='shortlisted' WHERE id=?");
    $stUpA->bind_param('i', $app_id); $stUpA->execute(); $stUpA->close();

    $stJI = $conn->prepare("SELECT j.title, c.company_name FROM jobs j JOIN companies c ON j.company_id=c.id WHERE j.id=?");
    $stJI->bind_param('i', $job_id); $stJI->execute();
    $jobInfo = $stJI->get_result()->fetch_assoc(); $stJI->close();
    if ($jobInfo) notifyInterviewScheduled($conn, $student_id, $jobInfo['title'], $jobInfo['company_name'], $scheduled);
    $msg = '<div class="alert alert-success">✅ Interview scheduled! Student has been notified.</div>';
}

// Update interview status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $iid    = (int)$_POST['interview_id'];
    $status = trim($_POST['status'] ?? '');
    if (in_array($status, ['scheduled','completed','cancelled','rescheduled'])) {
        $stUS = $conn->prepare("UPDATE interviews SET status=? WHERE id=? AND company_id=?");
        $stUS->bind_param('sii', $status, $iid, $cid); $stUS->execute(); $stUS->close();
        $msg = '<div class="alert alert-success">Status updated.</div>';
    }
}

// Save minutes & recording
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_minutes'])) {
    $iid       = (int)$_POST['interview_id'];
    $minutes   = trim($_POST['minutes'] ?? '');
    $recording = trim($_POST['recording_url'] ?? '');
    $stSM = $conn->prepare("UPDATE interviews SET minutes=?, recording_url=? WHERE id=? AND company_id=?");
    $stSM->bind_param('ssii', $minutes, $recording, $iid, $cid); $stSM->execute(); $stSM->close();
    $msg = '<div class="alert alert-success">✅ Interview minutes & recording saved.</div>';
}

// Select / Reject candidate
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['decide_candidate'])) {
    $iid        = (int)$_POST['interview_id'];
    $app_id     = (int)$_POST['app_id'];
    $student_id = (int)$_POST['student_id'];
    $decision   = trim($_POST['decision'] ?? '');

    if (in_array($decision, ['selected', 'rejected'])) {
        $stDC1 = $conn->prepare("UPDATE interviews SET status='completed' WHERE id=? AND company_id=?");
        $stDC1->bind_param('ii', $iid, $cid); $stDC1->execute(); $stDC1->close();
        $stDC2 = $conn->prepare("UPDATE applications a JOIN jobs j ON a.job_id=j.id SET a.status=? WHERE a.id=? AND j.company_id=?");
        $stDC2->bind_param('sii', $decision, $app_id, $cid); $stDC2->execute(); $stDC2->close();
        $stJD = $conn->prepare("SELECT j.title, c.company_name FROM interviews i JOIN jobs j ON i.job_id=j.id JOIN companies c ON j.company_id=c.id WHERE i.id=?");
        $stJD->bind_param('i', $iid); $stJD->execute();
        $jobInfo = $stJD->get_result()->fetch_assoc(); $stJD->close();
        if ($jobInfo) notifyApplicationStatus($conn, $student_id, $decision, $jobInfo['title'], $jobInfo['company_name']);
        $label = $decision === 'selected' ? '✅ Candidate selected!' : '❌ Candidate rejected.';
        $msg = "<div class='alert alert-success'>$label Student has been notified.</div>";
    }
}

// Delete interview
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_interview'])) {
    $iid = (int)$_POST['interview_id'];
    $stDel = $conn->prepare("DELETE FROM interviews WHERE id=? AND company_id=?");
    $stDel->bind_param('ii', $iid, $cid); $stDel->execute(); $stDel->close();
    $msg = '<div class="alert alert-success">Interview deleted.</div>';
}

// Applicants not yet interviewed
$stSL = $conn->prepare("SELECT a.id as app_id, a.student_id, a.job_id, u.name as student_name, u.email, sp.department, sp.cgpa, j.title as job_title FROM applications a JOIN users u ON a.student_id=u.id LEFT JOIN student_profiles sp ON sp.user_id=u.id JOIN jobs j ON a.job_id=j.id WHERE j.company_id=? AND a.status IN ('applied','shortlisted') AND a.id NOT IN (SELECT application_id FROM interviews WHERE status NOT IN ('cancelled')) ORDER BY a.applied_at DESC");
$stSL->bind_param('i', $cid); $stSL->execute();
$shortlisted = $stSL->get_result(); $stSL->close();

$stIVL = $conn->prepare("SELECT i.*, u.name as student_name, u.email, j.title as job_title, sp.department, a.status as app_status, a.id as app_id FROM interviews i JOIN users u ON i.student_id=u.id LEFT JOIN student_profiles sp ON sp.user_id=u.id JOIN jobs j ON i.job_id=j.id JOIN applications a ON i.application_id=a.id WHERE i.company_id=? ORDER BY i.scheduled_at DESC");
$stIVL->bind_param('i', $cid); $stIVL->execute();
$interviews = $stIVL->get_result(); $stIVL->close();

$iv_total       = (int)(function() use ($conn,$cid){ $s=$conn->prepare("SELECT COUNT(*) as c FROM interviews WHERE company_id=?"); $s->bind_param('i',$cid); $s->execute(); $c=(int)$s->get_result()->fetch_assoc()['c']; $s->close(); return $c; })();
$iv_scheduled   = (int)(function() use ($conn,$cid){ $s=$conn->prepare("SELECT COUNT(*) as c FROM interviews WHERE company_id=? AND status='scheduled'"); $s->bind_param('i',$cid); $s->execute(); $c=(int)$s->get_result()->fetch_assoc()['c']; $s->close(); return $c; })();
$selected_count = (int)(function() use ($conn,$cid){ $s=$conn->prepare("SELECT COUNT(*) as c FROM applications a JOIN jobs j ON a.job_id=j.id WHERE j.company_id=? AND a.status='selected'"); $s->bind_param('i',$cid); $s->execute(); $c=(int)$s->get_result()->fetch_assoc()['c']; $s->close(); return $c; })();

$platformIcons = ['google_meet'=>'🟢','zoom'=>'🔵','teams'=>'🟣','jitsi'=>'🟠','other'=>'⚪'];
$platformNames = ['google_meet'=>'Google Meet','zoom'=>'Zoom','teams'=>'MS Teams','jitsi'=>'Jitsi Meet','other'=>'Other'];
$statusColors  = ['scheduled'=>'#1565c0','completed'=>'#2e7d32','cancelled'=>'#c62828','rescheduled'=>'#e65100'];
$statusBg      = ['scheduled'=>'#e3f2fd','completed'=>'#e8f5e9','cancelled'=>'#ffebee','rescheduled'=>'#fff8e1'];
$companyName   = htmlspecialchars($company['company_name'] ?? $_SESSION['name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Interviews - CampusRecruit</title>
<link rel="stylesheet" href="../../css/style.css">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',sans-serif;background:#f0f4f8;color:#333}
.app-layout{display:flex;min-height:100vh}

.sidebar{width:250px;min-height:100vh;flex-shrink:0;background:linear-gradient(180deg,#004d40 0%,#00695c 50%,#00796b 100%);display:flex;flex-direction:column;position:fixed;top:0;left:0;z-index:200;box-shadow:3px 0 20px rgba(0,0,0,0.25);overflow-y:auto}
.sidebar-brand{padding:22px 20px 16px;border-bottom:1px solid rgba(255,255,255,0.12)}
.sidebar-brand .brand-name{color:#fff;font-size:1.25rem;font-weight:800;text-decoration:none;display:block}
.sidebar-brand .brand-name span{color:#80cbc4}
.sidebar-brand .brand-sub{color:#80cbc4;font-size:0.72rem;margin-top:4px}
.sidebar-company{padding:14px 20px;border-bottom:1px solid rgba(255,255,255,0.1);display:flex;align-items:center;gap:12px}
.company-avatar{width:42px;height:42px;border-radius:10px;background:linear-gradient(135deg,#80cbc4,#4db6ac);display:flex;align-items:center;justify-content:center;font-size:1.1rem;font-weight:800;color:#004d40;flex-shrink:0}
.company-name-text{color:#fff;font-size:0.88rem;font-weight:700;line-height:1.3}
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
.stat-box.blue{border-top-color:#1565c0}
.stat-box.green{border-top-color:#2e7d32}

.card{background:#fff;border-radius:12px;padding:22px;box-shadow:0 2px 10px rgba(0,0,0,0.07);margin-bottom:22px}
.card h2{color:#004d40;font-size:1.05rem;margin-bottom:16px;padding-bottom:10px;border-bottom:2px solid #e0f2f1}

.applicant-row{display:flex;justify-content:space-between;align-items:center;padding:12px 15px;border:1px solid #e0e0e0;border-radius:8px;margin-bottom:8px;flex-wrap:wrap;gap:10px}
.applicant-row:hover{background:#f8f9ff;border-color:#c5cae9}

.interview-card{background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 12px rgba(0,0,0,0.08);margin-bottom:16px;border-left:5px solid #3f51b5;transition:transform 0.2s}
.interview-card:hover{transform:translateY(-2px)}

.platform-badge{display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:20px;font-size:0.82rem;font-weight:700;background:#f5f5f5;color:#333}
.status-badge{display:inline-block;padding:4px 12px;border-radius:20px;font-size:0.82rem;font-weight:700}
.meet-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 18px;border-radius:8px;background:linear-gradient(135deg,#1a237e,#3949ab);color:#fff;text-decoration:none;font-weight:700;font-size:0.9rem;transition:all 0.2s}
.meet-btn:hover{background:linear-gradient(135deg,#283593,#3f51b5);transform:translateY(-1px)}

.minutes-section{background:#f8f9ff;border-radius:8px;padding:14px;margin-top:12px;border:1px solid #e8eaf6}
.minutes-section label{font-size:0.82rem;font-weight:700;color:#3f51b5;display:block;margin-bottom:5px}
.minutes-section textarea{width:100%;padding:8px 10px;border:1px solid #c5cae9;border-radius:6px;font-size:0.85rem;font-family:inherit;resize:vertical}
.minutes-section input{width:100%;padding:7px 10px;border:1px solid #c5cae9;border-radius:6px;font-size:0.85rem;margin-bottom:8px}

.decide-section{background:#fff8e1;border-radius:8px;padding:14px;margin-top:10px;border:2px solid #ffd54f}
.decide-section p{font-size:0.85rem;font-weight:700;color:#e65100;margin-bottom:10px}
.btn-select{background:#2e7d32;color:#fff;border:none;padding:9px 20px;border-radius:20px;font-size:0.85rem;font-weight:700;cursor:pointer;transition:all 0.2s}
.btn-select:hover{background:#1b5e20}
.btn-reject{background:#c62828;color:#fff;border:none;padding:9px 20px;border-radius:20px;font-size:0.85rem;font-weight:700;cursor:pointer;transition:all 0.2s}
.btn-reject:hover{background:#b71c1c}
.decided-badge{display:inline-block;padding:8px 18px;border-radius:20px;font-size:0.9rem;font-weight:700}
.decided-selected{background:#e8f5e9;color:#2e7d32;border:2px solid #66bb6a}
.decided-rejected{background:#ffebee;color:#c62828;border:2px solid #ef9a9a}

.recording-link{display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:8px;background:#e3f2fd;color:#1565c0;text-decoration:none;font-size:0.85rem;font-weight:700;transition:all 0.2s}
.recording-link:hover{background:#bbdefb}

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
        <a href="../dashboard.php" class="brand-name">🎓 Campus<span>Recruit</span></a>
        <div class="brand-sub">🏢 Recruiter Portal</div>
    </div>
    <div class="sidebar-company">
        <div class="company-avatar"><?= strtoupper(substr($company['company_name'] ?? $_SESSION['name'], 0, 1)) ?></div>
        <div>
            <div class="company-name-text"><?= $companyName ?></div>
            <div class="company-role"><?= htmlspecialchars($company['industry'] ?? 'Recruiter') ?></div>
        </div>
    </div>

    <div class="sec-label">Hiring</div>
    <nav class="sidebar-nav">
        <a href="../dashboard.php"><span class="ni">🏠</span> Dashboard</a>
        <a href="index.php" class="active"><span class="ni">🎥</span> Interviews</a>
        <a href="../feedback.php"><span class="ni">💬</span> Student Feedback</a>
    </nav>

    <div class="sec-label">Account</div>
    <nav class="sidebar-nav">
        <a href="../profile.php"><span class="ni">🏢</span> Company Profile</a>
        <a href="../../security/index.php"><span class="ni">🔒</span> Security</a>
    </nav>

    <div class="sidebar-footer">
        <a href="../logout.php"><span>🚪</span> Logout</a>
    </div>
</aside>

<div class="topbar">
    <div class="topbar-left">
        <button class="hamburger" onclick="toggleSidebar()">☰</button>
        <span class="page-title">🎥 Interview Management</span>
    </div>
    <div class="topbar-right">
        <span class="topbar-company-badge">🏢 <?= $companyName ?></span>
        <?php require_once '../../notifications/widget.php'; ?>
    </div>
</div>

<main class="main-content">

    <?= $msg ?>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-box blue">
            <div class="num"><?= $iv_total ?></div>
            <div class="lbl">🎥 Total Interviews</div>
        </div>
        <div class="stat-box">
            <div class="num"><?= $iv_scheduled ?></div>
            <div class="lbl">📅 Upcoming</div>
        </div>
        <div class="stat-box green">
            <div class="num"><?= $selected_count ?></div>
            <div class="lbl">✅ Candidates Selected</div>
        </div>
    </div>

    <!-- Schedule New Interview -->
    <?php if ($shortlisted->num_rows > 0): ?>
    <div class="card">
        <h2>📅 Schedule New Interview</h2>
        <p style="color:#666;margin-bottom:15px;font-size:0.9rem">Select an applicant to schedule their interview.</p>
        <?php while($s = $shortlisted->fetch_assoc()): ?>
        <div class="applicant-row">
            <div>
                <strong style="color:#1a237e"><?= htmlspecialchars($s['student_name']) ?></strong>
                <span style="color:#666;font-size:0.85rem;margin-left:8px"><?= htmlspecialchars($s['email']) ?></span>
                <div style="font-size:0.82rem;color:#555;margin-top:3px">
                    💼 <?= htmlspecialchars($s['job_title']) ?>
                    <?php if ($s['department']): ?> · 🎓 <?= htmlspecialchars($s['department']) ?><?php endif; ?>
                    <?php if ($s['cgpa']): ?> · 📊 CGPA: <?= $s['cgpa'] ?><?php endif; ?>
                </div>
            </div>
            <button class="btn btn-primary btn-sm"
                onclick="openScheduleForm(<?= $s['app_id'] ?>,<?= $s['student_id'] ?>,<?= $s['job_id'] ?>,'<?= htmlspecialchars(addslashes($s['student_name'])) ?>','<?= htmlspecialchars(addslashes($s['job_title'])) ?>')">
                📅 Schedule Interview
            </button>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>

    <!-- Schedule Modal -->
    <div id="schedule-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center">
        <div style="background:#fff;border-radius:12px;padding:30px;width:100%;max-width:550px;max-height:90vh;overflow-y:auto;margin:20px">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
                <h3 style="color:#1a237e">📅 Schedule Interview</h3>
                <button onclick="closeModal()" style="background:none;border:none;font-size:1.5rem;cursor:pointer;color:#666">✕</button>
            </div>
            <div id="modal-info" style="background:#e8eaf6;border-radius:8px;padding:12px;margin-bottom:20px;font-size:0.9rem;color:#1a237e"></div>
            <form method="POST">
                <input type="hidden" name="schedule" value="1">
                <input type="hidden" name="application_id" id="modal-app-id">
                <input type="hidden" name="student_id" id="modal-student-id">
                <input type="hidden" name="job_id" id="modal-job-id">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="form-group">
                        <label>Date & Time *</label>
                        <input type="datetime-local" name="scheduled_at" required min="<?= date('Y-m-d\TH:i') ?>">
                    </div>
                    <div class="form-group">
                        <label>Duration</label>
                        <select name="duration">
                            <option value="30">30 minutes</option>
                            <option value="45">45 minutes</option>
                            <option value="60" selected>60 minutes</option>
                            <option value="90">90 minutes</option>
                            <option value="120">2 hours</option>
                        </select>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="form-group">
                        <label>Platform</label>
                        <select name="platform" id="platform-select" onchange="updateLinkHint()">
                            <option value="jitsi">🟠 Jitsi (Auto-generated)</option>
                            <option value="google_meet">🟢 Google Meet</option>
                            <option value="zoom">🔵 Zoom</option>
                            <option value="teams">🟣 MS Teams</option>
                            <option value="other">⚪ Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Meeting Link</label>
                        <input type="url" name="meeting_link" id="meeting-link-input" placeholder="Leave empty for Jitsi auto-generate">
                        <small id="link-hint" style="color:#3f51b5;font-size:0.78rem">Jitsi link will be auto-generated</small>
                    </div>
                </div>
                <div class="form-group">
                    <label>Notes for Student</label>
                    <textarea name="notes" rows="3" placeholder="e.g. Please join 5 minutes early. Have your resume ready..."></textarea>
                </div>
                <div style="display:flex;gap:10px">
                    <button type="submit" class="btn btn-primary">📅 Schedule</button>
                    <button type="button" onclick="closeModal()" class="btn" style="background:#607d8b;color:#fff">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- All Interviews -->
    <div class="card">
        <h2>📋 All Interviews</h2>
        <?php if ($interviews->num_rows === 0): ?>
        <div style="text-align:center;padding:40px;color:#999">
            <div style="font-size:3rem;margin-bottom:10px">🎥</div>
            <p>No interviews scheduled yet.</p>
        </div>
        <?php else: ?>
        <?php while($iv = $interviews->fetch_assoc()):
            $isPast = strtotime($iv['scheduled_at']) < time();
            $isDecided = in_array($iv['app_status'], ['selected', 'rejected']);
        ?>
        <div class="interview-card" style="border-left-color:<?= $statusColors[$iv['status']] ?>">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px">
                <div style="flex:1;min-width:0">
                    <!-- Header -->
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:6px">
                        <strong style="color:#1a237e;font-size:1.05rem"><?= htmlspecialchars($iv['student_name']) ?></strong>
                        <span class="status-badge" style="background:<?= $statusBg[$iv['status']] ?>;color:<?= $statusColors[$iv['status']] ?>"><?= ucfirst($iv['status']) ?></span>
                        <span class="platform-badge"><?= $platformIcons[$iv['platform']] ?> <?= $platformNames[$iv['platform']] ?></span>
                        <?php if ($isDecided): ?>
                        <span class="decided-badge <?= $iv['app_status']==='selected' ? 'decided-selected' : 'decided-rejected' ?>">
                            <?= $iv['app_status']==='selected' ? '✅ Selected' : '❌ Rejected' ?>
                        </span>
                        <?php endif; ?>
                    </div>

                    <!-- Info -->
                    <div style="color:#555;font-size:0.88rem;margin-bottom:8px">
                        💼 <?= htmlspecialchars($iv['job_title']) ?>
                        <?php if ($iv['department']): ?> · 🎓 <?= htmlspecialchars($iv['department']) ?><?php endif; ?>
                        <br>📧 <?= htmlspecialchars($iv['email']) ?>
                    </div>
                    <div style="display:flex;gap:15px;flex-wrap:wrap;font-size:0.85rem;color:#333;margin-bottom:10px">
                        <span>📅 <?= date('D, d M Y', strtotime($iv['scheduled_at'])) ?></span>
                        <span>🕐 <?= date('h:i A', strtotime($iv['scheduled_at'])) ?></span>
                        <span>⏱️ <?= $iv['duration'] ?> min</span>
                        <?php if ($isPast && $iv['status']==='scheduled'): ?>
                        <span style="color:#e65100;font-weight:700">⚠️ Past due</span>
                        <?php endif; ?>
                    </div>

                    <?php if ($iv['notes']): ?>
                    <div style="background:#f5f5f5;border-radius:6px;padding:8px 12px;font-size:0.85rem;color:#555;margin-bottom:10px">
                        📝 <?= htmlspecialchars($iv['notes']) ?>
                    </div>
                    <?php endif; ?>

                    <!-- Join + Recording links -->
                    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:10px">
                        <?php if ($iv['meeting_link']): ?>
                        <a href="<?= htmlspecialchars($iv['meeting_link']) ?>" target="_blank" class="meet-btn">
                            <?= $platformIcons[$iv['platform']] ?> Join Meeting
                        </a>
                        <button onclick="copyLink('<?= htmlspecialchars(addslashes($iv['meeting_link'])) ?>')"
                            class="btn btn-sm" style="background:#e8eaf6;color:#3f51b5">📋 Copy Link</button>
                        <?php endif; ?>
                        <?php if ($iv['recording_url']): ?>
                        <a href="<?= htmlspecialchars($iv['recording_url']) ?>" target="_blank" class="recording-link">
                            🎬 View Recording
                        </a>
                        <?php endif; ?>
                    </div>

                    <!-- Interview Minutes & Recording -->
                    <div class="minutes-section">
                        <form method="POST">
                            <input type="hidden" name="interview_id" value="<?= $iv['id'] ?>">
                            <label>📝 Interview Minutes / Notes</label>
                            <textarea name="minutes" rows="3"
                                placeholder="Write interview summary, key observations, candidate performance notes..."><?= htmlspecialchars($iv['minutes'] ?? '') ?></textarea>
                            <label style="margin-top:8px">🎬 Recording URL</label>
                            <input type="url" name="recording_url"
                                placeholder="Paste recording link (Google Drive, Zoom, etc.)..."
                                value="<?= htmlspecialchars($iv['recording_url'] ?? '') ?>">
                            <button type="submit" name="save_minutes"
                                style="background:#3f51b5;color:#fff;border:none;padding:7px 18px;border-radius:20px;font-size:0.82rem;font-weight:700;cursor:pointer">
                                💾 Save
                            </button>
                        </form>
                    </div>

                    <!-- Decide Candidate -->
                    <?php if (!$isDecided): ?>
                    <div class="decide-section">
                        <p>🏆 Final Decision — Choose this candidate's outcome:</p>
                        <form method="POST" style="display:inline"
                            onsubmit="return confirm('Select this candidate? This will notify the student.')">
                            <input type="hidden" name="interview_id" value="<?= $iv['id'] ?>">
                            <input type="hidden" name="app_id" value="<?= $iv['app_id'] ?>">
                            <input type="hidden" name="student_id" value="<?= $iv['student_id'] ?>">
                            <input type="hidden" name="decision" value="selected">
                            <button type="submit" name="decide_candidate" class="btn-select">✅ Select This Person</button>
                        </form>
                        <form method="POST" style="display:inline;margin-left:8px"
                            onsubmit="return confirm('Reject this candidate? This will notify the student.')">
                            <input type="hidden" name="interview_id" value="<?= $iv['id'] ?>">
                            <input type="hidden" name="app_id" value="<?= $iv['app_id'] ?>">
                            <input type="hidden" name="student_id" value="<?= $iv['student_id'] ?>">
                            <input type="hidden" name="decision" value="rejected">
                            <button type="submit" name="decide_candidate" class="btn-reject">❌ Reject</button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Right: Status update + delete -->
                <div style="display:flex;flex-direction:column;gap:6px;min-width:140px">
                    <form method="POST" style="display:flex;flex-direction:column;gap:5px">
                        <input type="hidden" name="interview_id" value="<?= $iv['id'] ?>">
                        <select name="status" style="padding:5px 8px;border:1px solid #ddd;border-radius:5px;font-size:0.82rem">
                            <option value="scheduled"   <?= $iv['status']==='scheduled'   ?'selected':'' ?>>Scheduled</option>
                            <option value="completed"   <?= $iv['status']==='completed'   ?'selected':'' ?>>Completed</option>
                            <option value="rescheduled" <?= $iv['status']==='rescheduled' ?'selected':'' ?>>Rescheduled</option>
                            <option value="cancelled"   <?= $iv['status']==='cancelled'   ?'selected':'' ?>>Cancelled</option>
                        </select>
                        <button name="update_status" class="btn btn-success btn-sm">Update</button>
                    </form>
                    <form method="POST" onsubmit="return confirm('Delete this interview?')">
                        <input type="hidden" name="interview_id" value="<?= $iv['id'] ?>">
                        <button name="delete_interview" class="btn btn-danger btn-sm" style="width:100%">Delete</button>
                    </form>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
        <?php endif; ?>
    </div>

</main>
</div>

<?php require_once '../../chatbot/widget.php'; ?>
<script>
function openScheduleForm(appId, studentId, jobId, studentName, jobTitle) {
    document.getElementById('modal-app-id').value = appId;
    document.getElementById('modal-student-id').value = studentId;
    document.getElementById('modal-job-id').value = jobId;
    document.getElementById('modal-info').innerHTML = '👤 <strong>' + studentName + '</strong> — ' + jobTitle;
    document.getElementById('schedule-modal').style.display = 'flex';
}
function closeModal() { document.getElementById('schedule-modal').style.display = 'none'; }
function updateLinkHint() {
    const p = document.getElementById('platform-select').value;
    const hint = document.getElementById('link-hint');
    const input = document.getElementById('meeting-link-input');
    if (p === 'jitsi') {
        hint.textContent = 'Jitsi link will be auto-generated if left empty';
        input.placeholder = 'Leave empty for auto-generate';
    } else {
        hint.textContent = 'Paste your meeting link';
        input.placeholder = 'https://...';
    }
}
function copyLink(link) { navigator.clipboard.writeText(link).then(() => alert('Meeting link copied!')); }
document.getElementById('schedule-modal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
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
