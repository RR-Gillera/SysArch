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

$title    = trim($_POST['Title']    ?? '');
$message  = trim($_POST['Message']  ?? '');
$postedBy = trim($_POST['PostedBy'] ?? '');

if ($title === '' || $message === '') {
    set_flash('error', 'Title and message are required.');
    header('Location: /php/admin/announcements.php');
    exit;
}

$db = getDB();
$db->prepare(
    'INSERT INTO announcements (Title, Message, PostedBy, PostedAt) VALUES (?, ?, ?, NOW())'
)->execute([
    $title,
    $message,
    $postedBy !== '' ? $postedBy : 'CCS Admin',
]);

set_flash('success', 'Announcement posted successfully.');
header('Location: /php/admin/announcements.php');
exit;
