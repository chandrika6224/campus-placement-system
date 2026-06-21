<?php
require_once '../includes/config.php';
requireLogin('admin');

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $id = (int)$_POST['delete'];
    $conn->query("DELETE FROM jobs WHERE id=$id");
    $_SESSION['jobs_msg'] = '<div class="alert alert-success">Job deleted.</div>';
    header('Location: jobs.php'); exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $id = (int)$_POST['job_id'];
    $conn->query("UPDATE jobs SET status = IF(status='open','closed','open') WHERE id=$id");
    $_SESSION['jobs_msg'] = '<div class="alert alert-success">Job status updated.</div>';
    header('Location: jobs.php'); exit();
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
        if ($cRow) {
            $cid = (int)$cRow['id'];
        } else {
            $conn->query("INSERT INTO companies (company_name, user_id) VALUES ('$company_name', 0)");
            $cid = (int)$conn->insert_id;
        }
        $conn->query("INSERT INTO jobs (company_id, title, job_type, location, salary_range, min_cgpa, deadline, description, requirements, allowed_streams, status)
            VALUES ($cid, '$title', '$job_type', '$location', '$salary', $min_cgpa, $deadline_val, '$description', '$requirements', '$streams', 'open')");
        $_SESSION['jobs_msg'] = '<div class="alert alert-success">✅ Job posted successfully.</div>';
        header('Location: jobs.php'); exit();
    } else {
        $msg = '<div class="alert alert-error">Title and company are required.</div>';
    }
}

$msg = $msg ?: ($_SESSION['jobs_msg'] ?? ''); unset($_SESSION['jobs_msg']);

$company_filter = trim($_GET['company'] ?? '');
$companies = $conn->query("SELECT id, company_name FROM companies ORDER BY company_name");
// Only show Full-time and Part-time jobs — Internships are managed separately
$jobs_result = $conn->query("SELECT j.*, c.company_name, c.industry, c.description as company_desc, c.website,
    (SELECT COUNT(*) FROM applications WHERE job_id=j.id) as app_count,
    (SELECT COUNT(*) FROM applications WHERE job_id=j.id AND status='shortlisted') as shortlisted,
    (SELECT COUNT(*) FROM applications WHERE job_id=j.id AND status='selected') as selected,
    (SELECT COUNT(*) FROM applications WHERE job_id=j.id AND status='rejected') as rejected
    FROM jobs j JOIN companies c ON j.company_id=c.id
    WHERE j.job_type != 'Internship'
    " . ($company_filter ? " AND c.company_name='" . $conn->real_escape_string($company_filter) . "'" : "") . "
    ORDER BY j.created_at DESC");

// Collect all rows and build jdMap in PHP
$jobs = [];
$jdMap = [];
while ($j = $jobs_result->fetch_assoc()) {
    $jobs[] = $j;
    $jdMap[$j['id']] = [
        'name'        => $j['company_name'],
        'industry'    => $j['industry'] ?? '',
        'desc'        => $j['company_desc'] ?? '',
        'website'     => $j['website'] ?? '',
        'job_title'   => $j['title'],
        'type'        => $j['job_type'],
        'location'    => $j['location'] ?? '',
        'salary'      => $j['salary_range'] ?? '',
        'deadline'    => $j['deadline'] ? date('d M Y', strtotime($j['deadline'])) : 'Open',
        'total'       => (int)$j['app_count'],
        'shortlisted' => (int)$j['shortlisted'],
        'selected'    => (int)$j['selected'],
        'rejected'    => (int)$j['rejected'],
        'pending'     => (int)$j['app_count'] - (int)$j['shortlisted'] - (int)$j['selected'] - (int)$j['rejected'],
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Jobs - Admin</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php require_once 'sidebar.php'; ?>
<div class="topbar">
    <div class="topbar-left">
        <button class="hamburger" onclick="toggleSidebar()">☰</button>
        <span class="page-title">Jobs</span>
    </div>
    <div class="topbar-right"><?php require_once '../notifications/widget.php'; ?></div>
</div>
<div class="main-content">
    <?= $msg ?>

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
                        <input type="text" name="company_name_input" id="company_name_input" list="companies_list"
                            placeholder="Type or select company..." required
                            style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:6px;font-size:0.95rem">
                        <input type="hidden" name="company_id" id="company_id_hidden">
                        <datalist id="companies_list">
                            <?php $companies->data_seek(0); while($c = $companies->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($c['company_name']) ?>" data-id="<?= $c['id'] ?>"></option>
                            <?php endwhile; ?>
                        </datalist>
                        <small style="color:#999">Select existing or type a new company name</small>
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
                        <input type="number" name="min_cgpa" step="0.1" min="0" max="10" value="0" placeholder="e.g. 7.0">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Application Deadline</label>
                        <input type="date" name="deadline" min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label>Allowed Streams <small style="color:#999">(comma separated)</small></label>
                        <input type="text" name="allowed_streams" placeholder="e.g. Computer Science Engineering, IT">
                    </div>
                </div>
                <div class="form-group">
                    <label>Job Description</label>
                    <textarea name="description" rows="3" placeholder="Describe the role, responsibilities..."></textarea>
                </div>
                <div class="form-group">
                    <label>Requirements / Skills</label>
                    <textarea name="requirements" rows="2" placeholder="e.g. PHP, MySQL, JavaScript, Problem Solving"></textarea>
                </div>
                <div style="display:flex;gap:10px">
                    <button type="submit" class="btn btn-primary">📤 Post Job</button>
                    <button type="button" onclick="togglePostForm()" class="btn" style="background:#e8eaf6;color:#333">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Jobs Table -->
    <div class="card">
        <h2>All Job Postings (<?= count($jobs) ?>) <small style="color:#999;font-size:0.8rem">— Internships are managed under the Internships section</small>
            <?php if ($company_filter): ?>
            <span style="background:#e8eaf6;color:#3f51b5;font-size:0.75rem;padding:3px 10px;border-radius:12px;font-weight:700;margin-left:8px">🏢 <?= htmlspecialchars($company_filter) ?> — <a href="jobs.php" style="color:#1a237e">Clear</a></span>
            <?php endif; ?>
        </h2>
        <div class="table-wrap">
            <table>
                <tr><th>Title</th><th>Company</th><th>Type</th><th>Location</th><th>Salary</th><th>Deadline</th><th>Applications</th><th>Status</th><th>Actions</th></tr>
                <?php foreach ($jobs as $j): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($j['title']) ?></strong></td>
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
</div>
</div>

<!-- Company Detail Modal -->
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

<script>
var _jdMap = <?= json_encode($jdMap) ?>;

function showCompany(id) {
    var d = _jdMap[id];
    console.log('showCompany called, id='+id, d);
    if (!d) { console.error('No data for id '+id); return; }
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
    if (d.website) {
        document.getElementById('cm-website').textContent = d.website;
        document.getElementById('cm-website').href = d.website;
        document.getElementById('cm-website-wrap').style.display = 'block';
    } else {
        document.getElementById('cm-website-wrap').style.display = 'none';
    }
    document.getElementById('cm-desc-wrap').innerHTML = d.desc ? '<strong>About:</strong> ' + d.desc : '';
    var total = d.total || 1;
    document.getElementById('bar-shortlisted').style.width = (d.shortlisted/total*100) + '%';
    document.getElementById('bar-selected').style.width    = (d.selected/total*100) + '%';
    document.getElementById('bar-rejected').style.width    = (d.rejected/total*100) + '%';
    document.getElementById('bar-pending').style.width     = (d.pending/total*100) + '%';
    var modal = document.getElementById('company-modal');
    modal.style.display = 'flex';
    modal.style.position = 'fixed';
    modal.style.zIndex = '99999';
}
function closeCompanyModal() {
    document.getElementById('company-modal').style.display = 'none';
}
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('show');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('sidebarOverlay').classList.remove('show');}
function togglePostForm() {
    var form = document.getElementById('post-job-form');
    var btn  = document.getElementById('postToggleBtn');
    var open = form.style.display !== 'none';
    form.style.display = open ? 'none' : 'block';
    btn.textContent    = open ? '➕ Post Job' : '✕ Cancel';
    if (!open) form.scrollIntoView({behavior:'smooth', block:'start'});
}
const _autoOpenJob = <?= isset($_GET['open']) ? (int)$_GET['open'] : 0 ?>;
if (_autoOpenJob && _jdMap[_autoOpenJob]) showCompany(_autoOpenJob);
</script>
</body>
</html>
