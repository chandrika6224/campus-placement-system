<?php
require_once '../includes/config.php';
requireLogin('admin');

$conn->query("ALTER TABLE student_profiles ADD COLUMN IF NOT EXISTS placed_salary DECIMAL(10,2) DEFAULT NULL");
$conn->query("ALTER TABLE student_profiles ADD COLUMN IF NOT EXISTS placed_company VARCHAR(200) DEFAULT NULL");
$conn->query("ALTER TABLE student_profiles ADD COLUMN IF NOT EXISTS placed_month_year VARCHAR(20) DEFAULT NULL");

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        $id = (int)$_POST['delete'];
        $conn->query("DELETE FROM users WHERE id=$id AND role='student'");
        $msg = '<div class="alert alert-success">Student deleted.</div>';
    }
    if (isset($_POST['set_salary'])) {
        $sid     = (int)$_POST['student_id'];
        $salary  = (float)$_POST['placed_salary'];
        $company = $conn->real_escape_string(trim($_POST['placed_company'] ?? ''));
        $monyear = $conn->real_escape_string(trim($_POST['placed_month_year'] ?? ''));
        $conn->query("UPDATE student_profiles SET placed_salary=$salary, placed_company='$company', placed_month_year='$monyear' WHERE user_id=$sid");
        $msg = '<div class="alert alert-success">Placement details updated.</div>';
    }
}

$placement_filter = trim($_GET['placement'] ?? '');
$dept_filter      = trim($_GET['dept'] ?? '');
$students = $conn->query("SELECT u.*, sp.department, sp.cgpa, sp.year_of_passing,
    sp.roll_number, sp.skills, sp.phone, sp.address, sp.gender,
    sp.tenth_percent, sp.twelfth_percent, sp.has_internship, sp.resume_path,
    COALESCE(sp.backlogs, 0) as backlogs,
    sp.placement_status, sp.placed_salary, sp.placed_company, sp.placed_month_year, sp.placed_company, sp.placed_month_year,
    (SELECT COUNT(*) FROM applications WHERE student_id=u.id) as app_count,
    (SELECT COUNT(*) FROM applications WHERE student_id=u.id AND status='selected') as selected_count,
    (SELECT COUNT(*) FROM applications WHERE student_id=u.id AND status='shortlisted') as shortlisted_count,
    (SELECT COUNT(*) FROM interviews WHERE student_id=u.id) as interview_count,
    (SELECT score FROM resume_analysis WHERE user_id=u.id ORDER BY analyzed_at DESC LIMIT 1) as resume_score
    FROM users u LEFT JOIN student_profiles sp ON u.id=sp.user_id
    WHERE u.role='student'
    " . ($placement_filter === 'Placed' ? " AND (sp.placement_status='Placed' OR u.id IN (SELECT student_id FROM applications WHERE status='selected'))" : "") . "
    " . ($dept_filter ? " AND sp.department='" . $conn->real_escape_string($dept_filter) . "'" : "") . "
    ORDER BY u.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Students - Admin</title>
<link rel="stylesheet" href="../css/style.css">
<style>
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center}
.modal-overlay.open{display:flex}
.modal-box{background:#fff;border-radius:14px;width:680px;max-width:95vw;max-height:90vh;overflow-y:auto;box-shadow:0 8px 40px rgba(0,0,0,0.2)}
.modal-header{padding:18px 24px 14px;border-bottom:1px solid #e8eaf6;display:flex;justify-content:space-between;align-items:center}
.modal-body{padding:20px 24px}
.modal-close{background:none;border:none;font-size:1.4rem;cursor:pointer;color:#666;line-height:1}
.s-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px}
.s-item{background:#f5f6fa;border-radius:8px;padding:10px 14px}
.s-item .lbl{font-size:0.7rem;color:#888;font-weight:600;text-transform:uppercase}
.s-item .val{font-size:0.95rem;font-weight:700;color:#1a237e;margin-top:2px}
.email-link{color:#3949ab;cursor:pointer;text-decoration:underline dotted;font-weight:600}
.email-link:hover{color:#1a237e}
.student-row:hover{background:#f0f4ff;cursor:pointer}
</style>
</head>
<body>
<?php require_once 'sidebar.php'; ?>
<div class="topbar">
    <div class="topbar-left">
        <button class="hamburger" onclick="toggleSidebar()">☰</button>
        <span class="page-title">Students</span>
    </div>
    <div class="topbar-right"><?php require_once '../notifications/widget.php'; ?></div>
</div>
<div class="main-content">
    <?= $msg ?>
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px">
            <h2 style="margin:0">All Students (<span id="student-count"><?= $students->num_rows ?></span>)
                <?php if ($placement_filter === 'Placed'): ?>
                <span style="background:#e8f5e9;color:#2e7d32;font-size:0.75rem;padding:3px 10px;border-radius:12px;font-weight:700;margin-left:8px">🎓 Placed Filter Active — <a href="students.php" style="color:#1a237e">Clear</a></span>
                <?php endif; ?>
                <?php if ($dept_filter): ?>
                <span style="background:#e8eaf6;color:#3f51b5;font-size:0.75rem;padding:3px 10px;border-radius:12px;font-weight:700;margin-left:8px">🎓 <?= htmlspecialchars($dept_filter) ?> — <a href="students.php" style="color:#1a237e">Clear</a></span>
                <?php endif; ?>
            </h2>
            <input type="text" id="student-search" placeholder="🔍 Search by name, email, department..." oninput="searchStudents()" style="padding:8px 14px;border:1px solid #ddd;border-radius:6px;font-size:0.9rem;width:300px">
        </div>
        <div class="table-wrap">
            <table>
                <tr><th>Name</th><th>Email</th><th>Department</th><th>CGPA</th><th>Year</th><th>Backlogs</th><th>Placement</th><th>Pkg (LPA)</th><th>Applications</th><th>Selected</th><th>Action</th></tr>
                <?php $allStudents = []; while($s = $students->fetch_assoc()): $allStudents[] = $s; ?>
                <tr class="student-row">
                    <td><span class="email-link" onclick="openStudent(<?= $s['id'] ?>)"><?= htmlspecialchars($s['name']) ?></span></td>
                    <td><?= htmlspecialchars($s['email']) ?></td>
                    <td><?= htmlspecialchars($s['department'] ?? '-') ?></td>
                    <td><?= $s['cgpa'] ?? '-' ?></td>
                    <td><?= $s['year_of_passing'] ?? '-' ?></td>
                    <td><?= (int)$s['backlogs'] ?></td>
                    <td>
                        <?php if (($s['placement_status'] ?? '') === 'Placed'): ?>
                        <span class="badge badge-selected" style="background:#e8f5e9;color:#2e7d32">🎓 Placed</span>
                        <?php else: ?>
                        <span style="color:#999;font-size:0.8rem">Not Placed</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (($s['placement_status'] ?? '') === 'Placed'): ?>
                        <form method="POST" style="display:flex;gap:4px;align-items:center;flex-wrap:wrap">
                            <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                            <input type="number" name="placed_salary" step="0.1" min="0" value="<?= $s['placed_salary'] ?? '' ?>" placeholder="LPA" style="width:60px;padding:3px 6px;border:1px solid #ddd;border-radius:4px;font-size:0.8rem" title="Salary in LPA">
                            <input type="text" name="placed_company" value="<?= htmlspecialchars($s['placed_company'] ?? '') ?>" placeholder="Company" style="width:100px;padding:3px 6px;border:1px solid #ddd;border-radius:4px;font-size:0.8rem">
                            <input type="month" name="placed_month_year" value="<?= $s['placed_month_year'] ?? '' ?>" style="padding:3px 6px;border:1px solid #ddd;border-radius:4px;font-size:0.8rem">
                            <button name="set_salary" class="btn btn-primary btn-sm" style="padding:3px 8px;font-size:0.75rem">&#10003;</button>
                        </form>
                        <?php else: ?>
                        <span style="color:#bbb;font-size:0.8rem">&mdash;</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $s['app_count'] ?></td>
                    <td><span class="badge badge-selected"><?= $s['selected_count'] ?></span></td>
                    <td>
                        <form method="POST" onsubmit="return confirm('Delete this student?')">
                            <input type="hidden" name="delete" value="<?= $s['id'] ?>">
                            <button class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>
</div>

<div class="modal-overlay" id="studentModal" onclick="if(event.target===this)closeStudent()">
    <div class="modal-box">
        <div class="modal-header">
            <div>
                <div style="font-size:1.1rem;font-weight:800;color:#1a237e" id="m-name"></div>
                <div style="font-size:0.82rem;color:#888" id="m-email"></div>
            </div>
            <button class="modal-close" onclick="closeStudent()">&#x2715;</button>
        </div>
        <div class="modal-body" id="m-body"></div>
    </div>
</div>

<script>
const studentsData = <?= json_encode(array_map(function($s) {
    return [
        'id'          => (int)$s['id'],
        'name'        => $s['name'],
        'email'       => $s['email'],
        'roll'        => $s['roll_number'] ?? '',
        'dept'        => $s['department'] ?? '',
        'cgpa'        => $s['cgpa'] ?? '',
        'year'        => $s['year_of_passing'] ?? '',
        'phone'       => $s['phone'] ?? '',
        'gender'      => $s['gender'] ?? '',
        'address'     => $s['address'] ?? '',
        'skills'      => $s['skills'] ?? '',
        'tenth'       => $s['tenth_percent'] ?? '',
        'twelfth'     => $s['twelfth_percent'] ?? '',
        'internship'  => (int)($s['has_internship'] ?? 0),
        'backlogs'    => (int)($s['backlogs'] ?? 0),
        'apps'        => (int)$s['app_count'],
        'shortlisted' => (int)$s['shortlisted_count'],
        'selected'    => (int)$s['selected_count'],
        'interviews'  => (int)$s['interview_count'],
        'resumeScore' => (int)($s['resume_score'] ?? 0),
        'placementStatus' => $s['placement_status'] ?? '',
        'placedSalary'    => $s['placed_salary'] ?? '',
        'placedCompany'   => $s['placed_company'] ?? '',
        'placedMonthYear' => $s['placed_month_year'] ?? '',
        'joined'      => $s['created_at'],
    ];
}, $allStudents), JSON_HEX_TAG) ?>;

const sidx = {}; studentsData.forEach(s => sidx[s.id] = s);

function openStudent(id) {
    const s = sidx[id];
    document.getElementById('m-name').textContent = s.name;
    document.getElementById('m-email').textContent = s.email;
    const skillTags = s.skills
        ? s.skills.split(',').map(x => x.trim()).filter(Boolean)
            .map(x => `<span style="background:#e8eaf6;color:#3949ab;padding:2px 9px;border-radius:12px;font-size:0.78rem;font-weight:600">${x}</span>`).join(' ')
        : '<span style="color:#aaa">None listed</span>';
    document.getElementById('m-body').innerHTML = `
        <div class="s-grid">
            <div class="s-item"><div class="lbl">Roll Number</div><div class="val">${s.roll||'&#8212;'}</div></div>
            <div class="s-item"><div class="lbl">Department</div><div class="val">${s.dept||'&#8212;'}</div></div>
            <div class="s-item"><div class="lbl">CGPA</div><div class="val">${s.cgpa||'&#8212;'}</div></div>
            <div class="s-item"><div class="lbl">Year of Passing</div><div class="val">${s.year||'&#8212;'}</div></div>
            <div class="s-item"><div class="lbl">Phone</div><div class="val">${s.phone||'&#8212;'}</div></div>
            <div class="s-item"><div class="lbl">Gender</div><div class="val">${s.gender||'&#8212;'}</div></div>
            <div class="s-item"><div class="lbl">10th %</div><div class="val">${s.tenth ? s.tenth+'%' : '&#8212;'}</div></div>
            <div class="s-item"><div class="lbl">12th %</div><div class="val">${s.twelfth ? s.twelfth+'%' : '&#8212;'}</div></div>
            <div class="s-item"><div class="lbl">Internship</div><div class="val">${s.internship ? '&#10003; Yes' : '&#10007; No'}</div></div>
            <div class="s-item"><div class="lbl">Backlogs</div><div class="val" style="color:${s.backlogs > 0 ? '#c62828' : '#2e7d32'}">${s.backlogs}</div></div>
            <div class="s-item"><div class="lbl">Resume Score</div><div class="val">${s.resumeScore > 0 ? s.resumeScore+'/100' : '&#8212;'}</div></div>
            <div class="s-item"><div class="lbl">Placement Status</div><div class="val" style="color:${s.placementStatus==='Placed'?'#2e7d32':'#555'}">${s.placementStatus==='Placed' ? '&#127891; Placed' : (s.placementStatus || '&#8212;')}</div></div>
            ${s.placementStatus==='Placed' ? `
            <div class="s-item"><div class="lbl">Placed Package</div><div class="val" style="color:#1a237e">${s.placedSalary ? s.placedSalary+' LPA' : 'Not set'}</div></div>
            <div class="s-item"><div class="lbl">Placed Company</div><div class="val">${s.placedCompany || 'Not set'}</div></div>
            <div class="s-item"><div class="lbl">Placement Month/Year</div><div class="val">${s.placedMonthYear || 'Not set'}</div></div>
            ` : ''}
            <div class="s-item"><div class="lbl">Applications</div><div class="val">${s.apps} applied &middot; ${s.shortlisted} shortlisted &middot; ${s.selected} selected</div></div>
            <div class="s-item"><div class="lbl">Interviews</div><div class="val">${s.interviews}</div></div>
            ${s.address ? `<div class="s-item" style="grid-column:span 2"><div class="lbl">Address</div><div class="val" style="font-size:0.85rem">${s.address}</div></div>` : ''}
        </div>
        <div style="margin-bottom:14px">
            <div style="font-weight:700;color:#1a237e;margin-bottom:6px;font-size:0.88rem">Skills</div>
            <div style="display:flex;flex-wrap:wrap;gap:5px">${skillTags}</div>
        </div>
        <div style="font-size:0.75rem;color:#aaa">Joined: ${s.joined}</div>
    `;
    document.getElementById('studentModal').classList.add('open');
}
function closeStudent() {
    document.getElementById('studentModal').classList.remove('open');
}
function searchStudents() {
    const q = document.getElementById('student-search').value.toLowerCase();
    let count = 0;
    document.querySelectorAll('.student-row').forEach(row => {
        const text = row.textContent.toLowerCase();
        const show = text.includes(q);
        row.style.display = show ? '' : 'none';
        if (show) count++;
    });
    document.getElementById('student-count').textContent = count;
}
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('show');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('sidebarOverlay').classList.remove('show');}
const _autoOpen = <?= isset($_GET['open']) ? (int)$_GET['open'] : 0 ?>;
if (_autoOpen && sidx[_autoOpen]) openStudent(_autoOpen);
</script>
</body>
</html>
