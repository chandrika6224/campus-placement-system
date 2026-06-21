<?php
// AI Resume Analyzer Engine (Rule-Based)
// Analyzes resume text and returns score, suggestions, matched jobs

class ResumeAnalyzer {

    // Skills database by category
    private $skillsDB = [
        'programming' => [
            'php','python','java','javascript','c++','c#','ruby','swift','kotlin','go',
            'typescript','r','matlab','scala','perl','rust','dart','html','css','sql'
        ],
        'frameworks' => [
            'laravel','django','flask','react','angular','vue','node','express','spring',
            'bootstrap','jquery','codeigniter','symfony','asp.net','tensorflow','pytorch',
            'keras','hadoop','spark','flutter','xamarin'
        ],
        'databases' => [
            'mysql','postgresql','mongodb','sqlite','oracle','redis','firebase',
            'cassandra','dynamodb','mariadb','mssql'
        ],
        'tools' => [
            'git','github','docker','kubernetes','jenkins','aws','azure','gcp',
            'linux','unix','jira','postman','figma','photoshop','excel','tableau',
            'power bi','selenium','maven','gradle'
        ],
        'soft_skills' => [
            'leadership','communication','teamwork','problem solving','critical thinking',
            'time management','adaptability','creativity','analytical','presentation',
            'collaboration','management','negotiation','multitasking'
        ],
        'concepts' => [
            'machine learning','deep learning','data science','artificial intelligence',
            'cloud computing','devops','agile','scrum','rest api','microservices',
            'object oriented','data structures','algorithms','networking','cybersecurity',
            'blockchain','iot','nlp','computer vision','big data'
        ]
    ];

    // Job roles and their required skills
    private $jobRoles = [
        'Software Developer' => ['php','python','java','javascript','c++','git','mysql','html','css','object oriented','data structures'],
        'Web Developer' => ['html','css','javascript','php','react','angular','vue','bootstrap','mysql','git','node'],
        'Data Scientist' => ['python','r','machine learning','deep learning','tensorflow','pytorch','sql','data science','statistics','pandas','numpy'],
        'Mobile Developer' => ['java','kotlin','swift','flutter','dart','react','android','ios','firebase','git'],
        'DevOps Engineer' => ['docker','kubernetes','jenkins','aws','azure','linux','git','python','bash','ci/cd','devops'],
        'Database Administrator' => ['mysql','postgresql','oracle','mongodb','sql','redis','database','backup','performance'],
        'UI/UX Designer' => ['figma','photoshop','css','html','bootstrap','user interface','wireframe','prototyping','adobe'],
        'Machine Learning Engineer' => ['python','tensorflow','pytorch','keras','machine learning','deep learning','data science','nlp','computer vision'],
        'Cloud Engineer' => ['aws','azure','gcp','docker','kubernetes','cloud computing','linux','python','terraform'],
        'Cybersecurity Analyst' => ['cybersecurity','networking','linux','python','ethical hacking','firewall','encryption','penetration testing'],
        'Full Stack Developer' => ['html','css','javascript','php','react','node','mysql','git','rest api','bootstrap'],
        'Business Analyst' => ['excel','tableau','power bi','sql','data analysis','communication','presentation','agile','jira'],
    ];

    // Resume sections to check
    private $sections = [
        'education'      => ['education','university','college','degree','bachelor','master','b.tech','m.tech','bsc','msc','b.e','m.e','cgpa','gpa','10th','12th','sslc','hsc'],
        'experience'     => ['experience','internship','worked','employment','company','organization','project','developed','built','implemented','designed'],
        'skills'         => ['skills','technologies','technical','proficient','expertise','knowledge','tools'],
        'projects'       => ['project','developed','built','created','implemented','designed','application','system','website','app'],
        'certifications' => ['certification','certified','certificate','course','training','udemy','coursera','nptel','microsoft','google','aws','oracle'],
        'achievements'   => ['achievement','award','winner','rank','scholarship','honor','distinction','merit','prize','competition'],
        'contact'        => ['email','phone','mobile','linkedin','github','address','contact'],
        'objective'      => ['objective','summary','profile','about','career','goal'],
    ];

    public function analyze($resumeText) {
        $text = strtolower($resumeText);
        $result = [];

        // 1. Check sections
        $result['sections'] = $this->checkSections($text);

        // 2. Find skills
        $result['found_skills'] = $this->findSkills($text);

        // 3. Calculate score
        $result['score'] = $this->calculateScore($text, $result['sections'], $result['found_skills']);

        // 4. Find missing important skills
        $result['missing_skills'] = $this->findMissingSkills($result['found_skills']);

        // 5. Match job roles
        $result['matched_jobs'] = $this->matchJobRoles($result['found_skills']);

        // 6. Generate suggestions
        $result['suggestions'] = $this->generateSuggestions($text, $result['sections'], $result['found_skills'], $result['score']);

        // 7. Strength level
        $result['strength'] = $this->getStrengthLevel($result['score']);

        return $result;
    }

    private function checkSections($text) {
        $found = [];
        foreach ($this->sections as $section => $keywords) {
            foreach ($keywords as $kw) {
                if (strpos($text, $kw) !== false) {
                    $found[$section] = true;
                    break;
                }
            }
            if (!isset($found[$section])) $found[$section] = false;
        }
        return $found;
    }

    private function findSkills($text) {
        $found = [];
        foreach ($this->skillsDB as $category => $skills) {
            $found[$category] = [];
            foreach ($skills as $skill) {
                // Use word boundary matching to avoid false positives
                // e.g. 'r' should not match 'react', 'go' should not match 'good'
                $pattern = '/(?<![a-z0-9])' . preg_quote(strtolower($skill), '/') . '(?![a-z0-9])/i';
                if (preg_match($pattern, $text)) {
                    $found[$category][] = $skill;
                }
            }
        }
        return $found;
    }

    private function calculateScore($text, $sections, $foundSkills) {
        $score = 0;

        // Sections score (40 points)
        $sectionWeights = [
            'contact' => 5, 'objective' => 5, 'education' => 8,
            'skills' => 8, 'projects' => 7, 'experience' => 7,
            'certifications' => 3, 'achievements' => 3
        ];
        foreach ($sectionWeights as $section => $weight) {
            if ($sections[$section]) $score += $weight;
        }

        // Skills score (40 points)
        $totalSkills = 0;
        foreach ($foundSkills as $cat => $skills) $totalSkills += count($skills);
        if ($totalSkills >= 15) $score += 40;
        elseif ($totalSkills >= 10) $score += 30;
        elseif ($totalSkills >= 6)  $score += 20;
        elseif ($totalSkills >= 3)  $score += 10;
        else $score += 5;

        // Length score (10 points)
        $wordCount = str_word_count($text);
        if ($wordCount >= 400) $score += 10;
        elseif ($wordCount >= 250) $score += 7;
        elseif ($wordCount >= 150) $score += 4;

        // Keywords score (10 points)
        $keywords = ['github','linkedin','project','internship','certification','achievement','gpa','cgpa'];
        $kwFound = 0;
        foreach ($keywords as $kw) { if (strpos($text, $kw) !== false) $kwFound++; }
        $score += min(10, $kwFound * 2);

        return min(100, $score);
    }

    private function findMissingSkills($foundSkills) {
        $important = [
            'programming' => ['python','java','javascript','php','c++'],
            'tools'       => ['git','github','linux'],
            'databases'   => ['mysql','mongodb'],
            'concepts'    => ['data structures','algorithms','object oriented']
        ];
        $missing = [];
        foreach ($important as $cat => $skills) {
            foreach ($skills as $skill) {
                $found = false;
                if (isset($foundSkills[$cat])) {
                    foreach ($foundSkills[$cat] as $fs) {
                        if (strtolower($fs) === strtolower($skill)) { $found = true; break; }
                    }
                }
                if (!$found) $missing[] = $skill;
            }
        }
        return $missing;
    }

    private function matchJobRoles($foundSkills) {
        $allFound = [];
        foreach ($foundSkills as $skills) {
            foreach ($skills as $s) $allFound[] = strtolower($s);
        }

        $matches = [];
        foreach ($this->jobRoles as $role => $required) {
            $matched = 0;
            foreach ($required as $skill) {
                if (in_array(strtolower($skill), $allFound)) $matched++;
            }
            $pct = round(($matched / count($required)) * 100);
            if ($pct >= 20) {
                $matches[$role] = $pct;
            }
        }
        arsort($matches);
        return array_slice($matches, 0, 5, true);
    }

    private function generateSuggestions($text, $sections, $foundSkills, $score) {
        $suggestions = [];

        if (!$sections['contact'])
            $suggestions[] = ['type'=>'error','msg'=>'Add your contact information (email, phone, LinkedIn, GitHub).'];
        if (!$sections['objective'])
            $suggestions[] = ['type'=>'warning','msg'=>'Add a career objective or professional summary at the top.'];
        if (!$sections['education'])
            $suggestions[] = ['type'=>'error','msg'=>'Education section is missing. Add your degree, college, and CGPA.'];
        if (!$sections['skills'])
            $suggestions[] = ['type'=>'error','msg'=>'Add a dedicated Skills section with your technical skills.'];
        if (!$sections['projects'])
            $suggestions[] = ['type'=>'warning','msg'=>'Add at least 2-3 projects with descriptions and technologies used.'];
        if (!$sections['experience'])
            $suggestions[] = ['type'=>'info','msg'=>'Add internship or work experience. Even personal projects count.'];
        if (!$sections['certifications'])
            $suggestions[] = ['type'=>'info','msg'=>'Add certifications from Coursera, Udemy, NPTEL, or Google to boost your profile.'];
        if (!$sections['achievements'])
            $suggestions[] = ['type'=>'info','msg'=>'Mention achievements, awards, or hackathon participations.'];

        $totalSkills = 0;
        foreach ($foundSkills as $s) $totalSkills += count($s);
        if ($totalSkills < 5)
            $suggestions[] = ['type'=>'error','msg'=>'Very few skills detected. Add more technical skills relevant to your target role.'];
        elseif ($totalSkills < 10)
            $suggestions[] = ['type'=>'warning','msg'=>'Add more skills. Aim for at least 10-15 relevant technical skills.'];

        if (strpos($text, 'github') === false)
            $suggestions[] = ['type'=>'warning','msg'=>'Add your GitHub profile link to showcase your projects.'];
        if (strpos($text, 'linkedin') === false)
            $suggestions[] = ['type'=>'warning','msg'=>'Add your LinkedIn profile URL for professional networking.'];

        $wordCount = str_word_count($text);
        if ($wordCount < 200)
            $suggestions[] = ['type'=>'error','msg'=>'Resume is too short. Add more details about your projects, skills, and experience.'];
        elseif ($wordCount > 1000)
            $suggestions[] = ['type'=>'info','msg'=>'Resume might be too long. Keep it concise, ideally 1-2 pages.'];

        if (empty($foundSkills['tools']) || !in_array('git', array_map('strtolower', $foundSkills['tools'])))
            $suggestions[] = ['type'=>'warning','msg'=>'Learn and add Git/GitHub — it is essential for all tech roles.'];

        if ($score >= 80)
            $suggestions[] = ['type'=>'success','msg'=>'Excellent resume! Keep updating it with new projects and certifications.'];
        elseif ($score >= 60)
            $suggestions[] = ['type'=>'success','msg'=>'Good resume. Focus on adding more projects and certifications to reach 80+.'];

        return $suggestions;
    }

    private function getStrengthLevel($score) {
        if ($score >= 80) return ['label'=>'Excellent','color'=>'#2e7d32','bg'=>'#e8f5e9'];
        if ($score >= 60) return ['label'=>'Good','color'=>'#1565c0','bg'=>'#e3f2fd'];
        if ($score >= 40) return ['label'=>'Average','color'=>'#e65100','bg'=>'#fff8e1'];
        return ['label'=>'Needs Improvement','color'=>'#c62828','bg'=>'#ffebee'];
    }

    // Extract text from uploaded file
    public function extractText($filePath) {
        $ext  = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $text = '';

        if ($ext === 'txt') {
            $text = file_get_contents($filePath);

        } elseif ($ext === 'pdf') {
            $content = file_get_contents($filePath);

            // Method 1: decompress zlib streams
            preg_match_all('/stream(.*?)endstream/s', $content, $streams);
            foreach ($streams[1] as $stream) {
                $stream  = ltrim($stream, "\r\n");
                $decoded = @gzuncompress($stream);
                if ($decoded === false) $decoded = @gzinflate($stream);
                if ($decoded) {
                    // extract text from BT...ET blocks
                    preg_match_all('/BT(.*?)ET/s', $decoded, $bt);
                    foreach ($bt[1] as $block) {
                        // Tj and TJ operators
                        preg_match_all('/\(([^)]+)\)\s*Tj/', $block, $m1);
                        foreach ($m1[1] as $t) $text .= ' ' . $t;
                        preg_match_all('/\[([^\]]+)\]\s*TJ/', $block, $m2);
                        foreach ($m2[1] as $t) {
                            preg_match_all('/\(([^)]+)\)/', $t, $m3);
                            foreach ($m3[1] as $s) $text .= ' ' . $s;
                        }
                    }
                    // also grab any plain readable text
                    $text .= ' ' . preg_replace('/[^\x20-\x7E\n]/', ' ', $decoded);
                }
            }

            // Method 2: direct BT...ET from raw content
            if (strlen(trim($text)) < 100) {
                preg_match_all('/BT(.*?)ET/s', $content, $bt);
                foreach ($bt[1] as $block) {
                    preg_match_all('/\(([^)]+)\)\s*Tj/', $block, $m1);
                    foreach ($m1[1] as $t) $text .= ' ' . $t;
                }
            }

            // Method 3: fallback — strip binary, keep printable
            if (strlen(trim($text)) < 100) {
                $text = preg_replace('/[^\x20-\x7E\n\r]/', ' ', $content);
                // Remove PDF operator noise
                $text = preg_replace('/\b(obj|endobj|stream|endstream|xref|trailer|startxref|BT|ET|Tf|Td|Tm|Tj|TJ|cm|Do|q|Q|re|f|S|gs|cs|scn|w|J|j|M|d|ri|i|rg|RG|k|K|BMC|EMC|BDC|BS|BE)\b/', ' ', $text);
            }

        } elseif ($ext === 'docx') {
            $zip = new ZipArchive();
            if ($zip->open($filePath) === true) {
                $xml = $zip->getFromName('word/document.xml');
                $zip->close();
                if ($xml) {
                    $text = strip_tags(str_replace(
                        ['</w:p>','</w:r>','<w:br/>','<w:tab/>'],
                        ["\n", ' ', "\n", "\t"],
                        $xml
                    ));
                }
            }

        } elseif ($ext === 'doc') {
            $content = file_get_contents($filePath);
            $text    = preg_replace('/[^\x20-\x7E\n\r]/', ' ', $content);
        }

        // Final cleanup
        $text = preg_replace('/\s{3,}/', ' ', $text);
        $text = html_entity_decode($text);
        return trim($text);
    }
}
?>
