<?php
/**
 * api/agar_io.php - Integrazione Agar.io Classic con ZeroArcade/MySQL.
 * Stato profilo e statistiche sono legati all'account autenticato.
 */
require_once __DIR__ . '/db.php';

$cur = require_login_api();
$pdo = db();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$in = $method === 'POST' ? read_json_post() : $_GET;
$action = (string)($in['action'] ?? 'load');

enforce_same_origin();

function agar_default_state(array $u): array {
    return [
        'name' => $u['username'],
        'loggedIn' => true,
        'provider' => 'zeroarcade',
        'role' => $u['role'],
        'coins' => (int)$u['coins'],
        'xp' => (int)$u['xp'],
        'level' => (int)$u['level'],
        'clan' => $u['clan'],
        'bestMass' => 0,
        'runs' => 0,
        'totalParticles' => 0,
        'totalRivals' => 0,
        'totalTime' => 0,
    ];
}

function agar_progress(array $row): array {
    return [
        'bestMass' => (int)$row['best_mass'],
        'runs' => (int)$row['games_played'],
        'totalRivals' => (int)$row['total_kills'],
        'totalParticles' => (int)$row['total_particles'],
        'totalTime' => (int)$row['total_time'],
    ];
}

if ($action === 'load') {
    $state = agar_default_state($cur);
    $st = $pdo->prepare('SELECT state_json FROM game_profiles WHERE game = ? AND user_id = ? LIMIT 1');
    $st->execute(['agar_io', $cur['id']]);
    $row = $st->fetch();
    if ($row && $row['state_json']) {
        $decoded = json_decode($row['state_json'], true);
        if (is_array($decoded)) $state = array_replace_recursive($state, $decoded);
    }
    $st = $pdo->prepare('SELECT * FROM game_progress WHERE game = ? AND user_id = ? LIMIT 1');
    $st->execute(['agar_io', $cur['id']]);
    $p = $st->fetch();
    if ($p) $state = array_replace($state, agar_progress($p));
    $state['name'] = $cur['username'];
    $state['loggedIn'] = true;
    $state['role'] = $cur['role'];
    $state['coins'] = (int)$cur['coins'];
    $state['xp'] = (int)$cur['xp'];
    $state['level'] = (int)$cur['level'];
    $state['clan'] = $cur['clan'];
    json_response(['success'=>true,'state'=>$state,'progress'=>agar_progress($p ?: [
        'best_mass'=>0,'games_played'=>0,'total_kills'=>0,'total_particles'=>0,'total_time'=>0
    ])]);
}

if ($action === 'save') {
    if ($method !== 'POST') json_response(['success'=>false,'error'=>'Metodo non consentito.'],405);
    $state = $in['state'] ?? null;
    if (!is_array($state)) json_response(['success'=>false,'error'=>'Stato non valido.'],400);
    // Only allow a bounded JSON payload; the server remains authoritative for wallet/XP.
    $state['name'] = $cur['username'];
    $state['loggedIn'] = true;
    $state['role'] = $cur['role'];
    $state['coins'] = (int)$cur['coins'];
    $state['xp'] = (int)$cur['xp'];
    $state['level'] = (int)$cur['level'];
    $state['clan'] = $cur['clan'];
    $json = json_encode($state, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    if ($json === false || strlen($json) > 450000) json_response(['success'=>false,'error'=>'Profilo troppo grande.'],413);
    $st = $pdo->prepare('INSERT INTO game_profiles (game,user_id,state_json,updated_at) VALUES (?,?,?,NOW()) ON DUPLICATE KEY UPDATE state_json=VALUES(state_json),updated_at=NOW()');
    $st->execute(['agar_io',$cur['id'],$json]);
    json_response(['success'=>true]);
}

if ($action === 'submit') {
    if ($method !== 'POST') json_response(['success'=>false,'error'=>'Metodo non consentito.'],405);
    $score = max(0,min(600000,(int)($in['score'] ?? 0)));
    $kills = max(0,min(100,(int)($in['kills'] ?? 0)));
    $particles = max(0,min(5000,(int)($in['particles'] ?? 0)));
    $time = max(0,min(86400,(int)($in['time'] ?? 0)));
    $coins = max(0,min(150,(int)($in['coins'] ?? 0)));
    $xp = min(120, $kills * 8 + intdiv($particles,25));

    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare('INSERT INTO arcade_scores (game,user_id,score,updated_at) VALUES (?,?,?,NOW()) ON DUPLICATE KEY UPDATE score=GREATEST(score,VALUES(score)),updated_at=IF(VALUES(score)>score,NOW(),updated_at)');
        $st->execute(['agar_io',$cur['id'],$score]);
        $st = $pdo->prepare('INSERT INTO game_progress (game,user_id,best_mass,games_played,total_kills,total_particles,total_time,last_mass,updated_at) VALUES (?,?,?,?,?,?,?, ?,NOW()) ON DUPLICATE KEY UPDATE best_mass=GREATEST(best_mass,VALUES(best_mass)),games_played=games_played+1,total_kills=total_kills+VALUES(total_kills),total_particles=total_particles+VALUES(total_particles),total_time=total_time+VALUES(total_time),last_mass=VALUES(last_mass),updated_at=NOW()');
        $st->execute(['agar_io',$cur['id'],$score,1,$kills,$particles,$time,$score]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('agar_io submit: '.$e->getMessage());
        json_response(['success'=>false,'error'=>'Salvataggio partita non riuscito.'],500);
    }
    if ($coins > 0 || $xp > 0) add_rewards($cur['id'],$coins,$xp);
    $u = current_user(true);
    $st = $pdo->prepare('SELECT score FROM arcade_scores WHERE game=? AND user_id=?');
    $st->execute(['agar_io',$cur['id']]);
    json_response(['success'=>true,'coins_earned'=>$coins,'earned_xp'=>$xp,'total_coins'=>(int)$u['coins'],'level'=>(int)$u['level'],'new_high'=>(int)$st->fetchColumn()]);
}

json_response(['success'=>false,'error'=>'Azione non valida.'],400);
