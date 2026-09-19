<?php
declare(strict_types=1);

require_once __DIR__ . '/db_connect.php';

$token = trim($_GET['token'] ?? '');
$email = strtolower(trim($_GET['email'] ?? ''));
$error = '';
$success = false;
$validToken = false;

if ($token !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $tokenHash = hash('sha256', $token);

    $stmt = $conn->prepare(
        'SELECT id FROM password_resets
         WHERE email = ? AND token = ? AND expires_at > NOW()
         LIMIT 1'
    );
    $stmt->bind_param('ss', $email, $tokenHash);
    $stmt->execute();
    $resetRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($resetRow) {
        $validToken = true;
    } else {
        $error = 'This reset link is invalid or has expired. Please request a new one.';
    }

    if ($validToken && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $newPassword = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (strlen($newPassword) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif (!preg_match('/[A-Z]/', $newPassword)) {
            $error = 'Password must contain at least one uppercase letter.';
        } elseif (!preg_match('/[0-9]/', $newPassword)) {
            $error = 'Password must contain at least one number.';
        } elseif (!preg_match('/[\\W_]/', $newPassword)) {
            $error = 'Password must contain at least one special character.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } else {
            $hashed = password_hash($newPassword, PASSWORD_DEFAULT);

            $conn->begin_transaction();
            try {
                $update = $conn->prepare('UPDATE students SET password = ? WHERE email = ?');
                $update->bind_param('ss', $hashed, $email);
                $update->execute();
                $update->close();

                $delete = $conn->prepare('DELETE FROM password_resets WHERE email = ?');
                $delete->bind_param('s', $email);
                $delete->execute();
                $delete->close();

                $conn->commit();
                $success = true;
                $validToken = false;
            } catch (Throwable $e) {
                $conn->rollback();
                error_log('CourseMatch password reset failed: ' . $e->getMessage());
                $error = 'A server error occurred. Please try again.';
            }
        }
    }
} else {
    $error = 'Invalid reset link. Please request a new one.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Choose a new CourseMatch password.">
  <title>Choose a new password · CourseMatch</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="modern.css">
</head>
<body class="auth-page cm-page">

<main class="auth-shell">
  <section class="auth-brand-panel">
    <div class="brand-lockup">
      <span class="brand-mark" aria-hidden="true">CM</span>
      <span>CourseMatch</span>
    </div>

    <div class="auth-copy">
      <div class="auth-eyebrow">Account security</div>
      <h1>Choose a strong new password.</h1>
      <p>
        Reset links are single-use and time-limited. After a successful reset, the token is removed so it cannot be used again.
      </p>

      <div class="auth-points">
        <div class="auth-point">
          <strong>8+ characters</strong>
          <span>Minimum length</span>
        </div>
        <div class="auth-point">
          <strong>Mixed password</strong>
          <span>Uppercase, number, symbol</span>
        </div>
        <div class="auth-point">
          <strong>Single use</strong>
          <span>Token deleted after reset</span>
        </div>
      </div>
    </div>

    <div class="auth-footnote">Use a password you do not reuse on other websites.</div>
  </section>

  <section class="auth-form-panel">
    <div class="auth-form-wrap">
      <?php if ($success): ?>
        <div class="auth-status">
          <div class="status-icon" aria-hidden="true">✓</div>
          <h2>Password updated</h2>
          <p>Your new password is ready. You can now sign in to CourseMatch.</p>
          <a class="secondary-action" href="index.html">Continue to sign in</a>
        </div>

      <?php elseif ($validToken): ?>
        <h2>Create a new password</h2>
        <p class="lead">Enter and confirm a new password for your CourseMatch account.</p>

        <?php if ($error !== ''): ?>
          <div class="auth-error-card" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="POST" novalidate>
          <div class="form-field">
            <label for="password">New password</label>
            <div class="field-control">
              <span class="field-icon" aria-hidden="true">●</span>
              <input id="password" name="password" type="password" autocomplete="new-password" required>
            </div>
          </div>

          <div class="form-field">
            <label for="confirm_password">Confirm new password</label>
            <div class="field-control">
              <span class="field-icon" aria-hidden="true">●</span>
              <input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" required>
            </div>
          </div>

          <div class="form-hint">
            Use 8+ characters with at least one uppercase letter, one number, and one special character.
          </div>

          <button class="primary-submit" type="submit">Update password</button>
        </form>

      <?php else: ?>
        <div class="auth-status">
          <div class="status-icon" aria-hidden="true">!</div>
          <h2>Reset link unavailable</h2>
          <p><?= htmlspecialchars($error ?: 'This password reset link is invalid or expired.', ENT_QUOTES, 'UTF-8') ?></p>
          <a class="secondary-action" href="forgot-password.html">Request a new reset link</a>
          <div class="auth-switch"><a class="text-link" href="index.html">Back to sign in</a></div>
        </div>
      <?php endif; ?>
    </div>
  </section>
</main>

</body>
</html>
