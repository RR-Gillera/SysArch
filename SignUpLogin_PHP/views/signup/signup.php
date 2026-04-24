<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Sign Up - College of Computer Studies'; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>lib/bootstrap/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/site.css" />
</head>
<body>
    <?php include __DIR__ . '/shared/login_navbar.php'; ?>

    <div class="container signup-container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="signup-form-container">
                    <h2 class="text-center mb-4">Create Account</h2>

                    <?php if (isset($_SESSION['Success'])): ?>
                        <div class="alert alert-success"><?php echo $_SESSION['Success']; unset($_SESSION['Success']); ?></div>
                    <?php endif; ?>

                    <form method="post" action="<?php echo BASE_URL; ?>signup/register">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="IdNumber" class="form-label">ID Number *</label>
                                <input type="text" class="form-control" name="IdNumber" id="IdNumber" 
                                       value="<?php echo htmlspecialchars($data['IdNumber'] ?? ''); ?>" 
                                       placeholder="8 digits" required maxlength="8">
                                <?php if (isset($errors['IdNumber'])): ?>
                                    <span class="text-danger small"><?php echo $errors['IdNumber']; ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="FirstName" class="form-label">First Name *</label>
                                <input type="text" class="form-control" name="FirstName" id="FirstName" 
                                       value="<?php echo htmlspecialchars($data['FirstName'] ?? ''); ?>" required>
                                <?php if (isset($errors['FirstName'])): ?>
                                    <span class="text-danger small"><?php echo $errors['FirstName']; ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="MiddleName" class="form-label">Middle Name</label>
                                <input type="text" class="form-control" name="MiddleName" id="MiddleName" 
                                       value="<?php echo htmlspecialchars($data['MiddleName'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="LastName" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" name="LastName" id="LastName" 
                                       value="<?php echo htmlspecialchars($data['LastName'] ?? ''); ?>" required>
                                <?php if (isset($errors['LastName'])): ?>
                                    <span class="text-danger small"><?php echo $errors['LastName']; ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="CourseLevel" class="form-label">Course Level *</label>
                                <select class="form-select" name="CourseLevel" id="CourseLevel" required>
                                    <option value="">Select Level</option>
                                    <option value="1st Year" <?php echo ($data['CourseLevel'] ?? '') === '1st Year' ? 'selected' : ''; ?>>1st Year</option>
                                    <option value="2nd Year" <?php echo ($data['CourseLevel'] ?? '') === '2nd Year' ? 'selected' : ''; ?>>2nd Year</option>
                                    <option value="3rd Year" <?php echo ($data['CourseLevel'] ?? '') === '3rd Year' ? 'selected' : ''; ?>>3rd Year</option>
                                    <option value="4th Year" <?php echo ($data['CourseLevel'] ?? '') === '4th Year' ? 'selected' : ''; ?>>4th Year</option>
                                </select>
                                <?php if (isset($errors['CourseLevel'])): ?>
                                    <span class="text-danger small"><?php echo $errors['CourseLevel']; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="Course" class="form-label">Course *</label>
                                <select class="form-select" name="Course" id="Course" required>
                                    <option value="">Select Course</option>
                                    <option value="BS Computer Science" <?php echo ($data['Course'] ?? '') === 'BS Computer Science' ? 'selected' : ''; ?>>BS Computer Science</option>
                                    <option value="BS Information Technology" <?php echo ($data['Course'] ?? '') === 'BS Information Technology' ? 'selected' : ''; ?>>BS Information Technology</option>
                                    <option value="BS Computer Engineering" <?php echo ($data['Course'] ?? '') === 'BS Computer Engineering' ? 'selected' : ''; ?>>BS Computer Engineering</option>
                                </select>
                                <?php if (isset($errors['Course'])): ?>
                                    <span class="text-danger small"><?php echo $errors['Course']; ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="Email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" name="Email" id="Email" 
                                       value="<?php echo htmlspecialchars($data['Email'] ?? ''); ?>" required>
                                <?php if (isset($errors['Email'])): ?>
                                    <span class="text-danger small"><?php echo $errors['Email']; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="Address" class="form-label">Address *</label>
                            <textarea class="form-control" name="Address" id="Address" rows="2" required><?php echo htmlspecialchars($data['Address'] ?? ''); ?></textarea>
                            <?php if (isset($errors['Address'])): ?>
                                <span class="text-danger small"><?php echo $errors['Address']; ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="Password" class="form-label">Password *</label>
                                <input type="password" class="form-control" name="Password" id="Password" required>
                                <?php if (isset($errors['Password'])): ?>
                                    <span class="text-danger small"><?php echo $errors['Password']; ?></span>
                                <?php endif; ?>
                                <small class="text-muted">Must have 8+ characters, uppercase, lowercase, number, and symbol.</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="ConfirmPassword" class="form-label">Confirm Password *</label>
                                <input type="password" class="form-control" name="ConfirmPassword" id="ConfirmPassword" required>
                                <?php if (isset($errors['ConfirmPassword'])): ?>
                                    <span class="text-danger small"><?php echo $errors['ConfirmPassword']; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mt-3">Register</button>
                    </form>

                    <div class="mt-3 text-center">
                        <span>Already have an account? <a href="<?php echo BASE_URL; ?>login" class="text-danger text-decoration-none fw-bold">Login</a></span>
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
    body {
        background-image: url('<?php echo BASE_URL; ?>images/UC_background.jpg');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        min-height: 100vh;
    }

    .signup-container {
        padding-top: 50px !important;
    }

    .signup-form-container {
        background-color: #ffffff;
        padding: 40px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .form-label {
        font-weight: 600;
        color: #333;
    }

    .form-control, .form-select {
        border-radius: 4px;
    }

    .form-control:focus, .form-select:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    .btn-primary {
        background-color: #007bff;
        border-color: #007bff;
        padding: 12px;
        font-weight: 500;
    }

    .btn-primary:hover {
        background-color: #0056b3;
        border-color: #0056b3;
    }
</style>
