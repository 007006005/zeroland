<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
$action = (string)($_GET['action'] ?? '');
$in = read_json_post();
if ($action === '' && isset($in['action'])) $action = (string)$in['action'];
if ($action === 'me') {
    $u = current_user();
    json_response(['ok' => true, 'profile' => $u ? [
        'id' => $u['id'], 'name' => $u['username'], 'username' => $u['username'], 'loggedIn' => true,
        'provider' => 'zeroarcade', 'role' => $u['role'], 'coins' => (int)$u['coins'], 'xp' => (int)$u['xp'],
        'level' => (int)$u['level'], 'clan' => $u['clan'],
    ] : null]);
}
$u = require_login_api();
$pdo = db();
$game = (string)($in['game'] ?? $_GET['game'] ?? 'zeroagar_classic');
if ($action === 'game_leaderboard') {
    $limit = max(1, min(20, (int)($in['limit'] ?? $_GET['limit'] ?? 10)));
    $st = $pdo->prepare('SELECT s.score, u.username, u.role FROM arcade_scores s JOIN users u ON u.id=s.user_id WHERE s.game=? ORDER BY s.score DESC LIMIT ' . $limit);
    $st->execute([$game]);
    json_response(['ok'=>true,'leaders'=>$st->fetchAll()]);
}

if ($action === 'game_progress') {
    $st = $pdo->prepare('SELECT best_mass,games_played,total_time,total_kills,total_particles FROM game_progress WHERE game=? AND user_id=? LIMIT 1');
    $st->execute([$game,$u['id']]); $r = $st->fetch() ?: [];
    json_response(['ok'=>true,'progress'=>[
        'best'=>(int)($r['best_mass']??0),'plays'=>(int)($r['games_played']??0),'total'=>(int)($r['total_time']??0)
    ]]);
}
if ($action === 'game_save') {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') json_response(['ok'=>false,'error'=>'Metodo non consentito'],405);
    enforce_same_origin();
    $score=max(0,min(600000,(int)($in['score']??0)));
    $data=is_array($in['data']??null)?$in['data']:[];
    $best=max(0,min(600000,(int)($data['best']??$score)));
    $plays=max(1,(int)($data['plays']??1));
    $total=max(0,(int)($data['total']??0));
    $kills=max(0,min(2000000000,(int)($data['kills']??0)));
    $particles=max(0,min(2000000000,(int)($data['particles']??0)));
    $pdo->beginTransaction();
    try {
        $st=$pdo->prepare('INSERT INTO arcade_scores(game,user_id,score,updated_at) VALUES(?,?,?,NOW()) ON DUPLICATE KEY UPDATE score=GREATEST(score,VALUES(score)),updated_at=NOW()');
        $st->execute([$game,$u['id'],$score]);
        $st=$pdo->prepare('SELECT best_mass,games_played,total_time FROM game_progress WHERE game=? AND user_id=? LIMIT 1');
        $st->execute([$game,$u['id']]); $old=$st->fetch();
        if ($old) {
            $up=$pdo->prepare('UPDATE game_progress SET best_mass=?,games_played=?,total_kills=?,total_particles=?,total_time=?,last_mass=?,updated_at=NOW() WHERE game=? AND user_id=?');
            $up->execute([max((int)$old['best_mass'],$best,$score),max((int)$old['games_played'],$plays),max((int)($old['total_kills'] ?? 0),$kills),max((int)($old['total_particles'] ?? 0),$particles),max((int)$old['total_time'],$total),$score,$game,$u['id']]);
        } else {
            $ins=$pdo->prepare('INSERT INTO game_progress(game,user_id,best_mass,games_played,total_kills,total_particles,total_time,last_mass,updated_at) VALUES(?,?,?,?,?,?,?, ?,NOW())');
            $ins->execute([$game,$u['id'],$best,$plays,$kills,$particles,$total,$score]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        json_response(['ok'=>false,'error'=>'Salvataggio non riuscito'],500);
    }
    json_response(['ok'=>true]);
}
json_response(['ok'=>false,'error'=>'Azione non valida'],400);
