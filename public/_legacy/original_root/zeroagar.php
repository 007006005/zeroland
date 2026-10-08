<?php
/**
 * ZEROAGAR v3.5 - Cyber Arena con Autenticazione & Sincronizzazione Centralizzata
 * Dominio: https://engine.master-elettroerosioni.it/
 * Database Centralizzato & Sessione Condivisa con: https://www.master-elettroerosioni.it/
 */

header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/api/db.php';

// Identità e statistiche arrivano dalla sessione + database (non più da un payload nell'URL)
$authUser = require_login_page();

$player_name  = $authUser['username'];
$player_clan  = $authUser['clan'] ?? 'ZERO';
$player_color = $authUser['equipped']['cell_skin'] ?? '#00f0ff';
if (!preg_match('/^#[0-9a-fA-F]{6}$/', (string)$player_color)) $player_color = '#00f0ff';
$player_coins = $authUser['coins'];
$player_level = $authUser['level'];
$player_xp    = $authUser['xp'];
$portal_url   = 'index.php';
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <title>ZeroAgar v3.5 - Cyber Arena</title>
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;800&family=Rajdhani:wght@500;700&display=swap" rel="stylesheet" />
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; user-select: none; }
    body, html { width: 100%; height: 100%; overflow: hidden; background: #03050d; font-family: 'Rajdhani', sans-serif; color: #fff; }
    #game-canvas { display: block; width: 100vw; height: 100vh; background: #050814; }
    
    /* TOP HUD BAR */
    #sync-hud-bar {
      position: absolute; top: 12px; left: 50%; transform: translateX(-50%);
      z-index: 50; display: flex; align-items: center; gap: 14px;
      background: rgba(6, 12, 32, 0.9); backdrop-filter: blur(10px);
      border: 1px solid rgba(0, 240, 255, 0.4); padding: 8px 18px; border-radius: 30px;
      box-shadow: 0 0 20px rgba(0, 240, 255, 0.25); font-family: 'Orbitron', sans-serif;
    }
    .hud-pill { display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 700; }
    .hud-pill.coins { color: #ffd700; text-shadow: 0 0 10px rgba(255, 215, 0, 0.5); }
    .hud-pill.level { color: #00f0ff; }
    .hud-pill.user { color: #fff; border-right: 1px solid rgba(255,255,255,0.15); padding-right: 12px; }
    .btn-portal {
      background: linear-gradient(135deg, rgba(0, 240, 255, 0.2), rgba(157, 0, 255, 0.3));
      border: 1px solid #00f0ff; color: #00f0ff; padding: 5px 12px; border-radius: 14px;
      font-size: 11px; font-family: 'Orbitron', sans-serif; cursor: pointer; text-decoration: none;
      transition: all .2s;
    }
    .btn-portal:hover { background: #00f0ff; color: #000; box-shadow: 0 0 15px #00f0ff; }

    /* LOBBY MODAL */
    #lobby-overlay {
      position: absolute; inset: 0; z-index: 100;
      background: rgba(3, 5, 15, 0.88); backdrop-filter: blur(12px);
      display: flex; align-items: center; justify-content: center;
    }
    .lobby-card {
      width: 480px; max-width: 92vw; background: rgba(10, 16, 38, 0.94);
      border: 1px solid rgba(0, 240, 255, 0.4); border-radius: 16px;
      box-shadow: 0 0 35px rgba(0, 240, 255, 0.25); padding: 28px;
    }
    .lobby-title { font-family: 'Orbitron', sans-serif; font-size: 30px; font-weight: 800; color: #00f0ff; text-align: center; text-shadow: 0 0 15px #00f0ff; margin-bottom: 8px; }
    .lobby-subtitle { text-align: center; font-size: 13px; color: #8a99ad; margin-bottom: 20px; }
    
    .sync-badge {
      display: flex; align-items: center; justify-content: space-between;
      background: rgba(0, 240, 255, 0.08); border: 1px solid rgba(0, 240, 255, 0.3);
      padding: 10px 14px; border-radius: 10px; margin-bottom: 18px; font-size: 13px;
    }
    .tab-bar { display: flex; gap: 8px; margin-bottom: 18px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 8px; }
    .tab-btn { flex: 1; padding: 8px; background: transparent; border: none; color: #8a99ad; font-family: 'Orbitron', sans-serif; font-size: 12px; cursor: pointer; border-radius: 6px; }
    .tab-btn.active { background: rgba(0, 240, 255, 0.15); color: #00f0ff; border: 1px solid rgba(0, 240, 255, 0.4); }
    .form-group { margin-bottom: 14px; }
    .form-group label { display: block; font-size: 12px; color: #8a99ad; margin-bottom: 5px; font-family: 'Orbitron', sans-serif; }
    .input-field { width: 100%; padding: 12px; background: rgba(4, 8, 22, 0.8); border: 1px solid rgba(0, 240, 255, 0.3); border-radius: 8px; color: #fff; font-size: 15px; outline: none; }
    .input-field:focus { border-color: #00f0ff; box-shadow: 0 0 10px rgba(0, 240, 255, 0.3); }
    .btn-play { width: 100%; padding: 14px; margin-top: 10px; background: linear-gradient(135deg, #00f0ff, #0077ff); border: none; border-radius: 8px; color: #000; font-family: 'Orbitron', sans-serif; font-size: 16px; font-weight: 800; cursor: pointer; box-shadow: 0 0 20px rgba(0, 240, 255, 0.4); }
    .btn-play:hover { transform: translateY(-2px); box-shadow: 0 0 30px rgba(0, 240, 255, 0.6); }

    .hud-layer { position: absolute; inset: 0; pointer-events: none; z-index: 10; }
    .hud-layer * { pointer-events: auto; }
    .top-bar { position: absolute; top: 15px; left: 15px; display: flex; gap: 12px; align-items: center; }
    .badge-info { background: rgba(6, 12, 30, 0.75); border: 1px solid rgba(0, 240, 255, 0.3); padding: 8px 16px; border-radius: 20px; font-family: 'Orbitron', sans-serif; font-size: 13px; color: #00f0ff; }
    #killfeed { position: absolute; top: 70px; right: 15px; width: 260px; display: flex; flex-direction: column; gap: 6px; pointer-events: none; }
    .kf-item { background: rgba(15, 5, 25, 0.85); border-left: 3px solid #00f0ff; padding: 6px 10px; border-radius: 4px; font-size: 12px; }
    #radar-canvas { position: absolute; bottom: 15px; right: 15px; width: 150px; height: 150px; background: rgba(4, 8, 22, 0.85); border: 1px solid rgba(0, 240, 255, 0.4); border-radius: 12px; }

    #sync-toast {
      position: absolute; bottom: 30px; left: 50%; transform: translateX(-50%);
      background: rgba(0, 240, 255, 0.95); color: #030614; font-weight: 700;
      padding: 10px 22px; border-radius: 20px; font-family: 'Orbitron', sans-serif;
      font-size: 13px; box-shadow: 0 0 25px rgba(0, 240, 255, 0.7);
      opacity: 0; pointer-events: none; transition: opacity .3s, transform .3s; z-index: 999;
    }
    #sync-toast.show { opacity: 1; transform: translateX(-50%) translateY(-10px); }
  </style>
</head>
<body>

  <!-- SYNC PROFILE BAR -->
  <div id="sync-hud-bar">
    <div class="hud-pill user">
      <span>👤</span>
      <span id="sync-user-name"><?= htmlspecialchars((string)$player_name, ENT_QUOTES, 'UTF-8') ?></span>
      <span style="color:#00f0ff; font-size:11px;">[<?= htmlspecialchars((string)$player_clan, ENT_QUOTES, 'UTF-8') ?>]</span>
    </div>
    <div class="hud-pill level">⭐ LVL <span id="sync-user-level"><?= htmlspecialchars((string)$player_level, ENT_QUOTES, 'UTF-8') ?></span></div>
    <div class="hud-pill coins">🪙 <span id="sync-user-coins"><?= htmlspecialchars((string)$player_coins, ENT_QUOTES, 'UTF-8') ?></span> ZC</div>
    <a href="<?= htmlspecialchars($portal_url) ?>" class="btn-portal">↩️ PORTALE ARCADE</a>
  </div>

  <div id="sync-toast">🔄 Sincronizzato con il Portale!</div>

  <!-- LOBBY OVERLAY -->
  <div id="lobby-overlay">
    <div class="lobby-card">
      <div class="lobby-title">ZEROAGAR 3.5</div>
      <div class="lobby-subtitle">Arena Cyber - Autenticazione &amp; Sincronizzazione Centralizzata</div>

      <div class="sync-badge">
        <div>
          <span style="color:#00ff66;">● Account Collegato:</span> <b><span id="card-nick"><?= htmlspecialchars((string)$player_name, ENT_QUOTES, 'UTF-8') ?></span></b>
        </div>
        <div>
          🪙 <b><span id="card-coins"><?= htmlspecialchars((string)$player_coins, ENT_QUOTES, 'UTF-8') ?></span> ZC</b> | ⭐ <b>Lv. <span id="card-level"><?= htmlspecialchars((string)$player_level, ENT_QUOTES, 'UTF-8') ?></span></b>
        </div>
      </div>
      
      <div class="tab-bar">
        <button class="tab-btn active" onclick="switchTab('play')">GIOCA</button>
        <button class="tab-btn" onclick="switchTab('skin')">SKIN &amp; CLAN</button>
      </div>

      <!-- TAB GIOCA -->
      <div id="tab-play">
        <div class="form-group">
          <label>NICKNAME</label>
          <input type="text" id="input-nick" class="input-field" value="<?= htmlspecialchars((string)$player_name, ENT_QUOTES, 'UTF-8') ?>" maxlength="20" />
        </div>
        <div class="form-group">
          <label>TAG CLAN</label>
          <input type="text" id="input-clan" class="input-field" value="<?= htmlspecialchars((string)$player_clan, ENT_QUOTES, 'UTF-8') ?>" maxlength="6" />
        </div>
        <div class="form-group">
          <label>MODALITÀ ONLINE</label>
          <select id="l-mode" class="input-field"><option value="ffa">Tutti contro tutti</option><option value="teams">Squadre (rossi/blu)</option><option value="party">Party (codice stanza)</option><option value="experimental">Experimental</option></select>
        </div>
        <div class="form-group" id="l-codew" style="display:none;">
          <label>CODICE STANZA</label>
          <input type="text" id="l-code" class="input-field" maxlength="12" />
        </div>
        <div class="form-group">
          <label style="display:flex;gap:8px;align-items:center;"><input type="checkbox" id="l-on" /> GIOCA CON ALTRI GIOCATORI ONLINE</label>
        </div>
        <button class="btn-play" onclick="startGame()">ENTRA NELL'ARENA</button>
      </div>

      <!-- TAB SKIN -->
      <div id="tab-skin" style="display:none;">
        <div class="form-group">
          <label>COLORE NEON</label>
          <input type="color" id="input-color" value="<?= htmlspecialchars((string)$player_color, ENT_QUOTES, 'UTF-8') ?>" style="width:100%; height:44px; border:none; background:transparent; cursor:pointer;" />
        </div>
        <div class="form-group">
          <label>SKIN IMMAGINE (PNG/JPG/WEBP/GIF, max 10 MB)</label>
          <input type="file" id="l-file" accept="image/png,image/jpeg,image/webp,image/gif" style="color:#8a99ad;font-size:12px;" />
          <div id="l-msg" style="font-size:12px;color:#8a99ad;margin-top:6px;"></div>
        </div>
        <div class="form-group">
          <label>SKIN FACCINA</label>
          <select id="l-emoji" class="input-field"><option value="">nessuna</option><option>😎</option><option>👾</option><option>🔥</option><option>🐉</option><option>🦄</option><option>💀</option><option>🤖</option><option>⭐</option><option>🐸</option></select>
        </div>
      </div>
    </div>
  </div>

  <div class="hud-layer">
    <div class="top-bar">
      <div class="badge-info">MASSA: <span id="hud-mass">0</span></div>
      <div class="badge-info">PING: <span id="hud-ping">18</span> ms</div>
      <div class="badge-info">FPS: <span id="hud-fps">60</span></div>
    </div>
    <div id="killfeed"></div>
    <canvas id="radar-canvas"></canvas>
  </div>

  <canvas id="game-canvas"></canvas>

  <script>
    const PORTAL_API_URL = (function() {
      let p = window.location.pathname;
      if (!p.endsWith('/') && !p.split('/').pop().includes('.')) p += '/';
      const dir = p.substring(0, p.lastIndexOf('/') + 1);
      return (dir || './') + 'api';
    })();

    // Audio Engine
    const audioCtx = (typeof window !== 'undefined' && (window.AudioContext || window.webkitAudioContext)) ? new (window.AudioContext || window.webkitAudioContext)() : null;
    function playTone(freq, type, duration, gainVal = 0.1) {
      if (!audioCtx) return;
      try {
        if (audioCtx.state === 'suspended') audioCtx.resume();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.type = type;
        osc.frequency.setValueAtTime(freq, audioCtx.currentTime);
        gain.gain.setValueAtTime(gainVal, audioCtx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + duration);
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.start();
        osc.stop(audioCtx.currentTime + duration);
      } catch (e) {}
    }
    const playPop = () => playTone(540, 'sine', 0.08, 0.08);
    const playEat = () => playTone(320, 'triangle', 0.25, 0.2);
    let playSplitSound = () => playTone(680, 'sawtooth', 0.15, 0.12);

    function switchTab(t) {
      ['play', 'skin'].forEach(id => {
        const el = document.getElementById('tab-' + id);
        if (el) el.style.display = (id === t) ? 'block' : 'none';
      });
      document.querySelectorAll('.tab-btn').forEach((b, i) => {
        b.classList.toggle('active', (i === 0 && t === 'play') || (i === 1 && t === 'skin'));
      });
    }

    function showSyncToast(text) {
      const toast = document.getElementById('sync-toast');
      if (text) toast.innerText = text;
      toast.classList.add('show');
      setTimeout(() => toast.classList.remove('show'), 2800);
    }

    // Engine Canvas & World
    const canvas = document.getElementById('game-canvas');
    const ctx = canvas.getContext('2d');
    const radarCanvas = document.getElementById('radar-canvas');
    const radarCtx = radarCanvas.getContext('2d');

    const MAP_SIZE = 3400;
    const FOOD_COUNT = 360;
    const BOT_COUNT = 14;
    const VIRUS_COUNT = 14;

    const BOT_NAMES = ['NovaKid', 'BlobQueen', 'Zyphra', 'MrSplit', 'GhostOrbit', 'PlasmaPop', 'CellDoc', 'ZeroFan', 'ApexPredator', 'NeonStriker', 'TitanV', 'Vortex9'];
    const BOT_COLORS = ['#ff0055', '#ff9900', '#00ff88', '#9900ff', '#00ddff', '#ff00aa', '#ffff00', '#00ffcc'];
    const FOOD_COLORS = ['#00f0ff', '#ff007f', '#00ff66', '#ffd700', '#a855f7', '#ff6600'];

    let isGameRunning = false;
    let mouseX = MAP_SIZE / 2;
    let mouseY = MAP_SIZE / 2;
    let cameraX = MAP_SIZE / 2;
    let cameraY = MAP_SIZE / 2;
    let cameraZoom = 1;

    let playerCells = [];
    let foods = [];
    let viruses = [];
    let bots = [];
    let ejectedPellets = [];


    /* ===== GRAFICA 4.0: temi, particelle, luci ===== */
    const THEMES = {
      neon:   { name: 'Neon Cyan', bg0: '#0b1536', bg1: '#02040c', grid: 'rgba(0, 240, 255, 0.08)', border: '#00f0ff', star: '#8fdcff' },
      aurora: { name: 'Aurora',    bg0: '#1c1046', bg1: '#030610', grid: 'rgba(168, 85, 247, 0.10)', border: '#a855f7', star: '#c4b5fd' },
      sunset: { name: 'Tramonto',  bg0: '#2d1026', bg1: '#0a0309', grid: 'rgba(255, 122, 80, 0.09)', border: '#ff7a50', star: '#ffd0b0' },
      matrix: { name: 'Matrix',    bg0: '#06260f', bg1: '#010a04', grid: 'rgba(0, 255, 102, 0.09)', border: '#00ff66', star: '#9dffc0' }
    };
    const GFX = { theme: 'neon', quality: 'high', particles: [], t: 0 };
    try { const sv = JSON.parse(localStorage.getItem('zaGfx') || '{}'); if (THEMES[sv.theme]) GFX.theme = sv.theme; if (sv.quality === 'low' || sv.quality === 'high') GFX.quality = sv.quality; } catch (e) {}
    const STARS = Array.from({ length: 110 }, () => ({ x: Math.random(), y: Math.random(), r: Math.random() * 1.5 + 0.3, p: Math.random() * 0.6 + 0.1, ph: Math.random() * 6.28 }));
    function burst(x, y, color, n, sp) {
      if (GFX.quality === 'low') return;
      for (let i = 0; i < n && GFX.particles.length < 450; i++) {
        const a = Math.random() * 6.283, v = (0.4 + Math.random()) * sp;
        GFX.particles.push({ x, y, vx: Math.cos(a) * v, vy: Math.sin(a) * v, life: 1, decay: 0.018 + Math.random() * 0.025, r: 1.5 + Math.random() * 3, color });
      }
    }
    function drawFx() {
      const P = GFX.particles;
      for (let i = P.length - 1; i >= 0; i--) {
        const q = P[i]; q.x += q.vx; q.y += q.vy; q.vx *= 0.96; q.vy *= 0.96; q.life -= q.decay;
        if (q.life <= 0) { P.splice(i, 1); continue; }
        ctx.globalAlpha = Math.max(0, q.life); ctx.fillStyle = q.color;
        ctx.beginPath(); ctx.arc(q.x, q.y, q.r * q.life + 0.5, 0, 6.283); ctx.fill();
      }
      ctx.globalAlpha = 1;
    }
    function shine(x, y, r) {
      if (GFX.quality === 'low') return;
      const g = ctx.createRadialGradient(x - r * 0.35, y - r * 0.4, r * 0.05, x, y, r);
      g.addColorStop(0, 'rgba(255,255,255,0.40)'); g.addColorStop(0.55, 'rgba(255,255,255,0)'); g.addColorStop(1, 'rgba(0,0,0,0.30)');
      ctx.beginPath(); ctx.arc(x, y, r, 0, 6.283); ctx.fillStyle = g; ctx.fill();
    }

    let matchStats = {
      startTime: Date.now(),
      peakMass: 25,
      coinsEarned: 0,
      xpEarned: 0,
      rivals: 0,
      particles: 0,
      sentRivals: 0,
      sentParticles: 0
    };

    function resizeCanvas() {
      canvas.width = window.innerWidth;
      canvas.height = window.innerHeight;
      radarCanvas.width = 150;
      radarCanvas.height = 150;
    }
    window.addEventListener('resize', resizeCanvas);
    resizeCanvas();

    function initGameWorld() {
      const pName = document.getElementById('input-nick').value.trim() || 'master';
      const pClan = document.getElementById('input-clan').value.trim() || 'ZERO';
      const pColor = document.getElementById('input-color').value || '#00f0ff';

      playerCells = [{
        x: MAP_SIZE / 2 + (Math.random() * 200 - 100),
        y: MAP_SIZE / 2 + (Math.random() * 200 - 100),
        radius: 26,
        mass: 30,
        color: pColor,
        name: pName,
        clan: pClan,
        vx: 0,
        vy: 0
      }];

      foods = [];
      for (let i = 0; i < FOOD_COUNT; i++) {
        foods.push({
          x: Math.random() * (MAP_SIZE - 80) + 40,
          y: Math.random() * (MAP_SIZE - 80) + 40,
          radius: 3.5,
          color: FOOD_COLORS[Math.floor(Math.random() * FOOD_COLORS.length)]
        });
      }

      viruses = [];
      for (let i = 0; i < VIRUS_COUNT; i++) {
        viruses.push({
          x: Math.random() * (MAP_SIZE - 200) + 100,
          y: Math.random() * (MAP_SIZE - 200) + 100,
          radius: 42
        });
      }

      bots = [];
      for (let i = 0; i < BOT_COUNT; i++) {
        const m = Math.floor(Math.random() * 80) + 25;
        bots.push({
          id: 'bot_' + i,
          name: BOT_NAMES[i % BOT_NAMES.length],
          clan: 'AI',
          x: Math.random() * (MAP_SIZE - 200) + 100,
          y: Math.random() * (MAP_SIZE - 200) + 100,
          mass: m,
          radius: Math.sqrt(m * 22) + 10,
          color: BOT_COLORS[i % BOT_COLORS.length],
          targetX: Math.random() * MAP_SIZE,
          targetY: Math.random() * MAP_SIZE,
          vx: 0,
          vy: 0
        });
      }

      ejectedPellets = [];
      matchStats = {
        startTime: Date.now(),
        peakMass: 30,
        coinsEarned: 0,
        xpEarned: 0,
        rivals: 0,
        particles: 0,
        sentRivals: 0,
        sentParticles: 0
      };

      isGameRunning = true;
    }

    window.addEventListener('mousemove', (e) => {
      mouseX = e.clientX;
      mouseY = e.clientY;
    });

    window.addEventListener('keydown', (e) => {
      if (!isGameRunning) return;
      if (e.code === 'Space') {
        splitPlayerCells();
      } else if (e.code === 'KeyW') {
        ejectPlayerMass();
      }
    });

    function splitPlayerCells() {
      if (playerCells.length >= 16) return;
      const newCells = [];
      playerCells.forEach(cell => {
        if (cell.mass >= 36) {
          const halfMass = Math.floor(cell.mass / 2);
          cell.mass = halfMass;
          cell.radius = Math.sqrt(halfMass * 22) + 8;

          const dx = mouseX - canvas.width / 2;
          const dy = mouseY - canvas.height / 2;
          const dist = Math.hypot(dx, dy) || 1;
          const nx = dx / dist;
          const ny = dy / dist;

          newCells.push({
            x: cell.x + nx * (cell.radius + 15),
            y: cell.y + ny * (cell.radius + 15),
            radius: cell.radius,
            mass: halfMass,
            color: cell.color,
            name: cell.name,
            clan: cell.clan,
            vx: nx * 14,
            vy: ny * 14
          });
          playSplitSound();
        }
      });
      playerCells.push(...newCells);
    }

    function ejectPlayerMass() {
      playerCells.forEach(cell => {
        if (cell.mass > 32) {
          cell.mass -= 10;
          cell.radius = Math.sqrt(cell.mass * 22) + 8;
          const dx = mouseX - canvas.width / 2;
          const dy = mouseY - canvas.height / 2;
          const dist = Math.hypot(dx, dy) || 1;
          const nx = dx / dist;
          const ny = dy / dist;

          ejectedPellets.push({
            x: cell.x + nx * (cell.radius + 10),
            y: cell.y + ny * (cell.radius + 10),
            vx: nx * 10,
            vy: ny * 10,
            radius: 8,
            mass: 10,
            color: cell.color
          });
        }
      });
    }

    function addKillfeed(killer, victim) {
      const feed = document.getElementById('killfeed');
      if (!feed) return;
      const item = document.createElement('div');
      item.className = 'kf-item';
      item.innerHTML = '<span style="color:#00f0ff;">' + killer + '</span> ha divorato <span style="color:#ff0055;">' + victim + '</span>';
      feed.prepend(item);
      setTimeout(() => item.remove(), 4500);
    }

    let frameCount = 0;
    let lastFpsUpdate = performance.now();

    function gameLoop(now) {
      requestAnimationFrame(gameLoop);

      frameCount++;
      if (now - lastFpsUpdate >= 1000) {
        document.getElementById('hud-fps').innerText = frameCount;
        frameCount = 0;
        lastFpsUpdate = now;
      }

      if (!isGameRunning) {
        ctx.fillStyle = '#03050e';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        return;
      }

      let totalX = 0;
      let totalY = 0;
      let totalMass = 0;

      playerCells.forEach(cell => {
        cell.x += cell.vx;
        cell.y += cell.vy;
        cell.vx *= 0.92;
        cell.vy *= 0.92;

        const screenCenterX = canvas.width / 2;
        const screenCenterY = canvas.height / 2;
        const dx = (mouseX - screenCenterX) / cameraZoom;
        const dy = (mouseY - screenCenterY) / cameraZoom;
        const dist = Math.hypot(dx, dy);

        const speed = Math.max(1.8, 6.5 - Math.log(cell.mass) * 0.7);
        if (dist > 10) {
          cell.x += (dx / dist) * Math.min(speed, dist * 0.08);
          cell.y += (dy / dist) * Math.min(speed, dist * 0.08);
        }

        cell.x = Math.max(cell.radius, Math.min(MAP_SIZE - cell.radius, cell.x));
        cell.y = Math.max(cell.radius, Math.min(MAP_SIZE - cell.radius, cell.y));

        totalX += cell.x;
        totalY += cell.y;
        totalMass += cell.mass;
      });

      if (playerCells.length === 0) {
        isGameRunning = false;
        syncMatchProgress(true);
        document.getElementById('lobby-overlay').style.display = 'flex';
        showSyncToast('💀 Sei stato divorato! Punteggio salvato');
        return;
      }

      const avgX = totalX / playerCells.length;
      const avgY = totalY / playerCells.length;
      cameraX += (avgX - cameraX) * 0.1;
      cameraY += (avgY - cameraY) * 0.1;
      const targetZoom = Math.max(0.12, Math.min(2.2, Math.max(0.45, Math.min(1.2, 1.05 - (totalMass * 0.0003))) * (window.__zoomMul || 1)));
      cameraZoom += (targetZoom - cameraZoom) * 0.05;

      document.getElementById('hud-mass').innerText = totalMass;
      if (totalMass > matchStats.peakMass) matchStats.peakMass = totalMass;

      // Movimento Pellet
      ejectedPellets.forEach(pellet => {
        pellet.x += pellet.vx;
        pellet.y += pellet.vy;
        pellet.vx *= 0.94;
        pellet.vy *= 0.94;
      });

      // Movimento Bot
      bots.forEach(bot => {
        const bdx = bot.targetX - bot.x;
        const bdy = bot.targetY - bot.y;
        const bdist = Math.hypot(bdx, bdy);
        const bspeed = Math.max(1.5, 5.5 - Math.log(bot.mass) * 0.6);

        if (bdist < 40 || Math.random() < 0.015) {
          bot.targetX = Math.random() * (MAP_SIZE - 100) + 50;
          bot.targetY = Math.random() * (MAP_SIZE - 100) + 50;
        } else {
          bot.x += (bdx / bdist) * bspeed;
          bot.y += (bdy / bdist) * bspeed;
        }

        bot.x = Math.max(bot.radius, Math.min(MAP_SIZE - bot.radius, bot.x));
        bot.y = Math.max(bot.radius, Math.min(MAP_SIZE - bot.radius, bot.y));
      });

      // Mangia Cibo
      foods.forEach(food => {
        playerCells.forEach(cell => {
          if (Math.hypot(cell.x - food.x, cell.y - food.y) < cell.radius) {
            cell.mass += 1;
            cell.radius = Math.sqrt(cell.mass * 22) + 8;
            burst(food.x, food.y, food.color, 5, 2.4);
            matchStats.particles += 1;
            if (matchStats.particles % 5 === 0) matchStats.coinsEarned += 1;
            if (matchStats.particles % 3 === 0) matchStats.xpEarned += 2;

            food.x = Math.random() * (MAP_SIZE - 80) + 40;
            food.y = Math.random() * (MAP_SIZE - 80) + 40;
            food.color = FOOD_COLORS[Math.floor(Math.random() * FOOD_COLORS.length)];
            playPop();
          }
        });

        bots.forEach(bot => {
          if (Math.hypot(bot.x - food.x, bot.y - food.y) < bot.radius) {
            bot.mass += 1;
            bot.radius = Math.sqrt(bot.mass * 22) + 10;
            food.x = Math.random() * (MAP_SIZE - 80) + 40;
            food.y = Math.random() * (MAP_SIZE - 80) + 40;
          }
        });
      });

      // Scontri Giocatore / Bot
      bots.forEach(bot => {
        playerCells.forEach((cell, ci) => {
          const dist = Math.hypot(cell.x - bot.x, cell.y - bot.y);
          if (dist < Math.max(cell.radius, bot.radius)) {
            if (cell.mass > bot.mass * 1.15) {
              cell.mass += Math.floor(bot.mass * 0.7);
              cell.radius = Math.sqrt(cell.mass * 22) + 8;
              matchStats.rivals += 1;
              matchStats.coinsEarned += 15;
              matchStats.xpEarned += 50;
              addKillfeed(cell.name, bot.name);
              playEat();
              burst(bot.x, bot.y, bot.color, 30, 5.5);

              bot.x = Math.random() * (MAP_SIZE - 200) + 100;
              bot.y = Math.random() * (MAP_SIZE - 200) + 100;
              bot.mass = 30;
              bot.radius = Math.sqrt(bot.mass * 22) + 10;
            } else if (bot.mass > cell.mass * 1.15) {
              bot.mass += Math.floor(cell.mass * 0.7);
              bot.radius = Math.sqrt(bot.mass * 22) + 10;
              addKillfeed(bot.name, cell.name);
              playerCells.splice(ci, 1);
            }
          }
        });
      });

      // Rendering
      const TH = THEMES[GFX.theme] || THEMES.neon; GFX.t = performance.now();
      const hiQ = GFX.quality !== 'low';
      const bgG = ctx.createRadialGradient(canvas.width / 2, canvas.height / 2, 0, canvas.width / 2, canvas.height / 2, Math.max(canvas.width, canvas.height) * 0.75);
      bgG.addColorStop(0, TH.bg0); bgG.addColorStop(1, TH.bg1);
      ctx.fillStyle = bgG;
      ctx.fillRect(0, 0, canvas.width, canvas.height);
      if (hiQ) {
        ctx.fillStyle = TH.star;
        STARS.forEach(st => {
          const sx = (((st.x * canvas.width - cameraX * st.p * 0.2) % canvas.width) + canvas.width) % canvas.width;
          const sy = (((st.y * canvas.height - cameraY * st.p * 0.2) % canvas.height) + canvas.height) % canvas.height;
          ctx.globalAlpha = 0.35 + 0.35 * Math.sin(GFX.t / 700 + st.ph);
          ctx.beginPath(); ctx.arc(sx, sy, st.r, 0, 6.283); ctx.fill();
        });
        ctx.globalAlpha = 1;
      }

      ctx.save();
      ctx.translate(canvas.width / 2, canvas.height / 2);
      ctx.scale(cameraZoom, cameraZoom);
      ctx.translate(-cameraX, -cameraY);

      ctx.strokeStyle = TH.grid;
      ctx.lineWidth = 1.5;
      const gridSize = 100;
      for (let x = 0; x <= MAP_SIZE; x += gridSize) {
        ctx.beginPath();
        ctx.moveTo(x, 0);
        ctx.lineTo(x, MAP_SIZE);
        ctx.stroke();
      }
      for (let y = 0; y <= MAP_SIZE; y += gridSize) {
        ctx.beginPath();
        ctx.moveTo(0, y);
        ctx.lineTo(MAP_SIZE, y);
        ctx.stroke();
      }

      ctx.strokeStyle = TH.border;
      ctx.lineWidth = 6;
      if (hiQ) { ctx.shadowColor = TH.border; ctx.shadowBlur = 24 + 10 * Math.sin(GFX.t / 500); }
      ctx.strokeRect(0, 0, MAP_SIZE, MAP_SIZE);
      ctx.shadowBlur = 0;

      foods.forEach(f => {
        if (hiQ) {
          ctx.globalAlpha = 0.22; ctx.beginPath();
          ctx.arc(f.x, f.y, f.radius * (1.9 + 0.3 * Math.sin(GFX.t / 300 + f.x)), 0, 6.283);
          ctx.fillStyle = f.color; ctx.fill(); ctx.globalAlpha = 1;
        }
        ctx.beginPath();
        ctx.arc(f.x, f.y, f.radius, 0, Math.PI * 2);
        ctx.fillStyle = f.color;
        ctx.fill();
      });

      ejectedPellets.forEach(p => {
        ctx.beginPath();
        ctx.arc(p.x, p.y, p.radius, 0, Math.PI * 2);
        ctx.fillStyle = p.color;
        ctx.fill();
      });

      viruses.forEach(v => {
        ctx.save();
        ctx.translate(v.x, v.y);
        if (hiQ) ctx.rotate(GFX.t / 5000);
        ctx.beginPath();
        const spikes = 16;
        for (let i = 0; i < spikes * 2; i++) {
          const r = (i % 2 === 0) ? v.radius : v.radius - 8;
          const angle = (i * Math.PI) / spikes;
          const px = Math.cos(angle) * r;
          const py = Math.sin(angle) * r;
          if (i === 0) ctx.moveTo(px, py);
          else ctx.lineTo(px, py);
        }
        ctx.closePath();
        ctx.fillStyle = 'rgba(0, 255, 102, 0.45)';
        ctx.fill();
        ctx.strokeStyle = '#00ff66';
        ctx.lineWidth = 3;
        ctx.stroke();
        ctx.restore();
      });

      bots.forEach(bot => {
        ctx.beginPath();
        ctx.arc(bot.x, bot.y, bot.radius, 0, Math.PI * 2);
        ctx.fillStyle = bot.color;
        ctx.fill();
        shine(bot.x, bot.y, bot.radius);
        ctx.strokeStyle = 'rgba(255, 255, 255, 0.4)';
        ctx.lineWidth = 3;
        ctx.stroke();

        ctx.fillStyle = '#ffffff';
        ctx.font = 'bold ' + Math.max(11, bot.radius * 0.32) + "px 'Orbitron', sans-serif";
        ctx.textAlign = 'center';
        ctx.fillText(bot.name, bot.x, bot.y + 4);
      });

      playerCells.forEach(cell => {
        ctx.shadowColor = cell.color;
        ctx.shadowBlur = 20;
        ctx.beginPath();
        ctx.arc(cell.x, cell.y, cell.radius, 0, Math.PI * 2);
        ctx.fillStyle = cell.color;
        ctx.fill();
        ctx.shadowBlur = 0;
        shine(cell.x, cell.y, cell.radius);

        ctx.strokeStyle = '#ffffff';
        ctx.lineWidth = 3.5;
        ctx.stroke();

        ctx.fillStyle = '#ffffff';
        ctx.font = 'bold ' + Math.max(12, cell.radius * 0.3) + "px 'Orbitron', sans-serif";
        ctx.textAlign = 'center';
        ctx.fillText(cell.name, cell.x, cell.y - 2);

        ctx.fillStyle = '#00f0ff';
        ctx.font = 'bold ' + Math.max(9, cell.radius * 0.22) + "px 'Rajdhani', sans-serif";
        ctx.fillText('[' + cell.clan + '] ' + cell.mass + ' M', cell.x, cell.y + cell.radius * 0.35);
      });

      drawFx();
      ctx.restore();
      drawRadarMinimap();
    }

    function drawRadarMinimap() {
      radarCtx.fillStyle = 'rgba(4, 8, 22, 0.9)';
      radarCtx.fillRect(0, 0, 150, 150);
      radarCtx.strokeStyle = 'rgba(0, 240, 255, 0.5)';
      radarCtx.strokeRect(0, 0, 150, 150);

      const scale = 150 / MAP_SIZE;

      radarCtx.fillStyle = '#ff0055';
      bots.forEach(b => {
        radarCtx.fillRect(b.x * scale - 1.5, b.y * scale - 1.5, 3, 3);
      });

      radarCtx.fillStyle = '#00f0ff';
      playerCells.forEach(p => {
        radarCtx.beginPath();
        radarCtx.arc(p.x * scale, p.y * scale, 3.5, 0, Math.PI * 2);
        radarCtx.fill();
      });
    }

    async function syncMatchProgress(isFinal = false) {
      if (matchStats.coinsEarned === 0 && matchStats.xpEarned === 0 && !isFinal) return;

      const pName = document.getElementById('input-nick').value.trim() || 'master';
      const pClan = document.getElementById('input-clan').value.trim() || 'ZERO';
      const pSkin = document.getElementById('input-color').value || '#00f0ff';

      const payload = {
        action: 'submit_game_result',
        game_id: 'zero_agar',
        score: matchStats.peakMass * 10,
        coins_earned: matchStats.coinsEarned,
        kills: matchStats.rivals - matchStats.sentRivals,       // solo i nuovi rivali dall'ultimo invio
        match_rivals: matchStats.rivals,                        // totale partita (per gli obiettivi)
        particles_delta: matchStats.particles - matchStats.sentParticles,
        mass: matchStats.peakMass,
        final: isFinal,
        clan: pClan,
        skin: pSkin
      };

      try {
        const resp = await fetch(PORTAL_API_URL + '/games_sync.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const res = await resp.json();
        if (res && res.success) {
          matchStats.sentRivals = matchStats.rivals;
          matchStats.sentParticles = matchStats.particles;
          const lvl = document.getElementById('sync-user-level');
          const cns = document.getElementById('sync-user-coins');
          if (lvl && res.level !== undefined) lvl.innerText = res.level;
          if (cns && res.total_coins !== undefined) cns.innerText = res.total_coins;
          if (matchStats.coinsEarned > 0) {
            showSyncToast('🪙 +' + matchStats.coinsEarned + ' ZeroCoin salvati!');
          }
          matchStats.coinsEarned = 0;
          matchStats.xpEarned = 0;
        }
      } catch (e) {}
    }

    setInterval(() => {
      if (isGameRunning) syncMatchProgress(false);
    }, 12000);

    function startGame() {
      document.getElementById('lobby-overlay').style.display = 'none';
      initGameWorld();
      showSyncToast('🚀 Arena Avviata! Usa il mouse per muoverti, Spazio per dividerti');
    }

    requestAnimationFrame(gameLoop);
  </script>

  <!-- Global Arcade Chat Bottom-Left Widget in ZeroAgar standalone -->
  <div id="za-chat-container" style="position: fixed; bottom: 16px; left: 16px; z-index: 1000; font-family: 'Rajdhani', sans-serif;">
    <button id="za-chat-btn" onclick="toggleZaChat()" style="display: flex; align-items: center; gap: 8px; background: linear-gradient(135deg, #00f0ff, #0077ff); color: #000; font-weight: 800; font-size: 12px; text-transform: uppercase; border: 1px solid #00f0ff; border-radius: 24px; padding: 8px 16px; cursor: pointer; box-shadow: 0 0 15px rgba(0,240,255,0.4);">
      <span>💬</span>
      <span>Chat Globale</span>
      <span id="za-online-count" style="background: rgba(0,0,0,0.6); color: #00f0ff; font-size: 10px; padding: 2px 6px; border-radius: 10px;">1 Online</span>
    </button>

    <div id="za-chat-window" style="display: none; margin-top: 8px; width: 340px; height: 420px; background: rgba(8, 14, 30, 0.95); border: 1px solid rgba(0, 240, 255, 0.4); border-radius: 16px; box-shadow: 0 0 30px rgba(0,0,0,0.8); backdrop-filter: blur(10px); flex-direction: column; overflow: hidden;">
      <div style="padding: 10px 14px; background: rgba(3, 7, 18, 0.9); border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: space-between;">
        <span style="font-size: 12px; font-weight: 700; color: #00f0ff; text-transform: uppercase;">Chat Globale Arcade</span>
        <button onclick="toggleZaChat()" style="background: transparent; border: none; color: #8a99ad; font-size: 14px; cursor: pointer;">✕</button>
      </div>
      <div id="za-chat-messages" style="flex: 1; padding: 10px; overflow-y: auto; display: flex; flex-direction: column; gap: 8px; font-size: 12px;">
        <div style="text-align: center; color: #64748b; font-size: 11px;">Caricamento messaggi live...</div>
      </div>
      <form onsubmit="sendZaMessage(event)" style="padding: 8px; background: rgba(3, 7, 18, 0.8); border-top: 1px solid rgba(255,255,255,0.1); display: flex; gap: 6px;">
        <input id="za-chat-input" type="text" placeholder="Scrivi in chat..." style="flex: 1; padding: 8px 12px; background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(0, 240, 255, 0.3); border-radius: 8px; color: #fff; font-size: 12px; outline: none;" />
        <button type="submit" style="padding: 8px 14px; background: #00f0ff; border: none; border-radius: 8px; color: #000; font-weight: 700; font-size: 12px; cursor: pointer;">Invia</button>
      </form>
    </div>
  </div>

  <script>
    let isZaChatOpen = false;
    let zaSocket = null;

    function toggleZaChat() {
      isZaChatOpen = !isZaChatOpen;
      const w = document.getElementById('za-chat-window');
      w.style.display = isZaChatOpen ? 'flex' : 'none';
      if (isZaChatOpen) initZaChat();
      else if (zaPoll) { clearInterval(zaPoll); zaPoll = null; }
    }

    let zaPoll = null;
    function escZa(v) {
      return String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    function initZaChat() {
      // Apache/XAMPP non ha WebSocket: aggiornamento periodico via api/chat.php
      pollZaChat();
      if (!zaPoll) zaPoll = setInterval(pollZaChat, 3500);
    }

    async function pollZaChat() {
      try {
        const res = await fetch(PORTAL_API_URL + '/chat.php');
        const data = await res.json();
        if (data.success && data.messages) {
          const oc = document.getElementById('za-online-count');
          if (oc && data.online_count) oc.innerText = data.online_count + ' Online';
          const box = document.getElementById('za-chat-messages');
          box.innerHTML = '';
          data.messages.forEach(appendZaMessage);
        }
      } catch(e){}
    }

    function appendZaMessage(m) {
      const box = document.getElementById('za-chat-messages');
      if (!box) return;
      const d = document.createElement('div');
      d.style.cssText = 'padding: 6px 10px; background: rgba(15, 23, 42, 0.7); border-radius: 8px; border: 1px solid rgba(255,255,255,0.06);';
      d.innerHTML = `<span style="font-weight: 700; color: #00f0ff;">${escZa(m.avatar || '👾')} ${escZa(m.username || 'Pilota')}:</span> <span style="color: #cbd5e1;">${escZa(m.text)}</span>`;
      box.appendChild(d);
      box.scrollTop = box.scrollHeight;
    }

    function sendZaMessage(e) {
      e.preventDefault();
      const inp = document.getElementById('za-chat-input');
      const val = inp.value.trim();
      if (!val) return;
      fetch(PORTAL_API_URL + '/chat.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ type: 'message', text: val })
      }).then(pollZaChat).catch(() => {});
      inp.value = '';
    }
  </script>
<script id="agar-extras">
(function () {
  const PR = m => Math.sqrt(m * 22) + 8, BR = m => Math.sqrt(m * 22) + 10;
  const rnd = () => Math.random() * (MAP_SIZE - 200) + 100;
  const nm = () => (playerCells[0] && playerCells[0].name) || 'Tu';
  let wHeld = false, lastEj = 0, lastT = performance.now(), tick = 0, lbT = 0;

  /* ---- Comandi: W tenuto = espelli di continuo, E = split doppio, touch per mobile ---- */
  addEventListener('keydown', e => { if (e.code === 'KeyW') wHeld = true; if (e.code === 'KeyE' && isGameRunning) { splitPlayerCells(); splitPlayerCells(); } });
  addEventListener('keyup', e => { if (e.code === 'KeyW') wHeld = false; });
  addEventListener('touchmove', e => { const t = e.touches[0]; if (t) { mouseX = t.clientX; mouseY = t.clientY; } }, { passive: true });
  if ('ontouchstart' in window) {
    const mk = (txt, css, fn) => { const b = document.createElement('button'); b.textContent = txt; b.style.cssText = 'position:fixed;z-index:50;width:64px;height:64px;border-radius:50%;border:2px solid #22d3ee;background:rgba(6,182,212,.25);color:#fff;font:700 13px sans-serif;' + css; b.addEventListener('touchstart', ev => { ev.preventDefault(); fn(); }, { passive: false }); document.body.appendChild(b); };
    mk('SPLIT', 'right:16px;bottom:190px', () => isGameRunning && splitPlayerCells());
    mk('W', 'right:92px;bottom:190px', () => isGameRunning && ejectPlayerMass());
  }

  /* ---- Classifica live top 10 ---- */
  const lb = document.createElement('div');
  lb.style.cssText = 'position:fixed;top:64px;right:10px;z-index:40;min-width:150px;max-width:44vw;background:rgba(4,8,20,.65);border:1px solid rgba(34,211,238,.4);border-radius:10px;padding:8px 10px;font:12px monospace;color:#e2e8f0;pointer-events:none;backdrop-filter:blur(4px)';
  lb.id='agar-lb';document.body.appendChild(lb);
  function drawLB() {
    const rows = bots.map(b => ({ n: b.name, m: b.mass, me: false }));
    rows.push({ n: nm(), m: playerCells.reduce((s, c) => s + c.mass, 0), me: true });
    rows.sort((a, b) => b.m - a.m);
    lb.innerHTML = '<b style="color:#22d3ee">🏆 CLASSIFICA</b>' + rows.slice(0, 10).map((r, i) =>
      `<div style="${r.me ? 'color:#facc15;font-weight:700' : ''};white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${i + 1}. ${String(r.n).replace(/[<>&]/g, '')} · ${Math.floor(r.m)}</div>`).join('') +
      '<div style="margin-top:6px;color:#64748b;font-size:10px">W tieni = espelli · Spazio = dividi · E = split x2</div>';
  }

  const decay = (o, dt, rate, rad) => { if (o.mass > 60) { o._d = (o._d || 0) + o.mass * rate * dt; const k = Math.floor(o._d); if (k > 0) { o.mass -= k; o._d -= k; o.radius = rad(o.mass); } } };
  const popVirus = v => { v.x = rnd(); v.y = rnd(); v.radius = 42; v.feed = 0; };

  function step(t) {
    requestAnimationFrame(step);
    const dt = Math.min(0.1, (t - lastT) / 1000); lastT = t; tick++;
    lb.style.display = (isGameRunning && !window.__hideLB) ? 'block' : 'none';
    if (!isGameRunning || !playerCells.length) return;

    playerCells.forEach(c => { if (!c.born) c.born = t; decay(c, dt, 0.004, PR); });
    bots.forEach(b => decay(b, dt, 0.003, BR));
    if (wHeld && t - lastEj > 110) { ejectPlayerMass(); lastEj = t; }

    /* ---- Le tue celle: si respingono, poi si riuniscono dopo il timer ---- */
    for (let i = 0; i < playerCells.length; i++) for (let j = i + 1; j < playerCells.length; j++) {
      const a = playerCells[i], b = playerCells[j]; if (!a || !b) continue;
      const dx = b.x - a.x, dy = b.y - a.y, d = Math.hypot(dx, dy) || 1, min = a.radius + b.radius;
      const wait = 12000 + Math.max(a.mass, b.mass) * 25;
      if (t - Math.max(a.born, b.born) < wait) {
        if (d < min * 0.95) { const p = (min * 0.95 - d) / 2; a.x -= dx / d * p; a.y -= dy / d * p; b.x += dx / d * p; b.y += dy / d * p; }
      } else if (d < Math.max(a.radius, b.radius) * 0.7) {
        a.mass += b.mass; a.radius = PR(a.mass); playerCells.splice(j, 1); j--;
      }
    }

    /* ---- Massa espulsa: mangiabile da te e dai bot, nutre i virus ---- */
    for (let i = ejectedPellets.length - 1; i >= 0; i--) {
      const p = ejectedPellets[i]; if (!p.t0) p.t0 = t; let gone = false;
      for (const v of viruses) if (Math.hypot(p.x - v.x, p.y - v.y) < v.radius) {
        v.feed = (v.feed || 0) + 1; v.radius = 42 + v.feed * 2; gone = true;
        if (v.feed >= 7 && viruses.length < 30) { const s = Math.hypot(p.vx, p.vy) || 1; viruses.push({ x: v.x + p.vx / s * 90, y: v.y + p.vy / s * 90, radius: 42, feed: 0 }); v.feed = 0; v.radius = 42; }
        break;
      }
      if (!gone && t - p.t0 > 250) {
        for (const c of playerCells) if (Math.hypot(c.x - p.x, c.y - p.y) < c.radius) { c.mass += 8; c.radius = PR(c.mass); gone = true; break; }
        if (!gone) for (const b of bots) if (Math.hypot(b.x - p.x, b.y - p.y) < b.radius) { b.mass += 8; b.radius = BR(b.mass); gone = true; break; }
      }
      if (gone) ejectedPellets.splice(i, 1);
    }

    /* ---- Virus: le celle grosse esplodono, i bot grossi si dimezzano ---- */
    for (const v of viruses) {
      for (let i = 0; i < playerCells.length; i++) {
        const c = playerCells[i];
        if (c.mass >= 120 && Math.hypot(c.x - v.x, c.y - v.y) < c.radius - v.radius * 0.3) {
          const n = Math.min(7, 16 - playerCells.length); if (n < 1) continue;
          const part = Math.floor(c.mass / (n + 1));
          for (let k = 0; k < n; k++) { const an = Math.random() * 6.283; playerCells.push({ x: c.x, y: c.y, radius: PR(part), mass: part, color: c.color, name: c.name, clan: c.clan, vx: Math.cos(an) * 16, vy: Math.sin(an) * 16, born: t }); }
          c.mass -= part * n; c.radius = PR(c.mass); c.born = t; popVirus(v); showSyncToast('💥 Virus! Sei esploso in più celle'); break;
        }
      }
      for (const b of bots) if (b.mass >= 150 && Math.hypot(b.x - v.x, b.y - v.y) < b.radius - 10) { b.mass = Math.floor(b.mass * 0.5); b.radius = BR(b.mass); popVirus(v); }
    }

    /* ---- Bot che si mangiano tra loro ---- */
    for (const big of bots) for (const sm of bots) {
      if (big !== sm && big.mass > sm.mass * 1.15 && Math.hypot(big.x - sm.x, big.y - sm.y) < big.radius) {
        big.mass += Math.floor(sm.mass * 0.7); big.radius = BR(big.mass); addKillfeed(big.name, sm.name);
        sm.mass = 30; sm.radius = BR(30); sm.x = rnd(); sm.y = rnd();
      }
    }

    /* ---- Intelligenza dei bot: inseguono le prede, scappano dai più grossi, evitano i virus ---- */
    if (tick % 6 === 0) bots.forEach(b => {
      const vis = 450 + b.radius * 2; let prey = null, pd = 1e9, thr = null, td = 1e9;
      const consider = (x, y, m) => { const d = Math.hypot(x - b.x, y - b.y); if (d > vis) return; if (m * 1.15 < b.mass && d < pd) { pd = d; prey = { x, y }; } else if (m > b.mass * 1.15 && d < td) { td = d; thr = { x, y }; } };
      playerCells.forEach(c => consider(c.x, c.y, c.mass));
      bots.forEach(o => { if (o !== b) consider(o.x, o.y, o.mass); });
      if (thr) { b.targetX = Math.max(50, Math.min(MAP_SIZE - 50, b.x - (thr.x - b.x) * 2)); b.targetY = Math.max(50, Math.min(MAP_SIZE - 50, b.y - (thr.y - b.y) * 2)); }
      else if (prey) { b.targetX = prey.x; b.targetY = prey.y; }
      else { let f = null, fd = 320; for (let i = 0; i < foods.length; i += 3) { const d = Math.hypot(foods[i].x - b.x, foods[i].y - b.y); if (d < fd) { fd = d; f = foods[i]; } } if (f) { b.targetX = f.x; b.targetY = f.y; } }
      if (b.mass >= 130) viruses.forEach(v => { if (Math.hypot(v.x - b.x, v.y - b.y) < b.radius + v.radius + 80) { b.targetX = Math.max(50, Math.min(MAP_SIZE - 50, b.x - (v.x - b.x) * 3)); b.targetY = Math.max(50, Math.min(MAP_SIZE - 50, b.y - (v.y - b.y) * 3)); } });
    });

    if (t - lbT > 500) { lbT = t; drawLB(); }
  }
  requestAnimationFrame(step);
})();
</script>
<script id="agar-extras2">
(function () {
  const PR = m => Math.sqrt(m * 22) + 8, BR = m => Math.sqrt(m * 22) + 10;
  const rnd = () => Math.random() * (MAP_SIZE - 200) + 100;
  const now = () => performance.now();
  const total = () => playerCells.reduce((s, c) => s + c.mass, 0);
  const me = () => (playerCells[0] && playerCells[0].name) || '';

  /* ---------- Impostazioni salvate nel browser ---------- */
  let S = { sound: true, lb: true, radar: true, hud: true };
  try { Object.assign(S, JSON.parse(localStorage.getItem('zaSettings') || '{}')); } catch (e) {}
  const saveS = () => { try { localStorage.setItem('zaSettings', JSON.stringify(S)); } catch (e) {} applyS(); };
  const _pt = playTone; playTone = function () { if (S.sound) return _pt.apply(this, arguments); };
  if (typeof playSplitSound === 'function') { const _ps = playSplitSound; playSplitSound = function () { if (S.sound) return _ps.apply(this, arguments); }; }
  function applyS() { window.__hideLB = !S.lb; radarCanvas.style.display = S.radar ? '' : 'none'; hud.style.display = S.hud ? 'block' : 'none'; }

  /* ---------- Menu ESC ---------- */
  const menu = document.createElement('div');
  menu.style.cssText = 'position:fixed;inset:0;z-index:100;display:none;align-items:center;justify-content:center;background:rgba(3,5,14,.72);backdrop-filter:blur(6px);font-family:Orbitron,sans-serif';
  menu.innerHTML = `<div style="width:min(92vw,360px);max-height:90vh;overflow:auto;background:#0b1224;border:1px solid #22d3ee;border-radius:14px;padding:18px;color:#e2e8f0">
    <h2 style="margin:0 0 12px;color:#22d3ee;text-align:center;font-size:18px">⏸ MENU</h2>
    <div id="m-main"></div><div id="m-set" style="display:none"></div></div>`;
  document.body.appendChild(menu);
  const btn = (id, t, c) => `<button id="${id}" style="display:block;width:100%;margin:6px 0;padding:11px;border-radius:9px;border:1px solid #334155;background:${c || '#111a33'};color:#fff;font:600 13px Orbitron,sans-serif;cursor:pointer">${t}</button>`;
  const mMain = menu.querySelector('#m-main'), mSet = menu.querySelector('#m-set');
  mMain.innerHTML = btn('m-res', '▶ Riprendi', '#0e7490') + btn('m-set-b', '⚙️ Impostazioni') + btn('m-dash', '📊 Dashboard') + btn('m-home', '🏠 Torna al portale') + btn('m-quit', '🚪 Termina partita', '#7f1d1d') +
    '<p style="font:11px monospace;color:#94a3b8;margin:10px 0 0;line-height:1.6">Mouse: muovi · Spazio: dividi · W (tieni): espelli · E: split x2 · Shift: scatto · ESC: menu</p>';
  const rows = [['sound', '🔊 Suoni'], ['lb', '🏆 Classifica'], ['radar', '🛰️ Radar'], ['hud', '✨ Potenziamenti e missione']];
  mSet.innerHTML = rows.map(r => `<label style="display:flex;justify-content:space-between;margin:10px 0;font:13px sans-serif">${r[1]}<input type="checkbox" data-k="${r[0]}"></label>`).join('') + btn('m-back', '← Indietro');
  mSet.querySelectorAll('input').forEach(i => { i.checked = S[i.dataset.k]; i.onchange = () => { S[i.dataset.k] = i.checked; saveS(); }; });
  let open = false;
  function toggle(f) {
    open = f === undefined ? !open : f; menu.style.display = open ? 'flex' : 'none';
    mSet.style.display = 'none'; mMain.style.display = 'block'; wHeld(false);
  }
  const wHeld = () => {};
  menu.querySelector('#m-res').onclick = () => toggle(false);
  menu.querySelector('#m-set-b').onclick = () => { mMain.style.display = 'none'; mSet.style.display = 'block'; };
  menu.querySelector('#m-back').onclick = () => { mSet.style.display = 'none'; mMain.style.display = 'block'; };
  menu.querySelector('#m-dash').onclick = () => window.open('dashboard.php', '_blank');
  menu.querySelector('#m-home').onclick = async () => { try { await syncMatchProgress(true); } catch (e) {} location.href = 'index.php'; };
  menu.querySelector('#m-quit').onclick = () => { toggle(false); playerCells.length = 0; };
  document.addEventListener('keydown', e => {
    if (e.code === 'Escape' && isGameRunning) { e.preventDefault(); toggle(); e.stopImmediatePropagation(); return; }
    if (open) e.stopImmediatePropagation();
  }, true);
  ['mousemove', 'touchmove'].forEach(ev => document.addEventListener(ev, e => { if (open) e.stopImmediatePropagation(); }, true));

  /* ---------- HUD potenziamenti + missione ---------- */
  const hud = document.createElement('div');
  hud.style.cssText = 'position:fixed;top:64px;left:10px;z-index:40;font:12px monospace;color:#e2e8f0;background:rgba(4,8,20,.6);border:1px solid rgba(250,204,21,.4);border-radius:10px;padding:8px 10px;pointer-events:none;max-width:44vw';
  document.body.appendChild(hud); applyS();

  /* ---------- 4 potenziamenti sulla mappa: scudo, turbo, magnete, stella ---------- */
  const TYPES = { shield: ['🛡️', '#60a5fa', 'Scudo'], turbo: ['⚡', '#facc15', 'Turbo'], magnet: ['🧲', '#f472b6', 'Magnete'], star: ['⭐', '#fbbf24', 'Stella +60'] };
  let pups = [], until = { shield: 0, turbo: 0, magnet: 0 };
  const spawnPup = () => { const k = Object.keys(TYPES); pups.push({ x: rnd(), y: rnd(), type: k[Math.floor(Math.random() * k.length)] }); };

  /* overlay per disegnare i potenziamenti sopra il gioco */
  const fx = document.createElement('canvas'); fx.style.cssText = 'position:fixed;inset:0;z-index:5;pointer-events:none'; document.body.appendChild(fx);
  const fctx = fx.getContext('2d');
  const rs = () => { fx.width = innerWidth; fx.height = innerHeight; }; rs(); addEventListener('resize', rs);

  /* ---------- Scatto (Shift) ---------- */
  let dashCd = 0;
  addEventListener('keydown', e => {
    if ((e.code === 'ShiftLeft' || e.code === 'ShiftRight') && isGameRunning && !open && now() > dashCd) {
      dashCd = now() + 5000; const a = Math.atan2(mouseY - innerHeight / 2, mouseX - innerWidth / 2);
      playerCells.forEach(c => { c.vx += Math.cos(a) * 22; c.vy += Math.sin(a) * 22; }); showSyncToast('💨 Scatto!');
    }
  });

  /* ---------- Combo uccisioni ---------- */
  let streak = 0, lastKill = 0, kills = 0;
  const _ak = addKillfeed;
  addKillfeed = function (k, v) {
    _ak(k, v);
    if (k === me()) { kills++; streak = now() - lastKill < 6000 ? streak + 1 : 1; lastKill = now(); if (streak > 1) { matchStats.coinsEarned += 5 * streak; showSyncToast('🔥 Combo x' + streak + ' +' + 5 * streak + ' ZC'); } }
  };

  /* ---------- Missioni di partita ---------- */
  const QUESTS = [['Raggiungi 300 massa', () => total() >= 300, 40], ['Mangia 3 bot', () => kills >= 3, 50], ['Sopravvivi 2 minuti', () => now() - t0 > 120000, 60], ['Raggiungi 800 massa', () => total() >= 800, 100], ['Mangia 10 bot', () => kills >= 10, 150]];
  let qi = 0, t0 = now(), wasRun = false, nextBoss = 0, nextRain = 0, nextPup = 0, lastTick = 0;

  /* ---------- Salvataggio automatico ogni 30 s ---------- */
  setInterval(() => { if (isGameRunning && !open) { try { syncMatchProgress(false); } catch (e) {} } }, 30000);

  function loop(t) {
    requestAnimationFrame(loop);
    fctx.clearRect(0, 0, fx.width, fx.height);
    if (!isGameRunning) { wasRun = false; return; }
    if (!wasRun) { wasRun = true; t0 = now(); qi = 0; kills = 0; streak = 0; pups = []; until = { shield: 0, turbo: 0, magnet: 0 }; nextBoss = now() + 90000; nextRain = now() + 60000; nextPup = 0; }
    const n = now(), dt = Math.min(0.1, (n - (lastTick || n)) / 1000); lastTick = n;
    if (!playerCells.length) return;

    if (pups.length < 8 && n > nextPup) { spawnPup(); nextPup = n + 800; }
    for (let i = pups.length - 1; i >= 0; i--) {
      const p = pups[i];
      for (const c of playerCells) if (Math.hypot(c.x - p.x, c.y - p.y) < c.radius + 14) {
        if (p.type === 'star') { c.mass += 60; c.radius = PR(c.mass); } else until[p.type] = n + 8000;
        showSyncToast(TYPES[p.type][0] + ' ' + TYPES[p.type][2] + '!'); pups.splice(i, 1); break;
      }
    }
    /* effetti */
    if (n < until.shield) bots.forEach(b => playerCells.forEach(c => { const dx = b.x - c.x, dy = b.y - c.y, d = Math.hypot(dx, dy) || 1, min = c.radius + b.radius + 6; if (d < min) { b.x += dx / d * (min - d); b.y += dy / d * (min - d); } }));
    if (n < until.turbo) { const a = Math.atan2(mouseY - innerHeight / 2, mouseX - innerWidth / 2); playerCells.forEach(c => { c.x += Math.cos(a) * 3; c.y += Math.sin(a) * 3; }); }
    if (n < until.magnet) foods.forEach(f => playerCells.forEach(c => { const dx = c.x - f.x, dy = c.y - f.y, d = Math.hypot(dx, dy); if (d < 260 && d > 1) { f.x += dx / d * 6; f.y += dy / d * 6; } }));

    /* boss */
    const boss = bots.find(b => b.id === 'boss');
    if (boss && boss.mass <= 35) { bots.splice(bots.indexOf(boss), 1); matchStats.coinsEarned += 100; matchStats.xpEarned += 50; showSyncToast('👑 BOSS sconfitto! +100 ZC'); nextBoss = n + 90000; }
    else if (!boss && n > nextBoss && bots.length) { bots.push(Object.assign({}, bots[0], { id: 'boss', name: '👑 BOSS', mass: 500, radius: BR(500), x: rnd(), y: rnd(), _d: 0 })); showSyncToast('👑 Il BOSS è apparso nell\'arena!'); }
    /* pioggia di cibo */
    if (n > nextRain) {
      nextRain = n + 60000; const c = playerCells[0];
      for (let i = 0; i < 60 && i < foods.length; i++) { const f = foods[Math.floor(Math.random() * foods.length)]; f.x = Math.max(40, Math.min(MAP_SIZE - 40, c.x + (Math.random() - .5) * 1200)); f.y = Math.max(40, Math.min(MAP_SIZE - 40, c.y + (Math.random() - .5) * 1200)); }
      showSyncToast('🌧️ Pioggia di cibo!');
    }
    /* missione */
    const q = QUESTS[qi];
    if (q && q[1]()) { matchStats.coinsEarned += q[2]; showSyncToast('🎯 Missione completata! +' + q[2] + ' ZC'); qi++; }

    /* disegno potenziamenti (stessa camera del gioco) */
    fctx.save(); fctx.translate(fx.width / 2, fx.height / 2); fctx.scale(cameraZoom, cameraZoom); fctx.translate(-cameraX, -cameraY);
    fctx.textAlign = 'center'; fctx.textBaseline = 'middle';
    pups.forEach(p => { const r = 16 + Math.sin(n / 250) * 2; fctx.beginPath(); fctx.arc(p.x, p.y, r + 6, 0, 6.283); fctx.fillStyle = TYPES[p.type][1] + '55'; fctx.fill(); fctx.font = (r * 1.3) + 'px sans-serif'; fctx.fillText(TYPES[p.type][0], p.x, p.y); });
    fctx.restore();

    /* HUD */
    const act = Object.keys(until).filter(k => n < until[k]).map(k => TYPES[k][0] + ' ' + Math.ceil((until[k] - n) / 1000) + 's').join(' ');
    hud.innerHTML = (act ? '<div>' + act + '</div>' : '') + (q ? '<div>🎯 ' + q[0] + ' (+' + q[2] + ')</div>' : '<div>🎯 Missioni completate!</div>') +
      '<div>' + (n > dashCd ? '💨 Scatto pronto (Shift)' : '💨 ' + Math.ceil((dashCd - n) / 1000) + 's') + ' · 🔪 ' + kills + '</div>';
  }
  requestAnimationFrame(loop);
})();
</script>
<script id="agar-extras3">
(function () {
  let S = { mass: true, ring: true, skin: '', color: '' };
  try { Object.assign(S, JSON.parse(localStorage.getItem('zaSettings2') || '{}')); } catch (e) {}
  const save = () => { try { localStorage.setItem('zaSettings2', JSON.stringify(S)); } catch (e) {} };

  /* ---- Opzioni nel menu ESC > Impostazioni ---- */
  const back = document.getElementById('m-back');
  if (back) {
    const box = document.createElement('div');
    const skins = ['', '😎', '👾', '🔥', '🐉', '🦄', '💀', '🤖', '⭐', '🐸'];
    box.innerHTML = `<label style="display:flex;justify-content:space-between;margin:10px 0;font:13px sans-serif">🔢 Massa dei bot<input type="checkbox" id="s3-mass"></label>
      <label style="display:flex;justify-content:space-between;margin:10px 0;font:13px sans-serif">🎯 Anelli pericolo/preda<input type="checkbox" id="s3-ring"></label>
      <label style="display:flex;justify-content:space-between;margin:10px 0;font:13px sans-serif">🎨 Colore cella<input type="color" id="s3-color" value="#00f0ff"></label>
      <label style="display:flex;justify-content:space-between;margin:10px 0;font:13px sans-serif">😀 Skin<select id="s3-skin" style="background:#0f172a;color:#fff;border-radius:6px">${skins.map(s => `<option value="${s}">${s || 'nessuna'}</option>`).join('')}</select></label>
      <p style="font:11px monospace;color:#94a3b8">Rotella mouse o Z: zoom / vista mappa intera</p>`;
    back.parentNode.insertBefore(box, back);
    const $ = id => box.querySelector(id);
    $('#s3-mass').checked = S.mass; $('#s3-ring').checked = S.ring; $('#s3-skin').value = S.skin; if (S.color) $('#s3-color').value = S.color;
    $('#s3-mass').onchange = e => { S.mass = e.target.checked; save(); };
    $('#s3-ring').onchange = e => { S.ring = e.target.checked; save(); };
    $('#s3-skin').onchange = e => { S.skin = e.target.value; save(); };
    $('#s3-color').oninput = e => { S.color = e.target.value; save(); };
  }

  /* ---- Zoom con rotella e tasto Z (vista mappa intera) ---- */
  window.__zoomMul = 1;
  addEventListener('wheel', e => { if (!isGameRunning) return; window.__zoomMul = Math.max(0.3, Math.min(2.2, window.__zoomMul * (e.deltaY > 0 ? 0.9 : 1.1))); }, { passive: true });
  addEventListener('keydown', e => { if (e.code === 'KeyZ' && isGameRunning) window.__zoomMul = window.__zoomMul < 0.6 ? 1 : 0.3; });

  /* ---- Overlay: anelli, massa dei bot, skin ---- */
  const cv = document.createElement('canvas'); cv.style.cssText = 'position:fixed;inset:0;z-index:6;pointer-events:none'; document.body.appendChild(cv);
  const c2 = cv.getContext('2d'); const rs = () => { cv.width = innerWidth; cv.height = innerHeight; }; rs(); addEventListener('resize', rs);
  const rank = document.createElement('div');
  rank.style.cssText = 'position:fixed;bottom:12px;left:50%;transform:translateX(-50%);z-index:40;font:700 13px Orbitron,monospace;color:#facc15;text-shadow:0 0 6px #000;pointer-events:none';
  document.body.appendChild(rank);

  function loop() {
    requestAnimationFrame(loop);
    c2.clearRect(0, 0, cv.width, cv.height);
    if (!isGameRunning || !playerCells.length) { rank.textContent = ''; return; }
    if (S.color) playerCells.forEach(c => { if (c.color !== S.color) c.color = S.color; });
    const myMax = Math.max(...playerCells.map(c => c.mass)), tot = playerCells.reduce((s, c) => s + c.mass, 0);
    c2.save(); c2.translate(cv.width / 2, cv.height / 2); c2.scale(cameraZoom, cameraZoom); c2.translate(-cameraX, -cameraY);
    c2.textAlign = 'center'; c2.textBaseline = 'middle';
    bots.forEach(b => {
      if (S.ring) { c2.beginPath(); c2.arc(b.x, b.y, b.radius + 4, 0, 6.283); c2.lineWidth = 3 / cameraZoom; c2.strokeStyle = b.mass > myMax * 1.15 ? '#ef4444' : (myMax > b.mass * 1.15 ? '#22c55e' : '#facc15'); c2.stroke(); }
      if (S.mass) { c2.font = '700 ' + Math.max(10, b.radius * 0.28) + 'px monospace'; c2.fillStyle = 'rgba(255,255,255,.85)'; c2.fillText(Math.floor(b.mass), b.x, b.y + b.radius * 0.4); }
    });
    if (S.skin) playerCells.forEach(c => { c2.font = (c.radius * 1.1) + 'px sans-serif'; c2.fillText(S.skin, c.x, c.y - c.radius * 0.35); });
    c2.restore();
    const r = 1 + bots.filter(b => b.mass > tot).length;
    rank.textContent = 'POSIZIONE #' + r + ' / ' + (bots.length + 1);
  }
  requestAnimationFrame(loop);
})();
</script>
<script id="agar-extras4">
(function () {
  const PR = m => Math.sqrt(m * 22) + 8, rnd = () => Math.random() * (MAP_SIZE - 200) + 100;
  const TC = ['#ef4444', '#3b82f6'];
  let C = { online: true, mode: 'ffa', code: '' };
  try { Object.assign(C, JSON.parse(localStorage.getItem('zaOnline') || '{}')); } catch (e) {}
  const saveC = () => { try { localStorage.setItem('zaOnline', JSON.stringify(C)); } catch (e) {} };
  const room = () => C.mode === 'party' ? 'party:' + ((C.code || 'MAIN').toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 12) || 'MAIN') : C.mode;
  const me = () => (playerCells[0] && playerCells[0].name) || '';
  const mySkin = () => { try { return JSON.parse(localStorage.getItem('zaSettings2') || '{}').skin || ''; } catch (e) { return ''; } };
  const post = (o, form) => fetch(form ? 'api/skin.php' : 'api/arena.php', form ? { method: 'POST', body: form, credentials: 'same-origin' } : { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(o) }).then(r => r.text().then(t => { try { return JSON.parse(t); } catch (e) { return { success: false, error: 'Risposta server non valida (HTTP ' + r.status + (r.status === 404 ? ' - manca api/skin.php' : r.status === 413 ? ' - file troppo grande per il server' : '') + ')' }; } }));

  let remote = {}, myTeam = 0, myImg = null, lastEv = -1, lastChat = -1, inflight = false, spectating = false, wasOn = false;
  const imgs = {}; const getImg = u => { if (!u) return null; if (!imgs[u]) { const i = new Image(); i.src = u; imgs[u] = i; } return imgs[u].complete && imgs[u].naturalWidth ? imgs[u] : null; };

  /* ---------- Interfaccia nel menu ESC ---------- */
  const mMain = document.getElementById('m-main'), quit = document.getElementById('m-quit');
  if (mMain && quit) {
    const box = document.createElement('div');
    box.style.cssText = 'border:1px solid #334155;border-radius:9px;padding:10px;margin:6px 0;font:12px sans-serif';
    box.innerHTML = `<b style="color:#22d3ee">🌐 ONLINE</b>
      <label style="display:flex;justify-content:space-between;margin:8px 0">Gioca con altri giocatori<input type="checkbox" id="o-on"></label>
      <label style="display:flex;justify-content:space-between;margin:8px 0;gap:8px">Modalità<select id="o-mode" style="background:#0f172a;color:#fff;border-radius:6px">
        <option value="ffa">Tutti contro tutti</option><option value="teams">Squadre (rossi/blu)</option><option value="party">Party (codice)</option><option value="experimental">Experimental</option></select></label>
      <label id="o-codew" style="display:none;justify-content:space-between;margin:8px 0;gap:8px">Codice stanza<input id="o-code" maxlength="12" style="width:110px;background:#0f172a;color:#fff;border:1px solid #334155;border-radius:6px;padding:3px"></label>
      <label style="display:block;margin:8px 0">🖼 Skin immagine (max 10 MB)<input type="file" id="o-skin" accept="image/png,image/jpeg,image/webp,image/gif" style="display:block;margin-top:4px;color:#94a3b8;font-size:11px"></label>
      <div style="color:#64748b;font-size:10px">Invio: chat · Squadre: i compagni non si mangiano</div>`;
    mMain.insertBefore(box, quit);
    const $ = id => box.querySelector(id);
    $('#o-on').checked = C.online; $('#o-mode').value = C.mode; $('#o-code').value = C.code;
    const showCode = () => { $('#o-codew').style.display = C.mode === 'party' ? 'flex' : 'none'; }; showCode();
    $('#o-on').onchange = e => { C.online = e.target.checked; saveC(); if (!C.online) { remote = {}; post({ action: 'leave' }); } };
    $('#o-mode').onchange = e => { C.mode = e.target.value; saveC(); showCode(); remote = {}; lastEv = lastChat = -1; chatLog.innerHTML = ''; expDone = false; };
    $('#o-code').onchange = e => { C.code = e.target.value; saveC(); remote = {}; lastEv = lastChat = -1; chatLog.innerHTML = ''; };
    $('#o-skin').onchange = e => {
      const f = e.target.files[0]; if (!f) return; if (f.size > 10 * 1024 * 1024) { showSyncToast('File troppo grande (max 10 MB)'); return; } const fd = new FormData(); fd.append('skin', f);
      post(null, fd).then(d => showSyncToast(d.message || d.error || 'Errore')).catch(() => showSyncToast('Caricamento non riuscito'));
    };
  }

  /* ---------- Chat ---------- */
  const chatLog = document.createElement('div');
  chatLog.style.cssText = 'position:fixed;left:10px;bottom:64px;z-index:40;max-width:min(60vw,320px);font:12px sans-serif;color:#e2e8f0;pointer-events:none;text-shadow:0 1px 3px #000';
  const chatIn = document.createElement('input');
  chatIn.maxLength = 140; chatIn.placeholder = 'Scrivi e premi Invio…';
  chatIn.style.cssText = 'position:fixed;left:10px;bottom:30px;z-index:60;width:min(60vw,320px);display:none;background:rgba(4,8,20,.85);color:#fff;border:1px solid #22d3ee;border-radius:8px;padding:6px 8px;font:13px sans-serif';
  document.body.append(chatLog, chatIn);
  const addChat = (n, t) => {
    const d = document.createElement('div'); d.style.cssText = 'margin-top:2px;background:rgba(4,8,20,.45);padding:2px 6px;border-radius:6px';
    const b = document.createElement('b'); b.style.color = '#22d3ee'; b.textContent = n + ': '; d.append(b, document.createTextNode(t));
    chatLog.append(d); while (chatLog.children.length > 7) chatLog.firstChild.remove(); setTimeout(() => d.remove(), 15000);
  };
  chatIn.addEventListener('keydown', e => {
    e.stopPropagation();
    if (e.code === 'Enter') { const t = chatIn.value.trim(); if (t && C.online) post({ action: 'chat', room: room(), text: t }); chatIn.value = ''; chatIn.style.display = 'none'; chatIn.blur(); }
  });
  chatIn.addEventListener('keyup', e => e.stopPropagation());
  addEventListener('keydown', e => { if (e.code === 'Enter' && (isGameRunning || spectating) && C.online && chatIn.style.display === 'none') { e.preventDefault(); chatIn.style.display = 'block'; chatIn.focus(); } });

  /* ---------- Sincronizzazione (ogni 150 ms) ---------- */
  function handle(d) {
    if (!d.success) return;
    myTeam = d.my_team; myImg = d.my_img; lastEv = d.last_event; lastChat = d.last_chat;
    const nr = {};
    d.players.forEach(p => {
      const old = remote[p.id], cells = p.cells.map((c, i) => { const o = old && old.cells[i]; return { x: o ? o.x : c[0], y: o ? o.y : c[1], m: o ? o.m : c[2], tx: c[0], ty: c[1], tm: c[2], t: o ? o.t : 0 }; });
      nr[p.id] = Object.assign({}, p, { cells });
    });
    remote = nr;
    (d.chat || []).forEach(m => addChat(m.name, m.text));
    (d.events || []).forEach(ev => {
      let bi = -1, bd = 400; playerCells.forEach((c, i) => { const dd = Math.hypot(c.x - ev.x, c.y - ev.y); if (dd < bd) { bd = dd; bi = i; } });
      if (bi >= 0) { const k = Object.values(remote).find(p => p.id === ev.killer); addKillfeed(k ? k.name : 'Rivale', me()); playerCells.splice(bi, 1); }
    });
  }
  setInterval(() => {
    const playing = isGameRunning && playerCells.length;
    if (!C.online || inflight || (!playing && !spectating)) { if (wasOn && !playing && !spectating) { post({ action: 'leave' }).catch(() => {}); remote = {}; } wasOn = !!(playing || spectating); return; }
    wasOn = true; inflight = true;
    post({ action: 'sync', room: room(), spec: !playing, color: playing ? playerCells[0].color : undefined, skin: mySkin(), since_event: lastEv, since_chat: lastChat,
      cells: playing ? playerCells.map(c => ({ x: Math.round(c.x), y: Math.round(c.y), m: Math.round(c.mass) })) : [] })
      .then(handle).catch(() => {}).finally(() => { inflight = false; });
  }, 150);
  addEventListener('beforeunload', () => { if (C.online) navigator.sendBeacon && navigator.sendBeacon('api/arena.php', new Blob([JSON.stringify({ action: 'leave' })], { type: 'application/json' })); });

  /* ---------- Overlay di disegno ---------- */
  const cv = document.createElement('canvas'); cv.style.cssText = 'position:fixed;inset:0;z-index:7;pointer-events:none'; document.body.appendChild(cv);
  const g = cv.getContext('2d'); const rs = () => { cv.width = innerWidth; cv.height = innerHeight; }; rs(); addEventListener('resize', rs);
  const circleImg = (im, x, y, r) => { g.save(); g.beginPath(); g.arc(x, y, r, 0, 6.283); g.clip(); g.drawImage(im, x - r, y - r, r * 2, r * 2); g.restore(); };

  /* ---------- Spettatore ---------- */
  const sb = document.createElement('button');
  sb.style.cssText = 'position:fixed;top:12px;left:50%;transform:translateX(-50%);z-index:150;padding:9px 16px;border-radius:9px;border:1px solid #22d3ee;background:rgba(6,182,212,.25);color:#fff;font:700 12px Orbitron,sans-serif;cursor:pointer;display:none';
  document.body.appendChild(sb);
  let sx = MAP_SIZE / 2, sy = MAP_SIZE / 2, sz = 0.5;
  sb.onclick = () => {
    const lob = document.getElementById('lobby-overlay');
    spectating = !spectating; lastEv = lastChat = -1;
    if (lob) lob.style.display = spectating ? 'none' : 'flex';
  };

  let expDone = false, expT = 0;
  function frame(t) {
    requestAnimationFrame(frame);
    g.clearRect(0, 0, cv.width, cv.height);
    const playing = isGameRunning && playerCells.length;
    sb.style.display = (C.online && (spectating || !isGameRunning)) ? 'block' : 'none';
    sb.textContent = spectating ? '✖ Esci dallo spettatore' : '👁 Guarda la partita (spettatore)';
    if (!playing && !spectating) { expDone = false; return; }

    if (playing && C.mode === 'teams') playerCells.forEach(c => { c.color = TC[myTeam]; });
    if (playing && C.mode === 'experimental') {
      if (!expDone) { for (let i = 0; i < 15; i++) viruses.push({ x: rnd(), y: rnd(), radius: 42, feed: 0 }); expDone = true; expT = t + 20000; }
      if (t > expT) { expT = t + 20000; const c = playerCells[0]; for (let i = 0; i < 40 && i < foods.length; i++) { const f = foods[Math.floor(Math.random() * foods.length)]; f.x = Math.max(40, Math.min(MAP_SIZE - 40, c.x + (Math.random() - .5) * 1000)); f.y = Math.max(40, Math.min(MAP_SIZE - 40, c.y + (Math.random() - .5) * 1000)); } showSyncToast('🧪 Experimental: pioggia di cibo!'); }
    }

    let cx, cy, z;
    const list = Object.values(remote);
    list.forEach(p => p.cells.forEach(c => { c.x += (c.tx - c.x) * 0.3; c.y += (c.ty - c.y) * 0.3; c.m += (c.tm - c.m) * 0.3; }));
    if (playing) { cx = cameraX; cy = cameraY; z = cameraZoom; }
    else {
      let best = null, bm = -1; list.forEach(p => { const s = p.cells.reduce((a, c) => a + c.tm, 0); if (s > bm) { bm = s; best = p; } });
      if (best && best.cells.length) { sx += (best.cells.reduce((a, c) => a + c.x, 0) / best.cells.length - sx) * 0.08; sy += (best.cells.reduce((a, c) => a + c.y, 0) / best.cells.length - sy) * 0.08; }
      cx = sx; cy = sy; z = sz * (window.__zoomMul || 1);
      g.fillStyle = '#040714'; g.fillRect(0, 0, cv.width, cv.height);
    }
    g.save(); g.translate(cv.width / 2, cv.height / 2); g.scale(z, z); g.translate(-cx, -cy);
    if (!playing) { g.strokeStyle = 'rgba(0,240,255,.08)'; g.lineWidth = 1.5; for (let x = 0; x <= MAP_SIZE; x += 100) { g.beginPath(); g.moveTo(x, 0); g.lineTo(x, MAP_SIZE); g.stroke(); g.beginPath(); g.moveTo(0, x); g.lineTo(MAP_SIZE, x); g.stroke(); } g.strokeStyle = '#22d3ee'; g.strokeRect(0, 0, MAP_SIZE, MAP_SIZE); }

    /* mia skin immagine */
    if (playing && myImg) { const im = getImg(myImg); if (im) playerCells.forEach(c => circleImg(im, c.x, c.y, c.radius * 0.92)); }

    g.textAlign = 'center'; g.textBaseline = 'middle';
    list.forEach(p => {
      const col = C.mode === 'teams' ? TC[p.team] : p.color, im = getImg(p.img);
      p.cells.forEach(c => {
        const r = PR(c.m);
        g.beginPath(); g.arc(c.x, c.y, r, 0, 6.283); g.fillStyle = col; g.globalAlpha = .92; g.fill(); g.globalAlpha = 1;
        g.lineWidth = 4; g.strokeStyle = C.mode === 'teams' ? '#fff' : 'rgba(255,255,255,.55)'; g.stroke();
        if (im) circleImg(im, c.x, c.y, r * 0.92); else if (p.skin) { g.font = (r * 1.1) + 'px sans-serif'; g.fillText(p.skin, c.x, c.y - r * .35); }
        g.fillStyle = '#fff'; g.font = '700 ' + Math.max(12, r * .32) + 'px sans-serif'; g.fillText(p.name, c.x, c.y);
        g.font = Math.max(10, r * .22) + 'px monospace'; g.fillText(Math.floor(c.m), c.x, c.y + r * .38);
      });
    });
    g.restore();

    /* collisioni giocatore-giocatore (mangia chi è >15% più grosso) */
    if (playing) list.forEach(p => {
      if (C.mode === 'teams' && p.team === myTeam) return;
      p.cells.forEach(rc => {
        if (rc.eat && t - rc.eat < 1500) return;
        for (const c of playerCells) if (c.mass > rc.tm * 1.15 && Math.hypot(c.x - rc.x, c.y - rc.y) < c.radius) {
          rc.eat = t; c.mass += Math.floor(rc.tm * 0.7); c.radius = PR(c.mass);
          post({ action: 'eat', room: room(), target: p.id, x: Math.round(rc.x), y: Math.round(rc.y), m: Math.round(rc.tm) });
          addKillfeed(me(), p.name); matchStats.coinsEarned += 10; break;
        }
      });
    });
  }
  /* ---- Controlli nella lobby (stessi dati del menu ESC) ---- */
  window.__zaReload = () => { try { Object.assign(C, JSON.parse(localStorage.getItem('zaOnline') || '{}')); } catch (e) {} remote = {}; lastEv = lastChat = -1; expDone = false; };
  (function () {
    const $ = id => document.getElementById(id); if (!$('l-mode')) return;
    $('l-mode').value = C.mode; $('l-code').value = C.code; $('l-on').checked = C.online;
    $('l-codew').style.display = C.mode === 'party' ? 'block' : 'none';
    const upd = () => { C.mode = $('l-mode').value; C.code = $('l-code').value; C.online = $('l-on').checked; $('l-codew').style.display = C.mode === 'party' ? 'block' : 'none'; saveC(); remote = {}; lastEv = lastChat = -1; expDone = false; };
    ['l-mode', 'l-code', 'l-on'].forEach(i => $(i).onchange = upd);
    let s2 = {}; try { s2 = JSON.parse(localStorage.getItem('zaSettings2') || '{}'); } catch (e) {}
    $('l-emoji').value = s2.skin || '';
    $('l-emoji').onchange = e => { try { const o = JSON.parse(localStorage.getItem('zaSettings2') || '{}'); o.skin = e.target.value; localStorage.setItem('zaSettings2', JSON.stringify(o)); } catch (x) {} };
    $('l-file').onchange = e => {
      const f = e.target.files[0]; if (!f) return; if (f.size > 10 * 1024 * 1024) { $('l-msg').textContent = '✖ File troppo grande (max 10 MB)'; return; } const fd = new FormData(); fd.append('skin', f); $('l-msg').textContent = 'Caricamento…';
      post(null, fd).then(d => { $('l-msg').textContent = d.success ? '✔ ' + d.message + ' La vedrai nell\'arena.' : '✖ ' + (d.error || 'Errore'); }).catch(() => { $('l-msg').textContent = '✖ Caricamento non riuscito'; });
    };
  })();
  requestAnimationFrame(frame);
})();
</script>
<script id="agar-extras5">
/* ZeroAgar 4.0: menu utente in gioco, impostazioni grafiche, traguardi, emote, screenshot */
(function () {
  const $ = id => document.getElementById(id);
  const typing = () => { const a = document.activeElement; return a && (a.tagName === 'INPUT' || a.tagName === 'TEXTAREA' || a.tagName === 'SELECT'); };
  const openMenu = () => document.dispatchEvent(new KeyboardEvent('keydown', { code: 'Escape', key: 'Escape', bubbles: true, cancelable: true }));
  const menuEl = () => { const m = $('m-main'); return m && m.closest('div[style*="position:fixed"]'); };
  const isOpen = () => { const m = menuEl(); return !!m && m.style.display !== 'none'; };

  /* ---- tempo di gioco ---- */
  let startedAt = 0, maxMass = 0, wasRunning = false;
  const totalMass = () => playerCells.reduce((s, c) => s + c.mass, 0);
  const fmtTime = ms => { const s = Math.floor(ms / 1000); return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0'); };

  /* ---- scheda utente nel menu ESC ---- */
  const main = $('m-main');
  if (main) {
    const card = document.createElement('div');
    card.style.cssText = 'display:flex;gap:12px;align-items:center;padding:10px;margin-bottom:8px;border:1px solid #1e3a5f;border-radius:11px;background:linear-gradient(135deg,#0d1b36,#0a1224)';
    card.innerHTML = '<div id="um-av" style="width:46px;height:46px;border-radius:50%;display:flex;align-items:center;justify-content:center;font:700 20px Orbitron,sans-serif;color:#fff;border:2px solid #fff;box-shadow:0 0 14px currentColor">?</div>' +
      '<div style="flex:1;min-width:0"><div id="um-name" style="font:700 14px Orbitron,sans-serif;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"></div>' +
      '<div style="font:12px monospace;color:#facc15;margin-top:3px"><span id="um-lv"></span> · 🪙 <span id="um-coins"></span> ZC</div></div>';
    const stats = document.createElement('div');
    stats.id = 'um-stats';
    stats.style.cssText = 'display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-bottom:8px;font:11px monospace;color:#cbd5e1';
    const shot = document.createElement('button');
    shot.textContent = '📸 Screenshot';
    shot.style.cssText = 'display:block;width:100%;margin:6px 0;padding:11px;border-radius:9px;border:1px solid #334155;background:#111a33;color:#fff;font:600 13px Orbitron,sans-serif;cursor:pointer';
    const out = document.createElement('button');
    out.textContent = '🔓 Esci dall\'account';
    out.style.cssText = shot.style.cssText;
    out.onclick = () => { location.href = 'logout.php'; };
    shot.onclick = () => {
      try { const c = $('game-canvas'); c.toBlob(b => { const a = document.createElement('a'); a.href = URL.createObjectURL(b); a.download = 'zeroagar-' + Date.now() + '.png'; a.click(); setTimeout(() => URL.revokeObjectURL(a.href), 4000); }); showSyncToast('📸 Screenshot salvato'); } catch (e) { showSyncToast('Screenshot non riuscito'); }
    };
    main.insertBefore(stats, main.firstChild);
    main.insertBefore(card, main.firstChild);
    const dash = $('m-dash'); if (dash) dash.parentNode.insertBefore(shot, dash.nextSibling);
    const quit = $('m-quit'); if (quit) quit.parentNode.insertBefore(out, quit);

    const refresh = () => {
      const nm = ($('sync-user-name') || {}).textContent || 'Giocatore', lv = ($('sync-user-level') || {}).textContent || '1', co = ($('sync-user-coins') || {}).textContent || '0';
      const col = ($('input-color') || {}).value || '#00f0ff';
      $('um-name').textContent = nm; $('um-lv').textContent = '⭐ Lv. ' + lv; $('um-coins').textContent = co;
      const av = $('um-av'); av.textContent = (nm.trim()[0] || '?').toUpperCase(); av.style.background = col; av.style.color = '#fff'; av.style.borderColor = col;
      const m = Math.round(totalMass());
      stats.innerHTML = [['⏱ Tempo', fmtTime(performance.now() - startedAt)], ['⚖ Massa', m], ['🏔 Record', Math.round(maxMass)], ['⚔ Rivali', matchStats.rivals || 0], ['🟢 Particelle', matchStats.particles || 0], ['🪙 ZC partita', matchStats.coinsEarned || 0]]
        .map(r => '<div style="background:#0a1224;border:1px solid #1e293b;border-radius:7px;padding:5px 7px">' + r[0] + '<br><b style="color:#22d3ee">' + r[1] + '</b></div>').join('');
    };
    setInterval(() => { if (isOpen()) refresh(); }, 400);
    setTimeout(refresh, 300);
  }

  /* ---- impostazioni grafiche nel pannello Impostazioni ---- */
  const set = $('m-set');
  if (set) {
    const box = document.createElement('div');
    const sty = 'background:#0f172a;color:#fff;border:1px solid #334155;border-radius:6px;padding:3px';
    box.innerHTML = '<label style="display:flex;justify-content:space-between;align-items:center;margin:10px 0;font:13px sans-serif">🎨 Tema<select id="g-theme" style="' + sty + '">' +
      Object.keys(THEMES).map(k => '<option value="' + k + '">' + THEMES[k].name + '</option>').join('') + '</select></label>' +
      '<label style="display:flex;justify-content:space-between;align-items:center;margin:10px 0;font:13px sans-serif">✨ Qualità<select id="g-q" style="' + sty + '"><option value="high">Alta (luci + particelle)</option><option value="low">Bassa (più FPS)</option></select></label>';
    const back = $('m-back'); set.insertBefore(box, back);
    $('g-theme').value = GFX.theme; $('g-q').value = GFX.quality;
    const save = () => { try { localStorage.setItem('zaGfx', JSON.stringify({ theme: GFX.theme, quality: GFX.quality })); } catch (e) {} };
    $('g-theme').onchange = e => { GFX.theme = e.target.value; save(); };
    $('g-q').onchange = e => { GFX.quality = e.target.value; save(); };
  }

  /* ---- pulsanti flottanti: menu utente + clic sul profilo in alto ---- */
  const fab = document.createElement('button');
  fab.id = 'um-fab'; fab.title = 'Menu (ESC / P)'; fab.innerHTML = '☰';
  fab.style.cssText = 'position:fixed;top:14px;right:14px;z-index:60;width:42px;height:42px;border-radius:50%;border:1px solid rgba(0,240,255,.6);background:rgba(6,12,32,.9);color:#00f0ff;font:20px sans-serif;cursor:pointer;box-shadow:0 0 14px rgba(0,240,255,.35);display:none';
  fab.onclick = openMenu; document.body.appendChild(fab);
  const pill = document.querySelector('#sync-hud-bar .hud-pill.user');
  if (pill) { pill.style.cursor = 'pointer'; pill.title = 'Apri menu utente'; pill.addEventListener('click', () => { if (isGameRunning && !isOpen()) openMenu(); }); }

  /* ---- scorciatoie: P = menu, 1-4 = emote ---- */
  const EMOTES = { Digit1: '😀', Digit2: '😡', Digit3: '👍', Digit4: '💀' };
  let emote = null;
  addEventListener('keydown', e => {
    if (!isGameRunning || typing()) return;
    if (e.code === 'KeyP') { e.preventDefault(); openMenu(); }
    else if (EMOTES[e.code] && !isOpen()) emote = { e: EMOTES[e.code], t: performance.now() };
  });

  /* ---- overlay emote ---- */
  const ov = document.createElement('canvas');
  ov.style.cssText = 'position:fixed;inset:0;z-index:6;pointer-events:none'; document.body.appendChild(ov);
  const oc = ov.getContext('2d');
  const rs = () => { ov.width = innerWidth; ov.height = innerHeight; }; rs(); addEventListener('resize', rs);

  /* ---- traguardi di massa con bonus ---- */
  const MILES = [[100, 5], [250, 10], [500, 20], [1000, 40], [2000, 80], [4000, 150]];
  let milestone = 0;

  (function loop(now) {
    requestAnimationFrame(loop);
    oc.clearRect(0, 0, ov.width, ov.height);
    if (isGameRunning && !wasRunning) { startedAt = performance.now(); maxMass = 0; milestone = 0; }
    wasRunning = isGameRunning;
    fab.style.display = isGameRunning ? 'block' : 'none';
    if (!isGameRunning || !playerCells.length) return;
    const tm = totalMass(); if (tm > maxMass) maxMass = tm;
    if (milestone < MILES.length && tm >= MILES[milestone][0]) {
      const m = MILES[milestone++]; matchStats.coinsEarned += m[1];
      showSyncToast('🏅 Traguardo ' + m[0] + ' massa! +' + m[1] + ' ZC');
      playerCells.forEach(c => burst(c.x, c.y, c.color, 24, 5));
    }
    if (emote) {
      const age = now - emote.t; if (age > 1800) { emote = null; return; }
      const c = playerCells[0], z = cameraZoom;
      const sx = (c.x - cameraX) * z + ov.width / 2, sy = (c.y - cameraY) * z + ov.height / 2 - c.radius * z - 18 - age * 0.02;
      oc.globalAlpha = Math.min(1, (1800 - age) / 500); oc.font = Math.max(26, c.radius * z * 0.7) + 'px sans-serif'; oc.textAlign = 'center';
      oc.fillText(emote.e, sx, sy); oc.globalAlpha = 1;
    }
  })(performance.now());

  /* aggiorna testo comandi nel menu */
  const hint = main && main.querySelector('p'); if (hint) hint.textContent += ' · P: menu · 1-4: emote';
})();
</script>

</body>
</html>
