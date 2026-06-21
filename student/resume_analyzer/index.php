<?php
require_once '../../includes/config.php';
requireLogin('student');
require_once 'analyzer.php';

$uid = $_SESSION['user_id'];
$result = null;
$error = '';
$inputText = '';

// Create table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS resume_analysis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    score INT DEFAULT 0,
    found_skills TEXT,
    missing_skills TEXT,
    suggestions TEXT,
    matched_jobs TEXT,
    sections_found TEXT,
    analyzed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

// Load last analysis
$lastAnalysis = $conn->query("SELECT * FROM resume_analysis WHERE user_id=$uid ORDER BY analyzed_at DESC LIMIT 1")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $analyzer = new ResumeAnalyzer();
    $text = '';

    // Option 1: Paste text
    if (!empty($_POST['resume_text'])) {
        $text = $_POST['resume_text'];
        $inputText = $text;
    }
    // Option 2: PDF extracted via PDF.js in browser
    elseif (!empty($_POST['pdf_extracted_text'])) {
        $text = $_POST['pdf_extracted_text'];
        $inputText = $text;
    }
    // Option 3: Upload non-PDF file (txt, docx, doc)
    elseif (!empty($_FILES['resume_file']['name'])) {
        $ext = strtolower(pathinfo($_FILES['resume_file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['pdf','doc','docx','txt'])) {
            $error = 'Only PDF, DOC, DOCX, TXT files are allowed.';
        } else {
            $tmpPath = $_FILES['resume_file']['tmp_name'];
            $text = $analyzer->extractText($tmpPath);
            if (empty(trim($text)) || strlen(trim($text)) < 50) {
                $error = 'Could not extract enough text from this file. Please use the <strong>Paste Text</strong> option.';
            }
        }
    } else {
        $error = 'Please paste your resume text or upload a file.';
    }

    if (!$error && !empty($text)) {
        $result = $analyzer->analyze($text);

        // Save to DB
        $score       = $result['score'];
        $found       = $conn->real_escape_string(json_encode($result['found_skills']));
        $missing     = $conn->real_escape_string(json_encode($result['missing_skills']));
        $suggestions = $conn->real_escape_string(json_encode($result['suggestions']));
        $matched     = $conn->real_escape_string(json_encode($result['matched_jobs']));
        $sections    = $conn->real_escape_string(json_encode($result['sections']));

        $conn->query("INSERT INTO resume_analysis (user_id,score,found_skills,missing_skills,suggestions,matched_jobs,sections_found)
            VALUES ($uid,$score,'$found','$missing','$suggestions','$matched','$sections')");

        // ── Auto-update student profile skills from resume ──
        $extractedSkills = [];
        foreach ($result['found_skills'] as $cat => $skills) {
            // Exclude soft skills from profile auto-update (keep only technical)
            if ($cat === 'soft_skills') continue;
            foreach ($skills as $sk) $extractedSkills[] = $sk;
        }
        if (!empty($extractedSkills)) {
            // Merge with existing profile skills (no duplicates, case-insensitive)
            $stPro = $conn->prepare("SELECT skills FROM student_profiles WHERE user_id=?");
            $stPro->bind_param('i', $uid); $stPro->execute();
            $existing = $stPro->get_result()->fetch_assoc()['skills'] ?? '';
            $stPro->close();

            $existingArr = array_filter(array_map('trim', explode(',', $existing)));
            $existingLow = array_map('strtolower', $existingArr);

            foreach ($extractedSkills as $sk) {
                if (!in_array(strtolower($sk), $existingLow)) {
                    $existingArr[] = $sk;
                    $existingLow[] = strtolower($sk);
                }
            }
            $mergedSkills = $conn->real_escape_string(implode(', ', $existingArr));
            $conn->query("UPDATE student_profiles SET skills='$mergedSkills' WHERE user_id=$uid");
        }

        $lastAnalysis = $conn->query("SELECT * FROM resume_analysis WHERE user_id=$uid ORDER BY analyzed_at DESC LIMIT 1")->fetch_assoc();
    }
}

// Load previous result for display
if (!$result && $lastAnalysis) {
    $result = [
        'score'        => $lastAnalysis['score'],
        'found_skills' => json_decode($lastAnalysis['found_skills'], true),
        'missing_skills'=> json_decode($lastAnalysis['missing_skills'], true),
        'suggestions'  => json_decode($lastAnalysis['suggestions'], true),
        'matched_jobs' => json_decode($lastAnalysis['matched_jobs'], true),
        'sections'     => json_decode($lastAnalysis['sections_found'], true),
        'strength'     => (new ResumeAnalyzer())->analyze('dummy')['strength'],
    ];
    // Recalculate strength from score
    $s = $lastAnalysis['score'];
    if ($s >= 80)      $result['strength'] = ['label'=>'Excellent','color'=>'#2e7d32','bg'=>'#e8f5e9'];
    elseif ($s >= 60)  $result['strength'] = ['label'=>'Good','color'=>'#1565c0','bg'=>'#e3f2fd'];
    elseif ($s >= 40)  $result['strength'] = ['label'=>'Average','color'=>'#e65100','bg'=>'#fff8e1'];
    else               $result['strength'] = ['label'=>'Needs Improvement','color'=>'#c62828','bg'=>'#ffebee'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AI Resume Analyzer</title>
<link rel="stylesheet" href="../../css/style.css">
<style>
.score-circle {
    width: 140px; height: 140px; border-radius: 50%;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    margin: 0 auto 20px;
    border: 8px solid #e0e0e0;
    position: relative; font-size: 2.2rem; font-weight: 800;
}
.section-check { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px; }
.section-tag {
    padding: 6px 14px; border-radius: 20px; font-size: 0.85rem; font-weight: 600;
}
.tag-found { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
.tag-missing { background: #ffebee; color: #c62828; border: 1px solid #ef9a9a; }
.skill-tag {
    display: inline-block; padding: 4px 12px; border-radius: 20px;
    font-size: 0.82rem; font-weight: 600; margin: 3px;
    background: #e8eaf6; color: #3f51b5; border: 1px solid #c5cae9;
}
.skill-tag.missing { background: #ffebee; color: #c62828; border-color: #ef9a9a; }
.job-match-bar { margin-bottom: 12px; }
.job-match-bar .label { display: flex; justify-content: space-between; margin-bottom: 4px; font-size: 0.9rem; font-weight: 600; }
.bar-bg { background: #e0e0e0; border-radius: 10px; height: 10px; }
.bar-fill { height: 10px; border-radius: 10px; background: linear-gradient(90deg, #3f51b5, #7986cb); transition: width 1s; }
.suggestion-item { display: flex; gap: 12px; align-items: flex-start; padding: 10px 14px; border-radius: 8px; margin-bottom: 8px; }
.suggestion-item.error   { background: #ffebee; border-left: 4px solid #e53935; }
.suggestion-item.warning { background: #fff8e1; border-left: 4px solid #fb8c00; }
.suggestion-item.info    { background: #e3f2fd; border-left: 4px solid #1e88e5; }
.suggestion-item.success { background: #e8f5e9; border-left: 4px solid #43a047; }
.tab-btn { padding: 10px 25px; border: 2px solid #3f51b5; background: none; border-radius: 6px; cursor: pointer; font-weight: 600; color: #3f51b5; transition: all 0.2s; }
.tab-btn.active { background: #3f51b5; color: #fff; }
.analyze-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
@media(max-width:768px){ .analyze-grid { grid-template-columns: 1fr; } }
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
        <a href="index.php" class="active">AI Resume Analyzer</a>
        <a href="../notices.php">Notices</a>
        <a href="../logout.php" class="btn-logout">Logout</a>
    </div>
</nav>

<div class="container">
    <div class="card">
        <h2>🤖 AI Resume Analyzer</h2>
        <p style="color:#666;margin-bottom:20px">Our AI analyzes your resume and gives a detailed score with improvement suggestions.</p>

        <?php if ($error): ?>
        <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($lastAnalysis && !isset($_GET['reanalyze'])): ?>
        <!-- Resume already analyzed — show status instead of upload form -->
        <div style="background:#e8f5e9;border-radius:10px;padding:18px;border-left:5px solid #43a047;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
            <div>
                <div style="font-weight:700;color:#1b5e20;font-size:1rem">✅ Resume Already Analyzed</div>
                <div style="color:#555;font-size:0.88rem;margin-top:4px">Last analyzed: <?= date('d M Y, h:i A', strtotime($lastAnalysis['analyzed_at'])) ?> &nbsp;·&nbsp; Score: <strong><?= $lastAnalysis['score'] ?>/100</strong></div>
            </div>
            <a href="?reanalyze=1" class="btn" style="background:#1b5e20;color:#fff;padding:8px 20px;border-radius:20px;text-decoration:none;font-weight:700;font-size:0.88rem">🔄 Re-analyze Resume</a>
        </div>
        <?php else: ?>
        <!-- Show upload form -->
        <div style="display:flex;gap:10px;margin-bottom:20px">
            <button class="tab-btn active" onclick="switchTab('paste',this)">📝 Paste Text</button>
            <button class="tab-btn" onclick="switchTab('upload',this)">📁 Upload File</button>
        </div>

        <form method="POST" enctype="multipart/form-data" id="analyzeForm">
            <div id="tab-paste">
                <div class="form-group">
                    <label>Paste Your Resume Text</label>
                    <textarea name="resume_text" id="resume_text" rows="10" placeholder="Paste your full resume content here including education, skills, projects, experience, certifications..."><?= htmlspecialchars($inputText) ?></textarea>
                </div>
            </div>
            <div id="tab-upload" style="display:none">
                <div class="form-group">
                    <label>Upload Resume File (PDF, DOC, DOCX, TXT)</label>
                    <input type="file" name="resume_file" id="resume_file" accept=".pdf,.doc,.docx,.txt" onchange="handleFileUpload(this)">
                    <div id="extract-status" style="margin-top:8px;font-size:0.85rem"></div>
                    <small style="color:#999">PDF text will be extracted automatically in your browser.</small>
                </div>
                <textarea name="pdf_extracted_text" id="pdf_extracted_text" style="display:none"></textarea>
            </div>
            <button type="submit" class="btn btn-primary" id="analyzeBtn" style="padding:12px 35px;font-size:1rem">🔍 Analyze Resume</button>
        </form>
        <?php endif; ?>

        <!-- PDF.js CDN -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
        <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

        async function handleFileUpload(input) {
            const file = input.files[0];
            if (!file) return;
            const ext = file.name.split('.').pop().toLowerCase();
            const status = document.getElementById('extract-status');

            if (ext === 'pdf') {
                status.innerHTML = '⏳ Extracting text from PDF...';
                status.style.color = '#1565c0';
                try {
                    const arrayBuffer = await file.arrayBuffer();
                    const pdf = await pdfjsLib.getDocument({ data: arrayBuffer }).promise;
                    let fullText = '';
                    for (let i = 1; i <= pdf.numPages; i++) {
                        const page = await pdf.getPage(i);
                        const content = await page.getTextContent();
                        fullText += content.items.map(item => item.str).join(' ') + '\n';
                    }
                    if (fullText.trim().length < 50) {
                        status.innerHTML = '⚠️ This PDF appears to be image-based (scanned). Please use Paste Text instead.';
                        status.style.color = '#c62828';
                        document.getElementById('pdf_extracted_text').value = '';
                    } else {
                        document.getElementById('pdf_extracted_text').value = fullText;
                        status.innerHTML = '✅ Text extracted successfully (' + fullText.split(/\s+/).length + ' words). Ready to analyze!';
                        status.style.color = '#2e7d32';
                    }
                } catch (e) {
                    status.innerHTML = '❌ Could not read PDF. Please use Paste Text option.';
                    status.style.color = '#c62828';
                }
            } else {
                status.innerHTML = '✅ File ready: ' + file.name;
                status.style.color = '#2e7d32';
                document.getElementById('pdf_extracted_text').value = '';
            }
        }
        </script>
    </div>

    <?php if ($result): ?>
    <?php if (!empty($extractedSkills)): ?>
    <div class="alert alert-success">✅ <strong><?= count($extractedSkills) ?> skills</strong> extracted from your resume and automatically added to your profile.</div>
    <?php endif; ?>
    <!-- Score Card -->
    <div class="analyze-grid">
        <div class="card" style="text-align:center">
            <h2>Resume Score</h2>
            <div class="score-circle" style="border-color:<?= $result['strength']['color'] ?>;color:<?= $result['strength']['color'] ?>;background:<?= $result['strength']['bg'] ?>">
                <span><?= $result['score'] ?></span>
                <span style="font-size:0.9rem;font-weight:400">/100</span>
            </div>
            <div style="display:inline-block;padding:8px 25px;border-radius:20px;background:<?= $result['strength']['bg'] ?>;color:<?= $result['strength']['color'] ?>;font-weight:700;font-size:1.1rem">
                <?= $result['strength']['label'] ?>
            </div>
            <?php if ($lastAnalysis): ?>
            <p style="color:#999;font-size:0.82rem;margin-top:10px">Last analyzed: <?= date('d M Y, h:i A', strtotime($lastAnalysis['analyzed_at'])) ?></p>
            <?php endif; ?>

            <!-- Score breakdown bar -->
            <div style="margin-top:20px;text-align:left">
                <div style="margin-bottom:8px;font-size:0.85rem;color:#666">Score Breakdown</div>
                <?php
                $bars = [
                    'Sections'     => min(40, $result['score'] > 0 ? 40 : 0),
                    'Skills'       => min(40, count($result['found_skills']['programming'] ?? []) * 3),
                    'Content'      => min(10, $result['score'] > 50 ? 10 : 5),
                    'Keywords'     => min(10, $result['score'] > 60 ? 10 : 4),
                ];
                foreach ($bars as $label => $val): ?>
                <div style="margin-bottom:6px">
                    <div style="display:flex;justify-content:space-between;font-size:0.82rem"><span><?= $label ?></span><span><?= $val ?>/<?= $label==='Sections'||$label==='Skills'?40:10 ?></span></div>
                    <div class="bar-bg"><div class="bar-fill" style="width:<?= $label==='Sections'||$label==='Skills'?($val/40*100):($val/10*100) ?>%"></div></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Sections Found -->
        <div class="card">
            <h2>📋 Resume Sections</h2>
            <p style="color:#666;font-size:0.9rem;margin-bottom:15px">Sections detected in your resume:</p>
            <div class="section-check">
                <?php
                $sectionIcons = [
                    'contact'=>'📞','objective'=>'🎯','education'=>'🎓','skills'=>'💡',
                    'projects'=>'🚀','experience'=>'💼','certifications'=>'🏆','achievements'=>'⭐'
                ];
                foreach ($result['sections'] as $sec => $found): ?>
                <span class="section-tag <?= $found ? 'tag-found' : 'tag-missing' ?>">
                    <?= $sectionIcons[$sec] ?? '📌' ?> <?= ucfirst($sec) ?> <?= $found ? '✓' : '✗' ?>
                </span>
                <?php endforeach; ?>
            </div>

            <h2 style="margin-top:25px">🎯 Job Role Matches</h2>
            <p style="color:#666;font-size:0.9rem;margin-bottom:15px">Based on your skills:</p>
            <?php if (!empty($result['matched_jobs'])): ?>
                <?php foreach ($result['matched_jobs'] as $role => $pct): ?>
                <div class="job-match-bar">
                    <div class="label"><span><?= $role ?></span><span style="color:<?= $pct>=60?'#2e7d32':($pct>=40?'#e65100':'#c62828') ?>"><?= $pct ?>%</span></div>
                    <div class="bar-bg"><div class="bar-fill" style="width:<?= $pct ?>%;background:<?= $pct>=60?'linear-gradient(90deg,#43a047,#66bb6a)':($pct>=40?'linear-gradient(90deg,#fb8c00,#ffa726)':'linear-gradient(90deg,#e53935,#ef5350)') ?>"></div></div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
            <p style="color:#999">No strong job matches found. Add more skills to your resume.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Skills Found -->
    <div class="card">
        <h2>💡 Skills Detected</h2>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:15px">
            <?php
            $catIcons = ['programming'=>'💻','frameworks'=>'🔧','databases'=>'🗄️','tools'=>'⚙️','soft_skills'=>'🤝','concepts'=>'🧠'];
            foreach ($result['found_skills'] as $cat => $skills): if (!empty($skills)): ?>
            <div>
                <div style="font-weight:700;color:#1a237e;margin-bottom:8px;font-size:0.9rem"><?= $catIcons[$cat] ?? '📌' ?> <?= ucwords(str_replace('_',' ',$cat)) ?> (<?= count($skills) ?>)</div>
                <?php foreach ($skills as $skill): ?>
                <span class="skill-tag"><?= htmlspecialchars($skill) ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; endforeach; ?>
        </div>

        <?php if (!empty($result['missing_skills'])): ?>
        <div style="margin-top:20px">
            <div style="font-weight:700;color:#c62828;margin-bottom:8px">❌ Important Missing Skills</div>
            <?php foreach ($result['missing_skills'] as $skill): ?>
            <span class="skill-tag missing"><?= htmlspecialchars($skill) ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Suggestions -->
    <div class="card">
        <h2>💬 AI Suggestions & Improvements</h2>
        <?php
        $icons = ['error'=>'❌','warning'=>'⚠️','info'=>'ℹ️','success'=>'✅'];
        foreach ($result['suggestions'] as $s): ?>
        <div class="suggestion-item <?= $s['type'] ?>">
            <span style="font-size:1.2rem"><?= $icons[$s['type']] ?></span>
            <span><?= htmlspecialchars($s['msg']) ?></span>
        </div>
        <?php endforeach; ?>

        <div style="margin-top:20px;padding:15px;background:#f5f5f5;border-radius:8px">
            <strong>📚 Recommended Resources to Improve:</strong>
            <ul style="margin-top:10px;padding-left:20px;color:#555;font-size:0.9rem;line-height:2">
                <li>🎓 <a href="https://www.coursera.org" target="_blank">Coursera</a> — Free certifications from top universities</li>
                <li>🎓 <a href="https://www.udemy.com" target="_blank">Udemy</a> — Affordable tech courses</li>
                <li>🎓 <a href="https://nptel.ac.in" target="_blank">NPTEL</a> — Free Indian university courses</li>
                <li>💻 <a href="https://github.com" target="_blank">GitHub</a> — Host your projects</li>
                <li>🏆 <a href="https://www.hackerrank.com" target="_blank">HackerRank</a> — Practice coding & get certificates</li>
                <li>🔗 <a href="https://www.linkedin.com" target="_blank">LinkedIn</a> — Build your professional profile</li>
            </ul>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function switchTab(tab, btn) {
    document.getElementById('tab-paste').style.display = tab === 'paste' ? 'block' : 'none';
    document.getElementById('tab-upload').style.display = tab === 'upload' ? 'block' : 'none';
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}
</script>
</body>
</html>
