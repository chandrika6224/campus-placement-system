-- Jobs & Internships seed (companies already exist)

INSERT INTO jobs (company_id, title, description, requirements, salary_range, location, job_type, min_cgpa, deadline, status, allowed_streams)
SELECT c.id,
'Software Engineer',
'Design, develop and maintain scalable software applications using modern tech stacks. Work in agile teams delivering high-quality solutions for millions of users. Participate in code reviews, architecture discussions and contribute to TCS digital transformation projects.',
'B.E/B.Tech in CS/IT/ECE. Strong fundamentals in Java or Python. Good knowledge of Data Structures & Algorithms. Understanding of databases (SQL/NoSQL). CGPA 6.5+. Excellent communication skills.',
'3.5 - 7 LPA', 'Chennai, Hyderabad, Pune, Bangalore', 'Full-time', 6.50, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'open',
'Computer Science and Engineering,Information Technology,Electronics and Communication Engineering'
FROM companies c JOIN users u ON c.user_id=u.id WHERE u.email='hr@tcs.com';

INSERT INTO jobs (company_id, title, description, requirements, salary_range, location, job_type, min_cgpa, deadline, status, allowed_streams)
SELECT c.id,
'System Engineer',
'Maintain and support enterprise IT systems across client environments. Troubleshoot technical issues, manage incidents and ensure SLA compliance. Work with cross-functional teams to deliver infrastructure solutions.',
'B.E/B.Tech any branch. Basic programming knowledge in any language. Analytical and problem-solving skills. CGPA 6.0+. Willingness to work in rotational shifts.',
'3.0 - 5 LPA', 'Pan India', 'Full-time', 6.00, DATE_ADD(CURDATE(), INTERVAL 25 DAY), 'open',
'Computer Science and Engineering,Information Technology,Electrical Engineering,Electronics and Communication Engineering,Mechanical Engineering'
FROM companies c JOIN users u ON c.user_id=u.id WHERE u.email='hr@tcs.com';

INSERT INTO jobs (company_id, title, description, requirements, salary_range, location, job_type, min_cgpa, deadline, status, allowed_streams)
SELECT c.id,
'Associate Software Engineer',
'Build and maintain enterprise-grade web applications using Java Spring Boot. Collaborate with senior developers on architecture decisions. Write unit tests, participate in code reviews, and contribute to Agile sprints delivering features for global clients.',
'B.E/B.Tech CS/IT/ECE. Proficiency in Java and SQL. Knowledge of Spring MVC or similar frameworks. Good understanding of OOP concepts. CGPA 6.5+.',
'3.6 - 6.5 LPA', 'Bangalore, Pune, Hyderabad, Chennai', 'Full-time', 6.50, DATE_ADD(CURDATE(), INTERVAL 28 DAY), 'open',
'Computer Science and Engineering,Information Technology,Computer Science in AIML'
FROM companies c JOIN users u ON c.user_id=u.id WHERE u.email='hr@infosys.com';

INSERT INTO jobs (company_id, title, description, requirements, salary_range, location, job_type, min_cgpa, deadline, status, allowed_streams)
SELECT c.id,
'Operations Analyst',
'Analyze complex business processes and provide data-driven insights to clients. Build dashboards, automate reports and work with stakeholders to drive operational efficiency.',
'B.E/B.Tech or MBA. Strong analytical mindset. Proficiency in Excel and SQL. Exposure to Power BI is a plus. CGPA 6.0+.',
'4.0 - 7 LPA', 'Bangalore, Mysore', 'Full-time', 6.00, DATE_ADD(CURDATE(), INTERVAL 20 DAY), 'open',
'Computer Science and Engineering,Information Technology,Computer Science in Data Science'
FROM companies c JOIN users u ON c.user_id=u.id WHERE u.email='hr@infosys.com';

INSERT INTO jobs (company_id, title, description, requirements, salary_range, location, job_type, min_cgpa, deadline, status, allowed_streams)
SELECT c.id,
'Project Engineer',
'Contribute to software development projects across Java, Python and cloud technology stacks. Collaborate with global delivery teams, follow SDLC processes and participate in client presentations.',
'B.E/B.Tech any branch. Programming skills in any language. Good communication and teamwork. CGPA 6.0+.',
'3.5 - 5.5 LPA', 'Bangalore, Hyderabad, Chennai, Pune', 'Full-time', 6.00, DATE_ADD(CURDATE(), INTERVAL 35 DAY), 'open',
'Computer Science and Engineering,Information Technology,Electronics and Communication Engineering,Electrical Engineering,Mechanical Engineering'
FROM companies c JOIN users u ON c.user_id=u.id WHERE u.email='hr@wipro.com';

INSERT INTO jobs (company_id, title, description, requirements, salary_range, location, job_type, min_cgpa, deadline, status, allowed_streams)
SELECT c.id,
'SDE-1 (Software Development Engineer)',
'Write high-quality, scalable code for Amazon products used by hundreds of millions worldwide. Own complete features end-to-end from design to deployment. Participate in design reviews. Work on distributed systems at massive scale.',
'B.E/B.Tech CS/IT. Exceptional DSA skills. Proficiency in C++/Java/Python. Strong system design understanding. CGPA 7.5+. Competitive programming experience preferred.',
'18 - 32 LPA', 'Bangalore, Hyderabad', 'Full-time', 7.50, DATE_ADD(CURDATE(), INTERVAL 45 DAY), 'open',
'Computer Science and Engineering,Information Technology,Computer Science in AIML'
FROM companies c JOIN users u ON c.user_id=u.id WHERE u.email='hr@amazon.com';

INSERT INTO jobs (company_id, title, description, requirements, salary_range, location, job_type, min_cgpa, deadline, status, allowed_streams)
SELECT c.id,
'Software Engineer (STEP)',
'Build Google products from the ground up. Solve large-scale engineering challenges impacting billions of users. Work with world-class engineers on Search, Ads, Maps, Cloud and AI products.',
'B.E/B.Tech CS/IT. Exceptional problem-solving ability. Proficiency in C++/Java/Python/Go. Strong mathematics foundation. CGPA 8.0+.',
'25 - 45 LPA', 'Bangalore', 'Full-time', 8.00, DATE_ADD(CURDATE(), INTERVAL 60 DAY), 'open',
'Computer Science and Engineering,Information Technology,Computer Science in AIML,IMsc Maths and Computing'
FROM companies c JOIN users u ON c.user_id=u.id WHERE u.email='hr@google.com';

INSERT INTO jobs (company_id, title, description, requirements, salary_range, location, job_type, min_cgpa, deadline, status, allowed_streams)
SELECT c.id,
'Software Engineer II',
'Design and implement highly scalable services powering Microsoft Azure, Office 365 and Teams. Collaborate across global teams to ship features used by enterprise customers worldwide.',
'B.E/B.Tech CS/IT. Strong coding skills in C#/Java/Python. Understanding of cloud computing concepts. CGPA 7.5+.',
'20 - 38 LPA', 'Hyderabad, Bangalore', 'Full-time', 7.50, DATE_ADD(CURDATE(), INTERVAL 40 DAY), 'open',
'Computer Science and Engineering,Information Technology,Computer Science in AIML'
FROM companies c JOIN users u ON c.user_id=u.id WHERE u.email='hr@microsoft.com';

INSERT INTO jobs (company_id, title, description, requirements, salary_range, location, job_type, min_cgpa, deadline, status, allowed_streams)
SELECT c.id,
'Application Developer Associate',
'Develop and maintain client-facing applications across web and mobile platforms. Work on diverse projects spanning BFSI, retail and healthcare industries.',
'B.E/B.Tech CS/IT/ECE. Knowledge of web technologies. Good communication skills. CGPA 6.5+.',
'4.5 - 8 LPA', 'Bangalore, Mumbai, Hyderabad, Chennai', 'Full-time', 6.50, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'open',
'Computer Science and Engineering,Information Technology,Electronics and Communication Engineering,Computer Science and Design'
FROM companies c JOIN users u ON c.user_id=u.id WHERE u.email='hr@accenture.com';

INSERT INTO jobs (company_id, title, description, requirements, salary_range, location, job_type, min_cgpa, deadline, status, allowed_streams)
SELECT c.id,
'Programmer Analyst',
'Develop software solutions for global clients across banking, insurance and manufacturing domains. Participate in full SDLC from requirements to deployment using Agile methodologies.',
'B.E/B.Tech CS/IT. Strong programming skills. Knowledge of DBMS and SQL. Logical reasoning and analytical skills. CGPA 6.5+.',
'4.0 - 7 LPA', 'Chennai, Bangalore, Pune, Hyderabad', 'Full-time', 6.50, DATE_ADD(CURDATE(), INTERVAL 22 DAY), 'open',
'Computer Science and Engineering,Information Technology,Computer Science in AIML,Computer Science in Data Science'
FROM companies c JOIN users u ON c.user_id=u.id WHERE u.email='hr@cognizant.com';

INSERT INTO jobs (company_id, title, description, requirements, salary_range, location, job_type, min_cgpa, deadline, status, allowed_streams)
SELECT c.id,
'Graduate Engineer Trainee',
'Join HCL as a GET and undergo structured training in software engineering, cloud and digital technologies. Work on live client projects after training. Opportunity to specialize in AI/ML, DevOps or cybersecurity.',
'B.E/B.Tech any branch. Strong logical and analytical ability. Good communication skills. Willingness to relocate. CGPA 6.0+.',
'3.5 - 6 LPA', 'Noida, Chennai, Bangalore, Hyderabad', 'Full-time', 6.00, DATE_ADD(CURDATE(), INTERVAL 38 DAY), 'open',
'Computer Science and Engineering,Information Technology,Electronics and Communication Engineering,Electrical Engineering,Electrical and Electronics Engineering'
FROM companies c JOIN users u ON c.user_id=u.id WHERE u.email='hr@hcl.com';

INSERT INTO jobs (company_id, title, description, requirements, salary_range, location, job_type, min_cgpa, deadline, status, allowed_streams)
SELECT c.id,
'Software Engineer - Digital',
'Work on digital transformation projects for global telecom and media clients. Build cloud-native applications, REST APIs and microservices. Collaborate with global teams across USA, Europe and APAC.',
'B.E/B.Tech CS/IT/ECE. Programming skills in Java or Python. Basic knowledge of REST APIs and cloud. CGPA 6.5+.',
'4.0 - 7.5 LPA', 'Pune, Hyderabad, Chennai', 'Full-time', 6.50, DATE_ADD(CURDATE(), INTERVAL 32 DAY), 'open',
'Computer Science and Engineering,Information Technology,Electronics and Communication Engineering'
FROM companies c JOIN users u ON c.user_id=u.id WHERE u.email='hr@techmahindra.com';

-- ============================================================
-- Internships
-- ============================================================
INSERT INTO internships (company_id, title, description, requirements, stipend, location, duration, min_cgpa, deadline, status, allowed_streams)
SELECT c.id,
'Software Development Intern',
'Work alongside experienced engineers building real features for TCS internal tools and client solutions. Gain hands-on experience with Java, Spring Boot and microservices. Complete a capstone project presented to senior leadership.',
'B.E/B.Tech 3rd or pre-final year CS/IT. Basic Java or Python programming. Eagerness to learn. CGPA 6.5+.',
'15,000 - 20,000/month', 'Bangalore, Chennai', '2 Months', 6.50, DATE_ADD(CURDATE(), INTERVAL 20 DAY), 'open',
'Computer Science and Engineering,Information Technology'
FROM companies c JOIN users u ON c.user_id=u.id WHERE u.email='hr@tcs.com';

INSERT INTO internships (company_id, title, description, requirements, stipend, location, duration, min_cgpa, deadline, status, allowed_streams)
SELECT c.id,
'Data Science Intern',
'Analyze large datasets, build predictive models and create business intelligence dashboards. Work with Infosys data science team using Python, pandas, scikit-learn and Tableau on real client engagements.',
'B.E/B.Tech 3rd/4th year CS/IT/DS. Strong Python and SQL skills. Exposure to ML concepts. CGPA 7.0+.',
'20,000 - 25,000/month', 'Bangalore, Pune', '3 Months', 7.00, DATE_ADD(CURDATE(), INTERVAL 18 DAY), 'open',
'Computer Science and Engineering,Computer Science in Data Science,Computer Science in AIML,Information Technology'
FROM companies c JOIN users u ON c.user_id=u.id WHERE u.email='hr@infosys.com';

INSERT INTO internships (company_id, title, description, requirements, stipend, location, duration, min_cgpa, deadline, status, allowed_streams)
SELECT c.id,
'Cloud & DevOps Intern',
'Assist in building and managing cloud infrastructure on AWS and Azure. Work with CI/CD pipelines, Docker, Kubernetes and monitoring tools. Gain exposure to enterprise-scale DevOps practices.',
'B.E/B.Tech any year CS/IT. Basic Linux and scripting knowledge. Interest in cloud technologies. CGPA 6.0+.',
'18,000 - 22,000/month', 'Bangalore, Hyderabad', '2 Months', 6.00, DATE_ADD(CURDATE(), INTERVAL 25 DAY), 'open',
'Computer Science and Engineering,Information Technology,Electronics and Communication Engineering'
FROM companies c JOIN users u ON c.user_id=u.id WHERE u.email='hr@wipro.com';

INSERT INTO internships (company_id, title, description, requirements, stipend, location, duration, min_cgpa, deadline, status, allowed_streams)
SELECT c.id,
'Software Development Engineer Intern',
'Work on real Amazon products, contribute production-quality code, collaborate with senior engineers. Projects span AWS, Amazon.in, Alexa and Logistics. PPO opportunity for outstanding performers.',
'Pre-final year B.E/B.Tech CS/IT. Strong DSA skills. Competitive programming experience preferred. CGPA 7.5+.',
'80,000 - 1,00,000/month', 'Bangalore, Hyderabad', '2 Months', 7.50, DATE_ADD(CURDATE(), INTERVAL 15 DAY), 'open',
'Computer Science and Engineering,Information Technology,Computer Science in AIML'
FROM companies c JOIN users u ON c.user_id=u.id WHERE u.email='hr@amazon.com';

INSERT INTO internships (company_id, title, description, requirements, stipend, location, duration, min_cgpa, deadline, status, allowed_streams)
SELECT c.id,
'STEP Intern (Software Engineering)',
'Join Google STEP and work on products impacting billions. Assigned to a real team contributing to production code. Paired with a dedicated mentor. PPO opportunity. Work on Search, YouTube, Maps or Cloud.',
'Pre-final year B.E/B.Tech CS/IT. Strong DSA and coding skills. CGPA 8.0+.',
'1,00,000 - 1,50,000/month', 'Bangalore', '3 Months', 8.00, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'open',
'Computer Science and Engineering,Information Technology,Computer Science in AIML,IMsc Maths and Computing'
FROM companies c JOIN users u ON c.user_id=u.id WHERE u.email='hr@google.com';

INSERT INTO internships (company_id, title, description, requirements, stipend, location, duration, min_cgpa, deadline, status, allowed_streams)
SELECT c.id,
'Explore Intern (Engineering)',
'Microsoft Explore is a 12-week internship for students passionate about technology. Rotate between program management, software engineering and product design. Work on Azure, Office or Xbox teams.',
'2nd or 3rd year B.E/B.Tech CS/IT. Passion for technology products. Good programming foundation. CGPA 7.5+.',
'90,000 - 1,20,000/month', 'Hyderabad', '3 Months', 7.50, DATE_ADD(CURDATE(), INTERVAL 20 DAY), 'open',
'Computer Science and Engineering,Information Technology,Computer Science and Design'
FROM companies c JOIN users u ON c.user_id=u.id WHERE u.email='hr@microsoft.com';

INSERT INTO internships (company_id, title, description, requirements, stipend, location, duration, min_cgpa, deadline, status, allowed_streams)
SELECT c.id,
'Technology Consulting Intern',
'Support project teams on client engagements across BFSI, retail and public sector. Assist in requirements gathering, process mapping, solution design and client presentations.',
'B.E/B.Tech 3rd or final year any branch. Good communication and analytical skills. CGPA 6.5+.',
'20,000 - 30,000/month', 'Bangalore, Mumbai, Hyderabad', '2 Months', 6.50, DATE_ADD(CURDATE(), INTERVAL 22 DAY), 'open',
'Computer Science and Engineering,Information Technology,Electronics and Communication Engineering,Electrical Engineering'
FROM companies c JOIN users u ON c.user_id=u.id WHERE u.email='hr@accenture.com';

INSERT INTO internships (company_id, title, description, requirements, stipend, location, duration, min_cgpa, deadline, status, allowed_streams)
SELECT c.id,
'GenC Intern (Next Gen Cognizant)',
'Cognizant GenC Intern program provides exposure to AI, cloud, IoT and blockchain. Work on real projects, attend training sessions and build your professional network.',
'B.E/B.Tech any branch final year. Strong programming aptitude. Good English communication. CGPA 6.5+.',
'15,000 - 20,000/month', 'Chennai, Pune, Bangalore', '6 Months', 6.50, DATE_ADD(CURDATE(), INTERVAL 28 DAY), 'open',
'Computer Science and Engineering,Information Technology,Electronics and Communication Engineering,Electrical and Electronics Engineering'
FROM companies c JOIN users u ON c.user_id=u.id WHERE u.email='hr@cognizant.com';

INSERT INTO internships (company_id, title, description, requirements, stipend, location, duration, min_cgpa, deadline, status, allowed_streams)
SELECT c.id,
'Machine Learning Intern',
'Work with HCL AI Labs team on computer vision, NLP and predictive analytics. Build ML pipelines, train and deploy models to production APIs. Mentorship from senior AI researchers.',
'B.E/B.Tech 3rd/4th year CS/IT/DS. Python and ML fundamentals required. Exposure to TensorFlow or PyTorch. CGPA 7.0+.',
'25,000 - 35,000/month', 'Noida, Bangalore', '3 Months', 7.00, DATE_ADD(CURDATE(), INTERVAL 18 DAY), 'open',
'Computer Science and Engineering,Computer Science in AIML,Computer Science in Data Science,Information Technology'
FROM companies c JOIN users u ON c.user_id=u.id WHERE u.email='hr@hcl.com';

INSERT INTO internships (company_id, title, description, requirements, stipend, location, duration, min_cgpa, deadline, status, allowed_streams)
SELECT c.id,
'Network & Telecom Intern',
'Assist in designing and testing 5G/LTE network solutions for telecom clients. Work with simulation tools, network protocol analyzers and cloud-based testing platforms.',
'B.E/B.Tech ECE/EEE/Telecom. Knowledge of networking fundamentals. Interest in telecom domain. CGPA 6.5+.',
'15,000 - 20,000/month', 'Pune, Hyderabad', '2 Months', 6.50, DATE_ADD(CURDATE(), INTERVAL 24 DAY), 'open',
'Electronics and Communication Engineering,Electrical and Electronics Engineering,Electrical Engineering'
FROM companies c JOIN users u ON c.user_id=u.id WHERE u.email='hr@techmahindra.com';
