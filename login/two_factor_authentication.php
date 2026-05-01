<?php
declare(strict_types=1);

session_start();

require_once dirname(__DIR__) . "/database_connector.php";

if (isset($_GET["cancel"])) {
    session_unset();
    session_destroy();

    header("Location: login_page.php");
    exit;
}

if (
    empty($_SESSION["login_user_id"]) ||
    empty($_SESSION["login_role"]) ||
    empty($_SESSION["login_profile_id"]) ||
    empty($_SESSION["two_factor_pending"])
) {
    header("Location: login_page.php");
    exit;
}

$user_id = (int) $_SESSION["login_user_id"];
$profile_id = (int) $_SESSION["login_profile_id"];
$role_name = (string) $_SESSION["login_role"];
$error_message = "";

function getAuthenticationTitle(string $role_name): string
{
    if ($role_name === "Student") {
        return "Student Authentication";
    }

    if ($role_name === "Faculty") {
        return "Faculty Authentication";
    }

    if ($role_name === "Admin") {
        return "Admin Authentication";
    }

    return "Identity Authentication";
}

function getRolePin(string $role_name): string
{
    if ($role_name === "Admin") {
        return "99999";
    }

    if ($role_name === "Faculty") {
        return "65432";
    }

    if ($role_name === "Student") {
        return "12345";
    }

    return "00000";
}

function redirectToDashboard(string $role_name): void
{
    $role_name = strtolower($role_name);

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

function prepareTwoFactorChallenge(PDO $pdo, int $user_id, string $role_name): void
{
    $role_pin = getRolePin($role_name);

    $expire_old_query = "
        UPDATE two_factor_challenge
        SET challenge_status = 'Expired'
        WHERE user_id = :user_id
        AND challenge_status = 'Pending'
    ";

    $expire_old_statement = $pdo->prepare($expire_old_query);
    $expire_old_statement->execute([
        "user_id" => $user_id
    ]);

    $insert_query = "
        INSERT INTO two_factor_challenge (
            user_id,
            challenge_type,
            challenge_code_hash,
            expiration_at,
            challenge_status
        )
        VALUES (
            :user_id,
            'PIN',
            :challenge_code_hash,
            DATE_ADD(NOW(), INTERVAL 10 MINUTE),
            'Pending'
        )
    ";

    $insert_statement = $pdo->prepare($insert_query);
    $insert_statement->execute([
        "user_id" => $user_id,
        "challenge_code_hash" => password_hash($role_pin, PASSWORD_DEFAULT)
    ]);
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    try {
        prepareTwoFactorChallenge($pdo, $user_id, $role_name);
    } catch (PDOException $error) {
        error_log("2FA challenge preparation error: " . $error->getMessage());
        $error_message = "Authentication service is temporarily unavailable.";
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $pin_code = trim($_POST["pin_code"] ?? "");

    if (!preg_match("/^[0-9]{5}$/", $pin_code)) {
        $error_message = "Please enter the complete 5 digit PIN.";
    } else {
        try {
            $query = "
                SELECT
                    two_factor_challenge_id,
                    challenge_code_hash
                FROM two_factor_challenge
                WHERE user_id = :user_id
                AND challenge_status = 'Pending'
                AND expiration_at >= NOW()
                ORDER BY two_factor_challenge_id DESC
                LIMIT 1
            ";

            $statement = $pdo->prepare($query);
            $statement->execute([
                "user_id" => $user_id
            ]);

            $challenge = $statement->fetch();

            if (!$challenge) {
                $error_message = "Verification code expired. Please go back and login again.";
            } elseif (!password_verify($pin_code, $challenge["challenge_code_hash"])) {
                $error_message = "Invalid verification PIN.";
            } else {
                $update_challenge_query = "
                    UPDATE two_factor_challenge
                    SET
                        challenge_status = 'Verified',
                        verification_at = NOW()
                    WHERE two_factor_challenge_id = :two_factor_challenge_id
                ";

                $update_challenge_statement = $pdo->prepare($update_challenge_query);
                $update_challenge_statement->execute([
                    "two_factor_challenge_id" => $challenge["two_factor_challenge_id"]
                ]);

                $update_login_query = "
                    UPDATE `user`
                    SET last_login_at = NOW()
                    WHERE user_id = :user_id
                ";

                $update_login_statement = $pdo->prepare($update_login_query);
                $update_login_statement->execute([
                    "user_id" => $user_id
                ]);

                session_regenerate_id(true);

                $_SESSION["authenticated_user_id"] = $user_id;
                $_SESSION["authenticated_profile_id"] = $profile_id;
                $_SESSION["authenticated_role"] = $role_name;

                unset($_SESSION["two_factor_pending"]);

                redirectToDashboard($role_name);
            }
        } catch (PDOException $error) {
            error_log("2FA verification error: " . $error->getMessage());
            $error_message = "Authentication service is temporarily unavailable.";
        }
    }
}

$authentication_title = getAuthenticationTitle($role_name);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <title><?php echo htmlspecialchars($authentication_title, ENT_QUOTES, "UTF-8"); ?></title>

  <link rel="stylesheet" href="two-factor-authentication.css" />
</head>
<body>
  <main class="authentication-page">
    <section class="authentication-card">
      <div class="security-icon" aria-hidden="true">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
          <path
            d="M12 3L19 6V11C19 15.6 16.1 19.7 12 21C7.9 19.7 5 15.6 5 11V6L12 3Z"
            stroke="#172033"
            stroke-width="2"
            stroke-linejoin="round"
          />
        </svg>
      </div>

      <h1><?php echo htmlspecialchars($authentication_title, ENT_QUOTES, "UTF-8"); ?></h1>
      <p class="subtitle">Scan the QR Code to verify your identity</p>

      <div class="qr-panel">
        <svg class="qr-code-icon" viewBox="0 0 120 120" aria-hidden="true">
          <rect x="8" y="8" width="104" height="104" rx="4" fill="none" stroke="#172033" stroke-width="4"/>

          <rect x="22" y="22" width="26" height="26" rx="4" fill="#172033"/>
          <rect x="30" y="30" width="10" height="10" rx="2" fill="#ffffff"/>

          <rect x="72" y="22" width="26" height="26" rx="4" fill="#172033"/>
          <rect x="80" y="30" width="10" height="10" rx="2" fill="#ffffff"/>

          <rect x="22" y="72" width="26" height="26" rx="4" fill="#172033"/>
          <rect x="30" y="80" width="10" height="10" rx="2" fill="#ffffff"/>

          <rect x="56" y="24" width="8" height="8" rx="2" fill="#172033"/>
          <rect x="55" y="48" width="10" height="10" rx="2" fill="#172033"/>
          <rect x="70" y="58" width="9" height="9" rx="2" fill="#172033"/>
          <rect x="88" y="58" width="10" height="10" rx="2" fill="#172033"/>
          <rect x="56" y="72" width="10" height="10" rx="2" fill="#172033"/>
          <rect x="72" y="78" width="8" height="22" rx="2" fill="#172033"/>
          <rect x="88" y="82" width="10" height="8" rx="2" fill="#172033"/>
          <rect x="100" y="94" width="8" height="8" rx="2" fill="#172033"/>
          <rect x="50" y="96" width="10" height="10" rx="2" fill="#172033"/>
          <rect x="26" y="56" width="8" height="8" rx="2" fill="#172033"/>
          <rect x="42" y="56" width="18" height="8" rx="2" fill="#172033"/>
        </svg>

        <p>Scan this Unique QR Code to verify and register your device</p>
      </div>

      <form action="two_factor_authentication.php" method="POST" class="authentication-form">
        <label class="pin-label">Enter 5 Digit PIN</label>

        <div class="pin-input-group">
          <input type="text" inputmode="numeric" maxlength="1" class="pin-box" />
          <input type="text" inputmode="numeric" maxlength="1" class="pin-box" />
          <input type="text" inputmode="numeric" maxlength="1" class="pin-box" />
          <input type="text" inputmode="numeric" maxlength="1" class="pin-box" />
          <input type="text" inputmode="numeric" maxlength="1" class="pin-box" />
        </div>

        <input type="hidden" name="pin_code" id="pinCode" />

        <div class="alert-space">
          <?php if ($error_message !== ""): ?>
            <div class="alert alert-error" id="authenticationErrorAlert">
              <?php echo htmlspecialchars($error_message, ENT_QUOTES, "UTF-8"); ?>
            </div>
          <?php else: ?>
            <div class="alert alert-error alert-hidden" id="authenticationErrorAlert"></div>
          <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary">Verify Identity</button>

        <a href="two_factor_authentication.php?cancel=1" class="btn btn-secondary">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          Back to Login
        </a>
      </form>
    </section>
  </main>

  <script>
    const pinBoxes = document.querySelectorAll(".pin-box");
    const pinCode = document.getElementById("pinCode");
    const authenticationErrorAlert = document.getElementById("authenticationErrorAlert");

    function updatePinCode() {
      let pinValue = "";

      pinBoxes.forEach(function (box) {
        pinValue += box.value;
      });

      pinCode.value = pinValue;
    }

    pinBoxes.forEach(function (box, index) {
      box.addEventListener("input", function () {
        box.value = box.value.replace(/\D/g, "");

        if (box.value && index < pinBoxes.length - 1) {
          pinBoxes[index + 1].focus();
        }

        updatePinCode();
      });

      box.addEventListener("keydown", function (event) {
        if (event.key === "Backspace" && box.value === "" && index > 0) {
          pinBoxes[index - 1].focus();
        }
      });

      box.addEventListener("paste", function (event) {
        event.preventDefault();

        const pastedValue = event.clipboardData.getData("text").replace(/\D/g, "").slice(0, 5);

        pinBoxes.forEach(function (pinBox, pinIndex) {
          pinBox.value = pastedValue[pinIndex] || "";
        });

        updatePinCode();

        const focusIndex = Math.min(pastedValue.length, pinBoxes.length - 1);
        pinBoxes[focusIndex].focus();
      });
    });

    if (pinBoxes.length > 0) {
      pinBoxes[0].focus();
    }

    if (authenticationErrorAlert && authenticationErrorAlert.textContent.trim() !== "") {
      setTimeout(function () {
        authenticationErrorAlert.style.opacity = "0";
        authenticationErrorAlert.style.visibility = "hidden";
      }, 3000);
    }
  </script>
</body>
</html>