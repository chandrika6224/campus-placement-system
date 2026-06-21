<?php
require_once '../../includes/config.php';
requireLogin('student');

$uid = (int)$_SESSION['user_id'];

$conn->query("ALTER TABLE interviews ADD COLUMN IF NOT EXISTS minutes TEXT DEFAULT NULL");
$conn->query("ALTER TABLE interviews ADD COLUMN IF NOT EXISTS recording_url VARCHAR(500) DEFAULT NULL");
$conn->query("ALTER TABLE applications ADD COLUMN IF NOT EXISTS feedback TEXT DEFAULT NULL");
$conn->query("ALTER TABLE applications ADD COLUMN IF NOT EXISTS feedback_at TIMESTAMP NULL DEFAULT NULL");

$interviews = $conn->query("SELECT i.*, j.title as job_title, c.company_name,
    a.status as app_status, a.feedback, a.feedback_at
    FROM interviews i
    JOIN jobs j ON i.job_id=j.id
    JOIN companies c ON j.company_id=c.id
    JOIN applications a ON i.application_id=a.id
    WHERE i.student_id=$uid
    ORDER BY i.scheduled_at DESC");

$total     = $conn->query("SELECT COUNT(*) as c FROM interviews WHERE student_id=$uid")->fetch_assoc()['c'];
$withMins  = $conn->query("SELECT COUNT(*) as c FROM interviews WHERE student_id=$uid AND minutes IS NOT NULL AND minutes!=''")->fetch_assoc()['c'];
$withRec   = $conn->query("SELECT COUNT(*) as c FROM interviews WHERE student_id=$uid AND recording_url IS NOT NULL AND recording_url!=''")->fetch_assoc()['c'];
$withFb    = $conn->query("SELECT COUNT(*) as c FROM interviews i JOIN applications a ON i.application_id=a.id WHERE i.student_id=$uid AND a.feedback IS NOT NULL AND a.feedback!=''")->fetch_assoc()['c'];

$sc = ['scheduled'=>'#1565c0','completed'=>'#2e7d32','cancelled'=>'#c62828','rescheduled'=>'#e65100'];
$sb = ['scheduled'=>'#e3f2fd','completed'=>'#e8f5e9','cancelled'=>'#ffebee','rescheduled'=>'#fff8e1'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Interview Minutes & Feedback</title>
<link rel="stylesheet" href="../../css/style.css">
<style>
.iv-card{background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 10px rgba(0,0,0,0.07);margin-bottom:16px;border-left:5px solid #3f51b5;transition:transform 0.2s}
.iv-card:hover{transform:translateY(-2px)}
.mins-view{background:#e8f5e9;border-radius:8px;padding:12px 16px;border-left:3px solid #43a047;font-size:0.88rem;color:#1b5e20;line-height:1.7}
.fb-view{background:#fff8e1;border-radius:8px;padding:12px 16px;border-left:3px solid #ffd54f;font-size:0.88rem;color:#e65100;line-height:1.7}
</style>
</head>
<body>
<?php require_once '../sidebar.php'; ?>
<div class="topbar">
    <div class="topbar-left">
        <button class="hamburger" onclick="toggleSidebar()">☰</button>
        <span class="page-title">🎥 My Interview Minutes & Feedback</span>
    </div>
    <div class="topbar-right"><?php require_once '../../notifications/widget.php'; ?></div>
</div>
<div class="main-content">

    <!-- Stats -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px">
        <?php foreach([['🎥 Total Interviews',$total,'#1565c0'],['📝 With Minutes',$withMins,'#2e7d32'],['🎬 With Recordings',$withRec,'#7b1fa2'],['💬 Feedback Received',$withFb,'#e65100']] as [$l,$v,$c]): ?>
        <div style="background:#fff;border-radius:12px;padding:18px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,0.07);border-top:4px solid <?= $c ?>">
            <div style="font-size:1.8rem;font-weight:800;color:<?= $c ?>"><?= $v ?></div>
            <div style="font-size:0.82rem;color:#666;margin-top:4px"><?= $l ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($interviews->num_rows === 0): ?>
    <div style="text-align:center;padding:60px;color:#999;background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.06)">
        <div style="font-size:3.5rem;margin-bottom:12px">🎥</div>
        <p>No interviews yet. Keep applying to jobs!</p>
        <a href="../jobs.php" class="btn btn-primary" style="margin-top:12px">💼 Browse Jobs</a>
    </div>
    <?php else: while($iv = $interviews->fetch_assoc()): ?>
    <div class="iv-card" style="border-left-color:<?= $sc[$iv['status']] ?? '#3f51b5' ?>">
        <!-- Header -->
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px;margin-bottom:14px">
            <div>
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:5px">
                    <strong style="color:#1a237e;font-size:1rem"><?= htmlspecialchars($iv['job_title']) ?></strong>
                    <span style="background:<?= $sb[$iv['status']] ?? '#f5f5f5' ?>;color:<?= $sc[$iv['status']] ?? '#333' ?>;padding:2px 10px;border-radius:12px;font-size:0.75rem;font-weight:700"><?= ucfirst($iv['status']) ?></span>
                    <?php if ($iv['app_status']==='selected'): ?>
                    <span style="background:#e8f5e9;color:#2e7d32;padding:2px 10px;border-radius:12px;font-size:0.75rem;font-weight:700">✅ Selected</span>
                    <?php elseif ($iv['app_status']==='rejected'): ?>
                    <span style="background:#ffebee;color:#c62828;padding:2px 10px;border-radius:12px;font-size:0.75rem;font-weight:700">❌ Rejected</span>
                    <?php endif; ?>
                </div>
                <div style="font-size:0.85rem;color:#555">🏢 <?= htmlspecialchars($iv['company_name']) ?></div>
                <div style="font-size:0.8rem;color:#999;margin-top:3px">
                    📅 <?= date('d M Y, h:i A', strtotime($iv['scheduled_at'])) ?> · ⏱ <?= $iv['duration'] ?> min
                </div>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <?php if ($iv['meeting_link']): ?>
                <a href="<?= htmlspecialchars($iv['meeting_link']) ?>" target="_blank" class="btn btn-primary btn-sm">🔗 Join Meeting</a>
                <?php endif; ?>
                <?php if ($iv['recording_url']): ?>
                <a href="<?= htmlspecialchars($iv['recording_url']) ?>" target="_blank" class="btn btn-sm" style="background:#7b1fa2;color:#fff">🎬 View Recording</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($iv['notes']): ?>
        <div style="background:#f5f5f5;border-radius:8px;padding:10px 14px;margin-bottom:10px;font-size:0.85rem;color:#555">
            📝 <strong>Notes:</strong> <?= nl2br(htmlspecialchars($iv['notes'])) ?>
        </div>
        <?php endif; ?>

        <!-- Minutes -->
        <?php if (!empty($iv['minutes'])): ?>
        <div class="mins-view" style="margin-bottom:10px">
            <strong>📝 Interview Minutes:</strong><br><?= nl2br(htmlspecialchars($iv['minutes'])) ?>
        </div>
        <?php else: ?>
        <div style="background:#f8f9ff;border-radius:8px;padding:10px 14px;margin-bottom:10px;font-size:0.85rem;color:#999">
            📝 Interview minutes not added yet.
        </div>
        <?php endif; ?>

        <!-- Feedback -->
        <?php if (!empty($iv['feedback'])): ?>
        <div class="fb-view">
            <strong>💬 Feedback from Admin:</strong><br><?= nl2br(htmlspecialchars($iv['feedback'])) ?>
            <?php if ($iv['feedback_at']): ?>
            <div style="font-size:0.75rem;color:#bbb;margin-top:6px">Posted: <?= date('d M Y', strtotime($iv['feedback_at'])) ?></div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div style="background:#fff8e1;border-radius:8px;padding:10px 14px;font-size:0.85rem;color:#999">
            💬 No feedback posted yet.
        </div>
        <?php endif; ?>
    </div>
    <?php endwhile; endif; ?>
</div>
<?php require_once '../../chatbot/widget.php'; ?>
<script>
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('show');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('sidebarOverlay').classList.remove('show');}
</script>
</body>
</html>
