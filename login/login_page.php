<?php
declare(strict_types=1);

session_start();

require_once dirname(__DIR__) . "/database_connector.php";

$error_message = "";
$university_id = "";

function redirectToDashboard(string $role_name): void
{
    $role_name = strtolower(trim($role_name));

    if ($role_name === "student") {
        header("Location: ../student/student_dashboard.php");
        exit;
    }

    if ($role_name === "faculty") {
        header("Location: ../faculty/faculty_dashboard.php");
        exit;
    }

    if ($role_name === "admin") {
        header("Location: ../admin/admin_dashboard.php");
        exit;
    }

    header("Location: login_page.php");
    exit;
}

function clearAuthenticationSession(): void
{
    unset(
        $_SESSION["login_user_id"],
        $_SESSION["login_profile_id"],
        $_SESSION["login_role"],
        $_SESSION["login_university_id"],
        $_SESSION["authenticated_user_id"],
        $_SESSION["authenticated_profile_id"],
        $_SESSION["authenticated_role"],
        $_SESSION["two_factor_pending"]
    );
}

function fetchStudentScopeProfile(PDO $pdo, int $student_id): ?array
{
    if ($student_id <= 0) {
        return null;
    }

    $student_query = "
        SELECT
            s.student_id,
            COALESCE(sec.course_id, s.course_id) AS active_course_id,
            COALESCE(sec.year_level, s.current_year_level) AS active_year_level,
            COALESCE(sec.section_id, s.current_section_id) AS active_section_id,
            COALESCE(active_course.department_id, base_course.department_id) AS active_department_id
        FROM student s
        INNER JOIN course base_course
            ON base_course.course_id = s.course_id
        LEFT JOIN student_section_enrollment sse
            ON sse.student_id = s.student_id
            AND sse.enrollment_status = 'Active'
        LEFT JOIN section sec
            ON sec.section_id = COALESCE(sse.section_id, s.current_section_id)
            AND sec.section_status = 'Active'
        LEFT JOIN course active_course
            ON active_course.course_id = COALESCE(sec.course_id, s.course_id)
        WHERE s.student_id = :student_id
        ORDER BY sse.student_section_enrollment_id DESC
        LIMIT 1
    ";

    $student_statement = $pdo->prepare($student_query);
    $student_statement->execute(["student_id" => $student_id]);
    $student = $student_statement->fetch(PDO::FETCH_ASSOC);

    return $student ?: null;
}

function studentHasMatchingScope(PDO $pdo, array $student, string $status_group): bool
{
    $department_id = (int) ($student["active_department_id"] ?? 0);
    $course_id = (int) ($student["active_course_id"] ?? 0);
    $year_level = (int) ($student["active_year_level"] ?? 0);
    $section_id = (int) ($student["active_section_id"] ?? 0);

    if ($status_group === "open") {
        $status_condition = "eps.scope_status = 'Active' AND ep.period_status IN ('Open', 'Ongoing')";
    } else {
        $status_condition = "(
            eps.scope_status IN ('Completed', 'Closed', 'Archived')
            OR ep.period_status IN ('Closed', 'Archived')
        )";
    }

    $scope_query = "
        SELECT 1
        FROM evaluation_period_scope eps
        INNER JOIN evaluation_period ep
            ON ep.evaluation_period_id = eps.evaluation_period_id
        WHERE $status_condition
            AND (
                eps.scope_type = 'All'
                OR (eps.scope_type = 'Department' AND eps.department_id = :department_id)
                OR (eps.scope_type = 'Course' AND eps.course_id = :course_id)
                OR (eps.scope_type = 'Year Level' AND eps.year_level = :year_level)
                OR (eps.scope_type = 'Section' AND eps.section_id = :section_id)
            )
        ORDER BY
            COALESCE(eps.closed_at, eps.opened_at, ep.updated_at) DESC,
            eps.evaluation_period_scope_id DESC
        LIMIT 1
    ";

    $scope_statement = $pdo->prepare($scope_query);
    $scope_statement->execute([
        "department_id" => $department_id,
        "course_id" => $course_id,
        "year_level" => $year_level,
        "section_id" => $section_id
    ]);

    return (bool) $scope_statement->fetchColumn();
}

function getStudentEvaluationAccessStatus(PDO $pdo, int $student_id): string
{
    $student = fetchStudentScopeProfile($pdo, $student_id);

    if (!$student) {
        return "not_yet_open";
    }

    if (studentHasMatchingScope($pdo, $student, "open")) {
        return "open";
    }

    if (studentHasMatchingScope($pdo, $student, "closed")) {
        return "closed";
    }

    return "not_yet_open";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $university_id = trim($_POST["university_id"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($university_id === "" || $password === "") {
        $error_message = "Please enter your University ID Number and password.";
    } else {
        try {
            $query = "
                SELECT
                    u.user_id,
                    u.university_id,
                    u.email,
                    u.password_hash,
                    u.account_status,
                    u.is_two_factor_enabled,
                    r.role_name,
                    r.status AS role_status,
                    s.student_id,
                    s.student_status,
                    f.faculty_id,
                    f.faculty_status,
                    a.admin_id,
                    a.admin_status
                FROM `user` u
                INNER JOIN `role` r
                    ON r.role_id = u.role_id
                LEFT JOIN student s
                    ON s.user_id = u.user_id
                LEFT JOIN faculty f
                    ON f.user_id = u.user_id
                LEFT JOIN `admin` a
                    ON a.user_id = u.user_id
                WHERE u.university_id = :university_id
                LIMIT 1
            ";

            $statement = $pdo->prepare($query);
            $statement->execute([
                "university_id" => $university_id
            ]);

            $account = $statement->fetch();

            if (!$account || !password_verify($password, $account["password_hash"])) {
                $error_message = "Invalid University ID Number or password.";
            } elseif ($account["role_status"] !== "Active") {
                $error_message = "This role is currently inactive.";
            } elseif ($account["account_status"] !== "Active") {
                $error_message = "This account is not active.";
            } else {
                $role_name = $account["role_name"];
                $profile_id = null;
                $profile_status = null;

                if ($role_name === "Student") {
                    $profile_id = $account["student_id"];
                    $profile_status = $account["student_status"];
                }

                if ($role_name === "Faculty") {
                    $profile_id = $account["faculty_id"];
                    $profile_status = $account["faculty_status"];
                }

                if ($role_name === "Admin") {
                    $profile_id = $account["admin_id"];
                    $profile_status = $account["admin_status"];
                }

                if ($profile_id === null) {
                    $error_message = "No valid profile was found for this account.";
                } elseif ($profile_status !== "Active") {
                    $error_message = "Your profile is not active.";
                } else {
                    if ($role_name === "Student") {
                        $evaluation_access_status = getStudentEvaluationAccessStatus($pdo, (int) $profile_id);

                        if ($evaluation_access_status === "closed") {
                            clearAuthenticationSession();
                            $_SESSION["evaluation_closed_message"] = "The student evaluation is officially closed. The evaluation period for your section has already ended. If you believe this is an error or you still need assistance, please contact the admin or your college office for further guidance.";
                            header("Location: evaluation_closed.php");
                            exit;
                        }

                        if ($evaluation_access_status !== "open") {
                            clearAuthenticationSession();
                            $_SESSION["evaluation_access_message"] = "You cannot log in yet to the student evaluation. Please wait for the announcement on when the admin will open the student evaluation for your section.";
                            header("Location: evaluation_not_yet_open.php");
                            exit;
                        }
                    }

                    session_regenerate_id(true);

                    $_SESSION["login_user_id"] = (int) $account["user_id"];
                    $_SESSION["login_profile_id"] = (int) $profile_id;
                    $_SESSION["login_role"] = $role_name;
                    $_SESSION["login_university_id"] = $account["university_id"];

                    if ((int) $account["is_two_factor_enabled"] === 1) {
                        $_SESSION["two_factor_pending"] = true;
                        header("Location: two_factor_authentication.php");
                        exit;
                    }

                    $_SESSION["authenticated_user_id"] = (int) $account["user_id"];
                    $_SESSION["authenticated_profile_id"] = (int) $profile_id;
                    $_SESSION["authenticated_role"] = $role_name;

                    $update_login_query = "
                        UPDATE `user`
                        SET last_login_at = NOW()
                        WHERE user_id = :user_id
                    ";

                    $update_statement = $pdo->prepare($update_login_query);
                    $update_statement->execute([
                        "user_id" => $account["user_id"]
                    ]);

                    redirectToDashboard($role_name);
                }
            }
        } catch (PDOException $error) {
            error_log("Login error: " . $error->getMessage());
            $error_message = "Login service is temporarily unavailable.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <title>Student Evaluation for Teacher | Login</title>

  <link rel="stylesheet" href="login-page.css" />
</head>
<body class="login-body">
  <main class="login-page">
    <section class="login-card">
      <img src="../img/eastgate_college_logo.png" alt="Eastgate College Logo" class="school-logo" />

      <h1>Student Evaluation for Teacher</h1>
      <p class="subtitle">Sign in to continue</p>

      <form class="login-form" action="login_page.php" method="POST">
        <div class="form-group">
          <label for="university_id">University ID Number</label>
          <input
            type="text"
            id="university_id"
            name="university_id"
            placeholder="Enter your ID number"
            value="<?php echo htmlspecialchars($university_id, ENT_QUOTES, 'UTF-8'); ?>"
            required
          />
        </div>

        <div class="form-group">
          <label for="password">Password</label>

          <div class="password-wrapper">
            <input
              type="password"
              id="password"
              name="password"
              placeholder="Enter your password"
              required
            />

            <button type="button" class="password-toggle" id="passwordToggle" aria-label="Show password">
                <span class="eye-open-icon" style="display: none;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </span>

                <span class="eye-closed-icon" style="display: flex;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 3L21 21"></path>
                        <path d="M10.73 5.08C11.15 5.03 11.57 5 12 5C17 5 20.73 8.11 22 12C21.63 13.13 20.93 14.19 19.98 15.1"></path>
                        <path d="M6.61 6.61C4.55 7.86 3 9.78 2 12C3.27 15.89 7 19 12 19C13.52 19 14.93 18.7 16.18 18.16"></path>
                        <path d="M9.88 9.88C9.34 10.42 9 11.17 9 12C9 13.66 10.34 15 12 15C12.83 15 13.58 14.66 14.12 14.12"></path>
                    </svg>
                </span>
            </button>
          </div>
        </div>

        <?php if ($error_message !== ""): ?>
          <div class="alert alert-error" id="loginErrorAlert">
            <?php echo htmlspecialchars($error_message, ENT_QUOTES, "UTF-8"); ?>
          </div>
        <?php endif; ?>

        <button type="submit" class="btn btn-login">Login</button>
        <button type="button" class="btn btn-clear" id="clearLoginForm">Clear</button>
      </form>
    </section>
  </main>

  <script>
    const universityIdInput = document.getElementById("university_id");
    const passwordInput = document.getElementById("password");
    const passwordToggle = document.getElementById("passwordToggle");
    const clearLoginForm = document.getElementById("clearLoginForm");

    passwordToggle.addEventListener("click", function () {
      const eyeOpen = document.querySelector(".eye-open-icon");
      const eyeClosed = document.querySelector(".eye-closed-icon");

      if (passwordInput.type === "password") {
        passwordInput.type = "text";
        eyeOpen.style.display = "flex";
        eyeClosed.style.display = "none";
        passwordToggle.setAttribute("aria-label", "Hide password");
      } else {
        passwordInput.type = "password";
        eyeOpen.style.display = "none";
        eyeClosed.style.display = "flex";
        passwordToggle.setAttribute("aria-label", "Show password");
      }
    });

    clearLoginForm.addEventListener("click", function () {
      universityIdInput.value = "";
      passwordInput.value = "";
      universityIdInput.focus();
    });
  </script>
</body>
</html>