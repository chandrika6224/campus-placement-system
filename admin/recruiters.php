<?php
require_once '../includes/config.php';
requireLogin('admin');

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        $id = (int)$_POST['delete'];
        $st = $conn->prepare("DELETE FROM users WHERE id=? AND role='recruiter'");
        $st->bind_param('i', $id); $st->execute(); $st->close();
        $msg = '<div class="alert alert-success">Recruiter deleted.</div>';
    }
}

$stRec = $conn->prepare("SELECT u.id, u.name, u.email, u.created_at, c.company_name, c.industry, c.website, c.description as company_desc,
    (SELECT COUNT(*) FROM jobs WHERE company_id=c.id) as job_count,
    (SELECT COUNT(*) FROM jobs WHERE company_id=c.id AND status='open') as open_jobs,
    (SELECT COUNT(*) FROM applications a JOIN jobs j ON a.job_id=j.id WHERE j.company_id=c.id) as total_apps,
    (SELECT COUNT(*) FROM applications a JOIN jobs j ON a.job_id=j.id WHERE j.company_id=c.id AND a.status='selected') as hired
    FROM users u LEFT JOIN companies c ON u.id=c.user_id WHERE u.role='recruiter' ORDER BY u.created_at DESC");
$stRec->execute(); $recruiters = $stRec->get_result(); $stRec->close();
$recruitersData = [];
while ($r = $recruiters->fetch_assoc()) $recruitersData[] = $r;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Recruiters - Admin</title>
<link rel="stylesheet" href="../css/style.css">
<style>
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center}
.modal-overlay.open{display:flex}
.modal-box{background:#fff;border-radius:14px;width:560px;max-width:95vw;max-height:90vh;overflow-y:auto;box-shadow:0 8px 40px rgba(0,0,0,0.2)}
.r-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px}
.r-item{background:#f5f6fa;border-radius:8px;padding:10px 14px}
.r-item .lbl{font-size:0.7rem;color:#888;font-weight:600;text-transform:uppercase}
.r-item .val{font-size:0.95rem;font-weight:700;color:#1a237e;margin-top:2px}
.clickable-row{cursor:pointer}
.clickable-row:hover{background:#f0f4ff}
</style>
</head>
<body>
<?php require_once 'sidebar.php'; ?>
<div class="topbar">
    <div class="topbar-left">
        <button class="hamburger" onclick="toggleSidebar()">☰</button>
        <span class="page-title">Recruiters</span>
    </div>
    <div class="topbar-right"><?php require_once '../notifications/widget.php'; ?></div>
</div>
<div class="main-content">
    <?= $msg ?>
    <div class="card">
        <h2>All Recruiters (<?= count($recruitersData) ?>)</h2>
        <div class="table-wrap">
            <table>
                <tr><th>Name</th><th>Email</th><th>Company</th><th>Industry</th><th>Jobs Posted</th><th>Hired</th><th>Action</th></tr>
                <?php foreach($recruitersData as $r): ?>
                <tr class="clickable-row" onclick="openPopup(<?= $r['id'] ?>)" title="View recruiter details">
                    <td style="color:#3949ab;font-weight:600"><?= htmlspecialchars($r['name']) ?></td>
                    <td><?= htmlspecialchars($r['email']) ?></td>
                    <td><?= htmlspecialchars($r['company_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($r['industry'] ?? '-') ?></td>
                    <td><?= $r['job_count'] ?></td>
                    <td><span style="color:#2e7d32;font-weight:700"><?= $r['hired'] ?></span></td>
                    <td onclick="event.stopPropagation()">
                        <form method="POST" onsubmit="return confirm('Delete this recruiter?')">
                            <input type="hidden" name="delete" value="<?= $r['id'] ?>">
                            <button class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</div>

<!-- Recruiter Detail Popup -->
<div class="modal-overlay" id="recruiterModal" onclick="if(event.target===this)closePopup()">
    <div class="modal-box">
        <div style="background:linear-gradient(135deg,#1a237e,#3949ab);padding:22px 24px;border-radius:14px 14px 0 0;display:flex;justify-content:space-between;align-items:flex-start">
            <div>
                <div id="rp-name" style="font-size:1.2rem;font-weight:800;color:#ffd54f"></div>
                <div id="rp-email" style="color:#c5cae9;font-size:0.85rem;margin-top:3px"></div>
            </div>
            <button onclick="closePopup()" style="background:rgba(255,255,255,0.15);border:none;color:#fff;width:32px;height:32px;border-radius:50%;cursor:pointer;font-size:1.1rem">&times;</button>
        </div>
        <div style="padding:20px 24px">
            <div style="font-size:0.75rem;color:#999;font-weight:700;margin-bottom:10px">COMPANY INFO</div>
            <div class="r-grid">
                <div class="r-item"><div class="lbl">Company</div><div class="val" id="rp-company"></div></div>
                <div class="r-item"><div class="lbl">Industry</div><div class="val" id="rp-industry"></div></div>
                <div class="r-item"><div class="lbl">Jobs Posted</div><div class="val" id="rp-jobs"></div></div>
                <div class="r-item"><div class="lbl">Open Jobs</div><div class="val" id="rp-open"></div></div>
                <div class="r-item"><div class="lbl">Total Applications</div><div class="val" id="rp-apps"></div></div>
                <div class="r-item"><div class="lbl">Students Hired</div><div class="val" id="rp-hired" style="color:#2e7d32"></div></div>
            </div>
            <div id="rp-website-wrap" style="margin-bottom:10px;font-size:0.88rem">
                <span style="color:#999">Website:</span> <a id="rp-website" href="#" target="_blank" style="color:#3949ab;font-weight:600"></a>
            </div>
            <div id="rp-desc" style="font-size:0.85rem;color:#555;margin-bottom:14px"></div>
            <div style="font-size:0.75rem;color:#aaa" id="rp-joined"></div>
            <div style="margin-top:14px">
                <a id="rp-jobs-link" href="jobs.php" style="display:inline-block;padding:7px 16px;background:#1a237e;color:#fff;border-radius:6px;font-size:0.85rem;font-weight:700;text-decoration:none;margin-right:8px">View Jobs →</a>
                <a id="rp-apps-link" href="applications.php" style="display:inline-block;padding:7px 16px;background:#e8eaf6;color:#1a237e;border-radius:6px;font-size:0.85rem;font-weight:700;text-decoration:none">View Applications →</a>
            </div>
        </div>
    </div>
</div>

</div><!-- app-layout -->
<script>
const _rdata = <?= json_encode(array_map(function($r) {
    return [
        'id'       => (int)$r['id'],
        'name'     => $r['name'],
        'email'    => $r['email'],
        'company'  => $r['company_name'] ?? '',
        'industry' => $r['industry'] ?? '',
        'website'  => $r['website'] ?? '',
        'desc'     => $r['company_desc'] ?? '',
        'jobs'     => (int)$r['job_count'],
        'open'     => (int)$r['open_jobs'],
        'apps'     => (int)$r['total_apps'],
        'hired'    => (int)$r['hired'],
        'joined'   => $r['created_at'],
    ];
}, $recruitersData), JSON_HEX_TAG) ?>;
const _ridx = {}; _rdata.forEach(r => _ridx[r.id] = r);

function openPopup(id) {
    const r = _ridx[id]; if (!r) return;
    document.getElementById('rp-name').textContent    = r.name;
    document.getElementById('rp-email').textContent   = r.email;
    document.getElementById('rp-company').textContent = r.company || '—';
    document.getElementById('rp-industry').textContent= r.industry || '—';
    document.getElementById('rp-jobs').textContent    = r.jobs;
    document.getElementById('rp-open').textContent    = r.open;
    document.getElementById('rp-apps').textContent    = r.apps;
    document.getElementById('rp-hired').textContent   = r.hired;
    document.getElementById('rp-joined').textContent  = 'Joined: ' + r.joined;
    const ww = document.getElementById('rp-website-wrap');
    if (r.website) {
        document.getElementById('rp-website').textContent = r.website;
        document.getElementById('rp-website').href = r.website;
        ww.style.display = 'block';
    } else { ww.style.display = 'none'; }
    document.getElementById('rp-desc').innerHTML = r.desc ? '<strong>About:</strong> ' + r.desc : '';
    document.getElementById('rp-jobs-link').href = 'jobs.php?company=' + encodeURIComponent(r.company);
    document.getElementById('rp-apps-link').href = 'applications.php?search=' + encodeURIComponent(r.company);
    document.getElementById('recruiterModal').classList.add('open');
}
function closePopup() {
    document.getElementById('recruiterModal').classList.remove('open');
}
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('show');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('sidebarOverlay').classList.remove('show');}
</script>
</body>
</html>
