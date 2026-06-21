<?php
require_once '../includes/config.php';
requireLogin('recruiter');
require_once '../includes/notify.php';

$uid = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if ($uid === 0) { header('Location: ../index.php'); exit(); }
$stCo = $conn->prepare("SELECT * FROM companies WHERE user_id=?");
$stCo->bind_param('i',$uid); $stCo->execute();
$company = $stCo->get_result()->fetch_assoc(); $stCo->close();
$cid = (int)($company['id'] ?? 0);
$msg     = '';

// Add allowed_streams column if missing
$conn->query("ALTER TABLE jobs ADD COLUMN IF NOT EXISTS allowed_streams TEXT DEFAULT NULL");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = trim($_POST['title'] ?? '');
    $desc     = trim($_POST['description'] ?? '');
    $req      = trim($_POST['requirements'] ?? '');
    $salary   = trim($_POST['salary_range'] ?? '');
    $loc      = trim($_POST['location'] ?? '');
    $type     = trim($_POST['job_type'] ?? '');
    $cgpa     = (float)($_POST['min_cgpa'] ?? 0);
    $deadline = trim($_POST['deadline'] ?? '');
    $streams  = isset($_POST['streams']) ? implode(',', array_map('trim', $_POST['streams'])) : '';

    $st = $conn->prepare("INSERT INTO jobs (company_id,title,description,requirements,salary_range,location,job_type,min_cgpa,deadline,allowed_streams) VALUES (?,?,?,?,?,?,?,?,?,?)");
    $st->bind_param('issssssdss', $cid, $title, $desc, $req, $salary, $loc, $type, $cgpa, $deadline, $streams);
    $st->execute();
    $jobId = $conn->insert_id;
    $st->close();
    notifyAllStudentsNewJob($conn, $title, $company['company_name'] ?? 'A Company', $jobId);
    $msg = '<div class="alert alert-success">Job posted successfully!</div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Post Job - Recruiter</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<nav class="navbar">
    <a href="dashboard.php" class="brand">🎓 Campus<span>Recruit</span></a>
    <div class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="post_job.php" class="active">Post Job</a>
        <a href="jobs.php">My Jobs</a>
        <a href="applications.php">Applications</a>
        <a href="profile.php">Company Profile</a>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>
</nav>
<div class="container">
    <?= $msg ?>
    <div class="card">
        <h2>Post a New Job</h2>
        <form method="POST">
            <div class="form-group">
                <label>Job Title *</label>
                <input type="text" name="title" placeholder="e.g. Software Engineer" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Job Type</label>
                    <select name="job_type">
                        <option value="Full-time">Full-time</option>
                        <option value="Internship">Internship</option>
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
                    <label>Salary / Stipend Range</label>
                    <input type="text" name="salary_range" placeholder="e.g. 5-8 LPA">
                </div>
                <div class="form-group">
                    <label>Minimum CGPA Required</label>
                    <input type="number" name="min_cgpa" step="0.1" min="0" max="10" value="0">
                </div>
            </div>
            <div class="form-group">
                <label>Application Deadline</label>
                <input type="date" name="deadline">
            </div>
            <div class="form-group">
                <label>Job Description</label>
                <textarea name="description" rows="4" placeholder="Describe the role and responsibilities..."></textarea>
            </div>
            <div class="form-group">
                <label>Requirements / Qualifications</label>
                <textarea name="requirements" rows="4" placeholder="List required skills, qualifications..."></textarea>
            </div>
            <div class="form-group">
                <label>Eligible Streams <small style="color:#999;font-weight:400">(leave all unchecked = open to all streams)</small></label>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:8px;margin-top:6px">
                    <?php
                    $streams_list = [
                        'Computer Science and Engineering',
                        'Computer Science in AIML',
                        'Computer Science in Data Science',
                        'Computer Science and Design',
                        'Information Technology',
                        'Electronics and Communication Engineering',
                        'Electrical Engineering',
                        'Electrical and Electronics Engineering',
                        'Mechanical Engineering',
                        'Civil Engineering',
                        'Chemical Engineering',
                        'Production Engineering',
                        'Electronics Engineering',
                        'IMsc Maths and Computing',
                        'MBA', 'MCA', 'Other'
                    ];
                    foreach ($streams_list as $s): ?>
                    <label style="display:flex;align-items:center;gap:6px;font-weight:400;cursor:pointer;font-size:0.88rem">
                        <input type="checkbox" name="streams[]" value="<?= htmlspecialchars($s) ?>">
                        <?= htmlspecialchars($s) ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Post Job</button>
        </form>
    </div>
</div>
</body>
</html>
