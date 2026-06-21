<?php
require_once '../../includes/config.php';
requireLogin('student');

$uid = $_SESSION['user_id'];
$id  = (int)($_GET['id'] ?? 0);

$app = $conn->query("SELECT ia.*, i.title as internship_title, i.duration, i.location,
    c.company_name, u.name as student_name, sp.department, sp.roll_number,
    ia.completion_date
    FROM internship_applications ia
    JOIN internships i ON ia.internship_id=i.id
    JOIN companies c ON i.company_id=c.id
    JOIN users u ON ia.student_id=u.id
    LEFT JOIN student_profiles sp ON sp.user_id=u.id
    WHERE ia.id=$id AND ia.student_id=$uid AND ia.certificate_issued=1")->fetch_assoc();

if (!$app) {
    echo '<div style="text-align:center;padding:60px;font-family:sans-serif"><h2>Certificate not available.</h2><a href="index.php">← Back</a></div>';
    exit();
}
$issueDate = $app['completion_date'] ? date('d F Y', strtotime($app['completion_date'])) : date('d F Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Internship Certificate</title>
<style>
body { background:#f0f2f5;font-family:'Georgia',serif;display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0; }
.cert-wrap { background:#fff;width:850px;padding:60px;border:12px solid #4a148c;border-radius:4px;position:relative;box-shadow:0 10px 40px rgba(0,0,0,0.2); }
.cert-wrap::before { content:'';position:absolute;inset:8px;border:3px solid #ffd54f;pointer-events:none; }
.cert-header { text-align:center;margin-bottom:30px; }
.cert-header h1 { font-size:2.8rem;color:#4a148c;letter-spacing:4px;margin-bottom:5px; }
.cert-header p { color:#888;font-size:0.95rem;letter-spacing:2px; }
.cert-body { text-align:center;margin:30px 0; }
.cert-body .label { font-size:1rem;color:#666;margin-bottom:8px; }
.cert-body .name { font-size:2.2rem;color:#1a237e;font-weight:700;border-bottom:2px solid #ffd54f;display:inline-block;padding-bottom:5px;margin-bottom:20px; }
.cert-body p { font-size:1.05rem;color:#444;line-height:1.9;max-width:600px;margin:0 auto; }
.cert-footer { display:flex;justify-content:space-between;margin-top:50px;align-items:flex-end; }
.cert-footer .sign { text-align:center; }
.cert-footer .sign .line { width:160px;border-top:2px solid #333;margin:0 auto 6px; }
.cert-footer .sign p { font-size:0.85rem;color:#555; }
.cert-id { text-align:center;margin-top:20px;font-size:0.78rem;color:#aaa; }
.print-btn { position:fixed;top:20px;right:20px;background:#4a148c;color:#fff;border:none;padding:10px 22px;border-radius:8px;cursor:pointer;font-size:0.95rem;font-weight:600; }
@media print { .print-btn { display:none; } body { background:#fff; } }
</style>
</head>
<body>
<button class="print-btn" onclick="window.print()">🖨️ Print / Save PDF</button>
<div class="cert-wrap">
    <div class="cert-header">
        <h1>🎓 CampusRecruit</h1>
        <p>CAMPUS PLACEMENT & RECRUITMENT SYSTEM</p>
        <div style="font-size:1.5rem;font-weight:700;color:#7b1fa2;margin-top:15px;letter-spacing:3px">INTERNSHIP COMPLETION CERTIFICATE</div>
    </div>

    <div class="cert-body">
        <p class="label">This is to certify that</p>
        <div class="name"><?= htmlspecialchars($app['student_name']) ?></div>
        <?php if ($app['department']): ?>
        <p style="color:#666;font-size:0.9rem;margin-bottom:15px"><?= htmlspecialchars($app['department']) ?><?= $app['roll_number'] ? ' · Roll No: '.$app['roll_number'] : '' ?></p>
        <?php endif; ?>
        <p>
            has successfully completed the internship program as<br>
            <strong style="color:#4a148c;font-size:1.1rem"><?= htmlspecialchars($app['internship_title']) ?></strong><br>
            at <strong><?= htmlspecialchars($app['company_name']) ?></strong>
            <?php if ($app['duration']): ?>for a duration of <strong><?= htmlspecialchars($app['duration']) ?></strong><?php endif; ?>
            <?php if ($app['location']): ?>at <strong><?= htmlspecialchars($app['location']) ?></strong><?php endif; ?>.
        </p>
        <p style="margin-top:15px">
            The intern demonstrated excellent dedication, professionalism, and technical skills throughout the internship period.
        </p>
    </div>

    <div style="text-align:center;margin-top:20px">
        <span style="background:#f3e5f5;color:#4a148c;padding:6px 20px;border-radius:20px;font-size:0.9rem">
            📅 Issue Date: <strong><?= $issueDate ?></strong>
        </span>
    </div>

    <div class="cert-footer">
        <div class="sign">
            <div class="line"></div>
            <p><?= htmlspecialchars($app['company_name']) ?></p>
            <p style="font-size:0.78rem;color:#888">Company Representative</p>
        </div>
        <div style="text-align:center">
            <div style="font-size:3rem">🏆</div>
            <div style="font-size:0.78rem;color:#aaa">Certificate of Completion</div>
        </div>
        <div class="sign">
            <div class="line"></div>
            <p>Placement Cell</p>
            <p style="font-size:0.78rem;color:#888">CampusRecruit</p>
        </div>
    </div>

    <div class="cert-id">Certificate ID: CERT-INTERN-<?= str_pad($app['id'], 6, '0', STR_PAD_LEFT) ?> · Issued by CampusRecruit Platform</div>
</div>
</body>
</html>
