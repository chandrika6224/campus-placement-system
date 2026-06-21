<?php
require_once '../../includes/config.php';
requireLogin('student');

$uid = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if ($uid === 0) { header('Location: ../../index.php'); exit(); }

// Ensure tables exist
$conn->query("CREATE TABLE IF NOT EXISTS eligibility_criteria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    min_cgpa DECIMAL(4,2) DEFAULT 6.00,
    min_attendance DECIMAL(5,2) DEFAULT 75.00,
    max_backlogs INT DEFAULT 0
)");
$conn->query("CREATE TABLE IF NOT EXISTS student_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    attendance_pct DECIMAL(5,2) DEFAULT 0,
    backlogs INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");
if ($conn->query("SELECT COUNT(*) as c FROM eligibility_criteria")->fetch_assoc()['c'] == 0) {
    $conn->query("INSERT INTO eligibility_criteria (min_cgpa, min_attendance, max_backlogs) VALUES (6.00, 75.00, 0)");
}

$criteria   = $conn->query("SELECT * FROM eligibility_criteria LIMIT 1")->fetch_assoc();
$stProf = $conn->prepare("SELECT sp.*, u.name FROM student_profiles sp JOIN users u ON sp.user_id=u.id WHERE sp.user_id=?");
$stProf->bind_param('i', $uid); $stProf->execute();
$profile = $stProf->get_result()->fetch_assoc(); $stProf->close();

// Determine placed salary from both sources
$is_placed_profile = ($profile['placement_status'] ?? '') === 'Placed';
$placed_salary     = (float)($profile['placed_salary'] ?? 0);
// Also check application-based placement
if ($conn->query("SHOW TABLES LIKE 'applications'")->num_rows > 0) {
    $selRes = $conn->query("SELECT j.salary_range FROM applications a JOIN jobs j ON a.job_id=j.id WHERE a.student_id=$uid AND a.status='selected'");
    while ($sr = $selRes->fetch_assoc()) {
        preg_match('/([\d.]+)/', $sr['salary_range'] ?? '', $mm);
        $v = isset($mm[1]) ? (float)$mm[1] : 0;
        if ($v > $placed_salary) $placed_salary = $v;
        $is_placed_profile = true;
    }
}
$stAtt = $conn->prepare("SELECT * FROM student_attendance WHERE user_id=?");
$stAtt->bind_param('i', $uid); $stAtt->execute();
$attendance = $stAtt->get_result()->fetch_assoc(); $stAtt->close();

$cgpa       = (float)($profile['cgpa'] ?? 0);
$attPct     = (float)($attendance['attendance_pct'] ?? 0);
$backlogs   = (int)($attendance['backlogs'] ?? 0);

$cgpaOk     = $cgpa >= $criteria['min_cgpa'];
$attOk      = $attPct >= $criteria['min_attendance'];
$backlogOk  = $backlogs <= $criteria['max_backlogs'];
$eligible   = $cgpaOk && $attOk && $backlogOk;

$checks = [
    ['label'=>'CGPA', 'icon'=>'📊', 'value'=>$cgpa ?: 'Not set', 'required'=>'Min '.$criteria['min_cgpa'], 'ok'=>$cgpaOk, 'tip'=>'Update your CGPA in your profile.'],
    ['label'=>'Attendance', 'icon'=>'📅', 'value'=>$attPct.'%', 'required'=>'Min '.$criteria['min_attendance'].'%', 'ok'=>$attOk, 'tip'=>'Contact admin to update your attendance.'],
    ['label'=>'Backlogs', 'icon'=>'📋', 'value'=>$backlogs, 'required'=>'Max '.$criteria['max_backlogs'], 'ok'=>$backlogOk, 'tip'=>'Clear your backlogs to become eligible.'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Placement Eligibility</title>
<link rel="stylesheet" href="../../css/style.css">
</head>
<body>
<nav class="navbar">
    <a href="../dashboard.php" class="brand">🎓 Campus<span>Recruit</span></a>
    <div class="nav-links">
        <a href="../dashboard.php">Dashboard</a>
        <a href="index.php" class="active">✅ Eligibility</a>
        <a href="../profile.php">Profile</a>
        <?php require_once '../../notifications/widget.php'; ?>
        <a href="../logout.php" class="btn-logout">Logout</a>
    </div>
</nav>

<div class="container" style="max-width:700px">
    <!-- Status Banner -->
    <div style="background:<?= $eligible ? 'linear-gradient(135deg,#1b5e20,#2e7d32)' : 'linear-gradient(135deg,#b71c1c,#c62828)' ?>;color:#fff;border-radius:14px;padding:30px;text-align:center;margin-bottom:25px">
        <div style="font-size:3.5rem;margin-bottom:10px"><?= $eligible ? '🎉' : '⚠️' ?></div>
        <h2 style="border:none;padding:0;margin-bottom:8px;font-size:1.6rem;color:#fff">
            <?= $eligible ? 'You are Eligible for Placements!' : 'Not Yet Eligible' ?>
        </h2>
        <p style="color:rgba(255,255,255,0.85);font-size:0.95rem">
            <?= $eligible ? 'Congratulations! You meet all placement eligibility criteria.' : 'You do not meet one or more eligibility criteria. See details below.' ?>
        </p>
    </div>

    <?php if ($is_placed_profile): ?>
    <div style="background:linear-gradient(135deg,#1b5e20,#2e7d32);color:#fff;border-radius:14px;padding:22px 24px;margin-bottom:20px">
        <div style="font-size:1.4rem;font-weight:800;margin-bottom:6px">🎓 Already Placed</div>
        <?php if ($placed_salary > 0): ?>
        <p style="margin:0;color:rgba(255,255,255,0.9);font-size:0.95rem">
            Your current package is <strong><?= $placed_salary ?> LPA</strong>.<br>
            You are <strong>not eligible</strong> for jobs below <strong><?= $placed_salary ?> LPA</strong>.<br>
            You can only apply to jobs offering <strong><?= $placed_salary * 2 ?> LPA or more</strong> (double your package).
        </p>
        <?php else: ?>
        <p style="margin:0;color:rgba(255,255,255,0.9);font-size:0.95rem">
            You are marked as placed. Contact admin to set your placed salary so the double-package rule can be applied.
        </p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Criteria Checks -->
    <div class="card">
        <h2>📋 Eligibility Criteria Check</h2>
        <?php foreach ($checks as $c): ?>
        <div style="display:flex;align-items:center;gap:15px;padding:16px;border-radius:10px;margin-bottom:10px;background:<?= $c['ok'] ? '#e8f5e9' : '#ffebee' ?>;border-left:5px solid <?= $c['ok'] ? '#43a047' : '#e53935' ?>">
            <div style="font-size:1.8rem"><?= $c['icon'] ?></div>
            <div style="flex:1">
                <div style="font-weight:700;color:#1a237e;font-size:0.95rem"><?= $c['label'] ?></div>
                <div style="font-size:0.85rem;color:#555;margin-top:2px">
                    Your value: <strong><?= $c['value'] ?></strong> &nbsp;·&nbsp; Required: <strong><?= $c['required'] ?></strong>
                </div>
                <?php if (!$c['ok']): ?>
                <div style="font-size:0.8rem;color:#c62828;margin-top:4px">💡 <?= $c['tip'] ?></div>
                <?php endif; ?>
            </div>
            <div style="font-size:1.5rem"><?= $c['ok'] ? '✅' : '❌' ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- What to do -->
    <?php if (!$eligible): ?>
    <div class="card">
        <h2>🚀 How to Become Eligible</h2>
        <ul style="padding-left:20px;line-height:2.2;color:#555;font-size:0.92rem">
            <?php if (!$cgpaOk): ?><li>Improve your CGPA to at least <strong><?= $criteria['min_cgpa'] ?></strong> — update it in your <a href="../profile.php">profile</a>.</li><?php endif; ?>
            <?php if (!$attOk): ?><li>Maintain attendance above <strong><?= $criteria['min_attendance'] ?>%</strong> — contact your admin to update records.</li><?php endif; ?>
            <?php if (!$backlogOk): ?><li>Clear all backlogs — you currently have <strong><?= $backlogs ?></strong> backlog(s).</li><?php endif; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div style="text-align:center;margin-top:10px">
        <a href="../profile.php" class="btn btn-primary">✏️ Update Profile</a>
        <a href="../jobs.php" class="btn btn-success" style="margin-left:10px">💼 Browse Jobs</a>
    </div>
</div>
<?php require_once '../../chatbot/widget.php'; ?>
</body>
</html>
