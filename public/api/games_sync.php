<?php
/**
 * api/games_sync.php - Salvataggio risultati di ZeroAgar (compatibile con il client esistente).
 * I valori inviati dal browser sono SOLO una richiesta: il server li limita (tetto per invio,
 * un invio ogni 8 secondi, tetto orario) prima di accreditare monete e XP.
 */
require_once __DIR__ . '/db.php';

$cur = require_login_api();
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') json_response(['success' => false, 'error' => 'Metodo non consentito'], 405);
enforce_same_origin();
$in = read_json_post();
$action = (string)($in['action'] ?? 'submit_game_result');
$pdo = db();

if ($action !== 'submit_game_result') {
    json_response(['success' => false, 'error' => 'Azione non supportata.'], 400);
}
$gameId = (string)($in['game_id'] ?? 'zero_agar');
if ($gameId !== 'zero_agar') json_response(['success' => false, 'error' => 'Gioco non valido.'], 400);

// Antiabuso
$nowT = time();
if (($nowT - (int)($_SESSION['gs_last'] ?? 0)) < 8) {
    json_response(['success' => false, 'error' => 'Troppi invii ravvicinati.'], 429);
}
$_SESSION['gs_last'] = $nowT;
$hourKey = date('YmdH');
if (($_SESSION['gs_hour'] ?? '') !== $hourKey) { $_SESSION['gs_hour'] = $hourKey; $_SESSION['gs_coins'] = 0; }

$score = max(0, min(5000000, (int)($in['score'] ?? 0)));
$kills = max(0, min(200, (int)($in['kills'] ?? 0)));
$particles = max(0, min(5000, (int)($in['particles_delta'] ?? 0)));

$coins = max(0, min(150, (int)($in['coins_earned'] ?? 0)));
$coins = max(0, min($coins, 1200 - (int)$_SESSION['gs_coins']));   // tetto 1200 monete/ora
$_SESSION['gs_coins'] += $coins;
$xp = min(120, $kills * 10 + intdiv($particles, 25));

$pdo->prepare('INSERT INTO arcade_scores (game, user_id, score, updated_at) VALUES (?,?,?,?)
    ON DUPLICATE KEY UPDATE updated_at = IF(VALUES(score) > score, VALUES(updated_at), updated_at), score = GREATEST(score, VALUES(score))')
    ->execute([$gameId, $cur['id'], $score, date('Y-m-d H:i:s')]);

add_rewards($cur['id'], $coins, $xp);
$u = current_user(true);
$best = $pdo->prepare('SELECT score FROM arcade_scores WHERE game = ? AND user_id = ?');
$best->execute([$gameId, $cur['id']]);

json_response([
    'success'      => true,
    'coins_earned' => $coins,
    'earned_xp'    => $xp,
    'total_coins'  => (int)$u['coins'],
    'level'        => (int)$u['level'],
    'new_high'     => (int)$best->fetchColumn(),
]);
