<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/reservations.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$weekInput = $_GET['week'] ?? null;

if (is_string($weekInput) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $weekInput)) {
    $baseDate = new DateTimeImmutable($weekInput);
} else {
    $baseDate = new DateTimeImmutable('today');
}

$rangeStart = $baseDate->modify('monday this week');
$rangeEnd = $rangeStart->modify('+7 day');

$reservations = fetchDriverBoardReservations(
    $pdo,
    $rangeStart->format('Y-m-d 00:00:00'),
    $rangeEnd->format('Y-m-d 00:00:00')
);

$payload = array_map(static function (array $reservation): array {
    return [
        'id' => (int) ($reservation['id'] ?? 0),
        'reservation_code' => (string) ($reservation['reservation_code'] ?? ''),
        'reservation_datetime' => (string) ($reservation['reservation_datetime'] ?? ''),
        'destination' => (string) ($reservation['destination'] ?? ''),
        'other_destination' => (string) ($reservation['other_destination'] ?? ''),
        'purpose' => (string) ($reservation['purpose'] ?? ''),
        'department' => (string) ($reservation['department'] ?? ''),
        'loads' => (string) ($reservation['loads'] ?? ''),
        'oc_email' => (string) ($reservation['oc_email'] ?? ''),
        'vehicle' => (string) ($reservation['vehicle'] ?? ''),
        'driver' => (string) ($reservation['driver'] ?? ''),
        'status' => (string) ($reservation['status'] ?? ''),
        'passengers' => (int) ($reservation['passengers'] ?? 0),
        'travel_time' => (string) ($reservation['travel_time'] ?? ''),
        'waiting_time' => (string) ($reservation['waiting_time'] ?? ''),
    ];
}, $reservations);

echo json_encode([
    'reservations' => $payload,
    'range_start' => $rangeStart->format('Y-m-d'),
    'range_end' => $rangeEnd->format('Y-m-d'),
    'generated_at' => date('c'),
], JSON_UNESCAPED_UNICODE);
