<?php
require_once 'includes/config.php';

// Ensure internships table has needed columns
$conn->query("ALTER TABLE internships ADD COLUMN IF NOT EXISTS location VARCHAR(150) DEFAULT NULL");
$conn->query("ALTER TABLE internships ADD COLUMN IF NOT EXISTS min_cgpa DECIMAL(4,2) DEFAULT 0");
$conn->query("ALTER TABLE internships ADD COLUMN IF NOT EXISTS allowed_streams TEXT DEFAULT NULL");
$conn->query("ALTER TABLE internships ADD COLUMN IF NOT EXISTS requirements TEXT DEFAULT NULL");

// Fetch all jobs with job_type = 'Internship'
$jobs = $conn->query("SELECT j.*, c.company_name FROM jobs j JOIN companies c ON j.company_id=c.id WHERE j.job_type='Internship'");

$moved = 0;
$skipped = 0;
$errors = [];

while ($j = $jobs->fetch_assoc()) {
    // Check if already exists in internships (by title + company_id)
    $exists = $conn->query("SELECT id FROM internships WHERE title='{$conn->real_escape_string($j['title'])}' AND company_id={$j['company_id']} LIMIT 1")->fetch_assoc();
    if ($exists) {
        $skipped++;
        continue;
    }

    $title       = $conn->real_escape_string($j['title']);
    $company_id  = (int)$j['company_id'];
    $location    = $conn->real_escape_string($j['location'] ?? '');
    $stipend     = $conn->real_escape_string($j['salary_range'] ?? '');
    $description = $conn->real_escape_string($j['description'] ?? '');
    $requirements= $conn->real_escape_string($j['requirements'] ?? '');
    $min_cgpa    = (float)($j['min_cgpa'] ?? 0);
    $deadline    = $j['deadline'] ? "'{$j['deadline']}'" : 'NULL';
    $status      = $conn->real_escape_string($j['status'] ?? 'open');

    $sql = "INSERT INTO internships (company_id, title, location, stipend, description, requirements, min_cgpa, deadline, status, duration)
            VALUES ($company_id, '$title', '$location', '$stipend', '$description', '$requirements', $min_cgpa, $deadline, '$status', '')";

    if ($conn->query($sql)) {
        $moved++;
    } else {
        $errors[] = "Failed to move: {$j['title']} — " . $conn->error;
    }
}

// Delete moved internship-type jobs from jobs table
if ($moved > 0) {
    $conn->query("DELETE FROM jobs WHERE job_type='Internship'");
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Internship Migration</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body style="padding:40px;max-width:700px;margin:0 auto">
<div class="card">
    <h2>🔄 Internship Migration</h2>
    <p style="color:#2e7d32;font-size:1rem">✅ <strong><?= $moved ?></strong> internship(s) moved from Jobs to Internships.</p>
    <?php if ($skipped > 0): ?>
    <p style="color:#e65100">⚠️ <strong><?= $skipped ?></strong> already existed in Internships — skipped.</p>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
    <div style="background:#ffebee;padding:12px;border-radius:8px;margin-top:10px">
        <?php foreach($errors as $e): ?>
        <p style="color:#c62828;font-size:0.88rem"><?= htmlspecialchars($e) ?></p>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <div style="margin-top:20px;display:flex;gap:10px">
        <a href="admin/internships/index.php" class="btn btn-primary">→ Go to Internships</a>
        <a href="admin/jobs.php" class="btn" style="background:#e8eaf6;color:#333">→ Go to Jobs</a>
    </div>
</div>
</body>
</html>
