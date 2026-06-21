<?php
require_once 'includes/config.php';

// Delete duplicate applications — keep only the latest one per student per job
$conn->query("DELETE a1 FROM applications a1
    INNER JOIN applications a2
    WHERE a1.student_id = a2.student_id
    AND a1.job_id = a2.job_id
    AND a1.id < a2.id");

$deleted = $conn->affected_rows;
echo "✅ Done. Removed <strong>$deleted</strong> duplicate application(s).<br><br>";
echo "<a href='admin/shortlist/index.php'>→ Go to Shortlist & Approval</a>";
?>
