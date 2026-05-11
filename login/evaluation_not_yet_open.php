<?php
declare(strict_types=1);

session_start();

/*
    This page is shown when a student has a valid account,
    but the admin has not opened an evaluation period for the student's active scope yet.
    Keep this file inside the login folder.
*/

$back_to_login = "login_page.php";
$message = $_SESSION["evaluation_access_message"] ?? "You cannot log in yet to the student evaluation.\n\nPlease wait for the announcement on when the admin will open the student evaluation for your section.";

unset($_SESSION["evaluation_access_message"]);
unset($_SESSION["login_user_id"], $_SESSION["login_profile_id"], $_SESSION["login_role"], $_SESSION["login_university_id"]);
unset($_SESSION["authenticated_user_id"], $_SESSION["authenticated_profile_id"], $_SESSION["authenticated_role"]);
unset($_SESSION["two_factor_pending"]);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    <title>Evaluation Not Yet Open | Student Evaluation for Teacher</title>
    <link rel="stylesheet" href="evaluation-not-yet-open.css" />
</head>
<body class="evaluation-closed-body">
    <main class="evaluation-closed-page" aria-label="Evaluation access notice">
        <section class="evaluation-closed-card">
            <img src="../img/eastgate_college_logo.png" alt="Eastgate College Logo" class="school-logo" />

            <h1>Student Evaluation for Teacher</h1>

            <div class="brand-divider" aria-hidden="true">
                <span></span>
            </div>

            <div class="warning-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" role="img" aria-label="Warning icon">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                    <line x1="12" y1="9" x2="12" y2="13"></line>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
            </div>

            <h2>Evaluation Not Yet Open</h2>

            <div class="notice-message" role="alert">
                <?php echo nl2br(e($message)); ?>
            </div>

            <a href="<?php echo e($back_to_login); ?>" class="back-login-button" aria-label="Back to Login">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                <span>Back to Login</span>
            </a>
        </section>
    </main>
</body>
</html>
