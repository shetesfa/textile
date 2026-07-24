<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('owner');

$weekStart = $_GET['week'] ?? getThisMonday();
$weekDates = getWeekDates($weekStart);
$prevWeek  = date('Y-m-d', strtotime($weekStart . ' -7 days'));
$nextWeek  = date('Y-m-d', strtotime($weekStart . ' +7 days'));

$employees = $pdo->query("SELECT * FROM employees WHERE is_active=1 ORDER BY full_name")->fetchAll();

$totalEmp  = count($employees);
$todayDate = date('Y-m-d');

$todayStmt = $pdo->prepare("SELECT status, COUNT(*) as cnt FROM attendance WHERE work_date=? GROUP BY status");
$todayStmt->execute([$todayDate]);
$todayCounts = [];
foreach ($todayStmt->fetchAll() as $r) { $todayCounts[$r['status']] = $r['cnt']; }
$presentToday = $todayCounts['present'] ?? 0;
$absentToday  = $totalEmp - $presentToday;

$ph = implode(',', array_fill(0, count($weekDates), '?'));

$weekEarnStmt = $pdo->prepare("SELECT SUM(daily_rate) FROM attendance WHERE work_date IN ($ph) AND status='present'");
$weekEarnStmt->execute($weekDates);
$weekTotalEarned = (float)$weekEarnStmt->fetchColumn();

$attRows    = [];
$dailyRates = [];
if ($employees) {
    $stmt = $pdo->prepare("SELECT employee_id, work_date, status, daily_rate FROM attendance WHERE work_date IN ($ph)");
    $stmt->execute($weekDates);
    foreach ($stmt->fetchAll() as $row) {
        $attRows[$row['employee_id']][$row['work_date']]    = $row['status'];
        $dailyRates[$row['employee_id']][$row['work_date']] = (float)$row['daily_rate'];
    }
}

// Feature 1 — Total Unpaid per employee
$unpaidMap = [];
if ($employees) {
    $empIds = array_column($employees, 'id');
    $inPh   = implode(',', array_fill(0, count($empIds), '?'));

    $earnedStmt = $pdo->prepare(
        "SELECT employee_id, COALESCE(SUM(daily_rate),0) AS total
         FROM attendance WHERE status='present' AND employee_id IN ($inPh) GROUP BY employee_id"
    );
    $earnedStmt->execute($empIds);
    $earnedMap = [];
    foreach ($earnedStmt->fetchAll() as $r) { $earnedMap[$r['employee_id']] = (float)$r['total']; }

    $paidStmt = $pdo->prepare(
        "SELECT employee_id, COALESCE(SUM(amount),0) AS total
         FROM payments WHERE employee_id IN ($inPh) GROUP BY employee_id"
    );
    $paidStmt->execute($empIds);
    $paidMap = [];
    foreach ($paidStmt->fetchAll() as $r) { $paidMap[$r['employee_id']] = (float)$r['total']; }

    foreach ($empIds as $eid) {
        $unpaidMap[$eid] = ($earnedMap[$eid] ?? 0) - ($paidMap[$eid] ?? 0);
    }
}

function unpaidWeeks(float $unpaid, float $dailyRate): int {
    if ($dailyRate <= 0 || $unpaid <= 0) return 0;
    return (int)floor($unpaid / ($dailyRate * 6));
}

// Ethiopian day names short
function ethDayShort(string $gDate): string {
    $dow = (int)date('N', strtotime($gDate)); // 1=Mon … 6=Sat
    $names = [1=>'ሰኞ', 2=>'ማክሰ', 3=>'ረቡ', 4=>'ሐሙ', 5=>'አርብ', 6=>'ቅዳሜ', 7=>'እሁድ'];
    return $names[$dow] ?? '';
}

// Ethiopian day number only (day of month)
function ethDayNum(string $gDate): string {
    [$y, $m, $d] = explode('-', $gDate);
    $eth = gregorianToEthiopian((int)$y, (int)$m, (int)$d);
    return (string)$eth['day'];
}

$pageTitle = 'Attendance Report';
include __DIR__ . '/../includes/header.php';
?>

<!-- Summary Cards -->
<div class="row g-2 g-md-3 mb-3 mb-md-4">
  <div class="col-6 col-lg-3">
    <div class="att-stat-card att-stat-blue">
      <div class="att-stat-icon"><i class="bi bi-people-fill"></i></div>
      <div class="att-stat-val"><?= $totalEmp ?></div>
      <div class="att-stat-label">ጠቅላላ ሰራተኞች</div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="att-stat-card att-stat-green">
      <div class="att-stat-icon"><i class="bi bi-person-check-fill"></i></div>
      <div class="att-stat-val"><?= $presentToday ?></div>
      <div class="att-stat-label">ዛሬ የቀረቡ</div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="att-stat-card att-stat-red">
      <div class="att-stat-icon"><i class="bi bi-person-x-fill"></i></div>
      <div class="att-stat-val"><?= $absentToday ?></div>
      <div class="att-stat-label">ዛሬ የሌሉ</div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="att-stat-card att-stat-amber">
      <div class="att-stat-icon"><i class="bi bi-cash-stack"></i></div>
      <div class="att-stat-val" style="font-size:1.2rem"><?= number_format($weekTotalEarned) ?></div>
      <div class="att-stat-label">የሳምንት ክፍያ (ETB)</div>
    </div>
  </div>
</div>

<!-- Week Navigation -->
<div class="att-section-card mb-3 mb-md-4">
  <div class="att-section-head">
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <a href="?week=<?= $prevWeek ?>" class="att-nav-btn"><i class="bi bi-chevron-left"></i></a>
      <span class="att-week-label">
        <i class="bi bi-calendar3 me-1"></i><?= formatEthDate($weekStart) ?>
      </span>
      <?php if ($nextWeek <= date('Y-m-d')): ?>
        <a href="?week=<?= $nextWeek ?>" class="att-nav-btn"><i class="bi bi-chevron-right"></i></a>
      <?php else: ?>
        <span class="att-nav-btn att-nav-btn--disabled"><i class="bi bi-chevron-right"></i></span>
      <?php endif; ?>
      <a href="?week=<?= getThisMonday() ?>" class="btn btn-sm btn-outline-primary ms-1">ይህ ሳምንት</a>
    </div>
    <span class="badge bg-secondary-subtle text-secondary px-3 py-2">
      <i class="bi bi-eye me-1"></i>እይታ ብቻ
    </span>
  </div>
</div>

<!-- Attendance Table -->
<div class="att-section-card">
  <div class="att-section-head">
    <h5 class="att-section-title"><i class="bi bi-calendar-week me-2"></i>የሳምንት የስራ መዝገብ</h5>
    <small class="text-muted eth-font">
      <?= formatEthDate($weekDates[0]) ?> – <?= formatEthDate(end($weekDates)) ?>
    </small>
  </div>

  <div class="table-responsive">
    <table class="att-table">
      <thead>
        <tr>
          <th class="att-th-name">ሰራተኛ</th>
          <th class="att-th-lvl">ደረጃ</th>
          <?php foreach ($weekDates as $d): ?>
            <th class="att-th-day <?= $d === $todayDate ? 'att-th-today' : '' ?>">
              <span class="att-dayname eth-font"><?= ethDayShort($d) ?></span>
              <span class="att-daynum eth-font"><?= ethDayNum($d) ?></span>
            </th>
          <?php endforeach; ?>
          <th class="att-th-total">ቀናት</th>
          <th class="att-th-earn">ሳምንቱ<br><small class="fw-normal">ETB</small></th>
          <th class="att-th-unpaid">ያልተከፈለ<br><small class="fw-normal">ጠቅላላ</small></th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$employees): ?>
        <tr><td colspan="11" class="att-empty">ምንም ሰራተኛ አልተገኘም።</td></tr>
      <?php else: foreach ($employees as $emp):
          $days = 0; $weekEarned = 0;
          foreach ($weekDates as $d) {
              if (($attRows[$emp['id']][$d] ?? null) === 'present') {
                  $days++;
                  $weekEarned += $dailyRates[$emp['id']][$d] ?? 0;
              }
          }
          $unpaid   = $unpaidMap[$emp['id']] ?? 0;
          $allEarned = 0; $allDays = 0;
          foreach (($dailyRates[$emp['id']] ?? []) as $dr) {
              if ($dr > 0) { $allEarned += $dr; $allDays++; }
          }
          $avgRate = $allDays > 0 ? ($allEarned / $allDays) : 180;
          $weeks   = unpaidWeeks($unpaid, $avgRate);
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
            $rec = $attRows[$emp['id']][$d] ?? null;
          ?>
            <td class="att-td-cell <?= $d === $todayDate ? 'att-td-today' : '' ?>">
              <?php if ($rec === 'present'): ?>
                <span class="att-cell-badge att-cell-present">P</span>
              <?php elseif ($rec === 'absent'): ?>
                <span class="att-cell-badge att-cell-absent">A</span>
              <?php else: ?>
                <span class="att-cell-empty">—</span>
              <?php endif; ?>
            </td>
          <?php endforeach; ?>
          <td class="att-td-total">
            <span class="att-days-chip <?= $days >= 5 ? 'att-days-full' : ($days >= 3 ? 'att-days-mid' : 'att-days-low') ?>">
              <?= $days ?>
            </span>
          </td>
          <td class="att-td-earn"><?= $weekEarned > 0 ? number_format($weekEarned) : '–' ?></td>
          <td class="att-td-unpaid">
            <?php if ($unpaid <= 0): ?>
              <span class="unpaid-badge unpaid-ok">✓ ተከፍሏል</span>
            <?php else: ?>
              <span class="unpaid-badge unpaid-w<?= min($weeks, 4) ?>">
                <?= number_format($unpaid) ?> ETB
                <?php if ($weeks >= 1): ?>
                  <small class="unpaid-weeks"><?= $weeks ?> ሳምንት</small>
                <?php endif; ?>
              </span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($employees): ?>
  <div class="att-table-footer">
    <span class="text-muted"><?= $totalEmp ?> ሰራተኞች &middot; እይታ ብቻ</span>
    <strong class="text-success">ጠቅላላ: <?= number_format($weekTotalEarned) ?> ETB</strong>
  </div>
  <?php endif; ?>
</div>

<style>
/* ── Mobile first ── */
.eth-font { font-family: 'Noto Sans Ethiopic', sans-serif; }

.att-stat-card {
  border-radius: 12px; padding: 14px 12px;
  border: 1px solid var(--border); background: var(--card-bg);
  display: flex; flex-direction: column; gap: 3px;
  transition: transform .15s, box-shadow .15s;
}
.att-stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,.08); }
.att-stat-icon  { font-size: 1.4rem; margin-bottom: 2px; }
.att-stat-val   { font-size: 1.6rem; font-weight: 700; line-height: 1; }
.att-stat-label { font-size: .72rem; color: var(--muted); font-weight: 500; margin-top: 2px;
                  font-family: 'Noto Sans Ethiopic', sans-serif; }
.att-stat-blue  { border-left: 4px solid #3b82f6; }
.att-stat-blue  .att-stat-icon { color: #3b82f6; }
.att-stat-green { border-left: 4px solid #10b981; }
.att-stat-green .att-stat-icon { color: #10b981; }
.att-stat-red   { border-left: 4px solid #ef4444; }
.att-stat-red   .att-stat-icon { color: #ef4444; }
.att-stat-amber { border-left: 4px solid #f59e0b; }
.att-stat-amber .att-stat-icon { color: #f59e0b; }

.att-section-card {
  background: var(--card-bg); border-radius: 12px;
  border: 1px solid var(--border); overflow: hidden;
  box-shadow: 0 1px 4px rgba(0,0,0,.05);
}
.att-section-head {
  padding: 12px 14px; border-bottom: 1px solid var(--border);
  display: flex; align-items: center; justify-content: space-between;
  flex-wrap: wrap; gap: 8px;
}
.att-section-title { margin: 0; font-size: .9rem; font-weight: 600; }

.att-nav-btn {
  display: inline-flex; align-items: center; justify-content: center;
  width: 32px; height: 32px; border-radius: 8px;
  border: 1px solid var(--border); background: var(--card-bg);
  color: var(--text); text-decoration: none; font-size: .9rem; transition: all .15s;
}
.att-nav-btn:hover { background: var(--primary); color: #fff; border-color: var(--primary); }
.att-nav-btn--disabled { opacity: .35; pointer-events: none; }
.att-week-label { font-size: .85rem; font-weight: 600; color: var(--primary);
                  font-family: 'Noto Sans Ethiopic', sans-serif; }

/* Table */
.att-table { width: 100%; border-collapse: collapse; font-size: .78rem; }
.att-table thead tr { background: #f8fafc; }
.att-table th {
  padding: 8px 5px; text-align: center;
  color: var(--muted); font-size: .65rem; font-weight: 600;
  text-transform: uppercase; letter-spacing: .03em;
  border-bottom: 2px solid var(--border); white-space: nowrap;
}
.att-th-name   { text-align: left; min-width: 120px; padding-left: 10px; }
.att-th-lvl    { min-width: 36px; }
.att-th-day    { min-width: 34px; }
.att-th-today  { background: #eff6ff; color: #1d4ed8 !important; }
.att-th-total  { min-width: 40px; }
.att-th-earn   { min-width: 72px; text-align: right; padding-right: 8px; }
.att-th-unpaid { min-width: 90px; text-align: right; padding-right: 8px; }

/* Ethiopian date in column header */
.att-dayname {
  display: block; font-size: .72rem; font-weight: 700;
  font-family: 'Noto Sans Ethiopic', sans-serif; line-height: 1.2;
}
.att-daynum {
  display: block; font-size: .7rem; color: var(--muted);
  font-family: 'Noto Sans Ethiopic', sans-serif; margin-top: 2px;
}

.att-table td { padding: 8px 5px; border-bottom: 1px solid var(--border); vertical-align: middle; }
.att-row:last-child td { border-bottom: none; }
.att-row:hover { background: #f8fafc; }

.att-td-name   { text-align: left; display: flex; align-items: center; gap: 8px; padding-left: 10px; }
.att-td-lvl    { text-align: center; }
.att-td-cell   { text-align: center; }
.att-td-today  { background: #f0f9ff; }
.att-td-total  { text-align: center; }
.att-td-earn   { text-align: right; font-weight: 600; color: var(--success); white-space: nowrap; padding-right: 8px; }
.att-td-unpaid { text-align: right; white-space: nowrap; padding-right: 8px; }

.att-emp-avatar {
  width: 30px; height: 30px; border-radius: 50%; flex-shrink: 0;
  background: linear-gradient(135deg, var(--primary), #3b82f6);
  color: #fff; font-weight: 700; font-size: .8rem;
  display: flex; align-items: center; justify-content: center;
}
.att-emp-name { font-weight: 600; font-size: .83rem; line-height: 1.2; }
.att-emp-pos  { font-size: .7rem; color: var(--muted); }

.att-level-badge {
  display: inline-block; padding: 2px 6px; border-radius: 20px;
  background: #f1f5f9; color: #475569; font-size: .68rem; font-weight: 700;
}

.att-cell-badge {
  display: inline-flex; align-items: center; justify-content: center;
  width: 26px; height: 26px; border-radius: 7px; font-size: .75rem; font-weight: 700;
}
.att-cell-present { background: #d1fae5; color: #065f46; }
.att-cell-absent  { background: #fee2e2; color: #991b1b; }
.att-cell-empty   { color: #d1d5db; font-size: .8rem; }

.att-days-chip {
  display: inline-block; padding: 2px 7px; border-radius: 20px;
  font-size: .78rem; font-weight: 700;
}
.att-days-full { background: #d1fae5; color: #065f46; }
.att-days-mid  { background: #fef3c7; color: #92400e; }
.att-days-low  { background: #fee2e2; color: #991b1b; }

/* Unpaid badges */
.unpaid-badge {
  display: inline-flex; align-items: center; gap: 4px;
  padding: 3px 7px; border-radius: 8px;
  font-size: .7rem; font-weight: 700; white-space: nowrap;
}
.unpaid-ok  { background: #f0fdf4; color: #166534; }
.unpaid-w0  { background: #f1f5f9; color: #475569; }
.unpaid-w1  { background: #f1f5f9; color: #475569; }
.unpaid-w2  { background: #fef9c3; color: #854d0e; }
.unpaid-w3  { background: #ffedd5; color: #9a3412; }
.unpaid-w4  { background: #fee2e2; color: #991b1b; }
.unpaid-weeks { font-size: .63rem; opacity: .8;
                font-family: 'Noto Sans Ethiopic', sans-serif; }

.att-table-footer {
  padding: 10px 14px; border-top: 1px solid var(--border);
  display: flex; align-items: center; justify-content: space-between;
  flex-wrap: wrap; gap: 8px; background: #f8fafc; font-size: .82rem;
}
.att-empty { text-align: center; padding: 40px 16px; color: var(--muted); font-size: .88rem; }

/* Desktop enhancements */
@media (min-width: 768px) {
  .att-stat-card  { padding: 20px 18px; }
  .att-stat-icon  { font-size: 1.6rem; }
  .att-stat-val   { font-size: 2rem; }
  .att-stat-label { font-size: .78rem; }
  .att-section-head  { padding: 14px 20px; }
  .att-section-title { font-size: .95rem; }
  .att-table    { font-size: .83rem; }
  .att-table th { padding: 10px 10px; font-size: .7rem; }
  .att-table td { padding: 10px 10px; }
  .att-th-name  { min-width: 200px; }
  .att-th-day   { min-width: 48px; }
  .att-emp-avatar { width: 36px; height: 36px; font-size: .9rem; }
  .att-emp-name   { font-size: .88rem; }
  .att-cell-badge { width: 28px; height: 28px; }
  .att-nav-btn    { width: 34px; height: 34px; }
  .att-week-label { font-size: .9rem; }
  .att-dayname    { font-size: .78rem; }
  .att-daynum     { font-size: .72rem; }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>