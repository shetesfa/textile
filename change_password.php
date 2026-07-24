<?php

session_start();

require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$user = currentUser();

$pageTitle = 'Change Password';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if ($new !== $confirm) {

        $error = 'New passwords do not match';

    } else {

        $stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
        $stmt->execute([$user['id']]);

        $dbUser = $stmt->fetch();

        if (!password_verify($current, $dbUser['password'])) {

            $error = 'Current password incorrect';

        } else {

            $newHash = password_hash($new, PASSWORD_DEFAULT);

            $update = $pdo->prepare("UPDATE users SET password=? WHERE id=?");

            $update->execute([$newHash, $user['id']]);

            $message = 'Password changed successfully';
        }
    }
}

include 'includes/header.php';
?>

<div class="container-fluid">

    <div class="row justify-content-center">

        <div class="col-lg-5">

            <div class="table-card">

                <div class="tc-head">
                    <h5>Change Password</h5>
                </div>

                <div class="p-4">

                    <?php if($message): ?>
                        <div class="alert alert-success">
                            <?= $message ?>
                        </div>
                    <?php endif; ?>

                    <?php if($error): ?>
                        <div class="alert alert-danger">
                            <?= $error ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">

                        <div class="mb-3">
                            <label>Current Password</label>
                            <input type="password"
                                   name="current_password"
                                   class="form-control"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label>New Password</label>
                            <input type="password"
                                   name="new_password"
                                   class="form-control"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label>Confirm Password</label>
                            <input type="password"
                                   name="confirm_password"
                                   class="form-control"
                                   required>
                        </div>

                        <button class="btn btn-primary w-100">
                            Update Password
                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

<?php include 'includes/footer.php'; ?>