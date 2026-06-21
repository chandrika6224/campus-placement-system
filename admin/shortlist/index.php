<?php
require_once '../../includes/config.php';
requireLogin('admin');
require_once '../../includes/notify.php';

// Add approval columns if missing
$conn->query("ALTER TABLE applications ADD COLUMN IF NOT EXISTS approval_status ENUM('pending','approved','rejected') DEFAULT NULL");
$conn->query("ALTER TABLE applications ADD COLUMN IF NOT EXISTS approval_note TEXT DEFAULT NULL");

$msg = '';
$shortlistResult = null;

// ── Bulk Approve / Reject ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $bulk_ids    = array_map('intval', $_POST['bulk_ids'] ?? []);
    $bulk_action = trim($_POST['bulk_action']);
    if (in_array($bulk_action, ['approved','rejected']) && !empty($bulk_ids)) {
        $placeholders = implode(',', $bulk_ids);
        $conn->query("UPDATE applications SET approval_status='$bulk_action' WHERE id IN ($placeholders)");
        foreach ($bulk_ids as $bid) {
            $info = $conn->query("SELECT a.student_id, j.title, c.company_name FROM applications a JOIN jobs j ON a.job_id=j.id JOIN companies c ON j.company_id=c.id WHERE a.id=$bid")->fetch_assoc();
            if ($info) {
                $notifMsg = $bulk_action === 'approved'
                    ? "Your shortlisting for {$info['title']} at {$info['company_name']} has been approved."
                    : "Your shortlisting for {$info['title']} at {$info['company_name']} was not approved.";
                $stN = $conn->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'application', 'Application Update', ?)");
                $stN->bind_param('is', $info['student_id'], $notifMsg); $stN->execute(); $stN->close();
            }
        }
        $count = count($bulk_ids);
        $msg = "<div class='alert alert-success'>\u2705 $count candidate(s) " . ($bulk_action === 'approved' ? 'approved' : 'rejected') . ".</div>";
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_reject'])) {
    $app_id        = (int)$_POST['app_id'];
    $decision      = trim($_POST['decision'] ?? '');
    $approval_note = trim($_POST['approval_note'] ?? '');

    if (in_array($decision, ['approved', 'rejected'])) {
        $stAR = $conn->prepare("UPDATE applications SET approval_status=?, approval_note=? WHERE id=?");
        $stAR->bind_param('ssi', $decision, $approval_note, $app_id); $stAR->execute(); $stAR->close();
        $stInfo = $conn->prepare("SELECT a.student_id, j.title, c.company_name FROM applications a JOIN jobs j ON a.job_id=j.id JOIN companies c ON j.company_id=c.id WHERE a.id=?");
        $stInfo->bind_param('i', $app_id); $stInfo->execute();
        $info = $stInfo->get_result()->fetch_assoc(); $stInfo->close();
        if ($info) {
            $notifMsg = $decision === 'approved'
                ? "Your shortlisting for {$info['title']} at {$info['company_name']} has been approved by admin."
                : "Your shortlisting for {$info['title']} at {$info['company_name']} was not approved." . ($approval_note ? " Note: $approval_note" : '');
            $stNot = $conn->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'application', 'Application Update', ?)");
            $stNot->bind_param('is', $info['student_id'], $notifMsg); $stNot->execute(); $stNot->close();
        }
        $msg = "<div class='alert alert-success'>\u2705 Application " . ucfirst($decision) . ".</div>";
    }
}

// ── Run Auto-Shortlist ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_shortlist'])) {
    $job_id      = (int)$_POST['job_id'];
    $min_cgpa    = (float)$_POST['min_cgpa'];
    $req_skills  = array_filter(array_map('strtolower', array_map('trim', explode(',', $_POST['required_skills'] ?? ''))));
    $min_skills  = (int)($_POST['min_skills_match'] ?? 1);
    $dept_filter = array_filter(array_map('trim', explode(',', $_POST['departments'] ?? '')));
    $resume_min  = (int)($_POST['min_resume_score'] ?? 0);
    $min_attend  = (float)($_POST['min_attendance'] ?? 75);

    // Weights for composite DS score (must sum to 1.0) — test score removed
    $w_cgpa     = 0.30;
    $w_attend   = 0.25;
    $w_skills   = 0.25;
    $w_resume   = 0.20;

    $stJob = $conn->prepare("SELECT j.*, c.company_name FROM jobs j JOIN companies c ON j.company_id=c.id WHERE j.id=?");
    $stJob->bind_param('i', $job_id); $stJob->execute();
    $job = $stJob->get_result()->fetch_assoc(); $stJob->close();
    if (!$job) {
        $msg = '<div class="alert alert-error">Invalid job.</div>';
    } else {
        $stCand = $conn->prepare("SELECT a.id as app_id, a.student_id, u.name, u.email, sp.cgpa, sp.skills, sp.department, COALESCE(sa.attendance_pct,0) as attendance_pct, COALESCE(sa.backlogs,0) as backlogs FROM applications a JOIN users u ON a.student_id=u.id LEFT JOIN student_profiles sp ON sp.user_id=u.id LEFT JOIN student_attendance sa ON sa.user_id=u.id WHERE a.job_id=? AND a.status='applied'");
        $stCand->bind_param('i', $job_id); $stCand->execute();
        $candidates = $stCand->get_result(); $stCand->close();
        $all_candidates = [];
        while ($c = $candidates->fetch_assoc()) {
            $stRS = $conn->prepare("SELECT score, found_skills FROM resume_analysis WHERE user_id=? ORDER BY analyzed_at DESC LIMIT 1");
            $stRS->bind_param('i', $c['student_id']); $stRS->execute();
            $rs = $stRS->get_result()->fetch_assoc(); $stRS->close();
            $c['resume_score'] = (int)($rs['score'] ?? 0);
            $c['found_skills'] = $rs['found_skills'] ?? '';

            // ── Data Science: Normalised feature scores (0-100) ──
            // 1. CGPA score: scale 0-10 → 0-100
            $cgpa_score = min(100, ($c['cgpa'] / 10) * 100);

            // 2. Attendance score: sigmoid-like penalty below threshold
            //    score = 100 if attendance >= threshold, else exponential decay
            $att = (float)$c['attendance_pct'];
            if ($att >= $min_attend) {
                $attend_score = 100;
            } else {
                // Exponential decay: score drops steeply below threshold
                $deficit = ($min_attend - $att) / $min_attend; // 0..1
                $attend_score = max(0, round(100 * exp(-3 * $deficit)));
            }

            // 3. Skills match score
            $skill_score = 0;
            $matched_skills = [];
            if (!empty($req_skills)) {
                $sSkills = array_map('strtolower', array_map('trim', explode(',', $c['skills'] ?? '')));
                // also include resume found_skills
                $rSkills = array_map('strtolower', array_map('trim', explode(',', $c['found_skills'])));
                $allStudentSkills = array_unique(array_merge($sSkills, $rSkills));
                $matched_skills = array_values(array_intersect($req_skills, $allStudentSkills));
                $skill_score = round(count($matched_skills) / count($req_skills) * 100);
            } else {
                $skill_score = 100; // no filter = full score
            }

            // 4. Resume score (already 0-100)
            $resume_score = $c['resume_score'];

            // ── Composite weighted score ──
            $composite = round(
                $cgpa_score   * $w_cgpa  +
                $attend_score * $w_attend +
                $skill_score  * $w_skills +
                $resume_score * $w_resume
            );

            $c['cgpa_score']    = $cgpa_score;
            $c['attend_score']  = $attend_score;
            $c['skill_score']   = $skill_score;
            $c['matched_skills']= $matched_skills;
            $c['composite']     = $composite;
            $all_candidates[]   = $c;
        }

        // ── Percentile ranking among this job's candidates ──
        $composites = array_column($all_candidates, 'composite');
        sort($composites);
        $n = count($composites);

        $shortlisted = []; $skipped = [];

        foreach ($all_candidates as $c) {
            // Percentile: how many scored below this candidate
            $below = count(array_filter($composites, fn($v) => $v < $c['composite']));
            $percentile = $n > 1 ? round($below / ($n - 1) * 100) : 100;
            $c['percentile'] = $percentile;

            // Recommendation label based on composite score
            if ($c['composite'] >= 80)      $c['label'] = ['text'=>'⭐ Highly Recommended', 'color'=>'#1b5e20', 'bg'=>'#c8e6c9'];
            elseif ($c['composite'] >= 60)  $c['label'] = ['text'=>'✅ Recommended',         'color'=>'#2e7d32', 'bg'=>'#e8f5e9'];
            elseif ($c['composite'] >= 40)  $c['label'] = ['text'=>'⚠️ Borderline',          'color'=>'#e65100', 'bg'=>'#fff8e1'];
            else                             $c['label'] = ['text'=>'❌ Not Recommended',     'color'=>'#c62828', 'bg'=>'#ffebee'];

            $reasons = []; $failReasons = []; $pass = true;

            // Hard filters
            if ($min_cgpa > 0 && $c['cgpa'] < $min_cgpa) {
                $failReasons[] = "❌ CGPA {$c['cgpa']} < $min_cgpa";
                $pass = false;
            } elseif ($min_cgpa > 0) {
                $reasons[] = "✅ CGPA {$c['cgpa']}";
            }

            // Attendance: hard fail if 0 AND threshold set, else show DS score
            if ($att < $min_attend) {
                $failReasons[] = "❌ Attendance {$c['attendance_pct']}% < {$min_attend}% (DS score: {$c['attend_score']}/100)";
                $pass = false;
            } else {
                $reasons[] = "✅ Attendance {$c['attendance_pct']}%";
            }

            if (!empty($req_skills)) {
                $mc = count($c['matched_skills']);
                if ($mc >= $min_skills) {
                    $reasons[] = "✅ {$mc}/" . count($req_skills) . " skills (" . implode(', ', array_slice($c['matched_skills'],0,3)) . ")";
                } else {
                    $failReasons[] = "❌ {$mc}/" . count($req_skills) . " skills matched (need $min_skills)";
                    $pass = false;
                }
            }

            if (!empty($dept_filter)) {
                $dm = false;
                foreach ($dept_filter as $d) { if (stripos($c['department'] ?? '', $d) !== false) { $dm = true; break; } }
                if ($dm) $reasons[] = "✅ Dept: {$c['department']}";
                else { $failReasons[] = "❌ Dept not in filter"; $pass = false; }
            }

            if ($resume_min > 0) {
                if ($c['resume_score'] >= $resume_min) $reasons[] = "✅ Resume {$c['resume_score']}/100";
                else { $failReasons[] = "❌ Resume {$c['resume_score']} < $resume_min"; $pass = false; }
            }

            $c['reasons'] = $pass ? $reasons : $failReasons;

            if ($pass) {
                $stUpSL = $conn->prepare("UPDATE applications SET status='shortlisted', approval_status='pending' WHERE id=?");
                $stUpSL->bind_param('i', $c['app_id']); $stUpSL->execute(); $stUpSL->close();
                notifyApplicationStatus($conn, $c['student_id'], 'shortlisted', $job['title'], $job['company_name']);
                $shortlisted[] = $c;
            } else {
                $skipped[] = $c;
            }
        }

        // Sort shortlisted by composite score descending
        usort($shortlisted, fn($a,$b) => $b['composite'] - $a['composite']);
        usort($skipped,     fn($a,$b) => $b['composite'] - $a['composite']);

        $shortlistResult = ['job'=>$job,'shortlisted'=>$shortlisted,'skipped'=>$skipped,
            'weights'=>['cgpa'=>$w_cgpa,'attend'=>$w_attend,'skills'=>$w_skills,'resume'=>$w_resume],
            'min_attend'=>$min_attend];
        $count = count($shortlisted);
        $msg = "<div class='alert alert-success'>✅ <strong>$count candidate(s)</strong> shortlisted using Data Science scoring. Awaiting admin approval.</div>";
    }
}

// Jobs list
$stJobs = $conn->prepare("SELECT j.*, c.company_name, (SELECT COUNT(*) FROM applications WHERE job_id=j.id AND status='applied') as applied_count, (SELECT COUNT(*) FROM applications WHERE job_id=j.id AND status='shortlisted') as shortlisted_count, (SELECT COUNT(*) FROM applications WHERE job_id=j.id AND status='selected') as selected_count FROM jobs j JOIN companies c ON j.company_id=c.id WHERE j.status='open' ORDER BY applied_count DESC");
$stJobs->execute(); $jobs = $stJobs->get_result(); $stJobs->close();

$stPA = $conn->prepare("SELECT a.*, u.name as student_name, u.email, sp.department, sp.cgpa, sp.skills, j.title as job_title, c.company_name, (SELECT score FROM resume_analysis WHERE user_id=a.student_id ORDER BY analyzed_at DESC LIMIT 1) as resume_score FROM applications a JOIN users u ON a.student_id=u.id LEFT JOIN student_profiles sp ON u.id=sp.user_id JOIN jobs j ON a.job_id=j.id JOIN companies c ON j.company_id=c.id WHERE a.status='shortlisted' AND (a.approval_status='pending' OR a.approval_status IS NULL) ORDER BY a.applied_at DESC");
$stPA->execute(); $pending_approval = $stPA->get_result(); $stPA->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Shortlisting & Approval - Admin</title>
<link rel="stylesheet" href="../../css/style.css">
<style>
.candidate-row{display:flex;justify-content:space-between;align-items:flex-start;padding:12px 15px;border-radius:8px;margin-bottom:8px;flex-wrap:wrap;gap:10px}
.candidate-pass{background:#e8f5e9;border-left:4px solid #43a047}
.candidate-fail{background:#ffebee;border-left:4px solid #e53935}
.reason-tag{display:inline-block;padding:3px 9px;border-radius:10px;font-size:0.78rem;font-weight:600;margin:2px}
.reason-pass{background:#c8e6c9;color:#1b5e20}
.reason-fail{background:#ffcdd2;color:#b71c1c}
.resume-bar{height:8px;border-radius:4px;background:#e0e0e0;overflow:hidden;width:100px;display:inline-block;vertical-align:middle;margin-left:6px}
.resume-bar-fill{height:100%;border-radius:4px;background:linear-gradient(90deg,#43a047,#66bb6a)}
.approval-card{background:#fff;border-radius:10px;padding:16px;box-shadow:0 2px 10px rgba(0,0,0,0.07);margin-bottom:12px;border-left:4px solid #fb8c00}
</style>
</head>
<body>
<?php require_once '../sidebar.php'; ?>

<div class="topbar">
    <div class="topbar-left">
        <button class="hamburger" onclick="toggleSidebar()">☰</button>
        <span class="page-title">⚡ Shortlisting & Approval</span>
    </div>
    <div class="topbar-right">
        <?php require_once '../../notifications/widget.php'; ?>
    </div>
</div>

<div class="main-content">
    <?= $msg ?>

    <!-- Tabs -->
    <div style="display:flex;gap:8px;margin-bottom:22px;border-bottom:2px solid #e8eaf6;padding-bottom:0">
        <button onclick="showTab('shortlist')" id="tab-shortlist"
            style="padding:10px 22px;border:none;background:none;font-size:0.95rem;font-weight:700;color:#3f51b5;border-bottom:3px solid #3f51b5;cursor:pointer">
            ⚡ Auto Shortlist
        </button>
        <button onclick="showTab('approval')" id="tab-approval"
            style="padding:10px 22px;border:none;background:none;font-size:0.95rem;font-weight:600;color:#666;border-bottom:3px solid transparent;cursor:pointer">
            ✅ Pending Approval
            <?php if ($pending_approval->num_rows > 0): ?>
            <span style="background:#e53935;color:#fff;border-radius:10px;padding:1px 7px;font-size:0.72rem;margin-left:4px"><?= $pending_approval->num_rows ?></span>
            <?php endif; ?>
        </button>
    </div>

    <!-- ── SHORTLIST TAB ── -->
    <div id="tab-shortlist-content">
        <div class="card" style="background:linear-gradient(135deg,#e65100,#f57c00);color:#fff;margin-bottom:22px">
            <h2 style="color:#fff3e0;margin-bottom:6px">⚡ Automated Shortlisting with Resume Match</h2>
            <p style="color:#ffe0b2">Shortlist candidates using CGPA, skills, resume match score, and test performance. Shortlisted candidates require admin approval before proceeding to interviews.</p>
        </div>

        <div class="card">
            <h2>🎯 Configure & Run Shortlisting</h2>
            <form method="POST">
                <input type="hidden" name="run_shortlist" value="1">
                <div class="form-group">
                    <label>Select Job *</label>
                    <select name="job_id" required onchange="updateJobInfo(this)">
                        <option value="">-- Select a Job --</option>
                        <?php while($j = $jobs->fetch_assoc()): ?>
                        <option value="<?= $j['id'] ?>" data-cgpa="<?= $j['min_cgpa'] ?>" data-applied="<?= $j['applied_count'] ?>"
                            data-req="<?= htmlspecialchars($j['requirements'] ?? '') ?>">
                            <?= htmlspecialchars($j['title']) ?> — <?= htmlspecialchars($j['company_name']) ?>
                            (<?= $j['applied_count'] ?> applied)
                        </option>
                        <?php endwhile; ?>
                    </select>
                    <div id="job-info" style="margin-top:8px;padding:10px;background:#e8eaf6;border-radius:6px;font-size:0.85rem;display:none"></div>
                </div>

                <div style="background:#f8f9ff;border-radius:10px;padding:20px;border:2px solid #e8eaf6;margin-bottom:18px">
                    <div style="font-weight:700;color:#1a237e;margin-bottom:14px">📋 Shortlisting Criteria + Data Science Weights</div>
                    <div style="background:#e8f5e9;border-radius:8px;padding:10px 14px;margin-bottom:14px;font-size:0.85rem;color:#1b5e20;border-left:3px solid #43a047">
                        🧠 <strong>Data Science Engine:</strong> Each candidate gets a weighted composite score (0–100).
                        Attendance below threshold uses exponential decay penalty. Candidates are ranked by percentile.
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Minimum CGPA <small style="color:#999">(0 = no filter) · Weight: 25%</small></label>
                            <input type="number" name="min_cgpa" id="min-cgpa" step="0.1" min="0" max="10" value="0">
                        </div>
                        <div class="form-group">
                            <label>Min Attendance % <small style="color:#999">(DS decay below threshold) · Weight: 20%</small></label>
                            <input type="number" name="min_attendance" step="0.1" min="0" max="100" value="75">
                            <small style="color:#e65100">⚠️ Students below this get exponential penalty & are rejected</small>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Minimum Skills to Match · Weight: 20%</label>
                            <input type="number" name="min_skills_match" min="1" max="20" value="2">
                        </div>
                        <div class="form-group">
                            <label>Minimum Resume Score <small style="color:#999">(0 = no filter) · Weight: 20%</small></label>
                            <input type="number" name="min_resume_score" min="0" max="100" value="0">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Required Skills <small style="color:#999">(comma separated — matched vs profile + resume)</small></label>
                        <input type="text" name="required_skills" id="req-skills" placeholder="e.g. python, mysql, javascript, git">
                    </div>
                    <div class="form-group">
                        <label>Allowed Departments <small style="color:#999">(empty = all)</small></label>
                        <input type="text" name="departments" placeholder="e.g. Computer Science, MCA">
                    </div>
                </div>

                <div style="background:#e3f2fd;border-radius:8px;padding:12px;margin-bottom:15px;font-size:0.88rem;color:#1565c0;border-left:4px solid #1565c0">
                    ℹ️ Shortlisted candidates will be marked <strong>Pending Approval</strong>. You must approve them in the Approval tab before they proceed to interviews.
                </div>
                <button type="submit" class="btn btn-primary" style="background:linear-gradient(135deg,#e65100,#f57c00);border:none;padding:12px 30px">
                    ⚡ Run Shortlisting
                </button>
            </form>
        </div>

        <!-- Results -->
        <?php if ($shortlistResult): ?>
        <div class="card">
            <h2>📊 Results — <?= htmlspecialchars($shortlistResult['job']['title']) ?> @ <?= htmlspecialchars($shortlistResult['job']['company_name']) ?></h2>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:15px;margin-bottom:20px">
                <div style="text-align:center;background:#e8f5e9;border-radius:10px;padding:15px">
                    <div style="font-size:2rem;font-weight:800;color:#2e7d32"><?= count($shortlistResult['shortlisted']) ?></div>
                    <div style="font-size:0.85rem;color:#555">✅ Shortlisted (Pending Approval)</div>
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

            <?php if (!empty($shortlistResult['shortlisted'])): ?>
            <h3 style="color:#2e7d32;margin-bottom:12px">✅ Shortlisted — Pending Approval (<?= count($shortlistResult['shortlisted']) ?>)</h3>
            <?php foreach ($shortlistResult['shortlisted'] as $c): ?>
            <div class="candidate-row candidate-pass">
                <div style="flex:1">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px">
                        <strong style="color:#1a237e"><?= htmlspecialchars($c['name']) ?></strong>
                        <span style="color:#666;font-size:0.83rem"><?= htmlspecialchars($c['email']) ?></span>
                        <span style="background:<?= $c['label']['bg'] ?>;color:<?= $c['label']['color'] ?>;padding:2px 10px;border-radius:12px;font-size:0.78rem;font-weight:700"><?= $c['label']['text'] ?></span>
                    </div>
                    <div style="font-size:0.82rem;color:#555;margin-bottom:8px">
                        🎓 <?= htmlspecialchars($c['department'] ?? 'N/A') ?>
                        · 📊 CGPA: <strong><?= $c['cgpa'] ?></strong>
                        · 📍 Attendance: <strong style="color:<?= $c['attendance_pct'] >= $shortlistResult['min_attend'] ? '#2e7d32' : '#c62828' ?>"><?= $c['attendance_pct'] ?>%</strong>
                        · 📄 Resume: <strong><?= $c['resume_score'] ?>/100</strong>
                        · Composite: <strong style="color:#1a237e"><?= $c['composite'] ?>/100</strong>
                        · Percentile: <strong style="color:#3f51b5"><?= $c['percentile'] ?>th</strong>
                    </div>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:6px">
                        <?php foreach ([['CGPA',$c['cgpa_score'],'#1565c0'],['Attend',$c['attend_score'],$c['attendance_pct']>=$shortlistResult['min_attend']?'#2e7d32':'#c62828'],['Skills',$c['skill_score'],'#7b1fa2'],['Resume',$c['resume_score'],'#e65100']] as [$lbl,$val,$col]): ?>
                        <div style="text-align:center">
                            <div style="color:<?= $col ?>;font-weight:700;font-size:0.82rem"><?= $val ?>%</div>
                            <div style="width:44px;height:5px;background:#e0e0e0;border-radius:3px;overflow:hidden">
                                <div style="width:<?= $val ?>%;height:100%;background:<?= $col ?>;border-radius:3px"></div>
                            </div>
                            <div style="color:#999;font-size:0.68rem"><?= $lbl ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div>
                        <?php foreach ($c['reasons'] as $r): ?>
                        <span class="reason-tag reason-pass"><?= htmlspecialchars($r) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <span class="badge" style="background:#fff8e1;color:#f57f17">⏳ Pending Approval</span>
            </div>
            <?php endforeach; endif; ?>

            <?php if (!empty($shortlistResult['skipped'])): ?>
            <h3 style="color:#c62828;margin-top:20px;margin-bottom:12px">❌ Not Qualified (<?= count($shortlistResult['skipped']) ?>)</h3>
            <?php foreach ($shortlistResult['skipped'] as $c): ?>
            <div class="candidate-row candidate-fail">
                <div style="flex:1">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px">
                        <strong style="color:#1a237e"><?= htmlspecialchars($c['name']) ?></strong>
                        <span style="color:#666;font-size:0.83rem"><?= htmlspecialchars($c['email']) ?></span>
                        <span style="background:<?= $c['label']['bg'] ?>;color:<?= $c['label']['color'] ?>;padding:2px 10px;border-radius:12px;font-size:0.78rem;font-weight:700"><?= $c['label']['text'] ?></span>
                    </div>
                    <div style="font-size:0.82rem;color:#555;margin-bottom:6px">
                        🎓 <?= htmlspecialchars($c['department'] ?? 'N/A') ?>
                        · 📊 CGPA: <strong><?= $c['cgpa'] ?></strong>
                        · 📍 Attendance: <strong style="color:#c62828"><?= $c['attendance_pct'] ?>%</strong>
                        · 📄 Resume: <strong><?= $c['resume_score'] ?>/100</strong>
                        · Composite: <strong><?= $c['composite'] ?>/100</strong>
                    </div>
                    <div>
                        <?php foreach ($c['reasons'] as $r): ?>
                        <span class="reason-tag reason-fail"><?= htmlspecialchars($r) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <span class="badge badge-rejected">❌ Not Qualified</span>
            </div>
            <?php endforeach; endif; ?>

            <div style="margin-top:16px">
                <button onclick="showTab('approval')" class="btn btn-primary">✅ Go to Approval Tab →</button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Jobs Overview -->
        <div class="card">
            <h2>💼 All Open Jobs</h2>
            <?php
            $jobs2 = $conn->query("SELECT j.*, c.company_name, (SELECT COUNT(*) FROM applications WHERE job_id=j.id AND status='applied') as applied_count, (SELECT COUNT(*) FROM applications WHERE job_id=j.id AND status='shortlisted') as shortlisted_count, (SELECT COUNT(*) FROM applications WHERE job_id=j.id AND status='selected') as selected_count FROM jobs j JOIN companies c ON j.company_id=c.id WHERE j.status='open' ORDER BY applied_count DESC");
            ?>
            <div class="table-wrap">
                <table>
                    <tr><th>Job</th><th>Company</th><th>Min CGPA</th><th>Applied</th><th>Shortlisted</th><th>Selected</th><th>Action</th></tr>
                    <?php while($j = $jobs2->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($j['title']) ?></strong></td>
                        <td><?= htmlspecialchars($j['company_name']) ?></td>
                        <td><?= $j['min_cgpa'] > 0 ? $j['min_cgpa'] : '—' ?></td>
                        <td><span style="font-weight:700;color:#3f51b5"><?= $j['applied_count'] ?></span></td>
                        <td><span style="font-weight:700;color:#f57f17"><?= $j['shortlisted_count'] ?></span></td>
                        <td><span style="font-weight:700;color:#2e7d32"><?= $j['selected_count'] ?></span></td>
                        <td>
                            <button class="btn btn-warning btn-sm" onclick="selectJob(<?= $j['id'] ?>, <?= $j['min_cgpa'] ?>)">
                                ⚡ Shortlist
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            </div>
        </div>
    </div>

    <!-- ── APPROVAL TAB ── -->
    <div id="tab-approval-content" style="display:none">
        <div class="card" style="background:linear-gradient(135deg,#1b5e20,#2e7d32);color:#fff;margin-bottom:22px">
            <h2 style="color:#c8e6c9;margin-bottom:6px">✅ Admin Approval Panel</h2>
            <p style="color:#a5d6a7">Review shortlisted candidates and approve or reject them before scheduling interviews.</p>
        </div>

        <?php if ($pending_approval->num_rows === 0): ?>
        <div class="card" style="text-align:center;padding:40px;color:#999">
            <div style="font-size:3rem;margin-bottom:12px">✅</div>
            <p>No candidates pending approval. Run shortlisting to populate this list.</p>
        </div>
        <?php else: ?>
        <?php while($a = $pending_approval->fetch_assoc()):
            $skillsArr = array_map('strtolower', array_map('trim', explode(',', $a['skills'] ?? '')));
        ?>
        <div class="approval-card">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px">
                <div style="flex:1">
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:6px">
                        <strong style="font-size:1rem;color:#1a237e"><?= htmlspecialchars($a['student_name']) ?></strong>
                        <span style="color:#666;font-size:0.83rem"><?= htmlspecialchars($a['email']) ?></span>
                    </div>
                    <div style="font-size:0.85rem;color:#555;margin-bottom:8px">
                        💼 <strong><?= htmlspecialchars($a['job_title']) ?></strong> — 🏢 <?= htmlspecialchars($a['company_name']) ?>
                        <br>🎓 <?= htmlspecialchars($a['department'] ?? 'N/A') ?>
                        · 📊 CGPA: <strong style="color:<?= $a['cgpa'] >= 6 ? '#2e7d32' : '#c62828' ?>"><?= $a['cgpa'] ?: 'N/A' ?></strong>
                        · 📄 Resume Score: <strong><?= $a['resume_score'] ?? 'N/A' ?></strong>/100
                        <span class="resume-bar"><span class="resume-bar-fill" style="width:<?= $a['resume_score'] ?? 0 ?>%"></span></span>
                    </div>
                    <?php if ($a['skills']): ?>
                    <div style="margin-bottom:8px">
                        <?php foreach (array_slice($skillsArr, 0, 8) as $sk): ?>
                        <span style="background:#e8eaf6;color:#3f51b5;padding:2px 8px;border-radius:10px;font-size:0.75rem;margin:2px;display:inline-block"><?= htmlspecialchars($sk) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div style="min-width:260px">
                    <form method="POST">
                        <input type="hidden" name="approve_reject" value="1">
                        <input type="hidden" name="app_id" value="<?= $a['id'] ?>">
                        <div class="form-group" style="margin-bottom:8px">
                            <textarea name="approval_note" rows="2" placeholder="Optional note to student..."
                                style="width:100%;padding:6px 8px;border:1px solid #ddd;border-radius:6px;font-size:0.83rem;resize:none"></textarea>
                        </div>
                        <div style="display:flex;gap:8px">
                            <button type="submit" name="decision" value="approved"
                                style="background:#2e7d32;color:#fff;border:none;padding:8px 18px;border-radius:20px;font-weight:700;font-size:0.85rem;cursor:pointer;flex:1">
                                ✅ Approve
                            </button>
                            <button type="submit" name="decision" value="rejected"
                                style="background:#c62828;color:#fff;border:none;padding:8px 18px;border-radius:20px;font-weight:700;font-size:0.85rem;cursor:pointer;flex:1"
                                onclick="return confirm('Reject this candidate?')">
                                ❌ Reject
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
        <?php endif; ?>
    </div>

</div><!-- main-content -->
</div><!-- app-layout -->

<?php require_once '../../chatbot/widget.php'; ?>
<script>
function showTab(tab) {
    document.getElementById('tab-shortlist-content').style.display = tab === 'shortlist' ? 'block' : 'none';
    document.getElementById('tab-approval-content').style.display  = tab === 'approval'  ? 'block' : 'none';
    document.getElementById('tab-shortlist').style.borderBottomColor = tab === 'shortlist' ? '#3f51b5' : 'transparent';
    document.getElementById('tab-shortlist').style.color = tab === 'shortlist' ? '#3f51b5' : '#666';
    document.getElementById('tab-shortlist').style.fontWeight = tab === 'shortlist' ? '700' : '600';
    document.getElementById('tab-approval').style.borderBottomColor = tab === 'approval' ? '#3f51b5' : 'transparent';
    document.getElementById('tab-approval').style.color = tab === 'approval' ? '#3f51b5' : '#666';
    document.getElementById('tab-approval').style.fontWeight = tab === 'approval' ? '700' : '600';
}
function updateJobInfo(sel) {
    const opt = sel.options[sel.selectedIndex];
    const info = document.getElementById('job-info');
    if (!opt.value) { info.style.display = 'none'; return; }
    info.style.display = 'block';
    info.innerHTML = `💼 <strong>${opt.text.split('(')[0].trim()}</strong> | 📋 ${opt.dataset.applied} applied`;
    if (opt.dataset.cgpa > 0) document.getElementById('min-cgpa').value = opt.dataset.cgpa;
    if (opt.dataset.req) document.getElementById('req-skills').value = '';
}
function selectJob(id, cgpa) {
    document.querySelector('select[name="job_id"]').value = id;
    updateJobInfo(document.querySelector('select[name="job_id"]'));
    if (cgpa > 0) document.getElementById('min-cgpa').value = cgpa;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('show');
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('show');
}
</script>
</body>
</html>
