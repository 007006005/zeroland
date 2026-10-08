<?php
// Configurazione ambiente ZeroLegend Agar. I segreti arrivano da Railway.
$requiredEnv = static function (string $key): string {
  $value = getenv($key);
  if (!is_string($value) || $value === '') {
    throw new RuntimeException('Missing required environment variable: ' . $key);
  }
  return $value;
};

$origins = getenv('ALLOWED_ORIGINS') ?: 'https://zerothelegend.com,https://www.zerothelegend.com,https://games.zerothelegend.com';

return [
  'db_host' => $requiredEnv('DB_HOST'),
  'db_name' => $requiredEnv('DB_NAME'),
  'db_user' => $requiredEnv('DB_USER'),
  'db_pass' => $requiredEnv('DB_PASS'),
  'auth_secret' => $requiredEnv('AUTH_SECRET'),
  'server_key'  => $requiredEnv('SERVER_KEY'),
  'token_ttl' => 60 * 60 * 24 * 30,
  'allowed_origins' => array_values(array_filter(array_map('trim', explode(',', $origins)))),
];
