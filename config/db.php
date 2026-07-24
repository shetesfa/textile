<?php
// ============================================================
// Configuration File
// ============================================================

// CHANGE THIS: Your project folder name inside htdocs/
// Example: project is at C:\xampp\htdocs\textile\ → '/textile'
define('BASE_URL', '/textile');

// Database settings (default XAMPP)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'textile_db');

// Connect to MySQL using PDO
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('
    <html><head><meta charset="UTF-8">
    <style>body{font-family:sans-serif;background:#1e293b;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
    .box{background:#fff;padding:30px;border-radius:12px;max-width:480px;width:100%}
    h3{color:#dc2626}pre{background:#f9fafb;padding:10px;border-radius:6px;font-size:.8rem;overflow:auto}</style>
    </head><body><div class="box">
    <h3>⚠️ Database Connection Failed</h3>
    <p>Make sure <strong>XAMPP MySQL is running</strong> and you have imported <code>database.sql</code> in phpMyAdmin.</p>
    <p>Then open <a href="/textile/setup.php">setup.php</a> to create the owner account.</p>
    <pre>' . htmlspecialchars($e->getMessage()) . '</pre>
    </div></body></html>');
}
