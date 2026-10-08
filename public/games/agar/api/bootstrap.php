<?php
declare(strict_types=1);

$CFG = require __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

// ---- CORS (nessun cookie: il token viaggia nel body / header Authorization)
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '' && in_array($origin, $CFG['allowed_origins'], true)) {
  header('Access-Control-Allow-Origin: ' . $origin);
  header('Access-Control-Allow-Credentials: true');
  header('Vary: Origin');
  header('Access-Control-Allow-Headers: Content-Type, Authorization');
  header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
}
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') { http_response_code(204); exit; }

function out(array $data, int $code = 200): void {
  http_response_code($code);
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}
function fail(string $msg, int $code = 400): void { out(['ok' => false, 'error' => $msg], $code); }

function db(): PDO {
  global $CFG;
  static $pdo = null;
  if ($pdo === null) {
    try {
      $pdo = new PDO(
        "mysql:host={$CFG['db_host']};dbname={$CFG['db_name']};charset=utf8mb4",
        $CFG['db_user'], $CFG['db_pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
         PDO::ATTR_EMULATE_PREPARES => false]
      );
    } catch (Throwable $e) { fail('Database non disponibile', 500); }
  }
  return $pdo;
}

function json_body(): array {
  $raw = file_get_contents('php://input') ?: '';
  if (strlen($raw) > 4096) fail('Richiesta troppo grande', 413);
  $d = json_decode($raw, true);
  return is_array($d) ? $d : [];
}

// ---- Token firmato: base64url(payload).base64url(hmac)
function b64u(string $s): string { return rtrim(strtr(base64_encode($s), '+/', '-_'), '='); }
function b64u_dec(string $s): string { return (string)base64_decode(strtr($s, '-_', '+/')); }

function make_token(int $uid, string $name): string {
  global $CFG;
  $p = b64u(json_encode(['uid' => $uid, 'name' => $name, 'exp' => time() + $CFG['token_ttl']]));
  return $p . '.' . b64u(hash_hmac('sha256', $p, $CFG['auth_secret'], true));
}
function read_token(?string $tok): ?array {
  global $CFG;
  if (!$tok || substr_count($tok, '.') !== 1) return null;
  [$p, $sig] = explode('.', $tok, 2);
  $exp = b64u(hash_hmac('sha256', $p, $CFG['auth_secret'], true));
  if (!hash_equals($exp, $sig)) return null;
  $d = json_decode(b64u_dec($p), true);
  if (!is_array($d) || ($d['exp'] ?? 0) < time()) return null;
  return $d;
}
function bearer(): ?string {
  $h = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
  return preg_match('/^Bearer\s+(\S+)$/', $h, $m) ? $m[1] : null;
}

// ---- Rate limit: max $max tentativi di tipo $kind per IP in $minutes minuti
function rate_check(string $kind, int $max, int $minutes): void {
  $ip = $_SERVER['REMOTE_ADDR'] ?? '0';
  $st = db()->prepare('SELECT COUNT(*) c FROM agar_attempts WHERE ip=? AND kind=? AND created_at > (NOW() - INTERVAL ? MINUTE)');
  $st->execute([$ip, $kind, $minutes]);
  if ((int)$st->fetch()['c'] >= $max) fail('Troppi tentativi, riprova tra qualche minuto', 429);
}
function rate_hit(string $kind): void {
  db()->prepare('INSERT INTO agar_attempts (ip, kind) VALUES (?, ?)')->execute([$_SERVER['REMOTE_ADDR'] ?? '0', $kind]);
  if (random_int(1, 50) === 1) db()->exec('DELETE FROM agar_attempts WHERE created_at < (NOW() - INTERVAL 1 DAY)');
}

function level_from_xp(int $xp): int { return 1 + (int)floor(sqrt($xp / 100)); }
