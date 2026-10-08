<?php
require_once __DIR__ . '/api/db.php';

$allowedNext = ['index.php', 'zeroagar.php'];
$next = in_array($_GET['next'] ?? '', $allowedNext, true) ? $_GET['next'] : 'index.php';

if (current_user()) {
    header('Location: ' . $next);
    exit;
}
$tab = (($_GET['tab'] ?? '') === 'register' || !empty($_GET['ref'])) ? 'register' : 'login';
$ref = preg_match('/^[A-Za-z0-9\-]{1,30}$/', $_GET['ref'] ?? '') ? strtoupper($_GET['ref']) : '';
?><!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Retro Arcade · Accedi</title>
<style>
  *{box-sizing:border-box}
  body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;
       background:radial-gradient(circle at 20% 10%,#12214a,#070b18 60%);color:#e2e8f0;font-family:system-ui,Segoe UI,Roboto,sans-serif}
  .card{width:100%;max-width:420px;background:rgba(15,23,42,.92);border:1px solid rgba(34,211,238,.35);border-radius:22px;
        padding:28px;box-shadow:0 0 40px rgba(34,211,238,.12)}
  h1{margin:0 0 4px;font-size:24px;color:#22d3ee;letter-spacing:.5px}
  .sub{margin:0 0 20px;color:#94a3b8;font-size:13px}
  .tabs{display:grid;grid-template-columns:1fr 1fr;gap:6px;background:#0b1224;padding:4px;border-radius:12px;margin-bottom:18px}
  .tabs button{padding:9px;border:0;border-radius:9px;background:transparent;color:#94a3b8;font-weight:700;cursor:pointer}
  .tabs button.on{background:#0891b2;color:#fff}
  label{display:block;font-size:12px;color:#94a3b8;margin:12px 0 5px;font-weight:600}
  input[type=text],input[type=password],input[type=email]{width:100%;padding:11px 13px;border-radius:10px;border:1px solid #334155;
        background:#0b1224;color:#fff;font-size:14px;outline:none}
  input:focus{border-color:#22d3ee}
  .avatars{display:flex;flex-wrap:wrap;gap:6px}
  .avatars label{margin:0;cursor:pointer}
  .avatars input{display:none}
  .avatars span{display:flex;width:38px;height:38px;align-items:center;justify-content:center;font-size:20px;border-radius:10px;
        border:1px solid #334155;background:#0b1224}
  .avatars input:checked + span{border-color:#22d3ee;background:#083344}
  .go{width:100%;margin-top:18px;padding:12px;border:0;border-radius:12px;font-weight:800;letter-spacing:.5px;cursor:pointer;
      background:linear-gradient(90deg,#06b6d4,#3b82f6);color:#001018;font-size:14px}
  .go:disabled{opacity:.6;cursor:wait}
  .msg{margin-top:14px;padding:10px 12px;border-radius:10px;font-size:13px;display:none}
  .msg.err{display:block;background:#450a0a;border:1px solid #991b1b;color:#fecaca}
  .msg.ok{display:block;background:#052e1b;border:1px solid #166534;color:#bbf7d0}
  .hint{font-size:11px;color:#64748b;margin-top:4px}
  .hide{display:none}
</style>
<link rel="stylesheet" href="assets/css/surreal.css?v=1">
</head>
<body>
<div class="card">
  <h1>🕹️ RETRO ARCADE</h1>
  <p class="sub">Accedi per salvare punteggi, monete e progressi nel tuo database.</p>

  <div class="tabs">
    <button type="button" id="tab-login" class="<?= $tab === 'login' ? 'on' : '' ?>">Accedi</button>
    <button type="button" id="tab-register" class="<?= $tab === 'register' ? 'on' : '' ?>">Registrati</button>
  </div>

  <form id="form-login" class="<?= $tab === 'login' ? '' : 'hide' ?>" autocomplete="on">
    <label for="l-user">Nome utente</label>
    <input type="text" id="l-user" name="username" autocomplete="username" maxlength="20" required>
    <label for="l-pass">Password</label>
    <input type="password" id="l-pass" name="password" autocomplete="current-password" required>
    <button class="go" type="submit">ENTRA NELL'ARCADE</button>
  </form>

  <form id="form-register" class="<?= $tab === 'register' ? '' : 'hide' ?>" autocomplete="on">
    <label for="r-user">Nome utente</label>
    <input type="text" id="r-user" name="username" autocomplete="username" maxlength="20" pattern="[A-Za-z0-9_]{3,20}" required>
    <div class="hint">3-20 caratteri: lettere, numeri, underscore.</div>
    <label for="r-email">Email (facoltativa)</label>
    <input type="email" id="r-email" name="email" autocomplete="email" maxlength="190">
    <label for="r-pass">Password</label>
    <input type="password" id="r-pass" name="password" autocomplete="new-password" minlength="8" required>
    <div class="hint">Almeno 8 caratteri.</div>
    <label for="r-pass2">Ripeti password</label>
    <input type="password" id="r-pass2" autocomplete="new-password" minlength="8" required>
    <label>Avatar</label>
    <div class="avatars">
      <?php foreach (ARCADE_AVATARS as $i => $av): ?>
        <label><input type="radio" name="avatar" value="<?= $av ?>" <?= $i === 0 ? 'checked' : '' ?>><span><?= $av ?></span></label>
      <?php endforeach; ?>
    </div>
    <label for="r-ref">Codice referral (facoltativo, +500 ZeroCoins)</label>
    <input type="text" id="r-ref" name="referral_code" maxlength="30" value="<?= htmlspecialchars($ref) ?>">
    <button class="go" type="submit">CREA ACCOUNT</button>
  </form>

  <div id="msg" class="msg"></div>
</div>

<script>
const NEXT = <?= json_encode($next) ?>;
const msg = document.getElementById('msg');
function show(text, ok) { msg.textContent = text; msg.className = 'msg ' + (ok ? 'ok' : 'err'); }

function setTab(t) {
  document.getElementById('form-login').classList.toggle('hide', t !== 'login');
  document.getElementById('form-register').classList.toggle('hide', t !== 'register');
  document.getElementById('tab-login').classList.toggle('on', t === 'login');
  document.getElementById('tab-register').classList.toggle('on', t === 'register');
  msg.className = 'msg';
}
document.getElementById('tab-login').onclick = () => setTab('login');
document.getElementById('tab-register').onclick = () => setTab('register');

async function post(url, body, btn) {
  btn.disabled = true;
  try {
    const res = await fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    });
    const data = await res.json();
    if (data && data.success) {
      show(data.message || 'Fatto!', true);
      setTimeout(() => { window.location.href = NEXT; }, 600);
    } else {
      show((data && data.error) || 'Errore imprevisto', false);
      btn.disabled = false;
    }
  } catch (e) {
    show('Impossibile contattare il server. Apache e MySQL sono avviati in XAMPP e hai eseguito install.php?', false);
    btn.disabled = false;
  }
}

document.getElementById('form-login').addEventListener('submit', e => {
  e.preventDefault();
  post('api/login.php', {
    username: document.getElementById('l-user').value.trim(),
    password: document.getElementById('l-pass').value
  }, e.target.querySelector('.go'));
});

document.getElementById('form-register').addEventListener('submit', e => {
  e.preventDefault();
  const p1 = document.getElementById('r-pass').value;
  if (p1 !== document.getElementById('r-pass2').value) { show('Le due password non coincidono', false); return; }
  post('api/register.php', {
    username: document.getElementById('r-user').value.trim(),
    email: document.getElementById('r-email').value.trim(),
    password: p1,
    avatar: (document.querySelector('input[name=avatar]:checked') || {}).value || '🚀',
    referral_code: document.getElementById('r-ref').value.trim()
  }, e.target.querySelector('.go'));
});
</script>
</body>
</html>
