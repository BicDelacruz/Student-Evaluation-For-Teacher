<?php
declare(strict_types=1);

session_start();

require_once dirname(__DIR__) . "/database_connector.php";

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function clean_string(string $value): string
{
    return trim($value);
}

function redirect_panel(string $panel, string $message, string $type = "success"): void
{
    $_SESSION["academic_flash_message"] = $message;
    $_SESSION["academic_flash_type"] = $type;

    header("Location: academic_structure.php?panel=" . urlencode($panel));
    exit;
}

function count_rows(PDO $pdo, string $sql, array $params = []): int
{
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    return (int) $statement->fetchColumn();
}

function fetch_one(PDO $pdo, string $sql, array $params = []): array|false
{
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    return $statement->fetch(PDO::FETCH_ASSOC);
}

function get_admin_id(PDO $pdo, int $user_id): int
{
    $statement = $pdo->prepare("
        SELECT admin_id
        FROM `admin`
        WHERE user_id = :user_id
        LIMIT 1
    ");

    $statement->execute([
        "user_id" => $user_id
    ]);

    return (int) $statement->fetchColumn();
}

function valid_status(string $status, array $allowed, string $default = "Active"): string
{
    return in_array($status, $allowed, true) ? $status : $default;
}

function valid_term_name(string $term_name): string
{
    $term_name = clean_string($term_name);

    $allowed_terms = [
        "First Semester",
        "Second Semester",
        "Summer"
    ];

    return in_array($term_name, $allowed_terms, true) ? $term_name : "First Semester";
}

function year_level_label(int $year_level): string
{
    return match ($year_level) {
        1 => "1st Year",
        2 => "2nd Year",
        3 => "3rd Year",
        4 => "4th Year",
        5 => "5th Year",
        6 => "6th Year",
        default => $year_level . "th Year"
    };
}

function find_or_create_term(PDO $pdo, int $academic_year_id, string $term_name): int
{
    $term_name = valid_term_name($term_name);

    if ($academic_year_id <= 0) {
        return 0;
    }

    $statement = $pdo->prepare("
        SELECT term_id
        FROM term
        WHERE academic_year_id = :academic_year_id
        AND term_name = :term_name
        LIMIT 1
    ");

    $statement->execute([
        "academic_year_id" => $academic_year_id,
        "term_name" => $term_name
    ]);

    $term_id = $statement->fetchColumn();

    if ($term_id) {
        return (int) $term_id;
    }

    $year_statement = $pdo->prepare("
        SELECT start_date, end_date
        FROM academic_year
        WHERE academic_year_id = :academic_year_id
        LIMIT 1
    ");

    $year_statement->execute([
        "academic_year_id" => $academic_year_id
    ]);

    $academic_year = $year_statement->fetch(PDO::FETCH_ASSOC);

    if (!$academic_year) {
        return 0;
    }

    $start_date = (string) $academic_year["start_date"];
    $end_date = (string) $academic_year["end_date"];

    if ($term_name === "Second Semester") {
        $start_year = (int) substr($start_date, 0, 4) + 1;
        $start_date = $start_year . "-01-10";
        $end_date = $start_year . "-05-30";
    }

    if ($term_name === "Summer") {
        $start_year = (int) substr($end_date, 0, 4);
        $start_date = $start_year . "-06-01";
        $end_date = $start_year . "-07-15";
    }

    $insert = $pdo->prepare("
        INSERT INTO term (
            academic_year_id,
            term_name,
            start_date,
            end_date,
            term_status
        )
        VALUES (
            :academic_year_id,
            :term_name,
            :start_date,
            :end_date,
            'Active'
        )
    ");

    $insert->execute([
        "academic_year_id" => $academic_year_id,
        "term_name" => $term_name,
        "start_date" => $start_date,
        "end_date" => $end_date
    ]);

    return (int) $pdo->lastInsertId();
}

function upsert_course_subject(PDO $pdo, int $course_id, int $subject_id, int $year_level, string $term_name): void
{
    $term_name = valid_term_name($term_name);

    $existing = fetch_one(
        $pdo,
        "
        SELECT course_subject_id
        FROM course_subject
        WHERE course_id = :course_id
        AND subject_id = :subject_id
        AND year_level = :year_level
        AND term_name = :term_name
        LIMIT 1
        ",
        [
            "course_id" => $course_id,
            "subject_id" => $subject_id,
            "year_level" => $year_level,
            "term_name" => $term_name
        ]
    );

    if ($existing) {
        $statement = $pdo->prepare("
            UPDATE course_subject
            SET
                is_required = 1,
                course_subject_status = 'Active'
            WHERE course_subject_id = :course_subject_id
        ");

        $statement->execute([
            "course_subject_id" => (int) $existing["course_subject_id"]
        ]);

        return;
    }

    $statement = $pdo->prepare("
        INSERT INTO course_subject (
            course_id,
            subject_id,
            year_level,
            term_name,
            is_required,
            course_subject_status
        )
        VALUES (
            :course_id,
            :subject_id,
            :year_level,
            :term_name,
            1,
            'Active'
        )
    ");

    $statement->execute([
        "course_id" => $course_id,
        "subject_id" => $subject_id,
        "year_level" => $year_level,
        "term_name" => $term_name
    ]);
}

function icon_svg(string $name): string
{
    $icons = [
        "dashboard" => '<svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>',
        "students" => '<svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
        "faculty" => '<svg viewBox="0 0 24 24"><path d="M18 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9.5" cy="7" r="4"></circle><path d="M20 8v6"></path><path d="M23 11h-6"></path></svg>',
        "assignment" => '<svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><path d="M20 8v6"></path><path d="M23 11h-6"></path></svg>',
        "book" => '<svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"/></svg>',
        "building" => '<svg viewBox="0 0 24 24"><path d="M4 21V8l8-5 8 5v13"/><path d="M9 21v-8h6v8"/><path d="M7 10h.01"/><path d="M17 10h.01"/></svg>',
        "cap" => '<svg viewBox="0 0 24 24"><path d="M22 10L12 5 2 10l10 5 10-5z"/><path d="M6 12v5c3 2 9 2 12 0v-5"/></svg>',
        "section" => '<svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6"/><path d="M16 11h6"/></svg>',
        "settings" => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1v.17a2 2 0 1 1-4 0V21a1.7 1.7 0 0 0-.4-1 1.7 1.7 0 0 0-1-.6 1.7 1.7 0 0 0-1.88.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.6-1 1.7 1.7 0 0 0-1-.4H2.83a2 2 0 1 1 0-4H3a1.7 1.7 0 0 0 1-.4 1.7 1.7 0 0 0 .6-1 1.7 1.7 0 0 0-.34-1.88l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.6 1.7 1.7 0 0 0 .4-1V2.83a2 2 0 1 1 4 0V3a1.7 1.7 0 0 0 .4 1 1.7 1.7 0 0 0 1 .6 1.7 1.7 0 0 0 1.88-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.4 9c.2.3.4.7.6 1h.17a2 2 0 1 1 0 4H20a1.7 1.7 0 0 0-.6 1z"></path></svg>',
        "clipboard" => '<svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"></path><rect x="9" y="3" width="6" height="4" rx="1"></rect><path d="M9 12h6"></path><path d="M9 16h6"></path></svg>',
        "chart" => '<svg viewBox="0 0 24 24"><path d="M3 3v18h18"></path><path d="M7 16V9"></path><path d="M12 16V5"></path><path d="M17 16v-3"></path></svg>',
        "megaphone" => '<svg viewBox="0 0 24 24"><path d="M3 11v2a2 2 0 0 0 2 2h2l5 4V5L7 9H5a2 2 0 0 0-2 2z"></path><path d="M16 9a5 5 0 0 1 0 6"></path></svg>',
        "moon" => '<svg viewBox="0 0 24 24"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"></path></svg>',
        "logout" => '<svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><path d="M16 17l5-5-5-5"></path><path d="M21 12H9"></path></svg>',
        "search" => '<svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg>',
        "eye" => '<svg viewBox="0 0 24 24"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>',
        "edit" => '<svg viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>',
        "trash" => '<svg viewBox="0 0 24 24"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>',
        "plus" => '<svg viewBox="0 0 24 24"><path d="M12 5v14"/><path d="M5 12h14"/></svg>'
    ];

    return $icons[$name] ?? "";
}

$allowed_panels = ["departments", "courses", "sections", "subjects"];
$active_panel = $_GET["panel"] ?? "departments";

if (!in_array($active_panel, $allowed_panels, true)) {
    $active_panel = "departments";
}

$admin_id = get_admin_id($pdo, $user_id);

try {
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $form_action = $_POST["form_action"] ?? "";

        if ($form_action === "add_department") {
            $department_code = strtoupper(clean_string($_POST["department_code"] ?? ""));
            $department_name = clean_string($_POST["department_name"] ?? "");
            $department_head_faculty_id = ($_POST["department_head_faculty_id"] ?? "") !== "" ? (int) $_POST["department_head_faculty_id"] : null;

            if ($department_code === "" || $department_name === "") {
                redirect_panel("departments", "College code and name are required.", "error");
            }

            $duplicate = count_rows(
                $pdo,
                "
                SELECT COUNT(*)
                FROM department
                WHERE department_code = :department_code
                OR department_name = :department_name
                ",
                [
                    "department_code" => $department_code,
                    "department_name" => $department_name
                ]
            );

            if ($duplicate > 0) {
                redirect_panel("departments", "College code or name already exists.", "error");
            }

            $statement = $pdo->prepare("
                INSERT INTO department (
                    department_code,
                    department_name,
                    department_head_faculty_id,
                    department_status
                )
                VALUES (
                    :department_code,
                    :department_name,
                    :department_head_faculty_id,
                    'Active'
                )
            ");

            $statement->execute([
                "department_code" => $department_code,
                "department_name" => $department_name,
                "department_head_faculty_id" => $department_head_faculty_id
            ]);

            redirect_panel("departments", "College {$department_code} successfully added!");
        }

        if ($form_action === "update_department") {
            $department_id = (int) ($_POST["department_id"] ?? 0);
            $department_code = strtoupper(clean_string($_POST["department_code"] ?? ""));
            $department_name = clean_string($_POST["department_name"] ?? "");
            $department_head_faculty_id = ($_POST["department_head_faculty_id"] ?? "") !== "" ? (int) $_POST["department_head_faculty_id"] : null;
            $department_status = valid_status($_POST["department_status"] ?? "Active", ["Active", "Inactive"], "Active");

            if ($department_id <= 0 || $department_code === "" || $department_name === "") {
                redirect_panel("departments", "Please complete all college fields.", "error");
            }

            $duplicate = count_rows(
                $pdo,
                "
                SELECT COUNT(*)
                FROM department
                WHERE department_id <> :department_id
                AND (
                    department_code = :department_code
                    OR department_name = :department_name
                )
                ",
                [
                    "department_id" => $department_id,
                    "department_code" => $department_code,
                    "department_name" => $department_name
                ]
            );

            if ($duplicate > 0) {
                redirect_panel("departments", "College code or name already exists.", "error");
            }

            $statement = $pdo->prepare("
                UPDATE department
                SET
                    department_code = :department_code,
                    department_name = :department_name,
                    department_head_faculty_id = :department_head_faculty_id,
                    department_status = :department_status
                WHERE department_id = :department_id
            ");

            $statement->execute([
                "department_id" => $department_id,
                "department_code" => $department_code,
                "department_name" => $department_name,
                "department_head_faculty_id" => $department_head_faculty_id,
                "department_status" => $department_status
            ]);

            redirect_panel("departments", "College {$department_code} successfully updated!");
        }

        if ($form_action === "delete_department") {
            $department_id = (int) ($_POST["department_id"] ?? 0);

            if ($department_id <= 0) {
                redirect_panel("departments", "Invalid college selected.", "error");
            }

            $linked_records =
                count_rows($pdo, "SELECT COUNT(*) FROM course WHERE department_id = :id", ["id" => $department_id]) +
                count_rows($pdo, "SELECT COUNT(*) FROM subject WHERE department_id = :id", ["id" => $department_id]) +
                count_rows($pdo, "SELECT COUNT(*) FROM faculty WHERE department_id = :id", ["id" => $department_id]);

            if ($linked_records > 0) {
                $statement = $pdo->prepare("
                    UPDATE department
                    SET department_status = 'Inactive'
                    WHERE department_id = :department_id
                ");

                $statement->execute([
                    "department_id" => $department_id
                ]);

                redirect_panel("departments", "You cannot delete an entity with record inside. This will mark as inactive.", "warning");
            }

            $statement = $pdo->prepare("
                DELETE FROM department
                WHERE department_id = :department_id
            ");

            $statement->execute([
                "department_id" => $department_id
            ]);

            redirect_panel("departments", "College successfully deleted!");
        }

        if ($form_action === "add_course") {
            $department_id = (int) ($_POST["department_id"] ?? 0);
            $course_code = strtoupper(clean_string($_POST["course_code"] ?? ""));
            $course_name = clean_string($_POST["course_name"] ?? "");
            $course_description = clean_string($_POST["course_description"] ?? "");
            $number_of_year_level = (int) ($_POST["number_of_year_level"] ?? 4);
            $course_status = valid_status($_POST["course_status"] ?? "Active", ["Active", "Inactive"], "Active");

            if ($department_id <= 0 || $course_code === "" || $course_name === "") {
                redirect_panel("courses", "College, program code, and program name are required.", "error");
            }

            if ($number_of_year_level < 1 || $number_of_year_level > 6) {
                redirect_panel("courses", "Number of year levels must be from 1 to 6.", "error");
            }

            $duplicate = count_rows(
                $pdo,
                "
                SELECT COUNT(*)
                FROM course
                WHERE course_code = :course_code
                OR course_name = :course_name
                ",
                [
                    "course_code" => $course_code,
                    "course_name" => $course_name
                ]
            );

            if ($duplicate > 0) {
                redirect_panel("courses", "Program code or name already exists.", "error");
            }

            $statement = $pdo->prepare("
                INSERT INTO course (
                    department_id,
                    course_code,
                    course_name,
                    course_description,
                    number_of_year_level,
                    course_status
                )
                VALUES (
                    :department_id,
                    :course_code,
                    :course_name,
                    :course_description,
                    :number_of_year_level,
                    :course_status
                )
            ");

            $statement->execute([
                "department_id" => $department_id,
                "course_code" => $course_code,
                "course_name" => $course_name,
                "course_description" => $course_description,
                "number_of_year_level" => $number_of_year_level,
                "course_status" => $course_status
            ]);

            redirect_panel("courses", "Program {$course_code} successfully added!");
        }

        if ($form_action === "update_course") {
            $course_id = (int) ($_POST["course_id"] ?? 0);
            $department_id = (int) ($_POST["department_id"] ?? 0);
            $course_code = strtoupper(clean_string($_POST["course_code"] ?? ""));
            $course_name = clean_string($_POST["course_name"] ?? "");
            $course_description = clean_string($_POST["course_description"] ?? "");
            $number_of_year_level = (int) ($_POST["number_of_year_level"] ?? 4);
            $course_status = valid_status($_POST["course_status"] ?? "Active", ["Active", "Inactive"], "Active");

            if ($course_id <= 0 || $department_id <= 0 || $course_code === "" || $course_name === "") {
                redirect_panel("courses", "Please complete all program fields.", "error");
            }

            if ($number_of_year_level < 1 || $number_of_year_level > 6) {
                redirect_panel("courses", "Number of year levels must be from 1 to 6.", "error");
            }

            $duplicate = count_rows(
                $pdo,
                "
                SELECT COUNT(*)
                FROM course
                WHERE course_id <> :course_id
                AND (
                    course_code = :course_code
                    OR course_name = :course_name
                )
                ",
                [
                    "course_id" => $course_id,
                    "course_code" => $course_code,
                    "course_name" => $course_name
                ]
            );

            if ($duplicate > 0) {
                redirect_panel("courses", "Program code or name already exists.", "error");
            }

            $statement = $pdo->prepare("
                UPDATE course
                SET
                    department_id = :department_id,
                    course_code = :course_code,
                    course_name = :course_name,
                    course_description = :course_description,
                    number_of_year_level = :number_of_year_level,
                    course_status = :course_status
                WHERE course_id = :course_id
            ");

            $statement->execute([
                "course_id" => $course_id,
                "department_id" => $department_id,
                "course_code" => $course_code,
                "course_name" => $course_name,
                "course_description" => $course_description,
                "number_of_year_level" => $number_of_year_level,
                "course_status" => $course_status
            ]);

            redirect_panel("courses", "Program {$course_code} successfully updated!");
        }

        if ($form_action === "delete_course") {
            $course_id = (int) ($_POST["course_id"] ?? 0);

            if ($course_id <= 0) {
                redirect_panel("courses", "Invalid program selected.", "error");
            }

            $linked_records =
                count_rows($pdo, "SELECT COUNT(*) FROM student WHERE course_id = :id", ["id" => $course_id]) +
                count_rows($pdo, "SELECT COUNT(*) FROM section WHERE course_id = :id", ["id" => $course_id]) +
                count_rows($pdo, "SELECT COUNT(*) FROM course_subject WHERE course_id = :id", ["id" => $course_id]);

            if ($linked_records > 0) {
                $statement = $pdo->prepare("
                    UPDATE course
                    SET course_status = 'Inactive'
                    WHERE course_id = :course_id
                ");

                $statement->execute([
                    "course_id" => $course_id
                ]);

                redirect_panel("courses", "You cannot delete an entity with record inside. This will mark as inactive.", "warning");
            }

            $statement = $pdo->prepare("
                DELETE FROM course
                WHERE course_id = :course_id
            ");

            $statement->execute([
                "course_id" => $course_id
            ]);

            redirect_panel("courses", "Program successfully deleted!");
        }

        if ($form_action === "add_sections") {
            $course_id = (int) ($_POST["course_id"] ?? 0);
            $year_level = (int) ($_POST["year_level"] ?? 0);
            $academic_year_id = (int) ($_POST["academic_year_id"] ?? 0);
            $term_name = valid_term_name($_POST["term_name"] ?? "First Semester");
            $maximum_student_count = (int) ($_POST["maximum_student_count"] ?? 50);

            if ($course_id <= 0) {
                redirect_panel("sections", "Please select a program.", "error");
            }

            if ($year_level < 1 || $year_level > 6) {
                redirect_panel("sections", "Please select a valid year level.", "error");
            }

            if ($academic_year_id <= 0) {
                redirect_panel("sections", "Please select an academic year.", "error");
            }

            if ($maximum_student_count < 1 || $maximum_student_count > 500) {
                redirect_panel("sections", "Maximum students must be from 1 to 500.", "error");
            }

            $term_id = find_or_create_term($pdo, $academic_year_id, $term_name);

            if ($term_id <= 0) {
                redirect_panel("sections", "Unable to find or create the selected semester.", "error");
            }

            $section_names = [];

            for ($i = 1; $i <= 5; $i++) {
                $section_name = strtoupper(clean_string($_POST["section_name_" . $i] ?? ""));

                if ($section_name !== "") {
                    $section_names[] = $section_name;
                }
            }

            $section_names = array_values(array_unique($section_names));

            if (empty($section_names)) {
                redirect_panel("sections", "Please enter at least one section name.", "error");
            }

            $pdo->beginTransaction();

            $created_count = 0;
            $created_names = [];
            $duplicate_names = [];

            foreach ($section_names as $section_name) {
                $duplicate = count_rows(
                    $pdo,
                    "
                    SELECT COUNT(*)
                    FROM section
                    WHERE course_id = :course_id
                    AND term_id = :term_id
                    AND year_level = :year_level
                    AND section_name = :section_name
                    ",
                    [
                        "course_id" => $course_id,
                        "term_id" => $term_id,
                        "year_level" => $year_level,
                        "section_name" => $section_name
                    ]
                );

                if ($duplicate > 0) {
                    $duplicate_names[] = $section_name;
                    continue;
                }

                $statement = $pdo->prepare("
                    INSERT INTO section (
                        course_id,
                        term_id,
                        academic_year_id,
                        section_name,
                        year_level,
                        maximum_student_count,
                        section_status
                    )
                    VALUES (
                        :course_id,
                        :term_id,
                        :academic_year_id,
                        :section_name,
                        :year_level,
                        :maximum_student_count,
                        'Active'
                    )
                ");

                $statement->execute([
                    "course_id" => $course_id,
                    "term_id" => $term_id,
                    "academic_year_id" => $academic_year_id,
                    "section_name" => $section_name,
                    "year_level" => $year_level,
                    "maximum_student_count" => $maximum_student_count
                ]);

                $created_count++;
                $created_names[] = $section_name;
            }

            $pdo->commit();

            if ($created_count === 0) {
                redirect_panel("sections", "No new section was added because the section already exists for the selected program, year level, and semester.", "error");
            }

            $message = $created_count === 1
                ? "Section {$created_names[0]} successfully added!"
                : "{$created_count} sections successfully added!";

            if (!empty($duplicate_names)) {
                $message .= " Duplicate skipped: " . implode(", ", $duplicate_names) . ".";
            }

            redirect_panel("sections", $message);
        }

        if ($form_action === "update_section") {
            $section_id = (int) ($_POST["section_id"] ?? 0);
            $course_id = (int) ($_POST["course_id"] ?? 0);
            $section_name = strtoupper(clean_string($_POST["section_name"] ?? ""));
            $year_level = (int) ($_POST["year_level"] ?? 0);
            $academic_year_id = (int) ($_POST["academic_year_id"] ?? 0);
            $term_name = valid_term_name($_POST["term_name"] ?? "First Semester");
            $maximum_student_count = (int) ($_POST["maximum_student_count"] ?? 50);
            $section_status = valid_status($_POST["section_status"] ?? "Active", ["Active", "Inactive", "Closed"], "Active");

            if ($section_id <= 0 || $course_id <= 0 || $section_name === "") {
                redirect_panel("sections", "Please complete all section fields.", "error");
            }

            if ($year_level < 1 || $year_level > 6) {
                redirect_panel("sections", "Please select a valid year level.", "error");
            }

            if ($academic_year_id <= 0) {
                redirect_panel("sections", "Please select an academic year.", "error");
            }

            if ($maximum_student_count < 1 || $maximum_student_count > 500) {
                redirect_panel("sections", "Maximum students must be from 1 to 500.", "error");
            }

            $term_id = find_or_create_term($pdo, $academic_year_id, $term_name);

            if ($term_id <= 0) {
                redirect_panel("sections", "Unable to find or create the selected semester.", "error");
            }

            $duplicate = count_rows(
                $pdo,
                "
                SELECT COUNT(*)
                FROM section
                WHERE section_id <> :section_id
                AND course_id = :course_id
                AND term_id = :term_id
                AND year_level = :year_level
                AND section_name = :section_name
                ",
                [
                    "section_id" => $section_id,
                    "course_id" => $course_id,
                    "term_id" => $term_id,
                    "year_level" => $year_level,
                    "section_name" => $section_name
                ]
            );

            if ($duplicate > 0) {
                redirect_panel("sections", "This section already exists for the selected program, year level, and semester.", "error");
            }

            $statement = $pdo->prepare("
                UPDATE section
                SET
                    course_id = :course_id,
                    term_id = :term_id,
                    academic_year_id = :academic_year_id,
                    section_name = :section_name,
                    year_level = :year_level,
                    maximum_student_count = :maximum_student_count,
                    section_status = :section_status
                WHERE section_id = :section_id
            ");

            $statement->execute([
                "section_id" => $section_id,
                "course_id" => $course_id,
                "term_id" => $term_id,
                "academic_year_id" => $academic_year_id,
                "section_name" => $section_name,
                "year_level" => $year_level,
                "maximum_student_count" => $maximum_student_count,
                "section_status" => $section_status
            ]);

            redirect_panel("sections", "Section {$section_name} successfully updated!");
        }

        if ($form_action === "delete_section") {
            $section_id = (int) ($_POST["section_id"] ?? 0);

            if ($section_id <= 0) {
                redirect_panel("sections", "Invalid section selected.", "error");
            }

            $linked_records =
                count_rows($pdo, "SELECT COUNT(*) FROM student_section_enrollment WHERE section_id = :id", ["id" => $section_id]) +
                count_rows($pdo, "SELECT COUNT(*) FROM section_subject_offering WHERE section_id = :id", ["id" => $section_id]);

            if ($linked_records > 0) {
                $statement = $pdo->prepare("
                    UPDATE section
                    SET section_status = 'Inactive'
                    WHERE section_id = :section_id
                ");

                $statement->execute([
                    "section_id" => $section_id
                ]);

                redirect_panel("sections", "You cannot delete an entity with record inside. This will mark as inactive.", "warning");
            }

            $statement = $pdo->prepare("
                DELETE FROM section
                WHERE section_id = :section_id
            ");

            $statement->execute([
                "section_id" => $section_id
            ]);

            redirect_panel("sections", "Section successfully deleted!");
        }

        if ($form_action === "add_subject") {
            $pdo->beginTransaction();

            $department_id = (int) ($_POST["department_id"] ?? 0);
            $subject_code = strtoupper(clean_string($_POST["subject_code"] ?? ""));
            $subject_title = clean_string($_POST["subject_title"] ?? "");
            $subject_description = clean_string($_POST["subject_description"] ?? "");
            $subject_unit = (float) ($_POST["subject_unit"] ?? 3);
            $year_level = (int) ($_POST["year_level"] ?? 1);
            $term_name = valid_term_name($_POST["term_name"] ?? "First Semester");
            $is_general_education = isset($_POST["is_general_education"]);
            $course_ids = $_POST["course_ids"] ?? [];

            if ($department_id <= 0 || $subject_code === "" || $subject_title === "") {
                throw new RuntimeException("College, course code, and course title are required.");
            }

            if ($subject_unit <= 0 || $subject_unit > 9) {
                throw new RuntimeException("Course units must be from 1 to 9.");
            }

            if ($year_level < 1 || $year_level > 6) {
                throw new RuntimeException("Please select a valid year level.");
            }

            $duplicate = count_rows(
                $pdo,
                "
                SELECT COUNT(*)
                FROM subject
                WHERE subject_code = :subject_code
                ",
                [
                    "subject_code" => $subject_code
                ]
            );

            if ($duplicate > 0) {
                throw new RuntimeException("Course code already exists.");
            }

            if ($is_general_education && count($course_ids) === 0) {
                $course_ids = array_column(
                    $pdo->query("
                        SELECT course_id
                        FROM course
                        WHERE course_status = 'Active'
                    ")->fetchAll(PDO::FETCH_ASSOC),
                    "course_id"
                );
            }

            if (!$is_general_education && count($course_ids) === 0) {
                throw new RuntimeException("Please select at least one program.");
            }

            $subject_type = $is_general_education ? "General Education" : "Major";

            $statement = $pdo->prepare("
                INSERT INTO subject (
                    department_id,
                    subject_code,
                    subject_title,
                    subject_description,
                    subject_unit,
                    subject_type,
                    subject_status
                )
                VALUES (
                    :department_id,
                    :subject_code,
                    :subject_title,
                    :subject_description,
                    :subject_unit,
                    :subject_type,
                    'Active'
                )
            ");

            $statement->execute([
                "department_id" => $department_id,
                "subject_code" => $subject_code,
                "subject_title" => $subject_title,
                "subject_description" => $subject_description,
                "subject_unit" => $subject_unit,
                "subject_type" => $subject_type
            ]);

            $subject_id = (int) $pdo->lastInsertId();

            foreach ($course_ids as $course_id) {
                upsert_course_subject($pdo, (int) $course_id, $subject_id, $year_level, $term_name);
            }

            $pdo->commit();

            redirect_panel("subjects", "Course {$subject_code} successfully added!");
        }

        if ($form_action === "update_subject") {
            $pdo->beginTransaction();

            $subject_id = (int) ($_POST["subject_id"] ?? 0);
            $department_id = (int) ($_POST["department_id"] ?? 0);
            $subject_code = strtoupper(clean_string($_POST["subject_code"] ?? ""));
            $subject_title = clean_string($_POST["subject_title"] ?? "");
            $subject_description = clean_string($_POST["subject_description"] ?? "");
            $subject_unit = (float) ($_POST["subject_unit"] ?? 3);
            $year_level = (int) ($_POST["year_level"] ?? 1);
            $term_name = valid_term_name($_POST["term_name"] ?? "First Semester");
            $subject_status = valid_status($_POST["subject_status"] ?? "Active", ["Active", "Inactive"], "Active");
            $is_general_education = isset($_POST["is_general_education"]);
            $course_ids = $_POST["course_ids"] ?? [];

            if ($subject_id <= 0 || $department_id <= 0 || $subject_code === "" || $subject_title === "") {
                throw new RuntimeException("Please complete all course fields.");
            }

            if ($subject_unit <= 0 || $subject_unit > 9) {
                throw new RuntimeException("Course units must be from 1 to 9.");
            }

            if ($year_level < 1 || $year_level > 6) {
                throw new RuntimeException("Please select a valid year level.");
            }

            $duplicate = count_rows(
                $pdo,
                "
                SELECT COUNT(*)
                FROM subject
                WHERE subject_id <> :subject_id
                AND subject_code = :subject_code
                ",
                [
                    "subject_id" => $subject_id,
                    "subject_code" => $subject_code
                ]
            );

            if ($duplicate > 0) {
                throw new RuntimeException("Course code already exists.");
            }

            if ($is_general_education && count($course_ids) === 0) {
                $course_ids = array_column(
                    $pdo->query("
                        SELECT course_id
                        FROM course
                        WHERE course_status = 'Active'
                    ")->fetchAll(PDO::FETCH_ASSOC),
                    "course_id"
                );
            }

            if (!$is_general_education && count($course_ids) === 0) {
                throw new RuntimeException("Please select at least one program.");
            }

            $subject_type = $is_general_education ? "General Education" : "Major";

            $statement = $pdo->prepare("
                UPDATE subject
                SET
                    department_id = :department_id,
                    subject_code = :subject_code,
                    subject_title = :subject_title,
                    subject_description = :subject_description,
                    subject_unit = :subject_unit,
                    subject_type = :subject_type,
                    subject_status = :subject_status
                WHERE subject_id = :subject_id
            ");

            $statement->execute([
                "subject_id" => $subject_id,
                "department_id" => $department_id,
                "subject_code" => $subject_code,
                "subject_title" => $subject_title,
                "subject_description" => $subject_description,
                "subject_unit" => $subject_unit,
                "subject_type" => $subject_type,
                "subject_status" => $subject_status
            ]);

            $deactivate_map = $pdo->prepare("
                UPDATE course_subject
                SET course_subject_status = 'Inactive'
                WHERE subject_id = :subject_id
            ");

            $deactivate_map->execute([
                "subject_id" => $subject_id
            ]);

            foreach ($course_ids as $course_id) {
                upsert_course_subject($pdo, (int) $course_id, $subject_id, $year_level, $term_name);
            }

            $pdo->commit();

            redirect_panel("subjects", "Course {$subject_code} successfully updated!");
        }

        if ($form_action === "delete_subject") {
            $subject_id = (int) ($_POST["subject_id"] ?? 0);

            if ($subject_id <= 0) {
                redirect_panel("subjects", "Invalid course selected.", "error");
            }

            $linked_records = count_rows(
                $pdo,
                "
                SELECT COUNT(*)
                FROM section_subject_offering
                WHERE subject_id = :id
                ",
                [
                    "id" => $subject_id
                ]
            );

            if ($linked_records > 0) {
                $statement = $pdo->prepare("
                    UPDATE subject
                    SET subject_status = 'Inactive'
                    WHERE subject_id = :subject_id
                ");

                $statement->execute([
                    "subject_id" => $subject_id
                ]);

                $map = $pdo->prepare("
                    UPDATE course_subject
                    SET course_subject_status = 'Inactive'
                    WHERE subject_id = :subject_id
                ");

                $map->execute([
                    "subject_id" => $subject_id
                ]);

                redirect_panel("subjects", "You cannot delete an entity with record inside. This will mark as inactive.", "warning");
            }

            $delete_map = $pdo->prepare("
                DELETE FROM course_subject
                WHERE subject_id = :subject_id
            ");

            $delete_map->execute([
                "subject_id" => $subject_id
            ]);

            $statement = $pdo->prepare("
                DELETE FROM subject
                WHERE subject_id = :subject_id
            ");

            $statement->execute([
                "subject_id" => $subject_id
            ]);

            redirect_panel("subjects", "Course successfully deleted!");
        }
    }
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Academic Structure Error: " . $error->getMessage());

    $error_message = $error instanceof RuntimeException
        ? $error->getMessage()
        : "Database action failed. Please check duplicate values or connected records.";

    redirect_panel($active_panel, $error_message, "error");
}

$flash_message = $_SESSION["academic_flash_message"] ?? "";
$flash_type = $_SESSION["academic_flash_type"] ?? "success";

unset($_SESSION["academic_flash_message"]);
unset($_SESSION["academic_flash_type"]);

$departments_all = $pdo->query("
    SELECT department_id, department_code, department_name, department_status
    FROM department
    ORDER BY department_name
")->fetchAll(PDO::FETCH_ASSOC);

$courses_all = $pdo->query("
    SELECT course_id, course_code, course_name, department_id, number_of_year_level, course_status
    FROM course
    ORDER BY course_code
")->fetchAll(PDO::FETCH_ASSOC);

$faculty_all = $pdo->query("
    SELECT faculty_id, full_name, department_id, faculty_status
    FROM faculty
    ORDER BY full_name
")->fetchAll(PDO::FETCH_ASSOC);

$academic_years_all = $pdo->query("
    SELECT academic_year_id, academic_year_name, academic_year_status
    FROM academic_year
    ORDER BY academic_year_id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$modal_type = $_GET["type"] ?? "";
$modal_action = $_GET["action"] ?? "";
$modal_id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Structure</title>
    <link rel="stylesheet" href="academic-structure.css?v=<?php echo time(); ?>">
</head>
<body class="academic-structure-page">
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
            <a class="nav-link active" href="academic_structure.php"><?php echo icon_svg("book"); ?> Academic Structure</a>
            <a class="nav-link" href="assignment_management.php"><?php echo icon_svg("assignment"); ?> Assignment Management</a>
            <a class="nav-link" href="evaluation_setup.php"><?php echo icon_svg("settings"); ?> Evaluation Setup</a>
            <a class="nav-link" href="submission_monitoring.php"><?php echo icon_svg("clipboard"); ?> Submission Monitoring</a>
            <a class="nav-link" href="report_page.php"><?php echo icon_svg("chart"); ?> Reports</a>
            <a class="nav-link" href="announcement_page.php"><?php echo icon_svg("megaphone"); ?> Announcements</a>
            <a class="nav-link" href="settings_page.php"><?php echo icon_svg("settings"); ?> Settings</a>
            <a class="nav-link" href="#"><?php echo icon_svg("moon"); ?> Dark Mode</a>
        </nav>

        <div class="sidebar-bottom">
            <a class="logout-link" href="academic_structure.php?logout=1"><?php echo icon_svg("logout"); ?> Logout</a>
        </div>
    </aside>

    <main class="main-content">
        <section class="content-wrap">
            <div class="page-header">
                <h1>Academic Structure</h1>
                <p>Manage colleges, programs, sections, and courses</p>
            </div>

            <?php if ($flash_message !== ""): ?>
                <div class="toast-modal <?php echo e($flash_type); ?>" id="toastModal">
                    <?php echo e($flash_message); ?>
                </div>
            <?php endif; ?>

            <div class="tab-bar">
                <a href="academic_structure.php?panel=departments" class="<?php echo $active_panel === "departments" ? "active" : ""; ?>">
                    <?php echo icon_svg("building"); ?> Colleges
                </a>

                <a href="academic_structure.php?panel=courses" class="<?php echo $active_panel === "courses" ? "active" : ""; ?>">
                    <?php echo icon_svg("cap"); ?> Programs
                </a>

                <a href="academic_structure.php?panel=sections" class="<?php echo $active_panel === "sections" ? "active" : ""; ?>">
                    <?php echo icon_svg("section"); ?> Sections
                </a>

                <a href="academic_structure.php?panel=subjects" class="<?php echo $active_panel === "subjects" ? "active" : ""; ?>">
                    <?php echo icon_svg("book"); ?> Courses
                </a>
            </div>

            <?php
            if ($active_panel === "departments") {
                require __DIR__ . "/department_panel.php";
            }

            if ($active_panel === "courses") {
                require __DIR__ . "/course_panel.php";
            }

            if ($active_panel === "sections") {
                require __DIR__ . "/section_panel.php";
            }

            if ($active_panel === "subjects") {
                require __DIR__ . "/subject_panel.php";
            }
            ?>
        </section>
    </main>

    <script>
        const autoFilterForms = document.querySelectorAll(".auto-filter-form");

        autoFilterForms.forEach(function (form) {
            const searchInput = form.querySelector('input[name="q"]');
            const selects = form.querySelectorAll("select");
            let searchTimer = null;

            if (searchInput) {
                searchInput.addEventListener("input", function () {
                    clearTimeout(searchTimer);

                    searchTimer = setTimeout(function () {
                        form.submit();
                    }, 450);
                });
            }

            selects.forEach(function (select) {
                select.addEventListener("change", function () {
                    form.submit();
                });
            });
        });

        const toastModal = document.getElementById("toastModal");

        if (toastModal) {
            setTimeout(function () {
                toastModal.classList.add("hide");

                setTimeout(function () {
                    toastModal.remove();
                }, 400);
            }, 3000);
        }
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