<?php
require_once '../../includes/config.php';
requireLogin('student');

$uid = $_SESSION['user_id'];
$profile = $conn->query("SELECT sp.*, u.name FROM student_profiles sp JOIN users u ON sp.user_id=u.id WHERE sp.user_id=$uid")->fetch_assoc();

// ── Skills & Job Role Database ────────────────────────────────────────────────
$jobRoles = [
    'Software Developer'        => ['php','python','java','javascript','c++','git','mysql','html','css','object oriented','data structures','algorithms'],
    'Web Developer'             => ['html','css','javascript','php','react','angular','bootstrap','mysql','git','node','rest api'],
    'Full Stack Developer'      => ['html','css','javascript','php','react','node','mysql','git','rest api','bootstrap','python'],
    'Data Scientist'            => ['python','r','machine learning','deep learning','tensorflow','sql','data science','pandas','statistics','tableau'],
    'Mobile Developer'          => ['java','kotlin','swift','flutter','dart','firebase','git','android','rest api'],
    'DevOps Engineer'           => ['docker','kubernetes','jenkins','aws','linux','git','python','bash','devops','azure'],
    'Machine Learning Engineer' => ['python','tensorflow','pytorch','keras','machine learning','deep learning','nlp','data science','sql','git'],
    'Cloud Engineer'            => ['aws','azure','gcp','docker','kubernetes','linux','python','cloud computing','terraform','git'],
    'Database Administrator'    => ['mysql','postgresql','oracle','mongodb','sql','redis','backup','performance','linux'],
    'UI/UX Designer'            => ['figma','photoshop','css','html','bootstrap','javascript','user interface','adobe','prototyping'],
    'Cybersecurity Analyst'     => ['cybersecurity','networking','linux','python','ethical hacking','firewall','encryption','git'],
    'Business Analyst'          => ['excel','tableau','power bi','sql','communication','presentation','agile','jira','data analysis'],
    'Android Developer'         => ['java','kotlin','android','firebase','git','rest api','sqlite','xml','gradle'],
    'Data Analyst'              => ['python','sql','excel','tableau','power bi','statistics','data analysis','mysql','r'],
];

// Course recommendations per skill
$courses = [
    'python'           => ['Python for Everybody — Coursera','Automate the Boring Stuff — Udemy','Python Bootcamp — Udemy'],
    'java'             => ['Java Masterclass — Udemy','Core Java — NPTEL','Java Programming — Coursera'],
    'javascript'       => ['JavaScript: The Complete Guide — Udemy','JS30 — Free','The Odin Project — Free'],
    'php'              => ['PHP & MySQL Bootcamp — Udemy','PHP Tutorial — W3Schools','Laravel from Scratch — Laracasts'],
    'react'            => ['React — The Complete Guide — Udemy','React Docs — Free','Full Stack Open — Free'],
    'node'             => ['Node.js Complete Guide — Udemy','NodeSchool — Free','Full Stack Open — Free'],
    'mysql'            => ['MySQL Bootcamp — Udemy','SQL for Data Science — Coursera','NPTEL DBMS — Free'],
    'sql'              => ['SQL for Beginners — Udemy','Mode SQL Tutorial — Free','W3Schools SQL — Free'],
    'git'              => ['Git & GitHub Crash Course — Udemy','Pro Git Book — Free','GitHub Learning Lab — Free'],
    'docker'           => ['Docker & Kubernetes — Udemy','Play with Docker — Free','Docker Docs — Free'],
    'aws'              => ['AWS Certified Cloud Practitioner — Udemy','AWS Free Tier — Free','A Cloud Guru — Paid'],
    'machine learning' => ['ML by Andrew Ng — Coursera','Hands-On ML — Book','Fast.ai — Free'],
    'deep learning'    => ['Deep Learning Specialization — Coursera','Fast.ai — Free','PyTorch Tutorials — Free'],
    'tensorflow'       => ['TensorFlow Developer Certificate — Coursera','TF Tutorials — Free','Udemy TF Course'],
    'data science'     => ['IBM Data Science — Coursera','Kaggle Learn — Free','Data Science Bootcamp — Udemy'],
    'html'             => ['HTML & CSS — freeCodeCamp Free','The Odin Project — Free','W3Schools — Free'],
    'css'              => ['CSS Complete Guide — Udemy','CSS Tricks — Free','freeCodeCamp — Free'],
    'linux'            => ['Linux Command Line — Udemy','Linux Foundation — Free','OverTheWire — Free'],
    'kubernetes'       => ['Kubernetes for Beginners — Udemy','K8s Docs — Free','KodeKloud — Paid'],
    'flutter'          => ['Flutter & Dart — Udemy','Flutter Docs — Free','Flutter Codelabs — Free'],
    'kotlin'           => ['Kotlin Bootcamp — Udacity Free','JetBrains Academy — Paid','Android Kotlin — Google Free'],
    'tableau'          => ['Tableau Training — Udemy','Tableau Public — Free','Tableau eLearning — Free'],
    'power bi'         => ['Power BI — Microsoft Learn Free','Power BI Udemy','Guy in a Cube — YouTube Free'],
    'excel'            => ['Excel from Beginner to Advanced — Udemy','Excel Easy — Free','GCFGlobal — Free'],
    'figma'            => ['Figma UI Design — Udemy','Figma Tutorials — Free','DesignCourse — YouTube Free'],
    'cybersecurity'    => ['Google Cybersecurity Certificate — Coursera','TryHackMe — Free/Paid','CompTIA Security+ — Udemy'],
    'networking'       => ['Computer Networking — Coursera','Cisco NetAcad — Free','Professor Messer — Free'],
    'agile'            => ['Agile Fundamentals — Coursera','Scrum.org — Free','PMI Agile — Paid'],
    'data structures'  => ['DSA — NPTEL Free','Algorithms — Coursera','LeetCode — Free'],
    'algorithms'       => ['Algorithms Specialization — Coursera','CLRS Book','LeetCode — Free'],
    'object oriented'  => ['OOP Concepts — Udemy','Head First OOP — Book','NPTEL OOP — Free'],
    'rest api'         => ['REST API Design — Udemy','Postman Learning — Free','REST API Tutorial — Free'],
    'firebase'         => ['Firebase Bootcamp — Udemy','Firebase Docs — Free','Fireship.io — Free/Paid'],
    'mongodb'          => ['MongoDB University — Free','MongoDB Bootcamp — Udemy','Mongoose Docs — Free'],
    'r'                => ['R Programming — Coursera','R for Data Science — Free Book','DataCamp R — Paid'],
    'angular'          => ['Angular Complete Guide — Udemy','Angular Docs — Free','Tour of Heroes — Free'],
    'bootstrap'        => ['Bootstrap 5 — Udemy','Bootstrap Docs — Free','W3Schools Bootstrap — Free'],
    'azure'            => ['AZ-900 — Microsoft Learn Free','Azure Fundamentals — Udemy','A Cloud Guru — Paid'],
    'gcp'              => ['Google Cloud Fundamentals — Coursera','GCP Free Tier — Free','Qwiklabs — Free/Paid'],
];

// ── Parse student skills ──────────────────────────────────────────────────────
$studentSkills = array_map('strtolower', array_map('trim', array_filter(explode(',', $profile['skills'] ?? ''))));

// ── Selected role (default: best match) ──────────────────────────────────────
$selectedRole = $_GET['role'] ?? '';

// Auto-detect best matching role if none selected
if (!$selectedRole) {
    $bestMatch = 0; $bestRole = array_key_first($jobRoles);
    foreach ($jobRoles as $role => $required) {
        $matched = count(array_intersect($studentSkills, array_map('strtolower', $required)));
        if ($matched > $bestMatch) { $bestMatch = $matched; $bestRole = $role; }
    }
    $selectedRole = $bestRole;
}

// ── Compute gap for selected role ─────────────────────────────────────────────
function computeGap($studentSkills, $required) {
    $req = array_map('strtolower', $required);
    $have    = array_values(array_intersect($req, $studentSkills));
    $missing = array_values(array_diff($req, $studentSkills));
    $pct     = count($req) > 0 ? round((count($have) / count($req)) * 100) : 0;
    return ['have'=>$have, 'missing'=>$missing, 'pct'=>$pct, 'total'=>count($req)];
}

$gap = computeGap($studentSkills, $jobRoles[$selectedRole] ?? []);

// ── All roles match summary ───────────────────────────────────────────────────
$allGaps = [];
foreach ($jobRoles as $role => $required) {
    $g = computeGap($studentSkills, $required);
    $allGaps[$role] = $g;
}
uasort($allGaps, fn($a,$b) => $b['pct'] - $a['pct']);

// ── Course suggestions for missing skills ─────────────────────────────────────
$suggestedCourses = [];
foreach ($gap['missing'] as $skill) {
    $key = strtolower($skill);
    if (isset($courses[$key])) {
        $suggestedCourses[$skill] = $courses[$key];
    } else {
        $suggestedCourses[$skill] = ['Search on Coursera — coursera.org', 'Search on Udemy — udemy.com', 'Search on NPTEL — nptel.ac.in'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Skill Gap Analysis</title>
<link rel="stylesheet" href="../../css/style.css">
<style>
.skill-tag { display:inline-block;padding:5px 13px;border-radius:20px;font-size:0.82rem;font-weight:600;margin:3px; }
.skill-have    { background:#e8f5e9;color:#2e7d32;border:1px solid #a5d6a7; }
.skill-missing { background:#ffebee;color:#c62828;border:1px solid #ef9a9a; }
.role-card { border-radius:10px;padding:14px 16px;margin-bottom:10px;border:2px solid #e0e0e0;cursor:pointer;transition:all 0.2s;display:flex;justify-content:space-between;align-items:center;text-decoration:none;color:inherit; }
.role-card:hover { border-color:#3f51b5;background:#f5f5ff; }
.role-card.active { border-color:#3f51b5;background:#e8eaf6; }
.match-bar-bg { flex:1;height:8px;background:#e0e0e0;border-radius:4px;margin:0 12px; }
.match-bar-fill { height:8px;border-radius:4px;transition:width 1s ease; }
.course-card { background:#f8f9ff;border-radius:8px;padding:14px;margin-bottom:10px;border-left:4px solid #3f51b5; }
.course-item { display:flex;align-items:center;gap:8px;padding:6px 0;font-size:0.88rem;color:#444;border-bottom:1px solid #eee; }
.course-item:last-child { border-bottom:none; }
.gap-circle { width:110px;height:110px;border-radius:50%;display:flex;flex-direction:column;align-items:center;justify-content:center;margin:0 auto 10px;font-weight:800; }
.priority-high   { background:#ffebee;color:#c62828;border:3px solid #ef9a9a; }
.priority-medium { background:#fff8e1;color:#e65100;border:3px solid #ffcc80; }
.priority-low    { background:#e8f5e9;color:#2e7d32;border:3px solid #a5d6a7; }
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
        <a href="index.php" class="active">🧩 Skill Gap</a>
        <a href="../notices.php">Notices</a>
        <a href="../logout.php" class="btn-logout">Logout</a>
    </div>
</nav>

<div class="container">

    <!-- Header -->
    <div class="card" style="background:linear-gradient(135deg,#004d40,#00695c);color:#fff;margin-bottom:25px">
        <h2 style="color:#ffd54f;margin-bottom:8px">🧩 Skill Gap Analysis</h2>
        <p style="color:#b2dfdb">Compare your current skills against job role requirements. Get personalized course recommendations to bridge the gap.</p>
    </div>

    <?php if (empty($studentSkills)): ?>
    <div class="card" style="text-align:center;padding:40px">
        <div style="font-size:3rem;margin-bottom:15px">⚠️</div>
        <h3 style="color:#1a237e">No Skills Found in Your Profile</h3>
        <p style="color:#666;margin-bottom:20px">Please add your skills to your profile to get a skill gap analysis.</p>
        <a href="../profile.php" class="btn btn-primary">Update Profile →</a>
    </div>
    <?php else: ?>

    <div style="display:grid;grid-template-columns:1fr 2fr;gap:20px">

        <!-- Left: Role Selector -->
        <div>
            <div class="card" style="padding:15px">
                <h2 style="font-size:1rem;margin-bottom:12px">🎯 Select Target Role</h2>
                <?php foreach ($allGaps as $role => $g):
                    $barColor = $g['pct'] >= 70 ? '#43a047' : ($g['pct'] >= 40 ? '#fb8c00' : '#e53935');
                ?>
                <a href="?role=<?= urlencode($role) ?>" class="role-card <?= $role===$selectedRole?'active':'' ?>">
                    <div style="font-size:0.88rem;font-weight:700;color:#1a237e;min-width:140px"><?= htmlspecialchars($role) ?></div>
                    <div class="match-bar-bg">
                        <div class="match-bar-fill" style="width:<?= $g['pct'] ?>%;background:<?= $barColor ?>"></div>
                    </div>
                    <div style="font-size:0.82rem;font-weight:800;color:<?= $barColor ?>;min-width:35px;text-align:right"><?= $g['pct'] ?>%</div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Right: Gap Detail -->
        <div>
            <!-- Summary -->
            <div class="card">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:15px;margin-bottom:20px">
                    <div>
                        <h2 style="margin:0;color:#1a237e"><?= htmlspecialchars($selectedRole) ?></h2>
                        <p style="color:#666;font-size:0.9rem;margin-top:4px">
                            <?= count($gap['have']) ?> of <?= $gap['total'] ?> required skills matched
                        </p>
                    </div>
                    <div style="text-align:center">
                        <?php
                        $pct = $gap['pct'];
                        $circleClass = $pct >= 70 ? 'priority-low' : ($pct >= 40 ? 'priority-medium' : 'priority-high');
                        ?>
                        <div class="gap-circle <?= $circleClass ?>">
                            <span style="font-size:1.8rem"><?= $pct ?>%</span>
                            <span style="font-size:0.72rem;font-weight:600">Match</span>
                        </div>
                        <div style="font-size:0.82rem;color:#666"><?= $pct>=70?'Strong Match':($pct>=40?'Partial Match':'Weak Match') ?></div>
                    </div>
                </div>

                <!-- Progress bar -->
                <div style="margin-bottom:20px">
                    <div style="display:flex;justify-content:space-between;font-size:0.85rem;color:#555;margin-bottom:6px">
                        <span>Skill Coverage</span>
                        <span><?= count($gap['have']) ?>/<?= $gap['total'] ?> skills</span>
                    </div>
                    <div style="background:#e0e0e0;border-radius:8px;height:14px">
                        <div style="height:14px;border-radius:8px;width:<?= $pct ?>%;background:<?= $pct>=70?'linear-gradient(90deg,#43a047,#66bb6a)':($pct>=40?'linear-gradient(90deg,#fb8c00,#ffa726)':'linear-gradient(90deg,#e53935,#ef5350)') ?>;transition:width 1s"></div>
                    </div>
                </div>

                <!-- Skills you have -->
                <?php if (!empty($gap['have'])): ?>
                <div style="margin-bottom:18px">
                    <div style="font-weight:700;color:#2e7d32;margin-bottom:8px;font-size:0.95rem">✅ Skills You Have (<?= count($gap['have']) ?>)</div>
                    <?php foreach ($gap['have'] as $s): ?>
                    <span class="skill-tag skill-have"><?= htmlspecialchars($s) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Skills you're missing -->
                <?php if (!empty($gap['missing'])): ?>
                <div>
                    <div style="font-weight:700;color:#c62828;margin-bottom:8px;font-size:0.95rem">❌ Missing Skills (<?= count($gap['missing']) ?>)</div>
                    <?php foreach ($gap['missing'] as $s): ?>
                    <span class="skill-tag skill-missing"><?= htmlspecialchars($s) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div style="background:#e8f5e9;border-radius:8px;padding:15px;text-align:center;color:#2e7d32;font-weight:700">
                    🎉 You have all required skills for this role!
                </div>
                <?php endif; ?>
            </div>

            <!-- Course Recommendations -->
            <?php if (!empty($suggestedCourses)): ?>
            <div class="card">
                <h2>📚 Recommended Courses to Bridge the Gap</h2>
                <p style="color:#666;font-size:0.9rem;margin-bottom:15px">Learn these skills to become eligible for <strong><?= htmlspecialchars($selectedRole) ?></strong> roles.</p>

                <?php
                $priority = count($gap['missing']);
                $i = 0;
                foreach ($suggestedCourses as $skill => $courseList):
                    $i++;
                    $priorityLabel = $i <= 3 ? 'High Priority' : ($i <= 6 ? 'Medium Priority' : 'Good to Have');
                    $priorityColor = $i <= 3 ? '#c62828' : ($i <= 6 ? '#e65100' : '#1565c0');
                    $priorityBg    = $i <= 3 ? '#ffebee' : ($i <= 6 ? '#fff8e1' : '#e3f2fd');
                ?>
                <div class="course-card">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                        <div style="font-weight:800;color:#1a237e;font-size:1rem">💡 <?= htmlspecialchars(ucwords($skill)) ?></div>
                        <span style="background:<?= $priorityBg ?>;color:<?= $priorityColor ?>;padding:3px 10px;border-radius:12px;font-size:0.75rem;font-weight:700"><?= $priorityLabel ?></span>
                    </div>
                    <?php foreach ($courseList as $course): ?>
                    <div class="course-item">
                        <span style="color:#3f51b5;font-size:1rem">🎓</span>
                        <span><?= htmlspecialchars($course) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>

                <!-- Learning Resources -->
                <div style="margin-top:15px;padding:15px;background:#f5f5f5;border-radius:8px">
                    <div style="font-weight:700;color:#1a237e;margin-bottom:10px">🌐 Top Learning Platforms</div>
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px">
                        <?php foreach ([
                            ['🎓','Coursera','coursera.org','Free/Paid'],
                            ['🎓','Udemy','udemy.com','Paid'],
                            ['🎓','NPTEL','nptel.ac.in','Free'],
                            ['💻','freeCodeCamp','freecodecamp.org','Free'],
                            ['🏆','Kaggle','kaggle.com','Free'],
                            ['💡','YouTube','youtube.com','Free'],
                        ] as $p): ?>
                        <a href="https://<?= $p[2] ?>" target="_blank" style="text-decoration:none;background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:10px;text-align:center;transition:all 0.2s;display:block" onmouseover="this.style.borderColor='#3f51b5'" onmouseout="this.style.borderColor='#e0e0e0'">
                            <div style="font-size:1.4rem"><?= $p[0] ?></div>
                            <div style="font-weight:700;font-size:0.85rem;color:#1a237e"><?= $p[1] ?></div>
                            <div style="font-size:0.72rem;color:#666"><?= $p[3] ?></div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Improvement Report -->
            <div class="card">
                <h2>📊 Your Skill Gap Report</h2>
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:15px;margin-bottom:15px">
                    <div style="text-align:center;background:#e8f5e9;border-radius:10px;padding:15px">
                        <div style="font-size:2rem;font-weight:800;color:#2e7d32"><?= count($studentSkills) ?></div>
                        <div style="font-size:0.82rem;color:#555">Your Total Skills</div>
                    </div>
                    <div style="text-align:center;background:#e8eaf6;border-radius:10px;padding:15px">
                        <div style="font-size:2rem;font-weight:800;color:#1a237e"><?= count($gap['have']) ?></div>
                        <div style="font-size:0.82rem;color:#555">Matched for Role</div>
                    </div>
                    <div style="text-align:center;background:#ffebee;border-radius:10px;padding:15px">
                        <div style="font-size:2rem;font-weight:800;color:#c62828"><?= count($gap['missing']) ?></div>
                        <div style="font-size:0.82rem;color:#555">Skills to Learn</div>
                    </div>
                </div>
                <div style="background:#fff8e1;border-radius:8px;padding:14px;border-left:4px solid #fb8c00">
                    <strong style="color:#e65100">💡 Action Plan:</strong>
                    <ul style="margin-top:8px;padding-left:20px;color:#555;font-size:0.9rem;line-height:2">
                        <?php if (count($gap['missing']) > 0): ?>
                        <li>Focus on learning the top <?= min(3, count($gap['missing'])) ?> missing skills first: <strong><?= implode(', ', array_slice($gap['missing'], 0, 3)) ?></strong></li>
                        <li>Dedicate 1-2 hours daily to online courses</li>
                        <li>Build a project using each new skill you learn</li>
                        <li>Add learned skills to your profile and resume</li>
                        <?php else: ?>
                        <li>You have all required skills! Focus on deepening your expertise.</li>
                        <li>Build portfolio projects to demonstrate your skills.</li>
                        <li>Apply to <?= htmlspecialchars($selectedRole) ?> positions now!</li>
                        <?php endif; ?>
                        <li>Re-run this analysis after adding new skills to track progress</li>
                    </ul>
                </div>
                <div style="margin-top:15px;display:flex;gap:10px;flex-wrap:wrap">
                    <a href="../profile.php" class="btn btn-primary">Update My Skills</a>
                    <a href="../jobs.php" class="btn btn-success">Browse <?= htmlspecialchars($selectedRole) ?> Jobs</a>
                    <a href="../resume_analyzer/index.php" class="btn btn-warning">Analyze Resume</a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
