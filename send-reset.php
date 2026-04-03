<?php
ini_set('display_errors', 0);
date_default_timezone_set('Asia/Manila'); // Set to Philippines timezone
header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if (!file_exists(__DIR__ . '/src/PHPMailer.php')) {
    echo json_encode(['success' => false, 'message' => 'PHPMailer src/ folder not found.']);
    exit;
}

require __DIR__ . '/src/Exception.php';
require __DIR__ . '/src/PHPMailer.php';
require __DIR__ . '/src/SMTP.php';

// ── Config ────────────────────────────────────────────────
$db_host        = 'localhost';
$db_name        = 'coursematch_db';
$db_user        = 'root';
$db_pass        = '';
$gmail_address  = 'aronditee@gmail.com';
$gmail_password = 'mtmv okfm vftu mxhe';
$from_name      = 'CourseMatch';
$base_url       = 'http://localhost/Course-Recommender-main/Course-Recommender-main/CourseRecommender';
// ─────────────────────────────────────────────────────────

$email = trim($_POST['email'] ?? '');

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
    exit;
}

try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8",
        $db_user, $db_pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $stmt = $pdo->prepare("SELECT id, first_name FROM students WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        // Return success anyway to avoid revealing which emails are registered
        echo json_encode(['success' => true]);
        exit;
    }

    $token      = bin2hex(random_bytes(32));
    $token_hash = hash('sha256', $token);
    

    $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
    $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))")
        ->execute([$email, $token_hash]);

    $reset_link = "$base_url/reset-password.php?token=$token&email=" . urlencode($email);
    $first_name = htmlspecialchars($student['first_name']);

    $html = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#f0f2ff;font-family:'Segoe UI',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f2ff;padding:40px 0;">
    <tr><td align="center">
      <table width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 8px 32px rgba(100,60,180,0.12);">
        <tr>
          <td style="background:linear-gradient(135deg,#667eea,#764ba2);padding:40px 48px;text-align:center;">
            <div style="font-size:36px;margin-bottom:10px;">&#128273;</div>
            <h1 style="margin:0;color:#ffffff;font-size:24px;font-weight:700;">Password Reset</h1>
            <p style="margin:8px 0 0;color:rgba(255,255,255,0.8);font-size:14px;">CourseMatch Account Security</p>
          </td>
        </tr>
        <tr>
          <td style="padding:40px 48px;">
            <p style="margin:0 0 16px;font-size:16px;color:#1a1a2e;">Hi <strong>{$first_name}</strong>!</p>
            <p style="margin:0 0 24px;font-size:14px;color:#555577;line-height:1.7;">
              We received a request to reset your CourseMatch password.
              Click the button below to choose a new password. This link expires in <strong>1 hour</strong>.
            </p>
            <table width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td align="center" style="padding:8px 0 28px;">
                  <a href="{$reset_link}" style="display:inline-block;padding:14px 36px;background:linear-gradient(135deg,#667eea,#764ba2);color:#ffffff;text-decoration:none;border-radius:10px;font-size:15px;font-weight:700;">
                    Reset My Password
                  </a>
                </td>
              </tr>
            </table>
            <p style="margin:0 0 8px;font-size:13px;color:#8888aa;">Or copy this link into your browser:</p>
            <p style="margin:0 0 28px;font-size:12px;color:#667eea;word-break:break-all;">{$reset_link}</p>
            <hr style="border:none;border-top:1px solid #eef0ff;margin:0 0 24px;">
            <p style="margin:0;font-size:13px;color:#aaaacc;line-height:1.6;">
              If you didn't request this, you can safely ignore this email.
            </p>
          </td>
        </tr>
        <tr>
          <td style="background:#f7f8ff;padding:24px 48px;text-align:center;">
            <p style="margin:0;font-size:12px;color:#aaaacc;">&copy; 2025 CourseMatch &middot; Sent to {$email}</p>
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $gmail_address;
    $mail->Password   = $gmail_password;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // Fix SSL certificate error on XAMPP localhost
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true,
        ]
    ];

    $mail->setFrom($gmail_address, $from_name);
    $mail->addAddress($email, $first_name);
    $mail->isHTML(true);
    $mail->Subject = 'Reset Your CourseMatch Password';
    $mail->Body    = $html;
    $mail->AltBody = "Hi $first_name, reset your password here: $reset_link (expires in 1 hour)";

    $mail->send();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Mailer error: ' . $e->getMessage()]);
} catch (\Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}