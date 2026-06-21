<?php
require_once '../../includes/config.php';
requireLogin('admin');

$msg = '';

// Ensure internships table has needed columns
$conn->query("ALTER TABLE internships ADD COLUMN IF NOT EXISTS location VARCHAR(150) DEFAULT NULL");
$conn->query("ALTER TABLE internships ADD COLUMN IF NOT EXISTS allowed_streams TEXT DEFAULT NULL");
$conn->query("ALTER TABLE internships ADD COLUMN IF NOT EXISTS min_cgpa DECIMAL(4,2) DEFAULT 0");

// Post new internship
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_internship'])) {
    $title       = $conn->real_escape_string(trim($_POST['title']));
    $company_name= $conn->real_escape_string(trim($_POST['company_name_input']));
    $duration    = $conn->real_escape_string(trim($_POST['duration'] ?? ''));
    $stipend     = $conn->real_escape_string(trim($_POST['stipend'] ?? ''));
    $location    = $conn->real_escape_string(trim($_POST['location'] ?? ''));
    $description = $conn->real_escape_string(trim($_POST['description'] ?? ''));
    $requirements= $conn->real_escape_string(trim($_POST['requirements'] ?? ''));
    $streams     = $conn->real_escape_string(trim($_POST['allowed_streams'] ?? ''));
    $min_cgpa    = (float)($_POST['min_cgpa'] ?? 0);
    $deadline    = $conn->real_escape_string($_POST['deadline'] ?? '');
    $deadline_val= !empty($deadline) ? "'$deadline'" : 'NULL';

    if ($title && $company_name) {
        $cRow = $conn->query("SELECT id FROM companies WHERE company_name='$company_name' LIMIT 1")->fetch_assoc();
        if ($cRow) {
            $cid = (int)$cRow['id'];
        } else {
            $conn->query("INSERT INTO companies (company_name, user_id) VALUES ('$company_name', 0)");
            $cid = (int)$conn->insert_id;
        }
        $conn->query("INSERT INTO internships (company_id, title, duration, stipend, location, description, requirements, allowed_streams, min_cgpa, deadline, status)
            VALUES ($cid, '$title', '$duration', '$stipend', '$location', '$description', '$requirements', '$streams', $min_cgpa, $deadline_val, 'open')");
        $msg = '<div class="alert alert-success">✅ Internship posted successfully.</div>';
    } else {
        $msg = '<div class="alert alert-error">Title and company are required.</div>';
    }
}

// Issue certificate
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['issue_cert'])) {
    $app_id = (int)$_POST['app_id'];
    $stIC = $conn->prepare("UPDATE internship_applications SET status='completed', certificate_issued=1, completion_date=CURDATE() WHERE id=?");
    $stIC->bind_param('i', $app_id); $stIC->execute(); $stIC->close();
    $stApp = $conn->prepare("SELECT ia.student_id, i.title, c.company_name FROM internship_applications ia JOIN internships i ON ia.internship_id=i.id JOIN companies c ON i.company_id=c.id WHERE ia.id=?");
    $stApp->bind_param('i', $app_id); $stApp->execute();
    $app = $stApp->get_result()->fetch_assoc(); $stApp->close();
    if ($app) {
        require_once '../../includes/notify.php';
        createNotification($conn, $app['student_id'], 'system', '🏆 Internship Certificate Issued', "Your completion certificate for {$app['title']} at {$app['company_name']} is ready. Download it now!", '/placement system/student/internships/index.php');
    }
    $msg = '<div class="alert alert-success">✅ Certificate issued and student notified.</div>';
}

// Delete internship
if (isset($_GET['delete'])) {
    $iid = (int)$_GET['delete'];
    $stDel = $conn->prepare("DELETE FROM internships WHERE id=?");
    $stDel->bind_param('i', $iid); $stDel->execute(); $stDel->close();
    header("Location: index.php"); exit();
}

$stStats = $conn->prepare("SELECT (SELECT COUNT(*) FROM internships) as total, (SELECT COUNT(*) FROM internships WHERE status='open') as open_count, (SELECT COUNT(*) FROM internship_applications) as apps, (SELECT COUNT(*) FROM internship_applications WHERE status='completed') as completed");
$stStats->execute(); $statsRow = $stStats->get_result()->fetch_assoc(); $stStats->close();
$stats = ['total'=>(int)$statsRow['total'],'open'=>(int)$statsRow['open_count'],'apps'=>(int)$statsRow['apps'],'completed'=>(int)$statsRow['completed']];

$stInts = $conn->prepare("SELECT i.*, c.company_name, (SELECT COUNT(*) FROM internship_applications WHERE internship_id=i.id) as total_apps, (SELECT COUNT(*) FROM internship_applications WHERE internship_id=i.id AND status='completed') as completed_count FROM internships i JOIN companies c ON i.company_id=c.id ORDER BY i.created_at DESC");
$stInts->execute(); $internships = $stInts->get_result(); $stInts->close();

$view_iid = (int)($_GET['view'] ?? 0);
$applications = null; $view_intern = null;
if ($view_iid) {
    $stVI = $conn->prepare("SELECT i.*, c.company_name FROM internships i JOIN companies c ON i.company_id=c.id WHERE i.id=?");
    $stVI->bind_param('i', $view_iid); $stVI->execute();
    $view_intern = $stVI->get_result()->fetch_assoc(); $stVI->close();
    if ($view_intern) {
        $stVA = $conn->prepare("SELECT ia.*, u.name, u.email, sp.cgpa, sp.department FROM internship_applications ia JOIN users u ON ia.student_id=u.id LEFT JOIN student_profiles sp ON sp.user_id=u.id WHERE ia.internship_id=? ORDER BY ia.applied_at DESC");
        $stVA->bind_param('i', $view_iid); $stVA->execute();
        $applications = $stVA->get_result(); $stVA->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Internship Management - Admin</title>
<link rel="stylesheet" href="../../css/style.css">
<style>
.clickable-row{cursor:pointer}
.clickable-row:hover{background:#f5f0ff}
.modal-overlay.open{display:flex!important}
</style>
</head>
<body>
<?php require_once '../sidebar.php'; ?>
<div class="topbar">
    <div class="topbar-left">
        <button class="hamburger" onclick="toggleSidebar()">☰</button>
        <span class="page-title">🏢 Internship Management</span>
    </div>
    <div class="topbar-right"><?php require_once '../../notifications/widget.php'; ?></div>
</div>
<div class="main-content">
    <?= $msg ?>

    <div class="card" style="background:linear-gradient(135deg,#4a148c,#7b1fa2);color:#fff;margin-bottom:25px">
        <h2 style="color:#ffd54f;margin-bottom:8px">🏢 Internship Management</h2>
        <p style="color:#ce93d8">Monitor all internships, manage applications, and issue completion certificates.</p>
    </div>

    <div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:25px">
        <div class="stat-card"><div class="number"><?= $stats['total'] ?></div><div class="label">🏢 Total Internships</div></div>
        <div class="stat-card green"><div class="number"><?= $stats['open'] ?></div><div class="label">✅ Open</div></div>
        <div class="stat-card orange"><div class="number"><?= $stats['apps'] ?></div><div class="label">📋 Applications</div></div>
        <div class="stat-card" style="border-left-color:#7b1fa2"><div class="number"><?= $stats['completed'] ?></div><div class="label">🏆 Completed</div></div>
    </div>

    <!-- Post Internship Form -->
    <div class="card" style="margin-bottom:20px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
            <h2 style="margin:0">➕ Post a New Internship</h2>
            <button onclick="togglePostForm()" id="postToggleBtn" class="btn btn-primary btn-sm">➕ Post Internship</button>
        </div>
        <div id="post-intern-form" style="display:none">
            <form method="POST">
                <input type="hidden" name="post_internship" value="1">
                <div class="form-row">
                    <div class="form-group">
                        <label>Internship Title *</label>
                        <input type="text" name="title" placeholder="e.g. Web Development Intern" required>
                    </div>
                    <div class="form-group">
                        <label>Company *</label>
                        <input type="text" name="company_name_input" list="companies_list_intern" placeholder="Type or select company..." required
                            style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:6px;font-size:0.95rem">
                        <datalist id="companies_list_intern">
                            <?php
                            $cList = $conn->query("SELECT company_name FROM companies ORDER BY company_name");
                            while($c=$cList->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($c['company_name']) ?>"></option>
                            <?php endwhile; ?>
                        </datalist>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Duration</label>
                        <input type="text" name="duration" placeholder="e.g. 3 Months, 6 Weeks">
                    </div>
                    <div class="form-group">
                        <label>Stipend</label>
                        <input type="text" name="stipend" placeholder="e.g. ₹5000/month or Unpaid">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="location" placeholder="e.g. Hyderabad, Remote">
                    </div>
                    <div class="form-group">
                        <label>Min CGPA</label>
                        <input type="number" name="min_cgpa" step="0.1" min="0" max="10" value="0">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Application Deadline</label>
                        <input type="date" name="deadline" min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label>Allowed Streams <small style="color:#999">(comma separated)</small></label>
                        <input type="text" name="allowed_streams" placeholder="e.g. Computer Science, MCA">
                    </div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3" placeholder="Describe the internship role and responsibilities..."></textarea>
                </div>
                <div class="form-group">
                    <label>Requirements / Skills</label>
                    <textarea name="requirements" rows="2" placeholder="e.g. Python, Django, Communication Skills"></textarea>
                </div>
                <div style="display:flex;gap:10px">
                    <button type="submit" class="btn btn-primary">📤 Post Internship</button>
                    <button type="button" onclick="togglePostForm()" class="btn" style="background:#e8eaf6;color:#333">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($view_intern && $applications): ?>
    <div class="card">
        <h2>📋 Applications — <?= htmlspecialchars($view_intern['title']) ?> @ <?= htmlspecialchars($view_intern['company_name']) ?>
            <a href="index.php" class="btn btn-sm" style="float:right;background:#e8eaf6;color:#333">← Back</a>
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
                    <td>
                        <?php if ($a['certificate_issued']): ?>
                        <span style="color:#2e7d32;font-weight:700">🏆 Issued</span>
                        <?php else: ?>
                        <span style="color:#999">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($a['status'] === 'selected' && !$a['certificate_issued']): ?>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="app_id" value="<?= $a['id'] ?>">
                            <button name="issue_cert" class="btn btn-success btn-sm" onclick="return confirm('Issue certificate to <?= htmlspecialchars($a['name']) ?>?')">
                                🏆 Issue Cert
                            </button>
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

    <div class="card">
        <h2>All Internships</h2>
        <?php if ($internships->num_rows === 0): ?>
        <p style="color:#999;text-align:center;padding:30px">No internships posted yet.</p>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <tr><th>Title</th><th>Company</th><th>Duration</th><th>Stipend</th><th>Status</th><th>Applications</th><th>Completed</th><th>Deadline</th><th>Actions</th></tr>
                <?php $internData = []; while($i = $internships->fetch_assoc()): $internData[] = $i; ?>
                <tr class="clickable-row" onclick="openInternPopup(<?= $i['id'] ?>)" title="View internship details">
                    <td style="color:#6a1b9a;font-weight:600"><?= htmlspecialchars($i['title']) ?></td>
                    <td><?= htmlspecialchars($i['company_name']) ?></td>
                    <td><?= htmlspecialchars($i['duration'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($i['stipend'] ?? '-') ?></td>
                    <td><span class="badge badge-<?= $i['status'] ?>"><?= ucfirst($i['status']) ?></span></td>
                    <td><strong style="color:#3f51b5"><?= $i['total_apps'] ?></strong></td>
                    <td><strong style="color:#2e7d32"><?= $i['completed_count'] ?></strong></td>
                    <td><?= $i['deadline'] ? date('d M Y', strtotime($i['deadline'])) : '—' ?></td>
                    <td onclick="event.stopPropagation()" style="display:flex;gap:5px;flex-wrap:wrap">
                        <a href="?view=<?= $i['id'] ?>" class="btn btn-primary btn-sm">Applications</a>
                        <a href="?delete=<?= $i['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this internship?')">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Internship Detail Popup -->
<div class="modal-overlay" id="internPopup" onclick="if(event.target===this)closeInternPopup()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:14px;width:580px;max-width:95vw;max-height:90vh;overflow-y:auto;box-shadow:0 8px 40px rgba(0,0,0,0.2)">
        <div style="background:linear-gradient(135deg,#4a148c,#7b1fa2);padding:22px 24px;border-radius:14px 14px 0 0;display:flex;justify-content:space-between;align-items:flex-start">
            <div>
                <div id="ip-title" style="font-size:1.2rem;font-weight:800;color:#ffd54f"></div>
                <div id="ip-company" style="color:#ce93d8;font-size:0.88rem;margin-top:3px"></div>
            </div>
            <button onclick="closeInternPopup()" style="background:rgba(255,255,255,0.15);border:none;color:#fff;width:32px;height:32px;border-radius:50%;cursor:pointer;font-size:1.1rem">&times;</button>
        </div>
        <div style="padding:20px 24px">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px">
                <div style="background:#f5f6fa;border-radius:8px;padding:10px 14px"><div style="font-size:0.7rem;color:#888;font-weight:600;text-transform:uppercase">Duration</div><div id="ip-duration" style="font-size:0.95rem;font-weight:700;color:#1a237e;margin-top:2px"></div></div>
                <div style="background:#f5f6fa;border-radius:8px;padding:10px 14px"><div style="font-size:0.7rem;color:#888;font-weight:600;text-transform:uppercase">Stipend</div><div id="ip-stipend" style="font-size:0.95rem;font-weight:700;color:#1a237e;margin-top:2px"></div></div>
                <div style="background:#f5f6fa;border-radius:8px;padding:10px 14px"><div style="font-size:0.7rem;color:#888;font-weight:600;text-transform:uppercase">Location</div><div id="ip-location" style="font-size:0.95rem;font-weight:700;color:#1a237e;margin-top:2px"></div></div>
                <div style="background:#f5f6fa;border-radius:8px;padding:10px 14px"><div style="font-size:0.7rem;color:#888;font-weight:600;text-transform:uppercase">Min CGPA</div><div id="ip-cgpa" style="font-size:0.95rem;font-weight:700;color:#1a237e;margin-top:2px"></div></div>
                <div style="background:#f5f6fa;border-radius:8px;padding:10px 14px"><div style="font-size:0.7rem;color:#888;font-weight:600;text-transform:uppercase">Applications</div><div id="ip-apps" style="font-size:0.95rem;font-weight:700;color:#3f51b5;margin-top:2px"></div></div>
                <div style="background:#f5f6fa;border-radius:8px;padding:10px 14px"><div style="font-size:0.7rem;color:#888;font-weight:600;text-transform:uppercase">Completed</div><div id="ip-completed" style="font-size:0.95rem;font-weight:700;color:#2e7d32;margin-top:2px"></div></div>
            </div>
            <div id="ip-streams-wrap" style="margin-bottom:10px;font-size:0.85rem;color:#555"></div>
            <div id="ip-desc-wrap" style="margin-bottom:10px;font-size:0.85rem;color:#555"></div>
            <div id="ip-req-wrap" style="margin-bottom:14px;font-size:0.85rem;color:#555"></div>
            <div style="display:flex;align-items:center;justify-content:space-between">
                <div id="ip-deadline" style="font-size:0.82rem;color:#999"></div>
                <a id="ip-view-link" href="#" style="padding:7px 16px;background:#4a148c;color:#fff;border-radius:6px;font-size:0.85rem;font-weight:700;text-decoration:none">View Applications →</a>
            </div>
        </div>
    </div>
</div>

</div>
</div><!-- app-layout -->
<?php require_once '../../chatbot/widget.php'; ?>
<script>
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('show');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('sidebarOverlay').classList.remove('show');}
function togglePostForm() {
    var form = document.getElementById('post-intern-form');
    var btn  = document.getElementById('postToggleBtn');
    var open = form.style.display !== 'none';
    form.style.display = open ? 'none' : 'block';
    btn.textContent    = open ? '➕ Post Internship' : '✕ Cancel';
    if (!open) form.scrollIntoView({behavior:'smooth', block:'start'});
}
const _idata = <?= isset($internData) ? json_encode(array_map(function($i){
    return [
        'id'        => (int)$i['id'],
        'title'     => $i['title'],
        'company'   => $i['company_name'],
        'duration'  => $i['duration'] ?? '',
        'stipend'   => $i['stipend'] ?? '',
        'location'  => $i['location'] ?? '',
        'cgpa'      => $i['min_cgpa'] ?? '0',
        'desc'      => $i['description'] ?? '',
        'req'       => $i['requirements'] ?? '',
        'streams'   => $i['allowed_streams'] ?? '',
        'apps'      => (int)$i['total_apps'],
        'completed' => (int)$i['completed_count'],
        'deadline'  => $i['deadline'] ?? '',
        'status'    => $i['status'],
    ];
}, $internData), JSON_HEX_TAG) : '[]' ?>;
const _iidx = {}; _idata.forEach(i => _iidx[i.id] = i);
function openInternPopup(id) {
    const i = _iidx[id]; if (!i) return;
    document.getElementById('ip-title').textContent    = i.title;
    document.getElementById('ip-company').textContent  = '🏢 ' + i.company;
    document.getElementById('ip-duration').textContent = i.duration || '—';
    document.getElementById('ip-stipend').textContent  = i.stipend  || '—';
    document.getElementById('ip-location').textContent = i.location || '—';
    document.getElementById('ip-cgpa').textContent     = i.cgpa > 0 ? i.cgpa : 'Any';
    document.getElementById('ip-apps').textContent     = i.apps;
    document.getElementById('ip-completed').textContent= i.completed;
    document.getElementById('ip-deadline').textContent = i.deadline ? 'Deadline: ' + i.deadline : 'No deadline';
    document.getElementById('ip-streams-wrap').innerHTML = i.streams ? '<strong>Allowed Streams:</strong> ' + i.streams : '';
    document.getElementById('ip-desc-wrap').innerHTML  = i.desc ? '<strong>Description:</strong> ' + i.desc : '';
    document.getElementById('ip-req-wrap').innerHTML   = i.req  ? '<strong>Requirements:</strong> ' + i.req  : '';
    document.getElementById('ip-view-link').href = 'index.php?view=' + id;
    const pop = document.getElementById('internPopup');
    pop.style.display = 'flex';
}
function closeInternPopup() {
    document.getElementById('internPopup').style.display = 'none';
}
</script>
</body>
</html>
