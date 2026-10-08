<?php
/**
 * Configurazione ambientale per il progetto.
 * Non memorizzare password o secret nel repository: usare variabili d'ambiente.
 */
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

define('INSTALL_KEY', env_or_default('INSTALL_KEY', 'change-me-install-key'));
define('PAYMENTS_MODE', env_or_default('PAYMENTS_MODE', 'demo'));

date_default_timezone_set('Europe/Rome');
