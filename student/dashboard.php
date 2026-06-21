<?php
require_once '../includes/config.php';

try {
    requireLogin('student');

    $uid = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
    if ($uid === 0) {
        throw new RuntimeException('Unauthenticated access.');
    }

$stmtP = $conn->prepare("SELECT sp.*, u.name, u.email FROM student_profiles sp JOIN users u ON sp.user_id=u.id WHERE sp.user_id=? AND u.role='student'");
$stmtP->bind_param('i', $uid); $stmtP->execute();
$profile = $stmtP->get_result()->fetch_assoc(); $stmtP->close();
// If profile row doesn't exist yet, create it and set defaults
if (!$profile) {
    $stmtIns = $conn->prepare("INSERT IGNORE INTO student_profiles (user_id) VALUES (?)");
    $stmtIns->bind_param('i', $uid); $stmtIns->execute(); $stmtIns->close();
    $safeName  = htmlspecialchars(strip_tags((string)($_SESSION['name']  ?? '')));
    $safeEmail = htmlspecialchars(strip_tags((string)($_SESSION['email'] ?? '')));
    $profile = ['name'=>$safeName,'email'=>$safeEmail,'department'=>'','skills'=>'','cgpa'=>0,'phone'=>'','resume_path'=>'','roll_number'=>'','address'=>'','year_of_passing'=>''];
}

function stmtCount($st) {
    $st->execute();
    $count = (int)($st->get_result()->fetch_assoc()['c'] ?? 0);
    $st->close();
    return $count;
}

$stApplied    = $conn->prepare("SELECT COUNT(*) as c FROM applications WHERE student_id=?"); $stApplied->bind_param('i', $uid);
$stShort      = $conn->prepare("SELECT COUNT(*) as c FROM applications WHERE student_id=? AND status='shortlisted'"); $stShort->bind_param('i', $uid);
$stSelected   = $conn->prepare("SELECT COUNT(*) as c FROM applications WHERE student_id=? AND status='selected'"); $stSelected->bind_param('i', $uid);

// Count overall placed students — guard against missing placement_status column
$total_placed_count = 0;
if ($conn->query("SHOW COLUMNS FROM student_profiles LIKE 'placement_status'")->num_rows > 0) {
    $total_placed_count = (int)$conn->query("SELECT COUNT(*) as c FROM student_profiles WHERE placement_status='Placed'")->fetch_assoc()['c'];
}
$stIvAtt = $conn->prepare("SELECT COUNT(*) as c FROM interviews WHERE student_id=? AND status='completed'"); $stIvAtt->bind_param('i', $uid);

$popupIvUp = $conn->prepare("SELECT j.title, c.company_name, i.scheduled_at FROM interviews i JOIN jobs j ON i.job_id=j.id JOIN companies c ON j.company_id=c.id WHERE i.student_id=? AND i.status='scheduled' AND i.scheduled_at > NOW() ORDER BY i.scheduled_at ASC"); $popupIvUp->bind_param('i',$uid); $popupIvUp->execute(); $popupIvUp=$popupIvUp->get_result()->fetch_all(MYSQLI_ASSOC);

$stats = [
    'applied'     => stmtCount($stApplied),
    'shortlisted' => stmtCount($stShort),
    'selected'    => stmtCount($stSelected),
    'open_jobs'   => (int)($conn->query("SELECT COUNT(*) as c FROM jobs WHERE status='open'")->fetch_assoc()['c'] ?? 0),
    'iv_attended' => stmtCount($stIvAtt),
    'iv_upcoming' => count($popupIvUp),
];

$stmtRA = $conn->prepare("SELECT a.*, j.title, c.company_name FROM applications a JOIN jobs j ON a.job_id=j.id JOIN companies c ON j.company_id=c.id WHERE a.student_id=? ORDER BY a.applied_at DESC LIMIT 5");
$stmtRA->bind_param('i', $uid); $stmtRA->execute();
$recent_apps = $stmtRA->get_result(); $stmtRA->close();

// Stat card popup data
$popupOpenJobs  = $conn->query("SELECT j.title, c.company_name, j.location, j.job_type, j.salary_range, j.deadline FROM jobs j JOIN companies c ON j.company_id=c.id WHERE j.status='open' ORDER BY j.created_at DESC");
$popupApplied   = $conn->prepare("SELECT j.title, c.company_name, a.status, a.applied_at FROM applications a JOIN jobs j ON a.job_id=j.id JOIN companies c ON j.company_id=c.id WHERE a.student_id=? ORDER BY a.applied_at DESC"); $popupApplied->bind_param('i',$uid); $popupApplied->execute(); $popupApplied=$popupApplied->get_result();
$popupShort     = $conn->prepare("SELECT j.title, c.company_name, a.applied_at FROM applications a JOIN jobs j ON a.job_id=j.id JOIN companies c ON j.company_id=c.id WHERE a.student_id=? AND a.status='shortlisted' ORDER BY a.applied_at DESC"); $popupShort->bind_param('i',$uid); $popupShort->execute(); $popupShort=$popupShort->get_result();
$popupSelected  = $conn->prepare("SELECT j.title, c.company_name, a.applied_at FROM applications a JOIN jobs j ON a.job_id=j.id JOIN companies c ON j.company_id=c.id WHERE a.student_id=? AND a.status='selected' ORDER BY a.applied_at DESC"); $popupSelected->bind_param('i',$uid); $popupSelected->execute(); $popupSelected=$popupSelected->get_result();
$popupIvAtt     = $conn->prepare("SELECT j.title, c.company_name, i.scheduled_at FROM interviews i JOIN jobs j ON i.job_id=j.id JOIN companies c ON j.company_id=c.id WHERE i.student_id=? AND i.status='completed' ORDER BY i.scheduled_at DESC"); $popupIvAtt->bind_param('i',$uid); $popupIvAtt->execute(); $popupIvAtt=$popupIvAtt->get_result();

// Guard placed_salary and placement_status columns
$_placedCols = array_column($conn->query("SHOW COLUMNS FROM student_profiles")->fetch_all(MYSQLI_ASSOC), 'Field');
$_salaryCol  = in_array('placed_salary',   $_placedCols) ? 'sp.placed_salary'   : 'NULL as placed_salary';
$_statusCol  = in_array('placement_status', $_placedCols);
$popupPlaced = $_statusCol
    ? $conn->query("SELECT u.name, sp.department, sp.cgpa, {$_salaryCol} FROM student_profiles sp JOIN users u ON sp.user_id=u.id WHERE sp.placement_status='Placed' ORDER BY u.name ASC")
    : $conn->query("SELECT u.name, sp.department, sp.cgpa, NULL as placed_salary FROM student_profiles sp JOIN users u ON sp.user_id=u.id ORDER BY u.name ASC LIMIT 0");

$stmtNot = $conn->prepare("SELECT id, title, created_at FROM notices ORDER BY created_at DESC LIMIT 5");
$stmtNot->execute();
$notices = $stmtNot->get_result(); $stmtNot->close();



$lastScore = null;
if ($conn->query("SHOW TABLES LIKE 'resume_analysis'")->num_rows > 0) {
    $stmtLS = $conn->prepare("SELECT score FROM resume_analysis WHERE user_id=? ORDER BY analyzed_at DESC LIMIT 1");
    $stmtLS->bind_param('i', $uid); $stmtLS->execute();
    $lastScore = $stmtLS->get_result()->fetch_assoc(); $stmtLS->close();
}

$testCount  = 0;
$myAttempts = 0;
if ($conn->query("SHOW TABLES LIKE 'tests'")->num_rows > 0) {
    $stTests = $conn->prepare("SELECT COUNT(*) as c FROM tests WHERE status='active'");
    $stTAtt  = $conn->prepare("SELECT COUNT(*) as c FROM test_attempts WHERE student_id=? AND status='completed'"); $stTAtt->bind_param('i', $uid);
    $testCount  = stmtCount($stTests);
    $myAttempts = stmtCount($stTAtt);
}

$codingSolved = 0;
$codingPoints = 0;
if ($conn->query("SHOW TABLES LIKE 'coding_submissions'")->num_rows > 0) {
    $stCSolv = $conn->prepare("SELECT COUNT(DISTINCT problem_id) as c FROM coding_submissions WHERE user_id=? AND status='accepted'"); $stCSolv->bind_param('i', $uid);
    $codingSolved = stmtCount($stCSolv);
    $stCP = $conn->prepare("SELECT COALESCE(SUM(points_earned),0) as p FROM coding_submissions WHERE user_id=? AND status='accepted'");
    $stCP->bind_param('i', $uid); $stCP->execute();
    $codingPoints = (int)($stCP->get_result()->fetch_assoc()['p'] ?? 0); $stCP->close();
}

$openInterns = 0;
$myInterns   = 0;
if ($conn->query("SHOW TABLES LIKE 'internships'")->num_rows > 0) {
    $stOI = $conn->prepare("SELECT COUNT(*) as c FROM internships WHERE status='open'");
    $stMI = $conn->prepare("SELECT COUNT(*) as c FROM internship_applications WHERE student_id=?"); $stMI->bind_param('i', $uid);
    $openInterns = stmtCount($stOI);
    $myInterns   = stmtCount($stMI);
}

$_gradeMap = [
    'A' => ['#e8f5e9', '#2e7d32', 'Excellent'],
    'B' => ['#e3f2fd', '#1565c0', 'Good'],
    'C' => ['#fff8e1', '#e65100', 'Average'],
    'D' => ['#ffebee', '#c62828', 'Needs Improvement'],
];
$predResult = ['probability' => 0, 'grade' => 'D', 'gradeBg' => '#ffebee', 'gradeColor' => '#c62828', 'gradeLabel' => 'Needs Improvement'];
if (file_exists(__DIR__ . '/placement_prediction/predictor.php')) {
    require_once 'placement_prediction/predictor.php';
    $predData   = PlacementPredictor::getStudentData($conn, $uid);
    $predResult = PlacementPredictor::predict($predData);
    $predResult['gradeBg']    = $_gradeMap[$predResult['grade']][0] ?? '#f5f5f5';
    $predResult['gradeColor'] = $_gradeMap[$predResult['grade']][1] ?? '#555';
    $predResult['gradeLabel'] = $_gradeMap[$predResult['grade']][2] ?? 'Unknown';
}

$recCount = 0;
if ($profile['skills']) {
    $studentSkills = array_map('trim', array_map('strtolower', explode(',', $profile['skills'])));
    $allOpenJobs   = $conn->query("SELECT j.*, c.company_name FROM jobs j JOIN companies c ON j.company_id=c.id WHERE j.status='open'");
    while ($oj = $allOpenJobs->fetch_assoc()) {
        $fullText = strtolower($oj['title'].' '.($oj['requirements'] ?? '').' '.($oj['description'] ?? ''));
        foreach ($studentSkills as $sk) {
            if (!empty($sk) && strpos($fullText, $sk) !== false) { $recCount++; break; }
        }
    }
}

$bestGapPct = 0;
$studentSkillsArr = array_filter(array_map('strtolower', array_map('trim', explode(',', $profile['skills']))));
foreach ([['php','python','java','javascript','c++','git','mysql'],['html','css','javascript','php','react','mysql','git']] as $sgReq) {
    $p = count($sgReq) > 0 ? round(count(array_intersect($studentSkillsArr, $sgReq)) / count($sgReq) * 100) : 0;
    if ($p > $bestGapPct) $bestGapPct = $p;
}

$currentPage = 'dashboard';
} catch (RuntimeException $e) {
    header('Location: ../index.php');
    return;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Dashboard - CampusRecruit</title>
<link rel="stylesheet" href="../css/style.css">
<style>
.stats-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:16px; margin-bottom:24px; }
.stat-card { background:#fff; border-radius:12px; padding:20px; box-shadow:0 2px 10px rgba(0,0,0,0.07); text-align:center; border-top:4px solid #3f51b5; }
.stat-card .number { font-size:2rem; font-weight:800; color:#1a237e; }
.stat-card .label { color:#666; font-size:0.85rem; margin-top:5px; }
.stat-card.orange { border-top-color:#fb8c00; }
.stat-card.green  { border-top-color:#43a047; }
.stat-card.purple { border-top-color:#7b1fa2; }
.feature-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:14px; margin-bottom:24px; }
.feature-card { border-radius:12px; padding:16px 18px; display:flex; justify-content:space-between; align-items:center; gap:10px; transition:transform 0.2s, box-shadow 0.2s; }
.feature-card:hover { transform:translateY(-3px); box-shadow:0 6px 20px rgba(0,0,0,0.15); }
.feature-card .fc-info h3 { font-size:0.92rem; font-weight:700; margin-bottom:4px; }
.feature-card .fc-info p  { font-size:0.78rem; opacity:0.85; margin:0; }
.feature-card .fc-info small { font-size:0.75rem; opacity:0.9; display:block; margin-top:3px; }
.feature-card .fc-btn { padding:6px 14px; border-radius:20px; background:#ffd54f; color:#1a237e; font-weight:700; font-size:0.8rem; text-decoration:none; white-space:nowrap; flex-shrink:0; }
.bottom-grid { display:grid; grid-template-columns:2fr 1fr; gap:20px; }
@media(max-width:900px) { .stats-grid { grid-template-columns:repeat(4,1fr); } .bottom-grid { grid-template-columns:1fr; } }
@media(max-width:500px) { .stats-grid { grid-template-columns:repeat(2,1fr); } .feature-grid { grid-template-columns:1fr; } }
</style>
</head>
<body>
<?php require_once 'sidebar.php'; ?>
<div class="topbar">
    <div class="topbar-left">
        <button class="hamburger" onclick="toggleSidebar()">☰</button>
        <span class="page-title">Dashboard</span>
    </div>
    <div class="topbar-right">
        <span style="font-size:0.85rem;color:#666">Welcome, <strong style="color:#1a237e"><?= htmlspecialchars($_SESSION['name']) ?></strong>!</span>
        <?php require_once '../notifications/widget.php'; ?>
    </div>
</div>
<main class="main-content">

    <?php
    $missing = [];
    if (empty($profile['department']))  $missing[] = 'Department/Stream';
    if (empty($profile['cgpa']))        $missing[] = 'CGPA';
    if (empty($profile['skills']))      $missing[] = 'Skills';
    if (empty($profile['phone']))       $missing[] = 'Phone';
    ?>
    <?php if (($profile['placement_status'] ?? '') === 'Placed'): ?>
    <div class="alert" style="background:#e8f5e9;border-left:4px solid #43a047;color:#1b5e20;margin-bottom:20px;display:flex;align-items:center;gap:12px;padding:14px 18px;border-radius:8px;">
        <span style="font-size:1.4rem">🎓</span>
        <strong>Congratulations! You are marked as <span style="color:#2e7d32">Placed</span> in the system.</strong>
    </div>
    <?php endif; ?>
    <div class="alert alert-info" style="margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
        <div>
        <?php if (!empty($missing)): ?>
            ⚠️ Your profile is incomplete. Missing: <strong><?= implode(', ', $missing) ?></strong>.
            <a href="profile.php" style="font-weight:700;margin-left:8px">✏️ Edit Profile →</a>
        <?php else: ?>
            ✅ Your profile is complete!
        <?php endif; ?>
        </div>
        <button onclick="toggleSettings()" title="Security & Settings" style="background:#1565c0;border:none;color:#fff;padding:6px 14px;border-radius:8px;cursor:pointer;font-size:0.88rem;font-weight:700;display:flex;align-items:center;gap:6px;flex-shrink:0;white-space:nowrap">
            ⚙️ Settings
        </button>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card" style="cursor:pointer" onclick="openStatModal('open_jobs')">
            <div class="number"><?= $stats['open_jobs'] ?></div>
            <div class="label">💼 Open Jobs</div>
        </div>
        <div class="stat-card orange" style="cursor:pointer" onclick="openStatModal('applied')">
            <div class="number"><?= $stats['applied'] ?></div>
            <div class="label">📋 Applied</div>
        </div>
        <div class="stat-card purple" style="cursor:pointer" onclick="openStatModal('shortlisted')">
            <div class="number"><?= $stats['shortlisted'] ?></div>
            <div class="label">⭐ Shortlisted</div>
        </div>
        <div class="stat-card green" style="cursor:pointer" onclick="openStatModal('selected')">
            <div class="number"><?= $stats['selected'] ?></div>
            <div class="label">✅ Selected</div>
        </div>
        <div class="stat-card" style="border-top-color:#0d47a1;cursor:pointer" onclick="openStatModal('iv_attended')">
            <div class="number"><?= $stats['iv_attended'] ?></div>
            <div class="label">🎥 Interviews Attended</div>
        </div>
        <div class="stat-card" style="border-top-color:#00897b;cursor:pointer" onclick="openStatModal('iv_upcoming')">
            <div class="number"><?= $stats['iv_upcoming'] ?></div>
            <div class="label">📅 Upcoming Interviews</div>
        </div>
        <div class="stat-card green" style="border-top-color:#2e7d32;cursor:pointer" onclick="openStatModal('placed')">
            <div class="number"><?= $total_placed_count ?></div>
            <div class="label">🎓 Total Placed</div>
        </div>
    </div>

    <!-- Feature Cards -->
    <div class="feature-grid">
        <div class="feature-card" style="background:linear-gradient(135deg,#1a237e,#3949ab);color:#fff;cursor:pointer" onclick="openFeatureModal('resume')">
            <div class="fc-info">
                <h3>🤖 AI Resume</h3>
                <p>Score & improve your resume</p>
                <?php if ($lastScore): ?><small>Last: <?= $lastScore['score'] ?>/100</small><?php endif; ?>
            </div>
            <span class="fc-btn">Analyze</span>
        </div>

        <div class="feature-card" style="background:linear-gradient(135deg,#37474f,#546e7a);color:#fff;cursor:pointer" onclick="openFeatureModal('performance')">
            <div class="fc-info">
                <h3>📊 Performance</h3>
                <p>Charts & analytics</p>
                <small><?= $stats['applied'] ?> apps · <?= $stats['selected'] ?> selected</small>
            </div>
            <span class="fc-btn">View</span>
        </div>

        <div class="feature-card" style="background:linear-gradient(135deg,#880e4f,#c2185b);color:#fff;cursor:pointer" onclick="openFeatureModal('interviews')">
            <div class="fc-info">
                <h3>💬 Interview Experiences</h3>
                <p>Read & share interview stories</p>
            </div>
            <span class="fc-btn">View</span>
        </div>

        <div class="feature-card" style="background:linear-gradient(135deg,#1b5e20,#388e3c);color:#fff;cursor:pointer" onclick="openFeatureModal('jobmatch')">
            <div class="fc-info">
                <h3>🎯 AI Job Match</h3>
                <p>Personalized job recommendations</p>
                <small><?= $recCount ?> jobs matched</small>
            </div>
            <span class="fc-btn">View</span>
        </div>

        <div class="feature-card" style="background:linear-gradient(135deg,#e65100,#f57c00);color:#fff;cursor:pointer" onclick="openFeatureModal('prediction')">
            <div class="fc-info">
                <h3>🔮 Prediction</h3>
                <p>AI placement probability</p>
                <small><?= $predResult['probability'] ?>% — Grade <?= $predResult['grade'] ?></small>
            </div>
            <span class="fc-btn">View</span>
        </div>

        <div class="feature-card" style="background:linear-gradient(135deg,#4a148c,#7b1fa2);color:#fff;cursor:pointer" onclick="openFeatureModal('coding')">
            <div class="fc-info">
                <h3>💻 Coding</h3>
                <p>Practice problems & leaderboard</p>
                <small><?= $codingSolved ?> solved · <?= $codingPoints ?> pts</small>
            </div>
            <span class="fc-btn">Practice</span>
        </div>
    </div>

    <!-- Bottom: Recent Apps + Notices -->
    <div class="bottom-grid">
        <div class="card">
            <h2>My Recent Applications
                <a href="applications.php" style="font-size:0.82rem;color:#3f51b5;float:right;font-weight:600">View All →</a>
            </h2>
            <?php if ($recent_apps->num_rows === 0): ?>
            <p style="color:#999;text-align:center;padding:20px">No applications yet. <a href="jobs.php">Browse jobs</a></p>
            <?php else: ?>
            <div class="table-wrap">
                <table>
                    <tr><th>Job Title</th><th>Company</th><th>Status</th><th>Applied</th></tr>
                    <?php while($a = $recent_apps->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($a['title']) ?></td>
                        <td><?= htmlspecialchars($a['company_name']) ?></td>
                        <td><span class="badge badge-<?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span></td>
                        <td><?= date('d M Y', strtotime($a['applied_at'])) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>📢 Notices</h2>
            <?php
            $noticeCount = 0;
            while($n = $notices->fetch_assoc()):
                $noticeCount++;
            ?>
            <div class="notice-item">
                <h4><?= htmlspecialchars($n['title']) ?></h4>
                <div class="date"><?= date('d M Y', strtotime($n['created_at'])) ?></div>
            </div>
            <?php endwhile; ?>
            <?php if ($noticeCount === 0): ?>
            <p style="color:#999;font-size:0.88rem;text-align:center;padding:15px">No notices yet.</p>
            <?php endif; ?>
            <a href="notices.php" style="font-size:0.85rem;color:#3f51b5;font-weight:600">View all notices →</a>
        </div>
    </div>

</main>
</div><!-- app-layout -->
<?php require_once '../chatbot/widget.php'; ?>

<!-- Stat Card Modal -->
<div id="statModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;padding:16px" onclick="if(event.target===this)closeStatModal()">
    <div style="background:#fff;border-radius:14px;width:100%;max-width:750px;max-height:88vh;overflow-y:auto;box-shadow:0 8px 40px rgba(0,0,0,0.2)">
        <div style="padding:16px 22px;border-bottom:1px solid #e8eaf6;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;background:#fff;z-index:1">
            <strong id="stat-modal-title" style="color:#1a237e;font-size:1.05rem"></strong>
            <button onclick="closeStatModal()" style="background:none;border:none;font-size:1.4rem;cursor:pointer;color:#666">&times;</button>
        </div>
        <div style="padding:16px 22px">

            <!-- Open Jobs -->
            <div id="sm-open_jobs" class="sm-sec" style="display:none">
                <div class="table-wrap"><table>
                    <tr><th>#</th><th>Title</th><th>Company</th><th>Location</th><th>Type</th><th>Salary</th><th>Deadline</th></tr>
                    <?php $i=1; while($r=$popupOpenJobs->fetch_assoc()): ?>
                    <tr><td><?= $i++ ?></td><td style="font-weight:600;color:#1a237e"><?= htmlspecialchars($r['title']) ?></td><td><?= htmlspecialchars($r['company_name']) ?></td><td><?= htmlspecialchars($r['location']??'-') ?></td><td><?= htmlspecialchars($r['job_type']??'-') ?></td><td><?= htmlspecialchars($r['salary_range']??'-') ?></td><td><?= $r['deadline']?date('d M Y',strtotime($r['deadline'])):'Open' ?></td></tr>
                    <?php endwhile; ?>
                </table></div>
            </div>

            <!-- Applied -->
            <div id="sm-applied" class="sm-sec" style="display:none">
                <div class="table-wrap"><table>
                    <tr><th>#</th><th>Job Title</th><th>Company</th><th>Status</th><th>Applied On</th></tr>
                    <?php $i=1; while($r=$popupApplied->fetch_assoc()): ?>
                    <tr><td><?= $i++ ?></td><td style="font-weight:600;color:#1a237e"><?= htmlspecialchars($r['title']) ?></td><td><?= htmlspecialchars($r['company_name']) ?></td><td><span class="badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td><td><?= date('d M Y',strtotime($r['applied_at'])) ?></td></tr>
                    <?php endwhile; ?>
                </table></div>
            </div>

            <!-- Shortlisted -->
            <div id="sm-shortlisted" class="sm-sec" style="display:none">
                <div class="table-wrap"><table>
                    <tr><th>#</th><th>Job Title</th><th>Company</th><th>Applied On</th></tr>
                    <?php $i=1; while($r=$popupShort->fetch_assoc()): ?>
                    <tr><td><?= $i++ ?></td><td style="font-weight:600;color:#1a237e"><?= htmlspecialchars($r['title']) ?></td><td><?= htmlspecialchars($r['company_name']) ?></td><td><?= date('d M Y',strtotime($r['applied_at'])) ?></td></tr>
                    <?php endwhile; ?>
                </table></div>
            </div>

            <!-- Selected -->
            <div id="sm-selected" class="sm-sec" style="display:none">
                <div class="table-wrap"><table>
                    <tr><th>#</th><th>Job Title</th><th>Company</th><th>Date</th></tr>
                    <?php $i=1; while($r=$popupSelected->fetch_assoc()): ?>
                    <tr><td><?= $i++ ?></td><td style="font-weight:600;color:#2e7d32"><?= htmlspecialchars($r['title']) ?></td><td><?= htmlspecialchars($r['company_name']) ?></td><td><?= date('d M Y',strtotime($r['applied_at'])) ?></td></tr>
                    <?php endwhile; ?>
                </table></div>
            </div>

            <!-- Interviews Attended -->
            <div id="sm-iv_attended" class="sm-sec" style="display:none">
                <div class="table-wrap"><table>
                    <tr><th>#</th><th>Job Title</th><th>Company</th><th>Date</th></tr>
                    <?php $i=1; while($r=$popupIvAtt->fetch_assoc()): ?>
                    <tr><td><?= $i++ ?></td><td style="font-weight:600;color:#1a237e"><?= htmlspecialchars($r['title']) ?></td><td><?= htmlspecialchars($r['company_name']) ?></td><td><?= $r['scheduled_at']?date('d M Y',strtotime($r['scheduled_at'])):'-' ?></td></tr>
                    <?php endwhile; ?>
                </table></div>
            </div>

            <!-- Upcoming Interviews -->
            <div id="sm-iv_upcoming" class="sm-sec" style="display:none">
                <div class="table-wrap"><table>
                    <tr><th>#</th><th>Job Title</th><th>Company</th><th>Scheduled At</th></tr>
                    <?php $i=1; foreach($popupIvUp as $r): ?>
                    <tr><td><?= $i++ ?></td><td style="font-weight:600;color:#1a237e"><?= htmlspecialchars($r['title']) ?></td><td><?= htmlspecialchars($r['company_name']) ?></td><td style="color:#e65100;font-weight:700"><?= $r['scheduled_at']?date('d M Y, h:i A',strtotime($r['scheduled_at'])):'-' ?></td></tr>
                    <?php endforeach; ?>
                </table></div>
            </div>

            <!-- Total Placed -->
            <div id="sm-placed" class="sm-sec" style="display:none">
                <div class="table-wrap"><table>
                    <tr><th>#</th><th>Name</th><th>Department</th><th>CGPA</th><th>Package</th></tr>
                    <?php $i=1; while($r=$popupPlaced->fetch_assoc()): ?>
                    <tr><td><?= $i++ ?></td><td style="font-weight:600;color:#2e7d32"><?= htmlspecialchars($r['name']) ?></td><td><?= htmlspecialchars($r['department']??'-') ?></td><td><?= $r['cgpa']??'-' ?></td><td><?= $r['placed_salary']?$r['placed_salary'].' LPA':'-' ?></td></tr>
                    <?php endwhile; ?>
                </table></div>
            </div>

        </div>
    </div>
</div>

<!-- Feature Detail Modal -->
<div id="featureModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;padding:16px" onclick="if(event.target===this)closeFeatureModal()">
    <div style="background:#fff;border-radius:14px;width:100%;max-width:600px;max-height:88vh;overflow-y:auto;box-shadow:0 8px 40px rgba(0,0,0,0.2)">
        <div id="fm-header" style="padding:18px 22px;border-radius:14px 14px 0 0;display:flex;justify-content:space-between;align-items:center">
            <strong id="fm-title" style="font-size:1.05rem;color:#fff"></strong>
            <button onclick="closeFeatureModal()" style="background:rgba(255,255,255,0.2);border:none;color:#fff;width:30px;height:30px;border-radius:50%;cursor:pointer;font-size:1.1rem">&times;</button>
        </div>
        <div id="fm-body" style="padding:20px 22px"></div>
        <div style="padding:12px 22px;border-top:1px solid #eee;text-align:right">
            <a id="fm-link" href="#" style="padding:8px 20px;background:#1a237e;color:#fff;border-radius:6px;font-size:0.88rem;font-weight:700;text-decoration:none">Open Full Page →</a>
        </div>
    </div>
</div>

<script>
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('show');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('sidebarOverlay').classList.remove('show');}

const featureConfig = {
    resume:      { title:'🤖 AI Resume Analyzer', color:'linear-gradient(135deg,#1a237e,#3949ab)', link:'resume_analyzer/index.php',
        body:`<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div style="background:#f0f4ff;border-radius:10px;padding:14px;text-align:center">
                <div style="font-size:2rem;font-weight:800;color:#1a237e"><?= $lastScore ? $lastScore['score'] : 'N/A' ?></div>
                <div style="font-size:0.8rem;color:#666;margin-top:4px">Last Resume Score</div>
            </div>
            <div style="background:#f0f4ff;border-radius:10px;padding:14px;text-align:center">
                <div style="font-size:2rem;font-weight:800;color:#1a237e">/100</div>
                <div style="font-size:0.8rem;color:#666;margin-top:4px">Max Score</div>
            </div>
        </div>
        <p style="margin-top:14px;color:#555;font-size:0.88rem">Upload your resume to get an AI-powered score with suggestions to improve it for better visibility to recruiters.</p>` },
    performance: { title:'📊 My Performance', color:'linear-gradient(135deg,#37474f,#546e7a)', link:'performance/index.php',
        body:`<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px">
            <div style="background:#e3f2fd;border-radius:10px;padding:14px;text-align:center">
                <div style="font-size:2rem;font-weight:800;color:#1565c0"><?= $stats['applied'] ?></div>
                <div style="font-size:0.8rem;color:#555;margin-top:4px">Applied</div>
            </div>
            <div style="background:#fff8e1;border-radius:10px;padding:14px;text-align:center">
                <div style="font-size:2rem;font-weight:800;color:#e65100"><?= $stats['shortlisted'] ?></div>
                <div style="font-size:0.8rem;color:#555;margin-top:4px">Shortlisted</div>
            </div>
            <div style="background:#e8f5e9;border-radius:10px;padding:14px;text-align:center">
                <div style="font-size:2rem;font-weight:800;color:#2e7d32"><?= $stats['selected'] ?></div>
                <div style="font-size:0.8rem;color:#555;margin-top:4px">Selected</div>
            </div>
            <div style="background:#f3e5f5;border-radius:10px;padding:14px;text-align:center">
                <div style="font-size:2rem;font-weight:800;color:#7b1fa2"><?= $stats['iv_attended'] ?></div>
                <div style="font-size:0.8rem;color:#555;margin-top:4px">Interviews Attended</div>
            </div>
            <div style="background:#e8f5e9;border-radius:10px;padding:14px;text-align:center">
                <div style="font-size:2rem;font-weight:800;color:#2e7d32"><?= $stats['iv_upcoming'] ?></div>
                <div style="font-size:0.8rem;color:#555;margin-top:4px">Upcoming Interviews</div>
            </div>
            <div style="background:#e3f2fd;border-radius:10px;padding:14px;text-align:center">
                <div style="font-size:2rem;font-weight:800;color:#1565c0"><?= $stats['open_jobs'] ?></div>
                <div style="font-size:0.8rem;color:#555;margin-top:4px">Open Jobs</div>
            </div>
        </div>` },
    interviews:  { title:'💬 Interview Experiences', color:'linear-gradient(135deg,#880e4f,#c2185b)', link:'interviews/experience.php',
        body:`<p style="color:#555;font-size:0.9rem;margin-bottom:14px">Read real interview experiences shared by students and share your own to help others prepare.</p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div style="background:#fce4ec;border-radius:10px;padding:14px;text-align:center">
                <div style="font-size:2rem">📖</div>
                <div style="font-size:0.85rem;font-weight:700;color:#880e4f;margin-top:6px">Read Experiences</div>
            </div>
            <div style="background:#fce4ec;border-radius:10px;padding:14px;text-align:center">
                <div style="font-size:2rem">✍️</div>
                <div style="font-size:0.85rem;font-weight:700;color:#880e4f;margin-top:6px">Share Your Story</div>
            </div>
        </div>` },
    jobmatch:    { title:'🎯 AI Job Match', color:'linear-gradient(135deg,#1b5e20,#388e3c)', link:'job_recommendation/index.php',
        body:`<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px">
            <div style="background:#e8f5e9;border-radius:10px;padding:14px;text-align:center">
                <div style="font-size:2rem;font-weight:800;color:#1b5e20"><?= $recCount ?></div>
                <div style="font-size:0.8rem;color:#555;margin-top:4px">Jobs Matched to Your Skills</div>
            </div>
            <div style="background:#e8f5e9;border-radius:10px;padding:14px;text-align:center">
                <div style="font-size:2rem;font-weight:800;color:#1b5e20"><?= $stats['open_jobs'] ?></div>
                <div style="font-size:0.8rem;color:#555;margin-top:4px">Total Open Jobs</div>
            </div>
        </div>
        <p style="color:#555;font-size:0.88rem">AI matches open jobs to your listed skills. The more skills you add to your profile, the better the recommendations.</p>` },
    prediction:  { title:'🔮 Placement Prediction', color:'linear-gradient(135deg,#e65100,#f57c00)', link:'placement_prediction/index.php',
        body:`<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px">
            <div style="background:#fff3e0;border-radius:10px;padding:14px;text-align:center">
                <div style="font-size:2.5rem;font-weight:900;color:#e65100"><?= $predResult['probability'] ?>%</div>
                <div style="font-size:0.8rem;color:#555;margin-top:4px">Placement Probability</div>
            </div>
            <div style="background:<?= $predResult['gradeBg'] ?>;border-radius:10px;padding:14px;text-align:center">
                <div style="font-size:2.5rem;font-weight:900;color:<?= $predResult['gradeColor'] ?>"><?= $predResult['grade'] ?></div>
                <div style="font-size:0.8rem;color:#555;margin-top:4px"><?= $predResult['gradeLabel'] ?></div>
            </div>
        </div>
        <p style="color:#555;font-size:0.88rem">Based on your CGPA, skills, resume score, test performance, and applications. Improve these to increase your probability.</p>` },
    coding:      { title:'💻 Coding Practice', color:'linear-gradient(135deg,#4a148c,#7b1fa2)', link:'coding/index.php',
        body:`<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px">
            <div style="background:#f3e5f5;border-radius:10px;padding:14px;text-align:center">
                <div style="font-size:2rem;font-weight:800;color:#4a148c"><?= $codingSolved ?></div>
                <div style="font-size:0.8rem;color:#555;margin-top:4px">Problems Solved</div>
            </div>
            <div style="background:#f3e5f5;border-radius:10px;padding:14px;text-align:center">
                <div style="font-size:2rem;font-weight:800;color:#4a148c"><?= $codingPoints ?></div>
                <div style="font-size:0.8rem;color:#555;margin-top:4px">Points Earned</div>
            </div>
        </div>
        <p style="color:#555;font-size:0.88rem">Practice coding problems, climb the leaderboard, and sharpen your skills for technical interviews.</p>` },
};
const statTitles = {
    open_jobs:'💼 Open Jobs', applied:'📋 My Applications', shortlisted:'⭐ Shortlisted Jobs',
    selected:'✅ Selected Jobs', iv_attended:'🎥 Interviews Attended', iv_upcoming:'📅 Upcoming Interviews', placed:'🎓 Placed Students'
};
function openStatModal(key) {
    document.querySelectorAll('.sm-sec').forEach(function(el){ el.style.display='none'; });
    var sec = document.getElementById('sm-'+key);
    if (sec) sec.style.display = 'block';
    document.getElementById('stat-modal-title').textContent = statTitles[key] || '';
    document.getElementById('statModal').style.display = 'flex';
}
function closeStatModal() {
    document.getElementById('statModal').style.display = 'none';
}
function openFeatureModal(key) {
    const f = featureConfig[key]; if (!f) return;
    document.getElementById('fm-header').style.background = f.color;
    document.getElementById('fm-title').textContent = f.title;
    document.getElementById('fm-body').innerHTML = f.body;
    document.getElementById('fm-link').href = f.link;
    document.getElementById('featureModal').style.display = 'flex';
}
function closeFeatureModal() {
    document.getElementById('featureModal').style.display = 'none';
}
</script>
</body>
</html>
