<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function admin_icon(string $name): string
{
    $icons = [
        'dashboard' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1.5"></rect><rect x="14" y="3" width="7" height="7" rx="1.5"></rect><rect x="3" y="14" width="7" height="7" rx="1.5"></rect><rect x="14" y="14" width="7" height="7" rx="1.5"></rect></svg>',
        'students' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"></path><circle cx="9.5" cy="7" r="3.5"></circle><path d="M20 8v6"></path><path d="M23 11h-6"></path></svg>',
        'faculty' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="7" r="4"></circle><path d="M17 11a4 4 0 1 0 0-8"></path><path d="M3 21a6 6 0 0 1 12 0"></path><path d="M17 14a5 5 0 0 1 4 5"></path></svg>',
        'structure' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v14H4z"></path><path d="M12 5v14"></path></svg>',
        'assignments' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 7h13"></path><path d="M8 12h13"></path><path d="M8 17h13"></path><path d="M3 7h.01"></path><path d="M3 12h.01"></path><path d="M3 17h.01"></path></svg>',
        'setup' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.7 1.7 0 0 0-1.87-.34 1.7 1.7 0 0 0-1 1.55V21a2 2 0 0 1-4 0v-.09a1.7 1.7 0 0 0-1-1.55 1.7 1.7 0 0 0-1.87.34l-.06.06A2 2 0 1 1 4.3 16.98l.06-.06a1.7 1.7 0 0 0 .34-1.87 1.7 1.7 0 0 0-1.55-1H3a2 2 0 0 1 0-4h.09a1.7 1.7 0 0 0 1.55-1 1.7 1.7 0 0 0-.34-1.87l-.06-.06A2 2 0 1 1 7.07 4.3l.06.06a1.7 1.7 0 0 0 1.87.34h.01a1.7 1.7 0 0 0 1-1.55V3a2 2 0 0 1 4 0v.09a1.7 1.7 0 0 0 1 1.55 1.7 1.7 0 0 0 1.87-.34l.06-.06A2 2 0 1 1 19.7 7.07l-.06.06a1.7 1.7 0 0 0-.34 1.87v.01a1.7 1.7 0 0 0 1.55 1H21a2 2 0 0 1 0 4h-.09a1.7 1.7 0 0 0-1.51 1z"></path></svg>',
        'monitoring' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3v18h18"></path><path d="M7 14l4-4 3 3 5-7"></path></svg>',
        'reports' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 2h9l5 5v15H6z"></path><path d="M14 2v6h6"></path><path d="M9 14h6"></path><path d="M9 18h6"></path></svg>',
        'announcements' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 11l18-8v18l-18-8z"></path><path d="M11 13v6"></path></svg>',
        'settings' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.7 1.7 0 0 0-1.87-.34 1.7 1.7 0 0 0-1 1.55V21a2 2 0 0 1-4 0v-.09a1.7 1.7 0 0 0-1-1.55 1.7 1.7 0 0 0-1.87.34l-.06.06A2 2 0 1 1 4.3 16.98l.06-.06a1.7 1.7 0 0 0 .34-1.87 1.7 1.7 0 0 0-1.55-1H3a2 2 0 0 1 0-4h.09a1.7 1.7 0 0 0 1.55-1 1.7 1.7 0 0 0-.34-1.87l-.06-.06A2 2 0 1 1 7.07 4.3l.06.06a1.7 1.7 0 0 0 1.87.34h.01a1.7 1.7 0 0 0 1-1.55V3a2 2 0 0 1 4 0v.09a1.7 1.7 0 0 0 1 1.55 1.7 1.7 0 0 0 1.87-.34l.06-.06A2 2 0 1 1 19.7 7.07l-.06.06a1.7 1.7 0 0 0-.34 1.87v.01a1.7 1.7 0 0 0 1.55 1H21a2 2 0 0 1 0 4h-.09a1.7 1.7 0 0 0-1.51 1z"></path></svg>',
        'moon' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"></path></svg>',
        'logout' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><path d="M16 17l5-5-5-5"></path><path d="M21 12H9"></path></svg>',
        'refresh' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12a9 9 0 1 1-2.64-6.36"></path><path d="M21 3v6h-6"></path></svg>',
        'search' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="M21 21l-4.35-4.35"></path></svg>',
        'filter' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 4h18l-7 8v6l-4 2v-8z"></path></svg>',
        'plus' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14"></path><path d="M5 12h14"></path></svg>',
        'view' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"></path><circle cx="12" cy="12" r="2.5"></circle></svg>',
        'edit' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"></path><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"></path></svg>',
        'suspend' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M8.5 8.5l7 7"></path><path d="M15.5 8.5l-7 7"></path></svg>',
        'activate' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M8 12.5l2.5 2.5 5.5-6"></path></svg>',
        'delete' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="M19 6l-1 14H6L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path></svg>',
    ];

    return $icons[$name] ?? $icons['dashboard'];
}

function render_admin_layout_start(string $title, string $activeNav, string $pageHeading, string $pageSubtitle): void
{
    $navItems = admin_nav_items();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="assets/admin.css">
</head>
<body class="admin-body">
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <div class="admin-brand">
                <img src="../img/logo.png" alt="School logo" class="admin-brand-logo">
                <div class="admin-brand-name">Student Evaluation for Teacher</div>
                <div class="admin-brand-role">System Administrator</div>
            </div>
            <nav class="sidebar-nav">
                <?php foreach ($navItems as $key => $item): ?>
                    <a class="sidebar-link <?= $activeNav === $key ? 'active' : '' ?>" href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>">
                        <span class="sidebar-icon"><?= admin_icon($item['icon']) ?></span>
                        <span><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
            <div class="sidebar-footer">
                <button class="sidebar-link sidebar-button" type="button" data-dark-mode-toggle>
                    <span class="sidebar-icon"><?= admin_icon('moon') ?></span>
                    <span>Dark Mode</span>
                </button>
                <a class="sidebar-link logout-link" href="logout.php">
                    <span class="sidebar-icon"><?= admin_icon('logout') ?></span>
                    <span>Logout</span>
                </a>
            </div>
        </aside>
        <main class="admin-main">
            <header class="page-header">
                <div>
                    <h1><?= htmlspecialchars($pageHeading, ENT_QUOTES, 'UTF-8') ?></h1>
                    <p><?= htmlspecialchars($pageSubtitle, ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </header>
<?php
}

function render_admin_layout_end(): void
{
    ?>
        </main>
    </div>
    <script src="assets/admin.js" defer></script>
</body>
</html>
<?php
}
