<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /php/admin/announcements.php');
    exit;
}

require_admin();
verify_csrf();

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    set_flash('error', 'Invalid announcement.');
    header('Location: /php/admin/announcements.php');
    exit;
}

$db   = getDB();
$stmt = $db->prepare('SELECT 1 FROM announcements WHERE Id = ? LIMIT 1');
$stmt->execute([$id]);
if (!$stmt->fetchColumn()) {
    set_flash('error', 'Announcement not found.');
    header('Location: /php/admin/announcements.php');
    exit;
}

$db->prepare('DELETE FROM announcements WHERE Id = ?')->execute([$id]);

set_flash('success', 'Announcement deleted.');
header('Location: /php/admin/announcements.php');
exit;
