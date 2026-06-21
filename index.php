<?php
require_once 'includes/config.php';

if (isLoggedIn()) {
    if ($_SESSION['role'] === 'admin') header("Location: admin/dashboard.php");
    else header("Location: student/dashboard.php");
    exit();
}

// Redirect to homepage if visiting index.php directly without any action
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !isset($_GET['tab'])) {
    header("Location: home.php");
    exit();
}

$activeTab = isset($_GET['tab']) && $_GET['tab'] === 'register' ? 'register' : 'login';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $ip = substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45);
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250);

        $conn->query("CREATE TABLE IF NOT EXISTS login_activity (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            ip_address VARCHAR(45),
            user_agent VARCHAR(300),
            status ENUM('success','failed') DEFAULT 'success',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");

        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name']    = $user['name'];
                $_SESSION['role']    = $user['role'];
                $_SESSION['email']   = $user['email'];
                unset($_SESSION['failed_login_email']);
                $logStmt = $conn->prepare("INSERT INTO login_activity (user_id, ip_address, user_agent, status) VALUES (?, ?, ?, 'success')");
                $logStmt->bind_param('iss', $user['id'], $ip, $ua);
                $logStmt->execute();
                $logStmt->close();
                if ($user['role'] === 'admin') header("Location: admin/dashboard.php");
                elseif ($user['role'] === 'recruiter') {
                    $error = "Recruiters cannot login here. Please contact the Placement Cell directly.";
                } else header("Location: student/dashboard.php");
                exit();
            } else {
                $logStmt = $conn->prepare("INSERT INTO login_activity (user_id, ip_address, user_agent, status) VALUES (?, ?, ?, 'failed')");
                $logStmt->bind_param('iss', $user['id'], $ip, $ua);
                $logStmt->execute();
                $logStmt->close();
                $_SESSION['failed_login_email'] = $email;
                $error = "Invalid password.";
            }
        } else {
            $error = "No account found with this email.";
        }
        $stmt->close();
    }

    if ($action === 'register') {
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['reg_email'] ?? '');
        $rawPass  = $_POST['reg_password'] ?? '';
        $role     = $_POST['role'] ?? '';

        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', $rawPass)) {
            $error = "Password must be at least 8 characters and include uppercase, lowercase, a number, and a special character.";
        } elseif ($role !== 'student') {
            $error = "Invalid role. Only students can register.";
        } else {
            $password = password_hash($rawPass, PASSWORD_DEFAULT);
            $chk = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $chk->bind_param('s', $email);
            $chk->execute();
            $chk->store_result();
            if ($chk->num_rows > 0) {
                $error = "Email already registered.";
            } else {
                $ins = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'student')");
                $ins->bind_param('sss', $name, $email, $password);
                $ins->execute();
                $uid = $conn->insert_id;
                $ins->close();
                $department   = trim($_POST['department'] ?? '');
                $cgpa         = (float)($_POST['cgpa'] ?? 0);
                $year_passing = (int)($_POST['year_of_passing'] ?? 0);
                $sp = $conn->prepare("INSERT IGNORE INTO student_profiles (user_id, department, cgpa, year_of_passing) VALUES (?, ?, ?, ?)");
                $sp->bind_param('isdi', $uid, $department, $cgpa, $year_passing);
                $sp->execute();
                $sp->close();
                $success = "Account created successfully! Please login.";
            }
            $chk->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Campus Recruitment System</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="login-page">
    <div class="login-box">
        <div class="logo">
            <h1>🎓 Campus<span style="color:#3f51b5">Recruit</span></h1>
            <p>Campus Placement & Recruitment System</p>
            <p style="margin-top:8px"><a href="home.php" style="color:#3f51b5;font-size:0.85rem">← Back to Home</a></p>
        </div>

        <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

        <div class="login-tabs">
            <button class="active" onclick="showTab('login',this)">Login</button>
            <button onclick="showTab('register',this)">Sign Up</button>
        </div>

        <div id="login-tab">
            <form method="POST">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="Enter your email" required autocomplete="email">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%">Login</button>
                <div style="text-align:center;margin-top:12px">
                    <a href="forgot_password.php" style="font-size:0.85rem;color:#3f51b5">Forgot your password?</a>
                </div>
            </form>
        </div>

        <div id="register-tab" style="display:none">
            <form method="POST">
                <input type="hidden" name="action" value="register">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" placeholder="Enter your full name" required autocomplete="name">
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="reg_email" placeholder="Enter your email" required autocomplete="email">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="reg_password" id="reg_password" placeholder="Create a password" required autocomplete="new-password" oninput="checkRegStrength(this.value)">
                    <div id="reg-strength" style="margin-top:6px;font-size:0.82rem"></div>
                </div>
                <input type="hidden" name="role" value="student">

                <div id="student-fields">
                    <div class="form-group">
                        <label>Branch / Department</label>
                        <select name="department" id="branch-select" onchange="toggleOtherBranch(this.value)" required>
                            <option value="">-- Select Branch --</option>
                            <option>Computer Science Engineering</option>
                            <option>Information Technology</option>
                            <option>Electronics & Communication</option>
                            <option>Electrical Engineering</option>
                            <option>Mechanical Engineering</option>
                            <option>Civil Engineering</option>
                            <option>Chemical Engineering</option>
                            <option>Biotechnology</option>
                            <option>MBA</option>
                            <option>MCA</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group" id="other-branch-group" style="display:none">
                        <label>Specify Your Branch / Stream</label>
                        <input type="text" id="other-branch-input" placeholder="Enter your branch or stream" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>CGPA</label>
                        <input type="number" name="cgpa" placeholder="e.g. 8.5" min="0" max="10" step="0.01">
                    </div>
                    <div class="form-group">
                        <label>Year of Passing</label>
                        <select name="year_of_passing">
                            <option value="">-- Select Year --</option>
                            <?php for ($y = date('Y'); $y <= date('Y') + 4; $y++): ?>
                            <option value="<?= $y ?>"><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%">Sign Up</button>
            </form>
        </div>
    </div>
</div>
<script>
function checkRegStrength(pwd) {
    const bar = document.getElementById('reg-strength');
    if (!pwd.length) { bar.textContent = ''; return; }
    let passed = 0;
    if (pwd.length >= 8)            passed++;
    if (/[A-Z]/.test(pwd))          passed++;
    if (/[a-z]/.test(pwd))          passed++;
    if (/[0-9]/.test(pwd))          passed++;
    if (/[^A-Za-z0-9]/.test(pwd))   passed++;
    const labels = ['','Weak','Fair','Good','Strong','Very Strong'];
    const colors = ['','#c62828','#fb8c00','#1565c0','#2e7d32','#1b5e20'];
    bar.textContent = 'Strength: ' + (labels[passed] || 'Weak');
    bar.style.color = colors[passed] || '#c62828';
}
document.getElementById('reg_password').addEventListener('blur', function() {
    const pwd = this.value;
    const bar = document.getElementById('reg-strength');
    const missing = [];
    if (pwd.length < 8)             missing.push('at least 8 characters');
    if (!/[A-Z]/.test(pwd))         missing.push('1 uppercase letter');
    if (!/[a-z]/.test(pwd))         missing.push('1 lowercase letter');
    if (!/[0-9]/.test(pwd))         missing.push('1 number');
    if (!/[^A-Za-z0-9]/.test(pwd))  missing.push('1 special character (!@#$...)');
    if (missing.length) {
        bar.textContent = '⚠️ Password needs: ' + missing.join(', ');
        bar.style.color = '#c62828';
    }
});
document.querySelector('#register-tab form').addEventListener('submit', function(e) {
    const pwd = document.getElementById('reg_password').value;
    if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/.test(pwd)) {
        e.preventDefault();
        const missing = [];
        if (pwd.length < 8)             missing.push('at least 8 characters');
        if (!/[A-Z]/.test(pwd))         missing.push('1 uppercase letter');
        if (!/[a-z]/.test(pwd))         missing.push('1 lowercase letter');
        if (!/[0-9]/.test(pwd))         missing.push('1 number');
        if (!/[^A-Za-z0-9]/.test(pwd))  missing.push('1 special character (!@#$...)');
        const bar = document.getElementById('reg-strength');
        bar.textContent = '\u26a0\ufe0f Password needs: ' + missing.join(', ');
        bar.style.color = '#c62828';
        document.getElementById('reg_password').focus();
        return;
    }
    // If Other branch selected, inject the custom value into the select
    const branchSelect = document.getElementById('branch-select');
    if (branchSelect.value === 'Other') {
        const customBranch = document.getElementById('other-branch-input').value.trim();
        if (!customBranch) {
            e.preventDefault();
            document.getElementById('other-branch-input').focus();
            return;
        }
        // Add custom branch as a new option and select it
        const opt = new Option(customBranch, customBranch, true, true);
        branchSelect.add(opt);
        branchSelect.value = customBranch;
    }
});

function showTab(tab, btn) {
    document.getElementById('login-tab').style.display = tab === 'login' ? 'block' : 'none';
    document.getElementById('register-tab').style.display = tab === 'register' ? 'block' : 'none';
    document.querySelectorAll('.login-tabs button').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}
function toggleOtherBranch(val) {
    const grp = document.getElementById('other-branch-group');
    const inp = document.getElementById('other-branch-input');
    if (val === 'Other') {
        grp.style.display = 'block';
        inp.required = true;
        inp.focus();
    } else {
        grp.style.display = 'none';
        inp.required = false;
        inp.value = '';
    }
}
// Auto open register tab if ?tab=register
const params = new URLSearchParams(window.location.search);
if (params.get('tab') === 'register') {
    document.querySelectorAll('.login-tabs button')[1].click();
}
</script>
</body>
</html>
