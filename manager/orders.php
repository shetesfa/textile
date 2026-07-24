<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('manager');

// Advance order status
if (isset($_GET['advance'])) {
    $id    = (int)$_GET['advance'];
    $order = $pdo->prepare("SELECT * FROM orders WHERE id=?");
    $order->execute([$id]); $order = $order->fetch();
    if ($order) {
        $next = getNextStatus($order['status']);
        if ($next && $next !== 'finished') {
            $pdo->prepare("UPDATE orders SET status=?, updated_at=NOW() WHERE id=?")->execute([$next, $id]);
            $pdo->prepare("INSERT INTO order_updates (order_id,old_status,new_status,updated_by) VALUES (?,?,?,?)")
                ->execute([$id, $order['status'], $next, $_SESSION['user_id']]);
            flash('success', 'Order moved to: ' . orderStatusLabel($next));
        }
    }
    header('Location: orders.php'); exit;
}

// Finish order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'finish') {
    $id        = (int)$_POST['order_id'];
    $completed = (int)$_POST['completed_quantity'];
    $reason    = trim($_POST['reason'] ?? '');
    $order     = $pdo->prepare("SELECT * FROM orders WHERE id=?");
    $order->execute([$id]); $order = $order->fetch();
    if ($order) {
        $pdo->prepare(
            "UPDATE orders SET status='finished', completed_quantity=?, incomplete_reason=?, finished_at=NOW(), updated_at=NOW() WHERE id=?"
        )->execute([$completed, $reason ?: null, $id]);
        $pdo->prepare("INSERT INTO order_updates (order_id,old_status,new_status,note,updated_by) VALUES (?,?,?,?,?)")
            ->execute([$id, $order['status'], 'finished', "Completed: $completed / {$order['target_quantity']}. $reason", $_SESSION['user_id']]);
        flash('success', 'Order marked as FINISHED! Completed: ' . $completed . ' / ' . $order['target_quantity']);
    }
    header('Location: orders.php'); exit;
}

$filter  = $_GET['status'] ?? 'active';
$sqlWhere = $filter === 'all' ? '' : ($filter === 'active' ? "WHERE status != 'finished'" : "WHERE status = " . $pdo->quote($filter));
$orders  = $pdo->query("SELECT * FROM orders $sqlWhere ORDER BY updated_at DESC")->fetchAll();

$pageTitle = 'Manage Orders';
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex gap-2 flex-wrap mb-3">
  <?php foreach (['active'=>'⚙️ Active','all'=>'📋 All','new'=>'🆕 New','accepted'=>'✅ Accepted','working'=>'🔧 Working','half_finished'=>'📦 Half Finished','finished'=>'🏁 Finished'] as $s => $lbl): ?>
    <a href="?status=<?= $s ?>" class="btn btn-sm <?= $filter===$s ? 'btn-primary' : 'btn-outline-secondary' ?>"><?= $lbl ?></a>
  <?php endforeach; ?>
</div>

<div class="table-card">
  <div class="tc-head">
    <h5>📦 Orders (<?= count($orders) ?>)</h5>
    <small class="text-muted">Manager controls order progress</small>
  </div>
  <div class="table-responsive">
  <table class="table table-hover">
    <thead><tr><th>Order #</th><th>Client</th><th>Product</th><th>Target</th><th>Deadline</th><th>Status</th><th>Next Step</th></tr></thead>
    <tbody>
    <?php if (!$orders): ?>
      <tr><td colspan="7" class="text-center py-5 text-muted">No orders in this category</td></tr>
    <?php else: foreach ($orders as $o):
      $next = getNextStatus($o['status']);
    ?>
      <tr>
        <td><strong><?= sanitize($o['order_number']) ?></strong></td>
        <td><?= sanitize($o['client_name']) ?></td>
        <td><?= sanitize($o['product_name']) ?></td>
        <td><?= $o['target_quantity'] ?></td>
        <td><?= $o['deadline'] ? formatEthDate($o['deadline']) : '<span class="text-muted">—</span>' ?></td>
        <td>
          <!-- Progress Steps Visual -->
          <div class="order-steps" style="min-width:260px">
            <?php foreach (statusSteps() as $idx => $st):
              $cur = array_search($o['status'], statusSteps());
              $cls = $idx < $cur ? 'done' : ($idx === $cur ? 'active' : '');
            ?>
              <div class="step <?= $cls ?>" style="font-size:.65rem;padding:6px 2px">
                <div class="step-dot"></div>
                <?= orderStatusLabel($st) ?>
              </div>
            <?php endforeach; ?>
          </div>
        </td>
        <td>
          <?php if ($o['status'] === 'finished'): ?>
            <span class="badge bg-success">✅ Finished (<?= $o['completed_quantity'] ?>)</span>
            <?php if ($o['incomplete_reason']): ?>
              <br><small class="text-danger">⚠️ <?= sanitize($o['incomplete_reason']) ?></small>
            <?php endif; ?>
          <?php elseif ($next === 'finished'): ?>
            <button class="btn btn-success btn-lg"
              data-bs-toggle="modal" data-bs-target="#finishModal"
              data-id="<?= $o['id'] ?>"
              data-qty="<?= $o['target_quantity'] ?>"
              data-num="<?= sanitize($o['order_number']) ?>">
              🏁 Mark Finished
            </button>
          <?php elseif ($next): ?>
            <a href="?advance=<?= $o['id'] ?>&status=<?= $filter ?>"
               class="btn btn-primary"
               onclick="return confirm('Move order to: <?= orderStatusLabel($next) ?>?')">
              → <?= orderStatusLabel($next) ?>
            </a>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
  </div>
</div>

<!-- Finish Modal -->
<div class="modal fade" id="finishModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header bg-success text-white">
      <h5 class="modal-title">🏁 Mark Order as Finished</h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="finish">
      <input type="hidden" name="order_id" id="finishId">
      <div class="modal-body">
        <div class="alert alert-info" id="finishInfo" style="font-size:.88rem"></div>
        <div class="mb-3">
          <label class="form-label fw-bold fs-5">✅ Completed Quantity</label>
          <input type="number" name="completed_quantity" id="finishQty"
                 class="form-control form-control-lg" min="0" required>
          <div class="form-text">Enter how many were actually completed</div>
        </div>
        <div class="mb-3">
          <label class="form-label fw-bold">📝 Reason (if incomplete)</label>
          <textarea name="reason" id="finishReason" class="form-control" rows="3"
                    placeholder="e.g. 2 damaged during sewing, power outage..."></textarea>
          <div class="form-text text-muted">Leave empty if 100% completed</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-success btn-lg">✅ Confirm Finished</button>
      </div>
    </form>
  </div></div>
</div>

<script>
document.getElementById('finishModal').addEventListener('show.bs.modal', e => {
  const b = e.relatedTarget;
  document.getElementById('finishId').value = b.dataset.id;
  document.getElementById('finishQty').max  = b.dataset.qty;
  document.getElementById('finishQty').value = b.dataset.qty;
  document.getElementById('finishInfo').innerHTML =
    'Order: <strong>' + b.dataset.num + '</strong> | Target: <strong>' + b.dataset.qty + ' units</strong>';
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
