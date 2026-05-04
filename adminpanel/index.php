<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/reservations.php';

$current = 'dashboard';
$page_title = 'Dashboard';

$dashboardPayload = reservationDashboardPayload($pdo);
$pendingCount = $dashboardPayload['summary']['pending_count'];
$todayCount = $dashboardPayload['summary']['today_count'];
$approvedCount = $dashboardPayload['summary']['approved_count'];
$completedCount = $dashboardPayload['summary']['completed_count'];
$weeklyLabels = $dashboardPayload['weekly_labels'];
$weeklySeries = $dashboardPayload['weekly_series'];
$statusLabels = $dashboardPayload['status_labels'];
$statusData = $dashboardPayload['status_data'];

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="content">
  <div class="topbar mb-4">
    <div>
      <div class="text-muted text-uppercase" style="font-size:11px; letter-spacing:1px;">Pages / Dashboard</div>
      <div class="fw-bold" style="font-size:18px;">Dashboard</div>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card card-shadow stat-card p-3">
        <div class="text-muted">Pending Approvals</div>
        <h2 id="pendingCount"><?php echo $pendingCount; ?></h2>
        <small class="text-success">updated live</small>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card card-shadow stat-card p-3">
        <div class="text-muted">Today's Appointments</div>
        <h2 id="todayCount"><?php echo $todayCount; ?></h2>
        <small class="text-muted">today</small>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card card-shadow stat-card p-3">
        <div class="text-muted">Approved Trips</div>
        <h2 id="approvedCount"><?php echo $approvedCount; ?></h2>
        <small class="text-muted">confirmed reservations</small>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card card-shadow stat-card p-3">
        <div class="text-muted">Completed Trips</div>
        <h2 id="completedCount"><?php echo $completedCount; ?></h2>
        <small class="text-muted">finished reservations</small>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card card-shadow p-3">
        <div class="d-flex align-items-center justify-content-between">
          <div class="fw-semibold">Booking Trends</div>
          <span class="badge-pill">Last 4 weeks</span>
        </div>
        <canvas id="trendChart" height="120"></canvas>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card card-shadow p-3 h-100">
        <div class="d-flex align-items-center justify-content-between">
          <div class="fw-semibold">Booking Status</div>
          <span class="badge-pill">All time</span>
        </div>
        <canvas id="statusChart" height="180"></canvas>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
  const ctx = document.getElementById('trendChart');
  let trendChart = null;
  if (ctx) {
    trendChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: <?php echo json_encode($weeklyLabels); ?>,
        datasets: [
          {
            label: 'Pending',
            data: <?php echo json_encode($weeklySeries['pending']); ?>,
            borderColor: '#f6c23e',
            backgroundColor: 'rgba(246,194,62,0.12)',
            fill: true,
            tension: 0.4,
            pointRadius: 3
          },
          {
            label: 'Approved',
            data: <?php echo json_encode($weeklySeries['approved']); ?>,
            borderColor: '#1cc88a',
            backgroundColor: 'rgba(28,200,138,0.12)',
            fill: true,
            tension: 0.4,
            pointRadius: 3
          },
          {
            label: 'Completed',
            data: <?php echo json_encode($weeklySeries['completed']); ?>,
            borderColor: '#4e73df',
            backgroundColor: 'rgba(78,115,223,0.12)',
            fill: true,
            tension: 0.4,
            pointRadius: 3
          }
        ]
      },
      options: {
        plugins: { legend: { position: 'bottom' } },
        scales: { y: { beginAtZero: true } }
      }
    });
  }

  const statusCtx = document.getElementById('statusChart');
  let statusChart = null;
  if (statusCtx) {
    statusChart = new Chart(statusCtx, {
      type: 'bar',
      data: {
        labels: <?php echo json_encode($statusLabels); ?>,
        datasets: [{
          data: <?php echo json_encode($statusData); ?>,
          backgroundColor: ['#f6c23e', '#1cc88a', '#4e73df']
        }]
      },
      options: {
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
      }
    });
  }

  const reportBtn = document.getElementById('generate-report');
  if (reportBtn) {
    reportBtn.addEventListener('click', () => {
      alert('Report generated successfully (demo).');
    });
  }

  (() => {
    let refreshing = false;

    async function refreshDashboard() {
      if (refreshing || document.hidden) return;
      refreshing = true;

      try {
        const response = await fetch(`api/dashboard-stats.php?t=${Date.now()}`, {
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          cache: 'no-store'
        });

        if (!response.ok) return;

        const payload = await response.json();
        const summary = payload.summary || {};

        const pendingEl = document.getElementById('pendingCount');
        const todayEl = document.getElementById('todayCount');
        const approvedEl = document.getElementById('approvedCount');
        const completedEl = document.getElementById('completedCount');

        if (pendingEl) pendingEl.textContent = summary.pending_count ?? 0;
        if (todayEl) todayEl.textContent = summary.today_count ?? 0;
        if (approvedEl) approvedEl.textContent = summary.approved_count ?? 0;
        if (completedEl) completedEl.textContent = summary.completed_count ?? 0;

        if (trendChart && payload.weekly_labels && payload.weekly_series) {
          trendChart.data.labels = payload.weekly_labels;
          trendChart.data.datasets[0].data = payload.weekly_series.pending || [];
          trendChart.data.datasets[1].data = payload.weekly_series.approved || [];
          trendChart.data.datasets[2].data = payload.weekly_series.completed || [];
          trendChart.update();
        }

        if (statusChart && payload.status_labels && payload.status_data) {
          statusChart.data.labels = payload.status_labels;
          statusChart.data.datasets[0].data = payload.status_data;
          statusChart.update();
        }
      } catch (error) {
        console.warn('Dashboard live refresh failed.', error);
      } finally {
        refreshing = false;
      }
    }

    setInterval(refreshDashboard, 5000);
  })();
</script>
