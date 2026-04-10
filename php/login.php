<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/helpers.php';

// Redirect if already logged in
if (!empty($_SESSION['IdNumber'])) {
    header('Location: ' . (is_admin() ? '/php/admin/home.php' : '/php/home.php'));
    exit;
}

$errors   = [];
$idNumber = '';
$remember = false;

// Pre-fill from remember-me cookie
if (empty($_POST) && isset($_COOKIE['RememberedIdNumber'])) {
    $idNumber = $_COOKIE['RememberedIdNumber'];
    $remember = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $idNumber = trim($_POST['IdNumber'] ?? '');
    $password = $_POST['Password']  ?? '';
    $remember = !empty($_POST['RememberMe']);

    if ($idNumber === '') {
        $errors[] = 'ID Number is required.';
    }
    if ($password === '') {
        $errors[] = 'Password is required.';
    }

    if (empty($errors)) {
        $db   = getDB();
        // Try by IdNumber first, then by FirstName for admins
        $stmt = $db->prepare('SELECT * FROM signups WHERE IdNumber = ? LIMIT 1');
        $stmt->execute([$idNumber]);
        $user = $stmt->fetch();

        if (!$user) {
            $stmt = $db->prepare("SELECT * FROM signups WHERE FirstName = ? AND Role = 'Admin' LIMIT 1");
            $stmt->execute([$idNumber]);
            $user = $stmt->fetch();
        }

        if (!$user || !password_verify($password, $user['Password'])) {
            $errors[] = 'Invalid ID Number/Username or Password.';
        } else {
            // Set remember-me cookie
            if ($remember) {
                setcookie('RememberedIdNumber', $idNumber, [
                    'expires'  => time() + 30 * 86400,
                    'path'     => '/',
                    'httponly' => true,
                    'samesite' => 'Lax',
                    'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
                ]);
            } else {
                setcookie('RememberedIdNumber', '', ['expires' => time() - 3600, 'path' => '/']);
            }

            $_SESSION['UserName']        = trim($user['FirstName'] . ' ' . $user['LastName']);
            $_SESSION['IdNumber']        = $user['IdNumber'];
            $_SESSION['Course']          = $user['Course'];
            $_SESSION['CourseLevel']     = $user['CourseLevel'];
            $_SESSION['Email']           = $user['Email'];
            $_SESSION['Role']            = $user['Role'];
            $_SESSION['ProfileImagePath'] = $user['ProfileImagePath'] ?? '';

            set_flash('success', 'Login successful!');

            if (strtolower($user['Role']) === 'admin') {
                header('Location: /php/admin/home.php');
            } else {
                header('Location: /php/home.php');
            }
            exit;
        }
    }
}

$success = get_flash('success');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - College of Computer Studies</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
</head>
<body>
    <?php include __DIR__ . '/partials/login_navbar.php'; ?>

    <div class="container login-container">
        <div class="row justify-content-center align-items-center h-100">
            <div class="col-md-5 text-center">
                <img src="/php/images/CCS Logo.png" alt="CCS Logo" class="ccs-logo">
            </div>
            <div class="col-md-6">
                <div class="login-form-container">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $err): ?>
                                <div><?= e($err) ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="/php/login.php">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label">ID Number</label>
                            <input type="text" class="form-control" name="IdNumber"
                                   value="<?= e($idNumber) ?>"
                                   placeholder="Enter a valid id number" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="Password"
                                   placeholder="Enter password" required>
                        </div>

                        <div class="mb-3 login-options">
                            <div class="form-check remember-check mb-0">
                                <input type="checkbox" class="form-check-input" name="RememberMe"
                                       id="rememberMe" <?= $remember ? 'checked' : '' ?>>
                                <label class="form-check-label" for="rememberMe">Remember me</label>
                            </div>
                            <a href="#" class="text-primary text-decoration-none forgot-link">Forgot password?</a>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 login-btn">Login</button>
                    </form>

                    <div class="mt-3 text-center">
                        <span>Don't have an account?
                            <a href="/php/signup.php" class="text-danger text-decoration-none fw-bold">Register</a>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/partials/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <?php if ($success !== ''): ?>
    <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,0.5)">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center p-4">
                <div class="mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" fill="#28a745" viewBox="0 0 16 16">
                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z" />
                    </svg>
                </div>
                <h5 class="fw-bold"><?= e($success) ?></h5>
                <button class="btn btn-success mt-3 px-4" onclick="this.closest('.modal').remove()">OK</button>
            </div>
        </div>
    </div>
    <?php endif; ?>
</body>
</html>

<style>
    html, body { height: 100%; margin: 0; overflow: hidden; }
    body {
        display: flex; flex-direction: column; height: 100vh;
        background-image: url('/php/images/UC_background.jpg');
        background-size: cover; background-position: center; background-repeat: no-repeat;
    }
    .login-container { flex: 1; display: flex; align-items: center; overflow: hidden; }
    .login-container .row { width: 100%; }
    .ccs-logo { max-width: 350px; width: 100%; height: auto; object-fit: contain; }
    .login-form-container {
        background-color: #ffffff; padding: 40px;
        border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .login-btn { background-color: #007bff; border-color: #007bff; padding: 12px; font-weight: 500; font-size: 16px; }
    .login-btn:hover { background-color: #0056b3; border-color: #0056b3; }
    .form-label { font-weight: 600; color: #333; margin-bottom: 8px; }
    .form-control { border: 1px solid #ced4da; border-radius: 4px; padding: 10px 12px; }
    .form-control:focus { border-color: #007bff; box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25); }
    .login-options { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
    .remember-check { display: flex; align-items: center; gap: 8px; min-height: auto; }
    .remember-check .form-check-input { margin: 0; float: none; }
    .remember-check .form-check-label { margin: 0; color: #333; }
    .forgot-link { white-space: nowrap; }
    .form-check-input:checked { background-color: #007bff; border-color: #007bff; }
</style>
