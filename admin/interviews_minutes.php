<?php
require_once '../includes/config.php';
requireLogin('admin');
require_once '../includes/notify.php';

$conn->query("ALTER TABLE interviews ADD COLUMN IF NOT EXISTS minutes TEXT DEFAULT NULL");
$conn->query("ALTER TABLE interviews ADD COLUMN IF NOT EXISTS recording_url VARCHAR(500) DEFAULT NULL");
$conn->query("ALTER TABLE applications ADD COLUMN IF NOT EXISTS feedback TEXT DEFAULT NULL");
$conn->query("ALTER TABLE applications ADD COLUMN IF NOT EXISTS feedback_at TIMESTAMP NULL DEFAULT NULL");

$msg = $_SESSION['admin_msg'] ?? ''; unset($_SESSION['admin_msg']);

// Save minutes & recording
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_minutes'])) {
    $iid = (int)$_POST['interview_id'];
    $minutes = trim($_POST['minutes'] ?? '');
    $recording = trim($_POST['recording_url'] ?? '');
    $st = $conn->prepare("UPDATE interviews SET minutes=?, recording_url=? WHERE id=?");
    $st->bind_param('ssi', $minutes, $recording, $iid); $st->execute(); $st->close();
    $_SESSION['admin_msg'] = '<div class="alert alert-success">✅ Minutes & recording saved.</div>';
    header('Location: interviews_minutes.php'); exit();
}

// Save feedback
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_feedback'])) {
    $app_id = (int)$_POST['app_id'];
    $feedback = trim($_POST['feedback'] ?? '');
    $st = $conn->prepare("UPDATE applications SET feedback=?, feedback_at=NOW() WHERE id=?");
    $st->bind_param('si', $feedback, $app_id); $st->execute(); $st->close();
    $row = $conn->query("SELECT a.student_id, j.title, c.company_name FROM applications a JOIN jobs j ON a.job_id=j.id JOIN companies c ON j.company_id=c.id WHERE a.id=$app_id")->fetch_assoc();
    if ($row) createNotification($conn, $row['student_id'], 'system', '💬 Interview Feedback', 'Feedback posted for your '.$row['title'].' interview at '.$row['company_name'].'.', '/placement/student/interviews/minutes.php');
    $_SESSION['admin_msg'] = '<div class="alert alert-success">✅ Feedback saved & student notified.</div>';
    header('Location: interviews_minutes.php'); exit();
}

$search  = trim($_GET['q'] ?? '');
$statusF = trim($_GET['status'] ?? '');
$where   = "WHERE 1";
if ($search)  $where .= " AND (u.name LIKE '%".$conn->real_escape_string($search)."%' OR j.title LIKE '%".$conn->real_escape_string($search)."%' OR c.company_name LIKE '%".$conn->real_escape_string($search)."%')";
if ($statusF) $where .= " AND i.status='".$conn->real_escape_string($statusF)."'";

$interviews = $conn->query("SELECT i.*, u.name as student_name, u.email, j.title as job_title,
    c.company_name, sp.department, sp.cgpa, a.status as app_status, a.id as app_id,
    a.feedback, a.feedback_at, a.student_id
    FROM interviews i
    JOIN users u ON i.student_id=u.id
    LEFT JOIN student_profiles sp ON sp.user_id=u.id
    JOIN jobs j ON i.job_id=j.id
    JOIN companies c ON j.company_id=c.id
    JOIN applications a ON i.application_id=a.id
    $where ORDER BY i.scheduled_at DESC");

$stats = [
    'total'    => $conn->query("SELECT COUNT(*) as c FROM interviews")->fetch_assoc()['c'],
    'minutes'  => $conn->query("SELECT COUNT(*) as c FROM interviews WHERE minutes IS NOT NULL AND minutes!=''")->fetch_assoc()['c'],
    'rec'      => $conn->query("SELECT COUNT(*) as c FROM interviews WHERE recording_url IS NOT NULL AND recording_url!=''")->fetch_assoc()['c'],
    'feedback' => $conn->query("SELECT COUNT(*) as c FROM applications a JOIN interviews i ON i.application_id=a.id WHERE a.feedback IS NOT NULL AND a.feedback!=''")->fetch_assoc()['c'],
];

$sc = ['scheduled'=>'#1565c0','completed'=>'#2e7d32','cancelled'=>'#c62828','rescheduled'=>'#e65100'];
$sb = ['scheduled'=>'#e3f2fd','completed'=>'#e8f5e9','cancelled'=>'#ffebee','rescheduled'=>'#fff8e1'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Interview Minutes & Feedback - Admin</title>
<link rel="stylesheet" href="../css/style.css">
<style>
.iv-card{background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 10px rgba(0,0,0,0.07);margin-bottom:16px;border-left:5px solid #3f51b5}
.min-box{background:#f8f9ff;border-radius:8px;padding:14px;margin-top:12px;border:1px solid #e8eaf6}
.min-box label{font-size:0.82rem;font-weight:700;color:#3f51b5;display:block;margin-bottom:5px}
.min-box textarea,.min-box input[type=url]{width:100%;padding:8px 10px;border:1px solid #c5cae9;border-radius:6px;font-size:0.85rem;font-family:inherit;margin-bottom:6px}
.min-box textarea{resize:vertical}
.fb-box{background:#fff8e1;border-radius:8px;padding:14px;margin-top:10px;border:1px solid #ffd54f}
.fb-box label{font-size:0.82rem;font-weight:700;color:#e65100;display:block;margin-bottom:5px}
.fb-box textarea{width:100%;padding:8px;border:1px solid #ffe082;border-radius:6px;font-size:0.85rem;font-family:inherit;resize:vertical}
</style>
</head>
<body>
<?php require_once 'sidebar.php'; ?>
<div class="topbar">
    <div class="topbar-left">
        <button class="hamburger" onclick="toggleSidebar()">☰</button>
        <span class="page-title">🎥 Interview Minutes & Feedback</span>
    </div>
    <div class="topbar-right"><?php require_once '../notifications/widget.php'; ?></div>
</div>
<div class="main-content">
    <?= $msg ?>

    <!-- Stats -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px">
        <?php foreach([['🎥 Total',$stats['total'],'#1565c0'],['📝 With Minutes',$stats['minutes'],'#2e7d32'],['🎬 With Recordings',$stats['rec'],'#7b1fa2'],['💬 Feedback Given',$stats['feedback'],'#e65100']] as [$l,$v,$c]): ?>
        <div style="background:#fff;border-radius:12px;padding:18px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,0.07);border-top:4px solid <?= $c ?>">
            <div style="font-size:1.8rem;font-weight:800;color:<?= $c ?>"><?= $v ?></div>
            <div style="font-size:0.82rem;color:#666;margin-top:4px"><?= $l ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Filters -->
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px">
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="🔍 Search student, job, company..." style="flex:1;min-width:180px;padding:8px 14px;border:1.5px solid #ddd;border-radius:20px;font-size:0.88rem">
        <select name="status" style="padding:8px 14px;border:1.5px solid #ddd;border-radius:20px;font-size:0.88rem">
            <option value="">All Status</option>
            <?php foreach(['scheduled','completed','cancelled','rescheduled'] as $s): ?>
            <option value="<?= $s ?>" <?= $statusF===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-primary btn-sm">Filter</button>
        <a href="interviews_minutes.php" class="btn btn-sm" style="background:#e8eaf6;color:#333">Clear</a>
    </form>

    <!-- List -->
    <?php if ($interviews->num_rows === 0): ?>
    <div style="text-align:center;padding:50px;color:#999;background:#fff;border-radius:12px">
        <div style="font-size:3rem;margin-bottom:10px">🎥</div><p>No interviews found.</p>
    </div>
    <?php else: while($iv = $interviews->fetch_assoc()): ?>
    <div class="iv-card" style="border-left-color:<?= $sc[$iv['status']] ?? '#3f51b5' ?>">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px;margin-bottom:10px">
            <div>
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px">
                    <strong style="color:#1a237e;font-size:1rem"><?= htmlspecialchars($iv['student_name']) ?></strong>
                    <span style="background:<?= $sb[$iv['status']] ?? '#f5f5f5' ?>;color:<?= $sc[$iv['status']] ?? '#333' ?>;padding:2px 10px;border-radius:12px;font-size:0.75rem;font-weight:700"><?= ucfirst($iv['status']) ?></span>
                    <?php if ($iv['app_status']==='selected'): ?><span style="background:#e8f5e9;color:#2e7d32;padding:2px 10px;border-radius:12px;font-size:0.75rem;font-weight:700">✅ Selected</span>
                    <?php elseif ($iv['app_status']==='rejected'): ?><span style="background:#ffebee;color:#c62828;padding:2px 10px;border-radius:12px;font-size:0.75rem;font-weight:700">❌ Rejected</span><?php endif; ?>
                </div>
                <div style="font-size:0.85rem;color:#555">💼 <?= htmlspecialchars($iv['job_title']) ?> · 🏢 <?= htmlspecialchars($iv['company_name']) ?><?php if($iv['department']): ?> · 🎓 <?= htmlspecialchars($iv['department']) ?><?php endif; ?></div>
                <div style="font-size:0.8rem;color:#999;margin-top:2px">📅 <?= date('d M Y, h:i A', strtotime($iv['scheduled_at'])) ?> · ⏱ <?= $iv['duration'] ?> min · 📧 <?= htmlspecialchars($iv['email']) ?></div>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <?php if ($iv['meeting_link']): ?><a href="<?= htmlspecialchars($iv['meeting_link']) ?>" target="_blank" class="btn btn-primary btn-sm">🔗 Meeting</a><?php endif; ?>
                <?php if ($iv['recording_url']): ?><a href="<?= htmlspecialchars($iv['recording_url']) ?>" target="_blank" class="btn btn-sm" style="background:#7b1fa2;color:#fff">🎬 Recording</a><?php endif; ?>
            </div>
        </div>

        <?php if (!empty($iv['minutes'])): ?>
        <div style="background:#e8f5e9;border-radius:8px;padding:10px 14px;margin-bottom:8px;font-size:0.87rem;color:#1b5e20;border-left:3px solid #43a047">
            <strong>📝 Saved Minutes:</strong> <?= nl2br(htmlspecialchars($iv['minutes'])) ?>
        </div>
        <?php endif; ?>

        <!-- Minutes & Recording Form -->
        <div class="min-box">
            <form method="POST">
                <input type="hidden" name="interview_id" value="<?= $iv['id'] ?>">
                <label>📝 Interview Minutes / Notes</label>
                <textarea name="minutes" rows="3" placeholder="Interview summary, candidate performance..."><?= htmlspecialchars($iv['minutes'] ?? '') ?></textarea>
                <label>🎬 Recording URL</label>
                <input type="url" name="recording_url" placeholder="Paste recording link..." value="<?= htmlspecialchars($iv['recording_url'] ?? '') ?>">
                <button type="submit" name="save_minutes" class="btn btn-primary btn-sm">💾 Save</button>
            </form>
        </div>

        <!-- Feedback Form -->
        <div class="fb-box">
            <form method="POST">
                <input type="hidden" name="app_id" value="<?= $iv['app_id'] ?>">
                <label>💬 Student Feedback <?php if ($iv['feedback_at']): ?><span style="font-weight:400;color:#999">(Last: <?= date('d M Y', strtotime($iv['feedback_at'])) ?>)</span><?php endif; ?></label>
                <textarea name="feedback" rows="3" placeholder="Strengths, areas to improve, interview performance..."><?= htmlspecialchars($iv['feedback'] ?? '') ?></textarea>
                <button type="submit" name="save_feedback" class="btn btn-sm" style="background:#e65100;color:#fff;margin-top:6px">💬 Save Feedback</button>
            </form>
        </div>
    </div>
    <?php endwhile; endif; ?>
</div>
<?php require_once '../chatbot/widget.php'; ?>
<script>
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('show');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('sidebarOverlay').classList.remove('show');}
</script>
</body>
</html>

