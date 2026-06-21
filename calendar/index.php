<?php
require_once '../includes/config.php';
requireLogin();

$uid  = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Create table
$conn->query("CREATE TABLE IF NOT EXISTS calendar_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    event_type ENUM('interview','test','placement_drive','deadline','other') DEFAULT 'other',
    event_date DATE NOT NULL,
    event_time TIME,
    end_time TIME,
    location VARCHAR(200),
    meeting_link VARCHAR(500),
    created_by INT NOT NULL,
    target_role ENUM('all','student','recruiter') DEFAULT 'all',
    color VARCHAR(20) DEFAULT '#3f51b5',
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
)");

$msg = '';

// Add event (admin/recruiter)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_event']) && in_array($role, ['admin','recruiter'])) {
    $title    = $conn->real_escape_string(trim($_POST['title']));
    $desc     = $conn->real_escape_string(trim($_POST['description'] ?? ''));
    $type     = $conn->real_escape_string($_POST['event_type']);
    $date     = $conn->real_escape_string($_POST['event_date']);
    $time     = $conn->real_escape_string($_POST['event_time'] ?? '');
    $endTime  = $conn->real_escape_string($_POST['end_time'] ?? '');
    $loc      = $conn->real_escape_string($_POST['location'] ?? '');
    $link     = $conn->real_escape_string($_POST['meeting_link'] ?? '');
    $target   = $conn->real_escape_string($_POST['target_role'] ?? 'all');
    $colors   = ['interview'=>'#1565c0','test'=>'#7b1fa2','placement_drive'=>'#2e7d32','deadline'=>'#c62828','other'=>'#546e7a'];
    $color    = $colors[$type] ?? '#3f51b5';
    if ($title && $date) {
        $conn->query("INSERT INTO calendar_events (title, description, event_type, event_date, event_time, end_time, location, meeting_link, created_by, target_role, color)
            VALUES ('$title','$desc','$type','$date','$time','$endTime','$loc','$link',$uid,'$target','$color')");
        // Notify students
        if (in_array($target, ['all','student'])) {
            require_once '../includes/notify.php';
            $students = $conn->query("SELECT id FROM users WHERE role='student'");
            while ($s = $students->fetch_assoc()) {
                createNotification($conn, $s['id'], 'system', "📅 New Event: $title", "A new $type has been scheduled on $date.", '/placement system/calendar/index.php');
            }
        }
        $msg = '<div class="alert alert-success">Event added successfully!</div>';
    }
}

// Delete event
if (isset($_GET['del']) && in_array($role, ['admin','recruiter'])) {
    $eid = (int)$_GET['del'];
    $conn->query("DELETE FROM calendar_events WHERE id=$eid AND created_by=$uid" . ($role === 'admin' ? ' OR id='.$eid : ''));
    header("Location: index.php"); exit();
}

// Current month/year
$month = (int)($_GET['month'] ?? date('n'));
$year  = (int)($_GET['year']  ?? date('Y'));
if ($month < 1) { $month = 12; $year--; }
if ($month > 12) { $month = 1; $year++; }

$prevMonth = $month - 1; $prevYear = $year;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
$nextMonth = $month + 1; $nextYear = $year;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

// Events for this month
$roleFilter = $role === 'student' ? "AND (target_role='all' OR target_role='student')" : ($role === 'recruiter' ? "AND (target_role='all' OR target_role='recruiter')" : "");
$events = $conn->query("SELECT * FROM calendar_events WHERE MONTH(event_date)=$month AND YEAR(event_date)=$year $roleFilter ORDER BY event_date, event_time");

// Also pull interviews/tests into calendar
$autoEvents = [];
if ($role === 'student') {
    $ivs = $conn->query("SELECT 'interview' as etype, j.title as etitle, c.company_name, iv.scheduled_at, iv.meeting_link
        FROM interviews iv JOIN jobs j ON iv.job_id=j.id JOIN companies c ON j.company_id=c.id
        WHERE iv.student_id=$uid AND MONTH(iv.scheduled_at)=$month AND YEAR(iv.scheduled_at)=$year AND iv.status='scheduled'");
    while ($iv = $ivs->fetch_assoc()) $autoEvents[] = $iv;

    $tsts = $conn->query("SELECT 'test' as etype, t.title as etitle, ta.started_at as scheduled_at
        FROM test_attempts ta JOIN tests t ON ta.test_id=t.id
        WHERE ta.student_id=$uid AND MONTH(ta.started_at)=$month AND YEAR(ta.started_at)=$year");
    while ($t = $tsts->fetch_assoc()) $autoEvents[] = $t;
}

// Group events by day
$eventsByDay = [];
while ($e = $events->fetch_assoc()) {
    $day = (int)date('j', strtotime($e['event_date']));
    $eventsByDay[$day][] = $e;
}
foreach ($autoEvents as $ae) {
    $day = (int)date('j', strtotime($ae['scheduled_at']));
    $eventsByDay[$day][] = $ae + ['auto' => true];
}

$daysInMonth  = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$firstDayOfWeek = (int)date('w', mktime(0,0,0,$month,1,$year));
$monthName    = date('F Y', mktime(0,0,0,$month,1,$year));
$today        = (int)date('j');
$isCurrentMonth = ($month == date('n') && $year == date('Y'));

$typeColors = ['interview'=>'#1565c0','test'=>'#7b1fa2','placement_drive'=>'#2e7d32','deadline'=>'#c62828','other'=>'#546e7a'];
$typeIcons  = ['interview'=>'🎥','test'=>'📝','placement_drive'=>'🏢','deadline'=>'⏰','other'=>'📅'];

$dashLink   = ($role === 'admin') ? '../admin/dashboard.php' : (($role === 'recruiter') ? '../recruiter/dashboard.php' : '../student/dashboard.php');
$logoutLink = '../' . $role . '/logout.php';

// Upcoming events list
$upcoming = $conn->query("SELECT * FROM calendar_events WHERE event_date >= CURDATE() $roleFilter ORDER BY event_date, event_time LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Smart Calendar</title>
<link rel="stylesheet" href="../css/style.css">
<style>
.cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:2px}
.cal-header-cell{background:#1a237e;color:#fff;text-align:center;padding:10px 5px;font-size:0.82rem;font-weight:700;border-radius:4px}
.cal-cell{background:#fff;border-radius:6px;padding:6px;min-height:80px;border:1px solid #e8eaf6;transition:all 0.2s;vertical-align:top}
.cal-cell:hover{border-color:#3f51b5;box-shadow:0 2px 8px rgba(63,81,181,0.15)}
.cal-cell.today{border:2px solid #3f51b5;background:#f0f4ff}
.cal-cell.empty{background:#f8f9ff;border-color:#f0f0f0}
.cal-day-num{font-weight:700;font-size:0.85rem;color:#1a237e;margin-bottom:4px}
.cal-day-num.today-num{background:#3f51b5;color:#fff;border-radius:50%;width:24px;height:24px;display:flex;align-items:center;justify-content:center;font-size:0.78rem}
.event-dot{font-size:0.7rem;padding:2px 5px;border-radius:4px;color:#fff;margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;cursor:pointer}
.upcoming-item{display:flex;gap:12px;align-items:flex-start;padding:12px;border-radius:8px;background:#f8f9ff;margin-bottom:8px;border-left:4px solid #3f51b5}
</style>
</head>
<body>
<nav class="navbar">
    <a href="<?= $dashLink ?>" class="brand">🎓 Campus<span>Recruit</span></a>
    <div class="nav-links">
        <a href="<?= $dashLink ?>">Dashboard</a>
        <a href="index.php" class="active">📅 Calendar</a>
        <?php require_once '../notifications/widget.php'; ?>
        <a href="<?= $logoutLink ?>" class="btn-logout">Logout</a>
    </div>
</nav>

<div class="container">
    <?= $msg ?>

    <div style="display:grid;grid-template-columns:1fr 300px;gap:20px">
        <!-- Calendar -->
        <div>
            <div class="card">
                <!-- Month Navigation -->
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
                    <a href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?>" class="btn btn-sm" style="background:#e8eaf6;color:#333">← Prev</a>
                    <h2 style="border:none;padding:0;margin:0;font-size:1.3rem">📅 <?= $monthName ?></h2>
                    <a href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?>" class="btn btn-sm" style="background:#e8eaf6;color:#333">Next →</a>
                </div>

                <!-- Day Headers -->
                <div class="cal-grid" style="margin-bottom:4px">
                    <?php foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d): ?>
                    <div class="cal-header-cell"><?= $d ?></div>
                    <?php endforeach; ?>
                </div>

                <!-- Calendar Cells -->
                <div class="cal-grid">
                    <?php
                    for ($i = 0; $i < $firstDayOfWeek; $i++):
                    ?>
                    <div class="cal-cell empty"></div>
                    <?php endfor; ?>

                    <?php for ($day = 1; $day <= $daysInMonth; $day++):
                        $isToday = $isCurrentMonth && $day === $today;
                        $hasEvents = isset($eventsByDay[$day]);
                    ?>
                    <div class="cal-cell <?= $isToday ? 'today' : '' ?>">
                        <div class="cal-day-num <?= $isToday ? 'today-num' : '' ?>"><?= $day ?></div>
                        <?php if ($hasEvents): ?>
                        <?php foreach (array_slice($eventsByDay[$day], 0, 3) as $ev):
                            if (isset($ev['auto'])) {
                                $evColor = $ev['etype'] === 'interview' ? '#1565c0' : '#7b1fa2';
                                $evTitle = ($ev['etype'] === 'interview' ? '🎥 ' : '📝 ') . substr($ev['etitle'], 0, 18);
                            } else {
                                $evColor = $ev['color'];
                                $evTitle = ($typeIcons[$ev['event_type']] ?? '📅') . ' ' . substr($ev['title'], 0, 18);
                            }
                        ?>
                        <div class="event-dot" style="background:<?= $evColor ?>" title="<?= htmlspecialchars($evTitle) ?>"><?= htmlspecialchars($evTitle) ?></div>
                        <?php endforeach; ?>
                        <?php if (count($eventsByDay[$day]) > 3): ?>
                        <div style="font-size:0.68rem;color:#999">+<?= count($eventsByDay[$day]) - 3 ?> more</div>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <?php endfor; ?>
                </div>

                <!-- Legend -->
                <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:15px;padding-top:12px;border-top:1px solid #f0f0f0">
                    <?php foreach ($typeColors as $type => $color): ?>
                    <div style="display:flex;align-items:center;gap:5px;font-size:0.78rem">
                        <div style="width:12px;height:12px;border-radius:3px;background:<?= $color ?>"></div>
                        <?= $typeIcons[$type] ?> <?= ucfirst(str_replace('_',' ',$type)) ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div>
            <!-- Add Event (admin/recruiter) -->
            <?php if (in_array($role, ['admin','recruiter'])): ?>
            <div class="card">
                <h2 style="font-size:1rem">➕ Add Event</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Title *</label>
                        <input type="text" name="title" required placeholder="Event title">
                    </div>
                    <div class="form-group">
                        <label>Type *</label>
                        <select name="event_type" required>
                            <option value="interview">🎥 Interview</option>
                            <option value="test">📝 Test</option>
                            <option value="placement_drive">🏢 Placement Drive</option>
                            <option value="deadline">⏰ Deadline</option>
                            <option value="other">📅 Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Date *</label>
                        <input type="date" name="event_date" required min="<?= date('Y-m-d') ?>">
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                        <div class="form-group">
                            <label>Start Time</label>
                            <input type="time" name="event_time">
                        </div>
                        <div class="form-group">
                            <label>End Time</label>
                            <input type="time" name="end_time">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="location" placeholder="Room / Online">
                    </div>
                    <div class="form-group">
                        <label>Meeting Link</label>
                        <input type="url" name="meeting_link" placeholder="https://meet.google.com/...">
                    </div>
                    <div class="form-group">
                        <label>Visible To</label>
                        <select name="target_role">
                            <option value="all">Everyone</option>
                            <option value="student">Students Only</option>
                            <option value="recruiter">Recruiters Only</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="2" placeholder="Optional details..."></textarea>
                    </div>
                    <button name="add_event" class="btn btn-primary" style="width:100%">Add Event</button>
                </form>
            </div>
            <?php endif; ?>

            <!-- Upcoming Events -->
            <div class="card">
                <h2 style="font-size:1rem">📋 Upcoming Events</h2>
                <?php if ($upcoming->num_rows === 0): ?>
                <p style="color:#999;font-size:0.85rem;text-align:center;padding:15px">No upcoming events.</p>
                <?php else: ?>
                <?php while($e = $upcoming->fetch_assoc()): ?>
                <div class="upcoming-item" style="border-left-color:<?= $e['color'] ?>">
                    <div style="font-size:1.3rem"><?= $typeIcons[$e['event_type']] ?? '📅' ?></div>
                    <div style="flex:1">
                        <div style="font-weight:700;color:#1a237e;font-size:0.88rem"><?= htmlspecialchars($e['title']) ?></div>
                        <div style="font-size:0.75rem;color:#666;margin-top:2px">
                            📅 <?= date('d M Y', strtotime($e['event_date'])) ?>
                            <?php if ($e['event_time']): ?> · ⏰ <?= date('h:i A', strtotime($e['event_time'])) ?><?php endif; ?>
                        </div>
                        <?php if ($e['location']): ?><div style="font-size:0.73rem;color:#999">📍 <?= htmlspecialchars($e['location']) ?></div><?php endif; ?>
                        <?php if ($e['meeting_link']): ?><a href="<?= htmlspecialchars($e['meeting_link']) ?>" target="_blank" style="font-size:0.73rem;color:#1565c0">🔗 Join Meeting</a><?php endif; ?>
                    </div>
                    <?php if (in_array($role, ['admin','recruiter'])): ?>
                    <a href="?del=<?= $e['id'] ?>" onclick="return confirm('Delete event?')" style="color:#e53935;font-size:0.8rem">🗑️</a>
                    <?php endif; ?>
                </div>
                <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require_once '../chatbot/widget.php'; ?>
</body>
</html>
