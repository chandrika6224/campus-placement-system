<?php
require_once 'includes/config.php';

// Insert attendance for Payal Roy (payal_roy79@gmail.com) — CGPA 7.37 >= 6.00
$res = $conn->query("SELECT id FROM users WHERE email='payal_roy79@gmail.com'");
$uid = (int)$res->fetch_assoc()['id'];

$conn->query("INSERT INTO student_attendance (user_id, attendance_pct, backlogs)
    VALUES ($uid, 82.00, 0)
    ON DUPLICATE KEY UPDATE attendance_pct=82.00, backlogs=0");

echo "Done! Attendance set for Payal Roy (uid=$uid).<br>";
echo "<br><strong>Test Login:</strong><br>";
echo "Email: payal_roy79@gmail.com<br>";
echo "Password: Payal@123<br>";
echo "Then go to: Eligibility tab in sidebar<br>";
?>
