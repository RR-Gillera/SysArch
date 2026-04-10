<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/helpers.php';

require_admin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Feedback — CCS Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
</head>
<body class="admin-page">
    <nav class="navbar navbar-expand-lg navbar-dark admin-nav sticky-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-semibold" href="/php/admin/home.php">CCS Admin</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                    <li class="nav-item"><a class="nav-link" href="/php/admin/home.php"><i class="bi bi-house-door me-1"></i>Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="/php/admin/students.php"><i class="bi bi-people me-1"></i>Students</a></li>
                    <li class="nav-item"><a class="nav-link" href="/php/admin/sit_in_records.php"><i class="bi bi-journal-text me-1"></i>Sit-in Records</a></li>
                    <li class="nav-item"><a class="nav-link" href="/php/admin/announcements.php"><i class="bi bi-megaphone me-1"></i>Announcements</a></li>
                    <li class="nav-item"><a class="nav-link active" href="/php/admin/feedback.php"><i class="bi bi-chat-left-text me-1"></i>Feedback</a></li>
                    <li class="nav-item"><a class="nav-link" href="/php/admin/reservations.php"><i class="bi bi-calendar-check me-1"></i>Reservations</a></li>
                    <li class="nav-item ms-lg-2"><a class="btn btn-warning btn-sm px-3" href="/php/logout.php">Log out</a></li>
                </ul>
            </div>
        </div>
    </nav>
    <main class="admin-main px-4 py-5 text-center text-muted">
        <i class="bi bi-chat-left-text fs-1 d-block mb-3"></i>
        <h5>Feedback</h5>
        <p>This section is coming soon.</p>
    </main>
</body>
</html>
<style>
    .admin-page { min-height: 100vh; display: flex; flex-direction: column; background: #f0f4f8; }
    .admin-main { flex: 1; max-width: 1300px; margin: 0 auto; width: 100%; }
    .admin-nav { background: #0a2a6e; }
    .admin-nav .navbar-brand, .admin-nav .nav-link { color: rgba(255,255,255,.85); font-size: .9rem; }
    .admin-nav .nav-link:hover, .admin-nav .nav-link.active { color: #fff; }
</style>
