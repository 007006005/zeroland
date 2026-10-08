<?php
/** Gestione staff: solo il Founder può cambiare ruoli o bannare. */
require_once __DIR__ . '/db.php';

$cur = require_login_api();
$in = read_json_post();

if ($cur['role'] !== 'founder') {
    json_response(['success' => false, 'error' => 'Permesso negato: solo il Founder Supremo può modificare i ruoli'], 403);
}

$action = (string)($in['action'] ?? '');
$targetId = (string)($in['user_id'] ?? '');
$st = db()->prepare('SELECT id, username, role FROM users WHERE id = ?');
$st->execute([$targetId]);
$target = $st->fetch();
if (!$target) json_response(['success' => false, 'error' => 'Utente non trovato'], 404);

if ($action === 'update_user') {
    $role = (string)($in['role'] ?? '');
    if (!in_array($role, ARCADE_ROLES, true)) json_response(['success' => false, 'error' => 'Ruolo non valido'], 400);
    if ($target['id'] === $cur['id'] && $role !== 'founder') {
        json_response(['success' => false, 'error' => 'Non puoi togliere a te stesso il ruolo Founder'], 400);
    }
    db()->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $target['id']]);
    json_response(['success' => true, 'message' => "Ruolo di {$target['username']} aggiornato a $role"]);
}

if ($action === 'set_ban') {
    if ($target['id'] === $cur['id']) json_response(['success' => false, 'error' => 'Non puoi bannare te stesso'], 400);
    $banned = !empty($in['banned']) ? 1 : 0;
    $reason = $banned ? mb_substr(trim((string)($in['reason'] ?? '')), 0, 200) : null;
    db()->prepare('UPDATE users SET is_banned = ?, ban_reason = ? WHERE id = ?')->execute([$banned, $reason, $target['id']]);
    json_response(['success' => true, 'message' => $banned ? "{$target['username']} bannato" : "{$target['username']} riammesso"]);
}

json_response(['success' => false, 'error' => 'Azione non valida'], 400);
