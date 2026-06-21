<?php
require_once '../includes/config.php';
requireLogin('recruiter');

$uid = $_SESSION['user_id'];
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $company_name = trim($_POST['company_name'] ?? '');
    $industry    = trim($_POST['industry'] ?? '');
    $website     = trim($_POST['website'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $contact     = trim($_POST['contact_person'] ?? '');
    $phone       = trim($_POST['phone'] ?? '');

    $s1 = $conn->prepare("UPDATE users SET name=? WHERE id=?");
    $s1->bind_param('si', $name, $uid);
    $s1->execute(); $s1->close();

    $s2 = $conn->prepare("UPDATE companies SET company_name=?, industry=?, website=?, description=?, contact_person=?, phone=? WHERE user_id=?");
    $s2->bind_param('ssssssi', $company_name, $industry, $website, $description, $contact, $phone, $uid);
    $s2->execute(); $s2->close();

    $_SESSION['name'] = $name;
    $msg = '<div class="alert alert-success">Profile updated!</div>';
}

$stmtD = $conn->prepare("SELECT u.name, u.email, c.* FROM users u LEFT JOIN companies c ON u.id=c.user_id WHERE u.id=?");
$stmtD->bind_param('i', $uid);
$stmtD->execute();
$data = $stmtD->get_result()->fetch_assoc();
$stmtD->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Company Profile - Recruiter</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<nav class="navbar">
    <a href="dashboard.php" class="brand">🎓 Campus<span>Recruit</span></a>
    <div class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="post_job.php">Post Job</a>
        <a href="jobs.php">My Jobs</a>
        <a href="applications.php">Applications</a>
        <a href="profile.php" class="active">Company Profile</a>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>
</nav>
<div class="container">
    <?= $msg ?>
    <div class="card">
        <h2>Company Profile</h2>
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Contact Person Name</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($data['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Email (cannot change)</label>
                    <input type="email" value="<?= htmlspecialchars($data['email']) ?>" disabled>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Company Name *</label>
                    <input type="text" name="company_name" value="<?= htmlspecialchars($data['company_name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Industry</label>
                    <select name="industry">
                        <option value="">-- Select --</option>
                        <?php foreach(['IT/Software','Finance','Healthcare','Manufacturing','Consulting','E-commerce','Education','Other'] as $i): ?>
                        <option value="<?= $i ?>" <?= ($data['industry'] ?? '') === $i ? 'selected' : '' ?>><?= $i ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Website</label>
                    <input type="url" name="website" placeholder="https://company.com" value="<?= htmlspecialchars($data['website'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($data['phone'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Contact Person</label>
                <input type="text" name="contact_person" value="<?= htmlspecialchars($data['contact_person'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Company Description</label>
                <textarea name="description" rows="4"><?= htmlspecialchars($data['description'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Save Profile</button>
        </form>
    </div>
</div>
</body>
</html>
