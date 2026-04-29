<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(ADMIN_LOGIN_REDIRECT);
}

clear_flash();
reset_admin_session(false);

$universityId = trim((string) ($_POST['university_id'] ?? ''));
$password = (string) ($_POST['password'] ?? '');

if ($universityId === 'admin' && $password === 'admin') {
    $_SESSION['admin_pending'] = true;
    $_SESSION['admin_user'] = [
        'university_id' => 'admin',
        'name' => 'System Administrator',
    ];

    redirect('authentication.php');
}

$studentDemo = $universityId === '24-001234' && $password === 'student123';
$facultyDemo = $universityId === 'FAC0001' && $password === 'faculty123';

if ($studentDemo || $facultyDemo) {
    redirect('../dashboard.html');
}

redirect(ADMIN_LOGIN_REDIRECT . '?error=' . rawurlencode('Invalid university ID number or password.'));
