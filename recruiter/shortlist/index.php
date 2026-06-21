<?php
require_once '../../includes/config.php';
requireLogin('recruiter');
require_once '../../includes/notify.php';

$uid = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if ($uid === 0) { header('Location: ../../index.php'); exit(); }
$stCo = $conn->prepare("SELECT * FROM companies WHERE user_id=?");
$stCo->bind_param('i', $uid); $stCo->execute();
$company = $stCo->get_result()->fetch_assoc(); $stCo->close();
$cid = (int)($company['id'] ?? 0);

$msg = '';
$shortlistResult = null;

// ── Run Auto-Shortlist ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_shortlist'])) {
    $job_id      = (int)$_POST['job_id'];
    $min_cgpa    = (float)$_POST['min_cgpa'];
    $req_skills  = array_filter(array_map('strtolower', array_map('trim', explode(',', $_POST['required_skills'] ?? ''))));
    $min_skills  = (int)($_POST['min_skills_match'] ?? 1);
    $dept_filter = array_filter(array_map('trim', explode(',', $_POST['departments'] ?? '')));
    $resume_min  = (int)($_POST['min_resume_score'] ?? 0);
    $test_min    = (int)($_POST['min_test_score'] ?? 0);

    // Verify job belongs to this company
    $stJob = $conn->prepare("SELECT * FROM jobs WHERE id=? AND company_id=?");
    $stJob->bind_param('ii', $job_id, $cid); $stJob->execute();
    $job = $stJob->get_result()->fetch_assoc(); $stJob->close();
    if (!$job) { $msg = '<div class="alert alert-error">Invalid job.</div>'; }
    else {
        $stCand = $conn->prepare("SELECT a.id as app_id, a.student_id, u.name, u.email, sp.cgpa, sp.skills, sp.department FROM applications a JOIN users u ON a.student_id=u.id LEFT JOIN student_profiles sp ON sp.user_id=u.id WHERE a.job_id=? AND a.status='applied'");
        $stCand->bind_param('i', $job_id); $stCand->execute();
        $candidates = $stCand->get_result(); $stCand->close();

        $shortlisted = []; $rejected = []; $skipped = [];

        while ($c = $candidates->fetch_assoc()) {
            $reasons  = [];
            $failReasons = [];
            $pass = true;

            // 1. CGPA check
            $cgpa = (float)($c['cgpa'] ?? 0);
            if ($min_cgpa > 0) {
                if ($cgpa >= $min_cgpa) $reasons[] = "✅ CGPA $cgpa ≥ $min_cgpa";
                else { $failReasons[] = "❌ CGPA $cgpa < $min_cgpa"; $pass = false; }
            }

            // 2. Skills check
            if (!empty($req_skills)) {
                $studentSkills = array_map('strtolower', array_map('trim', explode(',', $c['skills'] ?? '')));
                $matched = array_intersect($req_skills, $studentSkills);
                $matchCount = count($matched);
                if ($matchCount >= $min_skills) {
                    $reasons[] = "✅ Skills matched: " . implode(', ', array_slice($matched, 0, 3)) . ($matchCount > 3 ? '...' : '');
                } else {
                    $failReasons[] = "❌ Only $matchCount/" . count($req_skills) . " skills matched (need $min_skills)";
                    $pass = false;
                }
            }

            // 3. Department filter
            if (!empty($dept_filter)) {
                $deptMatch = false;
                foreach ($dept_filter as $d) {
                    if (stripos($c['department'] ?? '', $d) !== false) { $deptMatch = true; break; }
                }
                if ($deptMatch) $reasons[] = "✅ Department: " . $c['department'];
                else { $failReasons[] = "❌ Department not in filter"; $pass = false; }
            }

            // 4. Resume score check
            if ($resume_min > 0) {
                $stRS = $conn->prepare("SELECT score FROM resume_analysis WHERE user_id=? ORDER BY analyzed_at DESC LIMIT 1");
                $stRS->bind_param('i', $c['student_id']); $stRS->execute();
                $rs = $stRS->get_result()->fetch_assoc(); $stRS->close();
                $rscore = (int)($rs['score'] ?? 0);
                if ($rscore >= $resume_min) $reasons[] = "✅ Resume score $rscore ≥ $resume_min";
                else { $failReasons[] = "❌ Resume score $rscore < $resume_min"; $pass = false; }
            }

            // 5. Test score check
            if ($test_min > 0) {
                $stTS = $conn->prepare("SELECT AVG(score/total_marks*100) as avg FROM test_attempts WHERE student_id=? AND status='completed' AND total_marks>0");
                $stTS->bind_param('i', $c['student_id']); $stTS->execute();
                $ts = $stTS->get_result()->fetch_assoc(); $stTS->close();
                $tavg = round((float)($ts['avg'] ?? 0));
                if ($tavg >= $test_min) $reasons[] = "✅ Test avg $tavg% ≥ $test_min%";
                else { $failReasons[] = "❌ Test avg $tavg% < $test_min%"; $pass = false; }
            }

            if ($pass) {
                $stUpSL = $conn->prepare("UPDATE applications SET status='shortlisted' WHERE id=?");
                $stUpSL->bind_param('i', $c['app_id']); $stUpSL->execute(); $stUpSL->close();
                notifyApplicationStatus($conn, $c['student_id'], 'shortlisted', $job['title'], $company['company_name']);
                $shortlisted[] = array_merge($c, ['reasons' => $reasons]);
            } else {
                $skipped[] = array_merge($c, ['reasons' => $failReasons]);
            }
        }

        $shortlistResult = [
            'job'        => $job,
            'shortlisted'=> $shortlisted,
            'skipped'    => $skipped,
            'criteria'   => [
                'min_cgpa'    => $min_cgpa,
                'req_skills'  => $req_skills,
                'min_skills'  => $min_skills,
                'departments' => $dept_filter,
                'resume_min'  => $resume_min,
                'test_min'    => $test_min,
            ],
        ];

        $count = count($shortlisted);
        $msg = "<div class='alert alert-success'>✅ Auto-shortlisting complete! <strong>$count candidate(s)</strong> shortlisted. Notifications sent.</div>";
    }
}

// Get all jobs with applicant counts
$stJobs = $conn->prepare("SELECT j.*, (SELECT COUNT(*) FROM applications WHERE job_id=j.id AND status='applied') as applied_count, (SELECT COUNT(*) FROM applications WHERE job_id=j.id AND status='shortlisted') as shortlisted_count FROM jobs j WHERE j.company_id=? AND j.status='open' ORDER BY j.created_at DESC");
$stJobs->bind_param('i', $cid); $stJobs->execute();
$jobs = $stJobs->get_result(); $stJobs->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Auto Shortlisting - Recruiter</title>
<link rel="stylesheet" href="../../css/style.css">
<style>
.candidate-row { display:flex;justify-content:space-between;align-items:flex-start;padding:12px 15px;border-radius:8px;margin-bottom:8px;flex-wrap:wrap;gap:10px; }
.candidate-pass { background:#e8f5e9;border-left:4px solid #43a047; }
.candidate-fail { background:#ffebee;border-left:4px solid #e53935; }
.reason-tag { display:inline-block;padding:3px 9px;border-radius:10px;font-size:0.78rem;font-weight:600;margin:2px; }
.reason-pass { background:#c8e6c9;color:#1b5e20; }
.reason-fail { background:#ffcdd2;color:#b71c1c; }
.criteria-section { background:#f8f9ff;border-radius:10px;padding:20px;border:2px solid #e8eaf6;margin-bottom:20px; }
</style>
</head>
<body>
<nav class="navbar">
    <a href="../dashboard.php" class="brand">🎓 Campus<span>Recruit</span></a>
    <div class="nav-links">
        <a href="../dashboard.php">Dashboard</a>
        <a href="../post_job.php">Post Job</a>
        <a href="../jobs.php">My Jobs</a>
        <a href="../applications.php">Applications</a>
        <a href="../interviews/index.php">🎥 Interviews</a>
        <a href="../analytics/index.php">📊 Analytics</a>
        <a href="index.php" class="active">⚡ Auto Shortlist</a>
        <a href="../profile.php">Profile</a>
        <?php require_once '../../notifications/widget.php'; ?>
        <a href="../logout.php" class="btn-logout">Logout</a>
    </div>
</nav>

<div class="container">
    <?= $msg ?>

    <div class="card" style="background:linear-gradient(135deg,#e65100,#f57c00);color:#fff;margin-bottom:25px">
        <h2 style="color:#fff3e0;margin-bottom:8px">⚡ Automated Shortlisting System</h2>
        <p style="color:#ffe0b2">Set criteria and instantly shortlist matching candidates. Notifications are sent automatically.</p>
    </div>

    <!-- Criteria Form -->
    <div class="card">
        <h2>🎯 Set Shortlisting Criteria</h2>
        <form method="POST">
            <input type="hidden" name="run_shortlist" value="1">

            <div class="form-group">
                <label>Select Job *</label>
                <select name="job_id" required onchange="updateJobInfo(this)">
                    <option value="">-- Select a Job --</option>
                    <?php $jobs->data_seek(0); while($j = $jobs->fetch_assoc()): ?>
                    <option value="<?= $j['id'] ?>"
                        data-cgpa="<?= $j['min_cgpa'] ?>"
                        data-applied="<?= $j['applied_count'] ?>"
                        data-shortlisted="<?= $j['shortlisted_count'] ?>"
                        data-req="<?= htmlspecialchars($j['requirements'] ?? '') ?>">
                        <?= htmlspecialchars($j['title']) ?>
                        (<?= $j['applied_count'] ?> applied, <?= $j['shortlisted_count'] ?> shortlisted)
                    </option>
                    <?php endwhile; ?>
                </select>
                <div id="job-info" style="margin-top:8px;padding:10px;background:#e8eaf6;border-radius:6px;font-size:0.85rem;display:none"></div>
            </div>

            <div class="criteria-section">
                <div style="font-weight:700;color:#1a237e;margin-bottom:15px;font-size:1rem">📋 Shortlisting Criteria</div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Minimum CGPA <small style="color:#999">(0 = no filter)</small></label>
                        <input type="number" name="min_cgpa" id="min-cgpa" step="0.1" min="0" max="10" value="0" placeholder="e.g. 7.0">
                    </div>
                    <div class="form-group">
                        <label>Minimum Skills to Match <small style="color:#999">(out of required)</small></label>
                        <input type="number" name="min_skills_match" min="1" max="20" value="2" placeholder="e.g. 3">
                    </div>
                </div>
                <div class="form-group">
                    <label>Required Skills <small style="color:#999">(comma separated)</small></label>
                    <input type="text" name="required_skills" id="req-skills" placeholder="e.g. python, mysql, javascript, git">
                    <small style="color:#666">Students must match at least the minimum number of these skills.</small>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Allowed Departments <small style="color:#999">(leave empty for all)</small></label>
                        <input type="text" name="departments" placeholder="e.g. Computer Science, Information Technology">
                    </div>
                    <div class="form-group">
                        <label>Minimum Resume Score <small style="color:#999">(0 = no filter)</small></label>
                        <input type="number" name="min_resume_score" min="0" max="100" value="0" placeholder="e.g. 50">
                    </div>
                </div>
                <div class="form-group">
                    <label>Minimum Test Score % <small style="color:#999">(0 = no filter)</small></label>
                    <input type="number" name="min_test_score" min="0" max="100" value="0" placeholder="e.g. 60">
                </div>
            </div>

            <div style="background:#fff8e1;border-radius:8px;padding:12px;margin-bottom:15px;font-size:0.88rem;color:#e65100;border-left:4px solid #fb8c00">
                ⚠️ <strong>Note:</strong> This will update all matching <strong>Applied</strong> candidates to <strong>Shortlisted</strong> status and send them notifications. This action cannot be undone automatically.
            </div>

            <button type="submit" class="btn btn-primary" style="background:linear-gradient(135deg,#e65100,#f57c00);border:none;padding:12px 30px">
                ⚡ Run Auto Shortlisting
            </button>
        </form>
    </div>

    <!-- Results -->
    <?php if ($shortlistResult): ?>
    <div class="card">
        <h2>📊 Shortlisting Results — <?= htmlspecialchars($shortlistResult['job']['title']) ?></h2>

        <!-- Summary -->
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:15px;margin-bottom:20px">
            <div style="text-align:center;background:#e8f5e9;border-radius:10px;padding:15px">
                <div style="font-size:2rem;font-weight:800;color:#2e7d32"><?= count($shortlistResult['shortlisted']) ?></div>
                <div style="font-size:0.85rem;color:#555">✅ Shortlisted</div>
            </div>
            <div style="text-align:center;background:#ffebee;border-radius:10px;padding:15px">
                <div style="font-size:2rem;font-weight:800;color:#c62828"><?= count($shortlistResult['skipped']) ?></div>
                <div style="font-size:0.85rem;color:#555">❌ Not Qualified</div>
            </div>
            <div style="text-align:center;background:#e8eaf6;border-radius:10px;padding:15px">
                <div style="font-size:2rem;font-weight:800;color:#1a237e"><?= count($shortlistResult['shortlisted']) + count($shortlistResult['skipped']) ?></div>
                <div style="font-size:0.85rem;color:#555">📋 Total Evaluated</div>
            </div>
        </div>

        <!-- Criteria used -->
        <div style="background:#f5f5f5;border-radius:8px;padding:12px;margin-bottom:20px;font-size:0.85rem">
            <strong>Criteria Applied:</strong>
            <?php $c = $shortlistResult['criteria']; ?>
            <?php if ($c['min_cgpa'] > 0): ?><span style="background:#e8eaf6;padding:2px 8px;border-radius:10px;margin:2px;display:inline-block">CGPA ≥ <?= $c['min_cgpa'] ?></span><?php endif; ?>
            <?php if (!empty($c['req_skills'])): ?><span style="background:#e8eaf6;padding:2px 8px;border-radius:10px;margin:2px;display:inline-block">Skills: <?= implode(', ', array_slice($c['req_skills'],0,4)) ?> (min <?= $c['min_skills'] ?>)</span><?php endif; ?>
            <?php if (!empty($c['departments'])): ?><span style="background:#e8eaf6;padding:2px 8px;border-radius:10px;margin:2px;display:inline-block">Dept: <?= implode(', ', $c['departments']) ?></span><?php endif; ?>
            <?php if ($c['resume_min'] > 0): ?><span style="background:#e8eaf6;padding:2px 8px;border-radius:10px;margin:2px;display:inline-block">Resume ≥ <?= $c['resume_min'] ?></span><?php endif; ?>
            <?php if ($c['test_min'] > 0): ?><span style="background:#e8eaf6;padding:2px 8px;border-radius:10px;margin:2px;display:inline-block">Test ≥ <?= $c['test_min'] ?>%</span><?php endif; ?>
        </div>

        <!-- Shortlisted candidates -->
        <?php if (!empty($shortlistResult['shortlisted'])): ?>
        <h3 style="color:#2e7d32;margin-bottom:12px">✅ Shortlisted Candidates (<?= count($shortlistResult['shortlisted']) ?>)</h3>
        <?php foreach ($shortlistResult['shortlisted'] as $c): ?>
        <div class="candidate-row candidate-pass">
            <div>
                <strong style="color:#1a237e"><?= htmlspecialchars($c['name']) ?></strong>
                <span style="color:#666;font-size:0.85rem;margin-left:8px"><?= htmlspecialchars($c['email']) ?></span>
                <div style="font-size:0.82rem;color:#555;margin-top:3px">
                    🎓 <?= htmlspecialchars($c['department'] ?? 'N/A') ?> · 📊 CGPA: <?= $c['cgpa'] ?: 'N/A' ?>
                </div>
                <div style="margin-top:6px">
                    <?php foreach ($c['reasons'] as $r): ?>
                    <span class="reason-tag reason-pass"><?= htmlspecialchars($r) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <span class="badge badge-shortlisted">⭐ Shortlisted</span>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <!-- Not qualified -->
        <?php if (!empty($shortlistResult['skipped'])): ?>
        <h3 style="color:#c62828;margin-top:20px;margin-bottom:12px">❌ Not Qualified (<?= count($shortlistResult['skipped']) ?>)</h3>
        <?php foreach ($shortlistResult['skipped'] as $c): ?>
        <div class="candidate-row candidate-fail">
            <div>
                <strong style="color:#1a237e"><?= htmlspecialchars($c['name']) ?></strong>
                <span style="color:#666;font-size:0.85rem;margin-left:8px"><?= htmlspecialchars($c['email']) ?></span>
                <div style="font-size:0.82rem;color:#555;margin-top:3px">
                    🎓 <?= htmlspecialchars($c['department'] ?? 'N/A') ?> · 📊 CGPA: <?= $c['cgpa'] ?: 'N/A' ?>
                </div>
                <div style="margin-top:6px">
                    <?php foreach ($c['reasons'] as $r): ?>
                    <span class="reason-tag reason-fail"><?= htmlspecialchars($r) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <span class="badge badge-rejected">❌ Not Qualified</span>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <div style="margin-top:20px;display:flex;gap:10px">
            <a href="../applications.php" class="btn btn-primary">View All Applications</a>
            <a href="../interviews/index.php" class="btn btn-success">Schedule Interviews →</a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Jobs Overview -->
    <div class="card">
        <h2>📋 Your Jobs — Shortlisting Status</h2>
        <?php $jobs->data_seek(0); if ($jobs->num_rows === 0): ?>
        <p style="color:#999;text-align:center;padding:20px">No open jobs. <a href="../post_job.php" style="color:#3f51b5">Post a job →</a></p>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <tr><th>Job Title</th><th>Type</th><th>Min CGPA</th><th>Applied</th><th>Shortlisted</th><th>Action</th></tr>
                <?php while($j = $jobs->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($j['title']) ?></strong></td>
                    <td><?= $j['job_type'] ?></td>
                    <td><?= $j['min_cgpa'] > 0 ? $j['min_cgpa'] : 'None' ?></td>
                    <td><span style="font-weight:700;color:#3f51b5"><?= $j['applied_count'] ?></span></td>
                    <td><span style="font-weight:700;color:#f57f17"><?= $j['shortlisted_count'] ?></span></td>
                    <td>
                        <button class="btn btn-warning btn-sm" onclick="selectJob(<?= $j['id'] ?>, <?= $j['min_cgpa'] ?>)">
                            ⚡ Auto Shortlist
                        </button>
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
function updateJobInfo(sel) {
    const opt = sel.options[sel.selectedIndex];
    const info = document.getElementById('job-info');
    if (!opt.value) { info.style.display='none'; return; }
    const cgpa = opt.dataset.cgpa;
    const applied = opt.dataset.applied;
    const shortlisted = opt.dataset.shortlisted;
    info.style.display = 'block';
    info.innerHTML = `💼 <strong>${opt.text.split('(')[0].trim()}</strong> &nbsp;|&nbsp; 
        📋 ${applied} applied &nbsp;|&nbsp; ⭐ ${shortlisted} shortlisted &nbsp;|&nbsp; 
        📊 Min CGPA: ${cgpa > 0 ? cgpa : 'None'}`;
    if (cgpa > 0) document.getElementById('min-cgpa').value = cgpa;
}

function selectJob(id, cgpa) {
    const sel = document.querySelector('select[name="job_id"]');
    sel.value = id;
    updateJobInfo(sel);
    if (cgpa > 0) document.getElementById('min-cgpa').value = cgpa;
    window.scrollTo({top: 0, behavior: 'smooth'});
}
</script>
</body>
</html>
