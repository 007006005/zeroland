<?php
// includes/db.php
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

$host = env_or_default('DB_HOST', '127.0.0.1');
$port = (int) env_or_default('DB_PORT', '3306');
$dbname = env_or_default('DB_NAME', 'your_database_name');
$username = env_or_default('DB_USER', 'your_db_user');
$password = env_or_default('DB_PASS', 'your_db_password');

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('DB connection failed: ' . $e->getMessage());
    die(json_encode(['success' => false, 'error' => 'Errore di connessione al database.']));
}
?>