<?php
require_once '../includes/config.php';
require_once '../includes/notify.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['count' => 0, 'notifications' => []]);
    exit();
}

ensureNotificationsTable($conn);
$uid = (int)$_SESSION['user_id'];

$count = $conn->query("SELECT COUNT(*) as c FROM notifications WHERE user_id=$uid AND is_read=0")->fetch_assoc()['c'];

$result = $conn->query("SELECT * FROM notifications WHERE user_id=$uid ORDER BY created_at DESC LIMIT 15");
$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = [
        'id'         => $row['id'],
        'type'       => $row['type'],
        'title'      => $row['title'],
        'message'    => $row['message'],
        'link'       => $row['link'],
        'is_read'    => (int)$row['is_read'],
        'created_at' => $row['created_at'],
        'time_ago'   => timeAgo($row['created_at']),
    ];
}

echo json_encode(['count' => (int)$count, 'notifications' => $notifications]);

function timeAgo($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'Just now';
    if ($diff < 3600)   return floor($diff/60) . 'm ago';
    if ($diff < 86400)  return floor($diff/3600) . 'h ago';
    if ($diff < 604800) return floor($diff/86400) . 'd ago';
    return date('d M', strtotime($datetime));
}
