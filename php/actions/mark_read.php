<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /php/home.php');
    exit;
}

require_student();
verify_csrf();

$idNumber = $_SESSION['IdNumber'];
$db       = getDB();

$db->prepare('UPDATE signups SET LastAnnouncementsReadAt = NOW() WHERE IdNumber = ?')
   ->execute([$idNumber]);

$_SESSION['UnreadAnnouncements'] = 0;

header('Location: /php/home.php');
exit;
