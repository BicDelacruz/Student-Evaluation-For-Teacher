<?php
session_start();

require_once __DIR__ . "/../database_connector.php";

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
    header("Location: announcement_page.php?toast_type=" . urlencode($type) . "&toast_message=" . urlencode($message));
    exit;
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
        "edit" => '<svg viewBox="0 0 24 24"><path d="M12 20h9"></path><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"></path></svg>',
        "trash" => '<svg viewBox="0 0 24 24"><path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="M19 6l-1 14H6L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path></svg>',
        "send" => '<svg viewBox="0 0 24 24"><path d="M22 2L11 13"></path><path d="M22 2l-7 20-4-9-9-4 20-7z"></path></svg>',
        "file" => '<svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path></svg>',
        "x" => '<svg viewBox="0 0 24 24"><path d="M18 6L6 18"></path><path d="M6 6l12 12"></path></svg>'
    ];

    return $icons[$name] ?? "";
}

function format_date_label($date_value)
{
    if (!$date_value) {
        return "N/A";
    }

    $timestamp = strtotime((string) $date_value);

    if ($timestamp === false) {
        return "N/A";
    }

    return date("n/j/Y", $timestamp);
}

function normalize_target_audience($target_audience)
{
    $target_audience = strtolower(clean_string($target_audience));
    $allowed = ["all", "students", "faculty"];

    if (in_array($target_audience, $allowed, true)) {
        return $target_audience;
    }

    return "all";
}

function target_audience_label($target_audience)
{
    $target_audience = strtolower(clean_string($target_audience));

    if ($target_audience === "students") {
        return "students";
    }

    if ($target_audience === "faculty") {
        return "faculty";
    }

    if ($target_audience === "admin") {
        return "admin";
    }

    if ($target_audience === "all" || $target_audience === "all users") {
        return "all";
    }

    return $target_audience !== "" ? $target_audience : "all";
}

function get_role_ids($pdo)
{
    $roles = [
        "students" => null,
        "faculty" => null,
        "admin" => null
    ];

    $statement = $pdo->query("SELECT role_id, role_name FROM role WHERE status = 'Active'");

    foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $role) {
        $name = strtolower((string) $role["role_name"]);

        if ($name === "student") {
            $roles["students"] = (int) $role["role_id"];
        }

        if ($name === "faculty") {
            $roles["faculty"] = (int) $role["role_id"];
        }

        if ($name === "admin") {
            $roles["admin"] = (int) $role["role_id"];
        }
    }

    return $roles;
}

function get_current_admin($pdo)
{
    if (!empty($_SESSION["admin_id"])) {
        $statement = $pdo->prepare("SELECT a.admin_id, a.user_id, a.full_name FROM admin a INNER JOIN `user` u ON u.user_id = a.user_id WHERE a.admin_id = :admin_id AND a.admin_status = 'Active' AND u.account_status = 'Active' LIMIT 1");
        $statement->execute(["admin_id" => (int) $_SESSION["admin_id"]]);
        $admin = $statement->fetch(PDO::FETCH_ASSOC);

        if ($admin) {
            return $admin;
        }
    }

    if (!empty($_SESSION["user_id"])) {
        $statement = $pdo->prepare("SELECT a.admin_id, a.user_id, a.full_name FROM admin a INNER JOIN `user` u ON u.user_id = a.user_id INNER JOIN role r ON r.role_id = u.role_id WHERE a.user_id = :user_id AND r.role_name = 'Admin' AND a.admin_status = 'Active' AND u.account_status = 'Active' LIMIT 1");
        $statement->execute(["user_id" => (int) $_SESSION["user_id"]]);
        $admin = $statement->fetch(PDO::FETCH_ASSOC);

        if ($admin) {
            return $admin;
        }
    }

    $statement = $pdo->query("SELECT a.admin_id, a.user_id, a.full_name FROM admin a INNER JOIN `user` u ON u.user_id = a.user_id WHERE a.admin_status = 'Active' AND u.account_status = 'Active' ORDER BY a.admin_id LIMIT 1");
    $admin = $statement->fetch(PDO::FETCH_ASSOC);

    if ($admin) {
        return $admin;
    }

    die("No active admin account was found. Please create or activate an admin account first.");
}

function set_app_context($pdo, $user_id)
{
    $statement = $pdo->prepare("SET @app_user_id = :user_id");
    $statement->execute(["user_id" => (int) $user_id]);

    $ip_address = $_SERVER["REMOTE_ADDR"] ?? null;
    $statement = $pdo->prepare("SET @app_ip_address = :ip_address");
    $statement->execute(["ip_address" => $ip_address]);
}

function validate_announcement_input($title, $message, $target_audience)
{
    $errors = [];

    if ($title === "") {
        $errors[] = "Announcement title is required.";
    }

    if (strlen($title) > 150) {
        $errors[] = "Announcement title must not exceed 150 characters.";
    }

    if ($message === "") {
        $errors[] = "Announcement message is required.";
    }

    if (strlen($message) > 10000) {
        $errors[] = "Announcement message is too long.";
    }

    if (!in_array(normalize_target_audience($target_audience), ["all", "students", "faculty"], true)) {
        $errors[] = "Please select a valid target audience.";
    }

    return $errors;
}

function replace_announcement_target($pdo, $announcement_id, $target_audience, $role_ids)
{
    $delete_statement = $pdo->prepare("DELETE FROM announcement_target WHERE announcement_id = :announcement_id");
    $delete_statement->execute(["announcement_id" => (int) $announcement_id]);

    $target_audience = normalize_target_audience($target_audience);
    $target_role_id = null;

    if ($target_audience === "students") {
        $target_role_id = $role_ids["students"] ?? null;
    }

    if ($target_audience === "faculty") {
        $target_role_id = $role_ids["faculty"] ?? null;
    }

    $insert_statement = $pdo->prepare("INSERT INTO announcement_target (announcement_id, target_role_id, target_department_id, target_course_id, target_section_id, target_faculty_id, target_student_id) VALUES (:announcement_id, :target_role_id, NULL, NULL, NULL, NULL, NULL)");
    $insert_statement->bindValue(":announcement_id", (int) $announcement_id, PDO::PARAM_INT);

    if ($target_role_id === null) {
        $insert_statement->bindValue(":target_role_id", null, PDO::PARAM_NULL);
    } else {
        $insert_statement->bindValue(":target_role_id", (int) $target_role_id, PDO::PARAM_INT);
    }

    $insert_statement->execute();
}

function fetch_targeted_user_ids($pdo, $announcement_id)
{
    $targets_statement = $pdo->prepare("SELECT * FROM announcement_target WHERE announcement_id = :announcement_id");
    $targets_statement->execute(["announcement_id" => (int) $announcement_id]);
    $targets = $targets_statement->fetchAll(PDO::FETCH_ASSOC);

    $user_ids = [];

    $add_user_ids = function ($rows) use (&$user_ids) {
        foreach ($rows as $row) {
            $user_id = (int) ($row["user_id"] ?? 0);

            if ($user_id > 0) {
                $user_ids[$user_id] = $user_id;
            }
        }
    };

    if (empty($targets)) {
        $statement = $pdo->query("SELECT user_id FROM `user` WHERE account_status = 'Active'");
        $add_user_ids($statement->fetchAll(PDO::FETCH_ASSOC));
        return array_values($user_ids);
    }

    foreach ($targets as $target) {
        $is_all_users = empty($target["target_role_id"])
            && empty($target["target_department_id"])
            && empty($target["target_course_id"])
            && empty($target["target_section_id"])
            && empty($target["target_faculty_id"])
            && empty($target["target_student_id"]);

        if ($is_all_users) {
            $statement = $pdo->query("SELECT user_id FROM `user` WHERE account_status = 'Active'");
            $add_user_ids($statement->fetchAll(PDO::FETCH_ASSOC));
            return array_values($user_ids);
        }

        if (!empty($target["target_role_id"])) {
            $statement = $pdo->prepare("SELECT user_id FROM `user` WHERE role_id = :role_id AND account_status = 'Active'");
            $statement->execute(["role_id" => (int) $target["target_role_id"]]);
            $add_user_ids($statement->fetchAll(PDO::FETCH_ASSOC));
        }

        if (!empty($target["target_department_id"])) {
            $student_statement = $pdo->prepare("SELECT DISTINCT u.user_id FROM student s INNER JOIN `user` u ON u.user_id = s.user_id INNER JOIN course c ON c.course_id = s.course_id WHERE c.department_id = :department_id AND u.account_status = 'Active' AND s.student_status = 'Active'");
            $student_statement->execute(["department_id" => (int) $target["target_department_id"]]);
            $add_user_ids($student_statement->fetchAll(PDO::FETCH_ASSOC));

            $faculty_statement = $pdo->prepare("SELECT DISTINCT u.user_id FROM faculty f INNER JOIN `user` u ON u.user_id = f.user_id WHERE f.department_id = :department_id AND u.account_status = 'Active' AND f.faculty_status = 'Active'");
            $faculty_statement->execute(["department_id" => (int) $target["target_department_id"]]);
            $add_user_ids($faculty_statement->fetchAll(PDO::FETCH_ASSOC));
        }

        if (!empty($target["target_course_id"])) {
            $statement = $pdo->prepare("SELECT DISTINCT u.user_id FROM student s INNER JOIN `user` u ON u.user_id = s.user_id WHERE s.course_id = :course_id AND u.account_status = 'Active' AND s.student_status = 'Active'");
            $statement->execute(["course_id" => (int) $target["target_course_id"]]);
            $add_user_ids($statement->fetchAll(PDO::FETCH_ASSOC));
        }

        if (!empty($target["target_section_id"])) {
            $statement = $pdo->prepare("SELECT DISTINCT u.user_id FROM student s INNER JOIN `user` u ON u.user_id = s.user_id WHERE s.current_section_id = :section_id AND u.account_status = 'Active' AND s.student_status = 'Active'");
            $statement->execute(["section_id" => (int) $target["target_section_id"]]);
            $add_user_ids($statement->fetchAll(PDO::FETCH_ASSOC));
        }

        if (!empty($target["target_faculty_id"])) {
            $statement = $pdo->prepare("SELECT u.user_id FROM faculty f INNER JOIN `user` u ON u.user_id = f.user_id WHERE f.faculty_id = :faculty_id AND u.account_status = 'Active' AND f.faculty_status = 'Active'");
            $statement->execute(["faculty_id" => (int) $target["target_faculty_id"]]);
            $add_user_ids($statement->fetchAll(PDO::FETCH_ASSOC));
        }

        if (!empty($target["target_student_id"])) {
            $statement = $pdo->prepare("SELECT u.user_id FROM student s INNER JOIN `user` u ON u.user_id = s.user_id WHERE s.student_id = :student_id AND u.account_status = 'Active' AND s.student_status = 'Active'");
            $statement->execute(["student_id" => (int) $target["target_student_id"]]);
            $add_user_ids($statement->fetchAll(PDO::FETCH_ASSOC));
        }
    }

    return array_values($user_ids);
}

function create_notifications_for_announcement($pdo, $announcement_id, $title, $message)
{
    $user_ids = fetch_targeted_user_ids($pdo, $announcement_id);

    if (empty($user_ids)) {
        return 0;
    }

    $insert_statement = $pdo->prepare("INSERT INTO notification (user_id, announcement_id, notification_title, notification_message, notification_type, is_read) VALUES (:user_id, :announcement_id, :notification_title, :notification_message, 'Announcement', 0)");
    $created_count = 0;

    foreach ($user_ids as $user_id) {
        $insert_statement->execute([
            "user_id" => (int) $user_id,
            "announcement_id" => (int) $announcement_id,
            "notification_title" => $title,
            "notification_message" => $message
        ]);

        $created_count++;
    }

    return $created_count;
}

function get_announcement_status_class($status)
{
    $status = strtolower((string) $status);

    if ($status === "published") {
        return "published";
    }

    if ($status === "archived") {
        return "archived";
    }

    return "draft";
}

function get_target_labels($raw_labels)
{
    $raw_labels = clean_string($raw_labels);

    if ($raw_labels === "") {
        return ["all"];
    }

    $labels = array_values(array_unique(array_filter(array_map("trim", explode("|", $raw_labels)))));

    if (empty($labels)) {
        return ["all"];
    }

    return $labels;
}

function get_target_value_from_labels($raw_labels)
{
    $labels = get_target_labels($raw_labels);

    if (in_array("students", $labels, true)) {
        return "students";
    }

    if (in_array("faculty", $labels, true)) {
        return "faculty";
    }

    return "all";
}

$current_admin = get_current_admin($pdo);
set_app_context($pdo, (int) $current_admin["user_id"]);
$role_ids = get_role_ids($pdo);

$modal_action = clean_string($_GET["action"] ?? "");
$modal_id = (int) ($_GET["id"] ?? 0);
$toast_type = clean_string($_GET["toast_type"] ?? "");
$toast_message = clean_string($_GET["toast_message"] ?? "");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $form_action = clean_string($_POST["form_action"] ?? "");

    if ($form_action === "create_announcement") {
        $title = clean_string($_POST["title"] ?? "");
        $message = clean_string($_POST["message"] ?? "");
        $target_audience = normalize_target_audience($_POST["target_audience"] ?? "all");
        $save_mode = clean_string($_POST["save_mode"] ?? "publish");
        $announcement_status = $save_mode === "draft" ? "Draft" : "Published";
        $published_at = $announcement_status === "Published" ? date("Y-m-d H:i:s") : null;

        $errors = validate_announcement_input($title, $message, $target_audience);

        if (!empty($errors)) {
            redirect_with_message("error", implode(" ", $errors));
        }

        try {
            $pdo->beginTransaction();

            $insert_statement = $pdo->prepare("INSERT INTO announcement (created_by_admin_id, title, message, announcement_status, published_at) VALUES (:created_by_admin_id, :title, :message, :announcement_status, :published_at)");
            $insert_statement->execute([
                "created_by_admin_id" => (int) $current_admin["admin_id"],
                "title" => $title,
                "message" => $message,
                "announcement_status" => $announcement_status,
                "published_at" => $published_at
            ]);

            $announcement_id = (int) $pdo->lastInsertId();
            replace_announcement_target($pdo, $announcement_id, $target_audience, $role_ids);

            if ($announcement_status === "Published") {
                create_notifications_for_announcement($pdo, $announcement_id, $title, $message);
            }

            $pdo->commit();

            if ($announcement_status === "Published") {
                redirect_with_message("success", "Announcement created and published successfully.");
            }

            redirect_with_message("success", "Announcement saved as draft successfully.");
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            redirect_with_message("error", "Unable to create announcement. " . $error->getMessage());
        }
    }

    if ($form_action === "update_announcement") {
        $announcement_id = (int) ($_POST["announcement_id"] ?? 0);
        $title = clean_string($_POST["title"] ?? "");
        $message = clean_string($_POST["message"] ?? "");
        $target_audience = normalize_target_audience($_POST["target_audience"] ?? "all");
        $save_mode = clean_string($_POST["save_mode"] ?? "update");

        $errors = validate_announcement_input($title, $message, $target_audience);

        if ($announcement_id <= 0) {
            $errors[] = "Invalid announcement record.";
        }

        if (!empty($errors)) {
            redirect_with_message("error", implode(" ", $errors));
        }

        try {
            $pdo->beginTransaction();

            $current_statement = $pdo->prepare("SELECT announcement_status, published_at FROM announcement WHERE announcement_id = :announcement_id LIMIT 1");
            $current_statement->execute(["announcement_id" => $announcement_id]);
            $current_announcement = $current_statement->fetch(PDO::FETCH_ASSOC);

            if (!$current_announcement) {
                throw new RuntimeException("Announcement record was not found.");
            }

            $new_status = $current_announcement["announcement_status"];
            $new_published_at = $current_announcement["published_at"];
            $should_create_notifications = false;

            if ($save_mode === "publish" && $current_announcement["announcement_status"] !== "Published") {
                $new_status = "Published";
                $new_published_at = date("Y-m-d H:i:s");
                $should_create_notifications = true;
            }

            $update_statement = $pdo->prepare("UPDATE announcement SET title = :title, message = :message, announcement_status = :announcement_status, published_at = :published_at WHERE announcement_id = :announcement_id");
            $update_statement->execute([
                "title" => $title,
                "message" => $message,
                "announcement_status" => $new_status,
                "published_at" => $new_published_at,
                "announcement_id" => $announcement_id
            ]);

            replace_announcement_target($pdo, $announcement_id, $target_audience, $role_ids);

            if ($should_create_notifications) {
                create_notifications_for_announcement($pdo, $announcement_id, $title, $message);
            }

            $pdo->commit();

            if ($should_create_notifications) {
                redirect_with_message("success", "Announcement updated and published successfully.");
            }

            redirect_with_message("success", "Announcement updated successfully.");
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            redirect_with_message("error", "Unable to update announcement. " . $error->getMessage());
        }
    }

    if ($form_action === "publish_announcement") {
        $announcement_id = (int) ($_POST["announcement_id"] ?? 0);

        if ($announcement_id <= 0) {
            redirect_with_message("error", "Invalid announcement record.");
        }

        try {
            $pdo->beginTransaction();

            $statement = $pdo->prepare("SELECT title, message, announcement_status FROM announcement WHERE announcement_id = :announcement_id LIMIT 1");
            $statement->execute(["announcement_id" => $announcement_id]);
            $announcement = $statement->fetch(PDO::FETCH_ASSOC);

            if (!$announcement) {
                throw new RuntimeException("Announcement record was not found.");
            }

            if ($announcement["announcement_status"] === "Published") {
                throw new RuntimeException("Announcement is already published.");
            }

            $update_statement = $pdo->prepare("UPDATE announcement SET announcement_status = 'Published', published_at = NOW() WHERE announcement_id = :announcement_id");
            $update_statement->execute(["announcement_id" => $announcement_id]);

            create_notifications_for_announcement($pdo, $announcement_id, $announcement["title"], $announcement["message"]);

            $pdo->commit();
            redirect_with_message("success", "Announcement published successfully.");
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            redirect_with_message("error", "Unable to publish announcement. " . $error->getMessage());
        }
    }

    if ($form_action === "delete_announcement") {
        $announcement_id = (int) ($_POST["announcement_id"] ?? 0);

        if ($announcement_id <= 0) {
            redirect_with_message("error", "Invalid announcement record.");
        }

        try {
            $delete_statement = $pdo->prepare("DELETE FROM announcement WHERE announcement_id = :announcement_id");
            $delete_statement->execute(["announcement_id" => $announcement_id]);

            redirect_with_message("success", "Announcement deleted successfully.");
        } catch (Throwable $error) {
            redirect_with_message("error", "Unable to delete announcement. " . $error->getMessage());
        }
    }
}

$announcements_statement = $pdo->query(" 
    SELECT
        a.announcement_id,
        a.created_by_admin_id,
        a.title,
        a.message,
        a.announcement_status,
        a.published_at,
        a.created_at,
        a.updated_at,
        ad.full_name AS created_by_name,
        GROUP_CONCAT(
            DISTINCT CASE
                WHEN at.announcement_target_id IS NULL THEN NULL
                WHEN at.target_role_id IS NULL
                    AND at.target_department_id IS NULL
                    AND at.target_course_id IS NULL
                    AND at.target_section_id IS NULL
                    AND at.target_faculty_id IS NULL
                    AND at.target_student_id IS NULL THEN 'all'
                WHEN LOWER(r.role_name) = 'student' THEN 'students'
                WHEN LOWER(r.role_name) = 'faculty' THEN 'faculty'
                WHEN LOWER(r.role_name) = 'admin' THEN 'admin'
                WHEN d.department_name IS NOT NULL THEN CONCAT('college: ', d.department_name)
                WHEN c.course_code IS NOT NULL THEN CONCAT('program: ', c.course_code)
                WHEN sec.section_name IS NOT NULL THEN CONCAT('section: ', sec.section_name)
                WHEN f.full_name IS NOT NULL THEN CONCAT('faculty: ', f.full_name)
                WHEN s.full_name IS NOT NULL THEN CONCAT('student: ', s.full_name)
                ELSE 'custom'
            END
            ORDER BY at.announcement_target_id
            SEPARATOR '|'
        ) AS target_labels
    FROM announcement a
    INNER JOIN admin ad ON ad.admin_id = a.created_by_admin_id
    LEFT JOIN announcement_target at ON at.announcement_id = a.announcement_id
    LEFT JOIN role r ON r.role_id = at.target_role_id
    LEFT JOIN department d ON d.department_id = at.target_department_id
    LEFT JOIN course c ON c.course_id = at.target_course_id
    LEFT JOIN `section` sec ON sec.section_id = at.target_section_id
    LEFT JOIN faculty f ON f.faculty_id = at.target_faculty_id
    LEFT JOIN student s ON s.student_id = at.target_student_id
    GROUP BY
        a.announcement_id,
        a.created_by_admin_id,
        a.title,
        a.message,
        a.announcement_status,
        a.published_at,
        a.created_at,
        a.updated_at,
        ad.full_name
    ORDER BY a.created_at DESC, a.announcement_id DESC
");
$announcements = $announcements_statement->fetchAll(PDO::FETCH_ASSOC);

$selected_announcement = null;

if ($modal_action === "edit" && $modal_id > 0) {
    $selected_statement = $pdo->prepare(" 
        SELECT
            a.announcement_id,
            a.title,
            a.message,
            a.announcement_status,
            a.created_at,
            GROUP_CONCAT(
                DISTINCT CASE
                    WHEN at.announcement_target_id IS NULL THEN NULL
                    WHEN at.target_role_id IS NULL
                        AND at.target_department_id IS NULL
                        AND at.target_course_id IS NULL
                        AND at.target_section_id IS NULL
                        AND at.target_faculty_id IS NULL
                        AND at.target_student_id IS NULL THEN 'all'
                    WHEN LOWER(r.role_name) = 'student' THEN 'students'
                    WHEN LOWER(r.role_name) = 'faculty' THEN 'faculty'
                    ELSE 'all'
                END
                ORDER BY at.announcement_target_id
                SEPARATOR '|'
            ) AS target_labels
        FROM announcement a
        LEFT JOIN announcement_target at ON at.announcement_id = a.announcement_id
        LEFT JOIN role r ON r.role_id = at.target_role_id
        WHERE a.announcement_id = :announcement_id
        GROUP BY a.announcement_id, a.title, a.message, a.announcement_status, a.created_at
        LIMIT 1
    ");
    $selected_statement->execute(["announcement_id" => $modal_id]);
    $selected_announcement = $selected_statement->fetch(PDO::FETCH_ASSOC);

    if (!$selected_announcement) {
        $modal_action = "";
    }
}

$show_create_modal = $modal_action === "create";
$show_edit_modal = $modal_action === "edit" && $selected_announcement;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements</title>
    <link rel="stylesheet" href="announcement-page.css?v=<?php echo time(); ?>">
</head>
<body class="announcement-page">
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
            <a class="nav-link" href="report_page.php"><?php echo icon_svg("reports"); ?> Reports</a>
            <a class="nav-link active" href="announcement_page.php"><?php echo icon_svg("announcement"); ?> Announcements</a>
            <a class="nav-link" href="settings_page.php"><?php echo icon_svg("settings"); ?> Settings</a>
            <a class="nav-link" href="#"><?php echo icon_svg("moon"); ?> Dark Mode</a>
        </nav>

        <div class="sidebar-bottom">
            <a class="logout-link" href="../login/login_page.php"><?php echo icon_svg("logout"); ?> Logout</a>
        </div>
    </aside>

    <main class="main-content">
        <div class="content-wrap">
            <section class="announcement-shell">
                <div class="page-title-row">
                    <div>
                        <h1>Announcements</h1>
                        <p>Create and manage system announcements</p>
                    </div>

                    <a class="primary-button" href="announcement_page.php?action=create">
                        <?php echo icon_svg("plus"); ?> Create Announcement
                    </a>
                </div>

                <div class="announcement-list">
                    <?php if (empty($announcements)): ?>
                        <div class="empty-announcement-card">
                            <div class="empty-icon"><?php echo icon_svg("announcement"); ?></div>
                            <h2>No announcements yet</h2>
                            <p>Create your first announcement to send notices, reminders, or system messages to students and faculty.</p>
                            <a class="primary-button" href="announcement_page.php?action=create"><?php echo icon_svg("plus"); ?> Create Announcement</a>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($announcements as $announcement): ?>
                        <?php
                            $status_label = strtolower((string) $announcement["announcement_status"]);
                            $status_class = get_announcement_status_class($announcement["announcement_status"]);
                            $target_labels = get_target_labels($announcement["target_labels"] ?? "");
                        ?>
                        <article class="announcement-card">
                            <div class="announcement-card-main">
                                <div class="announcement-title-line">
                                    <h2><?php echo e($announcement["title"]); ?></h2>
                                    <div class="badge-group">
                                        <span class="status-badge <?php echo e($status_class); ?>"><?php echo e($status_label); ?></span>
                                        <?php foreach ($target_labels as $target_label): ?>
                                            <span class="audience-badge"><?php echo e(target_audience_label($target_label)); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <p class="announcement-message"><?php echo nl2br(e($announcement["message"])); ?></p>

                                <div class="announcement-meta">
                                    <span>Created: <?php echo e(format_date_label($announcement["created_at"])); ?></span>
                                    <?php if (!empty($announcement["published_at"])): ?>
                                        <span>Published: <?php echo e(format_date_label($announcement["published_at"])); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="announcement-actions">
                                <a class="action-button edit" href="announcement_page.php?action=edit&id=<?php echo (int) $announcement["announcement_id"]; ?>" title="Edit Announcement">
                                    <?php echo icon_svg("edit"); ?>
                                </a>

                                <?php if ($announcement["announcement_status"] !== "Published"): ?>
                                    <form method="POST" class="inline-action-form publish-form">
                                        <input type="hidden" name="form_action" value="publish_announcement">
                                        <input type="hidden" name="announcement_id" value="<?php echo (int) $announcement["announcement_id"]; ?>">
                                        <button type="submit" class="action-button publish" title="Publish Announcement">
                                            <?php echo icon_svg("send"); ?>
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <form method="POST" class="inline-action-form delete-form">
                                    <input type="hidden" name="form_action" value="delete_announcement">
                                    <input type="hidden" name="announcement_id" value="<?php echo (int) $announcement["announcement_id"]; ?>">
                                    <button type="submit" class="action-button delete" title="Delete Announcement">
                                        <?php echo icon_svg("trash"); ?>
                                    </button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </main>

    <?php if ($show_create_modal): ?>
        <div class="modal-overlay">
            <form class="modal-box announcement-modal" method="POST" action="announcement_page.php">
                <input type="hidden" name="form_action" value="create_announcement">

                <h2>Create Announcement</h2>

                <label for="create_title">Title</label>
                <input id="create_title" type="text" name="title" maxlength="150" placeholder="Enter announcement title" required>

                <label for="create_message">Message</label>
                <textarea id="create_message" name="message" placeholder="Enter announcement message" required></textarea>

                <label for="create_target_audience">Target Audience</label>
                <select id="create_target_audience" name="target_audience" required>
                    <option value="all">All Users</option>
                    <option value="students">Students</option>
                    <option value="faculty">Faculty</option>
                </select>

                <div class="modal-actions three-actions">
                    <a class="secondary-button" href="announcement_page.php">Cancel</a>
                    <button type="submit" class="secondary-button" name="save_mode" value="draft">Save Draft</button>
                    <button type="submit" class="dark-button" name="save_mode" value="publish">Create &amp; Publish</button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <?php if ($show_edit_modal): ?>
        <?php $selected_target = get_target_value_from_labels($selected_announcement["target_labels"] ?? ""); ?>
        <div class="modal-overlay">
            <form class="modal-box announcement-modal" method="POST" action="announcement_page.php">
                <input type="hidden" name="form_action" value="update_announcement">
                <input type="hidden" name="announcement_id" value="<?php echo (int) $selected_announcement["announcement_id"]; ?>">

                <h2>Edit Announcement</h2>

                <label for="edit_title">Title</label>
                <input id="edit_title" type="text" name="title" maxlength="150" value="<?php echo e($selected_announcement["title"]); ?>" placeholder="Enter announcement title" required>

                <label for="edit_message">Message</label>
                <textarea id="edit_message" name="message" placeholder="Enter announcement message" required><?php echo e($selected_announcement["message"]); ?></textarea>

                <label for="edit_target_audience">Target Audience</label>
                <select id="edit_target_audience" name="target_audience" required>
                    <option value="all" <?php echo $selected_target === "all" ? "selected" : ""; ?>>All Users</option>
                    <option value="students" <?php echo $selected_target === "students" ? "selected" : ""; ?>>Students</option>
                    <option value="faculty" <?php echo $selected_target === "faculty" ? "selected" : ""; ?>>Faculty</option>
                </select>

                <?php if ($selected_announcement["announcement_status"] === "Draft"): ?>
                    <div class="modal-actions three-actions">
                        <a class="secondary-button" href="announcement_page.php">Cancel</a>
                        <button type="submit" class="secondary-button" name="save_mode" value="update">Save Changes</button>
                        <button type="submit" class="dark-button" name="save_mode" value="publish">Save &amp; Publish</button>
                    </div>
                <?php else: ?>
                    <div class="modal-actions">
                        <a class="secondary-button" href="announcement_page.php">Cancel</a>
                        <button type="submit" class="dark-button" name="save_mode" value="update">Update Announcement</button>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    <?php endif; ?>

    <?php if ($toast_message !== ""): ?>
        <div class="toast-modal <?php echo e($toast_type); ?>" id="toastModal">
            <?php echo e($toast_message); ?>
        </div>
    <?php endif; ?>

    <script>
        const toastModal = document.getElementById("toastModal");

        if (toastModal) {
            setTimeout(function () {
                toastModal.classList.add("hide");
            }, 2800);

            setTimeout(function () {
                toastModal.remove();
            }, 3400);
        }

        document.querySelectorAll(".delete-form").forEach(function (form) {
            form.addEventListener("submit", function (event) {
                if (!confirm("Delete this announcement? This action cannot be undone.")) {
                    event.preventDefault();
                }
            });
        });

        document.querySelectorAll(".publish-form").forEach(function (form) {
            form.addEventListener("submit", function (event) {
                if (!confirm("Publish this announcement now? Target users will receive a notification.")) {
                    event.preventDefault();
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
