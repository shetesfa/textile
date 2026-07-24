<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('manager');

$todayPresent = (int)$pdo->query("SELECT COUNT(*) FROM attendance WHERE work_date=CURDATE() AND status='present'")->fetchColumn();
$totalEmp     = (int)$pdo->query("SELECT COUNT(*) FROM employees WHERE is_active=1")->fetchColumn();
$activeOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status NOT IN ('finished')")->fetchColumn();
$finishedOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status='finished'")->fetchColumn();

$unpaidTotal = 0.0;
foreach ($pdo->query("SELECT id FROM employees WHERE is_active=1")->fetchAll() as $e) {
    $unpaidTotal += getEmployeeBalance($pdo, $e['id']);
}
$recentOrders = $pdo->query("SELECT * FROM orders WHERE status != 'finished' ORDER BY updated_at DESC LIMIT 5")->fetchAll();

$pageTitle = 'Manager Dashboard';
include __DIR__ . '/../includes/header.php';
?>

<div class="row g-3 mb-4">
  <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-icon">👥</div><div>
    <div class="stat-val text-primary"><?= $todayPresent ?>/<?= $totalEmp ?></div>
    <div class="stat-label">Present Today</div>
  </div></div></div>
  <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-icon">⚙️</div><div>
    <div class="stat-val text-warning"><?= $activeOrders ?></div>
    <div class="stat-label">Active Orders</div>
  </div></div></div>
  <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-icon">🏁</div><div>
    <div class="stat-val text-success"><?= $finishedOrders ?></div>
    <div class="stat-label">Finished Orders</div>
  </div></div></div>
  <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-icon">💰</div><div>
    <div class="stat-val text-danger" style="font-size:1.3rem"><?= number_format($unpaidTotal) ?></div>
    <div class="stat-label">Unpaid (ETB)</div>
  </div></div></div>
</div>

<div class="row g-3 mb-4">
  <div class="col-12"><h6 class="text-muted fw-bold text-uppercase" style="font-size:.75rem;letter-spacing:.06em">Quick Actions</h6></div>
  <div class="col-6 col-md-3">
    <a href="attendance.php" class="btn btn-primary btn-lg w-100 py-3">
      <i class="bi bi-calendar-check d-block" style="font-size:1.6rem;margin-bottom:4px"></i>Attendance
    </a>
  </div>
  <div class="col-6 col-md-3">
    <a href="employees.php" class="btn btn-outline-primary btn-lg w-100 py-3">
      <i class="bi bi-people d-block" style="font-size:1.6rem;margin-bottom:4px"></i>Employees
    </a>
  </div>
  <div class="col-6 col-md-3">
    <a href="orders.php" class="btn btn-outline-warning btn-lg w-100 py-3">
      <i class="bi bi-box-seam d-block" style="font-size:1.6rem;margin-bottom:4px"></i>Orders
    </a>
  </div>
  <div class="col-6 col-md-3">
    <a href="levels.php" class="btn btn-outline-secondary btn-lg w-100 py-3">
      <i class="bi bi-stars d-block" style="font-size:1.6rem;margin-bottom:4px"></i>Salary Levels
    </a>
  </div>
</div>

<div class="table-card">
  <div class="tc-head">
    <h5>Active Orders</h5>
    <a href="orders.php" class="btn btn-sm btn-outline-primary">Manage All</a>
  </div>
  <table class="table table-hover">
    <thead><tr><th>Order</th><th>Client</th><th>Product</th><th>Status</th><th>Action</th></tr></thead>
    <tbody>
    <?php if (!$recentOrders): ?>
      <tr><td colspan="5" class="text-center py-4 text-muted">No active orders</td></tr>
    <?php else: foreach ($recentOrders as $o): ?>
      <tr>
        <td><strong><?= sanitize($o['order_number']) ?></strong></td>
        <td><?= sanitize($o['client_name']) ?></td>
        <td><?= sanitize($o['product_name']) ?></td>
        <td><span class="status-badge <?= orderStatusClass($o['status']) ?>"><?= orderStatusIcon($o['status']).' '.orderStatusLabel($o['status']) ?></span></td>
        <td><a href="orders.php" class="btn btn-sm btn-outline-primary">Manage</a></td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
