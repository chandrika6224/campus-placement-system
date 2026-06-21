<?php
require_once '../../includes/config.php';
requireLogin('admin');

$conn->query("CREATE TABLE IF NOT EXISTS placement_rounds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    round_number INT NOT NULL,
    round_name VARCHAR(100) NOT NULL,
    round_type ENUM('aptitude','technical','hr','coding','group_discussion','other') DEFAULT 'other',
    description TEXT,
    test_id INT DEFAULT NULL,
    test_link VARCHAR(500),
    meeting_link VARCHAR(500),
    scheduled_at DATETIME DEFAULT NULL,
    duration INT DEFAULT 60,
    status ENUM('upcoming','active','completed') DEFAULT 'upcoming',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
)");
$conn->query("ALTER TABLE placement_rounds ADD COLUMN IF NOT EXISTS test_id INT DEFAULT NULL");
$conn->query("ALTER TABLE placement_rounds ADD COLUMN IF NOT EXISTS end_time DATETIME DEFAULT NULL");
$conn->query("ALTER TABLE placement_rounds ADD COLUMN IF NOT EXISTS coding_problem_id INT DEFAULT NULL");
$conn->query("ALTER TABLE placement_rounds ADD COLUMN IF NOT EXISTS min_pass_score INT DEFAULT 0");

$msg = '';

// Add round
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_round'])) {
    $job_id      = (int)$_POST['job_id'];
    $round_name  = $conn->real_escape_string(trim($_POST['round_name']));
    $round_type  = $conn->real_escape_string($_POST['round_type']);
    $description = $conn->real_escape_string(trim($_POST['description'] ?? ''));
    $test_link   = $conn->real_escape_string(trim($_POST['test_link'] ?? ''));
    $meet_link   = $conn->real_escape_string(trim($_POST['meeting_link'] ?? ''));
    $sched       = trim($_POST['start_time'] ?? '');
    $end          = trim($_POST['end_time'] ?? '');
    $sched_val    = !empty($sched) ? "'".$conn->real_escape_string($sched)."'" : 'NULL';
    $end_val      = !empty($end)   ? "'".$conn->real_escape_string($end)."'"   : 'NULL';
    // derive duration in minutes from start/end
    $duration = (!empty($sched) && !empty($end)) ? max(0, (int)((strtotime($end) - strtotime($sched)) / 60)) : 60;

    $rn = (int)($conn->query("SELECT COALESCE(MAX(round_number),0)+1 as n FROM placement_rounds WHERE job_id=$job_id")->fetch_assoc()['n']);

    $test_id_val        = (int)($_POST['test_id'] ?? 0);
    $test_id_sql         = $test_id_val > 0 ? $test_id_val : 'NULL';
    $coding_problem_val  = (int)($_POST['coding_problem_id'] ?? 0);
    $coding_problem_sql  = $coding_problem_val > 0 ? $coding_problem_val : 'NULL';
    $min_pass            = (int)($_POST['min_pass_score'] ?? 0);

    $conn->query("INSERT INTO placement_rounds (job_id, round_number, round_name, round_type, description, test_id, coding_problem_id, test_link, meeting_link, scheduled_at, end_time, duration, min_pass_score)
        VALUES ($job_id, $rn, '$round_name', '$round_type', '$description', $test_id_sql, $coding_problem_sql, '$test_link', '$meet_link', $sched_val, $end_val, $duration, $min_pass)");

    // Notify shortlisted students
    $job = $conn->query("SELECT j.title, c.company_name FROM jobs j JOIN companies c ON j.company_id=c.id WHERE j.id=$job_id")->fetch_assoc();
    $stus = $conn->query("SELECT student_id FROM applications WHERE job_id=$job_id AND status='shortlisted'");
    while ($s = $stus->fetch_assoc()) {
        $sid   = $s['student_id'];
        $notif = $conn->real_escape_string("Round $rn ($round_name) has been added for {$job['title']} at {$job['company_name']}. Check your dashboard for details and links.");
        $conn->query("INSERT INTO notifications (user_id, type, title, message, link) VALUES ($sid, 'application', 'New Placement Round Added', '$notif', '/placement/student/applications.php')");
    }
    $msg = '<div class="alert alert-success">✅ Round added and shortlisted students notified.</div>';
}

// Update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $rid    = (int)$_POST['round_id'];
    $status = $conn->real_escape_string($_POST['status']);
    $conn->query("UPDATE placement_rounds SET status='$status' WHERE id=$rid");
    $msg = '<div class="alert alert-success">✅ Status updated.</div>';
}

// Delete round
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_round'])) {
    $rid = (int)$_POST['round_id'];
    $jid = (int)$_POST['job_id'];
    $conn->query("DELETE FROM placement_rounds WHERE id=$rid");
    $rem = $conn->query("SELECT id FROM placement_rounds WHERE job_id=$jid ORDER BY round_number");
    $i = 1; while ($r = $rem->fetch_assoc()) { $conn->query("UPDATE placement_rounds SET round_number=$i WHERE id={$r['id']}"); $i++; }
    $msg = '<div class="alert alert-success">✅ Round deleted.</div>';
}

$selected_job = (int)($_GET['job_id'] ?? $_POST['job_id'] ?? 0);
$jobs  = $conn->query("SELECT j.*, c.company_name, (SELECT COUNT(*) FROM applications WHERE job_id=j.id AND status='shortlisted') as shortlisted FROM jobs j JOIN companies c ON j.company_id=c.id ORDER BY j.created_at DESC");
$tests_list = $conn->query("SELECT id, title, category FROM tests WHERE status='active' ORDER BY created_at DESC");
$tests_arr = [];
while ($t = $tests_list->fetch_assoc()) $tests_arr[] = $t;
$coding_problems_list = $conn->query("SELECT id, title, difficulty FROM coding_problems ORDER BY FIELD(difficulty,'easy','medium','hard'), title");
$coding_problems_arr = [];
while ($cp = $coding_problems_list->fetch_assoc()) $coding_problems_arr[] = $cp;
$rounds = [];
$job_info = null;
if ($selected_job) {
    $res = $conn->query("SELECT * FROM placement_rounds WHERE job_id=$selected_job ORDER BY round_number");
    $now = time();
    while ($row = $res->fetch_assoc()) {
        // Auto-derive status from start/end time
        if ($row['scheduled_at'] && $row['end_time']) {
            $start = strtotime($row['scheduled_at']);
            $end   = strtotime($row['end_time']);
            if ($now < $start)       $row['status'] = 'upcoming';
            elseif ($now <= $end)    $row['status'] = 'active';
            else                     $row['status'] = 'completed';
            // Persist auto status
            $conn->query("UPDATE placement_rounds SET status='{$row['status']}' WHERE id={$row['id']}");
        }
        $rounds[] = $row;
    }
    $job_info = $conn->query("SELECT j.*, c.company_name FROM jobs j JOIN companies c ON j.company_id=c.id WHERE j.id=$selected_job")->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Placement Rounds - Admin</title>
<link rel="stylesheet" href="../../css/style.css">
<style>
.round-card{background:#fff;border-radius:10px;padding:18px;margin-bottom:12px;box-shadow:0 2px 8px rgba(0,0,0,0.07);border-left:5px solid #3f51b5}
.round-card.active{border-left-color:#43a047}
.round-card.completed{border-left-color:#9e9e9e}
.rbadge{display:inline-block;padding:3px 12px;border-radius:12px;font-size:0.78rem;font-weight:700}
.t-aptitude{background:#e3f2fd;color:#1565c0}
.t-technical{background:#e8eaf6;color:#283593}
.t-hr{background:#fce4ec;color:#880e4f}
.t-coding{background:#e8f5e9;color:#1b5e20}
.t-group_discussion{background:#fff8e1;color:#e65100}
.t-other{background:#f3e5f5;color:#4a148c}
</style>
</head>
<body>
<?php require_once '../sidebar.php'; ?>
<div class="topbar">
    <div class="topbar-left">
        <button class="hamburger" onclick="toggleSidebar()">☰</button>
        <span class="page-title">🎯 Placement Rounds</span>
    </div>
    <div class="topbar-right"><?php require_once '../../notifications/widget.php'; ?></div>
</div>
<div class="main-content">
<?= $msg ?>
<div style="display:grid;grid-template-columns:270px 1fr;gap:20px">

    <!-- Job list -->
    <div class="card" style="height:fit-content">
        <h2 style="font-size:1rem;margin-bottom:14px">💼 Select Job</h2>
        <?php
        $jobs_arr = [];
        while ($j = $jobs->fetch_assoc()) $jobs_arr[] = $j;
        foreach ($jobs_arr as $j): ?>
        <a href="?job_id=<?= $j['id'] ?>" style="display:block;padding:10px 12px;border-radius:8px;text-decoration:none;margin-bottom:6px;background:<?= $selected_job==$j['id']?'#e8eaf6':'#f8f9ff' ?>;border-left:3px solid <?= $selected_job==$j['id']?'#3f51b5':'transparent' ?>">
            <div style="font-weight:700;color:#1a237e;font-size:0.88rem"><?= htmlspecialchars($j['title']) ?></div>
            <div style="font-size:0.78rem;color:#666"><?= htmlspecialchars($j['company_name']) ?> · <span style="color:<?= $j['shortlisted']>0?'#e65100':'#999' ?>"><?= $j['shortlisted'] ?> shortlisted</span></div>
            <div style="font-size:0.75rem;color:<?= !empty($j['deadline']) && strtotime($j['deadline']) < time() ? '#c62828' : '#2e7d32' ?>;margin-top:2px">
                📅 <?= !empty($j['deadline']) ? date('d M Y', strtotime($j['deadline'])) : 'No deadline' ?>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <div>
    <?php if (!$selected_job): ?>
    <div class="card" style="text-align:center;padding:50px;color:#999">
        <div style="font-size:3rem;margin-bottom:10px">🎯</div>
        <p>Select a job from the left to manage its placement rounds.</p>
    </div>
    <?php else: ?>

    <!-- Job banner -->
    <div class="card" style="background:linear-gradient(135deg,#1a237e,#3949ab);color:#fff;margin-bottom:18px;padding:18px">
        <h2 style="color:#ffd54f;margin-bottom:4px"><?= htmlspecialchars($job_info['title']) ?></h2>
        <div style="color:#c5cae9;font-size:0.88rem">🏢 <?= htmlspecialchars($job_info['company_name']) ?> · <?= count($rounds) ?> round(s) configured</div>
    </div>

    <!-- Add round form -->
    <div class="card" style="margin-bottom:18px">
        <h2 style="font-size:1rem;margin-bottom:16px">➕ Add New Round</h2>
        <form method="POST">
            <input type="hidden" name="add_round" value="1">
            <input type="hidden" name="job_id" value="<?= $selected_job ?>">
            <div class="form-row">
                <div class="form-group">
                    <label>Round Name *</label>
                    <input type="text" name="round_name" placeholder="e.g. Aptitude Test, Technical Interview" required>
                </div>
                <div class="form-group">
                    <label>Round Type *</label>
                    <select name="round_type" required>
                        <option value="aptitude">📝 Aptitude Test</option>
                        <option value="coding">💻 Coding Test</option>
                        <option value="technical">🔧 Technical Interview</option>
                        <option value="hr">👥 HR Interview</option>
                        <option value="group_discussion">💬 Group Discussion</option>
                        <option value="other">📌 Other</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Test / Assessment Link</label>
                    <input type="url" name="test_link" placeholder="https://hackerrank.com/test/...">
                </div>
                <div class="form-group" id="aptitude-test-group">
                    <label>Link to Internal Aptitude Test</label>
                    <select name="test_id">
                        <option value="">&mdash; None &mdash;</option>
                        <?php foreach ($tests_arr as $t): ?>
                        <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['title']) ?> (<?= ucfirst($t['category']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" id="coding-problem-group" style="display:none">
                    <label>Link to Internal Coding Problem</label>
                    <select name="coding_problem_id">
                        <option value="">&mdash; None &mdash;</option>
                        <?php foreach ($coding_problems_arr as $cp): ?>
                        <option value="<?= $cp['id'] ?>"><?= htmlspecialchars($cp['title']) ?> (<?= ucfirst($cp['difficulty']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
                <div class="form-group">
                    <label>Meeting / Interview Link</label>
                    <input type="url" name="meeting_link" placeholder="https://meet.google.com/...">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Start Date &amp; Time *</label>
                    <input type="datetime-local" name="start_time" required>
                </div>
                <div class="form-group">
                    <label>End Date &amp; Time *</label>
                    <input type="datetime-local" name="end_time" required>
                </div>
            </div>
            <div class="form-group">
                <label>Instructions for Students</label>
                <textarea name="description" rows="2" placeholder="What students should prepare, bring, or know..."></textarea>
            </div>
            <div class="form-group">
                <label>Minimum Pass Score (%) <small style="color:#999">— Students must score this % or above to unlock the next round (0 = no gate)</small></label>
                <input type="number" name="min_pass_score" value="0" min="0" max="100" style="width:120px">
            </div>
            <button type="submit" class="btn btn-primary">➕ Add Round & Notify Students</button>
        </form>
    </div>

    <!-- Rounds list -->
    <div class="card">
        <h2 style="font-size:1rem;margin-bottom:16px">📋 Configured Rounds (<?= count($rounds) ?>)</h2>
        <?php if (empty($rounds)): ?>
        <p style="text-align:center;color:#999;padding:20px">No rounds added yet.</p>
        <?php else: foreach ($rounds as $round): ?>
        <div class="round-card <?= $round['status'] ?>">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px">
                <div style="flex:1">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:6px">
                        <span style="background:#1a237e;color:#fff;border-radius:50%;width:26px;height:26px;display:inline-flex;align-items:center;justify-content:center;font-weight:800;font-size:0.85rem"><?= $round['round_number'] ?></span>
                        <strong style="color:#1a237e"><?= htmlspecialchars($round['round_name']) ?></strong>
                        <span class="rbadge t-<?= $round['round_type'] ?>"><?= ucfirst(str_replace('_',' ',$round['round_type'])) ?></span>
                        <span class="rbadge" style="background:<?= $round['status']==='active'?'#c8e6c9':($round['status']==='completed'?'#f5f5f5':'#fff8e1') ?>;color:<?= $round['status']==='active'?'#1b5e20':($round['status']==='completed'?'#757575':'#e65100') ?>"><?= ucfirst($round['status']) ?></span>
                    </div>
                    <?php if ($round['scheduled_at']): ?>
                    <div style="font-size:0.83rem;color:#555;margin-bottom:4px">
                        🕐 Start: <strong><?= date('d M Y, h:i A', strtotime($round['scheduled_at'])) ?></strong>
                        <?php if ($round['end_time']): ?>
                        &nbsp;→&nbsp; 🕑 End: <strong><?= date('d M Y, h:i A', strtotime($round['end_time'])) ?></strong>
                        &nbsp;·&nbsp; ⏱ <?= $round['duration'] ?> mins
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($round['description']): ?>
                    <div style="font-size:0.83rem;color:#666;margin-bottom:6px">📌 <?= htmlspecialchars($round['description']) ?></div>
                    <?php endif; ?>
                    <?php if ($round['min_pass_score'] > 0): ?>
                    <div style="font-size:0.82rem;color:#e65100;font-weight:600;margin-bottom:6px">🔒 Next round unlocks at: <?= $round['min_pass_score'] ?>% pass score</div>
                    <?php endif; ?>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:6px">
                        <?php if ($round['test_link']): ?>
                        <a href="<?= htmlspecialchars($round['test_link']) ?>" target="_blank" style="font-size:0.82rem;background:#e3f2fd;color:#1565c0;padding:4px 12px;border-radius:10px;text-decoration:none;font-weight:600">📝 Test Link</a>
                        <?php endif; ?>
                        <?php if (!empty($round['test_id'])): ?>
                        <a href="../aptitude/results.php?test_id=<?= $round['test_id'] ?>" style="font-size:0.82rem;background:#e8f5e9;color:#1b5e20;padding:4px 12px;border-radius:10px;text-decoration:none;font-weight:600">📊 View Results</a>
                        <?php endif; ?>
                        <?php if (!empty($round['coding_problem_id'])): ?>
                        <a href="../coding/index.php" style="font-size:0.82rem;background:#e8f5e9;color:#1b5e20;padding:4px 12px;border-radius:10px;text-decoration:none;font-weight:600">💻 View Submissions</a>
                        <?php endif; ?>
                        <?php if ($round['meeting_link']): ?>
                        <a href="<?= htmlspecialchars($round['meeting_link']) ?>" target="_blank" style="font-size:0.82rem;background:#e8f5e9;color:#1b5e20;padding:4px 12px;border-radius:10px;text-decoration:none;font-weight:600">🎥 Meeting Link</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="display:flex;gap:6px;align-items:center;flex-shrink:0">
                    <form method="POST">
                        <input type="hidden" name="update_status" value="1">
                        <input type="hidden" name="round_id" value="<?= $round['id'] ?>">
                        <input type="hidden" name="job_id" value="<?= $selected_job ?>">
                        <select name="status" onchange="this.form.submit()" style="padding:4px 8px;border-radius:6px;border:1px solid #ddd;font-size:0.82rem">
                            <option value="upcoming"  <?= $round['status']==='upcoming' ?'selected':'' ?>>Upcoming</option>
                            <option value="active"    <?= $round['status']==='active'   ?'selected':'' ?>>Active</option>
                            <option value="completed" <?= $round['status']==='completed'?'selected':'' ?>>Completed</option>
                        </select>
                    </form>
                    <form method="POST" onsubmit="return confirm('Delete this round?')">
                        <input type="hidden" name="delete_round" value="1">
                        <input type="hidden" name="round_id" value="<?= $round['id'] ?>">
                        <input type="hidden" name="job_id" value="<?= $selected_job ?>">
                        <button class="btn btn-danger btn-sm">🗑</button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; endif; ?>
    </div>
    <?php endif; ?>
    </div>
</div>
</div>
</div>
<?php require_once '../../chatbot/widget.php'; ?>
<script>
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('show');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('sidebarOverlay').classList.remove('show');}
function toggleRoundType(val) {
    document.getElementById('aptitude-test-group').style.display  = val === 'aptitude' ? 'block' : 'none';
    document.getElementById('coding-problem-group').style.display = val === 'coding'   ? 'block' : 'none';
}
document.querySelector('select[name="round_type"]').addEventListener('change', function(){ toggleRoundType(this.value); });
toggleRoundType(document.querySelector('select[name="round_type"]').value);
</script>
</body>
</html>

