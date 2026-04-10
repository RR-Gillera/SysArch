<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/helpers.php';

require_admin();

$db = getDB();

// Stats
$studentsRegistered = (int)$db->query("SELECT COUNT(*) FROM signups WHERE Role = 'Student'")->fetchColumn();
$totalSitInRecords  = (int)$db->query('SELECT COUNT(*) FROM sit_in_records')->fetchColumn();

// Current sit-ins (no timeout)
$currentSitIns = $db->query(
    'SELECT r.*, s.FirstName, s.LastName
     FROM sit_in_records r
     LEFT JOIN signups s ON s.IdNumber = r.StudentIdNumber
     WHERE r.TimeOut IS NULL
     ORDER BY r.TimeIn DESC
     LIMIT 10'
)->fetchAll();

$currentlySitIn = count($currentSitIns);

// Latest announcements
$announcements = $db->query(
    'SELECT * FROM announcements ORDER BY PostedAt DESC LIMIT 5'
)->fetchAll();

$userName = e($_SESSION['UserName'] ?? 'Admin');
$success  = get_flash('success');
$error    = get_flash('error');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
</head>
<body class="admin-page">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark admin-nav sticky-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-semibold" href="#">CCS Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#adminNav" aria-controls="adminNav"
                    aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNav">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                    <li class="nav-item">
                        <a class="nav-link active" href="/php/admin/home.php">
                            <i class="bi bi-house-door me-1"></i>Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/php/admin/students.php">
                            <i class="bi bi-people me-1"></i>Students
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/php/admin/sit_in_records.php">
                            <i class="bi bi-journal-text me-1"></i>Sit-in Records
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/php/admin/announcements.php">
                            <i class="bi bi-megaphone me-1"></i>Announcements
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/php/admin/feedback.php">
                            <i class="bi bi-chat-left-text me-1"></i>Feedback
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/php/admin/reservations.php">
                            <i class="bi bi-calendar-check me-1"></i>Reservations
                        </a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-warning btn-sm px-3" href="/php/logout.php">Log out</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="admin-main px-4 py-4">

        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h4 class="mb-0 fw-bold">Admin Dashboard</h4>
                <p class="text-muted small mb-0">Welcome back, <?= $userName ?></p>
            </div>
        </div>

        <!-- Stat Cards -->
        <section class="row g-3 mb-4">
            <div class="col-sm-4">
                <div class="stat-card">
                    <div class="stat-icon bg-primary-soft text-primary">
                        <i class="bi bi-person-check fs-4"></i>
                    </div>
                    <div>
                        <div class="stat-label">Students Registered</div>
                        <div class="stat-value"><?= $studentsRegistered ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="stat-card">
                    <div class="stat-icon bg-success-soft text-success">
                        <i class="bi bi-display fs-4"></i>
                    </div>
                    <div>
                        <div class="stat-label">Currently Sit-in</div>
                        <div class="stat-value"><?= $currentlySitIn ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="stat-card">
                    <div class="stat-icon bg-warning-soft text-warning">
                        <i class="bi bi-journal-richtext fs-4"></i>
                    </div>
                    <div>
                        <div class="stat-label">Total Sit-in Sessions</div>
                        <div class="stat-value"><?= $totalSitInRecords ?></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Sit-in Form + Current Sit-ins -->
        <section class="row g-4 mb-4">

            <!-- Sit-in Form -->
            <div class="col-lg-4">
                <div class="module-card h-100">
                    <h5 class="section-title mb-3">
                        <i class="bi bi-person-plus me-2 text-primary"></i>New Sit-in
                    </h5>

                    <?php if ($error !== ''): ?>
                        <div class="alert alert-danger alert-dismissible py-2 small fade show" role="alert">
                            <i class="bi bi-exclamation-circle me-1"></i><?= e($error) ?>
                            <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    <?php if ($success !== ''): ?>
                        <div class="alert alert-success alert-dismissible py-2 small fade show" role="alert">
                            <i class="bi bi-check-circle me-1"></i><?= e($success) ?>
                            <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form id="sitInForm" action="/php/actions/sit_in.php" method="post">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">ID Number</label>
                            <div class="input-group input-group-sm">
                                <input type="text" id="idNumberInput" class="form-control"
                                       placeholder="Enter student ID" autocomplete="off" />
                                <button type="button" class="btn btn-primary" id="lookupBtn">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                            <div id="lookupFeedback" class="form-text text-danger d-none">
                                <i class="bi bi-exclamation-circle me-1"></i>Student not found.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Student Name</label>
                            <input type="text" id="studentName" class="form-control form-control-sm bg-light"
                                   readonly placeholder="Auto-populated" />
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Remaining Sessions</label>
                            <input type="text" id="remainingSessions"
                                   class="form-control form-control-sm bg-light"
                                   readonly placeholder="Auto-populated" />
                            <input type="hidden" id="studentIdHidden" name="StudentIdNumber" />
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Purpose</label>
                            <input type="text" name="Purpose" class="form-control form-control-sm"
                                   placeholder="e.g. C Programming" required />
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Laboratory</label>
                            <select name="Laboratory" class="form-select form-select-sm" required>
                                <option value="" disabled selected>Select lab</option>
                                <option value="Lab 524">Lab 524</option>
                                <option value="Lab 526">Lab 526</option>
                                <option value="Lab 528">Lab 528</option>
                                <option value="Lab 542">Lab 542</option>
                                <option value="Lab 544">Lab 544</option>
                            </select>
                        </div>

                        <button type="submit" id="sitInBtn" class="btn btn-success btn-sm w-100" disabled>
                            <i class="bi bi-box-arrow-in-right me-1"></i>Sit In
                        </button>
                    </form>
                </div>
            </div>

            <!-- Active Sit-ins Table -->
            <div class="col-lg-8">
                <div class="module-card h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="section-title mb-0">
                            <i class="bi bi-display me-2 text-success"></i>Current Sit-in
                        </h5>
                        <span class="badge bg-success rounded-pill"><?= $currentlySitIn ?> active</span>
                    </div>
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
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($currentSitIns)): ?>
                                    <?php foreach ($currentSitIns as $sit):
                                        $name = $sit['FirstName'] !== null
                                            ? trim($sit['FirstName'] . ' ' . $sit['LastName'])
                                            : '-';
                                    ?>
                                    <tr>
                                        <td class="text-muted small"><?= (int)$sit['Id'] ?></td>
                                        <td><?= e($sit['StudentIdNumber']) ?></td>
                                        <td><?= e($name) ?></td>
                                        <td><?= $sit['Purpose'] !== '' ? e($sit['Purpose']) : '-' ?></td>
                                        <td><?= e($sit['Laboratory']) ?></td>
                                        <td class="text-nowrap"><?= date('h:i A', strtotime($sit['TimeIn'])) ?></td>
                                        <td><span class="badge text-bg-success">Active</span></td>
                                        <td>
                                            <button type="button"
                                                    class="btn btn-outline-danger btn-sm close-sitin-btn"
                                                    data-sit-id="<?= (int)$sit['Id'] ?>"
                                                    data-student-name="<?= e($name) ?>">
                                                <i class="bi bi-stop-circle me-1"></i>End
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            <i class="bi bi-inbox fs-5 d-block mb-1"></i>No active sit-ins.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        <!-- Latest Announcements Preview -->
        <section class="module-card mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="section-title mb-0">
                    <i class="bi bi-megaphone me-2 text-warning"></i>Latest Announcements
                </h5>
                <a href="/php/admin/announcements.php" class="btn btn-outline-primary btn-sm">
                    Manage <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
            <?php if (!empty($announcements)): ?>
                <?php foreach ($announcements as $item): ?>
                    <div class="announcement-row">
                        <div class="announcement-meta">
                            <i class="bi bi-person-circle me-1"></i><?= e($item['PostedBy']) ?>
                            &nbsp;·&nbsp; <?= date('M d, Y', strtotime($item['PostedAt'])) ?>
                        </div>
                        <div class="fw-semibold small"><?= e($item['Title']) ?></div>
                        <div class="text-muted small"><?= e($item['Message']) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-muted small py-2">
                    <i class="bi bi-info-circle me-1"></i>No announcements posted yet.
                    <a href="/php/admin/announcements.php" class="ms-2">Post one now</a>
                </div>
            <?php endif; ?>
        </section>

    </main>

    <!-- Confirm End Sit-in Modal -->
    <div class="modal fade" id="closeSitInModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title fw-bold">
                        <i class="bi bi-stop-circle text-danger me-2"></i>End Sit-in Session
                    </h6>
                    <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-2 small">
                    End session for <strong id="closeSitInStudentName"></strong>?
                    The current time will be recorded as the time-out.
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <form id="closeSitInForm" action="/php/actions/end_sit_in.php" method="post" class="d-inline">
                        <?= csrf_field() ?>
                        <input type="hidden" id="closeSitInId" name="sitInId" />
                        <button type="submit" class="btn btn-danger btn-sm">
                            <i class="bi bi-stop-circle me-1"></i>End Session
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sit-in AJAX lookup
        const lookupBtn    = document.getElementById('lookupBtn');
        const idInput      = document.getElementById('idNumberInput');
        const nameField    = document.getElementById('studentName');
        const sessField    = document.getElementById('remainingSessions');
        const hiddenId     = document.getElementById('studentIdHidden');
        const sitInBtn     = document.getElementById('sitInBtn');
        const fbk          = document.getElementById('lookupFeedback');

        function resetFields() {
            nameField.value = sessField.value = hiddenId.value = '';
            sitInBtn.disabled = true;
            fbk.classList.add('d-none');
        }

        async function lookupStudent() {
            const id = idInput.value.trim();
            if (!id) return;
            resetFields();
            try {
                const res  = await fetch('/php/actions/lookup_student.php?idNumber=' + encodeURIComponent(id));
                const data = await res.json();
                if (data.found) {
                    nameField.value   = data.name;
                    sessField.value   = data.remainingSessions;
                    hiddenId.value    = data.idNumber;
                    sitInBtn.disabled = false;
                } else {
                    fbk.classList.remove('d-none');
                }
            } catch { fbk.classList.remove('d-none'); }
        }

        lookupBtn.addEventListener('click', lookupStudent);
        idInput.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); lookupStudent(); } });
        idInput.addEventListener('input', resetFields);

        // End Sit-in modal
        const closeSitInModal = new bootstrap.Modal(document.getElementById('closeSitInModal'));
        document.querySelectorAll('.close-sitin-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('closeSitInId').value = btn.dataset.sitId;
                document.getElementById('closeSitInStudentName').textContent = btn.dataset.studentName;
                closeSitInModal.show();
            });
        });
    </script>
</body>
</html>

<style>
    .admin-page  { min-height: 100vh; display: flex; flex-direction: column; background: #f0f4f8; }
    .admin-main  { flex: 1; max-width: 1300px; margin: 0 auto; width: 100%; }
    .admin-nav { background: #0a2a6e; box-shadow: 0 2px 8px rgba(0,0,0,.18); }
    .admin-nav .navbar-brand { font-size: .95rem; color: #fff; }
    .admin-nav .nav-link { color: rgba(255,255,255,.78); font-size: .82rem; padding: .4rem .65rem; border-radius: 6px; transition: background .15s; }
    .admin-nav .nav-link:hover, .admin-nav .nav-link.active { color: #fff; background: rgba(255,255,255,.13); }
    .stat-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 20px; display: flex; align-items: center; gap: 16px; box-shadow: 0 1px 4px rgba(0,0,0,.05); }
    .stat-icon  { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .bg-primary-soft { background: #e8f0fe; }
    .bg-success-soft { background: #e6f4ea; }
    .bg-warning-soft { background: #fef9e7; }
    .stat-label { font-size: .8rem; color: #64748b; }
    .stat-value { font-size: 1.75rem; font-weight: 700; color: #0f172a; line-height: 1.1; }
    .module-card  { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 22px; box-shadow: 0 1px 4px rgba(0,0,0,.05); }
    .section-title { font-size: .93rem; font-weight: 600; color: #1e293b; }
    .table th { font-size: .75rem; text-transform: uppercase; letter-spacing: .04em; color: #64748b; font-weight: 600; white-space: nowrap; }
    .table td { font-size: .85rem; }
    .announcement-row { border-top: 1px solid #f1f5f9; padding: 10px 0; }
    .announcement-row:first-of-type { border-top: none; padding-top: 0; }
    .announcement-meta { font-size: .75rem; color: #94a3b8; margin-bottom: 2px; }
</style>
