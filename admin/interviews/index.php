<?php
require_once '../../includes/config.php';
requireLogin('admin');
require_once '../../includes/notify.php';

// Ensure columns exist
$conn->query("ALTER TABLE interviews ADD COLUMN IF NOT EXISTS minutes TEXT DEFAULT NULL");
$conn->query("ALTER TABLE interviews ADD COLUMN IF NOT EXISTS recording_url VARCHAR(500) DEFAULT NULL");
$conn->query("CREATE TABLE IF NOT EXISTS scheduled_tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_id INT NOT NULL,
    student_id INT NOT NULL,
    scheduled_at DATETIME NOT NULL,
    note TEXT,
    status ENUM('pending','completed','missed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
)");

$msg = '';

// Schedule test for student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule_test'])) {
    $student_id = (int)$_POST['student_id'];
    $test_id    = (int)$_POST['test_id'];
    $scheduled  = trim($_POST['test_scheduled_at'] ?? '');
    $note       = trim($_POST['test_note'] ?? '');
    $stEx = $conn->prepare("SELECT id FROM scheduled_tests WHERE student_id=? AND test_id=?");
    $stEx->bind_param('ii', $student_id, $test_id); $stEx->execute(); $stEx->store_result();
    if ($stEx->num_rows > 0) {
        $stUp = $conn->prepare("UPDATE scheduled_tests SET scheduled_at=?, note=?, status='pending' WHERE student_id=? AND test_id=?");
        $stUp->bind_param('ssii', $scheduled, $note, $student_id, $test_id); $stUp->execute(); $stUp->close();
    } else {
        $stIn = $conn->prepare("INSERT INTO scheduled_tests (test_id, student_id, scheduled_at, note) VALUES (?,?,?,?)");
        $stIn->bind_param('iiss', $test_id, $student_id, $scheduled, $note); $stIn->execute(); $stIn->close();
    }
    $stEx->close();
    $stTI = $conn->prepare("SELECT title FROM tests WHERE id=?");
    $stTI->bind_param('i', $test_id); $stTI->execute();
    $tInfo = $stTI->get_result()->fetch_assoc(); $stTI->close();
    $notifMsg = "A test '{$tInfo['title']}' has been scheduled for you on " . date('d M Y h:i A', strtotime($scheduled));
    $stNot = $conn->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'test', 'Test Scheduled', ?)");
    $stNot->bind_param('is', $student_id, $notifMsg); $stNot->execute(); $stNot->close();
    $msg = '<div class="alert alert-success">✅ Test scheduled for student! Student has been notified.</div>';
}

// Delete scheduled test
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_scheduled_test'])) {
    $stid = (int)$_POST['st_id'];
    $conn->query("DELETE FROM scheduled_tests WHERE id=$stid");
    $msg = '<div class="alert alert-success">Scheduled test removed.</div>';
}

// Schedule interview
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule'])) {
    $app_id     = (int)$_POST['application_id'];
    $student_id = (int)$_POST['student_id'];
    $job_id     = (int)$_POST['job_id'];
    $company_id = (int)$_POST['company_id'];
    $scheduled  = trim($_POST['scheduled_at'] ?? '');
    $duration   = (int)$_POST['duration'];
    $platform   = trim($_POST['platform'] ?? '');
    $link       = trim($_POST['meeting_link'] ?? '');
    $notes      = trim($_POST['notes'] ?? '');

    if (empty($link) && $platform === 'jitsi') {
        $room = 'CampusRecruit-' . strtoupper(substr(md5($app_id . time()), 0, 8));
        $link = "https://meet.jit.si/$room";
    }
    $stSch = $conn->prepare("INSERT INTO interviews (application_id,job_id,student_id,company_id,scheduled_at,duration,meeting_link,platform,notes) VALUES (?,?,?,?,?,?,?,?,?)");
    $stSch->bind_param('iiiisisss', $app_id, $job_id, $student_id, $company_id, $scheduled, $duration, $link, $platform, $notes);
    $stSch->execute(); $stSch->close();
    $stUpA = $conn->prepare("UPDATE applications SET status='shortlisted' WHERE id=?");
    $stUpA->bind_param('i', $app_id); $stUpA->execute(); $stUpA->close();
    $stJI = $conn->prepare("SELECT j.title, c.company_name FROM jobs j JOIN companies c ON j.company_id=c.id WHERE j.id=?");
    $stJI->bind_param('i', $job_id); $stJI->execute();
    $jobInfo = $stJI->get_result()->fetch_assoc(); $stJI->close();
    if ($jobInfo) notifyInterviewScheduled($conn, $student_id, $jobInfo['title'], $jobInfo['company_name'], $scheduled);
    $msg = '<div class="alert alert-success">✅ Interview scheduled! Student has been notified.</div>';
}

// Update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $iid    = (int)$_POST['interview_id'];
    $status = trim($_POST['status'] ?? '');
    if (in_array($status, ['scheduled','completed','cancelled','rescheduled'])) {
        $stUS = $conn->prepare("UPDATE interviews SET status=? WHERE id=?");
        $stUS->bind_param('si', $status, $iid); $stUS->execute(); $stUS->close();
        $msg = '<div class="alert alert-success">Status updated.</div>';
    }
}

// Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_interview'])) {
    $iid = (int)$_POST['interview_id'];
    $stDI = $conn->prepare("DELETE FROM interviews WHERE id=?");
    $stDI->bind_param('i', $iid); $stDI->execute(); $stDI->close();
    $msg = '<div class="alert alert-success">Interview deleted.</div>';
}

$allowedFilters = ['all','scheduled','completed','cancelled','rescheduled'];
$filter = isset($_GET['status']) && in_array($_GET['status'], $allowedFilters) ? $_GET['status'] : 'all';
$ivSql  = "SELECT i.*, u.name as student_name, j.title as job_title, c.company_name, sp.department, sp.cgpa, a.id as app_id FROM interviews i JOIN users u ON i.student_id=u.id LEFT JOIN student_profiles sp ON sp.user_id=u.id JOIN jobs j ON i.job_id=j.id JOIN companies c ON i.company_id=c.id JOIN applications a ON i.application_id=a.id";
if ($filter !== 'all') {
    $stIV = $conn->prepare($ivSql . " WHERE i.status=? ORDER BY i.scheduled_at DESC");
    $stIV->bind_param('s', $filter);
} else {
    $stIV = $conn->prepare($ivSql . " ORDER BY i.scheduled_at DESC");
}
$stIV->execute(); $interviews = $stIV->get_result(); $stIV->close();

$stats = [
    'total'     => $conn->query("SELECT COUNT(*) as c FROM interviews")->fetch_assoc()['c'],
    'scheduled' => $conn->query("SELECT COUNT(*) as c FROM interviews WHERE status='scheduled'")->fetch_assoc()['c'],
    'completed' => $conn->query("SELECT COUNT(*) as c FROM interviews WHERE status='completed'")->fetch_assoc()['c'],
    'cancelled' => $conn->query("SELECT COUNT(*) as c FROM interviews WHERE status='cancelled'")->fetch_assoc()['c'],
];

// All shortlisted/applied applicants not yet interviewed (for schedule form)
$applicants = $conn->query("SELECT a.id as app_id, a.student_id, a.job_id,
        u.name as student_name, u.email, sp.department, sp.cgpa,
        j.title as job_title, j.company_id, c.company_name
    FROM applications a
    JOIN users u ON a.student_id=u.id
    LEFT JOIN student_profiles sp ON sp.user_id=u.id
    JOIN jobs j ON a.job_id=j.id
    JOIN companies c ON j.company_id=c.id
    WHERE a.status IN ('applied','shortlisted')
    AND a.id NOT IN (SELECT application_id FROM interviews WHERE status NOT IN ('cancelled'))
    ORDER BY u.name ASC");

// Students list for test scheduling
$all_students = $conn->query("SELECT u.id, u.name, u.email, sp.department FROM users u LEFT JOIN student_profiles sp ON u.id=sp.user_id WHERE u.role='student' ORDER BY u.name ASC");
$all_tests    = $conn->query("SELECT id, title, category, duration FROM tests WHERE status='active' ORDER BY title ASC");

// All scheduled tests
$all_scheduled_tests = $conn->query("SELECT st.*, u.name as student_name, t.title as test_title, t.category
    FROM scheduled_tests st
    JOIN users u ON st.student_id=u.id
    JOIN tests t ON st.test_id=t.id
    ORDER BY st.scheduled_at DESC");

$platformIcons = ['google_meet'=>'🟢','zoom'=>'🔵','teams'=>'🟣','jitsi'=>'🟠','other'=>'⚪'];
$platformNames = ['google_meet'=>'Google Meet','zoom'=>'Zoom','teams'=>'MS Teams','jitsi'=>'Jitsi Meet','other'=>'Other'];
$statusColors  = ['scheduled'=>'#1565c0','completed'=>'#2e7d32','cancelled'=>'#c62828','rescheduled'=>'#e65100'];
$statusBg      = ['scheduled'=>'#e3f2fd','completed'=>'#e8f5e9','cancelled'=>'#ffebee','rescheduled'=>'#fff8e1'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Interviews - Admin</title>
<link rel="stylesheet" href="../../css/style.css">
<style>
.interview-card{background:#fff;border-radius:10px;padding:18px;box-shadow:0 2px 10px rgba(0,0,0,0.07);margin-bottom:14px;border-left:5px solid #3f51b5}
.platform-badge{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:0.8rem;font-weight:700;background:#f5f5f5;color:#333}
.status-badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:0.8rem;font-weight:700}
.meet-btn{display:inline-flex;align-items:center;gap:5px;padding:6px 14px;border-radius:6px;background:linear-gradient(135deg,#1a237e,#3949ab);color:#fff;text-decoration:none;font-weight:700;font-size:0.85rem}
.applicant-row{display:flex;justify-content:space-between;align-items:center;padding:10px 14px;border:1px solid #e0e0e0;border-radius:8px;margin-bottom:7px;flex-wrap:wrap;gap:8px}
.applicant-row:hover{background:#f8f9ff;border-color:#c5cae9}
</style>
</head>
<body>
<?php require_once '../sidebar.php'; ?>

<div class="topbar">
    <div class="topbar-left">
        <button class="hamburger" onclick="toggleSidebar()">☰</button>
        <span class="page-title">🎥 Interview Management</span>
    </div>
    <div class="topbar-right">
        <?php require_once '../../notifications/widget.php'; ?>
    </div>
</div>

<div class="main-content">
    <?= $msg ?>

    <!-- Stats -->
    <div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:22px">
        <div class="stat-card"><div class="number"><?= $stats['total'] ?></div><div class="label">🎥 Total Interviews</div></div>
        <div class="stat-card"><div class="number"><?= $stats['scheduled'] ?></div><div class="label">📅 Scheduled</div></div>
        <div class="stat-card green"><div class="number"><?= $stats['completed'] ?></div><div class="label">✅ Completed</div></div>
        <div class="stat-card red"><div class="number"><?= $stats['cancelled'] ?></div><div class="label">❌ Cancelled</div></div>
    </div>

    <!-- Schedule New Interview -->
    <?php if ($applicants->num_rows > 0): ?>
    <div class="card" style="margin-bottom:22px">
        <h2>📅 Schedule New Interview</h2>
        <p style="color:#666;font-size:0.9rem;margin-bottom:15px">Select an applicant to schedule their interview.</p>
        <?php while($s = $applicants->fetch_assoc()): ?>
        <div class="applicant-row">
            <div>
                <strong style="color:#1a237e"><?= htmlspecialchars($s['student_name']) ?></strong>
                <span style="color:#666;font-size:0.83rem;margin-left:8px"><?= htmlspecialchars($s['email']) ?></span>
                <div style="font-size:0.8rem;color:#555;margin-top:2px">
                    💼 <?= htmlspecialchars($s['job_title']) ?> — 🏢 <?= htmlspecialchars($s['company_name']) ?>
                    <?php if ($s['department']): ?> · 🎓 <?= htmlspecialchars($s['department']) ?><?php endif; ?>
                    <?php if ($s['cgpa']): ?> · 📊 <?= $s['cgpa'] ?><?php endif; ?>
                </div>
            </div>
            <button class="btn btn-primary btn-sm"
                onclick="openModal(<?= $s['app_id'] ?>,<?= $s['student_id'] ?>,<?= $s['job_id'] ?>,<?= $s['company_id'] ?>,'<?= htmlspecialchars(addslashes($s['student_name'])) ?>','<?= htmlspecialchars(addslashes($s['job_title'])) ?>','<?= htmlspecialchars(addslashes($s['company_name'])) ?>')">
                📅 Schedule
            </button>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>

    <!-- Schedule Modal -->
    <div id="schedule-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center">
        <div style="background:#fff;border-radius:12px;padding:28px;width:100%;max-width:540px;max-height:90vh;overflow-y:auto;margin:20px">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px">
                <h3 style="color:#1a237e">📅 Schedule Interview</h3>
                <button onclick="closeModal()" style="background:none;border:none;font-size:1.4rem;cursor:pointer;color:#666">✕</button>
            </div>
            <div id="modal-info" style="background:#e8eaf6;border-radius:8px;padding:10px;margin-bottom:16px;font-size:0.88rem;color:#1a237e"></div>
            <form method="POST">
                <input type="hidden" name="schedule" value="1">
                <input type="hidden" name="application_id" id="modal-app-id">
                <input type="hidden" name="student_id" id="modal-student-id">
                <input type="hidden" name="job_id" id="modal-job-id">
                <input type="hidden" name="company_id" id="modal-company-id">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="form-group">
                        <label>Date & Time *</label>
                        <input type="datetime-local" name="scheduled_at" required min="<?= date('Y-m-d\TH:i') ?>">
                    </div>
                    <div class="form-group">
                        <label>Duration</label>
                        <select name="duration">
                            <option value="30">30 min</option>
                            <option value="45">45 min</option>
                            <option value="60" selected>60 min</option>
                            <option value="90">90 min</option>
                            <option value="120">2 hours</option>
                        </select>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="form-group">
                        <label>Platform</label>
                        <select name="platform" id="platform-select" onchange="updateHint()">
                            <option value="jitsi">🟠 Jitsi (Auto-generated)</option>
                            <option value="google_meet">🟢 Google Meet</option>
                            <option value="zoom">🔵 Zoom</option>
                            <option value="teams">🟣 MS Teams</option>
                            <option value="other">⚪ Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Meeting Link</label>
                        <input type="url" name="meeting_link" id="link-input" placeholder="Leave empty for Jitsi auto-generate">
                        <small id="link-hint" style="color:#3f51b5;font-size:0.78rem">Jitsi link will be auto-generated</small>
                    </div>
                </div>
                <div class="form-group">
                    <label>Notes for Student</label>
                    <textarea name="notes" rows="3" placeholder="e.g. Please join 5 minutes early, have your resume ready..."></textarea>
                </div>
                <div style="display:flex;gap:10px">
                    <button type="submit" class="btn btn-primary">📅 Schedule Interview</button>
                    <button type="button" onclick="closeModal()" class="btn" style="background:#607d8b;color:#fff">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- All Interviews -->
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:18px">
            <h2 style="margin:0">📋 All Interviews</h2>
            <div style="display:flex;gap:6px;flex-wrap:wrap">
                <?php foreach (['all'=>'All','scheduled'=>'📅 Scheduled','completed'=>'✅ Completed','cancelled'=>'❌ Cancelled','rescheduled'=>'🔄 Rescheduled'] as $val=>$label): ?>
                <a href="?status=<?= $val ?>" class="btn btn-sm <?= $filter===$val?'btn-primary':'btn-warning' ?>"><?= $label ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($interviews->num_rows === 0): ?>
        <p style="color:#999;text-align:center;padding:30px">No interviews found.</p>
        <?php else: ?>
        <?php while($iv = $interviews->fetch_assoc()): ?>
        <div class="interview-card" style="border-left-color:<?= $statusColors[$iv['status']] ?>">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px">
                <div style="flex:1">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:5px">
                        <strong style="color:#1a237e"><?= htmlspecialchars($iv['student_name']) ?></strong>
                        <span class="status-badge" style="background:<?= $statusBg[$iv['status']] ?>;color:<?= $statusColors[$iv['status']] ?>"><?= ucfirst($iv['status']) ?></span>
                        <span class="platform-badge"><?= $platformIcons[$iv['platform']] ?> <?= $platformNames[$iv['platform']] ?></span>
                    </div>
                    <div style="font-size:0.85rem;color:#555;margin-bottom:6px">
                        💼 <?= htmlspecialchars($iv['job_title']) ?> — 🏢 <?= htmlspecialchars($iv['company_name']) ?>
                        <?php if ($iv['department']): ?> · 🎓 <?= htmlspecialchars($iv['department']) ?><?php endif; ?>
                        <?php if ($iv['cgpa']): ?> · 📊 CGPA: <?= $iv['cgpa'] ?><?php endif; ?>
                    </div>
                    <div style="display:flex;gap:14px;flex-wrap:wrap;font-size:0.83rem;color:#333;margin-bottom:8px">
                        <span>📅 <?= date('D, d M Y', strtotime($iv['scheduled_at'])) ?></span>
                        <span>🕐 <?= date('h:i A', strtotime($iv['scheduled_at'])) ?></span>
                        <span>⏱️ <?= $iv['duration'] ?> min</span>
                    </div>
                    <?php if ($iv['notes']): ?>
                    <div style="background:#f5f5f5;border-radius:6px;padding:7px 10px;font-size:0.83rem;color:#555;margin-bottom:8px">
                        📝 <?= htmlspecialchars($iv['notes']) ?>
                    </div>
                    <?php endif; ?>
                    <div style="display:flex;gap:8px;flex-wrap:wrap">
                        <?php if ($iv['meeting_link']): ?>
                        <a href="<?= htmlspecialchars($iv['meeting_link']) ?>" target="_blank" class="meet-btn">
                            <?= $platformIcons[$iv['platform']] ?> Join Meeting
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($iv['recording_url'])): ?>
                        <a href="<?= htmlspecialchars($iv['recording_url']) ?>" target="_blank"
                           style="display:inline-flex;align-items:center;gap:5px;padding:6px 14px;border-radius:6px;background:#e3f2fd;color:#1565c0;text-decoration:none;font-size:0.83rem;font-weight:700">
                            🎬 View Recording
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="display:flex;flex-direction:column;gap:5px;min-width:130px">
                    <form method="POST" style="display:flex;flex-direction:column;gap:5px">
                        <input type="hidden" name="interview_id" value="<?= $iv['id'] ?>">
                        <select name="status" style="padding:5px 7px;border:1px solid #ddd;border-radius:5px;font-size:0.82rem">
                            <option value="scheduled"   <?= $iv['status']==='scheduled'   ?'selected':'' ?>>Scheduled</option>
                            <option value="completed"   <?= $iv['status']==='completed'   ?'selected':'' ?>>Completed</option>
                            <option value="rescheduled" <?= $iv['status']==='rescheduled' ?'selected':'' ?>>Rescheduled</option>
                            <option value="cancelled"   <?= $iv['status']==='cancelled'   ?'selected':'' ?>>Cancelled</option>
                        </select>
                        <button name="update_status" class="btn btn-success btn-sm">Update</button>
                    </form>
                    <form method="POST" onsubmit="return confirm('Delete this interview?')">
                        <input type="hidden" name="interview_id" value="<?= $iv['id'] ?>">
                        <button name="delete_interview" class="btn btn-danger btn-sm" style="width:100%">Delete</button>
                    </form>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
        <?php endif; ?>
    </div>

    <!-- ── SCHEDULE TEST FOR STUDENT ── -->
    <div class="card" style="margin-top:22px">
        <h2>📝 Schedule Test for Student</h2>
        <p style="color:#666;font-size:0.9rem;margin-bottom:15px">Assign a specific test to a student with a scheduled date/time. It will appear in the student's Interviews & Tests page.</p>
        <form method="POST" style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr auto;gap:12px;align-items:end;flex-wrap:wrap">
            <input type="hidden" name="schedule_test" value="1">
            <div class="form-group" style="margin:0">
                <label>Student *</label>
                <select name="student_id" required style="width:100%">
                    <option value="">-- Select Student --</option>
                    <?php while($s = $all_students->fetch_assoc()): ?>
                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?> (<?= htmlspecialchars($s['department'] ?? 'N/A') ?>)</option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group" style="margin:0">
                <label>Test *</label>
                <select name="test_id" required style="width:100%">
                    <option value="">-- Select Test --</option>
                    <?php while($t = $all_tests->fetch_assoc()): ?>
                    <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['title']) ?> (<?= ucfirst($t['category']) ?>, <?= $t['duration'] ?>min)</option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group" style="margin:0">
                <label>Scheduled Date & Time *</label>
                <input type="datetime-local" name="test_scheduled_at" required min="<?= date('Y-m-d\TH:i') ?>">
            </div>
            <div class="form-group" style="margin:0">
                <label>Note for Student</label>
                <input type="text" name="test_note" placeholder="Optional instructions...">
            </div>
            <button type="submit" class="btn btn-primary" style="white-space:nowrap">📝 Assign Test</button>
        </form>
    </div>

    <!-- All Scheduled Tests -->
    <div class="card" style="margin-top:18px">
        <h2>📝 All Scheduled Tests</h2>
        <?php if ($all_scheduled_tests->num_rows === 0): ?>
        <p style="color:#999;text-align:center;padding:20px">No tests scheduled yet.</p>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <tr><th>Student</th><th>Test</th><th>Category</th><th>Scheduled At</th><th>Status</th><th>Action</th></tr>
                <?php while($st = $all_scheduled_tests->fetch_assoc()):
                    $stColor = ['pending'=>'#f57f17','completed'=>'#2e7d32','missed'=>'#c62828'];
                    $stBg    = ['pending'=>'#fff8e1','completed'=>'#e8f5e9','missed'=>'#ffebee'];
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($st['student_name']) ?></strong></td>
                    <td><?= htmlspecialchars($st['test_title']) ?></td>
                    <td><span style="background:#e8eaf6;color:#3f51b5;padding:2px 8px;border-radius:10px;font-size:0.8rem"><?= ucfirst($st['category']) ?></span></td>
                    <td><?= date('d M Y, h:i A', strtotime($st['scheduled_at'])) ?></td>
                    <td><span style="background:<?= $stBg[$st['status']] ?>;color:<?= $stColor[$st['status']] ?>;padding:3px 10px;border-radius:12px;font-size:0.8rem;font-weight:700"><?= ucfirst($st['status']) ?></span></td>
                    <td>
                        <form method="POST" onsubmit="return confirm('Remove this scheduled test?')" style="display:inline">
                            <input type="hidden" name="st_id" value="<?= $st['id'] ?>">
                            <button name="delete_scheduled_test" class="btn btn-danger btn-sm">Remove</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
        <?php endif; ?>
    </div>

</div><!-- main-content -->
</div><!-- app-layout -->

<?php require_once '../../chatbot/widget.php'; ?>
<script>
function openModal(appId, studentId, jobId, companyId, studentName, jobTitle, companyName) {
    document.getElementById('modal-app-id').value = appId;
    document.getElementById('modal-student-id').value = studentId;
    document.getElementById('modal-job-id').value = jobId;
    document.getElementById('modal-company-id').value = companyId;
    document.getElementById('modal-info').innerHTML =
        '👤 <strong>' + studentName + '</strong> — ' + jobTitle + ' @ ' + companyName;
    document.getElementById('schedule-modal').style.display = 'flex';
}
function closeModal() { document.getElementById('schedule-modal').style.display = 'none'; }
function updateHint() {
    const p = document.getElementById('platform-select').value;
    const hint = document.getElementById('link-hint');
    const input = document.getElementById('link-input');
    if (p === 'jitsi') {
        hint.textContent = 'Jitsi link will be auto-generated if left empty';
        input.placeholder = 'Leave empty for auto-generate';
    } else {
        hint.textContent = 'Paste your meeting link';
        input.placeholder = 'https://...';
    }
}
document.getElementById('schedule-modal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
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
