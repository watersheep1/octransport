<?php
session_start();

$isLoggedIn = !empty($_SESSION['user_id']);
if ($isLoggedIn) {
    header('Location: index.php');
    exit;
}

$config = require __DIR__ . '/../includes/config.php';

$dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $config['db_host'], $config['db_name']);
$pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS user_accounts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        email VARCHAR(190) NOT NULL UNIQUE,
        password_hash VARCHAR(255) DEFAULT NULL,
        provider VARCHAR(30) NOT NULL DEFAULT 'google',
        provider_id VARCHAR(190) DEFAULT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME DEFAULT NULL,
        last_login_at DATETIME DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
);

$columns = $pdo->query('SHOW COLUMNS FROM user_accounts')->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('updated_at', $columns, true)) {
    $pdo->exec('ALTER TABLE user_accounts ADD COLUMN updated_at DATETIME DEFAULT NULL');
}
if (!in_array('last_login_at', $columns, true)) {
    $pdo->exec('ALTER TABLE user_accounts ADD COLUMN last_login_at DATETIME DEFAULT NULL');
}

$error = '';
$googleReady = !empty($config['google_client_id']) && !empty($config['google_client_secret']);
$googleError = $_GET['error'] ?? '';
if ($googleError === 'google_config') {
    $error = 'Google login is not configured yet. Add the Client ID and Secret first.';
}
if ($googleError === 'google_failed') {
    $error = 'Google login failed. Please try again.';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>User Login</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Space+Grotesk:wght@500;700&display=swap');

    :root {
      --forest: #0b6f4b;
      --forest-dark: #075e3f;
      --sage: #74a656;
      --cream: #f7f2ec;
      --mint: #eff8f3;
      --ink: #123121;
      --muted: #6c7a72;
    }

    * {
      box-sizing: border-box;
    }

    body {
      min-height: 100vh;
      background:
        linear-gradient(rgba(11, 111, 75, 0.66), rgba(7, 94, 63, 0.74)),
        radial-gradient(circle at top left, rgba(230, 244, 234, 0.28), transparent 34%),
        url('assets/olivarez-campus.jpg') center center / cover no-repeat fixed;
      font-family: 'DM Sans', 'Segoe UI', sans-serif;
      color: var(--ink);
      margin: 0;
    }

    .site-brand {
      position: fixed;
      top: 18px;
      left: 24px;
      z-index: 5;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 0;
      text-decoration: none;
      color: #f3fff7;
    }

    .site-brand img {
      width: 62px;
      height: 62px;
      object-fit: contain;
      background: transparent;
      padding: 0;
      box-shadow: none;
    }

    .login-shell {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 32px 18px;
    }

    .login-card {
      width: min(980px, 95vw);
      background: rgba(255, 255, 255, 0.96);
      border: 1px solid rgba(255, 255, 255, 0.35);
      border-radius: 28px;
      overflow: hidden;
      box-shadow: 0 34px 80px rgba(7, 56, 35, 0.24);
      outline: 2px solid rgba(255, 255, 255, 0.9);
      outline-offset: 0;
      display: grid;
      grid-template-columns: 1.05fr 1fr;
      backdrop-filter: blur(10px);
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
      background: rgba(255, 255, 255, 0.07);
      pointer-events: none;
      z-index: 0;
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

    .visual-top {
      position: relative;
      z-index: 1;
      display: flex;
      flex-direction: column;
      gap: 18px;
    }

    .brand-cluster {
      display: flex;
      align-items: flex-start;
      gap: 18px;
      flex-wrap: nowrap;
    }

    .brand-mark {
      width: 108px;
      height: 108px;
      border-radius: 18px;
      background: #ffffff;
      display: grid;
      place-items: center;
      padding: 14px;
      flex-shrink: 0;
    }

    .brand-mark img {
      width: 100%;
      height: 100%;
      object-fit: contain;
      display: block;
    }

    .brand-copy {
      display: flex;
      flex-direction: column;
      justify-content: flex-start;
      min-width: 0;
      padding-top: 6px;
    }

    .brand-eyebrow {
      font-family: 'DM Sans', 'Segoe UI', sans-serif;
      font-size: 15px;
      font-weight: 700;
      letter-spacing: 0.16em;
      text-transform: uppercase;
      color: rgba(234, 245, 238, 0.86);
      margin: 0 0 6px;
      line-height: 1;
    }

    .brand-title {
      font-family: 'DM Sans', 'Segoe UI', sans-serif;
      font-size: 48px;
      line-height: 0.9;
      font-weight: 800;
      color: #ffffff;
      margin: 0 0 8px;
      letter-spacing: -0.05em;
    }

    .brand-subtitle {
      font-family: 'DM Sans', 'Segoe UI', sans-serif;
      font-size: 20px;
      font-weight: 700;
      color: #edf7f1;
      margin: 0;
      line-height: 1;
      letter-spacing: -0.03em;
    }

    .access-pill {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      width: fit-content;
      padding: 12px 18px;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.12);
      border: 1px solid rgba(255, 255, 255, 0.14);
      color: #f0fff7;
      font-size: 13px;
      font-weight: 800;
      letter-spacing: 0.05em;
      text-transform: uppercase;
      margin-top: 2px;
    }

    .access-pill::before {
      content: "";
      width: 10px;
      height: 10px;
      border-radius: 999px;
      background: #b7e2ca;
      box-shadow: 0 0 0 5px rgba(183, 226, 202, 0.18);
    }

    .login-form {
      padding: 52px 42px 42px;
      background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(247,252,249,0.96));
    }

    .login-form h4 {
      font-weight: 800;
      font-size: 34px;
      margin: 0 0 8px;
      font-family: 'Space Grotesk', 'DM Sans', sans-serif;
    }

    .login-form .subtitle {
      color: var(--muted);
      font-size: 14px;
      line-height: 1.6;
      margin-bottom: 26px;
      max-width: 320px;
    }

    .btn-google {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      border: 1px solid #d6e1db;
      border-radius: 12px;
      padding: 10px 16px;
      font-weight: 600;
      color: #1f3d2d;
      text-decoration: none;
      background: #f7fbf9;
      width: 100%;
      padding: 18px 22px;
      font-size: 1rem;
      transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
    }

    .btn-google:hover {
      transform: translateY(-1px);
      border-color: rgba(11, 111, 75, 0.35);
      box-shadow: 0 12px 26px rgba(11, 111, 75, 0.12);
    }

    .btn-google svg {
      width: 24px;
      height: 24px;
    }

    .google-panel {
      margin-top: 0;
      border: 1px solid #e0ebe4;
      border-radius: 24px;
      padding: 28px;
      background: linear-gradient(180deg, #fbfefd 0%, #f4faf7 100%);
    }

    .google-panel-title {
      margin: 0 0 18px;
      font-size: 14px;
      font-weight: 700;
      color: #173525;
    }

    .alert {
      background: #fdecea;
      border: 1px solid #f5c2c0;
      color: #b42318;
      padding: 10px 12px;
      border-radius: 10px;
      margin-bottom: 16px;
      font-size: 13px;
    }

    .hint {
      font-size: 13px;
      color: var(--muted);
      margin: 18px 0 0;
      line-height: 1.5;
    }

    @media (max-width: 900px) {
      .site-brand {
        position: static;
        margin: 18px auto 0;
        width: fit-content;
      }

      body {
        background-attachment: scroll;
      }

      .login-card {
        grid-template-columns: 1fr;
      }

      .login-visual {
        min-height: auto;
        padding: 28px;
      }

      .brand-cluster {
        align-items: center;
      }

      .login-form {
        padding: 34px 22px 26px;
      }

      .brand-mark {
        width: 78px;
        height: 78px;
        flex-basis: 78px;
        padding: 10px;
      }

      .brand-title {
        font-size: 34px;
      }

      .brand-eyebrow {
        font-size: 12px;
      }

      .brand-subtitle {
        font-size: 16px;
      }

      .brand-copy {
        padding-top: 2px;
      }

      .access-pill {
        font-size: 12px;
        padding: 10px 16px;
      }
    }
  </style>
</head>
<body>
  <div class="site-brand" aria-label="Olivarez College logo">
    <img src="assets/oc-logo.png" alt="Olivarez College logo">
  </div>
  <div class="login-shell">
    <div class="login-card">
      <div class="login-visual">
        <div class="visual-top">
          <div class="brand-cluster">
            <div class="brand-mark">
              <img src="../logo/624125921_1375793547898072_4114286405174986642_n.png" alt="OCTRANSPO logo" data-pin-nopin="true" nopin="nopin">
            </div>
            <div class="brand-copy">
              <p class="brand-eyebrow">Transport Operations</p>
              <h3 class="brand-title">OCTRANSPO</h3>
              <p class="brand-subtitle">Services</p>
            </div>
          </div>
          <div class="access-pill">User Access</div>
        </div>
      </div>
      <div class="login-form">
        <h4>User Login</h4>
        <div class="subtitle">Enter your Google account to continue to the reservation portal and request controls.</div>

        <?php if ($error): ?>
          <div class="alert"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <div class="google-panel">
          <p class="google-panel-title">Use your Google account to continue</p>
          <a class="btn-google" href="google-login.php" <?php echo $googleReady ? '' : 'onclick="return false;"'; ?>>
            <svg viewBox="0 0 48 48" aria-hidden="true">
              <path fill="#EA4335" d="M24 9.5c3.5 0 6.7 1.2 9.2 3.5l6.8-6.8C36 2.2 30.3 0 24 0 14.6 0 6.3 5.4 2.3 13.2l7.9 6.1C12 13.5 17.5 9.5 24 9.5z"/>
              <path fill="#4285F4" d="M46.1 24.6c0-1.6-.1-2.7-.4-4H24v7.6h12.7c-.3 2-1.7 5-4.8 7l7.4 5.7c4.3-4 6.8-9.8 6.8-16.3z"/>
              <path fill="#FBBC05" d="M10.2 28.9c-.6-1.8-.9-3.7-.9-5.7s.3-3.9.9-5.7l-7.9-6.1C.8 14.9 0 19.3 0 23.2c0 3.9.8 8.3 2.3 11.8l7.9-6.1z"/>
              <path fill="#34A853" d="M24 46.5c6.3 0 11.6-2.1 15.5-5.8l-7.4-5.7c-2.1 1.4-4.8 2.4-8.1 2.4-6.5 0-12-4.1-14-9.8l-7.9 6.1C6.3 42.6 14.6 46.5 24 46.5z"/>
            </svg>
            Continue with Google
          </a>
          <p class="hint">
            <?php if ($googleReady): ?>
              Sign in and account creation both happen through your Google account.
            <?php else: ?>
              Google login requires your OAuth Client ID, Client Secret, and redirect URI in `config.php`.
            <?php endif; ?>
          </p>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
