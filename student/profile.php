<?php
require_once '../includes/config.php';
requireLogin('student');
require_once '../includes/notify.php';

$uid = $_SESSION['user_id'];
$msg = $_SESSION['profile_msg'] ?? '';
unset($_SESSION['profile_msg']);

$conn->query("CREATE TABLE IF NOT EXISTS documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    doc_type ENUM('certificate','marksheet','id_proof','offer_letter','other') NOT NULL,
    doc_name VARCHAR(200) NOT NULL,
    file_path VARCHAR(300) NOT NULL,
    file_size INT DEFAULT 0,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    admin_remarks TEXT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

// Handle document upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_doc'])) {
    $docType = $_POST['doc_type'] ?? '';
    $docName = trim($_POST['doc_name'] ?? '');
    if (!in_array($docType, ['certificate','marksheet','id_proof','offer_letter','other'])) {
        $msg = '<div class="alert alert-error">Invalid document type.</div>';
    } elseif (empty($_FILES['doc_file']['name'])) {
        $msg = '<div class="alert alert-error">Please select a file.</div>';
    } else {
        $file = $_FILES['doc_file'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['pdf','jpg','jpeg','png','doc','docx'])) {
            $msg = '<div class="alert alert-error">Only PDF, JPG, PNG, DOC, DOCX allowed.</div>';
        } elseif ($file['size'] > 5*1024*1024) {
            $msg = '<div class="alert alert-error">File must be under 5MB.</div>';
        } else {
            $filename = 'doc_'.$uid.'_'.time().'_'.uniqid().'.'.$ext;
            if (move_uploaded_file($file['tmp_name'], '../uploads/documents/'.$filename)) {
                $dn = $conn->real_escape_string($docName ?: $file['name']);
                $fs = (int)$file['size'];
                $dt = $conn->real_escape_string($docType);
                $conn->query("INSERT INTO documents (user_id,doc_type,doc_name,file_path,file_size) VALUES ($uid,'$dt','$dn','$filename',$fs)");
                $admins = $conn->query("SELECT id FROM users WHERE role='admin'");
                while ($a = $admins->fetch_assoc()) {
                    createNotification($conn,$a['id'],'system','📄 New Document Uploaded',$_SESSION['name'].' uploaded a '.ucfirst(str_replace('_',' ',$docType)).' for verification.','/placement/admin/documents/index.php');
                }
                $_SESSION['profile_msg'] = '<div class="alert alert-success">✅ Document uploaded! Awaiting admin verification.</div>';
                header('Location: profile.php');
                exit;
            } else {
                $msg = '<div class="alert alert-error">Upload failed. Please try again.</div>';
            }
        }
    }
}

// Handle document delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_doc'])) {
    $did = (int)$_POST['doc_id'];
    $doc = $conn->query("SELECT * FROM documents WHERE id=$did AND user_id=$uid AND status='pending'")->fetch_assoc();
    if ($doc) {
        @unlink('../uploads/documents/'.$doc['file_path']);
        $conn->query("DELETE FROM documents WHERE id=$did");
        $_SESSION['profile_msg'] = '<div class="alert alert-success">Document deleted.</div>';
        header('Location: profile.php');
        exit;
    }
}

// Ensure profile row exists & backlogs column
$conn->query("INSERT IGNORE INTO student_profiles (user_id) VALUES (" . (int)$uid . ")");
$conn->query("ALTER TABLE student_profiles ADD COLUMN IF NOT EXISTS backlogs INT DEFAULT 0");
$conn->query("ALTER TABLE student_profiles ADD COLUMN IF NOT EXISTS placed_company VARCHAR(200) DEFAULT NULL");
$conn->query("ALTER TABLE student_profiles ADD COLUMN IF NOT EXISTS placed_month_year VARCHAR(20) DEFAULT NULL");

// Auto-sync: if student was selected via application, mark as Placed in profile
$selectedApp = $conn->query("SELECT j.title, c.company_name FROM applications a JOIN jobs j ON a.job_id=j.id JOIN companies c ON j.company_id=c.id WHERE a.student_id=$uid AND a.status='selected' LIMIT 1")->fetch_assoc();
if ($selectedApp) {
    $conn->query("UPDATE student_profiles SET placement_status='Placed', placed_company='" . $conn->real_escape_string($selectedApp['company_name']) . "' WHERE user_id=$uid AND (placement_status IS NULL OR placement_status='Not Placed')");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['upload_doc']) && !isset($_POST['delete_doc'])) {
    $name    = trim($_POST['name'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $roll    = trim($_POST['roll_number'] ?? '');
    $dept    = trim($_POST['department'] ?? '');
    $year    = (int)($_POST['year_of_passing'] ?? 0);
    $cgpa    = (float)($_POST['cgpa'] ?? 0);
    $skills  = trim($_POST['skills'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $backlogs = (int)($_POST['backlogs'] ?? 0);

    $stmt = $conn->prepare("UPDATE users SET name=? WHERE id=?");
    $stmt->bind_param('si', $name, $uid);
    $stmt->execute();
    $stmt->close();

    $stmt2 = $conn->prepare("UPDATE student_profiles SET roll_number=?, department=?, year_of_passing=?, cgpa=?, skills=?, phone=?, address=?, backlogs=? WHERE user_id=?");
    $stmt2->bind_param('ssidssiii', $roll, $dept, $year, $cgpa, $skills, $phone, $address, $backlogs, $uid);
    $stmt2->execute();
    $stmt2->close();

    if (!empty($_FILES['resume']['name'])) {
        $ext = strtolower(pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['pdf','doc','docx'])) {
            $filename = 'resume_' . $uid . '.' . $ext;
            move_uploaded_file($_FILES['resume']['tmp_name'], '../uploads/' . $filename);
            $stmt3 = $conn->prepare("UPDATE student_profiles SET resume_path=? WHERE user_id=?");
            $stmt3->bind_param('si', $filename, $uid);
            $stmt3->execute();
            $stmt3->close();
        }
    }
    $_SESSION['name'] = $name;
    $_SESSION['profile_msg'] = '<div class="alert alert-success">✅ Profile updated successfully!</div>';
    header('Location: profile.php');
    exit;
}

$stmtP = $conn->prepare("SELECT sp.*, u.name, u.email FROM student_profiles sp JOIN users u ON sp.user_id=u.id WHERE sp.user_id=?");
$stmtP->bind_param('i', $uid);
$stmtP->execute();
$profile = $stmtP->get_result()->fetch_assoc();
$stmtP->close();

// Safe defaults if still null
if (!$profile) {
    $profile = [
        'name' => $_SESSION['name'] ?? '', 'email' => $_SESSION['email'] ?? '',
        'roll_number' => '', 'phone' => '', 'department' => '',
        'year_of_passing' => '', 'cgpa' => '', 'resume_path' => '',
        'skills' => '', 'address' => '', 'backlogs' => 0
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile - Student</title>
<link rel="stylesheet" href="../css/style.css">
<style>
.app-layout{display:flex;min-height:100vh}
.sidebar{width:240px;height:100vh;flex-shrink:0;background:linear-gradient(180deg,#1a237e 0%,#283593 60%,#3949ab 100%);display:flex;flex-direction:column;position:fixed;top:0;left:0;z-index:200;box-shadow:3px 0 15px rgba(0,0,0,0.2);overflow-y:auto;overflow-x:hidden;scrollbar-width:thin;scrollbar-color:rgba(255,255,255,0.3) transparent}
.sidebar-brand{padding:20px 18px 15px;border-bottom:1px solid rgba(255,255,255,0.1)}
.sidebar-brand a{color:#fff;font-size:1.2rem;font-weight:800;text-decoration:none;display:block}
.sidebar-brand a span{color:#ffd54f}
.sidebar-brand .sub{color:#9fa8da;font-size:0.72rem;margin-top:3px}
.sidebar-user{padding:14px 18px;border-bottom:1px solid rgba(255,255,255,0.1);display:flex;align-items:center;gap:10px}
.user-avatar{width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#ffd54f,#ffca28);display:flex;align-items:center;justify-content:center;font-size:1rem;font-weight:800;color:#1a237e;flex-shrink:0}
.sec-label{padding:14px 18px 5px;color:#9fa8da;font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:1px}
.sidebar-nav a{display:flex;align-items:center;gap:10px;padding:9px 18px;color:#c5cae9;text-decoration:none;font-size:0.875rem;font-weight:500;transition:all 0.2s;border-left:3px solid transparent}
.sidebar-nav a:hover{background:rgba(255,255,255,0.08);color:#fff;border-left-color:rgba(255,255,255,0.3)}
.sidebar-nav a.active{background:rgba(255,255,255,0.15);color:#ffd54f;border-left-color:#ffd54f;font-weight:700}
.sidebar-nav a .ni{font-size:1rem;width:20px;text-align:center;flex-shrink:0}
.sidebar-footer{margin-top:auto;padding:15px 18px;border-top:1px solid rgba(255,255,255,0.1)}
.sidebar-footer a{display:flex;align-items:center;gap:8px;color:#ef9a9a;font-size:0.85rem;font-weight:600;text-decoration:none;padding:8px 10px;border-radius:8px;transition:all 0.2s}
.sidebar-footer a:hover{background:rgba(239,83,80,0.15);color:#ef5350}
.topbar{position:fixed;top:0;left:240px;right:0;height:58px;z-index:100;background:#fff;border-bottom:1px solid #e8eaf6;display:flex;align-items:center;justify-content:space-between;padding:0 24px;box-shadow:0 2px 8px rgba(0,0,0,0.06)}
.topbar .page-title{font-size:1.1rem;font-weight:700;color:#1a237e}
.main-content{margin-left:240px;margin-top:58px;padding:24px;flex:1}
.profile-card{background:#fff;border-radius:12px;padding:30px;box-shadow:0 2px 10px rgba(0,0,0,0.08)}
.profile-header{background:linear-gradient(135deg,#1a237e,#3949ab);border-radius:12px;padding:25px;color:#fff;margin-bottom:25px;display:flex;align-items:center;gap:20px}
.profile-avatar-big{width:70px;height:70px;border-radius:50%;background:linear-gradient(135deg,#ffd54f,#ffca28);display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:800;color:#1a237e;flex-shrink:0;border:3px solid rgba(255,255,255,0.3)}
@media(max-width:900px){.sidebar{transform:translateX(-100%)}.topbar{left:0}.main-content{margin-left:0}}
</style>
</head>
<body>
<div class="app-layout">

<aside class="sidebar">
    <div class="sidebar-brand">
        <a href="dashboard.php">🎓 Campus<span>Recruit</span></a>
        <div class="sub">Student Portal</div>
    </div>
    <div class="sidebar-user">
        <div class="user-avatar"><?= strtoupper(substr($_SESSION['name'], 0, 1)) ?></div>
        <div>
            <div style="color:#fff;font-size:0.85rem;font-weight:700"><?= htmlspecialchars($_SESSION['name']) ?></div>
            <div style="color:#9fa8da;font-size:0.72rem">Student</div>
        </div>
    </div>
    <div class="sec-label">Main</div>
    <nav class="sidebar-nav">
        <a href="dashboard.php"><span class="ni">🏠</span> Dashboard</a>
        <a href="jobs.php"><span class="ni">💼</span> Browse Jobs</a>
        <a href="applications.php"><span class="ni">📋</span> My Applications</a>
        <a href="profile.php" class="active"><span class="ni">👤</span> My Profile</a>
        <a href="notices.php"><span class="ni">📢</span> Notices</a>
    </nav>
    <div class="sec-label">AI & Smart</div>
    <nav class="sidebar-nav">
        <a href="resume_analyzer/index.php"><span class="ni">🤖</span> AI Resume</a>
        <a href="job_recommendation/index.php"><span class="ni">🎯</span> AI Job Match</a>
        <a href="placement_prediction/index.php"><span class="ni">🔮</span> Prediction</a>
        <a href="skill_gap/index.php"><span class="ni">🧩</span> Skill Gap</a>
    </nav>
    <div class="sec-label">Tests & Coding</div>
    <nav class="sidebar-nav">
        <a href="aptitude_test/index.php"><span class="ni">📝</span> Aptitude Tests</a>
        <a href="coding/index.php"><span class="ni">💻</span> Coding Practice</a>
    </nav>
    <div class="sec-label">Career</div>
    <nav class="sidebar-nav">
        <a href="interviews/index.php"><span class="ni">🎥</span> Interviews</a>
        <a href="internships/index.php"><span class="ni">🏢</span> Internships</a>
        <a href="documents/index.php"><span class="ni">📄</span> Documents</a>
        <a href="eligibility/index.php"><span class="ni">✅</span> Eligibility</a>
    </nav>
    <div class="sec-label">Community</div>
    <nav class="sidebar-nav">
        <a href="../forum/index.php"><span class="ni">💬</span> Forum</a>
        <a href="../alumni/index.php"><span class="ni">🎓</span> Alumni</a>
        <a href="../calendar/index.php"><span class="ni">📅</span> Calendar</a>
    </nav>
    <div class="sec-label">Account</div>
    <nav class="sidebar-nav">
        <a href="performance/index.php"><span class="ni">📊</span> Performance</a>
        <a href="gamification/index.php"><span class="ni">🏆</span> Achievements</a>
        <a href="../security/index.php"><span class="ni">🔒</span> Security</a>
    </nav>
    <div class="sidebar-footer">
        <a href="logout.php"><span>🚪</span> Logout</a>
    </div>
</aside>

<div class="topbar">
    <span class="page-title">👤 My Profile</span>
    <div style="display:flex;align-items:center;gap:10px">
        <span style="font-size:0.85rem;color:#666">Welcome, <strong style="color:#1a237e"><?= htmlspecialchars($_SESSION['name']) ?></strong></span>
        <?php require_once '../notifications/widget.php'; ?>
    </div>
</div>

<main class="main-content">
    <?= $msg ?>

    <!-- Profile Header -->
    <div class="profile-header">
        <div class="profile-avatar-big"><?= strtoupper(substr($profile['name'] ?? 'S', 0, 1)) ?></div>
        <div style="flex:1">
            <div style="font-size:1.3rem;font-weight:800;color:#ffd54f"><?= htmlspecialchars($profile['name'] ?? '') ?></div>
            <div style="color:#c5cae9;font-size:0.9rem;margin-top:3px"><?= htmlspecialchars($profile['email'] ?? '') ?></div>
            <div style="display:flex;gap:10px;margin-top:8px;flex-wrap:wrap">
                <?php if (!empty($profile['department'])): ?>
                <span style="background:rgba(255,255,255,0.15);padding:3px 10px;border-radius:10px;font-size:0.8rem">🎓 <?= htmlspecialchars($profile['department']) ?></span>
                <?php endif; ?>
                <?php if (!empty($profile['cgpa'])): ?>
                <span style="background:rgba(255,255,255,0.15);padding:3px 10px;border-radius:10px;font-size:0.8rem">📊 CGPA: <?= $profile['cgpa'] ?></span>
                <?php endif; ?>
                <?php if (!empty($profile['resume_path'])): ?>
                <span style="background:#e8f5e9;color:#2e7d32;padding:3px 10px;border-radius:10px;font-size:0.8rem;font-weight:700">✅ Resume Uploaded</span>
                <?php endif; ?>
                <?php if (($profile['placement_status'] ?? '') === 'Placed'): ?>
                <span style="background:#ffd54f;color:#1a237e;padding:3px 12px;border-radius:10px;font-size:0.8rem;font-weight:800">🎓 Placed</span>
                <?php endif; ?>
            </div>
        </div>
        <button onclick="toggleEdit()" id="editToggleBtn" class="btn" style="background:#ffd54f;color:#1a237e;font-weight:700;padding:8px 20px;border-radius:20px;border:none;cursor:pointer;flex-shrink:0">✏️ Edit Profile</button>
    </div>

    <!-- ── VIEW MODE ── -->
    <div id="view-mode" class="profile-card" style="margin-bottom:20px">
        <h2 style="color:#1a237e;margin-bottom:20px;font-size:1.1rem">📄 Profile Details</h2>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px">
            <?php
            $fields = [
                ['👤 Full Name',       $profile['name']           ?? ''],
                ['📧 Email',            $profile['email']          ?? ''],
                ['🎓 Department',       $profile['department']     ?? ''],
                ['📅 Year of Passing',  $profile['year_of_passing']?? ''],
                ['📊 CGPA',             $profile['cgpa']           ?? ''],
                ['📞 Phone',            $profile['phone']          ?? ''],
                ['🏠 Roll Number',      $profile['roll_number']    ?? ''],
                ['📍 Address',          $profile['address']        ?? ''],
                ['⚠️ Backlogs',         $profile['backlogs'] ?? 0],
                ['🎓 Placement Status',  $profile['placement_status'] ?? 'Not Set'],
            ];
            foreach ($fields as [$label, $value]):
                $display = !empty($value) ? htmlspecialchars($value) : '<span style="color:#bbb">Not set</span>';
            ?>
            <div style="padding:12px 16px;background:#f8f9ff;border-radius:8px;border-left:3px solid #e8eaf6">
                <div style="font-size:0.78rem;color:#999;font-weight:600;margin-bottom:4px"><?= $label ?></div>
                <div style="font-size:0.92rem;color:#1a237e;font-weight:600"><?= $display ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Skills -->
        <div style="margin-top:18px;padding:14px 16px;background:#f8f9ff;border-radius:8px;border-left:3px solid #e8eaf6">
            <div style="font-size:0.78rem;color:#999;font-weight:600;margin-bottom:8px">🧠 Skills</div>
            <?php if (!empty($profile['skills'])): ?>
            <div>
                <?php foreach (array_map('trim', explode(',', $profile['skills'])) as $sk): if(empty($sk)) continue; ?>
                <span style="display:inline-block;background:#e8eaf6;color:#3f51b5;padding:3px 10px;border-radius:12px;font-size:0.8rem;font-weight:600;margin:3px"><?= htmlspecialchars($sk) ?></span>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <span style="color:#bbb">Not set</span>
            <?php endif; ?>
        </div>

        <!-- Resume -->
        <div style="margin-top:18px;padding:14px 16px;background:#f8f9ff;border-radius:8px;border-left:3px solid #e8eaf6">
            <div style="font-size:0.78rem;color:#999;font-weight:600;margin-bottom:6px">📄 Resume</div>
            <?php if (!empty($profile['resume_path'])): ?>
            <a href="../uploads/<?= htmlspecialchars($profile['resume_path']) ?>" target="_blank" style="color:#2e7d32;font-weight:700;text-decoration:none">✅ View Resume</a>
            <?php else: ?>
            <span style="color:#bbb">Not uploaded</span>
            <?php endif; ?>
        </div>
    </div>

    <?php if (($profile['placement_status'] ?? '') === 'Placed'): ?>
    <!-- ── PLACEMENT DETAILS CARD ── -->
    <div class="profile-card" style="margin-bottom:20px;border-left:5px solid #43a047">
        <h2 style="color:#1b5e20;font-size:1.1rem;margin-bottom:18px">🎓 Placement Details</h2>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
            <div style="padding:16px;background:linear-gradient(135deg,#e8f5e9,#f1f8e9);border-radius:10px;border:1px solid #c8e6c9">
                <div style="font-size:0.75rem;color:#558b2f;font-weight:700;margin-bottom:4px">💰 Package</div>
                <div style="font-size:1.4rem;font-weight:800;color:#1b5e20">
                    <?= !empty($profile['placed_salary']) ? htmlspecialchars($profile['placed_salary']).' LPA' : '<span style="font-size:0.95rem;color:#aaa">Not set yet</span>' ?>
                </div>
            </div>
            <div style="padding:16px;background:linear-gradient(135deg,#e3f2fd,#e8eaf6);border-radius:10px;border:1px solid #bbdefb">
                <div style="font-size:0.75rem;color:#1565c0;font-weight:700;margin-bottom:4px">🏢 Company</div>
                <div style="font-size:1.1rem;font-weight:800;color:#0d47a1">
                    <?= !empty($profile['placed_company']) ? htmlspecialchars($profile['placed_company']) : '<span style="font-size:0.9rem;color:#aaa">Not set yet</span>' ?>
                </div>
            </div>
            <div style="padding:16px;background:linear-gradient(135deg,#fff8e1,#fffde7);border-radius:10px;border:1px solid #fff176;grid-column:span 2">
                <div style="font-size:0.75rem;color:#f57f17;font-weight:700;margin-bottom:4px">📅 Placed On</div>
                <div style="font-size:1.05rem;font-weight:700;color:#e65100">
                    <?php
                    if (!empty($profile['placed_month_year'])) {
                        // placed_month_year stored as YYYY-MM from <input type="month">
                        $dt = DateTime::createFromFormat('Y-m', $profile['placed_month_year']);
                        echo $dt ? $dt->format('F Y') : htmlspecialchars($profile['placed_month_year']);
                    } else {
                        echo '<span style="font-size:0.9rem;color:#aaa">Not set yet</span>';
                    }
                    ?>
                </div>
            </div>
        </div>
        <div style="margin-top:12px;font-size:0.8rem;color:#888">💡 Contact your placement coordinator to update these details.</div>
    </div>
    <?php endif; ?>

    <!-- ── EDIT MODE ── -->
    <div id="edit-mode" class="profile-card" style="display:none">
        <h2 style="color:#1a237e;margin-bottom:20px;font-size:1.1rem">✏️ Edit Profile</h2>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($profile['name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Email (cannot change)</label>
                    <input type="email" value="<?= htmlspecialchars($profile['email'] ?? '') ?>" disabled style="background:#f5f5f5;color:#999">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Roll Number</label>
                    <input type="text" name="roll_number" value="<?= htmlspecialchars($profile['roll_number'] ?? '') ?>" placeholder="e.g. CS2021001">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($profile['phone'] ?? '') ?>" placeholder="e.g. 9876543210">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Department / Stream</label>
                    <input type="text" name="department" value="<?= htmlspecialchars($profile['department'] ?? '') ?>" placeholder="e.g. Computer Science and Engineering">
                </div>
                <div class="form-group">
                    <label>Year of Passing</label>
                    <input type="number" name="year_of_passing" min="2020" max="2035" value="<?= htmlspecialchars($profile['year_of_passing'] ?? '') ?>" placeholder="e.g. 2025">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>CGPA (out of 10)</label>
                    <input type="number" name="cgpa" step="0.01" min="0" max="10" value="<?= htmlspecialchars($profile['cgpa'] ?? '') ?>" placeholder="e.g. 8.5">
                </div>
                <div class="form-group">
                    <label>Number of Backlogs</label>
                    <input type="number" name="backlogs" min="0" max="50" value="<?= (int)($profile['backlogs'] ?? 0) ?>" placeholder="0">
                    <small style="color:#999">Enter 0 if no backlogs</small>
                </div>
            </div>
            <div class="form-group">
                <label>Resume (PDF/DOC) <?php if (!empty($profile['resume_path'])): ?><span style="color:#2e7d32;font-weight:700">✅ Uploaded</span><?php endif; ?></label>
                <input type="file" name="resume" accept=".pdf,.doc,.docx">
                <small style="color:#999">Max 5MB · PDF, DOC, DOCX</small>
            </div>
            <div class="form-group">
                <label>Skills (comma separated)</label>
                <input type="text" name="skills" placeholder="e.g. PHP, MySQL, JavaScript, Python, React" value="<?= htmlspecialchars($profile['skills'] ?? '') ?>">
                <small style="color:#999">Add as many skills as possible — used for AI job matching</small>
            </div>
            <div class="form-group">
                <label>Address</label>
                <textarea name="address" rows="3" placeholder="Your current address..."><?= htmlspecialchars($profile['address'] ?? '') ?></textarea>
            </div>
            <div style="display:flex;gap:10px">
                <button type="submit" class="btn btn-primary" style="padding:12px 35px">💾 Save Profile</button>
                <button type="button" onclick="toggleEdit()" class="btn" style="padding:12px 25px;background:#f5f5f5;color:#555;border:1px solid #ddd">✕ Cancel</button>
            </div>
        </form>
    </div>

    <!-- ── DOCUMENTS SECTION ── -->
    <?php
    $typeIcons  = ['certificate'=>'🏆','marksheet'=>'📊','id_proof'=>'🧐','offer_letter'=>'📝','other'=>'📄'];
    $typeLabels = ['certificate'=>'Certificate','marksheet'=>'Marksheet','id_proof'=>'ID Proof','offer_letter'=>'Offer Letter','other'=>'Other'];
    $statusColors = ['pending'=>['#e65100','#fff8e1'],'approved'=>['#2e7d32','#e8f5e9'],'rejected'=>['#c62828','#ffebee']];
    $documents = $conn->query("SELECT * FROM documents WHERE user_id=$uid ORDER BY uploaded_at DESC");
    ?>
    <div class="profile-card" style="margin-top:20px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
            <h2 style="color:#4a148c;font-size:1.1rem;margin:0">📄 My Documents</h2>
            <button onclick="toggleDocs()" id="docToggleBtn" class="btn" style="background:#f3e5f5;color:#4a148c;font-weight:700;padding:6px 16px;border-radius:20px;border:none;cursor:pointer;font-size:0.85rem">➕ Upload Document</button>
        </div>

        <!-- Upload Form (hidden by default) -->
        <div id="doc-upload-form" style="display:none;background:#f8f4ff;border-radius:10px;padding:18px;margin-bottom:18px;border:1px solid #e1bee7">
            <h3 style="color:#4a148c;font-size:0.95rem;margin-bottom:14px">📤 Upload New Document</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="upload_doc" value="1">
                <div class="form-row">
                    <div class="form-group">
                        <label>Document Type *</label>
                        <select name="doc_type" required>
                            <option value="">-- Select Type --</option>
                            <option value="certificate">🏆 Certificate</option>
                            <option value="marksheet">📊 Marksheet / Transcript</option>
                            <option value="id_proof">🧐 ID Proof (Aadhar/PAN/Passport)</option>
                            <option value="offer_letter">📝 Offer Letter</option>
                            <option value="other">📄 Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Document Name *</label>
                        <input type="text" name="doc_name" placeholder="e.g. Python Certificate - Coursera" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Upload File * <small style="color:#999">(PDF, JPG, PNG, DOC, DOCX · Max 5MB)</small></label>
                    <input type="file" name="doc_file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required onchange="document.getElementById('doc-fname').textContent = this.files[0]?.name || ''">
                    <div id="doc-fname" style="font-size:0.82rem;color:#2e7d32;margin-top:4px"></div>
                </div>
                <div style="display:flex;gap:10px">
                    <button type="submit" class="btn btn-primary" style="background:linear-gradient(135deg,#4a148c,#6a1b9a)">📤 Upload</button>
                    <button type="button" onclick="toggleDocs()" class="btn" style="background:#f5f5f5;color:#555;border:1px solid #ddd">Cancel</button>
                </div>
            </form>
        </div>

        <!-- Stats row -->
        <?php
        $dStats = $conn->query("SELECT status, COUNT(*) as c FROM documents WHERE user_id=$uid GROUP BY status")->fetch_all(MYSQLI_ASSOC);
        $dCount = ['pending'=>0,'approved'=>0,'rejected'=>0,'total'=>0];
        foreach ($dStats as $ds) { $dCount[$ds['status']] = $ds['c']; $dCount['total'] += $ds['c']; }
        ?>
        <?php if ($dCount['total'] > 0): ?>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px">
            <span style="background:#e8eaf6;color:#3f51b5;padding:4px 12px;border-radius:20px;font-size:0.82rem;font-weight:700">📂 <?= $dCount['total'] ?> Total</span>
            <?php if ($dCount['pending']): ?><span style="background:#fff8e1;color:#e65100;padding:4px 12px;border-radius:20px;font-size:0.82rem;font-weight:700">⏳ <?= $dCount['pending'] ?> Pending</span><?php endif; ?>
            <?php if ($dCount['approved']): ?><span style="background:#e8f5e9;color:#2e7d32;padding:4px 12px;border-radius:20px;font-size:0.82rem;font-weight:700">✅ <?= $dCount['approved'] ?> Approved</span><?php endif; ?>
            <?php if ($dCount['rejected']): ?><span style="background:#ffebee;color:#c62828;padding:4px 12px;border-radius:20px;font-size:0.82rem;font-weight:700">❌ <?= $dCount['rejected'] ?> Rejected</span><?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Documents list -->
        <?php if ($documents->num_rows === 0): ?>
        <div style="text-align:center;padding:30px;color:#999">
            <div style="font-size:2.5rem;margin-bottom:8px">📂</div>
            <p>No documents uploaded yet. Click "Upload Document" to get started.</p>
        </div>
        <?php else: while ($d = $documents->fetch_assoc()):
            $sc = $statusColors[$d['status']];
            $isImg = in_array(strtolower(pathinfo($d['file_path'], PATHINFO_EXTENSION)), ['jpg','jpeg','png']);
        ?>
        <div style="background:#fff;border-radius:10px;padding:14px 16px;margin-bottom:10px;border-left:5px solid <?= $d['status']==='approved'?'#43a047':($d['status']==='rejected'?'#e53935':'#fb8c00') ?>;box-shadow:0 2px 6px rgba(0,0,0,0.06)">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px">
                <div style="flex:1">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px">
                        <span style="font-size:1.2rem"><?= $typeIcons[$d['doc_type']] ?></span>
                        <strong style="color:#1a237e;font-size:0.9rem"><?= htmlspecialchars($d['doc_name']) ?></strong>
                        <span style="background:<?= $sc[1] ?>;color:<?= $sc[0] ?>;padding:2px 10px;border-radius:12px;font-size:0.75rem;font-weight:700">
                            <?= $d['status']==='pending'?'⏳ Pending for Approval':($d['status']==='approved'?'✅ Approved':'❌ Rejected') ?>
                        </span>
                    </div>
                    <div style="font-size:0.78rem;color:#999">
                        <?= $typeLabels[$d['doc_type']] ?> · <?= date('d M Y', strtotime($d['uploaded_at'])) ?> · <?= round($d['file_size']/1024,1) ?> KB
                    </div>
                    <?php if ($d['admin_remarks']): ?>
                    <div style="background:<?= $sc[1] ?>;border-radius:6px;padding:6px 10px;font-size:0.82rem;color:<?= $sc[0] ?>;margin-top:6px">
                        💬 Admin: <?= htmlspecialchars($d['admin_remarks']) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div style="display:flex;gap:6px;flex-shrink:0">
                    <a href="../uploads/documents/<?= htmlspecialchars($d['file_path']) ?>" target="_blank" class="btn btn-primary btn-sm"><?= $isImg?'🖼️ View':'📄 View' ?></a>
                    <?php if ($d['status'] === 'pending'): ?>
                    <form method="POST" onsubmit="return confirm('Delete this document?')" style="display:inline">
                        <input type="hidden" name="doc_id" value="<?= $d['id'] ?>">
                        <button name="delete_doc" class="btn btn-danger btn-sm">🗑️</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endwhile; endif; ?>
    </div>

</main>
</div>
<?php require_once '../chatbot/widget.php'; ?>
<script>
function toggleEdit() {
    const view = document.getElementById('view-mode');
    const edit = document.getElementById('edit-mode');
    const btn  = document.getElementById('editToggleBtn');
    const isEditing = edit.style.display !== 'none';
    view.style.display = isEditing ? 'block' : 'none';
    edit.style.display = isEditing ? 'none'  : 'block';
    btn.textContent    = isEditing ? '✏️ Edit Profile' : '✖ Close Edit';
    if (!isEditing) edit.scrollIntoView({ behavior: 'smooth', block: 'start' });
}
function toggleDocs() {
    const f = document.getElementById('doc-upload-form');
    const b = document.getElementById('docToggleBtn');
    const open = f.style.display !== 'none';
    f.style.display = open ? 'none' : 'block';
    b.textContent   = open ? '➕ Upload Document' : '✖ Cancel';
    if (!open) f.scrollIntoView({ behavior: 'smooth', block: 'start' });
}
<?php if ($msg): ?>window.scrollTo(0,0);<?php endif; ?>
</script>
</body>
</html>

