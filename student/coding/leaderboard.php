<?php
require_once '../../includes/config.php';
requireLogin('student');

$uid = $_SESSION['user_id'];

$leaders = $conn->query("SELECT u.id, u.name, sp.department, sp.cgpa,
    COUNT(DISTINCT cs.problem_id) as solved,
    COALESCE(SUM(cs.points_earned),0) as points,
    SUM(p.difficulty='easy') as easy_solved,
    SUM(p.difficulty='medium') as medium_solved,
    SUM(p.difficulty='hard') as hard_solved,
    MAX(cs.submitted_at) as last_submission
    FROM users u
    LEFT JOIN student_profiles sp ON u.id=sp.user_id
    LEFT JOIN coding_submissions cs ON u.id=cs.user_id AND cs.status='accepted'
    LEFT JOIN coding_problems p ON cs.problem_id=p.id
    WHERE u.role='student'
    GROUP BY u.id ORDER BY points DESC, solved DESC, last_submission ASC");

$totalProblems = $conn->query("SELECT COUNT(*) as c FROM coding_problems")->fetch_assoc()['c'];
$myPoints = $conn->query("SELECT COALESCE(SUM(points_earned),0) as p FROM coding_submissions WHERE user_id=$uid AND status='accepted'")->fetch_assoc()['p'];
$mySolved = $conn->query("SELECT COUNT(DISTINCT problem_id) as c FROM coding_submissions WHERE user_id=$uid AND status='accepted'")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Coding Leaderboard</title>
<link rel="stylesheet" href="../../css/style.css">
<style>
.lb-row { display:flex;align-items:center;gap:15px;padding:14px 18px;border-radius:10px;margin-bottom:8px;transition:all 0.2s; }
.lb-row:hover { transform:translateX(3px); }
.lb-row.me { background:linear-gradient(135deg,#e8f5e9,#f1f8e9);border:2px solid #a5d6a7; }
.lb-row.top1 { background:linear-gradient(135deg,#fff8e1,#fffde7);border:2px solid #ffd54f; }
.lb-row.top2 { background:linear-gradient(135deg,#f5f5f5,#fafafa);border:2px solid #bdbdbd; }
.lb-row.top3 { background:linear-gradient(135deg,#fbe9e7,#fff3e0);border:2px solid #ffab91; }
.lb-row.other { background:#fff;border:1px solid #e0e0e0; }
.rank-num { font-size:1.3rem;font-weight:800;min-width:40px;text-align:center; }
.diff-pill { display:inline-block;padding:2px 7px;border-radius:8px;font-size:0.72rem;font-weight:700;margin:1px; }
</style>
</head>
<body>
<nav class="navbar">
    <a href="../dashboard.php" class="brand">🎓 Campus<span>Recruit</span></a>
    <div class="nav-links">
        <a href="index.php">💻 Problems</a>
        <a href="leaderboard.php" class="active">🏆 Leaderboard</a>
        <?php require_once '../../notifications/widget.php'; ?>
        <a href="../logout.php" class="btn-logout">Logout</a>
    </div>
</nav>

<div class="container">
    <div class="card" style="background:linear-gradient(135deg,#1b5e20,#2e7d32);color:#fff;margin-bottom:25px">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:15px">
            <div>
                <h2 style="color:#ffd54f;margin-bottom:6px">🏆 Coding Leaderboard</h2>
                <p style="color:#c8e6c9">Top coders ranked by points earned from solving problems.</p>
            </div>
            <div style="display:flex;gap:20px">
                <div style="text-align:center"><div style="font-size:1.8rem;font-weight:800;color:#ffd54f"><?= $myPoints ?></div><div style="font-size:0.78rem;color:#a5d6a7">Your Points</div></div>
                <div style="text-align:center"><div style="font-size:1.8rem;font-weight:800;color:#69f0ae"><?= $mySolved ?>/<?= $totalProblems ?></div><div style="font-size:0.78rem;color:#a5d6a7">Solved</div></div>
            </div>
        </div>
    </div>

    <div class="card">
        <?php $rank=1; while($l = $leaders->fetch_assoc()):
            $isMe = $l['id'] == $uid;
            $rowClass = $isMe ? 'me' : ($rank===1?'top1':($rank===2?'top2':($rank===3?'top3':'other')));
            $rankIcon = $rank===1?'🥇':($rank===2?'🥈':($rank===3?'🥉':"#$rank"));
            $pct = $totalProblems > 0 ? round($l['solved']/$totalProblems*100) : 0;
        ?>
        <div class="lb-row <?= $rowClass ?>">
            <div class="rank-num"><?= $rankIcon ?></div>
            <div style="flex:1;min-width:0">
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                    <span style="font-weight:700;color:#1a237e;font-size:0.95rem"><?= htmlspecialchars($l['name']) ?></span>
                    <?php if ($isMe): ?><span style="background:#e8f5e9;color:#2e7d32;padding:1px 7px;border-radius:10px;font-size:0.72rem;font-weight:700">You</span><?php endif; ?>
                    <?php if ($l['points'] >= 100): ?><span style="font-size:0.9rem" title="100+ points">🔥</span><?php endif; ?>
                    <?php if ($l['hard_solved'] >= 3): ?><span style="font-size:0.9rem" title="3+ hard problems">💪</span><?php endif; ?>
                </div>
                <div style="font-size:0.78rem;color:#666;margin-top:2px">
                    <?php if ($l['department']): ?><?= htmlspecialchars($l['department']) ?> · <?php endif; ?>
                    <?= $l['solved'] ?> problems solved
                    <?php if ($l['last_submission']): ?> · Last: <?= date('d M', strtotime($l['last_submission'])) ?><?php endif; ?>
                </div>
                <div style="margin-top:5px;display:flex;gap:4px;flex-wrap:wrap">
                    <?php if ($l['easy_solved'] > 0): ?><span class="diff-pill" style="background:#e8f5e9;color:#2e7d32">🟢 <?= $l['easy_solved'] ?> Easy</span><?php endif; ?>
                    <?php if ($l['medium_solved'] > 0): ?><span class="diff-pill" style="background:#fff8e1;color:#e65100">🟡 <?= $l['medium_solved'] ?> Medium</span><?php endif; ?>
                    <?php if ($l['hard_solved'] > 0): ?><span class="diff-pill" style="background:#ffebee;color:#c62828">🔴 <?= $l['hard_solved'] ?> Hard</span><?php endif; ?>
                </div>
                <!-- Progress bar -->
                <div style="background:#e0e0e0;border-radius:4px;height:5px;margin-top:6px;max-width:200px">
                    <div style="height:5px;border-radius:4px;width:<?= $pct ?>%;background:<?= $rank===1?'#ffd700':($rank<=3?'#43a047':'#3f51b5') ?>"></div>
                </div>
            </div>
            <div style="text-align:right;flex-shrink:0">
                <div style="font-size:1.5rem;font-weight:800;color:#fb8c00"><?= $l['points'] ?></div>
                <div style="font-size:0.72rem;color:#999">points</div>
            </div>
        </div>
        <?php $rank++; endwhile; ?>
    </div>

    <div style="text-align:center;margin-top:10px">
        <a href="index.php" class="btn btn-primary">← Back to Problems</a>
    </div>
</div>

<?php require_once '../../chatbot/widget.php'; ?>
</body>
</html>
