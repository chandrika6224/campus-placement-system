<?php
require_once '../../includes/config.php';
requireLogin('admin');

$msg = '';

// Approve / Reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review'])) {
    $did     = (int)$_POST['doc_id'];
    $status  = $_POST['status'];
    $remarks = $conn->real_escape_string(trim($_POST['remarks'] ?? ''));
    if (in_array($status, ['approved','rejected'])) {
        $conn->query("UPDATE documents SET status='$status', admin_remarks='$remarks', reviewed_at=NOW() WHERE id=$did");
        // Notify student
        $doc = $conn->query("SELECT d.*, u.name FROM documents d JOIN users u ON d.user_id=u.id WHERE d.id=$did")->fetch_assoc();
        if ($doc) {
            $notif = $conn->real_escape_string("Your document '{$doc['doc_name']}' has been " . ($status==='approved'?'✅ approved':'❌ rejected') . ($remarks ? ". Admin note: $remarks" : '.'));
            $conn->query("INSERT INTO notifications (user_id, type, title, message, link) VALUES ({$doc['user_id']}, 'system', 'Document Review Update', '$notif', '/placement/student/profile.php')");
        }
        $msg = '<div class="alert alert-success">✅ Document ' . ucfirst($status) . '.</div>';
    }
}

$filter = $_GET['filter'] ?? 'pending';
$allowed = ['pending','approved','rejected','all'];
if (!in_array($filter, $allowed)) $filter = 'pending';
$where = $filter !== 'all' ? "WHERE d.status='$filter'" : '';

$docs = $conn->query("SELECT d.*, u.name as student_name, u.email FROM documents d JOIN users u ON d.user_id=u.id $where ORDER BY d.uploaded_at DESC");
$counts = $conn->query("SELECT status, COUNT(*) as c FROM documents GROUP BY status")->fetch_all(MYSQLI_ASSOC);
$cnt = ['pending'=>0,'approved'=>0,'rejected'=>0,'all'=>0];
foreach ($counts as $c) { $cnt[$c['status']] = $c['c']; $cnt['all'] += $c['c']; }

$typeIcons = ['certificate'=>'🏆','marksheet'=>'📊','id_proof'=>'🪪','offer_letter'=>'📝','other'=>'📄'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Documents - Admin</title>
<link rel="stylesheet" href="../../css/style.css">
</head>
<body>
<?php require_once '../sidebar.php'; ?>
<div class="topbar">
    <div class="topbar-left">
        <button class="hamburger" onclick="toggleSidebar()">☰</button>
        <span class="page-title">📄 Document Verification</span>
    </div>
    <div class="topbar-right"><?php require_once '../../notifications/widget.php'; ?></div>
</div>
<div class="main-content">
    <?= $msg ?>

    <!-- Filter tabs -->
    <div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap">
        <?php foreach (['pending'=>'⏳ Pending','approved'=>'✅ Approved','rejected'=>'❌ Rejected','all'=>'📂 All'] as $k=>$label): ?>
        <a href="?filter=<?= $k ?>" style="padding:8px 18px;border-radius:20px;text-decoration:none;font-weight:700;font-size:0.85rem;
            background:<?= $filter===$k?'#3f51b5':'#f0f2f5' ?>;color:<?= $filter===$k?'#fff':'#555' ?>">
            <?= $label ?> (<?= $cnt[$k] ?>)
        </a>
        <?php endforeach; ?>
    </div>

    <div class="card">
        <h2>Documents — <?= ucfirst($filter) ?> (<?= $docs->num_rows ?>)</h2>
        <?php if ($docs->num_rows === 0): ?>
        <div style="text-align:center;padding:40px;color:#999">
            <div style="font-size:3rem;margin-bottom:10px">📂</div>
            <p>No <?= $filter ?> documents.</p>
        </div>
        <?php else: while ($d = $docs->fetch_assoc()):
            $isImg = in_array(strtolower(pathinfo($d['file_path'],PATHINFO_EXTENSION)),['jpg','jpeg','png']);
            $borderColor = $d['status']==='approved'?'#43a047':($d['status']==='rejected'?'#e53935':'#fb8c00');
        ?>
        <div style="background:#fff;border-radius:10px;padding:16px;margin-bottom:12px;border-left:5px solid <?= $borderColor ?>;box-shadow:0 2px 8px rgba(0,0,0,0.06)">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px">
                <div style="flex:1">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:6px">
                        <span style="font-size:1.3rem"><?= $typeIcons[$d['doc_type']] ?></span>
                        <strong style="color:#1a237e"><?= htmlspecialchars($d['doc_name']) ?></strong>
                        <span style="background:<?= $d['status']==='approved'?'#e8f5e9':($d['status']==='rejected'?'#ffebee':'#fff8e1') ?>;
                              color:<?= $d['status']==='approved'?'#2e7d32':($d['status']==='rejected'?'#c62828':'#e65100') ?>;
                              padding:2px 10px;border-radius:12px;font-size:0.75rem;font-weight:700">
                            <?= $d['status']==='pending'?'⏳':($d['status']==='approved'?'✅':'❌') ?> <?= ucfirst($d['status']) ?>
                        </span>
                    </div>
                    <div style="font-size:0.83rem;color:#555;margin-bottom:6px">
                        👨‍🎓 <strong><?= htmlspecialchars($d['student_name']) ?></strong>
                        · <?= htmlspecialchars($d['email']) ?>
                        · <?= ucfirst(str_replace('_',' ',$d['doc_type'])) ?>
                        · <?= date('d M Y, h:i A', strtotime($d['uploaded_at'])) ?>
                        · <?= round($d['file_size']/1024,1) ?> KB
                    </div>
                    <?php if ($d['admin_remarks']): ?>
                    <div style="font-size:0.82rem;color:#555;background:#f5f5f5;padding:6px 10px;border-radius:6px">
                        💬 <?= htmlspecialchars($d['admin_remarks']) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div style="display:flex;flex-direction:column;gap:6px;min-width:200px">
                    <a href="../../uploads/documents/<?= htmlspecialchars($d['file_path']) ?>" target="_blank"
                       class="btn btn-primary btn-sm" style="text-align:center">
                        <?= $isImg?'🖼️ View Image':'📄 View File' ?>
                    </a>
                    <?php if ($d['status'] === 'pending'): ?>
                    <form method="POST">
                        <input type="hidden" name="review" value="1">
                        <input type="hidden" name="doc_id" value="<?= $d['id'] ?>">
                        <textarea name="remarks" rows="2" placeholder="Optional remarks..." style="width:100%;padding:5px 8px;border:1px solid #ddd;border-radius:6px;font-size:0.82rem;resize:none;margin-bottom:6px"></textarea>
                        <div style="display:flex;gap:6px">
                            <button name="status" value="approved" class="btn btn-success btn-sm" style="flex:1">✅ Approve</button>
                            <button name="status" value="rejected" class="btn btn-danger btn-sm" style="flex:1" onclick="return confirm('Reject this document?')">❌ Reject</button>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endwhile; endif; ?>
    </div>
</div>
</div>
<script>
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('show');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('sidebarOverlay').classList.remove('show');}
</script>
</body>
</html>

