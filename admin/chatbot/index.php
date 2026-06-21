<?php
require_once '../../includes/config.php';
requireLogin('admin');

$conn->query("CREATE TABLE IF NOT EXISTS chatbot_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    role VARCHAR(20),
    message TEXT,
    reply TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$logs = $conn->query("SELECT cl.*, u.name FROM chatbot_logs cl
    LEFT JOIN users u ON cl.user_id=u.id
    ORDER BY cl.created_at DESC LIMIT 200");

$stats = [
    'total'   => $conn->query("SELECT COUNT(*) as c FROM chatbot_logs")->fetch_assoc()['c'],
    'today'   => $conn->query("SELECT COUNT(*) as c FROM chatbot_logs WHERE DATE(created_at)=CURDATE()")->fetch_assoc()['c'],
    'students'=> $conn->query("SELECT COUNT(*) as c FROM chatbot_logs WHERE role='student'")->fetch_assoc()['c'],
    'unique'  => $conn->query("SELECT COUNT(DISTINCT user_id) as c FROM chatbot_logs")->fetch_assoc()['c'],
];

$topQ = $conn->query("SELECT message, COUNT(*) as cnt FROM chatbot_logs GROUP BY message ORDER BY cnt DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Chatbot Logs - Admin</title>
<link rel="stylesheet" href="../../css/style.css">
</head>
<body>
<nav class="navbar">
    <a href="../dashboard.php" class="brand">🎓 Campus<span>Recruit</span></a>
    <div class="nav-links">
        <a href="../dashboard.php">Dashboard</a>
        <a href="../students.php">Students</a>
        <a href="../aptitude/index.php">📝 Tests</a>
        <a href="../interviews/index.php">🎥 Interviews</a>
        <a href="../placement_prediction/index.php">🔮 Predictions</a>
        <a href="../skill_gap/index.php">🧩 Skill Gaps</a>
        <a href="index.php" class="active">💬 Chatbot</a>
        <a href="../reports.php">Reports</a>
        <a href="../logout.php" class="btn-logout">Logout</a>
    </div>
</nav>
<div class="container">
    <div class="card" style="background:linear-gradient(135deg,#1a237e,#3949ab);color:#fff;margin-bottom:25px">
        <h2 style="color:#ffd54f;margin-bottom:8px">💬 Chatbot Analytics & Logs</h2>
        <p style="color:#c5cae9">Monitor chatbot conversations and identify common student queries.</p>
    </div>

    <div class="stats-grid" style="grid-template-columns:repeat(4,1fr)">
        <div class="stat-card"><div class="number"><?= $stats['total'] ?></div><div class="label">💬 Total Messages</div></div>
        <div class="stat-card orange"><div class="number"><?= $stats['today'] ?></div><div class="label">📅 Today</div></div>
        <div class="stat-card green"><div class="number"><?= $stats['students'] ?></div><div class="label">👨🎓 Student Messages</div></div>
        <div class="stat-card"><div class="number"><?= $stats['unique'] ?></div><div class="label">👥 Unique Users</div></div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 2fr;gap:20px">
        <div class="card">
            <h2>🔥 Top Questions</h2>
            <?php $topQ->data_seek(0); while($q = $topQ->fetch_assoc()): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #f0f0f0;font-size:0.88rem">
                <span style="color:#333;flex:1"><?= htmlspecialchars(substr($q['message'],0,40)) ?><?= strlen($q['message'])>40?'...':'' ?></span>
                <span style="background:#e8eaf6;color:#3f51b5;padding:2px 8px;border-radius:10px;font-weight:700;font-size:0.78rem;margin-left:8px"><?= $q['cnt'] ?>x</span>
            </div>
            <?php endwhile; ?>
            <?php if ($stats['total'] == 0): ?>
            <p style="color:#999;text-align:center;padding:20px">No conversations yet.</p>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>📋 Recent Conversations</h2>
            <?php if ($logs->num_rows === 0): ?>
            <p style="color:#999;text-align:center;padding:30px">No chatbot conversations yet.</p>
            <?php else: ?>
            <div class="table-wrap">
                <table>
                    <tr><th>User</th><th>Role</th><th>Message</th><th>Time</th></tr>
                    <?php while($l = $logs->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($l['name'] ?? 'Guest') ?></td>
                        <td><span class="badge badge-<?= $l['role']==='student'?'open':($l['role']==='recruiter'?'shortlisted':'applied') ?>"><?= ucfirst($l['role'] ?? 'guest') ?></span></td>
                        <td style="max-width:250px;font-size:0.85rem"><?= htmlspecialchars(substr($l['message'],0,60)) ?><?= strlen($l['message'])>60?'...':'' ?></td>
                        <td style="font-size:0.82rem;color:#999"><?= date('d M, h:i A', strtotime($l['created_at'])) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require_once '../../chatbot/widget.php'; ?>
</body>
</html>
