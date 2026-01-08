<?php
header('Content-Type: application/json');
require_once '../db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['game_id'], $data['player_id'], $data['position'])) {
    echo json_encode(["error" => "game_id, player_id and position are required"]);
    exit;
}

$game_id   = $data['game_id'];
$player_id = $data['player_id'];
$position  = (int)$data['position']; 

if ($position < 1) {
    echo json_encode(["error" => "Position must be 1 or higher"]);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM games WHERE id = ?");
$stmt->execute([$game_id]);
$game = $stmt->fetch();

if (!$game) {
    echo json_encode(["error" => "Game not found"]);
    exit;
}

if ($game['current_turn'] && $game['current_turn'] != $player_id) {
    echo json_encode(["error" => "Not your turn"]);
    exit;
}

$handLocation = $player_id == $game['player1_id'] ? 'p1_hand' : 'p2_hand';

$stmt = $pdo->prepare("
    SELECT gc.card_id, c.suit, c.value
    FROM game_cards gc
    JOIN cards c ON gc.card_id = c.id
    WHERE gc.game_id = ? AND gc.location = ?
    ORDER BY gc.card_id ASC
");
$stmt->execute([$game_id, $handLocation]);
$hand = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($position > count($hand)) {
    echo json_encode(["error" => "Position out of range. Hand has " . count($hand) . " cards."]);
    exit;
}

$playedCard = $hand[$position - 1];
$card_id = $playedCard['card_id']; 

$stmt = $pdo->prepare("
    SELECT gc.card_id, c.suit, c.value
    FROM game_cards gc
    JOIN cards c ON gc.card_id = c.id
    WHERE gc.game_id = ? AND gc.location = 'table'
    ORDER BY gc.played_at DESC
");
$stmt->execute([$game_id]);
$tableCards = $stmt->fetchAll(PDO::FETCH_ASSOC);

$capturedCards = [];
$isXeri = false;
$lastCard = reset($tableCards); 

if ($lastCard && ($lastCard['value'] === $playedCard['value'] || $playedCard['value'] === 'J')) {
    if (count($tableCards) === 1) {
        if ($lastCard['value'] === 'J')
        {
            $isBalesXeri = true;
            $column = $player_id == $game['player1_id'] ? 'p1_xeri_bales_count' : 'p2_xeri_bales_count';
            $pdo->prepare("UPDATE games SET $column = $column + 1 WHERE id = ?")->execute([$game_id]);
        }
        else
        {
            $isXeri = true;
            $column = $player_id == $game['player1_id'] ? 'p1_xeri_count' : 'p2_xeri_count';
            $pdo->prepare("UPDATE games SET $column = $column + 1 WHERE id = ?")->execute([$game_id]);
        }
    } else {
        $capturedCards = $tableCards;
        $capturedCards[] = $playedCard;
        $capturedLocation = $player_id == $game['player1_id'] ? 'p1_captured' : 'p2_captured';
        $ids = array_column($capturedCards, 'card_id');
        $pdo->prepare("UPDATE game_cards SET location = ? WHERE game_id = ? AND card_id IN (" . implode(',', $ids) . ")")
            ->execute([$capturedLocation, $game_id]);
    }
} else {
    $pdo->prepare("UPDATE game_cards SET location = 'table', played_at = NOW() WHERE game_id = ? AND card_id = ?")
        ->execute([$game_id, $card_id]);
}

$nextTurn = $player_id == $game['player1_id'] ? $game['player2_id'] : $game['player1_id'];
$pdo->prepare("UPDATE games SET current_turn = ? WHERE id = ?")->execute([$nextTurn, $game_id]);

function getGameBoard($pdo, $game_id) {
    $stmt = $pdo->prepare("
        SELECT gc.card_id, c.suit, c.value, gc.location
        FROM game_cards gc
        JOIN cards c ON gc.card_id = c.id
        WHERE gc.game_id = ?
        ORDER BY gc.played_at DESC
    ");
    $stmt->execute([$game_id]);
    $cards = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $board = [
        'p1_hand' => [],
        'p2_hand' => [],
        'table' => [],
        'p1_captured' => [],
        'p2_captured' => [],
        'deck' => 0 
    ];

    foreach ($cards as $c) {
        if ($c['location'] === 'deck') {
            $board['deck']++;
        } else {
            $str = strtoupper(substr($c['suit'],0,1)) . $c['value'];
            if ($c['location'] === 'table') {
                $board['table'][] = $str; 
            } else {
                $board[$c['location']][] = $str;
            }
        }
    }

    return $board;
}


echo json_encode([
    "status" => "ok",
    "game_id" => $game_id,
    "played_card" => strtoupper(substr($playedCard['suit'],0,1)) . $playedCard['value'],
    "captured_cards" => array_map(function($c){ return strtoupper(substr($c['suit'],0,1)) . $c['value']; }, $capturedCards),
    "xeri" => $isXeri,
    "board" => getGameBoard($pdo, $game_id)
]);
