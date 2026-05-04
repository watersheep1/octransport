<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Enforce login for protected pages.
if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/admins.php';

ensureAdminsSchema($pdo);

$currentAdmin = fetchAdminById($pdo, (int) $_SESSION['admin_id']);
if (!$currentAdmin) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

syncAdminSession($currentAdmin);
