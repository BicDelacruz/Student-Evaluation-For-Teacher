<?php
declare(strict_types=1);

session_start();

require_once dirname(__DIR__) . "/database_connector.php";

if (isset($_GET["logout"])) {
    session_unset();
    session_destroy();
    header("Location: ../login/login_page.php");
    exit;
}

if (
    empty($_SESSION["authenticated_user_id"]) ||
    empty($_SESSION["authenticated_role"]) ||
    strtolower((string) $_SESSION["authenticated_role"]) !== "student"
) {
    header("Location: ../login/login_page.php");
    exit;
}

$user_id = (int) $_SESSION["authenticated_user_id"];

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
}

function icon_svg(string $name): string
{
    $icons = [
        "dashboard" => '<svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>',
        "file" => '<svg viewBox="0 0 24 24"><path d="M7 3h7l5 5v13H7z"/><path d="M14 3v5h5"/><path d="M9 13h6"/><path d="M9 17h6"/></svg>',
        "history" => '<svg viewBox="0 0 24 24"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/><path d="M12 7v6l4 2"/></svg>',
        "settings" => '<svg viewBox="0 0 24 24"><path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 0 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6V21a2 2 0 0 1-4 0v-.1a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1a2 2 0 0 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.6-1H3a2 2 0 0 1 0-4h.1a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9l-.1-.1A2 2 0 0 1 7.1 4l.1.1a1.7 1.7 0 0 0 1.9.3h.1a1.7 1.7 0 0 0 1-1.6V3a2 2 0 0 1 4 0v.1a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1A2 2 0 0 1 20 7.1l-.1.1a1.7 1.7 0 0 0-.3 1.9v.1a1.7 1.7 0 0 0 1.6 1h.1a2 2 0 0 1 0 4h-.1a1.7 1.7 0 0 0-1.6 1z"/></svg>',
        "moon" => '<svg viewBox="0 0 24 24"><path d="M21 14.5A8.5 8.5 0 1 1 9.5 3a7 7 0 0 0 11.5 11.5z"/></svg>',
        "logout" => '<svg viewBox="0 0 24 24"><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/><path d="M21 3v18"/></svg>',
        "bell" => '<svg viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 7h18s-3 0-3-7"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>',
        "refresh" => '<svg viewBox="0 0 24 24"><path d="M21 12a9 9 0 0 1-15.5 6.3"/><path d="M3 12A9 9 0 0 1 18.5 5.7"/><path d="M18 2v4h-4"/><path d="M6 22v-4h4"/></svg>',
        "check" => '<svg viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>',
        "clock" => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
        "lock" => '<svg viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>'
    ];

    return $icons[$name] ?? "";
}

$student = null;
$current_period = null;
$assigned_teachers = [];
$guideline_accepted = false;

try {
    $student_query = "
        SELECT
            s.student_id,
            s.student_number,
            s.full_name,
            s.current_year_level,
            c.course_code,
            sec.section_name
        FROM student s
        INNER JOIN course c ON c.course_id = s.course_id
        LEFT JOIN section sec ON sec.section_id = s.current_section_id
        WHERE s.user_id = :user_id
        LIMIT 1
    ";

    $student_statement = $pdo->prepare($student_query);
    $student_statement->execute(["user_id" => $user_id]);
    $student = $student_statement->fetch();

    if (!$student) {
        throw new RuntimeException("Student profile was not found.");
    }

    $period_query = "
        SELECT
            ep.evaluation_period_id,
            ep.period_status,
            t.term_name,
            ay.academic_year_name
        FROM evaluation_period ep
        INNER JOIN term t ON t.term_id = ep.term_id
        INNER JOIN academic_year ay ON ay.academic_year_id = t.academic_year_id
        ORDER BY
            FIELD(ep.period_status, 'Ongoing', 'Open', 'Draft', 'Closed', 'Archived'),
            ep.start_date DESC
        LIMIT 1
    ";

    $current_period = $pdo->query($period_query)->fetch();

    if ($current_period) {
        $acceptance_query = "
            SELECT COUNT(*)
            FROM guideline_acceptance
            WHERE student_id = :student_id
            AND evaluation_period_id = :evaluation_period_id
        ";

        $acceptance_statement = $pdo->prepare($acceptance_query);
        $acceptance_statement->execute([
            "student_id" => $student["student_id"],
            "evaluation_period_id" => $current_period["evaluation_period_id"]
        ]);

        $guideline_accepted = ((int) $acceptance_statement->fetchColumn()) > 0;

        $assigned_query = "
            SELECT
                setask.student_evaluation_task_id,
                setask.task_status,
                f.full_name AS faculty_name,
                sub.subject_title,
                d.department_name,
                er.average_score,
                er.submitted_at
            FROM student_evaluation_task setask
            INNER JOIN teaching_assignment ta ON ta.teaching_assignment_id = setask.teaching_assignment_id
            INNER JOIN faculty f ON f.faculty_id = ta.faculty_id
            INNER JOIN section_subject_offering sso ON sso.section_subject_offering_id = ta.section_subject_offering_id
            INNER JOIN subject sub ON sub.subject_id = sso.subject_id
            LEFT JOIN department d ON d.department_id = sub.department_id
            LEFT JOIN evaluation_response er ON er.student_evaluation_task_id = setask.student_evaluation_task_id
            WHERE setask.student_id = :student_id
            AND setask.evaluation_period_id = :evaluation_period_id
            ORDER BY f.full_name ASC
        ";

        $assigned_statement = $pdo->prepare($assigned_query);
        $assigned_statement->execute([
            "student_id" => $student["student_id"],
            "evaluation_period_id" => $current_period["evaluation_period_id"]
        ]);

        $assigned_teachers = $assigned_statement->fetchAll();
    }
} catch (Throwable $error) {
    $assigned_teachers = [];
}

$total_teachers = count($assigned_teachers);
$completed_count = 0;

foreach ($assigned_teachers as $teacher) {
    if ($teacher["task_status"] === "Submitted") {
        $completed_count++;
    }
}

$pending_count = max($total_teachers - $completed_count, 0);

$term_label = $current_period
    ? $current_period["term_name"] . " · " . $current_period["academic_year_name"]
    : "No active term";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="student-dashboard.css">
</head>
<body>
    <aside class="sidebar">
        <div class="brand">
            <img src="../img/eastgate_college_logo.png" alt="Eastgate College Logo">
            <h1>Student Evaluation for Teacher</h1>
            <p>Student Portal</p>
        </div>

        <nav class="nav-menu">
            <a class="nav-link active" href="student_dashboard.php"><?php echo icon_svg("dashboard"); ?> Dashboard</a>
            <a class="nav-link" href="#"><?php echo icon_svg("file"); ?> Guidelines</a>
            <a class="nav-link <?php echo $guideline_accepted ? "" : "disabled"; ?>" href="#"><?php echo icon_svg("file"); ?> Evaluation <?php echo !$guideline_accepted ? icon_svg("lock") : ""; ?></a>
            <a class="nav-link" href="#"><?php echo icon_svg("history"); ?> Submission History</a>
            <a class="nav-link" href="#"><?php echo icon_svg("settings"); ?> Settings</a>
            <a class="nav-link" href="#"><?php echo icon_svg("moon"); ?> Dark Mode</a>
        </nav>

        <div class="sidebar-bottom">
            <div class="progress-box">
                <div><span>Completed</span><strong><?php echo $completed_count; ?></strong></div>
                <div><span>Pending</span><strong class="warning"><?php echo $pending_count; ?></strong></div>
            </div>

            <a class="logout-link" href="student_dashboard.php?logout=1"><?php echo icon_svg("logout"); ?> Logout</a>

            <?php if ($pending_count > 0): ?>
                <p class="logout-note">Complete all evaluations first</p>
            <?php endif; ?>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h2>Welcome, <?php echo e($student["full_name"] ?? "Student"); ?></h2>
                <p><?php echo e($term_label); ?></p>
            </div>

            <div class="topbar-right">
                <span class="id-badge">Student ID: <strong><?php echo e($student["student_number"] ?? ""); ?></strong></span>
                <span class="bell"><?php echo icon_svg("bell"); ?></span>
            </div>
        </header>

        <section class="content-wrap">
            <div class="page-title-row">
                <div>
                    <h1>Dashboard</h1>
                    <p>Track your evaluation progress and upcoming deadlines</p>
                </div>
                <a href="student_dashboard.php" class="refresh-btn"><?php echo icon_svg("refresh"); ?> Refresh</a>
            </div>

            <div class="stats-grid student-stats">
                <div class="stat-card">
                    <div>
                        <p>Total Teachers</p>
                        <strong><?php echo $total_teachers; ?></strong>
                    </div>
                    <span class="stat-icon dark"><?php echo icon_svg("file"); ?></span>
                </div>

                <div class="stat-card">
                    <div>
                        <p>Completed</p>
                        <strong class="green-text"><?php echo $completed_count; ?></strong>
                    </div>
                    <span class="stat-icon green"><?php echo icon_svg("check"); ?></span>
                </div>

                <div class="stat-card">
                    <div>
                        <p>Pending</p>
                        <strong class="yellow-text"><?php echo $pending_count; ?></strong>
                    </div>
                    <span class="stat-icon yellow"><?php echo icon_svg("clock"); ?></span>
                </div>
            </div>

            <?php if (!$guideline_accepted): ?>
                <div class="alert alert-red">
                    <?php echo icon_svg("lock"); ?>
                    <div>
                        <strong>Evaluation Locked</strong>
                        <p>You must read and accept the evaluation guidelines before you can start evaluating teachers. Please visit the Guidelines page to proceed.</p>
                    </div>
                </div>
            <?php endif; ?>

            <section class="panel">
                <div class="panel-header">
                    <h2>Assigned Teachers</h2>
                </div>

                <div class="teacher-list">
                    <?php if (count($assigned_teachers) === 0): ?>
                        <div class="list-row">
                            <div>
                                <h3>No assigned teachers found</h3>
                                <p>No evaluation task is available for this period.</p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($assigned_teachers as $teacher): ?>
                        <div class="list-row">
                            <div>
                                <h3><?php echo e($teacher["faculty_name"]); ?></h3>
                                <p><?php echo e($teacher["subject_title"]); ?></p>
                                <small><?php echo e($teacher["department_name"] ?? ""); ?></small>
                            </div>

                            <span class="status-pill <?php echo $teacher["task_status"] === "Submitted" ? "completed" : "pending"; ?>">
                                <?php echo $teacher["task_status"] === "Submitted" ? "Completed" : "Pending"; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <div class="action-grid">
                <a href="#" class="action-btn disabled"><?php echo icon_svg("lock"); ?> Evaluation Locked</a>
                <a href="#" class="action-btn"><?php echo icon_svg("history"); ?> View Submission History</a>
                <a href="#" class="action-btn"><?php echo icon_svg("file"); ?> View Guidelines</a>
            </div>
        </section>
    </main>
</body>
</html>