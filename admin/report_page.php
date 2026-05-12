<?php
declare(strict_types=1);

session_start();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$database_host = "localhost";
$database_name = "student_evaluation_for_teacher_db";
$database_username = "root";
$database_password = "";

$db = new mysqli($database_host, $database_username, $database_password, $database_name);
$db->set_charset("utf8mb4");

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function json_safe($value): string
{
    return json_encode($value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
}

function db_column_exists(mysqli $db, string $table, string $column): bool
{
    $stmt = $db->prepare("SELECT COUNT(*) AS c FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
    $stmt->bind_param("ss", $table, $column);
    $stmt->execute();
    return ((int)$stmt->get_result()->fetch_assoc()["c"]) > 0;
}

function db_table_exists(mysqli $db, string $table): bool
{
    $stmt = $db->prepare("SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
    $stmt->bind_param("s", $table);
    $stmt->execute();
    return ((int)$stmt->get_result()->fetch_assoc()["c"]) > 0;
}

function fetch_all(mysqli $db, string $sql, string $types = "", array $params = []): array
{
    if ($params) {
        $stmt = $db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    $result = $db->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
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
    $_SESSION["report_page_flash"] = ["message" => $message, "type" => $type];
}

function get_flash(): ?array
{
    if (!isset($_SESSION["report_page_flash"])) {
        return null;
    }
    $flash = $_SESSION["report_page_flash"];
    unset($_SESSION["report_page_flash"]);
    return $flash;
}

function redirect_self(array $extra = []): void
{
    $params = $_GET;
    unset($params["action"]);
    $params = array_merge($params, $extra);
    $query = $params ? "?" . http_build_query($params) : "";
    header("Location: " . basename(__FILE__) . $query);
    exit;
}

function normalize_term_label(string $term): string
{
    return match ($term) {
        "First Semester" => "1st Semester",
        "Second Semester" => "2nd Semester",
        default => $term,
    };
}

function report_type_db_value(string $uiType): string
{
    return match ($uiType) {
        "Faculty Performance Summary" => "Faculty Summary",
        "Criteria Performance Summary" => "Criteria Summary",
        default => "Overall Summary",
    };
}

function performance_label(?float $rating): string
{
    if ($rating === null || $rating <= 0) {
        return "No Rating";
    }
    if ($rating >= 4.50) {
        return "Excellent";
    }
    if ($rating >= 3.50) {
        return "Very Good";
    }
    if ($rating >= 2.50) {
        return "Good";
    }
    if ($rating >= 1.50) {
        return "Fair";
    }
    return "Needs Improvement";
}

function badge_class(string $label): string
{
    return strtolower(str_replace(" ", "-", $label));
}

function current_admin_id(mysqli $db): int
{
    if (!empty($_SESSION["admin_id"])) {
        return (int)$_SESSION["admin_id"];
    }
    if (!empty($_SESSION["user_id"])) {
        $row = fetch_one($db, "SELECT admin_id FROM admin WHERE user_id = ? LIMIT 1", "i", [(int)$_SESSION["user_id"]]);
        if ($row) {
            return (int)$row["admin_id"];
        }
    }
    $row = fetch_one($db, "SELECT admin_id FROM admin WHERE admin_status = 'Active' ORDER BY admin_id LIMIT 1");
    return $row ? (int)$row["admin_id"] : 1;
}

function icon_svg(string $name): string
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
        "filter" => '<svg viewBox="0 0 24 24"><path d="M22 3H2l8 9v7l4 2v-9l8-9z"></path></svg>',
        "download" => '<svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><path d="M7 10l5 5 5-5"></path><path d="M12 15V3"></path></svg>',
        "eye" => '<svg viewBox="0 0 24 24"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"></path><circle cx="12" cy="12" r="3"></circle></svg>',
        "print" => '<svg viewBox="0 0 24 24"><path d="M6 9V2h12v7"></path><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><path d="M6 14h12v8H6z"></path></svg>',
        "check" => '<svg viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"></path></svg>',
        "file" => '<svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path></svg>',
        "clock" => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg>',
        "trend" => '<svg viewBox="0 0 24 24"><path d="M3 17l6-6 4 4 8-8"></path><path d="M14 7h7v7"></path></svg>',
        "close" => '<svg viewBox="0 0 24 24"><path d="M18 6L6 18"></path><path d="M6 6l12 12"></path></svg>',
    ];
    return $icons[$name] ?? "";
}

function ensure_report_support(mysqli $db): void
{
    if (!db_table_exists($db, "evaluation_result_release")) {
        $db->query("CREATE TABLE evaluation_result_release (
            evaluation_result_release_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            evaluation_period_id BIGINT UNSIGNED NOT NULL,
            release_name VARCHAR(150) NOT NULL,
            release_status ENUM('Draft','Released','Unreleased','Revoked') NOT NULL DEFAULT 'Draft',
            released_by_admin_id BIGINT UNSIGNED NOT NULL,
            released_at DATETIME NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (evaluation_result_release_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    $columns = [
        "release_scope_type" => "ALTER TABLE evaluation_result_release ADD COLUMN release_scope_type ENUM('All','Department','Course','Year Level','Section','Subject','Faculty') NOT NULL DEFAULT 'All' AFTER release_status",
        "release_department_id" => "ALTER TABLE evaluation_result_release ADD COLUMN release_department_id BIGINT UNSIGNED NULL AFTER release_scope_type",
        "release_course_id" => "ALTER TABLE evaluation_result_release ADD COLUMN release_course_id BIGINT UNSIGNED NULL AFTER release_department_id",
        "release_year_level" => "ALTER TABLE evaluation_result_release ADD COLUMN release_year_level TINYINT UNSIGNED NULL AFTER release_course_id",
        "release_section_id" => "ALTER TABLE evaluation_result_release ADD COLUMN release_section_id BIGINT UNSIGNED NULL AFTER release_year_level",
        "release_subject_id" => "ALTER TABLE evaluation_result_release ADD COLUMN release_subject_id BIGINT UNSIGNED NULL AFTER release_section_id",
        "release_faculty_id" => "ALTER TABLE evaluation_result_release ADD COLUMN release_faculty_id BIGINT UNSIGNED NULL AFTER release_subject_id",
        "release_academic_year_id" => "ALTER TABLE evaluation_result_release ADD COLUMN release_academic_year_id BIGINT UNSIGNED NULL AFTER release_faculty_id",
        "release_term_id" => "ALTER TABLE evaluation_result_release ADD COLUMN release_term_id BIGINT UNSIGNED NULL AFTER release_academic_year_id",
        "release_note" => "ALTER TABLE evaluation_result_release ADD COLUMN release_note VARCHAR(255) NULL AFTER release_term_id"
    ];

    foreach ($columns as $column => $sql) {
        if (!db_column_exists($db, "evaluation_result_release", $column)) {
            $db->query($sql);
        }
    }

    if (!db_table_exists($db, "generated_report")) {
        $db->query("CREATE TABLE generated_report (
            generated_report_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            generated_by_user_id BIGINT UNSIGNED NOT NULL,
            report_type VARCHAR(100) NOT NULL,
            report_format VARCHAR(50) NOT NULL,
            filter_academic_year_id BIGINT UNSIGNED NULL,
            filter_term_id BIGINT UNSIGNED NULL,
            filter_department_id BIGINT UNSIGNED NULL,
            filter_course_id BIGINT UNSIGNED NULL,
            filter_year_level TINYINT UNSIGNED NULL,
            filter_section_id BIGINT UNSIGNED NULL,
            filter_subject_id BIGINT UNSIGNED NULL,
            filter_faculty_id BIGINT UNSIGNED NULL,
            file_path VARCHAR(255) NULL,
            generated_report_status VARCHAR(30) NOT NULL DEFAULT 'Generated',
            generated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (generated_report_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
}

function log_generated_report(mysqli $db, int $adminId, array $filters, string $format): void
{
    if (!db_table_exists($db, "generated_report")) {
        return;
    }
    try {
        $type = report_type_db_value((string)$filters["report_type"]);
        execute_stmt($db, "INSERT INTO generated_report (
            generated_by_user_id, report_type, report_format, filter_academic_year_id, filter_term_id,
            filter_department_id, filter_course_id, filter_year_level, filter_section_id, filter_subject_id,
            filter_faculty_id, generated_report_status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Generated')", "issiiiiiiii", [
            $adminId,
            $type,
            $format,
            (int)($filters["academic_year_id"] ?: 0) ?: null,
            (int)($filters["term_id"] ?: 0) ?: null,
            (int)($filters["department_id"] ?: 0) ?: null,
            (int)($filters["course_id"] ?: 0) ?: null,
            (int)($filters["year_level"] ?: 0) ?: null,
            (int)($filters["section_id"] ?: 0) ?: null,
            (int)($filters["subject_id"] ?: 0) ?: null,
            (int)($filters["faculty_id"] ?: 0) ?: null,
        ]);
    } catch (Throwable $e) {
        return;
    }
}

function get_filters(): array
{
    $reportType = (string)($_GET["report_type"] ?? "Overall Summary");
    if (!in_array($reportType, ["Overall Summary", "Faculty Performance Summary", "Criteria Performance Summary"], true)) {
        $reportType = "Overall Summary";
    }
    return [
        "report_type" => $reportType,
        "term_id" => (int)($_GET["term_id"] ?? 0),
        "academic_year_id" => (int)($_GET["academic_year_id"] ?? 0),
        "department_id" => (int)($_GET["department_id"] ?? 0),
        "course_id" => (int)($_GET["course_id"] ?? 0),
        "year_level" => (int)($_GET["year_level"] ?? 0),
        "section_id" => (int)($_GET["section_id"] ?? 0),
        "subject_id" => (int)($_GET["subject_id"] ?? 0),
        "faculty_id" => (int)($_GET["faculty_id"] ?? 0),
    ];
}

function build_base_parts(mysqli $db, array $filters, string $prefix = "setask"): array
{
    $hasTaskAssignment = db_column_exists($db, "student_evaluation_task", "teaching_assignment_id");
    $assignmentJoin = $hasTaskAssignment
        ? "ta.teaching_assignment_id = COALESCE(setask.teaching_assignment_id, er.teaching_assignment_id)"
        : "ta.teaching_assignment_id = er.teaching_assignment_id";

    $from = "
        FROM student_evaluation_task setask
        INNER JOIN student st ON st.student_id = setask.student_id
        INNER JOIN course c ON c.course_id = st.course_id
        INNER JOIN department d ON d.department_id = c.department_id
        LEFT JOIN section sec ON sec.section_id = st.current_section_id
        INNER JOIN evaluation_period ep ON ep.evaluation_period_id = setask.evaluation_period_id
        INNER JOIN term t ON t.term_id = ep.term_id
        INNER JOIN academic_year ay ON ay.academic_year_id = t.academic_year_id
        LEFT JOIN evaluation_response er ON er.student_evaluation_task_id = setask.student_evaluation_task_id AND er.response_status = 'Submitted'
        LEFT JOIN teaching_assignment ta ON {$assignmentJoin}
        LEFT JOIN faculty f ON f.faculty_id = ta.faculty_id
        LEFT JOIN department fd ON fd.department_id = f.department_id
        LEFT JOIN section_subject_offering sso ON sso.section_subject_offering_id = ta.section_subject_offering_id
        LEFT JOIN subject subj ON subj.subject_id = sso.subject_id
    ";

    $where = ["setask.task_status <> 'Reset'"];
    $types = "";
    $params = [];

    $map = [
        "academic_year_id" => "ay.academic_year_id",
        "term_id" => "t.term_id",
        "department_id" => "d.department_id",
        "course_id" => "c.course_id",
        "year_level" => "st.current_year_level",
        "section_id" => "sec.section_id",
        "subject_id" => "subj.subject_id",
        "faculty_id" => "f.faculty_id",
    ];

    foreach ($map as $key => $column) {
        if (!empty($filters[$key])) {
            $where[] = "{$column} = ?";
            $types .= "i";
            $params[] = (int)$filters[$key];
        }
    }

    return ["from" => $from, "where" => " WHERE " . implode(" AND ", $where), "types" => $types, "params" => $params];
}

function build_answer_parts(mysqli $db, array $filters): array
{
    $from = "
        FROM evaluation_response_answer era
        INNER JOIN evaluation_response er ON er.evaluation_response_id = era.evaluation_response_id AND er.response_status = 'Submitted'
        INNER JOIN student_evaluation_task setask ON setask.student_evaluation_task_id = er.student_evaluation_task_id
        INNER JOIN student st ON st.student_id = er.student_id
        INNER JOIN course c ON c.course_id = st.course_id
        INNER JOIN department d ON d.department_id = c.department_id
        LEFT JOIN section sec ON sec.section_id = st.current_section_id
        INNER JOIN evaluation_period ep ON ep.evaluation_period_id = er.evaluation_period_id
        INNER JOIN term t ON t.term_id = ep.term_id
        INNER JOIN academic_year ay ON ay.academic_year_id = t.academic_year_id
        LEFT JOIN teaching_assignment ta ON ta.teaching_assignment_id = er.teaching_assignment_id
        LEFT JOIN faculty f ON f.faculty_id = ta.faculty_id
        LEFT JOIN section_subject_offering sso ON sso.section_subject_offering_id = ta.section_subject_offering_id
        LEFT JOIN subject subj ON subj.subject_id = sso.subject_id
        INNER JOIN evaluation_form_item efi ON efi.evaluation_form_item_id = era.evaluation_form_item_id
        INNER JOIN evaluation_form_category efc ON efc.evaluation_form_category_id = efi.evaluation_form_category_id
        INNER JOIN evaluation_category ec ON ec.evaluation_category_id = efc.evaluation_category_id
    ";

    $where = ["setask.task_status <> 'Reset'"];
    $types = "";
    $params = [];
    $map = [
        "academic_year_id" => "ay.academic_year_id",
        "term_id" => "t.term_id",
        "department_id" => "d.department_id",
        "course_id" => "c.course_id",
        "year_level" => "st.current_year_level",
        "section_id" => "sec.section_id",
        "subject_id" => "subj.subject_id",
        "faculty_id" => "f.faculty_id",
    ];
    foreach ($map as $key => $column) {
        if (!empty($filters[$key])) {
            $where[] = "{$column} = ?";
            $types .= "i";
            $params[] = (int)$filters[$key];
        }
    }
    return ["from" => $from, "where" => " WHERE " . implode(" AND ", $where), "types" => $types, "params" => $params];
}

function report_scope_labels(mysqli $db, array $filters): array
{
    $labels = [
        "semester" => "All Semesters",
        "academic_year" => "All Academic Years",
        "department" => "All Colleges",
        "course" => "All Programs",
        "year_level" => "All Year Levels",
        "section" => "All Sections",
        "subject" => "All Courses",
        "faculty" => "All Faculty",
    ];
    if ($filters["term_id"]) {
        $row = fetch_one($db, "SELECT term_name FROM term WHERE term_id = ?", "i", [$filters["term_id"]]);
        if ($row) {
            $labels["semester"] = normalize_term_label((string)$row["term_name"]);
        }
    }
    if ($filters["academic_year_id"]) {
        $row = fetch_one($db, "SELECT academic_year_name FROM academic_year WHERE academic_year_id = ?", "i", [$filters["academic_year_id"]]);
        if ($row) {
            $labels["academic_year"] = str_replace(" - ", "-", (string)$row["academic_year_name"]);
        }
    }
    if ($filters["department_id"]) {
        $row = fetch_one($db, "SELECT department_name FROM department WHERE department_id = ?", "i", [$filters["department_id"]]);
        if ($row) {
            $labels["department"] = (string)$row["department_name"];
        }
    }
    if ($filters["course_id"]) {
        $row = fetch_one($db, "SELECT CONCAT(course_code, ' - ', course_name) AS name FROM course WHERE course_id = ?", "i", [$filters["course_id"]]);
        if ($row) {
            $labels["course"] = (string)$row["name"];
        }
    }
    if ($filters["year_level"]) {
        $labels["year_level"] = ordinal((int)$filters["year_level"]) . " Year";
    }
    if ($filters["section_id"]) {
        $row = fetch_one($db, "SELECT section_name FROM section WHERE section_id = ?", "i", [$filters["section_id"]]);
        if ($row) {
            $labels["section"] = (string)$row["section_name"];
        }
    }
    if ($filters["subject_id"]) {
        $row = fetch_one($db, "SELECT CONCAT(subject_code, ' - ', subject_title) AS name FROM subject WHERE subject_id = ?", "i", [$filters["subject_id"]]);
        if ($row) {
            $labels["subject"] = (string)$row["name"];
        }
    }
    if ($filters["faculty_id"]) {
        $row = fetch_one($db, "SELECT full_name FROM faculty WHERE faculty_id = ?", "i", [$filters["faculty_id"]]);
        if ($row) {
            $labels["faculty"] = (string)$row["full_name"];
        }
    }
    return $labels;
}

function ordinal(int $number): string
{
    return match ($number) {
        1 => "1st",
        2 => "2nd",
        3 => "3rd",
        default => $number . "th",
    };
}

function get_report_data(mysqli $db, array $filters): array
{
    $parts = build_base_parts($db, $filters);
    $stats = fetch_one($db, "
        SELECT
            COUNT(DISTINCT f.faculty_id) AS total_faculty,
            COUNT(DISTINCT st.student_id) AS total_students,
            COUNT(DISTINCT CASE WHEN er.response_status = 'Submitted' THEN er.evaluation_response_id END) AS total_responses,
            COUNT(DISTINCT setask.student_evaluation_task_id) AS total_assigned,
            COUNT(DISTINCT CASE WHEN setask.task_status = 'Submitted' THEN setask.student_evaluation_task_id END) AS completed_tasks,
            COUNT(DISTINCT CASE WHEN setask.task_status IN ('Pending','Draft') THEN setask.student_evaluation_task_id END) AS pending_tasks,
            ROUND(AVG(er.average_score), 2) AS average_rating
        {$parts['from']} {$parts['where']}
    ", $parts["types"], $parts["params"]);

    $stats = $stats ?: [];
    $totalAssigned = (int)($stats["total_assigned"] ?? 0);
    $completed = (int)($stats["completed_tasks"] ?? 0);
    $stats["completion_rate"] = $totalAssigned > 0 ? round(($completed / $totalAssigned) * 100, 1) : 0;
    $stats["average_rating"] = $stats["average_rating"] !== null ? round((float)$stats["average_rating"], 2) : 0;

    $facultyRows = fetch_all($db, "
        SELECT
            f.faculty_id,
            COALESCE(f.faculty_number, CONCAT('FAC', LPAD(f.faculty_id, 5, '0'))) AS faculty_number,
            COALESCE(f.full_name, 'Unassigned Faculty') AS faculty_name,
            COALESCE(fd.department_name, d.department_name, 'No College') AS department_name,
            GROUP_CONCAT(DISTINCT CONCAT(COALESCE(subj.subject_code, 'N/A'), ' - ', COALESCE(subj.subject_title, 'No Course')) ORDER BY subj.subject_code SEPARATOR ', ') AS subjects,
            COUNT(DISTINCT setask.student_evaluation_task_id) AS assigned_count,
            COUNT(DISTINCT CASE WHEN er.response_status = 'Submitted' THEN er.evaluation_response_id END) AS responses,
            ROUND(AVG(er.average_score), 2) AS avg_rating,
            CASE WHEN COUNT(DISTINCT setask.student_evaluation_task_id) > 0
                THEN ROUND((COUNT(DISTINCT CASE WHEN setask.task_status = 'Submitted' THEN setask.student_evaluation_task_id END) / COUNT(DISTINCT setask.student_evaluation_task_id)) * 100, 1)
                ELSE 0 END AS completion_rate
        {$parts['from']} {$parts['where']}
        GROUP BY f.faculty_id, f.faculty_number, f.full_name, fd.department_name, d.department_name
        HAVING f.faculty_id IS NOT NULL
        ORDER BY avg_rating DESC, responses DESC, faculty_name ASC
        LIMIT 200
    ", $parts["types"], $parts["params"]);

    foreach ($facultyRows as &$row) {
        $row["avg_rating"] = $row["avg_rating"] !== null ? round((float)$row["avg_rating"], 2) : null;
        $row["performance"] = performance_label($row["avg_rating"]);
    }
    unset($row);

    $answerParts = build_answer_parts($db, $filters);
    $criteriaRows = fetch_all($db, "
        SELECT
            ec.category_name,
            MAX(efc.weight_percent) AS weight_percent,
            ROUND(MAX(era.rating_value), 2) AS highest_score,
            ROUND(AVG(era.rating_value), 2) AS average_score,
            ROUND(MIN(era.rating_value), 2) AS lowest_score,
            COUNT(era.evaluation_response_answer_id) AS response_count
        {$answerParts['from']} {$answerParts['where']}
        GROUP BY ec.evaluation_category_id, ec.category_name
        ORDER BY MIN(efc.display_order), ec.category_name
    ", $answerParts["types"], $answerParts["params"]);

    $departmentRows = fetch_all($db, "
        SELECT
            d.department_id,
            d.department_name,
            COUNT(DISTINCT er.evaluation_response_id) AS responses,
            COUNT(DISTINCT setask.student_evaluation_task_id) AS assigned_count,
            COUNT(DISTINCT CASE WHEN setask.task_status = 'Submitted' THEN setask.student_evaluation_task_id END) AS completed_count,
            ROUND(AVG(er.average_score), 2) AS avg_rating
        {$parts['from']} {$parts['where']}
        GROUP BY d.department_id, d.department_name
        ORDER BY d.department_name
    ", $parts["types"], $parts["params"]);
    foreach ($departmentRows as &$row) {
        $assigned = (int)($row["assigned_count"] ?? 0);
        $completed = (int)($row["completed_count"] ?? 0);
        $row["completion_rate"] = $assigned > 0 ? round(($completed / $assigned) * 100, 1) : 0;
        $row["avg_rating"] = $row["avg_rating"] !== null ? round((float)$row["avg_rating"], 2) : 0;
    }
    unset($row);

    $courseRows = fetch_all($db, "
        SELECT
            c.course_id,
            c.course_code,
            c.course_name,
            d.department_name,
            COUNT(DISTINCT er.evaluation_response_id) AS responses,
            COUNT(DISTINCT setask.student_evaluation_task_id) AS assigned_count,
            COUNT(DISTINCT CASE WHEN setask.task_status = 'Submitted' THEN setask.student_evaluation_task_id END) AS completed_count,
            ROUND(AVG(er.average_score), 2) AS avg_rating
        {$parts['from']} {$parts['where']}
        GROUP BY c.course_id, c.course_code, c.course_name, d.department_name
        ORDER BY c.course_code, c.course_name
    ", $parts["types"], $parts["params"]);
    foreach ($courseRows as &$row) {
        $assigned = (int)($row["assigned_count"] ?? 0);
        $completed = (int)($row["completed_count"] ?? 0);
        $row["completion_rate"] = $assigned > 0 ? round(($completed / $assigned) * 100, 1) : 0;
        $row["avg_rating"] = $row["avg_rating"] !== null ? round((float)$row["avg_rating"], 2) : 0;
    }
    unset($row);

    $sectionRows = fetch_all($db, "
        SELECT
            sec.section_id,
            sec.section_name,
            c.course_code,
            st.current_year_level AS year_level,
            COUNT(DISTINCT er.evaluation_response_id) AS responses,
            COUNT(DISTINCT setask.student_evaluation_task_id) AS assigned_count,
            COUNT(DISTINCT CASE WHEN setask.task_status = 'Submitted' THEN setask.student_evaluation_task_id END) AS completed_count,
            ROUND(AVG(er.average_score), 2) AS avg_rating
        {$parts['from']} {$parts['where']} AND sec.section_id IS NOT NULL
        GROUP BY sec.section_id, sec.section_name, c.course_code, st.current_year_level
        ORDER BY c.course_code, st.current_year_level, sec.section_name
    ", $parts["types"], $parts["params"]);
    foreach ($sectionRows as &$row) {
        $assigned = (int)($row["assigned_count"] ?? 0);
        $completed = (int)($row["completed_count"] ?? 0);
        $row["completion_rate"] = $assigned > 0 ? round(($completed / $assigned) * 100, 1) : 0;
        $row["avg_rating"] = $row["avg_rating"] !== null ? round((float)$row["avg_rating"], 2) : 0;
    }
    unset($row);

    $topFaculty = array_values(array_filter($facultyRows, fn($row) => ($row["avg_rating"] ?? null) !== null && (int)($row["responses"] ?? 0) > 0));
    usort($topFaculty, function ($a, $b) {
        $ratingCompare = ((float)$b["avg_rating"]) <=> ((float)$a["avg_rating"]);
        if ($ratingCompare !== 0) return $ratingCompare;
        return ((int)$b["responses"]) <=> ((int)$a["responses"]);
    });
    $topFaculty = array_slice($topFaculty, 0, 5);

    $scope = report_scope_labels($db, $filters);
    return [
        "stats" => $stats,
        "faculty" => $facultyRows,
        "criteria" => $criteriaRows,
        "departments" => $departmentRows,
        "courses" => $courseRows,
        "sections" => $sectionRows,
        "top_faculty" => $topFaculty,
        "scope" => $scope,
    ];
}

function get_dropdowns(mysqli $db): array
{
    return [
        "academic_years" => fetch_all($db, "SELECT academic_year_id, academic_year_name FROM academic_year ORDER BY start_date DESC, academic_year_id DESC"),
        "terms" => fetch_all($db, "SELECT t.term_id, t.term_name, ay.academic_year_name FROM term t INNER JOIN academic_year ay ON ay.academic_year_id = t.academic_year_id ORDER BY ay.start_date DESC, t.start_date DESC, t.term_id DESC"),
        "departments" => fetch_all($db, "SELECT department_id, department_code, department_name FROM department WHERE department_status <> 'Inactive' ORDER BY department_name"),
        "courses" => fetch_all($db, "SELECT course_id, department_id, course_code, course_name FROM course WHERE course_status <> 'Inactive' ORDER BY course_code, course_name"),
        "sections" => fetch_all($db, "SELECT section_id, course_id, year_level, section_name FROM section WHERE section_status <> 'Inactive' ORDER BY year_level, section_name"),
        "subjects" => fetch_all($db, "SELECT subject_id, department_id, subject_code, subject_title FROM subject WHERE subject_status <> 'Inactive' ORDER BY subject_code, subject_title"),
        "faculty" => fetch_all($db, "SELECT faculty_id, faculty_number, full_name FROM faculty WHERE faculty_status <> 'Inactive' ORDER BY full_name"),
    ];
}

function latest_term_badge(mysqli $db, array $filters): string
{
    $row = null;
    if (!empty($filters["term_id"])) {
        $row = fetch_one($db, "SELECT t.term_name, ay.academic_year_name FROM term t INNER JOIN academic_year ay ON ay.academic_year_id = t.academic_year_id WHERE t.term_id = ?", "i", [(int)$filters["term_id"]]);
    }
    if (!$row) {
        $row = fetch_one($db, "SELECT t.term_name, ay.academic_year_name FROM term t INNER JOIN academic_year ay ON ay.academic_year_id = t.academic_year_id ORDER BY FIELD(t.term_status, 'Active','Inactive','Closed','Archived'), t.start_date DESC, t.term_id DESC LIMIT 1");
    }
    if (!$row) {
        return "No Semester";
    }
    return normalize_term_label((string)$row["term_name"]) . " " . str_replace(" - ", "-", (string)$row["academic_year_name"]);
}

function output_csv_report(mysqli $db, array $data, array $filters): void
{
    log_generated_report($db, current_admin_id($db), $filters, "Excel");
    $filename = "evaluation_report_" . date("Ymd_His") . ".csv";
    header("Content-Type: text/csv; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"{$filename}\"");
    $out = fopen("php://output", "w");
    fputcsv($out, ["Student Evaluation for Teacher", $filters["report_type"]]);
    fputcsv($out, ["Generated", date("Y-m-d H:i:s")]);
    fputcsv($out, []);
    fputcsv($out, ["Report Scope"]);
    foreach ($data["scope"] as $key => $value) {
        fputcsv($out, [ucwords(str_replace("_", " ", $key)), $value]);
    }
    fputcsv($out, []);
    fputcsv($out, ["Overall Statistics"]);
    fputcsv($out, ["Total Faculty", "Average Rating", "Total Responses", "Completion Rate", "Total Students", "Pending Evaluations", "Total Assigned"]);
    fputcsv($out, [
        (int)$data["stats"]["total_faculty"],
        number_format((float)$data["stats"]["average_rating"], 2),
        (int)$data["stats"]["total_responses"],
        number_format((float)$data["stats"]["completion_rate"], 1) . "%",
        (int)$data["stats"]["total_students"],
        (int)$data["stats"]["pending_tasks"],
        (int)$data["stats"]["total_assigned"],
    ]);
    fputcsv($out, []);
    if ($filters["report_type"] !== "Criteria Performance Summary") {
        fputcsv($out, ["Faculty Performance Summary"]);
        fputcsv($out, ["Faculty ID", "Faculty Name", "College", "Courses", "Responses", "Average Rating", "Performance", "Completion"]);
        foreach ($data["faculty"] as $row) {
            fputcsv($out, [$row["faculty_number"], $row["faculty_name"], $row["department_name"], $row["subjects"], $row["responses"], $row["avg_rating"], $row["performance"], $row["completion_rate"] . "%"]);
        }
    }
    if ($filters["report_type"] !== "Faculty Performance Summary") {
        fputcsv($out, []);
        fputcsv($out, ["Criteria Performance Summary"]);
        fputcsv($out, ["Criteria", "Weight", "Highest Score", "Average Score", "Lowest Score", "Responses"]);
        foreach ($data["criteria"] as $row) {
            fputcsv($out, [$row["category_name"], $row["weight_percent"] . "%", $row["highest_score"], $row["average_score"], $row["lowest_score"], $row["response_count"]]);
        }
    }
    fclose($out);
    exit;
}

function release_scope_type_from_filters(array $filters): string
{
    if (!empty($filters["faculty_id"])) return "Faculty";
    if (!empty($filters["subject_id"])) return "Subject";
    if (!empty($filters["section_id"])) return "Section";
    if (!empty($filters["year_level"])) return "Year Level";
    if (!empty($filters["course_id"])) return "Course";
    if (!empty($filters["department_id"])) return "Department";
    return "All";
}

function completed_period_ids_for_release(mysqli $db, array $filters): array
{
    $where = ["eps.scope_status = 'Completed'"];
    $types = "";
    $params = [];
    if (!empty($filters["academic_year_id"])) {
        $where[] = "ay.academic_year_id = ?"; $types .= "i"; $params[] = (int)$filters["academic_year_id"];
    }
    if (!empty($filters["term_id"])) {
        $where[] = "t.term_id = ?"; $types .= "i"; $params[] = (int)$filters["term_id"];
    }
    if (!empty($filters["department_id"])) {
        $where[] = "(eps.scope_type = 'All' OR eps.department_id = ? OR eps.course_id IN (SELECT course_id FROM course WHERE department_id = ?))";
        $types .= "ii"; $params[] = (int)$filters["department_id"]; $params[] = (int)$filters["department_id"];
    }
    if (!empty($filters["course_id"])) {
        $where[] = "(eps.scope_type = 'All' OR eps.course_id = ? OR eps.section_id IN (SELECT section_id FROM section WHERE course_id = ?))";
        $types .= "ii"; $params[] = (int)$filters["course_id"]; $params[] = (int)$filters["course_id"];
    }
    if (!empty($filters["year_level"])) {
        $where[] = "(eps.scope_type = 'All' OR eps.year_level = ? OR eps.section_id IN (SELECT section_id FROM section WHERE year_level = ?))";
        $types .= "ii"; $params[] = (int)$filters["year_level"]; $params[] = (int)$filters["year_level"];
    }
    if (!empty($filters["section_id"])) {
        $where[] = "(eps.scope_type = 'All' OR eps.section_id = ?)";
        $types .= "i"; $params[] = (int)$filters["section_id"];
    }
    $sql = "SELECT DISTINCT eps.evaluation_period_id
        FROM evaluation_period_scope eps
        INNER JOIN evaluation_period ep ON ep.evaluation_period_id = eps.evaluation_period_id
        INNER JOIN term t ON t.term_id = ep.term_id
        INNER JOIN academic_year ay ON ay.academic_year_id = t.academic_year_id
        WHERE " . implode(" AND ", $where) . "
        ORDER BY eps.evaluation_period_id DESC";
    $rows = fetch_all($db, $sql, $types, $params);
    return array_map(fn($r) => (int)$r["evaluation_period_id"], $rows);
}

function handle_release_results(mysqli $db, array $filters): void
{
    if (!db_table_exists($db, "evaluation_period_scope")) {
        set_flash("Release failed because evaluation period scope history is missing.", "error");
        redirect_self();
    }

    $periodIds = completed_period_ids_for_release($db, $filters);
    if (!$periodIds) {
        set_flash("Release failed. Only completed evaluation periods can be released.", "error");
        redirect_self();
    }

    $adminId = current_admin_id($db);
    $scopeType = release_scope_type_from_filters($filters);
    $releasedCount = 0;
    $db->begin_transaction();
    try {
        foreach ($periodIds as $periodId) {
            $releaseName = $filters["report_type"] . " Release " . date("Y-m-d H:i:s");
            execute_stmt($db, "INSERT INTO evaluation_result_release (
                evaluation_period_id, release_name, release_status, release_scope_type, release_department_id,
                release_course_id, release_year_level, release_section_id, release_subject_id, release_faculty_id,
                release_academic_year_id, release_term_id, released_by_admin_id, released_at, release_note
            ) VALUES (?, ?, 'Released', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'Released from Reports page')", "issiiiiiiiii", [
                $periodId,
                $releaseName,
                $scopeType,
                (int)($filters["department_id"] ?: 0) ?: null,
                (int)($filters["course_id"] ?: 0) ?: null,
                (int)($filters["year_level"] ?: 0) ?: null,
                (int)($filters["section_id"] ?: 0) ?: null,
                (int)($filters["subject_id"] ?: 0) ?: null,
                (int)($filters["faculty_id"] ?: 0) ?: null,
                (int)($filters["academic_year_id"] ?: 0) ?: null,
                (int)($filters["term_id"] ?: 0) ?: null,
                $adminId,
            ]);
            $releaseId = (int)$db->insert_id;

            $releaseFilters = $filters;
            $releaseFilters["term_id"] = 0;
            $releaseFilters["academic_year_id"] = 0;
            $parts = build_base_parts($db, $releaseFilters);
            $where = $parts["where"] . " AND ep.evaluation_period_id = ? AND ta.teaching_assignment_id IS NOT NULL";
            $types = $parts["types"] . "i";
            $params = array_merge($parts["params"], [$periodId]);
            $assignments = fetch_all($db, "SELECT DISTINCT ta.teaching_assignment_id {$parts['from']} {$where}", $types, $params);
            foreach ($assignments as $assignment) {
                try {
                    execute_stmt($db, "CALL sp_compute_faculty_result(?, ?, ?)", "iii", [$periodId, (int)$assignment["teaching_assignment_id"], $releaseId]);
                    while ($db->more_results() && $db->next_result()) { $db->store_result(); }
                } catch (Throwable $inner) {
                    continue;
                }
                $releasedCount++;
            }
        }
        $db->commit();
        set_flash("Results released successfully for {$releasedCount} faculty assignment record(s).", "success");
    } catch (Throwable $e) {
        $db->rollback();
        set_flash("Release failed: " . $e->getMessage(), "error");
    }
    redirect_self();
}

ensure_report_support($db);
$filters = get_filters();
$action = (string)($_GET["action"] ?? "");

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["form_action"] ?? "") === "release_results") {
    $filters = [
        "report_type" => (string)($_POST["report_type"] ?? "Overall Summary"),
        "term_id" => (int)($_POST["term_id"] ?? 0),
        "academic_year_id" => (int)($_POST["academic_year_id"] ?? 0),
        "department_id" => (int)($_POST["department_id"] ?? 0),
        "course_id" => (int)($_POST["course_id"] ?? 0),
        "year_level" => (int)($_POST["year_level"] ?? 0),
        "section_id" => (int)($_POST["section_id"] ?? 0),
        "subject_id" => (int)($_POST["subject_id"] ?? 0),
        "faculty_id" => (int)($_POST["faculty_id"] ?? 0),
    ];
    handle_release_results($db, $filters);
}

$data = get_report_data($db, $filters);
$dropdowns = get_dropdowns($db);
$termBadge = latest_term_badge($db, $filters);
$flash = get_flash();

if ($action === "download_excel") {
    output_csv_report($db, $data, $filters);
}

if ($action === "download_pdf") {
    log_generated_report($db, current_admin_id($db), $filters, "PDF");
}

if ($action === "print") {
    log_generated_report($db, current_admin_id($db), $filters, "Print");
}

$isPrintableReport = in_array($action, ["print", "download_pdf"], true);

$baseQuery = $_GET;
unset($baseQuery["action"]);
function action_url(array $baseQuery, string $action): string
{
    $query = array_merge($baseQuery, ["action" => $action]);
    return basename(__FILE__) . "?" . http_build_query($query);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports</title>
    <link rel="stylesheet" href="report-page.css?v=<?php echo time(); ?>">
</head>
<body class="report-page <?php echo $isPrintableReport ? 'print-page' : ''; ?>">
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
            <a class="nav-link" href="submission_monitoring.php"><?php echo icon_svg("clipboard"); ?> Submission Monitoring</a>
            <a class="nav-link active" href="report_page.php"><?php echo icon_svg("reports"); ?> Reports</a>
            <a class="nav-link" href="announcement_page.php"><?php echo icon_svg("announcement"); ?> Announcements</a>
            <a class="nav-link" href="settings.php"><?php echo icon_svg("settings"); ?> Settings</a>
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
                    <h1>Reports</h1>
                    <p>Generate and export evaluation reports</p>
                </div>
                <div class="term-badge"><strong><?php echo e($termBadge); ?></strong></div>
            </div>

            <?php if ($flash): ?>
                <div class="toast-modal <?php echo e($flash['type']); ?>" id="flashToast"><?php echo e($flash['message']); ?></div>
            <?php endif; ?>

            <form class="report-panel" id="reportFilterForm" method="get" action="<?php echo e(basename(__FILE__)); ?>">
                <div class="section-title">
                    <?php echo icon_svg("filter"); ?>
                    <h2>Report Configuration</h2>
                </div>

                <div class="filter-grid">
                    <div class="form-group full">
                        <label for="report_type">Report Type</label>
                        <select id="report_type" name="report_type">
                            <?php foreach (["Overall Summary", "Faculty Performance Summary", "Criteria Performance Summary"] as $type): ?>
                                <option value="<?php echo e($type); ?>" <?php echo $filters["report_type"] === $type ? "selected" : ""; ?>><?php echo e($type); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="term_id">Semester</label>
                        <select id="term_id" name="term_id">
                            <option value="0">All Semesters</option>
                            <?php foreach ($dropdowns["terms"] as $row): ?>
                                <option value="<?php echo (int)$row['term_id']; ?>" <?php echo $filters["term_id"] === (int)$row["term_id"] ? "selected" : ""; ?>><?php echo e(normalize_term_label((string)$row["term_name"]) . " " . str_replace(" - ", "-", (string)$row["academic_year_name"])); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="academic_year_id">Academic Year</label>
                        <select id="academic_year_id" name="academic_year_id">
                            <option value="0">All Academic Years</option>
                            <?php foreach ($dropdowns["academic_years"] as $row): ?>
                                <option value="<?php echo (int)$row['academic_year_id']; ?>" <?php echo $filters["academic_year_id"] === (int)$row["academic_year_id"] ? "selected" : ""; ?>><?php echo e(str_replace(" - ", "-", (string)$row["academic_year_name"])); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="department_id">College</label>
                        <select id="department_id" name="department_id">
                            <option value="0">All Colleges</option>
                            <?php foreach ($dropdowns["departments"] as $row): ?>
                                <option value="<?php echo (int)$row['department_id']; ?>" <?php echo $filters["department_id"] === (int)$row["department_id"] ? "selected" : ""; ?>><?php echo e($row["department_name"]); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="course_id">Program</label>
                        <select id="course_id" name="course_id">
                            <option value="0">All Programs</option>
                            <?php foreach ($dropdowns["courses"] as $row): ?>
                                <option value="<?php echo (int)$row['course_id']; ?>" data-department="<?php echo (int)$row['department_id']; ?>" <?php echo $filters["course_id"] === (int)$row["course_id"] ? "selected" : ""; ?>><?php echo e($row["course_code"] . " - " . $row["course_name"]); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="year_level">Year Level</label>
                        <select id="year_level" name="year_level">
                            <option value="0">All Year Levels</option>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $filters["year_level"] === $i ? "selected" : ""; ?>><?php echo e(ordinal($i)); ?> Year</option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="section_id">Section</label>
                        <select id="section_id" name="section_id">
                            <option value="0">All Sections</option>
                            <?php foreach ($dropdowns["sections"] as $row): ?>
                                <option value="<?php echo (int)$row['section_id']; ?>" data-course="<?php echo (int)$row['course_id']; ?>" data-year="<?php echo (int)$row['year_level']; ?>" <?php echo $filters["section_id"] === (int)$row["section_id"] ? "selected" : ""; ?>><?php echo e($row["section_name"]); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="subject_id">Course</label>
                        <select id="subject_id" name="subject_id">
                            <option value="0">All Courses</option>
                            <?php foreach ($dropdowns["subjects"] as $row): ?>
                                <option value="<?php echo (int)$row['subject_id']; ?>" data-department="<?php echo (int)$row['department_id']; ?>" <?php echo $filters["subject_id"] === (int)$row["subject_id"] ? "selected" : ""; ?>><?php echo e($row["subject_code"] . " - " . $row["subject_title"]); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="faculty_id">Faculty</label>
                        <select id="faculty_id" name="faculty_id">
                            <option value="0">All Faculty</option>
                            <?php foreach ($dropdowns["faculty"] as $row): ?>
                                <option value="<?php echo (int)$row['faculty_id']; ?>" <?php echo $filters["faculty_id"] === (int)$row["faculty_id"] ? "selected" : ""; ?>><?php echo e($row["full_name"]); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="inline-actions">
                        <a class="btn-clear" href="<?php echo e(basename(__FILE__)); ?>">Clear</a>
                    </div>
                </div>
            </form>

            <section class="stats-panel">
                <div class="section-title">
                    <?php echo icon_svg("reports"); ?>
                    <h2>Overall Statistics</h2>
                </div>

                <div class="stats-grid">
                    <article class="stat-card">
                        <div><span>Total Faculty</span><strong class="blue"><?php echo (int)$data["stats"]["total_faculty"]; ?></strong></div>
                        <div class="stat-icon blue-bg"><?php echo icon_svg("faculty"); ?></div>
                    </article>
                    <article class="stat-card">
                        <div><span>Avg Rating</span><strong class="green"><?php echo number_format((float)$data["stats"]["average_rating"], 1); ?></strong></div>
                        <div class="stat-icon green-bg"><?php echo icon_svg("check"); ?></div>
                    </article>
                    <article class="stat-card">
                        <div><span>Total Responses</span><strong class="purple"><?php echo (int)$data["stats"]["total_responses"]; ?></strong></div>
                        <div class="stat-icon blue-bg"><?php echo icon_svg("reports"); ?></div>
                    </article>
                    <article class="stat-card">
                        <div><span>Completion Rate</span><strong class="orange"><?php echo number_format((float)$data["stats"]["completion_rate"], 1); ?>%</strong></div>
                        <div class="stat-icon orange-bg"><?php echo icon_svg("trend"); ?></div>
                    </article>
                </div>

                <div class="stats-grid secondary">
                    <article class="stat-card">
                        <div><span>Total Students</span><strong class="blue"><?php echo (int)$data["stats"]["total_students"]; ?></strong></div>
                        <div class="stat-icon purple-bg"><?php echo icon_svg("students"); ?></div>
                    </article>
                    <article class="stat-card">
                        <div><span>Pending Evaluations</span><strong class="orange"><?php echo (int)$data["stats"]["pending_tasks"]; ?></strong></div>
                        <div class="stat-icon orange-bg"><?php echo icon_svg("file"); ?></div>
                    </article>
                    <article class="stat-card">
                        <div><span>Total Assigned</span><strong class="green"><?php echo (int)$data["stats"]["total_assigned"]; ?></strong></div>
                        <div class="stat-icon green-bg"><?php echo icon_svg("clipboard"); ?></div>
                    </article>
                </div>
            </section>

            <section class="report-actions">
                <a class="action-button preview" href="<?php echo e(action_url($baseQuery, 'preview')); ?>"><?php echo icon_svg("eye"); ?> Preview Report</a>
                <a class="action-button pdf" target="_blank" href="<?php echo e(action_url($baseQuery, 'download_pdf')); ?>"><?php echo icon_svg("download"); ?> Download PDF</a>
                <a class="action-button excel" href="<?php echo e(action_url($baseQuery, 'download_excel')); ?>"><?php echo icon_svg("download"); ?> Download Excel</a>
                <a class="action-button print" target="_blank" href="<?php echo e(action_url($baseQuery, 'print')); ?>"><?php echo icon_svg("print"); ?> Print Report</a>
                <button class="action-button release" type="button" onclick="openReleaseModal()"><?php echo icon_svg("check"); ?> Release Result</button>
            </section>

            <?php if ($action === "preview" || $isPrintableReport): ?>
                <?php render_preview_section($data, $filters); ?>
            <?php endif; ?>
        </div>
    </main>

    <div class="modal-overlay" id="releaseModal">
        <div class="modal-box">
            <div class="modal-header">
                <h2>Release Evaluation Results</h2>
                <button class="modal-close" type="button" onclick="closeReleaseModal()"><?php echo icon_svg("close"); ?></button>
            </div>
            <form method="post" action="<?php echo e(basename(__FILE__) . '?' . http_build_query($baseQuery)); ?>">
                <div class="modal-body">
                    <input type="hidden" name="form_action" value="release_results">
                    <?php foreach ($filters as $key => $value): ?>
                        <input type="hidden" name="<?php echo e($key); ?>" value="<?php echo e($value); ?>">
                    <?php endforeach; ?>
                    <div class="release-summary">
                        <strong>Release Scope:</strong><br>
                        <?php echo e($data["scope"]["department"]); ?>, <?php echo e($data["scope"]["course"]); ?>, <?php echo e($data["scope"]["year_level"]); ?>, <?php echo e($data["scope"]["section"]); ?><br><br>
                        Only completed evaluation periods that match this scope will be released to faculty members.
                    </div>
                    <p>Active or incomplete evaluation periods will not be included in the release.</p>
                </div>
                <div class="modal-actions">
                    <button class="btn-secondary" type="button" onclick="closeReleaseModal()">Cancel</button>
                    <button class="btn-primary" type="submit">Confirm Release</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const filterForm = document.getElementById('reportFilterForm');
        const filterSelects = filterForm.querySelectorAll('select');
        const departmentSelect = document.getElementById('department_id');
        const courseSelect = document.getElementById('course_id');
        const yearSelect = document.getElementById('year_level');
        const sectionSelect = document.getElementById('section_id');
        const subjectSelect = document.getElementById('subject_id');
        let submitTimer = null;

        function autoSubmitFilters() {
            clearTimeout(submitTimer);
            submitTimer = setTimeout(() => filterForm.submit(), 250);
        }

        function filterDependentOptions() {
            const departmentId = departmentSelect.value;
            const courseId = courseSelect.value;
            const yearLevel = yearSelect.value;

            courseSelect.querySelectorAll('option[data-department]').forEach(option => {
                option.hidden = departmentId !== '0' && option.dataset.department !== departmentId;
            });

            subjectSelect.querySelectorAll('option[data-department]').forEach(option => {
                option.hidden = departmentId !== '0' && option.dataset.department !== departmentId;
            });

            sectionSelect.querySelectorAll('option[data-course]').forEach(option => {
                const courseMatches = courseId === '0' || option.dataset.course === courseId;
                const yearMatches = yearLevel === '0' || option.dataset.year === yearLevel;
                option.hidden = !(courseMatches && yearMatches);
            });
        }

        filterSelects.forEach(select => {
            select.addEventListener('change', () => {
                filterDependentOptions();
                autoSubmitFilters();
            });
        });
        filterDependentOptions();

        function openReleaseModal() {
            document.getElementById('releaseModal').classList.add('open');
        }

        function closeReleaseModal() {
            document.getElementById('releaseModal').classList.remove('open');
        }

        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', function (event) {
                if (event.target === this) closeReleaseModal();
            });
        });

        const flashToast = document.getElementById('flashToast');
        if (flashToast) {
            setTimeout(() => flashToast.classList.add('hide'), 2600);
            setTimeout(() => flashToast.remove(), 3200);
        }

        <?php if ($isPrintableReport): ?>
        document.title = 'evaluation_report_<?php echo date('Ymd_His'); ?>';
        window.addEventListener('load', () => setTimeout(() => window.print(), 500));
        <?php endif; ?>
    </script>

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
<?php
function print_number($value, int $decimals = 0): string
{
    if ($value === null || $value === '') {
        return $decimals > 0 ? number_format(0, $decimals) : '0';
    }
    return number_format((float)$value, $decimals);
}

function report_weighted_average(array $criteria): float
{
    $weightedTotal = 0.0;
    $weightSum = 0.0;
    foreach ($criteria as $row) {
        $weight = (float)($row['weight_percent'] ?? 0);
        $average = (float)($row['average_score'] ?? 0);
        if ($weight <= 0) {
            continue;
        }
        $weightedTotal += $average * $weight;
        $weightSum += $weight;
    }
    return $weightSum > 0 ? round($weightedTotal / $weightSum, 2) : 0.0;
}

function render_preview_section(array $data, array $filters): void
{
    render_admin_report_template($data, $filters);
}

function render_admin_report_template(array $data, array $filters): void
{
    $stats = $data['stats'];
    $scope = $data['scope'];
    $reportType = (string)$filters['report_type'];
    $generatedDate = date('F d, Y');
    $generatedDateTime = date('F d, Y \a\t h:i A');
    $weightedAverage = report_weighted_average($data['criteria']);
    $showFaculty = $reportType !== 'Criteria Performance Summary';
    $showCriteria = $reportType !== 'Faculty Performance Summary';
    ?>
    <section class="admin-report-template" id="adminReportTemplate">
        <header class="report-document-header avoid-break">
            <div class="report-header-left">
                <img src="../img/eastgate_college_logo.png" alt="Eastgate College Logo">
                <div>
                    <h2>Student Evaluation for Teacher</h2>
                    <h1><?php echo e($reportType); ?></h1>
                    <p><?php echo e($scope['semester']); ?> • <?php echo e($scope['academic_year']); ?></p>
                    <p>Generated: <?php echo e($generatedDateTime); ?></p>
                </div>
            </div>
        </header>

        <section class="report-meta-grid avoid-break">
            <div class="meta-row"><span>Admin</span><strong>System Administrator</strong></div>
            <div class="meta-row"><span>Academic Year</span><strong><?php echo e($scope['academic_year']); ?></strong></div>
            <div class="meta-row"><span>College</span><strong><?php echo e($scope['department']); ?></strong></div>
            <div class="meta-row"><span>Semester</span><strong><?php echo e($scope['semester']); ?></strong></div>
            <div class="meta-row"><span>Year Level</span><strong><?php echo e($scope['year_level']); ?></strong></div>
            <div class="meta-row"><span>Program</span><strong><?php echo e($scope['course']); ?></strong></div>
            <div class="meta-row"><span>Course</span><strong><?php echo e($scope['subject']); ?></strong></div>
            <div class="meta-row"><span>Section</span><strong><?php echo e($scope['section']); ?></strong></div>
        </section>

        <section class="print-section avoid-break">
            <h3>Overall Statistics</h3>
            <div class="overall-stat-grid">
                <div><strong><?php echo print_number($stats['total_faculty'] ?? 0); ?></strong><span>Total Faculty</span></div>
                <div><strong><?php echo print_number($stats['average_rating'] ?? 0, 2); ?></strong><span>Avg Rating</span></div>
                <div><strong><?php echo print_number($stats['total_responses'] ?? 0); ?></strong><span>Total Responses</span></div>
                <div><strong><?php echo print_number($stats['completion_rate'] ?? 0, 1); ?>%</strong><span>Completion Rate</span></div>
                <div><strong><?php echo print_number($stats['total_students'] ?? 0); ?></strong><span>Total Students</span></div>
                <div><strong><?php echo print_number($stats['pending_tasks'] ?? 0); ?></strong><span>Pending Evaluations</span></div>
                <div><strong><?php echo print_number($stats['total_assigned'] ?? 0); ?></strong><span>Total Assigned</span></div>
            </div>
        </section>

        <?php if ($showFaculty): ?>
            <section class="print-section">
                <h3>Faculty Performance Summary</h3>
                <div class="print-table-wrap">
                    <table class="print-report-table faculty-table">
                        <thead>
                            <tr>
                                <th>Faculty Name</th>
                                <th>Colleges</th>
                                <th>Courses</th>
                                <th>Responses</th>
                                <th>Assigned</th>
                                <th>Avg Rating</th>
                                <th>Completion Rating</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$data['faculty']): ?>
                                <tr><td colspan="7" class="empty-cell">No faculty performance records found for the selected filters.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($data['faculty'] as $row): ?>
                                <tr>
                                    <td><?php echo e($row['faculty_name']); ?><br><small><?php echo e($row['faculty_number']); ?></small></td>
                                    <td><?php echo e($row['department_name'] ?: 'No College'); ?></td>
                                    <td><?php echo e($row['subjects'] ?: 'No Course'); ?></td>
                                    <td><?php echo (int)($row['responses'] ?? 0); ?></td>
                                    <td><?php echo (int)($row['assigned_count'] ?? 0); ?></td>
                                    <td><?php echo $row['avg_rating'] !== null ? number_format((float)$row['avg_rating'], 2) : '0.00'; ?></td>
                                    <td><?php echo number_format((float)($row['completion_rate'] ?? 0), 1); ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($showCriteria): ?>
            <section class="print-section">
                <h3>Criteria Performance Summary</h3>
                <div class="print-table-wrap">
                    <table class="print-report-table compact-table criteria-summary-table">
                        <thead>
                            <tr>
                                <th>Criteria</th>
                                <th>Weight</th>
                                <th>Highest Score</th>
                                <th>Average Score</th>
                                <th>Lowest Score</th>
                                <th>Rating</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$data['criteria']): ?>
                                <tr><td colspan="6" class="empty-cell">No criteria performance records found for the selected filters.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($data['criteria'] as $row): ?>
                                <tr>
                                    <td><?php echo e($row['category_name']); ?></td>
                                    <td><?php echo number_format((float)($row['weight_percent'] ?? 0), 0); ?>%</td>
                                    <td><?php echo number_format((float)($row['highest_score'] ?? 0), 2); ?></td>
                                    <td><?php echo number_format((float)($row['average_score'] ?? 0), 2); ?></td>
                                    <td><?php echo number_format((float)($row['lowest_score'] ?? 0), 2); ?></td>
                                    <td><?php echo e(performance_label((float)($row['average_score'] ?? 0))); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="weighted-row">
                                <td colspan="3">WEIGHTED AVERAGE</td>
                                <td><?php echo number_format($weightedAverage, 2); ?></td>
                                <td colspan="2"><?php echo e(performance_label($weightedAverage)); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>

        <section class="print-section">
            <h3>Colleges Summary</h3>
            <div class="print-table-wrap">
                <table class="print-report-table compact-table">
                    <thead>
                        <tr>
                            <th>Colleges</th>
                            <th>Department Head</th>
                            <th>Responses</th>
                            <th>Total Assigned</th>
                            <th>Completion Rate</th>
                            <th>Avg Rating</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($data['departments'])): ?>
                            <tr><td colspan="6" class="empty-cell">No college records found.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($data['departments'] as $row): ?>
                            <tr>
                                <td><?php echo e($row['department_name']); ?></td>
                                <td>Not specified</td>
                                <td><?php echo (int)($row['responses'] ?? 0); ?></td>
                                <td><?php echo (int)($row['assigned_count'] ?? 0); ?></td>
                                <td><?php echo number_format((float)($row['completion_rate'] ?? 0), 1); ?>%</td>
                                <td><?php echo number_format((float)($row['avg_rating'] ?? 0), 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="print-section">
            <h3>Program Summary</h3>
            <div class="print-table-wrap">
                <table class="print-report-table compact-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Program Name</th>
                            <th>College</th>
                            <th>Responses</th>
                            <th>Assigned</th>
                            <th>Comp. Rate</th>
                            <th>Avg Rating</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($data['courses'])): ?>
                            <tr><td colspan="7" class="empty-cell">No program records found.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($data['courses'] as $row): ?>
                            <tr>
                                <td><?php echo e($row['course_code']); ?></td>
                                <td><?php echo e($row['course_name']); ?></td>
                                <td><?php echo e($row['department_name']); ?></td>
                                <td><?php echo (int)($row['responses'] ?? 0); ?></td>
                                <td><?php echo (int)($row['assigned_count'] ?? 0); ?></td>
                                <td><?php echo number_format((float)($row['completion_rate'] ?? 0), 1); ?>%</td>
                                <td><?php echo number_format((float)($row['avg_rating'] ?? 0), 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="print-section">
            <h3>Section Summary</h3>
            <div class="print-table-wrap">
                <table class="print-report-table compact-table">
                    <thead>
                        <tr>
                            <th>Section</th>
                            <th>Program ID</th>
                            <th>Year Level</th>
                            <th>Responses</th>
                            <th>Assigned</th>
                            <th>Comp. Rate</th>
                            <th>Avg Rating</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($data['sections'])): ?>
                            <tr><td colspan="7" class="empty-cell">No section records found.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($data['sections'] as $row): ?>
                            <tr>
                                <td><?php echo e($row['section_name']); ?></td>
                                <td><?php echo e($row['course_code'] ?: 'N/A'); ?></td>
                                <td><?php echo ordinal((int)($row['year_level'] ?? 0)); ?> Year</td>
                                <td><?php echo (int)($row['responses'] ?? 0); ?></td>
                                <td><?php echo (int)($row['assigned_count'] ?? 0); ?></td>
                                <td><?php echo number_format((float)($row['completion_rate'] ?? 0), 1); ?>%</td>
                                <td><?php echo number_format((float)($row['avg_rating'] ?? 0), 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <?php if (!empty($data['top_faculty'])): ?>
            <section class="print-section optional-section avoid-break">
                <h3>Top Performing Faculty</h3>
                <div class="print-table-wrap">
                    <table class="print-report-table compact-table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Faculty Name</th>
                                <th>College</th>
                                <th>Avg Rating</th>
                                <th>Rating</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['top_faculty'] as $index => $row): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo e($row['faculty_name']); ?></td>
                                    <td><?php echo e($row['department_name'] ?: 'No College'); ?></td>
                                    <td><?php echo number_format((float)($row['avg_rating'] ?? 0), 2); ?></td>
                                    <td><?php echo e($row['performance']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>

        <section class="signature-section avoid-break">
            <div>
                <span class="signature-line"></span>
                <strong>Print name and Signature of Administrator</strong>
                <p>System Administrator</p>
            </div>
            <div>
                <span class="date-line"></span>
                <strong>Date Signed</strong>
                <p><?php echo e($generatedDate); ?></p>
            </div>
        </section>

        <section class="important-notice avoid-break">
            <h3>Important Notice</h3>
            <p>This report was generated by the System Administrator for official student evaluation monitoring and reporting purposes.</p>
            <p>Only released and completed evaluation results should be used for faculty result viewing. Student identity and individual response details must remain confidential.</p>
            <p>Generated reports include timestamp and administrator information for record keeping.</p>
        </section>
    </section>
    <?php
}
