<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
$u=require_login_api();
enforce_same_origin();
$in=read_json_post();
$action=(string)($_GET['action']??($in['action']??''));
$game=(string)($in['game']??$_GET['game']??'');
$games=['neon_snake','zero_breakout','pixel_invaders','cyber_pong','blob_sumo','orbit_miner','laser_maze','stack_tower','zero_tetrix','beat_orbit'];
if(!in_array($game,$games,true)) json_response(['success'=>false,'error'=>'Gioco non valido.'],400);
$pdo=db();
if($action==='leaderboard'){
  $st=$pdo->prepare('SELECT s.user_id,u.username,s.score FROM arcade_scores s JOIN users u ON u.id=s.user_id WHERE s.game=? AND u.is_banned=0 ORDER BY s.score DESC,s.updated_at ASC LIMIT 10');
  $st->execute([$game]); $rows=$st->fetchAll(); $top=[];$i=1; foreach($rows as $r)$top[]=['rank'=>$i++,'username'=>$r['username'],'score'=>(int)$r['score']];
  $me=$pdo->prepare('SELECT score FROM arcade_scores WHERE game=? AND user_id=? LIMIT 1');$me->execute([$game,$u['id']]);$mine=(int)($me->fetchColumn()?:0);
  json_response(['success'=>true,'top'=>$top,'mine'=>$mine]);
}
if($action==='submit'){
  $score=max(0,min(1000000000,(int)($in['score']??0)));
  $stats=is_array($in['stats']??null)?$in['stats']:[];
  $pdo->beginTransaction();
  try{
    $st=$pdo->prepare('SELECT score FROM arcade_scores WHERE game=? AND user_id=? FOR UPDATE');$st->execute([$game,$u['id']]);$old=$st->fetchColumn();$best=max((int)($old?:0),$score);
    if($old===false)$pdo->prepare('INSERT INTO arcade_scores(game,user_id,score,updated_at) VALUES(?,?,?,NOW())')->execute([$game,$u['id'],$best]);
    elseif($best!=(int)$old)$pdo->prepare('UPDATE arcade_scores SET score=?,updated_at=NOW() WHERE game=? AND user_id=?')->execute([$best,$game,$u['id']]);
    // lightweight game progress aggregation stored in game_progress when available
    try{
      $gp=$pdo->prepare('INSERT INTO game_progress(game,user_id,best_mass,games_played,total_kills,total_particles,total_time,updated_at) VALUES(?,?,?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE games_played=games_played+1,updated_at=NOW()');
      $gp->execute([$game,$u['id'],$best,1,(int)($stats['kills']??0),(int)($stats['particles']??0),(int)($stats['seconds']??$stats['duration']??0)]);
    }catch(Throwable $e){}
    $pdo->commit(); json_response(['success'=>true,'best'=>$best,'score'=>$score]);
  }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();json_response(['success'=>false,'error'=>'Salvataggio punteggio non riuscito.'],500);}
}
json_response(['success'=>false,'error'=>'Azione non valida.'],400);
