<?php
/**
 * api/rooms.php - Stanze multiplayer a turni (Tris, Forza 4).
 * Il server è l'unico arbitro: valida turno, mossa, vittoria e assegna i premi.
 *
 * Azioni (JSON): list | create | join | state | move | leave
 */
require_once __DIR__ . '/db.php';

$cur = require_login_api();
$in = read_json_post();
$action = (string)($in['action'] ?? ($_GET['action'] ?? 'list'));
$pdo = db();
$me = $cur['id'];
$now = time();

if (!in_array($action, ['list', 'state'], true)) {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') json_response(['success' => false, 'error' => 'Metodo non consentito'], 405);
    enforce_same_origin();
}

// ------------------------------------------------------------------ Logica dei giochi
function room_initial_state(string $game): array {
    return ['board' => array_fill(0, $game === 'tris' ? 9 : 42, 0), 'moves' => 0];
}

function tris_winner(array $b): int {
    foreach ([[0,1,2],[3,4,5],[6,7,8],[0,3,6],[1,4,7],[2,5,8],[0,4,8],[2,4,6]] as $l) {
        if ($b[$l[0]] !== 0 && $b[$l[0]] === $b[$l[1]] && $b[$l[1]] === $b[$l[2]]) return $b[$l[0]];
    }
    return 0;
}

function forza4_winner(array $b): int {
    $dirs = [[0, 1], [1, 0], [1, 1], [1, -1]];
    for ($r = 0; $r < 6; $r++) {
        for ($c = 0; $c < 7; $c++) {
            $p = $b[$r * 7 + $c];
            if ($p === 0) continue;
            foreach ($dirs as $d) {
                $ok = true;
                for ($k = 1; $k < 4; $k++) {
                    $rr = $r + $d[0] * $k;
                    $cc = $c + $d[1] * $k;
                    if ($rr < 0 || $rr > 5 || $cc < 0 || $cc > 6 || $b[$rr * 7 + $cc] !== $p) { $ok = false; break; }
                }
                if ($ok) return $p;
            }
        }
    }
    return 0;
}

/** Applica la mossa. Ritorna una stringa d'errore oppure null se valida. */
function room_apply(string $game, array &$state, int $player, $move): ?string {
    if (!is_int($move) && !(is_string($move) && ctype_digit($move))) return 'Mossa non valida.';
    $move = (int)$move;
    $b = $state['board'];
    if ($game === 'tris') {
        if ($move < 0 || $move > 8) return 'Casella non valida.';
        if ($b[$move] !== 0) return 'Casella già occupata.';
        $b[$move] = $player;
    } else {
        if ($move < 0 || $move > 6) return 'Colonna non valida.';
        $placed = false;
        for ($r = 5; $r >= 0; $r--) {
            if ($b[$r * 7 + $move] === 0) { $b[$r * 7 + $move] = $player; $placed = true; break; }
        }
        if (!$placed) return 'Colonna piena.';
    }
    $state['board'] = $b;
    $state['moves'] = (int)$state['moves'] + 1;
    return null;
}

/** 'win' | 'draw' | null */
function room_outcome(string $game, array $state, int $player): ?string {
    $w = $game === 'tris' ? tris_winner($state['board']) : forza4_winner($state['board']);
    if ($w === $player) return 'win';
    return in_array(0, $state['board'], true) ? null : 'draw';
}

// ------------------------------------------------------------------ Utilità stanze
function room_cleanup(): void {
    $pdo = db();
    $now = time();
    $pdo->prepare("UPDATE game_rooms SET status = 'closed', result = 'expired', version = version + 1, updated_at = ? WHERE status = 'waiting' AND updated_at < ?")
        ->execute([$now, $now - 900]);
    $pdo->prepare("UPDATE game_rooms SET status = 'finished', result = 'abandoned', version = version + 1, updated_at = ? WHERE status = 'playing' AND updated_at < ?")
        ->execute([$now, $now - 600]);
    if (mt_rand(1, 20) === 1) {
        $pdo->prepare("DELETE FROM game_rooms WHERE status IN ('finished','closed') AND updated_at < ?")->execute([$now - 3600]);
    }
}

function active_room(string $uid): ?array {
    $st = db()->prepare("SELECT * FROM game_rooms WHERE (p1 = ? OR p2 = ?) AND status IN ('waiting','playing') ORDER BY id DESC LIMIT 1");
    $st->execute([$uid, $uid]);
    $r = $st->fetch();
    return $r ?: null;
}

function room_view(array $r, string $me): array {
    $ids = array_values(array_filter([$r['p1'], $r['p2']]));
    $names = [];
    if ($ids) {
        $st = db()->prepare('SELECT id, username FROM users WHERE id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')');
        $st->execute($ids);
        foreach ($st->fetchAll() as $u) $names[$u['id']] = $u['username'];
    }
    $state = json_decode((string)$r['state'], true) ?: ['board' => [], 'moves' => 0];
    $you = $r['p1'] === $me ? 1 : ($r['p2'] === $me ? 2 : 0);
    $winner = 0;
    if ($r['winner']) $winner = ($r['winner'] === $r['p1']) ? 1 : 2;
    return [
        'id'      => (int)$r['id'],
        'game'    => $r['game'],
        'status'  => $r['status'],
        'board'   => $state['board'],
        'moves'   => (int)$state['moves'],
        'turn'    => (int)$r['turn'],
        'you'     => $you,
        'p1'      => $names[$r['p1']] ?? '?',
        'p2'      => $r['p2'] ? ($names[$r['p2']] ?? '?') : null,
        'winner'  => $winner,
        'result'  => $r['result'],
        'version' => (int)$r['version'],
    ];
}

/** Premi a fine partita (solo se sono state giocate almeno 5 mosse: niente farming con partite finte). */
function room_award(array $r, int $moves, string $result, ?string $winnerId): void {
    if ($moves < 5 || $result === 'abandoned') return;
    $pdo = db();
    if ($result === 'draw') {
        add_rewards($r['p1'], 25, 20);
        add_rewards($r['p2'], 25, 20);
        return;
    }
    $loser = ($winnerId === $r['p1']) ? $r['p2'] : $r['p1'];
    add_rewards($winnerId, 60, 40);
    add_rewards($loser, 10, 10);
    $pdo->prepare('INSERT INTO arcade_scores (game, user_id, score, updated_at) VALUES (?,?,1,?)
        ON DUPLICATE KEY UPDATE score = score + 1, updated_at = VALUES(updated_at)')
        ->execute([$r['game'], $winnerId, date('Y-m-d H:i:s')]);
}

function room_lock(int $id): ?array {
    $st = db()->prepare('SELECT * FROM game_rooms WHERE id = ? FOR UPDATE');
    $st->execute([$id]);
    $r = $st->fetch();
    return $r ?: null;
}

try {
    room_cleanup();
    $game = (string)($in['game'] ?? ($_GET['game'] ?? ''));
    if ($game !== '' && !in_array($game, room_games(), true)) {
        json_response(['success' => false, 'error' => 'Gioco non valido.'], 400);
    }

    // -------------------------------------------------------------- LIST
    if ($action === 'list') {
        touch_presence($me);
        $sql = "SELECT r.id, r.game, r.created_at, u.username AS owner FROM game_rooms r JOIN users u ON u.id = r.p1
                WHERE r.status = 'waiting' AND r.p1 <> ? AND u.last_seen >= ?" . ($game !== '' ? ' AND r.game = ?' : '') . ' ORDER BY r.id DESC LIMIT 20';
        $st = $pdo->prepare($sql);
        $params = [$me, date('Y-m-d H:i:s', time() - 40)];   // solo stanze il cui creatore è ancora connesso
        if ($game !== '') $params[] = $game;
        $st->execute($params);
        $mine = active_room($me);
        json_response([
            'success' => true,
            'open'    => array_map(function ($r) { return ['id' => (int)$r['id'], 'game' => $r['game'], 'owner' => $r['owner'], 'age' => time() - (int)$r['created_at']]; }, $st->fetchAll()),
            'mine'    => $mine ? ['id' => (int)$mine['id'], 'game' => $mine['game'], 'status' => $mine['status']] : null,
        ]);
    }

    // -------------------------------------------------------------- CREATE
    if ($action === 'create') {
        if ($game === '') json_response(['success' => false, 'error' => 'Scegli un gioco.'], 400);
        $mine = active_room($me);
        if ($mine) {
            json_response(['success' => false, 'error' => 'Hai già una partita in corso.', 'room_id' => (int)$mine['id'], 'game' => $mine['game']], 409);
        }
        $pdo->prepare('INSERT INTO game_rooms (game, status, p1, turn, state, version, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?)')
            ->execute([$game, 'waiting', $me, 1, json_encode(room_initial_state($game)), 1, $now, $now]);
        json_response(['success' => true, 'room_id' => (int)$pdo->lastInsertId()]);
    }

    $roomId = (int)($in['room_id'] ?? ($_GET['room_id'] ?? 0));
    if ($roomId <= 0) json_response(['success' => false, 'error' => 'Stanza non specificata.'], 400);

    // -------------------------------------------------------------- JOIN
    if ($action === 'join') {
        $pdo->beginTransaction();
        $r = room_lock($roomId);
        if (!$r || $r['status'] !== 'waiting' || $r['p2'] !== null) {
            $pdo->rollBack();
            json_response(['success' => false, 'error' => 'La stanza non è più disponibile.'], 409);
        }
        if ($r['p1'] === $me) {
            $pdo->rollBack();
            json_response(['success' => false, 'error' => 'Non puoi sfidare te stesso.'], 400);
        }
        $mine = active_room($me);
        if ($mine) {
            $pdo->rollBack();
            json_response(['success' => false, 'error' => 'Hai già una partita in corso.', 'room_id' => (int)$mine['id'], 'game' => $mine['game']], 409);
        }
        $pdo->prepare("UPDATE game_rooms SET p2 = ?, status = 'playing', turn = 1, version = version + 1, updated_at = ? WHERE id = ?")
            ->execute([$me, $now, $roomId]);
        $pdo->commit();
        json_response(['success' => true, 'room_id' => $roomId]);
    }

    // -------------------------------------------------------------- STATE (polling)
    if ($action === 'state') {
        $st = $pdo->prepare('SELECT * FROM game_rooms WHERE id = ?');
        $st->execute([$roomId]);
        $r = $st->fetch();
        if (!$r || ($r['p1'] !== $me && $r['p2'] !== $me)) {
            json_response(['success' => false, 'error' => 'Stanza non trovata.'], 404);
        }
        touch_presence($me);
        $since = (int)($_GET['since'] ?? ($in['since'] ?? 0));
        if ($since > 0 && $since === (int)$r['version']) {
            json_response(['success' => true, 'changed' => false, 'version' => (int)$r['version']]);
        }
        json_response(['success' => true, 'changed' => true, 'room' => room_view($r, $me)]);
    }

    // -------------------------------------------------------------- MOVE
    if ($action === 'move') {
        $pdo->beginTransaction();
        $r = room_lock($roomId);
        if (!$r || ($r['p1'] !== $me && $r['p2'] !== $me)) {
            $pdo->rollBack();
            json_response(['success' => false, 'error' => 'Stanza non trovata.'], 404);
        }
        if ($r['status'] !== 'playing') {
            $pdo->rollBack();
            json_response(['success' => false, 'error' => 'La partita non è in corso.'], 409);
        }
        $you = $r['p1'] === $me ? 1 : 2;
        if ((int)$r['turn'] !== $you) {
            $pdo->rollBack();
            json_response(['success' => false, 'error' => 'Non è il tuo turno.'], 409);
        }
        $state = json_decode((string)$r['state'], true);
        $err = room_apply($r['game'], $state, $you, $in['move'] ?? null);
        if ($err !== null) {
            $pdo->rollBack();
            json_response(['success' => false, 'error' => $err], 400);
        }
        $out = room_outcome($r['game'], $state, $you);
        if ($out === null) {
            $pdo->prepare('UPDATE game_rooms SET state = ?, turn = ?, version = version + 1, updated_at = ? WHERE id = ?')
                ->execute([json_encode($state), $you === 1 ? 2 : 1, $now, $roomId]);
        } else {
            $winnerId = ($out === 'win') ? $me : null;
            $pdo->prepare("UPDATE game_rooms SET state = ?, status = 'finished', winner = ?, result = ?, version = version + 1, updated_at = ? WHERE id = ?")
                ->execute([json_encode($state), $winnerId, $out, $now, $roomId]);
            room_award($r, (int)$state['moves'], $out, $winnerId);
        }
        $pdo->commit();
        $st = $pdo->prepare('SELECT * FROM game_rooms WHERE id = ?');
        $st->execute([$roomId]);
        json_response(['success' => true, 'room' => room_view($st->fetch(), $me)]);
    }

    // -------------------------------------------------------------- LEAVE (chiude la stanza o si arrende)
    if ($action === 'leave') {
        $pdo->beginTransaction();
        $r = room_lock($roomId);
        if (!$r || ($r['p1'] !== $me && $r['p2'] !== $me)) {
            $pdo->rollBack();
            json_response(['success' => false, 'error' => 'Stanza non trovata.'], 404);
        }
        if ($r['status'] === 'waiting') {
            $pdo->prepare("UPDATE game_rooms SET status = 'closed', result = 'closed', version = version + 1, updated_at = ? WHERE id = ?")->execute([$now, $roomId]);
        } elseif ($r['status'] === 'playing') {
            $winnerId = ($r['p1'] === $me) ? $r['p2'] : $r['p1'];
            $state = json_decode((string)$r['state'], true);
            $pdo->prepare("UPDATE game_rooms SET status = 'finished', winner = ?, result = 'resign', version = version + 1, updated_at = ? WHERE id = ?")
                ->execute([$winnerId, $now, $roomId]);
            room_award($r, (int)($state['moves'] ?? 0), 'resign', $winnerId);
        }
        $pdo->commit();
        json_response(['success' => true]);
    }

    json_response(['success' => false, 'error' => 'Azione non valida.'], 400);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('rooms: ' . $e->getMessage());
    json_response(['success' => false, 'error' => 'Errore del server.'], 500);
}
