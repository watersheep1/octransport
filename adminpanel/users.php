<?php
require __DIR__ . '/includes/auth.php';

$current = 'users';
$page_title = 'Driver Management';

$weekInput = $_GET['week'] ?? null;
if ($weekInput && preg_match('/^\d{4}-\d{2}-\d{2}$/', $weekInput)) {
    $baseDate = new DateTimeImmutable($weekInput);
} else {
    $baseDate = new DateTimeImmutable('today');
}

$weekStart = $baseDate->modify('monday this week');
$weekDays = [];
for ($i = 0; $i < 7; $i++) {
    $date = $weekStart->modify("+$i day");
    $weekDays[] = [
        'label' => $date->format('l'),
        'short' => strtoupper($date->format('D')),
        'date' => $date->format('Y-m-d'),
        'display' => $date->format('F j, Y')
    ];
}

$prevWeek = $weekStart->modify('-7 day');
$nextWeek = $weekStart->modify('+7 day');

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>
<div class="content">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div class="users-header">
      <h2>Driver Management</h2>
      <p>Weekly dispatch board for drivers, plate numbers, and daily trip availability.</p>
    </div>
    <button class="blue-btn" type="button" id="add-driver-btn">+ Add Driver</button>
  </div>

  <div class="users-card">
    <div class="calendar-toolbar mb-3">
      <div>
        <h5 class="mb-1" style="font-size: 20px;">Driver Schedule Board</h5>
        <p class="mb-0 text-muted" style="font-size: 13px;">Click any schedule cell to update trip details or driver availability.</p>
      </div>
      <div class="calendar-toolbar-actions">
        <a class="btn btn-outline-secondary btn-sm" href="users.php?week=<?php echo urlencode($prevWeek->format('Y-m-d')); ?>">Prev</a>
        <a class="btn btn-outline-secondary btn-sm" href="users.php">Today</a>
        <a class="btn btn-outline-secondary btn-sm" href="users.php?week=<?php echo urlencode($nextWeek->format('Y-m-d')); ?>">Next</a>
      </div>
    </div>

    <div class="driver-board-wrap">
      <table class="driver-board-table" id="driver-board-table">
        <thead>
          <tr class="driver-board-tophead">
            <th>Drivers</th>
            <th>Plate No.</th>
            <?php foreach ($weekDays as $day): ?>
              <th><?php echo htmlspecialchars($day['label']); ?></th>
            <?php endforeach; ?>
          </tr>
          <tr class="driver-board-subhead">
            <th></th>
            <th></th>
            <?php foreach ($weekDays as $day): ?>
              <th><?php echo htmlspecialchars(strtoupper($day['display'])); ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody id="driver-board-body"></tbody>
      </table>
    </div>

    <div class="agenda-details mt-3" id="scheduleDetails">
      <h6>Schedule Details</h6>
      <div class="text-muted">Click a schedule cell to view the full assignment details.</div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

<div class="modal fade soft-modal" id="driverModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="driverModalTitle">Add Driver</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="driverForm">
          <input type="hidden" id="driverIndex" />
          <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" class="form-control soft-input" id="driverName" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Phone Number</label>
            <input type="text" class="form-control soft-input" id="driverPhone" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Vehicle</label>
            <input type="text" class="form-control soft-input" id="driverVehicle" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Plate Number</label>
            <input type="text" class="form-control soft-input" id="driverPlate" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Registration Info</label>
            <input type="text" class="form-control soft-input" id="driverRegistration" required />
          </div>
          <div class="mb-3 d-none" id="driverStatusGroup">
            <label class="form-label">Default Status</label>
            <select class="form-select soft-input" id="driverStatus">
              <option>Available</option>
              <option>On Trip</option>
              <option>Off Duty</option>
              <option>Leave</option>
            </select>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="blue-btn" id="saveDriver">Save Driver</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade soft-modal" id="scheduleModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="scheduleModalTitle">Update Schedule</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="scheduleForm">
          <input type="hidden" id="scheduleDriverIndex" />
          <input type="hidden" id="scheduleDateKey" />
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Driver</label>
              <input type="text" class="form-control soft-input" id="scheduleDriverName" readonly />
            </div>
            <div class="col-md-6">
              <label class="form-label">Date</label>
              <input type="date" class="form-control soft-input" id="scheduleDateLabel" readonly />
            </div>
            <div class="col-md-6">
              <label class="form-label">Status</label>
              <select class="form-select soft-input" id="scheduleStatus">
                <option>Available</option>
                <option>On Trip</option>
                <option>Off Duty</option>
                <option>Leave</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Time</label>
              <input type="time" class="form-control soft-input" id="scheduleTime" step="900" />
            </div>
            <div class="col-12">
              <label class="form-label">Assignment / Destination</label>
              <textarea class="form-control soft-input" id="scheduleTask" rows="3" placeholder="Airport pickup / client name / route"></textarea>
            </div>
            <div class="col-12">
              <label class="form-label">Notes</label>
              <textarea class="form-control soft-input" id="scheduleNotes" rows="2" placeholder="Optional note"></textarea>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-danger me-auto" id="clearSchedule">Clear Cell</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="blue-btn" id="saveSchedule">Save Schedule</button>
      </div>
    </div>
  </div>
</div>

<script>
  const driversKey = 'dispatch-drivers';
  const scheduleKey = 'dispatch-week-schedule';
  const reservationSyncKey = 'dispatch-reservations-updated-at';
  const driversSeedKey = 'dispatch-drivers-seed-version';
  const scheduleSeedKey = 'dispatch-schedule-seed-version';
  const blankSeedVersion = 'weekly-board-coding-leave-v3';
  const weekDates = <?php echo json_encode($weekDays, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
  const weekFeedUrl = <?php echo json_encode('api/driver-board-reservations.php?week=' . $weekStart->format('Y-m-d'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
  const boardBody = document.getElementById('driver-board-body');
  const detailsPanel = document.getElementById('scheduleDetails');
  const driverModal = new bootstrap.Modal(document.getElementById('driverModal'));
  const scheduleModal = new bootstrap.Modal(document.getElementById('scheduleModal'));
  let boardReservations = [];
  let isBoardRefreshing = false;

  const defaultDrivers = [
    { name: 'Lito Lobregat', phone: '0950 034 8453', vehicle: 'Tagaytay Isuzu Travis', plate: 'NKA 8749', registration: 'Registration September', status: 'Available' },
    { name: 'Willy Trinidad', phone: '0930 063 7947', vehicle: 'Traviz', plate: 'NKP 9248', registration: 'Registration August', status: 'Available' },
    { name: 'Robert Silvano', phone: '0981 191 6344', vehicle: 'Isuzu Travis', plate: 'NIU 6895', registration: 'Registration May', status: 'Available' },
    { name: 'Ding Open', phone: '0951 556 9094', vehicle: 'Isuzu Travis', plate: 'NIG 8729', registration: 'Registration September', status: 'Available' },
    { name: 'Vener Nora', phone: '0945 566 2287', vehicle: 'Mitsubishi L300', plate: 'ZSG 102', registration: 'Registration February', status: 'Available' },
    { name: 'Mondskie Bangit', phone: '0965 466 7824', vehicle: 'Mitsubishi L300', plate: 'NUQ 286', registration: 'Registration June', status: 'Available' },
    { name: 'Bencio Bustamante', phone: '0951 612 6869', vehicle: 'Mitsubishi L300', plate: 'XNG 790', registration: 'Registration October', status: 'Available' },
    { name: 'Allan Agsap', phone: '', vehicle: 'Unknown Unit', plate: 'TQO 423', registration: 'Registration March', status: 'Leave' }
  ];

  const codingDayByPlate = {
    'NKA 8749': 'friday',
    'NKP 9248': 'thursday',
    'NIU 6895': 'wednesday',
    'NIG 8729': 'friday',
    'ZSG 102': 'monday',
    'NUQ 286': 'wednesday',
    'XNG 790': 'friday'
  };

  function getDefaultStatusForDriverOnDate(driver, dateKey) {
    const plate = (driver.plate || '').trim();
    const driverStatus = driver.status || 'Available';
    const codingDay = codingDayByPlate[plate] || '';
    if (!codingDay || !dateKey) {
      return driverStatus;
    }

    const date = new Date(`${dateKey}T00:00:00`);
    const dayName = date.toLocaleDateString('en-US', { weekday: 'long' }).toLowerCase();
    return dayName === codingDay ? 'Leave' : driverStatus;
  }

  function createBlankWeekSchedule(drivers) {
    const schedule = {};

    drivers.forEach((driver) => {
      const plate = (driver.plate || '').trim();
      if (!plate) return;

      schedule[plate] = {};
      weekDates.forEach((day) => {
        const finalStatus = getDefaultStatusForDriverOnDate(driver, day.date);
        schedule[plate][day.date] = {
          status: finalStatus,
          time: finalStatus === 'Off Duty' ? 'Offset' : (finalStatus === 'Leave' ? 'Leave' : ''),
          task: '',
          notes: '',
          manualOverride: false
        };
      });
    });

    return schedule;
  }

  const defaultSchedule = createBlankWeekSchedule(defaultDrivers);

  function getDrivers() {
    const seeded = localStorage.getItem(driversSeedKey);
    const stored = localStorage.getItem(driversKey);
    if (!stored || seeded !== blankSeedVersion) {
      localStorage.setItem(driversKey, JSON.stringify(defaultDrivers));
      localStorage.setItem(driversSeedKey, blankSeedVersion);
      return defaultDrivers;
    }
    return JSON.parse(stored);
  }

  function saveDrivers(drivers) {
    localStorage.setItem(driversKey, JSON.stringify(drivers));
  }

  function getSchedule() {
    const seeded = localStorage.getItem(scheduleSeedKey);
    const stored = localStorage.getItem(scheduleKey);
    if (!stored || seeded !== blankSeedVersion) {
      const drivers = getDrivers();
      const freshSchedule = createBlankWeekSchedule(drivers);
      localStorage.setItem(scheduleKey, JSON.stringify(freshSchedule));
      localStorage.setItem(scheduleSeedKey, blankSeedVersion);
      return freshSchedule;
    }
    return JSON.parse(stored);
  }

  function saveSchedule(schedule) {
    localStorage.setItem(scheduleKey, JSON.stringify(schedule));
  }

  function statusClass(status) {
    return (status || 'Available').toLowerCase().replace(/\s+/g, '-');
  }

  function normalizeDriverName(value) {
    return String(value || '').trim().toLowerCase().replace(/\s+/g, ' ');
  }

  function formatBoardDateTime(value) {
    if (!value) return '';
    const date = new Date(value.replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) return '';
    return formatTimeLabel(`${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`);
  }

  function normalizeBoardStatusForEditor(value) {
    const status = String(value || '').trim().toLowerCase();
    if (status === 'on trip' || status === 'approved' || status === 'completed') {
      return 'On Trip';
    }
    if (status === 'off duty') {
      return 'Off Duty';
    }
    if (status === 'leave') {
      return 'Leave';
    }
    return 'Available';
  }

  function reservationClientLabel(reservation) {
    const email = String(reservation.oc_email || '').trim();
    if (!email) return 'Walk-in Request';

    const namePart = email.includes('@') ? email.split('@')[0] : email;
    if (!namePart) return email;

    return namePart
      .replace(/[._-]+/g, ' ')
      .replace(/\s+/g, ' ')
      .trim()
      .split(' ')
      .map(part => part ? part.charAt(0).toUpperCase() + part.slice(1) : '')
      .join(' ');
  }

  function buildReservationOverlay(drivers, reservations) {
    const grouped = {};

    reservations.forEach((reservation) => {
      const driverName = normalizeDriverName(reservation.driver || '');
      const reservationDate = String(reservation.reservation_datetime || '').slice(0, 10);
      if (!driverName || !reservationDate) return;

      const driver = drivers.find((entry) => normalizeDriverName(entry.name) === driverName);
      if (!driver || !driver.plate) return;

      const key = `${driver.plate}::${reservationDate}`;
      if (!grouped[key]) {
        grouped[key] = {
          driver,
          date: reservationDate,
          items: [],
        };
      }
      grouped[key].items.push(reservation);
    });

    const overlay = {};

    Object.values(grouped).forEach((group) => {
      const { driver, date, items } = group;
      const sorted = items.slice().sort((a, b) => String(a.reservation_datetime || '').localeCompare(String(b.reservation_datetime || '')));
      const first = sorted[0] || {};
      const firstDestination = String(first.destination || first.other_destination || '').trim();
      const taskLabel = firstDestination || 'Approved reservation';

      const notes = sorted.map((item) => {
        const client = reservationClientLabel(item);
        const purpose = String(item.purpose || '').trim();
        return [client, purpose].filter(Boolean).join(' • ') || 'No purpose provided';
      }).join(' | ');

      if (!overlay[driver.plate]) {
        overlay[driver.plate] = {};
      }

      overlay[driver.plate][date] = {
        status: 'On Trip',
        time: String(first.reservation_datetime || '').slice(11, 16),
        task: taskLabel,
        notes,
        source: 'reservation',
        count: sorted.length,
        vehicle: String(first.vehicle || driver.vehicle || ''),
        reservationCode: String(first.reservation_code || ''),
      };
    });

    return overlay;
  }

  function buildDefaultScheduleEntry(driver, dateKey) {
    const defaultStatus = getDefaultStatusForDriverOnDate(driver, dateKey);
    return {
      status: defaultStatus,
      time: defaultStatus === 'Off Duty' ? 'Offset' : (defaultStatus === 'Leave' ? 'Leave' : ''),
      task: '',
      notes: '',
      manualOverride: false,
      source: '',
      reservationCode: ''
    };
  }

  function syncReservationSchedule(drivers, schedule, reservations) {
    const overlay = buildReservationOverlay(drivers, reservations);

    drivers.forEach((driver) => {
      const plate = (driver.plate || '').trim();
      if (!plate) return;

      if (!schedule[plate]) {
        schedule[plate] = {};
      }

      weekDates.forEach((day) => {
        const existingCell = schedule[plate][day.date];
        const reservationCell = overlay[plate]?.[day.date] || null;

        if (reservationCell) {
          const sameReservation =
            existingCell &&
            existingCell.manualOverride &&
            String(existingCell.reservationCode || '') !== '' &&
            String(existingCell.reservationCode || '') === String(reservationCell.reservationCode || '');

          if (!existingCell || !existingCell.manualOverride || !sameReservation) {
            schedule[plate][day.date] = {
              ...reservationCell,
              manualOverride: false
            };
          }
          return;
        }

        if (existingCell && existingCell.source === 'reservation' && !existingCell.manualOverride) {
          schedule[plate][day.date] = buildDefaultScheduleEntry(driver, day.date);
        } else if (!existingCell) {
          schedule[plate][day.date] = buildDefaultScheduleEntry(driver, day.date);
        }
      });
    });

    return schedule;
  }

  function defaultCellForDriver(driver) {
    return {
      status: driver.status || 'Available',
      time: '',
      task: '',
      notes: ''
    };
  }

  function normalizeTimeInput(value) {
    const raw = String(value || '').trim();
    if (!raw) return '';

    if (/^\d{2}:\d{2}$/.test(raw)) {
      return raw;
    }

    const match = raw.match(/^(\d{1,2})(?::(\d{2}))?\s*(AM|PM)$/i);
    if (!match) return raw;

    let hour = parseInt(match[1], 10);
    const minute = match[2] || '00';
    const meridiem = match[3].toUpperCase();

    if (meridiem === 'PM' && hour < 12) hour += 12;
    if (meridiem === 'AM' && hour === 12) hour = 0;

    return `${String(hour).padStart(2, '0')}:${minute}`;
  }

  function formatTimeLabel(value) {
    const normalized = normalizeTimeInput(value);
    if (!normalized || !/^\d{2}:\d{2}$/.test(normalized)) {
      return value || '';
    }

    const [hours, minutes] = normalized.split(':').map(Number);
    const period = hours >= 12 ? 'PM' : 'AM';
    const displayHour = hours % 12 || 12;
    return `${displayHour}:${String(minutes).padStart(2, '0')} ${period}`;
  }

  function getCellData(schedule, driver, dateKey) {
    const plate = driver.plate || '';
    const plateSchedule = schedule[plate] || {};
    if (plateSchedule[dateKey] && plateSchedule[dateKey].manualOverride) {
      return {
        ...plateSchedule[dateKey],
        time: normalizeTimeInput(plateSchedule[dateKey].time || '')
      };
    }

    if (plateSchedule[dateKey]) {
      return {
        ...plateSchedule[dateKey],
        time: normalizeTimeInput(plateSchedule[dateKey].time || '')
      };
    }

    const defaultStatus = getDefaultStatusForDriverOnDate(driver, dateKey);
    return {
      status: defaultStatus,
      time: '',
      task: '',
      notes: ''
    };
  }

  function renderDetails(driver, dayLabel, cell) {
    detailsPanel.innerHTML = `
      <h6>${driver.name} - ${dayLabel}</h6>
      <div><strong>Plate:</strong> ${driver.plate || 'N/A'}</div>
      <div><strong>Vehicle:</strong> ${driver.vehicle || 'N/A'}</div>
      <div><strong>Status:</strong> ${cell.status || 'Available'}</div>
      <div><strong>Date:</strong> ${cell.dateLabel || dayLabel}</div>
      <div><strong>Time:</strong> ${formatTimeLabel(cell.time) || 'Not set'}</div>
      <div><strong>Assignment:</strong> ${cell.task || 'No assignment yet'}</div>
      ${cell.source === 'reservation' ? `<div><strong>Source:</strong> Approved reservation${cell.count > 1 ? ` (${cell.count})` : ''}</div>` : ''}
      <div><strong>Notes:</strong> ${cell.notes || 'None'}</div>
      ${cell.manualOverride ? '<div><strong>Edited:</strong> Manual override saved</div>' : ''}
    `;
  }

  function renderBoard() {
    const drivers = getDrivers();
    const schedule = syncReservationSchedule(getDrivers(), getSchedule(), boardReservations);
    boardBody.innerHTML = '';

    drivers.forEach((driver, index) => {
      const row = document.createElement('tr');

      const driverCell = `
        <td class="board-driver-cell">
          <div class="board-driver-name">${driver.name}</div>
          <div class="board-driver-phone">${driver.phone || ''}</div>
          <div class="board-driver-actions">
            <button class="board-driver-action" type="button" data-edit-driver="${index}">Edit</button>
            <button class="board-driver-action delete" type="button" data-delete-driver="${index}">Delete</button>
          </div>
        </td>
      `;

      const plateCell = `
        <td class="board-vehicle-cell">
          <div class="board-plate">${driver.plate || ''}</div>
          <div class="board-vehicle">${driver.vehicle || ''}</div>
          <div class="board-registration">${driver.registration || ''}</div>
        </td>
      `;

      const dayCells = weekDates.map(day => {
        const cell = getCellData(schedule, driver, day.date);
        const timeHtml = cell.time ? `<div class="board-slot-time">${formatTimeLabel(cell.time)}</div>` : '';
        const taskHtml = cell.task ? `<div class="board-slot-task">${cell.task}</div>` : `<div class="board-slot-empty">${cell.status}</div>`;
        return `
          <td class="board-slot ${statusClass(cell.status)} ${cell.source === 'reservation' ? 'board-slot-linked' : ''}" data-driver-index="${index}" data-date="${day.date}">
            ${timeHtml}
            ${taskHtml}
          </td>
        `;
      }).join('');

      row.innerHTML = driverCell + plateCell + dayCells;
      boardBody.appendChild(row);
    });
  }

  document.getElementById('add-driver-btn').addEventListener('click', () => {
    document.getElementById('driverForm').reset();
    document.getElementById('driverIndex').value = '';
    document.getElementById('driverModalTitle').textContent = 'Add Driver';
    document.getElementById('driverStatusGroup').classList.add('d-none');
    driverModal.show();
  });

  document.getElementById('saveDriver').addEventListener('click', () => {
    const name = document.getElementById('driverName').value.trim();
    const phone = document.getElementById('driverPhone').value.trim();
    const vehicle = document.getElementById('driverVehicle').value.trim();
    const plate = document.getElementById('driverPlate').value.trim();
    const registration = document.getElementById('driverRegistration').value.trim();
    const idx = document.getElementById('driverIndex').value;
    const selectedStatus = document.getElementById('driverStatus').value;

    if (!name || !vehicle || !plate || !registration) return;

    const drivers = getDrivers();
    const existingStatus = idx === '' ? 'Available' : (drivers[parseInt(idx, 10)]?.status || 'Available');
    const finalStatus = idx === '' ? 'Available' : selectedStatus || existingStatus;
    const driver = { name, phone, vehicle, plate, registration, status: finalStatus };

    if (idx === '') {
      drivers.push(driver);
    } else {
      drivers[parseInt(idx, 10)] = driver;
    }

    saveDrivers(drivers);
    renderBoard();
    driverModal.hide();
  });

  boardBody.addEventListener('click', event => {
    const editBtn = event.target.closest('[data-edit-driver]');
    if (editBtn) {
      const idx = parseInt(editBtn.dataset.editDriver, 10);
      const drivers = getDrivers();
      const driver = drivers[idx];
      document.getElementById('driverIndex').value = idx;
      document.getElementById('driverName').value = driver.name;
      document.getElementById('driverPhone').value = driver.phone;
      document.getElementById('driverVehicle').value = driver.vehicle;
      document.getElementById('driverPlate').value = driver.plate;
      document.getElementById('driverRegistration').value = driver.registration;
      document.getElementById('driverStatus').value = driver.status;
      document.getElementById('driverModalTitle').textContent = 'Edit Driver';
      document.getElementById('driverStatusGroup').classList.remove('d-none');
      driverModal.show();
      return;
    }

    const deleteBtn = event.target.closest('[data-delete-driver]');
    if (deleteBtn) {
      const idx = parseInt(deleteBtn.dataset.deleteDriver, 10);
      if (!confirm('Delete this driver?')) return;
      const drivers = getDrivers();
      const schedule = getSchedule();
      const removed = drivers.splice(idx, 1)[0];
      if (removed && removed.plate && schedule[removed.plate]) {
        delete schedule[removed.plate];
        saveSchedule(schedule);
      }
      saveDrivers(drivers);
      renderBoard();
      return;
    }

    const slot = event.target.closest('.board-slot');
    if (!slot) return;

    const driverIndex = parseInt(slot.dataset.driverIndex, 10);
    const dateKey = slot.dataset.date;
    const drivers = getDrivers();
    const schedule = syncReservationSchedule(getDrivers(), getSchedule(), boardReservations);
    const driver = drivers[driverIndex];
    const dayInfo = weekDates.find(day => day.date === dateKey);
    const cell = getCellData(schedule, driver, dateKey);

    document.getElementById('scheduleDriverIndex').value = String(driverIndex);
    document.getElementById('scheduleDateKey').value = dateKey;
    document.getElementById('scheduleDriverName').value = `${driver.name} (${driver.plate})`;
    document.getElementById('scheduleDateLabel').value = dateKey;
    document.getElementById('scheduleStatus').value = normalizeBoardStatusForEditor(cell.status || 'Available');
    document.getElementById('scheduleTime').value = normalizeTimeInput(cell.time || '');
    document.getElementById('scheduleTask').value = cell.task || '';
    document.getElementById('scheduleNotes').value = cell.notes || '';

    renderDetails(driver, dayInfo ? dayInfo.label : dateKey, { ...cell, dateLabel: dayInfo ? dayInfo.display : dateKey });
    scheduleModal.show();
  });

  document.getElementById('saveSchedule').addEventListener('click', () => {
    const driverIndex = parseInt(document.getElementById('scheduleDriverIndex').value, 10);
    const dateKey = document.getElementById('scheduleDateKey').value;
    const status = document.getElementById('scheduleStatus').value;
    const time = normalizeTimeInput(document.getElementById('scheduleTime').value.trim());
    const task = document.getElementById('scheduleTask').value.trim();
    const notes = document.getElementById('scheduleNotes').value.trim();

    const drivers = getDrivers();
    const schedule = syncReservationSchedule(getDrivers(), getSchedule(), boardReservations);
    const driver = drivers[driverIndex];
    if (!driver) return;

    if (!schedule[driver.plate]) {
      schedule[driver.plate] = {};
    }

    const currentCell = getCellData(schedule, driver, dateKey);

    schedule[driver.plate][dateKey] = {
      status,
      time,
      task,
      notes,
      manualOverride: true,
      source: currentCell.source || '',
      reservationCode: currentCell.reservationCode || ''
    };
    saveSchedule(schedule);
    renderBoard();
    const activeDay = weekDates.find(day => day.date === dateKey);
    renderDetails(driver, activeDay?.label || dateKey, { ...schedule[driver.plate][dateKey], dateLabel: activeDay?.display || dateKey });
    scheduleModal.hide();
  });

  document.getElementById('clearSchedule').addEventListener('click', () => {
    const driverIndex = parseInt(document.getElementById('scheduleDriverIndex').value, 10);
    const dateKey = document.getElementById('scheduleDateKey').value;
    const drivers = getDrivers();
    const schedule = syncReservationSchedule(getDrivers(), getSchedule(), boardReservations);
    const driver = drivers[driverIndex];
    if (!driver) return;

    const currentCell = getCellData(schedule, driver, dateKey);
    const fallbackStatus = getDefaultStatusForDriverOnDate(driver, dateKey);

    if (!schedule[driver.plate]) {
      schedule[driver.plate] = {};
    }

    schedule[driver.plate][dateKey] = {
      status: currentCell.source === 'reservation' ? fallbackStatus : fallbackStatus,
      time: '',
      task: '',
      notes: '',
      manualOverride: true,
      source: currentCell.source === 'reservation' ? 'reservation-cleared' : '',
      reservationCode: currentCell.reservationCode || ''
    };

    saveSchedule(schedule);
    renderBoard();
    detailsPanel.innerHTML = `
      <h6>Schedule Details</h6>
      <div class="text-muted">Schedule entry cleared. Click a cell to view details again.</div>
    `;
    scheduleModal.hide();
  });

  async function refreshBoardReservations() {
    if (isBoardRefreshing || document.hidden) return;
    isBoardRefreshing = true;

    try {
      const weekResponse = await fetch(`${weekFeedUrl}&t=${Date.now()}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        cache: 'no-store'
      });

      if (!weekResponse.ok) return;

      const weekPayload = await weekResponse.json();
      boardReservations = Array.isArray(weekPayload.reservations) ? weekPayload.reservations : [];
      const syncedSchedule = syncReservationSchedule(getDrivers(), getSchedule(), boardReservations);
      saveSchedule(syncedSchedule);
      renderBoard();
    } catch (error) {
      console.warn('Could not refresh driver board reservations.', error);
    } finally {
      isBoardRefreshing = false;
    }
  }

  renderBoard();
  refreshBoardReservations();
  setInterval(refreshBoardReservations, 5000);
  window.addEventListener('storage', (event) => {
    if (event.key === reservationSyncKey) {
      refreshBoardReservations();
    }
  });
  window.addEventListener('focus', refreshBoardReservations);
</script>
