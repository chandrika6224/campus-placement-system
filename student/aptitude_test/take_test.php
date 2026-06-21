<?php
require_once '../../includes/config.php';
requireLogin('student');

$uid = $_SESSION['user_id'];
$test_id = (int)($_GET['test_id'] ?? 0);
if (!$test_id) { header("Location: index.php"); exit(); }

$test = $conn->query("SELECT * FROM tests WHERE id=$test_id AND status='active'")->fetch_assoc();
if (!$test) { header("Location: index.php"); exit(); }

// Block access before start time or after end time — use UTC
$roundCheck = $conn->query("SELECT id, scheduled_at, end_time FROM placement_rounds WHERE test_id=$test_id AND scheduled_at IS NOT NULL ORDER BY scheduled_at ASC LIMIT 1")->fetch_assoc();
$roundEndTs = PHP_INT_MAX;
if ($roundCheck) {
    $start = strtotime($roundCheck['scheduled_at']);
    $end   = $roundCheck['end_time'] ? strtotime($roundCheck['end_time']) : PHP_INT_MAX;
    $roundEndTs = $end;
    $now   = time();
    if ($now < $start) {
        $availableAt = date('d M Y, h:i A', $start);
        die("<!DOCTYPE html><html><head><title>Not Yet Available</title><link rel='stylesheet' href='../../css/style.css'></head><body style='display:flex;align-items:center;justify-content:center;min-height:100vh'><div class='card' style='text-align:center;max-width:420px;padding:40px'><div style='font-size:3rem;margin-bottom:16px'>&#9203;</div><h2 style='color:#1a237e'>Test Not Yet Available</h2><p style='color:#666;margin:12px 0'>This test opens on:</p><div style='background:#e8eaf6;border-radius:8px;padding:12px;font-size:1.1rem;font-weight:700;color:#1a237e;margin-bottom:20px'>$availableAt</div><a href='index.php' class='btn btn-primary'>&larr; Back to Tests</a></div></body></html>");
    }
    if ($now >= $end) {
        die("<!DOCTYPE html><html><head><title>Test Closed</title><link rel='stylesheet' href='../../css/style.css'></head><body style='display:flex;align-items:center;justify-content:center;min-height:100vh'><div class='card' style='text-align:center;max-width:420px;padding:40px'><div style='font-size:3rem;margin-bottom:16px'>&#128683;</div><h2 style='color:#c62828'>Test Window Closed</h2><p style='color:#666;margin:12px 0'>The submission window for this test has ended.</p><a href='index.php' class='btn btn-primary'>&larr; Back to Tests</a></div></body></html>");
    }
    require_once '../../includes/round_gate.php';
    $gate = checkRoundGate($conn, $uid, $roundCheck['id']);
    if (!$gate['pass']) roundGateBlock($gate, '/placement/student/aptitude_test/index.php');
}

$questions = [];
$qResult = $conn->query("SELECT * FROM test_questions WHERE test_id=$test_id ORDER BY id ASC");
while ($row = $qResult->fetch_assoc()) $questions[] = $row;

if (empty($questions)) { header("Location: index.php"); exit(); }

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_test'])) {
    $answers = $_POST['answers'] ?? [];

    // Check for existing incomplete attempt or create new
    $existingAttempt = $conn->query("SELECT id FROM test_attempts WHERE test_id=$test_id AND student_id=$uid AND status='started' ORDER BY started_at DESC LIMIT 1")->fetch_assoc();

    if ($existingAttempt) {
        $attempt_id = $existingAttempt['id'];
        // Clear old answers
        $conn->query("DELETE FROM test_answers WHERE attempt_id=$attempt_id");
    } else {
        $conn->query("INSERT INTO test_attempts (test_id,student_id,total_marks,status) VALUES ($test_id,$uid,{$test['total_marks']},'started')");
        $attempt_id = $conn->insert_id;
    }

    $score = 0; $correct = 0; $wrong = 0;
    foreach ($questions as $q) {
        $qid = $q['id'];
        $selected = isset($answers[$qid]) ? $conn->real_escape_string($answers[$qid]) : null;
        $is_correct = ($selected === $q['correct_answer']) ? 1 : 0;
        if ($is_correct) { $score += $q['marks']; $correct++; } elseif ($selected) { $wrong++; }
        $selVal = $selected ? "'$selected'" : "NULL";
        $conn->query("INSERT INTO test_answers (attempt_id,question_id,selected_answer,is_correct) VALUES ($attempt_id,$qid,$selVal,$is_correct)");
    }

    $conn->query("UPDATE test_attempts SET score=$score,correct_answers=$correct,wrong_answers=$wrong,status='completed',completed_at=NOW() WHERE id=$attempt_id");
    header("Location: result.php?attempt_id=$attempt_id");
    exit();
}

// Create or resume attempt
$attempt = $conn->query("SELECT * FROM test_attempts WHERE test_id=$test_id AND student_id=$uid AND status='started' ORDER BY started_at DESC LIMIT 1")->fetch_assoc();
if (!$attempt) {
    $conn->query("INSERT INTO test_attempts (test_id,student_id,total_marks) VALUES ($test_id,$uid,{$test['total_marks']})");
    $attempt_id = $conn->insert_id;
    $attempt = $conn->query("SELECT * FROM test_attempts WHERE id=$attempt_id")->fetch_assoc();
}

$elapsed = time() - strtotime($attempt['started_at']);
// Use round window duration if linked to a round, else test duration
$testDurationSecs = ($roundCheck && !empty($roundCheck['end_time']))
    ? max(0, strtotime($roundCheck['end_time']) - strtotime($roundCheck['scheduled_at']))
    : ($test['duration'] * 60);
$testRemaining = max(0, $testDurationSecs - $elapsed);
// Also cap by round end_time if set
$roundSecsLeft = ($roundEndTs < PHP_INT_MAX) ? max(0, $roundEndTs - time()) : PHP_INT_MAX;
$remaining = min($testRemaining, $roundSecsLeft);
if ($remaining === 0) {
    // Auto-submit
    $answers = [];
    $existingAttempt = $conn->query("SELECT id FROM test_attempts WHERE test_id=$test_id AND student_id=$uid AND status='started' ORDER BY started_at DESC LIMIT 1")->fetch_assoc();
    if ($existingAttempt) {
        $attempt_id = $existingAttempt['id'];
        $conn->query("UPDATE test_attempts SET status='completed',completed_at=NOW() WHERE id=$attempt_id");
    }
    header("Location: index.php"); exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($test['title']) ?></title>
<link rel="stylesheet" href="../../css/style.css">
<style>
.test-header { position:sticky;top:0;z-index:100;background:linear-gradient(135deg,#1a237e,#3949ab);color:#fff;padding:15px 30px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;box-shadow:0 2px 10px rgba(0,0,0,0.3); }
.timer { font-size:1.5rem;font-weight:800;color:#ffd54f;font-family:monospace; }
.timer.warning { color:#ff7043; }
.timer.danger { color:#ef5350;animation:blink 0.5s infinite; }
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.4} }
.q-nav { display:flex;flex-wrap:wrap;gap:6px;margin-bottom:20px; }
.q-nav button { width:38px;height:38px;border-radius:6px;border:2px solid #e0e0e0;background:#fff;cursor:pointer;font-weight:700;font-size:0.85rem;transition:all 0.2s; }
.q-nav button.answered { background:#3f51b5;color:#fff;border-color:#3f51b5; }
.q-nav button.current { border-color:#fb8c00;box-shadow:0 0 0 3px rgba(251,140,0,0.3); }
.question-block { display:none; }
.question-block.active { display:block; }
.option-label { display:flex;align-items:center;gap:12px;padding:12px 16px;border:2px solid #e0e0e0;border-radius:8px;cursor:pointer;margin-bottom:10px;transition:all 0.2s;font-size:0.95rem; }
.option-label:hover { border-color:#3f51b5;background:#f5f5ff; }
.option-label input[type=radio] { width:18px;height:18px;accent-color:#3f51b5; }
.option-label.selected { border-color:#3f51b5;background:#e8eaf6; }
.progress-bar { height:6px;background:#e0e0e0;border-radius:3px;margin-bottom:20px; }
.progress-fill { height:6px;border-radius:3px;background:linear-gradient(90deg,#3f51b5,#7986cb);transition:width 0.3s; }
@keyframes slideDown { from{opacity:0;top:50px} to{opacity:1;top:70px} }
</style>
</head>
<body>
<div class="test-header">
    <div>
        <div style="font-size:1.1rem;font-weight:700"><?= htmlspecialchars($test['title']) ?></div>
        <div style="font-size:0.85rem;color:#c5cae9"><?= count($questions) ?> Questions · <?= $test['total_marks'] ?> Marks</div>
    </div>
    <div style="text-align:center">
        <div style="font-size:0.8rem;color:#c5cae9;margin-bottom:2px">Time Remaining</div>
        <div class="timer" id="timer">--:--</div>
    </div>
    <div>
        <span id="answered-count" style="color:#69f0ae;font-weight:700">0</span>/<span style="color:#c5cae9"><?= count($questions) ?></span>
        <span style="color:#c5cae9;font-size:0.85rem;margin-left:5px">answered</span>
    </div>
</div>

<div class="container" style="margin-top:20px">
    <div class="progress-bar"><div class="progress-fill" id="progress" style="width:0%"></div></div>

    <!-- Question Navigator -->
    <div class="card" style="padding:15px">
        <div style="font-size:0.85rem;font-weight:600;color:#555;margin-bottom:10px">Question Navigator</div>
        <div class="q-nav" id="q-nav">
            <?php foreach ($questions as $i => $q): ?>
            <button type="button" id="nav-<?= $i ?>" onclick="goTo(<?= $i ?>)" class="<?= $i===0?'current':'' ?>"><?= $i+1 ?></button>
            <?php endforeach; ?>
        </div>
        <div style="display:flex;gap:15px;font-size:0.8rem;color:#666;flex-wrap:wrap">
            <span><span style="display:inline-block;width:14px;height:14px;background:#3f51b5;border-radius:3px;vertical-align:middle;margin-right:4px"></span>Answered</span>
            <span><span style="display:inline-block;width:14px;height:14px;border:2px solid #fb8c00;border-radius:3px;vertical-align:middle;margin-right:4px"></span>Current</span>
            <span><span style="display:inline-block;width:14px;height:14px;border:2px solid #e0e0e0;border-radius:3px;vertical-align:middle;margin-right:4px"></span>Not Answered</span>
        </div>
    </div>

    <form method="POST" id="test-form">
        <input type="hidden" name="submit_test" value="1">

        <?php foreach ($questions as $i => $q): ?>
        <div class="question-block card <?= $i===0?'active':'' ?>" id="q-<?= $i ?>">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px">
                <span style="background:#e8eaf6;color:#1a237e;padding:4px 12px;border-radius:20px;font-size:0.85rem;font-weight:700">Question <?= $i+1 ?> of <?= count($questions) ?></span>
                <span style="color:#666;font-size:0.85rem"><?= $q['marks'] ?> mark<?= $q['marks']>1?'s':'' ?></span>
            </div>
            <p style="font-size:1.05rem;font-weight:600;color:#1a237e;margin-bottom:20px;line-height:1.6"><?= htmlspecialchars($q['question']) ?></p>
            <?php foreach (['a','b','c','d'] as $opt): ?>
            <label class="option-label" id="lbl-<?= $i ?>-<?= $opt ?>">
                <input type="radio" name="answers[<?= $q['id'] ?>]" value="<?= $opt ?>" onchange="markAnswered(<?= $i ?>,this)">
                <span style="font-weight:700;color:#3f51b5;min-width:20px"><?= strtoupper($opt) ?>.</span>
                <span><?= htmlspecialchars($q['option_'.strtolower($opt)]) ?></span>
            </label>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>

        <!-- Navigation Buttons -->
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:10px">
            <button type="button" class="btn" style="background:#607d8b;color:#fff" onclick="prevQ()" id="btn-prev" disabled>← Previous</button>
            <button type="button" class="btn btn-primary" onclick="nextQ()" id="btn-next">Next →</button>
            <button type="button" class="btn btn-success" id="btn-submit" style="display:none" onclick="submitTest()">Submit Test ✓</button>
        </div>
    </form>
</div>

<script>
const totalQ = <?= count($questions) ?>;
const pageLoadTs = <?= time() ?>; // server unix timestamp when page rendered
const remaining  = <?= $remaining ?>; // seconds left at page load
const roundEndTs = <?= $roundEndTs === PHP_INT_MAX ? 'null' : $roundEndTs ?>;
let current = 0;
const answered = new Set();
let notified5min = false;

if (Notification && Notification.permission === 'default') Notification.requestPermission();

function getTimeLeft() {
    const nowTs = Math.floor(Date.now() / 1000);
    const elapsed = nowTs - pageLoadTs;
    const byDuration = remaining - elapsed;
    if (roundEndTs) {
        return Math.min(byDuration, roundEndTs - nowTs);
    }
    return byDuration;
}

const timerEl = document.getElementById('timer');
const timerInterval = setInterval(() => {
    const timeLeft = getTimeLeft();
    const m = String(Math.floor(Math.max(0,timeLeft) / 60)).padStart(2,'0');
    const s = String(Math.max(0,timeLeft) % 60).padStart(2,'0');
    timerEl.textContent = m + ':' + s;

    if (timeLeft <= 300 && timeLeft > 0) {
        timerEl.className = 'timer danger';
        document.querySelector('.test-header').style.background = 'linear-gradient(135deg,#b71c1c,#c62828)';
        if (!notified5min) {
            notified5min = true;
            // Browser notification
            if (Notification && Notification.permission === 'granted') {
                new Notification('⏰ 5 Minutes Left!', { body: 'Your test is about to end. Submit now!', icon: '' });
            }
            // In-page alert banner
            const banner = document.createElement('div');
            banner.id = 'warn-banner';
            banner.innerHTML = '⚠️ Only 5 minutes remaining! Please submit your answers soon.';
            banner.style.cssText = 'position:fixed;top:70px;left:50%;transform:translateX(-50%);background:#c62828;color:#fff;padding:12px 28px;border-radius:8px;font-weight:700;z-index:9999;box-shadow:0 4px 15px rgba(0,0,0,0.3);font-size:0.95rem;animation:slideDown 0.4s ease';
            document.body.appendChild(banner);
            setTimeout(() => banner.remove(), 8000);
        }
    } else if (timeLeft <= 600) {
        timerEl.className = 'timer warning';
    } else {
        timerEl.className = 'timer';
    }

    if (timeLeft <= 0) {
        clearInterval(timerInterval);
        timerEl.textContent = '00:00';
        document.getElementById('test-form').submit();
    }
}, 1000);

function goTo(idx) {
    document.getElementById('q-' + current).classList.remove('active');
    document.getElementById('nav-' + current).classList.remove('current');
    current = idx;
    document.getElementById('q-' + current).classList.add('active');
    document.getElementById('nav-' + current).classList.add('current');
    document.getElementById('btn-prev').disabled = current === 0;
    document.getElementById('btn-next').style.display = current === totalQ - 1 ? 'none' : 'inline-block';
    document.getElementById('btn-submit').style.display = current === totalQ - 1 ? 'inline-block' : 'none';
    window.scrollTo({top: 0, behavior: 'smooth'});
}

function nextQ() { if (current < totalQ - 1) goTo(current + 1); }
function prevQ() { if (current > 0) goTo(current - 1); }

function markAnswered(idx, radio) {
    answered.add(idx);
    document.getElementById('nav-' + idx).classList.add('answered');
    document.getElementById('answered-count').textContent = answered.size;
    document.getElementById('progress').style.width = (answered.size / totalQ * 100) + '%';
    // Highlight selected option
    const qBlock = document.getElementById('q-' + idx);
    qBlock.querySelectorAll('.option-label').forEach(l => l.classList.remove('selected'));
    radio.closest('.option-label').classList.add('selected');
}

function submitTest() {
    const unanswered = totalQ - answered.size;
    const msg = unanswered > 0
        ? `You have ${unanswered} unanswered question(s). Submit anyway?`
        : 'Submit the test? You cannot change answers after submission.';
    if (confirm(msg)) document.getElementById('test-form').submit();
}

// Init
goTo(0);
</script>
</body>
</html>

