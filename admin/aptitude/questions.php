<?php
require_once '../../includes/config.php';
requireLogin('admin');

$test_id = (int)($_GET['test_id'] ?? 0);
if (!$test_id) { header("Location: index.php"); exit(); }

$stTest = $conn->prepare("SELECT * FROM tests WHERE id=?");
$stTest->bind_param('i', $test_id); $stTest->execute();
$test = $stTest->get_result()->fetch_assoc(); $stTest->close();
if (!$test) { header("Location: index.php"); exit(); }

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_question'])) {
        $q    = trim($_POST['question'] ?? '');
        $a    = trim($_POST['option_a'] ?? '');
        $b    = trim($_POST['option_b'] ?? '');
        $c    = trim($_POST['option_c'] ?? '');
        $d    = trim($_POST['option_d'] ?? '');
        $ans  = trim($_POST['correct_answer'] ?? '');
        $marks = (int)$_POST['marks'];
        $stIQ = $conn->prepare("INSERT INTO test_questions (test_id,question,option_a,option_b,option_c,option_d,correct_answer,marks) VALUES (?,?,?,?,?,?,?,?)");
        $stIQ->bind_param('issssssi', $test_id, $q, $a, $b, $c, $d, $ans, $marks);
        $stIQ->execute(); $stIQ->close();
        $stUM = $conn->prepare("UPDATE tests SET total_marks=(SELECT SUM(marks) FROM test_questions WHERE test_id=?) WHERE id=?");
        $stUM->bind_param('ii', $test_id, $test_id); $stUM->execute(); $stUM->close();
        $msg = '<div class="alert alert-success">Question added!</div>';
    }
    if (isset($_POST['delete_q'])) {
        $qid = (int)$_POST['q_id'];
        $stDQ = $conn->prepare("DELETE FROM test_questions WHERE id=? AND test_id=?");
        $stDQ->bind_param('ii', $qid, $test_id); $stDQ->execute(); $stDQ->close();
        $stUM2 = $conn->prepare("UPDATE tests SET total_marks=(SELECT COALESCE(SUM(marks),0) FROM test_questions WHERE test_id=?) WHERE id=?");
        $stUM2->bind_param('ii', $test_id, $test_id); $stUM2->execute(); $stUM2->close();
        $msg = '<div class="alert alert-success">Question deleted.</div>';
    }
    if (isset($_POST['add_samples'])) {
        $cat = $test['category'];
        $samples = getSampleQuestions($cat);
        $stIS = $conn->prepare("INSERT INTO test_questions (test_id,question,option_a,option_b,option_c,option_d,correct_answer,marks) VALUES (?,?,?,?,?,?,?,1)");
        foreach ($samples as $sq) {
            $stIS->bind_param('issssss', $test_id, $sq['q'], $sq['a'], $sq['b'], $sq['c'], $sq['d'], $sq['ans']);
            $stIS->execute();
        }
        $stIS->close();
        $stUMS = $conn->prepare("UPDATE tests SET total_marks=(SELECT SUM(marks) FROM test_questions WHERE test_id=?) WHERE id=?");
        $stUMS->bind_param('ii', $test_id, $test_id); $stUMS->execute(); $stUMS->close();
        $msg = '<div class="alert alert-success">'.count($samples).' sample questions added!</div>';
    }
}

function getSampleQuestions($category) {
    $aptitude = [
        ['q'=>'If a train travels 60 km in 1 hour, how far will it travel in 2.5 hours?','a'=>'120 km','b'=>'150 km','c'=>'180 km','d'=>'200 km','ans'=>'b'],
        ['q'=>'What is 15% of 200?','a'=>'25','b'=>'30','c'=>'35','d'=>'40','ans'=>'b'],
        ['q'=>'A man buys a book for Rs.50 and sells it for Rs.65. What is the profit percentage?','a'=>'25%','b'=>'30%','c'=>'35%','d'=>'20%','ans'=>'b'],
        ['q'=>'If 6 workers can complete a job in 12 days, how many days will 9 workers take?','a'=>'6','b'=>'8','c'=>'10','d'=>'12','ans'=>'b'],
        ['q'=>'What is the next number in the series: 2, 6, 12, 20, 30, ?','a'=>'40','b'=>'42','c'=>'44','d'=>'46','ans'=>'b'],
        ['q'=>'The average of 5 numbers is 20. If one number is removed, the average becomes 18. What is the removed number?','a'=>'26','b'=>'28','c'=>'30','d'=>'32','ans'=>'b'],
        ['q'=>'A car covers 300 km in 5 hours. What is its speed in km/h?','a'=>'50','b'=>'55','c'=>'60','d'=>'65','ans'=>'c'],
        ['q'=>'If 2x + 3 = 11, what is x?','a'=>'3','b'=>'4','c'=>'5','d'=>'6','ans'=>'b'],
        ['q'=>'What is the LCM of 12 and 18?','a'=>'24','b'=>'36','c'=>'48','d'=>'72','ans'=>'b'],
        ['q'=>'A rectangle has length 8 cm and width 5 cm. What is its area?','a'=>'35 sq cm','b'=>'40 sq cm','c'=>'45 sq cm','d'=>'50 sq cm','ans'=>'b'],
    ];
    $technical = [
        ['q'=>'Which data structure uses LIFO (Last In First Out) principle?','a'=>'Queue','b'=>'Stack','c'=>'Array','d'=>'Linked List','ans'=>'b'],
        ['q'=>'What does HTML stand for?','a'=>'Hyper Text Markup Language','b'=>'High Text Markup Language','c'=>'Hyper Transfer Markup Language','d'=>'None','ans'=>'a'],
        ['q'=>'Which of the following is NOT a programming language?','a'=>'Python','b'=>'Java','c'=>'HTML','d'=>'C++','ans'=>'c'],
        ['q'=>'What is the time complexity of Binary Search?','a'=>'O(n)','b'=>'O(n²)','c'=>'O(log n)','d'=>'O(1)','ans'=>'c'],
        ['q'=>'Which SQL command is used to retrieve data?','a'=>'INSERT','b'=>'UPDATE','c'=>'SELECT','d'=>'DELETE','ans'=>'c'],
        ['q'=>'What does OOP stand for?','a'=>'Object Oriented Programming','b'=>'Open Oriented Programming','c'=>'Object Order Processing','d'=>'None','ans'=>'a'],
        ['q'=>'Which of the following is a primary key property?','a'=>'Can be NULL','b'=>'Must be unique','c'=>'Can be duplicate','d'=>'Optional','ans'=>'b'],
        ['q'=>'What is the output of: print(2**3) in Python?','a'=>'6','b'=>'8','c'=>'9','d'=>'5','ans'=>'b'],
        ['q'=>'Which protocol is used for secure web browsing?','a'=>'HTTP','b'=>'FTP','c'=>'HTTPS','d'=>'SMTP','ans'=>'c'],
        ['q'=>'What is a foreign key?','a'=>'A key from another country','b'=>'A key that references primary key of another table','c'=>'A duplicate key','d'=>'None','ans'=>'b'],
    ];
    $coding = [
        ['q'=>'What will be the output of: x=5; x+=3; print(x) in Python?','a'=>'5','b'=>'3','c'=>'8','d'=>'15','ans'=>'c'],
        ['q'=>'Which loop is guaranteed to execute at least once?','a'=>'for loop','b'=>'while loop','c'=>'do-while loop','d'=>'foreach loop','ans'=>'c'],
        ['q'=>'What is recursion?','a'=>'A loop','b'=>'A function calling itself','c'=>'A variable','d'=>'An array','ans'=>'b'],
        ['q'=>'What does the "break" statement do in a loop?','a'=>'Continues to next iteration','b'=>'Exits the loop','c'=>'Restarts the loop','d'=>'None','ans'=>'b'],
        ['q'=>'Which sorting algorithm has best average case complexity O(n log n)?','a'=>'Bubble Sort','b'=>'Selection Sort','c'=>'Merge Sort','d'=>'Insertion Sort','ans'=>'c'],
        ['q'=>'What is the index of the first element in an array?','a'=>'1','b'=>'-1','c'=>'0','d'=>'Depends on language','ans'=>'c'],
        ['q'=>'What does NULL mean in programming?','a'=>'Zero','b'=>'Empty string','c'=>'No value / absence of value','d'=>'False','ans'=>'c'],
        ['q'=>'Which of these is a valid variable name in most languages?','a'=>'2variable','b'=>'my-var','c'=>'my_var','d'=>'my var','ans'=>'c'],
        ['q'=>'What is the purpose of a constructor in OOP?','a'=>'To destroy objects','b'=>'To initialize objects','c'=>'To copy objects','d'=>'None','ans'=>'b'],
        ['q'=>'What is an API?','a'=>'A programming language','b'=>'Application Programming Interface','c'=>'A database','d'=>'A server','ans'=>'b'],
    ];
    return $category === 'aptitude' ? $aptitude : ($category === 'technical' ? $technical : $coding);
}

$stQ = $conn->prepare("SELECT * FROM test_questions WHERE test_id=? ORDER BY id ASC");
$stQ->bind_param('i', $test_id); $stQ->execute();
$questions = $stQ->get_result(); $stQ->close();
$qCount = $questions->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Test Questions - Admin</title>
<link rel="stylesheet" href="../../css/style.css">
</head>
<body>
<?php require_once '../sidebar.php'; ?>
<div class="topbar">
    <div class="topbar-left">
        <button class="hamburger" onclick="toggleSidebar()">☰</button>
        <span class="page-title">Test Questions</span>
    </div>
    <div class="topbar-right"><?php require_once '../../notifications/widget.php'; ?></div>
</div>
<div class="main-content">
    <?= $msg ?>

    <div class="card" style="background:linear-gradient(135deg,#1a237e,#3949ab);color:#fff;margin-bottom:20px">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
            <div>
                <h3 style="color:#ffd54f;margin-bottom:5px"><?= htmlspecialchars($test['title']) ?></h3>
                <div style="color:#c5cae9;font-size:0.9rem;display:flex;gap:15px;flex-wrap:wrap">
                    <span>📂 <?= ucfirst($test['category']) ?></span>
                    <span>⏱️ <?= $test['duration'] ?> min</span>
                    <span>❓ <?= $qCount ?> Questions</span>
                    <span>🏆 Total: <?= $test['total_marks'] ?> marks</span>
                </div>
            </div>
            <a href="index.php" class="btn" style="background:#ffd54f;color:#1a237e">← Back to Tests</a>
        </div>
    </div>

    <!-- Add Sample Questions -->
    <?php if ($qCount === 0): ?>
    <div class="card" style="border:2px dashed #3f51b5;text-align:center;padding:25px">
        <h3 style="color:#1a237e;margin-bottom:10px">🚀 Quick Start</h3>
        <p style="color:#666;margin-bottom:15px">Add 10 sample <?= $test['category'] ?> questions instantly to get started.</p>
        <form method="POST">
            <button name="add_samples" class="btn btn-primary">Add 10 Sample Questions</button>
        </form>
    </div>
    <?php endif; ?>

    <!-- Add Question Form -->
    <div class="card">
        <h2>➕ Add New Question</h2>
        <form method="POST">
            <div class="form-group">
                <label>Question *</label>
                <textarea name="question" rows="3" placeholder="Enter your question here..." required></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Option A *</label>
                    <input type="text" name="option_a" placeholder="Option A" required>
                </div>
                <div class="form-group">
                    <label>Option B *</label>
                    <input type="text" name="option_b" placeholder="Option B" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Option C *</label>
                    <input type="text" name="option_c" placeholder="Option C" required>
                </div>
                <div class="form-group">
                    <label>Option D *</label>
                    <input type="text" name="option_d" placeholder="Option D" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Correct Answer *</label>
                    <select name="correct_answer" required>
                        <option value="a">A</option>
                        <option value="b">B</option>
                        <option value="c">C</option>
                        <option value="d">D</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Marks</label>
                    <input type="number" name="marks" value="1" min="1" max="10">
                </div>
            </div>
            <button type="submit" name="add_question" class="btn btn-primary">Add Question</button>
        </form>
    </div>

    <!-- Questions List -->
    <div class="card">
        <h2>Questions (<?= $qCount ?>)</h2>
        <?php $stQ2 = $conn->prepare("SELECT * FROM test_questions WHERE test_id=? ORDER BY id ASC"); $stQ2->bind_param('i', $test_id); $stQ2->execute(); $questions = $stQ2->get_result(); $stQ2->close(); ?>
        <?php if ($qCount === 0): ?>
        <p style="color:#999;text-align:center;padding:20px">No questions added yet.</p>
        <?php else: ?>
        <?php $i=1; while($q = $questions->fetch_assoc()): ?>
        <div style="border:1px solid #e0e0e0;border-radius:8px;padding:15px;margin-bottom:12px">
            <div style="display:flex;justify-content:space-between;align-items:flex-start">
                <div style="flex:1">
                    <div style="font-weight:700;color:#1a237e;margin-bottom:8px">Q<?= $i ?>. <?= htmlspecialchars($q['question']) ?> <span style="color:#999;font-size:0.82rem">(<?= $q['marks'] ?> mark<?= $q['marks']>1?'s':'' ?>)</span></div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:5px;font-size:0.9rem">
                        <span style="padding:5px 10px;border-radius:5px;background:<?= $q['correct_answer']==='a'?'#e8f5e9':'#f5f5f5' ?>;color:<?= $q['correct_answer']==='a'?'#2e7d32':'#555' ?>">A. <?= htmlspecialchars($q['option_a']) ?> <?= $q['correct_answer']==='a'?'✓':'' ?></span>
                        <span style="padding:5px 10px;border-radius:5px;background:<?= $q['correct_answer']==='b'?'#e8f5e9':'#f5f5f5' ?>;color:<?= $q['correct_answer']==='b'?'#2e7d32':'#555' ?>">B. <?= htmlspecialchars($q['option_b']) ?> <?= $q['correct_answer']==='b'?'✓':'' ?></span>
                        <span style="padding:5px 10px;border-radius:5px;background:<?= $q['correct_answer']==='c'?'#e8f5e9':'#f5f5f5' ?>;color:<?= $q['correct_answer']==='c'?'#2e7d32':'#555' ?>">C. <?= htmlspecialchars($q['option_c']) ?> <?= $q['correct_answer']==='c'?'✓':'' ?></span>
                        <span style="padding:5px 10px;border-radius:5px;background:<?= $q['correct_answer']==='d'?'#e8f5e9':'#f5f5f5' ?>;color:<?= $q['correct_answer']==='d'?'#2e7d32':'#555' ?>">D. <?= htmlspecialchars($q['option_d']) ?> <?= $q['correct_answer']==='d'?'✓':'' ?></span>
                    </div>
                </div>
                <form method="POST" onsubmit="return confirm('Delete this question?')" style="margin-left:10px">
                    <input type="hidden" name="q_id" value="<?= $q['id'] ?>">
                    <button name="delete_q" class="btn btn-danger btn-sm">Delete</button>
                </form>
            </div>
        </div>
        <?php $i++; endwhile; ?>
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
