<?php
/**
 * Legacy DB config: read values from environment so secrets are not committed.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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

define('ARCADE_ROLES', ['user', 'premium', 'helper', 'moderatore', 'admin', 'founder']);

function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $host = DB_HOST;
    $port = DB_PORT;
    $dbname = DB_NAME;
    $user = DB_USER;
    $pass = DB_PASS;

    try {
        $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        $sqlitePath = __DIR__ . '/database.sqlite';
        $pdo = new PDO("sqlite:$sqlitePath", null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    init_db_schema($pdo);
    return $pdo;
}

function init_db_schema(PDO $pdo): void {
    static $initialized = false;
    if ($initialized) return;
    $initialized = true;

    $isSqlite = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';

    if ($isSqlite) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id TEXT PRIMARY KEY,
            username TEXT UNIQUE NOT NULL,
            email TEXT,
            password TEXT NOT NULL,
            role TEXT DEFAULT 'user',
            coins INTEGER DEFAULT 500,
            gems INTEGER DEFAULT 15,
            dust INTEGER DEFAULT 50,
            xp INTEGER DEFAULT 50,
            level INTEGER DEFAULT 1,
            prestige INTEGER DEFAULT 0,
            streak INTEGER DEFAULT 1,
            has_pro_license INTEGER DEFAULT 0,
            is_premium_trial INTEGER DEFAULT 0,
            is_banned INTEGER DEFAULT 0,
            ban_reason TEXT,
            profile_json TEXT,
            last_seen TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS chat_messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id TEXT NOT NULL,
            username TEXT NOT NULL,
            text TEXT NOT NULL,
            type TEXT DEFAULT 'user',
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS shop_items (
            id TEXT PRIMARY KEY,
            name TEXT NOT NULL,
            type TEXT NOT NULL,
            price INTEGER NOT NULL,
            sort_order INTEGER DEFAULT 0
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id TEXT NOT NULL,
            item_id TEXT NOT NULL,
            amount INTEGER DEFAULT 1,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS leaderboards (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            game_id TEXT NOT NULL,
            user_id TEXT,
            username TEXT NOT NULL,
            score INTEGER NOT NULL,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )");
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id VARCHAR(24) PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) NULL,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(20) NOT NULL DEFAULT 'user',
            coins INT NOT NULL DEFAULT 500,
            gems INT NOT NULL DEFAULT 15,
            dust INT NOT NULL DEFAULT 50,
            xp INT NOT NULL DEFAULT 50,
            level INT NOT NULL DEFAULT 1,
            prestige INT NOT NULL DEFAULT 0,
            streak INT NOT NULL DEFAULT 1,
            has_pro_license TINYINT(1) NOT NULL DEFAULT 0,
            is_premium_trial TINYINT(1) NOT NULL DEFAULT 0,
            is_banned TINYINT(1) NOT NULL DEFAULT 0,
            ban_reason VARCHAR(200) NULL,
            profile_json TEXT NULL,
            last_seen DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS chat_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(24) NOT NULL,
            username VARCHAR(50) NOT NULL,
            text VARCHAR(255) NOT NULL,
            type VARCHAR(20) DEFAULT 'user',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS shop_items (
            id VARCHAR(50) PRIMARY KEY,
            name VARCHAR(80) NOT NULL,
            type VARCHAR(30) NOT NULL,
            price INT NOT NULL DEFAULT 0,
            sort_order INT NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(24) NOT NULL,
            item_id VARCHAR(50) NOT NULL,
            amount INT NOT NULL DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS leaderboards (
            id INT AUTO_INCREMENT PRIMARY KEY,
            game_id VARCHAR(40) NOT NULL,
            user_id VARCHAR(24) NULL,
            username VARCHAR(50) NOT NULL,
            score INT NOT NULL,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
}

function read_json_post(): array {
    $raw = file_get_contents('php://input');
    if (!$raw) return $_POST ?: [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function json_response(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function require_login_api(): array {
    if (empty($_SESSION['user']) || empty($_SESSION['user']['id'])) {
        json_response(['success' => false, 'error' => 'Sessione non valida o scaduta. Effettua il login.'], 401);
    }
    
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id, username, email, role, is_banned, ban_reason FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user']['id']]);
    $user = $stmt->fetch();

    if (!$user) {
        unset($_SESSION['user']);
        json_response(['success' => false, 'error' => 'Account utente non trovato.'], 401);
    }

    if (!empty($user['is_banned'])) {
        json_response(['success' => false, 'error' => 'Sei stato bannato: ' . ($user['ban_reason'] ?: 'Violazione del regolamento.')], 403);
    }

    $pdo->prepare('UPDATE users SET last_seen = NOW() WHERE id = ?')->execute([$user['id']]);

    $_SESSION['user'] = $user;
    return $user;
}

function require_login_page(): array {
    if (empty($_SESSION['user']) || empty($_SESSION['user']['id'])) {
        header('Location: index.php?login_required=1');
        exit;
    }

    $pdo = db();
    $stmt = $pdo->prepare('SELECT id, username, email, role, is_banned, ban_reason FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user']['id']]);
    $user = $stmt->fetch();

    if (!$user || !empty($user['is_banned'])) {
        session_destroy();
        header('Location: index.php?banned=1');
        exit;
    }

    $_SESSION['user'] = $user;
    return $user;
}

function default_profile(): array {
    return [
        'avatar' => '😎',
        'bio' => 'Giocatore Arcade',
        'unlocked_skins' => ['default'],
        'equipped_skin' => 'default'
    ];
}