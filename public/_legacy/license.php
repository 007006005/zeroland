<?php
/** Licenza Arcade Pro 30 giorni. Pagamento SIMULATO in modalità demo. */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/game_logic.php';
require_once __DIR__ . '/catalog.php';

$cur = require_login_api();
$in = read_json_post();

if (PAYMENTS_MODE !== 'demo') {
    json_response(['success' => false, 'error' => 'Pagamenti non configurati: collega un gateway reale in api/license.php'], 501);
}
$method = (string)($in['payment_method'] ?? '');
if (!in_array($method, ['paypal', 'credit_card'], true)) {
    json_response(['success' => false, 'error' => 'Metodo di pagamento non valido'], 400);
}

$u = user_lock($cur['id']);
if (!$u) json_response(['success' => false, 'error' => 'Utente non trovato'], 404);

$base = ($u['has_pro_license'] && $u['license_expires_at']) ? max(time(), strtotime($u['license_expires_at'])) : time();
$u['has_pro_license'] = true;
$u['license_expires_at'] = date('c', $base + LICENSE_DAYS * 86400);
if (in_array($u['role'], ['user', 'premium'], true)) {
    $u['role'] = 'premium';
    if (($u['premium_source'] ?? null) !== 'package') $u['premium_source'] = 'license';
}

db()->prepare('INSERT INTO orders (user_id, kind, item_id, amount_eur, coins, gems, payment_method, status, created_at) VALUES (?, ?, ?, ?, 0, 0, ?, ?, ?)')
    ->execute([$u['id'], 'license', 'license_30d', LICENSE_PRICE_EUR, $method, 'demo', date('Y-m-d H:i:s')]);
user_commit($u);

json_response([
    'success'    => true,
    'message'    => 'Licenza 30 giorni attivata (pagamento simulato in modalità demo)',
    'expires_at' => $u['license_expires_at'],
    'user'       => $u,
]);
