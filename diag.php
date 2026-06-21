<?php
require_once 'includes/config.php';

echo "<h3>Duplicate users (same email):</h3>";
$r = $conn->query("SELECT email, COUNT(*) as cnt FROM users WHERE role='student' GROUP BY email HAVING cnt > 1 ORDER BY cnt DESC LIMIT 20");
while($row = $r->fetch_assoc()) echo "Email: {$row['email']} — Count: {$row['cnt']}<br>";

echo "<h3>Duplicate student_profiles (same user_id):</h3>";
$r2 = $conn->query("SELECT user_id, COUNT(*) as cnt FROM student_profiles GROUP BY user_id HAVING cnt > 1 LIMIT 20");
while($row = $r2->fetch_assoc()) echo "user_id: {$row['user_id']} — Count: {$row['cnt']}<br>";

echo "<h3>Total users with role=student:</h3>";
$r3 = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role='student'");
echo $r3->fetch_assoc()['cnt'];

echo "<h3>Total student_profiles rows:</h3>";
$r4 = $conn->query("SELECT COUNT(*) as cnt FROM student_profiles");
echo $r4->fetch_assoc()['cnt'];
?>
