<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/helpers.php';

if (!empty($_SESSION['IdNumber'])) {
    header('Location: ' . (is_admin() ? '/php/admin/home.php' : '/php/home.php'));
    exit;
}

$errors = [];
$input  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $fields = ['IdNumber','FirstName','LastName','MiddleName','CourseLevel','Course','Address','Email','Password','ConfirmPassword'];
    foreach ($fields as $f) {
        $input[$f] = trim($_POST[$f] ?? '');
    }

    // Validation
    if (!preg_match('/^\d{8}$/', $input['IdNumber'])) {
        $errors['IdNumber'] = 'ID Number must be exactly 8 digits.';
    }
    if ($input['FirstName'] === '') {
        $errors['FirstName'] = 'First Name is required.';
    } elseif (!preg_match("/^[a-zA-Z\s\-']+$/", $input['FirstName'])) {
        $errors['FirstName'] = 'First Name must contain letters only.';
    }
    if ($input['LastName'] === '') {
        $errors['LastName'] = 'Last Name is required.';
    } elseif (!preg_match("/^[a-zA-Z\s\-']+$/", $input['LastName'])) {
        $errors['LastName'] = 'Last Name must contain letters only.';
    }
    if ($input['MiddleName'] !== '' && !preg_match("/^[a-zA-Z\s\-']+$/", $input['MiddleName'])) {
        $errors['MiddleName'] = 'Middle Name must contain letters only.';
    }
    if ($input['CourseLevel'] === '') {
        $errors['CourseLevel'] = 'Course Level is required.';
    }
    if ($input['Course'] === '') {
        $errors['Course'] = 'Course is required.';
    }
    if ($input['Address'] === '') {
        $errors['Address'] = 'Address is required.';
    }
    if ($input['Email'] === '') {
        $errors['Email'] = 'Email is required.';
    } elseif (!filter_var($input['Email'], FILTER_VALIDATE_EMAIL)) {
        $errors['Email'] = 'Enter a valid email address.';
    }
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $input['Password'])) {
        $errors['Password'] = 'Password must have at least 8 characters, an uppercase, a lowercase, a number, and a symbol.';
    }
    if ($input['ConfirmPassword'] !== $input['Password']) {
        $errors['ConfirmPassword'] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $db = getDB();

        $s = $db->prepare('SELECT 1 FROM signups WHERE IdNumber = ? LIMIT 1');
        $s->execute([$input['IdNumber']]);
        if ($s->fetchColumn()) {
            $errors['IdNumber'] = 'This ID Number is already registered.';
        }

        $s = $db->prepare('SELECT 1 FROM signups WHERE Email = ? LIMIT 1');
        $s->execute([$input['Email']]);
        if ($s->fetchColumn()) {
            $errors['Email'] = 'This Email is already registered.';
        }
    }

    if (empty($errors)) {
        $db = getDB();
        $hashed = password_hash($input['Password'], PASSWORD_BCRYPT);
        $stmt = $db->prepare(
            'INSERT INTO signups
                (IdNumber, FirstName, LastName, MiddleName, CourseLevel, Course, Address, Email, Password, Role, RemainingSessions, CreatedAt)
             VALUES (?,?,?,?,?,?,?,?,?,\'Student\',30,NOW())'
        );
        $stmt->execute([
            $input['IdNumber'],
            $input['FirstName'],
            $input['LastName'],
            $input['MiddleName'],
            $input['CourseLevel'],
            $input['Course'],
            $input['Address'],
            $input['Email'],
            $hashed,
        ]);

        set_flash('success', 'Registration successful!');
        header('Location: /php/login.php');
        exit;
    }
}

$success = get_flash('success');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Register - College of Computer Studies</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
</head>
<body>
    <?php include __DIR__ . '/partials/login_navbar.php'; ?>

    <div class="container signup-container">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="signup-form-container">
                    <div class="text-center mb-4">
                        <img src="/php/images/CCS Logo.png" alt="CCS Logo" class="ccs-logo" />
                        <h4 class="form-title mt-3">Create an Account</h4>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="/php/signup.php">
                        <?= csrf_field() ?>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">ID Number</label>
                                <input type="text" class="form-control <?= isset($errors['IdNumber']) ? 'is-invalid' : '' ?>"
                                       name="IdNumber" value="<?= e($input['IdNumber'] ?? '') ?>"
                                       placeholder="Enter your ID number" />
                                <?php if (isset($errors['IdNumber'])): ?>
                                    <div class="invalid-feedback"><?= e($errors['IdNumber']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Address</label>
                                <input type="email" class="form-control <?= isset($errors['Email']) ? 'is-invalid' : '' ?>"
                                       name="Email" value="<?= e($input['Email'] ?? '') ?>"
                                       placeholder="Enter your email" />
                                <?php if (isset($errors['Email'])): ?>
                                    <div class="invalid-feedback"><?= e($errors['Email']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control <?= isset($errors['FirstName']) ? 'is-invalid' : '' ?>"
                                       name="FirstName" value="<?= e($input['FirstName'] ?? '') ?>"
                                       placeholder="First name" />
                                <?php if (isset($errors['FirstName'])): ?>
                                    <div class="invalid-feedback"><?= e($errors['FirstName']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control <?= isset($errors['LastName']) ? 'is-invalid' : '' ?>"
                                       name="LastName" value="<?= e($input['LastName'] ?? '') ?>"
                                       placeholder="Last name" />
                                <?php if (isset($errors['LastName'])): ?>
                                    <div class="invalid-feedback"><?= e($errors['LastName']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Middle Name</label>
                                <input type="text" class="form-control <?= isset($errors['MiddleName']) ? 'is-invalid' : '' ?>"
                                       name="MiddleName" value="<?= e($input['MiddleName'] ?? '') ?>"
                                       placeholder="Middle name" />
                                <?php if (isset($errors['MiddleName'])): ?>
                                    <div class="invalid-feedback"><?= e($errors['MiddleName']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Course Level</label>
                                <select class="form-select <?= isset($errors['CourseLevel']) ? 'is-invalid' : '' ?>" name="CourseLevel">
                                    <option value="" disabled <?= empty($input['CourseLevel']) ? 'selected' : '' ?>>Select year</option>
                                    <?php foreach (['1' => '1st Year','2' => '2nd Year','3' => '3rd Year','4' => '4th Year'] as $val => $label): ?>
                                        <option value="<?= $val ?>" <?= ($input['CourseLevel'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['CourseLevel'])): ?>
                                    <div class="invalid-feedback"><?= e($errors['CourseLevel']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Course</label>
                                <select class="form-select <?= isset($errors['Course']) ? 'is-invalid' : '' ?>" name="Course">
                                    <option value="" disabled <?= empty($input['Course']) ? 'selected' : '' ?>>Select course</option>
                                    <?php foreach (['BSCS','BSIT','BSIS'] as $c): ?>
                                        <option value="<?= $c ?>" <?= ($input['Course'] ?? '') === $c ? 'selected' : '' ?>><?= $c ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['Course'])): ?>
                                    <div class="invalid-feedback"><?= e($errors['Course']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Address</label>
                                <input type="text" class="form-control <?= isset($errors['Address']) ? 'is-invalid' : '' ?>"
                                       name="Address" value="<?= e($input['Address'] ?? '') ?>"
                                       placeholder="Enter your address" />
                                <?php if (isset($errors['Address'])): ?>
                                    <div class="invalid-feedback"><?= e($errors['Address']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" class="form-control <?= isset($errors['Password']) ? 'is-invalid' : '' ?>"
                                       name="Password" placeholder="Create a password" />
                                <?php if (isset($errors['Password'])): ?>
                                    <div class="invalid-feedback"><?= e($errors['Password']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" class="form-control <?= isset($errors['ConfirmPassword']) ? 'is-invalid' : '' ?>"
                                       name="ConfirmPassword" placeholder="Confirm your password" />
                                <?php if (isset($errors['ConfirmPassword'])): ?>
                                    <div class="invalid-feedback"><?= e($errors['ConfirmPassword']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 signup-btn mt-2">Register</button>
                    </form>

                    <div class="mt-3 text-center" style="color:white">
                        Already have an account?
                        <a href="/php/login.php" class="text-danger text-decoration-none fw-bold">Login</a>
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
                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
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
    html, body { height: 100%; margin: 0; overflow: auto; }
    body {
        display: flex; flex-direction: column; min-height: 100vh;
        background-image: url('/php/images/UC_background.jpg');
        background-size: cover; background-position: center; background-repeat: no-repeat;
    }
    .mb-3 { margin-bottom: 0.4rem !important; }
    .signup-container { flex: 1; display: flex; align-items: center; justify-content: center; padding: 40px 0; width: 100%; }
    .ccs-logo { max-width: 140px; width: 100%; height: auto; object-fit: contain; }
    .signup-form-container {
        background-color: rgba(255,255,255,0.2); backdrop-filter: blur(8px);
        padding: 15px 40px; border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.3); width: 900px; margin: 0 auto;
    }
    .form-title { font-weight: 700; color: #003d82; font-size: 22px; }
    .signup-btn { background-color: #003d82; border-color: #003d82; padding: 12px; font-weight: 500; font-size: 16px; }
    .signup-btn:hover { background-color: #002a5c; border-color: #002a5c; }
    .form-label { font-weight: 600; color: #333; margin-bottom: 6px; }
    .form-control, .form-select { border: 1px solid #ced4da; border-radius: 4px; padding: 10px 12px; }
    .form-control:focus, .form-select:focus { border-color: #003d82; box-shadow: 0 0 0 0.2rem rgba(0,61,130,0.2); }
</style>
