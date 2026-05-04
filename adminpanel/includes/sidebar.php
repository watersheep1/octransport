<?php
$current = $current ?? '';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
function nav_item($label, $href, $key, $current, $icon) {
    $active = $current === $key ? 'active' : '';
    echo '<a class="nav-link ' . $active . '" href="' . $href . '">' . $icon . '<span>' . $label . '</span></a>';
}
$adminName = $_SESSION['admin_name'] ?? ($_SESSION['admin_username'] ?? 'Administrator');
$adminRole = $_SESSION['admin_role'] ?? 'Administrator';
$initial = strtoupper(substr($adminName, 0, 1));
?>
<aside class="sidebar">
  <div class="brand">
    <div class="brand-logo">
      <img src="logo/624125921_1375793547898072_4114286405174986642_n.png" alt="OCTRANSPO logo">
    </div>
    <div>
      <div>OCTRANSPO</div>
      <div class="brand-sub">Services</div>
    </div>
  </div>

  <div class="sidebar-sep"></div>

  <nav class="nav flex-column gap-1">
    <?php nav_item('Dashboard', 'index.php', 'dashboard', $current, '<span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="8" height="8" rx="2"></rect><rect x="13" y="3" width="8" height="8" rx="2"></rect><rect x="3" y="13" width="8" height="8" rx="2"></rect><rect x="13" y="13" width="8" height="8" rx="2"></rect></svg></span>'); ?>
    <?php nav_item('Approvals', 'approvals.php', 'approvals', $current, '<span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="3" width="16" height="18" rx="2"></rect><path d="M8 7h8M8 11h8M8 15h5"></path></svg></span>'); ?>
    <?php nav_item('Driver Management', 'users.php', 'users', $current, '<span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 11c1.7 0 3-1.3 3-3s-1.3-3-3-3-3 1.3-3 3 1.3 3 3 3z"></path><path d="M8 11c1.7 0 3-1.3 3-3S9.7 5 8 5 5 6.3 5 8s1.3 3 3 3z"></path><path d="M2 20c1.5-3 5-4 6-4"></path><path d="M12 16c1.8-.5 5 .5 6 4"></path></svg></span>'); ?>
    <?php nav_item('Settings', 'settings.php', 'settings', $current, '<span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"></circle><path d="M19 12a7 7 0 0 0-.1-1l2-1-2-3-2 1a7 7 0 0 0-1.7-1l-.3-2h-4l-.3 2a7 7 0 0 0-1.7 1l-2-1-2 3 2 1a7 7 0 0 0 0 2l-2 1 2 3 2-1a7 7 0 0 0 1.7 1l.3 2h4l.3-2a7 7 0 0 0 1.7-1l2 1 2-3-2-1a7 7 0 0 0 .1-1z"></path></svg></span>'); ?>
  </nav>

  <div class="sidebar-profile">
    <div class="profile-chip">
      <div class="profile-avatar"><?php echo htmlspecialchars($initial); ?></div>
      <div>
        <div style="color:#1f3d2d;"><?php echo htmlspecialchars($adminName); ?></div>
        <div class="brand-sub"><?php echo htmlspecialchars($adminRole); ?></div>
      </div>
    </div>
    <a class="logout-link" href="logout.php"><span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 4h6a2 2 0 0 1 2 2v3"></path><path d="M17 15v3a2 2 0 0 1-2 2H9"></path><path d="M5 12h11"></path><path d="M12 8l4 4-4 4"></path></svg></span>Logout</a>
  </div>
</aside>
