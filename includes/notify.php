<?php
/**
 * Notification Helper
 * Call createNotification() anywhere to send a notification to a user.
 */

function ensureNotificationsTable($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        type ENUM('job','application','interview','test','notice','system') DEFAULT 'system',
        title VARCHAR(200) NOT NULL,
        message TEXT NOT NULL,
        link VARCHAR(500),
        is_read TINYINT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
}

/**
 * Create a notification for a specific user.
 *
 * @param mysqli $conn
 * @param int    $userId   Target user ID
 * @param string $type     job|application|interview|test|notice|system
 * @param string $title    Short title
 * @param string $message  Full message
 * @param string $link     Optional URL to redirect on click
 */
function createNotification($conn, $userId, $type, $title, $message, $link = '') {
    ensureNotificationsTable($conn);
    $userId  = (int)$userId;
    $type    = $conn->real_escape_string($type);
    $title   = $conn->real_escape_string($title);
    $message = $conn->real_escape_string($message);
    $link    = $conn->real_escape_string($link);
    $conn->query("INSERT INTO notifications (user_id, type, title, message, link)
        VALUES ($userId, '$type', '$title', '$message', '$link')");
}

/**
 * Notify all students about a new job.
 */
function notifyAllStudentsNewJob($conn, $jobTitle, $companyName, $jobId) {
    ensureNotificationsTable($conn);
    $students = $conn->query("SELECT id FROM users WHERE role='student'");
    while ($s = $students->fetch_assoc()) {
        createNotification(
            $conn, $s['id'], 'job',
            "New Job: $jobTitle",
            "$companyName is hiring for $jobTitle. Apply before the deadline!",
            "/placement/student/jobs.php"
        );
    }
}

/**
 * Notify a student about application status change.
 */
function notifyApplicationStatus($conn, $studentId, $status, $jobTitle, $companyName) {
    $icons   = ['shortlisted'=>'⭐','selected'=>'🎉','rejected'=>'❌','applied'=>'📋'];
    $msgs    = [
        'shortlisted' => "Congratulations! You have been shortlisted for $jobTitle at $companyName.",
        'selected'    => "🎉 Amazing! You have been SELECTED for $jobTitle at $companyName!",
        'rejected'    => "Your application for $jobTitle at $companyName was not selected this time. Keep applying!",
        'applied'     => "Your application for $jobTitle at $companyName has been received.",
    ];
    $icon = $icons[$status] ?? '📋';
    createNotification(
        $conn, $studentId, 'application',
        "$icon Application " . ucfirst($status) . ": $jobTitle",
        $msgs[$status] ?? "Your application status has been updated.",
        "/placement/student/applications.php"
    );
}

/**
 * Notify a student about a scheduled interview.
 */
function notifyInterviewScheduled($conn, $studentId, $jobTitle, $companyName, $scheduledAt) {
    $dateStr = date('D, d M Y \a\t h:i A', strtotime($scheduledAt));
    createNotification(
        $conn, $studentId, 'interview',
        "🎥 Interview Scheduled: $jobTitle",
        "Your interview for $jobTitle at $companyName is scheduled on $dateStr. Check your interviews page for the meeting link.",
        "/placement/student/interviews/index.php"
    );
}

/**
 * Notify all students about a new test.
 */
function notifyAllStudentsNewTest($conn, $testTitle, $category, $testId) {
    ensureNotificationsTable($conn);
    $students = $conn->query("SELECT id FROM users WHERE role='student'");
    while ($s = $students->fetch_assoc()) {
        createNotification(
            $conn, $s['id'], 'test',
            "📝 New Test Available: $testTitle",
            "A new " . ucfirst($category) . " test '$testTitle' has been published. Take it now to boost your placement score!",
            "/placement/student/aptitude_test/index.php"
        );
    }
}

/**
 * Notify all students about a new notice.
 */
function notifyAllStudentsNewNotice($conn, $noticeTitle) {
    ensureNotificationsTable($conn);
    $students = $conn->query("SELECT id FROM users WHERE role='student'");
    while ($s = $students->fetch_assoc()) {
        createNotification(
            $conn, $s['id'], 'notice',
            "📢 New Notice: $noticeTitle",
            "A new placement notice has been posted: $noticeTitle",
            "/placement/student/notices.php"
        );
    }
}
