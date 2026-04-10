<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/helpers.php';

require_admin();

$db            = getDB();
$announcements = $db->query('SELECT * FROM announcements ORDER BY PostedAt DESC')->fetchAll();

$success = get_flash('success');
$error   = get_flash('error');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Announcements — CCS Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
</head>
<body class="admin-page">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark admin-nav sticky-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-semibold" href="/php/admin/home.php">CCS Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNav">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                    <li class="nav-item"><a class="nav-link" href="/php/admin/home.php"><i class="bi bi-house-door me-1"></i>Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="/php/admin/students.php"><i class="bi bi-people me-1"></i>Students</a></li>
                    <li class="nav-item"><a class="nav-link" href="/php/admin/sit_in_records.php"><i class="bi bi-journal-text me-1"></i>Sit-in Records</a></li>
                    <li class="nav-item"><a class="nav-link active" href="/php/admin/announcements.php"><i class="bi bi-megaphone me-1"></i>Announcements</a></li>
                    <li class="nav-item"><a class="nav-link" href="/php/admin/feedback.php"><i class="bi bi-chat-left-text me-1"></i>Feedback</a></li>
                    <li class="nav-item"><a class="nav-link" href="/php/admin/reservations.php"><i class="bi bi-calendar-check me-1"></i>Reservations</a></li>
                    <li class="nav-item ms-lg-2"><a class="btn btn-warning btn-sm px-3" href="/php/logout.php">Log out</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="admin-main px-4 py-4">
        <h4 class="mb-3 fw-bold">Announcements</h4>

        <?php if ($success !== ''): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= e($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= e($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Post New Announcement -->
        <div class="module-card mb-4">
            <h5 class="section-title mb-3"><i class="bi bi-plus-circle me-2 text-primary"></i>Post New Announcement</h5>
            <form action="/php/actions/post_announcement.php" method="post">
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Title</label>
                        <input type="text" name="Title" class="form-control form-control-sm"
                               maxlength="120" placeholder="Announcement title" required />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Posted By</label>
                        <input type="text" name="PostedBy" class="form-control form-control-sm"
                               maxlength="80" placeholder="CCS Admin" />
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold small">Message</label>
                        <textarea name="Message" class="form-control form-control-sm" rows="3"
                                  maxlength="1000" placeholder="Write your announcement..." required></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-megaphone me-1"></i>Post Announcement
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Announcement List -->
        <div class="module-card">
            <h5 class="section-title mb-3"><i class="bi bi-list-ul me-2"></i>All Announcements</h5>
            <?php if (!empty($announcements)): ?>
                <?php foreach ($announcements as $ann): ?>
                    <div class="announcement-row d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <div class="announcement-meta">
                                <i class="bi bi-person-circle me-1"></i><?= e($ann['PostedBy']) ?>
                                &nbsp;·&nbsp; <?= date('M d, Y', strtotime($ann['PostedAt'])) ?>
                            </div>
                            <div class="fw-semibold small"><?= e($ann['Title']) ?></div>
                            <div class="text-muted small"><?= e($ann['Message']) ?></div>
                        </div>
                        <form action="/php/actions/delete_announcement.php" method="post" class="flex-shrink-0"
                              onsubmit="return confirm('Delete this announcement?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$ann['Id'] ?>" />
                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted small">No announcements yet.</p>
            <?php endif; ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<style>
    .admin-page  { min-height: 100vh; display: flex; flex-direction: column; background: #f0f4f8; }
    .admin-main  { flex: 1; max-width: 1300px; margin: 0 auto; width: 100%; }
    .admin-nav { background: #0a2a6e; box-shadow: 0 2px 8px rgba(0,0,0,.18); }
    .admin-nav .navbar-brand { font-size: .95rem; color: #fff; }
    .admin-nav .nav-link { color: rgba(255,255,255,.78); font-size: .82rem; padding: .4rem .65rem; border-radius: 6px; transition: background .15s; }
    .admin-nav .nav-link:hover, .admin-nav .nav-link.active { color: #fff; background: rgba(255,255,255,.13); }
    .module-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 22px; box-shadow: 0 1px 4px rgba(0,0,0,.05); }
    .section-title { font-size: .93rem; font-weight: 600; color: #1e293b; }
    .announcement-row { border-top: 1px solid #f1f5f9; padding: 12px 0; }
    .announcement-row:first-of-type { border-top: none; padding-top: 0; }
    .announcement-meta { font-size: .75rem; color: #94a3b8; margin-bottom: 2px; }
</style>
