<?php
require_once 'includes/config.php';

if (isLoggedIn()) { header('Location: student/dashboard.php'); exit(); }

// Token comes from GET on first load, from POST hidden field on submit
$token   = trim($_POST['token'] ?? $_GET['token'] ?? '');
$msg     = ''; $msgType = '';
$valid   = false;
$email   = '';

// Validate token
if (!empty($token)) {
    $st = $conn->prepare("SELECT * FROM password_resets WHERE token=? AND used=0 AND expires_at > NOW()");
    $st->bind_param('s', $token); $st->execute();
    $reset = $st->get_result()->fetch_assoc(); $st->close();

    if ($reset) {
        $valid = true;
        $email = $reset['email'];
    } else {
        $msg = 'This reset link is invalid or has expired. Please request a new one.';
        $msgType = 'error';
    }
} else {
    $msg = 'No reset token provided.';
    $msgType = 'error';
}

// Handle password reset form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid) {
    $newPass  = $_POST['password']     ?? '';
    $confPass = $_POST['confirm_pass'] ?? '';

    if (strlen($newPass) < 8) {
        $msg = 'Password must be at least 8 characters.'; $msgType = 'error';
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', $newPass)) {
        $msg = 'Password must include uppercase, lowercase, a number and a special character.'; $msgType = 'error';
    } elseif ($newPass !== $confPass) {
        $msg = 'Passwords do not match.'; $msgType = 'error';
    } else {
        $hashed = password_hash($newPass, PASSWORD_DEFAULT);

        // Update user password
        $upd = $conn->prepare("UPDATE users SET password=? WHERE email=?");
        $upd->bind_param('ss', $hashed, $email); $upd->execute(); $upd->close();

        // Mark token as used
        $mark = $conn->prepare("UPDATE password_resets SET used=1 WHERE token=?");
        $mark->bind_param('s', $token); $mark->execute(); $mark->close();

        $msg = 'Your password has been reset successfully! You can now login.';
        $msgType = 'success';
        $valid = false; // Hide form
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password - Campus Recruit</title>
<link rel="stylesheet" href="css/style.css">
<style>
.rp-page { min-height:100vh; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg,#1a237e,#3949ab); padding:20px; }
.rp-box { background:#fff; border-radius:16px; padding:40px 35px; width:100%; max-width:440px; box-shadow:0 10px 40px rgba(0,0,0,0.2); }
.rp-icon { text-align:center; font-size:3rem; margin-bottom:10px; }
.rp-box h2 { text-align:center; color:#1a237e; margin-bottom:6px; font-size:1.5rem; }
.rp-box p { text-align:center; color:#666; font-size:0.9rem; margin-bottom:24px; }
.strength-bar { height:6px; border-radius:3px; background:#e0e0e0; margin-top:6px; overflow:hidden; }
.strength-fill { height:100%; border-radius:3px; transition:width 0.3s,background 0.3s; width:0; }
</style>
</head>
<body>
<div class="rp-page">
    <div class="rp-box">
        <div class="rp-icon">🔒</div>
        <h2>Reset Password</h2>
        <p>Enter your new password below.</p>

        <?php if ($msg): ?>
        <div class="alert alert-<?= $msgType ?>" style="margin-bottom:18px"><?= $msg ?></div>
        <?php endif; ?>

        <?php if ($valid): ?>
        <form method="POST" id="resetForm">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="password" id="newPass" placeholder="Create new password"
                    required oninput="checkStrength(this.value)" autofocus>
                <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                <div id="strengthText" style="font-size:0.78rem;margin-top:4px;color:#888"></div>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_pass" id="confPass" placeholder="Repeat new password"
                    required oninput="checkMatch()">
                <div id="matchText" style="font-size:0.78rem;margin-top:4px"></div>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;padding:12px">
                🔑 Reset Password
            </button>
        </form>
        <?php elseif ($msgType === 'success'): ?>
        <div style="text-align:center;margin-top:10px">
            <a href="index.php" class="btn btn-primary" style="border-radius:25px">→ Login Now</a>
        </div>
        <?php else: ?>
        <div style="text-align:center;margin-top:10px">
            <a href="forgot_password.php" class="btn btn-warning" style="border-radius:25px">Request New Link</a>
        </div>
        <?php endif; ?>

        <div style="text-align:center;margin-top:20px">
            <a href="index.php" style="font-size:0.85rem;color:#666">← Back to Login</a>
        </div>
    </div>
</div>
<script>
function checkStrength(pwd) {
    const fill = document.getElementById('strengthFill');
    const text = document.getElementById('strengthText');
    let score = 0;
    if (pwd.length >= 8)            score++;
    if (/[A-Z]/.test(pwd))          score++;
    if (/[a-z]/.test(pwd))          score++;
    if (/[0-9]/.test(pwd))          score++;
    if (/[^A-Za-z0-9]/.test(pwd))   score++;
    const pct   = (score / 5) * 100;
    const colors = ['', '#e53935', '#fb8c00', '#1565c0', '#2e7d32', '#1b5e20'];
    const labels = ['', 'Weak', 'Fair', 'Good', 'Strong', 'Very Strong'];
    fill.style.width     = pct + '%';
    fill.style.background = colors[score] || '#e53935';
    text.textContent     = pwd.length ? 'Strength: ' + (labels[score] || 'Weak') : '';
    text.style.color     = colors[score] || '#e53935';
}
function checkMatch() {
    const p1 = document.getElementById('newPass').value;
    const p2 = document.getElementById('confPass').value;
    const el = document.getElementById('matchText');
    if (!p2) { el.textContent = ''; return; }
    if (p1 === p2) { el.textContent = '✅ Passwords match'; el.style.color = '#2e7d32'; }
    else           { el.textContent = '❌ Passwords do not match'; el.style.color = '#c62828'; }
}
</script>
</body>
</html>
