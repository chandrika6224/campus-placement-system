<?php
require_once 'includes/config.php';

if (isLoggedIn()) { header('Location: student/dashboard.php'); exit(); }

$msg = ''; $msgType = '';
$prefillEmail = trim($_GET['email'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = 'Please enter a valid email address.';
        $msgType = 'error';
    } else {
        $st = $conn->prepare("SELECT id, name FROM users WHERE email=?");
        $st->bind_param('s', $email); $st->execute();
        $user = $st->get_result()->fetch_assoc(); $st->close();

        if (!$user) {
            // Don't reveal if email exists or not
            $msg = "If that email is registered, a reset link has been sent.";
            $msgType = 'success';
        } else {
            // Delete old tokens for this email
            $del = $conn->prepare("DELETE FROM password_resets WHERE email=?");
            $del->bind_param('s', $email); $del->execute(); $del->close();

            // Generate token
            $token   = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

            $ins = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?,?,?)");
            $ins->bind_param('sss', $email, $token, $expires); $ins->execute(); $ins->close();

            // Build reset link
            $protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host      = $_SERVER['HTTP_HOST'];
            $resetLink = "$protocol://$host/placement/reset_password.php?token=$token";

            // Send email using PHP mail()
            $to      = $email;
            $subject = "Password Reset - Campus Recruit";
            $name    = htmlspecialchars($user['name']);

            $body = "Hello $name,\n\n";
            $body .= "You requested a password reset for your Campus Recruit account.\n\n";
            $body .= "Click the link below to reset your password (valid for 24 hours):\n\n";
            $body .= "$resetLink\n\n";
            $body .= "If you did not request this, please ignore this email.\n\n";
            $body .= "Regards,\nCampus Recruit Team";

            $headers  = "From: noreply@campusrecruit.local\r\n";
            $headers .= "Reply-To: noreply@campusrecruit.local\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();

            $sent = mail($to, $subject, $body, $headers);

            // For local dev — store the reset link in session so we can show it
            $_SESSION['dev_reset_link'] = $resetLink;
            $_SESSION['dev_reset_name'] = $user['name'];

            $msg = "Password reset link has been sent to <strong>$email</strong>. Check your inbox (valid for 1 hour).";
            $msgType = 'success';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password - Campus Recruit</title>
<link rel="stylesheet" href="css/style.css">
<style>
.fp-page { min-height:100vh; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg,#1a237e,#3949ab); padding:20px; }
.fp-box { background:#fff; border-radius:16px; padding:40px 35px; width:100%; max-width:440px; box-shadow:0 10px 40px rgba(0,0,0,0.2); }
.fp-icon { text-align:center; font-size:3rem; margin-bottom:10px; }
.fp-box h2 { text-align:center; color:#1a237e; margin-bottom:6px; font-size:1.5rem; }
.fp-box p { text-align:center; color:#666; font-size:0.9rem; margin-bottom:24px; }
.dev-box { background:#fff8e1; border:2px dashed #fb8c00; border-radius:10px; padding:16px; margin-top:20px; }
.dev-box h4 { color:#e65100; font-size:0.88rem; margin:0 0 8px; }
.dev-box a { color:#1565c0; font-size:0.82rem; word-break:break-all; font-weight:600; }
</style>
</head>
<body>
<div class="fp-page">
    <div class="fp-box">
        <div class="fp-icon">🔑</div>
        <h2>Forgot Password?</h2>
        <p>Enter your registered email and we'll send you a link to reset your password (valid for 24 hours).</p>

        <?php if ($msg): ?>
        <div class="alert alert-<?= $msgType ?>" style="margin-bottom:18px"><?= $msg ?></div>
        <?php endif; ?>

        <?php if ($msgType !== 'success'): ?>
        <form method="POST">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" value="<?= htmlspecialchars($prefillEmail) ?>"
                    placeholder="Enter your registered email" required autofocus>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;padding:12px">
                📧 Send Reset Link
            </button>
        </form>
        <?php else: ?>
        <div style="text-align:center;margin-top:10px">
            <a href="index.php" class="btn btn-primary" style="border-radius:25px">← Back to Login</a>
        </div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['dev_reset_link'])): ?>
        <div class="dev-box">
            <h4>🛠️ LOCAL DEV — Reset Link (since email may not work on localhost):</h4>
            <p style="margin:4px 0;font-size:0.78rem;color:#555">For: <strong><?= htmlspecialchars($_SESSION['dev_reset_name']) ?></strong></p>
            <a href="<?= htmlspecialchars($_SESSION['dev_reset_link']) ?>"><?= htmlspecialchars($_SESSION['dev_reset_link']) ?></a>
        </div>
        <?php unset($_SESSION['dev_reset_link'], $_SESSION['dev_reset_name']); ?>
        <?php endif; ?>

        <div style="text-align:center;margin-top:20px">
            <a href="index.php" style="font-size:0.85rem;color:#666">← Back to Login</a>
        </div>
    </div>
</div>
</body>
</html>
