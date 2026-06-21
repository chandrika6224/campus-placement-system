<?php
class PlacementPredictor {

    /**
     * Main prediction function.
     * Returns probability (0-100), grade, factors breakdown, and improvement tips.
     */
    public static function predict($data) {
        $score = 0;
        $factors = [];
        $tips = [];

        // If already placed from CSV dataset, show 98% with full stats
        if (($data['placement_status'] ?? '') === 'Placed' || (int)($data['selected'] ?? 0) > 0) {
            // Still compute full factor scores to show breakdown
            $placed_label = (int)($data['selected'] ?? 0) > 0 ? 'Selected via Application' : 'Placed (Dataset)';
            $tips[] = ['type'=>'success', 'msg'=>'🎉 Congratulations! You are marked as <strong>Placed</strong> in the system ('.$placed_label.'). Keep updating your profile!'];

            // Compute stats for overall placement rate comparison
            $total   = (int)($data['total_students'] ?? 402);
            $placed  = (int)($data['total_placed']   ?? 199);
            $pct     = $total > 0 ? round($placed / $total * 100, 1) : 0;
            $tips[]  = ['type'=>'info', 'msg'=>"Overall campus placement rate: <strong>{$pct}%</strong> ({$placed} out of {$total} students placed)."];

            return [
                'probability' => 98,
                'grade'       => 'A+',
                'gradeLabel'  => 'Placed ✅',
                'gradeColor'  => '#2e7d32',
                'gradeBg'     => '#e8f5e9',
                'factors'     => [
                    ['label'=>'Placement Status', 'score'=>98, 'max'=>100, 'status'=>'Placed', 'color'=>'#2e7d32', 'icon'=>'🎓'],
                ],
                'tips'        => $tips,
                'total_score' => 98,
                'max_score'   => 100,
            ];
        }

        $cgpa = (float)($data['cgpa'] ?? 0);
        if ($cgpa >= 9.0)       { $cgpaScore = 25; $cgpaLabel = 'Excellent'; $cgpaColor = '#2e7d32'; }
        elseif ($cgpa >= 8.0)   { $cgpaScore = 22; $cgpaLabel = 'Very Good'; $cgpaColor = '#388e3c'; }
        elseif ($cgpa >= 7.0)   { $cgpaScore = 18; $cgpaLabel = 'Good';      $cgpaColor = '#1565c0'; }
        elseif ($cgpa >= 6.0)   { $cgpaScore = 13; $cgpaLabel = 'Average';   $cgpaColor = '#e65100'; }
        elseif ($cgpa >= 5.0)   { $cgpaScore = 8;  $cgpaLabel = 'Below Avg'; $cgpaColor = '#c62828'; }
        else                    { $cgpaScore = 3;  $cgpaLabel = 'Low';       $cgpaColor = '#b71c1c'; }
        $score += $cgpaScore;
        $factors[] = ['label'=>'CGPA ('.$cgpa.')', 'score'=>$cgpaScore, 'max'=>25, 'status'=>$cgpaLabel, 'color'=>$cgpaColor, 'icon'=>'📊'];
        if ($cgpa < 7.0) $tips[] = ['type'=>'error',   'msg'=>'Improve your CGPA to at least 7.0 — most companies require minimum 6.5-7.0 CGPA.'];
        elseif ($cgpa < 8.0) $tips[] = ['type'=>'info', 'msg'=>'Good CGPA! Aim for 8.0+ to qualify for top-tier companies.'];

        // ── 2. Skills (max 20 pts) ────────────────────────────────────────
        $skills = array_filter(array_map('trim', explode(',', $data['skills'] ?? '')));
        $skillCount = count($skills);
        if ($skillCount >= 10)      { $skillScore = 20; $skillLabel = 'Excellent'; $skillColor = '#2e7d32'; }
        elseif ($skillCount >= 7)   { $skillScore = 16; $skillLabel = 'Good';      $skillColor = '#1565c0'; }
        elseif ($skillCount >= 4)   { $skillScore = 11; $skillLabel = 'Average';   $skillColor = '#e65100'; }
        elseif ($skillCount >= 2)   { $skillScore = 6;  $skillLabel = 'Low';       $skillColor = '#c62828'; }
        else                        { $skillScore = 2;  $skillLabel = 'Very Low';  $skillColor = '#b71c1c'; }
        $score += $skillScore;
        $factors[] = ['label'=>'Skills ('.$skillCount.' listed)', 'score'=>$skillScore, 'max'=>20, 'status'=>$skillLabel, 'color'=>$skillColor, 'icon'=>'💡'];
        if ($skillCount < 5)  $tips[] = ['type'=>'error',   'msg'=>'Add more skills to your profile. Aim for at least 8-10 relevant technical skills.'];
        elseif ($skillCount < 8) $tips[] = ['type'=>'warning', 'msg'=>'Add more skills — especially frameworks, tools, and databases relevant to your field.'];

        // ── 3. Resume Score (max 15 pts) ──────────────────────────────────
        $resumeScore = (int)($data['resume_score'] ?? 0);
        if ($resumeScore >= 80)     { $rsScore = 15; $rsLabel = 'Excellent'; $rsColor = '#2e7d32'; }
        elseif ($resumeScore >= 60) { $rsScore = 12; $rsLabel = 'Good';      $rsColor = '#1565c0'; }
        elseif ($resumeScore >= 40) { $rsScore = 8;  $rsLabel = 'Average';   $rsColor = '#e65100'; }
        elseif ($resumeScore > 0)   { $rsScore = 4;  $rsLabel = 'Weak';      $rsColor = '#c62828'; }
        else                        { $rsScore = 0;  $rsLabel = 'Not Done';  $rsColor = '#9e9e9e'; }
        $score += $rsScore;
        $factors[] = ['label'=>'Resume Score ('.$resumeScore.'/100)', 'score'=>$rsScore, 'max'=>15, 'status'=>$rsLabel, 'color'=>$rsColor, 'icon'=>'📄'];
        if ($resumeScore === 0) $tips[] = ['type'=>'error',   'msg'=>'Use the AI Resume Analyzer to score and improve your resume — it\'s free!'];
        elseif ($resumeScore < 60) $tips[] = ['type'=>'warning', 'msg'=>'Your resume score is low. Follow the AI suggestions to improve it above 70.'];

        // ── 4. Test Performance (max 15 pts) ──────────────────────────────
        $avgTestPct = (float)($data['avg_test_pct'] ?? 0);
        $testCount  = (int)($data['test_count'] ?? 0);
        if ($testCount === 0)           { $tScore = 0;  $tLabel = 'No Tests';  $tColor = '#9e9e9e'; }
        elseif ($avgTestPct >= 80)      { $tScore = 15; $tLabel = 'Excellent'; $tColor = '#2e7d32'; }
        elseif ($avgTestPct >= 60)      { $tScore = 11; $tLabel = 'Good';      $tColor = '#1565c0'; }
        elseif ($avgTestPct >= 40)      { $tScore = 7;  $tLabel = 'Average';   $tColor = '#e65100'; }
        else                            { $tScore = 3;  $tLabel = 'Low';       $tColor = '#c62828'; }
        $score += $tScore;
        $factors[] = ['label'=>'Test Avg ('.round($avgTestPct).'%)', 'score'=>$tScore, 'max'=>15, 'status'=>$tLabel, 'color'=>$tColor, 'icon'=>'📝'];
        if ($testCount === 0) $tips[] = ['type'=>'warning', 'msg'=>'Take aptitude and technical tests to boost your placement score.'];
        elseif ($avgTestPct < 60) $tips[] = ['type'=>'warning', 'msg'=>'Practice more aptitude and coding problems to improve your test scores above 60%.'];

        // ── 5. Applications & Shortlist Ratio (max 15 pts) ────────────────
        $applied      = (int)($data['applied'] ?? 0);
        $shortlisted  = (int)($data['shortlisted'] ?? 0);
        $selected     = (int)($data['selected'] ?? 0);
        if ($selected > 0)                          { $appScore = 15; $appLabel = 'Selected!';  $appColor = '#2e7d32'; }
        elseif ($shortlisted > 0 && $applied >= 3)  { $appScore = 12; $appLabel = 'Shortlisted'; $appColor = '#1565c0'; }
        elseif ($shortlisted > 0)                   { $appScore = 10; $appLabel = 'Shortlisted'; $appColor = '#1565c0'; }
        elseif ($applied >= 5)                      { $appScore = 7;  $appLabel = 'Active';     $appColor = '#e65100'; }
        elseif ($applied >= 1)                      { $appScore = 4;  $appLabel = 'Started';    $appColor = '#fb8c00'; }
        else                                        { $appScore = 0;  $appLabel = 'No Apps';    $appColor = '#9e9e9e'; }
        $score += $appScore;
        $factors[] = ['label'=>'Applications ('.$applied.' applied, '.$shortlisted.' shortlisted)', 'score'=>$appScore, 'max'=>15, 'status'=>$appLabel, 'color'=>$appColor, 'icon'=>'📋'];
        if ($applied === 0) $tips[] = ['type'=>'error',   'msg'=>'Start applying to jobs! Apply to at least 5-10 relevant positions.'];
        elseif ($shortlisted === 0 && $applied >= 3) $tips[] = ['type'=>'warning', 'msg'=>'Not shortlisted yet. Improve your resume and skills to get noticed by recruiters.'];

        // ── 6. Interviews (max 5 pts) ─────────────────────────────────────
        $interviews = (int)($data['interviews'] ?? 0);
        if ($interviews >= 3)       { $ivScore = 5; $ivLabel = 'Active';   $ivColor = '#2e7d32'; }
        elseif ($interviews >= 1)   { $ivScore = 3; $ivLabel = 'Started';  $ivColor = '#1565c0'; }
        else                        { $ivScore = 0; $ivLabel = 'None Yet'; $ivColor = '#9e9e9e'; }
        $score += $ivScore;
        $factors[] = ['label'=>'Interviews ('.$interviews.' scheduled)', 'score'=>$ivScore, 'max'=>5, 'status'=>$ivLabel, 'color'=>$ivColor, 'icon'=>'🎥'];

        // ── 7. Profile Completeness (max 5 pts) ───────────────────────────
        $fields = ['cgpa','skills','department','resume_path','phone'];
        $filled = 0;
        foreach ($fields as $f) { if (!empty($data[$f])) $filled++; }
        $profScore = round(($filled / count($fields)) * 5);
        $profLabel = $filled >= 5 ? 'Complete' : ($filled >= 3 ? 'Partial' : 'Incomplete');
        $profColor = $filled >= 5 ? '#2e7d32' : ($filled >= 3 ? '#e65100' : '#c62828');
        $score += $profScore;
        $factors[] = ['label'=>'Profile ('.($filled*20).'% complete)', 'score'=>$profScore, 'max'=>5, 'status'=>$profLabel, 'color'=>$profColor, 'icon'=>'👤'];
        if ($filled < 5) $tips[] = ['type'=>'info', 'msg'=>'Complete your profile (CGPA, skills, department, phone, resume) for better visibility to recruiters.'];

        // ── Final probability ─────────────────────────────────────────────
        $probability = min(98, max(2, $score));

        // Grade
        if ($probability >= 80)      { $grade = 'A+'; $gradeLabel = 'Highly Likely';  $gradeColor = '#2e7d32'; $gradeBg = '#e8f5e9'; }
        elseif ($probability >= 65)  { $grade = 'A';  $gradeLabel = 'Likely';         $gradeColor = '#1565c0'; $gradeBg = '#e3f2fd'; }
        elseif ($probability >= 50)  { $grade = 'B';  $gradeLabel = 'Moderate';       $gradeColor = '#e65100'; $gradeBg = '#fff8e1'; }
        elseif ($probability >= 35)  { $grade = 'C';  $gradeLabel = 'Needs Work';     $gradeColor = '#c62828'; $gradeBg = '#ffebee'; }
        else                         { $grade = 'D';  $gradeLabel = 'At Risk';        $gradeColor = '#b71c1c'; $gradeBg = '#ffebee'; }

        // Add positive tips
        if ($probability >= 70) $tips[] = ['type'=>'success', 'msg'=>'Great profile! Keep applying and preparing for interviews.'];
        if ($selected > 0)      $tips[] = ['type'=>'success', 'msg'=>'Congratulations! You have already been selected for a position!'];

        return [
            'probability' => $probability,
            'grade'       => $grade,
            'gradeLabel'  => $gradeLabel,
            'gradeColor'  => $gradeColor,
            'gradeBg'     => $gradeBg,
            'factors'     => $factors,
            'tips'        => $tips,
            'total_score' => $score,
            'max_score'   => 100,
        ];
    }

    /** Fetch all data needed for a student from DB — safe table checks */
    public static function getStudentData($conn, $uid) {
        $profile = $conn->query("SELECT sp.*, u.name FROM student_profiles sp JOIN users u ON sp.user_id=u.id WHERE sp.user_id=$uid")->fetch_assoc();

        // resume_analysis
        $resumeScore = 0;
        if ($conn->query("SHOW TABLES LIKE 'resume_analysis'")->num_rows > 0) {
            $resumeScore = $conn->query("SELECT score FROM resume_analysis WHERE user_id=$uid ORDER BY analyzed_at DESC LIMIT 1")->fetch_assoc()['score'] ?? 0;
        }

        // applications
        $appStats = ['applied' => 0, 'shortlisted' => 0, 'selected' => 0];
        if ($conn->query("SHOW TABLES LIKE 'applications'")->num_rows > 0) {
            $appStats = $conn->query("SELECT
                COUNT(*) as applied,
                SUM(status='shortlisted') as shortlisted,
                SUM(status='selected') as selected
                FROM applications WHERE student_id=$uid")->fetch_assoc();
        }

        // test_attempts
        $testStats = ['cnt' => 0, 'avg_pct' => 0];
        if ($conn->query("SHOW TABLES LIKE 'test_attempts'")->num_rows > 0) {
            $testStats = $conn->query("SELECT COUNT(*) as cnt,
                AVG(score/total_marks*100) as avg_pct
                FROM test_attempts WHERE student_id=$uid AND status='completed' AND total_marks > 0")->fetch_assoc();
        }

        // interviews
        $ivCount = 0;
        if ($conn->query("SHOW TABLES LIKE 'interviews'")->num_rows > 0) {
            $ivCount = (int)$conn->query("SELECT COUNT(*) as c FROM interviews WHERE student_id=$uid AND status IN ('scheduled','completed')")->fetch_assoc()['c'];
        }

        // overall placement stats
        $totalStudents = (int)$conn->query("SELECT COUNT(*) as c FROM users WHERE role='student'")->fetch_assoc()['c'];
        $totalPlaced   = (int)$conn->query("SELECT COUNT(DISTINCT uid) as c FROM (SELECT student_id as uid FROM applications WHERE status='selected' UNION SELECT user_id as uid FROM student_profiles WHERE placement_status='Placed') t")->fetch_assoc()['c'];

        return array_merge($profile ?? [], [
            'resume_score'   => (int)$resumeScore,
            'applied'        => (int)($appStats['applied'] ?? 0),
            'shortlisted'    => (int)($appStats['shortlisted'] ?? 0),
            'selected'       => (int)($appStats['selected'] ?? 0),
            'test_count'     => (int)($testStats['cnt'] ?? 0),
            'avg_test_pct'   => (float)($testStats['avg_pct'] ?? 0),
            'interviews'     => (int)$ivCount,
            'total_students' => $totalStudents,
            'total_placed'   => $totalPlaced,
        ]);
    }
}
