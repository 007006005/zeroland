<?php
/**
 * Acquisto pacchetti ZeroCoins.
 * In PAYMENTS_MODE = 'demo' il pagamento è SIMULATO (nessun addebito reale).
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/game_logic.php';
require_once __DIR__ . '/catalog.php';

$cur = require_login_api();
$in = read_json_post();

if (PAYMENTS_MODE !== 'demo') {
    json_response(['success' => false, 'error' => 'Pagamenti non configurati: collega un gateway reale (PayPal/Stripe) in api/packages.php'], 501);
}

$pkg = find_coin_package((string)($in['package_id'] ?? ''));
if (!$pkg) json_response(['success' => false, 'error' => 'Pacchetto non trovato'], 404);
$method = (string)($in['payment_method'] ?? '');
if (!in_array($method, ['paypal', 'credit_card'], true)) {
    json_response(['success' => false, 'error' => 'Metodo di pagamento non valido'], 400);
}

$u = user_lock($cur['id']);
if (!$u) json_response(['success' => false, 'error' => 'Utente non trovato'], 404);

$totalCoins = (int)$pkg['coins'] + (int)($pkg['bonus_coins'] ?? 0);
$u['coins'] += $totalCoins;
$u['gems']  += (int)$pkg['gems'];
foreach ($pkg['grants_items'] ?? [] as $it) {
    if (!in_array($it, $u['unlocked_items'], true)) $u['unlocked_items'][] = $it;
}
if (!empty($pkg['gives_premium_role'])) {
    if (in_array($u['role'], ['user', 'premium'], true)) $u['role'] = 'premium';
    if (empty($pkg['gives_license_days'])) {
        $u['premium_source'] = 'package';   // VIP permanente
        $u['is_premium_trial'] = false;
    }
}
if (!empty($pkg['gives_license_days'])) {
    $base = ($u['has_pro_license'] && $u['license_expires_at']) ? max(time(), strtotime($u['license_expires_at'])) : time();
    $u['has_pro_license'] = true;
    $u['license_expires_at'] = date('c', $base + (int)$pkg['gives_license_days'] * 86400);
    if ($u['role'] === 'premium' && ($u['premium_source'] ?? null) !== 'package') $u['premium_source'] = 'license';
}

db()->prepare('INSERT INTO orders (user_id, kind, item_id, amount_eur, coins, gems, payment_method, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
    ->execute([$u['id'], 'package', $pkg['id'], $pkg['price_eur'], $totalCoins, (int)$pkg['gems'], $method, 'demo', date('Y-m-d H:i:s')]);
user_commit($u);

json_response([
    'success' => true,
    'message' => 'Pagamento SIMULATO (modalità demo, nessun addebito): +' . number_format($totalCoins, 0, ',', '.') . ' ZeroCoins accreditati!',
    'user'    => $u,
]);
