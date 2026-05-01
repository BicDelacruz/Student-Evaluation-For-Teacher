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
    strtolower((string) $_SESSION["authenticated_role"]) !== "faculty"
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
        "book" => '<svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"/></svg>',
        "chart" => '<svg viewBox="0 0 24 24"><path d="M4 19V5"/><path d="M4 19h16"/><path d="M8 16v-5"/><path d="M12 16V8"/><path d="M16 16v-7"/></svg>',
        "list" => '<svg viewBox="0 0 24 24"><path d="M8 6h13"/><path d="M8 12h13"/><path d="M8 18h13"/><path d="M3 6h.01"/><path d="M3 12h.01"/><path d="M3 18h.01"/></svg>',
        "comment" => '<svg viewBox="0 0 24 24"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/></svg>',
        "users" => '<svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9"/><path d="M16 3.1a4 4 0 0 1 0 7.8"/></svg>',
        "file" => '<svg viewBox="0 0 24 24"><path d="M7 3h7l5 5v13H7z"/><path d="M14 3v5h5"/></svg>',
        "logout" => '<svg viewBox="0 0 24 24"><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/><path d="M21 3v18"/></svg>',
        "bell" => '<svg viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 7h18s-3 0-3-7"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>',
        "refresh" => '<svg viewBox="0 0 24 24"><path d="M21 12a9 9 0 0 1-15.5 6.3"/><path d="M3 12A9 9 0 0 1 18.5 5.7"/><path d="M18 2v4h-4"/><path d="M6 22v-4h4"/></svg>',
        "check" => '<svg viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>',
        "trend" => '<svg viewBox="0 0 24 24"><path d="M3 17l6-6 4 4 8-8"/><path d="M14 7h7v7"/></svg>'
    ];

    return $icons[$name] ?? "";
}

$faculty = null;
$current_period = null;
$assigned_subjects = [];

try {
    $faculty_query = "
        SELECT
            f.faculty_id,
            f.faculty_number,
            f.full_name,
            d.department_name,
            d.department_code
        FROM faculty f
        INNER JOIN department d ON d.department_id = f.department_id
        WHERE f.user_id = :user_id
        LIMIT 1
    ";

    $faculty_statement = $pdo->prepare($faculty_query);
    $faculty_statement->execute(["user_id" => $user_id]);
    $faculty = $faculty_statement->fetch();

    if (!$faculty) {
        throw new RuntimeException("Faculty profile was not found.");
    }

    $period_query = "
        SELECT
            ep.evaluation_period_id,
            t.term_id,
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
        $assigned_query = "
            SELECT
                ta.teaching_assignment_id,
                sub.subject_code,
                sub.subject_title,
                sec.section_name,
                t.term_name,
                COUNT(DISTINCT sse.student_id) AS student_count,
                fer.overall_average_score,
                fer.submitted_response_count,
                fer.eligible_student_count
            FROM teaching_assignment ta
            INNER JOIN section_subject_offering sso ON sso.section_subject_offering_id = ta.section_subject_offering_id
            INNER JOIN subject sub ON sub.subject_id = sso.subject_id
            INNER JOIN section sec ON sec.section_id = sso.section_id
            INNER JOIN term t ON t.term_id = ta.term_id
            LEFT JOIN student_section_enrollment sse
                ON sse.section_id = sec.section_id
                AND sse.term_id = ta.term_id
                AND sse.enrollment_status = 'Active'
            LEFT JOIN faculty_evaluation_result fer
                ON fer.teaching_assignment_id = ta.teaching_assignment_id
                AND fer.evaluation_period_id = :evaluation_period_id
                AND fer.result_status = 'Released'
            WHERE ta.faculty_id = :faculty_id
            AND ta.assignment_status = 'Active'
            GROUP BY
                ta.teaching_assignment_id,
                sub.subject_code,
                sub.subject_title,
                sec.section_name,
                t.term_name,
                fer.overall_average_score,
                fer.submitted_response_count,
                fer.eligible_student_count
            ORDER BY sub.subject_code, sec.section_name
        ";

        $assigned_statement = $pdo->prepare($assigned_query);
        $assigned_statement->execute([
            "evaluation_period_id" => $current_period["evaluation_period_id"],
            "faculty_id" => $faculty["faculty_id"]
        ]);

        $assigned_subjects = $assigned_statement->fetchAll();
    }
} catch (Throwable $error) {
    $assigned_subjects = [];
}

$assigned_count = count($assigned_subjects);
$released_count = 0;
$average_sum = 0;
$average_count = 0;
$participation_sum = 0;
$participation_count = 0;

foreach ($assigned_subjects as $subject) {
    if ($subject["overall_average_score"] !== null) {
        $released_count++;
        $average_sum += (float) $subject["overall_average_score"];
        $average_count++;

        if ((int) $subject["eligible_student_count"] > 0) {
            $participation_sum += ((int) $subject["submitted_response_count"] / (int) $subject["eligible_student_count"]) * 100;
            $participation_count++;
        }
    }
}

$overall_average = $average_count > 0 ? number_format($average_sum / $average_count, 2) : "0.00";
$participation_rate = $participation_count > 0 ? number_format($participation_sum / $participation_count, 1) : "0.0";

$term_label = $current_period
    ? $current_period["term_name"] . " · " . $current_period["academic_year_name"]
    : "No active term";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard</title>
    <link rel="stylesheet" href="faculty-dashboard.css">
</head>
<body>
    <aside class="sidebar">
        <div class="brand">
            <img src="../img/eastgate_college_logo.png" alt="Eastgate College Logo">
            <h1>Student Evaluation for Teacher</h1>
            <p>Faculty Portal</p>
        </div>

        <nav class="nav-menu">
            <a class="nav-link active" href="faculty_dashboard.php"><?php echo icon_svg("dashboard"); ?> Dashboard</a>
            <a class="nav-link" href="#"><?php echo icon_svg("book"); ?> My Subjects</a>
            <a class="nav-link" href="#"><?php echo icon_svg("chart"); ?> Evaluation Results</a>
            <a class="nav-link" href="#"><?php echo icon_svg("list"); ?> Criteria Scores</a>
            <a class="nav-link" href="#"><?php echo icon_svg("comment"); ?> Comments</a>
            <a class="nav-link" href="#"><?php echo icon_svg("users"); ?> Participation</a>
            <a class="nav-link" href="#"><?php echo icon_svg("file"); ?> Reports</a>
        </nav>

        <div class="sidebar-bottom">
            <div class="department-box">
                <span>Department</span>
                <strong><?php echo e($faculty["department_name"] ?? ""); ?></strong>
            </div>

            <a class="logout-link" href="faculty_dashboard.php?logout=1"><?php echo icon_svg("logout"); ?> Logout</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h2>Welcome, Prof. <?php echo e($faculty["full_name"] ?? "Faculty"); ?></h2>
                <p><?php echo e($term_label); ?></p>
            </div>

            <div class="topbar-right">
                <span class="id-badge">Faculty ID: <strong><?php echo e($faculty["faculty_number"] ?? ""); ?></strong></span>
                <span class="bell"><?php echo icon_svg("bell"); ?></span>
            </div>
        </header>

        <section class="content-wrap">
            <div class="page-title-row">
                <div>
                    <h1>Dashboard</h1>
                    <p>Overview of your evaluation results and assigned subjects</p>
                </div>
                <a href="faculty_dashboard.php" class="refresh-btn"><?php echo icon_svg("refresh"); ?> Refresh</a>
            </div>

            <div class="stats-grid faculty-stats">
                <div class="stat-card">
                    <div>
                        <p>Assigned Subjects</p>
                        <strong><?php echo $assigned_count; ?></strong>
                    </div>
                    <span class="stat-icon dark"><?php echo icon_svg("book"); ?></span>
                </div>

                <div class="stat-card">
                    <div>
                        <p>Released Results</p>
                        <strong class="green-text"><?php echo $released_count; ?></strong>
                    </div>
                    <span class="stat-icon green"><?php echo icon_svg("check"); ?></span>
                </div>

                <div class="stat-card">
                    <div>
                        <p>Overall Average</p>
                        <strong class="yellow-text"><?php echo $overall_average; ?></strong>
                    </div>
                    <span class="stat-icon yellow"><?php echo icon_svg("trend"); ?></span>
                </div>

                <div class="stat-card">
                    <div>
                        <p>Participation</p>
                        <strong class="blue-text"><?php echo $participation_rate; ?>%</strong>
                    </div>
                    <span class="stat-icon blue"><?php echo icon_svg("users"); ?></span>
                </div>
            </div>

            <?php if ($released_count > 0): ?>
                <div class="alert alert-green">
                    <?php echo icon_svg("check"); ?>
                    <div>
                        <strong>Evaluation Results Released</strong>
                        <p>Your evaluation results for <?php echo e($term_label); ?> have been released. You can now view detailed feedback and scores.</p>
                    </div>
                </div>
            <?php endif; ?>

            <section class="panel">
                <div class="panel-header">
                    <div>
                        <h2>My Assigned Subjects</h2>
                        <p><?php echo e($term_label); ?></p>
                    </div>
                    <a href="#">View All</a>
                </div>

                <?php if (count($assigned_subjects) === 0): ?>
                    <div class="list-row">
                        <div>
                            <h3>No assigned subjects found</h3>
                            <p>No teaching assignment is available.</p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php foreach ($assigned_subjects as $subject): ?>
                    <div class="list-row">
                        <div>
                            <h3><?php echo e($subject["subject_code"]); ?> <span><?php echo e($subject["section_name"]); ?></span></h3>
                            <p><?php echo e($subject["subject_title"]); ?></p>
                            <small><?php echo (int) $subject["student_count"]; ?> students · <?php echo e($subject["term_name"]); ?></small>
                        </div>

                        <?php if ($subject["overall_average_score"] !== null): ?>
                            <div class="score-box">
                                <strong><?php echo number_format((float) $subject["overall_average_score"], 2); ?></strong>
                                <span><?php echo (int) $subject["submitted_response_count"]; ?>/<?php echo (int) $subject["eligible_student_count"]; ?> responses</span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </section>

            <div class="action-grid">
                <a href="#" class="action-btn"><?php echo icon_svg("book"); ?> View My Subjects</a>
                <a href="#" class="action-btn active"><?php echo icon_svg("chart"); ?> View Evaluation Results</a>
                <a href="#" class="action-btn"><?php echo icon_svg("comment"); ?> View Comments</a>
            </div>
        </section>
    </main>
</body>
</html>