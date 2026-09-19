<?php
declare(strict_types=1);

ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/src/Exception.php';
require_once __DIR__ . '/src/PHPMailer.php';
require_once __DIR__ . '/src/SMTP.php';

function resetResponse(bool $success, string $message = ''): never
{
    echo json_encode([
        'success' => $success,
        'message' => $message
    ]);
    exit;
}

$email = strtolower(trim($_POST['email'] ?? ''));

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    resetResponse(false, 'Enter a valid email address.');
}

$stmt = $conn->prepare('SELECT first_name FROM students WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Do not reveal whether an email is registered.
if (!$student) {
    resetResponse(true, 'If that email exists, a reset link has been sent.');
}

if (MAIL_USERNAME === '' || MAIL_PASSWORD === '') {
    error_log('CourseMatch mail is not configured. Set MAIL_USERNAME and MAIL_PASSWORD.');
    http_response_code(500);
    resetResponse(false, 'Password reset email is not configured yet.');
}

$token = bin2hex(random_bytes(32));
$tokenHash = hash('sha256', $token);

$delete = $conn->prepare('DELETE FROM password_resets WHERE email = ?');
$delete->bind_param('s', $email);
$delete->execute();
$delete->close();

$insert = $conn->prepare(
    'INSERT INTO password_resets (email, token, expires_at)
     VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))'
);
$insert->bind_param('ss', $email, $tokenHash);
$insert->execute();
$insert->close();

$resetLink = APP_URL . '/reset-password.php?token=' . urlencode($token) . '&email=' . urlencode($email);
$firstNameText = $student['first_name'] ?: 'there';
$firstNameHtml = htmlspecialchars($firstNameText, ENT_QUOTES, 'UTF-8');
$resetLinkHtml = htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8');

$html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f5f7ff;font-family:Arial,sans-serif;color:#182034">
  <table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 16px;background:#f5f7ff">
    <tr><td align="center">
      <table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;background:#fff;border-radius:18px;overflow:hidden">
        <tr><td style="padding:34px 40px;background:#5b5bd6;color:#fff">
          <h1 style="margin:0;font-size:24px">Reset your CourseMatch password</h1>
        </td></tr>
        <tr><td style="padding:36px 40px">
          <p style="margin:0 0 16px">Hi <strong>{$firstNameHtml}</strong>,</p>
          <p style="margin:0 0 24px;line-height:1.6;color:#59617a">
            We received a request to reset your password. This link expires in one hour.
          </p>
          <p style="text-align:center;margin:0 0 28px">
            <a href="{$resetLinkHtml}" style="display:inline-block;padding:13px 24px;background:#5b5bd6;color:#fff;text-decoration:none;border-radius:10px;font-weight:700">
              Reset password
            </a>
          </p>
          <p style="margin:0;color:#7b8298;font-size:13px;line-height:1.6">
            If you did not request this, you can safely ignore this email.
          </p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;

try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = MAIL_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = MAIL_USERNAME;
    $mail->Password = MAIL_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = MAIL_PORT;

    $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
    $mail->addAddress($email, $firstNameText);
    $mail->isHTML(true);
    $mail->Subject = 'Reset your CourseMatch password';
    $mail->Body = $html;
    $mail->AltBody = "Reset your CourseMatch password: {$resetLink} (expires in one hour).";
    $mail->send();

    resetResponse(true, 'If that email exists, a reset link has been sent.');
} catch (Exception $e) {
    error_log('CourseMatch mailer error: ' . $e->getMessage());
    http_response_code(500);
    resetResponse(false, 'Unable to send the reset email right now.');
}
