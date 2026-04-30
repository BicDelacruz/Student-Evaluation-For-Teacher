<?php
declare(strict_types=1);

require_once __DIR__ . '/academic_structure.php';

const ADMIN_ASSIGNMENT_MANAGEMENT_FILE = __DIR__ . '/../data/assignment_management.json';

function assignment_management_tabs(): array
{
    return ['faculty_to_subject', 'section_to_subject'];
}

function normalize_assignment_tab(?string $tab): string
{
    $tab = strtolower(trim((string) $tab));
    return in_array($tab, assignment_management_tabs(), true) ? $tab : 'faculty_to_subject';
}

function load_assignment_management(): array
{
    if (!file_exists(ADMIN_ASSIGNMENT_MANAGEMENT_FILE)) {
        $default = default_assignment_management();
        save_assignment_management($default);
        return $default;
    }

    $raw = file_get_contents(ADMIN_ASSIGNMENT_MANAGEMENT_FILE);
    if ($raw === false || trim($raw) === '') {
        $default = default_assignment_management();
        save_assignment_management($default);
        return $default;
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        $default = default_assignment_management();
        save_assignment_management($default);
        return $default;
    }

    if (!isset($data['faculty_assignments']) || !is_array($data['faculty_assignments'])) {
        $default = default_assignment_management();
        save_assignment_management($default);
        return $default;
    }

    if (!isset($data['section_enrollments']) || !is_array($data['section_enrollments'])) {
        $default = default_assignment_management();
        save_assignment_management($default);
        return $default;
    }

    return [
        'faculty_assignments' => array_values(array_filter($data['faculty_assignments'], 'is_array')),
        'section_enrollments' => array_values(array_filter($data['section_enrollments'], 'is_array')),
    ];
}

function save_assignment_management(array $data): void
{
    $payload = [
        'faculty_assignments' => array_values($data['faculty_assignments'] ?? []),
        'section_enrollments' => array_values($data['section_enrollments'] ?? []),
    ];

    usort($payload['faculty_assignments'], static function (array $left, array $right): int {
        $leftKey = implode('|', [
            (string) ($left['academic_year'] ?? ''),
            (string) ($left['semester'] ?? ''),
            (string) ($left['faculty_id'] ?? ''),
            (string) ($left['subject_code'] ?? ''),
        ]);
        $rightKey = implode('|', [
            (string) ($right['academic_year'] ?? ''),
            (string) ($right['semester'] ?? ''),
            (string) ($right['faculty_id'] ?? ''),
            (string) ($right['subject_code'] ?? ''),
        ]);

        return strcmp($leftKey, $rightKey);
    });

    usort($payload['section_enrollments'], static function (array $left, array $right): int {
        $leftKey = implode('|', [
            (string) ($left['academic_year'] ?? ''),
            (string) ($left['semester'] ?? ''),
            (string) ($left['course_code'] ?? ''),
            (string) ($left['section_name'] ?? ''),
        ]);
        $rightKey = implode('|', [
            (string) ($right['academic_year'] ?? ''),
            (string) ($right['semester'] ?? ''),
            (string) ($right['course_code'] ?? ''),
            (string) ($right['section_name'] ?? ''),
        ]);

        return strcmp($leftKey, $rightKey);
    });

    file_put_contents(
        ADMIN_ASSIGNMENT_MANAGEMENT_FILE,
        json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        LOCK_EX
    );
}

function default_assignment_management(): array
{
    return [
        'faculty_assignments' => [
            [
                'id' => 'fa_seed_001',
                'department_code' => 'CCS',
                'faculty_id' => 'FAC0001',
                'subject_code' => 'IT 221',
                'course_codes' => ['BSIT'],
                'is_general_education' => false,
                'year_level' => '2nd Year',
                'semester' => '2nd Semester',
                'academic_year' => '2025-2026',
            ],
            [
                'id' => 'fa_seed_002',
                'department_code' => 'CCS',
                'faculty_id' => 'FAC0002',
                'subject_code' => 'IT 121',
                'course_codes' => ['BSIT'],
                'is_general_education' => false,
                'year_level' => '1st Year',
                'semester' => '2nd Semester',
                'academic_year' => '2025-2026',
            ],
            [
                'id' => 'fa_seed_003',
                'department_code' => 'CCS',
                'faculty_id' => 'FAC0002',
                'subject_code' => 'CS 121',
                'course_codes' => ['BSCS'],
                'is_general_education' => false,
                'year_level' => '2nd Year',
                'semester' => '1st Semester',
                'academic_year' => '2025-2026',
            ],
            [
                'id' => 'fa_seed_004',
                'department_code' => 'CCS',
                'faculty_id' => 'FAC0004',
                'subject_code' => 'CS 321',
                'course_codes' => ['BSCS'],
                'is_general_education' => false,
                'year_level' => '3rd Year',
                'semester' => '2nd Semester',
                'academic_year' => '2025-2026',
            ],
            [
                'id' => 'fa_seed_005',
                'department_code' => 'COED',
                'faculty_id' => 'FAC0015',
                'subject_code' => 'GE 201',
                'course_codes' => [],
                'is_general_education' => true,
                'year_level' => '2nd Year',
                'semester' => '2nd Semester',
                'academic_year' => '2025-2026',
            ],
        ],
        'section_enrollments' => [
            [
                'id' => 'se_seed_001',
                'department_code' => 'CCS',
                'course_code' => 'BSIT',
                'section_name' => 'BSIT-2A',
                'year_level' => '2nd Year',
                'semester' => '2nd Semester',
                'subject_type' => 'program_and_ge',
                'subject_codes' => ['IT 221', 'GE 201'],
                'academic_year' => '2025-2026',
                'status' => 'active',
            ],
            [
                'id' => 'se_seed_002',
                'department_code' => 'CCS',
                'course_code' => 'BSIT',
                'section_name' => 'BSIT-2B',
                'year_level' => '2nd Year',
                'semester' => '2nd Semester',
                'subject_type' => 'program_and_ge',
                'subject_codes' => ['IT 221', 'GE 201'],
                'academic_year' => '2025-2026',
                'status' => 'active',
            ],
            [
                'id' => 'se_seed_003',
                'department_code' => 'CCS',
                'course_code' => 'BSCS',
                'section_name' => 'BSCS-3A',
                'year_level' => '3rd Year',
                'semester' => '2nd Semester',
                'subject_type' => 'program_only',
                'subject_codes' => ['CS 321'],
                'academic_year' => '2025-2026',
                'status' => 'active',
            ],
            [
                'id' => 'se_seed_004',
                'department_code' => 'CBA',
                'course_code' => 'BSBA',
                'section_name' => 'BSBA-1A',
                'year_level' => '1st Year',
                'semester' => '2nd Semester',
                'subject_type' => 'program_only',
                'subject_codes' => ['BA 121'],
                'academic_year' => '2025-2026',
                'status' => 'active',
            ],
            [
                'id' => 'se_seed_005',
                'department_code' => 'CCS',
                'course_code' => 'BSIT',
                'section_name' => 'BSIT-1A',
                'year_level' => '1st Year',
                'semester' => '2nd Semester',
                'subject_type' => 'program_and_ge',
                'subject_codes' => ['IT 121'],
                'academic_year' => '2025-2026',
                'status' => 'active',
            ],
            [
                'id' => 'se_seed_006',
                'department_code' => 'CCS',
                'course_code' => 'BSIT',
                'section_name' => 'BSIT-1B',
                'year_level' => '1st Year',
                'semester' => '2nd Semester',
                'subject_type' => 'program_and_ge',
                'subject_codes' => ['IT 121'],
                'academic_year' => '2025-2026',
                'status' => 'active',
            ],
            [
                'id' => 'se_seed_007',
                'department_code' => 'CCS',
                'course_code' => 'BSCS',
                'section_name' => 'BSCS-2A',
                'year_level' => '2nd Year',
                'semester' => '2nd Semester',
                'subject_type' => 'program_and_ge',
                'subject_codes' => ['CS 221', 'GE 201'],
                'academic_year' => '2025-2026',
                'status' => 'active',
            ],
        ],
    ];
}

function assignment_management_reference_data(): array
{
    $structure = load_academic_structure();
    $faculty = load_faculty();

    return [
        'structure' => $structure,
        'faculty' => $faculty,
        'faculty_map' => assignment_management_faculty_map($faculty),
        'subject_map' => assignment_management_subject_map($structure),
        'section_map' => assignment_management_section_map($structure),
        'course_map' => academic_structure_course_map($structure),
        'department_map' => academic_structure_department_map($structure),
    ];
}

function assignment_management_faculty_map(array $faculty): array
{
    $map = [];
    foreach ($faculty as $record) {
        $id = (string) ($record['faculty_id'] ?? '');
        if ($id !== '') {
            $map[$id] = $record;
        }
    }

    return $map;
}

function assignment_management_subject_map(array $structure): array
{
    $map = [];
    foreach ($structure['subjects'] ?? [] as $subject) {
        $code = (string) ($subject['code'] ?? '');
        if ($code !== '') {
            $map[$code] = $subject;
        }
    }

    return $map;
}

function assignment_management_section_map(array $structure): array
{
    $map = [];
    foreach ($structure['sections'] ?? [] as $section) {
        $name = (string) ($section['name'] ?? '');
        if ($name !== '') {
            $map[$name] = $section;
        }
    }

    return $map;
}

function assignment_management_active_faculty(array $faculty): array
{
    return array_values(array_filter($faculty, static function (array $record): bool {
        return (($record['status'] ?? 'active') === 'active');
    }));
}

function assignment_management_active_courses(array $structure): array
{
    return array_values(array_filter($structure['courses'] ?? [], static function (array $course): bool {
        return (($course['status'] ?? 'active') === 'active');
    }));
}

function assignment_management_next_id(string $prefix): string
{
    return $prefix . '_' . substr(md5(uniqid($prefix, true)), 0, 12);
}

function assignment_management_faculty_assignment_index(array $data, string $id): ?int
{
    foreach ($data['faculty_assignments'] as $index => $record) {
        if (($record['id'] ?? '') === $id) {
            return $index;
        }
    }

    return null;
}

function assignment_management_section_enrollment_index(array $data, string $id): ?int
{
    foreach ($data['section_enrollments'] as $index => $record) {
        if (($record['id'] ?? '') === $id) {
            return $index;
        }
    }

    return null;
}

function normalize_faculty_assignment_payload(array $source): array
{
    $courseCodes = array_map(
        static fn (mixed $value): string => strtoupper(trim((string) $value)),
        (array) ($source['course_codes'] ?? [])
    );
    $courseCodes = array_values(array_filter(array_unique($courseCodes), static fn (string $value): bool => $value !== ''));
    $isGeneralEducation = !empty($source['is_general_education']);

    return [
        'id' => trim((string) ($source['id'] ?? '')),
        'department_code' => strtoupper(trim((string) ($source['department_code'] ?? ''))),
        'faculty_id' => trim((string) ($source['faculty_id'] ?? '')),
        'subject_code' => strtoupper(trim((string) ($source['subject_code'] ?? ''))),
        'course_codes' => $isGeneralEducation ? [] : $courseCodes,
        'is_general_education' => $isGeneralEducation,
        'year_level' => trim((string) ($source['year_level'] ?? '')),
        'semester' => trim((string) ($source['semester'] ?? '')),
        'academic_year' => trim((string) ($source['academic_year'] ?? '')),
    ];
}

function validate_faculty_assignment_payload(array $payload, array $data, array $references, ?string $originalId = null): array
{
    $errors = [];
    $structure = $references['structure'];
    $facultyMap = $references['faculty_map'];
    $subject = $references['subject_map'][$payload['subject_code']] ?? null;

    if ($payload['department_code'] === '') {
        $errors[] = 'Department is required.';
    }
    if ($payload['faculty_id'] === '') {
        $errors[] = 'Faculty Member is required.';
    }
    if ($payload['subject_code'] === '') {
        $errors[] = 'Subject is required.';
    }
    if ($payload['academic_year'] === '') {
        $errors[] = 'Academic Year is required.';
    }

    if (!$payload['is_general_education'] && $payload['course_codes'] === []) {
        $errors[] = 'Select at least one Course unless General Education is checked.';
    }

    if ($payload['department_code'] !== '' && $payload['department_code'] !== 'ALL' && academic_structure_department_index($structure, $payload['department_code']) === null) {
        $errors[] = 'Please select a valid Department.';
    }

    if ($payload['faculty_id'] !== '' && !isset($facultyMap[$payload['faculty_id']])) {
        $errors[] = 'Please select a valid Faculty Member.';
    }

    if ($subject === null) {
        $errors[] = 'Please select a valid Subject.';
    } else {
        if (($payload['year_level'] !== '' && $payload['year_level'] !== ($subject['year_level'] ?? ''))
            || ($payload['semester'] !== '' && $payload['semester'] !== ($subject['semester'] ?? ''))) {
            $errors[] = 'Year Level and Semester must match the selected Subject.';
        }

        if ($payload['is_general_education'] && empty($subject['is_general_education'])) {
            $errors[] = 'Only General Education subjects can be used when General Education is checked.';
        }

        if (!$payload['is_general_education']) {
            if (!empty($subject['is_general_education'])) {
                $errors[] = 'General Education subjects require the General Education option.';
            }

            foreach ($payload['course_codes'] as $courseCode) {
                $course = academic_structure_find_by_code($structure['courses'], 'code', $courseCode);
                if ($course === null) {
                    $errors[] = 'One or more selected Courses are invalid.';
                    continue;
                }

                if (($course['department_code'] ?? '') !== $payload['department_code']) {
                    $errors[] = 'Selected Courses must belong to the chosen Department.';
                }

                if (!in_array($courseCode, (array) ($subject['course_codes'] ?? []), true)) {
                    $errors[] = 'Selected Subject does not belong to one or more chosen Courses.';
                }
            }
        }
    }

    if ($payload['academic_year'] !== '' && !preg_match('/^\d{4}-\d{4}$/', $payload['academic_year'])) {
        $errors[] = 'Academic Year must use the format YYYY-YYYY.';
    }

    $signatureCourses = $payload['course_codes'];
    sort($signatureCourses);
    foreach ($data['faculty_assignments'] as $record) {
        $sameRecord = $originalId !== null && ($record['id'] ?? '') === $originalId;
        if ($sameRecord) {
            continue;
        }

        $recordCourses = array_map(static fn (mixed $value): string => strtoupper((string) $value), (array) ($record['course_codes'] ?? []));
        sort($recordCourses);

        if (
            ($record['faculty_id'] ?? '') === $payload['faculty_id']
            && ($record['subject_code'] ?? '') === $payload['subject_code']
            && ($record['semester'] ?? '') === $payload['semester']
            && ($record['academic_year'] ?? '') === $payload['academic_year']
            && $recordCourses === $signatureCourses
        ) {
            $errors[] = 'This faculty assignment already exists.';
        }
    }

    return array_values(array_unique($errors));
}

function build_faculty_assignment_record(array $payload, array $references, ?array $existing = null): array
{
    $subject = $references['subject_map'][$payload['subject_code']] ?? null;

    return [
        'id' => $existing['id'] ?? ($payload['id'] !== '' ? $payload['id'] : assignment_management_next_id('fa')),
        'department_code' => $payload['department_code'],
        'faculty_id' => $payload['faculty_id'],
        'subject_code' => $payload['subject_code'],
        'course_codes' => $payload['is_general_education'] ? [] : array_values($payload['course_codes']),
        'is_general_education' => (bool) $payload['is_general_education'],
        'year_level' => (string) ($subject['year_level'] ?? $payload['year_level']),
        'semester' => (string) ($subject['semester'] ?? $payload['semester']),
        'academic_year' => $payload['academic_year'],
    ];
}

function normalize_section_enrollment_payload(array $source): array
{
    $sectionNames = array_map(
        static fn (mixed $value): string => strtoupper(trim((string) $value)),
        (array) ($source['section_names'] ?? [])
    );
    $sectionNames = array_values(array_filter(array_unique($sectionNames), static fn (string $value): bool => $value !== ''));

    $subjectCodes = array_map(
        static fn (mixed $value): string => strtoupper(trim((string) $value)),
        (array) ($source['subject_codes'] ?? [])
    );
    $subjectCodes = array_values(array_filter(array_unique($subjectCodes), static fn (string $value): bool => $value !== ''));

    return [
        'id' => trim((string) ($source['id'] ?? '')),
        'department_code' => strtoupper(trim((string) ($source['department_code'] ?? ''))),
        'course_code' => strtoupper(trim((string) ($source['course_code'] ?? ''))),
        'year_level' => trim((string) ($source['year_level'] ?? '')),
        'semester' => trim((string) ($source['semester'] ?? '')),
        'section_names' => $sectionNames,
        'section_name' => strtoupper(trim((string) ($source['section_name'] ?? ''))),
        'subject_type' => trim((string) ($source['subject_type'] ?? 'program_only')),
        'subject_codes' => $subjectCodes,
        'academic_year' => trim((string) ($source['academic_year'] ?? '')),
        'status' => trim((string) ($source['status'] ?? 'active')) ?: 'active',
    ];
}

function validate_section_enrollment_payload(array $payload, array $data, array $references, bool $isBulk, ?string $originalId = null): array
{
    $errors = [];
    $structure = $references['structure'];
    $sectionMap = $references['section_map'];
    $subjectMap = $references['subject_map'];
    $sectionNames = $isBulk ? $payload['section_names'] : [$payload['section_name']];
    $sectionNames = array_values(array_filter($sectionNames, static fn (string $value): bool => $value !== ''));

    if ($payload['department_code'] === '') {
        $errors[] = 'Department is required.';
    }
    if ($payload['course_code'] === '') {
        $errors[] = 'Course is required.';
    }
    if ($payload['year_level'] === '') {
        $errors[] = 'Year Level is required.';
    }
    if ($payload['semester'] === '') {
        $errors[] = 'Semester is required.';
    }
    if ($payload['academic_year'] === '') {
        $errors[] = 'Academic Year is required.';
    }
    if ($sectionNames === []) {
        $errors[] = 'Select at least one Section.';
    }
    if ($payload['subject_codes'] === []) {
        $errors[] = 'Select at least one Subject.';
    }

    if ($payload['department_code'] !== '' && academic_structure_department_index($structure, $payload['department_code']) === null) {
        $errors[] = 'Please select a valid Department.';
    }

    $course = academic_structure_find_by_code($structure['courses'], 'code', $payload['course_code']);
    if ($course === null) {
        $errors[] = 'Please select a valid Course.';
    } elseif (($course['department_code'] ?? '') !== $payload['department_code']) {
        $errors[] = 'Selected Course does not belong to the selected Department.';
    }

    if ($payload['subject_type'] === '') {
        $errors[] = 'Subject Type is required.';
    }

    if ($payload['academic_year'] !== '' && !preg_match('/^\d{4}-\d{4}$/', $payload['academic_year'])) {
        $errors[] = 'Academic Year must use the format YYYY-YYYY.';
    }

    foreach ($sectionNames as $sectionName) {
        $section = $sectionMap[$sectionName] ?? null;
        if ($section === null) {
            $errors[] = 'One or more selected Sections are invalid.';
            continue;
        }

        if (($section['course_code'] ?? '') !== $payload['course_code'] || ($section['year_level'] ?? '') !== $payload['year_level']) {
            $errors[] = 'Selected Sections must belong to the chosen Course and Year Level.';
        }
    }

    foreach ($payload['subject_codes'] as $subjectCode) {
        $subject = $subjectMap[$subjectCode] ?? null;
        if ($subject === null) {
            $errors[] = 'One or more selected Subjects are invalid.';
            continue;
        }

        if (($subject['semester'] ?? '') !== $payload['semester']) {
            $errors[] = 'Selected Subjects must match the chosen Semester.';
        }

        if (($subject['year_level'] ?? '') !== $payload['year_level']) {
            $errors[] = 'Selected Subjects must match the chosen Year Level.';
        }

        $isGe = !empty($subject['is_general_education']);
        $matchesType = match ($payload['subject_type']) {
            'program_only' => !$isGe,
            'ge_only' => $isGe,
            'program_and_ge' => true,
            default => false,
        };

        if (!$matchesType) {
            $errors[] = 'Selected Subjects do not match the chosen Subject Type.';
        }

        if (!$isGe && !in_array($payload['course_code'], (array) ($subject['course_codes'] ?? []), true)) {
            $errors[] = 'Selected program Subjects must belong to the chosen Course.';
        }
    }

    foreach ($sectionNames as $sectionName) {
        foreach ($data['section_enrollments'] as $record) {
            $sameRecord = $originalId !== null && ($record['id'] ?? '') === $originalId;
            if ($sameRecord) {
                continue;
            }

            if (
                ($record['section_name'] ?? '') === $sectionName
                && ($record['semester'] ?? '') === $payload['semester']
                && ($record['academic_year'] ?? '') === $payload['academic_year']
            ) {
                $overlap = array_intersect(
                    array_map(static fn (mixed $value): string => strtoupper((string) $value), (array) ($record['subject_codes'] ?? [])),
                    $payload['subject_codes']
                );
                if ($overlap !== []) {
                    $errors[] = 'Section ' . $sectionName . ' is already enrolled in one or more selected Subjects for the same Semester and Academic Year.';
                }
            }
        }
    }

    return array_values(array_unique($errors));
}

function build_section_enrollment_records(array $payload, array $sectionNames, ?array $existing = null): array
{
    $records = [];
    foreach ($sectionNames as $index => $sectionName) {
        $records[] = [
            'id' => $existing['id'] ?? ($payload['id'] !== '' && $index === 0 ? $payload['id'] : assignment_management_next_id('se')),
            'department_code' => $payload['department_code'],
            'course_code' => $payload['course_code'],
            'section_name' => $sectionName,
            'year_level' => $payload['year_level'],
            'semester' => $payload['semester'],
            'subject_type' => in_array($payload['subject_type'], ['program_only', 'ge_only', 'program_and_ge'], true) ? $payload['subject_type'] : 'program_only',
            'subject_codes' => array_values($payload['subject_codes']),
            'academic_year' => $payload['academic_year'],
            'status' => in_array($payload['status'], ['active', 'inactive'], true) ? $payload['status'] : 'active',
        ];
    }

    return $records;
}

function assignment_management_course_scope_label(array $record, array $structure): string
{
    if (!empty($record['is_general_education'])) {
        return 'All Courses';
    }

    $labels = [];
    foreach ((array) ($record['course_codes'] ?? []) as $courseCode) {
        $labels[] = (string) $courseCode;
    }

    return $labels === [] ? 'No Course Scope' : implode(', ', $labels);
}

function assignment_management_subject_codes_label(array $subjectCodes): string
{
    return $subjectCodes === [] ? 'No Subjects' : implode(', ', $subjectCodes);
}

function assignment_management_faculty_name(string $facultyId, array $facultyMap): string
{
    $record = $facultyMap[$facultyId] ?? null;
    if ($record === null) {
        return $facultyId;
    }

    return faculty_full_name($record);
}

function assignment_management_subject_label(string $subjectCode, array $subjectMap): string
{
    $subject = $subjectMap[$subjectCode] ?? null;
    if ($subject === null) {
        return $subjectCode;
    }

    return trim($subjectCode . ' - ' . ((string) ($subject['title'] ?? '')));
}

function assignment_management_subject_filter_options(array $records): array
{
    $values = [];
    foreach ($records as $record) {
        $value = trim((string) ($record['subject_code'] ?? ''));
        if ($value !== '') {
            $values[$value] = $value;
        }
    }

    ksort($values);
    return array_values($values);
}

function assignment_management_faculty_filter_options(array $records): array
{
    $values = [];
    foreach ($records as $record) {
        $value = trim((string) ($record['faculty_id'] ?? ''));
        if ($value !== '') {
            $values[$value] = $value;
        }
    }

    ksort($values);
    return array_values($values);
}

function assignment_management_course_filter_options(array $facultyAssignments, array $sectionEnrollments): array
{
    $values = [];

    foreach ($facultyAssignments as $record) {
        foreach ((array) ($record['course_codes'] ?? []) as $courseCode) {
            if ($courseCode !== '') {
                $values[(string) $courseCode] = (string) $courseCode;
            }
        }
    }

    foreach ($sectionEnrollments as $record) {
        $courseCode = trim((string) ($record['course_code'] ?? ''));
        if ($courseCode !== '') {
            $values[$courseCode] = $courseCode;
        }
    }

    ksort($values);
    return array_values($values);
}

function assignment_management_section_filter_options(array $records): array
{
    $values = [];
    foreach ($records as $record) {
        $value = trim((string) ($record['section_name'] ?? ''));
        if ($value !== '') {
            $values[$value] = $value;
        }
    }

    ksort($values);
    return array_values($values);
}

function assignment_management_year_level_filter_options(array $records): array
{
    $values = [];
    foreach ($records as $record) {
        $value = trim((string) ($record['year_level'] ?? ''));
        if ($value !== '') {
            $values[$value] = $value;
        }
    }

    ksort($values);
    return array_values($values);
}

function assignment_management_subject_type_label(string $subjectType): string
{
    return match ($subjectType) {
        'program_only' => 'Program Subjects Only',
        'ge_only' => 'General Education Subjects Only',
        'program_and_ge' => 'Program and General Education Subjects',
        default => 'Program Subjects Only',
    };
}

function assignment_management_matching_faculty_for_subject(array $facultyAssignments, array $enrollment, string $subjectCode): ?array
{
    foreach ($facultyAssignments as $assignment) {
        if (($assignment['subject_code'] ?? '') !== $subjectCode) {
            continue;
        }

        if (($assignment['semester'] ?? '') !== ($enrollment['semester'] ?? '')) {
            continue;
        }

        if (($assignment['academic_year'] ?? '') !== ($enrollment['academic_year'] ?? '')) {
            continue;
        }

        if (!empty($assignment['is_general_education'])) {
            return $assignment;
        }

        if (in_array((string) ($enrollment['course_code'] ?? ''), (array) ($assignment['course_codes'] ?? []), true)) {
            return $assignment;
        }
    }

    return null;
}
