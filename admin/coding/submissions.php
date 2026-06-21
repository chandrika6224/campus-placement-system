<?php
require_once '../../includes/config.php';
requireLogin('admin');
header('Content-Type: application/json');

$pid = (int)($_GET['problem_id'] ?? 0);
if (!$pid) { echo json_encode(['total'=>0,'students'=>0,'submissions'=>[]]); exit(); }

$prob = $conn->query("SELECT title, description, difficulty, category, sample_input, sample_output, hints, tags, points FROM coding_problems WHERE id=$pid")->fetch_assoc();
if (!$prob) { echo json_encode(['total'=>0,'students'=>0,'submissions'=>[]]); exit(); }

$st = $conn->prepare("SELECT cs.*, u.name, u.email
    FROM coding_submissions cs
    JOIN users u ON cs.user_id = u.id
    WHERE cs.problem_id = ?
    ORDER BY cs.submitted_at DESC");
$st->bind_param('i', $pid); $st->execute();
$rows = $st->get_result()->fetch_all(MYSQLI_ASSOC); $st->close();

$students = count(array_unique(array_column($rows, 'user_id')));

echo json_encode([
    'total'       => count($rows),
    'students'    => $students,
    'problem'     => $prob,
    'submissions' => array_map(fn($r) => [
        'name'         => $r['name'],
        'email'        => $r['email'],
        'language'     => $r['language'],
        'code'         => $r['code'],
        'status'       => $r['status'],
        'points_earned'=> $r['points_earned'],
        'submitted_at' => date('d M Y, h:i A', strtotime($r['submitted_at'])),
    ], $rows),
]);
