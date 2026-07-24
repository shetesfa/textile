<?php
// ============================================================
// Authentication & Role Control
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if not logged in
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

// Restrict page to specific role(s)
function requireRole($roles) {
    requireLogin();
    if (!is_array($roles)) $roles = [$roles];
    if (!in_array($_SESSION['role'] ?? '', $roles)) {
        header('Location: ' . BASE_URL . '/login.php?error=access');
        exit;
    }
}

// Role checkers
function isOwner()   { return ($_SESSION['role'] ?? '') === 'owner';   }
function isManager() { return ($_SESSION['role'] ?? '') === 'manager'; }
function isWriter()  { return ($_SESSION['role'] ?? '') === 'writer';  }

// Return current logged in user info
function currentUser() {
    return [
        'id'   => $_SESSION['user_id'] ?? null,
        'name' => $_SESSION['name']    ?? '',
        'role' => $_SESSION['role']    ?? '',
    ];
}

// Get the correct dashboard URL for the current role
function getDashboardUrl() {
    switch ($_SESSION['role'] ?? '') {
        case 'owner':   return BASE_URL . '/owner/dashboard.php';
        case 'manager': return BASE_URL . '/manager/dashboard.php';
        case 'writer':  return BASE_URL . '/writer/attendance.php';
        default:        return BASE_URL . '/login.php';
    }
}
