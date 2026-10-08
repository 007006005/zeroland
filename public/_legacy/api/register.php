<?php
require_once __DIR__ . '/db.php';

$in = read_json_post();
$username = trim((string)($in['username'] ?? ''));
$password = (string)($in['password'] ?? '');
$email    = trim((string)($in['email'] ?? ''));
$avatar   = (string)($in['avatar'] ?? '🚀');
$refInput = strtoupper(trim((string)($in['referral_code'] ?? '')));

if (!preg_match('/^[A-Za-z0-9_]{3,20}$/', $username)) {
    json_response(['success' => false, 'error' => 'Il nome utente deve avere 3-20 caratteri: lettere, numeri o underscore'], 400);
}
if (strlen($password) < 8 || strlen($password) > 200) {
    json_response(['success' => false, 'error' => 'La password deve avere almeno 8 caratteri'], 400);
}
if ($email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190)) {
    json_response(['success' => false, 'error' => 'Indirizzo email non valido'], 400);
}
if (!in_array($avatar, ARCADE_AVATARS, true)) $avatar = '🚀';
if (!preg_match('/^[A-Z0-9\-]{0,30}$/', $refInput)) $refInput = '';

$pdo = db();
try {
    $pdo->beginTransaction();

    // Unicità (la UNIQUE del database resta come ultima barriera)
    $chk = $pdo->prepare('SELECT 1 FROM users WHERE username = ?');
    $chk->execute([$username]);
    if ($chk->fetchColumn()) {
        json_response(['success' => false, 'error' => 'Questo nome utente è già registrato'], 409);
    }
    if ($email !== '') {
        $chk = $pdo->prepare('SELECT 1 FROM users WHERE email = ?');
        $chk->execute([$email]);
        if ($chk->fetchColumn()) {
            json_response(['success' => false, 'error' => 'Questa email è già registrata'], 409);
        }
    }

    // Il primo account (quando non esiste ancora nessun Founder) diventa Founder.
    // Nessun altro può ottenere ruoli staff registrandosi, qualunque nome utente scelga.
    $founders = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'founder' FOR UPDATE")->fetchColumn();
    $isFirst = ($founders === 0);
    $role = $isFirst ? 'founder' : 'premium';
    $trial = !$isFirst;

    $coins = 500;
    $referredBy = null;
    $bonusMsg = '';
    if ($refInput !== '') {
        $ist = $pdo->prepare('SELECT * FROM users WHERE referral_code = ? FOR UPDATE');
        $ist->execute([$refInput]);
        $irow = $ist->fetch();
        if ($irow) {
            $inviter = user_from_row($irow);
            $inviter['coins'] += 500;
            $inviter['referral_count'] += 1;
            $inviter['referral_coins_earned'] += 500;
            array_unshift($inviter['referrals_history'], ['username' => $username, 'date' => date('c'), 'bonus' => 500]);
            $inviter['referrals_history'] = array_slice($inviter['referrals_history'], 0, 50);
            save_user($inviter);
            $coins += 500;
            $referredBy = $inviter['username'];
            $bonusMsg = " (+500 ZeroCoins bonus referral da {$inviter['username']}!)";
        }
    }

    // Codice referral univoco
    $base = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $username), 0, 5));
    do {
        $refCode = 'ARCADE-' . $base . '-' . random_int(100, 9999);
        $ex = $pdo->prepare('SELECT 1 FROM users WHERE referral_code = ?');
        $ex->execute([$refCode]);
    } while ($ex->fetchColumn());

    $id = 'usr_' . bin2hex(random_bytes(6));
    $now = date('Y-m-d H:i:s');
    $profile = default_profile();
    $profile['premium_source'] = $trial ? 'trial' : null;

    $pdo->prepare(
        'INSERT INTO users (id, username, email, password_hash, role, avatar, coins, gems, dust, xp, level, streak, clan,
                            referral_code, referred_by, is_premium_trial, trial_expires_at, last_login, last_seen,
                            created_at, profile_json)
         VALUES (?, ?, ?, ?, ?, ?, ?, 15, 50, 50, 1, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    )->execute([
        $id, $username, $email !== '' ? $email : null, password_hash($password, PASSWORD_DEFAULT),
        $role, $avatar, $coins, 'ZERO', $refCode, $referredBy,
        $trial ? 1 : 0, $trial ? date('Y-m-d H:i:s', time() + 7 * 86400) : null,
        $now, $now, $now, json_encode($profile, JSON_UNESCAPED_UNICODE),
    ]);

    $pdo->commit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if ($e->getCode() === '23000') {
        json_response(['success' => false, 'error' => 'Nome utente o email già registrati'], 409);
    }
    json_response(['success' => false, 'error' => 'Errore database durante la registrazione'], 500);
}

start_user_session($id);
$st = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$st->execute([$id]);

json_response([
    'success' => true,
    'message' => $isFirst
        ? 'Account Founder creato con successo!'
        : 'Account creato con successo! Free Trial Premium 7 giorni attiva!' . $bonusMsg,
    'user'    => user_from_row($st->fetch()),
]);
