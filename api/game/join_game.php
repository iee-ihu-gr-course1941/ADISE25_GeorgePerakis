<?php
header('Content-Type: application/json');
require_once '../db_connect2.php';

try {

    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);

    if (!$data) {
        throw new Exception("Invalid JSON body");
    }

if (!isset($data['game_id']) || !isset($data['player_token'])) {
    echo json_encode(["error" => "game_id and player_token are required"]);
    exit;
}

$game_id = (int)$data['game_id'];
$playerToken = $data['player_token'];

$stmt = $pdo->prepare(
    "SELECT id FROM players WHERE token = ? LIMIT 1"
);
$stmt->execute([$playerToken]);

$player = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$player) {
    echo json_encode(["error" => "Invalid player token"]);
    exit;
}

$player2_id = (int)$player['id'];

$stmt = $pdo->prepare("SELECT * FROM games WHERE id = ?");
$stmt->execute([$game_id]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$game) {
        throw new Exception("Game not found");
    }

if ($game['status'] !== 'waiting') {
    echo json_encode(["error" => "Game already started or finished"]);
    exit;
}

if ((int)$game['player1_id'] === $player2_id) {
    echo json_encode(["error" => "Player cannot join their own game"]);
    exit;
}

$stmt = $pdo->prepare("
    UPDATE games
    SET player2_id = ?, status = 'playing', current_turn = player1_id
    WHERE id = ?
");
$stmt->execute([$player2_id, $game_id]);

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
