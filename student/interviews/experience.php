<?php
require_once '../../includes/config.php';
requireLogin('student');

$uid = $_SESSION['user_id'];

// Create table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS interview_experiences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    company_name VARCHAR(150) NOT NULL,
    job_role VARCHAR(150),
    interview_date DATE,
    difficulty ENUM('Easy','Medium','Hard') DEFAULT 'Medium',
    outcome ENUM('Selected','Rejected','Pending') DEFAULT 'Pending',
    rounds TEXT,
    experience TEXT NOT NULL,
    tips TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

$msg = '';

// Post experience
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'post') {
    $company   = $conn->real_escape_string(trim($_POST['company_name']));
    $role      = $conn->real_escape_string(trim($_POST['job_role']));
    $date      = $conn->real_escape_string($_POST['interview_date'] ?? '');
    $diff      = in_array($_POST['difficulty'], ['Easy','Medium','Hard']) ? $_POST['difficulty'] : 'Medium';
    $outcome   = in_array($_POST['outcome'], ['Selected','Rejected','Pending']) ? $_POST['outcome'] : 'Pending';
    $rounds    = $conn->real_escape_string(trim($_POST['rounds']));
    $exp       = $conn->real_escape_string(trim($_POST['experience']));
    $tips      = $conn->real_escape_string(trim($_POST['tips']));

    if ($company && $exp) {
        $conn->query("INSERT INTO interview_experiences (user_id, company_name, job_role, interview_date, difficulty, outcome, rounds, experience, tips)
            VALUES ($uid, '$company', '$role', " . ($date ? "'$date'" : "NULL") . ", '$diff', '$outcome', '$rounds', '$exp', '$tips')");
        $msg = '<div class="alert alert-success">✅ Your experience has been shared successfully!</div>';
    }
}

// Delete own experience
if (isset($_GET['delete'])) {
    $did = (int)$_GET['delete'];
    $conn->query("DELETE FROM interview_experiences WHERE id=$did AND user_id=$uid");
    header("Location: experience.php"); exit();
}

// Filter
$filter_company = $conn->real_escape_string(trim($_GET['company'] ?? ''));
$filter_outcome = $conn->real_escape_string(trim($_GET['outcome'] ?? ''));
$filter_diff    = $conn->real_escape_string(trim($_GET['difficulty'] ?? ''));

$where = "WHERE 1";
if ($filter_company) $where .= " AND ie.company_name LIKE '%$filter_company%'";
if ($filter_outcome) $where .= " AND ie.outcome='$filter_outcome'";
if ($filter_diff)    $where .= " AND ie.difficulty='$filter_diff'";

$experiences = $conn->query("SELECT ie.*, u.name as author
    FROM interview_experiences ie
    JOIN users u ON ie.user_id = u.id
    $where ORDER BY ie.created_at DESC");

$my_count    = $conn->query("SELECT COUNT(*) as c FROM interview_experiences WHERE user_id=$uid")->fetch_assoc()['c'];
$total_count = $conn->query("SELECT COUNT(*) as c FROM interview_experiences")->fetch_assoc()['c'];

$diff_colors    = ['Easy' => '#2e7d32', 'Medium' => '#e65100', 'Hard' => '#c62828'];
$diff_bg        = ['Easy' => '#e8f5e9', 'Medium' => '#fff8e1', 'Hard' => '#ffebee'];
$outcome_colors = ['Selected' => '#2e7d32', 'Rejected' => '#c62828', 'Pending' => '#1565c0'];
$outcome_bg     = ['Selected' => '#e8f5e9', 'Rejected' => '#ffebee', 'Pending' => '#e3f2fd'];
$outcome_icon   = ['Selected' => '✅', 'Rejected' => '❌', 'Pending' => '⏳'];

// Get unique companies for filter
$companies = $conn->query("SELECT DISTINCT company_name FROM interview_experiences ORDER BY company_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Interview Experiences</title>
<link rel="stylesheet" href="../../css/style.css">
<style>
.exp-layout { display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start; }
.exp-card {
    background: #fff; border-radius: 12px; padding: 22px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.07); margin-bottom: 16px;
    border-left: 5px solid #3f51b5; transition: transform 0.2s;
}
.exp-card:hover { transform: translateY(-2px); }
.badge-sm { display:inline-block; padding: 3px 10px; border-radius: 20px; font-size: 0.78rem; font-weight: 700; }
.round-tag { display:inline-block; background:#e8eaf6; color:#3f51b5; padding:3px 10px; border-radius:20px; font-size:0.78rem; font-weight:600; margin:2px; }
.stat-mini { background:#fff; border-radius:10px; padding:16px; text-align:center; box-shadow:0 2px 8px rgba(0,0,0,0.07); }
.stat-mini .num { font-size:1.8rem; font-weight:800; color:#1a237e; }
.stat-mini .lbl { font-size:0.78rem; color:#666; margin-top:3px; }
@media(max-width:900px) { .exp-layout { grid-template-columns: 1fr; } }
</style>
</head>
<body>
<nav class="navbar">
    <a href="../dashboard.php" class="brand">🎓 Campus<span>Recruit</span></a>
    <div class="nav-links">
        <a href="../dashboard.php">Dashboard</a>
        <a href="../jobs.php">Jobs</a>
        <a href="../applications.php">Applications</a>
        <a href="index.php">🎥 Interviews</a>
        <a href="experience.php" class="active">💬 Experiences</a>
        <a href="../logout.php" class="btn-logout">Logout</a>
    </div>
</nav>

<div class="container">
    <?= $msg ?>

    <!-- Header -->
    <div class="card" style="background:linear-gradient(135deg,#1a237e,#3949ab);color:#fff;margin-bottom:24px">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:15px">
            <div>
                <h2 style="color:#ffd54f;margin-bottom:6px">💬 Interview Experiences</h2>
                <p style="color:#c5cae9;font-size:0.9rem">Share your interview experience and help fellow students prepare better.</p>
            </div>
            <div style="display:flex;gap:15px">
                <div class="stat-mini">
                    <div class="num" style="color:#ffd54f"><?= $total_count ?></div>
                    <div class="lbl" style="color:#c5cae9">Total Shared</div>
                </div>
                <div class="stat-mini">
                    <div class="num" style="color:#69f0ae"><?= $my_count ?></div>
                    <div class="lbl" style="color:#c5cae9">My Shares</div>
                </div>
            </div>
        </div>
    </div>

    <div class="exp-layout">

        <!-- Left: Feed + Filters -->
        <div>
            <!-- Filters -->
            <form method="GET" style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap">
                <input type="text" name="company" placeholder="🔍 Search company..." value="<?= htmlspecialchars($filter_company) ?>"
                    style="flex:1;padding:9px 14px;border:1px solid #ddd;border-radius:8px;min-width:150px">
                <select name="outcome" style="padding:9px 14px;border:1px solid #ddd;border-radius:8px">
                    <option value="">All Outcomes</option>
                    <option value="Selected"  <?= $filter_outcome==='Selected' ?'selected':'' ?>>✅ Selected</option>
                    <option value="Rejected"  <?= $filter_outcome==='Rejected' ?'selected':'' ?>>❌ Rejected</option>
                    <option value="Pending"   <?= $filter_outcome==='Pending'  ?'selected':'' ?>>⏳ Pending</option>
                </select>
                <select name="difficulty" style="padding:9px 14px;border:1px solid #ddd;border-radius:8px">
                    <option value="">All Difficulty</option>
                    <option value="Easy"   <?= $filter_diff==='Easy'   ?'selected':'' ?>>🟢 Easy</option>
                    <option value="Medium" <?= $filter_diff==='Medium' ?'selected':'' ?>>🟡 Medium</option>
                    <option value="Hard"   <?= $filter_diff==='Hard'   ?'selected':'' ?>>🔴 Hard</option>
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="experience.php" class="btn btn-warning">Clear</a>
            </form>

            <!-- Experience Cards -->
            <?php if ($experiences->num_rows === 0): ?>
            <div class="card" style="text-align:center;padding:50px;color:#999">
                <div style="font-size:3.5rem;margin-bottom:12px">💬</div>
                <p>No experiences shared yet. Be the first to share!</p>
            </div>
            <?php else: ?>
            <?php while($e = $experiences->fetch_assoc()): ?>
            <div class="exp-card" style="border-left-color:<?= $outcome_colors[$e['outcome']] ?>">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px;margin-bottom:12px">
                    <div>
                        <h3 style="color:#1a237e;margin:0 0 6px"><?= htmlspecialchars($e['company_name']) ?></h3>
                        <?php if ($e['job_role']): ?>
                        <div style="color:#555;font-size:0.88rem;margin-bottom:6px">💼 <?= htmlspecialchars($e['job_role']) ?></div>
                        <?php endif; ?>
                        <div style="display:flex;gap:6px;flex-wrap:wrap">
                            <span class="badge-sm" style="background:<?= $outcome_bg[$e['outcome']] ?>;color:<?= $outcome_colors[$e['outcome']] ?>">
                                <?= $outcome_icon[$e['outcome']] ?> <?= $e['outcome'] ?>
                            </span>
                            <span class="badge-sm" style="background:<?= $diff_bg[$e['difficulty']] ?>;color:<?= $diff_colors[$e['difficulty']] ?>">
                                <?= $e['difficulty'] ?>
                            </span>
                            <?php if ($e['interview_date']): ?>
                            <span class="badge-sm" style="background:#f5f5f5;color:#666">
                                📅 <?= date('d M Y', strtotime($e['interview_date'])) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="text-align:right;font-size:0.78rem;color:#999">
                        <div>By <strong style="color:#3f51b5"><?= htmlspecialchars($e['author']) ?></strong></div>
                        <div><?= date('d M Y', strtotime($e['created_at'])) ?></div>
                        <?php if ($e['user_id'] == $uid): ?>
                        <a href="?delete=<?= $e['id'] ?>" onclick="return confirm('Delete this experience?')"
                            style="color:#e53935;font-size:0.78rem;font-weight:600">🗑️ Delete</a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($e['rounds']): ?>
                <div style="margin-bottom:10px">
                    <strong style="font-size:0.82rem;color:#555">Interview Rounds:</strong><br>
                    <?php foreach (explode(',', $e['rounds']) as $r): ?>
                    <span class="round-tag"><?= htmlspecialchars(trim($r)) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div style="color:#444;font-size:0.9rem;line-height:1.7;margin-bottom:10px">
                    <?= nl2br(htmlspecialchars($e['experience'])) ?>
                </div>

                <?php if ($e['tips']): ?>
                <div style="background:#e8f5e9;border-radius:8px;padding:10px 14px;font-size:0.87rem;color:#2e7d32;border-left:3px solid #43a047">
                    💡 <strong>Tips:</strong> <?= nl2br(htmlspecialchars($e['tips'])) ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endwhile; ?>
            <?php endif; ?>
        </div>

        <!-- Right: Share Form -->
        <div>
            <div class="card" style="position:sticky;top:80px">
                <h2 style="font-size:1.1rem;margin-bottom:16px">✍️ Share Your Experience</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="post">
                    <div class="form-group">
                        <label>Company Name *</label>
                        <input type="text" name="company_name" placeholder="e.g. TCS, Infosys" required>
                    </div>
                    <div class="form-group">
                        <label>Job Role</label>
                        <input type="text" name="job_role" placeholder="e.g. Software Engineer">
                    </div>
                    <div class="form-group">
                        <label>Interview Date</label>
                        <input type="date" name="interview_date">
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                        <div class="form-group">
                            <label>Difficulty</label>
                            <select name="difficulty">
                                <option value="Easy">🟢 Easy</option>
                                <option value="Medium" selected>🟡 Medium</option>
                                <option value="Hard">🔴 Hard</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Outcome</label>
                            <select name="outcome">
                                <option value="Selected">✅ Selected</option>
                                <option value="Rejected">❌ Rejected</option>
                                <option value="Pending" selected>⏳ Pending</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Interview Rounds <small style="color:#999">(comma separated)</small></label>
                        <input type="text" name="rounds" placeholder="e.g. Aptitude, Technical, HR">
                    </div>
                    <div class="form-group">
                        <label>Your Experience *</label>
                        <textarea name="experience" rows="5" placeholder="Describe the interview process, questions asked, environment..." required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Tips for Others</label>
                        <textarea name="tips" rows="3" placeholder="What would you advise others preparing for this company?"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%">📤 Share Experience</button>
                </form>
            </div>
        </div>

    </div>
</div>
<?php require_once '../../chatbot/widget.php'; ?>
</body>
</html>
