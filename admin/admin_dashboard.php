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
        "students" => '<svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
        "faculty" => '<svg viewBox="0 0 24 24"><path d="M18 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9.5" cy="7" r="4"></circle><path d="M20 8v6"></path><path d="M23 11h-6"></path></svg>',
        "book" => '<svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"/></svg>',
        "settings" => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 0 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6V21a2 2 0 0 1-4 0v-.1a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1a2 2 0 0 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.6-1H3a2 2 0 0 1 0-4h.1a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9l-.1-.1A2 2 0 0 1 7.1 4l.1.1a1.7 1.7 0 0 0 1.9.3 1.7 1.7 0 0 0 1-1.6V3a2 2 0 0 1 4 0v.1a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1A2 2 0 0 1 20 7.1l-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.6 1h.1a2 2 0 0 1 0 4h-.1a1.7 1.7 0 0 0-1.6 1z"/></svg>',
        "chart" => '<svg viewBox="0 0 24 24"><path d="M3 3v18h18"></path><path d="M7 16V9"></path><path d="M12 16V5"></path><path d="M17 16v-3"></path></svg>',
        "megaphone" => '<svg viewBox="0 0 24 24"><path d="M3 11v2a2 2 0 0 0 2 2h2l5 4V5L7 9H5a2 2 0 0 0-2 2z"></path><path d="M16 9a5 5 0 0 1 0 6"></path></svg>',
        "moon" => '<svg viewBox="0 0 24 24"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"></path></svg>',
        "logout" => '<svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><path d="M16 17l5-5-5-5"></path><path d="M21 12H9"></path></svg>',
        "refresh" => '<svg viewBox="0 0 24 24"><path d="M21 12a9 9 0 0 1-15.5 6.3"/><path d="M3 12A9 9 0 0 1 18.5 5.7"/><path d="M18 2v4h-4"/><path d="M6 22v-4h4"/></svg>',
        "check" => '<svg viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>',
        "clock" => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
        "clipboard" => '<svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"></path><rect x="9" y="3" width="6" height="4" rx="1"></rect><path d="M9 12h6"></path><path d="M9 16h6"></path></svg>',
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
<body class="admin-dashboard-page">
    <aside class="sidebar">
        <div class="brand">
            <img src="../img/eastgate_college_logo.png" alt="Eastgate College Logo">
            <h1>Student Evaluation for Teacher</h1>
            <p>System Administrator</p>
        </div>

        <nav class="nav-menu">
            <a class="nav-link active" href="admin_dashboard.php"><?php echo icon_svg("dashboard"); ?> Dashboard</a>
            <a class="nav-link" href="student_management.php"><?php echo icon_svg("students"); ?> Student Management</a>
            <a class="nav-link" href="faculty_management.php"><?php echo icon_svg("faculty"); ?> Faculty Management</a>
            <a class="nav-link" href="academic_structure.php"><?php echo icon_svg("book"); ?> Academic Structure</a>
            <a class="nav-link" href="assignment_management.php"><?php echo icon_svg("faculty"); ?> Assignment Management</a>
            <a class="nav-link" href="evaluation_setup.php"><?php echo icon_svg("settings"); ?> Evaluation Setup</a>
            <a class="nav-link" href="submission_monitoring.php"><?php echo icon_svg("clipboard"); ?> Submission Monitoring</a>
            <a class="nav-link" href="report_page.php"><?php echo icon_svg("chart"); ?> Reports</a>
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

<?php
$logout_role_label = $_SESSION["role_name"] ?? "System Administrator";
?>

<style>
.admin_logout_overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.48);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 99999;
    padding: 24px;
}

.admin_logout_overlay.show {
    display: flex;
}

.admin_logout_modal {
    width: 100%;
    max-width: 610px;
    background: #ffffff;
    border-radius: 10px;
    padding: 36px 34px 32px;
    box-shadow: 0 24px 70px rgba(0, 0, 0, 0.32);
    text-align: center;
    font-family: Arial, Helvetica, sans-serif;
    animation: admin_logout_pop 0.18s ease;
}

@keyframes admin_logout_pop {
    from {
        opacity: 0;
        transform: scale(0.96) translateY(10px);
    }

    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.admin_logout_icon {
    width: 92px;
    height: 92px;
    border-radius: 999px;
    background: #fee2e2;
    color: #dc2626;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 22px;
}

.admin_logout_icon svg {
    width: 48px;
    height: 48px;
    stroke: currentColor;
    fill: none;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.admin_logout_modal h2 {
    font-size: 32px;
    line-height: 1.2;
    color: #172033;
    font-weight: 700;
    margin-bottom: 20px;
}

.admin_logout_modal p {
    font-size: 19px;
    line-height: 1.55;
    color: #4b5563;
    max-width: 500px;
    margin: 0 auto 28px;
}

.admin_logout_modal p strong {
    color: #374151;
    font-weight: 700;
}

.admin_logout_divider {
    height: 1px;
    background: #e5e7eb;
    margin: 0 0 28px;
}

.admin_logout_actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 22px;
}

.admin_logout_cancel,
.admin_logout_confirm {
    min-height: 56px;
    border-radius: 8px;
    font-size: 18px;
    font-weight: 700;
    cursor: pointer;
    font-family: Arial, Helvetica, sans-serif;
    transition: background 0.2s ease, color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
}

.admin_logout_cancel {
    background: #ffffff;
    color: #172033;
    border: 1px solid #d8dce2;
}

.admin_logout_cancel:hover {
    background: #f8fafc;
    border-color: #bfc7d4;
    box-shadow: 0 6px 16px rgba(15, 23, 42, 0.08);
    transform: translateY(-1px);
}

.admin_logout_confirm {
    background: #dc2626;
    color: #ffffff;
    border: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 11px;
}

.admin_logout_confirm:hover {
    background: #b91c1c;
    box-shadow: 0 10px 22px rgba(220, 38, 38, 0.24);
    transform: translateY(-1px);
}

.admin_logout_confirm svg {
    width: 24px;
    height: 24px;
    stroke: currentColor;
    fill: none;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.admin_logout_cancel:active,
.admin_logout_confirm:active {
    transform: scale(0.98);
}

body.admin_logout_locked {
    overflow: hidden;
}

@media (max-width: 640px) {
    .admin_logout_modal {
        padding: 30px 24px 26px;
    }

    .admin_logout_modal h2 {
        font-size: 26px;
    }

    .admin_logout_modal p {
        font-size: 16px;
    }

    .admin_logout_actions {
        grid-template-columns: 1fr;
        gap: 14px;
    }
}
</style>

<div class="admin_logout_overlay" id="adminLogoutOverlay" aria-hidden="true">
    <div class="admin_logout_modal" role="dialog" aria-modal="true" aria-labelledby="adminLogoutTitle">
        <div class="admin_logout_icon">
            <svg viewBox="0 0 24 24">
                <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                <line x1="12" y1="9" x2="12" y2="13"></line>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
        </div>

        <h2 id="adminLogoutTitle">Log Out Confirmation</h2>

        <p>
            Are you sure you want to log out of the
            <strong><?php echo htmlspecialchars($logout_role_label); ?></strong>
            account? You will need to sign in again to continue managing the
            <strong>Student Evaluation for Teacher System.</strong>
        </p>

        <div class="admin_logout_divider"></div>

        <div class="admin_logout_actions">
            <button type="button" class="admin_logout_cancel" id="adminLogoutCancel">Cancel</button>

            <button type="button" class="admin_logout_confirm" id="adminLogoutConfirm">
                <svg viewBox="0 0 24 24">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                Log Out
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    const overlay = document.getElementById("adminLogoutOverlay");
    const cancelBtn = document.getElementById("adminLogoutCancel");
    const confirmBtn = document.getElementById("adminLogoutConfirm");

    let logoutUrl = "../login/login_page.php";

    function openAdminLogoutModal(url) {
        logoutUrl = url || logoutUrl;
        overlay.classList.add("show");
        overlay.setAttribute("aria-hidden", "false");
        document.body.classList.add("admin_logout_locked");
    }

    function closeAdminLogoutModal() {
        overlay.classList.remove("show");
        overlay.setAttribute("aria-hidden", "true");
        document.body.classList.remove("admin_logout_locked");
    }

    document.querySelectorAll(".logout-link, a[href*='logout'], a[href*='login_page.php']").forEach(function (link) {
        const text = (link.textContent || "").trim().toLowerCase();

        if (link.classList.contains("logout-link") || text.includes("logout") || text.includes("log out")) {
            link.addEventListener("click", function (event) {
                event.preventDefault();
                openAdminLogoutModal(link.getAttribute("href"));
            });
        }
    });

    cancelBtn.addEventListener("click", closeAdminLogoutModal);

    overlay.addEventListener("click", function (event) {
        if (event.target === overlay) {
            closeAdminLogoutModal();
        }
    });

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape" && overlay.classList.contains("show")) {
            closeAdminLogoutModal();
        }
    });

    confirmBtn.addEventListener("click", function () {
        window.location.href = logoutUrl;
    });
})();
</script>

</body>
</html>