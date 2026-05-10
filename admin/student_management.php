<?php
session_start();

require_once __DIR__ . "/../database_connector.php";
require_once __DIR__ . "/../generate_password_hash.php";

if (!isset($pdo)) {
    die("Database connection variable \$pdo was not found. Please check database_connector.php.");
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function clean_string($value)
{
    return trim((string) $value);
}

function redirect_with_message($type, $message)
{
    header("Location: student_management.php?toast_type=" . urlencode($type) . "&toast_message=" . urlencode($message));
    exit;
}

function year_level_label($year_level)
{
    $year_level = (int) $year_level;

    if ($year_level === 1) {
        return "1st Year";
    }

    if ($year_level === 2) {
        return "2nd Year";
    }

    if ($year_level === 3) {
        return "3rd Year";
    }

    return $year_level . "th Year";
}

function expected_student_id_prefix($year_level)
{
    $year_level = (int) $year_level;

    if ($year_level < 1 || $year_level > 6) {
        return "";
    }

    return (string) (26 - $year_level);
}

function validate_student_number_by_year_level($student_number, $year_level)
{
    $student_number = clean_string($student_number);
    $year_level = (int) $year_level;

    if (preg_match("/^[0-9]{2}-[0-9]{5}$/", $student_number) !== 1) {
        return "Student ID must strictly follow this format: 24-00123.";
    }

    $expected_prefix = expected_student_id_prefix($year_level);

    if ($expected_prefix === "") {
        return "Please select a valid year level before entering the Student ID.";
    }

    $actual_prefix = substr($student_number, 0, 2);

    if ($actual_prefix !== $expected_prefix) {
        return year_level_label($year_level) . " students must use Student ID starting with " . $expected_prefix . ". Example: " . $expected_prefix . "-00123.";
    }

    return "";
}

function display_student_name($student)
{
    $last = clean_string($student["last_name"] ?? "");
    $first = clean_string($student["first_name"] ?? "");
    $middle = clean_string($student["middle_name"] ?? "");

    if ($middle !== "") {
        return $last . ", " . $first . " " . $middle;
    }

    return $last . ", " . $first;
}

function build_full_name($first_name, $middle_name, $last_name)
{
    $parts = array_filter([
        clean_string($first_name),
        clean_string($middle_name),
        clean_string($last_name)
    ]);

    return implode(" ", $parts);
}

function normalize_status($status)
{
    $allowed = ["Active", "Inactive", "Suspended", "Restricted"];

    if (in_array($status, $allowed, true)) {
        return $status;
    }

    return "Active";
}

function user_account_status_from_student_status($student_status)
{
    if ($student_status === "Suspended") {
        return "Suspended";
    }

    if ($student_status === "Restricted") {
        return "Restricted";
    }

    if ($student_status === "Inactive") {
        return "Inactive";
    }

    return "Active";
}

function validate_student_input($data, $is_update)
{
    $errors = [];

    $student_number_error = validate_student_number_by_year_level($data["student_number"], $data["year_level"]);

    if ($student_number_error !== "") {
        $errors[] = $student_number_error;
    }

    if (!filter_var($data["email"], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email must be a valid email address.";
    }

    if ($data["first_name"] === "") {
        $errors[] = "First name is required.";
    }

    if ($data["last_name"] === "") {
        $errors[] = "Last name is required.";
    }

    if ($data["department_id"] <= 0) {
        $errors[] = "College is required.";
    }

    if ($data["course_id"] <= 0) {
        $errors[] = "Program is required.";
    }

    if ($data["year_level"] < 1 || $data["year_level"] > 6) {
        $errors[] = "Year level must be from 1st Year to 6th Year.";
    }

    if ($data["section_id"] <= 0) {
        $errors[] = "Section is required.";
    }

    if (!$is_update && $data["password"] !== "" && strlen($data["password"]) < 6) {
        $errors[] = "Password must have at least 6 characters. Leave it blank if you want to use the Student ID as the default password.";
    }

    if ($is_update && $data["password"] !== "" && strlen($data["password"]) < 6) {
        $errors[] = "New password must have at least 6 characters.";
    }

    return $errors;
}

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
        "search" => '<svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"></circle><path d="M21 21l-4.35-4.35"></path></svg>',
        "eye" => '<svg viewBox="0 0 24 24"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"></path><circle cx="12" cy="12" r="3"></circle></svg>',
        "eye_closed" => '<svg viewBox="0 0 24 24"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a20.29 20.29 0 0 1 5.06-5.94"></path><path d="M9.9 4.24A10.76 10.76 0 0 1 12 4c7 0 11 7 11 7a20.1 20.1 0 0 1-3.17 4.15"></path><path d="M14.12 14.12A3 3 0 0 1 9.88 9.88"></path><path d="M3 3l18 18"></path></svg>',
        "edit" => '<svg viewBox="0 0 24 24"><path d="M12 20h9"></path><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"></path></svg>',
        "trash" => '<svg viewBox="0 0 24 24"><path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="M19 6l-1 14H6L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path></svg>',
        "check" => '<svg viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"></path></svg>',
        "ban" => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"></circle><path d="M15 9l-6 6"></path><path d="M9 9l6 6"></path></svg>',
        "filter" => '<svg viewBox="0 0 24 24"><path d="M3 4h18l-7 8v6l-4 2v-8z"></path></svg>'
    ];

    return $icons[$name] ?? "";
}

function get_section_information($pdo, $section_id)
{
    $statement = $pdo->prepare("
        SELECT
            sec.section_id,
            sec.course_id,
            sec.term_id,
            sec.section_name,
            sec.year_level,
            sec.academic_year_id,
            c.department_id,
            c.course_code,
            c.course_name,
            t.term_name,
            COALESCE(ay_section.academic_year_name, ay_term.academic_year_name) AS academic_year_name
        FROM `section` sec
        INNER JOIN course c ON c.course_id = sec.course_id
        INNER JOIN term t ON t.term_id = sec.term_id
        LEFT JOIN academic_year ay_section ON ay_section.academic_year_id = sec.academic_year_id
        LEFT JOIN academic_year ay_term ON ay_term.academic_year_id = t.academic_year_id
        WHERE sec.section_id = :section_id
        LIMIT 1
    ");

    $statement->execute(["section_id" => $section_id]);

    return $statement->fetch();
}

function sync_student_enrollment($pdo, $student_id, $section)
{
    $term_id = (int) $section["term_id"];
    $section_id = (int) $section["section_id"];

    $pdo->prepare("
        UPDATE student_section_enrollment
        SET enrollment_status = 'Transferred'
        WHERE student_id = :student_id
        AND enrollment_status = 'Active'
        AND term_id <> :term_id
    ")->execute([
        "student_id" => $student_id,
        "term_id" => $term_id
    ]);

    $check = $pdo->prepare("
        SELECT student_section_enrollment_id
        FROM student_section_enrollment
        WHERE student_id = :student_id
        AND term_id = :term_id
        LIMIT 1
    ");

    $check->execute([
        "student_id" => $student_id,
        "term_id" => $term_id
    ]);

    $existing = $check->fetch();

    if ($existing) {
        $update = $pdo->prepare("
            UPDATE student_section_enrollment
            SET section_id = :section_id,
                enrollment_status = 'Active'
            WHERE student_section_enrollment_id = :student_section_enrollment_id
        ");

        $update->execute([
            "section_id" => $section_id,
            "student_section_enrollment_id" => $existing["student_section_enrollment_id"]
        ]);

        return;
    }

    $insert = $pdo->prepare("
        INSERT INTO student_section_enrollment (
            student_id,
            section_id,
            term_id,
            enrollment_status
        )
        VALUES (
            :student_id,
            :section_id,
            :term_id,
            'Active'
        )
    ");

    $insert->execute([
        "student_id" => $student_id,
        "section_id" => $section_id,
        "term_id" => $term_id
    ]);
}

function student_has_evaluation_record($pdo, $student_id)
{
    $statement = $pdo->prepare("
        SELECT COUNT(*)
        FROM student_evaluation_task
        WHERE student_id = :student_id
    ");

    $statement->execute(["student_id" => $student_id]);

    return (int) $statement->fetchColumn() > 0;
}

function get_student_role_id($pdo)
{
    $statement = $pdo->query("
        SELECT role_id
        FROM `role`
        WHERE LOWER(role_name) = 'student'
        LIMIT 1
    ");

    $role_id = $statement->fetchColumn();

    if (!$role_id) {
        throw new RuntimeException("Student role was not found in the role table.");
    }

    return (int) $role_id;
}

$toast_type = clean_string($_GET["toast_type"] ?? "");
$toast_message = clean_string($_GET["toast_message"] ?? "");

$modal_action = clean_string($_GET["action"] ?? "");
$modal_id = (int) ($_GET["id"] ?? 0);

try {
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $form_action = clean_string($_POST["form_action"] ?? "");

        if ($form_action === "add_student" || $form_action === "update_student") {
            $is_update = $form_action === "update_student";
            $student_id = (int) ($_POST["student_id"] ?? 0);

            $data = [
                "student_number" => strtoupper(clean_string($_POST["student_number"] ?? "")),
                "email" => strtolower(clean_string($_POST["email"] ?? "")),
                "first_name" => clean_string($_POST["first_name"] ?? ""),
                "middle_name" => clean_string($_POST["middle_name"] ?? ""),
                "last_name" => clean_string($_POST["last_name"] ?? ""),
                "department_id" => (int) ($_POST["department_id"] ?? 0),
                "course_id" => (int) ($_POST["course_id"] ?? 0),
                "year_level" => (int) ($_POST["current_year_level"] ?? 0),
                "section_id" => (int) ($_POST["current_section_id"] ?? 0),
                "status" => normalize_status(clean_string($_POST["student_status"] ?? "Active")),
                "password" => (string) ($_POST["password"] ?? "")
            ];

            $errors = validate_student_input($data, $is_update);

            $section = null;

            if ($data["section_id"] > 0) {
                $section = get_section_information($pdo, $data["section_id"]);

                if (!$section) {
                    $errors[] = "Selected section was not found.";
                } else {
                    if ((int) $section["course_id"] !== $data["course_id"]) {
                        $errors[] = "Selected section does not belong to the selected program.";
                    }

                    if ((int) $section["year_level"] !== $data["year_level"]) {
                        $errors[] = "Selected section does not match the selected year level.";
                    }

                    if ((int) $section["department_id"] !== $data["department_id"]) {
                        $errors[] = "Selected course does not belong to the selected college.";
                    }
                }
            }

            if (!$is_update) {
                $duplicate = $pdo->prepare("
                    SELECT COUNT(*)
                    FROM `user` u
                    LEFT JOIN student s ON s.user_id = u.user_id
                    WHERE u.university_id = :user_university_id
                    OR u.email = :user_email
                    OR s.student_number = :student_number
                    OR s.email = :student_email
                ");

                $duplicate->execute([
                    "user_university_id" => $data["student_number"],
                    "user_email" => $data["email"],
                    "student_number" => $data["student_number"],
                    "student_email" => $data["email"]
                ]);

                if ((int) $duplicate->fetchColumn() > 0) {
                    $errors[] = "Student ID or email already exists.";
                }
            } else {
                $current = $pdo->prepare("
                    SELECT user_id
                    FROM student
                    WHERE student_id = :student_id
                    LIMIT 1
                ");

                $current->execute(["student_id" => $student_id]);
                $current_user_id = (int) $current->fetchColumn();

                if ($current_user_id <= 0) {
                    $errors[] = "Student record was not found.";
                } else {
                    $duplicate = $pdo->prepare("
                        SELECT COUNT(*)
                        FROM `user` u
                        LEFT JOIN student s ON s.user_id = u.user_id
                        WHERE (
                            u.university_id = :user_university_id
                            OR u.email = :user_email
                            OR s.student_number = :student_number
                            OR s.email = :student_email
                        )
                        AND u.user_id <> :current_user_id
                    ");

                    $duplicate->execute([
                        "user_university_id" => $data["student_number"],
                        "user_email" => $data["email"],
                        "student_number" => $data["student_number"],
                        "student_email" => $data["email"],
                        "current_user_id" => $current_user_id
                    ]);

                    if ((int) $duplicate->fetchColumn() > 0) {
                        $errors[] = "Student ID or email already exists in another account.";
                    }
                }
            }

            if (count($errors) > 0) {
                redirect_with_message("error", implode(" ", $errors));
            }

            $pdo->beginTransaction();

            $full_name = build_full_name($data["first_name"], $data["middle_name"], $data["last_name"]);
            $account_status = user_account_status_from_student_status($data["status"]);

            if (!$is_update) {
                $role_id = get_student_role_id($pdo);
                $plain_password = $data["password"] !== "" ? $data["password"] : $data["student_number"];

                $insert_user = $pdo->prepare("
                    INSERT INTO `user` (
                        role_id,
                        university_id,
                        email,
                        password_hash,
                        account_status,
                        is_two_factor_enabled
                    )
                    VALUES (
                        :role_id,
                        :university_id,
                        :email,
                        :password_hash,
                        :account_status,
                        1
                    )
                ");

                $insert_user->execute([
                    "role_id" => $role_id,
                    "university_id" => $data["student_number"],
                    "email" => $data["email"],
                    "password_hash" => create_system_password_hash($plain_password),
                    "account_status" => $account_status
                ]);

                $user_id = (int) $pdo->lastInsertId();

                $insert_student = $pdo->prepare("
                    INSERT INTO student (
                        user_id,
                        student_number,
                        email,
                        first_name,
                        middle_name,
                        last_name,
                        full_name,
                        course_id,
                        current_year_level,
                        current_section_id,
                        academic_year_id,
                        student_status
                    )
                    VALUES (
                        :user_id,
                        :student_number,
                        :email,
                        :first_name,
                        :middle_name,
                        :last_name,
                        :full_name,
                        :course_id,
                        :current_year_level,
                        :current_section_id,
                        :academic_year_id,
                        :student_status
                    )
                ");

                $insert_student->execute([
                    "user_id" => $user_id,
                    "student_number" => $data["student_number"],
                    "email" => $data["email"],
                    "first_name" => $data["first_name"],
                    "middle_name" => $data["middle_name"],
                    "last_name" => $data["last_name"],
                    "full_name" => $full_name,
                    "course_id" => $data["course_id"],
                    "current_year_level" => $data["year_level"],
                    "current_section_id" => $data["section_id"],
                    "academic_year_id" => (int) $section["academic_year_id"],
                    "student_status" => $data["status"]
                ]);

                $new_student_id = (int) $pdo->lastInsertId();
                sync_student_enrollment($pdo, $new_student_id, $section);

                $pdo->commit();

                redirect_with_message("success", "Student " . $data["student_number"] . " " . display_student_name($data) . " successfully added!");
            }

            $student_user_statement = $pdo->prepare("
                SELECT user_id
                FROM student
                WHERE student_id = :student_id
                LIMIT 1
            ");

            $student_user_statement->execute(["student_id" => $student_id]);
            $user_id = (int) $student_user_statement->fetchColumn();

            $update_user_sql = "
                UPDATE `user`
                SET university_id = :university_id,
                    email = :email,
                    account_status = :account_status
            ";

            $update_user_params = [
                "university_id" => $data["student_number"],
                "email" => $data["email"],
                "account_status" => $account_status,
                "user_id" => $user_id
            ];

            if ($data["password"] !== "") {
                $update_user_sql .= ", password_hash = :password_hash";
                $update_user_params["password_hash"] = create_system_password_hash($data["password"]);
            }

            $update_user_sql .= " WHERE user_id = :user_id";

            $update_user = $pdo->prepare($update_user_sql);
            $update_user->execute($update_user_params);

            $update_student = $pdo->prepare("
                UPDATE student
                SET student_number = :student_number,
                    email = :email,
                    first_name = :first_name,
                    middle_name = :middle_name,
                    last_name = :last_name,
                    full_name = :full_name,
                    course_id = :course_id,
                    current_year_level = :current_year_level,
                    current_section_id = :current_section_id,
                    academic_year_id = :academic_year_id,
                    student_status = :student_status
                WHERE student_id = :student_id
            ");

            $update_student->execute([
                "student_number" => $data["student_number"],
                "email" => $data["email"],
                "first_name" => $data["first_name"],
                "middle_name" => $data["middle_name"],
                "last_name" => $data["last_name"],
                "full_name" => $full_name,
                "course_id" => $data["course_id"],
                "current_year_level" => $data["year_level"],
                "current_section_id" => $data["section_id"],
                "academic_year_id" => (int) $section["academic_year_id"],
                "student_status" => $data["status"],
                "student_id" => $student_id
            ]);

            sync_student_enrollment($pdo, $student_id, $section);

            $pdo->commit();

            redirect_with_message("success", "Student " . $data["student_number"] . " " . display_student_name($data) . " successfully updated!");
        }

        if ($form_action === "toggle_status") {
            $student_id = (int) ($_POST["student_id"] ?? 0);

            $statement = $pdo->prepare("
                SELECT s.student_status, s.user_id, s.student_number, s.first_name, s.middle_name, s.last_name
                FROM student s
                WHERE s.student_id = :student_id
                LIMIT 1
            ");

            $statement->execute(["student_id" => $student_id]);
            $student = $statement->fetch();

            if (!$student) {
                redirect_with_message("error", "Student record was not found.");
            }

            $new_status = $student["student_status"] === "Active" ? "Suspended" : "Active";
            $new_account_status = user_account_status_from_student_status($new_status);

            $pdo->beginTransaction();

            $pdo->prepare("
                UPDATE student
                SET student_status = :student_status
                WHERE student_id = :student_id
            ")->execute([
                "student_status" => $new_status,
                "student_id" => $student_id
            ]);

            $pdo->prepare("
                UPDATE `user`
                SET account_status = :account_status
                WHERE user_id = :user_id
            ")->execute([
                "account_status" => $new_account_status,
                "user_id" => $student["user_id"]
            ]);

            $pdo->commit();

            redirect_with_message("success", "Student " . $student["student_number"] . " status changed to " . $new_status . ".");
        }

        if ($form_action === "delete_student") {
            $student_id = (int) ($_POST["student_id"] ?? 0);

            $statement = $pdo->prepare("
                SELECT s.user_id, s.student_number, s.first_name, s.middle_name, s.last_name
                FROM student s
                WHERE s.student_id = :student_id
                LIMIT 1
            ");

            $statement->execute(["student_id" => $student_id]);
            $student = $statement->fetch();

            if (!$student) {
                redirect_with_message("error", "Student record was not found.");
            }

            $has_evaluation = student_has_evaluation_record($pdo, $student_id);

            $pdo->beginTransaction();

            if ($has_evaluation) {
                $pdo->prepare("
                    UPDATE student
                    SET student_status = 'Inactive'
                    WHERE student_id = :student_id
                ")->execute(["student_id" => $student_id]);

                $pdo->prepare("
                    UPDATE `user`
                    SET account_status = 'Inactive'
                    WHERE user_id = :user_id
                ")->execute(["user_id" => $student["user_id"]]);

                $pdo->commit();

                redirect_with_message("warning", "You cannot delete an entity with record inside. This will mark as inactive.");
            }

            try {
                $pdo->prepare("
                    DELETE FROM student_section_enrollment
                    WHERE student_id = :student_id
                ")->execute(["student_id" => $student_id]);

                $pdo->prepare("
                    DELETE FROM student
                    WHERE student_id = :student_id
                ")->execute(["student_id" => $student_id]);

                $pdo->prepare("
                    DELETE FROM `user`
                    WHERE user_id = :user_id
                ")->execute(["user_id" => $student["user_id"]]);

                $pdo->commit();

                redirect_with_message("success", "Student " . $student["student_number"] . " successfully deleted.");
            } catch (Throwable $delete_error) {
                $pdo->prepare("
                    UPDATE student
                    SET student_status = 'Inactive'
                    WHERE student_id = :student_id
                ")->execute(["student_id" => $student_id]);

                $pdo->prepare("
                    UPDATE `user`
                    SET account_status = 'Inactive'
                    WHERE user_id = :user_id
                ")->execute(["user_id" => $student["user_id"]]);

                $pdo->commit();

                redirect_with_message("warning", "You cannot delete an entity with record inside. This will mark as inactive.");
            }
        }
    }
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    redirect_with_message("error", "System error: " . $error->getMessage());
}

$departments_all = $pdo->query("
    SELECT department_id, department_code, department_name
    FROM department
    WHERE department_status = 'Active'
    ORDER BY department_name
")->fetchAll();

$courses_all = $pdo->query("
    SELECT course_id, department_id, course_code, course_name, number_of_year_level
    FROM course
    WHERE course_status = 'Active'
    ORDER BY course_code
")->fetchAll();

$academic_years_all = $pdo->query("
    SELECT academic_year_id, academic_year_name, academic_year_status
    FROM academic_year
    ORDER BY academic_year_name DESC
")->fetchAll();

$sections_all = $pdo->query("
    SELECT
        sec.section_id,
        sec.course_id,
        sec.term_id,
        sec.section_name,
        sec.year_level,
        sec.academic_year_id,
        c.department_id,
        c.course_code,
        t.term_name,
        COALESCE(ay_section.academic_year_name, ay_term.academic_year_name) AS academic_year_name
    FROM `section` sec
    INNER JOIN course c ON c.course_id = sec.course_id
    INNER JOIN term t ON t.term_id = sec.term_id
    LEFT JOIN academic_year ay_section ON ay_section.academic_year_id = sec.academic_year_id
    LEFT JOIN academic_year ay_term ON ay_term.academic_year_id = t.academic_year_id
    WHERE sec.section_status = 'Active'
    ORDER BY c.course_code, sec.year_level, sec.section_name
")->fetchAll();

$student_search = clean_string($_GET["q"] ?? "");
$filter_department_id = (int) ($_GET["department_id"] ?? 0);
$filter_course_id = (int) ($_GET["course_id"] ?? 0);
$filter_year_level = (int) ($_GET["year_level"] ?? 0);
$filter_academic_year_id = (int) ($_GET["academic_year_id"] ?? 0);
$filter_section_id = (int) ($_GET["section_id"] ?? 0);
$filter_status = clean_string($_GET["status"] ?? "");

$params = [];
$where = "WHERE 1 = 1";

if ($student_search !== "") {
    $where .= "
        AND (
            s.student_number LIKE :student_search
            OR s.first_name LIKE :first_name_search
            OR s.middle_name LIKE :middle_name_search
            OR s.last_name LIKE :last_name_search
            OR s.full_name LIKE :full_name_search
            OR s.email LIKE :student_email_search
            OR u.email LIKE :user_email_search
            OR u.university_id LIKE :university_search
        )
    ";

    $search_value = "%" . $student_search . "%";

    $params["student_search"] = $search_value;
    $params["first_name_search"] = $search_value;
    $params["middle_name_search"] = $search_value;
    $params["last_name_search"] = $search_value;
    $params["full_name_search"] = $search_value;
    $params["student_email_search"] = $search_value;
    $params["user_email_search"] = $search_value;
    $params["university_search"] = $search_value;
}

if ($filter_department_id > 0) {
    $where .= " AND c.department_id = :department_id";
    $params["department_id"] = $filter_department_id;
}

if ($filter_course_id > 0) {
    $where .= " AND s.course_id = :course_id";
    $params["course_id"] = $filter_course_id;
}

if ($filter_year_level > 0) {
    $where .= " AND s.current_year_level = :year_level";
    $params["year_level"] = $filter_year_level;
}

if ($filter_academic_year_id > 0) {
    $where .= " AND s.academic_year_id = :academic_year_id";
    $params["academic_year_id"] = $filter_academic_year_id;
}

if ($filter_section_id > 0) {
    $where .= " AND s.current_section_id = :section_id";
    $params["section_id"] = $filter_section_id;
}

if ($filter_status !== "") {
    $where .= " AND s.student_status = :student_status";
    $params["student_status"] = $filter_status;
}

$students_statement = $pdo->prepare("
    SELECT
        s.student_id,
        s.user_id,
        s.student_number,
        s.email AS student_email,
        s.first_name,
        s.middle_name,
        s.last_name,
        s.full_name,
        s.course_id,
        s.current_year_level,
        s.current_section_id,
        s.academic_year_id,
        s.student_status,
        u.email AS user_email,
        u.account_status,
        c.course_code,
        c.course_name,
        c.department_id,
        d.department_name,
        sec.section_name,
        sec.term_id,
        t.term_name,
        COALESCE(ay_student.academic_year_name, ay_section.academic_year_name, ay_term.academic_year_name) AS academic_year_name
    FROM student s
    INNER JOIN `user` u ON u.user_id = s.user_id
    INNER JOIN course c ON c.course_id = s.course_id
    INNER JOIN department d ON d.department_id = c.department_id
    LEFT JOIN `section` sec ON sec.section_id = s.current_section_id
    LEFT JOIN term t ON t.term_id = sec.term_id
    LEFT JOIN academic_year ay_student ON ay_student.academic_year_id = s.academic_year_id
    LEFT JOIN academic_year ay_section ON ay_section.academic_year_id = sec.academic_year_id
    LEFT JOIN academic_year ay_term ON ay_term.academic_year_id = t.academic_year_id
    $where
    ORDER BY s.last_name, s.first_name, s.student_number
");

$students_statement->execute($params);
$students = $students_statement->fetchAll();
$student_count = count($students);

$total_students = (int) $pdo->query("SELECT COUNT(*) FROM student")->fetchColumn();

$selected_student = null;

if (($modal_action === "view" || $modal_action === "edit") && $modal_id > 0) {
    $selected_statement = $pdo->prepare("
        SELECT
            s.student_id,
            s.user_id,
            s.student_number,
            s.email AS student_email,
            s.first_name,
            s.middle_name,
            s.last_name,
            s.full_name,
            s.course_id,
            s.current_year_level,
            s.current_section_id,
            s.academic_year_id,
            s.student_status,
            u.email AS user_email,
            u.account_status,
            c.course_code,
            c.course_name,
            c.department_id,
            d.department_name,
            sec.section_name,
            sec.term_id,
            t.term_name,
            COALESCE(ay_student.academic_year_name, ay_section.academic_year_name, ay_term.academic_year_name) AS academic_year_name
        FROM student s
        INNER JOIN `user` u ON u.user_id = s.user_id
        INNER JOIN course c ON c.course_id = s.course_id
        INNER JOIN department d ON d.department_id = c.department_id
        LEFT JOIN `section` sec ON sec.section_id = s.current_section_id
        LEFT JOIN term t ON t.term_id = sec.term_id
        LEFT JOIN academic_year ay_student ON ay_student.academic_year_id = s.academic_year_id
        LEFT JOIN academic_year ay_section ON ay_section.academic_year_id = sec.academic_year_id
        LEFT JOIN academic_year ay_term ON ay_term.academic_year_id = t.academic_year_id
        WHERE s.student_id = :student_id
        LIMIT 1
    ");

    $selected_statement->execute(["student_id" => $modal_id]);
    $selected_student = $selected_statement->fetch();
}

$has_filters = $student_search !== ""
    || $filter_department_id > 0
    || $filter_course_id > 0
    || $filter_year_level > 0
    || $filter_academic_year_id > 0
    || $filter_section_id > 0
    || $filter_status !== "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management</title>
    <link rel="stylesheet" href="student-management.css?v=<?php echo time(); ?>">
</head>
<body class="student-management-page">
    <aside class="sidebar">
        <div class="brand">
            <img src="../img/eastgate_college_logo.png" alt="Eastgate College Logo">
            <h1>Student Evaluation for Teacher</h1>
            <p>System Administrator</p>
        </div>

        <nav class="nav-menu">
            <a class="nav-link" href="admin_dashboard.php"><?php echo icon_svg("dashboard"); ?> Dashboard</a>
            <a class="nav-link active" href="student_management.php"><?php echo icon_svg("students"); ?> Student Management</a>
            <a class="nav-link" href="faculty_management.php"><?php echo icon_svg("faculty"); ?> Faculty Management</a>
            <a class="nav-link" href="academic_structure.php"><?php echo icon_svg("book"); ?> Academic Structure</a>
            <a class="nav-link" href="assignment_management.php"><?php echo icon_svg("assignment"); ?> Assignment Management</a>
            <a class="nav-link" href="evaluation_setup.php"><?php echo icon_svg("settings"); ?> Evaluation Setup</a>
            <a class="nav-link" href="#"><?php echo icon_svg("clipboard"); ?> Submission Monitoring</a>
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
                    <h1>Student Management</h1>
                    <p>Manage student accounts and records</p>
                </div>

                <a class="primary-button" href="student_management.php?action=add">
                    <?php echo icon_svg("plus"); ?> Add Student
                </a>
            </div>

            <section class="filter-panel">
                <form class="student-filter-form auto-filter-form" method="GET" action="student_management.php">
                    <div class="search-box">
                        <?php echo icon_svg("search"); ?>
                        <input
                            type="text"
                            name="q"
                            value="<?php echo e($student_search); ?>"
                            placeholder="Search by ID, name, or email..."
                        >
                    </div>

                    <a class="clear-filter clear-beside-search" href="student_management.php">Clear</a>

                    <select name="department_id" id="filter_department_id">
                        <option value="0">All Colleges</option>
                        <?php foreach ($departments_all as $department): ?>
                            <option value="<?php echo (int) $department["department_id"]; ?>" <?php echo $filter_department_id === (int) $department["department_id"] ? "selected" : ""; ?>>
                                <?php echo e($department["department_name"]); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="course_id" id="filter_course_id">
                        <option value="0">All Programs</option>
                        <?php foreach ($courses_all as $course): ?>
                            <option
                                value="<?php echo (int) $course["course_id"]; ?>"
                                data-department-id="<?php echo (int) $course["department_id"]; ?>"
                                <?php echo $filter_course_id === (int) $course["course_id"] ? "selected" : ""; ?>
                            >
                                <?php echo e($course["course_code"]); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="year_level" id="filter_year_level">
                        <option value="0">All Year Levels</option>
                        <?php for ($i = 1; $i <= 6; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $filter_year_level === $i ? "selected" : ""; ?>>
                                <?php echo year_level_label($i); ?>
                            </option>
                        <?php endfor; ?>
                    </select>

                    <select name="academic_year_id" id="filter_academic_year_id">
                        <option value="0">All Academic Years</option>
                        <?php foreach ($academic_years_all as $year): ?>
                            <option value="<?php echo (int) $year["academic_year_id"]; ?>" <?php echo $filter_academic_year_id === (int) $year["academic_year_id"] ? "selected" : ""; ?>>
                                <?php echo e($year["academic_year_name"]); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="section_id" id="filter_section_id">
                        <option value="0">All Sections</option>
                        <?php foreach ($sections_all as $section): ?>
                            <option
                                value="<?php echo (int) $section["section_id"]; ?>"
                                data-department-id="<?php echo (int) $section["department_id"]; ?>"
                                data-course-id="<?php echo (int) $section["course_id"]; ?>"
                                data-year-level="<?php echo (int) $section["year_level"]; ?>"
                                data-academic-year-id="<?php echo (int) $section["academic_year_id"]; ?>"
                                <?php echo $filter_section_id === (int) $section["section_id"] ? "selected" : ""; ?>
                            >
                                <?php echo e($section["section_name"]); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="status">
                        <option value="">All Status</option>
                        <?php foreach (["Active", "Inactive", "Suspended", "Restricted"] as $status): ?>
                            <option value="<?php echo e($status); ?>" <?php echo $filter_status === $status ? "selected" : ""; ?>>
                                <?php echo e($status); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>

                <div class="filter-summary">
                    <span>Showing <?php echo $student_count; ?> of <?php echo $total_students; ?> students</span>
                    <span><?php echo icon_svg("filter"); ?> <?php echo $has_filters ? "Filters applied" : "No filters applied"; ?></span>
                </div>
            </section>

            <section class="table-card">
                <table class="student-table">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Program</th>
                            <th>Year Level</th>
                            <th>Academic Year</th>
                            <th>Section</th>
                            <th>Status</th>
                            <th class="actions-head">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (count($students) === 0): ?>
                            <tr>
                                <td colspan="9" class="empty-row">No students found matching your search criteria.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><strong><?php echo e($student["student_number"]); ?></strong></td>
                                <td><?php echo e(display_student_name($student)); ?></td>
                                <td><?php echo e($student["student_email"] ?: $student["user_email"]); ?></td>
                                <td><?php echo e($student["course_code"]); ?></td>
                                <td><?php echo e(year_level_label((int) $student["current_year_level"])); ?></td>
                                <td><?php echo e($student["academic_year_name"] ?? "Not set"); ?></td>
                                <td><?php echo e($student["section_name"] ?? "No section"); ?></td>
                                <td>
                                    <span class="status-badge <?php echo strtolower(e($student["student_status"])); ?>">
                                        <?php echo e(strtolower($student["student_status"])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-icons">
                                        <a class="view" title="View" href="student_management.php?action=view&id=<?php echo (int) $student["student_id"]; ?>">
                                            <?php echo icon_svg("eye"); ?>
                                        </a>

                                        <a class="edit" title="Edit" href="student_management.php?action=edit&id=<?php echo (int) $student["student_id"]; ?>">
                                            <?php echo icon_svg("edit"); ?>
                                        </a>

                                        <form method="POST" action="student_management.php">
                                            <input type="hidden" name="form_action" value="toggle_status">
                                            <input type="hidden" name="student_id" value="<?php echo (int) $student["student_id"]; ?>">

                                            <button class="<?php echo $student["student_status"] === "Active" ? "suspend" : "activate"; ?>" type="submit" title="<?php echo $student["student_status"] === "Active" ? "Suspend Account" : "Activate Account"; ?>">
                                                <?php echo $student["student_status"] === "Active" ? icon_svg("ban") : icon_svg("check"); ?>
                                            </button>
                                        </form>

                                        <form method="POST" action="student_management.php" onsubmit="return confirm('Delete this student record?');">
                                            <input type="hidden" name="form_action" value="delete_student">
                                            <input type="hidden" name="student_id" value="<?php echo (int) $student["student_id"]; ?>">

                                            <button class="delete" type="submit" title="Delete">
                                                <?php echo icon_svg("trash"); ?>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        </div>
    </main>

    <?php if ($modal_action === "add"): ?>
        <div class="modal-overlay">
            <div class="modal-box student-modal">
                <h2>Add New Student</h2>

                <form method="POST" action="student_management.php" id="studentForm">
                    <input type="hidden" name="form_action" value="add_student">

                    <div class="two-column">
                        <div>
                            <label>Student ID</label>
                            <input type="text" name="student_number" id="modal_student_number" placeholder="e.g., 24-00123" required>
                        </div>

                        <div>
                            <label>Email</label>
                            <input type="email" name="email" id="modal_email" placeholder="e.g., student@eastgate.edu.ph" required>
                        </div>
                    </div>

                    <div class="three-column">
                        <div>
                            <label>First Name</label>
                            <input type="text" name="first_name" id="modal_first_name" placeholder="e.g., Juan" required>
                        </div>

                        <div>
                            <label>Middle Initial</label>
                            <input type="text" name="middle_name" id="modal_middle_name" placeholder="e.g., D.">
                        </div>

                        <div>
                            <label>Last Name</label>
                            <input type="text" name="last_name" id="modal_last_name" placeholder="e.g., Dela Cruz" required>
                        </div>
                    </div>

                    <label>College</label>
                    <select name="department_id" id="modal_department_id" required>
                        <option value="">Select College</option>
                        <?php foreach ($departments_all as $department): ?>
                            <option value="<?php echo (int) $department["department_id"]; ?>">
                                <?php echo e($department["department_name"]); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div class="three-column">
                        <div>
                            <label>Program</label>
                            <select name="course_id" id="modal_course_id" required>
                                <option value="">Select Program</option>
                                <?php foreach ($courses_all as $course): ?>
                                    <option
                                        value="<?php echo (int) $course["course_id"]; ?>"
                                        data-department-id="<?php echo (int) $course["department_id"]; ?>"
                                        data-year-levels="<?php echo (int) $course["number_of_year_level"]; ?>"
                                    >
                                        <?php echo e($course["course_code"]); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label>Year Level</label>
                            <select name="current_year_level" id="modal_year_level" required>
                                <option value="">Select Year Level</option>
                                <?php for ($i = 1; $i <= 6; $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo year_level_label($i); ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div>
                            <label>Section</label>
                            <select name="current_section_id" id="modal_section_id" required>
                                <option value="">Select program and year</option>
                                <?php foreach ($sections_all as $section): ?>
                                    <option
                                        value="<?php echo (int) $section["section_id"]; ?>"
                                        data-department-id="<?php echo (int) $section["department_id"]; ?>"
                                        data-course-id="<?php echo (int) $section["course_id"]; ?>"
                                        data-year-level="<?php echo (int) $section["year_level"]; ?>"
                                        data-term-name="<?php echo e($section["term_name"]); ?>"
                                        data-academic-year-id="<?php echo (int) $section["academic_year_id"]; ?>"
                                        data-academic-year-name="<?php echo e($section["academic_year_name"]); ?>"
                                    >
                                        <?php echo e($section["section_name"]); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="two-column">
                        <div>
                            <label>Semester</label>
                            <input type="text" id="modal_semester_text" value="" placeholder="Auto-filled from section" readonly>
                        </div>

                        <div>
                            <label>Academic Year</label>
                            <input type="text" id="modal_academic_year_text" value="" placeholder="Auto-filled from section" readonly>
                        </div>
                    </div>

                    <label>Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" placeholder="Leave blank to use Student ID as password">
                        <button type="button" class="password-toggle" aria-label="Show password">
                            <span class="eye-open-icon"><?php echo icon_svg("eye"); ?></span>
                            <span class="eye-closed-icon"><?php echo icon_svg("eye_closed"); ?></span>
                        </button>
                    </div>

                    <div class="modal-actions">
                        <a href="student_management.php" class="secondary-button">Cancel</a>
                        <button type="submit" class="dark-button">Add Student</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($modal_action === "view" && $selected_student): ?>
        <div class="modal-overlay">
            <div class="modal-box student-modal">
                <h2>Student Record Details</h2>

                <div class="two-column">
                    <div>
                        <label>Student ID</label>
                        <input type="text" value="<?php echo e($selected_student["student_number"]); ?>" readonly>
                    </div>

                    <div>
                        <label>Email</label>
                        <input type="text" value="<?php echo e($selected_student["student_email"] ?: $selected_student["user_email"]); ?>" readonly>
                    </div>
                </div>

                <div class="three-column">
                    <div>
                        <label>First Name</label>
                        <input type="text" value="<?php echo e($selected_student["first_name"]); ?>" readonly>
                    </div>

                    <div>
                        <label>Middle Initial</label>
                        <input type="text" value="<?php echo e($selected_student["middle_name"]); ?>" readonly>
                    </div>

                    <div>
                        <label>Last Name</label>
                        <input type="text" value="<?php echo e($selected_student["last_name"]); ?>" readonly>
                    </div>
                </div>

                <label>College</label>
                <input type="text" value="<?php echo e($selected_student["department_name"]); ?>" readonly>

                <div class="three-column">
                    <div>
                        <label>Program</label>
                        <input type="text" value="<?php echo e($selected_student["course_code"]); ?>" readonly>
                    </div>

                    <div>
                        <label>Year Level</label>
                        <input type="text" value="<?php echo e(year_level_label((int) $selected_student["current_year_level"])); ?>" readonly>
                    </div>

                    <div>
                        <label>Section</label>
                        <input type="text" value="<?php echo e($selected_student["section_name"] ?? "No section"); ?>" readonly>
                    </div>
                </div>

                <div class="two-column">
                    <div>
                        <label>Semester</label>
                        <input type="text" value="<?php echo e($selected_student["term_name"] ?? "Not set"); ?>" readonly>
                    </div>

                    <div>
                        <label>Academic Year</label>
                        <input type="text" value="<?php echo e($selected_student["academic_year_name"] ?? "Not set"); ?>" readonly>
                    </div>
                </div>

                <label>Status</label>
                <div class="readonly-status">
                    <span class="status-badge <?php echo strtolower(e($selected_student["student_status"])); ?>">
                        <?php echo e(strtolower($selected_student["student_status"])); ?>
                    </span>
                </div>

                <div class="modal-divider"></div>

                <div class="modal-actions">
                    <a href="student_management.php" class="secondary-button">Cancel</a>
                    <a href="student_management.php?action=edit&id=<?php echo (int) $selected_student["student_id"]; ?>" class="dark-button">Edit Information</a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($modal_action === "edit" && $selected_student): ?>
        <div class="modal-overlay">
            <div class="modal-box student-modal">
                <h2>Edit Student</h2>

                <form method="POST" action="student_management.php" id="studentForm">
                    <input type="hidden" name="form_action" value="update_student">
                    <input type="hidden" name="student_id" value="<?php echo (int) $selected_student["student_id"]; ?>">

                    <div class="two-column">
                        <div>
                            <label>Student ID</label>
                            <input type="text" name="student_number" id="modal_student_number" value="<?php echo e($selected_student["student_number"]); ?>" required>
                        </div>

                        <div>
                            <label>Email</label>
                            <input type="email" name="email" id="modal_email" value="<?php echo e($selected_student["student_email"] ?: $selected_student["user_email"]); ?>" required>
                        </div>
                    </div>

                    <div class="three-column">
                        <div>
                            <label>First Name</label>
                            <input type="text" name="first_name" id="modal_first_name" value="<?php echo e($selected_student["first_name"]); ?>" required>
                        </div>

                        <div>
                            <label>Middle Initial</label>
                            <input type="text" name="middle_name" id="modal_middle_name" value="<?php echo e($selected_student["middle_name"]); ?>">
                        </div>

                        <div>
                            <label>Last Name</label>
                            <input type="text" name="last_name" id="modal_last_name" value="<?php echo e($selected_student["last_name"]); ?>" required>
                        </div>
                    </div>

                    <label>College</label>
                    <select name="department_id" id="modal_department_id" required>
                        <?php foreach ($departments_all as $department): ?>
                            <option
                                value="<?php echo (int) $department["department_id"]; ?>"
                                <?php echo (int) $selected_student["department_id"] === (int) $department["department_id"] ? "selected" : ""; ?>
                            >
                                <?php echo e($department["department_name"]); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div class="three-column">
                        <div>
                            <label>Program</label>
                            <select name="course_id" id="modal_course_id" required>
                                <?php foreach ($courses_all as $course): ?>
                                    <option
                                        value="<?php echo (int) $course["course_id"]; ?>"
                                        data-department-id="<?php echo (int) $course["department_id"]; ?>"
                                        data-year-levels="<?php echo (int) $course["number_of_year_level"]; ?>"
                                        <?php echo (int) $selected_student["course_id"] === (int) $course["course_id"] ? "selected" : ""; ?>
                                    >
                                        <?php echo e($course["course_code"]); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label>Year Level</label>
                            <select name="current_year_level" id="modal_year_level" required>
                                <?php for ($i = 1; $i <= 6; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo (int) $selected_student["current_year_level"] === $i ? "selected" : ""; ?>>
                                        <?php echo year_level_label($i); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div>
                            <label>Section</label>
                            <select name="current_section_id" id="modal_section_id" required>
                                <?php foreach ($sections_all as $section): ?>
                                    <option
                                        value="<?php echo (int) $section["section_id"]; ?>"
                                        data-department-id="<?php echo (int) $section["department_id"]; ?>"
                                        data-course-id="<?php echo (int) $section["course_id"]; ?>"
                                        data-year-level="<?php echo (int) $section["year_level"]; ?>"
                                        data-term-name="<?php echo e($section["term_name"]); ?>"
                                        data-academic-year-id="<?php echo (int) $section["academic_year_id"]; ?>"
                                        data-academic-year-name="<?php echo e($section["academic_year_name"]); ?>"
                                        <?php echo (int) $selected_student["current_section_id"] === (int) $section["section_id"] ? "selected" : ""; ?>
                                    >
                                        <?php echo e($section["section_name"]); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="two-column">
                        <div>
                            <label>Semester</label>
                            <input type="text" id="modal_semester_text" value="<?php echo e($selected_student["term_name"] ?? ""); ?>" placeholder="Auto-filled from section" readonly>
                        </div>

                        <div>
                            <label>Academic Year</label>
                            <input type="text" id="modal_academic_year_text" value="<?php echo e($selected_student["academic_year_name"] ?? ""); ?>" placeholder="Auto-filled from section" readonly>
                        </div>
                    </div>

                    <label>Status</label>
                    <select name="student_status" required>
                        <?php foreach (["Active", "Inactive", "Suspended", "Restricted"] as $status): ?>
                            <option value="<?php echo e($status); ?>" <?php echo $selected_student["student_status"] === $status ? "selected" : ""; ?>>
                                <?php echo e($status); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label>Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" placeholder="Leave blank to keep current">
                        <button type="button" class="password-toggle" aria-label="Show password">
                            <span class="eye-open-icon"><?php echo icon_svg("eye"); ?></span>
                            <span class="eye-closed-icon"><?php echo icon_svg("eye_closed"); ?></span>
                        </button>
                    </div>

                    <div class="modal-actions">
                        <a href="student_management.php" class="secondary-button">Cancel</a>
                        <button type="submit" class="dark-button">Update Student</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($toast_message !== ""): ?>
        <div class="toast-modal <?php echo e($toast_type); ?>" id="toastModal">
            <?php echo e($toast_message); ?>
        </div>
    <?php endif; ?>

    <script>
        const autoFilterForm = document.querySelector(".auto-filter-form");
        const searchInput = autoFilterForm ? autoFilterForm.querySelector("input[name='q']") : null;
        const filterSelects = autoFilterForm ? autoFilterForm.querySelectorAll("select") : [];
        let searchTimer = null;

        function submitAutoFilter() {
            if (autoFilterForm) {
                autoFilterForm.submit();
            }
        }

        if (searchInput) {
            searchInput.addEventListener("input", function () {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(submitAutoFilter, 450);
            });
        }

        filterSelects.forEach(function (select) {
            select.addEventListener("change", submitAutoFilter);
        });

        function setupFilterDependencies() {
            const departmentSelect = document.getElementById("filter_department_id");
            const courseSelect = document.getElementById("filter_course_id");
            const yearSelect = document.getElementById("filter_year_level");
            const academicYearSelect = document.getElementById("filter_academic_year_id");
            const sectionSelect = document.getElementById("filter_section_id");

            if (!departmentSelect || !courseSelect || !yearSelect || !academicYearSelect || !sectionSelect) {
                return;
            }

            function filterOptions() {
                const departmentId = departmentSelect.value;
                const courseId = courseSelect.value;
                const yearLevel = yearSelect.value;
                const academicYearId = academicYearSelect.value;

                courseSelect.querySelectorAll("option").forEach(function (option) {
                    if (option.value === "0") {
                        option.hidden = false;
                        return;
                    }

                    const show = departmentId === "0" || option.dataset.departmentId === departmentId;
                    option.hidden = !show;
                });

                sectionSelect.querySelectorAll("option").forEach(function (option) {
                    if (option.value === "0") {
                        option.hidden = false;
                        return;
                    }

                    const showDepartment = departmentId === "0" || option.dataset.departmentId === departmentId;
                    const showCourse = courseId === "0" || option.dataset.courseId === courseId;
                    const showYear = yearLevel === "0" || option.dataset.yearLevel === yearLevel;
                    const showAcademicYear = academicYearId === "0" || option.dataset.academicYearId === academicYearId;

                    option.hidden = !(showDepartment && showCourse && showYear && showAcademicYear);
                });
            }

            filterOptions();
        }

        function expectedStudentIdPrefix(yearLevel) {
            const level = parseInt(yearLevel || "0", 10);

            if (level < 1 || level > 6) {
                return "";
            }

            return String(26 - level);
        }

        function formatStudentNumberInput(input) {
            let digits = input.value.replace(/\D/g, "").slice(0, 7);

            if (digits.length > 2) {
                input.value = digits.slice(0, 2) + "-" + digits.slice(2);
            } else {
                input.value = digits;
            }
        }

        function validateModalStudentNumber() {
            const studentNumberInput = document.getElementById("modal_student_number");
            const yearSelect = document.getElementById("modal_year_level");

            if (!studentNumberInput || !yearSelect) {
                return true;
            }

            const studentNumber = studentNumberInput.value.trim();
            const yearLevel = yearSelect.value;
            const expectedPrefix = expectedStudentIdPrefix(yearLevel);
            const pattern = /^[0-9]{2}-[0-9]{5}$/;

            studentNumberInput.setCustomValidity("");

            if (studentNumber === "") {
                return false;
            }

            if (!pattern.test(studentNumber)) {
                studentNumberInput.setCustomValidity("Student ID must strictly follow this format: 24-00123.");
                return false;
            }

            if (expectedPrefix === "") {
                studentNumberInput.setCustomValidity("Please select a year level before entering the Student ID.");
                return false;
            }

            if (studentNumber.substring(0, 2) !== expectedPrefix) {
                studentNumberInput.setCustomValidity(yearSelect.options[yearSelect.selectedIndex].text + " students must use Student ID starting with " + expectedPrefix + ". Example: " + expectedPrefix + "-00123.");
                return false;
            }

            return true;
        }

        function makeEmailPart(value) {
            return value
                .toLowerCase()
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "")
                .replace(/[^a-z0-9]/g, "");
        }

        function buildStudentEmail(firstName, lastName) {
            const firstPart = makeEmailPart(firstName);
            const lastPart = makeEmailPart(lastName);

            if (firstPart === "" || lastPart === "") {
                return "";
            }

            return firstPart + "." + lastPart + "@eastgate.edu.ph";
        }

        function setupAutomaticEmail() {
            const firstNameInput = document.getElementById("modal_first_name");
            const lastNameInput = document.getElementById("modal_last_name");
            const emailInput = document.getElementById("modal_email");

            if (!firstNameInput || !lastNameInput || !emailInput) {
                return;
            }

            let adminEditedEmail = false;

            emailInput.addEventListener("input", function () {
                adminEditedEmail = true;
            });

            function updateEmail() {
                if (adminEditedEmail) {
                    return;
                }

                const generatedEmail = buildStudentEmail(firstNameInput.value, lastNameInput.value);

                if (generatedEmail !== "") {
                    emailInput.value = generatedEmail;
                }
            }

            firstNameInput.addEventListener("input", updateEmail);
            lastNameInput.addEventListener("input", updateEmail);
            firstNameInput.addEventListener("blur", updateEmail);
            lastNameInput.addEventListener("blur", updateEmail);
        }

        function setupStudentModalDependencies() {
            const departmentSelect = document.getElementById("modal_department_id");
            const courseSelect = document.getElementById("modal_course_id");
            const yearSelect = document.getElementById("modal_year_level");
            const sectionSelect = document.getElementById("modal_section_id");
            const semesterInput = document.getElementById("modal_semester_text");
            const academicYearInput = document.getElementById("modal_academic_year_text");
            const studentNumberInput = document.getElementById("modal_student_number");
            const studentForm = document.getElementById("studentForm");

            if (!departmentSelect || !courseSelect || !yearSelect || !sectionSelect) {
                return;
            }

            function filterModalCourses() {
                const departmentId = departmentSelect.value;

                courseSelect.querySelectorAll("option").forEach(function (option) {
                    if (option.value === "") {
                        option.hidden = false;
                        return;
                    }

                    option.hidden = departmentId !== "" && option.dataset.departmentId !== departmentId;
                });

                const selectedOption = courseSelect.options[courseSelect.selectedIndex];

                if (selectedOption && selectedOption.hidden) {
                    courseSelect.value = "";
                }
            }

            function filterModalYearLevels() {
                const selectedCourse = courseSelect.options[courseSelect.selectedIndex];
                const maxYear = selectedCourse ? parseInt(selectedCourse.dataset.yearLevels || "6", 10) : 6;

                yearSelect.querySelectorAll("option").forEach(function (option) {
                    if (option.value === "") {
                        option.hidden = false;
                        return;
                    }

                    option.hidden = parseInt(option.value, 10) > maxYear;
                });

                const selectedYear = yearSelect.options[yearSelect.selectedIndex];

                if (selectedYear && selectedYear.hidden) {
                    yearSelect.value = "";
                }

                validateModalStudentNumber();
            }

            function filterModalSections() {
                const departmentId = departmentSelect.value;
                const courseId = courseSelect.value;
                const yearLevel = yearSelect.value;

                sectionSelect.querySelectorAll("option").forEach(function (option) {
                    if (option.value === "") {
                        option.hidden = false;
                        return;
                    }

                    const showDepartment = departmentId === "" || option.dataset.departmentId === departmentId;
                    const showCourse = courseId === "" || option.dataset.courseId === courseId;
                    const showYear = yearLevel === "" || option.dataset.yearLevel === yearLevel;

                    option.hidden = !(showDepartment && showCourse && showYear);
                });

                const selectedSection = sectionSelect.options[sectionSelect.selectedIndex];

                if (selectedSection && selectedSection.hidden) {
                    sectionSelect.value = "";
                    updateSectionDetails();
                }
            }

            function updateSectionDetails() {
                const selectedSection = sectionSelect.options[sectionSelect.selectedIndex];

                if (!selectedSection || selectedSection.value === "") {
                    if (semesterInput) {
                        semesterInput.value = "";
                    }

                    if (academicYearInput) {
                        academicYearInput.value = "";
                    }

                    return;
                }

                if (semesterInput) {
                    semesterInput.value = selectedSection.dataset.termName || "";
                }

                if (academicYearInput) {
                    academicYearInput.value = selectedSection.dataset.academicYearName || "";
                }
            }

            function refreshAll() {
                filterModalCourses();
                filterModalYearLevels();
                filterModalSections();
                updateSectionDetails();
                validateModalStudentNumber();
            }

            if (studentNumberInput) {
                studentNumberInput.addEventListener("input", function () {
                    formatStudentNumberInput(studentNumberInput);
                    validateModalStudentNumber();
                });

                studentNumberInput.addEventListener("blur", validateModalStudentNumber);
            }

            if (studentForm) {
                studentForm.addEventListener("submit", function (event) {
                    if (!validateModalStudentNumber()) {
                        event.preventDefault();

                        if (studentNumberInput) {
                            studentNumberInput.reportValidity();
                        }
                    }
                });
            }

            departmentSelect.addEventListener("change", refreshAll);
            courseSelect.addEventListener("change", refreshAll);
            yearSelect.addEventListener("change", refreshAll);
            sectionSelect.addEventListener("change", updateSectionDetails);

            refreshAll();
        }

        setupFilterDependencies();
        setupStudentModalDependencies();
        setupAutomaticEmail();

        const toastModal = document.getElementById("toastModal");

        if (toastModal) {
            setTimeout(function () {
                toastModal.classList.add("hide");
            }, 2600);

            setTimeout(function () {
                toastModal.remove();
            }, 3200);
        }

        document.querySelectorAll(".password-toggle").forEach(function (button) {
            button.addEventListener("click", function () {
                const wrapper = button.closest(".password-wrapper");
                const input = wrapper.querySelector("input");

                if (input.type === "password") {
                    input.type = "text";
                    button.classList.add("is-visible");
                    button.setAttribute("aria-label", "Hide password");
                } else {
                    input.type = "password";
                    button.classList.remove("is-visible");
                    button.setAttribute("aria-label", "Show password");
                }
            });
        });
    </script>
</body>
</html>