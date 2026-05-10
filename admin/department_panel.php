<?php
$department_search = clean_string($_GET["q"] ?? "");

$params = [];
$where = "WHERE 1 = 1";

if ($department_search !== "") {
    $where .= "
        AND (
            d.department_code LIKE :department_code_search
            OR d.department_name LIKE :department_name_search
            OR f.full_name LIKE :head_search
        )
    ";

    $params["department_code_search"] = "%" . $department_search . "%";
    $params["department_name_search"] = "%" . $department_search . "%";
    $params["head_search"] = "%" . $department_search . "%";
}

$department_statement = $pdo->prepare("
    SELECT
        d.department_id,
        d.department_code,
        d.department_name,
        d.department_head_faculty_id,
        d.department_status,
        f.full_name AS department_head_name
    FROM department d
    LEFT JOIN faculty f ON f.faculty_id = d.department_head_faculty_id
    $where
    ORDER BY d.department_code
");

$department_statement->execute($params);
$departments = $department_statement->fetchAll();

$department_count = count($departments);

$selected_department = null;

if ($modal_type === "department" && $modal_id > 0) {
    $statement = $pdo->prepare("
        SELECT
            d.*,
            f.full_name AS department_head_name
        FROM department d
        LEFT JOIN faculty f ON f.faculty_id = d.department_head_faculty_id
        WHERE d.department_id = :department_id
        LIMIT 1
    ");

    $statement->execute(["department_id" => $modal_id]);
    $selected_department = $statement->fetch();
}
?>

<div class="panel-top">
    <h2>Colleges (<?php echo $department_count; ?>)</h2>

    <a class="primary-button" href="academic_structure.php?panel=departments&type=department&action=add">
        <?php echo icon_svg("plus"); ?> Add College
    </a>
</div>

<form class="filter-bar department-filter auto-filter-form" method="GET" action="academic_structure.php">
    <input type="hidden" name="panel" value="departments">

    <div class="search-box">
        <?php echo icon_svg("search"); ?>
        <input type="text" name="q" value="<?php echo e($department_search); ?>" placeholder="Search by college code, name, or head...">
    </div>

    <a href="academic_structure.php?panel=departments" class="clear-filter">Clear</a>
</form>

<div class="data-table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>College Code</th>
                <th>College Name</th>
                <th>College Head</th>
                <th>Status</th>
                <th class="actions-column">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($departments) === 0): ?>
                <tr>
                    <td colspan="5" class="empty-message">No colleges found matching your search criteria.</td>
                </tr>
            <?php endif; ?>

            <?php foreach ($departments as $department): ?>
                <tr>
                    <td><strong><?php echo e($department["department_code"]); ?></strong></td>
                    <td><?php echo e($department["department_name"]); ?></td>
                    <td><?php echo e($department["department_head_name"] ?? "No assigned college head"); ?></td>
                    <td>
                        <span class="status-badge <?php echo strtolower((string) $department["department_status"]); ?>">
                            <?php echo e($department["department_status"]); ?>
                        </span>
                    </td>
                    <td>
                        <div class="action-icons">
                            <a class="view" title="View" href="academic_structure.php?panel=departments&type=department&action=view&id=<?php echo (int) $department["department_id"]; ?>">
                                <?php echo icon_svg("eye"); ?>
                            </a>

                            <a class="edit" title="Edit" href="academic_structure.php?panel=departments&type=department&action=edit&id=<?php echo (int) $department["department_id"]; ?>">
                                <?php echo icon_svg("edit"); ?>
                            </a>

                            <form method="POST" action="academic_structure.php?panel=departments" onsubmit="return confirm('Delete this college record?');">
                                <input type="hidden" name="form_action" value="delete_department">
                                <input type="hidden" name="department_id" value="<?php echo (int) $department["department_id"]; ?>">
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
</div>

<?php if ($modal_type === "department" && $modal_action === "add"): ?>
    <div class="modal-overlay">
        <div class="modal-box small">
            <h2>Add New College</h2>

            <form method="POST" action="academic_structure.php?panel=departments">
                <input type="hidden" name="form_action" value="add_department">

                <label>College Code</label>
                <input type="text" name="department_code" placeholder="e.g., CCS" required>

                <label>College Name</label>
                <input type="text" name="department_name" placeholder="e.g., College of Computer Studies" required>

                <label>College Head</label>
                <select name="department_head_faculty_id">
                    <option value="">No assigned college head</option>
                    <?php foreach ($faculty_all as $faculty): ?>
                        <option value="<?php echo (int) $faculty["faculty_id"]; ?>">
                            <?php echo e($faculty["full_name"]); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <div class="modal-actions">
                    <a href="academic_structure.php?panel=departments" class="secondary-button">Cancel</a>
                    <button type="submit" class="dark-button">Add College</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if ($modal_type === "department" && $modal_action === "view" && $selected_department): ?>
    <div class="modal-overlay">
        <div class="modal-box small">
            <h2>College Details</h2>

            <label>College Code</label>
            <input type="text" value="<?php echo e($selected_department["department_code"]); ?>" readonly>

            <label>College Name</label>
            <input type="text" value="<?php echo e($selected_department["department_name"]); ?>" readonly>

            <label>College Head</label>
            <input type="text" value="<?php echo e($selected_department["department_head_name"] ?? "No assigned college head"); ?>" readonly>

            <div class="modal-divider"></div>

            <div class="modal-actions">
                <a href="academic_structure.php?panel=departments" class="secondary-button">Cancel</a>
                <a href="academic_structure.php?panel=departments&type=department&action=edit&id=<?php echo (int) $selected_department["department_id"]; ?>" class="dark-button">Edit Information</a>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($modal_type === "department" && $modal_action === "edit" && $selected_department): ?>
    <div class="modal-overlay">
        <div class="modal-box small">
            <h2>Edit College</h2>

            <form method="POST" action="academic_structure.php?panel=departments">
                <input type="hidden" name="form_action" value="update_department">
                <input type="hidden" name="department_id" value="<?php echo (int) $selected_department["department_id"]; ?>">

                <label>College Code</label>
                <input type="text" name="department_code" value="<?php echo e($selected_department["department_code"]); ?>" required>

                <label>College Name</label>
                <input type="text" name="department_name" value="<?php echo e($selected_department["department_name"]); ?>" required>

                <label>College Head</label>
                <select name="department_head_faculty_id">
                    <option value="">No assigned college head</option>
                    <?php foreach ($faculty_all as $faculty): ?>
                        <option value="<?php echo (int) $faculty["faculty_id"]; ?>" <?php echo (int) $selected_department["department_head_faculty_id"] === (int) $faculty["faculty_id"] ? "selected" : ""; ?>>
                            <?php echo e($faculty["full_name"]); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <div class="modal-actions">
                    <a href="academic_structure.php?panel=departments" class="secondary-button">Cancel</a>
                    <button type="submit" class="dark-button">Update College</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>