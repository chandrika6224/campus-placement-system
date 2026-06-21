<?php
/**
 * Coding System Setup & Migration Script
 * Run once at: http://localhost/placement/coding_setup.php
 */
require_once 'includes/config.php';

$log = [];
$errors = [];

function run($conn, $sql, $label, &$log, &$errors) {
    if ($conn->query($sql)) {
        $log[] = "✅ $label";
    } else {
        $errors[] = "❌ $label: " . $conn->error;
    }
}

// ── 1. Ensure coding_problems has all required columns ──────────
run($conn, "CREATE TABLE IF NOT EXISTS coding_problems (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    difficulty  ENUM('easy','medium','hard') DEFAULT 'easy',
    category    VARCHAR(100) DEFAULT 'General',
    sample_input  TEXT,
    sample_output TEXT,
    hints       TEXT,
    tags        VARCHAR(300),
    points      INT DEFAULT 10,
    company_tag VARCHAR(100) DEFAULT NULL,
    year_asked  YEAR DEFAULT NULL,
    status      ENUM('active','inactive') DEFAULT 'active',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)", "Create coding_problems table", $log, $errors);

// Add missing columns one by one (safe for existing tables)
$cpCols = [];
$r = $conn->query("SHOW COLUMNS FROM coding_problems");
while ($c = $r->fetch_assoc()) $cpCols[] = $c['Field'];

$cpAlters = [
    'hints'       => "ADD COLUMN hints TEXT AFTER sample_output",
    'tags'        => "ADD COLUMN tags VARCHAR(300) AFTER hints",
    'points'      => "ADD COLUMN points INT DEFAULT 10 AFTER tags",
    'company_tag' => "ADD COLUMN company_tag VARCHAR(100) DEFAULT NULL AFTER points",
    'year_asked'  => "ADD COLUMN year_asked YEAR DEFAULT NULL AFTER company_tag",
    'status'      => "ADD COLUMN status ENUM('active','inactive') DEFAULT 'active' AFTER year_asked",
];
foreach ($cpAlters as $col => $alter) {
    if (!in_array($col, $cpCols)) {
        run($conn, "ALTER TABLE coding_problems $alter", "Add column coding_problems.$col", $log, $errors);
    } else {
        $log[] = "⏭ coding_problems.$col already exists";
    }
}

// ── 2. Ensure coding_submissions has all required columns ───────
run($conn, "CREATE TABLE IF NOT EXISTS coding_submissions (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    problem_id    INT NOT NULL,
    user_id       INT NOT NULL,
    language      VARCHAR(30),
    code          TEXT,
    status        ENUM('accepted','wrong','error','partial') DEFAULT 'wrong',
    points_earned INT DEFAULT 0,
    exec_time     INT DEFAULT 0,
    submitted_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (problem_id) REFERENCES coding_problems(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)    REFERENCES users(id) ON DELETE CASCADE
)", "Create coding_submissions table", $log, $errors);

$csCols = [];
$r = $conn->query("SHOW COLUMNS FROM coding_submissions");
while ($c = $r->fetch_assoc()) $csCols[] = $c['Field'];

if (!in_array('exec_time', $csCols)) {
    run($conn, "ALTER TABLE coding_submissions ADD COLUMN exec_time INT DEFAULT 0 AFTER points_earned",
        "Add column coding_submissions.exec_time", $log, $errors);
} else {
    $log[] = "⏭ coding_submissions.exec_time already exists";
}

// Fix ENUM to include 'partial' if missing
$enumRow = $conn->query("SHOW COLUMNS FROM coding_submissions LIKE 'status'")->fetch_assoc();
if ($enumRow && strpos($enumRow['Type'], 'partial') === false) {
    run($conn, "ALTER TABLE coding_submissions MODIFY COLUMN status ENUM('accepted','wrong','error','partial') DEFAULT 'wrong'",
        "Fix coding_submissions.status ENUM (add partial)", $log, $errors);
} else {
    $log[] = "⏭ coding_submissions.status ENUM already correct";
}

// ── 3. Ensure coding_test_cases table exists ────────────────────
run($conn, "CREATE TABLE IF NOT EXISTS coding_test_cases (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    problem_id      INT NOT NULL,
    input           TEXT NOT NULL,
    expected_output TEXT NOT NULL,
    is_sample       TINYINT DEFAULT 0,
    FOREIGN KEY (problem_id) REFERENCES coding_problems(id) ON DELETE CASCADE
)", "Create coding_test_cases table", $log, $errors);

// ── 4. Remove duplicate problems (keep lowest id per title) ─────
$dupes = $conn->query("SELECT title, COUNT(*) as cnt FROM coding_problems GROUP BY title HAVING cnt > 1");
$dupCount = 0;
while ($d = $dupes->fetch_assoc()) {
    $title = $conn->real_escape_string($d['title']);
    $conn->query("DELETE FROM coding_problems WHERE title='$title' AND id NOT IN (SELECT id FROM (SELECT MIN(id) as id FROM coding_problems WHERE title='$title') t)");
    $dupCount++;
}
if ($dupCount > 0) {
    $log[] = "✅ Removed duplicates for $dupCount problem title(s)";
} else {
    $log[] = "⏭ No duplicate problems found";
}

// ── 5. Seed problems only if table is empty ──────────────────────
$existing = (int)$conn->query("SELECT COUNT(*) as c FROM coding_problems")->fetch_assoc()['c'];
if ($existing === 0) {
    $problems = [
        // title, description, difficulty, category, sample_input, sample_output, hints, tags, points, company_tag, year_asked
        ['Reverse a String','Given a string, print it in reverse order.\n\nInput: A single string\nOutput: Reversed string','easy','Strings','hello','olleh','Use slicing s[::-1] in Python or a loop.','strings,basics',10,'TCS',2023],
        ['Sum of Array','Given n integers, find their sum.\n\nInput: First line is n, second line has n space-separated integers.\nOutput: Sum of all integers.','easy','Arrays',"5\n1 2 3 4 5",'15','Use a loop or built-in sum().','arrays,math',10,'Wipro',2023],
        ['Count Vowels','Count the number of vowels (a, e, i, o, u) in a given string (case-insensitive).\n\nInput: A single string\nOutput: Count of vowels','easy','Strings','Hello World','3','Convert to lowercase, then check each character.','strings,counting',10,'Infosys',2022],
        ['Factorial','Find the factorial of a given non-negative integer n.\n\nInput: A single integer n (0 <= n <= 12)\nOutput: n!','easy','Math','5','120','Use a loop: result = 1, multiply 1 to n.','math,loops',10,'TCS',2022],
        ['Palindrome Check','Check if a given string is a palindrome. Ignore case.\n\nInput: A single string\nOutput: "Yes" if palindrome, "No" otherwise','easy','Strings','Racecar','Yes','Convert to lowercase and compare with its reverse.','strings,palindrome',10,'Cognizant',2023],
        ['FizzBuzz','Print numbers 1 to n. For multiples of 3 print "Fizz", multiples of 5 print "Buzz", both print "FizzBuzz", else the number.\n\nInput: Integer n\nOutput: One per line.','easy','Logic','15',"1\n2\nFizz\n4\nBuzz\nFizz\n7\n8\nFizz\nBuzz\n11\nFizz\n13\n14\nFizzBuzz",'Use modulo operator.','logic,loops',10,null,null],
        ['Find Maximum','Find the maximum element in an array.\n\nInput: First line n, second line n space-separated integers.\nOutput: Maximum value.','easy','Arrays',"5\n3 1 4 1 5",'5','Use max() or iterate.','arrays,searching',10,null,null],
        ['Prime Check','Check if a number is prime.\n\nInput: Integer n\nOutput: "Prime" or "Not Prime"','easy','Math','17','Prime','Check divisibility up to sqrt(n).','math,prime',10,null,null],
        ['Two Sum','Given an array and a target, find two indices i,j (0-based) where arr[i]+arr[j]=target. Print space-separated.\n\nInput: n, then n integers, then target\nOutput: Two indices','medium','Arrays',"4\n2 7 11 15\n9",'0 1','Use a hashmap for O(n).','arrays,hashing',20,'Amazon',2023],
        ['Balanced Brackets','Check if a string of ()[]{}  is balanced.\n\nInput: A string\nOutput: "Balanced" or "Not Balanced"','medium','Stacks','{[()]}','Balanced','Use a stack.','stacks,strings',20,'Microsoft',2023],
        ['Missing Number','Given n-1 integers from range [1,n], find the missing one.\n\nInput: First line n, second line n-1 integers\nOutput: Missing number','medium','Math',"5\n1 2 4 5",'3','Expected sum = n*(n+1)/2 minus actual sum.','math,arrays',20,'Infosys',2023],
        ['Maximum Subarray Sum','Find contiguous subarray with largest sum (Kadane\'s Algorithm).\n\nInput: n, then n integers\nOutput: Maximum subarray sum','medium','Arrays',"8\n-2 1 -3 4 -1 2 1 -5 4",'6','Track current_sum and max_sum. Reset current_sum to 0 if negative.','arrays,dp,kadane',20,'Microsoft',2022],
        ['Anagram Check','Check if two strings are anagrams. Ignore case.\n\nInput: Two strings on separate lines\nOutput: "Yes" or "No"','medium','Strings',"listen\nsilent",'Yes','Sort both strings and compare.','strings,sorting',20,'Wipro',2023],
        ['Valid Parentheses','Check if a string of (){}[] is valid.\n\nInput: String\nOutput: "Valid" or "Invalid"','medium','Stacks','({[]})','Valid','Use a stack for opening brackets.','stack,strings',20,null,null],
        ['Binary Search','Given a sorted array and a target, find the index using binary search. Print -1 if not found.\n\nInput: n, sorted array, target','medium','Searching',"6\n1 3 5 7 9 11\n7",'3','Use low, mid, high pointers.','searching,binary',20,null,null],
        ['Longest Common Subsequence','Find the length of the LCS of two strings.\n\nInput: Two strings on separate lines\nOutput: LCS length','hard','Dynamic Programming',"ABCBDAB\nBDCAB",'4','Use a 2D DP table.','dp,strings',30,'Google',2023],
        ['Number of Islands','Given a 2D grid of 1s and 0s, count the islands.\n\nInput: rows cols, then the grid rows\nOutput: Number of islands','hard','Graphs',"4 5\n1 1 0 0 0\n1 1 0 0 0\n0 0 1 0 0\n0 0 0 1 1",'3','DFS/BFS from each unvisited land cell.','graphs,dfs,matrix',30,'Amazon',2023],
        ['Trapping Rain Water','Compute how much water can be trapped given elevation heights.\n\nInput: n, then n heights\nOutput: Total water trapped','hard','Arrays',"12\n0 1 0 2 1 0 1 3 2 1 2 1",'6','For each index: water = min(max_left, max_right) - height[i].','arrays,two-pointers',30,'Google',2022],
        ['Word Break','Determine if a string can be segmented into dictionary words.\n\nInput: string, n, then n words\nOutput: "Yes" or "No"','hard','Dynamic Programming',"leetcode\n3\nleet\ncode\nleetcode",'Yes','DP: dp[i]=true if s[0..i-1] can be segmented.','dp,strings',30,'Microsoft',2023],
        ['LRU Cache','Implement LRU cache. Capacity c, q queries: GET key or PUT key value. Print result of each GET.\n\nInput: capacity, queries count, then queries\nOutput: Result of each GET','hard','Data Structures',"2\n5\nPUT 1 10\nPUT 2 20\nGET 1\nPUT 3 30\nGET 2","10\n-1",'Use OrderedDict or doubly linked list + hashmap.','data-structures,design',30,'Amazon',2022],
    ];

    $st = $conn->prepare("INSERT INTO coding_problems (title,description,difficulty,category,sample_input,sample_output,hints,tags,points,company_tag,year_asked) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    foreach ($problems as $p) {
        $st->bind_param('ssssssssissi'[0].'ssssssssiss', $p[0],$p[1],$p[2],$p[3],$p[4],$p[5],$p[6],$p[7],$p[8],$p[9],$p[10]);
        // use individual bind
        $t=$p[0];$d=$p[1];$df=$p[2];$cat=$p[3];$si=$p[4];$so=$p[5];$h=$p[6];$tg=$p[7];$pts=$p[8];$co=$p[9];$yr=$p[10];
        $st->bind_param('ssssssssiis',$t,$d,$df,$cat,$si,$so,$h,$tg,$pts,$co,$yr);
        $st->execute();
    }
    $st->close();
    $log[] = "✅ Seeded " . count($problems) . " problems";
} else {
    $log[] = "⏭ Problems already exist ($existing rows), skipping seed";
}

// ── 6. Seed test cases for key problems ─────────────────────────
$tcExist = (int)$conn->query("SELECT COUNT(*) as c FROM coding_test_cases")->fetch_assoc()['c'];
if ($tcExist === 0) {
    $testCases = [
        // [problem_title, input, expected_output, is_sample]
        ['Reverse a String', 'hello',    'olleh', 1],
        ['Reverse a String', 'world',    'dlrow', 0],
        ['Reverse a String', 'abcde',    'edcba', 0],
        ['Reverse a String', 'a',        'a',     0],

        ['Sum of Array', "5\n1 2 3 4 5",   '15',  1],
        ['Sum of Array', "3\n10 20 30",     '60',  0],
        ['Sum of Array', "4\n-1 -2 3 4",   '4',   0],

        ['Count Vowels', 'Hello World',   '3',    1],
        ['Count Vowels', 'aeiou',         '5',    0],
        ['Count Vowels', 'rhythm',        '0',    0],

        ['Factorial', '5',  '120',   1],
        ['Factorial', '0',  '1',     0],
        ['Factorial', '10', '3628800', 0],

        ['Palindrome Check', 'Racecar',  'Yes',  1],
        ['Palindrome Check', 'hello',    'No',   0],
        ['Palindrome Check', 'madam',    'Yes',  0],

        ['FizzBuzz', '5',  "1\n2\nFizz\n4\nBuzz",  1],

        ['Find Maximum', "5\n3 1 4 1 5", '5', 1],
        ['Find Maximum', "3\n7 2 9",     '9', 0],

        ['Prime Check', '17',  'Prime',     1],
        ['Prime Check', '4',   'Not Prime', 0],
        ['Prime Check', '1',   'Not Prime', 0],

        ['Two Sum', "4\n2 7 11 15\n9",  '0 1', 1],
        ['Two Sum', "3\n3 2 4\n6",      '1 2', 0],

        ['Balanced Brackets', '{[()]}',   'Balanced',     1],
        ['Balanced Brackets', '({[}])',   'Not Balanced', 0],
        ['Balanced Brackets', '',         'Balanced',     0],

        ['Missing Number', "5\n1 2 4 5", '3', 1],
        ['Missing Number', "3\n1 3",     '2', 0],

        ['Maximum Subarray Sum', "8\n-2 1 -3 4 -1 2 1 -5 4", '6', 1],
        ['Maximum Subarray Sum', "5\n1 2 3 4 5",               '15', 0],

        ['Anagram Check', "listen\nsilent", 'Yes', 1],
        ['Anagram Check', "hello\nworld",   'No',  0],

        ['Valid Parentheses', '({[]})', 'Valid',   1],
        ['Valid Parentheses', '([)]',   'Invalid', 0],

        ['Binary Search', "6\n1 3 5 7 9 11\n7",  '3',  1],
        ['Binary Search', "6\n1 3 5 7 9 11\n6",  '-1', 0],

        ['Longest Common Subsequence', "ABCBDAB\nBDCAB", '4', 1],
        ['Longest Common Subsequence', "AGGTAB\nGXTXAYB", '4', 0],

        ['Trapping Rain Water', "12\n0 1 0 2 1 0 1 3 2 1 2 1", '6', 1],
        ['Trapping Rain Water', "6\n3 0 2 0 4",                 '7', 0],

        ['Word Break', "leetcode\n3\nleet\ncode\nleetcode", 'Yes', 1],
        ['Word Break', "applepenapple\n2\napple\npen",     'Yes', 0],
        ['Word Break', "catsandog\n2\ncats\ndog",          'No',  0],

        ['LRU Cache', "2\n5\nPUT 1 10\nPUT 2 20\nGET 1\nPUT 3 30\nGET 2", "10\n-1", 1],
    ];

    $stTC = $conn->prepare("INSERT INTO coding_test_cases (problem_id, input, expected_output, is_sample) VALUES (?,?,?,?)");
    $inserted = 0;
    foreach ($testCases as $tc) {
        $pidRow = $conn->query("SELECT id FROM coding_problems WHERE title='" . $conn->real_escape_string($tc[0]) . "' LIMIT 1")->fetch_assoc();
        if (!$pidRow) continue;
        $pid = $pidRow['id'];
        $stTC->bind_param('issi', $pid, $tc[1], $tc[2], $tc[3]);
        $stTC->execute();
        $inserted++;
    }
    $stTC->close();
    $log[] = "✅ Seeded $inserted test cases";
} else {
    $log[] = "⏭ Test cases already exist ($tcExist rows), skipping";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Coding System Setup</title>
<style>
body { font-family: monospace; background: #1e1f2b; color: #f8f8f2; padding: 30px; }
h2   { color: #ffd54f; }
.log-item { padding: 4px 0; font-size: 0.95rem; }
.error { color: #ff5555; }
.done  { margin-top: 20px; background: #1b5e20; color: #69f0ae; padding: 14px 20px; border-radius: 8px; font-size: 1rem; }
a { color: #8be9fd; }
</style>
</head>
<body>
<h2>🔧 Coding System Setup</h2>
<?php foreach ($log as $l): ?>
<div class="log-item"><?= htmlspecialchars($l) ?></div>
<?php endforeach; ?>
<?php foreach ($errors as $e): ?>
<div class="log-item error"><?= htmlspecialchars($e) ?></div>
<?php endforeach; ?>
<?php if (empty($errors)): ?>
<div class="done">
    ✅ Setup complete! No errors.<br><br>
    → <a href="student/coding/index.php">Student Coding Page</a><br>
    → <a href="admin/coding/index.php">Admin Coding Management</a>
</div>
<?php else: ?>
<div style="background:#b71c1c;color:#fff;padding:14px 20px;border-radius:8px;margin-top:20px">
    ⚠️ Setup completed with <?= count($errors) ?> error(s). Check above.
</div>
<?php endif; ?>
</body>
</html>
