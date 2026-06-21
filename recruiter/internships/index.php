<?php
require_once '../../includes/config.php';
requireLogin('recruiter');
require_once '../../includes/notify.php';

$uid = $_SESSION['user_id'];
$company = $conn->query("SELECT * FROM companies WHERE user_id=$uid")->fetch_assoc();
$cid = $company['id'] ?? 0;

$msg = '';

// Post internship
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_internship'])) {
    $title    = sanitize($_POST['title']);
    $desc     = sanitize($_POST['description']);
    $req      = sanitize($_POST['requirements']);
    $stipend  = sanitize($_POST['stipend']);
    $location = sanitize($_POST['location']);
    $duration = sanitize($_POST['duration']);
    $min_cgpa = (float)$_POST['min_cgpa'];
    $deadline = sanitize($_POST['deadline']);
    $conn->query("INSERT INTO internships (company_id,title,description,requirements,stipend,location,duration,min_cgpa,deadline)
        VALUES ($cid,'$title','$desc','$req','$stipend','$location','$duration',$min_cgpa,'$deadline')");
    $iid = $conn->insert_id;
    // Notify all students
    $students = $conn->query("SELECT id FROM users WHERE role='student'");
    while ($s = $students->fetch_assoc()) {
        createNotification($conn, $s['id'], 'job', "🏢 New Internship: $title", "{$company['company_name']} posted a new internship: $title. Apply now!", '/placement system/student/internships/index.php');
    }
    $msg = '<div class="alert alert-success">✅ Internship posted successfully!</div>';
}

// Update application status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $app_id    = (int)$_POST['app_id'];
    $newStatus = sanitize($_POST['new_status']);
    $allowed   = ['applied','shortlisted','rejected','selected','completed'];
    if (in_array($newStatus, $allowed)) {
        $conn->query("UPDATE internship_applications SET status='$newStatus' WHERE id=$app_id");
        if ($newStatus === 'completed') {
            $conn->query("UPDATE internship_applications SET completion_date=CURDATE(), certificate_issued=1 WHERE id=$app_id");
        }
        $msg = '<div class="alert alert-success">✅ Status updated.</div>';
    }
}

// Close/open internship
if (isset($_GET['toggle'])) {
    $iid = (int)$_GET['toggle'];
    $cur = $conn->query("SELECT status FROM internships WHERE id=$iid AND company_id=$cid")->fetch_assoc();
    if ($cur) {
        $ns = $cur['status'] === 'open' ? 'closed' : 'open';
        $conn->query("UPDATE internships SET status='$ns' WHERE id=$iid");
        header("Location: index.php"); exit();
    }
}

$internships = $conn->query("SELECT i.*,
    (SELECT COUNT(*) FROM internship_applications WHERE internship_id=i.id) as total_apps,
    (SELECT COUNT(*) FROM internship_applications WHERE internship_id=i.id AND status='selected') as selected_count
    FROM internships i WHERE i.company_id=$cid ORDER BY i.created_at DESC");

$view_iid = (int)($_GET['view'] ?? 0);
$applications = null;
$view_intern  = null;
if ($view_iid) {
    $view_intern = $conn->query("SELECT * FROM internships WHERE id=$view_iid AND company_id=$cid")->fetch_assoc();
    if ($view_intern) {
        $applications = $conn->query("SELECT ia.*, u.name, u.email, sp.cgpa, sp.department, sp.skills
            FROM internship_applications ia
            JOIN users u ON ia.student_id=u.id
            LEFT JOIN student_profiles sp ON sp.user_id=u.id
            WHERE ia.internship_id=$view_iid ORDER BY ia.applied_at DESC");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Internships - Recruiter</title>
<link rel="stylesheet" href="../../css/style.css">
</head>
<body>
<nav class="navbar">
    <a href="../dashboard.php" class="brand">🎓 Campus<span>Recruit</span></a>
    <div class="nav-links">
        <a href="../dashboard.php">Dashboard</a>
        <a href="../post_job.php">Post Job</a>
        <a href="../jobs.php">My Jobs</a>
        <a href="index.php" class="active">🏢 Internships</a>
        <a href="../applications.php">Applications</a>
        <a href="../interviews/index.php">🎥 Interviews</a>
        <a href="../analytics/index.php">📊 Analytics</a>
        <?php require_once '../../notifications/widget.php'; ?>
        <a href="../logout.php" class="btn-logout">Logout</a>
    </div>
</nav>

<div class="container">
    <?= $msg ?>

    <div class="card" style="background:linear-gradient(135deg,#4a148c,#7b1fa2);color:#fff;margin-bottom:25px">
        <h2 style="color:#ffd54f;margin-bottom:8px">🏢 Internship Management</h2>
        <p style="color:#ce93d8">Post internship opportunities and manage student applications.</p>
    </div>

    <?php if ($view_intern && $applications): ?>
    <!-- Applications View -->
    <div class="card">
        <h2>📋 Applications — <?= htmlspecialchars($view_intern['title']) ?> <a href="index.php" class="btn btn-sm" style="float:right;background:#e8eaf6;color:#333">← Back</a></h2>
        <?php if ($applications->num_rows === 0): ?>
        <p style="color:#999;text-align:center;padding:20px">No applications yet.</p>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <tr><th>Student</th><th>Email</th><th>Dept</th><th>CGPA</th><th>Status</th><th>Applied</th><th>Action</th></tr>
                <?php while($a = $applications->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($a['name']) ?></strong></td>
                    <td><?= htmlspecialchars($a['email']) ?></td>
                    <td><?= htmlspecialchars($a['department'] ?? '-') ?></td>
                    <td><?= $a['cgpa'] ?: '-' ?></td>
                    <td><span class="badge badge-<?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span></td>
                    <td><?= date('d M Y', strtotime($a['applied_at'])) ?></td>
                    <td>
                        <form method="POST" style="display:flex;gap:5px">
                            <input type="hidden" name="app_id" value="<?= $a['id'] ?>">
                            <select name="new_status" style="padding:4px 8px;border-radius:5px;border:1px solid #ddd;font-size:0.82rem">
                                <?php foreach(['applied','shortlisted','selected','rejected','completed'] as $s): ?>
                                <option value="<?= $s ?>" <?= $a['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button name="update_status" class="btn btn-primary btn-sm">Update</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 1.5fr;gap:20px">
        <!-- Post Form -->
        <div class="card">
            <h2>➕ Post New Internship</h2>
            <form method="POST">
                <input type="hidden" name="post_internship" value="1">
                <div class="form-group">
                    <label>Internship Title *</label>
                    <input type="text" name="title" placeholder="e.g. Web Development Intern" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Duration *</label>
                        <input type="text" name="duration" placeholder="e.g. 2 Months" required>
                    </div>
                    <div class="form-group">
                        <label>Stipend</label>
                        <input type="text" name="stipend" placeholder="e.g. ₹5000/month">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="location" placeholder="e.g. Remote / Mumbai">
                    </div>
                    <div class="form-group">
                        <label>Min CGPA</label>
                        <input type="number" name="min_cgpa" step="0.1" min="0" max="10" value="0">
                    </div>
                </div>
                <div class="form-group">
                    <label>Application Deadline</label>
                    <input type="date" name="deadline">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3" placeholder="Internship description..."></textarea>
                </div>
                <div class="form-group">
                    <label>Requirements</label>
                    <textarea name="requirements" rows="2" placeholder="Skills required..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;background:linear-gradient(135deg,#4a148c,#7b1fa2)">
                    🏢 Post Internship
                </button>
            </form>
        </div>

        <!-- My Internships -->
        <div class="card">
            <h2>My Posted Internships</h2>
            <?php if ($internships->num_rows === 0): ?>
            <p style="color:#999;text-align:center;padding:30px">No internships posted yet.</p>
            <?php else: ?>
            <?php while($i = $internships->fetch_assoc()): ?>
            <div style="border:1px solid #e0e0e0;border-radius:10px;padding:15px;margin-bottom:12px;border-left:4px solid #7b1fa2">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px">
                    <div>
                        <strong style="color:#4a148c"><?= htmlspecialchars($i['title']) ?></strong>
                        <div style="font-size:0.82rem;color:#666;margin-top:4px">
                            ⏱️ <?= htmlspecialchars($i['duration'] ?? '-') ?> · 📋 <?= $i['total_apps'] ?> applied · ✅ <?= $i['selected_count'] ?> selected
                        </div>
                        <div style="font-size:0.78rem;color:#999;margin-top:3px">Deadline: <?= $i['deadline'] ? date('d M Y', strtotime($i['deadline'])) : 'Open' ?></div>
                    </div>
                    <div style="display:flex;gap:6px;flex-wrap:wrap">
                        <span class="badge badge-<?= $i['status'] ?>"><?= ucfirst($i['status']) ?></span>
                        <a href="?view=<?= $i['id'] ?>" class="btn btn-primary btn-sm">View Apps</a>
                        <a href="?toggle=<?= $i['id'] ?>" class="btn btn-sm" style="background:#e8eaf6;color:#333"><?= $i['status']==='open'?'Close':'Open' ?></a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../../chatbot/widget.php'; ?>
</body>
</html>
