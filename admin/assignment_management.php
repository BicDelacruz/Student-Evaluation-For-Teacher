<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/assignment_management.php';

require_admin_verified();

$assignmentData = load_assignment_management();
$references = assignment_management_reference_data();
$structure = $references['structure'];
$facultyRecords = $references['faculty'];
$facultyMap = $references['faculty_map'];
$subjectMap = $references['subject_map'];
$sectionMap = $references['section_map'];

$activeTab = normalize_assignment_tab($_GET['tab'] ?? $_POST['tab'] ?? 'faculty_to_subject');
$selfAction = 'assignment_management.php?tab=' . rawurlencode($activeTab);
$pageAction = 'assignment_management.php';

$successMessage = get_flash('success');
$errorMessage = get_flash('error');
$pageErrors = [];
$openModal = null;

$facultyAssignmentAddForm = [
    'department_code' => '',
    'faculty_id' => '',
    'is_general_education' => false,
    'course_codes' => [],
    'subject_code' => '',
    'year_level' => '',
    'semester' => '',
    'academic_year' => '2025-2026',
];
$facultyAssignmentEditForm = $facultyAssignmentAddForm + ['id' => ''];

$sectionEnrollmentAddForm = [
    'department_code' => '',
    'course_code' => '',
    'year_level' => '',
    'semester' => '',
    'section_names' => [],
    'subject_type' => 'program_only',
    'subject_codes' => [],
    'academic_year' => '2025-2026',
    'status' => 'active',
];
$sectionEnrollmentEditForm = [
    'id' => '',
    'department_code' => '',
    'course_code' => '',
    'year_level' => '',
    'semester' => '',
    'section_name' => '',
    'section_names' => [],
    'subject_type' => 'program_only',
    'subject_codes' => [],
    'academic_year' => '2025-2026',
    'status' => 'active',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string) ($_POST['action'] ?? ''));

    if ($action === 'add_faculty_assignment') {
        $facultyAssignmentAddForm = normalize_faculty_assignment_payload($_POST);
        $pageErrors = validate_faculty_assignment_payload($facultyAssignmentAddForm, $assignmentData, $references);

        if ($pageErrors === []) {
            $assignmentData['faculty_assignments'][] = build_faculty_assignment_record($facultyAssignmentAddForm, $references);
            save_assignment_management($assignmentData);
            set_flash('success', 'Faculty assignment added successfully.');
            redirect($selfAction);
        }

        $openModal = 'addFacultyAssignmentModal';
    } elseif ($action === 'update_faculty_assignment') {
        $recordId = trim((string) ($_POST['id'] ?? ''));
        $recordIndex = assignment_management_faculty_assignment_index($assignmentData, $recordId);

        if ($recordIndex === null) {
            set_flash('error', 'The selected faculty assignment could not be found.');
            redirect($selfAction);
        }

        $facultyAssignmentEditForm = normalize_faculty_assignment_payload($_POST);
        $facultyAssignmentEditForm['id'] = $recordId;
        $pageErrors = validate_faculty_assignment_payload($facultyAssignmentEditForm, $assignmentData, $references, $recordId);

        if ($pageErrors === []) {
            $assignmentData['faculty_assignments'][$recordIndex] = build_faculty_assignment_record(
                $facultyAssignmentEditForm,
                $references,
                $assignmentData['faculty_assignments'][$recordIndex]
            );
            save_assignment_management($assignmentData);
            set_flash('success', 'Faculty assignment updated successfully.');
            redirect($selfAction);
        }

        $openModal = 'editFacultyAssignmentModal';
    } elseif ($action === 'delete_faculty_assignment') {
        $recordId = trim((string) ($_POST['id'] ?? ''));
        $recordIndex = assignment_management_faculty_assignment_index($assignmentData, $recordId);

        if ($recordIndex === null) {
            set_flash('error', 'The selected faculty assignment could not be found.');
            redirect($selfAction);
        }

        array_splice($assignmentData['faculty_assignments'], $recordIndex, 1);
        save_assignment_management($assignmentData);
        set_flash('success', 'Faculty assignment deleted successfully.');
        redirect($selfAction);
    } elseif ($action === 'add_section_enrollments') {
        $sectionEnrollmentAddForm = normalize_section_enrollment_payload($_POST);
        $pageErrors = validate_section_enrollment_payload($sectionEnrollmentAddForm, $assignmentData, $references, true);

        if ($pageErrors === []) {
            foreach (build_section_enrollment_records($sectionEnrollmentAddForm, $sectionEnrollmentAddForm['section_names']) as $record) {
                $assignmentData['section_enrollments'][] = $record;
            }
            save_assignment_management($assignmentData);
            set_flash('success', 'Section enrollments added successfully.');
            redirect($selfAction);
        }

        $openModal = 'addSectionEnrollmentModal';
    } elseif ($action === 'update_section_enrollment') {
        $recordId = trim((string) ($_POST['id'] ?? ''));
        $recordIndex = assignment_management_section_enrollment_index($assignmentData, $recordId);

        if ($recordIndex === null) {
            set_flash('error', 'The selected section enrollment could not be found.');
            redirect($selfAction);
        }

        $sectionEnrollmentEditForm = normalize_section_enrollment_payload($_POST);
        $sectionEnrollmentEditForm['id'] = $recordId;
        $pageErrors = validate_section_enrollment_payload($sectionEnrollmentEditForm, $assignmentData, $references, false, $recordId);

        if ($pageErrors === []) {
            $records = build_section_enrollment_records(
                $sectionEnrollmentEditForm,
                [$sectionEnrollmentEditForm['section_name']],
                $assignmentData['section_enrollments'][$recordIndex]
            );
            $assignmentData['section_enrollments'][$recordIndex] = $records[0];
            save_assignment_management($assignmentData);
            set_flash('success', 'Section enrollment updated successfully.');
            redirect($selfAction);
        }

        $openModal = 'editSectionEnrollmentModal';
    } elseif ($action === 'delete_section_enrollment') {
        $recordId = trim((string) ($_POST['id'] ?? ''));
        $recordIndex = assignment_management_section_enrollment_index($assignmentData, $recordId);

        if ($recordIndex === null) {
            set_flash('error', 'The selected section enrollment could not be found.');
            redirect($selfAction);
        }

        array_splice($assignmentData['section_enrollments'], $recordIndex, 1);
        save_assignment_management($assignmentData);
        set_flash('success', 'Section enrollment deleted successfully.');
        redirect($selfAction);
    }

    $assignmentData = load_assignment_management();
    $references = assignment_management_reference_data();
    $structure = $references['structure'];
    $facultyRecords = $references['faculty'];
    $facultyMap = $references['faculty_map'];
    $subjectMap = $references['subject_map'];
    $sectionMap = $references['section_map'];
}

$departments = $structure['departments'];
usort($departments, static fn (array $left, array $right): int => strcmp((string) $left['name'], (string) $right['name']));

$courses = $structure['courses'];
usort($courses, static fn (array $left, array $right): int => strcmp((string) $left['code'], (string) $right['code']));

$activeCourses = assignment_management_active_courses($structure);
usort($activeCourses, static fn (array $left, array $right): int => strcmp((string) $left['code'], (string) $right['code']));

$activeFaculty = assignment_management_active_faculty($facultyRecords);
usort($activeFaculty, static fn (array $left, array $right): int => strcmp(faculty_full_name($left), faculty_full_name($right)));

$allFaculty = $facultyRecords;
usort($allFaculty, static fn (array $left, array $right): int => strcmp(faculty_full_name($left), faculty_full_name($right)));

$subjects = $structure['subjects'];
usort($subjects, static fn (array $left, array $right): int => strcmp((string) $left['code'], (string) $right['code']));

$sections = $structure['sections'];
usort($sections, static function (array $left, array $right): int {
    $leftKey = implode('|', [(string) ($left['course_code'] ?? ''), (string) ($left['year_level'] ?? ''), (string) ($left['name'] ?? '')]);
    $rightKey = implode('|', [(string) ($right['course_code'] ?? ''), (string) ($right['year_level'] ?? ''), (string) ($right['name'] ?? '')]);
    return strcmp($leftKey, $rightKey);
});

$facultySubjectFilters = assignment_management_subject_filter_options($assignmentData['faculty_assignments']);
$facultyNameFilters = assignment_management_faculty_filter_options($assignmentData['faculty_assignments']);
$facultyCourseFilters = assignment_management_course_filter_options($assignmentData['faculty_assignments'], []);
$sectionCourseFilters = assignment_management_course_filter_options([], $assignmentData['section_enrollments']);
$sectionNameFilters = assignment_management_section_filter_options($assignmentData['section_enrollments']);
$sectionYearFilters = assignment_management_year_level_filter_options($assignmentData['section_enrollments']);

$clientConfig = [
    'activeTab' => $activeTab,
    'departments' => $departments,
    'courses' => $courses,
    'faculty' => $allFaculty,
    'sections' => $sections,
    'subjects' => $subjects,
    'facultyAssignments' => $assignmentData['faculty_assignments'],
    'sectionEnrollments' => $assignmentData['section_enrollments'],
];

render_admin_layout_start(
    'Assignment Management',
    'assignments',
    'Assignment Management',
    'Assign faculty to subjects and enroll students'
);
?>
<?php if ($successMessage !== null): ?>
    <div class="alert alert-success"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($errorMessage !== null): ?>
    <div class="alert alert-error"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($pageErrors !== [] && $openModal === null): ?>
    <div class="alert alert-error"><?= htmlspecialchars(implode(' ', $pageErrors), ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<script>
window.ADMIN_ASSIGNMENT_MANAGEMENT = <?= json_encode($clientConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
</script>

<section class="structure-tabs assignment-tabs" data-assignment-tabs data-active-tab="<?= htmlspecialchars($activeTab, ENT_QUOTES, 'UTF-8') ?>">
    <?php foreach ([
        'faculty_to_subject' => ['label' => 'Faculty to Subject', 'icon' => 'faculty'],
        'section_to_subject' => ['label' => 'Section to Subject', 'icon' => 'students'],
    ] as $tabKey => $tabConfig): ?>
        <a
            href="assignment_management.php?tab=<?= rawurlencode($tabKey) ?>"
            class="structure-tab-button <?= $activeTab === $tabKey ? 'is-active' : '' ?>"
            data-assignment-tab="<?= htmlspecialchars($tabKey, ENT_QUOTES, 'UTF-8') ?>"
        >
            <span class="structure-tab-icon"><?= admin_icon($tabConfig['icon']) ?></span>
            <span><?= htmlspecialchars($tabConfig['label'], ENT_QUOTES, 'UTF-8') ?></span>
        </a>
    <?php endforeach; ?>
</section>

<section class="structure-pane assignment-pane <?= $activeTab === 'faculty_to_subject' ? 'is-active' : '' ?>" data-assignment-pane="faculty_to_subject">
    <div class="structure-pane-header">
        <div>
            <h2>Faculty Assignments (<span data-assignment-count="faculty_to_subject"><?= count($assignmentData['faculty_assignments']) ?></span>)</h2>
        </div>
        <button type="button" class="add-button" data-open-modal="#addFacultyAssignmentModal">
            <span class="button-icon"><?= admin_icon('plus') ?></span>
            Assign Faculty
        </button>
    </div>

    <section class="filter-panel assignment-filter-card">
        <div class="search-row">
            <span class="search-icon-inline"><?= admin_icon('search') ?></span>
            <input type="text" placeholder="Search by faculty name or subject..." data-assignment-search="faculty_to_subject">
        </div>
        <div class="filters-grid">
            <div class="filter-field">
                <label for="faculty_subject_filter">Subject</label>
                <select id="faculty_subject_filter" data-assignment-filter="faculty-subject">
                    <option value="">All Subjects</option>
                    <?php foreach ($facultySubjectFilters as $subjectCode): ?>
                        <option value="<?= htmlspecialchars($subjectCode, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($subjectCode, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-field">
                <label for="faculty_name_filter">Faculty Name</label>
                <select id="faculty_name_filter" data-assignment-filter="faculty-name">
                    <option value="">All Faculty</option>
                    <?php foreach ($facultyNameFilters as $facultyId): ?>
                        <option value="<?= htmlspecialchars($facultyId, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(assignment_management_faculty_name($facultyId, $facultyMap), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-field">
                <label for="faculty_course_filter">Course</label>
                <select id="faculty_course_filter" data-assignment-filter="faculty-course">
                    <option value="">All Courses</option>
                    <?php foreach ($facultyCourseFilters as $courseCode): ?>
                        <option value="<?= htmlspecialchars($courseCode, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($courseCode, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </section>

    <section class="table-card">
        <table class="students-table">
            <thead>
                <tr>
                    <th>Faculty</th>
                    <th>Subject</th>
                    <th>Course</th>
                    <th>Semester</th>
                    <th>Academic Year</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody data-assignment-search-scope="faculty_to_subject">
                <?php foreach ($assignmentData['faculty_assignments'] as $record): ?>
                    <?php
                    $recordJson = htmlspecialchars(json_encode($record, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');
                    $facultyName = assignment_management_faculty_name((string) $record['faculty_id'], $facultyMap);
                    $subjectLabel = assignment_management_subject_label((string) $record['subject_code'], $subjectMap);
                    $courseLabel = assignment_management_course_scope_label($record, $structure);
                    $searchText = strtolower(implode(' ', [$facultyName, $subjectLabel, $courseLabel]));
                    $courseFilterValue = !empty($record['is_general_education']) ? 'ALL' : implode(',', (array) ($record['course_codes'] ?? []));
                    ?>
                    <tr
                        data-assignment-item
                        data-assignment-type="faculty"
                        data-search-text="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>"
                        data-filter-subject="<?= htmlspecialchars((string) $record['subject_code'], ENT_QUOTES, 'UTF-8') ?>"
                        data-filter-faculty="<?= htmlspecialchars((string) $record['faculty_id'], ENT_QUOTES, 'UTF-8') ?>"
                        data-filter-course="<?= htmlspecialchars($courseFilterValue, ENT_QUOTES, 'UTF-8') ?>"
                    >
                        <td data-label="Faculty"><?= htmlspecialchars($facultyName, ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="Subject"><?= htmlspecialchars($subjectLabel, ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="Course"><?= htmlspecialchars($courseLabel, ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="Semester"><?= htmlspecialchars((string) $record['semester'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="Academic Year"><?= htmlspecialchars((string) $record['academic_year'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="Actions">
                            <div class="action-buttons">
                                <button type="button" class="icon-button assignment-view" title="View Assignment" data-open-modal="#viewFacultyAssignmentModal" data-assignment-entity="faculty_assignment" data-assignment-action="view" data-assignment-json="<?= $recordJson ?>">
                                    <?= admin_icon('view') ?>
                                </button>
                                <button type="button" class="icon-button edit" title="Edit Assignment" data-open-modal="#editFacultyAssignmentModal" data-assignment-entity="faculty_assignment" data-assignment-action="edit" data-assignment-json="<?= $recordJson ?>">
                                    <?= admin_icon('edit') ?>
                                </button>
                                <form method="post" action="<?= htmlspecialchars($pageAction, ENT_QUOTES, 'UTF-8') ?>" class="inline-action-form">
                                    <input type="hidden" name="tab" value="faculty_to_subject">
                                    <input type="hidden" name="action" value="delete_faculty_assignment">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars((string) $record['id'], ENT_QUOTES, 'UTF-8') ?>">
                                    <button type="submit" class="icon-submit delete" title="Delete Assignment" data-confirm-message="Delete this faculty assignment?">
                                        <?= admin_icon('delete') ?>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</section>

<section class="structure-pane assignment-pane <?= $activeTab === 'section_to_subject' ? 'is-active' : '' ?>" data-assignment-pane="section_to_subject">
    <div class="structure-pane-header">
        <div>
            <h2>Section Enrollments (<span data-assignment-count="section_to_subject"><?= count($assignmentData['section_enrollments']) ?></span>)</h2>
        </div>
        <button type="button" class="add-button" data-open-modal="#addSectionEnrollmentModal">
            <span class="button-icon"><?= admin_icon('plus') ?></span>
            Enroll Section
        </button>
    </div>

    <section class="filter-panel assignment-filter-card">
        <div class="search-row">
            <span class="search-icon-inline"><?= admin_icon('search') ?></span>
            <input type="text" placeholder="Search by section, course, or subject..." data-assignment-search="section_to_subject">
        </div>
        <div class="filters-grid">
            <div class="filter-field">
                <label for="section_course_filter">Course</label>
                <select id="section_course_filter" data-assignment-filter="section-course">
                    <option value="">All Courses</option>
                    <?php foreach ($sectionCourseFilters as $courseCode): ?>
                        <option value="<?= htmlspecialchars($courseCode, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($courseCode, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-field">
                <label for="section_name_filter">Section</label>
                <select id="section_name_filter" data-assignment-filter="section-name">
                    <option value="">All Sections</option>
                    <?php foreach ($sectionNameFilters as $sectionName): ?>
                        <option value="<?= htmlspecialchars($sectionName, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($sectionName, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-field">
                <label for="section_year_filter">Year Level</label>
                <select id="section_year_filter" data-assignment-filter="section-year">
                    <option value="">All Year Levels</option>
                    <?php foreach ($sectionYearFilters as $yearLevel): ?>
                        <option value="<?= htmlspecialchars($yearLevel, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($yearLevel, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </section>

    <section class="table-card">
        <table class="students-table">
            <thead>
                <tr>
                    <th>Course</th>
                    <th>Section</th>
                    <th>Subjects Taken by the Section</th>
                    <th>Year Level</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody data-assignment-search-scope="section_to_subject">
                <?php foreach ($assignmentData['section_enrollments'] as $record): ?>
                    <?php
                    $recordJson = htmlspecialchars(json_encode($record, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');
                    $subjectCodesLabel = assignment_management_subject_codes_label((array) ($record['subject_codes'] ?? []));
                    $searchText = strtolower(implode(' ', [(string) $record['course_code'], (string) $record['section_name'], $subjectCodesLabel]));
                    ?>
                    <tr
                        data-assignment-item
                        data-assignment-type="section"
                        data-search-text="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>"
                        data-filter-course="<?= htmlspecialchars((string) $record['course_code'], ENT_QUOTES, 'UTF-8') ?>"
                        data-filter-section="<?= htmlspecialchars((string) $record['section_name'], ENT_QUOTES, 'UTF-8') ?>"
                        data-filter-year="<?= htmlspecialchars((string) $record['year_level'], ENT_QUOTES, 'UTF-8') ?>"
                    >
                        <td data-label="Course"><?= htmlspecialchars((string) $record['course_code'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="Section"><?= htmlspecialchars((string) $record['section_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="Subjects Taken by the Section"><?= htmlspecialchars($subjectCodesLabel, ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="Year Level"><?= htmlspecialchars((string) $record['year_level'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="Status">
                            <span class="status-badge <?= ($record['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active' ?>"><?= htmlspecialchars((string) $record['status'], ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                        <td data-label="Actions">
                            <div class="action-buttons">
                                <button type="button" class="icon-button assignment-view" title="View Enrollment" data-open-modal="#viewSectionEnrollmentModal" data-assignment-entity="section_enrollment" data-assignment-action="view" data-assignment-json="<?= $recordJson ?>">
                                    <?= admin_icon('view') ?>
                                </button>
                                <button type="button" class="icon-button edit" title="Edit Enrollment" data-open-modal="#editSectionEnrollmentModal" data-assignment-entity="section_enrollment" data-assignment-action="edit" data-assignment-json="<?= $recordJson ?>">
                                    <?= admin_icon('edit') ?>
                                </button>
                                <form method="post" action="<?= htmlspecialchars($pageAction, ENT_QUOTES, 'UTF-8') ?>" class="inline-action-form">
                                    <input type="hidden" name="tab" value="section_to_subject">
                                    <input type="hidden" name="action" value="delete_section_enrollment">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars((string) $record['id'], ENT_QUOTES, 'UTF-8') ?>">
                                    <button type="submit" class="icon-submit delete" title="Delete Enrollment" data-confirm-message="Delete this section enrollment?">
                                        <?= admin_icon('delete') ?>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</section>

<div class="modal<?= $openModal === 'addFacultyAssignmentModal' ? ' is-visible' : '' ?>" id="addFacultyAssignmentModal">
    <div class="modal-card structure-modal-card structure-modal-tall assignment-modal-card">
        <div class="modal-header-row">
            <h3>Assign Faculty to Subject</h3>
            <button type="button" class="modal-close-button" data-close-modal aria-label="Close"><?= admin_icon('close') ?></button>
        </div>
        <?php if ($openModal === 'addFacultyAssignmentModal' && $pageErrors !== []): ?>
            <div class="alert alert-error"><?= htmlspecialchars(implode(' ', $pageErrors), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <form method="post" action="<?= htmlspecialchars($pageAction, ENT_QUOTES, 'UTF-8') ?>" data-assignment-faculty-form="add">
            <input type="hidden" name="tab" value="faculty_to_subject">
            <input type="hidden" name="action" value="add_faculty_assignment">
            <div class="modal-grid structure-modal-grid assignment-modal-body">
                <?php render_faculty_assignment_form_fields($facultyAssignmentAddForm, $departments, $activeFaculty, $activeCourses, $subjects, false); ?>
            </div>
            <div class="modal-actions modal-actions-sticky">
                <button type="button" class="secondary-button" data-close-modal>Cancel</button>
                <button type="submit" class="primary-button" data-assignment-submit="faculty-add">Assign Faculty</button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="viewFacultyAssignmentModal">
    <div class="modal-card structure-modal-card assignment-details-modal">
        <div class="modal-header-row">
            <h3>Assignment Details</h3>
            <button type="button" class="modal-close-button" data-close-modal aria-label="Close"><?= admin_icon('close') ?></button>
        </div>
        <div class="modal-grid structure-modal-grid single-column">
            <div class="modal-field full">
                <label>Faculty Member</label>
                <input type="text" readonly data-assignment-view-field="faculty-member">
            </div>
            <div class="modal-field full">
                <label>Department</label>
                <input type="text" readonly data-assignment-view-field="faculty-department">
            </div>
            <div class="modal-field full">
                <label>Subject</label>
                <input type="text" readonly data-assignment-view-field="faculty-subject">
            </div>
            <div class="modal-field full">
                <label>Course(s)</label>
                <input type="text" readonly data-assignment-view-field="faculty-courses">
            </div>
            <div class="modal-field">
                <label>Year Level</label>
                <input type="text" readonly data-assignment-view-field="faculty-year-level">
            </div>
            <div class="modal-field">
                <label>Semester</label>
                <input type="text" readonly data-assignment-view-field="faculty-semester">
            </div>
            <div class="modal-field full">
                <label>Academic Year</label>
                <input type="text" readonly data-assignment-view-field="faculty-academic-year">
            </div>
        </div>
        <div class="modal-actions">
            <button type="button" class="secondary-button" data-close-modal>Close</button>
        </div>
    </div>
</div>

<div class="modal<?= $openModal === 'editFacultyAssignmentModal' ? ' is-visible' : '' ?>" id="editFacultyAssignmentModal">
    <div class="modal-card structure-modal-card assignment-modal-card">
        <div class="modal-header-row">
            <h3>Edit Faculty Assignment</h3>
            <button type="button" class="modal-close-button" data-close-modal aria-label="Close"><?= admin_icon('close') ?></button>
        </div>
        <?php if ($openModal === 'editFacultyAssignmentModal' && $pageErrors !== []): ?>
            <div class="alert alert-error"><?= htmlspecialchars(implode(' ', $pageErrors), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <form method="post" action="<?= htmlspecialchars($pageAction, ENT_QUOTES, 'UTF-8') ?>" data-assignment-faculty-form="edit">
            <input type="hidden" name="tab" value="faculty_to_subject">
            <input type="hidden" name="action" value="update_faculty_assignment">
            <input type="hidden" name="id" value="<?= htmlspecialchars((string) $facultyAssignmentEditForm['id'], ENT_QUOTES, 'UTF-8') ?>">
            <div class="modal-grid structure-modal-grid">
                <?php render_faculty_assignment_form_fields($facultyAssignmentEditForm, $departments, $allFaculty, $courses, $subjects, true); ?>
            </div>
            <div class="modal-actions">
                <button type="button" class="secondary-button" data-close-modal>Cancel</button>
                <button type="submit" class="primary-button">Update Assignment</button>
            </div>
        </form>
    </div>
</div>

<div class="modal<?= $openModal === 'addSectionEnrollmentModal' ? ' is-visible' : '' ?>" id="addSectionEnrollmentModal">
    <div class="modal-card structure-modal-card assignment-modal-card assignment-enrollment-modal">
        <div class="modal-header-row">
            <h3>Enroll Section to Subjects</h3>
            <button type="button" class="modal-close-button" data-close-modal aria-label="Close"><?= admin_icon('close') ?></button>
        </div>
        <?php if ($openModal === 'addSectionEnrollmentModal' && $pageErrors !== []): ?>
            <div class="alert alert-error"><?= htmlspecialchars(implode(' ', $pageErrors), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <form method="post" action="<?= htmlspecialchars($pageAction, ENT_QUOTES, 'UTF-8') ?>" data-assignment-section-form="add">
            <input type="hidden" name="tab" value="section_to_subject">
            <input type="hidden" name="action" value="add_section_enrollments">
            <div class="modal-grid structure-modal-grid assignment-modal-body single-column assignment-enrollment-grid">
                <?php render_section_enrollment_form_fields($sectionEnrollmentAddForm, $departments, $activeCourses, $sections, $subjects, true, $assignmentData['faculty_assignments'], $facultyMap); ?>
            </div>
            <div class="modal-actions modal-actions-sticky">
                <button type="button" class="secondary-button" data-close-modal>Cancel</button>
                <button type="submit" class="primary-button" data-assignment-submit="section-add">Enroll Section</button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="viewSectionEnrollmentModal">
    <div class="modal-card structure-modal-card assignment-details-modal">
        <div class="modal-header-row">
            <h3>Enrollment Details</h3>
            <button type="button" class="modal-close-button" data-close-modal aria-label="Close"><?= admin_icon('close') ?></button>
        </div>
        <div class="modal-grid structure-modal-grid single-column">
            <div class="modal-field full">
                <label>Department</label>
                <input type="text" readonly data-assignment-view-field="section-department">
            </div>
            <div class="modal-field full">
                <label>Course</label>
                <input type="text" readonly data-assignment-view-field="section-course">
            </div>
            <div class="modal-field">
                <label>Year Level</label>
                <input type="text" readonly data-assignment-view-field="section-year-level">
            </div>
            <div class="modal-field">
                <label>Section</label>
                <input type="text" readonly data-assignment-view-field="section-name">
            </div>
            <div class="modal-field full">
                <label>Semester</label>
                <input type="text" readonly data-assignment-view-field="section-semester">
            </div>
            <div class="modal-field full assignment-detail-list-field">
                <label>Subjects Taken by the Section</label>
                <div class="assignment-detail-list" data-assignment-view-list="section-subjects"></div>
            </div>
            <div class="modal-field">
                <label>Academic Year</label>
                <input type="text" readonly data-assignment-view-field="section-academic-year">
            </div>
            <div class="modal-field">
                <label>Status</label>
                <div class="assignment-status-wrap">
                    <span class="status-badge active" data-assignment-view-status="section-status">active</span>
                </div>
            </div>
        </div>
        <div class="modal-actions">
            <button type="button" class="secondary-button" data-close-modal>Close</button>
        </div>
    </div>
</div>

<div class="modal<?= $openModal === 'editSectionEnrollmentModal' ? ' is-visible' : '' ?>" id="editSectionEnrollmentModal">
    <div class="modal-card structure-modal-card assignment-modal-card assignment-enrollment-modal">
        <div class="modal-header-row">
            <h3>Edit Section Enrollment</h3>
            <button type="button" class="modal-close-button" data-close-modal aria-label="Close"><?= admin_icon('close') ?></button>
        </div>
        <?php if ($openModal === 'editSectionEnrollmentModal' && $pageErrors !== []): ?>
            <div class="alert alert-error"><?= htmlspecialchars(implode(' ', $pageErrors), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <form method="post" action="<?= htmlspecialchars($pageAction, ENT_QUOTES, 'UTF-8') ?>" data-assignment-section-form="edit">
            <input type="hidden" name="tab" value="section_to_subject">
            <input type="hidden" name="action" value="update_section_enrollment">
            <input type="hidden" name="id" value="<?= htmlspecialchars((string) $sectionEnrollmentEditForm['id'], ENT_QUOTES, 'UTF-8') ?>">
            <div class="modal-grid structure-modal-grid assignment-modal-body single-column assignment-enrollment-grid">
                <?php render_section_enrollment_form_fields($sectionEnrollmentEditForm, $departments, $courses, $sections, $subjects, false, $assignmentData['faculty_assignments'], $facultyMap); ?>
            </div>
            <div class="modal-actions modal-actions-sticky">
                <button type="button" class="secondary-button" data-close-modal>Cancel</button>
                <button type="submit" class="primary-button">Update Enrollment</button>
            </div>
        </form>
    </div>
</div>

<?php
render_admin_layout_end();

function render_faculty_assignment_form_fields(array $values, array $departments, array $facultyRecords, array $courses, array $subjects, bool $isEdit): void
{
    $prefix = $isEdit ? 'edit' : 'add';
    ?>
    <div class="modal-field full">
        <label for="<?= $prefix ?>_assignment_department">Department</label>
        <select id="<?= $prefix ?>_assignment_department" name="department_code" required data-assignment-department="<?= $isEdit ? 'edit' : 'add' ?>">
            <option value=""><?= $isEdit ? 'Select Department' : 'Select Department' ?></option>
            <?php foreach ($departments as $department): ?>
                <option value="<?= htmlspecialchars((string) $department['code'], ENT_QUOTES, 'UTF-8') ?>" <?= ((string) $values['department_code'] === (string) $department['code']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string) $department['name'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="modal-field full">
        <label for="<?= $prefix ?>_assignment_faculty">Faculty Member</label>
        <select id="<?= $prefix ?>_assignment_faculty" name="faculty_id" required data-assignment-faculty-select="<?= $isEdit ? 'edit' : 'add' ?>">
            <option value=""><?= $isEdit ? 'Select Faculty Member' : 'Select department first' ?></option>
            <?php foreach ($facultyRecords as $faculty): ?>
                <option
                    value="<?= htmlspecialchars((string) $faculty['faculty_id'], ENT_QUOTES, 'UTF-8') ?>"
                    data-department-name="<?= htmlspecialchars((string) ($faculty['department'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    <?= ((string) $values['faculty_id'] === (string) $faculty['faculty_id']) ? 'selected' : '' ?>
                >
                    <?= htmlspecialchars(faculty_full_name($faculty), ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <?php if (!$isEdit): ?>
        <div class="modal-field full modal-checkbox-field">
            <label class="checkbox-inline">
                <input type="checkbox" name="is_general_education" value="1" data-assignment-ge-toggle="add" <?= !empty($values['is_general_education']) ? 'checked' : '' ?>>
                <span>General Education (applies to all courses)</span>
            </label>
        </div>

        <div class="modal-field full" data-assignment-course-wrap="add">
            <label>Course(s)</label>
            <div class="course-checklist assignment-checklist" data-assignment-course-checklist="add">
                <?php foreach ($courses as $course): ?>
                    <label class="course-checklist-item assignment-checklist-item" data-department-code="<?= htmlspecialchars((string) $course['department_code'], ENT_QUOTES, 'UTF-8') ?>">
                        <input type="checkbox" name="course_codes[]" value="<?= htmlspecialchars((string) $course['code'], ENT_QUOTES, 'UTF-8') ?>" <?= in_array((string) $course['code'], (array) $values['course_codes'], true) ? 'checked' : '' ?>>
                        <span><?= htmlspecialchars((string) $course['code'] . ' - ' . (string) $course['name'], ENT_QUOTES, 'UTF-8') ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="modal-field full">
            <label>Course Scope</label>
            <div class="assignment-scope-summary" data-assignment-course-summary="edit"></div>
            <div data-assignment-edit-hidden-courses>
                <?php foreach ((array) ($values['course_codes'] ?? []) as $courseCode): ?>
                    <input type="hidden" name="course_codes[]" value="<?= htmlspecialchars((string) $courseCode, ENT_QUOTES, 'UTF-8') ?>">
                <?php endforeach; ?>
                <?php if (!empty($values['is_general_education'])): ?>
                    <input type="hidden" name="is_general_education" value="1">
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="modal-field full">
        <label for="<?= $prefix ?>_assignment_subject">Subject</label>
        <select id="<?= $prefix ?>_assignment_subject" name="subject_code" required data-assignment-subject-select="<?= $isEdit ? 'edit' : 'add' ?>">
            <option value=""><?= $isEdit ? 'Select Subject' : 'Select course(s) to see subjects' ?></option>
            <?php foreach ($subjects as $subject): ?>
                <option
                    value="<?= htmlspecialchars((string) $subject['code'], ENT_QUOTES, 'UTF-8') ?>"
                    data-department-code="<?= htmlspecialchars((string) $subject['department_code'], ENT_QUOTES, 'UTF-8') ?>"
                    data-year-level="<?= htmlspecialchars((string) $subject['year_level'], ENT_QUOTES, 'UTF-8') ?>"
                    data-semester="<?= htmlspecialchars((string) $subject['semester'], ENT_QUOTES, 'UTF-8') ?>"
                    data-course-codes="<?= htmlspecialchars(implode(',', (array) ($subject['course_codes'] ?? [])), ENT_QUOTES, 'UTF-8') ?>"
                    data-is-ge="<?= !empty($subject['is_general_education']) ? '1' : '0' ?>"
                    <?= ((string) $values['subject_code'] === (string) $subject['code']) ? 'selected' : '' ?>
                >
                    <?= htmlspecialchars((string) $subject['code'] . ' - ' . (string) $subject['title'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="modal-field">
        <label for="<?= $prefix ?>_assignment_year_level">Year Level</label>
        <input id="<?= $prefix ?>_assignment_year_level" type="text" name="year_level" readonly value="<?= htmlspecialchars((string) $values['year_level'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Auto-filled from subject" data-assignment-year-level="<?= $isEdit ? 'edit' : 'add' ?>">
    </div>

    <div class="modal-field">
        <label for="<?= $prefix ?>_assignment_semester">Semester</label>
        <input id="<?= $prefix ?>_assignment_semester" type="text" name="semester" readonly value="<?= htmlspecialchars((string) $values['semester'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Auto-filled from subject" data-assignment-semester="<?= $isEdit ? 'edit' : 'add' ?>">
    </div>

    <div class="modal-field full">
        <label for="<?= $prefix ?>_assignment_academic_year">Academic Year</label>
        <input id="<?= $prefix ?>_assignment_academic_year" type="text" name="academic_year" required value="<?= htmlspecialchars((string) $values['academic_year'], ENT_QUOTES, 'UTF-8') ?>" placeholder="e.g., 2025-2026">
    </div>
    <?php
}

function render_section_enrollment_form_fields(array $values, array $departments, array $courses, array $sections, array $subjects, bool $isAdd, array $facultyAssignments, array $facultyMap): void
{
    $prefix = $isAdd ? 'add' : 'edit';
    ?>
    <div class="modal-field full">
        <label for="<?= $prefix ?>_enrollment_department">Department</label>
        <select id="<?= $prefix ?>_enrollment_department" name="department_code" required data-assignment-section-department="<?= $prefix ?>">
            <option value=""><?= $isAdd ? 'Select Department' : 'Select Department' ?></option>
            <?php foreach ($departments as $department): ?>
                <option value="<?= htmlspecialchars((string) $department['code'], ENT_QUOTES, 'UTF-8') ?>" <?= ((string) $values['department_code'] === (string) $department['code']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string) $department['name'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="modal-field full">
        <label for="<?= $prefix ?>_enrollment_course">Course</label>
        <select id="<?= $prefix ?>_enrollment_course" name="course_code" required data-assignment-section-course="<?= $prefix ?>">
            <option value=""><?= $isAdd ? 'Select department first' : 'Select Course' ?></option>
            <?php foreach ($courses as $course): ?>
                <option
                    value="<?= htmlspecialchars((string) $course['code'], ENT_QUOTES, 'UTF-8') ?>"
                    data-department-code="<?= htmlspecialchars((string) $course['department_code'], ENT_QUOTES, 'UTF-8') ?>"
                    <?= ((string) $values['course_code'] === (string) $course['code']) ? 'selected' : '' ?>
                >
                    <?= htmlspecialchars((string) $course['code'] . ' - ' . (string) $course['name'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="modal-field">
        <label for="<?= $prefix ?>_enrollment_year_level">Year Level</label>
        <select id="<?= $prefix ?>_enrollment_year_level" name="year_level" required data-assignment-section-year="<?= $prefix ?>">
            <option value="">Select Year Level</option>
            <?php foreach (academic_structure_year_levels() as $yearLevel): ?>
                <option value="<?= htmlspecialchars($yearLevel, ENT_QUOTES, 'UTF-8') ?>" <?= ((string) $values['year_level'] === $yearLevel) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($yearLevel, ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="modal-field">
        <label for="<?= $prefix ?>_enrollment_semester">Semester</label>
        <select id="<?= $prefix ?>_enrollment_semester" name="semester" required data-assignment-section-semester="<?= $prefix ?>">
            <option value="">Select Semester</option>
            <?php foreach (academic_structure_semesters() as $semester): ?>
                <option value="<?= htmlspecialchars($semester, ENT_QUOTES, 'UTF-8') ?>" <?= ((string) $values['semester'] === $semester) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($semester, ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <?php if ($isAdd): ?>
        <div class="modal-field full">
            <label>Section(s)</label>
            <div class="course-checklist assignment-checklist" data-assignment-section-checklist="add">
                <?php foreach ($sections as $section): ?>
                    <label class="course-checklist-item assignment-checklist-item" data-department-code="<?= htmlspecialchars((string) $section['department_code'], ENT_QUOTES, 'UTF-8') ?>" data-course-code="<?= htmlspecialchars((string) $section['course_code'], ENT_QUOTES, 'UTF-8') ?>" data-year-level="<?= htmlspecialchars((string) $section['year_level'], ENT_QUOTES, 'UTF-8') ?>">
                        <input type="checkbox" name="section_names[]" value="<?= htmlspecialchars((string) $section['name'], ENT_QUOTES, 'UTF-8') ?>" <?= in_array((string) $section['name'], (array) $values['section_names'], true) ? 'checked' : '' ?>>
                        <span class="assignment-checklist-copy">
                            <strong><?= htmlspecialchars(assignment_management_section_display_name((string) $section['name']), ENT_QUOTES, 'UTF-8') ?></strong>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
            <p class="assignment-selected-count" data-assignment-selected-count="sections-add">0 section(s) selected</p>
        </div>
    <?php else: ?>
        <div class="modal-field full">
            <label for="edit_enrollment_section">Section</label>
            <select id="edit_enrollment_section" name="section_name" required data-assignment-section-select="edit">
                <option value="">Select Section</option>
                <?php foreach ($sections as $section): ?>
                    <option
                        value="<?= htmlspecialchars((string) $section['name'], ENT_QUOTES, 'UTF-8') ?>"
                        data-department-code="<?= htmlspecialchars((string) $section['department_code'], ENT_QUOTES, 'UTF-8') ?>"
                        data-course-code="<?= htmlspecialchars((string) $section['course_code'], ENT_QUOTES, 'UTF-8') ?>"
                        data-year-level="<?= htmlspecialchars((string) $section['year_level'], ENT_QUOTES, 'UTF-8') ?>"
                        <?= ((string) $values['section_name'] === (string) $section['name']) ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars((string) $section['name'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>

    <div class="modal-field full">
        <label for="<?= $prefix ?>_enrollment_subject_type">Subject Type</label>
        <select id="<?= $prefix ?>_enrollment_subject_type" name="subject_type" required data-assignment-subject-type="<?= $prefix ?>">
            <?php foreach ([
                'program_only' => 'Program Subjects Only',
                'ge_only' => 'General Education Subjects Only',
                'program_and_ge' => 'Program and General Education Subjects',
            ] as $typeValue => $typeLabel): ?>
                <option value="<?= htmlspecialchars($typeValue, ENT_QUOTES, 'UTF-8') ?>" <?= ((string) $values['subject_type'] === $typeValue) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="modal-field full">
        <label>Subject(s)</label>
        <div class="course-checklist assignment-checklist" data-assignment-subject-checklist="<?= $prefix ?>">
            <?php foreach ($subjects as $subject): ?>
                <?php
                $previewFaculty = assignment_management_subject_preview_faculty_name(
                    $facultyAssignments,
                    $facultyMap,
                    (string) ($values['course_code'] ?? ''),
                    (string) ($subject['code'] ?? ''),
                    (string) ($subject['semester'] ?? ''),
                    (string) ($values['academic_year'] ?? '2025-2026')
                );
                ?>
                <label class="course-checklist-item assignment-checklist-item assignment-subject-item" data-department-code="<?= htmlspecialchars((string) $subject['department_code'], ENT_QUOTES, 'UTF-8') ?>" data-course-codes="<?= htmlspecialchars(implode(',', (array) ($subject['course_codes'] ?? [])), ENT_QUOTES, 'UTF-8') ?>" data-year-level="<?= htmlspecialchars((string) $subject['year_level'], ENT_QUOTES, 'UTF-8') ?>" data-semester="<?= htmlspecialchars((string) $subject['semester'], ENT_QUOTES, 'UTF-8') ?>" data-is-ge="<?= !empty($subject['is_general_education']) ? '1' : '0' ?>">
                    <input type="checkbox" name="subject_codes[]" value="<?= htmlspecialchars((string) $subject['code'], ENT_QUOTES, 'UTF-8') ?>" <?= in_array((string) $subject['code'], (array) $values['subject_codes'], true) ? 'checked' : '' ?>>
                    <span class="assignment-checklist-copy">
                        <strong><?= htmlspecialchars((string) $subject['code'] . ', ' . (string) $subject['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <?php if ($previewFaculty !== null): ?>
                            <small>Faculty: <?= htmlspecialchars($previewFaculty, ENT_QUOTES, 'UTF-8') ?></small>
                        <?php endif; ?>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>
        <p class="assignment-selected-count" data-assignment-selected-count="subjects-<?= $prefix ?>">0 subject(s) selected</p>
    </div>

    <div class="modal-field full">
        <label for="<?= $prefix ?>_enrollment_academic_year">Academic Year</label>
        <input id="<?= $prefix ?>_enrollment_academic_year" type="text" name="academic_year" required value="<?= htmlspecialchars((string) $values['academic_year'], ENT_QUOTES, 'UTF-8') ?>" placeholder="e.g., 2025-2026">
    </div>

    <?php if (!$isAdd): ?>
        <div class="modal-field full">
            <label for="edit_enrollment_status">Status</label>
            <select id="edit_enrollment_status" name="status">
                <option value="active" <?= ((string) $values['status'] === 'active') ? 'selected' : '' ?>>active</option>
                <option value="inactive" <?= ((string) $values['status'] === 'inactive') ? 'selected' : '' ?>>inactive</option>
            </select>
        </div>
    <?php endif; ?>
    <?php
}

function assignment_management_section_display_name(string $name): string
{
    return str_replace('-', ' ', $name);
}

function assignment_management_subject_preview_faculty_name(array $facultyAssignments, array $facultyMap, string $courseCode, string $subjectCode, string $semester, string $academicYear): ?string
{
    foreach ($facultyAssignments as $assignment) {
        if (($assignment['subject_code'] ?? '') !== $subjectCode) {
            continue;
        }

        if (($assignment['semester'] ?? '') !== $semester || ($assignment['academic_year'] ?? '') !== $academicYear) {
            continue;
        }

        if (!empty($assignment['is_general_education']) || in_array($courseCode, (array) ($assignment['course_codes'] ?? []), true)) {
            return assignment_management_faculty_name((string) ($assignment['faculty_id'] ?? ''), $facultyMap);
        }
    }

    return null;
}
