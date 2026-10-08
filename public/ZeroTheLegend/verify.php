<?php
// Percorso: https://zerothelegend.com/ZeroTheLegend/verify.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = isset($_POST['user_id']) ? $_POST['user_id'] : '';

    // ==========================================
    // PANNELLO DI CONTROLLO
    // ==========================================
    
    // Cambia in "inactive" per DISATTIVARE lo script a TUTTI.
    // Cambia in "active" per ATTIVARE lo script a TUTTI.
    $global_status = "active"; 

    // Inserisci qui gli ID degli utenti a cui vuoi bloccare l'accesso singolarmente.
    $banned_users = [
        "ztl_utente_da_bloccare",
    ];
    // ==========================================

    // 1. Controllo permessi
    if ($global_status === "inactive" || in_array($user_id, $banned_users)) {
        // Blocco attivato (globale o utente bannato): non restituisce il codice
        echo json_encode(["status" => "inactive"]);
        exit;
    }

    // 2. Lettura del file con il codice dello script principale
    // Il file main_script.js deve essere nella stessa cartella di verify.php sul server
    $script_path = __DIR__ . '/main_script.js';
    
    if (file_exists($script_path)) {
        $script_code = file_get_contents($script_path);
        
        // Invia il permesso "active" e tutto il codice del tuo script
        echo json_encode([
            "status" => "active", 
            "code" => $script_code
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "File main_script.js mancante sul server."]);
    }

} else {
    echo json_encode(["status" => "error", "message" => "Metodo non consentito."]);
}
?>