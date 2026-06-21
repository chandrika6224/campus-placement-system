<?php
require_once '../../includes/config.php';
requireLogin('student');

$uid = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if ($uid === 0) { header('Location: ../../index.php'); exit(); }

// Ensure tables exist
$conn->query("CREATE TABLE IF NOT EXISTS tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    category ENUM('aptitude','technical','coding') DEFAULT 'aptitude',
    duration INT DEFAULT 30,
    total_marks INT DEFAULT 0,
    pass_marks INT DEFAULT 0,
    status ENUM('active','inactive') DEFAULT 'active',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
)");
$conn->query("CREATE TABLE IF NOT EXISTS test_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_id INT NOT NULL,
    student_id INT NOT NULL,
    score INT DEFAULT 0,
    total_marks INT DEFAULT 0,
    correct_answers INT DEFAULT 0,
    wrong_answers INT DEFAULT 0,
    status ENUM('started','completed') DEFAULT 'started',
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
)");

$stTests = $conn->prepare("SELECT t.*,
    (SELECT COUNT(*) FROM test_questions WHERE test_id=t.id) as q_count,
    (SELECT id FROM test_attempts WHERE test_id=t.id AND student_id=? AND status='completed' LIMIT 1) as attempt_id,
    (SELECT score FROM test_attempts WHERE test_id=t.id AND student_id=? AND status='completed' LIMIT 1) as my_score,
    pr.id as round_id, pr.scheduled_at as round_start, pr.end_time as round_end, pr.round_name, pr.job_id, pr.min_pass_score,
    CASE WHEN pr.scheduled_at IS NOT NULL AND pr.end_time IS NOT NULL
         THEN GREATEST(0, TIMESTAMPDIFF(MINUTE, pr.scheduled_at, pr.end_time))
         ELSE t.duration END as display_duration
    FROM tests t
    LEFT JOIN placement_rounds pr ON pr.test_id = t.id
    WHERE t.status='active' ORDER BY t.created_at DESC");
$stTests->bind_param('ii',$uid,$uid); $stTests->execute();
$tests = $stTests->get_result(); $stTests->close();

$catIcons = ['aptitude'=>'📊','technical'=>'💻','coding'=>'🖥️'];
$catColors = ['aptitude'=>'#1565c0','technical'=>'#2e7d32','coding'=>'#6a1b9a'];
$catBg = ['aptitude'=>'#e3f2fd','technical'=>'#e8f5e9','coding'=>'#f3e5f5'];
$now = time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Aptitude Tests</title>
<link rel="stylesheet" href="../../css/style.css">
<style>
.test-card { background:#fff;border-radius:12px;padding:22px;box-shadow:0 2px 12px rgba(0,0,0,0.08);margin-bottom:20px;border-left:5px solid #3f51b5;transition:transform 0.2s,box-shadow 0.2s; }
.test-card:hover { transform:translateY(-3px);box-shadow:0 6px 20px rgba(0,0,0,0.12); }
.cat-badge { display:inline-block;padding:5px 14px;border-radius:20px;font-size:0.82rem;font-weight:700; }
.score-pill { display:inline-block;padding:4px 14px;border-radius:20px;font-size:0.85rem;font-weight:700; }
</style>
</head>
<body>
<?php require_once '../sidebar.php'; ?>
<div class="topbar">
    <div class="topbar-left">
        <button class="hamburger" onclick="toggleSidebar()">☰</button>
        <span class="page-title">📝 Aptitude Tests</span>
    </div>
    <div class="topbar-right"><?php require_once '../../notifications/widget.php'; ?></div>
</div>
<div class="main-content">
    <div class="card" style="background:linear-gradient(135deg,#4a148c,#7b1fa2);color:#fff;margin-bottom:25px">
        <h2 style="color:#ffd54f;margin-bottom:8px">📝 Online Aptitude Tests</h2>
        <p style="color:#ce93d8">Take timed tests to improve your placement readiness. Results are auto-evaluated instantly.</p>
    </div>

    <?php if ($tests->num_rows === 0): ?>
    <div class="card" style="text-align:center;padding:40px">
        <div style="font-size:3rem;margin-bottom:15px">📋</div>
        <h3 style="color:#1a237e">No Tests Available</h3>
        <p style="color:#666;margin-top:8px">Check back later. Admin will publish tests soon.</p>
    </div>
    <?php else: ?>
    <?php while($t = $tests->fetch_assoc()):
        $cat = $t['category'];
        $attempted = !empty($t['attempt_id']);
        $pct = $t['total_marks'] > 0 ? round(($t['my_score'] / $t['total_marks']) * 100) : 0;
        $passed = $t['my_score'] >= $t['pass_marks'];
        // Schedule
        $hasSchedule  = !empty($t['round_start']);
        $startTs      = $hasSchedule ? strtotime($t['round_start']) : 0;
        $endTs        = ($hasSchedule && !empty($t['round_end'])) ? strtotime($t['round_end']) : PHP_INT_MAX;
        $isBeforeStart = $hasSchedule && $now < $startTs;
        $isAfterEnd    = $hasSchedule && $now > $endTs;
        $isOpen        = !$hasSchedule || ($now >= $startTs && $now <= $endTs);
    ?>
    <div class="test-card" style="border-left-color:<?= $isAfterEnd ? '#9e9e9e' : ($isBeforeStart ? '#fb8c00' : $catColors[$cat]) ?>">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px">
            <div style="flex:1">
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:8px">
                    <h3 style="color:#1a237e;margin:0"><?= htmlspecialchars($t['title']) ?></h3>
                    <span class="cat-badge" style="background:<?= $catBg[$cat] ?>;color:<?= $catColors[$cat] ?>"><?= $catIcons[$cat] ?> <?= ucfirst($cat) ?></span>
                    <?php if ($attempted && empty($t['round_id'])): ?>
                    <span class="score-pill" style="background:<?= $passed?'#e8f5e9':'#ffebee' ?>;color:<?= $passed?'#2e7d32':'#c62828' ?>">
                        <?= $passed?'✅ Passed':'❌ Failed' ?> — <?= $t['my_score'] ?>/<?= $t['total_marks'] ?>
                    </span>
                    <?php elseif ($attempted && !empty($t['round_id'])): ?>
                    <span class="score-pill" style="background:#e8eaf6;color:#1a237e">✓ Submitted</span>
                    <?php endif; ?>
                    <?php if ($isAfterEnd): ?>
                    <span style="background:#f5f5f5;color:#9e9e9e;padding:3px 10px;border-radius:12px;font-size:0.78rem;font-weight:700">🔒 Closed</span>
                    <?php elseif ($isBeforeStart): ?>
                    <span style="background:#fff8e1;color:#e65100;padding:3px 10px;border-radius:12px;font-size:0.78rem;font-weight:700">⏳ Scheduled</span>
                    <?php elseif ($isOpen && $hasSchedule): ?>
                    <span style="background:#e8f5e9;color:#2e7d32;padding:3px 10px;border-radius:12px;font-size:0.78rem;font-weight:700">🟢 Open Now</span>
                    <?php endif; ?>
                </div>
                <?php if ($t['description']): ?>
                <p style="color:#666;font-size:0.9rem;margin-bottom:10px"><?= htmlspecialchars($t['description']) ?></p>
                <?php endif; ?>
                <div style="display:flex;gap:15px;flex-wrap:wrap;font-size:0.85rem;color:#555">
                    <span>⏱️ <?= $t['display_duration'] ?> minutes</span>
                    <span>❓ <?= $t['q_count'] ?> questions</span>
                    <span>🏆 <?= $t['total_marks'] ?> marks</span>
                    <?php if ($t['pass_marks'] > 0): ?><span>✅ Pass: <?= $t['pass_marks'] ?></span><?php endif; ?>
                    <?php if (!empty($t['min_pass_score']) && $t['min_pass_score'] > 0): ?><span>🔒 Cutoff: <?= $t['min_pass_score'] ?>%</span><?php endif; ?>
                </div>
                <?php if ($hasSchedule): ?>
                <div style="margin-top:10px;padding:10px 14px;background:#f8f9ff;border-radius:8px;font-size:0.83rem">
                    <?php if ($isBeforeStart): ?>
                    <div style="color:#e65100;font-weight:600;margin-bottom:4px">⏳ Opens in: <span id="cd-<?= $t['id'] ?>" style="font-family:monospace;font-weight:800"></span></div>
                    <div style="color:#888">🕐 Start: <strong><?= date('d M Y, h:i A', $startTs) ?></strong> &nbsp;→&nbsp; 🕑 End: <strong><?= date('d M Y, h:i A', $endTs) ?></strong></div>
                    <script>startCountdown(<?= $t['id'] ?>, <?= $startTs ?>);</script>
                    <?php elseif ($isOpen): ?>
                    <div style="color:#2e7d32;font-weight:600;margin-bottom:4px">🟢 Open — closes in: <span id="cd-<?= $t['id'] ?>" style="font-family:monospace;font-weight:800"></span></div>
                    <div style="color:#888">🕑 Closes: <strong><?= date('d M Y, h:i A', $endTs) ?></strong></div>
                    <script>startCountdown(<?= $t['id'] ?>, <?= $endTs ?>);</script>
                    <?php else: ?>
                    <div style="color:#9e9e9e">🔒 This test window has closed.</div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <div style="display:flex;flex-direction:column;gap:8px;min-width:130px">
                <?php if ($attempted): ?>
                <?php if (!empty($t['round_id'])): ?>
                <a href="result.php?attempt_id=<?= $t['attempt_id'] ?>" class="btn btn-sm" style="background:#3f51b5;color:#fff;text-align:center">View Status</a>
                <?php else: ?>
                <a href="result.php?attempt_id=<?= $t['attempt_id'] ?>" class="btn btn-warning btn-sm" style="text-align:center">View Result</a>
                <?php if ($isOpen): ?>
                <a href="take_test.php?test_id=<?= $t['id'] ?>" class="btn btn-sm" style="background:#607d8b;color:#fff;text-align:center">Retake Test</a>
                <?php endif; ?>
                <?php endif; ?>
                <?php elseif ($isAfterEnd): ?>
                <span style="color:#9e9e9e;font-size:0.85rem;text-align:center">Window Closed</span>
                <?php elseif ($isBeforeStart): ?>
                <button disabled class="btn btn-sm" style="background:#e0e0e0;color:#aaa;cursor:not-allowed;text-align:center">🔒 Not Started</button>
                <?php elseif ($t['q_count'] > 0): ?>
                <a href="take_test.php?test_id=<?= $t['id'] ?>" class="btn btn-primary btn-sm" style="text-align:center">Start Test →</a>
                <?php else: ?>
                <span style="color:#999;font-size:0.85rem;text-align:center">No questions yet</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endwhile; ?>
    <?php endif; ?>
</div>
</div><!-- app-layout -->
<?php require_once '../../chatbot/widget.php'; ?>
<script>
function startCountdown(id, targetTs) {
    const el = document.getElementById('cd-' + id);
    if (!el) return;
    function tick() {
        const diff = targetTs - Math.floor(Date.now() / 1000);
        if (diff <= 0) { el.textContent = '00:00:00'; location.reload(); return; }
        const h = String(Math.floor(diff/3600)).padStart(2,'0');
        const m = String(Math.floor((diff%3600)/60)).padStart(2,'0');
        const s = String(diff%60).padStart(2,'0');
        el.textContent = h+':'+m+':'+s;
    }
    tick(); setInterval(tick, 1000);
}
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('show');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('sidebarOverlay').classList.remove('show');}
</script>
</body>
</html>
