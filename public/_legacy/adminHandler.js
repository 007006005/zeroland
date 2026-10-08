// adminHandler.js - Server Backend (Node.js / Express / Socket.io)

// Ruoli riconosciuti sia in italiano che in inglese per garantire piena compatibilità
const STAFF_ROLES = ['moderatore', 'admin', 'founder', 'moderator'];

function getRoleRank(role) {
    const ranks = { 'user': 0, 'premium': 1, 'helper': 2, 'moderatore': 3, 'moderator': 3, 'admin': 4, 'founder': 5 };
    return ranks[role] || 0;
}

function setupAdminSystem(io, socket, userSession) {
    // 1. Verifica autenticazione e ruolo dello staff
    const userRole = userSession ? userSession.role : null;
    const isAuth = userSession && STAFF_ROLES.includes(userRole);

    socket.emit('admin_auth_status', { 
        authorized: isAuth,
        role: userRole,
        rank: getRoleRank(userRole)
    });

    if (!isAuth) return;

    // Log azione amministrativa
    const logAdminAction = (action, details) => {
        console.log(`[ADMIN AUDIT] [${new Date().toISOString()}] Admin: ${userSession.username} (${userRole}) | Action: ${action} | Details:`, details);
    };

    // 2. Invio lista giocatori aggiornata
    socket.on('admin_request_players', () => {
        const players = [];
        const sockets = io.sockets.sockets;
        sockets.forEach((s) => {
            if (s.user) {
                players.push({ 
                    id: s.id, 
                    username: s.user.username || 'Guest',
                    role: s.user.role || 'user',
                    isMuted: !!s.isMuted
                });
            }
        });
        socket.emit('admin_players_update', players);
    });

    // 3. Richiesta telemetria e statistiche server in tempo reale
    socket.on('admin_request_telemetry', () => {
        const memoryUsage = process.memoryUsage();
        const telemetry = {
            onlineSockets: io.sockets.sockets.size,
            uptime: Math.floor(process.uptime()),
            ramUsedMB: Math.round(memoryUsage.heapUsed / 1024 / 1024),
            serverTime: new Date().toLocaleTimeString('it-IT')
        };
        socket.emit('admin_telemetry_update', telemetry);
    });

    // 4. Gestione centralizzata dei comandi Admin con validazione rigida lato server
    socket.on('admin_command', ({ action, payload }) => {
        payload = payload || {};
        
        // Re-verificare l'autorizzazione su OGNI singolo pacchetto per prevenire escalation o bypass lato client
        if (!userSession || !STAFF_ROLES.includes(userSession.role)) {
            return socket.emit('error', 'Azione non autorizzata. Accesso negato.');
        }

        const myRank = getRoleRank(userSession.role);

        switch (action) {
            case 'toggle_godmode':
                if (socket.gameState) {
                    socket.gameState.isInvincible = !!payload.active;
                    logAdminAction('toggle_godmode', { active: payload.active });
                    socket.emit('admin_feedback', { success: true, message: `God Mode ${payload.active ? 'attivata' : 'disattivata'}.` });
                }
                break;

            case 'teleport':
                if (socket.gameState && typeof payload.x === 'number' && typeof payload.y === 'number') {
                    socket.gameState.x = payload.x;
                    socket.gameState.y = payload.y;
                    io.emit('player_moved', { id: socket.id, x: payload.x, y: payload.y });
                    logAdminAction('teleport', { x: payload.x, y: payload.y });
                }
                break;

            case 'kick_player': {
                const targetSocket = io.sockets.sockets.get(payload.targetId);
                if (targetSocket) {
                    const targetRank = getRoleRank(targetSocket.user ? targetSocket.user.role : 'user');
                    if (targetRank >= myRank && targetSocket.id !== socket.id) {
                        return socket.emit('error', 'Non puoi espellere un membro dello staff di pari grado o superiore.');
                    }
                    targetSocket.emit('kicked', 'Sei stato rimosso dal server da un amministratore.');
                    targetSocket.disconnect(true);
                    logAdminAction('kick_player', { targetId: payload.targetId });
                    socket.emit('admin_feedback', { success: true, message: 'Giocatore espulso con successo.' });
                } else {
                    socket.emit('error', 'Giocatore non trovato o disconnesso.');
                }
                break;
            }

            case 'ban_player': {
                if (myRank < getRoleRank('admin')) {
                    return socket.emit('error', 'Serve il ruolo Admin o superiore per bannare.');
                }
                const targetSocket = io.sockets.sockets.get(payload.targetId);
                if (targetSocket) {
                    const targetRank = getRoleRank(targetSocket.user ? targetSocket.user.role : 'user');
                    if (targetRank >= myRank && targetSocket.id !== socket.id) {
                        return socket.emit('error', 'Non puoi bannare un membro dello staff di pari grado o superiore.');
                    }
                    targetSocket.emit('banned', 'Sei stato bannato dal server.');
                    targetSocket.disconnect(true);
                    logAdminAction('ban_player', { targetId: payload.targetId });
                    socket.emit('admin_feedback', { success: true, message: 'Giocatore bannato ed espulso.' });
                }
                break;
            }

            case 'toggle_mute': {
                const targetSocket = io.sockets.sockets.get(payload.targetId);
                if (targetSocket) {
                    targetSocket.isMuted = !targetSocket.isMuted;
                    targetSocket.emit('mute_status_changed', { isMuted: targetSocket.isMuted });
                    logAdminAction('toggle_mute', { targetId: payload.targetId, isMuted: targetSocket.isMuted });
                    socket.emit('admin_feedback', { success: true, message: `Stato mute aggiornato per il giocatore.` });
                }
                break;
            }

            case 'broadcast_message': {
                const msgText = String(payload.message || '').trim();
                if (!msgText) {
                    return socket.emit('error', 'Il messaggio non può essere vuoto.');
                }
                
                const announcement = {
                    sender: 'AMMINISTRAZIONE',
                    message: msgText,
                    text: msgText,
                    type: 'system',
                    timestamp: new Date().toISOString()
                };

                // Invio multi-canale per garantire la ricezione da parte di tutti i client
                io.emit('server_announcement', announcement);
                io.emit('global_broadcast', announcement);
                io.emit('chat_message', announcement);

                logAdminAction('broadcast_message', { message: msgText });
                socket.emit('admin_feedback', { success: true, message: 'Annuncio globale trasmesso a tutti i giocatori.' });
                break;
            }

            case 'toggle_freeze_server': {
                if (myRank < getRoleRank('admin')) return socket.emit('error', 'Ruolo Admin richiesto.');
                global.isServerFrozen = !global.isServerFrozen;
                io.emit('server_freeze_state', { frozen: global.isServerFrozen });
                logAdminAction('toggle_freeze_server', { frozen: global.isServerFrozen });
                socket.emit('admin_feedback', { success: true, message: `Partita ${global.isServerFrozen ? 'CONGELATA' : 'SBLOCCATA'}.` });
                break;
            }

            case 'trigger_event': {
                if (myRank < getRoleRank('admin')) return socket.emit('error', 'Ruolo Admin richiesto.');
                const eventType = payload.eventType || 'double_xp';
                io.emit('global_event_started', { eventType, durationSec: payload.duration || 300 });
                logAdminAction('trigger_event', { eventType });
                socket.emit('admin_feedback', { success: true, message: `Evento "${eventType}" avviato!` });
                break;
            }

            case 'clear_entities':
                if (global.gameState) {
                    global.gameState.entities = [];
                    io.emit('sync_entities', global.gameState.entities);
                    logAdminAction('clear_entities', {});
                    socket.emit('admin_feedback', { success: true, message: 'Tutte le entità del mondo sono state rimosse.' });
                }
                break;

            default:
                console.log(`[ADMIN] Comando non riconosciuto: ${action}`);
                socket.emit('error', 'Comando non valido.');
                break;
        }
    });
}

module.exports = { setupAdminSystem };