<?php
require_once '../includes/config.php';
requireLogin('admin');

function admCount($conn, $sql) {
    $st = $conn->prepare($sql); $st->execute();
    $c = (int)$st->get_result()->fetch_assoc()['c']; $st->close(); return $c;
}
$stats = [
    'students'    => admCount($conn, "SELECT COUNT(*) as c FROM users WHERE role='student'"),
    'recruiters'  => admCount($conn, "SELECT COUNT(*) as c FROM users WHERE role='recruiter'"),
    'jobs'        => admCount($conn, "SELECT COUNT(*) as c FROM jobs"),
    'applications'=> admCount($conn, "SELECT COUNT(*) as c FROM applications"),
    'placed'       => admCount($conn, "SELECT COUNT(DISTINCT uid) as c FROM (SELECT user_id as uid FROM student_profiles WHERE placement_status='Placed' UNION SELECT student_id as uid FROM applications WHERE status='selected') t"),
    'selected'    => admCount($conn, "SELECT COUNT(DISTINCT uid) as c FROM (SELECT student_id as uid FROM applications WHERE status='selected' UNION SELECT user_id as uid FROM student_profiles WHERE placement_status='Placed') t"),
    'open_jobs'   => admCount($conn, "SELECT COUNT(*) as c FROM jobs WHERE status='open'"),
    'iv_total'    => admCount($conn, "SELECT COUNT(*) as c FROM interviews"),
    'iv_completed'=> admCount($conn, "SELECT COUNT(*) as c FROM interviews WHERE status='completed'"),
    'iv_scheduled'=> admCount($conn, "SELECT COUNT(*) as c FROM interviews WHERE status='scheduled'"),
];

// Modal data
$allStudents     = $conn->query("SELECT u.id, u.name, u.email, sp.department, sp.cgpa, sp.year_of_passing, COALESCE(sp.backlogs,0) as backlogs FROM users u LEFT JOIN student_profiles sp ON sp.user_id=u.id WHERE u.role='student' ORDER BY u.name ASC");
$allRecruiters   = $conn->query("SELECT u.id, u.name, u.email, u.created_at, c.company_name, c.industry, c.website, c.description as company_desc, (SELECT COUNT(*) FROM jobs WHERE company_id=c.id) as job_count, (SELECT COUNT(*) FROM applications a JOIN jobs j ON a.job_id=j.id WHERE j.company_id=c.id) as app_count FROM users u LEFT JOIN companies c ON c.user_id=u.id WHERE u.role='recruiter' ORDER BY u.name ASC");
$openJobs        = $conn->query("SELECT j.id, j.title, c.company_name, j.location, j.job_type, j.deadline FROM jobs j JOIN companies c ON j.company_id=c.id WHERE j.status='open' ORDER BY j.created_at DESC");
$allApplications = $conn->query("SELECT a.id, u.name as student_name, j.title as job_title, c.company_name, a.status, a.applied_at FROM applications a JOIN users u ON a.student_id=u.id JOIN jobs j ON a.job_id=j.id JOIN companies c ON j.company_id=c.id ORDER BY a.applied_at DESC");
$selectedStudents= $conn->query("SELECT u.id as student_id, u.name as student_name, u.email, j.title as job_title, c.company_name, a.applied_at FROM applications a JOIN users u ON a.student_id=u.id JOIN jobs j ON a.job_id=j.id JOIN companies c ON j.company_id=c.id WHERE a.status='selected' ORDER BY a.applied_at DESC");
$placedStudents  = $conn->query("
    SELECT u.id as student_id, u.name, u.email, sp.department, sp.cgpa, sp.placement_status,
        CASE WHEN sp.placed_salary > 0 THEN CONCAT(sp.placed_salary,' LPA') ELSE NULL END as pkg,
        'CSV' as source
    FROM student_profiles sp
    JOIN users u ON sp.user_id=u.id
    WHERE sp.placement_status='Placed'
    UNION
    SELECT u.id as student_id, u.name, u.email, sp.department, sp.cgpa, 'Placed' as placement_status,
        j.salary_range as pkg,
        'App' as source
    FROM applications a
    JOIN users u ON a.student_id=u.id
    JOIN student_profiles sp ON u.id=sp.user_id
    JOIN jobs j ON a.job_id=j.id
    WHERE a.status='selected'
      AND u.id NOT IN (SELECT user_id FROM student_profiles WHERE placement_status='Placed')
    ORDER BY name ASC
");
$allJobs         = $conn->query("SELECT j.id, j.title, c.company_name, j.status, j.job_type, j.deadline FROM jobs j JOIN companies c ON j.company_id=c.id ORDER BY j.created_at DESC");
$allInterviews   = $conn->query("SELECT i.id, u.name as student_name, j.title as job_title, c.company_name, i.scheduled_at, i.status FROM interviews i JOIN users u ON i.student_id=u.id JOIN jobs j ON i.job_id=j.id JOIN companies c ON j.company_id=c.id ORDER BY i.scheduled_at DESC");
$completedIV     = $conn->query("SELECT i.id, u.name as student_name, j.title as job_title, c.company_name, i.scheduled_at FROM interviews i JOIN users u ON i.student_id=u.id JOIN jobs j ON i.job_id=j.id JOIN companies c ON j.company_id=c.id WHERE i.status='completed' ORDER BY i.scheduled_at DESC");
$scheduledIV     = $conn->query("SELECT i.id, u.name as student_name, j.title as job_title, c.company_name, i.scheduled_at FROM interviews i JOIN users u ON i.student_id=u.id JOIN jobs j ON i.job_id=j.id JOIN companies c ON j.company_id=c.id WHERE i.status='scheduled' ORDER BY i.scheduled_at ASC");

$stRA = $conn->prepare("SELECT a.*, u.name as student_name, j.title as job_title, c.company_name FROM applications a JOIN users u ON a.student_id=u.id JOIN jobs j ON a.job_id=j.id JOIN companies c ON j.company_id=c.id ORDER BY a.applied_at DESC LIMIT 8");
$stRA->execute(); $recent_apps = $stRA->get_result(); $stRA->close();

$stN = $conn->prepare("SELECT n.*, u.name as posted_by_name FROM notices n JOIN users u ON n.posted_by=u.id ORDER BY n.created_at DESC LIMIT 5");
$stN->execute(); $notices = $stN->get_result(); $stN->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - Campus Recruit</title>
<link rel="stylesheet" href="../css/style.css">
<style>
.stat-card { cursor:pointer; transition:transform 0.2s,box-shadow 0.2s; }
.stat-card:hover { transform:translateY(-4px); box-shadow:0 6px 20px rgba(0,0,0,0.13); }
.stat-card:hover .label { color:#3f51b5; }
.modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center; }
.modal-overlay.open { display:flex; }
.modal-box { background:#fff; border-radius:14px; width:820px; max-width:95vw; max-height:88vh; overflow-y:auto; box-shadow:0 8px 40px rgba(0,0,0,0.2); }
.modal-header { padding:18px 24px; border-bottom:1px solid #e8eaf6; display:flex; justify-content:space-between; align-items:center; position:sticky; top:0; background:#fff; z-index:1; }
.modal-body { padding:20px 24px; }
.modal-close { background:none; border:none; font-size:1.4rem; cursor:pointer; color:#666; line-height:1; }
.clickable-row { cursor:pointer; }
.clickable-row:hover { background:#f0f4ff; }
</style>
</head>
<body>
<?php require_once 'sidebar.php'; ?>

<div class="topbar">
    <div class="topbar-left">
        <button class="hamburger" onclick="toggleSidebar()">☰</button>
        <span class="page-title">Dashboard</span>
    </div>
    <div class="topbar-right">
        <?php require_once '../notifications/widget.php'; ?>
    </div>
</div>

<div class="main-content">
    <div class="stats-grid">
        <div class="stat-card" onclick="openModal('students')" title="Click to view all students">
            <div class="number"><?= $stats['students'] ?></div>
            <div class="label">👨🎓 Total Students</div>
        </div>
        <div class="stat-card orange" onclick="openModal('recruiters')" title="Click to view all recruiters">
            <div class="number"><?= $stats['recruiters'] ?></div>
            <div class="label">🏢 Recruiters</div>
        </div>
        <div class="stat-card" onclick="openModal('open_jobs')" title="Click to view open jobs">
            <div class="number"><?= $stats['open_jobs'] ?></div>
            <div class="label">💼 Open Jobs</div>
        </div>
        <div class="stat-card green" onclick="openModal('applications')" title="Click to view all applications">
            <div class="number"><?= $stats['applications'] ?></div>
            <div class="label">📋 Applications</div>
        </div>
        <div class="stat-card green" onclick="openModal('selected')" title="Click to view selected students">
            <div class="number"><?= $stats['selected'] ?></div>
            <div class="label">✅ Students Selected</div>
        </div>
        <div class="stat-card green" onclick="openModal('placed')" title="Click to view placed students">
            <div class="number"><?= $stats['placed'] ?></div>
            <div class="label">🎓 Students Placed</div>
        </div>
        <div class="stat-card red" onclick="openModal('all_jobs')" title="Click to view all jobs">
            <div class="number"><?= $stats['jobs'] ?></div>
            <div class="label">📌 Total Jobs Posted</div>
        </div>
        <div class="stat-card" onclick="openModal('iv_total')" title="Click to view all interviews">
            <div class="number"><?= $stats['iv_total'] ?></div>
            <div class="label">🎥 Total Interviews</div>
        </div>
        <div class="stat-card green" onclick="openModal('iv_completed')" title="Click to view completed interviews">
            <div class="number"><?= $stats['iv_completed'] ?></div>
            <div class="label">✅ Interviews Attended</div>
        </div>
        <div class="stat-card orange" onclick="openModal('iv_scheduled')" title="Click to view upcoming interviews">
            <div class="number"><?= $stats['iv_scheduled'] ?></div>
            <div class="label">📅 Upcoming Interviews</div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px">
        <div class="card">
            <h2>Recent Applications</h2>
            <div class="table-wrap">
                <table>
                    <tr><th>Student</th><th>Job</th><th>Company</th><th>Status</th><th>Date</th></tr>
                    <?php while($row = $recent_apps->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['student_name']) ?></td>
                        <td><?= htmlspecialchars($row['job_title']) ?></td>
                        <td><?= htmlspecialchars($row['company_name']) ?></td>
                        <td><span class="badge badge-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span></td>
                        <td><?= date('d M Y', strtotime($row['applied_at'])) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            </div>
        </div>
        <div class="card">
            <h2>Notice Board <a href="notices.php" class="btn btn-primary btn-sm" style="float:right">+ Add</a></h2>
            <?php while($n = $notices->fetch_assoc()): ?>
            <div class="notice-item">
                <h4><?= htmlspecialchars($n['title']) ?></h4>
                <p style="font-size:0.85rem;color:#555;margin:4px 0"><?= htmlspecialchars(substr($n['content'],0,80)) ?>...</p>
                <div class="date"><?= date('d M Y', strtotime($n['created_at'])) ?></div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal-overlay" id="dashModal" onclick="if(event.target===this)closeModal()">
    <div class="modal-box">
        <div class="modal-header">
            <strong id="modal-title" style="color:#1a237e;font-size:1.05rem"></strong>
            <button class="modal-close" onclick="closeModal()">&#x2715;</button>
        </div>
        <div class="modal-body">

            <!-- Students -->
            <div id="m-students" class="m-section" style="display:none">
                <div class="table-wrap"><table>
                    <tr><th>#</th><th>Name</th><th>Email</th><th>Department</th><th>CGPA</th><th>Year</th><th>Backlogs</th></tr>
                    <?php $i=1; while($r=$allStudents->fetch_assoc()): ?>
                    <tr style="cursor:pointer" onclick="window.location='students.php?open=<?= $r['id'] ?>'" title="View student details">
                        <td><?= $i++ ?></td>
                        <td style="color:#3949ab;font-weight:600"><?= htmlspecialchars($r['name']) ?></td>
                        <td><?= htmlspecialchars($r['email']) ?></td>
                        <td><?= htmlspecialchars($r['department']??'-') ?></td>
                        <td><?= $r['cgpa']??'-' ?></td>
                        <td><?= $r['year_of_passing']??'-' ?></td>
                        <td style="color:<?= $r['backlogs']>0?'#c62828':'#2e7d32' ?>;font-weight:700"><?= $r['backlogs'] ?></td>
                    </tr>
                    <?php endwhile; ?>
                </table></div>
            </div>

            <!-- Recruiters -->
            <div id="m-recruiters" class="m-section" style="display:none">
                <div class="table-wrap"><table>
                    <tr><th>#</th><th>Name</th><th>Email</th><th>Company</th><th>Industry</th><th>Jobs</th></tr>
                    <?php $i=1; $recruitersData=[]; while($r=$allRecruiters->fetch_assoc()): $recruitersData[]=$r; ?>
                    <tr class="clickable-row" onclick="openRecruiterPopup(<?= $r['id'] ?>)" title="View recruiter details">
                        <td><?= $i++ ?></td>
                        <td style="color:#3949ab;font-weight:600"><?= htmlspecialchars($r['name']) ?></td>
                        <td><?= htmlspecialchars($r['email']) ?></td>
                        <td><?= htmlspecialchars($r['company_name']??'-') ?></td>
                        <td><?= htmlspecialchars($r['industry']??'-') ?></td>
                        <td><?= $r['job_count'] ?></td>
                    </tr>
                    <?php endwhile; ?>
                </table></div>
            </div>

            <!-- Open Jobs -->
            <div id="m-open_jobs" class="m-section" style="display:none">
                <div class="table-wrap"><table>
                    <tr><th>#</th><th>Title</th><th>Company</th><th>Location</th><th>Type</th><th>Deadline</th></tr>
                    <?php $i=1; while($r=$openJobs->fetch_assoc()): ?>
                    <tr class="clickable-row" onclick="window.location='jobs.php?open=<?= $r['id'] ?>'" title="View job details">
                        <td><?= $i++ ?></td>
                        <td style="color:#3949ab;font-weight:600"><?= htmlspecialchars($r['title']) ?></td>
                        <td><?= htmlspecialchars($r['company_name']) ?></td>
                        <td><?= htmlspecialchars($r['location']??'-') ?></td>
                        <td><?= htmlspecialchars($r['job_type']??'-') ?></td>
                        <td><?= $r['deadline']?date('d M Y',strtotime($r['deadline'])):'Open' ?></td>
                    </tr>
                    <?php endwhile; ?>
                </table></div>
            </div>

            <!-- All Applications -->
            <div id="m-applications" class="m-section" style="display:none">
                <div class="table-wrap"><table>
                    <tr><th>#</th><th>Student</th><th>Job</th><th>Company</th><th>Status</th><th>Date</th></tr>
                    <?php $i=1; while($r=$allApplications->fetch_assoc()): ?>
                    <tr class="clickable-row" onclick="window.location='applications.php?search=<?= urlencode($r['student_name']) ?>&status=<?= $r['status'] ?>'" title="View applications for <?= htmlspecialchars($r['student_name']) ?>">
                        <td><?= $i++ ?></td>
                        <td style="color:#3949ab;font-weight:600"><?= htmlspecialchars($r['student_name']) ?></td>
                        <td><?= htmlspecialchars($r['job_title']) ?></td>
                        <td><?= htmlspecialchars($r['company_name']) ?></td>
                        <td><span class="badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
                        <td><?= date('d M Y',strtotime($r['applied_at'])) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </table></div>
            </div>

            <!-- Placed Students -->
            <div id="m-placed" class="m-section" style="display:none">
                <div class="table-wrap"><table>
                    <tr><th>#</th><th>Name</th><th>Email</th><th>Department</th><th>CGPA</th><th>Package</th><th>Source</th></tr>
                    <?php $i=1; while($r=$placedStudents->fetch_assoc()): ?>
                    <tr class="clickable-row" onclick="window.location='students.php?open=<?= $r['student_id'] ?>'" title="View student details">
                        <td><?= $i++ ?></td>
                        <td style="color:#3949ab;font-weight:600"><?= htmlspecialchars($r['name']) ?></td>
                        <td><?= htmlspecialchars($r['email']) ?></td>
                        <td><?= htmlspecialchars($r['department'] ?? '-') ?></td>
                        <td><?= $r['cgpa'] ?? '-' ?></td>
                        <td><?= htmlspecialchars($r['pkg'] ?? '-') ?></td>
                        <td><span style="background:<?= $r['source']==='CSV'?'#e8eaf6':'#e8f5e9' ?>;color:<?= $r['source']==='CSV'?'#3f51b5':'#2e7d32' ?>;padding:2px 8px;border-radius:10px;font-size:0.75rem;font-weight:700"><?= $r['source']==='CSV'?'CSV Import':'Application' ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                </table></div>
            </div>

            <!-- Selected Students -->
            <div id="m-selected" class="m-section" style="display:none">
                <div class="table-wrap"><table>
                    <tr><th>#</th><th>Student</th><th>Email</th><th>Job</th><th>Company</th><th>Date</th></tr>
                    <?php $i=1; while($r=$selectedStudents->fetch_assoc()): ?>
                    <tr class="clickable-row" onclick="window.location='students.php?open=<?= $r['student_id'] ?>'" title="View student details">
                        <td><?= $i++ ?></td>
                        <td style="color:#3949ab;font-weight:600"><?= htmlspecialchars($r['student_name']) ?></td>
                        <td><?= htmlspecialchars($r['email']) ?></td>
                        <td><?= htmlspecialchars($r['job_title']) ?></td>
                        <td><?= htmlspecialchars($r['company_name']) ?></td>
                        <td><?= date('d M Y',strtotime($r['applied_at'])) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </table></div>
            </div>

            <!-- All Jobs -->
            <div id="m-all_jobs" class="m-section" style="display:none">
                <div class="table-wrap"><table>
                    <tr><th>#</th><th>Title</th><th>Company</th><th>Type</th><th>Status</th><th>Deadline</th></tr>
                    <?php $i=1; while($r=$allJobs->fetch_assoc()): ?>
                    <tr class="clickable-row" onclick="window.location='jobs.php?open=<?= $r['id'] ?>'" title="View job details">
                        <td><?= $i++ ?></td>
                        <td style="color:#3949ab;font-weight:600"><?= htmlspecialchars($r['title']) ?></td>
                        <td><?= htmlspecialchars($r['company_name']) ?></td>
                        <td><?= htmlspecialchars($r['job_type']??'-') ?></td>
                        <td><span class="badge badge-<?= $r['status']==='open'?'selected':'rejected' ?>"><?= ucfirst($r['status']) ?></span></td>
                        <td><?= $r['deadline']?date('d M Y',strtotime($r['deadline'])):'Open' ?></td>
                    </tr>
                    <?php endwhile; ?>
                </table></div>
            </div>

            <!-- All Interviews -->
            <div id="m-iv_total" class="m-section" style="display:none">
                <div class="table-wrap"><table>
                    <tr><th>#</th><th>Student</th><th>Job</th><th>Company</th><th>Scheduled</th><th>Status</th></tr>
                    <?php $i=1; while($r=$allInterviews->fetch_assoc()): ?>
                    <tr class="clickable-row" onclick="window.location='interviews/index.php?status=<?= $r['status'] ?>'" title="View interview details">
                        <td><?= $i++ ?></td>
                        <td style="color:#3949ab;font-weight:600"><?= htmlspecialchars($r['student_name']) ?></td>
                        <td><?= htmlspecialchars($r['job_title']) ?></td>
                        <td><?= htmlspecialchars($r['company_name']) ?></td>
                        <td><?= $r['scheduled_at']?date('d M Y, h:i A',strtotime($r['scheduled_at'])):'-' ?></td>
                        <td><span class="badge badge-<?= $r['status']==='completed'?'selected':'applied' ?>"><?= ucfirst($r['status']) ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                </table></div>
            </div>

            <!-- Completed Interviews -->
            <div id="m-iv_completed" class="m-section" style="display:none">
                <div class="table-wrap"><table>
                    <tr><th>#</th><th>Student</th><th>Job</th><th>Company</th><th>Date</th></tr>
                    <?php $i=1; while($r=$completedIV->fetch_assoc()): ?>
                    <tr class="clickable-row" onclick="window.location='interviews/index.php?status=completed'" title="View completed interviews">
                        <td><?= $i++ ?></td>
                        <td style="color:#3949ab;font-weight:600"><?= htmlspecialchars($r['student_name']) ?></td>
                        <td><?= htmlspecialchars($r['job_title']) ?></td>
                        <td><?= htmlspecialchars($r['company_name']) ?></td>
                        <td><?= $r['scheduled_at']?date('d M Y, h:i A',strtotime($r['scheduled_at'])):'-' ?></td>
                    </tr>
                    <?php endwhile; ?>
                </table></div>
            </div>

            <!-- Upcoming Interviews -->
            <div id="m-iv_scheduled" class="m-section" style="display:none">
                <div class="table-wrap"><table>
                    <tr><th>#</th><th>Student</th><th>Job</th><th>Company</th><th>Scheduled At</th></tr>
                    <?php $i=1; while($r=$scheduledIV->fetch_assoc()): ?>
                    <tr class="clickable-row" onclick="window.location='interviews/index.php?status=scheduled'" title="View scheduled interviews">
                        <td><?= $i++ ?></td>
                        <td style="color:#3949ab;font-weight:600"><?= htmlspecialchars($r['student_name']) ?></td>
                        <td><?= htmlspecialchars($r['job_title']) ?></td>
                        <td><?= htmlspecialchars($r['company_name']) ?></td>
                        <td><?= $r['scheduled_at']?date('d M Y, h:i A',strtotime($r['scheduled_at'])):'-' ?></td>
                    </tr>
                    <?php endwhile; ?>
                </table></div>
            </div>

        </div>
    </div>
</div>
<!-- Recruiter Detail Popup -->
<div class="modal-overlay" id="recruiterPopup" onclick="if(event.target===this)closeRecruiterPopup()">
    <div class="modal-box" style="max-width:520px">
        <div style="background:linear-gradient(135deg,#1a237e,#3949ab);padding:22px 24px;border-radius:14px 14px 0 0;display:flex;justify-content:space-between;align-items:flex-start">
            <div>
                <div id="rp-name" style="font-size:1.2rem;font-weight:800;color:#ffd54f"></div>
                <div id="rp-email" style="color:#c5cae9;font-size:0.85rem;margin-top:3px"></div>
            </div>
            <button onclick="closeRecruiterPopup()" style="background:rgba(255,255,255,0.15);border:none;color:#fff;width:32px;height:32px;border-radius:50%;cursor:pointer;font-size:1.1rem">&times;</button>
        </div>
        <div style="padding:20px 24px">
            <div style="background:#f8f9ff;border-radius:10px;padding:14px 16px;margin-bottom:14px">
                <div style="font-size:0.75rem;color:#999;font-weight:700;margin-bottom:8px">COMPANY INFO</div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:0.88rem">
                    <div><span style="color:#999">Company:</span> <strong id="rp-company"></strong></div>
                    <div><span style="color:#999">Industry:</span> <strong id="rp-industry"></strong></div>
                    <div><span style="color:#999">Jobs Posted:</span> <strong id="rp-jobs"></strong></div>
                    <div><span style="color:#999">Total Applications:</span> <strong id="rp-apps"></strong></div>
                </div>
                <div id="rp-website-wrap" style="margin-top:8px;font-size:0.88rem"><span style="color:#999">Website:</span> <a id="rp-website" href="#" target="_blank" style="color:#3949ab;font-weight:600"></a></div>
                <div id="rp-desc-wrap" style="margin-top:8px;font-size:0.85rem;color:#555"></div>
            </div>
            <div style="font-size:0.75rem;color:#aaa" id="rp-joined"></div>
            <div style="margin-top:14px">
                <a id="rp-view-link" href="#" style="display:inline-block;padding:7px 18px;background:#1a237e;color:#fff;border-radius:6px;font-size:0.85rem;font-weight:700;text-decoration:none">View in Recruiters Page →</a>
            </div>
        </div>
    </div>
</div>

</div><!-- app-layout -->

<script>
const _recruiters = <?= json_encode(array_map(function($r){
    return [
        'id'      => (int)$r['id'],
        'name'    => $r['name'],
        'email'   => $r['email'],
        'company' => $r['company_name'] ?? '',
        'industry'=> $r['industry'] ?? '',
        'website' => $r['website'] ?? '',
        'desc'    => $r['company_desc'] ?? '',
        'jobs'    => (int)$r['job_count'],
        'apps'    => (int)$r['app_count'],
        'joined'  => $r['created_at'],
    ];
}, $recruitersData), JSON_HEX_TAG) ?>;
const _ridx = {}; _recruiters.forEach(r => _ridx[r.id] = r);

function openRecruiterPopup(id) {
    const r = _ridx[id]; if (!r) return;
    document.getElementById('rp-name').textContent    = r.name;
    document.getElementById('rp-email').textContent   = r.email;
    document.getElementById('rp-company').textContent = r.company || '—';
    document.getElementById('rp-industry').textContent= r.industry || '—';
    document.getElementById('rp-jobs').textContent    = r.jobs;
    document.getElementById('rp-apps').textContent    = r.apps;
    document.getElementById('rp-joined').textContent  = 'Joined: ' + r.joined;
    const ww = document.getElementById('rp-website-wrap');
    if (r.website) {
        document.getElementById('rp-website').textContent = r.website;
        document.getElementById('rp-website').href = r.website;
        ww.style.display = 'block';
    } else { ww.style.display = 'none'; }
    const dw = document.getElementById('rp-desc-wrap');
    dw.innerHTML = r.desc ? '<strong>About:</strong> ' + r.desc : '';
    document.getElementById('rp-view-link').href = 'recruiters.php';
    document.getElementById('recruiterPopup').classList.add('open');
}
function closeRecruiterPopup() {
    document.getElementById('recruiterPopup').classList.remove('open');
}

const modalTitles = {
    students:     '👨🎓 All Registered Students',
    placed:       '🎓 Placed Students (from CSV)',
    recruiters:   '🏢 All Recruiters',
    open_jobs:    '💼 Open Jobs',
    applications: '📋 All Applications',
    selected:     '✅ Selected Students',
    all_jobs:     '📌 All Jobs Posted',
    iv_total:     '🎥 All Interviews',
    iv_completed: '✅ Interviews Attended',
    iv_scheduled: '📅 Upcoming Interviews',
};
function openModal(key) {
    document.querySelectorAll('.m-section').forEach(el => el.style.display = 'none');
    const sec = document.getElementById('m-' + key);
    if (sec) sec.style.display = 'block';
    document.getElementById('modal-title').textContent = modalTitles[key] || '';
    document.getElementById('dashModal').classList.add('open');
}
function closeModal() {
    document.getElementById('dashModal').classList.remove('open');
}
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('show');
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('show');
}
</script>
</body>
</html>
