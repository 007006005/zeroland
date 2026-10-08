<?php
/** 
 * api/admin.php - API Staff & Admin Backend
 */
require_once __DIR__ . '/db.php';

$cur = require_login_api();
$rankMap = array_flip(ARCADE_ROLES);
$myRole = $cur['role'] ?? 'user';
$myRank = $rankMap[$myRole] ?? 0;

if ($myRank < ($rankMap['moderatore'] ?? 3)) {
    json_response(['success' => false, 'error' => 'Permesso negato. Accesso riservato allo staff.'], 403);
}

$in = read_json_post();
$action = (string)($in['action'] ?? '');
$pdo = db();

$need = function (string $requiredRole) use ($myRank, $rankMap) {
    $requiredRank = $rankMap[$requiredRole] ?? 99;
    if ($myRank < $requiredRank) {
        json_response(['success' => false, 'error' => "Azione riservata al ruolo $requiredRole o superiore."], 403);
    }
};

$ok = fn($d = []) => json_response(['success' => true] + $d);

$target = function () use ($pdo, $in, $cur, $rankMap, $myRank) {
    $targetId = (string)($in['user_id'] ?? '');
    if (!$targetId) json_response(['success' => false, 'error' => 'ID utente mancante'], 400);

    $s = $pdo->prepare('SELECT id, username, role FROM users WHERE id = ?');
    $s->execute([$targetId]);
    $t = $s->fetch();
    if (!$t) json_response(['success' => false, 'error' => 'Utente non trovato'], 404);

    $targetRank = $rankMap[$t['role']] ?? 0;
    
    if ($t['id'] !== $cur['id'] && $targetRank >= $myRank && $myRank < ($rankMap['founder'] ?? 5)) {
        json_response(['success' => false, 'error' => 'Non puoi eseguire azioni su un utente di rango pari o superiore al tuo.'], 403);
    }
    return $t;
};

$numVal = fn($k, $max = 10000000) => max(-$max, min($max, (int)($in[$k] ?? 0)));

$logAudit = function($actionName, $targetUsername, $details = '') use ($pdo, $cur) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS admin_audit_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id VARCHAR(24) NOT NULL,
            admin_username VARCHAR(50) NOT NULL,
            action VARCHAR(50) NOT NULL,
            target_username VARCHAR(50) NULL,
            details TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $pdo->prepare("INSERT INTO admin_audit_logs (admin_id, admin_username, action, target_username, details) VALUES (?, ?, ?, ?, ?)")
            ->execute([$cur['id'], $cur['username'], $actionName, $targetUsername, $details]);
    } catch (Exception $e) {}
};

switch ($action) {
case 'stats':
    $q = fn($sql) => (int)$pdo->query($sql)->fetchColumn();
    $ok(['stats' => [
        'Utenti Totali' => $q('SELECT COUNT(*) FROM users'),
        'Nuovi Oggi' => $q("SELECT COUNT(*) FROM users WHERE created_at >= CURDATE()"),
        'Attivi 24h' => $q("SELECT COUNT(*) FROM users WHERE last_seen >= NOW() - INTERVAL 1 DAY"),
        'Utenti Bannati' => $q('SELECT COUNT(*) FROM users WHERE is_banned = 1'),
        'Account Premium/Pro' => $q('SELECT COUNT(*) FROM users WHERE has_pro_license = 1 OR is_premium_trial = 1'),
        'Monete Totali in Circolo' => $q('SELECT COALESCE(SUM(coins),0) FROM users'),
        'Ordini Effettuati' => $q('SELECT COUNT(*) FROM orders'),
        'Messaggi Chat' => $q('SELECT COUNT(*) FROM chat_messages'),
    ]]);

case 'list_users':
    $searchTerm = trim((string)($in['q'] ?? ''));
    $like = '%' . addcslashes($searchTerm, '%_') . '%';
    $s = $pdo->prepare('SELECT id, username, email, role, coins, gems, xp, level, is_banned, ban_reason, last_seen, created_at FROM users
        WHERE username LIKE ? OR email LIKE ? ORDER BY created_at DESC LIMIT 100');
    $s->execute([$like, $like]);
    $ok(['users' => $s->fetchAll(), 'my_role' => $cur['role']]);

case 'set_ban':
    $t = $target();
    if ($t['id'] === $cur['id']) json_response(['success' => false, 'error' => 'Non puoi bannare il tuo stesso account.'], 400);
    $bannedStatus = !empty($in['banned']) ? 1 : 0;
    $reason = $bannedStatus ? mb_substr(trim((string)($in['reason'] ?? 'Violazione regolamento')), 0, 200) : null;

    $pdo->prepare('UPDATE users SET is_banned = ?, ban_reason = ? WHERE id = ?')
        ->execute([$bannedStatus, $reason, $t['id']]);

    $logAudit($bannedStatus ? 'BAN_USER' : 'UNBAN_USER', $t['username'], $reason);
    $ok(['message' => $bannedStatus ? "Utente {$t['username']} bannato." : "Utente {$t['username']} riammesso."]);

case 'update_user':
    $need('founder');
    $t = $target();
    $newRole = (string)($in['role'] ?? '');
    if (!in_array($newRole, ARCADE_ROLES, true)) json_response(['success' => false, 'error' => 'Ruolo selezionato non valido.'], 400);
    if ($t['id'] === $cur['id'] && $newRole !== 'founder') {
        json_response(['success' => false, 'error' => 'Non puoi rimuovere a te stesso il ruolo Founder.'], 400);
    }
    $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$newRole, $t['id']]);
    $logAudit('UPDATE_ROLE', $t['username'], "Ruolo cambiato a: $newRole");
    $ok(['message' => "Ruolo dell'utente {$t['username']} aggiornato a: $newRole"]);

case 'grant':
    $need('admin');
    $t = $target();
    $c = $numVal('coins');
    $g = $numVal('gems');
    $x = $numVal('xp');
    $pdo->prepare('UPDATE users SET coins = GREATEST(0, coins + ?), gems = GREATEST(0, gems + ?), xp = GREATEST(0, xp + ?) WHERE id = ?')
        ->execute([$c, $g, $x, $t['id']]);
    $logAudit('GRANT_BONUS', $t['username'], "Coins: $c, Gems: $g, XP: $x");
    $ok(['message' => "Bonus applicato con successo all'utente {$t['username']}"]);

case 'reset_progress':
    $need('admin');
    $t = $target();
    $pdo->prepare("UPDATE users SET coins=500, gems=15, dust=50, xp=50, level=1, prestige=0, streak=1, profile_json=? WHERE id = ?")
        ->execute([json_encode(default_profile(), JSON_UNESCAPED_UNICODE), $t['id']]);
    $pdo->prepare('DELETE FROM leaderboards WHERE user_id = ?')->execute([$t['id']]);
    $logAudit('RESET_PROGRESS', $t['username']);
    $ok(['message' => "Progressi di {$t['username']} azzerati."]);

case 'delete_user':
    $need('founder');
    $t = $target();
    if ($t['id'] === $cur['id']) json_response(['success' => false, 'error' => 'Non puoi eliminare te stesso.'], 400);
    foreach (['leaderboards', 'chat_messages', 'orders'] as $tb) {
        $pdo->prepare("DELETE FROM $tb WHERE user_id = ?")->execute([$t['id']]);
    }
    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$t['id']]);
    $logAudit('DELETE_USER', $t['username']);
    $ok(['message' => "Account {$t['username']} eliminato definitivamente."]);

case 'broadcast_global':
    $need('moderatore');
    $msgText = mb_substr(trim((string)($in['message'] ?? '')), 0, 250);
    if (!$msgText) json_response(['success' => false, 'error' => 'Il messaggio non può essere vuoto.'], 400);

    $now = (int)(microtime(true) * 1000);
    try {
        $pdo->prepare("INSERT INTO chat_messages (user_id, username, text, type, created_at) VALUES (?, ?, ?, 'system', NOW())")
            ->execute([$cur['id'], 'SISTEMA / ' . $cur['username'], $msgText]);
    } catch (Exception $e) {}

    try {
        $pdo->prepare("INSERT INTO arena_chat (room, user_id, name, text, ts) VALUES ('ffa', ?, ?, ?, ?)")
            ->execute([$cur['id'], '📢 ANNUNCIO', $msgText, $now]);
    } catch (Exception $e) {}

    $logAudit('BROADCAST_MSG', null, $msgText);
    $ok(['message' => 'Annuncio globale pubblicato nella chat di sistema!']);

case 'audit_logs':
    $need('admin');
    try {
        $logs = $pdo->query('SELECT * FROM admin_audit_logs ORDER BY id DESC LIMIT 100')->fetchAll();
        $ok(['rows' => $logs]);
    } catch (Exception $e) {
        $ok(['rows' => []]);
    }

case 'chat_list':
    $ok(['rows' => $pdo->query('SELECT id, username, text, created_at FROM chat_messages ORDER BY id DESC LIMIT 100')->fetchAll()]);

case 'chat_delete':
    $need('moderatore');
    $msgId = (int)($in['id'] ?? 0);
    $pdo->prepare('DELETE FROM chat_messages WHERE id = ?')->execute([$msgId]);
    $ok(['message' => 'Messaggio eliminato']);

case 'chat_clear':
    $need('admin');
    $pdo->exec("DELETE FROM chat_messages WHERE type <> 'system'");
    $logAudit('CLEAR_CHAT', null);
    $ok(['message' => 'Chat svuotata con successo.']);

case 'shop_list':
    $need('admin');
    $ok(['rows' => $pdo->query('SELECT id, name, type, price FROM shop_items ORDER BY sort_order')->fetchAll()]);

case 'shop_update':
    $need('admin');
    $pdo->prepare('UPDATE shop_items SET price = ?, name = ? WHERE id = ?')
        ->execute([max(0, $numVal('price')), mb_substr(trim((string)($in['name'] ?? '')), 0, 80), (string)($in['id'] ?? '')]);
    $ok(['message' => 'Articolo aggiornato']);

case 'orders_list':
    $need('admin');
    $ok(['rows' => $pdo->query('SELECT o.*, u.username FROM orders o LEFT JOIN users u ON u.id = o.user_id ORDER BY o.id DESC LIMIT 100')->fetchAll()]);

case 'leaderboard_reset':
    $need('admin');
    $pdo->prepare('DELETE FROM leaderboards WHERE game_id = ? AND user_id IS NOT NULL')->execute([(string)($in['game_id'] ?? '')]);
    $ok(['message' => 'Classifica azzerata']);
}

json_response(['success' => false, 'error' => 'Azione non valida o non riconosciuta'], 400);