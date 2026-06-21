<?php
require_once '../../includes/config.php';
requireLogin('student');

$uid = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if ($uid === 0) { header('Location: ../../index.php'); exit(); }

// Ensure scheduled_tests table exists
$conn->query("CREATE TABLE IF NOT EXISTS scheduled_tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_id INT NOT NULL,
    student_id INT NOT NULL,
    scheduled_at DATETIME NOT NULL,
    note TEXT,
    status ENUM('pending','completed','missed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
)");

$stIV = $conn->prepare("SELECT i.*, j.title as job_title, c.company_name, c.industry FROM interviews i JOIN jobs j ON i.job_id=j.id JOIN companies c ON i.company_id=c.id WHERE i.student_id=? ORDER BY i.scheduled_at ASC");
$stIV->bind_param('i',$uid); $stIV->execute();
$interviews = $stIV->get_result(); $stIV->close();

$stUp = $conn->prepare("SELECT COUNT(*) as c FROM interviews WHERE student_id=? AND status='scheduled' AND scheduled_at > NOW()");
$stUp->bind_param('i',$uid); $stUp->execute();
$upcoming = (int)$stUp->get_result()->fetch_assoc()['c']; $stUp->close();

$stCo = $conn->prepare("SELECT COUNT(*) as c FROM interviews WHERE student_id=? AND status='completed'");
$stCo->bind_param('i',$uid); $stCo->execute();
$completed = (int)$stCo->get_result()->fetch_assoc()['c']; $stCo->close();

$stST = $conn->prepare("SELECT st.*, t.title as test_title, t.category, t.duration, t.total_marks, t.pass_marks,
    (SELECT COUNT(*) FROM test_questions WHERE test_id=t.id) as q_count,
    (SELECT id FROM test_attempts WHERE test_id=t.id AND student_id=? AND status='completed' LIMIT 1) as attempt_id,
    (SELECT score FROM test_attempts WHERE test_id=t.id AND student_id=? AND status='completed' LIMIT 1) as my_score
    FROM scheduled_tests st JOIN tests t ON st.test_id=t.id WHERE st.student_id=? ORDER BY st.scheduled_at ASC");
$stST->bind_param('iii',$uid,$uid,$uid); $stST->execute();
$scheduled_tests = $stST->get_result(); $stST->close();

$platformIcons = ['google_meet'=>'🟢','zoom'=>'🔵','teams'=>'🟣','jitsi'=>'🟠','other'=>'⚪'];
$platformNames = ['google_meet'=>'Google Meet','zoom'=>'Zoom','teams'=>'MS Teams','jitsi'=>'Jitsi Meet','other'=>'Other'];
$statusColors  = ['scheduled'=>'#1565c0','completed'=>'#2e7d32','cancelled'=>'#c62828','rescheduled'=>'#e65100'];
$statusBg      = ['scheduled'=>'#e3f2fd','completed'=>'#e8f5e9','cancelled'=>'#ffebee','rescheduled'=>'#fff8e1'];
$catIcons  = ['aptitude'=>'📊','technical'=>'💻','coding'=>'🖥️'];
$catColors = ['aptitude'=>'#1565c0','technical'=>'#2e7d32','coding'=>'#6a1b9a'];
$catBg     = ['aptitude'=>'#e3f2fd','technical'=>'#e8f5e9','coding'=>'#f3e5f5'];

$ivList = [];
while ($row = $interviews->fetch_assoc()) $ivList[] = $row;
$stList = [];
while ($row = $scheduled_tests->fetch_assoc()) $stList[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Interviews</title>
<link rel="stylesheet" href="../../css/style.css">
<style>
.interview-card { background:#fff;border-radius:12px;padding:22px;box-shadow:0 2px 12px rgba(0,0,0,0.08);margin-bottom:18px;border-left:5px solid #3f51b5;transition:transform 0.2s; }
.interview-card:hover { transform:translateY(-2px); }
.platform-badge { display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:20px;font-size:0.82rem;font-weight:700;background:#f5f5f5;color:#333; }
.status-badge { display:inline-block;padding:4px 12px;border-radius:20px;font-size:0.82rem;font-weight:700; }
.meet-btn { display:inline-flex;align-items:center;gap:8px;padding:10px 22px;border-radius:8px;background:linear-gradient(135deg,#1a237e,#3949ab);color:#fff;text-decoration:none;font-weight:700;font-size:0.95rem;transition:all 0.2s;border:none;cursor:pointer; }
.meet-btn:hover { background:linear-gradient(135deg,#283593,#3f51b5);transform:translateY(-2px);box-shadow:0 4px 15px rgba(63,81,181,0.4); }
.meet-btn.disabled { background:#9e9e9e;cursor:not-allowed;transform:none;box-shadow:none; }
.countdown { font-size:1.1rem;font-weight:800;color:#1565c0;font-family:monospace; }
.countdown.soon { color:#e65100; }
.countdown.now { color:#2e7d32;animation:pulse 1s infinite; }
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.6} }
.tips-grid { display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;margin-top:10px; }
.tip-item { background:#f8f9ff;border-radius:8px;padding:12px;border-left:3px solid #3f51b5;font-size:0.88rem;color:#444; }
</style>
</head>
<body>
<?php require_once '../sidebar.php'; ?>
<div class="topbar">
    <div class="topbar-left">
        <button class="hamburger" onclick="toggleSidebar()">☰</button>
        <span class="page-title">🎥 My Interviews</span>
    </div>
    <div class="topbar-right"><?php require_once '../../notifications/widget.php'; ?></div>
</div>
<div class="main-content">
    <!-- Header -->
    <div class="card" style="background:linear-gradient(135deg,#0d47a1,#1565c0);color:#fff;margin-bottom:25px">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:15px">
            <div>
                <h2 style="color:#ffd54f;margin-bottom:8px">🎥 My Interviews & Scheduled Tests</h2>
                <p style="color:#90caf9">View your scheduled interviews, join meetings, and take assigned tests.</p>
            </div>
            <div style="display:flex;gap:20px">
                <div style="text-align:center">
                    <div style="font-size:2rem;font-weight:800;color:#69f0ae"><?= $upcoming ?></div>
                    <div style="font-size:0.8rem;color:#90caf9">Upcoming Interviews</div>
                </div>
                <div style="text-align:center">
                    <div style="font-size:2rem;font-weight:800;color:#ffd54f"><?= $completed ?></div>
                    <div style="font-size:0.8rem;color:#90caf9">Attended</div>
                </div>
                <div style="text-align:center">
                    <div style="font-size:2rem;font-weight:800;color:#80cbc4"><?= count($stList) ?></div>
                    <div style="font-size:0.8rem;color:#90caf9">Scheduled Tests</div>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($ivList)): ?>
    <div class="card" style="text-align:center;padding:50px">
        <div style="font-size:4rem;margin-bottom:15px">🎥</div>
        <h3 style="color:#1a237e;margin-bottom:10px">No Interviews Scheduled Yet</h3>
        <p style="color:#666;margin-bottom:20px">When a recruiter schedules an interview for you, it will appear here with the meeting link.</p>
        <a href="../jobs.php" class="btn btn-primary">Browse Jobs & Apply</a>
    </div>
    <?php else: ?>

    <!-- Interview Tips -->
    <div class="card">
        <h2>💡 Interview Tips</h2>
        <div class="tips-grid">
            <div class="tip-item">🕐 Join 5 minutes early to test your audio/video</div>
            <div class="tip-item">📄 Keep your resume ready on screen</div>
            <div class="tip-item">💡 Ensure good lighting and quiet environment</div>
            <div class="tip-item">👔 Dress professionally even for online interviews</div>
            <div class="tip-item">🔋 Charge your device fully before the interview</div>
            <div class="tip-item">📶 Use a stable internet connection</div>
        </div>
    </div>

    <?php foreach ($ivList as $iv):
        $isPast = strtotime($iv['scheduled_at']) < time();
        $isUpcoming = !$isPast && $iv['status'] === 'scheduled';
        $diffSecs = strtotime($iv['scheduled_at']) - time();
        $canJoin = $iv['meeting_link'] && $iv['status'] === 'scheduled' && $diffSecs < 1800; // 30 min before
    ?>
    <div class="interview-card" style="border-left-color:<?= $statusColors[$iv['status']] ?>">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:15px">
            <div style="flex:1">
                <!-- Company & Job -->
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:8px">
                    <h3 style="color:#1a237e;margin:0"><?= htmlspecialchars($iv['company_name']) ?></h3>
                    <span class="status-badge" style="background:<?= $statusBg[$iv['status']] ?>;color:<?= $statusColors[$iv['status']] ?>"><?= ucfirst($iv['status']) ?></span>
                    <span class="platform-badge"><?= $platformIcons[$iv['platform']] ?> <?= $platformNames[$iv['platform']] ?></span>
                </div>
                <div style="color:#555;font-size:0.9rem;margin-bottom:10px">
                    💼 <?= htmlspecialchars($iv['job_title']) ?>
                    <?php if ($iv['industry']): ?> · 🏢 <?= htmlspecialchars($iv['industry']) ?><?php endif; ?>
                </div>

                <!-- Date/Time -->
                <div style="display:flex;gap:15px;flex-wrap:wrap;font-size:0.88rem;margin-bottom:12px">
                    <span style="background:#e8eaf6;padding:5px 12px;border-radius:20px;color:#1a237e;font-weight:600">
                        📅 <?= date('D, d M Y', strtotime($iv['scheduled_at'])) ?>
                    </span>
                    <span style="background:#e8eaf6;padding:5px 12px;border-radius:20px;color:#1a237e;font-weight:600">
                        🕐 <?= date('h:i A', strtotime($iv['scheduled_at'])) ?>
                    </span>
                    <span style="background:#e8eaf6;padding:5px 12px;border-radius:20px;color:#1a237e;font-weight:600">
                        ⏱️ <?= $iv['duration'] ?> min
                    </span>
                </div>

                <!-- Countdown -->
                <?php if ($isUpcoming): ?>
                <div style="margin-bottom:12px">
                    <?php if ($diffSecs > 0): ?>
                    <span class="countdown <?= $diffSecs < 3600 ? ($diffSecs < 600 ? 'now' : 'soon') : '' ?>" id="cd-<?= $iv['id'] ?>" data-target="<?= strtotime($iv['scheduled_at']) ?>">
                        Loading...
                    </span>
                    <?php else: ?>
                    <span style="color:#2e7d32;font-weight:700">🟢 Interview time has started!</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Notes -->
                <?php if ($iv['notes']): ?>
                <div style="background:#fff8e1;border-radius:8px;padding:10px 14px;font-size:0.88rem;color:#555;margin-bottom:12px;border-left:3px solid #fb8c00">
                    📝 <strong>Recruiter Notes:</strong> <?= htmlspecialchars($iv['notes']) ?>
                </div>
                <?php endif; ?>

                <!-- Meeting Link -->
                <?php if ($iv['meeting_link'] && $iv['status'] !== 'cancelled'): ?>
                <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
                    <?php if ($canJoin): ?>
                    <a href="<?= htmlspecialchars($iv['meeting_link']) ?>" target="_blank" class="meet-btn">
                        <?= $platformIcons[$iv['platform']] ?> Join Interview Now
                    </a>
                    <?php else: ?>
                    <a href="<?= htmlspecialchars($iv['meeting_link']) ?>" target="_blank" class="meet-btn" style="background:#607d8b">
                        <?= $platformIcons[$iv['platform']] ?> Open Meeting Link
                    </a>
                    <?php endif; ?>
                    <button onclick="copyLink('<?= htmlspecialchars(addslashes($iv['meeting_link'])) ?>')" class="btn btn-sm" style="background:#e8eaf6;color:#3f51b5">
                        📋 Copy Link
                    </button>
                </div>
                <?php elseif ($iv['status'] === 'cancelled'): ?>
                <div style="color:#c62828;font-size:0.9rem">❌ This interview has been cancelled by the recruiter.</div>
                <?php else: ?>
                <div style="color:#999;font-size:0.88rem">⏳ Meeting link will be shared by the recruiter.</div>
                <?php endif; ?>
            </div>

            <!-- Calendar Add -->
            <?php if ($isUpcoming): ?>
            <div style="text-align:center">
                <div style="background:#e8eaf6;border-radius:10px;padding:15px;min-width:80px">
                    <div style="font-size:0.75rem;color:#3f51b5;font-weight:700;text-transform:uppercase"><?= date('M', strtotime($iv['scheduled_at'])) ?></div>
                    <div style="font-size:2rem;font-weight:800;color:#1a237e;line-height:1"><?= date('d', strtotime($iv['scheduled_at'])) ?></div>
                    <div style="font-size:0.75rem;color:#666"><?= date('D', strtotime($iv['scheduled_at'])) ?></div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <!-- ── SCHEDULED TESTS ── -->
    <div style="margin-top:30px">
        <div style="background:linear-gradient(135deg,#4a148c,#7b1fa2);border-radius:12px;padding:20px 24px;color:#fff;margin-bottom:18px">
            <h2 style="color:#ffd54f;margin-bottom:6px">📝 Scheduled Tests</h2>
            <p style="color:#ce93d8;font-size:0.9rem">Tests assigned to you by admin. Complete them before the scheduled deadline.</p>
        </div>

        <?php if (empty($stList)): ?>
        <div class="card" style="text-align:center;padding:35px;color:#999">
            <div style="font-size:3rem;margin-bottom:10px">📝</div>
            <p>No tests have been scheduled for you yet.</p>
            <a href="../aptitude_test/index.php" class="btn btn-primary" style="margin-top:12px">Browse Available Tests</a>
        </div>
        <?php else: ?>
        <?php foreach ($stList as $st):
            $cat = $st['category'] ?? 'aptitude';
            $attempted = !empty($st['attempt_id']);
            $isPast = strtotime($st['scheduled_at']) < time();
            $pct = $st['total_marks'] > 0 ? round(($st['my_score'] / $st['total_marks']) * 100) : 0;
            $passed = $attempted && $st['my_score'] >= $st['pass_marks'];
            $diffSecs = strtotime($st['scheduled_at']) - time();
        ?>
        <div style="background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 12px rgba(0,0,0,0.08);margin-bottom:14px;border-left:5px solid <?= $catColors[$cat] ?>;transition:transform 0.2s">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px">
                <div style="flex:1">
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:6px">
                        <h3 style="color:#1a237e;margin:0"><?= htmlspecialchars($st['test_title']) ?></h3>
                        <span style="background:<?= $catBg[$cat] ?>;color:<?= $catColors[$cat] ?>;padding:3px 10px;border-radius:20px;font-size:0.8rem;font-weight:700">
                            <?= $catIcons[$cat] ?> <?= ucfirst($cat) ?>
                        </span>
                        <?php if ($attempted): ?>
                        <span style="background:<?= $passed?'#e8f5e9':'#ffebee' ?>;color:<?= $passed?'#2e7d32':'#c62828' ?>;padding:3px 10px;border-radius:20px;font-size:0.8rem;font-weight:700">
                            <?= $passed?'✅ Passed':'❌ Failed' ?> — <?= $st['my_score'] ?>/<?= $st['total_marks'] ?>
                        </span>
                        <?php elseif ($isPast): ?>
                        <span style="background:#ffebee;color:#c62828;padding:3px 10px;border-radius:20px;font-size:0.8rem;font-weight:700">⚠️ Overdue</span>
                        <?php else: ?>
                        <span style="background:#fff8e1;color:#f57f17;padding:3px 10px;border-radius:20px;font-size:0.8rem;font-weight:700">⏳ Pending</span>
                        <?php endif; ?>
                    </div>

                    <div style="display:flex;gap:14px;flex-wrap:wrap;font-size:0.85rem;color:#555;margin-bottom:8px">
                        <span style="background:#e8eaf6;padding:4px 12px;border-radius:20px;color:#1a237e;font-weight:600">
                            📅 Scheduled: <?= date('D, d M Y · h:i A', strtotime($st['scheduled_at'])) ?>
                        </span>
                        <span>⏱️ <?= $st['duration'] ?> min</span>
                        <span>❓ <?= $st['q_count'] ?> questions</span>
                        <span>🏆 <?= $st['total_marks'] ?> marks</span>
                    </div>

                    <?php if (!$attempted && !$isPast && $diffSecs > 0): ?>
                    <div style="font-size:0.85rem;color:#e65100;font-weight:700">
                        ⏳ <?php
                            $d = floor($diffSecs/86400); $h = floor(($diffSecs%86400)/3600); $m = floor(($diffSecs%3600)/60);
                            if ($d > 0) echo "{$d}d {$h}h {$m}m remaining";
                            elseif ($h > 0) echo "{$h}h {$m}m remaining";
                            else echo "{$m}m remaining";
                        ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($st['note']): ?>
                    <div style="background:#fff8e1;border-radius:6px;padding:8px 12px;font-size:0.85rem;color:#555;margin-top:8px;border-left:3px solid #fb8c00">
                        📝 <strong>Admin Note:</strong> <?= htmlspecialchars($st['note']) ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div style="display:flex;flex-direction:column;gap:8px;min-width:130px;text-align:center">
                    <?php if ($attempted): ?>
                    <a href="../aptitude_test/result.php?attempt_id=<?= $st['attempt_id'] ?>" class="btn btn-warning btn-sm">View Result</a>
                    <a href="../aptitude_test/take_test.php?test_id=<?= $st['test_id'] ?>" class="btn btn-sm" style="background:#607d8b;color:#fff">Retake</a>
                    <?php elseif ($st['q_count'] > 0): ?>
                    <a href="../aptitude_test/take_test.php?test_id=<?= $st['test_id'] ?>" class="btn btn-primary btn-sm">📝 Take Test →</a>
                    <?php else: ?>
                    <span style="color:#999;font-size:0.82rem">No questions yet</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

</div>
</div><!-- app-layout -->
<?php require_once '../../chatbot/widget.php'; ?>
<script>
function copyLink(link) {
    navigator.clipboard.writeText(link).then(() => {
        alert('Meeting link copied to clipboard!');
    });
}
function updateCountdowns() {
    document.querySelectorAll('.countdown[data-target]').forEach(el => {
        const target = parseInt(el.dataset.target) * 1000;
        const diff = target - Date.now();
        if (diff <= 0) { el.textContent = '🟢 Interview is starting now!'; el.className = 'countdown now'; return; }
        const d = Math.floor(diff / 86400000);
        const h = Math.floor((diff % 86400000) / 3600000);
        const m = Math.floor((diff % 3600000) / 60000);
        const s = Math.floor((diff % 60000) / 1000);
        if (d > 0) el.textContent = `⏳ ${d}d ${h}h ${m}m remaining`;
        else if (h > 0) el.textContent = `⏳ ${h}h ${m}m ${s}s remaining`;
        else el.textContent = `⚡ ${m}m ${s}s remaining`;
        el.className = 'countdown' + (diff < 600000 ? ' now' : diff < 3600000 ? ' soon' : '');
    });
}
updateCountdowns();
setInterval(updateCountdowns, 1000);
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('show');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('sidebarOverlay').classList.remove('show');}
</script>
</body>
</html>
