<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';
require __DIR__ . '/../includes/reservations.php';

date_default_timezone_set('Asia/Manila');

$config = require __DIR__ . '/../includes/config.php';

$db = null;
$dbError = null;

try {
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $config['db_host'], $config['db_name']);
    $db = new PDO($dsn, $config['db_user'], $config['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    ensureReservationsSchema($db);
} catch (Throwable $e) {
    $dbError = $e->getMessage();
}

function generateReservationCode(): string
{
    $rand = random_int(100, 999);
    return 'RES-' . $rand;
}

function readReservations(PDO $db): array
{
    $userId = (int) ($_SESSION['user_id'] ?? 0);
    $userEmail = $_SESSION['user_email'] ?? null;
    return fetchReservations($db, $userId > 0 ? $userId : null, $userEmail);
}

function saveReservation(PDO $db, array $data): void
{
    $stmt = $db->prepare(
        'INSERT INTO reservations
        (user_account_id, reservation_code, requested_at, reservation_datetime, passengers, destination, other_destination, travel_time, waiting_time, purpose, department, loads, oc_email, requested_vehicle, requested_driver, status, vehicle, driver)
        VALUES (:user_account_id, :code, :requested_at, :reservation_datetime, :passengers, :destination, :other_destination, :travel_time, :waiting_time, :purpose, :department, :loads, :oc_email, :requested_vehicle, :requested_driver, :status, :vehicle, :driver)'
    );
    $stmt->execute($data);
}

function markSentToDashboard(PDO $db, int $id): void
{
    $stmt = $db->prepare("UPDATE reservations SET status = 'Sent' WHERE id = :id");
    $stmt->execute(['id' => $id]);
}

$flash = $_SESSION['reservation_flash'] ?? null;
unset($_SESSION['reservation_flash']);

$shouldOpenModal = false;
$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'create') {
        $departureDate = trim($_POST['departure_date'] ?? '');
        $departureTime = trim($_POST['departure_time'] ?? '');
        $passengers = (int)($_POST['passengers'] ?? 0);
        $destination = trim($_POST['destination'] ?? '');
        $otherDestination = trim($_POST['other_destination'] ?? '');
        $travelTime = trim($_POST['travel_time'] ?? '');
        $waitingTime = trim($_POST['waiting_time'] ?? '');
        $purpose = trim($_POST['purpose'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $loads = trim($_POST['loads'] ?? '');
        $ocEmail = trim($_POST['oc_email'] ?? ($_SESSION['user_email'] ?? ''));
        $requestedVehicle = trim($_POST['requested_vehicle'] ?? '');
        $requestedDriver = trim($_POST['requested_driver'] ?? '');
        $reservationDatetime = '';

        if ($departureDate && $departureTime) {
            $reservationDatetime = $departureDate . ' ' . $departureTime . ':00';
        }

        if (!$db) {
            $flash = ['type' => 'error', 'message' => 'Database connection failed. Reservation was not saved.'];
            $shouldOpenModal = true;
        } elseif ($reservationDatetime && $passengers > 0 && $destination && $travelTime && $purpose && $department && $ocEmail) {
            $data = [
                'user_account_id' => $userId > 0 ? $userId : null,
                'code' => generateReservationCode(),
                'requested_at' => date('Y-m-d H:i:s'),
                'reservation_datetime' => date('Y-m-d H:i:s', strtotime($reservationDatetime)),
                'passengers' => $passengers,
                'destination' => $destination,
                'other_destination' => $otherDestination ?: null,
                'travel_time' => $travelTime,
                'waiting_time' => $waitingTime ?: null,
                'purpose' => $purpose,
                'department' => $department ?: null,
                'loads' => $loads ?: null,
                'oc_email' => $ocEmail,
                'requested_vehicle' => $requestedVehicle ?: null,
                'requested_driver' => $requestedDriver ?: null,
                'status' => 'Pending',
                'vehicle' => null,
                'driver' => null,
            ];

            saveReservation($db, $data);
            $_SESSION['reservation_flash'] = ['type' => 'success', 'message' => 'Reservation submitted and queued.'];
            header('Location: index.php');
            exit;
        } else {
            $flash = ['type' => 'error', 'message' => 'Please complete all required fields.'];
            $shouldOpenModal = true;
        }
    }

    if ($db && isset($_POST['action']) && $_POST['action'] === 'send' && isset($_POST['reservation_id'])) {
        markSentToDashboard($db, (int)$_POST['reservation_id']);
        $_SESSION['reservation_flash'] = ['type' => 'success', 'message' => 'Reservation sent to dashboard.'];
        header('Location: index.php');
        exit;
    }
}

$reservations = $db ? readReservations($db) : [];
$userEmail = $_SESSION['user_email'] ?? 'user@olivarez.edu.ph';
$defaultDriverOptions = [
    ['name' => 'Lito Lobregat', 'vehicle' => 'Mitsubishi L300'],
    ['name' => 'Willy Trinidad', 'vehicle' => 'Toyota Hiace'],
    ['name' => 'Robert Silvano', 'vehicle' => 'Toyota Avanza'],
    ['name' => 'Ding Open', 'vehicle' => 'Nissan Urvan'],
    ['name' => 'Vener Nora', 'vehicle' => 'Toyota Innova'],
    ['name' => 'Mondskie Bangit', 'vehicle' => 'Hyundai Starex'],
    ['name' => 'Bencio Bustamante', 'vehicle' => 'Suzuki APV'],
    ['name' => 'Allan Agsap', 'vehicle' => 'Vehicle not assigned'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>My Reservations</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Space+Grotesk:wght@500;700&display=swap');

        :root {
            --forest: #2f6f51;
            --mint: #eef7f2;
            --card: #f7fbf8;
            --accent: #f2a14a;
            --ink: #1d2b27;
            --muted: #577065;
            --line: #1a1a1a;
            --fog: #dbece2;
            --paper: #fbfdfb;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'DM Sans', 'Segoe UI', sans-serif;
            background:
                radial-gradient(circle at top left, rgba(247, 255, 250, 0.22), transparent 28%),
                radial-gradient(circle at top right, rgba(255, 255, 255, 0.14), transparent 24%),
                linear-gradient(180deg, #3e7b5c 0%, #2d654d 52%, #214b39 100%);
            color: var(--ink);
            image-rendering: auto;
        }

        .page {
            min-height: 100vh;
            padding: 46px 6vw 80px;
            display: flex;
            flex-direction: column;
            gap: 26px;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            color: #f6fff9;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .header h1 {
            font-family: 'Space Grotesk', 'DM Sans', sans-serif;
            font-size: clamp(2rem, 2.5vw, 2.75rem);
            margin: 0;
        }

        .header p {
            margin: 8px 0 0;
            color: rgba(246, 255, 249, 0.85);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            border: none;
            border-radius: 999px;
            padding: 15px 28px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            color: var(--forest);
            background: linear-gradient(180deg, #f8fff9, #eaf8ef);
            box-shadow: 0 14px 30px rgba(0, 0, 0, 0.12);
        }

        .btn span.plus {
            font-size: 1.4rem;
            line-height: 1;
        }

        .btn-ghost {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 14px 24px;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            color: #f6fff9;
            border: 1px solid rgba(246, 255, 249, 0.38);
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
        }

        .btn-ghost:hover {
            background: rgba(255, 255, 255, 0.16);
        }

        .notice {
            border-radius: 16px;
            padding: 12px 18px;
            background: rgba(255, 255, 255, 0.2);
            color: #f6fff9;
            max-width: 520px;
        }

        .notice.error {
            background: rgba(255, 0, 0, 0.2);
        }

        .notice.success {
            background: rgba(55, 207, 134, 0.35);
        }

        .table-shell {
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 252, 249, 0.98));
            border-radius: 34px;
            overflow: hidden;
            border: 1px solid rgba(205, 223, 213, 0.9);
            padding: 10px;
        }

        .reservation-table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.96);
            table-layout: fixed;
        }

        .reservation-table th,
        .reservation-table td {
            padding: 24px 22px;
            text-align: left;
            border-bottom: 1px solid #e1ebe4;
            vertical-align: middle;
        }

        .reservation-table th {
            background: rgba(242, 248, 244, 0.94);
            font-size: 1rem;
            font-weight: 800;
            color: #1a2c21;
            letter-spacing: -0.01em;
        }

        .reservation-table thead th:first-child {
            border-top-left-radius: 24px;
        }

        .reservation-table thead th:last-child {
            border-top-right-radius: 24px;
        }

        .reservation-table tr:last-child td {
            border-bottom: none;
        }

        .reservation-id {
            font-weight: 800;
            font-size: 1rem;
            color: #1a2c21;
        }

        .reservation-table td {
            font-size: 0.98rem;
            color: #203127;
        }

        .reservation-destination-cell {
            width: 28%;
            max-width: 280px;
        }

        .reservation-destination-text {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 146px;
            padding: 11px 20px;
            border-radius: 999px;
            font-size: 0.95rem;
            font-weight: 700;
            border: 2px solid transparent;
        }

        .status-pending {
            color: #c57c00;
            background: #fff7db;
            border-color: #f3c54b;
        }

        .status-approved {
            color: #11974f;
            background: #e9fff0;
            border-color: #6fd89b;
        }

        .status-cancelled {
            color: #c73b34;
            background: #fff0ef;
            border-color: #f0a59f;
        }

        .status-completed,
        .status-sent {
            color: #215cf4;
            background: #eef4ff;
            border-color: #8fb0ff;
        }

        .status-alert-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-top: 10px;
            padding: 7px 12px;
            border-radius: 999px;
            background: #fff2f0;
            border: 1px solid #f2b6b0;
            color: #bb4038;
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }

        .updates-inbox-btn {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            width: min(100%, 430px);
            padding: 14px 18px;
            border-radius: 18px;
            border: 1px solid rgba(218, 230, 223, 0.95);
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(245, 250, 247, 0.98));
            color: #203127;
            cursor: pointer;
            box-shadow: 0 12px 26px rgba(9, 46, 28, 0.08);
        }

        .updates-inbox-copy {
            display: flex;
            flex-direction: column;
            gap: 3px;
            text-align: left;
            min-width: 0;
        }

        .updates-inbox-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            width: fit-content;
            padding: 0;
            border-radius: 0;
            background: transparent;
            color: #274838;
            font-size: 0.96rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            text-transform: none;
        }

        .updates-inbox-kicker::before {
            content: "";
            width: 12px;
            height: 12px;
            border-radius: 999px;
            background: #7ca98f;
            box-shadow: 0 0 0 4px rgba(124, 169, 143, 0.16);
        }

        .updates-inbox-title {
            font-size: 0.82rem;
            font-weight: 600;
            color: #60776c;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .updates-inbox-text {
            font-size: 0.92rem;
            line-height: 1.4;
            color: #4f675c;
        }

        .updates-inbox-count {
            flex-shrink: 0;
            min-width: 38px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            background: #ffffff;
            border: 1px solid #d5e3db;
            color: #527464;
            font-size: 0.95rem;
            font-weight: 800;
        }

        .updates-inbox-count.has-updates {
            background: #fff1ef;
            border-color: #f0c1bb;
            color: #bc4038;
        }

        .updates-modal-content {
            max-width: 760px;
        }

        .updates-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
            margin-top: 8px;
            max-height: 420px;
            overflow-y: auto;
            padding-right: 4px;
        }

        .updates-item {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            padding: 18px;
            border-radius: 20px;
            background: #f7fbf8;
            border: 1px solid #dce8e1;
        }

        .updates-item-copy {
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-width: 0;
        }

        .updates-item-meta {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .updates-item-code {
            font-size: 0.96rem;
            font-weight: 800;
            color: #1c3025;
        }

        .updates-item-date {
            font-size: 0.82rem;
            color: #60776c;
        }

        .updates-item-note {
            font-size: 0.94rem;
            line-height: 1.55;
            color: #4d6359;
            word-break: break-word;
        }

        .updates-empty {
            border-radius: 20px;
            padding: 22px;
            border: 1px dashed rgba(220, 232, 225, 0.9);
            background: rgba(247, 251, 248, 0.9);
            color: #5f786c;
        }

        .btn-secondary {
            padding: 10px 18px;
            border-radius: 12px;
            border: 1px solid rgba(47, 111, 81, 0.2);
            background: #ffffff;
            color: var(--forest);
            font-weight: 600;
            cursor: pointer;
        }

        .table-action {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(180deg, #ffffff, #f4f8f5);
            border: 2px solid #dde6ee;
            border-radius: 18px;
            padding: 11px 18px;
            font-weight: 700;
            font-size: 0.92rem;
            color: #12263f;
            cursor: pointer;
            min-width: 148px;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(9, 46, 28, 0.04);
        }

        .table-action svg {
            width: 20px;
            height: 20px;
        }

        .modal {
            position: fixed;
            inset: 0;
            background: rgba(10, 24, 18, 0.6);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            z-index: 20;
        }

        .modal.open {
            display: flex;
        }

        .modal-content {
            background: #ffffff;
            border-radius: 32px;
            padding: 32px;
            max-width: 920px;
            width: 100%;
            max-height: calc(100vh - 40px);
            overflow-y: auto;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.2);
        }

        .details-content {
            max-width: 760px;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-header h3 {
            margin: 0;
            font-family: 'Space Grotesk', 'DM Sans', sans-serif;
        }

        .modal-copy {
            margin: 10px 0 24px;
            color: var(--muted);
            line-height: 1.7;
        }

        .form-grid {
            display: grid;
            gap: 16px 20px;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        }

        label {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--muted);
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #d1e0d9;
            border-radius: 14px;
            font-family: inherit;
            font-size: 1rem;
        }

        textarea {
            min-height: 120px;
            resize: none;
        }

        .field-note {
            margin-top: 8px;
            font-size: 0.88rem;
            color: var(--muted);
        }

        .driver-option {
            color: #1d2b27;
        }

        input[list] {
            color-scheme: light;
        }

        .span-full {
            grid-column: 1 / -1;
        }

        .readonly-field {
            background: #f4f6fb;
            color: #6f7e87;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 18px;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px 20px;
            margin-top: 24px;
        }

        .detail-card {
            background: #f6faf7;
            border: 1px solid #dde9e2;
            border-radius: 18px;
            padding: 18px;
        }

        .detail-card strong {
            display: block;
            margin-bottom: 8px;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--muted);
        }

        @media (max-width: 720px) {
            .page {
                padding: 40px 6vw 60px;
            }

            .table-shell {
                overflow-x: auto;
            }

            .modal-actions {
                flex-direction: column-reverse;
            }

            .modal-actions button {
                width: 100%;
            }

            .reservation-table {
                min-width: 760px;
            }

            .updates-item {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <header class="header">
            <div>
                <h1>My Reservations</h1>
                <p>Manage your vehicle reservation requests</p>
            </div>
            <div class="header-actions">
                <button class="btn" id="openModal" type="button">
                    Add Reservation <span class="plus">+</span>
                </button>
                <a class="btn-ghost" href="logout.php">Logout</a>
            </div>
        </header>

        <?php if ($dbError): ?>
            <div class="notice error">Database connection failed. Live reservations are unavailable. Error: <?php echo htmlspecialchars($dbError); ?></div>
        <?php endif; ?>

        <?php if ($flash): ?>
            <div class="notice <?php echo $flash['type'] === 'success' ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
        <?php endif; ?>

        <div id="updatesInboxTrigger"><?php echo renderUserUpdatesInboxTrigger($reservations); ?></div>

        <section class="table-shell">
            <table class="reservation-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>Destination</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="reservationTableBody"><?php echo renderUserReservationRows($reservations); ?></tbody>
            </table>
        </section>
    </div>

    <div class="modal" id="reservationModal" aria-hidden="true">
        <div class="modal-content">
            <div class="modal-header">
                <h3>New Vehicle Reservation</h3>
                <button class="btn-secondary" id="closeModal" type="button">Close</button>
            </div>
            <p class="modal-copy">Fill out the form below to request a vehicle reservation. Admin will review and assign a vehicle and driver.</p>
            <form method="POST">
                <input type="hidden" name="action" value="create" />
                <div class="form-grid">
                    <div class="span-full">
                        <label for="oc_email">OC Email *</label>
                        <input id="oc_email" name="oc_email" type="email" class="readonly-field" value="<?php echo htmlspecialchars($userEmail); ?>" readonly required />
                    </div>
                    <div>
                        <label for="departure_date">Date of Departure *</label>
                        <input id="departure_date" name="departure_date" type="date" required />
                    </div>
                    <div>
                        <label for="departure_time">Time of Departure *</label>
                        <input id="departure_time" name="departure_time" type="time" required />
                    </div>
                    <div>
                        <label for="department">Department *</label>
                        <select id="department" name="department" required>
                            <option value="">Select your department</option>
                            <option>IT</option>
                            <option>Registrar</option>
                            <option>Accounting</option>
                            <option>Human Resources</option>
                            <option>Marketing</option>
                            <option>Operations</option>
                            <option>Faculty</option>
                            <option>Administration</option>
                        </select>
                    </div>
                    <div>
                        <label for="requested_vehicle">Vehicle Requested (Optional)</label>
                        <input id="requested_vehicle" name="requested_vehicle" type="text" placeholder="e.g., Van, Sedan, Bus" />
                        <div class="field-note">Admin will assign based on availability</div>
                    </div>
                    <div>
                        <label for="requested_driver">Driver Requested (Optional)</label>
                        <input id="requested_driver" name="requested_driver" type="text" list="driverOptions" placeholder="Driver name (if any preference)" />
                        <datalist id="driverOptions">
                            <?php foreach ($defaultDriverOptions as $driverOption): ?>
                                <option class="driver-option" value="<?php echo htmlspecialchars($driverOption['name']); ?>">
                                    <?php echo htmlspecialchars($driverOption['vehicle'] !== '' ? $driverOption['vehicle'] : 'Vehicle not assigned'); ?>
                                </option>
                            <?php endforeach; ?>
                        </datalist>
                        <div class="field-note">Leave blank if no preference</div>
                    </div>
                    <div>
                        <label for="passengers">Number of Passengers *</label>
                        <input id="passengers" name="passengers" type="number" min="1" placeholder="e.g., 5" required />
                    </div>
                    <div>
                        <label for="waiting_time">Waiting Time (Optional)</label>
                        <input id="waiting_time" name="waiting_time" type="text" placeholder="e.g., 2 hours" />
                    </div>
                    <div class="span-full">
                        <label for="destination">Destination *</label>
                        <input id="destination" name="destination" type="text" placeholder="Primary destination" required />
                    </div>
                    <div class="span-full">
                        <label for="other_destination">Other Destination (Optional)</label>
                        <input id="other_destination" name="other_destination" type="text" placeholder="Additional stops" />
                    </div>
                    <div class="span-full">
                        <label for="travel_time">Approximate Travel Time (Back and Forth) *</label>
                        <input id="travel_time" name="travel_time" type="text" placeholder="e.g., 4 hours, Half day, Full day" required />
                    </div>
                    <div class="span-full">
                        <label for="loads">Are there any loads? (Optional)</label>
                        <textarea id="loads" name="loads" placeholder="Specify if you'll be carrying equipment, materials, etc."></textarea>
                    </div>
                    <div class="span-full">
                        <label for="purpose">Purpose of Request *</label>
                        <textarea id="purpose" name="purpose" placeholder="e.g., School business, Personal, Academic conference, etc." required></textarea>
                    </div>
                </div>
                <div class="modal-actions">
                    <button class="btn" type="submit">Submit Reservation</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal" id="updatesModal" aria-hidden="true">
        <div class="modal-content updates-modal-content">
            <div class="modal-header">
                <h3>Updates Inbox</h3>
                <button class="btn-secondary" id="closeUpdatesModal" type="button">Close</button>
            </div>
            <p class="modal-copy">View all admin updates for your reservations in one place.</p>
            <div class="updates-list" id="updatesInboxList"><?php echo renderUserUpdatesInboxList($reservations); ?></div>
        </div>
    </div>

    <div class="modal" id="detailsModal" aria-hidden="true">
        <div class="modal-content details-content">
            <div class="modal-header">
                <h3 id="detailsTitle">Reservation Details</h3>
                <button class="btn-secondary" id="closeDetailsModal" type="button">Close</button>
            </div>
            <p class="modal-copy" id="detailsSubtitle">Review the submitted reservation information.</p>
            <div class="details-grid">
                <div class="detail-card"><strong>Status</strong><span id="detailStatus"></span></div>
                <div class="detail-card"><strong>Requested On</strong><span id="detailRequested"></span></div>
                <div class="detail-card"><strong>Date</strong><span id="detailDate"></span></div>
                <div class="detail-card"><strong>Time</strong><span id="detailTime"></span></div>
                <div class="detail-card"><strong>OC Email</strong><span id="detailEmail"></span></div>
                <div class="detail-card"><strong>Department</strong><span id="detailDepartment"></span></div>
                <div class="detail-card"><strong>Passengers</strong><span id="detailPassengers"></span></div>
                <div class="detail-card"><strong>Travel Time</strong><span id="detailTravelTime"></span></div>
                <div class="detail-card"><strong>Waiting Time</strong><span id="detailWaitingTime"></span></div>
                <div class="detail-card"><strong>Destination</strong><span id="detailDestination"></span></div>
                <div class="detail-card"><strong>Other Destination</strong><span id="detailOtherDestination"></span></div>
                <div class="detail-card"><strong>Requested Vehicle</strong><span id="detailRequestedVehicle"></span></div>
                <div class="detail-card"><strong>Requested Driver</strong><span id="detailRequestedDriver"></span></div>
                <div class="detail-card"><strong>Assigned Vehicle</strong><span id="detailVehicle"></span></div>
                <div class="detail-card"><strong>Assigned Driver</strong><span id="detailDriver"></span></div>
                <div class="detail-card span-full"><strong>Admin Note / Reason</strong><span id="detailAdminNote"></span></div>
                <div class="detail-card span-full"><strong>Purpose</strong><span id="detailPurpose"></span></div>
                <div class="detail-card span-full"><strong>Loads</strong><span id="detailLoads"></span></div>
            </div>
        </div>
    </div>

    <script>
        const openModalButton = document.getElementById('openModal');
        const closeModalButton = document.getElementById('closeModal');
        const reservationModal = document.getElementById('reservationModal');
        const openUpdatesModalButton = document.getElementById('openUpdatesModal');
        const updatesModal = document.getElementById('updatesModal');
        const closeUpdatesModalButton = document.getElementById('closeUpdatesModal');
        const detailsModal = document.getElementById('detailsModal');
        const closeDetailsModalButton = document.getElementById('closeDetailsModal');
        const requestedDriverInput = document.getElementById('requested_driver');
        const driverOptionsList = document.getElementById('driverOptions');
        const defaultDriverOptions = <?php echo json_encode($defaultDriverOptions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;

        const openReservationModal = () => {
            reservationModal.classList.add('open');
            reservationModal.setAttribute('aria-hidden', 'false');
        };

        const closeReservationModal = () => {
            reservationModal.classList.remove('open');
            reservationModal.setAttribute('aria-hidden', 'true');
        };

        openModalButton.addEventListener('click', openReservationModal);

        closeModalButton.addEventListener('click', closeReservationModal);

        reservationModal.addEventListener('click', (event) => {
            if (event.target === reservationModal) {
                closeReservationModal();
            }
        });

        const openUpdatesModal = () => {
            updatesModal.classList.add('open');
            updatesModal.setAttribute('aria-hidden', 'false');
        };

        const closeUpdatesModal = () => {
            updatesModal.classList.remove('open');
            updatesModal.setAttribute('aria-hidden', 'true');
        };

        openUpdatesModalButton.addEventListener('click', openUpdatesModal);
        closeUpdatesModalButton.addEventListener('click', closeUpdatesModal);

        updatesModal.addEventListener('click', (event) => {
            if (event.target === updatesModal) {
                closeUpdatesModal();
            }
        });

        const detailFields = {
            title: document.getElementById('detailsTitle'),
            subtitle: document.getElementById('detailsSubtitle'),
            status: document.getElementById('detailStatus'),
            requested: document.getElementById('detailRequested'),
            date: document.getElementById('detailDate'),
            time: document.getElementById('detailTime'),
            purpose: document.getElementById('detailPurpose'),
            passengers: document.getElementById('detailPassengers'),
            destination: document.getElementById('detailDestination'),
            otherDestination: document.getElementById('detailOtherDestination'),
            department: document.getElementById('detailDepartment'),
            travelTime: document.getElementById('detailTravelTime'),
            waitingTime: document.getElementById('detailWaitingTime'),
            email: document.getElementById('detailEmail'),
            loads: document.getElementById('detailLoads'),
            requestedVehicle: document.getElementById('detailRequestedVehicle'),
            requestedDriver: document.getElementById('detailRequestedDriver'),
            vehicle: document.getElementById('detailVehicle'),
            driver: document.getElementById('detailDriver'),
            adminNote: document.getElementById('detailAdminNote'),
        };

        const openDetailsModal = (button) => {
            detailFields.title.textContent = `Reservation ${button.dataset.code}`;
            detailFields.subtitle.textContent = `Submitted on ${button.dataset.requested}`;
            detailFields.status.textContent = button.dataset.status || 'Pending';
            detailFields.requested.textContent = button.dataset.requested || '-';
            detailFields.date.textContent = button.dataset.date || '-';
            detailFields.time.textContent = button.dataset.time || '-';
            detailFields.purpose.textContent = button.dataset.purpose || '-';
            detailFields.passengers.textContent = button.dataset.passengers ? `${button.dataset.passengers} people` : '-';
            detailFields.destination.textContent = button.dataset.destination || '-';
            detailFields.otherDestination.textContent = button.dataset.otherDestination || 'None';
            detailFields.department.textContent = button.dataset.department || '-';
            detailFields.travelTime.textContent = button.dataset.travelTime || '-';
            detailFields.waitingTime.textContent = button.dataset.waitingTime || 'None';
            detailFields.email.textContent = button.dataset.email || '-';
            detailFields.loads.textContent = button.dataset.loads || 'None';
            detailFields.requestedVehicle.textContent = button.dataset.requestedVehicle || 'No preference';
            detailFields.requestedDriver.textContent = button.dataset.requestedDriver || 'No preference';
            detailFields.vehicle.textContent = button.dataset.vehicle || 'To be assigned';
            detailFields.driver.textContent = button.dataset.driver || 'To be assigned';
            detailFields.adminNote.textContent = button.dataset.adminNote || 'No admin note yet.';
            detailsModal.classList.add('open');
            detailsModal.setAttribute('aria-hidden', 'false');
        };

        const closeDetailsModal = () => {
            detailsModal.classList.remove('open');
            detailsModal.setAttribute('aria-hidden', 'true');
        };

        document.addEventListener('click', (event) => {
            const button = event.target.closest('.open-details');
            if (!button) {
                return;
            }

            if (updatesModal.classList.contains('open')) {
                closeUpdatesModal();
            }

            openDetailsModal(button);
        });

        closeDetailsModalButton.addEventListener('click', closeDetailsModal);
        detailsModal.addEventListener('click', (event) => {
            if (event.target === detailsModal) {
                closeDetailsModal();
            }
        });

        (() => {
            const fallbackDrivers = defaultDriverOptions;
            let driverList = fallbackDrivers;

            try {
                const storedDrivers = localStorage.getItem('dispatch-drivers');
                if (storedDrivers) {
                    const parsedDrivers = JSON.parse(storedDrivers);
                    if (Array.isArray(parsedDrivers) && parsedDrivers.length) {
                        driverList = parsedDrivers;
                    }
                }
            } catch (error) {
                console.warn('Unable to load driver list from localStorage.', error);
            }

            driverOptionsList.innerHTML = '';
            driverList.forEach((driver) => {
                const option = document.createElement('option');
                option.value = driver.name || '';
                option.label = driver.vehicle || 'Vehicle not assigned';
                driverOptionsList.appendChild(option);
            });

            if (requestedDriverInput && !requestedDriverInput.value && driverList.length) {
                requestedDriverInput.placeholder = 'Select or type a driver name';
            }
        })();

        <?php if ($shouldOpenModal): ?>
        openReservationModal();
        <?php endif; ?>

        <?php if ($db): ?>
        (() => {
            let lastMarkup = reservationTableBody.innerHTML.trim();
            let lastUpdatesTriggerMarkup = document.getElementById('updatesInboxTrigger').innerHTML.trim();
            let lastUpdatesListMarkup = document.getElementById('updatesInboxList').innerHTML.trim();
            let isRefreshing = false;

            async function refreshReservations() {
                if (isRefreshing || document.hidden) return;
                isRefreshing = true;

                try {
                    const response = await fetch(`api/reservations.php?t=${Date.now()}`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        cache: 'no-store'
                    });

                    if (!response.ok) {
                        return;
                    }

                    const payload = await response.json();
                    const nextMarkup = (payload.html || '').trim();
                    const nextUpdatesTriggerMarkup = (payload.updatesTriggerHtml || '').trim();
                    const nextUpdatesListMarkup = (payload.updatesListHtml || '').trim();

                    if (nextMarkup !== lastMarkup) {
                        reservationTableBody.innerHTML = payload.html;
                        lastMarkup = nextMarkup;
                    }

                    if (nextUpdatesTriggerMarkup !== lastUpdatesTriggerMarkup) {
                        document.getElementById('updatesInboxTrigger').innerHTML = payload.updatesTriggerHtml || '';
                        lastUpdatesTriggerMarkup = nextUpdatesTriggerMarkup;
                        const refreshedOpenUpdatesModalButton = document.getElementById('openUpdatesModal');
                        refreshedOpenUpdatesModalButton.addEventListener('click', openUpdatesModal);
                    }

                    if (nextUpdatesListMarkup !== lastUpdatesListMarkup) {
                        document.getElementById('updatesInboxList').innerHTML = payload.updatesListHtml || '';
                        lastUpdatesListMarkup = nextUpdatesListMarkup;
                    }
                } catch (error) {
                    console.warn('Live reservation refresh failed.', error);
                } finally {
                    isRefreshing = false;
                }
            }

            setInterval(refreshReservations, 5000);
        })();
        <?php endif; ?>
    </script>
</body>
</html>
