<?php
require __DIR__ . '/bootstrap.php';

$t = read_token(bearer());
if (!$t) fail('Non autenticato', 401);

$st = db()->prepare('SELECT u.username, s.xp, s.level, s.games, s.kills, s.best_mass, s.total_seconds
                     FROM agar_users u JOIN agar_stats s ON s.user_id = u.id WHERE u.id = ?');
$st->execute([$t['uid']]);
$r = $st->fetch();
if (!$r) fail('Utente non trovato', 401);

out(['ok' => true, 'user' => [
  'name' => $r['username'], 'xp' => (int)$r['xp'], 'level' => (int)$r['level'],
  'games' => (int)$r['games'], 'kills' => (int)$r['kills'],
  'best_mass' => (int)$r['best_mass'], 'total_seconds' => (int)$r['total_seconds'],
]]);
