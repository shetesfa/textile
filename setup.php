<?php
/**
 * SETUP SCRIPT
 * Run this ONCE to create the default owner account.
 * Then DELETE this file for security!
 *
 * URL: http://localhost/textile/setup.php
 */

require_once __DIR__ . '/config/db.php';

// Check if owner already exists
$check = $pdo->query("SELECT id FROM users WHERE role='owner' LIMIT 1")->fetch();
if ($check) {
    die('<div style="font-family:sans-serif;padding:30px;max-width:500px;margin:40px auto;background:#fff3cd;border:1px solid #ffc107;border-radius:8px;">
        <h3>⚠️ Already Set Up</h3>
        <p>An owner account already exists. This script is not needed.</p>
        <p><strong>Delete this file (setup.php) for security!</strong></p>
        <p><a href="login.php" style="color:#007bff">→ Go to Login</a></p>
    </div>');
}

$name     = 'System Owner';
$username = 'owner';
$password = password_hash('owner123', PASSWORD_DEFAULT);
$role     = 'owner';

try {
    $stmt = $pdo->prepare("INSERT INTO users (name, username, password, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $username, $password, $role]);
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
    <title>Setup Complete</title>
    <style>
        body { font-family: sans-serif; background: #f8f9fa; display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0; }
        .box { background:#fff; padding:40px; border-radius:12px; max-width:420px; width:100%; box-shadow:0 4px 20px rgba(0,0,0,.1); text-align:center; }
        .icon { font-size:3rem; }
        h2 { color:#059669; }
        .cred { background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:16px; margin:20px 0; text-align:left; }
        .cred p { margin:6px 0; font-size:.95rem; }
        .cred strong { color:#065f46; }
        .warn { background:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:12px; color:#991b1b; font-size:.9rem; margin-bottom:20px; }
        a.btn { display:block; background:#f59e0b; color:#000; padding:12px; border-radius:8px; text-decoration:none; font-weight:700; }
    </style></head><body>
    <div class="box">
        <div class="icon">✅</div>
        <h2>Setup Complete!</h2>
        <div class="cred">
            <p><strong>Username:</strong> owner</p>
            <p><strong>Password:</strong> owner123</p>
        </div>
        <div class="warn">⚠️ <strong>IMPORTANT:</strong> Delete the file <code>setup.php</code> now for security!</div>
        <a href="login.php" class="btn">Go to Login →</a>
    </div></body></html>';
} catch (Exception $e) {
    echo '<div style="font-family:sans-serif;padding:30px;max-width:500px;margin:40px auto;background:#f8d7da;border:1px solid #f5c2c7;border-radius:8px;">
        <h3>❌ Error</h3>
        <p>' . htmlspecialchars($e->getMessage()) . '</p>
        <p>Make sure you ran <strong>database.sql</strong> in phpMyAdmin first.</p>
    </div>';
}
