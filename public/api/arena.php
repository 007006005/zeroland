<?php
/** 
 * api/arena.php - Sincronizzazione Multiplayer e Chat Arena
 */
require_once __DIR__ . '/db.php';

$cur = require_login_api();
enforce_same_origin();
$in = read_json_post();
$pdo = db();
$uid = $cur['id'];

if (!empty($cur['is_banned'])) {
    json_response(['success' => false, 'error' => 'Sei stato bannato dal server.'], 403);
}

try { $pdo->query('SELECT 1 FROM arena_state LIMIT 1'); } catch (PDOException $e) {
    $o = ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
    $pdo->exec('CREATE TABLE IF NOT EXISTS arena_state (room VARCHAR(40) NOT NULL, user_id VARCHAR(24) NOT NULL, name VARCHAR(24) NOT NULL,
        team TINYINT NOT NULL DEFAULT 0, color VARCHAR(9) NOT NULL DEFAULT \'#00f0ff\', skin VARCHAR(8) NULL, cells TEXT NOT NULL, ts BIGINT NOT NULL,
        PRIMARY KEY (room, user_id), KEY idx_ts (ts))' . $o);
    $pdo->exec('CREATE TABLE IF NOT EXISTS arena_events (id INT UNSIGNED NOT NULL AUTO_INCREMENT, room VARCHAR(40) NOT NULL, to_user VARCHAR(24) NOT NULL,
        killer VARCHAR(24) NOT NULL, x INT NOT NULL, y INT NOT NULL, m INT NOT NULL, ts BIGINT NOT NULL, PRIMARY KEY (id), KEY idx_to (to_user, id))' . $o);
    $pdo->exec('CREATE TABLE IF NOT EXISTS arena_chat (id INT UNSIGNED NOT NULL AUTO_INCREMENT, room VARCHAR(40) NOT NULL, user_id VARCHAR(24) NOT NULL,
        name VARCHAR(24) NOT NULL, text VARCHAR(140) NOT NULL, ts BIGINT NOT NULL, PRIMARY KEY (id), KEY idx_room (room, id))' . $o);
}

$room = substr(preg_replace('/[^A-Za-z0-9:_-]/', '', (string)($in['room'] ?? 'ffa')) ?: 'ffa', 0, 40);
$a = (string)($in['action'] ?? 'sync');
$now = (int)(microtime(true) * 1000);
$num = fn($v, $max) => (int)max(0, min($max, (int)$v));

if ($a === 'leave') {
    $pdo->prepare('DELETE FROM arena_state WHERE user_id = ?')->execute([$uid]);
    json_response(['success' => true]);
}

if ($a === 'chat') {
    $text = mb_substr(trim((string)($in['text'] ?? '')), 0, 140);
    if ($text === '') json_response(['success' => false, 'error' => 'Messaggio vuoto'], 400);
    
    $l = $pdo->prepare('SELECT MAX(ts) FROM arena_chat WHERE user_id = ?'); 
    $l->execute([$uid]);
    if ($now - (int)$l->fetchColumn() < 600) json_response(['success' => false, 'error' => 'Invio troppo rapido'], 429);
    
    $pdo->prepare('INSERT INTO arena_chat (room, user_id, name, text, ts) VALUES (?,?,?,?,?)')
        ->execute([$room, $uid, $cur['username'], $text, $now]);
    json_response(['success' => true]);
}

if ($a === 'eat') {
    $t = (string)($in['target'] ?? '');
    $c = $pdo->prepare('SELECT 1 FROM arena_state WHERE room = ? AND user_id = ?'); 
    $c->execute([$room, $t]);
    if (!$c->fetchColumn() || $t === $uid) json_response(['success' => false, 'error' => 'Bersaglio non valido'], 400);
    
    $pdo->prepare('INSERT INTO arena_events (room, to_user, killer, x, y, m, ts) VALUES (?,?,?,?,?,?,?)')
        ->execute([$room, $t, $uid, $num($in['x'] ?? 0, 3400), $num($in['y'] ?? 0, 3400), $num($in['m'] ?? 0, 100000), $now]);
    json_response(['success' => true]);
}

/* Sync Stato Giocatore */
$team = 0;
if (empty($in['spec'])) {
    $cells = [];
    foreach (array_slice((array)($in['cells'] ?? []), 0, 16) as $c) {
        if (!is_array($c)) continue;
        $cells[] = [$num($c['x'] ?? 0, 3400), $num($c['y'] ?? 0, 3400), $num($c['m'] ?? 0, 100000)];
    }
    $s = $pdo->prepare('SELECT team FROM arena_state WHERE room = ? AND user_id = ?'); 
    $s->execute([$room, $uid]);
    $t = $s->fetchColumn();
    if ($t === false) {
        $s = $pdo->prepare('SELECT COALESCE(SUM(team = 0),0), COALESCE(SUM(team = 1),0) FROM arena_state WHERE room = ? AND ts > ?');
        $s->execute([$room, $now - 8000]); 
        [$t0, $t1] = $s->fetch(PDO::FETCH_NUM);
        $team = ((int)$t0 <= (int)$t1) ? 0 : 1;
    } else { $team = (int)$t; }

    $color = preg_match('/^#[0-9a-fA-F]{6}$/', (string)($in['color'] ?? '')) ? $in['color'] : '#00f0ff';
    $skin = mb_substr((string)($in['skin'] ?? ''), 0, 2) ?: null;

    $pdo->prepare('INSERT INTO arena_state (room, user_id, name, team, color, skin, cells, ts) VALUES (?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE name = VALUES(name), color = VALUES(color), skin = VALUES(skin), cells = VALUES(cells), ts = VALUES(ts)')
        ->execute([$room, $uid, mb_substr($cur['username'], 0, 24), $team, $color, $skin, json_encode($cells), $now]);
}

$pdo->prepare('DELETE FROM arena_state WHERE ts < ?')->execute([$now - 8000]);
$pdo->prepare('DELETE FROM arena_events WHERE ts < ?')->execute([$now - 60000]);

$img = function (string $id): ?string {
    foreach (['png', 'jpg', 'webp', 'gif'] as $e) {
        $f = __DIR__ . '/../uploads/skins/' . $id . '.' . $e;
        if (is_file($f)) return 'uploads/skins/' . $id . '.' . $e . '?v=' . filemtime($f);
    }
    return null;
};

$st = $pdo->prepare('SELECT user_id, name, team, color, skin, cells FROM arena_state WHERE room = ? AND user_id <> ? AND ts > ?');
$st->execute([$room, $uid, $now - 8000]);
$others = [];
foreach ($st->fetchAll() as $r) {
    $others[] = ['id' => $r['user_id'], 'name' => $r['name'], 'team' => (int)$r['team'], 'color' => $r['color'],
        'skin' => $r['skin'], 'img' => $img($r['user_id']), 'cells' => json_decode($r['cells'], true) ?: []];
}

$since = (int)($in['since_event'] ?? -1);
if ($since < 0) { 
    $m = $pdo->prepare('SELECT COALESCE(MAX(id),0) FROM arena_events'); $m->execute(); $since = (int)$m->fetchColumn(); $events = []; 
} else { 
    $e = $pdo->prepare('SELECT id, killer, x, y, m FROM arena_events WHERE to_user = ? AND room = ? AND id > ? ORDER BY id LIMIT 20'); 
    $e->execute([$uid, $room, $since]); 
    $events = $e->fetchAll(); 
}
foreach ($events as $ev) $since = max($since, (int)$ev['id']);

$sc = (int)($in['since_chat'] ?? -1);
if ($sc < 0) { 
    $c = $pdo->prepare('SELECT id, name, text FROM (SELECT id, name, text FROM arena_chat WHERE room = ? ORDER BY id DESC LIMIT 10) t ORDER BY id'); 
    $c->execute([$room]); 
} else { 
    $c = $pdo->prepare('SELECT id, name, text FROM arena_chat WHERE room = ? AND id > ? ORDER BY id LIMIT 30'); 
    $c->execute([$room, $sc]); 
}
$chat = $c->fetchAll();
$lastChat = $sc;
foreach ($chat as $m) $lastChat = max($lastChat, (int)$m['id']);

json_response([
    'success' => true, 
    'players' => $others, 
    'my_team' => $team, 
    'my_img' => $img($uid),
    'events' => $events, 
    'last_event' => $since, 
    'chat' => $chat, 
    'last_chat' => $lastChat
]);