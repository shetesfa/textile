<?php
// ============================================================
// Helper Functions
// ============================================================

// --- Ethiopian Calendar ---

/**
 * Convert Gregorian date to Ethiopian date
 * @param int $gYear  Gregorian year
 * @param int $gMonth Gregorian month (1-12)
 * @param int $gDay   Gregorian day
 * @return array ['year', 'month', 'day']
 */
function gregorianToEthiopian($gYear, $gMonth, $gDay) {
    $jdn = gregorianToJDN($gYear, $gMonth, $gDay);
    return jdnToEthiopian($jdn);
}

function gregorianToJDN($year, $month, $day) {
    $a = intdiv(14 - $month, 12);
    $y = $year + 4800 - $a;
    $m = $month + 12 * $a - 3;
    return $day + intdiv(153 * $m + 2, 5) + 365 * $y
         + intdiv($y, 4) - intdiv($y, 100) + intdiv($y, 400) - 32045;
}

function jdnToEthiopian($jdn) {
    $r = ($jdn - 1723856) % 1461;
    if ($r < 0) $r += 1461;
    $n    = ($r % 365) + 365 * intdiv($r, 1460);
    $year = 4 * intdiv($jdn - 1723856, 1461) + intdiv($r, 365) - intdiv($r, 1460);
    $month = intdiv($n, 30) + 1;
    $day   = ($n % 30) + 1;
    return ['year' => $year, 'month' => $month, 'day' => $day];
}

function ethMonthName($month) {
    $names = [
        1 => 'መስከረም', 2 => 'ጥቅምት',  3 => 'ህዳር',   4 => 'ታህሳስ',
        5 => 'ጥር',    6 => 'የካቲት',  7 => 'መጋቢት',  8 => 'ሚያዚያ',
        9 => 'ግንቦት', 10 => 'ሰኔ',    11 => 'ሐምሌ',  12 => 'ነሐሴ',
        13 => 'ጳጉሜ'
    ];
    return $names[$month] ?? '';
}

/**
 * Format a 'YYYY-MM-DD' Gregorian date as Ethiopian date string
 */
function formatEthDate($gDate) {
    if (!$gDate) return '—';
    [$y, $m, $d] = explode('-', $gDate);
    $eth = gregorianToEthiopian((int)$y, (int)$m, (int)$d);
    return $eth['day'] . ' ' . ethMonthName($eth['month']) . ' ' . $eth['year'];
}

/**
 * Today's date in Ethiopian calendar (display string)
 */
function todayEthiopian() {
    return formatEthDate(date('Y-m-d'));
}

// --- Attendance / Salary ---

/**
 * Get Mon–Sat dates for the week starting on $mondayDate
 * @param string $mondayDate 'YYYY-MM-DD' of the Monday
 * @return string[]
 */
function getWeekDates($mondayDate) {
    $ts = strtotime($mondayDate);
    $dates = [];
    for ($i = 0; $i < 6; $i++) {
        $dates[] = date('Y-m-d', $ts + $i * 86400);
    }
    return $dates;
}

/**
 * Find the Monday of the current (or given) week
 */
function getThisMonday($referenceDate = null) {
    $ts = $referenceDate ? strtotime($referenceDate) : time();
    $dow = (int)date('N', $ts); // 1=Mon … 7=Sun
    return date('Y-m-d', $ts - ($dow - 1) * 86400);
}

function getDayShort($date) {
    return date('D', strtotime($date)); // Mon, Tue …
}

function getDayNum($date) {
    return date('d', strtotime($date));
}

// Total salary earned by employee (all time)
function getEmployeeEarned($pdo, $empId) {
    $stmt = $pdo->prepare(
        "SELECT COALESCE(SUM(daily_rate),0) AS total
         FROM attendance WHERE employee_id=? AND status='present'"
    );
    $stmt->execute([$empId]);
    return (float)$stmt->fetchColumn();
}

// Total salary paid to employee (all time)
function getEmployeePaid($pdo, $empId) {
    $stmt = $pdo->prepare(
        "SELECT COALESCE(SUM(amount),0) AS total FROM payments WHERE employee_id=?"
    );
    $stmt->execute([$empId]);
    return (float)$stmt->fetchColumn();
}

// Unpaid balance for employee
function getEmployeeBalance($pdo, $empId) {
    return getEmployeeEarned($pdo, $empId) - getEmployeePaid($pdo, $empId);
}

// Get daily rate from salary_levels for a given level
function getLevelRate($pdo, $level) {
    $stmt = $pdo->prepare("SELECT daily_rate FROM salary_levels WHERE level=?");
    $stmt->execute([$level]);
    $row = $stmt->fetch();
    return $row ? (float)$row['daily_rate'] : 0.0;
}

// Get all salary levels as id=>row map
function getAllLevels($pdo) {
    $rows = $pdo->query("SELECT * FROM salary_levels ORDER BY level")->fetchAll();
    $out = [];
    foreach ($rows as $r) $out[$r['level']] = $r;
    return $out;
}

// --- Orders ---

function orderStatusLabel($status) {
    return [
        'new'           => 'New Order',
        'accepted'      => 'Accepted',
        'working'       => 'Working',
        'half_finished' => 'Half Finished',
        'finished'      => 'Finished',
    ][$status] ?? $status;
}

function orderStatusIcon($status) {
    return [
        'new'           => '🆕',
        'accepted'      => '✅',
        'working'       => '⚙️',
        'half_finished' => '📦',
        'finished'      => '🏁',
    ][$status] ?? '•';
}

function orderStatusClass($status) {
    return [
        'new'           => 'status-new',
        'accepted'      => 'status-accepted',
        'working'       => 'status-working',
        'half_finished' => 'status-half',
        'finished'      => 'status-finished',
    ][$status] ?? '';
}

function getNextStatus($status) {
    $flow = [
        'new'           => 'accepted',
        'accepted'      => 'working',
        'working'       => 'half_finished',
        'half_finished' => 'finished',
    ];
    return $flow[$status] ?? null;
}

// All statuses in order
function statusSteps() {
    return ['new','accepted','working','half_finished','finished'];
}

// --- UI Helpers ---

function sanitize($v) {
    return htmlspecialchars(trim((string)$v), ENT_QUOTES, 'UTF-8');
}

function money($amount) {
    return number_format((float)$amount, 2) . ' ETB';
}

// Store flash message
function flash($type, $msg) {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

// Render and clear flash message
function showFlash() {
    if (empty($_SESSION['flash'])) return;
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $cls = match($f['type']) {
        'success' => 'alert-success',
        'error'   => 'alert-danger',
        'warning' => 'alert-warning',
        default   => 'alert-info',
    };
    $icon = match($f['type']) {
        'success' => '✅', 'error' => '❌', 'warning' => '⚠️', default => 'ℹ️'
    };
    echo '<div class="alert ' . $cls . ' alert-dismissible fade show" role="alert">'
       . $icon . ' ' . sanitize($f['msg'])
       . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
}

// Check if a URI segment is the current page (for sidebar active state)
function isActive($segment) {
    return (strpos($_SERVER['PHP_SELF'] ?? '', $segment) !== false) ? 'active' : '';
}

// Generate a unique order number
function generateOrderNumber() {
    return 'ORD-' . strtoupper(date('ymd')) . '-' . rand(100, 999);
}
