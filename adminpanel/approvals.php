<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/reservations.php';

$current = 'approvals';
$page_title = 'Reservation Approvals';

$flash = null;

ensureReservationsSchema($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'update' && $id > 0) {
        $status = trim($_POST['status'] ?? 'Pending');
        $vehicle = trim($_POST['vehicle'] ?? '');
        $driver = trim($_POST['driver'] ?? '');
        $adminNote = trim($_POST['admin_note'] ?? '');
        $allowedStatuses = ['Pending', 'Approved', 'Cancelled', 'Completed'];

        if (in_array($status, $allowedStatuses, true)) {
            if ($status === 'Cancelled' && $adminNote === '') {
                $flash = ['type' => 'danger', 'message' => 'Please add a cancellation reason before saving.'];
            } else {
                $stmt = $pdo->prepare(
                    'UPDATE reservations
                     SET status = :status, vehicle = :vehicle, driver = :driver, admin_note = :admin_note
                     WHERE id = :id'
                );
                $stmt->execute([
                    'status' => $status,
                    'vehicle' => $vehicle !== '' ? $vehicle : null,
                    'driver' => $driver !== '' ? $driver : null,
                    'admin_note' => $adminNote !== '' ? $adminNote : null,
                    'id' => $id,
                ]);
                $flash = ['type' => 'success', 'message' => 'Reservation updated successfully.'];
            }
        }
    } elseif ($action === 'archive' && $id > 0) {
        $stmt = $pdo->prepare('UPDATE reservations SET is_archived = 1 WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $flash = ['type' => 'success', 'message' => 'Reservation archived successfully.'];
    }
}

$reservations = fetchReservations($pdo);

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>
<div class="content">
  <div class="approvals-header mb-4">
    <h2>Reservation Approvals</h2>
    <p>Manage customer reservation requests from the user panel.</p>
  </div>

  <div class="approvals-toolbar mb-4">
    <div class="approvals-filter-bar" id="approvalsFilterBar">
      <button type="button" class="approvals-filter-btn active" data-filter-status="all">
        All
        <span class="approvals-filter-count" data-filter-count="all">0</span>
      </button>
      <button type="button" class="approvals-filter-btn" data-filter-status="pending">
        Pending
        <span class="approvals-filter-count" data-filter-count="pending">0</span>
      </button>
      <button type="button" class="approvals-filter-btn" data-filter-status="approved">
        Approved
        <span class="approvals-filter-count" data-filter-count="approved">0</span>
      </button>
      <button type="button" class="approvals-filter-btn" data-filter-status="cancelled">
        Cancelled
        <span class="approvals-filter-count" data-filter-count="cancelled">0</span>
      </button>
      <button type="button" class="approvals-filter-btn" data-filter-status="completed">
        Completed
        <span class="approvals-filter-count" data-filter-count="completed">0</span>
      </button>
    </div>

    <div class="approvals-toolbar-actions">
      <a href="archive.php" class="approvals-archive-link">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="M3 7h18"></path>
          <path d="M5 7l1 12h12l1-12"></path>
          <path d="M9 11v4"></path>
          <path d="M15 11v4"></path>
          <path d="M9 3h6l1 4H8l1-4z"></path>
        </svg>
        Archive
      </a>

      <label class="approvals-search" for="approvalsSearchInput">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="M10.5 4a6.5 6.5 0 1 0 4.09 11.56l4.42 4.42 1.41-1.41-4.42-4.42A6.5 6.5 0 0 0 10.5 4Zm0 2a4.5 4.5 0 1 1 0 9 4.5 4.5 0 0 1 0-9Z" fill="currentColor"/>
        </svg>
        <input
          type="search"
          id="approvalsSearchInput"
          class="approvals-search-input"
          placeholder="Search reservation code, requester, destination, vehicle, or driver"
          autocomplete="off"
        >
      </label>
    </div>
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-<?php echo htmlspecialchars((string) ($flash['type'] ?? 'success')); ?>"><?php echo htmlspecialchars($flash['message']); ?></div>
  <?php endif; ?>

  <section class="approvals-status-section">
    <div class="approvals-section-head">
      <h3 id="approvalsSectionTitle">All Reservations</h3>
      <span class="approvals-section-count" id="allReservationsCount"><?php echo count($reservations); ?></span>
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
            <th class="text-end" style="width: 150px;">Actions</th>
          </tr>
        </thead>
        <tbody id="approvals-body"><?php echo renderAdminReservationRows($reservations); ?></tbody>
      </table>
    </div>
  </section>
</div>

<div class="approval-details-modal" id="approvalDetailsModal" aria-hidden="true">
  <div class="approval-details-dialog">
    <div class="approval-details-head">
      <div>
        <h3>Reservation Details</h3>
        <p>Review the full request before approving it.</p>
      </div>
      <button type="button" class="approval-details-close" id="approvalDetailsClose" aria-label="Close details">×</button>
    </div>
    <div class="approval-details-grid">
      <div class="approval-detail-card"><strong>Reservation Code</strong><span id="approvalDetailCode"></span></div>
      <div class="approval-detail-card"><strong>Client</strong><span id="approvalDetailClient"></span></div>
      <div class="approval-detail-card"><strong>Email</strong><span id="approvalDetailEmail"></span></div>
      <div class="approval-detail-card"><strong>Department</strong><span id="approvalDetailDepartment"></span></div>
      <div class="approval-detail-card"><strong>Date</strong><span id="approvalDetailDate"></span></div>
      <div class="approval-detail-card"><strong>Time</strong><span id="approvalDetailTime"></span></div>
      <div class="approval-detail-card"><strong>Service</strong><span id="approvalDetailService"></span></div>
      <div class="approval-detail-card"><strong>Status</strong><span id="approvalDetailStatus"></span></div>
      <div class="approval-detail-card"><strong>Destination</strong><span id="approvalDetailDestination"></span></div>
      <div class="approval-detail-card"><strong>Other Destination</strong><span id="approvalDetailOtherDestination"></span></div>
      <div class="approval-detail-card"><strong>Passengers</strong><span id="approvalDetailPassengers"></span></div>
      <div class="approval-detail-card"><strong>Travel Time</strong><span id="approvalDetailTravelTime"></span></div>
      <div class="approval-detail-card"><strong>Waiting Time</strong><span id="approvalDetailWaitingTime"></span></div>
      <div class="approval-detail-card"><strong>Vehicle</strong><span id="approvalDetailVehicle"></span></div>
      <div class="approval-detail-card"><strong>Driver</strong><span id="approvalDetailDriver"></span></div>
      <div class="approval-detail-card approval-detail-card--wide"><strong>Admin Note / Reason</strong><span id="approvalDetailAdminNote"></span></div>
      <div class="approval-detail-card approval-detail-card--wide"><strong>Purpose / Notes</strong><span id="approvalDetailPurpose"></span></div>
      <div class="approval-detail-card approval-detail-card--wide"><strong>Loads</strong><span id="approvalDetailLoads"></span></div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
<?php if ($flash && ($flash['type'] ?? '') === 'success'): ?>
<script>
  localStorage.setItem('dispatch-reservations-updated-at', String(Date.now()));
</script>
<?php endif; ?>
<script>
  (() => {
    const approvalsBody = document.getElementById('approvals-body');
    if (!approvalsBody) return;
    const allReservationsCount = document.getElementById('allReservationsCount');
    const approvalsSectionTitle = document.getElementById('approvalsSectionTitle');
    const filterBar = document.getElementById('approvalsFilterBar');
    const searchInput = document.getElementById('approvalsSearchInput');
    const dispatchDriversKey = 'dispatch-drivers';
    const detailsModal = document.getElementById('approvalDetailsModal');
    const detailsClose = document.getElementById('approvalDetailsClose');
    const detailFields = {
      code: document.getElementById('approvalDetailCode'),
      client: document.getElementById('approvalDetailClient'),
      email: document.getElementById('approvalDetailEmail'),
      department: document.getElementById('approvalDetailDepartment'),
      date: document.getElementById('approvalDetailDate'),
      time: document.getElementById('approvalDetailTime'),
      service: document.getElementById('approvalDetailService'),
      status: document.getElementById('approvalDetailStatus'),
      destination: document.getElementById('approvalDetailDestination'),
      otherDestination: document.getElementById('approvalDetailOtherDestination'),
      passengers: document.getElementById('approvalDetailPassengers'),
      travelTime: document.getElementById('approvalDetailTravelTime'),
      waitingTime: document.getElementById('approvalDetailWaitingTime'),
      vehicle: document.getElementById('approvalDetailVehicle'),
      driver: document.getElementById('approvalDetailDriver'),
      adminNote: document.getElementById('approvalDetailAdminNote'),
      purpose: document.getElementById('approvalDetailPurpose'),
      loads: document.getElementById('approvalDetailLoads'),
    };

    let lastMarkup = approvalsBody.innerHTML.trim();
    let isRefreshing = false;
    let activeFilter = 'all';
    let searchQuery = '';

    function getDriverRoster() {
      try {
        const parsed = JSON.parse(localStorage.getItem(dispatchDriversKey) || '[]');
        return Array.isArray(parsed) ? parsed : [];
      } catch (error) {
        console.warn('Could not read driver roster from local storage.', error);
        return [];
      }
    }

    function wireDriverSelects(scope = approvalsBody) {
      const drivers = getDriverRoster();
      scope.querySelectorAll('[data-driver-select]').forEach((select) => {
        const current = select.dataset.currentDriver || select.value || '';
        const form = select.closest('form');
        const vehicleDisplay = form ? form.querySelector('[data-vehicle-display]') : null;
        const vehicleHidden = form ? form.querySelector('[data-vehicle-hidden]') : null;

        select.innerHTML = '<option value="">Driver</option>';

        drivers.forEach((driver) => {
          const option = document.createElement('option');
          option.value = driver.name || '';
          option.textContent = driver.name || '';
          option.dataset.vehicle = driver.vehicle || '';
          if (option.value === current) {
            option.selected = true;
          }
          select.appendChild(option);
        });

        if (current && !Array.from(select.options).some((option) => option.value === current)) {
          const fallbackOption = document.createElement('option');
          fallbackOption.value = current;
          fallbackOption.textContent = current;
          fallbackOption.selected = true;
          select.appendChild(fallbackOption);
        }

        if (select.dataset.wired === 'true') return;

        select.addEventListener('change', () => {
          const selected = select.options[select.selectedIndex];
          const nextVehicle = selected && selected.dataset.vehicle ? selected.dataset.vehicle : '';
          if (vehicleDisplay) {
            vehicleDisplay.value = nextVehicle;
          }
          if (vehicleHidden) {
            vehicleHidden.value = nextVehicle;
          }
          select.dataset.currentDriver = select.value;
        });

        select.dataset.wired = 'true';
      });
    }

    function applyStatusSelectState(select) {
      select.classList.remove(
        'status-pending-live',
        'status-approved-live',
        'status-cancelled-live',
        'status-completed-live'
      );

      const value = (select.value || '').toLowerCase();
      if (value === 'approved') {
        select.classList.add('status-approved-live');
      } else if (value === 'cancelled' || value === 'canceled') {
        select.classList.add('status-cancelled-live');
      } else if (value === 'completed') {
        select.classList.add('status-completed-live');
      } else {
        select.classList.add('status-pending-live');
      }
    }

    function wireStatusSelects(scope = approvalsBody) {
      scope.querySelectorAll('[data-status-live]').forEach((select) => {
        applyStatusSelectState(select);

        if (select.dataset.wiredStatus === 'true') return;

        select.addEventListener('change', () => {
          applyStatusSelectState(select);
        });

        select.dataset.wiredStatus = 'true';
      });
    }

    function applyApprovalsFilter() {
      const rows = approvalsBody.querySelectorAll('tr[data-reservation-status]');
      const counts = {
        all: rows.length,
        pending: 0,
        approved: 0,
        cancelled: 0,
        completed: 0,
      };

      rows.forEach((row) => {
        const status = (row.dataset.reservationStatus || 'pending').toLowerCase();
        const rowSearch = (row.dataset.reservationSearch || row.textContent || '').toLowerCase();
        const matchesSearch = searchQuery === '' || rowSearch.includes(searchQuery);
        if (Object.prototype.hasOwnProperty.call(counts, status)) {
          counts[status] += 1;
        }
        const matchesStatus = activeFilter === 'all' || status === activeFilter;
        row.style.display = (matchesStatus && matchesSearch) ? '' : 'none';
      });

      Object.entries(counts).forEach(([key, value]) => {
        const counter = document.querySelector(`[data-filter-count="${key}"]`);
        if (counter) counter.textContent = String(value);
      });

      const titles = {
        all: 'All Reservations',
        pending: 'All Pending',
        approved: 'All Approved',
        cancelled: 'All Cancelled',
        completed: 'All Completed',
      };

      if (approvalsSectionTitle) {
        approvalsSectionTitle.textContent = titles[activeFilter] || 'All Reservations';
      }

      if (allReservationsCount) {
        const nextCount = activeFilter === 'all' ? counts.all : (counts[activeFilter] ?? 0);
        allReservationsCount.textContent = String(nextCount);
      }

      if (filterBar) {
        filterBar.querySelectorAll('[data-filter-status]').forEach((button) => {
          button.classList.toggle('active', button.dataset.filterStatus === activeFilter);
        });
      }
    }

    async function refreshApprovals() {
      if (isRefreshing || document.hidden) return;
      isRefreshing = true;

        try {
        const response = await fetch(`api/admin-reservations.php?t=${Date.now()}`, {
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          cache: 'no-store'
        });

        if (!response.ok) {
          return;
        }

        const payload = await response.json();
        const nextMarkup = (payload.html || '').trim();

        if (nextMarkup && nextMarkup !== lastMarkup) {
          approvalsBody.innerHTML = payload.html;
          lastMarkup = nextMarkup;
          wireDriverSelects();
          wireStatusSelects();
          applyApprovalsFilter();
        }
      } catch (error) {
        console.warn('Live approval refresh failed.', error);
      } finally {
        isRefreshing = false;
      }
    }

    wireDriverSelects();
    wireStatusSelects();
    applyApprovalsFilter();
    setInterval(refreshApprovals, 5000);

    approvalsBody.addEventListener('submit', (event) => {
      if (event.target.closest('.approvals-actions-form')) {
        localStorage.setItem('dispatch-reservations-updated-at', String(Date.now()));
      }
    });

    if (filterBar) {
      filterBar.addEventListener('click', (event) => {
        const button = event.target.closest('[data-filter-status]');
        if (!button) return;
        activeFilter = button.dataset.filterStatus || 'all';
        applyApprovalsFilter();
      });
    }

    if (searchInput) {
      searchInput.addEventListener('input', () => {
        searchQuery = (searchInput.value || '').trim().toLowerCase();
        applyApprovalsFilter();
      });
    }

    approvalsBody.addEventListener('click', (event) => {
      const trigger = event.target.closest('.open-approval-details');
      if (!trigger || !detailsModal) return;

      detailFields.code.textContent = trigger.dataset.code || '—';
      detailFields.client.textContent = trigger.dataset.client || '—';
      detailFields.email.textContent = trigger.dataset.email || '—';
      detailFields.department.textContent = trigger.dataset.department || '—';
      detailFields.date.textContent = trigger.dataset.date || '—';
      detailFields.time.textContent = trigger.dataset.time || '—';
      detailFields.service.textContent = trigger.dataset.service || '—';
      detailFields.status.textContent = trigger.dataset.status || '—';
      detailFields.destination.textContent = trigger.dataset.destination || '—';
      detailFields.otherDestination.textContent = trigger.dataset.otherDestination || 'None';
      detailFields.passengers.textContent = trigger.dataset.passengers || '—';
      detailFields.travelTime.textContent = trigger.dataset.travelTime || '—';
      detailFields.waitingTime.textContent = trigger.dataset.waitingTime || '—';
      detailFields.vehicle.textContent = trigger.dataset.vehicle || 'To be assigned';
      detailFields.driver.textContent = trigger.dataset.driver || 'To be assigned';
      detailFields.adminNote.textContent = trigger.dataset.adminNote || 'No admin note yet.';
      detailFields.purpose.textContent = trigger.dataset.purpose || trigger.dataset.notes || 'No notes';
      detailFields.loads.textContent = trigger.dataset.loads || 'None';

      detailsModal.classList.add('open');
      detailsModal.setAttribute('aria-hidden', 'false');
    });

    if (detailsClose && detailsModal) {
      detailsClose.addEventListener('click', () => {
        detailsModal.classList.remove('open');
        detailsModal.setAttribute('aria-hidden', 'true');
      });

      detailsModal.addEventListener('click', (event) => {
        if (event.target === detailsModal) {
          detailsModal.classList.remove('open');
          detailsModal.setAttribute('aria-hidden', 'true');
        }
      });
    }
  })();
</script>
