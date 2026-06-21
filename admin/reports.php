<?php
require_once '../includes/config.php';
requireLogin('admin');

// Ensure placed_salary column exists
$conn->query("ALTER TABLE student_profiles ADD COLUMN IF NOT EXISTS placed_salary DECIMAL(10,2) DEFAULT NULL");
$conn->query("ALTER TABLE student_profiles ADD COLUMN IF NOT EXISTS placed_company VARCHAR(200) DEFAULT NULL");
$conn->query("ALTER TABLE student_profiles ADD COLUMN IF NOT EXISTS placed_month_year VARCHAR(20) DEFAULT NULL");

// Gather report data
// Placed = application status='selected' OR student_profiles.placement_status='Placed'
$placed = $conn->query("SELECT u.id as student_id, u.name, u.email, sp.department, sp.cgpa,
        j.title as job_title, c.company_name, j.salary_range, a.applied_at,
        'application' as source
    FROM applications a
    JOIN users u ON a.student_id=u.id
    JOIN student_profiles sp ON u.id=sp.user_id
    JOIN jobs j ON a.job_id=j.id
    JOIN companies c ON j.company_id=c.id
    WHERE a.status='selected'
    UNION
    SELECT u.id as student_id, u.name, u.email, sp.department, sp.cgpa,
        'Placed (CSV Import)' as job_title,
        COALESCE(NULLIF(sp.placed_company,''),'-') as company_name,
        CASE WHEN sp.placed_salary > 0 THEN CONCAT(sp.placed_salary, ' LPA') ELSE 'Not Set' END as salary_range,
        CASE WHEN sp.placed_month_year IS NOT NULL AND sp.placed_month_year != ''
             THEN CONCAT(sp.placed_month_year, '-01')
             ELSE u.created_at END as applied_at,
        'csv' as source
    FROM student_profiles sp
    JOIN users u ON sp.user_id=u.id
    WHERE sp.placement_status='Placed'
      AND u.id NOT IN (SELECT student_id FROM applications WHERE status='selected')
    ORDER BY applied_at DESC");

$dept_stats = $conn->query("
    SELECT department,
        COUNT(*) as total,
        SUM(is_placed) as placed
    FROM (
        SELECT sp.department,
            MAX(CASE WHEN sp.placement_status='Placed' OR a_placed.student_id IS NOT NULL THEN 1 ELSE 0 END) as is_placed
        FROM users u
        JOIN student_profiles sp ON u.id = sp.user_id
        LEFT JOIN (
            SELECT DISTINCT student_id FROM applications WHERE status='selected'
        ) a_placed ON u.id = a_placed.student_id
        WHERE u.role='student' AND sp.department IS NOT NULL AND sp.department != ''
        GROUP BY u.id, sp.department
    ) t
    GROUP BY department
    ORDER BY placed DESC
");

$company_stats = $conn->query("SELECT c.company_name, c.industry,
    COUNT(DISTINCT j.id) as jobs_posted,
    COUNT(a.id) as total_apps,
    SUM(CASE WHEN a.status='selected' THEN 1 ELSE 0 END) as hired
    FROM companies c
    LEFT JOIN jobs j ON c.id=j.company_id
    LEFT JOIN applications a ON j.id=a.job_id
    GROUP BY c.id ORDER BY hired DESC LIMIT 20");

$overall = [
    'students'    => $conn->query("SELECT COUNT(*) as c FROM users WHERE role='student'")->fetch_assoc()['c'],
    'placed'      => $conn->query("
        SELECT COUNT(DISTINCT uid) as c FROM (
            SELECT student_id as uid FROM applications WHERE status='selected'
            UNION
            SELECT user_id as uid FROM student_profiles WHERE placement_status='Placed'
        ) t
    ")->fetch_assoc()['c'],
    'companies'   => $conn->query("SELECT COUNT(*) as c FROM companies")->fetch_assoc()['c'],
    'jobs'        => $conn->query("SELECT COUNT(*) as c FROM jobs")->fetch_assoc()['c'],
    'applications'=> $conn->query("SELECT COUNT(*) as c FROM applications")->fetch_assoc()['c'],
];
$overall['placement_pct'] = $overall['students'] > 0 ? round($overall['placed'] / $overall['students'] * 100, 1) : 0;

// Highest package — from applications
$highPkg = $conn->query("SELECT j.salary_range, c.company_name, u.name as student_name
    FROM applications a JOIN jobs j ON a.job_id=j.id JOIN companies c ON j.company_id=c.id JOIN users u ON a.student_id=u.id
    WHERE a.status='selected' AND j.salary_range IS NOT NULL AND j.salary_range != ''
    ORDER BY j.salary_range DESC LIMIT 1")->fetch_assoc();
// Also check CSV placed_salary for highest package
$csvPkg = $conn->query("SELECT sp.placed_salary, u.name as student_name
    FROM student_profiles sp JOIN users u ON sp.user_id=u.id
    WHERE sp.placement_status='Placed' AND sp.placed_salary > 0
    ORDER BY sp.placed_salary DESC LIMIT 1")->fetch_assoc();
if ($csvPkg && (!$highPkg || (float)$csvPkg['placed_salary'] > (float)preg_replace('/[^0-9.]/','',$highPkg['salary_range'] ?? '0'))) {
    $highPkg = ['salary_range' => $csvPkg['placed_salary'].' LPA', 'company_name' => 'CSV Import', 'student_name' => $csvPkg['student_name']];
}

// Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="placement_report_'.date('Y-m-d').'.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Student Name','Email','Department','CGPA','Job Title','Company','Salary Range','Date','Source']);
    $placed->data_seek(0);
    while ($p = $placed->fetch_assoc()) {
        fputcsv($out, [$p['name'],$p['email'],$p['department'],$p['cgpa'],$p['job_title'],$p['company_name'],$p['salary_range'],date('d M Y',strtotime($p['applied_at'])),$p['source']]);
    }
    fclose($out);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Advanced Reports - Admin</title>
<link rel="stylesheet" href="../css/style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
@media print {
    .navbar,.no-print{display:none!important}
    .container{margin:0;padding:0;max-width:100%}
    .card{box-shadow:none;border:1px solid #ddd;page-break-inside:avoid}
}
.clickable-row { cursor:pointer; }
.clickable-row:hover { background:#f0f4ff; }
</style>
</head>
<body>
<?php require_once 'sidebar.php'; ?>
<div class="topbar no-print">
    <div class="topbar-left">
        <button class="hamburger" onclick="toggleSidebar()">☰</button>
        <span class="page-title">Reports</span>
    </div>
    <div class="topbar-right"><?php require_once '../notifications/widget.php'; ?></div>
</div>
<div class="main-content">
    <!-- Header + Export -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:10px" class="no-print">
        <h2 style="color:#1a237e;font-size:1.4rem">📊 Advanced Placement Reports</h2>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a href="?export=csv" class="btn btn-success">📥 Export CSV</a>
            <button onclick="window.print()" class="btn btn-primary">🖨️ Print / PDF</button>
        </div>
    </div>

    <!-- Overall Stats -->
    <div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr))">
        <div class="stat-card" onclick="window.location='students.php'" style="cursor:pointer" title="View all students"><div class="number"><?= $overall['students'] ?></div><div class="label">👨‍🎓 Total Students</div></div>
        <div class="stat-card green" onclick="window.location='students.php?placement=Placed'" style="cursor:pointer" title="View placed students"><div class="number"><?= $overall['placed'] ?></div><div class="label">✅ Students Placed</div></div>
        <div class="stat-card orange"><div class="number"><?= $overall['placement_pct'] ?>%</div><div class="label">📈 Placement Rate</div></div>
        <div class="stat-card" onclick="window.location='recruiters.php'" style="cursor:pointer" title="View all companies"><div class="number"><?= $overall['companies'] ?></div><div class="label">🏢 Companies</div></div>
        <div class="stat-card" onclick="window.location='jobs.php'" style="cursor:pointer" title="View all jobs"><div class="number"><?= $overall['jobs'] ?></div><div class="label">💼 Jobs Posted</div></div>
        <div class="stat-card red" onclick="window.location='applications.php'" style="cursor:pointer" title="View all applications"><div class="number"><?= $overall['applications'] ?></div><div class="label">📋 Applications</div></div>
    </div>

    <?php if ($highPkg): ?>
    <div class="card" style="background:linear-gradient(135deg,#1b5e20,#2e7d32);color:#fff;margin-bottom:20px">
        <div style="display:flex;align-items:center;gap:15px;flex-wrap:wrap">
            <div style="font-size:2.5rem">🏆</div>
            <div>
                <div style="font-size:0.85rem;color:#c8e6c9">Highest Package</div>
                <div style="font-size:1.5rem;font-weight:800;color:#ffd54f"><?= htmlspecialchars($highPkg['salary_range']) ?></div>
                <div style="color:#a5d6a7;font-size:0.88rem"><?= htmlspecialchars($highPkg['student_name']) ?> → <?= htmlspecialchars($highPkg['company_name']) ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
        <!-- Department Chart -->
        <div class="card">
            <h2>Department-wise Placement</h2>
            <canvas id="deptChart" height="220"></canvas>
        </div>

        <!-- Company Hiring Chart -->
        <div class="card">
            <h2>Top Hiring Companies</h2>
            <canvas id="compChart" height="220"></canvas>
        </div>
    </div>

    <!-- Department Table -->
    <div class="card">
        <h2>📊 Department-wise Statistics</h2>
        <div class="table-wrap">
            <table>
                <tr><th>Department</th><th>Total Students</th><th>Placed</th><th>Placement %</th><th>Progress</th></tr>
                <?php $dept_stats->data_seek(0); while($d = $dept_stats->fetch_assoc()):
                    $pct = $d['total'] > 0 ? round(($d['placed']/$d['total'])*100) : 0;
                ?>
                <tr class="clickable-row" onclick="window.location='students.php?dept=' + encodeURIComponent('<?= addslashes($d['department']) ?>')" style="cursor:pointer" title="View students in this department">
                    <td><strong style="color:#3949ab"><?= htmlspecialchars($d['department']) ?></strong></td>
                    <td><?= $d['total'] ?></td>
                    <td><strong style="color:#2e7d32"><?= $d['placed'] ?></strong></td>
                    <td><strong><?= $pct ?>%</strong></td>
                    <td>
                        <div style="background:#e8eaf6;border-radius:10px;height:10px;width:150px">
                            <div style="background:<?= $pct>=75?'#43a047':($pct>=50?'#fb8c00':'#e53935') ?>;height:10px;border-radius:10px;width:<?= $pct ?>%"></div>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>

    <!-- Company Analytics -->
    <div class="card">
        <h2>🏢 Company-wise Hiring Analytics</h2>
        <div class="table-wrap">
            <table>
                <tr><th>Company</th><th>Industry</th><th>Jobs Posted</th><th>Applications</th><th>Hired</th><th>Conversion</th></tr>
                <?php $company_stats->data_seek(0); while($c = $company_stats->fetch_assoc()):
                    $conv = $c['total_apps'] > 0 ? round($c['hired']/$c['total_apps']*100) : 0;
                ?>
                <tr class="clickable-row" onclick="window.location='jobs.php?company=' + encodeURIComponent('<?= addslashes($c['company_name']) ?>')" style="cursor:pointer" title="View jobs for this company">
                    <td><strong style="color:#3949ab"><?= htmlspecialchars($c['company_name']) ?></strong></td>
                    <td><?= htmlspecialchars($c['industry'] ?? '-') ?></td>
                    <td><?= $c['jobs_posted'] ?></td>
                    <td><?= $c['total_apps'] ?></td>
                    <td><strong style="color:#2e7d32"><?= $c['hired'] ?></strong></td>
                    <td><?= $conv ?>%</td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>

    <!-- Placed Students -->
    <div class="card">
        <h2>✅ Placed Students List</h2>
        <div class="table-wrap">
            <table>
                <tr><th>Student</th><th>Email</th><th>Department</th><th>CGPA</th><th>Job Title</th><th>Company</th><th>Package</th><th>Date</th><th>Source</th></tr>
                <?php $placed->data_seek(0); while($p = $placed->fetch_assoc()): ?>
                <tr class="clickable-row" onclick="window.location='students.php?open=<?= $p['student_id'] ?>'" style="cursor:pointer" title="View student details">
                    <td><strong style="color:#3949ab"><?= htmlspecialchars($p['name']) ?></strong></td>
                    <td><?= htmlspecialchars($p['email']) ?></td>
                    <td><?= htmlspecialchars($p['department'] ?? '-') ?></td>
                    <td><?= $p['cgpa'] ?? '-' ?></td>
                    <td><?= htmlspecialchars($p['job_title']) ?></td>
                    <td><?= htmlspecialchars($p['company_name']) ?></td>
                    <td><?= htmlspecialchars($p['salary_range'] ?? '-') ?></td>
                    <td><?= date('d M Y', strtotime($p['applied_at'])) ?></td>
                    <td>
                        <?php if ($p['source'] === 'csv'): ?>
                        <span style="background:#e8eaf6;color:#3f51b5;padding:2px 8px;border-radius:10px;font-size:0.75rem;font-weight:700">CSV Import</span>
                        <?php else: ?>
                        <span style="background:#e8f5e9;color:#2e7d32;padding:2px 8px;border-radius:10px;font-size:0.75rem;font-weight:700">Application</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>
</div><!-- main-content -->
</div><!-- app-layout -->
<?php
$dept_stats->data_seek(0);
$deptLabels = []; $deptPlaced = []; $deptTotal = [];
while($d = $dept_stats->fetch_assoc()) {
    $deptLabels[] = $d['department'];
    $deptPlaced[] = (int)$d['placed'];
    $deptTotal[]  = (int)$d['total'];
}
$company_stats->data_seek(0);
$compLabels = []; $compHired = [];
while($c = $company_stats->fetch_assoc()) {
    if ($c['hired'] > 0) { $compLabels[] = $c['company_name']; $compHired[] = (int)$c['hired']; }
}
?>
<script>
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('show');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('sidebarOverlay').classList.remove('show');}
const deptColors = ['#3f51b5','#e53935','#43a047','#fb8c00','#9c27b0','#00897b','#f06292','#1565c0'];

new Chart(document.getElementById('deptChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($deptLabels) ?>,
        datasets: [
            { label: 'Total', data: <?= json_encode($deptTotal) ?>, backgroundColor: '#e8eaf6', borderColor: '#9fa8da', borderWidth: 1 },
            { label: 'Placed', data: <?= json_encode($deptPlaced) ?>, backgroundColor: '#43a047', borderColor: '#2e7d32', borderWidth: 1 }
        ]
    },
    options: { responsive: true, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true } } }
});

new Chart(document.getElementById('compChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($compLabels) ?>,
        datasets: [{ data: <?= json_encode($compHired) ?>, backgroundColor: deptColors }]
    },
    options: { responsive: true, plugins: { legend: { position: 'right' } } }
});
</script>
</body>
</html>
