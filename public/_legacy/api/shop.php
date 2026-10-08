<?php
/**
 * api/shop.php - API Mercato & Acquisti Skin
 */
require_once __DIR__ . '/db.php';

$cur = require_login_api();
$in = read_json_post();
$action = (string)($in['action'] ?? '');
$pdo = db();

if ($action === 'buy_skin') {
    $skinId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($in['skin_id'] ?? ''));
    $price = (int)($in['price'] ?? 0);
    $currency = (string)($in['currency'] ?? 'coins');

    if (!$skinId || $price <= 0 || !in_array($currency, ['coins', 'gems'], true)) {
        json_response(['success' => false, 'error' => 'Parametri d\'acquisto non validi.'], 400);
    }

    $stmt = $pdo->prepare('SELECT coins, gems, profile_json FROM users WHERE id = ?');
    $stmt->execute([$cur['id']]);
    $u = $stmt->fetch();

    $profile = json_decode($u['profile_json'] ?: '', true) ?: default_profile();
    $unlocked = $profile['unlocked_skins'] ?? ['default'];

    if (in_array($skinId, $unlocked, true)) {
        json_response(['success' => false, 'error' => 'Possiedi già questa skin!'], 400);
    }

    $userBalance = (int)($u[$currency] ?? 0);
    if ($userBalance < $price) {
        json_response(['success' => false, 'error' => "Fondi insufficienti ($currency)."], 400);
    }

    // Detrai saldo e aggiungi skin
    $unlocked[] = $skinId;
    $profile['unlocked_skins'] = array_values(array_unique($unlocked));
    $profile['equipped_skin'] = $skinId;

    $stmt = $pdo->prepare("UPDATE users SET $currency = $currency - ?, profile_json = ? WHERE id = ?");
    $stmt->execute([$price, json_encode($profile, JSON_UNESCAPED_UNICODE), $cur['id']]);

    json_response(['success' => true, 'message' => 'Skin acquistata ed equipaggiata!']);
}

json_response(['success' => false, 'error' => 'Azione non valida'], 400);