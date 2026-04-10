<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/helpers.php';

require_student();

$idNumber = $_SESSION['IdNumber'];
$db       = getDB();

$stmt = $db->prepare('SELECT * FROM signups WHERE IdNumber = ? LIMIT 1');
$stmt->execute([$idNumber]);
$student = $stmt->fetch();

if (!$student) {
    session_destroy();
    header('Location: /php/login.php');
    exit;
}

$success = get_flash('success');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>My Profile</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
</head>
<body class="profile-page">
    <?php include __DIR__ . '/partials/user_navbar.php'; ?>

    <main class="profile-main container py-4" style="max-width:900px;">

        <?php if ($success !== ''): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-3 align-items-center mb-3">
                    <?php $img = !empty($student['ProfileImagePath']) ? $student['ProfileImagePath'] : '/php/images/default-avatar.png'; ?>
                    <img src="<?= e($img) ?>"
                         onerror="this.src='/php/images/default-avatar.png'"
                         alt="Profile picture"
                         class="rounded-circle border"
                         style="width:92px;height:92px;object-fit:cover;" />
                    <h3 class="mb-0">My Profile</h3>
                </div>

                <div class="row g-3">
                    <div class="col-md-6"><strong>ID Number:</strong> <?= e($student['IdNumber']) ?></div>
                    <div class="col-md-6"><strong>Email:</strong> <?= e($student['Email']) ?></div>
                    <div class="col-md-6"><strong>Name:</strong>
                        <?= e(trim($student['FirstName'].' '.$student['MiddleName'].' '.$student['LastName'])) ?>
                    </div>
                    <div class="col-md-6"><strong>Course / Year:</strong> <?= e($student['Course']) ?> - <?= e($student['CourseLevel']) ?></div>
                    <div class="col-12"><strong>Address:</strong> <?= e($student['Address']) ?></div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="mb-3">Quick Actions</h5>
                <div class="d-flex flex-wrap gap-2">
                    <a href="/php/profile_edit.php" class="btn btn-primary">Edit Profile Picture</a>
                    <a href="/php/profile_password.php" class="btn btn-outline-primary">Change Password</a>
                    <a href="/php/home.php" class="btn btn-light border">Back to Home</a>
                </div>
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
