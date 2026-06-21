<?php
require_once 'includes/config.php';

$csvFile = __DIR__ . '/Sample.csv';
if (!file_exists($csvFile)) { die("Sample.csv not found."); }

$handle  = fopen($csvFile, 'r');
$rawHdr  = fgetcsv($handle);
// Normalise headers
$headers = array_map(fn($h) => strtolower(trim($h)), $rawHdr);

// Locate column indexes
$idxEmail    = array_search('email',           $headers);          // A (0)
$idxStream   = array_search('stream',          $headers);          // H (7) — student's own stream
$idxSkillsQ  = array_search('skills',          $headers);          // Q (16)
// Columns T and U are the SECOND occurrence of 'stream' and 'technical skills'
$idxStreamT  = false;
$idxTechSkills = false;
foreach ($headers as $k => $v) {
    if ($v === 'stream'          && $k !== $idxStream)   $idxStreamT    = $k; // col T
    if ($v === 'technical skills')                        $idxTechSkills = $k; // col U
}

$updated = 0;
$skipped = 0;
$errors  = [];

while (($row = fgetcsv($handle)) !== false) {
    $row     = array_pad($row, count($headers), '');
    $email   = trim($row[$idxEmail]   ?? '');
    // Department: use col H (student's own stream) — authoritative
    $dept    = trim($row[$idxStream]  ?? '');
    // Skills: prefer col U (Technical Skills for that stream), fall back to col Q
    $techSk  = ($idxTechSkills !== false) ? trim($row[$idxTechSkills] ?? '') : '';
    $skillsQ = ($idxSkillsQ    !== false) ? trim($row[$idxSkillsQ]    ?? '') : '';
    $skills  = ($techSk !== '' && strtolower($techSk) !== '#n/a') ? $techSk
             : (($skillsQ !== '' && strtolower($skillsQ) !== '#n/a') ? $skillsQ : '');

    if (empty($email) || empty($dept)) { $skipped++; continue; }

    // Find student by email
    $st = $conn->prepare("SELECT u.id FROM users u WHERE u.email=? AND u.role='student'");
    $st->bind_param('s', $email);
    $st->execute();
    $uid = $st->get_result()->fetch_assoc()['id'] ?? null;
    $st->close();

    if (!$uid) { $skipped++; continue; }

    $dept_e   = $conn->real_escape_string($dept);
    $skills_e = $conn->real_escape_string($skills);

    $conn->query("UPDATE student_profiles
        SET department = '$dept_e'
            " . ($skills !== '' ? ", skills = '$skills_e'" : "") . "
        WHERE user_id = $uid");

    if ($conn->affected_rows >= 0) $updated++;
    else { $errors[] = "Error for $email: ".$conn->error; $skipped++; }
}
fclose($handle);

echo "<div style='font-family:sans-serif;padding:24px;max-width:600px'>";
echo "<h3 style='color:#1a237e'>✅ Department & Skills Sync Complete</h3>";
echo "<p>✔ <strong>$updated</strong> students updated (department + skills)</p>";
echo "<p>⏭ <strong>$skipped</strong> skipped (not found or missing data)</p>";
if ($errors) {
    echo "<details><summary>Errors (".count($errors).")</summary><pre>".implode("\n",$errors)."</pre></details>";
}
echo "<br><a href='admin/students.php' style='color:#3f51b5'>→ Go to Students</a> &nbsp;|&nbsp;
      <a href='admin/reports.php' style='color:#3f51b5'>→ Go to Reports</a> &nbsp;|&nbsp;
      <a href='admin/dashboard.php' style='color:#3f51b5'>→ Go to Dashboard</a>";
echo "</div>";
