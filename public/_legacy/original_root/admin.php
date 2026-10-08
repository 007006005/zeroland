<?php
/**
 * admin.php - Pannello staff (moderatore, admin, founder). Le azioni passano da api/admin.php.
 */
require_once __DIR__ . '/api/db.php';

$user = require_login_page('index.php');
if (role_rank($user['role']) < role_rank('moderatore')) {
    header('Location: index.php');
    exit;
}
$roles = array_values(array_filter(ARCADE_ROLES, function ($r) use ($user) { return role_rank($r) < role_rank($user['role']); }));
?><!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Staff - ZeroArcade</title>
<link rel="stylesheet" href="assets/css/portal.css">
</head>
<body>
<header class="top">
  <h1><a href="index.php">🕹️ ZeroArcade</a> <span class="muted">/ Staff</span></h1>
  <div class="nav">
    <span class="chip">👤 <b><?= esc($user['username']) ?></b> (<?= esc($user['role']) ?>)</span>
    <a class="btn" href="index.php">← Portale</a>
  </div>
</header>
<div class="wrap">
  <section class="card"><h2>Riepilogo</h2><div id="stats" class="nav"></div></section>

  <section class="card">
    <h2>Utenti</h2>
    <form class="row" onsubmit="return loadUsers(event)" style="margin:0 0 10px">
      <input id="q" placeholder="Cerca username o email…"><button class="btn btn-cyan" type="submit">Cerca</button>
    </form>
    <div style="overflow-x:auto"><table class="t"><thead><tr><th>Utente</th><th>Ruolo</th><th>💰</th><th>💎</th><th>Stato</th><th>Azioni</th></tr></thead><tbody id="users"></tbody></table></div>
  </section>

  <section class="card">
    <h2>Chat <?php if (role_rank($user['role']) >= role_rank('admin')): ?><button class="btn btn-red" style="float:right" onclick="clearChat()">Svuota</button><?php endif; ?></h2>
    <div id="chat" class="chat" style="height:200px"></div>
  </section>

  <section class="card"><h2>Registro azioni</h2>
    <div style="overflow-x:auto"><table class="t"><thead><tr><th>Quando</th><th>Staff</th><th>Azione</th><th>Utente</th><th>Dettagli</th></tr></thead><tbody id="logs"></tbody></table></div>
  </section>
</div>
<script src="assets/js/portal.js"></script>
<script>
var ROLES = <?= json_encode($roles) ?>;
var MYRANK = <?= (int)role_rank($user['role']) ?>;
var ALL = <?= json_encode(ARCADE_ROLES) ?>;
function A(body) { return api('api/admin.php', body); }

async function loadStats() {
  var d = await A({ action: 'stats' });
  if (!d.success) return;
  var s = d.stats;
  document.getElementById('stats').innerHTML = ['users:Utenti', 'online:Online', 'banned:Bannati', 'rooms:Stanze attive', 'messages:Messaggi chat']
    .map(function (x) { var p = x.split(':'); return '<span class="chip">' + p[1] + ' <b>' + s[p[0]] + '</b></span>'; }).join('');
}
async function loadUsers(e) {
  if (e) e.preventDefault();
  var d = await A({ action: 'list_users', q: document.getElementById('q').value });
  if (!d.success) { toast(d.error); return false; }
  document.getElementById('users').innerHTML = d.users.map(function (u) {
    var can = ALL.indexOf(u.role) < MYRANK, id = esc(u.id);
    var acts = '';
    if (can) {
      acts += u.is_banned == 1
        ? '<button class="btn" onclick="ban(\'' + id + '\',0)">Sblocca</button> '
        : '<button class="btn btn-red" onclick="ban(\'' + id + '\',1)">Banna</button> ';
      if (MYRANK >= ALL.indexOf('admin')) acts += '<button class="btn" onclick="role(\'' + id + '\')">Ruolo</button> <button class="btn" onclick="grant(\'' + id + '\')">Accredita</button> ';
      if (MYRANK >= ALL.indexOf('founder')) acts += '<button class="btn btn-red" onclick="del(\'' + id + '\')">Elimina</button>';
    }
    return '<tr><td>' + esc(u.username) + '<br><small class="muted">' + esc(u.email || '') + '</small></td><td>' + esc(u.role) + '</td><td>' + u.coins + '</td><td>' + u.gems +
      '</td><td>' + (u.is_banned == 1 ? '🚫 ' + esc(u.ban_reason || '') : 'attivo') + '</td><td>' + acts + '</td></tr>';
  }).join('');
  return false;
}
async function ban(id, b) {
  var reason = b ? (prompt('Motivo del ban:') || '') : '';
  if (b && reason === '') return;
  var d = await A({ action: 'set_ban', user_id: id, banned: b, reason: reason });
  toast(d.success ? 'Fatto' : d.error); loadUsers(); loadLogs(); loadStats();
}
async function role(id) {
  var r = prompt('Nuovo ruolo (' + ROLES.join(', ') + '):');
  if (!r) return;
  var d = await A({ action: 'set_role', user_id: id, role: r.trim() });
  toast(d.success ? 'Fatto' : d.error); loadUsers(); loadLogs();
}
async function grant(id) {
  var c = prompt('Monete da aggiungere (negativo per togliere):', '0'); if (c === null) return;
  var g = prompt('Gemme da aggiungere:', '0'); if (g === null) return;
  var d = await A({ action: 'grant', user_id: id, coins: parseInt(c, 10) || 0, gems: parseInt(g, 10) || 0 });
  toast(d.success ? 'Fatto' : d.error); loadUsers(); loadLogs();
}
async function del(id) {
  if (!confirm('Eliminare definitivamente questo utente?')) return;
  var d = await A({ action: 'delete_user', user_id: id });
  toast(d.success ? 'Utente eliminato' : d.error); loadUsers(); loadLogs(); loadStats();
}
async function loadChat() {
  var d = await A({ action: 'chat_list' });
  if (!d.success) return;
  document.getElementById('chat').innerHTML = d.messages.reverse().map(function (m) {
    return '<div><b>' + esc(m.username) + ':</b> ' + esc(m.text) + ' <a href="#" onclick="delMsg(' + m.id + ');return false">🗑</a></div>';
  }).join('');
}
async function delMsg(id) { await A({ action: 'chat_delete', id: id }); loadChat(); loadLogs(); }
async function clearChat() { if (confirm('Svuotare tutta la chat?')) { await A({ action: 'chat_clear' }); loadChat(); loadLogs(); } }
async function loadLogs() {
  var d = await A({ action: 'audit_logs' });
  if (!d.success) return;
  document.getElementById('logs').innerHTML = d.logs.map(function (l) {
    return '<tr><td>' + esc(l.created_at) + '</td><td>' + esc(l.admin_name) + '</td><td>' + esc(l.action) + '</td><td>' + esc(l.target) + '</td><td>' + esc(l.details) + '</td></tr>';
  }).join('');
}
loadStats(); loadUsers(); loadChat(); loadLogs();
</script>
</body>
</html>
