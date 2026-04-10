<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/helpers.php';

require_admin();

$db       = getDB();
$students = $db->query(
    "SELECT * FROM signups WHERE Role = 'Student' ORDER BY LastName ASC"
)->fetchAll();

$success = get_flash('success');
$error   = get_flash('error');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Students — CCS Admin</title>
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
                    <li class="nav-item"><a class="nav-link active" href="/php/admin/students.php"><i class="bi bi-people me-1"></i>Students</a></li>
                    <li class="nav-item"><a class="nav-link" href="/php/admin/sit_in_records.php"><i class="bi bi-journal-text me-1"></i>Sit-in Records</a></li>
                    <li class="nav-item"><a class="nav-link" href="/php/admin/announcements.php"><i class="bi bi-megaphone me-1"></i>Announcements</a></li>
                    <li class="nav-item"><a class="nav-link" href="/php/admin/feedback.php"><i class="bi bi-chat-left-text me-1"></i>Feedback</a></li>
                    <li class="nav-item"><a class="nav-link" href="/php/admin/reservations.php"><i class="bi bi-calendar-check me-1"></i>Reservations</a></li>
                    <li class="nav-item ms-lg-2"><a class="btn btn-warning btn-sm px-3" href="/php/logout.php">Log out</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="admin-main px-4 py-4">

        <div class="d-flex align-items-center justify-content-between mb-3">
            <h4 class="mb-0 fw-bold">Students</h4>
            <form action="/php/actions/reset_sessions.php" method="post" class="d-inline"
                  onsubmit="return confirm('Reset all student sessions to 30?')">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-repeat me-1"></i>Reset All Sessions
                </button>
            </form>
        </div>

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

        <div class="module-card">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID Number</th>
                            <th>Name</th>
                            <th>Course</th>
                            <th>Year</th>
                            <th>Email</th>
                            <th>Sessions</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($students)): ?>
                            <?php foreach ($students as $s): ?>
                            <tr>
                                <td><?= e($s['IdNumber']) ?></td>
                                <td><?= e($s['LastName'] . ', ' . $s['FirstName']) ?></td>
                                <td><?= e($s['Course']) ?></td>
                                <td><?= e($s['CourseLevel']) ?></td>
                                <td><?= e($s['Email']) ?></td>
                                <td><?= (int)$s['RemainingSessions'] ?></td>
                                <td class="text-nowrap">
                                    <button type="button" class="btn btn-outline-primary btn-sm me-1 edit-btn"
                                            data-id="<?= e($s['IdNumber']) ?>"
                                            data-first="<?= e($s['FirstName']) ?>"
                                            data-last="<?= e($s['LastName']) ?>"
                                            data-level="<?= e($s['CourseLevel']) ?>"
                                            data-course="<?= e($s['Course']) ?>"
                                            data-sessions="<?= (int)$s['RemainingSessions'] ?>">
                                        <i class="bi bi-pencil me-1"></i>Edit
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-sm delete-btn"
                                            data-id="<?= e($s['IdNumber']) ?>"
                                            data-name="<?= e($s['FirstName'].' '.$s['LastName']) ?>">
                                        <i class="bi bi-trash me-1"></i>Delete
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">No students found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Edit Student Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil me-2 text-primary"></i>Edit Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="/php/actions/edit_student.php" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="IdNumber" id="editId" />
                    <div class="modal-body row g-3">
                        <div class="col-md-6">
                            <label class="form-label">First Name</label>
                            <input type="text" name="FirstName" id="editFirst" class="form-control" required />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="LastName" id="editLast" class="form-control" required />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Year Level</label>
                            <select name="CourseLevel" id="editLevel" class="form-select">
                                <option value="1">1st Year</option>
                                <option value="2">2nd Year</option>
                                <option value="3">3rd Year</option>
                                <option value="4">4th Year</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Course</label>
                            <select name="Course" id="editCourse" class="form-select">
                                <option value="BSCS">BSCS</option>
                                <option value="BSIT">BSIT</option>
                                <option value="BSIS">BSIS</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Remaining Sessions</label>
                            <input type="number" name="RemainingSessions" id="editSessions"
                                   class="form-control" min="0" required />
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirm Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title fw-bold text-danger"><i class="bi bi-trash me-2"></i>Delete Student</h6>
                    <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body small pt-2">
                    Delete <strong id="deleteStudentName"></strong>? This cannot be undone.
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <form action="/php/actions/delete_student.php" method="post" class="d-inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="idNumber" id="deleteIdInput" />
                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const editModal   = new bootstrap.Modal(document.getElementById('editModal'));
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));

        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('editId').value      = btn.dataset.id;
                document.getElementById('editFirst').value   = btn.dataset.first;
                document.getElementById('editLast').value    = btn.dataset.last;
                document.getElementById('editLevel').value   = btn.dataset.level;
                document.getElementById('editCourse').value  = btn.dataset.course;
                document.getElementById('editSessions').value = btn.dataset.sessions;
                editModal.show();
            });
        });

        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('deleteIdInput').value   = btn.dataset.id;
                document.getElementById('deleteStudentName').textContent = btn.dataset.name;
                deleteModal.show();
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
    .module-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 22px; box-shadow: 0 1px 4px rgba(0,0,0,.05); }
    .table th { font-size: .75rem; text-transform: uppercase; letter-spacing: .04em; color: #64748b; font-weight: 600; }
    .table td { font-size: .85rem; }
</style>
