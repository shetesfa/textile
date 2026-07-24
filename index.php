<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
if (isset($_SESSION['user_id'])) {
    header('Location: ' . getDashboardUrl()); exit;
}
header('Location: ' . BASE_URL . '/login.php'); exit;
