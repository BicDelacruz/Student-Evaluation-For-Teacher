<?php
session_start();

require_once __DIR__ . "/../database_connector.php";
require_once __DIR__ . "/../generate_password_hash.php";

if (!isset($pdo)) {
    die("Database connection variable \$pdo was not found. Please check database_connector.php.");
}

if (!function_exists("create_system_password_hash")) {
    function create_system_password_hash($plain_password)
    {
        return password_hash($plain_password, PASSWORD_DEFAULT);
    }
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
    header("Location: faculty_management.php?toast_type=" . urlencode($type) . "&toast_message=" . urlencode($message));
    exit;
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

function normalize_faculty_status($status)
{
    $allowed = ["Active", "Inactive", "Suspended"];
    return in_array($status, $allowed, true) ? $status : "Active";
}

function normalize_employment_status($status)
{
    $allowed = ["Full Time", "Part Time"];
    return in_array($status, $allowed, true) ? $status : "Full Time";
}

function normalize_academic_rank($rank)
{
    $allowed = [
        "Professor",
        "Associate Professor",
        "Assistant Professor",
        "Instructor",
        "Dean"
    ];

    return in_array($rank, $allowed, true) ? $rank : "";
}

function user_account_status_from_faculty_status($faculty_status)
{
    if ($faculty_status === "Suspended") {
        return "Suspended";
    }

    if ($faculty_status === "Inactive") {
        return "Inactive";
    }

    return "Active";
}

function validate_faculty_number($faculty_number)
{
    $faculty_number = strtoupper(clean_string($faculty_number));

    if (preg_match("/^FAC-[0-9]{5}$/", $faculty_number) !== 1) {
        return "Faculty ID must strictly follow this format: FAC-01234.";
    }

    return "";
}

function validate_faculty_input($data, $is_update)
{
    $errors = [];

    $faculty_number_error = validate_faculty_number($data["faculty_number"]);

    if ($faculty_number_error !== "") {
        $errors[] = $faculty_number_error;
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

    if ($data["academic_year_id"] <= 0) {
        $errors[] = "Academic year is required.";
    }

    if ($data["academic_rank"] === "") {
        $errors[] = "Position is required.";
    }

    if ($data["employment_status"] === "") {
        $errors[] = "Employment status is required.";
    }

    if ($data["academic_rank"] === "Dean" && $data["employment_status"] !== "Full Time") {
        $errors[] = "Dean must have Full Time employment status.";
    }

    if ($data["password"] !== "" && strlen($data["password"]) < 6) {
        $errors[] = $is_update
            ? "New password must have at least 6 characters."
            : "Password must have at least 6 characters. Leave it blank if you want to use the Faculty ID as the default password.";
    }

    return $errors;
}

function get_faculty_role_id($pdo)
{
    $statement = $pdo->query("
        SELECT role_id
        FROM `role`
        WHERE LOWER(role_name) = 'faculty'
        LIMIT 1
    ");

    $role_id = $statement->fetchColumn();

    if (!$role_id) {
        throw new RuntimeException("Faculty role was not found in the role table.");
    }

    return (int) $role_id;
}

function faculty_has_assignment_record($pdo, $faculty_id)
{
    $statement = $pdo->prepare("
        SELECT COUNT(*)
        FROM teaching_assignment
        WHERE faculty_id = :faculty_id
    ");

    $statement->execute(["faculty_id" => $faculty_id]);

    return (int) $statement->fetchColumn() > 0;
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

$toast_type = clean_string($_GET["toast_type"] ?? "");
$toast_message = clean_string($_GET["toast_message"] ?? "");

$modal_action = clean_string($_GET["action"] ?? "");
$modal_id = (int) ($_GET["id"] ?? 0);

$rank_options = [
    "Professor",
    "Associate Professor",
    "Assistant Professor",
    "Instructor",
    "Dean"
];

$employment_options = [
    "Full Time",
    "Part Time"
];

try {
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $form_action = clean_string($_POST["form_action"] ?? "");

        if ($form_action === "add_faculty" || $form_action === "update_faculty") {
            $is_update = $form_action === "update_faculty";
            $faculty_id = (int) ($_POST["faculty_id"] ?? 0);

            $data = [
                "faculty_number" => strtoupper(clean_string($_POST["faculty_number"] ?? "")),
                "email" => strtolower(clean_string($_POST["email"] ?? "")),
                "first_name" => clean_string($_POST["first_name"] ?? ""),
                "middle_name" => clean_string($_POST["middle_name"] ?? ""),
                "last_name" => clean_string($_POST["last_name"] ?? ""),
                "department_id" => (int) ($_POST["department_id"] ?? 0),
                "academic_year_id" => (int) ($_POST["academic_year_id"] ?? 0),
                "academic_rank" => normalize_academic_rank(clean_string($_POST["academic_rank"] ?? "")),
                "employment_status" => normalize_employment_status(clean_string($_POST["employment_status"] ?? "")),
                "faculty_status" => normalize_faculty_status(clean_string($_POST["faculty_status"] ?? "Active")),
                "password" => (string) ($_POST["password"] ?? "")
            ];

            $errors = validate_faculty_input($data, $is_update);

            if (!$is_update) {
                $duplicate = $pdo->prepare("
                    SELECT COUNT(*)
                    FROM `user` u
                    LEFT JOIN faculty f ON f.user_id = u.user_id
                    WHERE u.university_id = :user_university_id
                    OR u.email = :user_email
                    OR f.faculty_number = :faculty_number
                    OR f.email = :faculty_email
                ");

                $duplicate->execute([
                    "user_university_id" => $data["faculty_number"],
                    "user_email" => $data["email"],
                    "faculty_number" => $data["faculty_number"],
                    "faculty_email" => $data["email"]
                ]);

                if ((int) $duplicate->fetchColumn() > 0) {
                    $errors[] = "Faculty ID or email already exists.";
                }
            } else {
                $current = $pdo->prepare("
                    SELECT user_id
                    FROM faculty
                    WHERE faculty_id = :faculty_id
                    LIMIT 1
                ");

                $current->execute(["faculty_id" => $faculty_id]);
                $current_user_id = (int) $current->fetchColumn();

                if ($current_user_id <= 0) {
                    $errors[] = "Faculty record was not found.";
                } else {
                    $duplicate = $pdo->prepare("
                        SELECT COUNT(*)
                        FROM `user` u
                        LEFT JOIN faculty f ON f.user_id = u.user_id
                        WHERE (
                            u.university_id = :user_university_id
                            OR u.email = :user_email
                            OR f.faculty_number = :faculty_number
                            OR f.email = :faculty_email
                        )
                        AND u.user_id <> :current_user_id
                    ");

                    $duplicate->execute([
                        "user_university_id" => $data["faculty_number"],
                        "user_email" => $data["email"],
                        "faculty_number" => $data["faculty_number"],
                        "faculty_email" => $data["email"],
                        "current_user_id" => $current_user_id
                    ]);

                    if ((int) $duplicate->fetchColumn() > 0) {
                        $errors[] = "Faculty ID or email already exists in another account.";
                    }
                }
            }

            if (count($errors) > 0) {
                redirect_with_message("error", implode(" ", $errors));
            }

            $pdo->beginTransaction();

            $full_name = build_full_name($data["first_name"], $data["middle_name"], $data["last_name"]);
            $account_status = user_account_status_from_faculty_status($data["faculty_status"]);

            if (!$is_update) {
                $role_id = get_faculty_role_id($pdo);
                $plain_password = $data["password"] !== "" ? $data["password"] : $data["faculty_number"];

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
                    "university_id" => $data["faculty_number"],
                    "email" => $data["email"],
                    "password_hash" => create_system_password_hash($plain_password),
                    "account_status" => $account_status
                ]);

                $user_id = (int) $pdo->lastInsertId();

                $insert_faculty = $pdo->prepare("
                    INSERT INTO faculty (
                        user_id,
                        faculty_number,
                        email,
                        first_name,
                        middle_name,
                        last_name,
                        full_name,
                        department_id,
                        academic_year_id,
                        academic_rank,
                        employment_status,
                        faculty_status
                    )
                    VALUES (
                        :user_id,
                        :faculty_number,
                        :email,
                        :first_name,
                        :middle_name,
                        :last_name,
                        :full_name,
                        :department_id,
                        :academic_year_id,
                        :academic_rank,
                        :employment_status,
                        :faculty_status
                    )
                ");

                $insert_faculty->execute([
                    "user_id" => $user_id,
                    "faculty_number" => $data["faculty_number"],
                    "email" => $data["email"],
                    "first_name" => $data["first_name"],
                    "middle_name" => $data["middle_name"],
                    "last_name" => $data["last_name"],
                    "full_name" => $full_name,
                    "department_id" => $data["department_id"],
                    "academic_year_id" => $data["academic_year_id"],
                    "academic_rank" => $data["academic_rank"],
                    "employment_status" => $data["employment_status"],
                    "faculty_status" => $data["faculty_status"]
                ]);

                $pdo->commit();

                redirect_with_message("success", "Faculty " . $data["faculty_number"] . " " . $full_name . " successfully added!");
            }

            $faculty_user_statement = $pdo->prepare("
                SELECT user_id
                FROM faculty
                WHERE faculty_id = :faculty_id
                LIMIT 1
            ");

            $faculty_user_statement->execute(["faculty_id" => $faculty_id]);
            $user_id = (int) $faculty_user_statement->fetchColumn();

            $update_user_sql = "
                UPDATE `user`
                SET university_id = :university_id,
                    email = :email,
                    account_status = :account_status
            ";

            $update_user_params = [
                "university_id" => $data["faculty_number"],
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

            $update_faculty = $pdo->prepare("
                UPDATE faculty
                SET faculty_number = :faculty_number,
                    email = :email,
                    first_name = :first_name,
                    middle_name = :middle_name,
                    last_name = :last_name,
                    full_name = :full_name,
                    department_id = :department_id,
                    academic_year_id = :academic_year_id,
                    academic_rank = :academic_rank,
                    employment_status = :employment_status,
                    faculty_status = :faculty_status
                WHERE faculty_id = :faculty_id
            ");

            $update_faculty->execute([
                "faculty_number" => $data["faculty_number"],
                "email" => $data["email"],
                "first_name" => $data["first_name"],
                "middle_name" => $data["middle_name"],
                "last_name" => $data["last_name"],
                "full_name" => $full_name,
                "department_id" => $data["department_id"],
                "academic_year_id" => $data["academic_year_id"],
                "academic_rank" => $data["academic_rank"],
                "employment_status" => $data["employment_status"],
                "faculty_status" => $data["faculty_status"],
                "faculty_id" => $faculty_id
            ]);

            $pdo->commit();

            redirect_with_message("success", "Faculty " . $data["faculty_number"] . " " . $full_name . " successfully updated!");
        }

        if ($form_action === "toggle_status") {
            $faculty_id = (int) ($_POST["faculty_id"] ?? 0);

            $statement = $pdo->prepare("
                SELECT faculty_status, user_id, faculty_number, full_name
                FROM faculty
                WHERE faculty_id = :faculty_id
                LIMIT 1
            ");

            $statement->execute(["faculty_id" => $faculty_id]);
            $faculty = $statement->fetch();

            if (!$faculty) {
                redirect_with_message("error", "Faculty record was not found.");
            }

            $new_status = $faculty["faculty_status"] === "Active" ? "Suspended" : "Active";
            $new_account_status = user_account_status_from_faculty_status($new_status);

            $pdo->beginTransaction();

            $pdo->prepare("
                UPDATE faculty
                SET faculty_status = :faculty_status
                WHERE faculty_id = :faculty_id
            ")->execute([
                "faculty_status" => $new_status,
                "faculty_id" => $faculty_id
            ]);

            $pdo->prepare("
                UPDATE `user`
                SET account_status = :account_status
                WHERE user_id = :user_id
            ")->execute([
                "account_status" => $new_account_status,
                "user_id" => $faculty["user_id"]
            ]);

            $pdo->commit();

            redirect_with_message("success", "Faculty " . $faculty["faculty_number"] . " status changed to " . $new_status . ".");
        }

        if ($form_action === "delete_faculty") {
            $faculty_id = (int) ($_POST["faculty_id"] ?? 0);

            $statement = $pdo->prepare("
                SELECT user_id, faculty_number, full_name
                FROM faculty
                WHERE faculty_id = :faculty_id
                LIMIT 1
            ");

            $statement->execute(["faculty_id" => $faculty_id]);
            $faculty = $statement->fetch();

            if (!$faculty) {
                redirect_with_message("error", "Faculty record was not found.");
            }

            $has_assignment = faculty_has_assignment_record($pdo, $faculty_id);

            $pdo->beginTransaction();

            if ($has_assignment) {
                $pdo->prepare("
                    UPDATE faculty
                    SET faculty_status = 'Inactive'
                    WHERE faculty_id = :faculty_id
                ")->execute(["faculty_id" => $faculty_id]);

                $pdo->prepare("
                    UPDATE `user`
                    SET account_status = 'Inactive'
                    WHERE user_id = :user_id
                ")->execute(["user_id" => $faculty["user_id"]]);

                $pdo->commit();

                redirect_with_message("warning", "You cannot delete an entity with record inside. This will mark as inactive.");
            }

            try {
                $pdo->prepare("
                    DELETE FROM faculty
                    WHERE faculty_id = :faculty_id
                ")->execute(["faculty_id" => $faculty_id]);

                $pdo->prepare("
                    DELETE FROM `user`
                    WHERE user_id = :user_id
                ")->execute(["user_id" => $faculty["user_id"]]);

                $pdo->commit();

                redirect_with_message("success", "Faculty " . $faculty["faculty_number"] . " successfully deleted.");
            } catch (Throwable $delete_error) {
                $pdo->prepare("
                    UPDATE faculty
                    SET faculty_status = 'Inactive'
                    WHERE faculty_id = :faculty_id
                ")->execute(["faculty_id" => $faculty_id]);

                $pdo->prepare("
                    UPDATE `user`
                    SET account_status = 'Inactive'
                    WHERE user_id = :user_id
                ")->execute(["user_id" => $faculty["user_id"]]);

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

$academic_years_all = $pdo->query("
    SELECT
        ay.academic_year_id,
        ay.academic_year_name,
        (
            SELECT t.term_name
            FROM term t
            WHERE t.academic_year_id = ay.academic_year_id
            ORDER BY t.term_id DESC
            LIMIT 1
        ) AS term_name
    FROM academic_year ay
    ORDER BY ay.academic_year_name DESC
")->fetchAll();

$db_ranks = $pdo->query("
    SELECT DISTINCT academic_rank
    FROM faculty
    WHERE academic_rank IS NOT NULL
    AND academic_rank <> ''
    ORDER BY academic_rank
")->fetchAll(PDO::FETCH_COLUMN);

foreach ($db_ranks as $db_rank) {
    if (!in_array($db_rank, $rank_options, true)) {
        $rank_options[] = $db_rank;
    }
}

$faculty_search = clean_string($_GET["q"] ?? "");
$filter_department_id = (int) ($_GET["department_id"] ?? 0);
$filter_academic_year_id = (int) ($_GET["academic_year_id"] ?? 0);
$filter_academic_rank = clean_string($_GET["academic_rank"] ?? "");
$filter_employment_status = clean_string($_GET["employment_status"] ?? "");
$filter_status = clean_string($_GET["status"] ?? "");

$params = [];
$where = "WHERE 1 = 1";

if ($faculty_search !== "") {
    $where .= "
        AND (
            f.faculty_number LIKE :faculty_number_search
            OR f.first_name LIKE :first_name_search
            OR f.middle_name LIKE :middle_name_search
            OR f.last_name LIKE :last_name_search
            OR f.full_name LIKE :full_name_search
            OR f.email LIKE :faculty_email_search
            OR u.email LIKE :user_email_search
            OR u.university_id LIKE :university_search
            OR d.department_name LIKE :department_search
            OR f.academic_rank LIKE :rank_search
        )
    ";

    $search_value = "%" . $faculty_search . "%";

    $params["faculty_number_search"] = $search_value;
    $params["first_name_search"] = $search_value;
    $params["middle_name_search"] = $search_value;
    $params["last_name_search"] = $search_value;
    $params["full_name_search"] = $search_value;
    $params["faculty_email_search"] = $search_value;
    $params["user_email_search"] = $search_value;
    $params["university_search"] = $search_value;
    $params["department_search"] = $search_value;
    $params["rank_search"] = $search_value;
}

if ($filter_department_id > 0) {
    $where .= " AND f.department_id = :department_id";
    $params["department_id"] = $filter_department_id;
}

if ($filter_academic_year_id > 0) {
    $where .= " AND f.academic_year_id = :academic_year_id";
    $params["academic_year_id"] = $filter_academic_year_id;
}

if ($filter_academic_rank !== "") {
    $where .= " AND f.academic_rank = :academic_rank";
    $params["academic_rank"] = $filter_academic_rank;
}

if ($filter_employment_status !== "") {
    $where .= " AND f.employment_status = :employment_status";
    $params["employment_status"] = $filter_employment_status;
}

if ($filter_status !== "") {
    $where .= " AND f.faculty_status = :faculty_status";
    $params["faculty_status"] = $filter_status;
}

$faculty_statement = $pdo->prepare("
    SELECT
        f.faculty_id,
        f.user_id,
        f.faculty_number,
        f.email AS faculty_email,
        f.first_name,
        f.middle_name,
        f.last_name,
        f.full_name,
        f.department_id,
        f.academic_year_id,
        f.academic_rank,
        f.employment_status,
        f.faculty_status,
        u.email AS user_email,
        u.account_status,
        d.department_code,
        d.department_name,
        ay.academic_year_name,
        (
            SELECT t.term_name
            FROM term t
            WHERE t.academic_year_id = f.academic_year_id
            ORDER BY t.term_id DESC
            LIMIT 1
        ) AS term_name,
        COUNT(DISTINCT ta.teaching_assignment_id) AS total_active_assignments
    FROM faculty f
    INNER JOIN `user` u ON u.user_id = f.user_id
    INNER JOIN department d ON d.department_id = f.department_id
    LEFT JOIN academic_year ay ON ay.academic_year_id = f.academic_year_id
    LEFT JOIN teaching_assignment ta
        ON ta.faculty_id = f.faculty_id
        AND ta.assignment_status = 'Active'
    $where
    GROUP BY
        f.faculty_id,
        f.user_id,
        f.faculty_number,
        f.email,
        f.first_name,
        f.middle_name,
        f.last_name,
        f.full_name,
        f.department_id,
        f.academic_year_id,
        f.academic_rank,
        f.employment_status,
        f.faculty_status,
        u.email,
        u.account_status,
        d.department_code,
        d.department_name,
        ay.academic_year_name
    ORDER BY f.faculty_number
");

$faculty_statement->execute($params);
$faculty_records = $faculty_statement->fetchAll();
$faculty_count = count($faculty_records);

$total_faculty = (int) $pdo->query("SELECT COUNT(*) FROM faculty")->fetchColumn();

$selected_faculty = null;

if (($modal_action === "view" || $modal_action === "edit") && $modal_id > 0) {
    $selected_statement = $pdo->prepare("
        SELECT
            f.faculty_id,
            f.user_id,
            f.faculty_number,
            f.email AS faculty_email,
            f.first_name,
            f.middle_name,
            f.last_name,
            f.full_name,
            f.department_id,
            f.academic_year_id,
            f.academic_rank,
            f.employment_status,
            f.faculty_status,
            u.email AS user_email,
            u.account_status,
            d.department_code,
            d.department_name,
            ay.academic_year_name,
            (
                SELECT t.term_name
                FROM term t
                WHERE t.academic_year_id = f.academic_year_id
                ORDER BY t.term_id DESC
                LIMIT 1
            ) AS term_name,
            (
                SELECT COUNT(DISTINCT ta.teaching_assignment_id)
                FROM teaching_assignment ta
                WHERE ta.faculty_id = f.faculty_id
                AND ta.assignment_status = 'Active'
            ) AS total_active_assignments
        FROM faculty f
        INNER JOIN `user` u ON u.user_id = f.user_id
        INNER JOIN department d ON d.department_id = f.department_id
        LEFT JOIN academic_year ay ON ay.academic_year_id = f.academic_year_id
        WHERE f.faculty_id = :faculty_id
        LIMIT 1
    ");

    $selected_statement->execute(["faculty_id" => $modal_id]);
    $selected_faculty = $selected_statement->fetch();
}

$has_filters = $faculty_search !== ""
    || $filter_department_id > 0
    || $filter_academic_year_id > 0
    || $filter_academic_rank !== ""
    || $filter_employment_status !== ""
    || $filter_status !== "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Management</title>
    <link rel="stylesheet" href="faculty-management.css?v=<?php echo time(); ?>">
</head>
<body class="faculty-management-page">
    <aside class="sidebar">
        <div class="brand">
            <img src="../img/eastgate_college_logo.png" alt="Eastgate College Logo">
            <h1>Student Evaluation for Teacher</h1>
            <p>System Administrator</p>
        </div>

        <nav class="nav-menu">
            <a class="nav-link" href="admin_dashboard.php"><?php echo icon_svg("dashboard"); ?> Dashboard</a>
            <a class="nav-link" href="student_management.php"><?php echo icon_svg("students"); ?> Student Management</a>
            <a class="nav-link active" href="faculty_management.php"><?php echo icon_svg("faculty"); ?> Faculty Management</a>
            <a class="nav-link" href="academic_structure.php"><?php echo icon_svg("book"); ?> Academic Structure</a>
            <a class="nav-link" href="assignment_management.php"><?php echo icon_svg("assignment"); ?> Assignment Management</a>
            <a class="nav-link" href="evaluation_setup.php"><?php echo icon_svg("settings"); ?> Evaluation Setup</a>
            <a class="nav-link" href="submission_monitoring.php"><?php echo icon_svg("clipboard"); ?> Submission Monitoring</a>
            <a class="nav-link" href="report_page.php"><?php echo icon_svg("reports"); ?> Reports</a>
            <a class="nav-link" href="announcement_page.php"><?php echo icon_svg("announcement"); ?> Announcements</a>
            <a class="nav-link" href="settings_page.php"><?php echo icon_svg("settings"); ?> Settings</a>
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
                    <h1>Faculty Management</h1>
                    <p>Manage faculty accounts and records</p>
                </div>

                <a class="primary-button" href="faculty_management.php?action=add">
                    <?php echo icon_svg("plus"); ?> Add Faculty
                </a>
            </div>

            <section class="filter-panel">
                <form class="faculty-filter-form auto-filter-form" method="GET" action="faculty_management.php">
                    <div class="search-row">
                        <div class="search-box">
                            <?php echo icon_svg("search"); ?>
                            <input
                                type="text"
                                name="q"
                                value="<?php echo e($faculty_search); ?>"
                                placeholder="Search by ID, name, email, or college..."
                            >
                        </div>

                        <a class="clear-filter" href="faculty_management.php">Clear</a>
                    </div>

                    <div class="filter-grid">
                        <select name="department_id">
                            <option value="0">All Colleges</option>
                            <?php foreach ($departments_all as $department): ?>
                                <option value="<?php echo (int) $department["department_id"]; ?>" <?php echo $filter_department_id === (int) $department["department_id"] ? "selected" : ""; ?>>
                                    <?php echo e($department["department_name"]); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <select name="academic_year_id">
                            <option value="0">All Academic Years</option>
                            <?php foreach ($academic_years_all as $year): ?>
                                <option value="<?php echo (int) $year["academic_year_id"]; ?>" <?php echo $filter_academic_year_id === (int) $year["academic_year_id"] ? "selected" : ""; ?>>
                                    <?php echo e($year["academic_year_name"]); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <select name="academic_rank">
                            <option value="">All Positions</option>
                            <?php foreach ($rank_options as $rank): ?>
                                <option value="<?php echo e($rank); ?>" <?php echo $filter_academic_rank === $rank ? "selected" : ""; ?>>
                                    <?php echo e($rank); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <select name="employment_status">
                            <option value="">All Employment Status</option>
                            <?php foreach ($employment_options as $employment): ?>
                                <option value="<?php echo e($employment); ?>" <?php echo $filter_employment_status === $employment ? "selected" : ""; ?>>
                                    <?php echo e($employment); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <select name="status">
                            <option value="">All Status</option>
                            <?php foreach (["Active", "Inactive", "Suspended"] as $status): ?>
                                <option value="<?php echo e($status); ?>" <?php echo $filter_status === $status ? "selected" : ""; ?>>
                                    <?php echo e($status); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>

                <div class="filter-summary">
                    <span>Showing <?php echo $faculty_count; ?> of <?php echo $total_faculty; ?> faculty members</span>
                    <span><?php echo icon_svg("filter"); ?> <?php echo $has_filters ? "Filters applied" : "No filters applied"; ?></span>
                </div>
            </section>

            <section class="table-card">
                <table class="faculty-table">
                    <thead>
                        <tr>
                            <th>Faculty ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>College</th>
                            <th>Position</th>
                            <th>Subjects Assigned</th>
                            <th>Status</th>
                            <th class="actions-head">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (count($faculty_records) === 0): ?>
                            <tr>
                                <td colspan="8" class="empty-row">No faculty members found matching your search criteria.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($faculty_records as $faculty): ?>
                            <tr>
                                <td><strong><?php echo e($faculty["faculty_number"]); ?></strong></td>
                                <td><?php echo e($faculty["full_name"]); ?></td>
                                <td><?php echo e($faculty["faculty_email"] ?: $faculty["user_email"]); ?></td>
                                <td><?php echo e($faculty["department_name"]); ?></td>
                                <td><?php echo e($faculty["academic_rank"]); ?></td>
                                <td><?php echo (int) $faculty["total_active_assignments"]; ?></td>
                                <td>
                                    <span class="status-badge <?php echo strtolower(e($faculty["faculty_status"])); ?>">
                                        <?php echo e(strtolower($faculty["faculty_status"])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-icons">
                                        <a class="view" title="View" href="faculty_management.php?action=view&id=<?php echo (int) $faculty["faculty_id"]; ?>">
                                            <?php echo icon_svg("eye"); ?>
                                        </a>

                                        <a class="edit" title="Edit" href="faculty_management.php?action=edit&id=<?php echo (int) $faculty["faculty_id"]; ?>">
                                            <?php echo icon_svg("edit"); ?>
                                        </a>

                                        <form method="POST" action="faculty_management.php">
                                            <input type="hidden" name="form_action" value="toggle_status">
                                            <input type="hidden" name="faculty_id" value="<?php echo (int) $faculty["faculty_id"]; ?>">

                                            <button class="<?php echo $faculty["faculty_status"] === "Active" ? "suspend" : "activate"; ?>" type="submit" title="<?php echo $faculty["faculty_status"] === "Active" ? "Suspend Account" : "Activate Account"; ?>">
                                                <?php echo $faculty["faculty_status"] === "Active" ? icon_svg("ban") : icon_svg("check"); ?>
                                            </button>
                                        </form>

                                        <form method="POST" action="faculty_management.php" onsubmit="return confirm('Delete this faculty record?');">
                                            <input type="hidden" name="form_action" value="delete_faculty">
                                            <input type="hidden" name="faculty_id" value="<?php echo (int) $faculty["faculty_id"]; ?>">

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
            <div class="modal-box faculty-modal">
                <h2>Add New Faculty</h2>

                <form method="POST" action="faculty_management.php" id="facultyForm">
                    <input type="hidden" name="form_action" value="add_faculty">

                    <div class="two-column">
                        <div>
                            <label>Faculty ID</label>
                            <input type="text" name="faculty_number" id="modal_faculty_number" placeholder="e.g., FAC-01234" required>
                        </div>

                        <div>
                            <label>Email</label>
                            <input type="email" name="email" id="modal_email" placeholder="e.g., faculty@eastgate.edu.ph" required>
                        </div>
                    </div>

                    <div class="three-column">
                        <div>
                            <label>First Name</label>
                            <input type="text" name="first_name" id="modal_first_name" placeholder="e.g., Julius" required>
                        </div>

                        <div>
                            <label>Middle Initial</label>
                            <input type="text" name="middle_name" id="modal_middle_name" placeholder="e.g., R.">
                        </div>

                        <div>
                            <label>Last Name</label>
                            <input type="text" name="last_name" id="modal_last_name" placeholder="e.g., Samonte" required>
                        </div>
                    </div>

                    <div>
                        <label>College</label>
                        <select name="department_id" required>
                            <option value="">Select College</option>
                            <?php foreach ($departments_all as $department): ?>
                                <option value="<?php echo (int) $department["department_id"]; ?>">
                                    <?php echo e($department["department_name"]); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="three-column">
                        <div>
                            <label>Position</label>
                            <select name="academic_rank" id="modal_academic_rank" required>
                                <option value="">Select Position</option>
                                <?php foreach ($rank_options as $rank): ?>
                                    <option value="<?php echo e($rank); ?>"><?php echo e($rank); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label>Employment Status</label>
                            <select name="employment_status" id="modal_employment_status" required>
                                <?php foreach ($employment_options as $employment): ?>
                                    <option value="<?php echo e($employment); ?>"><?php echo e($employment); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label>Academic Year</label>
                            <select name="academic_year_id" id="modal_academic_year_id" required>
                                <option value="">Select Academic Year</option>
                                <?php foreach ($academic_years_all as $year): ?>
                                    <option
                                        value="<?php echo (int) $year["academic_year_id"]; ?>"
                                        data-term-name="<?php echo e($year["term_name"] ?? ""); ?>"
                                    >
                                        <?php echo e($year["academic_year_name"]); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="two-column">
                        <div>
                            <label>Subjects Assigned</label>
                            <input type="text" value="0" readonly>
                        </div>

                        <div>
                            <label>Semester</label>
                            <input type="text" id="modal_semester_text" placeholder="Auto-filled from academic year" readonly>
                        </div>
                    </div>

                    <label>Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" placeholder="Leave blank to use Faculty ID as password">
                        <button type="button" class="password-toggle" aria-label="Show password">
                            <span class="eye-open-icon"><?php echo icon_svg("eye"); ?></span>
                            <span class="eye-closed-icon"><?php echo icon_svg("eye_closed"); ?></span>
                        </button>
                    </div>

                    <div class="modal-actions">
                        <a href="faculty_management.php" class="secondary-button">Cancel</a>
                        <button type="submit" class="dark-button">Add Faculty</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($modal_action === "view" && $selected_faculty): ?>
        <div class="modal-overlay">
            <div class="modal-box faculty-modal">
                <h2>Faculty Record Details</h2>

                <div class="two-column">
                    <div>
                        <label>Faculty ID</label>
                        <input type="text" value="<?php echo e($selected_faculty["faculty_number"]); ?>" readonly>
                    </div>

                    <div>
                        <label>Email</label>
                        <input type="text" value="<?php echo e($selected_faculty["faculty_email"] ?: $selected_faculty["user_email"]); ?>" readonly>
                    </div>
                </div>

                <div class="three-column">
                    <div>
                        <label>First Name</label>
                        <input type="text" value="<?php echo e($selected_faculty["first_name"]); ?>" readonly>
                    </div>

                    <div>
                        <label>Middle Initial</label>
                        <input type="text" value="<?php echo e($selected_faculty["middle_name"]); ?>" readonly>
                    </div>

                    <div>
                        <label>Last Name</label>
                        <input type="text" value="<?php echo e($selected_faculty["last_name"]); ?>" readonly>
                    </div>
                </div>

                <label>College</label>
                <input type="text" value="<?php echo e($selected_faculty["department_name"]); ?>" readonly>

                <div class="three-column">
                    <div>
                        <label>Position</label>
                        <input type="text" value="<?php echo e($selected_faculty["academic_rank"]); ?>" readonly>
                    </div>

                    <div>
                        <label>Employment Status</label>
                        <input type="text" value="<?php echo e($selected_faculty["employment_status"]); ?>" readonly>
                    </div>

                    <div>
                        <label>Subjects Assigned</label>
                        <input type="text" value="<?php echo (int) $selected_faculty["total_active_assignments"]; ?>" readonly>
                    </div>
                </div>

                <div class="two-column">
                    <div>
                        <label>Semester</label>
                        <input type="text" value="<?php echo e($selected_faculty["term_name"] ?? "Not set"); ?>" readonly>
                    </div>

                    <div>
                        <label>Academic Year</label>
                        <input type="text" value="<?php echo e($selected_faculty["academic_year_name"] ?? "Not set"); ?>" readonly>
                    </div>
                </div>

                <label>Status</label>
                <div class="readonly-status">
                    <span class="status-badge <?php echo strtolower(e($selected_faculty["faculty_status"])); ?>">
                        <?php echo e(strtolower($selected_faculty["faculty_status"])); ?>
                    </span>
                </div>

                <div class="modal-actions">
                    <a href="faculty_management.php" class="secondary-button">Cancel</a>
                    <a href="faculty_management.php?action=edit&id=<?php echo (int) $selected_faculty["faculty_id"]; ?>" class="dark-button">Edit Information</a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($modal_action === "edit" && $selected_faculty): ?>
        <div class="modal-overlay">
            <div class="modal-box faculty-modal">
                <h2>Edit Faculty</h2>

                <form method="POST" action="faculty_management.php" id="facultyForm">
                    <input type="hidden" name="form_action" value="update_faculty">
                    <input type="hidden" name="faculty_id" value="<?php echo (int) $selected_faculty["faculty_id"]; ?>">

                    <div class="two-column">
                        <div>
                            <label>Faculty ID</label>
                            <input type="text" name="faculty_number" id="modal_faculty_number" value="<?php echo e($selected_faculty["faculty_number"]); ?>" required>
                        </div>

                        <div>
                            <label>Email</label>
                            <input type="email" name="email" id="modal_email" value="<?php echo e($selected_faculty["faculty_email"] ?: $selected_faculty["user_email"]); ?>" required>
                        </div>
                    </div>

                    <div class="three-column">
                        <div>
                            <label>First Name</label>
                            <input type="text" name="first_name" id="modal_first_name" value="<?php echo e($selected_faculty["first_name"]); ?>" required>
                        </div>

                        <div>
                            <label>Middle Initial</label>
                            <input type="text" name="middle_name" id="modal_middle_name" value="<?php echo e($selected_faculty["middle_name"]); ?>">
                        </div>

                        <div>
                            <label>Last Name</label>
                            <input type="text" name="last_name" id="modal_last_name" value="<?php echo e($selected_faculty["last_name"]); ?>" required>
                        </div>
                    </div>

                    <label>College</label>
                    <select name="department_id" required>
                        <?php foreach ($departments_all as $department): ?>
                            <option
                                value="<?php echo (int) $department["department_id"]; ?>"
                                <?php echo (int) $selected_faculty["department_id"] === (int) $department["department_id"] ? "selected" : ""; ?>
                            >
                                <?php echo e($department["department_name"]); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div class="three-column">
                        <div>
                            <label>Position</label>
                            <select name="academic_rank" id="modal_academic_rank" required>
                                <?php foreach ($rank_options as $rank): ?>
                                    <option value="<?php echo e($rank); ?>" <?php echo $selected_faculty["academic_rank"] === $rank ? "selected" : ""; ?>>
                                        <?php echo e($rank); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label>Employment Status</label>
                            <select name="employment_status" id="modal_employment_status" required>
                                <?php foreach ($employment_options as $employment): ?>
                                    <option value="<?php echo e($employment); ?>" <?php echo $selected_faculty["employment_status"] === $employment ? "selected" : ""; ?>>
                                        <?php echo e($employment); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label>Academic Year</label>
                            <select name="academic_year_id" id="modal_academic_year_id" required>
                                <?php foreach ($academic_years_all as $year): ?>
                                    <option
                                        value="<?php echo (int) $year["academic_year_id"]; ?>"
                                        data-term-name="<?php echo e($year["term_name"] ?? ""); ?>"
                                        <?php echo (int) $selected_faculty["academic_year_id"] === (int) $year["academic_year_id"] ? "selected" : ""; ?>
                                    >
                                        <?php echo e($year["academic_year_name"]); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="two-column">
                        <div>
                            <label>Subjects Assigned</label>
                            <input type="text" value="<?php echo (int) $selected_faculty["total_active_assignments"]; ?>" readonly>
                        </div>

                        <div>
                            <label>Semester</label>
                            <input type="text" id="modal_semester_text" value="<?php echo e($selected_faculty["term_name"] ?? ""); ?>" readonly>
                        </div>
                    </div>

                    <label>Status</label>
                    <select name="faculty_status" required>
                        <?php foreach (["Active", "Inactive", "Suspended"] as $status): ?>
                            <option value="<?php echo e($status); ?>" <?php echo $selected_faculty["faculty_status"] === $status ? "selected" : ""; ?>>
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
                        <a href="faculty_management.php" class="secondary-button">Cancel</a>
                        <button type="submit" class="dark-button">Update Faculty</button>
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

        function formatFacultyNumberInput(input) {
            let raw = input.value.toUpperCase().replace(/[^A-Z0-9]/g, "");

            if (raw.startsWith("FAC")) {
                raw = raw.slice(3);
            }

            raw = raw.replace(/\D/g, "").slice(0, 5);

            input.value = raw.length > 0 ? "FAC-" + raw : "";
        }

        function validateFacultyNumber() {
            const facultyNumberInput = document.getElementById("modal_faculty_number");

            if (!facultyNumberInput) {
                return true;
            }

            const facultyNumber = facultyNumberInput.value.trim();
            const pattern = /^FAC-[0-9]{5}$/;

            facultyNumberInput.setCustomValidity("");

            if (facultyNumber === "") {
                return false;
            }

            if (!pattern.test(facultyNumber)) {
                facultyNumberInput.setCustomValidity("Faculty ID must strictly follow this format: FAC-01234.");
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

        function buildFacultyEmail(firstName, lastName) {
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

                const generatedEmail = buildFacultyEmail(firstNameInput.value, lastNameInput.value);

                if (generatedEmail !== "") {
                    emailInput.value = generatedEmail;
                }
            }

            firstNameInput.addEventListener("input", updateEmail);
            lastNameInput.addEventListener("input", updateEmail);
            firstNameInput.addEventListener("blur", updateEmail);
            lastNameInput.addEventListener("blur", updateEmail);
        }

        function setupFacultyModal() {
            const facultyForm = document.getElementById("facultyForm");
            const facultyNumberInput = document.getElementById("modal_faculty_number");
            const academicYearSelect = document.getElementById("modal_academic_year_id");
            const semesterInput = document.getElementById("modal_semester_text");
            const rankSelect = document.getElementById("modal_academic_rank");
            const employmentSelect = document.getElementById("modal_employment_status");

            if (facultyNumberInput) {
                facultyNumberInput.addEventListener("input", function () {
                    formatFacultyNumberInput(facultyNumberInput);
                    validateFacultyNumber();
                });

                facultyNumberInput.addEventListener("blur", validateFacultyNumber);
            }

            if (academicYearSelect && semesterInput) {
                function updateSemester() {
                    const option = academicYearSelect.options[academicYearSelect.selectedIndex];

                    if (!option || academicYearSelect.value === "") {
                        semesterInput.value = "";
                        return;
                    }

                    semesterInput.value = option.dataset.termName || "Not set";
                }

                academicYearSelect.addEventListener("change", updateSemester);
                updateSemester();
            }

            if (rankSelect && employmentSelect) {
                function forceDeanFullTime() {
                    if (rankSelect.value === "Dean") {
                        employmentSelect.value = "Full Time";
                    }
                }

                rankSelect.addEventListener("change", forceDeanFullTime);
                forceDeanFullTime();
            }

            if (facultyForm) {
                facultyForm.addEventListener("submit", function (event) {
                    if (!validateFacultyNumber()) {
                        event.preventDefault();

                        if (facultyNumberInput) {
                            facultyNumberInput.reportValidity();
                        }
                    }
                });
            }
        }

        setupAutomaticEmail();
        setupFacultyModal();

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