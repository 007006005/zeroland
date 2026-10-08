<?php
require_once __DIR__ . '/api/db.php';
$u = require_login_page();
$pdo = db();
$p = $u['profile'] ?? $u;
$stats = $u['stats'] ?? ($p['stats'] ?? []);
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$rk = $pdo->prepare('SELECT game_id, score FROM leaderboards WHERE user_id = ? ORDER BY game_id');
$rk->execute([$u['id'] ?? '']);
$ord = $pdo->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 10');
$ord->execute([$u['id'] ?? '']);
$staff = in_array($u['role'] ?? '', ['moderatore', 'admin', 'founder'], true);
?><!DOCTYPE html><html lang="it"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>La mia dashboard</title>
<style>body{font-family:system-ui,sans-serif;background:#0b1020;color:#e2e8f0;max-width:900px;margin:0 auto;padding:20px}a{color:#22d3ee}h1,h2{color:#22d3ee}
.card{display:inline-block;background:#111a33;border-radius:10px;padding:14px 18px;margin:0 8px 8px 0;min-width:110px}.card b{display:block;font-size:22px;color:#22d3ee}
table{border-collapse:collapse;width:100%}td,th{padding:7px;border-bottom:1px solid #1e293b;text-align:left}.bar{background:#1e293b;border-radius:6px;height:10px}.bar i{display:block;height:10px;background:#06b6d4;border-radius:6px}</style><link rel="stylesheet" href="assets/css/surreal.css?v=1">
</head><body>
<h1><?= $h($u['avatar'] ?? '👾') ?> <?= $h($u['username']) ?> <small style="font-size:14px;color:#94a3b8"><?= $h($u['role']) ?> · <?= $h($u['rank_title'] ?? '') ?></small></h1>
<p><a href="index.php">← Arcade</a> · <a href="zeroagar.php">ZeroAgar</a><?= $staff ? ' · <a href="admin-panel.php">Pannello Admin</a>' : '' ?> · <a href="#" onclick="fetch('api/logout.php',{method:'POST'}).then(()=>location='login.php');return false">Esci</a></p>
<?php foreach (['Monete' => 'coins', 'Gemme' => 'gems', 'Polvere' => 'dust', 'XP' => 'xp', 'Livello' => 'level', 'Streak' => 'streak'] as $l => $k): ?>
<div class="card"><?= $l ?><b><?= $h(number_format((int)($u[$k] ?? 0), 0, ',', '.')) ?></b></div><?php endforeach; ?>
<h2>Statistiche</h2>
<p>Partite giocate: <b><?= (int)($stats['games_played'] ?? 0) ?></b> · Uccisioni: <b><?= (int)($stats['total_kills'] ?? 0) ?></b> · Monete guadagnate: <b><?= (int)($stats['total_coins_earned'] ?? 0) ?></b></p>
<h2>Migliori punteggi</h2>
<table><?php foreach (($stats['highscores'] ?? []) as $g => $s): ?><tr><td><?= $h($g) ?></td><td><?= (int)$s ?></td></tr><?php endforeach; ?></table>
<h2>Posizioni in classifica</h2>
<table><?php foreach ($rk->fetchAll() as $r): ?><tr><td><?= $h($r['game_id']) ?></td><td><?= (int)$r['score'] ?></td></tr><?php endforeach; ?></table>
<h2>Ultimi acquisti</h2>
<table><?php foreach ($ord->fetchAll() as $o): ?><tr><td><?= $h($o['item_id']) ?></td><td><?= $h($o['created_at'] ?? '') ?></td></tr><?php endforeach; ?></table>
<h2>Invita amici</h2><p>Codice referral: <b><?= $h($u['referral_code'] ?? '') ?></b> · amici invitati: <?= (int)($u['referral_count'] ?? 0) ?></p>
</body></html>
