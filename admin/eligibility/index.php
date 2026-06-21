<?php
require_once '../../includes/config.php';
requireLogin('admin');

$conn->query("CREATE TABLE IF NOT EXISTS eligibility_criteria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    min_cgpa DECIMAL(4,2) DEFAULT 6.00,
    min_attendance DECIMAL(5,2) DEFAULT 75.00,
    max_backlogs INT DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");
$conn->query("CREATE TABLE IF NOT EXISTS student_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    attendance_pct DECIMAL(5,2) DEFAULT 0,
    backlogs INT DEFAULT 0,
    placement_approval ENUM('pending','approved','rejected') DEFAULT 'pending',
    approval_note TEXT DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");
// Add approval columns if missing
$conn->query("ALTER TABLE student_attendance ADD COLUMN IF NOT EXISTS placement_approval ENUM('pending','approved','rejected') DEFAULT 'pending'");
$conn->query("ALTER TABLE student_attendance ADD COLUMN IF NOT EXISTS approval_note TEXT DEFAULT NULL");

if ($conn->query("SELECT COUNT(*) as c FROM eligibility_criteria")->fetch_assoc()['c'] == 0) {
    $conn->query("INSERT INTO eligibility_criteria (min_cgpa, min_attendance, max_backlogs) VALUES (6.00, 75.00, 0)");
}

$msg = '';

// Update criteria
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_criteria'])) {
    $minCgpa       = (float)$_POST['min_cgpa'];
    $minAttendance = (float)$_POST['min_attendance'];
    $maxBacklogs   = (int)$_POST['max_backlogs'];
    $st = $conn->prepare("UPDATE eligibility_criteria SET min_cgpa=?, min_attendance=?, max_backlogs=?");
    $st->bind_param('ddi', $minCgpa, $minAttendance, $maxBacklogs); $st->execute(); $st->close();
    $msg = '<div class="alert alert-success">✅ Eligibility criteria updated!</div>';
}

// Bulk update attendance/backlogs
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_update'])) {
    $ids  = $_POST['sid'] ?? [];
    $atts = $_POST['att'] ?? [];
    $bkls = $_POST['bkl'] ?? [];
    $stBU = $conn->prepare("INSERT INTO student_attendance (user_id, attendance_pct, backlogs) VALUES (?,?,?) ON DUPLICATE KEY UPDATE attendance_pct=?, backlogs=?");
    foreach ($ids as $i => $sid) {
        $sid = (int)$sid; $att = (float)($atts[$i] ?? 0); $bkl = (int)($bkls[$i] ?? 0);
        $stBU->bind_param('idddi', $sid, $att, $bkl, $att, $bkl); $stBU->execute();
    }
    $stBU->close();
    $msg = '<div class="alert alert-success">✅ Attendance records saved!</div>';
}

// Approve / Reject individual student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_student'])) {
    $sid      = (int)$_POST['student_id'];
    $decision = trim($_POST['decision'] ?? '');
    $note     = trim($_POST['approval_note'] ?? '');
    if (in_array($decision, ['approved', 'rejected'])) {
        $stAp = $conn->prepare("INSERT INTO student_attendance (user_id, placement_approval, approval_note) VALUES (?,?,?) ON DUPLICATE KEY UPDATE placement_approval=?, approval_note=?");
        $stAp->bind_param('issss', $sid, $decision, $note, $decision, $note); $stAp->execute(); $stAp->close();
        $label = $decision === 'approved' ? 'approved for placement' : 'rejected from placement';
        $notifMsg = 'Your placement eligibility has been ' . $label . ' by the admin.' . ($note ? ' Note: ' . $note : '');
        $stNot = $conn->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'system', 'Placement Eligibility Update', ?)");
        $stNot->bind_param('is', $sid, $notifMsg); $stNot->execute(); $stNot->close();
        $msg = "<div class='alert alert-success'>✅ Student " . ucfirst($decision) . " for placement.</div>";
    }
}

// Bulk approve all eligible students
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_approve'])) {
    $eligible_ids = $_POST['eligible_ids'] ?? [];
    $stBA  = $conn->prepare("INSERT INTO student_attendance (user_id, placement_approval) VALUES (?, 'approved') ON DUPLICATE KEY UPDATE placement_approval='approved'");
    $stBAn = $conn->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'system', 'Placement Eligibility Approved', 'Congratulations! You have been approved for placement drives.')");
    foreach ($eligible_ids as $sid) {
        $sid = (int)$sid;
        $stBA->bind_param('i', $sid); $stBA->execute();
        $stBAn->bind_param('i', $sid); $stBAn->execute();
    }
    $stBA->close(); $stBAn->close();
    $msg = '<div class="alert alert-success">✅ All eligible students approved for placement!</div>';
}

$criteria = $conn->query("SELECT * FROM eligibility_criteria LIMIT 1")->fetch_assoc();

$students = $conn->query("SELECT u.id, u.name, u.email, sp.department, sp.cgpa, sp.roll_number,
    COALESCE(sa.attendance_pct, 0) as attendance_pct,
    COALESCE(sa.backlogs, 0) as backlogs,
    COALESCE(sa.placement_approval, 'pending') as placement_approval,
    COALESCE(sa.approval_note, '') as approval_note
    FROM users u
    JOIN student_profiles sp ON u.id = sp.user_id
    LEFT JOIN student_attendance sa ON u.id = sa.user_id
    WHERE u.role = 'student'
    ORDER BY sp.department, u.name");

$stats = ['eligible'=>0,'ineligible'=>0,'total'=>0,'approved'=>0,'rejected'=>0,'pending'=>0];
$rows  = [];
$eligible_ids = [];
while ($s = $students->fetch_assoc()) {
    // DS attendance score
    $att = (float)$s['attendance_pct'];
    $min_att = (float)$criteria['min_attendance'];
    if ($att >= $min_att) {
        $attend_score = 100;
    } else {
        $deficit = $min_att > 0 ? ($min_att - $att) / $min_att : 1;
        $attend_score = max(0, round(100 * exp(-3 * $deficit)));
    }
    $s['attend_score'] = $attend_score;

    $eligible = ($s['cgpa'] >= $criteria['min_cgpa'])
             && ($att >= $min_att)
             && ($s['backlogs'] <= $criteria['max_backlogs']);
    $s['eligible'] = $eligible;
    $rows[] = $s;
    $stats['total']++;
    if ($eligible) { $stats['eligible']++; $eligible_ids[] = $s['id']; }
    else $stats['ineligible']++;
    $stats[$s['placement_approval']]++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Eligibility & Approval - Admin</title>
<link rel="stylesheet" href="../../css/style.css">
<style>
.criteria-card{background:linear-gradient(135deg,#1a237e,#3949ab);color:#fff;border-radius:12px;padding:22px;margin-bottom:20px}
.att-bar{height:6px;border-radius:3px;background:#e0e0e0;overflow:hidden;width:60px;display:inline-block;vertical-align:middle;margin-left:4px}
.att-bar-fill{height:100%;border-radius:3px}
.approval-badge{padding:3px 10px;border-radius:12px;font-size:0.75rem;font-weight:700;display:inline-block}
.badge-approved{background:#e8f5e9;color:#2e7d32}
.badge-rejected{background:#ffebee;color:#c62828}
.badge-pending{background:#fff8e1;color:#f57f17}
.approve-form{display:flex;flex-direction:column;gap:5px;min-width:200px}
.approve-form textarea{padding:5px 7px;border:1px solid #ddd;border-radius:6px;font-size:0.78rem;resize:none;font-family:inherit}
.approve-form .btn-row{display:flex;gap:5px}
</style>
</head>
<body>
<?php require_once '../sidebar.php'; ?>

<div class="topbar">
    <div class="topbar-left">
        <button class="hamburger" onclick="toggleSidebar()">☰</button>
        <span class="page-title">✅ Eligibility & Placement Approval</span>
    </div>
    <div class="topbar-right">
        <?php require_once '../../notifications/widget.php'; ?>
    </div>
</div>

<div class="main-content">
    <?= $msg ?>

    <!-- Header Banner -->
    <div class="criteria-card">
        <h2 style="color:#ffd54f;border:none;padding:0;margin-bottom:8px">✅ Placement Eligibility & Approval</h2>
        <p style="color:#c5cae9">Set criteria, verify student eligibility using DS-based attendance scoring, and approve/reject students for placement drives.</p>
    </div>

    <!-- Tabs -->
    <div style="display:flex;gap:8px;margin-bottom:22px;border-bottom:2px solid #e8eaf6">
        <button onclick="showTab('eligibility')" id="tab-elig"
            style="padding:10px 22px;border:none;background:none;font-size:0.95rem;font-weight:700;color:#3f51b5;border-bottom:3px solid #3f51b5;cursor:pointer">
            📋 Eligibility Check
        </button>
        <button onclick="showTab('approval')" id="tab-appr"
            style="padding:10px 22px;border:none;background:none;font-size:0.95rem;font-weight:600;color:#666;border-bottom:3px solid transparent;cursor:pointer">
            ✅ Placement Approval
            <?php if ($stats['pending'] > 0): ?>
            <span style="background:#e53935;color:#fff;border-radius:10px;padding:1px 7px;font-size:0.72rem;margin-left:4px"><?= $stats['pending'] ?></span>
            <?php endif; ?>
        </button>
    </div>

    <!-- ── ELIGIBILITY TAB ── -->
    <div id="tab-elig-content">
        <div style="display:grid;grid-template-columns:280px 1fr;gap:20px">
            <!-- Criteria + Summary -->
            <div>
                <div class="card">
                    <h2>⚙️ Eligibility Criteria</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label>Minimum CGPA</label>
                            <input type="number" name="min_cgpa" step="0.01" min="0" max="10" value="<?= $criteria['min_cgpa'] ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Minimum Attendance %
                                <small style="color:#e65100;font-weight:600">(DS decay below this)</small>
                            </label>
                            <input type="number" name="min_attendance" step="0.1" min="0" max="100" value="<?= $criteria['min_attendance'] ?>" required>
                            <small style="color:#666">Students below <?= $criteria['min_attendance'] ?>% get exponential DS penalty</small>
                        </div>
                        <div class="form-group">
                            <label>Maximum Backlogs Allowed</label>
                            <input type="number" name="max_backlogs" min="0" value="<?= $criteria['max_backlogs'] ?>" required>
                        </div>
                        <button name="update_criteria" class="btn btn-primary" style="width:100%">💾 Save Criteria</button>
                    </form>
                </div>

                <div class="card">
                    <h2>📊 Summary</h2>
                    <?php foreach ([
                        [$stats['total'],      '#1a237e', '📋 Total Students'],
                        [$stats['eligible'],   '#2e7d32', '✅ Eligible'],
                        [$stats['ineligible'], '#c62828', '❌ Ineligible'],
                        [$stats['approved'],   '#2e7d32', '🎓 Placement Approved'],
                        [$stats['rejected'],   '#c62828', '🚫 Placement Rejected'],
                        [$stats['pending'],    '#f57f17', '⏳ Pending Approval'],
                    ] as [$num, $col, $lbl]): ?>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #f5f5f5">
                        <span style="font-size:0.88rem;color:#555"><?= $lbl ?></span>
                        <strong style="color:<?= $col ?>;font-size:1.1rem"><?= $num ?></strong>
                    </div>
                    <?php endforeach; ?>
                    <div style="margin-top:14px;text-align:center">
                        <div style="font-size:1.6rem;font-weight:800;color:#1a237e">
                            <?= $stats['total'] > 0 ? round($stats['eligible']/$stats['total']*100) : 0 ?>%
                        </div>
                        <div style="color:#666;font-size:0.85rem">Eligibility Rate</div>
                    </div>
                </div>
            </div>

            <!-- Students Table -->
            <div class="card">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;flex-wrap:wrap;gap:10px">
                    <h2 style="border:none;padding:0;margin:0">Student Eligibility Status</h2>
                    <div style="display:flex;gap:6px;flex-wrap:wrap">
                        <button onclick="filterTable('all')"        class="btn btn-sm" style="background:#e8eaf6;color:#333" id="f-all">All (<?= $stats['total'] ?>)</button>
                        <button onclick="filterTable('eligible')"   class="btn btn-sm btn-success" id="f-eligible">✅ Eligible (<?= $stats['eligible'] ?>)</button>
                        <button onclick="filterTable('ineligible')" class="btn btn-sm btn-danger"  id="f-ineligible">❌ Ineligible (<?= $stats['ineligible'] ?>)</button>
                    </div>
                </div>

                <form method="POST">
                    <div class="table-wrap">
                        <table>
                            <tr>
                                <th>Student</th>
                                <th>Dept</th>
                                <th>CGPA<br><small style="font-weight:400;color:#999">Min: <?= $criteria['min_cgpa'] ?></small></th>
                                <th>Attendance %<br><small style="font-weight:400;color:#999">Min: <?= $criteria['min_attendance'] ?>% (DS)</small></th>
                                <th>Backlogs<br><small style="font-weight:400;color:#999">Max: <?= $criteria['max_backlogs'] ?></small></th>
                                <th>Eligibility</th>
                                <th>Approval</th>
                            </tr>
                            <?php foreach ($rows as $s): ?>
                            <tr class="student-row" data-eligible="<?= $s['eligible'] ? 'eligible' : 'ineligible' ?>">
                                <td>
                                    <input type="hidden" name="sid[]" value="<?= $s['id'] ?>">
                                    <strong><?= htmlspecialchars($s['name']) ?></strong>
                                    <div style="font-size:0.75rem;color:#999"><?= htmlspecialchars($s['roll_number'] ?? '') ?></div>
                                </td>
                                <td style="font-size:0.85rem"><?= htmlspecialchars($s['department'] ?? '-') ?></td>
                                <td>
                                    <span style="color:<?= $s['cgpa'] >= $criteria['min_cgpa'] ? '#2e7d32' : '#c62828' ?>;font-weight:700">
                                        <?= $s['cgpa'] ?: '-' ?>
                                    </span>
                                </td>
                                <td>
                                    <input type="number" name="att[]" value="<?= $s['attendance_pct'] ?>" min="0" max="100" step="0.1"
                                        style="width:65px;padding:3px 5px;border:1px solid <?= $s['attendance_pct'] >= $criteria['min_attendance'] ? '#43a047' : '#e53935' ?>;border-radius:4px;font-size:0.82rem">
                                    <!-- DS attendance bar -->
                                    <div style="margin-top:3px;font-size:0.7rem;color:<?= $s['attend_score'] >= 80 ? '#2e7d32' : ($s['attend_score'] >= 50 ? '#e65100' : '#c62828') ?>">
                                        DS: <?= $s['attend_score'] ?>%
                                        <span class="att-bar"><span class="att-bar-fill" style="width:<?= $s['attend_score'] ?>%;background:<?= $s['attend_score'] >= 80 ? '#43a047' : ($s['attend_score'] >= 50 ? '#fb8c00' : '#e53935') ?>"></span></span>
                                    </div>
                                </td>
                                <td>
                                    <input type="number" name="bkl[]" value="<?= $s['backlogs'] ?>" min="0"
                                        style="width:50px;padding:3px 5px;border:1px solid <?= $s['backlogs'] <= $criteria['max_backlogs'] ? '#43a047' : '#e53935' ?>;border-radius:4px;font-size:0.82rem">
                                </td>
                                <td>
                                    <?php if ($s['eligible']): ?>
                                    <span class="badge badge-selected">✅ Eligible</span>
                                    <?php else: ?>
                                    <span class="badge badge-rejected">❌ Ineligible</span>
                                    <?php
                                    $r = [];
                                    if ($s['cgpa'] < $criteria['min_cgpa']) $r[] = 'Low CGPA';
                                    if ($s['attendance_pct'] < $criteria['min_attendance']) $r[] = 'Low Attendance';
                                    if ($s['backlogs'] > $criteria['max_backlogs']) $r[] = 'Backlogs';
                                    ?>
                                    <div style="font-size:0.7rem;color:#c62828"><?= implode(', ', $r) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="approval-badge badge-<?= $s['placement_approval'] ?>">
                                        <?= $s['placement_approval'] === 'approved' ? '🎓 Approved' : ($s['placement_approval'] === 'rejected' ? '🚫 Rejected' : '⏳ Pending') ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                    <div style="margin-top:14px;display:flex;gap:10px;align-items:center;flex-wrap:wrap">
                        <button name="bulk_update" class="btn btn-primary">💾 Save Attendance & Backlogs</button>
                        <span style="font-size:0.82rem;color:#999">Edit inline then save.</span>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ── APPROVAL TAB ── -->
    <div id="tab-appr-content" style="display:none">
        <div class="card" style="background:linear-gradient(135deg,#1b5e20,#2e7d32);color:#fff;margin-bottom:20px">
            <h2 style="color:#c8e6c9;margin-bottom:6px">🎓 Placement Approval Panel</h2>
            <p style="color:#a5d6a7">Approve or reject eligible students for placement drives. Students are notified instantly.</p>
        </div>

        <!-- Bulk approve button -->
        <?php if ($stats['eligible'] > 0): ?>
        <div style="margin-bottom:18px">
            <form method="POST" onsubmit="return confirm('Approve ALL <?= $stats['eligible'] ?> eligible students for placement?')">
                <?php foreach ($eligible_ids as $eid): ?>
                <input type="hidden" name="eligible_ids[]" value="<?= $eid ?>">
                <?php endforeach; ?>
                <button name="bulk_approve" class="btn btn-success" style="padding:10px 24px">
                    ✅ Bulk Approve All <?= $stats['eligible'] ?> Eligible Students
                </button>
            </form>
        </div>
        <?php endif; ?>

        <?php if (empty($rows)): ?>
        <div class="card" style="text-align:center;padding:40px;color:#999">No students found.</div>
        <?php else: ?>
        <div class="table-wrap" style="background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 10px rgba(0,0,0,0.07)">
            <table>
                <tr>
                    <th>Student</th>
                    <th>Dept</th>
                    <th>CGPA</th>
                    <th>Attendance (DS Score)</th>
                    <th>Eligibility</th>
                    <th>Approval Status</th>
                    <th style="min-width:220px">Action</th>
                </tr>
                <?php foreach ($rows as $s): ?>
                <tr style="background:<?= $s['placement_approval']==='approved' ? '#f1f8e9' : ($s['placement_approval']==='rejected' ? '#fff8f8' : '#fff') ?>">
                    <td>
                        <strong><?= htmlspecialchars($s['name']) ?></strong>
                        <div style="font-size:0.75rem;color:#999"><?= htmlspecialchars($s['email']) ?></div>
                    </td>
                    <td style="font-size:0.85rem"><?= htmlspecialchars($s['department'] ?? '-') ?></td>
                    <td>
                        <span style="font-weight:700;color:<?= $s['cgpa'] >= $criteria['min_cgpa'] ? '#2e7d32' : '#c62828' ?>">
                            <?= $s['cgpa'] ?: 'N/A' ?>
                        </span>
                    </td>
                    <td>
                        <span style="font-weight:700;color:<?= $s['attendance_pct'] >= $criteria['min_attendance'] ? '#2e7d32' : '#c62828' ?>">
                            <?= $s['attendance_pct'] ?>%
                        </span>
                        <span style="font-size:0.75rem;color:#666;margin-left:4px">(DS: <?= $s['attend_score'] ?>)</span>
                        <span class="att-bar"><span class="att-bar-fill" style="width:<?= $s['attend_score'] ?>%;background:<?= $s['attend_score'] >= 80 ? '#43a047' : ($s['attend_score'] >= 50 ? '#fb8c00' : '#e53935') ?>"></span></span>
                    </td>
                    <td>
                        <?php if ($s['eligible']): ?>
                        <span class="badge badge-selected">✅ Eligible</span>
                        <?php else: ?>
                        <span class="badge badge-rejected">❌ Ineligible</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="approval-badge badge-<?= $s['placement_approval'] ?>">
                            <?= $s['placement_approval'] === 'approved' ? '🎓 Approved' : ($s['placement_approval'] === 'rejected' ? '🚫 Rejected' : '⏳ Pending') ?>
                        </span>
                        <?php if ($s['approval_note']): ?>
                        <div style="font-size:0.72rem;color:#666;margin-top:2px"><?= htmlspecialchars($s['approval_note']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="POST" class="approve-form">
                            <input type="hidden" name="approve_student" value="1">
                            <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                            <textarea name="approval_note" rows="2" placeholder="Optional note to student..."></textarea>
                            <div class="btn-row">
                                <button type="submit" name="decision" value="approved"
                                    style="background:#2e7d32;color:#fff;border:none;padding:6px 14px;border-radius:16px;font-size:0.8rem;font-weight:700;cursor:pointer;flex:1">
                                    ✅ Approve
                                </button>
                                <button type="submit" name="decision" value="rejected"
                                    style="background:#c62828;color:#fff;border:none;padding:6px 14px;border-radius:16px;font-size:0.8rem;font-weight:700;cursor:pointer;flex:1"
                                    onclick="return confirm('Reject this student from placement?')">
                                    🚫 Reject
                                </button>
                            </div>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endif; ?>
    </div>

</div><!-- main-content -->
</div><!-- app-layout -->

<?php require_once '../../chatbot/widget.php'; ?>
<script>
function showTab(tab) {
    document.getElementById('tab-elig-content').style.display  = tab === 'eligibility' ? 'block' : 'none';
    document.getElementById('tab-appr-content').style.display  = tab === 'approval'    ? 'block' : 'none';
    document.getElementById('tab-elig').style.borderBottomColor = tab === 'eligibility' ? '#3f51b5' : 'transparent';
    document.getElementById('tab-elig').style.color             = tab === 'eligibility' ? '#3f51b5' : '#666';
    document.getElementById('tab-elig').style.fontWeight        = tab === 'eligibility' ? '700' : '600';
    document.getElementById('tab-appr').style.borderBottomColor = tab === 'approval' ? '#3f51b5' : 'transparent';
    document.getElementById('tab-appr').style.color             = tab === 'approval' ? '#3f51b5' : '#666';
    document.getElementById('tab-appr').style.fontWeight        = tab === 'approval' ? '700' : '600';
}
function filterTable(type) {
    document.querySelectorAll('.student-row').forEach(row => {
        row.style.display = (type === 'all' || row.dataset.eligible === type) ? '' : 'none';
    });
    ['all','eligible','ineligible'].forEach(t => {
        document.getElementById('f-'+t).style.opacity = t === type ? '1' : '0.5';
    });
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
