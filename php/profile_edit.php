<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/helpers.php';

require_student();

$idNumber = $_SESSION['IdNumber'];
$db       = getDB();

$errors = [];

// Load current image
$stmt = $db->prepare('SELECT ProfileImagePath FROM signups WHERE IdNumber = ? LIMIT 1');
$stmt->execute([$idNumber]);
$row = $stmt->fetch();
$currentImage = $row['ProfileImagePath'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (empty($_FILES['ProfileImage']) || $_FILES['ProfileImage']['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Please select an image to upload.';
    } elseif ($_FILES['ProfileImage']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Upload error. Please try again.';
    } else {
        $file      = $_FILES['ProfileImage'];
        $ext       = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed   = ['jpg','jpeg','png','webp'];

        if (!in_array($ext, $allowed, true)) {
            $errors[] = 'Only .jpg, .jpeg, .png, and .webp files are allowed.';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $errors[] = 'Image size must be 5MB or less.';
        } else {
            // Verify it's actually an image
            $mime = mime_content_type($file['tmp_name']);
            if (!str_starts_with($mime, 'image/')) {
                $errors[] = 'The uploaded file is not a valid image.';
            }
        }

        if (empty($errors)) {
            $uploadsDir = __DIR__ . '/images/profiles/';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }

            $fileName = $idNumber . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $savePath = $uploadsDir . $fileName;

            if (!move_uploaded_file($file['tmp_name'], $savePath)) {
                $errors[] = 'Failed to save the image. Please try again.';
            } else {
                $webPath = '/php/images/profiles/' . $fileName;
                $db->prepare('UPDATE signups SET ProfileImagePath = ? WHERE IdNumber = ?')
                   ->execute([$webPath, $idNumber]);
                $_SESSION['ProfileImagePath'] = $webPath;

                set_flash('success', 'Profile picture updated successfully.');
                header('Location: /php/profile.php');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Profile Picture</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
</head>
<body class="profile-page">
    <?php include __DIR__ . '/partials/user_navbar.php'; ?>

    <main class="profile-main container py-4" style="max-width:760px;">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h3 class="mb-3">Update Profile Picture</h3>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form action="/php/profile_edit.php" method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>

                    <div class="mb-3 text-center">
                        <?php $img = !empty($currentImage) ? $currentImage : '/php/images/default-avatar.png'; ?>
                        <img src="<?= e($img) ?>"
                             onerror="this.src='/php/images/default-avatar.png'"
                             alt="Profile picture"
                             class="rounded-circle border"
                             style="width:120px;height:120px;object-fit:cover;" />
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Profile Image</label>
                        <input type="file" class="form-control" name="ProfileImage"
                               accept=".jpg,.jpeg,.png,.webp,image/*" />
                        <small class="text-muted">Allowed: jpg, jpeg, png, webp (max 5MB).</small>
                    </div>

                    <div class="mt-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Upload</button>
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
