<?php
require_once '../includes/config.php';
requireLogin('admin');

$msg = '';
$activeTab = $_GET['tab'] ?? 'jobs';

// ── JOBS ACTIONS ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $id = (int)$_POST['delete'];
    $conn->query("DELETE FROM jobs WHERE id=$id");
    $_SESSION['ji_msg'] = '<div class="alert alert-success">Job deleted.</div>';
    header('Location: jobs_internships.php?tab=jobs'); exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $id = (int)$_POST['job_id'];
    $conn->query("UPDATE jobs SET status = IF(status='open','closed','open') WHERE id=$id");
    $_SESSION['ji_msg'] = '<div class="alert alert-success">Job status updated.</div>';
    header('Location: jobs_internships.php?tab=jobs'); exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_job'])) {
    $title        = $conn->real_escape_string(trim($_POST['title']));
    $company_name = $conn->real_escape_string(trim($_POST['company_name_input'] ?? ''));
    $job_type     = $conn->real_escape_string($_POST['job_type']);
    $location     = $conn->real_escape_string(trim($_POST['location'] ?? ''));
    $salary       = $conn->real_escape_string(trim($_POST['salary_range'] ?? ''));
    $min_cgpa     = (float)($_POST['min_cgpa'] ?? 0);
    $deadline     = $conn->real_escape_string($_POST['deadline'] ?? '');
    $description  = $conn->real_escape_string(trim($_POST['description'] ?? ''));
    $requirements = $conn->real_escape_string(trim($_POST['requirements'] ?? ''));
    $streams      = $conn->real_escape_string(trim($_POST['allowed_streams'] ?? ''));
    $deadline_val = !empty($deadline) ? "'$deadline'" : 'NULL';
    if ($title && $company_name) {
        $cRow = $conn->query("SELECT id FROM companies WHERE company_name='$company_name' LIMIT 1")->fetch_assoc();
        $cid  = $cRow ? (int)$cRow['id'] : null;
        if (!$cid) { $conn->query("INSERT INTO companies (company_name, user_id) VALUES ('$company_name', 0)"); $cid = (int)$conn->insert_id; }
        $conn->query("INSERT INTO jobs (company_id, title, job_type, location, salary_range, min_cgpa, deadline, description, requirements, allowed_streams, status)
            VALUES ($cid, '$title', '$job_type', '$location', '$salary', $min_cgpa, $deadline_val, '$description', '$requirements', '$streams', 'open')");
        $_SESSION['ji_msg'] = '<div class="alert alert-success">✅ Job posted successfully.</div>';
        header('Location: jobs_internships.php?tab=jobs'); exit();
    } else {
        $msg = '<div class="alert alert-error">Title and company are required.</div>';
    }
}

// ── INTERNSHIP ACTIONS ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_intern'])) {
    $conn->query("ALTER TABLE internships ADD COLUMN IF NOT EXISTS allowed_streams VARCHAR(255) DEFAULT NULL");
    $ititle    = $conn->real_escape_string(trim($_POST['i_title']));
    $icompany  = $conn->real_escape_string(trim($_POST['i_company_name'] ?? ''));
    $iduration = $conn->real_escape_string(trim($_POST['i_duration'] ?? ''));
    $istipend  = $conn->real_escape_string(trim($_POST['i_stipend'] ?? ''));
    $ilocation = $conn->real_escape_string(trim($_POST['i_location'] ?? ''));
    $istreams  = $conn->real_escape_string(trim($_POST['i_streams'] ?? ''));
    $icgpa     = (float)($_POST['i_min_cgpa'] ?? 0);
    $ideadline = $conn->real_escape_string($_POST['i_deadline'] ?? '');
    $idesc     = $conn->real_escape_string(trim($_POST['i_description'] ?? ''));
    $ideadline_val = !empty($ideadline) ? "'$ideadline'" : 'NULL';
    if ($ititle && $icompany) {
        $cRow = $conn->query("SELECT id FROM companies WHERE company_name='$icompany' LIMIT 1")->fetch_assoc();
        $cid  = $cRow ? (int)$cRow['id'] : null;
        if (!$cid) { $conn->query("INSERT INTO companies (company_name, user_id) VALUES ('$icompany', 0)"); $cid = (int)$conn->insert_id; }
        $conn->query("INSERT INTO internships (company_id, title, duration, stipend, location, allowed_streams, min_cgpa, deadline, description, status)
            VALUES ($cid, '$ititle', '$iduration', '$istipend', '$ilocation', '$istreams', $icgpa, $ideadline_val, '$idesc', 'open')");
        $_SESSION['ji_msg'] = '<div class="alert alert-success">✅ Internship posted successfully.</div>';
        header('Location: jobs_internships.php?tab=internships'); exit();
    } else {
        $msg = '<div class="alert alert-error">Title and company are required.</div>';
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['issue_cert'])) {
    $app_id = (int)$_POST['app_id'];
    $conn->query("UPDATE internship_applications SET status='completed', certificate_issued=1, completion_date=CURDATE() WHERE id=$app_id");
    $app = $conn->query("SELECT ia.student_id, i.title, c.company_name FROM internship_applications ia JOIN internships i ON ia.internship_id=i.id JOIN companies c ON i.company_id=c.id WHERE ia.id=$app_id")->fetch_assoc();
    if ($app) {
        require_once '../includes/notify.php';
        createNotification($conn, $app['student_id'], 'system', '🏆 Internship Certificate Issued', "Your certificate for {$app['title']} at {$app['company_name']} is ready!", '/placement/student/internships/index.php');
    }
    $_SESSION['ji_msg'] = '<div class="alert alert-success">✅ Certificate issued.</div>';
    header('Location: jobs_internships.php?tab=internships'); exit();
}
if (isset($_GET['delete_intern'])) {
    $iid = (int)$_GET['delete_intern'];
    $conn->query("DELETE FROM internships WHERE id=$iid");
    header('Location: jobs_internships.php?tab=internships'); exit();
}

$msg = $msg ?: ($_SESSION['ji_msg'] ?? ''); unset($_SESSION['ji_msg']);

// ── FETCH JOBS DATA ──
$companies = $conn->query("SELECT id, company_name FROM companies ORDER BY company_name");
$jobs_result = $conn->query("SELECT j.*, c.company_name, c.industry, c.description as company_desc, c.website,
    (SELECT COUNT(*) FROM applications WHERE job_id=j.id) as app_count,
    (SELECT COUNT(*) FROM applications WHERE job_id=j.id AND status='shortlisted') as shortlisted,
    (SELECT COUNT(*) FROM applications WHERE job_id=j.id AND status='selected') as selected,
    (SELECT COUNT(*) FROM applications WHERE job_id=j.id AND status='rejected') as rejected
    FROM jobs j JOIN companies c ON j.company_id=c.id
    WHERE j.job_type != 'Internship'
    ORDER BY j.created_at DESC");
$jobs = []; $jdMap = [];
while ($j = $jobs_result->fetch_assoc()) {
    $jobs[] = $j;
    $jdMap[$j['id']] = ['name'=>$j['company_name'],'industry'=>$j['industry']??'','desc'=>$j['company_desc']??'','website'=>$j['website']??'','job_title'=>$j['title'],'type'=>$j['job_type'],'location'=>$j['location']??'','salary'=>$j['salary_range']??'','deadline'=>$j['deadline']?date('d M Y',strtotime($j['deadline'])):'Open','total'=>(int)$j['app_count'],'shortlisted'=>(int)$j['shortlisted'],'selected'=>(int)$j['selected'],'rejected'=>(int)$j['rejected'],'pending'=>(int)$j['app_count']-(int)$j['shortlisted']-(int)$j['selected']-(int)$j['rejected']];
}

// ── JOB DETAIL VIEW ──
$view_job = null; $job_applicants = null;
$view_jid = (int)($_GET['view_job'] ?? 0);
if ($view_jid) {
    $view_job = $conn->query("SELECT j.*, c.company_name, c.industry, c.website, c.description as company_desc FROM jobs j JOIN companies c ON j.company_id=c.id WHERE j.id=$view_jid")->fetch_assoc();
    if ($view_job) {
        $job_applicants = $conn->query("SELECT a.*, u.name, u.email, sp.cgpa, sp.department, sp.phone FROM applications a JOIN users u ON a.student_id=u.id LEFT JOIN student_profiles sp ON sp.user_id=u.id WHERE a.job_id=$view_jid ORDER BY FIELD(a.status,'shortlisted','selected','applied','rejected'), a.applied_at DESC");
        $activeTab = 'jobs';
    }
}

// ── FETCH INTERNSHIPS DATA ──
$statsRow = $conn->query("SELECT (SELECT COUNT(*) FROM internships) as total, (SELECT COUNT(*) FROM internships WHERE status='open') as open_count, (SELECT COUNT(*) FROM internship_applications) as apps, (SELECT COUNT(*) FROM internship_applications WHERE status='completed') as completed")->fetch_assoc();
$istats = ['total'=>(int)$statsRow['total'],'open'=>(int)$statsRow['open_count'],'apps'=>(int)$statsRow['apps'],'completed'=>(int)$statsRow['completed']];
$internships = $conn->query("SELECT i.*, c.company_name, (SELECT COUNT(*) FROM internship_applications WHERE internship_id=i.id) as total_apps, (SELECT COUNT(*) FROM internship_applications WHERE internship_id=i.id AND status='completed') as completed_count FROM internships i JOIN companies c ON i.company_id=c.id ORDER BY i.created_at DESC");

$view_iid = (int)($_GET['view'] ?? 0);
$applications = null; $view_intern = null;
if ($view_iid) {
    $view_intern = $conn->query("SELECT i.*, c.company_name FROM internships i JOIN companies c ON i.company_id=c.id WHERE i.id=$view_iid")->fetch_assoc();
    if ($view_intern) {
        $applications = $conn->query("SELECT ia.*, u.name, u.email, sp.cgpa, sp.department FROM internship_applications ia JOIN users u ON ia.student_id=u.id LEFT JOIN student_profiles sp ON sp.user_id=u.id WHERE ia.internship_id=$view_iid ORDER BY ia.applied_at DESC");
        $activeTab = 'internships';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Jobs & Internships - Admin</title>
<link rel="stylesheet" href="../css/style.css">
<style>
.tab-btns { display:flex; gap:8px; margin-bottom:24px; border-bottom:2px solid #e8eaf6; padding-bottom:0; }
.tab-btn { padding:10px 28px; border:none; background:none; cursor:pointer; font-size:1rem; font-weight:600; color:#666; border-bottom:3px solid transparent; margin-bottom:-2px; transition:all 0.2s; border-radius:6px 6px 0 0; }
.tab-btn.active { color:#1a237e; border-bottom-color:#1a237e; background:#f0f2ff; }
.tab-btn:hover { background:#f0f2ff; color:#1a237e; }
.tab-pane { display:none; }
.tab-pane.active { display:block; }
</style>
</head>
<body>
<?php require_once 'sidebar.php'; ?>
<div class="topbar">
    <div class="topbar-left">
        <button class="hamburger" onclick="toggleSidebar()">☰</button>
        <span class="page-title">💼 Jobs & Internships</span>
    </div>
    <div class="topbar-right"><?php require_once '../notifications/widget.php'; ?></div>
</div>
<div class="main-content">
    <?= $msg ?>

    <div class="tab-btns">
        <button class="tab-btn <?= $activeTab==='jobs'?'active':'' ?>" onclick="switchTab('jobs')">💼 Jobs (<?= count($jobs) ?>)</button>
        <button class="tab-btn <?= $activeTab==='internships'?'active':'' ?>" onclick="switchTab('internships')">🏢 Internships (<?= $istats['total'] ?>)</button>
    </div>

    <!-- ══ JOBS TAB ══ -->
    <div id="tab-jobs" class="tab-pane <?= $activeTab==='jobs'?'active':'' ?>">

        <!-- Post a Job -->
        <div class="card" style="margin-bottom:20px">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
                <h2 style="margin:0">➕ Post a New Job</h2>
                <button onclick="togglePostForm()" id="postToggleBtn" class="btn btn-primary btn-sm">➕ Post Job</button>
            </div>
            <div id="post-job-form" style="display:none">
                <form method="POST">
                    <input type="hidden" name="post_job" value="1">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Job Title *</label>
                            <input type="text" name="title" placeholder="e.g. Software Engineer" required>
                        </div>
                        <div class="form-group">
                            <label>Company *</label>
                            <input type="text" name="company_name_input" list="companies_list" placeholder="Type or select company..." required style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:6px;font-size:0.95rem">
                            <datalist id="companies_list">
                                <?php $companies->data_seek(0); while($c = $companies->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($c['company_name']) ?>"></option>
                                <?php endwhile; ?>
                            </datalist>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Job Type</label>
                            <select name="job_type">
                                <option value="Full-time">Full-time</option>
                                <option value="Part-time">Part-time</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Location</label>
                            <input type="text" name="location" placeholder="e.g. Bangalore, Remote">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Salary Range</label>
                            <input type="text" name="salary_range" placeholder="e.g. 8-12 LPA">
                        </div>
                        <div class="form-group">
                            <label>Min CGPA</label>
                            <input type="number" name="min_cgpa" step="0.1" min="0" max="10" value="0">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Deadline</label>
                            <input type="date" name="deadline" min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="form-group">
                            <label>Allowed Streams</label>
                            <input type="text" name="allowed_streams" placeholder="e.g. CSE, IT">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="3" placeholder="Describe the role..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Requirements / Skills</label>
                        <textarea name="requirements" rows="2" placeholder="e.g. PHP, MySQL, JavaScript"></textarea>
                    </div>
                    <div style="display:flex;gap:10px">
                        <button type="submit" class="btn btn-primary">📤 Post Job</button>
                        <button type="button" onclick="togglePostForm()" class="btn" style="background:#e8eaf6;color:#333">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Job Detail View -->
        <?php if ($view_job): ?>
        <div class="card" style="margin-bottom:20px">
            <a href="jobs_internships.php?tab=jobs" class="btn btn-sm" style="background:#e8eaf6;color:#333;margin-bottom:16px;display:inline-block">← Back to All Jobs</a>

            <!-- Job Header -->
            <div style="background:linear-gradient(135deg,#1a237e,#3949ab);border-radius:12px;padding:22px 24px;color:#fff;margin-bottom:20px">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px">
                    <div>
                        <div style="font-size:1.4rem;font-weight:800;color:#ffd54f"><?= htmlspecialchars($view_job['title']) ?></div>
                        <div style="color:#c5cae9;font-size:1rem;margin-top:4px"><?= htmlspecialchars($view_job['company_name']) ?><?= $view_job['industry'] ? ' · '.htmlspecialchars($view_job['industry']) : '' ?></div>
                    </div>
                    <span style="background:<?= $view_job['status']==='open'?'#e8f5e9':'#ffebee' ?>;color:<?= $view_job['status']==='open'?'#2e7d32':'#c62828' ?>;padding:6px 16px;border-radius:20px;font-weight:700;font-size:0.88rem">
                        <?= $view_job['status']==='open' ? '🟢 Open' : '🔴 Closed' ?>
                    </span>
                </div>
                <div style="display:flex;gap:16px;flex-wrap:wrap;margin-top:14px;font-size:0.88rem">
                    <?php if ($view_job['location']): ?><span>📍 <?= htmlspecialchars($view_job['location']) ?></span><?php endif; ?>
                    <?php if ($view_job['job_type']): ?><span>💼 <?= htmlspecialchars($view_job['job_type']) ?></span><?php endif; ?>
                    <?php if ($view_job['salary_range']): ?><span>💰 <?= htmlspecialchars($view_job['salary_range']) ?></span><?php endif; ?>
                    <?php if ($view_job['min_cgpa']): ?><span>📊 Min CGPA: <?= $view_job['min_cgpa'] ?></span><?php endif; ?>
                    <?php if ($view_job['deadline']): ?><span>📅 Deadline: <?= date('d M Y', strtotime($view_job['deadline'])) ?></span><?php endif; ?>
                    <?php if ($view_job['allowed_streams']): ?><span>🎓 <?= htmlspecialchars($view_job['allowed_streams']) ?></span><?php endif; ?>
                </div>
            </div>

            <!-- Description & Requirements -->
            <?php if ($view_job['description'] || $view_job['requirements']): ?>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px">
                <?php if ($view_job['description']): ?>
                <div style="background:#f8f9ff;border-radius:10px;padding:16px">
                    <div style="font-size:0.78rem;color:#999;font-weight:700;margin-bottom:8px">📄 DESCRIPTION</div>
                    <p style="font-size:0.88rem;color:#444;line-height:1.6;margin:0"><?= nl2br(htmlspecialchars($view_job['description'])) ?></p>
                </div>
                <?php endif; ?>
                <?php if ($view_job['requirements']): ?>
                <div style="background:#f8f9ff;border-radius:10px;padding:16px">
                    <div style="font-size:0.78rem;color:#999;font-weight:700;margin-bottom:8px">🛠 REQUIREMENTS</div>
                    <div style="font-size:0.88rem;color:#444">
                        <?php foreach (array_filter(array_map('trim', explode(',', $view_job['requirements']))) as $req): ?>
                        <span style="display:inline-block;background:#e8eaf6;color:#3f51b5;padding:3px 10px;border-radius:12px;font-size:0.8rem;font-weight:600;margin:3px"><?= htmlspecialchars($req) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Stats Row -->
            <?php
            $total_apps   = $job_applicants->num_rows;
            $cnt = ['shortlisted'=>0,'selected'=>0,'rejected'=>0,'applied'=>0];
            $all_apps = []; while($a = $job_applicants->fetch_assoc()) { $all_apps[] = $a; $cnt[$a['status']] = ($cnt[$a['status']] ?? 0) + 1; }
            ?>
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px">
                <div style="background:#e3f2fd;border-radius:10px;padding:14px;text-align:center">
                    <div style="font-size:1.8rem;font-weight:800;color:#1565c0"><?= $total_apps ?></div>
                    <div style="font-size:0.78rem;color:#555;margin-top:3px">Total Applied</div>
                </div>
                <div style="background:#fff8e1;border-radius:10px;padding:14px;text-align:center">
                    <div style="font-size:1.8rem;font-weight:800;color:#e65100"><?= $cnt['shortlisted'] ?></div>
                    <div style="font-size:0.78rem;color:#555;margin-top:3px">Shortlisted</div>
                </div>
                <div style="background:#e8f5e9;border-radius:10px;padding:14px;text-align:center">
                    <div style="font-size:1.8rem;font-weight:800;color:#2e7d32"><?= $cnt['selected'] ?></div>
                    <div style="font-size:0.78rem;color:#555;margin-top:3px">Selected</div>
                </div>
                <div style="background:#ffebee;border-radius:10px;padding:14px;text-align:center">
                    <div style="font-size:1.8rem;font-weight:800;color:#c62828"><?= $cnt['rejected'] ?></div>
                    <div style="font-size:0.78rem;color:#555;margin-top:3px">Rejected</div>
                </div>
            </div>

            <!-- Applicants Table -->
            <h3 style="color:#1a237e;margin-bottom:12px">👥 All Applicants</h3>
            <?php if (empty($all_apps)): ?>
            <p style="color:#999;text-align:center;padding:20px">No applications yet.</p>
            <?php else: ?>
            <!-- Filter buttons -->
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px">
                <button onclick="filterApps('all')" class="filter-btn active" data-filter="all" style="padding:5px 14px;border-radius:20px;border:2px solid #1a237e;background:#1a237e;color:#fff;cursor:pointer;font-size:0.82rem;font-weight:600">All (<?= $total_apps ?>)</button>
                <button onclick="filterApps('shortlisted')" class="filter-btn" data-filter="shortlisted" style="padding:5px 14px;border-radius:20px;border:2px solid #fb8c00;background:#fff;color:#fb8c00;cursor:pointer;font-size:0.82rem;font-weight:600">⭐ Shortlisted (<?= $cnt['shortlisted'] ?>)</button>
                <button onclick="filterApps('selected')" class="filter-btn" data-filter="selected" style="padding:5px 14px;border-radius:20px;border:2px solid #2e7d32;background:#fff;color:#2e7d32;cursor:pointer;font-size:0.82rem;font-weight:600">✅ Selected (<?= $cnt['selected'] ?>)</button>
                <button onclick="filterApps('rejected')" class="filter-btn" data-filter="rejected" style="padding:5px 14px;border-radius:20px;border:2px solid #c62828;background:#fff;color:#c62828;cursor:pointer;font-size:0.82rem;font-weight:600">❌ Rejected (<?= $cnt['rejected'] ?>)</button>
                <button onclick="filterApps('applied')" class="filter-btn" data-filter="applied" style="padding:5px 14px;border-radius:20px;border:2px solid #999;background:#fff;color:#555;cursor:pointer;font-size:0.82rem;font-weight:600">⏳ Pending (<?= $cnt['applied'] ?>)</button>
            </div>
            <div class="table-wrap">
                <table id="apps-table">
                    <tr><th>#</th><th>Student</th><th>Email</th><th>Dept</th><th>CGPA</th><th>Status</th><th>Applied On</th></tr>
                    <?php foreach ($all_apps as $idx => $a): ?>
                    <tr class="app-row" data-status="<?= $a['status'] ?>">
                        <td><?= $idx+1 ?></td>
                        <td><strong><?= htmlspecialchars($a['name']) ?></strong></td>
                        <td style="font-size:0.85rem;color:#666"><?= htmlspecialchars($a['email']) ?></td>
                        <td><?= htmlspecialchars($a['department'] ?? '-') ?></td>
                        <td><?= $a['cgpa'] ?: '-' ?></td>
                        <td>
                            <span style="padding:4px 12px;border-radius:20px;font-size:0.78rem;font-weight:700;background:<?= ['shortlisted'=>'#fff8e1','selected'=>'#e8f5e9','rejected'=>'#ffebee','applied'=>'#e3f2fd'][$a['status']]??'#eee' ?>;color:<?= ['shortlisted'=>'#e65100','selected'=>'#2e7d32','rejected'=>'#c62828','applied'=>'#1565c0'][$a['status']]??'#333' ?>">
                                <?= ['shortlisted'=>'⭐ Shortlisted','selected'=>'✅ Selected','rejected'=>'❌ Rejected','applied'=>'⏳ Pending'][$a['status']] ?? ucfirst($a['status']) ?>
                            </span>
                        </td>
                        <td style="font-size:0.85rem;color:#666"><?= date('d M Y', strtotime($a['applied_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <?php else: ?>
        <div class="card">
            <h2>All Job Postings (<?= count($jobs) ?>)</h2>
            <div class="table-wrap">
                <table>
                    <tr><th>Title</th><th>Company</th><th>Type</th><th>Location</th><th>Salary</th><th>Deadline</th><th>Applications</th><th>Status</th><th>Actions</th></tr>
                    <?php foreach ($jobs as $j): ?>
                    <tr>
                        <td>
                            <a href="?tab=jobs&view_job=<?= $j['id'] ?>" style="color:#1a237e;font-weight:700;text-decoration:none">
                                <?= htmlspecialchars($j['title']) ?>
                            </a>
                        </td>
                        <td>
                            <a href="#" onclick="showCompany(<?= $j['id'] ?>);return false;" style="color:#1a237e;font-weight:600;text-decoration:none;cursor:pointer">
                                <?= htmlspecialchars($j['company_name']) ?>
                            </a>
                        </td>
                        <td><?= $j['job_type'] ?></td>
                        <td><?= htmlspecialchars($j['location'] ?? '-') ?></td>
                        <td><?= $j['salary_range'] ? htmlspecialchars($j['salary_range']) : '-' ?></td>
                        <td style="color:<?= $j['deadline'] && strtotime($j['deadline']) < time() ? '#c62828' : '#2e7d32' ?>">
                            <?= $j['deadline'] ? date('d M Y', strtotime($j['deadline'])) : '-' ?>
                        </td>
                        <td><?= $j['app_count'] ?></td>
                        <td>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="job_id" value="<?= $j['id'] ?>">
                                <button name="toggle_status" class="btn btn-sm" style="background:<?= $j['status']==='open'?'#e8f5e9':'#ffebee' ?>;color:<?= $j['status']==='open'?'#2e7d32':'#c62828' ?>">
                                    <?= $j['status']==='open' ? '🟢 Open' : '🔴 Closed' ?>
                                </button>
                            </form>
                        </td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Delete this job?')" style="display:inline">
                                <input type="hidden" name="delete" value="<?= $j['id'] ?>">
                                <button class="btn btn-danger btn-sm">🗑 Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ══ INTERNSHIPS TAB ══ -->
    <div id="tab-internships" class="tab-pane <?= $activeTab==='internships'?'active':'' ?>">

        <!-- Post Internship -->
        <div class="card" style="margin-bottom:20px">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
                <h2 style="margin:0">➕ Post a New Internship</h2>
                <button onclick="toggleInternForm()" id="internToggleBtn" class="btn btn-primary btn-sm">➕ Post Internship</button>
            </div>
            <div id="post-intern-form" style="display:none">
                <form method="POST">
                    <input type="hidden" name="post_intern" value="1">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Internship Title *</label>
                            <input type="text" name="i_title" placeholder="e.g. Web Development Intern" required>
                        </div>
                        <div class="form-group">
                            <label>Company *</label>
                            <input type="text" name="i_company_name" list="i_companies_list" placeholder="Type or select company..." required style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:6px;font-size:0.95rem">
                            <datalist id="i_companies_list">
                                <?php $companies->data_seek(0); while($c = $companies->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($c['company_name']) ?>"></option>
                                <?php endwhile; ?>
                            </datalist>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Duration</label>
                            <input type="text" name="i_duration" placeholder="e.g. 3 Months">
                        </div>
                        <div class="form-group">
                            <label>Stipend</label>
                            <input type="text" name="i_stipend" placeholder="e.g. 10,000/month or Unpaid">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Location</label>
                            <input type="text" name="i_location" placeholder="e.g. Remote, Hyderabad">
                        </div>
                        <div class="form-group">
                            <label>Min CGPA</label>
                            <input type="number" name="i_min_cgpa" step="0.1" min="0" max="10" value="0">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Deadline</label>
                            <input type="date" name="i_deadline" min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="form-group">
                            <label>Allowed Streams</label>
                            <input type="text" name="i_streams" placeholder="e.g. CSE, IT, ECE">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="i_description" rows="3" placeholder="Describe the internship role..."></textarea>
                    </div>
                    <div style="display:flex;gap:10px">
                        <button type="submit" class="btn btn-primary">📤 Post Internship</button>
                        <button type="button" onclick="toggleInternForm()" class="btn" style="background:#e8eaf6;color:#333">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:25px">
            <div class="stat-card"><div class="number"><?= $istats['total'] ?></div><div class="label">🏢 Total</div></div>
            <div class="stat-card green"><div class="number"><?= $istats['open'] ?></div><div class="label">✅ Open</div></div>
            <div class="stat-card orange"><div class="number"><?= $istats['apps'] ?></div><div class="label">📋 Applications</div></div>
            <div class="stat-card" style="border-left-color:#7b1fa2"><div class="number"><?= $istats['completed'] ?></div><div class="label">🏆 Completed</div></div>
        </div>

        <!-- View Applications -->
        <?php if ($view_intern && $applications): ?>
        <div class="card" style="margin-bottom:20px">
            <h2>📋 Applications — <?= htmlspecialchars($view_intern['title']) ?> @ <?= htmlspecialchars($view_intern['company_name']) ?>
                <a href="jobs_internships.php?tab=internships" class="btn btn-sm" style="float:right;background:#e8eaf6;color:#333">← Back</a>
            </h2>
            <?php if ($applications->num_rows === 0): ?>
            <p style="color:#999;text-align:center;padding:20px">No applications yet.</p>
            <?php else: ?>
            <div class="table-wrap">
                <table>
                    <tr><th>Student</th><th>Dept</th><th>CGPA</th><th>Status</th><th>Applied</th><th>Certificate</th><th>Action</th></tr>
                    <?php while($a = $applications->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($a['name']) ?></strong><br><small style="color:#999"><?= htmlspecialchars($a['email']) ?></small></td>
                        <td><?= htmlspecialchars($a['department'] ?? '-') ?></td>
                        <td><?= $a['cgpa'] ?: '-' ?></td>
                        <td><span class="badge badge-<?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span></td>
                        <td><?= date('d M Y', strtotime($a['applied_at'])) ?></td>
                        <td><?= $a['certificate_issued'] ? '<span style="color:#2e7d32;font-weight:700">🏆 Issued</span>' : '—' ?></td>
                        <td>
                            <?php if ($a['status'] === 'selected' && !$a['certificate_issued']): ?>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="app_id" value="<?= $a['id'] ?>">
                                <button name="issue_cert" class="btn btn-success btn-sm" onclick="return confirm('Issue certificate?')">🏆 Issue Cert</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Internships Table -->
        <div class="card">
            <h2>All Internships</h2>
            <?php if ($internships->num_rows === 0): ?>
            <p style="color:#999;text-align:center;padding:30px">No internships posted yet.</p>
            <?php else: ?>
            <div class="table-wrap">
                <table>
                    <tr><th>Title</th><th>Company</th><th>Duration</th><th>Stipend</th><th>Status</th><th>Applications</th><th>Completed</th><th>Deadline</th><th>Actions</th></tr>
                    <?php $idMap = []; $internships->data_seek(0); while($i = $internships->fetch_assoc()): $idMap[$i['id']] = $i; ?>
                    <tr>
                        <td><a href="#" onclick="showInternship(<?= $i['id'] ?>);return false;" style="color:#1a237e;font-weight:700;text-decoration:none;cursor:pointer"><?= htmlspecialchars($i['title']) ?></a></td>
                        <td><?= htmlspecialchars($i['company_name']) ?></td>
                        <td><?= htmlspecialchars($i['duration'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($i['stipend'] ?? '-') ?></td>
                        <td><span class="badge badge-<?= $i['status'] ?>"><?= ucfirst($i['status']) ?></span></td>
                        <td><strong style="color:#3f51b5"><?= $i['total_apps'] ?></strong></td>
                        <td><strong style="color:#2e7d32"><?= $i['completed_count'] ?></strong></td>
                        <td><?= $i['deadline'] ? date('d M Y', strtotime($i['deadline'])) : '—' ?></td>
                        <td style="display:flex;gap:5px;flex-wrap:wrap">
                            <a href="?tab=internships&view=<?= $i['id'] ?>" class="btn btn-primary btn-sm">View</a>
                            <a href="?tab=internships&delete_intern=<?= $i['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete?')">Delete</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>

<!-- Company Modal -->
<div id="company-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;padding:20px">
    <div style="background:#fff;border-radius:14px;width:100%;max-width:560px;max-height:90vh;overflow-y:auto;box-shadow:0 10px 40px rgba(0,0,0,0.2)">
        <div style="background:linear-gradient(135deg,#1a237e,#3949ab);padding:22px 24px;border-radius:14px 14px 0 0;display:flex;justify-content:space-between;align-items:flex-start">
            <div>
                <div id="cm-name" style="font-size:1.3rem;font-weight:800;color:#ffd54f"></div>
                <div id="cm-industry" style="color:#c5cae9;font-size:0.88rem;margin-top:4px"></div>
            </div>
            <button onclick="closeCompanyModal()" style="background:rgba(255,255,255,0.15);border:none;color:#fff;width:32px;height:32px;border-radius:50%;cursor:pointer;font-size:1.1rem">&times;</button>
        </div>
        <div style="padding:22px 24px">
            <div style="background:#f8f9ff;border-radius:10px;padding:14px 16px;margin-bottom:16px">
                <div style="font-size:0.78rem;color:#999;font-weight:700;margin-bottom:8px">JOB DETAILS</div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:0.88rem">
                    <div><span style="color:#999">Role:</span> <strong id="cm-role"></strong></div>
                    <div><span style="color:#999">Type:</span> <strong id="cm-type"></strong></div>
                    <div><span style="color:#999">Location:</span> <strong id="cm-location"></strong></div>
                    <div><span style="color:#999">Salary:</span> <strong id="cm-salary"></strong></div>
                    <div><span style="color:#999">Deadline:</span> <strong id="cm-deadline"></strong></div>
                    <div id="cm-website-wrap"><span style="color:#999">Website:</span> <a id="cm-website" href="#" target="_blank" style="color:#1a237e;font-weight:600"></a></div>
                </div>
                <div id="cm-desc-wrap" style="margin-top:10px;font-size:0.85rem;color:#555"></div>
            </div>
            <div style="font-size:0.78rem;color:#999;font-weight:700;margin-bottom:10px">STUDENT STATISTICS</div>
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:16px">
                <div style="background:#e3f2fd;border-radius:10px;padding:12px;text-align:center">
                    <div id="cm-total" style="font-size:1.6rem;font-weight:800;color:#1565c0"></div>
                    <div style="font-size:0.75rem;color:#555;margin-top:3px">Total Applied</div>
                </div>
                <div style="background:#fff8e1;border-radius:10px;padding:12px;text-align:center">
                    <div id="cm-shortlisted" style="font-size:1.6rem;font-weight:800;color:#e65100"></div>
                    <div style="font-size:0.75rem;color:#555;margin-top:3px">Shortlisted</div>
                </div>
                <div style="background:#e8f5e9;border-radius:10px;padding:12px;text-align:center">
                    <div id="cm-selected" style="font-size:1.6rem;font-weight:800;color:#2e7d32"></div>
                    <div style="font-size:0.75rem;color:#555;margin-top:3px">Selected</div>
                </div>
                <div style="background:#ffebee;border-radius:10px;padding:12px;text-align:center">
                    <div id="cm-rejected" style="font-size:1.6rem;font-weight:800;color:#c62828"></div>
                    <div style="font-size:0.75rem;color:#555;margin-top:3px">Rejected</div>
                </div>
            </div>
            <div style="display:flex;height:10px;border-radius:10px;overflow:hidden;gap:2px;margin-bottom:6px">
                <div id="bar-shortlisted" style="background:#fb8c00;transition:width 0.5s"></div>
                <div id="bar-selected"    style="background:#43a047;transition:width 0.5s"></div>
                <div id="bar-rejected"    style="background:#e53935;transition:width 0.5s"></div>
                <div id="bar-pending"     style="background:#e0e0e0;transition:width 0.5s"></div>
            </div>
            <div style="display:flex;gap:14px;flex-wrap:wrap">
                <span style="font-size:0.75rem;color:#fb8c00">⭐ Shortlisted</span>
                <span style="font-size:0.75rem;color:#43a047">✅ Selected</span>
                <span style="font-size:0.75rem;color:#e53935">❌ Rejected</span>
                <span style="font-size:0.75rem;color:#999">⏳ Pending</span>
            </div>
        </div>
    </div>
</div>

<!-- Internship Modal -->
<div id="intern-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;padding:20px">
    <div style="background:#fff;border-radius:14px;width:100%;max-width:560px;max-height:90vh;overflow-y:auto;box-shadow:0 10px 40px rgba(0,0,0,0.2)">
        <div style="background:linear-gradient(135deg,#1b5e20,#388e3c);padding:22px 24px;border-radius:14px 14px 0 0;display:flex;justify-content:space-between;align-items:flex-start">
            <div>
                <div id="im-title" style="font-size:1.3rem;font-weight:800;color:#f9fbe7"></div>
                <div id="im-company" style="color:#c8e6c9;font-size:0.88rem;margin-top:4px"></div>
            </div>
            <button onclick="closeInternModal()" style="background:rgba(255,255,255,0.15);border:none;color:#fff;width:32px;height:32px;border-radius:50%;cursor:pointer;font-size:1.1rem">&times;</button>
        </div>
        <div style="padding:22px 24px">
            <div style="background:#f1f8e9;border-radius:10px;padding:14px 16px;margin-bottom:16px">
                <div style="font-size:0.78rem;color:#999;font-weight:700;margin-bottom:8px">INTERNSHIP DETAILS</div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:0.88rem">
                    <div><span style="color:#999">Duration:</span> <strong id="im-duration"></strong></div>
                    <div><span style="color:#999">Stipend:</span> <strong id="im-stipend"></strong></div>
                    <div><span style="color:#999">Location:</span> <strong id="im-location"></strong></div>
                    <div><span style="color:#999">Min CGPA:</span> <strong id="im-cgpa"></strong></div>
                    <div><span style="color:#999">Deadline:</span> <strong id="im-deadline"></strong></div>
                    <div><span style="color:#999">Streams:</span> <strong id="im-streams"></strong></div>
                    <div><span style="color:#999">Status:</span> <strong id="im-status"></strong></div>
                </div>
                <div id="im-desc-wrap" style="margin-top:10px;font-size:0.85rem;color:#555"></div>
            </div>
            <div style="font-size:0.78rem;color:#999;font-weight:700;margin-bottom:10px">STATISTICS</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                <div style="background:#e3f2fd;border-radius:10px;padding:14px;text-align:center">
                    <div id="im-apps" style="font-size:1.8rem;font-weight:800;color:#1565c0"></div>
                    <div style="font-size:0.75rem;color:#555;margin-top:3px">Total Applications</div>
                </div>
                <div style="background:#e8f5e9;border-radius:10px;padding:14px;text-align:center">
                    <div id="im-completed" style="font-size:1.8rem;font-weight:800;color:#2e7d32"></div>
                    <div style="font-size:0.75rem;color:#555;margin-top:3px">Completed</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
var _jdMap = <?= json_encode($jdMap) ?>;
var _idMap = <?= json_encode(array_map(function($i){ return ['title'=>$i['title'],'company'=>$i['company_name'],'duration'=>$i['duration']??'','stipend'=>$i['stipend']??'','location'=>$i['location']??'','min_cgpa'=>$i['min_cgpa']??0,'deadline'=>$i['deadline']?date('d M Y',strtotime($i['deadline'])):'Open','description'=>$i['description']??'','allowed_streams'=>$i['allowed_streams']??'','status'=>$i['status'],'total_apps'=>(int)$i['total_apps'],'completed'=>(int)$i['completed_count']]; }, $idMap ?? [])) ?>;

function filterApps(status) {
    document.querySelectorAll('.app-row').forEach(function(row) {
        row.style.display = (status === 'all' || row.dataset.status === status) ? '' : 'none';
    });
    document.querySelectorAll('.filter-btn').forEach(function(btn) {
        var active = btn.dataset.filter === status;
        btn.style.background = active ? '#1a237e' : '#fff';
        btn.style.color = active ? '#fff' : btn.style.borderColor;
    });
}
function switchTab(tab) {
    document.querySelectorAll('.tab-pane').forEach(function(p){ p.classList.remove('active'); });
    document.querySelectorAll('.tab-btn').forEach(function(b){ b.classList.remove('active'); });
    document.getElementById('tab-' + tab).classList.add('active');
    event.currentTarget.classList.add('active');
}
function showCompany(id) {
    var d = _jdMap[id]; if (!d) return;
    document.getElementById('cm-name').textContent        = d.name;
    document.getElementById('cm-industry').textContent    = d.industry || 'Company';
    document.getElementById('cm-role').textContent        = d.job_title;
    document.getElementById('cm-type').textContent        = d.type;
    document.getElementById('cm-location').textContent    = d.location || 'N/A';
    document.getElementById('cm-salary').textContent      = d.salary || 'Not disclosed';
    document.getElementById('cm-deadline').textContent    = d.deadline;
    document.getElementById('cm-total').textContent       = d.total;
    document.getElementById('cm-shortlisted').textContent = d.shortlisted;
    document.getElementById('cm-selected').textContent    = d.selected;
    document.getElementById('cm-rejected').textContent    = d.rejected;
    if (d.website) { document.getElementById('cm-website').textContent = d.website; document.getElementById('cm-website').href = d.website; document.getElementById('cm-website-wrap').style.display='block'; }
    else { document.getElementById('cm-website-wrap').style.display='none'; }
    document.getElementById('cm-desc-wrap').innerHTML = d.desc ? '<strong>About:</strong> ' + d.desc : '';
    var t = d.total || 1;
    document.getElementById('bar-shortlisted').style.width = (d.shortlisted/t*100)+'%';
    document.getElementById('bar-selected').style.width    = (d.selected/t*100)+'%';
    document.getElementById('bar-rejected').style.width    = (d.rejected/t*100)+'%';
    document.getElementById('bar-pending').style.width     = (d.pending/t*100)+'%';
    document.getElementById('company-modal').style.display = 'flex';
}
function closeCompanyModal() { document.getElementById('company-modal').style.display = 'none'; }
function showInternship(id) {
    var d = _idMap[id]; if (!d) return;
    document.getElementById('im-title').textContent    = d.title;
    document.getElementById('im-company').textContent  = d.company;
    document.getElementById('im-duration').textContent = d.duration || 'N/A';
    document.getElementById('im-stipend').textContent  = d.stipend || 'Unpaid';
    document.getElementById('im-location').textContent = d.location || 'N/A';
    document.getElementById('im-cgpa').textContent     = d.min_cgpa || 'None';
    document.getElementById('im-deadline').textContent = d.deadline;
    document.getElementById('im-streams').textContent  = d.allowed_streams || 'All';
    document.getElementById('im-status').textContent   = d.status.charAt(0).toUpperCase() + d.status.slice(1);
    document.getElementById('im-apps').textContent     = d.total_apps;
    document.getElementById('im-completed').textContent = d.completed;
    document.getElementById('im-desc-wrap').innerHTML  = d.description ? '<strong>Description:</strong> ' + d.description : '';
    document.getElementById('intern-modal').style.display = 'flex';
}
function closeInternModal() { document.getElementById('intern-modal').style.display = 'none'; }
document.getElementById('intern-modal').addEventListener('click', function(e){ if(e.target===this) closeInternModal(); });
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('show');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('sidebarOverlay').classList.remove('show');}
function toggleInternForm() {
    var form = document.getElementById('post-intern-form');
    var btn  = document.getElementById('internToggleBtn');
    var open = form.style.display !== 'none';
    form.style.display = open ? 'none' : 'block';
    btn.textContent    = open ? '➕ Post Internship' : '✕ Cancel';
    if (!open) form.scrollIntoView({behavior:'smooth',block:'start'});
}
function togglePostForm() {
    var form = document.getElementById('post-job-form');
    var btn  = document.getElementById('postToggleBtn');
    var open = form.style.display !== 'none';
    form.style.display = open ? 'none' : 'block';
    btn.textContent    = open ? '➕ Post Job' : '✕ Cancel';
    if (!open) form.scrollIntoView({behavior:'smooth',block:'start'});
}
</script>
</body>
</html>

