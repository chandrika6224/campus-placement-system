<?php
require_once '../../includes/config.php';
requireLogin('admin');

// Job roles required skills (same as student page)
$jobRoles = [
    'Software Developer'        => ['php','python','java','javascript','c++','git','mysql','html','css','object oriented','data structures','algorithms'],
    'Web Developer'             => ['html','css','javascript','php','react','angular','bootstrap','mysql','git','node','rest api'],
    'Full Stack Developer'      => ['html','css','javascript','php','react','node','mysql','git','rest api','bootstrap','python'],
    'Data Scientist'            => ['python','r','machine learning','deep learning','tensorflow','sql','data science','pandas','statistics','tableau'],
    'Mobile Developer'          => ['java','kotlin','swift','flutter','dart','firebase','git','android','rest api'],
    'DevOps Engineer'           => ['docker','kubernetes','jenkins','aws','linux','git','python','bash','devops','azure'],
    'Machine Learning Engineer' => ['python','tensorflow','pytorch','keras','machine learning','deep learning','nlp','data science','sql','git'],
    'Cloud Engineer'            => ['aws','azure','gcp','docker','kubernetes','linux','python','cloud computing','terraform','git'],
    'Business Analyst'          => ['excel','tableau','power bi','sql','communication','presentation','agile','jira','data analysis'],
    'Data Analyst'              => ['python','sql','excel','tableau','power bi','statistics','data analysis','mysql','r'],
];

// Get all students with skills
$students = $conn->query("SELECT u.id, u.name, sp.department, sp.skills, sp.cgpa
    FROM users u JOIN student_profiles sp ON u.id=sp.user_id
    WHERE u.role='student' AND sp.skills IS NOT NULL AND sp.skills != ''
    ORDER BY sp.department, u.name");

$studentList = [];
while ($s = $students->fetch_assoc()) $studentList[] = $s;

// Compute missing skills frequency
$missingFreq = [];
$deptGaps    = [];
$roleGaps    = [];

foreach ($studentList as $s) {
    $sSkills = array_map('strtolower', array_map('trim', array_filter(explode(',', $s['skills']))));
    $dept = $s['department'] ?? 'Unknown';

    // Best role match
    $bestPct = 0; $bestRole = 'Software Developer';
    foreach ($jobRoles as $role => $req) {
        $matched = count(array_intersect($sSkills, array_map('strtolower', $req)));
        $pct = count($req) > 0 ? round($matched / count($req) * 100) : 0;
        if ($pct > $bestPct) { $bestPct = $pct; $bestRole = $role; }
    }

    $required = array_map('strtolower', $jobRoles[$bestRole]);
    $missing  = array_diff($required, $sSkills);

    foreach ($missing as $skill) {
        $missingFreq[$skill] = ($missingFreq[$skill] ?? 0) + 1;
    }

    if (!isset($deptGaps[$dept])) $deptGaps[$dept] = ['total'=>0,'gap_sum'=>0,'students'=>[]];
    $deptGaps[$dept]['total']++;
    $deptGaps[$dept]['gap_sum'] += count($missing);
    $deptGaps[$dept]['students'][] = [
        'name'    => $s['name'],
        'cgpa'    => $s['cgpa'],
        'skills'  => count($sSkills),
        'missing' => count($missing),
        'role'    => $bestRole,
        'pct'     => $bestPct,
    ];
}

arsort($missingFreq);
$topMissing = array_slice($missingFreq, 0, 15, true);

$totalStudents = count($studentList);
$avgMissing = $totalStudents > 0 ? round(array_sum($missingFreq) / $totalStudents, 1) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Skill Gap Report - Admin</title>
<link rel="stylesheet" href="../../css/style.css">
<style>
.skill-bar-bg { height:10px;background:#e0e0e0;border-radius:5px;flex:1; }
.skill-bar-fill { height:10px;border-radius:5px;background:linear-gradient(90deg,#e53935,#ef5350); }
.dept-card { background:#fff;border-radius:10px;padding:18px;box-shadow:0 2px 10px rgba(0,0,0,0.07);margin-bottom:15px;border-left:4px solid #3f51b5; }
</style>
</head>
<body>
<?php require_once '../sidebar.php'; ?>
<div class="topbar">
    <div class="topbar-left">
        <button class="hamburger" onclick="toggleSidebar()">☰</button>
        <span class="page-title">🧩 Skill Gap Report</span>
    </div>
    <div class="topbar-right"><?php require_once '../../notifications/widget.php'; ?></div>
</div>
<div class="main-content">
    <div class="card" style="background:linear-gradient(135deg,#004d40,#00695c);color:#fff;margin-bottom:25px">
        <h2 style="color:#ffd54f;margin-bottom:8px">🧩 Skill Gap Analysis Report</h2>
        <p style="color:#b2dfdb">Institution-wide skill gap overview. Identify what skills students are missing most and plan training programs.</p>
    </div>

    <!-- Stats -->
    <div class="stats-grid" style="grid-template-columns:repeat(4,1fr)">
        <div class="stat-card"><div class="number"><?= $totalStudents ?></div><div class="label">👨‍🎓 Students Analyzed</div></div>
        <div class="stat-card orange"><div class="number"><?= count($topMissing) ?></div><div class="label">❌ Unique Missing Skills</div></div>
        <div class="stat-card red"><div class="number"><?= $avgMissing ?></div><div class="label">📊 Avg Missing per Student</div></div>
        <div class="stat-card"><div class="number"><?= count($deptGaps) ?></div><div class="label">🏫 Departments</div></div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

        <!-- Top Missing Skills -->
        <div class="card">
            <h2>🔴 Most Common Missing Skills</h2>
            <p style="color:#666;font-size:0.88rem;margin-bottom:15px">Skills most frequently absent across all students (based on best-fit job role).</p>
            <?php $maxFreq = max($topMissing ?: [1]); ?>
            <?php foreach ($topMissing as $skill => $count): $pct = round($count / $totalStudents * 100); ?>
            <div style="margin-bottom:12px">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
                    <span style="font-weight:600;font-size:0.9rem;color:#333">💡 <?= htmlspecialchars(ucwords($skill)) ?></span>
                    <span style="font-size:0.82rem;color:#c62828;font-weight:700"><?= $count ?> students (<?= $pct ?>%)</span>
                </div>
                <div class="skill-bar-bg">
                    <div class="skill-bar-fill" style="width:<?= round($count/$maxFreq*100) ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($topMissing)): ?>
            <p style="color:#999;text-align:center;padding:20px">No data available yet.</p>
            <?php endif; ?>
        </div>

        <!-- Department-wise Gap -->
        <div class="card">
            <h2>🏫 Department-wise Skill Gap</h2>
            <p style="color:#666;font-size:0.88rem;margin-bottom:15px">Average number of missing skills per student by department.</p>
            <?php if (empty($deptGaps)): ?>
            <p style="color:#999;text-align:center;padding:20px">No data available yet.</p>
            <?php else: ?>
            <?php
            $maxGap = max(array_map(fn($d) => $d['total'] > 0 ? $d['gap_sum']/$d['total'] : 0, $deptGaps) ?: [1]);
            uasort($deptGaps, fn($a,$b) => ($b['gap_sum']/$b['total']) <=> ($a['gap_sum']/$a['total']));
            foreach ($deptGaps as $dept => $d):
                $avgGap = $d['total'] > 0 ? round($d['gap_sum'] / $d['total'], 1) : 0;
                $barPct = $maxGap > 0 ? round($avgGap / $maxGap * 100) : 0;
                $barColor = $avgGap >= 6 ? '#e53935' : ($avgGap >= 3 ? '#fb8c00' : '#43a047');
            ?>
            <div style="margin-bottom:14px">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
                    <span style="font-weight:600;font-size:0.9rem;color:#1a237e"><?= htmlspecialchars($dept) ?></span>
                    <span style="font-size:0.82rem;color:#555"><?= $d['total'] ?> students · avg <?= $avgGap ?> missing</span>
                </div>
                <div style="background:#e0e0e0;border-radius:5px;height:10px">
                    <div style="height:10px;border-radius:5px;width:<?= $barPct ?>%;background:<?= $barColor ?>"></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Student-wise breakdown -->
    <?php foreach ($deptGaps as $dept => $d): ?>
    <div class="dept-card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;flex-wrap:wrap;gap:10px">
            <h3 style="color:#1a237e;margin:0">🏫 <?= htmlspecialchars($dept) ?></h3>
            <span style="background:#e8eaf6;color:#3f51b5;padding:4px 12px;border-radius:20px;font-size:0.82rem;font-weight:700"><?= $d['total'] ?> students</span>
        </div>
        <div class="table-wrap">
            <table>
                <tr><th>Student</th><th>CGPA</th><th>Skills Listed</th><th>Best Role Match</th><th>Match %</th><th>Missing Skills</th></tr>
                <?php foreach ($d['students'] as $st):
                    $matchColor = $st['pct'] >= 70 ? '#2e7d32' : ($st['pct'] >= 40 ? '#e65100' : '#c62828');
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($st['name']) ?></strong></td>
                    <td><?= $st['cgpa'] ?: 'N/A' ?></td>
                    <td><?= $st['skills'] ?></td>
                    <td style="font-size:0.85rem"><?= htmlspecialchars($st['role']) ?></td>
                    <td><span style="font-weight:800;color:<?= $matchColor ?>"><?= $st['pct'] ?>%</span></td>
                    <td>
                        <?php if ($st['missing'] === 0): ?>
                        <span style="color:#2e7d32;font-weight:700">✅ None</span>
                        <?php else: ?>
                        <span style="color:#c62828;font-weight:700">❌ <?= $st['missing'] ?> skills</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
    <?php endforeach; ?>

    <?php if (empty($deptGaps)): ?>
    <div class="card" style="text-align:center;padding:40px">
        <div style="font-size:3rem;margin-bottom:15px">📊</div>
        <h3 style="color:#1a237e">No Student Data Available</h3>
        <p style="color:#666">Students need to add skills to their profiles for gap analysis.</p>
    </div>
    <?php endif; ?>
</div>
</div><!-- app-layout -->
<?php require_once '../../chatbot/widget.php'; ?>
<script>
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('show');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('sidebarOverlay').classList.remove('show');}
</script>
</body>
</html>
