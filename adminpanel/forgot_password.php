<?php
session_start();
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/admins.php';

ensureAdminsSchema($pdo);

if (!isLocalRequest()) {
    http_response_code(403);
    exit('Password reset is only available on localhost.');
}

$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($username === '' || $password === '' || $confirmPassword === '') {
        $error = 'Please complete all fields.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        $admin = fetchAdminByUsername($pdo, $username);
        if (!$admin) {
            $error = 'Admin account not found.';
        } else {
            $stmt = $pdo->prepare('UPDATE admins SET password = :password, updated_at = NOW() WHERE id = :id');
            $stmt->execute([
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'id' => (int) $admin['id'],
            ]);

            $_SESSION['admin_flash'] = 'Password updated. You can log in now.';
            header('Location: login.php');
            exit;
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Forgot Password</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { min-height: 100vh; background: radial-gradient(circle at top left, #e6f4ea, #cfe7d7 45%, #0b6f4b 100%); font-family: "Segoe UI", Tahoma, sans-serif; }
    .shell { min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px; }
    .card-box { width:min(520px, 95vw); background:#fff; border-radius:20px; padding:28px; box-shadow:0 30px 60px rgba(11,111,75,.22); }
    .btn-primary { background:#0b6f4b; border-color:#0b6f4b; }
    .btn-primary:hover { background:#075e3f; border-color:#075e3f; }
    .password-wrap { position: relative; }
    .password-wrap .form-control { padding-right: 58px; }
    .toggle-password {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      border: none;
      background: #edf5ef;
      color: #1f3d2d;
      width: 38px;
      height: 38px;
      border-radius: 12px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: background .18s ease, transform .18s ease;
    }
    .toggle-password:hover { background:#e2eee6; transform: translateY(-50%) scale(1.03); }
    .toggle-password svg {
      width: 19px;
      height: 19px;
      stroke: currentColor;
      fill: none;
      stroke-width: 2;
      stroke-linecap: round;
      stroke-linejoin: round;
    }
  </style>
</head>
<body>
  <div class="shell">
    <div class="card-box">
      <h2 class="mb-2">Reset Admin Password</h2>
      <p class="text-muted mb-4">Local-only reset for admins saved in phpMyAdmin.</p>
      <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
      <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
      <form method="post" class="row g-3">
        <div class="col-12">
          <label class="form-label">Username</label>
          <input class="form-control" name="username" required />
        </div>
        <div class="col-12">
          <label class="form-label">New Password</label>
          <div class="password-wrap">
            <input class="form-control" id="reset-password" name="password" type="password" required />
            <button type="button" class="toggle-password" data-password-toggle="reset-password" aria-label="Show password">
              <span class="icon-eye" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                  <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
              </span>
              <span class="icon-eye-off" aria-hidden="true" style="display:none;">
                <svg viewBox="0 0 24 24">
                  <path d="M2 12s4-7 10-7c2.7 0 5 1 6.8 2.4"></path>
                  <path d="M22 12s-4 7-10 7c-2.7 0-5-1-6.8-2.4"></path>
                  <path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"></path>
                  <path d="M3 3l18 18"></path>
                </svg>
              </span>
            </button>
          </div>
        </div>
        <div class="col-12">
          <label class="form-label">Confirm Password</label>
          <div class="password-wrap">
            <input class="form-control" id="reset-confirm-password" name="confirm_password" type="password" required />
            <button type="button" class="toggle-password" data-password-toggle="reset-confirm-password" aria-label="Show password">
              <span class="icon-eye" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                  <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
              </span>
              <span class="icon-eye-off" aria-hidden="true" style="display:none;">
                <svg viewBox="0 0 24 24">
                  <path d="M2 12s4-7 10-7c2.7 0 5 1 6.8 2.4"></path>
                  <path d="M22 12s-4 7-10 7c-2.7 0-5-1-6.8-2.4"></path>
                  <path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"></path>
                  <path d="M3 3l18 18"></path>
                </svg>
              </span>
            </button>
          </div>
        </div>
        <div class="col-12 d-flex gap-2 justify-content-end">
          <a class="btn btn-outline-secondary" href="login.php">Back</a>
          <button class="btn btn-primary" type="submit">Update Password</button>
        </div>
      </form>
    </div>
  </div>
  <script>
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
      const input = document.getElementById(button.dataset.passwordToggle);
      const iconEye = button.querySelector('.icon-eye');
      const iconEyeOff = button.querySelector('.icon-eye-off');
      if (!input || !iconEye || !iconEyeOff) return;

      button.addEventListener('click', () => {
        const isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        iconEye.style.display = isHidden ? 'none' : 'inline-flex';
        iconEyeOff.style.display = isHidden ? 'inline-flex' : 'none';
        button.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
      });
    });
  </script>
</body>
</html>
