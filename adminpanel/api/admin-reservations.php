<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/reservations.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

ensureReservationsSchema($pdo);
$reservations = fetchReservations($pdo);

echo json_encode([
    'html' => renderAdminReservationRows($reservations),
    'count' => count($reservations),
    'generatedAt' => date('c'),
], JSON_UNESCAPED_UNICODE);
