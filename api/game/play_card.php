<?php
header('Content-Type: application/json');
require_once '../db_connect2.php';

try {

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['game_id'], $data['player_id'], $data['position'])) {
    throw new Exception("game_id, player_id and position are required");
}

$game_id   = (int)$data['game_id'];
$player_id = (int)$data['player_id'];
$position  = (int)$data['position'];

if ($position < 1) {
    throw new Exception("Position must be 1 or higher");
}

/* ---------------- GAME ---------------- */

$stmt = $pdo->prepare("SELECT * FROM games WHERE id = ?");
$stmt->execute([$game_id]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$game) {
    throw new Exception("Game not found");
}

if ($game['current_turn'] && $game['current_turn'] != $player_id) {
    throw new Exception("Not your turn");
}

$handLocation = $player_id == $game['player1_id'] ? 'p1_hand' : 'p2_hand';

$stmt = $pdo->prepare("
    SELECT gc.id, gc.card_id, c.suit, c.value
    FROM game_cards gc
    JOIN cards c ON gc.card_id = c.id
    WHERE gc.game_id = ? AND gc.location = ?
    ORDER BY gc.id ASC
");
$stmt->execute([$game_id, $handLocation]);
$hand = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($position > count($hand)) {
    throw new Exception("Position out of range. Hand has " . count($hand) . " cards.");
}

$playedCard = $hand[$position - 1];
$card_id = $playedCard['card_id'];

/* ---------------- TABLE ---------------- */

$stmt = $pdo->prepare("
    SELECT gc.id, gc.card_id, c.suit, c.value
    FROM game_cards gc
    JOIN cards c ON gc.card_id = c.id
    WHERE gc.game_id = ? AND gc.location = 'table'
    ORDER BY gc.played_at DESC, gc.id DESC
");
$stmt->execute([$game_id]);
$tableCards = $stmt->fetchAll(PDO::FETCH_ASSOC);

$capturedCards = [];
$isXeri = false;
$isBalesXeri = false;

$lastCard = $tableCards[0] ?? null;

/* ---------------- CAPTURE LOGIC ---------------- */

if ($lastCard && ($lastCard['value'] === $playedCard['value'] || $playedCard['value'] === 'J')) {

    if (count($tableCards) === 1) {

        if ($lastCard['value'] === 'J') {
            $isBalesXeri = true;
            $column = $player_id == $game['player1_id'] ? 'p1_xeri_bales_count' : 'p2_xeri_bales_count';
        } else {
            $isXeri = true;
            $column = $player_id == $game['player1_id'] ? 'p1_xeri_count' : 'p2_xeri_count';
        }

        $pdo->prepare("UPDATE games SET $column = $column + 1 WHERE id = ?")
            ->execute([$game_id]);
    }

    $capturedCards = $tableCards;
    $capturedCards[] = $playedCard;

    $capturedLocation = $player_id == $game['player1_id'] ? 'p1_captured' : 'p2_captured';

    $ids = array_column($capturedCards, 'card_id');

    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "UPDATE game_cards SET location = ? WHERE game_id = ? AND card_id IN ($placeholders)";
        $params = array_merge([$capturedLocation, $game_id], $ids);
        $pdo->prepare($sql)->execute($params);
    }

} else {

    /* ---- NORMAL PLAY ---- */

    $pdo->prepare("
        UPDATE game_cards 
        SET location = 'table', played_at = NOW() 
        WHERE game_id = ? AND card_id = ?
    ")->execute([$game_id, $card_id]);
}

/* ---------------- TURN SWITCH ---------------- */

$nextTurn = $player_id == $game['player1_id']
    ? $game['player2_id']
    : $game['player1_id'];

$pdo->prepare("UPDATE games SET current_turn = ? WHERE id = ?")
    ->execute([$nextTurn, $game_id]);

/* ---------------- BOARD VIEW ---------------- */

function getGameBoard($pdo, $game_id) {

    $stmt = $pdo->prepare("
        SELECT gc.id, gc.card_id, c.suit, c.value, gc.location, gc.played_at
        FROM game_cards gc
        JOIN cards c ON gc.card_id = c.id
        WHERE gc.game_id = ?
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

    $tableTemp = [];

    foreach ($cards as $c) {
        if ($c['location'] === 'deck') {
            $board['deck']++;
            continue;
        }

        $str = strtoupper(substr($c['suit'],0,1)) . $c['value'];

        if ($c['location'] === 'table') {
            $tableTemp[] = $c + ['str'=>$str];
        } else {
            $board[$c['location']][] = $str;
        }
    }

    usort($tableTemp, function($a,$b){
        return strtotime($b['played_at']) <=> strtotime($a['played_at']);
    });

    foreach ($tableTemp as $c) {
        $board['table'][] = $c['str'];
    }

    return $board;
}

/* ---------------- RESPONSE ---------------- */

echo json_encode([
    "status" => "ok",
    "game_id" => $game_id,
    "played_card" => strtoupper(substr($playedCard['suit'],0,1)) . $playedCard['value'],
    "captured_cards" => array_map(fn($c) => strtoupper(substr($c['suit'],0,1)) . $c['value'], $capturedCards),
    "xeri" => $isXeri,
    "bales_xeri" => $isBalesXeri,
    "board" => getGameBoard($pdo, $game_id)
]);

} catch (Throwable $e) {

http_response_code(400);
echo json_encode([
    "status" => "error",
    "message" => $e->getMessage()
]);

}
