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
        "plus" => '<svg viewBox="0 0 24 24"><path d="M12 5v14"></path><path d="M5 12h14"></path></svg>',
        "eye" => '<svg viewBox="0 0 24 24"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"></path><circle cx="12" cy="12" r="3"></circle></svg>',
        "edit" => '<svg viewBox="0 0 24 24"><path d="M12 20h9"></path><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"></path></svg>',
        "trash" => '<svg viewBox="0 0 24 24"><path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="M19 6l-1 14H6L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path></svg>',
        "calendar" => '<svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M16 2v4"></path><path d="M8 2v4"></path><path d="M3 10h18"></path></svg>',
        "help" => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 1 1 5.82 1c0 2-3 2-3 4"></path><path d="M12 17h.01"></path></svg>',
        "save" => '<svg viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><path d="M17 21v-8H7v8"></path><path d="M7 3v5h8"></path></svg>',
        "play" => '<svg viewBox="0 0 24 24"><path d="M5 3l14 9-14 9V3z"></path></svg>',
        "stop" => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"></circle><path d="M15 9l-6 6"></path><path d="M9 9l6 6"></path></svg>',
        "check" => '<svg viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"></path></svg>',
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
    <title>Evaluation Setup</title>
    <link rel="stylesheet" href="evaluation-setup.css?v=<?php echo time(); ?>">
</head>
<body class="evaluation-setup-page">
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
            <a class="nav-link active" href="evaluation_setup.php"><?php echo icon_svg("settings"); ?> Evaluation Setup</a>
            <a class="nav-link" href="submission_monitoring.php"><?php echo icon_svg("clipboard"); ?> Submission Monitoring</a>
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
                    <h1>Evaluation Setup</h1>
                    <p>Configure evaluation criteria, schedule, and settings</p>
                </div>

                <span class="status-pill closed" id="evaluationStatusBadge">
                    <span></span>
                    Evaluation Closed
                </span>
            </div>

            <section class="setup-card">
                <div class="section-title">
                    <?php echo icon_svg("calendar"); ?>
                    <h2>Current Schedule</h2>
                </div>

                <div class="schedule-grid">
                    <div>
                        <label>Semester</label>
                        <select id="semesterSelect">
                            <option value="1st Semester">1st Semester</option>
                            <option value="2nd Semester" selected>2nd Semester</option>
                            <option value="Summer">Summer</option>
                        </select>
                    </div>

                    <div>
                        <label>Academic Year</label>
                        <input type="text" id="academicYearInput" value="2025-2026">
                    </div>

                    <div>
                        <label>Start Date</label>
                        <input type="date" id="startDateInput" value="2026-04-01">
                    </div>

                    <div>
                        <label>End Date</label>
                        <input type="date" id="endDateInput" value="2026-04-30">
                    </div>
                </div>
            </section>

            <section class="setup-card">
                <div class="card-header-row">
                    <div class="section-title">
                        <?php echo icon_svg("settings"); ?>
                        <h2>Evaluation Criteria</h2>
                    </div>

                    <button class="dark-button compact-button" type="button" onclick="openCriteriaModal('add')">
                        <?php echo icon_svg("plus"); ?> Add Criteria
                    </button>
                </div>

                <div class="table-wrap">
                    <table class="criteria-table">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Criteria Name</th>
                                <th>Weight (%)</th>
                                <th>Status</th>
                                <th class="actions-head">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="criteriaTableBody"></tbody>
                    </table>
                </div>
            </section>

            <section class="setup-card questions-card">
                <div class="questions-top">
                    <div class="section-title">
                        <?php echo icon_svg("help"); ?>
                        <h2>Evaluation Questions</h2>
                    </div>

                    <div class="display-control">
                        <label for="displayCountSelect">Questions to display per criteria:</label>
                        <select id="displayCountSelect">
                            <option value="5" selected>5</option>
                            <option value="6">6</option>
                            <option value="7">7</option>
                            <option value="8">8</option>
                            <option value="9">9</option>
                            <option value="10">10</option>
                        </select>
                    </div>
                </div>

                <p class="questions-note" id="questionsNote"></p>

                <div id="questionGroups"></div>
            </section>

            <section class="setup-card rating-card">
                <h2>Rating Scale</h2>
                <p>Current scale: 1 to 5 (Likert Scale)</p>

                <div class="rating-scale-list">
                    <span>1 - Strongly Disagree</span>
                    <span>2 - Disagree</span>
                    <span>3 - Neutral</span>
                    <span>4 - Agree</span>
                    <span>5 - Strongly Agree</span>
                </div>
            </section>

            <section class="scope-card">
                <strong>Current Active Scope:</strong>
                <span id="currentActiveScopeText">No active scope</span>
            </section>

            <section class="bottom-actions">
                <button class="blue-button" type="button" onclick="openPreviewModal()">
                    <?php echo icon_svg("eye"); ?> Preview Form
                </button>

                <button class="dark-button" type="button" onclick="saveSetup()">
                    <?php echo icon_svg("save"); ?> Save Setup
                </button>

                <button class="green-button" type="button" onclick="openScopeModal('open')">
                    <?php echo icon_svg("play"); ?> Open Evaluation
                </button>

                <button class="red-button" type="button" onclick="openScopeModal('close')">
                    <?php echo icon_svg("stop"); ?> Close Evaluation
                </button>
            </section>
        </div>
    </main>

    <div id="modalRoot"></div>

    <script>
        const iconSvg = {
            eye: '<?php echo icon_svg("eye"); ?>',
            edit: '<?php echo icon_svg("edit"); ?>',
            trash: '<?php echo icon_svg("trash"); ?>',
            plus: '<?php echo icon_svg("plus"); ?>',
            close: '<?php echo icon_svg("close"); ?>',
            check: '<?php echo icon_svg("check"); ?>'
        };

        let displayCount = 5;
        let evaluationStatus = "Closed";
        let currentActiveScope = "No active scope";
        let nextCriteriaId = 7;
        let nextQuestionId = 700;

        const departments = [
            { id: "CCS", name: "College of Computer Studies" },
            { id: "CBA", name: "College of Business and Accountancy" },
            { id: "COE", name: "College of Engineering" },
            { id: "COED", name: "College of Education" },
            { id: "CON", name: "College of Nursing" },
            { id: "CAS", name: "College of Arts and Sciences" }
        ];

        const courses = [
            { id: "BSIT", name: "Bachelor of Science in Information Technology", departmentId: "CCS" },
            { id: "BSCS", name: "Bachelor of Science in Computer Science", departmentId: "CCS" },
            { id: "BSBA", name: "Bachelor of Science in Business Administration", departmentId: "CBA" },
            { id: "BSA", name: "Bachelor of Science in Accountancy", departmentId: "CBA" },
            { id: "BSED", name: "Bachelor of Secondary Education", departmentId: "COED" },
            { id: "BSPSYCH", name: "Bachelor of Science in Psychology", departmentId: "CAS" }
        ];

        const yearLevels = [
            "1st Year",
            "2nd Year",
            "3rd Year",
            "4th Year"
        ];

        const sections = [
            { id: "BSIT 2A", courseId: "BSIT", yearLevel: "2nd Year" },
            { id: "BSIT 3A", courseId: "BSIT", yearLevel: "3rd Year" },
            { id: "BSCS 2A", courseId: "BSCS", yearLevel: "2nd Year" },
            { id: "BSBA 2A", courseId: "BSBA", yearLevel: "2nd Year" },
            { id: "BSED 2A", courseId: "BSED", yearLevel: "2nd Year" },
            { id: "BSPSYCH 2A", courseId: "BSPSYCH", yearLevel: "2nd Year" }
        ];

        let criteriaRecords = [
            {
                id: 1,
                order: 1,
                name: "Teaching Effectiveness",
                description: "Evaluates the effectiveness of teaching methods and delivery",
                weight: 25,
                status: "Active",
                questions: [
                    { id: 101, text: "The instructor explains concepts clearly and effectively.", shown: true },
                    { id: 102, text: "The instructor uses appropriate teaching methods to facilitate learning.", shown: true },
                    { id: 103, text: "The instructor provides relevant examples and applications.", shown: true },
                    { id: 104, text: "The instructor encourages critical thinking and problem-solving.", shown: true },
                    { id: 105, text: "The instructor adapts teaching style to suit different learners.", shown: true }
                ]
            },
            {
                id: 2,
                order: 2,
                name: "Subject Mastery",
                description: "Evaluates the instructor knowledge and mastery of the subject matter",
                weight: 20,
                status: "Active",
                questions: [
                    { id: 201, text: "The instructor demonstrates mastery of the subject matter.", shown: true },
                    { id: 202, text: "The instructor answers questions accurately and confidently.", shown: true },
                    { id: 203, text: "The instructor incorporates current and relevant content.", shown: true },
                    { id: 204, text: "The instructor connects topics to real-world applications.", shown: true },
                    { id: 205, text: "The instructor shows depth of understanding in the subject.", shown: true }
                ]
            },
            {
                id: 3,
                order: 3,
                name: "Preparedness",
                description: "Evaluates class readiness, organization, and lesson preparation",
                weight: 15,
                status: "Active",
                questions: [
                    { id: 301, text: "The instructor comes to class well-prepared.", shown: true },
                    { id: 302, text: "The instructor follows the course syllabus consistently.", shown: true },
                    { id: 303, text: "The instructor manages class time effectively.", shown: true },
                    { id: 304, text: "The instructor provides clear lesson objectives.", shown: true },
                    { id: 305, text: "The instructor uses organized teaching materials.", shown: true }
                ]
            },
            {
                id: 4,
                order: 4,
                name: "Engagement",
                description: "Evaluates how the instructor motivates and involves students",
                weight: 15,
                status: "Active",
                questions: [
                    { id: 401, text: "The instructor encourages student participation.", shown: true },
                    { id: 402, text: "The instructor creates an inclusive learning environment.", shown: true },
                    { id: 403, text: "The instructor motivates students to learn.", shown: true },
                    { id: 404, text: "The instructor responds positively to student input.", shown: true },
                    { id: 405, text: "The instructor uses interactive teaching strategies.", shown: true }
                ]
            },
            {
                id: 5,
                order: 5,
                name: "Professionalism",
                description: "Evaluates professional attitude, conduct, and fairness",
                weight: 15,
                status: "Active",
                questions: [
                    { id: 501, text: "The instructor treats students with respect.", shown: true },
                    { id: 502, text: "The instructor maintains professional behavior at all times.", shown: true },
                    { id: 503, text: "The instructor is punctual and consistent in attendance.", shown: true },
                    { id: 504, text: "The instructor follows institutional policies and ethics.", shown: true },
                    { id: 505, text: "The instructor shows fairness in grading and evaluation.", shown: true }
                ]
            },
            {
                id: 6,
                order: 6,
                name: "Feedback & Assessment",
                description: "Evaluates feedback quality, assessment fairness, and grading clarity",
                weight: 10,
                status: "Active",
                questions: [
                    { id: 601, text: "The instructor provides timely feedback on assignments.", shown: true },
                    { id: 602, text: "The instructor uses fair assessment methods.", shown: true },
                    { id: 603, text: "The instructor explains grading criteria clearly.", shown: true },
                    { id: 604, text: "The instructor offers constructive feedback for improvement.", shown: true },
                    { id: 605, text: "The instructor evaluates students objectively.", shown: true }
                ]
            }
        ];

        const modalRoot = document.getElementById("modalRoot");
        const displayCountSelect = document.getElementById("displayCountSelect");

        function escapeHTML(value) {
            return String(value)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function getSortedCriteria() {
            return [...criteriaRecords].sort(function (a, b) {
                return Number(a.order) - Number(b.order);
            });
        }

        function getTotalWeight(excludedId = null) {
            return criteriaRecords.reduce(function (total, criteria) {
                if (excludedId !== null && Number(criteria.id) === Number(excludedId)) {
                    return total;
                }

                return total + Number(criteria.weight || 0);
            }, 0);
        }

        function getShownQuestions(criteria) {
            return criteria.questions.filter(function (question) {
                return question.shown;
            });
        }

        function renderAll() {
            renderStatus();
            renderCriteriaTable();
            renderQuestionSections();
            renderQuestionNote();
        }

        function renderStatus() {
            const badge = document.getElementById("evaluationStatusBadge");
            const scopeText = document.getElementById("currentActiveScopeText");

            badge.className = "status-pill " + evaluationStatus.toLowerCase();
            badge.innerHTML = "<span></span> Evaluation " + escapeHTML(evaluationStatus);
            scopeText.textContent = currentActiveScope;
        }

        function renderQuestionNote() {
            document.getElementById("questionsNote").textContent =
                "Each criteria must have a minimum of 5 and a maximum of 10 questions. Selected display count: " +
                displayCount +
                " questions per criteria will be shown to students.";
        }

        function renderCriteriaTable() {
            const tbody = document.getElementById("criteriaTableBody");
            const criteria = getSortedCriteria();

            tbody.innerHTML = criteria.map(function (item) {
                return `
                    <tr>
                        <td>${escapeHTML(item.order)}</td>
                        <td><strong>${escapeHTML(item.name)}</strong></td>
                        <td>${escapeHTML(item.weight)}%</td>
                        <td>
                            <span class="status-badge ${item.status.toLowerCase()}">
                                ${escapeHTML(item.status.toLowerCase())}
                            </span>
                        </td>
                        <td>
                            <div class="action-icons">
                                <button class="view" type="button" title="View" onclick="openCriteriaModal('view', ${item.id})">
                                    ${iconSvg.eye}
                                </button>
                                <button class="edit" type="button" title="Edit" onclick="openCriteriaModal('edit', ${item.id})">
                                    ${iconSvg.edit}
                                </button>
                                <button class="delete" type="button" title="Delete" onclick="deleteCriteria(${item.id})">
                                    ${iconSvg.trash}
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join("");
        }

        function renderQuestionSections() {
            const container = document.getElementById("questionGroups");
            const criteria = getSortedCriteria();

            container.innerHTML = criteria.map(function (item) {
                const rows = item.questions.map(function (question, index) {
                    const displayClass = question.shown ? "shown" : "hidden";
                    const displayLabel = question.shown ? "Shown" : "Hidden";

                    return `
                        <tr>
                            <td>${index + 1}</td>
                            <td>${escapeHTML(question.text)}</td>
                            <td>
                                <label class="display-badge ${displayClass}">
                                    <input type="checkbox" ${question.shown ? "checked" : ""} onchange="toggleQuestionShown(${item.id}, ${question.id}, this)">
                                    <span>${escapeHTML(displayLabel)}</span>
                                </label>
                            </td>
                            <td>
                                <div class="action-icons small-icons">
                                    <button class="edit" type="button" title="Edit" onclick="openQuestionModal('edit', ${item.id}, ${question.id})">
                                        ${iconSvg.edit}
                                    </button>
                                    <button class="delete soft-delete" type="button" title="Delete" onclick="deleteQuestion(${item.id}, ${question.id})">
                                        ${iconSvg.trash}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                }).join("");

                return `
                    <article class="question-group">
                        <div class="question-group-header">
                            <div class="question-group-title">
                                <span class="criteria-order-box">${escapeHTML(item.order)}</span>
                                <div>
                                    <h3>${escapeHTML(item.name)}</h3>
                                    <p>${escapeHTML(item.weight)}% weight</p>
                                </div>
                            </div>

                            <div class="question-group-actions">
                                <span class="question-count-badge">${item.questions.length}/10 questions</span>
                                <button class="dark-button mini-button" type="button" onclick="openQuestionModal('add', ${item.id})">
                                    ${iconSvg.plus} Add Question
                                </button>
                            </div>
                        </div>

                        <div class="table-wrap">
                            <table class="question-table">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>Question</th>
                                        <th>Display</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>${rows}</tbody>
                            </table>
                        </div>
                    </article>
                `;
            }).join("");
        }

        function findCriteria(criteriaId) {
            return criteriaRecords.find(function (criteria) {
                return Number(criteria.id) === Number(criteriaId);
            });
        }

        function findQuestion(criteria, questionId) {
            return criteria.questions.find(function (question) {
                return Number(question.id) === Number(questionId);
            });
        }

        function openModal(content) {
            modalRoot.innerHTML = content;
            document.body.classList.add("modal-open");
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

        function showMessageModal(title, message, type = "warning") {
            const iconClass = type === "success" ? "success-icon" : type === "error" ? "error-icon" : "warning-icon";

            openModal(`
                <div class="modal-overlay" onclick="closeModalOnBackdrop(event)">
                    <div class="modal-box message-box">
                        <div class="${iconClass}">
                            ${type === "success" ? iconSvg.check : "!"}
                        </div>
                        <h2>${escapeHTML(title)}</h2>
                        <p>${escapeHTML(message)}</p>
                        <button class="dark-button full-button" type="button" onclick="closeModal()">Done</button>
                    </div>
                </div>
            `);
        }

        function modalShell(title, body, footer, extraClass = "") {
            return `
                <div class="modal-overlay" onclick="closeModalOnBackdrop(event)">
                    <div class="modal-box ${extraClass}">
                        <div class="modal-header">
                            <h2>${escapeHTML(title)}</h2>
                            <button class="modal-close" type="button" onclick="closeModal()">${iconSvg.close}</button>
                        </div>
                        <div class="modal-body">
                            ${body}
                        </div>
                        <div class="modal-footer">
                            ${footer}
                        </div>
                    </div>
                </div>
            `;
        }

        function openCriteriaModal(mode, criteriaId = null) {
            const isAdd = mode === "add";
            const isView = mode === "view";
            const criteria = criteriaId ? findCriteria(criteriaId) : null;

            if (!isAdd && !criteria) {
                showMessageModal("Record Not Found", "The selected criteria was not found.", "error");
                return;
            }

            if (isView) {
                const body = `
                    <div class="details-grid">
                        <div class="detail-full">
                            <span>Criteria Name</span>
                            <strong>${escapeHTML(criteria.name)}</strong>
                        </div>

                        <div class="detail-full">
                            <span>Description</span>
                            <p>${escapeHTML(criteria.description)}</p>
                        </div>

                        <div>
                            <span>Weight</span>
                            <strong>${escapeHTML(criteria.weight)}%</strong>
                        </div>

                        <div>
                            <span>Display Order</span>
                            <strong>${escapeHTML(criteria.order)}</strong>
                        </div>

                        <div>
                            <span>Status</span>
                            <span class="status-badge ${criteria.status.toLowerCase()}">${escapeHTML(criteria.status.toLowerCase())}</span>
                        </div>
                    </div>
                `;

                const footer = `
                    <button class="secondary-button" type="button" onclick="closeModal()">Close</button>
                    <button class="dark-button" type="button" onclick="openCriteriaModal('edit', ${criteria.id})">Edit</button>
                `;

                openModal(modalShell("Criteria Details", body, footer, "criteria-details-modal"));
                return;
            }

            const title = isAdd ? "Add Evaluation Criteria" : "Edit Evaluation Criteria";
            const buttonText = isAdd ? "Add Criteria" : "Update Criteria";
            const currentTotal = getTotalWeight(isAdd ? null : criteria.id);

            const body = `
                <form id="criteriaForm" onsubmit="saveCriteriaForm(event, '${mode}', ${criteriaId === null ? "null" : criteriaId})">
                    <label>Criteria Name <span>*</span></label>
                    <input type="text" id="criteriaNameInput" value="${criteria ? escapeHTML(criteria.name) : ""}" placeholder="e.g., Teaching Effectiveness" required>

                    <label>Description <span>*</span></label>
                    <textarea id="criteriaDescriptionInput" placeholder="Describe what this criterion evaluates..." required>${criteria ? escapeHTML(criteria.description) : ""}</textarea>

                    <div class="two-column">
                        <div>
                            <label>Weight (%) <span>*</span></label>
                            <input type="number" id="criteriaWeightInput" value="${criteria ? escapeHTML(criteria.weight) : ""}" placeholder="e.g., 25" min="1" max="100" required>
                            <small>Current total: ${currentTotal}%</small>
                        </div>

                        <div>
                            <label>Display Order <span>*</span></label>
                            <input type="number" id="criteriaOrderInput" value="${criteria ? escapeHTML(criteria.order) : ""}" placeholder="e.g., 1" min="1" required>
                        </div>
                    </div>

                    <label>Status <span>*</span></label>
                    <select id="criteriaStatusInput" required>
                        <option value="Active" ${criteria && criteria.status === "Active" ? "selected" : ""}>Active</option>
                        <option value="Inactive" ${criteria && criteria.status === "Inactive" ? "selected" : ""}>Inactive</option>
                    </select>
                </form>
            `;

            const footer = `
                <button class="secondary-button" type="button" onclick="closeModal()">Cancel</button>
                <button class="dark-button" type="submit" form="criteriaForm">${buttonText}</button>
            `;

            openModal(modalShell(title, body, footer, "criteria-form-modal"));
        }

        function saveCriteriaForm(event, mode, criteriaId) {
            event.preventDefault();

            const name = document.getElementById("criteriaNameInput").value.trim();
            const description = document.getElementById("criteriaDescriptionInput").value.trim();
            const weight = Number(document.getElementById("criteriaWeightInput").value);
            const order = Number(document.getElementById("criteriaOrderInput").value);
            const status = document.getElementById("criteriaStatusInput").value;

            if (name === "" || description === "" || weight <= 0 || order <= 0 || status === "") {
                showMessageModal("Missing Required Fields", "Please complete all required criteria fields.", "error");
                return;
            }

            const totalWithoutCurrent = getTotalWeight(mode === "edit" ? criteriaId : null);
            const newTotal = totalWithoutCurrent + weight;

            if (newTotal > 100) {
                showMessageModal("Invalid Total Weight", "Total criteria weight cannot exceed 100%. Current total after saving would be " + newTotal + "%.", "error");
                return;
            }

            if (mode === "add") {
                criteriaRecords.push({
                    id: nextCriteriaId++,
                    order: order,
                    name: name,
                    description: description,
                    weight: weight,
                    status: status,
                    questions: []
                });
            } else {
                const criteria = findCriteria(criteriaId);

                if (!criteria) {
                    showMessageModal("Record Not Found", "The selected criteria was not found.", "error");
                    return;
                }

                criteria.name = name;
                criteria.description = description;
                criteria.weight = weight;
                criteria.order = order;
                criteria.status = status;
            }

            closeModal();
            renderAll();
        }

        function deleteCriteria(criteriaId) {
            const criteria = findCriteria(criteriaId);

            if (!criteria) {
                showMessageModal("Record Not Found", "The selected criteria was not found.", "error");
                return;
            }

            if (!confirm("Delete this criteria and all of its questions?")) {
                return;
            }

            criteriaRecords = criteriaRecords.filter(function (item) {
                return Number(item.id) !== Number(criteriaId);
            });

            renderAll();
        }

        function openQuestionModal(mode, criteriaId, questionId = null) {
            const criteria = findCriteria(criteriaId);

            if (!criteria) {
                showMessageModal("Record Not Found", "The selected criteria was not found.", "error");
                return;
            }

            if (mode === "add" && criteria.questions.length >= 10) {
                showMessageModal("Question Limit Reached", "Each criteria can have a maximum of 10 questions only.", "error");
                return;
            }

            const question = questionId ? findQuestion(criteria, questionId) : null;

            if (mode === "edit" && !question) {
                showMessageModal("Record Not Found", "The selected question was not found.", "error");
                return;
            }

            const title = mode === "add" ? "Add Question" : "Edit Question";
            const buttonText = mode === "add" ? "Add Question" : "Update Question";

            const body = `
                <form id="questionForm" onsubmit="saveQuestionForm(event, '${mode}', ${criteriaId}, ${questionId === null ? "null" : questionId})">
                    <p class="criteria-label">Criteria: <strong>${escapeHTML(criteria.name)}</strong></p>

                    <label>Question Text <span>*</span></label>
                    <textarea id="questionTextInput" placeholder="e.g., The instructor explains the lessons clearly." required>${question ? escapeHTML(question.text) : ""}</textarea>
                </form>
            `;

            const footer = `
                <button class="secondary-button" type="button" onclick="closeModal()">Cancel</button>
                <button class="dark-button" type="submit" form="questionForm">${buttonText}</button>
            `;

            openModal(modalShell(title, body, footer, "question-form-modal"));
        }

        function saveQuestionForm(event, mode, criteriaId, questionId) {
            event.preventDefault();

            const criteria = findCriteria(criteriaId);
            const text = document.getElementById("questionTextInput").value.trim();

            if (!criteria) {
                showMessageModal("Record Not Found", "The selected criteria was not found.", "error");
                return;
            }

            if (text === "") {
                showMessageModal("Missing Question", "Please enter the question text.", "error");
                return;
            }

            if (mode === "add") {
                if (criteria.questions.length >= 10) {
                    showMessageModal("Question Limit Reached", "Each criteria can have a maximum of 10 questions only.", "error");
                    return;
                }

                const shownCount = getShownQuestions(criteria).length;

                criteria.questions.push({
                    id: nextQuestionId++,
                    text: text,
                    shown: shownCount < displayCount
                });
            } else {
                const question = findQuestion(criteria, questionId);

                if (!question) {
                    showMessageModal("Record Not Found", "The selected question was not found.", "error");
                    return;
                }

                question.text = text;
            }

            closeModal();
            renderAll();
        }

        function deleteQuestion(criteriaId, questionId) {
            const criteria = findCriteria(criteriaId);

            if (!criteria) {
                showMessageModal("Record Not Found", "The selected criteria was not found.", "error");
                return;
            }

            if (criteria.questions.length <= 5) {
                showMessageModal("Minimum Question Required", "Each criteria must keep at least 5 questions.", "error");
                return;
            }

            if (!confirm("Delete this question?")) {
                return;
            }

            criteria.questions = criteria.questions.filter(function (question) {
                return Number(question.id) !== Number(questionId);
            });

            balanceShownQuestions(criteria);
            renderAll();
        }

        function toggleQuestionShown(criteriaId, questionId, checkbox) {
            const criteria = findCriteria(criteriaId);
            const question = criteria ? findQuestion(criteria, questionId) : null;

            if (!criteria || !question) {
                checkbox.checked = !checkbox.checked;
                return;
            }

            const shownCount = getShownQuestions(criteria).length;

            if (checkbox.checked && shownCount >= displayCount) {
                checkbox.checked = false;
                showMessageModal("Display Limit Reached", "Only " + displayCount + " questions can be shown for this criteria.", "error");
                return;
            }

            if (!checkbox.checked && shownCount <= displayCount) {
                checkbox.checked = true;
                showMessageModal("Minimum Display Required", "You must show exactly " + displayCount + " questions for this criteria.", "error");
                return;
            }

            question.shown = checkbox.checked;
            renderQuestionSections();
        }

        function balanceShownQuestions(criteria) {
            criteria.questions.forEach(function (question, index) {
                question.shown = index < displayCount;
            });
        }

        displayCountSelect.addEventListener("change", function () {
            displayCount = Number(this.value);

            criteriaRecords.forEach(function (criteria) {
                balanceShownQuestions(criteria);
            });

            renderAll();
        });

        function validateSetup() {
            if (criteriaRecords.length === 0) {
                return "Please add at least one evaluation criteria.";
            }

            const totalWeight = getTotalWeight();

            if (totalWeight !== 100) {
                return "Total criteria weight must be exactly 100%. Current total is " + totalWeight + "%.";
            }

            for (const criteria of criteriaRecords) {
                if (criteria.questions.length < 5) {
                    return criteria.name + " must have at least 5 questions.";
                }

                if (criteria.questions.length > 10) {
                    return criteria.name + " must not exceed 10 questions.";
                }

                const shownCount = getShownQuestions(criteria).length;

                if (shownCount !== displayCount) {
                    return criteria.name + " must have exactly " + displayCount + " shown questions.";
                }
            }

            return "";
        }

        function openPreviewModal() {
            const semester = document.getElementById("semesterSelect").value;
            const academicYear = document.getElementById("academicYearInput").value.trim();
            const criteria = getSortedCriteria().filter(function (item) {
                return item.status === "Active";
            });

            if (criteria.length === 0) {
                showMessageModal("No Active Criteria", "Please keep at least one active criteria before previewing the form.", "error");
                return;
            }

            const criteriaTables = criteria.map(function (item) {
                const shownQuestions = getShownQuestions(item);

                const rows = shownQuestions.map(function (question, index) {
                    return `
                        <tr>
                            <td>${index + 1}. ${escapeHTML(question.text)}</td>
                            <td><span class="preview-check"></span></td>
                            <td><span class="preview-check"></span></td>
                            <td><span class="preview-check"></span></td>
                            <td><span class="preview-check"></span></td>
                            <td><span class="preview-check"></span></td>
                        </tr>
                    `;
                }).join("");

                return `
                    <div class="preview-criteria-block">
                        <h3>${escapeHTML(item.name)}</h3>
                        <table class="preview-table">
                            <thead>
                                <tr>
                                    <th>Statement</th>
                                    <th>1</th>
                                    <th>2</th>
                                    <th>3</th>
                                    <th>4</th>
                                    <th>5</th>
                                </tr>
                            </thead>
                            <tbody>${rows}</tbody>
                        </table>
                    </div>
                `;
            }).join("");

            const body = `
                <div class="preview-intro">
                    <p>Please rate your instructor honestly. Your responses are anonymous and will help improve teaching quality.</p>
                </div>

                <div class="scale-line">
                    <span>1 = Strongly Disagree</span>
                    <span>2 = Disagree</span>
                    <span>3 = Neutral</span>
                    <span>4 = Agree</span>
                    <span>5 = Strongly Agree</span>
                </div>

                ${criteriaTables}
            `;

            const footer = `
                <button class="secondary-button" type="button" onclick="closeModal()">Close Preview</button>
            `;

            const header = `
                <div>
                    <h2>Student Evaluation Form — Preview</h2>
                    <p>${escapeHTML(semester)} • A.Y. ${escapeHTML(academicYear)}</p>
                </div>
                <button class="modal-close" type="button" onclick="closeModal()">${iconSvg.close}</button>
            `;

            openModal(`
                <div class="modal-overlay" onclick="closeModalOnBackdrop(event)">
                    <div class="modal-box preview-modal">
                        <div class="modal-header preview-header">
                            ${header}
                        </div>
                        <div class="modal-body preview-body">
                            ${body}
                        </div>
                        <div class="modal-footer">
                            ${footer}
                        </div>
                    </div>
                </div>
            `);
        }

        function saveSetup() {
            const validationError = validateSetup();

            if (validationError !== "") {
                showMessageModal("Setup Validation Failed", validationError, "error");
                return;
            }

            const semester = document.getElementById("semesterSelect").value;
            const academicYear = document.getElementById("academicYearInput").value.trim();
            const totalQuestions = criteriaRecords.reduce(function (total, criteria) {
                return total + criteria.questions.length;
            }, 0);

            openModal(`
                <div class="modal-overlay" onclick="closeModalOnBackdrop(event)">
                    <div class="modal-box message-box success-save-box">
                        <div class="success-icon">
                            ${iconSvg.check}
                        </div>
                        <h2>Setup Saved Successfully</h2>
                        <p>${criteriaRecords.length} criteria, ${totalQuestions} questions, ${displayCount} questions displayed per criteria, rating scale 1 to 5, schedule ${escapeHTML(semester)} A.Y. ${escapeHTML(academicYear)}.</p>
                        <button class="dark-button full-button" type="button" onclick="closeModal()">Done</button>
                    </div>
                </div>
            `);
        }

        function buildOptions(records, placeholder, selectedValue = "") {
            return `
                <option value="">${escapeHTML(placeholder)}</option>
                ${records.map(function (record) {
                    const value = record.id || record;
                    const label = record.name || record.id || record;

                    return `<option value="${escapeHTML(value)}" ${String(value) === String(selectedValue) ? "selected" : ""}>${escapeHTML(label)}</option>`;
                }).join("")}
            `;
        }

        function openScopeModal(actionType) {
            const isOpen = actionType === "open";
            const title = isOpen ? "Open Evaluation" : "Close Evaluation";
            const confirmText = isOpen ? "Confirm Open" : "Confirm Close";
            const confirmClass = isOpen ? "green-button" : "red-button";

            const body = `
                <form id="scopeForm" onsubmit="confirmScopeAction(event, '${actionType}')">
                    <p class="scope-help">
                        Select the scope for ${isOpen ? "opening" : "closing"} the evaluation. If "Select All" is chosen, the action applies to all departments, courses, year levels, and sections.
                    </p>

                    <label class="scope-main-label">Apply to</label>

                    <div class="scope-options">
                        <label class="scope-option">
                            <input type="radio" name="scopeType" value="all" checked>
                            <span>Select All</span>
                        </label>

                        <label class="scope-option">
                            <input type="radio" name="scopeType" value="department">
                            <span>Department</span>
                        </label>

                        <label class="scope-option">
                            <input type="radio" name="scopeType" value="course">
                            <span>Course</span>
                        </label>

                        <label class="scope-option">
                            <input type="radio" name="scopeType" value="year">
                            <span>Year Level</span>
                        </label>

                        <label class="scope-option">
                            <input type="radio" name="scopeType" value="section">
                            <span>Section</span>
                        </label>
                    </div>

                    <div id="scopeFields"></div>

                    <div class="scope-summary" id="scopeSummary">
                        <strong>Summary:</strong> All Departments, Courses, Year Levels, and Sections
                    </div>
                </form>
            `;

            const footer = `
                <button class="secondary-button" type="button" onclick="closeModal()">Cancel</button>
                <button class="${confirmClass}" type="submit" form="scopeForm">${confirmText}</button>
            `;

            openModal(modalShell(title, body, footer, "scope-modal"));

            document.querySelectorAll("input[name='scopeType']").forEach(function (radio) {
                radio.addEventListener("change", renderScopeFields);
            });

            renderScopeFields();
        }

        function renderScopeFields() {
            const type = document.querySelector("input[name='scopeType']:checked").value;
            const fields = document.getElementById("scopeFields");

            if (type === "all") {
                fields.innerHTML = "";
                updateScopeSummary();
                return;
            }

            if (type === "department") {
                fields.innerHTML = `
                    <label>Department</label>
                    <select id="scopeDepartment" onchange="updateScopeSummary()">
                        ${buildOptions(departments, "Select Department")}
                    </select>
                `;
            }

            if (type === "course") {
                fields.innerHTML = `
                    <label>Department (optional filter)</label>
                    <select id="scopeDepartment" onchange="updateCourseOptions(); updateScopeSummary()">
                        <option value="">All Departments</option>
                        ${departments.map(function (department) {
                            return `<option value="${escapeHTML(department.id)}">${escapeHTML(department.name)}</option>`;
                        }).join("")}
                    </select>

                    <label>Course</label>
                    <select id="scopeCourse" onchange="updateScopeSummary()">
                        ${buildOptions(courses, "Select Course")}
                    </select>
                `;
            }

            if (type === "year") {
                fields.innerHTML = `
                    <label>Department (optional filter)</label>
                    <select id="scopeDepartment" onchange="updateCourseOptions(); updateScopeSummary()">
                        <option value="">All Departments</option>
                        ${departments.map(function (department) {
                            return `<option value="${escapeHTML(department.id)}">${escapeHTML(department.name)}</option>`;
                        }).join("")}
                    </select>

                    <label>Course (optional filter)</label>
                    <select id="scopeCourse" onchange="updateScopeSummary()">
                        <option value="">All Courses</option>
                        ${courses.map(function (course) {
                            return `<option value="${escapeHTML(course.id)}" data-department-id="${escapeHTML(course.departmentId)}">${escapeHTML(course.id)}</option>`;
                        }).join("")}
                    </select>

                    <label>Year Level</label>
                    <select id="scopeYearLevel" onchange="updateScopeSummary()">
                        ${buildOptions(yearLevels, "Select Year Level")}
                    </select>
                `;
            }

            if (type === "section") {
                fields.innerHTML = `
                    <div class="two-column">
                        <div>
                            <label>Course (filter)</label>
                            <select id="scopeCourse" onchange="updateSectionOptions(); updateScopeSummary()">
                                <option value="">All Courses</option>
                                ${courses.map(function (course) {
                                    return `<option value="${escapeHTML(course.id)}">${escapeHTML(course.id)}</option>`;
                                }).join("")}
                            </select>
                        </div>

                        <div>
                            <label>Year Level (filter)</label>
                            <select id="scopeYearLevel" onchange="updateSectionOptions(); updateScopeSummary()">
                                <option value="">All Year Levels</option>
                                ${yearLevels.map(function (yearLevel) {
                                    return `<option value="${escapeHTML(yearLevel)}">${escapeHTML(yearLevel)}</option>`;
                                }).join("")}
                            </select>
                        </div>
                    </div>

                    <label>Section</label>
                    <select id="scopeSection" onchange="updateScopeSummary()">
                        ${buildOptions(sections, "Select Section")}
                    </select>
                `;
            }

            updateScopeSummary();
        }

        function updateCourseOptions() {
            const departmentSelect = document.getElementById("scopeDepartment");
            const courseSelect = document.getElementById("scopeCourse");

            if (!departmentSelect || !courseSelect) {
                return;
            }

            const departmentId = departmentSelect.value;

            courseSelect.querySelectorAll("option").forEach(function (option) {
                if (option.value === "") {
                    option.hidden = false;
                    return;
                }

                const course = courses.find(function (item) {
                    return item.id === option.value;
                });

                option.hidden = departmentId !== "" && course && course.departmentId !== departmentId;
            });

            if (courseSelect.options[courseSelect.selectedIndex] && courseSelect.options[courseSelect.selectedIndex].hidden) {
                courseSelect.value = "";
            }
        }

        function updateSectionOptions() {
            const courseSelect = document.getElementById("scopeCourse");
            const yearSelect = document.getElementById("scopeYearLevel");
            const sectionSelect = document.getElementById("scopeSection");

            if (!courseSelect || !yearSelect || !sectionSelect) {
                return;
            }

            const courseId = courseSelect.value;
            const yearLevel = yearSelect.value;

            sectionSelect.querySelectorAll("option").forEach(function (option) {
                if (option.value === "") {
                    option.hidden = false;
                    return;
                }

                const section = sections.find(function (item) {
                    return item.id === option.value;
                });

                const showCourse = courseId === "" || section.courseId === courseId;
                const showYear = yearLevel === "" || section.yearLevel === yearLevel;

                option.hidden = !(showCourse && showYear);
            });

            if (sectionSelect.options[sectionSelect.selectedIndex] && sectionSelect.options[sectionSelect.selectedIndex].hidden) {
                sectionSelect.value = "";
            }
        }

        function getSelectedText(id) {
            const select = document.getElementById(id);

            if (!select || select.value === "") {
                return "";
            }

            return select.options[select.selectedIndex].text;
        }

        function updateScopeSummary() {
            const summary = document.getElementById("scopeSummary");

            if (!summary) {
                return;
            }

            const type = document.querySelector("input[name='scopeType']:checked").value;
            let text = "All Departments, Courses, Year Levels, and Sections";

            if (type === "department") {
                text = getSelectedText("scopeDepartment") || "Please complete the selection above.";
            }

            if (type === "course") {
                const department = getSelectedText("scopeDepartment");
                const course = getSelectedText("scopeCourse");

                text = course ? ((department ? department + " • " : "") + course) : "Please complete the selection above.";
            }

            if (type === "year") {
                const course = getSelectedText("scopeCourse");
                const year = getSelectedText("scopeYearLevel");

                text = year ? ((course ? course + " • " : "") + year) : "Please complete the selection above.";
            }

            if (type === "section") {
                const section = getSelectedText("scopeSection");
                text = section || "Please complete the selection above.";
            }

            summary.innerHTML = "<strong>Summary:</strong> " + escapeHTML(text);
        }

        function collectScopeSummary() {
            const type = document.querySelector("input[name='scopeType']:checked").value;

            if (type === "all") {
                return "All Departments, Courses, Year Levels, and Sections";
            }

            if (type === "department") {
                return getSelectedText("scopeDepartment");
            }

            if (type === "course") {
                const department = getSelectedText("scopeDepartment");
                const course = getSelectedText("scopeCourse");

                return course ? ((department ? department + " • " : "") + course) : "";
            }

            if (type === "year") {
                const course = getSelectedText("scopeCourse");
                const year = getSelectedText("scopeYearLevel");

                return year ? ((course ? course + " • " : "") + year) : "";
            }

            if (type === "section") {
                return getSelectedText("scopeSection");
            }

            return "";
        }

        function confirmScopeAction(event, actionType) {
            event.preventDefault();

            const validationError = validateSetup();

            if (validationError !== "") {
                showMessageModal("Setup Validation Failed", validationError, "error");
                return;
            }

            const scopeSummary = collectScopeSummary();

            if (scopeSummary === "") {
                showMessageModal("Scope Required", "Please complete the required scope selection before confirming.", "error");
                return;
            }

            if (actionType === "open") {
                evaluationStatus = "Open";
                currentActiveScope = scopeSummary;
                closeModal();
                renderAll();
                showMessageModal("Evaluation Opened", "Evaluation is now open for: " + scopeSummary + ".", "success");
            } else {
                evaluationStatus = "Closed";
                currentActiveScope = "No active scope";
                closeModal();
                renderAll();
                showMessageModal("Evaluation Closed", "Evaluation was closed for: " + scopeSummary + ".", "success");
            }
        }

        renderAll();
    </script>
</body>
</html>