<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('owner');

$empId = (int)($_GET['emp'] ?? 0);
$emp   = $pdo->prepare("SELECT * FROM employees WHERE id=?");
$emp->execute([$empId]); $emp = $emp->fetch();
if (!$emp) { flash('error','Employee not found.'); header('Location: salaries.php'); exit; }

$history = $pdo->prepare(
    "SELECT p.*, u.name AS paid_by_name FROM payments p
     LEFT JOIN users u ON u.id = p.paid_by
     WHERE p.employee_id = ? ORDER BY p.paid_at DESC"
);
$history->execute([$empId]);
$history = $history->fetchAll();

$earned  = getEmployeeEarned($pdo, $empId);
$paid    = getEmployeePaid($pdo,   $empId);
$balance = $earned - $paid;

$pageTitle = 'Payment History: ' . $emp['full_name'];
include __DIR__ . '/../includes/header.php';
?>

<a href="salaries.php" class="btn btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> Back</a>

<div class="row g-3 mb-4">
  <div class="col-md-4"><div class="stat-card"><div class="stat-icon">📈</div><div>
    <div class="stat-val text-primary"><?= number_format($earned) ?></div>
    <div class="stat-label">Total Earned (ETB)</div>
  </div></div></div>
  <div class="col-md-4"><div class="stat-card"><div class="stat-icon">✅</div><div>
    <div class="stat-val text-success"><?= number_format($paid) ?></div>
    <div class="stat-label">Total Paid (ETB)</div>
  </div></div></div>
  <div class="col-md-4"><div class="stat-card"><div class="stat-icon">💸</div><div>
    <div class="stat-val <?= $balance > 0 ? 'text-danger' : 'text-success' ?>"><?= number_format($balance) ?></div>
    <div class="stat-label">Remaining Balance (ETB)</div>
  </div></div></div>
</div>

<div class="table-card">
  <div class="tc-head"><h5>💳 Payment History — <?= sanitize($emp['full_name']) ?></h5></div>
  <table class="table">
    <thead><tr><th>#</th><th>Amount</th><th>Paid By</th><th>Note</th><th>Date</th></tr></thead>
    <tbody>
    <?php if (!$history): ?>
      <tr><td colspan="5" class="text-center text-muted py-4">No payments yet</td></tr>
    <?php else: foreach ($history as $i => $p): ?>
      <tr>
        <td><?= $i + 1 ?></td>
        <td class="text-success fw-bold"><?= money($p['amount']) ?></td>
        <td><?= sanitize($p['paid_by_name'] ?? '—') ?></td>
        <td><?= sanitize($p['note'] ?? '—') ?></td>
        <td><?= formatEthDate(substr($p['paid_at'],0,10)) ?></td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
