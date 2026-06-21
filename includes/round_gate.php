<?php
/**
 * Checks if a student has cleared all previous rounds for a given round.
 * Gate logic (in order of priority):
 *   1. If admin has explicitly marked eligibility in round_eligible table → use that.
 *   2. Else if min_pass_score > 0 → check scored % >= required %.
 *   3. Else if round has a test/coding problem → student must have at least attempted it.
 */
function checkRoundGate($conn, $uid, $round_id) {
    // Ensure round_eligible table exists
    $conn->query("CREATE TABLE IF NOT EXISTS round_eligible (
        id INT AUTO_INCREMENT PRIMARY KEY,
        round_id INT NOT NULL,
        student_id INT NOT NULL,
        eligible TINYINT(1) DEFAULT 1,
        marked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_re (round_id, student_id),
        FOREIGN KEY (round_id) REFERENCES placement_rounds(id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    $round = $conn->query("SELECT * FROM placement_rounds WHERE id=$round_id")->fetch_assoc();
    if (!$round) return ['pass' => true];

    $job_id       = (int)$round['job_id'];
    $round_number = (int)$round['round_number'];

    if ($round_number <= 1) return ['pass' => true];

    $prevRounds = $conn->query("SELECT * FROM placement_rounds WHERE job_id=$job_id AND round_number < $round_number ORDER BY round_number ASC");

    while ($prev = $prevRounds->fetch_assoc()) {
        $prev_id = (int)$prev['id'];

        // Priority 1: explicit admin eligibility marking
        $explicit = $conn->query("SELECT eligible FROM round_eligible WHERE round_id=$prev_id AND student_id=$uid LIMIT 1")->fetch_assoc();
        if ($explicit !== null) {
            if (!$explicit['eligible']) {
                return ['pass' => false, 'reason' => 'not_eligible', 'round_name' => $prev['round_name'], 'required' => 0, 'scored' => 0];
            }
            continue; // explicitly eligible — move to next previous round
        }

        $required_pct = (int)$prev['min_pass_score'];
        $scored_pct   = null;

        if (!empty($prev['test_id'])) {
            $tid = (int)$prev['test_id'];
            $att = $conn->query("SELECT score, total_marks FROM test_attempts WHERE test_id=$tid AND student_id=$uid AND status='completed' ORDER BY score DESC LIMIT 1")->fetch_assoc();
            if (!$att) {
                return ['pass' => false, 'reason' => 'not_attempted', 'round_name' => $prev['round_name'], 'required' => $required_pct, 'scored' => 0];
            }
            $scored_pct = $att['total_marks'] > 0 ? round(($att['score'] / $att['total_marks']) * 100) : 0;
        } elseif (!empty($prev['coding_problem_id'])) {
            $cpid = (int)$prev['coding_problem_id'];
            $sub  = $conn->query("SELECT status FROM coding_submissions WHERE problem_id=$cpid AND user_id=$uid ORDER BY submitted_at DESC LIMIT 1")->fetch_assoc();
            if (!$sub) {
                return ['pass' => false, 'reason' => 'not_attempted', 'round_name' => $prev['round_name'], 'required' => $required_pct, 'scored' => 0];
            }
            $scored_pct = match($sub['status']) { 'accepted' => 100, 'partial' => 50, default => 0 };
        }

        // Priority 2: score-based gate
        if ($required_pct > 0 && $scored_pct !== null && $scored_pct < $required_pct) {
            return ['pass' => false, 'reason' => 'failed', 'round_name' => $prev['round_name'], 'required' => $required_pct, 'scored' => $scored_pct];
        }
    }

    return ['pass' => true];
}

function roundGateBlock($gate, $backUrl = '') {
    $back = $backUrl ?: '/placement/student/aptitude_test/index.php';
    if ($gate['reason'] === 'not_attempted') {
        $reason = "You have not attempted the <strong>{$gate['round_name']}</strong> yet.";
    } elseif ($gate['reason'] === 'not_eligible') {
        $reason = "You were not shortlisted to proceed from <strong>{$gate['round_name']}</strong>.";
    } else {
        $reason = "You scored <strong>{$gate['scored']}%</strong> in <strong>{$gate['round_name']}</strong> but need at least <strong>{$gate['required']}%</strong> to proceed.";
    }
    die("<!DOCTYPE html><html><head><title>Round Locked</title>
    <link rel='stylesheet' href='/placement/css/style.css'>
    </head><body style='display:flex;align-items:center;justify-content:center;min-height:100vh'>
    <div class='card' style='text-align:center;max-width:460px;padding:40px'>
        <div style='font-size:3rem;margin-bottom:16px'>&#128274;</div>
        <h2 style='color:#c62828;margin-bottom:10px'>Round Locked</h2>
        <p style='color:#555;margin-bottom:16px;line-height:1.6'>$reason</p>
        <div style='background:#ffebee;border-radius:8px;padding:12px;font-size:0.9rem;color:#c62828;margin-bottom:20px'>
            Complete the previous round to unlock this one.
        </div>
        <a href='$back' class='btn btn-primary'>&larr; Go Back</a>
    </div></body></html>");
}

