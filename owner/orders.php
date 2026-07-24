<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('owner');

// Create new order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $num    = generateOrderNumber();
    $client = trim($_POST['client_name'] ?? '');
    $prod   = trim($_POST['product_name'] ?? '');
    $qty    = (int)($_POST['target_quantity'] ?? 0);
    $dead   = trim($_POST['deadline'] ?? '') ?: null;
    if ($client && $prod && $qty > 0) {
        $st = $pdo->prepare("INSERT INTO orders (order_number,client_name,product_name,target_quantity,deadline,created_by) VALUES (?,?,?,?,?,?)");
        $st->execute([$num, $client, $prod, $qty, $dead, $_SESSION['user_id']]);
        flash('success', "Order $num created successfully!");
    } else {
        flash('error', 'Please fill all required fields.');
    }
    header('Location: orders.php'); exit;
}

$filter = $_GET['status'] ?? 'all';
$where  = $filter !== 'all' ? "WHERE status = " . $pdo->quote($filter) : '';
$orders = $pdo->query("SELECT * FROM orders $where ORDER BY created_at DESC")->fetchAll();
$pageTitle = 'Orders';
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div class="d-flex gap-2 flex-wrap">
    <?php foreach (['all','new','accepted','working','half_finished','finished'] as $s): ?>
      <a href="?status=<?= $s ?>" class="btn btn-sm <?= $filter===$s ? 'btn-primary' : 'btn-outline-secondary' ?>">
        <?= $s === 'all' ? 'All' : (orderStatusIcon($s).' '.orderStatusLabel($s)) ?>
      </a>
    <?php endforeach; ?>
  </div>
  <button class="btn btn-accent btn-lg" data-bs-toggle="modal" data-bs-target="#newOrderModal">
    <i class="bi bi-plus-circle"></i> New Order
  </button>
</div>

<div class="table-card">
  <div class="tc-head"><h5>📦 Orders</h5><span class="text-muted" style="font-size:.82rem"><?= count($orders) ?> records</span></div>
  <div class="table-responsive">
  <table class="table table-hover">
    <thead><tr>
      <th>Order #</th><th>Client</th><th>Product</th><th>Target</th><th>Status</th><th>Deadline</th><th>Progress</th><th>Details</th>
    </tr></thead>
    <tbody>
    <?php if (!$orders): ?>
      <tr><td colspan="8" class="text-center text-muted py-5">No orders found</td></tr>
    <?php else: foreach ($orders as $o): ?>
      <tr>
        <td><strong><?= sanitize($o['order_number']) ?></strong></td>
        <td><?= sanitize($o['client_name']) ?></td>
        <td><?= sanitize($o['product_name']) ?></td>
        <td><?= $o['target_quantity'] ?></td>
        <td><span class="status-badge <?= orderStatusClass($o['status']) ?>"><?= orderStatusIcon($o['status']).' '.orderStatusLabel($o['status']) ?></span></td>
        <td><?= $o['deadline'] ? formatEthDate($o['deadline']) : '<span class="text-muted">—</span>' ?></td>
        <td>
          <?php $pct = $o['target_quantity'] > 0 ? min(100, round($o['completed_quantity']/$o['target_quantity']*100)) : 0; ?>
          <div class="d-flex align-items-center gap-2">
            <div class="progress flex-grow-1" style="height:8px;min-width:80px">
              <div class="progress-bar bg-success" style="width:<?= $pct ?>%"></div>
            </div>
            <small><?= $pct ?>%</small>
          </div>
          <?php if ($o['completed_quantity']): ?><small class="text-muted"><?= $o['completed_quantity'] ?>/<?= $o['target_quantity'] ?></small><?php endif; ?>
        </td>
        <td>
          <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#detailModal<?= $o['id'] ?>">
            <i class="bi bi-eye"></i>
          </button>
        </td>
      </tr>
      <!-- Detail Modal -->
      <div class="modal fade" id="detailModal<?= $o['id'] ?>" tabindex="-1">
        <div class="modal-dialog"><div class="modal-content">
          <div class="modal-header"><h5 class="modal-title">Order: <?= sanitize($o['order_number']) ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body">
            <!-- Progress Steps -->
            <div class="order-steps mb-3">
              <?php $steps = statusSteps(); foreach ($steps as $idx => $st):
                $cur = array_search($o['status'], $steps);
                $cls = ($idx < $cur) ? 'done' : (($idx === $cur) ? 'active' : '');
              ?>
              <div class="step <?= $cls ?>">
                <div class="step-dot"></div>
                <?= orderStatusLabel($st) ?>
              </div>
              <?php endforeach; ?>
            </div>
            <table class="table table-sm table-bordered">
              <tr><th>Client</th><td><?= sanitize($o['client_name']) ?></td></tr>
              <tr><th>Product</th><td><?= sanitize($o['product_name']) ?></td></tr>
              <tr><th>Target Qty</th><td><?= $o['target_quantity'] ?></td></tr>
              <tr><th>Completed</th><td><?= $o['completed_quantity'] ?></td></tr>
              <tr><th>Status</th><td><span class="status-badge <?= orderStatusClass($o['status']) ?>"><?= orderStatusLabel($o['status']) ?></span></td></tr>
              <tr><th>Deadline</th><td><?= $o['deadline'] ? formatEthDate($o['deadline']) : '—' ?></td></tr>
              <tr><th>Created</th><td><?= formatEthDate(substr($o['created_at'],0,10)) ?></td></tr>
              <?php if ($o['incomplete_reason']): ?>
              <tr><th>Note</th><td class="text-danger"><?= sanitize($o['incomplete_reason']) ?></td></tr>
              <?php endif; ?>
            </table>
          </div>
          <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
        </div></div>
      </div>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
  </div>
</div>

<!-- New Order Modal -->
<div class="modal fade" id="newOrderModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header bg-primary text-white">
      <h5 class="modal-title">🆕 Create New Order</h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="create">
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label fw-bold">Client Name *</label>
          <input type="text" name="client_name" class="form-control form-control-lg" placeholder="e.g. Ethio Garments PLC" required>
        </div>
        <div class="mb-3">
          <label class="form-label fw-bold">Product / Item *</label>
          <input type="text" name="product_name" class="form-control form-control-lg" placeholder="e.g. School Uniforms" required>
        </div>
        <div class="mb-3">
          <label class="form-label fw-bold">Target Quantity *</label>
          <input type="number" name="target_quantity" class="form-control form-control-lg" min="1" placeholder="e.g. 500" required>
        </div>
        <div class="mb-3">
          <label class="form-label fw-bold">Deadline (optional)</label>
          <input type="date" name="deadline" class="form-control">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary btn-lg">Create Order</button>
      </div>
    </form>
  </div></div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
