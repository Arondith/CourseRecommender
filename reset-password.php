<?php
/**
 * reset-password.php
 * Validates the token from the email link and lets the user set a new password.
 */

$db_host = 'localhost';
$db_name = 'coursematch_db';
$db_user = 'root';
$db_pass = '';

$token      = trim($_GET['token'] ?? '');
$email      = trim($_GET['email'] ?? '');
$error      = '';
$success    = false;
$validToken = false;

if ($token && $email) {
    try {
        $pdo = new PDO(
            "mysql:host=$db_host;dbname=$db_name;charset=utf8",
            $db_user, $db_pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $token_hash = hash('sha256', $token);
        $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE email = ? AND token = ? AND expires_at > NOW() LIMIT 1");
        $stmt->execute([$email, $token_hash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $validToken = true;
        } else {
            $error = 'This reset link is invalid or has expired. Please request a new one.';
        }

        // Handle form submission
        if ($validToken && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $new_password     = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            if (strlen($new_password) < 8) {
                $error = 'Password must be at least 8 characters.';
            } elseif ($new_password !== $confirm_password) {
                $error = 'Passwords do not match.';
            } else {
                $hashed = password_hash($new_password, PASSWORD_BCRYPT);
                $pdo->prepare("UPDATE students SET password = ? WHERE email = ?")->execute([$hashed, $email]);
                $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
                $success = true;
                $validToken = false;
            }
        }

    } catch (\Exception $e) {
        $error = 'A server error occurred. Please try again.';
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
  <title>CourseMatch - Reset Password</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 20px;
      font-family: 'Segoe UI', Arial, sans-serif;
    }

    .wrapper {
      width: 100%;
      max-width: 920px;
      min-height: 540px;
      display: flex;
      border-radius: 24px;
      overflow: hidden;
      box-shadow: 0 24px 64px rgba(100,60,160,0.35);
      animation: slideUp 0.45s ease;
    }

    @keyframes slideUp {
      from { opacity: 0; transform: translateY(28px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    /* Left Panel */
    .left {
      flex: 1;
      background: linear-gradient(160deg, #5a6fd6 0%, #6b46c1 100%);
      color: white;
      padding: 56px 48px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      position: relative;
      overflow: hidden;
    }

    .left::before {
      content: '';
      position: absolute;
      width: 380px; height: 380px;
      border-radius: 50%;
      background: rgba(255,255,255,0.06);
      top: -120px; right: -100px;
    }

    .left::after {
      content: '';
      position: absolute;
      width: 260px; height: 260px;
      border-radius: 50%;
      background: rgba(255,255,255,0.04);
      bottom: -80px; left: -60px;
    }

    .logo { margin-bottom: 32px; position: relative; }
    .logo img { width: 400px; height: auto; object-fit: contain; mix-blend-mode: multiply; filter: brightness(1.05) contrast(1.05); }

    .left h1 { font-size: 32px; font-weight: 700; margin-bottom: 16px; position: relative; line-height: 1.2; }
    .left p  { font-size: 15px; opacity: 0.82; line-height: 1.75; position: relative; max-width: 320px; }

    .left-badge {
      display: inline-flex; align-items: center; gap: 6px;
      background: rgba(255,255,255,0.15);
      border: 1px solid rgba(255,255,255,0.2);
      color: white; font-size: 12px; font-weight: 600;
      padding: 6px 14px; border-radius: 20px;
      margin-bottom: 24px; position: relative; width: fit-content;
    }

    /* Right Panel */
    .right {
      flex: 1; background: white;
      padding: 56px 48px;
      display: flex; flex-direction: column; justify-content: center;
    }

    .form-box { width: 100%; max-width: 360px; }

    .icon-wrap {
      width: 64px; height: 64px;
      background: linear-gradient(135deg, #eef0ff, #e8e0ff);
      border-radius: 18px;
      display: flex; align-items: center; justify-content: center;
      font-size: 28px; margin-bottom: 24px;
      box-shadow: 0 4px 16px rgba(102,126,234,0.15);
    }

    .form-title { font-size: 26px; font-weight: 700; color: #1a1a2e; margin-bottom: 6px; }
    .form-sub   { font-size: 14px; color: #8888aa; margin-bottom: 28px; line-height: 1.6; }

    .input-group { position: relative; margin-bottom: 16px; }

    .input-group input {
      width: 100%; padding: 13px 14px 13px 44px;
      border-radius: 10px; border: 1.5px solid #e8eaf6;
      outline: none; font-size: 14px; font-family: inherit;
      color: #1a1a2e; background: #f7f8ff; transition: all 0.2s;
    }

    .input-group input:focus {
      border-color: #667eea; background: white;
      box-shadow: 0 0 0 3px rgba(102,126,234,0.12);
    }

    .input-group span {
      position: absolute; left: 14px; top: 14px;
      font-size: 16px; pointer-events: none;
    }

    /* Password strength bar */
    .strength-bar { height: 4px; border-radius: 4px; background: #eee; margin: -8px 0 16px; overflow: hidden; }
    .strength-fill { height: 100%; width: 0; border-radius: 4px; transition: width 0.3s, background 0.3s; }

    .hint { font-size: 12px; color: #aaaacc; margin-top: -10px; margin-bottom: 16px; }

    .submit-btn {
      width: 100%; padding: 13px;
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white; border: none; border-radius: 10px;
      font-size: 15px; font-weight: 700; font-family: inherit;
      cursor: pointer; transition: all 0.25s; margin-top: 4px;
      box-shadow: 0 4px 16px rgba(102,126,234,0.35); letter-spacing: 0.2px;
    }

    .submit-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(102,126,234,0.45); }
    .submit-btn:active { transform: translateY(0); }

    .error-box {
      background: #fff0f0; border: 1px solid #fdd;
      border-radius: 10px; padding: 12px 16px;
      font-size: 13px; color: #e05555;
      margin-bottom: 20px; line-height: 1.5;
    }

    .success-box { text-align: center; }
    .success-icon {
      width: 72px; height: 72px;
      background: linear-gradient(135deg, #d4f5e8, #c8f0df);
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 32px; margin: 0 auto 20px;
      box-shadow: 0 4px 16px rgba(72,199,142,0.2);
    }

    .success-title { font-size: 22px; font-weight: 700; color: #1a1a2e; margin-bottom: 10px; }
    .success-msg   { font-size: 14px; color: #8888aa; line-height: 1.65; margin-bottom: 28px; }

    .back-link {
      display: inline-flex; align-items: center; gap: 6px;
      margin-top: 18px; font-size: 14px; font-weight: 600;
      color: #8888aa; text-decoration: none; transition: color 0.2s;
    }
    .back-link:hover { color: #667eea; }

    @media (max-width: 640px) {
      .left { display: none; }
      .wrapper { border-radius: 20px; }
      .right { padding: 40px 28px; }
    }
  </style>
</head>
<body>
<div class="wrapper">

  <!-- Left -->
  <div class="left">
    <div class="logo">
      <img src="images/logo.png" alt="CourseMatch Logo">
    </div>
    <div class="left-badge">🔐 Secure password reset</div>
    <h1>Create a New Password</h1>
    <p>Choose a strong password to keep your CourseMatch account safe and secure.</p>
  </div>

  <!-- Right -->
  <div class="right">
    <div class="form-box">

      <?php if ($success): ?>
        <!-- ✅ Success -->
        <div class="success-box">
          <div class="success-icon">✅</div>
          <div class="success-title">Password updated!</div>
          <p class="success-msg">Your password has been reset successfully. You can now sign in with your new password.</p>
          <a href="index.html" class="submit-btn" style="display:block;text-align:center;text-decoration:none;">Go to Sign In</a>
        </div>

      <?php elseif ($error && !$validToken): ?>
        <!-- ❌ Invalid / expired link -->
        <div class="icon-wrap">⚠️</div>
        <div class="form-title">Link expired</div>
        <div class="form-sub">This reset link is no longer valid. Request a new one below.</div>
        <div class="error-box"><?= htmlspecialchars($error) ?></div>
        <a href="forgot-password.html" class="submit-btn" style="display:block;text-align:center;text-decoration:none;">Request New Link</a>

      <?php else: ?>
        <!-- 🔑 Reset Form -->
        <div class="icon-wrap">🔒</div>
        <div class="form-title">New password</div>
        <div class="form-sub">Must be at least 8 characters. Choose something strong!</div>

        <?php if ($error): ?>
          <div class="error-box">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="reset-password.php?token=<?= urlencode($token) ?>&email=<?= urlencode($email) ?>">
          <div class="input-group">
            <span>🔒</span>
            <input type="password" name="password" id="newPass" placeholder="New password" required>
          </div>
          <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
          <p class="hint" id="strengthHint">Enter a password</p>

          <div class="input-group">
            <span>🔒</span>
            <input type="password" name="confirm_password" id="confirmPass" placeholder="Confirm new password" required>
          </div>

          <button type="submit" class="submit-btn">Update Password</button>
        </form>

        <a href="index.html" class="back-link">← Back to Sign In</a>
      <?php endif; ?>

    </div>
  </div>
</div>

<script>
  // Password strength indicator
  const passInput     = document.getElementById('newPass');
  const strengthFill  = document.getElementById('strengthFill');
  const strengthHint  = document.getElementById('strengthHint');

  if (passInput) {
    passInput.addEventListener('input', function () {
      const val = this.value;
      let score = 0;
      if (val.length >= 8)            score++;
      if (/[A-Z]/.test(val))          score++;
      if (/[0-9]/.test(val))          score++;
      if (/[^A-Za-z0-9]/.test(val))   score++;

      const levels = [
        { width: '0%',   color: '#eee',     label: 'Enter a password' },
        { width: '25%',  color: '#e05555',  label: 'Weak' },
        { width: '50%',  color: '#f0a500',  label: 'Fair' },
        { width: '75%',  color: '#48c78e',  label: 'Good' },
        { width: '100%', color: '#00c48c',  label: 'Strong 💪' },
      ];

      const lvl = val.length === 0 ? levels[0] : levels[score];
      strengthFill.style.width      = lvl.width;
      strengthFill.style.background = lvl.color;
      strengthHint.textContent      = lvl.label;
      strengthHint.style.color      = lvl.color === '#eee' ? '#aaaacc' : lvl.color;
    });
  }
</script>
</body>
</html>