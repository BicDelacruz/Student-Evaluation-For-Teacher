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
    strtolower((string) $_SESSION["authenticated_role"]) !== "admin"
) {
    header("Location: ../login/login_page.php");
    exit;
}

$user_id = (int) $_SESSION["authenticated_user_id"];

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
}

function count_query(PDO $pdo, string $sql, array $params = []): int
{
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    return (int) $statement->fetchColumn();
}

function icon_svg(string $name): string
{
    $icons = [
        "dashboard" => '<svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>',
        "students" => '<svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9"/></svg>',
        "faculty" => '<svg viewBox="0 0 24 24"><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4-4h4"/><path d="M17 11v6"/><path d="M14 14h6"/></svg>',
        "book" => '<svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"/></svg>',
        "settings" => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 0 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6V21a2 2 0 0 1-4 0v-.1a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1a2 2 0 0 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.6-1H3a2 2 0 0 1 0-4h.1a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9l-.1-.1A2 2 0 0 1 7.1 4l.1.1a1.7 1.7 0 0 0 1.9.3 1.7 1.7 0 0 0 1-1.6V3a2 2 0 0 1 4 0v.1a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1A2 2 0 0 1 20 7.1l-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.6 1h.1a2 2 0 0 1 0 4h-.1a1.7 1.7 0 0 0-1.6 1z"/></svg>',
        "chart" => '<svg viewBox="0 0 24 24"><path d="M4 19V5"/><path d="M4 19h16"/><path d="M8 16v-5"/><path d="M12 16V8"/><path d="M16 16v-7"/></svg>',
        "megaphone" => '<svg viewBox="0 0 24 24"><path d="M3 11v2a2 2 0 0 0 2 2h3l7 4V5L8 9H5a2 2 0 0 0-2 2z"/><path d="M19 9a4 4 0 0 1 0 6"/></svg>',
        "moon" => '<svg viewBox="0 0 24 24"><path d="M21 14.5A8.5 8.5 0 1 1 9.5 3a7 7 0 0 0 11.5 11.5z"/></svg>',
        "logout" => '<svg viewBox="0 0 24 24"><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/><path d="M21 3v18"/></svg>',
        "refresh" => '<svg viewBox="0 0 24 24"><path d="M21 12a9 9 0 0 1-15.5 6.3"/><path d="M3 12A9 9 0 0 1 18.5 5.7"/><path d="M18 2v4h-4"/><path d="M6 22v-4h4"/></svg>',
        "check" => '<svg viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>',
        "clock" => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
        "clipboard" => '<svg viewBox="0 0 24 24"><rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4a3 3 0 0 1 6 0"/><path d="M9 4h6"/><path d="M9 11h6"/><path d="M9 15h6"/></svg>',
        "trend" => '<svg viewBox="0 0 24 24"><path d="M3 17l6-6 4 4 8-8"/><path d="M14 7h7v7"/></svg>'
    ];

    return $icons[$name] ?? "";
}

$admin = null;
$current_period = null;

try {
    $admin_statement = $pdo->prepare("
        SELECT admin_id, admin_number, full_name
        FROM `admin`
        WHERE user_id = :user_id
        LIMIT 1
    ");
    $admin_statement->execute(["user_id" => $user_id]);
    $admin = $admin_statement->fetch();

    $current_period = $pdo->query("
        SELECT
            ep.evaluation_period_id,
            ep.period_name,
            ep.start_date,
            ep.end_date,
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
    ")->fetch();

    $total_students = count_query($pdo, "SELECT COUNT(*) FROM student WHERE student_status = 'Active'");
    $total_faculty = count_query($pdo, "SELECT COUNT(*) FROM faculty WHERE faculty_status = 'Active'");
    $total_subjects = count_query($pdo, "SELECT COUNT(*) FROM subject WHERE subject_status = 'Active'");
    $total_sections = count_query($pdo, "SELECT COUNT(*) FROM section WHERE section_status = 'Active'");

    if ($current_period) {
        $period_id = (int) $current_period["evaluation_period_id"];

        $total_tasks = count_query($pdo, "
            SELECT COUNT(*)
            FROM student_evaluation_task
            WHERE evaluation_period_id = :period_id
        ", ["period_id" => $period_id]);

        $submitted_tasks = count_query($pdo, "
            SELECT COUNT(*)
            FROM student_evaluation_task
            WHERE evaluation_period_id = :period_id
            AND task_status = 'Submitted'
        ", ["period_id" => $period_id]);

        $released_count = count_query($pdo, "
            SELECT COUNT(*)
            FROM faculty_evaluation_result
            WHERE evaluation_period_id = :period_id
            AND result_status = 'Released'
        ", ["period_id" => $period_id]);

        $assignment_count = count_query($pdo, "
            SELECT COUNT(DISTINCT teaching_assignment_id)
            FROM student_evaluation_task
            WHERE evaluation_period_id = :period_id
        ", ["period_id" => $period_id]);
    } else {
        $total_tasks = 0;
        $submitted_tasks = 0;
        $released_count = 0;
        $assignment_count = 0;
    }

    $pending_release_count = max($assignment_count - $released_count, 0);
    $participation_rate = $total_tasks > 0 ? number_format(($submitted_tasks / $total_tasks) * 100, 1) : "0.0";
} catch (Throwable $error) {
    $total_students = 0;
    $total_faculty = 0;
    $total_subjects = 0;
    $total_sections = 0;
    $total_tasks = 0;
    $submitted_tasks = 0;
    $released_count = 0;
    $pending_release_count = 0;
    $participation_rate = "0.0";
}

$schedule_status = $current_period ? $current_period["period_status"] : "No Schedule";
$schedule_term = $current_period ? $current_period["term_name"] . ", " . $current_period["academic_year_name"] : "No active schedule";
$start_date = $current_period ? date("n/j/Y", strtotime($current_period["start_date"])) : "N/A";
$end_date = $current_period ? date("n/j/Y", strtotime($current_period["end_date"])) : "N/A";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="admin-dashboard.css">
</head>
<body>
    <aside class="sidebar">
        <div class="brand">
            <img src="../img/eastgate_college_logo.png" alt="Eastgate College Logo">
            <h1>Student Evaluation for Teacher</h1>
            <p>System Administrator</p>
        </div>

        <nav class="nav-menu">
            <a class="nav-link active" href="admin_dashboard.php"><?php echo icon_svg("dashboard"); ?> Dashboard</a>
            <a class="nav-link" href="#"><?php echo icon_svg("students"); ?> Student Management</a>
            <a class="nav-link" href="#"><?php echo icon_svg("faculty"); ?> Faculty Management</a>
            <a class="nav-link" href="#"><?php echo icon_svg("book"); ?> Academic Structure</a>
            <a class="nav-link" href="#"><?php echo icon_svg("faculty"); ?> Assignment Management</a>
            <a class="nav-link" href="#"><?php echo icon_svg("settings"); ?> Evaluation Setup</a>
            <a class="nav-link" href="#"><?php echo icon_svg("clipboard"); ?> Submission Monitoring</a>
            <a class="nav-link" href="#"><?php echo icon_svg("chart"); ?> Reports</a>
            <a class="nav-link" href="#"><?php echo icon_svg("megaphone"); ?> Announcements</a>
            <a class="nav-link" href="#"><?php echo icon_svg("settings"); ?> Settings</a>
            <a class="nav-link" href="#"><?php echo icon_svg("moon"); ?> Dark Mode</a>
        </nav>

        <div class="sidebar-bottom">
            <a class="logout-link" href="admin_dashboard.php?logout=1"><?php echo icon_svg("logout"); ?> Logout</a>
        </div>
    </aside>

    <main class="main-content">
        <section class="content-wrap">
            <div class="page-title-row">
                <div>
                    <h1>Dashboard</h1>
                    <p>System overview and quick statistics</p>
                </div>
                <a href="admin_dashboard.php" class="refresh-btn"><?php echo icon_svg("refresh"); ?> Refresh</a>
            </div>

            <div class="stats-grid admin-stats">
                <div class="stat-card">
                    <div>
                        <p>Total Students</p>
                        <strong class="blue-text"><?php echo $total_students; ?></strong>
                        <small>Click to manage</small>
                    </div>
                    <span class="stat-icon blue"><?php echo icon_svg("students"); ?></span>
                </div>

                <div class="stat-card">
                    <div>
                        <p>Total Faculty</p>
                        <strong class="green-text"><?php echo $total_faculty; ?></strong>
                        <small>Click to manage</small>
                    </div>
                    <span class="stat-icon green"><?php echo icon_svg("faculty"); ?></span>
                </div>

                <div class="stat-card">
                    <div>
                        <p>Total Subjects</p>
                        <strong class="yellow-text"><?php echo $total_subjects; ?></strong>
                        <small><?php echo $total_sections; ?> sections total</small>
                    </div>
                    <span class="stat-icon yellow"><?php echo icon_svg("book"); ?></span>
                </div>

                <div class="stat-card">
                    <div>
                        <p>Participation Rate</p>
                        <strong class="purple-text"><?php echo $participation_rate; ?>%</strong>
                        <small><?php echo $submitted_tasks; ?> of <?php echo $total_tasks; ?> submitted</small>
                    </div>
                    <span class="stat-icon purple"><?php echo icon_svg("trend"); ?></span>
                </div>
            </div>

            <div class="middle-grid">
                <section class="panel">
                    <h2>Evaluation Status</h2>

                    <div class="status-box green">
                        <div>
                            <?php echo icon_svg("check"); ?>
                            <div>
                                <strong>Completed</strong>
                                <p>Released to faculty</p>
                            </div>
                        </div>
                        <strong><?php echo $released_count; ?></strong>
                    </div>

                    <div class="status-box red">
                        <div>
                            <?php echo icon_svg("clock"); ?>
                            <div>
                                <strong>Pending</strong>
                                <p>Not yet released</p>
                            </div>
                        </div>
                        <strong><?php echo $pending_release_count; ?></strong>
                    </div>
                </section>

                <section class="panel">
                    <h2>Current Schedule</h2>

                    <div class="schedule-box">
                        <strong><?php echo e($schedule_status); ?></strong>
                        <p><?php echo e($schedule_term); ?></p>

                        <div class="date-grid">
                            <div>
                                <span>Start Date</span>
                                <strong><?php echo e($start_date); ?></strong>
                            </div>
                            <div>
                                <span>End Date</span>
                                <strong><?php echo e($end_date); ?></strong>
                            </div>
                        </div>
                    </div>

                    <a href="#" class="manage-btn">Manage Evaluation Setup</a>
                </section>
            </div>

            <section class="panel quick-panel">
                <h2>Quick Actions</h2>

                <div class="quick-grid">
                    <a href="#"><?php echo icon_svg("students"); ?><span>View Students</span><small>Manage student accounts</small></a>
                    <a href="#"><?php echo icon_svg("faculty"); ?><span>View Faculty</span><small>Manage faculty accounts</small></a>
                    <a href="#"><?php echo icon_svg("check"); ?><span>View Pending</span><small>Monitor submissions</small></a>
                    <a href="#"><?php echo icon_svg("trend"); ?><span>View Reports</span><small>Generate analytics</small></a>
                </div>
            </section>

            <div class="system-info">
                <strong>System Information:</strong>
                You are logged in as System Administrator. You have full access to all system features and data.
            </div>
        </section>
    </main>
</body>
</html>