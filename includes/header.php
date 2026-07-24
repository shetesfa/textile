<?php
// includes/header.php
// Usage: include with $pageTitle and $role already set
if (session_status() === PHP_SESSION_NONE) session_start();
$user = currentUser();
$role = $user['role'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= sanitize($pageTitle ?? 'ጨርቃጨርቅ') ?> | Textile Factory</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Ethiopic:wght@400;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
  --primary:   #1d6fa4;
  --primary-d: #155a87;
  --accent:    #f59e0b;
  --sidebar-w: 240px;
  --bg:        #f0f4f8;
  --card-bg:   #ffffff;
  --text:      #1e293b;
  --muted:     #64748b;
  --border:    #e2e8f0;
  --success:   #059669;
  --danger:    #dc2626;
  --warning:   #d97706;
}
*, *::before, *::after { box-sizing: border-box; }
body {
  font-family: 'Inter', 'Noto Sans Ethiopic', sans-serif;
  background: var(--bg);
  color: var(--text);
  margin: 0;
  min-height: 100vh;
}

/* ── Sidebar ── */
.sidebar {
  position: fixed; top: 0; left: 0;
  width: var(--sidebar-w);
  height: 100vh;
  background: #0f1e2d;
  color: #cbd5e1;
  display: flex; flex-direction: column;
  z-index: 1040;
  transition: transform .25s;
  overflow-y: auto;
}
.sidebar-brand {
  padding: 20px 18px 16px;
  border-bottom: 1px solid rgba(255,255,255,.08);
}
.sidebar-brand .brand-am {
  font-family: 'Noto Sans Ethiopic', sans-serif;
  font-size: 1.1rem; font-weight: 700;
  color: var(--accent);
  line-height: 1.2;
}
.sidebar-brand .brand-en {
  font-size: .72rem; color: #94a3b8; margin-top: 2px;
}
.sidebar-role {
  font-size: .7rem; text-transform: uppercase; letter-spacing: .08em;
  color: #64748b; padding: 14px 18px 6px;
}
.sidebar nav a {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 18px; color: #94a3b8;
  text-decoration: none; font-size: .88rem;
  border-left: 3px solid transparent;
  transition: all .15s;
}
.sidebar nav a:hover { background: rgba(255,255,255,.05); color: #fff; }
.sidebar nav a.active {
  background: rgba(29,111,164,.25);
  color: #fff; border-left-color: var(--accent);
}
.sidebar nav a i { font-size: 1.05rem; width: 18px; text-align: center; }
.sidebar-footer {
  margin-top: auto; padding: 14px 18px;
  border-top: 1px solid rgba(255,255,255,.07);
  font-size: .8rem; color: #475569;
}
.sidebar-footer a { color: #ef4444; text-decoration: none; font-size: .82rem; }
.sidebar-footer a:hover { text-decoration: underline; }

/* ── Main ── */
.main-wrap {
  margin-left: var(--sidebar-w);
  min-height: 100vh;
  display: flex; flex-direction: column;
}
.topbar {
  background: var(--card-bg);
  border-bottom: 1px solid var(--border);
  padding: 12px 24px;
  display: flex; align-items: center; justify-content: space-between;
  position: sticky; top: 0; z-index: 100;
}
.topbar-title { font-size: 1.1rem; font-weight: 600; }
.topbar-meta { font-size: .8rem; color: var(--muted); text-align: right; line-height: 1.4; }
.eth-date { font-family: 'Noto Sans Ethiopic', sans-serif; color: var(--primary); font-weight: 600; }
.page-body { padding: 24px; flex: 1; }

/* ── Cards ── */
.stat-card {
  background: var(--card-bg);
  border-radius: 12px; padding: 20px;
  border: 1px solid var(--border);
  display: flex; align-items: center; gap: 16px;
}
.stat-icon { font-size: 2.2rem; line-height: 1; }
.stat-val { font-size: 2rem; font-weight: 700; line-height: 1; }
.stat-label { font-size: .82rem; color: var(--muted); margin-top: 3px; }

/* ── Tables ── */
.table-card {
  background: var(--card-bg);
  border-radius: 12px; padding: 0;
  border: 1px solid var(--border);
  overflow: hidden;
}
.table-card .tc-head {
  padding: 16px 20px;
  border-bottom: 1px solid var(--border);
  display: flex; align-items: center; justify-content: space-between;
}
.table-card .tc-head h5 { margin: 0; font-size: .95rem; font-weight: 600; }
.table { margin: 0; font-size: .88rem; }
.table th { background: #f8fafc; color: var(--muted); font-size: .78rem;
  text-transform: uppercase; letter-spacing: .05em; font-weight: 600; border-top: none; }
.table td, .table th { padding: 11px 16px; vertical-align: middle; }

/* ── Status badges ── */
.status-badge {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 3px 10px; border-radius: 20px; font-size: .75rem; font-weight: 600;
  white-space: nowrap;
}
.status-new      { background: #eff6ff; color: #1d4ed8; }
.status-accepted { background: #f0fdf4; color: #16a34a; }
.status-working  { background: #fefce8; color: #a16207; }
.status-half     { background: #fff7ed; color: #c2410c; }
.status-finished { background: #f0fdf4; color: #059669; }

/* ── Buttons ── */
.btn { font-size: .88rem; font-weight: 500; border-radius: 8px; }
.btn-lg { font-size: 1rem; padding: 10px 22px; }
.btn-primary { background: var(--primary); border-color: var(--primary); }
.btn-primary:hover { background: var(--primary-d); border-color: var(--primary-d); }
.btn-accent { background: var(--accent); border-color: var(--accent); color: #000; }
.btn-accent:hover { background: #d97706; border-color: #d97706; color: #000; }

/* ── Attendance ── */
.att-present { background: #d1fae5; color: #065f46; font-weight: 600; border-radius: 6px; padding: 4px 10px; }
.att-absent  { background: #fee2e2; color: #991b1b; font-weight: 600; border-radius: 6px; padding: 4px 10px; }

/* ── Alerts ── */
.alert { border-radius: 10px; font-size: .9rem; }

/* ── Progress steps ── */
.order-steps { display: flex; gap: 0; overflow-x: auto; padding: 8px 0; }
.step {
  flex: 1; text-align: center; padding: 8px 4px;
  font-size: .72rem; color: var(--muted);
  border-bottom: 3px solid #e2e8f0; min-width: 70px;
}
.step.done   { color: var(--success); border-bottom-color: var(--success); }
.step.active { color: var(--primary); border-bottom-color: var(--primary); font-weight: 700; }
.step-dot { width: 10px; height: 10px; border-radius: 50%; background: #e2e8f0; margin: 0 auto 4px; }
.step.done .step-dot   { background: var(--success); }
.step.active .step-dot { background: var(--primary); }

/* ── Mobile ── */
.mob-toggle { display: none; }
@media (max-width: 768px) {
  .sidebar { transform: translateX(-100%); }
  .sidebar.open { transform: translateX(0); }
  .main-wrap { margin-left: 0; }
  .mob-toggle { display: flex; align-items: center; background: none; border: none;
    font-size: 1.4rem; padding: 0 4px 0 0; cursor: pointer; color: var(--text); }
  .page-body { padding: 14px; }
  .stat-card { padding: 14px; }
  .stat-val { font-size: 1.6rem; }
}
.sidebar-overlay {
  display: none; position: fixed; inset: 0;
  background: rgba(0,0,0,.4); z-index: 1039;
}
.sidebar-overlay.open { display: block; }
</style>
</head>
<body>

<!-- Mobile overlay -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-am">ጨርቃጨርቅ ፋብሪካ</div>
    <div class="brand-en">Textile Factory Management</div>
  </div>

  <div class="sidebar-role">
    <?php if ($role === 'owner'): ?>👑 Owner
    <?php elseif ($role === 'manager'): ?>🛠 Manager
    <?php else: ?>📋 Attendance Writer<?php endif; ?>
  </div>

  <nav>
  <?php if ($role === 'owner'): ?>
    <a href="<?= BASE_URL ?>/owner/dashboard.php"  class="<?= isActive('dashboard') ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="<?= BASE_URL ?>/owner/orders.php"     class="<?= isActive('orders') ?>"><i class="bi bi-box-seam"></i> Orders</a>
    <a href="<?= BASE_URL ?>/owner/attendance.php" class="<?= isActive('attendance') ?>"><i class="bi bi-calendar-check"></i> Attendance</a>
    <a href="<?= BASE_URL ?>/owner/salaries.php"   class="<?= isActive('salaries') ?>"><i class="bi bi-cash-stack"></i> Salaries & Pay</a>
    <a href="<?= BASE_URL ?>/owner/managers.php"   class="<?= isActive('managers') ?>"><i class="bi bi-person-badge"></i> Manager Accounts</a>

  <?php elseif ($role === 'manager'): ?>
    <a href="<?= BASE_URL ?>/manager/dashboard.php"  class="<?= isActive('dashboard') ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="<?= BASE_URL ?>/manager/employees.php"  class="<?= isActive('employees') ?>"><i class="bi bi-people"></i> Employees</a>
    <a href="<?= BASE_URL ?>/manager/attendance.php" class="<?= isActive('attendance') ?>"><i class="bi bi-calendar-check"></i> Attendance</a>
    <a href="<?= BASE_URL ?>/manager/orders.php"     class="<?= isActive('orders') ?>"><i class="bi bi-box-seam"></i> Orders</a>
    <a href="<?= BASE_URL ?>/manager/levels.php"     class="<?= isActive('levels') ?>"><i class="bi bi-stars"></i> Salary Levels</a>
    <a href="<?= BASE_URL ?>/manager/writers.php"    class="<?= isActive('writers') ?>"><i class="bi bi-person-plus"></i> Writer Accounts</a>

  <?php else: ?>
    <a href="<?= BASE_URL ?>/writer/attendance.php" class="<?= isActive('attendance') ?>"><i class="bi bi-calendar-check"></i> Mark Attendance</a>

  <?php endif; ?>
  </nav>
  <a href="<?= BASE_URL ?>/change_password.php">
    <i class="bi bi-key"></i>
    Change Password
</a>
  <div class="sidebar-footer">
    <div><?= sanitize($user['name']) ?></div>
    <a href="<?= BASE_URL ?>/logout.php"><i class="bi bi-box-arrow-left"></i> Logout</a>
  </div>
</div>

<!-- Main wrapper -->
<div class="main-wrap">
  <div class="topbar">
    <div style="display:flex;align-items:center;gap:10px">
      <button class="mob-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
      <span class="topbar-title"><?= sanitize($pageTitle ?? 'Dashboard') ?></span>
    </div>
    <div class="topbar-meta">
      <div class="eth-date"><?= todayEthiopian() ?></div>
      <div><?= date('D, d M Y') ?></div>
    </div>
  </div>

  <div class="page-body">
    <?php showFlash(); ?>
