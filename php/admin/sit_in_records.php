<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/helpers.php';

require_admin();

$db      = getDB();
$records = $db->query(
    'SELECT r.*, s.FirstName, s.LastName
     FROM sit_in_records r
     LEFT JOIN signups s ON s.IdNumber = r.StudentIdNumber
     ORDER BY r.TimeIn DESC'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sit-in Records — CCS Admin</title>
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
                    <li class="nav-item"><a class="nav-link active" href="/php/admin/sit_in_records.php"><i class="bi bi-journal-text me-1"></i>Sit-in Records</a></li>
                    <li class="nav-item"><a class="nav-link" href="/php/admin/announcements.php"><i class="bi bi-megaphone me-1"></i>Announcements</a></li>
                    <li class="nav-item"><a class="nav-link" href="/php/admin/feedback.php"><i class="bi bi-chat-left-text me-1"></i>Feedback</a></li>
                    <li class="nav-item"><a class="nav-link" href="/php/admin/reservations.php"><i class="bi bi-calendar-check me-1"></i>Reservations</a></li>
                    <li class="nav-item ms-lg-2"><a class="btn btn-warning btn-sm px-3" href="/php/logout.php">Log out</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="admin-main px-4 py-4">
        <h4 class="mb-3 fw-bold">Sit-in Records</h4>

        <div class="module-card">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>ID Number</th>
                            <th>Name</th>
                            <th>Purpose</th>
                            <th>Lab</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Duration</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($records)): ?>
                            <?php foreach ($records as $r):
                                $name    = ($r['FirstName'] !== null) ? trim($r['FirstName'].' '.$r['LastName']) : '-';
                                $timeIn  = new DateTime($r['TimeIn']);
                                $timeOut = $r['TimeOut'] ? new DateTime($r['TimeOut']) : null;
                                $diff    = $timeOut ? $timeOut->diff($timeIn) : null;
                            ?>
                            <tr>
                                <td class="text-muted small"><?= (int)$r['Id'] ?></td>
                                <td><?= e($r['StudentIdNumber']) ?></td>
                                <td><?= e($name) ?></td>
                                <td><?= $r['Purpose'] !== '' ? e($r['Purpose']) : '-' ?></td>
                                <td><?= e($r['Laboratory']) ?></td>
                                <td class="text-nowrap"><?= $timeIn->format('Y-m-d h:i A') ?></td>
                                <td class="text-nowrap"><?= $timeOut ? $timeOut->format('h:i A') : '-' ?></td>
                                <td><?= $diff ? $diff->h . 'h ' . $diff->i . 'm' : '-' ?></td>
                                <td>
                                    <?php if ($r['TimeOut']): ?>
                                        <span class="badge text-bg-secondary">Done</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-success">Active</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="9" class="text-center text-muted py-4">No sit-in records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
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
    .table th { font-size: .75rem; text-transform: uppercase; letter-spacing: .04em; color: #64748b; font-weight: 600; }
    .table td { font-size: .85rem; }
</style>
