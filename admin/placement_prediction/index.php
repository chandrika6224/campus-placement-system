<?php
require_once '../../includes/config.php';
requireLogin('admin');
require_once '../../student/placement_prediction/predictor.php';

// Get all students
$students = $conn->query("SELECT u.id, u.name, u.email FROM users u WHERE u.role='student' ORDER BY u.name ASC");

$predictions = [];
while ($s = $students->fetch_assoc()) {
    $data = PlacementPredictor::getStudentData($conn, $s['id']);
    $pred = PlacementPredictor::predict($data);
    $predictions[] = [
        'id'         => $s['id'],
        'name'       => $s['name'],
        'email'      => $s['email'],
        'dept'       => $data['department'] ?? 'N/A',
        'cgpa'       => $data['cgpa'] ?? 0,
        'skills'     => $data['skills'] ?? '',
        'applied'    => $data['applied'],
        'shortlisted'=> $data['shortlisted'],
        'selected'   => $data['selected'],
        'resume_score'=> $data['resume_score'],
        'avg_test'   => round($data['avg_test_pct']),
        'probability'=> $pred['probability'],
        'grade'      => $pred['grade'],
        'gradeLabel' => $pred['gradeLabel'],
        'gradeColor' => $pred['gradeColor'],
        'gradeBg'    => $pred['gradeBg'],
    ];
}

// Sort by probability desc
usort($predictions, fn($a,$b) => $b['probability'] - $a['probability']);

// Summary stats
$total = count($predictions);
$highCount  = count(array_filter($predictions, fn($p) => $p['probability'] >= 65));
$medCount   = count(array_filter($predictions, fn($p) => $p['probability'] >= 35 && $p['probability'] < 65));
$lowCount   = count(array_filter($predictions, fn($p) => $p['probability'] < 35));
$avgProb    = $total > 0 ? round(array_sum(array_column($predictions,'probability')) / $total) : 0;

$deptFilter = $_GET['dept'] ?? 'all';
$gradeFilter = $_GET['grade'] ?? 'all';
$depts = array_unique(array_column($predictions, 'dept'));
sort($depts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Placement Predictions - Admin</title>
<link rel="stylesheet" href="../../css/style.css">
<style>
.prob-bar-bg { width:80px;height:8px;background:#e0e0e0;border-radius:4px;display:inline-block;vertical-align:middle;margin-right:6px; }
.prob-bar-fill { height:8px;border-radius:4px; }
.grade-badge { display:inline-block;padding:3px 10px;border-radius:12px;font-size:0.78rem;font-weight:800; }
.risk-badge { display:inline-block;padding:3px 10px;border-radius:12px;font-size:0.75rem;font-weight:700; }
.clickable-row { cursor:pointer; transition:background 0.15s; }
.clickable-row:hover { background:#f3f4ff !important; }
.modal-overlay { display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;pointer-events:none; }
.modal-overlay.open { display:flex;pointer-events:all; }
.modal-box { background:#fff;border-radius:14px;width:700px;max-width:95vw;max-height:90vh;overflow-y:auto;box-shadow:0 8px 40px rgba(0,0,0,0.2); }
.modal-header { padding:20px 24px 14px;border-bottom:1px solid #e8eaf6;display:flex;justify-content:space-between;align-items:center; }
.modal-body { padding:20px 24px; }
.modal-close { background:none;border:none;font-size:1.4rem;cursor:pointer;color:#666;line-height:1; }
.factor-row { display:flex;align-items:center;gap:10px;margin-bottom:10px; }
.factor-bar-bg { flex:1;height:8px;background:#e0e0e0;border-radius:4px; }
.factor-bar-fill { height:8px;border-radius:4px; }
.tip-box { padding:8px 12px;border-radius:7px;font-size:0.82rem;margin-bottom:6px; }
.tip-error   { background:#ffebee;color:#c62828; }
.tip-warning { background:#fff8e1;color:#e65100; }
.tip-info    { background:#e3f2fd;color:#1565c0; }
.tip-success { background:#e8f5e9;color:#2e7d32; }
.detail-grid { display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px; }
.detail-item { background:#f5f6fa;border-radius:8px;padding:10px 14px; }
.detail-item .di-label { font-size:0.72rem;color:#888;font-weight:600;text-transform:uppercase; }
.detail-item .di-val { font-size:1rem;font-weight:700;color:#1a237e;margin-top:2px; }
#pred-table th { font-size:0.75rem;padding:8px 10px; }
#pred-table td { font-size:0.78rem;padding:7px 10px; }
</style>
</head>
<body>
<?php require_once '../sidebar.php'; ?>
<div class="topbar">
    <div class="topbar-left">
        <button class="hamburger" onclick="toggleSidebar()">☰</button>
        <span class="page-title">🔮 Placement Predictions</span>
    </div>
    <div class="topbar-right"><?php require_once '../../notifications/widget.php'; ?></div>
</div>
<div class="main-content">
    <div class="card" style="background:linear-gradient(135deg,#1a237e,#3949ab);color:#fff;margin-bottom:25px">
        <h2 style="color:#ffd54f;margin-bottom:8px">🔮 Placement Prediction Report</h2>
        <p style="color:#c5cae9">ML-based placement probability for all students. Identify students who need support.</p>
    </div>

    <!-- Stats -->
    <div class="stats-grid" style="grid-template-columns:repeat(5,1fr)">
        <div class="stat-card"><div class="number"><?= $total ?></div><div class="label">👨‍🎓 Total Students</div></div>
        <div class="stat-card"><div class="number"><?= $avgProb ?>%</div><div class="label">📊 Avg Probability</div></div>
        <div class="stat-card green"><div class="number"><?= $highCount ?></div><div class="label">🟢 High Chance (65%+)</div></div>
        <div class="stat-card orange"><div class="number"><?= $medCount ?></div><div class="label">🟡 Moderate (35-64%)</div></div>
        <div class="stat-card red"><div class="number"><?= $lowCount ?></div><div class="label">🔴 At Risk (&lt;35%)</div></div>
    </div><!-- /stats-grid -->

    <!-- Filters -->
    <div class="card">
        <div style="display:flex;gap:20px;flex-wrap:wrap;align-items:center">
            <div style="display:flex;align-items:center;gap:8px">
                <span style="font-weight:600;font-size:0.88rem;color:#555">Dept:</span>
                <select id="dept-filter" onchange="filterTable()" style="padding:6px 12px;border:2px solid #ddd;border-radius:6px;font-size:0.88rem;background:#fff;color:#333;cursor:pointer;min-width:160px">
                    <option value="all">All Departments</option>
                    <?php foreach ($depts as $d): ?>
                    <option value="<?= htmlspecialchars($d) ?>"><?= htmlspecialchars($d) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="margin-left:auto;font-size:0.88rem;color:#666">
                Showing <span id="row-count"><?= $total ?></span> students
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card">
        <div class="table-wrap">
            <table id="pred-table">
                <tr>
                    <th>#</th>
                    <th>Student</th>
                    <th>Dept</th>
                    <th>CGPA</th>
                    <th>Skills</th>
                    <th>Resume</th>
                    <th>Applied</th>
                    <th>Shortlisted</th>
                    <th>Selected</th>
                    <th>Probability</th>
                    <th>Grade</th>
                </tr>
                <?php foreach ($predictions as $i => $p):
                    $skillCount = count(array_filter(array_map('trim', explode(',', $p['skills']))));
                    $barColor = $p['probability'] >= 65 ? '#43a047' : ($p['probability'] >= 35 ? '#fb8c00' : '#e53935');
                ?>
                <tr class="clickable-row" data-dept="<?= htmlspecialchars(trim($p['dept'])) ?>" data-grade="<?= htmlspecialchars(trim($p['grade'])) ?>" onclick="openModal(<?= $i ?>)">
                    <td><?= $i+1 ?></td>
                    <td>
                        <strong><?= htmlspecialchars($p['name']) ?></strong><br>
                        <small style="color:#999"><?= htmlspecialchars($p['email']) ?></small>
                    </td>
                    <td><?= htmlspecialchars($p['dept']) ?></td>
                    <td>
                        <span style="font-weight:700;color:<?= $p['cgpa']>=7?'#2e7d32':($p['cgpa']>=6?'#e65100':'#c62828') ?>">
                            <?= $p['cgpa'] ?: 'N/A' ?>
                        </span>
                    </td>
                    <td><?= $skillCount ?> skills</td>
                    <td>
                        <?php if ($p['resume_score'] > 0): ?>
                        <span style="color:<?= $p['resume_score']>=60?'#2e7d32':'#e65100' ?>;font-weight:700"><?= $p['resume_score'] ?>/100</span>
                        <?php else: ?><span style="color:#999">N/A</span><?php endif; ?>
                    </td>
                    <td><?= $p['applied'] ?></td>
                    <td><?= $p['shortlisted'] ?></td>
                    <td>
                        <?php if ($p['selected'] > 0): ?>
                        <span style="color:#2e7d32;font-weight:700">✅ <?= $p['selected'] ?></span>
                        <?php else: ?>0<?php endif; ?>
                    </td>
                    <td>
                        <div style="display:flex;align-items:center;gap:6px">
                            <div class="prob-bar-bg"><div class="prob-bar-fill" style="width:<?= $p['probability'] ?>%;background:<?= $barColor ?>"></div></div>
                            <span style="font-weight:800;color:<?= $barColor ?>"><?= $p['probability'] ?>%</span>
                        </div>
                    </td>
                    <td>
                        <span class="grade-badge" style="background:<?= $p['gradeBg'] ?>;color:<?= $p['gradeColor'] ?>">
                            <?= $p['grade'] ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</div><!-- /main-content -->
</div><!-- /app-layout -->

<!-- Student Detail Modal -->
<div class="modal-overlay" id="studentModal" onclick="if(event.target===this)closeModal()">
    <div class="modal-box">
        <div class="modal-header">
            <div>
                <div style="font-size:1.1rem;font-weight:800;color:#1a237e" id="modal-name"></div>
                <div style="font-size:0.82rem;color:#888" id="modal-email"></div>
            </div>
            <button class="modal-close" onclick="closeModal()">✕</button>
        </div>
        <div class="modal-body" id="modal-body"></div>
    </div>
</div>

<script>
const studentData = <?= json_encode(array_values(array_map(function($p) use ($conn) {
    $data = PlacementPredictor::getStudentData($conn, $p['id']);
    $pred = PlacementPredictor::predict($data);
    return [
        'id'          => $p['id'],
        'name'        => $p['name'],
        'email'       => $p['email'],
        'dept'        => $p['dept'],
        'cgpa'        => $p['cgpa'],
        'skills'      => $p['skills'],
        'applied'     => $p['applied'],
        'shortlisted' => $p['shortlisted'],
        'selected'    => $p['selected'],
        'resume_score'=> $p['resume_score'],
        'avg_test'    => $p['avg_test'],
        'probability' => $p['probability'],
        'grade'       => $p['grade'],
        'gradeLabel'  => $p['gradeLabel'],
        'gradeColor'  => $p['gradeColor'],
        'gradeBg'     => $p['gradeBg'],
        'factors'     => $pred['factors'],
        'tips'        => array_map(function($t){ return ['type'=>$t['type'],'msg'=>htmlspecialchars($t['msg'])]; }, $pred['tips']),
        'interviews'  => $data['interviews'],
        'phone'       => $data['phone'] ?? '',
        'year'        => $data['year_of_passing'] ?? '',
    ];
}, $predictions)), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>;
</script>
<script>
function openModal(idx) {
    const p = studentData[idx];
    document.getElementById('modal-name').textContent = p.name;
    document.getElementById('modal-email').textContent = p.email;

    const barColor = p.probability >= 65 ? '#43a047' : (p.probability >= 35 ? '#fb8c00' : '#e53935');
    const skillList = p.skills ? p.skills.split(',').map(s=>s.trim()).filter(Boolean) : [];

    let html = `
    <div class="detail-grid">
        <div class="detail-item"><div class="di-label">Department</div><div class="di-val">${p.dept||'N/A'}</div></div>
        <div class="detail-item"><div class="di-label">CGPA</div><div class="di-val">${p.cgpa||'N/A'}</div></div>
        <div class="detail-item"><div class="di-label">Year of Passing</div><div class="di-val">${p.year||'N/A'}</div></div>
        <div class="detail-item"><div class="di-label">Phone</div><div class="di-val">${p.phone||'N/A'}</div></div>
        <div class="detail-item"><div class="di-label">Applications</div><div class="di-val">${p.applied} applied &nbsp;·&nbsp; ${p.shortlisted} shortlisted &nbsp;·&nbsp; ${p.selected} selected</div></div>
        <div class="detail-item"><div class="di-label">Interviews</div><div class="di-val">${p.interviews}</div></div>
        <div class="detail-item"><div class="di-label">Resume Score</div><div class="di-val">${p.resume_score > 0 ? p.resume_score+'/100' : 'N/A'}</div></div>
        <div class="detail-item"><div class="di-label">Test Average</div><div class="di-val">${p.avg_test > 0 ? p.avg_test+'%' : 'N/A'}</div></div>
    </div>

    <div style="margin-bottom:16px">
        <div style="font-weight:700;color:#1a237e;margin-bottom:6px">Skills (${skillList.length})</div>
        <div style="display:flex;flex-wrap:wrap;gap:6px">
            ${skillList.length ? skillList.map(s=>`<span style="background:#e8eaf6;color:#3949ab;padding:3px 10px;border-radius:12px;font-size:0.8rem;font-weight:600">${s}</span>`).join('') : '<span style="color:#aaa">No skills listed</span>'}
        </div>
    </div>

    <div style="background:${p.gradeBg};border-radius:10px;padding:14px 18px;margin-bottom:16px;display:flex;align-items:center;gap:16px">
        <div style="font-size:2rem;font-weight:900;color:${p.gradeColor}">${p.grade}</div>
        <div>
            <div style="font-weight:700;color:${p.gradeColor};font-size:1rem">${p.gradeLabel}</div>
            <div style="font-size:0.82rem;color:#555">Placement Probability</div>
        </div>
        <div style="margin-left:auto;font-size:2rem;font-weight:900;color:${barColor}">${p.probability}%</div>
    </div>

    <div style="font-weight:700;color:#1a237e;margin-bottom:10px">📊 Prediction Factors</div>
    ${p.factors.map(f => {
        const pct = Math.round(f.score / f.max * 100);
        return `<div class="factor-row">
            <span style="width:22px;text-align:center">${f.icon}</span>
            <span style="width:220px;font-size:0.82rem;color:#444">${f.label}</span>
            <div class="factor-bar-bg"><div class="factor-bar-fill" style="width:${pct}%;background:${f.color}"></div></div>
            <span style="width:60px;text-align:right;font-size:0.82rem;font-weight:700;color:${f.color}">${f.score}/${f.max}</span>
            <span style="width:70px;font-size:0.75rem;color:${f.color};font-weight:600">${f.status}</span>
        </div>`;
    }).join('')}

    ${p.tips.length ? `<div style="font-weight:700;color:#1a237e;margin:14px 0 8px">💡 Improvement Tips</div>
    ${p.tips.map(t=>`<div class="tip-box tip-${t.type}">${t.msg}</div>`).join('')}` : ''}
    `;

    document.getElementById('modal-body').innerHTML = html;
    document.getElementById('studentModal').classList.add('open');
}
function closeModal() {
    document.getElementById('studentModal').classList.remove('open');
}
function filterTable() {
    var dept = document.getElementById('dept-filter').value;
    var visible = 0;
    document.querySelectorAll('#pred-table tr.clickable-row').forEach(function(row){
        var show = (dept === 'all' || row.getAttribute('data-dept') === dept);
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    document.getElementById('row-count').textContent = visible;
}
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('show');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('sidebarOverlay').classList.remove('show');}
</script>
</body>
</html>
