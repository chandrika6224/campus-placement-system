<?php
require_once 'includes/config.php';

// DS Engine weights (same as shortlist)
$w_cgpa    = 0.30;
$w_attend  = 0.25;
$w_skills  = 0.25;
$w_resume  = 0.20;

$criteria  = $conn->query("SELECT * FROM eligibility_criteria LIMIT 1")->fetch_assoc();
$min_cgpa  = (float)$criteria['min_cgpa'];    // 6.00
$min_att   = (float)$criteria['min_attendance']; // 75.00
$max_bl    = (int)$criteria['max_backlogs'];  // 0

// Set attendance for a few students so DS engine has data
$seeds = [
    ['email'=>'rohan_nandi12@gmail.com',    'att'=>85, 'bl'=>0],  // eligible
    ['email'=>'samaira_singhania95@gmail.com','att'=>80, 'bl'=>0], // eligible
    ['email'=>'smita_agarwal90@gmail.com',  'att'=>60, 'bl'=>1],  // NOT eligible
];
foreach ($seeds as $seed) {
    $e = $conn->real_escape_string($seed['email']);
    $u = $conn->query("SELECT id FROM users WHERE email='$e'")->fetch_assoc();
    if ($u) {
        $uid = $u['id'];
        $conn->query("INSERT INTO student_attendance (user_id, attendance_pct, backlogs) VALUES ($uid,{$seed['att']},{$seed['bl']}) ON DUPLICATE KEY UPDATE attendance_pct={$seed['att']}, backlogs={$seed['bl']}");
    }
}

// Get open jobs
$jobs_arr = [];
$jRes = $conn->query("SELECT j.*, c.company_name FROM jobs j JOIN companies c ON j.company_id=c.id WHERE j.status='open' LIMIT 6");
echo "<h3>Open Jobs</h3>";
while ($j = $jRes->fetch_assoc()) {
    echo "✅ <b>{$j['title']}</b> @ {$j['company_name']} | Min CGPA: {$j['min_cgpa']}<br>";
    $jobs_arr[] = $j;
}

// Evaluate each seeded student through full DS engine
$emails = array_column($seeds, 'email');
$in     = implode(',', array_map(fn($e) => "'" . $conn->real_escape_string($e) . "'", $emails));
$stus   = $conn->query("SELECT u.id, u.name, u.email, sp.cgpa, sp.skills, sp.department,
    COALESCE(sa.attendance_pct,0) as att, COALESCE(sa.backlogs,0) as bl
    FROM users u
    JOIN student_profiles sp ON u.id=sp.user_id
    LEFT JOIN student_attendance sa ON sa.user_id=u.id
    WHERE u.email IN ($in)");

echo "<br><h3>DS Engine Evaluation</h3>";
echo "<p>Criteria: Min CGPA=$min_cgpa | Min Attendance=$min_att% | Max Backlogs=$max_bl</p>";
echo "<p>Weights: CGPA=30% | Attendance=25% | Skills=25% | Resume=20%</p><hr>";

while ($s = $stus->fetch_assoc()) {
    // --- Hard filter checks ---
    $cgpa_ok = $s['cgpa'] >= $min_cgpa;
    $att_ok  = $s['att']  >= $min_att;
    $bl_ok   = $s['bl']   <= $max_bl;
    $eligible = $cgpa_ok && $att_ok && $bl_ok;

    // --- DS Scores ---
    $cgpa_score = min(100, ($s['cgpa'] / 10) * 100);

    if ($s['att'] >= $min_att) {
        $attend_score = 100;
    } else {
        $deficit      = ($min_att - $s['att']) / $min_att;
        $attend_score = max(0, round(100 * exp(-3 * $deficit)));
    }

    // Skills match against best job
    $studentSkills  = array_map('strtolower', array_map('trim', explode(',', $s['skills'] ?? '')));
    $best_skill_score = 0;
    $best_job = '';
    foreach ($jobs_arr as $j) {
        $reqWords = array_filter(array_map('strtolower', array_map('trim', preg_split('/[,\.\s]+/', $j['requirements'].' '.$j['title']))));
        $matched  = count(array_intersect($studentSkills, $reqWords));
        $total    = max(1, count($reqWords));
        $sc       = round($matched / $total * 100);
        if ($sc > $best_skill_score) { $best_skill_score = $sc; $best_job = $j['title'].' @ '.$j['company_name']; }
    }

    // Resume score
    $rs = $conn->query("SELECT score FROM resume_analysis WHERE user_id={$s['id']} ORDER BY analyzed_at DESC LIMIT 1")->fetch_assoc();
    $resume_score = (int)($rs['score'] ?? 0);

    // Composite
    $composite = round($cgpa_score*$w_cgpa + $attend_score*$w_attend + $best_skill_score*$w_skills + $resume_score*$w_resume);

    // Label
    if ($composite >= 80)     $label = '⭐ Highly Recommended';
    elseif ($composite >= 60) $label = '✅ Recommended';
    elseif ($composite >= 40) $label = '⚠️ Borderline';
    else                       $label = '❌ Not Recommended';

    $status = $eligible ? '✅ ELIGIBLE' : '❌ NOT ELIGIBLE';
    $color  = $eligible ? 'green' : 'red';
    $first  = explode(' ', $s['name'])[0];

    echo "<div style='border:1px solid #ccc;border-radius:8px;padding:14px;margin-bottom:14px;border-left:5px solid $color'>";
    echo "<b style='font-size:1.1rem'>{$s['name']}</b> &nbsp; <span style='color:$color;font-weight:700'>$status</span><br>";
    echo "Email: {$s['email']} | Password: <b>{$first}@123</b><br><br>";

    echo "<b>Hard Filter Checks:</b><br>";
    echo ($cgpa_ok?'✅':'❌')." CGPA: {$s['cgpa']} (need &gt;= $min_cgpa)<br>";
    echo ($att_ok ?'✅':'❌')." Attendance: {$s['att']}% (need &gt;= $min_att%)<br>";
    echo ($bl_ok  ?'✅':'❌')." Backlogs: {$s['bl']} (need &lt;= $max_bl)<br><br>";

    echo "<b>DS Engine Scores:</b><br>";
    echo "📊 CGPA Score: $cgpa_score/100 (weight 30%) → ".round($cgpa_score*$w_cgpa)." pts<br>";
    echo "📅 Attendance Score: $attend_score/100 (weight 25%) → ".round($attend_score*$w_attend)." pts<br>";
    echo "🧩 Skills Score: $best_skill_score/100 (weight 25%) → ".round($best_skill_score*$w_skills)." pts<br>";
    echo "📄 Resume Score: $resume_score/100 (weight 20%) → ".round($resume_score*$w_resume)." pts<br>";
    echo "<b>🏆 Composite Score: $composite/100 → $label</b><br>";
    echo "Best Matched Job: <i>$best_job</i><br>";
    echo "</div>";
}
?>
