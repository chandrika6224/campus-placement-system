-- ============================================================
-- Campus Placement System - Complete Database Schema
-- Database: placementsystem
-- ============================================================

CREATE DATABASE IF NOT EXISTS placementsystem;
USE placementsystem;

-- ── Core Tables ──────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','student','recruiter') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS student_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    roll_number VARCHAR(50),
    department VARCHAR(100),
    year_of_passing INT,
    cgpa DECIMAL(4,2),
    skills TEXT,
    resume_path VARCHAR(255),
    phone VARCHAR(15),
    address TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    company_name VARCHAR(150) NOT NULL,
    industry VARCHAR(100),
    website VARCHAR(200),
    description TEXT,
    contact_person VARCHAR(100),
    phone VARCHAR(15),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    requirements TEXT,
    salary_range VARCHAR(100),
    location VARCHAR(100),
    job_type ENUM('Full-time','Part-time','Internship') DEFAULT 'Full-time',
    min_cgpa DECIMAL(4,2) DEFAULT 0,
    deadline DATE,
    status ENUM('open','closed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    student_id INT NOT NULL,
    status ENUM('applied','shortlisted','rejected','selected') DEFAULT 'applied',
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    feedback TEXT DEFAULT NULL,
    feedback_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_application (job_id, student_id)
);

-- Add feedback columns if table already exists
ALTER TABLE applications
    ADD COLUMN IF NOT EXISTS feedback TEXT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS feedback_at TIMESTAMP NULL DEFAULT NULL;

CREATE TABLE IF NOT EXISTS notices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    posted_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (posted_by) REFERENCES users(id) ON DELETE CASCADE
);

-- ── Student Profile Extra Columns (dataset import) ─────────────
ALTER TABLE student_profiles
    ADD COLUMN IF NOT EXISTS gender ENUM('Male','Female','Other') DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS tenth_board VARCHAR(100) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS tenth_percent DECIMAL(5,2) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS twelfth_board VARCHAR(100) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS twelfth_percent DECIMAL(5,2) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS has_internship TINYINT DEFAULT 0;

-- ── Notifications ─────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('job','application','interview','test','notice','system') DEFAULT 'system',
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(500),
    is_read TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ── Resume Analysis ───────────────────────────────────────────

CREATE TABLE IF NOT EXISTS resume_analysis (
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
);

-- ── Aptitude Tests ────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    category ENUM('aptitude','technical','coding') DEFAULT 'aptitude',
    duration INT DEFAULT 30,
    total_marks INT DEFAULT 0,
    pass_marks INT DEFAULT 0,
    status ENUM('active','inactive') DEFAULT 'active',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS test_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_id INT NOT NULL,
    question TEXT NOT NULL,
    option_a VARCHAR(255) NOT NULL,
    option_b VARCHAR(255) NOT NULL,
    option_c VARCHAR(255) NOT NULL,
    option_d VARCHAR(255) NOT NULL,
    correct_answer ENUM('a','b','c','d') NOT NULL,
    marks INT DEFAULT 1,
    FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS test_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_id INT NOT NULL,
    student_id INT NOT NULL,
    score INT DEFAULT 0,
    total_marks INT DEFAULT 0,
    correct_answers INT DEFAULT 0,
    wrong_answers INT DEFAULT 0,
    status ENUM('started','completed') DEFAULT 'started',
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS test_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attempt_id INT NOT NULL,
    question_id INT NOT NULL,
    selected_answer ENUM('a','b','c','d') NULL,
    is_correct TINYINT DEFAULT 0,
    FOREIGN KEY (attempt_id) REFERENCES test_attempts(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES test_questions(id) ON DELETE CASCADE
);

-- ── Interviews ────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS interviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    job_id INT NOT NULL,
    student_id INT NOT NULL,
    company_id INT NOT NULL,
    scheduled_at DATETIME NOT NULL,
    duration INT DEFAULT 60,
    meeting_link VARCHAR(500),
    platform ENUM('google_meet','zoom','teams','jitsi','other') DEFAULT 'google_meet',
    notes TEXT,
    status ENUM('scheduled','completed','cancelled','rescheduled') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

-- ── Coding Platform ───────────────────────────────────────────

CREATE TABLE IF NOT EXISTS coding_problems (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    title        VARCHAR(200) NOT NULL,
    description  TEXT,
    difficulty   ENUM('easy','medium','hard') DEFAULT 'easy',
    category     VARCHAR(100) DEFAULT 'General',
    sample_input  TEXT,
    sample_output TEXT,
    hints        TEXT,
    tags         VARCHAR(300),
    points       INT DEFAULT 10,
    company_tag  VARCHAR(100) DEFAULT NULL,
    year_asked   YEAR DEFAULT NULL,
    status       ENUM('active','inactive') DEFAULT 'active',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS coding_submissions (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL,
    problem_id    INT NOT NULL,
    language      VARCHAR(20),
    code          TEXT,
    status        ENUM('accepted','wrong','error','partial','timeout') DEFAULT 'wrong',
    points_earned INT DEFAULT 0,
    exec_time     INT DEFAULT 0,
    submitted_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)    REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (problem_id) REFERENCES coding_problems(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS coding_test_cases (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    problem_id      INT NOT NULL,
    input           TEXT NOT NULL,
    expected_output TEXT NOT NULL,
    is_sample       TINYINT DEFAULT 0,
    FOREIGN KEY (problem_id) REFERENCES coding_problems(id) ON DELETE CASCADE
);

-- ── Documents ─────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    doc_type ENUM('certificate','marksheet','id_proof','offer_letter','other') NOT NULL,
    doc_name VARCHAR(200) NOT NULL,
    file_path VARCHAR(300) NOT NULL,
    file_size INT DEFAULT 0,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    admin_remarks TEXT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ── Internships ───────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS internships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    requirements TEXT,
    stipend VARCHAR(100),
    location VARCHAR(100),
    duration VARCHAR(50),
    min_cgpa DECIMAL(4,2) DEFAULT 0,
    deadline DATE,
    status ENUM('open','closed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS internship_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    internship_id INT NOT NULL,
    student_id INT NOT NULL,
    status ENUM('applied','shortlisted','rejected','selected','completed') DEFAULT 'applied',
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completion_date DATE NULL,
    certificate_issued TINYINT DEFAULT 0,
    FOREIGN KEY (internship_id) REFERENCES internships(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_intern_app (internship_id, student_id)
);

-- ── Alumni & Referrals ────────────────────────────────────────

CREATE TABLE IF NOT EXISTS alumni_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    company VARCHAR(150),
    designation VARCHAR(150),
    batch_year INT,
    department VARCHAR(100),
    linkedin VARCHAR(300),
    bio TEXT,
    is_mentor TINYINT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS alumni_referrals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alumni_id INT NOT NULL,
    student_id INT NOT NULL,
    job_title VARCHAR(200),
    company VARCHAR(150),
    message TEXT,
    status ENUM('pending','accepted','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (alumni_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS alumni_mentorship (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alumni_id INT NOT NULL,
    student_id INT NOT NULL,
    message TEXT,
    status ENUM('pending','active','closed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (alumni_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ── Eligibility ───────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS eligibility_criteria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    min_cgpa DECIMAL(4,2) DEFAULT 6.00,
    min_attendance DECIMAL(5,2) DEFAULT 75.00,
    max_backlogs INT DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS student_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    attendance_pct DECIMAL(5,2) DEFAULT 0,
    backlogs INT DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ── Forum ─────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS forum_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(10) DEFAULT '💬',
    description VARCHAR(255),
    sort_order INT DEFAULT 0
);

CREATE TABLE IF NOT EXISTS forum_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    views INT DEFAULT 0,
    is_pinned TINYINT DEFAULT 0,
    is_locked TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES forum_categories(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS forum_replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    is_solution TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES forum_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS forum_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT DEFAULT NULL,
    reply_id INT DEFAULT NULL,
    user_id INT NOT NULL
);

-- ── Gamification ──────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS badges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(10) NOT NULL,
    description VARCHAR(255),
    color VARCHAR(20) DEFAULT '#3f51b5'
);

CREATE TABLE IF NOT EXISTS user_badges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    badge_id INT NOT NULL,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user_badge (user_id, badge_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE
);

-- ── Calendar ──────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS calendar_events (
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
);

-- ── Security ──────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS login_activity (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(300),
    status ENUM('success','failed') DEFAULT 'success',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS two_factor_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    is_enabled TINYINT DEFAULT 0,
    secret_code VARCHAR(10),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ── Seed Data ─────────────────────────────────────────────────

-- Default admin (email: admin@campus.com | password: password)
INSERT IGNORE INTO users (name, email, password, role) VALUES
('Administrator', 'admin@campus.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Default eligibility criteria
INSERT IGNORE INTO eligibility_criteria (id, min_cgpa, min_attendance, max_backlogs) VALUES
(1, 6.00, 75.00, 0);

-- Default forum categories
INSERT IGNORE INTO forum_categories (id, name, icon, description, sort_order) VALUES
(1, 'Interview Experiences', '🎤', 'Share your interview experiences and tips', 1),
(2, 'Technical Help',        '💻', 'Get help with coding and technical questions', 2),
(3, 'General Discussion',    '💬', 'General placement-related discussions', 3);

-- Default badges
INSERT IGNORE INTO badges (id, name, icon, description, color) VALUES
(1,  'First Application', '🚀', 'Applied for your first job',           '#1565c0'),
(2,  'Active Applicant',  '📋', 'Applied for 5+ jobs',                  '#1976d2'),
(3,  'Job Hunter',        '🎯', 'Applied for 10+ jobs',                 '#0288d1'),
(4,  'Test Taker',        '📝', 'Completed your first test',            '#7b1fa2'),
(5,  'Test Pro',          '🏆', 'Scored 80%+ in a test',               '#6a1b9a'),
(6,  'Coder',             '💻', 'Solved your first coding problem',     '#2e7d32'),
(7,  'Code Master',       '⚡', 'Solved 10+ coding problems',          '#1b5e20'),
(8,  'Forum Contributor', '💬', 'Posted in the discussion forum',       '#e65100'),
(9,  'Shortlisted',       '⭐', 'Got shortlisted by a company',        '#f57f17'),
(10, 'Placed',            '🎉', 'Got selected by a company',           '#2e7d32'),
(11, 'Profile Complete',  '✅', 'Completed your profile',              '#00695c'),
(12, 'Resume Uploaded',   '📄', 'Uploaded your resume',                '#4a148c');
