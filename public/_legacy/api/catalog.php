<?php
/** Pacchetti ZeroCoins: definiti SOLO lato server (il client invia solo l'id). */
function get_coin_packages(): array {
    return [
        ['id' => 'pack_starter', 'name' => 'Starter Capsule', 'coins' => 1000, 'bonus_coins' => 0, 'gems' => 0, 'price_eur' => 2.99, 'badge' => 'ENTRY', 'desc' => '1.000 ZeroCoins immediati per iniziare'],
        ['id' => 'pack_cadet', 'name' => 'Cadet Booster', 'coins' => 2500, 'bonus_coins' => 250, 'gems' => 5, 'price_eur' => 4.99, 'badge' => '+10% BONUS', 'desc' => '2.750 ZeroCoins totali + 5 Gemme'],
        ['id' => 'pack_combat', 'name' => 'Combat Squadron', 'coins' => 5500, 'bonus_coins' => 750, 'gems' => 15, 'price_eur' => 9.99, 'badge' => 'POPOLARE', 'desc' => '6.250 ZeroCoins totali + 15 Gemme'],
        ['id' => 'pack_cyber', 'name' => 'Cyber Operative', 'coins' => 12000, 'bonus_coins' => 2000, 'gems' => 35, 'price_eur' => 19.99, 'badge' => 'BEST SELLER', 'desc' => '14.000 ZeroCoins + 35 Gemme + Scudo Overclock', 'grants_items' => ['shield_overclock']],
        ['id' => 'pack_vip_trial_upgrade', 'name' => 'VIP PASS AUTOMATICO', 'coins' => 15000, 'bonus_coins' => 5000, 'gems' => 60, 'price_eur' => 24.99, 'gives_premium_role' => true, 'badge' => 'RUOLO PREMIUM VIP', 'desc' => 'Diventi PREMIUM USER automatico + 20.000 ZeroCoins'],
        ['id' => 'pack_commander', 'name' => 'Commander Elite', 'coins' => 25000, 'bonus_coins' => 6000, 'gems' => 100, 'price_eur' => 34.99, 'badge' => '+25% BONUS', 'desc' => '31.000 ZeroCoins + 100 Gemme Cosmiche'],
        ['id' => 'pack_quantum', 'name' => 'Quantum Overlord', 'coins' => 40000, 'bonus_coins' => 12000, 'gems' => 180, 'price_eur' => 49.99, 'badge' => '+30% BONUS', 'desc' => '52.000 ZeroCoins + 180 Gemme Cosmiche'],
        ['id' => 'pack_pro_license_bundle', 'name' => 'LICENZA ARCADE PRO 30G', 'coins' => 30000, 'bonus_coins' => 10000, 'gems' => 150, 'price_eur' => 59.00, 'gives_premium_role' => true, 'gives_license_days' => 30, 'badge' => 'LICENZA 30 GIORNI', 'desc' => 'Conto alla rovescia 30 giorni + Ruolo VIP (€59 PayPal/Carta)'],
        ['id' => 'pack_cosmic_titan', 'name' => 'Cosmic Titan Vault', 'coins' => 75000, 'bonus_coins' => 25000, 'gems' => 350, 'price_eur' => 79.99, 'gives_premium_role' => true, 'badge' => 'MEGA PACK', 'desc' => '100.000 ZeroCoins + Status VIP Permanente'],
        ['id' => 'pack_supreme_founder', 'name' => 'Supreme Founder Syndicate', 'coins' => 200000, 'bonus_coins' => 80000, 'gems' => 1000, 'price_eur' => 149.99, 'gives_premium_role' => true, 'badge' => 'ULTRA VIP', 'desc' => '280.000 ZeroCoins + 1.000 Gemme + Status VIP'],
    ];
}

function find_coin_package(string $id): ?array {
    foreach (get_coin_packages() as $p) {
        if ($p['id'] === $id) return $p;
    }
    return null;
}

const LICENSE_PRICE_EUR = 59.00;
const LICENSE_DAYS      = 30;
