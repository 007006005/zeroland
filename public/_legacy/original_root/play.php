<?php
/**
 * play.php?game=tris|forza4 - Lobby e tabellone dei giochi a stanze (multiplayer a turni).
 * Accessibile solo con login: altrimenti si viene riportati al portale.
 */
require_once __DIR__ . '/api/db.php';

$user = require_login_page('index.php');
$game = (string)($_GET['game'] ?? '');
if (!in_array($game, room_games(), true)) {
    header('Location: index.php');
    exit;
}
$g = arcade_games()[$game];
?><!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc($g['title']) ?> - ZeroArcade</title>
<link rel="stylesheet" href="assets/css/portal.css">
</head>
<body>
<header class="top">
  <h1><a href="index.php">🕹️ ZeroArcade</a> <span class="muted">/ <?= esc($g['icon'] . ' ' . $g['title']) ?></span></h1>
  <div class="nav">
    <span class="chip">👤 <b><?= esc($user['username']) ?></b></span>
    <a class="btn" href="index.php">← Portale</a>
  </div>
</header>

<div class="wrap" style="max-width:760px">
  <!-- LOBBY -->
  <section id="lobby" class="card">
    <h2>Lobby</h2>
    <button id="btn-create" class="btn btn-cyan" onclick="createRoom()">➕ Crea una stanza</button>
    <h3 style="margin-top:18px">Stanze aperte</h3>
    <ul id="rooms" class="rooms"><li class="muted">Caricamento…</li></ul>
    <p class="muted" style="margin-top:12px">Vittoria: +60 💰 +40 XP · Pareggio: +25 💰 · Sconfitta: +10 💰 (premi dopo almeno 5 mosse totali).</p>
  </section>

  <!-- PARTITA -->
  <section id="match" class="card" style="display:none">
    <div id="players" class="status"></div>
    <div id="status" class="status" style="margin-top:6px"></div>
    <div id="board" class="board <?= esc($game) ?>"></div>
    <div style="text-align:center;margin-top:10px">
      <button id="btn-leave" class="btn btn-red" onclick="leaveRoom()">Abbandona</button>
    </div>
  </section>
</div>

<script src="assets/js/portal.js"></script>
<script>
var GAME = <?= json_encode($game) ?>;
var roomId = null, version = 0, timer = null, lobbyTimer = null, over = false;

function show(which) {
  document.getElementById('lobby').style.display = which === 'lobby' ? 'block' : 'none';
  document.getElementById('match').style.display = which === 'match' ? 'block' : 'none';
}

/* ---------- Lobby ---------- */
async function refreshLobby() {
  clearTimeout(lobbyTimer);
  if (roomId) return;
  var d = await api('api/rooms.php?action=list&game=' + GAME);
  if (d.success) {
    if (d.mine && d.mine.game === GAME) { openRoom(d.mine.id); return; }
    document.getElementById('btn-create').disabled = !!d.mine;
    document.getElementById('rooms').innerHTML = d.open.length
      ? d.open.map(function (r) {
          return '<li><span>👤 <b>' + esc(r.owner) + '</b> <span class="muted">sta aspettando</span></span>' +
                 '<button class="btn btn-violet" onclick="joinRoom(' + r.id + ')">Sfida</button></li>';
        }).join('')
      : '<li class="muted">Nessuna stanza aperta: creane una e aspetta uno sfidante!</li>';
  }
  lobbyTimer = setTimeout(refreshLobby, 2500);
}
async function createRoom() {
  var d = await api('api/rooms.php', { action: 'create', game: GAME });
  if (d.success || d.room_id) { if (d.game && d.game !== GAME) { location.href = 'play.php?game=' + d.game; return; } openRoom(d.room_id); }
  else toast(d.error || 'Errore');
}
async function joinRoom(id) {
  var d = await api('api/rooms.php', { action: 'join', room_id: id });
  if (d.success || d.room_id) { if (d.game && d.game !== GAME) { location.href = 'play.php?game=' + d.game; return; } openRoom(d.room_id); }
  else { toast(d.error || 'Errore'); refreshLobby(); }
}

/* ---------- Partita ---------- */
function openRoom(id) {
  clearTimeout(lobbyTimer);
  roomId = id; version = 0; over = false;
  show('match');
  poll();
}
async function poll() {
  clearTimeout(timer);
  if (!roomId || over) return;
  var d = await api('api/rooms.php?action=state&room_id=' + roomId + '&since=' + version);
  if (d.success && d.changed) { version = d.room.version; render(d.room); }
  else if (!d.success) { toast(d.error || 'Stanza non più disponibile'); backToLobby(); return; }
  if (!over) timer = setTimeout(poll, 1200);
}
function symbol(v) {
  if (GAME === 'tris') return v === 1 ? 'X' : (v === 2 ? 'O' : '');
  return '';
}
function render(r) {
  var mine = r.you, myTurn = r.status === 'playing' && r.turn === mine;
  document.getElementById('players').innerHTML =
    '<span style="color:#f87171">' + (GAME === 'tris' ? 'X ' : '● ') + esc(r.p1) + '</span> vs <span style="color:' + (GAME === 'tris' ? '#60a5fa' : '#facc15') + '">' +
    (r.p2 ? (GAME === 'tris' ? 'O ' : '● ') + esc(r.p2) : '…') + '</span>';

  var st = '';
  if (r.status === 'waiting') st = '⏳ In attesa di un avversario…';
  else if (r.status === 'playing') st = myTurn ? '🟢 Tocca a te!' : '⌛ Turno di ' + esc(r.turn === 1 ? r.p1 : r.p2);
  else {
    over = true;
    if (r.result === 'draw') st = '🤝 Pareggio!';
    else if (r.result === 'abandoned') st = 'Partita abbandonata (inattività).';
    else if (r.result === 'expired' || r.result === 'closed') st = 'Stanza chiusa.';
    else st = r.winner === mine ? '🏆 Hai vinto!' + (r.result === 'resign' ? ' (l\'avversario si è arreso)' : '') : '💀 Hai perso.';
  }
  document.getElementById('status').innerHTML = st;

  var b = document.getElementById('board'), html = '', cols = GAME === 'tris' ? 3 : 7;
  for (var i = 0; i < r.board.length; i++) {
    var v = r.board[i], cls = 'cell' + (v ? ' p' + v : ''), col = i % cols;
    var can = myTurn && (GAME === 'tris' ? v === 0 : true);
    html += '<button class="' + cls + '"' + (can ? '' : ' disabled') + ' onclick="move(' + (GAME === 'tris' ? i : col) + ')">' + symbol(v) + '</button>';
  }
  b.innerHTML = html;

  var leave = document.getElementById('btn-leave');
  leave.textContent = over ? '← Torna alla lobby' : (r.status === 'waiting' ? 'Chiudi stanza' : 'Arrenditi');
  leave.className = over ? 'btn btn-cyan' : 'btn btn-red';
}
async function move(m) {
  var d = await api('api/rooms.php', { action: 'move', room_id: roomId, move: m });
  if (d.success) { version = d.room.version; render(d.room); if (!over) { clearTimeout(timer); timer = setTimeout(poll, 1200); } }
  else toast(d.error || 'Mossa non valida');
}
async function leaveRoom() {
  if (over) { backToLobby(); return; }
  if (!confirm('Vuoi davvero uscire dalla partita?')) return;
  await api('api/rooms.php', { action: 'leave', room_id: roomId });
  backToLobby();
}
function backToLobby() {
  clearTimeout(timer); roomId = null; over = false;
  show('lobby'); refreshLobby();
}

show('lobby');
refreshLobby();
</script>
</body>
</html>
