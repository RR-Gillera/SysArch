<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Home - College of Computer Studies'; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>lib/bootstrap/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/site.css" />
</head>
<body>
    <?php include __DIR__ . '/shared/user_navbar.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h1>Welcome to CCS Portal</h1>
                <p class="lead">Hello, <?php echo htmlspecialchars($_SESSION['UserName']); ?>!</p>
                
                <?php if (isset($_SESSION['LoginSuccess'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['LoginSuccess']; unset($_SESSION['LoginSuccess']); ?></div>
                <?php endif; ?>

                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title">Your Information</h5>
                        <p><strong>ID Number:</strong> <?php echo htmlspecialchars($user['IdNumber']); ?></p>
                        <p><strong>Course:</strong> <?php echo htmlspecialchars($user['Course']); ?></p>
                        <p><strong>Level:</strong> <?php echo htmlspecialchars($user['CourseLevel']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['Email']); ?></p>
                        <p><strong>Remaining Sessions:</strong> <?php echo $user['RemainingSessions']; ?></p>
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
