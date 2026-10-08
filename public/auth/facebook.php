<?php
// auth/facebook.php
session_start();
header('Content-Type: application/json');

require_once '../includes/db.php';

// Ottieni il payload JSON inviato tramite fetch API
$input = json_decode(file_get_contents('php://input'), true);
$accessToken = $input['accessToken'] ?? '';

if (empty($accessToken)) {
    echo json_encode(['success' => false, 'error' => 'Token di accesso mancante.']);
    exit;
}

// Verifica il token interrogando direttamente l'API Graph di Facebook
$graphUrl = "https://graph.facebook.com/me?fields=id,name,email&access_token=" . urlencode($accessToken);

// Soppressione degli warning con @ per gestire gli errori manualmente
$response = @file_get_contents($graphUrl);

if ($response === false) {
    echo json_encode(['success' => false, 'error' => 'Impossibile convalidare il token con Meta.']);
    exit;
}

$userData = json_decode($response, true);

if (isset($userData['error'])) {
    echo json_encode(['success' => false, 'error' => 'Token non valido o scaduto.']);
    exit;
}

// Dati utente estratti con successo da Meta
$fb_id = $userData['id'];
$name = $userData['name'];
$email = $userData['email'] ?? '';

try {
    // Controllo se l'utente è già registrato nel database
    $stmt = $pdo->prepare("SELECT id FROM users WHERE fb_id = ?");
    $stmt->execute([$fb_id]);
    $user = $stmt->fetch();

    if ($user) {
        $internal_id = $user['id'];
    } else {
        // Registrazione nuovo utente
        $insert_stmt = $pdo->prepare("INSERT INTO users (fb_id, username, email) VALUES (?, ?, ?)");
        $insert_stmt->execute([$fb_id, $name, $email]);
        $internal_id = $pdo->lastInsertId();
    }

    // Inizializzazione Sessione
    $_SESSION['user_id'] = $internal_id;
    $_SESSION['user_name'] = $name;

    // Risposta di successo al frontend
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'DB Error: ' . $e->getMessage()]);
}
?>