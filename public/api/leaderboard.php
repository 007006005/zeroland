<?php
/**
 * api/leaderboard.php - Classifica per gioco: GET ?game=slug  (senza game: tutte le classifiche)
 */
require_once __DIR__ . '/db.php';
require_login_api();

$games = arcade_games();
$pdo = db();

$top = function (string $game) use ($pdo): array {
    $st = $pdo->prepare('SELECT u.username, s.score FROM arcade_scores s JOIN users u ON u.id = s.user_id
        WHERE s.game = ? AND s.score > 0 AND u.is_banned = 0 ORDER BY s.score DESC, s.updated_at ASC LIMIT 10');
    $st->execute([$game]);
    $out = [];
    $i = 1;
    foreach ($st->fetchAll() as $r) $out[] = ['rank' => $i++, 'username' => $r['username'], 'score' => (int)$r['score']];
    return $out;
};

$game = (string)($_GET['game'] ?? '');
if ($game !== '') {
    if (!isset($games[$game])) json_response(['success' => false, 'error' => 'Gioco sconosciuto.'], 404);
    json_response(['success' => true, 'game' => $game, 'label' => $games[$game]['score'], 'top' => $top($game)]);
}
$all = [];
foreach ($games as $slug => $g) $all[$slug] = ['title' => $g['title'], 'label' => $g['score'], 'top' => $top($slug)];
json_response(['success' => true, 'boards' => $all]);
