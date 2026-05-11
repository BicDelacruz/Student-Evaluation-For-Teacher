<?php
declare(strict_types=1);

session_start();

/*
    This page is shown when a student has a valid account,
    but the evaluation period for the student's matched active scope has already been closed.
    Keep this file inside the login folder.
*/

$back_to_login = "login_page.php";
$message = $_SESSION["evaluation_closed_message"] ?? "The student evaluation is officially closed. The evaluation period for your section has already ended. If you believe this is an error or you still need assistance, please contact the admin or your college office for further guidance.";

unset($_SESSION["evaluation_closed_message"]);
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
    <title>Evaluation Closed | Student Evaluation for Teacher</title>
    <link rel="stylesheet" href="evaluation-closed.css" />
</head>
<body class="evaluation-closed-body">
    <main class="evaluation-closed-page" aria-label="Evaluation closed notice">
        <section class="evaluation-closed-card">
            <img src="../img/eastgate_college_logo.png" alt="Eastgate College Logo" class="school-logo" />

            <h1>Student Evaluation for Teacher</h1>

            <div class="brand-divider" aria-hidden="true">
                <span></span>
            </div>

            <div class="closed-icon" aria-hidden="true">
                <svg viewBox="0 0 64 64" role="img" aria-label="Closed sign icon">
                    <circle class="pin" cx="32" cy="12" r="4"></circle>
                    <path class="hanger" d="M32 13 L18 28 M32 13 L46 28"></path>
                    <rect class="sign" x="16" y="27" width="32" height="20" rx="4"></rect>
                    <line class="minus" x1="24" y1="37" x2="40" y2="37"></line>
                </svg>
            </div>

            <h2>Evaluation Closed</h2>

            <div class="closed-message" role="alert">
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
