<?php
/**
 * api/chat.php - Chat globale e presenza online.
 * GET  ?since=ID  -> messaggi più recenti di ID (senza since: ultimi 50) + giocatori online
 * POST {text}     -> invia un messaggio (l'identità viene sempre dalla sessione)
 */
require_once __DIR__ . '/db.php';

$user = require_login_api();
$pdo = db();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    touch_presence($user['id']);
    $since = isset($_GET['since']) ? max(0, (int)$_GET['since']) : -1;

    if ($since < 0) {
        $st = $pdo->query('SELECT * FROM (SELECT id, username, text, created_at FROM lobby_chat ORDER BY id DESC LIMIT 50) t ORDER BY id ASC');
    } else {
        $st = $pdo->prepare('SELECT id, username, text, created_at FROM lobby_chat WHERE id > ? ORDER BY id ASC LIMIT 100');
        $st->execute([$since]);
    }
    $messages = [];
    foreach ($st->fetchAll() as $r) {
        $messages[] = [
            'id'        => 'msg_' . $r['id'],
            'seq'       => (int)$r['id'],
            'username'  => $r['username'],
            'avatar'    => '👾',
            'text'      => $r['text'],
            'type'      => 'message',
            'timestamp' => date('c', strtotime((string)$r['created_at'])),
        ];
    }

    $on = $pdo->prepare('SELECT username, level, clan FROM users WHERE last_seen >= ? AND is_banned = 0 ORDER BY last_seen DESC LIMIT 50');
    $on->execute([date('Y-m-d H:i:s', time() - 120)]);
    $online = $on->fetchAll();

    json_response([
        'success'      => true,
        'messages'     => $messages,
        'online'       => $online,
        'online_count' => max(1, count($online)),
    ]);
}

enforce_same_origin();
$in = read_json_post();
$text = (string)($in['text'] ?? '');
$text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? '';
$text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
$text = mb_substr($text, 0, 200);
if ($text === '') json_response(['success' => false, 'error' => 'Messaggio vuoto.'], 400);

// Antiflood: 1 messaggio ogni 2 secondi
$last = $pdo->prepare('SELECT created_at FROM lobby_chat WHERE user_id = ? ORDER BY id DESC LIMIT 1');
$last->execute([$user['id']]);
$lt = $last->fetchColumn();
if ($lt && (time() - strtotime((string)$lt)) < 2) {
    json_response(['success' => false, 'error' => 'Stai scrivendo troppo velocemente.'], 429);
}

$pdo->prepare('INSERT INTO lobby_chat (user_id, username, text, created_at) VALUES (?,?,?,?)')
    ->execute([$user['id'], $user['username'], $text, date('Y-m-d H:i:s')]);
if (mt_rand(1, 40) === 1) {
    $pdo->exec('DELETE FROM lobby_chat WHERE id < (SELECT m FROM (SELECT MAX(id) - 500 AS m FROM lobby_chat) x)');
}
json_response(['success' => true]);
