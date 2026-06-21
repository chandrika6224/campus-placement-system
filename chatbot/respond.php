<?php
require_once '../includes/config.php';
require_once 'engine.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['reply' => 'Invalid request.']);
    exit();
}

$message = trim($_POST['message'] ?? '');
if (empty($message)) {
    echo json_encode(['reply' => '💬 Please type a message!']);
    exit();
}

$role = $_SESSION['role'] ?? null;
$uid  = $_SESSION['user_id'] ?? null;

$bot   = new ChatbotEngine($conn, $role, $uid);
$reply = $bot->respond($message);

// Save to chat history
$conn->query("CREATE TABLE IF NOT EXISTS chatbot_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    role VARCHAR(20),
    message TEXT,
    reply TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

if ($uid) {
    $stLog = $conn->prepare("INSERT INTO chatbot_logs (user_id, role, message, reply) VALUES (?,?,?,?)");
    $safeRole = $role ?? 'guest';
    $stLog->bind_param('isss', $uid, $safeRole, $message, $reply);
    $stLog->execute(); $stLog->close();
}

echo json_encode(['reply' => $reply]);
