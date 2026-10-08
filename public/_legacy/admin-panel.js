// admin-panel.js - Client Overlay In-Game
class InGameAdminPanel {
    constructor(socket) {
        this.socket = socket;
        this.isAdmin = false;
        this.isOpen = false;
        this.godModeActive = false;
        this.isServerFrozen = false;
        
        this.initDOM();
        this.initBroadcastOverlay();
        this.bindEvents();
        this.listenSocketEvents();
    }

    initDOM() {
        if (document.getElementById('admin-overlay')) return;

        // Creazione contenitore principale overlay
        this.container = document.createElement('div');
        this.container.id = 'admin-overlay';
        this.container.style.cssText = `
            position: absolute;
            top: 20px;
            right: 20px;
            width: 360px;
            max-height: 85vh;
            background: rgba(15, 23, 42, 0.96);
            border: 2px solid #ef4444;
            border-radius: 8px;
            color: #f8fafc;
            font-family: system-ui, -apple-system, sans-serif;
            box-shadow: 0 10px 30px rgba(0,0,0,0.7);
            display: none;
            flex-direction: column;
            z-index: 9999;
            overflow: hidden;
        `;

        this.container.innerHTML = `
            <div style="background: linear-gradient(135deg, #ef4444, #dc2626); color: #fff; padding: 10px 14px; font-weight: bold; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #7f1d1d;">
                <span>⚙️ PANNELLO ADMIN IN-GAME</span>
                <button id="admin-close-btn" style="background:none; border:none; color:#fff; cursor:pointer; font-weight:bold; font-size:16px;">✕</button>
            </div>
            
            <!-- STATS RAPIDE TELEMETRIA -->
            <div id="admin-telemetry-bar" style="background:#1e293b; padding:6px 12px; font-size:11px; display:flex; justify-content:space-between; color:#94a3b8; border-bottom:1px solid #334155;">
                <span>Sockets: <b id="tele-online" style="color:#22d3ee;">-</b></span>
                <span>RAM: <b id="tele-ram" style="color:#22d3ee;">-</b></span>
                <span>Uptime: <b id="tele-uptime" style="color:#22d3ee;">-</b></span>
            </div>

            <div style="padding: 12px; overflow-y: auto; flex: 1;">
                <!-- SEZIONE ANNUNCIO GLOBALE -->
                <div style="margin-bottom: 15px; background: rgba(30,41,59,0.5); padding: 10px; border-radius: 6px; border: 1px solid #334155;">
                    <h4 style="margin: 0 0 6px 0; color: #f87171; font-size: 13px;">📢 Messaggio Globale (Broadcast)</h4>
                    <input type="text" id="broadcast-msg" placeholder="Scrivi un annuncio per tutti i giocatori..." class="admin-input">
                    <button id="btn-broadcast" class="admin-btn" style="background:#2563eb; font-weight:bold; margin-top:6px;">🚀 Invia Annuncio Globale</button>
                </div>

                <!-- SEZIONE AZIONI RAPIDE GIOCATORE -->
                <div style="margin-bottom: 15px; background: rgba(30,41,59,0.5); padding: 10px; border-radius: 6px; border: 1px solid #334155;">
                    <h4 style="margin: 0 0 6px 0; color: #f87171; font-size: 13px;">👤 Poteri Personali</h4>
                    <button id="btn-godmode" class="admin-btn">God Mode: OFF</button>
                    <div style="display: flex; gap: 5px; margin-top: 5px;">
                        <input type="number" id="teleport-x" placeholder="Coord X" class="admin-input">
                        <input type="number" id="teleport-y" placeholder="Coord Y" class="admin-input">
                        <button id="btn-teleport" class="admin-btn" style="width:110px;">Teleport</button>
                    </div>
                </div>

                <!-- SEZIONE GESTIONE GIOCATORI -->
                <div style="margin-bottom: 15px; background: rgba(30,41,59,0.5); padding: 10px; border-radius: 6px; border: 1px solid #334155;">
                    <h4 style="margin: 0 0 6px 0; color: #f87171; font-size: 13px;">👥 Gestione Utenti In-Game</h4>
                    <select id="player-list" class="admin-input" style="width: 100%; margin-bottom: 6px;">
                        <option value="">Caricamento giocatori...</option>
                    </select>
                    <div style="display: flex; gap: 4px;">
                        <button id="btn-mute" class="admin-btn" style="background:#d97706;">Mute</button>
                        <button id="btn-kick" class="admin-btn" style="background:#ea580c;">Kick</button>
                        <button id="btn-ban" class="admin-btn" style="background:#dc2626;">Ban</button>
                    </div>
                </div>

                <!-- SEZIONE CONTROLLO MONDO E PARTITA -->
                <div style="background: rgba(30,41,59,0.5); padding: 10px; border-radius: 6px; border: 1px solid #334155;">
                    <h4 style="margin: 0 0 6px 0; color: #f87171; font-size: 13px;">🌐 Controllo Eventi & Server</h4>
                    <button id="btn-freeze" class="admin-btn" style="background:#7c3aed;">❄️️ Congela Partita</button>
                    <button id="btn-event-xp" class="admin-btn" style="background:#059669; margin-top:4px;">⭐ Evento Doppi XP (5 min)</button>
                    <button id="btn-clear-entities" class="admin-btn" style="background:#475569; margin-top:4px;">🧹 Pulisci Mappa</button>
                </div>
            </div>
            
            <div id="admin-feedback-msg" style="padding:6px 12px; font-size:12px; text-align:center; display:none; background:#064e3b; color:#6ee7b7;"></div>
        `;

        // Style CSS iniettato per gli elementi del pannello
        const style = document.createElement('style');
        style.innerHTML = `
            .admin-btn {
                background: #334155;
                color: #fff;
                border: none;
                padding: 7px 10px;
                border-radius: 4px;
                cursor: pointer;
                font-size: 12px;
                width: 100%;
                font-weight: 600;
                transition: all 0.2s ease;
            }
            .admin-btn:hover { opacity: 0.88; transform: translateY(-1px); }
            .admin-btn:active { transform: translateY(0); }
            .admin-input {
                background: #0f172a;
                border: 1px solid #475569;
                color: #fff;
                padding: 6px 8px;
                border-radius: 4px;
                width: 100%;
                font-size: 12px;
                box-sizing: border-box;
            }
            .admin-input:focus { border-color: #ef4444; outline: none; }
        `;
        document.head.appendChild(style);
        document.body.appendChild(this.container);
    }

    initBroadcastOverlay() {
        if (document.getElementById('global-announcement-banner')) return;

        this.broadcastBanner = document.createElement('div');
        this.broadcastBanner.id = 'global-announcement-banner';
        this.broadcastBanner.style.cssText = `
            position: fixed;
            top: -100px;
            left: 50%;
            transform: translateX(-50%);
            width: 90%;
            max-width: 700px;
            background: linear-gradient(135deg, rgba(220, 38, 38, 0.95), rgba(185, 28, 28, 0.95));
            color: #ffffff;
            border: 2px solid #fca5a5;
            box-shadow: 0 10px 30px rgba(0,0,0,0.6);
            border-radius: 10px;
            padding: 12px 20px;
            text-align: center;
            z-index: 10000;
            transition: top 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            pointer-events: none;
        `;
        this.broadcastBanner.innerHTML = `
            <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; opacity: 0.9; font-weight: bold;">📢 COMUNICAZIONE DALLA DIREZIONE</div>
            <div id="global-announcement-text" style="font-size: 16px; font-weight: bold; margin-top: 4px; text-shadow: 0 2px 4px rgba(0,0,0,0.5);"></div>
        `;
        document.body.appendChild(this.broadcastBanner);
    }

    displayBroadcastNotification(msg) {
        const textEl = document.getElementById('global-announcement-text');
        if (textEl) {
            textEl.textContent = msg;
            this.broadcastBanner.style.top = '20px';

            if (this.broadcastTimeout) clearTimeout(this.broadcastTimeout);
            this.broadcastTimeout = setTimeout(() => {
                this.broadcastBanner.style.top = '-100px';
            }, 6500);
        }
    }

    showFeedback(msg, isError = false) {
        const fb = document.getElementById('admin-feedback-msg');
        if (!fb) return;
        fb.textContent = msg;
        fb.style.background = isError ? '#881337' : '#064e3b';
        fb.style.color = isError ? '#fca5a5' : '#6ee7b7';
        fb.style.display = 'block';
        setTimeout(() => { fb.style.display = 'none'; }, 3000);
    }

    bindEvents() {
        // Toggle visibilità con tasto F10 o Tilde (`)
        window.addEventListener('keydown', (e) => {
            if ((e.key === 'F10' || e.key === '`') && this.isAdmin) {
                e.preventDefault();
                this.togglePanel();
            }
        });

        document.getElementById('admin-close-btn').addEventListener('click', () => this.togglePanel(false));

        // Invio Messaggio Globale
        document.getElementById('btn-broadcast').addEventListener('click', () => {
            const input = document.getElementById('broadcast-msg');
            const message = input.value.trim();
            if (message !== '') {
                this.sendAction('broadcast_message', { message });
                input.value = '';
            } else {
                this.showFeedback('Inserisci un testo per l\'annuncio', true);
            }
        });

        // Toggle God Mode
        document.getElementById('btn-godmode').addEventListener('click', () => {
            this.godModeActive = !this.godModeActive;
            document.getElementById('btn-godmode').innerText = `God Mode: ${this.godModeActive ? 'ON' : 'OFF'}`;
            document.getElementById('btn-godmode').style.background = this.godModeActive ? '#16a34a' : '#334155';
            this.sendAction('toggle_godmode', { active: this.godModeActive });
        });

        // Teleport
        document.getElementById('btn-teleport').addEventListener('click', () => {
            const x = parseFloat(document.getElementById('teleport-x').value);
            const y = parseFloat(document.getElementById('teleport-y').value);
            if (!isNaN(x) && !isNaN(y)) {
                this.sendAction('teleport', { x, y });
            } else {
                this.showFeedback('Inserisci coordinate X e Y valide', true);
            }
        });

        // Azioni Utente
        document.getElementById('btn-mute').addEventListener('click', () => {
            const targetId = document.getElementById('player-list').value;
            if (targetId) this.sendAction('toggle_mute', { targetId });
        });

        document.getElementById('btn-kick').addEventListener('click', () => {
            const targetId = document.getElementById('player-list').value;
            if (targetId) this.sendAction('kick_player', { targetId });
        });

        document.getElementById('btn-ban').addEventListener('click', () => {
            const targetId = document.getElementById('player-list').value;
            if (targetId && confirm('Sei sicuro di voler bannare questo giocatore?')) {
                this.sendAction('ban_player', { targetId });
            }
        });

        // Eventi Mondo
        document.getElementById('btn-freeze').addEventListener('click', () => {
            this.sendAction('toggle_freeze_server', {});
        });

        document.getElementById('btn-event-xp').addEventListener('click', () => {
            this.sendAction('trigger_event', { eventType: 'double_xp', duration: 300 });
        });

        document.getElementById('btn-clear-entities').addEventListener('click', () => {
            this.sendAction('clear_entities', {});
        });
    }

    listenSocketEvents() {
        // Autenticazione e caricamento permessi admin
        this.socket.on('admin_auth_status', (data) => {
            if (data.authorized) {
                this.isAdmin = true;
                console.log('🔗 Accesso Pannello Admin autorizzato. Ruolo:', data.role);
            } else {
                this.isAdmin = false;
                this.togglePanel(false);
            }
        });

        // Feedback comandi admin
        this.socket.on('admin_feedback', (res) => {
            if (res.message) this.showFeedback(res.message, !res.success);
        });

        this.socket.on('error', (errMsg) => {
            this.showFeedback(errMsg, true);
        });

        // ASCOLTATORE ANNUNCI GLOBALI (Mostra il banner visivo a schermo)
        const handleAnnouncement = (data) => {
            const text = typeof data === 'string' ? data : (data.message || data.text || '');
            if (text) this.displayBroadcastNotification(text);
        };

        this.socket.on('server_announcement', handleAnnouncement);
        this.socket.on('global_broadcast', handleAnnouncement);

        // Aggiornamento telemetria
        this.socket.on('admin_telemetry_update', (data) => {
            document.getElementById('tele-online').textContent = data.onlineSockets;
            document.getElementById('tele-ram').textContent = `${data.ramUsedMB} MB`;
            document.getElementById('tele-uptime').textContent = `${data.uptime}s`;
        });

        // Aggiornamento dinamico della lista dei giocatori connessi
        this.socket.on('admin_players_update', (players) => {
            const select = document.getElementById('player-list');
            select.innerHTML = '<option value="">Seleziona Giocatore...</option>';
            players.forEach(p => {
                const option = document.createElement('option');
                option.value = p.id;
                option.textContent = `${p.username} (${p.role}) ${p.isMuted ? '[Muted]' : ''}`;
                select.appendChild(option);
            });
        });
    }

    togglePanel(forceState = null) {
        if (!this.isAdmin) return;
        this.isOpen = forceState !== null ? forceState : !this.isOpen;
        this.container.style.display = this.isOpen ? 'flex' : 'none';

        if (this.isOpen) {
            this.socket.emit('admin_request_players');
            this.socket.emit('admin_request_telemetry');
        }
    }

    sendAction(action, payload) {
        this.socket.emit('admin_command', { action, payload });
    }
}