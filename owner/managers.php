<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('owner');

// Create manager
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $name  = trim($_POST['name'] ?? '');
    $user  = trim($_POST['username'] ?? '');
    $pass  = trim($_POST['password'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    if ($name && $user && $pass) {
        $check = $pdo->prepare("SELECT id FROM users WHERE username=?");
        $check->execute([$user]);
        if ($check->fetch()) {
            flash('error', 'Username already exists. Choose another.');
        } else {
            $pdo->prepare("INSERT INTO users (name,username,password,role,phone,created_by) VALUES (?,?,?,?,?,?)")
                ->execute([$name, $user, password_hash($pass, PASSWORD_DEFAULT), 'manager', $phone ?: null, $_SESSION['user_id']]);
            flash('success', "Manager account '$user' created!");
        }
    } else {
        flash('error', 'Name, username, and password are required.');
    }
    header('Location: managers.php'); exit;
}

// Toggle active
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $pdo->prepare("UPDATE users SET is_active = 1-is_active WHERE id=? AND role='manager'")->execute([$id]);
    flash('success', 'Account status updated.');
    header('Location: managers.php'); exit;
}

$managers = $pdo->query("SELECT * FROM users WHERE role='manager' ORDER BY created_at DESC")->fetchAll();
$pageTitle = 'Manager Accounts';
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">Manager Accounts</h5>
  <button class="btn btn-accent btn-lg" data-bs-toggle="modal" data-bs-target="#addModal">
    <i class="bi bi-person-plus"></i> Add Manager
  </button>
</div>

<div class="table-card">
  <div class="tc-head"><h5>👤 Managers (<?= count($managers) ?>)</h5></div>
  <table class="table table-hover">
    <thead><tr><th>#</th><th>Name</th><th>Username</th><th>Phone</th><th>Status</th><th>Created</th><th>Action</th></tr></thead>
    <tbody>
    <?php if (!$managers): ?>
      <tr><td colspan="7" class="text-center text-muted py-4">No managers yet</td></tr>
    <?php else: foreach ($managers as $i => $m): ?>
      <tr>
        <td><?= $i+1 ?></td>
        <td><strong><?= sanitize($m['name']) ?></strong></td>
        <td><code><?= sanitize($m['username']) ?></code></td>
        <td><?= sanitize($m['phone'] ?? '—') ?></td>
        <td>
          <?php if ($m['is_active']): ?>
            <span class="badge bg-success">Active</span>
          <?php else: ?>
            <span class="badge bg-danger">Inactive</span>
          <?php endif; ?>
        </td>
        <td><?= formatEthDate(substr($m['created_at'],0,10)) ?></td>
        <td>
          <a href="?toggle=<?= $m['id'] ?>" class="btn btn-sm <?= $m['is_active'] ? 'btn-outline-danger' : 'btn-outline-success' ?>"
             onclick="return confirm('Change account status?')">
            <?= $m['is_active'] ? 'Deactivate' : 'Activate' ?>
          </a>
        </td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header bg-primary text-white">
      <h5 class="modal-title">➕ Add Manager Account</h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="create">
      <div class="modal-body">
        <div class="mb-3"><label class="form-label fw-bold">Full Name *</label>
          <input type="text" name="name" class="form-control form-control-lg" required></div>
        <div class="mb-3"><label class="form-label fw-bold">Username *</label>
          <input type="text" name="username" class="form-control form-control-lg" required autocomplete="off"></div>
        <div class="mb-3"><label class="form-label fw-bold">Password *</label>
          <input type="text" name="password" class="form-control form-control-lg" required autocomplete="off"></div>
        <div class="mb-3"><label class="form-label fw-bold">Phone</label>
          <input type="text" name="phone" class="form-control"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary btn-lg">Create Account</button>
      </div>
    </form>
  </div></div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
