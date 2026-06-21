<?php
require_once '../includes/config.php';
requireLogin('admin');
require_once '../includes/notify.php';

$msg = '';

// Handle status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_status'])) {
    $app_id    = (int)$_POST['app_id'];
    $newStatus = trim($_POST['new_status']);
    $allowed   = ['applied','shortlisted','selected','rejected'];
    if (in_array($newStatus, $allowed)) {
        $st = $conn->prepare("UPDATE applications SET status=? WHERE id=?");
        $st->bind_param('si', $newStatus, $app_id); $st->execute(); $st->close();

        // Get info for notification
        $info = $conn->query("SELECT a.student_id, j.title, c.company_name FROM applications a JOIN jobs j ON a.job_id=j.id JOIN companies c ON j.company_id=c.id WHERE a.id=$app_id")->fetch_assoc();
        if ($info) {
            $messages = [
                'shortlisted' => "🎉 Congratulations! You have been shortlisted for {$info['title']} at {$info['company_name']}.",
                'selected'    => "🎊 You have been SELECTED for {$info['title']} at {$info['company_name']}! Congratulations!",
                'rejected'    => "Your application for {$info['title']} at {$info['company_name']} was not selected this time.",
                'applied'     => "Your application for {$info['title']} at {$info['company_name']} has been reset to applied.",
            ];
            $notifMsg = $messages[$newStatus];
            $stN = $conn->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'application', 'Application Update', ?)");
            $stN->bind_param('is', $info['student_id'], $notifMsg); $stN->execute(); $stN->close();
        }
        $msg = "<div class='alert alert-success'>✅ Status updated to <strong>" . ucfirst($newStatus) . "</strong>.</div>";
    }
}

$filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$search = trim($_GET['search'] ?? '');
$allowed_statuses = ['applied', 'shortlisted', 'selected', 'rejected'];
if ($filter && !in_array($filter, $allowed_statuses)) $filter = '';

$where = '1=1';
$bindTypes = ''; $bindVals = [];
if ($filter) { $where .= " AND a.status=?"; $bindTypes .= 's'; $bindVals[] = $filter; }
if ($search) {
    $like = '%'.$search.'%';
    $where .= " AND (u.name LIKE ? OR j.title LIKE ? OR c.company_name LIKE ?)";
    $bindTypes .= 'sss'; $bindVals[] = $like; $bindVals[] = $like; $bindVals[] = $like;
}

$sql = "SELECT a.*, u.name as student_name, u.email, j.title as job_title, c.company_name, sp.cgpa, sp.department
    FROM applications a
    JOIN users u ON a.student_id=u.id
    JOIN jobs j ON a.job_id=j.id
    JOIN companies c ON j.company_id=c.id
    LEFT JOIN student_profiles sp ON sp.user_id=u.id
    WHERE $where ORDER BY a.applied_at DESC";
$stmtApps = $conn->prepare($sql);
if ($bindTypes) $stmtApps->bind_param($bindTypes, ...$bindVals);
$stmtApps->execute();
$apps = $stmtApps->get_result();
$stmtApps->close();

// Counts per status
$counts = [];
foreach (['applied','shortlisted','selected','rejected'] as $s) {
    $counts[$s] = $conn->query("SELECT COUNT(*) as c FROM applications WHERE status='$s'")->fetch_assoc()['c'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Applications - Admin</title>
<link rel="stylesheet" href="../css/style.css">
<style>
.status-btn { padding:4px 12px; border:none; border-radius:20px; font-size:0.78rem; font-weight:700; cursor:pointer; transition:all 0.2s; }
.status-btn:hover { opacity:0.85; transform:scale(1.05); }
.status-btn.shortlist { background:#fff8e1; color:#e65100; }
.status-btn.select    { background:#e8f5e9; color:#2e7d32; }
.status-btn.reject    { background:#ffebee; color:#c62828; }
.status-btn.reset     { background:#e8eaf6; color:#3f51b5; }
.count-chip { display:inline-block; padding:2px 8px; border-radius:10px; font-size:0.75rem; font-weight:700; margin-left:6px; }
</style>
</head>
<body>
<?php require_once 'sidebar.php'; ?>
<div class="topbar">
    <div class="topbar-left">
        <button class="hamburger" onclick="toggleSidebar()">☰</button>
        <span class="page-title">Applications</span>
    </div>
    <div class="topbar-right"><?php require_once '../notifications/widget.php'; ?></div>
</div>
<div class="main-content">
    <?= $msg ?>

    <!-- Stats -->
    <div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:20px">
        <div class="stat-card"><div class="number"><?= $counts['applied'] ?></div><div class="label">📋 Applied</div></div>
        <div class="stat-card orange"><div class="number"><?= $counts['shortlisted'] ?></div><div class="label">⭐ Shortlisted</div></div>
        <div class="stat-card green"><div class="number"><?= $counts['selected'] ?></div><div class="label">✅ Selected</div></div>
        <div class="stat-card red"><div class="number"><?= $counts['rejected'] ?></div><div class="label">❌ Rejected</div></div>
    </div>

    <div class="card">
        <h2>All Applications</h2>

        <!-- Filters -->
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;align-items:center">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="🔍 Search student, job, company..." style="flex:1;min-width:200px;padding:8px 14px;border:1.5px solid #e0e0e0;border-radius:20px;font-size:0.88rem;outline:none">
            <div style="display:flex;gap:6px;flex-wrap:wrap">
                <a href="applications.php" class="filter-btn <?= !$filter?'active':'' ?>" style="padding:6px 16px;border-radius:20px;border:2px solid #e0e0e0;background:<?= !$filter?'#3f51b5':'#fff' ?>;color:<?= !$filter?'#fff':'#555' ?>;font-weight:600;font-size:0.82rem;text-decoration:none">All <span class="count-chip" style="background:rgba(255,255,255,0.3);color:inherit"><?= array_sum($counts) ?></span></a>
                <a href="?status=applied<?= $search?'&search='.urlencode($search):'' ?>" style="padding:6px 16px;border-radius:20px;border:2px solid <?= $filter==='applied'?'#3f51b5':'#e0e0e0' ?>;background:<?= $filter==='applied'?'#3f51b5':'#fff' ?>;color:<?= $filter==='applied'?'#fff':'#555' ?>;font-weight:600;font-size:0.82rem;text-decoration:none">Applied <span class="count-chip" style="background:rgba(63,81,181,0.15);color:#3f51b5"><?= $counts['applied'] ?></span></a>
                <a href="?status=shortlisted<?= $search?'&search='.urlencode($search):'' ?>" style="padding:6px 16px;border-radius:20px;border:2px solid <?= $filter==='shortlisted'?'#e65100':'#e0e0e0' ?>;background:<?= $filter==='shortlisted'?'#e65100':'#fff' ?>;color:<?= $filter==='shortlisted'?'#fff':'#e65100' ?>;font-weight:600;font-size:0.82rem;text-decoration:none">Shortlisted <span class="count-chip" style="background:rgba(230,81,0,0.1);color:#e65100"><?= $counts['shortlisted'] ?></span></a>
                <a href="?status=selected<?= $search?'&search='.urlencode($search):'' ?>" style="padding:6px 16px;border-radius:20px;border:2px solid <?= $filter==='selected'?'#2e7d32':'#e0e0e0' ?>;background:<?= $filter==='selected'?'#2e7d32':'#fff' ?>;color:<?= $filter==='selected'?'#fff':'#2e7d32' ?>;font-weight:600;font-size:0.82rem;text-decoration:none">Selected <span class="count-chip" style="background:rgba(46,125,50,0.1);color:#2e7d32"><?= $counts['selected'] ?></span></a>
                <a href="?status=rejected<?= $search?'&search='.urlencode($search):'' ?>" style="padding:6px 16px;border-radius:20px;border:2px solid <?= $filter==='rejected'?'#c62828':'#e0e0e0' ?>;background:<?= $filter==='rejected'?'#c62828':'#fff' ?>;color:<?= $filter==='rejected'?'#fff':'#c62828' ?>;font-weight:600;font-size:0.82rem;text-decoration:none">Rejected <span class="count-chip" style="background:rgba(198,40,40,0.1);color:#c62828"><?= $counts['rejected'] ?></span></a>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Search</button>
        </form>

        <div class="table-wrap">
            <table>
                <tr><th>#</th><th>Student</th><th>Department</th><th>CGPA</th><th>Job</th><th>Company</th><th>Status</th><th>Applied On</th><th>Actions</th></tr>
                <?php $i=1; while($a = $apps->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td>
                        <div style="font-weight:700;color:#1a237e"><?= htmlspecialchars($a['student_name']) ?></div>
                        <div style="font-size:0.78rem;color:#999"><?= htmlspecialchars($a['email']) ?></div>
                    </td>
                    <td style="font-size:0.82rem"><?= htmlspecialchars($a['department'] ?? '—') ?></td>
                    <td><strong><?= $a['cgpa'] ?: '—' ?></strong></td>
                    <td style="font-size:0.85rem"><?= htmlspecialchars($a['job_title']) ?></td>
                    <td style="font-size:0.85rem"><?= htmlspecialchars($a['company_name']) ?></td>
                    <td><span class="badge badge-<?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span></td>
                    <td style="font-size:0.82rem;color:#666"><?= date('d M Y', strtotime($a['applied_at'])) ?></td>
                    <td>
                        <div style="display:flex;gap:5px;flex-wrap:wrap">
                            <?php if ($a['status'] !== 'shortlisted'): ?>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="app_id" value="<?= $a['id'] ?>">
                                <input type="hidden" name="new_status" value="shortlisted">
                                <button type="submit" name="change_status" class="status-btn shortlist" title="Shortlist">⭐ Shortlist</button>
                            </form>
                            <?php endif; ?>
                            <?php if ($a['status'] !== 'selected'): ?>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="app_id" value="<?= $a['id'] ?>">
                                <input type="hidden" name="new_status" value="selected">
                                <button type="submit" name="change_status" class="status-btn select" onclick="return confirm('Mark as Selected?')" title="Select">✅ Select</button>
                            </form>
                            <?php endif; ?>
                            <?php if ($a['status'] !== 'rejected'): ?>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="app_id" value="<?= $a['id'] ?>">
                                <input type="hidden" name="new_status" value="rejected">
                                <button type="submit" name="change_status" class="status-btn reject" onclick="return confirm('Reject this application?')" title="Reject">❌ Reject</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if ($i === 1): ?>
                <tr><td colspan="9" style="text-align:center;padding:30px;color:#999">No applications found.</td></tr>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>
</div>
<script>
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('show');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('sidebarOverlay').classList.remove('show');}
</script>
</body>
</html>
