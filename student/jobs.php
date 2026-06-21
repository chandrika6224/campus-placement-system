<?php
require_once '../includes/config.php';
requireLogin('student');

$uid = $_SESSION['user_id'];
$msg = '';

// Helper: extract the lower bound LPA number from salary string e.g. "18-32 LPA" -> 18, "5 LPA" -> 5
function parseSalaryMin($salary_str) {
    preg_match('/([\d.]+)/', $salary_str ?? '', $m);
    return isset($m[1]) ? floatval($m[1]) : 0;
}

// Get student profile including placement_status and placed_salary
$stmtProf = $conn->prepare("SELECT sp.department, sp.cgpa, sp.placement_status, sp.placed_salary FROM student_profiles sp WHERE sp.user_id=?");
$stmtProf->bind_param('i', $uid);
$stmtProf->execute();
$profile = $stmtProf->get_result()->fetch_assoc();
$stmtProf->close();
$student_stream = trim($profile['department'] ?? '');
$student_cgpa   = (float)($profile['cgpa'] ?? 0);

// Get eligibility criteria
$criteria = ['min_cgpa'=>6,'min_attendance'=>75,'max_backlogs'=>0];
if ($conn->query("SHOW TABLES LIKE 'eligibility_criteria'")->num_rows > 0) {
    $ec = $conn->query("SELECT * FROM eligibility_criteria LIMIT 1")->fetch_assoc();
    if ($ec) $criteria = $ec;
}
$student_attendance = 0;
$student_backlogs   = 0;
if ($conn->query("SHOW TABLES LIKE 'student_attendance'")->num_rows > 0) {
    $stAtt = $conn->prepare("SELECT attendance_pct, backlogs FROM student_attendance WHERE user_id=?");
    $stAtt->bind_param('i', $uid); $stAtt->execute();
    $attRow = $stAtt->get_result()->fetch_assoc(); $stAtt->close();
    if ($attRow) {
        $student_attendance = (float)$attRow['attendance_pct'];
        $student_backlogs   = (int)$attRow['backlogs'];
    }
}
$globally_eligible = ($student_cgpa >= $criteria['min_cgpa'])
    && ($student_attendance >= $criteria['min_attendance'] || $student_attendance == 0)
    && ($student_backlogs  <= $criteria['max_backlogs']);

// Determine if student is placed and their current salary
// Source 1: placement_status from CSV import with placed_salary
$current_salary_min = 0;
$is_placed = false;

if (($profile['placement_status'] ?? '') === 'Placed' && !empty($profile['placed_salary'])) {
    $is_placed = true;
    $current_salary_min = (float)$profile['placed_salary'];
}

// Source 2: application status='selected' with a salary (overrides if higher)
$stmtSel = $conn->prepare("SELECT j.salary_range FROM applications a JOIN jobs j ON a.job_id = j.id WHERE a.student_id = ? AND a.status = 'selected' ORDER BY j.created_at DESC");
$stmtSel->bind_param('i', $uid);
$stmtSel->execute();
$selected = $stmtSel->get_result();
$stmtSel->close();
while ($s = $selected->fetch_assoc()) {
    $val = parseSalaryMin($s['salary_range']);
    if ($val > $current_salary_min) $current_salary_min = $val;
    $is_placed = true;
}

// Apply for job
if (isset($_GET['apply'])) {
    $job_id = (int)$_GET['apply'];

    $ji = $conn->prepare("SELECT salary_range FROM jobs WHERE id=?");
    $ji->bind_param('i', $job_id);
    $ji->execute();
    $job_info = $ji->get_result()->fetch_assoc();
    $ji->close();
    $new_salary_min = parseSalaryMin($job_info['salary_range'] ?? '');

    if ($is_placed && $new_salary_min < $current_salary_min * 2) {
        $msg = '<div class="alert alert-error">❌ You are already placed with a package of <strong>' . $current_salary_min . ' LPA</strong>. You can only apply to jobs with at least <strong>' . ($current_salary_min * 2) . ' LPA</strong> (double your current package).</div>';
    } else {
        $chk = $conn->prepare("SELECT id FROM applications WHERE job_id=? AND student_id=?");
        $chk->bind_param('ii', $job_id, $uid);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows === 0) {
            $ins = $conn->prepare("INSERT INTO applications (job_id, student_id) VALUES (?, ?)");
            $ins->bind_param('ii', $job_id, $uid);
            $ins->execute();
            $ins->close();
            $msg = '<div class="alert alert-success">Application submitted successfully!</div>';
        } else {
            $msg = '<div class="alert alert-info">You have already applied for this job.</div>';
        }
        $chk->close();
    }
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$type   = isset($_GET['type'])   ? trim($_GET['type'])   : '';

$allowed_types = ['Full-time', 'Internship', 'Part-time'];
$where  = "WHERE j.status='open'";
$params = [];
$types  = '';

if ($search) {
    $like = '%' . $search . '%';
    $where .= " AND (j.title LIKE ? OR c.company_name LIKE ? OR j.location LIKE ?)";
}
if ($type && in_array($type, $allowed_types)) {
    $where .= " AND j.job_type=?";
}
$bindTypes = '';
$bindValues = [];
if ($search) {
    $bindTypes .= 'sss';
    $bindValues[] = $like;
    $bindValues[] = $like;
    $bindValues[] = $like;
}
if ($type && in_array($type, $allowed_types)) {
    $bindTypes .= 's';
    $bindValues[] = $type;
}
if ($student_stream) {
    $where .= " AND (j.allowed_streams IS NULL OR j.allowed_streams = '' OR j.allowed_streams LIKE ?)";
    $bindTypes .= 's';
    $bindValues[] = '%' . $student_stream . '%';
}

$jobsSql = "SELECT j.*, c.company_name, c.industry,
    (SELECT COUNT(*) FROM applications WHERE job_id=j.id) as app_count,
    (SELECT id FROM applications WHERE job_id=j.id AND student_id=$uid) as applied
    FROM jobs j JOIN companies c ON j.company_id=c.id $where ORDER BY j.created_at DESC";
$stmtJobs = $conn->prepare($jobsSql);
if ($bindTypes) {
    $stmtJobs->bind_param($bindTypes, ...$bindValues);
}
$stmtJobs->execute();
$jobs = $stmtJobs->get_result();
$stmtJobs->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Browse Jobs - Student</title>
<link rel="stylesheet" href="../css/style.css">
<style>
.job-card-compact{background:#fff;border-radius:10px;padding:14px 16px;box-shadow:0 2px 8px rgba(0,0,0,0.06);border-left:4px solid #3f51b5;transition:transform 0.15s,box-shadow 0.15s;margin-bottom:10px}
.job-card-compact:hover{transform:translateY(-2px);box-shadow:0 4px 16px rgba(0,0,0,0.12);border-left-color:#1a237e}
/* Modal */
.job-modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;padding:20px}
.job-modal-overlay.show{display:flex}
.job-modal{background:#fff;border-radius:14px;width:100%;max-width:680px;max-height:90vh;overflow-y:auto;box-shadow:0 10px 40px rgba(0,0,0,0.2)}
.job-modal-header{background:linear-gradient(135deg,#1a237e,#3949ab);padding:22px 24px;border-radius:14px 14px 0 0;position:sticky;top:0;z-index:1}
.job-modal-body{padding:24px}
.detail-row{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px}
.detail-tag{padding:5px 12px;border-radius:20px;font-size:0.82rem;font-weight:600}
.section-title{font-weight:700;color:#1a237e;font-size:0.9rem;margin:16px 0 6px;border-bottom:2px solid #e8eaf6;padding-bottom:4px}
</style>
</head>
<body>
<?php require_once 'sidebar.php'; ?>
<div class="topbar">
    <div class="topbar-left">
        <button class="hamburger" onclick="toggleSidebar()">☰</button>
        <span class="page-title">Browse Jobs</span>
    </div>
    <div class="topbar-right"><?php require_once '../notifications/widget.php'; ?></div>
</div>
<div class="main-content">
    <?= $msg ?>
    <?php if ($is_placed): ?>
    <div class="alert alert-info" style="margin-bottom:16px">
        <?php if ($current_salary_min > 0): ?>
        🎓 You are already placed with a package of <strong><?= $current_salary_min ?> LPA</strong>. You can only apply to jobs offering <strong><?= $current_salary_min * 2 ?> LPA or more</strong>.
        <?php else: ?>
        🎓 You are already placed. You can only apply to jobs with at least <strong>double</strong> your current package. Ask admin to set your placed salary to enable filtering.
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <div class="card">
        <h2>Available Jobs
            <?php if ($student_stream): ?>
            <small style="font-size:0.78rem;color:#666;font-weight:400;margin-left:10px">📚 Showing jobs eligible for: <strong style="color:#3f51b5"><?= htmlspecialchars($student_stream) ?></strong></small>
            <?php else: ?>
            <small style="font-size:0.78rem;color:#e53935;font-weight:400;margin-left:10px">⚠️ <a href="profile.php">Complete your profile</a> to see stream-specific jobs</small>
            <?php endif; ?>
        </h2>
        <form method="GET" style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap">
            <input type="text" name="search" placeholder="Search jobs, companies..." value="<?= htmlspecialchars($search) ?>" style="flex:1;padding:9px 14px;border:1px solid #ddd;border-radius:6px">
            <select name="type" style="padding:9px 14px;border:1px solid #ddd;border-radius:6px">
                <option value="">All Types</option>
                <option value="Full-time" <?= $type==='Full-time'?'selected':'' ?>>Full-time</option>
                <option value="Internship" <?= $type==='Internship'?'selected':'' ?>>Internship</option>
                <option value="Part-time" <?= $type==='Part-time'?'selected':'' ?>>Part-time</option>
            </select>
            <button type="submit" class="btn btn-primary">Search</button>
            <a href="jobs.php" class="btn btn-warning">Clear</a>
        </form>

        <div class="job-grid">
            <?php $jobCount = 0; while($j = $jobs->fetch_assoc()): $jobCount++; ?>
            <div class="job-card-compact" onclick="showJob(<?= $j['id'] ?>)" style="cursor:pointer">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px">
                    <div style="flex:1">
                        <div style="font-weight:800;color:#1a237e;font-size:0.95rem"><?= htmlspecialchars($j['title']) ?></div>
                        <div style="color:#555;font-size:0.82rem;margin-top:3px">🏢 <?= htmlspecialchars($j['company_name']) ?></div>
                    </div>
                    <?php if ($j['applied']): ?>
                    <span class="badge badge-applied" style="flex-shrink:0;font-size:0.72rem">Applied ✓</span>
                    <?php endif; ?>
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:8px">
                    <span style="background:#e8eaf6;color:#3f51b5;padding:2px 8px;border-radius:10px;font-size:0.75rem;font-weight:600">💼 <?= htmlspecialchars($j['job_type']) ?></span>
                    <span style="background:#e8f5e9;color:#2e7d32;padding:2px 8px;border-radius:10px;font-size:0.75rem;font-weight:600">📍 <?= htmlspecialchars($j['location'] ?? 'N/A') ?></span>
                    <?php if ($j['min_cgpa'] > 0): ?>
                    <span style="background:#fff8e1;color:#e65100;padding:2px 8px;border-radius:10px;font-size:0.75rem;font-weight:600">📊 CGPA ≥ <?= $j['min_cgpa'] ?></span>
                    <?php endif; ?>
                    <?php if ($j['salary_range']): ?>
                    <span style="background:#f3e5f5;color:#7b1fa2;padding:2px 8px;border-radius:10px;font-size:0.75rem;font-weight:600">💰 <?= htmlspecialchars($j['salary_range']) ?></span>
                    <?php endif; ?>
                </div>
                <div style="font-size:0.75rem;color:#999;margin-top:6px">👥 <?= $j['app_count'] ?> applicants · Deadline: <?= $j['deadline'] ? date('d M Y', strtotime($j['deadline'])) : 'Open' ?></div>
                <?php
                $jobEligible = $globally_eligible && ($student_cgpa >= (float)($j['min_cgpa'] ?? 0));
                ?>
                <div style="margin-top:6px">
                    <?php if ($jobEligible): ?>
                    <span style="background:#e8f5e9;color:#2e7d32;padding:2px 10px;border-radius:10px;font-size:0.75rem;font-weight:700">✅ Eligible</span>
                    <?php else: ?>
                    <span style="background:#ffebee;color:#c62828;padding:2px 10px;border-radius:10px;font-size:0.75rem;font-weight:700">❌ Not Eligible</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
            <?php if ($jobCount === 0): ?>
            <div style="text-align:center;padding:40px;color:#999;grid-column:1/-1">
                <div style="font-size:3rem;margin-bottom:12px">💼</div>
                <p style="font-size:1rem;font-weight:600">No jobs available for your stream right now.</p>
                <p style="font-size:0.85rem;margin-top:6px">Check back later or <a href="profile.php" style="color:#3f51b5">update your profile</a> if your stream is incorrect.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div><!-- app-layout -->

<!-- Job Detail Modal -->
<div class="job-modal-overlay" id="jobModal" onclick="closeModal(event)">
    <div class="job-modal" id="jobModalBox">
        <div class="job-modal-header">
            <div style="display:flex;justify-content:space-between;align-items:flex-start">
                <div>
                    <div id="m-title" style="font-size:1.2rem;font-weight:800;color:#ffd54f"></div>
                    <div id="m-company" style="color:#c5cae9;font-size:0.9rem;margin-top:4px"></div>
                </div>
                <button onclick="document.getElementById('jobModal').classList.remove('show')" style="background:rgba(255,255,255,0.15);border:none;color:#fff;width:32px;height:32px;border-radius:50%;cursor:pointer;font-size:1.1rem;flex-shrink:0">&times;</button>
            </div>
        </div>
        <div class="job-modal-body">
            <div class="detail-row" id="m-tags"></div>

            <div class="section-title">📝 Job Description</div>
            <div id="m-desc" style="color:#444;font-size:0.9rem;line-height:1.7"></div>

            <div class="section-title">✅ Requirements</div>
            <div id="m-req" style="color:#444;font-size:0.9rem;line-height:1.7"></div>

            <div class="section-title">📊 Details</div>
            <div id="m-details" style="display:grid;grid-template-columns:1fr 1fr;gap:10px;font-size:0.88rem"></div>

            <div style="margin-top:22px;padding-top:16px;border-top:2px solid #e8eaf6;text-align:center">
                <div id="m-apply-area"></div>
            </div>
        </div>
    </div>
</div>

<?php
// Build jobs JSON for modal
$jobsData = [];
$conn->query("SET SESSION group_concat_max_len = 100000");
$jRes = $conn->query("SELECT j.*, c.company_name, c.industry, c.description as company_desc,
    (SELECT id FROM applications WHERE job_id=j.id AND student_id=$uid) as applied
    FROM jobs j JOIN companies c ON j.company_id=c.id WHERE j.status='open'");
while ($jj = $jRes->fetch_assoc()) {
    $jobsData[$jj['id']] = [
        'id'          => $jj['id'],
        'title'       => $jj['title'],
        'company'     => $jj['company_name'],
        'industry'    => $jj['industry'] ?? '',
        'location'    => $jj['location'] ?? 'N/A',
        'type'        => $jj['job_type'],
        'salary'      => $jj['salary_range'] ?? '',
        'min_cgpa'    => $jj['min_cgpa'],
        'deadline'    => $jj['deadline'] ? date('d M Y', strtotime($jj['deadline'])) : 'Open',
        'description' => $jj['description'] ?? '',
        'requirements'=> $jj['requirements'] ?? '',
        'company_desc'=> $jj['company_desc'] ?? '',
        'applied'     => !empty($jj['applied']),
        'placed_block'=> $is_placed && parseSalaryMin($jj['salary_range']) < $current_salary_min * 2,
        'eligible'    => $globally_eligible && ($student_cgpa >= (float)($jj['min_cgpa'] ?? 0)),
        'elig_reason' => (!$globally_eligible || $student_cgpa < (float)($jj['min_cgpa'] ?? 0))
            ? ($student_cgpa < (float)($jj['min_cgpa'] ?? 0) ? 'CGPA '.$student_cgpa.' < required '.$jj['min_cgpa'] : 'Does not meet placement eligibility criteria')
            : '',
    ];
}
?>

<script>
const JOBS = <?= json_encode($jobsData) ?>;
const IS_PLACED = <?= $is_placed ? 'true' : 'false' ?>;
const CUR_PKG   = <?= $current_salary_min ?>;

function showJob(id) {
    const j = JOBS[id];
    if (!j) return;

    document.getElementById('m-title').textContent   = j.title;
    document.getElementById('m-company').textContent = '🏢 ' + j.company + (j.industry ? ' · ' + j.industry : '');

    // Tags
    const tags = [
        {text: '💼 ' + j.type,     bg:'#e8eaf6', color:'#3f51b5'},
        {text: '📍 ' + j.location, bg:'#e8f5e9', color:'#2e7d32'},
        j.salary   ? {text: '💰 ' + j.salary,           bg:'#f3e5f5', color:'#7b1fa2'} : null,
        j.min_cgpa > 0 ? {text: '📊 CGPA ≥ ' + j.min_cgpa, bg:'#fff8e1', color:'#e65100'} : null,
        {text: '📅 Deadline: ' + j.deadline, bg:'#fce4ec', color:'#880e4f'},
    ].filter(Boolean);
    document.getElementById('m-tags').innerHTML = tags.map(t =>
        `<span class="detail-tag" style="background:${t.bg};color:${t.color}">${t.text}</span>`
    ).join('');

    // Description
    document.getElementById('m-desc').innerHTML = j.description
        ? j.description.replace(/\n/g,'<br>')
        : '<span style="color:#999">No description provided.</span>';

    // Requirements
    if (j.requirements) {
        const reqs = j.requirements.split(/,|\n/).map(r => r.trim()).filter(Boolean);
        document.getElementById('m-req').innerHTML = reqs.map(r =>
            `<span style="display:inline-block;background:#e8eaf6;color:#3f51b5;padding:3px 10px;border-radius:12px;font-size:0.8rem;font-weight:600;margin:3px">${r}</span>`
        ).join('');
    } else {
        document.getElementById('m-req').innerHTML = '<span style="color:#999">Not specified.</span>';
    }

    // Details grid
    document.getElementById('m-details').innerHTML = [
        ['🏢 Company',   j.company],
        ['🏭 Industry',  j.industry || 'N/A'],
        ['📍 Location',  j.location],
        ['💼 Job Type',  j.type],
        ['💰 Package',   j.salary || 'Not disclosed'],
        ['📊 Min CGPA',  j.min_cgpa > 0 ? j.min_cgpa : 'No minimum'],
    ].map(([label, val]) =>
        `<div style="padding:8px 12px;background:#f8f9ff;border-radius:8px">
            <div style="font-size:0.72rem;color:#999;font-weight:600">${label}</div>
            <div style="font-size:0.88rem;font-weight:700;color:#1a237e;margin-top:2px">${val}</div>
        </div>`
    ).join('');

    // Apply button
    let applyHtml = '';
    if (j.applied) {
        applyHtml = `<span class="badge badge-applied" style="font-size:0.95rem;padding:10px 30px">✓ Already Applied</span>`;
    } else if (j.placed_block) {
        applyHtml = `<span style="color:#999;font-size:0.88rem">🔒 You need a job offering at least <strong>${CUR_PKG*2} LPA</strong> to apply again.</span>`;
    } else if (!j.eligible) {
        applyHtml = `<div style="background:#ffebee;border-radius:10px;padding:14px 18px;text-align:left">
            <div style="font-weight:700;color:#c62828;margin-bottom:4px">❌ Not Eligible for this Job</div>
            <div style="font-size:0.85rem;color:#555">${j.elig_reason}</div>
            <a href="profile.php" style="font-size:0.82rem;color:#1565c0;font-weight:600;display:inline-block;margin-top:6px">✏️ Update your profile to improve eligibility →</a>
        </div>`;
    } else {
        applyHtml = `<div>
            <div style="background:#e8f5e9;border-radius:10px;padding:8px 16px;margin-bottom:12px;color:#2e7d32;font-weight:700;font-size:0.88rem">✅ You are Eligible for this job!</div>
            <a href="jobs.php?apply=${j.id}" class="btn btn-success" style="padding:12px 40px;font-size:1rem;border-radius:25px" onclick="return confirm('Apply for ${j.title} at ${j.company}?')">Apply Now →</a>
        </div>`;
    }
    document.getElementById('m-apply-area').innerHTML = applyHtml;

    document.getElementById('jobModal').classList.add('show');
}

function closeModal(e) {
    if (e.target === document.getElementById('jobModal'))
        document.getElementById('jobModal').classList.remove('show');
}

function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('show');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('sidebarOverlay').classList.remove('show');}
</script>
</body>
</html>
