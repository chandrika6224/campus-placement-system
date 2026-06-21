<?php
require_once 'includes/config.php';

if (isLoggedIn()) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");
        exit();
    } else {
        header("Location: student/dashboard.php");
        exit();
    }
}

// Stats for homepage — use prepared statements to avoid SQL injection warnings
$role_student = 'student';
$stmt = $conn->prepare("SELECT COUNT(*) as c FROM users WHERE role = ?");
$stmt->bind_param('s', $role_student);
$stmt->execute();
$total_students = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
$stmt->close();

$res = $conn->query("SELECT COUNT(*) as c FROM companies");
$total_companies = $res ? (int)($res->fetch_assoc()['c'] ?? 0) : 0;

$status_open = 'open';
$stmt2 = $conn->prepare("SELECT COUNT(*) as c FROM jobs WHERE status = ?");
$stmt2->bind_param('s', $status_open);
$stmt2->execute();
$total_jobs = (int)($stmt2->get_result()->fetch_assoc()['c'] ?? 0);
$stmt2->close();

$res3 = $conn->query("SELECT COUNT(DISTINCT uid) as c FROM (SELECT student_id as uid FROM applications WHERE status='selected' UNION SELECT user_id as uid FROM student_profiles WHERE placement_status='Placed') t");
$total_placed = $res3 ? (int)($res3->fetch_assoc()['c'] ?? 0) : 0;

$res2 = $conn->query("SELECT COUNT(*) as c FROM applications");
$total_applications = $res2 ? (int)($res2->fetch_assoc()['c'] ?? 0) : 0;

// Recent job listings
$recent_jobs = $conn->query("SELECT j.*, c.company_name, c.industry FROM jobs j JOIN companies c ON j.company_id=c.id WHERE j.status='open' ORDER BY j.created_at DESC LIMIT 6");

// Recent notices
$notices = $conn->query("SELECT * FROM notices ORDER BY created_at DESC LIMIT 3");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Campus Recruitment System</title>
<link rel="stylesheet" href="css/style.css">
<style>
/* Hero Section */
.hero {
    background: linear-gradient(135deg, #1a237e 0%, #283593 50%, #3949ab 100%);
    color: #fff;
    padding: 90px 20px;
    text-align: center;
}
.hero h1 { font-size: 2.8rem; font-weight: 800; margin-bottom: 15px; }
.hero h1 span { color: #ffd54f; }
.hero p { font-size: 1.15rem; color: #c5cae9; max-width: 600px; margin: 0 auto 35px; }
.hero-btns { display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; }
.hero-btns .btn-hero {
    padding: 13px 35px; border-radius: 30px; font-size: 1rem;
    font-weight: 700; text-decoration: none; transition: all 0.3s;
}
.btn-hero-primary { background: #ffd54f; color: #1a237e; }
.btn-hero-primary:hover { background: #ffca28; transform: translateY(-2px); }
.btn-hero-outline { border: 2px solid #fff; color: #fff; }
.btn-hero-outline:hover { background: #fff; color: #1a237e; transform: translateY(-2px); }

/* Public Navbar */
.pub-navbar {
    background: #1a237e;
    padding: 0 40px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 65px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.3);
    position: sticky; top: 0; z-index: 100;
}
.pub-navbar .brand { color: #fff; font-size: 1.4rem; font-weight: 800; text-decoration: none; }
.pub-navbar .brand span { color: #ffd54f; }
.pub-nav-links { display: flex; gap: 5px; align-items: center; }
.pub-nav-links a {
    color: #c5cae9; text-decoration: none; padding: 8px 18px;
    border-radius: 6px; font-size: 0.95rem; font-weight: 600; transition: all 0.2s;
}
.pub-nav-links a:hover { color: #fff; background: rgba(255,255,255,0.1); }
.pub-nav-links .btn-nav-login {
    background: transparent; border: 2px solid #ffd54f; color: #ffd54f; border-radius: 20px;
}
.pub-nav-links .btn-nav-login:hover { background: #ffd54f; color: #1a237e; }
.pub-nav-links .btn-nav-register {
    background: #ffd54f; color: #1a237e; border-radius: 20px;
}
.pub-nav-links .btn-nav-register:hover { background: #ffca28; }

/* Stats Section */
.stats-section { background: #fff; padding: 60px 20px; }
.stats-section h2 { text-align: center; color: #1a237e; font-size: 2rem; margin-bottom: 10px; }
.stats-section .sub { text-align: center; color: #666; margin-bottom: 40px; }
.stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 25px; max-width: 1000px; margin: 0 auto; }
.stat-box {
    text-align: center; padding: 30px 20px; border-radius: 12px;
    background: linear-gradient(135deg, #e8eaf6, #f5f5f5);
    border-bottom: 4px solid #3f51b5; transition: transform 0.2s;
}
.stat-box:hover { transform: translateY(-5px); }
.stat-box .icon { font-size: 2.5rem; margin-bottom: 10px; }
.stat-box .num { font-size: 2.2rem; font-weight: 800; color: #1a237e; }
.stat-box .lbl { color: #555; font-size: 0.95rem; margin-top: 5px; font-weight: 600; }
.stat-box.green { border-bottom-color: #43a047; background: linear-gradient(135deg, #e8f5e9, #f5f5f5); }
.stat-box.orange { border-bottom-color: #fb8c00; background: linear-gradient(135deg, #fff8e1, #f5f5f5); }
.stat-box.red { border-bottom-color: #e53935; background: linear-gradient(135deg, #ffebee, #f5f5f5); }
.stat-box.teal { border-bottom-color: #00897b; background: linear-gradient(135deg, #e0f2f1, #f5f5f5); }

/* Jobs Section */
.jobs-section { background: #fff; padding: 60px 20px; }
.jobs-section h2 { text-align: center; color: #1a237e; font-size: 2rem; margin-bottom: 10px; }
.jobs-section .sub { text-align: center; color: #666; margin-bottom: 40px; }

/* Roles Section */
.roles-section { background: linear-gradient(135deg, #1a237e, #3949ab); padding: 60px 20px; }
.roles-section h2 { text-align: center; color: #fff; font-size: 2rem; margin-bottom: 10px; }
.roles-section .sub { text-align: center; color: #c5cae9; margin-bottom: 40px; }
.roles-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px; max-width: 1000px; margin: 0 auto; }
.role-card {
    background: rgba(255,255,255,0.1); border-radius: 12px; padding: 30px;
    border: 1px solid rgba(255,255,255,0.2); transition: all 0.3s;
}
.role-card:hover { background: rgba(255,255,255,0.18); transform: translateY(-5px); }
.role-card .icon { font-size: 2.5rem; margin-bottom: 15px; }
.role-card h3 { color: #ffd54f; margin-bottom: 12px; font-size: 1.2rem; }
.role-card ul { list-style: none; padding: 0; }
.role-card ul li { color: #c5cae9; font-size: 0.9rem; padding: 5px 0; border-bottom: 1px solid rgba(255,255,255,0.1); }
.role-card ul li::before { content: "✓ "; color: #69f0ae; font-weight: 700; }

/* Notices Section */
.notices-section { background: #f0f2f5; padding: 60px 20px; }
.notices-section h2 { text-align: center; color: #1a237e; font-size: 2rem; margin-bottom: 10px; }
.notices-section .sub { text-align: center; color: #666; margin-bottom: 40px; }
.notices-wrap { max-width: 800px; margin: 0 auto; }

/* CTA Section */
.cta-section { background: #fff; padding: 70px 20px; text-align: center; }
.cta-section h2 { color: #1a237e; font-size: 2rem; margin-bottom: 15px; }
.cta-section p { color: #666; font-size: 1rem; margin-bottom: 30px; }

/* Footer */
footer {
    background: #1a237e; color: #c5cae9; text-align: center;
    padding: 25px 20px; font-size: 0.9rem;
}
footer span { color: #ffd54f; }

/* Counter animation */
.num { transition: all 0.5s; }
</style>
</head>
<body>

<!-- Navbar -->
<nav class="pub-navbar">
    <a href="home.php" class="brand">🎓 Campus<span>Recruit</span></a>
    <div class="pub-nav-links">
        <a href="home.php">Home</a>
        <a href="#about">About</a>
        <a href="#jobs">Jobs</a>
        <a href="#contact">Contact</a>
        <a href="index.php?tab=login" class="btn-nav-login">Login</a>
        <a href="index.php?tab=register" class="btn-nav-register">Sign Up</a>
    </div>
</nav>

<!-- Hero -->
<section class="hero">
    <h1>Welcome to <span>Campus Recruit</span></h1>
    <p>Bridging the gap between talented students and top companies. Your dream career starts here.</p>
    <div class="hero-btns">
        <a href="index.php?tab=register" class="btn-hero btn-hero-primary">🎓 Sign Up</a>
        <a href="index.php?tab=login" class="btn-hero btn-hero-outline">🔑 Login</a>
    </div>
</section>

<!-- Stats -->
<section class="stats-section">
    <h2>Our Platform at a Glance</h2>
    <p class="sub">Real-time numbers from our campus placement system</p>
    <div class="stats-row">
        <div class="stat-box">
            <div class="icon">👨‍🎓</div>
            <div class="num" id="s1"><?= $total_students ?></div>
            <div class="lbl">Registered Students</div>
        </div>
        <div class="stat-box orange">
            <div class="icon">🏢</div>
            <div class="num" id="s2"><?= $total_companies ?></div>
            <div class="lbl">Companies</div>
        </div>
        <div class="stat-box green">
            <div class="icon">💼</div>
            <div class="num" id="s3"><?= $total_jobs ?></div>
            <div class="lbl">Open Jobs</div>
        </div>
        <div class="stat-box red">
            <div class="icon">✅</div>
            <div class="num" id="s4"><?= $total_placed ?></div>
            <div class="lbl">Students Placed</div>
        </div>
        <div class="stat-box teal">
            <div class="icon">📋</div>
            <div class="num" id="s5"><?= $total_applications ?></div>
            <div class="lbl">Total Applications</div>
        </div>
    </div>
</section>



<!-- Latest Jobs -->
<section class="jobs-section" id="jobs">
    <h2>Latest Job Openings</h2>
    <p class="sub">Recently posted opportunities from top companies</p>
    <?php if ($recent_jobs && $recent_jobs->num_rows > 0): ?>
    <div class="job-grid" style="max-width:1100px;margin:0 auto">
        <?php while($j = $recent_jobs->fetch_assoc()): ?>
        <div class="job-card">
            <h3><?= htmlspecialchars($j['title']) ?></h3>
            <div class="company">🏢 <?= htmlspecialchars($j['company_name']) ?> · <?= htmlspecialchars($j['industry'] ?? '') ?></div>
            <div class="meta">
                <span>📍 <?= htmlspecialchars($j['location'] ?? 'N/A') ?></span>
                <span>💼 <?= htmlspecialchars($j['job_type'] ?? '') ?></span>
                <?php if ($j['salary_range']): ?><span>💰 <?= htmlspecialchars($j['salary_range']) ?></span><?php endif; ?>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:10px">
                <small style="color:#999">Deadline: <?= $j['deadline'] ? date('d M Y', strtotime($j['deadline'])) : 'Open' ?></small>
                <a href="index.php?tab=login" class="btn btn-primary btn-sm">Login to Apply</a>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php else: ?>
    <p style="text-align:center;color:#999;padding:30px">No jobs posted yet. <a href="index.php">Register as a recruiter</a> to post jobs.</p>
    <?php endif; ?>
    <div style="text-align:center;margin-top:30px">
        <a href="index.php?tab=login" class="btn btn-primary">View All Jobs →</a>
    </div>
</section>

<!-- Roles -->
<section class="roles-section" style="display:none">
    <h2>Who Can Use This Platform?</h2>
    <p class="sub">Three roles, one powerful system</p>
    <div class="roles-grid">
        <div class="role-card">
            <div class="icon">👨‍🎓</div>
            <h3>Students</h3>
            <ul>
                <li>Create and manage profile</li>
                <li>Upload resume</li>
                <li>Browse & search jobs</li>
                <li>Apply with one click</li>
                <li>Track application status</li>
                <li>View placement notices</li>
            </ul>
        </div>
        <div class="role-card">
            <div class="icon">🏢</div>
            <h3>Recruiters</h3>
            <ul>
                <li>Set up company profile</li>
                <li>Post job openings</li>
                <li>View all applicants</li>
                <li>Download resumes</li>
                <li>Shortlist & select candidates</li>
                <li>Manage job status</li>
            </ul>
        </div>
        <div class="role-card">
            <div class="icon">🛡️</div>
            <h3>Admin</h3>
            <ul>
                <li>Manage all users</li>
                <li>Monitor all jobs & applications</li>
                <li>Post placement notices</li>
                <li>View placement reports</li>
                <li>Department-wise statistics</li>
                <li>Full system control</li>
            </ul>
        </div>
    </div>
</section>

<!-- Notices -->
<?php if ($notices && $notices->num_rows > 0): ?>
<section class="notices-section">
    <h2>📢 Latest Notices</h2>
    <p class="sub">Important announcements from the placement cell</p>
    <div class="notices-wrap">
        <?php while($n = $notices->fetch_assoc()): ?>
        <div class="notice-item">
            <h4><?= htmlspecialchars($n['title']) ?></h4>
            <p style="margin:6px 0;color:#555;font-size:0.9rem"><?= htmlspecialchars(substr($n['content'], 0, 150)) ?>...</p>
            <div class="date"><?= date('d M Y', strtotime($n['created_at'])) ?></div>
        </div>
        <?php endwhile; ?>
        <div style="text-align:center;margin-top:20px">
            <a href="index.php?tab=login" class="btn btn-primary">Login to View All Notices →</a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- CTA -->
<section class="cta-section">
    <h2>Ready to Get Started?</h2>
    <p>Join hundreds of students and companies already using Campus Recruit.</p>
    <div style="display:flex;gap:15px;justify-content:center;flex-wrap:wrap">
        <a href="index.php?tab=register" class="btn btn-primary" style="border-radius:30px;padding:12px 35px">Sign Up Now →</a>
        <a href="index.php?tab=login" class="btn btn-warning" style="border-radius:30px;padding:12px 35px">Login</a>
    </div>
</section>

<!-- About -->
<section id="about" style="background:#f0f2f5;padding:60px 20px">
    <div style="max-width:860px;margin:0 auto;text-align:center">
        <h2 style="color:#1a237e;font-size:2rem;margin-bottom:10px">About the Placement Cell</h2>
        <div style="width:60px;height:4px;background:#3f51b5;border-radius:2px;margin:0 auto 30px"></div>
        <p style="color:#444;font-size:1rem;line-height:1.9;margin-bottom:18px">The Campus Placement Cell serves as a bridge between students and leading organizations by facilitating recruitment opportunities and career development initiatives. Our goal is to prepare students for successful careers through industry-oriented training, skill enhancement programs, and campus recruitment drives.</p>
        <p style="color:#444;font-size:1rem;line-height:1.9;margin-bottom:18px">The Placement Cell works closely with reputed companies to organize on-campus recruitment, internships, workshops, aptitude training, mock interviews, group discussions, and career guidance sessions. These activities help students develop the technical, communication, and professional skills required by today's employers.</p>
        <p style="color:#444;font-size:1rem;line-height:1.9">With a strong network of recruiters across various industries, we are committed to providing students with excellent career opportunities and supporting organizations in hiring talented, job-ready graduates.</p>
    </div>

    <!-- Contact under About -->
    <div id="contact" style="max-width:700px;margin:40px auto 0;text-align:center;border-top:1px solid #ddd;padding-top:35px">
        <h3 style="color:#1a237e;font-size:1.4rem;margin-bottom:6px">Contact Us</h3>
        <p style="color:#555;font-size:0.97rem;margin-bottom:20px">Have questions about campus placements, recruitment drives, or student registrations? We're here to help. Reach out to the Placement Cell for any assistance related to placements, internships, recruiter partnerships, or portal support.</p>
        <h4 style="color:#1a237e;margin-bottom:4px">Get in Touch</h4>
        <p style="color:#555;margin-bottom:16px">Placement Cell &mdash; Campus Placement Office</p>
        <p style="color:#444;font-size:0.97rem;line-height:2.1">
            📍 <strong>Address:</strong> OU PG College (Self Finance), Beside Govt Degree College, Siddipet, Telangana &ndash; 502103<br>
            📞 <strong>Phone:</strong> <a href="tel:+919866214838" style="color:#3f51b5;text-decoration:none">+91 98662 14838</a><br>
            📧 <strong>Email:</strong> <a href="mailto:campusrecruitment6224@gmail.com" style="color:#3f51b5;text-decoration:none">campusrecruitment6224@gmail.com</a><br>
            🕒 <strong>Office Hours:</strong> Monday &ndash; Friday: 9:00 AM &ndash; 5:00 PM
        </p>
    </div>
</section>

<!-- Footer -->
<footer>
    <p>🎓 <span>CampusRecruit</span> — Campus Placement & Recruitment System</p>
    <p style="margin-top:8px;font-size:0.82rem">Connecting Students with Opportunities</p>
</footer>

</body>
</html>
