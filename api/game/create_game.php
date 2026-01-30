<?php
header('Content-Type: application/json');
require_once '../db_connect2.php';

try {
    $data = json_decode(file_get_contents("php://input"), true);
    if (!isset($data['player_id'])) {
        throw new Exception("player_id is required");
    }
    $player_id = $data['player_id'];

    $stmt = $pdo->prepare("INSERT INTO games (player1_id, status) VALUES (?, ?)");
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . implode(" | ", $pdo->errorInfo()));
    }

    if (!$stmt->execute([$player_id, 'waiting'])) {
        throw new Exception("Failed to execute statement: " . implode(" | ", $stmt->errorInfo()));
    }

    $game_id = $pdo->lastInsertId();
    if (!$game_id) {
        throw new Exception("Failed to get lastInsertId");
    }

    echo json_encode([
        "status" => "ok",
        "game_id" => $game_id,
        "player1_id" => $player_id,
        "message" => "Game created, waiting for player 2"
    ]);

} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
    exit;
}
