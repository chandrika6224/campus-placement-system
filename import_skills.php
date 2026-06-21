<?php
require_once 'includes/config.php';

$csvFile = __DIR__ . '/Sample.csv';
$handle  = fopen($csvFile, 'r');
$headers = array_map('strtolower', array_map('trim', fgetcsv($handle)));

$updated = 0; $skipped = 0;

while (($row = fgetcsv($handle)) !== false) {
    if (count($row) < 2) continue;
    $data   = array_combine($headers, array_pad($row, count($headers), ''));
    $email  = trim($data['email'] ?? '');
    $skills = trim($data['skills'] ?? '');
    $dept   = trim($data['stream'] ?? '');

    if (empty($email) || empty($skills) || $skills === '#N/A') { $skipped++; continue; }

    $email_esc  = $conn->real_escape_string($email);
    $skills_esc = $conn->real_escape_string($skills);
    $dept_esc   = $conn->real_escape_string($dept);

    $user = $conn->query("SELECT id FROM users WHERE email='$email_esc' AND role='student'")->fetch_assoc();
    if (!$user) { $skipped++; continue; }

    $uid = $user['id'];
    $conn->query("UPDATE student_profiles SET skills='$skills_esc', department='$dept_esc' WHERE user_id=$uid");
    $updated++;
}
fclose($handle);

echo "<h3>Skills Import Done</h3>";
echo "<p>✅ Updated: <b>$updated</b></p>";
echo "<p>⏭ Skipped: <b>$skipped</b></p>";
echo "<p><a href='/placement/admin/dashboard.php'>Go to Dashboard</a></p>";
?>
