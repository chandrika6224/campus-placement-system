<?php
require_once '../../includes/config.php';
requireLogin('recruiter');

$uid = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if ($uid === 0) { header('Location: ../../index.php'); exit(); }
$stCo = $conn->prepare("SELECT * FROM companies WHERE user_id=?");
$stCo->bind_param('i', $uid); $stCo->execute();
$company = $stCo->get_result()->fetch_assoc(); $stCo->close();
$cid = (int)($company['id'] ?? 0);

function anaCount($conn, $sql, $types='', ...$vals) {
    $st = $conn->prepare($sql); if ($types) $st->bind_param($types, ...$vals);
    $st->execute(); $c = (int)$st->get_result()->fetch_assoc()['c']; $st->close(); return $c;
}

$stats = [
    'total_jobs'   => anaCount($conn, "SELECT COUNT(*) as c FROM jobs WHERE company_id=?", 'i', $cid),
    'open_jobs'    => anaCount($conn, "SELECT COUNT(*) as c FROM jobs WHERE company_id=? AND status='open'", 'i', $cid),
    'total_apps'   => anaCount($conn, "SELECT COUNT(*) as c FROM applications a JOIN jobs j ON a.job_id=j.id WHERE j.company_id=?", 'i', $cid),
    'shortlisted'  => anaCount($conn, "SELECT COUNT(*) as c FROM applications a JOIN jobs j ON a.job_id=j.id WHERE j.company_id=? AND a.status='shortlisted'", 'i', $cid),
    'selected'     => anaCount($conn, "SELECT COUNT(*) as c FROM applications a JOIN jobs j ON a.job_id=j.id WHERE j.company_id=? AND a.status='selected'", 'i', $cid),
    'rejected'     => anaCount($conn, "SELECT COUNT(*) as c FROM applications a JOIN jobs j ON a.job_id=j.id WHERE j.company_id=? AND a.status='rejected'", 'i', $cid),
    'interviews'   => anaCount($conn, "SELECT COUNT(*) as c FROM interviews WHERE company_id=?", 'i', $cid),
    'iv_completed' => anaCount($conn, "SELECT COUNT(*) as c FROM interviews WHERE company_id=? AND status='completed'", 'i', $cid),
];

$hiringRate    = $stats['total_apps'] > 0 ? round($stats['selected'] / $stats['total_apps'] * 100, 1) : 0;
$shortlistRate = $stats['total_apps'] > 0 ? round($stats['shortlisted'] / $stats['total_apps'] * 100, 1) : 0;

// ── Applications over last 6 months ──────────────────────────────────────────
$appTimeline = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $label = date('M Y', strtotime("-$i months"));
    $stTL = $conn->prepare("SELECT COUNT(*) as total, SUM(a.status='shortlisted') as shortlisted, SUM(a.status='selected') as selected FROM applications a JOIN jobs j ON a.job_id=j.id WHERE j.company_id=? AND DATE_FORMAT(a.applied_at,'%Y-%m')=?");
    $stTL->bind_param('is', $cid, $month); $stTL->execute();
    $row = $stTL->get_result()->fetch_assoc(); $stTL->close();
    $appTimeline[] = ['label'=>$label,'total'=>(int)$row['total'],'shortlisted'=>(int)$row['shortlisted'],'selected'=>(int)$row['selected']];
}

// ── Department-wise applications ──────────────────────────────────────────────
$stDA = $conn->prepare("SELECT sp.department, COUNT(*) as cnt FROM applications a JOIN jobs j ON a.job_id=j.id JOIN student_profiles sp ON a.student_id=sp.user_id WHERE j.company_id=? AND sp.department IS NOT NULL AND sp.department != '' GROUP BY sp.department ORDER BY cnt DESC LIMIT 8");
$stDA->bind_param('i', $cid); $stDA->execute();
$deptApps = $stDA->get_result(); $stDA->close();
$deptLabels = []; $deptCounts = [];
while ($d = $deptApps->fetch_assoc()) {
    $deptLabels[] = $d['department'];
    $deptCounts[] = (int)$d['cnt'];
}

// ── Per-job application stats ─────────────────────────────────────────────────
$stJS = $conn->prepare("SELECT j.title, j.status, j.job_type, COUNT(a.id) as total_apps, SUM(a.status='shortlisted') as shortlisted, SUM(a.status='selected') as selected, SUM(a.status='rejected') as rejected, AVG(sp.cgpa) as avg_cgpa FROM jobs j LEFT JOIN applications a ON a.job_id=j.id LEFT JOIN student_profiles sp ON a.student_id=sp.user_id WHERE j.company_id=? GROUP BY j.id ORDER BY total_apps DESC");
$stJS->bind_param('i', $cid); $stJS->execute();
$jobStats = $stJS->get_result(); $stJS->close();

$jobTitles = []; $jobAppCounts = []; $jobSelectedCounts = [];
$jobRows = [];
while ($j = $jobStats->fetch_assoc()) {
    $jobTitles[]        = substr($j['title'], 0, 20);
    $jobAppCounts[]     = (int)$j['total_apps'];
    $jobSelectedCounts[]= (int)$j['selected'];
    $jobRows[]          = $j;
}

// ── CGPA distribution of applicants ──────────────────────────────────────────
$cgpaBands = ['< 6.0'=>0, '6.0-6.9'=>0, '7.0-7.9'=>0, '8.0-8.9'=>0, '9.0+'=>0];
$stCGPA = $conn->prepare("SELECT sp.cgpa FROM applications a JOIN jobs j ON a.job_id=j.id JOIN student_profiles sp ON a.student_id=sp.user_id WHERE j.company_id=? AND sp.cgpa > 0");
$stCGPA->bind_param('i', $cid); $stCGPA->execute();
$cgpaResult = $stCGPA->get_result(); $stCGPA->close();
while ($r = $cgpaResult->fetch_assoc()) {
    $c = (float)$r['cgpa'];
    if ($c < 6)       $cgpaBands['< 6.0']++;
    elseif ($c < 7)   $cgpaBands['6.0-6.9']++;
    elseif ($c < 8)   $cgpaBands['7.0-7.9']++;
    elseif ($c < 9)   $cgpaBands['8.0-8.9']++;
    else              $cgpaBands['9.0+']++;
}

// ── Interview performance ─────────────────────────────────────────────────────
$stIVS = $conn->prepare("SELECT status, COUNT(*) as cnt FROM interviews WHERE company_id=? GROUP BY status");
$stIVS->bind_param('i', $cid); $stIVS->execute();
$ivByStatus = $stIVS->get_result(); $stIVS->close();
$ivStatusLabels = []; $ivStatusCounts = [];
while ($iv = $ivByStatus->fetch_assoc()) {
    $ivStatusLabels[] = ucfirst($iv['status']);
    $ivStatusCounts[] = (int)$iv['cnt'];
}

// ── Top applicants (by CGPA) ──────────────────────────────────────────────────
$stTA = $conn->prepare("SELECT u.name, u.email, sp.department, sp.cgpa, sp.skills, COUNT(a.id) as apps, SUM(a.status='shortlisted') as shortlisted, SUM(a.status='selected') as selected, j.title as job_title FROM applications a JOIN users u ON a.student_id=u.id JOIN student_profiles sp ON u.id=sp.user_id JOIN jobs j ON a.job_id=j.id WHERE j.company_id=? GROUP BY a.student_id ORDER BY sp.cgpa DESC, shortlisted DESC LIMIT 10");
$stTA->bind_param('i', $cid); $stTA->execute();
$topApplicants = $stTA->get_result(); $stTA->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Company Analytics - <?= htmlspecialchars($company['company_name'] ?? 'Recruiter') ?></title>
<link rel="stylesheet" href="../../css/style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
.kpi-card { background:#fff;border-radius:12px;padding:18px 20px;box-shadow:0 2px 10px rgba(0,0,0,0.07);border-left:5px solid #3f51b5;display:flex;align-items:center;gap:15px; }
.kpi-icon { font-size:2rem;width:50px;height:50px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0; }
.kpi-num { font-size:1.8rem;font-weight:800;color:#1a237e;line-height:1; }
.kpi-lbl { font-size:0.82rem;color:#666;margin-top:3px; }
.kpi-sub { font-size:0.78rem;font-weight:700;margin-top:4px; }
.chart-card { background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 10px rgba(0,0,0,0.07);margin-bottom:20px; }
.chart-card h3 { color:#1a237e;font-size:1.05rem;margin-bottom:15px;padding-bottom:8px;border-bottom:2px solid #e8eaf6; }
.rate-circle { width:90px;height:90px;border-radius:50%;display:flex;flex-direction:column;align-items:center;justify-content:center;font-weight:800;flex-shrink:0; }
</style>
</head>
<body>
<nav class="navbar">
    <a href="../dashboard.php" class="brand">🎓 Campus<span>Recruit</span> <small style="font-size:0.7rem;color:#90caf9">Recruiter</small></a>
    <div class="nav-links">
        <a href="../dashboard.php">Dashboard</a>
        <a href="../post_job.php">Post Job</a>
        <a href="../jobs.php">My Jobs</a>
        <a href="../applications.php">Applications</a>
        <a href="../interviews/index.php">🎥 Interviews</a>
        <a href="index.php" class="active">📊 Analytics</a>
        <a href="../profile.php">Profile</a>
        <?php require_once '../../notifications/widget.php'; ?>
        <a href="../logout.php" class="btn-logout">Logout</a>
    </div>
</nav>

<div class="container">

    <!-- Header -->
    <div class="card" style="background:linear-gradient(135deg,#0d47a1,#1565c0);color:#fff;margin-bottom:25px">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:15px">
            <div>
                <h2 style="color:#ffd54f;margin-bottom:6px">📊 Company Analytics Dashboard</h2>
                <p style="color:#90caf9"><?= htmlspecialchars($company['company_name'] ?? 'Your Company') ?> — Recruitment performance overview</p>
            </div>
            <div style="display:flex;gap:20px;flex-wrap:wrap">
                <div style="text-align:center">
                    <div style="font-size:1.8rem;font-weight:800;color:#69f0ae"><?= $hiringRate ?>%</div>
                    <div style="font-size:0.78rem;color:#90caf9">Hiring Rate</div>
                </div>
                <div style="text-align:center">
                    <div style="font-size:1.8rem;font-weight:800;color:#ffd54f"><?= $shortlistRate ?>%</div>
                    <div style="font-size:0.78rem;color:#90caf9">Shortlist Rate</div>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI Cards -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:15px;margin-bottom:25px">
        <div class="kpi-card" style="border-left-color:#3f51b5">
            <div class="kpi-icon" style="background:#e8eaf6">💼</div>
            <div>
                <div class="kpi-num"><?= $stats['total_jobs'] ?></div>
                <div class="kpi-lbl">Total Jobs Posted</div>
                <div class="kpi-sub" style="color:#2e7d32"><?= $stats['open_jobs'] ?> currently open</div>
            </div>
        </div>
        <div class="kpi-card" style="border-left-color:#fb8c00">
            <div class="kpi-icon" style="background:#fff8e1">📋</div>
            <div>
                <div class="kpi-num"><?= $stats['total_apps'] ?></div>
                <div class="kpi-lbl">Total Applications</div>
                <div class="kpi-sub" style="color:#e65100"><?= $stats['total_jobs'] > 0 ? round($stats['total_apps']/$stats['total_jobs'],1) : 0 ?> avg per job</div>
            </div>
        </div>
        <div class="kpi-card" style="border-left-color:#fdd835">
            <div class="kpi-icon" style="background:#fffde7">⭐</div>
            <div>
                <div class="kpi-num"><?= $stats['shortlisted'] ?></div>
                <div class="kpi-lbl">Shortlisted</div>
                <div class="kpi-sub" style="color:#f57f17"><?= $shortlistRate ?>% shortlist rate</div>
            </div>
        </div>
        <div class="kpi-card" style="border-left-color:#43a047">
            <div class="kpi-icon" style="background:#e8f5e9">✅</div>
            <div>
                <div class="kpi-num"><?= $stats['selected'] ?></div>
                <div class="kpi-lbl">Selected / Hired</div>
                <div class="kpi-sub" style="color:#2e7d32"><?= $hiringRate ?>% hiring rate</div>
            </div>
        </div>
        <div class="kpi-card" style="border-left-color:#1565c0">
            <div class="kpi-icon" style="background:#e3f2fd">🎥</div>
            <div>
                <div class="kpi-num"><?= $stats['interviews'] ?></div>
                <div class="kpi-lbl">Interviews Scheduled</div>
                <div class="kpi-sub" style="color:#1565c0"><?= $stats['iv_completed'] ?> completed</div>
            </div>
        </div>
        <div class="kpi-card" style="border-left-color:#e53935">
            <div class="kpi-icon" style="background:#ffebee">❌</div>
            <div>
                <div class="kpi-num"><?= $stats['rejected'] ?></div>
                <div class="kpi-lbl">Rejected</div>
                <div class="kpi-sub" style="color:#c62828"><?= $stats['total_apps'] > 0 ? round($stats['rejected']/$stats['total_apps']*100,1) : 0 ?>% rejection rate</div>
            </div>
        </div>
    </div>

    <!-- Row 1: Application Timeline + Status Doughnut -->
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:20px">
        <div class="chart-card">
            <h3>📈 Application Trends (Last 6 Months)</h3>
            <canvas id="appTrendChart" height="120"></canvas>
        </div>
        <div class="chart-card" style="display:flex;flex-direction:column;align-items:center">
            <h3 style="width:100%">🍩 Application Funnel</h3>
            <canvas id="funnelChart" height="180"></canvas>
            <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:10px;justify-content:center;font-size:0.78rem">
                <?php foreach ([['Applied','#3f51b5',$stats['total_apps']],['Shortlisted','#fdd835',$stats['shortlisted']],['Selected','#43a047',$stats['selected']],['Rejected','#e53935',$stats['rejected']]] as $f): ?>
                <span style="display:flex;align-items:center;gap:4px">
                    <span style="width:10px;height:10px;background:<?= $f[1] ?>;border-radius:2px;display:inline-block"></span>
                    <?= $f[0] ?>: <strong><?= $f[2] ?></strong>
                </span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Row 2: Department-wise + CGPA Distribution -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
        <div class="chart-card">
            <h3>🏫 Department-wise Applications</h3>
            <?php if (empty($deptLabels)): ?>
            <div style="text-align:center;padding:30px;color:#999">No department data yet.</div>
            <?php else: ?>
            <canvas id="deptChart" height="180"></canvas>
            <?php endif; ?>
        </div>
        <div class="chart-card">
            <h3>📊 Applicant CGPA Distribution</h3>
            <canvas id="cgpaChart" height="180"></canvas>
        </div>
    </div>

    <!-- Row 3: Per-job performance + Interview status -->
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:20px">
        <div class="chart-card">
            <h3>💼 Applications per Job</h3>
            <?php if (empty($jobTitles)): ?>
            <div style="text-align:center;padding:30px;color:#999">No jobs posted yet.</div>
            <?php else: ?>
            <canvas id="jobChart" height="130"></canvas>
            <?php endif; ?>
        </div>
        <div class="chart-card">
            <h3>🎥 Interview Status</h3>
            <?php if (empty($ivStatusLabels)): ?>
            <div style="text-align:center;padding:30px;color:#999">No interviews yet.</div>
            <?php else: ?>
            <canvas id="ivChart" height="180"></canvas>
            <?php endif; ?>
        </div>
    </div>

    <!-- Per-job detailed table -->
    <div class="chart-card">
        <h3>📋 Job-wise Recruitment Summary</h3>
        <?php if (empty($jobRows)): ?>
        <p style="color:#999;text-align:center;padding:20px">No jobs posted yet. <a href="../post_job.php" style="color:#3f51b5">Post a job →</a></p>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <tr><th>Job Title</th><th>Type</th><th>Status</th><th>Applications</th><th>Shortlisted</th><th>Selected</th><th>Rejected</th><th>Avg CGPA</th><th>Hire Rate</th></tr>
                <?php foreach ($jobRows as $j):
                    $rate = $j['total_apps'] > 0 ? round($j['selected']/$j['total_apps']*100,1) : 0;
                    $rateColor = $rate >= 20 ? '#2e7d32' : ($rate >= 10 ? '#e65100' : '#c62828');
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($j['title']) ?></strong></td>
                    <td><?= $j['job_type'] ?></td>
                    <td><span class="badge badge-<?= $j['status'] ?>"><?= ucfirst($j['status']) ?></span></td>
                    <td><?= $j['total_apps'] ?></td>
                    <td style="color:#f57f17;font-weight:700"><?= $j['shortlisted'] ?></td>
                    <td style="color:#2e7d32;font-weight:700"><?= $j['selected'] ?></td>
                    <td style="color:#c62828"><?= $j['rejected'] ?></td>
                    <td><?= $j['avg_cgpa'] ? round($j['avg_cgpa'],2) : 'N/A' ?></td>
                    <td><span style="font-weight:800;color:<?= $rateColor ?>"><?= $rate ?>%</span></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Top Applicants -->
    <div class="chart-card">
        <h3>🌟 Top Applicants by CGPA</h3>
        <?php if ($topApplicants->num_rows === 0): ?>
        <p style="color:#999;text-align:center;padding:20px">No applicants yet.</p>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <tr><th>#</th><th>Student</th><th>Department</th><th>CGPA</th><th>Skills</th><th>Applied For</th><th>Status</th></tr>
                <?php $rank=1; while($a = $topApplicants->fetch_assoc()): ?>
                <tr>
                    <td><strong style="color:#3f51b5">#<?= $rank++ ?></strong></td>
                    <td>
                        <strong><?= htmlspecialchars($a['name']) ?></strong><br>
                        <small style="color:#999"><?= htmlspecialchars($a['email']) ?></small>
                    </td>
                    <td><?= htmlspecialchars($a['department'] ?? 'N/A') ?></td>
                    <td>
                        <span style="font-weight:800;color:<?= $a['cgpa']>=8?'#2e7d32':($a['cgpa']>=7?'#1565c0':'#e65100') ?>">
                            <?= $a['cgpa'] ?: 'N/A' ?>
                        </span>
                    </td>
                    <td style="font-size:0.8rem;max-width:150px"><?= htmlspecialchars(substr($a['skills'] ?? '', 0, 50)) ?><?= strlen($a['skills'] ?? '') > 50 ? '...' : '' ?></td>
                    <td style="font-size:0.85rem"><?= htmlspecialchars($a['job_title']) ?></td>
                    <td>
                        <?php if ($a['selected']): ?>
                        <span class="badge badge-selected">✅ Selected</span>
                        <?php elseif ($a['shortlisted']): ?>
                        <span class="badge badge-shortlisted">⭐ Shortlisted</span>
                        <?php else: ?>
                        <span class="badge badge-applied">Applied</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once '../../chatbot/widget.php'; ?>

<script>
// 1. Application Trend (multi-dataset bar)
new Chart(document.getElementById('appTrendChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($appTimeline,'label')) ?>,
        datasets: [
            {
                label: 'Total',
                data: <?= json_encode(array_column($appTimeline,'total')) ?>,
                backgroundColor: 'rgba(63,81,181,0.7)',
                borderRadius: 5,
            },
            {
                label: 'Shortlisted',
                data: <?= json_encode(array_column($appTimeline,'shortlisted')) ?>,
                backgroundColor: 'rgba(253,216,53,0.85)',
                borderRadius: 5,
            },
            {
                label: 'Selected',
                data: <?= json_encode(array_column($appTimeline,'selected')) ?>,
                backgroundColor: 'rgba(67,160,71,0.85)',
                borderRadius: 5,
            },
        ]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f0f0f0' } },
            x: { grid: { display: false } }
        },
        plugins: { legend: { position: 'top', labels: { font: { size: 11 } } } }
    }
});

// 2. Funnel Doughnut
new Chart(document.getElementById('funnelChart'), {
    type: 'doughnut',
    data: {
        labels: ['Applied','Shortlisted','Selected','Rejected'],
        datasets: [{
            data: [<?= $stats['total_apps'] ?>, <?= $stats['shortlisted'] ?>, <?= $stats['selected'] ?>, <?= $stats['rejected'] ?>],
            backgroundColor: ['#3f51b5','#fdd835','#43a047','#e53935'],
            borderWidth: 2, borderColor: '#fff',
        }]
    },
    options: { responsive: true, cutout: '60%', plugins: { legend: { display: false } } }
});

// 3. Department Bar
<?php if (!empty($deptLabels)): ?>
new Chart(document.getElementById('deptChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($deptLabels) ?>,
        datasets: [{
            data: <?= json_encode($deptCounts) ?>,
            backgroundColor: ['#3f51b5','#00897b','#fb8c00','#e53935','#9c27b0','#1565c0','#f57f17','#00695c'].slice(0, <?= count($deptLabels) ?>),
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        indexAxis: 'y',
        scales: {
            x: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f0f0f0' } },
            y: { grid: { display: false } }
        },
        plugins: { legend: { display: false } }
    }
});
<?php endif; ?>

// 4. CGPA Distribution
new Chart(document.getElementById('cgpaChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_keys($cgpaBands)) ?>,
        datasets: [{
            data: <?= json_encode(array_values($cgpaBands)) ?>,
            backgroundColor: ['#e53935','#fb8c00','#fdd835','#43a047','#1565c0'],
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f0f0f0' } },
            x: { grid: { display: false } }
        },
        plugins: { legend: { display: false } }
    }
});

// 5. Per-job chart
<?php if (!empty($jobTitles)): ?>
new Chart(document.getElementById('jobChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($jobTitles) ?>,
        datasets: [
            {
                label: 'Applications',
                data: <?= json_encode($jobAppCounts) ?>,
                backgroundColor: 'rgba(63,81,181,0.75)',
                borderRadius: 5,
            },
            {
                label: 'Selected',
                data: <?= json_encode($jobSelectedCounts) ?>,
                backgroundColor: 'rgba(67,160,71,0.85)',
                borderRadius: 5,
            }
        ]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f0f0f0' } },
            x: { grid: { display: false } }
        },
        plugins: { legend: { position: 'top', labels: { font: { size: 11 } } } }
    }
});
<?php endif; ?>

// 6. Interview Status
<?php if (!empty($ivStatusLabels)): ?>
new Chart(document.getElementById('ivChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($ivStatusLabels) ?>,
        datasets: [{
            data: <?= json_encode($ivStatusCounts) ?>,
            backgroundColor: ['#1565c0','#43a047','#e53935','#fb8c00'],
            borderWidth: 2, borderColor: '#fff',
        }]
    },
    options: {
        responsive: true,
        cutout: '55%',
        plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } }
    }
});
<?php endif; ?>
</script>
</body>
</html>
