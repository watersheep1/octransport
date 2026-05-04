<?php
session_start();
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/admins.php';

ensureAdminsSchema($pdo);

$isLoggedIn = !empty($_SESSION['admin_id']);
if ($isLoggedIn) {
    header('Location: index.php');
    exit;
}

$error = '';
$flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username !== '' && $password !== '') {
        $admin = fetchAdminByUsername($pdo, $username);

        if ($admin && password_verify($password, (string) $admin['password'])) {
            syncAdminSession($admin);
            header('Location: index.php');
            exit;
        }
    }

    $error = 'Invalid username or password.';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    :root {
      --forest: #0b6f4b;
      --forest-dark: #075e3f;
      --forest-soft: #13805a;
      --sage: #74a656;
      --cream: #f7f2ec;
      --mint: #eff8f3;
      --ink: #123121;
      --muted: #6c7a72;
    }

    body {
      position: relative;
      min-height: 100vh;
      background:
        linear-gradient(rgba(11, 111, 75, 0.60), rgba(7, 94, 63, 0.72)),
        radial-gradient(circle at top left, rgba(255,255,255,0.45), transparent 32%),
        radial-gradient(circle at bottom right, rgba(255,255,255,0.14), transparent 28%),
        url('logo/4063Sucat,_Paran%CC%83aque_City_14.jpg') center/cover no-repeat;
      font-family: "Segoe UI", Tahoma, sans-serif;
      color: var(--ink);
      overflow-x: hidden;
    }

    body::before {
      content: "";
      position: fixed;
      inset: 0;
      background: url('logo/OC-LOGO.png') center center / min(34vw, 380px) no-repeat;
      opacity: 0.11;
      pointer-events: none;
      z-index: 0;
    }

    .login-shell {
      position: relative;
      z-index: 1;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 32px 18px;
    }

    .page-corner-logo {
      position: fixed;
      top: 22px;
      left: 22px;
      z-index: 2;
      width: 88px;
      height: 88px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    .page-corner-logo img {
      width: 72px;
      height: 72px;
      object-fit: contain;
      display: block;
    }

    .login-card {
      width: min(980px, 95vw);
      background: rgba(255, 255, 255, 0.96);
      border-radius: 28px;
      overflow: hidden;
      border: 1px solid rgba(255, 255, 255, 0.35);
      box-shadow: 0 34px 80px rgba(7, 56, 35, 0.24);
      backdrop-filter: blur(10px);
      display: grid;
      grid-template-columns: 1.05fr 1fr;
    }

    .login-visual {
      position: relative;
      padding: 36px;
      background:
        radial-gradient(circle at top right, rgba(255,255,255,0.16), transparent 26%),
        radial-gradient(circle at 15% 78%, rgba(255,255,255,0.10), transparent 22%),
        linear-gradient(160deg, rgba(11, 111, 75, 0.94), rgba(7, 94, 63, 0.98));
      color: #e8f5ee;
      min-height: 520px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      isolation: isolate;
    }

    .login-visual::before,
    .login-visual::after {
      content: "";
      position: absolute;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.08);
      z-index: -1;
    }

    .login-visual::before {
      width: 220px;
      height: 220px;
      top: -72px;
      right: -88px;
    }

    .login-visual::after {
      width: 150px;
      height: 150px;
      bottom: 24px;
      right: 32px;
    }

    .brand-mark {
      display: flex;
      align-items: flex-start;
      gap: 14px;
      font-weight: 700;
      letter-spacing: 0.4px;
      margin-bottom: 12px;
      flex-wrap: nowrap;
    }

    .brand-mark img {
      width: 78px;
      height: 78px;
      flex: 0 0 78px;
      object-fit: contain;
      filter: brightness(1.1);
      background: rgba(255, 255, 255, 0.96);
      border-radius: 14px;
      padding: 8px;
    }

    .brand-copy {
      display: flex;
      flex-direction: column;
      justify-content: flex-start;
      min-width: 0;
      padding-top: 2px;
    }

    .brand-heading {
      font-size: 13px;
      text-transform: uppercase;
      letter-spacing: 0.14em;
      opacity: 0.78;
      margin-bottom: 2px;
      line-height: 1.2;
    }

    .brand-name {
      font-size: 29px;
      line-height: 0.98;
      margin-bottom: 4px;
    }

    .brand-sub {
      font-size: 14px;
      opacity: 0.8;
      line-height: 1.2;
    }

    .login-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 9px 14px;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.12);
      border: 1px solid rgba(255, 255, 255, 0.14);
      color: #f0fff7;
      font-size: 12px;
      font-weight: 700;
      letter-spacing: 0.05em;
      text-transform: uppercase;
      margin-top: 6px;
    }

    .login-badge::before {
      content: "";
      width: 8px;
      height: 8px;
      border-radius: 999px;
      background: #b7e2ca;
      box-shadow: 0 0 0 5px rgba(183, 226, 202, 0.18);
    }

    .login-form {
      padding: 52px 42px 42px;
      background:
        linear-gradient(180deg, rgba(255,255,255,0.98), rgba(247,252,249,0.96));
    }

    .login-form h4 {
      font-weight: 800;
      font-size: 34px;
      margin-bottom: 8px;
    }

    .login-form .subtitle {
      color: var(--muted);
      font-size: 14px;
      line-height: 1.6;
      margin-bottom: 26px;
      max-width: 320px;
    }

    .form-label {
      font-size: 14px;
      font-weight: 700;
      margin-bottom: 9px;
      color: #173525;
    }

    .form-control {
      border-radius: 15px;
      min-height: 56px;
      padding: 14px 16px;
      border: 1px solid #d6e1db;
      background: #f7fbf9;
      font-size: 16px;
    }

    .password-wrap {
      position: relative;
    }
    .password-wrap .form-control {
      padding-right: 56px;
    }
    .toggle-password {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      border: none;
      background: #e8f2ec;
      color: #1f3d2d;
      width: 38px;
      height: 38px;
      border-radius: 11px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: background 0.18s ease, transform 0.18s ease;
    }
    .toggle-password:hover {
      background: #dfece5;
      transform: translateY(-50%) scale(1.03);
    }
    .toggle-password svg {
      width: 18px;
      height: 18px;
      stroke: currentColor;
      fill: none;
      stroke-width: 2;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .form-control:focus {
      border-color: var(--forest);
      box-shadow: 0 0 0 0.24rem rgba(11, 111, 75, 0.11);
    }

    .btn-login {
      background: linear-gradient(135deg, var(--forest), var(--forest-soft));
      border: none;
      border-radius: 15px;
      min-height: 56px;
      padding: 12px 18px;
      font-weight: 800;
      font-size: 17px;
      letter-spacing: 0.01em;
      box-shadow: 0 18px 28px rgba(11, 111, 75, 0.18);
    }

    .btn-login:hover {
      background: var(--forest-dark);
    }

    .login-links {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
      margin-top: 18px;
      font-size: 13px;
    }

    .login-links a {
      color: var(--forest);
      text-decoration: none;
      font-weight: 700;
    }

    .login-links a:hover {
      text-decoration: underline;
    }

    @media (max-width: 900px) {
      .page-corner-logo {
        width: 74px;
        height: 74px;
        top: 16px;
        left: 16px;
        border-radius: 18px;
      }
      .page-corner-logo img {
        width: 60px;
        height: 60px;
      }
      .login-card {
        grid-template-columns: 1fr;
      }
      .login-visual {
        min-height: auto;
        padding: 28px;
      }
      .brand-mark {
        align-items: center;
      }
      .login-form {
        padding: 34px 22px 26px;
      }
      .brand-mark img {
        width: 70px;
        height: 70px;
        flex-basis: 70px;
      }
      .brand-name {
        font-size: 26px;
      }
      .brand-heading {
        font-size: 12px;
      }
    }
  </style>
</head>
<body>
  <a class="page-corner-logo" href="login.php" aria-label="Olivarez College">
    <img src="logo/OC-LOGO.png" alt="Olivarez College logo">
  </a>
  <div class="login-shell">
    <div class="login-card">
      <div class="login-visual">
        <div>
          <div class="brand-mark">
            <img src="logo/624125921_1375793547898072_4114286405174986642_n.png" alt="OCTRANSPO logo">
            <div class="brand-copy">
              <div class="brand-heading">Transport Operations</div>
              <div class="brand-name">OCTRANSPO</div>
              <div class="brand-sub">Services</div>
            </div>
          </div>
          <span class="login-badge">Admin Access</span>
        </div>
      </div>
      <div class="login-form">
        <h4>Admin Login</h4>
        <div class="subtitle">Enter your admin credentials to continue to the dashboard and reservation controls.</div>
        <?php if ($flash): ?>
          <div class="alert alert-success"><?php echo htmlspecialchars($flash); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="post">
          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" required />
          </div>
          <div class="mb-4">
            <label class="form-label">Password</label>
            <div class="password-wrap">
              <input type="password" name="password" id="login-password" class="form-control" required />
              <button type="button" class="toggle-password" id="toggle-password" aria-label="Show password">
                <span class="icon-eye" aria-hidden="true">
                  <svg viewBox="0 0 24 24">
                    <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                  </svg>
                </span>
                <span class="icon-eye-off"aria-hidden="true" style="display:none;">
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
          <button type="submit" class="btn btn-login w-100 text-white">Login</button>
        </form>
        <div class="login-links">
          <a href="forgot_password.php">Forgot password?</a>
        </div>
      </div>
    </div>
  </div>
  <script>
    const toggleButton = document.getElementById('toggle-password');
    const passwordInput = document.getElementById('login-password');
    const iconEye = toggleButton.querySelector('.icon-eye');
    const iconEyeOff = toggleButton.querySelector('.icon-eye-off');
    toggleButton.addEventListener('click', () => {
      const isHidden = passwordInput.type === 'password';
      passwordInput.type = isHidden ? 'text' : 'password';
      iconEye.style.display = isHidden ? 'none' : 'inline-flex';
      iconEyeOff.style.display = isHidden ? 'inline-flex' : 'none';
      toggleButton.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
    });
  </script>
</body>
</html>
