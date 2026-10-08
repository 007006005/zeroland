<?php
require_once __DIR__ . '/db.php';

$user = require_login_api();
$store = load_db_store($user);

json_response([
    'success'              => true,
    'user'                 => $user,
    'shop_items'           => $store['shop_items'],
    'achievements_catalog' => $store['achievements'],
    'leaderboards'         => $store['leaderboards'],
    'daily_challenges'     => $store['daily_challenges'],
]);
