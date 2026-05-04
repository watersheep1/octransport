<?php
session_start();

$config = require __DIR__ . '/../includes/config.php';

if (empty($_GET['code']) || empty($_GET['state'])) {
    header('Location: login.php?error=google_failed');
    exit;
}

if (!hash_equals($_SESSION['google_oauth_state'] ?? '', $_GET['state'])) {
    header('Location: login.php?error=google_failed');
    exit;
}

$tokenEndpoint = 'https://oauth2.googleapis.com/token';
$postData = [
    'code' => $_GET['code'],
    'client_id' => $config['google_client_id'],
    'client_secret' => $config['google_client_secret'],
    'redirect_uri' => $config['google_redirect_uri'],
    'grant_type' => 'authorization_code'
];

$ch = curl_init($tokenEndpoint);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if (!$response || $httpCode !== 200) {
    header('Location: login.php?error=google_failed');
    exit;
}

$tokenData = json_decode($response, true);
$accessToken = $tokenData['access_token'] ?? '';

if (!$accessToken) {
    header('Location: login.php?error=google_failed');
    exit;
}

$userInfoRequest = curl_init('https://www.googleapis.com/oauth2/v3/userinfo');
curl_setopt($userInfoRequest, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
curl_setopt($userInfoRequest, CURLOPT_RETURNTRANSFER, true);
$userInfoResponse = curl_exec($userInfoRequest);
$userInfoCode = curl_getinfo($userInfoRequest, CURLINFO_HTTP_CODE);
curl_close($userInfoRequest);

if (!$userInfoResponse || $userInfoCode !== 200) {
    header('Location: login.php?error=google_failed');
    exit;
}

$userInfo = json_decode($userInfoResponse, true);
$email = $userInfo['email'] ?? '';
$name = $userInfo['name'] ?? 'Google User';
$googleId = $userInfo['sub'] ?? null;

if (!$email) {
    header('Location: login.php?error=google_failed');
    exit;
}

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

$stmt = $pdo->prepare('SELECT * FROM user_accounts WHERE email = :email LIMIT 1');
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

if (!$user) {
    $insert = $pdo->prepare(
        'INSERT INTO user_accounts (name, email, provider, provider_id, created_at, last_login_at)
         VALUES (:name, :email, :provider, :provider_id, :created_at, :last_login_at)'
    );
    $insert->execute([
        'name' => $name,
        'email' => $email,
        'provider' => 'google',
        'provider_id' => $googleId,
        'created_at' => date('Y-m-d H:i:s'),
        'last_login_at' => date('Y-m-d H:i:s'),
    ]);

    $stmt = $pdo->prepare('SELECT * FROM user_accounts WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();
} else {
    $update = $pdo->prepare(
        'UPDATE user_accounts
         SET name = :name, provider = :provider, provider_id = :provider_id, updated_at = :updated_at, last_login_at = :last_login_at
         WHERE id = :id'
    );
    $update->execute([
        'name' => $name,
        'provider' => 'google',
        'provider_id' => $googleId,
        'updated_at' => date('Y-m-d H:i:s'),
        'last_login_at' => date('Y-m-d H:i:s'),
        'id' => $user['id'],
    ]);

    $stmt = $pdo->prepare('SELECT * FROM user_accounts WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $user['id']]);
    $user = $stmt->fetch();
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_name'] = $user['name'];

header('Location: index.php');
exit;
