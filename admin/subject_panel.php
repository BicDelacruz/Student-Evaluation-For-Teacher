<?php
if (!function_exists("year_level_label")) {
    function year_level_label(int $year_level): string
    {
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
}

if (!function_exists("subject_year_level_text")) {
    function subject_year_level_text(mixed $year_level): string
    {
        if ($year_level === null || $year_level === "" || (int) $year_level <= 0) {
            return "Not set";
        }

        return year_level_label((int) $year_level);
    }
}

$subject_search = clean_string($_GET["q"] ?? "");
$filter_department_id = (int) ($_GET["department_id"] ?? 0);
$filter_course_id = (int) ($_GET["course_id"] ?? 0);
$filter_year_level = (int) ($_GET["year_level"] ?? 0);

$params = [];
$where = "WHERE 1 = 1";

if ($subject_search !== "") {
    $where .= "
        AND (
            sub.subject_code LIKE :subject_code_search
            OR sub.subject_title LIKE :subject_title_search
            OR sub.subject_description LIKE :subject_description_search
            OR d.department_name LIKE :department_search
            OR c.course_code LIKE :course_code_search
            OR c.course_name LIKE :course_name_search
        )
    ";

    $params["subject_code_search"] = "%" . $subject_search . "%";
    $params["subject_title_search"] = "%" . $subject_search . "%";
    $params["subject_description_search"] = "%" . $subject_search . "%";
    $params["department_search"] = "%" . $subject_search . "%";
    $params["course_code_search"] = "%" . $subject_search . "%";
    $params["course_name_search"] = "%" . $subject_search . "%";
}

if ($filter_department_id > 0) {
    $where .= " AND sub.department_id = :department_id";
    $params["department_id"] = $filter_department_id;
}

if ($filter_course_id > 0) {
    $where .= " AND cs.course_id = :course_id";
    $params["course_id"] = $filter_course_id;
}

if ($filter_year_level > 0) {
    $where .= " AND cs.year_level = :year_level";
    $params["year_level"] = $filter_year_level;
}

$subject_statement = $pdo->prepare("
    SELECT
        sub.*,
        d.department_name,
        GROUP_CONCAT(DISTINCT c.course_code ORDER BY c.course_code SEPARATOR ', ') AS course_codes,
        GROUP_CONCAT(DISTINCT c.course_name ORDER BY c.course_name SEPARATOR ', ') AS course_names,
        MIN(cs.year_level) AS mapped_year_level,
        MIN(cs.term_name) AS mapped_term_name
    FROM subject sub
    INNER JOIN department d ON d.department_id = sub.department_id
    LEFT JOIN course_subject cs ON cs.subject_id = sub.subject_id
    LEFT JOIN course c ON c.course_id = cs.course_id
    $where
    GROUP BY sub.subject_id
    ORDER BY sub.subject_code
");

$subject_statement->execute($params);
$subjects = $subject_statement->fetchAll();

$subject_count = count($subjects);

$selected_subject = null;
$selected_subject_courses = [];

if ($modal_type === "subject" && $modal_id > 0) {
    $statement = $pdo->prepare("
        SELECT
            sub.*,
            d.department_name,
            GROUP_CONCAT(DISTINCT c.course_code ORDER BY c.course_code SEPARATOR ', ') AS course_codes,
            GROUP_CONCAT(DISTINCT c.course_name ORDER BY c.course_name SEPARATOR ', ') AS course_names,
            MIN(cs.year_level) AS mapped_year_level,
            MIN(cs.term_name) AS mapped_term_name
        FROM subject sub
        INNER JOIN department d ON d.department_id = sub.department_id
        LEFT JOIN course_subject cs ON cs.subject_id = sub.subject_id
        LEFT JOIN course c ON c.course_id = cs.course_id
        WHERE sub.subject_id = :subject_id
        GROUP BY sub.subject_id
        LIMIT 1
    ");

    $statement->execute([
        "subject_id" => $modal_id
    ]);

    $selected_subject = $statement->fetch();

    $course_map_statement = $pdo->prepare("
        SELECT course_id
        FROM course_subject
        WHERE subject_id = :subject_id
        AND course_subject_status = 'Active'
    ");

    $course_map_statement->execute([
        "subject_id" => $modal_id
    ]);

    $selected_subject_courses = array_map("intval", array_column($course_map_statement->fetchAll(), "course_id"));
}
?>

<div class="panel-top">
    <h2>Subjects (<?php echo $subject_count; ?>)</h2>

    <a class="primary-button" href="academic_structure.php?panel=subjects&type=subject&action=add">
        <?php echo icon_svg("plus"); ?> Add Subject
    </a>
</div>

<form class="filter-bar subjects-filter auto-filter-form" method="GET" action="academic_structure.php">
    <input type="hidden" name="panel" value="subjects">

    <div class="search-box">
        <?php echo icon_svg("search"); ?>
        <input
            type="text"
            name="q"
            value="<?php echo e($subject_search); ?>"
            placeholder="Search by subject code or title..."
        >
    </div>

    <select name="department_id">
        <option value="0">All Departments</option>

        <?php foreach ($departments_all as $department): ?>
            <option
                value="<?php echo (int) $department["department_id"]; ?>"
                <?php echo $filter_department_id === (int) $department["department_id"] ? "selected" : ""; ?>
            >
                <?php echo e($department["department_name"]); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="course_id">
        <option value="0">All Courses</option>

        <?php foreach ($courses_all as $course): ?>
            <option
                value="<?php echo (int) $course["course_id"]; ?>"
                <?php echo $filter_course_id === (int) $course["course_id"] ? "selected" : ""; ?>
            >
                <?php echo e($course["course_code"] . " - " . $course["course_name"]); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="year_level">
        <option value="0">All Year Levels</option>

        <?php for ($i = 1; $i <= 6; $i++): ?>
            <option value="<?php echo $i; ?>" <?php echo $filter_year_level === $i ? "selected" : ""; ?>>
                <?php echo year_level_label($i); ?>
            </option>
        <?php endfor; ?>
    </select>

    <a href="academic_structure.php?panel=subjects" class="clear-filter">Clear</a>
</form>

<div class="record-list">
    <?php if (count($subjects) === 0): ?>
        <div class="empty-card">No subjects found matching your search criteria.</div>
    <?php endif; ?>

    <?php foreach ($subjects as $subject): ?>
        <article class="record-card">
            <div>
                <h3>
                    <?php echo e($subject["subject_code"]); ?>

                    <span class="unit-badge">
                        <?php echo e($subject["subject_unit"]); ?> units
                    </span>

                    <?php if (!empty($subject["mapped_term_name"])): ?>
                        <span class="semester-badge">
                            <?php echo e($subject["mapped_term_name"]); ?>
                        </span>
                    <?php endif; ?>

                    <?php if ($subject["subject_type"] === "General Education"): ?>
                        <span class="ge-badge">GE</span>
                    <?php endif; ?>
                </h3>

                <p><?php echo e($subject["subject_title"]); ?></p>
                <small><?php echo e($subject["subject_description"]); ?></small>

                <div class="meta-row">
                    <span>Department: <?php echo e($subject["department_name"]); ?></span>
                    <span>Course: <?php echo e($subject["course_codes"] ?? "No course mapping"); ?></span>
                    <span>Year Level: <?php echo e(subject_year_level_text($subject["mapped_year_level"])); ?></span>
                </div>
            </div>

            <div class="action-icons">
                <a
                    class="view"
                    title="View"
                    href="academic_structure.php?panel=subjects&type=subject&action=view&id=<?php echo (int) $subject["subject_id"]; ?>"
                >
                    <?php echo icon_svg("eye"); ?>
                </a>

                <a
                    class="edit"
                    title="Edit"
                    href="academic_structure.php?panel=subjects&type=subject&action=edit&id=<?php echo (int) $subject["subject_id"]; ?>"
                >
                    <?php echo icon_svg("edit"); ?>
                </a>

                <form method="POST" action="academic_structure.php?panel=subjects" onsubmit="return confirm('Delete this subject record?');">
                    <input type="hidden" name="form_action" value="delete_subject">
                    <input type="hidden" name="subject_id" value="<?php echo (int) $subject["subject_id"]; ?>">

                    <button type="submit" class="delete" title="Delete">
                        <?php echo icon_svg("trash"); ?>
                    </button>
                </form>
            </div>
        </article>
    <?php endforeach; ?>
</div>

<?php if ($modal_type === "subject" && $modal_action === "add"): ?>
    <div class="modal-overlay">
        <div class="modal-box medium tall">
            <h2>Add New Subject</h2>

            <form method="POST" action="academic_structure.php?panel=subjects">
                <input type="hidden" name="form_action" value="add_subject">

                <div class="modal-scroll">
                    <div class="two-column">
                        <div>
                            <label>Subject Code</label>
                            <input type="text" name="subject_code" placeholder="e.g., IT 221" required>
                        </div>

                        <div>
                            <label>Units</label>
                            <input type="number" name="subject_unit" value="3" step="0.5" min="1" max="9" required>
                        </div>
                    </div>

                    <label>Subject Title</label>
                    <input type="text" name="subject_title" placeholder="e.g., Human Computer Interaction" required>

                    <label>Description</label>
                    <textarea name="subject_description" placeholder="Brief description of the subject"></textarea>

                    <label class="checkbox-label">
                        <input type="checkbox" name="is_general_education" value="1">
                        General Education applies to all courses
                    </label>

                    <label>Department</label>
                    <select name="department_id" required>
                        <option value="">Select Department</option>

                        <?php foreach ($departments_all as $department): ?>
                            <option value="<?php echo (int) $department["department_id"]; ?>">
                                <?php echo e($department["department_name"]); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label>Course or Courses</label>
                    <div class="checkbox-scroll">
                        <?php foreach ($courses_all as $course): ?>
                            <label>
                                <input type="checkbox" name="course_ids[]" value="<?php echo (int) $course["course_id"]; ?>">
                                <?php echo e($course["course_code"] . " - " . $course["course_name"]); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <label>Year Level</label>
                    <select name="year_level" required>
                        <?php for ($i = 1; $i <= 6; $i++): ?>
                            <option value="<?php echo $i; ?>">
                                <?php echo year_level_label($i); ?>
                            </option>
                        <?php endfor; ?>
                    </select>

                    <label>Semester</label>
                    <select name="term_name" required>
                        <option value="First Semester">First Semester</option>
                        <option value="Second Semester">Second Semester</option>
                        <option value="Summer">Summer</option>
                    </select>
                </div>

                <div class="modal-actions">
                    <a href="academic_structure.php?panel=subjects" class="secondary-button">Cancel</a>
                    <button type="submit" class="dark-button">Add Subject</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if ($modal_type === "subject" && $modal_action === "view" && $selected_subject): ?>
    <div class="modal-overlay">
        <div class="modal-box medium">
            <h2>Subject Details</h2>

            <div class="two-column">
                <div>
                    <label>Subject Code</label>
                    <input type="text" value="<?php echo e($selected_subject["subject_code"]); ?>" readonly>
                </div>

                <div>
                    <label>Units</label>
                    <input type="text" value="<?php echo e($selected_subject["subject_unit"]); ?>" readonly>
                </div>
            </div>

            <label>Subject Title</label>
            <input type="text" value="<?php echo e($selected_subject["subject_title"]); ?>" readonly>

            <label>Description</label>
            <input type="text" value="<?php echo e($selected_subject["subject_description"]); ?>" readonly>

            <label>Department</label>
            <input type="text" value="<?php echo e($selected_subject["department_name"]); ?>" readonly>

            <div class="two-column">
                <div>
                    <label>Course or Courses</label>
                    <input type="text" value="<?php echo e($selected_subject["course_codes"] ?? "No course mapping"); ?>" readonly>
                </div>

                <div>
                    <label>Year Level</label>
                    <input type="text" value="<?php echo e(subject_year_level_text($selected_subject["mapped_year_level"])); ?>" readonly>
                </div>
            </div>

            <label>Semester</label>
            <input type="text" value="<?php echo e($selected_subject["mapped_term_name"] ?? "Not set"); ?>" readonly>

            <div class="modal-divider"></div>

            <div class="modal-actions">
                <a href="academic_structure.php?panel=subjects" class="secondary-button">Cancel</a>

                <a
                    href="academic_structure.php?panel=subjects&type=subject&action=edit&id=<?php echo (int) $selected_subject["subject_id"]; ?>"
                    class="dark-button"
                >
                    Edit Information
                </a>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($modal_type === "subject" && $modal_action === "edit" && $selected_subject): ?>
    <div class="modal-overlay">
        <div class="modal-box medium tall">
            <h2>Edit Subject</h2>

            <form method="POST" action="academic_structure.php?panel=subjects">
                <input type="hidden" name="form_action" value="update_subject">
                <input type="hidden" name="subject_id" value="<?php echo (int) $selected_subject["subject_id"]; ?>">

                <div class="modal-scroll">
                    <div class="two-column">
                        <div>
                            <label>Subject Code</label>
                            <input type="text" name="subject_code" value="<?php echo e($selected_subject["subject_code"]); ?>" required>
                        </div>

                        <div>
                            <label>Units</label>
                            <input
                                type="number"
                                name="subject_unit"
                                value="<?php echo e($selected_subject["subject_unit"]); ?>"
                                step="0.5"
                                min="1"
                                max="9"
                                required
                            >
                        </div>
                    </div>

                    <label>Subject Title</label>
                    <input type="text" name="subject_title" value="<?php echo e($selected_subject["subject_title"]); ?>" required>

                    <label>Description</label>
                    <textarea name="subject_description"><?php echo e($selected_subject["subject_description"]); ?></textarea>

                    <label class="checkbox-label">
                        <input
                            type="checkbox"
                            name="is_general_education"
                            value="1"
                            <?php echo $selected_subject["subject_type"] === "General Education" ? "checked" : ""; ?>
                        >
                        General Education applies to all courses
                    </label>

                    <label>Department</label>
                    <select name="department_id" required>
                        <?php foreach ($departments_all as $department): ?>
                            <option
                                value="<?php echo (int) $department["department_id"]; ?>"
                                <?php echo (int) $selected_subject["department_id"] === (int) $department["department_id"] ? "selected" : ""; ?>
                            >
                                <?php echo e($department["department_name"]); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label>Course or Courses</label>
                    <div class="checkbox-scroll">
                        <?php foreach ($courses_all as $course): ?>
                            <label>
                                <input
                                    type="checkbox"
                                    name="course_ids[]"
                                    value="<?php echo (int) $course["course_id"]; ?>"
                                    <?php echo in_array((int) $course["course_id"], $selected_subject_courses, true) ? "checked" : ""; ?>
                                >
                                <?php echo e($course["course_code"] . " - " . $course["course_name"]); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <label>Year Level</label>
                    <select name="year_level" required>
                        <?php for ($i = 1; $i <= 6; $i++): ?>
                            <option
                                value="<?php echo $i; ?>"
                                <?php echo (int) ($selected_subject["mapped_year_level"] ?? 1) === $i ? "selected" : ""; ?>
                            >
                                <?php echo year_level_label($i); ?>
                            </option>
                        <?php endfor; ?>
                    </select>

                    <label>Semester</label>
                    <select name="term_name" required>
                        <?php foreach (["First Semester", "Second Semester", "Summer"] as $semester): ?>
                            <option
                                value="<?php echo e($semester); ?>"
                                <?php echo (string) $selected_subject["mapped_term_name"] === $semester ? "selected" : ""; ?>
                            >
                                <?php echo e($semester); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label>Status</label>
                    <select name="subject_status" required>
                        <option value="Active" <?php echo $selected_subject["subject_status"] === "Active" ? "selected" : ""; ?>>
                            Active
                        </option>

                        <option value="Inactive" <?php echo $selected_subject["subject_status"] === "Inactive" ? "selected" : ""; ?>>
                            Inactive
                        </option>
                    </select>
                </div>

                <div class="modal-actions">
                    <a href="academic_structure.php?panel=subjects" class="secondary-button">Cancel</a>
                    <button type="submit" class="dark-button">Update Subject</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>