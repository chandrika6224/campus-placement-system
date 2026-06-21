<?php
require_once '../../includes/config.php';
requireLogin('student');
require_once '../placement_prediction/predictor.php';

$uid = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if ($uid === 0) { header('Location: ../../index.php'); exit(); }

// ── Profile ───────────────────────────────────────────────────────────────────
$stP = $conn->prepare("SELECT sp.*, u.name, u.email FROM student_profiles sp JOIN users u ON sp.user_id=u.id WHERE sp.user_id=?");
$stP->bind_param('i', $uid); $stP->execute();
$profile = $stP->get_result()->fetch_assoc(); $stP->close();

// ── Application Stats ─────────────────────────────────────────────────────────
$stAS = $conn->prepare("SELECT COUNT(*) as total, SUM(status='applied') as applied, SUM(status='shortlisted') as shortlisted, SUM(status='selected') as selected, SUM(status='rejected') as rejected FROM applications WHERE student_id=?");
$stAS->bind_param('i', $uid); $stAS->execute();
$appStats = $stAS->get_result()->fetch_assoc(); $stAS->close();

// Applications over last 6 months
$appTimeline = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $label = date('M Y', strtotime("-$i months"));
    $stTL = $conn->prepare("SELECT COUNT(*) as c FROM applications WHERE student_id=? AND DATE_FORMAT(applied_at,'%Y-%m')=?");
    $stTL->bind_param('is', $uid, $month); $stTL->execute();
    $count = (int)$stTL->get_result()->fetch_assoc()['c']; $stTL->close();
    $appTimeline[] = ['label' => $label, 'count' => $count];
}

// ── Test Stats ────────────────────────────────────────────────────────────────
$stTA = $conn->prepare("SELECT ta.*, t.title, t.category, ROUND(ta.score/ta.total_marks*100) as pct FROM test_attempts ta JOIN tests t ON ta.test_id=t.id WHERE ta.student_id=? AND ta.status='completed' AND ta.total_marks>0 ORDER BY ta.completed_at ASC");
$stTA->bind_param('i', $uid); $stTA->execute();
$testAttempts = $stTA->get_result(); $stTA->close();

$testLabels = []; $testScores = []; $testCategories = [];
$catScores = ['aptitude'=>[],'technical'=>[],'coding'=>[]];
while ($t = $testAttempts->fetch_assoc()) {
    $testLabels[]  = htmlspecialchars(substr($t['title'], 0, 20));
    $testScores[]  = (int)$t['pct'];
    $testCategories[] = $t['category'];
    $catScores[$t['category']][] = (int)$t['pct'];
}
$avgByCategory = [];
foreach ($catScores as $cat => $scores) {
    $avgByCategory[$cat] = count($scores) > 0 ? round(array_sum($scores)/count($scores)) : 0;
}

// ── Resume Score History ──────────────────────────────────────────────────────
$stRH = $conn->prepare("SELECT score, analyzed_at FROM resume_analysis WHERE user_id=? ORDER BY analyzed_at ASC LIMIT 10");
$stRH->bind_param('i', $uid); $stRH->execute();
$resumeHistory = $stRH->get_result(); $stRH->close();
$resumeLabels = []; $resumeScores = [];
while ($r = $resumeHistory->fetch_assoc()) {
    $resumeLabels[] = date('d M', strtotime($r['analyzed_at']));
    $resumeScores[] = (int)$r['score'];
}
$latestResume = end($resumeScores) ?: 0;

// ── Skills Count ──────────────────────────────────────────────────────────────
$skills = array_filter(array_map('trim', explode(',', $profile['skills'] ?? '')));
$skillCount = count($skills);

// ── Interviews ────────────────────────────────────────────────────────────────
$stIV = $conn->prepare("SELECT COUNT(*) as total, SUM(status='scheduled') as scheduled, SUM(status='completed') as completed, SUM(status='cancelled') as cancelled FROM interviews WHERE student_id=?");
$stIV->bind_param('i', $uid); $stIV->execute();
$ivStats = $stIV->get_result()->fetch_assoc(); $stIV->close();

// ── Placement Prediction ──────────────────────────────────────────────────────
$predData   = PlacementPredictor::getStudentData($conn, $uid);
$predResult = PlacementPredictor::predict($predData);

// ── Application status breakdown for doughnut ────────────────────────────────
$statusData = [
    (int)$appStats['applied'],
    (int)$appStats['shortlisted'],
    (int)$appStats['selected'],
    (int)$appStats['rejected'],
];

// ── Jobs applied by company/industry ─────────────────────────────────────────
$stBI = $conn->prepare("SELECT c.industry, COUNT(*) as cnt FROM applications a JOIN jobs j ON a.job_id=j.id JOIN companies c ON j.company_id=c.id WHERE a.student_id=? AND c.industry IS NOT NULL AND c.industry != '' GROUP BY c.industry ORDER BY cnt DESC LIMIT 6");
$stBI->bind_param('i', $uid); $stBI->execute();
$byIndustry = $stBI->get_result(); $stBI->close();
$industryLabels = []; $industryCounts = [];
while ($row = $byIndustry->fetch_assoc()) {
    $industryLabels[] = $row['industry'];
    $industryCounts[] = (int)$row['cnt'];
}

// ── Radar chart data (overall performance) ───────────────────────────────────
$radarData = [
    min(100, round($profile['cgpa'] / 10 * 100)),
    min(100, round($skillCount / 15 * 100)),
    $latestResume,
    $avgByCategory['aptitude'],
    $avgByCategory['technical'],
    min(100, round(($appStats['shortlisted'] + $appStats['selected']) / max(1, $appStats['total']) * 100)),
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Performance Dashboard</title>
<link rel="stylesheet" href="../../css/style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
.perf-stat { background:#fff;border-radius:12px;padding:18px;box-shadow:0 2px 10px rgba(0,0,0,0.07);text-align:center;border-top:4px solid #3f51b5; }
.perf-stat .num { font-size:2rem;font-weight:800;color:#1a237e; }
.perf-stat .lbl { color:#666;font-size:0.85rem;margin-top:4px; }
.chart-card { background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 10px rgba(0,0,0,0.07);margin-bottom:20px; }
.chart-card h3 { color:#1a237e;font-size:1.05rem;margin-bottom:15px;padding-bottom:8px;border-bottom:2px solid #e8eaf6; }
.progress-row { display:flex;align-items:center;gap:12px;margin-bottom:10px; }
.progress-label { min-width:100px;font-size:0.85rem;font-weight:600;color:#333; }
.progress-bg { flex:1;height:10px;background:#e0e0e0;border-radius:5px; }
.progress-fill { height:10px;border-radius:5px;transition:width 1s ease; }
.achievement-badge { display:inline-flex;align-items:center;gap:8px;padding:8px 16px;border-radius:20px;font-size:0.85rem;font-weight:700;margin:4px; }
</style>
</head>
<body>
<nav class="navbar">
    <a href="../dashboard.php" class="brand">🎓 Campus<span>Recruit</span></a>
    <div class="nav-links">
        <a href="../dashboard.php">Dashboard</a>
        <a href="../jobs.php">Browse Jobs</a>
        <a href="../applications.php">My Applications</a>
        <a href="../profile.php">My Profile</a>
        <a href="../resume_analyzer/index.php">🤖 AI Resume</a>
        <a href="../job_recommendation/index.php">🎯 AI Jobs</a>
        <a href="../aptitude_test/index.php">📝 Tests</a>
        <a href="../interviews/index.php">🎥 Interviews</a>
        <a href="../placement_prediction/index.php">🔮 Prediction</a>
        <a href="../skill_gap/index.php">🧩 Skill Gap</a>
        <a href="index.php" class="active">📊 Performance</a>
        <a href="../notices.php">Notices</a>
        <a href="../logout.php" class="btn-logout">Logout</a>
    </div>
</nav>

<div class="container">

    <!-- Header -->
    <div class="card" style="background:linear-gradient(135deg,#1a237e,#3949ab);color:#fff;margin-bottom:25px">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:15px">
            <div>
                <h2 style="color:#ffd54f;margin-bottom:6px">📊 My Performance Dashboard</h2>
                <p style="color:#c5cae9">Visual analytics of your placement journey — applications, tests, skills, and more.</p>
            </div>
            <div style="text-align:center">
                <div style="font-size:2.5rem;font-weight:800;color:#69f0ae"><?= $predResult['probability'] ?>%</div>
                <div style="font-size:0.82rem;color:#c5cae9">Placement Probability</div>
                <div style="font-size:0.85rem;font-weight:700;color:#ffd54f">Grade <?= $predResult['grade'] ?> — <?= $predResult['gradeLabel'] ?></div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:15px;margin-bottom:25px">
        <div class="perf-stat" style="border-top-color:#3f51b5">
            <div class="num"><?= $appStats['total'] ?></div>
            <div class="lbl">📋 Total Applied</div>
        </div>
        <div class="perf-stat" style="border-top-color:#fb8c00">
            <div class="num"><?= $appStats['shortlisted'] ?></div>
            <div class="lbl">⭐ Shortlisted</div>
        </div>
        <div class="perf-stat" style="border-top-color:#43a047">
            <div class="num"><?= $appStats['selected'] ?></div>
            <div class="lbl">✅ Selected</div>
        </div>
        <div class="perf-stat" style="border-top-color:#9c27b0">
            <div class="num"><?= count($testLabels) ?></div>
            <div class="lbl">📝 Tests Taken</div>
        </div>
        <div class="perf-stat" style="border-top-color:#00897b">
            <div class="num"><?= $latestResume ?>/100</div>
            <div class="lbl">📄 Resume Score</div>
        </div>
        <div class="perf-stat" style="border-top-color:#e53935">
            <div class="num"><?= $skillCount ?></div>
            <div class="lbl">💡 Skills Listed</div>
        </div>
        <div class="perf-stat" style="border-top-color:#1565c0">
            <div class="num"><?= $ivStats['total'] ?></div>
            <div class="lbl">🎥 Interviews</div>
        </div>
        <div class="perf-stat" style="border-top-color:#f57f17">
            <div class="num"><?= $profile['cgpa'] ?: 'N/A' ?></div>
            <div class="lbl">📊 CGPA</div>
        </div>
    </div>

    <!-- Row 1: Application Timeline + Status Doughnut -->
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:20px">
        <div class="chart-card">
            <h3>📈 Application Activity (Last 6 Months)</h3>
            <canvas id="appTimelineChart" height="120"></canvas>
        </div>
        <div class="chart-card">
            <h3>🍩 Application Status</h3>
            <canvas id="statusDoughnut" height="180"></canvas>
            <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:10px;justify-content:center;font-size:0.78rem">
                <?php
                $statusInfo = [
                    ['Applied','#3f51b5',$appStats['applied']],
                    ['Shortlisted','#fb8c00',$appStats['shortlisted']],
                    ['Selected','#43a047',$appStats['selected']],
                    ['Rejected','#e53935',$appStats['rejected']],
                ];
                foreach ($statusInfo as $si): ?>
                <span style="display:flex;align-items:center;gap:4px">
                    <span style="width:10px;height:10px;background:<?= $si[1] ?>;border-radius:2px;display:inline-block"></span>
                    <?= $si[0] ?>: <strong><?= $si[2] ?></strong>
                </span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Row 2: Test Scores + Category Radar -->
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:20px">
        <div class="chart-card">
            <h3>📝 Test Score History</h3>
            <?php if (empty($testLabels)): ?>
            <div style="text-align:center;padding:30px;color:#999">
                <div style="font-size:2.5rem;margin-bottom:10px">📝</div>
                <p>No tests taken yet. <a href="../aptitude_test/index.php" style="color:#3f51b5">Take a test →</a></p>
            </div>
            <?php else: ?>
            <canvas id="testScoreChart" height="120"></canvas>
            <?php endif; ?>
        </div>
        <div class="chart-card">
            <h3>🎯 Overall Performance Radar</h3>
            <canvas id="radarChart" height="200"></canvas>
        </div>
    </div>

    <!-- Row 3: Resume Score Progress + Category Avg -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
        <div class="chart-card">
            <h3>📄 Resume Score Progress</h3>
            <?php if (empty($resumeScores)): ?>
            <div style="text-align:center;padding:30px;color:#999">
                <p>No resume analysis yet. <a href="../resume_analyzer/index.php" style="color:#3f51b5">Analyze now →</a></p>
            </div>
            <?php else: ?>
            <canvas id="resumeChart" height="150"></canvas>
            <?php endif; ?>
        </div>
        <div class="chart-card">
            <h3>📊 Test Category Performance</h3>
            <canvas id="categoryBar" height="150"></canvas>
        </div>
    </div>

    <!-- Row 4: Industry breakdown + Skill Progress -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
        <?php if (!empty($industryLabels)): ?>
        <div class="chart-card">
            <h3>🏢 Applications by Industry</h3>
            <canvas id="industryChart" height="180"></canvas>
        </div>
        <?php endif; ?>
        <div class="chart-card" <?= empty($industryLabels) ? 'style="grid-column:1/-1"' : '' ?>>
            <h3>💡 Skill Progress Tracker</h3>
            <p style="color:#666;font-size:0.85rem;margin-bottom:15px">Your skills vs recommended count for placement readiness.</p>
            <?php
            $skillTargets = [
                ['Programming Languages', min($skillCount, 5), 5, '#3f51b5'],
                ['Resume Score', $latestResume, 100, '#00897b'],
                ['CGPA', min(100, round(($profile['cgpa'] ?? 0) / 10 * 100)), 100, '#fb8c00'],
                ['Tests Completed', min(count($testLabels), 5), 5, '#9c27b0'],
                ['Applications Sent', min($appStats['total'], 10), 10, '#e53935'],
                ['Interviews', min($ivStats['total'], 3), 3, '#1565c0'],
            ];
            foreach ($skillTargets as $st):
                $pct = $st[2] > 0 ? round($st[1] / $st[2] * 100) : 0;
            ?>
            <div class="progress-row">
                <div class="progress-label"><?= $st[0] ?></div>
                <div class="progress-bg">
                    <div class="progress-fill" data-width="<?= $pct ?>" style="width:0%;background:<?= $st[3] ?>"></div>
                </div>
                <div style="min-width:45px;text-align:right;font-size:0.82rem;font-weight:700;color:<?= $st[3] ?>"><?= $pct ?>%</div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Achievements -->
    <div class="chart-card">
        <h3>🏆 Achievements & Milestones</h3>
        <div style="display:flex;flex-wrap:wrap;gap:8px">
            <?php
            $achievements = [];
            if ($appStats['total'] >= 1)       $achievements[] = ['📋','First Application','Applied to first job','#e3f2fd','#1565c0'];
            if ($appStats['total'] >= 5)       $achievements[] = ['🚀','Active Applicant','Applied to 5+ jobs','#e8f5e9','#2e7d32'];
            if ($appStats['total'] >= 10)      $achievements[] = ['⚡','Power Applicant','Applied to 10+ jobs','#fff8e1','#e65100'];
            if ($appStats['shortlisted'] >= 1) $achievements[] = ['⭐','Shortlisted','Got shortlisted!','#fff8e1','#f57f17'];
            if ($appStats['selected'] >= 1)    $achievements[] = ['🎉','Placed!','Got selected!','#e8f5e9','#2e7d32'];
            if ($latestResume >= 60)           $achievements[] = ['📄','Good Resume','Resume score 60+','#e8eaf6','#3f51b5'];
            if ($latestResume >= 80)           $achievements[] = ['🌟','Great Resume','Resume score 80+','#e8f5e9','#2e7d32'];
            if (count($testLabels) >= 1)       $achievements[] = ['📝','Test Taker','Completed first test','#f3e5f5','#6a1b9a'];
            if (count($testLabels) >= 3)       $achievements[] = ['🧠','Test Pro','Completed 3+ tests','#e8eaf6','#3f51b5'];
            if ($skillCount >= 5)              $achievements[] = ['💡','Skilled','5+ skills listed','#e0f2f1','#00695c'];
            if ($skillCount >= 10)             $achievements[] = ['🔥','Multi-Skilled','10+ skills listed','#fff8e1','#e65100'];
            if ($ivStats['total'] >= 1)        $achievements[] = ['🎥','Interviewer','Attended an interview','#e3f2fd','#1565c0'];
            if ($profile['cgpa'] >= 8)         $achievements[] = ['🎓','Academic Star','CGPA 8.0+','#e8f5e9','#2e7d32'];
            if ($predResult['probability'] >= 70) $achievements[] = ['🔮','High Potential','70%+ placement chance','#e8eaf6','#3f51b5'];

            if (empty($achievements)):
            ?>
            <p style="color:#999;font-size:0.9rem">Complete your profile, apply to jobs, and take tests to earn achievements! 🏆</p>
            <?php else: foreach ($achievements as $a): ?>
            <div class="achievement-badge" style="background:<?= $a[3] ?>;color:<?= $a[4] ?>">
                <span style="font-size:1.2rem"><?= $a[0] ?></span>
                <div>
                    <div style="font-weight:800;font-size:0.82rem"><?= $a[1] ?></div>
                    <div style="font-size:0.72rem;opacity:0.8"><?= $a[2] ?></div>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

</div>

<?php require_once '../../chatbot/widget.php'; ?>

<script>
// Animate progress bars
window.addEventListener('load', () => {
    document.querySelectorAll('.progress-fill[data-width]').forEach(el => {
        setTimeout(() => { el.style.width = el.dataset.width + '%'; }, 300);
    });
});

const chartDefaults = {
    responsive: true,
    plugins: { legend: { display: false } },
};

// 1. Application Timeline
new Chart(document.getElementById('appTimelineChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($appTimeline, 'label')) ?>,
        datasets: [{
            label: 'Applications',
            data: <?= json_encode(array_column($appTimeline, 'count')) ?>,
            backgroundColor: 'rgba(63,81,181,0.7)',
            borderColor: '#3f51b5',
            borderWidth: 2,
            borderRadius: 6,
        }]
    },
    options: {
        ...chartDefaults,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f0f0f0' } },
            x: { grid: { display: false } }
        }
    }
});

// 2. Status Doughnut
new Chart(document.getElementById('statusDoughnut'), {
    type: 'doughnut',
    data: {
        labels: ['Applied','Shortlisted','Selected','Rejected'],
        datasets: [{
            data: <?= json_encode($statusData) ?>,
            backgroundColor: ['#3f51b5','#fb8c00','#43a047','#e53935'],
            borderWidth: 2, borderColor: '#fff',
        }]
    },
    options: {
        responsive: true,
        cutout: '65%',
        plugins: { legend: { display: false } }
    }
});

// 3. Test Score History
<?php if (!empty($testLabels)): ?>
new Chart(document.getElementById('testScoreChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($testLabels) ?>,
        datasets: [{
            label: 'Score %',
            data: <?= json_encode($testScores) ?>,
            borderColor: '#9c27b0',
            backgroundColor: 'rgba(156,39,176,0.1)',
            borderWidth: 2.5,
            pointBackgroundColor: '#9c27b0',
            pointRadius: 5,
            fill: true,
            tension: 0.4,
        }]
    },
    options: {
        ...chartDefaults,
        scales: {
            y: { beginAtZero: true, max: 100, ticks: { callback: v => v+'%' }, grid: { color: '#f0f0f0' } },
            x: { grid: { display: false } }
        }
    }
});
<?php endif; ?>

// 4. Radar Chart
new Chart(document.getElementById('radarChart'), {
    type: 'radar',
    data: {
        labels: ['CGPA','Skills','Resume','Aptitude','Technical','Shortlist Rate'],
        datasets: [{
            label: 'Your Score',
            data: <?= json_encode($radarData) ?>,
            backgroundColor: 'rgba(63,81,181,0.2)',
            borderColor: '#3f51b5',
            borderWidth: 2,
            pointBackgroundColor: '#3f51b5',
            pointRadius: 4,
        }]
    },
    options: {
        responsive: true,
        scales: {
            r: {
                beginAtZero: true, max: 100,
                ticks: { stepSize: 25, font: { size: 9 } },
                pointLabels: { font: { size: 10 } },
                grid: { color: '#e0e0e0' }
            }
        },
        plugins: { legend: { display: false } }
    }
});

// 5. Resume Score Progress
<?php if (!empty($resumeScores)): ?>
new Chart(document.getElementById('resumeChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($resumeLabels) ?>,
        datasets: [{
            label: 'Resume Score',
            data: <?= json_encode($resumeScores) ?>,
            borderColor: '#00897b',
            backgroundColor: 'rgba(0,137,123,0.1)',
            borderWidth: 2.5,
            pointBackgroundColor: '#00897b',
            pointRadius: 5,
            fill: true,
            tension: 0.4,
        }]
    },
    options: {
        ...chartDefaults,
        scales: {
            y: { beginAtZero: true, max: 100, grid: { color: '#f0f0f0' } },
            x: { grid: { display: false } }
        }
    }
});
<?php endif; ?>

// 6. Category Bar
new Chart(document.getElementById('categoryBar'), {
    type: 'bar',
    data: {
        labels: ['Aptitude','Technical','Coding'],
        datasets: [{
            data: [<?= $avgByCategory['aptitude'] ?>, <?= $avgByCategory['technical'] ?>, <?= $avgByCategory['coding'] ?>],
            backgroundColor: ['rgba(63,81,181,0.8)','rgba(0,137,123,0.8)','rgba(156,39,176,0.8)'],
            borderRadius: 8,
            borderWidth: 0,
        }]
    },
    options: {
        ...chartDefaults,
        scales: {
            y: { beginAtZero: true, max: 100, ticks: { callback: v => v+'%' }, grid: { color: '#f0f0f0' } },
            x: { grid: { display: false } }
        }
    }
});

// 7. Industry Chart
<?php if (!empty($industryLabels)): ?>
new Chart(document.getElementById('industryChart'), {
    type: 'pie',
    data: {
        labels: <?= json_encode($industryLabels) ?>,
        datasets: [{
            data: <?= json_encode($industryCounts) ?>,
            backgroundColor: ['#3f51b5','#00897b','#fb8c00','#e53935','#9c27b0','#1565c0'],
            borderWidth: 2, borderColor: '#fff',
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } }
    }
});
<?php endif; ?>
</script>
</body>
</html>
