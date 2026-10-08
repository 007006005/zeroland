<?php
/**
 * admin.php - Pannello di Amministrazione e Gestione Staff
 */
require_once __DIR__ . '/api/db.php';

$cur = require_login_page();
$rankMap = array_flip(ARCADE_ROLES);$myRank = $rankMap[$cur['role']] ?? 0;

if ($myRank < ($rankMap['moderatore'] ?? 3)) {
    header('Location: index.php?error=unauthorized');
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pannello di Amministrazione - ZeroArcade</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: system-ui, -apple-system, sans-serif; }
        body { background: #0b1020; color: #f8fafc; display: flex; flex-direction: column; min-height: 100vh; }
        header { background: #0f172a; border-bottom: 1px solid #1e293b; padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; }
        header h1 { font-size: 20px; color: #ef4444; display: flex; align-items: center; gap: 8px; }
        .btn { background: #334155; color: #fff; border: 0; padding: 7px 14px; border-radius: 6px; cursor: pointer; text-decoration: none; font-size: 13px; font-weight: 600; transition: background 0.2s; display: inline-flex; align-items: center; gap: 6px; }
        .btn:hover { background: #475569; }
        .btn-red { background: #dc2626; }
        .btn-red:hover { background: #b91c1c; }
        .btn-cyan { background: #06b6d4; color: #000; }
        
        main { flex: 1; padding: 24px; max-width: 1400px; width: 100%; margin: 0 auto; display: flex; flex-direction: column; gap: 20px; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; }
        .stat-card { background: #111a33; border: 1px solid #1e293b; border-radius: 10px; padding: 16px; display: flex; flex-direction: column; gap: 6px; }
        .stat-card span { font-size: 13px; color: #94a3b8; text-transform: uppercase; }
        .stat-card b { font-size: 24px; color: #22d3ee; }
        
        .card { background: #111a33; border: 1px solid #1e293b; border-radius: 10px; padding: 20px; display: flex; flex-direction: column; gap: 16px; }
        .card h3 { color: #22d3ee; font-size: 16px; text-transform: uppercase; }
        
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
        th { background: #0f172a; color: #38bdf8; padding: 12px; border-bottom: 1px solid #1e293b; }
        td { padding: 12px; border-bottom: 1px solid #1e293b; color: #cbd5e1; }
        tr:hover td { background: rgba(255,255,255,0.02); }
        
        .search-bar { display: flex; gap: 10px; margin-bottom: 10px; }
        .search-bar input { flex: 1; background: #0f172a; border: 1px solid #334155; color: #fff; padding: 8px 12px; border-radius: 6px; outline: none; }
        
        .badge { font-size: 11px; padding: 3px 8px; border-radius: 12px; font-weight: 700; background: #334155; color: #38bdf8; text-transform: uppercase; }
        .badge-banned { background: #7f1d1d; color: #fca5a5; }
    </style>
</head>
<body>

<header>
    <h1>⚙️ Pannello di Amministrazione & Staff</h1>
    <div style="display: flex; gap: 12px; align-items: center;">
        <span>👤 <b><?= htmlspecialchars($cur['username']) ?></b> (<?= htmlspecialchars($cur['role']) ?>)</span>
        <a href="index.php" class="btn">🏠 Torna al Portale</a>
    </div>
</header>

<main>
    <!-- Statistiche Generali -->
    <div class="stats-grid" id="stats-container">
        <div class="stat-card"><span>Utenti Totali</span><b id="st-users">-</b></div>
        <div class="stat-card"><span>Attivi 24h</span><b id="st-active">-</b></div>
        <div class="stat-card"><span>Utenti Bannati</span><b id="st-banned">-</b></div>
        <div class="stat-card"><span>Monete in Circolo</span><b id="st-coins">-</b></div>
    </div>

    <!-- Gestione Utenti -->
    <div class="card">
        <h3>👥 Gestione Utenti e Moderazione</h3>
        <div class="search-bar">
            <input type="text" id="search-input" placeholder="Cerca utente per username o email..." oninput="loadUsers()">
            <button class="btn btn-cyan" onclick="loadUsers()">Cerca</button>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Ruolo</th>
                        <th>Monete</th>
                        <th>Stato</th>
                        <th>Registrazione</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody id="users-table-body">
                    <tr><td colspan="7" style="text-align:center;">Caricamento utenti in corso...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
    async function apiCall(action, data = {}) {
        try {
            const res = await fetch('api/admin.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action, ...data })
            });
            return await res.json();
        } catch(e) {
            return { success: false, error: 'Errore di connessione al server.' };
        }
    }

    async function loadStats() {
        const res = await apiCall('stats');
        if (res.success && res.stats) {
            document.getElementById('st-users').innerText = res.stats['Utenti Totali'] || 0;
            document.getElementById('st-active').innerText = res.stats['Attivi 24h'] || 0;
            document.getElementById('st-banned').innerText = res.stats['Utenti Bannati'] || 0;
            document.getElementById('st-coins').innerText = Number(res.stats['Monete Totali in Circolo'] || 0).toLocaleString();
        }
    }

    async function loadUsers() {
        const q = document.getElementById('search-input').value.trim();
        const res = await apiCall('list_users', { q });
        const tbody = document.getElementById('users-table-body');
        
        if (!res.success || !res.users || res.users.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;">Nessun utente trovato.</td></tr>`;
            return;
        }

        tbody.innerHTML = res.users.map(u => `
            <tr>
                <td><b>${escapeHtml(u.username)}</b></td>
                <td>${escapeHtml(u.email || '-')}</td>
                <td><span class="badge">${u.role}</span></td>
                <td>💰 ${Number(u.coins).toLocaleString()}</td>
                <td>${u.is_banned == 1 ? '<span class="badge badge-banned">Bannato</span>' : '<span style="color:#34d399">Attivo</span>'}</td>
                <td>${u.created_at}</td>
                <td>
                    <div style="display:flex; gap:6px;">
                        ${u.is_banned == 1 ? 
                            `<button class="btn btn-cyan" onclick="setBan('${u.id}', 0)">Sbanna</button>` : 
                            `<button class="btn btn-red" onclick="setBanModal('${u.id}')">Banna</button>`
                        }
                        ${res.my_role === 'founder' || res.my_role === 'admin' ? 
                            `<button class="btn" onclick="grantBonus('${u.id}')">+ Bonus</button>` : ''
                        }
                    </div>
                </td>
            </tr>
        `).join('');
    }

    async function setBan(userId, banned, reason = '') {
        const res = await apiCall('set_ban', { user_id: userId, banned, reason });
        if (res.success) {
            loadUsers();
            loadStats();
        } else {
            alert(res.error || 'Errore durante l\'operazione.');
        }
    }

    function setBanModal(userId) {
        const reason = prompt("Inserisci il motivo del ban:", "Violazione del regolamento");
        if (reason !== null) {
            setBan(userId, 1, reason);
        }
    }

    async function grantBonus(userId) {
        const coins = prompt("Quante monete vuoi assegnare?", "1000");
        if (coins === null) return;
        const gems = prompt("Quante gemme vuoi assegnare?", "50");
        if (gems === null) return;

        const res = await apiCall('grant', { user_id: userId, coins: parseInt(coins) || 0, gems: parseInt(gems) || 0 });
        if (res.success) {
            alert("Bonus applicato con successo!");
            loadUsers();
        } else {
            alert(res.error || 'Errore.');
        }
    }

    function escapeHtml(str) {
        return String(str || '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    // Carica dati iniziali
    loadStats();
    loadUsers();
</script>
</body>
</html>