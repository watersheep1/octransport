<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/reservations.php';

$current = 'archive';
$page_title = 'Archived Reservations';
$flash = null;

ensureReservationsSchema($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'restore' && $id > 0) {
        $stmt = $pdo->prepare('UPDATE reservations SET is_archived = 0 WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $flash = ['type' => 'success', 'message' => 'Reservation restored successfully.'];
    } elseif ($action === 'delete' && $id > 0) {
        $stmt = $pdo->prepare('DELETE FROM reservations WHERE id = :id AND is_archived = 1');
        $stmt->execute(['id' => $id]);
        $flash = ['type' => 'success', 'message' => 'Archived reservation deleted permanently.'];
    }
}

$reservations = fetchArchivedReservations($pdo);

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>
<div class="content">
  <div class="approvals-header mb-4">
    <h2>Archived Reservations</h2>
    <p>View, search, and restore reservations that were removed from the active approvals list.</p>
  </div>

  <div class="approvals-toolbar mb-4">
    <a href="approvals.php" class="approvals-archive-link">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M15 18l-6-6 6-6"></path>
      </svg>
      Back
    </a>

    <label class="approvals-search" for="archiveSearchInput">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M10.5 4a6.5 6.5 0 1 0 4.09 11.56l4.42 4.42 1.41-1.41-4.42-4.42A6.5 6.5 0 0 0 10.5 4Zm0 2a4.5 4.5 0 1 1 0 9 4.5 4.5 0 0 1 0-9Z" fill="currentColor"/>
      </svg>
      <input
        type="search"
        id="archiveSearchInput"
        class="approvals-search-input"
        placeholder="Search archived reservation code, requester, destination, vehicle, or driver"
        autocomplete="off"
      >
    </label>
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-<?php echo htmlspecialchars((string) ($flash['type'] ?? 'success')); ?>"><?php echo htmlspecialchars($flash['message']); ?></div>
  <?php endif; ?>

  <section class="approvals-status-section">
    <div class="approvals-section-head">
      <h3 id="archiveSectionTitle">Archived Reservations</h3>
      <span class="approvals-section-count" id="archiveReservationsCount"><?php echo count($reservations); ?></span>
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
            <th>Record</th>
            <th class="text-end" style="width: 320px;">Actions</th>
          </tr>
        </thead>
        <tbody id="archive-body"><?php echo renderArchivedReservationRows($reservations); ?></tbody>
      </table>
    </div>
  </section>
</div>

<div class="approval-details-modal" id="approvalDetailsModal" aria-hidden="true">
  <div class="approval-details-dialog">
    <div class="approval-details-head">
      <div>
        <h3>Reservation Details</h3>
        <p>Review the full archived request.</p>
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
<script>
  (() => {
    const archiveBody = document.getElementById('archive-body');
    const searchInput = document.getElementById('archiveSearchInput');
    const archiveReservationsCount = document.getElementById('archiveReservationsCount');
    const detailsModal = document.getElementById('approvalDetailsModal');
    const detailsClose = document.getElementById('approvalDetailsClose');

    if (!archiveBody) return;

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

    function applyArchiveSearch() {
      const rows = archiveBody.querySelectorAll('tr[data-archive-search]');
      const query = ((searchInput && searchInput.value) || '').trim().toLowerCase();
      let visibleCount = 0;

      rows.forEach((row) => {
        const haystack = (row.dataset.archiveSearch || row.textContent || '').toLowerCase();
        const matches = query === '' || haystack.includes(query);
        row.style.display = matches ? '' : 'none';
        if (matches) visibleCount += 1;
      });

      if (archiveReservationsCount) {
        archiveReservationsCount.textContent = String(visibleCount);
      }
    }

    if (searchInput) {
      searchInput.addEventListener('input', applyArchiveSearch);
    }

    archiveBody.addEventListener('click', (event) => {
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

    applyArchiveSearch();
  })();
</script>
