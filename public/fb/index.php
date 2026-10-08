<?php
/**
 * fb/index.php
 *
 * Pagina "Accedi con Facebook" per Zero Arcade.
 * Ottiene l'accessToken dall'SDK Facebook e lo invia a /auth/facebook.php.
 * Se l'utente è già loggato, rimanda direttamente al portale.
 */
session_start();

if (!empty($_SESSION['user_id'])) {
    header('Location: /portal/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Accedi con Facebook — Zero Arcade</title>
<style>
  * { box-sizing: border-box; }
  body {
    margin: 0;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #0d0d1a;
    font-family: 'Courier New', monospace;
    color: #f2f2f2;
  }
  .box {
    background: #15152b;
    border: 2px solid #3a3a6a;
    border-radius: 10px;
    padding: 40px 36px;
    text-align: center;
    width: 100%;
    max-width: 360px;
    box-shadow: 0 0 24px rgba(80, 80, 200, 0.35);
  }
  .box h1 {
    font-size: 20px;
    letter-spacing: 1px;
    margin: 0 0 8px;
    color: #ffd166;
  }
  .box p {
    font-size: 13px;
    color: #9a9ac0;
    margin: 0 0 28px;
  }
  #fb-login-btn {
    width: 100%;
    padding: 14px 18px;
    font-family: inherit;
    font-size: 14px;
    font-weight: bold;
    letter-spacing: 0.5px;
    color: #fff;
    background: #1877f2;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: transform 0.1s ease, background 0.2s ease;
  }
  #fb-login-btn:hover { background: #166fe0; }
  #fb-login-btn:active { transform: scale(0.98); }
  #fb-login-btn:disabled { opacity: 0.6; cursor: not-allowed; }
  #fb-status {
    margin-top: 16px;
    font-size: 12px;
    min-height: 16px;
    color: #ff6b6b;
  }
  .back-link {
    display: inline-block;
    margin-top: 24px;
    font-size: 12px;
    color: #6a6aa0;
    text-decoration: none;
  }
  .back-link:hover { color: #9a9ac0; }
</style>
<script>
  function setStatus(msg, isError) {
    const el = document.getElementById('fb-status');
    if (!el) return;
    el.textContent = msg || '';
    el.style.color = isError ? '#ff6b6b' : '#6bffb0';
  }
</script>
</head>
<body>

<div class="box">
  <h1>ZERO ARCADE</h1>
  <p>Accedi con il tuo account Facebook per continuare</p>

  <div id="fb-root"></div>
  <button id="fb-login-btn" onclick="loginFacebook()" type="button" disabled>
    Caricamento...
  </button>
  <div id="fb-status"></div>

  <a class="back-link" href="/login.php">Torna al login classico</a>
</div>

<script>
  let fbReady = false;

  window.fbAsyncInit = function() {
    FB.init({
      appId: '1630163095187615',
      cookie: true,
      xfbml: false,
      version: 'v21.0'
    });
    fbReady = true;
    const btn = document.getElementById('fb-login-btn');
    btn.disabled = false;
    btn.textContent = 'Accedi con Facebook';
  };

  setTimeout(function() {
    if (!fbReady) {
      setStatus('Impossibile caricare Facebook. Disattiva eventuali ad-blocker e ricarica la pagina.', true);
    }
  }, 6000);
</script>
<script async defer src="https://connect.facebook.net/it_IT/sdk.js"
  onerror="setStatus('Impossibile caricare lo script di Facebook. Controlla la connessione o un eventuale ad-blocker.', true)">
</script>

<script>
function loginFacebook() {
  if (!fbReady || typeof FB === 'undefined') {
    setStatus('Facebook non è ancora pronto, attendi un istante e riprova.', true);
    return;
  }

  const btn = document.getElementById('fb-login-btn');
  btn.disabled = true;
  setStatus('Attendi...', false);

  FB.login(function(response) {
    if (response.authResponse) {
      const accessToken = response.authResponse.accessToken;

      fetch('/auth/facebook.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ accessToken: accessToken })
      })
      .then(function(res) { return res.json(); })
      .then(function(data) {
        if (data.success) {
          setStatus('Accesso riuscito, reindirizzamento...', false);
          window.location.href = '/portal/index.php';
        } else {
          setStatus(data.error || 'Accesso fallito', true);
          btn.disabled = false;
        }
      })
      .catch(function() {
        setStatus('Errore di rete, riprova.', true);
        btn.disabled = false;
      });
    } else {
      setStatus('Accesso annullato.', true);
      btn.disabled = false;
    }
  }, { scope: 'public_profile,email' });
}
</script>

</body>
</html>