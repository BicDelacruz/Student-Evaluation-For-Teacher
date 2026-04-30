<?php
declare(strict_types=1);

const ADMIN_ACADEMIC_STRUCTURE_FILE = __DIR__ . '/../data/academic_structure.json';

function academic_structure_tabs(): array
{
    return ['departments', 'courses', 'sections', 'subjects'];
}

function normalize_structure_tab(?string $tab): string
{
    $tab = strtolower(trim((string) $tab));
    return in_array($tab, academic_structure_tabs(), true) ? $tab : 'departments';
}

function academic_structure_year_levels(): array
{
    return ['1st Year', '2nd Year', '3rd Year', '4th Year', '5th Year'];
}

function academic_structure_semesters(): array
{
    return ['1st Semester', '2nd Semester', 'Summer'];
}

function academic_structure_departments_catalog(): array
{
    return [
        'CCS' => ['code' => 'CCS', 'name' => 'College of Computer Studies (CCS)', 'head' => 'Dr. Roberto M. Santos'],
        'CBA' => ['code' => 'CBA', 'name' => 'College of Business and Accountancy (CBA)', 'head' => 'Prof. Jennifer L. Aquino'],
        'COE' => ['code' => 'COE', 'name' => 'College of Engineering (COE)', 'head' => 'Engr. Ricardo B. Cruz'],
        'COED' => ['code' => 'COED', 'name' => 'College of Education (COED)', 'head' => 'Dr. Maria T. Reyes'],
        'CON' => ['code' => 'CON', 'name' => 'College of Nursing (CON)', 'head' => 'Dr. Angelica S. Fernandez'],
        'CAS' => ['code' => 'CAS', 'name' => 'College of Arts and Sciences (CAS)', 'head' => 'Dr. Benjamin R. Morales'],
    ];
}

function academic_structure_course_seed(): array
{
    return [
        ['code' => 'BSIT', 'status' => 'active', 'name' => 'Bachelor of Science in Information Technology', 'description' => 'Four-year program focused on practical IT skills and technologies', 'department_code' => 'CCS', 'year_levels' => 4, 'student_count' => 1400],
        ['code' => 'BSCS', 'status' => 'active', 'name' => 'Bachelor of Science in Computer Science', 'description' => 'Four-year program emphasizing computational theory and software development', 'department_code' => 'CCS', 'year_levels' => 4, 'student_count' => 600],
        ['code' => 'BSA', 'status' => 'active', 'name' => 'Bachelor of Science in Accountancy', 'description' => 'Four-year program in accounting and financial management', 'department_code' => 'CBA', 'year_levels' => 4, 'student_count' => 550],
        ['code' => 'BSBA', 'status' => 'active', 'name' => 'Bachelor of Science in Business Administration', 'description' => 'Business degree covering marketing, management, and operations', 'department_code' => 'CBA', 'year_levels' => 4, 'student_count' => 820],
        ['code' => 'BSCE', 'status' => 'active', 'name' => 'Bachelor of Science in Civil Engineering', 'description' => 'Engineering program focused on structural design and construction systems', 'department_code' => 'COE', 'year_levels' => 4, 'student_count' => 430],
        ['code' => 'BSCPE', 'status' => 'active', 'name' => 'Bachelor of Science in Computer Engineering', 'description' => 'Program covering hardware systems, embedded devices, and software integration', 'department_code' => 'COE', 'year_levels' => 4, 'student_count' => 390],
        ['code' => 'BSEE', 'status' => 'active', 'name' => 'Bachelor of Science in Electrical Engineering', 'description' => 'Engineering program on power systems, circuits, and industrial applications', 'department_code' => 'COE', 'year_levels' => 4, 'student_count' => 360],
        ['code' => 'BSME', 'status' => 'inactive', 'name' => 'Bachelor of Science in Mechanical Engineering', 'description' => 'Engineering program centered on mechanics, machines, and thermal systems', 'department_code' => 'COE', 'year_levels' => 4, 'student_count' => 280],
        ['code' => 'BEED', 'status' => 'active', 'name' => 'Bachelor of Elementary Education', 'description' => 'Teacher education program for elementary classroom instruction', 'department_code' => 'COED', 'year_levels' => 4, 'student_count' => 340],
        ['code' => 'BSED', 'status' => 'active', 'name' => 'Bachelor of Secondary Education', 'description' => 'Teacher education program with subject-area specialization for secondary level', 'department_code' => 'COED', 'year_levels' => 4, 'student_count' => 420],
        ['code' => 'BSN', 'status' => 'active', 'name' => 'Bachelor of Science in Nursing', 'description' => 'Health sciences program focused on clinical practice and patient care', 'department_code' => 'CON', 'year_levels' => 4, 'student_count' => 510],
        ['code' => 'BSPSYCH', 'status' => 'active', 'name' => 'Bachelor of Science in Psychology', 'description' => 'Behavioral science program exploring research, development, and counseling foundations', 'department_code' => 'CAS', 'year_levels' => 4, 'student_count' => 300],
        ['code' => 'ABCOMM', 'status' => 'active', 'name' => 'Bachelor of Arts in Communication', 'description' => 'Liberal arts program covering media, writing, and strategic communication', 'department_code' => 'CAS', 'year_levels' => 4, 'student_count' => 265],
    ];
}

function load_academic_structure(): array
{
    if (!file_exists(ADMIN_ACADEMIC_STRUCTURE_FILE)) {
        $default = default_academic_structure();
        save_academic_structure($default);
        return $default;
    }

    $raw = file_get_contents(ADMIN_ACADEMIC_STRUCTURE_FILE);
    if ($raw === false || trim($raw) === '') {
        $default = default_academic_structure();
        save_academic_structure($default);
        return $default;
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        $default = default_academic_structure();
        save_academic_structure($default);
        return $default;
    }

    foreach (['departments', 'courses', 'sections', 'subjects'] as $key) {
        if (!isset($data[$key]) || !is_array($data[$key])) {
            $default = default_academic_structure();
            save_academic_structure($default);
            return $default;
        }
    }

    return [
        'departments' => array_values(array_filter($data['departments'], 'is_array')),
        'courses' => array_values(array_filter($data['courses'], 'is_array')),
        'sections' => array_values(array_filter($data['sections'], 'is_array')),
        'subjects' => array_values(array_filter($data['subjects'], 'is_array')),
    ];
}

function save_academic_structure(array $data): void
{
    $payload = [
        'departments' => array_values($data['departments'] ?? []),
        'courses' => array_values($data['courses'] ?? []),
        'sections' => array_values($data['sections'] ?? []),
        'subjects' => array_values($data['subjects'] ?? []),
    ];

    usort($payload['departments'], static fn (array $left, array $right): int => strcmp($left['code'] ?? '', $right['code'] ?? ''));
    usort($payload['courses'], static fn (array $left, array $right): int => strcmp($left['code'] ?? '', $right['code'] ?? ''));
    usort($payload['sections'], static function (array $left, array $right): int {
        $leftKey = implode('|', [$left['course_code'] ?? '', $left['year_level'] ?? '', $left['name'] ?? '']);
        $rightKey = implode('|', [$right['course_code'] ?? '', $right['year_level'] ?? '', $right['name'] ?? '']);
        return strcmp($leftKey, $rightKey);
    });
    usort($payload['subjects'], static fn (array $left, array $right): int => strcmp($left['code'] ?? '', $right['code'] ?? ''));

    file_put_contents(
        ADMIN_ACADEMIC_STRUCTURE_FILE,
        json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        LOCK_EX
    );
}

function default_academic_structure(): array
{
    $departments = array_values(academic_structure_departments_catalog());
    $departmentMap = academic_structure_department_map(['departments' => $departments]);

    $courses = [];
    foreach (academic_structure_course_seed() as $course) {
        $department = $departmentMap[$course['department_code']] ?? null;
        if ($department === null) {
            continue;
        }

        $courses[] = [
            'code' => $course['code'],
            'status' => strtolower($course['status']),
            'name' => $course['name'],
            'description' => $course['description'],
            'department_code' => $course['department_code'],
            'department_name' => $department['name'],
            'year_levels' => (int) $course['year_levels'],
            'student_count' => (int) $course['student_count'],
        ];
    }

    $sectionPlans = [
        'BSIT' => ['1st Year' => ['A', 'B', 'C', 'D'], '2nd Year' => ['A', 'B', 'C', 'D'], '3rd Year' => ['A', 'B', 'C'], '4th Year' => ['A', 'B', 'C']],
        'BSCS' => ['1st Year' => ['A', 'B'], '2nd Year' => ['A', 'B'], '3rd Year' => ['A', 'B'], '4th Year' => ['A']],
        'BSA' => ['1st Year' => ['A', 'B'], '2nd Year' => ['A', 'B'], '3rd Year' => ['A', 'B'], '4th Year' => ['A', 'B']],
        'BSBA' => ['1st Year' => ['A', 'B'], '2nd Year' => ['A', 'B'], '3rd Year' => ['A', 'B'], '4th Year' => ['A', 'B']],
        'BSCE' => ['1st Year' => ['A', 'B'], '2nd Year' => ['A', 'B'], '3rd Year' => ['A', 'B'], '4th Year' => ['A', 'B']],
        'BSCPE' => ['1st Year' => ['A', 'B'], '2nd Year' => ['A', 'B'], '3rd Year' => ['A', 'B'], '4th Year' => ['A', 'B']],
        'BSEE' => ['1st Year' => ['A', 'B'], '2nd Year' => ['A', 'B'], '3rd Year' => ['A', 'B'], '4th Year' => ['A', 'B']],
        'BSME' => ['1st Year' => ['A', 'B'], '2nd Year' => ['A', 'B'], '3rd Year' => ['A', 'B'], '4th Year' => ['A', 'B']],
        'BEED' => ['1st Year' => ['A', 'B'], '2nd Year' => ['A', 'B'], '3rd Year' => ['A', 'B'], '4th Year' => ['A', 'B']],
        'BSED' => ['1st Year' => ['A'], '2nd Year' => ['A'], '3rd Year' => ['A'], '4th Year' => ['A']],
        'BSN' => ['1st Year' => ['A'], '2nd Year' => ['A'], '3rd Year' => ['A'], '4th Year' => ['A']],
        'BSPSYCH' => ['3rd Year' => ['A']],
    ];

    $studentOverrides = [
        'BSIT-2A' => 1,
        'BSIT-2B' => 0,
        'BSCS-3A' => 1,
        'BSBA-1A' => 0,
    ];

    $sections = [];
    foreach ($sectionPlans as $courseCode => $yearPlan) {
        $course = academic_structure_find_by_code($courses, 'code', $courseCode);
        if ($course === null) {
            continue;
        }

        foreach ($yearPlan as $yearLevel => $letters) {
            foreach ($letters as $index => $letter) {
                $sectionName = $courseCode . '-' . academic_structure_year_level_number($yearLevel) . $letter;
                $sections[] = [
                    'name' => $sectionName,
                    'department_code' => $course['department_code'],
                    'course_code' => $courseCode,
                    'year_level' => $yearLevel,
                    'semester' => '2nd Semester',
                    'academic_year' => '2025-2026',
                    'total_students' => $studentOverrides[$sectionName] ?? (($index % 3 === 0) ? 0 : ($index % 2)),
                ];
            }
        }
    }

    $subjects = [
        [
            'code' => 'GE 101',
            'units' => '3',
            'title' => 'Understanding the Self',
            'description' => 'Philosophical and psychological perspectives on the self',
            'is_general_education' => true,
            'department_code' => 'ALL',
            'course_codes' => [],
            'year_level' => '1st Year',
            'semester' => '1st Semester',
        ],
        [
            'code' => 'GE 102',
            'units' => '3',
            'title' => 'Purposive Communication',
            'description' => 'Development of communication skills in various contexts',
            'is_general_education' => true,
            'department_code' => 'ALL',
            'course_codes' => [],
            'year_level' => '1st Year',
            'semester' => '1st Semester',
        ],
        [
            'code' => 'GE 201',
            'units' => '3',
            'title' => 'Rizal: Life and Works',
            'description' => 'Study of Jose Rizal\'s life, writings, and contributions',
            'is_general_education' => true,
            'department_code' => 'ALL',
            'course_codes' => [],
            'year_level' => '2nd Year',
            'semester' => '2nd Semester',
        ],
    ];

    $subjectPrefixes = [
        'BSIT' => 'IT',
        'BSCS' => 'CS',
        'BSA' => 'AC',
        'BSBA' => 'BA',
        'BSCE' => 'CE',
        'BSCPE' => 'CpE',
        'BSEE' => 'EE',
        'BSME' => 'ME',
        'BEED' => 'ED',
        'BSED' => 'SE',
        'BSN' => 'N',
        'BSPSYCH' => 'PSY',
        'ABCOMM' => 'COM',
    ];

    $subjectTitles = [
        '1st Year|1st Semester' => 'Foundations',
        '1st Year|2nd Semester' => 'Applications',
        '2nd Year|1st Semester' => 'Methods',
        '2nd Year|2nd Semester' => 'Studio',
        '3rd Year|1st Semester' => 'Analysis',
        '3rd Year|2nd Semester' => 'Integration',
        '4th Year|1st Semester' => 'Seminar',
        '4th Year|2nd Semester' => 'Capstone',
    ];

    foreach ($courses as $course) {
        $prefix = $subjectPrefixes[$course['code']] ?? substr($course['code'], 0, 3);
        $maxYears = $course['code'] === 'ABCOMM' ? 2 : min((int) $course['year_levels'], 4);

        for ($yearNumber = 1; $yearNumber <= $maxYears; $yearNumber++) {
            foreach (['1st Semester', '2nd Semester'] as $semesterIndex => $semester) {
                $yearLevel = academic_structure_year_levels()[$yearNumber - 1];
                $code = sprintf('%s %d%d1', $prefix, $yearNumber, $semesterIndex + 1);
                $title = trim($course['code'] . ' ' . ($subjectTitles[$yearLevel . '|' . $semester] ?? 'Core Subject'));
                $description = 'Core subject for ' . $course['name'] . ' during the ' . strtolower($semester) . '.';

                if ($course['code'] === 'BSIT' && $yearLevel === '2nd Year' && $semester === '2nd Semester') {
                    $code = 'IT 221';
                    $title = 'Human Computer Interaction';
                    $description = 'Principles and practices of user-centered interface design and evaluation';
                }

                $subjects[] = [
                    'code' => $code,
                    'units' => '3',
                    'title' => $title,
                    'description' => $description,
                    'is_general_education' => false,
                    'department_code' => $course['department_code'],
                    'course_codes' => [$course['code']],
                    'year_level' => $yearLevel,
                    'semester' => $semester,
                ];
            }
        }
    }

    return [
        'departments' => $departments,
        'courses' => $courses,
        'sections' => $sections,
        'subjects' => $subjects,
    ];
}

function academic_structure_find_by_code(array $records, string $field, string $value): ?array
{
    foreach ($records as $record) {
        if (($record[$field] ?? '') === $value) {
            return $record;
        }
    }

    return null;
}

function academic_structure_department_map(array $data): array
{
    $map = [];
    foreach ($data['departments'] ?? [] as $department) {
        $code = (string) ($department['code'] ?? '');
        if ($code !== '') {
            $map[$code] = $department;
        }
    }

    return $map;
}

function academic_structure_course_map(array $data): array
{
    $map = [];
    foreach ($data['courses'] ?? [] as $course) {
        $code = (string) ($course['code'] ?? '');
        if ($code !== '') {
            $map[$code] = $course;
        }
    }

    return $map;
}

function academic_structure_lookup_department_name(array $data, string $departmentCode): string
{
    if ($departmentCode === 'ALL') {
        return 'All Departments (General Education)';
    }

    $department = academic_structure_department_map($data)[$departmentCode] ?? null;
    return (string) ($department['name'] ?? $departmentCode);
}

function academic_structure_lookup_course_name(array $data, string $courseCode): string
{
    $course = academic_structure_course_map($data)[$courseCode] ?? null;
    return (string) ($course['name'] ?? $courseCode);
}

function academic_structure_course_label(array $data, string $courseCode): string
{
    $course = academic_structure_course_map($data)[$courseCode] ?? null;
    if ($course === null) {
        return $courseCode;
    }

    return trim($courseCode . ' - ' . ($course['name'] ?? ''));
}

function academic_structure_department_index(array $data, string $code): ?int
{
    foreach ($data['departments'] as $index => $department) {
        if (($department['code'] ?? '') === $code) {
            return $index;
        }
    }

    return null;
}

function academic_structure_course_index(array $data, string $code): ?int
{
    foreach ($data['courses'] as $index => $course) {
        if (($course['code'] ?? '') === $code) {
            return $index;
        }
    }

    return null;
}

function academic_structure_section_index(array $data, string $name, string $courseCode, string $yearLevel, string $semester, string $academicYear): ?int
{
    foreach ($data['sections'] as $index => $section) {
        if (
            ($section['name'] ?? '') === $name
            && ($section['course_code'] ?? '') === $courseCode
            && ($section['year_level'] ?? '') === $yearLevel
            && ($section['semester'] ?? '') === $semester
            && ($section['academic_year'] ?? '') === $academicYear
        ) {
            return $index;
        }
    }

    return null;
}

function academic_structure_subject_index(array $data, string $code): ?int
{
    foreach ($data['subjects'] as $index => $subject) {
        if (($subject['code'] ?? '') === $code) {
            return $index;
        }
    }

    return null;
}

function normalize_department_payload(array $source): array
{
    return [
        'code' => strtoupper(trim((string) ($source['code'] ?? ''))),
        'name' => trim((string) ($source['name'] ?? '')),
        'head' => trim((string) ($source['head'] ?? '')),
    ];
}

function validate_department_payload(array $payload, array $data, ?string $originalCode = null): array
{
    $errors = [];

    if ($payload['code'] === '') {
        $errors[] = 'Department Code is required.';
    }
    if ($payload['name'] === '') {
        $errors[] = 'Department Name is required.';
    }
    if ($payload['head'] === '') {
        $errors[] = 'Department Head is required.';
    }

    foreach ($data['departments'] as $department) {
        $sameRecord = $originalCode !== null && ($department['code'] ?? '') === $originalCode;
        if ($sameRecord) {
            continue;
        }

        if (($department['code'] ?? '') === $payload['code']) {
            $errors[] = 'Department Code already exists.';
        }

        if (strcasecmp((string) ($department['name'] ?? ''), $payload['name']) === 0) {
            $errors[] = 'Department Name already exists.';
        }
    }

    return array_values(array_unique($errors));
}

function normalize_course_payload(array $source, array $data): array
{
    $departmentCode = strtoupper(trim((string) ($source['department_code'] ?? '')));
    $departmentName = academic_structure_lookup_department_name($data, $departmentCode);

    return [
        'code' => strtoupper(trim((string) ($source['code'] ?? ''))),
        'status' => strtolower(trim((string) ($source['status'] ?? 'active'))) ?: 'active',
        'name' => trim((string) ($source['name'] ?? '')),
        'description' => trim((string) ($source['description'] ?? '')),
        'department_code' => $departmentCode,
        'department_name' => $departmentName,
        'year_levels' => trim((string) ($source['year_levels'] ?? '')),
        'student_count' => trim((string) ($source['student_count'] ?? '0')),
    ];
}

function validate_course_payload(array $payload, array $data, ?string $originalCode = null): array
{
    $errors = [];

    if ($payload['code'] === '') {
        $errors[] = 'Course Code is required.';
    }
    if ($payload['name'] === '') {
        $errors[] = 'Course Name is required.';
    }
    if ($payload['department_code'] === '') {
        $errors[] = 'Department is required.';
    }
    if ($payload['year_levels'] === '') {
        $errors[] = 'Number of Year Levels is required.';
    }

    if ($payload['department_code'] !== '' && academic_structure_department_index($data, $payload['department_code']) === null) {
        $errors[] = 'Please select a valid Department.';
    }

    if ($payload['year_levels'] !== '' && (!ctype_digit($payload['year_levels']) || (int) $payload['year_levels'] < 1 || (int) $payload['year_levels'] > 5)) {
        $errors[] = 'Number of Year Levels must be between 1 and 5.';
    }

    if ($payload['student_count'] !== '' && (!ctype_digit($payload['student_count']) || (int) $payload['student_count'] < 0)) {
        $errors[] = 'Student Count must be a non-negative whole number.';
    }

    foreach ($data['courses'] as $course) {
        $sameRecord = $originalCode !== null && ($course['code'] ?? '') === $originalCode;
        if ($sameRecord) {
            continue;
        }

        if (($course['code'] ?? '') === $payload['code']) {
            $errors[] = 'Course Code already exists.';
        }

        if (
            strcasecmp((string) ($course['name'] ?? ''), $payload['name']) === 0
            && ($course['department_code'] ?? '') === $payload['department_code']
        ) {
            $errors[] = 'Course Name already exists under the selected Department.';
        }
    }

    return array_values(array_unique($errors));
}

function build_course_record(array $payload, array $data): array
{
    return [
        'code' => $payload['code'],
        'status' => in_array($payload['status'], ['active', 'inactive'], true) ? $payload['status'] : 'active',
        'name' => $payload['name'],
        'description' => $payload['description'],
        'department_code' => $payload['department_code'],
        'department_name' => academic_structure_lookup_department_name($data, $payload['department_code']),
        'year_levels' => (int) $payload['year_levels'],
        'student_count' => max(0, (int) $payload['student_count']),
    ];
}

function normalize_section_payload(array $source): array
{
    $sectionNames = [];
    for ($index = 1; $index <= 5; $index++) {
        $sectionNames[] = strtoupper(trim((string) ($source['section_name_' . $index] ?? '')));
    }

    return [
        'section_names' => $sectionNames,
        'name' => strtoupper(trim((string) ($source['name'] ?? ''))),
        'department_code' => strtoupper(trim((string) ($source['department_code'] ?? ''))),
        'course_code' => strtoupper(trim((string) ($source['course_code'] ?? ''))),
        'year_level' => trim((string) ($source['year_level'] ?? '')),
        'semester' => trim((string) ($source['semester'] ?? '')),
        'academic_year' => trim((string) ($source['academic_year'] ?? '')),
        'total_students' => trim((string) ($source['total_students'] ?? '0')),
    ];
}

function validate_section_payload(array $payload, array $data, bool $isBulk, ?array $originalKey = null): array
{
    $errors = [];
    $names = $isBulk ? array_values(array_filter($payload['section_names'], static fn (string $name): bool => $name !== '')) : [$payload['name']];

    if ($names === []) {
        $errors[] = 'At least one Section Name is required.';
    }
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

    $course = academic_structure_find_by_code($data['courses'], 'code', $payload['course_code']);
    if ($course === null) {
        $errors[] = 'Please select a valid Course.';
    } elseif (($course['department_code'] ?? '') !== $payload['department_code']) {
        $errors[] = 'Selected Course does not belong to the selected Department.';
    }

    if ($payload['academic_year'] !== '' && !preg_match('/^\d{4}-\d{4}$/', $payload['academic_year'])) {
        $errors[] = 'Academic Year must use the format YYYY-YYYY.';
    }

    if ($payload['total_students'] !== '' && (!ctype_digit($payload['total_students']) || (int) $payload['total_students'] < 0)) {
        $errors[] = 'Total Students must be a non-negative whole number.';
    }

    foreach ($names as $name) {
        foreach ($data['sections'] as $section) {
            $sameRecord = $originalKey !== null
                && ($section['name'] ?? '') === ($originalKey['name'] ?? '')
                && ($section['course_code'] ?? '') === ($originalKey['course_code'] ?? '')
                && ($section['year_level'] ?? '') === ($originalKey['year_level'] ?? '')
                && ($section['semester'] ?? '') === ($originalKey['semester'] ?? '')
                && ($section['academic_year'] ?? '') === ($originalKey['academic_year'] ?? '');

            if ($sameRecord) {
                continue;
            }

            if (
                ($section['name'] ?? '') === $name
                && ($section['course_code'] ?? '') === $payload['course_code']
                && ($section['year_level'] ?? '') === $payload['year_level']
                && ($section['semester'] ?? '') === $payload['semester']
                && ($section['academic_year'] ?? '') === $payload['academic_year']
            ) {
                $errors[] = 'Section ' . $name . ' already exists under the selected Course, Year Level, Semester, and Academic Year.';
            }
        }
    }

    if (count($names) !== count(array_unique($names))) {
        $errors[] = 'Duplicate Section Names were entered in the same form.';
    }

    return array_values(array_unique($errors));
}

function build_section_record(array $payload, string $name): array
{
    return [
        'name' => $name,
        'department_code' => $payload['department_code'],
        'course_code' => $payload['course_code'],
        'year_level' => $payload['year_level'],
        'semester' => $payload['semester'],
        'academic_year' => $payload['academic_year'],
        'total_students' => max(0, (int) $payload['total_students']),
    ];
}

function normalize_subject_payload(array $source): array
{
    $courseCodes = array_map(
        static fn (mixed $value): string => strtoupper(trim((string) $value)),
        (array) ($source['course_codes'] ?? [])
    );
    $courseCodes = array_values(array_filter(array_unique($courseCodes), static fn (string $value): bool => $value !== ''));
    $isGeneralEducation = !empty($source['is_general_education']);

    return [
        'code' => strtoupper(trim((string) ($source['code'] ?? ''))),
        'units' => trim((string) ($source['units'] ?? '')),
        'title' => trim((string) ($source['title'] ?? '')),
        'description' => trim((string) ($source['description'] ?? '')),
        'is_general_education' => $isGeneralEducation,
        'department_code' => $isGeneralEducation ? 'ALL' : strtoupper(trim((string) ($source['department_code'] ?? ''))),
        'course_codes' => $isGeneralEducation ? [] : $courseCodes,
        'year_level' => trim((string) ($source['year_level'] ?? '')),
        'semester' => trim((string) ($source['semester'] ?? '')),
    ];
}

function validate_subject_payload(array $payload, array $data, ?string $originalCode = null): array
{
    $errors = [];

    if ($payload['code'] === '') {
        $errors[] = 'Subject Code is required.';
    }
    if ($payload['units'] === '') {
        $errors[] = 'Units is required.';
    }
    if ($payload['title'] === '') {
        $errors[] = 'Subject Title is required.';
    }
    if ($payload['description'] === '') {
        $errors[] = 'Description is required.';
    }
    if ($payload['year_level'] === '') {
        $errors[] = 'Year Level is required.';
    }
    if ($payload['semester'] === '') {
        $errors[] = 'Semester is required.';
    }

    if (!ctype_digit($payload['units']) || (int) $payload['units'] < 1 || (int) $payload['units'] > 9) {
        $errors[] = 'Units must be a whole number from 1 to 9.';
    }

    if (!$payload['is_general_education']) {
        if ($payload['department_code'] === '') {
            $errors[] = 'Department is required for non-General Education subjects.';
        }
        if ($payload['course_codes'] === []) {
            $errors[] = 'Select at least one Course or mark the subject as General Education.';
        }
    }

    foreach ($payload['course_codes'] as $courseCode) {
        $course = academic_structure_find_by_code($data['courses'], 'code', $courseCode);
        if ($course === null) {
            $errors[] = 'One or more selected Courses are invalid.';
            continue;
        }

        if (($course['department_code'] ?? '') !== $payload['department_code']) {
            $errors[] = 'Selected Courses must belong to the chosen Department.';
        }
    }

    foreach ($data['subjects'] as $subject) {
        $sameRecord = $originalCode !== null && ($subject['code'] ?? '') === $originalCode;
        if ($sameRecord) {
            continue;
        }

        if (($subject['code'] ?? '') === $payload['code']) {
            $errors[] = 'Subject Code already exists.';
        }
    }

    return array_values(array_unique($errors));
}

function build_subject_record(array $payload): array
{
    return [
        'code' => $payload['code'],
        'units' => (string) ((int) $payload['units']),
        'title' => $payload['title'],
        'description' => $payload['description'],
        'is_general_education' => (bool) $payload['is_general_education'],
        'department_code' => $payload['is_general_education'] ? 'ALL' : $payload['department_code'],
        'course_codes' => $payload['is_general_education'] ? [] : array_values($payload['course_codes']),
        'year_level' => $payload['year_level'],
        'semester' => $payload['semester'],
    ];
}

function academic_structure_department_dependencies(array $data, string $departmentCode): array
{
    $dependencies = [];

    $courseCount = count(array_filter($data['courses'], static fn (array $course): bool => ($course['department_code'] ?? '') === $departmentCode));
    $sectionCount = count(array_filter($data['sections'], static fn (array $section): bool => ($section['department_code'] ?? '') === $departmentCode));
    $subjectCount = count(array_filter($data['subjects'], static fn (array $subject): bool => ($subject['department_code'] ?? '') === $departmentCode));

    if ($courseCount > 0) {
        $dependencies[] = $courseCount . ' course' . ($courseCount > 1 ? 's' : '');
    }
    if ($sectionCount > 0) {
        $dependencies[] = $sectionCount . ' section' . ($sectionCount > 1 ? 's' : '');
    }
    if ($subjectCount > 0) {
        $dependencies[] = $subjectCount . ' subject' . ($subjectCount > 1 ? 's' : '');
    }

    return $dependencies;
}

function academic_structure_course_dependencies(array $data, string $courseCode): array
{
    $dependencies = [];

    $sectionCount = count(array_filter($data['sections'], static fn (array $section): bool => ($section['course_code'] ?? '') === $courseCode));
    $subjectCount = count(array_filter($data['subjects'], static fn (array $subject): bool => in_array($courseCode, (array) ($subject['course_codes'] ?? []), true)));

    if ($sectionCount > 0) {
        $dependencies[] = $sectionCount . ' section' . ($sectionCount > 1 ? 's' : '');
    }
    if ($subjectCount > 0) {
        $dependencies[] = $subjectCount . ' subject' . ($subjectCount > 1 ? 's' : '');
    }

    return $dependencies;
}

function academic_structure_section_key(array $section): array
{
    return [
        'name' => (string) ($section['name'] ?? ''),
        'course_code' => (string) ($section['course_code'] ?? ''),
        'year_level' => (string) ($section['year_level'] ?? ''),
        'semester' => (string) ($section['semester'] ?? ''),
        'academic_year' => (string) ($section['academic_year'] ?? ''),
    ];
}

function academic_structure_year_level_number(string $yearLevel): string
{
    return match ($yearLevel) {
        '1st Year' => '1',
        '2nd Year' => '2',
        '3rd Year' => '3',
        '4th Year' => '4',
        '5th Year' => '5',
        default => '1',
    };
}

