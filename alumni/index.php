<?php
require_once '../includes/config.php';
requireLogin();

$uid  = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Create tables
$conn->query("CREATE TABLE IF NOT EXISTS alumni_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    company VARCHAR(150),
    designation VARCHAR(150),
    batch_year INT,
    department VARCHAR(100),
    linkedin VARCHAR(300),
    bio TEXT,
    is_mentor TINYINT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");
$conn->query("CREATE TABLE IF NOT EXISTS alumni_referrals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alumni_id INT NOT NULL,
    student_id INT NOT NULL,
    job_title VARCHAR(200),
    company VARCHAR(150),
    message TEXT,
    status ENUM('pending','accepted','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (alumni_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
)");
$conn->query("CREATE TABLE IF NOT EXISTS alumni_mentorship (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alumni_id INT NOT NULL,
    student_id INT NOT NULL,
    message TEXT,
    status ENUM('pending','active','closed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (alumni_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
)");

$msg = '';

// Register as alumni (student who is placed)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_alumni'])) {
    $company     = $conn->real_escape_string(trim($_POST['company']));
    $designation = $conn->real_escape_string(trim($_POST['designation']));
    $batch       = (int)$_POST['batch_year'];
    $dept        = $conn->real_escape_string(trim($_POST['department']));
    $linkedin    = $conn->real_escape_string(trim($_POST['linkedin'] ?? ''));
    $bio         = $conn->real_escape_string(trim($_POST['bio'] ?? ''));
    $mentor      = isset($_POST['is_mentor']) ? 1 : 0;
    $exists      = $conn->query("SELECT id FROM alumni_profiles WHERE user_id=$uid")->num_rows;
    if ($exists) {
        $conn->query("UPDATE alumni_profiles SET company='$company', designation='$designation', batch_year=$batch, department='$dept', linkedin='$linkedin', bio='$bio', is_mentor=$mentor WHERE user_id=$uid");
    } else {
        $conn->query("INSERT INTO alumni_profiles (user_id, company, designation, batch_year, department, linkedin, bio, is_mentor) VALUES ($uid,'$company','$designation',$batch,'$dept','$linkedin','$bio',$mentor)");
    }
    $msg = '<div class="alert alert-success">Alumni profile saved!</div>';
}

// Send referral
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_referral'])) {
    $alumniId  = (int)$_POST['alumni_id'];
    $jobTitle  = $conn->real_escape_string(trim($_POST['job_title']));
    $company   = $conn->real_escape_string(trim($_POST['company']));
    $message   = $conn->real_escape_string(trim($_POST['message']));
    $conn->query("INSERT INTO alumni_referrals (alumni_id, student_id, job_title, company, message) VALUES ($alumniId, $uid, '$jobTitle', '$company', '$message')");
    require_once '../includes/notify.php';
    createNotification($conn, $alumniId, 'system', '🤝 Referral Request', $_SESSION['name']." is requesting a referral for $jobTitle at $company.", '/placement system/alumni/index.php');
    $msg = '<div class="alert alert-success">Referral request sent!</div>';
}

// Request mentorship
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_mentorship'])) {
    $alumniId = (int)$_POST['alumni_id'];
    $message  = $conn->real_escape_string(trim($_POST['message']));
    $exists   = $conn->query("SELECT id FROM alumni_mentorship WHERE alumni_id=$alumniId AND student_id=$uid AND status!='closed'")->num_rows;
    if (!$exists) {
        $conn->query("INSERT INTO alumni_mentorship (alumni_id, student_id, message) VALUES ($alumniId, $uid, '$message')");
        require_once '../includes/notify.php';
        createNotification($conn, $alumniId, 'system', '🎓 Mentorship Request', $_SESSION['name']." wants mentorship guidance.", '/placement system/alumni/index.php');
        $msg = '<div class="alert alert-success">Mentorship request sent!</div>';
    } else {
        $msg = '<div class="alert alert-info">You already have an active mentorship request with this alumni.</div>';
    }
}

// Update referral status (alumni)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_referral'])) {
    $rid    = (int)$_POST['referral_id'];
    $status = $conn->real_escape_string($_POST['status']);
    $conn->query("UPDATE alumni_referrals SET status='$status' WHERE id=$rid AND alumni_id=$uid");
    $ref = $conn->query("SELECT * FROM alumni_referrals WHERE id=$rid")->fetch_assoc();
    if ($ref) {
        require_once '../includes/notify.php';
        createNotification($conn, $ref['student_id'], 'system', "🤝 Referral ".ucfirst($status), "Your referral request for {$ref['job_title']} has been ".ucfirst($status).".", '/placement system/alumni/index.php');
    }
    $msg = '<div class="alert alert-success">Referral status updated.</div>';
}

$myAlumniProfile = $conn->query("SELECT * FROM alumni_profiles WHERE user_id=$uid")->fetch_assoc();

// Alumni list
$search = $conn->real_escape_string(trim($_GET['q'] ?? ''));
$where  = $search ? "WHERE (u.name LIKE '%$search%' OR ap.company LIKE '%$search%' OR ap.department LIKE '%$search%')" : "";
$alumniList = $conn->query("SELECT ap.*, u.name, u.email FROM alumni_profiles ap JOIN users u ON ap.user_id=u.id $where ORDER BY ap.is_mentor DESC, ap.id DESC");

// My referrals (as student)
$myReferrals = $conn->query("SELECT ar.*, u.name as alumni_name FROM alumni_referrals ar JOIN users u ON ar.alumni_id=u.id WHERE ar.student_id=$uid ORDER BY ar.created_at DESC");

// Incoming referrals (as alumni)
$incomingReferrals = $myAlumniProfile ? $conn->query("SELECT ar.*, u.name as student_name, u.email as student_email, sp.department, sp.cgpa FROM alumni_referrals ar JOIN users u ON ar.student_id=u.id LEFT JOIN student_profiles sp ON sp.user_id=u.id WHERE ar.alumni_id=$uid ORDER BY ar.created_at DESC") : null;

$dashLink   = ($role === 'admin') ? '../admin/dashboard.php' : (($role === 'recruiter') ? '../recruiter/dashboard.php' : '../student/dashboard.php');
$logoutLink = '../' . $role . '/logout.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Alumni Referral System</title>
<link rel="stylesheet" href="../css/style.css">
<style>
.alumni-card{background:#fff;border-radius:12px;padding:18px;box-shadow:0 2px 10px rgba(0,0,0,0.08);border-top:4px solid #1a237e;transition:transform 0.2s}
.alumni-card:hover{transform:translateY(-3px)}
.mentor-badge{background:linear-gradient(135deg,#ffd54f,#ffca28);color:#1a237e;padding:3px 10px;border-radius:20px;font-size:0.75rem;font-weight:700}
.tab-btn{padding:9px 22px;border:none;background:#e8eaf6;border-radius:8px 8px 0 0;cursor:pointer;font-weight:600;color:#555;transition:all 0.2s}
.tab-btn.active{background:#1a237e;color:#fff}
</style>
</head>
<body>
<nav class="navbar">
    <a href="<?= $dashLink ?>" class="brand">🎓 Campus<span>Recruit</span></a>
    <div class="nav-links">
        <a href="<?= $dashLink ?>">Dashboard</a>
        <a href="index.php" class="active">🎓 Alumni</a>
        <?php require_once '../notifications/widget.php'; ?>
        <a href="<?= $logoutLink ?>" class="btn-logout">Logout</a>
    </div>
</nav>

<div class="container">
    <?= $msg ?>

    <div style="background:linear-gradient(135deg,#1a237e,#3949ab);color:#fff;border-radius:14px;padding:25px;margin-bottom:25px">
        <h2 style="color:#ffd54f;margin-bottom:6px">🎓 Alumni Referral & Mentorship</h2>
        <p style="color:#c5cae9">Connect with alumni for referrals, mentorship, and career guidance.</p>
    </div>

    <!-- Tabs -->
    <div style="margin-bottom:0">
        <button class="tab-btn active" onclick="showTab('browse',this)">🔍 Browse Alumni</button>
        <?php if ($role === 'student'): ?>
        <button class="tab-btn" onclick="showTab('myrefs',this)">📋 My Referrals</button>
        <button class="tab-btn" onclick="showTab('register',this)">✏️ Alumni Profile</button>
        <?php endif; ?>
        <?php if ($myAlumniProfile): ?>
        <button class="tab-btn" onclick="showTab('incoming',this)">📥 Incoming Requests</button>
        <?php endif; ?>
    </div>

    <!-- Browse Alumni -->
    <div id="tab-browse" class="card" style="border-radius:0 10px 10px 10px">
        <div style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap">
            <form method="GET" style="display:flex;gap:8px;flex:1">
                <input type="text" name="q" placeholder="🔍 Search by name, company, department..." value="<?= htmlspecialchars($search) ?>" style="flex:1;padding:9px 14px;border:1px solid #ddd;border-radius:8px">
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if ($search): ?><a href="index.php" class="btn" style="background:#e8eaf6;color:#333">Clear</a><?php endif; ?>
            </form>
        </div>

        <?php if ($alumniList->num_rows === 0): ?>
        <p style="text-align:center;color:#999;padding:30px">No alumni registered yet.</p>
        <?php else: ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px">
            <?php while($a = $alumniList->fetch_assoc()): ?>
            <div class="alumni-card">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px">
                    <div>
                        <div style="font-weight:700;color:#1a237e;font-size:1rem"><?= htmlspecialchars($a['name']) ?></div>
                        <div style="color:#555;font-size:0.85rem"><?= htmlspecialchars($a['designation'] ?? '') ?></div>
                        <div style="color:#3f51b5;font-size:0.82rem;font-weight:600">🏢 <?= htmlspecialchars($a['company'] ?? '') ?></div>
                    </div>
                    <?php if ($a['is_mentor']): ?><span class="mentor-badge">🌟 Mentor</span><?php endif; ?>
                </div>
                <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px">
                    <?php if ($a['department']): ?><span style="background:#e8eaf6;color:#3f51b5;padding:2px 8px;border-radius:10px;font-size:0.75rem"><?= htmlspecialchars($a['department']) ?></span><?php endif; ?>
                    <?php if ($a['batch_year']): ?><span style="background:#e8f5e9;color:#2e7d32;padding:2px 8px;border-radius:10px;font-size:0.75rem">Batch <?= $a['batch_year'] ?></span><?php endif; ?>
                </div>
                <?php if ($a['bio']): ?><p style="font-size:0.82rem;color:#666;margin-bottom:10px;line-height:1.5"><?= htmlspecialchars(substr($a['bio'],0,100)) ?>...</p><?php endif; ?>
                <div style="display:flex;gap:6px;flex-wrap:wrap">
                    <?php if ($a['linkedin']): ?><a href="<?= htmlspecialchars($a['linkedin']) ?>" target="_blank" class="btn btn-sm" style="background:#0077b5;color:#fff">LinkedIn</a><?php endif; ?>
                    <?php if ($role === 'student' && $a['user_id'] != $uid): ?>
                    <button onclick="openReferral(<?= $a['user_id'] ?>, '<?= htmlspecialchars(addslashes($a['name'])) ?>', '<?= htmlspecialchars(addslashes($a['company'])) ?>')" class="btn btn-primary btn-sm">🤝 Request Referral</button>
                    <?php if ($a['is_mentor']): ?>
                    <button onclick="openMentorship(<?= $a['user_id'] ?>, '<?= htmlspecialchars(addslashes($a['name'])) ?>')" class="btn btn-success btn-sm">🎓 Mentorship</button>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- My Referrals (student) -->
    <?php if ($role === 'student'): ?>
    <div id="tab-myrefs" class="card" style="border-radius:0 10px 10px 10px;display:none">
        <h2>My Referral Requests</h2>
        <?php if ($myReferrals->num_rows === 0): ?>
        <p style="color:#999;text-align:center;padding:20px">No referral requests sent yet.</p>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <tr><th>Alumni</th><th>Job Title</th><th>Company</th><th>Status</th><th>Date</th></tr>
                <?php while($r = $myReferrals->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($r['alumni_name']) ?></td>
                    <td><?= htmlspecialchars($r['job_title']) ?></td>
                    <td><?= htmlspecialchars($r['company']) ?></td>
                    <td><span class="badge badge-<?= $r['status'] === 'accepted' ? 'selected' : ($r['status'] === 'rejected' ? 'rejected' : 'applied') ?>"><?= ucfirst($r['status']) ?></span></td>
                    <td><?= date('d M Y', strtotime($r['created_at'])) ?></td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Register as Alumni -->
    <div id="tab-register" class="card" style="border-radius:0 10px 10px 10px;display:none">
        <h2><?= $myAlumniProfile ? 'Update' : 'Register as' ?> Alumni</h2>
        <form method="POST" style="max-width:600px">
            <div class="form-row">
                <div class="form-group">
                    <label>Current Company *</label>
                    <input type="text" name="company" value="<?= htmlspecialchars($myAlumniProfile['company'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Designation *</label>
                    <input type="text" name="designation" value="<?= htmlspecialchars($myAlumniProfile['designation'] ?? '') ?>" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Batch Year</label>
                    <input type="number" name="batch_year" min="2000" max="<?= date('Y') ?>" value="<?= $myAlumniProfile['batch_year'] ?? '' ?>">
                </div>
                <div class="form-group">
                    <label>Department</label>
                    <select name="department">
                        <option value="">-- Select --</option>
                        <?php foreach(['Computer Science','Information Technology','Electronics','Mechanical','Civil','Electrical','MBA','MCA'] as $d): ?>
                        <option value="<?= $d ?>" <?= ($myAlumniProfile['department'] ?? '') === $d ? 'selected' : '' ?>><?= $d ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>LinkedIn Profile URL</label>
                <input type="url" name="linkedin" value="<?= htmlspecialchars($myAlumniProfile['linkedin'] ?? '') ?>" placeholder="https://linkedin.com/in/...">
            </div>
            <div class="form-group">
                <label>Bio / About</label>
                <textarea name="bio" rows="3" placeholder="Brief introduction..."><?= htmlspecialchars($myAlumniProfile['bio'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" name="is_mentor" <?= ($myAlumniProfile['is_mentor'] ?? 0) ? 'checked' : '' ?>>
                    🌟 I am willing to mentor students
                </label>
            </div>
            <button name="register_alumni" class="btn btn-primary">💾 Save Alumni Profile</button>
        </form>
    </div>
    <?php endif; ?>

    <!-- Incoming Requests (alumni) -->
    <?php if ($myAlumniProfile && $incomingReferrals): ?>
    <div id="tab-incoming" class="card" style="border-radius:0 10px 10px 10px;display:none">
        <h2>📥 Incoming Referral Requests</h2>
        <?php if ($incomingReferrals->num_rows === 0): ?>
        <p style="color:#999;text-align:center;padding:20px">No referral requests yet.</p>
        <?php else: ?>
        <?php while($r = $incomingReferrals->fetch_assoc()): ?>
        <div style="background:#f8f9ff;border-radius:10px;padding:16px;margin-bottom:12px;border-left:4px solid #3f51b5">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px">
                <div>
                    <div style="font-weight:700;color:#1a237e"><?= htmlspecialchars($r['student_name']) ?></div>
                    <div style="font-size:0.82rem;color:#666"><?= htmlspecialchars($r['department'] ?? '') ?> · CGPA: <?= $r['cgpa'] ?: 'N/A' ?></div>
                    <div style="font-size:0.85rem;color:#555;margin-top:5px">Requesting referral for: <strong><?= htmlspecialchars($r['job_title']) ?></strong> at <?= htmlspecialchars($r['company']) ?></div>
                    <?php if ($r['message']): ?><div style="font-size:0.82rem;color:#777;margin-top:5px;font-style:italic">"<?= htmlspecialchars($r['message']) ?>"</div><?php endif; ?>
                    <div style="font-size:0.75rem;color:#999;margin-top:4px"><?= date('d M Y', strtotime($r['created_at'])) ?></div>
                </div>
                <?php if ($r['status'] === 'pending'): ?>
                <div style="display:flex;gap:6px">
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="referral_id" value="<?= $r['id'] ?>">
                        <input type="hidden" name="status" value="accepted">
                        <button name="update_referral" class="btn btn-success btn-sm">✅ Accept</button>
                    </form>
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="referral_id" value="<?= $r['id'] ?>">
                        <input type="hidden" name="status" value="rejected">
                        <button name="update_referral" class="btn btn-danger btn-sm">❌ Decline</button>
                    </form>
                </div>
                <?php else: ?>
                <span class="badge badge-<?= $r['status'] === 'accepted' ? 'selected' : 'rejected' ?>"><?= ucfirst($r['status']) ?></span>
                <?php endif; ?>
            </div>
        </div>
        <?php endwhile; ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Referral Modal -->
<div id="refModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;padding:20px">
    <div style="background:#fff;border-radius:12px;padding:28px;width:100%;max-width:480px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px">
            <h2 style="color:#1a237e;border:none;padding:0;margin:0;font-size:1.1rem">🤝 Request Referral</h2>
            <button onclick="document.getElementById('refModal').style.display='none'" style="background:none;border:none;font-size:1.5rem;cursor:pointer;color:#999">×</button>
        </div>
        <form method="POST">
            <input type="hidden" name="alumni_id" id="ref_alumni_id">
            <div class="form-group">
                <label>Alumni</label>
                <input type="text" id="ref_alumni_name" disabled style="background:#f5f5f5">
            </div>
            <div class="form-group">
                <label>Job Title *</label>
                <input type="text" name="job_title" required placeholder="e.g. Software Engineer">
            </div>
            <div class="form-group">
                <label>Company *</label>
                <input type="text" name="company" id="ref_company" required>
            </div>
            <div class="form-group">
                <label>Message</label>
                <textarea name="message" rows="3" placeholder="Brief introduction about yourself..."></textarea>
            </div>
            <div style="display:flex;gap:10px">
                <button name="send_referral" class="btn btn-primary">Send Request</button>
                <button type="button" onclick="document.getElementById('refModal').style.display='none'" class="btn" style="background:#e8eaf6;color:#333">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Mentorship Modal -->
<div id="mentorModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;padding:20px">
    <div style="background:#fff;border-radius:12px;padding:28px;width:100%;max-width:480px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px">
            <h2 style="color:#1a237e;border:none;padding:0;margin:0;font-size:1.1rem">🎓 Request Mentorship</h2>
            <button onclick="document.getElementById('mentorModal').style.display='none'" style="background:none;border:none;font-size:1.5rem;cursor:pointer;color:#999">×</button>
        </div>
        <form method="POST">
            <input type="hidden" name="alumni_id" id="mentor_alumni_id">
            <div class="form-group">
                <label>Mentor</label>
                <input type="text" id="mentor_alumni_name" disabled style="background:#f5f5f5">
            </div>
            <div class="form-group">
                <label>Message *</label>
                <textarea name="message" rows="4" required placeholder="What guidance are you looking for?"></textarea>
            </div>
            <div style="display:flex;gap:10px">
                <button name="request_mentorship" class="btn btn-success">Send Request</button>
                <button type="button" onclick="document.getElementById('mentorModal').style.display='none'" class="btn" style="background:#e8eaf6;color:#333">Cancel</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../chatbot/widget.php'; ?>
<script>
function showTab(tab, btn) {
    ['browse','myrefs','register','incoming'].forEach(t => {
        const el = document.getElementById('tab-'+t);
        if (el) el.style.display = 'none';
    });
    const el = document.getElementById('tab-'+tab);
    if (el) el.style.display = 'block';
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}
function openReferral(id, name, company) {
    document.getElementById('ref_alumni_id').value = id;
    document.getElementById('ref_alumni_name').value = name;
    document.getElementById('ref_company').value = company;
    document.getElementById('refModal').style.display = 'flex';
}
function openMentorship(id, name) {
    document.getElementById('mentor_alumni_id').value = id;
    document.getElementById('mentor_alumni_name').value = name;
    document.getElementById('mentorModal').style.display = 'flex';
}
</script>
</body>
</html>
