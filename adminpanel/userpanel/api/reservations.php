<?php
declare(strict_types=1);

require __DIR__ . '/../auth.php';
require __DIR__ . '/../../includes/reservations.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$config = require __DIR__ . '/../../includes/config.php';
$dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $config['db_host'], $config['db_name']);

try {
    $db = new PDO($dsn, $config['db_user'], $config['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    ensureReservationsSchema($db);
    $userId = (int) ($_SESSION['user_id'] ?? 0);
    $userEmail = $_SESSION['user_email'] ?? null;
    $reservations = fetchReservations($db, $userId > 0 ? $userId : null, $userEmail);

    echo json_encode([
        'html' => renderUserReservationRows($reservations),
        'count' => count($reservations),
        'updatesTriggerHtml' => renderUserUpdatesInboxTrigger($reservations),
        'updatesListHtml' => renderUserUpdatesInboxList($reservations),
        'updatesCount' => count(reservationUpdatesData($reservations)),
        'generatedAt' => date('c'),
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'html' => '',
        'error' => 'Database connection failed.',
    ], JSON_UNESCAPED_UNICODE);
}
