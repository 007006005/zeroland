<?php
/**
 * api/auth.php - Registrazione, login, logout, sessione.
 * POST JSON: {action: "register"|"login"|"logout"|"me", ...}
 */
require_once __DIR__ . '/db.php';

$in = read_json_post();
$action = (string)($in['action'] ?? ($_GET['action'] ?? ''));

if ($action === 'me') {
    $u = current_user();
    json_response(['success' => true, 'user' => $u ? user_public($u) : null]);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_response(['success' => false, 'error' => 'Metodo non consentito'], 405);
}
enforce_same_origin();
$pdo = db();

// ------------------------------------------------------------------ LOGOUT
if ($action === 'logout') {
    logout_session();
    json_response(['success' => true]);
}

// ------------------------------------------------------------------ REGISTRAZIONE
if ($action === 'register') {
    $username = trim((string)($in['username'] ?? ''));
    $email    = trim((string)($in['email'] ?? ''));
    $password = (string)($in['password'] ?? '');
    $confirm  = (string)($in['confirm_password'] ?? $password);

    if (!preg_match('/^[A-Za-z0-9_]{3,20}$/', $username)) {
        json_response(['success' => false, 'error' => 'Username: 3-20 caratteri tra lettere, numeri e underscore.'], 400);
    }
    // Il portale storico usa username + PIN; l'email non viene richiesta dalla UI.
    // Se manca, generiamo un indirizzo tecnico non usabile per posta.
    if ($email === '') {
        $email = strtolower($username) . '@users.zerothelegend.invalid';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        json_response(['success' => false, 'error' => 'Email non valida.'], 400);
    }
    // Accettiamo sia le password classiche (>=8) sia il PIN storico 4-6 cifre del portale.
    if (!((strlen($password) >= 8 && strlen($password) <= 200) || preg_match('/^\d{4,6}$/', $password))) {
        json_response(['success' => false, 'error' => 'Password/PIN non valido.'], 400);
    }
    if ($password !== $confirm) {
        json_response(['success' => false, 'error' => 'Le password non coincidono.'], 400);
    }
    if (rate_limited('register', '', 5, 3600)) {
        json_response(['success' => false, 'error' => 'Troppe registrazioni da questo indirizzo. Riprova più tardi.'], 429);
    }

    $chk = $pdo->prepare('SELECT username, email FROM users WHERE username = ? OR email = ? LIMIT 1');
    $chk->execute([$username, $email]);
    if ($row = $chk->fetch()) {
        $msg = (strcasecmp((string)$row['username'], $username) === 0) ? 'Username già in uso.' : 'Email già registrata.';
        json_response(['success' => false, 'error' => $msg], 409);
    }

    // Il ruolo staff NON dipende dall'username: solo il primissimo account del portale diventa founder.
    // Altri amministratori si nominano con install.php (protetto da INSTALL_KEY) o dal pannello admin.
    $isFirst = ((int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0);
    $id   = 'usr_' . bin2hex(random_bytes(6));
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $now  = date('Y-m-d H:i:s');
    $cols = table_columns('users');

    $vals = [
        'id' => $id, 'username' => $username, 'email' => $email,
        'password_hash' => $hash,
        'role' => $isFirst ? 'founder' : 'user',
        'coins' => 500, 'gems' => 15, 'xp' => 0, 'level' => 1, 'clan' => 'ZERO',
        'profile_json' => json_encode(['avatar' => '😎', 'bio' => '', 'unlocked_skins' => ['default'], 'equipped_skin' => 'default'], JSON_UNESCAPED_UNICODE),
        'is_banned' => 0, 'daily_streak' => 0,
        'last_login' => $now, 'last_seen' => $now, 'created_at' => $now,
    ];
    if (isset($cols['password'])) $vals['password'] = $hash; // schemi vecchi con colonna "password"

    try {
        insert_row('users', $vals);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            json_response(['success' => false, 'error' => 'Username o email già in uso.'], 409);
        }
        error_log('register: ' . $e->getMessage());
        json_response(['success' => false, 'error' => 'Errore durante la registrazione.'], 500);
    }
    record_attempt('register', '');

    login_session($id);
    $u = current_user(true);
    json_response(['success' => true, 'message' => 'Registrazione completata!', 'user' => $u ? user_public($u) : null]);
}

// ------------------------------------------------------------------ LOGIN
if ($action === 'login') {
    $ident    = trim((string)($in['username'] ?? ''));
    $password = (string)($in['password'] ?? '');
    if ($ident === '' || $password === '') {
        json_response(['success' => false, 'error' => 'Inserisci username (o email) e password/PIN.'], 400);
    }
    if (strlen($ident) > 190 || strlen($password) > 200) {
        json_response(['success' => false, 'error' => 'Dati non validi.'], 400);
    }
    $key = strtolower(substr($ident, 0, 60));
    if (rate_limited('login', $key, 5, 900) || rate_limited('login', '*', 30, 900)) {
        json_response(['success' => false, 'error' => 'Troppi tentativi falliti. Riprova tra 15 minuti.'], 429);
    }

    $st = $pdo->prepare('SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1');
    $st->execute([$ident, $ident]);
    $row = $st->fetch();

    if ($row) {
        $ok = password_verify($password, user_hash($row));
    } else {
        password_hash($password, PASSWORD_DEFAULT); // tempo di risposta simile se l'utente non esiste
        $ok = false;
    }

    if (!$ok) {
        record_attempt('login', $key);
        record_attempt('login', '*');
        json_response(['success' => false, 'error' => 'Credenziali o PIN non corretti.'], 401);
    }
    if (!empty($row['is_banned'])) {
        json_response(['success' => false, 'error' => 'Account bannato: ' . ($row['ban_reason'] ?: 'violazione del regolamento.')], 403);
    }

    $pdo->prepare('DELETE FROM auth_attempts WHERE ip = ? AND kind = ? AND ident = ?')->execute([client_ip(), 'login', $key]);

    $now = date('Y-m-d H:i:s');
    $pdo->prepare('UPDATE users SET last_login = ?, last_seen = ? WHERE id = ?')->execute([$now, $now, $row['id']]);
    // Aggiorna l'hash se l'algoritmo è cambiato e allinea la colonna password_hash
    if (password_needs_rehash(user_hash($row), PASSWORD_DEFAULT) || empty($row['password_hash'])) {
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $row['id']]);
    }

    login_session($row['id']);
    $u = current_user(true);
    json_response(['success' => true, 'message' => 'Accesso effettuato.', 'user' => $u ? user_public($u) : null]);
}

json_response(['success' => false, 'error' => 'Azione non valida.'], 400);
