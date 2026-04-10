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

$idNumber = trim($_POST['idNumber'] ?? '');
$db       = getDB();
$stmt     = $db->prepare("SELECT * FROM signups WHERE IdNumber = ? AND Role = 'Student' LIMIT 1");
$stmt->execute([$idNumber]);
$student = $stmt->fetch();

if (!$student) {
    set_flash('error', 'Student not found.');
    header('Location: /php/admin/students.php');
    exit;
}

$db->prepare('DELETE FROM signups WHERE IdNumber = ?')->execute([$idNumber]);

set_flash('success', "Student {$student['FirstName']} {$student['LastName']} deleted.");
header('Location: /php/admin/students.php');
exit;
