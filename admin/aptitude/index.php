<?php
require_once '../../includes/config.php';
requireLogin('admin');

// Create tables if not exist
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
$conn->query("CREATE TABLE IF NOT EXISTS test_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_id INT NOT NULL,
    question TEXT NOT NULL,
    option_a VARCHAR(255) NOT NULL,
    option_b VARCHAR(255) NOT NULL,
    option_c VARCHAR(255) NOT NULL,
    option_d VARCHAR(255) NOT NULL,
    correct_answer ENUM('a','b','c','d') NOT NULL,
    marks INT DEFAULT 1,
    FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE CASCADE
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
$conn->query("CREATE TABLE IF NOT EXISTS test_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attempt_id INT NOT NULL,
    question_id INT NOT NULL,
    selected_answer ENUM('a','b','c','d') NULL,
    is_correct TINYINT DEFAULT 0,
    FOREIGN KEY (attempt_id) REFERENCES test_attempts(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES test_questions(id) ON DELETE CASCADE
)");

$uid = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if ($uid === 0) { header('Location: ../../index.php'); exit(); }

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_test'])) {
        $title = trim($_POST['title'] ?? '');
        $desc  = trim($_POST['description'] ?? '');
        $cat   = trim($_POST['category'] ?? '');
        $dur   = (int)$_POST['duration'];
        $pass  = (int)$_POST['pass_marks'];
        $st = $conn->prepare("INSERT INTO tests (title,description,category,duration,pass_marks,created_by) VALUES (?,?,?,?,?,?)");
        $st->bind_param('sssiii', $title, $desc, $cat, $dur, $pass, $uid);
        $st->execute(); $st->close();
        $msg = '<div class="alert alert-success">Test created! Now add questions.</div>';
    }
    if (isset($_POST['toggle_status'])) {
        $id = (int)$_POST['test_id'];
        $stC = $conn->prepare("SELECT status FROM tests WHERE id=?");
        $stC->bind_param('i', $id); $stC->execute();
        $cur = $stC->get_result()->fetch_assoc(); $stC->close();
        $new = ($cur['status'] ?? '') === 'active' ? 'inactive' : 'active';
        $stU = $conn->prepare("UPDATE tests SET status=? WHERE id=?");
        $stU->bind_param('si', $new, $id); $stU->execute(); $stU->close();
        $msg = '<div class="alert alert-success">Test status updated.</div>';
    }
    if (isset($_POST['delete_test'])) {
        $id = (int)$_POST['test_id'];
        $st = $conn->prepare("DELETE FROM tests WHERE id=?");
        $st->bind_param('i', $id); $st->execute(); $st->close();
        $msg = '<div class="alert alert-success">Test deleted.</div>';
    }
}

$stT = $conn->prepare("SELECT t.*, (SELECT COUNT(*) FROM test_questions WHERE test_id=t.id) as q_count, (SELECT COUNT(*) FROM test_attempts WHERE test_id=t.id AND status='completed') as attempts FROM tests t ORDER BY t.created_at DESC");
$stT->execute(); $tests = $stT->get_result(); $stT->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Tests - Admin</title>
<link rel="stylesheet" href="../../css/style.css">
</head>
<body>
<?php require_once '../sidebar.php'; ?>
<div class="topbar">
    <div class="topbar-left">
        <button class="hamburger" onclick="toggleSidebar()">☰</button>
        <span class="page-title">📝 Manage Tests</span>
    </div>
    <div class="topbar-right"><?php require_once '../../notifications/widget.php'; ?></div>
</div>
<div class="main-content">
    <?= $msg ?>
    <div class="card">
        <h2>➕ Create New Test</h2>
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Test Title *</label>
                    <input type="text" name="title" placeholder="e.g. Quantitative Aptitude Test" required>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category">
                        <option value="aptitude">📊 Aptitude</option>
                        <option value="technical">💻 Technical MCQ</option>
                        <option value="coding">🖥️ Coding</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Duration (minutes)</label>
                    <input type="number" name="duration" value="30" min="5" max="180">
                </div>
                <div class="form-group">
                    <label>Pass Marks</label>
                    <input type="number" name="pass_marks" value="0" min="0">
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="2" placeholder="Brief description of the test..."></textarea>
            </div>
            <button type="submit" name="add_test" class="btn btn-primary">Create Test</button>
        </form>
    </div>

    <div class="card">
        <h2>All Tests</h2>
        <div class="table-wrap">
            <table>
                <tr><th>Title</th><th>Category</th><th>Duration</th><th>Questions</th><th>Pass Marks</th><th>Attempts</th><th>Status</th><th>Test Link</th><th>Actions</th></tr>
                <?php while($t = $tests->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($t['title']) ?></strong><br><small style="color:#999"><?= htmlspecialchars(substr($t['description'],0,50)) ?></small></td>
                    <td><?= ucfirst($t['category']) ?></td>
                    <td>⏱️ <?= $t['duration'] ?> min</td>
                    <td><?= $t['q_count'] ?> Qs</td>
                    <td><?= $t['pass_marks'] ?></td>
                    <td><?= $t['attempts'] ?></td>
                    <td><span class="badge <?= $t['status']==='active'?'badge-open':'badge-closed' ?>"><?= ucfirst($t['status']) ?></span></td>
                    <td>
                        <?php $link = 'http://'.$_SERVER['HTTP_HOST'].'/placement/student/aptitude_test/take_test.php?test_id='.$t['id']; ?>
                        <div style="display:flex;align-items:center;gap:6px">
                            <input type="text" value="<?= $link ?>" id="link-<?= $t['id'] ?>" readonly style="font-size:0.75rem;padding:4px 8px;border:1px solid #ddd;border-radius:5px;width:200px;color:#555;background:#f9f9f9">
                            <button onclick="copyLink(<?= $t['id'] ?>)" style="padding:4px 10px;background:#3f51b5;color:#fff;border:none;border-radius:5px;cursor:pointer;font-size:0.78rem;white-space:nowrap" id="copy-<?= $t['id'] ?>">Copy</button>
                        </div>
                    </td>
                    <td>
                        <div style="display:flex;gap:5px;flex-wrap:wrap">
                            <a href="questions.php?test_id=<?= $t['id'] ?>" class="btn btn-primary btn-sm">Questions</a>
                            <a href="results.php?test_id=<?= $t['id'] ?>" class="btn btn-warning btn-sm">Results</a>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="test_id" value="<?= $t['id'] ?>">
                                <button name="toggle_status" class="btn btn-sm" style="background:#607d8b;color:#fff"><?= $t['status']==='active'?'Deactivate':'Activate' ?></button>
                            </form>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Delete this test?')">
                                <input type="hidden" name="test_id" value="<?= $t['id'] ?>">
                                <button name="delete_test" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>
</div>
</div><!-- app-layout -->
<script>
function copyLink(id) {
    const input = document.getElementById('link-' + id);
    input.select(); input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value).then(() => {
        const btn = document.getElementById('copy-' + id);
        btn.textContent = 'Copied!';
        btn.style.background = '#43a047';
        setTimeout(() => { btn.textContent = 'Copy'; btn.style.background = '#3f51b5'; }, 2000);
    });
}
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('show');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('sidebarOverlay').classList.remove('show');}
</script>
</body>
</html>

