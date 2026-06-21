<?php
require_once '../../includes/config.php';
requireLogin('admin');

$test_id = (int)($_GET['test_id'] ?? 0);
if (!$test_id) { header("Location: index.php"); exit(); }

$stTest = $conn->prepare("SELECT * FROM tests WHERE id=?");
$stTest->bind_param('i', $test_id); $stTest->execute();
$test = $stTest->get_result()->fetch_assoc(); $stTest->close();
if (!$test) { header("Location: index.php"); exit(); }

// Ensure round_eligible table
$conn->query("CREATE TABLE IF NOT EXISTS round_eligible (
    id INT AUTO_INCREMENT PRIMARY KEY,
    round_id INT NOT NULL,
    student_id INT NOT NULL,
    eligible TINYINT(1) DEFAULT 1,
    marked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_re (round_id, student_id),
    FOREIGN KEY (round_id) REFERENCES placement_rounds(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
)");

$round = $conn->query("SELECT * FROM placement_rounds WHERE test_id=$test_id LIMIT 1")->fetch_assoc();
$round_id = $round ? (int)$round['id'] : 0;

$msg = '';

// Mark top-N eligible
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_topn']) && $round_id) {
    $topN = max(1, (int)$_POST['top_n']);
    // Get top-N student IDs by score
    $topStudents = $conn->query("SELECT student_id FROM test_attempts WHERE test_id=$test_id AND status='completed' ORDER BY score DESC LIMIT $topN");
    $eligible_ids = [];
    while ($s = $topStudents->fetch_assoc()) $eligible_ids[] = (int)$s['student_id'];
    // Mark all attempted students, eligible only for top-N
    $all = $conn->query("SELECT DISTINCT student_id FROM test_attempts WHERE test_id=$test_id AND status='completed'");
    while ($s = $all->fetch_assoc()) {
        $sid = (int)$s['student_id'];
        $elig = in_array($sid, $eligible_ids) ? 1 : 0;
        $conn->query("INSERT INTO round_eligible (round_id,student_id,eligible) VALUES ($round_id,$sid,$elig) ON DUPLICATE KEY UPDATE eligible=$elig, marked_at=NOW()");
        // Notify student
        if ($elig) {
            $conn->query("INSERT INTO notifications (user_id,type,title,message,link) VALUES ($sid,'application','Round Result','You are eligible to proceed to the next round. Check your dashboard.','/placement/student/aptitude_test/index.php') ON DUPLICATE KEY UPDATE id=id");
        } else {
            $conn->query("INSERT INTO notifications (user_id,type,title,message,link) VALUES ($sid,'application','Round Result','You have not been shortlisted for the next round. Better luck next time.','/placement/student/aptitude_test/index.php') ON DUPLICATE KEY UPDATE id=id");
        }
    }
    $msg = '<div class="alert alert-success">✅ Top '.$topN.' students marked eligible. Others marked ineligible. Students notified.</div>';
}

// Manual mark individual student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_individual']) && $round_id) {
    $sid  = (int)$_POST['student_id'];
    $elig = (int)$_POST['eligible'];
    $conn->query("INSERT INTO round_eligible (round_id,student_id,eligible) VALUES ($round_id,$sid,$elig) ON DUPLICATE KEY UPDATE eligible=$elig, marked_at=NOW()");
    $notifMsg = $elig ? 'You are eligible to proceed to the next round.' : 'You have not been shortlisted for the next round.';
    $conn->query("INSERT INTO notifications (user_id,type,title,message,link) VALUES ($sid,'application','Round Result','$notifMsg','/placement/student/aptitude_test/index.php') ON DUPLICATE KEY UPDATE id=id");
    $msg = '<div class="alert alert-success">✅ Eligibility updated.</div>';
}

$stAtt = $conn->prepare("SELECT ta.*, u.name as student_name, u.email, sp.department, sp.cgpa,
    (SELECT eligible FROM round_eligible WHERE round_id=? AND student_id=ta.student_id LIMIT 1) as admin_eligible
    FROM test_attempts ta JOIN users u ON ta.student_id=u.id LEFT JOIN student_profiles sp ON sp.user_id=u.id
    WHERE ta.test_id=? AND ta.status='completed' ORDER BY ta.score DESC");
$stAtt->bind_param('ii', $round_id, $test_id); $stAtt->execute();
$attempts = $stAtt->get_result(); $stAtt->close();

$stSt = $conn->prepare("SELECT COUNT(*) as total, AVG(score) as avg_score, MAX(score) as max_score, MIN(score) as min_score FROM test_attempts WHERE test_id=? AND status='completed'");
$stSt->bind_param('i', $test_id); $stSt->execute();
$stats = $stSt->get_result()->fetch_assoc(); $stSt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Test Results - Admin</title>
<link rel="stylesheet" href="../../css/style.css">
</head>
<body>
<?php require_once '../sidebar.php'; ?>
<div class="topbar">
    <div class="topbar-left">
        <button class="hamburger" onclick="toggleSidebar()">☰</button>
        <span class="page-title">Test Results</span>
    </div>
    <div class="topbar-right"><?php require_once '../../notifications/widget.php'; ?></div>
</div>
<div class="main-content">
<?= $msg ?>
    <div class="card" style="background:linear-gradient(135deg,#4a148c,#7b1fa2);color:#fff;margin-bottom:20px">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
            <div>
                <h3 style="color:#ffd54f;margin-bottom:5px"><?= htmlspecialchars($test['title']) ?> — Results</h3>
                <div style="color:#ce93d8;font-size:0.9rem">⏱️ <?= $test['duration'] ?> min · 🏆 <?= $test['total_marks'] ?> marks · Pass: <?= $test['pass_marks'] ?></div>
            </div>
            <a href="index.php" class="btn" style="background:#ffd54f;color:#4a148c">← Back</a>
        </div>
    </div>

    <div class="stats-grid" style="grid-template-columns:repeat(4,1fr)">
        <div class="stat-card"><div class="number"><?= $stats['total'] ?></div><div class="label">Total Attempts</div></div>
        <div class="stat-card orange"><div class="number"><?= round($stats['avg_score'] ?? 0, 1) ?></div><div class="label">Average Score</div></div>
        <div class="stat-card green"><div class="number"><?= $stats['max_score'] ?? 0 ?></div><div class="label">Highest Score</div></div>
        <div class="stat-card red"><div class="number"><?= $stats['min_score'] ?? 0 ?></div><div class="label">Lowest Score</div></div>
    </div>

    <?php if ($round_id): ?>
    <div class="card" style="margin-bottom:18px;border-left:4px solid #3f51b5">
        <h2 style="font-size:1rem;margin-bottom:14px">🎯 Shortlist for Next Round</h2>
        <div style="display:flex;gap:20px;flex-wrap:wrap;align-items:flex-end">
            <form method="POST" style="display:flex;gap:10px;align-items:flex-end">
                <input type="hidden" name="mark_topn" value="1">
                <div>
                    <label style="font-size:0.85rem;font-weight:600;display:block;margin-bottom:4px">Shortlist Top-N Students</label>
                    <input type="number" name="top_n" value="10" min="1" max="<?= $stats['total'] ?>" style="width:80px;padding:7px;border-radius:6px;border:1px solid #ddd">
                </div>
                <button type="submit" class="btn btn-primary" onclick="return confirm('This will update eligibility for all students and send notifications. Continue?')">✅ Apply &amp; Notify</button>
            </form>
            <div style="font-size:0.82rem;color:#666;max-width:320px">Top-N students (by score) will be marked <strong>eligible</strong> for the next round. All others will be marked <strong>ineligible</strong>. Students are notified automatically.</div>
        </div>
    </div>
    <?php endif; ?>

    <div class="card">
        <h2>Student Results <?php if (!$round_id): ?><small style="color:#e65100;font-size:0.8rem">(not linked to a round — eligibility management unavailable)</small><?php endif; ?></h2>
        <?php if ($attempts->num_rows === 0): ?>
        <p style="color:#999;text-align:center;padding:20px">No attempts yet.</p>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <tr><th>#</th><th>Student</th><th>Dept</th><th>CGPA</th><th>Score</th><th>Correct</th><th>Wrong</th><th>%</th><th>Pass/Fail</th><th>Next Round</th><th>Completed</th></tr>
                <?php $rank=1; while($a = $attempts->fetch_assoc()):
                    $pct = $test['total_marks'] > 0 ? round(($a['score']/$test['total_marks'])*100) : 0;
                    $passed = $a['score'] >= $test['pass_marks'];
                    $adminElig = $a['admin_eligible']; // null=not set, 1=eligible, 0=ineligible
                ?>
                <tr>
                    <td><?= $rank++ ?></td>
                    <td><strong><?= htmlspecialchars($a['student_name']) ?></strong><br><small style="color:#999"><?= htmlspecialchars($a['email']) ?></small></td>
                    <td><?= htmlspecialchars($a['department'] ?? 'N/A') ?></td>
                    <td><?= $a['cgpa'] ?? 'N/A' ?></td>
                    <td><strong><?= $a['score'] ?>/<?= $test['total_marks'] ?></strong></td>
                    <td style="color:#2e7d32">✅ <?= $a['correct_answers'] ?></td>
                    <td style="color:#c62828">❌ <?= $a['wrong_answers'] ?></td>
                    <td><?= $pct ?>%</td>
                    <td><span class="badge <?= $passed?'badge-selected':'badge-rejected' ?>"><?= $passed?'Passed':'Failed' ?></span></td>
                    <td>
                    <?php if ($round_id): ?>
                        <?php if ($adminElig === null): ?>
                        <span style="color:#999;font-size:0.82rem">Pending</span>
                        <?php elseif ($adminElig == 1): ?>
                        <span style="color:#2e7d32;font-weight:700">✅ Eligible</span>
                        <?php else: ?>
                        <span style="color:#c62828;font-weight:700">❌ Not Shortlisted</span>
                        <?php endif; ?>
                        <form method="POST" style="display:inline;margin-left:6px">
                            <input type="hidden" name="mark_individual" value="1">
                            <input type="hidden" name="student_id" value="<?= $a['student_id'] ?>">
                            <select name="eligible" onchange="this.form.submit()" style="font-size:0.78rem;padding:2px 4px;border-radius:4px;border:1px solid #ddd">
                                <option value="">Change...</option>
                                <option value="1">✅ Eligible</option>
                                <option value="0">❌ Ineligible</option>
                            </select>
                        </form>
                    <?php else: ?>
                        <span style="color:#999;font-size:0.82rem">N/A</span>
                    <?php endif; ?>
                    </td>
                    <td><?= $a['completed_at'] ? date('d M Y, h:i A', strtotime($a['completed_at'])) : 'N/A' ?></td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
</div><!-- app-layout -->
<?php require_once '../../chatbot/widget.php'; ?>
<script>
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('show');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('sidebarOverlay').classList.remove('show');}
</script>
</body>
</html>

