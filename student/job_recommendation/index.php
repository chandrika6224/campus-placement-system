<?php
require_once '../../includes/config.php';
requireLogin('student');

$uid = $_SESSION['user_id'];

$profile = $conn->query("SELECT sp.*, u.name, u.email FROM student_profiles sp 
    JOIN users u ON sp.user_id=u.id WHERE sp.user_id=$uid")->fetch_assoc();

$appliedJobs = [];
$appResult = $conn->query("SELECT job_id FROM applications WHERE student_id=$uid");
while ($row = $appResult->fetch_assoc()) $appliedJobs[] = $row['job_id'];

$appliedStr = !empty($appliedJobs) ? implode(',', $appliedJobs) : '0';
$jobs = $conn->query("SELECT j.*, c.company_name, c.industry FROM jobs j 
    JOIN companies c ON j.company_id=c.id 
    WHERE j.status='open' AND j.id NOT IN ($appliedStr)");

$allJobs = [];
while ($row = $jobs->fetch_assoc()) $allJobs[] = $row;

function recommendJobs($profile, $allJobs) {
    $recommendations = [];

    $studentSkills = array_map('trim', array_map('strtolower', explode(',', $profile['skills'] ?? '')));
    $studentDept   = strtolower($profile['department'] ?? '');
    $studentCGPA   = (float)($profile['cgpa'] ?? 0);

    $deptSkillMap = [
        'computer science'       => ['php','python','java','javascript','c++','mysql','html','css','react','node','git'],
        'information technology' => ['php','python','javascript','mysql','html','css','networking','linux','git','react'],
        'electronics'            => ['c','c++','embedded','matlab','python','iot','microcontroller'],
        'mechanical'             => ['autocad','solidworks','matlab','catia','ansys','manufacturing'],
        'civil'                  => ['autocad','staad','revit','construction','surveying'],
        'electrical'             => ['matlab','plc','scada','power systems','autocad'],
        'mba'                    => ['excel','tableau','power bi','communication','leadership','management','sql'],
        'mca'                    => ['php','python','java','javascript','mysql','html','css','react','git'],
    ];

    $jobSkillMap = [
        'software'   => ['python','java','javascript','php','c++','git','mysql'],
        'web'        => ['html','css','javascript','php','react','angular','mysql'],
        'data'       => ['python','r','machine learning','sql','tableau','excel'],
        'mobile'     => ['java','kotlin','swift','flutter','dart','firebase'],
        'devops'     => ['docker','kubernetes','jenkins','aws','linux','git'],
        'database'   => ['mysql','postgresql','oracle','mongodb','sql'],
        'ui'         => ['figma','photoshop','css','html','bootstrap'],
        'machine'    => ['python','tensorflow','pytorch','machine learning'],
        'cloud'      => ['aws','azure','gcp','docker','kubernetes','linux'],
        'security'   => ['cybersecurity','networking','linux','python'],
        'full stack' => ['html','css','javascript','php','react','node','mysql'],
        'analyst'    => ['excel','tableau','power bi','sql','communication'],
        'testing'    => ['selenium','java','python','manual testing','jira'],
    ];

    foreach ($allJobs as $job) {
        $score = 0;
        $reasons = [];
        $jobTitle = strtolower($job['title']);
        $fullText = $jobTitle . ' ' . strtolower($job['requirements'] ?? '') . ' ' . strtolower($job['description'] ?? '');

        // 1. Skills match (40 pts)
        $matchedSkills = [];
        foreach ($studentSkills as $skill) {
            if (!empty($skill) && strpos($fullText, $skill) !== false) $matchedSkills[] = $skill;
        }
        if (!empty($matchedSkills)) {
            $score += min(40, count($matchedSkills) * 8);
            $preview = implode(', ', array_slice($matchedSkills, 0, 3)) . (count($matchedSkills) > 3 ? '...' : '');
            $reasons[] = "✅ " . count($matchedSkills) . " skills matched ($preview)";
        }

        // 2. Department match (20 pts)
        $deptSkills = $deptSkillMap[$studentDept] ?? [];
        $deptMatches = 0;
        foreach ($deptSkills as $ds) { if (strpos($fullText, $ds) !== false) $deptMatches++; }
        if ($deptMatches >= 3)      { $score += 20; $reasons[] = "🎓 Matches your department (" . ucwords($studentDept) . ")"; }
        elseif ($deptMatches >= 1)  { $score += 10; $reasons[] = "🎓 Partially matches your department"; }

        // 3. CGPA eligibility (15 pts)
        $minCgpa = (float)($job['min_cgpa'] ?? 0);
        if ($minCgpa == 0 || $studentCGPA >= $minCgpa) {
            $score += 15;
            $reasons[] = "📊 CGPA eligible (Min: " . ($minCgpa ?: 'None') . ", Yours: $studentCGPA)";
        } else {
            $reasons[] = "⚠️ CGPA not met (Min: $minCgpa, Yours: $studentCGPA)";
        }

        // 4. Job type match (15 pts)
        foreach ($jobSkillMap as $keyword => $relatedSkills) {
            if (strpos($jobTitle, $keyword) !== false) {
                $typeMatches = count(array_intersect($studentSkills, $relatedSkills));
                if ($typeMatches >= 2) { $score += 15; $reasons[] = "💼 Job type aligns with your skills"; break; }
            }
        }

        // 5. Industry match (10 pts)
        $industryMap = ['it/software' => ['computer science','information technology','mca'], 'finance' => ['mba'], 'manufacturing' => ['mechanical','electrical','civil']];
        foreach ($industryMap as $ind => $depts) {
            if (strpos(strtolower($job['industry'] ?? ''), explode('/', $ind)[0]) !== false && in_array($studentDept, $depts)) {
                $score += 10; $reasons[] = "🏢 Industry matches your background"; break;
            }
        }

        if ($score > 0) {
            $recommendations[] = [
                'job'         => $job,
                'score'       => min(100, $score),
                'reasons'     => $reasons,
                'match_level' => $score >= 70 ? 'high' : ($score >= 40 ? 'medium' : 'low'),
            ];
        }
    }

    usort($recommendations, fn($a, $b) => $b['score'] - $a['score']);
    return $recommendations;
}

$recommendations = recommendJobs($profile, $allJobs);

if (isset($_GET['apply'])) {
    $job_id = (int)$_GET['apply'];
    $check = $conn->query("SELECT id FROM applications WHERE job_id=$job_id AND student_id=$uid");
    if ($check->num_rows === 0) {
        $conn->query("INSERT INTO applications (job_id, student_id) VALUES ($job_id, $uid)");
    }
    header("Location: index.php?applied=1");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AI Job Recommendations</title>
<link rel="stylesheet" href="../../css/style.css">
<style>
.rec-card { background:#fff;border-radius:12px;padding:22px;box-shadow:0 2px 12px rgba(0,0,0,0.08);margin-bottom:20px;border-left:5px solid #3f51b5;transition:transform 0.2s,box-shadow 0.2s; }
.rec-card:hover { transform:translateY(-3px);box-shadow:0 6px 20px rgba(0,0,0,0.12); }
.rec-card.high   { border-left-color:#43a047; }
.rec-card.medium { border-left-color:#fb8c00; }
.rec-card.low    { border-left-color:#e53935; }
.match-badge { display:inline-block;padding:5px 14px;border-radius:20px;font-size:0.85rem;font-weight:700; }
.match-high   { background:#e8f5e9;color:#2e7d32; }
.match-medium { background:#fff8e1;color:#e65100; }
.match-low    { background:#ffebee;color:#c62828; }
.bar-bg { background:#e0e0e0;border-radius:10px;height:8px; }
.bar-fill { height:8px;border-radius:10px;transition:width 1s; }
.fill-high   { background:linear-gradient(90deg,#43a047,#66bb6a); }
.fill-medium { background:linear-gradient(90deg,#fb8c00,#ffa726); }
.fill-low    { background:linear-gradient(90deg,#e53935,#ef5350); }
.reason-tag { display:inline-block;padding:4px 10px;background:#f5f5f5;border-radius:5px;font-size:0.82rem;color:#555;margin:3px; }
.filter-btn { padding:7px 18px;border-radius:20px;border:2px solid #3f51b5;background:none;color:#3f51b5;font-weight:600;cursor:pointer;font-size:0.85rem;transition:all 0.2s; }
.filter-btn.active,.filter-btn:hover { background:#3f51b5;color:#fff; }
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
        <a href="index.php" class="active">🎯 AI Jobs</a>
        <a href="../notices.php">Notices</a>
        <a href="../logout.php" class="btn-logout">Logout</a>
    </div>
</nav>

<div class="container">
    <?php if (isset($_GET['applied'])): ?>
    <div class="alert alert-success">✅ Application submitted successfully!</div>
    <?php endif; ?>

    <?php if (!$profile['skills'] || !$profile['department']): ?>
    <div class="card" style="background:linear-gradient(135deg,#1a237e,#3949ab);color:#fff">
        <h3 style="color:#ffd54f;margin-bottom:8px">⚠️ Complete Your Profile for Better Recommendations</h3>
        <p style="color:#c5cae9;margin-bottom:15px">Add your skills, department, and CGPA to get personalized job recommendations.</p>
        <a href="../profile.php" class="btn" style="background:#ffd54f;color:#1a237e;border-radius:20px">Complete Profile →</a>
    </div>
    <?php else: ?>
    <div class="card" style="background:linear-gradient(135deg,#1a237e,#3949ab);color:#fff">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:15px">
            <div>
                <h3 style="color:#ffd54f;margin-bottom:8px">🎯 AI Job Recommendations for <?= htmlspecialchars($profile['name']) ?></h3>
                <div style="display:flex;gap:15px;flex-wrap:wrap;font-size:0.9rem;color:#c5cae9">
                    <span>🎓 <?= htmlspecialchars($profile['department']) ?></span>
                    <span>📊 CGPA: <?= $profile['cgpa'] ?></span>
                    <span>💡 <?= htmlspecialchars(substr($profile['skills'], 0, 60)) ?>...</span>
                </div>
            </div>
            <div style="text-align:center">
                <div style="font-size:2.5rem;font-weight:800;color:#ffd54f"><?= count($recommendations) ?></div>
                <div style="font-size:0.85rem;color:#c5cae9">Jobs Matched</div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- How it works -->
    <div class="card">
        <h2>🤖 How AI Recommendations Work</h2>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:15px;margin-top:10px">
            <?php foreach ([
                ['💡','Skills Match','Your skills matched with job requirements','40 pts'],
                ['🎓','Department','Jobs suited for your academic background','20 pts'],
                ['📊','CGPA Filter','Only shows jobs you are eligible for','15 pts'],
                ['💼','Job Type','Matches based on role and industry','25 pts'],
            ] as $item): ?>
            <div style="text-align:center;padding:15px;background:#f5f5f5;border-radius:8px">
                <div style="font-size:1.8rem"><?= $item[0] ?></div>
                <div style="font-weight:700;color:#1a237e;margin:5px 0"><?= $item[1] ?></div>
                <div style="font-size:0.82rem;color:#666"><?= $item[2] ?></div>
                <div style="font-size:0.78rem;color:#3f51b5;font-weight:700;margin-top:4px"><?= $item[3] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (!empty($recommendations)): ?>
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px;align-items:center">
        <span style="font-weight:600;color:#333">Filter:</span>
        <button class="filter-btn active" onclick="filterRec('all',this)">All (<?= count($recommendations) ?>)</button>
        <button class="filter-btn" onclick="filterRec('high',this)">🟢 High (<?= count(array_filter($recommendations, fn($r) => $r['match_level']==='high')) ?>)</button>
        <button class="filter-btn" onclick="filterRec('medium',this)">🟡 Medium (<?= count(array_filter($recommendations, fn($r) => $r['match_level']==='medium')) ?>)</button>
        <button class="filter-btn" onclick="filterRec('low',this)">🔴 Low (<?= count(array_filter($recommendations, fn($r) => $r['match_level']==='low')) ?>)</button>
    </div>

    <div id="rec-list">
        <?php foreach ($recommendations as $rec):
            $job = $rec['job']; $ml = $rec['match_level']; ?>
        <div class="rec-card <?= $ml ?>" data-level="<?= $ml ?>">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px">
                <div style="flex:1">
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:6px">
                        <h3 style="color:#1a237e;margin:0"><?= htmlspecialchars($job['title']) ?></h3>
                        <span class="match-badge match-<?= $ml ?>"><?= $ml==='high'?'🟢':($ml==='medium'?'🟡':'🔴') ?> <?= $rec['score'] ?>% Match</span>
                    </div>
                    <div style="color:#666;font-size:0.9rem;margin-bottom:10px">
                        🏢 <?= htmlspecialchars($job['company_name']) ?><?php if ($job['industry']): ?> · <?= htmlspecialchars($job['industry']) ?><?php endif; ?>
                    </div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px">
                        <span style="background:#f5f5f5;padding:3px 10px;border-radius:20px;font-size:0.8rem">📍 <?= htmlspecialchars($job['location'] ?? 'N/A') ?></span>
                        <span style="background:#f5f5f5;padding:3px 10px;border-radius:20px;font-size:0.8rem">💼 <?= $job['job_type'] ?></span>
                        <?php if ($job['salary_range']): ?><span style="background:#f5f5f5;padding:3px 10px;border-radius:20px;font-size:0.8rem">💰 <?= htmlspecialchars($job['salary_range']) ?></span><?php endif; ?>
                        <?php if ($job['min_cgpa'] > 0): ?><span style="background:#f5f5f5;padding:3px 10px;border-radius:20px;font-size:0.8rem">📊 Min CGPA: <?= $job['min_cgpa'] ?></span><?php endif; ?>
                    </div>
                    <div style="margin-bottom:10px">
                        <div style="display:flex;justify-content:space-between;font-size:0.82rem;color:#666;margin-bottom:4px"><span>Match Score</span><span><?= $rec['score'] ?>%</span></div>
                        <div class="bar-bg"><div class="bar-fill fill-<?= $ml ?>" style="width:<?= $rec['score'] ?>%"></div></div>
                    </div>
                    <div>
                        <div style="font-size:0.82rem;font-weight:600;color:#555;margin-bottom:5px">Why recommended:</div>
                        <?php foreach ($rec['reasons'] as $reason): ?>
                        <span class="reason-tag"><?= htmlspecialchars($reason) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div style="display:flex;flex-direction:column;gap:8px;min-width:120px">
                    <a href="?apply=<?= $job['id'] ?>" class="btn btn-success btn-sm" onclick="return confirm('Apply for this job?')">Apply Now</a>
                    <a href="../jobs.php" class="btn btn-warning btn-sm">View All Jobs</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="card" style="text-align:center;padding:40px">
        <div style="font-size:3rem;margin-bottom:15px">🔍</div>
        <h3 style="color:#1a237e;margin-bottom:10px">No Recommendations Yet</h3>
        <p style="color:#666;margin-bottom:20px">
            <?= !$profile['skills'] ? 'Please <a href="../profile.php">add your skills</a> to get personalized recommendations.' : 'No open jobs match your profile right now. Check back later.' ?>
        </p>
        <a href="../jobs.php" class="btn btn-primary">Browse All Jobs</a>
    </div>
    <?php endif; ?>
</div>

<script>
function filterRec(level, btn) {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.rec-card').forEach(card => {
        card.style.display = (level === 'all' || card.dataset.level === level) ? 'block' : 'none';
    });
}
</script>
</body>
</html>
