<?php
header('Content-Type: application/json');
require_once '../db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['username'])) {
    echo json_encode(["error" => "Username required"]);
    exit;
}

$username = $data['username'];
$token = bin2hex(random_bytes(16)); 

$stmt = $pdo->prepare("SELECT id FROM players WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user) {
    $stmt = $pdo->prepare("UPDATE players SET token = ? WHERE id = ?");
    $stmt->execute([$token, $user['id']]);
} else {
    $stmt = $pdo->prepare("INSERT INTO players (username, token) VALUES (?, ?)");
    $stmt->execute([$username, $token]);
}

echo json_encode([
    "status" => "ok",
    "username" => $username,
    "token" => $token
]);
