<?php
require_once '../includes/config.php';
requireLogin('recruiter');
require_once '../includes/notify.php';

$uid = $_SESSION['user_id'];
$company = $conn->query("SELECT * FROM companies WHERE user_id=" . (int)$uid)->fetch_assoc();
$cid = $company['id'] ?? 0;
$msg = '';

// Update application status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $app_id = (int)$_POST['app_id'];
    $status = $_POST['status'] ?? '';
    if (in_array($status, ['applied','shortlisted','rejected','selected'])) {
        $stmtU = $conn->prepare("UPDATE applications a JOIN jobs j ON a.job_id=j.id SET a.status=? WHERE a.id=? AND j.company_id=?");
        $stmtU->bind_param('sii', $status, $app_id, $cid);
        $stmtU->execute(); $stmtU->close();
        $stmtAI = $conn->prepare("SELECT a.student_id, j.title, c.company_name FROM applications a JOIN jobs j ON a.job_id=j.id JOIN companies c ON j.company_id=c.id WHERE a.id=?");
        $stmtAI->bind_param('i', $app_id);
        $stmtAI->execute();
        $appInfo = $stmtAI->get_result()->fetch_assoc();
        $stmtAI->close();
        if ($appInfo) notifyApplicationStatus($conn, $appInfo['student_id'], $status, $appInfo['title'], $appInfo['company_name']);
        $msg = '<div class="alert alert-success">Status updated.</div>';
    }
}

// Save feedback
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_feedback'])) {
    $app_id   = (int)$_POST['app_id'];
    $feedback = trim($_POST['feedback'] ?? '');
    $stmtF = $conn->prepare("UPDATE applications a JOIN jobs j ON a.job_id=j.id SET a.feedback=?, a.feedback_at=NOW() WHERE a.id=? AND j.company_id=?");
    $stmtF->bind_param('sii', $feedback, $app_id, $cid);
    $stmtF->execute(); $stmtF->close();
    $msg = '<div class="alert alert-success">Feedback saved.</div>';
}

$job_filter = isset($_GET['job']) ? (int)$_GET['job'] : 0;
$where = "WHERE j.company_id=" . (int)$cid;
if ($job_filter) $where .= " AND j.id=" . (int)$job_filter;

$stmtApps = $conn->prepare("SELECT a.*, u.name as student_name, u.email, sp.department, sp.cgpa, sp.skills, sp.resume_path, j.title as job_title
    FROM applications a
    JOIN users u ON a.student_id=u.id
    LEFT JOIN student_profiles sp ON u.id=sp.user_id
    JOIN jobs j ON a.job_id=j.id
    $where ORDER BY a.applied_at DESC");
$stmtApps->execute();
$apps = $stmtApps->get_result();
$stmtApps->close();

$my_jobs = $conn->query("SELECT id, title FROM jobs WHERE company_id=" . (int)$cid . " ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Applications - Recruiter</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<nav class="navbar">
    <a href="dashboard.php" class="brand">🎓 Campus<span>Recruit</span></a>
    <div class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="post_job.php">Post Job</a>
        <a href="jobs.php">My Jobs</a>
        <a href="applications.php" class="active">Applications</a>
        <a href="interviews/index.php">🎥 Interviews</a>
        <a href="profile.php">Company Profile</a>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>
</nav>
<div class="container">
    <?= $msg ?>
    <div class="card">
        <h2>Applications</h2>
        <div style="margin-bottom:15px">
            Filter by job:
            <a href="applications.php" class="btn btn-sm <?= !$job_filter ? 'btn-primary' : 'btn-warning' ?>">All Jobs</a>
            <?php while($j = $my_jobs->fetch_assoc()): ?>
            <a href="?job=<?= $j['id'] ?>" class="btn btn-sm <?= $job_filter==$j['id'] ? 'btn-primary' : 'btn-warning' ?>"><?= htmlspecialchars($j['title']) ?></a>
            <?php endwhile; ?>
        </div>
        <div class="table-wrap">
            <table>
                <tr><th>Student</th><th>Email</th><th>Department</th><th>CGPA</th><th>Job</th><th>Resume</th><th>Status</th><th>Update Status</th><th>Feedback</th></tr>
                <?php while($a = $apps->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($a['student_name']) ?></td>
                    <td><?= htmlspecialchars($a['email']) ?></td>
                    <td><?= htmlspecialchars($a['department'] ?? '-') ?></td>
                    <td><?= $a['cgpa'] ?? '-' ?></td>
                    <td><?= htmlspecialchars($a['job_title']) ?></td>
                    <td>
                        <?php if ($a['resume_path']): ?>
                        <a href="../uploads/<?= $a['resume_path'] ?>" target="_blank" class="btn btn-primary btn-sm">View</a>
                        <?php else: ?><span style="color:#999">N/A</span><?php endif; ?>
                    </td>
                    <td><span class="badge badge-<?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span></td>
                    <td>
                        <form method="POST" style="display:flex;gap:5px">
                            <input type="hidden" name="app_id" value="<?= $a['id'] ?>">
                            <select name="status" style="padding:4px 8px;border:1px solid #ddd;border-radius:4px;font-size:0.85rem">
                                <option value="applied"     <?= $a['status']==='applied'     ?'selected':'' ?>>Applied</option>
                                <option value="shortlisted" <?= $a['status']==='shortlisted' ?'selected':'' ?>>Shortlisted</option>
                                <option value="selected"    <?= $a['status']==='selected'    ?'selected':'' ?>>Selected</option>
                                <option value="rejected"    <?= $a['status']==='rejected'    ?'selected':'' ?>>Rejected</option>
                            </select>
                            <button type="submit" name="update_status" class="btn btn-success btn-sm">Save</button>
                        </form>
                    </td>
                    <td style="min-width:220px">
                        <form method="POST">
                            <input type="hidden" name="app_id" value="<?= $a['id'] ?>">
                            <textarea name="feedback" rows="2"
                                style="width:100%;padding:5px 8px;border:1px solid #ddd;border-radius:6px;font-size:0.82rem;resize:vertical"
                                placeholder="Write feedback for this student..."><?= htmlspecialchars($a['feedback'] ?? '') ?></textarea>
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:4px">
                                <?php if ($a['feedback_at']): ?>
                                <small style="color:#999">Last: <?= date('d M Y', strtotime($a['feedback_at'])) ?></small>
                                <?php else: ?><small></small><?php endif; ?>
                                <button type="submit" name="save_feedback" class="btn btn-primary btn-sm">💬 Save</button>
                            </div>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>
</div>
</body>
</html>
