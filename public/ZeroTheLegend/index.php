<?php
// ============================================================================
// ZeroExtens Bots PRO - Backend Amministratore (Cyberpunk Theme)
// ============================================================================
session_start();

// --- CONFIGURAZIONE ---
$admin_password = 'admin'; // Cambia questa password prima di mettere online!
$data_file = 'auth_data.json'; // File dove vengono salvati i token autorizzati

// Inizializza il file dati se non esiste
if (!file_exists($data_file)) {
    file_put_contents($data_file, json_encode(['tokens' => []]));
}

// ----------------------------------------------------------------------------
// 1. GESTIONE API PER LO SCRIPT TAMPERMONKEY
// ----------------------------------------------------------------------------
if (isset($_GET['api']) && $_GET['api'] == 'check') {
    header('Access-Control-Allow-Origin: *'); // Permette le richieste dallo script
    header('Content-Type: application/json');
    
    $token = isset($_GET['token']) ? trim($_GET['token']) : '';
    $data = json_decode(file_get_contents($data_file), true);
    
    if (in_array($token, $data['tokens'])) {
        echo json_encode([
            'status' => 'success',
            'authorized' => true,
            'message' => 'Accesso consentito a ZeroExtens PRO.'
        ]);
    } else {
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'authorized' => false,
            'message' => 'Token non valido, accesso negato.'
        ]);
    }
    exit;
}

// ----------------------------------------------------------------------------
// 2. GESTIONE LOGIN AMMINISTRATORE
// ----------------------------------------------------------------------------
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    if ($_POST['password'] === $admin_password) {
        $_SESSION['admin_logged'] = true;
        header("Location: index.php");
        exit;
    } else {
        $error = "Accesso Negato: Password Errata.";
    }
}

// ----------------------------------------------------------------------------
// 3. GESTIONE DASHBOARD AMMINISTRATORE
// ----------------------------------------------------------------------------
if (isset($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true) {
    $data = json_decode(file_get_contents($data_file), true);

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['add_token']) && !empty($_POST['new_token'])) {
            $new = trim($_POST['new_token']);
            if (!in_array($new, $data['tokens'])) {
                $data['tokens'][] = $new;
                file_put_contents($data_file, json_encode($data));
            }
        } elseif (isset($_POST['remove_token'])) {
            $remove = trim($_POST['token_to_remove']);
            $data['tokens'] = array_values(array_filter($data['tokens'], function($t) use ($remove) {
                return $t !== $remove;
            }));
            file_put_contents($data_file, json_encode($data));
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZeroExtens PRO - Admin Terminal</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap');
        
        :root {
            --bg-color: #050505;
            --neon-cyan: #00f3ff;
            --neon-blue: #003cff;
            --danger-red: #ff003c;
            --text-color: #e0e0e0;
        }

        body {
            background-color: var(--bg-color);
            color: var(--neon-cyan);
            font-family: 'Share Tech Mono', monospace;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-image: 
                linear-gradient(rgba(0, 243, 255, 0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 243, 255, 0.05) 1px, transparent 1px);
            background-size: 20px 20px;
        }

        .container {
            background: rgba(10, 10, 10, 0.85);
            border: 1px solid var(--neon-cyan);
            box-shadow: 0 0 15px rgba(0, 243, 255, 0.2), inset 0 0 15px rgba(0, 243, 255, 0.1);
            padding: 30px;
            border-radius: 8px;
            width: 100%;
            max-width: 500px;
            text-shadow: 0 0 5px rgba(0, 243, 255, 0.5);
            position: relative;
            overflow: hidden;
        }

        .container::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 3px;
            background: var(--neon-cyan);
            box-shadow: 0 0 10px var(--neon-cyan);
        }

        h1, h2 {
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 20px;
        }

        h1 { font-size: 24px; color: var(--text-color); }
        h1 span { color: var(--neon-cyan); }

        .form-group {
            margin-bottom: 20px;
        }

        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 10px;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #333;
            color: var(--neon-cyan);
            font-family: 'Share Tech Mono', monospace;
            box-sizing: border-box;
            border-radius: 4px;
            outline: none;
            transition: all 0.3s ease;
        }

        input[type="text"]:focus, input[type="password"]:focus {
            border-color: var(--neon-cyan);
            box-shadow: 0 0 8px rgba(0, 243, 255, 0.4);
        }

        button {
            width: 100%;
            padding: 10px;
            background: transparent;
            color: var(--neon-cyan);
            border: 1px solid var(--neon-cyan);
            font-family: 'Share Tech Mono', monospace;
            font-size: 16px;
            cursor: pointer;
            text-transform: uppercase;
            transition: all 0.3s ease;
            border-radius: 4px;
        }

        button:hover {
            background: var(--neon-cyan);
            color: var(--bg-color);
            box-shadow: 0 0 15px var(--neon-cyan);
        }

        .btn-danger {
            border-color: var(--danger-red);
            color: var(--danger-red);
        }
        .btn-danger:hover {
            background: var(--danger-red);
            color: white;
            box-shadow: 0 0 15px var(--danger-red);
        }

        .error {
            color: var(--danger-red);
            text-align: center;
            margin-bottom: 15px;
            text-shadow: 0 0 5px var(--danger-red);
        }

        .token-list {
            list-style: none;
            padding: 0;
            margin: 0 0 20px 0;
            max-height: 200px;
            overflow-y: auto;
        }

        .token-list li {
            background: rgba(0, 243, 255, 0.05);
            border: 1px solid rgba(0, 243, 255, 0.2);
            padding: 10px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 4px;
        }

        .token-list li form { margin: 0; }
        .token-list button { width: auto; padding: 5px 10px; font-size: 12px; }

        .api-info {
            background: rgba(0, 60, 255, 0.1);
            border-left: 3px solid var(--neon-blue);
            padding: 10px;
            font-size: 12px;
            color: #aaa;
            margin-bottom: 20px;
            word-wrap: break-word;
        }

        .logout {
            text-align: center;
            margin-top: 20px;
        }
        .logout a {
            color: #888;
            text-decoration: none;
            transition: 0.3s;
        }
        .logout a:hover { color: var(--danger-red); }

        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: var(--bg-color); }
        ::-webkit-scrollbar-thumb { background: var(--neon-cyan); border-radius: 3px; }
    </style>
</head>
<body>

<div class="container">
    <h1>ZeroExtens <span>PRO</span> Terminal</h1>

    <?php if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true): ?>
        
        <h2>Autenticazione Richiesta</h2>
        <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <input type="password" name="password" placeholder="Inserisci Password di Sistema" required autocomplete="off">
            </div>
            <button type="submit" name="login">Inizia Sessione</button>
        </form>

    <?php else: ?>

        <h2>Gestore Autorizzazioni</h2>
        
        <div class="api-info">
            <strong>Endpoint API da inserire nello script:</strong><br>
            <span style="color: var(--neon-cyan);">https://<?php echo $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']; ?>?api=check&token=YOUR_TOKEN</span>
        </div>

        <form method="POST" action="" style="margin-bottom: 20px; display: flex; gap: 10px;">
            <input type="text" name="new_token" placeholder="Genera/Inserisci nuovo Token HWID" required>
            <button type="submit" name="add_token" style="width: auto; padding: 10px 20px;">Aggiungi</button>
        </form>

        <ul class="token-list">
            <?php 
            $current_data = json_decode(file_get_contents($data_file), true);
            if (empty($current_data['tokens'])): ?>
                <li style="justify-content: center; color: #888;">Nessun token autorizzato presente.</li>
            <?php else: ?>
                <?php foreach ($current_data['tokens'] as $t): ?>
                    <li>
                        <span><?= htmlspecialchars($t) ?></span>
                        <form method="POST" action="">
                            <input type="hidden" name="token_to_remove" value="<?= htmlspecialchars($t) ?>">
                            <button type="submit" name="remove_token" class="btn-danger">REVOCA</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>

        <div class="logout">
            <a href="?logout=1">[ Disconnetti Terminale ]</a>
        </div>

    <?php endif; ?>
</div>

</body>
</html>