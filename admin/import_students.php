<?php
require_once '../includes/config.php';
requireLogin('admin');

// Add new columns if they don't exist yet
$conn->query("ALTER TABLE student_profiles ADD COLUMN IF NOT EXISTS placed_salary DECIMAL(10,2) DEFAULT NULL");
$conn->query("ALTER TABLE student_profiles ADD COLUMN IF NOT EXISTS placed_company VARCHAR(200) DEFAULT NULL");
$conn->query("ALTER TABLE student_profiles ADD COLUMN IF NOT EXISTS placed_month_year VARCHAR(20) DEFAULT NULL");
$conn->query("ALTER TABLE student_profiles ADD COLUMN IF NOT EXISTS gender ENUM('Male','Female','Other') DEFAULT NULL");
$conn->query("ALTER TABLE student_profiles ADD COLUMN IF NOT EXISTS tenth_board VARCHAR(100) DEFAULT NULL");
$conn->query("ALTER TABLE student_profiles ADD COLUMN IF NOT EXISTS tenth_percent DECIMAL(5,2) DEFAULT NULL");
$conn->query("ALTER TABLE student_profiles ADD COLUMN IF NOT EXISTS twelfth_board VARCHAR(100) DEFAULT NULL");
$conn->query("ALTER TABLE student_profiles ADD COLUMN IF NOT EXISTS twelfth_percent DECIMAL(5,2) DEFAULT NULL");
$conn->query("ALTER TABLE student_profiles ADD COLUMN IF NOT EXISTS has_internship TINYINT DEFAULT 0");
$conn->query("ALTER TABLE student_profiles ADD COLUMN IF NOT EXISTS has_training TINYINT DEFAULT 0");
$conn->query("ALTER TABLE student_profiles ADD COLUMN IF NOT EXISTS backlogs INT DEFAULT 0");
$conn->query("ALTER TABLE student_profiles ADD COLUMN IF NOT EXISTS innovative_project TINYINT DEFAULT 0");
$conn->query("ALTER TABLE student_profiles ADD COLUMN IF NOT EXISTS communication_level INT DEFAULT 0");
$conn->query("ALTER TABLE student_profiles ADD COLUMN IF NOT EXISTS technical_course TINYINT DEFAULT 0");
$conn->query("ALTER TABLE student_profiles ADD COLUMN IF NOT EXISTS placement_status ENUM('Placed','Not Placed') DEFAULT NULL");

$results   = [];
$previewing = false;
$rows       = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── PREVIEW ──────────────────────────────────────────────
    if (isset($_POST['action']) && $_POST['action'] === 'preview') {
        if (!empty($_FILES['csv']['tmp_name'])) {
            $handle = fopen($_FILES['csv']['tmp_name'], 'r');
            $headers = array_map('strtolower', array_map('trim', fgetcsv($handle)));
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < 2) continue;
                $rows[] = array_combine($headers, array_pad($row, count($headers), ''));
            }
            fclose($handle);
            $previewing = true;
            // Store CSV in session for import step
            $_SESSION['csv_import'] = $rows;
        }

    // ── IMPORT ───────────────────────────────────────────────
    } elseif (isset($_POST['action']) && $_POST['action'] === 'import') {
        $rows = $_SESSION['csv_import'] ?? [];
        $inserted = 0; $skipped = 0; $errors = [];

        foreach ($rows as $i => $r) {
            $name     = trim($r['name'] ?? '');
            $email    = trim($r['email'] ?? '');
            $gender   = ucfirst(strtolower(trim($r['gender'] ?? '')));
            $tenth_b  = trim($r['10th board'] ?? '');
            $tenth_p  = floatval($r['10th marks'] ?? 0);
            $twlf_b   = trim($r['12th board'] ?? '');
            $twlf_p   = floatval($r['12th marks'] ?? 0);
            $dept     = trim($r['stream'] ?? '');
            $cgpa     = floatval($r['cgpa'] ?? 0);
            // cap cgpa to 10 (dataset has one row with 90)
            if ($cgpa > 10) $cgpa = round($cgpa / 10, 2);
            $intern   = (strtolower(trim($r['internships(y/n)'] ?? '')) === 'yes') ? 1 : 0;
            $training = (strtolower(trim($r['training(y/n)'] ?? '')) === 'yes') ? 1 : 0;
            $backlog  = (strtolower(trim($r['backlog in 5th sem'] ?? '')) === 'yes') ? 1 : 0;
            $proj     = (strtolower(trim($r['innovative project(y/n)'] ?? '')) === 'yes') ? 1 : 0;
            $comm     = intval($r['communication level'] ?? 0);
            $tech     = (strtolower(trim($r['technical course(y/n)'] ?? '')) === 'yes') ? 1 : 0;
            $placed   = (strtolower(trim($r['placement(y/n)?'] ?? $r['placement(y/n)'] ?? '')) === 'placed') ? 'Placed' : 'Not Placed';

            $tech_skills = trim($r['technical skills'] ?? '');
            $skills_raw  = trim($r['skills'] ?? '');
            // Prefer Technical Skills (col U), fall back to Skills (col Q)
            $skills_val  = ($tech_skills !== '' && strtolower($tech_skills) !== '#n/a')
                         ? $tech_skills
                         : (($skills_raw !== '' && strtolower($skills_raw) !== '#n/a') ? $skills_raw : '');

            if (empty($name)) { $errors[] = "Row " . ($i+1) . ": Name is empty, skipped."; $skipped++; continue; }

            // Use real email from dataset, fallback to generated
            if (empty($email)) {
                $slug  = strtolower(preg_replace('/[^a-z0-9]/i', '.', $name));
                $email = $slug . ($i+1) . '@student.campus.edu';
            }
            $email = $conn->real_escape_string($email);

            // Skip if email already exists
            if ($conn->query("SELECT id FROM users WHERE email='$email'")->num_rows > 0) {
                $skipped++; continue;
            }

            $name_esc  = $conn->real_escape_string($name);
            $gender    = in_array($gender, ['Male','Female','Other']) ? $gender : 'Other';
            $tenth_b   = $conn->real_escape_string($tenth_b);
            $twlf_b    = $conn->real_escape_string($twlf_b);
            $dept_esc  = $conn->real_escape_string($dept);
            // Password = first name + @123  e.g. Payal@123
            $first_name = explode(' ', $name)[0];
            $pass       = password_hash($first_name . '@123', PASSWORD_DEFAULT);

            $conn->query("INSERT INTO users (name, email, password, role) VALUES ('$name_esc', '$email', '$pass', 'student')");
            $uid = $conn->insert_id;

            if ($uid) {
                $conn->query("INSERT INTO student_profiles
                    (user_id, department, cgpa, gender, tenth_board, tenth_percent, twelfth_board, twelfth_percent,
                     has_internship, has_training, backlogs, innovative_project, communication_level, technical_course, placement_status, skills)
                    VALUES ($uid, '$dept_esc', $cgpa, '$gender', '$tenth_b', $tenth_p, '$twlf_b', $twlf_p,
                            $intern, $training, $backlog, $proj, $comm, $tech, '$placed',
                            '".$conn->real_escape_string($skills_val)."')
                    ON DUPLICATE KEY UPDATE
                        department=VALUES(department), cgpa=VALUES(cgpa), gender=VALUES(gender),
                        tenth_board=VALUES(tenth_board), tenth_percent=VALUES(tenth_percent),
                        twelfth_board=VALUES(twelfth_board), twelfth_percent=VALUES(twelfth_percent),
                        has_internship=VALUES(has_internship), has_training=VALUES(has_training),
                        backlogs=VALUES(backlogs), innovative_project=VALUES(innovative_project),
                        communication_level=VALUES(communication_level), technical_course=VALUES(technical_course),
                        placement_status=VALUES(placement_status), skills=VALUES(skills)");
                $inserted++;
            }
        }

        unset($_SESSION['csv_import']);
        $results = compact('inserted', 'skipped', 'errors');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Import Students - Admin</title>
<link rel="stylesheet" href="../css/style.css">
<style>
.import-wrap { max-width: 960px; margin: 0 auto; }
.upload-box {
    border: 2px dashed #c5cae9; border-radius: 12px;
    padding: 40px; text-align: center; background: #f8f9ff;
    transition: border-color 0.2s;
}
.upload-box:hover { border-color: #3f51b5; }
.upload-box input[type=file] { display: none; }
.upload-box label {
    cursor: pointer; display: inline-block;
    background: #3f51b5; color: #fff;
    padding: 10px 28px; border-radius: 25px;
    font-weight: 700; margin-top: 12px;
}
.upload-box .fname { margin-top: 10px; color: #3f51b5; font-weight: 600; font-size: 0.9rem; }
.col-map { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 0.85rem; margin: 14px 0; }
.col-map span { background: #e8eaf6; padding: 5px 12px; border-radius: 20px; color: #3f51b5; font-weight: 600; }
.preview-table { max-height: 320px; overflow-y: auto; }
.result-box { border-radius: 10px; padding: 20px; margin-bottom: 20px; }
.result-box.success { background: #e8f5e9; border-left: 4px solid #43a047; }
.result-box.warn    { background: #fff8e1; border-left: 4px solid #fb8c00; }
</style>
</head>
<body>
<nav class="navbar">
    <a href="dashboard.php" class="brand">🎓 Campus<span>Recruit</span></a>
    <div class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="students.php">Students</a>
        <a href="import_students.php" class="active">Import</a>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>
</nav>

<div class="container">
<div class="import-wrap">

<?php if (!empty($results)): ?>
    <div class="result-box success">
        <h3>✅ Import Complete</h3>
        <p>✔ <strong><?= $results['inserted'] ?></strong> students imported successfully.</p>
        <p>⏭ <strong><?= $results['skipped'] ?></strong> rows skipped (duplicates or empty names).</p>
        <p style="font-size:0.85rem;color:#555">Password format: <code>FirstName@123</code> &nbsp;e.g. Payal → <code>Payal@123</code></p>
    </div>
    <?php if (!empty($results['errors'])): ?>
    <div class="result-box warn">
        <strong>Notices:</strong>
        <ul style="margin-top:8px;font-size:0.85rem">
            <?php foreach ($results['errors'] as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    <a href="students.php" class="btn btn-primary">View All Students →</a>
    &nbsp;
    <a href="import_students.php" class="btn btn-warning">Import More</a>

<?php elseif ($previewing && !empty($rows)): ?>
    <div class="card">
        <h2>📋 Preview (<?= count($rows) ?> rows)</h2>
        <p style="color:#666;margin-bottom:14px">Review the data below then click <strong>Import All</strong> to proceed.</p>
        <div class="preview-table table-wrap">
            <table>
                <tr>
                    <th>#</th><th>Name</th><th>Email</th><th>Gender</th><th>Stream</th>
                    <th>CGPA</th><th>10th%</th><th>12th%</th><th>Internship</th><th>Placement</th>
                </tr>
                <?php foreach (array_slice($rows, 0, 50) as $i => $r):
                    $name   = $r['name'] ?? '-';
                    $email  = $r['email'] ?? '-';
                    $gen    = $r['gender'] ?? '-';
                    $dept   = $r['stream'] ?? '-';
                    $cgpa   = $r['cgpa'] ?? '-';
                    $tp     = $r['10th marks'] ?? '-';
                    $twp    = $r['12th marks'] ?? '-';
                    $int    = $r['internships(y/n)'] ?? '-';
                    $placed = $r['placement(y/n)?'] ?? $r['placement(y/n)'] ?? '-';
                ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <td><?= htmlspecialchars($name) ?></td>
                    <td><?= htmlspecialchars($email) ?></td>
                    <td><?= htmlspecialchars($gen) ?></td>
                    <td><?= htmlspecialchars($dept) ?></td>
                    <td><?= htmlspecialchars($cgpa) ?></td>
                    <td><?= htmlspecialchars($tp) ?></td>
                    <td><?= htmlspecialchars($twp) ?></td>
                    <td><?= htmlspecialchars($int) ?></td>
                    <td><?= htmlspecialchars($placed) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (count($rows) > 50): ?>
                <tr><td colspan="10" style="text-align:center;color:#999;font-style:italic">... and <?= count($rows)-50 ?> more rows</td></tr>
                <?php endif; ?>
            </table>
        </div>
        <br>
        <form method="POST">
            <input type="hidden" name="action" value="import">
            <button type="submit" class="btn btn-primary" style="padding:12px 35px">
                ✅ Import All <?= count($rows) ?> Students
            </button>
            &nbsp;
            <a href="import_students.php" class="btn btn-warning">Cancel</a>
        </form>
    </div>

<?php else: ?>
    <div class="card">
        <h2>📥 Import Students from CSV</h2>
        <p style="color:#666;margin-bottom:20px">Upload your student dataset CSV file. The system will auto-detect columns.</p>

        <div style="margin-bottom:20px">
            <strong>Expected CSV Columns (matching your Sample.csv):</strong>
            <div class="col-map">
                <span>Email</span>
                <span>Name</span>
                <span>Gender</span>
                <span>10th board</span>
                <span>10th marks</span>
                <span>12th board</span>
                <span>12th marks</span>
                <span>Stream</span>
                <span>Cgpa</span>
                <span>Internships(Y/N)</span>
                <span>Training(Y/N)</span>
                <span>Backlog in 5th sem</span>
                <span>Innovative Project(Y/N)</span>
                <span>Communication level</span>
                <span>Technical Course(Y/N)</span>
                <span>Placement(Y/N)?</span>
            </div>
            <p style="font-size:0.82rem;color:#999">Column names are case-insensitive. Extra columns are safely ignored.</p>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="preview">
            <div class="upload-box">
                <div style="font-size:3rem">📂</div>
                <p style="color:#666;margin:8px 0">Drag & drop your CSV file or click to browse</p>
                <input type="file" name="csv" id="csvFile" accept=".csv" required onchange="showName(this)">
                <label for="csvFile">Choose CSV File</label>
                <div class="fname" id="fname"></div>
            </div>
            <br>
            <button type="submit" class="btn btn-primary" style="padding:12px 35px">
                👁 Preview Data →
            </button>
        </form>
    </div>
<?php endif; ?>

</div>
</div>

<script>
function showName(input) {
    document.getElementById('fname').textContent = input.files[0]?.name ?? '';
}
</script>
</body>
</html>
