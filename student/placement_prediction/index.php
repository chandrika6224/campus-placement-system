<?php
require_once '../../includes/config.php';
requireLogin('student');
require_once 'predictor.php';

$uid  = $_SESSION['user_id'];
$data = PlacementPredictor::getStudentData($conn, $uid);
$pred = PlacementPredictor::predict($data);
$skills = array_filter(array_map('trim', explode(',', $data['skills'] ?? '')));

$tipIcons = ['error'=>'❌','warning'=>'⚠️','info'=>'ℹ️','success'=>'✅'];
$tipClass = ['error'=>'error','warning'=>'warning','info'=>'info','success'=>'success'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Placement Prediction</title>
<link rel="stylesheet" href="../../css/style.css">
<style>
.gauge-wrap { position:relative;width:220px;height:120px;margin:0 auto 10px;overflow:hidden; }
.gauge-bg { width:220px;height:110px;border-radius:110px 110px 0 0;background:conic-gradient(from 180deg, #e0e0e0 0deg, #e0e0e0 180deg);position:absolute;top:0; }
.gauge-fill { width:220px;height:110px;border-radius:110px 110px 0 0;position:absolute;top:0;transform-origin:bottom center;transition:transform 1.5s cubic-bezier(.4,0,.2,1); }
.gauge-center { position:absolute;bottom:0;left:50%;transform:translateX(-50%);text-align:center; }
.gauge-needle { position:absolute;bottom:0;left:50%;width:4px;height:90px;background:linear-gradient(to top,#333,#e53935);border-radius:2px;transform-origin:bottom center;transition:transform 1.5s cubic-bezier(.4,0,.2,1);margin-left:-2px; }
.factor-row { display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid #f0f0f0; }
.factor-row:last-child { border-bottom:none; }
.factor-bar-bg { flex:1;height:10px;background:#e0e0e0;border-radius:5px;overflow:hidden; }
.factor-bar-fill { height:10px;border-radius:5px;transition:width 1.2s ease; }
.tip-item { display:flex;gap:12px;align-items:flex-start;padding:10px 14px;border-radius:8px;margin-bottom:8px; }
.tip-item.error   { background:#ffebee;border-left:4px solid #e53935; }
.tip-item.warning { background:#fff8e1;border-left:4px solid #fb8c00; }
.tip-item.info    { background:#e3f2fd;border-left:4px solid #1e88e5; }
.tip-item.success { background:#e8f5e9;border-left:4px solid #43a047; }
.compare-bar { height:12px;border-radius:6px;background:#e0e0e0;margin-top:5px; }
.compare-fill { height:12px;border-radius:6px;transition:width 1.2s ease; }
.action-card { border-radius:10px;padding:16px;text-align:center;border:2px solid #e0e0e0;transition:all 0.2s;cursor:pointer;text-decoration:none;display:block;color:inherit; }
.action-card:hover { border-color:#3f51b5;background:#f5f5ff;transform:translateY(-2px); }
</style>
</head>
<body>
<nav class="navbar">
    <a href="../dashboard.php" class="brand">🎓 Campus<span>Recruit</span></a>
    <div class="nav-links">
        <a href="../dashboard.php">Dashboard</a>
        <a href="../jobs.php">Browse Jobs</a>
        <a href="../applications.php">My Applications</a>
        <a href="../profile.php">My Profile</a>
        <a href="../resume_analyzer/index.php">🤖 AI Resume</a>
        <a href="../job_recommendation/index.php">🎯 AI Jobs</a>
        <a href="../aptitude_test/index.php">📝 Tests</a>
        <a href="../interviews/index.php">🎥 Interviews</a>
        <a href="index.php" class="active">🔮 Prediction</a>
        <a href="../notices.php">Notices</a>
        <a href="../logout.php" class="btn-logout">Logout</a>
    </div>
</nav>

<div class="container">

    <!-- Hero -->
    <div class="card" style="background:linear-gradient(135deg,#1a237e,#3949ab);color:#fff;margin-bottom:25px">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:15px">
            <div>
                <h2 style="color:#ffd54f;margin-bottom:8px">🔮 Placement Prediction</h2>
                <p style="color:#c5cae9">AI-powered analysis of your placement readiness based on CGPA, skills, test scores, applications, and more.</p>
            </div>
            <?php
            $campusPct = $data['total_students'] > 0 ? round($data['total_placed'] / $data['total_students'] * 100, 1) : 0;
            ?>
            <div style="background:rgba(255,255,255,0.12);border-radius:14px;padding:16px 24px;text-align:center;min-width:160px">
                <div style="font-size:2.2rem;font-weight:800;color:#69f0ae"><?= $campusPct ?>%</div>
                <div style="font-size:0.8rem;color:#c5cae9;margin-top:2px">Campus Placement Rate</div>
                <div style="font-size:0.75rem;color:#9fa8da;margin-top:2px"><?= $data['total_placed'] ?> / <?= $data['total_students'] ?> students</div>
            </div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1.6fr;gap:20px;margin-bottom:20px">

        <!-- Gauge Card -->
        <div class="card" style="text-align:center">
            <h2>Placement Probability</h2>

            <!-- Gauge -->
            <div class="gauge-wrap" id="gauge-wrap">
                <!-- Color segments -->
                <div style="position:absolute;top:0;width:220px;height:110px;border-radius:110px 110px 0 0;background:conic-gradient(from 180deg,
                    #e53935 0deg 36deg,
                    #fb8c00 36deg 72deg,
                    #fdd835 72deg 108deg,
                    #43a047 108deg 144deg,
                    #1e88e5 144deg 180deg);opacity:0.25"></div>
                <div style="position:absolute;top:10px;left:10px;width:200px;height:100px;border-radius:100px 100px 0 0;background:#fff"></div>
                <!-- Labels -->
                <div style="position:absolute;bottom:2px;left:8px;font-size:0.65rem;color:#e53935;font-weight:700">0</div>
                <div style="position:absolute;bottom:2px;right:8px;font-size:0.65rem;color:#1e88e5;font-weight:700">100</div>
                <div style="position:absolute;top:8px;left:50%;transform:translateX(-50%);font-size:0.65rem;color:#666">50</div>
                <!-- Needle -->
                <div class="gauge-needle" id="gauge-needle" style="transform:rotate(-90deg)"></div>
                <!-- Center value -->
                <div class="gauge-center">
                    <div style="font-size:2.2rem;font-weight:800;color:<?= $pred['gradeColor'] ?>;line-height:1" id="gauge-val">0%</div>
                </div>
            </div>

            <div style="margin-top:15px">
                <div style="display:inline-block;padding:8px 25px;border-radius:20px;background:<?= $pred['gradeBg'] ?>;color:<?= $pred['gradeColor'] ?>;font-weight:800;font-size:1.1rem">
                    Grade <?= $pred['grade'] ?> — <?= $pred['gradeLabel'] ?>
                </div>
            </div>

            <div style="margin-top:20px;display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;text-align:center">
                <div style="background:#f5f5f5;border-radius:8px;padding:10px">
                    <div style="font-size:1.4rem;font-weight:800;color:#1a237e"><?= $data['applied'] ?></div>
                    <div style="font-size:0.75rem;color:#666">Applied</div>
                </div>
                <div style="background:#f5f5f5;border-radius:8px;padding:10px">
                    <div style="font-size:1.4rem;font-weight:800;color:#e65100"><?= $data['shortlisted'] ?></div>
                    <div style="font-size:0.75rem;color:#666">Shortlisted</div>
                </div>
                <div style="background:#f5f5f5;border-radius:8px;padding:10px">
                    <div style="font-size:1.4rem;font-weight:800;color:#2e7d32"><?= $data['selected'] ?></div>
                    <div style="font-size:0.75rem;color:#666">Selected</div>
                </div>
            </div>
        </div>

        <!-- Factors Breakdown -->
        <div class="card">
            <h2>📊 Score Breakdown</h2>
            <?php foreach ($pred['factors'] as $f): $pct = round($f['score'] / $f['max'] * 100); ?>
            <div class="factor-row">
                <div style="font-size:1.3rem;width:28px;text-align:center"><?= $f['icon'] ?></div>
                <div style="min-width:200px">
                    <div style="font-weight:600;font-size:0.9rem;color:#333"><?= $f['label'] ?></div>
                    <div style="font-size:0.78rem;font-weight:700;color:<?= $f['color'] ?>"><?= $f['status'] ?></div>
                </div>
                <div class="factor-bar-bg">
                    <div class="factor-bar-fill" data-width="<?= $pct ?>" style="width:0%;background:<?= $f['color'] ?>"></div>
                </div>
                <div style="min-width:55px;text-align:right;font-weight:700;font-size:0.88rem;color:<?= $f['color'] ?>"><?= $f['score'] ?>/<?= $f['max'] ?></div>
            </div>
            <?php endforeach; ?>

            <div style="margin-top:15px;padding:12px;background:#f5f5f5;border-radius:8px;display:flex;justify-content:space-between;align-items:center">
                <span style="font-weight:700;color:#1a237e">Total Score</span>
                <span style="font-size:1.2rem;font-weight:800;color:<?= $pred['gradeColor'] ?>"><?= $pred['total_score'] ?>/<?= $pred['max_score'] ?></span>
            </div>
        </div>
    </div>

    <!-- Improvement Tips -->
    <div class="card">
        <h2>💬 AI Recommendations</h2>
        <?php if (empty($pred['tips'])): ?>
        <div class="tip-item success"><span>✅</span><span>Your profile looks great! Keep it up.</span></div>
        <?php else: ?>
        <?php foreach ($pred['tips'] as $tip): ?>
        <div class="tip-item <?= $tipClass[$tip['type']] ?>">
            <span style="font-size:1.1rem"><?= $tipIcons[$tip['type']] ?></span>
            <span><?= htmlspecialchars($tip['msg']) ?></span>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Quick Action Cards -->
    <div class="card">
        <h2>🚀 Improve Your Score</h2>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:15px;margin-top:5px">
            <a href="../resume_analyzer/index.php" class="action-card">
                <div style="font-size:2rem;margin-bottom:8px">🤖</div>
                <div style="font-weight:700;color:#1a237e;font-size:0.9rem">Analyze Resume</div>
                <div style="font-size:0.78rem;color:#666;margin-top:4px">+up to 15 pts</div>
            </a>
            <a href="../aptitude_test/index.php" class="action-card">
                <div style="font-size:2rem;margin-bottom:8px">📝</div>
                <div style="font-weight:700;color:#1a237e;font-size:0.9rem">Take Tests</div>
                <div style="font-size:0.78rem;color:#666;margin-top:4px">+up to 15 pts</div>
            </a>
            <a href="../jobs.php" class="action-card">
                <div style="font-size:2rem;margin-bottom:8px">💼</div>
                <div style="font-weight:700;color:#1a237e;font-size:0.9rem">Apply to Jobs</div>
                <div style="font-size:0.78rem;color:#666;margin-top:4px">+up to 15 pts</div>
            </a>
            <a href="../profile.php" class="action-card">
                <div style="font-size:2rem;margin-bottom:8px">👤</div>
                <div style="font-weight:700;color:#1a237e;font-size:0.9rem">Complete Profile</div>
                <div style="font-size:0.78rem;color:#666;margin-top:4px">+up to 5 pts</div>
            </a>
            <a href="../job_recommendation/index.php" class="action-card">
                <div style="font-size:2rem;margin-bottom:8px">🎯</div>
                <div style="font-weight:700;color:#1a237e;font-size:0.9rem">AI Job Match</div>
                <div style="font-size:0.78rem;color:#666;margin-top:4px">Find best fit jobs</div>
            </a>
        </div>
    </div>

    <!-- What-If Simulator -->
    <div class="card">
        <h2>🧪 What-If Simulator</h2>
        <p style="color:#666;font-size:0.9rem;margin-bottom:15px">See how improving different factors changes your placement probability.</p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
            <div>
                <div class="form-group">
                    <label>Simulate CGPA</label>
                    <input type="range" id="sim-cgpa" min="0" max="10" step="0.1" value="<?= $data['cgpa'] ?? 0 ?>" oninput="simulate()">
                    <span id="sim-cgpa-val" style="font-weight:700;color:#3f51b5"><?= $data['cgpa'] ?? 0 ?></span>
                </div>
                <div class="form-group">
                    <label>Simulate Skills Count</label>
                    <input type="range" id="sim-skills" min="0" max="20" step="1" value="<?= count($skills) ?>" oninput="simulate()">
                    <span id="sim-skills-val" style="font-weight:700;color:#3f51b5"><?= count($skills) ?></span>
                </div>
                <div class="form-group">
                    <label>Simulate Resume Score</label>
                    <input type="range" id="sim-resume" min="0" max="100" step="1" value="<?= $data['resume_score'] ?>" oninput="simulate()">
                    <span id="sim-resume-val" style="font-weight:700;color:#3f51b5"><?= $data['resume_score'] ?></span>
                </div>
            </div>
            <div>
                <div class="form-group">
                    <label>Simulate Test Score (%)</label>
                    <input type="range" id="sim-test" min="0" max="100" step="1" value="<?= round($data['avg_test_pct']) ?>" oninput="simulate()">
                    <span id="sim-test-val" style="font-weight:700;color:#3f51b5"><?= round($data['avg_test_pct']) ?></span>
                </div>
                <div class="form-group">
                    <label>Simulate Applications</label>
                    <input type="range" id="sim-apps" min="0" max="20" step="1" value="<?= $data['applied'] ?>" oninput="simulate()">
                    <span id="sim-apps-val" style="font-weight:700;color:#3f51b5"><?= $data['applied'] ?></span>
                </div>
                <div style="margin-top:20px;padding:20px;background:#f5f5f5;border-radius:10px;text-align:center">
                    <div style="font-size:0.9rem;color:#666;margin-bottom:5px">Simulated Probability</div>
                    <div id="sim-result" style="font-size:2.5rem;font-weight:800;color:#3f51b5">--%</div>
                    <div id="sim-grade" style="font-size:0.9rem;color:#666;margin-top:5px"></div>
                    <div id="sim-diff" style="font-size:0.85rem;font-weight:700;margin-top:5px"></div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
const currentProb = <?= $pred['probability'] ?>;

// Animate gauge needle
window.addEventListener('load', () => {
    const prob = <?= $pred['probability'] ?>;
    const angle = -90 + (prob / 100) * 180;
    document.getElementById('gauge-needle').style.transform = `rotate(${angle}deg)`;

    // Animate value counter
    let count = 0;
    const interval = setInterval(() => {
        count = Math.min(count + 2, prob);
        document.getElementById('gauge-val').textContent = count + '%';
        if (count >= prob) clearInterval(interval);
    }, 20);

    // Animate factor bars
    document.querySelectorAll('.factor-bar-fill').forEach(bar => {
        setTimeout(() => { bar.style.width = bar.dataset.width + '%'; }, 300);
    });
});

// What-If Simulator
function simulate() {
    const cgpa      = parseFloat(document.getElementById('sim-cgpa').value);
    const skills    = parseInt(document.getElementById('sim-skills').value);
    const resume    = parseInt(document.getElementById('sim-resume').value);
    const test      = parseInt(document.getElementById('sim-test').value);
    const apps      = parseInt(document.getElementById('sim-apps').value);

    document.getElementById('sim-cgpa-val').textContent    = cgpa;
    document.getElementById('sim-skills-val').textContent  = skills;
    document.getElementById('sim-resume-val').textContent  = resume;
    document.getElementById('sim-test-val').textContent    = test;
    document.getElementById('sim-apps-val').textContent    = apps;

    // Simplified scoring
    let s = 0;
    s += cgpa >= 9 ? 25 : cgpa >= 8 ? 22 : cgpa >= 7 ? 18 : cgpa >= 6 ? 13 : cgpa >= 5 ? 8 : 3;
    s += skills >= 10 ? 20 : skills >= 7 ? 16 : skills >= 4 ? 11 : skills >= 2 ? 6 : 2;
    s += resume >= 80 ? 15 : resume >= 60 ? 12 : resume >= 40 ? 8 : resume > 0 ? 4 : 0;
    s += test >= 80 ? 15 : test >= 60 ? 11 : test >= 40 ? 7 : test > 0 ? 3 : 0;
    s += apps >= 5 ? 7 : apps >= 1 ? 4 : 0;
    s += <?= $data['shortlisted'] > 0 ? 5 : 0 ?>;
    s += <?= $pred['factors'][5]['score'] ?>; // interviews
    s += <?= $pred['factors'][6]['score'] ?>; // profile

    const prob = Math.min(98, Math.max(2, s));
    const diff = prob - currentProb;
    const grade = prob >= 80 ? 'A+' : prob >= 65 ? 'A' : prob >= 50 ? 'B' : prob >= 35 ? 'C' : 'D';
    const label = prob >= 80 ? 'Highly Likely' : prob >= 65 ? 'Likely' : prob >= 50 ? 'Moderate' : prob >= 35 ? 'Needs Work' : 'At Risk';
    const color = prob >= 65 ? '#2e7d32' : prob >= 50 ? '#e65100' : '#c62828';

    document.getElementById('sim-result').textContent = prob + '%';
    document.getElementById('sim-result').style.color = color;
    document.getElementById('sim-grade').textContent = 'Grade ' + grade + ' — ' + label;
    document.getElementById('sim-diff').textContent = diff > 0 ? '▲ +' + diff + ' pts improvement' : diff < 0 ? '▼ ' + diff + ' pts' : '= No change';
    document.getElementById('sim-diff').style.color = diff > 0 ? '#2e7d32' : diff < 0 ? '#c62828' : '#666';
}
simulate();
</script>
</body>
</html>
