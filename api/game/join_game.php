<?php
header('Content-Type: application/json');
require_once '../db_connect2.php';

try {

    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);

    if (!$data) {
        throw new Exception("Invalid JSON body");
    }

    if (!isset($data['game_id']) || !isset($data['player2_id'])) {
        throw new Exception("game_id and player2_id are required");
    }

    $game_id = (int)$data['game_id'];
    $player2_id = (int)$data['player2_id'];

    $stmt = $pdo->prepare("SELECT * FROM games WHERE id = ?");
    if (!$stmt->execute([$game_id])) {
        throw new Exception("Failed to fetch game");
    }

    $game = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$game) {
        throw new Exception("Game not found");
    }

    if ($game['status'] !== 'waiting') {
        throw new Exception("Game already started or finished");
    }

    if (!empty($game['player2_id'])) {
        throw new Exception("Game already has player 2");
    }

    if ($game['player1_id'] == $player2_id) {
        throw new Exception("Player cannot join own game");
    }

    $stmt = $pdo->prepare("
        UPDATE games 
        SET player2_id = ?, status = 'playing', current_turn = player1_id 
        WHERE id = ?
    ");

    if (!$stmt->execute([$player2_id, $game_id])) {
        throw new Exception("Update failed: " . implode(" | ", $stmt->errorInfo()));
    }

    echo json_encode([
        "status" => "ok",
        "game_id" => $game_id,
        "player1_id" => $game['player1_id'],
        "player2_id" => $player2_id,
        "current_turn" => $game['player1_id'],
        "message" => "Player 2 joined successfully"
    ]);

} catch (Throwable $e) {

    http_response_code(400);

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
