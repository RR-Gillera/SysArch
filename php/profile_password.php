<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/helpers.php';

require_student();

$idNumber = $_SESSION['IdNumber'];
$db       = getDB();
$errors   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $current = $_POST['CurrentPassword'] ?? '';
    $new     = $_POST['NewPassword']     ?? '';
    $confirm = $_POST['ConfirmNewPassword'] ?? '';

    if ($current === '') { $errors['CurrentPassword'] = 'Current password is required.'; }
    if ($new === '')     { $errors['NewPassword']     = 'New password is required.'; }
    if ($confirm === '') { $errors['ConfirmNewPassword'] = 'Please confirm your new password.'; }
    if ($new !== '' && $confirm !== '' && $new !== $confirm) {
        $errors['ConfirmNewPassword'] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $stmt = $db->prepare('SELECT Password FROM signups WHERE IdNumber = ? LIMIT 1');
        $stmt->execute([$idNumber]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($current, $row['Password'])) {
            $errors['CurrentPassword'] = 'Current password is incorrect.';
        }
    }

    if (empty($errors)) {
        $hashed = password_hash($new, PASSWORD_BCRYPT);
        $db->prepare('UPDATE signups SET Password = ? WHERE IdNumber = ?')
           ->execute([$hashed, $idNumber]);

        set_flash('success', 'Password changed successfully.');
        header('Location: /php/profile.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Change Password</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
</head>
<body class="profile-page">
    <?php include __DIR__ . '/partials/user_navbar.php'; ?>

    <main class="profile-main container py-4" style="max-width:700px;">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h3 class="mb-3">Change Password</h3>

                <form action="/php/profile_password.php" method="post">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" class="form-control <?= isset($errors['CurrentPassword']) ? 'is-invalid' : '' ?>"
                               name="CurrentPassword" />
                        <?php if (isset($errors['CurrentPassword'])): ?>
                            <div class="invalid-feedback"><?= e($errors['CurrentPassword']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-control <?= isset($errors['NewPassword']) ? 'is-invalid' : '' ?>"
                               name="NewPassword" />
                        <?php if (isset($errors['NewPassword'])): ?>
                            <div class="invalid-feedback"><?= e($errors['NewPassword']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control <?= isset($errors['ConfirmNewPassword']) ? 'is-invalid' : '' ?>"
                               name="ConfirmNewPassword" />
                        <?php if (isset($errors['ConfirmNewPassword'])): ?>
                            <div class="invalid-feedback"><?= e($errors['ConfirmNewPassword']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Update Password</button>
                        <a href="/php/profile.php" class="btn btn-light border">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/partials/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<style>
    html, body { height: 100%; margin: 0; }
    .profile-page { min-height: 100vh; display: flex; flex-direction: column; }
    .profile-main { flex: 1; }
</style>
