<?php
$course_search = clean_string($_GET["q"] ?? "");
$filter_department_id = (int) ($_GET["department_id"] ?? 0);

$params = [];
$where = "WHERE 1 = 1";

if ($course_search !== "") {
    $where .= "
        AND (
            c.course_code LIKE :course_code_search
            OR c.course_name LIKE :course_name_search
            OR c.course_description LIKE :course_description_search
            OR d.department_name LIKE :department_search
        )
    ";

    $params["course_code_search"] = "%" . $course_search . "%";
    $params["course_name_search"] = "%" . $course_search . "%";
    $params["course_description_search"] = "%" . $course_search . "%";
    $params["department_search"] = "%" . $course_search . "%";
}

if ($filter_department_id > 0) {
    $where .= " AND c.department_id = :department_id";
    $params["department_id"] = $filter_department_id;
}

$course_statement = $pdo->prepare("
    SELECT
        c.*,
        d.department_code,
        d.department_name,
        COUNT(DISTINCT s.student_id) AS student_count
    FROM course c
    INNER JOIN department d ON d.department_id = c.department_id
    LEFT JOIN student s ON s.course_id = c.course_id AND s.student_status = 'Active'
    $where
    GROUP BY c.course_id
    ORDER BY c.course_code
");

$course_statement->execute($params);
$courses = $course_statement->fetchAll();

$course_count = count($courses);

$selected_course = null;

if ($modal_type === "course" && $modal_id > 0) {
    $statement = $pdo->prepare("
        SELECT
            c.*,
            d.department_name,
            d.department_code,
            COUNT(DISTINCT s.student_id) AS student_count
        FROM course c
        INNER JOIN department d ON d.department_id = c.department_id
        LEFT JOIN student s ON s.course_id = c.course_id AND s.student_status = 'Active'
        WHERE c.course_id = :course_id
        GROUP BY c.course_id
        LIMIT 1
    ");

    $statement->execute(["course_id" => $modal_id]);
    $selected_course = $statement->fetch();
}
?>

<div class="panel-top">
    <h2>Programs (<?php echo $course_count; ?>)</h2>

    <a class="primary-button" href="academic_structure.php?panel=courses&type=course&action=add">
        <?php echo icon_svg("plus"); ?> Add Program
    </a>
</div>

<form class="filter-bar course-filter auto-filter-form" method="GET" action="academic_structure.php">
    <input type="hidden" name="panel" value="courses">

    <div class="search-box">
        <?php echo icon_svg("search"); ?>
        <input type="text" name="q" value="<?php echo e($course_search); ?>" placeholder="Search by program code or name...">
    </div>

    <select name="department_id">
        <option value="0">All Colleges</option>
        <?php foreach ($departments_all as $department): ?>
            <option value="<?php echo (int) $department["department_id"]; ?>" <?php echo $filter_department_id === (int) $department["department_id"] ? "selected" : ""; ?>>
                <?php echo e($department["department_name"]); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <a href="academic_structure.php?panel=courses" class="clear-filter">Clear</a>
</form>

<div class="record-list">
    <?php if (count($courses) === 0): ?>
        <div class="empty-card">No programs found matching your search criteria.</div>
    <?php endif; ?>

    <?php foreach ($courses as $course): ?>
        <article class="record-card">
            <div>
                <h3>
                    <?php echo e($course["course_code"]); ?>
                    <span class="status-badge <?php echo strtolower((string) $course["course_status"]); ?>">
                        <?php echo e($course["course_status"]); ?>
                    </span>
                </h3>

                <p><?php echo e($course["course_name"]); ?></p>
                <small><?php echo e($course["course_description"]); ?></small>

                <div class="meta-row">
                    <span>College: <?php echo e($course["department_name"]); ?></span>
                    <span>Year Levels: <?php echo (int) $course["number_of_year_level"]; ?></span>
                    <span>Students: <?php echo (int) $course["student_count"]; ?></span>
                </div>
            </div>

            <div class="action-icons">
                <a class="view" href="academic_structure.php?panel=courses&type=course&action=view&id=<?php echo (int) $course["course_id"]; ?>"><?php echo icon_svg("eye"); ?></a>
                <a class="edit" href="academic_structure.php?panel=courses&type=course&action=edit&id=<?php echo (int) $course["course_id"]; ?>"><?php echo icon_svg("edit"); ?></a>

                <form method="POST" action="academic_structure.php?panel=courses" onsubmit="return confirm('Delete this program record?');">
                    <input type="hidden" name="form_action" value="delete_course">
                    <input type="hidden" name="course_id" value="<?php echo (int) $course["course_id"]; ?>">
                    <button type="submit" class="delete"><?php echo icon_svg("trash"); ?></button>
                </form>
            </div>
        </article>
    <?php endforeach; ?>
</div>

<?php if ($modal_type === "course" && $modal_action === "add"): ?>
    <div class="modal-overlay">
        <div class="modal-box medium">
            <h2>Add New Program</h2>

            <form method="POST" action="academic_structure.php?panel=courses">
                <input type="hidden" name="form_action" value="add_course">

                <div class="two-column">
                    <div>
                        <label>Program Code</label>
                        <input type="text" name="course_code" placeholder="e.g., BSIT" required>
                    </div>

                    <div>
                        <label>Status</label>
                        <select name="course_status">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <label>Program Name</label>
                <input type="text" name="course_name" placeholder="e.g., Bachelor of Science in Information Technology" required>

                <label>Description</label>
                <textarea name="course_description" placeholder="Brief description of the program"></textarea>

                <label>College</label>
                <select name="department_id" required>
                    <option value="">Select College</option>
                    <?php foreach ($departments_all as $department): ?>
                        <option value="<?php echo (int) $department["department_id"]; ?>">
                            <?php echo e($department["department_name"]); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label>Number of Year Levels</label>
                <select name="number_of_year_level">
                    <?php for ($i = 1; $i <= 6; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo $i === 4 ? "selected" : ""; ?>>
                            <?php echo $i; ?> Year<?php echo $i > 1 ? "s" : ""; ?>
                        </option>
                    <?php endfor; ?>
                </select>

                <div class="modal-actions">
                    <a href="academic_structure.php?panel=courses" class="secondary-button">Cancel</a>
                    <button type="submit" class="dark-button">Add Program</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if ($modal_type === "course" && $modal_action === "view" && $selected_course): ?>
    <div class="modal-overlay">
        <div class="modal-box medium">
            <h2>Program Details</h2>

            <div class="two-column">
                <div>
                    <label>Program Code</label>
                    <input type="text" value="<?php echo e($selected_course["course_code"]); ?>" readonly>
                </div>

                <div>
                    <label>Status</label>
                    <input type="text" value="<?php echo e($selected_course["course_status"]); ?>" readonly>
                </div>
            </div>

            <label>Program Name</label>
            <input type="text" value="<?php echo e($selected_course["course_name"]); ?>" readonly>

            <label>Description</label>
            <input type="text" value="<?php echo e($selected_course["course_description"]); ?>" readonly>

            <label>College</label>
            <input type="text" value="<?php echo e($selected_course["department_name"]); ?>" readonly>

            <div class="two-column">
                <div>
                    <label>Year Levels</label>
                    <input type="text" value="<?php echo (int) $selected_course["number_of_year_level"]; ?>" readonly>
                </div>

                <div>
                    <label>Student Count</label>
                    <input type="text" value="<?php echo (int) $selected_course["student_count"]; ?>" readonly>
                </div>
            </div>

            <div class="modal-divider"></div>

            <div class="modal-actions">
                <a href="academic_structure.php?panel=courses" class="secondary-button">Cancel</a>
                <a href="academic_structure.php?panel=courses&type=course&action=edit&id=<?php echo (int) $selected_course["course_id"]; ?>" class="dark-button">Edit Information</a>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($modal_type === "course" && $modal_action === "edit" && $selected_course): ?>
    <div class="modal-overlay">
        <div class="modal-box medium">
            <h2>Edit Program</h2>

            <form method="POST" action="academic_structure.php?panel=courses">
                <input type="hidden" name="form_action" value="update_course">
                <input type="hidden" name="course_id" value="<?php echo (int) $selected_course["course_id"]; ?>">

                <div class="two-column">
                    <div>
                        <label>Program Code</label>
                        <input type="text" name="course_code" value="<?php echo e($selected_course["course_code"]); ?>" required>
                    </div>

                    <div>
                        <label>Status</label>
                        <select name="course_status">
                            <option value="Active" <?php echo $selected_course["course_status"] === "Active" ? "selected" : ""; ?>>Active</option>
                            <option value="Inactive" <?php echo $selected_course["course_status"] === "Inactive" ? "selected" : ""; ?>>Inactive</option>
                        </select>
                    </div>
                </div>

                <label>Program Name</label>
                <input type="text" name="course_name" value="<?php echo e($selected_course["course_name"]); ?>" required>

                <label>Description</label>
                <textarea name="course_description"><?php echo e($selected_course["course_description"]); ?></textarea>

                <label>College</label>
                <select name="department_id">
                    <?php foreach ($departments_all as $department): ?>
                        <option value="<?php echo (int) $department["department_id"]; ?>" <?php echo (int) $selected_course["department_id"] === (int) $department["department_id"] ? "selected" : ""; ?>>
                            <?php echo e($department["department_name"]); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label>Number of Year Levels</label>
                <select name="number_of_year_level">
                    <?php for ($i = 1; $i <= 6; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo (int) $selected_course["number_of_year_level"] === $i ? "selected" : ""; ?>>
                            <?php echo $i; ?> Year<?php echo $i > 1 ? "s" : ""; ?>
                        </option>
                    <?php endfor; ?>
                </select>

                <div class="modal-actions">
                    <a href="academic_structure.php?panel=courses" class="secondary-button">Cancel</a>
                    <button type="submit" class="dark-button">Update Program</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>