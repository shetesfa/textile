<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('manager');

$levels    = getAllLevels($pdo);
$levelKeys = array_keys($levels); // ['A','B','C','D'] — A=best

// Add employee
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $name  = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $pos   = trim($_POST['position'] ?? '');
    $lv    = $_POST['level'] ?? 'D';
    if ($name && isset($levels[$lv])) {
        $pdo->prepare("INSERT INTO employees (full_name,phone,position,level) VALUES (?,?,?,?)")
            ->execute([$name, $phone, $pos, $lv]);
        $newId = $pdo->lastInsertId();
        $rate  = (float)$levels[$lv]['daily_rate'];
        $pdo->prepare(
            "INSERT INTO employee_level_history (employee_id,level,daily_rate,effective_from,changed_by)
             VALUES (?,?,?,CURDATE(),?)"
        )->execute([$newId, $lv, $rate, $_SESSION['user_id']]);
        flash('success', "ሰራተኛ '$name' ተጨምሯል!");
    } else {
        flash('error', 'ስም እና ደረጃ ያስፈልጋሉ።');
    }
    header('Location: employees.php'); exit;
}

// Edit (name/phone/position only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    $id    = (int)$_POST['id'];
    $name  = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $pos   = trim($_POST['position'] ?? '');
    if ($id && $name) {
        $pdo->prepare("UPDATE employees SET full_name=?,phone=?,position=? WHERE id=?")
            ->execute([$name, $phone, $pos, $id]);
        flash('success', 'ሰራተኛ ተስተካክሏል።');
    }
    header('Location: employees.php'); exit;
}

// Feature 2: Level upgrade / downgrade
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_level') {
    $id        = (int)$_POST['id'];
    $direction = $_POST['direction'] ?? '';

    $empStmt = $pdo->prepare("SELECT * FROM employees WHERE id=? AND is_active=1");
    $empStmt->execute([$id]);
    $emp = $empStmt->fetch();

    if ($emp && in_array($direction, ['up', 'down'])) {
        $curIdx = array_search($emp['level'], $levelKeys);
        $newIdx = $direction === 'up' ? $curIdx - 1 : $curIdx + 1;

        if (isset($levelKeys[$newIdx])) {
            $newLevel = $levelKeys[$newIdx];
            $newRate  = (float)$levels[$newLevel]['daily_rate'];

            $pdo->prepare("UPDATE employees SET level=? WHERE id=?")
                ->execute([$newLevel, $id]);

            $pdo->prepare(
                "INSERT INTO employee_level_history (employee_id,level,daily_rate,effective_from,changed_by)
                 VALUES (?,?,?,CURDATE(),?)"
            )->execute([$id, $newLevel, $newRate, $_SESSION['user_id']]);

            $word = $direction === 'up' ? 'ደረጃ ከፍ ብሏል' : 'ደረጃ ዝቅ ብሏል';
            flash('success', sanitize($emp['full_name']) . " — $word: Level $newLevel (" . number_format($newRate, 2) . " ETB/ቀን). የድሮ መዝገቦች አልተቀያየሩም።");
        } else {
            flash('warning', $direction === 'up' ? 'ከፍተኛው ደረጃ ላይ ነው።' : 'ዝቅተኛው ደረጃ ላይ ነው።');
        }
    } else {
        flash('error', 'ልክ ያልሆነ ጥያቄ።');
    }
    header('Location: employees.php'); exit;
}

// Deactivate
if (isset($_GET['deactivate'])) {
    $pdo->prepare("UPDATE employees SET is_active=0 WHERE id=?")->execute([(int)$_GET['deactivate']]);
    flash('success', 'ሰራተኛ ተሰርዟል።');
    header('Location: employees.php'); exit;
}

$employees = $pdo->query("SELECT * FROM employees WHERE is_active=1 ORDER BY full_name")->fetchAll();
$pageTitle = 'ሰራተኞች';
include __DIR__ . '/../includes/header.php';
?>

<style>
/* Mobile-first */
.emp-topbar {
  display: flex; align-items: center; justify-content: space-between;
  flex-wrap: wrap; gap: 10px; margin-bottom: 14px;
}
.emp-topbar h5 { margin: 0; font-size: 1rem; font-weight: 700; }

/* Mobile card */
.emp-card {
  background: var(--card-bg); border: 1px solid var(--border);
  border-radius: 12px; padding: 14px; margin-bottom: 10px;
}
.emp-card-top  { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 10px; }
.emp-avatar {
  width: 40px; height: 40px; border-radius: 50%; flex-shrink: 0;
  background: linear-gradient(135deg, var(--primary), #3b82f6);
  color: #fff; font-weight: 700; font-size: 1rem;
  display: flex; align-items: center; justify-content: center;
}
.emp-name  { font-weight: 700; font-size: .95rem; }
.emp-meta  { font-size: .78rem; color: var(--muted); margin-top: 2px; }
.emp-row   { display: flex; align-items: center; justify-content: space-between;
             flex-wrap: wrap; gap: 8px; margin-bottom: 8px; }
.emp-actions { display: flex; gap: 6px; flex-wrap: wrap; }

/* Level badge colors */
.lvl-a { background:#d1fae5; color:#065f46; }
.lvl-b { background:#dbeafe; color:#1e40af; }
.lvl-c { background:#fef3c7; color:#92400e; }
.lvl-d { background:#f1f5f9; color:#475569; }
.lvl-badge {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 4px 10px; border-radius: 20px; font-size: .78rem; font-weight: 700;
  font-family: 'Noto Sans Ethiopic', sans-serif;
}

/* Level up/down buttons */
.lvl-btns { display: flex; gap: 4px; }
.btn-lvl-up {
  font-size: .72rem; padding: 4px 10px; border-radius: 6px; cursor: pointer;
  background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; font-weight: 700;
}
.btn-lvl-up:hover { background: #dcfce7; }
.btn-lvl-up:disabled { opacity: .35; cursor: not-allowed; }
.btn-lvl-down {
  font-size: .72rem; padding: 4px 10px; border-radius: 6px; cursor: pointer;
  background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; font-weight: 700;
}
.btn-lvl-down:hover { background: #ffedd5; }
.btn-lvl-down:disabled { opacity: .35; cursor: not-allowed; }

.bal-pos { color: var(--danger); font-weight: 700; font-size: .85rem; }
.bal-ok  { color: var(--success); font-weight: 700; font-size: .85rem; }

/* Mobile: cards only. Desktop: table only. */
.emp-mobile { display: block; }
.emp-desktop { display: none; }
@media (min-width: 768px) {
  .emp-mobile  { display: none; }
  .emp-desktop { display: block; }
}
</style>

<div class="emp-topbar">
  <h5>ሰራተኞች (<?= count($employees) ?>)</h5>
  <button class="btn btn-accent" data-bs-toggle="modal" data-bs-target="#addModal">
    <i class="bi bi-person-plus me-1"></i> ሰራተኛ ጨምር
  </button>
</div>

<?php
// Reusable level-change forms macro
function lvlForms(array $emp, array $levelKeys, bool $canUp, bool $canDown): void {
    $upLabel   = $canUp   ? 'Level ' . $levelKeys[max(0, array_search($emp['level'], $levelKeys) - 1)] : '';
    $downLabel = $canDown ? 'Level ' . $levelKeys[min(count($levelKeys)-1, array_search($emp['level'], $levelKeys) + 1)] : '';
    $name = htmlspecialchars($emp['full_name'], ENT_QUOTES);
    echo '<div class="lvl-btns">';
    echo '<form method="POST" style="display:inline">'
       . '<input type="hidden" name="action" value="change_level">'
       . '<input type="hidden" name="id" value="' . $emp['id'] . '">'
       . '<input type="hidden" name="direction" value="up">'
       . '<button type="submit" class="btn-lvl-up"' . (!$canUp ? ' disabled' : '')
       . ($canUp ? ' onclick="return confirm(\'▲ ' . $name . ' — ' . $upLabel . '?\\nየድሮ መዝገቦች አይቀያየሩም።\')"' : '')
       . '>▲ ከፍ</button></form>';
    echo '<form method="POST" style="display:inline">'
       . '<input type="hidden" name="action" value="change_level">'
       . '<input type="hidden" name="id" value="' . $emp['id'] . '">'
       . '<input type="hidden" name="direction" value="down">'
       . '<button type="submit" class="btn-lvl-down"' . (!$canDown ? ' disabled' : '')
       . ($canDown ? ' onclick="return confirm(\'▼ ' . $name . ' — ' . $downLabel . '?\\nየድሮ መዝገቦች አይቀያየሩም።\')"' : '')
       . '>▼ ዝቅ</button></form>';
    echo '</div>';
}
?>

<!-- Mobile: card list -->
<div class="emp-mobile">
<?php if (!$employees): ?>
  <div class="text-center py-5 text-muted">ምንም ሰራተኛ አልተጨመረም።</div>
<?php else: foreach ($employees as $emp):
  $rate    = isset($levels[$emp['level']]) ? (float)$levels[$emp['level']]['daily_rate'] : 0;
  $balance = getEmployeeBalance($pdo, $emp['id']);
  $lvIdx   = array_search($emp['level'], $levelKeys);
  $canUp   = $lvIdx > 0;
  $canDown = $lvIdx < count($levelKeys) - 1;
  $lvCls   = ['A'=>'lvl-a','B'=>'lvl-b','C'=>'lvl-c','D'=>'lvl-d'][$emp['level']] ?? 'lvl-d';
?>
  <div class="emp-card">
    <div class="emp-card-top">
      <div class="emp-avatar"><?= mb_substr($emp['full_name'], 0, 1) ?></div>
      <div style="flex:1;min-width:0">
        <div class="emp-name"><?= sanitize($emp['full_name']) ?></div>
        <div class="emp-meta">
          <?= sanitize($emp['position'] ?? '—') ?>
          <?php if ($emp['phone']): ?> · <?= sanitize($emp['phone']) ?><?php endif; ?>
        </div>
      </div>
    </div>
    <div class="emp-row">
      <span class="lvl-badge <?= $lvCls ?>">
        ደረጃ <?= $emp['level'] ?> — <?= sanitize($levels[$emp['level']]['label'] ?? '') ?>
      </span>
      <span style="font-size:.82rem;color:var(--muted)"><?= money($rate) ?>/ቀን</span>
    </div>
    <div class="emp-row">
      <span class="<?= $balance > 0 ? 'bal-pos' : 'bal-ok' ?>">
        <?= $balance > 0 ? '⚠ ያልተከፈለ: ' . money($balance) : '✓ ተከፍሏል' ?>
      </span>
      <?php lvlForms($emp, $levelKeys, $canUp, $canDown); ?>
    </div>
    <div class="emp-actions">
      <button class="btn btn-sm btn-outline-primary"
        data-bs-toggle="modal" data-bs-target="#editModal"
        data-id="<?= $emp['id'] ?>"
        data-name="<?= sanitize($emp['full_name']) ?>"
        data-phone="<?= sanitize($emp['phone'] ?? '') ?>"
        data-pos="<?= sanitize($emp['position'] ?? '') ?>">
        <i class="bi bi-pencil"></i> አስተካክል
      </button>
      <a href="?deactivate=<?= $emp['id'] ?>" class="btn btn-sm btn-outline-danger"
         onclick="return confirm('<?= sanitize($emp['full_name']) ?> ይሰረዝ?')">
        <i class="bi bi-person-x"></i> ሰርዝ
      </a>
    </div>
  </div>
<?php endforeach; endif; ?>
</div>

<!-- Desktop: table -->
<div class="emp-desktop table-card">
  <div class="table-responsive">
    <table class="table table-hover" style="font-size:.88rem">
      <thead>
        <tr>
          <th>#</th>
          <th>ስም</th>
          <th>ስልክ</th>
          <th>ልዩ ሙያ</th>
          <th>ደረጃ</th>
          <th>የቀን ክፍያ</th>
          <th>ያልተከፈለ</th>
          <th>ደረጃ ቀይር</th>
          <th>ድርጊት</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$employees): ?>
        <tr><td colspan="9" class="text-center py-5 text-muted">ምንም ሰራተኛ አልተጨመረም።</td></tr>
      <?php else: foreach ($employees as $i => $emp):
        $rate    = isset($levels[$emp['level']]) ? (float)$levels[$emp['level']]['daily_rate'] : 0;
        $balance = getEmployeeBalance($pdo, $emp['id']);
        $lvIdx   = array_search($emp['level'], $levelKeys);
        $canUp   = $lvIdx > 0;
        $canDown = $lvIdx < count($levelKeys) - 1;
        $lvCls   = ['A'=>'lvl-a','B'=>'lvl-b','C'=>'lvl-c','D'=>'lvl-d'][$emp['level']] ?? 'lvl-d';
      ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><strong><?= sanitize($emp['full_name']) ?></strong></td>
          <td><?= sanitize($emp['phone'] ?? '—') ?></td>
          <td><?= sanitize($emp['position'] ?? '—') ?></td>
          <td>
            <span class="lvl-badge <?= $lvCls ?>">
              <?= $emp['level'] ?> — <?= sanitize($levels[$emp['level']]['label'] ?? '') ?>
            </span>
          </td>
          <td><?= money($rate) ?></td>
          <td class="fw-bold <?= $balance > 0 ? 'text-danger' : 'text-success' ?>"><?= money($balance) ?></td>
          <td><?php lvlForms($emp, $levelKeys, $canUp, $canDown); ?></td>
          <td>
            <button class="btn btn-sm btn-outline-primary"
              data-bs-toggle="modal" data-bs-target="#editModal"
              data-id="<?= $emp['id'] ?>"
              data-name="<?= sanitize($emp['full_name']) ?>"
              data-phone="<?= sanitize($emp['phone'] ?? '') ?>"
              data-pos="<?= sanitize($emp['position'] ?? '') ?>">
              <i class="bi bi-pencil"></i>
            </button>
            <a href="?deactivate=<?= $emp['id'] ?>" class="btn btn-sm btn-outline-danger"
               onclick="return confirm('<?= sanitize($emp['full_name']) ?> ይሰረዝ?')">
              <i class="bi bi-person-x"></i>
            </a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <div class="modal-header bg-primary text-white">
      <h5 class="modal-title">➕ ሰራተኛ ጨምር</h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label fw-bold">ሙሉ ስም *</label>
          <input type="text" name="full_name" class="form-control form-control-lg" required placeholder="ለምሳሌ: አልማዝ በቀለ">
        </div>
        <div class="mb-3">
          <label class="form-label fw-bold">ስልክ</label>
          <input type="text" name="phone" class="form-control" placeholder="09...">
        </div>
        <div class="mb-3">
          <label class="form-label fw-bold">ልዩ ሙያ / ስራ</label>
          <input type="text" name="position" class="form-control" placeholder="ለምሳሌ: ሰፊ, ቆራጭ, ፊኒሸር">
        </div>
        <div class="mb-3">
          <label class="form-label fw-bold">የመጀመሪያ ደረጃ *</label>
          <select name="level" class="form-select form-select-lg" required>
            <?php foreach ($levels as $lv => $lvData): ?>
              <option value="<?= $lv ?>"><?= $lv ?> — <?= sanitize($lvData['label']) ?> (<?= money($lvData['daily_rate']) ?>/ቀን)</option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">ደረጃ ኋላ ላይ ከፍ ወይም ዝቅ ማድረግ ይቻላል።</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ሰርዝ</button>
        <button type="submit" class="btn btn-primary btn-lg">ጨምር</button>
      </div>
    </form>
  </div></div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <div class="modal-header bg-warning">
      <h5 class="modal-title">✏️ ሰራተኛ አስተካክል</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="editId">
      <div class="modal-body">
        <div class="alert alert-info py-2" style="font-size:.82rem">
          <i class="bi bi-info-circle me-1"></i>ደረጃ ለመቀየር ▲ ከፍ / ▼ ዝቅ ቁልፍ ይጠቀሙ።
        </div>
        <div class="mb-3">
          <label class="form-label fw-bold">ሙሉ ስም *</label>
          <input type="text" name="full_name" id="editName" class="form-control form-control-lg" required>
        </div>
        <div class="mb-3">
          <label class="form-label fw-bold">ስልክ</label>
          <input type="text" name="phone" id="editPhone" class="form-control">
        </div>
        <div class="mb-3">
          <label class="form-label fw-bold">ልዩ ሙያ / ስራ</label>
          <input type="text" name="position" id="editPos" class="form-control">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ሰርዝ</button>
        <button type="submit" class="btn btn-warning btn-lg">አስቀምጥ</button>
      </div>
    </form>
  </div></div>
</div>

<script>
document.getElementById('editModal').addEventListener('show.bs.modal', e => {
  const b = e.relatedTarget;
  document.getElementById('editId').value    = b.dataset.id;
  document.getElementById('editName').value  = b.dataset.name;
  document.getElementById('editPhone').value = b.dataset.phone;
  document.getElementById('editPos').value   = b.dataset.pos;
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>