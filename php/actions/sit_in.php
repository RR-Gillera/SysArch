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

$studentIdNumber = trim($_POST['StudentIdNumber'] ?? '');
$purpose         = trim($_POST['Purpose']         ?? '');
$laboratory      = trim($_POST['Laboratory']      ?? '');

if ($studentIdNumber === '' || $purpose === '' || $laboratory === '') {
    set_flash('error', 'All fields are required.');
    header('Location: /php/admin/home.php');
    exit;
}

$db   = getDB();
$stmt = $db->prepare("SELECT * FROM signups WHERE IdNumber = ? AND Role = 'Student' LIMIT 1");
$stmt->execute([$studentIdNumber]);
$student = $stmt->fetch();

if (!$student) {
    set_flash('error', 'Student not found.');
    header('Location: /php/admin/home.php');
    exit;
}

// Check for existing active sit-in
$stmt = $db->prepare('SELECT 1 FROM sit_in_records WHERE StudentIdNumber = ? AND TimeOut IS NULL LIMIT 1');
$stmt->execute([$studentIdNumber]);
if ($stmt->fetchColumn()) {
    set_flash('error', 'Student already has an active sit-in session.');
    header('Location: /php/admin/home.php');
    exit;
}

// Insert sit-in record
$db->prepare(
    'INSERT INTO sit_in_records (StudentIdNumber, Purpose, Laboratory, TimeIn) VALUES (?, ?, ?, NOW())'
)->execute([$studentIdNumber, $purpose, $laboratory]);

// Decrement remaining sessions
if ((int)$student['RemainingSessions'] > 0) {
    $db->prepare('UPDATE signups SET RemainingSessions = RemainingSessions - 1 WHERE IdNumber = ?')
       ->execute([$studentIdNumber]);
}

set_flash('success', 'Sit-in recorded for ' . trim($student['FirstName'] . ' ' . $student['LastName']) . '.');
header('Location: /php/admin/home.php');
exit;
