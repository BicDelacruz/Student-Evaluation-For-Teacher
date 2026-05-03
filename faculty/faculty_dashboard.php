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
    strtolower((string) $_SESSION["authenticated_role"]) !== "faculty"
) {
    header("Location: ../login/login_page.php");
    exit;
}

$user_id = (int) $_SESSION["authenticated_user_id"];

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
}

if (isset($_GET["action"]) && in_array($_GET["action"], ["report_download", "report_preview"], true)) {
    $rpt_user_id = $user_id;
    $rpt_filter_ta = isset($_GET["ta_id"]) ? (int) $_GET["ta_id"] : 0;
    $rpt_fac_stmt = $pdo->prepare("
        SELECT f.faculty_id, f.faculty_number, f.full_name, d.department_name, d.department_code
        FROM faculty f
        INNER JOIN department d ON d.department_id = f.department_id
        WHERE f.user_id = :user_id LIMIT 1
    ");
    $rpt_fac_stmt->execute(["user_id" => $rpt_user_id]);
    $rpt_faculty = $rpt_fac_stmt->fetch();
    if (!$rpt_faculty) { echo "Faculty not found."; exit; }
    $rpt_faculty_id = (int) $rpt_faculty["faculty_id"];
    $rpt_period_stmt = $pdo->query("
        SELECT ep.evaluation_period_id, t.term_name, ay.academic_year_name
        FROM evaluation_period ep
        INNER JOIN term t ON t.term_id = ep.term_id
        INNER JOIN academic_year ay ON ay.academic_year_id = t.academic_year_id
        ORDER BY FIELD(ep.period_status,'Ongoing','Open','Draft','Closed','Archived'), ep.start_date DESC
        LIMIT 1
    ");
    $rpt_period = $rpt_period_stmt->fetch();
    $rpt_period_id = $rpt_period ? (int) $rpt_period["evaluation_period_id"] : 0;
    $rpt_school_stmt = $pdo->query("SELECT school_name FROM school_profile LIMIT 1");
    $rpt_school = $rpt_school_stmt->fetch();
    $rpt_school_name = $rpt_school ? $rpt_school["school_name"] : "";
    $rpt_results_stmt = $pdo->prepare("
        SELECT fer.faculty_evaluation_result_id, fer.teaching_assignment_id,
               fer.eligible_student_count, fer.submitted_response_count, fer.pending_response_count,
               fer.participation_rate, fer.overall_average_score, fer.result_status, fer.released_at,
               sub.subject_code, sub.subject_title, sec.section_name, c.course_code, t.term_name
        FROM faculty_evaluation_result fer
        INNER JOIN teaching_assignment ta ON ta.teaching_assignment_id = fer.teaching_assignment_id
        INNER JOIN section_subject_offering sso ON sso.section_subject_offering_id = ta.section_subject_offering_id
        INNER JOIN subject sub ON sub.subject_id = sso.subject_id
        INNER JOIN section sec ON sec.section_id = sso.section_id
        INNER JOIN course c ON c.course_id = sec.course_id
        INNER JOIN term t ON t.term_id = ta.term_id
        WHERE ta.faculty_id = :faculty_id AND fer.evaluation_period_id = :period_id
        " . ($rpt_filter_ta ? "AND fer.teaching_assignment_id = :ta_id" : "") . "
        ORDER BY sub.subject_code, sec.section_name
    ");
    $rpt_bind = ["faculty_id" => $rpt_faculty_id, "period_id" => $rpt_period_id];
    if ($rpt_filter_ta) $rpt_bind["ta_id"] = $rpt_filter_ta;
    $rpt_results_stmt->execute($rpt_bind);
    $rpt_results = $rpt_results_stmt->fetchAll();
    $rpt_result_ids = array_column($rpt_results, "faculty_evaluation_result_id");
    $rpt_cat_scores = [];
    $rpt_item_scores = [];
    $rpt_rating_dist = [];
    $rpt_comments_map = [];
    if (!empty($rpt_result_ids)) {
        $in_ph = implode(",", array_fill(0, count($rpt_result_ids), "?"));
        $s = $pdo->prepare("
            SELECT frcs.faculty_evaluation_result_id, ec.category_name, frcs.average_score, frcs.percentage_score, frcs.rating_description
            FROM faculty_result_category_score frcs
            INNER JOIN evaluation_form_category efc ON efc.evaluation_form_category_id = frcs.evaluation_form_category_id
            INNER JOIN evaluation_category ec ON ec.evaluation_category_id = efc.evaluation_category_id
            WHERE frcs.faculty_evaluation_result_id IN ($in_ph)
            ORDER BY frcs.faculty_evaluation_result_id, efc.display_order
        ");
        $s->execute($rpt_result_ids);
        foreach ($s->fetchAll() as $row) { $rpt_cat_scores[$row["faculty_evaluation_result_id"]][] = $row; }
        $s = $pdo->prepare("
            SELECT fris.faculty_evaluation_result_id, efi.statement_text_snapshot, fris.average_score, fris.response_count
            FROM faculty_result_item_score fris
            INNER JOIN evaluation_form_item efi ON efi.evaluation_form_item_id = fris.evaluation_form_item_id
            WHERE fris.faculty_evaluation_result_id IN ($in_ph)
            ORDER BY fris.faculty_evaluation_result_id, efi.display_order
        ");
        $s->execute($rpt_result_ids);
        foreach ($s->fetchAll() as $row) { $rpt_item_scores[$row["faculty_evaluation_result_id"]][] = $row; }
        $s = $pdo->prepare("
            SELECT frrd.faculty_evaluation_result_id, rso.rating_value, rso.rating_label, frrd.rating_count, frrd.rating_percentage
            FROM faculty_result_rating_distribution frrd
            INNER JOIN rating_scale_option rso ON rso.rating_scale_option_id = frrd.rating_scale_option_id
            WHERE frrd.faculty_evaluation_result_id IN ($in_ph)
            ORDER BY frrd.faculty_evaluation_result_id, rso.rating_value DESC
        ");
        $s->execute($rpt_result_ids);
        foreach ($s->fetchAll() as $row) { $rpt_rating_dist[$row["faculty_evaluation_result_id"]][] = $row; }
        $ta_in = implode(",", array_map('intval', array_column($rpt_results, "teaching_assignment_id")));
        if ($ta_in) {
            $s = $pdo->prepare("
                SELECT er.teaching_assignment_id, erc.comment_text, erc.submitted_at
                FROM evaluation_response_comment erc
                INNER JOIN evaluation_response er ON er.evaluation_response_id = erc.evaluation_response_id
                WHERE er.teaching_assignment_id IN ($ta_in)
                AND er.evaluation_period_id = ?
                AND erc.is_visible_to_faculty = 1
                AND erc.moderation_status = 'Approved'
                AND er.response_status = 'Submitted'
                ORDER BY er.teaching_assignment_id, erc.submitted_at ASC
            ");
            $s->execute([$rpt_period_id]);
            foreach ($s->fetchAll() as $row) { $rpt_comments_map[$row["teaching_assignment_id"]][] = $row; }
        }
    }
    function rpt_score_color(float $s): string {
        if ($s >= 4.5) return "#16a34a";
        if ($s >= 4.0) return "#2563eb";
        if ($s >= 3.5) return "#b45309";
        if ($s >= 3.0) return "#ea580c";
        return "#dc2626";
    }
    function rpt_score_label(float $s): string {
        if ($s >= 4.5) return "Excellent";
        if ($s >= 4.0) return "Very Good";
        if ($s >= 3.5) return "Good";
        if ($s >= 3.0) return "Fair";
        return "Needs Improvement";
    }
    $rpt_total_submitted = (int) array_sum(array_column($rpt_results, "submitted_response_count"));
    $rpt_total_eligible  = (int) array_sum(array_column($rpt_results, "eligible_student_count"));
    $rpt_avg_sum = 0.0; $rpt_avg_count = 0;
    foreach ($rpt_results as $r) {
        if ($r["overall_average_score"] !== null) { $rpt_avg_sum += (float)$r["overall_average_score"]; $rpt_avg_count++; }
    }
    $rpt_overall_avg_val = $rpt_avg_count > 0 ? $rpt_avg_sum / $rpt_avg_count : 0.0;
    $rpt_overall_avg_fmt = $rpt_avg_count > 0 ? number_format($rpt_overall_avg_val, 2) : "N/A";
    $rpt_overall_rate = $rpt_total_eligible > 0 ? number_format(($rpt_total_submitted / $rpt_total_eligible) * 100, 1) : "0.0";
    $rpt_date_generated = date("F j, Y g:i A");
    $is_download = $_GET["action"] === "report_download";
    if ($is_download) {
        $safe_name = preg_replace('/[^a-z0-9_-]/i', '_', $rpt_faculty["full_name"] ?? "faculty");
        header("Content-Type: text/html; charset=UTF-8");
        header("Content-Disposition: attachment; filename=\"Faculty_Evaluation_Report_{$safe_name}.html\"");
    } else {
        header("Content-Type: text/html; charset=UTF-8");
    }
    ?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Faculty Evaluation Report</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:Arial,Helvetica,sans-serif;font-size:11pt;color:#1a1a2e;background:#fff;padding:30px 40px;}
@media print{body{padding:10px 16px;}@page{margin:1.5cm;size:A4;}.no-print{display:none;}}
.rpt-btn{display:inline-block;padding:8px 20px;background:#172033;color:#fff;border:none;border-radius:5px;font-size:11pt;cursor:pointer;margin-bottom:24px;font-family:inherit;}
.rpt-header{text-align:center;border-bottom:3px double #172033;padding-bottom:18px;margin-bottom:20px;}
.rpt-school{font-size:16pt;font-weight:bold;color:#172033;margin-bottom:4px;}
.rpt-title{font-size:14pt;font-weight:bold;letter-spacing:1px;margin-bottom:8px;}
.rpt-meta{font-size:10pt;color:#444;line-height:1.8;}
.rpt-section{margin-bottom:24px;}
.rpt-section-title{font-size:12pt;font-weight:bold;background:#172033;color:#fff;padding:6px 12px;margin-bottom:10px;letter-spacing:.5px;}
.rpt-table{width:100%;border-collapse:collapse;font-size:10pt;margin-bottom:10px;}
.rpt-table th{background:#f0f0f4;border:1px solid #b0b8c8;padding:6px 10px;text-align:left;font-weight:bold;}
.rpt-table td{border:1px solid #d0d8e4;padding:5px 10px;vertical-align:top;}
.rpt-table tr:nth-child(even) td{background:#f9f9fc;}
.rpt-subject-block{border:1px solid #c8d0de;border-radius:4px;margin-bottom:22px;overflow:hidden;}
.rpt-subject-header{background:#172033;color:#fff;padding:10px 16px;font-size:12pt;font-weight:bold;}
.rpt-subject-sub{font-size:10pt;font-weight:normal;color:#c8d0e8;}
.rpt-subject-body{padding:14px 16px;}
.rpt-stats-row{display:flex;gap:24px;flex-wrap:wrap;margin-bottom:14px;}
.rpt-stat{text-align:center;min-width:80px;}
.rpt-stat-val{font-size:18pt;font-weight:bold;}
.rpt-stat-lbl{font-size:8.5pt;color:#555;margin-top:2px;}
.rpt-subsection{margin-top:14px;}
.rpt-subsection-title{font-size:10.5pt;font-weight:bold;border-bottom:1px solid #c8d0de;padding-bottom:4px;margin-bottom:8px;color:#172033;}
.rpt-cat-row{display:flex;align-items:center;gap:10px;margin-bottom:7px;font-size:10pt;}
.rpt-cat-name{flex:1;min-width:120px;}
.rpt-bar-bg{flex:2;height:10px;background:#e8eaf0;border-radius:99px;overflow:hidden;}
.rpt-bar-fill{height:100%;border-radius:99px;}
.rpt-cat-score{min-width:60px;text-align:right;font-weight:bold;}
.rpt-cat-pct{min-width:50px;text-align:right;color:#666;font-size:9.5pt;}
.rpt-comment-item{border-left:3px solid #172033;padding:7px 12px;margin-bottom:8px;background:#f5f6fa;}
.rpt-comment-anon{font-size:9pt;color:#666;margin-bottom:4px;}
.rpt-comment-text{font-size:10.5pt;line-height:1.6;}
.rpt-footer{text-align:center;border-top:1px solid #c8d0de;padding-top:14px;margin-top:28px;font-size:9pt;color:#777;}
.rpt-overall-box{border:2px solid #172033;border-radius:6px;padding:16px 24px;display:flex;justify-content:space-between;align-items:center;margin-top:8px;}
.rpt-score-big{font-size:28pt;font-weight:bold;}
.badge-released{display:inline-block;padding:2px 10px;border-radius:99px;font-size:8.5pt;font-weight:bold;background:#dcfce7;color:#166534;margin-left:8px;}
.badge-unreleased{display:inline-block;padding:2px 10px;border-radius:99px;font-size:8.5pt;font-weight:bold;background:#fee2e2;color:#991b1b;margin-left:8px;}
.rpt-no-data{color:#888;font-style:italic;font-size:10pt;padding:6px 0;}
</style></head><body>
<?php if (!$is_download): ?><button class="rpt-btn no-print" onclick="window.print()">&#128438; Print / Save as PDF</button><?php endif; ?>
<div class="rpt-header">
<?php if ($rpt_school_name): ?><div class="rpt-school"><?php echo e($rpt_school_name); ?></div><?php endif; ?>
<div class="rpt-title">FACULTY EVALUATION REPORT</div>
<div class="rpt-meta">
<?php if ($rpt_period): ?><strong>Academic Year:</strong> <?php echo e($rpt_period["academic_year_name"]); ?> &nbsp;|&nbsp; <strong>Term:</strong> <?php echo e($rpt_period["term_name"]); ?><br><?php endif; ?>
<strong>Date Generated:</strong> <?php echo e($rpt_date_generated); ?>
</div></div>
<div class="rpt-section">
<div class="rpt-section-title">FACULTY INFORMATION</div>
<table class="rpt-table"><tbody>
<tr><th style="width:180px;">Faculty Name</th><td><?php echo e($rpt_faculty["full_name"]); ?></td></tr>
<tr><th>Faculty ID</th><td><?php echo e($rpt_faculty["faculty_number"]); ?></td></tr>
<tr><th>Department</th><td><?php echo e($rpt_faculty["department_name"]); ?> (<?php echo e($rpt_faculty["department_code"]); ?>)</td></tr>
</tbody></table></div>
<div class="rpt-section">
<div class="rpt-section-title">ASSIGNED SUBJECTS SUMMARY</div>
<?php if (empty($rpt_results)): ?><p class="rpt-no-data">No evaluation results found for this period.</p><?php else: ?>
<table class="rpt-table"><thead><tr><th>#</th><th>Subject Code</th><th>Subject Title</th><th>Section</th><th>Term</th><th>Status</th></tr></thead><tbody>
<?php foreach ($rpt_results as $i => $r):
$badge = $r["result_status"] === "Released" ? '<span class="badge-released">Released</span>' : '<span class="badge-unreleased">Unreleased</span>';
?><tr><td><?php echo $i+1; ?></td><td><strong><?php echo e($r["subject_code"]); ?></strong></td><td><?php echo e($r["subject_title"]); ?></td><td><?php echo e($r["section_name"]); ?></td><td><?php echo e($r["term_name"]); ?></td><td><?php echo $badge; ?></td></tr>
<?php endforeach; ?>
</tbody></table><?php endif; ?></div>
<?php foreach ($rpt_results as $r):
$fer_id = (int)$r["faculty_evaluation_result_id"];
$ta_id  = (int)$r["teaching_assignment_id"];
$avg = $r["overall_average_score"] !== null ? (float)$r["overall_average_score"] : null;
$rate = (float)$r["participation_rate"];
$badge = $r["result_status"] === "Released" ? '<span class="badge-released">Released</span>' : '<span class="badge-unreleased">Unreleased</span>';
?>
<div class="rpt-subject-block">
<div class="rpt-subject-header"><?php echo e($r["subject_code"]); ?> &ndash; <?php echo e($r["section_name"]); ?><?php echo $badge; ?> <span class="rpt-subject-sub"><?php echo e($r["subject_title"]); ?></span></div>
<div class="rpt-subject-body">
<div class="rpt-stats-row">
<div class="rpt-stat"><div class="rpt-stat-val"><?php echo (int)$r["eligible_student_count"]; ?></div><div class="rpt-stat-lbl">Total Students</div></div>
<div class="rpt-stat"><div class="rpt-stat-val" style="color:#16a34a;"><?php echo (int)$r["submitted_response_count"]; ?></div><div class="rpt-stat-lbl">Submitted</div></div>
<div class="rpt-stat"><div class="rpt-stat-val" style="color:#dc2626;"><?php echo (int)$r["pending_response_count"]; ?></div><div class="rpt-stat-lbl">Pending</div></div>
<div class="rpt-stat"><div class="rpt-stat-val" style="color:#2563eb;"><?php echo number_format($rate,1); ?>%</div><div class="rpt-stat-lbl">Participation</div></div>
<?php if ($avg !== null): ?><div class="rpt-stat"><div class="rpt-stat-val" style="color:<?php echo rpt_score_color($avg); ?>;"><?php echo number_format($avg,2); ?></div><div class="rpt-stat-lbl">Avg Score / 5.00<br><em><?php echo rpt_score_label($avg); ?></em></div></div><?php endif; ?>
</div>
<?php $cats = $rpt_cat_scores[$fer_id] ?? []; if (!empty($cats)): ?>
<div class="rpt-subsection"><div class="rpt-subsection-title">Category Scores</div>
<?php foreach ($cats as $cat):
$cs = (float)$cat["average_score"]; $pct = (float)$cat["percentage_score"]; $clr = rpt_score_color($cs);
$desc = !empty($cat["rating_description"]) ? ' <em style="font-size:9pt;color:#666;">(' . e($cat["rating_description"]) . ')</em>' : '';
?><div class="rpt-cat-row">
<span class="rpt-cat-name"><?php echo e($cat["category_name"]); ?><?php echo $desc; ?></span>
<div class="rpt-bar-bg"><div class="rpt-bar-fill" style="width:<?php echo min($pct,100); ?>%;background:<?php echo $clr; ?>;"></div></div>
<span class="rpt-cat-score" style="color:<?php echo $clr; ?>;"><?php echo number_format($cs,2); ?></span>
<span class="rpt-cat-pct"><?php echo number_format($pct,1); ?>%</span>
</div><?php endforeach; ?></div><?php endif; ?>
<?php $items = $rpt_item_scores[$fer_id] ?? []; if (!empty($items)): ?>
<div class="rpt-subsection"><div class="rpt-subsection-title">Item / Question Level Scores</div>
<table class="rpt-table"><thead><tr><th>#</th><th>Statement / Question</th><th style="text-align:center;">Avg Score</th><th style="text-align:center;">Responses</th></tr></thead><tbody>
<?php foreach ($items as $idx => $item):
$is = $item["average_score"] !== null ? number_format((float)$item["average_score"],2) : "N/A";
?><tr><td><?php echo $idx+1; ?></td><td><?php echo e($item["statement_text_snapshot"]); ?></td><td style="text-align:center;font-weight:bold;"><?php echo $is; ?></td><td style="text-align:center;"><?php echo (int)$item["response_count"]; ?></td></tr>
<?php endforeach; ?>
</tbody></table></div><?php endif; ?>
<?php $dist = $rpt_rating_dist[$fer_id] ?? []; if (!empty($dist)): ?>
<div class="rpt-subsection"><div class="rpt-subsection-title">Rating Distribution</div>
<table class="rpt-table"><thead><tr><th>Rating Value</th><th>Label</th><th style="text-align:center;">Count</th><th style="text-align:center;">Percentage</th></tr></thead><tbody>
<?php foreach ($dist as $d): ?><tr>
<td style="text-align:center;font-weight:bold;"><?php echo (int)$d["rating_value"]; ?></td>
<td><?php echo e($d["rating_label"] ?? ""); ?></td>
<td style="text-align:center;"><?php echo (int)$d["rating_count"]; ?></td>
<td><div style="display:flex;align-items:center;gap:8px;"><div style="flex:1;background:#e8eaf0;height:8px;border-radius:99px;overflow:hidden;"><div style="width:<?php echo min((float)$d["rating_percentage"],100); ?>%;background:#172033;height:100%;border-radius:99px;"></div></div><span style="min-width:42px;text-align:right;"><?php echo number_format((float)$d["rating_percentage"],1); ?>%</span></div></td>
</tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?>
<?php $comments = $rpt_comments_map[$ta_id] ?? []; if (!empty($comments)): ?>
<div class="rpt-subsection"><div class="rpt-subsection-title">Student Comments (Anonymous)</div>
<?php foreach ($comments as $ci => $cmt):
$cdate = $cmt["submitted_at"] ? date("F j, Y", strtotime($cmt["submitted_at"])) : "";
?><div class="rpt-comment-item">
<div class="rpt-comment-anon">Anonymous Student #<?php echo $ci+1; ?> &mdash; Student Identity Protected<?php if ($cdate): ?> | <?php echo e($cdate); ?><?php endif; ?></div>
<div class="rpt-comment-text"><?php echo e($cmt["comment_text"]); ?></div>
</div><?php endforeach; ?>
</div><?php endif; ?>
</div></div>
<?php endforeach; ?>
<?php if (!empty($rpt_results)): ?>
<div class="rpt-section">
<div class="rpt-section-title">OVERALL SUMMARY</div>
<div class="rpt-overall-box">
<div>
<div style="font-size:10pt;color:#555;margin-bottom:4px;">Final Average Score (All Results)</div>
<div class="rpt-score-big" style="color:<?php echo $rpt_avg_count > 0 ? rpt_score_color($rpt_overall_avg_val) : '#172033'; ?>;"><?php echo $rpt_overall_avg_fmt; ?><span style="font-size:14pt;font-weight:normal;color:#888;"> / 5.00</span></div>
<?php if ($rpt_avg_count > 0): ?><div style="font-size:11pt;color:#555;margin-top:4px;"><?php echo rpt_score_label($rpt_overall_avg_val); ?></div><?php endif; ?>
</div>
<div style="text-align:right;">
<div style="font-size:10pt;color:#555;margin-bottom:4px;">Overall Participation Rate</div>
<div style="font-size:22pt;font-weight:bold;color:#2563eb;"><?php echo $rpt_overall_rate; ?>%</div>
<div style="font-size:10pt;color:#555;"><?php echo $rpt_total_submitted; ?> / <?php echo $rpt_total_eligible; ?> students</div>
</div>
</div></div>
<?php endif; ?>
<div class="rpt-footer">This report is system-generated and contains confidential evaluation data. All student information is anonymous.
Generated on <?php echo e($rpt_date_generated); ?><?php if ($rpt_school_name): ?> &bull; <?php echo e($rpt_school_name); ?><?php endif; ?>
</div>
</body></html>
<?php
    exit;
}

function svg_icon(string $name, string $color = "currentColor"): string
{
    $icons = [
        "dashboard"  => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="3" y="3" width="7" height="7" rx="1.5" stroke="'.$color.'" stroke-width="2"/><rect x="14" y="3" width="7" height="7" rx="1.5" stroke="'.$color.'" stroke-width="2"/><rect x="3" y="14" width="7" height="7" rx="1.5" stroke="'.$color.'" stroke-width="2"/><rect x="14" y="14" width="7" height="7" rx="1.5" stroke="'.$color.'" stroke-width="2"/></svg>',
        "book"       => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" stroke="'.$color.'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z" stroke="'.$color.'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        "chart"      => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 19V5" stroke="'.$color.'" stroke-width="2" stroke-linecap="round"/><path d="M4 19h16" stroke="'.$color.'" stroke-width="2" stroke-linecap="round"/><path d="M8 16v-5" stroke="'.$color.'" stroke-width="2" stroke-linecap="round"/><path d="M12 16V8" stroke="'.$color.'" stroke-width="2" stroke-linecap="round"/><path d="M16 16v-7" stroke="'.$color.'" stroke-width="2" stroke-linecap="round"/></svg>',
        "list"       => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8 6h13" stroke="'.$color.'" stroke-width="2" stroke-linecap="round"/><path d="M8 12h13" stroke="'.$color.'" stroke-width="2" stroke-linecap="round"/><path d="M8 18h13" stroke="'.$color.'" stroke-width="2" stroke-linecap="round"/><path d="M3 6h.01" stroke="'.$color.'" stroke-width="2" stroke-linecap="round"/><path d="M3 12h.01" stroke="'.$color.'" stroke-width="2" stroke-linecap="round"/><path d="M3 18h.01" stroke="'.$color.'" stroke-width="2" stroke-linecap="round"/></svg>',
        "comment"    => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z" stroke="'.$color.'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        "users"      => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="'.$color.'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="7" r="4" stroke="'.$color.'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M23 21v-2a4 4 0 0 0-3-3.9" stroke="'.$color.'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M16 3.1a4 4 0 0 1 0 7.8" stroke="'.$color.'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        "file"       => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M7 3h7l5 5v13H7z" stroke="'.$color.'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 3v5h5" stroke="'.$color.'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        "logout"     => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" stroke="'.$color.'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><polyline points="16 17 21 12 16 7" stroke="'.$color.'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><line x1="21" y1="12" x2="9" y2="12" stroke="'.$color.'" stroke-width="2" stroke-linecap="round"/></svg>',
        "bell"       => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 7-3 7h18s-3 0-3-7" stroke="'.$color.'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M13.7 21a2 2 0 0 1-3.4 0" stroke="'.$color.'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        "refresh"    => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21 12a9 9 0 0 1-15.49 6.29" stroke="'.$color.'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 12A9 9 0 0 1 18.49 5.71" stroke="'.$color.'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><polyline points="18 2 18 6 14 6" stroke="'.$color.'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><polyline points="6 22 6 18 10 18" stroke="'.$color.'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        "check"      => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20 6L9 17l-5-5" stroke="'.$color.'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        "check-circle" => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" stroke="'.$color.'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><polyline points="22 4 12 14.01 9 11.01" stroke="'.$color.'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        "trend"      => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 17l6-6 4 4 8-8" stroke="'.$color.'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 7h7v7" stroke="'.$color.'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        "search"     => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="11" cy="11" r="8" stroke="'.$color.'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><line x1="21" y1="21" x2="16.65" y2="16.65" stroke="'.$color.'" stroke-width="2" stroke-linecap="round"/></svg>',
        "arrow-left" => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><line x1="19" y1="12" x2="5" y2="12" stroke="'.$color.'" stroke-width="2" stroke-linecap="round"/><polyline points="12 19 5 12 12 5" stroke="'.$color.'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        "x-circle"   => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" stroke="'.$color.'" stroke-width="2"/><path d="M15 9l-6 6M9 9l6 6" stroke="'.$color.'" stroke-width="2" stroke-linecap="round"/></svg>',
        "award"      => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="8" r="6" stroke="'.$color.'" stroke-width="2"/><path d="M15.5 13.5l1.5 7.5-5-3-5 3 1.5-7.5" stroke="'.$color.'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        "align-left" => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 6h18M3 12h12M3 18h15" stroke="'.$color.'" stroke-width="2" stroke-linecap="round"/></svg>',
        "clock"      => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" stroke="'.$color.'" stroke-width="2"/><polyline points="12 6 12 12 16 14" stroke="'.$color.'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        "shield"     => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke="'.$color.'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        "info"       => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" stroke="'.$color.'" stroke-width="2"/><line x1="12" y1="16" x2="12" y2="12" stroke="'.$color.'" stroke-width="2" stroke-linecap="round"/><line x1="12" y1="8" x2="12.01" y2="8" stroke="'.$color.'" stroke-width="2" stroke-linecap="round"/></svg>',
        "download"   => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" stroke="'.$color.'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><polyline points="7 10 12 15 17 10" stroke="'.$color.'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="15" x2="12" y2="3" stroke="'.$color.'" stroke-width="2" stroke-linecap="round"/></svg>',
        "print"      => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><polyline points="6 9 6 2 18 2 18 9" stroke="'.$color.'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" stroke="'.$color.'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><rect x="6" y="14" width="12" height="8" stroke="'.$color.'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        "eye"        => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="'.$color.'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="3" stroke="'.$color.'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        "filter"     => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3" stroke="'.$color.'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        "calendar"   => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="3" y="4" width="18" height="18" rx="2" ry="2" stroke="'.$color.'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><line x1="16" y1="2" x2="16" y2="6" stroke="'.$color.'" stroke-width="2" stroke-linecap="round"/><line x1="8" y1="2" x2="8" y2="6" stroke="'.$color.'" stroke-width="2" stroke-linecap="round"/><line x1="3" y1="10" x2="21" y2="10" stroke="'.$color.'" stroke-width="2" stroke-linecap="round"/></svg>',
    ];
    return $icons[$name] ?? "";
}

$faculty = null;
$current_period = null;
$assigned_subjects = [];
$all_results = [];
$comments_data = [];
$participation_data = [];
$criteria_data = [];

try {
    $stmt = $pdo->prepare("
        SELECT f.faculty_id, f.faculty_number, f.full_name, d.department_name, d.department_code
        FROM faculty f
        INNER JOIN department d ON d.department_id = f.department_id
        WHERE f.user_id = :user_id
        LIMIT 1
    ");
    $stmt->execute(["user_id" => $user_id]);
    $faculty = $stmt->fetch();

    if (!$faculty) {
        throw new RuntimeException("Faculty profile not found.");
    }

    $faculty_id = (int) $faculty["faculty_id"];

    $stmt = $pdo->query("
        SELECT ep.evaluation_period_id, t.term_id, t.term_name, ay.academic_year_name
        FROM evaluation_period ep
        INNER JOIN term t ON t.term_id = ep.term_id
        INNER JOIN academic_year ay ON ay.academic_year_id = t.academic_year_id
        ORDER BY FIELD(ep.period_status, 'Ongoing', 'Open', 'Draft', 'Closed', 'Archived'), ep.start_date DESC
        LIMIT 1
    ");
    $current_period = $stmt->fetch();

    $period_id = $current_period ? (int) $current_period["evaluation_period_id"] : 0;

    $stmt = $pdo->prepare("
        SELECT
            ta.teaching_assignment_id,
            sub.subject_id,
            sub.subject_code,
            sub.subject_title,
            sec.section_id,
            sec.section_name,
            sec.year_level,
            c.course_code,
            t.term_name,
            t.term_id,
            COUNT(DISTINCT sse.student_id) AS student_count
        FROM teaching_assignment ta
        INNER JOIN section_subject_offering sso ON sso.section_subject_offering_id = ta.section_subject_offering_id
        INNER JOIN subject sub ON sub.subject_id = sso.subject_id
        INNER JOIN section sec ON sec.section_id = sso.section_id
        INNER JOIN course c ON c.course_id = sec.course_id
        INNER JOIN term t ON t.term_id = ta.term_id
        LEFT JOIN student_section_enrollment sse
            ON sse.section_id = sec.section_id
            AND sse.term_id = ta.term_id
            AND sse.enrollment_status = 'Active'
        WHERE ta.faculty_id = :faculty_id
        AND ta.assignment_status = 'Active'
        GROUP BY
            ta.teaching_assignment_id,
            sub.subject_id, sub.subject_code, sub.subject_title,
            sec.section_id, sec.section_name, sec.year_level,
            c.course_code, t.term_name, t.term_id
        ORDER BY sub.subject_code, sec.section_name
    ");
    $stmt->execute(["faculty_id" => $faculty_id]);
    $assigned_subjects = $stmt->fetchAll();

    if ($period_id) {
        $stmt = $pdo->prepare("
            SELECT
                fer.faculty_evaluation_result_id,
                fer.teaching_assignment_id,
                fer.overall_average_score,
                fer.submitted_response_count,
                fer.eligible_student_count,
                fer.pending_response_count,
                fer.participation_rate,
                fer.result_status,
                fer.released_at,
                sub.subject_code,
                sub.subject_title,
                sec.section_name,
                c.course_code
            FROM faculty_evaluation_result fer
            INNER JOIN teaching_assignment ta ON ta.teaching_assignment_id = fer.teaching_assignment_id
            INNER JOIN section_subject_offering sso ON sso.section_subject_offering_id = ta.section_subject_offering_id
            INNER JOIN subject sub ON sub.subject_id = sso.subject_id
            INNER JOIN section sec ON sec.section_id = sso.section_id
            INNER JOIN course c ON c.course_id = sec.course_id
            WHERE ta.faculty_id = :faculty_id
            AND fer.evaluation_period_id = :period_id
            ORDER BY sub.subject_code, sec.section_name
        ");
        $stmt->execute(["faculty_id" => $faculty_id, "period_id" => $period_id]);
        $all_results = $stmt->fetchAll();

        $stmt = $pdo->prepare("
            SELECT
                erc.evaluation_response_comment_id,
                erc.comment_text,
                erc.submitted_at,
                sub.subject_code,
                sub.subject_title,
                sec.section_name,
                er.average_score
            FROM evaluation_response_comment erc
            INNER JOIN evaluation_response er ON er.evaluation_response_id = erc.evaluation_response_id
            INNER JOIN teaching_assignment ta ON ta.teaching_assignment_id = er.teaching_assignment_id
            INNER JOIN section_subject_offering sso ON sso.section_subject_offering_id = ta.section_subject_offering_id
            INNER JOIN subject sub ON sub.subject_id = sso.subject_id
            INNER JOIN section sec ON sec.section_id = sso.section_id
            WHERE ta.faculty_id = :faculty_id
            AND er.evaluation_period_id = :period_id
            AND erc.is_visible_to_faculty = 1
            AND erc.moderation_status = 'Approved'
            AND er.response_status = 'Submitted'
            ORDER BY sub.subject_code, sec.section_name, erc.submitted_at ASC
        ");
        $stmt->execute(["faculty_id" => $faculty_id, "period_id" => $period_id]);
        $all_comments_raw = $stmt->fetchAll();

        foreach ($all_comments_raw as $c) {
            $key = $c["subject_code"] . "||" . $c["section_name"];
            if (!isset($comments_data[$key])) {
                $comments_data[$key] = [
                    "subject_code"  => $c["subject_code"],
                    "subject_title" => $c["subject_title"],
                    "section_name"  => $c["section_name"],
                    "average_score" => null,
                    "comments"      => []
                ];
            }
            if ($c["average_score"] !== null) {
                $comments_data[$key]["average_score"] = $c["average_score"];
            }
            $comments_data[$key]["comments"][] = [
                "comment_text" => $c["comment_text"],
                "submitted_at" => $c["submitted_at"]
            ];
        }

        $stmt = $pdo->prepare("
            SELECT
                fer.teaching_assignment_id,
                fer.submitted_response_count,
                fer.eligible_student_count,
                fer.pending_response_count,
                fer.participation_rate,
                sub.subject_code,
                sub.subject_title,
                sec.section_name
            FROM faculty_evaluation_result fer
            INNER JOIN teaching_assignment ta ON ta.teaching_assignment_id = fer.teaching_assignment_id
            INNER JOIN section_subject_offering sso ON sso.section_subject_offering_id = ta.section_subject_offering_id
            INNER JOIN subject sub ON sub.subject_id = sso.subject_id
            INNER JOIN section sec ON sec.section_id = sso.section_id
            WHERE ta.faculty_id = :faculty_id
            AND fer.evaluation_period_id = :period_id
            ORDER BY sub.subject_code, sec.section_name
        ");
        $stmt->execute(["faculty_id" => $faculty_id, "period_id" => $period_id]);
        $participation_data = $stmt->fetchAll();

        $stmt = $pdo->prepare("
            SELECT
                frcs.faculty_evaluation_result_id,
                frcs.average_score AS category_avg,
                frcs.percentage_score,
                ec.category_name,
                fer.teaching_assignment_id,
                sub.subject_code,
                sub.subject_title,
                sec.section_name,
                fer.overall_average_score
            FROM faculty_result_category_score frcs
            INNER JOIN evaluation_form_category efc ON efc.evaluation_form_category_id = frcs.evaluation_form_category_id
            INNER JOIN evaluation_category ec ON ec.evaluation_category_id = efc.evaluation_category_id
            INNER JOIN faculty_evaluation_result fer ON fer.faculty_evaluation_result_id = frcs.faculty_evaluation_result_id
            INNER JOIN teaching_assignment ta ON ta.teaching_assignment_id = fer.teaching_assignment_id
            INNER JOIN section_subject_offering sso ON sso.section_subject_offering_id = ta.section_subject_offering_id
            INNER JOIN subject sub ON sub.subject_id = sso.subject_id
            INNER JOIN section sec ON sec.section_id = sso.section_id
            WHERE ta.faculty_id = :faculty_id
            AND fer.evaluation_period_id = :period_id
            AND fer.result_status = 'Released'
            ORDER BY sub.subject_code, sec.section_name, efc.display_order ASC
        ");
        $stmt->execute(["faculty_id" => $faculty_id, "period_id" => $period_id]);
        $criteria_raw = $stmt->fetchAll();

        foreach ($criteria_raw as $row) {
            $key = $row["subject_code"] . "||" . $row["section_name"];
            if (!isset($criteria_data[$key])) {
                $criteria_data[$key] = [
                    "subject_code"        => $row["subject_code"],
                    "subject_title"       => $row["subject_title"],
                    "section_name"        => $row["section_name"],
                    "overall_avg"         => $row["overall_average_score"],
                    "faculty_result_id"   => $row["faculty_evaluation_result_id"],
                    "categories"          => []
                ];
            }
            $criteria_data[$key]["categories"][] = [
                "category_name"   => $row["category_name"],
                "average_score"   => $row["category_avg"],
                "percentage_score"=> $row["percentage_score"]
            ];
        }
    }
} catch (Throwable $error) {
    $assigned_subjects = [];
    $all_results = [];
    $comments_data = [];
    $participation_data = [];
    $criteria_data = [];
}

$assigned_count = count($assigned_subjects);
$released_count = 0;
$unreleased_count = 0;
$average_sum = 0.0;
$average_count = 0;
$participation_sum = 0.0;
$participation_count = 0;

foreach ($all_results as $r) {
    if ($r["result_status"] === "Released") {
        $released_count++;
        if ($r["overall_average_score"] !== null) {
            $average_sum += (float) $r["overall_average_score"];
            $average_count++;
        }
        if ((int) $r["eligible_student_count"] > 0) {
            $participation_sum += (float) $r["participation_rate"];
            $participation_count++;
        }
    } else {
        $unreleased_count++;
    }
}

$overall_average   = $average_count > 0 ? number_format($average_sum / $average_count, 2) : "0.00";
$participation_rate = $participation_count > 0 ? number_format($participation_sum / $participation_count, 1) : "0.0";

$term_label = $current_period
    ? $current_period["term_name"] . " · " . $current_period["academic_year_name"]
    : "No active term";

$total_subjects_distinct = count(array_unique(array_column($assigned_subjects, "subject_code")));
$total_sections = count($assigned_subjects);
$total_students = array_sum(array_column($assigned_subjects, "student_count"));

$subjects_by_code = [];
foreach ($assigned_subjects as $s) {
    $code = $s["subject_code"];
    if (!isset($subjects_by_code[$code])) {
        $subjects_by_code[$code] = [
            "subject_code"  => $code,
            "subject_title" => $s["subject_title"],
            "sections"      => []
        ];
    }
    $subjects_by_code[$code]["sections"][] = $s;
}

$total_comments = 0;
foreach ($comments_data as $cd) {
    $total_comments += count($cd["comments"]);
}

$total_participation_submitted = array_sum(array_column($participation_data, "submitted_response_count"));
$total_participation_students  = array_sum(array_column($participation_data, "eligible_student_count"));
$total_participation_pending   = array_sum(array_column($participation_data, "pending_response_count"));
$overall_participation_rate    = $total_participation_students > 0
    ? number_format(($total_participation_submitted / $total_participation_students) * 100, 1)
    : "0.0";

function score_color(float $score): string
{
    if ($score >= 4.5) return "#16a34a";
    if ($score >= 4.0) return "#2563eb";
    if ($score >= 3.5) return "#f59e0b";
    if ($score >= 3.0) return "#ea580c";
    return "#dc2626";
}

function score_label(float $score): string
{
    if ($score >= 4.5) return "Excellent";
    if ($score >= 4.0) return "Very Good";
    if ($score >= 3.5) return "Good";
    if ($score >= 3.0) return "Fair";
    return "Needs Improvement";
}

function score_bg(float $score): string
{
    if ($score >= 4.5) return "#dcfce7";
    if ($score >= 4.0) return "#dbeafe";
    if ($score >= 3.5) return "#fef9c3";
    if ($score >= 3.0) return "#ffedd5";
    return "#fee2e2";
}

function participation_color(float $rate): string
{
    if ($rate >= 90) return "#16a34a";
    if ($rate >= 75) return "#2563eb";
    if ($rate >= 60) return "#f59e0b";
    return "#dc2626";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard – Student Evaluation for Teacher</title>
    <link rel="stylesheet" href="faculty-dashboard.css">
    <style>
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        .content-wrap {
            max-width: 960px;
            margin: 0 auto;
            padding: 28px 32px;
        }

        .section-badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            background: #172033;
            color: #fff;
            margin-left: 6px;
        }
        .badge-course {
            background: #dbeafe;
            color: #1d4ed8;
        }
        .badge-year {
            background: #f3e8ff;
            color: #7c3aed;
        }

        .result-row {
            padding: 18px 22px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            transition: background 0.15s;
        }
        .result-row:last-child { border-bottom: none; }
        .result-row:hover { background: #f9fafb; }
        .result-row h3 { font-size: 15px; margin-bottom: 5px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .result-row p  { font-size: 13px; color: #556070; margin-bottom: 3px; }
        .result-row small { font-size: 12px; color: #64748b; display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }
        .status-released   { background: #dcfce7; color: #16a34a; }
        .status-unreleased { background: #fee2e2; color: #dc2626; }

        .score-right {
            text-align: right;
            flex-shrink: 0;
        }
        .score-right strong { font-size: 20px; font-weight: 800; display: block; }
        .score-right small  { font-size: 12px; color: #64748b; }

        .results-filter-row {
            padding: 16px 22px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        .results-search-wrap {
            display: flex;
            align-items: center;
            border: 1px solid #d8dce2;
            border-radius: 8px;
            padding: 0 14px;
            flex: 1;
            min-width: 200px;
            background: #f9fafb;
        }
        .results-search-wrap input {
            border: none; background: transparent; padding: 10px 8px;
            font-size: 14px; width: 100%; outline: none;
        }
        .filter-select {
            min-height: 40px;
            padding: 0 12px;
            border: 1px solid #d8dce2;
            border-radius: 8px;
            background: #fff;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            outline: none;
        }

        .results-summary-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .results-summary-stat {
            padding: 18px 22px;
            border-right: 1px solid #e5e7eb;
        }
        .results-summary-stat:last-child { border-right: none; }
        .results-summary-stat p { font-size: 12px; color: #64748b; margin-bottom: 4px; }
        .results-summary-stat strong { font-size: 22px; font-weight: 800; }

        .note-box {
            padding: 14px 22px;
            background: #eff6ff;
            border-top: 1px solid #bfdbfe;
            font-size: 13px;
            color: #1d4ed8;
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }
        .note-box svg { flex-shrink: 0; margin-top: 2px; }

        .subjects-search-wrap {
            padding: 16px 22px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        .subjects-search-input {
            display: flex;
            align-items: center;
            border: 1px solid #d8dce2;
            border-radius: 8px;
            padding: 0 14px;
            flex: 1;
            background: #f9fafb;
        }
        .subjects-search-input input {
            border: none; background: transparent; padding: 10px 8px;
            font-size: 14px; width: 100%; outline: none;
        }

        .subject-group { border-bottom: 1px solid #e5e7eb; }
        .subject-group:last-child { border-bottom: none; }
        .subject-group-header {
            padding: 16px 22px;
            background: #f9fafb;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .subject-group-header svg { flex-shrink: 0; color: #556070; width: 18px; height: 18px; }
        .subject-group-header h3 { font-size: 16px; margin-bottom: 2px; }
        .subject-group-header small { font-size: 12px; color: #64748b; }

        .subject-section-row {
            padding: 14px 22px 14px 52px;
            border-top: 1px solid #f3f4f6;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .subject-section-row:hover { background: #f9fafb; }
        .subject-section-row .sec-info { flex: 1; }
        .subject-section-row .sec-info strong { font-size: 14px; display: flex; align-items: center; gap: 6px; }
        .subject-section-row .sec-info p { font-size: 13px; color: #556070; margin-top: 2px; }

        .teaching-load-summary {
            margin: 22px 0 0;
            background: #172033;
            border-radius: 8px;
            padding: 24px 28px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0;
        }
        .load-stat { text-align: center; }
        .load-stat p { font-size: 12px; color: #a8b4c4; margin-bottom: 4px; }
        .load-stat strong { font-size: 28px; font-weight: 800; color: #fff; }

        .criteria-selector {
            padding: 16px 22px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .criteria-header-card {
            background: #172033;
            padding: 24px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .criteria-header-card h2 { font-size: 20px; color: #fff; margin-bottom: 4px; }
        .criteria-header-card p  { font-size: 14px; color: #a8b4c4; margin: 0; }
        .criteria-header-score { text-align: right; }
        .criteria-header-score small { display: block; font-size: 12px; color: #a8b4c4; }
        .criteria-header-score strong { font-size: 32px; font-weight: 800; color: #f59e0b; }

        .category-row {
            padding: 16px 22px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .category-row:last-child { border-bottom: none; }
        .category-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .category-row .cat-name { flex: 1; font-size: 15px; font-weight: 600; }
        .cat-bar-wrap { flex: 2; height: 6px; background: #e5e7eb; border-radius: 999px; overflow: hidden; }
        .cat-bar-fill { height: 100%; border-radius: 999px; }
        .cat-score-right { text-align: right; min-width: 80px; }
        .cat-score-right strong { font-size: 20px; font-weight: 800; display: block; }
        .cat-score-right small  { font-size: 12px; color: #64748b; }

        .criteria-highlights {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0;
            border-top: 1px solid #e5e7eb;
        }
        .highlight-card {
            padding: 18px 22px;
            border-right: 1px solid #e5e7eb;
        }
        .highlight-card:last-child { border-right: none; }
        .highlight-card small { font-size: 12px; color: #64748b; display: block; margin-bottom: 4px; }
        .highlight-card h4 { font-size: 15px; font-weight: 700; margin-bottom: 4px; }
        .highlight-card strong { font-size: 20px; font-weight: 800; }

        .rating-scale-guide {
            padding: 16px 22px;
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            border-top: 1px solid #e5e7eb;
            font-size: 12px;
            color: #556070;
        }
        .scale-item { display: flex; align-items: center; gap: 6px; }
        .scale-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }

        .comments-header-info {
            padding: 16px 22px;
            background: #eff6ff;
            border-bottom: 1px solid #bfdbfe;
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }
        .comments-header-info svg { flex-shrink: 0; margin-top: 2px; }
        .comments-header-info p { font-size: 13px; color: #1d4ed8; margin: 0; }

        .comment-group { border-bottom: 1px solid #e5e7eb; }
        .comment-group:last-child { border-bottom: none; }
        .comment-group-header {
            padding: 16px 22px;
            background: #f9fafb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .comment-group-header h3 { font-size: 15px; margin-bottom: 3px; display: flex; align-items: center; gap: 8px; }
        .comment-group-header p  { font-size: 13px; color: #64748b; }

        .comment-item {
            padding: 16px 22px;
            border-top: 1px solid #f3f4f6;
            display: flex;
            gap: 14px;
        }
        .comment-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #172033;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .comment-avatar svg { width: 18px; height: 18px; }
        .comment-body { flex: 1; }
        .comment-body .anon-label { font-size: 13px; font-weight: 700; margin-bottom: 2px; display: flex; align-items: center; gap: 8px; }
        .anon-identity { font-size: 11px; background: #f3f4f6; color: #64748b; padding: 2px 8px; border-radius: 999px; font-weight: 400; }
        .comment-body .comment-date { font-size: 12px; color: #9ca3af; }
        .comment-body .comment-text { font-size: 14px; color: #071226; margin-top: 6px; line-height: 1.6; }

        .comments-footer {
            padding: 18px 22px;
            border-top: 1px solid #e5e7eb;
            background: #f9fafb;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0;
        }
        .comments-footer-stat { text-align: center; }
        .comments-footer-stat p { font-size: 12px; color: #64748b; margin-bottom: 2px; }
        .comments-footer-stat strong { font-size: 20px; font-weight: 800; }
        .comments-footer-stat svg { width: 18px; height: 18px; }

        .participation-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .participation-summary-stat {
            padding: 18px 22px;
            border-right: 1px solid #e5e7eb;
        }
        .participation-summary-stat:last-child { border-right: none; }
        .participation-summary-stat p { font-size: 12px; color: #64748b; margin-bottom: 4px; }
        .participation-summary-stat strong { font-size: 22px; font-weight: 800; }

        .participation-class-row {
            padding: 18px 22px;
            border-bottom: 1px solid #e5e7eb;
        }
        .participation-class-row:last-child { border-bottom: none; }
        .participation-class-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        .participation-class-top h3 { font-size: 15px; margin-bottom: 4px; display: flex; align-items: center; gap: 8px; }
        .participation-class-top p { font-size: 13px; color: #556070; }
        .participation-rate-badge {
            font-size: 20px;
            font-weight: 800;
            flex-shrink: 0;
        }
        .participation-stats-row {
            display: flex;
            gap: 28px;
            margin-bottom: 10px;
        }
        .participation-stat { font-size: 13px; color: #556070; }
        .participation-stat strong { display: block; font-size: 16px; font-weight: 800; color: #071226; }
        .participation-bar { height: 6px; background: #e5e7eb; border-radius: 999px; overflow: hidden; }
        .participation-bar-fill { height: 100%; border-radius: 999px; transition: width 0.3s; }

        .participation-rate-guide {
            margin-top: 22px;
            padding: 16px 22px;
            border: 1px solid #d8dce2;
            border-radius: 8px;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            font-size: 13px;
        }
        .rate-guide-item { display: flex; align-items: center; gap: 8px; }
        .rate-guide-dot { width: 12px; height: 12px; border-radius: 50%; flex-shrink: 0; }

        .reports-config {
            border: 1px solid #d8dce2;
            border-radius: 8px;
            padding: 22px;
            margin-bottom: 22px;
        }
        .reports-config h2 { font-size: 17px; margin-bottom: 16px; }
        .reports-config-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }

        .reports-summary {
            border: 1px solid #d8dce2;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 22px;
        }
        .reports-summary-header { padding: 16px 22px; border-bottom: 1px solid #e5e7eb; }
        .reports-summary-header h2 { font-size: 17px; }
        .reports-summary-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0;
        }
        .report-stat {
            padding: 18px 22px;
            border-right: 1px solid #e5e7eb;
        }
        .report-stat:last-child { border-right: none; }
        .report-stat p { font-size: 12px; color: #64748b; margin-bottom: 4px; }
        .report-stat strong { font-size: 22px; font-weight: 800; }

        .report-actions { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 22px; }
        .btn-report-preview  { min-height: 52px; border-radius: 8px; background: #2563eb; color: #fff; font-weight: 700; font-size: 15px; display: flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; border: none; }
        .btn-report-download { min-height: 52px; border-radius: 8px; background: #16a34a; color: #fff; font-weight: 700; font-size: 15px; display: flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; border: none; }
        .btn-report-print    { min-height: 52px; border-radius: 8px; background: #172033; color: #fff; font-weight: 700; font-size: 15px; display: flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; border: none; }

        .report-contents { border: 1px solid #d8dce2; border-radius: 8px; overflow: hidden; margin-bottom: 22px; }
        .report-contents-header { padding: 16px 22px; border-bottom: 1px solid #e5e7eb; }
        .report-contents-header h2 { font-size: 17px; }
        .report-contents-header p  { font-size: 13px; color: #64748b; margin-top: 2px; }
        .report-content-item { padding: 14px 22px; border-bottom: 1px solid #f3f4f6; display: flex; gap: 12px; align-items: flex-start; }
        .report-content-item:last-child { border-bottom: none; }
        .report-content-item strong { font-size: 14px; display: block; margin-bottom: 2px; }
        .report-content-item p { font-size: 13px; color: #64748b; margin: 0; }

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
            background: #172033;
            color: #fff;
        }
        .modal-header-title { display: flex; align-items: center; gap: 10px; }
        .btn-modal-close { background: none; border: none; cursor: pointer; color: #fff; display: flex; align-items: center; }
        .modal-body { padding: 22px; display: flex; flex-direction: column; gap: 16px; }
        .modal-alert-blue { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 14px 18px; display: flex; gap: 12px; }
        .modal-alert-blue p { font-size: 13px; color: #1d4ed8; margin: 0; }
        .modal-alert-blue strong { display: block; color: #1d4ed8; margin-bottom: 4px; font-size: 14px; }
        .modal-btn-row { display: flex; gap: 12px; }
        .btn-modal-cancel { flex: 1; min-height: 46px; border: 1px solid #d8dce2; border-radius: 8px; background: #f3f4f6; font-weight: 700; font-size: 14px; display: flex; align-items: center; justify-content: center; cursor: pointer; }
        .btn-modal-logout { flex: 2; min-height: 46px; border-radius: 8px; background: #dc2626; color: #fff; font-weight: 700; font-size: 14px; display: flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; border: none; }

        .empty-state { padding: 40px 22px; text-align: center; color: #64748b; font-size: 14px; }

        @media (max-width: 900px) {
            .results-summary-stats { grid-template-columns: repeat(2, 1fr); }
            .criteria-highlights { grid-template-columns: 1fr; }
            .participation-summary-grid { grid-template-columns: repeat(2, 1fr); }
            .reports-config-grid { grid-template-columns: 1fr; }
            .reports-summary-stats { grid-template-columns: repeat(2, 1fr); }
            .report-actions { grid-template-columns: 1fr; }
            .teaching-load-summary { grid-template-columns: 1fr; gap: 12px; }
            .comments-footer { grid-template-columns: 1fr; gap: 12px; }
        }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="brand">
        <img src="../img/eastgate_college_logo.png" alt="Eastgate College Logo">
        <h1>Student Evaluation for Teacher</h1>
        <p>Faculty Portal</p>
    </div>

    <nav class="nav-menu">
        <a class="nav-link active" href="#" data-tab="tab-dashboard"><?php echo svg_icon("dashboard"); ?> Dashboard</a>
        <a class="nav-link" href="#" data-tab="tab-subjects"><?php echo svg_icon("book"); ?> My Subjects</a>
        <a class="nav-link" href="#" data-tab="tab-results"><?php echo svg_icon("chart"); ?> Evaluation Results</a>
        <a class="nav-link" href="#" data-tab="tab-criteria"><?php echo svg_icon("list"); ?> Criteria Scores</a>
        <a class="nav-link" href="#" data-tab="tab-comments"><?php echo svg_icon("comment"); ?> Comments</a>
        <a class="nav-link" href="#" data-tab="tab-participation"><?php echo svg_icon("users"); ?> Participation</a>
        <a class="nav-link" href="#" data-tab="tab-reports"><?php echo svg_icon("file"); ?> Reports</a>
    </nav>

    <div class="sidebar-bottom">
        <div class="department-box">
            <span>Department</span>
            <strong><?php echo e($faculty["department_name"] ?? ""); ?></strong>
        </div>
        <a class="logout-link" href="#" id="btn-logout-trigger"><?php echo svg_icon("logout", "#ef4444"); ?> Logout</a>
    </div>
</aside>

<main class="main-content">
    <header class="topbar">
        <div>
            <h2>Welcome, Prof. <?php echo e($faculty["full_name"] ?? "Faculty"); ?></h2>
            <p><?php echo e($term_label); ?></p>
        </div>
        <div class="topbar-right">
            <span class="id-badge">Faculty ID: <strong><?php echo e($faculty["faculty_number"] ?? ""); ?></strong></span>
            <span class="bell"><?php echo svg_icon("bell", "#334155"); ?></span>
        </div>
    </header>

    <div class="content-wrap">

        <div class="tab-panel active" id="tab-dashboard">
            <div class="page-title-row">
                <div>
                    <h1>Dashboard</h1>
                    <p>Overview of your evaluation results and assigned subjects</p>
                </div>
                <a href="#" class="refresh-btn" id="btn-refresh"><?php echo svg_icon("refresh"); ?> Refresh</a>
            </div>

            <div class="stats-grid faculty-stats">
                <div class="stat-card">
                    <div>
                        <p>Assigned Subjects</p>
                        <strong><?php echo $assigned_count; ?></strong>
                    </div>
                    <span class="stat-icon dark"><?php echo svg_icon("book", "#fff"); ?></span>
                </div>
                <div class="stat-card">
                    <div>
                        <p>Released Results</p>
                        <strong class="green-text"><?php echo $released_count; ?></strong>
                    </div>
                    <span class="stat-icon green"><?php echo svg_icon("check-circle", "#16a34a"); ?></span>
                </div>
                <div class="stat-card">
                    <div>
                        <p>Overall Average</p>
                        <strong class="yellow-text"><?php echo $overall_average; ?></strong>
                    </div>
                    <span class="stat-icon yellow"><?php echo svg_icon("trend", "#f59e0b"); ?></span>
                </div>
                <div class="stat-card">
                    <div>
                        <p>Participation</p>
                        <strong class="blue-text"><?php echo $participation_rate; ?>%</strong>
                    </div>
                    <span class="stat-icon blue"><?php echo svg_icon("users", "#2563eb"); ?></span>
                </div>
            </div>

            <?php if ($released_count > 0): ?>
            <div class="alert alert-green">
                <?php echo svg_icon("check-circle", "#16a34a"); ?>
                <div>
                    <strong>Evaluation Results Released</strong>
                    <p>Your evaluation results for <?php echo e($term_label); ?> have been released. You can now view detailed feedback and scores.</p>
                </div>
            </div>
            <?php endif; ?>

            <section class="panel">
                <div class="panel-header">
                    <div>
                        <h2>My Assigned Subjects</h2>
                        <p><?php echo e($term_label); ?></p>
                    </div>
                    <a href="#" data-tab="tab-subjects">View All</a>
                </div>

                <?php if (empty($assigned_subjects)): ?>
                    <div class="list-row">
                        <div>
                            <h3>No assigned subjects found</h3>
                            <p>No active teaching assignment available.</p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php
                $result_map = [];
                foreach ($all_results as $r) {
                    $result_map[$r["teaching_assignment_id"]] = $r;
                }
                foreach ($assigned_subjects as $subject):
                    $res = $result_map[$subject["teaching_assignment_id"]] ?? null;
                ?>
                <div class="list-row">
                    <div>
                        <h3>
                            <?php echo e($subject["subject_code"]); ?>
                            <span class="section-badge"><?php echo e($subject["section_name"]); ?></span>
                        </h3>
                        <p><?php echo e($subject["subject_title"]); ?></p>
                        <small><?php echo (int) $subject["student_count"]; ?> students · <?php echo e($subject["term_name"]); ?></small>
                    </div>
                    <?php if ($res && $res["overall_average_score"] !== null): ?>
                    <div class="score-box">
                        <strong style="color:<?php echo score_color((float)$res["overall_average_score"]); ?>"><?php echo number_format((float) $res["overall_average_score"], 2); ?></strong>
                        <span><?php echo (int) $res["submitted_response_count"]; ?>/<?php echo (int) $res["eligible_student_count"]; ?> responses</span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </section>

            <div class="action-grid">
                <a href="#" class="action-btn" data-tab="tab-subjects"><?php echo svg_icon("book"); ?> View My Subjects</a>
                <a href="#" class="action-btn active" data-tab="tab-results"><?php echo svg_icon("chart"); ?> View Evaluation Results</a>
                <a href="#" class="action-btn" data-tab="tab-comments"><?php echo svg_icon("comment"); ?> View Comments</a>
            </div>
        </div>


        <div class="tab-panel" id="tab-subjects">
            <div class="page-title-row" style="margin-bottom:24px;">
                <div>
                    <h1>My Subjects</h1>
                    <p>View all your assigned subjects and sections</p>
                </div>
                <a href="#" class="refresh-btn" data-tab="tab-dashboard"><?php echo svg_icon("arrow-left"); ?> Back to Dashboard</a>
            </div>

            <section class="panel" style="overflow:hidden;">
                <div class="subjects-search-wrap">
                    <div class="subjects-search-input">
                        <?php echo svg_icon("search", "#9ca3af"); ?>
                        <input type="text" id="subjects-search" placeholder="Search by subject code, title, or section...">
                    </div>
                    <select class="filter-select" id="subjects-filter-subject">
                        <option value="">All Subjects</option>
                        <?php foreach (array_keys($subjects_by_code) as $code): ?>
                        <option value="<?php echo e($code); ?>"><?php echo e($code); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select class="filter-select" id="subjects-filter-year">
                        <option value="">All Year Levels</option>
                        <?php
                        $years = array_unique(array_column($assigned_subjects, "year_level"));
                        sort($years);
                        foreach ($years as $yr):
                        ?>
                        <option value="<?php echo e((string)$yr); ?>"><?php echo e((string)$yr); ?>nd Year</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="padding:10px 22px;font-size:13px;color:#64748b;border-bottom:1px solid #e5e7eb;">
                    Showing <strong id="subjects-count"><?php echo $total_sections; ?></strong> of <?php echo $total_sections; ?> assigned subjects
                </div>

                <div id="subjects-list">
                    <?php foreach ($subjects_by_code as $code => $group): ?>
                    <div class="subject-group" data-code="<?php echo e($code); ?>">
                        <div class="subject-group-header">
                            <?php echo svg_icon("book", "#556070"); ?>
                            <div>
                                <h3><?php echo e($code); ?> – <?php echo e($group["subject_title"]); ?></h3>
                                <small><?php echo count($group["sections"]); ?> section(s)</small>
                            </div>
                        </div>
                        <?php foreach ($group["sections"] as $sec): ?>
                        <div class="subject-section-row" data-year="<?php echo e((string)$sec["year_level"]); ?>" data-search="<?php echo e(strtolower($code . " " . $group["subject_title"] . " " . $sec["section_name"])); ?>">
                            <div class="sec-info">
                                <strong>
                                    <span class="section-badge" style="background:#172033;color:#fff;"><?php echo e($sec["section_name"]); ?></span>
                                    <span class="badge-course"><?php echo e($sec["course_code"]); ?></span>
                                    <span class="badge-year"><?php echo e((string)$sec["year_level"]); ?>nd Year</span>
                                </strong>
                                <p><?php echo (int)$sec["student_count"]; ?> students &nbsp;·&nbsp; <?php echo svg_icon("calendar", "#9ca3af"); ?> <?php echo e($sec["term_name"]); ?>, <?php echo e($current_period["academic_year_name"] ?? ""); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($subjects_by_code)): ?>
                    <div class="empty-state">No assigned subjects found.</div>
                    <?php endif; ?>
                </div>

                <div class="teaching-load-summary">
                    <div class="load-stat">
                        <p>Total Subjects</p>
                        <strong><?php echo $total_subjects_distinct; ?></strong>
                    </div>
                    <div class="load-stat">
                        <p>Total Sections</p>
                        <strong><?php echo $total_sections; ?></strong>
                    </div>
                    <div class="load-stat">
                        <p>Total Students</p>
                        <strong><?php echo $total_students; ?></strong>
                    </div>
                </div>
            </section>
        </div>


        <div class="tab-panel" id="tab-results">
            <div class="page-title-row" style="margin-bottom:24px;">
                <div>
                    <h1>Evaluation Results</h1>
                    <p>View summarized evaluation results for your assigned classes</p>
                </div>
                <a href="#" class="refresh-btn" data-tab="tab-dashboard"><?php echo svg_icon("arrow-left"); ?> Back to Dashboard</a>
            </div>

            <section class="panel" style="overflow:hidden;">
                <div class="results-filter-row">
                    <div class="results-search-wrap">
                        <?php echo svg_icon("search", "#9ca3af"); ?>
                        <input type="text" id="results-search" placeholder="Search subject...">
                    </div>
                    <select class="filter-select" id="results-filter-subject">
                        <option value="">All Subjects</option>
                        <?php foreach (array_keys($subjects_by_code) as $code): ?>
                        <option value="<?php echo e($code); ?>"><?php echo e($code); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select class="filter-select" id="results-filter-section">
                        <option value="">All Sections</option>
                        <?php foreach ($assigned_subjects as $s): ?>
                        <option value="<?php echo e($s["section_name"]); ?>"><?php echo e($s["section_name"]); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="results-summary-stats">
                    <div class="results-summary-stat">
                        <p>Overall Average</p>
                        <strong style="color:#f59e0b;"><?php echo $overall_average; ?></strong>
                    </div>
                    <div class="results-summary-stat">
                        <p>Released Results</p>
                        <strong style="color:#16a34a;"><?php echo $released_count; ?></strong>
                    </div>
                    <div class="results-summary-stat">
                        <p>Unreleased Results</p>
                        <strong style="color:#dc2626;"><?php echo $unreleased_count; ?></strong>
                    </div>
                    <div class="results-summary-stat">
                        <p>Avg. Participation</p>
                        <strong style="color:#2563eb;"><?php echo $participation_rate; ?>%</strong>
                    </div>
                </div>

                <div style="padding:16px 22px;font-weight:700;font-size:15px;border-bottom:1px solid #e5e7eb;">
                    Detailed Results
                    <div style="font-size:13px;font-weight:400;color:#64748b;margin-top:2px;">Click on any result to view detailed criteria scores</div>
                </div>

                <div id="results-list">
                    <?php foreach ($all_results as $r):
                        $is_released = $r["result_status"] === "Released";
                        $avg = $r["overall_average_score"] !== null ? (float)$r["overall_average_score"] : null;
                        $part_pct = (int)$r["eligible_student_count"] > 0
                            ? round(((int)$r["submitted_response_count"] / (int)$r["eligible_student_count"]) * 100, 1)
                            : 0;
                        $released_date = "";
                        if ($r["released_at"]) {
                            $dt = new DateTime($r["released_at"]);
                            $released_date = $dt->format("n/j/Y");
                        }
                    ?>
                    <div class="result-row"
                         data-subject="<?php echo e(strtolower($r["subject_code"])); ?>"
                         data-section="<?php echo e($r["section_name"]); ?>"
                         data-search="<?php echo e(strtolower($r["subject_code"] . " " . $r["subject_title"] . " " . $r["section_name"])); ?>"
                         data-code="<?php echo e($r["subject_code"]); ?>"
                         data-section-name="<?php echo e($r["section_name"]); ?>"
                         <?php if ($is_released): ?>onclick="switchToCriteria('<?php echo e($r["subject_code"]); ?>', '<?php echo e($r["section_name"]); ?>')"<?php endif; ?>>
                        <div>
                            <h3>
                                <?php echo e($r["subject_code"]); ?>
                                <span class="section-badge"><?php echo e($r["section_name"]); ?></span>
                                <?php if ($is_released): ?>
                                    <span class="status-badge status-released"><?php echo svg_icon("check-circle", "#16a34a"); ?> Released</span>
                                <?php else: ?>
                                    <span class="status-badge status-unreleased"><?php echo svg_icon("x-circle", "#dc2626"); ?> Unreleased</span>
                                <?php endif; ?>
                            </h3>
                            <p><?php echo e($r["subject_title"]); ?></p>
                            <small>
                                <?php echo (int)$r["submitted_response_count"]; ?>/<?php echo (int)$r["eligible_student_count"]; ?> responses
                                &nbsp;·&nbsp;
                                <?php echo $part_pct; ?>% participation
                                <?php if ($is_released && $released_date): ?>
                                &nbsp;·&nbsp; Released on <?php echo e($released_date); ?>
                                <?php endif; ?>
                            </small>
                        </div>
                        <?php if ($avg !== null): ?>
                        <div class="score-right">
                            <strong style="color:<?php echo score_color($avg); ?>"><?php echo number_format($avg, 2); ?></strong>
                            <small>out of 5.00</small>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($all_results)): ?>
                    <div class="empty-state">No evaluation results available for this period.</div>
                    <?php endif; ?>
                </div>

                <div class="note-box">
                    <?php echo svg_icon("info", "#2563eb"); ?>
                    <p><strong>Note:</strong> Both released and unreleased evaluation results are displayed. Released results (green badge) are final and viewable by all authorized parties. Unreleased results (red badge) are still being processed and not yet publicly available. You cannot edit or delete submitted evaluations.</p>
                </div>
            </section>
        </div>


        <div class="tab-panel" id="tab-criteria">
            <div class="page-title-row" style="margin-bottom:24px;">
                <div>
                    <h1>Criteria Scores</h1>
                    <p>Detailed breakdown of evaluation scores by category</p>
                </div>
                <a href="#" class="refresh-btn" data-tab="tab-results"><?php echo svg_icon("arrow-left"); ?> Back to Results</a>
            </div>

            <section class="panel" style="overflow:hidden;">
                <div class="criteria-selector">
                    <select class="filter-select" id="criteria-filter-subject" style="flex:1;">
                        <option value="">Select Subject</option>
                        <?php foreach (array_keys($criteria_data) as $key):
                            $parts = explode("||", $key);
                        ?>
                        <option value="<?php echo e($key); ?>"><?php echo e($parts[0]); ?> – <?php echo e($parts[1]); ?></option>
                        <?php endforeach; ?>
                        <?php if (empty($criteria_data)): ?>
                        <option disabled>No released results available</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div id="criteria-content">
                    <?php if (empty($criteria_data)): ?>
                    <div class="empty-state">No released evaluation results with criteria scores are available.</div>
                    <?php else:
                        $first_key = array_key_first($criteria_data);
                        foreach ($criteria_data as $key => $cdata):
                            $cats = $cdata["categories"];
                            $overall = $cdata["overall_avg"] !== null ? (float)$cdata["overall_avg"] : 0.0;
                            $highest_cat = null;
                            $lowest_cat  = null;
                            $cat_avg_sum = 0.0;
                            $cat_count   = 0;
                            foreach ($cats as $cat) {
                                $cs = (float)$cat["average_score"];
                                $cat_avg_sum += $cs;
                                $cat_count++;
                                if ($highest_cat === null || $cs > (float)$highest_cat["average_score"]) $highest_cat = $cat;
                                if ($lowest_cat  === null || $cs < (float)$lowest_cat["average_score"])  $lowest_cat  = $cat;
                            }
                            $cats_avg = $cat_count > 0 ? $cat_avg_sum / $cat_count : 0.0;
                    ?>
                    <div class="criteria-block" data-key="<?php echo e($key); ?>" style="<?php echo $key === $first_key ? "" : "display:none;"; ?>">
                        <div class="criteria-header-card">
                            <div>
                                <h2><?php echo e($cdata["subject_code"]); ?> <span class="section-badge" style="font-size:13px;"><?php echo e($cdata["section_name"]); ?></span></h2>
                                <p><?php echo e($cdata["subject_title"]); ?></p>
                            </div>
                            <div class="criteria-header-score">
                                <small>Overall Average</small>
                                <strong><?php echo number_format($overall, 2); ?></strong>
                                <small>out of 5.00</small>
                            </div>
                        </div>

                        <div style="padding:16px 22px;font-weight:700;font-size:15px;border-bottom:1px solid #e5e7eb;">Category Scores <span style="font-size:13px;font-weight:400;color:#64748b;">Average ratings for each evaluation category</span></div>

                        <?php foreach ($cats as $cat):
                            $cs = (float)$cat["average_score"];
                            $pct = (float)$cat["percentage_score"];
                            $bg = score_bg($cs);
                            $clr = score_color($cs);
                        ?>
                        <div class="category-row">
                            <div class="category-icon" style="background:<?php echo $bg; ?>;">
                                <?php echo svg_icon("award", $clr); ?>
                            </div>
                            <span class="cat-name"><?php echo e($cat["category_name"]); ?></span>
                            <div class="cat-bar-wrap">
                                <div class="cat-bar-fill" style="width:<?php echo $pct; ?>%;background:<?php echo $clr; ?>;"></div>
                            </div>
                            <div class="cat-score-right">
                                <strong style="color:<?php echo $clr; ?>;"><?php echo number_format($cs, 2); ?></strong>
                                <small><?php echo number_format($pct, 1); ?>%</small>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <div class="criteria-highlights">
                            <div class="highlight-card">
                                <small>Highest Score</small>
                                <h4><?php echo $highest_cat ? e($highest_cat["category_name"]) : "—"; ?></h4>
                                <strong style="color:#16a34a;"><?php echo $highest_cat ? number_format((float)$highest_cat["average_score"], 2) : "—"; ?></strong>
                            </div>
                            <div class="highlight-card">
                                <small>Average Score</small>
                                <h4>All Categories</h4>
                                <strong style="color:#2563eb;"><?php echo number_format($cats_avg, 2); ?></strong>
                            </div>
                            <div class="highlight-card">
                                <small>Lowest Score</small>
                                <h4><?php echo $lowest_cat ? e($lowest_cat["category_name"]) : "—"; ?></h4>
                                <strong style="color:#f59e0b;"><?php echo $lowest_cat ? number_format((float)$lowest_cat["average_score"], 2) : "—"; ?></strong>
                            </div>
                        </div>

                        <div class="rating-scale-guide">
                            <strong style="font-size:13px;color:#071226;">Rating Scale Guide</strong>
                            <div class="scale-item"><div class="scale-dot" style="background:#16a34a;"></div> Excellent (4.5–5.0)</div>
                            <div class="scale-item"><div class="scale-dot" style="background:#2563eb;"></div> Very Good (4.0–4.4)</div>
                            <div class="scale-item"><div class="scale-dot" style="background:#f59e0b;"></div> Good (3.5–3.9)</div>
                            <div class="scale-item"><div class="scale-dot" style="background:#ea580c;"></div> Fair (3.0–3.4)</div>
                            <div class="scale-item"><div class="scale-dot" style="background:#dc2626;"></div> Needs Improvement (&lt;3.0)</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>


        <div class="tab-panel" id="tab-comments">
            <div class="page-title-row" style="margin-bottom:24px;">
                <div>
                    <h1>Student Comments</h1>
                    <p>Anonymous feedback from your students</p>
                </div>
                <a href="#" class="refresh-btn" data-tab="tab-results"><?php echo svg_icon("arrow-left"); ?> Back to Results</a>
            </div>

            <section class="panel" style="overflow:hidden;">
                <div class="comments-header-info">
                    <?php echo svg_icon("shield", "#2563eb"); ?>
                    <p><strong>Anonymous Feedback</strong> All comments are completely anonymous. Student names and identities are not shown to protect confidentiality. You cannot reply to, edit, or delete these comments.</p>
                </div>

                <div style="padding:14px 22px;border-bottom:1px solid #e5e7eb;display:flex;gap:10px;flex-wrap:wrap;">
                    <select class="filter-select" id="comments-filter-subject" style="flex:1;">
                        <option value="">All Subjects</option>
                        <?php foreach (array_keys($subjects_by_code) as $code): ?>
                        <option value="<?php echo e($code); ?>"><?php echo e($code); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select class="filter-select" id="comments-filter-section">
                        <option value="">All Sections</option>
                        <?php foreach ($assigned_subjects as $s): ?>
                        <option value="<?php echo e($s["section_name"]); ?>"><?php echo e($s["section_name"]); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="comments-list">
                    <?php if (empty($comments_data)): ?>
                    <div class="empty-state">No student comments available yet.</div>
                    <?php else:
                        foreach ($comments_data as $cd):
                            $avg_display = $cd["average_score"] !== null
                                ? "Average Score: " . number_format((float)$cd["average_score"], 2)
                                : "Average Score: N/A";
                    ?>
                    <div class="comment-group" data-subject="<?php echo e($cd["subject_code"]); ?>" data-section="<?php echo e($cd["section_name"]); ?>">
                        <div class="comment-group-header">
                            <div>
                                <h3>
                                    <?php echo e($cd["subject_code"]); ?> – <?php echo e($cd["subject_title"]); ?>
                                    <span class="section-badge"><?php echo e($cd["section_name"]); ?></span>
                                </h3>
                                <p><?php echo count($cd["comments"]); ?> comment(s) · <?php echo e($avg_display); ?></p>
                            </div>
                        </div>
                        <?php foreach ($cd["comments"] as $idx => $cmt):
                            $date_display = "";
                            if ($cmt["submitted_at"]) {
                                $dt = new DateTime($cmt["submitted_at"]);
                                $date_display = $dt->format("n/j/Y");
                            }
                        ?>
                        <div class="comment-item">
                            <div class="comment-avatar"><?php echo svg_icon("comment", "#fff"); ?></div>
                            <div class="comment-body">
                                <div class="anon-label">
                                    Anonymous Student #<?php echo $idx + 1; ?>
                                    <span class="anon-identity">Student Identity Protected</span>
                                    <?php if ($date_display): ?><span class="comment-date"><?php echo svg_icon("calendar", "#9ca3af"); ?> <?php echo e($date_display); ?></span><?php endif; ?>
                                </div>
                                <div class="comment-text"><?php echo e($cmt["comment_text"]); ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="comments-footer">
                    <div class="comments-footer-stat">
                        <p>Total Comments</p>
                        <strong><?php echo $total_comments; ?></strong>
                    </div>
                    <div class="comments-footer-stat">
                        <p>Classes</p>
                        <strong><?php echo count($comments_data); ?></strong>
                    </div>
                    <div class="comments-footer-stat">
                        <p>Avg Comments/Class</p>
                        <strong><?php echo count($comments_data) > 0 ? number_format($total_comments / count($comments_data), 1) : "0"; ?></strong>
                    </div>
                </div>
            </section>
        </div>


        <div class="tab-panel" id="tab-participation">
            <div class="page-title-row" style="margin-bottom:24px;">
                <div>
                    <h1>Participation Rate</h1>
                    <p>Student evaluation submission statistics</p>
                </div>
                <a href="#" class="refresh-btn" data-tab="tab-dashboard"><?php echo svg_icon("arrow-left"); ?> Back to Dashboard</a>
            </div>

            <section class="panel" style="overflow:hidden;">
                <div style="padding:14px 22px;border-bottom:1px solid #e5e7eb;display:flex;gap:10px;flex-wrap:wrap;">
                    <select class="filter-select" id="participation-filter-subject" style="flex:1;">
                        <option value="">All Subjects</option>
                        <?php foreach (array_keys($subjects_by_code) as $code): ?>
                        <option value="<?php echo e($code); ?>"><?php echo e($code); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select class="filter-select" id="participation-filter-section">
                        <option value="">All Sections</option>
                        <?php foreach ($assigned_subjects as $s): ?>
                        <option value="<?php echo e($s["section_name"]); ?>"><?php echo e($s["section_name"]); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="participation-summary-grid">
                    <div class="participation-summary-stat">
                        <p>Overall Rate</p>
                        <strong style="color:#f59e0b;"><?php echo $overall_participation_rate; ?>%</strong>
                    </div>
                    <div class="participation-summary-stat">
                        <p>Total Submitted</p>
                        <strong style="color:#16a34a;"><?php echo $total_participation_submitted; ?></strong>
                    </div>
                    <div class="participation-summary-stat">
                        <p>Total Students</p>
                        <strong style="color:#2563eb;"><?php echo $total_participation_students; ?></strong>
                    </div>
                    <div class="participation-summary-stat">
                        <p>Pending</p>
                        <strong style="color:#f59e0b;"><?php echo $total_participation_pending; ?></strong>
                    </div>
                </div>

                <div style="padding:16px 22px;font-weight:700;font-size:15px;border-bottom:1px solid #e5e7eb;">
                    Participation by Class
                    <div style="font-size:13px;font-weight:400;color:#64748b;margin-top:2px;">Detailed breakdown for each section</div>
                </div>

                <div id="participation-list">
                    <?php
                    $part_subject_map = [];
                    foreach ($assigned_subjects as $s) {
                        $part_subject_map[$s["teaching_assignment_id"]] = $s;
                    }
                    foreach ($participation_data as $pd):
                        $rate = (float)$pd["participation_rate"];
                        $bar_color = participation_color($rate);
                    ?>
                    <div class="participation-class-row"
                         data-subject="<?php echo e($pd["subject_code"]); ?>"
                         data-section="<?php echo e($pd["section_name"]); ?>">
                        <div class="participation-class-top">
                            <div>
                                <h3>
                                    <?php echo e($pd["subject_code"]); ?>
                                    <span class="section-badge"><?php echo e($pd["section_name"]); ?></span>
                                </h3>
                                <p><?php echo e($pd["subject_title"]); ?></p>
                            </div>
                            <div class="participation-rate-badge" style="color:<?php echo $bar_color; ?>;">
                                <?php echo number_format($rate, 1); ?>%
                            </div>
                        </div>
                        <div class="participation-stats-row">
                            <div class="participation-stat">
                                <strong style="color:#16a34a;"><?php echo (int)$pd["submitted_response_count"]; ?></strong>
                                Submitted
                            </div>
                            <div class="participation-stat">
                                <strong><?php echo (int)$pd["eligible_student_count"]; ?></strong>
                                Total Students
                            </div>
                            <div class="participation-stat">
                                <strong style="color:#dc2626;"><?php echo (int)$pd["pending_response_count"]; ?></strong>
                                Pending
                            </div>
                        </div>
                        <div class="participation-bar">
                            <div class="participation-bar-fill" style="width:<?php echo min($rate, 100); ?>%;background:<?php echo $bar_color; ?>;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($participation_data)): ?>
                    <div class="empty-state">No participation data available for this period.</div>
                    <?php endif; ?>
                </div>

                <div class="participation-rate-guide">
                    <strong>Participation Rate Guide</strong>
                    <div class="rate-guide-item"><div class="rate-guide-dot" style="background:#16a34a;"></div> Excellent (≥90%)</div>
                    <div class="rate-guide-item"><div class="rate-guide-dot" style="background:#2563eb;"></div> Good (75–89%)</div>
                    <div class="rate-guide-item"><div class="rate-guide-dot" style="background:#f59e0b;"></div> Fair (60–74%)</div>
                    <div class="rate-guide-item"><div class="rate-guide-dot" style="background:#dc2626;"></div> Low (&lt;60%)</div>
                </div>
            </section>
        </div>


        <div class="tab-panel" id="tab-reports">
            <div class="page-title-row" style="margin-bottom:24px;">
                <div>
                    <h1>Generate Reports</h1>
                    <p>Preview, download, and print your evaluation reports</p>
                </div>
                <a href="#" class="refresh-btn" data-tab="tab-dashboard"><?php echo svg_icon("arrow-left"); ?> Back to Dashboard</a>
            </div>

            <div class="reports-config">
                <h2>Report Configuration</h2>
                <div class="reports-config-grid">
                    <select class="filter-select" style="width:100%;">
                        <option value="">All Subjects</option>
                        <?php foreach (array_keys($subjects_by_code) as $code): ?>
                        <option value="<?php echo e($code); ?>"><?php echo e($code); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select class="filter-select" style="width:100%;">
                        <option value="">All Sections</option>
                        <?php foreach ($assigned_subjects as $s): ?>
                        <option value="<?php echo e($s["section_name"]); ?>"><?php echo e($s["section_name"]); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select class="filter-select" style="width:100%;">
                        <option value="pdf">PDF Format</option>
                        <option value="excel">Excel Format</option>
                        <option value="print">Print</option>
                    </select>
                </div>
            </div>

            <div class="reports-summary">
                <div class="reports-summary-header">
                    <h2>Report Summary</h2>
                </div>
                <div class="reports-summary-stats">
                    <div class="report-stat">
                        <p>Total Classes</p>
                        <strong><?php echo $released_count; ?></strong>
                    </div>
                    <div class="report-stat">
                        <p>Overall Average</p>
                        <strong style="color:#f59e0b;"><?php echo $overall_average; ?></strong>
                    </div>
                    <div class="report-stat">
                        <p>Total Responses</p>
                        <strong style="color:#16a34a;"><?php echo $total_participation_submitted; ?></strong>
                    </div>
                    <div class="report-stat">
                        <p>Participation Rate</p>
                        <strong style="color:#2563eb;"><?php echo $overall_participation_rate; ?>%</strong>
                    </div>
                </div>
            </div>

            <div class="report-actions">
                <button class="btn-report-preview" id="btn-report-preview-main"><?php echo svg_icon("eye", "#fff"); ?> Preview Report</button>
                <button class="btn-report-download" id="btn-report-download-main"><?php echo svg_icon("download", "#fff"); ?> Download Report</button>
                <button class="btn-report-print" id="btn-report-print-main"><?php echo svg_icon("print", "#fff"); ?> Print Report</button>
            </div>

            <div class="report-contents">
                <div class="report-contents-header">
                    <h2>Report Contents</h2>
                    <p>The following data will be included in your report</p>
                </div>
                <div class="report-content-item">
                    <?php echo svg_icon("check-circle", "#16a34a"); ?>
                    <div><strong>Faculty Information</strong><p>Name, Department, Academic Year, Semester</p></div>
                </div>
                <div class="report-content-item">
                    <?php echo svg_icon("check-circle", "#16a34a"); ?>
                    <div><strong>Subject Details</strong><p>Subject Code, Title, Section, Total Students</p></div>
                </div>
                <div class="report-content-item">
                    <?php echo svg_icon("check-circle", "#16a34a"); ?>
                    <div><strong>Evaluation Scores</strong><p>Overall Average, Category Scores, Rating Distribution</p></div>
                </div>
                <div class="report-content-item">
                    <?php echo svg_icon("check-circle", "#16a34a"); ?>
                    <div><strong>Participation Statistics</strong><p>Total Responses, Participation Rate, Response Timeline</p></div>
                </div>
                <div class="report-content-item">
                    <?php echo svg_icon("check-circle", "#16a34a"); ?>
                    <div><strong>Anonymous Comments</strong><p>Student Feedback (All student identities protected)</p></div>
                </div>
                <div class="report-content-item">
                    <?php echo svg_icon("check-circle", "#16a34a"); ?>
                    <div><strong>Charts and Visualizations</strong><p>Score Distribution Charts, Trend Analysis</p></div>
                </div>
            </div>

            <div class="note-box" style="border-radius:8px;">
                <?php echo svg_icon("info", "#2563eb"); ?>
                <p>Reports can only be generated for released evaluation results. All student information remains anonymous in the report. Generated reports include timestamp and faculty information for record-keeping purposes.</p>
            </div>
        </div>

    </div>
</main>


<div class="modal-overlay" id="modal-report-preview" style="z-index:10000;">
    <div class="modal-box" style="width:min(96%,960px);max-height:92vh;display:flex;flex-direction:column;">
        <div class="modal-header" style="flex-shrink:0;">
            <div class="modal-header-title">
                <?php echo svg_icon("file", "#fff"); ?>
                Faculty Evaluation Report – Preview
            </div>
            <button class="btn-modal-close" id="btn-preview-close"><?php echo svg_icon("x-circle", "#fff"); ?></button>
        </div>
        <div style="flex:1;overflow:hidden;background:#f3f4f6;">
            <iframe id="preview-iframe" src="" style="width:100%;height:100%;min-height:72vh;border:none;background:#fff;" allowfullscreen></iframe>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modal-logout">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-header-title">
                <?php echo svg_icon("logout", "#fff"); ?>
                Confirm Logout
            </div>
            <button class="btn-modal-close" id="btn-modal-close"><?php echo svg_icon("x-circle", "#fff"); ?></button>
        </div>
        <div class="modal-body">
            <div class="modal-alert-blue">
                <?php echo svg_icon("info", "#2563eb"); ?>
                <div>
                    <strong>Session Information</strong>
                    <p>You are currently logged in as <strong><?php echo e($faculty["full_name"] ?? ""); ?></strong> (<?php echo e($faculty["faculty_number"] ?? ""); ?>). Logging out will end your current session and return you to the login page.</p>
                </div>
            </div>
            <div class="modal-btn-row">
                <button class="btn-modal-cancel" id="btn-modal-cancel">Cancel</button>
                <button class="btn-modal-logout" id="btn-do-logout"><?php echo svg_icon("logout", "#fff"); ?> Logout</button>
            </div>
        </div>
    </div>
</div>


<script>
(function () {
    "use strict";

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
            switchTab(this.dataset.tab);
        });
    });

    document.getElementById("btn-refresh").addEventListener("click", function (e) {
        e.preventDefault();
        window.location.reload();
    });

    document.getElementById("btn-logout-trigger").addEventListener("click", function (e) {
        e.preventDefault();
        document.getElementById("modal-logout").classList.add("open");
    });

    document.getElementById("btn-modal-close").addEventListener("click", function () {
        document.getElementById("modal-logout").classList.remove("open");
    });

    document.getElementById("btn-modal-cancel").addEventListener("click", function () {
        document.getElementById("modal-logout").classList.remove("open");
    });

    document.getElementById("modal-logout").addEventListener("click", function (e) {
        if (e.target === this) this.classList.remove("open");
    });

    document.getElementById("btn-do-logout").addEventListener("click", function () {
        window.location.href = "faculty_dashboard.php?logout=1";
    });

    const subjectsSearch = document.getElementById("subjects-search");
    const subjectsFilterSubject = document.getElementById("subjects-filter-subject");
    const subjectsFilterYear = document.getElementById("subjects-filter-year");

    function filterSubjects() {
        const query = subjectsSearch.value.toLowerCase();
        const filterSubj = subjectsFilterSubject.value.toLowerCase();
        const filterYear = subjectsFilterYear.value;
        let visible = 0;

        document.querySelectorAll(".subject-group").forEach(group => {
            const code = (group.dataset.code || "").toLowerCase();
            const sections = group.querySelectorAll(".subject-section-row");
            let groupVisible = false;

            if (filterSubj && code !== filterSubj) {
                group.style.display = "none";
                return;
            }

            sections.forEach(row => {
                const searchStr = row.dataset.search || "";
                const year = row.dataset.year || "";
                const matchText = !query || searchStr.includes(query);
                const matchYear = !filterYear || year === filterYear;
                if (matchText && matchYear) {
                    row.style.display = "";
                    groupVisible = true;
                    visible++;
                } else {
                    row.style.display = "none";
                }
            });

            group.style.display = groupVisible ? "" : "none";
        });

        const countEl = document.getElementById("subjects-count");
        if (countEl) countEl.textContent = visible;
    }

    if (subjectsSearch) subjectsSearch.addEventListener("input", filterSubjects);
    if (subjectsFilterSubject) subjectsFilterSubject.addEventListener("change", filterSubjects);
    if (subjectsFilterYear) subjectsFilterYear.addEventListener("change", filterSubjects);

    const resultsSearch = document.getElementById("results-search");
    const resultsFilterSubject = document.getElementById("results-filter-subject");
    const resultsFilterSection = document.getElementById("results-filter-section");

    function filterResults() {
        const query = resultsSearch ? resultsSearch.value.toLowerCase() : "";
        const filterSubj = resultsFilterSubject ? resultsFilterSubject.value.toLowerCase() : "";
        const filterSec  = resultsFilterSection ? resultsFilterSection.value : "";

        document.querySelectorAll(".result-row").forEach(row => {
            const search  = row.dataset.search || "";
            const subject = row.dataset.subject || "";
            const section = row.dataset.section || "";
            const matchText = !query || search.includes(query);
            const matchSubj = !filterSubj || subject === filterSubj;
            const matchSec  = !filterSec  || section === filterSec;
            row.style.display = (matchText && matchSubj && matchSec) ? "" : "none";
        });
    }

    if (resultsSearch) resultsSearch.addEventListener("input", filterResults);
    if (resultsFilterSubject) resultsFilterSubject.addEventListener("change", filterResults);
    if (resultsFilterSection) resultsFilterSection.addEventListener("change", filterResults);

    const criteriaSelect = document.getElementById("criteria-filter-subject");

    function updateCriteriaView(key) {
        document.querySelectorAll(".criteria-block").forEach(block => {
            block.style.display = (block.dataset.key === key) ? "" : "none";
        });
    }

    if (criteriaSelect) {
        criteriaSelect.addEventListener("change", function () {
            if (this.value) updateCriteriaView(this.value);
        });
        if (criteriaSelect.options.length > 1) {
            criteriaSelect.selectedIndex = 1;
            updateCriteriaView(criteriaSelect.value);
        }
    }

    window.switchToCriteria = function (subjectCode, sectionName) {
        const key = subjectCode + "||" + sectionName;
        switchTab("tab-criteria");
        setTimeout(function () {
            if (criteriaSelect) {
                for (let i = 0; i < criteriaSelect.options.length; i++) {
                    if (criteriaSelect.options[i].value === key) {
                        criteriaSelect.selectedIndex = i;
                        break;
                    }
                }
            }
            updateCriteriaView(key);
        }, 50);
    };

    const commentsFilterSubject = document.getElementById("comments-filter-subject");
    const commentsFilterSection = document.getElementById("comments-filter-section");

    function filterComments() {
        const filterSubj = commentsFilterSubject ? commentsFilterSubject.value : "";
        const filterSec  = commentsFilterSection ? commentsFilterSection.value : "";

        document.querySelectorAll(".comment-group").forEach(group => {
            const subject = group.dataset.subject || "";
            const section = group.dataset.section || "";
            const matchSubj = !filterSubj || subject === filterSubj;
            const matchSec  = !filterSec  || section === filterSec;
            group.style.display = (matchSubj && matchSec) ? "" : "none";
        });
    }

    if (commentsFilterSubject) commentsFilterSubject.addEventListener("change", filterComments);
    if (commentsFilterSection) commentsFilterSection.addEventListener("change", filterComments);

    const partFilterSubject = document.getElementById("participation-filter-subject");
    const partFilterSection = document.getElementById("participation-filter-section");

    function filterParticipation() {
        const filterSubj = partFilterSubject ? partFilterSubject.value : "";
        const filterSec  = partFilterSection ? partFilterSection.value : "";

        document.querySelectorAll(".participation-class-row").forEach(row => {
            const subject = row.dataset.subject || "";
            const section = row.dataset.section || "";
            const matchSubj = !filterSubj || subject === filterSubj;
            const matchSec  = !filterSec  || section === filterSec;
            row.style.display = (matchSubj && matchSec) ? "" : "none";
        });
    }

    if (partFilterSubject) partFilterSubject.addEventListener("change", filterParticipation);
    if (partFilterSection) partFilterSection.addEventListener("change", filterParticipation);

    function buildReportUrl(action) {
        var ta = "";
        var selSubj = document.querySelector("#tab-reports .filter-select");
        var selSec  = document.querySelectorAll("#tab-reports .filter-select")[1];
        return "faculty_dashboard.php?action=" + action + (ta ? "&ta_id=" + ta : "");
    }

    document.getElementById("btn-report-download-main").addEventListener("click", function () {
        var url = buildReportUrl("report_preview");
        var w = window.open(url, "_blank");
        if (w) { w.onload = function() { w.print(); }; }
    });

    document.getElementById("btn-report-preview-main").addEventListener("click", function () {
        var url = buildReportUrl("report_preview");
        document.getElementById("preview-iframe").src = url;
        document.getElementById("modal-report-preview").classList.add("open");
    });

    document.getElementById("btn-report-print-main").addEventListener("click", function () {
        var url = buildReportUrl("report_preview");
        var w = window.open(url, "_blank");
        if (w) { w.onload = function() { w.print(); }; }
    });

    document.getElementById("btn-preview-close").addEventListener("click", function () {
        document.getElementById("modal-report-preview").classList.remove("open");
        document.getElementById("preview-iframe").src = "";
    });

    document.getElementById("modal-report-preview").addEventListener("click", function (e) {
        if (e.target === this) {
            this.classList.remove("open");
            document.getElementById("preview-iframe").src = "";
        }
    });

})();
</script>
</body>
</html>