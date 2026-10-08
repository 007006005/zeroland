<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
$u = require_login_api();
enforce_same_origin();
$in = read_json_post();
$action = (string)($_GET['action'] ?? ($in['action'] ?? 'status'));
$allowed = ['welcome','goodbye'];
$event = (string)($_GET['event'] ?? ($in['event'] ?? ''));
if (!in_array($event, $allowed, true)) json_response(['success'=>false,'error'=>'Evento non valido.'],400);
$pdo = db();
if ($action === 'status') {
  $st=$pdo->prepare('SELECT 1 FROM user_voice_events WHERE user_id=? AND event_key=? LIMIT 1');
  $st->execute([$u['id'],'voice_'.$event]);
  json_response(['success'=>true,'played'=>(bool)$st->fetchColumn()]);
}
if ($action === 'claim') {
  $st=$pdo->prepare('INSERT IGNORE INTO user_voice_events(user_id,event_key,played_at) VALUES(?,?,NOW())');
  $st->execute([$u['id'],'voice_'.$event]);
  json_response(['success'=>true,'claimed'=>$st->rowCount()===1]);
}
json_response(['success'=>false,'error'=>'Azione non valida.'],400);
