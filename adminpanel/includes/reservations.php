<?php

function ensureReservationsSchema(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS reservations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_account_id INT DEFAULT NULL,
            reservation_code VARCHAR(32) NOT NULL,
            requested_at DATETIME NOT NULL,
            reservation_datetime DATETIME NOT NULL,
            passengers INT NOT NULL,
            destination VARCHAR(255) NOT NULL,
            other_destination VARCHAR(255) DEFAULT NULL,
            travel_time VARCHAR(120) NOT NULL,
            waiting_time VARCHAR(120) DEFAULT NULL,
            purpose TEXT NOT NULL,
            department VARCHAR(120) DEFAULT NULL,
            loads VARCHAR(255) DEFAULT NULL,
            oc_email VARCHAR(190) DEFAULT NULL,
            requested_vehicle VARCHAR(150) DEFAULT NULL,
            requested_driver VARCHAR(150) DEFAULT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'Pending',
            vehicle VARCHAR(120) DEFAULT NULL,
            driver VARCHAR(120) DEFAULT NULL,
            admin_note TEXT DEFAULT NULL,
            is_archived TINYINT(1) NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    );

    $reservationColumns = $pdo->query('SHOW COLUMNS FROM reservations')->fetchAll(PDO::FETCH_COLUMN);
    $reservationColumnUpdates = [
        'user_account_id' => 'ALTER TABLE reservations ADD COLUMN user_account_id INT DEFAULT NULL FIRST',
        'other_destination' => 'ALTER TABLE reservations ADD COLUMN other_destination VARCHAR(255) DEFAULT NULL AFTER destination',
        'waiting_time' => 'ALTER TABLE reservations ADD COLUMN waiting_time VARCHAR(120) DEFAULT NULL AFTER travel_time',
        'oc_email' => 'ALTER TABLE reservations ADD COLUMN oc_email VARCHAR(190) DEFAULT NULL AFTER loads',
        'requested_vehicle' => 'ALTER TABLE reservations ADD COLUMN requested_vehicle VARCHAR(150) DEFAULT NULL AFTER oc_email',
        'requested_driver' => 'ALTER TABLE reservations ADD COLUMN requested_driver VARCHAR(150) DEFAULT NULL AFTER requested_vehicle',
        'admin_note' => 'ALTER TABLE reservations ADD COLUMN admin_note TEXT DEFAULT NULL AFTER driver',
        'is_archived' => 'ALTER TABLE reservations ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0 AFTER admin_note',
    ];

    foreach ($reservationColumnUpdates as $column => $statement) {
        if (!in_array($column, $reservationColumns, true)) {
            $pdo->exec($statement);
        }
    }
}

function fetchReservations(PDO $pdo, ?int $userId = null, ?string $email = null, bool $includeArchived = false): array
{
    if ($userId !== null) {
        $sql = 'SELECT * FROM reservations
             WHERE (user_account_id = :user_id
                OR (user_account_id IS NULL AND oc_email = :email))';
        if (!$includeArchived) {
            $sql .= ' AND is_archived = 0';
        }
        $sql .= ' ORDER BY requested_at DESC';

        $stmt = $pdo->prepare(
            $sql
        );
        $stmt->execute([
            'user_id' => $userId,
            'email' => $email ?? '',
        ]);
        return $stmt->fetchAll();
    }

    $sql = 'SELECT * FROM reservations';
    if (!$includeArchived) {
        $sql .= ' WHERE is_archived = 0';
    }
    $sql .= ' ORDER BY requested_at DESC';

    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

function fetchArchivedReservations(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT * FROM reservations WHERE is_archived = 1 ORDER BY requested_at DESC');
    return $stmt->fetchAll();
}

function fetchDriverBoardReservations(PDO $pdo, string $weekStart, string $weekEnd): array
{
    $stmt = $pdo->prepare(
        "SELECT *
         FROM reservations
         WHERE status = 'Approved'
           AND driver IS NOT NULL
           AND TRIM(driver) <> ''
           AND reservation_datetime >= :week_start
           AND reservation_datetime < :week_end
         ORDER BY reservation_datetime ASC, id ASC"
    );
    $stmt->execute([
        'week_start' => $weekStart,
        'week_end' => $weekEnd,
    ]);

    return $stmt->fetchAll();
}

function reservationStatusClassAdmin(string $status): string
{
    $statusValue = strtolower(trim($status));

    if ($statusValue === 'approved') {
        return 'approved';
    }

    if ($statusValue === 'completed') {
        return 'completed';
    }

    if ($statusValue === 'cancelled' || $statusValue === 'canceled') {
        return 'cancelled';
    }

    return 'pending';
}

function reservationStatusKey(string $status): string
{
    $statusValue = strtolower(trim($status));

    if ($statusValue === 'approved') {
        return 'approved';
    }

    if ($statusValue === 'completed') {
        return 'completed';
    }

    if ($statusValue === 'cancelled' || $statusValue === 'canceled') {
        return 'cancelled';
    }

    return 'pending';
}

function reservationStatusClassUser(string $status): string
{
    $statusValue = strtolower(trim($status));

    if ($statusValue === 'approved') {
        return 'status-approved';
    }

    if ($statusValue === 'cancelled' || $statusValue === 'canceled') {
        return 'status-cancelled';
    }

    if ($statusValue === 'completed' || $statusValue === 'sent') {
        return 'status-completed';
    }

    return 'status-pending';
}

function reservationClientLabel(array $reservation): string
{
    $email = trim((string) ($reservation['oc_email'] ?? ''));
    if ($email === '') {
        return 'Walk-in Request';
    }

    $namePart = strstr($email, '@', true);
    if ($namePart === false || $namePart === '') {
        return $email;
    }

    $namePart = str_replace(['.', '_', '-'], ' ', $namePart);
    return ucwords($namePart);
}

function renderAdminReservationRows(array $reservations): string
{
    ob_start();

    if (!$reservations) {
        ?>
        <tr>
          <td colspan="7" class="text-center py-4 text-muted">No reservations submitted yet.</td>
        </tr>
        <?php
        return (string) ob_get_clean();
    }

    foreach ($reservations as $reservation) {
        $formId = 'approval-form-' . (int) $reservation['id'];
        $statusValue = (string) ($reservation['status'] ?? 'Pending');
        $statusClass = reservationStatusClassAdmin($statusValue);
        $clientLabel = reservationClientLabel($reservation);
        $serviceLabel = trim((string) ($reservation['requested_vehicle'] ?? '')) !== ''
            ? (string) $reservation['requested_vehicle']
            : 'Transport Service';

        $notesParts = [];
        if (!empty($reservation['destination'])) {
            $notesParts[] = (string) $reservation['destination'];
        }
        if (!empty($reservation['purpose'])) {
            $notesParts[] = 'Purpose: ' . (string) $reservation['purpose'];
        }
        if (!empty($reservation['other_destination'])) {
            $notesParts[] = 'Stop: ' . (string) $reservation['other_destination'];
        }
        if (!empty($reservation['waiting_time'])) {
            $notesParts[] = 'Wait: ' . (string) $reservation['waiting_time'];
        }
        if (!empty($reservation['loads'])) {
            $notesParts[] = 'Loads: ' . (string) $reservation['loads'];
        }
        $notesLabel = $notesParts ? implode(' | ', $notesParts) : 'No notes yet';
        $adminNote = trim((string) ($reservation['admin_note'] ?? ''));
        $searchText = implode(' ', array_filter([
            (string) ($reservation['reservation_code'] ?? ''),
            (string) ($reservation['oc_email'] ?? ''),
            $clientLabel,
            (string) ($reservation['department'] ?? ''),
            (string) ($reservation['destination'] ?? ''),
            (string) ($reservation['other_destination'] ?? ''),
            (string) ($reservation['purpose'] ?? ''),
            (string) ($reservation['loads'] ?? ''),
            (string) ($reservation['vehicle'] ?? ''),
            (string) ($reservation['driver'] ?? ''),
            $serviceLabel,
            $adminNote,
            date('M j, Y', strtotime((string) $reservation['reservation_datetime'])),
            date('H:i', strtotime((string) $reservation['reservation_datetime'])),
        ]));
        ?>
        <tr
          data-reservation-status="<?php echo htmlspecialchars(reservationStatusKey($statusValue)); ?>"
          data-reservation-search="<?php echo htmlspecialchars(strtolower($searchText)); ?>"
        >
          <td class="approvals-code-cell">
            <strong><?php echo htmlspecialchars((string) $reservation['reservation_code']); ?></strong>
            <div class="small text-muted"><?php echo htmlspecialchars((string) ($reservation['department'] ?? 'No department')); ?></div>
          </td>
          <td class="approvals-requester-cell">
            <strong><?php echo htmlspecialchars((string) ($reservation['oc_email'] ?? 'No email')); ?></strong>
            <div class="small text-muted"><?php echo htmlspecialchars($clientLabel); ?></div>
          </td>
          <td class="approvals-destination-cell">
            <strong><?php echo htmlspecialchars((string) $reservation['destination']); ?></strong>
            <div class="small text-muted"><?php echo htmlspecialchars((string) ($reservation['other_destination'] ?? '')); ?></div>
          </td>
          <td class="approvals-schedule-cell">
            <strong><?php echo htmlspecialchars(date('M j, Y', strtotime((string) $reservation['reservation_datetime']))); ?></strong>
            <div class="small text-muted"><?php echo htmlspecialchars(date('H:i', strtotime((string) $reservation['reservation_datetime']))); ?></div>
          </td>
          <td class="approvals-status-cell">
            <span class="pill <?php echo $statusClass; ?>"><?php echo htmlspecialchars($statusValue); ?></span>
            <div class="small text-muted approvals-status-subtext"><?php echo htmlspecialchars($serviceLabel); ?></div>
            <?php if ($adminNote !== ''): ?>
              <div class="small approvals-admin-note-preview"><?php echo htmlspecialchars($adminNote); ?></div>
            <?php endif; ?>
          </td>
          <td class="approvals-notes-cell">
            <div class="approvals-assignment-line"><?php echo htmlspecialchars((string) ($reservation['vehicle'] ?? 'To be assigned')); ?></div>
            <div class="small text-muted"><?php echo htmlspecialchars((string) ($reservation['driver'] ?? 'To be assigned')); ?></div>
          </td>
          <td class="text-end approvals-action-cell">
            <form method="post" class="approvals-actions-form" id="<?php echo htmlspecialchars($formId); ?>">
              <input type="hidden" name="id" value="<?php echo (int) $reservation['id']; ?>" />
              <select
                name="status"
                class="form-select form-select-sm approvals-status-select"
                data-status-live="true"
              >
                <option value="Pending" <?php echo strcasecmp($statusValue, 'Pending') === 0 ? 'selected' : ''; ?>>Pending</option>
                <option value="Approved" <?php echo strcasecmp($statusValue, 'Approved') === 0 ? 'selected' : ''; ?>>Approved</option>
                <option value="Cancelled" <?php echo strcasecmp($statusValue, 'Cancelled') === 0 ? 'selected' : ''; ?>>Cancelled</option>
                <option value="Completed" <?php echo strcasecmp($statusValue, 'Completed') === 0 ? 'selected' : ''; ?>>Completed</option>
              </select>
              <input type="hidden" name="vehicle" value="<?php echo htmlspecialchars((string) ($reservation['vehicle'] ?? '')); ?>" data-vehicle-hidden="true" />
              <input
                type="text"
                class="form-control form-control-sm approvals-vehicle-input"
                value="<?php echo htmlspecialchars((string) ($reservation['vehicle'] ?? '')); ?>"
                placeholder="Vehicle"
                data-vehicle-display="true"
                readonly
              />
              <select
                name="driver"
                class="form-select form-select-sm approvals-driver-input"
                data-driver-select="true"
                data-current-driver="<?php echo htmlspecialchars((string) ($reservation['driver'] ?? '')); ?>"
              >
                <option value="">Driver</option>
              </select>
              <input
                type="text"
                name="admin_note"
                class="form-control form-control-sm approvals-note-input"
                value="<?php echo htmlspecialchars($adminNote); ?>"
                placeholder="Add admin note or cancellation reason"
              />
              <button class="btn btn-sm btn-primary approvals-save-btn" type="submit" name="action" value="update">Save</button>
              <button class="btn btn-sm approvals-archive-btn" type="submit" name="action" value="archive">Archive</button>
              <button
                type="button"
                class="approvals-view-btn open-approval-details"
                data-code="<?php echo htmlspecialchars((string) $reservation['reservation_code']); ?>"
                data-client="<?php echo htmlspecialchars($clientLabel); ?>"
                data-email="<?php echo htmlspecialchars((string) ($reservation['oc_email'] ?? '')); ?>"
                data-department="<?php echo htmlspecialchars((string) ($reservation['department'] ?? '')); ?>"
                data-date="<?php echo htmlspecialchars(date('M j, Y', strtotime((string) $reservation['reservation_datetime']))); ?>"
                data-time="<?php echo htmlspecialchars(date('H:i', strtotime((string) $reservation['reservation_datetime']))); ?>"
                data-destination="<?php echo htmlspecialchars((string) $reservation['destination']); ?>"
                data-other-destination="<?php echo htmlspecialchars((string) ($reservation['other_destination'] ?? '')); ?>"
                data-purpose="<?php echo htmlspecialchars((string) ($reservation['purpose'] ?? '')); ?>"
                data-status="<?php echo htmlspecialchars($statusValue); ?>"
                data-travel-time="<?php echo htmlspecialchars((string) ($reservation['travel_time'] ?? '')); ?>"
                data-waiting-time="<?php echo htmlspecialchars((string) ($reservation['waiting_time'] ?? '')); ?>"
                data-loads="<?php echo htmlspecialchars((string) ($reservation['loads'] ?? '')); ?>"
                data-passengers="<?php echo htmlspecialchars((string) ($reservation['passengers'] ?? '')); ?>"
                data-vehicle="<?php echo htmlspecialchars((string) ($reservation['vehicle'] ?? 'To be assigned')); ?>"
                data-driver="<?php echo htmlspecialchars((string) ($reservation['driver'] ?? 'To be assigned')); ?>"
                data-service="<?php echo htmlspecialchars($serviceLabel); ?>"
                data-notes="<?php echo htmlspecialchars($notesLabel); ?>"
                data-admin-note="<?php echo htmlspecialchars($adminNote); ?>"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
                View Details
              </button>
            </form>
          </td>
        </tr>
        <?php
    }

    return (string) ob_get_clean();
}

function renderArchivedReservationRows(array $reservations): string
{
    ob_start();

    if (!$reservations) {
        ?>
        <tr>
          <td colspan="8" class="text-center py-4 text-muted">No archived reservations yet.</td>
        </tr>
        <?php
        return (string) ob_get_clean();
    }

    foreach ($reservations as $reservation) {
        $statusValue = (string) ($reservation['status'] ?? 'Pending');
        $statusClass = reservationStatusClassAdmin($statusValue);
        $clientLabel = reservationClientLabel($reservation);
        $serviceLabel = trim((string) ($reservation['requested_vehicle'] ?? '')) !== ''
            ? (string) $reservation['requested_vehicle']
            : 'Transport Service';
        $adminNote = trim((string) ($reservation['admin_note'] ?? ''));
        $searchText = implode(' ', array_filter([
            (string) ($reservation['reservation_code'] ?? ''),
            (string) ($reservation['oc_email'] ?? ''),
            $clientLabel,
            (string) ($reservation['department'] ?? ''),
            (string) ($reservation['destination'] ?? ''),
            (string) ($reservation['other_destination'] ?? ''),
            (string) ($reservation['purpose'] ?? ''),
            (string) ($reservation['loads'] ?? ''),
            (string) ($reservation['vehicle'] ?? ''),
            (string) ($reservation['driver'] ?? ''),
            $serviceLabel,
            $adminNote,
            date('M j, Y', strtotime((string) $reservation['reservation_datetime'])),
            date('H:i', strtotime((string) $reservation['reservation_datetime'])),
        ]));
        ?>
        <tr data-archive-search="<?php echo htmlspecialchars(strtolower($searchText)); ?>">
          <td class="approvals-code-cell">
            <strong><?php echo htmlspecialchars((string) $reservation['reservation_code']); ?></strong>
            <div class="small text-muted"><?php echo htmlspecialchars((string) ($reservation['department'] ?? 'No department')); ?></div>
          </td>
          <td class="approvals-requester-cell">
            <strong><?php echo htmlspecialchars((string) ($reservation['oc_email'] ?? 'No email')); ?></strong>
            <div class="small text-muted"><?php echo htmlspecialchars($clientLabel); ?></div>
          </td>
          <td class="approvals-destination-cell">
            <strong><?php echo htmlspecialchars((string) $reservation['destination']); ?></strong>
            <div class="small text-muted"><?php echo htmlspecialchars((string) ($reservation['other_destination'] ?? '')); ?></div>
          </td>
          <td class="approvals-schedule-cell">
            <strong><?php echo htmlspecialchars(date('M j, Y', strtotime((string) $reservation['reservation_datetime']))); ?></strong>
            <div class="small text-muted"><?php echo htmlspecialchars(date('H:i', strtotime((string) $reservation['reservation_datetime']))); ?></div>
          </td>
          <td class="approvals-status-cell">
            <span class="pill <?php echo $statusClass; ?>"><?php echo htmlspecialchars($statusValue); ?></span>
            <?php if ($adminNote !== ''): ?>
              <div class="small approvals-admin-note-preview"><?php echo htmlspecialchars($adminNote); ?></div>
            <?php endif; ?>
          </td>
          <td class="approvals-notes-cell">
            <div class="approvals-assignment-line"><?php echo htmlspecialchars((string) ($reservation['vehicle'] ?? 'To be assigned')); ?></div>
            <div class="small text-muted"><?php echo htmlspecialchars((string) ($reservation['driver'] ?? 'To be assigned')); ?></div>
          </td>
          <td>
            <span class="badge-pill">Archived</span>
          </td>
          <td class="text-end approvals-action-cell">
            <form method="post" class="archive-actions-form">
              <input type="hidden" name="id" value="<?php echo (int) $reservation['id']; ?>" />
              <div class="archive-actions-row">
                <button class="btn btn-sm approvals-restore-btn" type="submit" name="action" value="restore">Restore</button>
                <button
                  class="btn btn-sm approvals-delete-btn"
                  type="submit"
                  name="action"
                  value="delete"
                  onclick="return confirm('Delete this archived reservation permanently? This cannot be undone.');"
                >Delete</button>
              </div>
              <button
                type="button"
                class="approvals-view-btn open-approval-details"
                data-code="<?php echo htmlspecialchars((string) $reservation['reservation_code']); ?>"
                data-client="<?php echo htmlspecialchars($clientLabel); ?>"
                data-email="<?php echo htmlspecialchars((string) ($reservation['oc_email'] ?? '')); ?>"
                data-department="<?php echo htmlspecialchars((string) ($reservation['department'] ?? '')); ?>"
                data-date="<?php echo htmlspecialchars(date('M j, Y', strtotime((string) $reservation['reservation_datetime']))); ?>"
                data-time="<?php echo htmlspecialchars(date('H:i', strtotime((string) $reservation['reservation_datetime']))); ?>"
                data-destination="<?php echo htmlspecialchars((string) $reservation['destination']); ?>"
                data-other-destination="<?php echo htmlspecialchars((string) ($reservation['other_destination'] ?? '')); ?>"
                data-purpose="<?php echo htmlspecialchars((string) ($reservation['purpose'] ?? '')); ?>"
                data-status="<?php echo htmlspecialchars($statusValue); ?>"
                data-travel-time="<?php echo htmlspecialchars((string) ($reservation['travel_time'] ?? '')); ?>"
                data-waiting-time="<?php echo htmlspecialchars((string) ($reservation['waiting_time'] ?? '')); ?>"
                data-loads="<?php echo htmlspecialchars((string) ($reservation['loads'] ?? '')); ?>"
                data-passengers="<?php echo htmlspecialchars((string) ($reservation['passengers'] ?? '')); ?>"
                data-vehicle="<?php echo htmlspecialchars((string) ($reservation['vehicle'] ?? 'To be assigned')); ?>"
                data-driver="<?php echo htmlspecialchars((string) ($reservation['driver'] ?? 'To be assigned')); ?>"
                data-service="<?php echo htmlspecialchars($serviceLabel); ?>"
                data-notes="<?php echo htmlspecialchars((string) ($reservation['destination'] ?? '')); ?>"
                data-admin-note="<?php echo htmlspecialchars($adminNote); ?>"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
                View Details
              </button>
            </form>
          </td>
        </tr>
        <?php
    }

    return (string) ob_get_clean();
}

function filterReservationsByStatus(array $reservations, string $statusKey): array
{
    return array_values(array_filter($reservations, static function (array $reservation) use ($statusKey): bool {
        return reservationStatusKey((string) ($reservation['status'] ?? 'Pending')) === $statusKey;
    }));
}

function renderAdminReservationSections(array $reservations): string
{
    $sections = [
        'pending' => 'Pending Reservations',
        'approved' => 'Approved Reservations',
        'cancelled' => 'Cancelled Reservations',
        'completed' => 'Completed Reservations',
    ];

    ob_start();

    foreach ($sections as $key => $title) {
        $items = filterReservationsByStatus($reservations, $key);
        ?>
        <section class="approvals-status-section" data-section-status="<?php echo htmlspecialchars($key); ?>">
          <div class="approvals-section-head">
            <h3><?php echo htmlspecialchars($title); ?></h3>
            <span class="approvals-section-count"><?php echo count($items); ?></span>
          </div>
          <div class="card card-shadow approvals-card-shell p-4">
            <table class="table align-middle approvals-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Requester</th>
                  <th>Destination</th>
                  <th>Schedule</th>
                  <th>Status</th>
                  <th>Vehicle / Driver</th>
                  <th class="text-end" style="width: 360px;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php echo renderAdminReservationRows($items); ?>
              </tbody>
            </table>
          </div>
        </section>
        <?php
    }

    return (string) ob_get_clean();
}

function renderUserReservationRows(array $reservations): string
{
    ob_start();

    foreach ($reservations as $reservation) {
        $statusValue = (string) ($reservation['status'] ?? 'Pending');
        $statusClass = reservationStatusClassUser($statusValue);
        $adminNote = trim((string) ($reservation['admin_note'] ?? ''));
        ?>
        <tr>
            <td class="reservation-id">#<?php echo htmlspecialchars((string) $reservation['reservation_code']); ?></td>
            <td><?php echo htmlspecialchars(date('n/j/Y', strtotime((string) $reservation['reservation_datetime']))); ?></td>
            <td class="reservation-destination-cell">
                <?php $destination = (string) $reservation['destination']; ?>
                <span class="reservation-destination-text" title="<?php echo htmlspecialchars($destination); ?>"><?php echo htmlspecialchars($destination); ?></span>
            </td>
            <td>
                <span class="status-pill <?php echo $statusClass; ?>">
                    <?php echo htmlspecialchars(ucfirst($statusValue)); ?>
                </span>
                <?php if ($adminNote !== ''): ?>
                    <span class="status-alert-badge">Notice</span>
                <?php endif; ?>
            </td>
            <td>
                <button
                    class="table-action open-details"
                    type="button"
                    data-code="<?php echo htmlspecialchars((string) $reservation['reservation_code']); ?>"
                    data-requested="<?php echo htmlspecialchars(date('n/j/Y', strtotime((string) $reservation['requested_at']))); ?>"
                    data-date="<?php echo htmlspecialchars(date('n/j/Y', strtotime((string) $reservation['reservation_datetime']))); ?>"
                    data-time="<?php echo htmlspecialchars(date('H:i', strtotime((string) $reservation['reservation_datetime']))); ?>"
                    data-purpose="<?php echo htmlspecialchars((string) $reservation['purpose']); ?>"
                    data-passengers="<?php echo htmlspecialchars((string) $reservation['passengers']); ?>"
                    data-destination="<?php echo htmlspecialchars((string) $reservation['destination']); ?>"
                    data-other-destination="<?php echo htmlspecialchars((string) ($reservation['other_destination'] ?? '')); ?>"
                    data-department="<?php echo htmlspecialchars((string) ($reservation['department'] ?? '')); ?>"
                    data-status="<?php echo htmlspecialchars($statusValue); ?>"
                    data-travel-time="<?php echo htmlspecialchars((string) ($reservation['travel_time'] ?? '')); ?>"
                    data-waiting-time="<?php echo htmlspecialchars((string) ($reservation['waiting_time'] ?? '')); ?>"
                    data-email="<?php echo htmlspecialchars((string) ($reservation['oc_email'] ?? '')); ?>"
                    data-loads="<?php echo htmlspecialchars((string) ($reservation['loads'] ?? '')); ?>"
                    data-requested-vehicle="<?php echo htmlspecialchars((string) ($reservation['requested_vehicle'] ?? '')); ?>"
                    data-requested-driver="<?php echo htmlspecialchars((string) ($reservation['requested_driver'] ?? '')); ?>"
                    data-vehicle="<?php echo htmlspecialchars((string) ($reservation['vehicle'] ?? 'To be assigned')); ?>"
                    data-driver="<?php echo htmlspecialchars((string) ($reservation['driver'] ?? 'To be assigned')); ?>"
                    data-admin-note="<?php echo htmlspecialchars($adminNote); ?>"
                >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                    View Details
                </button>
            </td>
        </tr>
        <?php
    }

    if (!$reservations) {
        ?>
        <tr>
            <td colspan="6" class="text-center" style="padding: 24px; color: #577065;">No reservations yet.</td>
        </tr>
        <?php
    }

    return (string) ob_get_clean();
}

function reservationUpdatesData(array $reservations): array
{
    return array_values(array_filter($reservations, static function (array $reservation): bool {
        return trim((string) ($reservation['admin_note'] ?? '')) !== '';
    }));
}

function renderUserUpdatesInboxTrigger(array $reservations): string
{
    $updates = reservationUpdatesData($reservations);
    $count = count($updates);

    ob_start();
    ?>
    <button class="updates-inbox-btn" id="openUpdatesModal" type="button">
        <div class="updates-inbox-copy">
            <span class="updates-inbox-kicker">Updates Inbox</span>
            <div class="updates-inbox-title">Reservation updates</div>
            <div class="updates-inbox-text">
                <?php if ($updates): ?>
                    You have <?php echo $count; ?> reservation update<?php echo $count === 1 ? '' : 's'; ?> from admin.
                <?php else: ?>
                    No admin updates yet.
                <?php endif; ?>
            </div>
        </div>
        <span class="updates-inbox-count <?php echo $updates ? 'has-updates' : ''; ?>"><?php echo $count; ?></span>
    </button>
    <?php

    return (string) ob_get_clean();
}

function renderUserUpdatesInboxList(array $reservations): string
{
    $updates = reservationUpdatesData($reservations);

    ob_start();

    if ($updates) {
        foreach ($updates as $update) {
            $updateStatus = (string) ($update['status'] ?? 'Pending');
            $updateNote = trim((string) ($update['admin_note'] ?? ''));
            $updateCode = (string) ($update['reservation_code'] ?? '');
            $updateDate = date('n/j/Y', strtotime((string) ($update['reservation_datetime'] ?? 'now')));
            $updateTime = date('H:i', strtotime((string) ($update['reservation_datetime'] ?? 'now')));
            ?>
            <article class="updates-item">
                <div class="updates-item-copy">
                    <div class="updates-item-meta">
                        <span class="updates-item-code">#<?php echo htmlspecialchars($updateCode); ?></span>
                        <span class="status-pill <?php echo htmlspecialchars(reservationStatusClassUser($updateStatus)); ?>">
                            <?php echo htmlspecialchars(ucfirst($updateStatus)); ?>
                        </span>
                        <span class="updates-item-date"><?php echo htmlspecialchars($updateDate); ?> at <?php echo htmlspecialchars($updateTime); ?></span>
                    </div>
                    <div class="updates-item-note"><?php echo htmlspecialchars($updateNote); ?></div>
                </div>
                <button
                    class="table-action open-details"
                    type="button"
                    data-code="<?php echo htmlspecialchars($updateCode); ?>"
                    data-requested="<?php echo htmlspecialchars(date('n/j/Y', strtotime((string) ($update['requested_at'] ?? 'now')))); ?>"
                    data-date="<?php echo htmlspecialchars($updateDate); ?>"
                    data-time="<?php echo htmlspecialchars($updateTime); ?>"
                    data-purpose="<?php echo htmlspecialchars((string) ($update['purpose'] ?? '')); ?>"
                    data-passengers="<?php echo htmlspecialchars((string) ($update['passengers'] ?? '')); ?>"
                    data-destination="<?php echo htmlspecialchars((string) ($update['destination'] ?? '')); ?>"
                    data-other-destination="<?php echo htmlspecialchars((string) ($update['other_destination'] ?? '')); ?>"
                    data-department="<?php echo htmlspecialchars((string) ($update['department'] ?? '')); ?>"
                    data-status="<?php echo htmlspecialchars($updateStatus); ?>"
                    data-travel-time="<?php echo htmlspecialchars((string) ($update['travel_time'] ?? '')); ?>"
                    data-waiting-time="<?php echo htmlspecialchars((string) ($update['waiting_time'] ?? '')); ?>"
                    data-email="<?php echo htmlspecialchars((string) ($update['oc_email'] ?? '')); ?>"
                    data-loads="<?php echo htmlspecialchars((string) ($update['loads'] ?? '')); ?>"
                    data-requested-vehicle="<?php echo htmlspecialchars((string) ($update['requested_vehicle'] ?? '')); ?>"
                    data-requested-driver="<?php echo htmlspecialchars((string) ($update['requested_driver'] ?? '')); ?>"
                    data-vehicle="<?php echo htmlspecialchars((string) ($update['vehicle'] ?? 'To be assigned')); ?>"
                    data-driver="<?php echo htmlspecialchars((string) ($update['driver'] ?? 'To be assigned')); ?>"
                    data-admin-note="<?php echo htmlspecialchars($updateNote); ?>"
                >
                    View Details
                </button>
            </article>
            <?php
        }
    } else {
        ?>
        <div class="updates-empty">No reservation updates from admin yet.</div>
        <?php
    }

    return (string) ob_get_clean();
}

function reservationDashboardPayload(PDO $pdo): array
{
    $todayStart = new DateTimeImmutable('today');
    $todayEnd = $todayStart->modify('+1 day');

    $summaryStmt = $pdo->prepare(
        "SELECT
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending_count,
            SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) AS approved_count,
            SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed_count,
            SUM(CASE WHEN reservation_datetime >= :today_start AND reservation_datetime < :today_end THEN 1 ELSE 0 END) AS today_count
         FROM reservations"
    );
    $summaryStmt->execute([
        'today_start' => $todayStart->format('Y-m-d H:i:s'),
        'today_end' => $todayEnd->format('Y-m-d H:i:s'),
    ]);
    $summary = $summaryStmt->fetch() ?: [];

    $weekAnchor = new DateTimeImmutable('monday this week');
    $weeklyLabels = [];
    $weeklySeries = [
        'pending' => [],
        'approved' => [],
        'completed' => [],
    ];

    $weeklyStmt = $pdo->prepare(
        "SELECT
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending_count,
            SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) AS approved_count,
            SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed_count
         FROM reservations
         WHERE reservation_datetime >= :week_start AND reservation_datetime < :week_end"
    );

    for ($offset = 3; $offset >= 0; $offset--) {
        $weekStart = $weekAnchor->modify('-' . $offset . ' week');
        $weekEnd = $weekStart->modify('+1 week');
        $weeklyStmt->execute([
            'week_start' => $weekStart->format('Y-m-d H:i:s'),
            'week_end' => $weekEnd->format('Y-m-d H:i:s'),
        ]);
        $weekCounts = $weeklyStmt->fetch() ?: [];

        $weeklyLabels[] = $weekStart->format('M j');
        $weeklySeries['pending'][] = (int) ($weekCounts['pending_count'] ?? 0);
        $weeklySeries['approved'][] = (int) ($weekCounts['approved_count'] ?? 0);
        $weeklySeries['completed'][] = (int) ($weekCounts['completed_count'] ?? 0);
    }

    return [
        'summary' => [
            'pending_count' => (int) ($summary['pending_count'] ?? 0),
            'approved_count' => (int) ($summary['approved_count'] ?? 0),
            'completed_count' => (int) ($summary['completed_count'] ?? 0),
            'today_count' => (int) ($summary['today_count'] ?? 0),
        ],
        'weekly_labels' => $weeklyLabels,
        'weekly_series' => $weeklySeries,
        'status_labels' => ['Pending', 'Approved', 'Completed'],
        'status_data' => [
            (int) ($summary['pending_count'] ?? 0),
            (int) ($summary['approved_count'] ?? 0),
            (int) ($summary['completed_count'] ?? 0),
        ],
    ];
}
