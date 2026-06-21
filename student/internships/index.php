<?php
require_once '../../includes/config.php';
requireLogin('student');
require_once '../../includes/notify.php';

$uid = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if ($uid === 0) { header('Location: ../../index.php'); exit(); }

// Create tables
$conn->query("CREATE TABLE IF NOT EXISTS internships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    requirements TEXT,
    stipend VARCHAR(100),
    location VARCHAR(100),
    duration VARCHAR(50),
    min_cgpa DECIMAL(4,2) DEFAULT 0,
    deadline DATE,
    status ENUM('open','closed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
)");

$conn->query("CREATE TABLE IF NOT EXISTS internship_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    internship_id INT NOT NULL,
    student_id INT NOT NULL,
    status ENUM('applied','shortlisted','rejected','selected','completed') DEFAULT 'applied',
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completion_date DATE NULL,
    certificate_issued TINYINT DEFAULT 0,
    FOREIGN KEY (internship_id) REFERENCES internships(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_intern_app (internship_id, student_id)
)");

$msg = '';

// Apply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply'])) {
    $iid = (int)$_POST['internship_id'];
    $stChk = $conn->prepare("SELECT id FROM internship_applications WHERE internship_id=? AND student_id=?");
    $stChk->bind_param('ii', $iid, $uid); $stChk->execute(); $stChk->store_result();
    if ($stChk->num_rows > 0) {
        $msg = '<div class="alert alert-error">You have already applied for this internship.</div>';
    } else {
        $stIns = $conn->prepare("INSERT INTO internship_applications (internship_id, student_id) VALUES (?,?)");
        $stIns->bind_param('ii', $iid, $uid); $stIns->execute(); $stIns->close();
        $stIN = $conn->prepare("SELECT i.title, c.company_name FROM internships i JOIN companies c ON i.company_id=c.id WHERE i.id=?");
        $stIN->bind_param('i', $iid); $stIN->execute();
        $intern = $stIN->get_result()->fetch_assoc(); $stIN->close();
        createNotification($conn, $uid, 'application', '🏢 Internship Applied', "You applied for {$intern['title']} at {$intern['company_name']}.", '/placement system/student/internships/index.php');
        $msg = '<div class="alert alert-success">✅ Applied successfully!</div>';
    }
    $stChk->close();
}

$stPr = $conn->prepare("SELECT * FROM student_profiles WHERE user_id=?");
$stPr->bind_param('i',$uid); $stPr->execute();
$profile = $stPr->get_result()->fetch_assoc(); $stPr->close();

$stInt = $conn->prepare("SELECT i.*, c.company_name, c.industry,
    (SELECT id FROM internship_applications WHERE internship_id=i.id AND student_id=?) as applied_id,
    (SELECT status FROM internship_applications WHERE internship_id=i.id AND student_id=?) as my_status
    FROM internships i JOIN companies c ON i.company_id=c.id WHERE i.status='open' ORDER BY i.created_at DESC");
$stInt->bind_param('ii',$uid,$uid); $stInt->execute();
$internships = $stInt->get_result(); $stInt->close();

$stMA = $conn->prepare("SELECT ia.*, i.title, i.duration, i.stipend, i.location, c.company_name, ia.certificate_issued FROM internship_applications ia JOIN internships i ON ia.internship_id=i.id JOIN companies c ON i.company_id=c.id WHERE ia.student_id=? ORDER BY ia.applied_at DESC");
$stMA->bind_param('i',$uid); $stMA->execute();
$my_apps = $stMA->get_result(); $stMA->close();

function internCount($conn, $sql, $types, ...$vals) {
    $st = $conn->prepare($sql); if ($types) $st->bind_param($types, ...$vals);
    $st->execute(); $c = (int)$st->get_result()->fetch_assoc()['c']; $st->close(); return $c;
}
$stats = [
    'applied'   => internCount($conn, "SELECT COUNT(*) as c FROM internship_applications WHERE student_id=?", 'i', $uid),
    'selected'  => internCount($conn, "SELECT COUNT(*) as c FROM internship_applications WHERE student_id=? AND status='selected'", 'i', $uid),
    'completed' => internCount($conn, "SELECT COUNT(*) as c FROM internship_applications WHERE student_id=? AND status='completed'", 'i', $uid),
    'open'      => internCount($conn, "SELECT COUNT(*) as c FROM internships WHERE status='open'", ''),
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Internships - Student</title>
<link rel="stylesheet" href="../../css/style.css">
<style>
.intern-card { background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 10px rgba(0,0,0,0.08);border-top:4px solid #7b1fa2;margin-bottom:16px;transition:transform 0.2s;cursor:pointer; }
.intern-card:hover { transform:translateY(-3px);box-shadow:0 4px 18px rgba(123,31,162,0.15); }
.cert-badge { background:linear-gradient(135deg,#ffd54f,#ffca28);color:#1a237e;padding:5px 14px;border-radius:20px;font-size:0.82rem;font-weight:700; }
.tab-btn { padding:10px 24px;border:none;background:#e8eaf6;border-radius:8px 8px 0 0;cursor:pointer;font-weight:600;color:#555;transition:all 0.2s; }
.tab-btn.active { background:#7b1fa2;color:#fff; }
.clickable-row{cursor:pointer}
.clickable-row:hover{background:#f5f0ff}
</style>
</head>
<body>
<?php require_once '../sidebar.php'; ?>
<div class="topbar">
    <div class="topbar-left">
        <button class="hamburger" onclick="toggleSidebar()">☰</button>
        <span class="page-title">🏢 Internships</span>
    </div>
    <div class="topbar-right"><?php require_once '../../notifications/widget.php'; ?></div>
</div>
<div class="main-content">
    <?= $msg ?>

    <div class="card" style="background:linear-gradient(135deg,#4a148c,#7b1fa2);color:#fff;margin-bottom:25px">
        <h2 style="color:#ffd54f;margin-bottom:8px">🏢 Internship Management</h2>
        <p style="color:#ce93d8">Explore internship opportunities, apply, and earn completion certificates.</p>
    </div>

    <div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:25px">
        <div class="stat-card"><div class="number"><?= $stats['open'] ?></div><div class="label">🏢 Open Internships</div></div>
        <div class="stat-card orange"><div class="number"><?= $stats['applied'] ?></div><div class="label">📋 Applied</div></div>
        <div class="stat-card green"><div class="number"><?= $stats['selected'] ?></div><div class="label">✅ Selected</div></div>
        <div class="stat-card" style="border-left-color:#7b1fa2"><div class="number"><?= $stats['completed'] ?></div><div class="label">🏆 Completed</div></div>
    </div>

    <!-- Tabs -->
    <div style="margin-bottom:0">
        <button class="tab-btn active" onclick="showTab('browse',this)">🔍 Browse Internships</button>
        <button class="tab-btn" onclick="showTab('myapps',this)">📋 My Applications</button>
    </div>

    <!-- Browse Tab -->
    <div id="tab-browse" class="card" style="border-radius:0 10px 10px 10px">
        <h2>Available Internships</h2>
        <?php if ($internships->num_rows === 0): ?>
        <p style="text-align:center;color:#999;padding:30px">No internships available right now. Check back later.</p>
        <?php else: ?>
        <?php $internListData = []; while($i = $internships->fetch_assoc()): $internListData[] = $i; ?>
        <div class="intern-card" onclick="openInternDetail(<?= $i['id'] ?>)">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px">
                <div style="flex:1">
                    <h3 style="color:#4a148c;margin-bottom:5px"><?= htmlspecialchars($i['title']) ?></h3>
                    <div style="color:#666;font-size:0.9rem;margin-bottom:10px">🏢 <?= htmlspecialchars($i['company_name']) ?> · <?= htmlspecialchars($i['industry'] ?? '') ?></div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px">
                        <?php if ($i['stipend']): ?><span style="background:#f3e5f5;color:#6a1b9a;padding:3px 10px;border-radius:20px;font-size:0.8rem">💰 <?= htmlspecialchars($i['stipend']) ?></span><?php endif; ?>
                        <?php if ($i['duration']): ?><span style="background:#e8f5e9;color:#2e7d32;padding:3px 10px;border-radius:20px;font-size:0.8rem">⏱️ <?= htmlspecialchars($i['duration']) ?></span><?php endif; ?>
                        <?php if ($i['location']): ?><span style="background:#e3f2fd;color:#1565c0;padding:3px 10px;border-radius:20px;font-size:0.8rem">📍 <?= htmlspecialchars($i['location']) ?></span><?php endif; ?>
                        <?php if ($i['min_cgpa'] > 0): ?><span style="background:#fff8e1;color:#f57f17;padding:3px 10px;border-radius:20px;font-size:0.8rem">📊 Min CGPA: <?= $i['min_cgpa'] ?></span><?php endif; ?>
                    </div>
                    <?php if ($i['description']): ?>
                    <p style="color:#555;font-size:0.88rem;margin-bottom:8px"><?= htmlspecialchars(substr($i['description'],0,150)) ?>...</p>
                    <?php endif; ?>
                    <small style="color:#999">Deadline: <?= $i['deadline'] ? date('d M Y', strtotime($i['deadline'])) : 'Open' ?></small>
                </div>
                <div style="text-align:right" onclick="event.stopPropagation()">
                    <?php if ($i['applied_id']): ?>
                    <span class="badge badge-<?= $i['my_status'] ?>"><?= ucfirst($i['my_status']) ?></span>
                    <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="internship_id" value="<?= $i['id'] ?>">
                        <button name="apply" class="btn btn-primary" style="background:linear-gradient(135deg,#4a148c,#7b1fa2)">Apply Now</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
        <?php endif; ?>
    </div>

    <!-- My Applications Tab -->
    <div id="tab-myapps" class="card" style="border-radius:0 10px 10px 10px;display:none">
        <h2>My Internship Applications</h2>
        <?php if ($my_apps->num_rows === 0): ?>
        <p style="text-align:center;color:#999;padding:30px">No applications yet. Browse and apply for internships.</p>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <tr><th>Internship</th><th>Company</th><th>Duration</th><th>Stipend</th><th>Status</th><th>Certificate</th><th>Applied</th></tr>
                <?php $myAppData = []; while($a = $my_apps->fetch_assoc()): $myAppData[] = $a; ?>
                <tr class="clickable-row" onclick="openMyAppDetail(<?= $a['id'] ?>)" title="View details">
                    <td style="color:#6a1b9a;font-weight:600"><?= htmlspecialchars($a['title']) ?></td>
                    <td><?= htmlspecialchars($a['company_name']) ?></td>
                    <td><?= htmlspecialchars($a['duration'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($a['stipend'] ?? '-') ?></td>
                    <td><span class="badge badge-<?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span></td>
                    <td onclick="event.stopPropagation()">
                        <?php if ($a['certificate_issued']): ?>
                        <a href="certificate.php?id=<?= $a['id'] ?>" class="cert-badge" target="_blank">🏆 Download</a>
                        <?php else: ?>
                        <span style="color:#999;font-size:0.82rem">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?= date('d M Y', strtotime($a['applied_at'])) ?></td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

</div>
</div><!-- app-layout -->
<?php require_once '../../chatbot/widget.php'; ?>
<script>
function showTab(tab, btn) {
    document.getElementById('tab-browse').style.display = tab === 'browse' ? 'block' : 'none';
    document.getElementById('tab-myapps').style.display = tab === 'myapps' ? 'block' : 'none';
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('show');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('sidebarOverlay').classList.remove('show');}
</script>
</body>
</html>
