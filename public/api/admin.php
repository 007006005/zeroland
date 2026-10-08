<?php
/**
 * ZeroArcade - pannello amministrazione completo.
 * Tutte le modifiche sono protette dalla sessione PHP e dal grado dello staff.
 */
require_once __DIR__ . '/db.php';

$cur = require_staff_api('moderatore');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') json_response(['success'=>false,'error'=>'Metodo non consentito'],405);
enforce_same_origin();

$in = read_json_post();
$action = (string)($in['action'] ?? '');
$pdo = db();
$myRank = role_rank($cur['role']);

// Tabelle di configurazione create anche su installazioni già esistenti.
$pdo->exec("CREATE TABLE IF NOT EXISTS za_settings (
    setting_key VARCHAR(80) NOT NULL PRIMARY KEY,
    setting_value TEXT NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$pdo->exec("CREATE TABLE IF NOT EXISTS za_game_flags (
    game VARCHAR(40) NOT NULL PRIMARY KEY,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    maintenance TINYINT(1) NOT NULL DEFAULT 0,
    config_json TEXT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$need = function(string $role) use ($myRank): void {
    if ($myRank < role_rank($role)) json_response(['success'=>false,'error'=>"Azione riservata al ruolo $role o superiore."],403);
};
$target = function(bool $allowSelf=false) use ($pdo,$in,$cur,$myRank): array {
    $id=(string)($in['user_id'] ?? '');
    $st=$pdo->prepare('SELECT * FROM users WHERE id=? LIMIT 1');
    $st->execute([$id]);
    $t=$st->fetch();
    if(!$t) json_response(['success'=>false,'error'=>'Utente non trovato.'],404);
    if(!$allowSelf && (string)$t['id']===(string)$cur['id']) json_response(['success'=>false,'error'=>'Non puoi agire su te stesso.'],400);
    if((string)$t['id']!==(string)$cur['id'] && role_rank((string)$t['role']) >= $myRank) json_response(['success'=>false,'error'=>'Non puoi agire su un utente di grado pari o superiore al tuo.'],403);
    return $t;
};
$cleanRole = function(string $role): string { return in_array($role, ARCADE_ROLES, true) ? $role : 'user'; };

switch($action){
    case 'stats':
        $q=function(string $sql)use($pdo){return (int)$pdo->query($sql)->fetchColumn();};
        json_response(['success'=>true,'stats'=>[
            'users'=>$q('SELECT COUNT(*) FROM users'),
            'banned'=>$q('SELECT COUNT(*) FROM users WHERE is_banned=1'),
            'online'=>(int)$pdo->query("SELECT COUNT(*) FROM users WHERE last_seen >= '".date('Y-m-d H:i:s',time()-120)."'")->fetchColumn(),
            'rooms'=>$q("SELECT COUNT(*) FROM game_rooms WHERE status IN ('waiting','playing')"),
            'messages'=>$q('SELECT COUNT(*) FROM lobby_chat'),
            'scores'=>$q('SELECT COUNT(*) FROM arcade_scores'),
            'progress'=>$q('SELECT COUNT(*) FROM game_progress'),
            'staff'=>$q("SELECT COUNT(*) FROM users WHERE role IN ('helper','moderatore','admin','founder')")
        ]]);

    case 'list_users':
        $term='%'.str_replace(['%','_'],['\\%','\\_'],trim((string)($in['q']??''))).'%';
        $st=$pdo->prepare('SELECT id,username,email,role,coins,gems,xp,level,clan,is_banned,ban_reason,last_login,last_seen,created_at FROM users WHERE username LIKE ? OR email LIKE ? ORDER BY created_at DESC LIMIT 200');
        $st->execute([$term,$term]);
        json_response(['success'=>true,'users'=>$st->fetchAll(),'my_rank'=>$myRank,'roles'=>array_values(ARCADE_ROLES)]);

    case 'create_user':
        $need('admin');
        $username=trim((string)($in['username']??''));
        $email=trim((string)($in['email']??''));
        $password=(string)($in['password']??'');
        $role=$cleanRole((string)($in['role']??'user'));
        if(!preg_match('/^[A-Za-z0-9_]{3,20}$/',$username)) json_response(['success'=>false,'error'=>'Username non valido.'],400);
        if($email!=='' && (!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>190)) json_response(['success'=>false,'error'=>'Email non valida.'],400);
        if(!((strlen($password)>=8&&strlen($password)<=200)||preg_match('/^\d{4,6}$/',$password))) json_response(['success'=>false,'error'=>'Password/PIN non valido.'],400);
        if(role_rank($role)>=$myRank) json_response(['success'=>false,'error'=>'Non puoi creare un account con un ruolo pari o superiore al tuo.'],403);
        $chk=$pdo->prepare('SELECT id FROM users WHERE username=? OR (email IS NOT NULL AND email<>"" AND email=?) LIMIT 1');$chk->execute([$username,$email]);
        if($chk->fetch()) json_response(['success'=>false,'error'=>'Username o email già presenti.'],409);
        $id='usr_'.bin2hex(random_bytes(6));$hash=password_hash($password,PASSWORD_DEFAULT);
        insert_row('users',['id'=>$id,'username'=>$username,'email'=>$email?:null,'password_hash'=>$hash,'role'=>$role,'coins'=>500,'gems'=>15,'xp'=>0,'level'=>1,'clan'=>'ZERO','profile_json'=>json_encode(['avatar'=>'😎','bio'=>'','unlocked_skins'=>['default'],'equipped_skin'=>'default'],JSON_UNESCAPED_UNICODE),'is_banned'=>0,'created_at'=>date('Y-m-d H:i:s')]);
        audit($cur,'create_user',$username,'role='.$role);
        json_response(['success'=>true]);

    case 'set_ban':
        $t=$target();$ban=!empty($in['banned'])?1:0;$reason=mb_substr(trim((string)($in['reason']??'')),0,200);
        $pdo->prepare('UPDATE users SET is_banned=?,ban_reason=? WHERE id=?')->execute([$ban,$ban?$reason:null,$t['id']]);
        audit($cur,$ban?'ban':'unban',$t['username'],$reason);json_response(['success'=>true]);

    case 'set_role':
        $need('admin');$t=$target();$role=$cleanRole((string)($in['role']??''));
        if(role_rank($role)>=$myRank) json_response(['success'=>false,'error'=>'Ruolo non assegnabile.'],403);
        $pdo->prepare('UPDATE users SET role=? WHERE id=?')->execute([$role,$t['id']]);audit($cur,'set_role',$t['username'],$t['role'].' -> '.$role);json_response(['success'=>true]);

    case 'grant':
        $need('admin');$t=$target();$coins=max(-100000000,min(100000000,(int)($in['coins']??0)));$gems=max(-1000000,min(1000000,(int)($in['gems']??0)));
        $pdo->prepare('UPDATE users SET coins=GREATEST(0,coins+?), gems=GREATEST(0,gems+?) WHERE id=?')->execute([$coins,$gems,$t['id']]);audit($cur,'grant',$t['username'],"coins $coins, gems $gems");json_response(['success'=>true]);

    case 'edit_user':
        $need('admin');$t=$target();
        $coins=max(0,min(2000000000,(int)($in['coins']??$t['coins'])));
        $gems=max(0,min(2000000000,(int)($in['gems']??$t['gems'])));
        $xp=max(0,min(2000000000,(int)($in['xp']??$t['xp'])));
        $level=max(1,min(100000,(int)($in['level']??$t['level'])));
        $clan=substr(preg_replace('/[^A-Za-z0-9_-]/','',(string)($in['clan']??$t['clan'])),0,8) ?: 'ZERO';
        $pdo->prepare('UPDATE users SET coins=?,gems=?,xp=?,level=?,clan=? WHERE id=?')->execute([$coins,$gems,$xp,$level,$clan,$t['id']]);
        audit($cur,'edit_user',$t['username'],'coins='.$coins.', gems='.$gems.', xp='.$xp.', level='.$level.', clan='.$clan);json_response(['success'=>true]);

    case 'reset_password':
        $need('admin');$t=$target();$password=(string)($in['password']??'');
        if(!((strlen($password)>=8&&strlen($password)<=200)||preg_match('/^\d{4,6}$/',$password))) json_response(['success'=>false,'error'=>'Password/PIN non valido.'],400);
        $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($password,PASSWORD_DEFAULT),$t['id']]);audit($cur,'reset_password',$t['username']);json_response(['success'=>true]);

    case 'delete_user':
        $need('founder');$t=$target();$pdo->beginTransaction();
        try{
            foreach(['arcade_scores','game_progress','game_profiles','lobby_chat'] as $table) $pdo->prepare("DELETE FROM `$table` WHERE user_id=?")->execute([$t['id']]);
            $pdo->prepare("UPDATE game_rooms SET status='closed',result='closed' WHERE (p1=? OR p2=?) AND status IN ('waiting','playing')")->execute([$t['id'],$t['id']]);
            $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$t['id']]);$pdo->commit();
        }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();json_response(['success'=>false,'error'=>'Eliminazione non riuscita.'],500);}
        audit($cur,'delete_user',$t['username']);json_response(['success'=>true]);

    case 'chat_list':
        $st=$pdo->query('SELECT id,username,text,created_at FROM lobby_chat ORDER BY id DESC LIMIT 200');json_response(['success'=>true,'messages'=>$st->fetchAll()]);
    case 'chat_delete':
        $need('moderatore');$pdo->prepare('DELETE FROM lobby_chat WHERE id=?')->execute([(int)($in['id']??0)]);audit($cur,'chat_delete','#'.(int)($in['id']??0));json_response(['success'=>true]);
    case 'chat_clear':
        $need('admin');$pdo->exec('DELETE FROM lobby_chat');audit($cur,'chat_clear');json_response(['success'=>true]);

    case 'list_scores':
        $game=substr((string)($in['game']??''),0,30);$sql='SELECT s.game,s.user_id,s.score,s.updated_at,u.username,u.role FROM arcade_scores s JOIN users u ON u.id=s.user_id';$params=[];
        if($game!==''){$sql.=' WHERE s.game=?';$params[]=$game;}$sql.=' ORDER BY s.score DESC LIMIT 200';$st=$pdo->prepare($sql);$st->execute($params);json_response(['success'=>true,'scores'=>$st->fetchAll()]);
    case 'delete_score':
        $need('admin');$uid=(string)($in['user_id']??'');$game=substr((string)($in['game']??''),0,30);$u=$pdo->prepare('SELECT username,role FROM users WHERE id=?');$u->execute([$uid]);$row=$u->fetch();if(!$row)json_response(['success'=>false,'error'=>'Utente non trovato.'],404);if(role_rank((string)$row['role'])>=$myRank)json_response(['success'=>false,'error'=>'Grado non gestibile.'],403);$pdo->prepare('DELETE FROM arcade_scores WHERE game=? AND user_id=?')->execute([$game,$uid]);audit($cur,'delete_score',$row['username'],$game);json_response(['success'=>true]);
    case 'clear_leaderboard':
        $need('founder');$game=substr((string)($in['game']??''),0,30);$pdo->prepare('DELETE FROM arcade_scores WHERE game=?')->execute([$game]);audit($cur,'clear_leaderboard',$game);json_response(['success'=>true]);

    case 'list_progress':
        $game=substr((string)($in['game']??''),0,30);$sql='SELECT p.game,p.user_id,p.best_mass,p.games_played,p.total_kills,p.total_particles,p.total_time,p.last_mass,p.updated_at,u.username,u.role FROM game_progress p JOIN users u ON u.id=p.user_id';$params=[];if($game!==''){$sql.=' WHERE p.game=?';$params[]=$game;}$sql.=' ORDER BY p.best_mass DESC LIMIT 200';$st=$pdo->prepare($sql);$st->execute($params);json_response(['success'=>true,'progress'=>$st->fetchAll()]);
    case 'reset_progress':
        $need('admin');$t=$target();$game=substr((string)($in['game']??''),0,30);if($game===''){ $pdo->prepare('DELETE FROM game_progress WHERE user_id=?')->execute([$t['id']]);$pdo->prepare('DELETE FROM game_profiles WHERE user_id=?')->execute([$t['id']]); }else{$pdo->prepare('DELETE FROM game_progress WHERE user_id=? AND game=?')->execute([$t['id'],$game]);$pdo->prepare('DELETE FROM game_profiles WHERE user_id=? AND game=?')->execute([$t['id'],$game]);}audit($cur,'reset_progress',$t['username'],$game?:'all');json_response(['success'=>true]);

    case 'rooms_list':
        $st=$pdo->query("SELECT id,game,status,p1,p2,turn,winner,result,version,created_at,updated_at FROM game_rooms ORDER BY id DESC LIMIT 200");json_response(['success'=>true,'rooms'=>$st->fetchAll()]);
    case 'close_room':
        $need('moderatore');$id=(int)($in['id']??0);$pdo->prepare("UPDATE game_rooms SET status='closed',result='closed',updated_at=? WHERE id=? AND status IN ('waiting','playing')")->execute([time(),$id]);audit($cur,'close_room','#'.$id);json_response(['success'=>true]);
    case 'close_all_rooms':
        $need('admin');$pdo->exec("UPDATE game_rooms SET status='closed',result='closed',updated_at=UNIX_TIMESTAMP() WHERE status IN ('waiting','playing')");audit($cur,'close_all_rooms');json_response(['success'=>true]);

    case 'settings_get':
        $st=$pdo->query('SELECT setting_key,setting_value FROM za_settings');$out=[];foreach($st->fetchAll() as $r)$out[$r['setting_key']]=$r['setting_value'];json_response(['success'=>true,'settings'=>$out]);
    case 'settings_save':
        $need('admin');$allowed=['maintenance','registration_open','announcement','games_locked'];$changed=[];
        foreach($allowed as $k){if(array_key_exists($k,$in)){$v=is_scalar($in[$k])?(string)$in[$k]:'';$pdo->prepare('INSERT INTO za_settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)')->execute([$k,mb_substr($v,0,5000)]);$changed[]=$k;}}
        foreach(['zeroagar_classic','growth_orbit','tris','forza4'] as $g){if(isset($in['game_'.$g])){$v=(array)$in['game_'.$g];$pdo->prepare('INSERT INTO za_game_flags(game,enabled,maintenance,config_json) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE enabled=VALUES(enabled),maintenance=VALUES(maintenance),config_json=VALUES(config_json)')->execute([$g,!empty($v['enabled'])?1:0,!empty($v['maintenance'])?1:0,json_encode($v['config']??[],JSON_UNESCAPED_UNICODE)]);$changed[]='game_'.$g;}}
        audit($cur,'settings_save','',implode(',',$changed));json_response(['success'=>true]);
    case 'game_flags':
        $st=$pdo->query('SELECT game,enabled,maintenance,config_json FROM za_game_flags');$rows=$st->fetchAll();json_response(['success'=>true,'games'=>$rows]);

    case 'audit_logs':
        $st=$pdo->query('SELECT admin_name,action,target,details,created_at FROM admin_audit ORDER BY id DESC LIMIT 200');json_response(['success'=>true,'logs'=>$st->fetchAll()]);
    case 'purge_audit':
        $need('founder');$days=max(1,min(3650,(int)($in['days']??90)));$pdo->prepare('DELETE FROM admin_audit WHERE created_at < ?')->execute([date('Y-m-d H:i:s',time()-$days*86400)]);audit($cur,'purge_audit','',$days.' giorni');json_response(['success'=>true]);
}
json_response(['success'=>false,'error'=>'Azione non valida.'],400);
