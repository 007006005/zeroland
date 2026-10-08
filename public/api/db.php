<?php
/**
 * db.php — Connessione al database MySQL.
 * I valori sensibili vanno letti dalle variabili d'ambiente, non salvati nel codice.
 */
$allowedOrigins = array_values(array_filter(array_map(
    'trim',
    explode(',', getenv('ALLOWED_ORIGINS') ?: 'https://zerothelegend.com,https://www.zerothelegend.com,https://games.zerothelegend.com')
)));
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '' && in_array($origin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Vary: Origin');
}
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code($origin === '' || in_array($origin, $allowedOrigins, true) ? 204 : 403);
    exit;
}

function env_or_default(string $key, string $default): string {
    $value = getenv($key);
    if (is_string($value) && $value !== '') {
        return $value;
    }

    $value = $_ENV[$key] ?? null;
    if (is_string($value) && $value !== '') {
        return $value;
    }

    return $default;
}

define('DB_HOST', env_or_default('DB_HOST', '127.0.0.1'));
define('DB_PORT', (int) env_or_default('DB_PORT', '3306'));
define('DB_NAME', env_or_default('DB_NAME', 'your_database_name'));
define('DB_USER', env_or_default('DB_USER', 'your_db_user'));
define('DB_PASS', env_or_default('DB_PASS', 'your_db_password'));

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => true,
        'cookie_samesite' => 'Lax',
    ]);
}

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    error_log('DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    die('Errore di connessione al database');
}

function db(): PDO
{
    global $pdo;
    return $pdo;
}

function read_json_post(): array
{
    $raw = file_get_contents('php://input');
    if (!is_string($raw) || trim($raw) === '') {
        return $_POST;
    }

    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function json_response(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function enforce_same_origin(): void
{
    global $allowedOrigins;
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    if ($origin === '' || !in_array($origin, $allowedOrigins, true)) {
        json_response(['success' => false, 'error' => 'Origine della richiesta non consentita.'], 403);
    }
}

function client_ip(): string
{
    $address = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    return filter_var($address, FILTER_VALIDATE_IP) ? $address : 'unknown';
}

function ensure_auth_attempts_table(): void
{
    static $initialized = false;
    if ($initialized) {
        return;
    }

    db()->exec(
        'CREATE TABLE IF NOT EXISTS auth_attempts (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            ip VARCHAR(45) NOT NULL,
            kind VARCHAR(20) NOT NULL,
            ident VARCHAR(190) NOT NULL DEFAULT \'\',
            attempted_at DATETIME NOT NULL,
            KEY idx_auth_attempts_lookup (ip, kind, ident, attempted_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
    $initialized = true;
}

function rate_limited(string $kind, string $ident, int $limit, int $windowSeconds): bool
{
    ensure_auth_attempts_table();
    $cutoff = date('Y-m-d H:i:s', time() - max(1, $windowSeconds));
    $statement = db()->prepare(
        'SELECT COUNT(*) FROM auth_attempts
         WHERE ip = ? AND kind = ? AND ident = ? AND attempted_at >= ?'
    );
    $statement->execute([client_ip(), $kind, $ident, $cutoff]);

    return (int)$statement->fetchColumn() >= max(1, $limit);
}

function record_attempt(string $kind, string $ident): void
{
    ensure_auth_attempts_table();
    db()->prepare(
        'INSERT INTO auth_attempts (ip, kind, ident, attempted_at) VALUES (?, ?, ?, NOW())'
    )->execute([client_ip(), $kind, $ident]);
}

function table_columns(string $table): array
{
    if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $table)) {
        throw new InvalidArgumentException('Nome tabella non valido.');
    }

    $statement = db()->query('SHOW COLUMNS FROM `' . $table . '`');
    $columns = [];
    foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $column) {
        if (isset($column['Field'])) {
            $columns[(string)$column['Field']] = true;
        }
    }

    return $columns;
}

function insert_row(string $table, array $values): void
{
    if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $table) || $values === []) {
        throw new InvalidArgumentException('Dati per l’inserimento non validi.');
    }

    $columns = array_keys($values);
    foreach ($columns as $column) {
        if (!is_string($column) || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $column)) {
            throw new InvalidArgumentException('Nome colonna non valido.');
        }
    }

    $quotedColumns = array_map(static fn(string $column): string => '`' . $column . '`', $columns);
    $placeholders = implode(',', array_fill(0, count($columns), '?'));
    $statement = db()->prepare(
        'INSERT INTO `' . $table . '` (' . implode(',', $quotedColumns) . ') VALUES (' . $placeholders . ')'
    );
    $statement->execute(array_values($values));
}

function user_hash(array $user): string
{
    return (string)($user['password_hash'] ?? $user['password'] ?? '');
}

function login_session(string $userId): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    unset($_SESSION['user']);
}

function logout_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }
    session_destroy();
}

function current_user(bool $refresh = false): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $userId = (string)($_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? '');
    if ($userId === '') {
        return null;
    }

    if (!$refresh && isset($_SESSION['user']) && is_array($_SESSION['user'])) {
        $cached = $_SESSION['user'];
        if (!empty($cached['id']) && empty($cached['is_banned'])) {
            return $cached;
        }
    }

    $statement = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $statement->execute([$userId]);
    $user = $statement->fetch(PDO::FETCH_ASSOC);
    if (!is_array($user) || !empty($user['is_banned'])) {
        unset($_SESSION['user'], $_SESSION['user_id']);
        return null;
    }

    $profile = json_decode((string)($user['profile_json'] ?? ''), true);
    $user['profile'] = is_array($profile) ? $profile : [];
    $_SESSION['user'] = $user;
    $_SESSION['user_id'] = (string)$user['id'];

    return $user;
}

function user_public(array $user): array
{
    $profile = $user['profile'] ?? [];
    if (!is_array($profile)) {
        $profile = [];
    }

    foreach (['password', 'password_hash', 'pin', 'serverAuth', 'loggedIn', 'provider'] as $privateKey) {
        unset($profile[$privateKey]);
    }

    return [
        'id' => (string)($user['id'] ?? ''),
        'username' => (string)($user['username'] ?? ''),
        'email' => (string)($user['email'] ?? ''),
        'role' => (string)($user['role'] ?? 'user'),
        'coins' => (int)($user['coins'] ?? 0),
        'gems' => (int)($user['gems'] ?? 0),
        'xp' => (int)($user['xp'] ?? 0),
        'level' => (int)($user['level'] ?? 1),
        'clan' => (string)($user['clan'] ?? 'ZERO'),
        'profile' => $profile,
    ];
}

function require_login_api(): array
{
    $user = current_user(true);
    if ($user === null) {
        json_response(['success' => false, 'error' => 'Sessione non valida o scaduta. Effettua nuovamente l’accesso.'], 401);
    }

    return $user;
}
