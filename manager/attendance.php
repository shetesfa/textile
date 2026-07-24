<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('manager');

$weekStart = $_GET['week'] ?? getThisMonday();
$weekDates = getWeekDates($weekStart);
$prevWeek  = date('Y-m-d', strtotime($weekStart . ' -7 days'));
$nextWeek  = date('Y-m-d', strtotime($weekStart . ' +7 days'));
$todayDate = date('Y-m-d');

if ($weekStart > $todayDate) {
    flash('warning', 'Future attendance cannot be edited.');
    header("Location: attendance.php"); exit;
}

// Save attendance — manager can edit all past/current dates
// 3 states per cell: 'present' | 'absent' | 'unset' (skip unset)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    $savedWeek = $_POST['week_start'] ?? $weekStart;
    if ($savedWeek > $todayDate) {
        flash('danger', 'Cannot save future attendance.');
        header("Location: attendance.php?week=$savedWeek"); exit;
    }
    $dates     = getWeekDates($savedWeek);
    $employees = $pdo->query("SELECT id, level FROM employees WHERE is_active=1")->fetchAll();
    foreach ($employees as $emp) {
        $rate = getLevelRate($pdo, $emp['level']);
        foreach ($dates as $d) {
            if ($d > $todayDate) continue;
            $key = 'att_' . $emp['id'] . '_' . str_replace('-', '', $d);
            $val = $_POST[$key] ?? 'unset';
            if ($val === 'unset') continue; // don't write unset
            $status = ($val === 'present') ? 'present' : 'absent';
            $pdo->prepare(
                "INSERT INTO attendance (employee_id, work_date, status, daily_rate, recorded_by)
                 VALUES (?,?,?,?,?)
                 ON DUPLICATE KEY UPDATE status=VALUES(status), daily_rate=VALUES(daily_rate), recorded_by=VALUES(recorded_by)"
            )->execute([$emp['id'], $d, $status, $status === 'present' ? $rate : 0, $_SESSION['user_id']]);
        }
    }
    flash('success', "Attendance saved for week of " . formatEthDate($savedWeek));
    header("Location: attendance.php?week=$savedWeek"); exit;
}

$employees = $pdo->query("SELECT * FROM employees WHERE is_active=1 ORDER BY full_name")->fetchAll();

$totalEmp  = count($employees);
$todayStmt = $pdo->prepare("SELECT status, COUNT(*) as cnt FROM attendance WHERE work_date=? GROUP BY status");
$todayStmt->execute([$todayDate]);
$todayCounts = [];
foreach ($todayStmt->fetchAll() as $r) { $todayCounts[$r['status']] = $r['cnt']; }
$presentToday = $todayCounts['present'] ?? 0;
$absentToday  = $todayCounts['absent']  ?? 0;

// Week payroll
$ph = implode(',', array_fill(0, count($weekDates), '?'));
$weekEarnStmt = $pdo->prepare("SELECT SUM(daily_rate) FROM attendance WHERE work_date IN ($ph) AND status='present'");
$weekEarnStmt->execute($weekDates);
$weekTotalEarned = (float)$weekEarnStmt->fetchColumn();

// Load attendance — null = never recorded = show empty
$attRows = [];
if ($employees && $weekDates) {
    $stmt = $pdo->prepare("SELECT employee_id, work_date, status FROM attendance WHERE work_date IN ($ph)");
    $stmt->execute($weekDates);
    foreach ($stmt->fetchAll() as $r) {
        $attRows[$r['employee_id']][$r['work_date']] = $r['status'];
    }
}

$pageTitle = 'Manage Attendance';
include __DIR__ . '/../includes/header.php';
?>

<!-- ── Summary Cards ── -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="att-stat-card att-stat-blue">
      <div class="att-stat-icon"><i class="bi bi-people-fill"></i></div>
      <div class="att-stat-val"><?= $totalEmp ?></div>
      <div class="att-stat-label">Total Employees</div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="att-stat-card att-stat-green">
      <div class="att-stat-icon"><i class="bi bi-person-check-fill"></i></div>
      <div class="att-stat-val"><?= $presentToday ?></div>
      <div class="att-stat-label">Present Today</div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="att-stat-card att-stat-red">
      <div class="att-stat-icon"><i class="bi bi-person-x-fill"></i></div>
      <div class="att-stat-val"><?= $absentToday ?></div>
      <div class="att-stat-label">Absent Today</div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="att-stat-card att-stat-amber">
      <div class="att-stat-icon"><i class="bi bi-cash-stack"></i></div>
      <div class="att-stat-val" style="font-size:1.3rem"><?= number_format($weekTotalEarned) ?></div>
      <div class="att-stat-label">Week Payroll (ETB)</div>
    </div>
  </div>
</div>

<form method="POST" id="attForm">
  <input type="hidden" name="action" value="save">
  <input type="hidden" name="week_start" value="<?= $weekStart ?>">

  <!-- ── Week Navigation ── -->
  <div class="att-section-card mb-4">
    <div class="att-section-head">
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="?week=<?= $prevWeek ?>" class="att-nav-btn"><i class="bi bi-chevron-left"></i></a>
        <span class="att-week-label"><i class="bi bi-calendar3 me-1"></i><?= formatEthDate($weekStart) ?></span>
        <?php if ($nextWeek <= $todayDate): ?>
          <a href="?week=<?= $nextWeek ?>" class="att-nav-btn"><i class="bi bi-chevron-right"></i></a>
        <?php else: ?>
          <span class="att-nav-btn att-nav-btn--disabled"><i class="bi bi-chevron-right"></i></span>
        <?php endif; ?>
        <a href="?week=<?= getThisMonday() ?>" class="btn btn-sm btn-outline-primary ms-1">This Week</a>
      </div>
      <div class="d-flex gap-2 align-items-center flex-wrap">
        <button type="button" class="att-quick-btn att-quick-all-present" onclick="markAll('present')">
          <i class="bi bi-check-all"></i> All Present
        </button>
        <button type="button" class="att-quick-btn att-quick-all-absent" onclick="markAll('absent')">
          <i class="bi bi-x-lg"></i> All Absent
        </button>
        <button type="submit" class="btn btn-success px-4">
          <i class="bi bi-save me-1"></i> Save
        </button>
      </div>
    </div>
  </div>

  <!-- ── Legend ── -->
  <div class="att-legend mb-3">
    <span class="att-cell-badge att-cell-none" style="width:auto;padding:0 10px">Empty</span> Not recorded &nbsp;·&nbsp;
    <span class="att-cell-badge att-cell-present" style="width:auto;padding:0 10px">P</span> Present &nbsp;·&nbsp;
    <span class="att-cell-badge att-cell-absent" style="width:auto;padding:0 10px">A</span> Absent &nbsp;·&nbsp;
    <i class="bi bi-lock-fill text-muted"></i> Future locked
  </div>

  <!-- ── Attendance Table ── -->
  <div class="att-section-card">
    <div class="att-section-head">
      <h5 class="att-section-title"><i class="bi bi-calendar-week me-2"></i>Weekly Attendance Sheet</h5>
      <small class="text-muted">Tap any cell to cycle: <strong>empty → P → A → empty</strong></small>
    </div>

    <div class="table-responsive">
      <table class="att-table">
        <thead>
          <tr>
            <th class="att-th-name">Employee</th>
            <th class="att-th-lvl">Lvl</th>
            <?php foreach ($weekDates as $d): ?>
              <th class="att-th-day <?= $d === $todayDate ? 'att-th-today' : '' ?> <?= $d > $todayDate ? 'att-th-future' : '' ?>">
                <span class="att-dayname"><?= getDayShort($d) ?></span>
                <span class="att-daynum"><?= getDayNum($d) ?></span>
                <?php if ($d > $todayDate): ?>
                  <span class="att-col-tag att-col-lock"><i class="bi bi-lock-fill"></i></span>
                <?php elseif ($d === $todayDate): ?>
                  <span class="att-col-tag att-col-today">today</span>
                <?php endif; ?>
              </th>
            <?php endforeach; ?>
            <th class="att-th-total">Days</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$employees): ?>
          <tr><td colspan="9" class="att-empty">No employees found. Add employees first.</td></tr>
        <?php else: foreach ($employees as $emp):
            $days = 0;
            foreach ($weekDates as $d) {
                if (($attRows[$emp['id']][$d] ?? null) === 'present') $days++;
            }
        ?>
          <tr class="att-row">
            <td class="att-td-name">
              <div class="att-emp-avatar"><?= mb_substr($emp['full_name'], 0, 1) ?></div>
              <div>
                <div class="att-emp-name"><?= sanitize($emp['full_name']) ?></div>
                <div class="att-emp-pos"><?= sanitize($emp['position'] ?? '') ?></div>
              </div>
            </td>
            <td class="att-td-lvl"><span class="att-level-badge">L<?= $emp['level'] ?></span></td>

            <?php foreach ($weekDates as $d):
              $key      = 'att_' . $emp['id'] . '_' . str_replace('-', '', $d);
              $recorded = $attRows[$emp['id']][$d] ?? null; // null = never recorded
              $isFuture = ($d > $todayDate);
              $isToday  = ($d === $todayDate);
            ?>

              <?php if ($isFuture): ?>
                <!-- FUTURE: locked, no input -->
                <td class="att-td-cell att-td-future">
                  <span class="att-cell-badge att-cell-lock"><i class="bi bi-lock-fill"></i></span>
                </td>

              <?php else: ?>
                <!-- PAST or TODAY: manager can edit all -->
                <td class="att-td-cell <?= $isToday ? 'att-td-today' : 'att-td-past' ?> att-td-clickable"
                    data-state="<?= $recorded ?? 'unset' ?>"
                    data-key="<?= $key ?>"
                    data-emp="<?= $emp['id'] ?>"
                    onclick="cycleCell(this)">
                  <input type="hidden" name="<?= $key ?>" value="<?= $recorded ?? 'unset' ?>" class="att-state-input">
                  <span class="att-cell-badge <?= $recorded === 'present' ? 'att-cell-present' : ($recorded === 'absent' ? 'att-cell-absent' : 'att-cell-none') ?>">
                    <?= $recorded === 'present' ? 'P' : ($recorded === 'absent' ? 'A' : '') ?>
                  </span>
                </td>
              <?php endif; ?>

            <?php endforeach; ?>

            <td class="att-td-total">
              <span class="att-days-chip" id="days_<?= $emp['id'] ?>"><?= $days ?></span>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($employees): ?>
    <div class="att-table-footer">
      <span class="text-muted" style="font-size:.82rem"><?= $totalEmp ?> employees &middot; Week payroll: <strong class="text-success"><?= number_format($weekTotalEarned) ?> ETB</strong></span>
      <div class="d-flex gap-2 flex-wrap">
        <button type="button" class="att-quick-btn att-quick-all-present" onclick="markAll('present')">
          <i class="bi bi-check-all"></i> All Present
        </button>
        <button type="button" class="att-quick-btn att-quick-all-absent" onclick="markAll('absent')">
          <i class="bi bi-x-lg"></i> All Absent
        </button>
        <button type="submit" class="btn btn-success px-5">
          <i class="bi bi-save me-1"></i> Save Attendance
        </button>
      </div>
    </div>
    <?php endif; ?>
  </div>
</form>

<style>
.att-stat-card{border-radius:14px;padding:20px 18px;border:1px solid var(--border);background:var(--card-bg);display:flex;flex-direction:column;gap:4px;transition:transform .15s,box-shadow .15s;}
.att-stat-card:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,.08);}
.att-stat-icon{font-size:1.6rem;margin-bottom:4px;}
.att-stat-val{font-size:2rem;font-weight:700;line-height:1;}
.att-stat-label{font-size:.78rem;color:var(--muted);font-weight:500;margin-top:2px;}
.att-stat-blue{border-left:4px solid #3b82f6;}.att-stat-blue .att-stat-icon{color:#3b82f6;}
.att-stat-green{border-left:4px solid #10b981;}.att-stat-green .att-stat-icon{color:#10b981;}
.att-stat-red{border-left:4px solid #ef4444;}.att-stat-red .att-stat-icon{color:#ef4444;}
.att-stat-amber{border-left:4px solid #f59e0b;}.att-stat-amber .att-stat-icon{color:#f59e0b;}
.att-legend{display:flex;align-items:center;gap:8px;flex-wrap:wrap;font-size:.82rem;color:var(--muted);padding:0 2px;}
.att-section-card{background:var(--card-bg);border-radius:14px;border:1px solid var(--border);overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.05);}
.att-section-head{padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;}
.att-section-title{margin:0;font-size:.95rem;font-weight:600;}
.att-nav-btn{display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:8px;border:1px solid var(--border);background:var(--card-bg);color:var(--text);text-decoration:none;font-size:1rem;transition:all .15s;}
.att-nav-btn:hover{background:var(--primary);color:#fff;border-color:var(--primary);}
.att-nav-btn--disabled{opacity:.35;pointer-events:none;}
.att-week-label{font-size:.9rem;font-weight:600;color:var(--primary);}
.att-quick-btn{display:inline-flex;align-items:center;gap:5px;padding:6px 14px;border-radius:8px;font-size:.82rem;font-weight:600;cursor:pointer;border:1px solid;transition:all .15s;}
.att-quick-all-present{background:#d1fae5;color:#065f46;border-color:#a7f3d0;}.att-quick-all-present:hover{background:#a7f3d0;}
.att-quick-all-absent{background:#fee2e2;color:#991b1b;border-color:#fca5a5;}.att-quick-all-absent:hover{background:#fca5a5;}
.att-table{width:100%;border-collapse:collapse;font-size:.83rem;}
.att-table thead tr{background:#f8fafc;}
.att-table th{padding:10px 12px;text-align:center;color:var(--muted);font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;border-bottom:2px solid var(--border);white-space:nowrap;}
.att-th-name{text-align:left;min-width:180px;}
.att-th-lvl{min-width:48px;}
.att-th-day{min-width:58px;}
.att-th-today{background:#eff6ff;color:#1d4ed8 !important;}
.att-th-future{background:#f9fafb;color:#9ca3af !important;}
.att-th-total{min-width:56px;}
.att-dayname{display:block;}
.att-daynum{display:block;font-size:.78rem;color:var(--muted);font-weight:400;margin-top:1px;}
.att-col-tag{display:block;font-size:.62rem;margin-top:3px;font-weight:600;letter-spacing:.03em;}
.att-col-today{color:#1d4ed8;}
.att-col-lock{color:#d1d5db;}
.att-table td{padding:10px 10px;border-bottom:1px solid var(--border);vertical-align:middle;}
.att-row:last-child td{border-bottom:none;}
.att-row:hover{background:#f8fafc;}
.att-td-name{text-align:left;display:flex;align-items:center;gap:10px;}
.att-td-lvl{text-align:center;}
.att-td-cell{text-align:center;position:relative;}
.att-td-today{background:#f0f9ff;}
.att-td-past{background:#fafafa;}
.att-td-clickable{cursor:pointer;user-select:none;transition:background .12s;}
.att-td-clickable:hover{background:#dbeafe;}
.att-td-clickable:active{background:#bfdbfe;}
.att-td-future{background:#f9fafb;opacity:.5;cursor:not-allowed;}
.att-td-total{text-align:center;}
.att-cell-badge{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:9px;font-size:.82rem;font-weight:700;transition:transform .1s,background .15s;}
.att-cell-present{background:#d1fae5;color:#065f46;}
.att-cell-absent{background:#fee2e2;color:#991b1b;}
.att-cell-none{background:transparent;color:#cbd5e1;border:2px dashed #e2e8f0;font-size:.65rem;}
.att-cell-lock{background:#f1f5f9;color:#cbd5e1;font-size:.75rem;}
.att-cell-badge.pulse{transform:scale(1.25);}
.att-emp-avatar{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--primary),#3b82f6);color:#fff;font-weight:700;font-size:.9rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.att-emp-name{font-weight:600;font-size:.88rem;}
.att-emp-pos{font-size:.75rem;color:var(--muted);}
.att-level-badge{display:inline-block;padding:2px 8px;border-radius:20px;background:#f1f5f9;color:#475569;font-size:.73rem;font-weight:600;}
.att-days-chip{display:inline-block;padding:3px 10px;border-radius:20px;font-size:.82rem;font-weight:700;min-width:32px;text-align:center;background:#f1f5f9;color:#334155;transition:background .2s,color .2s;}
.att-days-chip.has-days{background:#d1fae5;color:#065f46;}
.att-table-footer{padding:12px 20px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;background:#f8fafc;font-size:.85rem;}
.att-empty{text-align:center;padding:48px 20px;color:var(--muted);font-size:.9rem;}
@media(max-width:768px){
  .att-stat-card{padding:14px;}.att-stat-val{font-size:1.6rem;}
  .att-section-head{padding:12px 14px;}
  .att-table th,.att-table td{padding:8px 6px;}
  .att-emp-avatar{width:30px;height:30px;font-size:.78rem;}
  .att-emp-pos{display:none;}
  .att-quick-btn{padding:5px 10px;font-size:.78rem;}
  .att-cell-badge{width:28px;height:28px;font-size:.78rem;}
  .att-legend{font-size:.75rem;}
}
</style>

<script>
const STATES     = ['unset', 'present', 'absent'];
const BADGE_CLASS = { unset:'att-cell-none', present:'att-cell-present', absent:'att-cell-absent' };
const BADGE_TEXT  = { unset:'', present:'P', absent:'A' };

function cycleCell(td) {
  const input  = td.querySelector('.att-state-input');
  const badge  = td.querySelector('.att-cell-badge');
  const empId  = td.dataset.emp;

  let next = STATES[(STATES.indexOf(td.dataset.state) + 1) % STATES.length];

  td.dataset.state  = next;
  input.value       = next;
  badge.className   = 'att-cell-badge ' + BADGE_CLASS[next];
  badge.textContent = BADGE_TEXT[next];

  badge.classList.add('pulse');
  setTimeout(() => badge.classList.remove('pulse'), 120);

  updateDaysChip(empId);
}

function updateDaysChip(empId) {
  const row  = document.querySelector(`[data-emp="${empId}"]`).closest('tr');
  const cells = row.querySelectorAll('.att-td-clickable');
  let days = 0;
  cells.forEach(td => { if (td.dataset.state === 'present') days++; });
  const chip = document.getElementById('days_' + empId);
  chip.textContent = days;
  chip.className   = 'att-days-chip' + (days > 0 ? ' has-days' : '');
}

function markAll(state) {
  document.querySelectorAll('.att-td-clickable').forEach(td => {
    const input  = td.querySelector('.att-state-input');
    const badge  = td.querySelector('.att-cell-badge');
    td.dataset.state  = state;
    input.value       = state;
    badge.className   = 'att-cell-badge ' + BADGE_CLASS[state];
    badge.textContent = BADGE_TEXT[state];
    updateDaysChip(td.dataset.emp);
  });
}

document.querySelectorAll('[data-emp]').forEach(td => updateDaysChip(td.dataset.emp));
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>