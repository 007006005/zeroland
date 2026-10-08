<?php
// Chiamato SOLO dal server di gioco su Railway (server-to-server), mai dal browser.
// Il client non può inviare statistiche: il server di gioco le calcola e le firma.
require __DIR__ . '/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('Metodo non consentito', 405);

$raw = file_get_contents('php://input') ?: '';
if (strlen($raw) > 2048) fail('Richiesta troppo grande', 413);

$sig = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
$exp = hash_hmac('sha256', $raw, $CFG['server_key']);
if (!hash_equals($exp, $sig)) fail('Firma non valida', 403);

$d = json_decode($raw, true);
if (!is_array($d)) fail('JSON non valido');
if (abs(time() - (int)($d['ts'] ?? 0)) > 300) fail('Richiesta scaduta', 403);

$uid  = (int)($d['user_id'] ?? 0);
$suid = (string)($d['session_uid'] ?? '');
if ($uid <= 0 || !preg_match('/^[a-f0-9]{32}$/', $suid)) fail('Dati non validi');

// Limiti di sicurezza: valori fuori scala vengono ridotti, non accettati
$kills = max(0, min((int)($d['kills'] ?? 0), 200));
$mass  = max(0, min((int)($d['max_mass'] ?? 0), 25000));
$secs  = max(0, min((int)($d['seconds'] ?? 0), 6 * 3600));

// Sessioni troppo brevi e senza uccisioni non danno nulla (evita spam di respawn)
if ($secs < 5 && $kills === 0) out(['ok' => true, 'skipped' => true]);

$xp = $kills * 50 + intdiv($mass, 10) + intdiv($secs, 10);

$pdo = db();
try {
  $pdo->beginTransaction();
  $ins = $pdo->prepare('INSERT IGNORE INTO agar_sessions (session_uid, user_id, kills, max_mass, seconds, xp_gained)
                        SELECT ?, id, ?, ?, ?, ? FROM agar_users WHERE id = ?');
  $ins->execute([$suid, $kills, $mass, $secs, $xp, $uid]);
  if ($ins->rowCount() === 0) { $pdo->rollBack(); out(['ok' => true, 'duplicate_or_unknown_user' => true]); }

  $pdo->prepare('UPDATE agar_stats SET xp = xp + ?, games = games + 1, kills = kills + ?,
                 best_mass = GREATEST(best_mass, ?), total_seconds = total_seconds + ? WHERE user_id = ?')
      ->execute([$xp, $kills, $mass, $secs, $uid]);
  $cur = (int)$pdo->query('SELECT xp FROM agar_stats WHERE user_id = ' . $uid)->fetchColumn();
  $pdo->prepare('UPDATE agar_stats SET level = ? WHERE user_id = ?')->execute([level_from_xp($cur), $uid]);
  $pdo->commit();
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  fail('Errore del server', 500);
}
out(['ok' => true, 'xp_gained' => $xp]);
