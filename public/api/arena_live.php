<?php
/**
 * ZeroArcade — ZeroAgar Classic LIVE Arena
 * Multiplayer reale sulla stessa arena + chat della stanza globale.
 * Trasporto: HTTP polling rapido, sessione PHP + MySQL come autorità di presenza/stato.
 *
 * POST JSON:
 *   action=join   -> entra nell'arena globale
 *   action=sync   -> giocatori + chat
 *   action=update -> aggiorna posizione/celle
 *   action=chat   -> invia messaggio
 *   action=leave  -> esce dall'arena
 */
declare(strict_types=1);
require_once __DIR__ . '/db.php';

$user = require_login_api();
$pdo = db();
$ARENA = 'zeroarcade:global';
// Unifica tutte le partite live nella stessa arena globale.
// Migrazione trasparente dei record creati dalla precedente Classic-only arena.
try {
    $pdo->exec("UPDATE zeroagar_arena_players SET arena_key='zeroarcade:global' WHERE arena_key='zeroagar_classic:global'");
} catch (Throwable $e) {}

$input = ($_SERVER['REQUEST_METHOD'] ?? '') === 'GET' ? $_GET : read_json_post();
$action = (string)($input['action'] ?? 'sync');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') enforce_same_origin();

/* Tabelle live: create-if-not-exists per installazioni già in uso, senza toccare i dati esistenti. */
$pdo->exec("CREATE TABLE IF NOT EXISTS zeroagar_arena_players (
    user_id VARCHAR(24) NOT NULL PRIMARY KEY,
    arena_key VARCHAR(48) NOT NULL DEFAULT 'zeroarcade:global',
    username VARCHAR(30) NOT NULL,
    color VARCHAR(32) NOT NULL DEFAULT '#1db7ef',
    team TINYINT NULL,
    cells_json MEDIUMTEXT NOT NULL,
    score INT NOT NULL DEFAULT 0,
    alive TINYINT(1) NOT NULL DEFAULT 1,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_arena_seen (arena_key, updated_at),
    KEY idx_arena_alive (arena_key, alive)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS zeroagar_arena_chat (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    arena_key VARCHAR(48) NOT NULL,
    user_id VARCHAR(24) NOT NULL,
    username VARCHAR(30) NOT NULL,
    text VARCHAR(200) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_arena_chat (arena_key, id),
    KEY idx_chat_user (user_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

function clean_cells($raw): array {
    $cells = is_array($raw) ? $raw : [];
    $out = [];
    foreach ($cells as $c) {
        if (!is_array($c)) continue;
        $x = (float)($c['x'] ?? 0);
        $y = (float)($c['y'] ?? 0);
        $m = (float)($c['m'] ?? 0);
        if (!is_finite($x) || !is_finite($y) || !is_finite($m) || $m < 1) continue;
        $out[] = [
            'x' => max(0, min(6000, $x)),
            'y' => max(0, min(6000, $y)),
            'm' => max(1, min(2000000, $m)),
            'r' => max(5, min(1600, sqrt(max(10, $m)))),
            'color' => substr((string)($c['color'] ?? '#1db7ef'), 0, 32),
            'team' => isset($c['team']) ? (int)$c['team'] : null,
        ];
        if (count($out) >= 16) break;
    }
    return $out;
}

function fetch_players(PDO $pdo, string $arena, string $me): array {
    $st = $pdo->prepare("SELECT user_id, username, color, team, cells_json, score, alive, updated_at
        FROM zeroagar_arena_players
        WHERE arena_key = ? AND updated_at >= ?
        ORDER BY score DESC LIMIT 80");
    $st->execute([$arena, date('Y-m-d H:i:s', time() - 8)]);
    $out = [];
    foreach ($st->fetchAll() as $r) {
        $cells = json_decode((string)$r['cells_json'], true);
        if (!is_array($cells)) $cells = [];
        $out[] = [
            'id' => (string)$r['user_id'],
            'username' => (string)$r['username'],
            'color' => (string)$r['color'],
            'team' => $r['team'] === null ? null : (int)$r['team'],
            'cells' => $cells,
            'score' => (int)$r['score'],
            'alive' => (bool)$r['alive'],
            'self' => (string)$r['user_id'] === $me,
        ];
    }
    return $out;
}

function clean_old(PDO $pdo, string $arena): void {
    $pdo->prepare('DELETE FROM zeroagar_arena_players WHERE arena_key = ? AND updated_at < ?')->execute([$arena, date('Y-m-d H:i:s', time() - 12)]);
    $pdo->prepare('DELETE FROM zeroagar_arena_chat WHERE arena_key = ? AND id < (SELECT x.m FROM (SELECT COALESCE(MAX(id),0) - 1000 AS m FROM zeroagar_arena_chat WHERE arena_key = ?) x)')->execute([$arena, $arena]);
}

function push_chat(PDO $pdo, string $arena, array $user, string $text): void {
    $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? '';
    $text = mb_substr($text, 0, 200);
    if ($text === '') json_response(['success' => false, 'error' => 'Messaggio vuoto.'], 400);
    $last = $pdo->prepare('SELECT created_at FROM zeroagar_arena_chat WHERE arena_key = ? AND user_id = ? ORDER BY id DESC LIMIT 1');
    $last->execute([$arena, $user['id']]);
    $lt = $last->fetchColumn();
    if ($lt && (time() - strtotime((string)$lt)) < 1) json_response(['success' => false, 'error' => 'Attendi un secondo tra i messaggi.'], 429);
    $pdo->prepare('INSERT INTO zeroagar_arena_chat (arena_key, user_id, username, text) VALUES (?,?,?,?)')->execute([$arena, $user['id'], $user['username'], $text]);
}

if ($action === 'join') {
    clean_old($pdo, $ARENA);
    $color = substr((string)($input['color'] ?? ($user['profile']['skin_color'] ?? '#1db7ef')), 0, 32);
    $team = isset($input['team']) && $input['team'] !== '' ? max(0, min(2, (int)$input['team'])) : null;
    $cells = clean_cells($input['cells'] ?? []);
    $score = max(0, min(2000000, (int)($input['score'] ?? 25)));
    if (!$cells) $cells = [['x'=>3000,'y'=>3000,'m'=>25,'r'=>5,'color'=>$color,'team'=>$team]];
    $pdo->prepare("INSERT INTO zeroagar_arena_players (user_id, arena_key, username, color, team, cells_json, score, alive, updated_at)
        VALUES (?,?,?,?,?,?,?,1,NOW())
        ON DUPLICATE KEY UPDATE arena_key=VALUES(arena_key), username=VALUES(username), color=VALUES(color), team=VALUES(team), cells_json=VALUES(cells_json), score=VALUES(score), alive=1, updated_at=NOW()")->execute([
        $user['id'], $ARENA, $user['username'], $color, $team, json_encode($cells, JSON_UNESCAPED_UNICODE), $score
    ]);
    touch_presence($user['id']);
    json_response(['success'=>true,'arena'=>$ARENA,'players'=>fetch_players($pdo,$ARENA,$user['id'])]);
}

if ($action === 'leave') {
    $pdo->prepare('DELETE FROM zeroagar_arena_players WHERE user_id = ? AND arena_key = ?')->execute([$user['id'], $ARENA]);
    json_response(['success'=>true]);
}

if ($action === 'chat') {
    push_chat($pdo, $ARENA, $user, (string)($input['text'] ?? ''));
    json_response(['success'=>true]);
}

if ($action === 'update') {
    $st = $pdo->prepare('SELECT alive FROM zeroagar_arena_players WHERE user_id = ? AND arena_key = ? LIMIT 1');
    $st->execute([$user['id'], $ARENA]);
    $alive = $st->fetchColumn();
    if ($alive === false) json_response(['success'=>false,'error'=>'Non sei nell\'arena.','need_join'=>true], 409);
    if (!(bool)$alive) json_response(['success'=>true,'dead'=>true]);

    $cells = clean_cells($input['cells'] ?? []);
    $score = max(0, min(2000000, (int)($input['score'] ?? 0)));
    $color = substr((string)($input['color'] ?? '#1db7ef'), 0, 32);
    $team = isset($input['team']) && $input['team'] !== '' ? max(0, min(2, (int)$input['team'])) : null;
    $pdo->beginTransaction();
    try {
        $stMine = $pdo->prepare('SELECT score FROM zeroagar_arena_players WHERE user_id=? AND arena_key=? FOR UPDATE');
        $stMine->execute([$user['id'], $ARENA]);
        $serverScore = (int)($stMine->fetchColumn() ?: $score);
        $serverScore = max($serverScore, $score);

        $pdo->prepare('UPDATE zeroagar_arena_players SET username=?, color=?, team=?, cells_json=?, score=?, alive=1, updated_at=NOW() WHERE user_id=? AND arena_key=?')
            ->execute([$user['username'], $color, $team, json_encode($cells, JSON_UNESCAPED_UNICODE), $serverScore, $user['id'], $ARENA]);

        // Collisione multiplayer basilare: il server decide quando un giocatore piu grande divora uno piu piccolo.
        $others = $pdo->prepare('SELECT user_id, team, cells_json, score FROM zeroagar_arena_players WHERE arena_key=? AND user_id<>? AND alive=1 AND updated_at>=? FOR UPDATE');
        $others->execute([$ARENA, $user['id'], date('Y-m-d H:i:s', time()-8)]);
        foreach ($others->fetchAll() as $o) {
            $otherCells = json_decode((string)$o['cells_json'], true);
            if (!is_array($otherCells)) continue;
            $otherDead = false;
            foreach ($cells as $mc) {
                foreach ($otherCells as $idx => $oc) {
                    if ($team !== null && $o['team'] !== null && (int)$team === (int)$o['team']) continue;
                    $dx=(float)$mc['x']-(float)($oc['x']??0); $dy=(float)$mc['y']-(float)($oc['y']??0);
                    $d=sqrt($dx*$dx+$dy*$dy);
                    $mr=(float)($mc['r']??sqrt(max(10,(float)$mc['m'])));
                    $or=(float)($oc['r']??sqrt(max(10,(float)$oc['m']??10)));
                    $mm=(float)($mc['m']??0); $om=(float)($oc['m']??0);
                    if ($d < max(1,$mr*.92) && $mm > $om*1.10 && $mr > $or*1.05) {
                        $serverScore += (int)round($om);
                        $otherDead = true;
                        break 2;
                    }
                }
            }
            if ($otherDead) {
                $pdo->prepare("UPDATE zeroagar_arena_players SET alive=0, cells_json='[]', score=0, updated_at=NOW() WHERE user_id=? AND arena_key=?")
                    ->execute([$o['user_id'],$ARENA]);
            }
        }
        $pdo->prepare('UPDATE zeroagar_arena_players SET score=? WHERE user_id=? AND arena_key=?')->execute([$serverScore,$user['id'],$ARENA]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
    touch_presence($user['id']);
    clean_old($pdo, $ARENA);
    json_response(['success'=>true,'server_score'=>$serverScore,'players'=>fetch_players($pdo,$ARENA,$user['id'])]);
}

if ($action === 'sync') {
    clean_old($pdo, $ARENA);
    touch_presence($user['id']);
    $since = max(0, (int)($input['since'] ?? ($_GET['since'] ?? 0)));
    if ($since > 0) {
        $st = $pdo->prepare('SELECT id, username, text, created_at FROM zeroagar_arena_chat WHERE arena_key=? AND id>? ORDER BY id ASC LIMIT 100');
        $st->execute([$ARENA, $since]);
    } else {
        $st = $pdo->prepare('SELECT id, username, text, created_at FROM zeroagar_arena_chat WHERE arena_key=? ORDER BY id DESC LIMIT 60');
        $st->execute([$ARENA]);
        $rows = array_reverse($st->fetchAll());
        $messages = [];
        foreach ($rows as $r) $messages[] = ['id'=>(int)$r['id'],'username'=>$r['username'],'text'=>$r['text'],'created_at'=>date('c', strtotime((string)$r['created_at']))];
        json_response(['success'=>true,'players'=>fetch_players($pdo,$ARENA,$user['id']),'messages'=>$messages,'last_chat'=>$rows ? (int)$rows[count($rows)-1]['id'] : 0,'online_count'=>count(fetch_players($pdo,$ARENA,$user['id']))]);
    }
    $messages=[]; foreach($st->fetchAll() as $r) $messages[]=['id'=>(int)$r['id'],'username'=>$r['username'],'text'=>$r['text'],'created_at'=>date('c',strtotime((string)$r['created_at']))];
    $lastChat=$messages ? (int)$messages[count($messages)-1]['id'] : $since;
    json_response(['success'=>true,'players'=>fetch_players($pdo,$ARENA,$user['id']),'messages'=>$messages,'last_chat'=>$lastChat,'online_count'=>count(fetch_players($pdo,$ARENA,$user['id']))]);
}

json_response(['success'=>false,'error'=>'Azione arena non valida.'], 400);
