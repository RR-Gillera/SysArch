<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /php/admin/students.php');
    exit;
}

require_admin();
verify_csrf();

$db = getDB();
$db->exec("UPDATE signups SET RemainingSessions = 30 WHERE Role = 'Student'");

set_flash('success', 'All student sessions reset to 30.');
header('Location: /php/admin/students.php');
exit;
