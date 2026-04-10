<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/helpers.php';

require_student();

$idNumber = $_SESSION['IdNumber'];
$db       = getDB();

// Load student
$stmt = $db->prepare('SELECT * FROM signups WHERE IdNumber = ? LIMIT 1');
$stmt->execute([$idNumber]);
$student = $stmt->fetch();

if (!$student) {
    session_destroy();
    header('Location: /php/login.php');
    exit;
}

// Reset sessions if depleted
if ((int)$student['RemainingSessions'] <= 0) {
    $db->prepare('UPDATE signups SET RemainingSessions = 30 WHERE IdNumber = ?')->execute([$idNumber]);
    $student['RemainingSessions'] = 30;
}

// Recent sit-in history (last 5)
$stmt = $db->prepare(
    'SELECT * FROM sit_in_records WHERE StudentIdNumber = ?
     ORDER BY TimeIn DESC LIMIT 5'
);
$stmt->execute([$idNumber]);
$recentHistory = $stmt->fetchAll();

// Announcements (last 5)
$announcements = $db->query(
    'SELECT * FROM announcements ORDER BY PostedAt DESC LIMIT 5'
)->fetchAll();

// Unread announcements count
$lastRead = $student['LastAnnouncementsReadAt'];
if ($lastRead) {
    $stmt = $db->prepare('SELECT COUNT(*) FROM announcements WHERE PostedAt > ?');
    $stmt->execute([$lastRead]);
} else {
    $stmt = $db->query('SELECT COUNT(*) FROM announcements');
}
$unreadCount = (int)$stmt->fetchColumn();
$_SESSION['UnreadAnnouncements'] = $unreadCount;

// Active sit-in
$stmt = $db->prepare('SELECT * FROM sit_in_records WHERE StudentIdNumber = ? AND TimeOut IS NULL LIMIT 1');
$stmt->execute([$idNumber]);
$activeSitIn = $stmt->fetch() ?: null;

// Update session profile image
$_SESSION['ProfileImagePath'] = $student['ProfileImagePath'] ?? '';

$today = date('F d, Y');

// Labs that are currently active
$activeLabs = array_column(array_filter($recentHistory, fn($r) => $r['TimeOut'] === null), 'Laboratory');

$success = get_flash('success');
$error   = get_flash('error');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Student Home</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
</head>
<body>
    <?php include __DIR__ . '/partials/user_navbar.php'; ?>

    <div class="dash-wrapper">
        <div class="student-home container py-4 py-md-5">

            <?php if ($success !== ''): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= e($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if ($error !== ''): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= e($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Profile panel -->
            <div class="profile-panel mb-4">
                <div class="row g-4 align-items-center">
                    <div class="col-md-3 text-center">
                        <div class="avatar-wrap mx-auto">
                            <?php $imgSrc = !empty($student['ProfileImagePath'])
                                ? e($student['ProfileImagePath'])
                                : '/php/images/default-avatar.png'; ?>
                            <img src="<?= $imgSrc ?>"
                                 onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode(trim($student['FirstName'].' '.$student['LastName'])) ?>&background=0b4a94&color=fff&size=160&bold=true'"
                                 alt="Student avatar" class="avatar-img" />
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="info-box">
                            <div class="row g-3">
                                <div class="col-lg-7">
                                    <h3 class="mb-3"><?= e(trim($student['FirstName'].' '.$student['LastName'])) ?></h3>
                                    <p class="mb-1"><strong>ID Number:</strong> <?= e($student['IdNumber']) ?></p>
                                    <p class="mb-1"><strong>Year Level:</strong> <?= e($student['CourseLevel']) ?></p>
                                    <p class="mb-1"><strong>Course:</strong> <?= e($student['Course']) ?></p>
                                    <p class="mb-0"><strong>Email:</strong> <?= e($student['Email']) ?></p>
                                </div>
                                <div class="col-lg-5 text-lg-end d-flex flex-column justify-content-between">
                                    <div class="d-flex flex-column align-items-lg-end gap-2">
                                        <div class="sessions-chip align-self-lg-end">
                                            Sessions Remaining: <strong><?= (int)$student['RemainingSessions'] ?></strong>
                                        </div>
                                        <?php if ($activeSitIn): ?>
                                            <div class="sitin-status-chip active">
                                                <span class="sitin-dot"></span>
                                                <span>Sit-in: <strong>Active</strong></span>
                                            </div>
                                            <div class="sitin-details">
                                                <p class="mb-0"><strong>Lab:</strong> <?= e($activeSitIn['Laboratory']) ?></p>
                                                <p class="mb-0"><strong>Time In:</strong> <?= date('h:i A', strtotime($activeSitIn['TimeIn'])) ?></p>
                                                <p class="mb-0"><strong>Purpose:</strong> <?= e($activeSitIn['Purpose']) ?></p>
                                            </div>
                                        <?php else: ?>
                                            <div class="sitin-status-chip inactive">
                                                <span class="sitin-dot"></span>
                                                <span>Sit-in: <strong>Not Active</strong></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <p class="mb-0 mt-3 mt-lg-0"><strong>Date:</strong> <?= $today ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Laboratory availability -->
            <div class="panel-card mb-4">
                <h5 class="panel-title">Laboratory Availability</h5>
                <div class="lab-grid">
                    <?php
                    $labs = ['524','526','528','542','544'];
                    foreach ($labs as $lab):
                        $isOccupied = in_array('Lab '.$lab, $activeLabs, true) || in_array($lab, $activeLabs, true);
                    ?>
                    <div class="lab-card <?= $isOccupied ? 'lab-occupied' : 'lab-available' ?>">
                        <div class="lab-number">Lab <?= $lab ?></div>
                        <div class="lab-status-row">
                            <span class="lab-dot-sm"></span>
                            <span class="lab-status-text"><?= $isOccupied ? 'Occupied' : 'Available' ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Announcements + History -->
            <div class="row g-4 mb-4">
                <div class="col-lg-6" id="announcements">
                    <div class="panel-card h-100">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h5 class="panel-title mb-0">Announcements</h5>
                            <?php if ($unreadCount > 0): ?>
                                <span class="badge text-bg-danger"><?= $unreadCount ?> unread</span>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($announcements)): ?>
                            <?php foreach ($announcements as $ann): ?>
                                <div class="announcement-item">
                                    <div class="announcement-meta">
                                        <?= e($ann['PostedBy']) ?> | <?= date('Y-M-d', strtotime($ann['PostedAt'])) ?>
                                    </div>
                                    <div><strong><?= e($ann['Title']) ?></strong></div>
                                    <div><?= e($ann['Message']) ?></div>
                                </div>
                            <?php endforeach; ?>

                            <form action="/php/actions/mark_read.php" method="post">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-primary">Mark all as read</button>
                            </form>
                        <?php else: ?>
                            <div class="empty-state">No announcements yet. New updates from admin will appear here.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="panel-card h-100">
                        <h5 class="panel-title">Recent Sit-in History</h5>
                        <?php if (!empty($recentHistory)): ?>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Lab</th>
                                            <th>Time In</th>
                                            <th>Time Out</th>
                                            <th>Duration</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentHistory as $item):
                                            $timeIn  = new DateTime($item['TimeIn']);
                                            $timeOut = $item['TimeOut'] ? new DateTime($item['TimeOut']) : null;
                                            $duration = $timeOut ? $timeOut->diff($timeIn) : null;
                                        ?>
                                        <tr>
                                            <td><?= $timeIn->format('Y-m-d') ?></td>
                                            <td><?= e($item['Laboratory']) ?></td>
                                            <td><?= $timeIn->format('h:i A') ?></td>
                                            <td><?= $timeOut ? $timeOut->format('h:i A') : 'In Progress' ?></td>
                                            <td><?= $duration ? $duration->h . 'h ' . $duration->i . 'm' : '-' ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">No sit-in history yet. Your latest 5 sessions will show here.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <?php include __DIR__ . '/partials/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<style>
    html, body { margin: 0; padding: 0; background: #f3f4f6; }
    body { display: flex; flex-direction: column; min-height: 100vh; }
    .dash-wrapper { flex: 1; }
    .student-home { max-width: 1100px; }
    .profile-panel { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 24px; box-shadow: 0 6px 18px rgba(15,23,42,0.06); }
    .avatar-wrap { width: 170px; height: 170px; border: 2px solid #dbeafe; border-radius: 14px; display: flex; align-items: center; justify-content: center; background: #eff6ff; }
    .avatar-img { width: 145px; height: 145px; object-fit: cover; border-radius: 50%; background: #fff; }
    .info-box { border: 1px solid #d1d5db; border-radius: 10px; padding: 18px 20px; background: #fff; }
    .sessions-chip { background: #eaf2ff; color: #0b4a94; border: 1px solid #bfdbfe; padding: 8px 12px; border-radius: 999px; font-size: .95rem; }
    .sitin-status-chip { display: inline-flex; align-items: center; gap: 7px; padding: 8px 14px; border-radius: 999px; font-size: .9rem; }
    .sitin-status-chip.active  { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
    .sitin-status-chip.inactive{ background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
    .sitin-dot { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }
    .sitin-status-chip.active  .sitin-dot { background: #22c55e; box-shadow: 0 0 0 3px rgba(34,197,94,0.2); }
    .sitin-status-chip.inactive .sitin-dot { background: #ef4444; }
    .sitin-details { font-size: .82rem; color: #374151; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 8px 12px; text-align: left; line-height: 1.7; }
    .panel-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 18px; }
    .panel-title { color: #0b4a94; font-weight: 700; margin-bottom: 14px; }
    .lab-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; }
    .lab-card { border-radius: 10px; padding: 16px 14px; text-align: center; border: 1.5px solid; }
    .lab-available { background: #f0fdf4; border-color: #bbf7d0; }
    .lab-occupied  { background: #fef2f2; border-color: #fecaca; }
    .lab-number { font-size: 1rem; font-weight: 700; color: #1e293b; margin-bottom: 8px; }
    .lab-status-row { display: flex; align-items: center; justify-content: center; gap: 6px; }
    .lab-dot-sm { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    .lab-available .lab-dot-sm { background: #22c55e; }
    .lab-occupied  .lab-dot-sm { background: #ef4444; }
    .lab-status-text { font-size: .82rem; font-weight: 600; }
    .lab-available .lab-status-text { color: #15803d; }
    .lab-occupied  .lab-status-text { color: #b91c1c; }
    .announcement-item { padding-bottom: 10px; margin-bottom: 10px; border-bottom: 1px solid #e5e7eb; }
    .announcement-meta { font-weight: 600; margin-bottom: 4px; }
    .empty-state { border: 1px dashed #cbd5e1; background: #f8fafc; border-radius: 10px; padding: 16px; color: #475569; }
    @media (max-width: 768px) { .lab-grid { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 480px) { .lab-grid { grid-template-columns: repeat(2, 1fr); } }
    footer { margin-top: 0 !important; }
</style>
