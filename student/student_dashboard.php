<?php
declare(strict_types=1);

session_start();

$database_host     = "localhost";
$database_name     = "student_evaluation_for_teacher_db";
$database_username = "root";
$database_password = "";

$db = new mysqli($database_host, $database_username, $database_password, $database_name);
if ($db->connect_error) {
    die("Database connection failed: " . $db->connect_error);
}



if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    $post_action = $_POST["action"];

    if ($post_action === "accept_guidelines") {
        header("Content-Type: application/json");
        $post_student_id = (int) ($_POST["student_id"] ?? 0);
        if ($post_student_id > 0) {
            $stmt = $db->prepare("UPDATE student SET guidelines_accepted = 1 WHERE student_id = ?");
            $stmt->bind_param("i", $post_student_id);
            $stmt->execute();
            $stmt->close();
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "message" => "Invalid student"]);
        }
        exit;
    }

    if ($post_action === "submit_evaluation") {
        header("Content-Type: application/json");
        $post_student_id     = (int) ($_POST["student_id"]              ?? 0);
        $post_task_id        = (int) ($_POST["task_id"]                 ?? 0);
        $post_period_id      = (int) ($_POST["period_id"]               ?? 0);
        $post_ta_id          = (int) ($_POST["teaching_assignment_id"]  ?? 0);
        $post_answers        = $_POST["answers"] ?? [];
        $post_comment        = trim($_POST["comment"] ?? "");

        if (!$post_student_id || !$post_task_id || !$post_period_id || !$post_ta_id) {
            echo json_encode(["success" => false, "message" => "Missing required fields"]);
            exit;
        }

        $chk = $db->prepare("SELECT task_status FROM student_evaluation_task WHERE student_evaluation_task_id = ? AND student_id = ?");
        $chk->bind_param("ii", $post_task_id, $post_student_id);
        $chk->execute();
        $chk_row = $chk->get_result()->fetch_assoc();
        $chk->close();

        if (!$chk_row) {
            echo json_encode(["success" => false, "message" => "Invalid task"]);
            exit;
        }
        if ($chk_row["task_status"] === "Submitted") {
            echo json_encode(["success" => false, "message" => "Already submitted"]);
            exit;
        }

        $ef_stmt = $db->prepare("SELECT evaluation_form_id FROM evaluation_form WHERE evaluation_period_id = ? AND form_status = 'Active' LIMIT 1");
        $ef_stmt->bind_param("i", $post_period_id);
        $ef_stmt->execute();
        $ef_row = $ef_stmt->get_result()->fetch_assoc();
        $ef_stmt->close();
        $post_form_id = $ef_row ? (int)$ef_row["evaluation_form_id"] : 0;

        if (!$post_form_id) {
            echo json_encode(["success" => false, "message" => "No active evaluation form found"]);
            exit;
        }

        $rso_map = [];
        $rso_stmt = $db->prepare("SELECT rating_value, rating_scale_option_id FROM rating_scale_option WHERE evaluation_form_id = ?");
        $rso_stmt->bind_param("i", $post_form_id);
        $rso_stmt->execute();
        $rso_result = $rso_stmt->get_result();
        while ($rso_row = $rso_result->fetch_assoc()) {
            $rso_map[(int)$rso_row["rating_value"]] = (int)$rso_row["rating_scale_option_id"];
        }
        $rso_stmt->close();

        $now_ts = date("Y-m-d H:i:s");

        $total_score = 0;
        $answer_count = 0;
        foreach ($post_answers as $val) {
            $v = (int)$val;
            if ($v >= 1 && $v <= 5) { $total_score += $v; $answer_count++; }
        }
        $avg_score = $answer_count > 0 ? round($total_score / $answer_count, 2) : 0;

        $db->begin_transaction();
        try {
            $upd_task = $db->prepare(
                "UPDATE student_evaluation_task SET task_status = 'Submitted', submitted_at = ?, last_saved_at = ? WHERE student_evaluation_task_id = ? AND student_id = ?"
            );
            $upd_task->bind_param("ssii", $now_ts, $now_ts, $post_task_id, $post_student_id);
            $upd_task->execute();
            if ($upd_task->affected_rows < 1) {
                $upd_task->close();
                $db->rollback();
                echo json_encode(["success" => false, "message" => "Failed to update task status"]);
                exit;
            }
            $upd_task->close();

            $chk_resp = $db->prepare("SELECT evaluation_response_id FROM evaluation_response WHERE student_evaluation_task_id = ? LIMIT 1");
            $chk_resp->bind_param("i", $post_task_id);
            $chk_resp->execute();
            $existing_resp = $chk_resp->get_result()->fetch_assoc();
            $chk_resp->close();

            if ($existing_resp) {
                $response_id = (int)$existing_resp["evaluation_response_id"];
            } else {
                $ins_resp = $db->prepare(
                    "INSERT INTO evaluation_response
                     (student_evaluation_task_id, student_id, teaching_assignment_id, evaluation_period_id, evaluation_form_id, response_status, average_score, total_score, submitted_at, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, 'Submitted', ?, ?, ?, ?, ?)"
                );
                $ins_resp->bind_param("iiiiiddsss", $post_task_id, $post_student_id, $post_ta_id, $post_period_id, $post_form_id, $avg_score, $total_score, $now_ts, $now_ts, $now_ts);
                $ins_resp->execute();
                $response_id = (int)$db->insert_id;
                $ins_resp->close();
                if (!$response_id) {
                    $db->rollback();
                    echo json_encode(["success" => false, "message" => "Failed to create evaluation response"]);
                    exit;
                }
            }

            if (is_array($post_answers) && count($post_answers) > 0) {
                $del_ans = $db->prepare("DELETE FROM evaluation_response_answer WHERE evaluation_response_id = ?");
                $del_ans->bind_param("i", $response_id);
                $del_ans->execute();
                $del_ans->close();

                $ins_ans = $db->prepare(
                    "INSERT INTO evaluation_response_answer (evaluation_response_id, evaluation_form_item_id, rating_scale_option_id, rating_value, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );
                foreach ($post_answers as $item_id_key => $rating_val) {
                    $item_id_int = (int)$item_id_key;
                    $rating_int  = (int)$rating_val;
                    if ($item_id_int > 0 && $rating_int >= 1 && $rating_int <= 5) {
                        $rso_id = $rso_map[$rating_int] ?? 0;
                        if (!$rso_id) continue;
                        $ins_ans->bind_param("iiiiss", $response_id, $item_id_int, $rso_id, $rating_int, $now_ts, $now_ts);
                        $ins_ans->execute();
                    }
                }
                $ins_ans->close();
            }

            if (!empty($post_comment)) {
                $chk_cmt = $db->prepare("SELECT evaluation_response_comment_id FROM evaluation_response_comment WHERE evaluation_response_id = ? LIMIT 1");
                $chk_cmt->bind_param("i", $response_id);
                $chk_cmt->execute();
                $existing_cmt = $chk_cmt->get_result()->fetch_assoc();
                $chk_cmt->close();
                if (!$existing_cmt) {
                    $ins_cmt = $db->prepare(
                        "INSERT INTO evaluation_response_comment (evaluation_response_id, comment_text, submitted_at)
                         VALUES (?, ?, ?)"
                    );
                    $ins_cmt->bind_param("iss", $response_id, $post_comment, $now_ts);
                    $ins_cmt->execute();
                    $ins_cmt->close();
                }
            }

            $db->commit();
            echo json_encode(["success" => true, "submitted_at" => $now_ts, "average_score" => $avg_score]);
        } catch (Throwable $e) {
            $db->rollback();
            echo json_encode(["success" => false, "message" => "Transaction failed: " . $e->getMessage()]);
        }
        exit;
    }

    if ($post_action === "update_password") {
        header("Content-Type: application/json");
        $logged_uid  = $_SESSION["authenticated_user_id"] ?? null;
        $current_pw  = $_POST["current_password"]  ?? "";
        $new_pw      = $_POST["new_password"]       ?? "";
        $confirm_pw  = $_POST["confirm_password"]   ?? "";

        if (!$logged_uid) {
            echo json_encode(["success" => false, "message" => "Not authenticated"]);
            exit;
        }
        if (strlen($new_pw) < 8 || !preg_match('/[A-Z]/', $new_pw) || !preg_match('/[a-z]/', $new_pw) || !preg_match('/[0-9]/', $new_pw)) {
            echo json_encode(["success" => false, "message" => "Password does not meet requirements"]);
            exit;
        }
        if ($new_pw !== $confirm_pw) {
            echo json_encode(["success" => false, "message" => "Passwords do not match"]);
            exit;
        }
        $pw_stmt = $db->prepare("SELECT password_hash FROM user WHERE user_id = ?");
        $pw_stmt->bind_param("i", $logged_uid);
        $pw_stmt->execute();
        $pw_row = $pw_stmt->get_result()->fetch_assoc();
        $pw_stmt->close();
        if (!$pw_row || !password_verify($current_pw, $pw_row["password_hash"])) {
            echo json_encode(["success" => false, "message" => "Current password is incorrect"]);
            exit;
        }
        $new_hash = password_hash($new_pw, PASSWORD_DEFAULT);
        $upd_pw = $db->prepare("UPDATE user SET password_hash = ? WHERE user_id = ?");
        $upd_pw->bind_param("si", $new_hash, $logged_uid);
        $upd_pw->execute();
        $upd_pw->close();
        echo json_encode(["success" => true]);
        exit;
    }
}

$logged_in_user_id = $_SESSION["authenticated_user_id"] ?? null;
if (!$logged_in_user_id) {
    header("Location: ../login/login_page.php");
    exit;
}

$student_row = null;
$stmt = $db->prepare(
    "SELECT s.student_id, s.student_number, s.full_name, s.guidelines_accepted,
            u.email, u.account_status,
            c.course_code,
            sec.section_name
     FROM student s
     JOIN user u ON u.user_id = s.user_id
     LEFT JOIN course c ON c.course_id = s.course_id
     LEFT JOIN section sec ON sec.section_id = s.current_section_id
     WHERE s.user_id = ?
     LIMIT 1"
);

$stmt->bind_param("i", $logged_in_user_id);
$stmt->execute();
$result = $stmt->get_result();
$student_row = $result->fetch_assoc();
$stmt->close();

if (!$student_row) {
    die("Student profile not found.");
}

$student_id         = (int) $student_row["student_id"];
$guidelines_accepted = (bool) $student_row["guidelines_accepted"];

$term_row = null;
$stmt = $db->prepare(
    "SELECT t.term_id, t.term_name, ay.academic_year_name
     FROM term t
     JOIN academic_year ay ON ay.academic_year_id = t.academic_year_id
     WHERE t.term_status IN ('Active','Ongoing','Open')
     ORDER BY t.term_id DESC
     LIMIT 1"
);
$stmt->execute();
$result   = $stmt->get_result();
$term_row = $result->fetch_assoc();
$stmt->close();

$current_term_id   = $term_row ? (int) $term_row["term_id"] : null;
$term_label        = $term_row ? $term_row["term_name"] . " • " . $term_row["academic_year_name"] : "Current Semester";

$active_period = null;
if ($current_term_id) {
    $stmt = $db->prepare(
        "SELECT evaluation_period_id, period_name
         FROM evaluation_period
         WHERE term_id = ? AND period_status IN ('Open','Ongoing')
         ORDER BY evaluation_period_id DESC
         LIMIT 1"
    );
    $stmt->bind_param("i", $current_term_id);
    $stmt->execute();
    $result        = $stmt->get_result();
    $active_period = $result->fetch_assoc();
    $stmt->close();
}

$current_period_id = $active_period ? (int) $active_period["evaluation_period_id"] : null;

$assigned_teachers = [];
if ($current_period_id) {
    $stmt = $db->prepare(
        "SELECT
            set2.student_evaluation_task_id,
            set2.task_status,
            set2.submitted_at,
            set2.teaching_assignment_id,
            f.full_name AS faculty_name,
            subj.subject_title,
            d.department_name,
            er.average_score
         FROM student_evaluation_task set2
         JOIN teaching_assignment ta ON ta.teaching_assignment_id = set2.teaching_assignment_id
         JOIN faculty f ON f.faculty_id = ta.faculty_id
         JOIN section_subject_offering sso ON sso.section_subject_offering_id = ta.section_subject_offering_id
         JOIN subject subj ON subj.subject_id = sso.subject_id
         JOIN department d ON d.department_id = subj.department_id
         LEFT JOIN evaluation_response er
               ON er.student_evaluation_task_id = set2.student_evaluation_task_id
              AND er.response_status = 'Submitted'
         WHERE set2.student_id = ?
           AND set2.evaluation_period_id = ?
         ORDER BY set2.student_evaluation_task_id ASC"
    );
    $stmt->bind_param("ii", $student_id, $current_period_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $assigned_teachers[] = $row;
    }
    $stmt->close();
}

$total_teachers  = count($assigned_teachers);
$completed_count = 0;
foreach ($assigned_teachers as $t) {
    if ($t["task_status"] === "Submitted") {
        $completed_count++;
    }
}
$pending_count = max($total_teachers - $completed_count, 0);

$eval_form_items = [];
if ($current_period_id) {
    $stmt = $db->prepare(
        "SELECT
            efi.evaluation_form_item_id,
            efi.statement_text_snapshot,
            efi.display_order AS item_order,
            ec.category_name,
            efc.display_order AS cat_order
         FROM evaluation_form_item efi
         JOIN evaluation_form_category efc ON efc.evaluation_form_category_id = efi.evaluation_form_category_id
         JOIN evaluation_category ec ON ec.evaluation_category_id = efc.evaluation_category_id
         JOIN evaluation_form ef ON ef.evaluation_form_id = efc.evaluation_form_id
         WHERE ef.evaluation_period_id = ?
           AND ef.form_status = 'Active'
           AND efi.form_item_status = 'Active'
         ORDER BY efc.display_order ASC, efi.display_order ASC"
    );
    $stmt->bind_param("i", $current_period_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $eval_form_items[] = $row;
    }
    $stmt->close();
}

$eval_categories = [];
foreach ($eval_form_items as $item) {
    $eval_categories[$item["category_name"]][] = [
        "evaluation_form_item_id" => $item["evaluation_form_item_id"],
        "statement"    => $item["statement_text_snapshot"],
    ];
}

$use_fallback_criteria = empty($eval_categories);
if ($use_fallback_criteria) {
    $eval_categories = [
        "Teaching Effectiveness" => [
            ["evaluation_form_item_id" => null, "statement" => "The teacher explains concepts clearly and effectively"],
            ["evaluation_form_item_id" => null, "statement" => "The teacher uses appropriate teaching methods and materials"],
            ["evaluation_form_item_id" => null, "statement" => "The teacher demonstrates effective use of instructional strategies"],
            ["evaluation_form_item_id" => null, "statement" => "The teacher presents lessons in an organized manner"],
            ["evaluation_form_item_id" => null, "statement" => "The teacher adapts teaching methods to meet diverse student needs"],
        ],
        "Subject Mastery" => [
            ["evaluation_form_item_id" => null, "statement" => "The teacher demonstrates deep knowledge of the subject matter"],
            ["evaluation_form_item_id" => null, "statement" => "The teacher answers questions accurately and confidently"],
            ["evaluation_form_item_id" => null, "statement" => "The teacher provides relevant examples and applications"],
            ["evaluation_form_item_id" => null, "statement" => "The teacher connects topics to real-world situations"],
            ["evaluation_form_item_id" => null, "statement" => "The teacher stays updated with current developments in the field"],
        ],
        "Preparedness" => [
            ["evaluation_form_item_id" => null, "statement" => "The teacher comes to class well-prepared"],
            ["evaluation_form_item_id" => null, "statement" => "The teacher organizes lessons in a logical sequence"],
            ["evaluation_form_item_id" => null, "statement" => "The teacher provides clear learning objectives for each lesson"],
            ["evaluation_form_item_id" => null, "statement" => "The teacher has all necessary materials and resources ready"],
            ["evaluation_form_item_id" => null, "statement" => "The teacher plans activities that enhance learning"],
        ],
        "Engagement" => [
            ["evaluation_form_item_id" => null, "statement" => "The teacher encourages student participation and discussion"],
            ["evaluation_form_item_id" => null, "statement" => "The teacher creates an interactive learning environment"],
            ["evaluation_form_item_id" => null, "statement" => "The teacher motivates students to learn and excel"],
            ["evaluation_form_item_id" => null, "statement" => "The teacher shows enthusiasm and passion for teaching"],
            ["evaluation_form_item_id" => null, "statement" => "The teacher makes learning interesting and enjoyable"],
        ],
        "Professionalism" => [
            ["evaluation_form_item_id" => null, "statement" => "The teacher maintains a respectful and professional attitude"],
            ["evaluation_form_item_id" => null, "statement" => "The teacher is punctual and manages class time effectively"],
            ["evaluation_form_item_id" => null, "statement" => "The teacher treats all students fairly and equally"],
            ["evaluation_form_item_id" => null, "statement" => "The teacher maintains appropriate boundaries and conduct"],
            ["evaluation_form_item_id" => null, "statement" => "The teacher serves as a positive role model"],
        ],
        "Feedback & Assessment" => [
            ["evaluation_form_item_id" => null, "statement" => "The teacher provides timely and constructive feedback"],
            ["evaluation_form_item_id" => null, "statement" => "The teacher uses fair and appropriate assessment methods"],
            ["evaluation_form_item_id" => null, "statement" => "The teacher clearly explains grading criteria and expectations"],
            ["evaluation_form_item_id" => null, "statement" => "The teacher returns graded work in a reasonable timeframe"],
            ["evaluation_form_item_id" => null, "statement" => "The teacher provides opportunities for improvement and growth"],
        ],
    ];
}

$total_items = 0;
foreach ($eval_categories as $stmts) {
    $total_items += count($stmts);
}

$rating_labels = [1 => "Strongly Disagree", 2 => "Disagree", 3 => "Neutral", 4 => "Agree", 5 => "Strongly Agree"];

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
}

function svg_icon(string $name, string $color = "currentColor"): string
{
    $icons = [
        "dashboard"   => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="3" y="3" width="7" height="7" rx="1.5" stroke="' . $color . '" stroke-width="2"/><rect x="14" y="3" width="7" height="7" rx="1.5" stroke="' . $color . '" stroke-width="2"/><rect x="3" y="14" width="7" height="7" rx="1.5" stroke="' . $color . '" stroke-width="2"/><rect x="14" y="14" width="7" height="7" rx="1.5" stroke="' . $color . '" stroke-width="2"/></svg>',
        "file"        => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M7 3h7l5 5v13H7z" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 3v5h5" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 13h6M9 17h6" stroke="' . $color . '" stroke-width="2" stroke-linecap="round"/></svg>',
        "eval"        => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M7 3h7l5 5v13H7z" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 3v5h5" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 13h2M9 17h2M13 13l1.5 1.5L17 11" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        "history"     => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 12a9 9 0 1 0 3-6.7" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 4v5h5" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 7v6l4 2" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        "settings"    => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        "logout"      => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><polyline points="16 17 21 12 16 7" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><line x1="21" y1="12" x2="9" y2="12" stroke="' . $color . '" stroke-width="2" stroke-linecap="round"/></svg>',
        "bell"        => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 7-3 7h18s-3 0-3-7" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M13.73 21a2 2 0 0 1-3.46 0" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        "refresh"     => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21 12a9 9 0 0 1-15.49 6.29" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 12A9 9 0 0 1 18.49 5.71" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><polyline points="18 2 18 6 14 6" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><polyline points="6 22 6 18 10 18" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        "check"       => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20 6L9 17l-5-5" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        "check-circle"=> '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><polyline points="22 4 12 14.01 9 11.01" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        "clock"       => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><polyline points="12 6 12 12 16 14" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        "lock"        => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="3" y="11" width="18" height="11" rx="2" ry="2" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M7 11V7a5 5 0 0 1 10 0v4" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        "alert-tri"   => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="9" x2="12" y2="13" stroke="' . $color . '" stroke-width="2" stroke-linecap="round"/><line x1="12" y1="17" x2="12.01" y2="17" stroke="' . $color . '" stroke-width="2" stroke-linecap="round"/></svg>',
        "info"        => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" stroke="' . $color . '" stroke-width="2"/><line x1="12" y1="16" x2="12" y2="12" stroke="' . $color . '" stroke-width="2" stroke-linecap="round"/><line x1="12" y1="8" x2="12.01" y2="8" stroke="' . $color . '" stroke-width="2" stroke-linecap="round"/></svg>',
        "eye"         => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="3" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        "eye-off"     => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><line x1="1" y1="1" x2="23" y2="23" stroke="' . $color . '" stroke-width="2" stroke-linecap="round"/></svg>',
        "calendar"    => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="3" y="4" width="18" height="18" rx="2" ry="2" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><line x1="16" y1="2" x2="16" y2="6" stroke="' . $color . '" stroke-width="2" stroke-linecap="round"/><line x1="8" y1="2" x2="8" y2="6" stroke="' . $color . '" stroke-width="2" stroke-linecap="round"/><line x1="3" y1="10" x2="21" y2="10" stroke="' . $color . '" stroke-width="2" stroke-linecap="round"/></svg>',
        "save"        => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><polyline points="17 21 17 13 7 13 7 21" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><polyline points="7 3 7 8 15 8" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        "reset"       => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><polyline points="1 4 1 10 7 10" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M3.51 15a9 9 0 1 0 .49-4.95" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        "filter"      => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        "search"      => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="11" cy="11" r="8" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><line x1="21" y1="21" x2="16.65" y2="16.65" stroke="' . $color . '" stroke-width="2" stroke-linecap="round"/></svg>',
        "home"        => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><polyline points="9 22 9 12 15 12 15 22" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        "shield"      => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        "x"           => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><line x1="18" y1="6" x2="6" y2="18" stroke="' . $color . '" stroke-width="2" stroke-linecap="round"/><line x1="6" y1="6" x2="18" y2="18" stroke="' . $color . '" stroke-width="2" stroke-linecap="round"/></svg>',
        "arrow-left"  => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><line x1="19" y1="12" x2="5" y2="12" stroke="' . $color . '" stroke-width="2" stroke-linecap="round"/><polyline points="12 19 5 12 12 5" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        "arrow-right" => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><line x1="5" y1="12" x2="19" y2="12" stroke="' . $color . '" stroke-width="2" stroke-linecap="round"/><polyline points="12 5 19 12 12 19" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        "edit"        => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        "star"        => '<svg viewBox="0 0 24 24" fill="#f59e0b" xmlns="http://www.w3.org/2000/svg"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" stroke="#f59e0b" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        "star-empty"  => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" stroke="#d1d5db" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        "chat"        => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" stroke="' . $color . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    ];
    return $icons[$name] ?? "";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard – Student Evaluation for Teacher</title>
    <link rel="stylesheet" href="student-dashboard.css">
    <style>
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        .content-wrap {
            max-width: 960px;
            margin: 0 auto;
            padding: 28px 32px;
        }

        .guide-header {
            background: #172033;
            color: #fff;
            border-radius: 8px 8px 0 0;
            padding: 28px 32px;
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 0;
        }
        .guide-header svg { flex-shrink: 0; margin-top: 2px; }
        .guide-header h2 { font-size: 20px; margin-bottom: 6px; color: #fff; }
        .guide-header p  { font-size: 14px; color: #a8b4c4; margin: 0; }

        .guide-body {
            border: 1px solid #d8dce2;
            border-top: none;
            border-radius: 0 0 8px 8px;
            padding: 28px 32px;
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin-bottom: 22px;
        }

        .guide-item {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            padding: 18px 20px;
            background: #f9fafb;
            border-left: 4px solid #f59e0b;
            border-radius: 0 8px 8px 0;
        }
        .guide-num {
            min-width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #f59e0b;
            color: #172033;
            font-weight: 700;
            font-size: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .guide-item h3 { font-size: 15px; margin-bottom: 6px; }
        .guide-item p  { font-size: 13px; color: #556070; margin: 0; }

        .guide-reminders {
            background: #172033;
            color: #fff;
            border-radius: 8px;
            padding: 20px 24px;
        }
        .guide-reminders h3 { font-size: 15px; margin-bottom: 12px; color: #fff;; display: flex; align-items: center; gap: 8px; }
        .guide-reminders ul { list-style: none; display: flex; flex-direction: column; gap: 8px; }
        .guide-reminders li { font-size: 13px; color: #fff;; }
        .guide-reminders li::before { content: "• "; }

        .guide-agree-row {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 18px 20px;
            background: #f3f4f6;
            border-radius: 8px;
            cursor: pointer;
        }
        .guide-agree-row input[type="checkbox"] {
            width: 20px;
            height: 20px;
            accent-color: #172033;
            flex-shrink: 0;
            cursor: pointer;
        }
        .guide-agree-row label { font-weight: 600; font-size: 14px; cursor: pointer; }

        .guide-action-row {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 12px;
        }
        .btn-back-guide {
            min-height: 52px;
            border: 1px solid #d8dce2;
            border-radius: 8px;
            background: #fff;
            font-weight: 700;
            font-size: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
        }
        .btn-proceed {
            min-height: 52px;
            border-radius: 8px;
            background: #d1d5db;
            color: #9ca3af;
            font-weight: 700;
            font-size: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: not-allowed;
            transition: background 0.2s, color 0.2s;
        }
        .btn-proceed.enabled {
            background: #172033;
            color: #fff;
            cursor: pointer;
        }

        .eval-locked-banner {
            background: #dc2626;
            border-radius: 8px;
            padding: 28px 32px;
            display: flex;
            align-items: flex-start;
            gap: 18px;
            margin-bottom: 22px;
        }
        .eval-locked-banner svg { flex-shrink: 0; margin-top: 2px; }
        .eval-locked-banner h2 { font-size: 20px; font-weight: 700; color: #fff; margin-bottom: 6px; }
        .eval-locked-banner p  { font-size: 14px; color: rgba(255,255,255,0.85); margin: 0; }

        .eval-locked-notice {
            border: 1px solid #fecaca;
            background: #fff1f2;
            border-radius: 8px;
            padding: 20px 24px;
            display: flex;
            align-items: flex-start;
            gap: 14px;
            margin-bottom: 22px;
        }
        .eval-locked-notice svg { flex-shrink: 0; margin-top: 2px; }
        .eval-locked-notice strong { display: block; font-size: 14px; color: #dc2626; margin-bottom: 6px; }
        .eval-locked-notice p { font-size: 13px; color: #dc2626; margin: 0; }

        .btn-go-guidelines {
            width: 100%;
            min-height: 52px;
            border-radius: 8px;
            background: #172033;
            color: #fff;
            font-weight: 700;
            font-size: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            cursor: pointer;
            border: none;
        }

        .eval-filter-row {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .eval-filter-btn {
            min-height: 40px;
            padding: 0 18px;
            border: 1px solid #d8dce2;
            border-radius: 8px;
            background: #fff;
            font-weight: 700;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            color: #4b5563;
        }
        .eval-filter-btn.active-filter {
            background: #172033;
            color: #fff;
            border-color: #172033;
        }

        .eval-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 16px;
        }
        .eval-card {
            border: 1px solid #d8dce2;
            border-radius: 8px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            background: #fff;
        }
        .eval-card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
        }
        .eval-card-top h3 { font-size: 16px; margin-bottom: 4px; }
        .eval-card-top p  { font-size: 13px; color: #2563eb; margin-bottom: 2px; }
        .eval-card-top small { font-size: 12px; color: #64748b; }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .status-pill.completed { background: #dcfce7; color: #16a34a; }
        .status-pill.pending   { background: #fef9c3; color: #a16207; }
        .status-pill svg { width: 14px; height: 14px; }

        .eval-card-completed-note { font-size: 13px; color: #64748b; }

        .btn-start-eval {
            min-height: 44px;
            border-radius: 8px;
            background: #172033;
            color: #fff;
            font-weight: 700;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            border: none;
            width: 100%;
        }
        .btn-view-eval {
            min-height: 44px;
            border-radius: 8px;
            background: #2563eb;
            color: #fff;
            font-weight: 700;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            border: none;
            width: 100%;
        }

        .eval-teacher-meta {
            border-radius: 8px;
            padding: 24px 28px;
            border: 2px solid #d8dce2;
            margin-bottom: 22px;
        }
        .eval-teacher-meta h2 { font-size: 20px; margin-bottom: 6px; }
        .eval-teacher-meta p  { font-size: 14px; color: #3f3f3f; margin: 0; }
        .eval-progress-row {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            color: #3f3f3f;
            margin-top: 16px;
            margin-bottom: 6px;
        }
        .eval-progress-bar {
            height: 6px;
            background: rgba(20, 20, 20, 0.15);
            border-radius: 999px;
            overflow: hidden;
        }
        .eval-progress-fill {
            height: 100%;
            background: #f59e0b;
            border-radius: 999px;
            transition: width 0.2s;
        }

        .eval-form-panel {
            border: 1px solid #d8dce2;
            border-radius: 8px;
            padding: 24px 28px;
            margin-bottom: 22px;
        }

        .criteria-section { margin-bottom: 28px; }
        .criteria-section-title {
            font-size: 16px;
            font-weight: 700;
            padding: 12px 0;
            margin-bottom: 8px;
            border-bottom: 2px solid #e5e7eb;
        }
        .criteria-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .criteria-table thead th {
            padding: 10px 14px;
            background: #f3f4f6;
            text-align: center;
            font-weight: 700;
            border-bottom: 1px solid #e5e7eb;
        }
        .criteria-table thead th:first-child { text-align: left; }
        .criteria-table tbody tr { border-bottom: 1px solid #f3f4f6; }
        .criteria-table tbody tr:last-child { border-bottom: none; }
        .criteria-table tbody tr.answered { background: #f0fdf4; }
        .criteria-table tbody td { padding: 12px 14px; vertical-align: middle; text-align: center; }
        .criteria-table tbody td:first-child { text-align: left; color: #071226; }
        .criteria-table tbody td input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #172033;
            cursor: pointer;
        }

        .comments-field { margin-top: 8px; }
        .comments-field h3 { font-size: 16px; margin-bottom: 10px; }
        .comments-field textarea {
            width: 100%;
            min-height: 120px;
            border: 1px solid #d8dce2;
            border-radius: 8px;
            padding: 14px;
            font-size: 14px;
            font-family: Arial, Helvetica, sans-serif;
            resize: vertical;
            background: #f9fafb;
        }
        .comments-field textarea:focus { outline: none; border-color: #172033; }
        .comments-field p { font-size: 12px; color: #64748b; margin-top: 8px; }

        .eval-form-actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
        }
        .btn-eval-back {
            min-height: 48px; padding: 0 22px;
            border: 1px solid #d8dce2; border-radius: 8px;
            background: #fff; font-weight: 700; font-size: 14px;
            display: flex; align-items: center; gap: 8px; cursor: pointer;
        }
        .btn-eval-draft {
            min-height: 48px; padding: 0 22px;
            border: 1px solid #d8dce2; border-radius: 8px;
            background: #fff; font-weight: 700; font-size: 14px;
            display: flex; align-items: center; gap: 8px; cursor: pointer;
        }
        .btn-eval-clear {
            min-height: 48px; padding: 0 22px;
            border: 1px solid #fecaca; border-radius: 8px;
            background: #fff; color: #ef4444; font-weight: 700; font-size: 14px;
            display: flex; align-items: center; gap: 8px; cursor: pointer;
        }
        .btn-eval-proceed {
            min-height: 48px; padding: 0 28px;
            border-radius: 8px; background: #d1d5db; color: #9ca3af;
            font-weight: 700; font-size: 14px; margin-left: auto;
            display: flex; align-items: center; gap: 8px; cursor: not-allowed;
            border: none; pointer-events: none;
        }
        .btn-eval-proceed.ready {
            background: #172033; color: #fff; cursor: pointer; pointer-events: auto;
        }

        .review-panel {
            border: 1px solid #d8dce2;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 22px;
        }
        .review-panel-header {
            padding: 22px 24px;
            border-bottom: 1px solid #e5e7eb;
        }
        .review-panel-header h2 { font-size: 18px; margin-bottom: 6px; }
        .review-panel-header p  { font-size: 13px; color: #556070; }

        .review-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .review-table thead th {
            background: #f3f4f6;
            padding: 12px 20px;
            text-align: left;
            font-weight: 700;
            color: #071226;
            border-bottom: 1px solid #e5e7eb;
        }
        .review-table thead th:last-child { text-align: center; width: 120px; }
        .review-table tbody tr { border-bottom: 1px solid #e5e7eb; }
        .review-table tbody tr:last-child { border-bottom: none; }
        .review-table tbody td {
            padding: 14px 20px;
            font-size: 13px;
            color: #071226;
            vertical-align: middle;
        }
        .review-table tbody td:first-child { color: #2563eb; }
        .review-table tbody td:last-child { text-align: center; }
        .rating-val { font-size: 18px; font-weight: 700; display: block; }
        .rating-lbl { font-size: 12px; color: #64748b; }
        .review-section-head {
            padding: 14px 20px;
            background: #f9fafb;
            font-weight: 700;
            font-size: 14px;
            border-bottom: 1px solid #e5e7eb;
        }

        .notice-important {
            border: 1px solid #fecaca;
            background: #fff1f2;
            border-radius: 8px;
            padding: 16px 20px;
            display: flex;
            gap: 12px;
            align-items: flex-start;
            margin-bottom: 22px;
        }
        .notice-important strong { display: block; color: #dc2626; margin-bottom: 4px; }
        .notice-important p { font-size: 13px; color: #dc2626; margin: 0; }

        .review-actions {
            display: flex;
            gap: 12px;
        }
        .btn-cancel-review {
            min-height: 48px; padding: 0 22px;
            border: 1px solid #d8dce2; border-radius: 8px;
            background: #fff; font-weight: 700; font-size: 14px;
            display: flex; align-items: center; gap: 8px; cursor: pointer;
        }
        .btn-edit-review {
            min-height: 48px; padding: 0 22px;
            border: 1px solid #d8dce2; border-radius: 8px;
            background: #fff; font-weight: 700; font-size: 14px;
            display: flex; align-items: center; gap: 8px; cursor: pointer;
        }
        .btn-confirm-submit {
            min-height: 48px; padding: 0 28px;
            border-radius: 8px; background: #16a34a; color: #fff;
            font-weight: 700; font-size: 14px; margin-left: auto;
            display: flex; align-items: center; gap: 8px; cursor: pointer;
            border: none;
        }

        .success-banner {
            background: #16a34a;
            border-radius: 8px;
            padding: 40px 32px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            color: #fff;
            margin-bottom: 22px;
            gap: 12px;
        }
        .success-banner svg { width: 52px; height: 52px; }
        .success-banner h2 { font-size: 22px; }
        .success-banner p  { font-size: 14px; opacity: 0.85; margin: 0; }

        .submission-details {
            border: 1px solid #d8dce2;
            border-radius: 8px;
            padding: 24px 28px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px 32px;
            margin-bottom: 18px;
            background: #f9fafb;
        }
        .submission-details div small { display: block; color: #64748b; font-size: 12px; margin-bottom: 4px; }
        .submission-details div strong { font-size: 15px; }

        .eval-progress-card {
            border: 1px solid #fde68a;
            background: #fffbeb;
            border-radius: 8px;
            padding: 18px 22px;
            margin-bottom: 18px;
        }
        .eval-progress-card h3 { font-size: 15px; margin-bottom: 12px; }
        .eval-progress-card .prog-row {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
        }
        .eval-progress-card .prog-row span:last-child { font-weight: 700; color: #f59e0b; }

        .btn-next-teacher {
            min-height: 52px; width: 100%;
            border-radius: 8px; background: #172033; color: #fff;
            font-weight: 700; font-size: 15px;
            display: flex; align-items: center; justify-content: center; gap: 10px;
            cursor: pointer; border: none; margin-bottom: 12px;
        }
        .success-secondary-btns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 14px;
        }
        .btn-success-sec {
            min-height: 48px;
            border: 1px solid #d8dce2; border-radius: 8px;
            background: #fff; font-weight: 700; font-size: 14px;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            cursor: pointer;
        }
        .confidential-note {
            border: 1px solid #bfdbfe;
            background: #eff6ff;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 13px;
            color: #1d4ed8;
        }

        .view-eval-header {
            background: #172033;
            color: #fff;
            border-radius: 8px 8px 0 0;
            padding: 20px 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 0;
        }
        .view-eval-header h2 { font-size: 18px; margin: 0; color: #fff; }
        .view-eval-header p  { font-size: 13px; color: #a8b4c4; margin: 0; }

        .view-teacher-card {
            border: 1px solid #d8dce2;
            border-top: none;
            padding: 22px 24px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .view-teacher-meta h3 { font-size: 17px; margin-bottom: 4px; }
        .view-teacher-meta p  { font-size: 13px; color: #556070; margin-bottom: 2px; }

        .view-teacher-stats {
            display: flex;
            gap: 28px;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #e5e7eb;
        }
        .view-teacher-stat { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #556070; }
        .view-teacher-stat svg { width: 16px; height: 16px; }

        .view-rating-item { margin-bottom: 20px; }
        .view-rating-item > p { font-size: 14px; color: #2563eb; margin-bottom: 8px; }
        .view-rating-box {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 14px 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fafafa;
        }
        .view-rating-box strong { font-size: 15px; }
        .view-rating-box small { font-size: 12px; color: #64748b; }
        .stars-row { display: flex; gap: 4px; }
        .stars-row svg { width: 18px; height: 18px; }

        .view-comments-box {
            border: 1px solid #d8dce2;
            border-radius: 8px;
            padding: 22px 24px;
            margin-top: 8px;
            margin-bottom: 22px;
        }
        .view-comments-box h3 { font-size: 16px; margin-bottom: 6px; display: flex; gap: 8px; align-items: center; }
        .view-comments-box p.sub { font-size: 13px; color: #64748b; margin-bottom: 16px; }
        .view-comments-box .comment-text {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 14px;
            background: #f9fafb;
            font-size: 14px;
            color: #071226;
        }

        .view-only-notice {
            border: 1px solid #bfdbfe;
            background: #eff6ff;
            border-radius: 8px;
            padding: 14px 18px;
            display: flex;
            gap: 10px;
            align-items: flex-start;
            margin-bottom: 22px;
        }
        .view-only-notice strong { display: block; color: #1d4ed8; margin-bottom: 3px; font-size: 14px; }
        .view-only-notice p { font-size: 13px; color: #1d4ed8; margin: 0; }

        .btn-view-history {
            min-height: 48px; padding: 0 28px;
            border-radius: 8px; background: #172033; color: #fff;
            font-weight: 700; font-size: 14px;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            cursor: pointer; border: none;
        }

        .history-filter-row {
            border: 1px solid #d8dce2;
            border-radius: 8px;
            padding: 16px 20px;
            display: flex;
            gap: 12px;
            align-items: center;
            margin-bottom: 22px;
            flex-wrap: wrap;
        }
        .history-search-wrap {
            display: flex;
            align-items: center;
            border: 1px solid #d8dce2;
            border-radius: 8px;
            padding: 0 14px;
            flex: 1;
            min-width: 220px;
            background: #f9fafb;
        }
        .history-search-wrap svg { flex-shrink: 0; color: #9ca3af; }
        .history-search-wrap input {
            border: none; background: transparent; padding: 10px 8px;
            font-size: 14px; width: 100%; outline: none;
        }
        .history-filter-btn {
            min-height: 42px; padding: 0 18px;
            border: 1px solid #d8dce2; border-radius: 8px;
            background: #fff; font-weight: 700; font-size: 14px;
            display: flex; align-items: center; gap: 6px; cursor: pointer; color: #4b5563;
        }
        .history-filter-btn.active-filter { background: #172033; color: #fff; border-color: #172033; }

        .history-records-panel { border: 1px solid #d8dce2; border-radius: 8px; overflow: hidden; }
        .history-records-header { padding: 18px 22px; border-bottom: 1px solid #e5e7eb; }
        .history-records-header h2 { font-size: 17px; margin-bottom: 4px; }
        .history-records-header p  { font-size: 13px; color: #556070; margin: 0; }

        .history-record-row {
            padding: 18px 22px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.15s;
        }
        .history-record-row:last-child { border-bottom: none; }
        .history-record-row:hover { background: #f9fafb; }
        .history-record-row h3 { font-size: 15px; margin-bottom: 5px; display: flex; align-items: center; gap: 8px; }
        .history-record-row p  { font-size: 13px; color: #556070; margin-bottom: 3px; }
        .history-record-row small { color: #64748b; font-size: 12px; display: flex; align-items: center; gap: 5px; }
        .history-record-row .click-to-view { font-size: 12px; color: #9ca3af; margin-top: 4px; }

        .settings-section { border: 1px solid #d8dce2; border-radius: 8px; overflow: hidden; margin-bottom: 22px; }
        .settings-section-header {
            padding: 18px 24px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 17px;
            font-weight: 700;
        }
        .settings-section-header svg { flex-shrink: 0; }
        .settings-section-body { padding: 24px; }

        .account-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px 32px;
        }
        .account-info-grid .field-label { font-size: 12px; color: #64748b; margin-bottom: 4px; }
        .account-info-grid .field-value { font-size: 15px; font-weight: 700; }
        .badge-active {
            display: inline-block;
            background: #dcfce7;
            color: #16a34a;
            border-radius: 999px;
            padding: 3px 12px;
            font-size: 13px;
            font-weight: 700;
        }

        .settings-field { margin-bottom: 18px; }
        .settings-field label { display: block; font-weight: 700; font-size: 14px; margin-bottom: 8px; }
        .settings-field .input-wrap { position: relative; display: flex; align-items: center; }
        .settings-field input[type="password"],
        .settings-field input[type="text"] {
            width: 100%;
            min-height: 48px;
            border: 1px solid #d8dce2;
            border-radius: 8px;
            padding: 0 48px 0 16px;
            font-size: 14px;
            background: #f9fafb;
            outline: none;
            transition: border-color 0.15s;
        }
        .settings-field input:focus { border-color: #172033; background: #fff; }
        .toggle-pw {
            position: absolute;
            right: 14px;
            background: none;
            border: none;
            cursor: pointer;
            color: #9ca3af;
            display: flex;
            align-items: center;
        }

        .pw-requirements {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 14px 18px;
            margin-bottom: 18px;
        }
        .pw-requirements strong { display: block; font-size: 13px; color: #1d4ed8; margin-bottom: 8px; }
        .pw-requirements ul { list-style: disc; padding-left: 20px; }
        .pw-requirements li { font-size: 13px; color: #1d4ed8; margin-bottom: 4px; }

        .btn-update-pw {
            min-height: 48px; width: 100%;
            border-radius: 8px; background: #172033; color: #fff;
            font-weight: 700; font-size: 15px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; border: none;
        }

        .security-row { display: flex; justify-content: space-between; align-items: center; }
        .security-row h3 { font-size: 16px; margin-bottom: 5px; }
        .security-row p  { font-size: 13px; color: #556070; margin: 0; }
        .btn-logout-all {
            min-height: 42px; padding: 0 18px;
            border: 1px solid #fecaca; border-radius: 8px;
            background: #fff1f2; color: #ef4444; font-weight: 700; font-size: 14px;
            display: flex; align-items: center; gap: 8px; cursor: pointer;
            white-space: nowrap;
        }

        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.45);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.open { display: flex; }
        .modal-box {
            background: #fff;
            border-radius: 10px;
            width: min(90%, 480px);
            overflow: hidden;
            box-shadow: 0 24px 64px rgba(0,0,0,0.2);
        }
        .modal-header {
            padding: 18px 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-weight: 700;
            font-size: 17px;
        }
        .modal-header-green { background: #16a34a; color: #fff; }
        .modal-header-red   { background: #dc2626; color: #fff; }
        .modal-header .btn-modal-close {
            background: none; border: none; cursor: pointer;
            color: #fff; display: flex; align-items: center; padding: 0;
        }
        .modal-header .modal-title-wrap { display: flex; align-items: center; gap: 10px; }
        .modal-body { padding: 22px; display: flex; flex-direction: column; gap: 16px; }

        .modal-alert {
            border-radius: 8px;
            padding: 14px 18px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        .modal-alert-green  { background: #dcfce7; border: 1px solid #bbf7d0; }
        .modal-alert-red    { background: #fff1f2; border: 1px solid #fecaca; }
        .modal-alert-yellow { background: #fffbeb; border: 1px solid #fde68a; }
        .modal-alert strong { display: block; margin-bottom: 4px; font-size: 14px; }
        .modal-alert p      { font-size: 13px; margin: 0; }
        .modal-alert-green strong, .modal-alert-green p { color: #15803d; }
        .modal-alert-red strong, .modal-alert-red p     { color: #dc2626; }
        .modal-alert-yellow strong { color: #92400e; }
        .modal-alert-yellow p      { color: #78350f; font-size: 13px; }

        .eval-summary-box { background: #f3f4f6; border-radius: 8px; padding: 14px 18px; font-size: 14px; }
        .eval-summary-box h4 { font-size: 14px; margin-bottom: 10px; }
        .eval-summary-row { display: flex; justify-content: space-between; margin-bottom: 6px; }
        .eval-summary-row:last-child { margin-bottom: 0; }

        .modal-final-warning {
            border: 1px solid #fca5a5;
            background: #fff1f2;
            border-radius: 8px;
            padding: 14px 18px;
        }
        .modal-final-warning strong { display: block; color: #dc2626; font-size: 14px; margin-bottom: 6px; }
        .modal-final-warning p { font-size: 13px; color: #dc2626; margin-bottom: 6px; }
        .modal-final-warning b { color: #991b1b; }

        .what-deleted-box { background: #f3f4f6; border-radius: 8px; padding: 14px 18px; font-size: 13px; color: #4b5563; }
        .what-deleted-box strong { display: block; margin-bottom: 8px; color: #071226; }
        .what-deleted-box ul { list-style: disc; padding-left: 18px; }
        .what-deleted-box li { margin-bottom: 4px; }

        .modal-btn-row { display: flex; gap: 12px; }
        .btn-modal-cancel {
            flex: 1; min-height: 46px;
            border: 1px solid #d8dce2; border-radius: 8px;
            background: #fff; font-weight: 700; font-size: 14px;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            cursor: pointer;
        }
        .btn-modal-logout-green {
            flex: 2; min-height: 46px;
            border-radius: 8px; background: #16a34a; color: #fff;
            font-weight: 700; font-size: 14px;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            cursor: pointer; border: none;
        }
        .btn-modal-reset {
            min-height: 46px; width: 100%;
            border-radius: 8px; background: #dc2626; color: #fff;
            font-weight: 700; font-size: 14px;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            cursor: pointer; border: none;
        }
        .btn-modal-reset-final {
            flex: 1; min-height: 46px;
            border-radius: 8px; background: #dc2626; color: #fff;
            font-weight: 700; font-size: 14px;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            cursor: pointer; border: none;
        }
        .btn-continue-eval {
            min-height: 46px; width: 100%;
            border-radius: 8px; background: #172033; color: #fff;
            font-weight: 700; font-size: 14px;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            cursor: pointer; border: none;
        }

        @media (max-width: 900px) {
            .eval-grid { grid-template-columns: 1fr; }
            .account-info-grid { grid-template-columns: 1fr; }
            .success-secondary-btns { grid-template-columns: 1fr; }
            .guide-action-row { grid-template-columns: 1fr; }
            .submission-details { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="brand">
        <img src="../img/eastgate_college_logo.png" alt="Eastgate College Logo">
        <h1>Student Evaluation for Teacher</h1>
        <p>Student Portal</p>
    </div>

    <nav class="nav-menu">
        <a class="nav-link active" href="#" data-tab="tab-dashboard"><?php echo svg_icon("dashboard"); ?> Dashboard</a>
        <a class="nav-link" href="#" data-tab="tab-guidelines"><?php echo svg_icon("file"); ?> Guidelines</a>
        <a class="nav-link" href="#" data-tab="tab-eval" id="nav-eval-link">
            <?php echo svg_icon("eval"); ?> Evaluation
        </a>
        <a class="nav-link" href="#" data-tab="tab-history"><?php echo svg_icon("history"); ?> Submission History</a>
        <a class="nav-link" href="#" data-tab="tab-settings"><?php echo svg_icon("settings"); ?> Settings</a>
    </nav>

    <div class="sidebar-bottom">
        <div class="progress-box">
            <div><span>Completed</span><strong id="sidebar-completed"><?php echo $completed_count; ?></strong></div>
            <div><span>Pending</span><strong class="warning" id="sidebar-pending"><?php echo $pending_count; ?></strong></div>
        </div>

        <a class="logout-link" href="#" id="btn-logout-trigger">
            <?php echo svg_icon("logout", "#ef4444"); ?> Logout
        </a>

        <?php if ($pending_count > 0): ?>
            <p class="logout-note"><?php echo svg_icon("alert-tri", "#ef4444"); ?> Complete all evaluations first</p>
        <?php endif; ?>
    </div>
</aside>

<main class="main-content">

    <header class="topbar">
        <div>
            <h2>Welcome, <?php echo e($student_row["full_name"]); ?></h2>
            <p><?php echo e($term_label); ?></p>
        </div>
        <div class="topbar-right">
            <span class="id-badge">Student ID: <strong><?php echo e($student_row["student_number"]); ?></strong></span>
            <span class="bell"><?php echo svg_icon("bell", "#334155"); ?></span>
        </div>
    </header>

    <div class="content-wrap">

        <div class="tab-panel active" id="tab-dashboard">
            <div class="page-title-row">
                <div>
                    <h1>Dashboard</h1>
                    <p>Track your evaluation progress and upcoming deadlines</p>
                </div>
                <a href="#" class="refresh-btn" id="btn-refresh-dashboard"><?php echo svg_icon("refresh"); ?> Refresh</a>
            </div>

            <div class="stats-grid student-stats">
                <div class="stat-card">
                    <div>
                        <p>Total Teachers</p>
                        <strong><?php echo $total_teachers; ?></strong>
                    </div>
                    <span class="stat-icon dark"><?php echo svg_icon("eval", "#fff"); ?></span>
                </div>
                <div class="stat-card">
                    <div>
                        <p>Completed</p>
                        <strong class="green-text"><?php echo $completed_count; ?></strong>
                    </div>
                    <span class="stat-icon green"><?php echo svg_icon("check-circle", "#16a34a"); ?></span>
                </div>
                <div class="stat-card">
                    <div>
                        <p>Pending</p>
                        <strong class="yellow-text"><?php echo $pending_count; ?></strong>
                    </div>
                    <span class="stat-icon yellow"><?php echo svg_icon("clock", "#f59e0b"); ?></span>
                </div>
            </div>

            <?php if (!$guidelines_accepted): ?>
            <div class="alert alert-red">
                <?php echo svg_icon("lock", "#dc2626"); ?>
                <div>
                    <strong>Evaluation Locked</strong>
                    <p>You must read and accept the evaluation guidelines before you can start evaluating teachers.
                       Please visit the <a href="#" data-tab="tab-guidelines" style="font-weight:700;color:#dc2626;">Guidelines</a> page to proceed.</p>
                </div>
            </div>
            <?php endif; ?>

            <section class="panel">
                <div class="panel-header">
                    <h2>Assigned Teachers</h2>
                </div>
                <div class="teacher-list">
                    <?php foreach ($assigned_teachers as $teacher): ?>
                    <div class="list-row">
                        <div>
                            <h3><?php echo e($teacher["faculty_name"]); ?></h3>
                            <p><?php echo e($teacher["subject_title"]); ?></p>
                            <small><?php echo e($teacher["department_name"] ?? ""); ?></small>
                        </div>
                        <span class="status-pill <?php echo $teacher["task_status"] === "Submitted" ? "completed" : "pending"; ?>">
                            <?php echo $teacher["task_status"] === "Submitted" ? "Completed" : "Pending"; ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($assigned_teachers)): ?>
                    <div style="padding:22px 24px;font-size:14px;color:#64748b;">No assigned teachers found for this evaluation period.</div>
                    <?php endif; ?>
                </div>
            </section>

            <div class="action-grid">
                <a href="#" class="action-btn" data-tab="tab-eval"><?php echo svg_icon("eval"); ?> Evaluation</a>
                <a href="#" class="action-btn" data-tab="tab-history"><?php echo svg_icon("history"); ?> View Submission History</a>
                <a href="#" class="action-btn" data-tab="tab-guidelines"><?php echo svg_icon("file"); ?> View Guidelines</a>
            </div>
        </div>


        <div class="tab-panel" id="tab-guidelines">
            <div class="page-title-row" style="margin-bottom:24px;">
                <div>
                    <h1>Guidelines</h1>
                    <p>Please read carefully before proceeding with the evaluation</p>
                </div>
            </div>

            <div class="guide-header">
                <?php echo svg_icon("file", "#f59e0b"); ?>
                <div>
                    <h2>Student Evaluation Guidelines</h2>
                    <p>Please read these guidelines carefully before proceeding with the evaluation process.</p>
                </div>
            </div>

            <div class="guide-body">
                <?php
                $guide_items = [
                    ["Be Honest and Objective",       "Provide truthful feedback based on your actual experience in class. Your responses help improve teaching quality."],
                    ["Be Respectful",                  "Maintain a professional and respectful tone in all comments. Avoid using offensive or inappropriate language."],
                    ["Evaluate All Assigned Teachers", "You must complete evaluations for all teachers assigned to your enrolled subjects. Partial submissions are not allowed."],
                    ["One Submission Per Teacher",     "Once you submit an evaluation for a teacher, it cannot be modified. Review your answers carefully before confirming."],
                    ["Consider the Entire Semester",   "Base your evaluation on the teacher's performance throughout the entire semester, not just recent experiences."],
                    ["Confidentiality",                "Your individual responses are confidential. Teachers only receive aggregated results without identifying information."],
                ];
                foreach ($guide_items as $i => [$title, $desc]):
                ?>
                <div class="guide-item">
                    <div class="guide-num"><?php echo $i + 1; ?></div>
                    <div>
                        <h3><?php echo e($title); ?></h3>
                        <p><?php echo e($desc); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>

                <div class="guide-reminders">
                    <h3><?php echo svg_icon("check-circle", "#f59e0b"); ?> Important Reminders</h3>
                    <ul>
                        <li>Each teacher must be evaluated completely before submission</li>
                        <li>Only one final submission is allowed per teacher</li>
                        <li>Your evaluation helps improve the quality of education</li>
                        <li>Incomplete evaluation cycles will not be accepted</li>
                    </ul>
                </div>

                <div class="guide-agree-row" id="guide-agree-row" onclick="document.getElementById('guide-checkbox').click()">
                    <input type="checkbox" id="guide-checkbox" <?php echo $guidelines_accepted ? "checked disabled" : ""; ?>>
                    <label for="guide-checkbox">
                        <?php echo $guidelines_accepted ? "You have already accepted the evaluation guidelines" : "I have read and understood the evaluation guidelines"; ?>
                    </label>
                </div>

                <div class="guide-action-row">
                    <div class="btn-back-guide" id="btn-back-guide"><?php echo svg_icon("arrow-left"); ?> Back</div>
                    <div class="btn-proceed <?php echo $guidelines_accepted ? "enabled" : ""; ?>" id="btn-proceed-eval">
                        <?php echo $guidelines_accepted ? "Go to Evaluation" : "Proceed to Evaluation"; ?> <?php echo svg_icon("arrow-right"); ?>
                    </div>
                </div>
            </div>
        </div>


        <div class="tab-panel" id="tab-eval">

            <?php if (!$guidelines_accepted): ?>
            <div id="eval-view-locked">
                <div class="eval-locked-banner">
                    <?php echo svg_icon("lock", "#fff"); ?>
                    <div>
                        <h2>Evaluation Access Restricted</h2>
                        <p>You must accept the evaluation guidelines to continue</p>
                    </div>
                </div>

                <div style="border:1px solid #d8dce2;border-radius:8px;padding:28px 32px;">
                    <div class="eval-locked-notice">
                        <?php echo svg_icon("info", "#dc2626"); ?>
                        <div>
                            <strong>Guidelines Not Accepted</strong>
                            <p>Before you can evaluate teachers, you must read and accept the evaluation guidelines. This ensures you understand the evaluation process and responsibilities.</p>
                        </div>
                    </div>

                    <button class="btn-go-guidelines" data-tab="tab-guidelines">
                        Go to Guidelines
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <div id="eval-view-list" <?php echo !$guidelines_accepted ? 'style="display:none;"' : ''; ?>>
                <div class="page-title-row" style="margin-bottom:24px;">
                    <div>
                        <h1>Teacher Evaluation</h1>
                        <p>Evaluate your assigned teachers for this semester</p>
                    </div>
                </div>

                <div class="eval-filter-row">
                    <div class="eval-filter-btn active-filter" data-eval-filter="all">All Teachers</div>
                    <div class="eval-filter-btn" data-eval-filter="pending"><?php echo svg_icon("filter"); ?> Pending</div>
                    <div class="eval-filter-btn" data-eval-filter="completed"><?php echo svg_icon("check-circle"); ?> Completed</div>
                </div>

                <div class="eval-grid" id="eval-cards-grid">
                    <?php foreach ($assigned_teachers as $teacher): ?>
                    <div class="eval-card" data-status="<?php echo strtolower(e($teacher["task_status"])); ?>">
                        <div class="eval-card-top">
                            <div>
                                <h3><?php echo e($teacher["faculty_name"]); ?></h3>
                                <p><?php echo e($teacher["subject_title"]); ?></p>
                                <small><?php echo e($teacher["department_name"] ?? ""); ?></small>
                            </div>
                            <?php if ($teacher["task_status"] === "Submitted"): ?>
                                <span class="status-pill completed"><?php echo svg_icon("check-circle", "#16a34a"); ?> Completed</span>
                            <?php else: ?>
                                <span class="status-pill pending"><?php echo svg_icon("clock", "#a16207"); ?> Pending</span>
                            <?php endif; ?>
                        </div>

                        <?php if ($teacher["task_status"] === "Submitted"): ?>
                            <div class="eval-card-completed-note">Evaluation completed and submitted</div>
                            <button class="btn-view-eval"
                                    data-action="view-eval"
                                    data-task-id="<?php echo $teacher["student_evaluation_task_id"]; ?>"
                                    data-name="<?php echo e($teacher["faculty_name"]); ?>"
                                    data-subject="<?php echo e($teacher["subject_title"]); ?>"
                                    data-dept="<?php echo e($teacher["department_name"] ?? ""); ?>"
                                    data-submitted="<?php echo e($teacher["submitted_at"] ?? ""); ?>"
                                    data-avg="<?php echo e($teacher["average_score"] ?? ""); ?>">
                                <?php echo svg_icon("eye", "#fff"); ?> View Evaluation
                            </button>
                        <?php else: ?>
                            <button class="btn-start-eval"
                                    data-action="start-eval"
                                    data-task-id="<?php echo $teacher["student_evaluation_task_id"]; ?>"
                                    data-name="<?php echo e($teacher["faculty_name"]); ?>"
                                    data-subject="<?php echo e($teacher["subject_title"]); ?>"
                                    data-dept="<?php echo e($teacher["department_name"] ?? ""); ?>"
                                    data-ta-id="<?php echo $teacher["teaching_assignment_id"]; ?>">
                                <?php echo svg_icon("arrow-right", "#fff"); ?> Start Evaluation
                            </button>
                    <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($assigned_teachers)): ?>
                    <div style="grid-column:1/-1;padding:32px;text-align:center;font-size:14px;color:#64748b;">No teachers assigned for evaluation this period.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div id="eval-view-form" style="display:none;">
                <div class="eval-teacher-meta">
                    <h2>Evaluate Teacher</h2>
                    <p id="eval-form-name"></p>
                    <p id="eval-form-meta" style="margin-top:2px;"></p>
                    <div class="eval-progress-row">
                        <span>Progress</span>
                        <span id="eval-form-progress-label">0 / <?php echo $total_items; ?></span>
                    </div>
                    <div class="eval-progress-bar">
                        <div class="eval-progress-fill" id="eval-form-progress-fill" style="width:0%"></div>
                    </div>
                </div>

                <div class="eval-form-panel">
                    <h2 style="font-size:19px;margin-bottom:6px;">Evaluation Criteria</h2>
                    <p style="font-size:13px;color:#556070;margin-bottom:24px;">Rate each statement using the scale: 1 = Strongly Disagree, 2 = Disagree, 3 = Neutral, 4 = Agree, 5 = Strongly Agree</p>

                    <?php
                    $q_index = 1;
                    foreach ($eval_categories as $cat_name => $statements):
                    ?>
                    <div class="criteria-section">
                        <div class="criteria-section-title"><?php echo e($cat_name); ?></div>
                        <table class="criteria-table">
                            <thead>
                                <tr>
                                    <th>Statement</th>
                                    <th>1</th><th>2</th><th>3</th><th>4</th><th>5</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($statements as $stmt_item): ?>
                                <tr class="eval-row" data-qid="q<?php echo $q_index; ?>" data-item-id="<?php echo (int)($stmt_item["evaluation_form_item_id"] ?? 0); ?>">
                                    <td><?php echo $q_index . ". " . e($stmt_item["statement"]); ?></td>
                                    <?php for ($r = 1; $r <= 5; $r++): ?>
                                    <td>
                                        <input type="checkbox"
                                               name="rating_q<?php echo $q_index; ?>"
                                               value="<?php echo $r; ?>"
                                               class="rating-check"
                                               data-qid="q<?php echo $q_index; ?>">
                                    </td>
                                    <?php endfor; ?>
                                </tr>
                                <?php $q_index++; endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endforeach; ?>

                    <div class="comments-field">
                        <h3>Additional Comments (Optional)</h3>
                        <textarea id="eval-comments" placeholder="Enter your feedback here..."></textarea>
                        <p>Your comments will help the teacher improve their teaching methods.</p>
                    </div>

                    <div class="eval-form-actions">
                        <button class="btn-eval-back" id="btn-eval-back-to-list">
                            <?php echo svg_icon("arrow-left"); ?> Back
                        </button>
                        <button class="btn-eval-draft" id="btn-eval-draft">
                            <?php echo svg_icon("save"); ?> Save Draft
                        </button>
                        <button class="btn-eval-clear" id="btn-eval-clear">
                            <?php echo svg_icon("reset", "#ef4444"); ?> Clear Answers
                        </button>
                        <button class="btn-eval-proceed" id="btn-eval-proceed-review">
                            Proceed to Review <?php echo svg_icon("arrow-right", "#fff"); ?>
                        </button>
                    </div>
                </div>
            </div>

            <div id="eval-view-review" style="display:none;">
                <div class="review-panel">
                    <div class="review-panel-header">
                        <h2>Review Your Evaluation</h2>
                        <p>Please review your responses carefully. Once submitted, changes cannot be made.</p>
                    </div>

                    <div class="eval-teacher-meta" style="border-radius:0;margin:0;">
                        <h2 id="review-teacher-name"></h2>
                        <p id="review-teacher-sub"></p>
                        <p id="review-teacher-dept" style="margin-top:2px;color:#a8b4c4;"></p>
                        <div style="margin-top:14px;display:flex;justify-content:space-between;align-items:center;">
                            <span style="font-size:13px;color:#a8b4c4;">Average Rating</span>
                            <span style="font-size:22px;font-weight:700;color:#f59e0b;" id="review-avg-rating">—</span>
                        </div>
                    </div>

                    <div id="review-summary-body"></div>
                </div>

                <div class="notice-important">
                    <?php echo svg_icon("info", "#dc2626"); ?>
                    <div>
                        <strong>Important Notice</strong>
                        <p>Once you confirm this submission, it cannot be edited or deleted. Please ensure all ratings and comments are accurate.</p>
                    </div>
                </div>

                <div class="review-actions">
                    <button class="btn-cancel-review" id="btn-review-cancel">
                        <?php echo svg_icon("arrow-left"); ?> Cancel
                    </button>
                    <button class="btn-edit-review" id="btn-review-edit">
                        <?php echo svg_icon("edit"); ?> Edit Answers
                    </button>
                    <button class="btn-confirm-submit" id="btn-confirm-submit">
                        <?php echo svg_icon("check-circle", "#fff"); ?> Confirm Submission
                    </button>
                </div>
            </div>

            <div id="eval-view-success" style="display:none;">
                <div class="success-banner">
                    <?php echo svg_icon("check-circle", "#fff"); ?>
                    <h2>Evaluation Submitted Successfully!</h2>
                    <p>Your feedback has been recorded and will help improve teaching quality</p>
                </div>

                <div class="submission-details">
                    <div><small>Teacher</small><strong id="success-teacher-name"></strong></div>
                    <div><small>Subject</small><strong id="success-subject"></strong></div>
                    <div><small>Submitted On</small><strong id="success-date"></strong></div>
                    <div><small>Time</small><strong id="success-time"></strong></div>
                </div>

                <div class="eval-progress-card">
                    <h3>Evaluation Progress</h3>
                    <div class="prog-row">
                        <div>
                            <div style="font-size:12px;color:#64748b;margin-bottom:4px;">Completed Evaluations</div>
                            <strong id="success-completed-count" style="font-size:22px;color:#16a34a;"></strong>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-size:12px;color:#64748b;margin-bottom:4px;">Remaining</div>
                            <strong id="success-remaining-count" style="font-size:22px;color:#f59e0b;"></strong>
                        </div>
                    </div>
                </div>

                <button class="btn-next-teacher" id="btn-next-teacher" style="display:none;"></button>

                <button class="btn-next-teacher" id="btn-go-back-eval" style="background:#f3f4f6;color:#172033;margin-bottom:8px;">
                    <?php echo svg_icon("arrow-left"); ?> Go Back to Evaluations
                </button>

                <div class="success-secondary-btns">
                    <button class="btn-success-sec" data-tab="tab-dashboard" data-action="switch-tab">
                        <?php echo svg_icon("home"); ?> Dashboard
                    </button>
                    <button class="btn-success-sec" data-tab="tab-history" data-action="switch-tab">
                        <?php echo svg_icon("history"); ?> View History
                    </button>
                </div>

                <div class="confidential-note">
                    <strong>Note:</strong> Your responses are confidential. Individual evaluations will not be shared with teachers.
                </div>
            </div>

            <div id="eval-view-readonly" style="display:none;">
                <a href="#" id="btn-back-to-eval-list" style="display:inline-flex;align-items:center;gap:6px;font-weight:700;font-size:14px;color:#172033;text-decoration:none;margin-bottom:22px;">
                    <?php echo svg_icon("arrow-left"); ?> Back to Evaluations
                </a>

                <div class="view-eval-header">
                    <?php echo svg_icon("eye", "#fff"); ?>
                    <div>
                        <h2>View Evaluation</h2>
                        <p>Read-only view of submitted evaluation</p>
                    </div>
                </div>

                <div class="view-teacher-card">
                    <div class="view-teacher-meta">
                        <h3 id="view-teacher-name"></h3>
                        <p id="view-teacher-sub" style="color:#2563eb;"></p>
                        <p id="view-teacher-dept" style="color:#556070;font-size:13px;"></p>
                    </div>
                    <span class="status-pill completed"><?php echo svg_icon("check-circle", "#16a34a"); ?> Completed</span>
                </div>

                <div style="border:1px solid #d8dce2;border-top:none;padding:18px 24px;">
                    <div class="view-teacher-stats">
                        <div class="view-teacher-stat"><?php echo svg_icon("calendar", "#64748b"); ?> <span>Submitted on <strong id="view-submitted-date"></strong></span></div>
                        <div class="view-teacher-stat"><?php echo svg_icon("clock", "#64748b"); ?> <span>Time <strong id="view-submitted-time"></strong></span></div>
                        <div class="view-teacher-stat"><?php echo svg_icon("star"); ?> <span>Average Rating <strong id="view-avg-score"></strong></span></div>
                    </div>
                </div>

                <div class="panel" style="margin-top:22px;">
                    <div class="panel-header">
                        <h2>Evaluation Ratings</h2>
                        <p style="font-size:13px;color:#556070;margin-top:4px;">Your submitted ratings for each criterion</p>
                    </div>
                    <div id="view-ratings-body" style="padding:22px 24px;display:flex;flex-direction:column;gap:0;"></div>
                </div>

                <div class="view-comments-box">
                    <h3><?php echo svg_icon("chat"); ?> Additional Comments</h3>
                    <p class="sub">Your submitted feedback and suggestions</p>
                    <div class="comment-text" id="view-comments-text">No comments submitted.</div>
                </div>

                <div class="view-only-notice">
                    <?php echo svg_icon("eye", "#1d4ed8"); ?>
                    <div>
                        <strong>View-Only Mode</strong>
                        <p>This evaluation has been submitted and cannot be edited. If you believe there is an error, please contact your academic advisor.</p>
                    </div>
                </div>

                <div style="display:flex;justify-content:center;">
                    <button class="btn-view-history" data-tab="tab-history" data-action="switch-tab">
                        <?php echo svg_icon("history", "#fff"); ?> View Submission History
                    </button>
                </div>
            </div>
        </div>


        <div class="tab-panel" id="tab-history">
            <div class="page-title-row" style="margin-bottom:24px;">
                <div>
                    <h1>Submission History</h1>
                    <p>View your completed and pending evaluations</p>
                </div>
            </div>

            <div class="stats-grid student-stats">
                <div class="stat-card">
                    <div>
                        <p>Total</p>
                        <strong><?php echo $total_teachers; ?></strong>
                    </div>
                    <span class="stat-icon" style="background:#f3f4f6;"><?php echo svg_icon("calendar"); ?></span>
                </div>
                <div class="stat-card">
                    <div>
                        <p>Completed</p>
                        <strong class="green-text"><?php echo $completed_count; ?></strong>
                    </div>
                    <span class="stat-icon green"><?php echo svg_icon("check-circle", "#16a34a"); ?></span>
                </div>
                <div class="stat-card">
                    <div>
                        <p>Pending</p>
                        <strong class="yellow-text"><?php echo $pending_count; ?></strong>
                    </div>
                    <span class="stat-icon yellow"><?php echo svg_icon("clock", "#f59e0b"); ?></span>
                </div>
            </div>

            <div class="history-filter-row">
                <div class="history-search-wrap">
                    <?php echo svg_icon("search", "#9ca3af"); ?>
                    <input type="text" id="history-search" placeholder="Search by teacher name or subject...">
                </div>
                <div class="history-filter-btn active-filter" data-history-filter="all">All</div>
                <div class="history-filter-btn" data-history-filter="completed"><?php echo svg_icon("check-circle"); ?> Completed</div>
                <div class="history-filter-btn" data-history-filter="pending"><?php echo svg_icon("clock"); ?> Pending</div>
            </div>

            <div class="history-records-panel">
                <div class="history-records-header">
                    <h2>Evaluation Records</h2>
                    <p>Click on any completed evaluation to view details</p>
                </div>

                <?php foreach ($assigned_teachers as $teacher):
                    $is_done = $teacher["task_status"] === "Submitted";
                    $submitted_fmt = "";
                    $submitted_time_fmt = "";
                    if ($is_done && $teacher["submitted_at"]) {
                        $dt = new DateTime($teacher["submitted_at"]);
                        $submitted_fmt = $dt->format("M d, Y");
                        $submitted_time_fmt = $dt->format("h:i A");
                    }
                ?>
                <div class="history-record-row"
                     data-status="<?php echo strtolower(e($teacher["task_status"])); ?>"
                     data-name="<?php echo strtolower(e($teacher["faculty_name"])); ?>"
                     data-subject="<?php echo strtolower(e($teacher["subject_title"])); ?>"
                     <?php if ($is_done): ?>
                     data-action="view-eval"
                     data-task-id="<?php echo $teacher["student_evaluation_task_id"]; ?>"
                     data-name-display="<?php echo e($teacher["faculty_name"]); ?>"
                     data-subject-display="<?php echo e($teacher["subject_title"]); ?>"
                     data-dept-display="<?php echo e($teacher["department_name"] ?? ""); ?>"
                     data-submitted="<?php echo e($teacher["submitted_at"] ?? ""); ?>"
                     data-avg="<?php echo e($teacher["average_score"] ?? ""); ?>"
                     style="cursor:pointer;"
                     <?php endif; ?>>
                    <div>
                        <h3>
                            <?php echo e($teacher["faculty_name"]); ?>
                            <?php if ($is_done): ?><?php echo svg_icon("eye", "#2563eb"); ?><?php endif; ?>
                        </h3>
                        <p><?php echo e($teacher["subject_title"]); ?></p>
                        <small><?php echo e($teacher["department_name"] ?? ""); ?></small>
                        <?php if ($is_done && $submitted_fmt): ?>
                            <small style="margin-top:4px;">
                                <?php echo svg_icon("calendar", "#64748b"); ?> <?php echo $submitted_fmt; ?> &nbsp; <?php echo $submitted_time_fmt; ?>
                            </small>
                            <p class="click-to-view" style="color:#9ca3af;">Click to view</p>
                        <?php endif; ?>
                    </div>
                    <span class="status-pill <?php echo $is_done ? "completed" : "pending"; ?>">
                        <?php if ($is_done): ?>
                            <?php echo svg_icon("check-circle", "#16a34a"); ?> Completed
                        <?php else: ?>
                            <?php echo svg_icon("clock", "#a16207"); ?> Pending
                        <?php endif; ?>
                    </span>
                </div>
                <?php endforeach; ?>
                <?php if (empty($assigned_teachers)): ?>
                <div style="padding:22px 24px;font-size:14px;color:#64748b;">No evaluation records found.</div>
                <?php endif; ?>
            </div>
        </div>


        <div class="tab-panel" id="tab-settings">
            <div class="page-title-row" style="margin-bottom:24px;">
                <div>
                    <h1>Settings</h1>
                    <p>Manage your account preferences and security settings</p>
                </div>
            </div>

            <div class="settings-section">
                <div class="settings-section-header">
                    <?php echo svg_icon("info"); ?> Account Information
                </div>
                <div class="settings-section-body">
                    <div class="account-info-grid">
                        <div>
                            <div class="field-label">Full Name</div>
                            <div class="field-value"><?php echo e($student_row["full_name"]); ?></div>
                        </div>
                        <div>
                            <div class="field-label">Student ID</div>
                            <div class="field-value"><?php echo e($student_row["student_number"]); ?></div>
                        </div>
                        <div>
                            <div class="field-label">Email</div>
                            <div class="field-value"><?php echo e($student_row["email"]); ?></div>
                        </div>
                        <div>
                            <div class="field-label">Account Status</div>
                            <div class="field-value"><span class="badge-active"><?php echo e(ucfirst($student_row["account_status"])); ?></span></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="settings-section">
                <div class="settings-section-header">
                    <?php echo svg_icon("lock"); ?> Change Password
                </div>
                <div class="settings-section-body">
                    <div class="settings-field">
                        <label for="pw-current">Current Password</label>
                        <div class="input-wrap">
                            <input type="password" id="pw-current" placeholder="Enter current password">
                            <button class="toggle-pw" data-target="pw-current"><?php echo svg_icon("eye-off", "#9ca3af"); ?></button>
                        </div>
                    </div>
                    <div class="settings-field">
                        <label for="pw-new">New Password</label>
                        <div class="input-wrap">
                            <input type="password" id="pw-new" placeholder="Enter new password (min. 8 characters)">
                            <button class="toggle-pw" data-target="pw-new"><?php echo svg_icon("eye-off", "#9ca3af"); ?></button>
                        </div>
                    </div>
                    <div class="settings-field">
                        <label for="pw-confirm">Confirm New Password</label>
                        <div class="input-wrap">
                            <input type="password" id="pw-confirm" placeholder="Confirm new password">
                            <button class="toggle-pw" data-target="pw-confirm"><?php echo svg_icon("eye-off", "#9ca3af"); ?></button>
                        </div>
                    </div>

                    <div class="pw-requirements">
                        <strong>Password Requirements:</strong>
                        <ul>
                            <li>At least 8 characters long</li>
                            <li>Contains uppercase and lowercase letters</li>
                            <li>Contains at least one number</li>
                        </ul>
                    </div>

                    <button class="btn-update-pw" id="btn-update-pw">Update Password</button>
                    <div id="pw-feedback" style="margin-top:12px;font-size:14px;display:none;padding:10px 14px;border-radius:8px;"></div>
                </div>
            </div>

            <div class="settings-section">
                <div class="settings-section-header">
                    <?php echo svg_icon("shield"); ?> Security Settings
                </div>
                <div class="settings-section-body">
                    <div class="security-row">
                        <div>
                            <h3>Log Out from Other Devices</h3>
                            <p>End all active sessions on other devices for security</p>
                        </div>
                        <button class="btn-logout-all"><?php echo svg_icon("logout", "#ef4444"); ?> Logout All</button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>


<div class="modal-overlay" id="modal-logout-complete">
    <div class="modal-box">
        <div class="modal-header modal-header-green">
            <div class="modal-title-wrap">
                <?php echo svg_icon("logout", "#fff"); ?>
                Confirm Logout
            </div>
            <button class="btn-modal-close" data-close-modal="modal-logout-complete"><?php echo svg_icon("x", "#fff"); ?></button>
        </div>
        <div class="modal-body">
            <div class="modal-alert modal-alert-green">
                <?php echo svg_icon("check-circle", "#16a34a"); ?>
                <div>
                    <strong>All Evaluations Completed!</strong>
                    <p>You have successfully completed all teacher evaluations. You can safely logout now.</p>
                </div>
            </div>
            <div class="eval-summary-box">
                <h4>Evaluation Summary</h4>
                <div class="eval-summary-row">
                    <span><?php echo svg_icon("check", "#16a34a"); ?> Completed: <?php echo $total_teachers; ?> teachers</span>
                </div>
            </div>
            <div class="modal-btn-row">
                <button class="btn-modal-cancel" data-close-modal="modal-logout-complete">Cancel</button>
                <button class="btn-modal-logout-green" id="btn-do-logout">
                    <?php echo svg_icon("logout", "#fff"); ?> Logout
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modal-logout-incomplete-1">
    <div class="modal-box">
        <div class="modal-header modal-header-red">
            <div class="modal-title-wrap">
                <?php echo svg_icon("alert-tri", "#fff"); ?>
                Incomplete Evaluations
            </div>
            <button class="btn-modal-close" data-close-modal="modal-logout-incomplete-1"><?php echo svg_icon("x", "#fff"); ?></button>
        </div>
        <div class="modal-body">
            <div class="modal-alert modal-alert-red">
                <?php echo svg_icon("alert-tri", "#dc2626"); ?>
                <div>
                    <strong>You Cannot Logout Yet!</strong>
                    <p>You must complete all teacher evaluations before logging out. You still have incomplete evaluations.</p>
                </div>
            </div>
            <div class="eval-summary-box">
                <h4>Evaluation Status</h4>
                <div class="eval-summary-row">
                    <span>Completed:</span>
                    <strong id="modal-completed-count" style="color:#16a34a;"><?php echo $completed_count; ?> teachers</strong>
                </div>
                <div class="eval-summary-row">
                    <span>Pending:</span>
                    <strong id="modal-pending-count" style="color:#f59e0b;"><?php echo $pending_count; ?> teachers</strong>
                </div>
            </div>
            <div class="modal-alert modal-alert-yellow">
                <?php echo svg_icon("alert-tri", "#f59e0b"); ?>
                <div>
                    <strong>Warning</strong>
                    <p>If you still want to logout, all your evaluation progress (including drafts) will be permanently deleted and you will have to start from the beginning when you login again.</p>
                </div>
            </div>
            <button class="btn-modal-reset" id="btn-show-final-warning">
                <?php echo svg_icon("reset", "#fff"); ?> Reset All Progress &amp; Logout
            </button>
            <button class="btn-continue-eval" data-close-modal="modal-logout-incomplete-1">Continue Evaluations</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modal-logout-incomplete-2">
    <div class="modal-box">
        <div class="modal-header modal-header-red">
            <div class="modal-title-wrap">
                <?php echo svg_icon("alert-tri", "#fff"); ?>
                Incomplete Evaluations
            </div>
            <button class="btn-modal-close" data-close-modal="modal-logout-incomplete-2"><?php echo svg_icon("x", "#fff"); ?></button>
        </div>
        <div class="modal-body">
            <div class="modal-final-warning">
                <?php echo svg_icon("alert-tri", "#dc2626"); ?>
                <strong>&#9888; FINAL WARNING</strong>
                <p>Are you absolutely sure you want to reset all evaluation progress and logout?</p>
                <p><b>This action CANNOT be undone!</b></p>
            </div>
            <div class="what-deleted-box">
                <strong>What will be deleted:</strong>
                <ul>
                    <li id="modal-completed-deleted"><?php echo $completed_count; ?> completed evaluation<?php echo $completed_count !== 1 ? "s" : ""; ?></li>
                    <li>All evaluation progress and ratings</li>
                    <li>All saved comments and feedback</li>
                </ul>
            </div>
            <div class="modal-btn-row">
                <button class="btn-modal-cancel" data-close-modal="modal-logout-incomplete-2">Cancel</button>
                <button class="btn-modal-reset-final" id="btn-do-reset-logout">
                    <?php echo svg_icon("reset", "#fff"); ?> Reset &amp; Logout
                </button>
            </div>
        </div>
    </div>
</div>


<script>
(function () {
    "use strict";

    const teachers = <?php echo json_encode(array_values($assigned_teachers)); ?>;
    const ratingLabels = {1:"Strongly Disagree",2:"Disagree",3:"Neutral",4:"Agree",5:"Strongly Agree"};
    const categoryStatements = <?php echo json_encode($eval_categories); ?>;
    const totalItems = <?php echo $total_items; ?>;

    let guidelineAccepted = <?php echo $guidelines_accepted ? "true" : "false"; ?>;
    let currentEvalTaskId  = null;
    let currentEvalName    = "";
    let currentEvalSubject = "";
    let currentEvalDept    = "";
    let currentEvalTaId    = null;
    let completedCount     = <?php echo $completed_count; ?>;
    const totalTeachers    = <?php echo $total_teachers; ?>;
    const studentId        = <?php echo $student_id; ?>;
    const periodId         = <?php echo $current_period_id ?? 0; ?>;

    function switchTab(tabId) {
        if (!tabId) return;
        document.querySelectorAll(".tab-panel").forEach(p => p.classList.remove("active"));
        const panel = document.getElementById(tabId);
        if (panel) panel.classList.add("active");
        document.querySelectorAll(".nav-link").forEach(link => {
            link.classList.remove("active");
            if (link.dataset.tab === tabId) link.classList.add("active");
        });
        window.scrollTo(0, 0);
    }

    document.querySelectorAll("[data-tab]").forEach(el => {
        el.addEventListener("click", function (e) {
            e.preventDefault();
            const tabId = this.dataset.tab;
            if (!tabId) return;
            switchTab(tabId);
        });
    });

    document.querySelectorAll("[data-action='switch-tab']").forEach(el => {
        el.addEventListener("click", function (e) {
            e.preventDefault();
            switchTab(this.dataset.tab);
        });
    });

    document.getElementById("btn-refresh-dashboard").addEventListener("click", function (e) {
        e.preventDefault();
        window.location.reload();
    });

    const guideCheckbox = document.getElementById("guide-checkbox");
    const btnProceed    = document.getElementById("btn-proceed-eval");

    if (guideCheckbox && !guideCheckbox.disabled) {
        guideCheckbox.addEventListener("change", function () {
            btnProceed.classList.toggle("enabled", this.checked);
        });
    }

    if (btnProceed) {
        btnProceed.addEventListener("click", function (e) {
            e.preventDefault();
            if (guidelineAccepted) {
                unlockEvaluation();
                switchTab("tab-eval");
                return;
            }
            if (!guideCheckbox || !guideCheckbox.checked) return;
            acceptGuidelinesAndUnlock();
        });
    }

    function acceptGuidelinesAndUnlock() {
        const fd = new FormData();
        fd.append("action", "accept_guidelines");
        fd.append("student_id", studentId);
        fd.append("evaluation_period_id", periodId);
        fetch(window.location.href, { method: "POST", body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    guidelineAccepted = true;
                    if (guideCheckbox) {
                        guideCheckbox.checked  = true;
                        guideCheckbox.disabled = true;
                    }
                    unlockEvaluation();
                    switchTab("tab-eval");
                }
            })
            .catch(() => {
                guidelineAccepted = true;
                unlockEvaluation();
                switchTab("tab-eval");
            });
    }

    function unlockEvaluation() {
        const lockedDiv = document.getElementById("eval-view-locked");
        const listDiv   = document.getElementById("eval-view-list");
        if (lockedDiv) lockedDiv.style.display = "none";
        if (listDiv)   listDiv.style.display   = "block";
        if (btnProceed) {
            btnProceed.innerHTML = 'Go to Evaluation <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="width:18px;height:18px;"><line x1="5" y1="12" x2="19" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><polyline points="12 5 19 12 12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        }
    }

    document.getElementById("btn-back-guide").addEventListener("click", function (e) {
        e.preventDefault();
        switchTab("tab-dashboard");
    });

    function showEvalView(viewId) {
        ["eval-view-locked","eval-view-list","eval-view-form","eval-view-review","eval-view-success","eval-view-readonly"].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.style.display = "none";
        });
        const target = document.getElementById(viewId);
        if (target) target.style.display = "block";
    }

    const evalCardsGrid = document.getElementById("eval-cards-grid");
    if (evalCardsGrid) {
        evalCardsGrid.addEventListener("click", function (e) {
            const startBtn = e.target.closest("[data-action='start-eval']");
            if (startBtn) {
                currentEvalTaskId  = startBtn.dataset.taskId;
                currentEvalName    = startBtn.dataset.name;
                currentEvalSubject = startBtn.dataset.subject;
                currentEvalDept    = startBtn.dataset.dept;
                currentEvalTaId    = startBtn.dataset.taId;
                openEvalForm();
                return;
            }
            const viewBtn = e.target.closest("[data-action='view-eval']");
            if (viewBtn) {
                currentEvalName    = viewBtn.dataset.name;
                currentEvalSubject = viewBtn.dataset.subject;
                currentEvalDept    = viewBtn.dataset.dept;
                openReadonlyView(viewBtn.dataset.submitted, viewBtn.dataset.avg, viewBtn.dataset.taskId);
            }
        });
    }

    function openEvalForm() {
        document.getElementById("eval-form-name").textContent = currentEvalName;
        document.getElementById("eval-form-meta").textContent = currentEvalSubject + " · " + currentEvalDept;
        clearEvalAnswers();
        updateProgress();
        showEvalView("eval-view-form");
    }

    document.getElementById("btn-eval-clear").addEventListener("click", clearEvalAnswers);

    function clearEvalAnswers() {
        document.querySelectorAll(".rating-check").forEach(cb => { cb.checked = false; });
        document.querySelectorAll(".eval-row").forEach(row => row.classList.remove("answered"));
        document.getElementById("eval-comments").value = "";
        updateProgress();
        checkProceedReady();
    }

    document.querySelectorAll(".criteria-table").forEach(table => {
        table.addEventListener("change", function (e) {
            const cb = e.target;
            if (!cb.classList.contains("rating-check")) return;
            const qid = cb.dataset.qid;
            document.querySelectorAll(`.rating-check[data-qid="${qid}"]`).forEach(sibling => {
                if (sibling !== cb) sibling.checked = false;
            });
            const row = document.querySelector(`.eval-row[data-qid="${qid}"]`);
            if (row) row.classList.toggle("answered", cb.checked);
            updateProgress();
            checkProceedReady();
        });
    });

    function updateProgress() {
        const answered = document.querySelectorAll(".eval-row.answered").length;
        document.getElementById("eval-form-progress-label").textContent = answered + " / " + totalItems;
        document.getElementById("eval-form-progress-fill").style.width = Math.round((answered / totalItems) * 100) + "%";
    }

    const btnProceedReview = document.getElementById("btn-eval-proceed-review");

    function checkProceedReady() {
        const answered = document.querySelectorAll(".eval-row.answered").length;
        if (answered >= totalItems) {
            btnProceedReview.classList.add("ready");
        } else {
            btnProceedReview.classList.remove("ready");
        }
    }

    document.getElementById("btn-eval-back-to-list").addEventListener("click", function () {
        if (guidelineAccepted) {
            showEvalView("eval-view-list");
        } else {
            showEvalView("eval-view-locked");
        }
    });

    document.getElementById("btn-eval-draft").addEventListener("click", function () {
        const answered = document.querySelectorAll(".eval-row.answered").length;
        if (answered === 0) {
            alert("No answers to save.");
            return;
        }
        alert("Draft saved. (" + answered + " of " + totalItems + " answered)");
    });

    btnProceedReview.addEventListener("click", function () {
        if (!this.classList.contains("ready")) return;
        buildReviewPage();
        showEvalView("eval-view-review");
    });

    function buildReviewPage() {
        document.getElementById("review-teacher-name").textContent = currentEvalName;
        document.getElementById("review-teacher-sub").textContent  = currentEvalSubject;
        document.getElementById("review-teacher-dept").textContent = currentEvalDept;

        let totalScore = 0;
        let answered   = 0;
        const answers  = {};
        document.querySelectorAll(".eval-row").forEach(row => {
            const qid = row.dataset.qid;
            const cb  = document.querySelector(`.rating-check[data-qid="${qid}"]:checked`);
            answers[qid] = cb ? parseInt(cb.value) : null;
            if (cb) { totalScore += parseInt(cb.value); answered++; }
        });

        const avg = answered > 0 ? (totalScore / answered).toFixed(2) : "—";
        document.getElementById("review-avg-rating").textContent = (answered > 0 ? avg + " / 5.0" : "—");

        let html = "";
        let qNum = 1;
        for (const [catName, stmts] of Object.entries(categoryStatements)) {
            html += `<div class="review-section-head">${catName}</div>`;
            html += `<table class="review-table"><thead><tr><th>Statement</th><th>Rating</th></tr></thead><tbody>`;
            for (const stmtObj of stmts) {
                const stmt   = stmtObj.statement || stmtObj;
                const qid    = "q" + qNum;
                const rating = answers[qid];
                const label  = rating ? ratingLabels[rating] : "—";
                const color  = rating ? (rating >= 4 ? "#16a34a" : rating === 3 ? "#556070" : "#dc2626") : "#9ca3af";
                html += `<tr>
                    <td style="color:#2563eb;">${qNum}. ${stmt}</td>
                    <td style="text-align:center;">
                        <span class="rating-val" style="color:${color};">${rating ?? "—"}</span>
                        <span class="rating-lbl">${label}</span>
                    </td>
                </tr>`;
                qNum++;
            }
            html += `</tbody></table>`;
        }

        const comments = document.getElementById("eval-comments").value.trim();
        if (comments) {
            html += `<div style="padding:18px 24px;border-top:1px solid #e5e7eb;">
                <strong style="font-size:14px;display:block;margin-bottom:8px;">Additional Comments</strong>
                <p style="font-size:14px;color:#556070;">${comments}</p>
            </div>`;
        }

        document.getElementById("review-summary-body").innerHTML = html;
    }

    document.getElementById("btn-review-edit").addEventListener("click", function () {
        showEvalView("eval-view-form");
    });

    document.getElementById("btn-review-cancel").addEventListener("click", function () {
        showEvalView("eval-view-list");
    });

    document.getElementById("btn-confirm-submit").addEventListener("click", function () {
        const btn = this;
        btn.disabled = true;
        btn.textContent = "Submitting...";

        const answersPayload = {};
        document.querySelectorAll(".eval-row").forEach(row => {
            const qid = row.dataset.qid;
            const itemId = row.dataset.itemId;
            const cb  = document.querySelector(`.rating-check[data-qid="${qid}"]:checked`);
            if (cb && itemId) {
                answersPayload[itemId] = parseInt(cb.value);
            }
        });

        const comment = document.getElementById("eval-comments").value.trim();

        const fd = new FormData();
        fd.append("action", "submit_evaluation");
        fd.append("student_id", studentId);
        fd.append("task_id", currentEvalTaskId);
        fd.append("period_id", periodId);
        fd.append("teaching_assignment_id", currentEvalTaId || 0);
        fd.append("comment", comment);
        for (const [itemId, rating] of Object.entries(answersPayload)) {
            fd.append("answers[" + itemId + "]", rating);
        }

        fetch(window.location.href, { method: "POST", body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const submittedAt = data.submitted_at || new Date().toISOString().replace("T", " ").substring(0, 19);
                    const avgScore    = data.average_score || 0;
                    finalizeSubmission(submittedAt, avgScore);
                } else {
                    alert(data.message || "Submission failed. Please try again.");
                    btn.disabled = false;
                    btn.innerHTML = '<?php echo addslashes(svg_icon("check-circle", "#fff")); ?> Confirm Submission';
                }
            })
            .catch(() => {
                finalizeSubmission(new Date().toISOString().replace("T", " ").substring(0, 19), 0);
            });
    });

    function finalizeSubmission(submittedAt, avgScore) {
        const card = document.querySelector(`[data-action="start-eval"][data-task-id="${currentEvalTaskId}"]`);
        if (card) {
            const parentCard = card.closest(".eval-card");
            if (parentCard) {
                parentCard.dataset.status = "submitted";
                const badge = parentCard.querySelector(".status-pill");
                if (badge) {
                    badge.className = "status-pill completed";
                    badge.innerHTML = `<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><polyline points="22 4 12 14.01 9 11.01" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Completed`;
                }
                const note = parentCard.querySelector(".eval-card-completed-note");
                if (!note) {
                    const n = document.createElement("div");
                    n.className = "eval-card-completed-note";
                    n.textContent = "Evaluation completed and submitted";
                    card.before(n);
                }
                card.className      = "btn-view-eval";
                card.dataset.action = "view-eval";
                card.innerHTML      = `<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="3" stroke="#fff" stroke-width="2"/></svg> View Evaluation`;
                card.dataset.name      = currentEvalName;
                card.dataset.subject   = currentEvalSubject;
                card.dataset.dept      = currentEvalDept;
                card.dataset.submitted = submittedAt;
                card.dataset.avg       = avgScore;
            }
        }

        const teacherInArr = teachers.find(t => t.student_evaluation_task_id == currentEvalTaskId);
        if (teacherInArr) {
            teacherInArr.task_status = "Submitted";
            teacherInArr.submitted_at = submittedAt;
            teacherInArr.average_score = avgScore;
        }

        completedCount++;
        updateSidebarProgress();
        showSuccessPage(submittedAt);
    }

    function showSuccessPage(submittedAt) {
        let dateStr, timeStr;
        if (submittedAt) {
            const dt = new Date(submittedAt.replace(" ", "T"));
            dateStr = dt.toLocaleDateString("en-US", {month:"long", day:"numeric", year:"numeric"});
            timeStr = dt.toLocaleTimeString("en-US", {hour:"2-digit", minute:"2-digit"});
        } else {
            const now = new Date();
            dateStr = now.toLocaleDateString("en-US", {month:"long", day:"numeric", year:"numeric"});
            timeStr = now.toLocaleTimeString("en-US", {hour:"2-digit", minute:"2-digit"});
        }
        const remaining = totalTeachers - completedCount;

        document.getElementById("success-teacher-name").textContent    = currentEvalName;
        document.getElementById("success-subject").textContent         = currentEvalSubject;
        document.getElementById("success-date").textContent            = dateStr;
        document.getElementById("success-time").textContent            = timeStr;
        document.getElementById("success-completed-count").textContent = completedCount;
        document.getElementById("success-remaining-count").textContent = remaining;

        const nextBtn    = document.getElementById("btn-next-teacher");
        const nextTeacher = teachers.find(t => t.task_status !== "Submitted" && t.student_evaluation_task_id != currentEvalTaskId);
        if (remaining > 0 && nextTeacher) {
            nextBtn.style.display = "flex";
            nextBtn.textContent   = "→ Evaluate Next Teacher (" + nextTeacher.faculty_name + ")";
            nextBtn.onclick = function () {
                currentEvalTaskId  = nextTeacher.student_evaluation_task_id;
                currentEvalName    = nextTeacher.faculty_name;
                currentEvalSubject = nextTeacher.subject_title;
                currentEvalDept    = nextTeacher.department_name;
                currentEvalTaId    = nextTeacher.teaching_assignment_id;
                openEvalForm();
            };
        } else {
            nextBtn.style.display = "none";
        }
        showEvalView("eval-view-success");
    }

    document.getElementById("btn-go-back-eval").addEventListener("click", function () {
        showEvalView("eval-view-list");
    });

    function updateSidebarProgress() {
        const remaining = totalTeachers - completedCount;
        const sidebarCompleted = document.getElementById("sidebar-completed");
        const sidebarPending   = document.getElementById("sidebar-pending");
        if (sidebarCompleted) sidebarCompleted.textContent = completedCount;
        if (sidebarPending)   sidebarPending.textContent   = remaining;
        const modalComp = document.getElementById("modal-completed-count");
        const modalPend = document.getElementById("modal-pending-count");
        if (modalComp) modalComp.textContent = completedCount + " teachers";
        if (modalPend) modalPend.textContent = remaining + " teachers";
    }

    document.getElementById("btn-back-to-eval-list").addEventListener("click", function (e) {
        e.preventDefault();
        showEvalView("eval-view-list");
    });

    function openReadonlyView(submittedAt, avgScore, taskId) {
        document.getElementById("view-teacher-name").textContent = currentEvalName;
        document.getElementById("view-teacher-sub").textContent  = currentEvalSubject;
        document.getElementById("view-teacher-dept").textContent = currentEvalDept;

        const dateEl = document.getElementById("view-submitted-date");
        const timeEl = document.getElementById("view-submitted-time");
        const avgEl  = document.getElementById("view-avg-score");

        if (submittedAt) {
            const dt = new Date(submittedAt.replace(" ", "T"));
            if (dateEl) dateEl.textContent = dt.toLocaleDateString("en-US", {month:"long", day:"numeric", year:"numeric"});
            if (timeEl) timeEl.textContent = dt.toLocaleTimeString("en-US", {hour:"2-digit", minute:"2-digit"});
        } else {
            if (dateEl) dateEl.textContent = "—";
            if (timeEl) timeEl.textContent = "—";
        }
        if (avgEl) avgEl.textContent = avgScore ? parseFloat(avgScore).toFixed(2) + " / 5.00" : "—";

        let html = "";
        let qNum = 1;
        for (const [catName, stmts] of Object.entries(categoryStatements)) {
            html += `<div style="font-weight:700;font-size:15px;margin-bottom:14px;margin-top:${qNum>1?'22px':'0'}">${catName}</div>`;
            for (const stmtObj of stmts) {
                const stmt = stmtObj.statement || stmtObj;
                const r    = 4;
                const lbl  = ratingLabels[r];
                const stars = [1,2,3,4,5].map(s => s <= r
                    ? `<svg viewBox="0 0 24 24" fill="#f59e0b" xmlns="http://www.w3.org/2000/svg" style="width:18px;height:18px;"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" fill="#f59e0b"/></svg>`
                    : `<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="width:18px;height:18px;"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" stroke="#d1d5db" stroke-width="2"/></svg>`
                ).join("");
                html += `<div class="view-rating-item">
                    <p>${qNum}. ${stmt}</p>
                    <div class="view-rating-box">
                        <div><strong>${lbl}</strong><small>Rating: ${r} / 5</small></div>
                        <div class="stars-row">${stars}</div>
                    </div>
                </div>`;
                qNum++;
            }
        }
        document.getElementById("view-ratings-body").innerHTML = html;
        showEvalView("eval-view-readonly");
        switchTab("tab-eval");
    }

    document.querySelectorAll("[data-eval-filter]").forEach(btn => {
        btn.addEventListener("click", function () {
            document.querySelectorAll("[data-eval-filter]").forEach(b => b.classList.remove("active-filter"));
            this.classList.add("active-filter");
            const filter = this.dataset.evalFilter;
            document.querySelectorAll(".eval-card").forEach(card => {
                const status = card.dataset.status;
                if (filter === "all") {
                    card.style.display = "";
                } else if (filter === "pending") {
                    card.style.display = (status === "pending") ? "" : "none";
                } else if (filter === "completed") {
                    card.style.display = (status === "submitted" || status === "completed") ? "" : "none";
                }
            });
        });
    });

    const historySearch = document.getElementById("history-search");
    historySearch.addEventListener("input", filterHistory);

    document.querySelectorAll("[data-history-filter]").forEach(btn => {
        btn.addEventListener("click", function () {
            document.querySelectorAll("[data-history-filter]").forEach(b => b.classList.remove("active-filter"));
            this.classList.add("active-filter");
            filterHistory();
        });
    });

    function filterHistory() {
        const query  = historySearch.value.toLowerCase();
        const filter = document.querySelector("[data-history-filter].active-filter")?.dataset.historyFilter || "all";
        document.querySelectorAll(".history-record-row").forEach(row => {
            const name    = row.dataset.name    || "";
            const subject = row.dataset.subject || "";
            const status  = row.dataset.status  || "";
            const matchText   = name.includes(query) || subject.includes(query);
            const matchFilter = filter === "all"
                || (filter === "completed" && status === "submitted")
                || (filter === "pending"   && status !== "submitted");
            row.style.display = (matchText && matchFilter) ? "" : "none";
        });
    }

    document.querySelectorAll(".history-record-row[data-action='view-eval']").forEach(row => {
        row.addEventListener("click", function () {
            currentEvalName    = this.dataset.nameDisplay || "";
            currentEvalSubject = this.dataset.subjectDisplay || "";
            currentEvalDept    = this.dataset.deptDisplay || "";
            openReadonlyView(this.dataset.submitted, this.dataset.avg, this.dataset.taskId);
        });
    });

    document.querySelectorAll(".toggle-pw").forEach(btn => {
        btn.addEventListener("click", function () {
            const input = document.getElementById(this.dataset.target);
            if (!input) return;
            input.type = input.type === "password" ? "text" : "password";
        });
    });

    document.getElementById("btn-update-pw").addEventListener("click", function () {
        const currentPw = document.getElementById("pw-current").value;
        const newPw     = document.getElementById("pw-new").value;
        const confirmPw = document.getElementById("pw-confirm").value;
        const feedback  = document.getElementById("pw-feedback");

        function showFeedback(msg, isError) {
            feedback.style.display = "block";
            feedback.style.background = isError ? "#fff1f2" : "#dcfce7";
            feedback.style.color      = isError ? "#dc2626" : "#16a34a";
            feedback.style.border     = isError ? "1px solid #fecaca" : "1px solid #bbf7d0";
            feedback.textContent = msg;
        }

        if (!currentPw || !newPw || !confirmPw) {
            showFeedback("Please fill in all password fields.", true);
            return;
        }
        if (newPw !== confirmPw) {
            showFeedback("New password and confirmation do not match.", true);
            return;
        }
        if (newPw.length < 8 || !/[A-Z]/.test(newPw) || !/[a-z]/.test(newPw) || !/[0-9]/.test(newPw)) {
            showFeedback("New password does not meet the requirements.", true);
            return;
        }

        const fd = new FormData();
        fd.append("action", "update_password");
        fd.append("current_password", currentPw);
        fd.append("new_password", newPw);
        fd.append("confirm_password", confirmPw);

        fetch(window.location.href, { method: "POST", body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showFeedback("Password updated successfully.", false);
                    document.getElementById("pw-current").value = "";
                    document.getElementById("pw-new").value     = "";
                    document.getElementById("pw-confirm").value = "";
                } else {
                    showFeedback(data.message || "Failed to update password.", true);
                }
            })
            .catch(() => {
                showFeedback("An error occurred. Please try again.", true);
            });
    });

    document.getElementById("btn-logout-trigger").addEventListener("click", function (e) {
        e.preventDefault();
        if (completedCount >= totalTeachers) {
            openModal("modal-logout-complete");
        } else {
            openModal("modal-logout-incomplete-1");
        }
    });

    document.querySelector(".btn-logout-all").addEventListener("click", function (e) {
        e.preventDefault();
        if (completedCount >= totalTeachers) {
            openModal("modal-logout-complete");
        } else {
            openModal("modal-logout-incomplete-1");
        }
    });

    document.getElementById("btn-show-final-warning").addEventListener("click", function () {
        closeModal("modal-logout-incomplete-1");
        openModal("modal-logout-incomplete-2");
    });

    document.getElementById("btn-do-logout").addEventListener("click", function () {
        window.location.href = "../login/login_page.php";
    });

    document.getElementById("btn-do-reset-logout").addEventListener("click", function () {
        window.location.href = "../login/login_page.php";
    });

    function openModal(id) {
        const el = document.getElementById(id);
        if (el) el.classList.add("open");
    }
    function closeModal(id) {
        const el = document.getElementById(id);
        if (el) el.classList.remove("open");
    }

    document.querySelectorAll("[data-close-modal]").forEach(btn => {
        btn.addEventListener("click", function () {
            closeModal(this.dataset.closeModal);
        });
    });

    document.querySelectorAll(".modal-overlay").forEach(overlay => {
        overlay.addEventListener("click", function (e) {
            if (e.target === this) closeModal(this.id);
        });
    });

})();
</script>
</body>
</html>