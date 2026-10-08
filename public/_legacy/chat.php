<?php
/** Chat globale: identità presa dalla sessione, mai dal browser. */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/game_logic.php';

$user = require_login_api();
$pdo = db();

function chat_row_to_message(array $r): array {
    return [
        'id'         => 'msg_' . $r['id'],
        'username'   => $r['username'],
        'avatar'     => $r['avatar'],
        'text'       => $r['text'],
        'type'       => $r['type'],
        'timestamp'  => date('c', strtotime($r['created_at'])),
        'score_data' => ($r['type'] === 'score_share' && $r['score_game'] !== null)
            ? ['game_name' => $r['score_game'], 'score' => (int)$r['score_value']]
            : null,
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $pdo->prepare('UPDATE users SET last_seen = ? WHERE id = ?')->execute([date('Y-m-d H:i:s'), $user['id']]);

    $limit = min(100, max(10, (int)($_GET['limit'] ?? 50)));
    $st = $pdo->prepare('SELECT * FROM (SELECT * FROM chat_messages ORDER BY id DESC LIMIT ' . $limit . ') t ORDER BY id ASC');
    $st->execute();
    $messages = array_map('chat_row_to_message', $st->fetchAll());

    $on = $pdo->prepare('SELECT COUNT(*) FROM users WHERE last_seen >= ?');
    $on->execute([date('Y-m-d H:i:s', time() - 300)]);

    json_response([
        'success'      => true,
        'messages'     => $messages,
        'online_count' => max(1, (int)$on->fetchColumn()),
    ]);
}

$in = read_json_post();
$type = (string)($in['type'] ?? 'message');
$type = ($type === 'chat:share_score' || $type === 'score_share') ? 'score_share' : 'message';

// Antiflood: 1 messaggio ogni 1,5 secondi
$last = $pdo->prepare('SELECT created_at FROM chat_messages WHERE user_id = ? ORDER BY id DESC LIMIT 1');
$last->execute([$user['id']]);
$lt = $last->fetchColumn();
if ($lt && (time() - strtotime($lt)) < 2 && strtotime($lt) <= time()) {
    json_response(['success' => false, 'error' => 'Stai scrivendo troppo velocemente'], 429);
}

$scoreGame = null;
$scoreValue = null;
if ($type === 'score_share') {
    // Il punteggio condiviso è SEMPRE quello reale salvato nel database
    $best = 0;
    $bestGame = 'zero_agar';
    foreach ($user['stats']['highscores'] as $g => $sc) {
        if ($sc > $best) { $best = (int)$sc; $bestGame = $g; }
    }
    if ($best <= 0) {
        json_response(['success' => false, 'error' => 'Non hai ancora un record da condividere'], 400);
    }
    $names = [
        'zero_agar' => 'ZeroAgar Arena', 'space_defender' => 'Space Defender',
        'cyber_runner' => 'Cyber Runner', 'neon_breaker' => 'Neon Breaker',
    ];
    $scoreGame = $names[$bestGame] ?? 'Arcade';
    $scoreValue = $best;
    $text = 'Ha condiviso il suo record in ' . $scoreGame . ': ' . number_format($best, 0, ',', '.') . ' pt!';
} else {
    $text = chat_filter_text((string)($in['text'] ?? ''));
    if ($text === '') {
        json_response(['success' => false, 'error' => 'Messaggio vuoto'], 400);
    }
}

$now = date('Y-m-d H:i:s');
$pdo->prepare(
    'INSERT INTO chat_messages (user_id, username, avatar, text, type, score_game, score_value, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
)->execute([$user['id'], $user['username'], $user['avatar'], $text, $type, $scoreGame, $scoreValue, $now]);
$newId = (int)$pdo->lastInsertId();

// Pulizia occasionale: tieni solo gli ultimi 500 messaggi
if (random_int(1, 25) === 1) {
    $pdo->exec('DELETE FROM chat_messages WHERE id <= (SELECT id FROM (SELECT id FROM chat_messages ORDER BY id DESC LIMIT 1 OFFSET 500) x)');
}

$row = $pdo->prepare('SELECT * FROM chat_messages WHERE id = ?');
$row->execute([$newId]);
json_response(['success' => true, 'message' => chat_row_to_message($row->fetch())]);
