<?php

function ensureAdminsSchema(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    );

    $columns = $pdo->query('SHOW COLUMNS FROM admins')->fetchAll(PDO::FETCH_COLUMN);
    $updates = [
        'full_name' => "ALTER TABLE admins ADD COLUMN full_name VARCHAR(150) DEFAULT NULL AFTER username",
        'email' => "ALTER TABLE admins ADD COLUMN email VARCHAR(190) DEFAULT NULL AFTER full_name",
        'phone' => "ALTER TABLE admins ADD COLUMN phone VARCHAR(60) DEFAULT NULL AFTER email",
        'role' => "ALTER TABLE admins ADD COLUMN role VARCHAR(80) NOT NULL DEFAULT 'Administrator' AFTER phone",
        'created_at' => "ALTER TABLE admins ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER role",
        'updated_at' => "ALTER TABLE admins ADD COLUMN updated_at DATETIME DEFAULT NULL AFTER created_at",
    ];

    foreach ($updates as $column => $sql) {
        if (!in_array($column, $columns, true)) {
            $pdo->exec($sql);
        }
    }
}

function isLocalRequest(): bool
{
    $addr = $_SERVER['REMOTE_ADDR'] ?? '';
    return in_array($addr, ['127.0.0.1', '::1', 'localhost', ''], true);
}

function fetchAdminByUsername(PDO $pdo, string $username): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM admins WHERE username = :username LIMIT 1');
    $stmt->execute(['username' => $username]);
    $admin = $stmt->fetch();
    return $admin ?: null;
}

function fetchAdminById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM admins WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $admin = $stmt->fetch();
    return $admin ?: null;
}

function syncAdminSession(array $admin): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $_SESSION['admin_id'] = (int) $admin['id'];
    $_SESSION['admin_username'] = (string) $admin['username'];
    $_SESSION['admin_name'] = (string) ($admin['full_name'] ?: strtoupper((string) $admin['username']));
    $_SESSION['admin_email'] = (string) ($admin['email'] ?? '');
    $_SESSION['admin_phone'] = (string) ($admin['phone'] ?? '');
    $_SESSION['admin_role'] = (string) ($admin['role'] ?? 'Administrator');
}

function adminDisplayName(array $admin): string
{
    $fullName = trim((string) ($admin['full_name'] ?? ''));
    if ($fullName !== '') {
        return $fullName;
    }

    return strtoupper((string) ($admin['username'] ?? 'ADMIN'));
}
