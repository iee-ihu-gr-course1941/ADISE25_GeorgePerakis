<?php
header('Content-Type: application/json');
require_once '../db_connect2.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['game_id']) || !isset($data['player2_id'])) {
    echo json_encode(["error" => "game_id and player2_id are required"]);
    exit;
}

$game_id = $data['game_id'];
$player2_id = $data['player2_id'];

$stmt = $pdo->prepare("SELECT * FROM games WHERE id = ?");
$stmt->execute([$game_id]);
$game = $stmt->fetch();

if (!$game) {
    echo json_encode(["error" => "Game not found"]);
    exit;
}

if ($game['status'] !== 'waiting') {
    echo json_encode(["error" => "Game already started or finished"]);
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
    "message" => "Player 2 joined, game in progress"
]);
