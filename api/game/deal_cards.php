<?php
header('Content-Type: application/json');
require_once '../db_connect2.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['game_id'])) {
    echo json_encode(["error" => "game_id is required"]);
    exit;
}

$game_id = $data['game_id'];

$stmt = $pdo->prepare("SELECT * FROM games WHERE id = ?");
$stmt->execute([$game_id]);
$game = $stmt->fetch();

if (!$game) {
    echo json_encode(["error" => "Game not found"]);
    exit;
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM game_cards WHERE game_id = ?");
$stmt->execute([$game_id]);
$total_cards = $stmt->fetchColumn();

if ($total_cards == 0) {
    $insert_stmt = $pdo->prepare("INSERT INTO game_cards (game_id, card_id, location) VALUES (?, ?, 'deck')");
    $cards = $pdo->query("SELECT id FROM cards")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($cards as $card_id) {
        $insert_stmt->execute([$game_id, $card_id]);
    }
}

$stmt = $pdo->prepare("
    SELECT gc.card_id, c.suit, c.value
    FROM game_cards gc
    JOIN cards c ON gc.card_id = c.id
    WHERE gc.game_id = ? AND gc.location = 'deck'
");
$stmt->execute([$game_id]);
$deck = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT 
        SUM(location='p1_hand') as p1_count, 
        SUM(location='p2_hand') as p2_count 
    FROM game_cards 
    WHERE game_id = ?
");
$stmt->execute([$game_id]);
$handsCounts = $stmt->fetch(PDO::FETCH_ASSOC);

$p1_hand_count = $handsCounts['p1_count'];
$p2_hand_count = $handsCounts['p2_count'];

if (count($deck) < 36 && $p1_hand_count == 0 && $p2_hand_count == 0) {
    $stmt = $pdo->prepare("UPDATE games SET status = 'finished' WHERE id = ?");
    $stmt->execute([$game_id]);
    $game['status'] = 'finished'; 
}

if ($game['status'] === 'finished') {
    $stmt = $pdo->prepare("
        SELECT gc.location, c.suit, c.value
        FROM game_cards gc
        JOIN cards c ON gc.card_id = c.id
        WHERE gc.game_id = ? AND gc.location IN ('p1_captured','p2_captured')
    ");
    $stmt->execute([$game_id]);
    $captured_cards = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $scores = ['p1' => 0, 'p2' => 0];
    $cards_count = ['p1' => 0, 'p2' => 0];

    foreach ($captured_cards as $card) {
        $player = $card['location'] === 'p1_captured' ? 'p1' : 'p2';
        $cards_count[$player]++;
        if (in_array($card['value'], ['J','Q','K'])) $scores[$player] += 1;
        if ($card['value'] === '10' && $card['suit'] !== 'diamonds') $scores[$player] += 1;
        if ($card['value'] === '2' && $card['suit'] === 'spades') $scores[$player] += 1;
        if ($card['value'] === '10' && $card['suit'] === 'diamonds') $scores[$player] += 1;
    }

    $scores['p1'] += $game['p1_xeri_count'] * 10;
    $scores['p2'] += $game['p2_xeri_count'] * 10;

    if ($cards_count['p1'] > $cards_count['p2']) $scores['p1'] += 3;
    elseif ($cards_count['p2'] > $cards_count['p1']) $scores['p2'] += 3;

    if ($scores['p1'] > $scores['p2']) {
        $winner = 'player1'; $winner_points = $scores['p1'];
        $loser = 'player2'; $loser_points = $scores['p2'];
    } elseif ($scores['p2'] > $scores['p1']) {
        $winner = 'player2'; $winner_points = $scores['p2'];
        $loser = 'player1'; $loser_points = $scores['p1'];
    } else {
        $winner = 'draw'; $winner_points = $scores['p1'];
        $loser = 'draw'; $loser_points = $scores['p2'];
    }

    echo json_encode([
        "status" => "finished",
        "game_id" => $game_id,
        "message" => "Game finished",
        "scores" => $scores,
        "captured_counts" => $cards_count,
        "winner" => $winner,
        "winner_points" => $winner_points,
        "loser" => $loser,
        "loser_points" => $loser_points
    ]);
    exit;
}

if ($p1_hand_count > 0 || $p2_hand_count > 0) {
    echo json_encode([
        "status" => "ok",
        "game_id" => $game_id,
        "message" => "Hands are not empty, no new cards dealt",
        "player1_hand_count" => $p1_hand_count,
        "player2_hand_count" => $p2_hand_count
    ]);
    exit;
}

shuffle($deck);
$player1_cards = array_splice($deck, 0, 6);
$player2_cards = array_splice($deck, 0, 6);

$table_cards = [];
if ($game['first_round_done'] == 0) {
    $table_cards = array_splice($deck, 0, 4);
    $stmt = $pdo->prepare("UPDATE games SET first_round_done = 1 WHERE id = ?");
    $stmt->execute([$game_id]);
}

$update_stmt = $pdo->prepare("UPDATE game_cards SET location = ? WHERE game_id = ? AND card_id = ?");
foreach ($player1_cards as $card) $update_stmt->execute(['p1_hand', $game_id, $card['card_id']]);
foreach ($player2_cards as $card) $update_stmt->execute(['p2_hand', $game_id, $card['card_id']]);
foreach ($table_cards as $card) $update_stmt->execute(['table', $game_id, $card['card_id']]);

function cardToString($card) {
    return strtoupper(substr($card['suit'],0,1)) . $card['value'];
}

echo json_encode([
    "status" => "ok",
    "game_id" => $game_id,
    "player1_cards" => array_map('cardToString', $player1_cards),
    "player2_cards" => array_map('cardToString', $player2_cards),
    "table_cards" => array_map('cardToString', $table_cards),
    "remaining_deck" => count($deck),
    "message" => "Cards dealt successfully"
]);
