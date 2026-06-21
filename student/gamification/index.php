<?php
require_once '../../includes/config.php';
requireLogin('student');

$uid = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if ($uid === 0) { header('Location: ../../index.php'); exit(); }

// Create tables
$conn->query("CREATE TABLE IF NOT EXISTS badges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(10) NOT NULL,
    description VARCHAR(255),
    color VARCHAR(20) DEFAULT '#3f51b5'
)");
$conn->query("CREATE TABLE IF NOT EXISTS user_badges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    badge_id INT NOT NULL,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user_badge (user_id, badge_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE
)");

// Seed badges
if ($conn->query("SELECT COUNT(*) as c FROM badges")->fetch_assoc()['c'] == 0) {
    $conn->query("INSERT INTO badges (name, icon, description, color) VALUES
        ('First Application','🚀','Applied for your first job','#1565c0'),
        ('Active Applicant','📋','Applied for 5+ jobs','#1976d2'),
        ('Job Hunter','🎯','Applied for 10+ jobs','#0288d1'),
        ('Test Taker','📝','Completed your first test','#7b1fa2'),
        ('Test Pro','🏆','Scored 80%+ in a test','#6a1b9a'),
        ('Coder','💻','Solved your first coding problem','#2e7d32'),
        ('Code Master','⚡','Solved 10+ coding problems','#1b5e20'),
        ('Forum Contributor','💬','Posted in the discussion forum','#e65100'),
        ('Shortlisted','⭐','Got shortlisted by a company','#f57f17'),
        ('Placed','🎉','Got selected by a company','#2e7d32'),
        ('Profile Complete','✅','Completed your profile','#00695c'),
        ('Resume Uploaded','📄','Uploaded your resume','#4a148c')
    ");
}

// Auto-award badges
$stA = $conn->prepare("SELECT COUNT(*) as c FROM applications WHERE student_id=?"); $stA->bind_param('i',$uid); $stA->execute(); $apps = (int)$stA->get_result()->fetch_assoc()['c']; $stA->close();
$stT = $conn->prepare("SELECT COUNT(*) as c FROM test_attempts WHERE student_id=? AND status='completed'"); $stT->bind_param('i',$uid); $stT->execute(); $tests = (int)$stT->get_result()->fetch_assoc()['c']; $stT->close();
$stTS = $conn->prepare("SELECT MAX(score/total_marks*100) as s FROM test_attempts WHERE student_id=? AND total_marks>0"); $stTS->bind_param('i',$uid); $stTS->execute(); $topScore = (float)($stTS->get_result()->fetch_assoc()['s'] ?? 0); $stTS->close();
$stC = $conn->prepare("SELECT COUNT(DISTINCT problem_id) as c FROM coding_submissions WHERE user_id=? AND status='accepted'"); $stC->bind_param('i',$uid); $stC->execute(); $coding = (int)$stC->get_result()->fetch_assoc()['c']; $stC->close();
$stF = $conn->prepare("SELECT COUNT(*) as c FROM forum_posts WHERE user_id=?"); $stF->bind_param('i',$uid); $stF->execute(); $forum = (int)$stF->get_result()->fetch_assoc()['c']; $stF->close();
$stSH = $conn->prepare("SELECT COUNT(*) as c FROM applications WHERE student_id=? AND status='shortlisted'"); $stSH->bind_param('i',$uid); $stSH->execute(); $shortlist = (int)$stSH->get_result()->fetch_assoc()['c']; $stSH->close();
$stSL = $conn->prepare("SELECT COUNT(*) as c FROM applications WHERE student_id=? AND status='selected'"); $stSL->bind_param('i',$uid); $stSL->execute(); $selected = (int)$stSL->get_result()->fetch_assoc()['c']; $stSL->close();
$stPR = $conn->prepare("SELECT cgpa, skills, phone, resume_path FROM student_profiles WHERE user_id=?"); $stPR->bind_param('i',$uid); $stPR->execute(); $profile = $stPR->get_result()->fetch_assoc(); $stPR->close();

$awardMap = [
    1  => $apps >= 1,
    2  => $apps >= 5,
    3  => $apps >= 10,
    4  => $tests >= 1,
    5  => $topScore >= 80,
    6  => $coding >= 1,
    7  => $coding >= 10,
    8  => $forum >= 1,
    9  => $shortlist >= 1,
    10 => $selected >= 1,
    11 => ($profile && $profile['cgpa'] && $profile['skills'] && $profile['phone']),
    12 => ($profile && !empty($profile['resume_path'])),
];
foreach ($awardMap as $bid => $cond) {
    if ($cond) {
        $stAw = $conn->prepare("INSERT IGNORE INTO user_badges (user_id, badge_id) VALUES (?,?)");
        $stAw->bind_param('ii', $uid, $bid); $stAw->execute(); $stAw->close();
    }
}

$stFR = $conn->prepare("SELECT COUNT(*) as c FROM forum_replies WHERE user_id=?"); $stFR->bind_param('i',$uid); $stFR->execute(); $forumReplies = (int)$stFR->get_result()->fetch_assoc()['c']; $stFR->close();
$stUB = $conn->prepare("SELECT COUNT(*) as c FROM user_badges WHERE user_id=?"); $stUB->bind_param('i',$uid); $stUB->execute(); $myBadgesCount = (int)$stUB->get_result()->fetch_assoc()['c']; $stUB->close();
$myPoints = $apps*5 + $tests*10 + $coding*15 + $forum*5 + $forumReplies*2 + $shortlist*20 + $selected*100 + $myBadgesCount*25;

$stMR = $conn->prepare("SELECT COUNT(*) as c FROM users u WHERE u.role='student' AND (
    (SELECT COUNT(*) FROM applications WHERE student_id=u.id)*5 +
    (SELECT COUNT(*) FROM test_attempts WHERE student_id=u.id AND status='completed')*10 +
    (SELECT COUNT(DISTINCT problem_id) FROM coding_submissions WHERE user_id=u.id AND status='accepted')*15 +
    (SELECT COUNT(*) FROM forum_posts WHERE user_id=u.id)*5 +
    (SELECT COUNT(*) FROM forum_replies WHERE user_id=u.id)*2 +
    (SELECT COUNT(*) FROM applications WHERE student_id=u.id AND status='shortlisted')*20 +
    (SELECT COUNT(*) FROM applications WHERE student_id=u.id AND status='selected')*100 +
    (SELECT COUNT(*) FROM user_badges WHERE user_id=u.id)*25
) > ?");
$stMR->bind_param('i', $myPoints); $stMR->execute();
$myRank = 1 + (int)$stMR->get_result()->fetch_assoc()['c']; $stMR->close();

$leaderboard = $conn->query("SELECT u.id, u.name, sp.department,
    (SELECT COUNT(*) FROM applications WHERE student_id=u.id)*5 +
    (SELECT COUNT(*) FROM test_attempts WHERE student_id=u.id AND status='completed')*10 +
    (SELECT COUNT(DISTINCT problem_id) FROM coding_submissions WHERE user_id=u.id AND status='accepted')*15 +
    (SELECT COUNT(*) FROM forum_posts WHERE user_id=u.id)*5 +
    (SELECT COUNT(*) FROM forum_replies WHERE user_id=u.id)*2 +
    (SELECT COUNT(*) FROM applications WHERE student_id=u.id AND status='shortlisted')*20 +
    (SELECT COUNT(*) FROM applications WHERE student_id=u.id AND status='selected')*100 +
    (SELECT COUNT(*) FROM user_badges WHERE user_id=u.id)*25 as total_points,
    (SELECT COUNT(*) FROM user_badges WHERE user_id=u.id) as badge_count
    FROM users u LEFT JOIN student_profiles sp ON u.id=sp.user_id
    WHERE u.role='student' GROUP BY u.id, u.name, sp.department ORDER BY total_points DESC LIMIT 20");

$stAB = $conn->prepare("SELECT b.*, (SELECT id FROM user_badges WHERE user_id=? AND badge_id=b.id LIMIT 1) as earned FROM badges b ORDER BY b.id");
$stAB->bind_param('i',$uid); $stAB->execute();
$allBadges = $stAB->get_result(); $stAB->close();
$stMBC = $conn->prepare("SELECT COUNT(*) as c FROM user_badges WHERE user_id=?");
$stMBC->bind_param('i',$uid); $stMBC->execute();
$myBadgeCount = (int)$stMBC->get_result()->fetch_assoc()['c']; $stMBC->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Achievements & Leaderboard</title>
<link rel="stylesheet" href="../../css/style.css">
<style>
.badge-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(155px,1fr));gap:12px}
.badge-card{border-radius:12px;padding:16px;text-align:center;border:2px solid #e0e0e0;transition:all 0.2s;position:relative}
.badge-card.earned{box-shadow:0 4px 14px rgba(0,0,0,0.1)}
.badge-card.locked{opacity:0.4;filter:grayscale(1)}
.lb-row{display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:8px;margin-bottom:7px;background:#f8f9ff}
.lb-row.me{background:linear-gradient(135deg,#e8eaf6,#c5cae9);border:2px solid #3f51b5}
</style>
</head>
<body>
<nav class="navbar">
    <a href="../dashboard.php" class="brand">🎓 Campus<span>Recruit</span></a>
    <div class="nav-links">
        <a href="../dashboard.php">Dashboard</a>
        <a href="index.php" class="active">🏆 Achievements</a>
        <?php require_once '../../notifications/widget.php'; ?>
        <a href="../logout.php" class="btn-logout">Logout</a>
    </div>
</nav>

<div class="container">
    <!-- Hero -->
    <div style="background:linear-gradient(135deg,#1a237e,#3949ab);color:#fff;border-radius:14px;padding:28px;text-align:center;margin-bottom:25px">
        <div style="font-size:0.95rem;color:#c5cae9;margin-bottom:5px">Your Total Points</div>
        <div style="font-size:3rem;font-weight:800;color:#ffd54f"><?= number_format($myPoints) ?></div>
        <div style="color:#c5cae9;margin-top:8px">🏅 Rank #<?= $myRank ?> &nbsp;·&nbsp; <?= $myBadgeCount ?> / 12 Badges Earned</div>
    </div>

    <!-- Points Guide -->
    <div class="card">
        <h2>📊 Points System</h2>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:10px">
            <?php foreach ([
                ['💼 Job Application','5 pts'],['📝 Test Completed','10 pts'],
                ['💻 Problem Solved','15 pts'],['💬 Forum Post','5 pts'],
                ['↩️ Forum Reply','2 pts'],['⭐ Shortlisted','20 pts'],
                ['🎉 Selected','100 pts'],['🏅 Badge Earned','25 pts'],
            ] as $b): ?>
            <div style="background:#f8f9ff;border-radius:8px;padding:11px;text-align:center">
                <div style="font-weight:700;color:#1a237e;font-size:0.85rem"><?= $b[0] ?></div>
                <div style="color:#3f51b5;font-size:0.82rem;margin-top:3px"><?= $b[1] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
        <!-- Badges -->
        <div class="card">
            <h2>🏅 Badges (<?= $myBadgeCount ?>/12)</h2>
            <div class="badge-grid">
                <?php while($b = $allBadges->fetch_assoc()): ?>
                <div class="badge-card <?= $b['earned'] ? 'earned' : 'locked' ?>"
                     style="<?= $b['earned'] ? 'border-color:'.htmlspecialchars($b['color']).';background:'.htmlspecialchars($b['color']).'18' : '' ?>">
                    <?php if ($b['earned']): ?>
                    <div style="position:absolute;top:7px;right:7px;background:#43a047;color:#fff;border-radius:50%;width:18px;height:18px;display:flex;align-items:center;justify-content:center;font-size:0.65rem;font-weight:700">✓</div>
                    <?php endif; ?>
                    <div style="font-size:2rem;margin-bottom:7px"><?= $b['icon'] ?></div>
                    <div style="font-weight:700;font-size:0.85rem;color:#1a237e;margin-bottom:3px"><?= htmlspecialchars($b['name']) ?></div>
                    <div style="font-size:0.73rem;color:#777;line-height:1.4"><?= htmlspecialchars($b['description']) ?></div>
                    <?php if (!$b['earned']): ?><div style="font-size:0.7rem;color:#bbb;margin-top:5px">🔒 Locked</div><?php endif; ?>
                </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Leaderboard -->
        <div class="card">
            <h2>🏆 Leaderboard</h2>
            <?php
            $rank = 0;
            while($l = $leaderboard->fetch_assoc()):
                $rank++;
                $medal = $rank === 1 ? '🥇' : ($rank === 2 ? '🥈' : ($rank === 3 ? '🥉' : '#'.$rank));
                $isMe  = $l['id'] == $uid;
            ?>
            <div class="lb-row <?= $isMe ? 'me' : '' ?>">
                <div style="font-size:<?= $rank<=3?'1.3rem':'1rem' ?>;font-weight:800;color:#1a237e;min-width:38px;text-align:center"><?= $medal ?></div>
                <div style="flex:1">
                    <div style="font-weight:700;color:#1a237e;font-size:0.88rem">
                        <?= htmlspecialchars($l['name']) ?>
                        <?php if ($isMe): ?><span style="background:#3f51b5;color:#fff;padding:1px 6px;border-radius:8px;font-size:0.68rem;margin-left:4px">You</span><?php endif; ?>
                    </div>
                    <div style="font-size:0.73rem;color:#999"><?= htmlspecialchars($l['department'] ?? 'N/A') ?> · <?= $l['badge_count'] ?> badges</div>
                </div>
                <div style="font-weight:800;color:#1a237e"><?= number_format($l['total_points']) ?> <span style="font-size:0.7rem;font-weight:400;color:#999">pts</span></div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>
<?php require_once '../../chatbot/widget.php'; ?>
</body>
</html>
