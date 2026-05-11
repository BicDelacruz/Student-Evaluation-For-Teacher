<?php
session_start();

if (isset($_GET["tab"])) {
    $legacy_panel = $_GET["tab"];

    if (!in_array($legacy_panel, ["faculty", "section"], true)) {
        $legacy_panel = "faculty";
    }

    $query = $_GET;
    unset($query["tab"]);
    $query["panel"] = $legacy_panel;

    header("Location: assignment_management.php?" . http_build_query($query));
    exit;
}

$page_title = "Assignment Management";
$current_page = basename($_SERVER["PHP_SELF"]);

require_once __DIR__ . "/../database_connector.php";

if (!isset($pdo) || !($pdo instanceof PDO)) {
    if (isset($conn) && $conn instanceof PDO) {
        $pdo = $conn;
    } else {
        $pdo = new PDO(
            "mysql:host=localhost;dbname=student_evaluation_for_teacher_db;charset=utf8mb4",
            "root",
            "",
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
    }
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function fetch_all($pdo, $sql, $params = [])
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetch_one($pdo, $sql, $params = [])
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function post_value($key, $default = "")
{
    return $_POST[$key] ?? $default;
}

function post_array($key)
{
    if (!isset($_POST[$key])) {
        return [];
    }

    if (is_array($_POST[$key])) {
        return array_values(array_filter($_POST[$key], fn($v) => $v !== "" && $v !== null));
    }

    return [$_POST[$key]];
}

function placeholders($items)
{
    return implode(",", array_fill(0, count($items), "?"));
}

function year_label($year)
{
    $year = (int)$year;

    return match ($year) {
        1 => "1st Year",
        2 => "2nd Year",
        3 => "3rd Year",
        4 => "4th Year",
        5 => "5th Year",
        6 => "6th Year",
        default => $year . " Year"
    };
}

function term_short_label($term)
{
    return match ($term) {
        "First Semester" => "1st Semester",
        "Second Semester" => "2nd Semester",
        default => $term
    };
}

function format_subject_list($subject_list)
{
    $items = array_filter(array_map("trim", explode("||", (string)$subject_list)));

    if (empty($items)) {
        return '<span class="muted-text">No active courses listed</span>';
    }

    $html = '<ol class="subjects-taken-list">';

    foreach ($items as $item) {
        $html .= '<li>' . h($item) . '</li>';
    }

    $html .= '</ol>';

    return $html;
}

function active_class($page)
{
    global $current_page;
    return $current_page === $page ? " active" : "";
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
        "search" => '<svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"></circle><path d="M21 21l-4.35-4.35"></path></svg>',
        "plus" => '<svg viewBox="0 0 24 24"><path d="M12 5v14"></path><path d="M5 12h14"></path></svg>',
        "eye" => '<svg viewBox="0 0 24 24"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"></path><circle cx="12" cy="12" r="3"></circle></svg>',
        "edit" => '<svg viewBox="0 0 24 24"><path d="M12 20h9"></path><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"></path></svg>',
        "trash" => '<svg viewBox="0 0 24 24"><path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="M19 6l-1 14H6L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path></svg>',
        "check" => '<svg viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"></path></svg>',
        "x" => '<svg viewBox="0 0 24 24"><path d="M18 6L6 18"></path><path d="M6 6l12 12"></path></svg>',
        "filter" => '<svg viewBox="0 0 24 24"><path d="M3 4h18l-7 8v6l-4 2v-8L3 4z"></path></svg>'
    ];

    return $icons[$name] ?? "";
}

function get_admin_id($pdo)
{
    if (isset($_SESSION["admin_id"]) && (int)$_SESSION["admin_id"] > 0) {
        return (int)$_SESSION["admin_id"];
    }

    if (isset($_SESSION["user_id"]) && (int)$_SESSION["user_id"] > 0) {
        $admin = fetch_one($pdo, "SELECT admin_id FROM admin WHERE user_id = ? LIMIT 1", [(int)$_SESSION["user_id"]]);
        if ($admin) {
            return (int)$admin["admin_id"];
        }
    }

    $admin = fetch_one($pdo, "SELECT admin_id FROM admin WHERE admin_status = 'Active' ORDER BY admin_id LIMIT 1");
    return $admin ? (int)$admin["admin_id"] : 1;
}

function get_term_id($pdo, $academic_year_id, $semester)
{
    $row = fetch_one(
        $pdo,
        "SELECT term_id FROM term WHERE academic_year_id = ? AND term_name = ? LIMIT 1",
        [$academic_year_id, $semester]
    );

    return $row ? (int)$row["term_id"] : 0;
}

function call_or_insert_offering($pdo, $section_id, $subject_id, $term_id, $admin_id)
{
    $existing = fetch_one(
        $pdo,
        "SELECT section_subject_offering_id
         FROM section_subject_offering
         WHERE section_id = ?
         AND subject_id = ?
         AND term_id = ?
         LIMIT 1",
        [$section_id, $subject_id, $term_id]
    );

    if ($existing) {
        $stmt = $pdo->prepare("
            UPDATE section_subject_offering
            SET offering_status = 'Active',
                updated_at = CURRENT_TIMESTAMP
            WHERE section_subject_offering_id = ?
        ");
        $stmt->execute([(int)$existing["section_subject_offering_id"]]);

        return (int)$existing["section_subject_offering_id"];
    }

    $stmt = $pdo->prepare("
        INSERT INTO section_subject_offering
            (section_id, subject_id, term_id, created_by_admin_id, offering_status)
        VALUES
            (?, ?, ?, ?, 'Active')
    ");
    $stmt->execute([$section_id, $subject_id, $term_id, $admin_id]);

    return (int)$pdo->lastInsertId();
}

function call_or_insert_assignment($pdo, $offering_id, $faculty_id, $term_id, $admin_id)
{
    $existing = fetch_one(
        $pdo,
        "SELECT teaching_assignment_id
         FROM teaching_assignment
         WHERE section_subject_offering_id = ?
         LIMIT 1",
        [$offering_id]
    );

    if ($existing) {
        $stmt = $pdo->prepare("
            UPDATE teaching_assignment
            SET faculty_id = ?,
                term_id = ?,
                assignment_status = 'Active',
                updated_at = CURRENT_TIMESTAMP
            WHERE teaching_assignment_id = ?
        ");
        $stmt->execute([$faculty_id, $term_id, (int)$existing["teaching_assignment_id"]]);

        return (int)$existing["teaching_assignment_id"];
    }

    $stmt = $pdo->prepare("
        INSERT INTO teaching_assignment
            (section_subject_offering_id, faculty_id, term_id, created_by_admin_id, assignment_status)
        VALUES
            (?, ?, ?, ?, 'Active')
    ");
    $stmt->execute([$offering_id, $faculty_id, $term_id, $admin_id]);

    return (int)$pdo->lastInsertId();
}

function flash_redirect($type, $message, $panel = "faculty")
{
    $_SESSION["flash_type"] = $type;
    $_SESSION["flash_message"] = $message;
    header("Location: assignment_management.php?panel=" . urlencode($panel));
    exit;
}

$admin_id = get_admin_id($pdo);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = post_value("form_action");

    try {
        if ($action === "assign_faculty") {
            $department_id = (int)post_value("assign_department_id");
            $faculty_id = (int)post_value("assign_faculty_id");
            $academic_year_id = (int)post_value("assign_academic_year_id");
            $year_level = (int)post_value("assign_year_level");
            $semester = post_value("assign_semester");
            $course_ids = array_map("intval", post_array("assign_course_ids"));
            $subject_ids = array_map("intval", post_array("assign_subject_ids"));

            if ($department_id <= 0) {
                flash_redirect("error", "Please select a college first.", "faculty");
            }

            if ($faculty_id <= 0) {
                flash_redirect("error", "Please select a faculty member.", "faculty");
            }

            if ($academic_year_id <= 0 || $semester === "") {
                flash_redirect("error", "Please select academic year and semester.", "faculty");
            }

            if ($year_level < 1 || $year_level > 6) {
                flash_redirect("error", "Please select a valid year level.", "faculty");
            }

            if (empty($course_ids)) {
                flash_redirect("error", "Please select at least one program.", "faculty");
            }

            if (empty($subject_ids)) {
                flash_redirect("error", "Please select at least one course.", "faculty");
            }

            $term_id = get_term_id($pdo, $academic_year_id, $semester);

            if ($term_id <= 0) {
                flash_redirect("error", "The selected academic year and semester do not have a valid term record.", "faculty");
            }

            $faculty = fetch_one(
                $pdo,
                "SELECT faculty_id, faculty_number, full_name
                 FROM faculty
                 WHERE faculty_id = ?
                 AND faculty_status = 'Active'",
                [$faculty_id]
            );

            if (!$faculty) {
                flash_redirect("error", "The selected faculty member is inactive or does not exist.", "faculty");
            }

            $course_placeholders = placeholders($course_ids);
            $section_params = array_merge($course_ids, [$term_id, $year_level]);

            $sections = fetch_all(
                $pdo,
                "SELECT section_id, section_name
                 FROM section
                 WHERE course_id IN ($course_placeholders)
                 AND term_id = ?
                 AND year_level = ?
                 AND section_status = 'Active'",
                $section_params
            );

            if (empty($sections)) {
                flash_redirect("error", "No active sections found for the selected program, year level, and semester.", "faculty");
            }

            $pdo->beginTransaction();

            foreach ($sections as $section) {
                foreach ($subject_ids as $subject_id) {
                    $offering_id = call_or_insert_offering($pdo, (int)$section["section_id"], (int)$subject_id, $term_id, $admin_id);
                    call_or_insert_assignment($pdo, $offering_id, $faculty_id, $term_id, $admin_id);
                }
            }

            $pdo->commit();

            flash_redirect(
                "success",
                "Faculty " . $faculty["faculty_number"] . " " . $faculty["full_name"] . " successfully assigned to selected course records.",
                "faculty"
            );
        }

        if ($action === "edit_assignment") {
            $teaching_assignment_id = (int)post_value("edit_assignment_id");
            $faculty_id = (int)post_value("edit_assignment_faculty_id");
            $subject_id = (int)post_value("edit_assignment_subject_id");
            $section_id = (int)post_value("edit_assignment_section_id");
            $term_id = (int)post_value("edit_assignment_term_id");

            if ($teaching_assignment_id <= 0 || $faculty_id <= 0 || $subject_id <= 0 || $section_id <= 0 || $term_id <= 0) {
                flash_redirect("error", "Please complete all required assignment fields.", "faculty");
            }

            $faculty = fetch_one(
                $pdo,
                "SELECT faculty_id FROM faculty WHERE faculty_id = ? AND faculty_status = 'Active'",
                [$faculty_id]
            );

            if (!$faculty) {
                flash_redirect("error", "The selected faculty member is inactive or does not exist.", "faculty");
            }

            $pdo->beginTransaction();

            $offering_id = call_or_insert_offering($pdo, $section_id, $subject_id, $term_id, $admin_id);

            $duplicate = fetch_one(
                $pdo,
                "SELECT teaching_assignment_id
                 FROM teaching_assignment
                 WHERE section_subject_offering_id = ?
                 AND teaching_assignment_id <> ?
                 LIMIT 1",
                [$offering_id, $teaching_assignment_id]
            );

            if ($duplicate) {
                $stmt = $pdo->prepare("
                    UPDATE teaching_assignment
                    SET faculty_id = ?,
                        term_id = ?,
                        assignment_status = 'Active',
                        updated_at = CURRENT_TIMESTAMP
                    WHERE teaching_assignment_id = ?
                ");
                $stmt->execute([$faculty_id, $term_id, (int)$duplicate["teaching_assignment_id"]]);

                $stmt = $pdo->prepare("
                    DELETE FROM teaching_assignment
                    WHERE teaching_assignment_id = ?
                    AND NOT EXISTS (
                        SELECT 1
                        FROM student_evaluation_task
                        WHERE student_evaluation_task.teaching_assignment_id = teaching_assignment.teaching_assignment_id
                    )
                ");
                $stmt->execute([$teaching_assignment_id]);

                if ($stmt->rowCount() === 0) {
                    $stmt = $pdo->prepare("
                        UPDATE teaching_assignment
                        SET assignment_status = 'Inactive',
                            updated_at = CURRENT_TIMESTAMP
                        WHERE teaching_assignment_id = ?
                    ");
                    $stmt->execute([$teaching_assignment_id]);
                }
            } else {
                $stmt = $pdo->prepare("
                    UPDATE teaching_assignment
                    SET faculty_id = ?,
                        section_subject_offering_id = ?,
                        term_id = ?,
                        assignment_status = 'Active',
                        updated_at = CURRENT_TIMESTAMP
                    WHERE teaching_assignment_id = ?
                ");
                $stmt->execute([$faculty_id, $offering_id, $term_id, $teaching_assignment_id]);
            }

            $pdo->commit();

            flash_redirect("success", "Faculty assignment successfully updated.", "faculty");
        }

        if ($action === "delete_assignment") {
            $teaching_assignment_id = (int)post_value("delete_assignment_id");

            if ($teaching_assignment_id <= 0) {
                flash_redirect("error", "Invalid assignment selected.", "faculty");
            }

            $linked = fetch_one(
                $pdo,
                "SELECT COUNT(*) AS total
                 FROM student_evaluation_task
                 WHERE teaching_assignment_id = ?",
                [$teaching_assignment_id]
            );

            if ((int)$linked["total"] > 0) {
                $stmt = $pdo->prepare("
                    UPDATE teaching_assignment
                    SET assignment_status = 'Inactive',
                        updated_at = CURRENT_TIMESTAMP
                    WHERE teaching_assignment_id = ?
                ");
                $stmt->execute([$teaching_assignment_id]);

                flash_redirect(
                    "warning",
                    "You cannot delete an entity with record inside. This will mark as inactive.",
                    "faculty"
                );
            }

            $stmt = $pdo->prepare("DELETE FROM teaching_assignment WHERE teaching_assignment_id = ?");
            $stmt->execute([$teaching_assignment_id]);

            flash_redirect("success", "Faculty assignment successfully deleted.", "faculty");
        }

        if ($action === "restore_assignment") {
            $teaching_assignment_id = (int)post_value("restore_assignment_id");

            if ($teaching_assignment_id <= 0) {
                flash_redirect("error", "Invalid assignment selected.", "faculty");
            }

            $assignment = fetch_one(
                $pdo,
                "SELECT section_subject_offering_id
                 FROM teaching_assignment
                 WHERE teaching_assignment_id = ?
                 LIMIT 1",
                [$teaching_assignment_id]
            );

            if (!$assignment) {
                flash_redirect("error", "Assignment record was not found.", "faculty");
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                UPDATE section_subject_offering
                SET offering_status = 'Active',
                    updated_at = CURRENT_TIMESTAMP
                WHERE section_subject_offering_id = ?
            ");
            $stmt->execute([(int)$assignment["section_subject_offering_id"]]);

            $stmt = $pdo->prepare("
                UPDATE teaching_assignment
                SET assignment_status = 'Active',
                    updated_at = CURRENT_TIMESTAMP
                WHERE teaching_assignment_id = ?
            ");
            $stmt->execute([$teaching_assignment_id]);

            $pdo->commit();

            flash_redirect("success", "Faculty assignment successfully activated.", "faculty");
        }

        if ($action === "enroll_section") {
            $department_id = (int)post_value("enroll_department_id");
            $course_id = (int)post_value("enroll_course_id");
            $academic_year_id = (int)post_value("enroll_academic_year_id");
            $year_level = (int)post_value("enroll_year_level");
            $semester = post_value("enroll_semester");
            $section_ids = array_map("intval", post_array("enroll_section_ids"));
            $subject_ids = array_map("intval", post_array("enroll_subject_ids"));

            if ($department_id <= 0) {
                flash_redirect("error", "Please select a college.", "section");
            }

            if ($course_id <= 0) {
                flash_redirect("error", "Please select a program.", "section");
            }

            if ($academic_year_id <= 0 || $semester === "") {
                flash_redirect("error", "Please select academic year and semester.", "section");
            }

            if ($year_level < 1 || $year_level > 6) {
                flash_redirect("error", "Please select a valid year level.", "section");
            }

            if (empty($section_ids)) {
                flash_redirect("error", "Please select at least one section.", "section");
            }

            if (empty($subject_ids)) {
                flash_redirect("error", "Please select at least one course.", "section");
            }

            $term_id = get_term_id($pdo, $academic_year_id, $semester);

            if ($term_id <= 0) {
                flash_redirect("error", "The selected academic year and semester do not have a valid term record.", "section");
            }

            $pdo->beginTransaction();

            foreach ($section_ids as $section_id) {
                foreach ($subject_ids as $subject_id) {
                    call_or_insert_offering($pdo, $section_id, $subject_id, $term_id, $admin_id);
                }
            }

            $pdo->commit();

            flash_redirect("success", "Selected section records successfully enrolled to the chosen courses.", "section");
        }

        if ($action === "edit_enrollment") {
            $section_id = (int)post_value("edit_enrollment_section_id");
            $term_id = (int)post_value("edit_enrollment_term_id");
            $subject_ids = array_map("intval", post_array("edit_enrollment_subject_ids"));

            if ($section_id <= 0 || $term_id <= 0) {
                flash_redirect("error", "Invalid section enrollment selected.", "section");
            }

            if (empty($subject_ids)) {
                flash_redirect("error", "Please select at least one course.", "section");
            }

            $pdo->beginTransaction();

            $selected_placeholders = placeholders($subject_ids);
            $params = array_merge([$section_id, $term_id], $subject_ids);

            $offerings_to_deactivate = fetch_all(
                $pdo,
                "SELECT section_subject_offering_id
                 FROM section_subject_offering
                 WHERE section_id = ?
                 AND term_id = ?
                 AND subject_id NOT IN ($selected_placeholders)",
                $params
            );

            if (!empty($offerings_to_deactivate)) {
                $offering_ids = array_map(fn($row) => (int)$row["section_subject_offering_id"], $offerings_to_deactivate);
                $offering_placeholders = placeholders($offering_ids);

                $stmt = $pdo->prepare("
                    UPDATE teaching_assignment
                    SET assignment_status = 'Inactive',
                        updated_at = CURRENT_TIMESTAMP
                    WHERE section_subject_offering_id IN ($offering_placeholders)
                ");
                $stmt->execute($offering_ids);

                $stmt = $pdo->prepare("
                    UPDATE section_subject_offering
                    SET offering_status = 'Inactive',
                        updated_at = CURRENT_TIMESTAMP
                    WHERE section_subject_offering_id IN ($offering_placeholders)
                ");
                $stmt->execute($offering_ids);
            }

            foreach ($subject_ids as $subject_id) {
                call_or_insert_offering($pdo, $section_id, $subject_id, $term_id, $admin_id);
            }

            $pdo->commit();

            flash_redirect("success", "Section enrollment successfully updated.", "section");
        }

        if ($action === "delete_enrollment") {
            $section_id = (int)post_value("delete_enrollment_section_id");
            $term_id = (int)post_value("delete_enrollment_term_id");

            if ($section_id <= 0 || $term_id <= 0) {
                flash_redirect("error", "Invalid section enrollment selected.", "section");
            }

            $linked = fetch_one(
                $pdo,
                "SELECT COUNT(*) AS total
                 FROM section_subject_offering sso
                 INNER JOIN teaching_assignment ta
                    ON ta.section_subject_offering_id = sso.section_subject_offering_id
                 WHERE sso.section_id = ?
                 AND sso.term_id = ?",
                [$section_id, $term_id]
            );

            if ((int)$linked["total"] > 0) {
                $pdo->beginTransaction();

                $offerings = fetch_all(
                    $pdo,
                    "SELECT section_subject_offering_id
                     FROM section_subject_offering
                     WHERE section_id = ?
                     AND term_id = ?",
                    [$section_id, $term_id]
                );

                if (!empty($offerings)) {
                    $offering_ids = array_map(fn($row) => (int)$row["section_subject_offering_id"], $offerings);
                    $offering_placeholders = placeholders($offering_ids);

                    $stmt = $pdo->prepare("
                        UPDATE teaching_assignment
                        SET assignment_status = 'Inactive',
                            updated_at = CURRENT_TIMESTAMP
                        WHERE section_subject_offering_id IN ($offering_placeholders)
                    ");
                    $stmt->execute($offering_ids);
                }

                $stmt = $pdo->prepare("
                    UPDATE section_subject_offering
                    SET offering_status = 'Inactive',
                        updated_at = CURRENT_TIMESTAMP
                    WHERE section_id = ?
                    AND term_id = ?
                ");
                $stmt->execute([$section_id, $term_id]);

                $pdo->commit();

                flash_redirect(
                    "warning",
                    "You cannot delete an entity with record inside. This will mark as inactive.",
                    "section"
                );
            }

            $stmt = $pdo->prepare("DELETE FROM section_subject_offering WHERE section_id = ? AND term_id = ?");
            $stmt->execute([$section_id, $term_id]);

            flash_redirect("success", "Section enrollment successfully deleted.", "section");
        }

        if ($action === "restore_enrollment") {
            $section_id = (int)post_value("restore_enrollment_section_id");
            $term_id = (int)post_value("restore_enrollment_term_id");

            if ($section_id <= 0 || $term_id <= 0) {
                flash_redirect("error", "Invalid section enrollment selected.", "section");
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                UPDATE section_subject_offering
                SET offering_status = 'Active',
                    updated_at = CURRENT_TIMESTAMP
                WHERE section_id = ?
                AND term_id = ?
            ");
            $stmt->execute([$section_id, $term_id]);

            $stmt = $pdo->prepare("
                UPDATE teaching_assignment ta
                INNER JOIN section_subject_offering sso
                    ON sso.section_subject_offering_id = ta.section_subject_offering_id
                SET ta.assignment_status = 'Active',
                    ta.updated_at = CURRENT_TIMESTAMP
                WHERE sso.section_id = ?
                AND sso.term_id = ?
            ");
            $stmt->execute([$section_id, $term_id]);

            $pdo->commit();

            flash_redirect("success", "Section enrollment successfully activated.", "section");
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $target_panel = str_contains((string)$action, "enrollment") || str_contains((string)$action, "enroll") ? "section" : "faculty";
        flash_redirect("error", "System error: " . $e->getMessage(), $target_panel);
    }
}

$departments = fetch_all(
    $pdo,
    "SELECT department_id, department_code, department_name
     FROM department
     WHERE department_status = 'Active'
     ORDER BY department_name"
);

$courses = fetch_all(
    $pdo,
    "SELECT course_id, department_id, course_code, course_name, number_of_year_level
     FROM course
     WHERE course_status = 'Active'
     ORDER BY course_code"
);

$academic_years = fetch_all(
    $pdo,
    "SELECT academic_year_id, academic_year_name, academic_year_status
     FROM academic_year
     ORDER BY start_date DESC, academic_year_name DESC"
);

$terms = fetch_all(
    $pdo,
    "SELECT t.term_id, t.academic_year_id, t.term_name, ay.academic_year_name
     FROM term t
     INNER JOIN academic_year ay ON ay.academic_year_id = t.academic_year_id
     WHERE t.term_status IN ('Active', 'Closed')
     ORDER BY ay.start_date DESC, FIELD(t.term_name, 'First Semester', 'Second Semester', 'Summer')"
);

$faculties = fetch_all(
    $pdo,
    "SELECT faculty_id, faculty_number, department_id, full_name, academic_rank, faculty_status
     FROM faculty
     WHERE faculty_status = 'Active'
     ORDER BY full_name"
);

$sections = fetch_all(
    $pdo,
    "SELECT sec.section_id, sec.course_id, sec.term_id, sec.section_name, sec.year_level, sec.section_status,
            c.department_id, c.course_code, c.course_name, t.term_name, ay.academic_year_id, ay.academic_year_name
     FROM section sec
     INNER JOIN course c ON c.course_id = sec.course_id
     INNER JOIN term t ON t.term_id = sec.term_id
     INNER JOIN academic_year ay ON ay.academic_year_id = t.academic_year_id
     WHERE sec.section_status = 'Active'
     ORDER BY c.course_code, sec.year_level, sec.section_name"
);

$subjects = fetch_all(
    $pdo,
    "SELECT subject_id, department_id, subject_code, subject_title, subject_type, subject_unit, subject_status
     FROM subject
     WHERE subject_status = 'Active'
     ORDER BY subject_code"
);

$course_subjects = fetch_all(
    $pdo,
    "SELECT cs.course_subject_id, cs.course_id, cs.subject_id, cs.year_level, cs.term_name, cs.course_subject_status
     FROM course_subject cs
     WHERE cs.course_subject_status = 'Active'"
);

$faculty_assignments = fetch_all(
    $pdo,
    "SELECT
        ta.teaching_assignment_id,
        ta.section_subject_offering_id,
        ta.faculty_id,
        ta.term_id,
        CASE
            WHEN ta.assignment_status = 'Active' AND sso.offering_status = 'Active' THEN 'Active'
            ELSE 'Inactive'
        END AS assignment_status,
        ta.assignment_status AS raw_assignment_status,
        sso.offering_status,
        f.faculty_number,
        f.full_name AS faculty_name,
        f.department_id AS faculty_department_id,
        fd.department_code AS faculty_department_code,
        fd.department_name AS faculty_department_name,
        s.subject_id,
        s.subject_code,
        s.subject_title,
        s.subject_type,
        sec.section_id,
        sec.section_name,
        sec.year_level,
        c.course_id,
        c.course_code,
        c.course_name,
        cd.department_id AS course_department_id,
        cd.department_code AS course_department_code,
        cd.department_name AS course_department_name,
        ay.academic_year_id,
        ay.academic_year_name,
        t.term_name
     FROM teaching_assignment ta
     INNER JOIN section_subject_offering sso
        ON sso.section_subject_offering_id = ta.section_subject_offering_id
     INNER JOIN section sec
        ON sec.section_id = sso.section_id
     INNER JOIN course c
        ON c.course_id = sec.course_id
     INNER JOIN department cd
        ON cd.department_id = c.department_id
     INNER JOIN subject s
        ON s.subject_id = sso.subject_id
     INNER JOIN faculty f
        ON f.faculty_id = ta.faculty_id
     INNER JOIN department fd
        ON fd.department_id = f.department_id
     INNER JOIN term t
        ON t.term_id = ta.term_id
     INNER JOIN academic_year ay
        ON ay.academic_year_id = t.academic_year_id
     ORDER BY ta.updated_at DESC, f.full_name, s.subject_code"
);

$section_enrollments = fetch_all(
    $pdo,
    "SELECT
        sec.section_id,
        sec.section_name,
        sec.year_level,
        c.course_id,
        c.course_code,
        c.course_name,
        d.department_id,
        d.department_code,
        d.department_name,
        t.term_id,
        t.term_name,
        ay.academic_year_id,
        ay.academic_year_name,
        CASE
            WHEN SUM(CASE WHEN sso.offering_status = 'Active' THEN 1 ELSE 0 END) > 0 THEN 'Active'
            ELSE 'Inactive'
        END AS enrollment_status,
        GROUP_CONCAT(
            DISTINCT CASE
                WHEN sso.offering_status = 'Active' THEN s.subject_id
            END
            ORDER BY s.subject_code
            SEPARATOR ','
        ) AS subject_ids,
        GROUP_CONCAT(
            DISTINCT CASE
                WHEN sso.offering_status = 'Active' THEN CONCAT(s.subject_code, ' - ', s.subject_title)
            END
            ORDER BY s.subject_code
            SEPARATOR '||'
        ) AS subject_list,
        GROUP_CONCAT(
            DISTINCT CASE
                WHEN sso.offering_status = 'Active' THEN CONCAT(s.subject_code, '::', s.subject_title, '::', COALESCE(f.full_name, 'Unassigned'))
            END
            ORDER BY s.subject_code
            SEPARATOR '||'
        ) AS subject_detail_list
     FROM section_subject_offering sso
     INNER JOIN section sec
        ON sec.section_id = sso.section_id
     INNER JOIN course c
        ON c.course_id = sec.course_id
     INNER JOIN department d
        ON d.department_id = c.department_id
     INNER JOIN subject s
        ON s.subject_id = sso.subject_id
     INNER JOIN term t
        ON t.term_id = sso.term_id
     INNER JOIN academic_year ay
        ON ay.academic_year_id = t.academic_year_id
     LEFT JOIN teaching_assignment ta
        ON ta.section_subject_offering_id = sso.section_subject_offering_id
        AND ta.assignment_status = 'Active'
     LEFT JOIN faculty f
        ON f.faculty_id = ta.faculty_id
     GROUP BY sec.section_id, sec.section_name, sec.year_level, c.course_id, c.course_code, c.course_name,
              d.department_id, d.department_code, d.department_name, t.term_id, t.term_name,
              ay.academic_year_id, ay.academic_year_name
     ORDER BY ay.academic_year_name DESC, t.term_name, c.course_code, sec.year_level, sec.section_name"
);

$flash_type = $_SESSION["flash_type"] ?? "";
$flash_message = $_SESSION["flash_message"] ?? "";
unset($_SESSION["flash_type"], $_SESSION["flash_message"]);

$active_tab = $_GET["panel"] ?? "faculty";
if (!in_array($active_tab, ["faculty", "section"], true)) {
    $active_tab = "faculty";
}

$js_departments = json_encode($departments);
$js_courses = json_encode($courses);
$js_academic_years = json_encode($academic_years);
$js_terms = json_encode($terms);
$js_faculties = json_encode($faculties);
$js_sections = json_encode($sections);
$js_subjects = json_encode($subjects);
$js_course_subjects = json_encode($course_subjects);
$js_faculty_assignments = json_encode($faculty_assignments);
$js_section_enrollments = json_encode($section_enrollments);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assignment Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assignment-management.css">
</head>
<body>
<div class="admin-layout">
    <aside class="sidebar">
        <div class="brand">
            <img src="../img/eastgate_college_logo.png" alt="Eastgate College Logo">
            <h1>Student Evaluation for Teacher</h1>
            <p>System Administrator</p>
        </div>

        <nav class="nav-menu">
            <a class="nav-link<?php echo active_class('admin_dashboard.php'); ?>" href="admin_dashboard.php"><?php echo icon_svg("dashboard"); ?>Dashboard</a>
            <a class="nav-link<?php echo active_class('student_management.php'); ?>" href="student_management.php"><?php echo icon_svg("students"); ?>Student Management</a>
            <a class="nav-link<?php echo active_class('faculty_management.php'); ?>" href="faculty_management.php"><?php echo icon_svg("faculty"); ?>Faculty Management</a>
            <a class="nav-link<?php echo active_class('academic_structure.php'); ?>" href="academic_structure.php"><?php echo icon_svg("book"); ?>Academic Structure</a>
            <a class="nav-link<?php echo active_class('assignment_management.php'); ?>" href="assignment_management.php"><?php echo icon_svg("assignment"); ?>Assignment Management</a>
            <a class="nav-link<?php echo active_class('evaluation_setup.php'); ?>" href="evaluation_setup.php"><?php echo icon_svg("settings"); ?>Evaluation Setup</a>
            <a class="nav-link<?php echo active_class('submission_monitoring.php'); ?>" href="submission_monitoring.php"><?php echo icon_svg("clipboard"); ?>Submission Monitoring</a>
            <a class="nav-link<?php echo active_class('reports.php'); ?>" href="reports.php"><?php echo icon_svg("reports"); ?>Reports</a>
            <a class="nav-link<?php echo active_class('announcements.php'); ?>" href="announcements.php"><?php echo icon_svg("announcement"); ?>Announcements</a>
            <a class="nav-link<?php echo active_class('settings.php'); ?>" href="settings.php"><?php echo icon_svg("settings"); ?>Settings</a>
            <a class="nav-link" href="#"><?php echo icon_svg("moon"); ?>Dark Mode</a>
        </nav>

        <div class="sidebar-bottom">
            <a class="logout-link" href="../login/login_page.php"><?php echo icon_svg("logout"); ?>Logout</a>
        </div>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <div>
                <h1>Assignment Management</h1>
                <p>Assign faculty to courses and enroll sections</p>
            </div>
        </div>

        <div class="tab-card">
            <button type="button" class="tab-button <?php echo $active_tab === 'faculty' ? 'active' : ''; ?>" data-tab-button="faculty">Faculty to Course</button>
            <button type="button" class="tab-button <?php echo $active_tab === 'section' ? 'active' : ''; ?>" data-tab-button="section">Section to Course</button>
        </div>

        <section class="panel <?php echo $active_tab === 'faculty' ? 'active' : ''; ?>" id="facultyPanel">
            <div class="panel-heading">
                <h2>Faculty Assignments <span id="facultyAssignmentCount">(<?php echo count($faculty_assignments); ?>)</span></h2>
                <button type="button" class="primary-button" onclick="openModal('assignFacultyModal')">
                    <?php echo icon_svg("plus"); ?> Assign Faculty
                </button>
            </div>

            <div class="filter-card">
                <div class="filter-row search-clear-row">
                    <div class="search-box">
                        <?php echo icon_svg("search"); ?>
                        <input type="text" id="facultySearch" placeholder="Search by faculty name, course, program, or section...">
                    </div>
                    <button type="button" class="clear-button" onclick="clearFacultyFilters()">Clear</button>
                </div>

                <div class="filter-grid faculty-filter-grid">
                    <select id="facultyDepartmentFilter">
                        <option value="">All Colleges</option>
                        <?php foreach ($departments as $department): ?>
                            <option value="<?php echo h($department["department_id"]); ?>"><?php echo h($department["department_name"]); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select id="facultyCourseFilter">
                        <option value="">All Programs</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo h($course["course_id"]); ?>"><?php echo h($course["course_code"]); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select id="facultyAcademicYearFilter">
                        <option value="">All Academic Years</option>
                        <?php foreach ($academic_years as $academic_year): ?>
                            <option value="<?php echo h($academic_year["academic_year_id"]); ?>"><?php echo h($academic_year["academic_year_name"]); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select id="facultyYearLevelFilter">
                        <option value="">All Year Levels</option>
                        <?php for ($i = 1; $i <= 6; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo year_label($i); ?></option>
                        <?php endfor; ?>
                    </select>

                    <select id="facultySemesterFilter">
                        <option value="">All Semesters</option>
                        <option value="First Semester">First Semester</option>
                        <option value="Second Semester">Second Semester</option>
                        <option value="Summer">Summer</option>
                    </select>

                    <select id="facultySubjectFilter">
                        <option value="">All Courses</option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?php echo h($subject["subject_id"]); ?>"><?php echo h($subject["subject_code"]); ?> - <?php echo h($subject["subject_title"]); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select id="facultyMemberFilter">
                        <option value="">All Faculty</option>
                        <?php foreach ($faculties as $faculty): ?>
                            <option value="<?php echo h($faculty["faculty_id"]); ?>"><?php echo h($faculty["full_name"]); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-footer">
                    <p id="facultyShowingText">Showing <?php echo count($faculty_assignments); ?> of <?php echo count($faculty_assignments); ?> faculty assignments</p>
                    <span class="filter-indicator" id="facultyFilterIndicator"><?php echo icon_svg("filter"); ?> No filters applied</span>
                </div>
            </div>

            <div class="table-card">
                <table class="data-table" id="facultyAssignmentTable">
                    <thead>
                    <tr>
                        <th>Faculty</th>
                        <th>Program</th>
                        <th>Program</th>
                        <th>Section</th>
                        <th>Year Level</th>
                        <th>Semester</th>
                        <th>Academic Year</th>
                        <th>Status</th>
                        <th class="actions-col">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($faculty_assignments)): ?>
                        <tr class="empty-row">
                            <td colspan="9">No faculty assignments found.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($faculty_assignments as $assignment): ?>
                        <tr
                            data-assignment-row
                            data-id="<?php echo h($assignment["teaching_assignment_id"]); ?>"
                            data-department="<?php echo h($assignment["course_department_id"]); ?>"
                            data-course="<?php echo h($assignment["course_id"]); ?>"
                            data-academic-year="<?php echo h($assignment["academic_year_id"]); ?>"
                            data-year-level="<?php echo h($assignment["year_level"]); ?>"
                            data-semester="<?php echo h($assignment["term_name"]); ?>"
                            data-subject="<?php echo h($assignment["subject_id"]); ?>"
                            data-faculty="<?php echo h($assignment["faculty_id"]); ?>"
                            data-search="<?php echo h(strtolower($assignment["faculty_name"] . " " . $assignment["faculty_number"] . " " . $assignment["subject_code"] . " " . $assignment["subject_title"] . " " . $assignment["course_code"] . " " . $assignment["section_name"])); ?>"
                        >
                            <td>
                                <strong><?php echo h($assignment["faculty_name"]); ?></strong>
                                <span><?php echo h($assignment["faculty_number"]); ?></span>
                            </td>
                            <td><?php echo h($assignment["subject_code"]); ?> - <?php echo h($assignment["subject_title"]); ?></td>
                            <td><?php echo h($assignment["course_code"]); ?></td>
                            <td><?php echo h($assignment["section_name"]); ?></td>
                            <td><?php echo year_label($assignment["year_level"]); ?></td>
                            <td><?php echo h(term_short_label($assignment["term_name"])); ?></td>
                            <td><?php echo h($assignment["academic_year_name"]); ?></td>
                            <td><span class="status-badge <?php echo strtolower($assignment["assignment_status"]); ?>"><?php echo h(strtolower($assignment["assignment_status"])); ?></span></td>
                            <td class="actions">
                                <div class="action-buttons">
                                    <button type="button" class="icon-button view" title="View" onclick="viewAssignment(<?php echo (int)$assignment["teaching_assignment_id"]; ?>)">
                                        <?php echo icon_svg("eye"); ?>
                                    </button>

                                    <button type="button" class="icon-button edit" title="Edit" onclick="editAssignment(<?php echo (int)$assignment["teaching_assignment_id"]; ?>)">
                                        <?php echo icon_svg("edit"); ?>
                                    </button>

                                    <?php if ($assignment["assignment_status"] === "Active"): ?>
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this assignment?');">
                                            <input type="hidden" name="form_action" value="delete_assignment">
                                            <input type="hidden" name="delete_assignment_id" value="<?php echo h($assignment["teaching_assignment_id"]); ?>">
                                            <button type="submit" class="icon-button delete" title="Delete">
                                                <?php echo icon_svg("trash"); ?>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST">
                                            <input type="hidden" name="form_action" value="restore_assignment">
                                            <input type="hidden" name="restore_assignment_id" value="<?php echo h($assignment["teaching_assignment_id"]); ?>">
                                            <button type="submit" class="icon-button restore" title="Activate">
                                                <?php echo icon_svg("check"); ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="no-records" id="facultyNoRecords">No faculty assignments found matching your search criteria.</div>
            </div>
        </section>

        <section class="panel <?php echo $active_tab === 'section' ? 'active' : ''; ?>" id="sectionPanel">
            <div class="panel-heading">
                <h2>Section Enrollments <span id="sectionEnrollmentCount">(<?php echo count($section_enrollments); ?>)</span></h2>
                <button type="button" class="primary-button" onclick="openModal('enrollSectionModal')">
                    <?php echo icon_svg("plus"); ?> Enroll Section
                </button>
            </div>

            <div class="filter-card">
                <div class="filter-row search-clear-row">
                    <div class="search-box">
                        <?php echo icon_svg("search"); ?>
                        <input type="text" id="sectionSearch" placeholder="Search by section, program, or course...">
                    </div>
                    <button type="button" class="clear-button" onclick="clearSectionFilters()">Clear</button>
                </div>

                <div class="filter-grid section-filter-grid">
                    <select id="sectionDepartmentFilter">
                        <option value="">All Colleges</option>
                        <?php foreach ($departments as $department): ?>
                            <option value="<?php echo h($department["department_id"]); ?>"><?php echo h($department["department_name"]); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select id="sectionCourseFilter">
                        <option value="">All Programs</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo h($course["course_id"]); ?>"><?php echo h($course["course_code"]); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select id="sectionAcademicYearFilter">
                        <option value="">All Academic Years</option>
                        <?php foreach ($academic_years as $academic_year): ?>
                            <option value="<?php echo h($academic_year["academic_year_id"]); ?>"><?php echo h($academic_year["academic_year_name"]); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select id="sectionYearLevelFilter">
                        <option value="">All Year Levels</option>
                        <?php for ($i = 1; $i <= 6; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo year_label($i); ?></option>
                        <?php endfor; ?>
                    </select>

                    <select id="sectionSemesterFilter">
                        <option value="">All Semesters</option>
                        <option value="First Semester">First Semester</option>
                        <option value="Second Semester">Second Semester</option>
                        <option value="Summer">Summer</option>
                    </select>

                    <select id="sectionStatusFilter">
                        <option value="">All Status</option>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>

                <div class="filter-footer">
                    <p id="sectionShowingText">Showing <?php echo count($section_enrollments); ?> of <?php echo count($section_enrollments); ?> section enrollments</p>
                    <span class="filter-indicator" id="sectionFilterIndicator"><?php echo icon_svg("filter"); ?> No filters applied</span>
                </div>
            </div>

            <div class="table-card">
                <table class="data-table" id="sectionEnrollmentTable">
                    <thead>
                    <tr>
                        <th>Program</th>
                        <th>Section</th>
                        <th>Courses Taken by the Section</th>
                        <th>Year Level</th>
                        <th>Semester</th>
                        <th>Academic Year</th>
                        <th>Status</th>
                        <th class="actions-col">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($section_enrollments)): ?>
                        <tr class="empty-row">
                            <td colspan="8">No section enrollments found.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($section_enrollments as $enrollment): ?>
                        <tr
                            data-section-row
                            data-section="<?php echo h($enrollment["section_id"]); ?>"
                            data-term="<?php echo h($enrollment["term_id"]); ?>"
                            data-department="<?php echo h($enrollment["department_id"]); ?>"
                            data-course="<?php echo h($enrollment["course_id"]); ?>"
                            data-academic-year="<?php echo h($enrollment["academic_year_id"]); ?>"
                            data-year-level="<?php echo h($enrollment["year_level"]); ?>"
                            data-semester="<?php echo h($enrollment["term_name"]); ?>"
                            data-status="<?php echo h($enrollment["enrollment_status"]); ?>"
                            data-search="<?php echo h(strtolower($enrollment["course_code"] . " " . $enrollment["section_name"] . " " . str_replace('||', ' ', (string)$enrollment["subject_list"]))); ?>"
                        >
                            <td><?php echo h($enrollment["course_code"]); ?></td>
                            <td><strong><?php echo h($enrollment["section_name"]); ?></strong></td>
                            <td class="subjects-taken-cell">
                                <?php echo format_subject_list($enrollment["subject_list"]); ?>
                            </td>
                            <td><?php echo year_label($enrollment["year_level"]); ?></td>
                            <td><?php echo h(term_short_label($enrollment["term_name"])); ?></td>
                            <td><?php echo h($enrollment["academic_year_name"]); ?></td>
                            <td><span class="status-badge <?php echo strtolower($enrollment["enrollment_status"]); ?>"><?php echo h(strtolower($enrollment["enrollment_status"])); ?></span></td>
                            <td class="actions">
                                <div class="action-buttons">
                                    <button type="button" class="icon-button view" title="View" onclick="viewEnrollment(<?php echo (int)$enrollment["section_id"]; ?>, <?php echo (int)$enrollment["term_id"]; ?>)">
                                        <?php echo icon_svg("eye"); ?>
                                    </button>

                                    <button type="button" class="icon-button edit" title="Edit" onclick="editEnrollment(<?php echo (int)$enrollment["section_id"]; ?>, <?php echo (int)$enrollment["term_id"]; ?>)">
                                        <?php echo icon_svg("edit"); ?>
                                    </button>

                                    <?php if ($enrollment["enrollment_status"] === "Active"): ?>
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this section enrollment?');">
                                            <input type="hidden" name="form_action" value="delete_enrollment">
                                            <input type="hidden" name="delete_enrollment_section_id" value="<?php echo h($enrollment["section_id"]); ?>">
                                            <input type="hidden" name="delete_enrollment_term_id" value="<?php echo h($enrollment["term_id"]); ?>">
                                            <button type="submit" class="icon-button delete" title="Delete">
                                                <?php echo icon_svg("trash"); ?>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST">
                                            <input type="hidden" name="form_action" value="restore_enrollment">
                                            <input type="hidden" name="restore_enrollment_section_id" value="<?php echo h($enrollment["section_id"]); ?>">
                                            <input type="hidden" name="restore_enrollment_term_id" value="<?php echo h($enrollment["term_id"]); ?>">
                                            <button type="submit" class="icon-button restore" title="Activate">
                                                <?php echo icon_svg("check"); ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="no-records" id="sectionNoRecords">No section enrollments found matching your search criteria.</div>
            </div>
        </section>
    </main>
</div>

<?php if ($flash_message): ?>
    <div class="toast-message <?php echo h($flash_type); ?>" id="toastMessage"><?php echo h($flash_message); ?></div>
<?php endif; ?>

<div class="modal-overlay" id="assignFacultyModal">
    <div class="modal-card large-modal">
        <div class="modal-header">
            <h3>Assign Faculty to Course</h3>
            <button type="button" class="modal-close" onclick="closeModal('assignFacultyModal')"><?php echo icon_svg("x"); ?></button>
        </div>

        <form method="POST" class="modal-form" id="assignFacultyForm">
            <input type="hidden" name="form_action" value="assign_faculty">

            <div class="modal-body">
                <label>College</label>
                <select name="assign_department_id" id="assignDepartment" required>
                    <option value="">Select College</option>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?php echo h($department["department_id"]); ?>"><?php echo h($department["department_name"]); ?> (<?php echo h($department["department_code"]); ?>)</option>
                    <?php endforeach; ?>
                </select>

                <label>Faculty Member</label>
                <select name="assign_faculty_id" id="assignFaculty" required>
                    <option value="">Select college first</option>
                </select>

                <label>Academic Year</label>
                <select name="assign_academic_year_id" id="assignAcademicYear" required>
                    <option value="">Select Academic Year</option>
                    <?php foreach ($academic_years as $academic_year): ?>
                        <option value="<?php echo h($academic_year["academic_year_id"]); ?>"><?php echo h($academic_year["academic_year_name"]); ?></option>
                    <?php endforeach; ?>
                </select>

                <div class="two-col">
                    <div>
                        <label>Year Level</label>
                        <select name="assign_year_level" id="assignYearLevel" required>
                            <option value="">Select Year Level</option>
                            <?php for ($i = 1; $i <= 6; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo year_label($i); ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div>
                        <label>Semester</label>
                        <select name="assign_semester" id="assignSemester" required>
                            <option value="">Select Semester</option>
                            <option value="First Semester">First Semester</option>
                            <option value="Second Semester">Second Semester</option>
                            <option value="Summer">Summer</option>
                        </select>
                    </div>
                </div>

                <label class="checkbox-inline">
                    <input type="checkbox" id="assignGeneralOnly">
                    General Education courses
                </label>

                <label>Program(s)</label>
                <div class="checkbox-list tall-list" id="assignCourseList">
                    <p class="muted-box">Select college first</p>
                </div>
                <small id="assignCourseCount">0 program(s) selected</small>

                <label>Course(s)</label>
                <div class="checkbox-list tall-list" id="assignSubjectList">
                    <p class="muted-box">Select program, year level, and semester to see courses</p>
                </div>
                <small id="assignSubjectCount">0 course(s) selected</small>
            </div>

            <div class="modal-footer">
                <button type="button" class="secondary-button" onclick="closeModal('assignFacultyModal')">Cancel</button>
                <button type="submit" class="primary-button">Assign Faculty</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="editAssignmentModal">
    <div class="modal-card">
        <div class="modal-header">
            <h3>Edit Faculty Assignment</h3>
            <button type="button" class="modal-close" onclick="closeModal('editAssignmentModal')"><?php echo icon_svg("x"); ?></button>
        </div>

        <form method="POST" class="modal-form" id="editAssignmentForm">
            <input type="hidden" name="form_action" value="edit_assignment">
            <input type="hidden" name="edit_assignment_id" id="editAssignmentId">
            <input type="hidden" name="edit_assignment_section_id" id="editAssignmentSectionId">
            <input type="hidden" name="edit_assignment_term_id" id="editAssignmentTermId">

            <div class="modal-body">
                <label>College</label>
                <select id="editAssignmentDepartment" disabled></select>

                <label>Faculty Member</label>
                <select name="edit_assignment_faculty_id" id="editAssignmentFaculty" required></select>

                <label>Program</label>
                <select name="edit_assignment_subject_id" id="editAssignmentSubject" required></select>

                <div class="two-col">
                    <div>
                        <label>Year Level</label>
                        <input type="text" id="editAssignmentYearLevel" readonly>
                    </div>

                    <div>
                        <label>Semester</label>
                        <input type="text" id="editAssignmentSemester" readonly>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="secondary-button" onclick="closeModal('editAssignmentModal')">Cancel</button>
                <button type="submit" class="primary-button">Update Assignment</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="viewAssignmentModal">
    <div class="modal-card small-modal assignment-details-modal">
        <div class="modal-header">
            <h3>Assignment Details</h3>
            <button type="button" class="modal-close" onclick="closeModal('viewAssignmentModal')"><?php echo icon_svg("x"); ?></button>
        </div>

        <div class="details-body" id="assignmentDetailsBody"></div>

        <div class="modal-footer">
            <button type="button" class="secondary-button" onclick="closeModal('viewAssignmentModal')">Close</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="enrollSectionModal">
    <div class="modal-card large-modal">
        <div class="modal-header">
            <h3>Enroll Section to Courses</h3>
            <button type="button" class="modal-close" onclick="closeModal('enrollSectionModal')"><?php echo icon_svg("x"); ?></button>
        </div>

        <form method="POST" class="modal-form" id="enrollSectionForm">
            <input type="hidden" name="form_action" value="enroll_section">

            <div class="modal-body">
                <label>College</label>
                <select name="enroll_department_id" id="enrollDepartment" required>
                    <option value="">Select College</option>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?php echo h($department["department_id"]); ?>"><?php echo h($department["department_name"]); ?> (<?php echo h($department["department_code"]); ?>)</option>
                    <?php endforeach; ?>
                </select>

                <label>Program</label>
                <select name="enroll_course_id" id="enrollCourse" required>
                    <option value="">Select college first</option>
                </select>

                <label>Academic Year</label>
                <select name="enroll_academic_year_id" id="enrollAcademicYear" required>
                    <option value="">Select Academic Year</option>
                    <?php foreach ($academic_years as $academic_year): ?>
                        <option value="<?php echo h($academic_year["academic_year_id"]); ?>"><?php echo h($academic_year["academic_year_name"]); ?></option>
                    <?php endforeach; ?>
                </select>

                <div class="two-col">
                    <div>
                        <label>Year Level</label>
                        <select name="enroll_year_level" id="enrollYearLevel" required>
                            <option value="">Select Year Level</option>
                            <?php for ($i = 1; $i <= 6; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo year_label($i); ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div>
                        <label>Semester</label>
                        <select name="enroll_semester" id="enrollSemester" required>
                            <option value="">Select Semester</option>
                            <option value="First Semester">First Semester</option>
                            <option value="Second Semester">Second Semester</option>
                            <option value="Summer">Summer</option>
                        </select>
                    </div>
                </div>

                <label>Section(s)</label>
                <div class="checkbox-list tall-list" id="enrollSectionList">
                    <p class="muted-box">Select program and year level to see sections</p>
                </div>
                <small id="enrollSectionCount">0 section(s) selected</small>

                <label>Course Type</label>
                <select id="enrollSubjectType">
                    <option value="Program">Program Courses Only</option>
                    <option value="General Education">General Education Courses Only</option>
                    <option value="Both">Program and General Education Courses</option>
                </select>

                <label>Course(s)</label>
                <div class="checkbox-list tall-list" id="enrollSubjectList">
                    <p class="muted-box">Select program, year level, and semester to see courses</p>
                </div>
                <small id="enrollSubjectCount">0 course(s) selected</small>
            </div>

            <div class="modal-footer">
                <button type="button" class="secondary-button" onclick="closeModal('enrollSectionModal')">Cancel</button>
                <button type="submit" class="primary-button">Enroll Section</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="editEnrollmentModal">
    <div class="modal-card large-modal">
        <div class="modal-header">
            <h3>Edit Section Enrollment</h3>
            <button type="button" class="modal-close" onclick="closeModal('editEnrollmentModal')"><?php echo icon_svg("x"); ?></button>
        </div>

        <form method="POST" class="modal-form" id="editEnrollmentForm">
            <input type="hidden" name="form_action" value="edit_enrollment">
            <input type="hidden" name="edit_enrollment_section_id" id="editEnrollmentSectionId">
            <input type="hidden" name="edit_enrollment_term_id" id="editEnrollmentTermId">

            <div class="modal-body">
                <label>College</label>
                <input type="text" id="editEnrollmentDepartment" readonly>

                <label>Program</label>
                <input type="text" id="editEnrollmentCourse" readonly>

                <div class="two-col">
                    <div>
                        <label>Year Level</label>
                        <input type="text" id="editEnrollmentYearLevel" readonly>
                    </div>

                    <div>
                        <label>Semester</label>
                        <input type="text" id="editEnrollmentSemester" readonly>
                    </div>
                </div>

                <label>Section</label>
                <input type="text" id="editEnrollmentSection" readonly>

                <label>Course Type</label>
                <select id="editEnrollmentSubjectType">
                    <option value="Program">Program Courses Only</option>
                    <option value="General Education">General Education Courses Only</option>
                    <option value="Both">Program and General Education Courses</option>
                </select>

                <label>Course(s)</label>
                <div class="checkbox-list tall-list" id="editEnrollmentSubjectList"></div>
                <small id="editEnrollmentSubjectCount">0 course(s) selected</small>
            </div>

            <div class="modal-footer">
                <button type="button" class="secondary-button" onclick="closeModal('editEnrollmentModal')">Cancel</button>
                <button type="submit" class="primary-button">Update Enrollment</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="viewEnrollmentModal">
    <div class="modal-card small-modal enrollment-details-modal">
        <div class="modal-header">
            <h3>Enrollment Details</h3>
            <button type="button" class="modal-close" onclick="closeModal('viewEnrollmentModal')"><?php echo icon_svg("x"); ?></button>
        </div>

        <div class="details-body" id="enrollmentDetailsBody"></div>

        <div class="modal-footer">
            <button type="button" class="secondary-button" onclick="closeModal('viewEnrollmentModal')">Close</button>
        </div>
    </div>
</div>

<script>
const DATA = {
    departments: <?php echo $js_departments ?: "[]"; ?>,
    courses: <?php echo $js_courses ?: "[]"; ?>,
    academicYears: <?php echo $js_academic_years ?: "[]"; ?>,
    terms: <?php echo $js_terms ?: "[]"; ?>,
    faculties: <?php echo $js_faculties ?: "[]"; ?>,
    sections: <?php echo $js_sections ?: "[]"; ?>,
    subjects: <?php echo $js_subjects ?: "[]"; ?>,
    courseSubjects: <?php echo $js_course_subjects ?: "[]"; ?>,
    facultyAssignments: <?php echo $js_faculty_assignments ?: "[]"; ?>,
    sectionEnrollments: <?php echo $js_section_enrollments ?: "[]"; ?>
};

function qs(selector) {
    return document.querySelector(selector);
}

function qsa(selector) {
    return Array.from(document.querySelectorAll(selector));
}

function yearLabel(year) {
    year = Number(year);
    if (year === 1) return "1st Year";
    if (year === 2) return "2nd Year";
    if (year === 3) return "3rd Year";
    if (year === 4) return "4th Year";
    if (year === 5) return "5th Year";
    if (year === 6) return "6th Year";
    return year + " Year";
}

function shortSemester(term) {
    if (term === "First Semester") return "1st Semester";
    if (term === "Second Semester") return "2nd Semester";
    return term;
}

function escapeHtml(value) {
    return String(value ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}

function openModal(id) {
    const modal = document.getElementById(id);

    if (modal) {
        modal.classList.add("show");
        document.body.classList.add("modal-open");
    }
}

function closeModal(id) {
    const modal = document.getElementById(id);

    if (modal) {
        modal.classList.remove("show");
        document.body.classList.remove("modal-open");
    }
}

qsa(".modal-overlay").forEach(modal => {
    modal.addEventListener("click", function(event) {
        if (event.target === modal) {
            closeModal(modal.id);
        }
    });
});

qsa("[data-tab-button]").forEach(button => {
    button.addEventListener("click", () => {
        const panel = button.dataset.tabButton;

        qsa("[data-tab-button]").forEach(btn => btn.classList.remove("active"));
        button.classList.add("active");

        qs("#facultyPanel").classList.toggle("active", panel === "faculty");
        qs("#sectionPanel").classList.toggle("active", panel === "section");

        const url = new URL(window.location.href);
        url.searchParams.delete("tab");
        url.searchParams.set("panel", panel);
        window.history.replaceState({}, "", url);
    });
});

function setOptions(select, rows, valueKey, labelBuilder, placeholder) {
    select.innerHTML = `<option value="">${placeholder}</option>`;

    rows.forEach(row => {
        const option = document.createElement("option");
        option.value = row[valueKey];
        option.textContent = labelBuilder(row);
        select.appendChild(option);
    });
}

function selectedValues(containerSelector) {
    return qsa(`${containerSelector} input[type="checkbox"]:checked`).map(input => String(input.value));
}

function renderCheckboxList(container, rows, name, valueKey, labelBuilder, subBuilder = null, checkedValues = []) {
    container.innerHTML = "";

    if (!rows.length) {
        container.innerHTML = `<p class="muted-box">No records found.</p>`;
        return;
    }

    rows.forEach(row => {
        const label = document.createElement("label");
        label.className = "checkbox-item";

        const input = document.createElement("input");
        input.type = "checkbox";
        input.name = name;
        input.value = row[valueKey];

        if (checkedValues.map(String).includes(String(row[valueKey]))) {
            input.checked = true;
        }

        const textWrap = document.createElement("span");
        textWrap.innerHTML = `<strong>${escapeHtml(labelBuilder(row))}</strong>`;

        if (subBuilder) {
            textWrap.innerHTML += `<small>${escapeHtml(subBuilder(row))}</small>`;
        }

        label.appendChild(input);
        label.appendChild(textWrap);
        container.appendChild(label);
    });
}

function isGeneralSubject(subject) {
    return String(subject.subject_type || "").toLowerCase().includes("general")
        || String(subject.subject_code || "").toUpperCase().startsWith("GE");
}

function getTermId(academicYearId, semester) {
    const term = DATA.terms.find(t => String(t.academic_year_id) === String(academicYearId) && t.term_name === semester);
    return term ? String(term.term_id) : "";
}

function getCourseSubjects(courseIds, yearLevel, semester, subjectType) {
    const courseIdSet = courseIds.map(String);

    let validSubjectIds = DATA.courseSubjects
        .filter(cs => courseIdSet.includes(String(cs.course_id)))
        .filter(cs => String(cs.year_level) === String(yearLevel))
        .filter(cs => cs.term_name === semester)
        .map(cs => String(cs.subject_id));

    validSubjectIds = [...new Set(validSubjectIds)];

    return DATA.subjects.filter(subject => {
        const inCourse = validSubjectIds.includes(String(subject.subject_id));
        const isGeneral = isGeneralSubject(subject);

        if (subjectType === "General Education") {
            return inCourse && isGeneral;
        }

        if (subjectType === "Program") {
            return inCourse && !isGeneral;
        }

        return inCourse;
    });
}

function updateAssignFacultyLists() {
    const departmentId = qs("#assignDepartment").value;

    const facultyRows = DATA.faculties.filter(f => String(f.department_id) === String(departmentId));
    setOptions(qs("#assignFaculty"), facultyRows, "faculty_id", f => `${f.full_name} (${f.faculty_number})`, "Select Faculty Member");

    const courseRows = DATA.courses.filter(c => String(c.department_id) === String(departmentId));
    renderCheckboxList(
        qs("#assignCourseList"),
        courseRows,
        "assign_course_ids[]",
        "course_id",
        c => `${c.course_code} - ${c.course_name}`
    );

    qsa('#assignCourseList input[type="checkbox"]').forEach(input => {
        input.addEventListener("change", updateAssignSubjectList);
    });

    updateAssignSubjectList();
}

function updateAssignSubjectList() {
    const selectedCourseIds = selectedValues("#assignCourseList");
    const yearLevel = qs("#assignYearLevel").value;
    const semester = qs("#assignSemester").value;
    const generalOnly = qs("#assignGeneralOnly").checked;

    let subjectRows = [];

    if (selectedCourseIds.length && yearLevel && semester) {
        subjectRows = getCourseSubjects(
            selectedCourseIds,
            yearLevel,
            semester,
            generalOnly ? "General Education" : "Both"
        );
    }

    renderCheckboxList(
        qs("#assignSubjectList"),
        subjectRows,
        "assign_subject_ids[]",
        "subject_id",
        s => `${s.subject_code} - ${s.subject_title}`,
        s => `${s.subject_type || "Program"} course`
    );

    qsa('#assignSubjectList input[type="checkbox"]').forEach(input => {
        input.addEventListener("change", () => updateCheckedCount("#assignSubjectList", "#assignSubjectCount", "course"));
    });

    updateCheckedCount("#assignCourseList", "#assignCourseCount", "program");
    updateCheckedCount("#assignSubjectList", "#assignSubjectCount", "course");
}

function updateEnrollCourseList() {
    const departmentId = qs("#enrollDepartment").value;
    const courseRows = DATA.courses.filter(c => String(c.department_id) === String(departmentId));

    setOptions(qs("#enrollCourse"), courseRows, "course_id", c => `${c.course_code} - ${c.course_name}`, "Select Program");
    updateEnrollLists();
}

function updateEnrollLists() {
    const courseId = qs("#enrollCourse").value;
    const academicYearId = qs("#enrollAcademicYear").value;
    const yearLevel = qs("#enrollYearLevel").value;
    const semester = qs("#enrollSemester").value;
    const termId = getTermId(academicYearId, semester);
    const subjectType = qs("#enrollSubjectType").value;

    const sectionRows = DATA.sections.filter(section => {
        return String(section.course_id) === String(courseId)
            && String(section.year_level) === String(yearLevel)
            && String(section.term_id) === String(termId);
    });

    renderCheckboxList(
        qs("#enrollSectionList"),
        sectionRows,
        "enroll_section_ids[]",
        "section_id",
        s => s.section_name
    );

    const subjectRows = courseId && yearLevel && semester
        ? getCourseSubjects([courseId], yearLevel, semester, subjectType)
        : [];

    renderCheckboxList(
        qs("#enrollSubjectList"),
        subjectRows,
        "enroll_subject_ids[]",
        "subject_id",
        s => `${s.subject_code} - ${s.subject_title}`,
        s => `${s.subject_type || "Program"} course`
    );

    qsa('#enrollSectionList input[type="checkbox"]').forEach(input => {
        input.addEventListener("change", () => updateCheckedCount("#enrollSectionList", "#enrollSectionCount", "section"));
    });

    qsa('#enrollSubjectList input[type="checkbox"]').forEach(input => {
        input.addEventListener("change", () => updateCheckedCount("#enrollSubjectList", "#enrollSubjectCount", "course"));
    });

    updateCheckedCount("#enrollSectionList", "#enrollSectionCount", "section");
    updateCheckedCount("#enrollSubjectList", "#enrollSubjectCount", "course");
}

function updateCheckedCount(listSelector, textSelector, label) {
    const count = selectedValues(listSelector).length;
    const target = qs(textSelector);

    if (target) {
        target.textContent = `${count} ${label}(s) selected`;
    }
}

["assignDepartment", "assignAcademicYear", "assignYearLevel", "assignSemester", "assignGeneralOnly"].forEach(id => {
    const element = qs("#" + id);
    if (element) {
        element.addEventListener("change", updateAssignFacultyLists);
    }
});

["enrollCourse", "enrollAcademicYear", "enrollYearLevel", "enrollSemester", "enrollSubjectType"].forEach(id => {
    const element = qs("#" + id);
    if (element) {
        element.addEventListener("change", updateEnrollLists);
    }
});

qs("#enrollDepartment")?.addEventListener("change", updateEnrollCourseList);

function filterFacultyAssignments() {
    const search = qs("#facultySearch").value.trim().toLowerCase();
    const department = qs("#facultyDepartmentFilter").value;
    const course = qs("#facultyCourseFilter").value;
    const academicYear = qs("#facultyAcademicYearFilter").value;
    const yearLevel = qs("#facultyYearLevelFilter").value;
    const semester = qs("#facultySemesterFilter").value;
    const subject = qs("#facultySubjectFilter").value;
    const faculty = qs("#facultyMemberFilter").value;

    const rows = qsa("[data-assignment-row]");
    let visible = 0;

    rows.forEach(row => {
        const match =
            (!search || row.dataset.search.includes(search)) &&
            (!department || row.dataset.department === department) &&
            (!course || row.dataset.course === course) &&
            (!academicYear || row.dataset.academicYear === academicYear) &&
            (!yearLevel || row.dataset.yearLevel === yearLevel) &&
            (!semester || row.dataset.semester === semester) &&
            (!subject || row.dataset.subject === subject) &&
            (!faculty || row.dataset.faculty === faculty);

        row.style.display = match ? "" : "none";

        if (match) {
            visible++;
        }
    });

    qs("#facultyAssignmentCount").textContent = `(${visible})`;
    qs("#facultyShowingText").textContent = `Showing ${visible} of ${rows.length} faculty assignments`;
    qs("#facultyNoRecords").style.display = visible === 0 ? "block" : "none";

    const hasFilter = search || department || course || academicYear || yearLevel || semester || subject || faculty;
    qs("#facultyFilterIndicator").innerHTML = hasFilter
        ? `<?php echo str_replace("\n", "", icon_svg("filter")); ?> Filters applied`
        : `<?php echo str_replace("\n", "", icon_svg("filter")); ?> No filters applied`;
}

function filterSectionEnrollments() {
    const search = qs("#sectionSearch").value.trim().toLowerCase();
    const department = qs("#sectionDepartmentFilter").value;
    const course = qs("#sectionCourseFilter").value;
    const academicYear = qs("#sectionAcademicYearFilter").value;
    const yearLevel = qs("#sectionYearLevelFilter").value;
    const semester = qs("#sectionSemesterFilter").value;
    const status = qs("#sectionStatusFilter").value;

    const rows = qsa("[data-section-row]");
    let visible = 0;

    rows.forEach(row => {
        const match =
            (!search || row.dataset.search.includes(search)) &&
            (!department || row.dataset.department === department) &&
            (!course || row.dataset.course === course) &&
            (!academicYear || row.dataset.academicYear === academicYear) &&
            (!yearLevel || row.dataset.yearLevel === yearLevel) &&
            (!semester || row.dataset.semester === semester) &&
            (!status || row.dataset.status === status);

        row.style.display = match ? "" : "none";

        if (match) {
            visible++;
        }
    });

    qs("#sectionEnrollmentCount").textContent = `(${visible})`;
    qs("#sectionShowingText").textContent = `Showing ${visible} of ${rows.length} section enrollments`;
    qs("#sectionNoRecords").style.display = visible === 0 ? "block" : "none";

    const hasFilter = search || department || course || academicYear || yearLevel || semester || status;
    qs("#sectionFilterIndicator").innerHTML = hasFilter
        ? `<?php echo str_replace("\n", "", icon_svg("filter")); ?> Filters applied`
        : `<?php echo str_replace("\n", "", icon_svg("filter")); ?> No filters applied`;
}

[
    "facultySearch",
    "facultyDepartmentFilter",
    "facultyCourseFilter",
    "facultyAcademicYearFilter",
    "facultyYearLevelFilter",
    "facultySemesterFilter",
    "facultySubjectFilter",
    "facultyMemberFilter"
].forEach(id => {
    const element = qs("#" + id);
    if (element) {
        element.addEventListener("input", filterFacultyAssignments);
        element.addEventListener("change", filterFacultyAssignments);
    }
});

[
    "sectionSearch",
    "sectionDepartmentFilter",
    "sectionCourseFilter",
    "sectionAcademicYearFilter",
    "sectionYearLevelFilter",
    "sectionSemesterFilter",
    "sectionStatusFilter"
].forEach(id => {
    const element = qs("#" + id);
    if (element) {
        element.addEventListener("input", filterSectionEnrollments);
        element.addEventListener("change", filterSectionEnrollments);
    }
});

function clearFacultyFilters() {
    [
        "facultySearch",
        "facultyDepartmentFilter",
        "facultyCourseFilter",
        "facultyAcademicYearFilter",
        "facultyYearLevelFilter",
        "facultySemesterFilter",
        "facultySubjectFilter",
        "facultyMemberFilter"
    ].forEach(id => qs("#" + id).value = "");

    filterFacultyAssignments();
}

function clearSectionFilters() {
    [
        "sectionSearch",
        "sectionDepartmentFilter",
        "sectionCourseFilter",
        "sectionAcademicYearFilter",
        "sectionYearLevelFilter",
        "sectionSemesterFilter",
        "sectionStatusFilter"
    ].forEach(id => qs("#" + id).value = "");

    filterSectionEnrollments();
}

function viewAssignment(id) {
    const row = DATA.facultyAssignments.find(item => Number(item.teaching_assignment_id) === Number(id));
    if (!row) return;

    qs("#assignmentDetailsBody").innerHTML = `
        <div class="details-form-grid">
            <div class="detail-field full">
                <label>Faculty Member</label>
                <div class="readonly-box faculty-member-box">
                    <strong>${escapeHtml(row.faculty_name)}</strong>
                    <span>(${escapeHtml(row.faculty_number)})</span>
                </div>
            </div>

            <div class="detail-field full">
                <label>College</label>
                <div class="readonly-box">${escapeHtml(row.faculty_department_name)} (${escapeHtml(row.faculty_department_code)})</div>
            </div>

            <div class="detail-field full">
                <label>Program</label>
                <div class="readonly-box">${escapeHtml(row.subject_code)} - ${escapeHtml(row.subject_title)}</div>
            </div>

            <div class="detail-field">
                <label>Program</label>
                <div class="readonly-box">${escapeHtml(row.course_code)}</div>
            </div>

            <div class="detail-field">
                <label>Section</label>
                <div class="readonly-box">${escapeHtml(row.section_name)}</div>
            </div>

            <div class="detail-field">
                <label>Year Level</label>
                <div class="readonly-box">${yearLabel(row.year_level)}</div>
            </div>

            <div class="detail-field">
                <label>Semester</label>
                <div class="readonly-box">${shortSemester(row.term_name)}</div>
            </div>

            <div class="detail-field">
                <label>Academic Year</label>
                <div class="readonly-box">${escapeHtml(row.academic_year_name)}</div>
            </div>

            <div class="detail-field">
                <label>Status</label>
                <div class="readonly-box status-box">
                    <span class="status-badge ${String(row.assignment_status).toLowerCase()}">${String(row.assignment_status).toLowerCase()}</span>
                </div>
            </div>
        </div>
    `;

    openModal("viewAssignmentModal");
}

function editAssignment(id) {
    const row = DATA.facultyAssignments.find(item => Number(item.teaching_assignment_id) === Number(id));
    if (!row) return;

    qs("#editAssignmentId").value = row.teaching_assignment_id;
    qs("#editAssignmentSectionId").value = row.section_id;
    qs("#editAssignmentTermId").value = row.term_id;
    qs("#editAssignmentYearLevel").value = yearLabel(row.year_level);
    qs("#editAssignmentSemester").value = shortSemester(row.term_name);

    setOptions(qs("#editAssignmentDepartment"), DATA.departments, "department_id", d => `${d.department_name} (${d.department_code})`, "Select College");
    qs("#editAssignmentDepartment").value = row.course_department_id;

    const facultyRows = DATA.faculties.filter(f => String(f.department_id) === String(row.course_department_id));
    setOptions(qs("#editAssignmentFaculty"), facultyRows, "faculty_id", f => `${f.full_name} (${f.faculty_number})`, "Select Faculty");
    qs("#editAssignmentFaculty").value = row.faculty_id;

    const subjectRows = getCourseSubjects([row.course_id], row.year_level, row.term_name, "Both");
    setOptions(qs("#editAssignmentSubject"), subjectRows, "subject_id", s => `${s.subject_code} - ${s.subject_title}`, "Select Course");
    qs("#editAssignmentSubject").value = row.subject_id;

    openModal("editAssignmentModal");
}

function viewEnrollment(sectionId, termId) {
    const row = DATA.sectionEnrollments.find(item => Number(item.section_id) === Number(sectionId) && Number(item.term_id) === Number(termId));
    if (!row) return;

    const detailItems = String(row.subject_detail_list || "")
        .split("||")
        .filter(Boolean)
        .map(item => {
            const parts = item.split("::");
            return `
                <li>
                    <strong>${escapeHtml(parts[0])}</strong>
                    <span>${escapeHtml(parts[1] || "")}</span>
                    <small>Faculty: ${escapeHtml(parts[2] || "Unassigned")}</small>
                </li>
            `;
        })
        .join("");

    qs("#enrollmentDetailsBody").innerHTML = `
        <div class="details-form-grid">
            <div class="detail-field full">
                <label>College</label>
                <div class="readonly-box">${escapeHtml(row.department_name)} (${escapeHtml(row.department_code)})</div>
            </div>

            <div class="detail-field full">
                <label>Program</label>
                <div class="readonly-box">${escapeHtml(row.course_code)} - ${escapeHtml(row.course_name)}</div>
            </div>

            <div class="detail-field">
                <label>Year Level</label>
                <div class="readonly-box">${yearLabel(row.year_level)}</div>
            </div>

            <div class="detail-field">
                <label>Section</label>
                <div class="readonly-box">${escapeHtml(row.section_name)}</div>
            </div>

            <div class="detail-field">
                <label>Semester</label>
                <div class="readonly-box">${shortSemester(row.term_name)}</div>
            </div>

            <div class="detail-field">
                <label>Academic Year</label>
                <div class="readonly-box">${escapeHtml(row.academic_year_name)}</div>
            </div>

            <div class="detail-field full">
                <label>Courses Taken by the Section</label>
                <div class="readonly-box subject-box">
                    <ul class="organized-subject-list">
                        ${detailItems || "<li><strong>No active courses found.</strong></li>"}
                    </ul>
                </div>
            </div>

            <div class="detail-field full">
                <label>Status</label>
                <div class="readonly-box status-box">
                    <span class="status-badge ${String(row.enrollment_status).toLowerCase()}">${String(row.enrollment_status).toLowerCase()}</span>
                </div>
            </div>
        </div>
    `;

    openModal("viewEnrollmentModal");
}

function editEnrollment(sectionId, termId) {
    const row = DATA.sectionEnrollments.find(item => Number(item.section_id) === Number(sectionId) && Number(item.term_id) === Number(termId));
    if (!row) return;

    qs("#editEnrollmentSectionId").value = row.section_id;
    qs("#editEnrollmentTermId").value = row.term_id;
    qs("#editEnrollmentDepartment").value = `${row.department_name} (${row.department_code})`;
    qs("#editEnrollmentCourse").value = `${row.course_code} - ${row.course_name}`;
    qs("#editEnrollmentYearLevel").value = yearLabel(row.year_level);
    qs("#editEnrollmentSemester").value = shortSemester(row.term_name);
    qs("#editEnrollmentSection").value = row.section_name;

    const currentSubjectIds = String(row.subject_ids || "").split(",").filter(Boolean);

    function renderEditSubjects() {
        const type = qs("#editEnrollmentSubjectType").value;
        const subjectRows = getCourseSubjects([row.course_id], row.year_level, row.term_name, type);

        renderCheckboxList(
            qs("#editEnrollmentSubjectList"),
            subjectRows,
            "edit_enrollment_subject_ids[]",
            "subject_id",
            s => `${s.subject_code} - ${s.subject_title}`,
            s => `${s.subject_type || "Program"} course`,
            currentSubjectIds
        );

        qsa('#editEnrollmentSubjectList input[type="checkbox"]').forEach(input => {
            input.addEventListener("change", () => updateCheckedCount("#editEnrollmentSubjectList", "#editEnrollmentSubjectCount", "course"));
        });

        updateCheckedCount("#editEnrollmentSubjectList", "#editEnrollmentSubjectCount", "course");
    }

    qs("#editEnrollmentSubjectType").onchange = renderEditSubjects;
    renderEditSubjects();

    openModal("editEnrollmentModal");
}

setTimeout(() => {
    const toast = qs("#toastMessage");
    if (toast) {
        toast.classList.add("hide");
        setTimeout(() => toast.remove(), 350);
    }
}, 3000);

updateAssignFacultyLists();
updateEnrollCourseList();
filterFacultyAssignments();
filterSectionEnrollments();
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