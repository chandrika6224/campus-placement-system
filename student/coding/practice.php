<?php
require_once '../../includes/config.php';
requireLogin('student');

$uid = $_SESSION['user_id'];
$pid = (int)($_GET['id'] ?? 0);
if (!$pid) { header("Location: index.php"); exit(); }

$problem = $conn->query("SELECT * FROM coding_problems WHERE id=$pid")->fetch_assoc();
if (!$problem) { header("Location: index.php"); exit(); }

// Check if this problem is linked to a placement round — enforce start/end window + round gate
$roundCheck = $conn->query("SELECT id, scheduled_at, end_time FROM placement_rounds WHERE coding_problem_id=$pid AND scheduled_at IS NOT NULL ORDER BY scheduled_at ASC LIMIT 1")->fetch_assoc();
if ($roundCheck) {
    $start = strtotime($roundCheck['scheduled_at']);
    $end   = $roundCheck['end_time'] ? strtotime($roundCheck['end_time']) : PHP_INT_MAX;
    $now   = time();
    if ($now < $start) {
        $at = date('d M Y, h:i A', $start);
        die("<!DOCTYPE html><html><head><title>Not Yet Available</title><link rel='stylesheet' href='../../css/style.css'></head><body style='display:flex;align-items:center;justify-content:center;min-height:100vh'><div class='card' style='text-align:center;max-width:420px;padding:40px'><div style='font-size:3rem;margin-bottom:16px'>⏳</div><h2 style='color:#1a237e'>Problem Not Yet Available</h2><p style='color:#666;margin:12px 0'>This problem opens on:</p><div style='background:#e8eaf6;border-radius:8px;padding:12px;font-size:1.1rem;font-weight:700;color:#1a237e;margin-bottom:20px'>$at</div><a href='index.php' class='btn btn-primary'>← Back</a></div></body></html>");
    }
    if ($now > $end) {
        die("<!DOCTYPE html><html><head><title>Closed</title><link rel='stylesheet' href='../../css/style.css'></head><body style='display:flex;align-items:center;justify-content:center;min-height:100vh'><div class='card' style='text-align:center;max-width:420px;padding:40px'><div style='font-size:3rem;margin-bottom:16px'>🚫</div><h2 style='color:#c62828'>Submission Window Closed</h2><p style='color:#666;margin:12px 0'>The coding round has ended.</p><a href='index.php' class='btn btn-primary'>← Back</a></div></body></html>");
    }
    // Check previous round gate
    require_once '../../includes/round_gate.php';
    $gate = checkRoundGate($conn, $uid, $roundCheck['id']);
    if (!$gate['pass']) roundGateBlock($gate, '/placement/student/coding/index.php');
}

// Get last submission
$lastSub = $conn->query("SELECT * FROM coding_submissions WHERE problem_id=$pid AND user_id=$uid ORDER BY submitted_at DESC LIMIT 1")->fetch_assoc();

// Get all submissions for this problem by this user
$submissions = $conn->query("SELECT * FROM coding_submissions WHERE problem_id=$pid AND user_id=$uid ORDER BY submitted_at DESC LIMIT 5");

$isSolved = $conn->query("SELECT id FROM coding_submissions WHERE problem_id=$pid AND user_id=$uid AND status='accepted'")->num_rows > 0;

// Starter code templates
$starters = [
    'python' => "# Write your solution here\n\n",
    'javascript' => "// Write your solution here\n\n",
    'cpp' => "#include <iostream>\nusing namespace std;\n\nint main() {\n    // Write your solution here\n    \n    return 0;\n}\n",
    'c' => "#include <stdio.h>\n\nint main() {\n    // Write your solution here\n    \n    return 0;\n}\n",
    'java' => "import java.util.Scanner;\n\npublic class Main {\n    public static void main(String[] args) {\n        Scanner sc = new Scanner(System.in);\n        // Write your solution here\n        \n    }\n}\n",
    'php' => "<?php\n// Write your solution here\n\n",
];

$diffColors = ['easy'=>['#2e7d32','#e8f5e9'],'medium'=>['#e65100','#fff8e1'],'hard'=>['#c62828','#ffebee']];
$dc = $diffColors[$problem['difficulty']];

// Handle submit
$submitMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_code'])) {
    $code     = $_POST['code'] ?? '';
    $language = sanitize($_POST['language'] ?? 'python');
    $execTime = (int)($_POST['exec_time'] ?? 0);

    // Fetch all test cases
    $stTC = $conn->prepare("SELECT * FROM coding_test_cases WHERE problem_id=? ORDER BY id");
    $stTC->bind_param('i', $pid); $stTC->execute();
    $allTC = $stTC->get_result()->fetch_all(MYSQLI_ASSOC); $stTC->close();

    // If no test cases fall back to sample_output
    if (empty($allTC) && !empty($problem['sample_output'])) {
        $allTC = [['id'=>0,'input'=>$problem['sample_input']??'','expected_output'=>trim($problem['sample_output']),'is_sample'=>1]];
    }

    $passed = 0; $total = count($allTC); $tcResults = [];
    foreach ($allTC as $tc) {
        $res = runTestCase($code, $language, $tc['input']);
        $got = trim($res['output'] ?? '');
        $exp = trim($tc['expected_output']);
        $ok  = ($got === $exp);
        if ($ok) $passed++;
        $tcResults[] = ['input'=>$tc['input'],'expected'=>$exp,'got'=>$got,'pass'=>$ok,'is_sample'=>$tc['is_sample'],'exec_time'=>$res['exec_time']];
    }

    $status = ($total === 0) ? 'accepted' : (($passed === $total) ? 'accepted' : ($passed > 0 ? 'partial' : 'wrong'));
    // Check for error in any case
    foreach ($tcResults as $r) { if (stripos($r['got'],'error') !== false || stripos($r['got'],'exception') !== false) { $status = 'error'; break; } }

    $points = $status === 'accepted' ? $problem['points'] : ($status === 'partial' ? round($problem['points'] * $passed / max($total,1)) : 0);
    $safeCode = $conn->real_escape_string($code);
    $colCheck = $conn->query("SHOW COLUMNS FROM coding_submissions LIKE 'exec_time'");
    if ($colCheck->num_rows === 0) {
        $conn->query("ALTER TABLE coding_submissions ADD COLUMN exec_time INT DEFAULT 0");
    }
    $conn->query("INSERT INTO coding_submissions (problem_id,user_id,language,code,status,points_earned,exec_time) VALUES ($pid,$uid,'$language','$safeCode','$status',$points,$execTime)");

    $tcJson = htmlspecialchars(json_encode($tcResults), ENT_QUOTES);
    $statusBanners = [
        'accepted' => "<div style='background:#1b5e20;color:#fff;padding:14px 18px;border-radius:8px;font-size:1rem;font-weight:700;margin-bottom:12px;text-align:center'>🎉 Accepted! All $total test case(s) passed! +$points pts</div>",
        'partial'  => "<div style='background:#e65100;color:#fff;padding:14px 18px;border-radius:8px;font-size:1rem;font-weight:700;margin-bottom:12px;text-align:center'>⚠️ Partial: $passed/$total passed. +$points pts</div>",
        'wrong'    => "<div style='background:#b71c1c;color:#fff;padding:14px 18px;border-radius:8px;font-size:1rem;font-weight:700;margin-bottom:12px;text-align:center'>❌ Wrong Answer. $passed/$total passed.</div>",
        'error'    => "<div style='background:#4a148c;color:#fff;padding:14px 18px;border-radius:8px;font-size:1rem;font-weight:700;margin-bottom:12px;text-align:center'>💥 Error in code. $passed/$total passed.</div>",
    ];
    $submitMsg = ($statusBanners[$status] ?? '') . "<div id='tc-results-data' data-results='$tcJson'></div>";
    $isSolved = ($status === 'accepted');
}

function runTestCase($code, $lang, $input) {
    $tmpDir = sys_get_temp_dir();
    $id = uniqid('tc_', true);
    $start = microtime(true);
    $output = '';
    switch ($lang) {
        case 'python':
            $f = "$tmpDir/$id.py"; file_put_contents($f, $code);
            $inf = "$tmpDir/{$id}_in.txt"; file_put_contents($inf, $input);
            $output = shell_exec("python \"$f\" < \"$inf\" 2>&1") ?? 'Timed out.';
            @unlink($f); @unlink($inf); break;
        case 'javascript':
            $f = "$tmpDir/$id.js"; file_put_contents($f, $code);
            $inf = "$tmpDir/{$id}_in.txt"; file_put_contents($inf, $input);
            $output = shell_exec("node \"$f\" < \"$inf\" 2>&1") ?? 'Timed out.';
            @unlink($f); @unlink($inf); break;
        case 'cpp': case 'c':
            $ext = $lang === 'c' ? 'c' : 'cpp'; $f = "$tmpDir/$id.$ext"; $bin = "$tmpDir/$id";
            file_put_contents($f, $code);
            $compiler = $lang === 'c' ? 'gcc' : 'g++';
            $co = shell_exec("$compiler \"$f\" -o \"$bin\" 2>&1");
            if (!empty($co)) { $output = "Compilation Error:\n$co"; } else {
                $inf = "$tmpDir/{$id}_in.txt"; file_put_contents($inf, $input);
                $output = shell_exec("\"$bin\" < \"$inf\" 2>&1") ?? 'Timed out.';
                @unlink($inf); @unlink($bin);
            }
            @unlink($f); break;
        case 'java':
            $f = "$tmpDir/Main_$id.java";
            $jc = preg_replace('/public\s+class\s+\w+/', "public class Main_$id", $code);
            file_put_contents($f, $jc);
            $co = shell_exec("javac \"$f\" -d \"$tmpDir\" 2>&1");
            if (!empty($co)) { $output = "Compilation Error:\n$co"; } else {
                $inf = "$tmpDir/{$id}_in.txt"; file_put_contents($inf, $input);
                $output = shell_exec("java -cp \"$tmpDir\" Main_$id < \"$inf\" 2>&1") ?? 'Timed out.';
                @unlink($inf); @unlink("$tmpDir/Main_$id.class");
            }
            @unlink($f); break;
        default: $output = 'Unsupported language.';
    }
    return ['output' => $output, 'exec_time' => round((microtime(true) - $start) * 1000)];
}

// Prev/Next problem
$prevProblem = $conn->query("SELECT id FROM coding_problems WHERE id < $pid ORDER BY id DESC LIMIT 1")->fetch_assoc();
$nextProblem = $conn->query("SELECT id FROM coding_problems WHERE id > $pid ORDER BY id ASC LIMIT 1")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($problem['title']) ?> - Coding</title>
<link rel="stylesheet" href="../../css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/theme/dracula.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/python/python.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/javascript/javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/clike/clike.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/php/php.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/edit/closebrackets.min.js"></script>
<style>
.editor-layout { display:grid;grid-template-columns:1fr 1fr;gap:0;height:calc(100vh - 70px);overflow:hidden; }
.problem-panel { overflow-y:auto;padding:20px;background:#f8f9ff;border-right:2px solid #e0e0e0; }
.editor-panel { display:flex;flex-direction:column;background:#282a36; }
.editor-toolbar { background:#1e1f2b;padding:10px 15px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;flex-shrink:0; }
.lang-select { background:#2d2f3e;color:#fff;border:1px solid #444;border-radius:6px;padding:5px 10px;font-size:0.85rem;cursor:pointer; }
.run-btn { padding:7px 18px;border-radius:6px;border:none;cursor:pointer;font-weight:700;font-size:0.85rem;transition:all 0.2s; }
.run-btn.run { background:#43a047;color:#fff; }
.run-btn.run:hover { background:#2e7d32; }
.run-btn.submit { background:#3f51b5;color:#fff; }
.run-btn.submit:hover { background:#303f9f; }
.run-btn:disabled { background:#555;cursor:not-allowed; }
.CodeMirror { flex:1;height:100%;font-size:0.9rem;font-family:'Fira Code','Consolas',monospace; }
.CodeMirror-scroll { height:100%; }
.output-panel { background:#1e1f2b;color:#f8f8f2;padding:12px 15px;font-family:monospace;font-size:0.85rem;min-height:120px;max-height:200px;overflow-y:auto;flex-shrink:0;border-top:2px solid #444; }
.output-panel .success { color:#50fa7b; }
.output-panel .error { color:#ff5555; }
.output-panel .info { color:#8be9fd; }
.diff-badge { padding:3px 10px;border-radius:12px;font-size:0.78rem;font-weight:700; }
.tag-chip { display:inline-block;padding:2px 8px;background:#e8eaf6;color:#3f51b5;border-radius:10px;font-size:0.72rem;margin:2px; }
.hint-box { background:#fff8e1;border-left:4px solid #fb8c00;border-radius:0 8px 8px 0;padding:10px 14px;margin-top:10px;font-size:0.88rem;color:#555;display:none; }
.input-area { background:#2d2f3e;color:#f8f8f2;border:1px solid #444;border-radius:6px;padding:8px;font-family:monospace;font-size:0.85rem;width:100%;resize:vertical;min-height:60px; }
@media(max-width:900px) { .editor-layout { grid-template-columns:1fr;height:auto; } .problem-panel { max-height:50vh; } }
</style>
</head>
<body style="margin:0;overflow:hidden">
<nav class="navbar" style="height:60px;flex-shrink:0">
    <a href="index.php" class="brand" style="font-size:1rem">🎓 Campus<span>Recruit</span> <span style="color:#c5cae9;font-size:0.75rem">/ Coding</span></a>
    <div class="nav-links" style="font-size:0.82rem">
        <a href="index.php">← Problems</a>
        <?php if ($prevProblem): ?><a href="practice.php?id=<?= $prevProblem['id'] ?>">‹ Prev</a><?php endif; ?>
        <?php if ($nextProblem): ?><a href="practice.php?id=<?= $nextProblem['id'] ?>">Next ›</a><?php endif; ?>
        <a href="leaderboard.php">🏆 Leaderboard</a>
        <?php if ($lastSub): ?>
        <?php
            $statusStyles = [
                'accepted' => 'background:#1b5e20;color:#69f0ae',
                'wrong'    => 'background:#b71c1c;color:#ff8a80',
                'partial'  => 'background:#e65100;color:#ffd180',
                'error'    => 'background:#4a148c;color:#ea80fc',
            ];
            $statusLabels = ['accepted'=>'✅ Accepted','wrong'=>'❌ Wrong Answer','partial'=>'⚠️ Partial','error'=>'💥 Error'];
            $st = $lastSub['status'];
        ?>
        <span style="<?= $statusStyles[$st] ?? 'background:#333;color:#fff' ?>;padding:4px 12px;border-radius:20px;font-size:0.8rem;font-weight:700">
            <?= $statusLabels[$st] ?? ucfirst($st) ?>
        </span>
        <?php endif; ?>
        <a href="../logout.php" class="btn-logout">Logout</a>
    </div>
</nav>

<div class="editor-layout">
    <!-- Left: Problem Description -->
    <div class="problem-panel">
        <?= $submitMsg ?>

        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:12px">
            <h2 style="color:#1a237e;margin:0;font-size:1.2rem"><?= htmlspecialchars($problem['title']) ?></h2>
            <span class="diff-badge" style="background:<?= $dc[1] ?>;color:<?= $dc[0] ?>"><?= ucfirst($problem['difficulty']) ?></span>
            <span style="color:#fb8c00;font-weight:700;font-size:0.85rem">⭐ <?= $problem['points'] ?> pts</span>
            <?php if ($isSolved): ?><span style="color:#2e7d32;font-weight:700;font-size:0.85rem">✅ Solved</span><?php endif; ?>
        </div>

        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:15px">
            <span style="background:#f5f5f5;padding:2px 8px;border-radius:10px;font-size:0.78rem;color:#555"><?= htmlspecialchars($problem['category']) ?></span>
            <?php foreach (array_filter(array_map('trim', explode(',', $problem['tags'] ?? ''))) as $tag): ?>
            <span class="tag-chip"><?= htmlspecialchars($tag) ?></span>
            <?php endforeach; ?>
        </div>

        <div style="color:#333;font-size:0.92rem;line-height:1.7;margin-bottom:20px;white-space:pre-wrap"><?= htmlspecialchars($problem['description']) ?></div>

        <?php if ($problem['sample_input'] || $problem['sample_output']): ?>
        <div style="background:#f5f5f5;border-radius:8px;padding:14px;margin-bottom:15px">
            <div style="font-weight:700;color:#1a237e;margin-bottom:8px;font-size:0.9rem">📋 Example</div>
            <?php if ($problem['sample_input']): ?>
            <div style="margin-bottom:8px">
                <div style="font-size:0.78rem;color:#666;font-weight:600;margin-bottom:3px">Input:</div>
                <pre style="background:#e8e8e8;padding:8px;border-radius:5px;font-size:0.85rem;margin:0;overflow-x:auto"><?= htmlspecialchars($problem['sample_input']) ?></pre>
            </div>
            <?php endif; ?>
            <?php if ($problem['sample_output']): ?>
            <div>
                <div style="font-size:0.78rem;color:#666;font-weight:600;margin-bottom:3px">Output:</div>
                <pre style="background:#e8e8e8;padding:8px;border-radius:5px;font-size:0.85rem;margin:0;overflow-x:auto"><?= htmlspecialchars($problem['sample_output']) ?></pre>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($problem['hints']): ?>
        <button onclick="document.getElementById('hint-box').style.display=document.getElementById('hint-box').style.display==='block'?'none':'block'" class="btn btn-sm" style="background:#fff8e1;color:#e65100;border:1px solid #ffcc80">💡 Show Hint</button>
        <div id="hint-box" class="hint-box"><?= htmlspecialchars($problem['hints']) ?></div>
        <?php endif; ?>

        <!-- Recent Submissions -->
        <?php if ($submissions->num_rows > 0): ?>
        <div style="margin-top:20px">
            <div style="font-weight:700;color:#1a237e;margin-bottom:8px;font-size:0.9rem">📜 Your Submissions</div>
            <?php while($s = $submissions->fetch_assoc()): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:7px 10px;background:#f5f5f5;border-radius:6px;margin-bottom:5px;font-size:0.82rem">
                <span>
                    <span class="badge badge-<?= $s['status']==='accepted'?'selected':($s['status']==='wrong'?'rejected':'applied') ?>"><?= ucfirst($s['status']) ?></span>
                    <span style="color:#666;margin-left:8px"><?= strtoupper($s['language']) ?></span>
                </span>
                <span style="color:#999"><?= date('d M, h:i A', strtotime($s['submitted_at'])) ?></span>
            </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Right: Code Editor -->
    <div class="editor-panel">
        <div class="editor-toolbar">
            <select class="lang-select" id="lang-select" onchange="changeLanguage(this.value)">
                <option value="python">🐍 Python</option>
                <option value="javascript">🟨 JavaScript</option>
                <option value="cpp">⚙️ C++</option>
                <option value="c">🔵 C</option>
                <option value="java">☕ Java</option>
                <option value="php">🐘 PHP</option>
            </select>
            <button class="run-btn run" onclick="runCode()" id="run-btn">▶ Run</button>
            <button class="run-btn submit" onclick="submitCode()" id="submit-btn">📤 Submit</button>
            <button class="run-btn" style="background:#607d8b;color:#fff" onclick="resetCode()">↺ Reset</button>
            <span id="exec-info" style="color:#8be9fd;font-size:0.78rem;margin-left:auto"></span>
        </div>

        <textarea id="code-editor"><?= htmlspecialchars($lastSub ? $lastSub['code'] : $starters['python']) ?></textarea>

        <!-- Custom Input -->
        <div style="background:#1e1f2b;padding:8px 15px;border-top:1px solid #444;flex-shrink:0">
            <div style="color:#8be9fd;font-size:0.78rem;font-weight:600;margin-bottom:4px">📥 Custom Input (optional)</div>
            <textarea class="input-area" id="custom-input" placeholder="Enter test input here..."><?= htmlspecialchars($problem['sample_input'] ?? '') ?></textarea>
        </div>

        <!-- Output -->
        <div class="output-panel" id="output-panel">
            <span class="info">// Output will appear here after running your code...</span>
        </div>
    </div>
</div>

<!-- Submit Loading Overlay -->
<div id="submit-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:9999;align-items:center;justify-content:center;flex-direction:column;gap:18px">
    <div style="background:#1e1f2b;border-radius:16px;padding:40px 50px;text-align:center;border:1px solid #444">
        <div style="font-size:2.5rem;margin-bottom:14px">⚙️</div>
        <div style="color:#ffd54f;font-size:1.1rem;font-weight:700;margin-bottom:8px">Evaluating Your Solution</div>
        <div style="color:#8be9fd;font-size:0.88rem;margin-bottom:20px">Running all test cases, please wait...</div>
        <div style="display:flex;justify-content:center;gap:6px">
            <div class="dot-pulse" style="width:10px;height:10px;background:#3f51b5;border-radius:50%;animation:pulse 1s ease-in-out infinite"></div>
            <div class="dot-pulse" style="width:10px;height:10px;background:#3f51b5;border-radius:50%;animation:pulse 1s ease-in-out 0.2s infinite"></div>
            <div class="dot-pulse" style="width:10px;height:10px;background:#3f51b5;border-radius:50%;animation:pulse 1s ease-in-out 0.4s infinite"></div>
        </div>
    </div>
</div>
<style>
@keyframes pulse { 0%,100%{opacity:0.3;transform:scale(0.8)} 50%{opacity:1;transform:scale(1.2)} }
</style>

<!-- Hidden submit form -->
<form method="POST" id="submit-form" style="display:none">
    <input type="hidden" name="submit_code" value="1">
    <input type="hidden" name="code" id="form-code">
    <input type="hidden" name="language" id="form-lang">
    <input type="hidden" name="exec_time" id="form-exec-time">
</form>

<script>
const starters = <?= json_encode($starters) ?>;
const sampleOutput = <?= json_encode(trim($problem['sample_output'] ?? '')) ?>;
const RUN_URL = '../../coding/run.php';

// Init CodeMirror
const editor = CodeMirror.fromTextArea(document.getElementById('code-editor'), {
    mode: 'python',
    theme: 'dracula',
    lineNumbers: true,
    autoCloseBrackets: true,
    indentUnit: 4,
    tabSize: 4,
    indentWithTabs: false,
    lineWrapping: true,
    extraKeys: {
        'Ctrl-Enter': runCode,
        'Tab': cm => cm.replaceSelection('    '),
    }
});
editor.setSize('100%', '100%');

const modeMap = {python:'python',javascript:'javascript',cpp:'text/x-c++src',c:'text/x-csrc',java:'text/x-java',php:'application/x-httpd-php'};

function changeLanguage(lang) {
    editor.setOption('mode', modeMap[lang] || 'python');
    editor.setValue(starters[lang] || '');
    editor.focus();
}

function resetCode() {
    const lang = document.getElementById('lang-select').value;
    if (confirm('Reset code to starter template?')) {
        editor.setValue(starters[lang] || '');
    }
}

function setOutput(html, cls='') {
    const panel = document.getElementById('output-panel');
    panel.innerHTML = cls ? `<span class="${cls}">${html}</span>` : html;
}

function runCode() {
    const code = editor.getValue();
    const lang = document.getElementById('lang-select').value;
    const input = document.getElementById('custom-input').value;

    document.getElementById('run-btn').disabled = true;
    document.getElementById('run-btn').textContent = '⏳ Running...';
    setOutput('<span class="info">⏳ Executing your code...</span>');

    fetch(RUN_URL, {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `code=${encodeURIComponent(code)}&language=${lang}&input=${encodeURIComponent(input)}`
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('run-btn').disabled = false;
        document.getElementById('run-btn').textContent = '▶ Run';
        document.getElementById('exec-info').textContent = `⏱ ${data.exec_time}ms`;

        const out = data.output || '(No output)';
        const outTrimmed = out.trim();
        const expected = sampleOutput.trim();

        let html = `<span class="info">Output:</span>\n${escHtml(out)}`;
        if (expected && outTrimmed === expected) {
            html += `\n\n<span class="success">✅ Matches expected output!</span>`;
        } else if (expected) {
            html += `\n\n<span style="color:#ffb86c">Expected: ${escHtml(expected)}</span>`;
        }
        setOutput(html);
    })
    .catch(err => {
        document.getElementById('run-btn').disabled = false;
        document.getElementById('run-btn').textContent = '▶ Run';
        setOutput('<span class="error">⚠️ Connection error. Make sure XAMPP is running.</span>');
    });
}

function submitCode() {
    const code = editor.getValue();
    const lang = document.getElementById('lang-select').value;
    if (!code.trim()) { alert('Please write some code first!'); return; }
    if (!confirm('Submit your solution? All test cases will be evaluated.')) return;
    document.getElementById('form-code').value = code;
    document.getElementById('form-lang').value = lang;
    document.getElementById('form-exec-time').value = 0;
    // Show loading overlay
    document.getElementById('submit-overlay').style.display = 'flex';
    document.getElementById('submit-btn').disabled = true;
    document.getElementById('submit-btn').textContent = '⏳ Submitting...';
    document.getElementById('submit-form').submit();
}

// Show test case results if available
window.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('tc-results-data');
    if (!el) return;
    // Scroll problem panel to top so banner is visible
    const panel = document.querySelector('.problem-panel');
    panel.scrollTop = 0;
    // Flash the banner
    const banner = panel.querySelector('[style*="border-radius:8px"]');
    if (banner) {
        banner.style.transition = 'transform 0.3s ease';
        banner.style.transform = 'scale(1.02)';
        setTimeout(() => banner.style.transform = 'scale(1)', 300);
    }
    try {
        const results = JSON.parse(el.dataset.results);
        if (!results.length) { setOutput('<span class="info">Submitted. No test case results.</span>'); return; }
        let html = '<span class="info">📋 Submission Results:</span>\n\n';
        results.forEach((r, i) => {
            const icon = r.pass ? '<span class="success">✅ PASS</span>' : '<span class="error">❌ FAIL</span>';
            const visibility = r.is_sample ? '' : ' <span style="color:#8be9fd">[Hidden]</span>';
            html += `Case ${i+1}${visibility}: ${icon} (${r.exec_time}ms)\n`;
            if (r.is_sample || !r.pass) {
                if (r.is_sample) html += `  Input:    ${escHtml(r.input)}\n`;
                html += `  Expected: ${escHtml(r.expected)}\n`;
                html += `  Got:      ${escHtml(r.got)}\n`;
            }
            html += '\n';
        });
        setOutput(html);
    } catch(e) { setOutput('<span class="info">Submitted successfully.</span>'); }
});

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// Keyboard shortcut
document.addEventListener('keydown', e => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') { e.preventDefault(); runCode(); }
});
</script>
</body>
</html>

