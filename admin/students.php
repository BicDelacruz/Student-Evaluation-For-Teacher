<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

require_admin_verified();

$students = load_students();
$filters = selected_filters_from_request();
$queryString = http_build_query(array_filter($filters, static fn (string $value): bool => $value !== ''));
$selfAction = 'students.php' . ($queryString !== '' ? '?' . $queryString : '');

$catalog = section_catalog();
$departments = array_values(array_unique(array_map(static fn (array $item): string => $item['department'], $catalog)));
sort($departments);
$courses = array_values(array_unique(array_map(static fn (array $item): string => $item['course'], $catalog)));
sort($courses);
$yearLevels = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
$sections = array_keys($catalog);
sort($sections);

$successMessage = get_flash('success');
$errorMessage = get_flash('error');
$pageErrors = [];
$openModal = null;
$addFormValues = [
    'student_id' => '',
    'email' => '',
    'first_name' => '',
    'middle_name' => '',
    'last_name' => '',
    'department' => '',
    'course' => '',
    'year_level' => '',
    'section' => '',
    'semester' => '',
    'academic_year' => '',
    'password' => '',
    'status' => 'active',
];
$editFormValues = $addFormValues + ['original_student_id' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string) ($_POST['action'] ?? ''));

    if ($action === 'add') {
        $addFormValues = normalize_student_payload($_POST);
        $pageErrors = validate_student_payload($addFormValues, $students);

        if (trim($addFormValues['password']) === '') {
            $pageErrors[] = 'Password is required when adding a student.';
        }

        if ($pageErrors === []) {
            $students[] = build_student_record($addFormValues);
            save_students($students);
            set_flash('success', 'Student record added successfully.');
            redirect($selfAction);
        }

        $openModal = 'add';
    } elseif ($action === 'update') {
        $originalStudentId = trim((string) ($_POST['original_student_id'] ?? ''));
        $studentIndex = student_index_by_id($students, $originalStudentId);

        if ($studentIndex === null) {
            set_flash('error', 'The selected student record could not be found.');
            redirect($selfAction);
        }

        $editFormValues = normalize_student_payload($_POST);
        $editFormValues['original_student_id'] = $originalStudentId;
        $editFormValues['status'] = (string) ($students[$studentIndex]['status'] ?? 'active');
        $pageErrors = validate_student_payload($editFormValues, $students, $originalStudentId);

        if ($pageErrors === []) {
            $students[$studentIndex] = build_student_record($editFormValues, $students[$studentIndex]);
            save_students($students);
            set_flash('success', 'Student record updated successfully.');
            redirect($selfAction);
        }

        $openModal = 'edit';
    } elseif (in_array($action, ['suspend', 'activate', 'delete'], true)) {
        $studentId = trim((string) ($_POST['student_id'] ?? ''));
        $studentIndex = student_index_by_id($students, $studentId);

        if ($studentIndex !== null) {
            if ($action === 'delete') {
                array_splice($students, $studentIndex, 1);
                set_flash('success', 'Student record deleted successfully.');
            } else {
                $students[$studentIndex]['status'] = $action === 'suspend' ? 'suspended' : 'active';
                set_flash('success', 'Student account status updated successfully.');
            }

            save_students($students);
        } else {
            set_flash('error', 'The selected student record could not be found.');
        }

        redirect($selfAction);
    }
}

$filteredStudents = filter_students($students, $filters);
$displayedCount = count($filteredStudents);
$totalCount = count($students);
$filterSummary = active_filter_summary($filters);

render_admin_layout_start(
    'Student Management',
    'students',
    'Student Management',
    'Manage student accounts and records'
);
?>
<div class="filter-toolbar">
    <div></div>
    <button type="button" class="add-button" data-open-modal="#addStudentModal">
        <span class="button-icon"><?= admin_icon('plus') ?></span>
        Add Student
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
    <form method="get" action="students.php" data-filter-form>
        <div class="search-row">
            <span class="search-icon-inline"><?= admin_icon('search') ?></span>
            <input type="text" name="search" value="<?= htmlspecialchars($filters['search'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Search by ID, name, or email...">
        </div>

        <div class="filters-grid">
            <select name="course">
                <option value="">All Courses</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?= htmlspecialchars($course, ENT_QUOTES, 'UTF-8') ?>" <?= $filters['course'] === $course ? 'selected' : '' ?>><?= htmlspecialchars($course, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
            <select name="year_level">
                <option value="">All Year Levels</option>
                <?php foreach ($yearLevels as $yearLevel): ?>
                    <option value="<?= htmlspecialchars($yearLevel, ENT_QUOTES, 'UTF-8') ?>" <?= $filters['year_level'] === $yearLevel ? 'selected' : '' ?>><?= htmlspecialchars($yearLevel, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
            <select name="section">
                <option value="">All Sections</option>
                <?php foreach ($sections as $section): ?>
                    <option value="<?= htmlspecialchars($section, ENT_QUOTES, 'UTF-8') ?>" <?= $filters['section'] === $section ? 'selected' : '' ?>><?= htmlspecialchars($section, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status">
                <option value="">All Status</option>
                <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="suspended" <?= $filters['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
            </select>
        </div>

        <div class="filter-meta">
            <div>Showing <?= $displayedCount ?> of <?= $totalCount ?> students</div>
            <div class="filter-status">
                <span class="sidebar-icon"><?= admin_icon('filter') ?></span>
                <span><?= htmlspecialchars($filterSummary, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>
    </form>
</section>

<section class="table-card">
    <?php if ($displayedCount === 0): ?>
        <div class="empty-state">No students matched your current search and filter settings.</div>
    <?php else: ?>
        <table class="students-table">
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Course</th>
                    <th>Year Level</th>
                    <th>Section</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($filteredStudents as $student): ?>
                    <?php
                    $studentJson = htmlspecialchars(json_encode($student, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');
                    $isSuspended = ($student['status'] ?? '') === 'suspended';
                    ?>
                    <tr>
                        <td data-label="Student ID" class="table-id"><?= htmlspecialchars((string) $student['student_id'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="Name"><?= htmlspecialchars(student_full_name($student), ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="Email"><?= htmlspecialchars((string) $student['email'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="Course"><?= htmlspecialchars((string) $student['course'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="Year Level"><?= htmlspecialchars((string) $student['year_level'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="Section"><?= htmlspecialchars((string) $student['section'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="Status">
                            <span class="status-badge <?= $isSuspended ? 'suspended' : 'active' ?>"><?= htmlspecialchars((string) $student['status'], ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                        <td data-label="Actions">
                            <div class="action-buttons">
                                <button type="button" class="icon-button view" title="View Student" data-open-modal="#viewStudentModal" data-student-view data-student-json="<?= $studentJson ?>">
                                    <?= admin_icon('view') ?>
                                </button>
                                <button type="button" class="icon-button edit" title="Edit Student" data-open-modal="#editStudentModal" data-student-edit data-student-json="<?= $studentJson ?>">
                                    <?= admin_icon('edit') ?>
                                </button>
                                <form method="post" action="<?= htmlspecialchars($selfAction, ENT_QUOTES, 'UTF-8') ?>" class="inline-action-form">
                                    <input type="hidden" name="student_id" value="<?= htmlspecialchars((string) $student['student_id'], ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="action" value="<?= $isSuspended ? 'activate' : 'suspend' ?>">
                                    <button type="submit" class="icon-submit <?= $isSuspended ? 'activate' : 'suspend' ?>" title="<?= $isSuspended ? 'Activate Student' : 'Suspend Student' ?>" data-confirm-message="<?= $isSuspended ? 'Activate this suspended student account?' : 'Suspend this active student account?' ?>">
                                        <?= admin_icon($isSuspended ? 'activate' : 'suspend') ?>
                                    </button>
                                </form>
                                <form method="post" action="<?= htmlspecialchars($selfAction, ENT_QUOTES, 'UTF-8') ?>" class="inline-action-form">
                                    <input type="hidden" name="student_id" value="<?= htmlspecialchars((string) $student['student_id'], ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="icon-submit delete" title="Delete Student" data-confirm-message="Delete this student record permanently?">
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

<div class="modal<?= $openModal === 'add' ? ' is-visible' : '' ?>" id="addStudentModal">
    <div class="modal-card">
        <h3>Add New Student</h3>
        <?php if ($openModal === 'add' && $pageErrors !== []): ?>
            <div class="alert alert-error"><?= htmlspecialchars(implode(' ', $pageErrors), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <form method="post" action="<?= htmlspecialchars($selfAction, ENT_QUOTES, 'UTF-8') ?>" data-student-form>
            <input type="hidden" name="action" value="add">
            <div class="modal-grid">
                <?php render_student_form_fields($addFormValues, $departments, $courses, $yearLevels, $sections, false); ?>
            </div>
            <div class="modal-actions">
                <button type="button" class="secondary-button" data-close-modal>Cancel</button>
                <button type="submit" class="primary-button">Add Student</button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="viewStudentModal">
    <div class="modal-card">
        <h3>Student Record Details</h3>
        <div class="modal-grid">
            <?php render_student_view_fields(); ?>
        </div>
        <div class="modal-actions">
            <button type="button" class="secondary-button" data-close-modal>Cancel</button>
            <button type="button" class="primary-button" data-view-edit-trigger>Edit Information</button>
        </div>
    </div>
</div>

<div class="modal<?= $openModal === 'edit' ? ' is-visible' : '' ?>" id="editStudentModal">
    <div class="modal-card">
        <h3>Edit Student</h3>
        <?php if ($openModal === 'edit' && $pageErrors !== []): ?>
            <div class="alert alert-error"><?= htmlspecialchars(implode(' ', $pageErrors), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <form method="post" action="<?= htmlspecialchars($selfAction, ENT_QUOTES, 'UTF-8') ?>" data-student-form>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="original_student_id" value="<?= htmlspecialchars((string) $editFormValues['original_student_id'], ENT_QUOTES, 'UTF-8') ?>">
            <div class="modal-grid">
                <?php render_student_form_fields($editFormValues, $departments, $courses, $yearLevels, $sections, true); ?>
            </div>
            <div class="modal-actions">
                <button type="button" class="secondary-button" data-close-modal>Cancel</button>
                <button type="submit" class="primary-button">Update Student</button>
            </div>
        </form>
    </div>
</div>

<script>
window.ADMIN_SECTION_META = <?= json_encode($catalog, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<?php render_admin_layout_end(); ?>

<?php
function render_student_form_fields(array $values, array $departments, array $courses, array $yearLevels, array $sections, bool $isEdit): void
{
    ?>
    <div class="modal-field">
        <label for="<?= $isEdit ? 'edit' : 'add' ?>_student_id">Student ID</label>
        <input id="<?= $isEdit ? 'edit' : 'add' ?>_student_id" type="text" name="student_id" value="<?= htmlspecialchars((string) $values['student_id'], ENT_QUOTES, 'UTF-8') ?>" required data-field="student_id" placeholder="e.g., 25-000001">
    </div>
    <div class="modal-field">
        <label for="<?= $isEdit ? 'edit' : 'add' ?>_email">Email</label>
        <input id="<?= $isEdit ? 'edit' : 'add' ?>_email" type="email" name="email" value="<?= htmlspecialchars((string) $values['email'], ENT_QUOTES, 'UTF-8') ?>" required data-field="email" placeholder="e.g., student@eastgate.edu">
    </div>
    <div class="modal-field">
        <label for="<?= $isEdit ? 'edit' : 'add' ?>_first_name">First Name</label>
        <input id="<?= $isEdit ? 'edit' : 'add' ?>_first_name" type="text" name="first_name" value="<?= htmlspecialchars((string) $values['first_name'], ENT_QUOTES, 'UTF-8') ?>" required data-field="first_name" placeholder="e.g., Juan">
    </div>
    <div class="modal-field">
        <label for="<?= $isEdit ? 'edit' : 'add' ?>_middle_name">Middle Name</label>
        <input id="<?= $isEdit ? 'edit' : 'add' ?>_middle_name" type="text" name="middle_name" value="<?= htmlspecialchars((string) $values['middle_name'], ENT_QUOTES, 'UTF-8') ?>" data-field="middle_name" placeholder="e.g., D.">
    </div>
    <div class="modal-field full">
        <label for="<?= $isEdit ? 'edit' : 'add' ?>_last_name">Last Name</label>
        <input id="<?= $isEdit ? 'edit' : 'add' ?>_last_name" type="text" name="last_name" value="<?= htmlspecialchars((string) $values['last_name'], ENT_QUOTES, 'UTF-8') ?>" required data-field="last_name" placeholder="e.g., Dela Cruz">
    </div>
    <div class="modal-field full">
        <label for="<?= $isEdit ? 'edit' : 'add' ?>_department">Department</label>
        <select id="<?= $isEdit ? 'edit' : 'add' ?>_department" name="department" required data-field="department">
            <option value="">Select Department</option>
            <?php foreach ($departments as $department): ?>
                <option value="<?= htmlspecialchars($department, ENT_QUOTES, 'UTF-8') ?>" <?= $values['department'] === $department ? 'selected' : '' ?>><?= htmlspecialchars($department, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="modal-field">
        <label for="<?= $isEdit ? 'edit' : 'add' ?>_course">Course</label>
        <select id="<?= $isEdit ? 'edit' : 'add' ?>_course" name="course" required data-field="course">
            <option value="">Select Course</option>
            <?php foreach ($courses as $course): ?>
                <option value="<?= htmlspecialchars($course, ENT_QUOTES, 'UTF-8') ?>" <?= $values['course'] === $course ? 'selected' : '' ?>><?= htmlspecialchars($course, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="modal-field">
        <label for="<?= $isEdit ? 'edit' : 'add' ?>_year_level">Year Level</label>
        <select id="<?= $isEdit ? 'edit' : 'add' ?>_year_level" name="year_level" required data-field="year_level">
            <option value="">Select Year Level</option>
            <?php foreach ($yearLevels as $yearLevel): ?>
                <option value="<?= htmlspecialchars($yearLevel, ENT_QUOTES, 'UTF-8') ?>" <?= $values['year_level'] === $yearLevel ? 'selected' : '' ?>><?= htmlspecialchars($yearLevel, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="modal-field full">
        <label for="<?= $isEdit ? 'edit' : 'add' ?>_section">Section</label>
        <select id="<?= $isEdit ? 'edit' : 'add' ?>_section" name="section" required data-field="section">
            <option value="">Select course and year level first</option>
            <?php foreach ($sections as $section): ?>
                <option value="<?= htmlspecialchars($section, ENT_QUOTES, 'UTF-8') ?>" <?= $values['section'] === $section ? 'selected' : '' ?>><?= htmlspecialchars($section, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="modal-field">
        <label for="<?= $isEdit ? 'edit' : 'add' ?>_semester">Semester</label>
        <input id="<?= $isEdit ? 'edit' : 'add' ?>_semester" type="text" name="semester" value="<?= htmlspecialchars((string) $values['semester'], ENT_QUOTES, 'UTF-8') ?>" readonly data-keep-readonly data-field="semester" placeholder="Auto-filled from section">
    </div>
    <div class="modal-field">
        <label for="<?= $isEdit ? 'edit' : 'add' ?>_academic_year">Academic Year</label>
        <input id="<?= $isEdit ? 'edit' : 'add' ?>_academic_year" type="text" name="academic_year" value="<?= htmlspecialchars((string) $values['academic_year'], ENT_QUOTES, 'UTF-8') ?>" readonly data-keep-readonly data-field="academic_year" placeholder="Auto-filled from section">
    </div>
    <div class="modal-field full">
        <label for="<?= $isEdit ? 'edit' : 'add' ?>_password">Password</label>
        <input id="<?= $isEdit ? 'edit' : 'add' ?>_password" type="password" name="password" value="<?= htmlspecialchars((string) $values['password'], ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= $isEdit ? 'Leave blank to keep current' : 'Enter password' ?>">
    </div>
    <?php
}

function render_student_view_fields(): void
{
    $fields = [
        ['label' => 'Student ID', 'name' => 'student_id'],
        ['label' => 'Email', 'name' => 'email'],
        ['label' => 'First Name', 'name' => 'first_name'],
        ['label' => 'Middle Name', 'name' => 'middle_name'],
        ['label' => 'Last Name', 'name' => 'last_name'],
        ['label' => 'Department', 'name' => 'department', 'full' => true],
        ['label' => 'Course', 'name' => 'course'],
        ['label' => 'Year Level', 'name' => 'year_level'],
        ['label' => 'Section', 'name' => 'section'],
        ['label' => 'Semester', 'name' => 'semester'],
        ['label' => 'Academic Year', 'name' => 'academic_year'],
    ];

    foreach ($fields as $field) {
        $fullClass = !empty($field['full']) ? ' full' : '';
        ?>
        <div class="modal-field<?= $fullClass ?>">
            <label><?= htmlspecialchars($field['label'], ENT_QUOTES, 'UTF-8') ?></label>
            <input type="text" data-field="<?= htmlspecialchars($field['name'], ENT_QUOTES, 'UTF-8') ?>" readonly>
        </div>
        <?php
    }
    ?>
    <div class="modal-field full">
        <label>Status</label>
        <div class="modal-status-field">
            <span class="status-badge active" data-view-status>active</span>
        </div>
    </div>
    <?php
}
