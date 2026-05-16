<?php
declare(strict_types=1);

session_start();

require_once dirname(__DIR__) . "/database_connector.php";

/*
 * ── HOW THIS WORKS ──────────────────────────────────────────────────────────
 *
 * TIME-BASED ONE-TIME PASSWORDS (TOTP, RFC 6238) sent by EMAIL.
 * Same algorithm as Google Authenticator — no app needed on the user side.
 *
 * Flow:
 *   GET  → generate 6-digit TOTP from user.totp_secret, store hashed in
 *           two_factor_challenge, email the code to the user.
 *   POST → fetch pending challenge, verify submitted code with totp_verify(),
 *           grant session access.
 *
 * SETUP (one-time):
 *   1. ALTER TABLE `user`
 *        ADD COLUMN `totp_secret` VARCHAR(64) NULL DEFAULT NULL
 *        AFTER `is_two_factor_enabled`;
 *
 *   2. Generate a secret for each user (all can share one or be unique):
 *        php -r "echo base64_encode(random_bytes(20)) . PHP_EOL;"
 *      Store the output in user.totp_secret for that user.
 *
 *   3. composer require phpmailer/phpmailer  (in project root)
 *
 *   4. Fill in MAILER CONFIG constants below.
 * ────────────────────────────────────────────────────────────────────────────
 */

// ── MAILER CONFIG — fill these in ─────────────────────────────────────────────
const SMTP_HOST       = "smtp.gmail.com";
const SMTP_PORT       = 587;
const SMTP_ENCRYPTION = "tls";
const SMTP_USERNAME   = "delacruz_bicjulian@plpasig.edu.ph";
const SMTP_PASSWORD   = "mmll bkvn klvj bxup";
const SMTP_FROM_NAME  = "Student Evaluation For Teacher";
// ─────────────────────────────────────────────────────────────────────────────

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

$user_id    = (int)    $_SESSION["login_user_id"];
$profile_id = (int)    $_SESSION["login_profile_id"];
$role_name  = (string) $_SESSION["login_role"];
$user_email = (string) ($_SESSION["login_email"] ?? "");
$error_message   = "";
$success_message = "";

// ── Pure-PHP TOTP (RFC 6238) ──────────────────────────────────────────────────

function totp_generate(string $secret, int $time_step = 30, int $digits = 6): string
{
    $counter    = (int) floor(time() / $time_step);
    $time_bytes = pack("N*", 0) . pack("N*", $counter);
    $key_bytes  = base64_decode($secret);
    $hash       = hash_hmac("sha1", $time_bytes, $key_bytes, true);
    $offset     = ord($hash[19]) & 0x0F;
    $code_int   = (
        ((ord($hash[$offset])     & 0x7F) << 24) |
        ((ord($hash[$offset + 1]) & 0xFF) << 16) |
        ((ord($hash[$offset + 2]) & 0xFF) <<  8) |
        ( ord($hash[$offset + 3]) & 0xFF)
    );
    return str_pad((string) ($code_int % (10 ** $digits)), $digits, "0", STR_PAD_LEFT);
}

function totp_verify(string $secret, string $submitted, int $window = 1, int $time_step = 30, int $digits = 6): bool
{
    $counter = (int) floor(time() / $time_step);
    for ($i = -$window; $i <= $window; $i++) {
        $t          = pack("N*", 0) . pack("N*", $counter + $i);
        $key        = base64_decode($secret);
        $hash       = hash_hmac("sha1", $t, $key, true);
        $offset     = ord($hash[19]) & 0x0F;
        $code_int   = (
            ((ord($hash[$offset])     & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) <<  8) |
            ( ord($hash[$offset + 3]) & 0xFF)
        );
        $expected = str_pad((string) ($code_int % (10 ** $digits)), $digits, "0", STR_PAD_LEFT);
        if (hash_equals($expected, $submitted)) {
            return true;
        }
    }
    return false;
}

// ── PHPMailer sender ──────────────────────────────────────────────────────────

function send_otp_email(string $to_email, string $to_name, string $code, string $role): bool
{
    $autoload = dirname(__DIR__) . "/vendor/autoload.php";
    if (!file_exists($autoload)) {
        error_log("PHPMailer not installed. Run: composer require phpmailer/phpmailer");
        return false;
    }
    require_once $autoload;

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_ENCRYPTION;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = "UTF-8";

        $mail->setFrom(SMTP_USERNAME, SMTP_FROM_NAME);
        $mail->addAddress($to_email, $to_name);

        $mail->isHTML(true);
        $mail->Subject = "Your Verification Code – Student Evaluation for Teacher";
        $mail->Body    = "
            <div style='font-family:Arial,sans-serif;max-width:480px;margin:0 auto;padding:32px;'>
                <h2 style='color:#172033;margin-bottom:8px;'>Verification Code</h2>
                <p style='color:#556070;margin-bottom:24px;'>
                    Use the code below to complete your {$role} login.
                    It expires in <strong>10 minutes</strong>.
                </p>
                <div style='background:#f3f4f6;border-radius:8px;padding:24px;text-align:center;
                            font-size:36px;font-weight:700;letter-spacing:10px;color:#172033;'>
                    {$code}
                </div>
                <p style='color:#9ca3af;font-size:13px;margin-top:24px;'>
                    If you did not attempt to log in, ignore this email.
                </p>
            </div>";
        $mail->AltBody = "Your verification code is: {$code}. It expires in 10 minutes.";

        $mail->send();
        return true;
    } catch (\Exception $e) {
        error_log("Mailer error: " . $mail->ErrorInfo);
        return false;
    }
}

// ── DB helpers ────────────────────────────────────────────────────────────────

function fetch_user_totp(PDO $pdo, int $user_id): ?array
{
    $stmt = $pdo->prepare("
        SELECT u.totp_secret, u.email,
               COALESCE(
                   CONCAT(s.first_name, ' ', s.last_name),
                   CONCAT(f.first_name, ' ', f.last_name),
                   CONCAT(a.first_name, ' ', a.last_name),
                   u.university_id
               ) AS display_name
        FROM `user` u
        LEFT JOIN student  s ON s.user_id = u.user_id
        LEFT JOIN faculty  f ON f.user_id = u.user_id
        LEFT JOIN `admin`  a ON a.user_id = u.user_id
        WHERE u.user_id = :user_id
        LIMIT 1
    ");
    $stmt->execute(["user_id" => $user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function store_challenge(PDO $pdo, int $user_id, string $code): void
{
    $pdo->prepare("
        UPDATE two_factor_challenge
        SET challenge_status = 'Expired'
        WHERE user_id = :uid AND challenge_status = 'Pending'
    ")->execute(["uid" => $user_id]);

    $pdo->prepare("
        INSERT INTO two_factor_challenge
            (user_id, challenge_type, challenge_code_hash, expiration_at, challenge_status)
        VALUES (:uid, 'PIN', :hash, DATE_ADD(NOW(), INTERVAL 10 MINUTE), 'Pending')
    ")->execute(["uid" => $user_id, "hash" => password_hash($code, PASSWORD_DEFAULT)]);
}

function mark_verified(PDO $pdo, int $user_id): void
{
    $pdo->prepare("
        UPDATE two_factor_challenge
        SET challenge_status = 'Verified', verification_at = NOW()
        WHERE user_id = :uid
          AND challenge_status = 'Pending'
          AND expiration_at >= NOW()
        ORDER BY two_factor_challenge_id DESC
        LIMIT 1
    ")->execute(["uid" => $user_id]);
}

function redirectToDashboard(string $role): void
{
    $r = strtolower($role);
    if ($r === "student") { header("Location: ../student/student_dashboard.php"); exit; }
    if ($r === "faculty") { header("Location: ../faculty/faculty_dashboard.php");  exit; }
    if ($r === "admin")   { header("Location: ../admin/admin_dashboard.php");       exit; }
    header("Location: login_page.php"); exit;
}

// ── GET: send code ────────────────────────────────────────────────────────────

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    try {
        $user_row = fetch_user_totp($pdo, $user_id);

        if (!$user_row || empty($user_row["totp_secret"])) {
            $error_message = "Two-factor authentication is not configured for this account. Please contact the admin.";
        } else {
            $code  = totp_generate($user_row["totp_secret"]);
            store_challenge($pdo, $user_id, $code);

            $email        = !empty($user_row["email"]) ? $user_row["email"] : $user_email;
            $name         = $user_row["display_name"] ?? "";
            $sent         = send_otp_email($email, $name, $code, $role_name);
            $masked       = preg_replace('/(?<=.{2}).(?=.*@)/', '*', $email);

            if ($sent) {
                $success_message = "A 6-digit code was sent to <strong>{$masked}</strong>. It expires in 10 minutes.";
            } else {
                $error_message = "Failed to send verification email. Please contact the admin.";
            }
        }
    } catch (PDOException $e) {
        error_log("2FA prep error: " . $e->getMessage());
        $error_message = "Authentication service is temporarily unavailable.";
    }
}

// ── POST: verify ──────────────────────────────────────────────────────────────

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $submitted = trim($_POST["pin_code"] ?? "");

    if (!preg_match('/^[0-9]{6}$/', $submitted)) {
        $error_message = "Please enter the complete 6-digit code.";
    } else {
        try {
            $user_row = fetch_user_totp($pdo, $user_id);

            if (!$user_row || empty($user_row["totp_secret"])) {
                $error_message = "Two-factor authentication is not configured. Please contact the admin.";
            } else {
                $stmt = $pdo->prepare("
                    SELECT two_factor_challenge_id
                    FROM two_factor_challenge
                    WHERE user_id = :uid
                      AND challenge_status = 'Pending'
                      AND expiration_at >= NOW()
                    ORDER BY two_factor_challenge_id DESC
                    LIMIT 1
                ");
                $stmt->execute(["uid" => $user_id]);
                $challenge = $stmt->fetch();

                if (!$challenge) {
                    $error_message = "Verification code expired. Please go back and log in again.";
                } elseif (!totp_verify($user_row["totp_secret"], $submitted)) {
                    $error_message = "Invalid verification code. Please try again.";
                } else {
                    mark_verified($pdo, $user_id);
                    $pdo->prepare("UPDATE `user` SET last_login_at = NOW() WHERE user_id = :uid")
                        ->execute(["uid" => $user_id]);

                    session_regenerate_id(true);
                    $_SESSION["authenticated_user_id"]    = $user_id;
                    $_SESSION["authenticated_profile_id"] = $profile_id;
                    $_SESSION["authenticated_role"]       = $role_name;
                    unset($_SESSION["two_factor_pending"], $_SESSION["login_email"]);

                    redirectToDashboard($role_name);
                }
            }
        } catch (PDOException $e) {
            error_log("2FA verify error: " . $e->getMessage());
            $error_message = "Authentication service is temporarily unavailable.";
        }
    }
}

$auth_title = match(strtolower($role_name)) {
    "student" => "Student Authentication",
    "faculty" => "Faculty Authentication",
    "admin"   => "Admin Authentication",
    default   => "Identity Authentication",
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo htmlspecialchars($auth_title, ENT_QUOTES, "UTF-8"); ?></title>
  <link rel="stylesheet" href="two-factor-authentication.css" />
</head>
<body>
  <main class="authentication-page">
    <section class="authentication-card">

      <div class="security-icon" aria-hidden="true">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
          <path d="M12 3L19 6V11C19 15.6 16.1 19.7 12 21C7.9 19.7 5 15.6 5 11V6L12 3Z"
            stroke="#172033" stroke-width="2" stroke-linejoin="round"/>
        </svg>
      </div>

      <h1><?php echo htmlspecialchars($auth_title, ENT_QUOTES, "UTF-8"); ?></h1>
      <p class="subtitle">Enter the 6-digit code sent to your email</p>

      <?php if ($success_message !== ""): ?>
        <div class="alert alert-info" style="margin-bottom:16px;padding:14px 18px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;color:#1d4ed8;font-size:14px;">
          <?php echo $success_message; ?>
        </div>
      <?php endif; ?>

      <form action="two_factor_authentication.php" method="POST" class="authentication-form">
        <label class="pin-label">Enter 6-Digit Code</label>

        <div class="pin-input-group">
          <input type="text" inputmode="numeric" maxlength="1" class="pin-box" autocomplete="one-time-code" />
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
            <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2"
              stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          Back to Login
        </a>
      </form>

    </section>
  </main>

  <script>
    const pinBoxes  = document.querySelectorAll(".pin-box");
    const pinCode   = document.getElementById("pinCode");
    const errAlert  = document.getElementById("authenticationErrorAlert");

    function updatePinCode() {
        let val = "";
        pinBoxes.forEach(b => { val += b.value; });
        pinCode.value = val;
    }

    pinBoxes.forEach(function (box, i) {
        box.addEventListener("input", function () {
            box.value = box.value.replace(/\D/g, "");
            if (box.value && i < pinBoxes.length - 1) pinBoxes[i + 1].focus();
            updatePinCode();
        });
        box.addEventListener("keydown", function (e) {
            if (e.key === "Backspace" && box.value === "" && i > 0) pinBoxes[i - 1].focus();
        });
        box.addEventListener("paste", function (e) {
            e.preventDefault();
            const pasted = e.clipboardData.getData("text").replace(/\D/g, "").slice(0, 6);
            pinBoxes.forEach((b, pi) => { b.value = pasted[pi] || ""; });
            updatePinCode();
            pinBoxes[Math.min(pasted.length, pinBoxes.length - 1)].focus();
        });
    });

    if (pinBoxes.length > 0) pinBoxes[0].focus();

    if (errAlert && errAlert.textContent.trim() !== "") {
        setTimeout(function () {
            errAlert.style.opacity = "0";
            errAlert.style.visibility = "hidden";
        }, 4000);
    }
  </script>
</body>
</html>