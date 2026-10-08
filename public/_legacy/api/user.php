<?php
/**
 * api/user.php - API Gestione Profilo Utente
 */
require_once __DIR__ . '/db.php';

$cur = require_login_api();
$in = read_json_post();
$action = (string)($in['action'] ?? '');
$pdo = db();

if ($action === 'update_profile') {
    $bio = mb_substr(trim((string)($in['bio'] ?? '')), 0, 200);
    $equippedSkin = (string)($in['equipped_skin'] ?? 'default');

    $stmt = $pdo->prepare('SELECT profile_json FROM users WHERE id = ?');
    $stmt->execute([$cur['id']]);
    $rawProf = $stmt->fetchColumn();
    $profile = json_decode($rawProf ?: '', true) ?: default_profile();

    $unlocked = $profile['unlocked_skins'] ?? ['default'];
    if (!in_array($equippedSkin, $unlocked, true)) {
        $equippedSkin = 'default';
    }

    $profile['bio'] = $bio;
    $profile['equipped_skin'] = $equippedSkin;

    $stmt = $pdo->prepare('UPDATE users SET profile_json = ? WHERE id = ?');
    $stmt->execute([json_encode($profile, JSON_UNESCAPED_UNICODE), $cur['id']]);

    json_response(['success' => true, 'message' => 'Profilo aggiornato con successo.']);
}

json_response(['success' => false, 'error' => 'Azione non valida'], 400);