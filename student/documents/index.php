<?php
require_once '../../includes/config.php';
requireLogin('student');
require_once '../../includes/notify.php';

$uid = $_SESSION['user_id'];

// Create table
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

$msg = '';

// Handle upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_doc'])) {
    $docType = sanitize($_POST['doc_type']);
    $docName = sanitize($_POST['doc_name']);

    if (!in_array($docType, ['certificate','marksheet','id_proof','offer_letter','other'])) {
        $msg = '<div class="alert alert-error">Invalid document type.</div>';
    } elseif (empty($_FILES['doc_file']['name'])) {
        $msg = '<div class="alert alert-error">Please select a file to upload.</div>';
    } else {
        $file     = $_FILES['doc_file'];
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed  = ['pdf','jpg','jpeg','png','doc','docx'];
        $maxSize  = 5 * 1024 * 1024; // 5MB

        if (!in_array($ext, $allowed)) {
            $msg = '<div class="alert alert-error">Only PDF, JPG, PNG, DOC, DOCX files allowed.</div>';
        } elseif ($file['size'] > $maxSize) {
            $msg = '<div class="alert alert-error">File size must be under 5MB.</div>';
        } else {
            $filename = 'doc_' . $uid . '_' . time() . '_' . uniqid() . '.' . $ext;
            $uploadPath = '../../uploads/documents/' . $filename;

            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                $safeDocName = $conn->real_escape_string($docName ?: $file['name']);
                $fileSize    = (int)$file['size'];
                $conn->query("INSERT INTO documents (user_id, doc_type, doc_name, file_path, file_size)
                    VALUES ($uid, '$docType', '$safeDocName', '$filename', $fileSize)");

                // Notify admin
                $admins = $conn->query("SELECT id FROM users WHERE role='admin'");
                while ($a = $admins->fetch_assoc()) {
                    createNotification($conn, $a['id'], 'system',
                        '📄 New Document Uploaded',
                        $_SESSION['name'] . " uploaded a " . ucfirst(str_replace('_',' ',$docType)) . " for verification.",
                        '/placement system/admin/documents/index.php'
                    );
                }
                $msg = '<div class="alert alert-success">✅ Document uploaded successfully! Awaiting admin verification.</div>';
            } else {
                $msg = '<div class="alert alert-error">Upload failed. Please try again.</div>';
            }
        }
    }
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_doc'])) {
    $did = (int)$_POST['doc_id'];
    $doc = $conn->query("SELECT * FROM documents WHERE id=$did AND user_id=$uid AND status='pending'")->fetch_assoc();
    if ($doc) {
        @unlink('../../uploads/documents/' . $doc['file_path']);
        $conn->query("DELETE FROM documents WHERE id=$did");
        $msg = '<div class="alert alert-success">Document deleted.</div>';
    }
}

$documents = $conn->query("SELECT * FROM documents WHERE user_id=$uid ORDER BY uploaded_at DESC");

$stats = [
    'total'    => $conn->query("SELECT COUNT(*) as c FROM documents WHERE user_id=$uid")->fetch_assoc()['c'],
    'pending'  => $conn->query("SELECT COUNT(*) as c FROM documents WHERE user_id=$uid AND status='pending'")->fetch_assoc()['c'],
    'approved' => $conn->query("SELECT COUNT(*) as c FROM documents WHERE user_id=$uid AND status='approved'")->fetch_assoc()['c'],
    'rejected' => $conn->query("SELECT COUNT(*) as c FROM documents WHERE user_id=$uid AND status='rejected'")->fetch_assoc()['c'],
];

$typeIcons = ['certificate'=>'🏆','marksheet'=>'📊','id_proof'=>'🪪','offer_letter'=>'📝','other'=>'📄'];
$typeLabels = ['certificate'=>'Certificate','marksheet'=>'Marksheet','id_proof'=>'ID Proof','offer_letter'=>'Offer Letter','other'=>'Other'];
$statusColors = ['pending'=>['#e65100','#fff8e1'],'approved'=>['#2e7d32','#e8f5e9'],'rejected'=>['#c62828','#ffebee']];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Documents</title>
<link rel="stylesheet" href="../../css/style.css">
<style>
.doc-card { background:#fff;border-radius:12px;padding:18px 20px;box-shadow:0 2px 10px rgba(0,0,0,0.07);margin-bottom:14px;border-left:5px solid #e0e0e0;transition:all 0.2s; }
.doc-card:hover { box-shadow:0 4px 15px rgba(0,0,0,0.1); }
.doc-card.pending  { border-left-color:#fb8c00; }
.doc-card.approved { border-left-color:#43a047; }
.doc-card.rejected { border-left-color:#e53935; }
.status-badge { display:inline-block;padding:4px 12px;border-radius:20px;font-size:0.82rem;font-weight:700; }
.upload-zone { border:2px dashed #c5cae9;border-radius:12px;padding:30px;text-align:center;background:#f8f9ff;transition:all 0.2s;cursor:pointer; }
.upload-zone:hover { border-color:#3f51b5;background:#e8eaf6; }
.type-grid { display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;margin-bottom:20px; }
.type-card { background:#fff;border-radius:10px;padding:15px;text-align:center;border:2px solid #e0e0e0;cursor:pointer;transition:all 0.2s; }
.type-card:hover,.type-card.selected { border-color:#3f51b5;background:#e8eaf6; }
</style>
</head>
<body>
<nav class="navbar">
    <a href="../dashboard.php" class="brand">🎓 Campus<span>Recruit</span></a>
    <div class="nav-links">
        <a href="../dashboard.php">Dashboard</a>
        <a href="../jobs.php">Browse Jobs</a>
        <a href="../profile.php">My Profile</a>
        <a href="../coding/index.php">💻 Coding</a>
        <a href="index.php" class="active">📄 Documents</a>
        <a href="../notices.php">Notices</a>
        <?php require_once '../../notifications/widget.php'; ?>
        <a href="../logout.php" class="btn-logout">Logout</a>
    </div>
</nav>

<div class="container">
    <?= $msg ?>

    <!-- Header -->
    <div class="card" style="background:linear-gradient(135deg,#4a148c,#6a1b9a);color:#fff;margin-bottom:25px">
        <h2 style="color:#ffd54f;margin-bottom:8px">📄 Document Verification</h2>
        <p style="color:#ce93d8">Upload your certificates, marksheets, and ID proofs for admin verification.</p>
    </div>

    <!-- Stats -->
    <div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:25px">
        <div class="stat-card"><div class="number"><?= $stats['total'] ?></div><div class="label">📄 Total Uploaded</div></div>
        <div class="stat-card orange"><div class="number"><?= $stats['pending'] ?></div><div class="label">⏳ Pending Review</div></div>
        <div class="stat-card green"><div class="number"><?= $stats['approved'] ?></div><div class="label">✅ Approved</div></div>
        <div class="stat-card red"><div class="number"><?= $stats['rejected'] ?></div><div class="label">❌ Rejected</div></div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1.5fr;gap:20px">

        <!-- Upload Form -->
        <div class="card">
            <h2>📤 Upload Document</h2>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="upload_doc" value="1">

                <div class="form-group">
                    <label>Document Type *</label>
                    <select name="doc_type" required>
                        <option value="">-- Select Type --</option>
                        <option value="certificate">🏆 Certificate</option>
                        <option value="marksheet">📊 Marksheet / Transcript</option>
                        <option value="id_proof">🪪 ID Proof (Aadhar/PAN/Passport)</option>
                        <option value="offer_letter">📝 Offer Letter</option>
                        <option value="other">📄 Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Document Name *</label>
                    <input type="text" name="doc_name" placeholder="e.g. Python Certificate - Coursera" required>
                </div>

                <div class="form-group">
                    <label>Upload File *</label>
                    <div class="upload-zone" onclick="document.getElementById('doc-file').click()">
                        <div style="font-size:2.5rem;margin-bottom:8px">📁</div>
                        <div style="font-weight:600;color:#3f51b5">Click to select file</div>
                        <div style="font-size:0.82rem;color:#999;margin-top:5px">PDF, JPG, PNG, DOC, DOCX · Max 5MB</div>
                        <div id="file-name" style="margin-top:8px;font-size:0.85rem;color:#2e7d32;font-weight:600"></div>
                    </div>
                    <input type="file" id="doc-file" name="doc_file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" style="display:none" onchange="showFileName(this)">
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;background:linear-gradient(135deg,#4a148c,#6a1b9a)">
                    📤 Upload Document
                </button>
            </form>

            <!-- Guidelines -->
            <div style="margin-top:20px;padding:14px;background:#f3e5f5;border-radius:8px;border-left:4px solid #9c27b0">
                <div style="font-weight:700;color:#4a148c;margin-bottom:8px">📋 Upload Guidelines</div>
                <ul style="padding-left:18px;color:#555;font-size:0.85rem;line-height:2">
                    <li>File size must be under <strong>5MB</strong></li>
                    <li>Accepted formats: PDF, JPG, PNG, DOC, DOCX</li>
                    <li>Ensure documents are clear and readable</li>
                    <li>Admin will verify within 2-3 working days</li>
                    <li>You can delete pending documents before review</li>
                </ul>
            </div>
        </div>

        <!-- Documents List -->
        <div>
            <div class="card" style="padding:15px;margin-bottom:0">
                <h2>My Documents</h2>
                <?php if ($documents->num_rows === 0): ?>
                <div style="text-align:center;padding:40px;color:#999">
                    <div style="font-size:3rem;margin-bottom:10px">📂</div>
                    <p>No documents uploaded yet.</p>
                    <p style="font-size:0.85rem;margin-top:5px">Upload your certificates and marksheets to get verified.</p>
                </div>
                <?php else: ?>
                <?php while($d = $documents->fetch_assoc()):
                    $sc = $statusColors[$d['status']];
                    $fileExt = strtolower(pathinfo($d['file_path'], PATHINFO_EXTENSION));
                    $isImage = in_array($fileExt, ['jpg','jpeg','png']);
                ?>
                <div class="doc-card <?= $d['status'] ?>">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px">
                        <div style="flex:1">
                            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:6px">
                                <span style="font-size:1.4rem"><?= $typeIcons[$d['doc_type']] ?></span>
                                <strong style="color:#1a237e;font-size:0.95rem"><?= htmlspecialchars($d['doc_name']) ?></strong>
                                <span class="status-badge" style="background:<?= $sc[1] ?>;color:<?= $sc[0] ?>">
                                    <?= $d['status']==='pending'?'⏳':($d['status']==='approved'?'✅':'❌') ?> <?= ucfirst($d['status']) ?>
                                </span>
                            </div>
                            <div style="font-size:0.82rem;color:#666;margin-bottom:6px">
                                <span style="background:#f3e5f5;color:#6a1b9a;padding:2px 8px;border-radius:10px;font-size:0.75rem;font-weight:700;margin-right:8px"><?= $typeLabels[$d['doc_type']] ?></span>
                                📅 <?= date('d M Y', strtotime($d['uploaded_at'])) ?>
                                · 📦 <?= round($d['file_size']/1024, 1) ?> KB
                            </div>
                            <?php if ($d['admin_remarks']): ?>
                            <div style="background:<?= $sc[1] ?>;border-radius:6px;padding:8px 12px;font-size:0.85rem;color:<?= $sc[0] ?>;margin-top:6px">
                                💬 <strong>Admin:</strong> <?= htmlspecialchars($d['admin_remarks']) ?>
                            </div>
                            <?php endif; ?>
                            <?php if ($d['reviewed_at']): ?>
                            <div style="font-size:0.78rem;color:#999;margin-top:4px">Reviewed: <?= date('d M Y', strtotime($d['reviewed_at'])) ?></div>
                            <?php endif; ?>
                        </div>
                        <div style="display:flex;flex-direction:column;gap:6px">
                            <a href="../../uploads/documents/<?= htmlspecialchars($d['file_path']) ?>" target="_blank" class="btn btn-primary btn-sm">
                                <?= $isImage ? '🖼️ View' : '📄 View' ?>
                            </a>
                            <?php if ($d['status'] === 'pending'): ?>
                            <form method="POST" onsubmit="return confirm('Delete this document?')">
                                <input type="hidden" name="doc_id" value="<?= $d['id'] ?>">
                                <button name="delete_doc" class="btn btn-danger btn-sm" style="width:100%">🗑️ Delete</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../chatbot/widget.php'; ?>

<script>
function showFileName(input) {
    const name = input.files[0]?.name || '';
    document.getElementById('file-name').textContent = name ? '✅ ' + name : '';
}
</script>
</body>
</html>
