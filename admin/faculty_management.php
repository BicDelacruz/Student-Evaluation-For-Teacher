<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

require_admin_verified();

$facultyRecords = load_faculty();
$filters = [
    'search' => trim((string) ($_GET['search'] ?? '')),
    'department' => trim((string) ($_GET['department'] ?? '')),
    'position' => trim((string) ($_GET['position'] ?? '')),
    'status' => trim((string) ($_GET['status'] ?? '')),
];
$queryString = http_build_query(array_filter($filters, static fn (string $value): bool => $value !== ''));
$selfAction = 'faculty_management.php' . ($queryString !== '' ? '?' . $queryString : '');

$departments = unique_values($facultyRecords, 'department');
$positions = unique_values($facultyRecords, 'position');
$semesterOptions = ['1st Semester', '2nd Semester', 'Summer'];

$successMessage = get_flash('success');
$errorMessage = get_flash('error');
$pageErrors = [];
$openModal = null;
$addFormValues = [
    'faculty_id' => '',
    'email' => '',
    'first_name' => '',
    'middle_name' => '',
    'last_name' => '',
    'department' => '',
    'position' => '',
    'subjects_assigned' => '',
    'semester' => '',
    'academic_year' => '',
    'password' => '',
    'status' => 'active',
];
$editFormValues = $addFormValues + ['original_faculty_id' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string) ($_POST['action'] ?? ''));

    if ($action === 'add') {
        $addFormValues = normalize_faculty_payload($_POST);
        $pageErrors = validate_faculty_payload($addFormValues, $facultyRecords);

        if (trim($addFormValues['password']) === '') {
            $pageErrors[] = 'Password is required when adding a faculty member.';
        }

        if ($pageErrors === []) {
            $facultyRecords[] = build_faculty_record($addFormValues);
            save_faculty($facultyRecords);
            set_flash('success', 'Faculty record added successfully.');
            redirect($selfAction);
        }

        $openModal = 'add';
    } elseif ($action === 'update') {
        $originalFacultyId = trim((string) ($_POST['original_faculty_id'] ?? ''));
        $facultyIndex = faculty_index_by_id($facultyRecords, $originalFacultyId);

        if ($facultyIndex === null) {
            set_flash('error', 'The selected faculty record could not be found.');
            redirect($selfAction);
        }

        $editFormValues = normalize_faculty_payload($_POST);
        $editFormValues['original_faculty_id'] = $originalFacultyId;
        $editFormValues['status'] = trim((string) ($_POST['status'] ?? ($facultyRecords[$facultyIndex]['status'] ?? 'active')));
        $pageErrors = validate_faculty_payload($editFormValues, $facultyRecords, $originalFacultyId);

        if ($pageErrors === []) {
            $facultyRecords[$facultyIndex] = build_faculty_record($editFormValues, $facultyRecords[$facultyIndex]);
            save_faculty($facultyRecords);
            set_flash('success', 'Faculty record updated successfully.');
            redirect($selfAction);
        }

        $openModal = 'edit';
    } elseif (in_array($action, ['activate', 'deactivate', 'delete'], true)) {
        $facultyId = trim((string) ($_POST['faculty_id'] ?? ''));
        $facultyIndex = faculty_index_by_id($facultyRecords, $facultyId);

        if ($facultyIndex !== null) {
            if ($action === 'delete') {
                array_splice($facultyRecords, $facultyIndex, 1);
                set_flash('success', 'Faculty record deleted successfully.');
            } else {
                $facultyRecords[$facultyIndex]['status'] = $action === 'deactivate' ? 'inactive' : 'active';
                set_flash('success', 'Faculty account status updated successfully.');
            }

            save_faculty($facultyRecords);
        } else {
            set_flash('error', 'The selected faculty record could not be found.');
        }

        redirect($selfAction);
    }
}

$filteredFaculty = filter_faculty($facultyRecords, $filters);
$displayedCount = count($filteredFaculty);
$totalCount = count($facultyRecords);
$filterSummary = active_filter_summary($filters);

render_admin_layout_start(
    'Faculty Management',
    'faculty',
    'Faculty Management',
    'Manage faculty accounts and records'
);
?>
<div class="filter-toolbar">
    <div></div>
    <button type="button" class="add-button" data-open-modal="#addFacultyModal">
        <span class="button-icon"><?= admin_icon('plus') ?></span>
        Add Faculty
    </button>
</div>

<?php if ($successMessage !== null): ?>
    <div class="alert alert-success"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($errorMessage !== null): ?>
    <div class="alert alert-error"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($pageErrors !== [] && $openModal === null): ?>
    <div class="alert alert-error"><?= htmlspecialchars(implode(' ', $pageErrors), ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<section class="filter-panel">
    <form method="get" action="faculty_management.php" data-filter-form>
        <div class="search-row">
            <span class="search-icon-inline"><?= admin_icon('search') ?></span>
            <input type="text" name="search" value="<?= htmlspecialchars($filters['search'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Search by ID, name, email, or department...">
        </div>

        <div class="filters-grid">
            <select name="department">
                <option value="">All Departments</option>
                <?php foreach ($departments as $department): ?>
                    <option value="<?= htmlspecialchars($department, ENT_QUOTES, 'UTF-8') ?>" <?= $filters['department'] === $department ? 'selected' : '' ?>><?= htmlspecialchars($department, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
            <select name="position">
                <option value="">All Positions</option>
                <?php foreach ($positions as $position): ?>
                    <option value="<?= htmlspecialchars($position, ENT_QUOTES, 'UTF-8') ?>" <?= $filters['position'] === $position ? 'selected' : '' ?>><?= htmlspecialchars($position, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status">
                <option value="">All Status</option>
                <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $filters['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>

        <div class="filter-meta">
            <div>Showing <?= $displayedCount ?> of <?= $totalCount ?> faculty members</div>
            <div class="filter-status">
                <span class="sidebar-icon"><?= admin_icon('filter') ?></span>
                <span><?= htmlspecialchars($filterSummary, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>
    </form>
</section>

<section class="table-card">
    <?php if ($displayedCount === 0): ?>
        <div class="empty-state">No faculty records matched your current search and filter settings.</div>
    <?php else: ?>
        <table class="students-table">
            <thead>
                <tr>
                    <th>Faculty ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Department</th>
                    <th>Position</th>
                    <th>Subjects Assigned</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($filteredFaculty as $faculty): ?>
                    <?php
                    $facultyJson = htmlspecialchars(json_encode($faculty, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');
                    $isInactive = ($faculty['status'] ?? '') === 'inactive';
                    ?>
                    <tr>
                        <td data-label="Faculty ID" class="table-id"><?= htmlspecialchars((string) $faculty['faculty_id'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="Name"><?= htmlspecialchars(faculty_full_name($faculty), ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="Email"><?= htmlspecialchars((string) $faculty['email'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="Department"><?= htmlspecialchars((string) $faculty['department'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="Position"><?= htmlspecialchars((string) $faculty['position'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="Subjects Assigned"><?= htmlspecialchars((string) $faculty['subjects_assigned'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="Status">
                            <span class="status-badge <?= $isInactive ? 'inactive' : 'active' ?>"><?= htmlspecialchars((string) $faculty['status'], ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                        <td data-label="Actions">
                            <div class="action-buttons">
                                <button type="button" class="icon-button view" title="View Faculty" data-open-modal="#viewFacultyModal" data-faculty-view data-faculty-json="<?= $facultyJson ?>">
                                    <?= admin_icon('view') ?>
                                </button>
                                <button type="button" class="icon-button edit" title="Edit Faculty" data-open-modal="#editFacultyModal" data-faculty-edit data-faculty-json="<?= $facultyJson ?>">
                                    <?= admin_icon('edit') ?>
                                </button>
                                <form method="post" action="<?= htmlspecialchars($selfAction, ENT_QUOTES, 'UTF-8') ?>" class="inline-action-form">
                                    <input type="hidden" name="faculty_id" value="<?= htmlspecialchars((string) $faculty['faculty_id'], ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="action" value="<?= $isInactive ? 'activate' : 'deactivate' ?>">
                                    <button type="submit" class="icon-submit <?= $isInactive ? 'activate' : 'suspend' ?>" title="<?= $isInactive ? 'Activate Faculty' : 'Deactivate Faculty' ?>" data-confirm-message="<?= $isInactive ? 'Activate this inactive faculty account?' : 'Deactivate this active faculty account?' ?>">
                                        <?= admin_icon($isInactive ? 'activate' : 'suspend') ?>
                                    </button>
                                </form>
                                <form method="post" action="<?= htmlspecialchars($selfAction, ENT_QUOTES, 'UTF-8') ?>" class="inline-action-form">
                                    <input type="hidden" name="faculty_id" value="<?= htmlspecialchars((string) $faculty['faculty_id'], ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="icon-submit delete" title="Delete Faculty" data-confirm-message="Delete this faculty record permanently?">
                                        <?= admin_icon('delete') ?>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<div class="modal<?= $openModal === 'add' ? ' is-visible' : '' ?>" id="addFacultyModal">
    <div class="modal-card">
        <h3>Add New Faculty</h3>
        <?php if ($openModal === 'add' && $pageErrors !== []): ?>
            <div class="alert alert-error"><?= htmlspecialchars(implode(' ', $pageErrors), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <form method="post" action="<?= htmlspecialchars($selfAction, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="add">
            <div class="modal-grid">
                <?php render_faculty_form_fields($addFormValues, $departments, $positions, $semesterOptions, false); ?>
            </div>
            <div class="modal-actions">
                <button type="button" class="secondary-button" data-close-modal>Cancel</button>
                <button type="submit" class="primary-button">Add Faculty</button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="viewFacultyModal">
    <div class="modal-card">
        <h3>Faculty Record Details</h3>
        <div class="modal-grid">
            <?php render_faculty_view_fields(); ?>
        </div>
        <div class="modal-actions">
            <button type="button" class="secondary-button" data-close-modal>Cancel</button>
            <button type="button" class="primary-button" data-view-faculty-edit-trigger>Edit Information</button>
        </div>
    </div>
</div>

<div class="modal<?= $openModal === 'edit' ? ' is-visible' : '' ?>" id="editFacultyModal">
    <div class="modal-card">
        <h3>Edit Faculty</h3>
        <?php if ($openModal === 'edit' && $pageErrors !== []): ?>
            <div class="alert alert-error"><?= htmlspecialchars(implode(' ', $pageErrors), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <form method="post" action="<?= htmlspecialchars($selfAction, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="original_faculty_id" value="<?= htmlspecialchars((string) $editFormValues['original_faculty_id'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="status" value="<?= htmlspecialchars((string) $editFormValues['status'], ENT_QUOTES, 'UTF-8') ?>">
            <div class="modal-grid">
                <?php render_faculty_form_fields($editFormValues, $departments, $positions, $semesterOptions, true); ?>
            </div>
            <div class="modal-actions">
                <button type="button" class="secondary-button" data-close-modal>Cancel</button>
                <button type="submit" class="primary-button">Update Faculty</button>
            </div>
        </form>
    </div>
</div>

<?php render_admin_layout_end(); ?>

<?php
function render_faculty_form_fields(array $values, array $departments, array $positions, array $semesterOptions, bool $isEdit): void
{
    ?>
    <div class="modal-field">
        <label for="<?= $isEdit ? 'edit' : 'add' ?>_faculty_id">Faculty ID</label>
        <input id="<?= $isEdit ? 'edit' : 'add' ?>_faculty_id" type="text" name="faculty_id" value="<?= htmlspecialchars((string) $values['faculty_id'], ENT_QUOTES, 'UTF-8') ?>" required data-faculty-field="faculty_id" placeholder="e.g., FAC0001">
    </div>
    <div class="modal-field">
        <label for="<?= $isEdit ? 'edit' : 'add' ?>_email">Email</label>
        <input id="<?= $isEdit ? 'edit' : 'add' ?>_email" type="email" name="email" value="<?= htmlspecialchars((string) $values['email'], ENT_QUOTES, 'UTF-8') ?>" required data-faculty-field="email" placeholder="e.g., faculty@eastgate.edu">
    </div>
    <div class="modal-field">
        <label for="<?= $isEdit ? 'edit' : 'add' ?>_first_name">First Name</label>
        <input id="<?= $isEdit ? 'edit' : 'add' ?>_first_name" type="text" name="first_name" value="<?= htmlspecialchars((string) $values['first_name'], ENT_QUOTES, 'UTF-8') ?>" required data-faculty-field="first_name" placeholder="e.g., Julius">
    </div>
    <div class="modal-field">
        <label for="<?= $isEdit ? 'edit' : 'add' ?>_middle_name">Middle Name</label>
        <input id="<?= $isEdit ? 'edit' : 'add' ?>_middle_name" type="text" name="middle_name" value="<?= htmlspecialchars((string) $values['middle_name'], ENT_QUOTES, 'UTF-8') ?>" data-faculty-field="middle_name" placeholder="e.g., R.">
    </div>
    <div class="modal-field full">
        <label for="<?= $isEdit ? 'edit' : 'add' ?>_last_name">Last Name</label>
        <input id="<?= $isEdit ? 'edit' : 'add' ?>_last_name" type="text" name="last_name" value="<?= htmlspecialchars((string) $values['last_name'], ENT_QUOTES, 'UTF-8') ?>" required data-faculty-field="last_name" placeholder="e.g., Samonte">
    </div>
    <div class="modal-field">
        <label for="<?= $isEdit ? 'edit' : 'add' ?>_department">Department</label>
        <select id="<?= $isEdit ? 'edit' : 'add' ?>_department" name="department" required data-faculty-field="department">
            <option value="">Select Department</option>
            <?php foreach ($departments as $department): ?>
                <option value="<?= htmlspecialchars($department, ENT_QUOTES, 'UTF-8') ?>" <?= $values['department'] === $department ? 'selected' : '' ?>><?= htmlspecialchars($department, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="modal-field">
        <label for="<?= $isEdit ? 'edit' : 'add' ?>_position">Position</label>
        <select id="<?= $isEdit ? 'edit' : 'add' ?>_position" name="position" required data-faculty-field="position">
            <option value="">Select Position</option>
            <?php foreach ($positions as $position): ?>
                <option value="<?= htmlspecialchars($position, ENT_QUOTES, 'UTF-8') ?>" <?= $values['position'] === $position ? 'selected' : '' ?>><?= htmlspecialchars($position, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="modal-field">
        <label for="<?= $isEdit ? 'edit' : 'add' ?>_subjects_assigned">Subjects Assigned</label>
        <input id="<?= $isEdit ? 'edit' : 'add' ?>_subjects_assigned" type="number" min="0" name="subjects_assigned" value="<?= htmlspecialchars((string) $values['subjects_assigned'], ENT_QUOTES, 'UTF-8') ?>" required data-faculty-field="subjects_assigned" placeholder="e.g., 3">
    </div>
    <div class="modal-field">
        <label for="<?= $isEdit ? 'edit' : 'add' ?>_semester">Semester</label>
        <select id="<?= $isEdit ? 'edit' : 'add' ?>_semester" name="semester" required data-faculty-field="semester">
            <option value="">Select Semester</option>
            <?php foreach ($semesterOptions as $semester): ?>
                <option value="<?= htmlspecialchars($semester, ENT_QUOTES, 'UTF-8') ?>" <?= $values['semester'] === $semester ? 'selected' : '' ?>><?= htmlspecialchars($semester, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="modal-field">
        <label for="<?= $isEdit ? 'edit' : 'add' ?>_academic_year">Academic Year</label>
        <input id="<?= $isEdit ? 'edit' : 'add' ?>_academic_year" type="text" name="academic_year" value="<?= htmlspecialchars((string) $values['academic_year'], ENT_QUOTES, 'UTF-8') ?>" required data-faculty-field="academic_year" placeholder="e.g., 2025-2026">
    </div>
    <div class="modal-field">
        <label for="<?= $isEdit ? 'edit' : 'add' ?>_password">Password</label>
        <input id="<?= $isEdit ? 'edit' : 'add' ?>_password" type="password" name="password" placeholder="<?= $isEdit ? 'Leave blank to keep current' : 'Enter password' ?>">
    </div>
    <?php
}

function render_faculty_view_fields(): void
{
    $fields = [
        ['label' => 'Faculty ID', 'name' => 'faculty_id'],
        ['label' => 'Email', 'name' => 'email'],
        ['label' => 'First Name', 'name' => 'first_name'],
        ['label' => 'Middle Name', 'name' => 'middle_name'],
        ['label' => 'Last Name', 'name' => 'last_name'],
        ['label' => 'Department', 'name' => 'department', 'full' => true],
        ['label' => 'Position', 'name' => 'position'],
        ['label' => 'Subjects Assigned', 'name' => 'subjects_assigned'],
        ['label' => 'Semester', 'name' => 'semester'],
        ['label' => 'Academic Year', 'name' => 'academic_year'],
    ];

    foreach ($fields as $field) {
        $fullClass = !empty($field['full']) ? ' full' : '';
        ?>
        <div class="modal-field<?= $fullClass ?>">
            <label><?= htmlspecialchars($field['label'], ENT_QUOTES, 'UTF-8') ?></label>
            <input type="text" data-faculty-field="<?= htmlspecialchars($field['name'], ENT_QUOTES, 'UTF-8') ?>" readonly>
        </div>
        <?php
    }
    ?>
    <div class="modal-field full">
        <label>Status</label>
        <div class="modal-status-field">
            <span class="status-badge active" data-view-faculty-status>active</span>
        </div>
    </div>
    <?php
}
