<?php
require_once __DIR__ . '/db.php';

$in = read_json_post();
$username = trim((string)($in['username'] ?? ''));
$password = (string)($in['password'] ?? '');

if ($username === '' || $password === '') {
    json_response(['success' => false, 'error' => 'Inserisci nome utente e password'], 400);
}
if (strlen($username) > 40 || strlen($password) > 200) {
    json_response(['success' => false, 'error' => 'Dati non validi'], 400);
}

$pdo = db();
$ip = client_ip();
$userKey = strtolower(substr($username, 0, 20));

// Anti brute-force: max 5 tentativi falliti per utente/IP in 15 minuti
$pdo->prepare('DELETE FROM login_attempts WHERE attempted_at < ?')->execute([date('Y-m-d H:i:s', time() - 900)]);
$cnt = $pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND username = ?');
$cnt->execute([$ip, $userKey]);
$cntIp = $pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip = ?');
$cntIp->execute([$ip]);
if ((int)$cnt->fetchColumn() >= 5 || (int)$cntIp->fetchColumn() >= 30) {
    json_response(['success' => false, 'error' => 'Troppi tentativi falliti. Riprova tra 15 minuti.'], 429);
}

$st = $pdo->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
$st->execute([$username]);
$row = $st->fetch();

// Hash fittizio: tempo di risposta simile anche se l'utente non esiste
$dummy = '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';
$ok = $row ? password_verify($password, $row['password_hash']) : (password_verify($password, $dummy) && false);

if (!$ok) {
    $pdo->prepare('INSERT INTO login_attempts (ip, username, attempted_at) VALUES (?, ?, ?)')
        ->execute([$ip, $userKey, date('Y-m-d H:i:s')]);
    json_response(['success' => false, 'error' => 'Nome utente o password non corretti'], 401);
}

if (!empty($row['is_banned'])) {
    $reason = $row['ban_reason'] ?: "Accesso sospeso dall'amministrazione";
    json_response(['success' => false, 'error' => 'Account bannato: ' . $reason], 403);
}

$pdo->prepare('DELETE FROM login_attempts WHERE ip = ? AND username = ?')->execute([$ip, $userKey]);

if (password_needs_rehash($row['password_hash'], PASSWORD_DEFAULT)) {
    $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
        ->execute([password_hash($password, PASSWORD_DEFAULT), $row['id']]);
}
$now = date('Y-m-d H:i:s');
$pdo->prepare('UPDATE users SET last_login = ?, last_seen = ? WHERE id = ?')->execute([$now, $now, $row['id']]);

start_user_session($row['id']);

$st = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$st->execute([$row['id']]);
$user = refresh_entitlements(user_from_row($st->fetch()));

json_response([
    'success' => true,
    'message' => 'Login effettuato con successo',
    'user'    => $user,
]);
