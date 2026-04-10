<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/helpers.php';

require_admin();

header('Content-Type: application/json');

$idNumber = trim($_GET['idNumber'] ?? '');
if ($idNumber === '') {
    echo json_encode(['found' => false]);
    exit;
}

$db   = getDB();
$stmt = $db->prepare("SELECT IdNumber, FirstName, LastName, RemainingSessions FROM signups WHERE IdNumber = ? AND Role = 'Student' LIMIT 1");
$stmt->execute([$idNumber]);
$student = $stmt->fetch();

if (!$student) {
    echo json_encode(['found' => false]);
    exit;
}

echo json_encode([
    'found'             => true,
    'idNumber'          => $student['IdNumber'],
    'name'              => trim($student['FirstName'] . ' ' . $student['LastName']),
    'remainingSessions' => (int)$student['RemainingSessions'],
]);
