<?php
require_once '../includes/config.php';
requireLogin();

$uid  = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Create tables
$conn->query("CREATE TABLE IF NOT EXISTS login_activity (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(300),
    status ENUM('success','failed') DEFAULT 'success',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");
$conn->query("CREATE TABLE IF NOT EXISTS two_factor_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    is_enabled TINYINT DEFAULT 0,
    secret_code VARCHAR(10),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

$msg = '';

// Change password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new     = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    $user    = $conn->query("SELECT password FROM users WHERE id=$uid")->fetch_assoc();
    if (!password_verify($current, $user['password'])) {
        $msg = '<div class="alert alert-error">Current password is incorrect.</div>';
    } elseif (strlen($new) < 8) {
        $msg = '<div class="alert alert-error">New password must be at least 8 characters.</div>';
    } elseif ($new !== $confirm) {
        $msg = '<div class="alert alert-error">Passwords do not match.</div>';
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET password='$hash' WHERE id=$uid");
        // Log activity
        $ip = $conn->real_escape_string($_SERVER['REMOTE_ADDR'] ?? '');
        $conn->query("INSERT INTO login_activity (user_id, ip_address, user_agent, status) VALUES ($uid, '$ip', 'Password Changed', 'success')");
        $msg = '<div class="alert alert-success">Password changed successfully!</div>';
    }
}

// Toggle 2FA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_2fa'])) {
    $current = $conn->query("SELECT * FROM two_factor_settings WHERE user_id=$uid")->fetch_assoc();
    if ($current) {
        $newVal = $current['is_enabled'] ? 0 : 1;
        $conn->query("UPDATE two_factor_settings SET is_enabled=$newVal WHERE user_id=$uid");
        $msg = '<div class="alert alert-success">Two-factor authentication ' . ($newVal ? 'enabled' : 'disabled') . '.</div>';
    } else {
        $code = strtoupper(substr(md5(uniqid()), 0, 8));
        $conn->query("INSERT INTO two_factor_settings (user_id, is_enabled, secret_code) VALUES ($uid, 1, '$code')");
        $msg = '<div class="alert alert-success">Two-factor authentication enabled.</div>';
    }
}

$twoFa       = $conn->query("SELECT * FROM two_factor_settings WHERE user_id=$uid")->fetch_assoc();
$loginLogs   = $conn->query("SELECT * FROM login_activity WHERE user_id=$uid ORDER BY created_at DESC LIMIT 15");
$passwordAge = $conn->query("SELECT created_at FROM users WHERE id=$uid")->fetch_assoc();

// Security score
$score = 0;
$user  = $conn->query("SELECT * FROM users WHERE id=$uid")->fetch_assoc();
if (strlen($user['password']) > 0) $score += 25;
if ($twoFa && $twoFa['is_enabled']) $score += 35;
$profile = $conn->query("SELECT phone FROM student_profiles WHERE user_id=$uid")->fetch_assoc();
if ($profile && $profile['phone']) $score += 20;
if ($conn->query("SELECT COUNT(*) as c FROM login_activity WHERE user_id=$uid AND status='failed' AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['c'] == 0) $score += 20;

$dashLink   = ($role === 'admin') ? '../admin/dashboard.php' : (($role === 'recruiter') ? '../recruiter/dashboard.php' : '../student/dashboard.php');
$logoutLink = '../' . $role . '/logout.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Security Settings</title>
<link rel="stylesheet" href="../css/style.css">
<style>
.security-score-ring{width:120px;height:120px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.8rem;font-weight:800;margin:0 auto 10px;border:8px solid}
.toggle-switch{position:relative;display:inline-block;width:50px;height:26px}
.toggle-switch input{opacity:0;width:0;height:0}
.toggle-slider{position:absolute;cursor:pointer;inset:0;background:#ccc;border-radius:26px;transition:0.3s}
.toggle-slider:before{position:absolute;content:"";height:20px;width:20px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:0.3s}
input:checked + .toggle-slider{background:#43a047}
input:checked + .toggle-slider:before{transform:translateX(24px)}
</style>
</head>
<body>
<nav class="navbar">
    <a href="<?= $dashLink ?>" class="brand">🎓 Campus<span>Recruit</span></a>
    <div class="nav-links">
        <a href="<?= $dashLink ?>">Dashboard</a>
        <a href="index.php" class="active">🔒 Security</a>
        <?php require_once '../notifications/widget.php'; ?>
        <a href="<?= $logoutLink ?>" class="btn-logout">Logout</a>
    </div>
</nav>

<div class="container">
    <?= $msg ?>

    <div style="display:grid;grid-template-columns:280px 1fr;gap:20px">
        <!-- Security Score -->
        <div>
            <div class="card" style="text-align:center">
                <h2>🛡️ Security Score</h2>
                <div class="security-score-ring" style="border-color:<?= $score>=80?'#43a047':($score>=50?'#fb8c00':'#e53935') ?>;color:<?= $score>=80?'#2e7d32':($score>=50?'#e65100':'#c62828') ?>">
                    <?= $score ?>%
                </div>
                <div style="font-weight:700;color:<?= $score>=80?'#2e7d32':($score>=50?'#e65100':'#c62828') ?>;margin-bottom:15px">
                    <?= $score>=80?'Strong 💪':($score>=50?'Moderate ⚠️':'Weak ❌') ?>
                </div>
                <div style="text-align:left;font-size:0.85rem">
                    <?php
                    $checks = [
                        ['Password set', $score >= 25, '+25 pts'],
                        ['2FA enabled', $twoFa && $twoFa['is_enabled'], '+35 pts'],
                        ['Phone added', $profile && $profile['phone'], '+20 pts'],
                        ['No failed logins (7d)', $score >= 95, '+20 pts'],
                    ];
                    foreach ($checks as $c):
                    ?>
                    <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #f0f0f0">
                        <span><?= $c[1] ? '✅' : '❌' ?> <?= $c[0] ?></span>
                        <span style="color:#999;font-size:0.78rem"><?= $c[2] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 2FA Toggle -->
            <div class="card">
                <h2>🔐 Two-Factor Auth</h2>
                <p style="font-size:0.85rem;color:#555;margin-bottom:15px">Add an extra layer of security to your account.</p>
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <span style="font-weight:600;color:#1a237e"><?= ($twoFa && $twoFa['is_enabled']) ? '✅ Enabled' : '❌ Disabled' ?></span>
                    <form method="POST">
                        <label class="toggle-switch">
                            <input type="checkbox" <?= ($twoFa && $twoFa['is_enabled']) ? 'checked' : '' ?> onchange="this.form.submit()">
                            <span class="toggle-slider"></span>
                        </label>
                        <input type="hidden" name="toggle_2fa" value="1">
                    </form>
                </div>
                <?php if ($twoFa && $twoFa['is_enabled'] && $twoFa['secret_code']): ?>
                <div style="margin-top:12px;background:#e8f5e9;border-radius:8px;padding:10px;text-align:center">
                    <div style="font-size:0.78rem;color:#555;margin-bottom:4px">Your 2FA Secret Code</div>
                    <div style="font-size:1.3rem;font-weight:800;color:#1a237e;letter-spacing:3px"><?= $twoFa['secret_code'] ?></div>
                    <div style="font-size:0.72rem;color:#999;margin-top:4px">Keep this code safe</div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <!-- Change Password -->
            <div class="card">
                <h2>🔑 Change Password</h2>
                <form method="POST" style="max-width:450px">
                    <div class="form-group">
                        <label>Current Password *</label>
                        <input type="password" name="current_password" required placeholder="Enter current password">
                    </div>
                    <div class="form-group">
                        <label>New Password *</label>
                        <input type="password" name="new_password" required placeholder="Min 8 characters" id="newpwd" oninput="checkStrength(this.value)">
                        <div id="pwd-strength" style="margin-top:5px;font-size:0.8rem"></div>
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password *</label>
                        <input type="password" name="confirm_password" required placeholder="Repeat new password">
                    </div>
                    <button name="change_password" class="btn btn-primary">🔑 Change Password</button>
                </form>

                <div style="margin-top:20px;padding:14px;background:#e3f2fd;border-radius:8px;border-left:4px solid #1e88e5">
                    <div style="font-weight:700;color:#1565c0;margin-bottom:8px">🛡️ Password Tips</div>
                    <ul style="padding-left:18px;color:#555;font-size:0.83rem;line-height:2">
                        <li>Use at least 8 characters</li>
                        <li>Mix uppercase, lowercase, numbers & symbols</li>
                        <li>Never reuse passwords across sites</li>
                        <li>Change your password every 90 days</li>
                    </ul>
                </div>
            </div>

            <!-- Login Activity -->
            <div class="card">
                <h2>📋 Recent Login Activity</h2>
                <?php if ($loginLogs->num_rows === 0): ?>
                <p style="color:#999;text-align:center;padding:20px">No login activity recorded yet.</p>
                <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <tr><th>Date & Time</th><th>IP Address</th><th>Activity</th><th>Status</th></tr>
                        <?php while($l = $loginLogs->fetch_assoc()): ?>
                        <tr>
                            <td><?= date('d M Y, h:i A', strtotime($l['created_at'])) ?></td>
                            <td><?= htmlspecialchars($l['ip_address'] ?? 'N/A') ?></td>
                            <td style="font-size:0.82rem;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars(substr($l['user_agent'] ?? 'Login', 0, 60)) ?></td>
                            <td>
                                <span class="badge badge-<?= $l['status'] === 'success' ? 'selected' : 'rejected' ?>">
                                    <?= $l['status'] === 'success' ? '✅ Success' : '❌ Failed' ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../chatbot/widget.php'; ?>
<script>
function checkStrength(pwd) {
    const el = document.getElementById('pwd-strength');
    let score = 0;
    if (pwd.length >= 8) score++;
    if (/[A-Z]/.test(pwd)) score++;
    if (/[0-9]/.test(pwd)) score++;
    if (/[^A-Za-z0-9]/.test(pwd)) score++;
    const labels = ['','Weak ❌','Fair ⚠️','Good 👍','Strong 💪'];
    const colors = ['','#c62828','#fb8c00','#1565c0','#2e7d32'];
    el.textContent = pwd.length ? 'Strength: ' + (labels[score] || labels[1]) : '';
    el.style.color = colors[score] || colors[1];
}
</script>
</body>
</html>
