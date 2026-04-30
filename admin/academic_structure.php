<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/academic_structure.php';

require_admin_verified();

$structure = load_academic_structure();
$activeTab = normalize_structure_tab($_GET['tab'] ?? $_POST['tab'] ?? 'departments');
$selfAction = 'academic_structure.php?tab=' . rawurlencode($activeTab);
$pageAction = 'academic_structure.php';

$successMessage = get_flash('success');
$errorMessage = get_flash('error');
$pageErrors = [];
$openModal = null;

$departmentAddForm = ['code' => '', 'name' => '', 'head' => ''];
$departmentEditForm = $departmentAddForm + ['original_code' => ''];

$courseAddForm = ['code' => '', 'status' => 'active', 'name' => '', 'description' => '', 'department_code' => '', 'year_levels' => '4', 'student_count' => '0'];
$courseEditForm = $courseAddForm + ['original_code' => ''];

$sectionAddForm = [
    'section_name_1' => '',
    'section_name_2' => '',
    'section_name_3' => '',
    'section_name_4' => '',
    'section_name_5' => '',
    'department_code' => '',
    'course_code' => '',
    'year_level' => '',
    'semester' => '',
    'academic_year' => '2025-2026',
    'total_students' => '0',
];
$sectionEditForm = [
    'name' => '',
    'department_code' => '',
    'course_code' => '',
    'year_level' => '',
    'semester' => '',
    'academic_year' => '',
    'total_students' => '0',
    'original_name' => '',
    'original_course_code' => '',
    'original_year_level' => '',
    'original_semester' => '',
    'original_academic_year' => '',
];

$subjectAddForm = [
    'code' => '',
    'units' => '',
    'title' => '',
    'description' => '',
    'is_general_education' => false,
    'department_code' => '',
    'course_codes' => [],
    'year_level' => '',
    'semester' => '',
];
$subjectEditForm = $subjectAddForm + ['original_code' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string) ($_POST['action'] ?? ''));

    if ($action === 'add_department') {
        $departmentAddForm = normalize_department_payload($_POST);
        $pageErrors = validate_department_payload($departmentAddForm, $structure);

        if ($pageErrors === []) {
            $structure['departments'][] = $departmentAddForm;
            save_academic_structure($structure);
            set_flash('success', 'Department added successfully.');
            redirect($selfAction);
        }

        $openModal = 'addDepartmentModal';
    } elseif ($action === 'update_department') {
        $originalCode = strtoupper(trim((string) ($_POST['original_code'] ?? '')));
        $departmentIndex = academic_structure_department_index($structure, $originalCode);

        if ($departmentIndex === null) {
            set_flash('error', 'The selected Department could not be found.');
            redirect($selfAction);
        }

        $departmentEditForm = normalize_department_payload($_POST) + ['original_code' => $originalCode];
        $pageErrors = validate_department_payload($departmentEditForm, $structure, $originalCode);

        if ($pageErrors === []) {
            $oldDepartment = $structure['departments'][$departmentIndex];
            $structure['departments'][$departmentIndex] = [
                'code' => $departmentEditForm['code'],
                'name' => $departmentEditForm['name'],
                'head' => $departmentEditForm['head'],
            ];

            foreach ($structure['courses'] as &$course) {
                if (($course['department_code'] ?? '') === $originalCode) {
                    $course['department_code'] = $departmentEditForm['code'];
                    $course['department_name'] = $departmentEditForm['name'];
                }
            }
            unset($course);

            foreach ($structure['sections'] as &$section) {
                if (($section['department_code'] ?? '') === $originalCode) {
                    $section['department_code'] = $departmentEditForm['code'];
                }
            }
            unset($section);

            foreach ($structure['subjects'] as &$subject) {
                if (($subject['department_code'] ?? '') === $originalCode) {
                    $subject['department_code'] = $departmentEditForm['code'];
                }
            }
            unset($subject);

            save_academic_structure($structure);
            set_flash('success', 'Department updated successfully.');
            redirect($selfAction);
        }

        $openModal = 'editDepartmentModal';
    } elseif ($action === 'delete_department') {
        $departmentCode = strtoupper(trim((string) ($_POST['code'] ?? '')));
        $departmentIndex = academic_structure_department_index($structure, $departmentCode);

        if ($departmentIndex === null) {
            set_flash('error', 'The selected Department could not be found.');
            redirect($selfAction);
        }

        $dependencies = academic_structure_department_dependencies($structure, $departmentCode);
        if ($dependencies !== []) {
            set_flash('error', 'Cannot delete this Department because it is still used by ' . implode(', ', $dependencies) . '.');
            redirect($selfAction);
        }

        array_splice($structure['departments'], $departmentIndex, 1);
        save_academic_structure($structure);
        set_flash('success', 'Department deleted successfully.');
        redirect($selfAction);
    } elseif ($action === 'add_course') {
        $courseAddForm = normalize_course_payload($_POST, $structure);
        $pageErrors = validate_course_payload($courseAddForm, $structure);

        if ($pageErrors === []) {
            $structure['courses'][] = build_course_record($courseAddForm, $structure);
            save_academic_structure($structure);
            set_flash('success', 'Course added successfully.');
            redirect($selfAction);
        }

        $openModal = 'addCourseModal';
    } elseif ($action === 'update_course') {
        $originalCode = strtoupper(trim((string) ($_POST['original_code'] ?? '')));
        $courseIndex = academic_structure_course_index($structure, $originalCode);

        if ($courseIndex === null) {
            set_flash('error', 'The selected Course could not be found.');
            redirect($selfAction);
        }

        $courseEditForm = normalize_course_payload($_POST, $structure) + ['original_code' => $originalCode];
        $pageErrors = validate_course_payload($courseEditForm, $structure, $originalCode);

        if ($pageErrors === []) {
            $oldCourse = $structure['courses'][$courseIndex];
            $structure['courses'][$courseIndex] = build_course_record($courseEditForm, $structure);

            foreach ($structure['sections'] as &$section) {
                if (($section['course_code'] ?? '') === $originalCode) {
                    $section['course_code'] = $courseEditForm['code'];
                    $section['department_code'] = $courseEditForm['department_code'];
                }
            }
            unset($section);

            foreach ($structure['subjects'] as &$subject) {
                $courseCodes = (array) ($subject['course_codes'] ?? []);
                if (in_array($originalCode, $courseCodes, true)) {
                    $subject['course_codes'] = array_values(array_map(
                        static fn (string $code): string => $code === $originalCode ? $courseEditForm['code'] : $code,
                        $courseCodes
                    ));
                    if (!(bool) ($subject['is_general_education'] ?? false)) {
                        $subject['department_code'] = $courseEditForm['department_code'];
                    }
                }
            }
            unset($subject);

            save_academic_structure($structure);
            set_flash('success', 'Course updated successfully.');
            redirect($selfAction);
        }

        $openModal = 'editCourseModal';
    } elseif ($action === 'delete_course') {
        $courseCode = strtoupper(trim((string) ($_POST['code'] ?? '')));
        $courseIndex = academic_structure_course_index($structure, $courseCode);

        if ($courseIndex === null) {
            set_flash('error', 'The selected Course could not be found.');
            redirect($selfAction);
        }

        $dependencies = academic_structure_course_dependencies($structure, $courseCode);
        if ($dependencies !== []) {
            set_flash('error', 'Cannot delete this Course because it is still used by ' . implode(', ', $dependencies) . '.');
            redirect($selfAction);
        }

        array_splice($structure['courses'], $courseIndex, 1);
        save_academic_structure($structure);
        set_flash('success', 'Course deleted successfully.');
        redirect($selfAction);
    } elseif ($action === 'add_sections') {
        $normalizedSectionPayload = normalize_section_payload($_POST);
        foreach ($sectionAddForm as $key => $value) {
            if ($key === 'academic_year') {
                $sectionAddForm[$key] = $normalizedSectionPayload[$key] ?? $value;
                continue;
            }
            if (str_starts_with($key, 'section_name_')) {
                $index = (int) substr($key, -1);
                $sectionAddForm[$key] = $normalizedSectionPayload['section_names'][$index - 1] ?? '';
            } else {
                $sectionAddForm[$key] = (string) ($normalizedSectionPayload[$key] ?? $value);
            }
        }
        $pageErrors = validate_section_payload($normalizedSectionPayload, $structure, true);

        if ($pageErrors === []) {
            foreach (array_filter($normalizedSectionPayload['section_names'], static fn (string $name): bool => $name !== '') as $name) {
                $structure['sections'][] = build_section_record($normalizedSectionPayload, $name);
            }
            save_academic_structure($structure);
            set_flash('success', 'Section records added successfully.');
            redirect($selfAction);
        }

        $openModal = 'addSectionModal';
    } elseif ($action === 'update_section') {
        $originalKey = [
            'name' => strtoupper(trim((string) ($_POST['original_name'] ?? ''))),
            'course_code' => strtoupper(trim((string) ($_POST['original_course_code'] ?? ''))),
            'year_level' => trim((string) ($_POST['original_year_level'] ?? '')),
            'semester' => trim((string) ($_POST['original_semester'] ?? '')),
            'academic_year' => trim((string) ($_POST['original_academic_year'] ?? '')),
        ];
        $sectionIndex = academic_structure_section_index(
            $structure,
            $originalKey['name'],
            $originalKey['course_code'],
            $originalKey['year_level'],
            $originalKey['semester'],
            $originalKey['academic_year']
        );

        if ($sectionIndex === null) {
            set_flash('error', 'The selected Section could not be found.');
            redirect($selfAction);
        }

        $normalizedSectionPayload = normalize_section_payload($_POST);
        $sectionEditForm = [
            'name' => $normalizedSectionPayload['name'],
            'department_code' => $normalizedSectionPayload['department_code'],
            'course_code' => $normalizedSectionPayload['course_code'],
            'year_level' => $normalizedSectionPayload['year_level'],
            'semester' => $normalizedSectionPayload['semester'],
            'academic_year' => $normalizedSectionPayload['academic_year'],
            'total_students' => (string) ($structure['sections'][$sectionIndex]['total_students'] ?? '0'),
            'original_name' => $originalKey['name'],
            'original_course_code' => $originalKey['course_code'],
            'original_year_level' => $originalKey['year_level'],
            'original_semester' => $originalKey['semester'],
            'original_academic_year' => $originalKey['academic_year'],
        ];

        $pageErrors = validate_section_payload($normalizedSectionPayload, $structure, false, $originalKey);

        if ($pageErrors === []) {
            $structure['sections'][$sectionIndex] = build_section_record(
                array_merge(
                    $normalizedSectionPayload,
                    ['total_students' => (string) ($structure['sections'][$sectionIndex]['total_students'] ?? '0')]
                ),
                $normalizedSectionPayload['name']
            );
            save_academic_structure($structure);
            set_flash('success', 'Section updated successfully.');
            redirect($selfAction);
        }

        $openModal = 'editSectionModal';
    } elseif ($action === 'delete_section') {
        $name = strtoupper(trim((string) ($_POST['name'] ?? '')));
        $courseCode = strtoupper(trim((string) ($_POST['course_code'] ?? '')));
        $yearLevel = trim((string) ($_POST['year_level'] ?? ''));
        $semester = trim((string) ($_POST['semester'] ?? ''));
        $academicYear = trim((string) ($_POST['academic_year'] ?? ''));
        $sectionIndex = academic_structure_section_index($structure, $name, $courseCode, $yearLevel, $semester, $academicYear);

        if ($sectionIndex === null) {
            set_flash('error', 'The selected Section could not be found.');
            redirect($selfAction);
        }

        array_splice($structure['sections'], $sectionIndex, 1);
        save_academic_structure($structure);
        set_flash('success', 'Section deleted successfully.');
        redirect($selfAction);
    } elseif ($action === 'add_subject') {
        $subjectAddForm = normalize_subject_payload($_POST);
        $pageErrors = validate_subject_payload($subjectAddForm, $structure);

        if ($pageErrors === []) {
            $structure['subjects'][] = build_subject_record($subjectAddForm);
            save_academic_structure($structure);
            set_flash('success', 'Subject added successfully.');
            redirect($selfAction);
        }

        $openModal = 'addSubjectModal';
    } elseif ($action === 'update_subject') {
        $originalCode = strtoupper(trim((string) ($_POST['original_code'] ?? '')));
        $subjectIndex = academic_structure_subject_index($structure, $originalCode);

        if ($subjectIndex === null) {
            set_flash('error', 'The selected Subject could not be found.');
            redirect($selfAction);
        }

        $subjectEditForm = normalize_subject_payload($_POST) + ['original_code' => $originalCode];
        $pageErrors = validate_subject_payload($subjectEditForm, $structure, $originalCode);

        if ($pageErrors === []) {
            $structure['subjects'][$subjectIndex] = build_subject_record($subjectEditForm);
            save_academic_structure($structure);
            set_flash('success', 'Subject updated successfully.');
            redirect($selfAction);
        }

        $openModal = 'editSubjectModal';
    } elseif ($action === 'delete_subject') {
        $subjectCode = strtoupper(trim((string) ($_POST['code'] ?? '')));
        $subjectIndex = academic_structure_subject_index($structure, $subjectCode);

        if ($subjectIndex === null) {
            set_flash('error', 'The selected Subject could not be found.');
            redirect($selfAction);
        }

        array_splice($structure['subjects'], $subjectIndex, 1);
        save_academic_structure($structure);
        set_flash('success', 'Subject deleted successfully.');
        redirect($selfAction);
    }

    $structure = load_academic_structure();
}

$departmentMap = academic_structure_department_map($structure);
$courseMap = academic_structure_course_map($structure);
$yearLevels = academic_structure_year_levels();
$semesters = academic_structure_semesters();
$yearLevelChoices = [1, 2, 3, 4, 5];

render_admin_layout_start(
    'Academic Structure',
    'structure',
    'Academic Structure',
    'Manage departments, courses, sections, and subjects'
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

<section class="structure-tabs" data-structure-tabs data-active-tab="<?= htmlspecialchars($activeTab, ENT_QUOTES, 'UTF-8') ?>">
    <?php foreach ([
        'departments' => ['label' => 'Departments', 'icon' => 'structure'],
        'courses' => ['label' => 'Courses', 'icon' => 'faculty'],
        'sections' => ['label' => 'Sections', 'icon' => 'students'],
        'subjects' => ['label' => 'Subjects', 'icon' => 'reports'],
    ] as $tabKey => $tabConfig): ?>
        <a
            href="academic_structure.php?tab=<?= rawurlencode($tabKey) ?>"
            class="structure-tab-button <?= $activeTab === $tabKey ? 'is-active' : '' ?>"
            data-structure-tab="<?= htmlspecialchars($tabKey, ENT_QUOTES, 'UTF-8') ?>"
        >
            <span class="structure-tab-icon"><?= admin_icon($tabConfig['icon']) ?></span>
            <span><?= htmlspecialchars($tabConfig['label'], ENT_QUOTES, 'UTF-8') ?></span>
        </a>
    <?php endforeach; ?>
</section>

<section class="structure-pane <?= $activeTab === 'departments' ? 'is-active' : '' ?>" data-structure-pane="departments">
    <div class="structure-pane-header">
        <div>
            <h2>Departments (<?= count($structure['departments']) ?>)</h2>
        </div>
        <button type="button" class="add-button" data-open-modal="#addDepartmentModal">
            <span class="button-icon"><?= admin_icon('plus') ?></span>
            Add Department
        </button>
    </div>

    <div class="structure-search-card">
        <div class="search-row">
            <span class="search-icon-inline"><?= admin_icon('search') ?></span>
            <input type="text" placeholder="Search by department code, name, or head..." data-structure-search="departments">
        </div>
    </div>

    <section class="table-card structure-table-card">
        <table class="students-table">
            <thead>
                <tr>
                    <th>Department Code</th>
                    <th>Department Name</th>
                    <th>Head</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody data-search-scope="departments">
                <?php foreach ($structure['departments'] as $department): ?>
                    <?php $departmentJson = htmlspecialchars(json_encode($department, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>
                    <tr data-search-item data-search-text="<?= htmlspecialchars(strtolower(implode(' ', [$department['code'], $department['name'], $department['head']])), ENT_QUOTES, 'UTF-8') ?>">
                        <td data-label="Department Code" class="table-id"><?= htmlspecialchars((string) $department['code'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="Department Name"><?= htmlspecialchars((string) $department['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="Head"><?= htmlspecialchars((string) $department['head'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-label="Actions">
                            <div class="action-buttons">
                                <button type="button" class="icon-button view" title="View Department" data-open-modal="#viewDepartmentModal" data-structure-entity="department" data-structure-action="view" data-structure-json="<?= $departmentJson ?>">
                                    <?= admin_icon('view') ?>
                                </button>
                                <button type="button" class="icon-button edit" title="Edit Department" data-open-modal="#editDepartmentModal" data-structure-entity="department" data-structure-action="edit" data-structure-json="<?= $departmentJson ?>">
                                    <?= admin_icon('edit') ?>
                                </button>
                                <form method="post" action="<?= htmlspecialchars($pageAction, ENT_QUOTES, 'UTF-8') ?>" class="inline-action-form">
                                    <input type="hidden" name="tab" value="departments">
                                    <input type="hidden" name="action" value="delete_department">
                                    <input type="hidden" name="code" value="<?= htmlspecialchars((string) $department['code'], ENT_QUOTES, 'UTF-8') ?>">
                                    <button type="submit" class="icon-submit delete" title="Delete Department" data-confirm-message="Delete this department? If it is used by other academic records, deletion will be blocked.">
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

<section class="structure-pane <?= $activeTab === 'courses' ? 'is-active' : '' ?>" data-structure-pane="courses">
    <div class="structure-pane-header">
        <div>
            <h2>Courses (<?= count($structure['courses']) ?>)</h2>
        </div>
        <button type="button" class="add-button" data-open-modal="#addCourseModal">
            <span class="button-icon"><?= admin_icon('plus') ?></span>
            Add Course
        </button>
    </div>

    <div class="structure-search-card">
        <div class="search-row">
            <span class="search-icon-inline"><?= admin_icon('search') ?></span>
            <input type="text" placeholder="Search by course code or name..." data-structure-search="courses">
        </div>
    </div>

    <div class="structure-card-list" data-search-scope="courses">
        <?php foreach ($structure['courses'] as $course): ?>
            <?php
            $courseJson = htmlspecialchars(json_encode($course, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');
            $courseSearch = strtolower(implode(' ', [$course['code'], $course['name'], $course['description'], $course['department_name']]));
            ?>
            <article class="structure-card course-card" data-search-item data-search-text="<?= htmlspecialchars($courseSearch, ENT_QUOTES, 'UTF-8') ?>">
                <div class="structure-card-actions">
                    <button type="button" class="icon-button view" title="View Course" data-open-modal="#viewCourseModal" data-structure-entity="course" data-structure-action="view" data-structure-json="<?= $courseJson ?>">
                        <?= admin_icon('view') ?>
                    </button>
                    <button type="button" class="icon-button edit" title="Edit Course" data-open-modal="#editCourseModal" data-structure-entity="course" data-structure-action="edit" data-structure-json="<?= $courseJson ?>">
                        <?= admin_icon('edit') ?>
                    </button>
                    <form method="post" action="<?= htmlspecialchars($pageAction, ENT_QUOTES, 'UTF-8') ?>" class="inline-action-form">
                        <input type="hidden" name="tab" value="courses">
                        <input type="hidden" name="action" value="delete_course">
                        <input type="hidden" name="code" value="<?= htmlspecialchars((string) $course['code'], ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="icon-submit delete" title="Delete Course" data-confirm-message="Delete this course? If it is used by sections or subjects, deletion will be blocked.">
                            <?= admin_icon('delete') ?>
                        </button>
                    </form>
                </div>
                <div class="course-card-heading">
                    <strong><?= htmlspecialchars((string) $course['code'], ENT_QUOTES, 'UTF-8') ?></strong>
                    <span class="status-badge <?= ($course['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active' ?>"><?= htmlspecialchars((string) $course['status'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <h3><?= htmlspecialchars((string) $course['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                <p><?= htmlspecialchars((string) $course['description'], ENT_QUOTES, 'UTF-8') ?></p>
                <div class="card-meta-row">
                    <span>Department: <?= htmlspecialchars((string) $course['department_name'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span>Year Levels: <?= (int) $course['year_levels'] ?></span>
                    <span>Students: <?= (int) $course['student_count'] ?></span>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="structure-pane <?= $activeTab === 'sections' ? 'is-active' : '' ?>" data-structure-pane="sections">
    <div class="structure-pane-header">
        <div>
            <h2>Sections (<?= count($structure['sections']) ?>)</h2>
        </div>
        <button type="button" class="add-button" data-open-modal="#addSectionModal">
            <span class="button-icon"><?= admin_icon('plus') ?></span>
            Add Section
        </button>
    </div>

    <div class="structure-search-card">
        <div class="search-row">
            <span class="search-icon-inline"><?= admin_icon('search') ?></span>
            <input type="text" placeholder="Search by section name, course, or year level..." data-structure-search="sections">
        </div>
    </div>

    <div class="structure-card-grid" data-search-scope="sections">
        <?php foreach ($structure['sections'] as $section): ?>
            <?php
            $sectionJson = htmlspecialchars(json_encode($section, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');
            $courseLabel = academic_structure_course_label($structure, (string) $section['course_code']);
            $departmentLabel = academic_structure_lookup_department_name($structure, (string) $section['department_code']);
            $sectionSearch = strtolower(implode(' ', [$section['name'], $section['course_code'], $courseLabel, $section['year_level'], $departmentLabel]));
            ?>
            <article class="structure-card section-card" data-search-item data-search-text="<?= htmlspecialchars($sectionSearch, ENT_QUOTES, 'UTF-8') ?>">
                <div class="structure-card-actions">
                    <button type="button" class="icon-button view" title="View Section" data-open-modal="#viewSectionModal" data-structure-entity="section" data-structure-action="view" data-structure-json="<?= $sectionJson ?>">
                        <?= admin_icon('view') ?>
                    </button>
                    <button type="button" class="icon-button edit" title="Edit Section" data-open-modal="#editSectionModal" data-structure-entity="section" data-structure-action="edit" data-structure-json="<?= $sectionJson ?>">
                        <?= admin_icon('edit') ?>
                    </button>
                    <form method="post" action="<?= htmlspecialchars($pageAction, ENT_QUOTES, 'UTF-8') ?>" class="inline-action-form">
                        <input type="hidden" name="tab" value="sections">
                        <input type="hidden" name="action" value="delete_section">
                        <input type="hidden" name="name" value="<?= htmlspecialchars((string) $section['name'], ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="course_code" value="<?= htmlspecialchars((string) $section['course_code'], ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="year_level" value="<?= htmlspecialchars((string) $section['year_level'], ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="semester" value="<?= htmlspecialchars((string) $section['semester'], ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="academic_year" value="<?= htmlspecialchars((string) $section['academic_year'], ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="icon-submit delete" title="Delete Section" data-confirm-message="Delete this section record?">
                            <?= admin_icon('delete') ?>
                        </button>
                    </form>
                </div>
                <h3><?= htmlspecialchars((string) $section['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                <div class="section-card-meta">
                    <span>Students: <?= (int) $section['total_students'] ?></span>
                    <span>Department: <?= htmlspecialchars($departmentLabel, ENT_QUOTES, 'UTF-8') ?></span>
                    <span>Course: <?= htmlspecialchars($courseLabel, ENT_QUOTES, 'UTF-8') ?></span>
                    <span>Year Level: <?= htmlspecialchars((string) $section['year_level'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span>Academic Year: <?= htmlspecialchars((string) $section['academic_year'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span>Semester: <?= htmlspecialchars((string) $section['semester'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="structure-pane <?= $activeTab === 'subjects' ? 'is-active' : '' ?>" data-structure-pane="subjects">
    <div class="structure-pane-header">
        <div>
            <h2>Subjects (<?= count($structure['subjects']) ?>)</h2>
        </div>
        <button type="button" class="add-button" data-open-modal="#addSubjectModal">
            <span class="button-icon"><?= admin_icon('plus') ?></span>
            Add Subject
        </button>
    </div>

    <div class="structure-search-card">
        <div class="search-row">
            <span class="search-icon-inline"><?= admin_icon('search') ?></span>
            <input type="text" placeholder="Search by subject code or title..." data-structure-search="subjects">
        </div>
    </div>

    <div class="structure-card-list" data-search-scope="subjects">
        <?php foreach ($structure['subjects'] as $subject): ?>
            <?php
            $subjectJson = htmlspecialchars(json_encode($subject, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');
            $subjectCourseLabel = (bool) ($subject['is_general_education'] ?? false)
                ? 'General Education'
                : implode(', ', array_map(static fn (string $code): string => $code, (array) ($subject['course_codes'] ?? [])));
            $subjectDepartmentLabel = academic_structure_lookup_department_name($structure, (string) $subject['department_code']);
            $subjectSearch = strtolower(implode(' ', [$subject['code'], $subject['title'], $subject['description'], $subjectCourseLabel]));
            ?>
            <article class="structure-card subject-card" data-search-item data-search-text="<?= htmlspecialchars($subjectSearch, ENT_QUOTES, 'UTF-8') ?>">
                <div class="structure-card-actions">
                    <button type="button" class="icon-button view" title="View Subject" data-open-modal="#viewSubjectModal" data-structure-entity="subject" data-structure-action="view" data-structure-json="<?= $subjectJson ?>">
                        <?= admin_icon('view') ?>
                    </button>
                    <button type="button" class="icon-button edit" title="Edit Subject" data-open-modal="#editSubjectModal" data-structure-entity="subject" data-structure-action="edit" data-structure-json="<?= $subjectJson ?>">
                        <?= admin_icon('edit') ?>
                    </button>
                    <form method="post" action="<?= htmlspecialchars($pageAction, ENT_QUOTES, 'UTF-8') ?>" class="inline-action-form">
                        <input type="hidden" name="tab" value="subjects">
                        <input type="hidden" name="action" value="delete_subject">
                        <input type="hidden" name="code" value="<?= htmlspecialchars((string) $subject['code'], ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="icon-submit delete" title="Delete Subject" data-confirm-message="Delete this subject record?">
                            <?= admin_icon('delete') ?>
                        </button>
                    </form>
                </div>
                <div class="subject-card-heading">
                    <strong><?= htmlspecialchars((string) $subject['code'], ENT_QUOTES, 'UTF-8') ?></strong>
                    <div class="subject-badges">
                        <span class="subject-badge units"><?= htmlspecialchars((string) $subject['units'], ENT_QUOTES, 'UTF-8') ?> units</span>
                        <span class="subject-badge semester"><?= htmlspecialchars((string) $subject['semester'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if (!empty($subject['is_general_education'])): ?>
                            <span class="subject-badge ge">GE</span>
                        <?php endif; ?>
                    </div>
                </div>
                <h3><?= htmlspecialchars((string) $subject['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                <p><?= htmlspecialchars((string) $subject['description'], ENT_QUOTES, 'UTF-8') ?></p>
                <div class="card-meta-row">
                    <span>Department: <?= htmlspecialchars($subjectDepartmentLabel, ENT_QUOTES, 'UTF-8') ?></span>
                    <span>Course: <?= htmlspecialchars($subjectCourseLabel, ENT_QUOTES, 'UTF-8') ?></span>
                    <span>Year Level: <?= htmlspecialchars((string) $subject['year_level'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<div class="modal<?= $openModal === 'addDepartmentModal' ? ' is-visible' : '' ?>" id="addDepartmentModal">
    <div class="modal-card structure-modal-card">
        <h3>Add New Department</h3>
        <?php if ($openModal === 'addDepartmentModal' && $pageErrors !== []): ?>
            <div class="alert alert-error"><?= htmlspecialchars(implode(' ', $pageErrors), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <form method="post" action="<?= htmlspecialchars($pageAction, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="tab" value="departments">
            <input type="hidden" name="action" value="add_department">
            <div class="modal-grid structure-modal-grid">
                <?php render_department_form_fields($departmentAddForm, false); ?>
            </div>
            <div class="modal-actions">
                <button type="button" class="secondary-button" data-close-modal>Cancel</button>
                <button type="submit" class="primary-button">Add Department</button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="viewDepartmentModal">
    <div class="modal-card structure-modal-card">
        <h3>Department Details</h3>
        <div class="modal-grid structure-modal-grid">
            <?php render_department_view_fields(); ?>
        </div>
        <div class="modal-actions">
            <button type="button" class="secondary-button" data-close-modal>Cancel</button>
            <button type="button" class="primary-button" data-structure-view-edit="department">Edit Information</button>
        </div>
    </div>
</div>

<div class="modal<?= $openModal === 'editDepartmentModal' ? ' is-visible' : '' ?>" id="editDepartmentModal">
    <div class="modal-card structure-modal-card">
        <h3>Edit Department</h3>
        <?php if ($openModal === 'editDepartmentModal' && $pageErrors !== []): ?>
            <div class="alert alert-error"><?= htmlspecialchars(implode(' ', $pageErrors), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <form method="post" action="<?= htmlspecialchars($pageAction, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="tab" value="departments">
            <input type="hidden" name="action" value="update_department">
            <input type="hidden" name="original_code" value="<?= htmlspecialchars((string) $departmentEditForm['original_code'], ENT_QUOTES, 'UTF-8') ?>">
            <div class="modal-grid structure-modal-grid">
                <?php render_department_form_fields($departmentEditForm, true); ?>
            </div>
            <div class="modal-actions">
                <button type="button" class="secondary-button" data-close-modal>Cancel</button>
                <button type="submit" class="primary-button">Update Department</button>
            </div>
        </form>
    </div>
</div>

<div class="modal<?= $openModal === 'addCourseModal' ? ' is-visible' : '' ?>" id="addCourseModal">
    <div class="modal-card structure-modal-card">
        <h3>Add New Course</h3>
        <?php if ($openModal === 'addCourseModal' && $pageErrors !== []): ?>
            <div class="alert alert-error"><?= htmlspecialchars(implode(' ', $pageErrors), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <form method="post" action="<?= htmlspecialchars($pageAction, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="tab" value="courses">
            <input type="hidden" name="action" value="add_course">
            <input type="hidden" name="student_count" value="<?= htmlspecialchars((string) $courseAddForm['student_count'], ENT_QUOTES, 'UTF-8') ?>">
            <div class="modal-grid structure-modal-grid">
                <?php render_course_form_fields($courseAddForm, $structure['departments'], $yearLevelChoices, false); ?>
            </div>
            <div class="modal-actions">
                <button type="button" class="secondary-button" data-close-modal>Cancel</button>
                <button type="submit" class="primary-button">Add Course</button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="viewCourseModal">
    <div class="modal-card structure-modal-card">
        <h3>Course Details</h3>
        <div class="modal-grid structure-modal-grid">
            <?php render_course_view_fields(); ?>
        </div>
        <div class="modal-actions">
            <button type="button" class="secondary-button" data-close-modal>Cancel</button>
            <button type="button" class="primary-button" data-structure-view-edit="course">Edit Information</button>
        </div>
    </div>
</div>

<div class="modal<?= $openModal === 'editCourseModal' ? ' is-visible' : '' ?>" id="editCourseModal">
    <div class="modal-card structure-modal-card">
        <h3>Edit Course</h3>
        <?php if ($openModal === 'editCourseModal' && $pageErrors !== []): ?>
            <div class="alert alert-error"><?= htmlspecialchars(implode(' ', $pageErrors), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <form method="post" action="<?= htmlspecialchars($pageAction, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="tab" value="courses">
            <input type="hidden" name="action" value="update_course">
            <input type="hidden" name="original_code" value="<?= htmlspecialchars((string) $courseEditForm['original_code'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="student_count" value="<?= htmlspecialchars((string) $courseEditForm['student_count'], ENT_QUOTES, 'UTF-8') ?>">
            <div class="modal-grid structure-modal-grid">
                <?php render_course_form_fields($courseEditForm, $structure['departments'], $yearLevelChoices, true); ?>
            </div>
            <div class="modal-actions">
                <button type="button" class="secondary-button" data-close-modal>Cancel</button>
                <button type="submit" class="primary-button">Update Course</button>
            </div>
        </form>
    </div>
</div>

<div class="modal<?= $openModal === 'addSectionModal' ? ' is-visible' : '' ?>" id="addSectionModal">
    <div class="modal-card structure-modal-card structure-modal-tall">
        <h3>Add New Section</h3>
        <p class="structure-modal-note">Add up to 5 sections at once. Fill in at least one section.</p>
        <?php if ($openModal === 'addSectionModal' && $pageErrors !== []): ?>
            <div class="alert alert-error"><?= htmlspecialchars(implode(' ', $pageErrors), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <form method="post" action="<?= htmlspecialchars($pageAction, ENT_QUOTES, 'UTF-8') ?>" data-structure-section-form>
            <input type="hidden" name="tab" value="sections">
            <input type="hidden" name="action" value="add_sections">
            <div class="modal-grid structure-modal-grid single-column">
                <?php render_section_add_fields($sectionAddForm, $structure['departments'], $structure['courses'], $yearLevels, $semesters); ?>
            </div>
            <div class="modal-actions">
                <button type="button" class="secondary-button" data-close-modal>Cancel</button>
                <button type="submit" class="primary-button">Add Sections</button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="viewSectionModal">
    <div class="modal-card structure-modal-card">
        <h3>Section Details</h3>
        <div class="modal-grid structure-modal-grid">
            <?php render_section_view_fields(); ?>
        </div>
        <div class="modal-actions">
            <button type="button" class="secondary-button" data-close-modal>Cancel</button>
            <button type="button" class="primary-button" data-structure-view-edit="section">Edit Information</button>
        </div>
    </div>
</div>

<div class="modal<?= $openModal === 'editSectionModal' ? ' is-visible' : '' ?>" id="editSectionModal">
    <div class="modal-card structure-modal-card">
        <h3>Edit Section</h3>
        <?php if ($openModal === 'editSectionModal' && $pageErrors !== []): ?>
            <div class="alert alert-error"><?= htmlspecialchars(implode(' ', $pageErrors), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <form method="post" action="<?= htmlspecialchars($pageAction, ENT_QUOTES, 'UTF-8') ?>" data-structure-section-edit-form>
            <input type="hidden" name="tab" value="sections">
            <input type="hidden" name="action" value="update_section">
            <input type="hidden" name="original_name" value="<?= htmlspecialchars((string) $sectionEditForm['original_name'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="original_course_code" value="<?= htmlspecialchars((string) $sectionEditForm['original_course_code'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="original_year_level" value="<?= htmlspecialchars((string) $sectionEditForm['original_year_level'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="original_semester" value="<?= htmlspecialchars((string) $sectionEditForm['original_semester'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="original_academic_year" value="<?= htmlspecialchars((string) $sectionEditForm['original_academic_year'], ENT_QUOTES, 'UTF-8') ?>">
            <div class="modal-grid structure-modal-grid">
                <?php render_section_edit_fields($sectionEditForm, $structure['departments'], $structure['courses'], $yearLevels, $semesters); ?>
            </div>
            <div class="modal-actions">
                <button type="button" class="secondary-button" data-close-modal>Cancel</button>
                <button type="submit" class="primary-button">Update Section</button>
            </div>
        </form>
    </div>
</div>

<div class="modal<?= $openModal === 'addSubjectModal' ? ' is-visible' : '' ?>" id="addSubjectModal">
    <div class="modal-card structure-modal-card structure-modal-tall">
        <h3>Add New Subject</h3>
        <?php if ($openModal === 'addSubjectModal' && $pageErrors !== []): ?>
            <div class="alert alert-error"><?= htmlspecialchars(implode(' ', $pageErrors), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <form method="post" action="<?= htmlspecialchars($pageAction, ENT_QUOTES, 'UTF-8') ?>" data-structure-subject-form>
            <input type="hidden" name="tab" value="subjects">
            <input type="hidden" name="action" value="add_subject">
            <div class="modal-grid structure-modal-grid">
                <?php render_subject_form_fields($subjectAddForm, $structure['departments'], $structure['courses'], $yearLevels, $semesters, false); ?>
            </div>
            <div class="modal-actions">
                <button type="button" class="secondary-button" data-close-modal>Cancel</button>
                <button type="submit" class="primary-button">Add Subject</button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="viewSubjectModal">
    <div class="modal-card structure-modal-card">
        <h3>Subject Details</h3>
        <div class="modal-grid structure-modal-grid">
            <?php render_subject_view_fields(); ?>
        </div>
        <div class="modal-actions">
            <button type="button" class="secondary-button" data-close-modal>Cancel</button>
            <button type="button" class="primary-button" data-structure-view-edit="subject">Edit Information</button>
        </div>
    </div>
</div>

<div class="modal<?= $openModal === 'editSubjectModal' ? ' is-visible' : '' ?>" id="editSubjectModal">
    <div class="modal-card structure-modal-card structure-modal-tall">
        <h3>Edit Subject</h3>
        <?php if ($openModal === 'editSubjectModal' && $pageErrors !== []): ?>
            <div class="alert alert-error"><?= htmlspecialchars(implode(' ', $pageErrors), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <form method="post" action="<?= htmlspecialchars($pageAction, ENT_QUOTES, 'UTF-8') ?>" data-structure-subject-edit-form>
            <input type="hidden" name="tab" value="subjects">
            <input type="hidden" name="action" value="update_subject">
            <input type="hidden" name="original_code" value="<?= htmlspecialchars((string) $subjectEditForm['original_code'], ENT_QUOTES, 'UTF-8') ?>">
            <div class="modal-grid structure-modal-grid">
                <?php render_subject_form_fields($subjectEditForm, $structure['departments'], $structure['courses'], $yearLevels, $semesters, true); ?>
            </div>
            <div class="modal-actions">
                <button type="button" class="secondary-button" data-close-modal>Cancel</button>
                <button type="submit" class="primary-button">Update Subject</button>
            </div>
        </form>
    </div>
</div>

<script>
window.ADMIN_ACADEMIC_STRUCTURE = <?= json_encode([
    'activeTab' => $activeTab,
    'departments' => $structure['departments'],
    'courses' => $structure['courses'],
    'sections' => $structure['sections'],
    'subjects' => $structure['subjects'],
], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<?php render_admin_layout_end(); ?>

<?php
function render_department_form_fields(array $values, bool $isEdit): void
{
    ?>
    <div class="modal-field full">
        <label for="<?= $isEdit ? 'edit' : 'add' ?>_department_code">Department Code</label>
        <input id="<?= $isEdit ? 'edit' : 'add' ?>_department_code" type="text" name="code" value="<?= htmlspecialchars((string) $values['code'], ENT_QUOTES, 'UTF-8') ?>" required placeholder="e.g., CCS" data-structure-field="department-code">
    </div>
    <div class="modal-field full">
        <label for="<?= $isEdit ? 'edit' : 'add' ?>_department_name">Department Name</label>
        <input id="<?= $isEdit ? 'edit' : 'add' ?>_department_name" type="text" name="name" value="<?= htmlspecialchars((string) $values['name'], ENT_QUOTES, 'UTF-8') ?>" required placeholder="e.g., College of Computer Studies (CCS)" data-structure-field="department-name">
    </div>
    <div class="modal-field full">
        <label for="<?= $isEdit ? 'edit' : 'add' ?>_department_head">Department Head</label>
        <input id="<?= $isEdit ? 'edit' : 'add' ?>_department_head" type="text" name="head" value="<?= htmlspecialchars((string) $values['head'], ENT_QUOTES, 'UTF-8') ?>" required placeholder="e.g., Dr. John Smith" data-structure-field="department-head">
    </div>
    <?php
}

function render_department_view_fields(): void
{
    foreach ([
        ['label' => 'Department Code', 'field' => 'code'],
        ['label' => 'Department Name', 'field' => 'name'],
        ['label' => 'Department Head', 'field' => 'head'],
    ] as $field) {
        ?>
        <div class="modal-field full">
            <label><?= htmlspecialchars($field['label'], ENT_QUOTES, 'UTF-8') ?></label>
            <input type="text" readonly data-structure-view-field="department-<?= htmlspecialchars($field['field'], ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <?php
    }
}

function render_course_form_fields(array $values, array $departments, array $yearLevelChoices, bool $isEdit): void
{
    ?>
    <div class="modal-field">
        <label for="<?= $isEdit ? 'edit' : 'add' ?>_course_code">Course Code</label>
        <input id="<?= $isEdit ? 'edit' : 'add' ?>_course_code" type="text" name="code" value="<?= htmlspecialchars((string) $values['code'], ENT_QUOTES, 'UTF-8') ?>" required placeholder="e.g., BSIT" data-structure-field="course-code">
    </div>
    <div class="modal-field">
        <label for="<?= $isEdit ? 'edit' : 'add' ?>_course_status">Status</label>
        <select id="<?= $isEdit ? 'edit' : 'add' ?>_course_status" name="status" data-structure-field="course-status">
            <option value="active" <?= ($values['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= ($values['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
    </div>
    <div class="modal-field full">
        <label for="<?= $isEdit ? 'edit' : 'add' ?>_course_name">Course Name</label>
        <input id="<?= $isEdit ? 'edit' : 'add' ?>_course_name" type="text" name="name" value="<?= htmlspecialchars((string) $values['name'], ENT_QUOTES, 'UTF-8') ?>" required placeholder="e.g., Bachelor of Science in Information Technology" data-structure-field="course-name">
    </div>
    <div class="modal-field full">
        <label for="<?= $isEdit ? 'edit' : 'add' ?>_course_description">Description</label>
        <textarea id="<?= $isEdit ? 'edit' : 'add' ?>_course_description" name="description" rows="4" placeholder="Brief description of the course" data-structure-field="course-description"><?= htmlspecialchars((string) $values['description'], ENT_QUOTES, 'UTF-8') ?></textarea>
    </div>
    <div class="modal-field full">
        <label for="<?= $isEdit ? 'edit' : 'add' ?>_course_department">Department</label>
        <select id="<?= $isEdit ? 'edit' : 'add' ?>_course_department" name="department_code" required data-structure-field="course-department">
            <option value="">Select Department</option>
            <?php foreach ($departments as $department): ?>
                <option value="<?= htmlspecialchars((string) $department['code'], ENT_QUOTES, 'UTF-8') ?>" <?= ($values['department_code'] ?? '') === ($department['code'] ?? '') ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string) $department['name'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="modal-field full">
        <label for="<?= $isEdit ? 'edit' : 'add' ?>_course_year_levels">Number of Year Levels</label>
        <select id="<?= $isEdit ? 'edit' : 'add' ?>_course_year_levels" name="year_levels" required data-structure-field="course-year-levels">
            <?php foreach ($yearLevelChoices as $choice): ?>
                <option value="<?= $choice ?>" <?= (string) ($values['year_levels'] ?? '4') === (string) $choice ? 'selected' : '' ?>><?= $choice ?> <?= $choice === 1 ? 'Year' : 'Years' ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php
}

function render_course_view_fields(): void
{
    foreach ([
        ['label' => 'Course Code', 'field' => 'code'],
        ['label' => 'Status', 'field' => 'status'],
        ['label' => 'Course Name', 'field' => 'name', 'full' => true],
        ['label' => 'Description', 'field' => 'description', 'full' => true],
        ['label' => 'Department', 'field' => 'department_name', 'full' => true],
        ['label' => 'Year Levels', 'field' => 'year_levels'],
        ['label' => 'Student Count', 'field' => 'student_count'],
    ] as $field) {
        $fullClass = !empty($field['full']) ? ' full' : '';
        ?>
        <div class="modal-field<?= $fullClass ?>">
            <label><?= htmlspecialchars($field['label'], ENT_QUOTES, 'UTF-8') ?></label>
            <input type="text" readonly data-structure-view-field="course-<?= htmlspecialchars($field['field'], ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <?php
    }
}

function render_section_add_fields(array $values, array $departments, array $courses, array $yearLevels, array $semesters): void
{
    for ($index = 1; $index <= 5; $index++) {
        ?>
        <div class="modal-field full">
            <label for="section_name_<?= $index ?>">Section <?= $index ?></label>
            <input id="section_name_<?= $index ?>" type="text" name="section_name_<?= $index ?>" value="<?= htmlspecialchars((string) ($values['section_name_' . $index] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="e.g., BSIT-2A" data-section-name-field>
        </div>
        <?php
    }

    render_section_shared_fields($values, $departments, $courses, $yearLevels, $semesters, 'add');
}

function render_section_edit_fields(array $values, array $departments, array $courses, array $yearLevels, array $semesters): void
{
    ?>
    <div class="modal-field full">
        <label for="edit_section_name">Section Name</label>
        <input id="edit_section_name" type="text" name="name" value="<?= htmlspecialchars((string) $values['name'], ENT_QUOTES, 'UTF-8') ?>" required placeholder="e.g., BSIT-2A" data-structure-field="section-name">
    </div>
    <?php

    render_section_shared_fields($values, $departments, $courses, $yearLevels, $semesters, 'edit');
}

function render_section_shared_fields(array $values, array $departments, array $courses, array $yearLevels, array $semesters, string $prefix): void
{
    ?>
    <div class="modal-field full">
        <label for="<?= $prefix ?>_section_department">Department</label>
        <select id="<?= $prefix ?>_section_department" name="department_code" required data-structure-course-filter="section">
            <option value="">Select Department</option>
            <?php foreach ($departments as $department): ?>
                <option value="<?= htmlspecialchars((string) $department['code'], ENT_QUOTES, 'UTF-8') ?>" <?= ($values['department_code'] ?? '') === ($department['code'] ?? '') ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string) $department['name'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="modal-field full">
        <label for="<?= $prefix ?>_section_course">Course</label>
        <select id="<?= $prefix ?>_section_course" name="course_code" required data-structure-course-select="section">
            <option value="">Select Course</option>
            <?php foreach ($courses as $course): ?>
                <option value="<?= htmlspecialchars((string) $course['code'], ENT_QUOTES, 'UTF-8') ?>" data-department-code="<?= htmlspecialchars((string) $course['department_code'], ENT_QUOTES, 'UTF-8') ?>" <?= ($values['course_code'] ?? '') === ($course['code'] ?? '') ? 'selected' : '' ?>>
                    <?= htmlspecialchars(academic_structure_course_label(['courses' => $courses], (string) $course['code']), ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="modal-field">
        <label for="<?= $prefix ?>_section_year_level">Year Level</label>
        <select id="<?= $prefix ?>_section_year_level" name="year_level" required data-structure-field="section-year-level">
            <option value="">Select Year Level</option>
            <?php foreach ($yearLevels as $yearLevel): ?>
                <option value="<?= htmlspecialchars($yearLevel, ENT_QUOTES, 'UTF-8') ?>" <?= ($values['year_level'] ?? '') === $yearLevel ? 'selected' : '' ?>><?= htmlspecialchars($yearLevel, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="modal-field">
        <label for="<?= $prefix ?>_section_semester">Semester</label>
        <select id="<?= $prefix ?>_section_semester" name="semester" required data-structure-field="section-semester">
            <option value="">Select Semester</option>
            <?php foreach ($semesters as $semester): ?>
                <option value="<?= htmlspecialchars($semester, ENT_QUOTES, 'UTF-8') ?>" <?= ($values['semester'] ?? '') === $semester ? 'selected' : '' ?>><?= htmlspecialchars($semester, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="modal-field full">
        <label for="<?= $prefix ?>_section_academic_year">Academic Year</label>
        <input id="<?= $prefix ?>_section_academic_year" type="text" name="academic_year" value="<?= htmlspecialchars((string) $values['academic_year'], ENT_QUOTES, 'UTF-8') ?>" required placeholder="e.g., 2025-2026" data-structure-field="section-academic-year">
    </div>
    <?php
}

function render_section_view_fields(): void
{
    foreach ([
        ['label' => 'Section Name', 'field' => 'name', 'full' => true],
        ['label' => 'Department', 'field' => 'department_label', 'full' => true],
        ['label' => 'Course', 'field' => 'course_label'],
        ['label' => 'Year Level', 'field' => 'year_level'],
        ['label' => 'Academic Year', 'field' => 'academic_year'],
        ['label' => 'Semester', 'field' => 'semester'],
        ['label' => 'Total Students', 'field' => 'total_students', 'full' => true],
    ] as $field) {
        $fullClass = !empty($field['full']) ? ' full' : '';
        ?>
        <div class="modal-field<?= $fullClass ?>">
            <label><?= htmlspecialchars($field['label'], ENT_QUOTES, 'UTF-8') ?></label>
            <input type="text" readonly data-structure-view-field="section-<?= htmlspecialchars($field['field'], ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <?php
    }
}

function render_subject_form_fields(array $values, array $departments, array $courses, array $yearLevels, array $semesters, bool $isEdit): void
{
    $prefix = $isEdit ? 'edit' : 'add';
    ?>
    <div class="modal-field">
        <label for="<?= $prefix ?>_subject_code">Subject Code</label>
        <input id="<?= $prefix ?>_subject_code" type="text" name="code" value="<?= htmlspecialchars((string) $values['code'], ENT_QUOTES, 'UTF-8') ?>" required placeholder="e.g., IT 221" data-structure-field="subject-code">
    </div>
    <div class="modal-field">
        <label for="<?= $prefix ?>_subject_units">Units</label>
        <input id="<?= $prefix ?>_subject_units" type="number" min="1" max="9" name="units" value="<?= htmlspecialchars((string) $values['units'], ENT_QUOTES, 'UTF-8') ?>" required placeholder="3" data-structure-field="subject-units">
    </div>
    <div class="modal-field full">
        <label for="<?= $prefix ?>_subject_title">Subject Title</label>
        <input id="<?= $prefix ?>_subject_title" type="text" name="title" value="<?= htmlspecialchars((string) $values['title'], ENT_QUOTES, 'UTF-8') ?>" required placeholder="e.g., Human Computer Interaction" data-structure-field="subject-title">
    </div>
    <div class="modal-field full">
        <label for="<?= $prefix ?>_subject_description">Description</label>
        <textarea id="<?= $prefix ?>_subject_description" name="description" rows="4" required placeholder="Brief description of the subject" data-structure-field="subject-description"><?= htmlspecialchars((string) $values['description'], ENT_QUOTES, 'UTF-8') ?></textarea>
    </div>
    <div class="modal-field full modal-checkbox-field">
        <label class="checkbox-inline">
            <input type="checkbox" name="is_general_education" value="1" <?= !empty($values['is_general_education']) ? 'checked' : '' ?> data-ge-toggle>
            <span>General Education (applies to all courses)</span>
        </label>
    </div>
    <div class="modal-field full" data-subject-department-wrap>
        <label for="<?= $prefix ?>_subject_department">Department</label>
        <select id="<?= $prefix ?>_subject_department" name="department_code" data-structure-course-filter="subject">
            <option value="">Select Department</option>
            <?php foreach ($departments as $department): ?>
                <option value="<?= htmlspecialchars((string) $department['code'], ENT_QUOTES, 'UTF-8') ?>" <?= ($values['department_code'] ?? '') === ($department['code'] ?? '') ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string) $department['name'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="modal-field full" data-subject-courses-wrap>
        <label>Course(s)</label>
        <div class="course-checklist" data-structure-course-checklist>
            <?php foreach ($courses as $course): ?>
                <label class="course-checklist-item" data-department-code="<?= htmlspecialchars((string) $course['department_code'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="checkbox" name="course_codes[]" value="<?= htmlspecialchars((string) $course['code'], ENT_QUOTES, 'UTF-8') ?>" <?= in_array((string) $course['code'], (array) ($values['course_codes'] ?? []), true) ? 'checked' : '' ?>>
                    <span><?= htmlspecialchars(academic_structure_course_label(['courses' => $courses], (string) $course['code']), ENT_QUOTES, 'UTF-8') ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="modal-field">
        <label for="<?= $prefix ?>_subject_year_level">Year Level</label>
        <select id="<?= $prefix ?>_subject_year_level" name="year_level" required data-structure-field="subject-year-level">
            <option value="">Select Year Level</option>
            <?php foreach ($yearLevels as $yearLevel): ?>
                <option value="<?= htmlspecialchars($yearLevel, ENT_QUOTES, 'UTF-8') ?>" <?= ($values['year_level'] ?? '') === $yearLevel ? 'selected' : '' ?>><?= htmlspecialchars($yearLevel, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="modal-field">
        <label for="<?= $prefix ?>_subject_semester">Semester</label>
        <select id="<?= $prefix ?>_subject_semester" name="semester" required data-structure-field="subject-semester">
            <option value="">Select Semester</option>
            <?php foreach ($semesters as $semester): ?>
                <option value="<?= htmlspecialchars($semester, ENT_QUOTES, 'UTF-8') ?>" <?= ($values['semester'] ?? '') === $semester ? 'selected' : '' ?>><?= htmlspecialchars($semester, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php
}

function render_subject_view_fields(): void
{
    foreach ([
        ['label' => 'Subject Code', 'field' => 'code'],
        ['label' => 'Units', 'field' => 'units'],
        ['label' => 'Subject Title', 'field' => 'title', 'full' => true],
        ['label' => 'Description', 'field' => 'description', 'full' => true],
        ['label' => 'Department', 'field' => 'department_label', 'full' => true],
        ['label' => 'Course(s)', 'field' => 'course_labels'],
        ['label' => 'Year Level', 'field' => 'year_level'],
        ['label' => 'Semester', 'field' => 'semester', 'full' => true],
    ] as $field) {
        $fullClass = !empty($field['full']) ? ' full' : '';
        ?>
        <div class="modal-field<?= $fullClass ?>">
            <label><?= htmlspecialchars($field['label'], ENT_QUOTES, 'UTF-8') ?></label>
            <input type="text" readonly data-structure-view-field="subject-<?= htmlspecialchars($field['field'], ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <?php
    }
}
?>
