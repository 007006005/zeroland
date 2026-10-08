<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

$action = (string)($_GET['action'] ?? 'bootstrap');
$in = read_json_post();
if (!empty($in['action'])) $action = (string)$in['action'];
$pdo = db();

function social_public_user(array $u): array {
    return [
        'id'=>(string)$u['id'], 'name'=>(string)$u['username'], 'username'=>(string)$u['username'],
        'role'=>(string)$u['role'], 'level'=>(int)$u['level'], 'clan'=>(string)$u['clan'],
        'avatar'=> '😎', 'bio'=>''
    ];
}
function social_user_by_id(PDO $pdo, string $id): ?array {
    $st=$pdo->prepare('SELECT id,username,role,level,clan,profile_json FROM users WHERE id=? LIMIT 1');
    $st->execute([$id]); $r=$st->fetch(); return $r ?: null;
}
function social_need_user(): array {
    $u=current_user();
    if (!$u) json_response(['ok'=>false,'error'=>'Accesso richiesto'],401);
    if (!empty($u['is_banned'])) json_response(['ok'=>false,'error'=>'Account bannato'],403);
    return $u;
}
function social_actor(PDO $pdo, string $uid): array {
    $u=social_user_by_id($pdo,$uid);
    return $u ? social_public_user($u) : ['id'=>$uid,'name'=>'Utente','username'=>'Utente','role'=>'user','level'=>1,'clan'=>'ZERO'];
}
function social_notify(PDO $pdo,string $uid,?string $actor,string $type,?string $entity,array $payload=[]): void {
    if ($actor !== null && $uid === $actor) return;
    $st=$pdo->prepare('INSERT INTO social_notifications(user_id,actor_id,type,entity_id,payload_json) VALUES(?,?,?,?,?)');
    $st->execute([$uid,$actor,$type,$entity,json_encode($payload,JSON_UNESCAPED_UNICODE)]);
}
function social_post(PDO $pdo, int $id, ?string $viewer=null): ?array {
    $st=$pdo->prepare('SELECT p.*,u.username,u.role,u.level FROM social_posts p JOIN users u ON u.id=p.user_id WHERE p.id=? AND p.deleted_at IS NULL LIMIT 1');
    $st->execute([$id]); $p=$st->fetch(); if(!$p)return null;
    $r=$pdo->prepare('SELECT reaction,COUNT(*) c FROM social_reactions WHERE post_id=? GROUP BY reaction'); $r->execute([$id]);
    $reactions=[]; foreach($r->fetchAll() as $x)$reactions[(string)$x['reaction']]=(int)$x['c'];
    $r=$pdo->prepare('SELECT c.*,u.username FROM social_comments c JOIN users u ON u.id=c.user_id WHERE c.post_id=? ORDER BY c.id DESC LIMIT 30'); $r->execute([$id]);
    $comments=[]; foreach(array_reverse($r->fetchAll()) as $c)$comments[]=['id'=>(int)$c['id'],'user'=>['id'=>$c['user_id'],'name'=>$c['username']],'body'=>$c['body'],'created'=>$c['created_at']];
    $mine=null; $saved=false;
    if($viewer){$r=$pdo->prepare('SELECT reaction FROM social_reactions WHERE post_id=? AND user_id=?');$r->execute([$id,$viewer]);$mine=$r->fetchColumn()?:null;$r=$pdo->prepare('SELECT 1 FROM social_saved WHERE post_id=? AND user_id=?');$r->execute([$id,$viewer]);$saved=(bool)$r->fetchColumn();}
    return ['id'=>(int)$p['id'],'type'=>$p['type'],'body'=>$p['body'],'media_url'=>$p['media_url'],'link_url'=>$p['link_url'],'visibility'=>$p['visibility'],'created'=>$p['created_at'],'author'=>['id'=>$p['user_id'],'name'=>$p['username'],'role'=>$p['role'],'level'=>(int)$p['level']], 'reactions'=>$reactions,'my_reaction'=>$mine,'saved'=>$saved,'comments'=>$comments];
}
function social_feed(PDO $pdo, ?string $viewer, int $limit=30): array {
    $limit=max(1,min(50,$limit));
    $sql='SELECT p.id FROM social_posts p WHERE p.deleted_at IS NULL AND (p.visibility="public" OR p.user_id=?)';
    $params=[$viewer];
    if($viewer){
      $sql.=' AND NOT EXISTS (SELECT 1 FROM social_blocks b WHERE b.user_id=? AND b.blocked_id=p.user_id) AND NOT EXISTS (SELECT 1 FROM social_blocks b2 WHERE b2.user_id=p.user_id AND b2.blocked_id=?)';
      $params[]=$viewer;$params[]=$viewer;
    }
    $sql.=' ORDER BY p.created_at DESC LIMIT '.$limit; $st=$pdo->prepare($sql); $st->execute($params);
    $out=[];foreach($st->fetchAll() as $r){$p=social_post($pdo,(int)$r['id'],$viewer);if($p)$out[]=$p;}return $out;
}
function social_json_payload(string $s,int $max=10000): array { $j=json_decode($s,true); return is_array($j)?$j:[]; }

if($action==='bootstrap'){
    $u=current_user(); $uid=$u['id']??null;
    $stories=[];
    $st=$pdo->query('SELECT s.*,u.username,u.role FROM social_stories s JOIN users u ON u.id=s.user_id WHERE s.expires_at>NOW() ORDER BY s.created_at DESC LIMIT 60');
    foreach($st->fetchAll() as $r)$stories[]=['id'=>(int)$r['id'],'body'=>$r['body'],'media_url'=>$r['media_url'],'created'=>$r['created_at'],'expires'=>$r['expires_at'],'author'=>['id'=>$r['user_id'],'name'=>$r['username'],'role'=>$r['role']]];
    $groups=$pdo->query('SELECT g.id,g.name,g.description,g.privacy,g.created_at,(SELECT COUNT(*) FROM social_group_members gm WHERE gm.group_id=g.id) members FROM social_groups g ORDER BY g.created_at DESC LIMIT 30')->fetchAll();
    $pages=$pdo->query('SELECT p.id,p.name,p.category,p.description,p.created_at,(SELECT COUNT(*) FROM social_page_followers pf WHERE pf.page_id=p.id) followers FROM social_pages p ORDER BY p.created_at DESC LIMIT 30')->fetchAll();
    $events=$pdo->query('SELECT e.id,e.title,e.description,e.location,e.starts_at,e.ends_at,e.created_at,(SELECT COUNT(*) FROM social_event_attendees ea WHERE ea.event_id=e.id AND ea.status="going") going FROM social_events e WHERE e.starts_at>=DATE_SUB(NOW(),INTERVAL 7 DAY) ORDER BY e.starts_at ASC LIMIT 30')->fetchAll();
    $market=$pdo->query('SELECT m.id,m.title,m.description,m.price,m.location,m.status,m.created_at,u.username seller FROM social_marketplace m JOIN users u ON u.id=m.seller_id WHERE m.status="active" ORDER BY m.created_at DESC LIMIT 40')->fetchAll();
    $notifications=[];$messages=[];$friends=[];$saved=[];
    if($uid){
      $st=$pdo->prepare('SELECT n.*,u.username actor_name FROM social_notifications n LEFT JOIN users u ON u.id=n.actor_id WHERE n.user_id=? ORDER BY n.id DESC LIMIT 40');$st->execute([$uid]);
      foreach($st->fetchAll() as $n)$notifications[]=['id'=>(int)$n['id'],'type'=>$n['type'],'entity_id'=>$n['entity_id'],'read'=>(bool)$n['is_read'],'created'=>$n['created_at'],'actor'=>$n['actor_id']?['id'=>$n['actor_id'],'name'=>$n['actor_name']]:null,'payload'=>social_json_payload((string)$n['payload_json'])];
      $st=$pdo->prepare('SELECT u.id,u.username,u.role,u.level,f.status,f.requested_by FROM social_friends f JOIN users u ON u.id=IF(f.user_a=?,f.user_b,f.user_a) WHERE (f.user_a=? OR f.user_b=?) ORDER BY f.updated_at DESC LIMIT 100');$st->execute([$uid,$uid,$uid]);foreach($st->fetchAll() as $r)$friends[]=['id'=>$r['id'],'name'=>$r['username'],'role'=>$r['role'],'level'=>(int)$r['level'],'status'=>$r['status'],'requested_by'=>$r['requested_by']];
      $st=$pdo->prepare('SELECT DISTINCT thread_key, id, sender_id, recipient_id, body, created_at, read_at FROM social_messages WHERE sender_id=? OR recipient_id=? ORDER BY id DESC LIMIT 200');$st->execute([$uid,$uid]);
      foreach($st->fetchAll() as $m){$peer=$m['sender_id']===$uid?$m['recipient_id']:$m['sender_id'];$pu=social_user_by_id($pdo,$peer);$messages[]=['id'=>(int)$m['id'],'thread'=>$m['thread_key'],'peer'=>$pu?['id'=>$pu['id'],'name'=>$pu['username']]:['id'=>$peer,'name'=>'Utente'],'from'=>$m['sender_id'],'body'=>$m['body'],'created'=>$m['created_at'],'read'=>(bool)$m['read_at']];}
      $st=$pdo->prepare('SELECT s.post_id FROM social_saved s WHERE s.user_id=? ORDER BY s.created_at DESC LIMIT 100');$st->execute([$uid]);foreach($st->fetchAll() as $x){$p=social_post($pdo,(int)$x['post_id'],$uid);if($p)$saved[]=$p;}
    }
    json_response(['ok'=>true,'user'=>$u?user_public($u):null,'feed'=>social_feed($pdo,$uid,40),'stories'=>$stories,'groups'=>$groups,'pages'=>$pages,'events'=>$events,'marketplace'=>$market,'notifications'=>$notifications,'friends'=>$friends,'messages'=>$messages,'saved'=>$saved,'server_time'=>date('c')]);
}

if($action==='search'){
    $q=trim((string)($in['q']??'')); if($q==='')json_response(['ok'=>true,'users'=>[],'pages'=>[],'groups'=>[],'events'=>[]]);
    $like='%'.$q.'%';
    $st=$pdo->prepare('SELECT id,username,role,level FROM users WHERE username LIKE ? AND is_banned=0 ORDER BY username LIMIT 20');$st->execute([$like]);$users=array_map(fn($r)=>['id'=>$r['id'],'name'=>$r['username'],'role'=>$r['role'],'level'=>(int)$r['level']],$st->fetchAll());
    $st=$pdo->prepare('SELECT id,name,category FROM social_pages WHERE name LIKE ? ORDER BY name LIMIT 20');$st->execute([$like]);$pages=$st->fetchAll();
    $st=$pdo->prepare('SELECT id,name,description FROM social_groups WHERE name LIKE ? ORDER BY name LIMIT 20');$st->execute([$like]);$groups=$st->fetchAll();
    $st=$pdo->prepare('SELECT id,title,location,starts_at FROM social_events WHERE title LIKE ? ORDER BY starts_at LIMIT 20');$st->execute([$like]);$events=$st->fetchAll();
    json_response(['ok'=>true,'users'=>$users,'pages'=>$pages,'groups'=>$groups,'events'=>$events]);
}

$u=social_need_user();$uid=$u['id'];
if(($_SERVER['REQUEST_METHOD']??'GET')!=='GET')enforce_same_origin();

switch($action){
case 'post_create':
  $body=trim((string)($in['body']??'')); if($body==='')json_response(['ok'=>false,'error'=>'Contenuto vuoto'],400); $body=mb_substr($body,0,5000);
  $type=preg_replace('/[^a-z_]/','',strtolower((string)($in['type']??'post')))?:'post';$vis=in_array(($in['visibility']??'public'),['public','friends','only_me'],true)?$in['visibility']:'public';
  $st=$pdo->prepare('INSERT INTO social_posts(user_id,type,body,media_url,link_url,visibility) VALUES(?,?,?,?,?,?)');$st->execute([$uid,$type,$body,$in['media_url']??null,$in['link_url']??null,$vis]);$id=(int)$pdo->lastInsertId();
  json_response(['ok'=>true,'post'=>social_post($pdo,$id,$uid)]);
case 'post_react':
  $pid=(int)($in['post_id']??0);$reaction=(string)($in['reaction']??'like');$allowed=['like','love','care','laugh','wow','sad','angry'];if(!in_array($reaction,$allowed,true))$reaction='like';
  $st=$pdo->prepare('SELECT user_id FROM social_posts WHERE id=? AND deleted_at IS NULL');$st->execute([$pid]);$owner=$st->fetchColumn();if(!$owner)json_response(['ok'=>false,'error'=>'Post non trovato'],404);
  $st=$pdo->prepare('SELECT reaction FROM social_reactions WHERE post_id=? AND user_id=?');$st->execute([$pid,$uid]);$old=$st->fetchColumn();
  if($old===$reaction){$pdo->prepare('DELETE FROM social_reactions WHERE post_id=? AND user_id=?')->execute([$pid,$uid]);$mine=null;}else{$pdo->prepare('INSERT INTO social_reactions(post_id,user_id,reaction) VALUES(?,?,?) ON DUPLICATE KEY UPDATE reaction=VALUES(reaction),created_at=NOW()')->execute([$pid,$uid,$reaction]);$mine=$reaction;if($owner!==$uid)social_notify($pdo,$owner,$uid,'reaction',(string)$pid,['reaction'=>$reaction]);}
  json_response(['ok'=>true,'my_reaction'=>$mine,'post'=>social_post($pdo,$pid,$uid)]);
case 'post_comment':
  $pid=(int)($in['post_id']??0);$body=trim((string)($in['body']??''));if(!$pid||$body==='')json_response(['ok'=>false,'error'=>'Commento non valido'],400);$body=mb_substr($body,0,1000);
  $st=$pdo->prepare('SELECT user_id FROM social_posts WHERE id=? AND deleted_at IS NULL');$st->execute([$pid]);$owner=$st->fetchColumn();if(!$owner)json_response(['ok'=>false,'error'=>'Post non trovato'],404);
  $pdo->prepare('INSERT INTO social_comments(post_id,user_id,body) VALUES(?,?,?)')->execute([$pid,$uid,$body]);$cid=(int)$pdo->lastInsertId();if($owner!==$uid)social_notify($pdo,$owner,$uid,'comment',(string)$pid,['body'=>$body]);json_response(['ok'=>true,'comment_id'=>$cid,'post'=>social_post($pdo,$pid,$uid)]);
case 'post_share':
  $pid=(int)($in['post_id']??0);$body=trim((string)($in['body']??''));$st=$pdo->prepare('SELECT user_id,body FROM social_posts WHERE id=? AND deleted_at IS NULL');$st->execute([$pid]);$src=$st->fetch();if(!$src)json_response(['ok'=>false,'error'=>'Post non trovato'],404);
  $pdo->prepare('INSERT INTO social_shares(post_id,user_id,body) VALUES(?,?,?)')->execute([$pid,$uid,mb_substr($body,0,500)]);$newBody=$body!==''?$body."\n\n↗ Condivisione: ".$src['body']:$src['body'];
  $pdo->prepare('INSERT INTO social_posts(user_id,type,body,visibility) VALUES(?,?,?,\'public\')')->execute([$uid,'share',$newBody]);if($src['user_id']!==$uid)social_notify($pdo,$src['user_id'],$uid,'share',(string)$pid,[]);json_response(['ok'=>true,'post'=>social_post($pdo,(int)$pdo->lastInsertId(),$uid)]);
case 'post_save':
  $pid=(int)($in['post_id']??0);$st=$pdo->prepare('SELECT 1 FROM social_saved WHERE user_id=? AND post_id=?');$st->execute([$uid,$pid]);$exists=(bool)$st->fetchColumn();if($exists)$pdo->prepare('DELETE FROM social_saved WHERE user_id=? AND post_id=?')->execute([$uid,$pid]);else$pdo->prepare('INSERT IGNORE INTO social_saved(user_id,post_id) VALUES(?,?)')->execute([$uid,$pid]);json_response(['ok'=>true,'saved'=>!$exists]);
case 'post_delete':
  $pid=(int)($in['post_id']??0);$st=$pdo->prepare('SELECT user_id FROM social_posts WHERE id=?');$st->execute([$pid]);$owner=$st->fetchColumn();if($owner!==$uid && role_rank((string)$u['role']) < role_rank('moderatore'))json_response(['ok'=>false,'error'=>'Non autorizzato'],403);$pdo->prepare('UPDATE social_posts SET deleted_at=NOW() WHERE id=?')->execute([$pid]);json_response(['ok'=>true]);
case 'friend_request':
  $to=(string)($in['user_id']??'');if(!$to||$to===$uid)json_response(['ok'=>false,'error'=>'Utente non valido'],400);$st=$pdo->prepare('SELECT id,username FROM users WHERE id=? AND is_banned=0');$st->execute([$to]);$tu=$st->fetch();if(!$tu)json_response(['ok'=>false,'error'=>'Utente non trovato'],404);
  $a=min($uid,$to);$b=max($uid,$to);$pdo->prepare('INSERT INTO social_friends(user_a,user_b,status,requested_by) VALUES(?,?,\'pending\',?) ON DUPLICATE KEY UPDATE status=IF(status=\'declined\',\'pending\',status),requested_by=VALUES(requested_by),updated_at=NOW()')->execute([$a,$b,$uid]);social_notify($pdo,$to,$uid,'friend_request',null,['user_id'=>$uid]);json_response(['ok'=>true]);
case 'friend_respond':
  $other=(string)($in['user_id']??'');$status=(string)($in['status']??'declined');if(!in_array($status,['accepted','declined','removed'],true))$status='declined';$a=min($uid,$other);$b=max($uid,$other);
  if($status==='removed')$pdo->prepare('DELETE FROM social_friends WHERE user_a=? AND user_b=?')->execute([$a,$b]);else{$pdo->prepare('UPDATE social_friends SET status=?,updated_at=NOW() WHERE user_a=? AND user_b=?')->execute([$status,$a,$b]);if($status==='accepted')social_notify($pdo,$other,$uid,'friend_accept',null,[]);}json_response(['ok'=>true,'status'=>$status]);
case 'follow':
  $type=(string)($in['target_type']??'user');$id=(string)($in['target_id']??'');if(!in_array($type,['user','page','group'],true)||$id==='')json_response(['ok'=>false,'error'=>'Destinazione non valida'],400);
  $st=$pdo->prepare('SELECT 1 FROM social_follows WHERE follower_id=? AND target_type=? AND target_id=?');$st->execute([$uid,$type,$id]);$has=(bool)$st->fetchColumn();if($has)$pdo->prepare('DELETE FROM social_follows WHERE follower_id=? AND target_type=? AND target_id=?')->execute([$uid,$type,$id]);else$pdo->prepare('INSERT INTO social_follows(follower_id,target_type,target_id) VALUES(?,?,?)')->execute([$uid,$type,$id]);json_response(['ok'=>true,'following'=>!$has]);
case 'message_send':
  $to=(string)($in['user_id']??'');$body=trim((string)($in['body']??''));if(!$to||$to===$uid||$body==='')json_response(['ok'=>false,'error'=>'Messaggio non valido'],400);$st=$pdo->prepare('SELECT id FROM users WHERE id=? AND is_banned=0');$st->execute([$to]);if(!$st->fetch())json_response(['ok'=>false,'error'=>'Destinatario non trovato'],404);
  $thread=strcmp($uid,$to)<0?$uid.':'.$to:$to.':'.$uid;$pdo->prepare('INSERT INTO social_messages(thread_key,sender_id,recipient_id,body) VALUES(?,?,?,?)')->execute([$thread,$uid,$to,mb_substr($body,0,4000)]);social_notify($pdo,$to,$uid,'message',(string)$pdo->lastInsertId(),[]);json_response(['ok'=>true]);
case 'message_read':
  $tid=(string)($in['thread']??'');if($tid!=='')$pdo->prepare('UPDATE social_messages SET read_at=NOW() WHERE thread_key=? AND recipient_id=?')->execute([$tid,$uid]);json_response(['ok'=>true]);
case 'story_create':
  $body=trim((string)($in['body']??''));if($body==='')json_response(['ok'=>false,'error'=>'Storia vuota'],400);$pdo->prepare('INSERT INTO social_stories(user_id,body,media_url,expires_at) VALUES(?,?,?,DATE_ADD(NOW(),INTERVAL 24 HOUR))')->execute([$uid,mb_substr($body,0,1000),$in['media_url']??null]);json_response(['ok'=>true]);
case 'story_view':
  $sid=(int)($in['story_id']??0);$pdo->prepare('INSERT IGNORE INTO social_story_views(story_id,user_id) VALUES(?,?)')->execute([$sid,$uid]);json_response(['ok'=>true]);
case 'group_create':
  $name=trim((string)($in['name']??''));if($name==='')json_response(['ok'=>false,'error'=>'Nome gruppo richiesto'],400);$pdo->prepare('INSERT INTO social_groups(owner_id,name,description,privacy) VALUES(?,?,?,?)')->execute([$uid,mb_substr($name,0,120),mb_substr((string)($in['description']??''),0,1000),in_array(($in['privacy']??'public'),['public','private'],true)?$in['privacy']:'public']);$gid=(int)$pdo->lastInsertId();$pdo->prepare('INSERT INTO social_group_members(group_id,user_id,role) VALUES(?,?,\'admin\')')->execute([$gid,$uid]);json_response(['ok'=>true,'id'=>$gid]);
case 'group_join':
  $gid=(int)($in['group_id']??0);$join=($in['join']??true)!==false;if($join)$pdo->prepare('INSERT IGNORE INTO social_group_members(group_id,user_id) VALUES(?,?)')->execute([$gid,$uid]);else$pdo->prepare('DELETE FROM social_group_members WHERE group_id=? AND user_id=?')->execute([$gid,$uid]);json_response(['ok'=>true,'joined'=>$join]);
case 'page_create':
  $name=trim((string)($in['name']??''));if($name==='')json_response(['ok'=>false,'error'=>'Nome pagina richiesto'],400);$pdo->prepare('INSERT INTO social_pages(owner_id,name,category,description) VALUES(?,?,?,?)')->execute([$uid,mb_substr($name,0,120),mb_substr((string)($in['category']??'Community'),0,80),mb_substr((string)($in['description']??''),0,1000)]);json_response(['ok'=>true,'id'=>(int)$pdo->lastInsertId()]);
case 'page_follow':
  $pid=(int)($in['page_id']??0);$st=$pdo->prepare('SELECT 1 FROM social_page_followers WHERE page_id=? AND user_id=?');$st->execute([$pid,$uid]);$has=(bool)$st->fetchColumn();if($has)$pdo->prepare('DELETE FROM social_page_followers WHERE page_id=? AND user_id=?')->execute([$pid,$uid]);else$pdo->prepare('INSERT INTO social_page_followers(page_id,user_id) VALUES(?,?)')->execute([$pid,$uid]);json_response(['ok'=>true,'following'=>!$has]);
case 'event_create':
  $title=trim((string)($in['title']??''));$start=(string)($in['starts_at']??'');if($title===''||$start==='')json_response(['ok'=>false,'error'=>'Titolo e data richiesti'],400);$pdo->prepare('INSERT INTO social_events(owner_id,title,description,location,starts_at,ends_at) VALUES(?,?,?,?,?,?)')->execute([$uid,mb_substr($title,0,180),mb_substr((string)($in['description']??''),0,2000),mb_substr((string)($in['location']??''),0,255),$start,$in['ends_at']??null]);json_response(['ok'=>true,'id'=>(int)$pdo->lastInsertId()]);
case 'event_rsvp':
  $eid=(int)($in['event_id']??0);$status=(string)($in['status']??'interested');if(!in_array($status,['going','interested','declined'],true))$status='interested';$pdo->prepare('INSERT INTO social_event_attendees(event_id,user_id,status) VALUES(?,?,?) ON DUPLICATE KEY UPDATE status=VALUES(status),updated_at=NOW()')->execute([$eid,$uid,$status]);json_response(['ok'=>true,'status'=>$status]);
case 'market_create':
  $title=trim((string)($in['title']??''));if($title==='')json_response(['ok'=>false,'error'=>'Titolo richiesto'],400);$price=max(0,(float)($in['price']??0));$pdo->prepare('INSERT INTO social_marketplace(seller_id,title,description,price,location) VALUES(?,?,?,?,?)')->execute([$uid,mb_substr($title,0,180),mb_substr((string)($in['description']??''),0,2000),$price,mb_substr((string)($in['location']??''),0,180)]);json_response(['ok'=>true,'id'=>(int)$pdo->lastInsertId()]);
case 'profile_update':
  $bio=mb_substr(trim((string)($in['bio']??'')),0,500);$avatar=mb_substr(trim((string)($in['avatar']??'')),0,120);
  $raw=(string)($u['profile_json']??'');$profile=json_decode($raw,true);if(!is_array($profile))$profile=[];$profile['bio']=$bio;$profile['avatar']=$avatar!==''?$avatar:($profile['avatar']??'😎');$profile['unlocked_skins']=is_array($profile['unlocked_skins']??null)?$profile['unlocked_skins']:['default'];$profile['equipped_skin']=$profile['equipped_skin']??'default';
  $pdo->prepare('UPDATE users SET profile_json=? WHERE id=?')->execute([json_encode($profile,JSON_UNESCAPED_UNICODE),$uid]);json_response(['ok'=>true,'profile'=>$profile]);
case 'memory':
  $year=(int)($in['year']??0);$st=$pdo->prepare('SELECT p.id FROM social_posts p WHERE p.user_id=? AND p.deleted_at IS NULL'.($year?' AND YEAR(p.created_at)=?':'').' ORDER BY p.created_at DESC LIMIT 50');$year?$st->execute([$uid,$year]):$st->execute([$uid]);$mem=[];foreach($st->fetchAll() as $r){$x=social_post($pdo,(int)$r['id'],$uid);if($x)$mem[]=$x;}json_response(['ok'=>true,'items'=>$mem]);
case 'notify_read':
  if(!empty($in['id']))$pdo->prepare('UPDATE social_notifications SET is_read=1 WHERE id=? AND user_id=?')->execute([(int)$in['id'],$uid]);else$pdo->prepare('UPDATE social_notifications SET is_read=1 WHERE user_id=?')->execute([$uid]);json_response(['ok'=>true]);
case 'block':
  $other=(string)($in['user_id']??'');if($other&&$other!==$uid)$pdo->prepare('INSERT IGNORE INTO social_blocks(user_id,blocked_id) VALUES(?,?)')->execute([$uid,$other]);json_response(['ok'=>true]);
case 'report':
  $type=mb_substr((string)($in['target_type']??'post'),0,30);$id=mb_substr((string)($in['target_id']??''),0,48);$reason=mb_substr(trim((string)($in['reason']??'')),0,1000);if($id===''||$reason==='')json_response(['ok'=>false,'error'=>'Segnalazione incompleta'],400);$pdo->prepare('INSERT INTO social_reports(reporter_id,target_type,target_id,reason) VALUES(?,?,?,?)')->execute([$uid,$type,$id,$reason]);json_response(['ok'=>true]);
default: json_response(['ok'=>false,'error'=>'Azione social non valida'],400);
}
