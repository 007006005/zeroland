<?php
require __DIR__ . '/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('Metodo non consentito', 405);

$b = json_body();
$u = trim((string)($b['username'] ?? ''));
$p = (string)($b['password'] ?? '');

rate_check('login', 10, 15);

$st = db()->prepare('SELECT u.id, u.username, u.password_hash, s.xp, s.level
                     FROM agar_users u LEFT JOIN agar_stats s ON s.user_id = u.id
                     WHERE u.username = ?');
$st->execute([$u]);
$row = $st->fetch();

// password_verify anche se l'utente non esiste, per non rivelare quali username esistono
$hash = $row['password_hash'] ?? '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';
$ok = password_verify($p, $hash) && $row;
if (!$ok) { rate_hit('login'); fail('Username o password errati', 401); }

if (password_needs_rehash($row['password_hash'], PASSWORD_DEFAULT)) {
  db()->prepare('UPDATE agar_users SET password_hash=? WHERE id=?')->execute([password_hash($p, PASSWORD_DEFAULT), $row['id']]);
}
db()->prepare('UPDATE agar_users SET last_login=NOW() WHERE id=?')->execute([$row['id']]);

out(['ok' => true,
     'token' => make_token((int)$row['id'], $row['username']),
     'user' => ['name' => $row['username'], 'xp' => (int)$row['xp'], 'level' => (int)$row['level']]]);
