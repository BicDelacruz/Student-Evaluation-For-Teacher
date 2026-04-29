<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

require_admin_verified();

$students = load_students();
$metrics = dashboard_metrics($students);

render_admin_layout_start(
    'Admin Dashboard',
    'dashboard',
    'Dashboard',
    'System overview and quick statistics'
);
?>
<div class="dashboard-toolbar">
    <a class="ghost-button" href="dashboard.php">
        <span class="button-icon"><?= admin_icon('refresh') ?></span>
        Refresh
    </a>
</div>

<section class="stats-grid">
    <article class="stat-card">
        <div class="stat-label">Total Students</div>
        <div class="stat-value blue"><?= (int) $metrics['total_students'] ?></div>
        <div class="stat-subtext">Click to manage</div>
        <div class="stat-icon blue"><?= admin_icon('students') ?></div>
    </article>
    <article class="stat-card">
        <div class="stat-label">Total Faculty</div>
        <div class="stat-value green"><?= (int) $metrics['total_faculty'] ?></div>
        <div class="stat-subtext">Click to manage</div>
        <div class="stat-icon green"><?= admin_icon('faculty') ?></div>
    </article>
    <article class="stat-card">
        <div class="stat-label">Total Subjects</div>
        <div class="stat-value gold"><?= (int) $metrics['total_subjects'] ?></div>
        <div class="stat-subtext">20 sections total</div>
        <div class="stat-icon gold"><?= admin_icon('structure') ?></div>
    </article>
    <article class="stat-card">
        <div class="stat-label">Participation Rate</div>
        <div class="stat-value violet"><?= htmlspecialchars((string) $metrics['participation_rate'], ENT_QUOTES, 'UTF-8') ?>%</div>
        <div class="stat-subtext"><?= (int) $metrics['submitted'] ?> of <?= (int) $metrics['total_participants'] ?> submitted</div>
        <div class="stat-icon violet"><?= admin_icon('monitoring') ?></div>
    </article>
</section>

<section class="content-grid two-column">
    <article class="panel">
        <h2>Evaluation Status</h2>
        <div class="status-summary success">
            <div>
                <div class="status-title">Completed</div>
                <div class="status-text">Released to faculty</div>
            </div>
            <strong><?= (int) $metrics['completed_evaluations'] ?></strong>
        </div>
        <div class="status-summary warning">
            <div>
                <div class="status-title">Pending</div>
                <div class="status-text">Not yet released</div>
            </div>
            <strong><?= (int) $metrics['pending_evaluations'] ?></strong>
        </div>
    </article>

    <article class="panel">
        <h2>Current Schedule</h2>
        <div class="schedule-card">
            <div class="schedule-badge"><?= htmlspecialchars((string) $metrics['schedule_status'], ENT_QUOTES, 'UTF-8') ?></div>
            <div class="schedule-term"><?= htmlspecialchars((string) $metrics['semester'], ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars((string) $metrics['academic_year'], ENT_QUOTES, 'UTF-8') ?></div>
            <div class="schedule-dates">
                <div>
                    <span>Start Date</span>
                    <strong><?= htmlspecialchars((string) $metrics['start_date'], ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
                <div>
                    <span>End Date</span>
                    <strong><?= htmlspecialchars((string) $metrics['end_date'], ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
            </div>
        </div>
        <a class="wide-action-button" href="placeholder.php?page=setup">Manage Evaluation Setup</a>
    </article>
</section>

<section class="panel">
    <h2>Quick Actions</h2>
    <div class="quick-actions-grid">
        <a class="quick-action-card" href="students.php">
            <span class="quick-action-icon"><?= admin_icon('students') ?></span>
            <div>
                <strong>View Students</strong>
                <span>Manage student accounts</span>
            </div>
        </a>
        <a class="quick-action-card" href="placeholder.php?page=faculty">
            <span class="quick-action-icon"><?= admin_icon('faculty') ?></span>
            <div>
                <strong>View Faculty</strong>
                <span>Manage faculty accounts</span>
            </div>
        </a>
        <a class="quick-action-card" href="placeholder.php?page=monitoring">
            <span class="quick-action-icon"><?= admin_icon('monitoring') ?></span>
            <div>
                <strong>View Pending</strong>
                <span>Monitor submissions</span>
            </div>
        </a>
        <a class="quick-action-card" href="placeholder.php?page=reports">
            <span class="quick-action-icon"><?= admin_icon('reports') ?></span>
            <div>
                <strong>View Reports</strong>
                <span>Generate analytics</span>
            </div>
        </a>
    </div>
</section>

<section class="system-note">
    <strong>System Information:</strong>
    You are logged in as System Administrator. You have full access to all system features and data.
</section>
<?php render_admin_layout_end(); ?>
