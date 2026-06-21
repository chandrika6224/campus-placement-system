<?php
require_once '../../includes/config.php';
requireLogin('admin');

$conn->query("CREATE TABLE IF NOT EXISTS coding_test_cases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    problem_id INT NOT NULL,
    input TEXT NOT NULL,
    expected_output TEXT NOT NULL,
    is_sample TINYINT DEFAULT 0,
    FOREIGN KEY (problem_id) REFERENCES coding_problems(id) ON DELETE CASCADE
)");

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_problem'])) {
        $title  = sanitize($_POST['title']);
        $desc   = $conn->real_escape_string($_POST['description']);
        $diff   = sanitize($_POST['difficulty']);
        $cat    = sanitize($_POST['category']);
        $si     = $conn->real_escape_string($_POST['sample_input']);
        $so     = $conn->real_escape_string($_POST['sample_output']);
        $hints  = $conn->real_escape_string($_POST['hints']);
        $tags   = sanitize($_POST['tags']);
        $points = (int)$_POST['points'];
        $conn->query("INSERT INTO coding_problems (title,description,difficulty,category,sample_input,sample_output,hints,tags,points)
            VALUES ('$title','$desc','$diff','$cat','$si','$so','$hints','$tags',$points)");
        $pid = $conn->insert_id;
        // Save test cases
        $tc_inputs  = $_POST['tc_input']  ?? [];
        $tc_outputs = $_POST['tc_output'] ?? [];
        $tc_samples = $_POST['tc_sample'] ?? [];
        $stTC = $conn->prepare("INSERT INTO coding_test_cases (problem_id, input, expected_output, is_sample) VALUES (?,?,?,?)");
        foreach ($tc_inputs as $i => $tci) {
            $tci = trim($tci); $tco = trim($tc_outputs[$i] ?? '');
            if ($tci === '' && $tco === '') continue;
            $is_sample = isset($tc_samples[$i]) ? 1 : 0;
            $stTC->bind_param('issi', $pid, $tci, $tco, $is_sample); $stTC->execute();
        }
        $stTC->close();
        $msg = '<div class="alert alert-success">Problem added with test cases!</div>';
    }
    if (isset($_POST['delete_problem'])) {
        $id = (int)$_POST['problem_id'];
        $conn->query("DELETE FROM coding_problems WHERE id=$id");
        $msg = '<div class="alert alert-success">Problem deleted.</div>';
    }
    if (isset($_POST['add_testcase'])) {
        $pid = (int)$_POST['problem_id'];
        $tci = trim($_POST['tc_input'] ?? '');
        $tco = trim($_POST['tc_output'] ?? '');
        $is_sample = isset($_POST['is_sample']) ? 1 : 0;
        $stTC = $conn->prepare("INSERT INTO coding_test_cases (problem_id, input, expected_output, is_sample) VALUES (?,?,?,?)");
        $stTC->bind_param('issi', $pid, $tci, $tco, $is_sample); $stTC->execute(); $stTC->close();
        $msg = '<div class="alert alert-success">Test case added!</div>';
    }
    if (isset($_POST['delete_testcase'])) {
        $tcid = (int)$_POST['tc_id'];
        $conn->query("DELETE FROM coding_test_cases WHERE id=$tcid");
        $msg = '<div class="alert alert-success">Test case deleted.</div>';
    }
}

$problems = $conn->query("SELECT p.*,
    (SELECT COUNT(DISTINCT user_id) FROM coding_submissions WHERE problem_id=p.id AND status='accepted') as solvers
    FROM coding_problems p ORDER BY FIELD(difficulty,'easy','medium','hard'), id ASC");

$stats = [
    'total'       => $conn->query("SELECT COUNT(*) as c FROM coding_problems")->fetch_assoc()['c'],
    'submissions' => $conn->query("SELECT COUNT(*) as c FROM coding_submissions")->fetch_assoc()['c'],
    'accepted'    => $conn->query("SELECT COUNT(*) as c FROM coding_submissions WHERE status='accepted'")->fetch_assoc()['c'],
    'students'    => $conn->query("SELECT COUNT(DISTINCT user_id) as c FROM coding_submissions")->fetch_assoc()['c'],
];
$diffColors = ['easy'=>['#2e7d32','#e8f5e9'],'medium'=>['#e65100','#fff8e1'],'hard'=>['#c62828','#ffebee']];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Coding Problems - Admin</title>
<link rel="stylesheet" href="../../css/style.css">
<style>
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:1000;align-items:center;justify-content:center}
.modal-overlay.open{display:flex}
.modal-box{background:#fff;border-radius:14px;width:820px;max-width:96vw;max-height:92vh;overflow-y:auto;box-shadow:0 8px 40px rgba(0,0,0,0.25)}
.modal-header{padding:16px 22px 12px;border-bottom:1px solid #e8eaf6;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;background:#fff;z-index:10}
.modal-close{background:none;border:none;font-size:1.4rem;cursor:pointer;color:#666}
.sub-card{background:#f8f9ff;border-radius:8px;padding:14px;margin-bottom:12px;border-left:4px solid #3f51b5}
.sub-card.accepted{border-left-color:#43a047}
.sub-card.wrong{border-left-color:#e53935}
.sub-card.partial{border-left-color:#fb8c00}
.sub-card.error{border-left-color:#9e9e9e}
pre.code-view{background:#282a36;color:#f8f8f2;padding:14px;border-radius:8px;font-size:0.82rem;overflow-x:auto;max-height:300px;overflow-y:auto;margin:8px 0 0}
</style>
</head>
<body>
<?php require_once '../sidebar.php'; ?>
<div class="topbar">
    <div class="topbar-left">
        <button class="hamburger" onclick="toggleSidebar()">☰</button>
        <span class="page-title">💻 Coding Problems</span>
    </div>
    <div class="topbar-right"><?php require_once '../../notifications/widget.php'; ?></div>
</div>
<div class="main-content">
    <?= $msg ?>

    <div class="card" style="background:linear-gradient(135deg,#1b5e20,#2e7d32);color:#fff;margin-bottom:25px">
        <h2 style="color:#ffd54f;margin-bottom:8px">💻 Coding Practice — Admin</h2>
        <p style="color:#c8e6c9">Manage coding problems for student practice.</p>
    </div>

    <div class="stats-grid" style="grid-template-columns:repeat(4,1fr)">
        <div class="stat-card"><div class="number"><?= $stats['total'] ?></div><div class="label">💻 Total Problems</div></div>
        <div class="stat-card orange"><div class="number"><?= $stats['submissions'] ?></div><div class="label">📤 Submissions</div></div>
        <div class="stat-card green"><div class="number"><?= $stats['accepted'] ?></div><div class="label">✅ Accepted</div></div>
        <div class="stat-card"><div class="number"><?= $stats['students'] ?></div><div class="label">👨🎓 Active Students</div></div>
    </div>

    <!-- Add Problem -->
    <div class="card">
        <h2>➕ Add New Problem</h2>
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Title *</label>
                    <input type="text" name="title" placeholder="e.g. Two Sum" required>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <input type="text" name="category" placeholder="e.g. Arrays, Strings, DP" value="General">
                </div>
            </div>
            <div class="form-group">
                <label>Description *</label>
                <textarea name="description" rows="5" placeholder="Problem statement with input/output format..." required></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Sample Input</label>
                    <textarea name="sample_input" rows="3" placeholder="Sample input..."></textarea>
                </div>
                <div class="form-group">
                    <label>Expected Output</label>
                    <textarea name="sample_output" rows="3" placeholder="Expected output..."></textarea>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Difficulty</label>
                    <select name="difficulty">
                        <option value="easy">🟢 Easy</option>
                        <option value="medium">🟡 Medium</option>
                        <option value="hard">🔴 Hard</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Points</label>
                    <select name="points">
                        <option value="5">5 pts (Very Easy)</option>
                        <option value="10" selected>10 pts (Easy)</option>
                        <option value="20">20 pts (Medium)</option>
                        <option value="30">30 pts (Hard)</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Tags <small style="color:#999">(comma separated)</small></label>
                    <input type="text" name="tags" placeholder="e.g. arrays,hashing,two-pointers">
                </div>
                <div class="form-group">
                    <label>Hints</label>
                    <input type="text" name="hints" placeholder="Optional hint for students...">
                </div>
            </div>
            <button type="submit" name="add_problem" class="btn btn-success">➕ Add Problem</button>
        </form>
    </div>

    <!-- Problems List -->
    <div class="card">
        <h2>All Problems (<?= $stats['total'] ?>)</h2>
        <div class="table-wrap">
            <table>
                <tr><th>#</th><th>Title</th><th>Category</th><th>Difficulty</th><th>Points</th><th>Solvers</th><th>Test Cases</th><th>Tags</th><th>Action</th></tr>
                <?php while($p = $problems->fetch_assoc()):
                    $dc = $diffColors[$p['difficulty']];
                    $tcCount = (int)$conn->query("SELECT COUNT(*) as c FROM coding_test_cases WHERE problem_id={$p['id']}")->fetch_assoc()['c'];
                ?>
                <tr>
                    <td><?= $p['id'] ?></td>
                    <td><strong><?= htmlspecialchars($p['title']) ?></strong></td>
                    <td><?= htmlspecialchars($p['category']) ?></td>
                    <td><span style="background:<?= $dc[1] ?>;color:<?= $dc[0] ?>;padding:2px 8px;border-radius:10px;font-size:0.78rem;font-weight:700"><?= ucfirst($p['difficulty']) ?></span></td>
                    <td>⭐ <?= $p['points'] ?></td>
                    <td><?= $p['solvers'] ?> students</td>
                    <td>
                        <span style="color:<?= $tcCount>0?'#2e7d32':'#c62828' ?>;font-weight:700"><?= $tcCount ?> cases</span>
                        <button onclick="toggleTC(<?= $p['id'] ?>)" style="background:#e8eaf6;border:none;border-radius:5px;padding:2px 8px;cursor:pointer;font-size:0.78rem;margin-left:4px">Manage</button>
                    </td>
                    <td style="font-size:0.78rem;color:#666;max-width:150px"><?= htmlspecialchars(substr($p['tags'] ?? '',0,40)) ?></td>
                    <td>
                        <div style="display:flex;gap:5px;flex-wrap:wrap">
                            <button onclick="viewSubmissions(<?= $p['id'] ?>, <?= htmlspecialchars(json_encode($p['title']), ENT_QUOTES) ?>)" class="btn btn-primary btn-sm">👁 Submissions</button>
                            <form method="POST" onsubmit="return confirm('Delete this problem?')" style="display:inline">
                                <input type="hidden" name="problem_id" value="<?= $p['id'] ?>">
                                <button name="delete_problem" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <!-- Test Cases Panel -->
                <tr id="tc-panel-<?= $p['id'] ?>" style="display:none">
                    <td colspan="9" style="background:#f8f9ff;padding:16px">
                        <div style="font-weight:700;color:#1a237e;margin-bottom:10px">🧪 Test Cases for: <?= htmlspecialchars($p['title']) ?></div>
                        <?php
                        $tcs = $conn->query("SELECT * FROM coding_test_cases WHERE problem_id={$p['id']} ORDER BY id");
                        if ($tcs->num_rows > 0):
                        ?>
                        <table style="width:100%;margin-bottom:12px;font-size:0.82rem">
                            <tr style="background:#e8eaf6"><th style="padding:6px 10px">Input</th><th style="padding:6px 10px">Expected Output</th><th style="padding:6px 10px">Sample?</th><th style="padding:6px 10px">Action</th></tr>
                            <?php while($tc = $tcs->fetch_assoc()): ?>
                            <tr style="border-bottom:1px solid #eee">
                                <td style="padding:6px 10px"><pre style="margin:0;font-size:0.8rem"><?= htmlspecialchars($tc['input']) ?></pre></td>
                                <td style="padding:6px 10px"><pre style="margin:0;font-size:0.8rem"><?= htmlspecialchars($tc['expected_output']) ?></pre></td>
                                <td style="padding:6px 10px"><?= $tc['is_sample'] ? '✅ Visible' : '🔒 Hidden' ?></td>
                                <td style="padding:6px 10px">
                                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete test case?')">
                                        <input type="hidden" name="tc_id" value="<?= $tc['id'] ?>">
                                        <button name="delete_testcase" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </table>
                        <?php else: ?>
                        <p style="color:#999;font-size:0.85rem">No test cases yet.</p>
                        <?php endif; ?>
                        <!-- Add test case -->
                        <form method="POST" style="display:flex;gap:10px;align-items:flex-start;flex-wrap:wrap">
                            <input type="hidden" name="problem_id" value="<?= $p['id'] ?>">
                            <div>
                                <label style="font-size:0.78rem;font-weight:600;color:#555">Input</label>
                                <textarea name="tc_input" rows="3" style="width:200px;padding:5px 8px;border:1px solid #ddd;border-radius:5px;font-family:monospace;font-size:0.82rem;display:block"></textarea>
                            </div>
                            <div>
                                <label style="font-size:0.78rem;font-weight:600;color:#555">Expected Output</label>
                                <textarea name="tc_output" rows="3" style="width:200px;padding:5px 8px;border:1px solid #ddd;border-radius:5px;font-family:monospace;font-size:0.82rem;display:block"></textarea>
                            </div>
                            <div style="padding-top:18px">
                                <label style="font-size:0.78rem;font-weight:600;color:#555;display:flex;align-items:center;gap:6px;margin-bottom:8px">
                                    <input type="checkbox" name="is_sample"> Visible to student?
                                </label>
                                <button name="add_testcase" class="btn btn-primary btn-sm">Add Test Case</button>
                            </div>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>
</div>
</div><!-- app-layout -->

<!-- Submissions Modal -->
<div class="modal-overlay" id="subModal" onclick="if(event.target===this)closeModal()">
    <div class="modal-box">
        <div class="modal-header">
            <div>
                <div style="font-weight:800;color:#1a237e;font-size:1rem" id="modal-title"></div>
                <div style="font-size:0.8rem;color:#888" id="modal-sub"></div>
            </div>
            <button class="modal-close" onclick="closeModal()">&#x2715;</button>
        </div>
        <div style="padding:18px 22px" id="modal-body">
            <div style="text-align:center;color:#999;padding:30px">⏳ Loading...</div>
        </div>
    </div>
</div>

<?php require_once '../../chatbot/widget.php'; ?>
<script>
function viewSubmissions(pid, title) {
    document.getElementById('modal-title').textContent = '👁 Submissions — ' + title;
    document.getElementById('modal-sub').textContent = 'Loading...';
    document.getElementById('modal-body').innerHTML = '<div style="text-align:center;padding:30px;color:#999">⏳ Loading...</div>';
    document.getElementById('subModal').classList.add('open');

    fetch('submissions.php?problem_id=' + pid)
        .then(r => r.json())
        .then(data => {
            document.getElementById('modal-sub').textContent = data.total + ' submission(s) from ' + data.students + ' student(s)';
            const p = data.problem;
            const diffColors = {easy:'#2e7d32',medium:'#e65100',hard:'#c62828'};
            const diffBg = {easy:'#e8f5e9',medium:'#fff8e1',hard:'#ffebee'};
            let html = `<div style="background:#f8f9ff;border-radius:10px;padding:16px;margin-bottom:18px;border-left:5px solid #3f51b5">
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:10px">
                    <span style="background:${diffBg[p.difficulty]};color:${diffColors[p.difficulty]};padding:3px 10px;border-radius:12px;font-size:0.78rem;font-weight:700">${p.difficulty.charAt(0).toUpperCase()+p.difficulty.slice(1)}</span>
                    <span style="background:#f5f5f5;padding:3px 10px;border-radius:12px;font-size:0.78rem;color:#555">${escHtml(p.category)}</span>
                    <span style="color:#fb8c00;font-size:0.82rem;font-weight:700">⭐ ${p.points} pts</span>
                </div>
                <div style="font-size:0.9rem;color:#333;line-height:1.7;white-space:pre-wrap;margin-bottom:12px">${escHtml(p.description)}</div>
                ${p.sample_input ? `<div style="margin-bottom:8px"><div style="font-size:0.75rem;font-weight:700;color:#555;margin-bottom:4px">Sample Input:</div><pre style="background:#e8e8e8;padding:8px;border-radius:5px;font-size:0.82rem;margin:0">${escHtml(p.sample_input)}</pre></div>` : ''}
                ${p.sample_output ? `<div><div style="font-size:0.75rem;font-weight:700;color:#555;margin-bottom:4px">Expected Output:</div><pre style="background:#e8e8e8;padding:8px;border-radius:5px;font-size:0.82rem;margin:0">${escHtml(p.sample_output)}</pre></div>` : ''}
                ${p.hints ? `<div style="margin-top:8px;background:#fff8e1;border-left:3px solid #fb8c00;padding:8px 12px;border-radius:0 6px 6px 0;font-size:0.82rem;color:#555">💡 ${escHtml(p.hints)}</div>` : ''}
            </div>`;
            if (!data.submissions.length) {
                html += '<p style="text-align:center;color:#999;padding:20px">No submissions yet.</p>';
            } else {
            data.submissions.forEach(s => {
                const colors = {accepted:'#2e7d32',wrong:'#c62828',partial:'#e65100',error:'#757575'};
                const col = colors[s.status] || '#555';
                html += `<div class="sub-card ${s.status}">
                    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:8px">
                        <div>
                            <strong style="color:#1a237e">${escHtml(s.name)}</strong>
                            <span style="color:#888;font-size:0.8rem;margin-left:8px">${escHtml(s.email)}</span>
                        </div>
                        <div style="display:flex;gap:8px;align-items:center">
                            <span style="background:${col}20;color:${col};padding:2px 10px;border-radius:12px;font-size:0.78rem;font-weight:700">${s.status.toUpperCase()}</span>
                            <span style="font-size:0.78rem;color:#666">${escHtml(s.language.toUpperCase())}</span>
                            <span style="font-size:0.75rem;color:#aaa">${escHtml(s.submitted_at)}</span>
                        </div>
                    </div>
                    <details>
                        <summary style="cursor:pointer;font-size:0.82rem;color:#3949ab;font-weight:600">💻 View Code</summary>
                        <pre class="code-view">${escHtml(s.code)}</pre>
                    </details>
                </div>`;
            });
            }
            document.getElementById('modal-body').innerHTML = html;
        })
        .catch(() => {
            document.getElementById('modal-body').innerHTML = '<p style="color:#c62828;padding:20px">Failed to load submissions.</p>';
        });
}
function closeModal() { document.getElementById('subModal').classList.remove('open'); }
function escHtml(str) { return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function toggleTC(id) {
    const el = document.getElementById('tc-panel-' + id);
    el.style.display = el.style.display === 'none' ? 'table-row' : 'none';
}
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('show');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('sidebarOverlay').classList.remove('show');}
</script>
</body>
</html>
