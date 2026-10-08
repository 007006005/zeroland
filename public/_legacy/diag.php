<?php
/** Diagnostica temporanea Retro Arcade. Mettilo accanto a config.php, aprilo con ?key=INSTALL_KEY, poi CANCELLALO. */
require_once __DIR__ . '/config.php';
header('Content-Type: text/plain; charset=utf-8');
if (!defined('INSTALL_KEY') || INSTALL_KEY === 'CAMBIAMI-con-una-stringa-lunga-casuale'
    || !hash_equals((string)INSTALL_KEY, (string)($_GET['key'] ?? ''))) {
    http_response_code(403);
    exit("Accesso negato: serve ?key=LA_TUA_INSTALL_KEY (quella in config.php)\n");
}
echo "PHP " . PHP_VERSION . "\n";
try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Throwable $e) {
    exit('CONNESSIONE FALLITA: ' . $e->getMessage() . "\n");
}
echo 'Connessione OK al database ' . DB_NAME . ' - server ' . $pdo->query('SELECT VERSION()')->fetchColumn() . "\n\n";

$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
echo 'Tabelle presenti (' . count($tables) . '): ' . ($tables ? implode(', ', $tables) : 'NESSUNA') . "\n";

$schema = @file_get_contents(__DIR__ . '/schema.sql') ?: '';
preg_match_all('/CREATE TABLE IF NOT EXISTS (\w+)/', $schema, $m);
$missing = array_diff($m[1] ?? [], $tables);
echo 'Tabelle mancanti: ' . ($missing ? implode(', ', $missing) : 'nessuna') . "\n\n";

if (in_array('users', $tables, true)) {
    $have = $pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_COLUMN);
    preg_match('/CREATE TABLE IF NOT EXISTS users \((.*?)PRIMARY KEY/s', $schema, $mm);
    preg_match_all('/^\s+(\w+) [A-Z]/m', $mm[1] ?? '', $cols);
    $need = $cols[1] ?? [];
    echo 'Colonne di users: ' . implode(', ', $have) . "\n";
    echo 'Colonne attese mancanti: ' . (array_diff($need, $have) ? implode(', ', array_diff($need, $have)) : 'nessuna') . "\n\n";
}

echo "Test inserimento (annullato subito):\n";
try {
    $pdo->beginTransaction();
    $now = date('Y-m-d H:i:s');
    $pdo->prepare('INSERT INTO users (id, username, email, password_hash, role, avatar, coins, gems, dust, xp, level, streak, clan,
        referral_code, referred_by, is_premium_trial, trial_expires_at, last_login, last_seen, created_at, profile_json)
        VALUES (?,?,?,?,?,?,?,15,50,50,1,1,?,?,?,?,?,?,?,?,?)')
        ->execute(['usr_diagtest', 'diagtest', null, 'x', 'user', '🚀', 500, 'ZERO', 'DIAG-1', null, 0, null, $now, $now, $now, '{}']);
    echo "OK, l'inserimento funziona.\n";
    $pdo->rollBack();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo 'ERRORE: ' . $e->getMessage() . "\n";
}
