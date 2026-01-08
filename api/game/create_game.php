<?php
header('Content-Type: application/json');
require_once '../db_connect2.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['player_id'])) {
    echo json_encode(["error" => "player_id is required"]);
    exit;
}

$player_id = $data['player_id'];

$stmt = $pdo->prepare("INSERT INTO games (player1_id, status) VALUES (?, ?)");
$stmt->execute([$player_id, 'waiting']);

$game_id = $pdo->lastInsertId();

echo json_encode([
    "status" => "ok",
    "game_id" => $game_id,
    "player1_id" => $player_id,
    "message" => "Game created, waiting for player 2"
]);
