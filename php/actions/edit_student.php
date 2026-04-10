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

$idNumber          = trim($_POST['IdNumber']          ?? '');
$firstName         = trim($_POST['FirstName']         ?? '');
$lastName          = trim($_POST['LastName']          ?? '');
$courseLevel       = trim($_POST['CourseLevel']       ?? '');
$course            = trim($_POST['Course']            ?? '');
$remainingSessions = (int)($_POST['RemainingSessions'] ?? 0);

$db   = getDB();
$stmt = $db->prepare("SELECT * FROM signups WHERE IdNumber = ? AND Role = 'Student' LIMIT 1");
$stmt->execute([$idNumber]);
$student = $stmt->fetch();

if (!$student) {
    set_flash('error', 'Student not found.');
    header('Location: /php/admin/students.php');
    exit;
}

$db->prepare(
    'UPDATE signups
     SET FirstName = ?, LastName = ?, CourseLevel = ?, Course = ?, RemainingSessions = ?
     WHERE IdNumber = ?'
)->execute([
    $firstName ?: $student['FirstName'],
    $lastName  ?: $student['LastName'],
    $courseLevel ?: $student['CourseLevel'],
    $course ?: $student['Course'],
    $remainingSessions,
    $idNumber,
]);

set_flash('success', "Student $firstName $lastName updated successfully.");
header('Location: /php/admin/students.php');
exit;
