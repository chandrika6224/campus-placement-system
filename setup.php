<?php
// Run this file ONCE to set up the database using config/schema.sql
// Visit: http://localhost/placement system/setup.php
// Then DELETE this file after setup

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'placementsystem');

// Connect without selecting DB first
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Create database if not exists
$conn->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
$conn->select_db(DB_NAME);

// Read schema.sql from config folder
$schemaFile = __DIR__ . '/config/schema.sql';
if (!file_exists($schemaFile)) {
    die("<p style='color:red'>❌ schema.sql not found in config/ folder.</p>");
}

$sql = file_get_contents($schemaFile);

// Split by semicolon and execute each statement
$statements = array_filter(array_map('trim', explode(';', $sql)));
$errors = [];
foreach ($statements as $stmt) {
    if (!empty($stmt) && stripos($stmt, '--') !== 0) {
        if (!$conn->query($stmt)) {
            $errors[] = $conn->error . " → " . substr($stmt, 0, 60);
        }
    }
}

echo "<!DOCTYPE html><html><head><title>Setup</title>
<style>body{font-family:sans-serif;max-width:600px;margin:50px auto;padding:20px}
.ok{color:green}.err{color:red}.warn{color:orange}
.box{background:#f5f5f5;padding:20px;border-radius:8px;border-left:4px solid #3f51b5}
a{color:#3f51b5}</style></head><body>";

echo "<div class='box'><h2>🎓 Campus Recruitment System — Setup</h2>";

if (empty($errors)) {
    echo "<p class='ok'>✅ Database <strong>campus_recruitment</strong> created successfully.</p>";
    echo "<p class='ok'>✅ All tables created from <strong>config/schema.sql</strong>.</p>";
    echo "<p class='ok'>✅ Admin account ready.</p>";
    echo "<hr>
    <p><strong>Admin Login:</strong><br>
    📧 Email: <code>admin@campus.com</code><br>
    🔑 Password: <code>password</code></p>
    <hr>
    <p><a href='index.php'>➡️ Go to Login Page</a></p>
    <p class='err'><strong>⚠️ Delete setup.php after use!</strong></p>";
} else {
    echo "<p class='warn'>⚠️ Setup completed with some notices:</p><ul>";
    foreach ($errors as $e) echo "<li class='err'>$e</li>";
    echo "</ul><p><a href='index.php'>➡️ Go to Login Page</a></p>";
}

echo "</div></body></html>";
?>
