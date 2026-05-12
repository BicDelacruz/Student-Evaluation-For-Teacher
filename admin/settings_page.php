<?php
session_start();

require_once __DIR__ . "/../database_connector.php";

if (!isset($pdo)) {
    die("Database connection variable \$pdo was not found. Please check database_connector.php.");
}

set_time_limit(0);

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
    header("Location: settings_page.php?toast_type=" . urlencode($type) . "&toast_message=" . urlencode($message));
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
        "building" => '<svg viewBox="0 0 24 24"><path d="M3 21h18"></path><path d="M5 21V7l8-4v18"></path><path d="M19 21V11l-6-4"></path><path d="M9 9h1"></path><path d="M9 13h1"></path><path d="M9 17h1"></path></svg>',
        "lock" => '<svg viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="10" rx="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>',
        "palette" => '<svg viewBox="0 0 24 24"><circle cx="13.5" cy="6.5" r=".5"></circle><circle cx="17.5" cy="10.5" r=".5"></circle><circle cx="8.5" cy="7.5" r=".5"></circle><circle cx="6.5" cy="12.5" r=".5"></circle><path d="M12 22a10 10 0 1 1 10-10c0 4-2.5 5-4.5 5H16a2 2 0 0 0-2 2 3 3 0 0 1-2 3z"></path></svg>',
        "clock" => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg>',
        "database" => '<svg viewBox="0 0 24 24"><ellipse cx="12" cy="5" rx="8" ry="3"></ellipse><path d="M4 5v6c0 1.7 3.6 3 8 3s8-1.3 8-3V5"></path><path d="M4 11v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6"></path></svg>',
        "upload" => '<svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><path d="M17 8l-5-5-5 5"></path><path d="M12 3v12"></path></svg>',
        "download" => '<svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><path d="M7 10l5 5 5-5"></path><path d="M12 15V3"></path></svg>',
        "restore" => '<svg viewBox="0 0 24 24"><path d="M3 12a9 9 0 1 0 3-6.7"></path><path d="M3 3v6h6"></path><path d="M12 7v5l3 2"></path></svg>',
        "trash" => '<svg viewBox="0 0 24 24"><path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="M19 6l-1 14H6L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path></svg>',
        "check" => '<svg viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"></path></svg>',
        "alert" => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><path d="M12 8v4"></path><path d="M12 16h.01"></path></svg>',
        "eye" => '<svg viewBox="0 0 24 24"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"></path><circle cx="12" cy="12" r="3"></circle></svg>',
        "eye_closed" => '<svg viewBox="0 0 24 24"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a20.29 20.29 0 0 1 5.06-5.94"></path><path d="M9.9 4.24A10.76 10.76 0 0 1 12 4c7 0 11 7 11 7a20.1 20.1 0 0 1-3.17 4.15"></path><path d="M14.12 14.12A3 3 0 0 1 9.88 9.88"></path><path d="M3 3l18 18"></path></svg>'
    ];

    return $icons[$name] ?? "";
}

function qid($identifier)
{
    return "`" . str_replace("`", "``", (string) $identifier) . "`";
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

function format_bytes_label($bytes)
{
    $bytes = (float) $bytes;

    if ($bytes <= 0) {
        return "0 B";
    }

    $units = ["B", "KB", "MB", "GB", "TB"];
    $power = min((int) floor(log($bytes, 1024)), count($units) - 1);
    $value = $bytes / pow(1024, $power);

    if ($power === 0) {
        return (string) (int) $value . " " . $units[$power];
    }

    return number_format($value, 1) . " " . $units[$power];
}

function get_current_admin($pdo)
{
    if (!empty($_SESSION["admin_id"])) {
        $statement = $pdo->prepare("SELECT a.admin_id, a.user_id, a.full_name, u.password_hash FROM admin a INNER JOIN `user` u ON u.user_id = a.user_id WHERE a.admin_id = :admin_id AND a.admin_status = 'Active' AND u.account_status = 'Active' LIMIT 1");
        $statement->execute(["admin_id" => (int) $_SESSION["admin_id"]]);
        $admin = $statement->fetch(PDO::FETCH_ASSOC);

        if ($admin) {
            return $admin;
        }
    }

    if (!empty($_SESSION["user_id"])) {
        $statement = $pdo->prepare("SELECT a.admin_id, a.user_id, a.full_name, u.password_hash FROM admin a INNER JOIN `user` u ON u.user_id = a.user_id INNER JOIN role r ON r.role_id = u.role_id WHERE a.user_id = :user_id AND LOWER(r.role_name) = 'admin' AND a.admin_status = 'Active' AND u.account_status = 'Active' LIMIT 1");
        $statement->execute(["user_id" => (int) $_SESSION["user_id"]]);
        $admin = $statement->fetch(PDO::FETCH_ASSOC);

        if ($admin) {
            return $admin;
        }
    }

    $statement = $pdo->query("SELECT a.admin_id, a.user_id, a.full_name, u.password_hash FROM admin a INNER JOIN `user` u ON u.user_id = a.user_id WHERE a.admin_status = 'Active' AND u.account_status = 'Active' ORDER BY a.admin_id LIMIT 1");
    $admin = $statement->fetch(PDO::FETCH_ASSOC);

    if ($admin) {
        return $admin;
    }

    die("No active admin account was found. Please create or activate an admin account first.");
}

function set_app_context($pdo, $user_id)
{
    try {
        $statement = $pdo->prepare("SET @app_user_id = :user_id");
        $statement->execute(["user_id" => (int) $user_id]);

        $ip_address = $_SERVER["REMOTE_ADDR"] ?? null;
        $statement = $pdo->prepare("SET @app_ip_address = :ip_address");
        $statement->execute(["ip_address" => $ip_address]);
    } catch (Throwable $exception) {
    }
}

function table_exists($pdo, $table_name)
{
    $statement = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table_name");
    $statement->execute(["table_name" => $table_name]);

    return (int) $statement->fetchColumn() > 0;
}

function get_system_setting($pdo, $key, $default = "")
{
    $statement = $pdo->prepare("SELECT setting_value FROM system_setting WHERE setting_key = :setting_key LIMIT 1");
    $statement->execute(["setting_key" => $key]);
    $value = $statement->fetchColumn();

    if ($value === false || $value === null || $value === "") {
        return $default;
    }

    return (string) $value;
}

function upsert_system_setting($pdo, $key, $value, $group, $admin_id)
{
    $statement = $pdo->prepare("INSERT INTO system_setting (setting_key, setting_value, setting_group, updated_by_admin_id) VALUES (:setting_key, :setting_value, :setting_group, :updated_by_admin_id) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_group = VALUES(setting_group), updated_by_admin_id = VALUES(updated_by_admin_id), updated_at = CURRENT_TIMESTAMP");
    $statement->execute([
        "setting_key" => $key,
        "setting_value" => $value,
        "setting_group" => $group,
        "updated_by_admin_id" => (int) $admin_id
    ]);
}

function get_school_profile($pdo)
{
    $statement = $pdo->query("SELECT * FROM school_profile ORDER BY school_profile_id ASC LIMIT 1");
    $profile = $statement->fetch(PDO::FETCH_ASSOC);

    if ($profile) {
        return $profile;
    }

    return [
        "school_profile_id" => null,
        "school_name" => "Eastgate College",
        "school_logo_path" => "../img/eastgate_college_logo.png",
        "contact_email" => "info@eastgate.edu",
        "contact_phone" => "+63 2 1234 5678",
        "address" => "123 Education St., Manila, Philippines"
    ];
}

function backup_directory_path()
{
    return __DIR__ . DIRECTORY_SEPARATOR . "backups";
}

function backup_relative_path($file_name)
{
    return "backups/" . $file_name;
}

function ensure_backup_directory()
{
    $directory = backup_directory_path();

    if (!is_dir($directory)) {
        if (!mkdir($directory, 0755, true)) {
            throw new RuntimeException("Unable to create the backups folder.");
        }
    }

    if (!is_writable($directory)) {
        throw new RuntimeException("The backups folder is not writable.");
    }

    return $directory;
}

function sql_value($pdo, $value)
{
    if ($value === null) {
        return "NULL";
    }

    return $pdo->quote((string) $value);
}

function fetch_database_name($pdo)
{
    $database_name = $pdo->query("SELECT DATABASE()")->fetchColumn();

    if (!$database_name) {
        throw new RuntimeException("Unable to detect the active database name.");
    }

    return (string) $database_name;
}

function strip_definer($create_sql)
{
    return preg_replace('/DEFINER=`[^`]+`@`[^`]+`\s+/i', '', (string) $create_sql);
}

function generate_mariadb_backup($pdo, $file_path)
{
    $database_name = fetch_database_name($pdo);
    $handle = fopen($file_path, "wb");

    if (!$handle) {
        throw new RuntimeException("Unable to create the backup file.");
    }

    $write = function ($text) use ($handle) {
        fwrite($handle, $text);
    };

    $write("-- Student Evaluation for Teacher System Backup\n");
    $write("-- Database: " . $database_name . "\n");
    $write("-- Generated: " . date("Y-m-d H:i:s") . "\n\n");
    $write("SET FOREIGN_KEY_CHECKS=0;\n");
    $write("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n");
    $write("CREATE DATABASE IF NOT EXISTS " . qid($database_name) . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n");
    $write("USE " . qid($database_name) . ";\n\n");

    $objects = $pdo->query("SHOW FULL TABLES")->fetchAll(PDO::FETCH_NUM);
    $tables = [];
    $views = [];

    foreach ($objects as $object) {
        if (($object[1] ?? "") === "BASE TABLE") {
            $tables[] = $object[0];
        } elseif (($object[1] ?? "") === "VIEW") {
            $views[] = $object[0];
        }
    }

    foreach ($views as $view) {
        $write("DROP VIEW IF EXISTS " . qid($view) . ";\n");
    }

    foreach ($tables as $table) {
        $write("DROP TABLE IF EXISTS " . qid($table) . ";\n");
    }

    $write("\n");

    foreach ($tables as $table) {
        $create_statement = $pdo->query("SHOW CREATE TABLE " . qid($table))->fetch(PDO::FETCH_ASSOC);
        $create_sql = $create_statement["Create Table"] ?? array_values($create_statement)[1] ?? "";

        $write("-- Table structure for " . qid($table) . "\n");
        $write($create_sql . ";\n\n");

        $rows_statement = $pdo->query("SELECT * FROM " . qid($table));
        $first_row = true;
        $column_names = [];
        $values_batch = [];
        $batch_size = 100;

        while ($row = $rows_statement->fetch(PDO::FETCH_ASSOC)) {
            if ($first_row) {
                $column_names = array_map("qid", array_keys($row));
                $first_row = false;
            }

            $values = [];
            foreach ($row as $value) {
                $values[] = sql_value($pdo, $value);
            }

            $values_batch[] = "(" . implode(", ", $values) . ")";

            if (count($values_batch) >= $batch_size) {
                $write("INSERT INTO " . qid($table) . " (" . implode(", ", $column_names) . ") VALUES\n" . implode(",\n", $values_batch) . ";\n");
                $values_batch = [];
            }
        }

        if (!$first_row && count($values_batch) > 0) {
            $write("INSERT INTO " . qid($table) . " (" . implode(", ", $column_names) . ") VALUES\n" . implode(",\n", $values_batch) . ";\n");
        }

        $write("\n");
    }

    foreach ($views as $view) {
        $create_statement = $pdo->query("SHOW CREATE VIEW " . qid($view))->fetch(PDO::FETCH_ASSOC);
        $create_sql = $create_statement["Create View"] ?? array_values($create_statement)[1] ?? "";
        $create_sql = strip_definer($create_sql);

        $write("-- View structure for " . qid($view) . "\n");
        $write("DROP VIEW IF EXISTS " . qid($view) . ";\n");
        $write($create_sql . ";\n\n");
    }

    $write("DELIMITER $$\n\n");

    $routines = $pdo->prepare("SELECT ROUTINE_NAME, ROUTINE_TYPE FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = DATABASE() ORDER BY ROUTINE_TYPE, ROUTINE_NAME");
    $routines->execute();

    foreach ($routines->fetchAll(PDO::FETCH_ASSOC) as $routine) {
        $routine_name = $routine["ROUTINE_NAME"];
        $routine_type = strtoupper($routine["ROUTINE_TYPE"]);
        $show_keyword = $routine_type === "FUNCTION" ? "FUNCTION" : "PROCEDURE";
        $create_statement = $pdo->query("SHOW CREATE " . $show_keyword . " " . qid($routine_name))->fetch(PDO::FETCH_ASSOC);
        $create_sql = $create_statement["Create " . ucfirst(strtolower($show_keyword))] ?? array_values($create_statement)[2] ?? "";
        $create_sql = strip_definer($create_sql);

        $write("DROP " . $show_keyword . " IF EXISTS " . qid($routine_name) . "$$\n");
        $write($create_sql . "$$\n\n");
    }

    $triggers = $pdo->query("SHOW TRIGGERS")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($triggers as $trigger) {
        $trigger_name = $trigger["Trigger"];
        $create_statement = $pdo->query("SHOW CREATE TRIGGER " . qid($trigger_name))->fetch(PDO::FETCH_ASSOC);
        $create_sql = $create_statement["SQL Original Statement"] ?? array_values($create_statement)[2] ?? "";
        $create_sql = strip_definer($create_sql);

        $write("DROP TRIGGER IF EXISTS " . qid($trigger_name) . "$$\n");
        $write($create_sql . "$$\n\n");
    }

    try {
        $events = $pdo->query("SHOW EVENTS")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($events as $event) {
            $event_name = $event["Name"] ?? $event["Event"] ?? null;

            if (!$event_name) {
                continue;
            }

            $create_statement = $pdo->query("SHOW CREATE EVENT " . qid($event_name))->fetch(PDO::FETCH_ASSOC);
            $create_sql = $create_statement["Create Event"] ?? array_values($create_statement)[3] ?? "";
            $create_sql = strip_definer($create_sql);

            $write("DROP EVENT IF EXISTS " . qid($event_name) . "$$\n");
            $write($create_sql . "$$\n\n");
        }
    } catch (Throwable $exception) {
        $write("-- Events could not be exported by the current database user.\n\n");
    }

    $write("DELIMITER ;\n\n");
    $write("SET FOREIGN_KEY_CHECKS=1;\n");

    fclose($handle);
}

function parse_and_restore_sql_file($pdo, $file_path)
{
    if (!is_file($file_path)) {
        throw new RuntimeException("The selected backup file was not found.");
    }

    $handle = fopen($file_path, "rb");

    if (!$handle) {
        throw new RuntimeException("Unable to read the selected backup file.");
    }

    $delimiter = ";";
    $buffer = "";

    while (($line = fgets($handle)) !== false) {
        $trimmed = trim($line);

        if ($trimmed === "" || preg_match('/^--/', $trimmed)) {
            continue;
        }

        if (stripos($trimmed, "DELIMITER ") === 0) {
            $delimiter = trim(substr($trimmed, 10));
            continue;
        }

        $buffer .= $line;
        $trimmed_buffer = rtrim($buffer);

        if ($delimiter !== "" && substr($trimmed_buffer, -strlen($delimiter)) === $delimiter) {
            $statement_sql = substr($trimmed_buffer, 0, -strlen($delimiter));
            $statement_sql = trim($statement_sql);

            if ($statement_sql !== "" && !preg_match('/^CREATE\s+DATABASE/i', $statement_sql) && !preg_match('/^USE\s+/i', $statement_sql)) {
                $pdo->exec($statement_sql);
            }

            $buffer = "";
        }
    }

    $remaining = trim($buffer);

    if ($remaining !== "" && !preg_match('/^CREATE\s+DATABASE/i', $remaining) && !preg_match('/^USE\s+/i', $remaining)) {
        $pdo->exec($remaining);
    }

    fclose($handle);
}

function get_backup_record($pdo, $backup_id)
{
    $statement = $pdo->prepare("SELECT * FROM backup_record WHERE backup_record_id = :backup_record_id LIMIT 1");
    $statement->execute(["backup_record_id" => (int) $backup_id]);

    return $statement->fetch(PDO::FETCH_ASSOC);
}

function backup_absolute_path($backup)
{
    $stored_path = (string) ($backup["backup_file_path"] ?? "");

    if ($stored_path === "") {
        return null;
    }

    $starts_with_directory_separator = substr($stored_path, 0, strlen(DIRECTORY_SEPARATOR)) === DIRECTORY_SEPARATOR;

    if (preg_match('/^[A-Za-z]:[\\\\\/]/', $stored_path) || $starts_with_directory_separator) {
        return $stored_path;
    }

    return __DIR__ . DIRECTORY_SEPARATOR . str_replace(["/", "\\"], DIRECTORY_SEPARATOR, $stored_path);
}

function log_backup_action($pdo, $backup_id, $admin_id, $action_type, $status, $message)
{
    try {
        $statement = $pdo->prepare("INSERT INTO backup_restore_log (backup_record_id, performed_by_admin_id, action_type, action_status, message) VALUES (:backup_record_id, :performed_by_admin_id, :action_type, :action_status, :message)");
        $statement->execute([
            "backup_record_id" => (int) $backup_id,
            "performed_by_admin_id" => (int) $admin_id,
            "action_type" => $action_type,
            "action_status" => $status,
            "message" => $message
        ]);
    } catch (Throwable $exception) {
    }
}

$current_admin = get_current_admin($pdo);
set_app_context($pdo, (int) $current_admin["user_id"]);

if (isset($_GET["download_backup"])) {
    $backup_id = (int) $_GET["download_backup"];
    $backup = get_backup_record($pdo, $backup_id);

    if (!$backup || $backup["backup_status"] === "Deleted") {
        redirect_with_message("error", "Backup record was not found.");
    }

    $file_path = backup_absolute_path($backup);

    if (!$file_path || !is_file($file_path)) {
        log_backup_action($pdo, $backup_id, (int) $current_admin["admin_id"], "Download", "Failed", "Backup file was missing.");
        redirect_with_message("error", "Backup file was not found on the server.");
    }

    log_backup_action($pdo, $backup_id, (int) $current_admin["admin_id"], "Download", "Success", "Backup file downloaded.");

    while (ob_get_level()) {
        ob_end_clean();
    }

    header("Content-Type: application/sql");
    header("Content-Disposition: attachment; filename=\"" . basename($backup["backup_file_name"]) . "\"");
    header("Content-Length: " . filesize($file_path));
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Pragma: no-cache");
    readfile($file_path);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $form_action = $_POST["form_action"] ?? "";

    try {
        if ($form_action === "save_profile") {
            $school_name = clean_string($_POST["school_name"] ?? "");
            $contact_email = clean_string($_POST["contact_email"] ?? "");
            $address = clean_string($_POST["address"] ?? "");
            $contact_phone = clean_string($_POST["contact_phone"] ?? "");
            $profile = get_school_profile($pdo);
            $logo_path = $profile["school_logo_path"] ?: "../img/eastgate_college_logo.png";

            if ($school_name === "") {
                redirect_with_message("error", "School name is required.");
            }

            if ($contact_email !== "" && !filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
                redirect_with_message("error", "Contact email must be valid.");
            }

            if (!empty($_FILES["school_logo"]["name"])) {
                if ($_FILES["school_logo"]["error"] !== UPLOAD_ERR_OK) {
                    redirect_with_message("error", "Logo upload failed. Please try again.");
                }

                $allowed_extensions = ["png", "jpg", "jpeg", "webp"];
                $extension = strtolower(pathinfo($_FILES["school_logo"]["name"], PATHINFO_EXTENSION));

                if (!in_array($extension, $allowed_extensions, true)) {
                    redirect_with_message("error", "Logo must be PNG, JPG, JPEG, or WEBP.");
                }

                if ((int) $_FILES["school_logo"]["size"] > 2 * 1024 * 1024) {
                    redirect_with_message("error", "Logo file must not exceed 2 MB.");
                }

                $image_directory = __DIR__ . "/../img";

                if (!is_dir($image_directory)) {
                    mkdir($image_directory, 0755, true);
                }

                $file_name = "school_logo_" . date("Ymd_His") . "_" . bin2hex(random_bytes(4)) . "." . $extension;
                $destination = $image_directory . DIRECTORY_SEPARATOR . $file_name;

                if (!move_uploaded_file($_FILES["school_logo"]["tmp_name"], $destination)) {
                    redirect_with_message("error", "Unable to save the uploaded logo.");
                }

                $logo_path = "../img/" . $file_name;
            }

            if (!empty($profile["school_profile_id"])) {
                $statement = $pdo->prepare("UPDATE school_profile SET school_name = :school_name, school_logo_path = :school_logo_path, contact_email = :contact_email, contact_phone = :contact_phone, address = :address, updated_by_admin_id = :updated_by_admin_id WHERE school_profile_id = :school_profile_id");
                $statement->execute([
                    "school_name" => $school_name,
                    "school_logo_path" => $logo_path,
                    "contact_email" => $contact_email,
                    "contact_phone" => $contact_phone,
                    "address" => $address,
                    "updated_by_admin_id" => (int) $current_admin["admin_id"],
                    "school_profile_id" => (int) $profile["school_profile_id"]
                ]);
            } else {
                $statement = $pdo->prepare("INSERT INTO school_profile (school_name, school_logo_path, contact_email, contact_phone, address, updated_by_admin_id) VALUES (:school_name, :school_logo_path, :contact_email, :contact_phone, :address, :updated_by_admin_id)");
                $statement->execute([
                    "school_name" => $school_name,
                    "school_logo_path" => $logo_path,
                    "contact_email" => $contact_email,
                    "contact_phone" => $contact_phone,
                    "address" => $address,
                    "updated_by_admin_id" => (int) $current_admin["admin_id"]
                ]);
            }

            redirect_with_message("success", "School profile updated successfully.");
        }

        if ($form_action === "update_password") {
            $current_password = (string) ($_POST["current_password"] ?? "");
            $new_password = (string) ($_POST["new_password"] ?? "");
            $confirm_password = (string) ($_POST["confirm_password"] ?? "");

            if ($current_password === "" || $new_password === "" || $confirm_password === "") {
                redirect_with_message("error", "All password fields are required.");
            }

            if (!password_verify($current_password, (string) $current_admin["password_hash"])) {
                redirect_with_message("error", "Current password is incorrect.");
            }

            if (strlen($new_password) < 8 || !preg_match('/[A-Z]/', $new_password) || !preg_match('/[a-z]/', $new_password) || !preg_match('/[0-9]/', $new_password)) {
                redirect_with_message("error", "New password must have at least 8 characters, uppercase and lowercase letters, and one number.");
            }

            if ($new_password !== $confirm_password) {
                redirect_with_message("error", "New password and confirmation do not match.");
            }

            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            $statement = $pdo->prepare("UPDATE `user` SET password_hash = :password_hash WHERE user_id = :user_id");
            $statement->execute([
                "password_hash" => $hash,
                "user_id" => (int) $current_admin["user_id"]
            ]);

            redirect_with_message("success", "Admin password updated successfully.");
        }

        if ($form_action === "save_settings") {
            $session_timeout = (int) ($_POST["session_timeout"] ?? 30);

            if ($session_timeout < 5 || $session_timeout > 480) {
                redirect_with_message("error", "Session timeout must be between 5 and 480 minutes.");
            }

            upsert_system_setting($pdo, "session_timeout_minutes", (string) $session_timeout, "Session", (int) $current_admin["admin_id"]);
            upsert_system_setting($pdo, "theme_mode", "Auto", "Theme", (int) $current_admin["admin_id"]);

            redirect_with_message("success", "System settings saved successfully.");
        }

        if ($form_action === "create_backup") {
            $backup_directory = ensure_backup_directory();
            $database_name = fetch_database_name($pdo);
            $safe_database_name = preg_replace('/[^A-Za-z0-9_]+/', '_', $database_name);
            $file_name = $safe_database_name . "_backup_" . date("Y_m_d_H_i_s") . ".sql";
            $file_path = $backup_directory . DIRECTORY_SEPARATOR . $file_name;
            $relative_path = backup_relative_path($file_name);

            try {
                generate_mariadb_backup($pdo, $file_path);
                $file_size = is_file($file_path) ? filesize($file_path) : 0;

                $statement = $pdo->prepare("INSERT INTO backup_record (backup_file_name, backup_file_path, backup_size, backup_status, created_by_admin_id) VALUES (:backup_file_name, :backup_file_path, :backup_size, 'Completed', :created_by_admin_id)");
                $statement->execute([
                    "backup_file_name" => $file_name,
                    "backup_file_path" => $relative_path,
                    "backup_size" => $file_size,
                    "created_by_admin_id" => (int) $current_admin["admin_id"]
                ]);

                redirect_with_message("success", "Backup created successfully.");
            } catch (Throwable $exception) {
                if (is_file($file_path)) {
                    @unlink($file_path);
                }

                $statement = $pdo->prepare("INSERT INTO backup_record (backup_file_name, backup_file_path, backup_size, backup_status, created_by_admin_id) VALUES (:backup_file_name, :backup_file_path, 0, 'Failed', :created_by_admin_id)");
                $statement->execute([
                    "backup_file_name" => $file_name,
                    "backup_file_path" => $relative_path,
                    "created_by_admin_id" => (int) $current_admin["admin_id"]
                ]);

                redirect_with_message("error", "Backup failed: " . $exception->getMessage());
            }
        }

        if ($form_action === "restore_backup") {
            $backup_id = (int) ($_POST["backup_record_id"] ?? 0);
            $backup = get_backup_record($pdo, $backup_id);

            if (!$backup || $backup["backup_status"] === "Deleted") {
                redirect_with_message("error", "Backup record was not found.");
            }

            $file_path = backup_absolute_path($backup);

            if (!$file_path || !is_file($file_path)) {
                log_backup_action($pdo, $backup_id, (int) $current_admin["admin_id"], "Restore", "Failed", "Backup file was missing.");
                redirect_with_message("error", "Backup file was not found on the server.");
            }

            try {
                parse_and_restore_sql_file($pdo, $file_path);
                set_app_context($pdo, (int) $current_admin["user_id"]);
                log_backup_action($pdo, $backup_id, (int) $current_admin["admin_id"], "Restore", "Success", "Backup restored successfully.");
                redirect_with_message("success", "Backup restored successfully.");
            } catch (Throwable $exception) {
                try {
                    log_backup_action($pdo, $backup_id, (int) $current_admin["admin_id"], "Restore", "Failed", $exception->getMessage());
                } catch (Throwable $inner_exception) {
                }

                redirect_with_message("error", "Restore failed: " . $exception->getMessage());
            }
        }

        if ($form_action === "delete_backup") {
            $backup_id = (int) ($_POST["backup_record_id"] ?? 0);
            $backup = get_backup_record($pdo, $backup_id);

            if (!$backup || $backup["backup_status"] === "Deleted") {
                redirect_with_message("error", "Backup record was not found.");
            }

            $file_path = backup_absolute_path($backup);

            if ($file_path && is_file($file_path)) {
                @unlink($file_path);
            }

            $statement = $pdo->prepare("UPDATE backup_record SET backup_status = 'Deleted' WHERE backup_record_id = :backup_record_id");
            $statement->execute(["backup_record_id" => $backup_id]);
            log_backup_action($pdo, $backup_id, (int) $current_admin["admin_id"], "Delete", "Success", "Backup file deleted and record marked as deleted.");

            redirect_with_message("success", "Backup deleted successfully.");
        }
    } catch (Throwable $exception) {
        redirect_with_message("error", $exception->getMessage());
    }
}

$school_profile = get_school_profile($pdo);
$session_timeout = get_system_setting($pdo, "session_timeout_minutes", "30");
$theme_mode = get_system_setting($pdo, "theme_mode", "Auto");

$backup_statement = $pdo->query("SELECT * FROM backup_record WHERE backup_status <> 'Deleted' ORDER BY created_at DESC, backup_record_id DESC");
$backup_records = $backup_statement->fetchAll(PDO::FETCH_ASSOC);

$total_backups = count(array_filter($backup_records, function ($backup) {
    return $backup["backup_status"] === "Completed";
}));

$last_backup = "No backup yet";
$total_backup_size = 0;

foreach ($backup_records as $backup) {
    if ($backup["backup_status"] === "Completed" && $last_backup === "No backup yet") {
        $last_backup = format_date_label($backup["created_at"]);
    }

    if ($backup["backup_status"] === "Completed") {
        $total_backup_size += (int) $backup["backup_size"];
    }
}

$modal_action = $_GET["modal"] ?? "";
$modal_backup_id = (int) ($_GET["backup_id"] ?? 0);
$modal_backup = null;

if (in_array($modal_action, ["restore", "delete"], true) && $modal_backup_id > 0) {
    $modal_backup = get_backup_record($pdo, $modal_backup_id);

    if (!$modal_backup || $modal_backup["backup_status"] === "Deleted") {
        $modal_backup = null;
    }
}

$toast_type = $_GET["toast_type"] ?? "";
$toast_message = $_GET["toast_message"] ?? "";
$logout_role_label = $_SESSION["role_name"] ?? "System Administrator";
$logo_src = $school_profile["school_logo_path"] ?: "../img/eastgate_college_logo.png";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
    <link rel="stylesheet" href="settings-page.css">
</head>
<body class="settings-page">
    <aside class="sidebar">
        <div class="brand">
            <img src="<?php echo e($logo_src); ?>" alt="School Logo">
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
            <a class="nav-link" href="announcement_page.php"><?php echo icon_svg("announcement"); ?> Announcements</a>
            <a class="nav-link active" href="settings_page.php"><?php echo icon_svg("settings"); ?> Settings</a>
            <a class="nav-link" href="#"><?php echo icon_svg("moon"); ?> Dark Mode</a>
        </nav>

        <div class="sidebar-bottom">
            <a class="logout-link" href="../login/login_page.php"><?php echo icon_svg("logout"); ?> Logout</a>
        </div>
    </aside>

    <main class="main-content">
        <div class="content-wrap">
            <div class="settings-shell">
                <div class="page-title-row">
                    <div>
                        <h1>Settings</h1>
                        <p>Manage system settings and configurations</p>
                    </div>
                </div>

                <section class="settings-card">
                    <div class="card-header">
                        <h2><?php echo icon_svg("building"); ?> School Profile</h2>
                    </div>

                    <form method="POST" action="settings_page.php" enctype="multipart/form-data">
                        <input type="hidden" name="form_action" value="save_profile">

                        <div class="logo-upload-row">
                            <div>
                                <label>School Logo</label>
                                <div class="logo-upload-wrap">
                                    <div class="logo-preview">
                                        <?php if (!empty($logo_src)): ?>
                                            <img src="<?php echo e($logo_src); ?>" alt="School Logo Preview">
                                        <?php else: ?>
                                            <?php echo icon_svg("building"); ?>
                                        <?php endif; ?>
                                    </div>
                                    <label class="upload-button">
                                        <?php echo icon_svg("upload"); ?> Upload Logo
                                        <input type="file" name="school_logo" accept="image/png,image/jpeg,image/webp">
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="two-column form-grid-spaced">
                            <div>
                                <label>School Name</label>
                                <input type="text" name="school_name" value="<?php echo e($school_profile["school_name"] ?? ""); ?>" required>
                            </div>

                            <div>
                                <label>Contact Email</label>
                                <input type="email" name="contact_email" value="<?php echo e($school_profile["contact_email"] ?? ""); ?>">
                            </div>

                            <div>
                                <label>Address</label>
                                <input type="text" name="address" value="<?php echo e($school_profile["address"] ?? ""); ?>">
                            </div>

                            <div>
                                <label>Contact Phone</label>
                                <input type="text" name="contact_phone" value="<?php echo e($school_profile["contact_phone"] ?? ""); ?>">
                            </div>
                        </div>

                        <button class="primary-button settings-button" type="submit">Save Profile</button>
                    </form>
                </section>

                <section class="settings-card compact-card">
                    <div class="card-header">
                        <h2><?php echo icon_svg("lock"); ?> Change Password</h2>
                    </div>

                    <form method="POST" action="settings_page.php" class="password-form">
                        <input type="hidden" name="form_action" value="update_password">

                        <div class="password-width">
                            <label>Current Password</label>
                            <div class="password-wrapper">
                                <input type="password" name="current_password" placeholder="Enter current password" autocomplete="current-password" required>
                                <button type="button" class="password-toggle" aria-label="Show or hide password"><?php echo icon_svg("eye"); ?></button>
                            </div>

                            <label>New Password</label>
                            <div class="password-wrapper">
                                <input type="password" name="new_password" placeholder="Enter new password" autocomplete="new-password" required>
                                <button type="button" class="password-toggle" aria-label="Show or hide password"><?php echo icon_svg("eye"); ?></button>
                            </div>

                            <label>Confirm New Password</label>
                            <div class="password-wrapper">
                                <input type="password" name="confirm_password" placeholder="Confirm new password" autocomplete="new-password" required>
                                <button type="button" class="password-toggle" aria-label="Show or hide password"><?php echo icon_svg("eye"); ?></button>
                            </div>

                            <button class="primary-button settings-button" type="submit">Update Password</button>
                        </div>
                    </form>
                </section>

                <section class="settings-card compact-card">
                    <div class="card-header">
                        <h2><?php echo icon_svg("palette"); ?> Theme Settings</h2>
                    </div>

                    <p class="section-description">Theme switching is available in the sidebar. Default theme is automatically detected from system preferences.</p>

                    <div class="theme-status-box">
                        <div>
                            <h3>Auto Theme Detection</h3>
                            <p>Follows system preference</p>
                        </div>
                        <span class="status-badge active">Enabled</span>
                    </div>
                </section>

                <section class="settings-card compact-card">
                    <div class="card-header">
                        <h2><?php echo icon_svg("clock"); ?> Session Settings</h2>
                    </div>

                    <form method="POST" action="settings_page.php" class="session-form">
                        <input type="hidden" name="form_action" value="save_settings">

                        <div class="password-width">
                            <label>Session Timeout</label>
                            <select name="session_timeout">
                                <?php foreach ([15, 30, 45, 60, 120, 240, 480] as $minutes): ?>
                                    <option value="<?php echo $minutes; ?>" <?php echo (int) $session_timeout === $minutes ? "selected" : ""; ?>>
                                        <?php echo $minutes; ?> minutes
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="input-help">Automatically log out users after this period of inactivity</p>
                            <button class="primary-button settings-button" type="submit">Save Settings</button>
                        </div>
                    </form>
                </section>

                <section class="settings-card backup-card">
                    <div class="card-header backup-header">
                        <h2><?php echo icon_svg("database"); ?> Backup and Recovery</h2>

                        <form method="POST" action="settings_page.php">
                            <input type="hidden" name="form_action" value="create_backup">
                            <button class="primary-button" type="submit"><?php echo icon_svg("database"); ?> Create New Backup</button>
                        </form>
                    </div>

                    <div class="backup-summary-grid">
                        <div class="summary-box blue">
                            <div>
                                <p>Total Backups</p>
                                <strong><?php echo (int) $total_backups; ?></strong>
                            </div>
                            <?php echo icon_svg("database"); ?>
                        </div>

                        <div class="summary-box green">
                            <div>
                                <p>Last Backup</p>
                                <strong><?php echo e($last_backup); ?></strong>
                            </div>
                            <?php echo icon_svg("clock"); ?>
                        </div>

                        <div class="summary-box purple">
                            <div>
                                <p>Total Size</p>
                                <strong><?php echo e(format_bytes_label($total_backup_size)); ?></strong>
                            </div>
                            <?php echo icon_svg("download"); ?>
                        </div>
                    </div>

                    <div class="backup-tips">
                        <?php echo icon_svg("alert"); ?>
                        <div>
                            <h3>Backup Best Practices</h3>
                            <ul>
                                <li>Create backups regularly, especially before major system changes</li>
                                <li>Store backup files in a secure off site location</li>
                                <li>Test restore procedures periodically to ensure backup integrity</li>
                                <li>Keep at least 3 recent backups for recovery options</li>
                            </ul>
                        </div>
                    </div>

                    <h3 class="table-title">Backup History</h3>

                    <div class="table-card">
                        <table class="backup-table">
                            <thead>
                                <tr>
                                    <th>Filename</th>
                                    <th>Date</th>
                                    <th>Size</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($backup_records)): ?>
                                    <tr>
                                        <td class="empty-row" colspan="5">No backup records found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($backup_records as $backup): ?>
                                        <tr>
                                            <td class="backup-file-name"><?php echo e($backup["backup_file_name"]); ?></td>
                                            <td><?php echo e(format_date_label($backup["created_at"])); ?></td>
                                            <td><?php echo e(format_bytes_label((int) $backup["backup_size"])); ?></td>
                                            <td><span class="status-badge <?php echo strtolower(e($backup["backup_status"])); ?>"><?php echo icon_svg($backup["backup_status"] === "Completed" ? "check" : "alert"); ?> <?php echo e($backup["backup_status"]); ?></span></td>
                                            <td>
                                                <div class="action-icons">
                                                    <?php if ($backup["backup_status"] === "Completed"): ?>
                                                        <a class="action-button download" href="settings_page.php?download_backup=<?php echo (int) $backup["backup_record_id"]; ?>" title="Download Backup"><?php echo icon_svg("download"); ?></a>
                                                        <a class="action-button restore" href="settings_page.php?modal=restore&backup_id=<?php echo (int) $backup["backup_record_id"]; ?>" title="Restore Backup"><?php echo icon_svg("restore"); ?></a>
                                                    <?php endif; ?>
                                                    <a class="action-button delete" href="settings_page.php?modal=delete&backup_id=<?php echo (int) $backup["backup_record_id"]; ?>" title="Delete Backup"><?php echo icon_svg("trash"); ?></a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <?php if ($toast_type !== "" && $toast_message !== ""): ?>
        <div class="toast-modal <?php echo e($toast_type); ?>" id="toastModal">
            <?php echo e($toast_message); ?>
        </div>
    <?php endif; ?>

    <?php if ($modal_backup): ?>
        <div class="modal-overlay">
            <div class="modal-box confirmation-modal">
                <?php if ($modal_action === "restore"): ?>
                    <h2><?php echo icon_svg("restore"); ?> Restore Backup</h2>
                    <p class="modal-warning">Restoring this backup will replace the current database content. Make sure you have a recent backup before continuing.</p>
                    <div class="confirm-file-box">
                        <strong><?php echo e($modal_backup["backup_file_name"]); ?></strong>
                        <span><?php echo e(format_bytes_label((int) $modal_backup["backup_size"])); ?> · <?php echo e(format_date_label($modal_backup["created_at"])); ?></span>
                    </div>
                    <form method="POST" action="settings_page.php">
                        <input type="hidden" name="form_action" value="restore_backup">
                        <input type="hidden" name="backup_record_id" value="<?php echo (int) $modal_backup["backup_record_id"]; ?>">
                        <div class="modal-actions">
                            <a class="secondary-button" href="settings_page.php">Cancel</a>
                            <button class="primary-button danger-safe" type="submit"><?php echo icon_svg("restore"); ?> Confirm Restore</button>
                        </div>
                    </form>
                <?php else: ?>
                    <h2><?php echo icon_svg("trash"); ?> Delete Backup</h2>
                    <p class="modal-warning">This will remove the backup file from the server and hide the record from the backup history.</p>
                    <div class="confirm-file-box">
                        <strong><?php echo e($modal_backup["backup_file_name"]); ?></strong>
                        <span><?php echo e(format_bytes_label((int) $modal_backup["backup_size"])); ?> · <?php echo e(format_date_label($modal_backup["created_at"])); ?></span>
                    </div>
                    <form method="POST" action="settings_page.php">
                        <input type="hidden" name="form_action" value="delete_backup">
                        <input type="hidden" name="backup_record_id" value="<?php echo (int) $modal_backup["backup_record_id"]; ?>">
                        <div class="modal-actions">
                            <a class="secondary-button" href="settings_page.php">Cancel</a>
                            <button class="danger-button" type="submit"><?php echo icon_svg("trash"); ?> Delete Backup</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="admin_logout_overlay" id="adminLogoutOverlay" aria-hidden="true">
        <div class="admin_logout_modal" role="dialog" aria-modal="true" aria-labelledby="adminLogoutTitle">
            <div class="admin_logout_icon">
                <?php echo icon_svg("logout"); ?>
            </div>

            <h2 id="adminLogoutTitle">Confirm Logout</h2>

            <p>
                You are currently signed in as <strong><?php echo e($logout_role_label); ?></strong>.
                Logging out will end your current admin session and return you to the login page.
            </p>

            <div class="admin_logout_divider"></div>

            <div class="admin_logout_actions">
                <button type="button" class="admin_logout_cancel" id="adminLogoutCancel">Cancel</button>
                <button type="button" class="admin_logout_confirm" id="adminLogoutConfirm">
                    <?php echo icon_svg("logout"); ?> Logout
                </button>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll(".password-toggle").forEach(function (button) {
            button.addEventListener("click", function () {
                const input = button.parentElement.querySelector("input");
                input.type = input.type === "password" ? "text" : "password";
            });
        });

        const toast = document.getElementById("toastModal");
        if (toast) {
            setTimeout(function () {
                toast.classList.add("hide");
            }, 2800);
        }

        const logoInput = document.querySelector('input[name="school_logo"]');
        const logoPreview = document.querySelector(".logo-preview img");
        if (logoInput && logoPreview) {
            logoInput.addEventListener("change", function () {
                const file = logoInput.files[0];
                if (!file) {
                    return;
                }
                logoPreview.src = URL.createObjectURL(file);
            });
        }

        (function () {
            const overlay = document.getElementById("adminLogoutOverlay");
            const cancelButton = document.getElementById("adminLogoutCancel");
            const confirmButton = document.getElementById("adminLogoutConfirm");
            const logoutLinks = document.querySelectorAll(".logout-link");
            let logoutUrl = "../login/login_page.php";

            function openLogoutModal(url) {
                logoutUrl = url || logoutUrl;
                overlay.classList.add("show");
                overlay.setAttribute("aria-hidden", "false");
                document.body.classList.add("admin_logout_locked");
            }

            function closeLogoutModal() {
                overlay.classList.remove("show");
                overlay.setAttribute("aria-hidden", "true");
                document.body.classList.remove("admin_logout_locked");
            }

            logoutLinks.forEach(function (link) {
                link.addEventListener("click", function (event) {
                    event.preventDefault();
                    openLogoutModal(link.getAttribute("href"));
                });
            });

            cancelButton.addEventListener("click", closeLogoutModal);

            confirmButton.addEventListener("click", function () {
                window.location.href = logoutUrl;
            });

            overlay.addEventListener("click", function (event) {
                if (event.target === overlay) {
                    closeLogoutModal();
                }
            });

            document.addEventListener("keydown", function (event) {
                if (event.key === "Escape" && overlay.classList.contains("show")) {
                    closeLogoutModal();
                }
            });
        })();
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
