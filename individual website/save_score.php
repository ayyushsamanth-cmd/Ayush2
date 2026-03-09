<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || !isset($_POST['score'])) {
    echo json_encode(['ok' => false]);
    exit;
}

$username = trim((string)$_SESSION['user']);
if ($username === '' || strtolower($username) === 'admin') {
    echo json_encode(['ok' => false]);
    exit;
}

$score = (int)$_POST['score'];
if ($score < 0) {
    $score = 0;
}

$conn = new mysqli('localhost', 'root', '', 'mario_game');
if ($conn->connect_error) {
    echo json_encode(['ok' => false]);
    exit;
}

$table = 'manage';
$stmt = $conn->prepare("UPDATE `$table` SET high_score = GREATEST(COALESCE(high_score, 0), ?) WHERE username = ? LIMIT 1");
if (!$stmt) {
    $conn->close();
    echo json_encode(['ok' => false]);
    exit;
}

$stmt->bind_param('is', $score, $username);
$stmt->execute();
$stmt->close();
$conn->close();

echo json_encode(['ok' => true]);
