<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'placementsystem');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin($role = null) {
    if (!isLoggedIn()) {
        throw new RuntimeException('Unauthenticated');
    }
    if ($role && $_SESSION['role'] !== $role) {
        throw new RuntimeException('Unauthorized role');
    }
}

function sanitize($data) {
    global $conn;
    return $conn->real_escape_string(htmlspecialchars(strip_tags(trim($data))));
}

// One-time schema migrations (compatible with MySQL < 8.0)
if ($conn->query("SHOW COLUMNS FROM student_profiles LIKE 'backlogs'")->num_rows === 0) {
    $conn->query("ALTER TABLE student_profiles ADD COLUMN backlogs INT DEFAULT 0");
}
?>
