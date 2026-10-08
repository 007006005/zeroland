<?php
require __DIR__ . '/bootstrap.php';

$rows = db()->query('SELECT u.username, s.xp, s.level, s.kills, s.best_mass
                     FROM agar_stats s JOIN agar_users u ON u.id = s.user_id
                     ORDER BY s.xp DESC LIMIT 20')->fetchAll();
out(['ok' => true, 'top' => array_map(fn($r) => [
  'name' => $r['username'], 'xp' => (int)$r['xp'], 'level' => (int)$r['level'],
  'kills' => (int)$r['kills'], 'best_mass' => (int)$r['best_mass'],
], $rows)]);
