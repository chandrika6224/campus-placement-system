<?php
/**
 * CampusRecruit Chatbot Engine
 * Rule-based NLP with intent detection and dynamic DB responses
 */
class ChatbotEngine {

    private $conn;
    private $role;
    private $uid;

    // Intent patterns: [keywords] => intent_name
    private $intents = [
        // Greetings
        'hello|hi|hey|good morning|good afternoon|good evening|howdy|greetings' => 'greeting',
        'bye|goodbye|see you|exit|quit|thanks bye|thank you bye' => 'farewell',
        'thank|thanks|thank you|thx|ty' => 'thanks',

        // Registration & Login
        'register|sign up|create account|new account|how to join|enroll' => 'registration',
        'login|sign in|log in|password|forgot password|reset password|cant login|cannot login' => 'login',

        // Jobs
        'job|jobs|vacancy|vacancies|opening|openings|position|positions|hiring|recruitment drive' => 'jobs',
        'apply|application|how to apply|submit application|applied' => 'apply',
        'application status|my application|check application|track application' => 'application_status',
        'shortlist|shortlisted|selected|rejected|not selected' => 'shortlist',
        'deadline|last date|closing date|expire' => 'deadline',
        'salary|package|ctc|pay|stipend|compensation' => 'salary',
        'internship|intern|part time|part-time' => 'internship',

        // Profile
        'profile|update profile|edit profile|complete profile|my details' => 'profile',
        'resume|cv|upload resume|resume upload|curriculum vitae' => 'resume',
        'cgpa|gpa|marks|percentage|academic|grade|score' => 'cgpa',
        'skills|add skills|my skills|technical skills' => 'skills',
        'department|branch|stream|course' => 'department',

        // Tests
        'test|tests|aptitude|exam|examination|quiz|mcq|coding test|technical test' => 'tests',
        'test result|my score|test score|marks in test|passed|failed test' => 'test_result',
        'timer|time limit|duration|how long|minutes' => 'test_timer',

        // Interviews
        'interview|interviews|scheduled interview|my interview|video interview|online interview' => 'interview',
        'meeting link|zoom|google meet|jitsi|teams|join meeting' => 'meeting_link',
        'interview tips|prepare interview|how to prepare|interview preparation' => 'interview_tips',

        // Placement
        'placement|placed|placement drive|campus placement|placement process' => 'placement',
        'placement prediction|prediction|probability|chance|likelihood|placement chance' => 'prediction',
        'eligibility|eligible|criteria|requirement|minimum cgpa|backlogs' => 'eligibility',

        // AI Features
        'ai resume|resume analyzer|analyze resume|resume score|resume check' => 'ai_resume',
        'job recommendation|recommended jobs|ai jobs|suggest jobs|best jobs for me' => 'job_recommendation',
        'skill gap|missing skills|skills needed|gap analysis|what skills' => 'skill_gap',

        // Notices
        'notice|notices|announcement|news|update|circular|notification' => 'notices',

        // Company / Recruiter
        'company|companies|recruiter|recruiters|which companies|top companies' => 'companies',
        'post job|add job|create job|new job posting' => 'post_job',

        // Admin
        'admin|administrator|contact admin|report issue|help admin' => 'admin',

        // General Help
        'help|support|assist|guide|how|what|explain|tell me|show me|features|what can you do' => 'help',
        'about|about this|about system|about platform|campus recruit' => 'about',
    ];

    public function __construct($conn, $role = null, $uid = null) {
        $this->conn = $conn;
        $this->role = $role;
        $this->uid  = $uid;
    }

    public function respond($message) {
        $msg   = strtolower(trim($message));
        $intent = $this->detectIntent($msg);
        return $this->generateResponse($intent, $msg);
    }

    private function detectIntent($msg) {
        foreach ($this->intents as $pattern => $intent) {
            $keywords = explode('|', $pattern);
            foreach ($keywords as $kw) {
                if (strpos($msg, trim($kw)) !== false) return $intent;
            }
        }
        return 'unknown';
    }

    private function generateResponse($intent, $msg) {
        $role = $this->role;
        $uid  = $this->uid;
        $conn = $this->conn;

        switch ($intent) {

            case 'greeting':
                $greetings = [
                    "👋 Hello! I'm CampusBot, your placement assistant. How can I help you today?",
                    "Hi there! 😊 I'm here to help with placements, jobs, tests, and more. What do you need?",
                    "Hey! Welcome to CampusRecruit. Ask me anything about placements, jobs, or interviews! 🎓",
                ];
                return $greetings[array_rand($greetings)];

            case 'farewell':
                return "👋 Goodbye! Best of luck with your placements. Feel free to come back anytime! 🎓";

            case 'thanks':
                return "😊 You're welcome! Is there anything else I can help you with?";

            case 'registration':
                return "📝 **How to Register:**\n\n1. Go to the home page\n2. Click **Register** button\n3. Fill in your name, email, password\n4. Select your role: **Student** or **Recruiter**\n5. Click Register and then login!\n\n👉 [Register Now](/placement system/index.php?tab=register)";

            case 'login':
                return "🔐 **Login Help:**\n\n• Go to the login page and enter your email & password\n• Default admin: admin@campus.com / password\n• If you forgot your password, contact the admin\n• Make sure you're using the correct role (Student/Recruiter/Admin)\n\n👉 [Login Page](/placement system/index.php)";

            case 'jobs':
                if ($role === 'student' && $uid) {
    $count = $conn->query("SELECT COUNT(*) as c FROM jobs WHERE status='open'")->fetch_assoc()['c'];
                    return "💼 There are currently **{$count} open jobs** available!\n\n• Browse all jobs in the **Browse Jobs** section\n• Use **AI Job Recommendations** for personalized matches\n• Filter by job type, location, and salary\n\n👉 [Browse Jobs](/placement system/student/jobs.php)";
                }
                if ($role === 'recruiter') {
                    return "💼 **For Recruiters:**\n\n• Go to **Post Job** to create a new job listing\n• View your posted jobs in **My Jobs**\n• Manage applications in **Applications**\n\n👉 [Post a Job](/placement system/recruiter/post_job.php)";
                }
                $count = $conn->query("SELECT COUNT(*) as c FROM jobs WHERE status='open'")->fetch_assoc()['c'];
                return "💼 There are **{$count} open jobs** on the platform. Login as a student to apply!";

            case 'apply':
                if ($role === 'student') {
                    return "📋 **How to Apply for a Job:**\n\n1. Go to **Browse Jobs**\n2. Find a job you like\n3. Click **Apply Now**\n4. Confirm your application\n5. Track status in **My Applications**\n\n💡 Tip: Complete your profile first for better chances!\n\n👉 [Browse Jobs](/placement system/student/jobs.php)";
                }
                return "📋 To apply for jobs, you need to **login as a Student** first. Register and complete your profile!";

            case 'application_status':
                if ($role === 'student' && $uid) {
                    $stApps = $conn->prepare("SELECT COUNT(*) as c FROM applications WHERE student_id=?"); $stApps->bind_param('i',$uid); $stApps->execute();
                    $apps = $stApps->get_result()->fetch_assoc()['c']; $stApps->close();
                    $stSh = $conn->prepare("SELECT COUNT(*) as c FROM applications WHERE student_id=? AND status='shortlisted'"); $stSh->bind_param('i',$uid); $stSh->execute();
                    $shortlisted = $stSh->get_result()->fetch_assoc()['c']; $stSh->close();
                    $stSel = $conn->prepare("SELECT COUNT(*) as c FROM applications WHERE student_id=? AND status='selected'"); $stSel->bind_param('i',$uid); $stSel->execute();
                    $selected = $stSel->get_result()->fetch_assoc()['c']; $stSel->close();
                    return "📊 **Your Application Summary:**\n\n• Total Applied: **{$apps}**\n• Shortlisted: **{$shortlisted}**\n• Selected: **{$selected}**\n\n👉 [View All Applications](/placement system/student/applications.php)";
                }
                return "📋 Login as a student to check your application status in **My Applications**.";

            case 'shortlist':
                return "⭐ **Shortlisting Process:**\n\nRecruiters review applications and update status to:\n• **Applied** → Initial state\n• **Shortlisted** → You're in consideration!\n• **Selected** → Congratulations! 🎉\n• **Rejected** → Keep applying to other jobs\n\nYou'll see updates in **My Applications**.";

            case 'deadline':
                if ($role === 'student') {
                    $jobs = $conn->query("SELECT title, deadline FROM jobs WHERE status='open' AND deadline >= CURDATE() ORDER BY deadline ASC LIMIT 5");
                    $resp = "⏰ **Upcoming Job Deadlines:**\n\n";
                    $found = false;
                    while ($j = $jobs->fetch_assoc()) {
                        $resp .= "• **{$j['title']}** — " . date('d M Y', strtotime($j['deadline'])) . "\n";
                        $found = true;
                    }
                    return $found ? $resp . "\n👉 [View All Jobs](/placement system/student/jobs.php)" : "No upcoming deadlines found. Check the jobs page for details.";
                }
                return "⏰ Job deadlines are shown on each job listing. Login to view them!";

            case 'salary':
                return "💰 **Salary Information:**\n\nSalary ranges are listed on each job posting. They vary by:\n• Company and role\n• Experience level\n• Job type (Full-time/Internship)\n\nCheck individual job listings for specific salary details.";

            case 'internship':
                if ($role === 'student') {
                    $stInt = $conn->prepare("SELECT COUNT(*) as c FROM jobs WHERE status='open' AND job_type='Internship'"); $stInt->execute();
                    $count = $stInt->get_result()->fetch_assoc()['c']; $stInt->close();
                    return "🎓 There are **{$count} internship** opportunities available!\n\n👉 [Browse Internships](/placement system/student/jobs.php)";
                }
                return "🎓 Internship opportunities are listed under job type **Internship**. Login to browse!";

            case 'profile':
                if ($role === 'student') {
                    return "👤 **Update Your Profile:**\n\n1. Click **My Profile** in the navbar\n2. Fill in: Department, CGPA, Skills, Phone\n3. Upload your resume\n4. Click **Save Profile**\n\n💡 A complete profile improves your placement prediction score!\n\n👉 [My Profile](/placement system/student/profile.php)";
                }
                return "👤 Go to **Profile** in the navbar to update your information.";

            case 'resume':
                if ($role === 'student') {
                    return "📄 **Resume Help:**\n\n• Upload your resume in **My Profile** (PDF/DOC)\n• Use the **AI Resume Analyzer** to get a score out of 100\n• Get suggestions to improve your resume\n• See which job roles match your resume\n\n👉 [AI Resume Analyzer](/placement system/student/resume_analyzer/index.php)";
                }
                return "📄 Students can upload and analyze their resumes using the AI Resume Analyzer feature.";

            case 'cgpa':
                return "📊 **CGPA Requirements:**\n\n• Most companies require minimum **6.0 - 7.0 CGPA**\n• Top companies may require **8.0+**\n• Each job listing shows the minimum CGPA required\n• Update your CGPA in **My Profile**\n\n💡 CGPA is one of the key factors in placement prediction!";

            case 'skills':
                if ($role === 'student') {
                    return "💡 **Managing Your Skills:**\n\n1. Go to **My Profile**\n2. Add skills separated by commas (e.g., PHP, Python, MySQL)\n3. Use **Skill Gap Analysis** to see what skills you're missing\n4. Use **AI Resume Analyzer** to detect skills in your resume\n\n👉 [Skill Gap Analysis](/placement system/student/skill_gap/index.php)";
                }
                return "💡 Students can add and manage their skills in their profile section.";

            case 'department':
                return "🏫 **Departments Supported:**\n\nComputer Science, Information Technology, Electronics, Mechanical, Civil, Electrical, MBA, MCA\n\nUpdate your department in **My Profile** for better job recommendations.";

            case 'tests':
                if ($role === 'student') {
                    $stTst = $conn->prepare("SELECT COUNT(*) as c FROM tests WHERE status='active'"); $stTst->execute();
                    $count = $stTst->get_result()->fetch_assoc()['c']; $stTst->close();
                    return "📝 **Aptitude Tests:**\n\n• **{$count} active tests** available\n• Categories: Aptitude, Technical MCQ, Coding\n• Timer-based with auto-evaluation\n• Instant results with detailed review\n\n👉 [Take a Test](/placement system/student/aptitude_test/index.php)";
                }
                if ($role === 'admin') {
                    return "📝 **Managing Tests:**\n\nGo to **Tests** in the admin panel to:\n• Create new tests\n• Add questions manually or use sample questions\n• View student results\n\n👉 [Manage Tests](/placement system/admin/aptitude/index.php)";
                }
                return "📝 Tests are available for students. Login as a student to take aptitude tests!";

            case 'test_result':
                if ($role === 'student' && $uid) {
                    $stTA = $conn->prepare("SELECT COUNT(*) as c FROM test_attempts WHERE student_id=? AND status='completed'"); $stTA->bind_param('i',$uid); $stTA->execute();
                    $attempts = $stTA->get_result()->fetch_assoc()['c']; $stTA->close();
                    $stAvg = $conn->prepare("SELECT AVG(score/total_marks*100) as avg FROM test_attempts WHERE student_id=? AND status='completed' AND total_marks>0"); $stAvg->bind_param('i',$uid); $stAvg->execute();
                    $avg = $stAvg->get_result()->fetch_assoc()['avg']; $stAvg->close();
                    return "📊 **Your Test Summary:**\n\n• Tests Completed: **{$attempts}**\n• Average Score: **" . round($avg ?? 0) . "%**\n\n👉 [View Tests](/placement system/student/aptitude_test/index.php)";
                }
                return "📊 Login as a student to view your test results.";

            case 'test_timer':
                return "⏱️ **Test Timer Info:**\n\n• Each test has a set duration (shown before starting)\n• Timer counts down in real-time\n• Test **auto-submits** when time runs out\n• You can submit early using the Submit button\n• Unanswered questions are marked as skipped";

            case 'interview':
                if ($role === 'student' && $uid) {
                    $stIVUp = $conn->prepare("SELECT COUNT(*) as c FROM interviews WHERE student_id=? AND status='scheduled' AND scheduled_at > NOW()"); $stIVUp->bind_param('i',$uid); $stIVUp->execute();
                    $upcoming = $stIVUp->get_result()->fetch_assoc()['c']; $stIVUp->close();
                    return "🎥 **Your Interviews:**\n\n• Upcoming Interviews: **{$upcoming}**\n• View meeting links and join directly\n• Live countdown timer to interview time\n• Join button activates 30 minutes before\n\n👉 [My Interviews](/placement system/student/interviews/index.php)";
                }
                if ($role === 'recruiter') {
                    return "🎥 **Scheduling Interviews:**\n\n1. Go to **Interviews** in the navbar\n2. Select an applicant to interview\n3. Set date, time, platform (Jitsi/Zoom/Meet)\n4. Add meeting link or auto-generate Jitsi link\n5. Student will see it in their interview page\n\n👉 [Manage Interviews](/placement system/recruiter/interviews/index.php)";
                }
                return "🎥 Interviews are scheduled by recruiters. Students can view and join from their interview page.";

            case 'meeting_link':
                if ($role === 'student') {
                    return "🔗 **Meeting Link:**\n\n• Your meeting link is in **My Interviews**\n• The **Join Interview Now** button activates 30 min before\n• Supported platforms: Jitsi Meet, Google Meet, Zoom, MS Teams\n• Copy the link to share or open in browser\n\n👉 [My Interviews](/placement system/student/interviews/index.php)";
                }
                return "🔗 Meeting links are shared by recruiters when scheduling interviews. Check your interviews page!";

            case 'interview_tips':
                return "💡 **Interview Tips:**\n\n✅ Join 5 minutes early to test audio/video\n✅ Keep your resume ready on screen\n✅ Dress professionally\n✅ Ensure good lighting & quiet environment\n✅ Charge your device fully\n✅ Use a stable internet connection\n✅ Research the company beforehand\n✅ Prepare answers for common questions\n✅ Have questions ready to ask the interviewer";

            case 'placement':
                if ($role === 'student' && $uid) {
                    $stPSel = $conn->prepare("SELECT COUNT(*) as c FROM applications WHERE student_id=? AND status='selected'"); $stPSel->bind_param('i',$uid); $stPSel->execute();
                    $selected = $stPSel->get_result()->fetch_assoc()['c']; $stPSel->close();
                    if ($selected > 0) return "🎉 Congratulations! You have been **selected** in {$selected} placement(s)! Check **My Applications** for details.";
                    return "🎓 **Placement Process:**\n\n1. Complete your profile\n2. Apply to relevant jobs\n3. Get shortlisted by recruiters\n4. Attend interviews\n5. Get selected!\n\n💡 Use **Placement Prediction** to check your chances.\n\n👉 [Placement Prediction](/placement system/student/placement_prediction/index.php)";
                }
                return "🎓 The placement process involves applying to jobs, getting shortlisted, attending interviews, and getting selected. Login to get started!";

            case 'prediction':
                if ($role === 'student') {
                    return "🔮 **Placement Prediction:**\n\nOur AI analyzes 7 factors:\n• CGPA (25 pts)\n• Skills (20 pts)\n• Resume Score (15 pts)\n• Test Performance (15 pts)\n• Applications (15 pts)\n• Interviews (5 pts)\n• Profile Completeness (5 pts)\n\nGet your probability score and grade (A+ to D)!\n\n👉 [View Prediction](/placement system/student/placement_prediction/index.php)";
                }
                if ($role === 'admin') {
                    return "🔮 View placement predictions for all students in the **Predictions** section of the admin panel.\n\n👉 [View Predictions](/placement system/admin/placement_prediction/index.php)";
                }
                return "🔮 Placement prediction is available for students. Login to check your placement probability!";

            case 'eligibility':
                return "✅ **Placement Eligibility:**\n\nCommon criteria set by companies:\n• Minimum CGPA (usually 6.0 - 7.5)\n• No active backlogs\n• Relevant department/branch\n• Skills matching job requirements\n\nEach job listing shows its specific eligibility criteria. Check before applying!";

            case 'ai_resume':
                if ($role === 'student') {
                    return "🤖 **AI Resume Analyzer:**\n\n• Paste your resume text or upload a file\n• Get a score out of **100**\n• See detected skills by category\n• Get job role match percentages\n• Receive improvement suggestions\n• View missing important skills\n\n👉 [Analyze Resume](/placement system/student/resume_analyzer/index.php)";
                }
                return "🤖 The AI Resume Analyzer is available for students to score and improve their resumes.";

            case 'job_recommendation':
                if ($role === 'student') {
                    return "🎯 **AI Job Recommendations:**\n\nOur AI matches jobs based on:\n• Your skills (40 pts)\n• Your department (20 pts)\n• Your CGPA eligibility (15 pts)\n• Job type alignment (25 pts)\n\nFilter by High/Medium/Low match!\n\n👉 [View Recommendations](/placement system/student/job_recommendation/index.php)";
                }
                return "🎯 AI Job Recommendations are personalized for each student based on their skills and profile.";

            case 'skill_gap':
                if ($role === 'student') {
                    return "🧩 **Skill Gap Analysis:**\n\n• Select a target job role (14 roles available)\n• See which required skills you already have ✅\n• See which skills you're missing ❌\n• Get course recommendations for each missing skill\n• Auto-detects your best matching role\n\n👉 [Skill Gap Analysis](/placement system/student/skill_gap/index.php)";
                }
                return "🧩 Skill Gap Analysis helps students identify missing skills for their target job roles.";

            case 'notices':
                if ($uid) {
                    $stNot = $conn->prepare("SELECT title, created_at FROM notices ORDER BY created_at DESC LIMIT 5"); $stNot->execute();
                    $notices = $stNot->get_result(); $stNot->close();
                    $resp = "📢 **Latest Notices:**\n\n";
                    $found = false;
                    while ($n = $notices->fetch_assoc()) {
                        $resp .= "• **{$n['title']}** — " . date('d M Y', strtotime($n['created_at'])) . "\n";
                        $found = true;
                    }
                    $link = $role === 'student' ? '/placement system/student/notices.php' : '/placement system/admin/notices.php';
                    return $found ? $resp . "\n👉 [View All Notices]($link)" : "📢 No notices posted yet. Check back later!";
                }
                return "📢 Notices are posted by the admin. Login to view the latest placement announcements!";

            case 'companies':
                $stCo = $conn->prepare("SELECT COUNT(*) as c FROM companies"); $stCo->execute();
                $count = $stCo->get_result()->fetch_assoc()['c']; $stCo->close();
                return "🏢 There are **{$count} companies** registered on the platform.\n\nCompanies post jobs and recruit students through the placement system. Login to see which companies are hiring!";

            case 'post_job':
                if ($role === 'recruiter') {
                    return "💼 **Post a New Job:**\n\n1. Go to **Post Job** in the navbar\n2. Fill in job title, description, requirements\n3. Set salary range, location, job type\n4. Set minimum CGPA requirement\n5. Set application deadline\n6. Click **Post Job**\n\n👉 [Post Job](/placement system/recruiter/post_job.php)";
                }
                return "💼 Only recruiters can post jobs. Register as a recruiter to get started!";

            case 'admin':
                return "🛡️ **Admin Contact:**\n\nFor system issues or queries, contact the placement cell admin:\n• Email: admin@campus.com\n• The admin manages all users, jobs, and placement activities\n• Admin can be reached through the college placement office";

            case 'about':
                return "🎓 **About CampusRecruit:**\n\nA comprehensive Campus Placement Management System with:\n• 🤖 AI Resume Analyzer\n• 🎯 AI Job Recommendations\n• 📝 Online Aptitude Tests\n• 🎥 Video Interview Integration\n• 🔮 Placement Prediction (ML)\n• 🧩 Skill Gap Analysis\n• 💬 Chatbot (that's me!)\n• 📊 Performance Dashboards\n• And much more!\n\nBuilt to connect students with their dream careers. 🚀";

            case 'help':
                $roleHelp = [
                    'student'   => "👨🎓 **Student Help Menu:**\n\n• 💼 Jobs & Applications\n• 📄 Resume & Profile\n• 📝 Aptitude Tests\n• 🎥 Interviews\n• 🔮 Placement Prediction\n• 🧩 Skill Gap Analysis\n• 🤖 AI Features\n• 📢 Notices\n\nJust ask me anything! E.g. *\"How do I apply for a job?\"*",
                    'recruiter' => "🏢 **Recruiter Help Menu:**\n\n• 💼 Post & manage jobs\n• 📋 View applications\n• 🎥 Schedule interviews\n• 👥 Manage candidates\n\nJust ask me anything! E.g. *\"How do I schedule an interview?\"*",
                    'admin'     => "🛡️ **Admin Help Menu:**\n\n• 👥 Manage students & recruiters\n• 💼 Monitor jobs & applications\n• 📝 Manage tests\n• 🎥 View interviews\n• 🔮 Placement predictions\n• 🧩 Skill gap reports\n• 📊 Reports\n\nJust ask me anything!",
                ];
                return $roleHelp[$role] ?? "💬 **I can help you with:**\n\n• Registration & Login\n• Jobs & Applications\n• Tests & Interviews\n• Placement Process\n• AI Features\n\nJust type your question!";

            default:
                $suggestions = [
                    "🤔 I'm not sure about that. Try asking about:\n• **jobs** — Browse open positions\n• **apply** — How to apply\n• **tests** — Aptitude tests\n• **interview** — Interview schedule\n• **prediction** — Placement chances\n• **help** — Full help menu",
                    "💭 I didn't quite understand that. You can ask me about jobs, applications, tests, interviews, or type **help** for a full menu!",
                    "🤖 Hmm, I'm still learning! Try rephrasing or type **help** to see what I can assist with.",
                ];
                return $suggestions[array_rand($suggestions)];
        }
    }
}
