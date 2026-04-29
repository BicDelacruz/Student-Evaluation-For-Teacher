<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

const ADMIN_DATA_FILE = __DIR__ . '/../data/students.json';
const ADMIN_LOGIN_REDIRECT = '../login.html';

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function set_flash(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}

function get_flash(string $key): ?string
{
    if (!isset($_SESSION['flash'][$key])) {
        return null;
    }

    $message = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);

    return is_string($message) ? $message : null;
}

function clear_flash(): void
{
    unset($_SESSION['flash']);
}

function admin_is_pending(): bool
{
    return !empty($_SESSION['admin_pending']) && !empty($_SESSION['admin_user']);
}

function admin_is_verified(): bool
{
    return !empty($_SESSION['admin_verified']) && !empty($_SESSION['admin_user']);
}

function current_admin(): array
{
    return $_SESSION['admin_user'] ?? [
        'university_id' => 'admin',
        'name' => 'System Administrator',
    ];
}

function reset_admin_session(bool $preserveFlash = true): void
{
    $flash = $preserveFlash ? ($_SESSION['flash'] ?? []) : [];

    unset(
        $_SESSION['admin_pending'],
        $_SESSION['admin_verified'],
        $_SESSION['admin_user']
    );

    if ($preserveFlash && $flash !== []) {
        $_SESSION['flash'] = $flash;
    }
}

function require_admin_pending(): void
{
    if (admin_is_verified()) {
        redirect('dashboard.php');
    }

    if (!admin_is_pending()) {
        redirect(ADMIN_LOGIN_REDIRECT);
    }
}

function require_admin_verified(): void
{
    if (!admin_is_verified()) {
        redirect(ADMIN_LOGIN_REDIRECT);
    }
}

function admin_nav_items(): array
{
    return [
        'dashboard' => ['label' => 'Dashboard', 'href' => 'dashboard.php', 'icon' => 'dashboard'],
        'students' => ['label' => 'Student Management', 'href' => 'students.php', 'icon' => 'students'],
        'faculty' => ['label' => 'Faculty Management', 'href' => 'placeholder.php?page=faculty', 'icon' => 'faculty'],
        'structure' => ['label' => 'Academic Structure', 'href' => 'placeholder.php?page=structure', 'icon' => 'structure'],
        'assignments' => ['label' => 'Assignment Management', 'href' => 'placeholder.php?page=assignments', 'icon' => 'assignments'],
        'setup' => ['label' => 'Evaluation Setup', 'href' => 'placeholder.php?page=setup', 'icon' => 'setup'],
        'monitoring' => ['label' => 'Submission Monitoring', 'href' => 'placeholder.php?page=monitoring', 'icon' => 'monitoring'],
        'reports' => ['label' => 'Reports', 'href' => 'placeholder.php?page=reports', 'icon' => 'reports'],
        'announcements' => ['label' => 'Announcements', 'href' => 'placeholder.php?page=announcements', 'icon' => 'announcements'],
        'settings' => ['label' => 'Settings', 'href' => 'placeholder.php?page=settings', 'icon' => 'settings'],
    ];
}

function placeholder_pages(): array
{
    return [
        'faculty' => 'Faculty Management',
        'structure' => 'Academic Structure',
        'assignments' => 'Assignment Management',
        'setup' => 'Evaluation Setup',
        'monitoring' => 'Submission Monitoring',
        'reports' => 'Reports',
        'announcements' => 'Announcements',
        'settings' => 'Settings',
    ];
}

function is_valid_placeholder(string $page): bool
{
    return array_key_exists($page, placeholder_pages());
}

function placeholder_title(string $page): string
{
    return placeholder_pages()[$page] ?? 'Module';
}

function section_catalog(): array
{
    return [
        'BSIT-1A' => ['department' => 'College of Computer Studies (CCS)', 'course' => 'BSIT', 'year_level' => '1st Year', 'semester' => '2nd Semester', 'academic_year' => '2025-2026'],
        'BSIT-1B' => ['department' => 'College of Computer Studies (CCS)', 'course' => 'BSIT', 'year_level' => '1st Year', 'semester' => '2nd Semester', 'academic_year' => '2025-2026'],
        'BSIT-2A' => ['department' => 'College of Computer Studies (CCS)', 'course' => 'BSIT', 'year_level' => '2nd Year', 'semester' => '2nd Semester', 'academic_year' => '2025-2026'],
        'BSIT-2B' => ['department' => 'College of Computer Studies (CCS)', 'course' => 'BSIT', 'year_level' => '2nd Year', 'semester' => '2nd Semester', 'academic_year' => '2025-2026'],
        'BSIT-3A' => ['department' => 'College of Computer Studies (CCS)', 'course' => 'BSIT', 'year_level' => '3rd Year', 'semester' => '2nd Semester', 'academic_year' => '2025-2026'],
        'BSIT-3C' => ['department' => 'College of Computer Studies (CCS)', 'course' => 'BSIT', 'year_level' => '3rd Year', 'semester' => '2nd Semester', 'academic_year' => '2025-2026'],
        'BSIT-4A' => ['department' => 'College of Computer Studies (CCS)', 'course' => 'BSIT', 'year_level' => '4th Year', 'semester' => '2nd Semester', 'academic_year' => '2025-2026'],
        'BSCS-2A' => ['department' => 'College of Computer Studies (CCS)', 'course' => 'BSCS', 'year_level' => '2nd Year', 'semester' => '2nd Semester', 'academic_year' => '2025-2026'],
        'BSCS-3A' => ['department' => 'College of Computer Studies (CCS)', 'course' => 'BSCS', 'year_level' => '3rd Year', 'semester' => '2nd Semester', 'academic_year' => '2025-2026'],
        'BSCS-4A' => ['department' => 'College of Computer Studies (CCS)', 'course' => 'BSCS', 'year_level' => '4th Year', 'semester' => '2nd Semester', 'academic_year' => '2025-2026'],
        'BSED-English-4A' => ['department' => 'College of Education (COED)', 'course' => 'BSED', 'year_level' => '4th Year', 'semester' => '2nd Semester', 'academic_year' => '2025-2026'],
        'BEED-Filipino-1A' => ['department' => 'College of Education (COED)', 'course' => 'BEED', 'year_level' => '1st Year', 'semester' => '2nd Semester', 'academic_year' => '2025-2026'],
        'BSED-Math-3A' => ['department' => 'College of Education (COED)', 'course' => 'BSED', 'year_level' => '3rd Year', 'semester' => '2nd Semester', 'academic_year' => '2025-2026'],
        'Psych-3A' => ['department' => 'College of Arts and Sciences (CAS)', 'course' => 'BSPsych', 'year_level' => '3rd Year', 'semester' => '2nd Semester', 'academic_year' => '2025-2026'],
        'HM-1A' => ['department' => 'College of Hospitality Management (CHM)', 'course' => 'BSHM', 'year_level' => '1st Year', 'semester' => '2nd Semester', 'academic_year' => '2025-2026'],
    ];
}

function default_students(): array
{
    return [
        ['student_id' => '25-000832', 'email' => 'guerrero.june@eastgate.edu', 'first_name' => 'June', 'middle_name' => 'E.', 'last_name' => 'Guerrero', 'department' => 'College of Computer Studies (CCS)', 'course' => 'BSIT', 'year_level' => '1st Year', 'section' => 'BSIT-1B', 'semester' => '2nd Semester', 'academic_year' => '2025-2026', 'password_hash' => 'student123', 'status' => 'active'],
        ['student_id' => '25-001245', 'email' => 'garcia.anna@eastgate.edu', 'first_name' => 'Anna', 'middle_name' => 'P.', 'last_name' => 'Garcia', 'department' => 'College of Computer Studies (CCS)', 'course' => 'BSIT', 'year_level' => '1st Year', 'section' => 'BSIT-1A', 'semester' => '2nd Semester', 'academic_year' => '2025-2026', 'password_hash' => 'student123', 'status' => 'active'],
        ['student_id' => '24-003156', 'email' => 'mendoza.leah@eastgate.edu', 'first_name' => 'Leah', 'middle_name' => 'C.', 'last_name' => 'Mendoza', 'department' => 'College of Computer Studies (CCS)', 'course' => 'BSIT', 'year_level' => '2nd Year', 'section' => 'BSIT-2B', 'semester' => '2nd Semester', 'academic_year' => '2025-2026', 'password_hash' => 'student123', 'status' => 'active'],
        ['student_id' => '23-002789', 'email' => 'fernandez.kyle@eastgate.edu', 'first_name' => 'Kyle', 'middle_name' => 'R.', 'last_name' => 'Fernandez', 'department' => 'College of Arts and Sciences (CAS)', 'course' => 'BSPsych', 'year_level' => '3rd Year', 'section' => 'Psych-3A', 'semester' => '2nd Semester', 'academic_year' => '2025-2026', 'password_hash' => 'student123', 'status' => 'active'],
        ['student_id' => '22-001534', 'email' => 'lopez.mica@eastgate.edu', 'first_name' => 'Mica', 'middle_name' => 'T.', 'last_name' => 'Lopez', 'department' => 'College of Computer Studies (CCS)', 'course' => 'BSCS', 'year_level' => '4th Year', 'section' => 'BSCS-4A', 'semester' => '2nd Semester', 'academic_year' => '2025-2026', 'password_hash' => 'student123', 'status' => 'suspended'],
        ['student_id' => '25-002876', 'email' => 'torres.ivan@eastgate.edu', 'first_name' => 'Ivan', 'middle_name' => 'L.', 'last_name' => 'Torres', 'department' => 'College of Hospitality Management (CHM)', 'course' => 'BSHM', 'year_level' => '1st Year', 'section' => 'HM-1A', 'semester' => '2nd Semester', 'academic_year' => '2025-2026', 'password_hash' => 'student123', 'status' => 'active'],
        ['student_id' => '23-005123', 'email' => 'rivera.carla@eastgate.edu', 'first_name' => 'Carla', 'middle_name' => 'M.', 'last_name' => 'Rivera', 'department' => 'College of Computer Studies (CCS)', 'course' => 'BSCS', 'year_level' => '3rd Year', 'section' => 'BSCS-3A', 'semester' => '2nd Semester', 'academic_year' => '2025-2026', 'password_hash' => 'student123', 'status' => 'active'],
        ['student_id' => '24-004567', 'email' => 'navarro.ella@eastgate.edu', 'first_name' => 'Ella', 'middle_name' => 'B.', 'last_name' => 'Navarro', 'department' => 'College of Computer Studies (CCS)', 'course' => 'BSIT', 'year_level' => '2nd Year', 'section' => 'BSIT-2A', 'semester' => '2nd Semester', 'academic_year' => '2025-2026', 'password_hash' => 'student123', 'status' => 'active'],
        ['student_id' => '22-003421', 'email' => 'morales.jia@eastgate.edu', 'first_name' => 'Jia', 'middle_name' => 'V.', 'last_name' => 'Morales', 'department' => 'College of Education (COED)', 'course' => 'BSED', 'year_level' => '4th Year', 'section' => 'BSED-English-4A', 'semester' => '2nd Semester', 'academic_year' => '2025-2026', 'password_hash' => 'student123', 'status' => 'active'],
        ['student_id' => '23-001987', 'email' => 'castillo.nico@eastgate.edu', 'first_name' => 'Nico', 'middle_name' => 'D.', 'last_name' => 'Castillo', 'department' => 'College of Education (COED)', 'course' => 'BSED', 'year_level' => '3rd Year', 'section' => 'BSED-Math-3A', 'semester' => '2nd Semester', 'academic_year' => '2025-2026', 'password_hash' => 'student123', 'status' => 'suspended'],
        ['student_id' => '25-003654', 'email' => 'ramos.lia@eastgate.edu', 'first_name' => 'Lia', 'middle_name' => 'A.', 'last_name' => 'Ramos', 'department' => 'College of Education (COED)', 'course' => 'BEED', 'year_level' => '1st Year', 'section' => 'BEED-Filipino-1A', 'semester' => '2nd Semester', 'academic_year' => '2025-2026', 'password_hash' => 'student123', 'status' => 'active'],
        ['student_id' => '24-001234', 'email' => 'domingo.mariojr@eastgate.edu', 'first_name' => 'Mario Jr.', 'middle_name' => 'C.', 'last_name' => 'Domingo', 'department' => 'College of Computer Studies (CCS)', 'course' => 'BSIT', 'year_level' => '2nd Year', 'section' => 'BSIT-2A', 'semester' => '2nd Semester', 'academic_year' => '2025-2026', 'password_hash' => 'student123', 'status' => 'active'],
        ['student_id' => '23-004821', 'email' => 'villar.theo@eastgate.edu', 'first_name' => 'Theo', 'middle_name' => 'G.', 'last_name' => 'Villar', 'department' => 'College of Computer Studies (CCS)', 'course' => 'BSIT', 'year_level' => '3rd Year', 'section' => 'BSIT-3C', 'semester' => '2nd Semester', 'academic_year' => '2025-2026', 'password_hash' => 'student123', 'status' => 'active'],
    ];
}

function load_students(): array
{
    if (!file_exists(ADMIN_DATA_FILE)) {
        save_students(default_students());
    }

    $raw = file_get_contents(ADMIN_DATA_FILE);
    if ($raw === false || trim($raw) === '') {
        $students = default_students();
        save_students($students);
        return $students;
    }

    $students = json_decode($raw, true);
    if (!is_array($students)) {
        $students = default_students();
        save_students($students);
    }

    return array_values(array_filter($students, 'is_array'));
}

function save_students(array $students): void
{
    usort($students, static function (array $left, array $right): int {
        return strcmp($right['student_id'], $left['student_id']);
    });

    file_put_contents(
        ADMIN_DATA_FILE,
        json_encode(array_values($students), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        LOCK_EX
    );
}

function student_full_name(array $student): string
{
    $first = trim((string) ($student['first_name'] ?? ''));
    $middle = trim((string) ($student['middle_name'] ?? ''));
    $last = trim((string) ($student['last_name'] ?? ''));

    return trim($last . ', ' . $first . ($middle !== '' ? ' ' . $middle : ''));
}

function dashboard_metrics(array $students): array
{
    return [
        'total_students' => count($students),
        'total_faculty' => 13,
        'total_subjects' => 29,
        'submitted' => 140,
        'total_participants' => 146,
        'participation_rate' => 95.9,
        'completed_evaluations' => 2,
        'pending_evaluations' => 2,
        'schedule_status' => 'Ongoing',
        'semester' => '2nd Semester',
        'academic_year' => '2025-2026',
        'start_date' => '4/1/2026',
        'end_date' => '4/30/2026',
    ];
}

function unique_values(array $students, string $field): array
{
    $values = [];
    foreach ($students as $student) {
        $value = trim((string) ($student[$field] ?? ''));
        if ($value !== '') {
            $values[$value] = $value;
        }
    }

    ksort($values);
    return array_values($values);
}

function filter_students(array $students, array $filters): array
{
    $search = strtolower(trim((string) ($filters['search'] ?? '')));
    $course = trim((string) ($filters['course'] ?? ''));
    $yearLevel = trim((string) ($filters['year_level'] ?? ''));
    $section = trim((string) ($filters['section'] ?? ''));
    $status = trim((string) ($filters['status'] ?? ''));

    return array_values(array_filter($students, static function (array $student) use ($search, $course, $yearLevel, $section, $status): bool {
        if ($search !== '') {
            $haystack = strtolower(implode(' ', [
                (string) ($student['student_id'] ?? ''),
                (string) ($student['email'] ?? ''),
                student_full_name($student),
            ]));

            if (strpos($haystack, $search) === false) {
                return false;
            }
        }

        if ($course !== '' && ($student['course'] ?? '') !== $course) {
            return false;
        }

        if ($yearLevel !== '' && ($student['year_level'] ?? '') !== $yearLevel) {
            return false;
        }

        if ($section !== '' && ($student['section'] ?? '') !== $section) {
            return false;
        }

        if ($status !== '' && ($student['status'] ?? '') !== $status) {
            return false;
        }

        return true;
    }));
}

function student_index_by_id(array $students, string $studentId): ?int
{
    foreach ($students as $index => $student) {
        if (($student['student_id'] ?? '') === $studentId) {
            return $index;
        }
    }

    return null;
}

function section_meta_for(string $section): ?array
{
    $catalog = section_catalog();
    return $catalog[$section] ?? null;
}

function normalize_student_payload(array $source): array
{
    $payload = [
        'student_id' => trim((string) ($source['student_id'] ?? '')),
        'email' => strtolower(trim((string) ($source['email'] ?? ''))),
        'first_name' => trim((string) ($source['first_name'] ?? '')),
        'middle_name' => trim((string) ($source['middle_name'] ?? '')),
        'last_name' => trim((string) ($source['last_name'] ?? '')),
        'department' => trim((string) ($source['department'] ?? '')),
        'course' => trim((string) ($source['course'] ?? '')),
        'year_level' => trim((string) ($source['year_level'] ?? '')),
        'section' => trim((string) ($source['section'] ?? '')),
        'semester' => trim((string) ($source['semester'] ?? '')),
        'academic_year' => trim((string) ($source['academic_year'] ?? '')),
        'password' => (string) ($source['password'] ?? ''),
        'status' => trim((string) ($source['status'] ?? 'active')) ?: 'active',
    ];

    $meta = section_meta_for($payload['section']);
    if ($meta !== null) {
        $payload['department'] = $meta['department'];
        $payload['course'] = $meta['course'];
        $payload['year_level'] = $meta['year_level'];
        $payload['semester'] = $meta['semester'];
        $payload['academic_year'] = $meta['academic_year'];
    }

    return $payload;
}

function validate_student_payload(array $payload, array $students, ?string $originalStudentId = null): array
{
    $errors = [];
    $requiredFields = [
        'student_id' => 'Student ID is required.',
        'email' => 'Email is required.',
        'first_name' => 'First name is required.',
        'last_name' => 'Last name is required.',
        'department' => 'Department is required.',
        'course' => 'Course is required.',
        'year_level' => 'Year level is required.',
        'section' => 'Section is required.',
        'semester' => 'Semester is required.',
        'academic_year' => 'Academic year is required.',
    ];

    foreach ($requiredFields as $field => $message) {
        if (trim((string) ($payload[$field] ?? '')) === '') {
            $errors[] = $message;
        }
    }

    if ($payload['email'] !== '' && !filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid email address.';
    }

    if (section_meta_for($payload['section']) === null) {
        $errors[] = 'Please select a valid section so semester and academic year can be filled automatically.';
    }

    foreach ($students as $student) {
        $sameRecord = $originalStudentId !== null && ($student['student_id'] ?? '') === $originalStudentId;
        if ($sameRecord) {
            continue;
        }

        if (($student['student_id'] ?? '') === $payload['student_id']) {
            $errors[] = 'Student ID already exists.';
        }

        if (strcasecmp((string) ($student['email'] ?? ''), $payload['email']) === 0) {
            $errors[] = 'Email already exists.';
        }
    }

    return array_values(array_unique($errors));
}

function build_student_record(array $payload, ?array $existing = null): array
{
    $passwordHash = $existing['password_hash'] ?? 'student123';
    if ($payload['password'] !== '') {
        $passwordHash = password_hash($payload['password'], PASSWORD_DEFAULT);
    }

    return [
        'student_id' => $payload['student_id'],
        'email' => $payload['email'],
        'first_name' => $payload['first_name'],
        'middle_name' => $payload['middle_name'],
        'last_name' => $payload['last_name'],
        'department' => $payload['department'],
        'course' => $payload['course'],
        'year_level' => $payload['year_level'],
        'section' => $payload['section'],
        'semester' => $payload['semester'],
        'academic_year' => $payload['academic_year'],
        'password_hash' => $passwordHash,
        'status' => in_array($payload['status'], ['active', 'suspended'], true) ? $payload['status'] : 'active',
    ];
}

function selected_filters_from_request(): array
{
    return [
        'search' => trim((string) ($_GET['search'] ?? '')),
        'course' => trim((string) ($_GET['course'] ?? '')),
        'year_level' => trim((string) ($_GET['year_level'] ?? '')),
        'section' => trim((string) ($_GET['section'] ?? '')),
        'status' => trim((string) ($_GET['status'] ?? '')),
    ];
}

function active_filter_summary(array $filters): string
{
    $labels = [];
    foreach ($filters as $key => $value) {
        if ($value === '') {
            continue;
        }

        $label = match ($key) {
            'search' => 'Search: ' . $value,
            'course' => 'Course: ' . $value,
            'year_level' => 'Year Level: ' . $value,
            'section' => 'Section: ' . $value,
            'status' => 'Status: ' . ucfirst($value),
            default => $value,
        };

        $labels[] = $label;
    }

    return $labels === [] ? 'No filters applied' : implode(' | ', $labels);
}
