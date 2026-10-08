<?php
/**
 * api/account.php - Profilo, negozio skin e bonus giornaliero.
 * Prezzi e premi sono decisi qui sul server: il client non può modificarli.
 */
require_once __DIR__ . '/db.php';

$cur = require_login_api();
$in = read_json_post();
$action = (string)($in['action'] ?? ($_GET['action'] ?? 'get'));
$pdo = db();

if ($action === 'get') {
    json_response(['success' => true, 'user' => user_public($cur), 'skins' => arcade_skins()]);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_response(['success' => false, 'error' => 'Metodo non consentito'], 405);
}
enforce_same_origin();

// ------------------------------------------------------------------ Profilo
if ($action === 'update_profile') {
    $bio  = mb_substr(trim(strip_tags((string)($in['bio'] ?? ''))), 0, 200);
    $skin = (string)($in['equipped_skin'] ?? 'default');
    $clan = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', (string)($in['clan'] ?? $cur['clan'])), 0, 6));
    if ($clan === '') $clan = 'ZERO';

    $stored = json_decode((string)($cur['profile_json'] ?? ''), true);
    if (!is_array($stored)) $stored = [];
    $unlocked = is_array($stored['unlocked_skins'] ?? null) ? $stored['unlocked_skins'] : ['default'];
    if (!in_array('default', $unlocked, true)) $unlocked[] = 'default';
    if (!in_array($skin, $unlocked, true)) $skin = 'default';
    $stored['bio'] = $bio;
    $stored['equipped_skin'] = $skin;
    $stored['unlocked_skins'] = $unlocked;

    $pdo->prepare('UPDATE users SET profile_json = ?, clan = ? WHERE id = ?')
        ->execute([json_encode($stored, JSON_UNESCAPED_UNICODE), $clan, $cur['id']]);
    json_response(['success' => true, 'message' => 'Profilo aggiornato.', 'user' => user_public(current_user(true))]);
}

// ------------------------------------------------------------------ Acquisto skin
if ($action === 'buy_skin') {
    $skins = arcade_skins();
    $id = (string)($in['skin_id'] ?? '');
    if (!isset($skins[$id]) || $id === 'default') {
        json_response(['success' => false, 'error' => 'Skin non valida.'], 400);
    }
    $price = (int)$skins[$id]['price'];
    $cur_col = $skins[$id]['currency'] === 'gems' ? 'gems' : 'coins';

    try {
        $pdo->beginTransaction();
        $st = $pdo->prepare('SELECT coins, gems, profile_json FROM users WHERE id = ? FOR UPDATE');
        $st->execute([$cur['id']]);
        $row = $st->fetch();
        $profile = json_decode((string)$row['profile_json'], true);
        if (!is_array($profile)) $profile = [];
        $profile['unlocked_skins'] = is_array($profile['unlocked_skins'] ?? null) ? $profile['unlocked_skins'] : ['default'];
        if (!in_array('default', $profile['unlocked_skins'], true)) $profile['unlocked_skins'][] = 'default';

        if (in_array($id, $profile['unlocked_skins'], true)) {
            $pdo->rollBack();
            json_response(['success' => false, 'error' => 'Possiedi già questa skin.'], 400);
        }
        if ((int)$row[$cur_col] < $price) {
            $pdo->rollBack();
            json_response(['success' => false, 'error' => 'Fondi insufficienti.'], 400);
        }
        $profile['unlocked_skins'][] = $id;
        $profile['equipped_skin'] = $id;
        $pdo->prepare("UPDATE users SET $cur_col = $cur_col - ?, profile_json = ? WHERE id = ?")
            ->execute([$price, json_encode($profile, JSON_UNESCAPED_UNICODE), $cur['id']]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('buy_skin: ' . $e->getMessage());
        json_response(['success' => false, 'error' => 'Acquisto non riuscito.'], 500);
    }
    json_response(['success' => true, 'message' => 'Skin ' . $skins[$id]['name'] . ' acquistata ed equipaggiata!', 'user' => user_public(current_user(true))]);
}

// ------------------------------------------------------------------ Bonus giornaliero (una volta al giorno)
if ($action === 'daily') {
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $streak = (($cur['last_daily'] ?? null) === $yesterday) ? (int)$cur['daily_streak'] + 1 : 1;
    $coins = 100 + 25 * min($streak, 7);
    $gems = ($streak % 7 === 0) ? 5 : 0;

    // L'UPDATE condizionale rende impossibile riscuotere due volte lo stesso giorno
    $st = $pdo->prepare('UPDATE users SET last_daily = ?, daily_streak = ?, gems = gems + ? WHERE id = ? AND (last_daily IS NULL OR last_daily < ?)');
    $st->execute([$today, $streak, $gems, $cur['id'], $today]);
    if ($st->rowCount() === 0) {
        json_response(['success' => false, 'error' => 'Bonus già riscosso oggi. Torna domani!'], 400);
    }
    add_rewards($cur['id'], $coins, 20);
    json_response([
        'success' => true,
        'message' => "🎁 Giorno $streak: +$coins monete" . ($gems ? ", +$gems gemme" : '') . ' e +20 XP!',
        'user' => user_public(current_user(true)),
    ]);
}

json_response(['success' => false, 'error' => 'Azione non valida.'], 400);
