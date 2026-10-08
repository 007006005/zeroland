<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

$action = (string)($_GET['action'] ?? 'get');
$in = read_json_post();
if (!empty($in['action'])) $action = (string)$in['action'];
$u = require_login_api();
$pdo = db();

enforce_same_origin();

function profile_state_defaults(array $u): array {
    return is_array($u['profile'] ?? null) ? $u['profile'] : [
        'id'=>$u['id'], 'name'=>$u['username'], 'username'=>$u['username'],
        'role'=>$u['role'], 'coins'=>(int)$u['coins'], 'gems'=>(int)$u['gems'],
        'xp'=>(int)$u['xp'], 'level'=>(int)$u['level'], 'clan'=>$u['clan'] ?: 'ZERO',
        'avatar'=>'😎','bio'=>'','unlocked_skins'=>['default'],'equipped_skin'=>'default'
    ];
}

if ($action === 'get') {
    $fresh = current_user(true);
    json_response(['success'=>true,'user'=>$fresh ? user_public($fresh) : null,'server'=>['storage'=>'mysql','local_persistence'=>false]]);
}

if ($action === 'reset') {
    $base = [
        'avatar'=>'😎','bio'=>'','unlocked_skins'=>['default'],'equipped_skin'=>'default',
        'settings'=>[],'achievements'=>[],'quests'=>['date'=>'','items'=>[]],
        'pass'=>['xp'=>0,'tier'=>0,'claimed'=>[]], 'inventory'=>[]
    ];
    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM user_state WHERE user_id=?')->execute([$u['id']]);
        $pdo->prepare('UPDATE users SET profile_json=? WHERE id=?')->execute([json_encode($base,JSON_UNESCAPED_UNICODE),$u['id']]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        json_response(['success'=>false,'error'=>'Reset database non riuscito.'],500);
    }
    $fresh=current_user(true);
    json_response(['success'=>true,'user'=>$fresh?user_public($fresh):null]);
}

if ($action !== 'save') json_response(['success'=>false,'error'=>'Azione profilo non valida.'],400);
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') json_response(['success'=>false,'error'=>'Metodo non consentito.'],405);

$profile = $in['profile'] ?? null;
if (!is_array($profile)) json_response(['success'=>false,'error'=>'Profilo mancante.'],400);

// Never persist authentication/session-only fields supplied by the browser.
foreach (['password','password_hash','pin','serverAuth','loggedIn','provider','email','role','id','username','name'] as $key) unset($profile[$key]);

// Metadata such as avatar/bio/skin ownership stays in users.profile_json; all other mutable state goes to user_state.
foreach (['avatar','bio','unlocked_skins','equipped_skin'] as $metaKey) unset($profile[$metaKey]);

// Bound the request before writing to MEDIUMTEXT. Large skins remain database-backed,
// but an accidental multi-megabyte data URL is rejected rather than corrupting state.
$skin = $profile['skin'] ?? null;
if (is_string($skin) && strlen($skin) > 600000) {
    $profile['skin'] = null;
    $profile['skinKind'] = null;
}

$encoded = json_encode($profile, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
if ($encoded === false || strlen($encoded) > 15000000) json_response(['success'=>false,'error'=>'Stato profilo troppo grande.'],413);

$coins = array_key_exists('coins',$in['profile']) ? max(0,min(2000000000,(int)$in['profile']['coins'])) : (int)$u['coins'];
$gems  = array_key_exists('gems',$in['profile']) ? max(0,min(2000000000,(int)$in['profile']['gems'])) : (int)$u['gems'];
$xp    = array_key_exists('xp',$in['profile']) ? max(0,min(2000000000,(int)$in['profile']['xp'])) : (int)$u['xp'];
$level = max(1,min(1000000,(int)($in['profile']['level'] ?? $u['level'])));
$clan  = array_key_exists('clan',$in['profile']) ? mb_substr(trim((string)$in['profile']['clan']),0,8) : (string)$u['clan'];
if ($clan === '') $clan='ZERO';

$pdo->beginTransaction();
try {
    // Keep only server-owned profile metadata in users.profile_json; full mutable state lives in user_state.
    $base = json_decode((string)($u['profile_json'] ?? ''), true);
    if (!is_array($base)) $base=[];
    foreach (['avatar','bio','unlocked_skins','equipped_skin'] as $k) {
        if (array_key_exists($k,$profile)) $base[$k]=$profile[$k];
    }
    $baseJson=json_encode($base,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
    $pdo->prepare('UPDATE users SET coins=?,gems=?,xp=?,level=?,clan=?,profile_json=?,last_seen=NOW() WHERE id=?')
        ->execute([$coins,$gems,$xp,$level,$clan,$baseJson,$u['id']]);
    $pdo->prepare('INSERT INTO user_state(user_id,state_json,updated_at) VALUES(?,?,NOW()) ON DUPLICATE KEY UPDATE state_json=VALUES(state_json),updated_at=NOW()')->execute([$u['id'],$encoded]);
    $pdo->commit();
    $fresh=current_user(true);
    json_response(['success'=>true,'user'=>$fresh?user_public($fresh):null,'storage'=>'mysql']);
} catch (Throwable $e) {
    // Re-run with an explicit upsert statement for compatibility with MySQL versions.
    $pdo->rollBack();
    try {
        $pdo->beginTransaction();
        $base = json_decode((string)($u['profile_json'] ?? ''), true); if (!is_array($base)) $base=[];
        foreach (['avatar','bio','unlocked_skins','equipped_skin'] as $k) if (array_key_exists($k,$profile)) $base[$k]=$profile[$k];
        $baseJson=json_encode($base,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
        $pdo->prepare('UPDATE users SET coins=?,gems=?,xp=?,level=?,clan=?,profile_json=?,last_seen=NOW() WHERE id=?')->execute([$coins,$gems,$xp,$level,$clan,$baseJson,$u['id']]);
        $pdo->prepare('INSERT INTO user_state(user_id,state_json,updated_at) VALUES(?,?,NOW()) ON DUPLICATE KEY UPDATE state_json=VALUES(state_json),updated_at=NOW()')->execute([$u['id'],$encoded]);
        $pdo->commit();
    } catch (Throwable $e2) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('profile_save: '.$e2->getMessage());
        json_response(['success'=>false,'error'=>'Salvataggio database non riuscito.'],500);
    }
    $fresh=current_user(true); json_response(['success'=>true,'user'=>$fresh?user_public($fresh):null,'storage'=>'mysql']);
}
// unreachable only if first transaction unexpectedly commits before state insert
if ($pdo->inTransaction()) $pdo->rollBack();
json_response(['success'=>false,'error'=>'Salvataggio database non riuscito.'],500);
