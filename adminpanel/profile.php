<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/admins.php';

$current = 'profile';
$page_title = 'Profile';

$message = '';
$error = '';
$admin = fetchAdminById($pdo, (int) $_SESSION['admin_id']);
if (!$admin) {
    header('Location: logout.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = trim($_POST['role'] ?? 'Administrator');

    if ($name === '' || $email === '') {
        $error = 'Full name and email are required.';
    } else {
        $stmt = $pdo->prepare(
            'UPDATE admins
             SET full_name = :full_name, email = :email, phone = :phone, role = :role, updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            'full_name' => $name,
            'email' => $email,
            'phone' => $phone !== '' ? $phone : null,
            'role' => $role !== '' ? $role : 'Administrator',
            'id' => (int) $admin['id'],
        ]);

        $admin = fetchAdminById($pdo, (int) $admin['id']);
        syncAdminSession($admin);
        $message = 'Profile updated successfully.';
    }
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>
<div class="content">
  <div class="settings-header mb-4">
    <h2>Profile</h2>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>
  <?php if ($message): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
  <?php endif; ?>

  <div class="profile-card mb-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <div>
        <h4 class="mb-1">Profile</h4>
        <div class="text-muted">Manage your personal information</div>
      </div>
    </div>
    <div class="d-flex align-items-center gap-3 mb-4">
      <div class="avatar-circle"><?php echo htmlspecialchars(substr($_SESSION['admin_name'], 0, 1)); ?></div>
      <div>
        <button class="btn btn-outline-secondary btn-sm" type="button">Change Photo</button>
        <div class="text-muted mt-1" style="font-size: 12px;">JPG, PNG or GIF. Max 2MB.</div>
      </div>
    </div>
    <form method="post" class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Full Name</label>
        <input type="text" name="name" class="form-control soft-input" value="<?php echo htmlspecialchars((string) ($_SESSION['admin_name'] ?? adminDisplayName($admin))); ?>" />
      </div>
      <div class="col-md-6">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control soft-input" value="<?php echo htmlspecialchars((string) ($_SESSION['admin_email'] ?? '')); ?>" />
      </div>
      <div class="col-md-6">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control soft-input" value="<?php echo htmlspecialchars((string) ($_SESSION['admin_phone'] ?? '')); ?>" />
      </div>
      <div class="col-md-6">
        <label class="form-label">Role</label>
        <input type="text" name="role" class="form-control soft-input" value="<?php echo htmlspecialchars((string) ($_SESSION['admin_role'] ?? 'Administrator')); ?>" />
      </div>
      <div class="col-12">
        <button class="btn btn-danger" type="submit">Save Changes</button>
      </div>
    </form>
  </div>

  <div class="profile-card mb-4">
    <div class="d-flex align-items-center justify-content-between mb-2">
      <div>
        <h4 class="mb-1">Security</h4>
        <div class="text-muted">Manage your account security</div>
      </div>
    </div>
    <div class="d-flex align-items-center justify-content-between p-3 rounded" style="background:#f3eee7;">
      <div>
        <div class="fw-semibold">Password</div>
        <div class="text-muted">Reset your admin password on localhost when needed.</div>
      </div>
      <a class="btn btn-outline-secondary" href="forgot_password.php">Change Password</a>
    </div>
  </div>

</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
