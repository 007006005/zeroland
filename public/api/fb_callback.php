<?php
// public/fb_callback.php
require_once 'fb_config.php';
require_once 'db.php';

if (isset($_GET['error'])) {
    header('Location: index.php?error=login_failed');
    exit;
}

if (isset($_GET['code'])) {
    $token_url = "https://graph.facebook.com/" . FB_GRAPH_VERSION . "/oauth/access_token?"
        . "client_id=" . FB_APP_ID . "&redirect_uri=" . urlencode(FB_REDIRECT_URI)
        . "&client_secret=" . FB_APP_SECRET . "&code=" . $_GET['code'];

    $response = @file_get_contents($token_url);
    $params = json_decode($response, true);

    if (isset($params['access_token'])) {
        $access_token = $params['access_token'];

        $graph_url = "https://graph.facebook.com/" . FB_GRAPH_VERSION . "/me?fields=id,name,email&access_token=" . $access_token;
        $user_data = json_decode(@file_get_contents($graph_url), true);

        if (isset($user_data['id'])) {
            $fb_id = $user_data['id'];
            $name = $user_data['name'];
            $email = isset($user_data['email']) ? $user_data['email'] : '';

            $stmt = $pdo->prepare("SELECT id FROM users WHERE fb_id = ?");
            $stmt->execute([$fb_id]);
            $user = $stmt->fetch();

            if ($user) {
                $internal_id = $user['id'];
            } else {
                $insert_stmt = $pdo->prepare("INSERT INTO users (fb_id, name, email, created_at) VALUES (?, ?, ?, NOW())");
                $insert_stmt->execute([$fb_id, $name, $email]);
                $internal_id = $pdo->lastInsertId();
            }

            $_SESSION['user_id'] = $internal_id;
            $_SESSION['user_name'] = $name;

            header('Location: index.php');
            exit;
        }
    }
}

header('Location: index.php?error=invalid_token');
exit;
?>