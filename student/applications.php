<?php
require_once '../includes/config.php';
requireLogin('student');
$uid = $_SESSION['user_id'];

// Ensure new columns exist on placement_rounds
$conn->query("ALTER TABLE placement_rounds ADD COLUMN IF NOT EXISTS test_id INT DEFAULT NULL");
$conn->query("ALTER TABLE placement_rounds ADD COLUMN IF NOT EXISTS end_time DATETIME DEFAULT NULL");
$conn->query("ALTER TABLE placement_rounds ADD COLUMN IF NOT EXISTS coding_problem_id INT DEFAULT NULL");
$conn->query("ALTER TABLE placement_rounds ADD COLUMN IF NOT EXISTS min_pass_score INT DEFAULT 0");

$stmtApps = $conn->prepare("SELECT a.*, j.title, j.location, j.job_type, j.salary_range, c.company_name
    FROM applications a JOIN jobs j ON a.job_id=j.id JOIN companies c ON j.company_id=c.id
    WHERE a.student_id=? ORDER BY a.applied_at DESC");
$stmtApps->bind_param('i', $uid);
$stmtApps->execute();
$apps = $stmtApps->get_result();
$stmtApps->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Applications</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php require_once 'sidebar.php'; ?>
<div class="topbar">
    <div class="topbar-left">
        <button class="hamburger" onclick="toggleSidebar()">☰</button>
        <span class="page-title">My Applications</span>
    </div>
    <div class="topbar-right"><?php require_once '../notifications/widget.php'; ?></div>
</div>
<div class="main-content">
    <div class="card">
        <h2>My Applications (<?= $apps->num_rows ?>)</h2>
        <?php if ($apps->num_rows === 0): ?>
        <p style="text-align:center;color:#999;padding:30px">No applications yet. <a href="jobs.php">Browse jobs</a></p>
        <?php else: ?>
        <style>
        .rounds-panel{background:#f8f9ff;border-radius:10px;padding:16px;margin-top:12px;border:1px solid #e8eaf6}
        .round-row{display:flex;align-items:flex-start;gap:12px;padding:10px 0;border-bottom:1px solid #e8eaf6}
        .round-row:last-child{border-bottom:none}
        .round-num{width:28px;height:28px;border-radius:50%;background:#1a237e;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.82rem;flex-shrink:0}
        .round-num.active{background:#43a047}
        .round-num.completed{background:#9e9e9e}
        .rlink{display:inline-block;padding:4px 12px;border-radius:10px;font-size:0.78rem;font-weight:700;text-decoration:none;margin-right:6px;margin-top:4px}
        </style>
        <?php
        require_once '../includes/round_gate.php';
        $apps_arr = [];
        while ($a = $apps->fetch_assoc()) $apps_arr[] = $a;
        foreach ($apps_arr as $a):
            $rounds = [];
            if ($a['status'] === 'shortlisted' || $a['status'] === 'selected') {
                $rRes = $conn->query("SELECT * FROM placement_rounds WHERE job_id={$a['job_id']} ORDER BY round_number");
                while ($r = $rRes->fetch_assoc()) $rounds[] = $r;
            }
        ?>
        <div style="background:#fff;border-radius:12px;padding:18px;margin-bottom:14px;box-shadow:0 2px 8px rgba(0,0,0,0.06);border-left:4px solid <?= $a['status']==='shortlisted'?'#fb8c00':($a['status']==='selected'?'#43a047':($a['status']==='rejected'?'#e53935':'#3f51b5')) ?>">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px">
                <div>
                    <div style="font-weight:800;color:#1a237e;font-size:1rem"><?= htmlspecialchars($a['title']) ?></div>
                    <div style="color:#555;font-size:0.85rem;margin-top:3px">🏢 <?= htmlspecialchars($a['company_name']) ?> · <?= $a['job_type'] ?> · 📍 <?= htmlspecialchars($a['location'] ?? '-') ?><?= $a['salary_range'] ? ' · 💰 '.htmlspecialchars($a['salary_range']) : '' ?></div>
                    <div style="font-size:0.8rem;color:#999;margin-top:2px">Applied: <?= date('d M Y', strtotime($a['applied_at'])) ?></div>
                </div>
                <span class="badge badge-<?= $a['status'] ?>" style="font-size:0.88rem;padding:5px 14px"><?= ucfirst($a['status']) ?></span>
            </div>

            <?php if ($a['status'] === 'shortlisted' || $a['status'] === 'selected'): ?>
            <div class="rounds-panel">
                <div style="font-weight:700;color:#1a237e;margin-bottom:10px;font-size:0.9rem">
                    🎯 Placement Rounds — <?= count($rounds) ?> Round<?= count($rounds)!=1?'s':'' ?> Configured
                </div>
                <?php if (empty($rounds)): ?>
                <p style="color:#999;font-size:0.85rem;margin:0">No rounds scheduled yet. Check back soon.</p>
                <?php else:
                    // Pre-compute each round's result for this student
                    $roundResults = [];
                    foreach ($rounds as $r) {
                        $res = ['attended'=>false,'score'=>0,'total'=>0,'pct'=>0,'status_label'=>'','passed'=>false];
                        if (!empty($r['test_id'])) {
                            $att = $conn->query("SELECT score, total_marks FROM test_attempts WHERE test_id={$r['test_id']} AND student_id=$uid AND status='completed' ORDER BY score DESC LIMIT 1")->fetch_assoc();
                            if ($att) {
                                $res['attended'] = true;
                                $res['score']    = (int)$att['score'];
                                $res['total']    = (int)$att['total_marks'];
                                $res['pct']      = $att['total_marks'] > 0 ? round($att['score']/$att['total_marks']*100) : 0;
                                $res['passed']   = $r['min_pass_score'] > 0 ? $res['pct'] >= $r['min_pass_score'] : true;
                                $res['status_label'] = $res['passed'] ? 'Passed' : 'Failed';
                            }
                        } elseif (!empty($r['coding_problem_id'])) {
                            $sub = $conn->query("SELECT status, points_earned FROM coding_submissions WHERE problem_id={$r['coding_problem_id']} AND user_id=$uid ORDER BY submitted_at DESC LIMIT 1")->fetch_assoc();
                            if ($sub) {
                                $res['attended'] = true;
                                $res['status_label'] = ucfirst($sub['status']);
                                $res['pct'] = match($sub['status']) { 'accepted'=>100, 'partial'=>50, default=>0 };
                                $res['passed'] = $r['min_pass_score'] > 0 ? $res['pct'] >= $r['min_pass_score'] : ($sub['status']==='accepted');
                            }
                        }
                        $roundResults[$r['id']] = $res;
                    }
                    foreach ($rounds as $idx => $r):
                        $res      = $roundResults[$r['id']];
                        $now      = time();
                        $startTs  = !empty($r['scheduled_at']) ? strtotime($r['scheduled_at']) : 0;
                        $endTs    = !empty($r['end_time'])     ? strtotime($r['end_time'])     : PHP_INT_MAX;
                        $isOpen   = $startTs && $now >= $startTs && $now <= $endTs;
                        $isFuture = $startTs && $now < $startTs;
                        $isClosed = $startTs && $now > $endTs;

                        // Next round eligibility
                        $nextRound   = $rounds[$idx+1] ?? null;
                        $gateResult  = $nextRound ? checkRoundGate($conn, $uid, $nextRound['id']) : null;

                        // Border color
                        $borderCol = '#e0e0e0';
                        if ($res['attended']) $borderCol = $res['passed'] ? '#43a047' : '#e53935';
                        elseif ($isOpen)      $borderCol = '#1565c0';
                        elseif ($isFuture)    $borderCol = '#fb8c00';
                ?>
                <div class="round-row" style="border-left:4px solid <?= $borderCol ?>;padding-left:12px;margin-bottom:10px;border-bottom:none">
                    <div class="round-num <?= $r['status'] ?>" style="background:<?= $res['attended']?'#3949ab':'#1a237e' ?>"><?= $r['round_number'] ?></div>
                    <div style="flex:1">
                        <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-bottom:4px">
                            <strong style="color:#1a237e;font-size:0.9rem"><?= htmlspecialchars($r['round_name']) ?></strong>
                            <span style="font-size:0.75rem;padding:2px 8px;border-radius:8px;font-weight:700;
                                background:<?= $r['status']==='active'?'#c8e6c9':($r['status']==='completed'?'#f5f5f5':'#fff8e1') ?>;
                                color:<?= $r['status']==='active'?'#1b5e20':($r['status']==='completed'?'#757575':'#e65100') ?>">
                                <?= ucfirst($r['status']) ?>
                            </span>
                            <?php if ($res['attended']): ?>
                                <span style="background:#e3f2fd;color:#1565c0;padding:2px 8px;border-radius:8px;font-size:0.75rem;font-weight:700">&#10003; Attended</span>
                            <?php elseif ($isOpen): ?>
                                <span style="background:#e3f2fd;color:#1565c0;padding:2px 8px;border-radius:8px;font-size:0.75rem;font-weight:700">🟢 Open Now</span>
                            <?php elseif ($isFuture): ?>
                                <span style="background:#fff8e1;color:#e65100;padding:2px 8px;border-radius:8px;font-size:0.75rem;font-weight:700">⏳ Upcoming</span>
                            <?php elseif ($isClosed && !$res['attended']): ?>
                                <span style="background:#f5f5f5;color:#9e9e9e;padding:2px 8px;border-radius:8px;font-size:0.75rem;font-weight:700">🔒 Missed</span>
                            <?php endif; ?>
                        </div>

                        <?php if ($r['scheduled_at']): ?>
                        <div style="font-size:0.78rem;color:#666;margin-bottom:4px">🕐 <?= date('d M Y, h:i A', strtotime($r['scheduled_at'])) ?><?= !empty($r['end_time']) ? ' → '.date('h:i A', strtotime($r['end_time'])) : '' ?></div>
                        <?php endif; ?>

                        <?php if ($r['description']): ?>
                        <div style="font-size:0.78rem;color:#666;margin-bottom:4px">📌 <?= htmlspecialchars($r['description']) ?></div>
                        <?php endif; ?>

                        <?php if ($res['attended']): ?>
                        <div style="background:<?= $res['passed']?'#e8f5e9':'#ffebee' ?>;border-radius:8px;padding:8px 12px;margin-top:6px;font-size:0.82rem">
                            <?php if (!empty($r['test_id'])): ?>
                            <strong>Score:</strong> <?= $res['score'] ?>/<?= $res['total'] ?> (<?= $res['pct'] ?>%)
                            <?php if ($r['min_pass_score'] > 0): ?> &nbsp;&middot;&nbsp; Required: <?= $r['min_pass_score'] ?>%<?php endif; ?>
                            <?php else: ?>
                            <strong>Result:</strong> <?= $res['status_label'] ?>
                            <?php if ($r['min_pass_score'] > 0): ?> &nbsp;&middot;&nbsp; Required: <?= $r['min_pass_score'] ?>%<?php endif; ?>
                            <?php endif; ?>
                            <?php if ($res['passed'] && $nextRound): ?>
                            &nbsp;&middot;&nbsp; <span style="color:#2e7d32;font-weight:700">🔓 Eligible for Round <?= $nextRound['round_number'] ?></span>
                            <?php elseif (!$res['passed'] && $r['min_pass_score'] > 0): ?>
                            &nbsp;&middot;&nbsp; <span style="color:#c62828;font-weight:700">🚫 Not eligible for next round</span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($nextRound && $res['attended'] && $res['passed'] && $gateResult && !$gateResult['pass']): ?>
                        <div style="background:#fff8e1;border-radius:6px;padding:6px 10px;margin-top:6px;font-size:0.78rem;color:#e65100">
                            ⚠️ Next round requires <?= $nextRound['round_name'] ?> — gate not yet cleared.
                        </div>
                        <?php endif; ?>

                        <div style="margin-top:6px;display:flex;gap:6px;flex-wrap:wrap">
                            <?php if (!empty($r['test_id']) && $isOpen && !$res['attended']): ?>
                            <a href="aptitude_test/take_test.php?test_id=<?= $r['test_id'] ?>" class="rlink" style="background:#e3f2fd;color:#1565c0">📝 Start Test</a>
                            <?php endif; ?>
                            <?php if (!empty($r['coding_problem_id']) && $isOpen && !$res['attended']): ?>
                            <a href="coding/practice.php?id=<?= $r['coding_problem_id'] ?>" class="rlink" style="background:#e8f5e9;color:#1b5e20">💻 Solve Problem</a>
                            <?php endif; ?>
                            <?php if ($r['test_link']): ?>
                            <a href="<?= htmlspecialchars($r['test_link']) ?>" target="_blank" class="rlink" style="background:#e3f2fd;color:#1565c0">📝 Take Test</a>
                            <?php endif; ?>
                            <?php if ($r['meeting_link']): ?>
                            <a href="<?= htmlspecialchars($r['meeting_link']) ?>" target="_blank" class="rlink" style="background:#e8f5e9;color:#1b5e20">🎥 Join Interview</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
</div><!-- app-layout -->
<script>
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('show');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('sidebarOverlay').classList.remove('show');}
</script>
</body>
</html>
