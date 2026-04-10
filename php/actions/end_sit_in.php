<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /php/admin/home.php');
    exit;
}

require_admin();
verify_csrf();

$sitInId = (int)($_POST['sitInId'] ?? 0);
if ($sitInId <= 0) {
    set_flash('error', 'Invalid sit-in session.');
    header('Location: /php/admin/home.php');
    exit;
}

$db   = getDB();
$stmt = $db->prepare('SELECT * FROM sit_in_records WHERE Id = ? AND TimeOut IS NULL LIMIT 1');
$stmt->execute([$sitInId]);
$record = $stmt->fetch();

if (!$record) {
    set_flash('error', 'Active sit-in session not found.');
    header('Location: /php/admin/home.php');
    exit;
}

$db->prepare('UPDATE sit_in_records SET TimeOut = NOW() WHERE Id = ?')
   ->execute([$sitInId]);

set_flash('success', 'Sit-in session ended successfully.');
header('Location: /php/admin/home.php');
exit;
