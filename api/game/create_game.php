<?php
header('Content-Type: application/json');
require_once '../db_connect2.php';

try {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['player_token'])) {
        throw new Exception("player token is required");
    }

    $playerToken = $data['player_token'];

    $stmt = $pdo->prepare(
        "SELECT id FROM players WHERE token = ? LIMIT 1"
    );
    $stmt->execute([$playerToken]);

    $player = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$player) {
        throw new Exception("Invalid player token");
    }

    $player_id = $player['id'];

    $stmt = $pdo->prepare(
        "INSERT INTO games (player1_id, status) VALUES (?, ?)"
    );
    $stmt->execute([$player_id, 'waiting']);

    echo json_encode([
        "status" => "ok",
        "game_id" => $pdo->lastInsertId(),
        "player1_id" => $player_id,
        "message" => "Game created, waiting for player 2"
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
