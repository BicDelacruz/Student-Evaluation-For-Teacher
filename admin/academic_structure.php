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
    header("Location: ../login-page/login_page.php");
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

function get_admin_id(PDO $pdo, int $user_id): int
{
    $statement = $pdo->prepare("SELECT admin_id FROM `admin` WHERE user_id = :user_id LIMIT 1");
    $statement->execute(["user_id" => $user_id]);

    return (int) $statement->fetchColumn();
}

function find_or_create_term(PDO $pdo, int $academic_year_id, string $term_name): int
{
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

    $academic_year = $year_statement->fetch();

    $start_date = $academic_year["start_date"] ?? date("Y") . "-01-01";
    $end_date = $academic_year["end_date"] ?? date("Y") . "-12-31";

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

function icon_svg(string $name): string
{
    $icons = [
        "dashboard" => '<svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>',
        "students" => '<svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9"/></svg>',
        "faculty" => '<svg viewBox="0 0 24 24"><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4-4h4"/><path d="M17 11v6"/><path d="M14 14h6"/></svg>',
        "book" => '<svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"/></svg>',
        "building" => '<svg viewBox="0 0 24 24"><path d="M4 21V8l8-5 8 5v13"/><path d="M9 21v-8h6v8"/><path d="M7 10h.01"/><path d="M17 10h.01"/></svg>',
        "cap" => '<svg viewBox="0 0 24 24"><path d="M22 10L12 5 2 10l10 5 10-5z"/><path d="M6 12v5c3 2 9 2 12 0v-5"/></svg>',
        "section" => '<svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6"/><path d="M16 11h6"/></svg>',
        "clipboard" => '<svg viewBox="0 0 24 24"><rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4a3 3 0 0 1 6 0"/><path d="M9 4h6"/><path d="M9 11h6"/><path d="M9 15h6"/></svg>',
        "settings" => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 0 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6V21a2 2 0 0 1-4 0v-.1a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1a2 2 0 0 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.6-1H3a2 2 0 0 1 0-4h.1a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9l-.1-.1A2 2 0 0 1 7.1 4l.1.1a1.7 1.7 0 0 0 1.9.3 1.7 1.7 0 0 0 1-1.6V3a2 2 0 0 1 4 0v.1a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1A2 2 0 0 1 20 7.1l-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.6 1h.1a2 2 0 0 1 0 4h-.1a1.7 1.7 0 0 0-1.6 1z"/></svg>',
        "chart" => '<svg viewBox="0 0 24 24"><path d="M4 19V5"/><path d="M4 19h16"/><path d="M8 16v-5"/><path d="M12 16V8"/><path d="M16 16v-7"/></svg>',
        "megaphone" => '<svg viewBox="0 0 24 24"><path d="M3 11v2a2 2 0 0 0 2 2h3l7 4V5L8 9H5a2 2 0 0 0-2 2z"/><path d="M19 9a4 4 0 0 1 0 6"/></svg>',
        "moon" => '<svg viewBox="0 0 24 24"><path d="M21 14.5A8.5 8.5 0 1 1 9.5 3a7 7 0 0 0 11.5 11.5z"/></svg>',
        "logout" => '<svg viewBox="0 0 24 24"><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/><path d="M21 3v18"/></svg>',
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
                redirect_panel("departments", "Department code and name are required.", "error");
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

            redirect_panel("departments", "Department {$department_code} successfully added!");
        }

        if ($form_action === "update_department") {
            $department_id = (int) ($_POST["department_id"] ?? 0);
            $department_code = strtoupper(clean_string($_POST["department_code"] ?? ""));
            $department_name = clean_string($_POST["department_name"] ?? "");
            $department_head_faculty_id = ($_POST["department_head_faculty_id"] ?? "") !== "" ? (int) $_POST["department_head_faculty_id"] : null;

            $statement = $pdo->prepare("
                UPDATE department
                SET
                    department_code = :department_code,
                    department_name = :department_name,
                    department_head_faculty_id = :department_head_faculty_id
                WHERE department_id = :department_id
            ");

            $statement->execute([
                "department_id" => $department_id,
                "department_code" => $department_code,
                "department_name" => $department_name,
                "department_head_faculty_id" => $department_head_faculty_id
            ]);

            redirect_panel("departments", "Department {$department_code} successfully updated!");
        }

        if ($form_action === "delete_department") {
            $department_id = (int) ($_POST["department_id"] ?? 0);

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

            $statement = $pdo->prepare("DELETE FROM department WHERE department_id = :department_id");
            $statement->execute(["department_id" => $department_id]);

            redirect_panel("departments", "Department successfully deleted!");
        }

        if ($form_action === "add_course") {
            $course_code = strtoupper(clean_string($_POST["course_code"] ?? ""));
            $course_name = clean_string($_POST["course_name"] ?? "");

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
                "department_id" => (int) $_POST["department_id"],
                "course_code" => $course_code,
                "course_name" => $course_name,
                "course_description" => clean_string($_POST["course_description"] ?? ""),
                "number_of_year_level" => (int) ($_POST["number_of_year_level"] ?? 4),
                "course_status" => $_POST["course_status"] ?? "Active"
            ]);

            redirect_panel("courses", "Course {$course_code} successfully added!");
        }

        if ($form_action === "update_course") {
            $course_id = (int) ($_POST["course_id"] ?? 0);
            $course_code = strtoupper(clean_string($_POST["course_code"] ?? ""));

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
                "department_id" => (int) $_POST["department_id"],
                "course_code" => $course_code,
                "course_name" => clean_string($_POST["course_name"] ?? ""),
                "course_description" => clean_string($_POST["course_description"] ?? ""),
                "number_of_year_level" => (int) $_POST["number_of_year_level"],
                "course_status" => $_POST["course_status"] ?? "Active"
            ]);

            redirect_panel("courses", "Course {$course_code} successfully updated!");
        }

        if ($form_action === "delete_course") {
            $course_id = (int) ($_POST["course_id"] ?? 0);

            $linked_records =
                count_rows($pdo, "SELECT COUNT(*) FROM student WHERE course_id = :id", ["id" => $course_id]) +
                count_rows($pdo, "SELECT COUNT(*) FROM section WHERE course_id = :id", ["id" => $course_id]) +
                count_rows($pdo, "SELECT COUNT(*) FROM course_subject WHERE course_id = :id", ["id" => $course_id]);

            if ($linked_records > 0) {
                $statement = $pdo->prepare("UPDATE course SET course_status = 'Inactive' WHERE course_id = :course_id");
                $statement->execute(["course_id" => $course_id]);

                redirect_panel("courses", "You cannot delete an entity with record inside. This will mark as inactive.", "warning");
            }

            $statement = $pdo->prepare("DELETE FROM course WHERE course_id = :course_id");
            $statement->execute(["course_id" => $course_id]);

            redirect_panel("courses", "Course successfully deleted!");
        }

        if ($form_action === "add_sections") {
            $course_id = (int) ($_POST["course_id"] ?? 0);
            $year_level = (int) ($_POST["year_level"] ?? 1);
            $academic_year_id = (int) ($_POST["academic_year_id"] ?? 0);
            $term_name = $_POST["term_name"] ?? "First Semester";
            $maximum_student_count = (int) ($_POST["maximum_student_count"] ?? 50);

            if ($course_id <= 0 || $academic_year_id <= 0) {
                redirect_panel("sections", "Course and academic year are required.", "error");
            }

            $term_id = find_or_create_term($pdo, $academic_year_id, $term_name);
            $created_count = 0;
            $created_names = [];

            for ($i = 1; $i <= 5; $i++) {
                $section_name = strtoupper(clean_string($_POST["section_name_" . $i] ?? ""));

                if ($section_name === "") {
                    continue;
                }

                $statement = $pdo->prepare("
                    INSERT INTO section (
                        course_id,
                        term_id,
                        section_name,
                        year_level,
                        maximum_student_count,
                        adviser_faculty_id,
                        section_status
                    )
                    VALUES (
                        :course_id,
                        :term_id,
                        :section_name,
                        :year_level,
                        :maximum_student_count,
                        NULL,
                        'Active'
                    )
                ");

                $statement->execute([
                    "course_id" => $course_id,
                    "term_id" => $term_id,
                    "section_name" => $section_name,
                    "year_level" => $year_level,
                    "maximum_student_count" => $maximum_student_count
                ]);

                $created_count++;
                $created_names[] = $section_name;
            }

            if ($created_count === 0) {
                redirect_panel("sections", "Please enter at least one section name.", "error");
            }

            $message = $created_count === 1
                ? "Section {$created_names[0]} successfully added!"
                : "{$created_count} sections successfully added!";

            redirect_panel("sections", $message);
        }

        if ($form_action === "update_section") {
            $section_id = (int) ($_POST["section_id"] ?? 0);
            $section_name = strtoupper(clean_string($_POST["section_name"] ?? ""));
            $academic_year_id = (int) ($_POST["academic_year_id"] ?? 0);
            $term_name = $_POST["term_name"] ?? "First Semester";
            $term_id = find_or_create_term($pdo, $academic_year_id, $term_name);

            $statement = $pdo->prepare("
                UPDATE section
                SET
                    course_id = :course_id,
                    term_id = :term_id,
                    section_name = :section_name,
                    year_level = :year_level,
                    maximum_student_count = :maximum_student_count,
                    section_status = :section_status
                WHERE section_id = :section_id
            ");

            $statement->execute([
                "section_id" => $section_id,
                "course_id" => (int) $_POST["course_id"],
                "term_id" => $term_id,
                "section_name" => $section_name,
                "year_level" => (int) $_POST["year_level"],
                "maximum_student_count" => (int) ($_POST["maximum_student_count"] ?? 50),
                "section_status" => $_POST["section_status"] ?? "Active"
            ]);

            redirect_panel("sections", "Section {$section_name} successfully updated!");
        }

        if ($form_action === "delete_section") {
            $section_id = (int) ($_POST["section_id"] ?? 0);

            $linked_records =
                count_rows($pdo, "SELECT COUNT(*) FROM student_section_enrollment WHERE section_id = :id", ["id" => $section_id]) +
                count_rows($pdo, "SELECT COUNT(*) FROM section_subject_offering WHERE section_id = :id", ["id" => $section_id]);

            if ($linked_records > 0) {
                $statement = $pdo->prepare("UPDATE section SET section_status = 'Inactive' WHERE section_id = :section_id");
                $statement->execute(["section_id" => $section_id]);

                redirect_panel("sections", "You cannot delete an entity with record inside. This will mark as inactive.", "warning");
            }

            $statement = $pdo->prepare("DELETE FROM section WHERE section_id = :section_id");
            $statement->execute(["section_id" => $section_id]);

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
            $term_name = $_POST["term_name"] ?? "First Semester";
            $is_general_education = isset($_POST["is_general_education"]);
            $course_ids = $_POST["course_ids"] ?? [];

            if ($is_general_education && count($course_ids) === 0) {
                $course_ids = array_column(
                    $pdo->query("SELECT course_id FROM course WHERE course_status = 'Active'")->fetchAll(),
                    "course_id"
                );
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
                $map = $pdo->prepare("
                    INSERT IGNORE INTO course_subject (
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

                $map->execute([
                    "course_id" => (int) $course_id,
                    "subject_id" => $subject_id,
                    "year_level" => $year_level,
                    "term_name" => $term_name
                ]);
            }

            $pdo->commit();

            redirect_panel("subjects", "Subject {$subject_code} successfully added!");
        }

        if ($form_action === "update_subject") {
            $pdo->beginTransaction();

            $subject_id = (int) ($_POST["subject_id"] ?? 0);
            $subject_code = strtoupper(clean_string($_POST["subject_code"] ?? ""));
            $is_general_education = isset($_POST["is_general_education"]);
            $course_ids = $_POST["course_ids"] ?? [];
            $year_level = (int) ($_POST["year_level"] ?? 1);
            $term_name = $_POST["term_name"] ?? "First Semester";
            $subject_type = $is_general_education ? "General Education" : "Major";

            if ($is_general_education && count($course_ids) === 0) {
                $course_ids = array_column(
                    $pdo->query("SELECT course_id FROM course WHERE course_status = 'Active'")->fetchAll(),
                    "course_id"
                );
            }

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
                "department_id" => (int) $_POST["department_id"],
                "subject_code" => $subject_code,
                "subject_title" => clean_string($_POST["subject_title"] ?? ""),
                "subject_description" => clean_string($_POST["subject_description"] ?? ""),
                "subject_unit" => (float) $_POST["subject_unit"],
                "subject_type" => $subject_type,
                "subject_status" => $_POST["subject_status"] ?? "Active"
            ]);

            $delete_map = $pdo->prepare("DELETE FROM course_subject WHERE subject_id = :subject_id");
            $delete_map->execute(["subject_id" => $subject_id]);

            foreach ($course_ids as $course_id) {
                $map = $pdo->prepare("
                    INSERT IGNORE INTO course_subject (
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

                $map->execute([
                    "course_id" => (int) $course_id,
                    "subject_id" => $subject_id,
                    "year_level" => $year_level,
                    "term_name" => $term_name
                ]);
            }

            $pdo->commit();

            redirect_panel("subjects", "Subject {$subject_code} successfully updated!");
        }

        if ($form_action === "delete_subject") {
            $subject_id = (int) ($_POST["subject_id"] ?? 0);

            $linked_records = count_rows(
                $pdo,
                "SELECT COUNT(*) FROM section_subject_offering WHERE subject_id = :id",
                ["id" => $subject_id]
            );

            if ($linked_records > 0) {
                $statement = $pdo->prepare("UPDATE subject SET subject_status = 'Inactive' WHERE subject_id = :subject_id");
                $statement->execute(["subject_id" => $subject_id]);

                $map = $pdo->prepare("UPDATE course_subject SET course_subject_status = 'Inactive' WHERE subject_id = :subject_id");
                $map->execute(["subject_id" => $subject_id]);

                redirect_panel("subjects", "You cannot delete an entity with record inside. This will mark as inactive.", "warning");
            }

            $delete_map = $pdo->prepare("DELETE FROM course_subject WHERE subject_id = :subject_id");
            $delete_map->execute(["subject_id" => $subject_id]);

            $statement = $pdo->prepare("DELETE FROM subject WHERE subject_id = :subject_id");
            $statement->execute(["subject_id" => $subject_id]);

            redirect_panel("subjects", "Subject successfully deleted!");
        }
    }
} catch (PDOException $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    redirect_panel($active_panel, "Database action failed. Please check duplicate values or connected records.", "error");
}

$flash_message = $_SESSION["academic_flash_message"] ?? "";
$flash_type = $_SESSION["academic_flash_type"] ?? "success";

unset($_SESSION["academic_flash_message"]);
unset($_SESSION["academic_flash_type"]);

$departments_all = $pdo->query("
    SELECT department_id, department_code, department_name, department_status
    FROM department
    ORDER BY department_name
")->fetchAll();

$courses_all = $pdo->query("
    SELECT course_id, course_code, course_name, department_id, number_of_year_level, course_status
    FROM course
    ORDER BY course_code
")->fetchAll();

$faculty_all = $pdo->query("
    SELECT faculty_id, full_name, department_id, faculty_status
    FROM faculty
    ORDER BY full_name
")->fetchAll();

$academic_years_all = $pdo->query("
    SELECT academic_year_id, academic_year_name, academic_year_status
    FROM academic_year
    ORDER BY academic_year_id DESC
")->fetchAll();

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
    <link rel="stylesheet" href="academic-structure.css?v=<?php echo time(); ?>"></head>
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
            <a class="nav-link" href="#"><?php echo icon_svg("faculty"); ?> Assignment Management</a>
            <a class="nav-link" href="#"><?php echo icon_svg("settings"); ?> Evaluation Setup</a>
            <a class="nav-link" href="#"><?php echo icon_svg("clipboard"); ?> Submission Monitoring</a>
            <a class="nav-link" href="#"><?php echo icon_svg("chart"); ?> Reports</a>
            <a class="nav-link" href="#"><?php echo icon_svg("megaphone"); ?> Announcements</a>
            <a class="nav-link" href="#"><?php echo icon_svg("settings"); ?> Settings</a>
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
                <p>Manage departments, courses, sections, and subjects</p>
            </div>

            <?php if ($flash_message !== ""): ?>
                <div class="toast-modal <?php echo e($flash_type); ?>" id="toastModal">
                    <?php echo e($flash_message); ?>
                </div>
            <?php endif; ?>

            <div class="tab-bar">
                <a href="academic_structure.php?panel=departments" class="<?php echo $active_panel === "departments" ? "active" : ""; ?>">
                    <?php echo icon_svg("building"); ?> Departments
                </a>

                <a href="academic_structure.php?panel=courses" class="<?php echo $active_panel === "courses" ? "active" : ""; ?>">
                    <?php echo icon_svg("cap"); ?> Courses
                </a>

                <a href="academic_structure.php?panel=sections" class="<?php echo $active_panel === "sections" ? "active" : ""; ?>">
                    <?php echo icon_svg("section"); ?> Sections
                </a>

                <a href="academic_structure.php?panel=subjects" class="<?php echo $active_panel === "subjects" ? "active" : ""; ?>">
                    <?php echo icon_svg("book"); ?> Subjects
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
</body>
</html>