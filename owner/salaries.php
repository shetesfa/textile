<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('owner');

// Handle payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'pay') {
    $empId  = (int)$_POST['employee_id'];
    $amount = (float)str_replace(',', '', $_POST['amount'] ?? 0);
    $note   = trim($_POST['note'] ?? '');
    if ($empId && $amount > 0) {
        $balance = getEmployeeBalance($pdo, $empId);
        if ($amount > $balance + 0.01) {
            flash('error', 'Payment amount exceeds unpaid balance.');
        } else {
            $pdo->prepare("INSERT INTO payments (employee_id, amount, paid_by, note) VALUES (?,?,?,?)")
                ->execute([$empId, $amount, $_SESSION['user_id'], $note ?: null]);
            flash('success', 'Payment of ' . money($amount) . ' recorded successfully!');
        }
    } else {
        flash('error', 'Invalid amount.');
    }
    header('Location: salaries.php'); exit;
}

$employees = $pdo->query("SELECT * FROM employees WHERE is_active=1 ORDER BY full_name")->fetchAll();

$pageTitle = 'Salaries & Payments';
include __DIR__ . '/../includes/header.php';
?>

<div class="table-card">
  <div class="tc-head">
    <h5>💰 Employee Salary Summary</h5>
    <small class="text-muted">Click Pay to record a payment</small>
  </div>
  <div class="table-responsive">
  <table class="table table-hover">
    <thead><tr>
      <th>Employee</th><th>Level</th><th>Total Earned</th><th>Total Paid</th><th>Balance</th><th>Action</th>
    </tr></thead>
    <tbody>
    <?php foreach ($employees as $emp):
      $earned  = getEmployeeEarned($pdo, $emp['id']);
      $paid    = getEmployeePaid($pdo,   $emp['id']);
      $balance = $earned - $paid;
    ?>
    <tr>
      <td><strong><?= sanitize($emp['full_name']) ?></strong><br><small class="text-muted"><?= sanitize($emp['position']) ?></small></td>
      <td><span class="badge bg-secondary"><?= $emp['level'] ?></span></td>
      <td class="text-primary fw-bold"><?= money($earned) ?></td>
      <td class="text-success"><?= money($paid) ?></td>
      <td class="fw-bold <?= $balance > 0 ? 'text-danger' : 'text-success' ?>"><?= money($balance) ?></td>
      <td>
        <?php if ($balance > 0): ?>
        <button class="btn btn-sm btn-success" data-bs-toggle="modal"
          data-bs-target="#payModal"
          data-id="<?= $emp['id'] ?>"
          data-name="<?= sanitize($emp['full_name']) ?>"
          data-balance="<?= number_format($balance, 2) ?>">
          <i class="bi bi-cash"></i> Pay
        </button>
        <?php else: ?>
        <span class="badge bg-success">✅ Paid</span>
        <?php endif; ?>
        <a href="pay_history.php?emp=<?= $emp['id'] ?>" class="btn btn-sm btn-outline-secondary ms-1">
          <i class="bi bi-clock-history"></i>
        </a>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<!-- Pay Modal -->
<div class="modal fade" id="payModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header bg-success text-white">
      <h5 class="modal-title">💸 Pay Employee</h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="pay">
      <input type="hidden" name="employee_id" id="payEmpId">
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label fw-bold">Employee</label>
          <input type="text" class="form-control" id="payEmpName" readonly style="background:#f9fafb">
        </div>
        <div class="alert alert-info p-2" style="font-size:.88rem">
          💰 Unpaid Balance: <strong id="payBalance"></strong>
        </div>
        <div class="mb-3">
          <label class="form-label fw-bold">Amount to Pay (ETB) *</label>
          <input type="number" name="amount" id="payAmount" class="form-control form-control-lg"
                 min="1" step="0.01" placeholder="Enter amount" required>
        </div>
        <div class="mb-3">
          <label class="form-label fw-bold">Note (optional)</label>
          <input type="text" name="note" class="form-control" placeholder="e.g. Week 1 payment">
        </div>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-outline-secondary" onclick="setFullPay()">Pay Full Balance</button>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-success btn-lg">Confirm Payment</button>
      </div>
    </form>
  </div></div>
</div>

<script>
const payModal = document.getElementById('payModal');
payModal.addEventListener('show.bs.modal', e => {
  const btn = e.relatedTarget;
  document.getElementById('payEmpId').value    = btn.dataset.id;
  document.getElementById('payEmpName').value  = btn.dataset.name;
  document.getElementById('payBalance').textContent = btn.dataset.balance + ' ETB';
  document.getElementById('payAmount').value   = '';
  document.getElementById('payAmount').max     = btn.dataset.balance;
});
function setFullPay() {
  const bal = parseFloat(document.getElementById('payBalance').textContent);
  document.getElementById('payAmount').value = bal;
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
