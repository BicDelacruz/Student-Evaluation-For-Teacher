<?php
declare(strict_types=1);

session_start();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$database_host = "localhost";
$database_name = "student_evaluation_for_teacher_db";
$database_username = "root";
$database_password = "";

try {
    $db = new mysqli($database_host, $database_username, $database_password, $database_name);
    $db->set_charset("utf8mb4");
} catch (Throwable $error) {
    die("Database connection failed. Please check database_connector or local database settings.");
}

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function json_safe($value): string
{
    return json_encode($value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
}

function db_table_exists(mysqli $db, string $table): bool
{
    $stmt = $db->prepare("SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
    $stmt->bind_param("s", $table);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return ((int)($row["c"] ?? 0)) > 0;
}

function db_column_exists(mysqli $db, string $table, string $column): bool
{
    $stmt = $db->prepare("SELECT COUNT(*) AS c FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
    $stmt->bind_param("ss", $table, $column);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return ((int)($row["c"] ?? 0)) > 0;
}

function fetch_all(mysqli $db, string $sql, string $types = "", array $params = []): array
{
    if ($params) {
        $stmt = $db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }
    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

function fetch_one(mysqli $db, string $sql, string $types = "", array $params = []): ?array
{
    $rows = fetch_all($db, $sql, $types, $params);
    return $rows[0] ?? null;
}

function execute_stmt(mysqli $db, string $sql, string $types = "", array $params = []): mysqli_stmt
{
    $stmt = $db->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt;
}

function set_flash(string $message, string $type = "success"): void
{
    $_SESSION["submission_monitoring_flash"] = ["message" => $message, "type" => $type];
}

function get_flash(): ?array
{
    if (!isset($_SESSION["submission_monitoring_flash"])) {
        return null;
    }
    $flash = $_SESSION["submission_monitoring_flash"];
    unset($_SESSION["submission_monitoring_flash"]);
    return $flash;
}

function current_admin_label(): string
{
    return $_SESSION["role_name"] ?? $_SESSION["authenticated_role"] ?? "System Administrator";
}

function year_level_label($value): string
{
    $n = (int)$value;
    return match ($n) {
        1 => "1st Year",
        2 => "2nd Year",
        3 => "3rd Year",
        4 => "4th Year",
        default => $n > 0 ? $n . "th Year" : "Not assigned",
    };
}

function term_label(?string $term): string
{
    return match ((string)$term) {
        "First Semester" => "1st Semester",
        "Second Semester" => "2nd Semester",
        "Summer" => "Summer",
        default => $term ?: "Not assigned",
    };
}

function format_date_only($value): string
{
    if (!$value) {
        return "—";
    }
    $time = strtotime((string)$value);
    return $time ? date("M d, Y", $time) : "—";
}

function format_datetime_display($value): string
{
    if (!$value) {
        return "—";
    }
    $time = strtotime((string)$value);
    return $time ? date("M d, Y, h:i A", $time) : "—";
}

function csv_escape($value): string
{
    $value = (string)$value;
    return '"' . str_replace('"', '""', $value) . '"';
}

function icon_svg($name): string
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
        "close" => '<svg viewBox="0 0 24 24"><path d="M18 6L6 18"></path><path d="M6 6l12 12"></path></svg>',
        "download" => '<svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><path d="M7 10l5 5 5-5"></path><path d="M12 15V3"></path></svg>',
        "x-circle" => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><path d="M15 9l-6 6"></path><path d="M9 9l6 6"></path></svg>',
        "alert" => '<svg viewBox="0 0 24 24"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>'
    ];
    return $icons[$name] ?? "";
}

function get_current_period(mysqli $db): ?array
{
    $sql = "
        SELECT
            ep.evaluation_period_id,
            ep.term_id,
            ep.period_name,
            ep.start_date,
            ep.end_date,
            ep.period_status,
            t.term_name,
            ay.academic_year_id,
            ay.academic_year_name
        FROM evaluation_period ep
        LEFT JOIN term t ON t.term_id = ep.term_id
        LEFT JOIN academic_year ay ON ay.academic_year_id = t.academic_year_id
        ORDER BY
            CASE ep.period_status
                WHEN 'Open' THEN 1
                WHEN 'Ongoing' THEN 2
                WHEN 'Draft' THEN 3
                WHEN 'Closed' THEN 4
                ELSE 5
            END,
            ep.evaluation_period_id DESC
        LIMIT 1
    ";
    return fetch_one($db, $sql);
}

function student_has_open_access(array $student, array $activeScopes): bool
{
    foreach ($activeScopes as $scope) {
        $scopeType = (string)($scope["scope_type"] ?? "");
        if ($scopeType === "All") {
            return true;
        }
        if ($scopeType === "Department" && (int)($scope["department_id"] ?? 0) === (int)$student["department_id"]) {
            return true;
        }
        if ($scopeType === "Course" && (int)($scope["course_id"] ?? 0) === (int)$student["course_id"]) {
            return true;
        }
        if ($scopeType === "Year Level" && (int)($scope["year_level"] ?? 0) === (int)$student["year_level_number"]) {
            return true;
        }
        if ($scopeType === "Section" && (int)($scope["section_id"] ?? 0) === (int)$student["section_id"]) {
            return true;
        }
    }
    return false;
}

function make_query_with_params(array $overrides = []): string
{
    $params = array_merge($_GET, $overrides);
    foreach ($params as $key => $value) {
        if ($value === "" || $value === null) {
            unset($params[$key]);
        }
    }
    return "?" . http_build_query($params);
}

function build_query_params(array $overrides = []): array
{
    $params = array_merge($_GET, $overrides);
    foreach ($params as $key => $value) {
        if ($value === "" || $value === null) {
            unset($params[$key]);
        }
    }
    return $params;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "delete_monitoring_record") {
        $studentId = (int)($_POST["student_id"] ?? 0);
        if ($studentId > 0) {
            try {
                $dependencyRow = fetch_one($db, "
                    SELECT
                        (SELECT COUNT(*) FROM student_evaluation_task WHERE student_id = ?) AS task_count,
                        (SELECT COUNT(*) FROM evaluation_response WHERE student_id = ?) AS response_count,
                        (SELECT COUNT(*) FROM student_section_enrollment WHERE student_id = ?) AS enrollment_count
                ", "iii", [$studentId, $studentId, $studentId]);
                $dependencyCount = (int)($dependencyRow["task_count"] ?? 0) + (int)($dependencyRow["response_count"] ?? 0) + (int)($dependencyRow["enrollment_count"] ?? 0);

                if ($dependencyCount > 0) {
                    execute_stmt($db, "UPDATE student SET student_status = 'Inactive' WHERE student_id = ?", "i", [$studentId])->close();
                    set_flash("You cannot delete an entity with record inside. This will mark as inactive", "warning");
                } else {
                    execute_stmt($db, "DELETE FROM student WHERE student_id = ?", "i", [$studentId])->close();
                    set_flash("Monitoring record deleted successfully.", "success");
                }
            } catch (Throwable $error) {
                error_log("Submission monitoring delete error: " . $error->getMessage());
                set_flash("Unable to update this monitoring record right now.", "error");
            }
        }
        header("Location: submission_monitoring.php" . make_query_with_params(["action" => null]));
        exit;
    }
}

$currentPeriod = get_current_period($db);
$currentPeriodId = (int)($currentPeriod["evaluation_period_id"] ?? 0);
$currentTermId = (int)($currentPeriod["term_id"] ?? 0);

$hasTaskTeachingAssignment = db_column_exists($db, "student_evaluation_task", "teaching_assignment_id");
$hasTaskIsActive = db_column_exists($db, "student_evaluation_task", "is_active");
$hasTaskAttemptNo = db_column_exists($db, "student_evaluation_task", "attempt_no");
$hasEvalPeriodScope = db_table_exists($db, "evaluation_period_scope");

$filters = [
    "search" => trim((string)($_GET["search"] ?? "")),
    "department_id" => (int)($_GET["department_id"] ?? 0),
    "course_id" => (int)($_GET["course_id"] ?? 0),
    "year_level" => (int)($_GET["year_level"] ?? 0),
    "section_id" => (int)($_GET["section_id"] ?? 0),
    "academic_year_id" => (int)($_GET["academic_year_id"] ?? 0),
    "semester" => trim((string)($_GET["semester"] ?? "")),
    "evaluation_access" => trim((string)($_GET["evaluation_access"] ?? "")),
    "status" => trim((string)($_GET["status"] ?? "")),
    "tab" => trim((string)($_GET["tab"] ?? "All")),
];
if (!in_array($filters["tab"], ["All", "Completed", "Pending"], true)) {
    $filters["tab"] = "All";
}

$departments = fetch_all($db, "SELECT department_id, department_name FROM department WHERE department_status = 'Active' ORDER BY department_name");
$courses = fetch_all($db, "SELECT course_id, course_code, course_name, department_id FROM course WHERE course_status = 'Active' ORDER BY course_code, course_name");
$sections = fetch_all($db, "SELECT section_id, section_name, course_id, year_level FROM section WHERE section_status = 'Active' ORDER BY section_name");
$academicYears = fetch_all($db, "SELECT academic_year_id, academic_year_name FROM academic_year ORDER BY academic_year_name DESC");
$semesters = fetch_all($db, "SELECT DISTINCT term_name FROM term ORDER BY FIELD(term_name, 'First Semester', 'Second Semester', 'Summer')");

$activeScopes = [];
if ($hasEvalPeriodScope) {
    $activeScopes = fetch_all($db, "
        SELECT eps.*
        FROM evaluation_period_scope eps
        INNER JOIN evaluation_period ep ON ep.evaluation_period_id = eps.evaluation_period_id
        WHERE eps.scope_status = 'Active'
          AND ep.period_status IN ('Open', 'Ongoing')
        ORDER BY eps.opened_at DESC, eps.evaluation_period_scope_id DESC
    ");
}

$studentRows = fetch_all($db, "
    SELECT
        s.student_id,
        s.student_number,
        s.full_name,
        s.first_name,
        s.middle_name,
        s.last_name,
        COALESCE(sec.section_id, s.current_section_id, 0) AS section_id,
        COALESCE(sec.section_name, 'Not assigned') AS section_name,
        COALESCE(sec.year_level, s.current_year_level, 0) AS year_level_number,
        COALESCE(active_course.course_id, base_course.course_id, 0) AS course_id,
        COALESCE(active_course.course_code, base_course.course_code, '') AS course_code,
        COALESCE(active_course.course_name, base_course.course_name, 'Not assigned') AS course_name,
        COALESCE(active_department.department_id, base_department.department_id, 0) AS department_id,
        COALESCE(active_department.department_name, base_department.department_name, 'Not assigned') AS department_name,
        COALESCE(active_term.term_id, sse.term_id, sec.term_id, 0) AS term_id,
        COALESCE(active_term.term_name, 'Not assigned') AS term_name,
        COALESCE(active_year.academic_year_id, s.academic_year_id, 0) AS academic_year_id,
        COALESCE(active_year.academic_year_name, 'Not assigned') AS academic_year_name
    FROM student s
    LEFT JOIN student_section_enrollment sse
        ON sse.student_section_enrollment_id = (
            SELECT sse2.student_section_enrollment_id
            FROM student_section_enrollment sse2
            WHERE sse2.student_id = s.student_id
              AND sse2.enrollment_status = 'Active'
            ORDER BY sse2.student_section_enrollment_id DESC
            LIMIT 1
        )
    LEFT JOIN section sec
        ON sec.section_id = COALESCE(sse.section_id, s.current_section_id)
    LEFT JOIN course base_course
        ON base_course.course_id = s.course_id
    LEFT JOIN course active_course
        ON active_course.course_id = COALESCE(sec.course_id, s.course_id)
    LEFT JOIN department base_department
        ON base_department.department_id = base_course.department_id
    LEFT JOIN department active_department
        ON active_department.department_id = active_course.department_id
    LEFT JOIN term active_term
        ON active_term.term_id = COALESCE(sse.term_id, sec.term_id)
    LEFT JOIN academic_year active_year
        ON active_year.academic_year_id = COALESCE(sec.academic_year_id, s.academic_year_id, active_term.academic_year_id)
    WHERE s.student_status = 'Active'
    ORDER BY s.last_name, s.first_name, s.student_number
");

$studentIds = array_map(static fn($row) => (int)$row["student_id"], $studentRows);
$taskStats = [];
$responseStats = [];
$evaluatedDetails = [];
$pendingAssignments = [];

if ($studentIds) {
    $idPlaceholders = implode(",", array_fill(0, count($studentIds), "?"));
    $idTypes = str_repeat("i", count($studentIds));

    if ($currentPeriodId > 0) {
        $activeTaskCondition = $hasTaskIsActive ? "AND setask.is_active = 1" : "";
        $taskRows = fetch_all($db, "
            SELECT
                setask.student_id,
                COUNT(DISTINCT setask.student_evaluation_task_id) AS task_count,
                COUNT(DISTINCT CASE WHEN setask.task_status = 'Submitted' THEN setask.student_evaluation_task_id END) AS submitted_task_count,
                COUNT(DISTINCT CASE WHEN setask.task_status IN ('Pending', 'Draft') THEN setask.student_evaluation_task_id END) AS unfinished_task_count,
                MAX(setask.submitted_at) AS latest_submitted_at
            FROM student_evaluation_task setask
            WHERE setask.evaluation_period_id = ?
              AND setask.task_status <> 'Reset'
              $activeTaskCondition
              AND setask.student_id IN ($idPlaceholders)
            GROUP BY setask.student_id
        ", "i" . $idTypes, array_merge([$currentPeriodId], $studentIds));
        foreach ($taskRows as $row) {
            $taskStats[(int)$row["student_id"]] = $row;
        }

        $responseRows = fetch_all($db, "
            SELECT
                er.student_id,
                COUNT(DISTINCT er.evaluation_response_id) AS response_count,
                AVG(er.average_score) AS average_rating,
                MAX(er.submitted_at) AS latest_response_submitted_at
            FROM evaluation_response er
            WHERE er.evaluation_period_id = ?
              AND er.response_status = 'Submitted'
              AND er.student_id IN ($idPlaceholders)
            GROUP BY er.student_id
        ", "i" . $idTypes, array_merge([$currentPeriodId], $studentIds));
        foreach ($responseRows as $row) {
            $responseStats[(int)$row["student_id"]] = $row;
        }

        $detailsRows = fetch_all($db, "
            SELECT
                er.student_id,
                er.evaluation_response_id,
                er.average_score,
                er.total_score,
                er.submitted_at,
                subj.subject_code,
                subj.subject_title,
                COALESCE(f.full_name, CONCAT_WS(' ', f.first_name, f.middle_name, f.last_name), 'Not assigned') AS faculty_name,
                COALESCE(erc.comment_text, '') AS comment_text
            FROM evaluation_response er
            INNER JOIN teaching_assignment ta
                ON ta.teaching_assignment_id = er.teaching_assignment_id
            INNER JOIN section_subject_offering sso
                ON sso.section_subject_offering_id = ta.section_subject_offering_id
            INNER JOIN subject subj
                ON subj.subject_id = sso.subject_id
            INNER JOIN faculty f
                ON f.faculty_id = ta.faculty_id
            LEFT JOIN evaluation_response_comment erc
                ON erc.evaluation_response_id = er.evaluation_response_id
            WHERE er.evaluation_period_id = ?
              AND er.response_status = 'Submitted'
              AND er.student_id IN ($idPlaceholders)
            ORDER BY er.student_id, subj.subject_code, subj.subject_title
        ", "i" . $idTypes, array_merge([$currentPeriodId], $studentIds));
        foreach ($detailsRows as $row) {
            $evaluatedDetails[(int)$row["student_id"]][] = $row;
        }

        $pendingJoinTask = "";
        $pendingSelectStatus = "'Pending' AS task_status";
        if ($hasTaskTeachingAssignment) {
            $pendingJoinTask = "
                LEFT JOIN student_evaluation_task setask
                    ON setask.student_id = s.student_id
                   AND setask.evaluation_period_id = ?
                   AND setask.teaching_assignment_id = ta.teaching_assignment_id
                   AND setask.task_status <> 'Reset'
            ";
            $pendingSelectStatus = "COALESCE(setask.task_status, 'Pending') AS task_status";
        }

        $pendingParams = $hasTaskTeachingAssignment ? array_merge([$currentTermId, $currentPeriodId], $studentIds) : array_merge([$currentTermId], $studentIds);
        $pendingTypes = ($hasTaskTeachingAssignment ? "ii" : "i") . $idTypes;
        $pendingRows = fetch_all($db, "
            SELECT DISTINCT
                s.student_id,
                ta.teaching_assignment_id,
                subj.subject_code,
                subj.subject_title,
                COALESCE(f.full_name, CONCAT_WS(' ', f.first_name, f.middle_name, f.last_name), 'Not assigned') AS faculty_name,
                $pendingSelectStatus
            FROM student s
            LEFT JOIN student_section_enrollment sse
                ON sse.student_section_enrollment_id = (
                    SELECT sse2.student_section_enrollment_id
                    FROM student_section_enrollment sse2
                    WHERE sse2.student_id = s.student_id
                      AND sse2.enrollment_status = 'Active'
                    ORDER BY sse2.student_section_enrollment_id DESC
                    LIMIT 1
                )
            INNER JOIN section sec
                ON sec.section_id = COALESCE(sse.section_id, s.current_section_id)
            INNER JOIN section_subject_offering sso
                ON sso.section_id = sec.section_id
               AND sso.offering_status = 'Active'
               AND sso.term_id = ?
            INNER JOIN subject subj
                ON subj.subject_id = sso.subject_id
               AND subj.subject_status = 'Active'
            INNER JOIN teaching_assignment ta
                ON ta.section_subject_offering_id = sso.section_subject_offering_id
               AND ta.assignment_status = 'Active'
            INNER JOIN faculty f
                ON f.faculty_id = ta.faculty_id
            $pendingJoinTask
            WHERE s.student_id IN ($idPlaceholders)
            ORDER BY s.student_id, subj.subject_code, subj.subject_title
        ", $pendingTypes, $pendingParams);
        foreach ($pendingRows as $row) {
            $pendingAssignments[(int)$row["student_id"]][] = $row;
        }
    }
}

$records = [];
foreach ($studentRows as $student) {
    $studentId = (int)$student["student_id"];
    $tasks = $taskStats[$studentId] ?? [
        "task_count" => 0,
        "submitted_task_count" => 0,
        "unfinished_task_count" => 0,
        "latest_submitted_at" => null,
    ];
    $responses = $responseStats[$studentId] ?? [
        "response_count" => 0,
        "average_rating" => null,
        "latest_response_submitted_at" => null,
    ];

    $taskCount = (int)($tasks["task_count"] ?? 0);
    $submittedTaskCount = (int)($tasks["submitted_task_count"] ?? 0);
    $responseCount = (int)($responses["response_count"] ?? 0);
    $isCompleted = $taskCount > 0 && $submittedTaskCount >= $taskCount;
    if (!$isCompleted && $taskCount === 0 && $responseCount > 0) {
        $isCompleted = true;
    }

    $status = $isCompleted ? "Completed" : "Pending";
    $access = student_has_open_access($student, $activeScopes) ? "Open" : "Closed";
    $submittedRaw = $responses["latest_response_submitted_at"] ?: ($tasks["latest_submitted_at"] ?? null);

    $programDisplay = trim((string)$student["course_code"] . " - " . (string)$student["course_name"], " -");
    $record = [
        "student_id" => $studentId,
        "student_number" => $student["student_number"],
        "student_name" => $student["full_name"],
        "department_id" => (int)$student["department_id"],
        "department_name" => $student["department_name"],
        "course_id" => (int)$student["course_id"],
        "course_code" => $student["course_code"],
        "course_name" => $student["course_name"],
        "program_display" => $programDisplay,
        "year_level_number" => (int)$student["year_level_number"],
        "year_level" => year_level_label($student["year_level_number"]),
        "section_id" => (int)$student["section_id"],
        "section_name" => $student["section_name"],
        "academic_year_id" => (int)$student["academic_year_id"],
        "academic_year" => $student["academic_year_name"],
        "semester" => term_label($student["term_name"]),
        "semester_raw" => $student["term_name"],
        "submitted_date_raw" => $submittedRaw,
        "submitted_date" => format_datetime_display($submittedRaw),
        "status" => $status,
        "evaluation_access" => $access,
        "task_count" => $taskCount,
        "submitted_task_count" => $submittedTaskCount,
        "response_count" => $responseCount,
        "average_rating" => $responses["average_rating"] !== null ? round((float)$responses["average_rating"], 2) : null,
        "evaluated_subjects" => [],
        "pending_subjects" => [],
    ];

    foreach ($evaluatedDetails[$studentId] ?? [] as $detail) {
        $record["evaluated_subjects"][] = [
            "subject" => trim($detail["subject_code"] . " - " . $detail["subject_title"], " -"),
            "faculty" => $detail["faculty_name"],
            "rating" => $detail["average_score"] !== null ? round((float)$detail["average_score"], 2) : null,
            "comment" => $detail["comment_text"] ?: "No comment submitted.",
            "submitted_at" => format_datetime_display($detail["submitted_at"]),
        ];
    }
    foreach ($pendingAssignments[$studentId] ?? [] as $assignment) {
        if (strtolower((string)$assignment["task_status"]) === "submitted") {
            continue;
        }
        $record["pending_subjects"][] = [
            "subject" => trim($assignment["subject_code"] . " - " . $assignment["subject_title"], " -"),
            "faculty" => $assignment["faculty_name"],
            "status" => $assignment["task_status"] ?: "Pending",
        ];
    }

    $records[] = $record;
}

$allRecords = $records;
$filteredRecords = array_values(array_filter($records, function (array $record) use ($filters): bool {
    if ($filters["tab"] !== "All" && $record["status"] !== $filters["tab"]) {
        return false;
    }
    if ($filters["status"] !== "" && $record["status"] !== $filters["status"]) {
        return false;
    }
    if ($filters["evaluation_access"] !== "" && $record["evaluation_access"] !== $filters["evaluation_access"]) {
        return false;
    }
    if ($filters["department_id"] > 0 && $record["department_id"] !== $filters["department_id"]) {
        return false;
    }
    if ($filters["course_id"] > 0 && $record["course_id"] !== $filters["course_id"]) {
        return false;
    }
    if ($filters["year_level"] > 0 && $record["year_level_number"] !== $filters["year_level"]) {
        return false;
    }
    if ($filters["section_id"] > 0 && $record["section_id"] !== $filters["section_id"]) {
        return false;
    }
    if ($filters["academic_year_id"] > 0 && $record["academic_year_id"] !== $filters["academic_year_id"]) {
        return false;
    }
    if ($filters["semester"] !== "" && $record["semester_raw"] !== $filters["semester"]) {
        return false;
    }
    if ($filters["search"] !== "") {
        $text = strtolower(implode(" ", [
            $record["student_number"],
            $record["student_name"],
            $record["department_name"],
            $record["program_display"],
            $record["year_level"],
            $record["section_name"],
            $record["academic_year"],
            $record["semester"],
            $record["status"],
            $record["evaluation_access"],
        ]));
        if (strpos($text, strtolower($filters["search"])) === false) {
            return false;
        }
    }
    return true;
}));

$totalAll = count($allRecords);
$completedAll = count(array_filter($allRecords, static fn($r) => $r["status"] === "Completed"));
$pendingAll = count(array_filter($allRecords, static fn($r) => $r["status"] === "Pending"));

$totalFiltered = count($filteredRecords);
$completedFiltered = count(array_filter($filteredRecords, static fn($r) => $r["status"] === "Completed"));
$pendingFiltered = count(array_filter($filteredRecords, static fn($r) => $r["status"] === "Pending"));
$totalResponses = $completedFiltered;
$participationRate = $totalFiltered > 0 ? round(($completedFiltered / $totalFiltered) * 100, 1) : 0.0;

if (($_GET["export"] ?? "") === "csv") {
    header("Content-Type: text/csv; charset=utf-8");
    header("Content-Disposition: attachment; filename=submission_monitoring_export_" . date("Ymd_His") . ".csv");
    $headers = ["Student ID", "Student Name", "Program", "Year Level", "Section", "Academic Year", "Semester", "Submitted Date", "Status", "Evaluation Access", "Average Rating"];
    echo implode(",", array_map("csv_escape", $headers)) . "\n";
    foreach ($filteredRecords as $record) {
        $line = [
            $record["student_number"],
            $record["student_name"],
            $record["program_display"],
            $record["year_level"],
            $record["section_name"],
            $record["academic_year"],
            $record["semester"],
            $record["submitted_date"],
            $record["status"],
            $record["evaluation_access"],
            $record["average_rating"] !== null ? number_format((float)$record["average_rating"], 2) : "",
        ];
        echo implode(",", array_map("csv_escape", $line)) . "\n";
    }
    exit;
}

$recordsJson = [];
foreach ($filteredRecords as $record) {
    $recordsJson[$record["student_id"]] = $record;
}

$flash = get_flash();
$logout_role_label = current_admin_label();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submission Monitoring</title>
    <link rel="stylesheet" href="submission-monitoring.css?v=<?php echo time(); ?>">

    <style>
        .filter-buttons {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
            flex-wrap: wrap;
        }

        .clear-filter-button {
            background: #f1f5f9 !important;
            color: #374151 !important;
            border-color: #d8dce2 !important;
        }

        .clear-filter-button:hover {
            background: #e5e7eb !important;
            border-color: #cbd5e1 !important;
            color: #111827 !important;
        }

        .filter-form.is-auto-submitting {
            opacity: 0.96;
        }

        .student-info-grid .status-badge,
        .student-info-grid .access-badge {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 7px !important;
            width: fit-content !important;
            min-width: 96px !important;
            max-width: max-content !important;
            min-height: 32px !important;
            padding: 0 13px !important;
            border-radius: 999px !important;
            font-size: 14px !important;
            font-weight: 700 !important;
            line-height: 1 !important;
            white-space: nowrap !important;
            margin: 0 !important;
        }

        .student-info-grid .status-badge > span,
        .student-info-grid .access-badge > span {
            display: inline-flex !important;
            width: 7px !important;
            height: 7px !important;
            min-width: 7px !important;
            max-width: 7px !important;
            border-radius: 999px !important;
            margin: 0 !important;
            padding: 0 !important;
            flex: 0 0 7px !important;
        }

        .student-info-grid .status-badge.completed {
            background: #dcfce7 !important;
            color: #16a34a !important;
        }

        .student-info-grid .status-badge.completed > span {
            background: #16a34a !important;
        }

        .student-info-grid .status-badge.pending {
            background: #ffedd5 !important;
            color: #fb4b05 !important;
        }

        .student-info-grid .status-badge.pending > span {
            background: #fb4b05 !important;
        }

        .student-info-grid .access-badge.open {
            background: #dcfce7 !important;
            color: #16a34a !important;
        }

        .student-info-grid .access-badge.open > span {
            background: #16a34a !important;
        }

        .student-info-grid .access-badge.closed {
            background: #fee2e2 !important;
            color: #dc2626 !important;
        }

        .student-info-grid .access-badge.closed > span {
            background: #dc2626 !important;
        }
    </style>
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
            <a class="nav-link" href="report_page.php"><?php echo icon_svg("reports"); ?> Reports</a>
            <a class="nav-link" href="announcement_page.php"><?php echo icon_svg("announcement"); ?> Announcements</a>
            <a class="nav-link" href="settings_page.php"><?php echo icon_svg("settings"); ?> Settings</a>
            <a class="nav-link" href="#"><?php echo icon_svg("moon"); ?> Dark Mode</a>
        </nav>

        <div class="sidebar-bottom">
            <a class="logout-link" href="submission_monitoring.php?logout=1"><?php echo icon_svg("logout"); ?> Logout</a>
        </div>
    </aside>

    <main class="main-content">
        <div class="content-wrap">
            <div class="page-title-row">
                <div>
                    <h1>Submission Monitoring</h1>
                    <p>Track completed and pending evaluations</p>
                </div>
                <?php if ($currentPeriod): ?>
                    <div class="period-chip">
                        <strong><?php echo e(term_label($currentPeriod["term_name"] ?? "")); ?></strong>
                        <span><?php echo e($currentPeriod["academic_year_name"] ?? ""); ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($flash): ?>
                <div class="toast-modal <?php echo e($flash["type"]); ?>" id="pageToast"><?php echo e($flash["message"]); ?></div>
            <?php endif; ?>

            <?php if (!$currentPeriod): ?>
                <div class="system-alert">
                    <?php echo icon_svg("alert"); ?>
                    <div>
                        <strong>No evaluation period found</strong>
                        <p>Please configure an evaluation period in Evaluation Setup before monitoring submissions.</p>
                    </div>
                </div>
            <?php endif; ?>

            <section class="summary-grid">
                <article class="summary-card completed-card">
                    <div>
                        <span>Completed</span>
                        <strong><?php echo (int)$completedFiltered; ?></strong>
                    </div>
                    <div class="summary-icon"><?php echo icon_svg("check-circle"); ?></div>
                </article>

                <article class="summary-card pending-card">
                    <div>
                        <span>Pending</span>
                        <strong><?php echo (int)$pendingFiltered; ?></strong>
                    </div>
                    <div class="summary-icon"><?php echo icon_svg("clock"); ?></div>
                </article>

                <article class="summary-card response-card">
                    <div>
                        <span>Total Responses</span>
                        <strong><?php echo (int)$totalResponses; ?></strong>
                    </div>
                    <div class="summary-icon"><?php echo icon_svg("bar-chart"); ?></div>
                </article>

                <article class="summary-card rate-card">
                    <div>
                        <span>Participation Rate</span>
                        <strong><?php echo e(number_format($participationRate, 1)); ?>%</strong>
                    </div>
                    <div class="summary-icon"><?php echo icon_svg("trend"); ?></div>
                </article>
            </section>

            <section class="status-tabs">
                <a class="tab-button all-tab <?php echo $filters["tab"] === "All" ? "active" : ""; ?>" href="submission_monitoring.php<?php echo e(make_query_with_params(["tab" => "All", "export" => null])); ?>">All <span>(<?php echo (int)$totalAll; ?>)</span></a>
                <a class="tab-button completed-tab <?php echo $filters["tab"] === "Completed" ? "active" : ""; ?>" href="submission_monitoring.php<?php echo e(make_query_with_params(["tab" => "Completed", "export" => null])); ?>">Completed <span>(<?php echo (int)$completedAll; ?>)</span></a>
                <a class="tab-button pending-tab <?php echo $filters["tab"] === "Pending" ? "active" : ""; ?>" href="submission_monitoring.php<?php echo e(make_query_with_params(["tab" => "Pending", "export" => null])); ?>">Pending <span>(<?php echo (int)$pendingAll; ?>)</span></a>
            </section>

            <section class="filter-panel">
                <form method="GET" action="submission_monitoring.php" class="filter-form">
                    <input type="hidden" name="tab" value="<?php echo e($filters["tab"]); ?>">
                    <div class="filter-title">
                        <?php echo icon_svg("filter"); ?>
                        <h2>Search and Filters</h2>
                    </div>

                    <div class="search-row">
                        <label for="searchInput">Search</label>
                        <div class="search-box">
                            <?php echo icon_svg("search"); ?>
                            <input type="text" id="searchInput" name="search" value="<?php echo e($filters["search"]); ?>" placeholder="Search by student name, ID, program, section, or status...">
                        </div>
                    </div>

                    <div class="filter-grid filter-grid-eight">
                        <div>
                            <label for="departmentFilter">College</label>
                            <select id="departmentFilter" name="department_id">
                                <option value="0">All Colleges</option>
                                <?php foreach ($departments as $department): ?>
                                    <option value="<?php echo (int)$department["department_id"]; ?>" <?php echo $filters["department_id"] === (int)$department["department_id"] ? "selected" : ""; ?>><?php echo e($department["department_name"]); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="courseFilter">Program</label>
                            <select id="courseFilter" name="course_id">
                                <option value="0">All Programs</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo (int)$course["course_id"]; ?>" data-department="<?php echo (int)$course["department_id"]; ?>" <?php echo $filters["course_id"] === (int)$course["course_id"] ? "selected" : ""; ?>><?php echo e($course["course_code"] . " - " . $course["course_name"]); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="yearFilter">Year Level</label>
                            <select id="yearFilter" name="year_level">
                                <option value="0">All Year Levels</option>
                                <?php for ($i = 1; $i <= 6; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $filters["year_level"] === $i ? "selected" : ""; ?>><?php echo e(year_level_label($i)); ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div>
                            <label for="sectionFilter">Section</label>
                            <select id="sectionFilter" name="section_id">
                                <option value="0">All Sections</option>
                                <?php foreach ($sections as $section): ?>
                                    <option value="<?php echo (int)$section["section_id"]; ?>" data-course="<?php echo (int)$section["course_id"]; ?>" data-year="<?php echo (int)$section["year_level"]; ?>" <?php echo $filters["section_id"] === (int)$section["section_id"] ? "selected" : ""; ?>><?php echo e($section["section_name"]); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="academicYearFilter">Academic Year</label>
                            <select id="academicYearFilter" name="academic_year_id">
                                <option value="0">All Academic Years</option>
                                <?php foreach ($academicYears as $academicYear): ?>
                                    <option value="<?php echo (int)$academicYear["academic_year_id"]; ?>" <?php echo $filters["academic_year_id"] === (int)$academicYear["academic_year_id"] ? "selected" : ""; ?>><?php echo e($academicYear["academic_year_name"]); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="semesterFilter">Semester</label>
                            <select id="semesterFilter" name="semester">
                                <option value="">All Semesters</option>
                                <?php foreach ($semesters as $semester): ?>
                                    <option value="<?php echo e($semester["term_name"]); ?>" <?php echo $filters["semester"] === $semester["term_name"] ? "selected" : ""; ?>><?php echo e(term_label($semester["term_name"])); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="accessFilter">Evaluation Access</label>
                            <select id="accessFilter" name="evaluation_access">
                                <option value="">All Access</option>
                                <option value="Open" <?php echo $filters["evaluation_access"] === "Open" ? "selected" : ""; ?>>Open</option>
                                <option value="Closed" <?php echo $filters["evaluation_access"] === "Closed" ? "selected" : ""; ?>>Closed</option>
                            </select>
                        </div>

                        <div>
                            <label for="statusFilter">Status</label>
                            <select id="statusFilter" name="status">
                                <option value="">All Status</option>
                                <option value="Completed" <?php echo $filters["status"] === "Completed" ? "selected" : ""; ?>>Completed</option>
                                <option value="Pending" <?php echo $filters["status"] === "Pending" ? "selected" : ""; ?>>Pending</option>
                            </select>
                        </div>
                    </div>

                    <div class="filter-action-row">
                        <div class="filter-count">Showing <?php echo (int)$totalFiltered; ?> of <?php echo (int)$totalAll; ?> student submissions</div>
                        <div class="filter-buttons">
                            <a class="secondary-button clear-filter-button" href="submission_monitoring.php">Clear</a>
                            <a class="export-button" href="submission_monitoring.php<?php echo e(make_query_with_params(["export" => "csv"])); ?>"><?php echo icon_svg("download"); ?> Export</a>
                        </div>
                    </div>
                </form>
            </section>

            <section class="table-card">
                <table class="submission-table">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Program</th>
                            <th>Year Level</th>
                            <th>Section</th>
                            <th>Academic Year</th>
                            <th>Semester</th>
                            <th>Submitted Date</th>
                            <th>Status</th>
                            <th>Evaluation Access</th>
                            <th class="actions-head">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$filteredRecords): ?>
                            <tr>
                                <td colspan="11" class="empty-row">No student submissions found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($filteredRecords as $record): ?>
                                <tr>
                                    <td><strong><?php echo e($record["student_number"]); ?></strong></td>
                                    <td><?php echo e($record["student_name"]); ?></td>
                                    <td><?php echo e($record["program_display"]); ?></td>
                                    <td><?php echo e($record["year_level"]); ?></td>
                                    <td><?php echo e($record["section_name"]); ?></td>
                                    <td><?php echo e($record["academic_year"]); ?></td>
                                    <td><?php echo e($record["semester"]); ?></td>
                                    <td><?php echo e($record["status"] === "Completed" ? $record["submitted_date"] : "—"); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo strtolower($record["status"]); ?>">
                                            <span></span>
                                            <?php echo e($record["status"]); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="access-badge <?php echo strtolower($record["evaluation_access"]); ?>">
                                            <span></span>
                                            <?php echo e($record["evaluation_access"]); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-icons">
                                            <button class="view" type="button" title="View Details" onclick="openSubmissionDetails(<?php echo (int)$record["student_id"]; ?>)">
                                                <?php echo icon_svg("eye"); ?>
                                            </button>
                                            <form method="POST" onsubmit="return confirmDeleteMonitoring();">
                                                <input type="hidden" name="action" value="delete_monitoring_record">
                                                <input type="hidden" name="student_id" value="<?php echo (int)$record["student_id"]; ?>">
                                                <button class="delete" type="submit" title="Delete Record">
                                                    <?php echo icon_svg("trash"); ?>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </div>
    </main>

    <div id="modalRoot"></div>

    <div class="admin_logout_overlay" id="adminLogoutOverlay" aria-hidden="true">
        <div class="admin_logout_modal" role="dialog" aria-modal="true" aria-labelledby="adminLogoutTitle">
            <div class="admin_logout_icon"><?php echo icon_svg("alert"); ?></div>
            <h2 id="adminLogoutTitle">Log Out Confirmation</h2>
            <p>
                Are you sure you want to log out of the
                <strong><?php echo e($logout_role_label); ?></strong>
                account? You will need to sign in again to continue managing the
                <strong>Student Evaluation for Teacher System.</strong>
            </p>
            <div class="admin_logout_divider"></div>
            <div class="admin_logout_actions">
                <button type="button" class="admin_logout_cancel" id="adminLogoutCancel">Cancel</button>
                <button type="button" class="admin_logout_confirm" id="adminLogoutConfirm"><?php echo icon_svg("logout"); ?> Log Out</button>
            </div>
        </div>
    </div>

    <script>
        const submissionRecords = <?php echo json_safe($recordsJson); ?>;
        const iconSvg = {
            close: <?php echo json_safe(icon_svg("close")); ?>
        };
        const modalRoot = document.getElementById("modalRoot");

        function escapeHTML(value) {
            return String(value ?? "")
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function statusBadge(status) {
            const statusClass = String(status || "Pending").toLowerCase();
            return `<span class="status-badge ${statusClass}"><span></span>${escapeHTML(status || "Pending")}</span>`;
        }

        function accessBadge(status) {
            const accessClass = String(status || "Closed").toLowerCase();
            return `<span class="access-badge ${accessClass}"><span></span>${escapeHTML(status || "Closed")}</span>`;
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

        function openSubmissionDetails(studentId) {
            const record = submissionRecords[String(studentId)];
            if (!record) return;

            const averageContent = record.status === "Completed"
                ? `<div class="average-card">
                       <span>Average Rating All Teachers</span>
                       <strong>${record.average_rating !== null ? Number(record.average_rating).toFixed(2) : "0.00"} / 5.0</strong>
                       <p>Based on ${Number(record.response_count || 0)} submitted evaluation${Number(record.response_count || 0) === 1 ? "" : "s"}</p>
                   </div>`
                : `<div class="average-card pending-average">
                       <span>Average Rating All Teachers</span>
                       <strong>Pending</strong>
                       <p>No submitted evaluations yet</p>
                   </div>`;

            const evaluatedSubjects = Array.isArray(record.evaluated_subjects) ? record.evaluated_subjects : [];
            const pendingSubjects = Array.isArray(record.pending_subjects) ? record.pending_subjects : [];

            let subjectContent = "";
            if (record.status === "Completed") {
                subjectContent = `<h3 class="details-section-title">Evaluated Courses and Teachers</h3>`;
                if (evaluatedSubjects.length === 0) {
                    subjectContent += `<div class="pending-message"><h3>No course details found</h3><p>The student is marked completed, but no submitted course details were found for the current evaluation period.</p></div>`;
                } else {
                    subjectContent += evaluatedSubjects.map(subject => `
                        <div class="subject-detail-card">
                            <div class="subject-detail-top">
                                <div>
                                    <strong>${escapeHTML(subject.subject)}</strong>
                                    <p>Faculty: ${escapeHTML(subject.faculty)}</p>
                                </div>
                                <div class="rating-box">
                                    <span>Rating</span>
                                    <strong>${subject.rating !== null ? Number(subject.rating).toFixed(1) : "0.0"} / 5.0</strong>
                                </div>
                            </div>
                            <div class="comment-box">
                                <span>Comment</span>
                                <p>${escapeHTML(subject.comment)}</p>
                            </div>
                        </div>
                    `).join("");
                }
            } else {
                subjectContent = `
                    <div class="pending-message">
                        <h3>Evaluation Not Submitted Yet</h3>
                        <p>This student still has pending teacher evaluations. No final ratings or comments are available yet.</p>
                    </div>
                    <h3 class="details-section-title">Assigned Courses Pending Evaluation</h3>
                `;
                if (pendingSubjects.length === 0) {
                    subjectContent += `<div class="subject-detail-card pending-subject"><div class="subject-detail-top"><div><strong>No assigned pending courses found</strong><p>Please check assignment management and generated student evaluation tasks.</p></div><div class="rating-box">Pending</div></div></div>`;
                } else {
                    subjectContent += pendingSubjects.map(subject => `
                        <div class="subject-detail-card pending-subject">
                            <div class="subject-detail-top">
                                <div>
                                    <strong>${escapeHTML(subject.subject)}</strong>
                                    <p>Faculty: ${escapeHTML(subject.faculty)}</p>
                                </div>
                                <div class="rating-box">${escapeHTML(subject.status || "Pending")}</div>
                            </div>
                        </div>
                    `).join("");
                }
            }

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
                                    <div><span>Student ID</span><strong>${escapeHTML(record.student_number)}</strong></div>
                                    <div><span>Student Name</span><strong>${escapeHTML(record.student_name)}</strong></div>
                                    <div><span>Program</span><strong>${escapeHTML(record.program_display)}</strong></div>
                                    <div><span>Year Level</span><strong>${escapeHTML(record.year_level)}</strong></div>
                                    <div><span>Section</span><strong>${escapeHTML(record.section_name)}</strong></div>
                                    <div><span>Academic Year</span><strong>${escapeHTML(record.academic_year)}</strong></div>
                                    <div><span>Semester</span><strong>${escapeHTML(record.semester)}</strong></div>
                                    <div><span>Status</span>${statusBadge(record.status)}</div>
                                    <div><span>Evaluation Access</span>${accessBadge(record.evaluation_access)}</div>
                                    <div><span>Submitted Date</span><strong>${record.status === "Completed" ? escapeHTML(record.submitted_date) : "Not submitted yet"}</strong></div>
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

        function confirmDeleteMonitoring() {
            return confirm("Delete this monitoring record? Records with existing evaluation data will be marked inactive instead.");
        }

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape") closeModal();
        });

        (function initDependentFilters() {
            const departmentFilter = document.getElementById("departmentFilter");
            const courseFilter = document.getElementById("courseFilter");
            const yearFilter = document.getElementById("yearFilter");
            const sectionFilter = document.getElementById("sectionFilter");

            function updateCourses() {
                const departmentId = departmentFilter.value;
                Array.from(courseFilter.options).forEach(option => {
                    if (option.value === "0") {
                        option.hidden = false;
                        return;
                    }
                    option.hidden = departmentId !== "0" && option.dataset.department !== departmentId;
                });
                if (courseFilter.selectedOptions[0] && courseFilter.selectedOptions[0].hidden) {
                    courseFilter.value = "0";
                }
                updateSections();
            }

            function updateSections() {
                const courseId = courseFilter.value;
                const year = yearFilter.value;
                Array.from(sectionFilter.options).forEach(option => {
                    if (option.value === "0") {
                        option.hidden = false;
                        return;
                    }
                    const matchesCourse = courseId === "0" || option.dataset.course === courseId;
                    const matchesYear = year === "0" || option.dataset.year === year;
                    option.hidden = !(matchesCourse && matchesYear);
                });
                if (sectionFilter.selectedOptions[0] && sectionFilter.selectedOptions[0].hidden) {
                    sectionFilter.value = "0";
                }
            }

            departmentFilter.addEventListener("change", updateCourses);
            courseFilter.addEventListener("change", updateSections);
            yearFilter.addEventListener("change", updateSections);
            updateCourses();
        })();


        (function initAutoFilterSubmission() {
            const form = document.querySelector(".filter-form");
            if (!form) return;

            const searchInput = document.getElementById("searchInput");
            const selectFilters = form.querySelectorAll("select");
            let filterTimer = null;
            let submitting = false;

            function submitAutomatically(delay = 350) {
                window.clearTimeout(filterTimer);
                filterTimer = window.setTimeout(() => {
                    if (submitting) return;
                    submitting = true;
                    form.classList.add("is-auto-submitting");
                    form.submit();
                }, delay);
            }

            if (searchInput) {
                searchInput.addEventListener("input", () => submitAutomatically(550));
                searchInput.addEventListener("keydown", event => {
                    if (event.key === "Enter") {
                        event.preventDefault();
                        submitAutomatically(0);
                    }
                });
            }

            selectFilters.forEach(select => {
                select.addEventListener("change", () => submitAutomatically(150));
            });
        })();

        (function initToast() {
            const toast = document.getElementById("pageToast");
            if (!toast) return;
            window.setTimeout(() => toast.classList.add("hide"), 3000);
            window.setTimeout(() => toast.remove(), 3500);
        })();

        (function initAdminLogoutModal() {
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

            document.querySelectorAll(".logout-link").forEach(link => {
                link.addEventListener("click", function (event) {
                    event.preventDefault();
                    openAdminLogoutModal(link.getAttribute("href"));
                });
            });
            cancelBtn.addEventListener("click", closeAdminLogoutModal);
            overlay.addEventListener("click", event => {
                if (event.target === overlay) closeAdminLogoutModal();
            });
            document.addEventListener("keydown", event => {
                if (event.key === "Escape" && overlay.classList.contains("show")) closeAdminLogoutModal();
            });
            confirmBtn.addEventListener("click", function () {
                window.location.href = "../login/login_page.php";
            });
        })();
    </script>
</body>
</html>
