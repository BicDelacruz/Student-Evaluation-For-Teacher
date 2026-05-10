<?php
function icon_svg($name)
{
    $icons = [
        "dashboard" => '<svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>',
        "students" => '<svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
        "faculty" => '<svg viewBox="0 0 24 24"><path d="M18 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9.5" cy="7" r="4"></circle><path d="M20 8v6"></path><path d="M23 11h-6"></path></svg>',
        "book" => '<svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"></path></svg>',
        "assignment" => '<svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><path d="M20 8v6"></path><path d="M23 11h-6"></path></svg>',
        "settings" => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1v.17a2 2 0 1 1-4 0V21a1.7 1.7 0 0 0-.4-1 1.7 1.7 0 0 0-1-.6 1.7 1.7 0 0 0-1.88.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.6-1 1.7 1.7 0 0 0-1-.4H2.83a2 2 0 1 1 0-4H3a1.7 1.7 0 0 0 1-.4 1.7 1.7 0 0 0 .6-1 1.7 1.7 0 0 0-.34-1.88l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.6 1.7 1.7 0 0 0 .4-1V2.83a2 2 0 1 1 4 0V3a1.7 1.7 0 0 0 .4 1 1.7 1.7 0 0 0 1 .6 1.7 1.7 0 0 0 1.88-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.4 9c.2.3.4.7.6 1h.17a2 2 0 1 1 0 4H20a1.7 1.7 0 0 0-.6 1z"></path></svg>',
        "clipboard" => '<svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"></path><rect x="9" y="3" width="6" height="4" rx="1"></rect><path d="M9 12h6"></path><path d="M9 16h6"></path></svg>',
        "reports" => '<svg viewBox="0 0 24 24"><path d="M3 3v18h18"></path><path d="M7 16V9"></path><path d="M12 16V5"></path><path d="M17 16v-3"></path></svg>',
        "announcement" => '<svg viewBox="0 0 24 24"><path d="M3 11v2a2 2 0 0 0 2 2h2l5 4V5L7 9H5a2 2 0 0 0-2 2z"></path><path d="M16 9a5 5 0 0 1 0 6"></path></svg>',
        "moon" => '<svg viewBox="0 0 24 24"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"></path></svg>',
        "logout" => '<svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><path d="M16 17l5-5-5-5"></path><path d="M21 12H9"></path></svg>',
        "search" => '<svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"></circle><path d="M21 21l-4.35-4.35"></path></svg>',
        "filter" => '<svg viewBox="0 0 24 24"><path d="M3 4h18l-7 8v6l-4 2v-8z"></path></svg>',
        "eye" => '<svg viewBox="0 0 24 24"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"></path><circle cx="12" cy="12" r="3"></circle></svg>',
        "trash" => '<svg viewBox="0 0 24 24"><path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="M19 6l-1 14H6L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path></svg>',
        "check-circle" => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><path d="M9 12l2 2 4-4"></path></svg>',
        "clock" => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg>',
        "bar-chart" => '<svg viewBox="0 0 24 24"><path d="M3 3v18h18"></path><path d="M7 16V9"></path><path d="M12 16V5"></path><path d="M17 16v-3"></path></svg>',
        "trend" => '<svg viewBox="0 0 24 24"><path d="M3 17l6-6 4 4 8-8"></path><path d="M14 7h7v7"></path></svg>',
        "close" => '<svg viewBox="0 0 24 24"><path d="M18 6L6 18"></path><path d="M6 6l12 12"></path></svg>'
    ];

    return $icons[$name] ?? "";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submission Monitoring</title>
    <link rel="stylesheet" href="submission-monitoring.css?v=<?php echo time(); ?>">
</head>
<body class="submission-monitoring-page">
    <aside class="sidebar">
        <div class="brand">
            <img src="../img/eastgate_college_logo.png" alt="Eastgate College Logo">
            <h1>Student Evaluation for Teacher</h1>
            <p>System Administrator</p>
        </div>

        <nav class="nav-menu">
            <a class="nav-link" href="admin_dashboard.php"><?php echo icon_svg("dashboard"); ?> Dashboard</a>
            <a class="nav-link" href="student_management.php"><?php echo icon_svg("students"); ?> Student Management</a>
            <a class="nav-link" href="faculty_management.php"><?php echo icon_svg("faculty"); ?> Faculty Management</a>
            <a class="nav-link" href="academic_structure.php"><?php echo icon_svg("book"); ?> Academic Structure</a>
            <a class="nav-link" href="assignment_management.php"><?php echo icon_svg("assignment"); ?> Assignment Management</a>
            <a class="nav-link" href="evaluation_setup.php"><?php echo icon_svg("settings"); ?> Evaluation Setup</a>
            <a class="nav-link active" href="submission_monitoring.php"><?php echo icon_svg("clipboard"); ?> Submission Monitoring</a>
            <a class="nav-link" href="#"><?php echo icon_svg("reports"); ?> Reports</a>
            <a class="nav-link" href="#"><?php echo icon_svg("announcement"); ?> Announcements</a>
            <a class="nav-link" href="#"><?php echo icon_svg("settings"); ?> Settings</a>
            <a class="nav-link" href="#"><?php echo icon_svg("moon"); ?> Dark Mode</a>
        </nav>

        <div class="sidebar-bottom">
            <a class="logout-link" href="../login/login_page.php"><?php echo icon_svg("logout"); ?> Logout</a>
        </div>
    </aside>

    <main class="main-content">
        <div class="content-wrap">
            <div class="page-title-row">
                <div>
                    <h1>Submission Monitoring</h1>
                    <p>Track completed and pending evaluations</p>
                </div>
            </div>

            <section class="summary-grid">
                <article class="summary-card completed-card">
                    <div>
                        <span>Completed</span>
                        <strong id="completedCount">0</strong>
                    </div>
                    <div class="summary-icon"><?php echo icon_svg("check-circle"); ?></div>
                </article>

                <article class="summary-card pending-card">
                    <div>
                        <span>Pending</span>
                        <strong id="pendingCount">0</strong>
                    </div>
                    <div class="summary-icon"><?php echo icon_svg("clock"); ?></div>
                </article>

                <article class="summary-card response-card">
                    <div>
                        <span>Total Responses</span>
                        <strong id="totalResponsesCount">0</strong>
                    </div>
                    <div class="summary-icon"><?php echo icon_svg("bar-chart"); ?></div>
                </article>

                <article class="summary-card rate-card">
                    <div>
                        <span>Participation Rate</span>
                        <strong id="participationRate">0%</strong>
                    </div>
                    <div class="summary-icon"><?php echo icon_svg("trend"); ?></div>
                </article>
            </section>

            <section class="status-tabs">
                <button class="tab-button active all-tab" type="button" data-tab="All">All <span id="allTabCount">(0)</span></button>
                <button class="tab-button completed-tab" type="button" data-tab="Completed">Completed <span id="completedTabCount">(0)</span></button>
                <button class="tab-button pending-tab" type="button" data-tab="Pending">Pending <span id="pendingTabCount">(0)</span></button>
            </section>

            <section class="filter-panel">
                <div class="filter-title">
                    <?php echo icon_svg("filter"); ?>
                    <h2>Search & Filters</h2>
                </div>

                <div class="search-row">
                    <label for="searchInput">Search</label>
                    <div class="search-box">
                        <?php echo icon_svg("search"); ?>
                        <input type="text" id="searchInput" placeholder="Search by student name, ID, or course...">
                    </div>
                </div>

                <div class="filter-grid">
                    <div>
                        <label for="departmentFilter">Department</label>
                        <select id="departmentFilter"></select>
                    </div>

                    <div>
                        <label for="courseFilter">Course</label>
                        <select id="courseFilter"></select>
                    </div>

                    <div>
                        <label for="yearFilter">Year Level</label>
                        <select id="yearFilter"></select>
                    </div>

                    <div>
                        <label for="sectionFilter">Section</label>
                        <select id="sectionFilter"></select>
                    </div>
                </div>

                <div class="filter-count" id="filterCountText">Showing 0 of 0 student submissions</div>
            </section>

            <section class="table-card">
                <table class="submission-table">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Course</th>
                            <th>Year Level</th>
                            <th>Section</th>
                            <th>Submitted Date</th>
                            <th>Status</th>
                            <th class="actions-head">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="submissionTableBody"></tbody>
                </table>
            </section>
        </div>
    </main>

    <div id="modalRoot"></div>

    <script>
        const iconSvg = {
            eye: '<?php echo icon_svg("eye"); ?>',
            trash: '<?php echo icon_svg("trash"); ?>',
            close: '<?php echo icon_svg("close"); ?>'
        };

        let activeTab = "All";

        let submissionRecords = [
            {
                id: 1,
                studentId: "24-001234",
                studentName: "Domingo, Mario Jr. C.",
                department: "College of Computer Studies",
                course: "BSIT - Bachelor of Science in Information Technology",
                courseCode: "BSIT",
                yearLevel: "2nd Year",
                section: "BSIT-2A",
                submittedDate: "Apr 10, 2026, 03:45 PM",
                status: "Completed",
                averageRating: 3.92,
                subjects: [
                    { code: "IT 221", title: "Human Computer Interaction", faculty: "Prof. Maria Teresa Dela Cruz", rating: 4.5, comment: "Excellent teaching methods and very engaging classes." },
                    { code: "IT 111", title: "Introduction to Computing", faculty: "Julius R. Samonte", rating: 4.2, comment: "Clear explanations and helpful during consultations." },
                    { code: "GE 102", title: "Purposive Communication", faculty: "Fernando J. Alvarez", rating: 3.0, comment: "Good content delivery but could improve engagement." },
                    { code: "GE 101", title: "Understanding the Self", faculty: "Beatriz G. Navarro", rating: 3.5, comment: "Interesting discussions and thought provoking lectures." }
                ]
            },
            {
                id: 2,
                studentId: "23-005678",
                studentName: "Reyes, Maria Clara S.",
                department: "College of Computer Studies",
                course: "BSCS - Bachelor of Science in Computer Science",
                courseCode: "BSCS",
                yearLevel: "3rd Year",
                section: "BSCS-3A",
                submittedDate: "Apr 12, 2026, 09:15 AM",
                status: "Completed",
                averageRating: 4.28,
                subjects: [
                    { code: "CS 321", title: "Software Engineering", faculty: "Dr. Elmer V. Ramos", rating: 4.7, comment: "Organized and practical lessons." },
                    { code: "CS 303", title: "Database Systems", faculty: "Prof. Liza M. Ortega", rating: 4.4, comment: "The activities helped us understand database design." },
                    { code: "GE 102", title: "Purposive Communication", faculty: "Fernando J. Alvarez", rating: 3.9, comment: "Helpful feedback on presentations." },
                    { code: "GE 101", title: "Understanding the Self", faculty: "Beatriz G. Navarro", rating: 4.1, comment: "The class was reflective and meaningful." }
                ]
            },
            {
                id: 3,
                studentId: "22-000632",
                studentName: "Reyes, Amaris D.",
                department: "College of Computer Studies",
                course: "BSIT - Bachelor of Science in Information Technology",
                courseCode: "BSIT",
                yearLevel: "4th Year",
                section: "BSIT-4A",
                submittedDate: "Apr 11, 2026, 04:45 PM",
                status: "Completed",
                averageRating: 4.10,
                subjects: [
                    { code: "IT 421", title: "Capstone Project", faculty: "Dr. Robert Anderson", rating: 4.6, comment: "Very supportive during project consultations." },
                    { code: "IT 414", title: "System Integration", faculty: "Prof. Emily Chen", rating: 4.0, comment: "Clear project expectations and examples." },
                    { code: "IT 405", title: "Information Assurance", faculty: "Dr. Michael Torres", rating: 3.8, comment: "Good lectures and relevant topics." }
                ]
            },
            {
                id: 4,
                studentId: "24-002345",
                studentName: "Santos, Leon B.",
                department: "College of Computer Studies",
                course: "BSCS - Bachelor of Science in Computer Science",
                courseCode: "BSCS",
                yearLevel: "2nd Year",
                section: "BSCS-2A",
                submittedDate: "",
                status: "Pending",
                averageRating: null,
                subjects: []
            },
            {
                id: 5,
                studentId: "23-004821",
                studentName: "Villar, Theo G.",
                department: "College of Computer Studies",
                course: "BSIT - Bachelor of Science in Information Technology",
                courseCode: "BSIT",
                yearLevel: "3rd Year",
                section: "BSIT-3A",
                submittedDate: "Apr 20, 2026, 10:15 AM",
                status: "Completed",
                averageRating: 4.35,
                subjects: [
                    { code: "IT 311", title: "Networking 2", faculty: "Prof. Daniel Lim", rating: 4.3, comment: "Hands on activities were useful." },
                    { code: "IT 312", title: "Web Development", faculty: "Prof. Emily Chen", rating: 4.5, comment: "Very clear examples and demonstrations." },
                    { code: "IT 313", title: "Application Development", faculty: "Prof. Linda Martinez", rating: 4.6, comment: "The instructor gave helpful coding feedback." }
                ]
            },
            {
                id: 6,
                studentId: "25-000832",
                studentName: "Guerrero, June E.",
                department: "College of Computer Studies",
                course: "BSIT - Bachelor of Science in Information Technology",
                courseCode: "BSIT",
                yearLevel: "1st Year",
                section: "BSIT-1B",
                submittedDate: "",
                status: "Pending",
                averageRating: null,
                subjects: []
            },
            {
                id: 7,
                studentId: "25-001245",
                studentName: "Garcia, Sofia M.",
                department: "College of Nursing",
                course: "BSN - Bachelor of Science in Nursing",
                courseCode: "BSN",
                yearLevel: "1st Year",
                section: "BSN-1A",
                submittedDate: "Apr 14, 2026, 10:30 AM",
                status: "Completed",
                averageRating: 4.48,
                subjects: [
                    { code: "NCM 101", title: "Fundamentals of Nursing", faculty: "Dr. Grace Mendoza", rating: 4.8, comment: "Very professional and organized." },
                    { code: "BIO 101", title: "Anatomy and Physiology", faculty: "Prof. Carla Reyes", rating: 4.4, comment: "Difficult topics were explained clearly." }
                ]
            },
            {
                id: 8,
                studentId: "24-003156",
                studentName: "Mendoza, Carlos J.",
                department: "College of Business and Accountancy",
                course: "BSBA - Bachelor of Science in Business Administration",
                courseCode: "BSBA",
                yearLevel: "2nd Year",
                section: "BSBA-2B",
                submittedDate: "",
                status: "Pending",
                averageRating: null,
                subjects: []
            },
            {
                id: 9,
                studentId: "23-002789",
                studentName: "Fernandez, Isabella R.",
                department: "College of Arts and Sciences",
                course: "BS-Psych - Bachelor of Science in Psychology",
                courseCode: "BS-Psych",
                yearLevel: "3rd Year",
                section: "BS-Psych-3A",
                submittedDate: "Apr 15, 2026, 01:45 PM",
                status: "Completed",
                averageRating: 4.20,
                subjects: [
                    { code: "PSY 301", title: "Abnormal Psychology", faculty: "Dr. Hannah Cruz", rating: 4.3, comment: "The lessons were engaging and informative." },
                    { code: "PSY 302", title: "Psychological Assessment", faculty: "Prof. Aaron Velasco", rating: 4.0, comment: "Assessment examples were helpful." }
                ]
            },
            {
                id: 10,
                studentId: "22-001534",
                studentName: "Lopez, Miguel T.",
                department: "College of Engineering",
                course: "BSECE - Bachelor of Science in Electronics and Communications Engineering",
                courseCode: "BSECE",
                yearLevel: "4th Year",
                section: "BSECE-4A",
                submittedDate: "Apr 16, 2026, 03:00 PM",
                status: "Completed",
                averageRating: 3.85,
                subjects: [
                    { code: "ECE 421", title: "Communication Systems", faculty: "Engr. Marco Santos", rating: 4.0, comment: "Good explanations of technical topics." },
                    { code: "ECE 422", title: "Electronics Design", faculty: "Engr. Ana Cruz", rating: 3.8, comment: "The activities were challenging but useful." }
                ]
            },
            {
                id: 11,
                studentId: "25-002876",
                studentName: "Torres, Daniela V.",
                department: "College of Hospitality Management",
                course: "BSHM - Bachelor of Science in Hospitality Management",
                courseCode: "BSHM",
                yearLevel: "1st Year",
                section: "BSHM-1A",
                submittedDate: "",
                status: "Pending",
                averageRating: null,
                subjects: []
            },
            {
                id: 12,
                studentId: "23-005123",
                studentName: "Rivera, Andre P.",
                department: "College of Business and Accountancy",
                course: "BSA - Bachelor of Science in Accountancy",
                courseCode: "BSA",
                yearLevel: "3rd Year",
                section: "BSA-3B",
                submittedDate: "Apr 17, 2026, 09:30 AM",
                status: "Completed",
                averageRating: 4.05,
                subjects: [
                    { code: "ACC 301", title: "Financial Accounting", faculty: "Prof. Renato Aguilar", rating: 4.0, comment: "Clear problem solving examples." },
                    { code: "ACC 302", title: "Cost Accounting", faculty: "Prof. Norma Abella", rating: 4.1, comment: "Helpful exercises and explanations." }
                ]
            },
            {
                id: 13,
                studentId: "24-004567",
                studentName: "Navarro, Camila L.",
                department: "College of Education",
                course: "BEEd - Bachelor of Elementary Education",
                courseCode: "BEEd",
                yearLevel: "2nd Year",
                section: "BEEd-2A",
                submittedDate: "Apr 18, 2026, 02:15 PM",
                status: "Completed",
                averageRating: 4.55,
                subjects: [
                    { code: "ED 201", title: "Child and Adolescent Development", faculty: "Prof. Sarah Williams", rating: 4.6, comment: "The lessons were organized and inspiring." },
                    { code: "ED 202", title: "Assessment of Learning", faculty: "Dr. James Rodriguez", rating: 4.5, comment: "Clear and practical examples." }
                ]
            },
            {
                id: 14,
                studentId: "22-003421",
                studentName: "Morales, Gabriel S.",
                department: "College of Education",
                course: "BSEd-English - Bachelor of Secondary Education major in English",
                courseCode: "BSEd-English",
                yearLevel: "4th Year",
                section: "BSEd-English-4A",
                submittedDate: "",
                status: "Pending",
                averageRating: null,
                subjects: []
            },
            {
                id: 15,
                studentId: "23-001987",
                studentName: "Castillo, Valentina C.",
                department: "College of Education",
                course: "BSEd-Math - Bachelor of Secondary Education major in Mathematics",
                courseCode: "BSEd-Math",
                yearLevel: "3rd Year",
                section: "BSEd-Math-3A",
                submittedDate: "",
                status: "Pending",
                averageRating: null,
                subjects: []
            },
            {
                id: 16,
                studentId: "25-003654",
                studentName: "Ramos, Santiago D.",
                department: "College of Education",
                course: "BSEd-Filipino - Bachelor of Secondary Education major in Filipino",
                courseCode: "BSEd-Filipino",
                yearLevel: "1st Year",
                section: "BSEd-Filipino-1A",
                submittedDate: "Apr 19, 2026, 11:00 AM",
                status: "Completed",
                averageRating: 4.00,
                subjects: [
                    { code: "FIL 101", title: "Introduksiyon sa Pag aaral ng Wika", faculty: "Prof. Linda Martinez", rating: 4.1, comment: "Maayos ang pagpapaliwanag sa bawat paksa." },
                    { code: "ED 101", title: "The Teaching Profession", faculty: "Dr. James Rodriguez", rating: 4.0, comment: "The lessons were clear and useful." }
                ]
            }
        ];

        const modalRoot = document.getElementById("modalRoot");

        function escapeHTML(value) {
            return String(value)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function getUniqueValues(key) {
            return [...new Set(submissionRecords.map(function (record) {
                return record[key];
            }))].sort();
        }

        function createOption(value, label) {
            return `<option value="${escapeHTML(value)}">${escapeHTML(label)}</option>`;
        }

        function populateFilters() {
            const departmentFilter = document.getElementById("departmentFilter");
            const courseFilter = document.getElementById("courseFilter");
            const yearFilter = document.getElementById("yearFilter");
            const sectionFilter = document.getElementById("sectionFilter");

            departmentFilter.innerHTML = createOption("", "All Departments") + getUniqueValues("department").map(function (value) {
                return createOption(value, value);
            }).join("");

            courseFilter.innerHTML = createOption("", "All Courses") + getUniqueValues("courseCode").map(function (value) {
                return createOption(value, value);
            }).join("");

            yearFilter.innerHTML = createOption("", "All Year Levels") + getUniqueValues("yearLevel").map(function (value) {
                return createOption(value, value);
            }).join("");

            sectionFilter.innerHTML = createOption("", "All Sections") + getUniqueValues("section").map(function (value) {
                return createOption(value, value);
            }).join("");
        }

        function getFilteredRecords() {
            const searchValue = document.getElementById("searchInput").value.trim().toLowerCase();
            const department = document.getElementById("departmentFilter").value;
            const course = document.getElementById("courseFilter").value;
            const yearLevel = document.getElementById("yearFilter").value;
            const section = document.getElementById("sectionFilter").value;

            return submissionRecords.filter(function (record) {
                const matchesTab = activeTab === "All" || record.status === activeTab;

                const combinedText = [
                    record.studentId,
                    record.studentName,
                    record.course,
                    record.courseCode,
                    record.yearLevel,
                    record.section,
                    record.department
                ].join(" ").toLowerCase();

                const matchesSearch = searchValue === "" || combinedText.includes(searchValue);
                const matchesDepartment = department === "" || record.department === department;
                const matchesCourse = course === "" || record.courseCode === course;
                const matchesYear = yearLevel === "" || record.yearLevel === yearLevel;
                const matchesSection = section === "" || record.section === section;

                return matchesTab && matchesSearch && matchesDepartment && matchesCourse && matchesYear && matchesSection;
            });
        }

        function renderSummary() {
            const total = submissionRecords.length;
            const completed = submissionRecords.filter(function (record) {
                return record.status === "Completed";
            }).length;
            const pending = submissionRecords.filter(function (record) {
                return record.status === "Pending";
            }).length;
            const rate = total > 0 ? ((completed / total) * 100).toFixed(1) : "0.0";

            document.getElementById("completedCount").textContent = completed;
            document.getElementById("pendingCount").textContent = pending;
            document.getElementById("totalResponsesCount").textContent = completed;
            document.getElementById("participationRate").textContent = rate + "%";

            document.getElementById("allTabCount").textContent = "(" + total + ")";
            document.getElementById("completedTabCount").textContent = "(" + completed + ")";
            document.getElementById("pendingTabCount").textContent = "(" + pending + ")";
        }

        function renderTabs() {
            document.querySelectorAll(".tab-button").forEach(function (button) {
                button.classList.remove("active");

                if (button.dataset.tab === activeTab) {
                    button.classList.add("active");
                }
            });
        }

        function renderTable() {
            const records = getFilteredRecords();
            const tbody = document.getElementById("submissionTableBody");

            if (records.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="empty-row">No student submissions found.</td>
                    </tr>
                `;
            } else {
                tbody.innerHTML = records.map(function (record) {
                    const statusClass = record.status.toLowerCase();
                    const submittedDate = record.submittedDate === "" ? "—" : record.submittedDate;

                    return `
                        <tr>
                            <td><strong>${escapeHTML(record.studentId)}</strong></td>
                            <td>${escapeHTML(record.studentName)}</td>
                            <td>${escapeHTML(record.course)}</td>
                            <td>${escapeHTML(record.yearLevel)}</td>
                            <td>${escapeHTML(record.section)}</td>
                            <td>${escapeHTML(submittedDate)}</td>
                            <td>
                                <span class="status-badge ${statusClass}">
                                    <span></span>
                                    ${escapeHTML(record.status)}
                                </span>
                            </td>
                            <td>
                                <div class="action-icons">
                                    <button class="view" type="button" title="View Details" onclick="openSubmissionDetails(${record.id})">
                                        ${iconSvg.eye}
                                    </button>

                                    <button class="delete" type="button" title="Delete Record" onclick="deleteSubmission(${record.id})">
                                        ${iconSvg.trash}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                }).join("");
            }

            document.getElementById("filterCountText").textContent =
                "Showing " + records.length + " of " + submissionRecords.length + " student submissions";
        }

        function renderAll() {
            renderSummary();
            renderTabs();
            renderTable();
        }

        function setActiveTab(tab) {
            activeTab = tab;
            renderAll();
        }

        function deleteSubmission(id) {
            const record = submissionRecords.find(function (item) {
                return Number(item.id) === Number(id);
            });

            if (!record) {
                return;
            }

            if (!confirm("Delete this submission monitoring record?")) {
                return;
            }

            submissionRecords = submissionRecords.filter(function (item) {
                return Number(item.id) !== Number(id);
            });

            populateFilters();
            renderAll();
        }

        function closeModal() {
            modalRoot.innerHTML = "";
            document.body.classList.remove("modal-open");
        }

        function closeModalOnBackdrop(event) {
            if (event.target.classList.contains("modal-overlay")) {
                closeModal();
            }
        }

        function openModal(content) {
            modalRoot.innerHTML = content;
            document.body.classList.add("modal-open");
        }

        function openSubmissionDetails(id) {
            const record = submissionRecords.find(function (item) {
                return Number(item.id) === Number(id);
            });

            if (!record) {
                return;
            }

            const statusClass = record.status.toLowerCase();

            let subjectContent = "";

            if (record.status === "Pending") {
                subjectContent = `
                    <div class="pending-message">
                        <h3>Evaluation Not Submitted Yet</h3>
                        <p>This student still has pending teacher evaluations. No ratings, comments, or submitted evaluation details are available yet.</p>
                    </div>

                    <h3 class="details-section-title">Assigned Subjects Pending Evaluation</h3>

                    <div class="subject-detail-card pending-subject">
                        <div class="subject-detail-top">
                            <div>
                                <strong>IT 221, Human Computer Interaction</strong>
                                <p>Faculty: Prof. Maria Teresa Dela Cruz</p>
                            </div>
                            <div class="rating-box">Pending</div>
                        </div>
                    </div>

                    <div class="subject-detail-card pending-subject">
                        <div class="subject-detail-top">
                            <div>
                                <strong>IT 111, Introduction to Computing</strong>
                                <p>Faculty: Julius R. Samonte</p>
                            </div>
                            <div class="rating-box">Pending</div>
                        </div>
                    </div>

                    <div class="subject-detail-card pending-subject">
                        <div class="subject-detail-top">
                            <div>
                                <strong>GE 102, Purposive Communication</strong>
                                <p>Faculty: Fernando J. Alvarez</p>
                            </div>
                            <div class="rating-box">Pending</div>
                        </div>
                    </div>
                `;
            } else {
                subjectContent = `
                    <h3 class="details-section-title">Evaluated Subjects and Teachers</h3>

                    ${record.subjects.map(function (subject) {
                        return `
                            <div class="subject-detail-card">
                                <div class="subject-detail-top">
                                    <div>
                                        <strong>${escapeHTML(subject.code)}, ${escapeHTML(subject.title)}</strong>
                                        <p>Faculty: ${escapeHTML(subject.faculty)}</p>
                                    </div>

                                    <div class="rating-box">
                                        <span>Rating</span>
                                        <strong>${Number(subject.rating).toFixed(1)} / 5.0</strong>
                                    </div>
                                </div>

                                <div class="comment-box">
                                    <span>Comment</span>
                                    <p>“${escapeHTML(subject.comment)}”</p>
                                </div>
                            </div>
                        `;
                    }).join("")}
                `;
            }

            const averageContent = record.status === "Completed"
                ? `
                    <div class="average-card">
                        <span>Average Rating (All Teachers)</span>
                        <strong>${Number(record.averageRating).toFixed(2)} / 5.0</strong>
                        <p>Based on ${record.subjects.length} evaluations</p>
                    </div>
                `
                : `
                    <div class="average-card pending-average">
                        <span>Average Rating (All Teachers)</span>
                        <strong>Pending</strong>
                        <p>No submitted evaluations yet</p>
                    </div>
                `;

            openModal(`
                <div class="modal-overlay" onclick="closeModalOnBackdrop(event)">
                    <div class="modal-box submission-details-modal">
                        <div class="modal-header">
                            <h2>Submission Details</h2>
                            <button class="modal-close" type="button" onclick="closeModal()">${iconSvg.close}</button>
                        </div>

                        <div class="modal-body">
                            <section class="student-info-card">
                                <h3>Student Information</h3>

                                <div class="student-info-grid">
                                    <div>
                                        <span>Student ID</span>
                                        <strong>${escapeHTML(record.studentId)}</strong>
                                    </div>

                                    <div>
                                        <span>Student Name</span>
                                        <strong>${escapeHTML(record.studentName)}</strong>
                                    </div>

                                    <div>
                                        <span>Course</span>
                                        <strong>${escapeHTML(record.course)}</strong>
                                    </div>

                                    <div>
                                        <span>Year Level</span>
                                        <strong>${escapeHTML(record.yearLevel)}</strong>
                                    </div>

                                    <div>
                                        <span>Section</span>
                                        <strong>${escapeHTML(record.section)}</strong>
                                    </div>

                                    <div>
                                        <span>Status</span>
                                        <span class="status-badge ${statusClass}">
                                            <span></span>
                                            ${escapeHTML(record.status)}
                                        </span>
                                    </div>

                                    <div>
                                        <span>Submitted Date</span>
                                        <strong>${escapeHTML(record.submittedDate === "" ? "Not submitted yet" : record.submittedDate)}</strong>
                                    </div>
                                </div>
                            </section>

                            ${averageContent}

                            ${subjectContent}
                        </div>

                        <div class="modal-footer">
                            <button class="secondary-button" type="button" onclick="closeModal()">Close</button>
                        </div>
                    </div>
                </div>
            `);
        }

        document.querySelectorAll(".tab-button").forEach(function (button) {
            button.addEventListener("click", function () {
                setActiveTab(button.dataset.tab);
            });
        });

        document.getElementById("searchInput").addEventListener("input", renderTable);
        document.getElementById("departmentFilter").addEventListener("change", renderTable);
        document.getElementById("courseFilter").addEventListener("change", renderTable);
        document.getElementById("yearFilter").addEventListener("change", renderTable);
        document.getElementById("sectionFilter").addEventListener("change", renderTable);

        populateFilters();
        renderAll();
    </script>
</body>
</html>