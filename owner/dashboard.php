<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('owner');
$pageTitle = 'Owner Dashboard';

// ── Stats ──────────────────────────────────────────────────
// Today's attendance
$todayCount = (int)$pdo->prepare("SELECT COUNT(*) FROM attendance WHERE work_date = CURDATE() AND status='present'")->execute() ? 
    $pdo->query("SELECT COUNT(*) FROM attendance WHERE work_date = CURDATE() AND status='present'")->fetchColumn() : 0;

$todayCount = (int)$pdo->query(
    "SELECT COUNT(*) FROM attendance WHERE work_date = CURDATE() AND status='present'"
)->fetchColumn();

$activeOrders = (int)$pdo->query(
    "SELECT COUNT(*) FROM orders WHERE status NOT IN ('finished')"
)->fetchColumn();

$finishedOrders = (int)$pdo->query(
    "SELECT COUNT(*) FROM orders WHERE status = 'finished'"
)->fetchColumn();

// Total unpaid balance across all employees
$unpaidTotal = 0.0;
$emps = $pdo->query("SELECT id FROM employees WHERE is_active=1")->fetchAll();
foreach ($emps as $e) {
    $unpaidTotal += getEmployeeBalance($pdo, $e['id']);
}

// Recent orders
$recentOrders = $pdo->query(
    "SELECT * FROM orders ORDER BY updated_at DESC LIMIT 8"
)->fetchAll();

// Employees with high unpaid balance
$topUnpaid = $pdo->query(
    "SELECT e.id, e.full_name, e.level,
        COALESCE((SELECT SUM(daily_rate) FROM attendance WHERE employee_id=e.id AND status='present'),0)
        - COALESCE((SELECT SUM(amount)    FROM payments  WHERE employee_id=e.id),0) AS balance
     FROM employees e WHERE e.is_active=1
     HAVING balance > 0
     ORDER BY balance DESC LIMIT 5"
)->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon">👥</div>
      <div>
        <div class="stat-val text-primary"><?= $todayCount ?></div>
        <div class="stat-label">Present Today</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon">⚙️</div>
      <div>
        <div class="stat-val text-warning"><?= $activeOrders ?></div>
        <div class="stat-label">Active Orders</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon">🏁</div>
      <div>
        <div class="stat-val text-success"><?= $finishedOrders ?></div>
        <div class="stat-label">Finished Orders</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon">💰</div>
      <div>
        <div class="stat-val text-danger" style="font-size:1.4rem"><?= number_format($unpaidTotal) ?></div>
        <div class="stat-label">Unpaid Salary (ETB)</div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <!-- Recent Orders -->
  <div class="col-md-7">
    <div class="table-card">
      <div class="tc-head">
        <h5>📦 Recent Orders</h5>
        <a href="<?= BASE_URL ?>/owner/orders.php" class="btn btn-sm btn-outline-primary">View All</a>
      </div>
      <table class="table table-hover">
        <thead><tr>
          <th>#</th><th>Client</th><th>Product</th><th>Status</th><th>Qty</th>
        </tr></thead>
        <tbody>
        <?php if (!$recentOrders): ?>
          <tr><td colspan="5" class="text-center text-muted py-4">No orders yet</td></tr>
        <?php else: foreach ($recentOrders as $o): ?>
          <tr>
            <td><?= sanitize($o['order_number']) ?></td>
            <td><?= sanitize($o['client_name']) ?></td>
            <td><?= sanitize($o['product_name']) ?></td>
            <td>
              <span class="status-badge <?= orderStatusClass($o['status']) ?>">
                <?= orderStatusIcon($o['status']) ?> <?= orderStatusLabel($o['status']) ?>
              </span>
            </td>
            <td><?= $o['target_quantity'] ?></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Unpaid Balances -->
  <div class="col-md-5">
    <div class="table-card">
      <div class="tc-head">
        <h5>💸 Top Unpaid Balances</h5>
        <a href="<?= BASE_URL ?>/owner/salaries.php" class="btn btn-sm btn-outline-danger">Pay Now</a>
      </div>
      <table class="table">
        <thead><tr><th>Employee</th><th>Level</th><th>Balance</th></tr></thead>
        <tbody>
        <?php if (!$topUnpaid): ?>
          <tr><td colspan="3" class="text-center text-muted py-4">All paid up ✅</td></tr>
        <?php else: foreach ($topUnpaid as $e): ?>
          <tr>
            <td><?= sanitize($e['full_name']) ?></td>
            <td><span class="badge bg-secondary"><?= $e['level'] ?></span></td>
            <td class="text-danger fw-bold"><?= money($e['balance']) ?></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
