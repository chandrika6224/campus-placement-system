<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

if (!isLoggedIn()) { echo json_encode(['ok'=>false]); exit(); }

$uid = (int)$_SESSION['user_id'];
$action = $_POST['action'] ?? 'one';
$id     = (int)($_POST['id'] ?? 0);

if ($action === 'all') {
    $conn->query("UPDATE notifications SET is_read=1 WHERE user_id=$uid");
} elseif ($id > 0) {
    $conn->query("UPDATE notifications SET is_read=1 WHERE id=$id AND user_id=$uid");
}

echo json_encode(['ok' => true]);
