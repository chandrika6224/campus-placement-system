<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'includes/config.php';

echo "<pre>";

// Step 1: Delete duplicates keeping only MIN id per user_id
$sql = "DELETE FROM student_profiles WHERE id NOT IN (
    SELECT id FROM (
        SELECT MIN(id) as id FROM student_profiles GROUP BY user_id
    ) AS keep_rows
)";
$result = $conn->query($sql);
if ($result === false) {
    echo "ERROR deleting duplicates: " . $conn->error . "\n";
} else {
    echo "✅ Removed " . $conn->affected_rows . " duplicate row(s)\n";
}

// Step 2: Add UNIQUE constraint
$res = $conn->query("SHOW INDEX FROM student_profiles WHERE Key_name = 'unique_user_id'");
if ($res && $res->num_rows === 0) {
    if ($conn->query("ALTER TABLE student_profiles ADD UNIQUE KEY unique_user_id (user_id)")) {
        echo "✅ UNIQUE constraint added\n";
    } else {
        echo "ERROR adding constraint: " . $conn->error . "\n";
    }
} else {
    echo "ℹ️ UNIQUE constraint already exists\n";
}

// Verify
$r1 = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='student'");
$r2 = $conn->query("SELECT COUNT(*) as c FROM student_profiles");
echo "\nUsers (students): " . $r1->fetch_assoc()['c'] . "\n";
echo "student_profiles: " . $r2->fetch_assoc()['c'] . "\n";

echo "</pre>";
echo "<a href='admin/students.php'>→ Go to Students</a>";
?>
