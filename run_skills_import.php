<?php
// Run from browser: http://localhost/placement/run_skills_import.php
$host = 'localhost'; $user = 'root'; $pass = ''; $db = 'placementsystem';
$conn = new mysqli($host, $user, $pass, $db);

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

    $email_esc  = $conn->real_escape_string($email);
    $dept_esc   = $conn->real_escape_string($dept);

    // Clean up skills - replace #N/A with empty
    if (empty($email) || $skills === '#N/A') { $skills = ''; }
    $skills_esc = $conn->real_escape_string($skills);

    $user = $conn->query("SELECT id FROM users WHERE email='$email_esc' AND role='student'")->fetch_assoc();
    if (!$user) { $skipped++; continue; }

    $uid = $user['id'];
    if (!empty($skills)) {
        $conn->query("UPDATE student_profiles SET skills='$skills_esc', department='$dept_esc' WHERE user_id=$uid");
    } else {
        $conn->query("UPDATE student_profiles SET department='$dept_esc' WHERE user_id=$uid");
    }
    $updated++;
}
fclose($handle);

$withSkills = $conn->query("SELECT COUNT(*) as c FROM student_profiles WHERE skills IS NOT NULL AND skills != ''")->fetch_assoc()['c'];

echo "<h2>✅ Import Done</h2>";
echo "<p>Updated: <b>$updated</b> students</p>";
echo "<p>Skipped: <b>$skipped</b></p>";
echo "<p>Students with skills now: <b>$withSkills</b></p>";
echo "<br><a href='/placement/admin/dashboard.php'>→ Admin Dashboard</a> &nbsp; <a href='/placement/admin/students.php'>→ View Students</a>";
?>
