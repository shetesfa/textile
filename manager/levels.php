<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('manager');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    foreach (['A','B','C','D'] as $lv) {
        $label = trim($_POST["label_$lv"] ?? '');
        $rate  = (float)str_replace(',', '', $_POST["rate_$lv"] ?? 0);
        if ($label && $rate > 0) {
            $pdo->prepare("UPDATE salary_levels SET label=?, daily_rate=? WHERE level=?")
                ->execute([$label, $rate, $lv]);
        }
    }
    flash('success', 'Salary levels updated successfully!');
    header('Location: levels.php'); exit;
}

$levels = getAllLevels($pdo);
$pageTitle = 'Salary Levels';
include __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
<div class="col-lg-7">
  <div class="table-card p-4">
    <h5 class="mb-1">⭐ Salary Levels</h5>
    <p class="text-muted mb-4" style="font-size:.85rem">Set the daily pay rate for each skill level. Changes apply to future attendance records.</p>
    <form method="POST">
      <input type="hidden" name="action" value="update">
      <?php foreach (['A','B','C','D'] as $lv):
        $lvData = $levels[$lv] ?? ['label'=>'','daily_rate'=>0];
        $colors = ['A'=>'success','B'=>'primary','C'=>'warning','D'=>'secondary'];
        $icons  = ['A'=>'🥇','B'=>'🥈','C'=>'🥉','D'=>'🔰'];
      ?>
      <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body">
          <div class="d-flex align-items-center gap-2 mb-3">
            <span class="badge fs-4 bg-<?= $colors[$lv] ?> px-3 py-2"><?= $lv ?></span>
            <span style="font-size:1.4rem"><?= $icons[$lv] ?></span>
            <strong class="fs-5">Level <?= $lv ?></strong>
          </div>
          <div class="row g-2">
            <div class="col-md-7">
              <label class="form-label fw-bold">Label / Description</label>
              <input type="text" name="label_<?= $lv ?>" class="form-control"
                     value="<?= sanitize($lvData['label']) ?>" required
                     placeholder="e.g. Excellent Worker">
            </div>
            <div class="col-md-5">
              <label class="form-label fw-bold">Daily Rate (ETB)</label>
              <div class="input-group">
                <input type="number" name="rate_<?= $lv ?>" class="form-control form-control"
                       value="<?= number_format($lvData['daily_rate'], 2) ?>"
                       min="1" step="0.01" required>
                <span class="input-group-text">ETB</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
      <button type="submit" class="btn btn-primary btn-lg w-100 mt-2">
        <i class="bi bi-save"></i> Save All Levels
      </button>
    </form>
  </div>
</div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
