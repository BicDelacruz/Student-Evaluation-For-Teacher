<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

reset_admin_session(false);
redirect(ADMIN_LOGIN_REDIRECT . '?message=' . rawurlencode('Admin session ended successfully.'));
