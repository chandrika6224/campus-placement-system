<?php
require_once '../includes/config.php';
requireLogin('recruiter');

$uid     = $_SESSION['user_id'];
$company = $conn->query("SELECT * FROM companies WHERE user_id=" . (int)$uid)->fetch_assoc();
$cid     = $company['id'] ?? 0;
$msg     = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle'])) {
        $id = (int)$_POST['toggle'];
        $cur = $conn->query("SELECT status FROM jobs WHERE id=$id AND company_id=$cid")->fetch_assoc();
        if ($cur) {
            $new = $cur['status'] === 'open' ? 'closed' : 'open';
            $stmtT = $conn->prepare("UPDATE jobs SET status=? WHERE id=? AND company_id=?");
            $stmtT->bind_param('sii', $new, $id, $cid);
            $stmtT->execute(); $stmtT->close();
            $msg = '<div class="alert alert-success">Job status updated.</div>';
        }
    }
    if (isset($_POST['delete'])) {
        $id = (int)$_POST['delete'];
        $conn->query("DELETE FROM jobs WHERE id=$id AND company_id=$cid");
        $msg = '<div class="alert alert-success">Job deleted.</div>';
    }
}

$jobs = $conn->query("SELECT j.*, (SELECT COUNT(*) FROM applications WHERE job_id=j.id) as app_count FROM jobs j WHERE j.company_id=" . (int)$cid . " ORDER BY j.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Jobs - Recruiter</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<nav class="navbar">
    <a href="dashboard.php" class="brand">🎓 Campus<span>Recruit</span></a>
    <div class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="post_job.php">Post Job</a>
        <a href="jobs.php" class="active">My Jobs</a>
        <a href="applications.php">Applications</a>
        <a href="profile.php">Company Profile</a>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>
</nav>
<div class="container">
    <?= $msg ?>
    <div class="card">
        <h2>My Job Postings <a href="post_job.php" class="btn btn-primary btn-sm" style="float:right">+ Post New Job</a></h2>
        <div class="table-wrap">
            <table>
                <tr><th>Title</th><th>Type</th><th>Location</th><th>Deadline</th><th>Applications</th><th>Status</th><th>Actions</th></tr>
                <?php while($j = $jobs->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($j['title']) ?></td>
                    <td><?= $j['job_type'] ?></td>
                    <td><?= htmlspecialchars($j['location'] ?? '-') ?></td>
                    <td><?= $j['deadline'] ? date('d M Y', strtotime($j['deadline'])) : '-' ?></td>
                    <td><a href="applications.php?job=<?= $j['id'] ?>"><?= $j['app_count'] ?> applicants</a></td>
                    <td><span class="badge badge-<?= $j['status'] ?>"><?= ucfirst($j['status']) ?></span></td>
                    <td style="display:flex;gap:5px">
                        <form method="POST">
                            <input type="hidden" name="toggle" value="<?= $j['id'] ?>">
                            <button class="btn btn-warning btn-sm"><?= $j['status']==='open' ? 'Close' : 'Open' ?></button>
                        </form>
                        <form method="POST" onsubmit="return confirm('Delete this job?')">
                            <input type="hidden" name="delete" value="<?= $j['id'] ?>">
                            <button class="btn btn-danger btn-sm">Delete</button>
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
