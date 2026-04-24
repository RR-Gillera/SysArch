<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Login - College of Computer Studies'; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>lib/bootstrap/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/site.css" />
</head>
<body>
    <?php include __DIR__ . '/shared/login_navbar.php'; ?>

    <div class="container login-container">
        <div class="row justify-content-center align-items-center h-100">
            <div class="col-md-5 text-center">
                <img src="<?php echo BASE_URL; ?>images/CCS Logo.png" alt="CCS Logo" class="ccs-logo">
            </div>
            <div class="col-md-6">
                <div class="login-form-container">
                    <?php if (isset($_SESSION['LoggedOut'])): ?>
                        <div class="alert alert-success"><?php echo $_SESSION['LoggedOut']; unset($_SESSION['LoggedOut']); ?></div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['Success'])): ?>
                        <div class="alert alert-success"><?php echo $_SESSION['Success']; unset($_SESSION['Success']); ?></div>
                    <?php endif; ?>

                    <form method="post" action="<?php echo BASE_URL; ?>login/login">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="idNumber" class="form-label">ID Number</label>
                            <input type="text" class="form-control" name="IdNumber" id="IdNumber" 
                                   value="<?php echo htmlspecialchars($rememberedIdNumber ?? ''); ?>" 
                                   placeholder="Enter a valid id number" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" name="Password" id="Password" 
                                   placeholder="Enter password" required>
                        </div>

                        <div class="mb-3 login-options">
                            <div class="form-check remember-check mb-0">
                                <input type="checkbox" class="form-check-input" name="RememberMe" id="RememberMe" 
                                       <?php echo !empty($rememberedIdNumber) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="RememberMe">Remember me</label>
                            </div>
                            <a href="#" class="text-primary text-decoration-none forgot-link">Forgot password?</a>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 login-btn">Login</button>
                    </form>

                    <div class="mt-3 text-center">
                        <span>Don't have an account? <a href="<?php echo BASE_URL; ?>signup" class="text-danger text-decoration-none fw-bold">Register</a></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/shared/footer.php'; ?>

    <script src="<?php echo BASE_URL; ?>lib/jquery/dist/jquery.min.js"></script>
    <script src="<?php echo BASE_URL; ?>lib/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<style>
    html, body {
        height: 100%;
        margin: 0;
        overflow: hidden;
    }

    body {
        display: flex;
        flex-direction: column;
        height: 100vh;
        background-image: url('<?php echo BASE_URL; ?>images/UC_background.jpg');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
    }

    .login-container {
        flex: 1;
        display: flex;
        align-items: center;
        overflow: hidden;
    }

    .login-container .row {
        width: 100%;
    }

    .ccs-logo {
        max-width: 350px;
        width: 100%;
        height: auto;
        object-fit: contain;
    }

    .login-form-container {
        background-color: #ffffff;
        padding: 40px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .login-btn {
        background-color: #007bff;
        border-color: #007bff;
        padding: 12px;
        font-weight: 500;
        font-size: 16px;
    }

    .login-btn:hover {
        background-color: #0056b3;
        border-color: #0056b3;
    }

    .form-label {
        font-weight: 600;
        color: #333;
        margin-bottom: 8px;
    }

    .form-control {
        border: 1px solid #ced4da;
        border-radius: 4px;
        padding: 10px 12px;
    }

    .form-control:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    .login-options {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    .remember-check {
        display: flex;
        align-items: center;
        gap: 8px;
        min-height: auto;
    }

    .remember-check .form-check-input {
        margin: 0;
        float: none;
    }

    .remember-check .form-check-label {
        margin: 0;
        color: #333;
    }

    .forgot-link {
        white-space: nowrap;
    }

    .form-check-input:checked {
        background-color: #007bff;
        border-color: #007bff;
    }
</style>
