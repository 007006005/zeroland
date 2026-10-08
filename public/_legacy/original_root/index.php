<?php
/**
 * index.php - Portale ZeroArcade.
 * Senza login si vede SOLO il modulo di accesso/registrazione: giochi, chat e classifiche
 * sono visibili (e le loro pagine/API raggiungibili) soltanto da utenti autenticati.
 */
require_once __DIR__ . '/api/db.php';

$user = current_user();
$games = arcade_games();
$skins = arcade_skins();
$isStaff = $user && role_rank($user['role']) >= role_rank('moderatore');
$needLogin = isset($_GET['login_required']);
?><!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ZeroArcade - Portale multigioco</title>
<link rel="stylesheet" href="assets/css/portal.css">
</head>
<body>
<header class="top">
  <h1><a href="index.php">🕹️ ZeroArcade</a></h1>
  <?php if ($user): ?>
  <div class="nav">
    <span class="chip">👤 <b><?= esc($user['username']) ?></b></span>
    <span class="chip">⭐ Lv <b id="u-level"><?= (int)$user['level'] ?></b></span>
    <span class="chip">💰 <b id="u-coins"><?= number_format((int)$user['coins'], 0, ',', '.') ?></b></span>
    <span class="chip">💎 <b id="u-gems"><?= (int)$user['gems'] ?></b></span>
    <button class="btn btn-cyan" id="btn-daily">🎁 Bonus</button>
    <button class="btn btn-violet" onclick="openProfile()">Profilo</button>
    <?php if ($isStaff): ?><a class="btn btn-red" href="admin.php">⚙️ Staff</a><?php endif; ?>
    <button class="btn" onclick="logout()">Esci</button>
  </div>
  <?php endif; ?>
</header>

<?php if (!$user): ?>
<!-- ===================== ACCESSO / REGISTRAZIONE ===================== -->
<main class="auth card">
  <h2 style="text-align:center;font-size:20px">Benvenuto su ZeroArcade</h2>
  <p class="muted" style="text-align:center;margin-bottom:12px">
    <?= $needLogin ? 'Per entrare nei giochi devi prima accedere.' : 'Accedi o crea un account per giocare con altri giocatori.' ?>
  </p>
  <div class="tabs">
    <button id="tab-login" class="on" onclick="tab('login')">Accedi</button>
    <button id="tab-register" onclick="tab('register')">Registrati</button>
  </div>

  <form id="f-login" onsubmit="return submitAuth(event,'login')">
    <label for="l-user">Username o email</label>
    <input id="l-user" name="username" autocomplete="username" required maxlength="190">
    <label for="l-pass">Password</label>
    <input id="l-pass" name="password" type="password" autocomplete="current-password" required maxlength="200">
    <button class="btn btn-cyan" style="width:100%;justify-content:center;margin-top:14px" type="submit">Accedi</button>
  </form>

  <form id="f-register" style="display:none" onsubmit="return submitAuth(event,'register')">
    <label for="r-user">Username (3-20: lettere, numeri, _)</label>
    <input id="r-user" name="username" autocomplete="username" required pattern="[A-Za-z0-9_]{3,20}" maxlength="20">
    <label for="r-mail">Email</label>
    <input id="r-mail" name="email" type="email" autocomplete="email" required maxlength="190">
    <label for="r-pass">Password (min. 8 caratteri)</label>
    <input id="r-pass" name="password" type="password" autocomplete="new-password" required minlength="8" maxlength="200">
    <label for="r-pass2">Conferma password</label>
    <input id="r-pass2" name="confirm_password" type="password" autocomplete="new-password" required minlength="8" maxlength="200">
    <button class="btn btn-violet" style="width:100%;justify-content:center;margin-top:14px" type="submit">Crea account</button>
  </form>
  <div id="auth-msg" class="msg"></div>
</main>
<script src="assets/js/portal.js"></script>
<script>
function tab(t) {
  var reg = t === 'register';
  document.getElementById('f-login').style.display = reg ? 'none' : 'block';
  document.getElementById('f-register').style.display = reg ? 'block' : 'none';
  document.getElementById('tab-login').classList.toggle('on', !reg);
  document.getElementById('tab-register').classList.toggle('on', reg);
  document.getElementById('auth-msg').className = 'msg';
}
async function submitAuth(e, action) {
  e.preventDefault();
  var f = e.target, msg = document.getElementById('auth-msg'), btn = f.querySelector('button[type=submit]');
  var body = { action: action };
  new FormData(f).forEach(function (v, k) { body[k] = v; });
  if (action === 'register' && body.password !== body.confirm_password) {
    msg.className = 'msg err'; msg.textContent = 'Le password non coincidono.'; return false;
  }
  btn.disabled = true;
  var d = await api('api/auth.php', body);
  btn.disabled = false;
  if (d.success) { location.href = 'index.php'; return false; }
  msg.className = 'msg err'; msg.textContent = d.error || 'Operazione non riuscita.';
  return false;
}
</script>

<?php else: ?>
<!-- ===================== PORTALE (utente autenticato) ===================== -->
<div class="wrap">
  <div class="cols">
    <div style="display:grid;gap:20px;align-content:start">
      <section class="card">
        <h2>🎮 Giochi</h2>
        <div class="games">
          <?php foreach ($games as $slug => $g): ?>
          <div class="game">
            <div class="ic"><?= esc($g['icon']) ?></div>
            <h4><?= esc($g['title']) ?></h4>
            <span class="tag"><?= esc($g['type']) ?></span>
            <p><?= esc($g['desc']) ?></p>
            <a class="btn btn-cyan" style="justify-content:center" href="<?= esc($g['url']) ?>">Gioca</a>
          </div>
          <?php endforeach; ?>
        </div>
      </section>
      <section class="card">
        <h2>🏆 Classifiche</h2>
        <div id="lb" class="lb"><span class="muted">Caricamento…</span></div>
      </section>
    </div>

    <aside style="display:grid;gap:20px;align-content:start">
      <section class="card">
        <h3>💬 Chat globale</h3>
        <div id="chat" class="chat"></div>
        <form class="row" onsubmit="return sendChat(event)">
          <input id="chat-in" maxlength="200" placeholder="Scrivi un messaggio…" autocomplete="off">
          <button class="btn btn-cyan" type="submit">Invia</button>
        </form>
      </section>
      <section class="card">
        <h3>🟢 Online (<span id="on-n">0</span>)</h3>
        <ul id="online" class="online"></ul>
      </section>
    </aside>
  </div>
</div>

<!-- Profilo + Negozio -->
<div id="m-profile" class="modal" onclick="if(event.target===this)closeProfile()">
  <div class="box">
    <div class="hd"><h3>👤 Profilo giocatore</h3><button class="x" onclick="closeProfile()">&times;</button></div>
    <label>Username</label><input value="<?= esc($user['username']) ?>" disabled>
    <label for="p-clan">Clan (max 6 caratteri)</label><input id="p-clan" maxlength="6" value="<?= esc($user['clan']) ?>">
    <label for="p-bio">Bio</label><textarea id="p-bio" rows="2" maxlength="200"><?= esc($user['profile']['bio']) ?></textarea>
    <label for="p-skin">Skin equipaggiata</label><select id="p-skin"></select>
    <button class="btn btn-cyan" style="width:100%;justify-content:center;margin-top:12px" onclick="saveProfile()">Salva</button>
    <h3 style="margin-top:20px">🛒 Negozio skin</h3>
    <div id="shop" class="shop"></div>
  </div>
</div>

<script src="assets/js/portal.js"></script>
<script>
var SKINS = <?= json_encode($skins, JSON_UNESCAPED_UNICODE) ?>;
var ME = <?= json_encode(user_public($user), JSON_UNESCAPED_UNICODE) ?>;
var lastSeq = -1;

function applyUser(u) {
  ME = u;
  document.getElementById('u-level').textContent = u.level;
  document.getElementById('u-coins').textContent = Number(u.coins).toLocaleString('it-IT');
  document.getElementById('u-gems').textContent = u.gems;
  document.getElementById('btn-daily').disabled = !u.daily_available;
}
applyUser(ME);

/* ---------- Bonus giornaliero ---------- */
document.getElementById('btn-daily').onclick = async function () {
  var d = await api('api/account.php', { action: 'daily' });
  toast(d.success ? d.message : (d.error || 'Errore'));
  if (d.success) applyUser(d.user); else this.disabled = true;
};

/* ---------- Profilo e negozio ---------- */
function renderProfile() {
  var own = ME.profile.unlocked_skins, sel = document.getElementById('p-skin');
  sel.innerHTML = own.map(function (id) {
    var s = SKINS[id] || { name: id, icon: '' };
    return '<option value="' + esc(id) + '"' + (id === ME.profile.equipped_skin ? ' selected' : '') + '>' + esc(s.icon + ' ' + s.name) + '</option>';
  }).join('');
  document.getElementById('shop').innerHTML = Object.keys(SKINS).filter(function (id) { return id !== 'default'; }).map(function (id) {
    var s = SKINS[id], has = own.indexOf(id) >= 0;
    return '<div><span class="e">' + esc(s.icon) + '</span><b>' + esc(s.name) + '</b>' +
      (has ? '<span class="muted">Posseduta</span>'
           : '<button class="btn btn-cyan" onclick="buy(\'' + esc(id) + '\')">' + s.price + (s.currency === 'gems' ? ' 💎' : ' 💰') + '</button>') + '</div>';
  }).join('');
}
function openProfile() { renderProfile(); document.getElementById('m-profile').classList.add('on'); }
function closeProfile() { document.getElementById('m-profile').classList.remove('on'); }
async function saveProfile() {
  var d = await api('api/account.php', { action: 'update_profile', bio: document.getElementById('p-bio').value,
    clan: document.getElementById('p-clan').value, equipped_skin: document.getElementById('p-skin').value });
  toast(d.success ? d.message : (d.error || 'Errore'));
  if (d.success) { applyUser(d.user); closeProfile(); }
}
async function buy(id) {
  if (!confirm('Acquistare la skin ' + SKINS[id].name + '?')) return;
  var d = await api('api/account.php', { action: 'buy_skin', skin_id: id });
  toast(d.success ? d.message : (d.error || 'Errore'));
  if (d.success) { applyUser(d.user); renderProfile(); }
}

/* ---------- Chat e presenza ---------- */
async function pollChat() {
  var d = await api('api/chat.php' + (lastSeq >= 0 ? '?since=' + lastSeq : ''));
  if (d.success) {
    var box = document.getElementById('chat'), stick = box.scrollTop + box.clientHeight >= box.scrollHeight - 30;
    d.messages.forEach(function (m) {
      var el = document.createElement('div');
      el.innerHTML = '<b>' + esc(m.username) + ':</b> ' + esc(m.text);
      box.appendChild(el);
      lastSeq = Math.max(lastSeq, m.seq);
    });
    if (lastSeq < 0) lastSeq = 0;
    if (d.messages.length && stick) box.scrollTop = box.scrollHeight;
    document.getElementById('on-n').textContent = d.online_count;
    document.getElementById('online').innerHTML = d.online.map(function (o) {
      return '<li>' + esc(o.username) + ' <small>Lv ' + esc(o.level) + ' · [' + esc(o.clan) + ']</small></li>';
    }).join('');
  }
}
async function sendChat(e) {
  e.preventDefault();
  var i = document.getElementById('chat-in'), t = i.value.trim();
  if (!t) return false;
  var d = await api('api/chat.php', { text: t });
  if (d.success) { i.value = ''; pollChat(); } else toast(d.error || 'Errore');
  return false;
}

/* ---------- Classifiche ---------- */
async function loadLb() {
  var d = await api('api/leaderboard.php');
  if (!d.success) return;
  document.getElementById('lb').innerHTML = Object.keys(d.boards).map(function (k) {
    var b = d.boards[k];
    return '<div><h4>' + esc(b.title) + ' <small>(' + esc(b.label) + ')</small></h4><ol>' +
      (b.top.map(function (r) { return '<li>' + esc(r.username) + ' <small>' + Number(r.score).toLocaleString('it-IT') + '</small></li>'; }).join('') || '<li class="muted">Nessun punteggio</li>') +
      '</ol></div>';
  }).join('');
}

pollChat(); loadLb();
setInterval(pollChat, 3000);
setInterval(loadLb, 30000);
</script>
<?php endif; ?>
<div id="toast"></div>
</body>
</html>
