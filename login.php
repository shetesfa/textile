<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
    header('Location: ' . getDashboardUrl());
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$username || !$password) {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['role']    = $user['role'];

            header('Location: ' . getDashboardUrl());
            exit;
        } else {
            $error = 'Incorrect username or password. Please try again.';
        }
    }
}

$accessError = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login | ጨርቃጨርቅ Factory</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Ethiopic:wght@400;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
body {
  font-family: 'Inter', sans-serif;
  background: linear-gradient(135deg, #0f1e2d 0%, #1d3a55 100%);
  min-height: 100vh;
  display: flex; align-items: center; justify-content: center;
  padding: 20px;
}
.login-card {
  background: #fff;
  border-radius: 16px;
  padding: 40px 36px;
  width: 100%; max-width: 400px;
  box-shadow: 0 20px 60px rgba(0,0,0,.4);
}
.brand-am {
  font-family: 'Noto Sans Ethiopic', sans-serif;
  font-size: 1.6rem; font-weight: 700; color: #1d6fa4; line-height: 1.1;
}
.brand-en { font-size: .82rem; color: #64748b; margin-top: 3px; }
.divider { border-color: #e2e8f0; margin: 20px 0; }
.form-label { font-weight: 600; font-size: .88rem; color: #374151; }
.form-control {
  border-radius: 8px; border: 1.5px solid #e2e8f0;
  padding: 11px 14px; font-size: .95rem;
}
.form-control:focus { border-color: #1d6fa4; box-shadow: 0 0 0 3px rgba(29,111,164,.12); }
.btn-login {
  background: #1d6fa4; color: #fff; border: none;
  padding: 13px; font-size: 1rem; font-weight: 700;
  border-radius: 10px; width: 100%; margin-top: 6px;
  transition: background .15s;
}
.btn-login:hover { background: #155a87; }
.alert-danger { border-radius: 8px; font-size: .88rem; }
.footer-note { text-align: center; font-size: .77rem; color: #94a3b8; margin-top: 24px; }
</style>
</head>
<body>
<div class="login-card">
  <div class="text-center mb-4">
    <div style="font-size:2.4rem;margin-bottom:8px">🏭</div>
    <div class="brand-am">ጨርቃጨርቅ ፋብሪካ</div>
    <div class="brand-en">Textile Factory Management System</div>
  </div>

  <hr class="divider">

  <?php if ($error): ?>
    <div class="alert alert-danger">❌ <?= sanitize($error) ?></div>
  <?php endif; ?>

  <?php if ($accessError === 'access'): ?>
    <div class="alert alert-warning">⚠️ You do not have access to that page.</div>
  <?php endif; ?>

  <form method="POST" novalidate>
    <div class="mb-3">
      <label class="form-label">Username</label>
      <input type="text" name="username" class="form-control"
             placeholder="Enter your username"
             value="<?= sanitize($_POST['username'] ?? '') ?>"
             autofocus required>
    </div>
    <div class="mb-3">
      <label class="form-label">Password</label>
      <input type="password" name="password" class="form-control"
             placeholder="Enter your password" required>
    </div>
    <button type="submit" class="btn-login">🔐 Login</button>
  </form>

  <div class="footer-note">ጨርቃጨርቅ Management System v1.0</div>
</div>
</body>
</html>
