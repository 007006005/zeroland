<?php
require_once __DIR__ . '/api/db.php';
$u = require_login_page();
if (!in_array($u['role'], ['moderatore', 'admin', 'founder'], true)) { 
    http_response_code(403); 
    exit('Accesso negato: Area riservata allo Staff.'); 
}
?><!DOCTYPE html>
<html lang="it">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Pannello di Amministrazione</title>
<style>
body{font-family:system-ui,-apple-system,sans-serif;background:#0b1020;color:#e2e8f0;margin:0;padding:20px}
a{color:#22d3ee;text-decoration:none}a:hover{text-decoration:underline}
h1{color:#22d3ee;margin:0 0 12px;font-size:24px}
nav button{background:#1e293b;color:#e2e8f0;border:0;padding:10px 16px;border-radius:8px;margin:0 6px 6px 0;cursor:pointer;font-weight:600;transition:all 0.2s}
nav button:hover{background:#334155}nav button.on{background:#06b6d4;color:#001018;font-weight:700}
table{width:100%;border-collapse:collapse;font-size:14px;margin-top:10px}
td,th{padding:9px;border-bottom:1px solid #1e293b;text-align:left;vertical-align:middle}
th{background:#0f172a;color:#94a3b8;font-size:12px;text-transform:uppercase}
input,select,textarea{background:#0f172a;color:#e2e8f0;border:1px solid #334155;border-radius:6px;padding:8px;font-size:14px}
button.s{background:#334155;color:#e2e8f0;border:0;border-radius:6px;padding:6px 12px;cursor:pointer;margin:2px;font-weight:600}
button.s:hover{opacity:0.85}button.r{background:#991b1b;color:#fca5a5}button.b{background:#2563eb;color:#fff}
.card{display:inline-block;background:#111a33;border:1px solid #1e293b;border-radius:10px;padding:16px 20px;margin:0 10px 10px 0;min-width:160px}
.card b{display:block;font-size:24px;color:#22d3ee;margin-top:4px}
#msg{position:fixed;right:20px;bottom:20px;background:#064e3b;color:#6ee7b7;padding:12px 18px;border-radius:8px;display:none;box-shadow:0 10px 25px rgba(0,0,0,0.5);z-index:99999}
.w{overflow-x:auto}.section-box{background:#111a33;border:1px solid #1e293b;border-radius:10px;padding:18px;margin-bottom:16px}
</style>
</head>
<body>
<h1>🛠️ Pannello Amministrazione <small style="font-size:14px;color:#94a3b8">— Utente: <?= htmlspecialchars($u['username']) ?> (<?= htmlspecialchars($u['role']) ?>)</small></h1>
<p><a href="index.php">← Torna all'Arcade</a> · <a href="dashboard.php">La mia dashboard</a></p>

<nav id="nav"></nav>
<div id="v" class="w"></div>
<div id="msg"></div>

<script>
const ME = <?= json_encode($u['role']) ?>, R = ['user','premium','helper','moderatore','admin','founder'];
const isA = ['admin','founder'].includes(ME), isF = ME === 'founder';
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

async function api(o){
  try {
    const r = await fetch('api/admin.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(o) });
    const d = await r.json();
    if (d.message) {
      const m = document.getElementById('msg');
      m.textContent = d.message;
      m.style.display = 'block';
      setTimeout(() => m.style.display = 'none', 3000);
    }
    if (!d.success) alert(d.error || 'Errore riscontrato');
    return d;
  } catch(e) {
    alert('Errore di connessione al server.');
    return { success: false };
  }
}

const tabs = {
  Statistiche: stats,
  Annuncio: broadcast,
  Utenti: users,
  Chat: chat,
  ...(isA ? { RegistroAudit: audit, Negozio: shop, Ordini: orders, Classifiche: boards } : {})
};

const nav = document.getElementById('nav'), v = document.getElementById('v');
Object.keys(tabs).forEach(k => {
  const b = document.createElement('button');
  b.textContent = k;
  b.onclick = () => { [...nav.children].forEach(x => x.className = ''); b.className = 'on'; tabs[k](); };
  nav.appendChild(b);
});
nav.firstChild.click();

async function stats() {
  const d = await api({action:'stats'});
  v.innerHTML = Object.entries(d.stats || {}).map(([k,x]) => `<div class="card">${esc(k)}<b>${Number(x).toLocaleString('it')}</b></div>`).join('');
}

function broadcast() {
  v.innerHTML = `
    <div class="section-box" style="max-width:600px;">
      <h3>📢 Invia Annuncio Globale (Broadcast)</h3>
      <p style="color:#94a3b8;font-size:13px;">Questo messaggio apparirà istantaneamente nella chat globale e nei sistemi di notifica di tutti i giocatori attivi.</p>
      <textarea id="bc_text" style="width:100%;height:80px;box-sizing:border-box;" placeholder="Scrivi il messaggio di annuncio..."></textarea><br><br>
      <button class="s b" onclick="sendBroadcast()">🚀 Trasmetti Annuncio</button>
    </div>
  `;
}

async function sendBroadcast() {
  const text = document.getElementById('bc_text').value.trim();
  if (!text) return alert('Inserisci il testo dell\'annuncio.');
  const res = await api({ action: 'broadcast_global', message: text });
  if (res.success) document.getElementById('bc_text').value = '';
}

async function users(q = '') {
  const d = await api({action:'list_users', q});
  if (!d.success) return;
  v.innerHTML = `
    <p><input id="q" placeholder="Cerca per utente o email" value="${esc(q)}" style="width:250px"> 
    <button class="s" onclick="users(document.getElementById('q').value)">Cerca</button></p>
    <table>
      <tr><th>Utente</th><th>Ruolo</th><th>Risorse (Monete/Gemme/XP)</th><th>Ultimo Accesso</th><th>Azioni</th></tr>
      ${d.users.map(x => `
        <tr>
          <td><b>${esc(x.username)}</b><br><small style="color:#94a3b8">${esc(x.email || '')}</small></td>
          <td>${isF ? `<select onchange="api({action:'update_user',user_id:'${x.id}',role:this.value})">${R.map(r => `<option ${r==x.role?'selected':''}>${r}</option>`).join('')}</select>` : esc(x.role)}</td>
          <td>💰 ${x.coins} \vert{} 💎 ${x.gems} | ⭐ ${x.xp} (Liv.${x.level})</td>
          <td>${esc(x.last_seen || '-')}</td>
          <td>
            <button class="s" onclick="ban('${x.id}',${x.is_banned==1?0:1})">${x.is_banned==1?'Riammetti':'Banna'}</button>${isA ? `<button class="s" onclick="grant('${x.id}')">Bonus</button><button class="s r" onclick="if(confirm('Azzerare i progressi?'))api({action:'reset_progress',user_id:'${x.id}'})">Reset</button>` : ''}
            ${isF ? `<button class="s r" onclick="if(confirm('Eliminare definitivamente l\\'utente ${esc(x.username)}?'))api({action:'delete_user',user_id:'${x.id}'}).then(()=>users())">Elimina</button>` : ''}
          </td>
        </tr>
      `).join('')}
    </table>`;
}

async function ban(id, b) {
  const reason = b ? prompt('Motivo del ban:') || 'Violazione regolamento' : '';
  await api({action:'set_ban', user_id:id, banned:b, reason});
  users(document.getElementById('q')?.value || '');
}

async function grant(id) {
  const c = +prompt('Monete (+/-):','0'), g = +prompt('Gemme (+/-):','0'), x = +prompt('XP (+/-):','0');
  await api({action:'grant', user_id:id, coins:c||0, gems:g||0, xp:x||0});
  users(document.getElementById('q')?.value || '');
}

async function audit() {
  const d = await api({action:'audit_logs'});
  v.innerHTML = `<h3>📋 Registro delle Azioni Amministrative</h3><table>
    <tr><th>Data</th><th>Admin</th><th>Azione</th><th>Target</th><th>Dettagli</th></tr>
    ${(d.rows || []).map(l => `<tr><td>${esc(l.created_at)}</td><td><b>${esc(l.admin_username)}</b></td><td><code>${esc(l.action)}</code></td><td>${esc(l.target_username \vert{}\vert{} '-')}</td><td>${esc(l.details || '-')}</td></tr>`).join('')}
  </table>`;
}

async function chat() {
  const d = await api({action:'chat_list'});
  v.innerHTML = (isA ? '<p><button class="s r" onclick="if(confirm(\'Svuotare l\'intera chat?\'))api({action:\'chat_clear\'}).then(chat)">Svuota Chat</button></p>' : '') +
  '<table><tr><th>ID</th><th>Utente</th><th>Messaggio</th><th>Data</th><th>Azione</th></tr>' +
  (d.rows || []).map(m => `<tr><td>${m.id}</td><td><b>${esc(m.username)}</b></td><td>${esc(m.text)}</td><td>${esc(m.created_at)}</td><td><button class="s r" onclick="api({action:'chat_delete',id:${m.id}}).then(chat)">✕</button></td></tr>`).join('') + '</table>';
}

async function shop() {
  const d = await api({action:'shop_list'});
  v.innerHTML = '<table><tr><th>ID</th><th>Nome Articolo</th><th>Prezzo</th><th>Azione</th></tr>' +
  (d.rows || []).map(s => `<tr><td>${esc(s.id)}</td><td><input id="n_${esc(s.id)}" value="${esc(s.name)}"></td><td><input id="p_${esc(s.id)}" type="number" value="${s.price}" style="width:90px"></td><td><button class="s" onclick="api({action:'shop_update',id:'${esc(s.id)}',name:document.getElementById('n_${esc(s.id)}').value,price:+document.getElementById('p_${esc(s.id)}').value})">Salva</button></td></tr>`).join('') + '</table>';
}

async function orders() {
  const d = await api({action:'orders_list'});
  const rows = d.rows || [];
  if (!rows.length) { v.textContent = 'Nessun ordine registrato.'; return; }
  const c = Object.keys(rows[0]);
  v.innerHTML = '<table><tr>' + c.map(k => `<th>${esc(k)}</th>`).join('') + '</tr>' +
  rows.map(r => '<tr>' + c.map(k => `<td>${esc(r[k])}</td>`).join('') + '</tr>').join('') + '</table>';
}

function boards() {
  v.innerHTML = '<p>Azzera i punteggi dei giocatori reali per una modalità:</p>' +
  ['zero_agar','space_defender','cyber_runner','neon_breaker'].map(g => `<button class="s r" onclick="if(confirm('Azzerare la classifica per ${g}?'))api({action:'leaderboard_reset',game_id:'${g}'})">${g}</button> `).join('');
}
</script>
</body>
</html>