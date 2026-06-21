<?php
require_once '../../includes/config.php';
requireLogin('student');

$uid = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if ($uid === 0) { header('Location: ../../index.php'); exit(); }

// Ensure tables and columns exist
$conn->query("CREATE TABLE IF NOT EXISTS coding_problems (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    difficulty ENUM('easy','medium','hard') DEFAULT 'easy',
    category VARCHAR(100) DEFAULT 'General',
    sample_input TEXT,
    sample_output TEXT,
    hints TEXT,
    tags VARCHAR(300),
    points INT DEFAULT 10,
    company_tag VARCHAR(100) DEFAULT NULL,
    year_asked YEAR DEFAULT NULL,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$conn->query("CREATE TABLE IF NOT EXISTS coding_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    problem_id INT NOT NULL,
    user_id INT NOT NULL,
    language VARCHAR(30),
    code TEXT,
    status ENUM('accepted','wrong','error','partial') DEFAULT 'wrong',
    points_earned INT DEFAULT 0,
    exec_time INT DEFAULT 0,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (problem_id) REFERENCES coding_problems(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");
$conn->query("CREATE TABLE IF NOT EXISTS coding_test_cases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    problem_id INT NOT NULL,
    input TEXT NOT NULL,
    expected_output TEXT NOT NULL,
    is_sample TINYINT DEFAULT 0,
    FOREIGN KEY (problem_id) REFERENCES coding_problems(id) ON DELETE CASCADE
)");
// Add any missing columns
$_cpCols = array_column($conn->query("SHOW COLUMNS FROM coding_problems")->fetch_all(MYSQLI_ASSOC), 'Field');
foreach (['hints TEXT','tags VARCHAR(300)','points INT DEFAULT 10','company_tag VARCHAR(100) DEFAULT NULL','year_asked YEAR DEFAULT NULL',"status ENUM('active','inactive') DEFAULT 'active'"] as $_ca) {
    $_cn = explode(' ', $_ca)[0];
    if (!in_array($_cn, $_cpCols)) $conn->query("ALTER TABLE coding_problems ADD COLUMN $_ca");
}
$_csCols = array_column($conn->query("SHOW COLUMNS FROM coding_submissions")->fetch_all(MYSQLI_ASSOC), 'Field');
if (!in_array('exec_time', $_csCols)) $conn->query("ALTER TABLE coding_submissions ADD COLUMN exec_time INT DEFAULT 0");



// Fetch shortlisted companies for this student
$shortlistedCompanies = [];
$scRes = $conn->prepare("SELECT DISTINCT c.company_name FROM applications a JOIN jobs j ON a.job_id=j.id JOIN companies c ON j.company_id=c.id WHERE a.student_id=? AND a.status IN ('shortlisted','selected')");
$scRes->bind_param('i', $uid); $scRes->execute();
$scResult = $scRes->get_result();
while ($sc = $scResult->fetch_assoc()) $shortlistedCompanies[] = $sc['company_name'];
$scRes->close();

// Fetch company practice questions for shortlisted companies
$companyQuestions = [];
if (!empty($shortlistedCompanies)) {
    $placeholders = implode(',', array_fill(0, count($shortlistedCompanies), '?'));
    $types = str_repeat('s', count($shortlistedCompanies));
    $stCQ = $conn->prepare("SELECT p.*, (SELECT COUNT(*) FROM coding_submissions WHERE problem_id=p.id AND user_id=? AND status='accepted') as solved FROM coding_problems p WHERE company_tag IN ($placeholders) ORDER BY company_tag, FIELD(difficulty,'easy','medium','hard')");
    $stCQ->bind_param('i'.$types, $uid, ...$shortlistedCompanies);
    $stCQ->execute();
    $cqResult = $stCQ->get_result();
    while ($cq = $cqResult->fetch_assoc()) $companyQuestions[$cq['company_tag']][] = $cq;
    $stCQ->close();
}


// Filters
$allowedDiff = ['easy','medium','hard'];
$diff   = isset($_GET['diff']) && in_array($_GET['diff'], $allowedDiff) ? $_GET['diff'] : 'all';
$cat    = isset($_GET['cat']) ? trim($_GET['cat']) : 'all';
$search = trim($_GET['search'] ?? '');

$validCats = [];
$stCatList = $conn->prepare("SELECT DISTINCT category FROM coding_problems ORDER BY category");
$stCatList->execute(); $catRes = $stCatList->get_result(); $stCatList->close();
while ($row = $catRes->fetch_assoc()) $validCats[] = $row['category'];
if ($cat !== 'all' && !in_array($cat, $validCats)) $cat = 'all';

$sql = "SELECT p.*, (SELECT COUNT(*) FROM coding_submissions WHERE problem_id=p.id AND user_id=? AND status='accepted') as solved,
    pr.id as round_id, pr.scheduled_at as round_start, pr.end_time as round_end
    FROM coding_problems p
    LEFT JOIN placement_rounds pr ON pr.coding_problem_id = p.id
    WHERE 1=1";
$bindTypes = 'i';
$bindVals  = [$uid];
if ($diff !== 'all') { $sql .= " AND difficulty=?"; $bindTypes .= 's'; $bindVals[] = $diff; }
if ($cat  !== 'all') { $sql .= " AND category=?";   $bindTypes .= 's'; $bindVals[] = $cat; }
if ($search !== '')  { $like = '%'.$search.'%'; $sql .= " AND (title LIKE ? OR tags LIKE ?)"; $bindTypes .= 'ss'; $bindVals[] = $like; $bindVals[] = $like; }
$sql .= " ORDER BY FIELD(difficulty,'easy','medium','hard'), id ASC";
$stProb = $conn->prepare($sql);
$stProb->bind_param($bindTypes, ...$bindVals);
$stProb->execute();
$problems = $stProb->get_result(); $stProb->close();

$stCats = $conn->prepare("SELECT DISTINCT category FROM coding_problems ORDER BY category");
$stCats->execute(); $categories = $stCats->get_result(); $stCats->close();

$stStats = $conn->prepare("SELECT COUNT(DISTINCT problem_id) as c FROM coding_submissions WHERE user_id=? AND status='accepted'");
$stStats->bind_param('i',$uid); $stStats->execute();
$solvedCount = (int)$stStats->get_result()->fetch_assoc()['c']; $stStats->close();

$stPts = $conn->prepare("SELECT COALESCE(SUM(points_earned),0) as p FROM coding_submissions WHERE user_id=? AND status='accepted'");
$stPts->bind_param('i',$uid); $stPts->execute();
$totalPoints = (int)$stPts->get_result()->fetch_assoc()['p']; $stPts->close();

$stRk = $conn->prepare("SELECT COUNT(*)+1 as r FROM (SELECT user_id, SUM(points_earned) as pts FROM coding_submissions WHERE status='accepted' GROUP BY user_id HAVING pts > ?) t");
$stRk->bind_param('i',$totalPoints); $stRk->execute();
$myRank = (int)$stRk->get_result()->fetch_assoc()['r']; $stRk->close();

$totalProblems = (int)$conn->query("SELECT COUNT(*) as c FROM coding_problems")->fetch_assoc()['c'];

$diffColors = ['easy'=>['#2e7d32','#e8f5e9'],'medium'=>['#e65100','#fff8e1'],'hard'=>['#c62828','#ffebee']];
$nowTs = time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Coding Practice</title>
<link rel="stylesheet" href="../../css/style.css">
<style>
.problem-card { background:#fff;border-radius:10px;padding:16px 20px;box-shadow:0 2px 8px rgba(0,0,0,0.07);margin-bottom:12px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;border-left:4px solid #e0e0e0;transition:all 0.2s;cursor:pointer; }
.problem-card:hover { box-shadow:0 4px 15px rgba(0,0,0,0.12);transform:translateY(-1px); }
.problem-card.solved { border-left-color:#43a047; }
.problem-card.easy   { border-left-color:#43a047; }
.problem-card.medium { border-left-color:#fb8c00; }
.problem-card.hard   { border-left-color:#e53935; }
.diff-badge { padding:3px 10px;border-radius:12px;font-size:0.78rem;font-weight:700; }
.tag-chip { display:inline-block;padding:2px 8px;background:#e8eaf6;color:#3f51b5;border-radius:10px;font-size:0.72rem;margin:2px; }
.filter-btn { padding:6px 16px;border-radius:20px;border:2px solid #e0e0e0;background:#fff;color:#555;font-weight:600;cursor:pointer;font-size:0.82rem;text-decoration:none;transition:all 0.2s;display:inline-block; }
.filter-btn:hover,.filter-btn.active { background:#3f51b5;color:#fff;border-color:#3f51b5; }
.leaderboard-row { display:flex;align-items:center;gap:12px;padding:10px 15px;border-radius:8px;margin-bottom:6px;background:#f8f9ff; }
.rank-badge { width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.85rem;flex-shrink:0; }
/* Question Detail Modal */
.q-modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:1000;align-items:center;justify-content:center;padding:20px}
.q-modal-overlay.open{display:flex}
.q-modal{background:#fff;border-radius:16px;width:700px;max-width:96vw;max-height:90vh;overflow-y:auto;box-shadow:0 12px 50px rgba(0,0,0,0.25);display:flex;flex-direction:column}
.q-modal-head{padding:20px 24px 16px;border-bottom:1px solid #e8eaf6;position:sticky;top:0;background:#fff;z-index:1;border-radius:16px 16px 0 0}
.q-modal-body{padding:20px 24px;flex:1}
.q-modal-foot{padding:14px 24px;border-top:1px solid #e8eaf6;display:flex;gap:10px;justify-content:flex-end;position:sticky;bottom:0;background:#fff;border-radius:0 0 16px 16px}
</style>
</head>
<body>
<?php require_once '../sidebar.php'; ?>
<div class="topbar">
    <div class="topbar-left">
        <button class="hamburger" onclick="toggleSidebar()">☰</button>
        <span class="page-title">💻 Coding Practice</span>
    </div>
    <div class="topbar-right"><?php require_once '../../notifications/widget.php'; ?></div>
</div>
<div class="main-content">
    <!-- Header -->
    <div class="card" style="background:linear-gradient(135deg,#1b5e20,#2e7d32);color:#fff;margin-bottom:25px">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:15px">
            <div>
                <h2 style="color:#ffd54f;margin-bottom:6px">💻 Coding Practice Platform</h2>
                <p style="color:#c8e6c9">Practice coding problems, improve your skills, and climb the leaderboard!</p>
            </div>
            <div style="display:flex;gap:20px;flex-wrap:wrap">
                <div style="text-align:center"><div style="font-size:1.8rem;font-weight:800;color:#69f0ae"><?= $solvedCount ?>/<?= $totalProblems ?></div><div style="font-size:0.78rem;color:#a5d6a7">Solved</div></div>
                <div style="text-align:center"><div style="font-size:1.8rem;font-weight:800;color:#ffd54f"><?= $totalPoints ?></div><div style="font-size:0.78rem;color:#a5d6a7">Points</div></div>
                <div style="text-align:center"><div style="font-size:1.8rem;font-weight:800;color:#fff">#<?= $myRank ?></div><div style="font-size:0.78rem;color:#a5d6a7">Rank</div></div>
            </div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px">
        <div>
            <!-- Filters -->
            <div class="card" style="padding:15px;margin-bottom:15px">
                <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="🔍 Search problems..." style="padding:7px 14px;border:1.5px solid #e0e0e0;border-radius:20px;font-size:0.88rem;outline:none;flex:1;min-width:150px">
                    <div style="display:flex;gap:6px;flex-wrap:wrap">
                        <a href="?diff=all" class="filter-btn <?= $diff==='all'?'active':'' ?>">All</a>
                        <a href="?diff=easy" class="filter-btn <?= $diff==='easy'?'active':'' ?>" style="<?= $diff==='easy'?'':'border-color:#43a047;color:#2e7d32' ?>">🟢 Easy</a>
                        <a href="?diff=medium" class="filter-btn <?= $diff==='medium'?'active':'' ?>" style="<?= $diff==='medium'?'':'border-color:#fb8c00;color:#e65100' ?>">🟡 Medium</a>
                        <a href="?diff=hard" class="filter-btn <?= $diff==='hard'?'active':'' ?>" style="<?= $diff==='hard'?'':'border-color:#e53935;color:#c62828' ?>">🔴 Hard</a>
                    </div>
                    <select name="cat" onchange="this.form.submit()" style="padding:7px 12px;border:1.5px solid #e0e0e0;border-radius:20px;font-size:0.85rem;outline:none">
                        <option value="all" <?= $cat==='all'?'selected':'' ?>>All Categories</option>
                        <?php while($c = $categories->fetch_assoc()): ?>
                        <option value="<?= $c['category'] ?>" <?= $cat===$c['category']?'selected':'' ?>><?= $c['category'] ?></option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">Search</button>
                </form>
            </div>

            <!-- Problem List -->
            <?php if ($problems->num_rows === 0): ?>
            <div class="card" style="text-align:center;padding:40px;color:#999">
                <div style="font-size:3rem;margin-bottom:10px">🔍</div>
                <p>No problems found. Try different filters.</p>
            </div>
            <?php else: ?>
            <?php $i=1; while($p = $problems->fetch_assoc()):
                $dc = $diffColors[$p['difficulty']];
                $tags = array_filter(array_map('trim', explode(',', $p['tags'] ?? '')));
                $hasSchedule   = !empty($p['round_start']);
                $startTs       = $hasSchedule ? strtotime($p['round_start']) : 0;
                $endTs         = ($hasSchedule && !empty($p['round_end'])) ? strtotime($p['round_end']) : PHP_INT_MAX;
                $isBeforeStart = $hasSchedule && $nowTs < $startTs;
                $isAfterEnd    = $hasSchedule && $nowTs > $endTs;
                $isOpen        = !$hasSchedule || ($nowTs >= $startTs && $nowTs <= $endTs);
            ?>
            <div class="problem-card <?= $p['difficulty'] ?> <?= $p['solved']?'solved':'' ?>" onclick="openQ(<?= $p['id'] ?>)" style="border-left-color:<?= $isAfterEnd?'#9e9e9e':($isBeforeStart?'#fb8c00':($p['solved']?'#43a047':'')) ?>">
                <div style="flex:1">
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:5px">
                        <span style="color:#999;font-size:0.82rem;min-width:30px">#<?= $i++ ?></span>
                        <span style="font-weight:700;color:#1a237e;font-size:1rem"><?= htmlspecialchars($p['title']) ?></span>
                        <?php if ($p['solved']): ?><span style="color:#2e7d32;font-size:0.85rem;font-weight:700">✅ Solved</span><?php endif; ?>
                        <?php if ($isAfterEnd): ?>
                            <span style="background:#f5f5f5;color:#9e9e9e;padding:2px 8px;border-radius:10px;font-size:0.75rem;font-weight:700">🔒 Closed</span>
                        <?php elseif ($isBeforeStart): ?>
                            <span style="background:#fff8e1;color:#e65100;padding:2px 8px;border-radius:10px;font-size:0.75rem;font-weight:700">⏳ Scheduled</span>
                        <?php elseif ($isOpen && $hasSchedule): ?>
                            <span style="background:#e8f5e9;color:#2e7d32;padding:2px 8px;border-radius:10px;font-size:0.75rem;font-weight:700">🟢 Open</span>
                        <?php endif; ?>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px">
                        <span class="diff-badge" style="background:<?= $dc[1] ?>;color:<?= $dc[0] ?>"><?= ucfirst($p['difficulty']) ?></span>
                        <span style="background:#f5f5f5;padding:2px 8px;border-radius:10px;font-size:0.78rem;color:#555"><?= htmlspecialchars($p['category']) ?></span>
                        <span style="color:#fb8c00;font-size:0.78rem;font-weight:700">⭐ <?= $p['points'] ?> pts</span>
                        <?php foreach (array_slice($tags,0,3) as $tag): ?>
                        <span class="tag-chip"><?= htmlspecialchars($tag) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($hasSchedule): ?>
                    <div style="font-size:0.78rem;margin-top:4px">
                        <?php if ($isBeforeStart): ?>
                            <span style="color:#e65100">⏳ Opens in: <strong id="cdc-<?= $p['id'] ?>"></strong></span>
                            &nbsp;·&nbsp; <span style="color:#888">Start: <?= date('d M, h:i A', $startTs) ?> → End: <?= date('d M, h:i A', $endTs) ?></span>
                            <script>startCdCode(<?= $p['id'] ?>, <?= $startTs ?>);</script>
                        <?php elseif ($isOpen): ?>
                            <span style="color:#2e7d32">🟢 Closes in: <strong id="cdc-<?= $p['id'] ?>"></strong></span>
                            &nbsp;·&nbsp; <span style="color:#888">End: <?= date('d M, h:i A', $endTs) ?></span>
                            <script>startCdCode(<?= $p['id'] ?>, <?= $endTs ?>);</script>
                        <?php else: ?>
                            <span style="color:#9e9e9e">🔒 Window closed on <?= date('d M, h:i A', $endTs) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php if ($isBeforeStart): ?>
                <button disabled class="btn btn-sm" onclick="event.stopPropagation()" style="background:#e0e0e0;color:#aaa;cursor:not-allowed;white-space:nowrap">🔒 Locked</button>
                <?php elseif ($isAfterEnd): ?>
                <span style="color:#9e9e9e;font-size:0.85rem">Closed</span>
                <?php else: ?>
                <a href="practice.php?id=<?= $p['id'] ?>" onclick="event.stopPropagation()" class="btn btn-sm <?= $p['solved']?'btn-success':'btn-primary' ?>" style="white-space:nowrap">
                    <?= $p['solved'] ? '🔄 Redo' : '▶ Solve' ?>
                </a>
                <?php endif; ?>
            </div>
            <?php endwhile; ?>
            <?php endif; ?>
        </div>

        <!-- Leaderboard Sidebar -->
        <div>
            <div class="card">
                <h2 style="font-size:1.05rem">🏆 Leaderboard</h2>
                <?php
                $leaders = $conn->query("SELECT u.name, u.id,
                    COUNT(DISTINCT cs.problem_id) as solved,
                    COALESCE(SUM(cs.points_earned),0) as points
                    FROM users u
                    LEFT JOIN coding_submissions cs ON u.id=cs.user_id AND cs.status='accepted'
                    WHERE u.role='student'
                    GROUP BY u.id ORDER BY points DESC, solved DESC LIMIT 10");
                $rank = 1;
                while($l = $leaders->fetch_assoc()):
                    $isMe = $l['id'] == $uid;
                    $rankColors = ['#ffd700','#c0c0c0','#cd7f32'];
                    $rankBg = $rank <= 3 ? $rankColors[$rank-1] : '#e8eaf6';
                    $rankColor = $rank <= 3 ? '#333' : '#3f51b5';
                ?>
                <div class="leaderboard-row" style="<?= $isMe?'background:#e8f5e9;border:1px solid #a5d6a7':'' ?>">
                    <div class="rank-badge" style="background:<?= $rankBg ?>;color:<?= $rankColor ?>">
                        <?= $rank <= 3 ? ['🥇','🥈','🥉'][$rank-1] : "#$rank" ?>
                    </div>
                    <div style="flex:1;min-width:0">
                        <div style="font-weight:700;font-size:0.88rem;color:#1a237e;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                            <?= htmlspecialchars($l['name']) ?> <?= $isMe?'(You)':'' ?>
                        </div>
                        <div style="font-size:0.75rem;color:#666"><?= $l['solved'] ?> solved</div>
                    </div>
                    <div style="font-weight:800;color:#fb8c00;font-size:0.9rem"><?= $l['points'] ?> pts</div>
                </div>
                <?php $rank++; endwhile; ?>
                <a href="leaderboard.php" style="display:block;text-align:center;margin-top:10px;font-size:0.85rem;color:#3f51b5;font-weight:600">View Full Leaderboard →</a>
            </div>

            <!-- Progress Card -->
            <div class="card">
                <h2 style="font-size:1.05rem">📊 Your Progress</h2>
                <?php
                $stBD = $conn->prepare("SELECT p.difficulty, COUNT(DISTINCT cs.problem_id) as solved, COUNT(DISTINCT p.id) as total FROM coding_problems p LEFT JOIN coding_submissions cs ON p.id=cs.problem_id AND cs.user_id=? AND cs.status='accepted' GROUP BY p.difficulty");
$stBD->bind_param('i',$uid); $stBD->execute();
$byDiff = $stBD->get_result(); $stBD->close();
                while($d = $byDiff->fetch_assoc()):
                    $pct = $d['total'] > 0 ? round($d['solved']/$d['total']*100) : 0;
                    $dc = $diffColors[$d['difficulty']];
                ?>
                <div style="margin-bottom:12px">
                    <div style="display:flex;justify-content:space-between;font-size:0.85rem;margin-bottom:4px">
                        <span style="font-weight:600;color:<?= $dc[0] ?>"><?= ucfirst($d['difficulty']) ?></span>
                        <span style="color:#666"><?= $d['solved'] ?>/<?= $d['total'] ?></span>
                    </div>
                    <div style="background:#e0e0e0;border-radius:5px;height:8px">
                        <div style="height:8px;border-radius:5px;width:<?= $pct ?>%;background:<?= $dc[0] ?>;transition:width 1s"></div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($companyQuestions)): ?>
<div class="card" style="margin-top:20px">
    <div style="background:linear-gradient(135deg,#4a148c,#7b1fa2);border-radius:10px;padding:18px 22px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
        <div>
            <h2 style="color:#ffd54f;margin:0;font-size:1.1rem">🏢 Previous Year Company Questions</h2>
            <p style="color:#e1bee7;font-size:0.82rem;margin:4px 0 0">Questions from companies you are shortlisted for</p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <?php foreach(array_keys($companyQuestions) as $cn): ?>
            <span style="background:rgba(255,255,255,0.15);color:#fff;padding:4px 12px;border-radius:20px;font-size:0.8rem;font-weight:700">🏢 <?= htmlspecialchars($cn) ?></span>
            <?php endforeach; ?>
        </div>
    </div>

    <?php foreach ($companyQuestions as $company => $cqs): ?>
    <div style="margin-bottom:24px">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;padding-bottom:8px;border-bottom:2px solid #f3e5f5">
            <span style="font-size:1.4rem">🏢</span>
            <h3 style="color:#4a148c;font-size:1rem;margin:0"><?= htmlspecialchars($company) ?></h3>
            <span style="background:#f3e5f5;color:#7b1fa2;padding:2px 10px;border-radius:10px;font-size:0.78rem;font-weight:700"><?= count($cqs) ?> Questions</span>
        </div>
        <?php foreach ($cqs as $p):
            $dc = $diffColors[$p['difficulty']];
            $tags = array_filter(array_map('trim', explode(',', $p['tags'] ?? '')));
        ?>
        <div class="problem-card <?= $p['difficulty'] ?> <?= $p['solved'] ? 'solved' : '' ?>" onclick="openQ(<?= $p['id'] ?>)" style="border-left-color:<?= $p['solved'] ? '#43a047' : '#7b1fa2' ?>">
            <div style="flex:1">
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:5px">
                    <a href="practice.php?id=<?= $p['id'] ?>" style="font-weight:700;color:#1a237e;font-size:0.95rem;text-decoration:none"><?= htmlspecialchars($p['title']) ?></a>
                    <?php if ($p['solved']): ?><span style="color:#2e7d32;font-size:0.82rem;font-weight:700">✅ Solved</span><?php endif; ?>
                    <?php if ($p['year_asked']): ?><span style="background:#f3e5f5;color:#7b1fa2;padding:1px 8px;border-radius:8px;font-size:0.75rem;font-weight:700">📅 <?= $p['year_asked'] ?></span><?php endif; ?>
                </div>
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                    <span class="diff-badge" style="background:<?= $dc[1] ?>;color:<?= $dc[0] ?>"><?= ucfirst($p['difficulty']) ?></span>
                    <span style="background:#f5f5f5;padding:2px 8px;border-radius:10px;font-size:0.78rem;color:#555"><?= htmlspecialchars($p['category']) ?></span>
                    <span style="color:#fb8c00;font-size:0.78rem;font-weight:700">⭐ <?= $p['points'] ?> pts</span>
                    <?php foreach (array_slice($tags,0,3) as $tag): ?>
                    <span class="tag-chip"><?= htmlspecialchars($tag) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <a href="practice.php?id=<?= $p['id'] ?>" onclick="event.stopPropagation()" class="btn btn-sm <?= $p['solved'] ? 'btn-success' : '' ?>" style="<?= $p['solved'] ? '' : 'background:#7b1fa2;color:#fff;' ?>white-space:nowrap">
                <?= $p['solved'] ? '🔄 Redo' : '▶ Solve' ?>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

</div><!-- app-layout -->
<?php require_once '../../chatbot/widget.php'; ?>

<!-- Question Detail Modal -->
<div class="q-modal-overlay" id="qModal" onclick="if(event.target===this)closeQ()">
    <div class="q-modal">
        <div class="q-modal-head">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px">
                <div style="flex:1">
                    <div style="font-size:1.15rem;font-weight:800;color:#1a237e;margin-bottom:6px" id="qm-title"></div>
                    <div style="display:flex;gap:6px;flex-wrap:wrap" id="qm-badges"></div>
                </div>
                <button onclick="closeQ()" style="background:none;border:none;font-size:1.4rem;cursor:pointer;color:#666;line-height:1;flex-shrink:0">&times;</button>
            </div>
        </div>
        <div class="q-modal-body">
            <div style="color:#333;font-size:0.93rem;line-height:1.75;white-space:pre-wrap;margin-bottom:18px" id="qm-desc"></div>
            <div id="qm-io" style="margin-bottom:16px"></div>
            <div id="qm-hint" style="display:none;background:#fff8e1;border-left:4px solid #fb8c00;border-radius:0 8px 8px 0;padding:10px 14px;font-size:0.88rem;color:#555;margin-bottom:12px"></div>
            <button id="qm-hint-btn" onclick="toggleHint()" style="background:#fff8e1;color:#e65100;border:1px solid #ffcc80;border-radius:6px;padding:5px 14px;cursor:pointer;font-size:0.82rem;font-weight:600">💡 Show Hint</button>
        </div>
        <div class="q-modal-foot">
            <button onclick="closeQ()" class="btn" style="background:#f5f5f5;color:#555;border:1px solid #ddd">Close</button>
            <a id="qm-solve-btn" href="#" class="btn btn-primary" style="padding:9px 28px">▶ Solve Now</a>
        </div>
    </div>
</div>

<?php
// Build all problems data for JS modal
$allProblemsRes = $conn->query("SELECT id,title,description,difficulty,category,sample_input,sample_output,hints,tags,points,company_tag,year_asked FROM coding_problems");
$allProblemsData = [];
while ($ap = $allProblemsRes->fetch_assoc()) $allProblemsData[$ap['id']] = $ap;
?>
<script>
const PROBLEMS = <?= json_encode($allProblemsData, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
const diffColors = {easy:['#2e7d32','#e8f5e9'],medium:['#e65100','#fff8e1'],hard:['#c62828','#ffebee']};
let currentHint = '';

function openQ(id) {
    const p = PROBLEMS[id];
    if (!p) return;

    document.getElementById('qm-title').textContent = p.title;

    // Badges
    const dc = diffColors[p.difficulty] || ['#555','#eee'];
    let badges = `<span style="background:${dc[1]};color:${dc[0]};padding:3px 10px;border-radius:12px;font-size:0.78rem;font-weight:700">${p.difficulty.charAt(0).toUpperCase()+p.difficulty.slice(1)}</span>`;
    badges += `<span style="background:#f5f5f5;padding:3px 9px;border-radius:10px;font-size:0.78rem;color:#555">${esc(p.category)}</span>`;
    badges += `<span style="color:#fb8c00;font-size:0.78rem;font-weight:700">&#11088; ${p.points} pts</span>`;
    if (p.company_tag) badges += `<span style="background:#f3e5f5;color:#7b1fa2;padding:3px 9px;border-radius:10px;font-size:0.78rem;font-weight:700">&#127970; ${esc(p.company_tag)}</span>`;
    if (p.year_asked)  badges += `<span style="background:#e3f2fd;color:#1565c0;padding:3px 9px;border-radius:10px;font-size:0.78rem;font-weight:700">&#128197; ${p.year_asked}</span>`;
    if (p.tags) {
        p.tags.split(',').slice(0,4).forEach(t => {
            t = t.trim();
            if (t) badges += `<span style="background:#e8eaf6;color:#3f51b5;padding:2px 8px;border-radius:10px;font-size:0.72rem;margin-top:2px">${esc(t)}</span>`;
        });
    }
    document.getElementById('qm-badges').innerHTML = badges;

    // Description
    document.getElementById('qm-desc').textContent = p.description;

    // Sample I/O
    let io = '';
    if (p.sample_input || p.sample_output) {
        io += `<div style="background:#f5f5f5;border-radius:8px;padding:14px">`;
        io += `<div style="font-weight:700;color:#1a237e;margin-bottom:8px;font-size:0.88rem">&#128203; Example</div>`;
        if (p.sample_input) io += `<div style="margin-bottom:8px"><div style="font-size:0.75rem;color:#666;font-weight:600;margin-bottom:3px">Input:</div><pre style="background:#e8e8e8;padding:8px;border-radius:5px;font-size:0.84rem;margin:0;overflow-x:auto">${esc(p.sample_input)}</pre></div>`;
        if (p.sample_output) io += `<div><div style="font-size:0.75rem;color:#666;font-weight:600;margin-bottom:3px">Output:</div><pre style="background:#e8e8e8;padding:8px;border-radius:5px;font-size:0.84rem;margin:0;overflow-x:auto">${esc(p.sample_output)}</pre></div>`;
        io += `</div>`;
    }
    document.getElementById('qm-io').innerHTML = io;

    // Hint
    currentHint = p.hints || '';
    const hintBtn = document.getElementById('qm-hint-btn');
    const hintBox = document.getElementById('qm-hint');
    hintBox.style.display = 'none';
    hintBox.textContent = currentHint;
    hintBtn.textContent = '&#128161; Show Hint';
    hintBtn.style.display = currentHint ? 'inline-block' : 'none';

    // Solve button
    document.getElementById('qm-solve-btn').href = 'practice.php?id=' + id;

    document.getElementById('qModal').classList.add('open');
}

function closeQ() {
    document.getElementById('qModal').classList.remove('open');
}

function toggleHint() {
    const box = document.getElementById('qm-hint');
    const btn = document.getElementById('qm-hint-btn');
    const open = box.style.display !== 'none';
    box.style.display = open ? 'none' : 'block';
    btn.innerHTML = open ? '&#128161; Show Hint' : '&#128161; Hide Hint';
}

function esc(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeQ(); });

function startCdCode(id, targetTs) {
    const el = document.getElementById('cdc-' + id);
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
