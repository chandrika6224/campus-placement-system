<?php
require_once '../../includes/config.php';
requireLogin('student');

$uid = $_SESSION['user_id'];
$attempt_id = (int)($_GET['attempt_id'] ?? 0);
if (!$attempt_id) { header("Location: index.php"); exit(); }

$attempt = $conn->query("SELECT ta.*, t.title, t.category, t.duration, t.pass_marks
    FROM test_attempts ta JOIN tests t ON ta.test_id=t.id
    WHERE ta.id=$attempt_id AND ta.student_id=$uid")->fetch_assoc();
if (!$attempt) { header("Location: index.php"); exit(); }

// Get placement round info if linked
$conn->query("CREATE TABLE IF NOT EXISTS round_eligible (
    id INT AUTO_INCREMENT PRIMARY KEY,
    round_id INT NOT NULL, student_id INT NOT NULL, eligible TINYINT(1) DEFAULT 1, marked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_re (round_id, student_id),
    FOREIGN KEY (round_id) REFERENCES placement_rounds(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
)");
$placementRound = $conn->query("SELECT pr.*, j.title as job_title, c.company_name
    FROM placement_rounds pr
    JOIN jobs j ON pr.job_id = j.id
    JOIN companies c ON j.company_id = c.id
    WHERE pr.test_id={$attempt['test_id']} LIMIT 1")->fetch_assoc();
$isPlacementRound = !empty($placementRound);

// Check if next round exists and if student is eligible
$nextRound = null;
$isEligible = null;
if ($isPlacementRound) {
    $job_id = (int)$placementRound['job_id'];
    $thisRoundId  = (int)$placementRound['id'];
    $thisRoundNum = (int)$placementRound['round_number'];
    $nextRound = $conn->query("SELECT * FROM placement_rounds WHERE job_id=$job_id AND round_number=" . ($thisRoundNum + 1) . " LIMIT 1")->fetch_assoc();

    // Priority 1: admin explicit eligibility
    $explicit = $conn->query("SELECT eligible FROM round_eligible WHERE round_id=$thisRoundId AND student_id=$uid LIMIT 1")->fetch_assoc();
    if ($explicit !== null) {
        $isEligible = (bool)$explicit['eligible'];
    } elseif (!empty($placementRound['min_pass_score']) && $placementRound['min_pass_score'] > 0) {
        // Priority 2: score-based cutoff set by admin
        $scoredPct = $attempt['total_marks'] > 0 ? round(($attempt['score'] / $attempt['total_marks']) * 100) : 0;
        $isEligible = $scoredPct >= (int)$placementRound['min_pass_score'];
    } elseif ($attempt['pass_marks'] > 0) {
        // Priority 3: pass_marks on the test
        $isEligible = $attempt['score'] >= $attempt['pass_marks'];
    }
    // else: $isEligible stays null = pending (admin must shortlist manually)
}

$answers = $conn->query("SELECT ta.*, tq.question, tq.option_a, tq.option_b, tq.option_c, tq.option_d, tq.correct_answer, tq.marks
    FROM test_answers ta JOIN test_questions tq ON ta.question_id=tq.id
    WHERE ta.attempt_id=$attempt_id ORDER BY tq.id ASC");

$pct = $attempt['total_marks'] > 0 ? round(($attempt['score'] / $attempt['total_marks']) * 100) : 0;
$passed = $attempt['score'] >= $attempt['pass_marks'];
$unanswered = $conn->query("SELECT COUNT(*) as c FROM test_answers WHERE attempt_id=$attempt_id AND selected_answer IS NULL")->fetch_assoc()['c'];

$optLabels = ['a'=>'A','b'=>'B','c'=>'C','d'=>'D'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Test Result</title>
<link rel="stylesheet" href="../../css/style.css">
<style>
.result-hero { background:linear-gradient(135deg,<?= $passed?'#1b5e20,#2e7d32':'#b71c1c,#c62828' ?>);color:#fff;border-radius:12px;padding:30px;text-align:center;margin-bottom:25px; }
.score-circle { width:130px;height:130px;border-radius:50%;border:8px solid rgba(255,255,255,0.4);display:flex;flex-direction:column;align-items:center;justify-content:center;margin:0 auto 15px;background:rgba(255,255,255,0.15); }
.ans-block { border-radius:10px;padding:16px;margin-bottom:14px;border-left:5px solid #e0e0e0; }
.ans-block.correct { border-left-color:#43a047;background:#f1f8e9; }
.ans-block.wrong { border-left-color:#e53935;background:#fff5f5; }
.ans-block.skipped { border-left-color:#fb8c00;background:#fff8e1; }
.opt-row { display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-top:10px; }
.opt-item { padding:7px 12px;border-radius:6px;font-size:0.88rem;background:#f5f5f5; }
.opt-item.correct-ans { background:#e8f5e9;color:#2e7d32;font-weight:700; }
.opt-item.wrong-ans { background:#ffebee;color:#c62828;font-weight:700; }
</style>
</head>
<body>
<?php require_once '../sidebar.php'; ?>
<div class="topbar">
    <div class="topbar-left">
        <button class="hamburger" onclick="toggleSidebar()">☰</button>
        <span class="page-title">Test Result</span>
    </div>
    <div class="topbar-right"><?php require_once '../../notifications/widget.php'; ?></div>
</div>
<div class="main-content">
    <!-- Result Hero -->
    <?php if ($isPlacementRound): ?>
    <?php
        $pending   = ($isEligible === null);
        $eligBg    = $pending ? 'linear-gradient(135deg,#1565c0,#1976d2)' : ($isEligible ? 'linear-gradient(135deg,#1b5e20,#2e7d32)' : 'linear-gradient(135deg,#b71c1c,#c62828)');
        $eligIcon  = $pending ? '⏳' : ($isEligible ? '🎉' : '😔');
        $eligTitle = $pending ? 'Test Submitted — Results Pending' : ($isEligible ? 'You are Eligible for the Next Round!' : 'Not Eligible for the Next Round');
        $eligMsg   = $pending
            ? 'Your responses have been recorded. Shortlisting will be done by the admin based on performance. You will be notified.'
            : ($isEligible ? 'Great performance! You have been shortlisted for the next round.' : 'Your score did not meet the cutoff for this round. Better luck next time!');
    ?>
    <div style="background:<?= $eligBg ?>;color:#fff;border-radius:12px;padding:32px;text-align:center;margin-bottom:20px">
        <div style="font-size:3.5rem;margin-bottom:12px"><?= $eligIcon ?></div>
        <h2 style="color:#fff;font-size:1.7rem;margin-bottom:6px"><?= $eligTitle ?></h2>
        <p style="color:rgba(255,255,255,0.85);margin-bottom:16px"><?= $eligMsg ?></p>

        <!-- Show marks -->
        <div style="display:inline-flex;gap:30px;background:rgba(0,0,0,0.15);border-radius:10px;padding:16px 28px;margin-bottom:16px;flex-wrap:wrap;justify-content:center">
            <div>
                <div style="font-size:2rem;font-weight:800"><?= $attempt['score'] ?>/<?= $attempt['total_marks'] ?></div>
                <div style="font-size:0.82rem;opacity:0.8">Your Score</div>
            </div>
            <div>
                <div style="font-size:2rem;font-weight:800"><?= $pct ?>%</div>
                <div style="font-size:0.82rem;opacity:0.8">Percentage</div>
            </div>
            <?php if ($attempt['pass_marks'] > 0): ?>
            <div>
                <div style="font-size:2rem;font-weight:800"><?= $attempt['pass_marks'] ?></div>
                <div style="font-size:0.82rem;opacity:0.8">Pass Marks</div>
            </div>
            <?php endif; ?>
            <?php if (!empty($placementRound['min_pass_score']) && $placementRound['min_pass_score'] > 0): ?>
            <div>
                <div style="font-size:2rem;font-weight:800"><?= $placementRound['min_pass_score'] ?>%</div>
                <div style="font-size:0.82rem;opacity:0.8">Cutoff %</div>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($isEligible && $nextRound): ?>
        <div style="background:rgba(255,255,255,0.15);border-radius:8px;padding:12px 20px;margin-bottom:14px;font-size:0.9rem">
            <strong>📋 Next Round:</strong> <?= htmlspecialchars($nextRound['round_name']) ?>
            <?php if ($nextRound['scheduled_at']): ?>
            &nbsp;·&nbsp; 🕐 <?= date('d M Y, h:i A', strtotime($nextRound['scheduled_at'])) ?>
            <?php endif; ?>
        </div>
        <?php elseif ($isEligible && !$nextRound): ?>
        <div style="background:rgba(255,255,255,0.15);border-radius:8px;padding:10px 20px;margin-bottom:14px;font-size:0.9rem">
            🏁 This was the final round. Results will be announced by admin.
        </div>
        <?php endif; ?>

        <div style="font-size:0.82rem;opacity:0.7">🕐 Submitted: <?= date('d M Y, h:i A', strtotime($attempt['completed_at'] ?? 'now')) ?></div>
    </div>
    <div style="text-align:center;margin-bottom:20px">
        <a href="index.php" class="btn btn-primary">← Back to Tests</a>
    </div>
    <?php else: ?>
    <!-- Normal result (non-placement) -->
    <div class="result-hero">
        <div class="score-circle">
            <span style="font-size:2.2rem;font-weight:800"><?= $pct ?>%</span>
            <span style="font-size:0.85rem;opacity:0.8">Score</span>
        </div>
        <h2 style="font-size:1.8rem;margin-bottom:5px"><?= $passed ? '🎉 Congratulations! You Passed!' : '😔 Better Luck Next Time' ?></h2>
        <p style="opacity:0.85;margin-bottom:15px"><?= htmlspecialchars($attempt['title']) ?></p>
        <div style="display:flex;justify-content:center;gap:30px;flex-wrap:wrap">
            <div><div style="font-size:1.8rem;font-weight:800"><?= $attempt['score'] ?>/<?= $attempt['total_marks'] ?></div><div style="opacity:0.75;font-size:0.85rem">Total Score</div></div>
            <div><div style="font-size:1.8rem;font-weight:800;color:#69f0ae"><?= $attempt['correct_answers'] ?></div><div style="opacity:0.75;font-size:0.85rem">Correct</div></div>
            <div><div style="font-size:1.8rem;font-weight:800;color:#ff8a80"><?= $attempt['wrong_answers'] ?></div><div style="opacity:0.75;font-size:0.85rem">Wrong</div></div>
            <div><div style="font-size:1.8rem;font-weight:800;color:#ffd54f"><?= $unanswered ?></div><div style="opacity:0.75;font-size:0.85rem">Skipped</div></div>
        </div>
        <?php if ($attempt['pass_marks'] > 0): ?>
        <div style="margin-top:15px;opacity:0.8;font-size:0.9rem">Pass Marks: <?= $attempt['pass_marks'] ?> | Your Score: <?= $attempt['score'] ?></div>
        <?php endif; ?>
    </div>

    <!-- Score Bar -->
    <div class="card">
        <h2>📊 Performance Summary</h2>
        <div style="margin-bottom:15px">
            <div style="display:flex;justify-content:space-between;font-size:0.9rem;margin-bottom:5px"><span>Score</span><span><?= $pct ?>%</span></div>
            <div style="background:#e0e0e0;border-radius:10px;height:14px"><div style="height:14px;border-radius:10px;background:<?= $pct>=60?'linear-gradient(90deg,#43a047,#66bb6a)':($pct>=40?'linear-gradient(90deg,#fb8c00,#ffa726)':'linear-gradient(90deg,#e53935,#ef5350)') ?>;width:<?= $pct ?>%;transition:width 1s"></div></div>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
            <a href="index.php" class="btn btn-primary">← Back to Tests</a>
            <a href="take_test.php?test_id=<?= $attempt['test_id'] ?>" class="btn btn-warning">Retake Test</a>
        </div>
    </div>

    <!-- Detailed Review -->
    <div class="card">
        <h2>📋 Detailed Answer Review</h2>
        <?php $i=1; while($a = $answers->fetch_assoc()):
            $isCorrect = $a['is_correct'];
            $skipped = is_null($a['selected_answer']);
            $cls = $skipped ? 'skipped' : ($isCorrect ? 'correct' : 'wrong');
        ?>
        <div class="ans-block <?= $cls ?>">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px">
                <div style="flex:1">
                    <div style="font-weight:700;color:#1a237e;margin-bottom:8px">Q<?= $i ?>. <?= htmlspecialchars($a['question']) ?></div>
                    <div class="opt-row">
                        <?php foreach (['a','b','c','d'] as $opt):
                            $isCorrectOpt = $opt === $a['correct_answer'];
                            $isSelected = $opt === $a['selected_answer'];
                            $cls2 = $isCorrectOpt ? 'correct-ans' : ($isSelected && !$isCorrect ? 'wrong-ans' : '');
                        ?>
                        <div class="opt-item <?= $cls2 ?>">
                            <?= $optLabels[$opt] ?>. <?= htmlspecialchars($a['option_'.$opt]) ?>
                            <?= $isCorrectOpt ? ' ✓' : '' ?>
                            <?= ($isSelected && !$isCorrect) ? ' ✗' : '' ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($skipped): ?>
                    <div style="margin-top:8px;font-size:0.85rem;color:#e65100">⚠️ Skipped — Correct: <?= strtoupper($a['correct_answer']) ?>. <?= htmlspecialchars($a['option_'.$a['correct_answer']]) ?></div>
                    <?php endif; ?>
                </div>
                <div style="text-align:center;min-width:60px">
                    <div style="font-size:1.5rem"><?= $skipped?'⏭️':($isCorrect?'✅':'❌') ?></div>
                    <div style="font-size:0.8rem;color:#666"><?= $isCorrect?'+'.$a['marks'].' mark':'0' ?></div>
                </div>
            </div>
        </div>
        <?php $i++; endwhile; ?>
    </div>
    <?php endif; ?>
</div>
</div><!-- app-layout -->
<?php require_once '../../chatbot/widget.php'; ?>
<script>
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('show');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('sidebarOverlay').classList.remove('show');}
</script>
</body>
</html>
