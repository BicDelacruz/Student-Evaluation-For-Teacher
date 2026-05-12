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

function redirect_self(array $params = []): void
{
    $base = basename(__FILE__);
    $query = $params ? "?" . http_build_query($params) : "";
    header("Location: " . $base . $query);
    exit;
}

function set_flash(string $message, string $type = "success"): void
{
    $_SESSION["evaluation_setup_flash"] = ["message" => $message, "type" => $type];
}

function get_flash(): ?array
{
    if (!isset($_SESSION["evaluation_setup_flash"])) {
        return null;
    }
    $flash = $_SESSION["evaluation_setup_flash"];
    unset($_SESSION["evaluation_setup_flash"]);
    return $flash;
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
        "plus" => '<svg viewBox="0 0 24 24"><path d="M12 5v14"></path><path d="M5 12h14"></path></svg>',
        "eye" => '<svg viewBox="0 0 24 24"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"></path><circle cx="12" cy="12" r="3"></circle></svg>',
        "edit" => '<svg viewBox="0 0 24 24"><path d="M12 20h9"></path><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"></path></svg>',
        "trash" => '<svg viewBox="0 0 24 24"><path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="M19 6l-1 14H6L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path></svg>',
        "calendar" => '<svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M16 2v4"></path><path d="M8 2v4"></path><path d="M3 10h18"></path></svg>',
        "help" => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 1 1 5.82 1c0 2-3 2-3 4"></path><path d="M12 17h.01"></path></svg>',
        "save" => '<svg viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><path d="M17 21v-8H7v8"></path><path d="M7 3v5h8"></path></svg>',
        "play" => '<svg viewBox="0 0 24 24"><path d="M5 3l14 9-14 9V3z"></path></svg>',
        "stop" => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"></circle><path d="M15 9l-6 6"></path><path d="M9 9l6 6"></path></svg>',
        "check" => '<svg viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"></path></svg>',
        "close" => '<svg viewBox="0 0 24 24"><path d="M18 6L6 18"></path><path d="M6 6l12 12"></path></svg>',
        "download" => '<svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><path d="M7 10l5 5 5-5"></path><path d="M12 15V3"></path></svg>',
        "history" => '<svg viewBox="0 0 24 24"><path d="M3 12a9 9 0 1 0 3-6.7"></path><path d="M3 3v6h6"></path><path d="M12 7v5l3 2"></path></svg>',
        "archive" => '<svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="4" rx="1"></rect><path d="M5 8v12h14V8"></path><path d="M10 12h4"></path></svg>',
        "filter" => '<svg viewBox="0 0 24 24"><path d="M22 3H2l8 9v7l4 2v-9l8-9z"></path></svg>'
    ];
    return $icons[$name] ?? "";
}

function ensure_support_tables(mysqli $db): void
{
    $db->query("CREATE TABLE IF NOT EXISTS evaluation_setup_setting (
        evaluation_setup_setting_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        evaluation_form_id BIGINT UNSIGNED NOT NULL,
        questions_to_display TINYINT UNSIGNED NOT NULL DEFAULT 5,
        updated_by_admin_id BIGINT UNSIGNED NULL,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (evaluation_setup_setting_id),
        UNIQUE KEY uq_evaluation_setup_form (evaluation_form_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->query("CREATE TABLE IF NOT EXISTS evaluation_period_scope (
        evaluation_period_scope_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        evaluation_period_id BIGINT UNSIGNED NOT NULL,
        scope_type ENUM('All','Department','Course','Year Level','Section') NOT NULL DEFAULT 'All',
        department_id BIGINT UNSIGNED NULL,
        course_id BIGINT UNSIGNED NULL,
        year_level TINYINT UNSIGNED NULL,
        section_id BIGINT UNSIGNED NULL,
        scope_status ENUM('Active','Completed','Archived') NOT NULL DEFAULT 'Active',
        opened_by_admin_id BIGINT UNSIGNED NULL,
        closed_by_admin_id BIGINT UNSIGNED NULL,
        opened_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        closed_at DATETIME NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (evaluation_period_scope_id),
        KEY idx_period_scope_period_status (evaluation_period_id, scope_status),
        KEY idx_period_scope_targets (scope_type, department_id, course_id, year_level, section_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    if (!db_column_exists($db, 'evaluation_period_scope', 'scope_status')) {
        $db->query("ALTER TABLE evaluation_period_scope ADD COLUMN scope_status ENUM('Active','Completed','Archived') NOT NULL DEFAULT 'Active' AFTER section_id");
    }
    if (!db_column_exists($db, 'evaluation_period_scope', 'opened_by_admin_id')) {
        $db->query("ALTER TABLE evaluation_period_scope ADD COLUMN opened_by_admin_id BIGINT UNSIGNED NULL AFTER scope_status");
    }
    if (!db_column_exists($db, 'evaluation_period_scope', 'closed_by_admin_id')) {
        $db->query("ALTER TABLE evaluation_period_scope ADD COLUMN closed_by_admin_id BIGINT UNSIGNED NULL AFTER opened_by_admin_id");
    }
    if (!db_column_exists($db, 'evaluation_period_scope', 'opened_at')) {
        $db->query("ALTER TABLE evaluation_period_scope ADD COLUMN opened_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER closed_by_admin_id");
    }
    if (!db_column_exists($db, 'evaluation_period_scope', 'closed_at')) {
        $db->query("ALTER TABLE evaluation_period_scope ADD COLUMN closed_at DATETIME NULL AFTER opened_at");
    }
    if (!db_column_exists($db, 'evaluation_period_scope', 'created_at')) {
        $db->query("ALTER TABLE evaluation_period_scope ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER closed_at");
    }
    if (!db_column_exists($db, 'evaluation_period_scope', 'updated_at')) {
        $db->query("ALTER TABLE evaluation_period_scope ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
    }

    if (!db_column_exists($db, 'evaluation_period_scope', 'academic_year_snapshot')) {
        $db->query("ALTER TABLE evaluation_period_scope ADD COLUMN academic_year_snapshot VARCHAR(20) NULL AFTER evaluation_period_id");
    }
    if (!db_column_exists($db, 'evaluation_period_scope', 'semester_snapshot')) {
        $db->query("ALTER TABLE evaluation_period_scope ADD COLUMN semester_snapshot VARCHAR(50) NULL AFTER academic_year_snapshot");
    }
    if (!db_column_exists($db, 'evaluation_period_scope', 'start_date_snapshot')) {
        $db->query("ALTER TABLE evaluation_period_scope ADD COLUMN start_date_snapshot DATE NULL AFTER semester_snapshot");
    }
    if (!db_column_exists($db, 'evaluation_period_scope', 'end_date_snapshot')) {
        $db->query("ALTER TABLE evaluation_period_scope ADD COLUMN end_date_snapshot DATE NULL AFTER start_date_snapshot");
    }

    $db->query("
        UPDATE evaluation_period_scope eps
        INNER JOIN evaluation_period ep
            ON ep.evaluation_period_id = eps.evaluation_period_id
        INNER JOIN term t
            ON t.term_id = ep.term_id
        INNER JOIN academic_year ay
            ON ay.academic_year_id = t.academic_year_id
        SET
            eps.academic_year_snapshot = COALESCE(eps.academic_year_snapshot, ay.academic_year_name),
            eps.semester_snapshot = COALESCE(eps.semester_snapshot, t.term_name),
            eps.start_date_snapshot = COALESCE(eps.start_date_snapshot, ep.start_date),
            eps.end_date_snapshot = COALESCE(eps.end_date_snapshot, ep.end_date)
        WHERE
            eps.academic_year_snapshot IS NULL
            OR eps.semester_snapshot IS NULL
            OR eps.start_date_snapshot IS NULL
            OR eps.end_date_snapshot IS NULL
    " );

    if (db_table_exists($db, 'student_evaluation_task') && !db_column_exists($db, 'student_evaluation_task', 'evaluation_access_status')) {
        $db->query("ALTER TABLE student_evaluation_task ADD COLUMN evaluation_access_status ENUM('Open','Closed') NOT NULL DEFAULT 'Closed' AFTER task_status");
    }
}


function normalize_academic_year(string $value): string
{
    return trim(preg_replace('/\s+/', ' ', str_replace('–', '-', $value)));
}

function display_academic_year(string $value): string
{
    return trim(str_replace(' - ', '-', $value));
}

function term_label(string $dbTerm): string
{
    return match ($dbTerm) {
        'First Semester' => '1st Semester',
        'Second Semester' => '2nd Semester',
        default => $dbTerm,
    };
}

function term_db_value(string $uiTerm): string
{
    return match ($uiTerm) {
        '1st Semester', 'First Semester' => 'First Semester',
        '2nd Semester', 'Second Semester' => 'Second Semester',
        default => 'Summer',
    };
}

function year_level_label($value): string
{
    $n = (int)$value;
    return match ($n) {
        1 => '1st Year',
        2 => '2nd Year',
        3 => '3rd Year',
        4 => '4th Year',
        default => $n . 'th Year',
    };
}

function scope_ui_label(string $scope): string
{
    return match ($scope) {
        'Department' => 'College',
        'Course' => 'Program',
        'Year Level' => 'Year Level',
        'Section' => 'Section',
        default => 'All',
    };
}

function current_context(mysqli $db, int $admin_id): array
{
    $period = fetch_one($db, "SELECT ep.*, t.term_name, t.academic_year_id, ay.academic_year_name
        FROM evaluation_period ep
        JOIN term t ON t.term_id = ep.term_id
        JOIN academic_year ay ON ay.academic_year_id = t.academic_year_id
        ORDER BY FIELD(ep.period_status, 'Open', 'Ongoing', 'Draft', 'Closed', 'Archived'), ep.evaluation_period_id DESC
        LIMIT 1");

    if (!$period) {
        $ay = fetch_one($db, "SELECT * FROM academic_year WHERE academic_year_status = 'Active' ORDER BY academic_year_id DESC LIMIT 1");
        if (!$ay) {
            execute_stmt($db, "INSERT INTO academic_year (academic_year_name, start_date, end_date, academic_year_status) VALUES ('2025 - 2026', '2025-08-01', '2026-07-31', 'Active')");
            $ayId = (int)$db->insert_id;
            $ayName = '2025 - 2026';
        } else {
            $ayId = (int)$ay['academic_year_id'];
            $ayName = $ay['academic_year_name'];
        }

        $term = fetch_one($db, "SELECT * FROM term WHERE academic_year_id = ? ORDER BY FIELD(term_status, 'Active', 'Inactive', 'Closed', 'Archived'), term_id DESC LIMIT 1", "i", [$ayId]);
        if (!$term) {
            execute_stmt($db, "INSERT INTO term (academic_year_id, term_name, start_date, end_date, term_status) VALUES (?, 'Second Semester', '2026-01-10', '2026-05-30', 'Active')", "i", [$ayId]);
            $termId = (int)$db->insert_id;
            $termName = 'Second Semester';
        } else {
            $termId = (int)$term['term_id'];
            $termName = $term['term_name'];
        }

        execute_stmt($db, "INSERT INTO evaluation_period (term_id, period_name, start_date, end_date, period_status, opened_by_admin_id) VALUES (?, 'Teacher Evaluation', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'Draft', ?)", "ii", [$termId, $admin_id]);
        $periodId = (int)$db->insert_id;
        $period = fetch_one($db, "SELECT ep.*, t.term_name, t.academic_year_id, ay.academic_year_name FROM evaluation_period ep JOIN term t ON t.term_id = ep.term_id JOIN academic_year ay ON ay.academic_year_id = t.academic_year_id WHERE ep.evaluation_period_id = ?", "i", [$periodId]);
    }

    $periodId = (int)$period['evaluation_period_id'];
    $form = fetch_one($db, "SELECT * FROM evaluation_form WHERE evaluation_period_id = ? ORDER BY FIELD(form_status, 'Active', 'Draft', 'Inactive', 'Archived'), evaluation_form_id DESC LIMIT 1", "i", [$periodId]);
    if (!$form) {
        execute_stmt($db, "INSERT INTO evaluation_form (evaluation_period_id, form_title, form_version, form_description, form_status, created_by_admin_id) VALUES (?, 'Faculty Evaluation Form', 1, 'Standard teacher evaluation form for students', 'Draft', ?)", "ii", [$periodId, $admin_id]);
        $formId = (int)$db->insert_id;
        $form = fetch_one($db, "SELECT * FROM evaluation_form WHERE evaluation_form_id = ?", "i", [$formId]);
    }

    $formId = (int)$form['evaluation_form_id'];
    $ratingRows = fetch_all($db, "SELECT rating_value FROM rating_scale_option WHERE evaluation_form_id = ?", "i", [$formId]);
    $existing = array_map(fn($r) => (int)$r['rating_value'], $ratingRows);
    $labels = [1 => 'Strongly Disagree', 2 => 'Disagree', 3 => 'Neutral', 4 => 'Agree', 5 => 'Strongly Agree'];
    for ($i = 1; $i <= 5; $i++) {
        if (!in_array($i, $existing, true)) {
            execute_stmt($db, "INSERT INTO rating_scale_option (evaluation_form_id, rating_value, rating_label, score_value, display_order) VALUES (?, ?, ?, ?, ?)", "iisdi", [$formId, $i, $labels[$i], (float)$i, $i]);
        }
    }

    execute_stmt($db, "INSERT IGNORE INTO evaluation_setup_setting (evaluation_form_id, questions_to_display, updated_by_admin_id) VALUES (?, 5, ?)", "ii", [$formId, $admin_id]);
    $setting = fetch_one($db, "SELECT * FROM evaluation_setup_setting WHERE evaluation_form_id = ?", "i", [$formId]);

    return ["period" => $period, "form" => $form, "setting" => $setting];
}

function get_criteria_records(mysqli $db, int $formId): array
{
    return fetch_all($db, "SELECT
            efc.evaluation_form_category_id,
            efc.evaluation_form_id,
            efc.evaluation_category_id,
            efc.weight_percent,
            efc.display_order,
            efc.form_category_status,
            ec.category_name,
            ec.category_description,
            ec.category_status,
            (
                SELECT COUNT(*)
                FROM evaluation_form_item efi
                WHERE efi.evaluation_form_category_id = efc.evaluation_form_category_id
            ) AS total_questions,
            (
                SELECT COUNT(*)
                FROM evaluation_form_item efi
                JOIN evaluation_item ei ON ei.evaluation_item_id = efi.evaluation_item_id
                WHERE efi.evaluation_form_category_id = efc.evaluation_form_category_id
                  AND efi.form_item_status = 'Active'
                  AND ei.item_status = 'Active'
            ) AS shown_questions
        FROM evaluation_form_category efc
        JOIN evaluation_category ec ON ec.evaluation_category_id = efc.evaluation_category_id
        WHERE efc.evaluation_form_id = ?
        ORDER BY efc.display_order ASC, efc.evaluation_form_category_id ASC", "i", [$formId]);
}

function get_question_records(mysqli $db, int $formId): array
{
    return fetch_all($db, "SELECT
            efi.evaluation_form_item_id,
            efi.evaluation_form_category_id,
            efi.evaluation_item_id,
            efi.statement_text_snapshot,
            efi.display_order,
            efi.form_item_status,
            ei.evaluation_category_id,
            ei.statement_text,
            ei.item_status,
            efc.display_order AS category_order,
            ec.category_name,
            ec.category_description
        FROM evaluation_form_item efi
        JOIN evaluation_form_category efc ON efc.evaluation_form_category_id = efi.evaluation_form_category_id
        JOIN evaluation_category ec ON ec.evaluation_category_id = efc.evaluation_category_id
        LEFT JOIN evaluation_item ei ON ei.evaluation_item_id = efi.evaluation_item_id
        WHERE efc.evaluation_form_id = ?
        ORDER BY efc.display_order ASC, efi.display_order ASC, efi.evaluation_form_item_id ASC", "i", [$formId]);
}

function setup_validation_errors(mysqli $db, int $formId, int $displayCount): array
{
    $errors = [];
    $criteria = fetch_all($db, "SELECT efc.*, ec.category_name FROM evaluation_form_category efc JOIN evaluation_category ec ON ec.evaluation_category_id = efc.evaluation_category_id WHERE efc.evaluation_form_id = ? AND efc.form_category_status = 'Active' AND ec.category_status = 'Active' ORDER BY efc.display_order", "i", [$formId]);
    if (!$criteria) {
        $errors[] = "At least one active evaluation criteria is required.";
    }

    $weight = 0.0;
    foreach ($criteria as $c) {
        $weight += (float)$c['weight_percent'];
    }
    if (abs($weight - 100.0) > 0.01) {
        $errors[] = "Total criteria weight must be exactly 100%. Current total is " . number_format($weight, 2) . "%";
    }

    foreach ($criteria as $c) {
        $total = fetch_one($db, "SELECT COUNT(*) AS c FROM evaluation_form_item efi LEFT JOIN evaluation_item ei ON ei.evaluation_item_id = efi.evaluation_item_id WHERE efi.evaluation_form_category_id = ? AND (ei.item_status IS NULL OR ei.item_status IN ('Active','Inactive'))", "i", [(int)$c['evaluation_form_category_id']]);
        $shown = fetch_one($db, "SELECT COUNT(*) AS c FROM evaluation_form_item efi JOIN evaluation_item ei ON ei.evaluation_item_id = efi.evaluation_item_id WHERE efi.evaluation_form_category_id = ? AND efi.form_item_status = 'Active' AND ei.item_status = 'Active'", "i", [(int)$c['evaluation_form_category_id']]);
        $totalCount = (int)$total['c'];
        $shownCount = (int)$shown['c'];
        if ($totalCount < 5 || $totalCount > 10) {
            $errors[] = $c['category_name'] . " must have 5 to 10 questions. Current total is " . $totalCount . ".";
        }
        if ($shownCount !== $displayCount) {
            $errors[] = $c['category_name'] . " must have exactly " . $displayCount . " shown questions. Current shown questions is " . $shownCount . ".";
        }
    }

    $rating = fetch_one($db, "SELECT COUNT(*) AS c FROM rating_scale_option WHERE evaluation_form_id = ? AND rating_value BETWEEN 1 AND 5", "i", [$formId]);
    if ((int)$rating['c'] < 5) {
        $errors[] = "The 1 to 5 rating scale is incomplete.";
    }
    return $errors;
}

function find_or_create_academic_year(mysqli $db, string $academicYear, string $startDate, string $endDate): int
{
    $academicYear = normalize_academic_year($academicYear);
    if ($academicYear === '') {
        throw new RuntimeException("Academic year is required.");
    }
    $compact = str_replace(' ', '', $academicYear);
    $row = fetch_one($db, "SELECT academic_year_id FROM academic_year WHERE REPLACE(academic_year_name, ' ', '') = ? LIMIT 1", "s", [$compact]);
    if ($row) {
        execute_stmt($db, "UPDATE academic_year SET academic_year_name = ?, academic_year_status = 'Active' WHERE academic_year_id = ?", "si", [$academicYear, (int)$row['academic_year_id']]);
        return (int)$row['academic_year_id'];
    }
    $yearParts = preg_split('/-/', $academicYear);
    $ayStart = $startDate;
    $ayEnd = $endDate;
    if (count($yearParts) === 2) {
        $first = (int)trim($yearParts[0]);
        $second = (int)trim($yearParts[1]);
        if ($first > 1900 && $second > $first) {
            $ayStart = $first . "-08-01";
            $ayEnd = $second . "-07-31";
        }
    }
    execute_stmt($db, "INSERT INTO academic_year (academic_year_name, start_date, end_date, academic_year_status) VALUES (?, ?, ?, 'Active')", "sss", [$academicYear, $ayStart, $ayEnd]);
    return (int)$db->insert_id;
}

function find_or_create_term(mysqli $db, int $academicYearId, string $termName, string $startDate, string $endDate): int
{
    $row = fetch_one($db, "SELECT term_id FROM term WHERE academic_year_id = ? AND term_name = ? LIMIT 1", "is", [$academicYearId, $termName]);
    if ($row) {
        execute_stmt($db, "UPDATE term SET start_date = ?, end_date = ?, term_status = 'Active' WHERE term_id = ?", "ssi", [$startDate, $endDate, (int)$row['term_id']]);
        return (int)$row['term_id'];
    }
    execute_stmt($db, "INSERT INTO term (academic_year_id, term_name, start_date, end_date, term_status) VALUES (?, ?, ?, ?, 'Active')", "isss", [$academicYearId, $termName, $startDate, $endDate]);
    return (int)$db->insert_id;
}

function period_name_for(string $termName): string
{
    return $termName . " Teacher Evaluation";
}

function save_schedule(mysqli $db, int $periodId, int $formId, int $adminId, array $post): void
{
    $semester = term_db_value(trim((string)($post['semester'] ?? '')));
    $academicYear = normalize_academic_year((string)($post['academic_year'] ?? ''));
    $startDate = (string)($post['start_date'] ?? '');
    $endDate = (string)($post['end_date'] ?? '');
    $displayCount = max(5, min(10, (int)($post['display_count'] ?? 5)));

    if ($academicYear === '' || $startDate === '' || $endDate === '') {
        throw new RuntimeException("Please complete the semester, academic year, start date, and end date before saving.");
    }
    if (strtotime($startDate) === false || strtotime($endDate) === false || strtotime($startDate) > strtotime($endDate)) {
        throw new RuntimeException("The schedule dates are invalid. Start date must be before or equal to end date.");
    }

    $academicYearId = find_or_create_academic_year($db, $academicYear, $startDate, $endDate);
    $termId = find_or_create_term($db, $academicYearId, $semester, $startDate, $endDate);
    $periodName = period_name_for($semester);

    execute_stmt($db, "UPDATE evaluation_period SET term_id = ?, period_name = ?, start_date = ?, end_date = ? WHERE evaluation_period_id = ?", "isssi", [$termId, $periodName, $startDate, $endDate, $periodId]);
    execute_stmt($db, "UPDATE evaluation_form SET form_title = 'Faculty Evaluation Form', form_description = 'Standard teacher evaluation form for students' WHERE evaluation_form_id = ?", "i", [$formId]);
    execute_stmt($db, "INSERT INTO evaluation_setup_setting (evaluation_form_id, questions_to_display, updated_by_admin_id) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE questions_to_display = VALUES(questions_to_display), updated_by_admin_id = VALUES(updated_by_admin_id)", "iii", [$formId, $displayCount, $adminId]);
}

function generate_tasks_for_scope(mysqli $db, int $periodId, array $scope): void
{
    if (!db_table_exists($db, 'student_evaluation_task')) {
        return;
    }

    $period = fetch_one($db, "SELECT term_id FROM evaluation_period WHERE evaluation_period_id = ?", "i", [$periodId]);
    if (!$period) {
        return;
    }
    $termId = (int)$period['term_id'];
    $hasTeachingAssignment = db_column_exists($db, 'student_evaluation_task', 'teaching_assignment_id');
    $hasAttempt = db_column_exists($db, 'student_evaluation_task', 'attempt_no');
    $hasActive = db_column_exists($db, 'student_evaluation_task', 'is_active');

    $where = "WHERE sse.term_id = ? AND sse.enrollment_status = 'Active' AND sso.offering_status = 'Active' AND ta.assignment_status = 'Active'";
    $types = "i";
    $params = [$termId];
    if (($scope['scope_type'] ?? 'All') === 'Department' && !empty($scope['department_id'])) {
        $where .= " AND c.department_id = ?";
        $types .= "i";
        $params[] = (int)$scope['department_id'];
    }
    if (($scope['scope_type'] ?? 'All') === 'Course' && !empty($scope['course_id'])) {
        $where .= " AND c.course_id = ?";
        $types .= "i";
        $params[] = (int)$scope['course_id'];
    }
    if (($scope['scope_type'] ?? 'All') === 'Year Level' && !empty($scope['year_level'])) {
        $where .= " AND sec.year_level = ?";
        $types .= "i";
        $params[] = (int)$scope['year_level'];
    }
    if (($scope['scope_type'] ?? 'All') === 'Section' && !empty($scope['section_id'])) {
        $where .= " AND sec.section_id = ?";
        $types .= "i";
        $params[] = (int)$scope['section_id'];
    }

    $rows = fetch_all($db, "SELECT DISTINCT sse.student_id, ta.teaching_assignment_id
        FROM student_section_enrollment sse
        JOIN section sec ON sec.section_id = sse.section_id
        JOIN course c ON c.course_id = sec.course_id
        JOIN section_subject_offering sso ON sso.section_id = sec.section_id AND sso.term_id = sse.term_id
        JOIN teaching_assignment ta ON ta.section_subject_offering_id = sso.section_subject_offering_id AND ta.term_id = sse.term_id
        $where", $types, $params);

    foreach ($rows as $r) {
        $studentId = (int)$r['student_id'];
        $taId = (int)$r['teaching_assignment_id'];
        if ($hasTeachingAssignment) {
            $existsSql = "SELECT student_evaluation_task_id FROM student_evaluation_task WHERE student_id = ? AND teaching_assignment_id = ? AND evaluation_period_id = ?" . ($hasActive ? " AND is_active = 1" : "") . " LIMIT 1";
            $exists = fetch_one($db, $existsSql, "iii", [$studentId, $taId, $periodId]);
            if (!$exists) {
                if ($hasAttempt && $hasActive) {
                    execute_stmt($db, "INSERT INTO student_evaluation_task (student_id, teaching_assignment_id, evaluation_period_id, attempt_no, task_status, is_active) VALUES (?, ?, ?, 1, 'Pending', 1)", "iii", [$studentId, $taId, $periodId]);
                } elseif ($hasAttempt) {
                    execute_stmt($db, "INSERT INTO student_evaluation_task (student_id, teaching_assignment_id, evaluation_period_id, attempt_no, task_status) VALUES (?, ?, ?, 1, 'Pending')", "iii", [$studentId, $taId, $periodId]);
                } elseif ($hasActive) {
                    execute_stmt($db, "INSERT INTO student_evaluation_task (student_id, teaching_assignment_id, evaluation_period_id, task_status, is_active) VALUES (?, ?, ?, 'Pending', 1)", "iii", [$studentId, $taId, $periodId]);
                } else {
                    execute_stmt($db, "INSERT INTO student_evaluation_task (student_id, teaching_assignment_id, evaluation_period_id, task_status) VALUES (?, ?, ?, 'Pending')", "iii", [$studentId, $taId, $periodId]);
                }
            }
        } else {
            $exists = fetch_one($db, "SELECT student_evaluation_task_id FROM student_evaluation_task WHERE student_id = ? AND evaluation_period_id = ? LIMIT 1", "ii", [$studentId, $periodId]);
            if (!$exists) {
                execute_stmt($db, "INSERT INTO student_evaluation_task (student_id, evaluation_period_id, task_status) VALUES (?, ?, 'Pending')", "ii", [$studentId, $periodId]);
            }
        }
    }
}


function update_student_task_access_for_scope(mysqli $db, int $periodId, array $scope, string $accessStatus): void
{
    if (!db_table_exists($db, 'student_evaluation_task') || !db_column_exists($db, 'student_evaluation_task', 'evaluation_access_status')) {
        return;
    }

    $period = fetch_one($db, "SELECT term_id FROM evaluation_period WHERE evaluation_period_id = ? LIMIT 1", "i", [$periodId]);
    if (!$period) {
        return;
    }

    $termId = (int)$period['term_id'];
    $hasTeachingAssignment = db_column_exists($db, 'student_evaluation_task', 'teaching_assignment_id');

    $scopeType = (string)($scope['scope_type'] ?? 'All');
    $where = "sse.term_id = ? AND sse.enrollment_status = 'Active'";
    $types = "i";
    $params = [$termId];

    if ($scopeType === 'Department' && !empty($scope['department_id'])) {
        $where .= " AND c.department_id = ?";
        $types .= "i";
        $params[] = (int)$scope['department_id'];
    } elseif ($scopeType === 'Course' && !empty($scope['course_id'])) {
        $where .= " AND c.course_id = ?";
        $types .= "i";
        $params[] = (int)$scope['course_id'];
    } elseif ($scopeType === 'Year Level' && !empty($scope['year_level'])) {
        $where .= " AND sec.year_level = ?";
        $types .= "i";
        $params[] = (int)$scope['year_level'];
    } elseif ($scopeType === 'Section' && !empty($scope['section_id'])) {
        $where .= " AND sec.section_id = ?";
        $types .= "i";
        $params[] = (int)$scope['section_id'];
    }

    if ($hasTeachingAssignment) {
        $sql = "UPDATE student_evaluation_task setask
                SET setask.evaluation_access_status = ?
                WHERE setask.evaluation_period_id = ?
                  AND setask.task_status <> 'Reset'
                  AND EXISTS (
                      SELECT 1
                      FROM student_section_enrollment sse
                      INNER JOIN section sec
                          ON sec.section_id = sse.section_id
                      INNER JOIN course c
                          ON c.course_id = sec.course_id
                      INNER JOIN section_subject_offering sso
                          ON sso.section_id = sec.section_id
                         AND sso.term_id = sse.term_id
                      INNER JOIN teaching_assignment ta
                          ON ta.section_subject_offering_id = sso.section_subject_offering_id
                         AND ta.term_id = sse.term_id
                      WHERE sse.student_id = setask.student_id
                        AND ta.teaching_assignment_id = setask.teaching_assignment_id
                        AND $where
                  )";
    } else {
        $sql = "UPDATE student_evaluation_task setask
                SET setask.evaluation_access_status = ?
                WHERE setask.evaluation_period_id = ?
                  AND setask.task_status <> 'Reset'
                  AND EXISTS (
                      SELECT 1
                      FROM student_section_enrollment sse
                      INNER JOIN section sec
                          ON sec.section_id = sse.section_id
                      INNER JOIN course c
                          ON c.course_id = sec.course_id
                      WHERE sse.student_id = setask.student_id
                        AND $where
                  )";
    }

    execute_stmt($db, $sql, "si" . $types, array_merge([$accessStatus, $periodId], $params));
}

function sync_student_task_access_for_period(mysqli $db, int $periodId): void
{
    if (!db_table_exists($db, 'student_evaluation_task') || !db_column_exists($db, 'student_evaluation_task', 'evaluation_access_status')) {
        return;
    }

    execute_stmt(
        $db,
        "UPDATE student_evaluation_task
         SET evaluation_access_status = 'Closed'
         WHERE evaluation_period_id = ?
           AND task_status <> 'Reset'",
        "i",
        [$periodId]
    );

    $activeScopes = fetch_all(
        $db,
        "SELECT scope_type, department_id, course_id, year_level, section_id
         FROM evaluation_period_scope
         WHERE evaluation_period_id = ?
           AND scope_status = 'Active'
         ORDER BY evaluation_period_scope_id ASC",
        "i",
        [$periodId]
    );

    foreach ($activeScopes as $activeScope) {
        update_student_task_access_for_scope($db, $periodId, $activeScope, 'Open');
    }
}

function scope_summary(mysqli $db, string $scopeType, ?int $departmentId, ?int $courseId, ?int $yearLevel, ?int $sectionId): string
{
    if ($scopeType === 'All') {
        return "All Colleges, Programs, Year Levels, and Sections";
    }
    if ($scopeType === 'Department' && $departmentId) {
        $row = fetch_one($db, "SELECT department_name FROM department WHERE department_id = ?", "i", [$departmentId]);
        return $row ? $row['department_name'] : "Selected College";
    }
    if ($scopeType === 'Course' && $courseId) {
        $row = fetch_one($db, "SELECT course_code, course_name FROM course WHERE course_id = ?", "i", [$courseId]);
        return $row ? $row['course_code'] . " - " . $row['course_name'] : "Selected Program";
    }
    if ($scopeType === 'Year Level' && $yearLevel) {
        return year_level_label($yearLevel);
    }
    if ($scopeType === 'Section' && $sectionId) {
        $row = fetch_one($db, "SELECT section_name FROM section WHERE section_id = ?", "i", [$sectionId]);
        return $row ? $row['section_name'] : "Selected Section";
    }
    return "Please complete the selection above.";
}

function validate_scope_selection(array $post): array
{
    $scopeType = (string)($post['scope_type'] ?? 'All');
    $departmentId = !empty($post['department_id']) ? (int)$post['department_id'] : null;
    $courseId = !empty($post['course_id']) ? (int)$post['course_id'] : null;
    $yearLevel = !empty($post['year_level']) ? (int)$post['year_level'] : null;
    $sectionId = !empty($post['section_id']) ? (int)$post['section_id'] : null;
    $allowed = ['All', 'Department', 'Course', 'Year Level', 'Section'];
    if (!in_array($scopeType, $allowed, true)) {
        $scopeType = 'All';
    }
    if ($scopeType === 'Department' && !$departmentId) {
        throw new RuntimeException("Please select a College.");
    }
    if ($scopeType === 'Course' && !$courseId) {
        throw new RuntimeException("Please select a Program.");
    }
    if ($scopeType === 'Year Level' && !$yearLevel) {
        throw new RuntimeException("Please select a year level.");
    }
    if ($scopeType === 'Section' && !$sectionId) {
        throw new RuntimeException("Please select a Section.");
    }
    return [
        "scope_type" => $scopeType,
        "department_id" => $scopeType === 'Department' ? $departmentId : ($scopeType === 'Course' ? $departmentId : null),
        "course_id" => in_array($scopeType, ['Course', 'Section'], true) ? $courseId : null,
        "year_level" => in_array($scopeType, ['Year Level', 'Section'], true) ? $yearLevel : null,
        "section_id" => $scopeType === 'Section' ? $sectionId : null,
    ];
}

function build_history_rows(mysqli $db): array
{
    $scopeRows = fetch_all($db, "SELECT eps.*, ep.period_name, ep.start_date, ep.end_date, ep.period_status, t.term_name, ay.academic_year_name,
            d.department_code, d.department_name, c.course_code, c.course_name, sec.section_name
        FROM evaluation_period_scope eps
        JOIN evaluation_period ep ON ep.evaluation_period_id = eps.evaluation_period_id
        JOIN term t ON t.term_id = ep.term_id
        JOIN academic_year ay ON ay.academic_year_id = t.academic_year_id
        LEFT JOIN department d ON d.department_id = eps.department_id
        LEFT JOIN course c ON c.course_id = eps.course_id
        LEFT JOIN section sec ON sec.section_id = eps.section_id
        ORDER BY eps.opened_at DESC, eps.evaluation_period_scope_id DESC");
    if (!$scopeRows) {
        $periodRows = fetch_all($db, "SELECT ep.*, t.term_name, ay.academic_year_name FROM evaluation_period ep JOIN term t ON t.term_id = ep.term_id JOIN academic_year ay ON ay.academic_year_id = t.academic_year_id ORDER BY ep.evaluation_period_id DESC");
        foreach ($periodRows as $r) {
            $scopeRows[] = [
                'evaluation_period_scope_id' => 0,
                'evaluation_period_id' => $r['evaluation_period_id'],
                'scope_type' => 'All',
                'department_id' => null,
                'course_id' => null,
                'year_level' => null,
                'section_id' => null,
                'scope_status' => in_array($r['period_status'], ['Open', 'Ongoing'], true) ? 'Active' : 'Completed',
                'opened_at' => $r['created_at'],
                'closed_at' => in_array($r['period_status'], ['Closed', 'Archived'], true) ? $r['updated_at'] : null,
                'period_name' => $r['period_name'],
                'start_date' => $r['start_date'],
                'end_date' => $r['end_date'],
                'period_status' => $r['period_status'],
                'term_name' => $r['term_name'],
                'academic_year_name' => $r['academic_year_name'],
                'department_name' => null,
                'course_code' => null,
                'course_name' => null,
                'section_name' => null,
            ];
        }
    }
    foreach ($scopeRows as &$r) {
        $r['scope_display'] = scope_applied_to_text($r);
        $r['scope_type_ui'] = scope_ui_label($r['scope_type']);
        $r['semester_ui'] = term_label((string)($r['semester_snapshot'] ?: $r['term_name']));
        $r['academic_year_ui'] = display_academic_year((string)($r['academic_year_snapshot'] ?: $r['academic_year_name']));
        $r['start_date'] = $r['start_date_snapshot'] ?: $r['start_date'];
        $r['end_date'] = $r['end_date_snapshot'] ?: $r['end_date'];
    }
    unset($r);
    return $scopeRows;
}

function scope_applied_to_text(array $row): string
{
    return match ($row['scope_type']) {
        'Department' => $row['department_name'] ?: 'Selected College',
        'Course' => trim(($row['course_code'] ?? '') . ' - ' . ($row['course_name'] ?? ''), ' -') ?: 'Selected Program',
        'Year Level' => year_level_label($row['year_level'] ?? 0),
        'Section' => $row['section_name'] ?: 'Selected Section',
        default => 'All Colleges, Programs, Year Levels, and Sections',
    };
}

function apply_history_filters(array $rows, array $get): array
{
    $academicYear = trim((string)($get['history_academic_year'] ?? ''));
    $semester = trim((string)($get['history_semester'] ?? ''));
    $scopeType = trim((string)($get['history_scope_type'] ?? ''));
    $status = trim((string)($get['history_status'] ?? ''));
    $search = mb_strtolower(trim((string)($get['history_search'] ?? '')));
    return array_values(array_filter($rows, function ($r) use ($academicYear, $semester, $scopeType, $status, $search) {
        if ($academicYear !== '' && $academicYear !== 'All' && $r['academic_year_ui'] !== $academicYear) return false;
        if ($semester !== '' && $semester !== 'All' && $r['semester_ui'] !== $semester) return false;
        if ($scopeType !== '' && $scopeType !== 'All' && $r['scope_type'] !== $scopeType) return false;
        if ($status !== '' && $status !== 'All' && $r['scope_status'] !== $status) return false;
        if ($search !== '') {
            $haystack = mb_strtolower(($r['scope_display'] ?? '') . ' ' . ($r['academic_year_ui'] ?? '') . ' ' . ($r['semester_ui'] ?? '') . ' ' . ($r['scope_type_ui'] ?? ''));
            if (!str_contains($haystack, $search)) return false;
        }
        return true;
    }));
}

$adminId = current_admin_id($db);
ensure_support_tables($db);
$context = current_context($db, $adminId);
$period = $context['period'];
$form = $context['form'];
$setting = $context['setting'];
$periodId = (int)$period['evaluation_period_id'];
$formId = (int)$form['evaluation_form_id'];
$displayCount = max(5, min(10, (int)($setting['questions_to_display'] ?? 5)));

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = (string)($_POST['action'] ?? '');
        $db->begin_transaction();

        if ($action === 'save_setup') {
            save_schedule($db, $periodId, $formId, $adminId, $_POST);
            $displayCount = max(5, min(10, (int)($_POST['display_count'] ?? 5)));
            $errors = setup_validation_errors($db, $formId, $displayCount);
            if ($errors) {
                throw new RuntimeException(implode("\n", $errors));
            }
            execute_stmt($db, "UPDATE evaluation_form SET form_status = CASE WHEN form_status = 'Active' THEN 'Active' ELSE 'Draft' END WHERE evaluation_form_id = ?", "i", [$formId]);
            $db->commit();
            unset($_SESSION["evaluation_setup_flash"]);
            redirect_self(["saved" => 1]);
        }

        if ($action === 'save_schedule_only') {
            save_schedule($db, $periodId, $formId, $adminId, $_POST);
            $db->commit();
            set_flash("Current schedule updated successfully.", "success");
            redirect_self();
        }

        if ($action === 'save_criteria') {
            $categoryName = trim((string)($_POST['category_name'] ?? ''));
            $description = trim((string)($_POST['category_description'] ?? ''));
            $weight = (float)($_POST['weight_percent'] ?? 0);
            $order = max(1, (int)($_POST['display_order'] ?? 1));
            $status = ((string)($_POST['status'] ?? 'Active')) === 'Inactive' ? 'Inactive' : 'Active';
            $formCategoryId = !empty($_POST['evaluation_form_category_id']) ? (int)$_POST['evaluation_form_category_id'] : 0;
            if ($categoryName === '' || $description === '' || $weight <= 0) {
                throw new RuntimeException("Please complete all required criteria fields.");
            }
            if ($formCategoryId > 0) {
                $row = fetch_one($db, "SELECT evaluation_category_id FROM evaluation_form_category WHERE evaluation_form_category_id = ? AND evaluation_form_id = ?", "ii", [$formCategoryId, $formId]);
                if (!$row) throw new RuntimeException("Criteria record was not found.");
                execute_stmt($db, "UPDATE evaluation_category SET category_name = ?, category_description = ?, category_status = ? WHERE evaluation_category_id = ?", "sssi", [$categoryName, $description, $status, (int)$row['evaluation_category_id']]);
                execute_stmt($db, "UPDATE evaluation_form_category SET weight_percent = ?, display_order = ?, form_category_status = ? WHERE evaluation_form_category_id = ?", "disi", [$weight, $order, $status, $formCategoryId]);
                $message = "Criteria updated successfully.";
            } else {
                execute_stmt($db, "INSERT INTO evaluation_category (category_name, category_description, category_status) VALUES (?, ?, ?)", "sss", [$categoryName, $description, $status]);
                $categoryId = (int)$db->insert_id;
                execute_stmt($db, "INSERT INTO evaluation_form_category (evaluation_form_id, evaluation_category_id, weight_percent, display_order, is_required, form_category_status) VALUES (?, ?, ?, ?, 1, ?)", "iidis", [$formId, $categoryId, $weight, $order, $status]);
                $message = "Criteria added successfully.";
            }
            $db->commit();
            set_flash($message, "success");
            redirect_self();
        }

        if ($action === 'delete_criteria') {
            $formCategoryId = (int)($_POST['evaluation_form_category_id'] ?? 0);
            $row = fetch_one($db, "SELECT evaluation_category_id FROM evaluation_form_category WHERE evaluation_form_category_id = ? AND evaluation_form_id = ?", "ii", [$formCategoryId, $formId]);
            if (!$row) throw new RuntimeException("Criteria record was not found.");
            $answerCount = fetch_one($db, "SELECT COUNT(*) AS c FROM evaluation_response_answer era JOIN evaluation_form_item efi ON efi.evaluation_form_item_id = era.evaluation_form_item_id WHERE efi.evaluation_form_category_id = ?", "i", [$formCategoryId]);
            if ((int)$answerCount['c'] > 0) {
                execute_stmt($db, "UPDATE evaluation_form_category SET form_category_status = 'Inactive' WHERE evaluation_form_category_id = ?", "i", [$formCategoryId]);
                execute_stmt($db, "UPDATE evaluation_category SET category_status = 'Inactive' WHERE evaluation_category_id = ?", "i", [(int)$row['evaluation_category_id']]);
                execute_stmt($db, "UPDATE evaluation_form_item SET form_item_status = 'Inactive' WHERE evaluation_form_category_id = ?", "i", [$formCategoryId]);
                $db->commit();
                set_flash("You cannot delete an entity with record inside. This will mark as inactive", "warning");
                redirect_self();
            }
            try {
                execute_stmt($db, "DELETE FROM evaluation_form_item WHERE evaluation_form_category_id = ?", "i", [$formCategoryId]);
                execute_stmt($db, "DELETE FROM evaluation_form_category WHERE evaluation_form_category_id = ?", "i", [$formCategoryId]);
                execute_stmt($db, "DELETE FROM evaluation_category WHERE evaluation_category_id = ?", "i", [(int)$row['evaluation_category_id']]);
                $db->commit();
                set_flash("Criteria deleted successfully.", "success");
                redirect_self();
            } catch (Throwable $t) {
                execute_stmt($db, "UPDATE evaluation_form_category SET form_category_status = 'Inactive' WHERE evaluation_form_category_id = ?", "i", [$formCategoryId]);
                execute_stmt($db, "UPDATE evaluation_category SET category_status = 'Inactive' WHERE evaluation_category_id = ?", "i", [(int)$row['evaluation_category_id']]);
                $db->commit();
                set_flash("You cannot delete an entity with record inside. This will mark as inactive", "warning");
                redirect_self();
            }
        }

        if ($action === 'save_question') {
            $formCategoryId = (int)($_POST['evaluation_form_category_id'] ?? 0);
            $formItemId = !empty($_POST['evaluation_form_item_id']) ? (int)$_POST['evaluation_form_item_id'] : 0;
            $questionText = trim((string)($_POST['statement_text'] ?? ''));
            if ($questionText === '') {
                throw new RuntimeException("Please enter the question text.");
            }
            $fc = fetch_one($db, "SELECT evaluation_category_id FROM evaluation_form_category WHERE evaluation_form_category_id = ? AND evaluation_form_id = ?", "ii", [$formCategoryId, $formId]);
            if (!$fc) throw new RuntimeException("Selected criteria was not found.");
            if ($formItemId > 0) {
                $row = fetch_one($db, "SELECT evaluation_item_id FROM evaluation_form_item WHERE evaluation_form_item_id = ? AND evaluation_form_category_id = ?", "ii", [$formItemId, $formCategoryId]);
                if (!$row) throw new RuntimeException("Question record was not found.");
                execute_stmt($db, "UPDATE evaluation_item SET statement_text = ?, item_status = 'Active' WHERE evaluation_item_id = ?", "si", [$questionText, (int)$row['evaluation_item_id']]);
                execute_stmt($db, "UPDATE evaluation_form_item SET statement_text_snapshot = ? WHERE evaluation_form_item_id = ?", "si", [$questionText, $formItemId]);
                $message = "Question updated successfully.";
            } else {
                $countRow = fetch_one($db, "SELECT COUNT(*) AS c, COALESCE(MAX(display_order), 0) AS max_order FROM evaluation_form_item WHERE evaluation_form_category_id = ?", "i", [$formCategoryId]);
                if ((int)$countRow['c'] >= 10) {
                    throw new RuntimeException("Each criteria can only have a maximum of 10 questions.");
                }
                execute_stmt($db, "INSERT INTO evaluation_item (evaluation_category_id, statement_text, item_status) VALUES (?, ?, 'Active')", "is", [(int)$fc['evaluation_category_id'], $questionText]);
                $itemId = (int)$db->insert_id;
                $nextOrder = (int)$countRow['max_order'] + 1;
                $itemStatus = $nextOrder <= $displayCount ? 'Active' : 'Inactive';
                execute_stmt($db, "INSERT INTO evaluation_form_item (evaluation_form_category_id, evaluation_item_id, statement_text_snapshot, display_order, is_required, form_item_status) VALUES (?, ?, ?, ?, 1, ?)", "iisis", [$formCategoryId, $itemId, $questionText, $nextOrder, $itemStatus]);
                $message = "Question added successfully.";
            }
            $db->commit();
            set_flash($message, "success");
            redirect_self(["questions" => 1]);
        }

        if ($action === 'toggle_question_display') {
            $formItemId = (int)($_POST['evaluation_form_item_id'] ?? 0);
            $row = fetch_one($db, "SELECT form_item_status FROM evaluation_form_item efi JOIN evaluation_form_category efc ON efc.evaluation_form_category_id = efi.evaluation_form_category_id WHERE efi.evaluation_form_item_id = ? AND efc.evaluation_form_id = ?", "ii", [$formItemId, $formId]);
            if (!$row) throw new RuntimeException("Question record was not found.");
            $newStatus = $row['form_item_status'] === 'Active' ? 'Inactive' : 'Active';
            execute_stmt($db, "UPDATE evaluation_form_item SET form_item_status = ? WHERE evaluation_form_item_id = ?", "si", [$newStatus, $formItemId]);
            $db->commit();
            set_flash($newStatus === 'Active' ? "Question is now shown." : "Question is now hidden.", "success");
            redirect_self(["questions" => 1]);
        }

        if ($action === 'delete_question') {
            $formItemId = (int)($_POST['evaluation_form_item_id'] ?? 0);
            $row = fetch_one($db, "SELECT efi.evaluation_item_id FROM evaluation_form_item efi JOIN evaluation_form_category efc ON efc.evaluation_form_category_id = efi.evaluation_form_category_id WHERE efi.evaluation_form_item_id = ? AND efc.evaluation_form_id = ?", "ii", [$formItemId, $formId]);
            if (!$row) throw new RuntimeException("Question record was not found.");
            $answerCount = fetch_one($db, "SELECT COUNT(*) AS c FROM evaluation_response_answer WHERE evaluation_form_item_id = ?", "i", [$formItemId]);
            if ((int)$answerCount['c'] > 0) {
                execute_stmt($db, "UPDATE evaluation_form_item SET form_item_status = 'Inactive' WHERE evaluation_form_item_id = ?", "i", [$formItemId]);
                execute_stmt($db, "UPDATE evaluation_item SET item_status = 'Inactive' WHERE evaluation_item_id = ?", "i", [(int)$row['evaluation_item_id']]);
                $db->commit();
                set_flash("You cannot delete an entity with record inside. This will mark as inactive", "warning");
                redirect_self(["questions" => 1]);
            }
            try {
                execute_stmt($db, "DELETE FROM evaluation_form_item WHERE evaluation_form_item_id = ?", "i", [$formItemId]);
                execute_stmt($db, "DELETE FROM evaluation_item WHERE evaluation_item_id = ?", "i", [(int)$row['evaluation_item_id']]);
                $db->commit();
                set_flash("Question deleted successfully.", "success");
                redirect_self(["questions" => 1]);
            } catch (Throwable $t) {
                execute_stmt($db, "UPDATE evaluation_form_item SET form_item_status = 'Inactive' WHERE evaluation_form_item_id = ?", "i", [$formItemId]);
                execute_stmt($db, "UPDATE evaluation_item SET item_status = 'Inactive' WHERE evaluation_item_id = ?", "i", [(int)$row['evaluation_item_id']]);
                $db->commit();
                set_flash("You cannot delete an entity with record inside. This will mark as inactive", "warning");
                redirect_self(["questions" => 1]);
            }
        }

        if ($action === 'open_evaluation' || $action === 'close_evaluation') {
            save_schedule($db, $periodId, $formId, $adminId, $_POST);
            $displayCount = max(5, min(10, (int)($_POST['display_count'] ?? 5)));
            $scope = validate_scope_selection($_POST);
            if ($action === 'open_evaluation') {
                $errors = setup_validation_errors($db, $formId, $displayCount);
                if ($errors) {
                    throw new RuntimeException(implode("\n", $errors));
                }
                execute_stmt($db, "UPDATE evaluation_period SET period_status = 'Open', opened_by_admin_id = ? WHERE evaluation_period_id = ?", "ii", [$adminId, $periodId]);
                execute_stmt($db, "UPDATE evaluation_form SET form_status = 'Active' WHERE evaluation_form_id = ?", "i", [$formId]);

                // Opening Select All covers every student, so all other active scopes for the same period must be completed first.
                // Opening a specific scope should not close other active specific scopes. It only closes an active All scope
                // and the exact same active scope to avoid duplicate active records for the same target.
                if ($scope['scope_type'] === 'All') {
                    execute_stmt(
                        $db,
                        "UPDATE evaluation_period_scope
                         SET scope_status = 'Completed',
                             closed_by_admin_id = ?,
                             closed_at = COALESCE(closed_at, NOW())
                         WHERE evaluation_period_id = ?
                           AND scope_status = 'Active'",
                        "ii",
                        [$adminId, $periodId]
                    );
                } else {
                    execute_stmt(
                        $db,
                        "UPDATE evaluation_period_scope
                         SET scope_status = 'Completed',
                             closed_by_admin_id = ?,
                             closed_at = COALESCE(closed_at, NOW())
                         WHERE evaluation_period_id = ?
                           AND scope_status = 'Active'
                           AND scope_type = 'All'",
                        "ii",
                        [$adminId, $periodId]
                    );

                    $duplicateWhere = "evaluation_period_id = ? AND scope_status = 'Active' AND scope_type = ?";
                    $duplicateTypes = "is";
                    $duplicateParams = [$periodId, $scope['scope_type']];

                    if ($scope['scope_type'] === 'Department') {
                        $duplicateWhere .= " AND department_id = ?";
                        $duplicateTypes .= "i";
                        $duplicateParams[] = (int)$scope['department_id'];
                    } elseif ($scope['scope_type'] === 'Course') {
                        $duplicateWhere .= " AND course_id = ?";
                        $duplicateTypes .= "i";
                        $duplicateParams[] = (int)$scope['course_id'];
                    } elseif ($scope['scope_type'] === 'Year Level') {
                        $duplicateWhere .= " AND year_level = ?";
                        $duplicateTypes .= "i";
                        $duplicateParams[] = (int)$scope['year_level'];
                    } elseif ($scope['scope_type'] === 'Section') {
                        $duplicateWhere .= " AND section_id = ?";
                        $duplicateTypes .= "i";
                        $duplicateParams[] = (int)$scope['section_id'];
                    }

                    execute_stmt(
                        $db,
                        "UPDATE evaluation_period_scope
                         SET scope_status = 'Completed',
                             closed_by_admin_id = ?,
                             closed_at = COALESCE(closed_at, NOW())
                         WHERE $duplicateWhere",
                        "i" . $duplicateTypes,
                        array_merge([$adminId], $duplicateParams)
                    );
                }

                $snapshotPeriod = fetch_one($db, "
                    SELECT
                        ay.academic_year_name,
                        t.term_name,
                        ep.start_date,
                        ep.end_date
                    FROM evaluation_period ep
                    INNER JOIN term t
                        ON t.term_id = ep.term_id
                    INNER JOIN academic_year ay
                        ON ay.academic_year_id = t.academic_year_id
                    WHERE ep.evaluation_period_id = ?
                    LIMIT 1
                ", "i", [$periodId]);

                execute_stmt(
                    $db,
                    "INSERT INTO evaluation_period_scope
                        (evaluation_period_id, academic_year_snapshot, semester_snapshot, start_date_snapshot, end_date_snapshot, scope_type, department_id, course_id, year_level, section_id, scope_status, opened_by_admin_id, opened_at)
                     VALUES
                        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', ?, NOW())",
                    "isssssiiiii",
                    [
                        $periodId,
                        $snapshotPeriod['academic_year_name'] ?? '',
                        $snapshotPeriod['term_name'] ?? '',
                        $snapshotPeriod['start_date'] ?? null,
                        $snapshotPeriod['end_date'] ?? null,
                        $scope['scope_type'],
                        $scope['department_id'],
                        $scope['course_id'],
                        $scope['year_level'],
                        $scope['section_id'],
                        $adminId
                    ]
                );
                generate_tasks_for_scope($db, $periodId, $scope);
                sync_student_task_access_for_period($db, $periodId);
                $db->commit();
                set_flash("Evaluation opened successfully for " . scope_summary($db, $scope['scope_type'], $scope['department_id'], $scope['course_id'], $scope['year_level'], $scope['section_id']) . ".", "success");
                redirect_self();
            } else {
                if ($scope['scope_type'] === 'All') {
                    execute_stmt(
                        $db,
                        "UPDATE evaluation_period_scope
                         SET scope_status = 'Completed',
                             closed_by_admin_id = ?,
                             closed_at = COALESCE(closed_at, NOW())
                         WHERE evaluation_period_id = ?
                           AND scope_status = 'Active'",
                        "ii",
                        [$adminId, $periodId]
                    );
                } else {
                    $closeWhere = "evaluation_period_id = ? AND scope_status = 'Active' AND scope_type = ?";
                    $closeTypes = "is";
                    $closeParams = [$periodId, $scope['scope_type']];

                    if ($scope['scope_type'] === 'Department') {
                        $closeWhere .= " AND department_id = ?";
                        $closeTypes .= "i";
                        $closeParams[] = (int)$scope['department_id'];
                    } elseif ($scope['scope_type'] === 'Course') {
                        $closeWhere .= " AND course_id = ?";
                        $closeTypes .= "i";
                        $closeParams[] = (int)$scope['course_id'];
                    } elseif ($scope['scope_type'] === 'Year Level') {
                        $closeWhere .= " AND year_level = ?";
                        $closeTypes .= "i";
                        $closeParams[] = (int)$scope['year_level'];
                    } elseif ($scope['scope_type'] === 'Section') {
                        $closeWhere .= " AND section_id = ?";
                        $closeTypes .= "i";
                        $closeParams[] = (int)$scope['section_id'];
                    }

                    execute_stmt(
                        $db,
                        "UPDATE evaluation_period_scope
                         SET scope_status = 'Completed',
                             closed_by_admin_id = ?,
                             closed_at = COALESCE(closed_at, NOW())
                         WHERE $closeWhere",
                        "i" . $closeTypes,
                        array_merge([$adminId], $closeParams)
                    );
                }

                $active = fetch_one($db, "SELECT COUNT(*) AS c FROM evaluation_period_scope WHERE evaluation_period_id = ? AND scope_status = 'Active'", "i", [$periodId]);
                if ((int)$active['c'] === 0 || $scope['scope_type'] === 'All') {
                    execute_stmt($db, "UPDATE evaluation_period SET period_status = 'Closed', closed_by_admin_id = ? WHERE evaluation_period_id = ?", "ii", [$adminId, $periodId]);
                    execute_stmt($db, "UPDATE evaluation_form SET form_status = 'Inactive' WHERE evaluation_form_id = ?", "i", [$formId]);
                } else {
                    execute_stmt($db, "UPDATE evaluation_period SET period_status = 'Open', opened_by_admin_id = ? WHERE evaluation_period_id = ?", "ii", [$adminId, $periodId]);
                    execute_stmt($db, "UPDATE evaluation_form SET form_status = 'Active' WHERE evaluation_form_id = ?", "i", [$formId]);
                }

                sync_student_task_access_for_period($db, $periodId);
                $db->commit();
                set_flash("Evaluation closed successfully for " . scope_summary($db, $scope['scope_type'], $scope['department_id'], $scope['course_id'], $scope['year_level'], $scope['section_id']) . ".", "success");
                redirect_self();
            }
        }

        if ($action === 'archive_scope') {
            $scopeId = (int)($_POST['evaluation_period_scope_id'] ?? 0);
            if ($scopeId > 0) {
                $archivedScope = fetch_one($db, "SELECT evaluation_period_id FROM evaluation_period_scope WHERE evaluation_period_scope_id = ? LIMIT 1", "i", [$scopeId]);
                execute_stmt($db, "UPDATE evaluation_period_scope SET scope_status = 'Archived' WHERE evaluation_period_scope_id = ?", "i", [$scopeId]);
                if ($archivedScope) {
                    sync_student_task_access_for_period($db, (int)$archivedScope['evaluation_period_id']);
                }
            }
            $db->commit();
            set_flash("Evaluation period history archived successfully.", "success");
            redirect_self(["history" => 1]);
        }

        throw new RuntimeException("Invalid action.");
    }
} catch (Throwable $ex) {
    if ($db->errno || $db->thread_id) {
        try { $db->rollback(); } catch (Throwable $ignored) {}
    }
    set_flash($ex->getMessage(), "error");
    redirect_self(["error" => 1]);
}

$context = current_context($db, $adminId);
$period = $context['period'];
$form = $context['form'];
$setting = $context['setting'];
$periodId = (int)$period['evaluation_period_id'];
$formId = (int)$form['evaluation_form_id'];
$displayCount = max(5, min(10, (int)($setting['questions_to_display'] ?? 5)));
$criteriaRecords = get_criteria_records($db, $formId);
$questionRecords = get_question_records($db, $formId);
$departments = fetch_all($db, "SELECT department_id, department_code, department_name FROM department WHERE department_status = 'Active' ORDER BY department_name");
$courses = fetch_all($db, "SELECT course_id, department_id, course_code, course_name, number_of_year_level FROM course WHERE course_status = 'Active' ORDER BY course_code");
$sections = fetch_all($db, "SELECT section_id, course_id, year_level, section_name FROM section WHERE section_status = 'Active' ORDER BY section_name");
$ratingOptions = fetch_all($db, "SELECT * FROM rating_scale_option WHERE evaluation_form_id = ? ORDER BY display_order", "i", [$formId]);
$allHistoryRows = build_history_rows($db);
$historyRows = apply_history_filters($allHistoryRows, $_GET);

if (isset($_GET['export_history'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=evaluation_history.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Academic Year', 'Semester', 'Scope Type', 'Applied To', 'Start Date', 'End Date', 'Date Opened', 'Date Closed', 'Status']);
    foreach ($historyRows as $row) {
        fputcsv($out, [$row['academic_year_ui'], $row['semester_ui'], $row['scope_type_ui'], $row['scope_display'], $row['start_date'], $row['end_date'], $row['opened_at'], $row['closed_at'] ?: '', $row['scope_status']]);
    }
    fclose($out);
    exit;
}

$questionsByCategory = [];
foreach ($questionRecords as $q) {
    $questionsByCategory[(int)$q['evaluation_form_category_id']][] = $q;
}
$activeScopeRows = fetch_all($db, "SELECT eps.*, d.department_name, c.course_code, c.course_name, sec.section_name
    FROM evaluation_period_scope eps
    LEFT JOIN department d ON d.department_id = eps.department_id
    LEFT JOIN course c ON c.course_id = eps.course_id
    LEFT JOIN section sec ON sec.section_id = eps.section_id
    WHERE eps.evaluation_period_id = ? AND eps.scope_status = 'Active'
    ORDER BY eps.opened_at DESC, eps.evaluation_period_scope_id DESC", "i", [$periodId]);
if ($activeScopeRows) {
    $currentActiveScopeText = implode(', ', array_map(fn($row) => scope_applied_to_text($row), $activeScopeRows));
} else {
    $currentActiveScopeText = in_array($period['period_status'], ['Open', 'Ongoing'], true) ? 'All Colleges, Programs, Year Levels, and Sections' : 'No active scope';
}
$evaluationIsOpen = in_array($period['period_status'], ['Open', 'Ongoing'], true) && count($activeScopeRows) > 0;
$flash = get_flash();
$historyStats = [
    'total' => count($allHistoryRows),
    'active' => count(array_filter($allHistoryRows, fn($r) => $r['scope_status'] === 'Active')),
    'completed' => count(array_filter($allHistoryRows, fn($r) => $r['scope_status'] === 'Completed')),
    'hybrid' => count($allHistoryRows),
];
$historyYears = array_values(array_unique(array_map(fn($r) => $r['academic_year_ui'], $allHistoryRows)));
$historySemesters = array_values(array_unique(array_map(fn($r) => $r['semester_ui'], $allHistoryRows)));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluation Setup</title>
    <link rel="stylesheet" href="evaluation-setup.css?v=<?php echo time(); ?>">
</head>
<body class="evaluation-setup-page">
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
            <a class="nav-link active" href="evaluation_setup.php"><?php echo icon_svg("settings"); ?> Evaluation Setup</a>
            <a class="nav-link" href="submission_monitoring.php"><?php echo icon_svg("clipboard"); ?> Submission Monitoring</a>
            <a class="nav-link" href="report_page.php"><?php echo icon_svg("reports"); ?> Reports</a>
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
                    <h1>Evaluation Setup</h1>
                    <p>Configure evaluation criteria, schedule, and settings</p>
                </div>
                <span class="status-pill <?php echo $evaluationIsOpen ? 'open' : 'closed'; ?>" id="evaluationStatusBadge">
                    <span></span>
                    Evaluation <?php echo $evaluationIsOpen ? 'Open' : 'Closed'; ?>
                </span>
            </div>

            <section class="setup-card">
                <div class="section-title">
                    <?php echo icon_svg("calendar"); ?>
                    <h2>Current Schedule</h2>
                </div>
                <div class="schedule-grid">
                    <div>
                        <label>Semester</label>
                        <select id="semesterSelect">
                            <?php foreach (['First Semester' => '1st Semester', 'Second Semester' => '2nd Semester', 'Summer' => 'Summer'] as $value => $label): ?>
                                <option value="<?php echo e($value); ?>" <?php echo $period['term_name'] === $value ? 'selected' : ''; ?>><?php echo e($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Academic Year</label>
                        <input type="text" id="academicYearInput" value="<?php echo e(display_academic_year($period['academic_year_name'])); ?>">
                    </div>
                    <div>
                        <label>Start Date</label>
                        <input type="date" id="startDateInput" value="<?php echo e($period['start_date']); ?>">
                    </div>
                    <div>
                        <label>End Date</label>
                        <input type="date" id="endDateInput" value="<?php echo e($period['end_date']); ?>">
                    </div>
                </div>
            </section>

            <section class="setup-card">
                <div class="card-header-row">
                    <div class="section-title">
                        <?php echo icon_svg("settings"); ?>
                        <h2>Evaluation Criteria</h2>
                    </div>
                    <button class="dark-button compact-button" type="button" onclick="openCriteriaModal('add')">
                        <?php echo icon_svg("plus"); ?> Add Criteria
                    </button>
                </div>
                <div class="table-wrap">
                    <table class="criteria-table">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Criteria Name</th>
                                <th>Weight (%)</th>
                                <th>Status</th>
                                <th class="actions-head">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$criteriaRecords): ?>
                                <tr><td colspan="5" class="empty-row">No evaluation criteria found.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($criteriaRecords as $criteria): ?>
                                <tr>
                                    <td><?php echo e($criteria['display_order']); ?></td>
                                    <td><strong><?php echo e($criteria['category_name']); ?></strong></td>
                                    <td><?php echo e(rtrim(rtrim(number_format((float)$criteria['weight_percent'], 2), '0'), '.')); ?>%</td>
                                    <td><span class="status-badge <?php echo strtolower($criteria['form_category_status']); ?>"><?php echo e(strtolower($criteria['form_category_status'])); ?></span></td>
                                    <td>
                                        <div class="action-icons">
                                            <button class="view" type="button" title="View" onclick="openCriteriaModal('view', <?php echo (int)$criteria['evaluation_form_category_id']; ?>)"><?php echo icon_svg("eye"); ?></button>
                                            <button class="edit" type="button" title="Edit" onclick="openCriteriaModal('edit', <?php echo (int)$criteria['evaluation_form_category_id']; ?>)"><?php echo icon_svg("edit"); ?></button>
                                            <form method="post" onsubmit="return confirm('Delete this criteria?');">
                                                <input type="hidden" name="action" value="delete_criteria">
                                                <input type="hidden" name="evaluation_form_category_id" value="<?php echo (int)$criteria['evaluation_form_category_id']; ?>">
                                                <button class="delete" type="submit" title="Delete"><?php echo icon_svg("trash"); ?></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="setup-card questions-card" id="questionsSection">
                <div class="questions-top">
                    <div class="section-title">
                        <?php echo icon_svg("help"); ?>
                        <h2>Evaluation Questions</h2>
                    </div>
                    <div class="display-control">
                        <label for="displayCountSelect">Questions to display per criteria:</label>
                        <select id="displayCountSelect">
                            <?php for ($i = 5; $i <= 10; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $displayCount === $i ? 'selected' : ''; ?>><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <p class="questions-note">Each criteria must have a minimum of 5 and a maximum of 10 questions. Selected display count: <strong><?php echo $displayCount; ?></strong> questions per criteria will be shown to students.</p>
                <?php foreach ($criteriaRecords as $criteria): ?>
                    <?php $questions = $questionsByCategory[(int)$criteria['evaluation_form_category_id']] ?? []; ?>
                    <div class="question-group">
                        <div class="question-group-header">
                            <div class="question-group-title">
                                <span class="criteria-order-box"><?php echo e($criteria['display_order']); ?></span>
                                <div>
                                    <h3><?php echo e($criteria['category_name']); ?></h3>
                                    <p><?php echo e(rtrim(rtrim(number_format((float)$criteria['weight_percent'], 2), '0'), '.')); ?>% weight</p>
                                </div>
                            </div>
                            <div class="question-group-actions">
                                <span class="question-count-badge"><?php echo (int)$criteria['shown_questions']; ?>/<?php echo (int)$criteria['total_questions']; ?> questions</span>
                                <button class="dark-button mini-button" type="button" onclick="openQuestionModal('add', <?php echo (int)$criteria['evaluation_form_category_id']; ?>)"><?php echo icon_svg("plus"); ?> Add Question</button>
                            </div>
                        </div>
                        <div class="table-wrap">
                            <table class="question-table">
                                <thead><tr><th>No.</th><th>Question</th><th>Display</th><th>Actions</th></tr></thead>
                                <tbody>
                                <?php if (!$questions): ?>
                                    <tr><td colspan="4" class="empty-row">No questions found for this criteria.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($questions as $qIndex => $question): ?>
                                    <?php $shown = $question['form_item_status'] === 'Active' && $question['item_status'] === 'Active'; ?>
                                    <tr>
                                        <td><?php echo $qIndex + 1; ?></td>
                                        <td><?php echo e($question['statement_text_snapshot']); ?></td>
                                        <td>
                                            <form method="post" class="display-form">
                                                <input type="hidden" name="action" value="toggle_question_display">
                                                <input type="hidden" name="evaluation_form_item_id" value="<?php echo (int)$question['evaluation_form_item_id']; ?>">
                                                <button type="submit" class="display-badge <?php echo $shown ? 'shown' : 'hidden'; ?>">
                                                    <input type="checkbox" <?php echo $shown ? 'checked' : ''; ?> tabindex="-1" readonly>
                                                    <?php echo $shown ? 'Shown' : 'Hidden'; ?>
                                                </button>
                                            </form>
                                        </td>
                                        <td>
                                            <div class="action-icons small-icons">
                                                <button class="edit" type="button" title="Edit" onclick="openQuestionModal('edit', <?php echo (int)$criteria['evaluation_form_category_id']; ?>, <?php echo (int)$question['evaluation_form_item_id']; ?>)"><?php echo icon_svg("edit"); ?></button>
                                                <form method="post" onsubmit="return confirm('Delete this question?');">
                                                    <input type="hidden" name="action" value="delete_question">
                                                    <input type="hidden" name="evaluation_form_item_id" value="<?php echo (int)$question['evaluation_form_item_id']; ?>">
                                                    <button class="delete" type="submit" title="Delete"><?php echo icon_svg("trash"); ?></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            </section>

            <section class="setup-card rating-card">
                <h2>Rating Scale</h2>
                <p>Current scale: 1 to 5 (Likert Scale)</p>
                <div class="rating-scale-list">
                    <?php foreach ($ratingOptions as $rate): ?>
                        <span><?php echo (int)$rate['rating_value']; ?> - <?php echo e($rate['rating_label']); ?></span>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="scope-card">
                <strong>Current Active Scope:</strong>
                <span id="currentActiveScopeText"><?php echo e($currentActiveScopeText); ?></span>
            </section>

            <section class="bottom-actions">
                <button class="blue-button" type="button" onclick="openPreviewModal()"><?php echo icon_svg("eye"); ?> Preview Form</button>
                <button class="dark-button" type="button" onclick="submitSetupAction('save_setup')"><?php echo icon_svg("save"); ?> Save Setup</button>
                <button class="green-button" type="button" onclick="openScopeModal('open')"><?php echo icon_svg("play"); ?> Open Evaluation</button>
                <button class="red-button" type="button" onclick="openScopeModal('close')"><?php echo icon_svg("stop"); ?> Close Evaluation</button>
            </section>

            <section class="setup-card history-card" id="historySection">
                <div class="card-header-row history-title-row">
                    <div class="section-title">
                        <?php echo icon_svg("history"); ?>
                        <div>
                            <h2>Evaluation History</h2>
                            <p>View past and active evaluation periods by scope.</p>
                        </div>
                    </div>
                </div>
                <div class="history-stats-grid">
                    <div class="history-stat"><span>Total Records</span><strong><?php echo $historyStats['total']; ?></strong><i class="stat-icon-blue"><?php echo icon_svg('clipboard'); ?></i></div>
                    <div class="history-stat"><span>Active Periods</span><strong class="green-number"><?php echo $historyStats['active']; ?></strong><i class="stat-icon-green"><?php echo icon_svg('play'); ?></i></div>
                    <div class="history-stat"><span>Completed Periods</span><strong><?php echo $historyStats['completed']; ?></strong><i><?php echo icon_svg('check'); ?></i></div>
                    <div class="history-stat"><span>Hybrid Scopes Used</span><strong class="purple-number"><?php echo $historyStats['hybrid']; ?></strong><i class="stat-icon-purple"><?php echo icon_svg('settings'); ?></i></div>
                </div>
                <form method="get" class="history-filter-form">
                    <div>
                        <label>Academic Year</label>
                        <select name="history_academic_year">
                            <option value="All">All</option>
                            <?php foreach ($historyYears as $year): ?>
                                <option value="<?php echo e($year); ?>" <?php echo (($_GET['history_academic_year'] ?? '') === $year) ? 'selected' : ''; ?>><?php echo e($year); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Semester</label>
                        <select name="history_semester">
                            <option value="All">All</option>
                            <?php foreach ($historySemesters as $sem): ?>
                                <option value="<?php echo e($sem); ?>" <?php echo (($_GET['history_semester'] ?? '') === $sem) ? 'selected' : ''; ?>><?php echo e($sem); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Scope Type</label>
                        <select name="history_scope_type">
                            <option value="All">All Scopes</option>
                            <?php foreach (['All' => 'All', 'Department' => 'College', 'Course' => 'Program', 'Year Level' => 'Year Level', 'Section' => 'Section'] as $value => $label): ?>
                                <option value="<?php echo e($value); ?>" <?php echo (($_GET['history_scope_type'] ?? '') === $value) ? 'selected' : ''; ?>><?php echo e($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Status</label>
                        <select name="history_status">
                            <option value="All">All</option>
                            <?php foreach (['Active', 'Completed', 'Archived'] as $status): ?>
                                <option value="<?php echo e($status); ?>" <?php echo (($_GET['history_status'] ?? '') === $status) ? 'selected' : ''; ?>><?php echo e($status); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="history-search-row">
                        <div class="history-search-box"><?php echo icon_svg('filter'); ?><input type="text" name="history_search" placeholder="Search by applied to..." value="<?php echo e($_GET['history_search'] ?? ''); ?>"></div>
                        <button class="secondary-button compact-button" type="submit">Filter</button>
                        <button class="secondary-button compact-button" type="submit" name="export_history" value="1"><?php echo icon_svg('download'); ?> Export</button>
                    </div>
                </form>
                <div class="table-wrap history-table-wrap">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Academic Year</th><th>Semester</th><th>Scope Type</th><th>Applied To</th><th>Start Date</th><th>End Date</th><th>Date Opened</th><th>Date Closed</th><th>Status</th><th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$historyRows): ?>
                                <tr><td colspan="10" class="empty-row">No evaluation history records found.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($historyRows as $row): ?>
                                <tr>
                                    <td><strong><?php echo e($row['academic_year_ui']); ?></strong></td>
                                    <td><?php echo e($row['semester_ui']); ?></td>
                                    <td><span class="scope-badge scope-<?php echo strtolower(str_replace(' ', '-', $row['scope_type_ui'])); ?>"><?php echo e($row['scope_type_ui']); ?></span></td>
                                    <td><?php echo e($row['scope_display']); ?></td>
                                    <td><?php echo e(date('m/d/Y', strtotime($row['start_date']))); ?></td>
                                    <td><?php echo e(date('m/d/Y', strtotime($row['end_date']))); ?></td>
                                    <td><?php echo $row['opened_at'] ? e(date('m/d/Y', strtotime($row['opened_at']))) : '—'; ?></td>
                                    <td><?php echo $row['closed_at'] ? e(date('m/d/Y', strtotime($row['closed_at']))) : '—'; ?></td>
                                    <td><span class="history-status <?php echo strtolower($row['scope_status']); ?>"><span></span><?php echo e($row['scope_status']); ?></span></td>
                                    <td>
                                        <div class="action-icons small-icons">
                                            <button class="view" type="button" onclick="openPeriodDetails(<?php echo (int)$row['evaluation_period_scope_id']; ?>, <?php echo (int)$row['evaluation_period_id']; ?>)"><?php echo icon_svg('eye'); ?></button>
                                            <?php if ((int)$row['evaluation_period_scope_id'] > 0): ?>
                                            <form method="post" onsubmit="return confirm('Archive this history record?');">
                                                <input type="hidden" name="action" value="archive_scope">
                                                <input type="hidden" name="evaluation_period_scope_id" value="<?php echo (int)$row['evaluation_period_scope_id']; ?>">
                                                <button class="archive" type="submit"><?php echo icon_svg('archive'); ?></button>
                                            </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="history-footer"><span>Showing <?php echo count($historyRows); ?> of <?php echo count($allHistoryRows); ?> records</span><div class="pagination-box"><button disabled>‹</button><strong>1</strong><button disabled>›</button></div></div>
            </section>
        </div>
    </main>

    <form method="post" id="setupActionForm" class="hidden-form">
        <input type="hidden" name="action" id="setupActionInput">
        <input type="hidden" name="semester" id="setupSemesterInput">
        <input type="hidden" name="academic_year" id="setupAcademicYearInput">
        <input type="hidden" name="start_date" id="setupStartDateInput">
        <input type="hidden" name="end_date" id="setupEndDateInput">
        <input type="hidden" name="display_count" id="setupDisplayCountInput">
    </form>

    <div class="modal-overlay hidden" id="criteriaModal">
        <div class="modal-box criteria-form-modal">
            <form method="post" id="criteriaForm">
                <input type="hidden" name="action" value="save_criteria">
                <input type="hidden" name="evaluation_form_category_id" id="criteriaFormCategoryId">
                <div class="modal-header"><h2 id="criteriaModalTitle">Add Evaluation Criteria</h2><button type="button" class="modal-close" onclick="closeModal('criteriaModal')"><?php echo icon_svg('close'); ?></button></div>
                <div class="modal-body" id="criteriaFormBody">
                    <label>Criteria Name <span>*</span></label>
                    <input type="text" name="category_name" id="criteriaNameInput" placeholder="e.g., Teaching Effectiveness" required>
                    <label>Description <span>*</span></label>
                    <textarea name="category_description" id="criteriaDescriptionInput" placeholder="Describe what this criterion evaluates..." required></textarea>
                    <div class="two-column">
                        <div><label>Weight (%) <span>*</span></label><input type="number" name="weight_percent" id="criteriaWeightInput" min="1" max="100" step="0.01" required><small>Current total: <?php echo e(number_format(array_sum(array_map(fn($c) => (float)$c['weight_percent'], $criteriaRecords)), 2)); ?>%</small></div>
                        <div><label>Display Order <span>*</span></label><input type="number" name="display_order" id="criteriaOrderInput" min="1" required></div>
                    </div>
                    <label>Status <span>*</span></label>
                    <select name="status" id="criteriaStatusInput"><option value="Active">Active</option><option value="Inactive">Inactive</option></select>
                </div>
                <div class="modal-body hidden" id="criteriaViewBody">
                    <div class="details-grid">
                        <div class="detail-full"><span>Criteria Name</span><strong id="viewCriteriaName"></strong></div>
                        <div class="detail-full"><span>Description</span><p id="viewCriteriaDescription"></p></div>
                        <div><span>Weight</span><strong id="viewCriteriaWeight"></strong></div>
                        <div><span>Display Order</span><strong id="viewCriteriaOrder"></strong></div>
                        <div><span>Status</span><strong id="viewCriteriaStatus"></strong></div>
                    </div>
                </div>
                <div class="modal-footer" id="criteriaFormFooter"><button type="button" class="secondary-button" onclick="closeModal('criteriaModal')">Cancel</button><button type="submit" class="dark-button" id="criteriaSubmitButton">Add Criteria</button></div>
                <div class="modal-footer hidden" id="criteriaViewFooter"><button type="button" class="secondary-button" onclick="closeModal('criteriaModal')">Close</button><button type="button" class="dark-button" id="criteriaEditFromViewButton">Edit</button></div>
            </form>
        </div>
    </div>

    <div class="modal-overlay hidden" id="questionModal">
        <div class="modal-box question-form-modal">
            <form method="post" id="questionForm">
                <input type="hidden" name="action" value="save_question">
                <input type="hidden" name="evaluation_form_category_id" id="questionFormCategoryId">
                <input type="hidden" name="evaluation_form_item_id" id="questionFormItemId">
                <div class="modal-header"><h2 id="questionModalTitle">Add Question</h2><button type="button" class="modal-close" onclick="closeModal('questionModal')"><?php echo icon_svg('close'); ?></button></div>
                <div class="modal-body">
                    <p class="criteria-label">Criteria: <strong id="questionCriteriaLabel"></strong></p>
                    <label>Question Text <span>*</span></label>
                    <textarea name="statement_text" id="questionTextInput" placeholder="e.g., The instructor explains the lessons clearly." required></textarea>
                </div>
                <div class="modal-footer"><button type="button" class="secondary-button" onclick="closeModal('questionModal')">Cancel</button><button type="submit" class="dark-button" id="questionSubmitButton">Add Question</button></div>
            </form>
        </div>
    </div>

    <div class="modal-overlay hidden" id="previewModal">
        <div class="modal-box preview-modal">
            <div class="modal-header preview-header"><div><h2>Student Evaluation Form — Preview</h2><p><?php echo e(term_label($period['term_name'])); ?> • A.Y. <?php echo e(display_academic_year($period['academic_year_name'])); ?></p></div><button type="button" class="modal-close" onclick="closeModal('previewModal')"><?php echo icon_svg('close'); ?></button></div>
            <div class="preview-body">
                <div class="preview-intro">Please rate your instructor honestly. Your responses are anonymous and will help improve teaching quality.</div>
                <div class="scale-line">
                    <?php foreach ($ratingOptions as $rate): ?>
                        <span><strong><?php echo (int)$rate['rating_value']; ?></strong> = <?php echo e($rate['rating_label']); ?></span>
                    <?php endforeach; ?>
                </div>
                <?php foreach ($criteriaRecords as $criteria): ?>
                    <?php if ($criteria['form_category_status'] !== 'Active') continue; ?>
                    <?php $shownQuestions = array_values(array_filter($questionsByCategory[(int)$criteria['evaluation_form_category_id']] ?? [], fn($q) => $q['form_item_status'] === 'Active' && $q['item_status'] === 'Active')); ?>
                    <div class="preview-criteria-block">
                        <h3><?php echo e($criteria['category_name']); ?></h3>
                        <div class="table-wrap">
                            <table class="preview-table">
                                <thead><tr><th>Statement</th><?php foreach ($ratingOptions as $rate): ?><th><?php echo (int)$rate['rating_value']; ?></th><?php endforeach; ?></tr></thead>
                                <tbody>
                                    <?php foreach ($shownQuestions as $i => $q): ?>
                                        <tr><td><?php echo ($i + 1); ?>. <?php echo e($q['statement_text_snapshot']); ?></td><?php foreach ($ratingOptions as $rate): ?><td><span class="preview-check"></span></td><?php endforeach; ?></tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="modal-footer"><button type="button" class="secondary-button" onclick="closeModal('previewModal')">Close Preview</button></div>
        </div>
    </div>

    <div class="modal-overlay hidden" id="scopeModal">
        <div class="modal-box scope-modal">
            <form method="post" id="scopeForm">
                <input type="hidden" name="action" id="scopeActionInput">
                <input type="hidden" name="semester" class="mirror-semester">
                <input type="hidden" name="academic_year" class="mirror-academic-year">
                <input type="hidden" name="start_date" class="mirror-start-date">
                <input type="hidden" name="end_date" class="mirror-end-date">
                <input type="hidden" name="display_count" class="mirror-display-count">
                <div class="modal-header"><h2 id="scopeModalTitle">Open Evaluation</h2><button type="button" class="modal-close" onclick="closeModal('scopeModal')"><?php echo icon_svg('close'); ?></button></div>
                <div class="modal-body">
                    <p class="scope-help" id="scopeHelpText">Select the scope for opening the evaluation. If Select All is chosen, the action applies to all colleges, programs, year levels, and sections.</p>
                    <label class="scope-main-label">Apply to</label>
                    <div class="scope-options">
                        <?php foreach (['All' => 'Select All', 'Department' => 'College', 'Course' => 'Program', 'Year Level' => 'Year Level', 'Section' => 'Section'] as $value => $label): ?>
                        <label class="scope-option"><input type="radio" name="scope_type" value="<?php echo e($value); ?>" <?php echo $value === 'All' ? 'checked' : ''; ?> onchange="updateScopeFields()"><span><?php echo e($label); ?></span></label>
                        <?php endforeach; ?>
                    </div>
                    <div id="scopeDynamicFields" class="scope-dynamic-fields">
                        <div class="field-row field-department hidden"><label>College <span class="optional-label">(optional filter)</span></label><select name="department_id" id="scopeDepartmentSelect"><option value="">All Colleges</option><?php foreach ($departments as $d): ?><option value="<?php echo (int)$d['department_id']; ?>"><?php echo e($d['department_name']); ?></option><?php endforeach; ?></select></div>
                        <div class="field-row field-course hidden"><label>Program</label><select name="course_id" id="scopeCourseSelect"><option value="">Select Program</option><?php foreach ($courses as $c): ?><option value="<?php echo (int)$c['course_id']; ?>" data-department="<?php echo (int)$c['department_id']; ?>"><?php echo e($c['course_code'] . ' - ' . $c['course_name']); ?></option><?php endforeach; ?></select></div>
                        <div class="two-column field-row field-section-filters hidden"><div><label>Program <span class="optional-label">(filter)</span></label><select id="scopeSectionCourseFilter"><option value="">All Programs</option><?php foreach ($courses as $c): ?><option value="<?php echo (int)$c['course_id']; ?>"><?php echo e($c['course_code']); ?></option><?php endforeach; ?></select></div><div><label>Year Level <span class="optional-label">(filter)</span></label><select name="year_level" id="scopeYearLevelSelect"><option value="">All Year Levels</option><?php for ($i=1;$i<=4;$i++): ?><option value="<?php echo $i; ?>"><?php echo e(year_level_label($i)); ?></option><?php endfor; ?></select></div></div>
                        <div class="field-row field-year hidden"><label>Year Level</label><select name="year_level_direct" id="scopeYearLevelDirectSelect"><option value="">Select Year Level</option><?php for ($i=1;$i<=4;$i++): ?><option value="<?php echo $i; ?>"><?php echo e(year_level_label($i)); ?></option><?php endfor; ?></select></div>
                        <div class="field-row field-section hidden"><label>Section</label><select name="section_id" id="scopeSectionSelect"><option value="">Select Section</option><?php foreach ($sections as $s): ?><option value="<?php echo (int)$s['section_id']; ?>" data-course="<?php echo (int)$s['course_id']; ?>" data-year="<?php echo (int)$s['year_level']; ?>"><?php echo e($s['section_name']); ?></option><?php endforeach; ?></select></div>
                    </div>
                    <div class="scope-summary"><strong>Summary:</strong> <span id="scopeSummaryText">All Colleges, Programs, Year Levels, and Sections</span></div>
                </div>
                <div class="modal-footer"><button type="button" class="secondary-button" onclick="closeModal('scopeModal')">Cancel</button><button type="submit" class="green-button" id="scopeSubmitButton">Confirm Open</button></div>
            </form>
        </div>
    </div>

    <div class="modal-overlay hidden" id="periodDetailsModal">
        <div class="modal-box period-details-modal">
            <div class="modal-header"><h2>Evaluation Period Details</h2><button type="button" class="modal-close" onclick="closeModal('periodDetailsModal')"><?php echo icon_svg('close'); ?></button></div>
            <div class="modal-body"><div class="details-grid period-details-grid" id="periodDetailsContent"></div></div>
            <div class="modal-footer"><button type="button" class="secondary-button" onclick="closeModal('periodDetailsModal')">Close</button></div>
        </div>
    </div>

    <div class="modal-overlay hidden" id="successModal">
        <div class="modal-box message-box success-save-box">
            <div class="success-icon"><?php echo icon_svg('check'); ?></div>
            <h2>Setup Saved Successfully</h2>
            <p><?php echo count($criteriaRecords); ?> criteria, <?php echo count($questionRecords); ?> questions, <?php echo $displayCount; ?> questions displayed per criteria, rating scale 1 to 5, schedule <?php echo e(term_label($period['term_name'])); ?> A.Y. <?php echo e(display_academic_year($period['academic_year_name'])); ?>.</p>
            <button type="button" class="dark-button full-button" onclick="closeModal('successModal')">Done</button>
        </div>
    </div>

    <?php if ($flash && !isset($_GET["saved"])): ?>
    <div class="toast-modal <?php echo e($flash['type']); ?>" id="flashToast"><?php echo nl2br(e($flash['message'])); ?></div>
    <?php endif; ?>

    <script>
        const criteriaRecords = <?php echo json_safe($criteriaRecords); ?>;
        const questionRecords = <?php echo json_safe($questionRecords); ?>;
        const historyRecords = <?php echo json_safe($allHistoryRows); ?>;
        const departments = <?php echo json_safe($departments); ?>;
        const courses = <?php echo json_safe($courses); ?>;
        const sections = <?php echo json_safe($sections); ?>;

        function openModal(id) {
            const el = document.getElementById(id);
            if (!el) return;
            el.classList.remove("hidden");
            document.body.classList.add("modal-open");
        }

        function closeModal(id) {
            const el = document.getElementById(id);
            if (!el) return;
            el.classList.add("hidden");
            if (!document.querySelector(".modal-overlay:not(.hidden)")) {
                document.body.classList.remove("modal-open");
            }
        }

        function collectSetupValues(form) {
            const semester = document.getElementById("semesterSelect").value;
            const academicYear = document.getElementById("academicYearInput").value;
            const startDate = document.getElementById("startDateInput").value;
            const endDate = document.getElementById("endDateInput").value;
            const displayCount = document.getElementById("displayCountSelect").value;
            if (form.id === "setupActionForm") {
                document.getElementById("setupSemesterInput").value = semester;
                document.getElementById("setupAcademicYearInput").value = academicYear;
                document.getElementById("setupStartDateInput").value = startDate;
                document.getElementById("setupEndDateInput").value = endDate;
                document.getElementById("setupDisplayCountInput").value = displayCount;
            } else {
                form.querySelector(".mirror-semester").value = semester;
                form.querySelector(".mirror-academic-year").value = academicYear;
                form.querySelector(".mirror-start-date").value = startDate;
                form.querySelector(".mirror-end-date").value = endDate;
                form.querySelector(".mirror-display-count").value = displayCount;
            }
        }

        function submitSetupAction(action) {
            const form = document.getElementById("setupActionForm");
            document.getElementById("setupActionInput").value = action;
            collectSetupValues(form);
            form.submit();
        }

        function openCriteriaModal(mode, id = null) {
            const form = document.getElementById("criteriaForm");
            form.reset();
            document.getElementById("criteriaFormCategoryId").value = "";
            document.getElementById("criteriaFormBody").classList.remove("hidden");
            document.getElementById("criteriaFormFooter").classList.remove("hidden");
            document.getElementById("criteriaViewBody").classList.add("hidden");
            document.getElementById("criteriaViewFooter").classList.add("hidden");
            Array.from(form.elements).forEach(el => el.disabled = false);

            if (mode === "add") {
                document.getElementById("criteriaModalTitle").textContent = "Add Evaluation Criteria";
                document.getElementById("criteriaSubmitButton").textContent = "Add Criteria";
                document.getElementById("criteriaOrderInput").value = criteriaRecords.length + 1;
            } else {
                const record = criteriaRecords.find(r => Number(r.evaluation_form_category_id) === Number(id));
                if (!record) return;
                if (mode === "view") {
                    document.getElementById("criteriaModalTitle").textContent = "Criteria Details";
                    document.getElementById("criteriaFormBody").classList.add("hidden");
                    document.getElementById("criteriaFormFooter").classList.add("hidden");
                    document.getElementById("criteriaViewBody").classList.remove("hidden");
                    document.getElementById("criteriaViewFooter").classList.remove("hidden");
                    document.getElementById("viewCriteriaName").textContent = record.category_name;
                    document.getElementById("viewCriteriaDescription").textContent = record.category_description || "";
                    document.getElementById("viewCriteriaWeight").textContent = parseFloat(record.weight_percent) + "%";
                    document.getElementById("viewCriteriaOrder").textContent = record.display_order;
                    document.getElementById("viewCriteriaStatus").innerHTML = `<span class="status-badge ${String(record.form_category_status).toLowerCase()}">${String(record.form_category_status).toLowerCase()}</span>`;
                    document.getElementById("criteriaEditFromViewButton").onclick = () => openCriteriaModal("edit", id);
                } else {
                    document.getElementById("criteriaModalTitle").textContent = "Edit Evaluation Criteria";
                    document.getElementById("criteriaSubmitButton").textContent = "Update Criteria";
                    document.getElementById("criteriaFormCategoryId").value = record.evaluation_form_category_id;
                    document.getElementById("criteriaNameInput").value = record.category_name;
                    document.getElementById("criteriaDescriptionInput").value = record.category_description || "";
                    document.getElementById("criteriaWeightInput").value = parseFloat(record.weight_percent);
                    document.getElementById("criteriaOrderInput").value = record.display_order;
                    document.getElementById("criteriaStatusInput").value = record.form_category_status;
                }
            }
            openModal("criteriaModal");
        }

        function openQuestionModal(mode, categoryId, questionId = null) {
            const form = document.getElementById("questionForm");
            form.reset();
            const category = criteriaRecords.find(r => Number(r.evaluation_form_category_id) === Number(categoryId));
            document.getElementById("questionFormCategoryId").value = categoryId;
            document.getElementById("questionFormItemId").value = "";
            document.getElementById("questionCriteriaLabel").textContent = category ? category.category_name : "";
            if (mode === "edit") {
                const question = questionRecords.find(q => Number(q.evaluation_form_item_id) === Number(questionId));
                if (!question) return;
                document.getElementById("questionModalTitle").textContent = "Edit Question";
                document.getElementById("questionSubmitButton").textContent = "Update Question";
                document.getElementById("questionFormItemId").value = question.evaluation_form_item_id;
                document.getElementById("questionTextInput").value = question.statement_text_snapshot;
            } else {
                document.getElementById("questionModalTitle").textContent = "Add Question";
                document.getElementById("questionSubmitButton").textContent = "Add Question";
            }
            openModal("questionModal");
        }

        function openPreviewModal() {
            openModal("previewModal");
        }

        function openScopeModal(type) {
            const form = document.getElementById("scopeForm");
            form.reset();
            collectSetupValues(form);
            document.getElementById("scopeActionInput").value = type === "open" ? "open_evaluation" : "close_evaluation";
            document.getElementById("scopeModalTitle").textContent = type === "open" ? "Open Evaluation" : "Close Evaluation";
            document.getElementById("scopeHelpText").textContent = type === "open"
                ? "Select the scope for opening the evaluation. If Select All is chosen, the action applies to all colleges, programs, year levels, and sections."
                : "Select the scope for closing the evaluation. If Select All is chosen, the action applies to all colleges, programs, year levels, and sections.";
            const submit = document.getElementById("scopeSubmitButton");
            submit.textContent = type === "open" ? "Confirm Open" : "Confirm Close";
            submit.className = type === "open" ? "green-button" : "red-button";
            updateScopeFields();
            openModal("scopeModal");
        }

        function selectedScopeType() {
            const checked = document.querySelector('input[name="scope_type"]:checked');
            return checked ? checked.value : "All";
        }

        function updateScopeFields() {
            const scope = selectedScopeType();
            document.querySelectorAll(".scope-dynamic-fields .field-row").forEach(el => el.classList.add("hidden"));
            document.getElementById("scopeCourseSelect").required = false;
            document.getElementById("scopeDepartmentSelect").required = false;
            document.getElementById("scopeSectionSelect").required = false;
            document.getElementById("scopeYearLevelDirectSelect").required = false;
            document.getElementById("scopeYearLevelSelect").name = "year_level";
            document.getElementById("scopeYearLevelDirectSelect").name = "year_level_direct";

            if (scope === "Department") {
                document.querySelector(".field-department").classList.remove("hidden");
                document.getElementById("scopeDepartmentSelect").required = true;
                document.querySelector(".field-department .optional-label").textContent = "";
            }
            if (scope === "Course") {
                document.querySelector(".field-department").classList.remove("hidden");
                document.querySelector(".field-course").classList.remove("hidden");
                document.querySelector(".field-department .optional-label").textContent = "(optional filter)";
                document.getElementById("scopeCourseSelect").required = true;
            }
            if (scope === "Year Level") {
                document.querySelector(".field-year").classList.remove("hidden");
                document.getElementById("scopeYearLevelDirectSelect").name = "year_level";
                document.getElementById("scopeYearLevelDirectSelect").required = true;
                document.getElementById("scopeYearLevelSelect").name = "year_level_filter";
            }
            if (scope === "Section") {
                document.querySelector(".field-section-filters").classList.remove("hidden");
                document.querySelector(".field-section").classList.remove("hidden");
                document.getElementById("scopeSectionSelect").required = true;
            }
            filterScopeOptions();
            updateScopeSummary();
        }

        function filterScopeOptions() {
            const depId = document.getElementById("scopeDepartmentSelect").value;
            Array.from(document.getElementById("scopeCourseSelect").options).forEach(opt => {
                if (!opt.value) { opt.hidden = false; return; }
                opt.hidden = depId && opt.dataset.department !== depId;
            });
            const courseFilter = document.getElementById("scopeSectionCourseFilter").value;
            const yearFilter = document.getElementById("scopeYearLevelSelect").value;
            Array.from(document.getElementById("scopeSectionSelect").options).forEach(opt => {
                if (!opt.value) { opt.hidden = false; return; }
                opt.hidden = (courseFilter && opt.dataset.course !== courseFilter) || (yearFilter && opt.dataset.year !== yearFilter);
            });
        }

        function updateScopeSummary() {
            const scope = selectedScopeType();
            let summary = "All Colleges, Programs, Year Levels, and Sections";
            if (scope === "Department") {
                const opt = document.getElementById("scopeDepartmentSelect").selectedOptions[0];
                summary = opt && opt.value ? opt.textContent : "Please select a College.";
            }
            if (scope === "Course") {
                const opt = document.getElementById("scopeCourseSelect").selectedOptions[0];
                summary = opt && opt.value ? opt.textContent : "Please select a Program.";
            }
            if (scope === "Year Level") {
                const opt = document.getElementById("scopeYearLevelDirectSelect").selectedOptions[0];
                summary = opt && opt.value ? opt.textContent : "Please select a year level.";
            }
            if (scope === "Section") {
                const opt = document.getElementById("scopeSectionSelect").selectedOptions[0];
                summary = opt && opt.value ? opt.textContent : "Please select a Section.";
            }
            document.getElementById("scopeSummaryText").textContent = summary;
        }

        ["scopeDepartmentSelect", "scopeCourseSelect", "scopeSectionCourseFilter", "scopeYearLevelSelect", "scopeYearLevelDirectSelect", "scopeSectionSelect"].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener("change", () => { filterScopeOptions(); updateScopeSummary(); });
        });

        function openPeriodDetails(scopeId, periodId) {
            const row = historyRecords.find(r => Number(r.evaluation_period_scope_id) === Number(scopeId) && Number(r.evaluation_period_id) === Number(periodId));
            if (!row) return;
            const html = `
                <div><span>Academic Year</span><strong>${row.academic_year_ui || ""}</strong></div>
                <div><span>Semester</span><strong>${row.semester_ui || ""}</strong></div>
                <div><span>Scope Type</span><strong><span class="scope-badge">${row.scope_type_ui || row.scope_type}</span></strong></div>
                <div><span>Status</span><strong>${row.scope_status || ""}</strong></div>
                <div class="detail-full"><span>Applied To</span><strong>${row.scope_display || ""}</strong></div>
                <div><span>Start Date</span><strong>${formatDate(row.start_date)}</strong></div>
                <div><span>End Date</span><strong>${formatDate(row.end_date)}</strong></div>
                <div><span>Date Opened</span><strong>${formatDateTime(row.opened_at)}</strong></div>
                <div><span>Date Closed</span><strong>${row.closed_at ? formatDateTime(row.closed_at) : "—"}</strong></div>
            `;
            document.getElementById("periodDetailsContent").innerHTML = html;
            openModal("periodDetailsModal");
        }

        function formatDate(value) {
            if (!value) return "—";
            const d = new Date(value.replace(" ", "T"));
            return isNaN(d) ? value : d.toLocaleDateString();
        }

        function formatDateTime(value) {
            if (!value) return "—";
            const d = new Date(value.replace(" ", "T"));
            return isNaN(d) ? value : d.toLocaleString();
        }

        document.querySelectorAll(".modal-overlay").forEach(overlay => {
            overlay.addEventListener("click", function(e) {
                if (e.target === this) closeModal(this.id);
            });
        });

        const toast = document.getElementById("flashToast");
        if (toast) {
            setTimeout(() => toast.classList.add("hide"), 3000);
            setTimeout(() => toast.remove(), 3500);
        }

        if (new URLSearchParams(window.location.search).has("saved")) {
            openModal("successModal");
        }
        if (new URLSearchParams(window.location.search).has("questions")) {
            setTimeout(() => document.getElementById("questionsSection")?.scrollIntoView({ behavior: "smooth", block: "start" }), 100);
        }
        if (new URLSearchParams(window.location.search).has("history")) {
            setTimeout(() => document.getElementById("historySection")?.scrollIntoView({ behavior: "smooth", block: "start" }), 100);
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