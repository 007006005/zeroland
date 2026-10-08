<?php
require __DIR__ . '/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('Metodo non consentito', 405);

$b = json_body();
$u = trim((string)($b['username'] ?? ''));
$p = (string)($b['password'] ?? '');

if (!preg_match('/^[A-Za-z0-9_]{3,16}$/', $u)) fail('Username: 3-16 caratteri, solo lettere, numeri e _');
if (strlen($p) < 8 || strlen($p) > 72) fail('La password deve avere da 8 a 72 caratteri');

rate_check('reg', 5, 60);
rate_hit('reg');

try {
  $pdo = db();
  $pdo->beginTransaction();
  $pdo->prepare('INSERT INTO agar_users (username, password_hash) VALUES (?, ?)')
      ->execute([$u, password_hash($p, PASSWORD_DEFAULT)]);
  $id = (int)$pdo->lastInsertId();
  $pdo->prepare('INSERT INTO agar_stats (user_id) VALUES (?)')->execute([$id]);
  $pdo->commit();
} catch (PDOException $e) {
  if (db()->inTransaction()) db()->rollBack();
  if ($e->getCode() === '23000') fail('Username già in uso', 409);
  fail('Errore del server', 500);
}

out(['ok' => true, 'token' => make_token($id, $u), 'user' => ['name' => $u, 'xp' => 0, 'level' => 1]]);
