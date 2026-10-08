<?php
/** api/logout.php - Disconnessione (accetta anche GET per i vecchi link) */
require_once __DIR__ . '/db.php';
logout_session();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') json_response(['success' => true]);
header('Location: ../index.php');
exit;
