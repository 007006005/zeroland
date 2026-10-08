<?php
/** Caricamento skin personale (immagine quadrata mostrata sulla tua cella). */
require_once __DIR__ . '/db.php';
$cur = require_login_api();
enforce_same_origin();
$f = $_FILES['skin'] ?? null;
if (!$f || $f['error'] !== UPLOAD_ERR_OK) json_response(['success' => false, 'error' => 'Nessun file ricevuto'], 400);
if ($f['size'] > 10 * 1024 * 1024) json_response(['success' => false, 'error' => 'Immagine troppo grande (max 10 MB)'], 400);
$info = @getimagesize($f['tmp_name']);
$map = [IMAGETYPE_PNG => 'png', IMAGETYPE_JPEG => 'jpg', IMAGETYPE_WEBP => 'webp', IMAGETYPE_GIF => 'gif'];
if (!$info || !isset($map[$info[2]]) || $info[0] > 1024 || $info[1] > 1024) {
    json_response(['success' => false, 'error' => 'Formato non valido: usa PNG, JPG, WEBP o GIF fino a 1024x1024'], 400);
}
$dir = __DIR__ . '/../uploads/skins';
if (!is_dir($dir)) mkdir($dir, 0755, true);
if (!is_file($dir . '/.htaccess')) {
    file_put_contents($dir . '/.htaccess', "<FilesMatch \"\\.(php|phtml|phar|html?)$\">\n  Require all denied\n</FilesMatch>\nOptions -ExecCGI\n");
}
foreach ($map as $e) @unlink($dir . '/' . $cur['id'] . '.' . $e);
if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $cur['id'] . '.' . $map[$info[2]])) {
    json_response(['success' => false, 'error' => 'Salvataggio non riuscito'], 500);
}
json_response(['success' => true, 'message' => 'Skin caricata!']);
