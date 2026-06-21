<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'includes/config.php';

$companies = [
    ['TCS', 'Information Technology', 'tcs.com', 'Tata Consultancy Services', 'hr@tcs.com'],
    ['Infosys', 'Information Technology', 'infosys.com', 'Infosys Limited', 'hr@infosys.com'],
    ['Wipro', 'Information Technology', 'wipro.com', 'Wipro Technologies', 'hr@wipro.com'],
    ['Amazon', 'E-commerce & Cloud', 'amazon.jobs', 'Amazon India', 'hr@amazon.com'],
    ['Google', 'Technology', 'careers.google.com', 'Google India', 'hr@google.com'],
    ['Accenture', 'Consulting', 'accenture.com', 'Accenture India', 'hr@accenture.com'],
];

$jobs = [
    // [company_index, title, description, requirements, salary, location, type, min_cgpa, deadline_days]
    [0, 'Software Engineer', 'Design and develop scalable software applications. Work in agile teams to deliver high-quality solutions.', 'B.E/B.Tech in CS/IT, Strong in Java or Python, Good DSA skills, CGPA 6.5+', '3.5-7 LPA', 'Chennai, Hyderabad, Pune', 'Full-time', 6.5, 30],
    [0, 'System Engineer', 'Maintain and support enterprise IT systems. Troubleshoot and resolve technical issues.', 'B.E/B.Tech any branch, Basic programming knowledge, CGPA 6.0+', '3-5 LPA', 'Pan India', 'Full-time', 6.0, 25],
    [1, 'Associate Developer', 'Build and maintain web applications using Java and related frameworks. Collaborate with senior developers.', 'B.E/B.Tech CS/IT/ECE, Java knowledge, SQL basics, CGPA 6.5+', '3.6-6.5 LPA', 'Bangalore, Pune, Hyderabad', 'Full-time', 6.5, 28],
    [1, 'Operations Analyst', 'Analyze business processes and provide data-driven insights. Work with cross-functional teams.', 'B.E/B.Tech or MBA, Analytical skills, Excel/SQL knowledge, CGPA 6.0+', '4-7 LPA', 'Bangalore, Mysore', 'Full-time', 6.0, 20],
    [2, 'Project Engineer', 'Contribute to software development projects across various technology stacks. Learn and grow in a dynamic environment.', 'B.E/B.Tech any branch, Programming skills in any language, CGPA 6.0+', '3.5-5.5 LPA', 'Bangalore, Hyderabad, Chennai', 'Full-time', 6.0, 35],
    [2, 'Software Intern', 'Work on live projects and gain hands-on experience in software development.', 'B.E/B.Tech 3rd or 4th year, Basic coding skills, Eagerness to learn', '15,000-20,000/month', 'Bangalore', 'Internship', 6.5, 15],
    [3, 'SDE-1 (Software Development Engineer)', 'Write high-quality code, participate in design reviews and contribute to Amazon\'s products used by millions worldwide.', 'B.E/B.Tech CS/IT, Excellent DSA, Proficiency in C++/Java/Python, System design basics, CGPA 7.5+', '18-32 LPA', 'Bangalore, Hyderabad', 'Full-time', 7.5, 45],
    [3, 'Software Development Engineer Intern', 'Work on real Amazon projects, contribute to production code, and collaborate with senior engineers.', 'Pre-final year B.E/B.Tech CS/IT, Strong DSA, Competitive programming experience preferred, CGPA 7.0+', '80,000-1,00,000/month', 'Bangalore, Hyderabad', 'Internship', 7.0, 20],
    [4, 'Software Engineer (STEP)', 'Build Google products from the ground up. Solve challenging engineering problems at massive scale.', 'B.E/B.Tech CS/IT, Exceptional problem-solving, Proficiency in C++/Java/Python/Go, CGPA 8.0+', '25-45 LPA', 'Bangalore', 'Full-time', 8.0, 60],
    [5, 'Application Developer Associate', 'Develop and maintain client applications. Work on diverse projects across industries.', 'B.E/B.Tech CS/IT/ECE, Knowledge of web technologies, Good communication skills, CGPA 6.5+', '4.5-8 LPA', 'Bangalore, Mumbai, Hyderabad, Chennai', 'Full-time', 6.5, 30],
    [5, 'Technology Analyst', 'Analyze and implement technology solutions for clients. Bridge business requirements with technical implementation.', 'B.E/B.Tech any branch, Analytical thinking, Basic SQL/Excel, CGPA 6.0+', '4-7.5 LPA', 'Pan India', 'Full-time', 6.0, 22],
    [5, 'Tech Intern', 'Support project teams and gain experience in consulting and technology delivery.', 'B.E/B.Tech 3rd or final year, Good communication, Basic programming', '20,000-25,000/month', 'Bangalore, Mumbai', 'Internship', 6.0, 18],
];

$inserted_companies = 0;
$inserted_jobs = 0;
$company_ids = [];

foreach ($companies as $i => $c) {
    [$name, $industry, $website, $desc, $email] = $c;

    // Create recruiter user if not exists
    $existing = $conn->query("SELECT id FROM users WHERE email='$email'")->fetch_assoc();
    if ($existing) {
        $uid = $existing['id'];
    } else {
        $pass = password_hash('recruiter123', PASSWORD_DEFAULT);
        if (!$conn->query("INSERT INTO users (name, email, password, role) VALUES ('$name HR', '$email', '$pass', 'recruiter')")) {
            die("User insert error: " . $conn->error);
        }
        $uid = $conn->insert_id;
        $inserted_companies++;
    }

    // Create company record if not exists
    $existing_company = $conn->query("SELECT id FROM companies WHERE user_id=$uid")->fetch_assoc();
    if ($existing_company) {
        $company_ids[$i] = $existing_company['id'];
    } else {
        if (!$conn->query("INSERT INTO companies (user_id, company_name, industry, website, description) VALUES ($uid, '$name', '$industry', '$website', '$desc')")) {
            die("Company insert error: " . $conn->error);
        }
        $company_ids[$i] = $conn->insert_id;
    }
}

foreach ($jobs as $j) {
    [$ci, $title, $desc, $req, $salary, $loc, $type, $cgpa, $days] = $j;
    $cid      = $company_ids[$ci];
    $deadline = date('Y-m-d', strtotime("+{$days} days"));
    $title    = $conn->real_escape_string($title);
    $desc     = $conn->real_escape_string($desc);
    $req      = $conn->real_escape_string($req);
    $salary   = $conn->real_escape_string($salary);
    $loc      = $conn->real_escape_string($loc);

    // Avoid duplicates
    $exists = $conn->query("SELECT id FROM jobs WHERE company_id=$cid AND title='$title'")->fetch_assoc();
    if (!$exists) {
        $conn->query("INSERT INTO jobs (company_id, title, description, requirements, salary_range, location, job_type, min_cgpa, deadline, status)
            VALUES ($cid, '$title', '$desc', '$req', '$salary', '$loc', '$type', $cgpa, '$deadline', 'open')");
        $inserted_jobs++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Seed Jobs</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container" style="max-width:700px;margin:60px auto">
    <div class="card">
        <h2>✅ Jobs Seeded Successfully</h2>
        <p>Added <strong><?= $inserted_companies ?> new recruiter accounts</strong> and <strong><?= $inserted_jobs ?> new jobs</strong>.</p>
        <br>
        <h3>Companies Added</h3>
        <ul style="line-height:2">
            <?php foreach ($companies as $c): ?>
            <li>🏢 <strong><?= $c[0] ?></strong> — <?= $c[1] ?></li>
            <?php endforeach; ?>
        </ul>
        <br>
        <p style="color:#e53935;font-weight:600">⚠️ Delete this file after seeding: <code>seed_jobs.php</code></p>
        <br>
        <a href="home.php" class="btn btn-primary">← Back to Home</a>
        &nbsp;
        <a href="index.php?tab=login" class="btn btn-primary">Login</a>
    </div>
</div>
</body>
</html>
