<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

require_admin_verified();

$page = trim((string) ($_GET['page'] ?? ''));
if (!is_valid_placeholder($page)) {
    redirect('dashboard.php');
}

$title = placeholder_title($page);

render_admin_layout_start(
    $title,
    $page,
    $title,
    'This module is prepared with protected navigation and a placeholder screen for now.'
);
?>
<section class="panel placeholder-panel">
    <div class="placeholder-icon"><?= admin_icon('settings') ?></div>
    <h2><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h2>
    <p>This Admin module is not yet part of the current implementation batch. The page is protected, linked from the sidebar, and ready to be expanded later without changing the Admin flow.</p>
    <div class="placeholder-actions">
        <a class="primary-button inline-flex" href="dashboard.php">Back to Dashboard</a>
        <?php if ($page !== 'faculty'): ?>
            <a class="secondary-button inline-flex" href="students.php">Open Student Management</a>
        <?php endif; ?>
    </div>
</section>
<?php render_admin_layout_end(); ?>
