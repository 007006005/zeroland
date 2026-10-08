<?php
/**
 * API ENDPOINT PER PANNELLO ADMIN IN-GAME (AJAX)
 */
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/game_logic.php';

$user = require_login_page();

// Verifica privilegi Amministratore
if (empty($user['is_admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accesso Negato: Privilegi insufficienti.']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add_coins':
        $amount = intval($_POST['amount'] ?? 1000);
        $user['coins'] += $amount;
        save_user_to_db($user);
        echo json_encode(['success' => true, 'message' => "+{$amount} Monete aggiunte!", 'new_coins' => $user['coins']]);
        break;

    case 'add_xp':
        $amount = intval($_POST['amount'] ?? 500);
        $user['xp'] += $amount;
        $user['level'] = max(1, floor(pow($user['xp'] / 100, 0.6)) + 1);
        save_user_to_db($user);
        echo json_encode(['success' => true, 'message' => "+{$amount} XP aggiunti!", 'new_xp' => $user['xp'], 'new_level' => $user['level']]);
        break;

    case 'god_mode':
        $user['admin_god_mode'] = !($user['admin_god_mode'] ?? false);
        save_user_to_db($user);
        $status = $user['admin_god_mode'] ? 'ATTIVATA' : 'DISATTIVATA';
        echo json_encode(['success' => true, 'message' => "God Mode {$status}!", 'god_mode' => $user['admin_god_mode']]);
        break;

    case 'unlock_all_items':
        $user['unlocked_items'] = ['skin_gold', 'skin_plasma', 'skin_cyber', 'ship_titan', 'sound_chiptune'];
        save_user_to_db($user);
        echo json_encode(['success' => true, 'message' => 'Tutti gli oggetti del catalogo sbloccati!']);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Azione non valida.']);
        break;
}