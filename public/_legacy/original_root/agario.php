<?php
require_once __DIR__ . '/api/db.php';
$zaUser = require_login_page('index.php');
$zaUserPublic = user_public($zaUser);
?>
<!DOCTYPE html>
<html>
  <head>
    <title>Agar.io Classic · ZeroArcade</title>
    <!-- ZEROARCADE INTEGRATION: Agar.io Classic · account + MySQL progress -->
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
    <meta name="theme-color" content="#030611" />
    <meta name="index-sync" content="updated-localized-portal" />
    <link
      href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Inter:wght@400;600&display=swap"
      rel="stylesheet"
    />
    <style>
      :root {
          --transition-fast: 150ms;
          --transition-normal: 300ms;
          --space-xs: 8px;
          --space-sm: 16px;
          --space-md: 24px;
          --space-lg: 32px;
          --ease-out: cubic-bezier(0.0, 0, 0.2, 1);
      }

      body, html {
          margin: 0;
          padding: 0;
          width: 100%;
          height: 100%;
          overflow: hidden;
          touch-action: none;
          -ms-touch-action: none;
          background: #000;
      }

      #game-world {
          position: relative;
          width: 100%;
          height: 100%;
          background-color: #0a0e27;
      }

      canvas {
          display: block;
          width: 100%;
          height: 100%;
          transition: filter 0.6s ease-out;
      }

      canvas.desaturated {
          filter: grayscale(0.9) contrast(1.1) brightness(0.65);
      }

      #vignette-overlay {
          position: absolute;
          inset: 0;
          pointer-events: none;
          z-index: 20;
          opacity: 0;
          transition: opacity 0.5s ease-out;
          background: radial-gradient(circle at 50% 50%, rgba(255, 0, 50, 0) 30%, rgba(180, 0, 40, 0.45) 70%, rgba(100, 0, 20, 0.85) 100%);
          box-shadow: inset 0 0 120px rgba(255, 0, 60, 0.7);
      }

      #vignette-overlay.active {
          opacity: 1;
      }

      .hud {
          position: absolute;
          top: 0;
          left: 0;
          width: 100%;
          pointer-events: none;
          z-index: 10;
          transform-origin: top center;
      }

      .hud > * {
          pointer-events: auto;
      }

      .score-container {
          display: flex;
          flex-direction: column;
          align-items: center;
          padding-top: 20px;
          pointer-events: none;
      }

      .score-badge {
          background: rgba(6, 14, 34, 0.7);
          border: 1px solid rgba(0, 240, 255, 0.45);
          box-shadow: 0 0 20px rgba(0, 200, 255, 0.25), inset 0 0 10px rgba(0, 240, 255, 0.12);
          border-radius: 32px;
          padding: 6px 24px;
          display: flex;
          align-items: baseline;
          gap: 8px;
      }

      .mass-label {
          font-family: 'Orbitron', sans-serif;
          font-size: 14px;
          font-weight: 700;
          letter-spacing: 2px;
          color: rgba(0, 240, 255, 0.9);
          text-transform: uppercase;
      }

      .score-display {
          font-family: 'Orbitron', sans-serif;
          font-size: 36px;
          font-weight: 700;
          color: #fff;
          text-shadow: 0 0 16px rgba(0, 255, 255, 0.8), 0 0 32px rgba(0, 255, 255, 0.4);
          transition: transform 0.12s cubic-bezier(0.18, 0.89, 0.32, 1.28);
          min-width: 60px;
          text-align: left;
      }

      .score-display.bump {
          transform: scale(1.18);
          color: #ffe600;
          text-shadow: 0 0 20px rgba(255, 230, 0, 0.9);
      }

      .leaderboard {
          position: absolute;
          top: 24px;
          right: 20px;
          min-width: 190px;
          padding: 12px 14px;
          border-radius: 12px;
          background: rgba(6, 10, 30, 0.55);
          border: 1px solid rgba(0, 240, 255, 0.25);
          box-shadow: 0 0 18px rgba(0, 200, 255, 0.15);
          font-family: 'Inter', sans-serif;
          font-size: 16px;
          color: rgba(255, 255, 255, 0.85);
          pointer-events: none;
      }

      .leaderboard h3 {
          margin: 0 0 8px 0;
          font-family: 'Orbitron', sans-serif;
          font-size: 15px;
          letter-spacing: 1px;
          color: #00f0ff;
          text-transform: uppercase;
      }

      .lb-row {
          display: flex;
          justify-content: space-between;
          gap: 10px;
          line-height: 1.5;
          white-space: nowrap;
      }

      .lb-row.me {
          color: #ffe600;
          font-weight: 600;
      }

      #game-over {
          position: absolute;
          inset: 0;
          z-index: 30;
          display: none;
          flex-direction: column;
          align-items: center;
          justify-content: center;
          background: radial-gradient(circle at 50% 45%, rgba(60, 0, 20, 0.55), rgba(2, 3, 10, 0.92));
          backdrop-filter: blur(3px);
          font-family: 'Inter', sans-serif;
      }

      #game-over.visible { display: flex; }

      #game-over .go-title {
          font-family: 'Orbitron', sans-serif;
          font-size: 58px;
          font-weight: 700;
          color: #ff3b6b;
          text-shadow: 0 0 22px rgba(255, 45, 90, 0.8), 0 0 60px rgba(255, 0, 60, 0.4);
          letter-spacing: 3px;
          text-align: center;
      }

      #game-over .go-sub {
          margin-top: 10px;
          font-size: 22px;
          color: rgba(255, 255, 255, 0.65);
          text-align: center;
          padding: 0 24px;
      }

      #game-over .go-score {
          margin-top: 28px;
          font-family: 'Orbitron', sans-serif;
          font-size: 72px;
          color: #fff;
          text-shadow: 0 0 24px rgba(0, 255, 255, 0.6);
      }

      #game-over .go-score-label {
          font-size: 18px;
          letter-spacing: 2px;
          color: rgba(255, 255, 255, 0.5);
          text-transform: uppercase;
      }

      #global-lb {
          margin-top: 32px;
          min-width: 280px;
          max-width: 420px;
          max-height: 280px;
          overflow-y: auto;
          padding: 16px 18px;
          border-radius: 12px;
          background: rgba(6, 10, 30, 0.65);
          border: 1px solid rgba(0, 240, 255, 0.35);
          box-shadow: 0 0 20px rgba(0, 200, 255, 0.2);
      }

      #global-lb h3 {
          margin: 0 0 12px 0;
          font-family: 'Orbitron', sans-serif;
          font-size: 16px;
          letter-spacing: 2px;
          color: #00f0ff;
          text-transform: uppercase;
          text-align: center;
      }

      #global-lb-list {
          font-family: 'Inter', sans-serif;
          font-size: 15px;
          color: rgba(255, 255, 255, 0.85);
      }

      .glb-row {
          display: flex;
          justify-content: space-between;
          gap: 12px;
          line-height: 1.8;
          padding: 4px 8px;
          border-radius: 6px;
          transition: background-color 0.15s ease;
      }

      .glb-row.highlight {
          background-color: rgba(255, 230, 0, 0.15);
          color: #ffe600;
          font-weight: 600;
      }

      .glb-row-name {
          flex: 1;
          overflow: hidden;
          text-overflow: ellipsis;
          white-space: nowrap;
      }

      .glb-row-score {
          font-weight: 600;
          min-width: 50px;
          text-align: right;
      }

      .action-buttons {
          position: absolute;
          bottom: 40px;
          left: 0;
          width: 100%;
          display: flex;
          justify-content: center;
          gap: 14px;
          z-index: 15;
          pointer-events: none;
      }

      .action-btn {
          pointer-events: auto;
          width: 106px;
          height: 106px;
          border-radius: 50%;
          border: 2px solid rgba(0, 255, 255, 0.55);
          background: radial-gradient(circle at 50% 32%, rgba(0, 210, 255, 0.38), rgba(0, 55, 110, 0.4));
          color: #fff;
          font-family: 'Orbitron', sans-serif;
          font-size: 15px;
          font-weight: 700;
          letter-spacing: 1.5px;
          box-shadow: 0 0 24px rgba(0, 220, 255, 0.3), inset 0 0 18px rgba(0, 240, 255, 0.12);
          cursor: pointer;
          display: flex;
          flex-direction: column;
          align-items: center;
          justify-content: center;
          gap: 4px;
          user-select: none;
          -webkit-user-select: none;
          -webkit-tap-highlight-color: transparent;
          transition: transform var(--transition-fast) var(--ease-out), box-shadow var(--transition-fast);
      }

      .action-btn .icon {
          font-size: 30px;
          line-height: 1;
          text-shadow: 0 0 14px rgba(0, 255, 255, 0.8);
      }

      .action-btn:active {
          transform: scale(0.92);
          box-shadow: 0 0 40px rgba(0, 240, 255, 0.65);
      }

      #split-btn {
          border-color: rgba(255, 230, 0, 0.55);
          background: radial-gradient(circle at 50% 32%, rgba(255, 220, 60, 0.32), rgba(120, 80, 0, 0.4));
      }

      #dash-btn {
          border-color: rgba(0, 255, 170, 0.6);
          background: radial-gradient(circle at 50% 32%, rgba(0, 255, 180, 0.3), rgba(0, 90, 70, 0.4));
      }

      #god-btn {
          border-color: rgba(255, 180, 60, 0.7);
          background: radial-gradient(circle at 50% 32%, rgba(255, 200, 80, 0.32), rgba(120, 60, 0, 0.42));
      }

      #pulse-btn {
          border-color: rgba(179, 136, 255, 0.72);
          background: radial-gradient(circle at 50% 32%, rgba(179, 136, 255, 0.36), rgba(58, 25, 120, 0.44));
      }

      #surge-btn {
          border-color: rgba(255, 45, 149, 0.78);
          background: radial-gradient(circle at 50% 32%, rgba(255, 45, 149, 0.36), rgba(120, 20, 85, 0.46));
      }

      .action-btn.cooling {
          opacity: 0.42;
          filter: saturate(0.35);
      }

      .action-btn.on {
          box-shadow: 0 0 34px rgba(255, 230, 0, 0.8);
          border-color: #ffe600;
      }

      #restart-btn {
          margin-top: 32px;
          min-width: 260px;
          min-height: 96px;
          padding: 0 40px;
          border-radius: 48px;
          border: 2px solid rgba(0, 255, 255, 0.7);
          background: linear-gradient(180deg, rgba(0, 200, 255, 0.35), rgba(0, 90, 160, 0.35));
          color: #fff;
          font-family: 'Orbitron', sans-serif;
          font-size: 28px;
          font-weight: 700;
          letter-spacing: 2px;
          cursor: pointer;
          box-shadow: 0 0 28px rgba(0, 220, 255, 0.35);
          transition: transform var(--transition-fast) var(--ease-out), box-shadow var(--transition-fast);
      }

      #restart-btn:active {
          transform: scale(0.95);
          box-shadow: 0 0 40px rgba(0, 240, 255, 0.6);
      }

      /* ===== Added systems: abilities, stats, notifications, minimap, chat, overlays ===== */
      .stat-strip {
          position: absolute;
          top: 78px;
          left: 0;
          width: 100%;
          display: flex;
          justify-content: center;
          flex-wrap: wrap;
          gap: 10px;
          font-family: 'Orbitron', sans-serif;
          font-size: 14px;
          color: rgba(255, 255, 255, 0.78);
          pointer-events: none;
      }

      .stat-pill {
          background: rgba(6, 14, 34, 0.5);
          border: 1px solid rgba(0, 240, 255, 0.22);
          border-radius: 14px;
          padding: 3px 11px;
      }

      .stat-pill.combo { color: #ffe600; border-color: rgba(255, 230, 0, 0.45); }
      .stat-pill.coins { color: #ffc94a; border-color: rgba(255, 180, 0, 0.45); }
      .stat-pill.hidden { display: none; }

      .ability-hud {
          position: absolute;
          top: 118px;
          left: 18px;
          display: flex;
          flex-direction: column;
          gap: 7px;
          pointer-events: none;
          font-family: 'Orbitron', sans-serif;
          font-size: 12px;
          letter-spacing: 1px;
          color: rgba(255, 255, 255, 0.8);
      }

      .ability-chip {
          display: flex;
          align-items: center;
          gap: 8px;
          min-width: 150px;
          padding: 5px 11px;
          border-radius: 16px;
          background: rgba(6, 14, 34, 0.55);
          border: 1px solid rgba(0, 240, 255, 0.25);
      }

      .ability-chip.ready { border-color: rgba(0, 255, 200, 0.7); color: #8effe0; }
      .ability-chip.active { border-color: #ffe600; color: #ffe600; box-shadow: 0 0 14px rgba(255, 230, 0, 0.3); }

      .chip-bar {
          flex: 1;
          height: 5px;
          border-radius: 3px;
          background: rgba(255, 255, 255, 0.16);
          overflow: hidden;
      }

      .chip-fill {
          display: block;
          height: 100%;
          width: 0%;
          background: linear-gradient(90deg, #00f0ff, #ffe600);
      }

      #effect-strip {
          position: absolute;
          top: 196px;
          left: 18px;
          display: flex;
          flex-direction: column;
          gap: 5px;
          pointer-events: none;
          font-family: 'Orbitron', sans-serif;
          font-size: 12px;
      }

      .eff-chip {
          padding: 3px 9px;
          border-radius: 12px;
          background: rgba(0, 0, 0, 0.45);
          border: 1px solid currentColor;
      }

      #notif-feed {
          position: absolute;
          top: 150px;
          right: 18px;
          width: 238px;
          display: flex;
          flex-direction: column;
          align-items: flex-end;
          gap: 6px;
          pointer-events: none;
          font-family: 'Inter', sans-serif;
          font-size: 14px;
      }

      .notif {
          background: rgba(6, 10, 30, 0.72);
          border-left: 3px solid #00f0ff;
          border-radius: 8px;
          padding: 6px 10px;
          color: #fff;
          opacity: 0;
          transform: translateX(22px);
          animation: notifIn 0.22s ease-out forwards;
          max-width: 100%;
      }

      .notif.fade { animation: notifOut 0.4s ease-in forwards; }
      @keyframes notifIn { to { opacity: 1; transform: translateX(0); } }
      @keyframes notifOut { to { opacity: 0; transform: translateX(22px); } }

      #minimap-wrap {
          position: absolute;
          right: 16px;
          bottom: 186px;
          width: 156px;
          height: 156px;
          border-radius: 12px;
          border: 1px solid rgba(0, 240, 255, 0.35);
          background: rgba(4, 8, 22, 0.55);
          box-shadow: 0 0 16px rgba(0, 200, 255, 0.18);
          overflow: hidden;
          z-index: 12;
      }

      #minimap { display: block; width: 100%; height: 100%; }

      #minimap-label {
          position: absolute;
          top: 3px;
          left: 8px;
          font-family: 'Orbitron', sans-serif;
          font-size: 10px;
          letter-spacing: 1px;
          color: rgba(0, 240, 255, 0.7);
          pointer-events: none;
      }

      #chat-panel {
          position: absolute;
          left: 16px;
          bottom: 186px;
          width: 272px;
          z-index: 14;
          font-family: 'Inter', sans-serif;
          display: flex;
          flex-direction: column;
          align-items: flex-start;
      }

      #chat-body {
          display: none;
          flex-direction: column;
          width: 100%;
          height: 224px;
          margin-bottom: 8px;
          border-radius: 12px;
          border: 1px solid rgba(0, 240, 255, 0.3);
          background: rgba(4, 8, 22, 0.78);
          overflow: hidden;
      }

      #chat-panel.open #chat-body { display: flex; }

      #chat-log {
          flex: 1;
          overflow-y: auto;
          padding: 8px;
          font-size: 13px;
          line-height: 1.45;
          color: rgba(255, 255, 255, 0.86);
      }

      .chat-msg .who { color: #00f0ff; font-weight: 600; }
      .chat-msg.me .who { color: #ffe600; }
      .chat-msg.sys { color: rgba(255, 255, 255, 0.45); font-style: italic; }

      #chat-form { display: flex; gap: 6px; padding: 6px; border-top: 1px solid rgba(0, 240, 255, 0.2); }

      #chat-input {
          flex: 1;
          min-width: 0;
          background: rgba(0, 0, 0, 0.4);
          border: 1px solid rgba(0, 240, 255, 0.25);
          border-radius: 8px;
          color: #fff;
          padding: 8px;
          font-size: 14px;
          outline: none;
      }

      #chat-send {
          width: 54px;
          border-radius: 8px;
          border: 1px solid rgba(0, 240, 255, 0.4);
          background: rgba(0, 180, 255, 0.28);
          color: #fff;
          font-weight: 700;
          cursor: pointer;
      }

      .quick-emotes { display: flex; gap: 5px; padding: 0 6px 6px; }

      .quick-emotes button {
          flex: 1;
          padding: 5px 0;
          background: rgba(0, 0, 0, 0.35);
          border: 1px solid rgba(0, 240, 255, 0.22);
          color: #fff;
          border-radius: 6px;
          font-size: 16px;
          cursor: pointer;
      }

      #chat-toggle {
          position: relative;
          width: 108px;
          height: 54px;
          border-radius: 14px;
          border: 1px solid rgba(0, 240, 255, 0.5);
          background: rgba(6, 14, 34, 0.78);
          color: #00f0ff;
          font-family: 'Orbitron', sans-serif;
          font-size: 13px;
          font-weight: 700;
          letter-spacing: 1px;
          cursor: pointer;
          -webkit-tap-highlight-color: transparent;
      }

      #chat-badge {
          position: absolute;
          top: -6px;
          right: -6px;
          min-width: 22px;
          height: 22px;
          border-radius: 11px;
          background: #ff2d6b;
          color: #fff;
          font-size: 12px;
          line-height: 22px;
          display: none;
      }

      #chat-badge.show { display: block; }

      .overlay-panel {
          position: absolute;
          inset: 0;
          z-index: 28;
          display: none;
          flex-direction: column;
          align-items: center;
          justify-content: center;
          background: rgba(2, 5, 16, 0.82);
          backdrop-filter: blur(3px);
          font-family: 'Inter', sans-serif;
          color: #fff;
          text-align: center;
          padding: 0 36px;
      }

      .overlay-panel.visible { display: flex; }

      .overlay-panel h2 {
          font-family: 'Orbitron', sans-serif;
          font-size: 42px;
          color: #00f0ff;
          margin: 0 0 18px 0;
          letter-spacing: 3px;
      }

      .key-list { font-size: 19px; line-height: 1.9; text-align: left; }
      .key-list b { color: #ffe600; font-family: 'Orbitron', sans-serif; }

      .overlay-btn {
          margin-top: 28px;
          min-width: 220px;
          min-height: 96px;
          border-radius: 48px;
          border: 2px solid rgba(0, 255, 255, 0.7);
          background: linear-gradient(180deg, rgba(0, 200, 255, 0.32), rgba(0, 90, 160, 0.32));
          color: #fff;
          font-family: 'Orbitron', sans-serif;
          font-size: 24px;
          font-weight: 700;
          letter-spacing: 2px;
          cursor: pointer;
      }

      #run-stats {
          margin-top: 18px;
          display: grid;
          grid-template-columns: repeat(3, auto);
          gap: 6px 26px;
          font-family: 'Inter', sans-serif;
          font-size: 16px;
          color: rgba(255, 255, 255, 0.8);
      }

      #run-stats b { color: #00f0ff; font-family: 'Orbitron', sans-serif; }

      /* ===== Profile chip + user menu ===== */
      #profile-chip {
          position: absolute;
          top: 18px;
          left: 16px;
          display: flex;
          align-items: center;
          gap: 9px;
          padding: 6px 14px 6px 6px;
          border-radius: 30px;
          background: rgba(6, 14, 34, 0.72);
          border: 1px solid rgba(0, 240, 255, 0.4);
          cursor: pointer;
          -webkit-tap-highlight-color: transparent;
          min-height: 52px;
      }

      #chip-avatar {
          width: 42px;
          height: 42px;
          border-radius: 50%;
          background: radial-gradient(circle at 40% 35%, #8ff9ff, #0077aa);
          background-size: cover;
          background-position: center;
          border: 2px solid rgba(0, 240, 255, 0.7);
          flex: none;
      }

      #chip-name {
          font-family: 'Orbitron', sans-serif;
          font-size: 14px;
          color: #fff;
          max-width: 110px;
          overflow: hidden;
          text-overflow: ellipsis;
          white-space: nowrap;
      }

      .role-badge {
          display: inline-block;
          padding: 2px 9px;
          border-radius: 9px;
          font-family: 'Orbitron', sans-serif;
          font-size: 11px;
          letter-spacing: 1px;
          color: #fff;
      }

      .role-admin { background: #ff2d6b; }
      .role-mod { background: #8b5cff; }
      .role-helper { background: #00bfa0; }
      .role-vip { background: #ffb400; color: #2a1a00; }
      .role-user { background: #2d3f63; }

      #user-menu {
          position: absolute;
          inset: 0;
          z-index: 70;
          display: none;
          flex-direction: column;
          background: linear-gradient(180deg, rgba(4, 9, 26, 0.97), rgba(2, 4, 12, 0.99));
          font-family: 'Inter', sans-serif;
          color: #fff;
      }

      #user-menu.visible { display: flex; }

      .um-header {
          display: flex;
          align-items: center;
          gap: 14px;
          padding: 20px 18px 16px;
          border-bottom: 1px solid rgba(0, 240, 255, 0.22);
      }

      #um-avatar {
          width: 86px;
          height: 86px;
          border-radius: 50%;
          flex: none;
          background: radial-gradient(circle at 40% 35%, #8ff9ff, #0077aa);
          background-size: cover;
          background-position: center;
          border: 2px solid #00f0ff;
          box-shadow: 0 0 22px rgba(0, 240, 255, 0.4);
      }

      .um-id { flex: 1; min-width: 0; }
      #um-name { font-family: 'Orbitron', sans-serif; font-size: 24px; }
      #um-xp-text { font-size: 13px; color: rgba(255, 255, 255, 0.6); margin-top: 4px; }

      .xp-bar {
          height: 8px;
          border-radius: 4px;
          background: rgba(255, 255, 255, 0.14);
          margin-top: 6px;
          overflow: hidden;
      }

      .xp-bar span {
          display: block;
          height: 100%;
          width: 0%;
          background: linear-gradient(90deg, #00f0ff, #ffe600);
      }

      #um-close {
          width: 64px;
          height: 64px;
          flex: none;
          border-radius: 50%;
          border: 2px solid rgba(0, 240, 255, 0.55);
          background: rgba(0, 120, 180, 0.3);
          color: #fff;
          font-size: 26px;
          font-family: 'Orbitron', sans-serif;
          cursor: pointer;
      }

      #um-tabs {
          display: flex;
          gap: 8px;
          padding: 12px 16px 6px;
          overflow-x: auto;
      }

      .um-tab {
          min-height: 54px;
          padding: 0 18px;
          white-space: nowrap;
          border-radius: 14px;
          border: 1px solid rgba(0, 240, 255, 0.28);
          background: rgba(255, 255, 255, 0.05);
          color: rgba(255, 255, 255, 0.72);
          font-family: 'Orbitron', sans-serif;
          font-size: 14px;
          letter-spacing: 1px;
          cursor: pointer;
      }

      .um-tab.on {
          background: rgba(0, 240, 255, 0.22);
          border-color: #00f0ff;
          color: #fff;
      }

      .um-body { flex: 1; overflow-y: auto; padding: 8px 16px 34px; }
      .um-pane { display: none; }
      .um-pane.on { display: block; }

      .um-row {
          display: flex;
          align-items: center;
          justify-content: space-between;
          gap: 12px;
          padding: 11px 13px;
          border-radius: 12px;
          background: rgba(255, 255, 255, 0.045);
          margin-bottom: 8px;
      }

      .um-row label { font-size: 16px; color: rgba(255, 255, 255, 0.88); }
      .um-val { color: #00f0ff; font-family: 'Orbitron', sans-serif; font-size: 13px; }
      .um-row input[type=range] { width: 168px; flex: none; }

      .um-row input[type=text],
      .um-row input[type=password],
      .um-row select {
          background: rgba(0, 0, 0, 0.45);
          border: 1px solid rgba(0, 240, 255, 0.3);
          color: #fff;
          border-radius: 8px;
          padding: 10px;
          font-size: 15px;
          min-height: 44px;
          max-width: 190px;
      }

      .sw {
          width: 66px;
          height: 36px;
          border-radius: 18px;
          background: rgba(255, 255, 255, 0.18);
          position: relative;
          cursor: pointer;
          flex: none;
          transition: background var(--transition-fast);
      }

      .sw.on { background: rgba(0, 255, 190, 0.55); }

      .sw i {
          position: absolute;
          top: 4px;
          left: 4px;
          width: 28px;
          height: 28px;
          border-radius: 50%;
          background: #fff;
          transition: left var(--transition-fast) var(--ease-out);
      }

      .sw.on i { left: 34px; }

      .prov-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }

      .prov {
          min-height: 66px;
          border-radius: 14px;
          border: none;
          color: #fff;
          font-family: 'Orbitron', sans-serif;
          font-size: 14px;
          display: flex;
          align-items: center;
          justify-content: center;
          gap: 8px;
          cursor: pointer;
      }

      .prov b { font-size: 19px; }

      .um-btn {
          min-height: 62px;
          padding: 0 22px;
          border-radius: 14px;
          border: 1px solid rgba(0, 240, 255, 0.5);
          background: rgba(0, 180, 255, 0.22);
          color: #fff;
          font-family: 'Orbitron', sans-serif;
          font-size: 16px;
          letter-spacing: 1px;
          cursor: pointer;
      }

      .um-btn.wide { width: 100%; margin-top: 10px; }
      .um-btn.small { min-height: 48px; font-size: 13px; padding: 0 14px; }
      .um-btn.danger { border-color: #ff5c7a; background: rgba(255, 50, 90, 0.2); }
      .um-btn.gold { border-color: #ffb400; background: rgba(255, 180, 0, 0.2); }
      .um-btn:disabled { opacity: 0.4; }

      .um-note {
          font-size: 13px;
          color: rgba(255, 255, 255, 0.55);
          margin: 10px 0 14px;
          line-height: 1.55;
      }

      .um-sub {
          font-family: 'Orbitron', sans-serif;
          font-size: 13px;
          letter-spacing: 2px;
          color: #00f0ff;
          margin: 18px 0 8px;
      }

      .skin-drop {
          border: 2px dashed rgba(0, 240, 255, 0.4);
          border-radius: 14px;
          padding: 20px 14px;
          text-align: center;
          font-size: 14px;
          color: rgba(255, 255, 255, 0.7);
          margin-bottom: 10px;
      }

      .skin-drop.hot { background: rgba(0, 240, 255, 0.1); }

      .preset-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }

      .preset {
          height: 62px;
          border-radius: 12px;
          border: 1px solid rgba(255, 255, 255, 0.25);
          cursor: pointer;
      }

      .stat-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 15px; }

      .stat-grid div {
          background: rgba(255, 255, 255, 0.05);
          border-radius: 10px;
          padding: 9px 11px;
      }

      .stat-grid b { color: #00f0ff; font-family: 'Orbitron', sans-serif; }

      #export-box {
          width: 100%;
          height: 74px;
          background: rgba(0, 0, 0, 0.45);
          border: 1px solid rgba(0, 240, 255, 0.3);
          color: #8ff9ff;
          border-radius: 10px;
          font-size: 12px;
          padding: 8px;
          resize: none;
      }

      .ach-list { display: flex; flex-wrap: wrap; gap: 6px; }

      .ach {
          font-size: 12px;
          padding: 4px 9px;
          border-radius: 10px;
          background: rgba(255, 230, 0, 0.14);
          border: 1px solid rgba(255, 230, 0, 0.4);
          color: #ffe600;
      }

      /* ===== Zero Arcade portal ===== */
      #portal-btn {
          position: absolute;
          right: 16px;
          bottom: 356px;
          z-index: 16;
          min-width: 106px;
          min-height: 58px;
          border-radius: 16px;
          border: 1px solid rgba(0, 240, 255, 0.55);
          background: rgba(6, 14, 34, 0.82);
          color: #00f0ff;
          font-family: 'Orbitron', sans-serif;
          font-size: 13px;
          letter-spacing: 1px;
          cursor: pointer;
          -webkit-tap-highlight-color: transparent;
      }

      #portal {
          position: absolute;
          inset: 0;
          z-index: 60;
          display: none;
          flex-direction: column;
          background: linear-gradient(180deg, #05081c, #0a0f2e 45%, #03040d);
          font-family: 'Inter', sans-serif;
          color: #fff;
      }

      #portal.visible { display: flex; }

      #pt-top {
          display: flex;
          align-items: center;
          gap: 10px;
          padding: 14px 16px;
          border-bottom: 1px solid rgba(0, 240, 255, 0.22);
          background: rgba(3, 6, 18, 0.9);
      }

      #pt-logo {
          font-family: 'Orbitron', sans-serif;
          font-size: 21px;
          letter-spacing: 2px;
      }

      #pt-logo span { color: #00f0ff; }

      #pt-wallet {
          margin-left: auto;
          display: flex;
          align-items: center;
          gap: 6px;
          padding: 9px 14px;
          border-radius: 20px;
          background: rgba(255, 180, 0, 0.14);
          border: 1px solid rgba(255, 180, 0, 0.5);
          font-family: 'Orbitron', sans-serif;
          font-size: 15px;
          color: #ffc94a;
          cursor: pointer;
      }

      .pt-icon-btn {
          width: 58px;
          height: 56px;
          flex: none;
          border-radius: 14px;
          border: 1px solid rgba(0, 240, 255, 0.45);
          background: rgba(0, 150, 220, 0.2);
          color: #fff;
          font-size: 20px;
          cursor: pointer;
      }

      #pt-nav {
          display: flex;
          gap: 8px;
          overflow-x: auto;
          padding: 10px 12px;
          border-bottom: 1px solid rgba(0, 240, 255, 0.15);
      }

      .pt-tab {
          flex: none;
          min-height: 56px;
          padding: 0 16px;
          border-radius: 14px;
          border: 1px solid rgba(0, 240, 255, 0.25);
          background: rgba(255, 255, 255, 0.05);
          color: rgba(255, 255, 255, 0.75);
          font-family: 'Orbitron', sans-serif;
          font-size: 13px;
          letter-spacing: 1px;
          white-space: nowrap;
          cursor: pointer;
      }

      .pt-tab.on { background: rgba(0, 240, 255, 0.2); border-color: #00f0ff; color: #fff; }

      #pt-body { flex: 1; overflow-y: auto; padding: 16px 16px 46px; }

      .pt-hero {
          border-radius: 18px;
          padding: 22px 20px;
          margin-bottom: 16px;
          border: 1px solid rgba(0, 240, 255, 0.35);
          background: radial-gradient(circle at 15% 20%, rgba(0, 240, 255, 0.32), rgba(120, 0, 255, 0.22) 55%, rgba(0, 0, 0, 0.45));
      }

      .pt-kicker {
          font-family: 'Orbitron', sans-serif;
          font-size: 11px;
          letter-spacing: 3px;
          color: rgba(255, 255, 255, 0.6);
      }

      .pt-hero h1 { font-family: 'Orbitron', sans-serif; font-size: 30px; margin: 8px 0; }
      .pt-hero p { margin: 0 0 14px; font-size: 16px; color: rgba(255, 255, 255, 0.78); line-height: 1.5; }
      .pt-hero-row { display: flex; flex-wrap: wrap; gap: 10px; }

      .pt-btn {
          min-height: 62px;
          padding: 0 20px;
          border-radius: 14px;
          border: 1px solid rgba(0, 240, 255, 0.45);
          background: rgba(0, 180, 255, 0.18);
          color: #fff;
          font-family: 'Orbitron', sans-serif;
          font-size: 15px;
          letter-spacing: 1px;
          cursor: pointer;
          -webkit-tap-highlight-color: transparent;
      }

      .pt-btn.primary { background: linear-gradient(180deg, rgba(0, 240, 255, 0.4), rgba(0, 110, 200, 0.4)); border-color: #00f0ff; }
      .pt-btn.gold { border-color: #ffb400; background: rgba(255, 180, 0, 0.22); color: #ffe0a0; }
      .pt-btn.danger { border-color: #ff5c7a; background: rgba(255, 50, 90, 0.2); }
      .pt-btn.small { min-height: 50px; font-size: 13px; padding: 0 14px; }
      .pt-btn.wide { width: 100%; margin-top: 8px; }
      .pt-btn:disabled { opacity: 0.4; }

      .pt-sec {
          font-family: 'Orbitron', sans-serif;
          font-size: 13px;
          letter-spacing: 3px;
          color: #00f0ff;
          margin: 20px 0 10px;
      }

      .pt-card {
          border-radius: 16px;
          padding: 14px;
          margin-bottom: 12px;
          background: rgba(255, 255, 255, 0.05);
          border: 1px solid rgba(255, 255, 255, 0.09);
      }

      .pt-stats4 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; }

      .pt-stat {
          background: rgba(255, 255, 255, 0.05);
          border-radius: 12px;
          padding: 10px 12px;
          font-size: 13px;
          color: rgba(255, 255, 255, 0.6);
      }

      .pt-stat b {
          display: block;
          font-family: 'Orbitron', sans-serif;
          font-size: 19px;
          color: #fff;
          margin-top: 3px;
      }

      .pt-game {
          display: flex;
          gap: 12px;
          align-items: center;
          border-radius: 16px;
          padding: 12px;
          margin-bottom: 10px;
          background: rgba(255, 255, 255, 0.05);
          border: 1px solid rgba(255, 255, 255, 0.1);
      }

      .pt-game-art {
          width: 92px;
          height: 92px;
          flex: none;
          border-radius: 14px;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 38px;
      }

      .pt-game-info { flex: 1; min-width: 0; }
      .pt-game-info h4 { margin: 0; font-family: 'Orbitron', sans-serif; font-size: 18px; }
      .pt-game-info p { margin: 4px 0 8px; font-size: 14px; color: rgba(255, 255, 255, 0.68); line-height: 1.4; }

      .pt-badge {
          display: inline-block;
          padding: 3px 9px;
          border-radius: 9px;
          font-family: 'Orbitron', sans-serif;
          font-size: 10px;
          letter-spacing: 1px;
          background: rgba(0, 240, 255, 0.18);
          border: 1px solid rgba(0, 240, 255, 0.45);
          color: #8ff9ff;
      }

      .pt-badge.soon { background: rgba(255, 255, 255, 0.07); border-color: rgba(255, 255, 255, 0.25); color: rgba(255, 255, 255, 0.6); }
      .pt-badge.hot { background: rgba(255, 60, 100, 0.2); border-color: #ff5c7a; color: #ffb3c4; }

      .pt-grid2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }

      .pt-quick {
          min-height: 84px;
          border-radius: 14px;
          border: 1px solid rgba(0, 240, 255, 0.22);
          background: rgba(255, 255, 255, 0.05);
          color: #fff;
          font-family: 'Orbitron', sans-serif;
          font-size: 12px;
          letter-spacing: 1px;
          cursor: pointer;
      }

      .pt-quick b { display: block; font-size: 24px; margin-bottom: 6px; }

      .pt-note { font-size: 13px; color: rgba(255, 255, 255, 0.55); line-height: 1.55; margin: 8px 0; }

      .pt-row, .pt-row2 { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-top: 10px; }
      .pt-row label { font-size: 14px; color: rgba(255, 255, 255, 0.7); }
      .pt-row2 > .pt-btn { flex: 1; }

      .pt-input {
          flex: 1;
          min-width: 90px;
          min-height: 50px;
          background: rgba(0, 0, 0, 0.45);
          border: 1px solid rgba(0, 240, 255, 0.3);
          border-radius: 10px;
          color: #fff;
          padding: 10px;
          font-size: 15px;
          outline: none;
      }

      .pt-item { display: flex; align-items: center; gap: 12px; border-radius: 14px; padding: 12px; margin-bottom: 10px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.09); }
      .pt-item.owned { border-color: rgba(0, 255, 160, 0.45); }
      .pt-item-icon { width: 58px; height: 58px; flex: none; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 26px; background: rgba(0, 240, 255, 0.12); }
      .pt-item-info { flex: 1; min-width: 0; }
      .pt-item-info h5 { margin: 0; font-family: 'Orbitron', sans-serif; font-size: 15px; }
      .pt-item-info p { margin: 3px 0 0; font-size: 13px; color: rgba(255, 255, 255, 0.62); line-height: 1.4; }
      .pt-price { font-family: 'Orbitron', sans-serif; font-size: 14px; color: #ffc94a; }

      #crash-canvas { display: block; width: 100%; height: auto; border-radius: 12px; background: rgba(0, 0, 0, 0.45); }
      #crash-mult { font-family: 'Orbitron', sans-serif; font-size: 44px; text-align: center; margin-top: 10px; color: #00ffa2; }
      #crash-mult.bust { color: #ff3b6b; }
      #crash-mult.win { color: #ffe600; }

      .pt-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; }
      .pt-chip { font-family: 'Orbitron', sans-serif; font-size: 12px; padding: 4px 9px; border-radius: 9px; background: rgba(255, 60, 100, 0.16); border: 1px solid rgba(255, 60, 100, 0.4); color: #ffb3c4; }
      .pt-chip.good { background: rgba(0, 255, 160, 0.14); border-color: rgba(0, 255, 160, 0.4); color: #8effd0; }

      .pt-tx { display: flex; justify-content: space-between; gap: 10px; font-size: 13px; padding: 7px 4px; border-bottom: 1px solid rgba(255, 255, 255, 0.06); }
      .pt-tx b { font-family: 'Orbitron', sans-serif; }
      .pt-tx .plus { color: #8effd0; }
      .pt-tx .minus { color: #ffb3c4; }

      .pt-news h4 { margin: 0 0 6px; font-family: 'Orbitron', sans-serif; font-size: 16px; color: #00f0ff; }
      .pt-news p { margin: 0; font-size: 14px; color: rgba(255, 255, 255, 0.7); line-height: 1.5; }

      /* ===== Mini games, quests, pass, jukebox ===== */
      .pt-search {
          width: 100%;
          min-height: 54px;
          margin-bottom: 10px;
          box-sizing: border-box;
          background: rgba(0, 0, 0, 0.45);
          border: 1px solid rgba(0, 240, 255, 0.3);
          border-radius: 12px;
          color: #fff;
          padding: 10px 14px;
          font-size: 16px;
          outline: none;
      }

      .pt-chiprow { display: flex; gap: 8px; overflow-x: auto; padding-bottom: 8px; }
      .pt-chiprow .pt-tab { min-height: 48px; font-size: 12px; padding: 0 13px; }

      .fav-btn {
          flex: none;
          width: 44px;
          height: 44px;
          background: none;
          border: none;
          color: #ffe600;
          font-size: 24px;
          cursor: pointer;
          -webkit-tap-highlight-color: transparent;
      }

      .mini-wrap {
          border-radius: 16px;
          padding: 14px;
          background: rgba(255, 255, 255, 0.05);
          border: 1px solid rgba(0, 240, 255, 0.22);
      }

      .mini-big {
          width: 100%;
          min-height: 160px;
          border-radius: 16px;
          border: 2px solid rgba(0, 240, 255, 0.45);
          background: rgba(0, 0, 0, 0.42);
          color: #fff;
          font-family: 'Orbitron', sans-serif;
          font-size: 22px;
          letter-spacing: 1px;
          cursor: pointer;
          -webkit-tap-highlight-color: transparent;
      }

      .mini-big.go { background: rgba(0, 255, 140, 0.35); border-color: #00ffa2; }
      .mini-big.wait { background: rgba(255, 60, 90, 0.22); border-color: #ff5c7a; }

      .mini-grid3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin: 10px 0; }

      .mini-tile {
          min-height: 80px;
          border-radius: 12px;
          border: 1px solid rgba(0, 240, 255, 0.3);
          background: rgba(0, 0, 0, 0.35);
          color: #fff;
          font-size: 26px;
          cursor: pointer;
          -webkit-tap-highlight-color: transparent;
      }

      .mini-tile.safe { background: rgba(0, 255, 160, 0.25); border-color: #00ffa2; }
      .mini-tile.bomb { background: rgba(255, 50, 90, 0.3); border-color: #ff5c7a; }
      .mini-tile.lit { background: rgba(255, 230, 0, 0.55); border-color: #ffe600; }

      .mini-out {
          font-family: 'Orbitron', sans-serif;
          font-size: 17px;
          text-align: center;
          margin: 12px 0;
          min-height: 24px;
          color: #ffe600;
      }

      .mini-reels {
          display: flex;
          gap: 12px;
          justify-content: center;
          align-items: center;
          font-family: 'Orbitron', sans-serif;
          font-size: 44px;
          margin: 10px 0;
          min-height: 58px;
          color: #fff;
      }

      .q-row {
          display: flex;
          align-items: center;
          gap: 10px;
          padding: 11px 12px;
          border-radius: 12px;
          background: rgba(255, 255, 255, 0.05);
          margin-bottom: 8px;
      }

      .q-row.done { border: 1px solid rgba(0, 255, 160, 0.5); }
      .q-row .q-info { flex: 1; min-width: 0; font-size: 14px; }

      .q-bar { height: 7px; border-radius: 4px; background: rgba(255, 255, 255, 0.15); margin-top: 6px; overflow: hidden; }
      .q-bar span { display: block; height: 100%; background: linear-gradient(90deg, #00f0ff, #ffe600); }

      .tier-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }

      .tier {
          border-radius: 12px;
          padding: 9px 4px;
          text-align: center;
          font-family: 'Orbitron', sans-serif;
          font-size: 11px;
          line-height: 1.5;
          background: rgba(255, 255, 255, 0.05);
          border: 1px solid rgba(255, 255, 255, 0.12);
          color: rgba(255, 255, 255, 0.75);
      }

      .tier.on { border-color: #00ffa2; color: #8effd0; }
      .tier.got { background: rgba(0, 255, 160, 0.18); }

      .track-row {
          display: flex;
          align-items: center;
          gap: 10px;
          padding: 10px;
          border-radius: 12px;
          background: rgba(255, 255, 255, 0.05);
          margin-bottom: 8px;
      }

      .track-row.on { border: 1px solid #b388ff; }

      /* ===== Zero The Legend: premium 3D brand + micro-game kit ===== */
      #pt-logo {
          display: flex;
          align-items: center;
          min-width: 176px;
          min-height: 42px;
          transform: translateZ(0);
      }

      #pt-logo-img {
          display: none;
          width: 188px;
          height: 48px;
          object-fit: contain;
          object-position: left center;
          filter: drop-shadow(0 0 10px rgba(0, 240, 255, 0.55));
      }

      #pt-logo.has-asset #pt-logo-img { display: block; }
      #pt-logo.has-asset #pt-logo-fallback { display: none; }
      #pt-logo-fallback { white-space: nowrap; }

      #portal { perspective: 1200px; }
      #portal .pt-card, #portal .pt-game, #portal .pt-item, #portal .pt-hero {
          transform-style: preserve-3d;
          transition: transform 220ms ease, box-shadow 220ms ease, border-color 220ms ease;
      }
      #portal .pt-card:hover, #portal .pt-game:hover, #portal .pt-item:hover {
          transform: translateY(-3px) rotateX(1deg) rotateY(-1deg);
          box-shadow: 0 14px 34px rgba(0, 0, 0, 0.25), 0 0 22px rgba(0, 240, 255, 0.12);
          border-color: rgba(0, 240, 255, 0.32);
      }

      .legend-stage {
          position: relative;
          min-height: 260px;
          overflow: hidden;
          border-radius: 16px;
          border: 1px solid rgba(0, 240, 255, 0.38);
          background: radial-gradient(circle at 50% 35%, rgba(0, 240, 255, 0.18), rgba(3, 6, 20, 0.92) 70%);
          box-shadow: inset 0 0 28px rgba(0, 240, 255, 0.1), 0 0 22px rgba(0, 0, 0, 0.2);
      }
      .legend-stage button { position: relative; z-index: 2; }
      .legend-target {
          position: absolute;
          width: 74px;
          height: 74px;
          min-height: 74px;
          padding: 0;
          border-radius: 50%;
          border: 2px solid #ffe600;
          background: radial-gradient(circle at 35% 30%, #fff, #ffe600 28%, #ff7a18 72%, #ff2d6b);
          box-shadow: 0 0 28px rgba(255, 230, 0, 0.72);
          transform: translate(-50%, -50%);
      }
      .legend-lanes { display: grid; grid-template-columns: repeat(3, 1fr); gap: 9px; margin-top: 12px; }
      .legend-lane, .legend-choice {
          min-height: 72px;
          border-radius: 12px;
          border: 1px solid rgba(0, 240, 255, 0.35);
          background: rgba(0, 0, 0, 0.32);
          color: #fff;
          font-family: 'Orbitron', sans-serif;
          cursor: pointer;
      }
      .legend-lane.hit, .legend-choice.hit { background: rgba(0, 255, 160, 0.32); border-color: #00ffa2; }
      .legend-meter { height: 12px; border-radius: 8px; background: rgba(255,255,255,0.14); overflow: hidden; margin: 14px 0; }
      .legend-meter span { display: block; height: 100%; width: 42%; background: linear-gradient(90deg,#ff2d6b,#ffe600,#00ffa2); }
      .legend-3d-off #game-canvas { filter: saturate(0.8) brightness(0.92); }
      .legend-3d-off #portal .pt-card, .legend-3d-off #portal .pt-game, .legend-3d-off #portal .pt-item { transform: none !important; }
      /* ===== Responsive spatial portal shell ===== */
      :root { --space-cyan: #00f0ff; --space-violet: #8b5cff; --space-gold: #ffe600; --space-ink: #030611; }

      #game-world {
          isolation: isolate;
          overflow: hidden;
          background:
            radial-gradient(circle at 50% 42%, rgba(19, 35, 91, 0.42), transparent 42%),
            radial-gradient(circle at 16% 18%, rgba(0, 240, 255, 0.09), transparent 25%),
            #030611;
      }

      #game-world::before, #game-world::after {
          content: '';
          position: absolute;
          inset: -18%;
          pointer-events: none;
          z-index: 0;
      }
      #game-world::before {
          opacity: 0.5;
          background-image:
            radial-gradient(circle, rgba(255,255,255,.85) 0 1px, transparent 1.5px),
            radial-gradient(circle, rgba(0,240,255,.55) 0 1px, transparent 1.5px);
          background-size: 97px 113px, 173px 151px;
          background-position: 12px 28px, 54px 3px;
          animation: cosmicDrift 34s linear infinite;
      }
      #game-world::after {
          background: radial-gradient(ellipse at center, transparent 38%, rgba(0,0,0,.58) 100%);
      }
      #game-canvas, .hud, #portal, #game-over, #user-menu, .overlay-panel { position: absolute; }
      #game-canvas { z-index: 1; }

      @keyframes cosmicDrift { from { transform: translate3d(0,0,0) rotate(0deg); } to { transform: translate3d(3%, -2%, 0) rotate(2deg); } }
      @keyframes spacePulse { 0%,100% { opacity: .35; transform: scale(.96); } 50% { opacity: .8; transform: scale(1.04); } }

      /* The portal becomes a true space-station layout on wide screens. Existing
         page IDs and controls stay intact; only the shell changes. */
      #portal {
          overflow: hidden;
          display: none;
          position: absolute;
          grid-template-columns: 238px minmax(0, 1fr);
          grid-template-rows: 88px minmax(0, 1fr);
          grid-template-areas: "top top" "nav body";
          background: #030611;
      }
      #portal.visible { display: grid; }
      .space-backdrop {
          position: absolute;
          inset: 0;
          overflow: hidden;
          pointer-events: none;
          z-index: 0;
          background:
            radial-gradient(circle at 78% 18%, rgba(122, 53, 255, .24), transparent 23%),
            radial-gradient(circle at 28% 74%, rgba(0, 240, 255, .14), transparent 30%),
            linear-gradient(135deg, #040719, #080c26 48%, #02030b);
      }
      .starfield { position: absolute; inset: -30%; background-repeat: repeat; opacity: .65; }
      .starfield-a { background-image: radial-gradient(circle, #fff 0 1px, transparent 1.5px); background-size: 83px 91px; animation: cosmicDrift 45s linear infinite; }
      .starfield-b { background-image: radial-gradient(circle, rgba(0,240,255,.9) 0 1px, transparent 1.6px); background-size: 137px 149px; animation: cosmicDrift 70s linear reverse infinite; opacity: .4; }
      .space-orbit { position: absolute; border: 1px solid rgba(0,240,255,.18); border-radius: 50%; transform: rotate(-18deg); box-shadow: 0 0 26px rgba(0,240,255,.08); }
      .orbit-one { width: 620px; height: 220px; right: -150px; top: 80px; }
      .orbit-two { width: 520px; height: 180px; left: -180px; bottom: 100px; border-color: rgba(139,92,255,.2); transform: rotate(22deg); }
      .space-planet { position: absolute; width: 210px; height: 210px; right: 5%; top: 18%; border-radius: 50%; opacity: .14; background: radial-gradient(circle at 32% 26%, #fff, #00f0ff 8%, #173987 42%, #030611 72%); filter: blur(.2px); animation: spacePulse 8s ease-in-out infinite; }
      #portal > *:not(.space-backdrop) { position: relative; z-index: 1; }
      #pt-top { grid-area: top; min-height: 88px; box-sizing: border-box; padding: 14px 22px; background: rgba(3,6,18,.74); backdrop-filter: blur(18px); border-bottom: 1px solid rgba(0,240,255,.25); position: relative; z-index: 320; }
      #pt-nav { grid-area: nav; flex-direction: column; align-items: stretch; justify-content: flex-start; overflow-y: auto; overflow-x: hidden; padding: 18px 12px; background: rgba(2,5,18,.78); border-right: 1px solid rgba(0,240,255,.2); }
      #pt-nav .pt-tab { width: 100%; text-align: left; justify-content: flex-start; }
      #pt-body { grid-area: body; min-width: 0; min-height: 0; padding: 24px clamp(18px, 4vw, 52px) 54px; background: linear-gradient(180deg, rgba(3,6,18,.34), rgba(2,3,10,.72)); }
      #pt-body > * { max-width: 980px; margin-left: auto; margin-right: auto; }
      #pt-body::-webkit-scrollbar, #pt-nav::-webkit-scrollbar { width: 8px; }
      #pt-body::-webkit-scrollbar-thumb, #pt-nav::-webkit-scrollbar-thumb { background: rgba(0,240,255,.28); border-radius: 10px; }
      #pt-logo-img { width: clamp(150px, 18vw, 220px); }
      .pt-hero { position: relative; overflow: hidden; box-shadow: inset 0 0 45px rgba(0,240,255,.08), 0 16px 50px rgba(0,0,0,.2); }
      .pt-hero::after { content: '✦  ✧  ✦'; position: absolute; right: 18px; top: 16px; color: rgba(255,230,0,.72); letter-spacing: 14px; font-size: 18px; animation: spacePulse 3s ease-in-out infinite; }

      /* Orbital fleet: planets, satellites and shuttles deepen the space-station portal. */
      .space-planet-two {
          position: absolute;
          width: 118px;
          height: 118px;
          left: 25%;
          bottom: 12%;
          border-radius: 50%;
          opacity: .24;
          background:
            radial-gradient(circle at 30% 25%, #fff 0 3%, #ffd36a 8%, transparent 13%),
            radial-gradient(circle at 58% 62%, rgba(255,120,65,.7) 0 7%, transparent 8%),
            radial-gradient(circle at 40% 40%, #ffb347, #8f2dff 58%, #190a48 78%, transparent 80%);
          box-shadow: 0 0 28px rgba(255,120,80,.28), inset -18px -16px 24px rgba(10,0,50,.72);
          animation: planetFloat 10s ease-in-out infinite;
      }
      .space-satellite {
          position: absolute;
          width: 150px;
          height: 72px;
          right: 17%;
          bottom: 18%;
          opacity: .42;
          transform: rotate(-17deg);
          animation: satelliteDrift 18s ease-in-out infinite;
      }
      .space-satellite::before {
          content: '';
          position: absolute;
          left: 48px;
          top: 23px;
          width: 54px;
          height: 26px;
          border-radius: 8px;
          background: linear-gradient(135deg, #eafcff, #5478a8 45%, #18233f);
          border: 1px solid rgba(255,255,255,.7);
          box-shadow: 0 0 16px rgba(0,240,255,.55);
      }
      .space-satellite::after {
          content: '';
          position: absolute;
          left: 0;
          top: 16px;
          width: 42px;
          height: 40px;
          border: 8px solid rgba(0,240,255,.8);
          border-top-color: transparent;
          border-bottom-color: transparent;
          box-shadow: 100px 0 0 -1px rgba(0,240,255,.8);
      }
      .space-shuttle {
          position: absolute;
          width: 92px;
          height: 34px;
          left: 9%;
          top: 31%;
          opacity: .56;
          transform: rotate(-20deg);
          animation: shuttleCruise 16s linear infinite;
      }
      .space-shuttle::before {
          content: '';
          position: absolute;
          left: 18px;
          top: 4px;
          width: 60px;
          height: 24px;
          clip-path: polygon(0 50%, 28% 0, 100% 38%, 78% 100%, 28% 78%);
          background: linear-gradient(135deg, #fff, #8ff9ff 38%, #5665ff 72%, #251047);
          border: 1px solid rgba(255,255,255,.8);
          filter: drop-shadow(0 0 8px rgba(0,240,255,.8));
      }
      .space-shuttle::after {
          content: '';
          position: absolute;
          left: -34px;
          top: 14px;
          width: 48px;
          height: 6px;
          border-radius: 50%;
          background: linear-gradient(90deg, transparent, #ffe600, #ff7a18);
          box-shadow: 0 0 15px #ff7a18;
      }
      .orbit-label {
          position: absolute;
          font: 700 10px Orbitron, sans-serif;
          letter-spacing: 2px;
          color: rgba(143,249,255,.62);
          text-transform: uppercase;
          pointer-events: none;
      }
      .orbit-label.station { left: 24px; top: 112px; }
      .orbit-label.shuttle-label { left: 10%; top: 38%; }
      @keyframes planetFloat { 0%,100% { transform: translate3d(0,0,0) scale(.98); } 50% { transform: translate3d(14px,-10px,0) scale(1.04); } }
      @keyframes satelliteDrift { 0%,100% { transform: translate3d(0,0,0) rotate(-17deg); } 50% { transform: translate3d(-28px,15px,0) rotate(-8deg); } }
      @keyframes shuttleCruise { 0% { transform: translate3d(-30px,18px,0) rotate(-20deg); opacity: 0; } 12%,78% { opacity: .56; } 100% { transform: translate3d(720px,-150px,0) rotate(-12deg); opacity: 0; } }

      /* Small screens keep the station dock touch-first and fluid. */
      @media (max-width: 899px) {
          #portal { display: none; grid-template-columns: 1fr; grid-template-rows: auto auto minmax(0,1fr); grid-template-areas: "top" "nav" "body"; }
          #portal.visible { display: grid; }
          #pt-top { min-height: 78px; padding: 10px 12px; gap: 7px; }
          #pt-nav { flex-direction: row; align-items: center; overflow-x: auto; overflow-y: hidden; padding: 9px 10px; border-right: 0; border-bottom: 1px solid rgba(0,240,255,.18); }
          #pt-nav .pt-tab { width: auto; text-align: center; justify-content: center; }
          #pt-body { padding: 15px 12px 38px; }
          #pt-logo { min-width: 0; flex: 1; }
          #pt-logo-img { width: min(44vw, 188px); height: 42px; }
          #pt-logo-fallback { font-size: clamp(15px, 4vw, 21px); }
          #pt-wallet { padding: 8px 10px; font-size: 12px; }
          .pt-icon-btn { width: 54px; height: 52px; }
          .pt-hero h1 { font-size: clamp(23px, 7vw, 30px); }
          .pt-hero p { font-size: clamp(14px, 4vw, 16px); }
          .pt-game { align-items: flex-start; }
          .pt-game-art { width: clamp(68px, 18vw, 92px); height: clamp(68px, 18vw, 92px); font-size: clamp(28px, 7vw, 38px); }
          .space-planet { width: 150px; height: 150px; right: -35px; top: 13%; }
          .orbit-one { right: -300px; top: 100px; }
      }

      @media (max-width: 520px) {
          .pt-stats4, .pt-grid2 { grid-template-columns: repeat(2, minmax(0,1fr)); }
          .pt-game { flex-wrap: wrap; }
          .pt-game-info { min-width: calc(100% - 82px); }
          .pt-game .fav-btn { margin-left: auto; }
          .pt-btn { min-height: 58px; }
          #pt-user, #pt-close { width: 48px; height: 48px; font-size: 17px; }
          #pt-wallet { margin-left: 0; }
      }

      /* Fluid arena HUD: no control is smaller than a comfortable thumb target. */
      @media (max-width: 899px), (max-height: 900px) {
          .score-container { padding-top: 12px; }
          .score-display { font-size: clamp(26px, 7vw, 36px); }
          .leaderboard { top: 12px; right: 10px; min-width: 148px; padding: 9px 10px; font-size: 13px; }
          .leaderboard h3 { font-size: 12px; margin-bottom: 5px; }
          #profile-chip { top: 10px; left: 10px; min-height: 48px; padding-right: 10px; }
          #chip-avatar { width: 36px; height: 36px; }
          #chip-name { max-width: 82px; font-size: 12px; }
          .stat-strip { top: 68px; gap: 5px; font-size: 11px; }
          .ability-hud { top: 104px; left: 10px; gap: 5px; }
          .ability-chip { min-width: 124px; padding: 5px 8px; font-size: 10px; }
          #notif-feed { top: 132px; right: 10px; width: min(210px, 42vw); font-size: 12px; }
          #minimap-wrap { right: 10px; bottom: 178px; width: clamp(112px, 22vw, 156px); height: clamp(112px, 22vw, 156px); }
          #chat-panel { left: 10px; bottom: 178px; width: min(250px, 42vw); }
          .action-buttons { bottom: 16px; gap: clamp(6px, 2vw, 14px); }
          .action-btn { width: clamp(78px, 14vw, 106px); height: clamp(78px, 14vw, 106px); font-size: clamp(11px, 2vw, 15px); }
          .action-btn .icon { font-size: clamp(24px, 4vw, 30px); }
      }

      /* ===== Portal footer chats + multicolor animated background ===== */
      #portal {
          isolation: isolate;
      }
      #portal::before {
          content: '';
          position: absolute;
          inset: -42%;
          z-index: 0;
          pointer-events: none;
          background: conic-gradient(from 0deg, rgba(0,240,255,.3), rgba(123,44,255,.28), rgba(255,45,149,.25), rgba(255,230,0,.22), rgba(0,255,162,.25), rgba(0,240,255,.3));
          filter: blur(82px) saturate(1.45);
          opacity: .52;
          animation: portalRainbowSpin 24s linear infinite;
      }
      #portal::after {
          content: '';
          position: absolute;
          inset: 0;
          z-index: 0;
          pointer-events: none;
          background: radial-gradient(circle at 18% 78%, rgba(255,45,149,.16), transparent 30%), radial-gradient(circle at 82% 22%, rgba(0,255,162,.14), transparent 28%), linear-gradient(115deg, rgba(0,240,255,.08), transparent 42%, rgba(255,230,0,.08));
          mix-blend-mode: screen;
          opacity: .72;
          animation: portalRainbowBreathe 9s ease-in-out infinite;
      }
      @keyframes portalRainbowSpin {
          from { transform: rotate(0deg) scale(1); }
          50% { transform: rotate(180deg) scale(1.08); }
          to { transform: rotate(360deg) scale(1); }
      }
      @keyframes portalRainbowBreathe {
          0%, 100% { opacity: .42; transform: scale(1); }
          50% { opacity: .86; transform: scale(1.04); }
      }
      .portal-chat-dock {
          position: absolute;
          bottom: 18px;
          z-index: 8;
          width: min(310px, calc(50% - 28px));
          color: #fff;
          font-family: Inter, sans-serif;
          pointer-events: auto;
      }
      #portal-support-dock { left: 18px; }
      #portal-fb-chat-dock { right: 18px; }
      .portal-chat-dock[hidden] { display: none !important; }
      .portal-chat-toggle {
          position: relative;
          width: 100%;
          min-height: 68px;
          padding: 0 15px;
          display: flex;
          align-items: center;
          gap: 10px;
          border: 1px solid rgba(0,240,255,.48);
          border-radius: 16px;
          background: linear-gradient(135deg, rgba(7,17,47,.94), rgba(28,10,62,.9));
          color: #fff;
          text-align: left;
          cursor: pointer;
          box-shadow: 0 10px 26px rgba(0,0,0,.28), 0 0 22px rgba(0,240,255,.13);
          -webkit-tap-highlight-color: transparent;
      }
      .portal-chat-toggle:hover, .portal-chat-toggle:focus { border-color: #00f0ff; outline: none; box-shadow: 0 10px 30px rgba(0,0,0,.3), 0 0 28px rgba(0,240,255,.28); }
      .portal-chat-toggle .chat-brand-icon { width: 38px; height: 38px; flex: none; display: grid; place-items: center; border-radius: 12px; color: #03101c; background: linear-gradient(135deg,#00f0ff,#ffe600); font: 700 20px Orbitron,sans-serif; }
      #portal-support-dock .chat-brand-icon { background: linear-gradient(135deg,#ff2d6b,#b388ff); color: #fff; }
      .portal-chat-toggle strong { display: block; font: 700 12px Orbitron,sans-serif; letter-spacing: 1px; }
      .portal-chat-toggle small { display: block; margin-top: 3px; color: rgba(255,255,255,.56); font-size: 11px; }
      .portal-chat-badge { margin-left: auto; min-width: 26px; height: 26px; padding: 0 7px; box-sizing: border-box; display: grid; place-items: center; border-radius: 13px; color: #03101c; background: #ffe600; font: 700 11px Inter,sans-serif; }
      .portal-chat-dock.open .portal-chat-toggle { border-bottom-left-radius: 8px; border-bottom-right-radius: 8px; }
      .portal-chat-panel {
          display: none;
          flex-direction: column;
          margin-bottom: 8px;
          padding: 12px;
          border: 1px solid rgba(0,240,255,.3);
          border-radius: 16px;
          background: rgba(3,8,27,.95);
          box-shadow: 0 16px 38px rgba(0,0,0,.4), inset 0 0 22px rgba(0,240,255,.05);
          backdrop-filter: blur(16px);
      }
      .portal-chat-dock.open .portal-chat-panel { display: flex; }
      .portal-chat-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding-bottom: 9px; border-bottom: 1px solid rgba(255,255,255,.1); }
      .portal-chat-head b { color: #8ff9ff; font: 700 11px Orbitron,sans-serif; letter-spacing: 1px; }
      .portal-chat-head span { color: rgba(255,255,255,.52); font-size: 10px; }
      .portal-chat-peer-list { display: flex; gap: 6px; overflow-x: auto; padding: 9px 0; }
      .portal-chat-peer { min-height: 42px; padding: 0 10px; border: 1px solid rgba(0,240,255,.2); border-radius: 10px; background: rgba(255,255,255,.06); color: rgba(255,255,255,.72); cursor: pointer; white-space: nowrap; font: 600 11px Inter,sans-serif; }
      .portal-chat-peer.on { border-color: #00f0ff; background: rgba(0,240,255,.18); color: #fff; }
      .portal-chat-log { min-height: 72px; max-height: 178px; overflow-y: auto; padding: 3px 2px 7px; }
      .portal-chat-empty { padding: 16px 6px; color: rgba(255,255,255,.48); text-align: center; font-size: 12px; line-height: 1.45; }
      .portal-chat-message { max-width: 88%; margin: 5px 0; padding: 8px 10px; border-radius: 11px; background: rgba(255,255,255,.08); color: rgba(255,255,255,.82); font-size: 12px; line-height: 1.4; overflow-wrap: anywhere; }
      .portal-chat-message.me { margin-left: auto; background: rgba(0,180,255,.24); border: 1px solid rgba(0,240,255,.2); }
      .portal-chat-message b { display: block; margin-bottom: 3px; color: #8ff9ff; font-size: 10px; }
      .portal-chat-message small { display: block; margin-top: 4px; color: rgba(255,255,255,.4); font-size: 9px; }
      .portal-chat-form { display: flex; gap: 6px; padding-top: 8px; border-top: 1px solid rgba(255,255,255,.1); }
      .portal-chat-form input { flex: 1; min-width: 0; min-height: 46px; box-sizing: border-box; padding: 0 10px; border: 1px solid rgba(0,240,255,.25); border-radius: 9px; background: rgba(0,0,0,.38); color: #fff; outline: none; font: 13px Inter,sans-serif; }
      .portal-chat-form input:focus { border-color: #00f0ff; }
      .portal-chat-form button { min-width: 58px; min-height: 46px; border: 1px solid rgba(0,240,255,.4); border-radius: 9px; background: rgba(0,180,255,.24); color: #fff; cursor: pointer; font-weight: 700; }
      .portal-chat-lock { padding: 10px; border: 1px solid rgba(255,45,107,.35); border-radius: 10px; background: rgba(255,45,107,.1); color: #ffb3c4; font-size: 12px; line-height: 1.45; }
      #portal > .portal-chat-dock {
          position: absolute;
          z-index: 8;
      }
      @media (max-width: 899px) {
          .portal-chat-dock { bottom: 12px; width: calc(50% - 18px); }
          #portal-support-dock { left: 12px; }
          #portal-fb-chat-dock { right: 12px; }
          .portal-chat-panel { max-height: 300px; }
      }
      @media (max-width: 520px) {
          .portal-chat-dock { width: calc(100% - 24px); left: 12px !important; right: 12px !important; }
          #portal-fb-chat-dock { bottom: 12px; }
          #portal-support-dock { bottom: 300px; }
      }
      @media (prefers-reduced-motion: reduce) {
          #game-world::before, .starfield-a, .starfield-b, .space-planet, .space-planet-two, .space-satellite, .space-shuttle, .pt-hero::after, #portal::before, #portal::after { animation: none; }
          *, *::before, *::after { scroll-behavior: auto !important; }
      }
      /* ===== Zero Omni Hub: one local media + social control room ===== */
      .omni-shell { width:100%; max-width:1380px !important; margin:0 auto; color:#fff; }
      .omni-hero { position:relative; overflow:hidden; padding:22px; margin-bottom:14px; border:1px solid rgba(255,230,0,.36); border-radius:18px; background:radial-gradient(circle at 88% 12%,rgba(255,230,0,.2),transparent 27%),radial-gradient(circle at 12% 18%,rgba(0,240,255,.2),transparent 35%),linear-gradient(135deg,rgba(10,20,55,.96),rgba(56,12,84,.86)); box-shadow:0 15px 38px rgba(0,0,0,.22); }
      .omni-hero h1 { margin:6px 0 7px; font:700 clamp(24px,4vw,39px) Orbitron,sans-serif; }
      .omni-hero p { max-width:820px; margin:0; color:rgba(255,255,255,.76); line-height:1.55; }
      .omni-kicker { color:#ffe600; font:700 10px Orbitron,sans-serif; letter-spacing:3px; }
      .omni-controls { display:flex; gap:8px; align-items:center; flex-wrap:wrap; margin-top:16px; }
      .omni-controls select, .omni-search, .omni-input { min-height:54px; box-sizing:border-box; border:1px solid rgba(0,240,255,.3); border-radius:11px; background:#0a1535; color:#fff; padding:0 13px; font:14px Inter,sans-serif; outline:none; }
      .omni-controls select { min-width:220px; }
      .omni-search { flex:1; min-width:190px; }
      .omni-input { width:100%; min-height:88px; padding:12px; resize:vertical; }
      .omni-controls select:focus, .omni-search:focus, .omni-input:focus { border-color:#00f0ff; box-shadow:0 0 0 3px rgba(0,240,255,.11); }
      .omni-tabs { display:flex; gap:8px; overflow-x:auto; margin-bottom:14px; padding-bottom:3px; }
      .omni-tab { flex:none; min-height:54px; padding:0 15px; border:1px solid rgba(255,255,255,.14); border-radius:11px; background:rgba(255,255,255,.06); color:rgba(255,255,255,.75); font:700 11px Orbitron,sans-serif; cursor:pointer; }
      .omni-tab.on { color:#fff; border-color:#ffe600; background:rgba(255,230,0,.16); }
      .omni-grid { display:grid; grid-template-columns:minmax(0,1.1fr) minmax(300px,.9fr); gap:14px; align-items:start; }
      .omni-card { min-width:0; padding:15px; margin-bottom:13px; border:1px solid rgba(0,240,255,.17); border-radius:15px; background:rgba(7,15,42,.86); box-shadow:0 9px 26px rgba(0,0,0,.18); }
      .omni-card h3 { margin:0 0 10px; color:#fff; font:700 14px Orbitron,sans-serif; letter-spacing:1px; }
      .omni-card p { color:rgba(255,255,255,.65); line-height:1.5; font-size:13px; }
      .omni-track { display:flex; align-items:center; gap:10px; padding:10px 0; border-bottom:1px solid rgba(255,255,255,.07); }
      .omni-track:last-child { border-bottom:0; }
      .omni-track-art { width:52px; height:52px; flex:none; display:grid; place-items:center; border-radius:13px; color:#03101c; background:linear-gradient(135deg,#00f0ff,#ffe600); font-size:24px; }
      .omni-track-info { flex:1; min-width:0; }
      .omni-track-info b { display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
      .omni-track-info span { display:block; margin-top:4px; color:rgba(255,255,255,.52); font-size:11px; }
      .omni-track .pt-btn { min-height:48px; }
      .omni-tool-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:9px; }
      .omni-tool { min-height:112px; padding:11px; border:1px solid rgba(255,255,255,.13); border-radius:13px; background:rgba(255,255,255,.045); color:#fff; text-align:left; cursor:pointer; transition:transform .16s ease,border-color .16s ease,background .16s ease; }
      .omni-tool:hover, .omni-tool:focus { transform:translateY(-2px); border-color:#00f0ff; background:rgba(0,240,255,.12); outline:none; }
      .omni-tool strong { display:block; color:#ffe600; font:700 11px Orbitron,sans-serif; letter-spacing:.5px; }
      .omni-tool span { display:block; margin-top:7px; color:rgba(255,255,255,.73); font-size:12px; line-height:1.35; }
      .omni-tool em { display:block; margin-top:7px; color:rgba(143,249,255,.72); font-size:10px; font-style:normal; }
      .omni-stat-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:8px; }
      .omni-stat { padding:10px; border-radius:10px; background:rgba(255,255,255,.055); color:rgba(255,255,255,.55); font-size:11px; }
      .omni-stat b { display:block; margin-top:4px; color:#fff; font:700 17px Orbitron,sans-serif; }
      .omni-output { width:100%; min-height:90px; box-sizing:border-box; padding:10px; resize:vertical; border:1px solid rgba(0,240,255,.24); border-radius:10px; background:rgba(0,0,0,.35); color:#8ff9ff; font:11px monospace; }
      .omni-badge { display:inline-block; padding:3px 7px; border-radius:8px; color:#8effd0; background:rgba(0,255,160,.12); border:1px solid rgba(0,255,160,.32); font:700 9px Orbitron,sans-serif; }

      /* ===== Zero The Legend tutorial academy ===== */
      .tutorial-shell { width:100%; max-width:1260px !important; margin:0 auto; color:#fff; }
      .tutorial-hero { position:relative; overflow:hidden; display:grid; grid-template-columns:minmax(0,1fr) 245px; gap:18px; align-items:center; padding:22px; margin-bottom:14px; border:1px solid rgba(255,230,0,.42); border-radius:18px; background:radial-gradient(circle at 82% 20%,rgba(255,230,0,.2),transparent 30%),radial-gradient(circle at 8% 20%,rgba(0,240,255,.2),transparent 34%),linear-gradient(135deg,rgba(8,18,52,.96),rgba(57,13,83,.9)); box-shadow:0 14px 38px rgba(0,0,0,.24); }
      .tutorial-hero h1 { margin:7px 0 8px; font:700 clamp(24px,4vw,40px) Orbitron,sans-serif; }
      .tutorial-hero p { max-width:720px; margin:0; color:rgba(255,255,255,.77); line-height:1.55; }
      .tutorial-kicker { color:#ffe600; font:700 10px Orbitron,sans-serif; letter-spacing:3px; }
      .tutorial-host { min-height:205px; border-radius:16px; border:1px solid rgba(0,240,255,.38); background:radial-gradient(circle at 50% 20%,rgba(0,240,255,.3),rgba(8,12,38,.6)); background-size:cover; background-position:center top; box-shadow:0 0 24px rgba(0,240,255,.18); }
      .tutorial-host-fallback { display:grid; place-items:center; min-height:205px; color:#8ff9ff; font:700 14px Orbitron,sans-serif; text-align:center; padding:15px; }
      .tutorial-controls { display:flex; gap:9px; flex-wrap:wrap; align-items:center; margin-top:16px; }
      .tutorial-controls select { min-height:52px; min-width:205px; padding:0 13px; border:1px solid rgba(0,240,255,.38); border-radius:11px; background:#0a1535; color:#fff; font:700 12px Inter,sans-serif; outline:none; }
      .tutorial-tabs { display:flex; gap:8px; overflow-x:auto; padding-bottom:3px; margin-bottom:14px; }
      .tutorial-tab { flex:none; min-height:52px; padding:0 15px; border:1px solid rgba(255,255,255,.14); border-radius:11px; background:rgba(255,255,255,.06); color:rgba(255,255,255,.75); font:700 11px Orbitron,sans-serif; cursor:pointer; }
      .tutorial-tab.on { color:#fff; border-color:#ffe600; background:rgba(255,230,0,.16); }
      .tutorial-grid { display:grid; grid-template-columns:minmax(0,1.15fr) minmax(280px,.85fr); gap:14px; align-items:start; }
      .tutorial-card { min-width:0; padding:15px; margin-bottom:13px; border:1px solid rgba(0,240,255,.18); border-radius:15px; background:rgba(7,15,42,.86); box-shadow:0 9px 26px rgba(0,0,0,.18); }
      .tutorial-card h3 { margin:0 0 9px; color:#fff; font:700 14px Orbitron,sans-serif; letter-spacing:1px; }
      .tutorial-card p { color:rgba(255,255,255,.68); font-size:13px; line-height:1.55; }
      .tutorial-video { position:relative; min-height:280px; overflow:hidden; display:flex; flex-direction:column; justify-content:flex-end; padding:18px; border-radius:14px; border:1px solid rgba(0,240,255,.3); background:radial-gradient(circle at 50% 28%,rgba(0,240,255,.24),rgba(8,12,38,.96) 70%); }
      .tutorial-video::before { content:'▶'; position:absolute; left:50%; top:42%; transform:translate(-50%,-50%); display:grid; place-items:center; width:74px; height:74px; border-radius:50%; color:#03101c; background:#fff; box-shadow:0 0 30px rgba(0,240,255,.75); font-size:27px; padding-left:4px; }
      .tutorial-video.playing::before { content:'❚❚'; font-size:20px; padding-left:0; }
      .tutorial-video-kicker { position:relative; z-index:1; color:#8ff9ff; font:700 10px Orbitron,sans-serif; letter-spacing:2px; }
      .tutorial-video h2 { position:relative; z-index:1; margin:6px 0; font:700 clamp(20px,3vw,28px) Orbitron,sans-serif; }
      .tutorial-video p { position:relative; z-index:1; margin:0; max-width:680px; color:rgba(255,255,255,.77); }
      .tutorial-progress { height:8px; margin-top:13px; overflow:hidden; border-radius:5px; background:rgba(255,255,255,.14); }
      .tutorial-progress span { display:block; height:100%; width:0; background:linear-gradient(90deg,#00f0ff,#ffe600,#ff2d95); transition:width .25s ease; }
      .tutorial-row { display:flex; align-items:center; gap:9px; flex-wrap:wrap; margin-top:11px; }
      .tutorial-meta { color:rgba(255,255,255,.55); font-size:12px; }
      .tutorial-list { display:grid; gap:8px; }
      .tutorial-lesson { display:flex; align-items:center; gap:10px; padding:10px; border-radius:11px; border:1px solid rgba(255,255,255,.1); background:rgba(255,255,255,.045); cursor:pointer; }
      .tutorial-lesson.on { border-color:#00f0ff; background:rgba(0,240,255,.12); }
      .tutorial-lesson.done { border-color:rgba(0,255,160,.45); }
      .tutorial-lesson-icon { width:42px; height:42px; flex:none; display:grid; place-items:center; border-radius:11px; background:linear-gradient(135deg,#00f0ff,#7b2cff); font-size:20px; }
      .tutorial-lesson-info { flex:1; min-width:0; }
      .tutorial-lesson-info b { display:block; font-size:13px; }
      .tutorial-lesson-info span { display:block; margin-top:3px; color:rgba(255,255,255,.55); font-size:11px; line-height:1.35; }
      .tutorial-check { color:#8effd0; font:700 13px Orbitron,sans-serif; }
      .tutorial-faq details { padding:11px 0; border-bottom:1px solid rgba(255,255,255,.09); }
      .tutorial-faq details:last-child { border-bottom:0; }
      .tutorial-faq summary { cursor:pointer; color:#8ff9ff; font-weight:700; font-size:13px; }
      .tutorial-faq p { margin:7px 0 0; }
      .tutorial-legal { color:rgba(255,255,255,.58); font-size:11px; line-height:1.55; }
      .tutorial-legal strong { color:#ffe600; }
      @media (max-width:899px) { .tutorial-hero { grid-template-columns:1fr; } .tutorial-host { min-height:170px; } .tutorial-grid { grid-template-columns:1fr; } }
      @media (max-width:520px) { .tutorial-hero { padding:17px; } .tutorial-host { min-height:145px; } .tutorial-video { min-height:260px; } }
      @media (prefers-reduced-motion: reduce) { .tutorial-progress span { transition:none; } }
      @media (max-width:899px) { .omni-grid { grid-template-columns:1fr; } .omni-controls select { width:100%; } }
      @media (max-width:520px) { .omni-tool-grid { grid-template-columns:1fr; } .omni-hero { padding:17px; } .omni-stat-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }

      /* ===== Social network shell: familiar feed layout for Zero Arcade ===== */
      #pt-search-form {
          flex: 1;
          min-width: 180px;
          max-width: 390px;
          margin-left: 8px;
      }
      #pt-global-search {
          width: 100%;
          min-height: 48px;
          box-sizing: border-box;
          padding: 0 16px;
          border-radius: 24px;
          border: 1px solid rgba(0, 240, 255, 0.28);
          background: rgba(255, 255, 255, 0.08);
          color: #fff;
          outline: none;
          font: 15px Inter, sans-serif;
      }
      #pt-global-search::placeholder { color: rgba(255, 255, 255, 0.55); }
      #pt-global-search:focus { border-color: #00f0ff; box-shadow: 0 0 0 3px rgba(0, 240, 255, 0.12); }
      .pt-notify-btn { position: relative; }
      #pt-notify-count {
          position: absolute;
          top: -5px;
          right: -5px;
          min-width: 22px;
          height: 22px;
          padding: 0 5px;
          box-sizing: border-box;
          border-radius: 11px;
          background: #ff2d6b;
          color: #fff;
          font: 700 11px/22px Inter, sans-serif;
          display: none;
      }
      #pt-notify-count.show { display: block; }

      .fb-layout {
          width: 100%;
          max-width: 1240px !important;
          display: grid;
          grid-template-columns: 220px minmax(0, 1fr) 270px;
          gap: 18px;
          align-items: start;
      }
      .fb-left-rail, .fb-feed, .fb-right-rail { min-width: 0; }
      .fb-left-rail, .fb-right-rail { position: sticky; top: 0; }
      .fb-profile-card, .fb-card, .fb-post, .fb-composer, .fb-stories {
          border-radius: 16px;
          background: rgba(9, 17, 43, 0.78);
          border: 1px solid rgba(0, 240, 255, 0.18);
          box-shadow: 0 10px 28px rgba(0, 0, 0, 0.16);
      }
      .fb-profile-card { padding: 16px; text-align: center; }
      .fb-avatar {
          width: 58px;
          height: 58px;
          margin: 0 auto 9px;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          color: #fff;
          font: 700 20px Orbitron, sans-serif;
          border: 2px solid rgba(0, 240, 255, 0.75);
          box-shadow: 0 0 18px rgba(0, 240, 255, 0.24);
      }
      .fb-avatar.tiny { width: 38px; height: 38px; margin: 0; font-size: 13px; }
      .fb-avatar.large { width: 72px; height: 72px; font-size: 24px; }
      .fb-profile-name { font: 700 17px Orbitron, sans-serif; color: #fff; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
      .fb-profile-meta { margin-top: 5px; color: rgba(255, 255, 255, 0.6); font-size: 13px; }
      .fb-profile-stats { display: grid; grid-template-columns: repeat(2, 1fr); gap: 6px; margin-top: 14px; }
      .fb-profile-stats div { padding: 8px 4px; border-radius: 9px; background: rgba(255, 255, 255, 0.05); color: rgba(255, 255, 255, 0.58); font-size: 11px; }
      .fb-profile-stats b { display: block; margin-top: 3px; color: #00f0ff; font: 700 15px Orbitron, sans-serif; }
      .fb-side-menu { display: flex; flex-direction: column; gap: 6px; margin-top: 12px; }
      .fb-side-menu button {
          width: 100%;
          min-height: 54px;
          padding: 0 13px;
          border: 1px solid transparent;
          border-radius: 12px;
          background: transparent;
          color: rgba(255, 255, 255, 0.75);
          text-align: left;
          font: 600 13px Inter, sans-serif;
          cursor: pointer;
      }
      .fb-side-menu button:hover, .fb-side-menu button:focus { background: rgba(0, 240, 255, 0.12); border-color: rgba(0, 240, 255, 0.25); color: #fff; }
      .fb-side-menu .fb-side-icon { display: inline-block; width: 27px; color: #00f0ff; font-size: 18px; vertical-align: middle; }
      .fb-section-title { margin: 0 0 10px; color: #00f0ff; font: 700 13px Orbitron, sans-serif; letter-spacing: 2px; }
      .fb-stories { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 8px; padding: 10px; margin-bottom: 14px; overflow: hidden; }
      .fb-story { min-height: 94px; padding: 9px 6px; border-radius: 11px; border: 1px solid rgba(0, 240, 255, 0.2); background: linear-gradient(160deg, rgba(0, 240, 255, 0.2), rgba(123, 44, 255, 0.18)); color: #fff; cursor: pointer; text-align: left; }
      .fb-story:first-child { background: linear-gradient(160deg, rgba(255, 230, 0, 0.25), rgba(255, 45, 107, 0.2)); }
      .fb-story .fb-avatar { width: 34px; height: 34px; margin: 0 0 12px; font-size: 11px; }
      .fb-story b { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 11px; }
      .fb-composer { padding: 14px; margin-bottom: 14px; }
      .fb-composer-top { display: flex; align-items: center; gap: 10px; }
      .fb-composer-top textarea { flex: 1; min-height: 58px; resize: vertical; box-sizing: border-box; padding: 12px; border-radius: 13px; border: 1px solid rgba(0, 240, 255, 0.25); background: rgba(0, 0, 0, 0.3); color: #fff; font: 15px Inter, sans-serif; outline: none; }
      .fb-composer-top textarea:focus { border-color: #00f0ff; }
      .fb-composer-bottom { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-top: 10px; }
      .fb-composer-hint { color: rgba(255, 255, 255, 0.5); font-size: 12px; }
      .fb-primary-btn, .fb-action-btn {
          min-height: 50px;
          padding: 0 16px;
          border-radius: 12px;
          border: 1px solid rgba(0, 240, 255, 0.45);
          background: rgba(0, 180, 255, 0.2);
          color: #fff;
          font: 700 13px Orbitron, sans-serif;
          cursor: pointer;
      }
      .fb-primary-btn { background: linear-gradient(180deg, rgba(0, 240, 255, 0.42), rgba(0, 100, 190, 0.34)); }
      .fb-primary-btn:active, .fb-action-btn:active { transform: scale(0.97); }
      .fb-feed-heading { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin: 0 0 10px; }
      .fb-feed-heading span { color: rgba(255, 255, 255, 0.55); font-size: 12px; }
      .fb-post { padding: 14px; margin-bottom: 12px; }
      .fb-post-head { display: flex; align-items: center; gap: 10px; }
      .fb-post-head-info { flex: 1; min-width: 0; }
      .fb-post-author { color: #fff; font-weight: 700; font-size: 14px; }
      .fb-post-time { display: block; margin-top: 3px; color: rgba(255, 255, 255, 0.5); font-size: 11px; }
      .fb-post-badge { display: inline-block; margin-left: 5px; padding: 2px 6px; border-radius: 7px; color: #ffe600; background: rgba(255, 230, 0, 0.14); font: 700 9px Orbitron, sans-serif; }
      .fb-post-text { margin: 14px 0; color: rgba(255, 255, 255, 0.88); font: 15px/1.55 Inter, sans-serif; white-space: pre-wrap; overflow-wrap: anywhere; }
      .fb-post-stats { display: flex; justify-content: space-between; gap: 8px; padding: 8px 2px; border-top: 1px solid rgba(255, 255, 255, 0.08); color: rgba(255, 255, 255, 0.55); font-size: 12px; }
      .fb-post-actions { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; }
      .fb-action-btn { min-height: 46px; padding: 0 6px; background: rgba(255, 255, 255, 0.04); border-color: transparent; font-family: Inter, sans-serif; font-size: 12px; }
      .fb-action-btn.active { color: #00f0ff; background: rgba(0, 240, 255, 0.12); border-color: rgba(0, 240, 255, 0.3); }
      .fb-comments { margin-top: 10px; }
      .fb-comment { display: flex; gap: 7px; margin-top: 7px; color: rgba(255, 255, 255, 0.72); font-size: 12px; line-height: 1.4; }
      .fb-comment b { color: #8ff9ff; }
      .fb-comment-form { display: flex; gap: 6px; margin-top: 10px; }
      .fb-comment-form input { flex: 1; min-width: 0; min-height: 44px; box-sizing: border-box; padding: 0 11px; border: 1px solid rgba(0, 240, 255, 0.2); border-radius: 10px; background: rgba(0, 0, 0, 0.28); color: #fff; outline: none; }
      .fb-comment-form button { min-width: 50px; min-height: 44px; border: 1px solid rgba(0, 240, 255, 0.3); border-radius: 10px; background: rgba(0, 180, 255, 0.2); color: #fff; cursor: pointer; }
      .fb-card { padding: 14px; margin-bottom: 12px; }
      .fb-card h3 { margin: 0 0 11px; color: #fff; font: 700 14px Orbitron, sans-serif; }
      .fb-contact, .fb-suggestion { display: flex; align-items: center; gap: 9px; padding: 8px 0; border-bottom: 1px solid rgba(255, 255, 255, 0.06); }
      .fb-contact:last-child, .fb-suggestion:last-child { border-bottom: 0; }
      .fb-contact-info, .fb-suggestion-info { flex: 1; min-width: 0; }
      .fb-contact-info b, .fb-suggestion-info b { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: rgba(255, 255, 255, 0.88); font-size: 13px; }
      .fb-contact-info span, .fb-suggestion-info span { display: block; margin-top: 3px; color: rgba(255, 255, 255, 0.5); font-size: 11px; }
      .fb-online { width: 9px; height: 9px; border-radius: 50%; background: #00ffa2; box-shadow: 0 0 8px #00ffa2; }
      .fb-offline { background: #59647c; box-shadow: none; }
      .fb-mini-btn { min-height: 42px; padding: 0 9px; border-radius: 9px; border: 1px solid rgba(0, 240, 255, 0.25); background: rgba(0, 240, 255, 0.1); color: #8ff9ff; font-size: 11px; cursor: pointer; }
      .fb-trend { display: flex; justify-content: space-between; gap: 8px; padding: 8px 0; color: rgba(255, 255, 255, 0.72); font-size: 12px; }
      .fb-trend b { color: #ffe600; }
      .fb-empty { padding: 24px 12px; text-align: center; color: rgba(255, 255, 255, 0.55); }

      @media (max-width: 1120px) {
          .fb-layout { grid-template-columns: 190px minmax(0, 1fr); }
          .fb-right-rail { display: none; }
      }
      @media (max-width: 899px) {
          #pt-search-form { max-width: none; }
          .fb-layout { grid-template-columns: 1fr; gap: 12px; }
          .fb-left-rail { position: static; }
          .fb-left-rail .fb-profile-card { display: flex; align-items: center; gap: 10px; text-align: left; }
          .fb-left-rail .fb-profile-card .fb-avatar { margin: 0; }
          .fb-left-rail .fb-profile-stats { margin: 0 0 0 auto; min-width: 135px; }
          .fb-side-menu { display: grid; grid-template-columns: repeat(4, 1fr); margin-top: 0; }
          .fb-side-menu button { text-align: center; padding: 0 4px; min-height: 58px; }
          .fb-side-menu .fb-side-icon { display: block; width: auto; margin-bottom: 3px; }
      }
      @media (max-width: 520px) {
          #pt-search-form { display: none; }
          #pt-top { flex-wrap: nowrap; }
          .fb-left-rail .fb-profile-card { align-items: flex-start; }
          .fb-left-rail .fb-profile-stats { display: none; }
          .fb-side-menu { grid-template-columns: repeat(2, 1fr); }
          .fb-stories { grid-template-columns: repeat(2, 1fr); }
          .fb-composer-top { align-items: flex-start; }
          .fb-composer-top .fb-avatar { flex: none; }
          .fb-composer-bottom { align-items: flex-end; flex-direction: column; }
          .fb-primary-btn { width: 100%; }
          .fb-post-actions { grid-template-columns: 1fr; }
      }

      /* ===== ZeroSocial NEXUS: a new social primitive, not a clone ===== */
      .nexus-wrap { width:100%; max-width:1320px !important; color:#fff; }
      .nexus-hero { position:relative; overflow:hidden; padding:24px; margin-bottom:15px; border:1px solid rgba(255,230,0,.4); border-radius:20px; background:radial-gradient(circle at 12% 12%,rgba(255,230,0,.22),transparent 28%),radial-gradient(circle at 84% 20%,rgba(0,240,255,.2),transparent 30%),linear-gradient(135deg,rgba(20,20,65,.96),rgba(53,15,83,.9)); box-shadow:0 16px 44px rgba(0,0,0,.24),inset 0 0 44px rgba(255,230,0,.06); }
      .nexus-hero::before { content:'NEXUS'; position:absolute; right:-14px; bottom:-25px; color:rgba(255,255,255,.035); font:700 112px/1 Orbitron,sans-serif; letter-spacing:8px; pointer-events:none; }
      .nexus-kicker { color:#ffe600; font:700 10px Orbitron,sans-serif; letter-spacing:3px; }
      .nexus-hero h2 { max-width:760px; margin:8px 0; font:700 clamp(25px,5vw,42px) Orbitron,sans-serif; line-height:1.08; }
      .nexus-hero p { max-width:760px; margin:0; color:rgba(255,255,255,.76); line-height:1.55; }
      .nexus-controls { display:flex; gap:9px; flex-wrap:wrap; align-items:center; margin-top:17px; }
      .nexus-controls select { min-height:52px; min-width:190px; padding:0 13px; border:1px solid rgba(255,230,0,.42); border-radius:11px; background:#171535; color:#fff; font:700 12px Inter,sans-serif; outline:none; }
      .nexus-grid { display:grid; grid-template-columns:minmax(0,1.35fr) minmax(280px,.65fr); gap:15px; align-items:start; }
      .nexus-card { min-width:0; padding:15px; border:1px solid rgba(0,240,255,.2); border-radius:17px; background:rgba(7,15,42,.84); box-shadow:0 10px 30px rgba(0,0,0,.2); }
      .nexus-card h3 { margin:0 0 10px; color:#fff; font:700 14px Orbitron,sans-serif; letter-spacing:1px; }
      .nexus-map { position:relative; min-height:480px; overflow:hidden; border:1px solid rgba(0,240,255,.26); border-radius:18px; background:radial-gradient(circle at 50% 50%,rgba(0,240,255,.16),transparent 17%),radial-gradient(circle at 50% 50%,rgba(123,44,255,.12),transparent 43%),#050a24; }
      .nexus-map::before,.nexus-map::after { content:''; position:absolute; left:50%; top:50%; border:1px solid rgba(0,240,255,.22); border-radius:50%; transform:translate(-50%,-50%) rotate(-18deg); pointer-events:none; }
      .nexus-map::before { width:72%; height:45%; box-shadow:0 0 35px rgba(0,240,255,.08); }
      .nexus-map::after { width:49%; height:68%; border-color:rgba(255,230,0,.2); transform:translate(-50%,-50%) rotate(30deg); }
      .nexus-map-grid { position:absolute; inset:0; opacity:.25; background-image:linear-gradient(rgba(0,240,255,.1) 1px,transparent 1px),linear-gradient(90deg,rgba(0,240,255,.1) 1px,transparent 1px); background-size:42px 42px; mask-image:radial-gradient(circle,#000 15%,transparent 76%); pointer-events:none; }
      .nexus-core { position:absolute; z-index:3; left:50%; top:50%; width:118px; height:118px; display:grid; place-items:center; box-sizing:border-box; padding:20px; border:2px solid #ffe600; border-radius:50%; transform:translate(-50%,-50%); text-align:center; color:#fff; font:700 11px/1.35 Orbitron,sans-serif; background:radial-gradient(circle at 35% 25%,#fff,#ffe600 17%,#ff7a18 52%,#7b2cff 82%); box-shadow:0 0 24px rgba(255,230,0,.75),0 0 80px rgba(123,44,255,.4); animation:nexusCorePulse 3s ease-in-out infinite; }
      .nexus-core small { display:block; margin-top:4px; color:#180d30; font:700 9px Inter,sans-serif; letter-spacing:1px; }
      .nexus-node { position:absolute; z-index:4; left:calc(50% + var(--nx-x)); top:calc(50% + var(--nx-y)); width:142px; min-height:92px; box-sizing:border-box; padding:10px; border:1px solid var(--nx-c); border-radius:14px; color:#fff; text-align:left; cursor:pointer; transform:translate(-50%,-50%); background:linear-gradient(145deg,rgba(17,28,69,.96),rgba(7,10,29,.94)); box-shadow:0 0 20px color-mix(in srgb,var(--nx-c) 22%,transparent); transition:transform .18s ease,box-shadow .18s ease,border-color .18s ease; }
      .nexus-node:hover,.nexus-node:focus { transform:translate(-50%,-50%) scale(1.05); box-shadow:0 0 28px color-mix(in srgb,var(--nx-c) 48%,transparent); border-color:#fff; outline:none; }
      .nexus-node.resonated { background:linear-gradient(145deg,color-mix(in srgb,var(--nx-c) 25%,#101636),rgba(7,10,29,.96)); }
      .nexus-node b { display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font:700 11px Orbitron,sans-serif; }
      .nexus-node span { display:block; margin-top:6px; color:rgba(255,255,255,.72); font:12px/1.35 Inter,sans-serif; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
      .nexus-node em { display:block; margin-top:7px; color:var(--nx-c); font:700 9px Orbitron,sans-serif; font-style:normal; letter-spacing:1px; }
      .nexus-side-stack { display:flex; flex-direction:column; gap:15px; }
      .nexus-pulse-form textarea { width:100%; min-height:92px; box-sizing:border-box; resize:vertical; padding:12px; border:1px solid rgba(255,230,0,.28); border-radius:11px; background:rgba(0,0,0,.3); color:#fff; font:14px/1.45 Inter,sans-serif; outline:none; }
      .nexus-pulse-form textarea:focus { border-color:#ffe600; box-shadow:0 0 0 3px rgba(255,230,0,.1); }
      .nexus-pulse-form .pt-btn { width:100%; margin-top:9px; }
      .nexus-stats { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:8px; }
      .nexus-stat { padding:10px; border-radius:11px; background:rgba(255,255,255,.055); color:rgba(255,255,255,.58); font-size:11px; }
      .nexus-stat b { display:block; margin-top:4px; color:#ffe600; font:700 19px Orbitron,sans-serif; }
      .nexus-rules { margin:0; padding-left:18px; color:rgba(255,255,255,.68); font-size:12px; line-height:1.65; }
      .nexus-rules strong { color:#8ff9ff; }
      .nexus-created { margin-top:10px; padding-top:10px; border-top:1px solid rgba(255,255,255,.09); }
      .nexus-created-item { padding:8px 0; border-bottom:1px solid rgba(255,255,255,.07); color:rgba(255,255,255,.8); font-size:12px; }
      .nexus-created-item:last-child { border-bottom:0; }
      .nexus-created-item b { color:#ffe600; }
      @keyframes nexusCorePulse { 0%,100% { box-shadow:0 0 24px rgba(255,230,0,.65),0 0 68px rgba(123,44,255,.32); } 50% { box-shadow:0 0 38px rgba(255,230,0,.92),0 0 96px rgba(123,44,255,.52); } }
      @media (max-width:899px) { .nexus-grid { grid-template-columns:1fr; } .nexus-map { min-height:500px; } }
      @media (max-width:520px) { .nexus-hero { padding:18px; } .nexus-hero::before { font-size:70px; } .nexus-map { min-height:455px; } .nexus-node { width:116px; min-height:82px; padding:8px; } .nexus-node span { font-size:11px; } .nexus-core { width:94px; height:94px; padding:14px; font-size:10px; } .nexus-controls select { width:100%; } }
      /* ===== ZeroSocial Hub: one touch-first shell for feed, stories, video, chat and pro tools ===== */
      .zs-shell { width:100%; max-width:1320px !important; margin:0 auto; color:#fff; }
      .zs-head { display:flex; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:14px; }
      .zs-head h1 { margin:0; font:700 clamp(22px,4vw,34px) Orbitron,sans-serif; letter-spacing:.4px; }
      .zs-head p { margin:4px 0 0; color:rgba(255,255,255,.58); font-size:13px; }
      .zs-head-copy { flex:1; min-width:220px; }
      .zs-select { min-height:52px; padding:0 38px 0 14px; border:1px solid rgba(0,240,255,.38); border-radius:11px; background:#0b1636; color:#fff; font:700 12px Inter,sans-serif; outline:none; }
      .zs-select:focus { border-color:#00f0ff; box-shadow:0 0 0 3px rgba(0,240,255,.12); }
      .zs-toolbar { display:flex; gap:8px; flex-wrap:wrap; align-items:center; margin-bottom:14px; }
      .zs-service-select { min-width:190px; }
      .zs-tabs { display:flex; gap:7px; overflow-x:auto; flex:1; padding-bottom:2px; }
      .zs-tab { min-height:48px; white-space:nowrap; padding:0 14px; border:1px solid rgba(255,255,255,.14); border-radius:10px; background:rgba(255,255,255,.06); color:rgba(255,255,255,.72); font:700 11px Orbitron,sans-serif; cursor:pointer; }
      .zs-tab.on { border-color:#00f0ff; background:rgba(0,240,255,.18); color:#fff; }
      .zs-hero { position:relative; overflow:hidden; padding:22px; border:1px solid rgba(0,240,255,.32); border-radius:17px; background:radial-gradient(circle at 8% 10%,rgba(0,240,255,.26),transparent 36%),linear-gradient(135deg,rgba(9,21,57,.94),rgba(49,13,84,.78)); margin-bottom:14px; }
      .zs-hero::after { content:'◈  ◇  ◈'; position:absolute; right:18px; top:16px; color:rgba(255,230,0,.75); letter-spacing:10px; }
      .zs-hero h2 { margin:6px 0 8px; font:700 clamp(24px,5vw,38px) Orbitron,sans-serif; }
      .zs-hero p { max-width:720px; margin:0; color:rgba(255,255,255,.76); line-height:1.5; }
      .zs-kicker { color:#8ff9ff; font:700 10px Orbitron,sans-serif; letter-spacing:2px; }
      .zs-metrics { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:9px; margin-bottom:15px; }
      .zs-metric,.zs-card { border:1px solid rgba(0,240,255,.16); border-radius:14px; background:rgba(9,17,43,.82); box-shadow:0 8px 24px rgba(0,0,0,.18); }
      .zs-metric { padding:11px 13px; color:rgba(255,255,255,.58); font-size:12px; }
      .zs-metric b { display:block; margin-top:4px; color:#fff; font:700 18px Orbitron,sans-serif; }
      .zs-columns { display:grid; grid-template-columns:minmax(0,1fr) 286px; gap:16px; align-items:start; }
      .zs-card { padding:14px; margin-bottom:13px; }
      .zs-card h3 { margin:0 0 11px; color:#fff; font:700 14px Orbitron,sans-serif; }
      .zs-section { margin:18px 0 10px; color:#00f0ff; font:700 12px Orbitron,sans-serif; letter-spacing:2px; }
      .zs-quick-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:8px; margin-bottom:14px; }
      .zs-quick { min-height:78px; border:1px solid rgba(255,255,255,.14); border-radius:12px; background:rgba(255,255,255,.055); color:#fff; font:700 11px Orbitron,sans-serif; cursor:pointer; }
      .zs-quick b { display:block; margin-bottom:5px; font-size:22px; }
      .zs-story-row { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:8px; overflow-x:auto; }
      .zs-story { min-height:116px; padding:10px; border:1px solid rgba(0,240,255,.2); border-radius:12px; color:#fff; text-align:left; cursor:pointer; background:linear-gradient(150deg,rgba(0,240,255,.22),rgba(123,44,255,.23)); }
      .zs-story.seen { opacity:.58; }
      .zs-story b { display:block; margin-top:18px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:12px; }
      .zs-story span { display:block; margin-top:3px; color:rgba(255,255,255,.6); font-size:11px; }
      .zs-composer textarea { width:100%; min-height:74px; box-sizing:border-box; resize:vertical; padding:12px; border:1px solid rgba(0,240,255,.25); border-radius:11px; background:rgba(0,0,0,.28); color:#fff; font:14px/1.45 Inter,sans-serif; outline:none; }
      .zs-composer textarea:focus { border-color:#00f0ff; }
      .zs-form-row { display:flex; gap:8px; align-items:center; flex-wrap:wrap; margin-top:9px; }
      .zs-form-row .pt-btn { flex:0 0 auto; }
      .zs-muted { color:rgba(255,255,255,.55); font-size:12px; line-height:1.5; }
      .zs-list-row { display:flex; align-items:center; gap:10px; padding:10px 0; border-bottom:1px solid rgba(255,255,255,.07); }
      .zs-list-row:last-child { border-bottom:0; }
      .zs-list-info { flex:1; min-width:0; }
      .zs-list-info b { display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
      .zs-list-info span { display:block; margin-top:3px; color:rgba(255,255,255,.52); font-size:11px; }
      .zs-avatar { width:42px; height:42px; flex:none; border-radius:50%; display:grid; place-items:center; border:2px solid rgba(0,240,255,.6); font:700 15px Orbitron,sans-serif; }
      .zs-pill { display:inline-block; padding:3px 8px; border-radius:9px; border:1px solid rgba(0,240,255,.35); color:#8ff9ff; background:rgba(0,240,255,.1); font:700 10px Orbitron,sans-serif; }
      .zs-video-grid,.zs-reel-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; }
      .zs-video,.zs-reel { overflow:hidden; border:1px solid rgba(255,255,255,.12); border-radius:13px; background:rgba(255,255,255,.045); }
      .zs-thumb { min-height:132px; display:grid; place-items:center; position:relative; background:radial-gradient(circle at 35% 28%,rgba(0,240,255,.55),rgba(39,20,105,.85) 55%,#050817); }
      .zs-thumb::after { content:'▶'; display:grid; place-items:center; width:54px; height:54px; border-radius:50%; color:#03101c; background:#fff; box-shadow:0 0 25px rgba(0,240,255,.65); }
      .zs-video-body,.zs-reel-body { padding:11px; }
      .zs-video-body b,.zs-reel-body b { display:block; font-size:14px; }
      .zs-video-body p,.zs-reel-body p { margin:5px 0; color:rgba(255,255,255,.66); font-size:12px; line-height:1.4; }
      .zs-reel .zs-thumb { min-height:220px; background:linear-gradient(160deg,rgba(255,45,107,.5),rgba(123,44,255,.8) 52%,#050817); }
      .zs-reel .zs-thumb::after { content:'▶  SHORT'; width:auto; height:auto; padding:10px 14px; border-radius:20px; font:700 11px Orbitron,sans-serif; }
      .zs-chat-layout { display:grid; grid-template-columns:210px minmax(0,1fr); min-height:380px; overflow:hidden; border:1px solid rgba(0,240,255,.16); border-radius:14px; background:rgba(9,17,43,.75); }
      .zs-chat-list { border-right:1px solid rgba(255,255,255,.1); padding:8px; }
      .zs-chat-peer { width:100%; min-height:58px; padding:8px; border:0; border-radius:9px; background:transparent; color:#fff; text-align:left; cursor:pointer; }
      .zs-chat-peer.on,.zs-chat-peer:hover { background:rgba(0,240,255,.14); }
      .zs-chat-window { display:flex; flex-direction:column; min-width:0; }
      .zs-chat-head { padding:13px; border-bottom:1px solid rgba(255,255,255,.1); font:700 14px Orbitron,sans-serif; }
      .zs-chat-log { flex:1; min-height:230px; max-height:320px; overflow:auto; padding:13px; }
      .zs-bubble { max-width:78%; margin:0 0 9px; padding:9px 11px; border-radius:12px; background:rgba(255,255,255,.08); color:rgba(255,255,255,.84); font-size:13px; line-height:1.4; }
      .zs-bubble.me { margin-left:auto; background:rgba(0,180,255,.22); }
      .zs-chat-form { display:flex; gap:7px; padding:9px; border-top:1px solid rgba(255,255,255,.1); }
      .zs-chat-form input,.zs-input { flex:1; min-width:0; min-height:46px; box-sizing:border-box; padding:0 11px; border:1px solid rgba(0,240,255,.25); border-radius:9px; background:rgba(0,0,0,.3); color:#fff; outline:none; }
      .zs-job { padding:12px 0; border-bottom:1px solid rgba(255,255,255,.08); }
      .zs-job:last-child { border-bottom:0; }
      .zs-job h4 { margin:0; font:700 14px Orbitron,sans-serif; }
      .zs-job p { margin:4px 0 8px; color:rgba(255,255,255,.6); font-size:12px; }
      .zs-xpost { padding:13px 0; border-bottom:1px solid rgba(255,255,255,.09); }
      .zs-xpost:last-child { border-bottom:0; }
      .zs-xpost-head { display:flex; gap:9px; align-items:center; }
      .zs-xpost p { margin:10px 0; color:rgba(255,255,255,.87); line-height:1.45; font-size:14px; white-space:pre-wrap; }
      .zs-action-row { display:flex; gap:7px; flex-wrap:wrap; }
      .zs-action { min-height:40px; padding:0 11px; border:1px solid transparent; border-radius:9px; background:rgba(255,255,255,.05); color:rgba(255,255,255,.75); cursor:pointer; font-size:12px; }
      .zs-action:hover { border-color:rgba(0,240,255,.3); color:#fff; }
      .zs-empty { padding:25px 10px; text-align:center; color:rgba(255,255,255,.55); }
      @media (max-width:899px) { .zs-columns { grid-template-columns:1fr; } .zs-metrics { grid-template-columns:repeat(2,minmax(0,1fr)); } .zs-quick-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .zs-chat-layout { grid-template-columns:1fr; } .zs-chat-list { display:flex; gap:6px; overflow-x:auto; border-right:0; border-bottom:1px solid rgba(255,255,255,.1); } .zs-chat-peer { min-width:150px; } }
      @media (max-width:520px) { .zs-head { align-items:stretch; } .zs-head-copy { min-width:100%; } .zs-select,.zs-service-select { width:100%; } .zs-story-row { grid-template-columns:repeat(2,minmax(132px,1fr)); } .zs-video-grid,.zs-reel-grid { grid-template-columns:1fr; } .zs-hero { padding:17px; } }

      /* ===== YouTube-style portal shell + dropdown navigation ===== */
      #pt-nav {
          display: block;
          padding: 18px 14px;
          background: rgba(2, 5, 18, 0.86);
      }
      .pt-nav-label {
          display: block;
          margin: 0 8px 8px;
          color: rgba(255,255,255,0.5);
          font: 700 10px Orbitron, sans-serif;
          letter-spacing: 2px;
      }
      .pt-page-select, .pt-category-select {
          width: 100%;
          min-height: 58px;
          box-sizing: border-box;
          padding: 0 42px 0 16px;
          border: 1px solid rgba(0,240,255,0.35);
          border-radius: 10px;
          color: #fff;
          background-color: rgba(12, 20, 48, 0.96);
          font: 700 13px Inter, sans-serif;
          letter-spacing: 0.4px;
          outline: none;
          cursor: pointer;
      }
      .pt-page-select:focus, .pt-category-select:focus {
          border-color: #00f0ff;
          box-shadow: 0 0 0 3px rgba(0,240,255,0.12);
      }
      .pt-page-select option, .pt-category-select option { color: #fff; background: #08112c; }
      .pt-menu-btn { display: inline-flex; align-items: center; justify-content: center; }
      #portal.yt-nav-collapsed { grid-template-columns: 0 minmax(0, 1fr); }
      #portal.yt-nav-collapsed #pt-nav { display: none; }

      /* YouTube-inspired channel shell: sidebar, Shorts shelf, video feed, trends. */
      .yt-layout {
          width: 100%;
          max-width: 1260px !important;
          display: grid;
          grid-template-columns: 210px minmax(0, 1fr) 282px;
          gap: 20px;
          align-items: start;
      }
      .yt-sidebar, .yt-main, .yt-trending { min-width: 0; }
      .yt-sidebar { position: sticky; top: 0; }
      .yt-channel-card, .yt-trending .fb-card, .yt-upload-box, .yt-video-card {
          border-radius: 12px;
          background: rgba(9,17,43,0.82);
          border: 1px solid rgba(0,240,255,0.16);
          box-shadow: 0 8px 24px rgba(0,0,0,0.2);
      }
      .yt-channel-card { padding: 16px 12px; text-align: center; }
      .yt-sidebar .fb-side-menu { margin-top: 12px; gap: 3px; }
      .yt-sidebar .fb-side-menu button {
          min-height: 50px;
          border: 0;
          border-radius: 8px;
          font-size: 13px;
      }
      .yt-sidebar .fb-side-menu button:first-child { color: #fff; background: rgba(0,240,255,0.16); }
      .yt-sidebar .fb-side-menu button:hover, .yt-sidebar .fb-side-menu button:focus { background: rgba(255,255,255,0.09); border-color: transparent; }
      .yt-section-heading { padding: 0 2px 12px; border-bottom: 1px solid rgba(255,255,255,0.1); }
      .yt-section-heading h2 { font-size: 24px !important; letter-spacing: -0.5px; }
      .yt-shorts-shelf {
          display: grid;
          grid-template-columns: repeat(4, minmax(0,1fr));
          gap: 10px;
          padding: 12px 0 18px;
          margin-bottom: 14px;
          border-bottom: 1px solid rgba(255,255,255,0.1);
      }
      .yt-short-card {
          position: relative;
          min-height: 138px;
          padding: 10px 8px;
          border-radius: 10px;
          border: 1px solid rgba(0,240,255,0.2);
          overflow: hidden;
          background: linear-gradient(160deg, rgba(0,240,255,0.2), rgba(123,44,255,0.2));
      }
      .yt-short-card::after {
          content: 'SHORT';
          position: absolute;
          top: 8px;
          right: 7px;
          padding: 2px 5px;
          border-radius: 4px;
          color: #fff;
          background: rgba(255,45,107,0.78);
          font: 700 8px Inter, sans-serif;
      }
      .yt-short-card .fb-avatar { margin: 0 0 30px; }
      .yt-upload-box { padding: 13px; margin-bottom: 16px; }
      .yt-video-card { position: relative; padding: 12px; margin-bottom: 14px; overflow: hidden; }
      .yt-video-thumb {
          position: relative;
          display: flex;
          align-items: center;
          justify-content: center;
          min-height: 150px;
          margin: -12px -12px 12px;
          background: radial-gradient(circle at 35% 28%, rgba(0,240,255,0.55), rgba(39,20,105,0.8) 52%, #050817 100%);
          border-bottom: 1px solid rgba(0,240,255,0.2);
          overflow: hidden;
      }
      .yt-video-thumb::before, .yt-video-thumb::after { content: ''; position: absolute; border: 1px solid rgba(255,255,255,0.18); border-radius: 50%; transform: rotate(-18deg); }
      .yt-video-thumb::before { width: 240px; height: 82px; }
      .yt-video-thumb::after { width: 130px; height: 44px; border-color: rgba(255,230,0,0.3); }
      .yt-play {
          position: relative;
          z-index: 1;
          display: grid;
          place-items: center;
          width: 62px;
          height: 62px;
          border-radius: 50%;
          color: #03101c;
          background: #fff;
          box-shadow: 0 0 28px rgba(0,240,255,0.7);
          font-size: 23px;
          padding-left: 3px;
      }
      .yt-video-thumb small { position: absolute; left: 12px; bottom: 9px; color: rgba(255,255,255,0.7); font: 700 10px Orbitron, sans-serif; letter-spacing: 1.5px; }
      .yt-video-card .fb-post-actions { border-top: 1px solid rgba(255,255,255,0.08); padding-top: 8px; }
      .yt-video-card .fb-action-btn { border-radius: 8px; }
      .yt-trending .fb-card { padding: 14px; margin-bottom: 12px; }
      .yt-trending .fb-card h3 { color: #fff; font-size: 13px; }
      .yt-trending .fb-mini-btn { min-height: 38px; }

      @media (max-width: 1120px) {
          .yt-layout { grid-template-columns: 190px minmax(0,1fr); }
          .yt-trending { display: none; }
      }
      @media (max-width: 899px) {
          #pt-nav { padding: 9px 10px; }
          .pt-nav-label { display: none; }
          .pt-page-select { min-height: 54px; }
          #portal.yt-nav-collapsed { grid-template-columns: 1fr; }
          #portal.yt-nav-collapsed #pt-nav { display: block; }
          .yt-layout { grid-template-columns: 1fr; gap: 12px; }
          .yt-sidebar { position: static; }
          .yt-sidebar .fb-profile-card { display: flex; align-items: center; gap: 10px; text-align: left; }
          .yt-sidebar .fb-profile-card .fb-avatar { margin: 0; }
          .yt-sidebar .fb-profile-stats { margin: 0 0 0 auto; min-width: 135px; }
          .yt-sidebar .fb-side-menu { display: grid; grid-template-columns: repeat(4,1fr); }
          .yt-sidebar .fb-side-menu button { text-align: center; padding: 0 4px; min-height: 58px; }
          .yt-sidebar .fb-side-menu .fb-side-icon { display: block; width: auto; margin-bottom: 3px; }
          .yt-shorts-shelf { grid-template-columns: repeat(4, minmax(90px,1fr)); overflow-x: auto; }
      }
      @media (max-width: 520px) {
          #pt-menu-toggle { display: none; }
          .yt-sidebar .fb-profile-card { align-items: flex-start; }
          .yt-sidebar .fb-profile-stats { display: none; }
          .yt-sidebar .fb-side-menu { grid-template-columns: repeat(2,1fr); }
          .yt-shorts-shelf { grid-template-columns: repeat(2, minmax(120px,1fr)); }
          .yt-video-thumb { min-height: 128px; }
      }
      /* ===== ZeroSocial CORE: one original portal structure, not seven cloned pages ===== */
      .zsc-shell { width:100%; max-width:1380px !important; margin:0 auto; color:#fff; }
      .zsc-topbar { display:flex; align-items:center; gap:14px; padding:16px 18px; margin-bottom:14px; border:1px solid rgba(0,240,255,.25); border-radius:18px; background:linear-gradient(120deg,rgba(8,21,53,.94),rgba(42,12,74,.84)); box-shadow:0 14px 36px rgba(0,0,0,.22), inset 0 0 28px rgba(0,240,255,.06); }
      .zsc-brand { display:flex; align-items:center; gap:10px; min-width:220px; }
      .zsc-brand-mark { width:42px; height:42px; display:grid; place-items:center; border:1px solid #ffe600; border-radius:13px; color:#ffe600; background:radial-gradient(circle,#fff 0 8%,#ffe600 22%,#7b2cff 72%); box-shadow:0 0 22px rgba(255,230,0,.38); font:700 19px Orbitron,sans-serif; }
      .zsc-brand b { display:block; font:700 17px Orbitron,sans-serif; letter-spacing:1px; }
      .zsc-brand span { display:block; margin-top:3px; color:rgba(255,255,255,.56); font-size:11px; }
      .zsc-topline { flex:1; min-width:180px; }
      .zsc-topline strong { display:block; color:#8ff9ff; font:700 11px Orbitron,sans-serif; letter-spacing:2px; }
      .zsc-topline p { margin:4px 0 0; color:rgba(255,255,255,.68); font-size:13px; }
      .zsc-topbar .zs-select { min-width:210px; }
      .zsc-layout { display:grid; grid-template-columns:196px minmax(0,1fr) 248px; gap:14px; align-items:start; }
      .zsc-command-rail, .zsc-live-rail { min-width:0; }
      .zsc-command-rail { position:sticky; top:0; padding:12px; border:1px solid rgba(0,240,255,.16); border-radius:16px; background:rgba(6,13,36,.78); }
      .zsc-rail-title { margin:2px 5px 10px; color:rgba(255,255,255,.48); font:700 10px Orbitron,sans-serif; letter-spacing:2px; }
      .zsc-rail-btn { width:100%; min-height:54px; display:flex; align-items:center; gap:9px; padding:0 11px; margin-bottom:6px; border:1px solid transparent; border-radius:11px; background:transparent; color:rgba(255,255,255,.72); text-align:left; font:700 11px Inter,sans-serif; cursor:pointer; }
      .zsc-rail-btn span { width:25px; color:#8ff9ff; font-size:18px; text-align:center; }
      .zsc-rail-btn small { display:block; margin-top:2px; color:rgba(255,255,255,.42); font-size:10px; font-weight:400; }
      .zsc-rail-btn:hover, .zsc-rail-btn:focus, .zsc-rail-btn.on { border-color:rgba(0,240,255,.35); background:linear-gradient(90deg,rgba(0,240,255,.18),rgba(123,44,255,.12)); color:#fff; outline:none; }
      .zsc-main { min-width:0; }
      .zsc-welcome { position:relative; overflow:hidden; padding:20px 22px; margin-bottom:13px; border:1px solid rgba(255,230,0,.34); border-radius:17px; background:radial-gradient(circle at 90% 10%,rgba(255,230,0,.2),transparent 28%),radial-gradient(circle at 12% 20%,rgba(0,240,255,.2),transparent 34%),rgba(10,17,48,.9); }
      .zsc-welcome::after { content:'ONE SOCIAL / MANY SIGNALS'; position:absolute; right:18px; bottom:13px; color:rgba(255,255,255,.09); font:700 16px Orbitron,sans-serif; letter-spacing:2px; transform:rotate(-8deg); pointer-events:none; }
      .zsc-welcome h1 { position:relative; z-index:1; margin:0; font:700 clamp(22px,4vw,34px) Orbitron,sans-serif; }
      .zsc-welcome p { position:relative; z-index:1; max-width:700px; margin:7px 0 0; color:rgba(255,255,255,.74); line-height:1.5; }
      .zsc-command-line { display:flex; gap:8px; flex-wrap:wrap; margin-top:15px; position:relative; z-index:1; }
      .zsc-command-line .pt-btn { min-height:52px; }
      .zsc-signal-grid { display:grid; grid-template-columns:repeat(6,minmax(92px,1fr)); gap:8px; margin-bottom:14px; overflow-x:auto; }
      .zsc-signal { min-height:88px; padding:10px 8px; border:1px solid rgba(255,255,255,.13); border-radius:12px; background:rgba(8,16,42,.78); color:#fff; text-align:left; cursor:pointer; }
      .zsc-signal:hover, .zsc-signal:focus { border-color:#00f0ff; background:rgba(0,240,255,.13); outline:none; }
      .zsc-signal span { display:block; color:#ffe600; font-size:21px; }
      .zsc-signal b { display:block; margin-top:6px; font:700 10px Orbitron,sans-serif; }
      .zsc-signal small { display:block; margin-top:3px; color:rgba(255,255,255,.5); font-size:10px; }
      .zsc-live-rail { display:flex; flex-direction:column; gap:12px; }
      .zsc-side-card { padding:14px; border:1px solid rgba(0,240,255,.17); border-radius:15px; background:rgba(7,15,42,.84); box-shadow:0 8px 24px rgba(0,0,0,.16); }
      .zsc-side-card h3 { margin:0 0 10px; color:#fff; font:700 12px Orbitron,sans-serif; letter-spacing:1px; }
      .zsc-side-row { display:flex; align-items:center; gap:8px; padding:9px 0; border-bottom:1px solid rgba(255,255,255,.07); }
      .zsc-side-row:last-child { border-bottom:0; }
      .zsc-side-row > div { flex:1; min-width:0; }
      .zsc-side-row b { display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:12px; }
      .zsc-side-row span { display:block; margin-top:3px; color:rgba(255,255,255,.5); font-size:10px; line-height:1.35; }
      .zsc-orbit-meter { height:8px; margin:8px 0 11px; overflow:hidden; border-radius:6px; background:rgba(255,255,255,.12); }
      .zsc-orbit-meter span { display:block; width:68%; height:100%; background:linear-gradient(90deg,#00f0ff,#ffe600,#ff2d95); }
      .zsc-footer-note { margin-top:14px; padding:12px 14px; border:1px solid rgba(255,255,255,.09); border-radius:12px; color:rgba(255,255,255,.5); font-size:11px; line-height:1.5; }
      @media (max-width:1120px) { .zsc-layout { grid-template-columns:176px minmax(0,1fr); } .zsc-live-rail { display:none; } }
      @media (max-width:899px) { .zsc-topbar { align-items:flex-start; flex-wrap:wrap; } .zsc-brand { min-width:0; flex:1; } .zsc-topline { order:3; flex-basis:100%; } .zsc-topbar .zs-select { width:100%; } .zsc-layout { grid-template-columns:1fr; } .zsc-command-rail { position:static; display:flex; gap:6px; overflow-x:auto; padding:8px; } .zsc-rail-title { display:none; } .zsc-rail-btn { flex:0 0 112px; min-height:66px; margin:0; display:block; padding:8px; text-align:center; } .zsc-rail-btn span { display:block; width:auto; margin-bottom:4px; } .zsc-rail-btn small { display:none; } .zsc-signal-grid { grid-template-columns:repeat(3,minmax(104px,1fr)); } }
      @media (max-width:520px) { .zsc-topbar { padding:12px; } .zsc-brand b { font-size:14px; } .zsc-brand span { font-size:10px; } .zsc-welcome { padding:17px; } .zsc-welcome::after { display:none; } .zsc-signal-grid { grid-template-columns:repeat(2,minmax(120px,1fr)); } }

      /* ===== First-launch language gate ===== */
      #language-gate {
          position: absolute;
          inset: 0;
          z-index: 1000;
          display: none;
          align-items: center;
          justify-content: center;
          padding: 24px;
          box-sizing: border-box;
          background: rgba(1, 3, 14, 0.86);
          backdrop-filter: blur(14px);
          font-family: Inter, sans-serif;
          color: #fff;
          pointer-events: auto;
          touch-action: manipulation;
      }
      #language-gate.visible { display: flex; }
      .language-gate-card {
          width: min(560px, 100%);
          max-height: min(760px, calc(100% - 24px));
          overflow-y: auto;
          padding: clamp(24px, 5vw, 42px);
          box-sizing: border-box;
          text-align: center;
          border: 1px solid rgba(0, 240, 255, 0.5);
          border-radius: 24px;
          background: linear-gradient(145deg, rgba(10, 20, 55, 0.98), rgba(35, 9, 61, 0.98));
          box-shadow: 0 24px 70px rgba(0,0,0,.6), 0 0 45px rgba(0,240,255,.2);
      }
      .language-gate-card h1 {
          margin: 0;
          color: #00f0ff;
          font: 700 clamp(25px, 5vw, 40px) Orbitron, sans-serif;
          letter-spacing: 1px;
      }
      .language-gate-card p { margin: 12px 0 22px; color: rgba(255,255,255,.68); font-size: 15px; }
      #language-gate-select {
          width: 100%;
          min-height: 64px;
          padding: 0 16px;
          border: 1px solid rgba(0,240,255,.45);
          border-radius: 13px;
          background: #0a1535;
          color: #fff;
          font: 600 17px Inter, sans-serif;
          cursor: pointer;
          outline: none;
      }
      #language-gate-select:focus { border-color: #ffe600; box-shadow: 0 0 0 3px rgba(255,230,0,.14); }
      #language-gate-continue {
          width: 100%;
          min-height: 70px;
          margin-top: 18px;
          border: 1px solid #00f0ff;
          border-radius: 14px;
          background: linear-gradient(180deg, rgba(0,240,255,.42), rgba(0,100,190,.4));
          color: #fff;
          font: 700 17px Orbitron, sans-serif;
          letter-spacing: 1px;
          cursor: pointer;
      }
      #language-gate-continue:active { transform: scale(.98); }

      /* ===== Header language picker ===== */
      #pt-language-picker { position:relative; flex:none; z-index:12; }
      #pt-language-toggle { min-width:66px; height:56px; padding:0 9px; display:flex; align-items:center; justify-content:center; gap:5px; border:1px solid rgba(0,240,255,.45); border-radius:14px; background:rgba(0,150,220,.2); color:#fff; cursor:pointer; font:700 12px Inter,sans-serif; }
      #pt-language-toggle:hover, #pt-language-toggle:focus { border-color:#00f0ff; outline:none; box-shadow:0 0 0 3px rgba(0,240,255,.12); }
      #pt-language-flag { font-size:23px; line-height:1; }
      #pt-language-code { color:#8ff9ff; font:700 10px Orbitron,sans-serif; letter-spacing:1px; }
      #pt-language-menu { position:absolute; top:64px; right:0; display:none; grid-template-columns:repeat(4,minmax(0,1fr)); gap:6px; width:min(360px,calc(100vw - 24px)); max-height:410px; overflow-y:auto; padding:9px; border:1px solid rgba(0,240,255,.42); border-radius:14px; background:rgba(3,8,27,.98); box-shadow:0 16px 36px rgba(0,0,0,.5),0 0 24px rgba(0,240,255,.18); }
      #pt-language-picker.open #pt-language-menu { display:grid; }
      .pt-language-option { min-height:54px; display:flex; align-items:center; justify-content:center; gap:4px; padding:0 5px; border:1px solid rgba(255,255,255,.1); border-radius:9px; background:rgba(255,255,255,.05); color:rgba(255,255,255,.8); cursor:pointer; font:600 11px Inter,sans-serif; }
      .pt-language-option:hover, .pt-language-option:focus, .pt-language-option.active { border-color:#00f0ff; background:rgba(0,240,255,.18); color:#fff; outline:none; }
      .pt-language-option .flag { font-size:20px; line-height:1; }
      .pt-language-option .code { color:#8ff9ff; font:700 10px Orbitron,sans-serif; }

      /* ===== Portal-wide admin announcement banner ===== */
      #global-announcement { position:absolute; top:0; left:0; width:100%; height:78px; display:none; overflow:hidden; z-index:230; pointer-events:none; border-bottom:2px solid #ffe600; background:linear-gradient(90deg,rgba(255,45,107,.98),rgba(255,122,24,.98) 48%,rgba(255,230,0,.96)); box-shadow:0 0 28px rgba(255,45,107,.85),0 5px 24px rgba(0,0,0,.5); }
      #global-announcement.visible { display:block; }
      .global-announcement-bar { height:100%; display:flex; align-items:center; gap:13px; }
      .global-announcement-icon { flex:none; margin-left:18px; color:#160914; font-size:39px; line-height:1; text-shadow:0 2px 0 rgba(255,255,255,.35); }
      .global-announcement-viewport { flex:1; min-width:0; overflow:hidden; white-space:nowrap; }
      .global-announcement-track { display:inline-flex; align-items:center; min-width:max-content; padding-right:15vw; color:#160914; font:900 clamp(25px,4.7vw,54px)/1 Orbitron,sans-serif; letter-spacing:3px; text-transform:uppercase; text-shadow:0 2px 0 rgba(255,255,255,.3),0 0 14px rgba(255,255,255,.55); animation:globalAnnouncementMarquee 18s linear infinite; }
      #global-announcement-close { pointer-events:auto; flex:none; width:50px; height:50px; margin-right:14px; border:2px solid rgba(22,9,20,.42); border-radius:50%; background:rgba(255,255,255,.28); color:#160914; font:700 31px/1 Inter,sans-serif; cursor:pointer; box-shadow:0 0 14px rgba(255,255,255,.35); }
      #global-announcement-close:hover, #global-announcement-close:focus { background:#fff; outline:none; transform:scale(1.06); }
      @keyframes globalAnnouncementMarquee { from { transform:translateX(100%); } to { transform:translateX(-100%); } }
      .global-admin-card { border-color:rgba(255,230,0,.5); background:linear-gradient(135deg,rgba(255,45,107,.12),rgba(255,230,0,.08)); }
      .global-admin-card textarea { width:100%; min-height:86px; box-sizing:border-box; resize:vertical; padding:12px; border:1px solid rgba(255,230,0,.4); border-radius:10px; background:rgba(0,0,0,.35); color:#fff; font:14px/1.45 Inter,sans-serif; outline:none; }
      .global-admin-card textarea:focus { border-color:#ffe600; box-shadow:0 0 0 3px rgba(255,230,0,.12); }
      .global-admin-preview { margin-top:9px; padding:8px 10px; border-radius:8px; background:rgba(0,0,0,.24); color:rgba(255,255,255,.65); font-size:11px; line-height:1.4; }
      @media (max-width:899px) { #pt-language-toggle { min-width:58px; height:52px; } #pt-language-menu { top:59px; } #global-announcement { height:66px; } .global-announcement-icon { margin-left:10px; font-size:30px; } .global-announcement-track { letter-spacing:1.5px; } }
      @media (max-width:520px) { #pt-language-toggle { min-width:50px; padding:0 6px; } #pt-language-code { display:none; } #pt-language-menu { grid-template-columns:repeat(3,minmax(0,1fr)); width:min(300px,calc(100vw - 18px)); } #global-announcement { height:60px; } .global-announcement-icon { margin-left:8px; font-size:26px; } .global-announcement-track { font-size:24px; } }
      @media (prefers-reduced-motion: reduce) { .global-announcement-track { animation-duration:34s; } }

      /* ===== Header collision fix: language and account actions stay in one dock ===== */
      /* Keep all portal selectors above the decorative backdrop and touch-safe.
         The game world disables touch gestures globally, so native controls need
         an explicit interaction policy of their own. */
      #pt-nav,
      #pt-nav *,
      #pt-top-actions,
      #pt-top-actions *,
      #pt-page-select,
      #pt-language-menu,
      #pt-language-menu * {
          pointer-events: auto;
          touch-action: manipulation;
      }
      #pt-nav {
          position: relative;
          z-index: 400;
      }
      #pt-page-select,
      #pt-category-select,
      #pt-language-toggle {
          position: relative;
          z-index: 401;
          cursor: pointer;
      }
      #pt-language-picker {
          position: relative;
          z-index: 500;
          isolation: isolate;
      }
      #pt-top-actions #pt-language-menu {
          z-index: 100000;
          pointer-events: auto;
      }
      .pt-language-option {
          position: relative;
          z-index: 100001;
          pointer-events: auto !important;
          touch-action: manipulation;
          user-select: none;
          -webkit-user-select: none;
      }
      #pt-top-actions {
          position: relative;
          z-index: 260;
          display: flex;
          align-items: center;
          justify-content: flex-end;
          gap: 8px;
          margin-left: auto;
          flex: none;
      }
      #pt-top-actions #pt-language-picker { position: relative; z-index: 270; }
      #pt-top-actions #pt-language-menu {
          position: fixed;
          pointer-events: auto !important;
          touch-action: manipulation;
          /* Floating below the header center, away from the language toggle and
             account controls, so opening it never collides with the top dock. */
          top: 104px;
          left: 50%;
          right: auto;
          transform: translateX(-50%);
          width: min(420px, calc(100vw - 36px));
          max-height: min(520px, calc(100vh - 100px));
          padding: 10px;
          grid-template-columns: repeat(4, minmax(0, 1fr));
          display: none;
          overflow-x: hidden;
          overflow-y: auto;
          overscroll-behavior: contain;
          z-index: 10000;
          background: rgba(4, 9, 26, 0.98);
          backdrop-filter: blur(20px);
          border: 1px solid rgba(0, 240, 255, 0.4);
          border-radius: 16px;
      }
      #pt-language-picker.open #pt-language-menu {
          display: grid;
      }
      #pt-top-actions #pt-language-menu::before {
          content: 'SELECT LANGUAGE';
          grid-column: 1 / -1;
          padding: 4px 8px 8px;
          color: rgba(143, 249, 255, 0.6);
          font: 700 11px Orbitron, sans-serif;
          letter-spacing: 2px;
          text-align: left;
          border-bottom: 1px solid rgba(255, 255, 255, 0.1);
          margin-bottom: 8px;
      }
      #global-announcement { z-index: 240; }
      @media (max-width: 899px) {
          #pt-top-actions { gap: 6px; }
          #pt-top-actions #pt-wallet { display: none; }
          #pt-search-form { flex: 1; min-width: 0; margin: 0 4px; }
          #pt-global-search { min-height: 48px; padding: 0 14px; font-size: 14px; border-radius: 12px; }
          #pt-top-actions #pt-language-menu { top: 92px; left: 50%; right: auto; transform: translateX(-50%); width: min(360px, calc(100vw - 20px)); max-height: calc(100vh - 100px); grid-template-columns: repeat(3, minmax(0, 1fr)); }
          #global-announcement-close { width:42px; height:42px; margin-right:8px; font-size:26px; }
      }
      @media (max-width: 520px) {
          #pt-top-actions { gap: 4px; }
          #pt-search-form { display: none; }
          #pt-logo { flex: 1; min-width: 0; }
          #pt-top-actions #pt-language-toggle { min-width: 54px; height: 50px; }
          #pt-top-actions #pt-user, #pt-top-actions #pt-close { width: 50px; height: 50px; }
          #pt-top-actions #pt-language-menu { top: 82px; left: 50%; right: auto; transform: translateX(-50%); width: min(320px, calc(100vw - 16px)); max-height: calc(100vh - 90px); }
          #global-announcement-close { width:36px; height:36px; margin-right:6px; font-size:23px; }
      }
    </style>
  <script>window.gameConfig = {
  "player": {
    "startSize": 21,
    "color": "#99ff00",
    "growthRate": 1.2,
    "speedMultiplier": 1.3
  },
  "arena": {
    "size": 5000,
    "particleCount": 150
  },
  "visual": {
    "gridColor": "#29117e",
    "particleColor": "#ff0000"
  },
  "ai": {
    "count": 100,
    "minStartSize": 14,
    "maxStartSize": 55,
    "aggression": 0.6,
    "growthRate": 1
  },
  "camera": {
    "zoomLevel": 1.6
  },
  "mechanics": {
    "splitMinMass": 36,
    "maxCells": 16,
    "mergeTime": 10,
    "splitBoost": 760,
    "ejectMass": 12,
    "virusCount": 10,
    "virusMass": 100,
    "zoneCount": 3,
    "zoneCaptureRate": 0.22,
    "zoneReward": 180,
    "maxMass": 600000
  },
  "abilities": {
    "dashCooldown": 9,
    "dashDuration": 3,
    "godCooldown": 28,
    "dashSpeed": 2.3,
    "godDuration": 5,
    "powerupCount": 5,
    "pulseCooldown": 16,
    "pulseRadius": 520,
    "pulseForce": 640,
    "relicCount": 10,
    "relicValue": 80,
    "surgeDuration": 4,
    "surgeCooldown": 20,
    "surgeSpeed": 1.7,
    "surgeRadius": 680
  },
  "ui": {
    "chat": true,
    "minimap": true,
    "botChatter": true
  },
  "branding": {
    "name": "Zero Arcade"
  }
};</script>
</head>
  <body>
    <div id="game-world">
      <div id="language-gate" role="dialog" aria-modal="true" aria-labelledby="language-gate-title">
        <div class="language-gate-card">
          <h1 id="language-gate-title">Choose your language</h1>
          <p id="language-gate-subtitle">Select a language before entering the site.</p>
          <select id="language-gate-select" aria-label="Choose your language"></select>
          <button id="language-gate-continue" type="button">CONTINUE</button>
        </div>
      </div>
      <canvas id="game-canvas"></canvas>
      <div id="global-announcement" role="status" aria-live="assertive" aria-hidden="true">
        <div class="global-announcement-bar">
          <span class="global-announcement-icon" aria-hidden="true">📢</span>
          <div class="global-announcement-viewport"><div class="global-announcement-track" id="global-announcement-track"><span id="global-announcement-text"></span></div></div>
          <button id="global-announcement-close" type="button" aria-label="Chiudi annuncio">×</button>
        </div>
      </div>
      <div id="vignette-overlay"></div>
      <div class="hud">
        <div class="score-container">
          <div class="score-badge">
            <span class="mass-label">MASS</span>
            <span class="score-display" id="score-display">20</span>
          </div>
        </div>
        <div class="leaderboard" id="leaderboard"><h3>Leaderboard</h3><div id="lb-list"></div></div>
        <div class="stat-strip" id="stat-strip">
          <span class="stat-pill" id="pill-time">00:00</span>
          <span class="stat-pill" id="pill-level">LV 1</span>
          <span class="stat-pill" id="pill-rank">#-</span>
          <span class="stat-pill coins" id="pill-coins">0 ZC</span>
          <span class="stat-pill" id="pill-zones">ZONES 0/3</span>
          <span class="stat-pill combo hidden" id="pill-combo">x1</span>
          <span class="stat-pill hidden" id="pill-fps">60 FPS</span>
        </div>
        <div class="ability-hud" id="ability-hud">
          <div class="ability-chip ready" id="chip-dash">
            <span id="chip-dash-label">SHIFT DASH</span>
            <span class="chip-bar"><span class="chip-fill" id="chip-dash-fill"></span></span>
          </div>
          <div class="ability-chip ready" id="chip-god">
            <span id="chip-god-label">G GOD</span>
            <span class="chip-bar"><span class="chip-fill" id="chip-god-fill"></span></span>
          </div>
          <div class="ability-chip ready" id="chip-pulse">
            <span id="chip-pulse-label">E PULSE</span>
            <span class="chip-bar"><span class="chip-fill" id="chip-pulse-fill"></span></span>
          </div>
          <div class="ability-chip ready" id="chip-surge">
            <span id="chip-surge-label">R SURGE</span>
            <span class="chip-bar"><span class="chip-fill" id="chip-surge-fill"></span></span>
          </div>
        </div>
        <div id="effect-strip"></div>
        <div id="notif-feed"></div>
        <div id="profile-chip">
          <div id="chip-avatar"></div>
          <div>
            <div id="chip-name">Guest</div>
            <span class="role-badge role-user" id="chip-role">USER</span>
          </div>
        </div>
      </div>
      <div class="action-buttons" id="action-buttons">
        <button class="action-btn" id="eject-btn"><span class="icon">&raquo;</span>FEED</button>
        <button class="action-btn" id="split-btn"><span class="icon">&#9678;</span>SPLIT</button>
        <button class="action-btn" id="dash-btn"><span class="icon">&#9889;</span>DASH</button>
        <button class="action-btn" id="god-btn"><span class="icon">&#10022;</span>GOD</button>
        <button class="action-btn" id="pulse-btn"><span class="icon">✹</span>PULSE</button>
        <button class="action-btn" id="surge-btn"><span class="icon">✦</span>SURGE</button>
      </div>
      <div id="minimap-wrap">
        <canvas id="minimap" width="156" height="156"></canvas>
        <div id="minimap-label">MAP</div>
      </div>
      <div id="chat-panel">
        <div id="chat-body">
          <div id="chat-log"></div>
          <div class="quick-emotes">
            <button data-emote="gg!">GG</button>
            <button data-emote="&#128128;">&#128128;</button>
            <button data-emote="&#128293;">&#128293;</button>
            <button data-emote="&#129504;">&#129504;</button>
            <button data-emote="&#128075;">&#128075;</button>
          </div>
          <form id="chat-form" autocomplete="off">
            <input id="chat-input" maxlength="120" placeholder="Global chat..." />
            <button id="chat-send" type="submit">&#10148;</button>
          </form>
        </div>
        <button id="chat-toggle">CHAT<span id="chat-badge">0</span></button>
      </div>
      <div class="overlay-panel" id="pause-overlay">
        <h2>PAUSED</h2>
        <div class="key-list">Tap resume or press <b>P</b> to continue.</div>
        <button class="overlay-btn" id="resume-btn">RESUME</button>
      </div>
      <div class="overlay-panel" id="help-overlay">
        <h2>CONTROLS</h2>
        <div class="key-list">
          <b>Mouse / Touch</b> &mdash; move<br />
          <b>Arrows / A S D</b> &mdash; keyboard move<br />
          <b>SPACE</b> &mdash; split<br />
          <b>W</b> &mdash; feed (eject mass)<br />
          <b>SHIFT</b> &mdash; spin dash (3s)<br />
          <b>G</b> &mdash; god mode (5s)<br />
          <b>E</b> &mdash; arcade pulse (knock back nearby rivals)<br />
          <b>R</b> &mdash; neon surge (speed, magnet and combo rewards)<br />
          <b>Wheel / +-</b> &mdash; zoom out to whole map<br />
          <b>TAB</b> (hold) &mdash; map overview<br />
          <b>C / T</b> &mdash; chat &nbsp; <b>M</b> &mdash; mute<br />
          <b>ESC / U</b> &mdash; user menu (account, settings, skin)<br />
          <b>Q</b> &mdash; portale Zero Arcade (giochi, shop, ZeroCoin)<br />
          <b>B</b> &mdash; musica on/off &nbsp; <b>N</b> &mdash; traccia successiva<br />
          <b>F</b> &mdash; FPS &nbsp; <b>P</b> &mdash; pause &nbsp; <b>H</b> &mdash; help
        </div>
        <button class="overlay-btn" id="help-close-btn">CLOSE</button>
      </div>
      <div id="user-menu">
        <div class="um-header">
          <div id="um-avatar"></div>
          <div class="um-id">
            <div id="um-name">Guest</div>
            <span class="role-badge role-user" id="um-role">USER</span>
            <div class="xp-bar"><span id="um-xp-fill"></span></div>
            <div id="um-xp-text">LV 1</div>
          </div>
          <button id="um-close">&times;</button>
        </div>
        <div id="um-tabs"></div>
        <div class="um-body">
          <div class="um-pane on" id="pane-account"></div>
          <div class="um-pane" id="pane-settings"></div>
          <div class="um-pane" id="pane-skin"></div>
          <div class="um-pane" id="pane-admin"></div>
          <div class="um-pane" id="pane-stats"></div>
        </div>
      </div>
      <button id="portal-btn">&#9776; PORTALE</button>
      <div id="portal">
        <div class="space-backdrop" aria-hidden="true"><span class="starfield starfield-a"></span><span class="starfield starfield-b"></span><span class="space-orbit orbit-one"></span><span class="space-orbit orbit-two"></span><span class="space-planet"></span><span class="space-planet-two"></span><span class="space-satellite"></span><span class="space-shuttle"></span><span class="orbit-label station">ORBITAL STATION // ZA-01</span><span class="orbit-label shuttle-label">SHUTTLE LINK</span></div>
        <div id="pt-top">
          <button class="pt-icon-btn pt-menu-btn" id="pt-menu-toggle" aria-label="Apri o chiudi il menu">&#9776;</button>
          <div id="pt-logo"><img id="pt-logo-img" alt="ZERO THE LEGEND" /><span id="pt-logo-fallback">ZERO<span>ARCADE</span></span></div>
          <form id="pt-search-form" autocomplete="off"><input id="pt-global-search" type="search" maxlength="80" placeholder="Cerca nel portale..." aria-label="Cerca nel portale" /></form>
          <div id="pt-top-actions" aria-label="Azioni portale">
            <div id="pt-language-picker" aria-label="Seleziona lingua">
              <button id="pt-language-toggle" type="button" aria-haspopup="true" aria-expanded="false" aria-label="Seleziona lingua"><span id="pt-language-flag">🌐</span><span id="pt-language-code">IT</span></button>
              <div id="pt-language-menu" role="menu"></div>
            </div>
            <button class="pt-icon-btn pt-notify-btn" id="pt-notify" aria-label="Notifiche">&#128276;<span id="pt-notify-count">0</span></button>
            <div id="pt-wallet"><span>&#129689;</span><span id="pt-coins">0</span> ZC</div>
            <button class="pt-icon-btn" id="pt-user">&#128100;</button>
            <button class="pt-icon-btn" id="pt-close">&#9654;</button>
          </div>
        </div>
        <div id="pt-nav"></div>
        <div id="pt-body"></div>
        <div id="portal-support-dock" class="portal-chat-dock" hidden>
          <div class="portal-chat-panel" id="portal-support-panel"></div>
          <button class="portal-chat-toggle" id="portal-support-toggle" type="button" aria-label="Apri chat di supporto staff">
            <span class="chat-brand-icon">⚙</span><span><strong>SUPPORT CHAT</strong><small>Solo MOD e ADMIN · stanza locale</small></span><span class="portal-chat-badge" id="portal-support-badge">STAFF</span>
          </button>
        </div>
        <div id="portal-fb-chat-dock" class="portal-chat-dock">
          <div class="portal-chat-panel" id="portal-fb-chat-panel"></div>
          <button class="portal-chat-toggle" id="portal-fb-chat-toggle" type="button" aria-label="Apri Facebook chat locale">
            <span class="chat-brand-icon">f</span><span><strong>FACEBOOK CHAT</strong><small>Messenger locale · footer room</small></span><span class="portal-chat-badge" id="portal-fb-chat-badge">CHAT</span>
          </button>
        </div>
      </div>
      <div id="game-over">
        <div class="go-title">DEVOURED</div>
        <div class="go-sub">A bigger cell swallowed you whole.</div>
        <div class="go-score-label">final mass</div>
        <div class="go-score" id="final-score">0</div>
        <div id="run-stats"></div>
        <div id="global-lb">
          <h3>GLOBAL BEST</h3>
          <div id="global-lb-list">Loading...</div>
        </div>
        <button id="restart-btn">PLAY AGAIN</button>
        <button class="overlay-btn" id="go-portal-btn">PORTALE</button>
      </div>
    </div>

    <script>
      // ZeroArcade bridge: replace Astrocade-only services with authenticated PHP/MySQL APIs.
      window.__ZA_AGAR_USER = <?php echo json_encode($zaUserPublic, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
      window.__ZA_AGAR_ASSETS = {"absorb_sfx":{"url":"https://d2oir5eh8rty2e.cloudfront.net/assets/sounds/effect/absorb_sfx_4e361433-c60d-409c-9450-bba2fa09be54.mp3","type":"audio"},"split_sfx":{"url":"https://d2oir5eh8rty2e.cloudfront.net/assets/sounds/effect/split_sfx_50134bb2-8a77-4cd7-b13f-331c613ede4c.mp3","type":"audio"},"eject_sfx":{"url":"https://d2oir5eh8rty2e.cloudfront.net/assets/sounds/effect/eject_sfx_3daa6c87-5ef1-4e4e-8541-1fadcf8ced74.mp3","type":"audio"},"virus_asset":{"url":"https://d2oir5eh8rty2e.cloudfront.net/assets/images/virus_asset_ab4b38c4-88ca-4417-9c50-1331a72de82d.webp","type":"image"}};
      window.__growthOrbitStandalone = false;
      window.lib = {
        log: function (m) { if (window.console) console.log('[ZeroArcade Agar.io]', m); },
        showGameParameters: function () {},
        getAsset: function (id) {
          var a = window.__ZA_AGAR_ASSETS[id];
          return a ? a : null;
        },
        getUserGameState: async function () {
          var r = await fetch('api/agar_io.php?action=load', {credentials:'same-origin',cache:'no-store'});
          var d = await r.json();
          if (!r.ok || !d.success) throw new Error(d.error || 'load failed');
          return { state: d.state };
        },
        saveUserGameState: async function (state) {
          var r = await fetch('api/agar_io.php', {method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'save',state:state})});
          var d = await r.json();
          if (!r.ok || !d.success) throw new Error(d.error || 'save failed');
          return d;
        },
        deleteUserGameState: async function () {
          // Account progress is intentionally not deletable from the client game.
          return {success:true};
        },
        addPlayerScoreToLeaderboard: async function (score, limit) {
          var r = await fetch('api/agar_io.php', {method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'submit',score:Math.round(score),kills:(window.__zaRunStats&&window.__zaRunStats.rivals)||0,particles:(window.__zaRunStats&&window.__zaRunStats.particles)||0,time:(window.__zaRunStats&&window.__zaRunStats.time)||0,coins:Math.min(150,Math.max(10,Math.round(score/100)))})});
          var d = await r.json();
          if (!r.ok || !d.success) throw new Error(d.error || 'submit failed');
          return d;
        },
        getTopNEntriesFromLeaderboard: async function (limit) {
          var r = await fetch('api/leaderboard.php?game=agar_io', {credentials:'same-origin',cache:'no-store'});
          var d = await r.json();
          if (!r.ok || !d.success) throw new Error(d.error || 'leaderboard failed');
          var entries = (d.top || []).slice(0, limit || 10);
          var rank = 0;
          var me = String(window.__ZA_AGAR_USER.username || '').toLowerCase();
          entries.forEach(function(e,i){ if(String(e.username||'').toLowerCase()===me) rank=i+1; });
          return {entries:entries,userRank:rank||null};
        }
      };
    </script>
    <script>
      /*
       * Zero Arcade — Neon arena experience
       * Polished neon aesthetics with layered glowing cells, parallax grid background,
       * particle sparkles, smooth absorption dissolve effects, and cell growth pulse.
       */

      // Standalone FTP build support. On Astrocade, the host-provided config and `lib`
      // are kept unchanged; on a normal static web host this supplies the single-file
      // game's initial configuration and gracefully falls back to procedural audio.
      (function prepareStandaloneBuild() {
        const hasAstrocadeHost = !!window.lib;
        window.__growthOrbitStandalone = !hasAstrocadeHost;

        if (!window.gameConfig) {
          window.gameConfig = {
            branding: { name: 'Zero Arcade' },
            player: { startSize: 21, color: '#00ffff', growthRate: 1, speedMultiplier: 1.3 },
            arena: { size: 3600, particleCount: 120 },
            visual: { particleColor: '#ffe600', gridColor: '#1a3a52' },
            ai: { count: 100, minStartSize: 14, maxStartSize: 55, aggression: 0.6, growthRate: 1 },
            camera: { zoomLevel: 0.9 },
            mechanics: {
              splitMinMass: 36, maxCells: 12, mergeTime: 10, splitBoost: 760,
              ejectMass: 12, virusCount: 10, virusMass: 100,
              zoneCount: 3, zoneCaptureRate: 0.22, zoneReward: 180, maxMass: 600000
            },
            abilities: {
              dashDuration: 3, dashSpeed: 2.3, dashCooldown: 9,
              godDuration: 5, godCooldown: 28, powerupCount: 5,
              pulseCooldown: 16, pulseRadius: 520, pulseForce: 640, relicCount: 3, relicValue: 25,
              surgeDuration: 4, surgeCooldown: 20, surgeSpeed: 1.7, surgeRadius: 680,
            },
            ui: { minimap: true, chat: true, botChatter: true }
          };
        }

        if (!hasAstrocadeHost) {
          // The standalone build has no platform services or required media files.
          // `getAsset` deliberately returns nothing, making the existing synthesized
          // pop-sound fallback the only sound source.
          window.lib = {
            getAsset: () => null,
            showGameParameters: () => {},
            log: (message) => { if (window.console) console.log('[Zero Arcade]', message); }
          };
          const heading = document.querySelector('#global-lb h3');
          const message = document.getElementById('global-lb-list');
          if (heading) heading.textContent = 'LOCAL RUN';
          if (message) message.textContent = 'Static FTP edition';
        }
      })();

      async function run(mode) {
        const gameWorld = document.getElementById('game-world');
        const canvas = document.getElementById('game-canvas');
        const ctx = canvas.getContext('2d');
        const vignetteOverlay = document.getElementById('vignette-overlay');
        const scoreEl = document.getElementById('score-display');
        const lbListEl = document.getElementById('lb-list');
        const gameOverEl = document.getElementById('game-over');
        const finalScoreEl = document.getElementById('final-score');
        const globalLbListEl = document.getElementById('global-lb-list');
        const restartBtn = document.getElementById('restart-btn');
        const actionsEl = document.getElementById('action-buttons');
        const splitBtn = document.getElementById('split-btn');
        const ejectBtn = document.getElementById('eject-btn');
        const dashBtn = document.getElementById('dash-btn');
        const godBtn = document.getElementById('god-btn');
        const pulseBtn = document.getElementById('pulse-btn');
        const surgeBtn = document.getElementById('surge-btn');
        const statStripEl = document.getElementById('stat-strip');
        const abilityHudEl = document.getElementById('ability-hud');
        const effectStripEl = document.getElementById('effect-strip');
        const notifFeedEl = document.getElementById('notif-feed');
        const pillTimeEl = document.getElementById('pill-time');
        const pillLevelEl = document.getElementById('pill-level');
        const pillRankEl = document.getElementById('pill-rank');
        const pillComboEl = document.getElementById('pill-combo');
        const pillCoinsEl = document.getElementById('pill-coins');
        const pillZonesEl = document.getElementById('pill-zones');
        const pillFpsEl = document.getElementById('pill-fps');
        const chipDash = document.getElementById('chip-dash');
        const chipDashLabel = document.getElementById('chip-dash-label');
        const chipDashFill = document.getElementById('chip-dash-fill');
        const chipGod = document.getElementById('chip-god');
        const chipGodLabel = document.getElementById('chip-god-label');
        const chipGodFill = document.getElementById('chip-god-fill');
        const chipPulse = document.getElementById('chip-pulse');
        const chipPulseLabel = document.getElementById('chip-pulse-label');
        const chipPulseFill = document.getElementById('chip-pulse-fill');
        const chipSurge = document.getElementById('chip-surge');
        const chipSurgeLabel = document.getElementById('chip-surge-label');
        const chipSurgeFill = document.getElementById('chip-surge-fill');
        const minimapWrap = document.getElementById('minimap-wrap');
        const miniCanvas = document.getElementById('minimap');
        const miniCtx = miniCanvas ? miniCanvas.getContext('2d') : null;
        const chatPanel = document.getElementById('chat-panel');
        const chatToggleBtn = document.getElementById('chat-toggle');
        const chatBadgeEl = document.getElementById('chat-badge');
        const chatLogEl = document.getElementById('chat-log');
        const chatFormEl = document.getElementById('chat-form');
        const chatInputEl = document.getElementById('chat-input');
        const pauseOverlay = document.getElementById('pause-overlay');
        const helpOverlay = document.getElementById('help-overlay');
        const resumeBtn = document.getElementById('resume-btn');
        const helpCloseBtn = document.getElementById('help-close-btn');
        const runStatsEl = document.getElementById('run-stats');

        function resizeCanvas() {
          canvas.width = gameWorld.clientWidth;
          canvas.height = gameWorld.clientHeight;
        }
        resizeCanvas();
        window.addEventListener('resize', resizeCanvas);

        const cfg = window.gameConfig;
        if (!cfg.branding || typeof cfg.branding !== 'object') cfg.branding = {};
        if (!cfg.branding.name) cfg.branding.name = 'Zero Arcade';
        const gameTitle = cfg.branding.name;
        if (!cfg.visual) cfg.visual = {};
        if (!cfg.visual.gridColor) cfg.visual.gridColor = '#1a3a52';
        if (!cfg.visual.particleColor) cfg.visual.particleColor = '#ffe600';
        if (!cfg.player) cfg.player = {};
        if (!cfg.player.color) cfg.player.color = '#00ffff';
        if (cfg.player.speedMultiplier === undefined) cfg.player.speedMultiplier = 1;
        if (!cfg.camera) cfg.camera = {};
        if (cfg.camera.zoomLevel === undefined) cfg.camera.zoomLevel = 1;
        if (!cfg.ai) cfg.ai = {};
        if (cfg.ai.count === undefined) cfg.ai.count = 100;
        if (cfg.ai.minStartSize === undefined) cfg.ai.minStartSize = 14;
        if (cfg.ai.maxStartSize === undefined) cfg.ai.maxStartSize = 55;
        if (cfg.ai.aggression === undefined) cfg.ai.aggression = 0.6;
        if (cfg.ai.growthRate === undefined) cfg.ai.growthRate = 1;
        if (!cfg.mechanics) cfg.mechanics = {};
        if (cfg.mechanics.splitMinMass === undefined) cfg.mechanics.splitMinMass = 36;
        if (cfg.mechanics.maxCells === undefined) cfg.mechanics.maxCells = 12;
        if (cfg.mechanics.mergeTime === undefined) cfg.mechanics.mergeTime = 10;
        if (cfg.mechanics.splitBoost === undefined) cfg.mechanics.splitBoost = 760;
        if (cfg.mechanics.ejectMass === undefined) cfg.mechanics.ejectMass = 12;
        if (cfg.mechanics.virusCount === undefined) cfg.mechanics.virusCount = 10;
        if (cfg.mechanics.virusMass === undefined) cfg.mechanics.virusMass = 100;
        if (!cfg.abilities) cfg.abilities = {};
        if (cfg.abilities.dashDuration === undefined) cfg.abilities.dashDuration = 3;
        if (cfg.abilities.dashSpeed === undefined) cfg.abilities.dashSpeed = 2.3;
        if (cfg.abilities.dashCooldown === undefined) cfg.abilities.dashCooldown = 9;
        if (cfg.abilities.godDuration === undefined) cfg.abilities.godDuration = 5;
        if (cfg.abilities.godCooldown === undefined) cfg.abilities.godCooldown = 28;
        if (cfg.abilities.powerupCount === undefined) cfg.abilities.powerupCount = 5;
        if (cfg.abilities.pulseCooldown === undefined) cfg.abilities.pulseCooldown = 16;
        if (cfg.abilities.pulseRadius === undefined) cfg.abilities.pulseRadius = 520;
        if (cfg.abilities.pulseForce === undefined) cfg.abilities.pulseForce = 640;
        if (cfg.abilities.relicCount === undefined) cfg.abilities.relicCount = 3;
        if (cfg.abilities.relicValue === undefined) cfg.abilities.relicValue = 25;
        if (cfg.abilities.surgeDuration === undefined) cfg.abilities.surgeDuration = 4;
        if (cfg.abilities.surgeCooldown === undefined) cfg.abilities.surgeCooldown = 20;
        if (cfg.abilities.surgeSpeed === undefined) cfg.abilities.surgeSpeed = 1.7;
        if (cfg.abilities.surgeRadius === undefined) cfg.abilities.surgeRadius = 680;
        if (!cfg.mechanics.zoneCount) cfg.mechanics.zoneCount = 3;
        if (cfg.mechanics.zoneCaptureRate === undefined) cfg.mechanics.zoneCaptureRate = 0.22;
        if (cfg.mechanics.zoneReward === undefined) cfg.mechanics.zoneReward = 180;
        if (cfg.mechanics.maxMass === undefined) cfg.mechanics.maxMass = 600000;
        if (!cfg.ui) cfg.ui = {};
        if (cfg.ui.minimap === undefined) cfg.ui.minimap = true;
        if (cfg.ui.chat === undefined) cfg.ui.chat = true;
        if (cfg.ui.botChatter === undefined) cfg.ui.botChatter = true;

        // ============================================================
        // USER PROFILE SYSTEM — accounts, roles, settings, skins
        // Everything here is player-specific (never written to gameConfig).
        // ============================================================
        const hudEl = document.querySelector('.hud');
        const userMenuEl = document.getElementById('user-menu');
        const umTabsEl = document.getElementById('um-tabs');
        const umPanes = {
          account: document.getElementById('pane-account'),
          settings: document.getElementById('pane-settings'),
          skin: document.getElementById('pane-skin'),
          admin: document.getElementById('pane-admin'),
          stats: document.getElementById('pane-stats'),
        };
        const umAvatarEl = document.getElementById('um-avatar');
        const umNameEl = document.getElementById('um-name');
        const umRoleEl = document.getElementById('um-role');
        const umXpFillEl = document.getElementById('um-xp-fill');
        const umXpTextEl = document.getElementById('um-xp-text');
        const umCloseBtn = document.getElementById('um-close');
        const profileChipEl = document.getElementById('profile-chip');
        const chipAvatarEl = document.getElementById('chip-avatar');
        const chipNameEl = document.getElementById('chip-name');
        const chipRoleEl = document.getElementById('chip-role');

        let userMenuOpen = false;
        let umTab = 'account';
        let autoRespawnTimer = 0;
        let adminGodLock = false;
        let aiFrozen = false;
        let botCommandMode = 'idle'; // idle | follow | farm
        let skinImg = null;
        let skinImgEl = null;
        let saveTimer = null;
        let playerColorOverride = null;   // player-chosen cell color (never written to gameConfig)

        function currentPlayerColor() {
          return playerColorOverride || cfg.player.color || '#00ffff';
        }

        const PROVIDERS = [
          { id: 'google', label: 'Google', color: '#c5221f', icon: 'G' },
          { id: 'facebook', label: 'Facebook', color: '#1877f2', icon: 'f' },
          { id: 'discord', label: 'Discord', color: '#5865f2', icon: '\u25C9' },
          { id: 'instagram', label: 'Instagram', color: '#c13584', icon: '\u25A3' },
          { id: 'tiktok', label: 'TikTok', color: '#1f2430', icon: '\u266A' },
          { id: 'telegram', label: 'Telegram', color: '#229ed9', icon: '\u2708' },
          { id: 'whatsapp', label: 'WhatsApp', color: '#1da851', icon: '\u260E' },
        ];
        const ROLE_ORDER = ['user', 'vip', 'helper', 'mod', 'admin'];
        const ROLE_CODES = {
          'ORBIT-VIP': 'vip',
          'ORBIT-HELPER': 'helper',
          'ORBIT-MOD': 'mod',
          'ORBIT-ADMIN': 'admin',
        };
        const SKIN_PRESETS = [
          '#00ffff', '#ffe600', '#ff2d95', '#00ffa2',
          '#b388ff', '#ff7a18', '#7fd4ff', '#ffffff',
        ];

        const I18N = {
          it: {
            account: 'ACCOUNT', settings: 'IMPOSTAZIONI', skin: 'SKIN', admin: 'STAFF', stats: 'PROGRESSI',
            nickname: 'Nickname', register: 'REGISTRATI', login: 'ACCEDI', logout: 'ESCI',
            loggedAs: 'Connesso come', via: 'via', role: 'Ruolo', level: 'Livello',
            bestMass: 'Massa record', runs: 'Partite', resetSettings: 'RIPRISTINA IMPOSTAZIONI',
            accountNote: 'Crea un profilo locale per salvare progressi, skin e impostazioni. Nickname + PIN a 4 cifre.',
            providerNote: 'Collega un profilo social (collegamento locale: nessun dato viene inviato fuori dal gioco).',
            uploadNote: 'Carica una skin: PNG, JPG, WEBP, GIF animata, SVG, BMP, AVIF. Il file resta nel tuo dispositivo.',
            drop: 'Tocca o trascina qui un file immagine / GIF',
            apply: 'APPLICA', clear: 'RIMUOVI SKIN', presets: 'COLORI RAPIDI',
            save: 'SALVA PROGRESSI', load: 'CARICA PROGRESSI', reset: 'AZZERA PROFILO',
            codeLabel: 'Codice ruolo', redeem: 'RISCATTA',
            staffLocked: 'Strumenti staff bloccati. Inserisci un codice ruolo nella scheda ACCOUNT.',
            ach: 'TROFEI', backup: 'BACKUP CODICE', exportBtn: 'ESPORTA', importBtn: 'IMPORTA',
          },
          en: {
            account: 'ACCOUNT', settings: 'SETTINGS', skin: 'SKIN', admin: 'STAFF', stats: 'PROGRESS',
            nickname: 'Nickname', register: 'REGISTER', login: 'LOG IN', logout: 'LOG OUT',
            loggedAs: 'Logged in as', via: 'via', role: 'Role', level: 'Level',
            bestMass: 'Best mass', runs: 'Runs', resetSettings: 'RESET SETTINGS',
            accountNote: 'Create a local profile to keep progress, skins and settings. Nickname + 4-digit PIN.',
            providerNote: 'Link a social profile (local link only: nothing leaves the game).',
            uploadNote: 'Upload a skin: PNG, JPG, WEBP, animated GIF, SVG, BMP, AVIF. The file stays on your device.',
            drop: 'Tap or drop an image / GIF here',
            apply: 'APPLY', clear: 'REMOVE SKIN', presets: 'QUICK COLORS',
            save: 'SAVE PROGRESS', load: 'LOAD PROGRESS', reset: 'WIPE PROFILE',
            codeLabel: 'Role code', redeem: 'REDEEM',
            staffLocked: 'Staff tools locked. Redeem a role code in the ACCOUNT tab.',
            ach: 'ACHIEVEMENTS', backup: 'BACKUP CODE', exportBtn: 'EXPORT', importBtn: 'IMPORT',
          },
        };

        // Core account labels for every language offered by the portal. Detailed
        // game copy keeps safe English fallbacks where a phrase is not defined.
        const PLAYER_LOCALE_EXTRA = {
          uk: { account:"ОБЛІКОВИЙ ЗАПИС",settings:"НАЛАШТУВАННЯ",skin:"СКІН",admin:"СТАФ",stats:"ПРОГРЕС",nickname:"Нікнейм",register:"РЕЄСТРАЦІЯ",login:"УВІЙТИ",logout:"ВИЙТИ",loggedAs:"Ви ввійшли як",via:"через",role:"Роль",level:"Рівень",bestMass:"Рекордна маса",runs:"Ігри",resetSettings:"СКИНУТИ НАЛАШТУВАННЯ",save:"ЗБЕРЕГТИ",load:"ЗАВАНТАЖИТИ",reset:"СКИНУТИ ПРОФІЛЬ",codeLabel:"Код ролі",redeem:"АКТИВУВАТИ",ach:"ДОСЯГНЕННЯ",backup:"РЕЗЕРВНА КОПІЯ",exportBtn:"ЕКСПОРТ",importBtn:"ІМПОРТ" },
          pl: { account:"KONTO",settings:"USTAWIENIA",skin:"SKÓRKA",admin:"STAFF",stats:"POSTĘPY",nickname:"Pseudonim",register:"REJESTRUJ",login:"ZALOGUJ",logout:"WYLOGUJ",loggedAs:"Zalogowano jako",via:"przez",role:"Rola",level:"Poziom",bestMass:"Rekord masy",runs:"Gry",resetSettings:"RESETUJ USTAWIENIA",save:"ZAPISZ",load:"WCZYTAJ",reset:"WYCZYŚĆ PROFIL",codeLabel:"Kod roli",redeem:"ODBIERZ",ach:"OSIĄGNIĘCIA",backup:"KOPIA ZAPASOWA",exportBtn:"EKSPORT",importBtn:"IMPORT" },
          nl: { account:"ACCOUNT",settings:"INSTELLINGEN",skin:"SKIN",admin:"STAFF",stats:"VOORTGANG",nickname:"Bijnaam",register:"REGISTREREN",login:"INLOGGEN",logout:"UITLOGGEN",loggedAs:"Ingelogd als",via:"via",role:"Rol",level:"Niveau",bestMass:"Beste massa",runs:"Rondes",resetSettings:"INSTELLINGEN HERSTELLEN",save:"OPSLAAN",load:"LADEN",reset:"PROFIEL WISSEN",codeLabel:"Rolcode",redeem:"INWISSELEN",ach:"PRESTATIES",backup:"BACK-UP",exportBtn:"EXPORTEREN",importBtn:"IMPORTEREN" },
          sv: { account:"KONTO",settings:"INSTÄLLNINGAR",skin:"SKIN",admin:"STAFF",stats:"FRAMSTEG",nickname:"Smeknamn",register:"REGISTRERA",login:"LOGGA IN",logout:"LOGGA UT",loggedAs:"Inloggad som",via:"via",role:"Roll",level:"Nivå",bestMass:"Bästa massa",runs:"Matcher",resetSettings:"ÅTERSTÄLL INSTÄLLNINGAR",save:"SPARA",load:"LADDA",reset:"RADERA PROFIL",codeLabel:"Rollkod",redeem:"LÖS IN",ach:"PRESTATIONER",backup:"SÄKERHETSKOPIA",exportBtn:"EXPORTERA",importBtn:"IMPORTERA" },
          da: { account:"KONTO",settings:"INDSTILLINGER",skin:"SKIN",admin:"STAFF",stats:"FREMSKRIDT",nickname:"Kælenavn",register:"REGISTRER",login:"LOG IND",logout:"LOG UD",loggedAs:"Logget ind som",via:"via",role:"Rolle",level:"Niveau",bestMass:"Bedste masse",runs:"Runder",resetSettings:"NULSTIL INDSTILLINGER",save:"GEM",load:"INDLÆS",reset:"SLET PROFIL",codeLabel:"Rollekode",redeem:"INDLØS",ach:"PRÆSTATIONER",backup:"SIKKERHEDSKOPI",exportBtn:"EKSPORTÉR",importBtn:"IMPORTÉR" },
          no: { account:"KONTO",settings:"INNSTILLINGER",skin:"SKIN",admin:"STAFF",stats:"FREMGANG",nickname:"Kallenavn",register:"REGISTRER",login:"LOGG INN",logout:"LOGG UT",loggedAs:"Logget inn som",via:"via",role:"Rolle",level:"Nivå",bestMass:"Beste masse",runs:"Runder",resetSettings:"TILBAKESTILL INNSTILLINGER",save:"LAGRE",load:"LAST INN",reset:"SLETT PROFIL",codeLabel:"Rollekode",redeem:"LØS INN",ach:"PRESTASJONER",backup:"SIKKERHETSKOPI",exportBtn:"EKSPORTER",importBtn:"IMPORTER" },
          fi: { account:"TILI",settings:"ASETUKSET",skin:"SKIINI",admin:"HENKILÖSTÖ",stats:"EDISTYMINEN",nickname:"Nimimerkki",register:"REKISTERÖI",login:"KIRJAUDU",logout:"KIRJAUDU ULOS",loggedAs:"Kirjautuneena käyttäjänä",via:"palvelulla",role:"Rooli",level:"Taso",bestMass:"Paras massa",runs:"Pelit",resetSettings:"PALAUTA ASETUKSET",save:"TALLENNA",load:"LATAA",reset:"TYHJENNÄ PROFIILI",codeLabel:"Roolikoodi",redeem:"LUNASTA",ach:"SAAVUTUKSET",backup:"VARMUUSKOPIO",exportBtn:"VIE",importBtn:"TUO" },
          el: { account:"ΛΟΓΑΡΙΑΣΜΟΣ",settings:"ΡΥΘΜΙΣΕΙΣ",skin:"SKIN",admin:"STAFF",stats:"ΠΡΟΟΔΟΣ",nickname:"Ψευδώνυμο",register:"ΕΓΓΡΑΦΗ",login:"ΣΥΝΔΕΣΗ",logout:"ΕΞΟΔΟΣ",loggedAs:"Συνδεδεμένος ως",via:"μέσω",role:"Ρόλος",level:"Επίπεδο",bestMass:"Καλύτερη μάζα",runs:"Παιχνίδια",resetSettings:"ΕΠΑΝΑΦΟΡΑ ΡΥΘΜΙΣΕΩΝ",save:"ΑΠΟΘΗΚΕΥΣΗ",load:"ΦΟΡΤΩΣΗ",reset:"ΔΙΑΓΡΑΦΗ ΠΡΟΦΙΛ",codeLabel:"Κωδικός ρόλου",redeem:"ΕΞΑΡΓΥΡΩΣΗ",ach:"ΕΠΙΤΕΥΓΜΑΤΑ",backup:"ΑΝΤΙΓΡΑΦΟ ΑΣΦΑΛΕΙΑΣ",exportBtn:"ΕΞΑΓΩΓΗ",importBtn:"ΕΙΣΑΓΩΓΗ" },
          cs: { account:"ÚČET",settings:"NASTAVENÍ",skin:"SKIN",admin:"TÝM",stats:"POKROK",nickname:"Přezdívka",register:"REGISTROVAT",login:"PŘIHLÁSIT",logout:"ODHLÁSIT",loggedAs:"Přihlášen jako",via:"přes",role:"Role",level:"Úroveň",bestMass:"Nejlepší hmotnost",runs:"Hry",resetSettings:"OBNOVIT NASTAVENÍ",save:"ULOŽIT",load:"NAČÍST",reset:"VYMAZAT PROFIL",codeLabel:"Kód role",redeem:"UPLATNIT",ach:"ÚSPĚCHY",backup:"ZÁLOHA",exportBtn:"EXPORT",importBtn:"IMPORT" },
          ro: { account:"CONT",settings:"SETĂRI",skin:"SKIN",admin:"STAFF",stats:"PROGRES",nickname:"Poreclă",register:"ÎNREGISTRARE",login:"CONECTARE",logout:"DECONECTARE",loggedAs:"Conectat ca",via:"prin",role:"Rol",level:"Nivel",bestMass:"Cea mai bună masă",runs:"Runde",resetSettings:"RESETARE SETĂRI",save:"SALVEAZĂ",load:"ÎNCARCĂ",reset:"ȘTERGE PROFILUL",codeLabel:"Cod rol",redeem:"REDEEM",ach:"REALIZĂRI",backup:"COPIE DE SIGURANȚĂ",exportBtn:"EXPORTĂ",importBtn:"IMPORTĂ" },
          hu: { account:"FIÓK",settings:"BEÁLLÍTÁSOK",skin:"SKIN",admin:"STAFF",stats:"HALADÁS",nickname:"Becenév",register:"REGISZTRÁCIÓ",login:"BELÉPÉS",logout:"KIJELENTKEZÉS",loggedAs:"Bejelentkezve mint",via:"ezzel",role:"Szerep",level:"Szint",bestMass:"Legjobb tömeg",runs:"Körök",resetSettings:"BEÁLLÍTÁSOK VISSZAÁLLÍTÁSA",save:"MENTÉS",load:"BETÖLTÉS",reset:"PROFIL TÖRLÉSE",codeLabel:"Szerepkód",redeem:"BEVÁLTÁS",ach:"EREDMÉNYEK",backup:"BIZTONSÁGI MENTÉS",exportBtn:"EXPORT",importBtn:"IMPORT" },
          bg: { account:"АКАУНТ",settings:"НАСТРОЙКИ",skin:"СКИН",admin:"СТАФ",stats:"ПРОГРЕС",nickname:"Псевдоним",register:"РЕГИСТРАЦИЯ",login:"ВХОД",logout:"ИЗХОД",loggedAs:"Влезли сте като",via:"чрез",role:"Роля",level:"Ниво",bestMass:"Най-добра маса",runs:"Рундове",resetSettings:"НУЛИРАНЕ НА НАСТРОЙКИТЕ",save:"ЗАПАЗИ",load:"ЗАРЕДИ",reset:"ИЗТРИЙ ПРОФИЛА",codeLabel:"Код за роля",redeem:"АКТИВИРАЙ",ach:"ПОСТИЖЕНИЯ",backup:"РЕЗЕРВНО КОПИЕ",exportBtn:"ЕКСПОРТ",importBtn:"ИМПОРТ" },
          sr: { account:"НАЛОГ",settings:"ПОДЕШАВАЊА",skin:"СКИН",admin:"СТАФ",stats:"НАПРЕДАК",nickname:"Надимак",register:"РЕГИСТРУЈ",login:"ПРИЈАВИ СЕ",logout:"ОДЈАВИ СЕ",loggedAs:"Пријављен као",via:"преко",role:"Улога",level:"Ниво",bestMass:"Најбоља маса",runs:"Рунде",resetSettings:"РЕСЕТУЈ ПОДЕШАВАЊА",save:"САЧУВАЈ",load:"УЧИТАЈ",reset:"ОБРИШИ ПРОФИЛ",codeLabel:"Код улоге",redeem:"ПРЕУЗМИ",ach:"ДОСТИГНУЋА",backup:"РЕЗЕРВНА КОПИЈА",exportBtn:"ИЗВЕЗИ",importBtn:"УВЕЗИ" },
          hr: { account:"RAČUN",settings:"POSTAVKE",skin:"SKIN",admin:"STAFF",stats:"NAPREDAK",nickname:"Nadimak",register:"REGISTRACIJA",login:"PRIJAVA",logout:"ODJAVA",loggedAs:"Prijavljen kao",via:"putem",role:"Uloga",level:"Razina",bestMass:"Najveća masa",runs:"Runde",resetSettings:"VRATI POSTAVKE",save:"SPREMI",load:"UČITAJ",reset:"OBRIŠI PROFIL",codeLabel:"Kod uloge",redeem:"ISKORISTI",ach:"POSTIGNUĆA",backup:"SIGURNOSNA KOPIJA",exportBtn:"IZVEZI",importBtn:"UVEZI" },
          sk: { account:"ÚČET",settings:"NASTAVENIA",skin:"SKIN",admin:"TÍM",stats:"POKROK",nickname:"Prezývka",register:"REGISTRÁCIA",login:"PRIHLÁSIŤ",logout:"ODHLÁSIŤ",loggedAs:"Prihlásený ako",via:"cez",role:"Rola",level:"Úroveň",bestMass:"Najlepšia hmotnosť",runs:"Hry",resetSettings:"OBNOVIŤ NASTAVENIA",save:"ULOŽIŤ",load:"NAČÍTAŤ",reset:"VYMAZAŤ PROFIL",codeLabel:"Kód roly",redeem:"UPLATNIŤ",ach:"ÚSPECHY",backup:"ZÁLOHA",exportBtn:"EXPORT",importBtn:"IMPORT" },
          sl: { account:"RAČUN",settings:"NASTAVITVE",skin:"PREOBLEKA",admin:"OSEBJE",stats:"NAPREDEK",nickname:"Vzdevek",register:"REGISTRACIJA",login:"PRIJAVA",logout:"ODJAVA",loggedAs:"Prijavljen kot",via:"prek",role:"Vloga",level:"Raven",bestMass:"Največja masa",runs:"Igre",resetSettings:"PONASTAVI NASTAVITVE",save:"SHRANI",load:"NALOŽI",reset:"IZBRIŠI PROFIL",codeLabel:"Koda vloge",redeem:"UNOVČI",ach:"DOSEŽKI",backup:"VARNOSTNA KOPIJA",exportBtn:"IZVOZI",importBtn:"UVOZI" },
          ar: { account:"الحساب",settings:"الإعدادات",skin:"المظهر",admin:"الفريق",stats:"التقدم",nickname:"الاسم المستعار",register:"تسجيل",login:"دخول",logout:"خروج",loggedAs:"تم تسجيل الدخول باسم",via:"عبر",role:"الدور",level:"المستوى",bestMass:"أفضل كتلة",runs:"الجولات",resetSettings:"إعادة الإعدادات",save:"حفظ",load:"تحميل",reset:"مسح الملف",codeLabel:"رمز الدور",redeem:"استرداد",ach:"الإنجازات",backup:"نسخة احتياطية",exportBtn:"تصدير",importBtn:"استيراد" },
          he: { account:"חשבון",settings:"הגדרות",skin:"סקין",admin:"צוות",stats:"התקדמות",nickname:"כינוי",register:"הרשמה",login:"התחברות",logout:"התנתקות",loggedAs:"מחובר בתור",via:"דרך",role:"תפקיד",level:"רמה",bestMass:"מסה מרבית",runs:"סיבובים",resetSettings:"איפוס הגדרות",save:"שמירה",load:"טעינה",reset:"מחיקת פרופיל",codeLabel:"קוד תפקיד",redeem:"מימוש",ach:"הישגים",backup:"גיבוי",exportBtn:"ייצוא",importBtn:"ייבוא" },
          fa: { account:"حساب",settings:"تنظیمات",skin:"پوسته",admin:"تیم",stats:"پیشرفت",nickname:"نام مستعار",register:"ثبت‌نام",login:"ورود",logout:"خروج",loggedAs:"واردشده با نام",via:"از طریق",role:"نقش",level:"سطح",bestMass:"بهترین جرم",runs:"دورها",resetSettings:"بازنشانی تنظیمات",save:"ذخیره",load:"بارگذاری",reset:"پاک کردن پروفایل",codeLabel:"کد نقش",redeem:"فعال‌سازی",ach:"دستاوردها",backup:"پشتیبان",exportBtn:"خروجی",importBtn:"ورودی" },
          hi: { account:"खाता",settings:"सेटिंग्स",skin:"स्किन",admin:"स्टाफ",stats:"प्रगति",nickname:"उपनाम",register:"पंजीकरण",login:"लॉग इन",logout:"लॉग आउट",loggedAs:"इस रूप में लॉग इन",via:"के माध्यम से",role:"भूमिका",level:"स्तर",bestMass:"सर्वश्रेष्ठ मास",runs:"राउंड",resetSettings:"सेटिंग्स रीसेट",save:"सहेजें",load:"लोड",reset:"प्रोफ़ाइल मिटाएँ",codeLabel:"भूमिका कोड",redeem:"रिडीम",ach:"उपलब्धियाँ",backup:"बैकअप",exportBtn:"निर्यात",importBtn:"आयात" },
          bn: { account:"অ্যাকাউন্ট",settings:"সেটিংস",skin:"স্কিন",admin:"স্টাফ",stats:"অগ্রগতি",nickname:"ডাকনাম",register:"নিবন্ধন",login:"লগ ইন",logout:"লগ আউট",loggedAs:"লগ ইন করেছেন",via:"মাধ্যমে",role:"ভূমিকা",level:"স্তর",bestMass:"সেরা ভর",runs:"রাউন্ড",resetSettings:"সেটিংস রিসেট",save:"সংরক্ষণ",load:"লোড",reset:"প্রোফাইল মুছুন",codeLabel:"ভূমিকা কোড",redeem:"রিডিম",ach:"অর্জন",backup:"ব্যাকআপ",exportBtn:"রপ্তানি",importBtn:"আমদানি" },
          ur: { account:"اکاؤنٹ",settings:"ترتیبات",skin:"اسکن",admin:"اسٹاف",stats:"پیش رفت",nickname:"عرف",register:"رجسٹر",login:"لاگ ان",logout:"لاگ آؤٹ",loggedAs:"لاگ ان بطور",via:"کے ذریعے",role:"کردار",level:"لیول",bestMass:"بہترین ماس",runs:"راؤنڈز",resetSettings:"ترتیبات ری سیٹ",save:"محفوظ",load:"لوڈ",reset:"پروفائل صاف کریں",codeLabel:"کردار کوڈ",redeem:"حاصل کریں",ach:"کامیابیاں",backup:"بیک اپ",exportBtn:"برآمد",importBtn:"درآمد" },
          vi: { account:"TÀI KHOẢN",settings:"CÀI ĐẶT",skin:"SKIN",admin:"ĐỘI NGŨ",stats:"TIẾN ĐỘ",nickname:"Biệt danh",register:"ĐĂNG KÝ",login:"ĐĂNG NHẬP",logout:"ĐĂNG XUẤT",loggedAs:"Đã đăng nhập với tên",via:"qua",role:"Vai trò",level:"Cấp độ",bestMass:"Khối lượng cao nhất",runs:"Ván chơi",resetSettings:"ĐẶT LẠI CÀI ĐẶT",save:"LƯU",load:"TẢI",reset:"XÓA HỒ SƠ",codeLabel:"Mã vai trò",redeem:"ĐỔI",ach:"THÀNH TỰU",backup:"SAO LƯU",exportBtn:"XUẤT",importBtn:"NHẬP" },
          th: { account:"บัญชี",settings:"ตั้งค่า",skin:"สกิน",admin:"ทีมงาน",stats:"ความคืบหน้า",nickname:"ชื่อเล่น",register:"สมัคร",login:"เข้าสู่ระบบ",logout:"ออกจากระบบ",loggedAs:"เข้าสู่ระบบในชื่อ",via:"ผ่าน",role:"บทบาท",level:"เลเวล",bestMass:"มวลสูงสุด",runs:"รอบ",resetSettings:"รีเซ็ตการตั้งค่า",save:"บันทึก",load:"โหลด",reset:"ล้างโปรไฟล์",codeLabel:"รหัสบทบาท",redeem:"แลก",ach:"ความสำเร็จ",backup:"สำรองข้อมูล",exportBtn:"ส่งออก",importBtn:"นำเข้า" },
          ms: { account:"AKAUN",settings:"TETAPAN",skin:"SKIN",admin:"KAKITANGAN",stats:"KEMAJUAN",nickname:"Nama samaran",register:"DAFTAR",login:"LOG MASUK",logout:"LOG KELUAR",loggedAs:"Log masuk sebagai",via:"melalui",role:"Peranan",level:"Tahap",bestMass:"Jisim terbaik",runs:"Pusingan",resetSettings:"RESET TETAPAN",save:"SIMPAN",load:"MUAT",reset:"PADAM PROFIL",codeLabel:"Kod peranan",redeem:"TEBUS",ach:"PENCAPAIAN",backup:"SANDARAN",exportBtn:"EKSPORT",importBtn:"IMPORT" },
          tl: { account:"ACCOUNT",settings:"MGA SETTING",skin:"SKIN",admin:"STAFF",stats:"PROGRESO",nickname:"Palayaw",register:"MAG-REGISTER",login:"MAG-LOGIN",logout:"LOG OUT",loggedAs:"Naka-login bilang",via:"sa pamamagitan ng",role:"Papel",level:"Antas",bestMass:"Pinakamahusay na masa",runs:"Laro",resetSettings:"I-RESET ANG SETTING",save:"I-SAVE",load:"I-LOAD",reset:"BURAHIN ANG PROFILE",codeLabel:"Code ng papel",redeem:"I-REDEEM",ach:"MGA ACHIEVEMENT",backup:"BACKUP",exportBtn:"I-EXPORT",importBtn:"I-IMPORT" },
          sw: { account:"AKAUNTI",settings:"MIPANGILIO",skin:"SKIN",admin:"TIMU",stats:"MAENDELEO",nickname:"Jina la utani",register:"JISAJILI",login:"INGIA",logout:"TOKA",loggedAs:"Umeingia kama",via:"kupitia",role:"Jukumu",level:"Kiwango",bestMass:"Misa bora",runs:"Mizunguko",resetSettings:"WEKA UPYA MIPANGILIO",save:"HIFADHI",load:"PAKIA",reset:"FUTA WASIFU",codeLabel:"Msimbo wa jukumu",redeem:"KOMBOA",ach:"MAFANIKIO",backup:"NAKALA",exportBtn:"HAMISHA",importBtn:"LETA" },
          es: { account:"CUENTA",settings:"AJUSTES",skin:"SKIN",admin:"STAFF",stats:"PROGRESO",nickname:"Apodo",register:"REGISTRARSE",login:"INICIAR SESIÓN",logout:"CERRAR SESIÓN",loggedAs:"Conectado como",via:"vía",role:"Rol",level:"Nivel",bestMass:"Masa récord",runs:"Partidas",resetSettings:"RESTABLECER AJUSTES",save:"GUARDAR",load:"CARGAR",reset:"BORRAR PERFIL",codeLabel:"Código de rol",redeem:"CANJEAR",ach:"LOGROS",backup:"COPIA DE SEGURIDAD",exportBtn:"EXPORTAR",importBtn:"IMPORTAR" },
          fr: { account:"COMPTE",settings:"PARAMÈTRES",skin:"SKIN",admin:"STAFF",stats:"PROGRÈS",nickname:"Pseudo",register:"S’INSCRIRE",login:"SE CONNECTER",logout:"SE DÉCONNECTER",loggedAs:"Connecté en tant que",via:"via",role:"Rôle",level:"Niveau",bestMass:"Masse record",runs:"Parties",resetSettings:"RÉINITIALISER",save:"ENREGISTRER",load:"CHARGER",reset:"EFFACER LE PROFIL",codeLabel:"Code de rôle",redeem:"UTILISER",ach:"SUCCÈS",backup:"SAUVEGARDE",exportBtn:"EXPORTER",importBtn:"IMPORTER" },
          de: { account:"KONTO",settings:"EINSTELLUNGEN",skin:"SKIN",admin:"STAFF",stats:"FORTSCHRITT",nickname:"Spitzname",register:"REGISTRIEREN",login:"ANMELDEN",logout:"ABMELDEN",loggedAs:"Angemeldet als",via:"über",role:"Rolle",level:"Stufe",bestMass:"Beste Masse",runs:"Runden",resetSettings:"EINSTELLUNGEN ZURÜCKSETZEN",save:"SPEICHERN",load:"LADEN",reset:"PROFIL LÖSCHEN",codeLabel:"Rollencode",redeem:"EINLÖSEN",ach:"ERFOLGE",backup:"BACKUP",exportBtn:"EXPORT",importBtn:"IMPORT" },
          pt: { account:"CONTA",settings:"DEFINIÇÕES",skin:"SKIN",admin:"STAFF",stats:"PROGRESSO",nickname:"Apelido",register:"REGISTAR",login:"ENTRAR",logout:"SAIR",loggedAs:"Sessão iniciada como",via:"via",role:"Função",level:"Nível",bestMass:"Melhor massa",runs:"Partidas",resetSettings:"REPOR DEFINIÇÕES",save:"GUARDAR",load:"CARREGAR",reset:"APAGAR PERFIL",codeLabel:"Código de função",redeem:"RESGATAR",ach:"CONQUISTAS",backup:"CÓPIA DE SEGURANÇA",exportBtn:"EXPORTAR",importBtn:"IMPORTAR" },
          ru: { account:"АККАУНТ",settings:"НАСТРОЙКИ",skin:"СКИН",admin:"СТАФФ",stats:"ПРОГРЕСС",nickname:"Имя",register:"РЕГИСТРАЦИЯ",login:"ВОЙТИ",logout:"ВЫЙТИ",loggedAs:"Вы вошли как",via:"через",role:"Роль",level:"Уровень",bestMass:"Рекордная масса",runs:"Игр",resetSettings:"СБРОСИТЬ НАСТРОЙКИ",save:"СОХРАНИТЬ",load:"ЗАГРУЗИТЬ",reset:"ОЧИСТИТЬ ПРОФИЛЬ",codeLabel:"Код роли",redeem:"АКТИВИРОВАТЬ",ach:"ДОСТИЖЕНИЯ",backup:"РЕЗЕРВНАЯ КОПИЯ",exportBtn:"ЭКСПОРТ",importBtn:"ИМПОРТ" },
          zh: { account:"账户",settings:"设置",skin:"皮肤",admin:"管理",stats:"进度",nickname:"昵称",register:"注册",login:"登录",logout:"退出",loggedAs:"当前用户",via:"通过",role:"角色",level:"等级",bestMass:"最高质量",runs:"场次",resetSettings:"重置设置",save:"保存",load:"加载",reset:"清除资料",codeLabel:"角色代码",redeem:"兑换",ach:"成就",backup:"备份",exportBtn:"导出",importBtn:"导入" },
          ja: { account:"アカウント",settings:"設定",skin:"スキン",admin:"スタッフ",stats:"進行状況",nickname:"ニックネーム",register:"登録",login:"ログイン",logout:"ログアウト",loggedAs:"ログイン中:",via:"経由",role:"役割",level:"レベル",bestMass:"最高質量",runs:"プレイ数",resetSettings:"設定をリセット",save:"保存",load:"読み込み",reset:"プロフィール削除",codeLabel:"役割コード",redeem:"交換",ach:"実績",backup:"バックアップ",exportBtn:"エクスポート",importBtn:"インポート" },
          ko: { account:"계정",settings:"설정",skin:"스킨",admin:"스태프",stats:"진행도",nickname:"닉네임",register:"가입",login:"로그인",logout:"로그아웃",loggedAs:"로그인 사용자",via:"통해",role:"역할",level:"레벨",bestMass:"최고 질량",runs:"플레이",resetSettings:"설정 초기화",save:"저장",load:"불러오기",reset:"프로필 삭제",codeLabel:"역할 코드",redeem:"사용",ach:"업적",backup:"백업",exportBtn:"내보내기",importBtn:"가져오기" },
          id: { account:"AKUN",settings:"PENGATURAN",skin:"SKIN",admin:"STAF",stats:"PROGRES",nickname:"Nama panggilan",register:"DAFTAR",login:"MASUK",logout:"KELUAR",loggedAs:"Masuk sebagai",via:"melalui",role:"Peran",level:"Level",bestMass:"Massa terbaik",runs:"Putaran",resetSettings:"RESET PENGATURAN",save:"SIMPAN",load:"MUAT",reset:"HAPUS PROFIL",codeLabel:"Kode peran",redeem:"TUKAR",ach:"PENCAPAIAN",backup:"CADANGAN",exportBtn:"EKSPOR",importBtn:"IMPOR" }
        };

        // 23 player-side settings exposed in the user menu
        const SETTING_DEFS = [
          { k: 'sfxVolume', t: 'slider', min: 0, max: 1, step: 0.05, it: 'Volume effetti', en: 'SFX volume' },
          { k: 'musicVolume', t: 'slider', min: 0, max: 1, step: 0.05, it: 'Volume musica', en: 'Music volume' },
          { k: 'musicOn', t: 'check', it: 'Musica di sottofondo', en: 'Background music' },
          { k: 'uiSounds', t: 'check', it: 'Suoni interfaccia', en: 'UI sounds' },
          { k: 'mute', t: 'check', it: 'Muto totale', en: 'Mute all' },
          { k: 'questToasts', t: 'check', it: 'Avvisi missioni', en: 'Quest alerts' },
          { k: 'showPortalBtn', t: 'check', it: 'Pulsante portale', en: 'Portal button' },
          { k: 'showCoins', t: 'check', it: 'ZeroCoin nell\'HUD', en: 'ZeroCoin in HUD' },
          { k: 'showMinimap', t: 'check', it: 'Minimappa', en: 'Minimap' },
          { k: 'showChat', t: 'check', it: 'Chat globale', en: 'Global chat' },
          { k: 'hideBotChatter', t: 'check', it: 'Silenzia i bot in chat', en: 'Silence bot chatter' },
          { k: 'showNames', t: 'check', it: 'Nomi dei rivali', en: 'Rival names' },
          { k: 'showGrid', t: 'check', it: 'Griglia di sfondo', en: 'Background grid' },
          { k: 'showTrails', t: 'check', it: 'Scia del dash', en: 'Dash trail' },
          { k: 'showThreatArrows', t: 'check', it: 'Frecce di pericolo', en: 'Threat arrows' },
          { k: 'notifications', t: 'check', it: 'Notifiche a schermo', en: 'On-screen notifications' },
          { k: 'vignette', t: 'check', it: 'Vignetta di morte', en: 'Death vignette' },
          { k: 'showFps', t: 'check', it: 'Contatore FPS', en: 'FPS counter' },
          { k: 'autoRespawn', t: 'check', it: 'Respawn automatico', en: 'Auto respawn' },
          { k: 'invertKeys', t: 'check', it: 'Inverti tasti direzione', en: 'Invert key steering' },
          { k: 'screenShake', t: 'slider', min: 0, max: 1.5, step: 0.1, it: 'Scuotimento schermo', en: 'Screen shake' },
          { k: 'hudScale', t: 'slider', min: 0.8, max: 1.3, step: 0.05, it: 'Scala HUD', en: 'HUD scale' },
          { k: 'cameraSmooth', t: 'slider', min: 1.5, max: 9, step: 0.5, it: 'Fluidità camera', en: 'Camera smoothing' },
          { k: 'zoomSens', t: 'slider', min: 0.5, max: 2, step: 0.1, it: 'Sensibilità zoom', en: 'Zoom sensitivity' },
          { k: 'skinGlow', t: 'slider', min: 0, max: 1.5, step: 0.1, it: 'Intensità bagliore', en: 'Glow intensity' },
          {
            k: 'particleQuality', t: 'select', it: 'Qualità particelle', en: 'Particle quality',
            opts: [{ v: 'low', l: 'Low' }, { v: 'med', l: 'Medium' }, { v: 'high', l: 'High' }],
          },
          {
            k: 'colorblind', t: 'select', it: 'Modalità daltonici', en: 'Colorblind mode',
            opts: [{ v: 'off', l: 'Off' }, { v: 'deuter', l: 'Deuteranopia' }, { v: 'proto', l: 'Protanopia' }, { v: 'mono', l: 'Mono' }],
          },
          {
            k: 'language', t: 'select', it: 'Lingua menu', en: 'Menu language',
            opts: [
              { v: 'it', l: 'Italiano' }, { v: 'en', l: 'English' }, { v: 'es', l: 'Español' },
              { v: 'fr', l: 'Français' }, { v: 'de', l: 'Deutsch' }, { v: 'pt', l: 'Português' },
              { v: 'ru', l: 'Русский' }, { v: 'uk', l: 'Українська' }, { v: 'pl', l: 'Polski' },
              { v: 'tr', l: 'Türkçe' }, { v: 'nl', l: 'Nederlands' }, { v: 'sv', l: 'Svenska' },
              { v: 'da', l: 'Dansk' }, { v: 'no', l: 'Norsk' }, { v: 'fi', l: 'Suomi' },
              { v: 'el', l: 'Ελληνικά' }, { v: 'cs', l: 'Čeština' }, { v: 'ro', l: 'Română' },
              { v: 'hu', l: 'Magyar' }, { v: 'bg', l: 'Български' }, { v: 'sr', l: 'Srpski' },
              { v: 'hr', l: 'Hrvatski' }, { v: 'sk', l: 'Slovenčina' }, { v: 'sl', l: 'Slovenščina' },
              { v: 'ar', l: 'العربية' }, { v: 'he', l: 'עברית' }, { v: 'fa', l: 'فارسی' },
              { v: 'hi', l: 'हिन्दी' }, { v: 'bn', l: 'বাংলা' }, { v: 'ur', l: 'اردو' },
              { v: 'zh', l: '中文' }, { v: 'ja', l: '日本語' }, { v: 'ko', l: '한국어' },
              { v: 'vi', l: 'Tiếng Việt' }, { v: 'th', l: 'ไทย' }, { v: 'id', l: 'Bahasa Indonesia' },
              { v: 'ms', l: 'Melayu' }, { v: 'tl', l: 'Filipino' }, { v: 'sw', l: 'Kiswahili' },
            ],
          },
          {
            k: 'musicTrack', t: 'select', it: 'Traccia musicale', en: 'Music track',
            opts: [
              { v: 'auto', l: 'Auto DJ' }, { v: 'bgm_menu', l: 'Neon Lobby' },
              { v: 'bgm_arena', l: 'Arena Rush' }, { v: 'bgm_bonus', l: 'Bonus Funk' },
              { v: 'proc', l: 'Deep Space (live)' },
            ],
          },
          {
            k: 'theme', t: 'select', it: 'Tema portale', en: 'Portal theme',
            opts: [
              { v: 'neon', l: 'Neon Cyan' }, { v: 'sunset', l: 'Sunset' }, { v: 'toxic', l: 'Toxic' },
              { v: 'royal', l: 'Royal' }, { v: 'mono', l: 'Mono' },
            ],
          },
          { k: 'threeDGraphics', t: 'check', it: 'Grafica 3D', en: '3D graphics' },
          { k: 'legendaryFx', t: 'check', it: 'Effetti leggendari', en: 'Legendary effects' },
          { k: 'portalDepth', t: 'check', it: 'Profondità portale', en: 'Portal depth' },
          { k: 'botFollowKey', t: 'text', it: 'Tasto bot segui', en: 'Bot follow key' },
          { k: 'botFarmKey', t: 'text', it: 'Tasto bot farm', en: 'Bot farm key' },
          { k: 'botStopKey', t: 'text', it: 'Tasto bot stop', en: 'Bot stop key' },
        ];

        function defaultSettings() {
          return {
            sfxVolume: 0.8, musicVolume: 0.45, mute: false,
            musicOn: true, uiSounds: true, musicTrack: 'auto', theme: 'neon',
            questToasts: true, showPortalBtn: true, showCoins: true,
            showMinimap: true, showChat: true, hideBotChatter: false,
            showNames: true, showGrid: true, showTrails: true, showThreatArrows: true,
            notifications: true, vignette: true, showFps: false, autoRespawn: false,
            invertKeys: false, screenShake: 1, hudScale: 1, cameraSmooth: 4.5,
            zoomSens: 1, skinGlow: 1, particleQuality: 'high', colorblind: 'off', 
            language: detectLanguage(), languageAuto: true,
            threeDGraphics: true, legendaryFx: true, portalDepth: true,
            botFollowKey: 'F7', botFarmKey: 'F8', botStopKey: 'F9',
          };
        }

        function defaultProfile() {
          return {
            v: 1,
            name: 'Guest',
            pin: '',
            provider: 'local',
            role: 'user',
            loggedIn: false,
            xp: 0,
            level: 1,
            bestMass: 0,
            runs: 0,
            totalParticles: 0,
            totalRivals: 0,
            totalTime: 0,
            achievements: {},
            skin: null,
            skinKind: null,
            // Zero Arcade portal: virtual economy + inventory (player-specific)
            coins: 500,
            inventory: {},
            equippedSkin: null,
            txs: [],
            promos: {},
            history: [],
            crash: { plays: 0, wins: 0, best: 0, profit: 0 },
            lastDaily: 0,
            lastAd: 0,
            dailyStreak: 0,
            // Quests, Zero Pass, social & mini-game progress
            quests: { date: '', items: [] },
            pass: { xp: 0, tier: 0, claimed: {} },
            favs: {},
            recent: [],
            friends: [],
            inbox: [],
            minis: {},
            legendFeatures: {},
            legendDaily: { date: '', score: 0 },
            tutorial: { current: 0, completed: {}, watched: 0 },
            posts: [],
            socialLikes: {},
            socialComments: {},
            // ZeroSocial hub: local features inspired by feeds, stories, video, chat and professional networks
            socialHub: {
              view: 'home',
              service: 'all',
              friendRequests: [],
              following: { zeroarcade: true },
              groups: [],
              pages: [],
              channels: [],
              messages: [],
              stories: [],
              reels: [],
              xPosts: [],
              nexusIntroduced: false,
              nexus: { intent: 'discover', pulse: 0, totalResonance: 0, resonance: {}, created: [] },
              saved: {},
              professional: { headline: 'Neon arena player', connections: 24, posts: [], jobs: [] },
              // Zero Omni Hub: local, offline-first media and social controls.
              media: { mode: 'overview', currentTrack: 'auto', queue: [], audioLikes: {}, audioReposts: {}, watchlist: {}, bookmarks: {}, toolUsage: {}, mixMode: false, privacyLocal: true },
              events: [],
              polls: [],
              portalNotices: [],
              supportMessages: []
            },
            refCode: '',
            refUsed: false,
            refCount: 0,
            bestReaction: 0,
            lastWheel: 0,
            settings: defaultSettings(),
          };
        }

        let profile = defaultProfile();
        profile.name = window.__ZA_AGAR_USER.username || 'Player';
        profile.loggedIn = true;
        profile.provider = 'zeroarcade';
        profile.role = window.__ZA_AGAR_USER.role || 'user';
        profile.coins = Number(window.__ZA_AGAR_USER.coins || 0);
        profile.xp = Number(window.__ZA_AGAR_USER.xp || 0);
        profile.level = Number(window.__ZA_AGAR_USER.level || 1);
        profile.clan = window.__ZA_AGAR_USER.clan || 'ZERO';
        let S = profile.settings;

        function T(key) {
          const lang = portalLanguage();
          // Use the requested locale first, then its extended player dictionary,
          // and finally English. Never silently fall back to Italian: that made
          // unsupported locales look as if the language picker had failed.
          const dict = I18N[lang] || PLAYER_LOCALE_EXTRA[lang] || I18N.en;
          if (dict && dict[key] !== undefined) return dict[key];
          if (PLAYER_LOCALE_EXTRA[lang] && PLAYER_LOCALE_EXTRA[lang][key] !== undefined) {
            return PLAYER_LOCALE_EXTRA[lang][key];
          }
          return I18N.en[key] !== undefined ? I18N.en[key] : key;
        }

        // --- Roles & permissions -------------------------------------
        function roleRank(role) {
          const idx = ROLE_ORDER.indexOf(role || 'user');
          return idx < 0 ? 0 : idx;
        }
        function hasPerm(minRole) {
          return roleRank(profile.role) >= roleRank(minRole);
        }
        function roleLabel() {
          return (profile.role || 'user').toUpperCase();
        }
        function xpRoleMult() {
          if (hasPerm('admin')) return 1.5;
          if (hasPerm('mod')) return 1.3;
          if (hasPerm('helper')) return 1.2;
          if (hasPerm('vip')) return 1.1;
          return 1;
        }
        function roleCooldownMult() {
          if (hasPerm('admin')) return 0.5;
          if (hasPerm('mod')) return 0.7;
          if (hasPerm('helper')) return 0.8;
          if (hasPerm('vip')) return 0.9;
          return 1;
        }
        // --- ZeroCoin wallet + shop bonuses ---------------------------
        function normalizeProfileData() {
          if (!profile.inventory || typeof profile.inventory !== 'object') profile.inventory = {};
          if (!profile.promos || typeof profile.promos !== 'object') profile.promos = {};
          if (!profile.crash || typeof profile.crash !== 'object') profile.crash = { plays: 0, wins: 0, best: 0, profit: 0 };
          if (!Array.isArray(profile.txs)) profile.txs = [];
          if (!Array.isArray(profile.history)) profile.history = [];
          if (typeof profile.coins !== 'number' || !isFinite(profile.coins)) profile.coins = 500;
          if (!profile.pass || typeof profile.pass !== 'object') profile.pass = { xp: 0, tier: 0, claimed: {} };
          if (!profile.pass.claimed || typeof profile.pass.claimed !== 'object') profile.pass.claimed = {};
          if (!profile.favs || typeof profile.favs !== 'object') profile.favs = {};
          if (!profile.minis || typeof profile.minis !== 'object') profile.minis = {};
          if (!profile.legendFeatures || typeof profile.legendFeatures !== 'object') profile.legendFeatures = {};
          if (!profile.legendDaily || typeof profile.legendDaily !== 'object') profile.legendDaily = { date: '', score: 0 };
          if (!profile.tutorial || typeof profile.tutorial !== 'object') profile.tutorial = { current: 0, completed: {}, watched: 0 };
          if (!profile.tutorial.completed || typeof profile.tutorial.completed !== 'object') profile.tutorial.completed = {};
          if (typeof profile.tutorial.current !== 'number') profile.tutorial.current = 0;
          if (typeof profile.tutorial.watched !== 'number') profile.tutorial.watched = 0;
          if (!Array.isArray(profile.recent)) profile.recent = [];
          if (!Array.isArray(profile.friends)) profile.friends = [];
          if (!Array.isArray(profile.inbox)) profile.inbox = [];
          if (!Array.isArray(profile.posts)) profile.posts = [];
          if (!profile.socialLikes || typeof profile.socialLikes !== 'object') profile.socialLikes = {};
          if (!profile.socialComments || typeof profile.socialComments !== 'object') profile.socialComments = {};
          ensureSocialHub();
          ensureQuests();
          ensureSocial();
        }
        function owns(id) {
          if (!profile.inventory || typeof profile.inventory !== 'object') profile.inventory = {};
          return (profile.inventory[id] || 0) > 0;
        }
        function itemCount(id) {
          if (!profile.inventory || typeof profile.inventory !== 'object') profile.inventory = {};
          return profile.inventory[id] || 0;
        }
        function fmt(n) {
          return String(Math.round(Number(n) || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }
        function logTx(amount, reason) {
          if (!Array.isArray(profile.txs)) profile.txs = [];
          profile.txs.unshift({ a: Math.round(amount), r: String(reason || ''), d: Date.now() });
          if (profile.txs.length > 24) profile.txs.length = 24;
        }
        function addCoins(amount, reason) {
          profile.coins = Math.max(0, Math.round((profile.coins || 0) + amount));
          logTx(amount, reason);
          if (amount > 0) questProgress('earn', amount);
          updateWalletUi();
          queueSave();
        }
        function spendCoins(amount, reason) {
          const cost = Math.max(0, Math.round(amount));
          if ((profile.coins || 0) < cost) {
            notify('\u26A0 ZeroCoin insufficienti', '#ff5c7a');
            return false;
          }
          profile.coins -= cost;
          logTx(-cost, reason);
          updateWalletUi();
          queueSave();
          return true;
        }
        function shopStartMassMult() { return owns('mass_boost') ? 1.25 : 1; }
        function shopDashCdMult() { return owns('dash_chip') ? 0.75 : 1; }
        function shopCoinMult() { return (owns('coin_mult') ? 1.5 : 1) * (hasPerm('vip') ? 1.25 : 1); }
        function shopXpMult() { return owns('xp_boost') ? 1.25 : 1; }
        function shopMagnet() { return owns('magnet_core'); }
        function shopShieldStart() { return owns('shield_start'); }

        function grantRole(role) {
          profile.role = role;
          notify('\u2726 ' + T('role') + ': ' + role.toUpperCase(), '#ffe600');
          addChatMsg('', 'Ruolo aggiornato: ' + role.toUpperCase(), 'sys');
          updateProfileChip();
          renderUserMenu();
          queueSave();
        }
        function redeemCode(code) {
          const r = ROLE_CODES[String(code || '').trim().toUpperCase()];
          if (!r) {
            notify('\u26A0 Codice non valido', '#ff5c7a');
            return false;
          }
          grantRole(r);
          return true;
        }

        // --- Account (local profile, no external OAuth possible) -----
        function sanitizeName(raw) {
          // Preserve Unicode letters, emoji and common symbols while removing
          // controls and HTML delimiters from player-entered nicknames.
          return String(raw || '')
            .replace(/[\u0000-\u001F\u007F<>]/gu, '')
            .normalize('NFC')
            .trim()
            .slice(0, 24);
        }
        function changeNickname(raw) {
          if (!profile.loggedIn) {
            notify('Accedi prima di cambiare nickname', '#ff5c7a');
            return false;
          }
          const n = sanitizeName(raw);
          if (n.length < 2) {
            notify('Nickname troppo corto', '#ff5c7a');
            return false;
          }
          profile.name = n;
          updateProfileChip();
          notify('Nickname aggiornato: ' + n, '#00ffa2');
          queueSave();
          renderUserMenu();
          return true;
        }
        function randomTag() {
          return String(1000 + Math.floor(Math.random() * 9000));
        }
        function registerAccount(name, pin) {
          const n = sanitizeName(name);
          if (n.length < 2) { notify('\u26A0 Nickname troppo corto', '#ff5c7a'); return; }
          profile.name = n;
          profile.pin = String(pin || '').slice(0, 6);
          profile.provider = 'local';
          profile.loggedIn = true;
          if (!profile.role) profile.role = 'user';
          notify('\u2714 Registrato: ' + n, '#00ffa2');
          addChatMsg('', n + ' si è registrato nell\'arena.', 'sys');
          unlock('account', 'Profilo creato!');
          finishAuth();
        }
        function loginAccount(name, pin) {
          const n = sanitizeName(name);
          if (profile.name && profile.name !== 'Guest' && profile.pin) {
            if (n.toLowerCase() !== profile.name.toLowerCase() || String(pin || '') !== profile.pin) {
              notify('\u26A0 Nickname o PIN errati', '#ff5c7a');
              return;
            }
            profile.loggedIn = true;
            notify('\u2714 Bentornato ' + profile.name, '#00ffa2');
            finishAuth();
            return;
          }
          registerAccount(name, pin);
        }
        function logoutAccount() {
          profile.loggedIn = false;
          notify('Disconnesso', '#7fd4ff');
          finishAuth();
        }
        function linkProvider(id) {
          const p = PROVIDERS.find((x) => x.id === id);
          if (!p) return;
          const typed = document.getElementById('acc-name');
          const base = typed && typed.value ? sanitizeName(typed.value) : '';
          profile.name = base || p.label + randomTag();
          profile.provider = p.id;
          profile.loggedIn = true;
          notify('\u2714 ' + p.label + ' collegato', p.color);
          addChatMsg('', profile.name + ' è entrato con ' + p.label + '.', 'sys');
          unlock('social', 'Profilo social collegato!');
          finishAuth();
        }
        function finishAuth() {
          updateProfileChip();
          renderUserMenu();
          queueSave();
        }

        // --- Profile XP / progress ----------------------------------
        function profileLevelFromXp(x) {
          return 1 + Math.floor(Math.sqrt(Math.max(0, x) / 120));
        }
        function addProfileXp(amount) {
          if (!(amount > 0)) return;
          profile.xp += amount * xpRoleMult();
          const lv = profileLevelFromXp(profile.xp);
          if (lv > profile.level) {
            profile.level = lv;
            notify('\u2B50 PROFILO LIVELLO ' + lv, '#ffe600');
          }
          profile.level = lv;
        }
        function recordRunToProfile(finalMass) {
          profile.runs += 1;
          profile.bestMass = Math.max(profile.bestMass || 0, finalMass);
          profile.totalParticles += stats.particles;
          profile.totalRivals += stats.rivals;
          profile.totalTime += Math.round(survivalTime);
          addProfileXp(finalMass * 0.5 + survivalTime * 2);
          for (const k in achievements) profile.achievements[k] = true;
          queueSave();
        }
        function queueSave() {
          if (saveTimer) clearTimeout(saveTimer);
          saveTimer = setTimeout(() => { saveTimer = null; saveProfile(); }, 1200);
        }

        // --- Persistence implementation (wired to host save/load) ------------
        async function saveProfile() {
          if (window.__growthOrbitStandalone || typeof lib.saveUserGameState !== 'function') return;
          try {
            const toSave = Object.assign({}, profile);
            // Guard against huge skin data URLs that break persistence
            if (toSave.skin && typeof toSave.skin === 'string' && toSave.skin.length > 200000) {
              toSave.skin = null;
              toSave.skinKind = null;
            }
            await lib.saveUserGameState(toSave);
          } catch (error) {
            lib.log(`Failed to save profile: ${error.message}`);
          }
        }

        async function loadProfile() {
          if (window.__growthOrbitStandalone || typeof lib.getUserGameState !== 'function') return;
          try {
            const response = await lib.getUserGameState();
            const raw = response && response.state !== undefined ? response.state : response;
            if (raw && typeof raw === 'object' && (raw.settings || raw.name)) {
              const loaded = raw;
              // Merge loaded profile over defaults, preserving new fields
              profile = Object.assign(defaultProfile(), loaded);
              // Merge loaded settings over default settings to preserve new settings
              profile.settings = Object.assign(defaultSettings(), loaded.settings || {});
              S = profile.settings;
              
              // Fulfill "auto translate" request: if language wasn't manually set, re-detect
              if (S.languageAuto !== false && !languageOverride) {
                const auto = detectLanguage();
                if (auto !== S.language) {
                  S.language = auto;
                  applySettings();
                }
              }
            }
          } catch (error) {
            lib.log(`Failed to load profile: ${error.message}`);
          }
        }
        async function wipeProfile() {
          if (!window.__growthOrbitStandalone && typeof lib.deleteUserGameState === 'function') {
            try {
              await lib.deleteUserGameState();
            } catch (error) {
              lib.log(`Failed to delete saved profile: ${error.message}`);
            }
          }
          profile = defaultProfile();
          S = profile.settings;
          clearSkin();
          applySettings();
          updateProfileChip();
          renderUserMenu();
          notify('Profilo azzerato', '#ff5c7a');
          queueSave();
        }

        function exportProfileCode() {
          try {
            const slim = Object.assign({}, profile);
            if (slim.skin && slim.skin.length > 4000) slim.skin = null;
            return btoa(unescape(encodeURIComponent(JSON.stringify(slim))));
          } catch (e) { return ''; }
        }
        function importProfileCode(code) {
          try {
            const obj = JSON.parse(decodeURIComponent(escape(atob(String(code).trim()))));
            if (!obj || typeof obj !== 'object') throw new Error('bad');
            profile = Object.assign(defaultProfile(), obj);
            profile.settings = Object.assign(defaultSettings(), obj.settings || {});
            S = profile.settings;
            normalizeProfileData();
            applySkinDataUrl(profile.skin, profile.skinKind);
            applySettings();
            updateProfileChip();
            updateWalletUi();
            renderUserMenu();
            notify('\u2714 Profilo importato', '#00ffa2');
            queueSave();
          } catch (e) {
            notify('\u26A0 Codice backup non valido', '#ff5c7a');
          }
        }

        // --- Custom skins (images + animated GIF) -------------------
        function applySkinDataUrl(url, kind) {
          profile.skin = url || null;
          profile.skinKind = url ? (kind || 'image') : null;
          if (skinImgEl && skinImgEl.parentNode) skinImgEl.parentNode.removeChild(skinImgEl);
          skinImgEl = null;
          skinImg = null;
          if (!url) return;
          const img = new Image();
          img.crossOrigin = 'anonymous';
          img.onload = () => { skinImg = img; };
          img.onerror = () => { skinImg = null; notify('\u26A0 Skin non caricabile', '#ff5c7a'); };
          // Kept in the DOM (1px, invisible) so animated GIFs keep ticking and
          // drawImage picks up the current frame each render.
          img.style.cssText = 'position:absolute;left:-20px;top:-20px;width:2px;height:2px;opacity:0.01;pointer-events:none;';
          gameWorld.appendChild(img);
          skinImgEl = img;
          img.src = url;
        }
        function uploadSkinFile(file) {
          if (!file) return;
          if (!/^image\//.test(file.type || '') && !/\.(png|jpe?g|gif|webp|svg|bmp|avif|apng)$/i.test(file.name || '')) {
            notify('\u26A0 Formato non supportato', '#ff5c7a');
            return;
          }
          if (file.size > 3.5 * 1024 * 1024) {
            notify('\u26A0 File troppo grande (max 3.5MB)', '#ff5c7a');
            return;
          }
          const reader = new FileReader();
          reader.onload = () => {
            applySkinDataUrl(String(reader.result), /gif/i.test(file.type) ? 'gif' : 'image');
            notify('\u2714 Skin applicata', '#00ffa2');
            unlock('skin', 'Skin personalizzata caricata!');
            renderSkinPane();
            updateProfileChip();
            queueSave();
          };
          reader.onerror = () => notify('\u26A0 Lettura file fallita', '#ff5c7a');
          reader.readAsDataURL(file);
        }
        function clearSkin() {
          applySkinDataUrl(null, null);
          renderSkinPane();
          updateProfileChip();
          queueSave();
        }
        function applyPresetSkin(color) {
          playerColorOverride = color;
          profile.skinColor = color;
          clearSkin();
          notify('Colore cella aggiornato', color);
        }

        // --- Settings application -----------------------------------
        function detectLanguage() {
          try {
            const supported = ['it', 'en', 'es', 'fr', 'de', 'pt', 'ru', 'uk', 'pl', 'tr', 'nl', 'sv', 'da', 'no', 'fi', 'el', 'cs', 'ro', 'hu', 'bg', 'sr', 'hr', 'sk', 'sl', 'ar', 'he', 'fa', 'hi', 'bn', 'ur', 'zh', 'ja', 'ko', 'vi', 'th', 'id', 'ms', 'tl', 'sw'];
            const candidates = Array.isArray(navigator.languages) && navigator.languages.length
              ? navigator.languages
              : [navigator.language || 'en'];
            for (const candidate of candidates) {
              const normalized = String(candidate || '').toLowerCase().replace('_', '-');
              const base = normalized.split('-')[0];
              if (supported.includes(base)) return base;
            }
            return 'en';
          } catch (e) { return 'en'; }
        }

        // Declared before any initialization path can call applySettings/resetGame.
        // The language gate may update this later, but it must never be in the TDZ.
        let languageOverride = null;
        // Initialized before resetGame/applySettings can call portalLanguage.
        // The full catalog is assigned later when the portal definitions load.
        let WORLD_LANGUAGES = [];
        let portalOpen = false;
        function setSetting(key, value) {
          S[key] = value;
          if (key === 'language') S.languageAuto = false;
          applySettings();
          queueSave();
        }
        function applySettings() {
          muted = !!S.mute;
          showFps = !!S.showFps;
          setAmbientVolume();
          applyMusicVolume();
          updateMusicContext();
          applyTheme();
          const activeLanguage = languageOverride || (S && S.language) || 'en';
          document.documentElement.lang = activeLanguage;
          document.documentElement.dir = ['ar', 'fa', 'he', 'ur'].indexOf(activeLanguage) >= 0 ? 'rtl' : 'ltr';
          legendApplyVisualMode();
          if (hudEl) hudEl.style.transform = 'scale(' + (S.hudScale || 1) + ')';
          const showPlayUi = mode === 'play';
          if (minimapWrap) minimapWrap.style.display = showPlayUi && cfg.ui.minimap && S.showMinimap ? 'block' : 'none';
          if (chatPanel) chatPanel.style.display = showPlayUi && cfg.ui.chat && S.showChat ? 'flex' : 'none';
          if (profileChipEl) profileChipEl.style.display = showPlayUi ? 'flex' : 'none';
          if (notifFeedEl) notifFeedEl.style.display = S.notifications ? 'flex' : 'none';
          const pBtn = document.getElementById('portal-btn');
          if (pBtn) pBtn.style.display = showPlayUi && S.showPortalBtn !== false ? 'block' : 'none';
          const cPill = document.getElementById('pill-coins');
          if (cPill) cPill.classList.toggle('hidden', !showPlayUi || S.showCoins === false);
          
          // Re-render UI components that use translated strings
          if (portalOpen) {
              const prevPage = ptPage;
              renderPortal();
              if (prevPage !== ptPage) setPtPage(prevPage);
          }
          if (userMenuOpen) renderUserMenu();
        }
        function dangerColor() {
          switch (S.colorblind) {
            case 'deuter': return '#ffb400';
            case 'proto': return '#00c8ff';
            case 'mono': return '#ffffff';
            default: return '#ff3c5a';
          }
        }
        function sfxVol() {
          return S.mute ? 0 : (S.sfxVolume !== undefined ? S.sfxVolume : 0.8);
        }
        function ensureAmbientHum() {
          if (!audioCtx || humNodes) return;
          try {
            const g = audioCtx.createGain();
            g.gain.value = 0;
            const o1 = audioCtx.createOscillator();
            o1.type = 'sine';
            o1.frequency.value = 58;
            const o2 = audioCtx.createOscillator();
            o2.type = 'triangle';
            o2.frequency.value = 87;
            const lfo = audioCtx.createOscillator();
            lfo.frequency.value = 0.07;
            const lfoGain = audioCtx.createGain();
            lfoGain.gain.value = 7;
            lfo.connect(lfoGain);
            lfoGain.connect(o2.frequency);
            o1.connect(g);
            o2.connect(g);
            g.connect(audioCtx.destination);
            o1.start();
            o2.start();
            lfo.start();
            humNodes = { g: g };
            setAmbientVolume();
          } catch (e) {}
        }
        function setAmbientVolume() {
          if (!humNodes || !audioCtx) return;
          const v = (S.mute || musicNow) ? 0 : (S.musicVolume || 0) * 0.06;
          try { humNodes.g.gain.setTargetAtTime(v, audioCtx.currentTime, 0.4); } catch (e) {}
        }

        // --- Staff / admin tools ------------------------------------
        function adminGrantCoins(value) {
          if (!hasPerm('admin')) return;
          const amount = Math.max(1, Math.min(1000000000, Math.round(Number(value) || 0)));
          addCoins(amount, 'Accredito admin al profilo corrente');
          notify('\u{1FA99} +' + fmt(amount) + ' ZC assegnati a ' + (profile.name || 'Ospite'), '#ffc94a');
        }
        function adminGrantLevels(value) {
          if (!hasPerm('admin')) return;
          const amount = Math.max(1, Math.min(1000, Math.round(Number(value) || 0)));
          const current = Math.max(1, profileLevelFromXp(profile.xp || 0));
          const target = current + amount;
          profile.xp = Math.max(profile.xp || 0, Math.pow(target - 1, 2) * 120);
          profile.level = profileLevelFromXp(profile.xp);
          updateProfileChip();
          notify('\u2B50 +' + amount + ' livelli assegnati a ' + (profile.name || 'Ospite'), '#ffe600');
          queueSave();
        }
        function adminSetBotMode(modeName) {
          if (!hasPerm('admin')) return;
          botCommandMode = modeName === 'follow' || modeName === 'farm' ? modeName : 'idle';
          notify(botCommandMode === 'idle' ? 'Bot fermati' : 'Bot: ' + botCommandMode.toUpperCase(), '#b388ff');
        }
        function adminSpawnBots(value) {
          if (!hasPerm('admin')) return;
          const requested = Math.max(1, Math.min(100, Math.round(Number(value) || 1)));
          const count = Math.min(requested, Math.max(0, 100 - aiCells.length));
          if (count <= 0) {
            notify('Arena piena: massimo 100 bot nella stessa partita', '#ff5c7a');
            return;
          }
          for (let i = 0; i < count; i++) aiCells.push(spawnAICell({ mass: Math.max(12, player.mass * (0.18 + Math.random() * 0.08)), commanded: true }));
          notify('+' + count + ' bot generati · ' + aiCells.length + '/100 nell’arena', '#b388ff');
        }
        function adminSetMass(value) {
          if (!hasPerm('admin') || !playerCells.length) return;
          const v = Math.max(10, Math.min(Number(cfg.mechanics.maxMass) || 600000, Number(value) || 0));
          playerCells[0].mass = v;
          for (let i = 1; i < playerCells.length; i++) playerCells[i].mass = v * 0.4;
          syncPlayerAggregate();
          notify('\u2699 Massa impostata a ' + Math.round(v), '#ffe600');
        }
        function adminSpawnFood(n) {
          if (!hasPerm('mod')) return;
          const count = Math.max(1, Math.min(120, Number(n) || 25));
          for (let i = 0; i < count; i++) particles.push(spawnParticle());
          notify('\u2699 +' + count + ' particelle', '#00ffa2');
        }
        function adminKickRival(name) {
          if (!hasPerm('mod')) return;
          const target = String(name || '').toLowerCase();
          for (let i = aiCells.length - 1; i >= 0; i--) {
            if (!target || aiCells[i].name.toLowerCase() === target) {
              const gone = aiCells[i].name;
              aiCells.splice(i, 1);
              addChatMsg('', gone + ' è stato espulso da uno staffer.', 'sys');
              notify('\u26D4 ' + gone + ' espulso', '#ff5c7a');
              return;
            }
          }
          notify('\u26A0 Rivale non trovato', '#ff5c7a');
        }
        function adminToggleFreeze() {
          if (!hasPerm('mod')) return;
          aiFrozen = !aiFrozen;
          notify(aiFrozen ? '\u2744 Arena congelata' : 'Arena riattivata', '#8ff9ff');
        }
        function adminTeleportCenter() {
          if (!hasPerm('admin')) return;
          for (const c of playerCells) { c.x = 0; c.y = 0; c.vx = 0; c.vy = 0; }
          syncPlayerAggregate();
          notify('\u2699 Teletrasporto al centro', '#b388ff');
        }
        function adminToggleGodLock() {
          if (!hasPerm('admin')) return;
          adminGodLock = !adminGodLock;
          if (adminGodLock) {
            godMode.active = true;
            godMode.timer = cfg.abilities.godDuration;
          }
          notify(adminGodLock ? '\u2726 God mode infinito ON' : 'God mode infinito OFF', '#ffb43c');
        }
        function adminClearViruses() {
          if (!hasPerm('mod')) return;
          viruses = [];
          notify('\u2699 Virus rimossi', '#33ff66');
        }
        function adminMaxPowerups() {
          if (!hasPerm('helper')) return;
          for (const t of POWERUP_TYPES) effects[t.id] = t.dur;
          notify('\u25C8 Tutti i power-up attivi', '#ffe600');
        }
        function helperHint() {
          if (!aiCells.length) { notify('Nessun rivale in vista', '#7fd4ff'); return; }
          let big = aiCells[0];
          for (const a of aiCells) if (a.mass > big.mass) big = a;
          const dir = Math.atan2(big.y - player.y, big.x - player.x) * 180 / Math.PI;
          notify('\u2139 Minaccia: ' + big.name + ' (' + Math.round(big.mass) + ') a ' + Math.round(dir) + '\u00B0', '#00bfa0');
        }
        function clearChatLog() {
          if (chatLogEl) chatLogEl.innerHTML = '';
          addChatMsg('', 'Chat pulita.', 'sys');
        }

        // --- Chat slash commands ------------------------------------
        function handleChatCommand(raw) {
          const parts = raw.slice(1).trim().split(/\s+/);
          const cmd = (parts[0] || '').toLowerCase();
          const arg = parts.slice(1).join(' ');
          switch (cmd) {
            case 'help':
              addChatMsg('', '/help /stats /roles /role <codice> /hint /clear /save /skin clear /coins /shop /portal /arcade /quests /pass /music /track /mass <n> /givecoins <n> /givelv <n> /bots <n> /botfollow /botfarm /botstop /spawn <n> /kick <nome> /freeze /god /tp /virus /power', 'sys');
              break;
            case 'coins':
              addChatMsg('', 'Wallet: ' + fmt(profile.coins || 0) + ' ZC \u00B7 Zero Pass tier ' + passTier(), 'sys');
              break;
            case 'shop':
              openPortal('shop');
              break;
            case 'portal':
              openPortal('home');
              break;
            case 'arcade':
              openPortal('arcade');
              break;
            case 'quests': {
              ensureQuests();
              const qi = (profile.quests && profile.quests.items) || [];
              addChatMsg('', qi.map((q) => q.label + ' (' + Math.floor(q.prog || 0) + '/' + q.goal + ')').join(' \u00B7 '), 'sys');
              break;
            }
            case 'pass':
              addChatMsg('', 'Zero Pass: tier ' + passTier() + '/' + PASS_MAX + ' \u00B7 ' + Math.round(profile.pass.xp || 0) + ' XP', 'sys');
              break;
            case 'music':
              toggleMusic();
              addChatMsg('', 'Musica ' + (S.musicOn ? 'attiva' : 'disattivata') + ' \u2014 ' + currentTrackName(), 'sys');
              break;
            case 'track':
              nextMusicTrack();
              addChatMsg('', 'Traccia: ' + currentTrackName(), 'sys');
              break;
            case 'stats':
              addChatMsg('', 'LV ' + profile.level + ' · record ' + Math.round(profile.bestMass) + ' · partite ' + profile.runs + ' · ruolo ' + roleLabel(), 'sys');
              break;
            case 'roles':
              addChatMsg('', 'Ruoli: USER < VIP < HELPER < MOD < ADMIN', 'sys');
              break;
            case 'role':
              redeemCode(arg);
              break;
            case 'hint':
              if (hasPerm('helper')) helperHint();
              else addChatMsg('', 'Serve il ruolo HELPER.', 'sys');
              break;
            case 'clear':
              clearChatLog();
              break;
            case 'save':
              saveProfile();
              addChatMsg('', 'Progressi salvati.', 'sys');
              break;
            case 'skin':
              if (/clear|rimuovi/i.test(arg)) clearSkin();
              else addChatMsg('', 'Usa il menu SKIN (ESC) per caricare un file.', 'sys');
              break;
            case 'mass':
              if (hasPerm('admin')) adminSetMass(arg);
              else addChatMsg('', 'Serve il ruolo ADMIN.', 'sys');
              break;
            case 'spawn':
              adminSpawnFood(arg);
              break;
            case 'kick':
              adminKickRival(arg);
              break;
            case 'freeze':
              adminToggleFreeze();
              break;
            case 'god':
              adminToggleGodLock();
              break;
            case 'tp':
              adminTeleportCenter();
              break;
            case 'virus':
              adminClearViruses();
              break;
            case 'power':
              adminMaxPowerups();
              break;
            case 'givecoins':
              if (hasPerm('admin')) adminGrantCoins(arg);
              else addChatMsg('', 'Serve il ruolo ADMIN.', 'sys');
              break;
            case 'givelv':
              if (hasPerm('admin')) adminGrantLevels(arg);
              else addChatMsg('', 'Serve il ruolo ADMIN.', 'sys');
              break;
            case 'bots':
              if (hasPerm('admin')) adminSpawnBots(arg);
              else addChatMsg('', 'Serve il ruolo ADMIN.', 'sys');
              break;
            case 'botfollow':
              adminSetBotMode('follow');
              break;
            case 'botfarm':
              adminSetBotMode('farm');
              break;
            case 'botstop':
              adminSetBotMode('idle');
              break;
            default:
              addChatMsg('', 'Comando sconosciuto: /' + cmd, 'sys');
          }
        }

        // --- User menu UI -------------------------------------------
        function avatarStyle() {
          if (profile.skin) return 'background-image:url(' + profile.skin + ');background-size:cover;background-position:center';
          const col = currentPlayerColor();
          return 'background:radial-gradient(circle at 40% 35%, #ffffff, ' + col + ')';
        }
        function updateProfileChip() {
          const nm = profile.loggedIn ? profile.name : 'Guest';
          if (chipNameEl) chipNameEl.textContent = nm;
          if (chipRoleEl) {
            chipRoleEl.textContent = roleLabel();
            chipRoleEl.className = 'role-badge role-' + (profile.role || 'user');
          }
          if (chipAvatarEl) chipAvatarEl.setAttribute('style', avatarStyle());
          if (umNameEl) umNameEl.textContent = nm;
          if (umRoleEl) {
            umRoleEl.textContent = roleLabel();
            umRoleEl.className = 'role-badge role-' + (profile.role || 'user');
          }
          if (umAvatarEl) umAvatarEl.setAttribute('style', avatarStyle());
          const lv = profile.level || 1;
          const base = Math.pow(lv - 1, 2) * 120;
          const next = Math.pow(lv, 2) * 120;
          const frac = Math.max(0, Math.min(1, (profile.xp - base) / Math.max(1, next - base)));
          if (umXpFillEl) umXpFillEl.style.width = (frac * 100).toFixed(1) + '%';
          if (umXpTextEl) {
            umXpTextEl.textContent = 'LV ' + lv + ' · ' + Math.round(profile.xp) + ' XP · ' +
              T('bestMass') + ' ' + Math.round(profile.bestMass || 0);
          }
        }
        function buildTabs() {
          if (!umTabsEl) return;
          const tabs = ['account', 'settings', 'skin', 'stats'];
          if (hasPerm('helper')) tabs.push('admin');
          umTabsEl.innerHTML = tabs
            .map((t) => '<button class="um-tab' + (t === umTab ? ' on' : '') + '" data-tab="' + t + '">' + T(t) + '</button>')
            .join('');
          umTabsEl.querySelectorAll('.um-tab').forEach((b) => {
            b.addEventListener('click', (e) => { e.preventDefault(); switchUmTab(b.dataset.tab); });
          });
          if (tabs.indexOf(umTab) < 0) umTab = 'account';
          for (const k in umPanes) {
            if (umPanes[k]) umPanes[k].classList.toggle('on', k === umTab);
          }
        }
        function switchUmTab(tab) {
          umTab = tab;
          buildTabs();
        }
        function renderUserMenu() {
          updateProfileChip();
          buildTabs();
          renderAccountPane();
          renderSettingsPane();
          renderSkinPane();
          renderAdminPane();
          renderStatsPane();
        }
        function renderAccountPane() {
          const el = umPanes.account;
          if (!el) return;
          let html = '';
          if (profile.loggedIn) {
            html += '<div class="um-note">' + T('loggedAs') + ' <b>' + escapeHtml(profile.name) + '</b> ' +
              T('via') + ' ' + escapeHtml(profile.provider) + '</div>';
            html += '<div class="um-row"><label>' + T('nickname') + '</label>' +
              '<input type="text" id="rename-name" maxlength="24" value="' + escapeHtml(profile.name) + '" />' +
              '<button class="um-btn small" id="btn-rename">SALVA</button></div>';
            html += '<div class="stat-grid">' +
              '<div>' + T('role') + ' <b>' + roleLabel() + '</b></div>' +
              '<div>' + T('level') + ' <b>' + profile.level + '</b></div>' +
              '<div>' + T('bestMass') + ' <b>' + Math.round(profile.bestMass || 0) + '</b></div>' +
              '<div>' + T('runs') + ' <b>' + profile.runs + '</b></div>' +
              '</div>';
            html += '<div class="um-sub">' + T('codeLabel') + '</div>' +
              '<div class="um-row"><input type="text" id="role-code" placeholder="ORBIT-..." />' +
              '<button class="um-btn small" id="btn-redeem">' + T('redeem') + '</button></div>' +
              '<div class="um-note">Codici: ORBIT-VIP · ORBIT-HELPER · ORBIT-MOD · ORBIT-ADMIN</div>';
            html += '<button class="um-btn wide danger" id="btn-logout">' + T('logout') + '</button>';
          } else {
            html += '<div class="um-note">' + T('accountNote') + '</div>';
            html += '<div class="um-row"><label>' + T('nickname') + '</label>' +
              '<input type="text" id="acc-name" maxlength="24" value="' +
              escapeHtml(profile.name === 'Guest' ? '' : profile.name) + '" placeholder="BlobMaster" /></div>';
            html += '<div class="um-row"><label>PIN</label>' +
              '<input type="password" id="acc-pin" maxlength="6" inputmode="numeric" placeholder="0000" /></div>';
            html += '<button class="um-btn wide" id="btn-register">' + T('register') + '</button>';
            html += '<button class="um-btn wide" id="btn-login">' + T('login') + '</button>';
            html += '<div class="um-sub">SOCIAL</div><div class="um-note">' + T('providerNote') + '</div>';
            html += '<div class="prov-grid">' + PROVIDERS.map((p) =>
              '<button class="prov" data-prov="' + p.id + '" style="background:' + p.color + '"><b>' +
              p.icon + '</b> ' + p.label + '</button>').join('') + '</div>';
          }
          el.innerHTML = html;
          const rn = el.querySelector('#btn-rename');
          if (rn) rn.addEventListener('click', (e) => {
            e.preventDefault();
            const inp = el.querySelector('#rename-name');
            changeNickname(inp ? inp.value : '');
          });
          const rd = el.querySelector('#btn-redeem');
          if (rd) rd.addEventListener('click', (e) => {
            e.preventDefault();
            const inp = el.querySelector('#role-code');
            redeemCode(inp ? inp.value : '');
          });
          const lo = el.querySelector('#btn-logout');
          if (lo) lo.addEventListener('click', (e) => { e.preventDefault(); logoutAccount(); });
          const rg = el.querySelector('#btn-register');
          if (rg) rg.addEventListener('click', (e) => {
            e.preventDefault();
            registerAccount(el.querySelector('#acc-name').value, el.querySelector('#acc-pin').value);
          });
          const li = el.querySelector('#btn-login');
          if (li) li.addEventListener('click', (e) => {
            e.preventDefault();
            loginAccount(el.querySelector('#acc-name').value, el.querySelector('#acc-pin').value);
          });
          el.querySelectorAll('.prov').forEach((b) => {
            b.addEventListener('click', (e) => { e.preventDefault(); linkProvider(b.dataset.prov); });
          });
        }
        function settingLabel(def) {
          const lang = (S && S.language) || 'en';
          const labels = {
            it: { sfxVolume:'Volume effetti', musicVolume:'Volume musica', musicOn:'Musica di sottofondo', uiSounds:'Suoni interfaccia', mute:'Muto totale', questToasts:'Avvisi missioni', showPortalBtn:'Pulsante portale', showCoins:'ZeroCoin nell\'HUD', showMinimap:'Minimappa', showChat:'Chat globale', hideBotChatter:'Silenzia i bot in chat', showNames:'Nomi dei rivali', showGrid:'Griglia di sfondo', showTrails:'Scia del dash', showThreatArrows:'Frecce di pericolo', notifications:'Notifiche a schermo', vignette:'Vignetta di morte', showFps:'Contatore FPS', autoRespawn:'Respawn automatico', invertKeys:'Inverti tasti direzione', screenShake:'Scuotimento schermo', hudScale:'Scala HUD', cameraSmooth:'Fluidità camera', zoomSens:'Sensibilità zoom', skinGlow:'Intensità bagliore', particleQuality:'Qualità particelle', colorblind:'Modalità daltonici', language:'Lingua menu', musicTrack:'Traccia musicale', theme:'Tema portale', threeDGraphics:'Grafica 3D', legendaryFx:'Effetti leggendari', portalDepth:'Profondità portale' },
            en: { sfxVolume:'SFX volume', musicVolume:'Music volume', musicOn:'Background music', uiSounds:'Interface sounds', mute:'Mute all', questToasts:'Quest alerts', showPortalBtn:'Portal button', showCoins:'ZeroCoin in HUD', showMinimap:'Minimap', showChat:'Global chat', hideBotChatter:'Silence bot chatter', showNames:'Rival names', showGrid:'Background grid', showTrails:'Dash trail', showThreatArrows:'Threat arrows', notifications:'On-screen notifications', vignette:'Death vignette', showFps:'FPS counter', autoRespawn:'Auto respawn', invertKeys:'Invert key steering', screenShake:'Screen shake', hudScale:'HUD scale', cameraSmooth:'Camera smoothing', zoomSens:'Zoom sensitivity', skinGlow:'Glow intensity', particleQuality:'Particle quality', colorblind:'Colorblind mode', language:'Menu language', musicTrack:'Music track', theme:'Portal theme', threeDGraphics:'3D graphics', legendaryFx:'Legendary effects', portalDepth:'Portal depth' },
            es: { sfxVolume:'Volumen de efectos', musicVolume:'Volumen de música', musicOn:'Música de fondo', uiSounds:'Sonidos de interfaz', mute:'Silenciar todo', showMinimap:'Minimapa', showChat:'Chat global', language:'Idioma del menú', theme:'Tema del portal' },
            fr: { sfxVolume:'Volume des effets', musicVolume:'Volume de la musique', musicOn:'Musique de fond', uiSounds:'Sons de l’interface', mute:'Tout couper', showMinimap:'Minicarte', showChat:'Chat global', language:'Langue du menu', theme:'Thème du portail' },
            de: { sfxVolume:'Effektlautstärke', musicVolume:'Musiklautstärke', musicOn:'Hintergrundmusik', uiSounds:'Interface-Sounds', mute:'Alles stumm', showMinimap:'Minikarte', showChat:'Globaler Chat', language:'Menüsprache', theme:'Portaldesign' },
            pt: { sfxVolume:'Volume dos efeitos', musicVolume:'Volume da música', musicOn:'Música de fundo', uiSounds:'Sons da interface', mute:'Silenciar tudo', showMinimap:'Minimapa', showChat:'Chat global', language:'Idioma do menu', theme:'Tema do portal' },
            ru: { sfxVolume:'Громкость эффектов', musicVolume:'Громкость музыки', musicOn:'Фоновая музыка', uiSounds:'Звуки интерфейса', mute:'Отключить звук', showMinimap:'Мини-карта', showChat:'Общий чат', language:'Язык меню', theme:'Тема портала' },
            zh: { sfxVolume:'音效音量', musicVolume:'音乐音量', musicOn:'背景音乐', uiSounds:'界面音效', mute:'全部静音', showMinimap:'小地图', showChat:'公共聊天', language:'菜单语言', theme:'门户主题' },
            ja: { sfxVolume:'効果音量', musicVolume:'音楽音量', musicOn:'BGM', uiSounds:'UIサウンド', mute:'すべてミュート', showMinimap:'ミニマップ', showChat:'グローバルチャット', language:'メニュー言語', theme:'ポータルテーマ' },
            ko: { sfxVolume:'효과음 볼륨', musicVolume:'음악 볼륨', musicOn:'배경 음악', uiSounds:'인터페이스 사운드', mute:'전체 음소거', showMinimap:'미니맵', showChat:'전체 채팅', language:'메뉴 언어', theme:'포털 테마' },
            tr: { sfxVolume:'Efekt sesi', musicVolume:'Müzik sesi', musicOn:'Arka plan müziği', uiSounds:'Arayüz sesleri', mute:'Tümünü sustur', showMinimap:'Mini harita', showChat:'Genel sohbet', language:'Menü dili', theme:'Portal teması' }
          };
          const dict = labels[lang] || labels.en;
          return dict[def.k] || (lang === 'it' ? def.it : def.en);
        }
        function renderSettingsPane() {
          const el = umPanes.settings;
          if (!el) return;
          let html = '';
          for (const d of SETTING_DEFS) {
            const label = settingLabel(d);
            const v = S[d.k];
            if (d.t === 'text') {
              html += '<div class="um-row"><label>' + label + '</label><input type="text" maxlength="12" value="' + escapeHtml(v || '') + '" data-set="' + d.k + '" data-type="text" /></div>';
            } else if (d.t === 'check') {
              html += '<div class="um-row"><label>' + label + '</label>' +
                '<div class="sw' + (v ? ' on' : '') + '" data-set="' + d.k + '" data-type="check"><i></i></div></div>';
            } else if (d.t === 'slider') {
              html += '<div class="um-row"><label>' + label + ' <span class="um-val">' + v + '</span></label>' +
                '<input type="range" min="' + d.min + '" max="' + d.max + '" step="' + d.step +
                '" value="' + v + '" data-set="' + d.k + '" data-type="slider" /></div>';
            } else {
              html += '<div class="um-row"><label>' + label + '</label><select data-set="' + d.k +
                '" data-type="select">' + d.opts.map((o) =>
                  '<option value="' + o.v + '"' + (o.v === v ? ' selected' : '') + '>' + o.l + '</option>').join('') +
                '</select></div>';
            }
          }
          html += '<button class="um-btn wide danger" id="btn-reset-settings">' + T('resetSettings') + '</button>';
          el.innerHTML = html;
          el.querySelectorAll('[data-set]').forEach((node) => {
            const key = node.dataset.set;
            const type = node.dataset.type;
            if (type === 'check') {
              node.addEventListener('click', () => {
                const on = !S[key];
                node.classList.toggle('on', on);
                setSetting(key, on);
              });
            } else if (type === 'text') {
              node.addEventListener('change', () => {
                const value = String(node.value || '').trim().slice(0, 12);
                setSetting(key, value || defaultSettings()[key]);
              });
            } else if (type === 'slider') {
              node.addEventListener('input', () => {
                const val = parseFloat(node.value);
                const lab = node.parentNode.querySelector('.um-val');
                if (lab) lab.textContent = val;
                setSetting(key, val);
              });
            } else {
              node.addEventListener('change', () => {
                setSetting(key, node.value);
                if (key === 'language') renderUserMenu();
              });
            }
          });
          const rs = el.querySelector('#btn-reset-settings');
          if (rs) rs.addEventListener('click', (e) => {
            e.preventDefault();
            profile.settings = defaultSettings();
            S = profile.settings;
            applySettings();
            renderUserMenu();
            notify('Impostazioni ripristinate', '#7fd4ff');
            queueSave();
          });
        }
        function renderSkinPane() {
          const el = umPanes.skin;
          if (!el) return;
          let html = '<div class="um-note">' + T('uploadNote') + '</div>';
          html += '<div class="skin-drop" id="skin-drop">' + T('drop') + '</div>';
          html += '<input type="file" id="skin-file" accept="image/*,.png,.jpg,.jpeg,.gif,.webp,.svg,.bmp,.avif,.apng" style="display:none" />';
          html += '<button class="um-btn wide" id="btn-pick-skin">' + T('apply') + ' / UPLOAD</button>';
          if (profile.skin) html += '<button class="um-btn wide danger" id="btn-clear-skin">' + T('clear') + '</button>';
          html += '<div class="um-sub">' + T('presets') + '</div><div class="preset-grid">' +
            SKIN_PRESETS.map((c) => '<div class="preset" data-col="' + c + '" style="background:' + c + '"></div>').join('') +
            '</div>';
          el.innerHTML = html;
          const file = el.querySelector('#skin-file');
          const pick = el.querySelector('#btn-pick-skin');
          const drop = el.querySelector('#skin-drop');
          if (pick && file) pick.addEventListener('click', (e) => { e.preventDefault(); file.click(); });
          if (drop && file) drop.addEventListener('click', (e) => { e.preventDefault(); file.click(); });
          if (file) file.addEventListener('change', () => uploadSkinFile(file.files && file.files[0]));
          if (drop) {
            drop.addEventListener('dragover', (e) => { e.preventDefault(); drop.classList.add('hot'); });
            drop.addEventListener('dragleave', () => drop.classList.remove('hot'));
            drop.addEventListener('drop', (e) => {
              e.preventDefault();
              drop.classList.remove('hot');
              const f = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
              uploadSkinFile(f);
            });
          }
          const cl = el.querySelector('#btn-clear-skin');
          if (cl) cl.addEventListener('click', (e) => { e.preventDefault(); clearSkin(); });
          el.querySelectorAll('.preset').forEach((p) => {
            p.addEventListener('click', (e) => { e.preventDefault(); applyPresetSkin(p.dataset.col); updateProfileChip(); });
          });
        }
        function renderAdminPane() {
          const el = umPanes.admin;
          if (!el) return;
          if (!hasPerm('helper')) {
            el.innerHTML = '<div class="um-note">' + T('staffLocked') + '</div>';
            return;
          }
          let html = '<div class="um-note">Ruolo attivo: <b>' + roleLabel() + '</b></div>';
          html += '<button class="um-btn wide" id="tool-hint">\u2139 HINT MINACCIA (helper)</button>';
          html += '<button class="um-btn wide gold" id="tool-power">\u25C8 TUTTI I POWER-UP (helper)</button>';
          if (hasPerm('mod')) {
            html += '<button class="um-btn wide" id="tool-freeze">\u2744 CONGELA ARENA (mod)</button>';
            html += '<button class="um-btn wide" id="tool-food">\u2726 SPAWN 30 PARTICELLE (mod)</button>';
            html += '<button class="um-btn wide" id="tool-virus">\u2623 RIMUOVI VIRUS (mod)</button>';
            html += '<button class="um-btn wide danger" id="tool-kick">\u26D4 ESPELLI RIVALE PIÙ VICINO (mod)</button>';
          }
          if (hasPerm('admin')) {
            html += '<div class="um-sub">ADMIN</div>';
            html += '<div class="um-row"><label>Massa</label><input type="text" id="tool-mass-val" value="500" />' +
              '<button class="um-btn small" id="tool-mass">SET</button></div>';
            html += '<button class="um-btn wide" id="tool-tp">\u2699 TELETRASPORTO AL CENTRO</button>';
            html += '<button class="um-btn wide gold" id="tool-god">\u2726 GOD MODE INFINITO</button>';
            html += '<div class="um-sub">BOT COMANDATI</div>';
            html += '<div class="um-row"><label>Bot</label><input type="number" id="tool-bot-count" value="3" min="1" max="100" />' +
              '<button class="um-btn small" id="tool-bot-spawn">GENERA</button></div>';
            html += '<div class="um-row"><button class="um-btn small" id="tool-bot-follow">SEGUI</button>' +
              '<button class="um-btn small" id="tool-bot-farm">FARMA</button>' +
              '<button class="um-btn small danger" id="tool-bot-stop">STOP</button></div>';
            html += '<div class="um-note">Tasti: ' + escapeHtml(S.botFollowKey || 'F7') + ' segui · ' + escapeHtml(S.botFarmKey || 'F8') + ' farm · ' + escapeHtml(S.botStopKey || 'F9') + '</div>';
            html += '<div class="um-sub">GESTIONE UTENTI</div>';
            html += '<div class="um-row"><label>Coin</label><input type="number" id="tool-coins-val" value="1000" min="1" />' +
              '<button class="um-btn small" id="tool-coins">DAI</button></div>';
            html += '<div class="um-row"><label>Livelli</label><input type="number" id="tool-level-val" value="5" min="1" />' +
              '<button class="um-btn small" id="tool-level-grant">DAI</button></div>';
          }
          el.innerHTML = html;
          const bind = (id, fn) => {
            const b = el.querySelector(id);
            if (b) b.addEventListener('click', (e) => { e.preventDefault(); fn(); });
          };
          bind('#tool-hint', helperHint);
          bind('#tool-power', adminMaxPowerups);
          bind('#tool-bot-spawn', () => {
            const input = el.querySelector('#tool-bot-count');
            adminSpawnBots(input ? input.value : 3);
          });
          bind('#tool-bot-follow', () => adminSetBotMode('follow'));
          bind('#tool-bot-farm', () => adminSetBotMode('farm'));
          bind('#tool-bot-stop', () => adminSetBotMode('idle'));
          bind('#tool-freeze', adminToggleFreeze);
          bind('#tool-food', () => adminSpawnFood(30));
          bind('#tool-virus', adminClearViruses);
          bind('#tool-kick', () => {
            let best = null, bd = Infinity;
            for (const a of aiCells) {
              const d = Math.hypot(a.x - player.x, a.y - player.y);
              if (d < bd) { bd = d; best = a; }
            }
            adminKickRival(best ? best.name : '');
          });
          bind('#tool-mass', () => {
            const v = el.querySelector('#tool-mass-val');
            adminSetMass(v ? v.value : 500);
          });
          bind('#tool-tp', adminTeleportCenter);
          bind('#tool-god', adminToggleGodLock);
          bind('#tool-coins', () => {
            const input = el.querySelector('#tool-coins-val');
            adminGrantCoins(input ? input.value : 1000);
          });
          bind('#tool-level-grant', () => {
            const input = el.querySelector('#tool-level-val');
            adminGrantLevels(input ? input.value : 5);
          });
        }
        function renderStatsPane() {
          const el = umPanes.stats;
          if (!el) return;
          const tm = Math.round(profile.totalTime || 0);
          let html = '<div class="stat-grid">' +
            '<div>' + T('level') + ' <b>' + profile.level + '</b></div>' +
            '<div>XP <b>' + Math.round(profile.xp) + '</b></div>' +
            '<div>' + T('bestMass') + ' <b>' + Math.round(profile.bestMass || 0) + '</b></div>' +
            '<div>' + T('runs') + ' <b>' + profile.runs + '</b></div>' +
            '<div>Particelle <b>' + profile.totalParticles + '</b></div>' +
            '<div>Rivali <b>' + profile.totalRivals + '</b></div>' +
            '<div>Tempo <b>' + Math.floor(tm / 60) + 'm ' + (tm % 60) + 's</b></div>' +
            '<div>' + T('role') + ' <b>' + roleLabel() + '</b></div>' +
            '</div>';
          const achKeys = Object.keys(profile.achievements || {});
          html += '<div class="um-sub">' + T('ach') + ' (' + achKeys.length + ')</div>';
          html += '<div class="ach-list">' + (achKeys.length
            ? achKeys.map((k) => '<span class="ach">' + escapeHtml(k) + '</span>').join('')
            : '<span class="um-note">—</span>') + '</div>';
          html += '<button class="um-btn wide gold" id="btn-open-portal">\u{1F3E0} APRI ZEROARCADE</button>';
          html += '<button class="um-btn wide" id="btn-save-prof">' + T('save') + '</button>';
          html += '<button class="um-btn wide" id="btn-load-prof">' + T('load') + '</button>';
          html += '<div class="um-sub">' + T('backup') + '</div>';
          html += '<textarea id="export-box" placeholder="codice backup"></textarea>';
          html += '<div class="um-row"><button class="um-btn small" id="btn-export">' + T('exportBtn') + '</button>' +
            '<button class="um-btn small" id="btn-import">' + T('importBtn') + '</button></div>';
          html += '<button class="um-btn wide danger" id="btn-wipe">' + T('reset') + '</button>';
          el.innerHTML = html;
          const box = el.querySelector('#export-box');
          const bind = (id, fn) => {
            const b = el.querySelector(id);
            if (b) b.addEventListener('click', (e) => { e.preventDefault(); fn(); });
          };
          bind('#btn-open-portal', () => { closeUserMenu(); openPortal('home'); });
          bind('#btn-save-prof', () => { saveProfile(); notify('\u2714 Progressi salvati', '#00ffa2'); });
          bind('#btn-load-prof', () => { loadProfile(); });
          bind('#btn-export', () => { if (box) box.value = exportProfileCode(); });
          bind('#btn-import', () => { if (box) importProfileCode(box.value); });
          bind('#btn-wipe', () => wipeProfile());
        }
        function openUserMenu() {
          userMenuOpen = true;
          if (userMenuEl) userMenuEl.classList.add('visible');
          renderUserMenu();
        }
        function closeUserMenu() {
          userMenuOpen = false;
          if (userMenuEl) userMenuEl.classList.remove('visible');
          if (portalOpen) renderPortal();
          queueSave();
        }
        function toggleUserMenu() {
          if (userMenuOpen) closeUserMenu();
          else openUserMenu();
        }
        if (umCloseBtn) umCloseBtn.addEventListener('click', (e) => { e.preventDefault(); closeUserMenu(); });
        if (profileChipEl) profileChipEl.addEventListener('click', (e) => { e.preventDefault(); openUserMenu(); });

        // --- Audio System ---
        let audioCtx = null;
        let humNodes = null;
        let absorbBuffer = null;
        let splitBuffer = null;
        let ejectBuffer = null;
        let virusImg = null;
        let lastSoundTime = 0;

        function initAudio() {
          if (!audioCtx) {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (AudioContext) audioCtx = new AudioContext();
          }
          if (audioCtx && audioCtx.state === 'suspended') {
            audioCtx.resume();
          }
          if (audioCtx) ensureAmbientHum();
        }

        function preloadAudio() {
          const assets = [
            { id: 'absorb_sfx', setter: (b) => absorbBuffer = b },
            { id: 'split_sfx', setter: (b) => splitBuffer = b },
            { id: 'eject_sfx', setter: (b) => ejectBuffer = b }
          ];

          assets.forEach(a => {
            const asset = lib.getAsset(a.id);
            if (asset && asset.url) {
              fetch(asset.url)
                .then((r) => r.arrayBuffer())
                .then((buf) => {
                  initAudio();
                  if (audioCtx) return audioCtx.decodeAudioData(buf);
                })
                .then((decoded) => {
                  if (decoded) a.setter(decoded);
                })
                .catch(() => {});
            }
          });
        }
        preloadAudio();

        function preloadImages() {
          const virusAsset = lib.getAsset('virus_asset');
          if (virusAsset && virusAsset.url) {
            virusImg = new Image();
            virusImg.src = virusAsset.url;
            virusImg.crossOrigin = 'anonymous';
          }
        }
        preloadImages();

        function playSound(buffer, volume = 1, pitchVar = 0.2) {
          if (muted) return;
          initAudio();
          if (!audioCtx) return;

          if (buffer) {
            try {
              const source = audioCtx.createBufferSource();
              source.buffer = buffer;
              source.playbackRate.value = (1 - pitchVar/2) + Math.random() * pitchVar;
              const gain = audioCtx.createGain();
              gain.gain.value = volume * sfxVol();
              source.connect(gain);
              gain.connect(audioCtx.destination);
              source.start(0);
              return true;
            } catch (e) {}
          }
          return false;
        }

        function playAbsorbSound() {
          const nowMs = performance.now();
          if (nowMs - lastSoundTime < 60) return;
          lastSoundTime = nowMs;

          if (playSound(absorbBuffer, 0.45)) return;

          // Procedural pop sound effect fallback
          try {
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            const now = audioCtx.currentTime;
            const baseFreq = 480 + Math.random() * 140;
            osc.type = 'sine';
            osc.frequency.setValueAtTime(baseFreq * 0.7, now);
            osc.frequency.exponentialRampToValueAtTime(baseFreq * 1.5, now + 0.04);
            osc.frequency.exponentialRampToValueAtTime(baseFreq * 0.4, now + 0.18);
            gain.gain.setValueAtTime(0.32 * sfxVol(), now);
            gain.gain.exponentialRampToValueAtTime(0.001, now + 0.22);
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.start(now);
            osc.stop(now + 0.24);
          } catch (e) {}
        }

        function playSplitSound() {
          if (playSound(splitBuffer, 0.6, 0.1)) return;
          playAbsorbSound(); // Fallback
        }

        function playEjectSound() {
          playSound(ejectBuffer, 0.35, 0.3);
        }

        // ============================================================
        // BACKGROUND MUSIC, UI SOUNDS, THEMES
        // ============================================================
        const MUSIC_TRACKS = [
          { id: 'auto', name: 'Auto DJ', asset: null },
          { id: 'bgm_menu', name: 'Neon Lobby', asset: 'bgm_menu' },
          { id: 'bgm_arena', name: 'Arena Rush', asset: 'bgm_arena' },
          { id: 'bgm_bonus', name: 'Bonus Funk', asset: 'bgm_bonus' },
          { id: 'proc', name: 'Deep Space (live)', asset: null },
        ];
        const THEMES = [
          { id: 'neon', name: 'Neon Cyan', a: '#00f0ff', bg: 'linear-gradient(180deg,#05081c,#0a0f2e 45%,#03040d)' },
          { id: 'sunset', name: 'Sunset', a: '#ff9436', bg: 'linear-gradient(180deg,#2a0716,#3d1028 45%,#0d0308)' },
          { id: 'toxic', name: 'Toxic', a: '#00ffa2', bg: 'linear-gradient(180deg,#02170f,#05291c 45%,#010806)' },
          { id: 'royal', name: 'Royal', a: '#b388ff', bg: 'linear-gradient(180deg,#120b2e,#1d1147 45%,#05030f)' },
          { id: 'mono', name: 'Mono', a: '#d8e6ff', bg: 'linear-gradient(180deg,#121419,#1b1e26 45%,#05060a)' },
        ];
        const musicBuffers = {};
        const musicPending = {};
        let musicGain = null;
        let musicSrc = null;
        let musicNow = null;
        let procTimer = null;
        let procStep = 0;

        // Portal voice announcements use the browser's local speech engine.
        // They are runtime-only: no storage, network call, or external account is used.
        let portalWelcomePlayed = false;
        let portalWelcomePending = false;
        let portalGoodbyePlayed = false;
        const PORTAL_WELCOME = 'Welcome to Zero The Legend Arcade. Enjoy and smile, life is beautiful.';
        const PORTAL_GOODBYE = 'Goodbye and thanks for playing at Zero The Legend Arcade.';

        function speakPortalMessage(text, isGoodbye) {
          if (!('speechSynthesis' in window) || typeof SpeechSynthesisUtterance === 'undefined') return false;
          try {
            window.speechSynthesis.cancel();
            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = 'en-US';
            utterance.rate = 0.92;
            utterance.pitch = 1.02;
            utterance.volume = 1;
            window.speechSynthesis.speak(utterance);
            if (isGoodbye) portalGoodbyePlayed = true;
            return true;
          } catch (e) {
            return false;
          }
        }

        function announcePortalWelcome() {
          if (portalWelcomePlayed || !portalWelcomePending) return;
          // Browsers commonly require a user gesture before allowing speech.
          if (navigator.userActivation && !navigator.userActivation.isActive) return;
          if (speakPortalMessage(PORTAL_WELCOME, false)) {
            portalWelcomePlayed = true;
            portalWelcomePending = false;
          }
        }

        function announcePortalGoodbye() {
          if (portalGoodbyePlayed) return;
          speakPortalMessage(PORTAL_GOODBYE, true);
        }

        window.addEventListener('pagehide', announcePortalGoodbye);
        window.addEventListener('beforeunload', announcePortalGoodbye);

        function ensureMusicGain() {
          if (!audioCtx) return null;
          if (!musicGain) {
            musicGain = audioCtx.createGain();
            musicGain.gain.value = 0;
            musicGain.connect(audioCtx.destination);
          }
          return musicGain;
        }
        function musicVol() {
          if (!S || S.mute || S.musicOn === false) return 0;
          return (S.musicVolume !== undefined ? S.musicVolume : 0.45) * 0.5;
        }
        function applyMusicVolume() {
          if (!musicGain || !audioCtx) return;
          try { musicGain.gain.setTargetAtTime(musicVol(), audioCtx.currentTime, 0.5); } catch (e) {}
        }
        function loadMusicBuffer(assetId) {
          if (musicBuffers[assetId]) return Promise.resolve(musicBuffers[assetId]);
          if (musicPending[assetId]) return musicPending[assetId];
          const asset = lib.getAsset ? lib.getAsset(assetId) : null;
          if (!asset || !asset.url) return Promise.resolve(null);
          musicPending[assetId] = fetch(asset.url)
            .then((r) => r.arrayBuffer())
            .then((buf) => { initAudio(); return audioCtx ? audioCtx.decodeAudioData(buf) : null; })
            .then((dec) => { if (dec) musicBuffers[assetId] = dec; return dec || null; })
            .catch(() => null);
          return musicPending[assetId];
        }
        function stopMusicSource() {
          if (musicSrc) { try { musicSrc.stop(); } catch (e) {} musicSrc = null; }
          if (procTimer) { clearInterval(procTimer); procTimer = null; }
        }
        function startProcMusic() {
          if (!audioCtx || procTimer) return;
          ensureMusicGain();
          applyMusicVolume();
          const scale = [0, 3, 5, 7, 10, 12, 15];
          procTimer = setInterval(() => {
            if (!audioCtx || !musicGain || musicVol() <= 0) return;
            try {
              const t = audioCtx.currentTime;
              const o = audioCtx.createOscillator();
              const g = audioCtx.createGain();
              const semi = scale[Math.floor(Math.random() * scale.length)] - (procStep % 8 === 0 ? 12 : 0);
              o.type = procStep % 4 === 0 ? 'triangle' : 'sine';
              o.frequency.value = 138 * Math.pow(2, semi / 12);
              g.gain.setValueAtTime(0.0001, t);
              g.gain.exponentialRampToValueAtTime(0.3, t + 0.04);
              g.gain.exponentialRampToValueAtTime(0.0001, t + 0.6);
              o.connect(g);
              g.connect(musicGain);
              o.start(t);
              o.stop(t + 0.65);
              procStep++;
            } catch (e) {}
          }, 400);
        }
        function playMusic(trackId) {
          if (mode !== 'play') return;
          // Background music belongs to gameplay only; the portal is voice-only.
          if (typeof portalOpen !== 'undefined' && portalOpen) {
            stopMusicSource();
            musicNow = null;
            return;
          }
          initAudio();
          if (!audioCtx) return;
          ensureMusicGain();
          if (musicVol() <= 0) { stopMusicSource(); musicNow = null; return; }
          const tr = MUSIC_TRACKS.find((t) => t.id === trackId) || MUSIC_TRACKS[2];
          if (musicNow === tr.id) { applyMusicVolume(); return; }
          musicNow = tr.id;
          stopMusicSource();
          if (!tr.asset) { startProcMusic(); return; }
          loadMusicBuffer(tr.asset).then((buf) => {
            if (musicNow !== tr.id) return;
            if (!buf) { startProcMusic(); return; }
            try {
              const src = audioCtx.createBufferSource();
              src.buffer = buf;
              src.loop = true;
              src.connect(ensureMusicGain());
              src.start(0);
              musicSrc = src;
              applyMusicVolume();
            } catch (e) { startProcMusic(); }
          });
        }
        function autoTrackId() {
          let pOpen = false, pg = 'home';
          try { pOpen = portalOpen; pg = ptPage; } catch (e) {}
          if (pOpen) return (pg === 'crash' || pg === 'arcade') ? 'bgm_bonus' : 'bgm_menu';
          return 'bgm_arena';
        }
        function updateMusicContext() {
          if (mode !== 'play') return;
          const sel = (S && S.musicTrack) || 'auto';
          playMusic(sel === 'auto' ? autoTrackId() : sel);
        }
        function currentTrackName() {
          const id = (S && S.musicTrack) || 'auto';
          const tr = MUSIC_TRACKS.find((t) => t.id === id);
          return tr ? tr.name : 'Auto DJ';
        }
        function nextMusicTrack() {
          const ids = MUSIC_TRACKS.map((t) => t.id);
          const i = ids.indexOf((S && S.musicTrack) || 'auto');
          setSetting('musicTrack', ids[(i + 1) % ids.length]);
          notify('\u266B ' + currentTrackName(), '#b388ff');
        }
        function toggleMusic() {
          setSetting('musicOn', !S.musicOn);
          notify(S.musicOn ? '\u266B Musica ON \u2014 ' + currentTrackName() : '\u266B Musica OFF', '#b388ff');
        }
        function playUiSound(kind) {
          if (!S || S.mute || S.uiSounds === false) return;
          initAudio();
          if (!audioCtx) return;
          try {
            const t = audioCtx.currentTime;
            const o = audioCtx.createOscillator();
            const g = audioCtx.createGain();
            const f = kind === 'buy' ? 640 : kind === 'err' ? 190 : kind === 'win' ? 880 : 420;
            o.type = kind === 'err' ? 'sawtooth' : 'square';
            o.frequency.setValueAtTime(f, t);
            o.frequency.exponentialRampToValueAtTime(kind === 'err' ? f * 0.55 : f * 1.7, t + 0.09);
            g.gain.setValueAtTime(0.08 * sfxVol(), t);
            g.gain.exponentialRampToValueAtTime(0.0001, t + 0.15);
            o.connect(g);
            g.connect(audioCtx.destination);
            o.start(t);
            o.stop(t + 0.17);
          } catch (e) {}
        }
        function applyTheme() {
          const th = THEMES.find((t) => t.id === ((S && S.theme) || 'neon')) || THEMES[0];
          const pEl = document.getElementById('portal');
          if (pEl) pEl.style.background = th.bg;
          const lg = document.getElementById('pt-logo');
          if (lg) {
            const span = lg.querySelector('span');
            if (span) span.style.color = th.a;
          }
        }
        // Any first interaction unlocks WebAudio and starts the soundtrack
        ['pointerdown', 'touchstart', 'keydown'].forEach((ev) =>
          window.addEventListener(ev, () => {
            initAudio();
            updateMusicContext();
            announcePortalWelcome();
          }, { passive: true }));

        // ============================================================
        // DAILY QUESTS, ZERO PASS, SOCIAL (friends / inbox / referral)
        // ============================================================
        const QUEST_POOL = [
          { id: 'eat', type: 'eat', it: 'Divora {g} particelle', goals: [60, 120, 200], reward: 180 },
          { id: 'kill', type: 'kill', it: 'Divora {g} rivali', goals: [3, 6, 10], reward: 260 },
          { id: 'run', type: 'run', it: 'Completa {g} partite in arena', goals: [2, 3, 5], reward: 200 },
          { id: 'time', type: 'time', it: 'Sopravvivi {g} secondi totali', goals: [90, 180, 300], reward: 220 },
          { id: 'mini', type: 'mini', it: 'Gioca {g} mini-giochi', goals: [3, 5, 8], reward: 150 },
          { id: 'crash', type: 'crash', it: 'Gioca {g} round di ZeroCrash', goals: [2, 4, 6], reward: 170 },
          { id: 'earn', type: 'earn', it: 'Guadagna {g} ZeroCoin', goals: [300, 600, 1000], reward: 240 },
          { id: 'dash', type: 'dash', it: 'Usa lo spin dash {g} volte', goals: [4, 8, 12], reward: 140 },
          { id: 'power', type: 'power', it: 'Raccogli {g} power-up', goals: [3, 6, 9], reward: 160 },
        ];
        const PASS_STEP = 400;
        const PASS_MAX = 20;
        const FRIEND_NAMES = ['NovaKid', 'BlobQueen', 'Zyphra', 'MrSplit', 'CellDoc', 'GhostOrbit', 'ZeroFan', 'PlasmaPop'];

        function todayKey() {
          const d = new Date();
          return d.getFullYear() + '-' + (d.getMonth() + 1) + '-' + d.getDate();
        }
        function ensureQuests() {
          if (!profile.quests || typeof profile.quests !== 'object' || !Array.isArray(profile.quests.items)) {
            profile.quests = { date: '', items: [] };
          }
          if (profile.quests.date === todayKey() && profile.quests.items.length) return;
          const pool = QUEST_POOL.slice().sort(() => Math.random() - 0.5).slice(0, 4);
          profile.quests = {
            date: todayKey(),
            items: pool.map((q) => {
              const g = q.goals[Math.floor(Math.random() * q.goals.length)];
              return {
                id: q.id, type: q.type, label: q.it.replace('{g}', g),
                goal: g, prog: 0, reward: q.reward, done: false, claimed: false,
              };
            }),
          };
        }
        function questProgress(type, amount) {
          if (!profile.quests || !Array.isArray(profile.quests.items)) return;
          let changed = false;
          for (const q of profile.quests.items) {
            if (q.type !== type || q.claimed || q.done) continue;
            q.prog = Math.min(q.goal, (q.prog || 0) + amount);
            changed = true;
            if (q.prog >= q.goal) {
              q.done = true;
              if (S.questToasts !== false) notify('\u2714 Missione completata: ' + q.label, '#00ffa2');
              playUiSound('win');
            }
          }
          if (changed) queueSave();
        }
        function claimQuest(id) {
          if (!profile.quests) return;
          const q = profile.quests.items.find((x) => x.id === id);
          if (!q || !q.done || q.claimed) return;
          q.claimed = true;
          addCoins(q.reward, 'Missione: ' + q.label);
          addPassXp(120);
          addProfileXp(60);
          notify('\u{1F3AF} +' + q.reward + ' ZC dalla missione', '#ffc94a');
          unlock('quest', 'Prima missione completata!');
          if (profile.quests.items.every((x) => x.claimed)) {
            sendMail('Missioni giornaliere complete', 'Hai completato tutte le missioni di oggi. Ecco il bonus!', 500);
            unlock('questall', 'Tutte le missioni giornaliere!');
          }
          queueSave();
        }
        function ensurePass() {
          if (!profile.pass || typeof profile.pass !== 'object') profile.pass = { xp: 0, tier: 0, claimed: {} };
          if (!profile.pass.claimed || typeof profile.pass.claimed !== 'object') profile.pass.claimed = {};
        }
        function passTier() {
          ensurePass();
          return Math.min(PASS_MAX, Math.floor((profile.pass.xp || 0) / PASS_STEP));
        }
        function passRewardFor(t) {
          if (t % 10 === 0) return { zc: 1200, item: 'extra_life', label: '1200 ZC + Vita Extra' };
          if (t % 5 === 0) return { zc: 700, label: '700 ZC' };
          return { zc: 150 + t * 20, label: (150 + t * 20) + ' ZC' };
        }
        function addPassXp(n) {
          ensurePass();
          const before = passTier();
          profile.pass.xp = (profile.pass.xp || 0) + Math.max(0, n);
          const after = passTier();
          if (after > before) {
            profile.pass.tier = after;
            notify('\u{1F39F} ZERO PASS livello ' + after + ' sbloccato!', '#ffe600');
            playUiSound('win');
          }
          queueSave();
        }
        function claimPassTier(t) {
          ensurePass();
          if (t > passTier() || profile.pass.claimed[t]) return;
          const rw = passRewardFor(t);
          profile.pass.claimed[t] = true;
          addCoins(rw.zc, 'Zero Pass tier ' + t);
          if (rw.item) profile.inventory[rw.item] = itemCount(rw.item) + 1;
          notify('\u{1F39F} Tier ' + t + ': ' + rw.label, '#ffc94a');
          unlock('pass', 'Primo premio Zero Pass!');
          queueSave();
        }
        function ensureSocial() {
          if (!profile.promos || typeof profile.promos !== 'object') profile.promos = {};
          if (!profile.refCode) {
            profile.refCode = 'ZA-' + Math.random().toString(36).slice(2, 7).toUpperCase();
          }
          if (!Array.isArray(profile.friends) || !profile.friends.length) {
            profile.friends = FRIEND_NAMES.slice(0, 4).map((n) => ({
              n: n, lv: 2 + Math.floor(Math.random() * 20), on: Math.random() < 0.6,
            }));
          }
          if (!Array.isArray(profile.inbox)) profile.inbox = [];
          if (!profile.promos.welcome_mail) {
            profile.promos.welcome_mail = true;
            profile.inbox.unshift({
              id: 'welcome', t: 'Benvenuto su Zero Arcade',
              b: 'Grazie per essere entrato nel portale. Ecco 250 ZeroCoin di benvenuto!',
              zc: 250, claimed: false, d: Date.now(),
            });
          }
        }
        // ============================================================
        // ZEROSOCIAL HUB — one local social layer combining feed, stories,
        // short video, channels, chat, groups, professional networking,
        // trends and micro-posts. It is intentionally offline/local-only.
        // ============================================================
        function ensureSocialHub() {
          if (!profile.socialHub || typeof profile.socialHub !== 'object') profile.socialHub = {};
          const h = profile.socialHub;
          if (!h.view) h.view = 'home';
          if (!h.media || typeof h.media !== 'object') h.media = {};
          if (!h.media.mode) h.media.mode = 'overview';
          if (!h.media.currentTrack) h.media.currentTrack = 'auto';
          if (!Array.isArray(h.media.queue)) h.media.queue = [];
          if (!h.media.audioLikes || typeof h.media.audioLikes !== 'object') h.media.audioLikes = {};
          if (!h.media.audioReposts || typeof h.media.audioReposts !== 'object') h.media.audioReposts = {};
          if (!h.media.watchlist || typeof h.media.watchlist !== 'object') h.media.watchlist = {};
          if (!h.media.bookmarks || typeof h.media.bookmarks !== 'object') h.media.bookmarks = {};
          if (!h.media.toolUsage || typeof h.media.toolUsage !== 'object') h.media.toolUsage = {};
          if (h.media.mixMode === undefined) h.media.mixMode = false;
          if (h.media.privacyLocal === undefined) h.media.privacyLocal = true;
          if (!Array.isArray(h.events)) h.events = [];
          if (!Array.isArray(h.polls)) h.polls = [];
          if (!Array.isArray(h.portalNotices)) h.portalNotices = [];
          if (!Array.isArray(h.supportMessages)) h.supportMessages = [];
          if (!h.service) h.service = 'all';
          if (!h.nexus || typeof h.nexus !== 'object') h.nexus = {};
          if (!h.nexus.intent) h.nexus.intent = 'discover';
          if (typeof h.nexus.pulse !== 'number') h.nexus.pulse = 0;
          if (typeof h.nexus.totalResonance !== 'number') h.nexus.totalResonance = 0;
          if (!h.nexus.resonance || typeof h.nexus.resonance !== 'object') h.nexus.resonance = {};
          if (!Array.isArray(h.nexus.created)) h.nexus.created = [];
          if (h.nexusIntroduced !== true) {
            h.nexusIntroduced = true;
          }
          if (!Array.isArray(h.friendRequests)) h.friendRequests = [];
          if (!h.following || typeof h.following !== 'object') h.following = { zeroarcade: true };
          if (!Array.isArray(h.groups)) h.groups = [];
          if (!Array.isArray(h.pages)) h.pages = [];
          if (!Array.isArray(h.channels)) h.channels = [];
          if (!Array.isArray(h.messages)) h.messages = [];
          if (!Array.isArray(h.stories)) h.stories = [];
          if (!Array.isArray(h.reels)) h.reels = [];
          if (!Array.isArray(h.xPosts)) h.xPosts = [];
          if (!h.saved || typeof h.saved !== 'object') h.saved = {};
          if (!h.professional || typeof h.professional !== 'object') h.professional = {};
          if (!Array.isArray(h.professional.posts)) h.professional.posts = [];
          if (!Array.isArray(h.professional.jobs)) h.professional.jobs = [];
          if (h.professional.headline === undefined) h.professional.headline = 'Neon arena player';
          if (h.professional.connections === undefined) h.professional.connections = 24;
          if (!h.groups.length) h.groups = [
            { id: 'orbiters', name: 'Orbiters Club', desc: 'Strategie, record e sfide dell\'arena.', members: 1240, joined: true },
            { id: 'legend-lab', name: 'Zero The Legend Lab', desc: 'Challenge, speedrun e nuove modalità.', members: 684, joined: false },
            { id: 'zero-pass', name: 'Zero Pass Italia', desc: 'Missioni, ricompense e consigli.', members: 431, joined: false },
          ];
          if (!h.pages.length) h.pages = [
            { id: 'zeroarcade', name: 'Zero Arcade', category: 'Gaming & entertainment', followers: 34800, verified: true },
            { id: 'growth-orbit', name: 'Zero Arcade', category: 'Arena neon', followers: 12700, verified: true },
            { id: 'legend-studio', name: 'Zero The Legend', category: 'Creator', followers: 9200, verified: true },
          ];
          if (!h.channels.length) h.channels = [
            { id: 'zeroarcade', name: 'Zero Arcade', subs: 34800, verified: true, desc: 'Giochi, guide e live dall\'orbita.' },
            { id: 'legend-studio', name: 'Zero The Legend', subs: 9200, verified: true, desc: 'Challenge e momenti leggendari.' },
            { id: 'arena-tips', name: 'Arena Tips', subs: 4100, verified: false, desc: 'Tutorial rapidi per crescere.' },
          ];
          if (!h.messages.length) h.messages = [
            { id: 'msg-nova', peer: 'NovaKid', text: 'Pronto per una sfida in arena?', time: 'Ora', unread: true, group: false, messages: [] },
            { id: 'msg-blob', peer: 'BlobQueen', text: 'Il tuo record è incredibile ⚡', time: '12 min', unread: false, group: false, messages: [] },
            { id: 'msg-community', peer: 'Orbiters Club', text: 'Nuovo evento: conquista la top 3!', time: '1 h', unread: true, group: true, messages: [] },
          ];
          if (!h.stories.length) h.stories = [
            { id: 'story-zero', author: 'Zero Arcade', text: 'Nuovo evento live', color: '#00f0ff', seen: false },
            { id: 'story-nova', author: 'NovaKid', text: 'Top 3 o niente', color: '#ff2d95', seen: false },
            { id: 'story-legend', author: 'Zero The Legend', text: 'Legend Lab aperto', color: '#ffe600', seen: false },
          ];
          if (!h.reels.length) h.reels = [
            { id: 'reel-dash', author: 'Zero Arcade', text: 'Il dash più stretto della settimana', icon: '⚡', likes: 812, views: '12K' },
            { id: 'reel-split', author: 'MrSplit', text: 'Split perfetto sul virus', icon: '◉', likes: 514, views: '8.4K' },
            { id: 'reel-reflex', author: 'Legend Studio', text: 'Reflex sotto 200 ms', icon: '✦', likes: 1200, views: '19K' },
            { id: 'reel-fail', author: 'BlobQueen', text: 'Quando pensi di essere invincibile', icon: '💥', likes: 377, views: '6.1K' },
          ];
          if (!h.xPosts.length) h.xPosts = [
            { id: 'x-zero', author: 'Zero Arcade', handle: '@zeroarcade', text: 'Arena online. Chi prende la top 1 oggi? #GrowthOrbit', time: '2 min', likes: 86, reposts: 14 },
            { id: 'x-legend', author: 'Zero The Legend', handle: '@zerothelegend', text: 'Il nuovo Legend Lab è live. Mostrate i vostri tempi. ⚡', time: '24 min', likes: 241, reposts: 42 },
            { id: 'x-tips', author: 'Arena Tips', handle: '@arenatips', text: 'Conserva G per il momento giusto: la pazienza vince gli scontri.', time: '1 h', likes: 59, reposts: 8 },
          ];
          if (!h.professional.jobs.length) h.professional.jobs = [
            { id: 'job-community', title: 'Community Moderator', company: 'Zero Arcade', meta: 'Remoto · Arena community', tag: 'COMMUNITY' },
            { id: 'job-creator', title: 'Neon Content Creator', company: 'Legend Studio', meta: 'Creator program · Freelance', tag: 'CREATOR' },
            { id: 'job-designer', title: 'Game Systems Designer', company: 'Orbital Labs', meta: 'Portale Zero · Full time', tag: 'DESIGN' },
          ];
        }
        function socialHubState() { ensureSocialHub(); return profile.socialHub; }
        function socialHubSave() { queueSave(); updateWalletUi(); }
        function socialHubFriendRequest(name) {
          const h = socialHubState();
          const n = sanitizeName(name) || String(name || '').trim().slice(0, 20);
          if (!n) return;
          if ((profile.friends || []).some((f) => String(f.n).toLowerCase() === n.toLowerCase())) { notify('Siete già amici', '#7fd4ff'); return; }
          if (h.friendRequests.some((r) => String(r.n).toLowerCase() === n.toLowerCase())) { notify('Richiesta già inviata', '#7fd4ff'); return; }
          h.friendRequests.unshift({ id: 'req_' + Date.now(), n: n, lv: 1 + Math.floor(Math.random() * 20), from: 'suggestion' });
          notify('Richiesta inviata a ' + n, '#00ffa2');
          unlock('friend_request', 'Prima richiesta di amicizia!');
          socialHubSave();
        }
        function socialHubAcceptFriend(id) {
          const h = socialHubState();
          const idx = h.friendRequests.findIndex((r) => r.id === id);
          if (idx < 0) return;
          const r = h.friendRequests[idx];
          if (!Array.isArray(profile.friends)) profile.friends = [];
          if (!profile.friends.some((f) => f.n === r.n)) profile.friends.unshift({ n: r.n, lv: r.lv || 1, on: true });
          h.friendRequests.splice(idx, 1);
          notify(r.n + ' è ora tuo amico', '#00ffa2');
          addProfileXp(12);
          socialHubSave();
        }
        function socialHubToggleFollow(id) {
          const h = socialHubState();
          h.following[id] = !h.following[id];
          notify(h.following[id] ? 'Segui questo canale' : 'Segui rimosso', '#7fd4ff');
          socialHubSave();
        }
        function socialHubJoinGroup(id) {
          const group = socialHubState().groups.find((g) => g.id === id);
          if (!group) return;
          group.joined = !group.joined;
          notify(group.joined ? 'Ti sei unito a ' + group.name : 'Hai lasciato ' + group.name, '#7fd4ff');
          socialHubSave();
        }
        function socialHubSendMessage(peer, text) {
          const value = String(text || '').trim().slice(0, 240);
          if (!value) return;
          const h = socialHubState();
          let chat = h.messages.find((m) => m.peer === peer);
          if (!chat) { chat = { id: 'msg_' + Date.now(), peer: peer, text: '', time: 'Ora', unread: false, group: false, messages: [] }; h.messages.unshift(chat); }
          if (!Array.isArray(chat.messages)) chat.messages = [];
          chat.messages.push({ from: profile.loggedIn ? profile.name : 'Ospite', text: value, time: 'Ora' });
          chat.text = value; chat.time = 'Ora'; chat.unread = false;
          notify('Messaggio inviato a ' + peer, '#00ffa2');
          socialHubSave();
        }
        function socialHubPublish(kind, text) {
          const value = String(text || '').trim().slice(0, 500);
          if (!value) return;
          const h = socialHubState();
          const nm = profile.loggedIn ? profile.name : PT_T('guest');
          if (kind === 'story') h.stories.unshift({ id: 'story_' + Date.now(), author: nm, text: value, color: currentPlayerColor(), seen: false, own: true });
          else if (kind === 'reel') h.reels.unshift({ id: 'reel_' + Date.now(), author: nm, text: value, icon: '▶', likes: 0, views: '0', own: true });
          else if (kind === 'x') h.xPosts.unshift({ id: 'x_' + Date.now(), author: nm, handle: '@' + nm.toLowerCase().replace(/[^a-z0-9]+/g, ''), text: value, time: 'Ora', likes: 0, reposts: 0, own: true });
          else if (kind === 'professional') h.professional.posts.unshift({ id: 'pro_' + Date.now(), author: nm, text: value, time: 'Ora' });
          else {
            socialPublish(value);
            return;
          }
          addProfileXp(6); addPassXp(3); socialHubSave(); notify('Pubblicato su ZeroSocial', '#00ffa2'); renderPortal();
        }
        function socialHubCreateGroup(name) {
          const n = String(name || '').trim().slice(0, 32);
          if (!n) return;
          const h = socialHubState();
          h.groups.unshift({ id: 'group_' + Date.now(), name: n, desc: 'Gruppo creato da ' + (profile.loggedIn ? profile.name : 'Ospite') + '.', members: 1, joined: true, own: true });
          notify('Gruppo creato: ' + n, '#00ffa2'); socialHubSave(); renderPortal();
        }

        // --- ZeroSocial NEXUS: content is an orbit, not another feed ---------
        // The NEXUS fuses every social format into one local constellation.
        // A player chooses an intent, scans the orbit, then resonates with nodes
        // or creates a new pulse that becomes part of the constellation.
        const NEXUS_INTENTS = [
          { id: 'discover', label: 'SCOPRI', hint: 'Trova segnali nuovi' },
          { id: 'connect', label: 'CONNETTI', hint: 'Avvicina persone e gruppi' },
          { id: 'create', label: 'CREA', hint: 'Trasforma idee in impulsi' },
          { id: 'learn', label: 'IMPARA', hint: 'Pesca guide e opportunità' },
          { id: 'play', label: 'GIOCA', hint: 'Cerca sfide nell’orbita' },
        ];
        function nexusSetIntent(intent) {
          const h = socialHubState();
          if (!NEXUS_INTENTS.some((x) => x.id === intent)) return;
          h.nexus.intent = intent;
          h.nexus.pulse = (h.nexus.pulse || 0) + 1;
          socialHubSave();
          notify('Intento Nexus: ' + NEXUS_INTENTS.find((x) => x.id === intent).label, '#ffe600');
          renderPortal();
        }
        function nexusScan() {
          const h = socialHubState();
          const current = NEXUS_INTENTS.findIndex((x) => x.id === h.nexus.intent);
          h.nexus.intent = NEXUS_INTENTS[(current + 1 + Math.floor(Math.random() * (NEXUS_INTENTS.length - 1))) % NEXUS_INTENTS.length].id;
          h.nexus.pulse = (h.nexus.pulse || 0) + 1;
          socialHubSave();
          notify('Scansione completata: nuova orbita sincronizzata', '#00f0ff');
          renderPortal();
        }
        function nexusResonate(id) {
          const h = socialHubState();
          h.nexus.resonance[id] = (h.nexus.resonance[id] || 0) + 1;
          h.nexus.totalResonance = (h.nexus.totalResonance || 0) + 1;
          addProfileXp(2);
          addPassXp(1);
          addCoins(2, 'Risonanza Nexus');
          if (h.nexus.totalResonance === 1) unlock('nexus_first', 'Prima risonanza nel Nexus!');
          if (h.nexus.totalResonance === 10) unlock('nexus_ten', 'Dieci segnali sincronizzati!');
          socialHubSave();
          notify('Risonanza inviata · +' + (h.nexus.resonance[id] || 1) + ' al segnale', '#00ffa2');
          renderPortal();
        }
        function nexusCreatePulse(text) {
          const value = String(text || '').trim().slice(0, 240);
          if (!value) return;
          const h = socialHubState();
          h.nexus.created.unshift({ id: 'pulse_' + Date.now(), text: value, intent: h.nexus.intent, resonance: 0, author: profile.loggedIn ? profile.name : 'Ospite', created: Date.now() });
          h.nexus.created = h.nexus.created.slice(0, 8);
          h.nexus.pulse = (h.nexus.pulse || 0) + 1;
          addProfileXp(8);
          addPassXp(4);
          unlock('nexus_pulse', 'Hai acceso un impulso nel Nexus!');
          socialHubSave();
          notify('Impulso lanciato nella tua orbita', '#ffe600');
          renderPortal();
        }
        function nexusNodes(h) {
          const nodes = [];
          (h.stories || []).slice(0, 2).forEach((s) => nodes.push({ id: 'story:' + s.id, kind: 'STORIA', title: s.author, text: s.text, color: s.color || '#ff2d95' }));
          (h.reels || []).slice(0, 2).forEach((r) => nodes.push({ id: 'reel:' + r.id, kind: 'REEL', title: r.author, text: r.text, color: '#ff2d95' }));
          (h.groups || []).slice(0, 1).forEach((g) => nodes.push({ id: 'group:' + g.id, kind: 'GRUPPO', title: g.name, text: g.desc, color: '#00ffa2' }));
          (h.messages || []).slice(0, 1).forEach((m) => nodes.push({ id: 'chat:' + m.id, kind: 'CHAT', title: m.peer, text: m.text || 'Conversazione aperta', color: '#00f0ff' }));
          ((h.professional && h.professional.jobs) || []).slice(0, 1).forEach((j) => nodes.push({ id: 'job:' + j.id, kind: 'OPPORTUNITÀ', title: j.title, text: j.company + ' · ' + j.meta, color: '#b388ff' }));
          (h.xPosts || []).slice(0, 1).forEach((p) => nodes.push({ id: 'live:' + p.id, kind: 'LIVE', title: p.author, text: p.text, color: '#7fd4ff' }));
          (h.nexus.created || []).slice(0, 3).forEach((p) => nodes.push({ id: p.id, kind: 'IMPULSO', title: p.author, text: p.text, color: '#ffe600' }));
          return nodes.slice(0, 8);
        }
        function zsNexusMarkup(h, nm) {
          const nx = h.nexus || { intent: 'discover', pulse: 0, totalResonance: 0, resonance: {}, created: [] };
          const intent = NEXUS_INTENTS.find((x) => x.id === nx.intent) || NEXUS_INTENTS[0];
          const nodes = nexusNodes(h);
          const slots = [['-31%','-28%'],['3%','-37%'],['34%','-18%'],['39%','22%'],['8%','37%'],['-35%','29%'],['-43%','-2%'],['1%','16%']];
          const nodeHtml = nodes.map((n, i) => {
            const pos = slots[i % slots.length];
            const resonances = nx.resonance[n.id] || 0;
            return '<button class="nexus-node' + (resonances ? ' resonated' : '') + '" data-nexus-resonate="' + escapeHtml(n.id) + '" style="--nx-x:' + pos[0] + ';--nx-y:' + pos[1] + ';--nx-c:' + zsSafeColor(n.color) + '"><b>' + escapeHtml(n.title) + '</b><span>' + escapeHtml(n.text) + '</span><em>' + escapeHtml(n.kind) + ' · ◉ ' + resonances + '</em></button>';
          }).join('');
          const created = (nx.created || []).slice(0, 3).map((p) => '<div class="nexus-created-item"><b>' + escapeHtml(p.author) + '</b> · ' + escapeHtml(p.text) + '</div>').join('');
          return '<div class="nexus-wrap"><section class="nexus-hero"><div class="nexus-kicker">ZEROSOCIAL · NEXUS MODE</div><h2>Il tuo social non è un feed.<br>È un universo che reagisce.</h2><p>Ogni storia, chat, Reel, gruppo, opportunità e post live diventa un segnale. Scegli un intento: il Nexus riorganizza la tua orbita per farti incontrare contenuti e persone, senza copiare nessuna piattaforma.</p><div class="nexus-controls"><select data-nexus-intent aria-label="Intento del Nexus">' + NEXUS_INTENTS.map((x) => '<option value="' + x.id + '"' + (x.id === intent.id ? ' selected' : '') + '>' + x.label + ' · ' + x.hint + '</option>').join('') + '</select><button class="pt-btn gold" data-nexus-scan>✦ SCANSIONA ORBITA</button><span class="zs-pill">INTENTO: ' + intent.label + '</span></div></section><div class="nexus-grid"><section class="nexus-card"><h3>CONSTELLAZIONE VIVA · ' + nodes.length + ' SEGNALI</h3><div class="nexus-map"><div class="nexus-map-grid"></div><div class="nexus-core">' + escapeHtml(nm) + '<small>CORE SOCIALE</small></div>' + (nodeHtml || '<div class="nexus-node" style="--nx-x:0%;--nx-y:-34%;--nx-c:#00f0ff"><b>Orbita vuota</b><span>Lancia il primo impulso per accendere il Nexus.</span><em>INIZIA</em></div>') + '</div></section><aside class="nexus-side-stack"><form class="nexus-card nexus-pulse-form" data-nexus-pulse autocomplete="off"><h3>LANCIA UN IMPULSO</h3><p class="zs-muted">Non pubblicare su una bacheca: invia un’idea che può attirare segnali affini.</p><textarea maxlength="240" placeholder="Cosa vuoi far vibrare nella tua orbita?"></textarea><button class="pt-btn primary" type="submit">⚡ CREA IMPULSO</button></form><div class="nexus-card"><h3>TELEMETRIA DEL TUO NEXUS</h3><div class="nexus-stats"><div class="nexus-stat">Impulsi<b>' + (nx.pulse || 0) + '</b></div><div class="nexus-stat">Risonanze<b>' + (nx.totalResonance || 0) + '</b></div><div class="nexus-stat">Segnali<b>' + nodes.length + '</b></div><div class="nexus-stat">Orbita<b>' + escapeHtml(intent.label) + '</b></div></div>' + (created ? '<div class="nexus-created"><div class="zs-muted">ULTIMI IMPULSI</div>' + created + '</div>' : '') + '</div><div class="nexus-card"><h3>COME FUNZIONA</h3><ul class="nexus-rules"><li><strong>Scansiona</strong> per cambiare prospettiva.</li><li><strong>Risuona</strong> con ciò che vuoi portare vicino.</li><li><strong>Crea un impulso</strong>: il contenuto entra nell’orbita.</li><li>Il Nexus è locale al tuo profilo: nessun account esterno collegato.</li></ul></div></aside></div></div>';
        }

        function sendMail(title, body, zc) {
          if (!Array.isArray(profile.inbox)) profile.inbox = [];
          profile.inbox.unshift({
            id: 'm' + Date.now() + Math.floor(Math.random() * 999),
            t: title, b: body, zc: zc || 0, claimed: false, d: Date.now(),
          });
          if (profile.inbox.length > 20) profile.inbox.length = 20;
          notify('\u2709 Nuovo messaggio: ' + title, '#7fd4ff');
          queueSave();
        }
        function claimMail(id) {
          if (!Array.isArray(profile.inbox)) return;
          const m = profile.inbox.find((x) => x.id === id);
          if (!m || m.claimed) return;
          m.claimed = true;
          if (m.zc > 0) addCoins(m.zc, 'Posta: ' + m.t);
          notify('\u2709 +' + (m.zc || 0) + ' ZC riscattati', '#00ffa2');
          queueSave();
        }
        function redeemFriendCode(code) {
          const c = String(code || '').trim().toUpperCase();
          if (!/^ZA-[A-Z0-9]{3,8}$/.test(c)) { notify('\u26A0 Codice amico non valido', '#ff5c7a'); playUiSound('err'); return; }
          if (c === profile.refCode) { notify('\u26A0 Non puoi invitare te stesso', '#ff5c7a'); return; }
          if (profile.refUsed) { notify('\u26A0 Hai gi\u00E0 usato un codice amico', '#ff5c7a'); return; }
          profile.refUsed = true;
          profile.refCount = (profile.refCount || 0) + 1;
          profile.friends.unshift({ n: 'Amico ' + c.slice(3), lv: 1 + Math.floor(Math.random() * 12), on: true });
          addCoins(300, 'Codice amico ' + c);
          notify('\u{1F91D} +300 ZC per il codice amico', '#ffc94a');
          unlock('friend', 'Primo amico invitato!');
          queueSave();
        }

        // --- Edit-mode params ---
        lib.showGameParameters({
          name: gameTitle,
          params: {
            'Player Color': { key: 'player.color', type: 'color' },
            'Particle Color': { key: 'visual.particleColor', type: 'color' },
            'Grid Color': { key: 'visual.gridColor', type: 'color' },
            'Player Start Size': {
              key: 'player.startSize',
              type: 'slider',
              min: 10,
              max: 30,
              step: 1,
            },
            'Speed Multiplier': {
              key: 'player.speedMultiplier',
              type: 'slider',
              min: 0.5,
              max: 2.5,
              step: 0.1,
            },
            'Camera Zoom Level': {
              key: 'camera.zoomLevel',
              type: 'slider',
              min: 0.5,
              max: 2.0,
              step: 0.1,
            },
            'Growth Rate': {
              key: 'player.growthRate',
              type: 'slider',
              min: 0.5,
              max: 2.0,
              step: 0.1,
            },
            'Particle Count': {
              key: 'arena.particleCount',
              type: 'slider',
              min: 30,
              max: 150,
              step: 5,
            },
            'Arena Size': { key: 'arena.size', type: 'slider', min: 2000, max: 5000, step: 200 },
            'AI Cell Count': { key: 'ai.count', type: 'slider', min: 0, max: 100, step: 1 },
            'AI Min Start Size': { key: 'ai.minStartSize', type: 'slider', min: 8, max: 60, step: 1 },
            'AI Max Start Size': { key: 'ai.maxStartSize', type: 'slider', min: 15, max: 200, step: 5 },
            'AI Aggression': { key: 'ai.aggression', type: 'slider', min: 0, max: 1, step: 0.05 },
            'AI Growth Rate': { key: 'ai.growthRate', type: 'slider', min: 0, max: 3, step: 0.1 },
            'Split Min Mass': { key: 'mechanics.splitMinMass', type: 'slider', min: 20, max: 120, step: 2 },
            'Max Player Cells': { key: 'mechanics.maxCells', type: 'slider', min: 2, max: 16, step: 1 },
            'Merge Time (s)': { key: 'mechanics.mergeTime', type: 'slider', min: 2, max: 25, step: 1 },
            'Split Boost': { key: 'mechanics.splitBoost', type: 'slider', min: 300, max: 1400, step: 20 },
            'Eject Mass': { key: 'mechanics.ejectMass', type: 'slider', min: 6, max: 30, step: 1 },
            'Zone Count': { key: 'mechanics.zoneCount', type: 'slider', min: 1, max: 5, step: 1 },
            'Zone Capture Rate': { key: 'mechanics.zoneCaptureRate', type: 'slider', min: 0.05, max: 0.8, step: 0.05 },
            'Zone Reward': { key: 'mechanics.zoneReward', type: 'slider', min: 50, max: 500, step: 10 },
            'Virus Count': { key: 'mechanics.virusCount', type: 'slider', min: 0, max: 25, step: 1 },
            'Virus Mass': { key: 'mechanics.virusMass', type: 'slider', min: 60, max: 220, step: 10 },
            'Dash Duration (s)': { key: 'abilities.dashDuration', type: 'slider', min: 1, max: 8, step: 0.5 },
            'Dash Speed x': { key: 'abilities.dashSpeed', type: 'slider', min: 1.2, max: 4, step: 0.1 },
            'Dash Cooldown (s)': { key: 'abilities.dashCooldown', type: 'slider', min: 2, max: 25, step: 1 },
            'God Mode Duration (s)': { key: 'abilities.godDuration', type: 'slider', min: 1, max: 12, step: 0.5 },
            'God Mode Cooldown (s)': { key: 'abilities.godCooldown', type: 'slider', min: 5, max: 90, step: 1 },
            'Power-Up Count': { key: 'abilities.powerupCount', type: 'slider', min: 0, max: 14, step: 1 },
            'Pulse Cooldown (s)': { key: 'abilities.pulseCooldown', type: 'slider', min: 5, max: 35, step: 1 },
            'Pulse Radius': { key: 'abilities.pulseRadius', type: 'slider', min: 260, max: 900, step: 20 },
            'Pulse Force': { key: 'abilities.pulseForce', type: 'slider', min: 240, max: 1200, step: 20 },
            'Surge Duration (s)': { key: 'abilities.surgeDuration', type: 'slider', min: 1, max: 10, step: 0.5 },
            'Surge Cooldown (s)': { key: 'abilities.surgeCooldown', type: 'slider', min: 5, max: 45, step: 1 },
            'Surge Speed x': { key: 'abilities.surgeSpeed', type: 'slider', min: 1.1, max: 3, step: 0.1 },
            'Surge Radius': { key: 'abilities.surgeRadius', type: 'slider', min: 300, max: 1200, step: 20 },
            'Arcade Core Count': { key: 'abilities.relicCount', type: 'slider', min: 0, max: 10, step: 1 },
            'Arcade Core Value': { key: 'abilities.relicValue', type: 'slider', min: 10, max: 80, step: 5 },
            'Show Minimap': { key: 'ui.minimap', type: 'checkbox' },
            'Show Chat': { key: 'ui.chat', type: 'checkbox' },
            'Rival Chatter': { key: 'ui.botChatter', type: 'checkbox' },
            'Maximum Mass': { key: 'mechanics.maxMass', type: 'slider', min: 1000, max: 600000, step: 1000 }
          },
        });

        // --- Runtime state ---
        // Aggregate view of the player's blob group (mass-weighted centroid + total mass).
        // Kept as `player` so camera, AI sensing, HUD and leaderboard logic stay simple.
        let player = {
          x: 0,
          y: 0,
          mass: cfg.player.startSize || 20,
          vx: 0,
          vy: 0,
        };
        let playerCells = [];   // Individual blobs after splitting
        let ejected = [];       // Ejected mass pellets
        let viruses = [];       // Spiky viruses that burst big cells
        let peakMass = 0;
        let splitCooldown = 0;
        let ejectCooldown = 0;

        let particles = [];
        let aiCells = [];
        let gameOver = false;
        let lbTimer = 0;
        const AI_COLORS = ['#ff2d95', '#ff3b3b', '#ff6ec7', '#d400ff', '#ff7a18', '#ff1744'];
        const AI_NAMES = [
          'Vortex', 'Nebula', 'Krill', 'Hydra', 'Zenith', 'Mitosis', 'Quark',
          'Plasma', 'Rogue', 'Nucleus', 'Spore', 'Photon', 'Aphid', 'Blobby',
          'Titan', 'Cygnus', 'Pyxis', 'Onyx', 'Zephyr', 'Helix',
        ];
        // Friends' best runs pulled from the shared leaderboard, reborn as rival cells
        let ghostRoster = [];
        window.applyGhostRivals = function (entries) {
          const list = Array.isArray(entries) ? entries : [];
          ghostRoster = list
            .map((e) => ({
              name: (e && (e.username || e.name || e.playerName || e.player || e.userName || e.user)) || 'Rival',
              score: Number((e && (e.score !== undefined ? e.score : e.value !== undefined ? e.value : e.mass)) || 0),
            }))
            .filter((r) => r.score > 0)
            .slice(0, 6);
          if (ghostRoster.length && aiCells.length) {
            for (let i = 0; i < Math.min(ghostRoster.length, aiCells.length); i++) {
              aiCells[i] = spawnAICell({ ghost: ghostRoster[i] });
            }
          }
        };

        let dissolvingEffects = []; // Particle absorption dissolve effects
        let shockwaves = [];        // Cell growth pulse ripples
        let camera = { x: 0, y: 0, zoom: 1 };
        let targetPos = { x: 0, y: 0, active: false };
        let lastTime = 0;
        let globalTime = 0;
        let growthPulse = 0; // Decaying pulse factor when absorbing

        // ================= New systems: abilities, power-ups, progression, UI =================
        let paused = false;
        let muted = false;
        let showFps = false;
        let helpOpen = false;
        let fpsSmooth = 60;
        let hudTimer = 0;
        let miniTimer = 0;
        let survivalTime = 0;
        let userZoom = 1;              // mouse-wheel / pinch zoom factor
        let overviewHold = false;      // TAB: whole-arena overview
        let shake = { t: 0, mag: 0 };
        let dash = { active: false, timer: 0, cooldown: 0 };
        let godMode = { active: false, timer: 0, cooldown: 0 };
        let pulse = { active: false, timer: 0, cooldown: 0, radius: 0 };
        let surge = { active: false, timer: 0, cooldown: 0, radius: 0 };
        let effects = { speed: 0, magnet: 0, shield: 0, x2: 0, freeze: 0 };
        let powerups = [];
        let arcadeCores = [];
        let zones = [];
        let pulseWaves = [];
        let trails = [];
        let combo = 0, comboTimer = 0, bestCombo = 0;
        let level = 1, xp = 0, xpNext = 60;
        let zoneCaptures = 0;
        let zoneBonusTimer = 0;
        let myRank = 99;
        let stats = { particles: 0, rivals: 0, splits: 0, ejects: 0, dashes: 0, gods: 0, pulses: 0, surges: 0, pops: 0, powerups: 0, zones: 0, bestRank: 99 };
        let achievements = {};
        const keysDown = {};
        const keyDir = { x: 0, y: 0 };
        let chatOpen = false;
        let unreadChat = 0;
        let botChatTimer = 5;
        let llmBusy = false;
        let lastChatSent = -99;

        const POWERUP_TYPES = [
          { id: 'speed', label: 'SPEED', color: '#00ffa2', dur: 8 },
          { id: 'magnet', label: 'MAGNET', color: '#b388ff', dur: 9 },
          { id: 'shield', label: 'SHIELD', color: '#7fd4ff', dur: 14 },
          { id: 'x2', label: 'DOUBLE MASS', color: '#ffe600', dur: 10 },
          { id: 'freeze', label: 'FREEZE', color: '#8ff9ff', dur: 5 },
        ];

        const BOT_LINES = [
          'who wants smoke?', 'nice split lol', 'catch me if you can',
          'that virus was NOT my fault', 'feeding time 🍽', 'top 1 incoming',
          'stop camping the edge', 'gg', 'I saw that dash 👀', 'mass check?',
          'someone pop the big one', 'this arena is mine', 'oops', 'brb growing',
        ];

        function escapeHtml(s) {
          return String(s).replace(/[&<>"']/g, (c) => (
            { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
          ));
        }

        function notify(text, color) {
          if (!notifFeedEl || mode !== 'play') return;
          if (S && S.notifications === false) return;
          const el = document.createElement('div');
          el.className = 'notif';
          if (color) el.style.borderLeftColor = color;
          el.textContent = text;
          notifFeedEl.appendChild(el);
          while (notifFeedEl.children.length > 5) notifFeedEl.removeChild(notifFeedEl.firstChild);
          setTimeout(() => el.classList.add('fade'), 2500);
          setTimeout(() => { if (el.parentNode) el.parentNode.removeChild(el); }, 3000);
        }

        function unlock(id, text) {
          if (achievements[id]) return;
          achievements[id] = true;
          if (profile && profile.achievements && !profile.achievements[id]) {
            profile.achievements[id] = true;
            addProfileXp(40);
            queueSave();
          }
          notify('\u{1F3C6} ' + text, '#ffe600');
        }

        function shakeScreen(mag, dur) {
          shake.mag = Math.max(shake.mag, mag);
          shake.t = Math.max(shake.t, dur);
        }

        function addXp(amount) {
          amount *= xpRoleMult() * shopXpMult();
          addProfileXp(amount * 0.35);
          xp += amount;
          while (xp >= xpNext) {
            xp -= xpNext;
            level++;
            xpNext = Math.round(xpNext * 1.45);
            notify('LEVEL ' + level + ' \u2014 speed +3%', '#00ffa2');
            shakeScreen(6, 0.22);
            if (level >= 5) unlock('lv5', 'Veteran cell: level 5!');
          }
        }

        function addCombo() {
          combo++;
          comboTimer = 2.6;
          if (combo > bestCombo) bestCombo = combo;
          if (combo === 10) unlock('combo10', 'Combo x10 feast!');
        }

        function comboMultiplier() {
          return 1 + Math.min(combo, 20) * 0.05;
        }

        // --- Spin dash (SHIFT) ---
        function tryDash() {
          if (mode !== 'play' || gameOver || paused) return;
          if (dash.active || dash.cooldown > 0) return;
          dash.active = true;
          dash.timer = cfg.abilities.dashDuration;
          stats.dashes++;
          questProgress('dash', 1);
          notify('\u26A1 SPIN DASH', '#00ffa2');
          unlock('dash', 'Spin Dash unleashed!');
          shakeScreen(5, 0.2);
          playAbsorbSound();
        }

        // --- Arcade pulse (E) ---
        // A short-range shockwave creates breathing room by pushing away nearby
        // rivals and safely converts small nearby pellets into bonus mass.
        function tryPulse() {
          if (mode !== 'play' || gameOver || paused) return;
          if (pulse.active || pulse.cooldown > 0 || !playerCells.length) return;
          const radius = cfg.abilities.pulseRadius || 520;
          const force = cfg.abilities.pulseForce || 640;
          pulse.active = true;
          pulse.timer = 0.42;
          pulse.radius = 72;
          pulse.cooldown = cfg.abilities.pulseCooldown || 16;
          stats.pulses++;
          notify('✹ ARCADE PULSE', '#b388ff');
          unlock('pulse', 'Arcade Pulse activated!');
          shakeScreen(7, 0.24);
          playSplitSound();
          pulseWaves.push({ x: player.x, y: player.y, radius: 40, maxRadius: radius, progress: 0, color: '#b388ff' });
          for (const a of aiCells) {
            const dx = a.x - player.x;
            const dy = a.y - player.y;
            const d = Math.hypot(dx, dy) || 1;
            if (d > radius) continue;
            const strength = (1 - d / radius) * force;
            a.vx += (dx / d) * strength;
            a.vy += (dy / d) * strength;
            a.flash = Math.max(a.flash, 0.65);
          }
          for (let i = particles.length - 1; i >= 0; i--) {
            const p = particles[i];
            const dx = p.x - player.x;
            const dy = p.y - player.y;
            if (Math.hypot(dx, dy) > radius * 0.78) continue;
            particles.splice(i, 1);
            particles.push(spawnParticle());
            const gain = (p.mass || 4) * 0.18 * (cfg.player.growthRate || 1);
            if (playerCells[0]) playerCells[0].mass += gain;
          }
        }

        // --- God mode (G) ---
        function tryGod() {
          if (mode !== 'play' || gameOver || paused) return;
          if (godMode.active || godMode.cooldown > 0) return;
          godMode.active = true;
          godMode.timer = cfg.abilities.godDuration;
          stats.gods++;
          notify('\u2726 GOD MODE \u2014 invincible!', '#ffb43c');
          unlock('god', 'Touched divinity (god mode)!');
          shakeScreen(9, 0.35);
          playAbsorbSound();
        }

        // Neon Surge is a short, high-energy capture window. It boosts movement,
        // pulls nearby particles and makes captured zones pay out a combo bonus.
        function trySurge() {
          if (mode !== 'play' || gameOver || paused) return;
          if (surge.active || surge.cooldown > 0 || !playerCells.length) return;
          surge.active = true;
          surge.timer = cfg.abilities.surgeDuration || 4;
          surge.radius = cfg.abilities.surgeRadius || 680;
          stats.surges++;
          notify('✦ NEON SURGE — cattura le zone!', '#ff2d95');
          unlock('surge', 'Neon Surge activated!');
          shakeScreen(8, 0.28);
          playSplitSound();
          pulseWaves.push({ x: player.x, y: player.y, radius: 30, maxRadius: surge.radius, progress: 0, color: '#ff2d95' });
        }

        function updateSurge(dt) {
          if (surge.active) {
            surge.timer -= dt;
            surge.radius = cfg.abilities.surgeRadius || 680;
            if (surge.timer <= 0) {
              surge.active = false;
              surge.cooldown = cfg.abilities.surgeCooldown || 20;
              notify('Neon Surge esaurita', '#ff2d95');
            }
          } else if (surge.cooldown > 0) {
            surge.cooldown = Math.max(0, surge.cooldown - dt);
            if (surge.cooldown === 0) notify('Surge pronta', '#ff2d95');
          }
        }

        function updateAbilities(dt) {
          updateSurge(dt);

          if (pulse.active) {
            pulse.timer -= dt;
            pulse.radius = Math.max(0, pulse.radius - dt * 980);
            if (pulse.timer <= 0) pulse.active = false;
          } else if (pulse.cooldown > 0) {
            pulse.cooldown = Math.max(0, pulse.cooldown - dt);
            if (pulse.cooldown === 0) notify('Pulse ready', '#b388ff');
          }

          if (dash.active) {
            dash.timer -= dt;
            if (dash.timer <= 0) {
              dash.active = false;
              dash.cooldown = cfg.abilities.dashCooldown * roleCooldownMult() * shopDashCdMult();
            }
          } else if (dash.cooldown > 0) {
            dash.cooldown = Math.max(0, dash.cooldown - dt);
            if (dash.cooldown === 0) notify('Dash ready', '#00ffa2');
          }

          if (godMode.active) {
            if (adminGodLock && hasPerm('admin')) godMode.timer = cfg.abilities.godDuration;
            else godMode.timer -= dt;
            if (godMode.timer <= 0) {
              godMode.active = false;
              godMode.cooldown = cfg.abilities.godCooldown * roleCooldownMult() * shopDashCdMult();
              notify('God mode ended', '#ffb43c');
            }
          } else if (godMode.cooldown > 0) {
            godMode.cooldown = Math.max(0, godMode.cooldown - dt);
            if (godMode.cooldown === 0) notify('God mode ready', '#ffb43c');
          }

          for (const k in effects) {
            if (effects[k] > 0) effects[k] = Math.max(0, effects[k] - dt);
          }

          if (comboTimer > 0) {
            comboTimer -= dt;
            if (comboTimer <= 0) combo = 0;
          }

          if (shake.t > 0) {
            shake.t = Math.max(0, shake.t - dt);
            if (shake.t === 0) shake.mag = 0;
          }
        }

        // --- Power-up orbs ---
        function spawnPowerup() {
          const arenaHalf = cfg.arena.size / 2;
          const a = Math.random() * Math.PI * 2;
          const d = Math.random() * arenaHalf * 0.88;
          return {
            x: Math.cos(a) * d,
            y: Math.sin(a) * d,
            type: POWERUP_TYPES[Math.floor(Math.random() * POWERUP_TYPES.length)],
            spin: Math.random() * 6.28,
            pulse: Math.random() * 6.28,
          };
        }

        function updatePowerups(dt) {
          const want = Math.max(0, Math.round(cfg.abilities.powerupCount || 0));
          while (powerups.length < want) powerups.push(spawnPowerup());
          while (powerups.length > want) powerups.pop();

          for (let i = powerups.length - 1; i >= 0; i--) {
            const pu = powerups[i];
            pu.spin += dt * 1.5;
            pu.pulse += dt * 3;
            let taken = false;
            for (const c of playerCells) {
              if (Math.hypot(c.x - pu.x, c.y - pu.y) < massToRadius(c.mass) + 24) {
                effects[pu.type.id] = pu.type.dur;
                stats.powerups++;
                questProgress('power', 1);
                notify('\u25C8 ' + pu.type.label + ' ACTIVE', pu.type.color);
                unlock('power', 'First power-up grabbed!');
                playAbsorbSound();
                shakeScreen(4, 0.15);
                taken = true;
                break;
              }
            }
            if (taken) {
              powerups.splice(i, 1);
              powerups.push(spawnPowerup());
            }
          }
        }

        function applyMagnet(dt) {
          if ((effects.magnet <= 0 && !shopMagnet() && !surge.active) || !playerCells.length) return;
          const range = surge.active ? (cfg.abilities.surgeRadius || 680) : 420;
          for (const p of particles) {
            let best = null, bestD = Infinity;
            for (const c of playerCells) {
              const d = Math.hypot(c.x - p.x, c.y - p.y);
              if (d < bestD) { bestD = d; best = c; }
            }
            if (best && bestD < range && bestD > 1) {
              const pull = (surge.active ? 520 : 340) * (1 - bestD / range);
              p.x += ((best.x - p.x) / bestD) * pull * dt;
              p.y += ((best.y - p.y) / bestD) * pull * dt;
            }
          }
        }

        // --- Pause / help ---
        function togglePause(force) {
          if (mode !== 'play' || gameOver) return;
          paused = force !== undefined ? force : !paused;
          if (pauseOverlay) pauseOverlay.classList.toggle('visible', paused);
        }

        function toggleHelp(force) {
          helpOpen = force !== undefined ? force : !helpOpen;
          if (helpOverlay) helpOverlay.classList.toggle('visible', helpOpen);
        }

        // --- Global chat (arena channel) ---
        function updateChatBadge() {
          if (!chatBadgeEl) return;
          chatBadgeEl.textContent = unreadChat > 9 ? '9+' : String(unreadChat);
          chatBadgeEl.classList.toggle('show', unreadChat > 0 && !chatOpen);
        }

        function addChatMsg(who, text, cls) {
          if (!chatLogEl) return;
          const el = document.createElement('div');
          el.className = 'chat-msg' + (cls ? ' ' + cls : '');
          el.innerHTML = cls === 'sys'
            ? escapeHtml(text)
            : '<span class="who">' + escapeHtml(who) + ':</span> ' + escapeHtml(text);
          chatLogEl.appendChild(el);
          while (chatLogEl.children.length > 60) chatLogEl.removeChild(chatLogEl.firstChild);
          chatLogEl.scrollTop = chatLogEl.scrollHeight;
          if (!chatOpen && cls !== 'sys') {
            unreadChat++;
            updateChatBadge();
          }
        }

        function setChatOpen(open) {
          chatOpen = !!open;
          if (chatPanel) chatPanel.classList.toggle('open', chatOpen);
          if (chatOpen) {
            unreadChat = 0;
            if (chatLogEl) chatLogEl.scrollTop = chatLogEl.scrollHeight;
          }
          updateChatBadge();
        }

        function randomRivalName() {
          if (aiCells.length) return aiCells[Math.floor(Math.random() * aiCells.length)].name;
          return AI_NAMES[Math.floor(Math.random() * AI_NAMES.length)];
        }

        function pickCanned() {
          return BOT_LINES[Math.floor(Math.random() * BOT_LINES.length)];
        }

        async function rivalReply(playerText) {
          const rival = randomRivalName();
          if (llmBusy || !lib.llm) {
            setTimeout(() => addChatMsg(rival, pickCanned()), 700 + Math.random() * 900);
            return;
          }
          llmBusy = true;
          try {
            const res = await lib.llm({
              systemPrompt:
                'You are ' + rival + ', a cocky rival blob in the neon arena game Zero Arcade. ' +
                'Reply to the player arena chat with ONE short playful trash-talk line, max 12 words, ' +
                'no quotes, same language as the player.',
              messages: [{ role: 'user', content: playerText }],
              config: { temperature: 1.1, maxOutputTokens: 60 },
            });
            const txt = (res && res.text ? res.text : '').trim().replace(/^["'\s]+|["'\s]+$/g, '');
            addChatMsg(rival, txt || pickCanned());
          } catch (e) {
            addChatMsg(rival, pickCanned());
          }
          llmBusy = false;
        }

        function sendChat() {
          if (!chatInputEl) return;
          const text = chatInputEl.value.trim();
          if (!text) return;
          if (globalTime - lastChatSent < 0.6) return;
          lastChatSent = globalTime;
          chatInputEl.value = '';
          if (text.charAt(0) === '/') {
            addChatMsg(profile.loggedIn ? profile.name : 'You', text, 'me');
            handleChatCommand(text);
            return;
          }
          addChatMsg(profile.loggedIn ? profile.name : 'You', text, 'me');
          unlock('chat', 'Said hi in global chat!');
          if (Math.random() < 0.85) rivalReply(text);
        }

        function updateBotChatter(dt) {
          if (!cfg.ui.botChatter || S.hideBotChatter) return;
          botChatTimer -= dt;
          if (botChatTimer <= 0) {
            botChatTimer = 9 + Math.random() * 14;
            addChatMsg(randomRivalName(), pickCanned());
          }
        }

        // --- HUD refresh (throttled) ---
        function updateAbilityHud() {
          if (mode !== 'play') return;
          const dDur = cfg.abilities.dashDuration || 3;
          const dCd = cfg.abilities.dashCooldown || 9;
          if (chipDash) {
            if (dash.active) {
              chipDash.className = 'ability-chip active';
              chipDashLabel.textContent = 'DASH ' + dash.timer.toFixed(1) + 's';
              chipDashFill.style.width = Math.max(0, (dash.timer / dDur) * 100) + '%';
            } else if (dash.cooldown > 0) {
              chipDash.className = 'ability-chip';
              chipDashLabel.textContent = 'DASH ' + Math.ceil(dash.cooldown) + 's';
              chipDashFill.style.width = (100 - (dash.cooldown / dCd) * 100) + '%';
            } else {
              chipDash.className = 'ability-chip ready';
              chipDashLabel.textContent = 'SHIFT DASH';
              chipDashFill.style.width = '100%';
            }
          }
          const gDur = cfg.abilities.godDuration || 5;
          const gCd = cfg.abilities.godCooldown || 28;
          if (chipSurge) {
            const sDur = cfg.abilities.surgeDuration || 4;
            const sCd = cfg.abilities.surgeCooldown || 20;
            if (surge.active) {
              chipSurge.className = 'ability-chip active';
              chipSurgeLabel.textContent = 'SURGE ' + surge.timer.toFixed(1) + 's';
              chipSurgeFill.style.width = Math.max(0, (surge.timer / sDur) * 100) + '%';
            } else if (surge.cooldown > 0) {
              chipSurge.className = 'ability-chip';
              chipSurgeLabel.textContent = 'SURGE ' + Math.ceil(surge.cooldown) + 's';
              chipSurgeFill.style.width = (100 - (surge.cooldown / sCd) * 100) + '%';
            } else {
              chipSurge.className = 'ability-chip ready';
              chipSurgeLabel.textContent = 'R SURGE';
              chipSurgeFill.style.width = '100%';
            }
          }
          if (chipPulse) {
            const pCd = cfg.abilities.pulseCooldown || 16;
            if (pulse.active) {
              chipPulse.className = 'ability-chip active';
              chipPulseLabel.textContent = 'PULSE';
              chipPulseFill.style.width = Math.max(0, (pulse.radius / (cfg.abilities.pulseRadius || 520)) * 100) + '%';
            } else if (pulse.cooldown > 0) {
              chipPulse.className = 'ability-chip';
              chipPulseLabel.textContent = 'PULSE ' + Math.ceil(pulse.cooldown) + 's';
              chipPulseFill.style.width = (100 - (pulse.cooldown / pCd) * 100) + '%';
            } else {
              chipPulse.className = 'ability-chip ready';
              chipPulseLabel.textContent = 'E PULSE';
              chipPulseFill.style.width = '100%';
            }
          }
          if (chipGod) {
            if (godMode.active) {
              chipGod.className = 'ability-chip active';
              chipGodLabel.textContent = 'GOD ' + godMode.timer.toFixed(1) + 's';
              chipGodFill.style.width = Math.max(0, (godMode.timer / gDur) * 100) + '%';
            } else if (godMode.cooldown > 0) {
              chipGod.className = 'ability-chip';
              chipGodLabel.textContent = 'GOD ' + Math.ceil(godMode.cooldown) + 's';
              chipGodFill.style.width = (100 - (godMode.cooldown / gCd) * 100) + '%';
            } else {
              chipGod.className = 'ability-chip ready';
              chipGodLabel.textContent = 'G GOD';
              chipGodFill.style.width = '100%';
            }
          }
          if (dashBtn) {
            dashBtn.classList.toggle('cooling', dash.cooldown > 0 && !dash.active);
            dashBtn.classList.toggle('on', dash.active);
          }
          if (godBtn) {
            godBtn.classList.toggle('cooling', godMode.cooldown > 0 && !godMode.active);
            godBtn.classList.toggle('on', godMode.active);
          }
          if (pulseBtn) {
            pulseBtn.classList.toggle('cooling', pulse.cooldown > 0 && !pulse.active);
            pulseBtn.classList.toggle('on', pulse.active);
          }
          if (surgeBtn) {
            surgeBtn.classList.toggle('cooling', surge.cooldown > 0 && !surge.active);
            surgeBtn.classList.toggle('on', surge.active);
          }

          const mm = Math.floor(survivalTime / 60);
          const ss = Math.floor(survivalTime % 60);
          if (pillTimeEl) pillTimeEl.textContent = (mm < 10 ? '0' : '') + mm + ':' + (ss < 10 ? '0' : '') + ss;
          if (pillLevelEl) pillLevelEl.textContent = 'LV ' + level;
          if (pillRankEl) pillRankEl.textContent = '#' + myRank;
          if (pillComboEl) {
            pillComboEl.classList.toggle('hidden', combo < 2);
            pillComboEl.textContent = 'COMBO x' + combo;
          }
          if (pillZonesEl) pillZonesEl.textContent = 'ZONES ' + zoneCaptures + '/' + zones.length;
          if (pillFpsEl) {
            pillFpsEl.classList.toggle('hidden', !showFps);
            pillFpsEl.textContent = Math.round(fpsSmooth) + ' FPS';
          }

          if (effectStripEl) {
            let html = '';
            for (const t of POWERUP_TYPES) {
              const left = effects[t.id];
              if (left > 0) {
                html += '<div class="eff-chip" style="color:' + t.color + '">' + t.label + ' ' + left.toFixed(1) + 's</div>';
              }
            }
            if (effectStripEl.innerHTML !== html) effectStripEl.innerHTML = html;
          }
        }

        // --- Minimap ---
        function renderMinimap() {
          if (!miniCtx || !cfg.ui.minimap) return;
          const W = miniCanvas.width;
          const H = miniCanvas.height;
          const arena = cfg.arena.size;
          const s = W / arena;
          const cx = W / 2;
          const cy = H / 2;
          miniCtx.clearRect(0, 0, W, H);
          miniCtx.fillStyle = 'rgba(4, 10, 26, 0.7)';
          miniCtx.fillRect(0, 0, W, H);

          miniCtx.strokeStyle = 'rgba(0, 240, 255, 0.35)';
          miniCtx.lineWidth = 1;
          miniCtx.beginPath();
          miniCtx.arc(cx, cy, (arena / 2) * s, 0, Math.PI * 2);
          miniCtx.stroke();

          miniCtx.fillStyle = 'rgba(80, 255, 140, 0.8)';
          for (const v of viruses) {
            miniCtx.beginPath();
            miniCtx.arc(cx + v.x * s, cy + v.y * s, 2, 0, Math.PI * 2);
            miniCtx.fill();
          }

          for (const core of arcadeCores) {
            miniCtx.fillStyle = '#ffe600';
            miniCtx.fillRect(cx + core.x * s - 2, cy + core.y * s - 2, 4, 4);
          }

          for (const pu of powerups) {
            miniCtx.fillStyle = pu.type.color;
            miniCtx.fillRect(cx + pu.x * s - 2, cy + pu.y * s - 2, 4, 4);
          }

          for (const z of zones) {
            miniCtx.strokeStyle = z.captured ? '#ffe600' : z.color;
            miniCtx.lineWidth = 1.5;
            miniCtx.beginPath();
            miniCtx.arc(cx + z.x * s, cy + z.y * s, Math.max(3, z.radius * s), 0, Math.PI * 2);
            miniCtx.stroke();
          }

          for (const a of aiCells) {
            miniCtx.fillStyle = a.mass > player.mass * 1.15 ? dangerColor() : hexToRgba(a.color, 0.85);
            const r = Math.max(2, Math.min(7, massToRadius(a.mass) * s));
            miniCtx.beginPath();
            miniCtx.arc(cx + a.x * s, cy + a.y * s, r, 0, Math.PI * 2);
            miniCtx.fill();
          }

          miniCtx.fillStyle = godMode.active ? '#ffb43c' : '#ffffff';
          for (const c of playerCells) {
            const r = Math.max(2.5, Math.min(9, massToRadius(c.mass) * s));
            miniCtx.beginPath();
            miniCtx.arc(cx + c.x * s, cy + c.y * s, r, 0, Math.PI * 2);
            miniCtx.fill();
          }

          const vw = (canvas.width / camera.zoom) * s;
          const vh = (canvas.height / camera.zoom) * s;
          miniCtx.strokeStyle = 'rgba(255, 255, 255, 0.35)';
          miniCtx.strokeRect(cx + camera.x * s - vw / 2, cy + camera.y * s - vh / 2, vw, vh);
        }

        // --- Off-screen threat indicators ---
        function drawThreatArrows(w, h) {
          if (mode !== 'play' || gameOver) return;
          const cx = w / 2;
          const cy = h / 2;
          const margin = 46;
          ctx.save();
          for (const a of aiCells) {
            if (a.mass <= player.mass * 1.15) continue;
            const sx = (a.x - camera.x) * camera.zoom + cx;
            const sy = (a.y - camera.y) * camera.zoom + cy;
            if (sx > -20 && sx < w + 20 && sy > -20 && sy < h + 20) continue;
            const ang = Math.atan2(a.y - player.y, a.x - player.x);
            const rad = Math.min(w, h) / 2 - margin;
            const ax = cx + Math.cos(ang) * rad;
            const ay = cy + Math.sin(ang) * rad;
            ctx.translate(ax, ay);
            ctx.rotate(ang);
            ctx.fillStyle = 'rgba(255, 60, 90, 0.75)';
            ctx.beginPath();
            ctx.moveTo(14, 0);
            ctx.lineTo(-10, 8);
            ctx.lineTo(-10, -8);
            ctx.closePath();
            ctx.fill();
            ctx.setTransform(1, 0, 0, 1, 0, 0);
          }
          ctx.restore();
        }

        function drawPowerups() {
          for (const pu of powerups) {
            const pulse = 1 + Math.sin(pu.pulse) * 0.12;
            const r = 17 * pulse;
            ctx.save();
            ctx.translate(pu.x, pu.y);
            ctx.rotate(pu.spin);
            ctx.globalAlpha = 0.3;
            ctx.fillStyle = pu.type.color;
            ctx.beginPath();
            ctx.arc(0, 0, r * 1.9, 0, Math.PI * 2);
            ctx.fill();
            ctx.globalAlpha = 1;
            ctx.strokeStyle = pu.type.color;
            ctx.lineWidth = 3;
            ctx.beginPath();
            for (let i = 0; i < 6; i++) {
              const a = (Math.PI * 2 * i) / 6;
              const px = Math.cos(a) * r;
              const py = Math.sin(a) * r;
              if (i === 0) ctx.moveTo(px, py);
              else ctx.lineTo(px, py);
            }
            ctx.closePath();
            ctx.stroke();
            ctx.fillStyle = 'rgba(255,255,255,0.85)';
            ctx.beginPath();
            ctx.arc(0, 0, r * 0.38, 0, Math.PI * 2);
            ctx.fill();
            ctx.restore();
          }
        }

        function spawnArcadeCore() {
          const arenaHalf = cfg.arena.size / 2;
          const a = Math.random() * Math.PI * 2;
          const d = Math.random() * arenaHalf * 0.86;
          return { x: Math.cos(a) * d, y: Math.sin(a) * d, pulse: Math.random() * 6.28, spin: Math.random() * 6.28 };
        }

        function spawnZone(index, total) {
          const arenaHalf = cfg.arena.size / 2;
          const angle = (Math.PI * 2 * index) / Math.max(1, total) + Math.random() * 0.4;
          const dist = arenaHalf * (0.28 + Math.random() * 0.42);
          return {
            id: 'zone_' + index,
            x: Math.cos(angle) * dist,
            y: Math.sin(angle) * dist,
            radius: Math.min(180, arenaHalf * 0.09),
            progress: 0,
            captured: false,
            pulse: Math.random() * 6.28,
            color: ['#00f0ff', '#ff2d95', '#ffe600', '#00ffa2', '#b388ff'][index % 5]
          };
        }

        function initZones() {
          zones = [];
          const count = Math.max(1, Math.round(cfg.mechanics.zoneCount || 3));
          for (let i = 0; i < count; i++) zones.push(spawnZone(i, count));
        }

        function updateZones(dt) {
          const count = Math.max(1, Math.round(cfg.mechanics.zoneCount || 3));
          while (zones.length < count) zones.push(spawnZone(zones.length, count));
          while (zones.length > count) zones.pop();
          const rate = Math.max(0.05, Number(cfg.mechanics.zoneCaptureRate) || 0.22);
          for (const z of zones) {
            z.pulse += dt * 2.4;
            if (z.captured) continue;
            let inside = false;
            for (const c of playerCells) {
              if (Math.hypot(c.x - z.x, c.y - z.y) < z.radius + massToRadius(c.mass) * 0.25) {
                inside = true;
                break;
              }
            }
            if (inside && surge.active) {
              z.progress = Math.min(1, z.progress + dt * rate);
              if (z.progress >= 1) {
                z.captured = true;
                zoneCaptures++;
                stats.zones++;
                const reward = Math.max(10, Math.round(cfg.mechanics.zoneReward || 180) * (1 + Math.min(combo, 10) * 0.08));
                addCoins(reward, 'Zona conquistata');
                addXp(reward * 0.35);
                addPassXp(12);
                addCombo();
                notify('◉ ZONA CONQUISTATA +' + reward + ' ZC', z.color);
                unlock('zone', 'First neon zone captured!');
                shockwaves.push({ x: z.x, y: z.y, radius: z.radius * 0.45, maxRadius: z.radius * 1.8, progress: 0, color: z.color });
                if (zoneCaptures >= zones.length) unlock('zone_master', 'Captured every arena zone!');
              }
            } else if (!inside && z.progress > 0) {
              z.progress = Math.max(0, z.progress - dt * rate * 0.18);
            }
          }
          if (zoneCaptures >= zones.length && zones.length) {
            zoneBonusTimer += dt;
            if (zoneBonusTimer > 18) {
              zoneBonusTimer = 0;
              for (const z of zones) { z.captured = false; z.progress = 0; }
              zoneCaptures = 0;
              notify('Nuova rotazione delle zone!', '#00f0ff');
            }
          }
        }

        function drawZones() {
          for (const z of zones) {
            const pulseScale = 1 + Math.sin(z.pulse) * 0.06;
            ctx.save();
            ctx.globalAlpha = z.captured ? 0.2 : 0.1 + z.progress * 0.18;
            ctx.fillStyle = z.color;
            ctx.beginPath(); ctx.arc(z.x, z.y, z.radius * 1.35 * pulseScale, 0, Math.PI * 2); ctx.fill();
            ctx.globalAlpha = z.captured ? 0.85 : 0.55;
            ctx.strokeStyle = z.color;
            ctx.lineWidth = z.captured ? 6 : 3;
            ctx.setLineDash(z.captured ? [12, 8] : [7, 10]);
            ctx.beginPath(); ctx.arc(z.x, z.y, z.radius * pulseScale, globalTime * 0.7, globalTime * 0.7 + Math.PI * 2); ctx.stroke();
            ctx.setLineDash([]);
            ctx.globalAlpha = 0.9;
            ctx.fillStyle = '#fff';
            ctx.font = '700 13px Orbitron, sans-serif';
            ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
            ctx.fillText(z.captured ? 'CAPTURED' : Math.round(z.progress * 100) + '%', z.x, z.y);
            ctx.restore();
          }
        }

        function updateArcadeCores(dt) {
          const want = Math.max(0, Math.round(cfg.abilities.relicCount || 0));
          while (arcadeCores.length < want) arcadeCores.push(spawnArcadeCore());
          while (arcadeCores.length > want) arcadeCores.pop();
          for (let i = arcadeCores.length - 1; i >= 0; i--) {
            const core = arcadeCores[i];
            core.pulse += dt * 3;
            core.spin += dt * 1.8;
            let taken = false;
            for (const c of playerCells) {
              if (Math.hypot(c.x - core.x, c.y - core.y) < massToRadius(c.mass) + 26) {
                const value = Math.max(1, Math.round(cfg.abilities.relicValue || 25));
                c.mass = Math.min(Number(cfg.mechanics.maxMass) || 600000, c.mass + value * 0.7);
                addCoins(value, 'Arcade Core');
                addXp(value * 1.4);
                addPassXp(4);
                notify('✦ Arcade Core +' + value + ' ZC', '#ffe600');
                unlock('arcade_core', 'First Arcade Core collected!');
                shockwaves.push({ x: core.x, y: core.y, radius: 12, maxRadius: 90, progress: 0, color: '#ffe600' });
                playAbsorbSound();
                taken = true;
                break;
              }
            }
            if (taken) {
              arcadeCores.splice(i, 1);
              arcadeCores.push(spawnArcadeCore());
            }
          }
        }

        function drawArcadeCores() {
          for (const core of arcadeCores) {
            const pulseScale = 1 + Math.sin(core.pulse) * 0.14;
            ctx.save();
            ctx.translate(core.x, core.y);
            ctx.rotate(core.spin);
            ctx.globalAlpha = 0.25;
            ctx.fillStyle = '#ffe600';
            ctx.beginPath();
            ctx.arc(0, 0, 34 * pulseScale, 0, Math.PI * 2);
            ctx.fill();
            ctx.globalAlpha = 1;
            ctx.strokeStyle = '#ffe600';
            ctx.lineWidth = 3;
            ctx.beginPath();
            ctx.moveTo(0, -18 * pulseScale); ctx.lineTo(18 * pulseScale, 0);
            ctx.lineTo(0, 18 * pulseScale); ctx.lineTo(-18 * pulseScale, 0);
            ctx.closePath(); ctx.stroke();
            ctx.fillStyle = '#fff';
            ctx.beginPath(); ctx.arc(-4, -4, 5, 0, Math.PI * 2); ctx.fill();
            ctx.restore();
          }
        }

        function updatePulseWaves(dt) {
          for (let i = pulseWaves.length - 1; i >= 0; i--) {
            const wave = pulseWaves[i];
            wave.progress += dt * 2.8;
            wave.radius = wave.maxRadius * wave.progress;
            if (wave.progress >= 1) pulseWaves.splice(i, 1);
          }
        }

        function drawPulseWaves() {
          for (const wave of pulseWaves) {
            ctx.save();
            ctx.globalAlpha = (1 - wave.progress) * 0.8;
            ctx.strokeStyle = wave.color;
            ctx.lineWidth = Math.max(2, (1 - wave.progress) * 9);
            ctx.beginPath();
            ctx.arc(wave.x, wave.y, wave.radius, 0, Math.PI * 2);
            ctx.stroke();
            ctx.restore();
          }
        }

        function updateTrails(dt) {
          for (let i = trails.length - 1; i >= 0; i--) {
            const t = trails[i];
            t.life -= dt * 2.2;
            if (t.life <= 0) trails.splice(i, 1);
          }
          if (dash.active) {
            for (const c of playerCells) {
              if (trails.length > 70) break;
              trails.push({ x: c.x, y: c.y, r: massToRadius(c.mass) * 0.85, life: 1 });
            }
          }
        }

        function drawTrails(color) {
          for (const t of trails) {
            ctx.globalAlpha = t.life * 0.28;
            ctx.fillStyle = color;
            ctx.beginPath();
            ctx.arc(t.x, t.y, t.r * t.life, 0, Math.PI * 2);
            ctx.fill();
          }
          ctx.globalAlpha = 1;
        }

        function massToRadius(mass) {
          return Math.sqrt(mass) * 4;
        }

        let cachedBgGrad = null;
        let cachedBgW = 0;
        let cachedBgH = 0;

        function updateCachedBgGrad(w, h) {
          if (w === cachedBgW && h === cachedBgH && cachedBgGrad) return cachedBgGrad;
          cachedBgW = w;
          cachedBgH = h;
          const bgGrad = ctx.createRadialGradient(w / 2, h / 2, 0, w / 2, h / 2, Math.max(w, h));
          bgGrad.addColorStop(0, '#0c1236');
          bgGrad.addColorStop(0.65, '#060919');
          bgGrad.addColorStop(1, '#020308');
          cachedBgGrad = bgGrad;
          return cachedBgGrad;
        }

        const rgbaCache = new Map();
        function hexToRgba(hex, alpha) {
          if (!hex || typeof hex !== 'string') return `rgba(0, 255, 255, ${alpha})`;
          const a = Math.round(alpha * 100) / 100;
          const key = hex + '_' + a;
          const cached = rgbaCache.get(key);
          if (cached) return cached;
          let c = hex.replace('#', '');
          if (c.length === 3) c = c[0] + c[0] + c[1] + c[1] + c[2] + c[2];
          const num = parseInt(c, 16);
          if (isNaN(num)) return `rgba(0, 255, 255, ${a})`;
          const r = (num >> 16) & 255;
          const g = (num >> 8) & 255;
          const b = num & 255;
          const str = `rgba(${r}, ${g}, ${b}, ${a})`;
          rgbaCache.set(key, str);
          return str;
        }

        function spawnParticle() {
          const arenaSize = cfg.arena.size;
          const half = arenaSize / 2;
          const angle = Math.random() * Math.PI * 2;
          const dist = Math.random() * half * 0.92;
          // Varied particle hues derived around particleColor or neon variations
          return {
            x: Math.cos(angle) * dist,
            y: Math.sin(angle) * dist,
            mass: 4 + Math.random() * 8,
            vx: (Math.random() - 0.5) * 18,
            vy: (Math.random() - 0.5) * 18,
            phase: Math.random() * Math.PI * 2,
            sparkleSpeed: 3 + Math.random() * 4,
            sparkleSize: 0.6 + Math.random() * 0.8,
            rotation: Math.random() * Math.PI,
            rotSpeed: (Math.random() - 0.5) * 2,
          };
        }

        function spawnAICell(opts) {
          const o = opts || {};
          const ghost = o.ghost || null;
          const arenaHalf = cfg.arena.size / 2;
          const minS = Math.min(cfg.ai.minStartSize, cfg.ai.maxStartSize);
          const maxS = Math.max(cfg.ai.minStartSize, cfg.ai.maxStartSize);
          let x, y;
          let tries = 0;
          do {
            const angle = Math.random() * Math.PI * 2;
            const dist = (0.25 + Math.random() * 0.68) * arenaHalf;
            x = Math.cos(angle) * dist;
            y = Math.sin(angle) * dist;
            tries++;
          } while (tries < 12 && Math.hypot(x - player.x, y - player.y) < 500);

          return {
            x: x,
            y: y,
            mass:
              o.mass !== undefined
                ? o.mass
                : ghost
                ? Math.max(minS, Math.min(ghost.score * 0.7, maxS * 3))
                : minS + Math.random() * (maxS - minS),
            vx: 0,
            vy: 0,
            isGhost: !!ghost,
            color: ghost ? '#a06bff' : AI_COLORS[Math.floor(Math.random() * AI_COLORS.length)],
            name: ghost ? ghost.name : AI_NAMES[Math.floor(Math.random() * AI_NAMES.length)],
            wander: Math.random() * Math.PI * 2,
            pulse: Math.random() * Math.PI * 2,
            flash: 0,
            commanded: !!o.commanded,
          };
        }

        function initAI() {
          aiCells = [];
          const count = Math.min(100, Math.max(0, Math.round(cfg.ai.count || 0)));
          for (let i = 0; i < count; i++) {
            const ghost = i < ghostRoster.length ? ghostRoster[i] : null;
            aiCells.push(spawnAICell(ghost ? { ghost: ghost } : {}));
          }
        }

        function initParticles() {
          particles = [];
          dissolvingEffects = [];
          shockwaves = [];
          const count = cfg.arena.particleCount;
          for (let i = 0; i < count; i++) {
            particles.push(spawnParticle());
          }
        }

        // --- Player blob group: split / merge / eject ---
        function makePlayerCell(x, y, mass, mergeTime) {
          return {
            x: x,
            y: y,
            mass: mass,
            vx: 0,
            vy: 0,
            bx: 0, // split boost velocity
            by: 0,
            mergeTimer: mergeTime || 0,
          };
        }

        function syncPlayerAggregate() {
          const maxMass = Number(cfg.mechanics.maxMass) || 600000;
          let m = 0, cx = 0, cy = 0, vx = 0, vy = 0;
          for (const c of playerCells) {
            c.mass = Math.max(0, Number(c.mass) || 0);
            m += c.mass;
            cx += c.x * c.mass;
            cy += c.y * c.mass;
            vx += c.vx * c.mass;
            vy += c.vy * c.mass;
          }
          if (m > maxMass && m > 0) {
            const scale = maxMass / m;
            for (const c of playerCells) c.mass *= scale;
            m = 0; cx = 0; cy = 0; vx = 0; vy = 0;
            for (const c of playerCells) {
              m += c.mass;
              cx += c.x * c.mass;
              cy += c.y * c.mass;
              vx += c.vx * c.mass;
              vy += c.vy * c.mass;
            }
          }
          if (m > 0) {
            player.x = cx / m;
            player.y = cy / m;
            player.vx = vx / m;
            player.vy = vy / m;
            player.mass = m;
            if (m > peakMass) peakMass = m;
          }
        }

        function aimDir(fromX, fromY) {
          let dx = 0, dy = 0;
          if (targetPos.active) {
            dx = targetPos.x - fromX;
            dy = targetPos.y - fromY;
          }
          let d = Math.hypot(dx, dy);
          if (d < 1) {
            dx = player.vx;
            dy = player.vy;
            d = Math.hypot(dx, dy);
          }
          if (d < 1) {
            const a = Math.random() * Math.PI * 2;
            return { x: Math.cos(a), y: Math.sin(a) };
          }
          return { x: dx / d, y: dy / d };
        }

        function doSplit() {
          if (mode !== 'play' || gameOver) return;
          if (splitCooldown > 0) return;
          const maxCells = cfg.mechanics.maxCells || 12;
          const minMass = cfg.mechanics.splitMinMass || 36;
          const mergeTime = cfg.mechanics.mergeTime || 10;
          const boost = cfg.mechanics.splitBoost || 760;
          if (playerCells.length >= maxCells) return;
          const additions = [];
          for (const c of playerCells) {
            if (playerCells.length + additions.length >= maxCells) break;
            if (c.mass < minMass) continue;
            const dir = aimDir(c.x, c.y);
            c.mass = c.mass / 2;
            c.mergeTimer = mergeTime;
            const r = massToRadius(c.mass);
            const nc = makePlayerCell(c.x + dir.x * r * 0.6, c.y + dir.y * r * 0.6, c.mass, mergeTime);
            nc.vx = c.vx;
            nc.vy = c.vy;
            nc.bx = dir.x * boost;
            nc.by = dir.y * boost;
            additions.push(nc);
          }
          if (!additions.length) return;
          for (const nc of additions) playerCells.push(nc);
          splitCooldown = 0.3;
          stats.splits++;
          unlock('split', 'First split — nice mitosis!');
          playSplitSound();
        }

        function doEject() {
          if (mode !== 'play' || gameOver) return;
          if (ejectCooldown > 0) return;
          const pelletMass = cfg.mechanics.ejectMass || 12;
          const cost = pelletMass * 1.25;
          let fired = false;
          for (const c of playerCells) {
            if (c.mass - cost < 18) continue;
            const dir = aimDir(c.x, c.y);
            c.mass -= cost;
            const r = massToRadius(c.mass);
            ejected.push({
              x: c.x + dir.x * (r + 4),
              y: c.y + dir.y * (r + 4),
              vx: dir.x * 640 + c.vx * 0.25,
              vy: dir.y * 640 + c.vy * 0.25,
              mass: pelletMass,
              color: currentPlayerColor(),
              life: 0,
            });
            fired = true;
          }
          if (fired) {
            ejectCooldown = 0.16;
            stats.ejects++;
            unlock('feed', 'Fed the arena with mass!');
            playEjectSound();
          }
        }

        function updatePlayerCells(dt) {
          const arenaHalf = cfg.arena.size / 2;
          let speedMult = cfg.player.speedMultiplier !== undefined ? cfg.player.speedMultiplier : 1;
          speedMult *= 1 + (level - 1) * 0.03;              // level progression bonus
          if (dash.active) speedMult *= cfg.abilities.dashSpeed || 2.3;
          if (surge.active) speedMult *= cfg.abilities.surgeSpeed || 1.7;
          if (effects.speed > 0) speedMult *= 1.35;
          const startMass = cfg.player.startSize || 20;

          // Keyboard steering (arrows / A S D) overrides the pointer target
          if (keyDir.x !== 0 || keyDir.y !== 0) {
            targetPos.x = player.x + keyDir.x * 420;
            targetPos.y = player.y + keyDir.y * 420;
            targetPos.active = true;
          }
          if (splitCooldown > 0) splitCooldown -= dt;
          if (ejectCooldown > 0) ejectCooldown -= dt;

          for (const c of playerCells) {
            if (c.mergeTimer > 0) c.mergeTimer -= dt;
            if (targetPos.active) {
              const dx = targetPos.x - c.x;
              const dy = targetPos.y - c.y;
              const dist = Math.hypot(dx, dy);
              if (dist > 1) {
                const baseSpeed = 310 * speedMult;
                const massRatio = Math.max(1, c.mass / startMass);
                const massSpeed = baseSpeed / Math.pow(massRatio, 0.42);
                const clampedSpeed = Math.max(70 * speedMult, massSpeed);
                const moveSpeed = Math.min(dist * 3.4, clampedSpeed);
                c.vx += ((dx / dist) * moveSpeed - c.vx) * Math.min(1, dt * 5.5);
                c.vy += ((dy / dist) * moveSpeed - c.vy) * Math.min(1, dt * 5.5);
              }
            } else {
              c.vx *= Math.pow(0.05, dt);
              c.vy *= Math.pow(0.05, dt);
            }

            c.x += (c.vx + c.bx) * dt;
            c.y += (c.vy + c.by) * dt;
            c.bx *= Math.pow(0.015, dt);
            c.by *= Math.pow(0.015, dt);

            const d = Math.hypot(c.x, c.y);
            if (d > arenaHalf) {
              const push = (d - arenaHalf) * 2.5;
              c.x -= (c.x / d) * push * dt;
              c.y -= (c.y / d) * push * dt;
            }
          }

          // Merge cells back together when their timer is up, otherwise keep them apart
          for (let i = playerCells.length - 1; i >= 0; i--) {
            for (let j = i - 1; j >= 0; j--) {
              const a = playerCells[i];
              const b = playerCells[j];
              if (!a || !b) continue;
              const dx = b.x - a.x;
              const dy = b.y - a.y;
              const d = Math.hypot(dx, dy) || 0.001;
              const ra = massToRadius(a.mass);
              const rb = massToRadius(b.mass);
              if (d >= ra + rb) continue;

              if (a.mergeTimer <= 0 && b.mergeTimer <= 0 && d < Math.max(ra, rb) * 0.85) {
                const big = a.mass >= b.mass ? a : b;
                const small = big === a ? b : a;
                big.mass += small.mass;
                const idx = playerCells.indexOf(small);
                if (idx >= 0) playerCells.splice(idx, 1);
                break;
              }

              const overlap = ra + rb - d;
              const nx = dx / d;
              const ny = dy / d;
              const strength = Math.min(1, dt * 6);
              a.x -= nx * overlap * 0.5 * strength;
              a.y -= ny * overlap * 0.5 * strength;
              b.x += nx * overlap * 0.5 * strength;
              b.y += ny * overlap * 0.5 * strength;
            }
          }
        }

        // --- Ejected mass pellets ---
        function updateEjected(dt) {
          const arenaHalf = cfg.arena.size / 2;
          for (let i = ejected.length - 1; i >= 0; i--) {
            const e = ejected[i];
            e.life += dt;
            e.x += e.vx * dt;
            e.y += e.vy * dt;
            e.vx *= Math.pow(0.02, dt);
            e.vy *= Math.pow(0.02, dt);

            const d = Math.hypot(e.x, e.y);
            if (d > arenaHalf) {
              e.x = (e.x / d) * arenaHalf;
              e.y = (e.y / d) * arenaHalf;
              e.vx *= -0.4;
              e.vy *= -0.4;
            }

            let eaten = false;

            // Feed a virus: enough pellets and it shoots out a brand new virus
            for (const v of viruses) {
              if (Math.hypot(v.x - e.x, v.y - e.y) < massToRadius(v.mass)) {
                v.fed += 1;
                v.flash = 1;
                const sp = Math.hypot(e.vx, e.vy);
                if (sp > 1) v.feedDir = { x: e.vx / sp, y: e.vy / sp };
                eaten = true;
                break;
              }
            }

            // Rivals snack on free-floating mass
            if (!eaten) {
              for (const a of aiCells) {
                if (Math.hypot(a.x - e.x, a.y - e.y) < massToRadius(a.mass)) {
                  a.mass += e.mass * 0.85;
                  a.flash = 0.5;
                  eaten = true;
                  break;
                }
              }
            }

            // Own cells can reclaim their pellets after a short delay
            if (!eaten && e.life > 0.55) {
              for (const c of playerCells) {
                if (Math.hypot(c.x - e.x, c.y - e.y) < massToRadius(c.mass)) {
                  c.mass = Math.min(Number(cfg.mechanics.maxMass) || 600000, c.mass + e.mass * 0.85);
                  eaten = true;
                  break;
                }
              }
            }

            if (eaten || e.life > 24) ejected.splice(i, 1);
          }
        }

        // --- Viruses ---
        function spawnVirus(opts) {
          const o = opts || {};
          const arenaHalf = cfg.arena.size / 2;
          const angle = Math.random() * Math.PI * 2;
          const dist = (0.2 + Math.random() * 0.75) * arenaHalf;
          return {
            x: o.x !== undefined ? o.x : Math.cos(angle) * dist,
            y: o.y !== undefined ? o.y : Math.sin(angle) * dist,
            vx: o.vx || 0,
            vy: o.vy || 0,
            mass: o.mass !== undefined ? o.mass : cfg.mechanics.virusMass || 100,
            fed: 0,
            feedDir: { x: 1, y: 0 },
            spin: Math.random() * Math.PI * 2,
            spinSpeed: (Math.random() - 0.5) * 0.7,
            flash: 0,
          };
        }

        function initViruses() {
          viruses = [];
          const n = Math.max(0, Math.round(cfg.mechanics.virusCount || 0));
          for (let i = 0; i < n; i++) viruses.push(spawnVirus());
        }

        function updateViruses(dt) {
          const arenaHalf = cfg.arena.size / 2;
          for (const v of viruses) {
            v.spin += v.spinSpeed * dt;
            if (v.flash > 0) v.flash = Math.max(0, v.flash - dt * 2.5);
            v.x += v.vx * dt;
            v.y += v.vy * dt;
            v.vx *= Math.pow(0.08, dt);
            v.vy *= Math.pow(0.08, dt);
            const d = Math.hypot(v.x, v.y);
            if (d > arenaHalf) {
              v.x = (v.x / d) * arenaHalf;
              v.y = (v.y / d) * arenaHalf;
              v.vx = 0;
              v.vy = 0;
            }
          }

          // Well-fed viruses spit out a new one in the feeding direction
          for (let i = viruses.length - 1; i >= 0; i--) {
            const v = viruses[i];
            if (v.fed >= 7) {
              v.fed = 0;
              const dir = v.feedDir || { x: 1, y: 0 };
              const vr = massToRadius(v.mass);
              viruses.push(
                spawnVirus({
                  x: v.x + dir.x * vr * 1.3,
                  y: v.y + dir.y * vr * 1.3,
                  vx: dir.x * 520,
                  vy: dir.y * 520,
                })
              );
            }
          }

          // Keep the population close to the configured count
          const want = Math.max(0, Math.round(cfg.mechanics.virusCount || 0));
          while (viruses.length < want) viruses.push(spawnVirus());
          while (viruses.length > want + 6) viruses.pop();
        }

        function popCellOnVirus(c, virus) {
          const maxCells = cfg.mechanics.maxCells || 12;
          const mergeTime = cfg.mechanics.mergeTime || 10;
          const room = Math.max(0, maxCells - playerCells.length);
          const pieces = Math.min(7, room);
          const each = c.mass / (pieces + 1);
          c.mass = each;
          c.mergeTimer = mergeTime;
          for (let i = 0; i < pieces; i++) {
            const a = (Math.PI * 2 * i) / pieces + Math.random() * 0.4;
            const nc = makePlayerCell(c.x, c.y, each, mergeTime);
            nc.bx = Math.cos(a) * 520;
            nc.by = Math.sin(a) * 520;
            playerCells.push(nc);
          }
          shockwaves.push({
            x: virus.x,
            y: virus.y,
            radius: massToRadius(virus.mass),
            maxRadius: massToRadius(virus.mass) * 3 + 60,
            progress: 0,
            color: '#33ff66',
          });
          stats.pops++;
          shakeScreen(9, 0.3);
          notify('☣ Popped on a virus!', '#33ff66');
          unlock('virus', 'Burst open on a virus!');
          playAbsorbSound();
        }

        function updateVirusCollisions() {
          for (let vi = viruses.length - 1; vi >= 0; vi--) {
            const v = viruses[vi];
            const vR = massToRadius(v.mass);
            let consumed = false;

            for (let i = playerCells.length - 1; i >= 0; i--) {
              const c = playerCells[i];
              if (c.mass <= v.mass * 1.12) continue;
              if (Math.hypot(c.x - v.x, c.y - v.y) < massToRadius(c.mass) * 0.85) {
                popCellOnVirus(c, v);
                consumed = true;
                break;
              }
            }
            if (consumed) {
              viruses.splice(vi, 1);
              continue;
            }

            // Rival cells burst on viruses too
            for (let i = aiCells.length - 1; i >= 0; i--) {
              const a = aiCells[i];
              if (a.mass <= v.mass * 1.12) continue;
              if (Math.hypot(a.x - v.x, a.y - v.y) < massToRadius(a.mass) * 0.85) {
                const maxAI = Math.min(100, Math.max(1, Math.round(cfg.ai.count || 0)) * 3);
                const pieces = Math.min(3, Math.max(0, maxAI - aiCells.length));
                const each = a.mass / (pieces + 1);
                a.mass = each;
                a.flash = 1;
                for (let k = 0; k < pieces; k++) {
                  const ang = Math.random() * Math.PI * 2;
                  const nb = spawnAICell({ mass: each });
                  nb.x = a.x + Math.cos(ang) * 20;
                  nb.y = a.y + Math.sin(ang) * 20;
                  nb.vx = Math.cos(ang) * 260;
                  nb.vy = Math.sin(ang) * 260;
                  nb.color = a.color;
                  nb.name = a.name;
                  aiCells.push(nb);
                }
                shockwaves.push({
                  x: v.x,
                  y: v.y,
                  radius: vR,
                  maxRadius: vR * 3 + 50,
                  progress: 0,
                  color: '#33ff66',
                });
                consumed = true;
                break;
              }
            }
            if (consumed) viruses.splice(vi, 1);
          }
        }

        let scoreBumpTimer = null;
        let lastScoreVal = -1;
        function bumpScore() {
          scoreEl.classList.add('bump');
          if (scoreBumpTimer) clearTimeout(scoreBumpTimer);
          scoreBumpTimer = setTimeout(() => {
            scoreEl.classList.remove('bump');
          }, 140);
        }
        function updateScoreDisplay() {
          const val = Math.floor(player.mass);
          if (val !== lastScoreVal) {
            lastScoreVal = val;
            scoreEl.textContent = val;
          }
        }

        function triggerAbsorption(p, playerR, cell) {
          playAbsorbSound();
          bumpScore();

          // Dissolve effect: particle is drawn inward toward player center with dissolving sparkles
          const startX = p.x;
          const startY = p.y;
          const pRadius = massToRadius(p.mass);
          const color = cfg.visual.particleColor || '#00f0ff';
          const pColor = currentPlayerColor();

          dissolvingEffects.push({
            x: startX,
            y: startY,
            target: cell || player,
            startR: pRadius,
            currentR: pRadius,
            progress: 0,
            duration: 0.35,
            color: color,
            motes: Array.from({ length: 7 }, () => ({
              angle: Math.random() * Math.PI * 2,
              dist: Math.random() * pRadius * 1.5,
              speed: 20 + Math.random() * 40,
              size: 1.5 + Math.random() * 2.5,
              life: 1,
              decay: 1.5 + Math.random() * 2,
            })),
          });

          // Cell growth pulse
          growthPulse = Math.min(growthPulse + 0.45, 1.2);

          // Growth shockwave ripple
          const src = cell || player;
          shockwaves.push({
            x: src.x,
            y: src.y,
            radius: playerR * 0.9,
            maxRadius: playerR * 1.6 + 25,
            progress: 0,
            color: pColor,
          });
        }

        function resetGame() {
          const spawnMass = Math.min(Number(cfg.mechanics.maxMass) || 600000, (cfg.player.startSize || 20) * shopStartMassMult());
          player.x = 0;
          player.y = 0;
          player.mass = spawnMass;
          player.vx = 0;
          player.vy = 0;
          playerCells = [makePlayerCell(0, 0, spawnMass, 0)];
          ejected = [];
          peakMass = player.mass;
          splitCooldown = 0;
          ejectCooldown = 0;
          growthPulse = 0;
          targetPos.active = false;
          gameOver = false;
          camera.x = 0;
          camera.y = 0;
          const zoomMultiplier = (cfg.camera && cfg.camera.zoomLevel !== undefined) ? cfg.camera.zoomLevel : 1.0;
          camera.zoom = zoomMultiplier;
          initParticles();
          initAI();
          initViruses();
          scoreEl.textContent = Math.floor(player.mass);
          gameOverEl.classList.remove('visible');
          if (vignetteOverlay) vignetteOverlay.classList.remove('active');
          canvas.classList.remove('desaturated');
          if (actionsEl) actionsEl.style.display = mode === 'play' ? 'flex' : 'none';

          // Reset the new systems
          dash = { active: false, timer: 0, cooldown: 0 };
          godMode = { active: false, timer: 0, cooldown: 0 };
          effects = { speed: 0, magnet: 0, shield: 0, x2: 0, freeze: 0 };
          if (mode === 'play' && shopShieldStart()) effects.shield = 14;
          powerups = [];
          arcadeCores = [];
          initZones();
          zoneCaptures = 0;
          zoneBonusTimer = 0;
          pulseWaves = [];
          surge = { active: false, timer: 0, cooldown: 0, radius: 0 };
          pulse = { active: false, timer: 0, cooldown: 0, radius: 0 };
          trails = [];
          combo = 0;
          comboTimer = 0;
          bestCombo = 0;
          level = 1;
          xp = 0;
          xpNext = 60;
          myRank = 99;
          stats = { particles: 0, rivals: 0, splits: 0, ejects: 0, dashes: 0, gods: 0, pulses: 0, surges: 0, pops: 0, powerups: 0, zones: 0, bestRank: 99 };
          achievements = {};
          survivalTime = 0;
          userZoom = 1;
          paused = false;
          helpOpen = false;
          shake = { t: 0, mag: 0 };
          botChatTimer = 6;
          if (pauseOverlay) pauseOverlay.classList.remove('visible');
          if (helpOverlay) helpOverlay.classList.remove('visible');
          if (notifFeedEl) notifFeedEl.innerHTML = '';
          if (effectStripEl) effectStripEl.innerHTML = '';
          if (runStatsEl) runStatsEl.innerHTML = '';

          const showPlayUi = mode === 'play';
          if (statStripEl) statStripEl.style.display = showPlayUi ? 'flex' : 'none';
          if (abilityHudEl) abilityHudEl.style.display = showPlayUi ? 'flex' : 'none';
          autoRespawnTimer = 0;
          aiFrozen = false;
          applySettings();
          updateProfileChip();
          updatePowerups(0);
          updateAbilityHud();
          renderMinimap();
        }

        async function triggerGameOver() {
          if (gameOver) return;
          gameOver = true;
          player.vx = 0;
          player.vy = 0;
          targetPos.active = false;
          const finalMass = Math.floor(Math.max(peakMass, player.mass));
          finalScoreEl.textContent = finalMass;
          const mm = Math.floor(survivalTime / 60);
          const ss = Math.floor(survivalTime % 60);
          if (runStatsEl) {
            runStatsEl.innerHTML =
              '<div>Time <b>' + (mm < 10 ? '0' : '') + mm + ':' + (ss < 10 ? '0' : '') + ss + '</b></div>' +
              '<div>Level <b>' + level + '</b></div>' +
              '<div>Best rank <b>#' + (stats.bestRank === 99 ? '-' : stats.bestRank) + '</b></div>' +
              '<div>Particles <b>' + stats.particles + '</b></div>' +
              '<div>Rivals <b>' + stats.rivals + '</b></div>' +
              '<div>Best combo <b>x' + bestCombo + '</b></div>' +
              '<div>Splits <b>' + stats.splits + '</b></div>' +
              '<div>Dashes <b>' + stats.dashes + '</b></div>' +
              '<div>Power-ups <b>' + stats.powerups + '</b></div>' +
              '<div>Pulses <b>' + stats.pulses + '</b></div>' +
              '<div>Surges <b>' + stats.surges + '</b></div>' +
              '<div>Zones <b>' + stats.zones + '</b></div>';
          }
          addChatMsg('', 'You were devoured with ' + finalMass + ' mass. Press R to respawn.', 'sys');
          recordRunToProfile(finalMass);

          // ZeroCoin payout for the run
          const earnedZc = Math.max(10, Math.round(
            (finalMass / 8 + stats.rivals * 6 + survivalTime * 0.8 + stats.particles * 0.15) * shopCoinMult()
          ));
          addCoins(earnedZc, 'Partita Zero Arcade');
          notify('\u{1FA99} +' + earnedZc + ' ZeroCoin', '#ffc94a');
          questProgress('run', 1);
          questProgress('time', Math.round(survivalTime));
          addPassXp(Math.round(finalMass / 4 + survivalTime));
          if (!Array.isArray(profile.history)) profile.history = [];
          profile.history.unshift({ m: finalMass, t: Math.round(survivalTime), c: earnedZc, d: Date.now() });
          if (profile.history.length > 10) profile.history.length = 10;
          if (runStatsEl) {
            runStatsEl.innerHTML += '<div>ZeroCoin <b>+' + earnedZc + '</b></div>';
          }

          gameOverEl.classList.add('visible');
          if (vignetteOverlay && S.vignette) vignetteOverlay.classList.add('active');
          canvas.classList.add('desaturated');
          if (actionsEl) actionsEl.style.display = 'none';

          window.__zaRunStats = { rivals: stats.rivals, particles: stats.particles, time: Math.round(survivalTime) };

          // Submit score and fetch global leaderboard (only in play mode)
          if (mode === 'play' && !window.__growthOrbitStandalone) {
            try {
              globalLbListEl.textContent = 'Loading...';
              
              // Submit score and get leaderboard
              const response = await lib.addPlayerScoreToLeaderboard(finalMass, 10);
              
              // Fetch fresh entries (in case addPlayerScoreToLeaderboard doesn't return them)
              const lbData = await lib.getTopNEntriesFromLeaderboard(10);
              
              // Render the global leaderboard
              renderGlobalLeaderboard(lbData.entries, lbData.userRank);
              
              // Update ghost rivals with fresh leaderboard data
              if (lbData.entries && lbData.entries.length > 0) {
                window.applyGhostRivals(lbData.entries);
              }
            } catch (error) {
              lib.log(`Failed to fetch global leaderboard: ${error.message}`);
              globalLbListEl.textContent = 'No scores yet';
            }
          }
        }

        function renderGlobalLeaderboard(entries, userRank) {
          if (!entries || entries.length === 0) {
            globalLbListEl.textContent = 'No scores yet';
            return;
          }

          let html = '';
          entries.forEach((entry, idx) => {
            const rank = idx + 1;
            const isCurrentUser = userRank && rank === userRank;
            const name = entry.username || entry.name || entry.playerName || entry.player || 'Anonymous';
            const score = Math.floor(entry.score || 0);
            
            html += `<div class="glb-row${isCurrentUser ? ' highlight' : ''}">
              <span class="glb-row-name">${rank}. ${escapeHtml(name)}</span>
              <span class="glb-row-score">${score}</span>
            </div>`;
          });
          
          globalLbListEl.innerHTML = html;
        }

        restartBtn.addEventListener('click', (e) => {
          e.preventDefault();
          resetGame();
        });
        restartBtn.addEventListener('touchstart', (e) => {
          e.preventDefault();
          resetGame();
        }, { passive: false });

        resetGame();

        // Load this player's saved profile (progress, settings, skin), then apply it
        applySettings();
        updateProfileChip();
        (async () => {
          try { await loadProfile(); } catch (e) {}
          S = profile.settings;
          playerColorOverride = profile.skinColor || null;
          applySkinDataUrl(profile.skin, profile.skinKind);
          normalizeProfileData();
          applySettings();
          updateProfileChip();
          updateWalletUi();
          if (userMenuOpen) renderUserMenu();
          if (portalOpen) renderPortal();
        })();

        if (mode === 'play') {
          addChatMsg('', 'Arena channel joined — press C or T to chat.', 'sys');
          addChatMsg('', 'ESC = menu utente · /help per i comandi chat.', 'sys');
          addChatMsg(randomRivalName(), 'fresh meat just spawned 😏');
          setChatOpen(false);
          notify('Press H for the control list', '#00f0ff');
        }

        // Fetch initial leaderboard for ghost rivals (only in play mode)
        if (mode === 'play' && !window.__growthOrbitStandalone) {
          (async () => {
            try {
              const lbData = await lib.getTopNEntriesFromLeaderboard(10);
              if (lbData.entries && lbData.entries.length > 0) {
                window.applyGhostRivals(lbData.entries);
              }
            } catch (error) {
              lib.log(`Failed to fetch initial leaderboard for ghost rivals: ${error.message}`);
            }
          })();
        }

        // --- Input handling ---
        function screenToWorld(sx, sy) {
          return {
            x: (sx - canvas.width / 2) / camera.zoom + camera.x,
            y: (sy - canvas.height / 2) / camera.zoom + camera.y,
          };
        }

        canvas.addEventListener('mousemove', (e) => {
          initAudio();
          if (mode === 'edit') return;
          const rect = canvas.getBoundingClientRect();
          const sx = (e.clientX - rect.left) * (canvas.width / rect.width);
          const sy = (e.clientY - rect.top) * (canvas.height / rect.height);
          const w = screenToWorld(sx, sy);
          targetPos.x = w.x;
          targetPos.y = w.y;
          targetPos.active = true;
        });

        canvas.addEventListener('touchstart', handleTouch, { passive: false });
        canvas.addEventListener('touchmove', handleTouch, { passive: false });
        canvas.addEventListener('touchend', () => {
          if (mode !== 'edit') targetPos.active = false;
        });

        // --- Split / Eject controls ---

        function bindActionButton(btn, fn) {
          if (!btn) return;
          let hold = null;
          const stop = () => {
            if (hold) {
              clearInterval(hold);
              hold = null;
            }
          };
          const start = (e) => {
            e.preventDefault();
            e.stopPropagation();
            initAudio();
            fn();
            stop();
            hold = setInterval(fn, 170);
          };
          btn.addEventListener('touchstart', start, { passive: false });
          btn.addEventListener('mousedown', start);
          btn.addEventListener('touchend', stop);
          btn.addEventListener('touchcancel', stop);
          btn.addEventListener('mouseup', stop);
          btn.addEventListener('mouseleave', stop);
          window.addEventListener('mouseup', stop);
          window.addEventListener('blur', stop);
        }
        bindActionButton(splitBtn, doSplit);
        bindActionButton(ejectBtn, doEject);

        bindActionButton(dashBtn, tryDash);
        bindActionButton(godBtn, tryGod);
        bindActionButton(pulseBtn, tryPulse);
        bindActionButton(surgeBtn, trySurge);

        function isTyping() {
          const el = document.activeElement;
          return !!el && (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA');
        }

        function updateKeyDir() {
          let x = 0, y = 0;
          if (keysDown['ArrowLeft'] || keysDown['KeyA']) x -= 1;
          if (keysDown['ArrowRight'] || keysDown['KeyD']) x += 1;
          if (keysDown['ArrowUp']) y -= 1;
          if (keysDown['ArrowDown'] || keysDown['KeyS']) y += 1;
          const l = Math.hypot(x, y);
          if (l > 0) { x /= l; y /= l; }
          if (S.invertKeys) { x = -x; y = -y; }
          keyDir.x = x;
          keyDir.y = y;
        }

        function zoomBy(factor) {
          const sens = S.zoomSens !== undefined ? S.zoomSens : 1;
          const adj = Math.pow(factor, sens);
          userZoom = Math.max(0.2, Math.min(2.6, userZoom * adj));
        }

        window.addEventListener('keydown', (e) => {
          if (isTyping()) {
            if (e.code === 'Escape') {
              if (chatInputEl) chatInputEl.blur();
              setChatOpen(false);
            }
            return;
          }

          // Portal shell captures keys while it is open
          if (portalOpen) {
            if (e.code === 'Escape' || e.code === 'KeyQ') {
              e.preventDefault();
              if (userMenuOpen) closeUserMenu();
              else closePortal();
            }
            return;
          }

          // ESC toggles the user menu (profile, settings, skins, staff tools)
          if (e.code === 'Escape') {
            e.preventDefault();
            if (helpOpen) toggleHelp(false);
            else if (paused) togglePause(false);
            else toggleUserMenu();
            return;
          }
          if (userMenuOpen) return;
          if (mode !== 'play') return;

          if (e.code === 'KeyT') {
            e.preventDefault();
            setChatOpen(true);
            setTimeout(() => { if (chatInputEl) chatInputEl.focus(); }, 40);
            return;
          }

          keysDown[e.code] = true;
          if (hasPerm('admin')) {
            if (e.code === String(S.botFollowKey || 'F7')) { e.preventDefault(); adminSetBotMode('follow'); return; }
            if (e.code === String(S.botFarmKey || 'F8')) { e.preventDefault(); adminSetBotMode('farm'); return; }
            if (e.code === String(S.botStopKey || 'F9')) { e.preventDefault(); adminSetBotMode('idle'); return; }
          }
          switch (e.code) {
            case 'Space':
              e.preventDefault(); initAudio(); doSplit(); break;
            case 'KeyW':
              e.preventDefault(); initAudio(); doEject(); break;
            case 'ShiftLeft':
            case 'ShiftRight':
              e.preventDefault(); initAudio(); tryDash(); break;
            case 'KeyG':
              e.preventDefault(); initAudio(); tryGod(); break;
            case 'KeyE':
              e.preventDefault(); initAudio(); tryPulse(); break;
            case 'KeyR':
              e.preventDefault(); initAudio(); trySurge(); break;
            case 'KeyP':
              e.preventDefault();
              if (helpOpen) toggleHelp(false);
              else togglePause();
              break;
            case 'KeyH':
              e.preventDefault(); toggleHelp(); break;
            case 'KeyC':
              e.preventDefault(); setChatOpen(!chatOpen); break;
            case 'KeyM':
              S.mute = !S.mute;
              applySettings();
              queueSave();
              notify(muted ? '\u{1F507} Audio muted' : '\u{1F50A} Audio on');
              break;
            case 'KeyF':
              S.showFps = !S.showFps;
              applySettings();
              queueSave();
              break;
            case 'KeyB':
              e.preventDefault(); toggleMusic(); break;
            case 'KeyN':
              e.preventDefault(); nextMusicTrack(); break;
            case 'KeyU':
              e.preventDefault(); toggleUserMenu(); break;
            case 'KeyQ':
              e.preventDefault(); openPortal(); break;
            case 'Tab':
              e.preventDefault(); overviewHold = true; break;
            case 'Equal':
            case 'NumpadAdd':
              zoomBy(1.15); break;
            case 'Minus':
            case 'NumpadSubtract':
              zoomBy(0.87); break;
            case 'Digit0':
              userZoom = 1; break;
          }
          updateKeyDir();
        });

        window.addEventListener('keyup', (e) => {
          keysDown[e.code] = false;
          if (e.code === 'Tab') overviewHold = false;
          updateKeyDir();
        });
        window.addEventListener('blur', () => {
          for (const k in keysDown) keysDown[k] = false;
          overviewHold = false;
          updateKeyDir();
        });

        // Mouse-wheel zoom (scroll out to see the whole arena)
        canvas.addEventListener('wheel', (e) => {
          e.preventDefault();
          zoomBy(e.deltaY > 0 ? 0.9 : 1.11);
        }, { passive: false });

        // Chat wiring
        if (chatToggleBtn) {
          chatToggleBtn.addEventListener('click', (e) => {
            e.preventDefault();
            setChatOpen(!chatOpen);
          });
        }
        if (chatFormEl) {
          chatFormEl.addEventListener('submit', (e) => {
            e.preventDefault();
            sendChat();
          });
        }
        const emoteBtns = document.querySelectorAll('.quick-emotes button');
        emoteBtns.forEach((b) => {
          b.addEventListener('click', (e) => {
            e.preventDefault();
            if (!chatInputEl) return;
            chatInputEl.value = b.dataset.emote || b.textContent;
            sendChat();
          });
        });
        if (resumeBtn) resumeBtn.addEventListener('click', (e) => { e.preventDefault(); togglePause(false); });
        if (helpCloseBtn) helpCloseBtn.addEventListener('click', (e) => { e.preventDefault(); toggleHelp(false); });

        function handleTouch(e) {
          initAudio();
          e.preventDefault();
          if (mode === 'edit') return;
          const t = e.touches[0];
          if (!t) return;
          const rect = canvas.getBoundingClientRect();
          const sx = (t.clientX - rect.left) * (canvas.width / rect.width);
          const sy = (t.clientY - rect.top) * (canvas.height / rect.height);
          const w = screenToWorld(sx, sy);
          targetPos.x = w.x;
          targetPos.y = w.y;
          targetPos.active = true;
        }

        // --- AI cells: hunt, flee, feed and grow ---
        function updateAI(dt) {
          const arenaHalf = cfg.arena.size / 2;
          const commanded = botCommandMode !== 'idle' ? aiCells.filter((a) => a.commanded) : [];
          const aggression = cfg.ai.aggression !== undefined ? cfg.ai.aggression : 0.6;
          const aiGrowth = cfg.ai.growthRate !== undefined ? cfg.ai.growthRate : 1;
          const baseSize = cfg.player.startSize || 20;

          for (let i = 0; i < aiCells.length; i++) {
            const a = aiCells[i];
            const aR = massToRadius(a.mass);
            a.pulse += dt * 2.2;
            if (a.flash > 0) a.flash = Math.max(0, a.flash - dt * 3);

            // Passive growth over time (they keep getting scarier)
            a.mass += aiGrowth * (0.35 + a.mass * 0.004) * dt;

            // --- Steering decision ---
            let preyX = 0, preyY = 0, preyScore = -Infinity;
            if (a.commanded && botCommandMode === 'follow') {
              const dx = player.x - a.x;
              const dy = player.y - a.y;
              const d = Math.hypot(dx, dy) || 1;
              preyX = dx / d;
              preyY = dy / d;
              preyScore = 999999;
            } else if (a.commanded && botCommandMode === 'farm') {
              let closest = null, closestD = Infinity;
              for (const p of particles) {
                const d = Math.hypot(p.x - a.x, p.y - a.y);
                if (d < closestD) { closestD = d; closest = p; }
              }
              if (closest) {
                preyX = (closest.x - a.x) / (closestD || 1);
                preyY = (closest.y - a.y) / (closestD || 1);
                preyScore = 999998;
              }
            }
            let fleeX = 0, fleeY = 0, threatWeight = 0;

            const rivals = [];
            for (let j = 0; j < aiCells.length; j++) {
              if (j !== i) rivals.push(aiCells[j]);
            }
            if (!gameOver) {
              for (const pc of playerCells) {
                rivals.push({ x: pc.x, y: pc.y, mass: pc.mass, isPlayer: true });
              }
            }

            const senseRange = aR * 9 + 420;
            for (const o of rivals) {
              const dx = o.x - a.x;
              const dy = o.y - a.y;
              const d = Math.hypot(dx, dy) + 0.001;
              if (d > senseRange) continue;
              if (o.mass > a.mass * 1.12) {
                // Danger: run away (braver cells flee later)
                const w = (1 - d / senseRange) * (1.6 - aggression * 0.8);
                fleeX -= (dx / d) * w;
                fleeY -= (dy / d) * w;
                threatWeight += w;
              } else if (a.mass > o.mass * 1.15) {
                let score = (o.mass * 3) / d;
                if (o.isPlayer) score *= 1 + aggression * 2.2;
                if (score > preyScore) {
                  preyScore = score;
                  preyX = dx / d;
                  preyY = dy / d;
                }
              }
            }

            // Viruses are deadly for big cells: keep a safe distance
            for (const v of viruses) {
              if (a.mass <= v.mass * 1.12) continue;
              const dx = v.x - a.x;
              const dy = v.y - a.y;
              const d = Math.hypot(dx, dy) + 0.001;
              if (d > aR + massToRadius(v.mass) + 180) continue;
              const w = 1.3;
              fleeX -= (dx / d) * w;
              fleeY -= (dy / d) * w;
              threatWeight += w;
            }

            // Look for nearby particles to munch
            let bestP = null, bestPD = Infinity;
            for (const p of particles) {
              const d = Math.hypot(p.x - a.x, p.y - a.y);
              if (d < senseRange * 0.75 && d < bestPD) {
                bestPD = d;
                bestP = p;
              }
            }
            if (bestP) {
              const score = (bestP.mass * 2.2) / (bestPD + 1);
              if (score > preyScore) {
                preyScore = score;
                preyX = (bestP.x - a.x) / (bestPD + 0.001);
                preyY = (bestP.y - a.y) / (bestPD + 0.001);
              }
            }

            // Wander noise keeps them alive-looking
            a.wander += (Math.random() - 0.5) * 3.2 * dt;
            let dirX = Math.cos(a.wander) * 0.35;
            let dirY = Math.sin(a.wander) * 0.35;

            if (preyScore > -Infinity) {
              dirX += preyX * (0.7 + aggression * 0.6);
              dirY += preyY * (0.7 + aggression * 0.6);
            }
            if (threatWeight > 0) {
              dirX += fleeX * 1.5;
              dirY += fleeY * 1.5;
            }

            // Steer back inside the arena
            const distO = Math.hypot(a.x, a.y);
            if (distO > arenaHalf * 0.9) {
              const w = (distO - arenaHalf * 0.9) / (arenaHalf * 0.1);
              dirX -= (a.x / distO) * w * 2;
              dirY -= (a.y / distO) * w * 2;
            }

            const dl = Math.hypot(dirX, dirY);
            if (dl > 0.001) {
              dirX /= dl;
              dirY /= dl;
            }

            const speed =
              300 * Math.pow(baseSize / Math.max(baseSize, a.mass), 0.45) * (0.7 + aggression * 0.5);
            a.vx += (dirX * speed - a.vx) * Math.min(1, dt * 3.2);
            a.vy += (dirY * speed - a.vy) * Math.min(1, dt * 3.2);
            a.x += a.vx * dt;
            a.y += a.vy * dt;

            // Hard arena clamp
            const d2 = Math.hypot(a.x, a.y);
            if (d2 > arenaHalf) {
              a.x = (a.x / d2) * arenaHalf;
              a.y = (a.y / d2) * arenaHalf;
            }

            // Eat particles
            for (let k = particles.length - 1; k >= 0; k--) {
              const p = particles[k];
              const dx = p.x - a.x;
              const dy = p.y - a.y;
              const maxDist = aR + 25;
              if (dx * dx + dy * dy > maxDist * maxDist) continue;
              if (Math.hypot(dx, dy) < aR + massToRadius(p.mass) * 0.4) {
                a.mass += p.mass * 0.3 * (cfg.player.growthRate || 1);
                a.flash = 0.6;
                particles.splice(k, 1);
                particles.push(spawnParticle());
              }
            }
          }

          // AI cells devour smaller AI cells
          for (let i = aiCells.length - 1; i >= 0; i--) {
            const a = aiCells[i];
            for (let j = aiCells.length - 1; j >= 0; j--) {
              if (i === j) continue;
              const b = aiCells[j];
              if (a.mass <= b.mass * 1.15) continue;
              const aR = massToRadius(a.mass);
              if (Math.hypot(b.x - a.x, b.y - a.y) < aR * 0.9) {
                a.mass += b.mass * 0.75;
                a.flash = 1;
                shockwaves.push({
                  x: b.x,
                  y: b.y,
                  radius: massToRadius(b.mass),
                  maxRadius: massToRadius(b.mass) * 2 + 30,
                  progress: 0,
                  color: b.color,
                });
                const desired = Math.max(0, Math.round(cfg.ai.count || 0));
                if (aiCells[j] && aiCells[j].commanded) {
                  aiCells[j].mass = Math.max(12, a.mass * 0.2);
                  aiCells[j].x = a.x + 24;
                  aiCells[j].y = a.y + 24;
                  aiCells[j].vx = 0;
                  aiCells[j].vy = 0;
                } else if (aiCells.length > desired) aiCells.splice(j, 1);
                else aiCells[j] = spawnAICell();
                break;
              }
            }
          }
        }

        function updateLeaderboard() {
          const rows = aiCells.map((a) => ({
            name: (a.isGhost ? '\u2605 ' : '') + a.name,
            mass: a.mass,
            me: false,
          }));
          rows.push({ name: profile.loggedIn ? profile.name : 'Guest', mass: player.mass, me: true });
          rows.sort((x, y) => y.mass - x.mass);
          const top = rows.slice(0, 5);
          myRank = rows.findIndex((r) => r.me) + 1;
          if (mode === 'play' && !gameOver) {
            if (player.mass >= (Number(cfg.mechanics.maxMass) || 600000)) unlock('masscap', 'Mass cap reached: 600000!');
            if (myRank < stats.bestRank) {
              stats.bestRank = myRank;
              if (myRank === 1) unlock('rank1', 'Apex cell: rank #1!');
              else if (myRank <= 3) unlock('rank3', 'Top 3 in the arena!');
            }
            if (player.mass >= 200) unlock('mass200', 'Mass 200 reached!');
            if (player.mass >= 500) unlock('mass500', 'Colossus: mass 500!');
            if (player.mass >= 1000) unlock('mass1000', 'Legend: mass 1000!');
          }
          let html = '';
          top.forEach((r, idx) => {
            html +=
              '<div class="lb-row' + (r.me ? ' me' : '') + '"><span>' + (idx + 1) + '. ' + r.name +
              '</span><span>' + Math.floor(r.mass) + '</span></div>';
          });
          if (myRank > 5) {
            html +=
              '<div class="lb-row me"><span>' + myRank + '. You</span><span>' +
              Math.floor(player.mass) + '</span></div>';
          }
          lbListEl.innerHTML = html;
        }

        // --- Update ---
        function update(dt) {
          const arenaHalf = cfg.arena.size / 2;
          globalTime += dt;

          // Optional hands-free respawn after death
          if (mode === 'play' && gameOver && S.autoRespawn) {
            autoRespawnTimer += dt;
            if (autoRespawnTimer > 3) {
              autoRespawnTimer = 0;
              resetGame();
            }
          }

          // Decay growth pulse
          if (growthPulse > 0) {
            growthPulse = Math.max(0, growthPulse - dt * 2.8);
          }

          if (mode === 'play' && !gameOver) {
            survivalTime += dt;
            if (survivalTime > 60) unlock('surv60', 'Survived a full minute!');
            updateAbilities(dt);
            updateBotChatter(dt);

            // Player blob group: movement, split boosts, recombination
            updatePlayerCells(dt);
            syncPlayerAggregate();

            // Collision with particles & absorption (per blob)
            for (const c of playerCells) {
              const cR = massToRadius(c.mass);
              const maxDist = cR + 25;
              const maxDistSq = maxDist * maxDist;
              for (let i = particles.length - 1; i >= 0; i--) {
                const p = particles[i];
                const dx = p.x - c.x;
                const dy = p.y - c.y;
                if (dx * dx + dy * dy > maxDistSq) continue;
                const dist = Math.hypot(dx, dy);
                if (dist < cR + massToRadius(p.mass) * 0.4) {
                  // Trigger visual absorption dissolve effect
                  triggerAbsorption(p, cR, c);

                  // Absorb mass (combo + double-mass power-up boost)
                  const gain = p.mass * 0.3 * (cfg.player.growthRate || 1) *
                    comboMultiplier() * (effects.x2 > 0 ? 2 : 1);
                  c.mass = Math.min(Number(cfg.mechanics.maxMass) || 600000, c.mass + gain);
                  addCombo();
                  stats.particles++;
                  questProgress('eat', 1);
                  addXp(gain * 1.6);
                  particles.splice(i, 1);
                  // Respawn elsewhere in arena
                  particles.push(spawnParticle());
                }
              }
            }

            // AI predators (slowed while FREEZE is active, halted by staff freeze)
            if (!aiFrozen) updateAI(effects.freeze > 0 ? dt * 0.25 : dt);

            // Ejected mass pellets and viruses
            updateEjected(dt);
            updateViruses(dt);
            updateVirusCollisions();

            // Power-ups, magnet pull, dash trail
            updatePowerups(dt);
            updateArcadeCores(dt);
            updateZones(dt);
            updatePulseWaves(dt);
            applyMagnet(dt);
            updateTrails(dt);

            // Player cells vs AI cells
            const desiredAICount = Math.min(100, Math.max(0, Math.round(cfg.ai.count || 0)));
            for (let i = aiCells.length - 1; i >= 0; i--) {
              const a = aiCells[i];
              const aR = massToRadius(a.mass);
              for (let k = playerCells.length - 1; k >= 0; k--) {
                const c = playerCells[k];
                const cR = massToRadius(c.mass);
                const dist = Math.hypot(a.x - c.x, a.y - c.y);
                const canEat = godMode.active || c.mass > a.mass * 1.15;
                const reach = godMode.active ? Math.max(cR, aR) * 0.95 : cR * 0.9;
                if (canEat && dist < reach) {
                  // This blob swallows a rival (god mode ignores size rules)
                  playAbsorbSound();
                  c.mass = Math.min(Number(cfg.mechanics.maxMass) || 600000, c.mass + a.mass * 0.8 * (cfg.player.growthRate || 1) * (effects.x2 > 0 ? 1.5 : 1));
                  growthPulse = Math.min(growthPulse + 0.8, 1.4);
                  stats.rivals++;
                  questProgress('kill', 1);
                  addXp(a.mass * 0.8);
                  shakeScreen(7, 0.25);
                  notify('☠ You devoured ' + a.name, a.color);
                  if (stats.rivals === 1) unlock('kill1', 'First rival devoured!');
                  if (stats.rivals >= 10) unlock('kill10', 'Serial predator: 10 rivals!');
                  shockwaves.push({
                    x: a.x,
                    y: a.y,
                    radius: aR,
                    maxRadius: aR * 2.4 + 40,
                    progress: 0,
                    color: a.color,
                  });
                  if (aiCells.length > desiredAICount) aiCells.splice(i, 1);
                  else aiCells[i] = spawnAICell();
                  break;
                } else if (a.mass > c.mass * 1.15 && dist < aR * 0.9) {
                  if (godMode.active) continue;
                  if (effects.shield > 0) {
                    // Shield power-up absorbs the hit and knocks the rival back
                    effects.shield = 0;
                    const kd = Math.max(1, dist);
                    a.vx += ((a.x - c.x) / kd) * 520;
                    a.vy += ((a.y - c.y) / kd) * 520;
                    notify('🛡 Shield absorbed the hit!', '#7fd4ff');
                    shakeScreen(10, 0.3);
                    unlock('shieldsave', 'Saved by the shield!');
                    continue;
                  }
                  // A rival devours one of your blobs
                  shakeScreen(12, 0.35);
                  a.mass += c.mass * 0.85;
                  a.flash = 1;
                  shockwaves.push({
                    x: c.x,
                    y: c.y,
                    radius: cR,
                    maxRadius: cR * 3 + 60,
                    progress: 0,
                    color: '#ff3b6b',
                  });
                  playerCells.splice(k, 1);
                }
              }
            }

            syncPlayerAggregate();
            if (playerCells.length === 0 && !tryReviveWithToken()) {
              triggerGameOver();
            }

            // Update particles drift & sparkle animation
            for (const p of particles) {
              p.x += p.vx * dt;
              p.y += p.vy * dt;
              p.rotation += p.rotSpeed * dt;
              p.phase += dt * p.sparkleSpeed;

              // Gentle direction steering
              p.vx += (Math.random() - 0.5) * 25 * dt;
              p.vy += (Math.random() - 0.5) * 25 * dt;
              const spd = Math.sqrt(p.vx * p.vx + p.vy * p.vy);
              if (spd > 32) {
                p.vx *= 32 / spd;
                p.vy *= 32 / spd;
              }
              // Boundary rebound
              const d = Math.sqrt(p.x * p.x + p.y * p.y);
              if (d > arenaHalf) {
                p.vx -= (p.x / d) * 60 * dt;
                p.vy -= (p.y / d) * 60 * dt;
              }
            }

          }

          if (mode === 'play') {
            // Update dissolving effects
            for (let i = dissolvingEffects.length - 1; i >= 0; i--) {
              const eff = dissolvingEffects[i];
              eff.progress += dt / eff.duration;
              // Smooth suction toward player center
              const t = eff.progress;
              const tgt = eff.target || player;
              eff.x += (tgt.x - eff.x) * Math.min(1, dt * 12);
              eff.y += (tgt.y - eff.y) * Math.min(1, dt * 12);
              eff.currentR = eff.startR * (1 - t * 0.85);

              // Update motes
              for (const m of eff.motes) {
                m.dist += m.speed * dt;
                m.life -= m.decay * dt;
              }

              if (eff.progress >= 1) {
                dissolvingEffects.splice(i, 1);
              }
            }

            // Update shockwaves
            for (let i = shockwaves.length - 1; i >= 0; i--) {
              const sw = shockwaves[i];
              sw.progress += dt * 3.5;
              if (sw.progress >= 1) {
                shockwaves.splice(i, 1);
              }
            }

            // Update HUD
            updateScoreDisplay();

            hudTimer += dt;
            if (hudTimer > 0.1) {
              hudTimer = 0;
              updateAbilityHud();
            }

            miniTimer += dt;
            if (miniTimer > 0.09) {
              miniTimer = 0;
              renderMinimap();
            }

            lbTimer += dt;
            if (lbTimer > 0.25) {
              lbTimer = 0;
              updateLeaderboard();
            }
          }

          // Camera follow & smooth zoom
          const camLerp = S.cameraSmooth || 4.5;
          camera.x += (player.x - camera.x) * Math.min(1, dt * camLerp);
          camera.y += (player.y - camera.y) * Math.min(1, dt * camLerp);
          const zoomMultiplier = (cfg.camera && cfg.camera.zoomLevel !== undefined) ? cfg.camera.zoomLevel : 1.0;
          const startMass = cfg.player.startSize || 20;
          const massZoomScale = 1.0 / Math.pow(Math.max(1, player.mass / startMass), 0.32);
          // Zoom range: from fully zoomed-in to the whole arena on screen (wheel / TAB)
          const wholeMapZoom = Math.min(canvas.width, canvas.height) / (cfg.arena.size + 120);
          let targetZoom = zoomMultiplier * massZoomScale * userZoom;
          // Keep every split blob comfortably in frame
          let spread = 0;
          for (const c of playerCells) {
            spread = Math.max(spread, Math.hypot(c.x - player.x, c.y - player.y) + massToRadius(c.mass));
          }
          if (spread > 0) {
            const fitZoom = (Math.min(canvas.width, canvas.height) * 0.42) / spread;
            targetZoom = Math.min(targetZoom, fitZoom);
          }
          if (overviewHold) targetZoom = wholeMapZoom;
          targetZoom = Math.max(wholeMapZoom * 0.92, Math.min(2.6, targetZoom));
          camera.zoom += (targetZoom - camera.zoom) * Math.min(1, dt * (overviewHold ? 5.5 : 2.8));
        }

        // --- Render Helpers ---
        function drawParallaxGrid(w, h, arenaHalf, viewLeft, viewRight, viewTop, viewBottom) {
          const gridColorHex = cfg.visual.gridColor || '#1a3a52';

          // Layer 1: Distant micro-grid with smooth ambient parallax motion (single batched stroke)
          const driftX = (globalTime * 8) % 70;
          const driftY = (globalTime * 5) % 70;
          const deepSpacing = 70;
          const deepParallax = 0.35;

          const deepLeft = camera.x * deepParallax - (w / 2) / camera.zoom - driftX;
          const deepRight = camera.x * deepParallax + (w / 2) / camera.zoom + driftX;
          const deepTop = camera.y * deepParallax - (h / 2) / camera.zoom - driftY;
          const deepBottom = camera.y * deepParallax + (h / 2) / camera.zoom + driftY;

          const deepStartX = Math.floor(deepLeft / deepSpacing) * deepSpacing;
          const deepStartY = Math.floor(deepTop / deepSpacing) * deepSpacing;

          ctx.save();
          ctx.strokeStyle = hexToRgba(gridColorHex, 0.12);
          ctx.lineWidth = 1;
          ctx.beginPath();
          for (let x = deepStartX; x <= deepRight; x += deepSpacing) {
            const lx = x + driftX;
            ctx.moveTo(lx, deepTop);
            ctx.lineTo(lx, deepBottom);
          }
          for (let y = deepStartY; y <= deepBottom; y += deepSpacing) {
            const ly = y + driftY;
            ctx.moveTo(deepLeft, ly);
            ctx.lineTo(deepRight, ly);
          }
          ctx.stroke();

          // Layer 2: Main primary arena grid (single batched stroke, clamped to arena bounds)
          const mainSpacing = 120;
          const clampedStartX = Math.floor(Math.max(viewLeft, -arenaHalf) / mainSpacing) * mainSpacing;
          const clampedEndX = Math.min(viewRight, arenaHalf);
          const clampedStartY = Math.floor(Math.max(viewTop, -arenaHalf) / mainSpacing) * mainSpacing;
          const clampedEndY = Math.min(viewBottom, arenaHalf);
          const boundTop = Math.max(viewTop, -arenaHalf);
          const boundBottom = Math.min(viewBottom, arenaHalf);
          const boundLeft = Math.max(viewLeft, -arenaHalf);
          const boundRight = Math.min(viewRight, arenaHalf);

          ctx.strokeStyle = hexToRgba(gridColorHex, 0.35);
          ctx.lineWidth = 1.2;
          ctx.beginPath();
          for (let x = clampedStartX; x <= clampedEndX; x += mainSpacing) {
            ctx.moveTo(x, boundTop);
            ctx.lineTo(x, boundBottom);
          }
          for (let y = clampedStartY; y <= clampedEndY; y += mainSpacing) {
            ctx.moveTo(boundLeft, y);
            ctx.lineTo(boundRight, y);
          }
          ctx.stroke();

          // Grid intersection node accents (batched in 1 call, zero shadowBlur)
          ctx.fillStyle = hexToRgba(gridColorHex, 0.65);
          ctx.beginPath();
          const arenaHalfSq = arenaHalf * arenaHalf;
          for (let x = clampedStartX; x <= clampedEndX; x += mainSpacing) {
            const xSq = x * x;
            for (let y = clampedStartY; y <= clampedEndY; y += mainSpacing) {
              if (xSq + y * y <= arenaHalfSq) {
                ctx.rect(x - 2, y - 2, 4, 4);
              }
            }
          }
          ctx.fill();
          ctx.restore();
        }

        function drawSparkle(cx, cy, radius, color, rotation, alpha) {
          ctx.save();
          ctx.translate(cx, cy);
          ctx.rotate(rotation);
          ctx.globalAlpha = alpha;

          // 4-pointed cross star flare
          const len = radius * 2.2;
          const thick = radius * 0.35;
          ctx.fillStyle = '#ffffff';

          ctx.beginPath();
          ctx.moveTo(-len, 0);
          ctx.quadraticCurveTo(0, thick, 0, len);
          ctx.quadraticCurveTo(0, thick, len, 0);
          ctx.quadraticCurveTo(0, -thick, 0, -len);
          ctx.quadraticCurveTo(0, -thick, -len, 0);
          ctx.closePath();
          ctx.fill();

          ctx.restore();
        }

        function drawVirus(v) {
          const r = massToRadius(v.mass);

          if (virusImg && virusImg.complete) {
            ctx.save();
            ctx.translate(v.x, v.y);
            ctx.rotate(v.spin);
            const size = r * 2.4;
            ctx.drawImage(virusImg, -size/2, -size/2, size, size);
            if (v.flash > 0) {
              ctx.globalAlpha = v.flash * 0.4;
              ctx.fillStyle = '#fff';
              ctx.beginPath();
              ctx.arc(0, 0, r, 0, Math.PI * 2);
              ctx.fill();
            }
            ctx.restore();
            return;
          }

          const spikes = 18;
          ctx.save();
          ctx.translate(v.x, v.y);
          ctx.rotate(v.spin);

          ctx.beginPath();
          for (let i = 0; i < spikes * 2; i++) {
            const ang = (Math.PI * i) / spikes;
            const rad = i % 2 === 0 ? r * 1.16 : r * 0.86;
            const px = Math.cos(ang) * rad;
            const py = Math.sin(ang) * rad;
            if (i === 0) ctx.moveTo(px, py);
            else ctx.lineTo(px, py);
          }
          ctx.closePath();

          const g = ctx.createRadialGradient(0, 0, r * 0.15, 0, 0, r * 1.16);
          g.addColorStop(0, 'rgba(90, 255, 150, 0.5)');
          g.addColorStop(0.65, 'rgba(25, 200, 95, 0.38)');
          g.addColorStop(1, 'rgba(10, 130, 60, 0.6)');
          ctx.fillStyle = g;
          ctx.fill();

          ctx.strokeStyle = `rgba(120, 255, 170, ${0.7 + v.flash * 0.3})`;
          ctx.lineWidth = 3;
          ctx.stroke();
          ctx.restore();
        }

        function drawPlayerCell(c, playerColor) {
          const baseRadius = massToRadius(c.mass);
          // Elastic pulse bounce: idle breathing + growth bounce
          const idleBreath = Math.sin(globalTime * 3) * 0.025;
          const growthBounce = growthPulse * Math.sin(globalTime * 22) * 0.12 + growthPulse * 0.08;
          const currentRadius = baseRadius * (1 + idleBreath + growthBounce);

          ctx.save();

          // Zero The Legend pseudo-3D volume: contact shadow, lower extrusion and orbit ring.
          legendDrawShadow(c.x, c.y, currentRadius, 0.24);
          legendDrawDepthRing(c.x, c.y, currentRadius, playerColor, 0.42);
          legendDrawLegendRing(c.x, c.y, currentRadius, '#ffe600');

          // 1. Broad outer atmospheric neon aura
          const glowMul = S.skinGlow !== undefined ? S.skinGlow : 1;
          const auraGrad = ctx.createRadialGradient(
            c.x, c.y, currentRadius * 0.8,
            c.x, c.y, currentRadius * 2.2 + growthPulse * 24
          );
          auraGrad.addColorStop(0, hexToRgba(playerColor, 0.45 * glowMul));
          auraGrad.addColorStop(0.5, hexToRgba(playerColor, 0.15 * glowMul));
          auraGrad.addColorStop(1, hexToRgba(playerColor, 0));

          ctx.fillStyle = auraGrad;
          ctx.beginPath();
          ctx.arc(c.x, c.y, currentRadius * 2.2 + growthPulse * 24, 0, Math.PI * 2);
          ctx.fill();

          // 2. High-intensity neon rim (dual stroke for intense glow without shadowBlur lag)
          ctx.beginPath();
          ctx.arc(c.x, c.y, currentRadius, 0, Math.PI * 2);
          ctx.strokeStyle = hexToRgba(playerColor, 0.35);
          ctx.lineWidth = 8 + currentRadius * 0.08;
          ctx.stroke();

          ctx.beginPath();
          ctx.arc(c.x, c.y, currentRadius, 0, Math.PI * 2);
          ctx.strokeStyle = playerColor;
          ctx.lineWidth = 3 + currentRadius * 0.04;
          ctx.stroke();

          // 3. Cell body translucent glowing gradient
          const bodyGrad = ctx.createRadialGradient(
            c.x - currentRadius * 0.25, c.y - currentRadius * 0.25, currentRadius * 0.1,
            c.x, c.y, currentRadius
          );
          bodyGrad.addColorStop(0, '#ffffff');
          bodyGrad.addColorStop(0.3, hexToRgba(playerColor, 0.85));
          bodyGrad.addColorStop(0.85, hexToRgba(playerColor, 0.6));
          bodyGrad.addColorStop(1, hexToRgba(playerColor, 0.95));

          ctx.fillStyle = bodyGrad;
          ctx.beginPath();
          ctx.arc(c.x, c.y, currentRadius, 0, Math.PI * 2);
          ctx.fill();

          // 3b. Custom uploaded skin (static image or animated GIF frame)
          if (skinImg && skinImg.complete && skinImg.naturalWidth) {
            try {
              ctx.save();
              ctx.beginPath();
              ctx.arc(c.x, c.y, currentRadius * 0.97, 0, Math.PI * 2);
              ctx.clip();
              const d = currentRadius * 1.94;
              ctx.drawImage(skinImg, c.x - d / 2, c.y - d / 2, d, d);
              ctx.restore();
            } catch (err) {
              ctx.restore();
              skinImg = null;
            }
          }

          // 4. Inner energetic core
          const coreRadius = currentRadius * (skinImg ? 0.2 : 0.45);
          const coreGrad = ctx.createRadialGradient(
            c.x - coreRadius * 0.2, c.y - coreRadius * 0.2, 0,
            c.x, c.y, coreRadius
          );
          coreGrad.addColorStop(0, '#ffffff');
          coreGrad.addColorStop(0.6, hexToRgba(playerColor, 0.9));
          coreGrad.addColorStop(1, hexToRgba(playerColor, 0));

          ctx.fillStyle = coreGrad;
          ctx.beginPath();
          ctx.arc(c.x, c.y, coreRadius, 0, Math.PI * 2);
          ctx.fill();
          legendDrawCellSpecular(c.x, c.y, currentRadius, playerColor);

          // God mode halo
          if (godMode.active) {
            const gp = 0.55 + 0.35 * Math.sin(globalTime * 8);
            ctx.strokeStyle = 'rgba(255, 190, 70, ' + gp.toFixed(2) + ')';
            ctx.lineWidth = 5;
            ctx.beginPath();
            ctx.arc(c.x, c.y, currentRadius * 1.3, 0, Math.PI * 2);
            ctx.stroke();
            ctx.strokeStyle = 'rgba(255, 255, 220, 0.5)';
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.arc(c.x, c.y, currentRadius * 1.45, 0, Math.PI * 2);
            ctx.stroke();
          }

          // Shield bubble
          if (effects.shield > 0) {
            ctx.strokeStyle = 'rgba(127, 212, 255, 0.75)';
            ctx.lineWidth = 3;
            ctx.setLineDash([12, 10]);
            ctx.beginPath();
            ctx.arc(c.x, c.y, currentRadius * 1.2, globalTime * 1.5, globalTime * 1.5 + Math.PI * 2);
            ctx.stroke();
            ctx.setLineDash([]);
          }

          // Dash streak ring
          if (dash.active) {
            ctx.strokeStyle = 'rgba(0, 255, 170, 0.7)';
            ctx.lineWidth = 4;
            ctx.setLineDash([6, 14]);
            ctx.beginPath();
            ctx.arc(c.x, c.y, currentRadius * 1.15, -globalTime * 6, -globalTime * 6 + Math.PI * 2);
            ctx.stroke();
            ctx.setLineDash([]);
          }

          // Merge cooldown ring
          if (c.mergeTimer > 0) {
            const mergeTime = cfg.mechanics.mergeTime || 10;
            const frac = Math.max(0, Math.min(1, c.mergeTimer / mergeTime));
            ctx.strokeStyle = 'rgba(255, 255, 255, 0.45)';
            ctx.lineWidth = 2.5;
            ctx.beginPath();
            ctx.arc(c.x, c.y, currentRadius * 1.12, -Math.PI / 2, -Math.PI / 2 + Math.PI * 2 * frac);
            ctx.stroke();
          }

          ctx.restore();
        }

        // --- Render ---
        function render() {
          const w = canvas.width;
          const h = canvas.height;
          const arenaHalf = cfg.arena.size / 2;
          const playerColor = currentPlayerColor();
          const particleColor = cfg.visual.particleColor || '#00f0ff';

          // Deep cosmic background gradient (cached) with a restrained 3D depth fog.
          ctx.fillStyle = updateCachedBgGrad(w, h);
          ctx.fillRect(0, 0, w, h);
          legendDrawDepthFog(w, h);

          // Calculate visible screen bounds in world space for view frustum culling
          const halfW = (w / 2) / camera.zoom;
          const halfH = (h / 2) / camera.zoom;
          const viewLeft = camera.x - halfW;
          const viewRight = camera.x + halfW;
          const viewTop = camera.y - halfH;
          const viewBottom = camera.y + halfH;

          ctx.save();
          // Camera transform (+ screen shake)
          let shakeX = 0, shakeY = 0;
          const shakeScale = S.screenShake !== undefined ? S.screenShake : 1;
          if (shake.t > 0 && shake.mag > 0 && shakeScale > 0) {
            shakeX = (Math.random() - 0.5) * shake.mag * 2 * shakeScale;
            shakeY = (Math.random() - 0.5) * shake.mag * 2 * shakeScale;
          }
          ctx.translate(w / 2 + shakeX, h / 2 + shakeY);
          ctx.scale(camera.zoom, camera.zoom);
          ctx.translate(-camera.x, -camera.y);

          // Parallax Grid + 3D speed streaks
          if (S.showGrid) drawParallaxGrid(w, h, arenaHalf, viewLeft, viewRight, viewTop, viewBottom);
          legendDrawSpeedLines(w, h);

          // Arena boundary neon perimeter
          ctx.save();
          ctx.beginPath();
          ctx.arc(0, 0, arenaHalf, 0, Math.PI * 2);
          ctx.strokeStyle = hexToRgba(playerColor, 0.18);
          ctx.lineWidth = 10;
          ctx.stroke();

          ctx.beginPath();
          ctx.arc(0, 0, arenaHalf, 0, Math.PI * 2);
          ctx.strokeStyle = hexToRgba(playerColor, 0.6);
          ctx.lineWidth = 2.5;
          ctx.stroke();
          ctx.restore();

          // Particles with sparkling neon visuals (with view frustum culling)
          for (const p of particles) {
            if (p.x < viewLeft - 30 || p.x > viewRight + 30 || p.y < viewTop - 30 || p.y > viewBottom + 30) continue;

            const baseR = massToRadius(p.mass);
            const sparkleCycle = (Math.sin(p.phase) + 1) * 0.5;
            const r = baseR * (0.85 + sparkleCycle * 0.35);

            legendDrawParticleShadow(p.x, p.y, r);
            ctx.save();
            // Outer neon ambient glow
            if (S.particleQuality !== 'low') {
              ctx.globalAlpha = 0.22 + sparkleCycle * 0.22;
              ctx.fillStyle = particleColor;
              ctx.beginPath();
              ctx.arc(p.x, p.y, r * 1.6, 0, Math.PI * 2);
              ctx.fill();
            }
            ctx.fillStyle = particleColor;

            // Vibrant particle body
            ctx.globalAlpha = 0.9;
            ctx.beginPath();
            ctx.arc(p.x, p.y, r, 0, Math.PI * 2);
            ctx.fill();

            // Bright core
            ctx.globalAlpha = 0.95;
            ctx.fillStyle = '#ffffff';
            ctx.beginPath();
            ctx.arc(p.x - r * 0.2, p.y - r * 0.2, r * 0.45, 0, Math.PI * 2);
            ctx.fill();

            // Occasional sparkle star gleam on peak twinkle
            if (sparkleCycle > 0.65 && S.particleQuality === 'high') {
              const flareAlpha = (sparkleCycle - 0.65) / 0.35;
              drawSparkle(p.x, p.y, r * 1.8 * p.sparkleSize, particleColor, p.rotation, flareAlpha * 0.85);
            }
            ctx.restore();
          }

          // Dissolve effects (particles being absorbed)
          for (const eff of dissolvingEffects) {
            if (eff.x < viewLeft - 60 || eff.x > viewRight + 60 || eff.y < viewTop - 60 || eff.y > viewBottom + 60) continue;

            ctx.save();
            const alpha = 1 - eff.progress;
            ctx.globalAlpha = alpha;

            // Dissolving core shrinking into player
            ctx.fillStyle = eff.color;
            ctx.beginPath();
            ctx.arc(eff.x, eff.y, Math.max(1, eff.currentR), 0, Math.PI * 2);
            ctx.fill();

            // Dissolve motes floating and dissipating
            for (const m of eff.motes) {
              if (m.life <= 0) continue;
              const mx = eff.x + Math.cos(m.angle) * m.dist;
              const my = eff.y + Math.sin(m.angle) * m.dist;
              ctx.globalAlpha = alpha * Math.max(0, m.life);
              ctx.fillStyle = '#ffffff';
              ctx.beginPath();
              ctx.arc(mx, my, m.size, 0, Math.PI * 2);
              ctx.fill();
            }

            // Suction line/streak connecting to the absorbing cell
            const effTgt = eff.target || player;
            ctx.beginPath();
            ctx.moveTo(eff.x, eff.y);
            ctx.lineTo(effTgt.x, effTgt.y);
            ctx.strokeStyle = hexToRgba(eff.color, alpha * 0.4);
            ctx.lineWidth = Math.max(1, eff.currentR * 0.4);
            ctx.stroke();

            ctx.restore();
          }

          // Shockwave rings from cell growth
          for (const sw of shockwaves) {
            ctx.save();
            const curR = sw.radius + (sw.maxRadius - sw.radius) * sw.progress;
            const swAlpha = (1 - sw.progress) * 0.65;
            ctx.strokeStyle = hexToRgba(sw.color, swAlpha);
            ctx.lineWidth = Math.max(1, (1 - sw.progress) * 4);
            ctx.beginPath();
            ctx.arc(sw.x, sw.y, curR, 0, Math.PI * 2);
            ctx.stroke();
            ctx.restore();
          }

          // Ejected mass pellets
          for (const e of ejected) {
            if (e.x < viewLeft - 30 || e.x > viewRight + 30 || e.y < viewTop - 30 || e.y > viewBottom + 30) continue;

            const r = massToRadius(e.mass) * 0.75;
            ctx.save();
            ctx.globalAlpha = 0.9;
            ctx.fillStyle = e.color;
            ctx.beginPath();
            ctx.arc(e.x, e.y, r, 0, Math.PI * 2);
            ctx.fill();
            ctx.globalAlpha = 0.95;
            ctx.fillStyle = '#ffffff';
            ctx.beginPath();
            ctx.arc(e.x - r * 0.2, e.y - r * 0.2, r * 0.4, 0, Math.PI * 2);
            ctx.fill();
            ctx.restore();
          }

          // Dash trail + power-up orbs
          if (trails.length && S.showTrails) drawTrails(playerColor);
          if (powerups.length) drawPowerups();
          if (arcadeCores.length) drawArcadeCores();
          if (zones.length) drawZones();
          if (pulseWaves.length) drawPulseWaves();

          // AI predator cells
          for (const a of aiCells) {
            const aR = massToRadius(a.mass);
            if (a.x < viewLeft - aR * 3 || a.x > viewRight + aR * 3 ||
                a.y < viewTop - aR * 3 || a.y > viewBottom + aR * 3) continue;

            const breath = 1 + Math.sin(a.pulse) * 0.03 + a.flash * 0.06;
            const r = aR * breath;
            const bigger = a.mass > player.mass * 1.15;

            ctx.save();
            // Outer aura
            const aura = ctx.createRadialGradient(a.x, a.y, r * 0.8, a.x, a.y, r * 2.1);
            aura.addColorStop(0, hexToRgba(a.color, 0.35 + a.flash * 0.2));
            aura.addColorStop(0.5, hexToRgba(a.color, 0.12));
            aura.addColorStop(1, hexToRgba(a.color, 0));
            ctx.fillStyle = aura;
            ctx.beginPath();
            ctx.arc(a.x, a.y, r * 2.1, 0, Math.PI * 2);
            ctx.fill();

            // Rim glow (dual stroke for intense glow without blur lag)
            ctx.beginPath();
            ctx.arc(a.x, a.y, r, 0, Math.PI * 2);
            ctx.strokeStyle = hexToRgba(a.color, 0.35);
            ctx.lineWidth = 7 + r * 0.06;
            ctx.stroke();

            ctx.beginPath();
            ctx.arc(a.x, a.y, r, 0, Math.PI * 2);
            ctx.strokeStyle = a.color;
            ctx.lineWidth = 2.5 + r * 0.035;
            ctx.stroke();

            // Body
            const body = ctx.createRadialGradient(
              a.x - r * 0.25, a.y - r * 0.25, r * 0.1,
              a.x, a.y, r
            );
            body.addColorStop(0, hexToRgba(a.color, 0.95));
            body.addColorStop(0.55, hexToRgba(a.color, 0.7));
            body.addColorStop(1, hexToRgba(a.color, 0.92));
            ctx.fillStyle = body;
            ctx.beginPath();
            ctx.arc(a.x, a.y, r, 0, Math.PI * 2);
            ctx.fill();

            // Inner core
            const core = ctx.createRadialGradient(a.x, a.y, 0, a.x, a.y, r * 0.42);
            core.addColorStop(0, 'rgba(255,255,255,0.9)');
            core.addColorStop(1, hexToRgba(a.color, 0));
            ctx.fillStyle = core;
            ctx.beginPath();
            ctx.arc(a.x, a.y, r * 0.42, 0, Math.PI * 2);
            ctx.fill();

            // Danger ring when this cell can eat the player
            if (bigger && mode === 'play' && !gameOver) {
              const dangerPulse = 0.35 + 0.25 * Math.sin(globalTime * 5 + a.pulse);
              ctx.strokeStyle = hexToRgba(dangerColor(), dangerPulse);
              ctx.lineWidth = 2;
              ctx.setLineDash([10, 12]);
              ctx.beginPath();
              ctx.arc(a.x, a.y, r * 1.25, 0, Math.PI * 2);
              ctx.stroke();
              ctx.setLineDash([]);
            }

            // Ghost rival marker (a friend's recorded run)
            if (a.isGhost) {
              ctx.strokeStyle = 'rgba(255, 255, 255, 0.55)';
              ctx.lineWidth = 1.5;
              ctx.setLineDash([5, 7]);
              ctx.beginPath();
              ctx.arc(a.x, a.y, r * 1.1, 0, Math.PI * 2);
              ctx.stroke();
              ctx.setLineDash([]);
            }

            // Name label
            if (S.showNames) {
              const fontSize = Math.max(11, r * 0.5);
              ctx.font = `700 ${fontSize}px Orbitron, sans-serif`;
              ctx.textAlign = 'center';
              ctx.textBaseline = 'middle';
              ctx.fillStyle = 'rgba(255,255,255,0.92)';
              ctx.fillText((a.isGhost ? '\u2605 ' : '') + a.name, a.x, a.y);
            }
            ctx.restore();
          }

          // Player blobs with multi-layered neon glow and dynamic growth pulse
          const orderedCells = playerCells.slice().sort((a, b) => a.mass - b.mass);
          for (const c of orderedCells) {
            drawPlayerCell(c, playerColor);
          }

          // Viruses drawn on top so smaller cells can slip beneath them (with view frustum culling)
          for (const v of viruses) {
            const vr = massToRadius(v.mass) * 1.5;
            if (v.x < viewLeft - vr || v.x > viewRight + vr || v.y < viewTop - vr || v.y > viewBottom + vr) continue;
            drawVirus(v);
          }

          ctx.restore();

          // Screen-space overlays
          if (S.showThreatArrows) drawThreatArrows(w, h);
        }

        // ============================================================
        // ZERO THE LEGEND — 3D presentation, brand, Legend Lab and 50 micro-features
        // ============================================================
        const LEGEND_FEATURES = [
          '3D cell depth', 'dynamic rim light', 'orbit ring aura', 'depth fog', 'speed lines',
          'particle shadows', 'specular highlights', 'camera parallax', 'tilt cards', 'logo pulse',
          'daily legend seed', 'feature badges', 'local challenge history', 'reflex challenge', 'lane challenge',
          'cipher challenge', 'pilot challenge', 'legendary combo', 'challenge streak', 'daily reward',
          'instant replay score', 'best-time tracking', 'safe color contrast', 'reduced motion fallback', '3D toggle',
          'legendary FX toggle', 'portal depth toggle', 'accent themes', 'brand asset loader', 'feature progress',
          'local preset export', 'local preset import', 'visual reset', 'challenge selector', 'challenge hints',
          'touch-first targets', 'keyboard challenge support', 'adaptive stage', 'high-score badges', 'reward toast',
          'pass XP rewards', 'ZeroCoin rewards', 'achievement hooks', 'recent challenge list', 'session streak',
          'no-network operation', 'asset caching', 'delta-time animation', 'safe HTML labels', 'mobile layout',
          'legend catalog filter',
        ];
        let legendGameId = 'reflex';
        let legendChallengeTimer = null;
        let legendLogoReady = false;

        function legendAddCatalogGames(list) {
          if (!Array.isArray(list) || !Array.isArray(GAMES)) return;
          const known = new Set(GAMES.map((g) => g.id));
          list.forEach((g) => {
            if (!g || known.has(g.id)) return;
            GAMES.push(Object.assign({ badge: 'LEGEND · IN ARRIVO', art: 'linear-gradient(135deg,#ffd54a,#7b2cff)' }, g));
            known.add(g.id);
          });
        }
        function legendLoadBrandAsset() {
          const img = document.getElementById('pt-logo-img');
          const logo = lib.getAsset ? lib.getAsset('zero_the_legend_logo') : null;
          if (!img || !logo || !logo.url) return false;
          img.src = logo.url;
          img.onload = () => {
            legendLogoReady = true;
            const wrap = document.getElementById('pt-logo');
            if (wrap) wrap.classList.add('has-asset');
          };
          return true;
        }
        function legendApplyVisualMode() {
          if (!gameWorld) return;
          gameWorld.classList.toggle('legend-3d-off', S && S.threeDGraphics === false);
          const portal = document.getElementById('portal');
          if (portal) portal.classList.toggle('legend-depth-off', S && S.portalDepth === false);
          if (S && S.legendaryFx !== false) legendPulseLogo(false);
        }
        function legendSet3DMode(on) { setSetting('threeDGraphics', !!on); }
        function legendToggle3D() { legendSet3DMode(!(S && S.threeDGraphics)); }
        function legendToggleLegendaryFx() { setSetting('legendaryFx', !(S && S.legendaryFx)); }
        function legendTogglePortalDepth() { setSetting('portalDepth', !(S && S.portalDepth)); }
        function legendClamp(v, lo, hi) { return Math.max(lo, Math.min(hi, v)); }
        function legendLerp(a, b, t) { return a + (b - a) * t; }
        function legendEase(t) { t = legendClamp(t, 0, 1); return t * t * (3 - 2 * t); }
        function legendRand(list) { return list && list.length ? list[Math.floor(Math.random() * list.length)] : null; }
        function legendSeed(text) {
          let n = 2166136261;
          String(text || '').split('').forEach((c) => { n ^= c.charCodeAt(0); n = Math.imul(n, 16777619); });
          return (n >>> 0) / 4294967296;
        }
        function legendDailySeed() { return legendSeed(new Date().toISOString().slice(0, 10)); }
        function legendSafe(text) { return escapeHtml(String(text == null ? '' : text)); }
        function legendFormatClock(ms) { const s = Math.max(0, Math.round(ms / 1000)); return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0'); }
        function legendAward(zc, xp, reason) {
          if (zc > 0) addCoins(Math.round(zc), reason || 'Legend Lab');
          if (xp > 0) { addProfileXp(xp); addPassXp(Math.round(xp * 0.4)); }
          legendRecordFeature('reward');
        }
        function legendRecordFeature(id) {
          if (!profile.legendFeatures || typeof profile.legendFeatures !== 'object') profile.legendFeatures = {};
          profile.legendFeatures[id] = (profile.legendFeatures[id] || 0) + 1;
          queueSave();
        }
        function legendReadFeature(id) { return profile.legendFeatures && profile.legendFeatures[id] ? profile.legendFeatures[id] : 0; }
        function legendFeatureUnlocked(id) { return legendReadFeature(id) > 0; }
        function legendUnlockFeature(id, text) { if (!legendFeatureUnlocked(id)) { legendRecordFeature(id); if (text) notify('✦ ' + text, '#ffe600'); } }
        function legendFeatureCount() { return Object.keys(profile.legendFeatures || {}).length; }
        function legendFeatureProgress() { return Math.min(100, Math.round((legendFeatureCount() / LEGEND_FEATURES.length) * 100)); }
        function legendFeatureBadge(id) { return legendFeatureUnlocked(id) ? '<span class="pt-badge">SBLOCCATA</span>' : '<span class="pt-badge soon">NUOVA</span>'; }
        function legendMount(html) { if (ptBodyEl) ptBodyEl.innerHTML = html; return ptBodyEl; }
        function legendUnwrap() { clearMiniTimers(); if (legendChallengeTimer) { clearTimeout(legendChallengeTimer); legendChallengeTimer = null; } }
        function legendToast(text, color) { notify(text, color || '#ffe600'); }
        function legendSetAccent(color) { document.documentElement.style.setProperty('--legend-accent', color || '#00f0ff'); }
        function legendSetLogo(active) { const el = document.getElementById('pt-logo'); if (el) el.classList.toggle('has-asset', !!active && legendLogoReady); }
        function legendPulseLogo(force) {
          const el = document.getElementById('pt-logo');
          if (!el || (!force && (!S || S.legendaryFx === false))) return;
          el.animate([{ transform: 'scale(1)' }, { transform: 'scale(1.035)' }, { transform: 'scale(1)' }], { duration: 650, easing: 'ease-out' });
        }
        function legendTiltCard(el, event) {
          if (!el || !S || S.portalDepth === false) return;
          const r = el.getBoundingClientRect();
          const x = (event.clientX - r.left) / Math.max(1, r.width) - 0.5;
          const y = (event.clientY - r.top) / Math.max(1, r.height) - 0.5;
          el.style.transform = 'translateY(-3px) rotateX(' + (-y * 4).toFixed(2) + 'deg) rotateY(' + (x * 5).toFixed(2) + 'deg)';
        }
        function legendBindTilt(scope) {
          if (!scope || !S || S.portalDepth === false) return;
          scope.querySelectorAll('.pt-card,.pt-game,.pt-item').forEach((el) => {
            if (el.dataset.legendTilt) return;
            el.dataset.legendTilt = '1';
            el.addEventListener('pointermove', (e) => legendTiltCard(el, e));
            el.addEventListener('pointerleave', () => { el.style.transform = ''; });
          });
        }
        function legendDrawShadow(x, y, r, alpha) {
          if (!S || S.threeDGraphics === false) return;
          ctx.save();
          ctx.globalAlpha = alpha == null ? 0.25 : alpha;
          ctx.fillStyle = '#000';
          ctx.beginPath();
          ctx.ellipse(x + r * 0.22, y + r * 0.32, r * 1.12, r * 0.42, 0.12, 0, Math.PI * 2);
          ctx.fill();
          ctx.restore();
        }
        function legendDrawDepthRing(x, y, r, color, alpha) {
          if (!S || S.threeDGraphics === false) return;
          ctx.save();
          ctx.globalAlpha = alpha == null ? 0.45 : alpha;
          ctx.strokeStyle = color;
          ctx.lineWidth = Math.max(2, r * 0.08);
          ctx.beginPath();
          ctx.arc(x, y + r * 0.16, r * 1.02, 0.08, Math.PI * 1.94);
          ctx.stroke();
          ctx.restore();
        }
        function legendDrawStar(x, y, r, color) {
          ctx.save();
          ctx.fillStyle = color || '#fff';
          ctx.globalAlpha = 0.8;
          ctx.beginPath();
          ctx.moveTo(x, y - r); ctx.lineTo(x + r * 0.3, y - r * 0.3); ctx.lineTo(x + r, y);
          ctx.lineTo(x + r * 0.3, y + r * 0.3); ctx.lineTo(x, y + r); ctx.lineTo(x - r * 0.3, y + r * 0.3);
          ctx.lineTo(x - r, y); ctx.lineTo(x - r * 0.3, y - r * 0.3); ctx.closePath(); ctx.fill();
          ctx.restore();
        }
        function legendDrawSpeedLines(w, h) {
          if (!S || S.threeDGraphics === false || S.legendaryFx === false) return;
          const arenaHalf = cfg.arena.size / 2;
          ctx.save(); ctx.globalAlpha = 0.08; ctx.strokeStyle = '#00f0ff'; ctx.lineWidth = 2;
          for (let i = 0; i < 9; i++) { const y = ((globalTime * (35 + i * 7) + i * 91) % (h + 100)) - 50; ctx.beginPath(); ctx.moveTo(-arenaHalf, y); ctx.lineTo(arenaHalf, y + 55); ctx.stroke(); }
          ctx.restore();
        }
        function legendDrawDepthFog(w, h) {
          if (!S || S.threeDGraphics === false) return;
          const g = ctx.createRadialGradient(w / 2, h / 2, Math.min(w, h) * 0.18, w / 2, h / 2, Math.max(w, h) * 0.75);
          g.addColorStop(0, 'rgba(0,0,0,0)'); g.addColorStop(1, 'rgba(1,3,14,0.34)');
          ctx.fillStyle = g; ctx.fillRect(0, 0, w, h);
        }
        function legendDrawLegendRing(x, y, r, color) {
          if (!S || S.legendaryFx === false) return;
          ctx.save(); ctx.strokeStyle = color || '#ffe600'; ctx.globalAlpha = 0.55; ctx.lineWidth = Math.max(1.5, r * 0.035); ctx.setLineDash([r * 0.18, r * 0.24]);
          ctx.beginPath(); ctx.arc(x, y, r * 1.42, globalTime * 0.8, globalTime * 0.8 + Math.PI * 1.55); ctx.stroke(); ctx.setLineDash([]); ctx.restore();
        }
        function legendDrawParticleShadow(x, y, r) { legendDrawShadow(x, y, r, 0.16); }
        function legendDrawCellSpecular(x, y, r, color) {
          if (!S || S.threeDGraphics === false) return;
          const g = ctx.createRadialGradient(x - r * 0.32, y - r * 0.4, 0, x - r * 0.32, y - r * 0.4, r * 0.7);
          g.addColorStop(0, 'rgba(255,255,255,0.8)'); g.addColorStop(0.22, 'rgba(255,255,255,0.22)'); g.addColorStop(1, hexToRgba(color, 0));
          ctx.fillStyle = g; ctx.beginPath(); ctx.arc(x, y, r, 0, Math.PI * 2); ctx.fill();
        }
        function legendCreateChallengeButton(id, label) { return '<button class="pt-btn small' + (legendGameId === id ? ' primary' : '') + '" data-legend-game="' + id + '">' + label + '</button>'; }
        function legendOpenLab(kind) { legendGameId = kind || 'reflex'; setPtPage('legend'); }
        function legendCloseLab() { legendUnwrap(); setPtPage('arcade'); }
        function legendStartChallenge(kind) { legendUnwrap(); legendGameId = kind; legendRecordFeature('challenge_' + kind); renderPortal(); }
        function legendPlayReflex(box) {
          box.innerHTML = '<div class="legend-stage"><button class="legend-target" id="legend-target" style="display:none">✦</button></div><div class="mini-out" id="legend-out">Premi START, poi colpisci il nucleo.</div><button class="pt-btn primary wide" id="legend-start">START REFLEX</button>';
          const target = box.querySelector('#legend-target'); const stage = box.querySelector('.legend-stage'); let ready = false; let t0 = 0;
          box.querySelector('#legend-start').addEventListener('click', () => { ready = false; target.style.display = 'none'; legendChallengeTimer = setTimeout(() => { ready = true; t0 = performance.now(); target.style.left = (18 + Math.random() * 64) + '%'; target.style.top = (22 + Math.random() * 56) + '%'; target.style.display = 'block'; }, 650 + Math.random() * 1500); box.querySelector('#legend-out').textContent = 'Concentrati...'; });
          target.addEventListener('click', (e) => { e.preventDefault(); if (!ready) return; const ms = Math.round(performance.now() - t0); ready = false; target.style.display = 'none'; const zc = Math.max(20, 460 - ms); legendAward(zc, 35, 'Legend Reflex'); box.querySelector('#legend-out').textContent = ms + ' ms · +' + fmt(zc) + ' ZC'; legendUnlockFeature('reflex_reward', 'Riflessi da leggenda'); });
        }
        function legendPlayLane(box) {
          box.innerHTML = '<div class="legend-stage"><div class="legend-meter"><span id="lane-meter"></span></div><div class="legend-lanes">' + [0,1,2].map((i) => '<button class="legend-lane" data-lane="' + i + '">CORSIA ' + (i + 1) + '</button>').join('') + '</div></div><div class="mini-out" id="legend-out">Evita il drone rosso. 5 round.</div>';
          let round = 0; let score = 0;
          box.querySelectorAll('[data-lane]').forEach((b) => b.addEventListener('click', () => { const safe = Math.floor(Math.random() * 3); round++; if (Number(b.dataset.lane) === safe) score++; b.classList.add(Number(b.dataset.lane) === safe ? 'hit' : 'wait'); setTimeout(() => b.classList.remove('hit','wait'), 180); box.querySelector('#lane-meter').style.width = (round / 5 * 100) + '%'; if (round >= 5) { const zc = score * 70; legendAward(zc, score * 12, 'Legend Lane'); box.querySelector('#legend-out').textContent = score + '/5 corsie sicure · +' + zc + ' ZC'; legendUnlockFeature('lane_reward', 'Corsia dominata'); } else box.querySelector('#legend-out').textContent = 'Round ' + round + '/5 · scegli ancora.'; }));
        }
        function legendPlayCipher(box) {
          const colors = ['CYAN','GOLD','PINK','MINT']; const answer = colors[Math.floor(legendDailySeed() * colors.length)];
          box.innerHTML = '<div class="legend-stage"><div class="mini-out">Il codice di oggi ha 1 colore. Trovalo.</div><div class="legend-lanes">' + colors.map((c) => '<button class="legend-choice" data-cipher="' + c + '">' + c + '</button>').join('') + '</div></div><div class="mini-out" id="legend-out">Decifra il nucleo.</div>';
          box.querySelectorAll('[data-cipher]').forEach((b) => b.addEventListener('click', () => { const win = b.dataset.cipher === answer; b.classList.add(win ? 'hit' : 'wait'); const zc = win ? 180 : 15; legendAward(zc, win ? 45 : 5, 'Color Cipher'); box.querySelector('#legend-out').textContent = win ? 'Codice corretto · +' + zc + ' ZC' : 'Codice errato · il nucleo era ' + answer; legendUnlockFeature(win ? 'cipher_win' : 'cipher_try', win ? 'Cifrario decodificato' : 'Cifrario testato'); }));
        }
        function legendPlayPilot(box) {
          box.innerHTML = '<div class="legend-stage"><div class="legend-target" id="pilot-core" style="left:50%;top:50%">✦</div></div><div class="mini-out" id="legend-out">Guida il pilota: premi FLY per attraversare 3 anelli.</div><button class="pt-btn primary wide" id="pilot-go">FLY</button>';
          box.querySelector('#pilot-go').addEventListener('click', () => { const rings = 1 + Math.floor(Math.random() * 3); const zc = rings * 80; legendAward(zc, rings * 18, 'Orbit Pilot'); box.querySelector('#legend-out').textContent = rings + ' anelli attraversati · +' + zc + ' ZC'; legendUnlockFeature('pilot_fly', 'Pilota orbitale'); });
        }
        function legendBindLab(scope) { if (!scope) return; scope.querySelectorAll('[data-legend-game]').forEach((b) => b.addEventListener('click', () => legendStartChallenge(b.dataset.legendGame))); legendBindTilt(scope); }
        function legendAddFeaturePanel() { return '<div class="pt-sec">50 FUNZIONI LEGEND</div><div class="pt-card"><div class="pt-note">Progressi locali: ' + legendFeatureCount() + '/' + LEGEND_FEATURES.length + ' · ' + legendFeatureProgress() + '%</div>' + LEGEND_FEATURES.map((f, i) => '<div class="pt-tx"><span>' + (i + 1) + '. ' + f + '</span>' + legendFeatureBadge(String(i)) + '</div>').join('') + '</div>'; }
        function legendRenderLab() {
          legendLoadBrandAsset();
          const title = { reflex: 'LEGEND REFLEX', lane: 'LEGEND LANE', cipher: 'COLOR CIPHER', pilot: 'ORBIT PILOT' }[legendGameId] || 'LEGEND LAB';
          let h = '<div class="pt-row2"><button class="pt-btn small" data-go="games">← CATALOGO</button><button class="pt-btn small" data-go="arcade">ARCADE</button></div><div class="pt-sec">ZERO THE LEGEND · LAB</div><div class="pt-card"><div class="pt-note">Sfide rapide, touch-first e ricompense in ZeroCoin. Scegli una modalità.</div><div class="pt-row2">' + legendCreateChallengeButton('reflex','REFLEX') + legendCreateChallengeButton('lane','LANE') + legendCreateChallengeButton('cipher','CIPHER') + legendCreateChallengeButton('pilot','PILOT') + '</div></div><div class="pt-card"><h3 style="font-family:Orbitron,sans-serif;margin:0;color:#ffe600">' + title + '</h3><div id="legend-box" style="margin-top:12px"></div></div>';
          h += legendAddFeaturePanel();
          legendMount(h);
          const box = document.getElementById('legend-box');
          if (legendGameId === 'reflex') legendPlayReflex(box); else if (legendGameId === 'lane') legendPlayLane(box); else if (legendGameId === 'cipher') legendPlayCipher(box); else legendPlayPilot(box);
          legendBindLab(ptBodyEl);
        }

        // ============================================================
        // ZEROARCADE PORTAL — 10 pages wrapped around the arena game:
        // home, games, ZeroCrash, shop, ZeroCoin wallet, dashboard,
        // leaderboards, news, support and the staff/admin panel.
        // ============================================================
        const portalEl = document.getElementById('portal');
        const ptNavEl = document.getElementById('pt-nav');
        const ptBodyEl = document.getElementById('pt-body');
        const ptCloseBtn = document.getElementById('pt-close');
        const ptMenuToggle = document.getElementById('pt-menu-toggle');
        const ptUserBtn = document.getElementById('pt-user');
        const ptWalletEl = document.getElementById('pt-wallet');
        const ptSearchFormEl = document.getElementById('pt-search-form');
        const ptGlobalSearchEl = document.getElementById('pt-global-search');
        const ptNotifyBtn = document.getElementById('pt-notify');
        const ptLanguagePickerEl = document.getElementById('pt-language-picker');
        const ptLanguageToggleEl = document.getElementById('pt-language-toggle');
        const ptLanguageMenuEl = document.getElementById('pt-language-menu');
        const ptLanguageFlagEl = document.getElementById('pt-language-flag');
        const ptLanguageCodeEl = document.getElementById('pt-language-code');
        const globalAnnouncementEl = document.getElementById('global-announcement');
        const globalAnnouncementTrackEl = document.getElementById('global-announcement-track');
        const globalAnnouncementTextEl = document.getElementById('global-announcement-text');
        const globalAnnouncementCloseEl = document.getElementById('global-announcement-close');
        const portalBtnEl = document.getElementById('portal-btn');
        const goPortalBtn = document.getElementById('go-portal-btn');
        const languageGateEl = document.getElementById('language-gate');
        const languageGateSelectEl = document.getElementById('language-gate-select');
        const languageGateContinueEl = document.getElementById('language-gate-continue');

        let ptPage = 'home';
        // The language gate is authoritative for this session. Profile loading is
        // asynchronous, so keep an explicit choice from being replaced by an
        // older saved/detected locale after the player has already chosen one.
        let currentGlobalAnnouncement = '';
        let adWatching = false;
        const crash = { state: 'idle', mult: 1, point: 0, bet: 100, auto: 0, elapsed: 0, last: 0, raf: null, pts: [], hist: [] };

        const PAGES = [
          { id: 'home', icon: '\u25C8', label: 'ZERO CORE' },
          { id: 'games', icon: '\u{1F3AE}', label: 'GIOCHI' },
          { id: 'arcade', icon: '\u{1F579}', label: 'MINI GIOCHI' },
          { id: 'crash', icon: '\u{1F680}', label: 'CRASH' },
          { id: 'shop', icon: '\u{1F6D2}', label: 'SHOP' },
          { id: 'wallet', icon: '\u{1FA99}', label: 'ZEROCOIN' },
          { id: 'quests', icon: '\u{1F3AF}', label: 'MISSIONI' },
          { id: 'pass', icon: '\u{1F39F}', label: 'ZERO PASS' },
          { id: 'music', icon: '\u266B', label: 'MUSICA' },
          { id: 'social', icon: '\u2726', label: 'SOCIAL OS' },
          { id: 'omni', icon: '\u{1F30D}', label: 'OMNI HUB' },
          { id: 'dash', icon: '\u{1F4CA}', label: 'DASHBOARD' },
          { id: 'ranks', icon: '\u{1F3C6}', label: 'CLASSIFICHE' },
          { id: 'news', icon: '\u{1F4F0}', label: 'NEWS' },
          { id: 'help', icon: '\u2139', label: 'SUPPORTO' },
          { id: 'admin', icon: '\u{1F6E0}', label: 'ADMIN' },
          { id: 'legend', icon: '\u{1F451}', label: 'LEGEND LAB' },
          { id: 'tutorials', icon: '\u{1F4DA}', label: 'TUTORIALS' },
        ];

        const GAMES = [
          {
            id: 'growth_orbit', name: 'Zero Arcade', icon: '\u{1F7E6}', live: true, badge: 'ARENA \u00B7 LIVE',
            desc: 'Divora particelle, schiva i predatori e domina l\u2019arena neon. Split, dash e god mode.',
            art: 'linear-gradient(135deg,#00f0ff,#7b2cff)',
          },
          {
            id: 'zero_crash', name: 'ZeroCrash', icon: '\u{1F680}', live: true, badge: 'ZEROCOIN \u00B7 LIVE', page: 'crash',
            desc: 'Punta ZeroCoin sul razzo e incassa prima del crash. Moltiplicatori fino a 60x.',
            art: 'linear-gradient(135deg,#ff7a18,#ff2d6b)',
          },
          { id: 'neon_dash', name: 'Neon Dash', icon: '\u26A1', desc: 'Corsa a ostacoli luminosi a velocit\u00E0 folle.', art: 'linear-gradient(135deg,#00ffa2,#0066ff)' },
          { id: 'rocket_rescue', name: 'Rocket Rescue', icon: '\u{1F6F0}', desc: 'Recupera celle di carburante tra i detriti spaziali.', art: 'linear-gradient(135deg,#ffe600,#ff7a18)' },
          { id: 'gravity_bounce', name: 'Gravity Bounce', icon: '\u{1F300}', desc: 'Inverti la gravit\u00E0 e scala la torre di spine.', art: 'linear-gradient(135deg,#b388ff,#ff2d95)' },
          { id: 'orbit_royale', name: 'Orbit Royale', icon: '\u{1F451}', desc: 'Battle royale di celle: l\u2019arena si restringe.', art: 'linear-gradient(135deg,#7fd4ff,#5865f2)' },
          { id: 'zero_puzzle', name: 'Zero Puzzle', icon: '\u{1F9E9}', desc: 'Puzzle a blocchi neon con sfide giornaliere.', art: 'linear-gradient(135deg,#00bfa0,#004e92)' },
        ];

        // 50 nuovi titoli: i primi 10 sono mini-giochi già giocabili nel portale
        const GAME_ART = [
          'linear-gradient(135deg,#00f0ff,#7b2cff)', 'linear-gradient(135deg,#ff7a18,#ff2d6b)',
          'linear-gradient(135deg,#00ffa2,#0066ff)', 'linear-gradient(135deg,#ffe600,#ff7a18)',
          'linear-gradient(135deg,#b388ff,#ff2d95)', 'linear-gradient(135deg,#7fd4ff,#5865f2)',
          'linear-gradient(135deg,#00bfa0,#004e92)', 'linear-gradient(135deg,#ff2d95,#3a0ca3)',
        ];
        const MORE_GAMES = [
          ['mini_reaction', 'Reflex Zero', '\u26A1', 'mini', 'Tocca appena diventa verde: meno millisecondi, pi\u00F9 ZeroCoin.', 'reaction'],
          ['mini_coin', 'ZeroFlip', '\u{1FA99}', 'casino', 'Testa o croce: raddoppia la puntata in un secondo.', 'coin'],
          ['mini_dice', 'ZeroDice', '\u{1F3B2}', 'casino', 'Punta su alto o basso e tira il dado neon.', 'dice'],
          ['mini_slots', 'Zero Slots', '\u{1F3B0}', 'casino', 'Tre rulli al neon, jackpot fino a 12x.', 'slots'],
          ['mini_mines', 'Zero Mines', '\u{1F48E}', 'casino', 'Scava celle sicure e incassa prima della mina.', 'mines'],
          ['mini_memory', 'Memory Matrix', '\u{1F9E0}', 'puzzle', 'Ripeti la sequenza luminosa sempre pi\u00F9 lunga.', 'memory'],
          ['mini_clicker', 'Tap Frenzy', '\u{1F449}', 'arcade', 'Quanti tap riesci a fare in 5 secondi?', 'clicker'],
          ['mini_higher', 'Higher or Lower', '\u{1F0CF}', 'casino', 'Indovina se la carta successiva \u00E8 pi\u00F9 alta o pi\u00F9 bassa.', 'higher'],
          ['mini_rps', 'Rock Zero', '\u270A', 'arcade', 'Morra cinese al neon contro il bot dell\u2019arena.', 'rps'],
          ['mini_wheel', 'Ruota ZeroCoin', '\u{1F3A1}', 'casino', 'Giro gratis ogni 15 minuti, fino a 1000 ZC.', 'wheel'],
          ['neon_snake', 'Neon Snake', '\u{1F40D}', 'arcade', 'Il serpente luminoso che cresce senza fine.'],
          ['zero_breakout', 'Zero Breakout', '\u{1F9F1}', 'arcade', 'Distruggi i mattoni neon con la pallina al plasma.'],
          ['pixel_invaders', 'Pixel Invaders', '\u{1F47E}', 'arcade', 'Difendi l\u2019orbita dagli invasori a pixel.'],
          ['cyber_pong', 'Cyber Pong', '\u{1F3D3}', 'sport', 'Il classico duello di racchette, versione cyber.'],
          ['turbo_kart', 'Turbo Kart', '\u{1F3CE}', 'sport', 'Kart al neon su circuiti sospesi nello spazio.'],
          ['blob_sumo', 'Blob Sumo', '\u{1F93C}', 'arena', 'Spingi i rivali fuori dalla piattaforma circolare.'],
          ['orbit_miner', 'Orbit Miner', '\u26CF', 'strategy', 'Estrai minerali dagli asteroidi e potenzia la base.'],
          ['laser_maze', 'Laser Maze', '\u{1F311}', 'puzzle', 'Rifletti il laser fino al nucleo evitando le trappole.'],
          ['stack_tower', 'Stack Tower', '\u{1F3D7}', 'arcade', 'Impila blocchi luminosi con precisione millimetrica.'],
          ['zero_tetrix', 'Zero Tetrix', '\u{1F7E6}', 'puzzle', 'Incastri veloci di blocchi al neon.'],
          ['match_nova', 'Match Nova', '\u{1F48E}', 'puzzle', 'Allinea tre gemme stellari e innesca combo.'],
          ['sudoku_neon', 'Sudoku Neon', '\u{1F522}', 'puzzle', 'Sudoku illuminato con sfide giornaliere.'],
          ['word_orbit', 'Word Orbit', '\u{1F524}', 'puzzle', 'Componi parole in orbita prima del timer.'],
          ['zero_chess', 'Zero Chess', '\u265F', 'strategy', 'Scacchi al neon contro l\u2019IA del portale.'],
          ['mahjong_nova', 'Mahjong Nova', '\u{1F004}', 'puzzle', 'Coppie di tessere luminose da eliminare.'],
          ['solitaire_zero', 'Solitaire Zero', '\u{1F0A1}', 'puzzle', 'Solitario classico con mazzo olografico.'],
          ['zero_poker', 'Zero Poker', '\u{1F0CF}', 'casino', 'Poker a 5 carte contro i bot dell\u2019arcade.'],
          ['blackjack_nova', 'Blackjack Nova', '\u{1F0B1}', 'casino', 'Arriva a 21 senza sballare.'],
          ['roulette_zero', 'Roulette Zero', '\u{1F3AF}', 'casino', 'Rosso, nero o pieno: la ruota neon decide.'],
          ['plinko_zero', 'Plinko Zero', '\u26AA', 'casino', 'Lascia cadere la sfera tra i pioli luminosi.'],
          ['keno_cosmos', 'Keno Cosmos', '\u{1F52D}', 'casino', 'Scegli i numeri e guarda le stelle estratte.'],
          ['scratch_nova', 'Scratch Nova', '\u{1F39F}', 'casino', 'Gratta e vinci digitale del portale.'],
          ['zero_towers', 'Zero Towers', '\u{1F5FC}', 'strategy', 'Tower defense contro ondate di virus.'],
          ['cell_defense', 'Cell Defense', '\u{1F6E1}', 'strategy', 'Proteggi il nucleo dai predatori in ondate.'],
          ['virus_wars', 'Virus Wars', '\u2623', 'strategy', 'Conquista i nodi infettandoli uno a uno.'],
          ['nano_empire', 'Nano Empire', '\u{1F3ED}', 'strategy', 'Costruisci un impero di nano-colonie.'],
          ['gravity_golf', 'Gravity Golf', '\u26F3', 'sport', 'Golf spaziale con gravit\u00E0 dei pianeti.'],
          ['neon_hoops', 'Neon Hoops', '\u{1F3C0}', 'sport', 'Canestri al neon a tempo.'],
          ['zero_soccer', 'Zero Soccer', '\u26BD', 'sport', 'Calcio 2v2 con celle al plasma.'],
          ['cyber_tennis', 'Cyber Tennis', '\u{1F3BE}', 'sport', 'Rally velocissimi su campo olografico.'],
          ['drift_zero', 'Drift Zero', '\u{1F697}', 'sport', 'Derapate perfette su asfalto luminoso.'],
          ['hover_race', 'Hover Race', '\u{1F6F8}', 'sport', 'Gare anti-gravit\u00E0 a velocit\u00E0 supersonica.'],
          ['jet_runner', 'Jet Runner', '\u{1F680}', 'arcade', 'Runner infinito con jetpack al plasma.'],
          ['lava_jump', 'Lava Jump', '\u{1F30B}', 'arcade', 'Salta di piattaforma in piattaforma sopra la lava.'],
          ['ninja_dash', 'Ninja Dash', '\u{1F977}', 'arcade', 'Scatti, pareti e katana al neon.'],
          ['quantum_escape', 'Quantum Escape', '\u{1F6AA}', 'puzzle', 'Escape room quantistica a stanze generate.'],
          ['pixel_farm', 'Pixel Farm', '\u{1F33E}', 'strategy', 'Coltiva celle luminose e vendile per ZC.'],
          ['idle_tycoon', 'Zero Idle Tycoon', '\u{1F4C8}', 'strategy', 'Automatizza la tua catena di arcade.'],
          ['beat_orbit', 'Beat Orbit', '\u{1F3B5}', 'arcade', 'Rhythm game sincronizzato con la colonna sonora.'],
          ['trivia_zero', 'Trivia Zero', '\u2753', 'puzzle', 'Quiz lampo su gaming e spazio.'],
          ['zero_bowling', 'Bowling Nova', '\u{1F3B3}', 'sport', 'Strike al neon con palla antigravitazionale.'],
        ];

        // 50 nuovi titoli della stagione Zero The Legend.
        // I primi quattro sono prototipi giocabili nel Legend Lab; gli altri sono schede catalogo pronte per futuri moduli.
        const LEGEND_GAMES = [
          { id: 'legend_reflex', name: 'Legend Reflex', icon: '\u26A1', cat: 'arcade', desc: 'Colpisci il nucleo dorato appena appare e scala la classifica.', live: true, legend: 'reflex' },
          { id: 'legend_lane', name: 'Legend Lane', icon: '\u{1F3CE}', cat: 'arcade', desc: 'Cambia corsia, evita i droni e sopravvivi alla velocità orbitale.', live: true, legend: 'lane' },
          { id: 'legend_cipher', name: 'Color Cipher', icon: '\u{1F3A8}', cat: 'puzzle', desc: 'Decifra la sequenza cromatica prima che il reattore si resetti.', live: true, legend: 'cipher' },
          { id: 'legend_pilot', name: 'Orbit Pilot', icon: '\u{1F6F8}', cat: 'sport', desc: 'Guida la navetta tra gli anelli di energia e raccogli stelle.', live: true, legend: 'pilot' },
          { id: 'star_forge', name: 'Star Forge', icon: '\u2692', cat: 'strategy', desc: 'Forgia reliquie stellari e costruisci il tuo arsenale.' },
          { id: 'astro_harvest', name: 'Astro Harvest', icon: '\u{1F33E}', cat: 'strategy', desc: 'Raccogli cristalli cosmici prima della tempesta solare.' },
          { id: 'pulse_runner', name: 'Pulse Runner', icon: '\u{1F3C3}', cat: 'arcade', desc: 'Corri tra impulsi laser sincronizzati con il beat.' },
          { id: 'neon_climb', name: 'Neon Climb', icon: '\u{1F9D7}', cat: 'arcade', desc: 'Scala una torre infinita di piattaforme magnetiche.' },
          { id: 'comet_catch', name: 'Comet Catch', icon: '\u2604', cat: 'arcade', desc: 'Acchiappa comete rare senza bruciare lo scudo.' },
          { id: 'void_defender', name: 'Void Defender', icon: '\u{1F6E1}', cat: 'strategy', desc: 'Difendi il portale dal buco nero in espansione.' },
          { id: 'prism_shift', name: 'Prism Shift', icon: '\u{1F308}', cat: 'puzzle', desc: 'Cambia spettro e attraversa barriere dello stesso colore.' },
          { id: 'circuit_breaker', name: 'Circuit Breaker', icon: '\u26A1', cat: 'puzzle', desc: 'Collega i nodi prima che il circuito vada in sovraccarico.' },
          { id: 'echo_memory', name: 'Echo Memory', icon: '\u{1F4A0}', cat: 'puzzle', desc: 'Memorizza e ripeti echi di luce sempre più complessi.' },
          { id: 'gravity_grid', name: 'Gravity Grid', icon: '\u{1F300}', cat: 'puzzle', desc: 'Ruota la griglia gravitazionale e porta il nucleo al centro.' },
          { id: 'rocket_relay', name: 'Rocket Relay', icon: '\u{1F680}', cat: 'sport', desc: 'Passa il carburante tra navette senza perdere quota.' },
          { id: 'solar_siege', name: 'Solar Siege', icon: '\u2600', cat: 'strategy', desc: 'Frena l’assalto solare con torrette orbitali.' },
          { id: 'orbital_chef', name: 'Orbital Chef', icon: '\u{1F468}\u200D\u{1F373}', cat: 'arcade', desc: 'Cucina ricette spaziali mentre la cucina ruota.' },
          { id: 'quantum_cards', name: 'Quantum Cards', icon: '\u{1F0CF}', cat: 'casino', desc: 'Scegli la probabilità giusta nel mazzo quantico.' },
          { id: 'zero_mahjong', name: 'Zero Mahjong', icon: '\u{1F004}', cat: 'puzzle', desc: 'Abbina tessere olografiche prima del collasso.' },
          { id: 'pixel_duel', name: 'Pixel Duel', icon: '\u{1F3AE}', cat: 'arcade', desc: 'Sfida un rivale in un duello a colpi di pixel.' },
          { id: 'laser_league', name: 'Laser League', icon: '\u{1F52B}', cat: 'sport', desc: 'Segna punti nei corridoi laser della lega.' },
          { id: 'hover_hockey', name: 'Hover Hockey', icon: '\u{1F3D2}', cat: 'sport', desc: 'Spingi il disco energetico oltre il campo magnetico.' },
          { id: 'plasma_pool', name: 'Plasma Pool', icon: '\u{1F3B1}', cat: 'sport', desc: 'Buca sfere al plasma con angoli impossibili.' },
          { id: 'cosmic_checkers', name: 'Cosmic Checkers', icon: '\u26C0', cat: 'strategy', desc: 'Dama tattica su una plancia in orbita.' },
          { id: 'nova_connect', name: 'Nova Connect', icon: '\u{1F517}', cat: 'puzzle', desc: 'Connetti le stelle senza incrociare le rotte.' },
          { id: 'asteroid_miner', name: 'Asteroid Miner', icon: '\u26CF', cat: 'strategy', desc: 'Scava asteroidi e vendi minerali rari.' },
          { id: 'portal_painter', name: 'Portal Painter', icon: '\u{1F58C}', cat: 'puzzle', desc: 'Dipingi portali con la traiettoria perfetta.' },
          { id: 'signal_sprint', name: 'Signal Sprint', icon: '\u{1F4E1}', cat: 'arcade', desc: 'Segui il segnale corretto tra falsi beacon.' },
          { id: 'meteor_match', name: 'Meteor Match', icon: '\u{1F4AB}', cat: 'puzzle', desc: 'Abbina meteoriti dello stesso nucleo per creare combo.' },
          { id: 'cyber_climber', name: 'Cyber Climber', icon: '\u{1F9D7}', cat: 'arcade', desc: 'Salta sui nodi cyber senza cadere nel vuoto.' },
          { id: 'reactor_rescue', name: 'Reactor Rescue', icon: '\u2622', cat: 'strategy', desc: 'Salva un reattore instabile in tempo record.' },
          { id: 'moonbase_builder', name: 'Moonbase Builder', icon: '\u{1F3D7}', cat: 'strategy', desc: 'Espandi la tua base sulla faccia nascosta della luna.' },
          { id: 'drone_swarm', name: 'Drone Swarm', icon: '\u{1F916}', cat: 'arcade', desc: 'Schiva uno sciame di droni intelligenti.' },
          { id: 'light_matrix', name: 'Light Matrix', icon: '\u{1F4A1}', cat: 'puzzle', desc: 'Accendi tutta la matrice con il minor numero di mosse.' },
          { id: 'turbo_tunnel', name: 'Turbo Tunnel', icon: '\u{1F6F0}', cat: 'arcade', desc: 'Sfreccia nel tunnel e attraversa i checkpoint.' },
          { id: 'starlight_slots', name: 'Starlight Slots', icon: '\u{1F3B0}', cat: 'casino', desc: 'Rulli cosmici e simboli leggendari in arrivo.' },
          { id: 'galaxy_golf', name: 'Galaxy Golf', icon: '\u26F3', cat: 'sport', desc: 'Colpisci il nucleo sfruttando la gravità dei pianeti.' },
          { id: 'rocket_rumble', name: 'Rocket Rumble', icon: '\u{1F680}', cat: 'arena', desc: 'Arena di razzi, scudi e collisioni spettacolari.' },
          { id: 'mecha_arena', name: 'Mecha Arena', icon: '\u{1F916}', cat: 'arena', desc: 'Personalizza il mecha e conquista il ring.' },
          { id: 'starship_sudoku', name: 'Starship Sudoku', icon: '\u{1F522}', cat: 'puzzle', desc: 'Numeri stellari e logica a gravità zero.' },
          { id: 'byte_brawler', name: 'Byte Brawler', icon: '\u{1F94A}', cat: 'arcade', desc: 'Combo digitali contro boss generati dal portale.' },
          { id: 'orbit_orchestra', name: 'Orbit Orchestra', icon: '\u{1F3B6}', cat: 'arcade', desc: 'Crea melodie sincronizzando satelliti musicali.' },
          { id: 'nebula_navigator', name: 'Nebula Navigator', icon: '\u{1F52D}', cat: 'strategy', desc: 'Traccia rotte sicure dentro la nebulosa.' },
          { id: 'cosmic_courier', name: 'Cosmic Courier', icon: '\u{1F4E6}', cat: 'sport', desc: 'Consegna pacchi stellari evitando i detriti.' },
          { id: 'zero_kart_rally', name: 'Zero Kart Rally', icon: '\u{1F3CE}', cat: 'sport', desc: 'Rally futuristico con curve a gravità invertita.' },
          { id: 'crystal_caverns', name: 'Crystal Caverns', icon: '\u{1F48E}', cat: 'strategy', desc: 'Esplora caverne e raccogli cristalli ancestrali.' },
          { id: 'dark_matter_dash', name: 'Dark Matter Dash', icon: '\u{1F311}', cat: 'arcade', desc: 'Scatta nella materia oscura senza perdere la rotta.' },
          { id: 'solar_flare', name: 'Solar Flare', icon: '\u{1F525}', cat: 'arcade', desc: 'Usa il flare per spingerti oltre il limite.' },
          { id: 'last_cell', name: 'The Last Cell', icon: '\u{1F7E3}', cat: 'arena', desc: 'L’ultima cellula rimasta decide il destino dell’arena.' },
          { id: 'legend_quest', name: 'Legend Quest', icon: '\u{1F451}', cat: 'strategy', desc: 'Una campagna a episodi per diventare la leggenda Zero.' },
        ];
        MORE_GAMES.forEach((g, i) => {
          GAMES.push({
            id: g[0], name: g[1], icon: g[2], cat: g[3], desc: g[4],
            mini: g[5] || null, live: !!g[5],
            badge: g[5] ? 'MINI \u00B7 LIVE' : 'IN ARRIVO',
            art: GAME_ART[i % GAME_ART.length],
          });
        });
        legendAddCatalogGames(LEGEND_GAMES);
        GAMES[0].cat = 'arena';
        GAMES[1].cat = 'casino';
        GAMES.forEach((g) => { if (!g.cat) g.cat = 'arcade'; });

        const GAME_CATS = [
          { id: 'all', label: 'TUTTI' }, { id: 'fav', label: '\u2605 PREFERITI' },
          { id: 'mini', label: 'GIOCABILI' }, { id: 'arena', label: 'ARENA' },
          { id: 'casino', label: 'ZEROCOIN' }, { id: 'arcade', label: 'ARCADE' },
          { id: 'puzzle', label: 'PUZZLE' }, { id: 'sport', label: 'SPORT' },
          { id: 'strategy', label: 'STRATEGIA' },
        ];
        let gameCat = 'all';
        let gameSearch = '';
        let socialSearch = '';

        const SHOP_ITEMS = [
          { id: 'mass_boost', cat: 'boost', icon: '\u{1F9EC}', name: 'Nucleo Denso', desc: '+25% massa iniziale in ogni partita.', price: 900 },
          { id: 'dash_chip', cat: 'boost', icon: '\u26A1', name: 'Chip Dash', desc: '-25% cooldown di dash e god mode.', price: 700 },
          { id: 'magnet_core', cat: 'boost', icon: '\u{1F9F2}', name: 'Nucleo Magnetico', desc: 'Magnete sempre attivo: le particelle ti seguono.', price: 1500 },
          { id: 'shield_start', cat: 'boost', icon: '\u{1F6E1}', name: 'Scudo di Partenza', desc: 'Inizi ogni partita con lo scudo attivo.', price: 600 },
          { id: 'coin_mult', cat: 'boost', icon: '\u{1F4B0}', name: 'Moltiplicatore ZC', desc: '+50% ZeroCoin guadagnati a fine partita.', price: 1200 },
          { id: 'xp_boost', cat: 'boost', icon: '\u2B50', name: 'Amplificatore XP', desc: '+25% XP profilo.', price: 800 },
          { id: 'extra_life', cat: 'cons', icon: '\u2764', name: 'Vita Extra', desc: 'Rinasci sul posto quando vieni divorato.', price: 300, stack: true },
          { id: 'skin_gold', cat: 'skin', icon: '\u{1F7E1}', name: 'Skin Aurum', desc: 'Cella dorata da campione.', price: 400, color: '#ffd54a' },
          { id: 'skin_void', cat: 'skin', icon: '\u{1F7E3}', name: 'Skin Void', desc: 'Viola abissale con nucleo scuro.', price: 400, color: '#b388ff' },
          { id: 'skin_plasma', cat: 'skin', icon: '\u{1F534}', name: 'Skin Plasma', desc: 'Rosso plasma incandescente.', price: 400, color: '#ff3b6b' },
          { id: 'skin_toxic', cat: 'skin', icon: '\u{1F7E2}', name: 'Skin Toxic', desc: 'Verde tossico fluorescente.', price: 400, color: '#00ffa2' },
          { id: 'vip_pass', cat: 'role', icon: '\u{1F451}', name: 'VIP Pass', desc: 'Ruolo VIP: +10% XP, +25% ZC, cooldown ridotti.', price: 2500, role: 'vip' },
          { id: 'helper_pass', cat: 'role', icon: '\u{1F6E0}', name: 'Helper Pass', desc: 'Ruolo HELPER: accesso agli strumenti di supporto.', price: 6000, role: 'helper' },
        ];

        const COIN_PACKS = [
          { id: 'daily', icon: '\u{1F381}', name: 'Pack Spark', zc: 250, req: 'Bonus giornaliero (ogni 20h)' },
          { id: 'ad', icon: '\u{1F4FA}', name: 'Pack Nova', zc: 120, req: 'Guarda lo spot sponsor (10s)' },
          { id: 'xp', icon: '\u2B50', name: 'Pack Quantum', zc: 100, req: 'Converti 500 XP profilo' },
          { id: 'level', icon: '\u{1F48E}', name: 'Pack Legend', zc: 1000, req: 'Sblocco unico al livello 5+' },
        ];

        const PROMOS = { 'ZEROSTART': 500, 'ORBIT100': 100, 'CRASHKING': 1000, 'GROWTHZERO': 750 };

        const NEWS = [
          { t: '50 nuovi giochi + colonna sonora', d: 'Il catalogo sale a oltre 55 titoli con 10 mini-giochi giocabili subito (Reflex Zero, Zero Mines, Slots, Ruota ZeroCoin e altri) e una colonna sonora completa: lobby, arena e bonus, con jukebox nella sezione MUSICA.' },
          { t: 'Missioni e Zero Pass', d: 'Ogni giorno 4 missioni nuove e 20 tier di Zero Pass da sbloccare giocando: ZeroCoin, vite extra e XP profilo.' },
          { t: 'Apre Zero Arcade', d: 'Il portale ufficiale \u00E8 online con 15 sezioni: giochi, mini-giochi, ZeroCrash, shop interno, wallet ZeroCoin, missioni, pass, musica, social, dashboard e pannello admin.' },
          { t: 'Zero Arcade \u00E8 il gioco #1', d: 'L\u2019arena neon guida la sezione giochi: split con SPACE, feed con W, dash con SHIFT e god mode con G.' },
          { t: 'Economia ZeroCoin', d: 'Ogni partita paga ZeroCoin in base a massa, rivali divorati e tempo di sopravvivenza. Spendili nello shop interno.' },
          { t: 'Ruoli e staff', d: 'VIP, Helper, Mod e Admin sbloccano strumenti diversi. I pass sono acquistabili nello shop o con codice ruolo.' },
        ];

        function updateWalletUi() {
          const el = document.getElementById('pt-coins');
          if (el) el.textContent = fmt(profile.coins || 0);
          const pill = document.getElementById('pill-coins');
          if (pill) pill.textContent = fmt(profile.coins || 0) + ' ZC';
          const noticeCount = (profile.inbox || []).filter((m) => !m.claimed).length;
          const noticeEl = document.getElementById('pt-notify-count');
          if (noticeEl) {
            noticeEl.textContent = noticeCount > 9 ? '9+' : String(noticeCount);
            noticeEl.classList.toggle('show', noticeCount > 0);
          }
        }

        function ptStat(label, value) {
          return '<div class="pt-stat">' + label + '<b>' + value + '</b></div>';
        }
        function ptQuick(page, icon, label) {
          return '<button class="pt-quick" data-go="' + page + '"><b>' + icon + '</b>' + label + '</button>';
        }
        function ptGameCard(g) {
          const live = !!g.live;
          const fav = !!(profile.favs && profile.favs[g.id]);
          return '<div class="pt-game"><div class="pt-game-art" style="background:' + g.art + '">' + g.icon + '</div>' +
            '<div class="pt-game-info"><h4>' + g.name + '</h4>' +
            '<span class="pt-badge' + (live ? '' : ' soon') + '">' + (g.badge || 'IN ARRIVO') + '</span>' +
            '<p>' + g.desc + '</p>' +
            '<button class="pt-btn small' + (live ? ' primary' : '') + '" data-play="' + g.id + '">' +
            (live ? '\u25B6 GIOCA' : '\u{1F512} PRESTO') + '</button></div>' +
            '<button class="fav-btn" data-fav="' + g.id + '" title="Preferito">' + (fav ? '\u2605' : '\u2606') + '</button></div>';
        }
        function pushRecent(id) {
          if (!Array.isArray(profile.recent)) profile.recent = [];
          profile.recent = [id].concat(profile.recent.filter((x) => x !== id)).slice(0, 6);
          queueSave();
        }
        function toggleFav(id) {
          if (!profile.favs || typeof profile.favs !== 'object') profile.favs = {};
          if (profile.favs[id]) delete profile.favs[id];
          else { profile.favs[id] = 1; unlock('fav', 'Primo gioco nei preferiti!'); }
          playUiSound('tap');
          queueSave();
          renderGameList();
        }
        function gameMatches(g) {
          const q = gameSearch.trim().toLowerCase();
          if (q && (g.name + ' ' + (g.desc || '')).toLowerCase().indexOf(q) < 0) return false;
          if (gameCat === 'all') return true;
          if (gameCat === 'fav') return !!(profile.favs && profile.favs[g.id]);
          if (gameCat === 'mini') return !!g.live;
          return (g.cat || 'arcade') === gameCat;
        }
        function bindGameCards(scope) {
          scope.querySelectorAll('[data-play]').forEach((b) => {
            b.setAttribute('data-bd', '1');
            b.addEventListener('click', (e) => { e.preventDefault(); launchGame(b.dataset.play); });
          });
          scope.querySelectorAll('[data-fav]').forEach((b) => {
            b.setAttribute('data-bd', '1');
            b.addEventListener('click', (e) => { e.preventDefault(); toggleFav(b.dataset.fav); });
          });
        }
        function renderGameList() {
          const box = document.getElementById('pt-glist');
          if (!box) return;
          const list = GAMES.filter(gameMatches);
          box.innerHTML = '<div class="pt-note">' + list.length + ' titoli \u00B7 ' +
            GAMES.filter((g) => g.live).length + ' giocabili ora</div>' +
            (list.length ? list.map(ptGameCard).join('') : '<div class="pt-note">Nessun gioco trovato.</div>');
          bindGameCards(box);
          const chips = document.getElementById('pt-gcats');
          if (chips) chips.value = gameCat;
        }
        function ptNewsCard(n) {
          return '<div class="pt-card pt-news"><h4>' + n.t + '</h4><p>' + n.d + '</p></div>';
        }

        // ---------------- Pages ----------------
        function renderPtHome() {
          const nm = profile.loggedIn ? escapeHtml(profile.name) : PT_T('guest');
          let h = '<div class="pt-hero"><div class="pt-kicker">ZEROARCADE \u00B7 ' + PT_T('kicker') + '</div>' +
            '<h1>' + PT_T('welcome') + ' ' + nm + '!</h1>' +
            '<p>Dieci sezioni, giochi live, valuta ZeroCoin e uno shop interno. Inizia dall\u2019arena neon.</p>' +
            '<div class="pt-hero-row"><button class="pt-btn primary" data-play="growth_orbit">\u25B6 GROWTH ORBIT</button>' +
            '<button class="pt-btn gold" data-go="wallet">\u{1FA99} ' + PT_T('getZc') + '</button></div></div>';
          h += '<div class="pt-stats4">' + ptStat('ZeroCoin', fmt(profile.coins || 0)) +
            ptStat(PT_T('level'), profile.level || 1) +
            ptStat(PT_T('bestMass'), Math.round(profile.bestMass || 0)) +
            ptStat(PT_T('role'), roleLabel()) + '</div>';
          h += '<div class="pt-sec">' + PT_T('featured') + '</div>' + GAMES.slice(0, 2).map(ptGameCard).join('');
          h += '<div class="pt-sec">' + PT_T('sections') + '</div><div class="pt-grid2">' +
            ptQuick('games', '\u{1F3AE}', PT_T('games')) + ptQuick('arcade', '\u{1F579}', PT_T('arcade')) +
            ptQuick('crash', '\u{1F680}', PT_T('crash')) + ptQuick('shop', '\u{1F6D2}', PT_T('shop')) +
            ptQuick('wallet', '\u{1FA99}', PT_T('wallet')) + ptQuick('quests', '\u{1F3AF}', PT_T('quests')) +
            ptQuick('pass', '\u{1F39F}', PT_T('pass')) + ptQuick('music', '\u266B', PT_T('music')) +
            ptQuick('social', '\u{1F91D}', PT_T('social')) + ptQuick('dash', '\u{1F4CA}', PT_T('dash')) +
            ptQuick('ranks', '\u{1F3C6}', PT_T('ranks')) + ptQuick('news', '\u{1F4F0}', PT_T('news')) +
            ptQuick('help', '\u2139', PT_T('help')) + ptQuick('admin', '\u{1F6E0}', PT_T('admin')) + '</div>';
          ensureQuests();
          const qToday = (profile.quests && profile.quests.items) || [];
          h += '<div class="pt-sec">' + PT_T('dailyQuests') + '</div><div class="pt-card">' +
            qToday.map((q) => '<div class="pt-tx"><span>' + escapeHtml(q.label) + '</span><b class="' +
              (q.claimed ? 'plus' : '') + '">' + Math.floor(q.prog || 0) + '/' + q.goal + '</b></div>').join('') +
            '<button class="pt-btn wide primary" data-go="quests">\u{1F3AF} ' + PT_T('quests') + '</button></div>';
          h += '<div class="pt-sec">' + PT_T('nowPlaying') + '</div><div class="pt-card">' +
            '<div class="pt-tx"><span>\u266B ' + currentTrackName() + (S.musicOn ? '' : ' (muta)') +
            '</span><button class="pt-btn small" data-go="music">' + PT_T('jukebox') + '</button></div></div>';
          h += '<div class="pt-sec">ZERO THE LEGEND</div><div class="pt-card"><div class="pt-note">Grafica 3D, logo leggendario e nuove sfide del Legend Lab: ' + legendFeatureCount() + '/' + LEGEND_FEATURES.length + ' funzioni scoperte.</div><div class="pt-row2"><button class="pt-btn gold" data-go="legend">\u{1F451} ' + PT_T('legend') + '</button><button class="pt-btn" data-go="music">\u266B AUDIO</button></div></div>';
          h += '<div class="pt-sec">' + PT_T('latestNews') + '</div>' + ptNewsCard(NEWS[0]);
          ptBodyEl.innerHTML = h;
          legendBindTilt(ptBodyEl);
        }

        function renderPtGames() {
          const running = !gameOver && playerCells.length > 0 && survivalTime > 0.5;
          let h = '<div class="pt-sec">' + PT_T('gameList') + ' (' + GAMES.length + ')</div>';
          if (running) {
            h += '<div class="pt-card"><div class="pt-note">' + PT_T('runningGame') + ' (massa ' +
              Math.round(player.mass) + ').</div><button class="pt-btn primary wide" data-play="growth_orbit">\u25B6 ' + PT_T('continueGame') + '</button></div>';
          }
          h += '<input class="pt-search" id="pt-gsearch" type="text" placeholder="\u{1F50D} ' + PT_T('search') + '" value="' +
            escapeHtml(gameSearch) + '" />';
          h += '<label class="pt-nav-label" for="pt-gcats">' + PT_T('category') + '</label>' +
            '<select id="pt-gcats" class="pt-category-select" aria-label="' + PT_T('category') + '">' +
            GAME_CATS.map((c) => '<option value="' + c.id + '"' + (c.id === gameCat ? ' selected' : '') + '>' + c.label + '</option>').join('') +
            '</select>';
          const rec = (profile.recent || []).map((id) => GAMES.find((g) => g.id === id)).filter(Boolean);
          if (rec.length) {
            h += '<div class="pt-sec">' + PT_T('recent') + '</div><div class="pt-chiprow">' +
              rec.map((g) => '<button class="pt-tab" data-play="' + g.id + '">' + g.icon + ' ' + g.name + '</button>').join('') +
              '</div>';
          }
          h += '<div id="pt-glist"></div>';
          h += '<div class="pt-note">' + PT_T('catalogInfo') + '</div>';
          legendBindTilt(ptBodyEl);
          ptBodyEl.innerHTML = h;
          renderGameList();
          const sIn = document.getElementById('pt-gsearch');
          if (sIn) sIn.addEventListener('input', () => { gameSearch = sIn.value; renderGameList(); });
          const chips = document.getElementById('pt-gcats');
          if (chips) chips.addEventListener('change', () => {
            gameCat = chips.value;
            playUiSound('tap');
            renderGameList();
          });
        }

        function crashPointRoll() {
          const r = Math.random();
          return Math.min(60, Math.max(1.01, 0.97 / Math.max(0.0001, 1 - r)));
        }
        function renderPtCrash() {
          const st = profile.crash || { plays: 0, wins: 0, best: 0, profit: 0 };
          let h = '<div class="pt-sec">ZEROCRASH \u00B7 PUNTA E INCASSA</div>';
          h += '<div class="pt-card"><canvas id="crash-canvas" width="640" height="300"></canvas>' +
            '<div id="crash-mult">1.00x</div>' +
            '<div class="pt-note" id="crash-state">Imposta la puntata e premi PUNTA. Incassa prima del crash!</div>' +
            '<div class="pt-row"><label>Puntata ZC</label><input class="pt-input" type="number" id="crash-bet" value="' +
            crash.bet + '" min="50" step="50" />' +
            '<label>Auto-cash</label><input class="pt-input" type="number" id="crash-auto" value="' +
            (crash.auto || 0) + '" min="0" step="0.5" /></div>' +
            '<div class="pt-row2"><button class="pt-btn primary" id="crash-start">PUNTA</button>' +
            '<button class="pt-btn gold" id="crash-cash">INCASSA</button></div>' +
            '<div class="pt-chips" id="crash-hist"></div></div>';
          h += '<div class="pt-stats4">' + ptStat('Round', st.plays || 0) + ptStat('Incassati', st.wins || 0) +
            ptStat('Miglior cash', (st.best || 0).toFixed(2) + 'x') +
            ptStat('Profitto ZC', fmt(st.profit || 0)) + '</div>';
          h += '<div class="pt-note">Gioco dimostrativo con valuta virtuale ZeroCoin: nessun denaro reale, nessun pagamento.</div>';
          ptBodyEl.innerHTML = h;
          const bet = document.getElementById('crash-bet');
          const auto = document.getElementById('crash-auto');
          if (bet) bet.addEventListener('change', () => { crash.bet = Math.max(50, Math.round(Number(bet.value) || 50)); bet.value = crash.bet; });
          if (auto) auto.addEventListener('change', () => { crash.auto = Math.max(0, Number(auto.value) || 0); });
          const sb = document.getElementById('crash-start');
          const cb = document.getElementById('crash-cash');
          if (sb) sb.addEventListener('click', (e) => { e.preventDefault(); startCrashRound(); });
          if (cb) cb.addEventListener('click', (e) => { e.preventDefault(); cashOutCrash(); });
          renderCrashHistory();
          updateCrashLabels();
          drawCrash();
        }
        function renderCrashHistory() {
          const el = document.getElementById('crash-hist');
          if (!el) return;
          el.innerHTML = crash.hist.slice(0, 12).map((r) =>
            '<span class="pt-chip' + (r.won ? ' good' : '') + '">' + r.mult.toFixed(2) + 'x</span>').join('');
        }
        function updateCrashLabels() {
          const m = document.getElementById('crash-mult');
          const s = document.getElementById('crash-state');
          const sb = document.getElementById('crash-start');
          const cb = document.getElementById('crash-cash');
          if (m) {
            m.textContent = crash.mult.toFixed(2) + 'x';
            m.className = crash.state === 'crashed' ? 'bust' : (crash.state === 'cashed' ? 'win' : '');
          }
          if (s) {
            if (crash.state === 'running') s.textContent = 'In volo \u2014 puntata ' + fmt(crash.bet) + ' ZC. Incassa!';
            else if (crash.state === 'crashed') s.textContent = '\u{1F4A5} CRASH a ' + crash.point.toFixed(2) + 'x \u2014 puntata persa.';
            else if (crash.state === 'cashed') s.textContent = '\u2714 Incassato a ' + crash.mult.toFixed(2) + 'x!';
            else s.textContent = 'Imposta la puntata e premi PUNTA. Incassa prima del crash!';
          }
          if (sb) sb.disabled = crash.state === 'running';
          if (cb) cb.disabled = crash.state !== 'running';
        }
        function drawCrash() {
          const cv = document.getElementById('crash-canvas');
          if (!cv) return;
          const c2 = cv.getContext('2d');
          const W = cv.width, H = cv.height;
          c2.clearRect(0, 0, W, H);
          c2.fillStyle = 'rgba(2,6,18,0.9)';
          c2.fillRect(0, 0, W, H);
          c2.strokeStyle = 'rgba(0,240,255,0.12)';
          c2.lineWidth = 1;
          c2.beginPath();
          for (let i = 1; i < 6; i++) {
            const y = (H / 6) * i;
            c2.moveTo(0, y); c2.lineTo(W, y);
          }
          c2.stroke();
          const pts = crash.pts;
          if (pts.length > 1) {
            const maxT = Math.max(4, pts[pts.length - 1].t);
            const maxM = Math.max(2, pts[pts.length - 1].m);
            const bust = crash.state === 'crashed';
            c2.strokeStyle = bust ? '#ff3b6b' : '#00ffa2';
            c2.lineWidth = 5;
            c2.beginPath();
            for (let i = 0; i < pts.length; i++) {
              const x = (pts[i].t / maxT) * (W - 20) + 10;
              const y = H - 14 - ((pts[i].m - 1) / (maxM - 1)) * (H - 40);
              if (i === 0) c2.moveTo(x, y); else c2.lineTo(x, y);
            }
            c2.stroke();
            const lx = (pts[pts.length - 1].t / maxT) * (W - 20) + 10;
            const ly = H - 14 - ((pts[pts.length - 1].m - 1) / (maxM - 1)) * (H - 40);
            c2.fillStyle = bust ? '#ff3b6b' : '#ffe600';
            c2.beginPath();
            c2.arc(lx, ly, 9, 0, Math.PI * 2);
            c2.fill();
          }
        }
        function startCrashRound() {
          if (crash.state === 'running') return;
          const bet = Math.max(50, Math.round(crash.bet || 50));
          if (!spendCoins(bet, 'ZeroCrash puntata')) return;
          crash.bet = bet;
          crash.state = 'running';
          crash.mult = 1;
          crash.point = crashPointRoll();
          crash.elapsed = 0;
          crash.pts = [{ t: 0, m: 1 }];
          crash.last = performance.now();
          if (!profile.crash) profile.crash = { plays: 0, wins: 0, best: 0, profit: 0 };
          profile.crash.plays = (profile.crash.plays || 0) + 1;
          profile.crash.profit = (profile.crash.profit || 0) - bet;
          questProgress('crash', 1);
          addPassXp(20);
          unlock('crash', 'Primo round su ZeroCrash!');
          updateCrashLabels();
          if (crash.raf) cancelAnimationFrame(crash.raf);
          crash.raf = requestAnimationFrame(crashLoop);
        }
        function crashLoop() {
          if (crash.state !== 'running') { crash.raf = null; return; }
          const now = performance.now();
          let dt = (now - crash.last) / 1000;
          crash.last = now;
          if (dt > 0.11) dt = 0.11;
          crash.elapsed += dt;
          crash.mult = Math.pow(1.085, crash.elapsed * 3.1);
          crash.pts.push({ t: crash.elapsed, m: crash.mult });
          if (crash.pts.length > 400) crash.pts.shift();
          if (crash.mult >= crash.point) {
            crash.mult = crash.point;
            crash.state = 'crashed';
            crash.hist.unshift({ mult: crash.point, won: false });
            if (crash.hist.length > 20) crash.hist.length = 20;
            notify('\u{1F4A5} Crash a ' + crash.point.toFixed(2) + 'x', '#ff5c7a');
            queueSave();
            drawCrash(); updateCrashLabels(); renderCrashHistory();
            crash.raf = null;
            return;
          }
          if (crash.auto > 1 && crash.mult >= crash.auto) { cashOutCrash(); return; }
          drawCrash();
          updateCrashLabels();
          crash.raf = requestAnimationFrame(crashLoop);
        }
        function cashOutCrash() {
          if (crash.state !== 'running') return;
          crash.state = 'cashed';
          if (crash.raf) { cancelAnimationFrame(crash.raf); crash.raf = null; }
          const win = Math.round(crash.bet * crash.mult);
          addCoins(win, 'ZeroCrash cash-out ' + crash.mult.toFixed(2) + 'x');
          if (!profile.crash) profile.crash = { plays: 0, wins: 0, best: 0, profit: 0 };
          profile.crash.wins = (profile.crash.wins || 0) + 1;
          profile.crash.profit = (profile.crash.profit || 0) + win;
          profile.crash.best = Math.max(profile.crash.best || 0, crash.mult);
          if (crash.mult >= 5) unlock('crash5', 'Cash-out oltre 5x su ZeroCrash!');
          crash.hist.unshift({ mult: crash.mult, won: true });
          if (crash.hist.length > 20) crash.hist.length = 20;
          notify('\u2714 +' + fmt(win) + ' ZC a ' + crash.mult.toFixed(2) + 'x', '#00ffa2');
          queueSave();
          drawCrash(); updateCrashLabels(); renderCrashHistory();
        }

        function renderPtShop() {
          let h = '<div class="pt-sec">SHOP INTERNO \u00B7 ' + fmt(profile.coins || 0) + ' ZC</div>';
          h += '<div class="pt-note">Tutti gli acquisti usano la valuta virtuale ZeroCoin guadagnata giocando.</div>';
          const groups = [
            { c: 'boost', t: 'POTENZIAMENTI PERMANENTI' },
            { c: 'cons', t: 'CONSUMABILI' },
            { c: 'skin', t: 'SKIN CELLA' },
            { c: 'role', t: 'PASS RUOLO' },
          ];
          for (const g of groups) {
            h += '<div class="pt-sec">' + g.t + '</div>';
            for (const it of SHOP_ITEMS.filter((x) => x.cat === g.c)) {
              const cnt = itemCount(it.id);
              const ownedPerm = cnt > 0 && !it.stack;
              h += '<div class="pt-item' + (cnt > 0 ? ' owned' : '') + '">' +
                '<div class="pt-item-icon">' + it.icon + '</div>' +
                '<div class="pt-item-info"><h5>' + it.name + (it.stack && cnt ? ' \u00D7' + cnt : '') + '</h5><p>' + it.desc + '</p></div>' +
                '<div style="text-align:right"><div class="pt-price">' + fmt(it.price) + ' ZC</div>' +
                (ownedPerm
                  ? (it.color
                    ? '<button class="pt-btn small" data-equip="' + it.id + '">EQUIPAGGIA</button>'
                    : '<span class="pt-badge">ATTIVO</span>')
                  : '<button class="pt-btn small primary" data-buy="' + it.id + '">COMPRA</button>') +
                '</div></div>';
            }
          }
          h += '<button class="pt-btn wide gold" data-go="wallet">\u{1FA99} SERVONO ZEROCOIN?</button>';
          ptBodyEl.innerHTML = h;
          ptBodyEl.querySelectorAll('[data-buy]').forEach((b) =>
            b.addEventListener('click', (e) => { e.preventDefault(); buyItem(b.dataset.buy); }));
          ptBodyEl.querySelectorAll('[data-equip]').forEach((b) =>
            b.addEventListener('click', (e) => { e.preventDefault(); equipShopSkin(b.dataset.equip); }));
        }
        function buyItem(id) {
          const it = SHOP_ITEMS.find((x) => x.id === id);
          if (!it) return;
          if (!it.stack && itemCount(id) > 0) { notify('Gi\u00E0 in tuo possesso', '#7fd4ff'); return; }
          if (!spendCoins(it.price, 'Shop: ' + it.name)) return;
          profile.inventory[id] = itemCount(id) + 1;
          if (it.role) grantRole(it.role);
          if (it.color) equipShopSkin(id);
          notify('\u2714 Acquistato: ' + it.name, '#00ffa2');
          unlock('shop', 'Primo acquisto nello shop!');
          queueSave();
          renderPortal();
        }
        function equipShopSkin(id) {
          const it = SHOP_ITEMS.find((x) => x.id === id);
          if (!it || !it.color) return;
          if (itemCount(id) <= 0) { notify('\u26A0 Skin non acquistata', '#ff5c7a'); return; }
          profile.equippedSkin = id;
          applyPresetSkin(it.color);
          updateProfileChip();
          queueSave();
          if (ptPage === 'shop' || ptPage === 'dash') renderPortal();
        }

        function renderPtWallet() {
          if (!profile.promos || typeof profile.promos !== 'object') profile.promos = {};
          const now = Date.now();
          const dailyLeft = Math.max(0, 20 * 3600000 - (now - (profile.lastDaily || 0)));
          const adLeft = Math.max(0, 180000 - (now - (profile.lastAd || 0)));
          let h = '<div class="pt-hero"><div class="pt-kicker">WALLET ZEROCOIN</div><h1>' +
            fmt(profile.coins || 0) + ' ZC</h1><p>La valuta del portale: guadagnala giocando, riscattala con bonus e codici, spendila nello shop interno.</p>' +
            '<div class="pt-hero-row"><button class="pt-btn primary" data-go="shop">\u{1F6D2} VAI ALLO SHOP</button>' +
            '<button class="pt-btn" data-play="growth_orbit">\u25B6 GUADAGNA GIOCANDO</button></div></div>';
          h += '<div class="pt-sec">PACCHETTI ZEROCOIN</div>';
          for (const p of COIN_PACKS) {
            let state = '';
            if (p.id === 'daily' && dailyLeft > 0) state = 'Tra ' + Math.ceil(dailyLeft / 3600000) + 'h';
            if (p.id === 'ad' && adLeft > 0) state = Math.ceil(adLeft / 1000) + 's';
            if (p.id === 'xp' && (profile.xp || 0) < 500) state = 'XP insuff.';
            if (p.id === 'level' && ((profile.level || 1) < 5 || profile.promos.pack_legend)) {
              state = (profile.level || 1) < 5 ? 'LV 5' : 'Riscattato';
            }
            h += '<div class="pt-item"><div class="pt-item-icon">' + p.icon + '</div>' +
              '<div class="pt-item-info"><h5>' + p.name + '</h5><p>' + p.req + '</p></div>' +
              '<div style="text-align:right"><div class="pt-price">+' + fmt(p.zc) + ' ZC</div>' +
              (state ? '<span class="pt-badge soon">' + state + '</span>'
                : '<button class="pt-btn small primary" data-claim="' + p.id + '">RISCATTA</button>') +
              '</div></div>';
          }
          h += '<div class="pt-note">' + PT_T('walletNote') + '</div>';
          h += '<div class="pt-sec">CODICI PROMO</div><div class="pt-card"><div class="pt-row">' +
            '<input class="pt-input" type="text" id="promo-in" placeholder="ZEROSTART" />' +
            '<button class="pt-btn small primary" id="promo-go">USA</button></div>' +
            '<div class="pt-note">Prova: ZEROSTART \u00B7 ORBIT100 \u00B7 CRASHKING \u00B7 GROWTHZERO (una volta ciascuno).</div></div>';
          h += '<div class="pt-sec">MOVIMENTI</div><div class="pt-card">' +
            ((profile.txs && profile.txs.length)
              ? profile.txs.map((t) => '<div class="pt-tx"><span>' + escapeHtml(t.r) + '</span><b class="' +
                (t.a >= 0 ? 'plus' : 'minus') + '">' + (t.a >= 0 ? '+' : '') + fmt(t.a) + '</b></div>').join('')
              : '<div class="pt-note">Nessun movimento registrato.</div>') + '</div>';
          ptBodyEl.innerHTML = h;
          ptBodyEl.querySelectorAll('[data-claim]').forEach((b) =>
            b.addEventListener('click', (e) => { e.preventDefault(); claimPack(b.dataset.claim); }));
          const pg = document.getElementById('promo-go');
          if (pg) pg.addEventListener('click', (e) => {
            e.preventDefault();
            const inp = document.getElementById('promo-in');
            redeemPromo(inp ? inp.value : '');
          });
        }
        function claimPack(id) {
          const pack = COIN_PACKS.find((p) => p.id === id);
          if (!pack) return;
          const now = Date.now();
          if (id === 'daily') {
            if (now - (profile.lastDaily || 0) < 20 * 3600000) return;
            profile.lastDaily = now;
            profile.dailyStreak = (profile.dailyStreak || 0) + 1;
            const bonus = pack.zc + Math.min(5, profile.dailyStreak) * 50;
            addCoins(bonus, 'Bonus giornaliero (streak ' + profile.dailyStreak + ')');
            notify('\u{1F381} +' + bonus + ' ZC bonus giornaliero', '#ffc94a');
            unlock('daily', 'Primo bonus giornaliero!');
          } else if (id === 'ad') {
            if (adWatching || now - (profile.lastAd || 0) < 180000) return;
            adWatching = true;
            notify('\u{1F4FA} Spot sponsor in corso... 10s', '#7fd4ff');
            setTimeout(() => {
              adWatching = false;
              profile.lastAd = Date.now();
              addCoins(pack.zc, 'Spot sponsor');
              notify('\u2714 +' + pack.zc + ' ZC dallo spot', '#00ffa2');
              if (ptPage === 'wallet') renderPortal();
            }, 10000);
          } else if (id === 'xp') {
            if ((profile.xp || 0) < 500) return;
            profile.xp -= 500;
            profile.level = profileLevelFromXp(profile.xp);
            addCoins(pack.zc, 'Conversione 500 XP');
            notify('\u2728 500 XP convertiti in ' + pack.zc + ' ZC', '#b388ff');
          } else if (id === 'level') {
            if ((profile.level || 1) < 5 || profile.promos.pack_legend) return;
            profile.promos.pack_legend = true;
            addCoins(pack.zc, 'Pack Legend (livello 5)');
            notify('\u{1F48E} +' + pack.zc + ' ZC Pack Legend', '#ffe600');
          }
          queueSave();
          renderPortal();
        }
        function redeemPromo(code) {
          const key = String(code || '').trim().toUpperCase();
          if (!key) return;
          if (ROLE_CODES[key]) { redeemCode(key); renderPortal(); return; }
          if (!PROMOS[key]) { notify('\u26A0 Codice non valido', '#ff5c7a'); return; }
          if (!profile.promos || typeof profile.promos !== 'object') profile.promos = {};
          if (profile.promos[key]) { notify('\u26A0 Codice gi\u00E0 usato', '#ff5c7a'); return; }
          profile.promos[key] = true;
          addCoins(PROMOS[key], 'Codice promo ' + key);
          notify('\u{1F3AB} +' + PROMOS[key] + ' ZC con ' + key, '#ffc94a');
          unlock('promo', 'Primo codice promo riscattato!');
          renderPortal();
        }

        function renderPtDash() {
          const inv = SHOP_ITEMS.filter((x) => itemCount(x.id) > 0);
          let h = '<div class="pt-sec">' + PT_T('dash') + '</div>';
          h += '<div class="pt-card"><div class="pt-row"><div class="pt-item-icon" style="width:66px;height:66px;font-size:28px;' +
            avatarStyle() + '"></div><div class="pt-item-info"><h5>' +
            escapeHtml(profile.loggedIn ? profile.name : PT_T('guest')) + ' <span class="role-badge role-' +
            (profile.role || 'user') + '">' + roleLabel() + '</span></h5>' +
            '<p>LV ' + (profile.level || 1) + ' \u00B7 ' + Math.round(profile.xp || 0) + ' XP \u00B7 ' +
            fmt(profile.coins || 0) + ' ZC</p></div></div>' +
            '<div class="pt-row2"><button class="pt-btn small" id="dash-account">' + PT_T('account') + ' / LOGIN</button>' +
            '<button class="pt-btn small" id="dash-save">' + T('save') + '</button></div></div>';
          h += '<div class="pt-stats4">' +
            ptStat(PT_T('runs'), profile.runs || 0) +
            ptStat(PT_T('bestMass'), Math.round(profile.bestMass || 0)) +
            ptStat('Particelle', profile.totalParticles || 0) +
            ptStat('Rivali', profile.totalRivals || 0) +
            ptStat('Tempo totale', Math.floor((profile.totalTime || 0) / 60) + 'm') +
            ptStat(PT_T('trophies'), Object.keys(profile.achievements || {}).length) +
            ptStat('Crash round', (profile.crash && profile.crash.plays) || 0) +
            ptStat('Profitto crash', fmt((profile.crash && profile.crash.profit) || 0)) + '</div>';
          h += '<div class="pt-sec">' + PT_T('inventory') + ' (' + inv.length + ')</div>';
          h += inv.length
            ? inv.map((it) => '<div class="pt-item owned"><div class="pt-item-icon">' + it.icon + '</div>' +
              '<div class="pt-item-info"><h5>' + it.name + (it.stack ? ' \u00D7' + itemCount(it.id) : '') + '</h5><p>' + it.desc + '</p></div>' +
              (it.color ? '<button class="pt-btn small" data-equip="' + it.id + '">EQUIPAGGIA</button>' : '') + '</div>').join('')
            : '<div class="pt-card"><div class="pt-note">Inventario vuoto: visita lo shop interno.</div>' +
              '<button class="pt-btn wide primary" data-go="shop">APRI SHOP</button></div>';
          h += '<div class="pt-sec">' + PT_T('lastGames') + '</div><div class="pt-card">' +
            ((profile.history && profile.history.length)
              ? profile.history.map((r) => '<div class="pt-tx"><span>Massa ' + Math.round(r.m) + ' \u00B7 ' + r.t + 's</span><b class="plus">+' + r.c + ' ZC</b></div>').join('')
              : '<div class="pt-note">Nessuna partita registrata.</div>') + '</div>';
          h += '<div class="pt-sec">' + PT_T('trophies') + '</div><div class="pt-card ach-list">' +
            (Object.keys(profile.achievements || {}).length
              ? Object.keys(profile.achievements).map((k) => '<span class="ach">' + escapeHtml(k) + '</span>').join('')
              : '<span class="pt-note">Nessun trofeo ancora.</span>') + '</div>';
          ptBodyEl.innerHTML = h;
          ptBodyEl.querySelectorAll('[data-equip]').forEach((b) =>
            b.addEventListener('click', (e) => { e.preventDefault(); equipShopSkin(b.dataset.equip); }));
          const ac = document.getElementById('dash-account');
          if (ac) ac.addEventListener('click', (e) => { e.preventDefault(); openUserMenu(); });
          const sv = document.getElementById('dash-save');
          if (sv) sv.addEventListener('click', (e) => { e.preventDefault(); saveProfile(); notify('\u2714 Progressi salvati', '#00ffa2'); });
        }

        function renderPtRanks() {
          let h = '<div class="pt-sec">' + PT_T('globalRanks') + '</div>';
          h += '<div class="pt-card" id="pt-lb"><div class="pt-note">Caricamento...</div></div>';
          h += '<div class="pt-sec">' + PT_T('yourRecords') + '</div><div class="pt-stats4">' +
            ptStat(PT_T('bestMass'), Math.round(profile.bestMass || 0)) +
            ptStat(PT_T('level'), profile.level || 1) +
            ptStat('Miglior crash', ((profile.crash && profile.crash.best) || 0).toFixed(2) + 'x') +
            ptStat(PT_T('runs'), profile.runs || 0) + '</div>';
          h += '<div class="pt-sec">' + PT_T('arenaRivals') + '</div><div class="pt-card">' +
            (aiCells.length
              ? aiCells.slice().sort((a, b) => b.mass - a.mass).slice(0, 8)
                .map((a, i) => '<div class="pt-tx"><span>' + (i + 1) + '. ' + escapeHtml(a.name) + '</span><b>' + Math.round(a.mass) + '</b></div>').join('')
              : '<div class="pt-note">Nessun rivale attivo.</div>') + '</div>';
          ptBodyEl.innerHTML = h;
          const box = document.getElementById('pt-lb');
          if (box && mode === 'play' && !window.__growthOrbitStandalone && typeof lib.getTopNEntriesFromLeaderboard === 'function') {
            (async () => {
              try {
                const data = await lib.getTopNEntriesFromLeaderboard(10);
                const rows = (data && data.entries) || [];
                box.innerHTML = rows.length
                  ? rows.map((e, i) => '<div class="pt-tx"><span>' + (i + 1) + '. ' +
                    escapeHtml(e.username || e.name || e.playerName || 'Anonimo') + '</span><b>' +
                    Math.floor(e.score || 0) + '</b></div>').join('')
                  : '<div class="pt-note">Nessun punteggio registrato.</div>';
              } catch (err) {
                box.innerHTML = '<div class="pt-note">Classifica non disponibile.</div>';
              }
            })();
          } else if (box) {
            box.innerHTML = '<div class="pt-note">Classifica globale disponibile in modalit\u00E0 gioco.</div>';
          }
        }

        function renderPtNews() {
          let h = '<div class="pt-sec">' + PT_T('news') + ' & COMMUNITY</div>' + NEWS.map(ptNewsCard).join('');
          h += '<div class="pt-sec">CHAT DI ARENA</div><div class="pt-card">' +
            '<div class="pt-note">La chat globale vive dentro l\u2019arena: aprila col tasto CHAT in basso a sinistra o premi C / T. Usa /help per i comandi.</div>' +
            '<button class="pt-btn wide primary" data-play="growth_orbit">\u25B6 ' + PT_T('continueArena') + '</button></div>';
          h += '<div class="pt-sec">PATCH NOTES</div><div class="pt-card"><div class="pt-note">' +
            'v1.3 \u2014 Portale Zero Arcade con 10 pagine, ZeroCrash, shop interno, wallet ZeroCoin, dashboard e admin panel.<br>' +
            'v1.2 \u2014 Menu utente, ruoli, skin personalizzate e salvataggio progressi.<br>' +
            'v1.1 \u2014 Dash, god mode, power-up, minimappa e chat globale.</div></div>';
          ptBodyEl.innerHTML = h;
        }

        function renderPtHelp() {
          let h = '<div class="pt-sec">SUPPORTO & GUIDA</div>';
          h += '<div class="pt-card"><h4 style="margin:0 0 8px;font-family:Orbitron,sans-serif;color:#00f0ff">COMANDI</h4>' +
            '<div class="pt-note">Mouse / dito = movimento \u00B7 SPACE = split \u00B7 W = feed \u00B7 SHIFT = spin dash \u00B7 G = god mode \u00B7 ' +
            'rotellina = zoom \u00B7 TAB = mappa intera \u00B7 C/T = chat \u00B7 P = pausa \u00B7 H = aiuto \u00B7 ESC = menu utente \u00B7 Q = portale</div></div>';
          h += '<div class="pt-card"><h4 style="margin:0 0 8px;font-family:Orbitron,sans-serif;color:#00f0ff">COME FUNZIONANO I ZEROCOIN</h4>' +
            '<div class="pt-note">Guadagni ZC a fine partita (massa, rivali divorati, tempo), con il bonus giornaliero, gli spot, la conversione XP e i codici promo. ' +
            'Li spendi nello shop interno per potenziamenti, skin, consumabili e pass ruolo. Valuta virtuale: nessun pagamento reale.</div></div>';
          h += '<div class="pt-card"><h4 style="margin:0 0 8px;font-family:Orbitron,sans-serif;color:#00f0ff">RUOLI</h4>' +
            '<div class="pt-note">USER \u2192 VIP \u2192 HELPER \u2192 MOD \u2192 ADMIN. I pass si acquistano nello shop oppure con codice ruolo ' +
            '(ORBIT-VIP, ORBIT-HELPER, ORBIT-MOD, ORBIT-ADMIN) nel menu utente o nel wallet.</div></div>';
          h += '<div class="pt-card"><h4 style="margin:0 0 8px;font-family:Orbitron,sans-serif;color:#00f0ff">FAQ</h4>' +
            '<div class="pt-note"><b>I progressi si salvano?</b> S\u00EC, XP, ZeroCoin, inventario e impostazioni restano legati al tuo profilo.<br>' +
            '<b>Posso caricare una skin?</b> S\u00EC, anche GIF animate: menu utente (ESC) \u2192 SKIN.<br>' +
            '<b>Come torno al portale?</b> Tasto PORTALE in basso a destra o Q.</div></div>';
          h += '<div class="pt-card"><h4 style="margin:0 0 8px;font-family:Orbitron,sans-serif;color:#00f0ff">MUSICA E MINI GIOCHI</h4>' +
            '<div class="pt-note">La sezione MUSICA contiene il jukebox (lobby, arena, bonus e traccia generata live), il mixer e i temi del portale. ' +
            'La sezione MINI GIOCHI offre 10 titoli istantanei che pagano ZeroCoin e fanno avanzare missioni e Zero Pass.</div>' +
            '<div class="pt-row2"><button class="pt-btn small" data-go="music">\u266B MUSICA</button>' +
            '<button class="pt-btn small" data-go="arcade">\u{1F579} MINI GIOCHI</button></div></div>';
          h += '<button class="pt-btn wide" id="help-reset-view">RIPRISTINA INTERFACCIA</button>';
          ptBodyEl.innerHTML = h;
          const hr = document.getElementById('help-reset-view');
          if (hr) hr.addEventListener('click', (e) => {
            e.preventDefault();
            profile.settings = defaultSettings();
            S = profile.settings;
            applySettings();
            notify('Interfaccia ripristinata', '#7fd4ff');
            queueSave();
          });
        }

        // ============================================================
        // ZERO THE LEGEND TUTORIAL ACADEMY
        // A local, touch-first tutorial video series. The presenter image
        // and intro sting are optional host assets; speech is browser-local.
        // No external video service, account, upload, analytics, or API is used.
        // ============================================================
        const TUTORIAL_LESSONS = [
          { id: 'welcome', icon: '✦', title: 'Portal tour', text: 'Discover the Zero The Legend portal, its navigation, pages, notifications, language selector, and local profile.', voice: 'Welcome to Zero The Legend. In this lesson we tour the portal, navigation, language selector, and your local profile.' },
          { id: 'arena', icon: '◉', title: 'Zero Arcade arena', text: 'Move with mouse or touch, collect particles, avoid larger rivals, and use split, feed, dash, god mode, power-ups, and the minimap.', voice: 'In Zero Arcade, move with your mouse or finger, collect particles, avoid larger rivals, and use split, feed, dash, god mode, power-ups, and the minimap.' },
          { id: 'games', icon: '🎮', title: 'Games and mini-games', text: 'Browse the catalogue, filter by category, launch live games, and play the local mini-games for ZeroCoin and Pass experience.', voice: 'Browse the game catalogue, filter by category, launch live games, and play mini-games to earn ZeroCoin and Pass experience.' },
          { id: 'shop', icon: '🛒', title: 'Shop and inventory', text: 'Spend virtual ZeroCoin on permanent boosts, consumables, skins, and role passes. Equip owned skins from your inventory.', voice: 'The shop uses virtual ZeroCoin. Buy boosts, consumables, skins, and role passes, then equip owned items from your inventory.' },
          { id: 'wallet', icon: '🪙', title: 'ZeroCoin and ZeroCrash', text: 'Earn virtual currency from runs, quests, bonuses, mini-games, and the demonstrative ZeroCrash section. No real-money payments are used.', voice: 'Earn virtual ZeroCoin from runs, quests, bonuses, and mini-games. ZeroCrash is demonstrative only and uses no real money.' },
          { id: 'social', icon: '✧', title: 'ZeroSocial', text: 'Use the local feed, stories, reels, video channels, chat, groups, live posts, Nexus, and professional tools inside your profile.', voice: 'ZeroSocial combines feed, stories, reels, video channels, chat, groups, live posts, Nexus, and professional tools in one local profile.' },
          { id: 'settings', icon: '⚙', title: 'Settings and accessibility', text: 'Open the user menu to adjust language, sound, music, theme, minimap, chat, graphics, motion, colorblind mode, and HUD scale.', voice: 'Open the user menu to adjust language, sound, music, theme, minimap, chat, graphics, motion, colorblind mode, and HUD scale.' },
          { id: 'creator', icon: '👑', title: 'Legend Lab and creator tools', text: 'Try Legend Lab challenges, track achievements, save progress, export a local backup, and discover the Zero The Legend creator universe.', voice: 'In Legend Lab, try challenges, earn rewards, track achievements, save progress, export a local backup, and discover Zero The Legend creator tools.' },
        ];
        const TUTORIAL_SPEECH_LANGS = { it:'it-IT', en:'en-US', es:'es-ES', fr:'fr-FR', de:'de-DE', pt:'pt-PT', ru:'ru-RU', uk:'uk-UA', pl:'pl-PL', nl:'nl-NL', sv:'sv-SE', da:'da-DK', no:'nb-NO', fi:'fi-FI', el:'el-GR', cs:'cs-CZ', ro:'ro-RO', hu:'hu-HU', bg:'bg-BG', sr:'sr-RS', hr:'hr-HR', sk:'sk-SK', sl:'sl-SI', ar:'ar-SA', he:'he-IL', fa:'fa-IR', hi:'hi-IN', bn:'bn-BD', ur:'ur-PK', zh:'zh-CN', ja:'ja-JP', ko:'ko-KR', vi:'vi-VN', th:'th-TH', id:'id-ID', ms:'ms-MY', tl:'fil-PH', sw:'sw-KE', tr:'tr-TR' };
        let tutorialPlayback = { playing: false, index: 0, progress: 0, startedAt: 0, duration: 26 };
        let tutorialTimer = null;
        let tutorialIntroBuffer = null;
        let tutorialIntroPending = null;

        function tutorialState() {
          if (!profile.tutorial || typeof profile.tutorial !== 'object') profile.tutorial = { current: 0, completed: {}, watched: 0 };
          if (!profile.tutorial.completed || typeof profile.tutorial.completed !== 'object') profile.tutorial.completed = {};
          profile.tutorial.current = Math.max(0, Math.min(TUTORIAL_LESSONS.length - 1, Number(profile.tutorial.current) || 0));
          return profile.tutorial;
        }
        function tutorialVoiceText(lesson) {
          const lang = portalLanguage();
          if (lang === 'it') {
            const it = {
              welcome: 'Benvenuto su Zero The Legend. In questa lezione vediamo il portale, la navigazione, la lingua e il profilo locale.',
              arena: 'In Zero Arcade muoviti con mouse o dito, raccogli particelle, evita i rivali più grandi e usa split, feed, dash, god mode e power-up.',
              games: 'Esplora il catalogo, filtra i giochi, avvia i titoli disponibili e gioca ai mini-giochi per ottenere ZeroCoin ed esperienza Pass.',
              shop: 'Lo shop usa ZeroCoin virtuali. Compra potenziamenti, consumabili, skin e ruoli, poi equipaggia gli oggetti dal tuo inventario.',
              wallet: 'Guadagna ZeroCoin con partite, missioni, bonus e mini-giochi. ZeroCrash è dimostrativo e non usa denaro reale.',
              social: 'ZeroSocial riunisce feed, storie, reel, video, chat, gruppi, post live, Nexus e strumenti professionali nel tuo profilo locale.',
              settings: 'Apri il menu utente per regolare lingua, audio, musica, tema, minimappa, chat, grafica, movimento, daltonismo e scala HUD.',
              creator: 'Nel Legend Lab prova le sfide, ottieni ricompense, controlla i trofei, salva i progressi ed esporta un backup locale.'
            };
            return it[lesson.id] || lesson.voice;
          }
          return lesson.voice;
        }
        function tutorialStopSpeech() {
          if ('speechSynthesis' in window) {
            try { window.speechSynthesis.cancel(); } catch (e) {}
          }
        }
        function tutorialSpeak(lesson) {
          tutorialStopSpeech();
          if (!('speechSynthesis' in window) || typeof SpeechSynthesisUtterance === 'undefined') {
            notify(tutorialLocale('noSpeech'), '#7fd4ff');
            return;
          }
          try {
            const u = new SpeechSynthesisUtterance(tutorialVoiceText(lesson));
            u.lang = TUTORIAL_SPEECH_LANGS[portalLanguage()] || portalLanguage();
            u.rate = 0.94;
            u.pitch = 1.08;
            u.volume = 1;
            window.speechSynthesis.speak(u);
          } catch (e) {
            notify(tutorialLocale('noSpeech'), '#7fd4ff');
          }
        }
        function tutorialPlayIntro() {
          if (tutorialIntroBuffer && audioCtx) {
            try {
              const source = audioCtx.createBufferSource();
              const gain = audioCtx.createGain();
              source.buffer = tutorialIntroBuffer;
              gain.gain.value = 0.34 * sfxVol();
              source.connect(gain); gain.connect(audioCtx.destination); source.start(0);
              return;
            } catch (e) {}
          }
          if (tutorialIntroPending || !lib.getAsset) return;
          const asset = lib.getAsset('zero_tutorial_intro_audio');
          if (!asset || !asset.url) return;
          tutorialIntroPending = fetch(asset.url).then((r) => r.arrayBuffer()).then((buf) => {
            initAudio();
            return audioCtx ? audioCtx.decodeAudioData(buf) : null;
          }).then((decoded) => { tutorialIntroBuffer = decoded || null; }).catch(() => { tutorialIntroBuffer = null; });
        }
        function tutorialHostAsset() {
          const asset = lib.getAsset ? lib.getAsset('zero_tutorial_host_blonde') : null;
          return asset && asset.url ? asset.url : '';
        }
        function tutorialSelect(index) {
          tutorialStop();
          const st = tutorialState();
          st.current = Math.max(0, Math.min(TUTORIAL_LESSONS.length - 1, Number(index) || 0));
          queueSave();
          renderPortal();
        }
        function tutorialStop() {
          tutorialPlayback.playing = false;
          if (tutorialTimer) { clearInterval(tutorialTimer); tutorialTimer = null; }
          tutorialStopSpeech();
          const video = document.getElementById('tutorial-video');
          if (video) video.classList.remove('playing');
        }
        function tutorialStart() {
          const st = tutorialState();
          const index = st.current;
          const lesson = TUTORIAL_LESSONS[index];
          tutorialStop();
          tutorialPlayback = { playing: true, index: index, progress: 0, startedAt: performance.now(), duration: 26 };
          const video = document.getElementById('tutorial-video');
          const bar = document.getElementById('tutorial-progress-fill');
          const status = document.getElementById('tutorial-status');
          if (video) video.classList.add('playing');
          if (status) status.textContent = '00:00 / 00:26';
          initAudio();
          tutorialPlayIntro();
          tutorialSpeak(lesson);
          st.watched = (st.watched || 0) + 1;
          queueSave();
          tutorialTimer = setInterval(() => {
            const elapsed = (performance.now() - tutorialPlayback.startedAt) / 1000;
            tutorialPlayback.progress = Math.min(1, elapsed / tutorialPlayback.duration);
            if (bar) bar.style.width = (tutorialPlayback.progress * 100).toFixed(1) + '%';
            if (status) status.textContent = '00:' + String(Math.min(26, Math.floor(elapsed))).padStart(2, '0') + ' / 00:26';
            if (tutorialPlayback.progress >= 1) {
              clearInterval(tutorialTimer); tutorialTimer = null; tutorialPlayback.playing = false;
              st.completed[lesson.id] = true;
              addProfileXp(10); addPassXp(5);
              unlock('tutorial_' + lesson.id, 'Tutorial completato: ' + lesson.title);
              if (video) video.classList.remove('playing');
              if (status) status.textContent = tutorialLocale('lessonDone');
              queueSave();
              renderPortal();
            }
          }, 120);
        }
        function tutorialNext() {
          const st = tutorialState();
          tutorialStop();
          st.current = (st.current + 1) % TUTORIAL_LESSONS.length;
          queueSave();
          renderPortal();
        }
        function tutorialFaqMarkup() {
          const faq = [
            ['How do I start?', 'Open the portal with PORTALE or Q, choose TUTORIALS, select a lesson, and press START VIDEO.'],
            ['Is the presenter a real person?', 'No. The page uses a generated visual presenter and the browser local speech engine. No camera, account, or external call is required.'],
            ['Can I use every language?', 'Choose any language offered by the portal. The speech engine uses its matching local language when the browser provides a voice; text remains available as a fallback.'],
            ['Are ZeroCoin and ZeroCrash real money?', 'No. ZeroCoin is an in-game virtual currency. ZeroCrash is a demonstrative mini-game and does not process payments.'],
            ['Where are my progress and backups?', 'Progress belongs to the game profile. Use the dashboard or user menu to save, load, or export a local backup when available.'],
            ['Can I publish real videos or social posts?', 'The portal is an offline/local experience in this build. Tutorial playback, posts, chats, and social features stay inside the local game profile.'],
          ];
          return faq.map((item) => '<details><summary>' + escapeHtml(item[0]) + '</summary><p>' + escapeHtml(item[1]) + '</p></details>').join('');
        }
        function renderPtTutorials() {
          const st = tutorialState();
          const lesson = TUTORIAL_LESSONS[st.current] || TUTORIAL_LESSONS[0];
          const host = tutorialHostAsset();
          const completed = Object.keys(st.completed || {}).length;
          const lessonRows = TUTORIAL_LESSONS.map((l, i) => '<button class="tutorial-lesson' + (i === st.current ? ' on' : '') + (st.completed[l.id] ? ' done' : '') + '" data-tutorial-select="' + i + '"><span class="tutorial-lesson-icon">' + l.icon + '</span><span class="tutorial-lesson-info"><b>' + escapeHtml((i + 1) + '. ' + l.title) + '</b><span>' + escapeHtml(l.text) + '</span></span><span class="tutorial-check">' + (st.completed[l.id] ? '✓' : tutorialLocale('lessonOpen')) + '</span></button>').join('');
          let h = '<div class="tutorial-shell"><section class="tutorial-hero"><div><div class="tutorial-kicker">' + tutorialLocale('kicker') + '</div><h1>' + tutorialLocale('title') + '</h1><p>' + tutorialLocale('desc') + '</p><div class="tutorial-controls"><label class="tutorial-meta" for="tutorial-language">' + tutorialLocale('choose') + '</label><select id="tutorial-language" aria-label="' + tutorialLocale('choose') + '">' + WORLD_LANGUAGES.map((x) => '<option value="' + x[0] + '"' + (x[0] === portalLanguage() ? ' selected' : '') + '>' + x[1] + '</option>').join('') + '</select><span class="omni-badge">' + tutorialLocale('host') + '</span></div></div><div class="tutorial-host" id="tutorial-host">' + (!host ? '<div class="tutorial-host-fallback">' + tutorialLocale('host') + '<br>ZERO THE LEGEND</div>' : '') + '</div></section><div class="tutorial-tabs"><button class="tutorial-tab on">📺 ' + tutorialLocale('lessons') + '</button><button class="tutorial-tab">❓ ' + tutorialLocale('faq') + '</button><button class="tutorial-tab">© ' + tutorialLocale('legal') + '</button></div><div class="tutorial-grid"><main><section class="tutorial-card"><div class="tutorial-video" id="tutorial-video"><div class="tutorial-video-kicker">LESSON ' + (st.current + 1) + ' / ' + TUTORIAL_LESSONS.length + '</div><h2>' + escapeHtml(lesson.title) + '</h2><p>' + escapeHtml(lesson.text) + '</p><div class="tutorial-progress"><span id="tutorial-progress-fill"></span></div><div class="tutorial-row"><button class="pt-btn primary" id="tutorial-start">▶ ' + tutorialLocale('start') + '</button><button class="pt-btn gold" id="tutorial-next">' + tutorialLocale('next') + ' →</button><span class="tutorial-meta" id="tutorial-status">' + (st.completed[lesson.id] ? tutorialLocale('lessonDone') : tutorialLocale('noSpeech')) + '</span></div></div></section><section class="tutorial-card"><h3>' + tutorialLocale('lessons') + ' · ' + completed + '/' + TUTORIAL_LESSONS.length + '</h3><div class="tutorial-list">' + lessonRows + '</div></section></main><aside><section class="tutorial-card tutorial-faq"><h3>❓ ' + tutorialLocale('faq') + '</h3>' + tutorialFaqMarkup() + '</section><section class="tutorial-card tutorial-legal"><h3>© ' + tutorialLocale('legal') + '</h3><p><strong>' + tutorialLocale('creator') + '</strong></p><p>© 2025 Zero The Legend. All rights reserved. Zero The Legend, Zero Arcade, Zero Arcade, ZeroSocial, Legend Lab, game names, visual identity, tutorial scripts, interface design, generated presenter artwork, audio direction, and original portal content are protected works of their respective owner.</p><p>All game currency is virtual. This local tutorial academy does not sell real-money items, collect biometric data, access a camera, or connect external social accounts.</p></section></aside></div></div>';
          ptBodyEl.innerHTML = h;
          const hostEl = document.getElementById('tutorial-host');
          if (hostEl && host) hostEl.style.backgroundImage = 'url("' + host.replace(/"/g, '') + '")';
          const lang = document.getElementById('tutorial-language');
          if (lang) lang.addEventListener('change', () => { setSetting('language', lang.value); tutorialStop(); renderPortal(); });
          ptBodyEl.querySelectorAll('[data-tutorial-select]').forEach((b) => b.addEventListener('click', (e) => { e.preventDefault(); tutorialSelect(b.dataset.tutorialSelect); }));
          const start = document.getElementById('tutorial-start');
          if (start) start.addEventListener('click', (e) => { e.preventDefault(); tutorialStart(); });
          const next = document.getElementById('tutorial-next');
          if (next) next.addEventListener('click', (e) => { e.preventDefault(); tutorialNext(); });
        }

        function renderPtAdmin() {
          if (!hasPerm('helper')) {
            ptBodyEl.innerHTML = '<div class="pt-sec">ADMIN PANEL</div><div class="pt-card">' +
              '<div class="pt-note">Area riservata a HELPER, MOD e ADMIN. Riscatta un codice ruolo o acquista un pass nello shop.</div>' +
              '<div class="pt-row"><input class="pt-input" type="text" id="adm-code" placeholder="ORBIT-ADMIN" />' +
              '<button class="pt-btn small primary" id="adm-code-go">RISCATTA</button></div>' +
              '<button class="pt-btn wide" data-go="shop">VAI ALLO SHOP</button></div>';
            const go = document.getElementById('adm-code-go');
            if (go) go.addEventListener('click', (e) => {
              e.preventDefault();
              const inp = document.getElementById('adm-code');
              if (redeemCode(inp ? inp.value : '')) renderPortal();
            });
            return;
          }
          let h = '<div class="pt-sec">ADMIN PANEL \u00B7 ' + roleLabel() + '</div>';
          if (hasPerm('admin')) {
            const notices = socialHubState().portalNotices || [];
            h += '<div class="pt-card global-admin-card"><h3 style="margin:0 0 10px;font-family:Orbitron,sans-serif;color:#ffe600">📢 ANNUNCIO GLOBALE</h3>' +
              '<p class="pt-note">Invia un messaggio evidenziato nella barra superiore del portale e dell’arena corrente. L’avviso resta nel profilo locale e viene riproposto al prossimo avvio.</p>' +
              '<textarea id="global-announcement-input" maxlength="240" placeholder="Scrivi l’annuncio per tutta la community..."></textarea>' +
              '<div class="global-admin-preview">Anteprima: testo gigante in movimento da destra a sinistra + suono di attenzione.</div>' +
              '<div class="pt-row2"><button class="pt-btn primary" data-adm="announce">📢 INVIA A TUTTI</button>' +
              (notices.length ? '<button class="pt-btn small" data-adm="showannounce">MOSTRA ULTIMO</button>' : '') + '</div></div>';
          }
          h += '<div class="pt-stats4">' + ptStat('Rivali attivi', aiCells.length) +
            ptStat('Particelle', particles.length) + ptStat('Virus', viruses.length) +
            ptStat('Arena', aiFrozen ? 'CONGELATA' : 'ATTIVA') + '</div>';

          h += '<div class="pt-sec">STRUMENTI ARENA</div><div class="pt-card">' +
            '<button class="pt-btn wide" data-adm="hint">\u2139 HINT MINACCIA</button>' +
            '<button class="pt-btn wide gold" data-adm="power">\u25C8 TUTTI I POWER-UP</button>';
          if (hasPerm('mod')) {
            h += '<button class="pt-btn wide" data-adm="freeze">\u2744 CONGELA / RIATTIVA ARENA</button>' +
              '<button class="pt-btn wide" data-adm="food">\u2726 SPAWN 30 PARTICELLE</button>' +
              '<button class="pt-btn wide" data-adm="virus">\u2623 RIMUOVI VIRUS</button>';
          }
          if (hasPerm('admin')) {
            h += '<button class="pt-btn wide" data-adm="tp">\u2699 TELETRASPORTO AL CENTRO</button>' +
              '<button class="pt-btn wide gold" data-adm="god">\u2726 GOD MODE INFINITO</button>' +
              '<div class="pt-row"><input class="pt-input" type="number" id="adm-bot-count" value="3" min="1" max="100" />' +
              '<button class="pt-btn small" data-adm="bots">GENERA BOT</button></div>' +
              '<div class="pt-row2"><button class="pt-btn small" data-adm="botfollow">BOT SEGUI</button>' +
              '<button class="pt-btn small" data-adm="botfarm">BOT FARMA</button>' +
              '<button class="pt-btn small danger" data-adm="botstop">BOT STOP</button></div>' +
              '<div class="pt-note">Keybind: ' + escapeHtml(S.botFollowKey || 'F7') + ' / ' + escapeHtml(S.botFarmKey || 'F8') + ' / ' + escapeHtml(S.botStopKey || 'F9') + '</div>' +
              '<div class="pt-row"><input class="pt-input" type="number" id="adm-mass" value="500" />' +
              '<button class="pt-btn small" data-adm="mass">SET MASSA</button></div>';
          }
          h += '</div>';

          if (hasPerm('mod')) {
            h += '<div class="pt-sec">GESTIONE GIOCATORI / RIVALI</div><div class="pt-card">' +
              (aiCells.length
                ? aiCells.slice(0, 10).map((a) => '<div class="pt-tx"><span>' + escapeHtml(a.name) + ' \u00B7 ' +
                  Math.round(a.mass) + '</span><button class="pt-btn small danger" data-kick="' +
                  escapeHtml(a.name) + '">KICK</button></div>').join('')
                : '<div class="pt-note">Nessun rivale in arena.</div>') +
              '<button class="pt-btn wide" data-adm="chatclear">\u{1F9F9} PULISCI CHAT</button></div>';
          }

          h += '<div class="pt-sec">ACCOUNT CORRENTE</div><div class="pt-card">' +
            '<div class="pt-tx"><span>' + escapeHtml(profile.loggedIn ? profile.name : 'Ospite') +
            ' \u00B7 ' + roleLabel() + '</span><b>' + fmt(profile.coins || 0) + ' ZC</b></div>' +
            '<div class="pt-tx"><span>LV ' + (profile.level || 1) + ' \u00B7 ' + Math.round(profile.xp || 0) +
            ' XP \u00B7 partite ' + (profile.runs || 0) + '</span><b>' + (profile.provider || 'local') + '</b></div></div>';

          if (hasPerm('admin')) {
            h += '<div class="pt-sec">ECONOMIA ZEROCOIN</div><div class="pt-card">' +
              '<div class="pt-row"><input class="pt-input" type="number" id="adm-zc" value="1000" />' +
              '<button class="pt-btn small primary" data-adm="givezc">ACCREDITA</button>' +
              '<button class="pt-btn small danger" data-adm="zerozc">AZZERA</button></div>' +
              '<button class="pt-btn wide" data-adm="unlockall">\u{1F513} SBLOCCA TUTTO LO SHOP</button>' +
              '<button class="pt-btn wide danger" data-adm="clearinv">SVUOTA INVENTARIO</button>' +
              '<button class="pt-btn wide" data-adm="levelup">\u2B50 +5 LIVELLI PROFILO</button></div>';
            h += '<div class="pt-sec">RUOLI</div><div class="pt-card">' +
              ROLE_ORDER.map((r) => '<button class="pt-btn small" data-role="' + r + '">' + r.toUpperCase() + '</button>').join(' ') +
              '</div>';
          }

          h += '<div class="pt-sec">INTERFACCIA LIVE</div><div class="pt-card">' +
            '<button class="pt-btn small" data-set="showMinimap">Minimappa: ' + (S.showMinimap ? 'ON' : 'OFF') + '</button> ' +
            '<button class="pt-btn small" data-set="showChat">Chat: ' + (S.showChat ? 'ON' : 'OFF') + '</button> ' +
            '<button class="pt-btn small" data-set="hideBotChatter">Bot muti: ' + (S.hideBotChatter ? 'ON' : 'OFF') + '</button> ' +
            '<button class="pt-btn small" data-set="showFps">FPS: ' + (S.showFps ? 'ON' : 'OFF') + '</button></div>';

          h += '<div class="pt-sec">LOG TRANSAZIONI</div><div class="pt-card">' +
            ((profile.txs && profile.txs.length)
              ? profile.txs.slice(0, 10).map((t) => '<div class="pt-tx"><span>' + escapeHtml(t.r) + '</span><b class="' +
                (t.a >= 0 ? 'plus' : 'minus') + '">' + (t.a >= 0 ? '+' : '') + fmt(t.a) + '</b></div>').join('')
              : '<div class="pt-note">Nessuna transazione.</div>') + '</div>';

          if (hasPerm('admin')) {
            h += '<div class="pt-sec">ZONA PERICOLOSA</div>' +
              '<button class="pt-btn wide danger" data-adm="wipe">AZZERA PROFILO COMPLETO</button>';
          }
          ptBodyEl.innerHTML = h;

          ptBodyEl.querySelectorAll('[data-adm]').forEach((b) =>
            b.addEventListener('click', (e) => { e.preventDefault(); runAdminAction(b.dataset.adm); }));
          ptBodyEl.querySelectorAll('[data-kick]').forEach((b) =>
            b.addEventListener('click', (e) => { e.preventDefault(); adminKickRival(b.dataset.kick); renderPortal(); }));
          ptBodyEl.querySelectorAll('[data-role]').forEach((b) =>
            b.addEventListener('click', (e) => { e.preventDefault(); grantRole(b.dataset.role); renderPortal(); }));
          ptBodyEl.querySelectorAll('[data-set]').forEach((b) =>
            b.addEventListener('click', (e) => {
              e.preventDefault();
              setSetting(b.dataset.set, !S[b.dataset.set]);
              renderPortal();
            }));
        }

        function runAdminAction(action) {
          switch (action) {
            case 'hint': helperHint(); break;
            case 'power': adminMaxPowerups(); break;
            case 'freeze': adminToggleFreeze(); break;
            case 'food': adminSpawnFood(30); break;
            case 'virus': adminClearViruses(); break;
            case 'tp': adminTeleportCenter(); break;
            case 'god': adminToggleGodLock(); break;
            case 'bots': {
              const inp = document.getElementById('adm-bot-count');
              adminSpawnBots(inp ? inp.value : 3);
              break;
            }
            case 'botfollow': adminSetBotMode('follow'); break;
            case 'botfarm': adminSetBotMode('farm'); break;
            case 'botstop': adminSetBotMode('idle'); break;
            case 'chatclear': clearChatLog(); break;
            case 'announce': {
              if (!hasPerm('admin')) return;
              const input = document.getElementById('global-announcement-input');
              sendGlobalAnnouncement(input ? input.value : '');
              break;
            }
            case 'showannounce':
              if (!hasPerm('admin')) return;
              showLatestGlobalAnnouncement();
              notify('Ultimo annuncio globale mostrato', '#ffe600');
              break;
            case 'mass': {
              const inp = document.getElementById('adm-mass');
              adminSetMass(inp ? inp.value : 500);
              break;
            }
            case 'givezc': {
              if (!hasPerm('admin')) return;
              const inp = document.getElementById('adm-zc');
              const v = Math.max(1, Math.round(Number(inp && inp.value) || 1000));
              addCoins(v, 'Accredito admin');
              notify('\u{1FA99} +' + fmt(v) + ' ZC accreditati', '#ffc94a');
              break;
            }
            case 'zerozc':
              if (!hasPerm('admin')) return;
              profile.coins = 0;
              logTx(0, 'Wallet azzerato (admin)');
              updateWalletUi();
              notify('Wallet azzerato', '#ff5c7a');
              queueSave();
              break;
            case 'unlockall':
              if (!hasPerm('admin')) return;
              for (const it of SHOP_ITEMS) profile.inventory[it.id] = Math.max(1, itemCount(it.id));
              notify('\u{1F513} Shop sbloccato', '#00ffa2');
              queueSave();
              break;
            case 'clearinv':
              if (!hasPerm('admin')) return;
              profile.inventory = {};
              profile.equippedSkin = null;
              notify('Inventario svuotato', '#ff5c7a');
              queueSave();
              break;
            case 'levelup':
              if (!hasPerm('admin')) return;
              profile.xp += (Math.pow((profile.level || 1) + 5, 2) - Math.pow(profile.level || 1, 2)) * 120;
              profile.level = profileLevelFromXp(profile.xp);
              updateProfileChip();
              notify('\u2B50 Livello ' + profile.level, '#ffe600');
              queueSave();
              break;
            case 'wipe':
              if (!hasPerm('admin')) return;
              wipeProfile();
              break;
          }
          renderPortal();
        }

        // ============================================================
        // MINI-GIOCHI ISTANTANEI (pagano ZeroCoin, avanzano missioni e pass)
        // ============================================================
        const MINIS = [
          { id: 'reaction', icon: '\u26A1', name: 'Reflex Zero', desc: 'Tocca appena diventa verde: meno millisecondi, pi\u00F9 ZeroCoin.' },
          { id: 'coin', icon: '\u{1FA99}', name: 'ZeroFlip', desc: 'Testa o croce: raddoppia la puntata in un secondo.' },
          { id: 'dice', icon: '\u{1F3B2}', name: 'ZeroDice', desc: 'Punta su alto (4-6) o basso (1-3), vincita 1.9x.' },
          { id: 'slots', icon: '\u{1F3B0}', name: 'Zero Slots', desc: 'Tre rulli al neon: due uguali 2x, tris 12x.' },
          { id: 'mines', icon: '\u{1F48E}', name: 'Zero Mines', desc: 'Scava celle sicure e incassa prima della mina.' },
          { id: 'memory', icon: '\u{1F9E0}', name: 'Memory Matrix', desc: 'Ripeti la sequenza luminosa sempre pi\u00F9 lunga.' },
          { id: 'clicker', icon: '\u{1F449}', name: 'Tap Frenzy', desc: 'Quanti tap riesci a fare in 5 secondi?' },
          { id: 'higher', icon: '\u{1F0CF}', name: 'Higher or Lower', desc: 'Indovina se la carta successiva \u00E8 pi\u00F9 alta o pi\u00F9 bassa.' },
          { id: 'rps', icon: '\u270A', name: 'Rock Zero', desc: 'Morra cinese al neon contro il bot dell\u2019arena.' },
          { id: 'wheel', icon: '\u{1F3A1}', name: 'Ruota ZeroCoin', desc: 'Giro gratis ogni 15 minuti, fino a 1000 ZC.' },
        ];
        let miniId = null;
        let miniTimers = [];

        function miniTimeout(fn, ms) {
          const t = setTimeout(fn, ms);
          miniTimers.push(t);
          return t;
        }
        function clearMiniTimers() {
          miniTimers.forEach(clearTimeout);
          miniTimers = [];
        }
        function openMini(id) {
          clearMiniTimers();
          miniId = id;
          playUiSound('tap');
          setPtPage('arcade');
        }
        function closeMini() {
          clearMiniTimers();
          miniId = null;
          renderPortal();
        }
        function miniBetRow(def) {
          return '<div class="pt-row"><label>Puntata ZC</label>' +
            '<input class="pt-input" type="number" id="mini-bet" value="' + def + '" min="25" step="25" /></div>';
        }
        function miniBetVal(def) {
          const el = document.getElementById('mini-bet');
          let v = Math.round(Number(el && el.value) || def);
          v = Math.max(25, Math.min(100000, v));
          if (el) el.value = v;
          return v;
        }
        function miniSetOut(txt) {
          const o = document.getElementById('mini-out');
          if (o) o.innerHTML = txt;
        }
        function miniCoinsRefresh() {
          const c = document.getElementById('mini-coins');
          if (c) c.textContent = fmt(profile.coins || 0) + ' ZC';
          updateWalletUi();
        }
        function miniPay(amount, label) {
          if (amount > 0) { addCoins(amount, 'Mini-gioco: ' + label); playUiSound('win'); }
          else playUiSound('err');
          miniCoinsRefresh();
        }
        function miniPlayed(id) {
          if (!profile.minis || typeof profile.minis !== 'object') profile.minis = {};
          profile.minis[id] = (profile.minis[id] || 0) + 1;
          questProgress('mini', 1);
          addPassXp(25);
          unlock('mini', 'Primo mini-gioco su Zero Arcade!');
          queueSave();
        }

        const MINI_BUILD = {
          reaction: (box) => {
            box.innerHTML = '<button class="mini-big" id="rx">TOCCA PER INIZIARE</button>' +
              '<div class="mini-out" id="mini-out">Record personale: ' + (profile.bestReaction || 0) + ' ms</div>';
            const b = box.querySelector('#rx');
            let st = 'idle', t0 = 0;
            b.addEventListener('click', (e) => {
              e.preventDefault();
              if (st === 'idle') {
                st = 'wait';
                b.className = 'mini-big wait';
                b.textContent = 'ASPETTA IL VERDE...';
                miniTimeout(() => {
                  if (st !== 'wait') return;
                  st = 'go';
                  b.className = 'mini-big go';
                  b.textContent = 'ORA!';
                  t0 = performance.now();
                }, 900 + Math.random() * 2300);
              } else if (st === 'wait') {
                st = 'idle';
                b.className = 'mini-big';
                b.textContent = 'RIPROVA';
                playUiSound('err');
                miniSetOut('\u2716 Troppo presto!');
              } else {
                const ms = Math.round(performance.now() - t0);
                st = 'idle';
                b.className = 'mini-big';
                b.textContent = 'ANCORA';
                if (!profile.bestReaction || ms < profile.bestReaction) profile.bestReaction = ms;
                const pay = Math.max(15, Math.round(420 - ms));
                miniPlayed('reaction');
                miniPay(pay, 'Reflex Zero');
                if (ms < 250) unlock('reflex', 'Riflessi fulminei: sotto 250 ms!');
                miniSetOut(ms + ' ms \u2014 +' + pay + ' ZC (record ' + profile.bestReaction + ' ms)');
              }
            });
          },
          coin: (box) => {
            box.innerHTML = miniBetRow(100) +
              '<div class="mini-reels" id="cv">\u{1FA99}</div><div class="mini-out" id="mini-out">Scegli il lato.</div>' +
              '<div class="pt-row2"><button class="pt-btn primary" data-side="h">TESTA</button>' +
              '<button class="pt-btn gold" data-side="t">CROCE</button></div>';
            box.querySelectorAll('[data-side]').forEach((b) => b.addEventListener('click', (e) => {
              e.preventDefault();
              const bet = miniBetVal(100);
              if (!spendCoins(bet, 'ZeroFlip puntata')) return;
              const res = Math.random() < 0.5 ? 'h' : 't';
              box.querySelector('#cv').textContent = res === 'h' ? '\u{1F451}' : '\u2694';
              miniPlayed('coin');
              if (res === b.dataset.side) {
                miniPay(bet * 2, 'ZeroFlip');
                miniSetOut('\u2714 ' + (res === 'h' ? 'TESTA' : 'CROCE') + ' \u2014 +' + fmt(bet * 2) + ' ZC');
              } else {
                miniSetOut('\u2716 Era ' + (res === 'h' ? 'TESTA' : 'CROCE') + ' \u2014 -' + fmt(bet) + ' ZC');
                playUiSound('err');
              }
              miniCoinsRefresh();
            }));
          },
          dice: (box) => {
            box.innerHTML = miniBetRow(100) +
              '<div class="mini-reels" id="dv">\u{1F3B2}</div><div class="mini-out" id="mini-out">Alto o basso?</div>' +
              '<div class="pt-row2"><button class="pt-btn primary" data-g="hi">ALTO 4-6</button>' +
              '<button class="pt-btn gold" data-g="lo">BASSO 1-3</button></div>';
            const faces = ['\u2680', '\u2681', '\u2682', '\u2683', '\u2684', '\u2685'];
            box.querySelectorAll('[data-g]').forEach((b) => b.addEventListener('click', (e) => {
              e.preventDefault();
              const bet = miniBetVal(100);
              if (!spendCoins(bet, 'ZeroDice puntata')) return;
              const roll = 1 + Math.floor(Math.random() * 6);
              box.querySelector('#dv').textContent = faces[roll - 1];
              const win = (b.dataset.g === 'hi' && roll >= 4) || (b.dataset.g === 'lo' && roll <= 3);
              miniPlayed('dice');
              if (win) {
                const pay = Math.round(bet * 1.9);
                miniPay(pay, 'ZeroDice');
                miniSetOut('\u2714 ' + roll + ' \u2014 +' + fmt(pay) + ' ZC');
              } else {
                miniSetOut('\u2716 ' + roll + ' \u2014 -' + fmt(bet) + ' ZC');
                playUiSound('err');
              }
              miniCoinsRefresh();
            }));
          },
          slots: (box) => {
            const sym = ['\u{1F7E6}', '\u{1F7E1}', '\u{1F7E3}', '\u{1F7E2}', '\u2B50', '\u{1F48E}'];
            box.innerHTML = miniBetRow(100) +
              '<div class="mini-reels" id="sv">\u2753 \u2753 \u2753</div><div class="mini-out" id="mini-out">Tira la leva!</div>' +
              '<button class="pt-btn primary wide" id="s-spin">GIRA</button>';
            let spinning = false;
            box.querySelector('#s-spin').addEventListener('click', (e) => {
              e.preventDefault();
              if (spinning) return;
              const bet = miniBetVal(100);
              if (!spendCoins(bet, 'Zero Slots puntata')) return;
              spinning = true;
              const view = box.querySelector('#sv');
              const res = [0, 0, 0].map(() => sym[Math.floor(Math.random() * sym.length)]);
              let n = 0;
              const roll = () => {
                n++;
                view.textContent = [0, 0, 0].map(() => sym[Math.floor(Math.random() * sym.length)]).join(' ');
                if (n < 12) miniTimeout(roll, 60 + n * 14);
                else {
                  view.textContent = res.join(' ');
                  spinning = false;
                  miniPlayed('slots');
                  const trio = res[0] === res[1] && res[1] === res[2];
                  const pair = res[0] === res[1] || res[1] === res[2] || res[0] === res[2];
                  if (trio) {
                    miniPay(bet * 12, 'Zero Slots JACKPOT');
                    miniSetOut('\u{1F389} JACKPOT! +' + fmt(bet * 12) + ' ZC');
                    unlock('slotjack', 'Jackpot su Zero Slots!');
                  } else if (pair) {
                    miniPay(bet * 2, 'Zero Slots');
                    miniSetOut('\u2714 Coppia \u2014 +' + fmt(bet * 2) + ' ZC');
                  } else {
                    miniSetOut('\u2716 Nessuna combinazione \u2014 -' + fmt(bet) + ' ZC');
                    playUiSound('err');
                  }
                  miniCoinsRefresh();
                }
              };
              roll();
            });
          },
          mines: (box) => {
            box.innerHTML = miniBetRow(100) +
              '<div class="mini-grid3" id="mgrid"></div>' +
              '<div class="mini-out" id="mini-out">2 mine su 9 celle. Ogni cella sicura vale x1.38.</div>' +
              '<div class="pt-row2"><button class="pt-btn primary" id="m-start">PUNTA</button>' +
              '<button class="pt-btn gold" id="m-cash">INCASSA</button></div>';
            const grid = box.querySelector('#mgrid');
            let state = new Array(9).fill(0);
            let bombs = [], bet = 0, mult = 1, active = false;
            function draw(reveal) {
              grid.innerHTML = state.map((s, i) => {
                let cls = 'mini-tile', txt = '?';
                if (s === 1) { cls += ' safe'; txt = '\u{1F48E}'; }
                else if (s === 2) { cls += ' bomb'; txt = '\u{1F4A5}'; }
                else if (reveal) { txt = bombs.indexOf(i) >= 0 ? '\u{1F4A5}' : '\u{1F48E}'; }
                return '<button class="' + cls + '" data-i="' + i + '">' + txt + '</button>';
              }).join('');
              grid.querySelectorAll('.mini-tile').forEach((b) =>
                b.addEventListener('click', (e) => { e.preventDefault(); pick(Number(b.dataset.i)); }));
            }
            function cash() {
              if (!active) return;
              active = false;
              const win = Math.round(bet * mult);
              miniPlayed('mines');
              miniPay(win, 'Zero Mines');
              miniSetOut('\u2714 Incassati ' + fmt(win) + ' ZC a ' + mult.toFixed(2) + 'x');
              if (mult >= 3) unlock('mines3', 'Zero Mines oltre 3x!');
              draw(true);
            }
            function pick(i) {
              if (!active || state[i] !== 0) return;
              if (bombs.indexOf(i) >= 0) {
                state[i] = 2;
                active = false;
                draw(true);
                miniPlayed('mines');
                playUiSound('err');
                miniSetOut('\u{1F4A5} Mina! Hai perso ' + fmt(bet) + ' ZC.');
                return;
              }
              state[i] = 1;
              mult *= 1.38;
              draw(false);
              miniSetOut('Sicuro! ' + mult.toFixed(2) + 'x \u2014 potenziale ' + fmt(Math.round(bet * mult)) + ' ZC');
              if (state.filter((s) => s === 1).length >= 7) cash();
            }
            box.querySelector('#m-start').addEventListener('click', (e) => {
              e.preventDefault();
              if (active) return;
              bet = miniBetVal(100);
              if (!spendCoins(bet, 'Zero Mines puntata')) return;
              state = new Array(9).fill(0);
              bombs = [];
              mult = 1;
              active = true;
              while (bombs.length < 2) {
                const r = Math.floor(Math.random() * 9);
                if (bombs.indexOf(r) < 0) bombs.push(r);
              }
              draw(false);
              miniSetOut('Scava una cella!');
              miniCoinsRefresh();
            });
            box.querySelector('#m-cash').addEventListener('click', (e) => { e.preventDefault(); cash(); });
            draw(false);
          },
          memory: (box) => {
            box.innerHTML = '<div class="mini-grid3" id="mg" style="grid-template-columns:repeat(2,1fr)"></div>' +
              '<div class="mini-out" id="mini-out">Premi START e ripeti la sequenza.</div>' +
              '<button class="pt-btn primary wide" id="m-go">START</button>';
            const grid = box.querySelector('#mg');
            const colors = ['#00f0ff', '#ffe600', '#ff2d95', '#00ffa2'];
            grid.innerHTML = colors.map((c, i) =>
              '<button class="mini-tile" data-i="' + i + '" style="background:' + c + '22;border-color:' + c + '"></button>').join('');
            const tiles = Array.prototype.slice.call(grid.querySelectorAll('.mini-tile'));
            let seq = [], idx = 0, level = 0, playing = false;
            function flash(i, ms) {
              tiles[i].classList.add('lit');
              miniTimeout(() => tiles[i].classList.remove('lit'), ms);
            }
            function showSeq() {
              playing = false;
              seq.forEach((v, k) => miniTimeout(() => flash(v, 320), k * 500));
              miniTimeout(() => {
                playing = true;
                idx = 0;
                miniSetOut('Ripeti la sequenza (' + seq.length + ')');
              }, seq.length * 500 + 150);
            }
            function nextLevel() {
              level++;
              seq.push(Math.floor(Math.random() * 4));
              miniSetOut('Livello ' + level + ' \u2014 guarda...');
              showSeq();
            }
            box.querySelector('#m-go').addEventListener('click', (e) => {
              e.preventDefault();
              clearMiniTimers();
              seq = [];
              level = 0;
              nextLevel();
            });
            tiles.forEach((t) => t.addEventListener('click', (e) => {
              e.preventDefault();
              if (!playing) return;
              const i = Number(t.dataset.i);
              flash(i, 180);
              if (seq[idx] === i) {
                idx++;
                if (idx >= seq.length) {
                  const pay = level * 70;
                  miniPlayed('memory');
                  miniPay(pay, 'Memory Matrix');
                  miniSetOut('\u2714 Livello ' + level + ' \u2014 +' + fmt(pay) + ' ZC');
                  if (level >= 5) unlock('memory5', 'Memory Matrix livello 5!');
                  miniTimeout(nextLevel, 950);
                }
              } else {
                playing = false;
                playUiSound('err');
                miniSetOut('\u2716 Sbagliato! La sequenza era di ' + seq.length + '. Premi START.');
              }
            }));
          },
          clicker: (box) => {
            box.innerHTML = '<button class="mini-big" id="tap">TAP!</button>' +
              '<div class="mini-out" id="mini-out">Tocca per iniziare: 5 secondi.</div>';
            const b = box.querySelector('#tap');
            let n = 0, running = false;
            b.addEventListener('click', (e) => {
              e.preventDefault();
              if (!running) {
                running = true;
                n = 0;
                b.className = 'mini-big go';
                miniSetOut('VIA! 0 tap');
                miniTimeout(() => {
                  running = false;
                  b.className = 'mini-big';
                  const pay = n * 4;
                  miniPlayed('clicker');
                  miniPay(pay, 'Tap Frenzy');
                  miniSetOut(n + ' tap \u2014 +' + fmt(pay) + ' ZC');
                  if (n >= 40) unlock('tap40', '40 tap in 5 secondi!');
                }, 5000);
              } else {
                n++;
                miniSetOut(n + ' tap');
              }
            });
          },
          higher: (box) => {
            const names = ['', 'A', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K'];
            let card = 2 + Math.floor(Math.random() * 11);
            box.innerHTML = miniBetRow(100) +
              '<div class="mini-reels" id="hv">' + names[card] + '</div>' +
              '<div class="mini-out" id="mini-out">La prossima sar\u00E0 pi\u00F9 alta o pi\u00F9 bassa?</div>' +
              '<div class="pt-row2"><button class="pt-btn primary" data-g="h">\u25B2 ALTA</button>' +
              '<button class="pt-btn gold" data-g="l">\u25BC BASSA</button></div>';
            box.querySelectorAll('[data-g]').forEach((b) => b.addEventListener('click', (e) => {
              e.preventDefault();
              const bet = miniBetVal(100);
              if (!spendCoins(bet, 'Higher or Lower puntata')) return;
              const next = 1 + Math.floor(Math.random() * 13);
              const win = (b.dataset.g === 'h' && next > card) || (b.dataset.g === 'l' && next < card);
              box.querySelector('#hv').textContent = names[next];
              miniPlayed('higher');
              if (win) {
                const pay = Math.round(bet * 1.9);
                miniPay(pay, 'Higher or Lower');
                miniSetOut('\u2714 ' + names[card] + ' \u2192 ' + names[next] + ' \u2014 +' + fmt(pay) + ' ZC');
              } else {
                miniSetOut('\u2716 ' + names[card] + ' \u2192 ' + names[next] + ' \u2014 -' + fmt(bet) + ' ZC');
                playUiSound('err');
              }
              card = next;
              miniCoinsRefresh();
            }));
          },
          rps: (box) => {
            const icons = { r: '\u270A', p: '\u270B', s: '\u270C' };
            box.innerHTML = miniBetRow(100) +
              '<div class="mini-reels" id="rv">\u270A vs \u270A</div><div class="mini-out" id="mini-out">Scegli la tua mossa.</div>' +
              '<div class="pt-row2"><button class="pt-btn primary" data-m="r">\u270A SASSO</button>' +
              '<button class="pt-btn" data-m="p">\u270B CARTA</button>' +
              '<button class="pt-btn gold" data-m="s">\u270C FORBICE</button></div>';
            box.querySelectorAll('[data-m]').forEach((b) => b.addEventListener('click', (e) => {
              e.preventDefault();
              const bet = miniBetVal(100);
              if (!spendCoins(bet, 'Rock Zero puntata')) return;
              const mine = b.dataset.m;
              const bot = ['r', 'p', 's'][Math.floor(Math.random() * 3)];
              box.querySelector('#rv').textContent = icons[mine] + ' vs ' + icons[bot];
              miniPlayed('rps');
              if (mine === bot) {
                addCoins(bet, 'Rock Zero pareggio');
                miniSetOut('\u2796 Pareggio, puntata restituita.');
              } else if ((mine === 'r' && bot === 's') || (mine === 'p' && bot === 'r') || (mine === 's' && bot === 'p')) {
                const pay = Math.round(bet * 1.9);
                miniPay(pay, 'Rock Zero');
                miniSetOut('\u2714 Hai vinto \u2014 +' + fmt(pay) + ' ZC');
              } else {
                miniSetOut('\u2716 Hai perso \u2014 -' + fmt(bet) + ' ZC');
                playUiSound('err');
              }
              miniCoinsRefresh();
            }));
          },
          wheel: (box) => {
            const segs = [0, 50, 100, 150, 250, 400, 600, 1000];
            box.innerHTML = '<div class="mini-reels" id="wv">\u{1F3A1}</div><div class="mini-out" id="mini-out"></div>' +
              '<div class="pt-row2"><button class="pt-btn primary" id="w-free">GIRO GRATIS</button>' +
              '<button class="pt-btn gold" id="w-paid">GIRO 150 ZC</button></div>';
            const view = box.querySelector('#wv');
            let spinning = false;
            function cdText() {
              const left = Math.max(0, 900000 - (Date.now() - (profile.lastWheel || 0)));
              return left > 0 ? 'Giro gratis tra ' + Math.ceil(left / 60000) + ' min' : 'Giro gratis disponibile!';
            }
            miniSetOut(cdText());
            function spin(free) {
              if (spinning) return;
              if (free) {
                if (Date.now() - (profile.lastWheel || 0) < 900000) { miniSetOut(cdText()); playUiSound('err'); return; }
                profile.lastWheel = Date.now();
              } else if (!spendCoins(150, 'Ruota ZeroCoin')) return;
              spinning = true;
              const target = segs[Math.floor(Math.random() * segs.length)];
              let i = 0;
              const tick = () => {
                view.textContent = segs[i % segs.length] + ' ZC';
                i++;
                if (i < 16) miniTimeout(tick, 70 + i * 13);
                else {
                  view.textContent = target + ' ZC';
                  spinning = false;
                  miniPlayed('wheel');
                  if (target > 0) {
                    miniPay(target, 'Ruota ZeroCoin');
                    miniSetOut('\u{1F389} Hai vinto ' + target + ' ZC!');
                    if (target >= 600) unlock('wheelbig', 'Jackpot alla Ruota ZeroCoin!');
                  } else {
                    miniSetOut('\u{1F615} Nessuna vincita, ritenta!');
                    playUiSound('err');
                  }
                  miniCoinsRefresh();
                }
              };
              tick();
            }
            box.querySelector('#w-free').addEventListener('click', (e) => { e.preventDefault(); spin(true); });
            box.querySelector('#w-paid').addEventListener('click', (e) => { e.preventDefault(); spin(false); });
          },
        };

        function renderPtArcade() {
          if (miniId) { renderMiniPage(); return; }
          let h = '<div class="pt-sec">' + PT_T('arcade') + ' \u00B7 ' + PT_T('play') + '</div>';
          h += '<div class="pt-note">' + PT_T('miniGamesInfo') + '</div>';
          h += MINIS.map((m) => '<div class="pt-item"><div class="pt-item-icon">' + m.icon + '</div>' +
            '<div class="pt-item-info"><h5>' + m.name + '</h5><p>' + m.desc + '</p></div>' +
            '<div style="text-align:right"><div class="pt-price">' +
            ((profile.minis && profile.minis[m.id]) || 0) + ' ' + PT_T('plays') + '</div>' +
            '<button class="pt-btn small primary" data-mini="' + m.id + '">' + PT_T('play') + '</button></div></div>').join('');
          h += '<div class="pt-row2"><button class="pt-btn" data-go="crash">\u{1F680} ' + PT_T('crash') + '</button>' +
            '<button class="pt-btn" data-go="games">\u{1F3AE} ' + PT_T('games') + '</button></div>';
          ptBodyEl.innerHTML = h;
          ptBodyEl.querySelectorAll('[data-mini]').forEach((b) =>
            b.addEventListener('click', (e) => { e.preventDefault(); openMini(b.dataset.mini); }));
        }
        function renderMiniPage() {
          const g = MINIS.find((m) => m.id === miniId);
          if (!g) { miniId = null; renderPtArcade(); return; }
          ptBodyEl.innerHTML = '<div class="pt-row2"><button class="pt-btn small" id="mini-back">\u2190 ARCADE</button>' +
            '<span class="pt-price" id="mini-coins">' + fmt(profile.coins || 0) + ' ZC</span></div>' +
            '<div class="pt-sec">' + g.icon + ' ' + g.name.toUpperCase() + '</div>' +
            '<div class="pt-note">' + g.desc + '</div><div class="mini-wrap" id="mini-box"></div>';
          const back = document.getElementById('mini-back');
          if (back) back.addEventListener('click', (e) => { e.preventDefault(); closeMini(); });
          const box = document.getElementById('mini-box');
          if (box && MINI_BUILD[miniId]) MINI_BUILD[miniId](box);
        }

        function renderPtQuests() {
          ensureQuests();
          const qs = (profile.quests && profile.quests.items) || [];
          const doneN = qs.filter((q) => q.claimed).length;
          let h = '<div class="pt-sec">MISSIONI GIORNALIERE (' + doneN + '/' + qs.length + ')</div>';
          h += '<div class="pt-note">Si rinnovano ogni giorno. Completale per ZeroCoin, XP profilo e XP Zero Pass.</div>';
          for (const q of qs) {
            const pct = Math.min(100, ((q.prog || 0) / q.goal) * 100);
            h += '<div class="q-row' + (q.done ? ' done' : '') + '"><div class="q-info"><b>' + escapeHtml(q.label) + '</b>' +
              '<div class="q-bar"><span style="width:' + pct.toFixed(0) + '%"></span></div>' +
              '<div class="pt-note" style="margin:4px 0 0">' + Math.floor(q.prog || 0) + ' / ' + q.goal +
              ' \u00B7 premio ' + q.reward + ' ZC</div></div>' +
              (q.claimed ? '<span class="pt-badge">RISCATTATO</span>'
                : (q.done ? '<button class="pt-btn small primary" data-claimq="' + q.id + '">RISCATTA</button>'
                  : '<span class="pt-badge soon">IN CORSO</span>')) + '</div>';
          }
          h += '<div class="pt-sec">STREAK & BONUS</div><div class="pt-stats4">' +
            ptStat('Streak giornaliera', (profile.dailyStreak || 0) + ' gg') +
            ptStat('Trofei', Object.keys(profile.achievements || {}).length) +
            ptStat('Tier Zero Pass', passTier() + '/' + PASS_MAX) +
            ptStat('ZeroCoin', fmt(profile.coins || 0)) + '</div>';
          h += '<div class="pt-row2"><button class="pt-btn primary" data-play="growth_orbit">\u25B6 GIOCA IN ARENA</button>' +
            '<button class="pt-btn" data-go="arcade">\u{1F579} MINI GIOCHI</button></div>';
          h += '<div class="pt-sec">TROFEI SBLOCCATI</div><div class="pt-card ach-list">' +
            (Object.keys(profile.achievements || {}).length
              ? Object.keys(profile.achievements).map((k) => '<span class="ach">' + escapeHtml(k) + '</span>').join('')
              : '<span class="pt-note">Nessun trofeo ancora.</span>') + '</div>';
          ptBodyEl.innerHTML = h;
          ptBodyEl.querySelectorAll('[data-claimq]').forEach((b) =>
            b.addEventListener('click', (e) => { e.preventDefault(); claimQuest(b.dataset.claimq); renderPortal(); }));
        }

        function renderPtPass() {
          ensurePass();
          const tier = passTier();
          const xp = profile.pass.xp || 0;
          const inTier = xp % PASS_STEP;
          let h = '<div class="pt-hero"><div class="pt-kicker">ZERO PASS \u00B7 STAGIONE 1</div>' +
            '<h1>Tier ' + tier + ' / ' + PASS_MAX + '</h1>' +
            '<p>Guadagni XP Pass giocando in arena, nei mini-giochi, su ZeroCrash e completando missioni.</p>' +
            '<div class="xp-bar"><span style="width:' + ((inTier / PASS_STEP) * 100).toFixed(0) + '%"></span></div>' +
            '<div class="pt-note">' + inTier + ' / ' + PASS_STEP + ' XP al prossimo tier \u00B7 totale ' + Math.round(xp) + ' XP</div></div>';
          h += '<div class="pt-sec">PREMI</div><div class="tier-grid">';
          for (let t = 1; t <= PASS_MAX; t++) {
            const got = !!profile.pass.claimed[t];
            const ok = t <= tier;
            h += '<div class="tier' + (ok ? ' on' : '') + (got ? ' got' : '') + '">T' + t + '<br>' +
              passRewardFor(t).zc + ' ZC' + (t % 10 === 0 ? '<br>\u2764' : '') + '</div>';
          }
          h += '</div>';
          h += '<div class="pt-sec">RISCATTA</div><div class="pt-card">';
          let any = false;
          for (let t = 1; t <= tier; t++) {
            if (profile.pass.claimed[t]) continue;
            any = true;
            h += '<div class="pt-tx"><span>Tier ' + t + ' \u00B7 ' + passRewardFor(t).label +
              '</span><button class="pt-btn small primary" data-pass="' + t + '">RISCATTA</button></div>';
          }
          if (!any) h += '<div class="pt-note">Nessun premio da riscattare: continua a giocare per salire di tier.</div>';
          h += '</div>';
          h += '<div class="pt-row2"><button class="pt-btn primary" data-go="quests">\u{1F3AF} MISSIONI</button>' +
            '<button class="pt-btn" data-go="arcade">\u{1F579} GUADAGNA XP</button></div>';
          ptBodyEl.innerHTML = h;
          ptBodyEl.querySelectorAll('[data-pass]').forEach((b) =>
            b.addEventListener('click', (e) => { e.preventDefault(); claimPassTier(Number(b.dataset.pass)); renderPortal(); }));
        }

        function renderPtMusic() {
          const cur = (S.musicTrack || 'auto');
          let h = '<div class="pt-sec">MUSICA & AUDIO</div>';
          h += '<div class="pt-card"><div class="pt-note">Colonna sonora del portale e dell\u2019arena. <b>B</b> = musica on/off, <b>N</b> = traccia successiva.</div>' +
            MUSIC_TRACKS.map((t) => '<div class="track-row' + (cur === t.id ? ' on' : '') + '">' +
              '<b style="font-size:20px">\u266B</b><div class="pt-item-info"><h5>' + t.name + '</h5><p>' +
              (t.id === 'auto' ? 'Cambia automaticamente tra lobby, mini-giochi e arena'
                : (t.asset ? 'Traccia in loop continuo' : 'Generata dal sintetizzatore in tempo reale')) +
              '</p></div><button class="pt-btn small' + (cur === t.id ? '' : ' primary') + '" data-track="' + t.id + '">' +
              (cur === t.id ? 'IN USO' : 'ASCOLTA') + '</button></div>').join('') + '</div>';
          h += '<div class="pt-sec">MIXER</div><div class="pt-card">' +
            '<div class="pt-row"><label>Musica</label><input type="range" min="0" max="1" step="0.05" value="' +
            (S.musicVolume !== undefined ? S.musicVolume : 0.45) + '" data-vol="musicVolume" style="flex:1" /></div>' +
            '<div class="pt-row"><label>Effetti</label><input type="range" min="0" max="1" step="0.05" value="' +
            (S.sfxVolume !== undefined ? S.sfxVolume : 0.8) + '" data-vol="sfxVolume" style="flex:1" /></div>' +
            '<div class="pt-row2"><button class="pt-btn small" data-tog="musicOn">Musica: ' + (S.musicOn ? 'ON' : 'OFF') + '</button>' +
            '<button class="pt-btn small" data-tog="uiSounds">Suoni UI: ' + (S.uiSounds ? 'ON' : 'OFF') + '</button>' +
            '<button class="pt-btn small" data-tog="mute">Muto: ' + (S.mute ? 'ON' : 'OFF') + '</button></div></div>';
          h += '<div class="pt-sec">TEMA DEL PORTALE</div><div class="pt-card">' +
            THEMES.map((t) => '<button class="pt-btn small" data-theme="' + t.id + '" style="border-color:' + t.a + '">' +
              ((S.theme || 'neon') === t.id ? '\u2714 ' : '') + t.name + '</button> ').join('') + '</div>';
          ptBodyEl.innerHTML = h;
          ptBodyEl.querySelectorAll('[data-track]').forEach((b) => b.addEventListener('click', (e) => {
            e.preventDefault();
            setSetting('musicTrack', b.dataset.track);
            notify('\u266B ' + currentTrackName(), '#b388ff');
            renderPortal();
          }));
          ptBodyEl.querySelectorAll('[data-vol]').forEach((r) => r.addEventListener('input', () => {
            setSetting(r.dataset.vol, parseFloat(r.value));
          }));
          ptBodyEl.querySelectorAll('[data-tog]').forEach((b) => b.addEventListener('click', (e) => {
            e.preventDefault();
            setSetting(b.dataset.tog, !S[b.dataset.tog]);
            playUiSound('tap');
            renderPortal();
          }));
          ptBodyEl.querySelectorAll('[data-theme]').forEach((b) => b.addEventListener('click', (e) => {
            e.preventDefault();
            setSetting('theme', b.dataset.theme);
            playUiSound('tap');
            renderPortal();
          }));
        }

        // ---------------- ZeroSocial Hub UI ----------------
        let zeroSocialChatPeer = '';
        function zsSafeColor(value) {
          return /^#[0-9a-f]{3,8}$/i.test(String(value || '')) ? String(value) : '#00f0ff';
        }
        function zsInitial(name) { return socialInitial(name); }
        function zsAvatar(name, color) {
          return '<div class="zs-avatar" style="background:radial-gradient(circle at 35% 25%,#fff,' + zsSafeColor(color) + ' 42%,#152060)">' + escapeHtml(zsInitial(name)) + '</div>';
        }
        function zsButtonView(view, icon, label, active) {
          return '<button class="zs-tab' + (active ? ' on' : '') + '" data-zs-view="' + view + '">' + icon + ' ' + label + '</button>';
        }
        function zsPublishForm(kind, placeholder, button) {
          return '<form class="zs-card zs-composer" data-zs-publish="' + kind + '" autocomplete="off"><textarea maxlength="500" placeholder="' + placeholder + '"></textarea><div class="zs-form-row"><span class="zs-muted">Pubblicazione locale sul tuo profilo</span><button class="pt-btn small primary" type="submit">' + button + '</button></div></form>';
        }
        function zsStoryShelf(h, nm) {
          const own = { id:'story-own', author:nm, text:'Crea una storia', color:currentPlayerColor(), seen:false, own:true };
          const list = [own].concat(h.stories || []).slice(0, 8);
          return '<div class="zs-card"><h3>STORIE · 24 ORE</h3><div class="zs-story-row">' + list.map((s) =>
            '<button class="zs-story' + (s.seen ? ' seen' : '') + '" data-zs-story="' + escapeHtml(s.id) + '" style="background:linear-gradient(150deg,' + zsSafeColor(s.color) + '55,rgba(123,44,255,.35))">' + zsAvatar(s.author, s.color) + '<b>' + escapeHtml(s.author) + '</b><span>' + escapeHtml(s.text) + '</span></button>').join('') + '</div></div>';
        }
        function zsFeedMarkup() {
          const posts = socialPosts();
          return '<div class="zs-section">FEED UNIFICATO · POST, COMMENTI E REAZIONI</div>' + zsPublishForm('post', 'Condividi un record, una strategia o un aggiornamento...', 'PUBBLICA') + (posts.length ? posts.map(socialPostCard).join('') : '<div class="zs-card zs-empty">Nessun post. Pubblica il primo aggiornamento della community.</div>');
        }
        function zsReelsMarkup(h) {
          return '<div class="zs-section">REELS & SHORTS · VIDEO BREVI</div>' + zsPublishForm('reel', 'Descrivi il tuo prossimo Reel / Short...', 'PUBBLICA REEL') + '<div class="zs-reel-grid">' + (h.reels || []).map((r) => '<article class="zs-reel"><div class="zs-thumb"><span style="position:absolute;font-size:48px;opacity:.8">' + escapeHtml(r.icon || '▶') + '</span></div><div class="zs-reel-body"><b>' + escapeHtml(r.author) + '</b><p>' + escapeHtml(r.text) + '</p><div class="zs-action-row"><button class="zs-action" data-zs-reel-like="' + escapeHtml(r.id) + '">♡ ' + (r.likes || 0) + '</button><span class="zs-pill">' + escapeHtml(r.views || '0') + ' views</span></div></div></article>').join('') + '</div>';
        }
        function zsVideosMarkup(h) {
          return '<div class="zs-section">VIDEO & CANALI</div><div class="zs-card"><h3>CANALI CONSIGLIATI</h3>' + (h.channels || []).map((c) => '<div class="zs-list-row">' + zsAvatar(c.name, c.id === 'zeroarcade' ? '#00f0ff' : '#b388ff') + '<div class="zs-list-info"><b>' + escapeHtml(c.name) + (c.verified ? ' ✓' : '') + '</b><span>' + fmt(c.subs || 0) + ' iscritti · ' + escapeHtml(c.desc) + '</span></div><button class="pt-btn small ' + (h.following[c.id] ? '' : 'primary') + '" data-zs-follow="' + escapeHtml(c.id) + '">' + (h.following[c.id] ? PT_T('subbed') : PT_T('subscribe')) + '</button></div>').join('') + '</div><div class="zs-video-grid">' + (h.reels || []).slice(0, 6).map((r) => '<article class="zs-video"><div class="zs-thumb"></div><div class="zs-video-body"><b>' + escapeHtml(r.text) + '</b><p>' + escapeHtml(r.author) + ' · ' + escapeHtml(r.views || '0') + ' visualizzazioni</p><button class="pt-btn small primary" data-zs-reel-like="' + escapeHtml(r.id) + '">👍 Mi piace ' + (r.likes || 0) + '</button></div></article>').join('') + '</div>';
        }
        function zsChatMarkup(h, nm) {
          const messages = h.messages || [];
          const peer = h.activePeer || (messages[0] && messages[0].peer) || 'NovaKid';
          const chat = messages.find((m) => m.peer === peer) || { peer:peer, text:'', messages:[] };
          const thread = Array.isArray(chat.messages) ? chat.messages : [];
          return '<div class="zs-section">MESSAGGI · CHAT PRIVATA E GRUPPI</div><div class="zs-chat-layout"><div class="zs-chat-list">' + (messages.length ? messages.map((m) => '<button class="zs-chat-peer' + (m.peer === peer ? ' on' : '') + '" data-zs-chat-peer="' + escapeHtml(m.peer) + '">' + zsAvatar(m.peer, m.group ? '#ffe600' : '#00f0ff') + '<b>' + escapeHtml(m.peer) + '</b><br><span class="zs-muted">' + escapeHtml(m.text || 'Nuova chat') + '</span></button>').join('') : '<div class="zs-empty">Nessuna chat.</div>') + '</div><div class="zs-chat-window"><div class="zs-chat-head">' + escapeHtml(peer) + ' <span class="zs-pill">online</span></div><div class="zs-chat-log">' + (thread.length ? thread.map((m) => '<div class="zs-bubble' + (m.from === nm ? ' me' : '') + '"><b>' + escapeHtml(m.from || peer) + '</b><br>' + escapeHtml(m.text) + '</div>').join('') : '<div class="zs-empty">Scrivi il primo messaggio.</div>') + '</div><form class="zs-chat-form" data-zs-message-form="' + escapeHtml(peer) + '" autocomplete="off"><input maxlength="240" placeholder="Scrivi a ' + escapeHtml(peer) + '..." /><button class="pt-btn small primary" type="submit">INVIA</button></form></div></div>';
        }
        function zsGroupsMarkup(h) {
          return '<div class="zs-section">GRUPPI · COMMUNITY</div><form class="zs-card" data-zs-create-group autocomplete="off"><h3>CREA UN GRUPPO</h3><div class="zs-form-row"><input class="zs-input" maxlength="32" placeholder="Nome del nuovo gruppo" /><button class="pt-btn small primary" type="submit">CREA</button></div></form><div class="zs-card">' + (h.groups || []).map((g) => '<div class="zs-list-row">' + zsAvatar(g.name, g.joined ? '#00ffa2' : '#8b5cff') + '<div class="zs-list-info"><b>' + escapeHtml(g.name) + '</b><span>' + escapeHtml(g.desc) + ' · ' + fmt(g.members || 0) + ' membri</span></div><button class="pt-btn small ' + (g.joined ? '' : 'primary') + '" data-zs-join="' + escapeHtml(g.id) + '">' + (g.joined ? PT_T('joined') : PT_T('join')) + '</button></div>').join('') + '</div>';
        }
        function zsPagesMarkup(h) {
          return '<div class="zs-section">PAGINE & CREATOR</div><div class="zs-card"><h3>SEGUI PAGINE E CANALI</h3>' + (h.pages || []).map((p) => '<div class="zs-list-row">' + zsAvatar(p.name, '#ff2d95') + '<div class="zs-list-info"><b>' + escapeHtml(p.name) + (p.verified ? ' ✓' : '') + '</b><span>' + escapeHtml(p.category) + ' · ' + fmt(p.followers || 0) + ' follower</span></div><button class="pt-btn small ' + (h.following[p.id] ? '' : 'primary') + '" data-zs-follow="' + escapeHtml(p.id) + '">' + (h.following[p.id] ? 'SEGUITA' : 'SEGUI') + '</button></div>').join('') + '</div><div class="zs-card"><h3>STRUMENTI CREATOR</h3><p class="zs-muted">Pubblica storie, Reel, video brevi e aggiornamenti dal tuo profilo. Le attività restano nel tuo spazio ZeroSocial.</p><div class="zs-form-row"><button class="pt-btn small" data-zs-view="stories">+ STORIA</button><button class="pt-btn small" data-zs-view="reels">+ REEL</button><button class="pt-btn small" data-zs-view="x">+ POST BREVE</button></div></div>';
        }
        function zsProfessionalMarkup(h, nm) {
          const pro = h.professional || {};
          const jobs = pro.jobs || [];
          const posts = pro.posts || [];
          return '<div class="zs-section">' + PT_T('proNetwork') + ' · PROFILO PROFESSIONALE</div><div class="zs-card"><div class="zs-list-row">' + zsAvatar(nm, currentPlayerColor()) + '<div class="zs-list-info"><b>' + escapeHtml(nm) + '</b><span>' + escapeHtml(pro.headline || 'Neon arena player') + ' · ' + fmt(pro.connections || 0) + ' connessioni</span></div><span class="zs-pill">OPEN TO CONNECT</span></div><form class="zs-form-row" data-zs-headline-form><input class="zs-input" maxlength="80" value="' + escapeHtml(pro.headline || '') + '" placeholder="Titolo professionale" /><button class="pt-btn small" type="submit">' + PT_T('update') + '</button></form></div>' + zsPublishForm('professional', 'Condividi un progetto, un risultato o una collaborazione...', PT_T('publish')) + '<div class="zs-card"><h3>POST PROFESSIONALI</h3>' + (posts.length ? posts.map((p) => '<div class="zs-xpost"><b>' + escapeHtml(p.author) + '</b><span class="zs-muted"> · ' + escapeHtml(p.time || 'Ora') + '</span><p>' + escapeHtml(p.text) + '</p></div>').join('') : '<div class="zs-empty">Nessun aggiornamento professionale.</div>') + '</div><div class="zs-card"><h3>OPPORTUNITÀ E NETWORKING</h3>' + jobs.map((j) => '<div class="zs-job"><h4>' + escapeHtml(j.title) + '</h4><p>' + escapeHtml(j.company) + ' · ' + escapeHtml(j.meta) + '</p><button class="pt-btn small" data-zs-job="' + escapeHtml(j.id) + '">' + (pro.applied && pro.applied[j.id] ? PT_T('applied') : PT_T('saveOpp')) + '</button></div>').join('') + '</div>';
        }
        function zsXMarkup(h) {
          return '<div class="zs-section">X · AGGIORNAMENTI IN TEMPO REALE</div>' + zsPublishForm('x', 'Cosa sta succedendo nell\'orbita?', 'POSTA') + '<div class="zs-card"><h3>TRENDING NOW</h3><div class="zs-form-row"><span class="zs-pill">#GrowthOrbit</span><span class="zs-pill">#ZeroPass</span><span class="zs-pill">#LegendLab</span><span class="zs-pill">#ArenaRush</span></div></div><div class="zs-card">' + (h.xPosts || []).map((p) => '<article class="zs-xpost"><div class="zs-xpost-head">' + zsAvatar(p.author, '#7fd4ff') + '<div class="zs-list-info"><b>' + escapeHtml(p.author) + '</b><span>' + escapeHtml(p.handle || '') + ' · ' + escapeHtml(p.time || 'Ora') + '</span></div></div><p>' + escapeHtml(p.text) + '</p><div class="zs-action-row"><button class="zs-action" data-zs-x-like="' + escapeHtml(p.id) + '">♡ ' + (p.likes || 0) + '</button><button class="zs-action" data-zs-x-repost="' + escapeHtml(p.id) + '">↻ ' + (p.reposts || 0) + '</button><button class="zs-action" data-zs-x-save="' + escapeHtml(p.id) + '">🔖 SALVA</button></div></article>').join('') + '</div>';
        }
        function zsFriendsMarkup(h) {
          const req = h.friendRequests || [];
          const suggestions = ['PlasmaPop', 'GhostOrbit', 'CellDoc', 'Zyphra'].filter((n) => !(profile.friends || []).some((f) => f.n === n));
          return '<div class="zs-section">AMICI · CONNESSIONI</div><form class="zs-card" data-zs-friend-form autocomplete="off"><h3>INVITA UN AMICO</h3><div class="zs-form-row"><input class="zs-input" maxlength="20" placeholder="Nickname del giocatore" /><button class="pt-btn small primary" type="submit">INVIA RICHIESTA</button></div></form>' + (req.length ? '<div class="zs-card"><h3>RICHIESTE RICEVUTE / INVIATE</h3>' + req.map((r) => '<div class="zs-list-row">' + zsAvatar(r.n, '#ffe600') + '<div class="zs-list-info"><b>' + escapeHtml(r.n) + '</b><span>Richiesta di amicizia</span></div><button class="pt-btn small primary" data-zs-accept="' + escapeHtml(r.id) + '">ACCETTA</button></div>').join('') + '</div>' : '') + '<div class="zs-card"><h3>I TUOI AMICI (' + (profile.friends || []).length + ')</h3>' + ((profile.friends || []).length ? profile.friends.map((f) => '<div class="zs-list-row">' + zsAvatar(f.n, f.on ? '#00ffa2' : '#59647c') + '<div class="zs-list-info"><b>' + escapeHtml(f.n) + '</b><span>LV ' + f.lv + ' · ' + (f.on ? 'online' : 'offline') + '</span></div><button class="pt-btn small" data-zs-chat-peer="' + escapeHtml(f.n) + '">MESSAGGIA</button></div>').join('') : '<div class="zs-empty">Nessun amico ancora.</div>') + '</div><div class="zs-card"><h3>POTRESTI CONOSCERE</h3>' + suggestions.map((n) => '<div class="zs-list-row">' + zsAvatar(n, '#b388ff') + '<div class="zs-list-info"><b>' + n + '</b><span>Giocatore dell\'arena</span></div><button class="pt-btn small primary" data-zs-friend="' + n + '">AGGIUNGI</button></div>').join('') + '</div>';
        }
        function zsHomeMarkup(h, nm) {
          const unread = (h.messages || []).filter((m) => m.unread).length;
          const followers = Object.keys(h.following || {}).filter((k) => h.following[k]).length;
          return '<div class="zs-hero"><div class="zs-kicker">ZEROSOCIAL · COMMUNITY HUB</div><h2>Ciao ' + escapeHtml(nm) + ' 👋</h2><p>Un unico spazio per feed, storie, video, chat, gruppi, pagine, networking professionale e aggiornamenti in tempo reale.</p><div class="zs-form-row"><button class="pt-btn primary" data-zs-view="feed">📰 APRI FEED</button><button class="pt-btn gold" data-zs-view="friends">👥 TROVA AMICI</button><button class="pt-btn" data-zs-view="chat">💬 MESSAGGI (' + unread + ')</button></div></div><div class="zs-metrics"><div class="zs-metric">Amici<b>' + (profile.friends || []).length + '</b></div><div class="zs-metric">Seguiti<b>' + followers + '</b></div><div class="zs-metric">Stories<b>' + (h.stories || []).length + '</b></div><div class="zs-metric">Gruppi<b>' + (h.groups || []).filter((g) => g.joined).length + '</b></div></div><div class="zs-section">TUTTE LE STRUTTURE IN UN SOLO HUB</div><div class="zs-quick-grid"><button class="zs-quick" data-zs-view="feed"><b>📰</b>FEED</button><button class="zs-quick" data-zs-view="stories"><b>◎</b>STORIE</button><button class="zs-quick" data-zs-view="reels"><b>▶</b>REELS</button><button class="zs-quick" data-zs-view="videos"><b>📺</b>VIDEO</button><button class="zs-quick" data-zs-view="chat"><b>💬</b>CHAT</button><button class="zs-quick" data-zs-view="groups"><b>👥</b>GRUPPI</button><button class="zs-quick" data-zs-view="pages"><b>▣</b>PAGINE</button><button class="zs-quick" data-zs-view="pro"><b>💼</b>PRO NETWORK</button><button class="zs-quick" data-zs-view="x"><b>𝕏</b>LIVE POSTS</button><button class="zs-quick" data-zs-view="friends"><b>🤝</b>AMICI</button></div>' + zsStoryShelf(h, nm) + '<div class="zs-columns"><main>' + zsFeedMarkup() + '</main><aside><div class="zs-card"><h3>IN TENDENZA</h3><div class="zs-list-row"><span class="zs-pill">#GrowthOrbit</span><b>1.2k</b></div><div class="zs-list-row"><span class="zs-pill">#ZeroPass</span><b>842</b></div><div class="zs-list-row"><span class="zs-pill">#LegendLab</span><b>506</b></div></div><div class="zs-card"><h3>COMMUNITY</h3><p class="zs-muted">' + (h.channels || []).length + ' canali · ' + (h.groups || []).length + ' gruppi · ' + (profile.friends || []).length + ' amici</p><button class="pt-btn small primary" data-zs-view="pages">ESPLORA CREATOR</button></div></aside></div>';
        }
        function bindZeroSocialHub(scope) {
          if (!scope) return;
          const h = socialHubState();
          scope.querySelectorAll('[data-zs-view]').forEach((b) => b.addEventListener('click', (e) => { e.preventDefault(); h.view = b.dataset.zsView; renderPortal(); }));
          const service = scope.querySelector('[data-zs-service]');
          if (service) service.addEventListener('change', () => { h.service = service.value; h.view = ({ facebook:'feed', instagram:'stories', youtube:'videos', whatsapp:'chat', tiktok:'reels', linkedin:'pro', x:'x' })[service.value] || 'home'; renderPortal(); });
          scope.querySelectorAll('[data-zs-publish]').forEach((form) => form.addEventListener('submit', (e) => { e.preventDefault(); const input = form.querySelector('textarea'); socialHubPublish(form.dataset.zsPublish, input ? input.value : ''); }));
          scope.querySelectorAll('[data-zs-story]').forEach((b) => b.addEventListener('click', (e) => { e.preventDefault(); if (b.dataset.zsStory !== 'story-own') { const s = h.stories.find((x) => x.id === b.dataset.zsStory); if (s) { s.seen = true; notify('Storia di ' + s.author + ' aperta', '#7fd4ff'); socialHubSave(); } } else h.view = 'stories'; renderPortal(); }));
          scope.querySelectorAll('[data-zs-reel-like]').forEach((b) => b.addEventListener('click', (e) => { e.preventDefault(); const r = h.reels.find((x) => x.id === b.dataset.zsReelLike); if (r) r.likes = (r.likes || 0) + 1; socialHubSave(); renderPortal(); }));
          scope.querySelectorAll('[data-zs-follow]').forEach((b) => b.addEventListener('click', (e) => { e.preventDefault(); socialHubToggleFollow(b.dataset.zsFollow); renderPortal(); }));
          scope.querySelectorAll('[data-zs-chat-peer]').forEach((b) => b.addEventListener('click', (e) => { e.preventDefault(); h.activePeer = b.dataset.zsChatPeer; h.view = 'chat'; renderPortal(); }));
          scope.querySelectorAll('[data-zs-message-form]').forEach((form) => form.addEventListener('submit', (e) => { e.preventDefault(); const input = form.querySelector('input'); socialHubSendMessage(form.dataset.zsMessageForm, input ? input.value : ''); h.view = 'chat'; renderPortal(); }));
          scope.querySelectorAll('[data-zs-join]').forEach((b) => b.addEventListener('click', (e) => { e.preventDefault(); socialHubJoinGroup(b.dataset.zsJoin); renderPortal(); }));
          scope.querySelectorAll('[data-zs-accept]').forEach((b) => b.addEventListener('click', (e) => { e.preventDefault(); socialHubAcceptFriend(b.dataset.zsAccept); renderPortal(); }));
          const groupForm = scope.querySelector('[data-zs-create-group]');
          if (groupForm) groupForm.addEventListener('submit', (e) => { e.preventDefault(); socialHubCreateGroup(groupForm.querySelector('input').value); });
          const friendForm = scope.querySelector('[data-zs-friend-form]');
          if (friendForm) friendForm.addEventListener('submit', (e) => { e.preventDefault(); socialHubFriendRequest(friendForm.querySelector('input').value); renderPortal(); });
          const headlineForm = scope.querySelector('[data-zs-headline-form]');
          if (headlineForm) headlineForm.addEventListener('submit', (e) => { e.preventDefault(); h.professional.headline = headlineForm.querySelector('input').value.trim().slice(0, 80) || 'Neon arena player'; socialHubSave(); notify('Profilo professionale aggiornato', '#00ffa2'); renderPortal(); });
          scope.querySelectorAll('[data-zs-job]').forEach((b) => b.addEventListener('click', (e) => { e.preventDefault(); if (!h.professional.applied) h.professional.applied = {}; h.professional.applied[b.dataset.zsJob] = true; socialHubSave(); notify('Opportunità salvata nel tuo profilo', '#00ffa2'); renderPortal(); }));
          const nexusIntent = scope.querySelector('[data-nexus-intent]');
          if (nexusIntent) nexusIntent.addEventListener('change', () => nexusSetIntent(nexusIntent.value));
          scope.querySelectorAll('[data-nexus-resonate]').forEach((b) => b.addEventListener('click', (e) => { e.preventDefault(); nexusResonate(b.dataset.nexusResonate); }));
          const nexusScanBtn = scope.querySelector('[data-nexus-scan]');
          if (nexusScanBtn) nexusScanBtn.addEventListener('click', (e) => { e.preventDefault(); nexusScan(); });
          const nexusPulseForm = scope.querySelector('[data-nexus-pulse]');
          if (nexusPulseForm) nexusPulseForm.addEventListener('submit', (e) => { e.preventDefault(); nexusCreatePulse(nexusPulseForm.querySelector('textarea').value); });
          scope.querySelectorAll('[data-zs-x-like]').forEach((b) => b.addEventListener('click', (e) => { e.preventDefault(); const p = h.xPosts.find((x) => x.id === b.dataset.zsXLike); if (p) p.likes = (p.likes || 0) + 1; socialHubSave(); renderPortal(); }));
          scope.querySelectorAll('[data-zs-x-repost]').forEach((b) => b.addEventListener('click', (e) => { e.preventDefault(); const p = h.xPosts.find((x) => x.id === b.dataset.zsXRepost); if (p) p.reposts = (p.reposts || 0) + 1; socialHubSave(); notify('Post ripubblicato', '#7fd4ff'); renderPortal(); }));
          scope.querySelectorAll('[data-zs-x-save]').forEach((b) => b.addEventListener('click', (e) => { e.preventDefault(); h.saved[b.dataset.zsXSave] = !h.saved[b.dataset.zsXSave]; socialHubSave(); notify(h.saved[b.dataset.zsXSave] ? 'Post salvato' : 'Post rimosso dai salvati', '#7fd4ff'); renderPortal(); }));
          socialBind(scope);
          legendBindTilt(scope);
        }
        function renderZeroSocialHub() {
          ensureSocial();
          const h = socialHubState();
          const nm = profile.loggedIn ? profile.name : 'Ospite';
          const serviceMap = { all:'TUTTI I SERVIZI', facebook:'FACEBOOK · FEED', instagram:'INSTAGRAM · STORIE', youtube:'YOUTUBE · VIDEO', whatsapp:'WHATSAPP · CHAT', tiktok:'TIKTOK · REELS', linkedin:'LINKEDIN · PRO', x:'X · LIVE' };
          const tabs = [['nexus','✦ NEXUS'],['home','⌂ HUB'],['feed','📰 FEED'],['stories','◎ STORIE'],['reels','▶ REELS'],['videos','📺 VIDEO'],['chat','💬 CHAT'],['groups','👥 GRUPPI'],['pages','▣ PAGINE'],['pro','💼 PRO'],['x','𝕏 LIVE'],['friends','🤝 AMICI']];
          let body = '';
          if (h.view === 'home') body = zsHomeMarkup(h, nm);
          else if (h.view === 'feed') body = zsFeedMarkup();
          else if (h.view === 'stories') body = zsStoryShelf(h, nm) + zsPublishForm('story', 'Racconta cosa succede nella tua orbita...', 'PUBBLICA STORIA');
          else if (h.view === 'reels') body = zsReelsMarkup(h);
          else if (h.view === 'videos') body = zsVideosMarkup(h);
          else if (h.view === 'chat') body = zsChatMarkup(h, nm);
          else if (h.view === 'groups') body = zsGroupsMarkup(h);
          else if (h.view === 'pages') body = zsPagesMarkup(h);
          else if (h.view === 'pro') body = zsProfessionalMarkup(h, nm);
          else if (h.view === 'x') body = zsXMarkup(h);
          else if (h.view === 'friends') body = zsFriendsMarkup(h);
          else if (h.view === 'nexus') body = zsNexusMarkup(h, nm);
          ptBodyEl.innerHTML = '<div class="zs-shell"><div class="zs-head"><div class="zs-head-copy"><h1>ZeroSocial Nexus</h1><p>Un social reattivo: i contenuti orbitano intorno al tuo intento.</p></div><select class="zs-select zs-service-select" data-zs-service aria-label="Servizio social"><option value="all">🌐 ' + serviceMap.all + '</option><option value="facebook">📰 ' + serviceMap.facebook + '</option><option value="instagram">◎ ' + serviceMap.instagram + '</option><option value="youtube">📺 ' + serviceMap.youtube + '</option><option value="whatsapp">💬 ' + serviceMap.whatsapp + '</option><option value="tiktok">▶ ' + serviceMap.tiktok + '</option><option value="linkedin">💼 ' + serviceMap.linkedin + '</option><option value="x">𝕏 ' + serviceMap.x + '</option></select></div><div class="zs-toolbar"><div class="zs-tabs">' + tabs.map((t) => zsButtonView(t[0], '', t[1], h.view === t[0])).join('') + '</div></div>' + body + '<div class="zs-card zs-muted" style="margin-top:14px">ZeroSocial è una struttura unificata offline: profilo, contenuti e interazioni restano nel tuo profilo di gioco. Nessun collegamento automatico agli account esterni.</div></div>';
          const selector = ptBodyEl.querySelector('[data-zs-service]');
          if (selector) selector.value = h.service || 'all';
          bindZeroSocialHub(ptBodyEl);
        }
        // The portal's main page is a single social operating system. The left rail
        // selects a social intent, the center adapts its workspace, and the right
        // rail keeps live signals visible instead of splitting the experience into
        // Facebook/Instagram/WhatsApp/YouTube/TikTok/LinkedIn/X clones.
        function renderZeroSocialCore() {
          ensureSocial();
          const h = socialHubState();
          const nm = profile.loggedIn ? profile.name : PT_T('guest');
          const validViews = ['home','feed','stories','reels','videos','chat','groups','pages','pro','x','friends','nexus'];
          const view = validViews.indexOf(h.view) >= 0 ? h.view : 'home';
          const active = view === 'nexus' ? 'nexus' : view;
          const serviceMap = {
            all: PT_T('allSignals'), facebook: 'FEED', instagram: 'STORIE', youtube: 'VIDEO',
            whatsapp: 'CHAT', tiktok: 'REELS', linkedin: PT_T('proNetwork'), x: PT_T('livePosts')
          };
          const chat = (h.messages || [])[0];
          const channel = (h.channels || [])[0];
          const job = h.professional && h.professional.jobs ? h.professional.jobs[0] : null;
          let workspace = '';
          if (view === 'feed' || view === 'home') workspace = zsStoryShelf(h, nm) + zsFeedMarkup();
          else if (view === 'stories') workspace = zsStoryShelf(h, nm) + zsPublishForm('story', 'Racconta cosa succede nella tua orbita...', PT_T('publish'));
          else if (view === 'reels') workspace = zsReelsMarkup(h);
          else if (view === 'videos') workspace = zsVideosMarkup(h);
          else if (view === 'chat') workspace = zsChatMarkup(h, nm);
          else if (view === 'groups') workspace = zsGroupsMarkup(h);
          else if (view === 'pages') workspace = zsPagesMarkup(h);
          else if (view === 'pro') workspace = zsProfessionalMarkup(h, nm);
          else if (view === 'x') workspace = zsXMarkup(h);
          else if (view === 'friends') workspace = zsFriendsMarkup(h);
          else workspace = zsNexusMarkup(h, nm);

          const rail = [
            ['home', '◈', 'CORE', 'tutto in un colpo'], ['feed', '▤', 'FEED', 'post e commenti'],
            ['stories', '◎', 'STORIE', 'segnali di 24h'], ['reels', '▶', 'REELS', 'short e video'],
            ['chat', '✉', 'ROOMS', 'chat e gruppi'], ['pro', '◆', 'PRO', 'opportunità'],
            ['x', '✦', 'LIVE', 'impulsi in tempo reale'], ['nexus', '⌘', 'ORBITA', 'la parte che non esisteva']
          ];
          const railHtml = rail.map((r) => '<button class="zsc-rail-btn' + (active === r[0] ? ' on' : '') + '" data-zs-view="' + r[0] + '"><span>' + r[1] + '</span><b>' + r[2] + '</b><small>' + r[3] + '</small></button>').join('');
          const signals = [
            ['feed', '▤', 'FEED', 'pensieri e record'], ['stories', '◎', '24H', 'storie vive'],
            ['reels', '▶', 'SHORT', 'video rapidi'], ['chat', '✉', 'ROOMS', 'conversazioni'],
            ['pro', '◆', 'PRO', 'talenti e lavori'], ['x', '✦', 'LIVE', 'segnali ora']
          ];
          const signalHtml = signals.map((s) => '<button class="zsc-signal" data-zs-view="' + s[0] + '"><span>' + s[1] + '</span><b>' + s[2] + '</b><small>' + s[3] + '</small></button>').join('');
          const serviceOptions = Object.keys(serviceMap).map((key) => '<option value="' + key + '">' + (key === 'all' ? '◈ ' : '') + serviceMap[key] + '</option>').join('');
          const friendCount = (profile.friends || []).length;
          const groupCount = (h.groups || []).filter((g) => g.joined).length;
          const unread = (h.messages || []).filter((m) => m.unread).length;
          ptBodyEl.innerHTML = `<div class="zsc-shell">
            <section class="zsc-topbar">
              <div class="zsc-brand"><div class="zsc-brand-mark">Z</div><div><b>ZEROSOCIAL / ONE</b><span>un solo portale · molte forme di connessione</span></div></div>
              <div class="zsc-topline"><strong>PORTAL CORE ONLINE</strong><p>Non apri sette social: entri in uno spazio che trasforma ogni formato in un segnale.</p></div>
              <select class="zs-select" data-zs-service aria-label="Scegli il tipo di segnale"><option value="all">◈ ${serviceMap.all}</option>${serviceOptions.replace('<option value="all">◈ ' + serviceMap.all + '</option>', '')}</select>
            </section>
            <div class="zsc-layout">
              <aside class="zsc-command-rail"><div class="zsc-rail-title">COMMAND RAIL</div>${railHtml}</aside>
              <main class="zsc-main">
                <section class="zsc-welcome"><h1>${PT_T('welcome')} nel tuo spazio, ${escapeHtml(nm)}.</h1><p>Feed, storie, Reel, video, chat, gruppi, live post e networking non sono pagine separate: sono modalità dello stesso universo sociale.</p><div class="zsc-command-line"><button class="pt-btn primary" data-zs-view="feed">${PT_T('openFeed')}</button><button class="pt-btn gold" data-zs-view="nexus">✦ ${PT_T('goOrbit')}</button><button class="pt-btn" data-zs-view="chat">${PT_T('messages')} ${unread ? '(' + unread + ')' : ''}</button></div></section>
                <div class="zsc-signal-grid">${signalHtml}</div>
                ${workspace}
              </main>
              <aside class="zsc-live-rail">
                <div class="zsc-side-card"><h3>LIVE RADAR</h3><div class="zsc-orbit-meter"><span></span></div><div class="zsc-side-row"><div><b>${escapeHtml(chat ? chat.peer : 'Nessuna room')}</b><span>${escapeHtml(chat ? (chat.text || 'Conversazione pronta') : 'Apri una conversazione')}</span></div><span>CHAT</span></div><div class="zsc-side-row"><div><b>${escapeHtml(channel ? channel.name : 'Zero Arcade')}</b><span>${channel ? fmt(channel.subs || 0) + ' iscritti' : 'Canale principale'}</span></div><span>VIDEO</span></div><div class="zsc-side-row"><div><b>${escapeHtml(job ? job.title : 'Nuova opportunità')}</b><span>${escapeHtml(job ? job.company : 'Pro network')}</span></div><span>PRO</span></div></div>
                <div class="zsc-side-card"><h3>IL TUO GRAFO</h3><div class="zsc-side-row"><div><b>${friendCount} connessioni</b><span>persone nel tuo spazio</span></div><span>◉</span></div><div class="zsc-side-row"><div><b>${groupCount} room attive</b><span>community a cui partecipi</span></div><span>⌂</span></div><div class="zsc-side-row"><div><b>${(h.reels || []).length} segnali video</b><span>short e contenuti da esplorare</span></div><span>▶</span></div></div>
                <div class="zsc-side-card"><h3>TREND NELLA TUA ORBITA</h3><div class="zs-form-row"><span class="zs-pill">#GrowthOrbit</span><span class="zs-pill">#ZeroPass</span><span class="zs-pill">#LegendLab</span><span class="zs-pill">#BuildInPublic</span></div><button class="pt-btn small primary" data-zs-view="x" style="width:100%;margin-top:10px">VEDI SEGNALI LIVE</button></div>
              </aside>
            </div>
            <div class="zsc-footer-note">ZeroSocial / One è la struttura unica del portale: i formati prendono il meglio dai social che conosci, ma vivono nello stesso grafo locale e reagiscono al tuo intento. Nessun account esterno e nessun feed duplicato.</div>
          </div>`;
          const selector = ptBodyEl.querySelector('[data-zs-service]');
          if (selector) selector.value = h.service || 'all';
          bindZeroSocialHub(ptBodyEl);
        }
        function renderPtSocial() {
          renderZeroSocialCore();
        }

        // ============================================================
        // ZERO OMNI HUB — local companions to SoundCloud, Facebook,
        // YouTube, X/Twitter and TikTok, plus 40 touch-first portal tools.
        // External APIs/accounts are intentionally not used in this sandbox;
        // every action stays inside the player's saved ZeroSocial profile.
        // ============================================================
        // `run()` initializes gameplay before the portal catalog is built. Use a
        // hoisted binding so early profile/HUD refreshes cannot hit the TDZ.
        WORLD_LANGUAGES = [
          ['it','Italiano'], ['en','English'], ['es','Español'], ['fr','Français'], ['de','Deutsch'], ['pt','Português'],
          ['ru','Русский'], ['uk','Українська'], ['pl','Polski'], ['tr','Türkçe'], ['nl','Nederlands'], ['sv','Svenska'],
          ['da','Dansk'], ['no','Norsk'], ['fi','Suomi'], ['el','Ελληνικά'], ['cs','Čeština'], ['ro','Română'], ['hu','Magyar'],
          ['bg','Български'], ['sr','Srpski'], ['hr','Hrvatski'], ['sk','Slovenčina'], ['sl','Slovenščina'], ['ar','العربية'],
          ['he','עברית'], ['fa','فارسی'], ['hi','हिन्दी'], ['bn','বাংলা'], ['ur','اردو'], ['zh','中文'], ['ja','日本語'],
          ['ko','한국어'], ['vi','Tiếng Việt'], ['th','ไทย'], ['id','Bahasa Indonesia'], ['ms','Melayu'], ['tl','Filipino'],
          ['sw','Kiswahili']
        ];
        const PORTAL_LABELS = {
          it: { 
            home:'ZERO CORE', games:'GIOCHI', arcade:'MINI GIOCHI', crash:'CRASH', shop:'SHOP', wallet:'ZEROCOIN', 
            quests:'MISSIONI', pass:'ZERO PASS', music:'MUSICA', social:'SOCIAL OS', omni:'OMNI HUB', dash:'DASHBOARD', 
            ranks:'CLASSIFICHE', news:'NEWS', help:'SUPPORTO', admin:'ADMIN', legend:'LEGEND LAB', tutorials:'TUTORIAL',
            featured: 'IN EVIDENZA', sections: 'SEZIONI', nowPlaying: 'IN RIPRODUZIONE', latestNews: 'ULTIME NOVITÀ',
            dailyQuests: 'MISSIONI DI OGGI', welcome: 'Ciao', jukebox: 'JUKEBOX', play: 'GIOCA', soon: 'PRESTO',
            back: 'INDIETRO', category: 'CATEGORIA', recent: 'RECENTI', search: 'Cerca...',
            gameList: 'LISTA GIOCHI', runningGame: 'PARTITA IN CORSO', continueGame: 'CONTINUA',
            miniGamesInfo: 'Mini-giochi istantanei', dailyBonus: 'Bonus Giornaliero', friends: 'AMICI',
            kicker: 'PORTALE UFFICIALE', community: 'La tua community Zero Arcade', postCount: 'post',
            thinking: 'A cosa stai pensando', publish: 'PUBBLICA', yourStory: 'La tua storia',
            trending: 'IN TENDENZA', suggested: 'Persone che potresti conoscere', greet: 'SALUTA',
            followers: 'follower', online: 'online', offline: 'offline',
            level: 'Livello', role: 'Ruolo', bestMass: 'Massa record', guest: 'Ospite',
            getZc: 'OTTIENI ZC', continueArena: 'ENTRA IN ARENA', subbed: 'ISCRITTO', subscribe: 'ISCRIVITI',
            joined: 'ISCRITTO', join: 'UNISCITI', applied: 'CANDIDATURA INVIATA', saveOpp: 'SALVA OPPORTUNITÀ',
            reposted: 'Post ripubblicato', saved: 'Post salvato', unread: 'non letti',
            proNetwork: 'PRO NETWORK', livePosts: 'LIVE POSTS', connect: 'CONNETTI', update: 'AGGIORNA',
            openFeed: 'APRI IL FLUSSO', goOrbit: 'VAI IN ORBITA', messages: 'MESSAGGI', allSignals: 'TUTTI I SEGNALI',
            catalogInfo: 'I titoli IN ARRIVO entrano nel portale nei prossimi aggiornamenti. I mini-giochi GIOCABILI pagano ZeroCoin subito. Cerca “Legend” per le nuove sfide.',
            stats: 'STATISTICHE', inventory: 'INVENTARIO', lastGames: 'ULTIME PARTITE', trophies: 'TROFEI',
            globalRanks: 'CLASSIFICHE GLOBALI', yourRecords: 'I TUOI RECORD', arenaRivals: 'RIVALI IN ARENA',
            walletNote: 'Store dimostrativo: i pacchetti si sbloccano giocando, non esistono pagamenti reali né acquisti in denaro.',
            plays: 'giocate'
          },
          en: { 
            home:'ZERO CORE', games:'GAMES', arcade:'MINI GAMES', crash:'CRASH', shop:'SHOP', wallet:'ZEROCOIN', 
            quests:'QUESTS', pass:'ZERO PASS', music:'MUSIC', social:'SOCIAL OS', omni:'OMNI HUB', dash:'DASHBOARD', 
            ranks:'LEADERBOARD', news:'NEWS', help:'SUPPORT', admin:'ADMIN', legend:'LEGEND LAB', tutorials:'TUTORIALS',
            featured: 'FEATURED', sections: 'SECTIONS', nowPlaying: 'NOW PLAYING', latestNews: 'LATEST NEWS',
            dailyQuests: 'DAILY QUESTS', welcome: 'Hello', jukebox: 'JUKEBOX', play: 'PLAY', soon: 'SOON',
            back: 'BACK', category: 'CATEGORY', recent: 'RECENT', search: 'Search...',
            gameList: 'GAME LIST', runningGame: 'RUNNING GAME', continueGame: 'CONTINUE',
            miniGamesInfo: 'Instant mini-games', dailyBonus: 'Daily Bonus', friends: 'FRIENDS',
            kicker: 'OFFICIAL PORTAL', community: 'Your Zero Arcade community', postCount: 'posts',
            thinking: 'What\'s on your mind', publish: 'PUBLISH', yourStory: 'Your story',
            trending: 'TRENDING', suggested: 'People you may know', greet: 'GREET',
            followers: 'followers', online: 'online', offline: 'offline',
            level: 'Level', role: 'Role', bestMass: 'Best mass', guest: 'Guest',
            getZc: 'GET ZC', continueArena: 'ENTER ARENA', subbed: 'SUBSCRIBED', subscribe: 'SUBSCRIBE',
            joined: 'JOINED', join: 'JOIN', applied: 'APPLIED', saveOpp: 'SAVE OPPORTUNITY',
            reposted: 'Reposted', saved: 'Saved', unread: 'unread',
            proNetwork: 'PRO NETWORK', livePosts: 'LIVE POSTS', connect: 'CONNECT', update: 'UPDATE',
            openFeed: 'OPEN FEED', goOrbit: 'GO IN ORBIT', messages: 'MESSAGES', allSignals: 'ALL SIGNALS',
            catalogInfo: 'COMING SOON titles enter the portal in next updates. PLAYABLE mini-games pay ZeroCoin instantly. Search "Legend" for new challenges.',
            stats: 'STATISTICS', inventory: 'INVENTORY', lastGames: 'LATEST GAMES', trophies: 'TROPHIES',
            globalRanks: 'GLOBAL LEADERBOARDS', yourRecords: 'YOUR RECORDS', arenaRivals: 'ARENA RIVALS',
            walletNote: 'Demonstrative store: packs are unlocked by playing, there are no real payments or cash purchases.',
            plays: 'plays'
          },
          es: { home:'NÚCLEO ZERO', games:'JUEGOS', arcade:'MINIJUEGOS', crash:'CRASH', shop:'TIENDA', wallet:'ZEROCOIN', quests:'MISIONES', pass:'ZERO PASS', music:'MÚSICA', social:'SOCIAL OS', omni:'HUB OMNI', dash:'PANEL', ranks:'CLASIFICACIÓN', news:'NOTICIAS', help:'AYUDA', admin:'ADMIN', legend:'LAB LEGEND', tutorials:'TUTORIALES' },
          fr: { home:'CŒUR ZERO', games:'JEUX', arcade:'MINI-JEUX', crash:'CRASH', shop:'BOUTIQUE', wallet:'ZEROCOIN', quests:'QUÊTES', pass:'ZERO PASS', music:'MUSIQUE', social:'SOCIAL OS', omni:'HUB OMNI', dash:'TABLEAU', ranks:'CLASSEMENT', news:'ACTUS', help:'AIDE', admin:'ADMIN', legend:'LAB LEGEND', tutorials:'TUTORIELS' },
          de: { home:'ZERO KERN', games:'SPIELE', arcade:'MINISPIELE', crash:'CRASH', shop:'SHOP', wallet:'ZEROCOIN', quests:'MISSIONEN', pass:'ZERO PASS', music:'MUSIK', social:'SOCIAL OS', omni:'OMNI HUB', dash:'DASHBOARD', ranks:'RANGLISTE', news:'NEWS', help:'HILFE', admin:'ADMIN', legend:'LEGEND LAB', tutorials:'TUTORIALS' },
          pt: { home:'NÚCLEO ZERO', games:'JOGOS', arcade:'MINIJOGOS', crash:'CRASH', shop:'LOJA', wallet:'ZEROCOIN', quests:'MISSÕES', pass:'ZERO PASS', music:'MÚSICA', social:'SOCIAL OS', omni:'HUB OMNI', dash:'PAINEL', ranks:'RANKING', news:'NOTÍCIAS', help:'SUPORTE', admin:'ADMIN', legend:'LAB LEGEND', tutorials:'TUTORIAIS' },
          ru: { home:'ЯДРО ZERO', games:'ИГРЫ', arcade:'МИНИ-ИГРЫ', crash:'КРАШ', shop:'МАГАЗИН', wallet:'ZEROCOIN', quests:'ЗАДАНИЯ', pass:'ZERO PASS', music:'МУЗЫКА', social:'SOCIAL OS', omni:'OMNI HUB', dash:'ПАНЕЛЬ', ranks:'РЕЙТИНГ', news:'НОВОСТИ', help:'ПОМОЩЬ', admin:'АДМИН', legend:'LEGEND LAB', tutorials:'УРОКИ' },
          zh: { home:'ZERO 核心', games:'游戏', arcade:'小游戏', crash:'崩溃', shop:'商店', wallet:'ZEROCOIN', quests:'任务', pass:'ZERO PASS', music:'音乐', social:'社交 OS', omni:'全能中心', dash:'仪表盘', ranks:'排行榜', news:'新闻', help:'帮助', admin:'管理', legend:'传奇实验室', tutorials:'教程' },
          ja: { home:'ZERO CORE', games:'ゲーム', arcade:'ミニゲーム', crash:'クラッシュ', shop:'ショップ', wallet:'ZEROCOIN', quests:'クエスト', pass:'ZERO PASS', music:'音楽', social:'SOCIAL OS', omni:'OMNI HUB', dash:'ダッシュボード', ranks:'ランキング', news:'ニュース', help:'サポート', admin:'管理', legend:'LEGEND LAB', tutorials:'チュートリアル' },
          ko: { home:'ZERO 코어', games:'게임', arcade:'미니 게임', crash:'크래시', shop:'상점', wallet:'ZEROCOIN', quests:'퀘스트', pass:'ZERO PASS', music:'음악', social:'소셜 OS', omni:'OMNI 허브', dash:'대시보드', ranks:'순위표', news:'뉴스', help:'지원', admin:'관리', legend:'레전드 랩', tutorials:'튜토리얼' },
          ar: { home:'نواة زيرو', games:'الألعاب', arcade:'ألعاب صغيرة', crash:'تحطم', shop:'المتجر', wallet:'ZEROCOIN', quests:'المهام', pass:'ZERO PASS', music:'الموسيقى', social:'النظام الاجتماعي', omni:'مركز شامل', dash:'لوحة التحكم', ranks:'المتصدرون', news:'الأخبار', help:'الدعم', admin:'الإدارة', legend:'مختبر الأسطورة', tutorials:'دروس تعليمية' },
          hi: { home:'ज़ीरो कोर', games:'गेम्स', arcade:'मिनी गेम्स', crash:'क्रैश', shop:'दुकan', wallet:'ZEROCOIN', quests:'मिशन', pass:'ZERO PASS', music:'संगीत', social:'SOCIAL OS', omni:'ओम्नी हब', dash:'डैशबोर्ड', ranks:'लीडरबोर्ड', news:'समाचार', help:'सहायता', admin:'एडमिन', legend:'लीजेंड लैब', tutorials:'ट्यूटोरियल' },
          tr: { home:'ZERO ÇEKİRDEK', games:'OYUNLAR', arcade:'MİNİ OYUNLAR', crash:'ÇÖKÜŞ', shop:'MAĞAZA', wallet:'ZEROCOIN', quests:'GÖREVLER', pass:'ZERO PASS', music:'MÜZİK', social:'SOSYAL OS', omni:'OMNI HUB', dash:'PANEL', ranks:'SIRALAMA', news:'HABERLER', help:'DESTEK', admin:'YÖNETİM', legend:'LEGEND LAB', tutorials:'EĞİTİMLER' },
          id: { home:'ZERO CORE', games:'PERMAINAN', arcade:'MINI GAME', crash:'CRASH', shop:'TOKO', wallet:'ZEROCOIN', quests:'MISI', pass:'ZERO PASS', music:'MUSIK', social:'SOSIAL OS', omni:'OMNI HUB', dash:'DASHBOARD', ranks:'PERINGKAT', news:'BERITA', help:'BANTUAN', admin:'ADMIN', legend:'LEGEND LAB', tutorials:'TUTORIAL' }
        };
        function portalLanguage() {
          const selected = languageOverride || (S && S.language);
          const languages = Array.isArray(WORLD_LANGUAGES) ? WORLD_LANGUAGES : [];
          return (selected && languages.some((x) => x[0] === selected)) ? selected : 'en';
        }
        const PORTAL_EXTRA_LABELS = {
          uk:{home:'ЯДРО ZERO',games:'ІГРИ',arcade:'МІНІ-ІГРИ',crash:'КРАШ',shop:'МАГАЗИН',wallet:'ZEROCOIN',quests:'ЗАВДАННЯ',pass:'ZERO PASS',music:'МУЗИКА',social:'СОЦІАЛЬНИЙ OS',omni:'OMNI HUB',dash:'ПАНЕЛЬ',ranks:'РЕЙТИНГ',news:'НОВИНИ',help:'ПІДТРИМКА',admin:'АДМІН',legend:'LEGEND LAB',tutorials:'ПОСІБНИКИ'},
          pl:{home:'ZERO CORE',games:'GRY',arcade:'MINIGRY',crash:'CRASH',shop:'SKLEP',wallet:'ZEROCOIN',quests:'MISJE',pass:'ZERO PASS',music:'MUZYKA',social:'SOCIAL OS',omni:'OMNI HUB',dash:'PANEL',ranks:'RANKING',news:'AKTUALNOŚCI',help:'POMOC',admin:'ADMIN',legend:'LEGEND LAB',tutorials:'SAMOUCZKI'},
          nl:{home:'ZERO CORE',games:'SPELLEN',arcade:'MINI-SPELLEN',crash:'CRASH',shop:'WINKEL',wallet:'ZEROCOIN',quests:'MISSIES',pass:'ZERO PASS',music:'MUZIEK',social:'SOCIAL OS',omni:'OMNI HUB',dash:'DASHBOARD',ranks:'RANGLIJST',news:'NIEUWS',help:'HULP',admin:'ADMIN',legend:'LEGEND LAB',tutorials:'TUTORIALS'},
          sv:{home:'ZERO CORE',games:'SPEL',arcade:'MINISPEL',crash:'KRASCH',shop:'BUTIK',wallet:'ZEROCOIN',quests:'UPPDRAG',pass:'ZERO PASS',music:'MUSIK',social:'SOCIAL OS',omni:'OMNI HUB',dash:'PANEL',ranks:'TOPPLISTA',news:'NYHETER',help:'HJÄLP',admin:'ADMIN',legend:'LEGEND LAB',tutorials:'GUIDER'},
          da:{home:'ZERO CORE',games:'SPIL',arcade:'MINISPIL',crash:'CRASH',shop:'BUTIK',wallet:'ZEROCOIN',quests:'MISSIONER',pass:'ZERO PASS',music:'MUSIK',social:'SOCIAL OS',omni:'OMNI HUB',dash:'DASHBOARD',ranks:'RANGLISTE',news:'NYHEDER',help:'SUPPORT',admin:'ADMIN',legend:'LEGEND LAB',tutorials:'GUIDER'},
          no:{home:'ZERO CORE',games:'SPILL',arcade:'MINISPILL',crash:'KRÆSJ',shop:'BUTIKK',wallet:'ZEROCOIN',quests:'OPPDRAG',pass:'ZERO PASS',music:'MUSIKK',social:'SOCIAL OS',omni:'OMNI HUB',dash:'DASHBOARD',ranks:'TOPPLISTE',news:'NYHETER',help:'STØTTE',admin:'ADMIN',legend:'LEGEND LAB',tutorials:'GUIDER'},
          fi:{home:'ZERO CORE',games:'PELIT',arcade:'MINIPELIT',crash:'ROMAHDUS',shop:'KAUPPA',wallet:'ZEROCOIN',quests:'TEHTÄVÄT',pass:'ZERO PASS',music:'MUSIIKKI',social:'SOCIAL OS',omni:'OMNI HUB',dash:'KOJELAUTA',ranks:'TULOKSET',news:'UUTISET',help:'TUKI',admin:'ADMIN',legend:'LEGEND LAB',tutorials:'OPPAAT'},
          el:{home:'ZERO CORE',games:'ΠΑΙΧΝΙΔΙΑ',arcade:'ΜΙΝΙ ΠΑΙΧΝΙΔΙΑ',crash:'CRASH',shop:'ΚΑΤΑΣΤΗΜΑ',wallet:'ZEROCOIN',quests:'ΑΠΟΣΤΟΛΕΣ',pass:'ZERO PASS',music:'ΜΟΥΣΙΚΗ',social:'SOCIAL OS',omni:'OMNI HUB',dash:'ΠΙΝΑΚΑΣ',ranks:'ΚΑΤΑΤΑΞΗ',news:'ΝΕΑ',help:'ΥΠΟΣΤΗΡΙΞΗ',admin:'ADMIN',legend:'LEGEND LAB',tutorials:'ΟΔΗΓΟΙ'},
          cs:{home:'ZERO CORE',games:'HRY',arcade:'MINI HRY',crash:'CRASH',shop:'OBCHOD',wallet:'ZEROCOIN',quests:'ÚKOLY',pass:'ZERO PASS',music:'HUDBA',social:'SOCIAL OS',omni:'OMNI HUB',dash:'PANEL',ranks:'ŽEBŘÍČEK',news:'NOVINKY',help:'PODPORA',admin:'ADMIN',legend:'LEGEND LAB',tutorials:'NÁVODY'},
          ro:{home:'ZERO CORE',games:'JOCURI',arcade:'MINI-JOCURI',crash:'CRASH',shop:'MAGAZIN',wallet:'ZEROCOIN',quests:'MISIUNI',pass:'ZERO PASS',music:'MUZICĂ',social:'SOCIAL OS',omni:'OMNI HUB',dash:'PANOU',ranks:'CLASAMENT',news:'ȘTIRI',help:'AJUTOR',admin:'ADMIN',legend:'LEGEND LAB',tutorials:'TUTORIALE'},
          hu:{home:'ZERO CORE',games:'JÁTÉKOK',arcade:'MINIJÁTÉKOK',crash:'CRASH',shop:'BOLT',wallet:'ZEROCOIN',quests:'KÜLDETÉSEK',pass:'ZERO PASS',music:'ZENE',social:'SOCIAL OS',omni:'OMNI HUB',dash:'IRÁNYÍTÓPULT',ranks:'RANGLISTA',news:'HÍREK',help:'TÁMOGATÁS',admin:'ADMIN',legend:'LEGEND LAB',tutorials:'ÚTMUTATÓK'},
          bg:{home:'ZERO CORE',games:'ИГРИ',arcade:'МИНИ ИГРИ',crash:'КРАШ',shop:'МАГАЗИН',wallet:'ZEROCOIN',quests:'МИСИИ',pass:'ZERO PASS',music:'МУЗИКА',social:'СОЦИАЛЕН OS',omni:'OMNI HUB',dash:'ТАБЛО',ranks:'КЛАСАЦИЯ',news:'НОВИНИ',help:'ПОМОЩ',admin:'АДМИН',legend:'LEGEND LAB',tutorials:'УРОЦИ'},
          hr:{home:'ZERO CORE',games:'IGRE',arcade:'MINI IGRE',crash:'CRASH',shop:'TRGOVINA',wallet:'ZEROCOIN',quests:'MISIJE',pass:'ZERO PASS',music:'GLAZBA',social:'SOCIAL OS',omni:'OMNI HUB',dash:'NADZORNA PLOČA',ranks:'LJESTVICA',news:'VIJESTI',help:'PODRŠKA',admin:'ADMIN',legend:'LEGEND LAB',tutorials:'VODIČI'},
          sk:{home:'ZERO CORE',games:'HRY',arcade:'MINI HRY',crash:'CRASH',shop:'OBCHOD',wallet:'ZEROCOIN',quests:'ÚLOHY',pass:'ZERO PASS',music:'HUDBA',social:'SOCIAL OS',omni:'OMNI HUB',dash:'NÁSTENKA',ranks:'REBRÍČEK',news:'SPRÁVY',help:'PODPORA',admin:'ADMIN',legend:'LEGEND LAB',tutorials:'NÁVODY'},
          sl:{home:'ZERO CORE',games:'IGRE',arcade:'MINI IGRE',crash:'CRASH',shop:'TRGOVINA',wallet:'ZEROCOIN',quests:'MISIJE',pass:'ZERO PASS',music:'GLASBA',social:'SOCIAL OS',omni:'OMNI HUB',dash:'NADZORNA PLOŠČA',ranks:'LJESTVICA',news:'NOVICE',help:'PODPORA',admin:'ADMIN',legend:'LEGEND LAB',tutorials:'VODNIKI'},
          vi:{home:'ZERO CORE',games:'TRÒ CHƠI',arcade:'MINI GAME',crash:'CRASH',shop:'CỬA HÀNG',wallet:'ZEROCOIN',quests:'NHIỆM VỤ',pass:'ZERO PASS',music:'ÂM NHẠC',social:'SOCIAL OS',omni:'OMNI HUB',dash:'BẢNG ĐIỀU KHIỂN',ranks:'BẢNG XẾP HẠNG',news:'TIN TỨC',help:'HỖ TRỢ',admin:'QUẢN TRỊ',legend:'LEGEND LAB',tutorials:'HƯỚNG DẪN'},
          th:{home:'ZERO CORE',games:'เกม',arcade:'มินิเกม',crash:'แครช',shop:'ร้านค้า',wallet:'ZEROCOIN',quests:'ภารกิจ',pass:'ZERO PASS',music:'เพลง',social:'โซเชียล OS',omni:'OMNI HUB',dash:'แดชบอร์ด',ranks:'กระดานผู้นำ',news:'ข่าว',help:'ช่วยเหลือ',admin:'ผู้ดูแล',legend:'LEGEND LAB',tutorials:'บทช่วยสอน'},
          id:{home:'ZERO CORE',games:'PERMAINAN',arcade:'MINI GAME',crash:'CRASH',shop:'TOKO',wallet:'ZEROCOIN',quests:'MISI',pass:'ZERO PASS',music:'MUSIK',social:'SOSIAL OS',omni:'OMNI HUB',dash:'DASHBOARD',ranks:'PERINGKAT',news:'BERITA',help:'BANTUAN',admin:'ADMIN',legend:'LEGEND LAB',tutorials:'TUTORIAL'},
          ms:{home:'ZERO CORE',games:'PERMAINAN',arcade:'MINI PERMAINAN',crash:'CRASH',shop:'KEDAI',wallet:'ZEROCOIN',quests:'MISI',pass:'ZERO PASS',music:'MUZIK',social:'SOSIAL OS',omni:'OMNI HUB',dash:'PAPAN PEMUKA',ranks:'KEDUDUKAN',news:'BERITA',help:'SOKONGAN',admin:'ADMIN',legend:'LEGEND LAB',tutorials:'TUTORIAL'},
          tl:{home:'ZERO CORE',games:'MGA LARO',arcade:'MINI LARO',crash:'CRASH',shop:'TINDahan',wallet:'ZEROCOIN',quests:'MGA MISYON',pass:'ZERO PASS',music:'MUSIKA',social:'SOCIAL OS',omni:'OMNI HUB',dash:'DASHBOARD',ranks:'LEADERBOARD',news:'BALITA',help:'TULONG',admin:'ADMIN',legend:'LEGEND LAB',tutorials:'TUTORIAL'},
          sw:{home:'ZERO CORE',games:'MICHEZO',arcade:'MICHEZO MIDOGO',crash:'CRASH',shop:'DUKA',wallet:'ZEROCOIN',quests:'MAJUKUMU',pass:'ZERO PASS',music:'MUZIKI',social:'SOCIAL OS',omni:'OMNI HUB',dash:'DASHIBODI',ranks:'ORODHA',news:'HABARI',help:'MSAADA',admin:'ADMIN',legend:'LEGEND LAB',tutorials:'MAFUNZO'}
        };
        function portalLabel(id) {
          const lang = portalLanguage();
          const labels = Object.assign({}, PORTAL_LABELS.en, PORTAL_LABELS[lang] || {}, PORTAL_EXTRA_LABELS[lang] || {});
          return labels[id] || id.toUpperCase();
        }
        function PT_T(key) { return portalLabel(key); }

        // Portal-wide localization layer. Most portal cards are assembled from
        // reusable HTML templates, so translating only the navigation labels left
        // large parts of the portal in Italian. This pass translates visible text,
        // placeholders, labels and accessibility text after every portal render.
        // It is fully local and uses authored copy only (no runtime translation API).
        const PORTAL_COPY_ROWS = [
          ['NAVIGAZIONE','NAVIGATION','NAVEGACIÓN','NAVIGATION','NAVIGATION','НАВИГАЦИЯ','导航','ナビゲーション','탐색','التنقل','NAVİGASYON'],
          ['PORTALE','PORTAL','PORTAL','PORTAIL','PORTAL','ПОРТАЛ','门户','ポータル','포털','البوابة','PORTAL'],
          ['Cerca nel portale...','Search the portal...','Buscar en el portal...','Rechercher dans le portail...','Portal durchsuchen...','Поиск по порталу...','搜索门户...','ポータルを検索...','포털 검색...','البحث في البوابة...','Portalı ara...'],
          ['Seleziona lingua','Choose language','Elegir idioma','Choisir la langue','Sprache wählen','Выберите язык','选择语言','言語を選択','언어 선택','اختر اللغة','Dil seç'],
          ['IN EVIDENZA','FEATURED','DESTACADO','À LA UNE','EMPFOHLEN','РЕКОМЕНДУЕМ','精选','おすすめ','추천','مميز','ÖNE ÇIKANLAR'],
          ['SEZIONI','SECTIONS','SECCIONES','SECTIONS','BEREICHE','РАЗДЕЛЫ','版块','セクション','섹션','الأقسام','BÖLÜMLER'],
          ['ULTIME NOVITÀ','LATEST NEWS','ÚLTIMAS NOTICIAS','DERNIÈRES NOUVEAUTÉS','NEUESTE NEWS','ПОСЛЕДНИЕ НОВОСТИ','最新消息','最新ニュース','최신 소식','آخر الأخبار','SON HABERLER'],
          ['MISSIONI DI OGGI','DAILY QUESTS','MISIONES DIARIAS','QUÊTES DU JOUR','TAGESQUESTS','ЕЖЕДНЕВНЫЕ ЗАДАНИЯ','每日任务','デイリークエスト','일일 퀘스트','المهام اليومية','GÜNLÜK GÖREVLER'],
          ['IN RIPRODUZIONE','NOW PLAYING','REPRODUCIENDO','EN LECTURE','WIRD ABGESPIELT','СЕЙЧАС ИГРАЕТ','正在播放','再生中','재생 중','قيد التشغيل','ŞİMDİ ÇALIYOR'],
          ['Cerca...','Search...','Buscar...','Rechercher...','Suchen...','Поиск...','搜索...','検索...','검색...','بحث...','Ara...'],
          ['CATEGORIA','CATEGORY','CATEGORÍA','CATÉGORIE','KATEGORIE','КАТЕГОРИЯ','分类','カテゴリー','카테고리','الفئة','KATEGORİ'],
          ['LISTA GIOCHI','GAME LIST','LISTA DE JUEGOS','LISTE DES JEUX','SPIELELISTE','СПИСОК ИГР','游戏列表','ゲーム一覧','게임 목록','قائمة الألعاب','OYUN LİSTESİ'],
          ['PARTITA IN CORSO','RUNNING GAME','PARTIDA EN CURSO','PARTIE EN COURS','LAUFENDES SPIEL','ТЕКУЩАЯ ИГРА','正在进行的游戏','プレイ中のゲーム','진행 중인 게임','اللعبة الجارية','DEVAM EDEN OYUN'],
          ['CONTINUA','CONTINUE','CONTINUAR','CONTINUER','FORTSETZEN','ПРОДОЛЖИТЬ','继续','続ける','계속','متابعة','DEVAM ET'],
          ['RECENTI','RECENT','RECIENTES','RÉCENTS','ZULETZT','НЕДАВНИЕ','最近','最近','최근','الأخيرة','SON OYNANANLAR'],
          ['GIOCABILI','PLAYABLE','JUGABLES','JOUABLES','SPIELBAR','ДОСТУПНЫЕ','可玩','プレイ可能','플레이 가능','قابلة للعب','OYANABİLİR'],
          ['TUTTI','ALL','TODOS','TOUS','ALLE','ВСЕ','全部','すべて','전체','الكل','TÜMÜ'],
          ['PREFERITI','FAVORITES','FAVORITOS','FAVORIS','FAVORITEN','ИЗБРАННОЕ','收藏','お気に入り','즐겨찾기','المفضلة','FAVORİLER'],
          ['GIOCA','PLAY','JUGAR','JOUER','SPIELEN','ИГРАТЬ','开始游戏','プレイ','플레이','تشغيل','OYNA'],
          ['PRESTO','COMING SOON','PRÓXIMAMENTE','BIENTÔT','BALD','СКОРО','即将推出','近日公開','출시 예정','قريبًا','YAKINDA'],
          ['INDIETRO','BACK','ATRÁS','RETOUR','ZURÜCK','НАЗАД','返回','戻る','뒤로','رجوع','GERİ'],
          ['PUBBLICA','PUBLISH','PUBLICAR','PUBLIER','VERÖFFENTLICHEN','ОПУБЛИКОВАТЬ','发布','投稿','게시','نشر','PAYLAŞ'],
          ['CONDIVIDI','SHARE','COMPARTIR','PARTAGER','TEILEN','ПОДЕЛИТЬСЯ','分享','共有','공유','مشاركة','PAYLAŞ'],
          ['MI PIACE','LIKE','ME GUSTA','J’AIME','GEFÄLLT MIR','НРАВИТСЯ','喜欢','いいね','좋아요','إعجاب','BEĞEN'],
          ['COMMENTA','COMMENT','COMENTAR','COMMENTER','KOMMENTIEREN','КОММЕНТИРОВАТЬ','评论','コメント','댓글','تعليق','YORUM YAP'],
          ['INVIA','SEND','ENVIAR','ENVOYER','SENDEN','ОТПРАВИТЬ','发送','送信','보내기','إرسال','GÖNDER'],
          ['SALVA','SAVE','GUARDAR','ENREGISTRER','SPEICHERN','СОХРАНИТЬ','保存','保存','저장','حفظ','KAYDET'],
          ['USA','USE','USAR','UTILISER','NUTZEN','ИСПОЛЬЗОВАТЬ','使用','使う','사용','استخدم','KULLAN'],
          ['RISCATTA','CLAIM','CANJEAR','RÉCLAMER','EINLÖSEN','ПОЛУЧИТЬ','领取','受け取る','받기','استلام','TALEP ET'],
          ['ACQUISTA','BUY','COMPRAR','ACHETER','KAUFEN','КУПИТЬ','购买','購入','구매','شراء','SATIN AL'],
          ['EQUIPAGGIA','EQUIP','EQUIPAR','ÉQUIPER','AUSRÜSTEN','ЭКИПИРОВАТЬ','装备','装備','장착','تجهيز','KUŞAN'],
          ['ATTIVO','ACTIVE','ACTIVO','ACTIF','AKTIV','АКТИВНО','启用','有効','활성','نشط','AKTİF'],
          ['INVENTARIO','INVENTORY','INVENTARIO','INVENTAIRE','INVENTAR','ИНВЕНТАРЬ','库存','インベントリ','인벤토리','المخزون','ENVANTER'],
          ['CLASSIFICHE GLOBALI','GLOBAL LEADERBOARDS','CLASIFICACIONES GLOBALES','CLASSEMENTS MONDIAUX','GLOBALE RANGLISTEN','ГЛОБАЛЬНЫЕ ТАБЛИЦЫ','全球排行榜','グローバルランキング','글로벌 순위','لوحات المتصدرين العالمية','GLOBAL LİDER TABLOLARI'],
          ['I TUOI RECORD','YOUR RECORDS','TUS RÉCORDS','VOS RECORDS','DEINE REKORDE','ВАШИ РЕКОРДЫ','你的记录','あなたの記録','나의 기록','سجلاتك','REKORLARIN'],
          ['RIVALI IN ARENA','ARENA RIVALS','RIVALES EN LA ARENA','ADVERSAIRES DE L’ARÈNE','ARENA-GEGNER','СОПЕРНИКИ НА АРЕНЕ','竞技场对手','アリーナのライバル','아레나 라이벌','خصوم الساحة','ARENA RAKİPLERİ'],
          ['SUPPORTO','SUPPORT','AYUDA','ASSISTANCE','SUPPORT','ПОДДЕРЖКА','支持','サポート','지원','الدعم','DESTEK'],
          ['MUSICA','MUSIC','MÚSICA','MUSIQUE','MUSIK','МУЗЫКА','音乐','音楽','음악','الموسيقى','MÜZİK'],
          ['SHOP','SHOP','TIENDA','BOUTIQUE','SHOP','МАГАЗИН','商店','ショップ','상점','المتجر','MAĞAZA'],
          ['MESSAGGI','MESSAGES','MENSAJES','MESSAGES','NACHRICHTEN','СООБЩЕНИЯ','消息','メッセージ','메시지','الرسائل','MESAJLAR'],
          ['AMICI','FRIENDS','AMIGOS','AMIS','FREUNDE','ДРУЗЬЯ','好友','友達','친구','الأصدقاء','ARKADAŞLAR'],
          ['GRUPPI','GROUPS','GRUPOS','GROUPES','GRUPPEN','ГРУППЫ','群组','グループ','그룹','المجموعات','GRUPLAR'],
          ['STORIE','STORIES','HISTORIAS','STORIES','STORIES','ИСТОРИИ','故事','ストーリー','스토리','القصص','HİKAYELER'],
          ['REELS','REELS','REELS','REELS','REELS','REELS','短视频','リール','릴스','ريلز','REELS'],
          ['VIDEO','VIDEO','VÍDEO','VIDÉO','VIDEO','ВИДЕО','视频','動画','동영상','فيديو','VİDEO'],
          ['SEGUI','FOLLOW','SEGUIR','SUIVRE','FOLGEN','ПОДПИСАТЬСЯ','关注','フォロー','팔로우','متابعة','TAKİP ET'],
          ['ISCRITTO','SUBSCRIBED','SUSCRITO','ABONNÉ','ABONNIERT','ПОДПИСАНО','已订阅','登録済み','구독 중','مشترك','ABONE OLUNDU'],
          ['CREA','CREATE','CREAR','CRÉER','ERSTELLEN','СОЗДАТЬ','创建','作成','만들기','إنشاء','OLUŞTUR'],
          ['AGGIORNA','UPDATE','ACTUALIZAR','METTRE À JOUR','AKTUALISIEREN','ОБНОВИТЬ','更新','更新','업데이트','تحديث','GÜNCELLE'],
          ['LINGUA','LANGUAGE','IDIOMA','LANGUE','SPRACHE','ЯЗЫК','语言','言語','언어','اللغة','DİL'],
          ['LOCALE','LOCAL','LOCAL','LOCAL','LOKAL','ЛОКАЛЬНЫЙ','本地','ローカル','로컬','محلي','YEREL'],
          ['Nessun messaggio','No messages','No hay mensajes','Aucun message','Keine Nachrichten','Нет сообщений','没有消息','メッセージなし','메시지 없음','لا توجد رسائل','Mesaj yok'],
          ['Nessun post','No posts','No hay publicaciones','Aucun post','Keine Beiträge','Нет публикаций','没有帖子','投稿なし','게시물 없음','لا توجد منشورات','Gönderi yok'],
          ['Nessun movimento registrato.','No transactions recorded.','No hay movimientos registrados.','Aucun mouvement enregistré.','Keine Transaktionen aufgezeichnet.','Транзакций нет.','暂无交易记录。','取引履歴はありません。','거래 기록이 없습니다.','لا توجد معاملات مسجلة.','Kayıtlı işlem yok.'],
          ['Nessuna partita registrata.','No games recorded.','No hay partidas registradas.','Aucune partie enregistrée.','Keine Spiele aufgezeichnet.','Игр пока нет.','暂无游戏记录。','ゲーム履歴はありません。','기록된 게임이 없습니다.','لا توجد مباريات مسجلة.','Kayıtlı oyun yok.'],
          ['Nessun rivale attivo.','No active rivals.','No hay rivales activos.','Aucun rival actif.','Keine aktiven Gegner.','Нет активных соперников.','没有活跃对手。','アクティブなライバルはいません。','활성 라이벌이 없습니다.','لا يوجد خصوم نشطون.','Aktif rakip yok.'],
          ['Profilo locale','Local profile','Perfil local','Profil local','Lokales Profil','Локальный профиль','本地资料','ローカルプロフィール','로컬 프로필','ملف محلي','Yerel profil'],
          ['Valuta virtuale','Virtual currency','Moneda virtual','Monnaie virtuelle','Virtuelle Währung','Виртуальная валюта','虚拟货币','仮想通貨','가상 화폐','عملة افتراضية','Sanal para'],
          ['nessun pagamento reale','no real-money payments','sin pagos reales','aucun paiement réel','keine Echtgeldzahlungen','без реальных платежей','无真实付款','実際の支払いなし','실제 결제 없음','لا توجد مدفوعات حقيقية','gerçek ödeme yok'],
          ['Comment added','Comment added','Comentario añadido','Commentaire ajouté','Kommentar hinzugefügt','Комментарий добавлен','评论已添加','コメントを追加しました','댓글이 추가되었습니다','تمت إضافة التعليق','Yorum eklendi']
        ];
        const PORTAL_COPY = {};
        PORTAL_COPY_ROWS.forEach((row) => {
          PORTAL_COPY[row[0]] = { en: row[1], es: row[2], fr: row[3], de: row[4], ru: row[5], zh: row[6], ja: row[7], ko: row[8], ar: row[9], tr: row[10] };
        });

        // Core portal copy is kept in the game source so every generated page
        // (including older Italian templates) receives the active locale. This
        // is deliberately local and deterministic: no translation request is
        // made at runtime, and unknown phrases keep a safe English fallback.
        const PORTAL_CORE_COPY_ROWS = [
          ['ZEROCRASH · PUNTA E INCASSA','ZEROCRASH · BET AND CASH OUT','ZEROCRASH · APUESTA Y COBRA','ZEROCRASH · PARIEZ ET ENCAISSEZ','ZEROCRASH · SETZEN UND AUSZAHLEN','ZEROCRASH · СТАВКА И ВЫВОД','ZEROCRASH · 下注并兑现','ZEROCRASH · ベットして換金','ZEROCRASH · 베팅 및 현금화','ZEROCRASH · راهن واسحب','ZEROCRASH · BAHİS YAP VE ÇEK'],
          ['SUPPORTO & GUIDA','SUPPORT & GUIDE','AYUDA Y GUÍA','ASSISTANCE ET GUIDE','SUPPORT & ANLEITUNG','ПОДДЕРЖКА И ГИД','支持与指南','サポートとガイド','지원 및 가이드','الدعم والدليل','DESTEK VE REHBER'],
          ['COME FUNZIONANO I ZEROCOIN','HOW ZEROCOIN WORKS','CÓMO FUNCIONA ZEROCOIN','COMMENT FONCTIONNE ZEROCOIN','SO FUNKTIONIERT ZEROCOIN','КАК РАБОТАЕТ ZEROCOIN','ZEROCOIN 的工作方式','ZEROCOIN の仕組み','ZEROCOIN 작동 방식','كيف تعمل ZEROCOIN','ZEROCOIN NASIL ÇALIŞIR'],
          ['POTENZIAMENTI PERMANENTI','PERMANENT BOOSTS','MEJORAS PERMANENTES','AMÉLIORATIONS PERMANENTES','DAUERHAFTE BOOSTS','ПОСТОЯННЫЕ УСИЛИТЕЛИ','永久增益','恒久ブースト','영구 부스트','تعزيزات دائمة','KALICI GÜÇLENDİRMELER'],
          ['CONSUMABILI','CONSUMABLES','CONSUMIBLES','CONSOMMABLES','VERBRAUCHSGÜTER','РАСХОДУЕМЫЕ ПРЕДМЕТЫ','消耗品','消耗品','소모품','مواد استهلاكية','TÜKETİLEBİLİRLER'],
          ['SKIN CELLA','CELL SKINS','ASPECTOS DE CÉLULA','SKINS DE CELLULE','ZELL-SKINS','СКИНЫ КЛЕТКИ','细胞皮肤','セルスキン','셀 스킨','مظاهر الخلية','HÜCRE GÖRÜNÜMLERİ'],
          ['PASS RUOLO','ROLE PASSES','PASES DE ROL','PASSES DE RÔLE','ROLLENPÄSSE','ПРОПУСКА РОЛЕЙ','角色通行证','ロールパス','역할 패스','بطاقات الأدوار','ROL BİLETLERİ'],
          ['ANNUNCIO GLOBALE','GLOBAL ANNOUNCEMENT','ANUNCIO GLOBAL','ANNONCE GLOBALE','GLOBALE ANKÜNDIGUNG','ГЛОБАЛЬНОЕ ОБЪЯВЛЕНИЕ','全局公告','グローバル告知','전체 공지','إعلان عالمي','KÜRESEL DUYURU'],
          ['STRUMENTI ARENA','ARENA TOOLS','HERRAMIENTAS DE ARENA','OUTILS D’ARÈNE','ARENA-WERKZEUGE','ИНСТРУМЕНТЫ АРЕНЫ','竞技场工具','アリーナツール','아레나 도구','أدوات الساحة','ARENA ARAÇLARI'],
          ['GESTIONE GIOCATORI / RIVALI','PLAYER / RIVAL MANAGEMENT','GESTIÓN DE JUGADORES / RIVALES','GESTION DES JOUEURS / RIVAUX','SPIELER- / GEGNERVERWALTUNG','УПРАВЛЕНИЕ ИГРОКАМИ / СОПЕРНИКАМИ','玩家/对手管理','プレイヤー・ライバル管理','플레이어 / 라이벌 관리','إدارة اللاعبين والخصوم','OYUNCU / RAKİP YÖNETİMİ'],
          ['ACCOUNT CORRENTE','CURRENT ACCOUNT','CUENTA ACTUAL','COMPTE ACTUEL','AKTUELLES KONTO','ТЕКУЩИЙ АККАУНТ','当前账户','現在のアカウント','현재 계정','الحساب الحالي','MEVCUT HESAP'],
          ['ECONOMIA ZEROCOIN','ZEROCOIN ECONOMY','ECONOMÍA ZEROCOIN','ÉCONOMIE ZEROCOIN','ZEROCOIN-WIRTSCHAFT','ЭКОНОМИКА ZEROCOIN','ZEROCOIN 经济','ZEROCOIN 経済','ZEROCOIN 경제','اقتصاد ZEROCOIN','ZEROCOIN EKONOMİSİ'],
          ['INTERFACCIA LIVE','LIVE INTERFACE','INTERFAZ EN VIVO','INTERFACE EN DIRECT','LIVE-SCHNITTSTELLE','ЖИВОЙ ИНТЕРФЕЙС','实时界面','ライブインターフェース','실시간 인터페이스','واجهة مباشرة','CANLI ARAYÜZ'],
          ['LOG TRANSAZIONI','TRANSACTION LOG','REGISTRO DE TRANSACCIONES','JOURNAL DES TRANSACTIONS','TRANSAKTIONSLOG','ЖУРНАЛ ТРАНЗАКЦИЙ','交易记录','取引ログ','거래 로그','سجل المعاملات','İŞLEM GÜNLÜĞÜ'],
          ['ZONA PERICOLOSA','DANGER ZONE','ZONA DE PELIGRO','ZONE DANGEREUSE','GEFAHRENZONE','ОПАСНАЯ ЗОНА','危险区域','危険ゾーン','위험 구역','منطقة الخطر','TEHLİKELİ BÖLGE'],
          ['INVIA A TUTTI','SEND TO EVERYONE','ENVIAR A TODOS','ENVOYER À TOUS','AN ALLE SENDEN','ОТПРАВИТЬ ВСЕМ','发送给所有人','全員に送信','모두에게 보내기','إرسال إلى الجميع','HERKESE GÖNDER'],
          ['MOSTRA ULTIMO','SHOW LATEST','MOSTRAR EL ÚLTIMO','AFFICHER LE DERNIER','LETZTES ANZEIGEN','ПОКАЗАТЬ ПОСЛЕДНЕЕ','显示最新','最新を表示','최신 표시','عرض الأحدث','SONUNCUYU GÖSTER'],
          ['PULISCI CHAT','CLEAR CHAT','LIMPIAR CHAT','EFFACER LE CHAT','CHAT LEEREN','ОЧИСТИТЬ ЧАТ','清空聊天','チャットを消去','채팅 지우기','مسح الدردشة','SOHBETİ TEMİZLE'],
          ['AZZERA PROFILO COMPLETO','WIPE COMPLETE PROFILE','BORRAR PERFIL COMPLETO','EFFACER LE PROFIL COMPLET','VOLLSTÄNDIGES PROFIL LÖSCHEN','ОЧИСТИТЬ ПРОФИЛЬ','清除完整资料','プロフィールを完全消去','전체 프로필 삭제','مسح الملف بالكامل','PROFİLİ TAMAMEN SİL'],
          ['TEMA DEL PORTALE','PORTAL THEME','TEMA DEL PORTAL','THÈME DU PORTAIL','PORTAL-THEMA','ТЕМА ПОРТАЛА','门户主题','ポータルテーマ','포털 테마','سمة البوابة','PORTAL TEMASI'],
          ['MUSICA & AUDIO','MUSIC & AUDIO','MÚSICA Y AUDIO','MUSIQUE ET AUDIO','MUSIK & AUDIO','МУЗЫКА И АУДИО','音乐与音频','音楽とオーディオ','음악 및 오디오','الموسيقى والصوت','MÜZİK VE SES'],
          ['RISCATTATO','CLAIMED','CANJEADO','RÉCLAMÉ','EINGELÖST','ПОЛУЧЕНО','已领取','受け取り済み','받음','تم الاستلام','ALINDI'],
          ['IN CORSO','IN PROGRESS','EN CURSO','EN COURS','IN ARBEIT','В ПРОЦЕССЕ','进行中','進行中','진행 중','قيد التنفيذ','DEVAM EDİYOR'],
          ['Nessun punteggio registrato.','No scores recorded.','No hay puntuaciones registradas.','Aucun score enregistré.','Keine Punktzahlen aufgezeichnet.','Нет зарегистрированных очков.','暂无记录分数。','記録されたスコアはありません。','기록된 점수가 없습니다.','لا توجد نتائج مسجلة.','Kayıtlı skor yok.']
        ];
        PORTAL_CORE_COPY_ROWS.forEach((row) => {
          PORTAL_COPY[row[0]] = { en: row[1], es: row[2], fr: row[3], de: row[4], ru: row[5], zh: row[6], ja: row[7], ko: row[8], ar: row[9], tr: row[10] };
        });

        // Second-pass portal copy catalog. The original portal was assembled from
        // many feature modules, so navigation labels were localized but the copy
        // inside cards, forms and status messages stayed Italian. These authored
        // phrases cover the shared UI used by every portal page and are applied
        // locally after each render (no translation API or network request).
        const PORTAL_DYNAMIC_COPY_ROWS = [
          ['Dieci sezioni, giochi live, valuta ZeroCoin e uno shop interno. Inizia dall’arena neon.','Ten sections, live games, ZeroCoin currency and an internal shop. Start in the neon arena.','Diez secciones, juegos en vivo, moneda ZeroCoin y una tienda interna. Empieza en la arena neón.','Dix sections, des jeux en direct, la monnaie ZeroCoin et une boutique interne. Commence dans l’arène néon.','Zehn Bereiche, Live-Spiele, ZeroCoin-Währung und ein interner Shop. Starte in der Neon-Arena.','Dez secções, jogos ao vivo, moeda ZeroCoin e uma loja interna. Começa na arena neon.','Десять разделов, игры, валюта ZeroCoin и внутренний магазин. Начни на неоновой арене.','十个专区、在线游戏、ZeroCoin 和内部商店。从霓虹竞技场开始。','10のセクション、ライブゲーム、ZeroCoin、ショップ。ネオンアリーナから始めよう。','10개 섹션, 라이브 게임, ZeroCoin과 내부 상점. 네온 아레나에서 시작하세요.','On bölüm, canlı oyunlar, ZeroCoin para birimi ve dahili mağaza. Neon arenada başla.'],
          ['Imposta la puntata e premi PUNTA. Incassa prima del crash!','Set your bet and press BET. Cash out before the crash!','Configura tu apuesta y pulsa APUESTA. ¡Cobra antes del crash!','Définissez votre mise et appuyez sur PARIER. Encaissez avant le crash !','Setze deinen Einsatz und drücke SETZEN. Zahle vor dem Crash aus!','Define a aposta e prima APOSTAR. Levanta antes do crash!','Установи ставку и нажми СТАВКА. Забери выигрыш до краха!','设置下注并按下下注。崩溃前兑现！','ベットを設定してBETを押そう。クラッシュ前に換金！','베팅을 설정하고 BET을 누르세요. 크래시 전에 현금화하세요!','Bahsi ayarla ve BAHİS yap. Çökmeden önce çek!'],
          ['Gioco dimostrativo con valuta virtuale ZeroCoin: nessun denaro reale, nessun pagamento.','Demonstration game with virtual ZeroCoin: no real money and no payments.','Juego de demostración con ZeroCoin virtual: sin dinero real ni pagos.','Jeu de démonstration avec ZeroCoin virtuel : aucun argent réel ni paiement.','Demospiel mit virtuellen ZeroCoin: kein echtes Geld und keine Zahlungen.','Jogo demonstrativo com ZeroCoin virtual: sem dinheiro real e sem pagamentos.','Демонстрационная игра с виртуальными ZeroCoin: без реальных денег и платежей.','虚拟 ZeroCoin 演示游戏：不涉及真实货币或付款。','仮想ZeroCoinのデモゲーム：現金や支払いはありません。','가상 ZeroCoin 데모 게임: 실제 돈이나 결제는 없습니다.','Sanal ZeroCoin ile demo oyun: gerçek para ve ödeme yok.'],
          ['Tutti gli acquisti usano la valuta virtuale ZeroCoin guadagnata giocando.','All purchases use virtual ZeroCoin earned by playing.','Todas las compras usan ZeroCoin virtuales obtenidos jugando.','Tous les achats utilisent des ZeroCoin virtuels gagnés en jouant.','Alle Käufe verwenden virtuelle ZeroCoin, die du beim Spielen verdienst.','Todas as compras usam ZeroCoin virtuais ganhos ao jogar.','Все покупки используют виртуальные ZeroCoin, заработанные в игре.','所有购买都使用游戏中赚取的虚拟 ZeroCoin。','すべての購入はゲームで獲得した仮想ZeroCoinを使用します。','모든 구매는 플레이로 얻은 가상 ZeroCoin을 사용합니다.','Tüm alışverişlerde oyun oynayarak kazanılan sanal ZeroCoin kullanılır.'],
          ['Si rinnovano ogni giorno. Completale per ZeroCoin, XP profilo e XP Zero Pass.','They refresh every day. Complete them for ZeroCoin, profile XP and Zero Pass XP.','Se renuevan cada día. Complétalas para conseguir ZeroCoin, XP de perfil y XP de Zero Pass.','Elles se renouvellent chaque jour. Terminez-les pour gagner des ZeroCoin, de l’XP de profil et de l’XP Zero Pass.','Sie werden täglich erneuert. Schließe sie für ZeroCoin, Profil-XP und Zero-Pass-XP ab.','Renovam todos os dias. Completa-as para ganhar ZeroCoin, XP de perfil e XP Zero Pass.','Они обновляются каждый день. Выполняй их ради ZeroCoin, опыта профиля и Zero Pass.','任务每天刷新。完成任务可获得 ZeroCoin、资料经验和 Zero Pass 经验。','毎日更新されます。達成してZeroCoin、プロフィールXP、Zero Pass XPを獲得しよう。','매일 갱신됩니다. 완료하면 ZeroCoin, 프로필 XP, Zero Pass XP를 얻습니다.','Her gün yenilenir. ZeroCoin, profil XP ve Zero Pass XP için tamamla.'],
          ['La chat globale vive dentro l’arena: aprila col tasto CHAT in basso a sinistra o premi C / T. Usa /help per i comandi.','Global chat lives inside the arena: open it with CHAT at the bottom left or press C / T. Use /help for commands.','El chat global vive dentro de la arena: ábrelo con CHAT abajo a la izquierda o pulsa C / T. Usa /help para ver los comandos.','Le chat global est dans l’arène : ouvrez-la avec CHAT en bas à gauche ou appuyez sur C / T. Utilisez /help pour les commandes.','Der globale Chat befindet sich in der Arena: Öffne ihn mit CHAT unten links oder drücke C / T. Nutze /help für Befehle.','O chat global vive na arena: abre-o com CHAT no canto inferior esquerdo ou prime C / T. Usa /help para comandos.','Глобальный чат находится на арене: открой его кнопкой CHAT или нажми C / T. Используй /help для команд.','全球聊天位于竞技场内：点击左下角 CHAT 或按 C / T。使用 /help 查看命令。','グローバルチャットはアリーナ内にあります。左下のCHATまたはC / Tで開き、/helpでコマンドを確認できます。','글로벌 채팅은 아레나 안에 있습니다. 왼쪽 아래 CHAT 또는 C / T로 열고 /help를 사용하세요.','Genel sohbet arenadadır: sol alttaki CHAT ile aç veya C / T tuşuna bas. Komutlar için /help kullan.'],
          ['La sezione MUSICA contiene il jukebox (lobby, arena, bonus e traccia generata live), il mixer e i temi del portale.','MUSIC includes the jukebox (lobby, arena, bonus and live-generated track), mixer and portal themes.','MÚSICA incluye el jukebox, el mezclador y los temas del portal.','MUSIQUE contient le jukebox, le mixeur et les thèmes du portail.','MUSIK enthält Jukebox, Mixer und Portal-Themes.','MÚSICA inclui jukebox, mixer e temas do portal.','В разделе МУЗЫКА есть джукбокс, микшер и темы портала.','音乐区包含点唱机、混音器和门户主题。','音楽にはジュークボックス、ミキサー、ポータルテーマがあります。','음악에는 주크박스, 믹서와 포털 테마가 있습니다.','MÜZİK bölümünde jukebox, mikser ve portal temaları bulunur.'],
          ['La sezione MINI GIOCHI offre 10 titoli istantanei che pagano ZeroCoin e fanno avanzare missioni e Zero Pass.','MINI GAMES offers 10 instant titles that pay ZeroCoin and advance quests and Zero Pass.','MINIJUEGOS ofrece 10 títulos instantáneos que pagan ZeroCoin y avanzan misiones y Zero Pass.','MINI-JEUX propose 10 jeux instantanés qui rapportent des ZeroCoin et font progresser les quêtes et le Zero Pass.','MINISPIELE bieten 10 sofort spielbare Titel mit ZeroCoin sowie Quest- und Zero-Pass-Fortschritt.','MINIJOGOS oferece 10 títulos instantâneos que pagam ZeroCoin e avançam missões e Zero Pass.','МИНИ-ИГРЫ предлагают 10 игр, которые дают ZeroCoin и продвигают задания и Zero Pass.','小游戏提供 10 个即时玩法，可获得 ZeroCoin 并推进任务和 Zero Pass。','ミニゲームにはZeroCoinを獲得し、クエストとZero Passを進められる10タイトルがあります。','미니 게임에는 ZeroCoin을 지급하고 퀘스트와 Zero Pass를 진행하는 10개 타이틀이 있습니다.','MİNİ OYUNLAR ZeroCoin kazandıran ve görevleri ilerleten 10 anlık oyun sunar.'],
          ['Nessun punteggio registrato.','No scores recorded.','No hay puntuaciones registradas.','Aucun score enregistré.','Keine Punktzahlen aufgezeichnet.','Nenhuma pontuação registada.','Нет зарегистрированных очков.','暂无记录分数。','記録されたスコアはありません。','기록된 점수가 없습니다.','Kayıtlı skor yok.'],
          ['Nessun rivale attivo.','No active rivals.','No hay rivales activos.','Aucun rival actif.','Keine aktiven Gegner.','Nenhum rival ativo.','Нет активных соперников.','没有活跃对手。','アクティブなライバルはいません。','활성 라이벌이 없습니다.','Aktif rakip yok.'],
          ['Nessun movimento registrato.','No transactions recorded.','No hay movimientos registrados.','Aucun mouvement enregistré.','Keine Transaktionen aufgezeichnet.','Nenhum movimento registado.','Транзакций нет.','暂无交易记录。','取引履歴はありません。','거래 기록이 없습니다.','Kayıtlı işlem yok.'],
          ['Nessuna partita registrata.','No games recorded.','No hay partidas registradas.','Aucune partie enregistrée.','Keine Spiele aufgezeichnet.','Nenhum jogo registado.','Игр пока нет.','暂无游戏记录。','ゲーム履歴はありません。','기록된 게임이 없습니다.','Kayıtlı oyun yok.']
        ];
        PORTAL_DYNAMIC_COPY_ROWS.forEach((row) => {
          PORTAL_COPY[row[0]] = { en: row[1], es: row[2], fr: row[3], de: row[4], pt: row[5], ru: row[6], zh: row[7], ja: row[8], ko: row[9], tr: row[10] };
        });

        function portalCopyFor(text) {
          const lang = portalLanguage();
          const exact = PORTAL_COPY[text];
          if (exact && lang !== 'it') return exact[lang] || exact.en || text;
          return text;
        }
        function translatePortalTextNode(node) {
          if (!node || !node.nodeValue || !portalEl || node.parentElement && node.parentElement.closest('#pt-language-menu')) return;
          let value = node.nodeValue;
          const trimmed = value.trim();
          if (trimmed && PORTAL_COPY[trimmed] && trimmed === value.trim()) {
            const translated = portalCopyFor(trimmed);
            node.nodeValue = value.replace(trimmed, translated);
            return;
          }
          // Translate embedded labels in longer generated strings while keeping
          // player names, numbers and game titles intact.
          const keys = Object.keys(PORTAL_COPY).filter((k) => k.length > 3).sort((a, b) => b.length - a.length);
          if (portalLanguage() === 'it') return;
          keys.forEach((key) => {
            if (value.indexOf(key) >= 0) value = value.split(key).join(portalCopyFor(key));
          });
          if (value !== node.nodeValue) node.nodeValue = value;
        }
        function translatePortalDom() {
          if (!portalEl || portalLanguage() === 'it') return;
          const walker = document.createTreeWalker(portalEl, NodeFilter.SHOW_TEXT);
          const nodes = [];
          let node;
          while ((node = walker.nextNode())) nodes.push(node);
          nodes.forEach(translatePortalTextNode);
          portalEl.querySelectorAll('input, textarea, select, button, [aria-label], [title]').forEach((el) => {
            ['placeholder', 'aria-label', 'title'].forEach((attr) => {
              if (el.hasAttribute(attr)) {
                const original = el.getAttribute(attr);
                el.setAttribute(attr, portalCopyFor(original));
              }
            });
          });
        }
                function languageFlag(code) {
          const flags = {
            it:'🇮🇹', en:'🇬🇧', es:'🇪🇸', fr:'🇫🇷', de:'🇩🇪', pt:'🇵🇹', ru:'🇷🇺', uk:'🇺🇦', pl:'🇵🇱', tr:'🇹🇷',
            nl:'🇳🇱', sv:'🇸🇪', da:'🇩🇰', no:'🇳🇴', fi:'🇫🇮', el:'🇬🇷', cs:'🇨🇿', ro:'🇷🇴', hu:'🇭🇺', bg:'🇧🇬',
            sr:'🇷🇸', hr:'🇭🇷', sk:'🇸🇰', sl:'🇸🇮', ar:'🇸🇦', he:'🇮🇱', fa:'🇮🇷', hi:'🇮🇳', bn:'🇧🇩', ur:'🇵🇰',
            zh:'🇨🇳', ja:'🇯🇵', ko:'🇰🇷', vi:'🇻🇳', th:'🇹🇭', id:'🇮🇩', ms:'🇲🇾', tl:'🇵🇭', sw:'🇰🇪'
          };
          return flags[String(code || '').toLowerCase()] || '🌐';
        }
        function renderLanguagePicker() {
          if (!ptLanguageMenuEl) return;
          const active = portalLanguage();
          const entry = WORLD_LANGUAGES.find((x) => x[0] === active);
          if (ptLanguageFlagEl) ptLanguageFlagEl.textContent = languageFlag(active);
          if (ptLanguageCodeEl) ptLanguageCodeEl.textContent = active.toUpperCase();
          ptLanguageMenuEl.innerHTML = WORLD_LANGUAGES.map((x) => '<button type="button" role="menuitem" class="pt-language-option' + (x[0] === active ? ' active' : '') + '" data-language="' + x[0] + '" aria-label="' + escapeHtml(x[1]) + '"><span class="flag">' + languageFlag(x[0]) + '</span><span class="code">' + x[0].toUpperCase() + '</span></button>').join('');
          // Option clicks are handled by the delegated listener below, which
          // survives this innerHTML refresh and works for mouse and touch.

          if (entry && ptLanguageToggleEl) ptLanguageToggleEl.setAttribute('aria-label', 'Lingua: ' + entry[1]);
        }
        function toggleLanguagePicker(force) {
          if (!ptLanguagePickerEl) return;
          const open = force !== undefined ? !!force : !ptLanguagePickerEl.classList.contains('open');
          ptLanguagePickerEl.classList.toggle('open', open);
          if (ptLanguageToggleEl) ptLanguageToggleEl.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        function showLanguageGate() {
          if (!languageGateEl || !languageGateSelectEl) return;
          languageGateSelectEl.innerHTML = WORLD_LANGUAGES.map((x) =>
            '<option value="' + x[0] + '">' + languageFlag(x[0]) + '  ' + escapeHtml(x[1]) + '</option>'
          ).join('');
          const preferred = (S && S.language && WORLD_LANGUAGES.some((x) => x[0] === S.language)) ? S.language : detectLanguage();
          languageGateSelectEl.value = preferred;
          languageGateEl.classList.add('visible');
          languageGateSelectEl.focus();
        }
        function closeLanguageGate() {
          if (!languageGateEl || !languageGateSelectEl) return;
          const code = languageGateSelectEl.value || 'en';
          languageOverride = code;
          setSetting('language', code);
          languageGateEl.classList.remove('visible');
          applySettings();
          openPortal('home');
        }
        if (languageGateContinueEl) {
          languageGateContinueEl.addEventListener('click', (e) => {
            e.preventDefault();
            closeLanguageGate();
          });
        }
        if (languageGateSelectEl) {
          languageGateSelectEl.addEventListener('keydown', (e) => {
            if (e.code === 'Enter') {
              e.preventDefault();
              closeLanguageGate();
            }
          });
        }

        // Handle language choices from the stable menu element itself. The menu
        // is rebuilt on every locale change, so individual option listeners are
        // intentionally avoided. `pointerdown` is used in addition to `click`
        // because the game world disables touch gestures globally; this makes
        // the same option reliable for mouse, touch and pen input.
        let languagePointerHandled = false;
        function choosePortalLanguage(option, event) {
          if (!option || !ptLanguageMenuEl || !ptLanguageMenuEl.contains(option)) return;
          const code = option.dataset.language;
          if (!WORLD_LANGUAGES.some((x) => x[0] === code)) return;
          if (event) {
            event.preventDefault();
            event.stopPropagation();
          }
          languageOverride = code;
          setSetting('language', code);
          document.documentElement.lang = code;
          document.documentElement.dir = ['ar', 'fa', 'he', 'ur'].indexOf(code) >= 0 ? 'rtl' : 'ltr';
          toggleLanguagePicker(false);
          if (portalOpen) {
            renderPortal();
            setPtPage(ptPage);
            translatePortalDom();
          }
        }
        if (ptLanguageMenuEl) {
          /* Do not select on pointerdown: changing the language re-renders the
             portal and detaches the pressed button before the browser can finish
             its click sequence.  Let the native click activate the option once. */
          ptLanguageMenuEl.addEventListener('pointerdown', (e) => {
            const option = e.target && e.target.closest ? e.target.closest('[data-language]') : null;
            if (option) {
              e.preventDefault();
              e.stopPropagation();
            }
          }, { capture: true, passive: false });
          ptLanguageMenuEl.addEventListener('click', (e) => {
            const option = e.target && e.target.closest ? e.target.closest('[data-language]') : null;
            if (!option) return;
            choosePortalLanguage(option, e);
          });
        }
        function showGlobalAnnouncement(text, playSound) {
          const value = String(text || '').trim().slice(0, 240);
          if (!value || !globalAnnouncementEl || !globalAnnouncementTextEl || !globalAnnouncementTrackEl) return;
          currentGlobalAnnouncement = value;
          globalAnnouncementTextEl.textContent = '  ' + value + '  ✦  ' + value + '  ';
          globalAnnouncementTrackEl.style.animationDuration = Math.max(12, Math.min(30, 11 + value.length * 0.08)) + 's';
          globalAnnouncementEl.classList.remove('visible');
          void globalAnnouncementEl.offsetWidth;
          globalAnnouncementEl.classList.add('visible');
          globalAnnouncementEl.setAttribute('aria-hidden', 'false');
          if (playSound) playGlobalAnnouncementSound();
        }
        function hideGlobalAnnouncement() {
          currentGlobalAnnouncement = '';
          if (globalAnnouncementEl) { globalAnnouncementEl.classList.remove('visible'); globalAnnouncementEl.setAttribute('aria-hidden', 'true'); }
        }
        if (globalAnnouncementCloseEl) globalAnnouncementCloseEl.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();
          hideGlobalAnnouncement();
        });
        function playGlobalAnnouncementSound() {
          if (!S || S.mute || S.sfxVolume === 0) return;
          initAudio();
          if (!audioCtx) return;
          try {
            const now = audioCtx.currentTime;
            const gain = audioCtx.createGain();
            gain.gain.setValueAtTime(0.0001, now);
            gain.gain.exponentialRampToValueAtTime(0.22 * sfxVol(), now + 0.03);
            gain.gain.exponentialRampToValueAtTime(0.0001, now + 1.1);
            gain.connect(audioCtx.destination);
            [523.25, 659.25, 783.99].forEach((freq, i) => {
              const osc = audioCtx.createOscillator();
              osc.type = i === 1 ? 'triangle' : 'sine';
              osc.frequency.value = freq;
              osc.connect(gain);
              osc.start(now + i * 0.13);
              osc.stop(now + 1.12);
            });
          } catch (e) {}
        }
        function sendGlobalAnnouncement(text) {
          if (!hasPerm('admin')) { notify('Funzione riservata agli ADMIN', '#ff5c7a'); return false; }
          const value = String(text || '').trim().slice(0, 240);
          if (!value) { notify('Scrivi un annuncio prima di inviare', '#ff5c7a'); return false; }
          const h = socialHubState();
          h.portalNotices.unshift({ id:'notice_' + Date.now(), text:value, author:profile.name || 'ADMIN', created:Date.now() });
          h.portalNotices = h.portalNotices.slice(0, 12);
          showGlobalAnnouncement(value, true);
          notify('Annuncio globale inviato in tutto il portale e nell’arena', '#ffe600');
          addChatMsg('', '📢 ANNUNCIO ADMIN: ' + value, 'sys');
          socialHubSave();
          renderPortal();
          return true;
        }
        function showLatestGlobalAnnouncement() {
          const h = socialHubState();
          const latest = Array.isArray(h.portalNotices) && h.portalNotices[0];
          if (latest && latest.text) {
            if (latest.text !== currentGlobalAnnouncement) showGlobalAnnouncement(latest.text, false);
          } else if (currentGlobalAnnouncement) hideGlobalAnnouncement();
        }
        const TUTORIAL_LOCALE = {
          it: { nav:'TUTORIAL', kicker:'ZERO THE LEGEND · ACADEMY', title:'Impara tutto il portale, in una lingua che senti tua.', desc:'Serie di video tutorial locali con una presentatrice virtuale: arena, giochi, shop, ZeroCoin, social e impostazioni. Audio narrato quando il browser lo supporta e testo sempre disponibile.', choose:'SCEGLI LINGUA', host:'PRESENTATRICE VIRTUALE', start:'AVVIA VIDEO', pause:'PAUSA VIDEO', next:'LEZIONE SUCCESSIVA', lessons:'SERIE COMPLETA', faq:'FAQ RAPIDA', legal:'DIRITTI E COPYRIGHT', creator:'Creatore: Zero The Legend', noSpeech:'La voce usa la sintesi vocale locale del browser.', lessonDone:'COMPLETATA', lessonOpen:'APRI' },
          en: { nav:'TUTORIALS', kicker:'ZERO THE LEGEND · ACADEMY', title:'Learn the whole portal in a language that feels yours.', desc:'A local tutorial video series with a virtual presenter: arena, games, shop, ZeroCoin, social and settings. Narration is available when the browser supports it, with text always available.', choose:'CHOOSE LANGUAGE', host:'VIRTUAL PRESENTER', start:'START VIDEO', pause:'PAUSE VIDEO', next:'NEXT LESSON', lessons:'FULL SERIES', faq:'QUICK FAQ', legal:'RIGHTS & COPYRIGHT', creator:'Creator: Zero The Legend', noSpeech:'Voice uses your browser’s local speech engine.', lessonDone:'COMPLETED', lessonOpen:'OPEN' },
          es: { nav:'TUTORIALES', kicker:'ZERO THE LEGEND · ACADEMIA', title:'Aprende todo el portal en tu idioma.', desc:'Serie local de tutoriales con una presentadora virtual: arena, juegos, tienda, ZeroCoin, social y ajustes.', choose:'ELEGIR IDIOMA', host:'PRESENTADORA VIRTUAL', start:'INICIAR VIDEO', pause:'PAUSAR VIDEO', next:'SIGUIENTE LECCIÓN', lessons:'SERIE COMPLETA', faq:'FAQ RÁPIDA', legal:'DERECHOS Y COPYRIGHT', creator:'Creador: Zero The Legend', noSpeech:'La voz usa el sistema local del navegador.', lessonDone:'COMPLETADA', lessonOpen:'ABRIR' },
          fr: { nav:'TUTORIELS', kicker:'ZERO THE LEGEND · ACADÉMIE', title:'Apprends tout le portail dans ta langue.', desc:'Série locale avec une présentatrice virtuelle : arène, jeux, boutique, ZeroCoin, social et réglages.', choose:'CHOISIR LA LANGUE', host:'PRÉSENTATRICE VIRTUELLE', start:'LANCER LA VIDÉO', pause:'PAUSE', next:'LEÇON SUIVANTE', lessons:'SÉRIE COMPLÈTE', faq:'FAQ RAPIDE', legal:'DROITS ET COPYRIGHT', creator:'Créateur : Zero The Legend', noSpeech:'La voix utilise la synthèse locale du navigateur.', lessonDone:'TERMINÉE', lessonOpen:'OUVRIR' },
          de: { nav:'TUTORIALS', kicker:'ZERO THE LEGEND · AKADEMIE', title:'Lerne das gesamte Portal in deiner Sprache.', desc:'Lokale Tutorial-Serie mit virtueller Präsentatorin: Arena, Spiele, Shop, ZeroCoin, Social und Einstellungen.', choose:'SPRACHE WÄHLEN', host:'VIRTUELLE PRÄSENTATORIN', start:'VIDEO STARTEN', pause:'PAUSE', next:'NÄCHSTE LEKTION', lessons:'KOMPLETTE SERIE', faq:'SCHNELLE FAQ', legal:'RECHTE & COPYRIGHT', creator:'Ersteller: Zero The Legend', noSpeech:'Die Stimme nutzt die lokale Browser-Synthese.', lessonDone:'ABGESCHLOSSEN', lessonOpen:'ÖFFNEN' },
          pt: { nav:'TUTORIAIS', kicker:'ZERO THE LEGEND · ACADEMIA', title:'Aprende todo o portal no teu idioma.', desc:'Série local com apresentadora virtual: arena, jogos, loja, ZeroCoin, social e definições.', choose:'ESCOLHER IDIOMA', host:'APRESENTADORA VIRTUAL', start:'INICIAR VÍDEO', pause:'PAUSA', next:'PRÓXIMA LIÇÃO', lessons:'SÉRIE COMPLETA', faq:'FAQ RÁPIDA', legal:'DIREITOS E COPYRIGHT', creator:'Criador: Zero The Legend', noSpeech:'A voz usa a síntese local do navegador.', lessonDone:'CONCLUÍDA', lessonOpen:'ABRIR' },
          ru: { nav:'УРОКИ', kicker:'ZERO THE LEGEND · АКАДЕМИЯ', title:'Изучи весь портал на своём языке.', desc:'Локальная серия уроков с виртуальной ведущей: арена, игры, магазин, ZeroCoin, социальный раздел и настройки.', choose:'ВЫБРАТЬ ЯЗЫК', host:'ВИРТУАЛЬНАЯ ВЕДУЩАЯ', start:'ЗАПУСТИТЬ ВИДЕО', pause:'ПАУЗА', next:'СЛЕДУЮЩИЙ УРОК', lessons:'ПОЛНАЯ СЕРИЯ', faq:'БЫСТРЫЕ FAQ', legal:'ПРАВА И COPYRIGHT', creator:'Создатель: Zero The Legend', noSpeech:'Голос использует локальный синтез браузера.', lessonDone:'ЗАВЕРШЕНО', lessonOpen:'ОТКРЫТЬ' },
          zh: { nav:'教程', kicker:'ZERO THE LEGEND · 学院', title:'用你的语言学习整个门户。', desc:'本地教程系列，虚拟主持人讲解竞技场、游戏、商店、ZeroCoin、社交和设置。', choose:'选择语言', host:'虚拟主持人', start:'开始视频', pause:'暂停视频', next:'下一课', lessons:'完整系列', faq:'快速 FAQ', legal:'权利与版权', creator:'创作者：Zero The Legend', noSpeech:'语音使用浏览器本地引擎。', lessonDone:'已完成', lessonOpen:'打开' },
          ja: { nav:'チュートリアル', kicker:'ZERO THE LEGEND · アカデミー', title:'好きな言語でポータルを学ぼう。', desc:'アリーナ、ゲーム、ショップ、ZeroCoin、ソーシャル、設定をバーチャル案内役が説明するローカル動画シリーズ。', choose:'言語を選択', host:'バーチャル案内役', start:'動画を開始', pause:'一時停止', next:'次のレッスン', lessons:'全シリーズ', faq:'クイック FAQ', legal:'権利と著作権', creator:'クリエイター：Zero The Legend', noSpeech:'音声はブラウザのローカル音声を使用します。', lessonDone:'完了', lessonOpen:'開く' },
          ko: { nav:'튜토리얼', kicker:'ZERO THE LEGEND · 아카데미', title:'내 언어로 포털 전체를 배워보세요.', desc:'아레나, 게임, 상점, ZeroCoin, 소셜과 설정을 가상 진행자가 설명하는 로컬 튜토리얼 시리즈입니다.', choose:'언어 선택', host:'가상 진행자', start:'영상 시작', pause:'영상 일시정지', next:'다음 레슨', lessons:'전체 시리즈', faq:'빠른 FAQ', legal:'권리 및 저작권', creator:'제작자: Zero The Legend', noSpeech:'음성은 브라우저의 로컬 음성 엔진을 사용합니다.', lessonDone:'완료', lessonOpen:'열기' },
          ar: { nav:'دروس تعليمية', kicker:'ZERO THE LEGEND · الأكاديمية', title:'تعلّم البوابة كاملة بلغتك.', desc:'سلسلة دروس محلية مع مقدمة افتراضية تشرح الساحة والألعاب والمتجر وZeroCoin والمجتمع والإعدادات.', choose:'اختر اللغة', host:'المقدمة الافتراضية', start:'ابدأ الفيديو', pause:'إيقاف مؤقت', next:'الدرس التالي', lessons:'السلسلة كاملة', faq:'الأسئلة الشائعة', legal:'الحقوق وحقوق النشر', creator:'المنشئ: Zero The Legend', noSpeech:'يستخدم الصوت محرك الكلام المحلي في المتصفح.', lessonDone:'مكتمل', lessonOpen:'افتح' },
          hi: { nav:'ट्यूटोरियल', kicker:'ZERO THE LEGEND · अकादमी', title:'अपनी भाषा में पूरा पोर्टल सीखें।', desc:'वर्चुअल प्रस्तुतकर्ता के साथ स्थानीय ट्यूटोरियल श्रृंखला: एरीना, गेम्स, शॉप, ZeroCoin, सोशल और सेटिंग्स।', choose:'भाषा चुनें', host:'वर्चुअल प्रस्तुतकर्ता', start:'वीडियो शुरू करें', pause:'रोकें', next:'अगला पाठ', lessons:'पूरी श्रृंखला', faq:'त्वरित FAQ', legal:'अधिकार और कॉपीराइट', creator:'निर्माता: Zero The Legend', noSpeech:'आवाज़ ब्राउज़र के स्थानीय इंजन का उपयोग करती है।', lessonDone:'पूर्ण', lessonOpen:'खोलें' },
          tr: { nav:'EĞİTİMLER', kicker:'ZERO THE LEGEND · AKADEMİ', title:'Tüm portalı kendi dilinde öğren.', desc:'Arena, oyunlar, mağaza, ZeroCoin, sosyal ve ayarları anlatan yerel sanal sunucu eğitim serisi.', choose:'DİL SEÇ', host:'SANAL SUNUCU', start:'VİDEOYU BAŞLAT', pause:'DURAKLAT', next:'SONRAKİ DERS', lessons:'TAM SERİ', faq:'HIZLI SSS', legal:'HAKLAR VE TELİF', creator:'Yaratıcı: Zero The Legend', noSpeech:'Ses, tarayıcının yerel konuşma motorunu kullanır.', lessonDone:'TAMAMLANDI', lessonOpen:'AÇ' }
        };
        function tutorialLocale(key) { const d = TUTORIAL_LOCALE[portalLanguage()] || TUTORIAL_LOCALE.en; return d[key] || TUTORIAL_LOCALE.en[key] || key; }
        function portalLocale(key) {
          const dict = {
            it:{kicker:'ZERO OMNI HUB · LOCALE',title:'Un solo portale per audio, social e creator',desc:'Funzioni locali ispirate ai formati che ami: audio, feed, video, short, live, chat e community. Nessun account esterno viene collegato.',language:'LINGUA',input:'Scrivi un testo per pubblicare, creare o condividere...',tools:'40 NUOVI STRUMENTI',audio:'AUDIO CLOUD',library:'LIBRERIA LOCALE',use:'USA',queue:'CODA',local:'OFFLINE · PROFILO LOCALE',export:'ESPORTA HUB'},
            en:{kicker:'ZERO OMNI HUB · LOCAL',title:'One portal for audio, social and creators',desc:'Local features inspired by the formats you love: audio, feeds, video, shorts, live, chat and communities. No external account is connected.',language:'LANGUAGE',input:'Write text to publish, create or share...',tools:'40 NEW TOOLS',audio:'AUDIO CLOUD',library:'LOCAL LIBRARY',use:'USE',queue:'QUEUE',local:'OFFLINE · LOCAL PROFILE',export:'EXPORT HUB'},
            es:{kicker:'ZERO OMNI HUB · LOCAL',title:'Un portal para audio, social y creadores',desc:'Funciones locales inspiradas en audio, feeds, vídeo, shorts, directos, chat y comunidades.',language:'IDIOMA',input:'Escribe un texto para publicar, crear o compartir...',tools:'40 HERRAMIENTAS NUEVAS',audio:'AUDIO CLOUD',library:'BIBLIOTECA LOCAL',use:'USAR',queue:'COLA',local:'SIN CONEXIÓN · PERFIL LOCAL',export:'EXPORTAR HUB'},
            fr:{kicker:'ZERO OMNI HUB · LOCAL',title:'Un portail pour audio, social et créateurs',desc:'Des fonctions locales inspirées de l’audio, des feeds, vidéos, shorts, lives, chats et communautés.',language:'LANGUE',input:'Écris un texte à publier, créer ou partager...',tools:'40 NOUVEAUX OUTILS',audio:'AUDIO CLOUD',library:'BIBLIOTHÈQUE LOCALE',use:'UTILISER',queue:'FILE',local:'HORS LIGNE · PROFIL LOCAL',export:'EXPORTER LE HUB'},
            de:{kicker:'ZERO OMNI HUB · LOKAL',title:'Ein Portal für Audio, Social und Creator',desc:'Lokale Funktionen für Audio, Feeds, Videos, Shorts, Live, Chat und Communities.',language:'SPRACHE',input:'Text zum Veröffentlichen oder Teilen schreiben...',tools:'40 NEUE TOOLS',audio:'AUDIO CLOUD',library:'LOKALE BIBLIOTHEK',use:'NUTZEN',queue:'WARTESCHLANGE',local:'OFFLINE · LOKALES PROFIL',export:'HUB EXPORTIEREN'},
            pt:{kicker:'ZERO OMNI HUB · LOCAL',title:'Um portal para áudio, social e criadores',desc:'Recursos locais para áudio, feeds, vídeos, shorts, lives, chat e comunidades.',language:'IDIOMA',input:'Escreva algo para publicar, criar ou partilhar...',tools:'40 NOVAS FERRAMENTAS',audio:'AUDIO CLOUD',library:'BIBLIOTECA LOCAL',use:'USAR',queue:'FILA',local:'OFFLINE · PERFIL LOCAL',export:'EXPORTAR HUB'},
            ru:{kicker:'ZERO OMNI HUB · ЛОКАЛЬНО',title:'Один портал для аудио, соцсетей и авторов',desc:'Локальные функции для аудио, ленты, видео, коротких роликов, эфиров, чата и сообществ.',language:'ЯЗЫК',input:'Текст для публикации или обмена...',tools:'40 НОВЫХ ИНСТРУМЕНТОВ',audio:'AUDIO CLOUD',library:'ЛОКАЛЬНАЯ БИБЛИОТЕКА',use:'ИСПОЛЬЗОВАТЬ',queue:'ОЧЕРЕДЬ',local:'ОФЛАЙН · ЛОКАЛЬНЫЙ ПРОФИЛЬ',export:'ЭКСПОРТ HUB'},
            zh:{kicker:'ZERO OMNI HUB · 本地',title:'音频、社交和创作者的一体化门户',desc:'本地音频、动态、视频、短片、直播、聊天和社区功能。不会连接外部账户。',language:'语言',input:'输入要发布、创建或分享的内容...',tools:'40 个新工具',audio:'音频云',library:'本地库',use:'使用',queue:'队列',local:'离线 · 本地资料',export:'导出中心'},
            ja:{kicker:'ZERO OMNI HUB · ローカル',title:'音楽・ソーシャル・クリエイターを一つに',desc:'音声、フィード、動画、ショート、ライブ、チャット、コミュニティをローカルで楽しめます。',language:'言語',input:'公開・作成・共有する文章...',tools:'40個の新機能',audio:'オーディオクラウド',library:'ローカルライブラリ',use:'使う',queue:'キュー',local:'オフライン · ローカルプロフィール',export:'ハブを出力'},
            ko:{kicker:'ZERO OMNI HUB · 로컬',title:'오디오, 소셜, 크리에이터를 하나의 포털로',desc:'오디오, 피드, 동영상, 쇼츠, 라이브, 채팅과 커뮤니티를 로컬에서 사용합니다.',language:'언어',input:'게시하거나 공유할 내용을 입력하세요...',tools:'새 도구 40개',audio:'오디오 클라우드',library:'로컬 라이브러리',use:'사용',queue:'대기열',local:'오프라인 · 로컬 프로필',export:'허브 내보내기'},
            ar:{kicker:'ZERO OMNI HUB · محلي',title:'بوابة واحدة للصوت والتواصل والمبدعين',desc:'ميزات محلية للصوت والمنشورات والفيديو والمقاطع القصيرة والبث والدردشة والمجتمعات.',language:'اللغة',input:'اكتب نصاً للنشر أو الإنشاء أو المشاركة...',tools:'40 أداة جديدة',audio:'سحابة الصوت',library:'المكتبة المحلية',use:'استخدم',queue:'قائمة الانتظار',local:'دون اتصال · ملف محلي',export:'تصدير المركز'},
            hi:{kicker:'ZERO OMNI HUB · स्थानीय',title:'ऑडियो, सोशल और क्रिएटर के लिए एक पोर्टल',desc:'ऑडियो, फ़ीड, वीडियो, शॉर्ट्स, लाइव, चैट और कम्युनिटी की स्थानीय सुविधाएँ।',language:'भाषा',input:'प्रकाशित या साझा करने के लिए लिखें...',tools:'40 नए टूल',audio:'ऑडियो क्लाउड',library:'स्थानीय लाइब्रेरी',use:'उपयोग',queue:'कतार',local:'ऑफ़लाइन · स्थानीय प्रोफ़ाइल',export:'हब निर्यात'},
            tr:{kicker:'ZERO OMNI HUB · YEREL',title:'Ses, sosyal ve üreticiler için tek portal',desc:'Ses, akış, video, kısa video, canlı yayın, sohbet ve topluluklar için yerel özellikler.',language:'DİL',input:'Yayınlamak veya paylaşmak için yaz...',tools:'40 YENİ ARAÇ',audio:'SES BULUTU',library:'YEREL KÜTÜPHANE',use:'KULLAN',queue:'SIRA',local:'ÇEVRİMDIŞI · YEREL PROFİL',export:'HUB DIŞA AKTAR'}
          };
          return (dict[portalLanguage()] && dict[portalLanguage()][key]) || dict.en[key] || key;
        }
        const OMNI_TOOL_DEFS = [
          ['audio_play_queue','Audio play queue','Play the selected arena track next.','SoundCloud style'], ['audio_like_track','Audio like','Like a local soundtrack track.','SoundCloud style'], ['audio_repost_track','Audio repost','Share a track as a live pulse.','SoundCloud style'], ['audio_save_track','Audio save','Save a track to your local library.','SoundCloud style'], ['audio_mix_mode','Audio mix mode','Toggle a personal queue mode.','SoundCloud style'], ['audio_next_track','Next track','Pick the next soundtrack without playing in the portal.','SoundCloud style'], ['audio_share_track','Share audio','Create a local recommendation post.','SoundCloud style'],
          ['feed_publish','Publish feed','Publish a local feed update.','Facebook style'], ['feed_react','React to feed','React to the featured feed post.','Facebook style'], ['feed_comment','Comment feed','Add the hub text to a featured post.','Facebook style'], ['feed_share','Share feed','Share the featured post locally.','Facebook style'], ['feed_save','Save feed','Bookmark the featured post.','Facebook style'],
          ['story_publish','Publish story','Create a 24-hour style local story.','Facebook style'], ['story_react','React to story','Send a pulse to the newest story.','Facebook style'], ['reel_publish','Publish reel','Create a short-form local reel.','TikTok style'], ['reel_like','Like reel','Like the newest short video.','TikTok style'], ['reel_remix','Remix reel','Create a local remix response.','TikTok style'],
          ['video_subscribe','Subscribe channel','Follow the featured video channel.','YouTube style'], ['video_watchlist','Watchlist','Save a video signal for later.','YouTube style'], ['video_watch_later','Watch later','Add the featured short to watch later.','YouTube style'],
          ['live_post','Live post','Send a real-time style pulse to the local hub.','X / Twitter style'], ['live_repost','Repost live','Repost the newest live signal.','X / Twitter style'], ['live_bookmark','Bookmark live','Save the newest live signal.','X / Twitter style'],
          ['chat_start','Start room','Open a local chat room with NovaKid.','Messenger style'], ['chat_reaction','Chat reaction','Send a friendly reaction to the active room.','Messenger style'], ['group_create','Create group','Create a community using the hub text.','Facebook style'], ['group_join','Join group','Join the first recommended community.','Facebook style'], ['group_poll','Create poll','Create a local community poll.','Community style'],
          ['page_follow','Follow page','Follow the featured creator page.','Creator style'], ['creator_profile','Creator mode','Toggle creator mode on your local profile.','Creator style'], ['pro_headline','Update headline','Set your professional headline from the hub text.','LinkedIn style'], ['pro_opportunity','Save opportunity','Save the featured opportunity.','LinkedIn style'],
          ['event_create','Create event','Create a local event in the portal.','Community style'], ['event_rsvp','RSVP event','Reserve a place at the newest local event.','Community style'], ['notifications_center','Notification center','Open the social notifications view.','Portal utility'], ['search_everywhere','Search everywhere','Search the unified social graph.','Portal utility'], ['theme_switcher','Theme switcher','Cycle the portal accent theme.','Portal utility'], ['privacy_local','Privacy lock','Toggle local-only privacy mode.','Portal utility'], ['language_picker','Language cycle','Cycle through the world language list.','Portal utility'], ['export_hub','Export hub','Prepare a portable local profile backup.','Portal utility']
        ];
        function omniTextInput() { const el = document.getElementById('omni-input'); return String(el && el.value || '').trim().slice(0, 500); }
        function omniCurrentTrack() { const h = socialHubState(); const id = h.media.currentTrack || S.musicTrack || 'auto'; return MUSIC_TRACKS.find((t) => t.id === id) || MUSIC_TRACKS[0]; }
        function omniUseTrack(id) { const h = socialHubState(); h.media.currentTrack = id; setSetting('musicTrack', id); socialHubSave(); notify('♪ ' + (MUSIC_TRACKS.find((t) => t.id === id) || MUSIC_TRACKS[0]).name + ' selezionata per la prossima arena', '#b388ff'); renderPortal(); }
        function omniTrack(id) { const h = socialHubState(); h.media.toolUsage[id] = (h.media.toolUsage[id] || 0) + 1; return h.media; }
        function portalToolAction(id) {
          const h = socialHubState(); const m = h.media; const text = omniTextInput() || 'Un nuovo segnale nella mia orbita Zero.'; const firstPost = socialPosts()[0]; const firstReel = h.reels[0]; const firstX = h.xPosts[0]; const firstChannel = h.channels[0]; const firstPage = h.pages[0];
          omniTrack(id);
          switch (id) {
            case 'audio_play_queue': m.queue.unshift(omniCurrentTrack().id); m.queue = m.queue.slice(0, 8); notify('♪ Aggiunta alla coda arena', '#b388ff'); break;
            case 'audio_like_track': m.audioLikes[omniCurrentTrack().id] = !m.audioLikes[omniCurrentTrack().id]; notify(m.audioLikes[omniCurrentTrack().id] ? 'Traccia apprezzata' : 'Like rimosso', '#b388ff'); break;
            case 'audio_repost_track': socialHubPublish('x', '♪ Consiglio ' + omniCurrentTrack().name + ' alla mia orbita.'); break;
            case 'audio_save_track': m.bookmarks['audio:' + omniCurrentTrack().id] = true; notify('Traccia salvata nella libreria locale', '#00ffa2'); break;
            case 'audio_mix_mode': m.mixMode = !m.mixMode; notify('Mix mode ' + (m.mixMode ? 'ON' : 'OFF'), '#00f0ff'); break;
            case 'audio_next_track': nextMusicTrack(); m.currentTrack = S.musicTrack; notify('Prossima traccia: ' + currentTrackName(), '#b388ff'); break;
            case 'audio_share_track': socialHubPublish('x', '♪ Sto ascoltando ' + omniCurrentTrack().name + ' su Zero The Legend.'); break;
            case 'feed_publish': socialPublish(text); return;
            case 'feed_react': if (firstPost) socialToggleLike(firstPost.id); return;
            case 'feed_comment': if (firstPost) socialAddComment(firstPost.id, text); return;
            case 'feed_share': if (firstPost) socialShare(firstPost.id); return;
            case 'feed_save': if (firstPost) { h.saved['post:' + firstPost.id] = !h.saved['post:' + firstPost.id]; notify(h.saved['post:' + firstPost.id] ? 'Post salvato' : 'Post rimosso', '#7fd4ff'); } break;
            case 'story_publish': socialHubPublish('story', text); return;
            case 'story_react': if (h.stories[0]) { h.storyReactions = h.storyReactions || {}; h.storyReactions[h.stories[0].id] = (h.storyReactions[h.stories[0].id] || 0) + 1; notify('Reazione inviata alla storia', '#ff2d95'); } break;
            case 'reel_publish': socialHubPublish('reel', text); return;
            case 'reel_like': if (firstReel) firstReel.likes = (firstReel.likes || 0) + 1; notify('Like al Reel inviato', '#ff2d95'); break;
            case 'reel_remix': socialHubPublish('reel', 'Remix locale: ' + text); return;
            case 'video_subscribe': if (firstChannel) socialHubToggleFollow(firstChannel.id); break;
            case 'video_watchlist': if (firstReel) { m.watchlist['video:' + firstReel.id] = true; notify('Video aggiunto alla watchlist', '#00f0ff'); } break;
            case 'video_watch_later': if (firstReel) { m.watchlist['later:' + firstReel.id] = true; notify('Video salvato per dopo', '#00f0ff'); } break;
            case 'live_post': socialHubPublish('x', text); return;
            case 'live_repost': if (firstX) { firstX.reposts = (firstX.reposts || 0) + 1; notify('Segnale ripubblicato', '#7fd4ff'); } break;
            case 'live_bookmark': if (firstX) { m.bookmarks['live:' + firstX.id] = true; notify('Segnale live salvato', '#7fd4ff'); } break;
            case 'chat_start': h.activePeer = 'NovaKid'; h.view = 'chat'; notify('Room NovaKid aperta', '#00f0ff'); break;
            case 'chat_reaction': socialHubSendMessage(h.activePeer || 'NovaKid', '⚡ ' + text); break;
            case 'group_create': socialHubCreateGroup(text.slice(0, 32)); return;
            case 'group_join': if (h.groups[0]) socialHubJoinGroup(h.groups[0].id); break;
            case 'group_poll': h.polls.unshift({ id:'poll_' + Date.now(), question:text, votes:{yes:0,no:0}, author:profile.name || 'Ospite' }); notify('Sondaggio creato nella community', '#ffe600'); break;
            case 'page_follow': if (firstPage) socialHubToggleFollow(firstPage.id); break;
            case 'creator_profile': h.creatorMode = !h.creatorMode; notify('Creator mode ' + (h.creatorMode ? 'ON' : 'OFF'), '#ffe600'); break;
            case 'pro_headline': h.professional.headline = text.slice(0, 80); notify('Headline professionale aggiornata', '#b388ff'); break;
            case 'pro_opportunity': if (h.professional.jobs[0]) { h.professional.applied = h.professional.applied || {}; h.professional.applied[h.professional.jobs[0].id] = true; notify('Opportunità salvata', '#b388ff'); } break;
            case 'event_create': h.events.unshift({ id:'event_' + Date.now(), title:text.slice(0, 80), host:profile.name || 'Ospite', rsvps:0, created:Date.now() }); h.events = h.events.slice(0, 12); notify('Evento creato nel portale', '#00ffa2'); break;
            case 'event_rsvp': if (h.events[0]) { h.events[0].rsvps = (h.events[0].rsvps || 0) + 1; notify('Partecipazione confermata', '#00ffa2'); } break;
            case 'notifications_center': h.view = 'home'; notify('Centro notifiche sincronizzato', '#7fd4ff'); break;
            case 'search_everywhere': socialSearch = text.slice(0, 80); h.view = 'feed'; notify('Ricerca unificata: ' + socialSearch, '#00f0ff'); break;
            case 'theme_switcher': { const i = THEMES.findIndex((x) => x.id === (S.theme || 'neon')); setSetting('theme', THEMES[(i + 1) % THEMES.length].id); notify('Tema: ' + THEMES[(i + 1) % THEMES.length].name, THEMES[(i + 1) % THEMES.length].a); break; }
            case 'privacy_local': m.privacyLocal = !m.privacyLocal; notify(m.privacyLocal ? 'Privacy locale attiva' : 'Privacy locale disattivata', '#00ffa2'); break;
            case 'language_picker': { const i = WORLD_LANGUAGES.findIndex((x) => x[0] === portalLanguage()); const next = WORLD_LANGUAGES[(i + 1) % WORLD_LANGUAGES.length][0]; setSetting('language', next); notify('Language: ' + WORLD_LANGUAGES[(i + 1) % WORLD_LANGUAGES.length][1], '#00f0ff'); break; }
            case 'export_hub': { const box = document.getElementById('omni-output'); if (box) box.value = exportProfileCode(); notify('Backup locale pronto', '#00ffa2'); break; }
            default: notify('Funzione non disponibile', '#ff5c7a');
          }
          socialHubSave(); renderPortal();
        }
        function omniToolMarkup(def) { return '<button class="omni-tool" data-omni-tool="' + def[0] + '"><strong>' + escapeHtml(def[1]) + '</strong><span>' + escapeHtml(def[2]) + '</span><em>' + escapeHtml(def[3]) + ' · TAP</em></button>'; }
        function renderPtOmni() {
          const h = socialHubState(); const m = h.media; const tr = omniCurrentTrack(); const L = (k) => portalLocale(k);
          const trackRows = MUSIC_TRACKS.map((t) => '<div class="omni-track"><div class="omni-track-art">♪</div><div class="omni-track-info"><b>' + escapeHtml(t.name) + '</b><span>' + (t.asset ? 'Arena asset · cached' : 'Procedural local track') + (m.audioLikes[t.id] ? ' · ♥' : '') + '</span></div><button class="pt-btn small' + (tr.id === t.id ? ' primary' : '') + '" data-omni-track="' + t.id + '">' + (tr.id === t.id ? '✓ ' + L('use') : L('use')) + '</button></div>').join('');
          const langOptions = WORLD_LANGUAGES.map((x) => '<option value="' + x[0] + '"' + (x[0] === portalLanguage() ? ' selected' : '') + '>' + x[1] + '</option>').join('');
          const tools = OMNI_TOOL_DEFS.map(omniToolMarkup).join('');
          ptBodyEl.innerHTML = '<div class="omni-shell"><section class="omni-hero"><div class="omni-kicker">' + L('kicker') + '</div><h1>' + L('title') + '</h1><p>' + L('desc') + '</p><div class="omni-controls"><select id="omni-language" aria-label="' + L('language') + '">' + langOptions + '</select><input class="omni-search" id="omni-search" maxlength="80" placeholder="⌕ ' + L('tools') + '..." /><span class="omni-badge">' + L('local') + '</span></div><textarea class="omni-input" id="omni-input" maxlength="500" placeholder="' + L('input') + '"></textarea></section><div class="omni-tabs"><button class="omni-tab on" data-omni-mode="overview">◈ OVERVIEW</button><button class="omni-tab" data-omni-mode="audio">♪ ' + L('audio') + '</button><button class="omni-tab" data-omni-mode="tools">✦ ' + L('tools') + '</button></div><div class="omni-grid"><main><section class="omni-card"><h3>' + L('audio') + ' · ' + escapeHtml(tr.name) + '</h3>' + trackRows + '<div class="omni-controls"><button class="pt-btn small primary" data-omni-tool="audio_play_queue">' + L('queue') + '</button><button class="pt-btn small" data-omni-tool="audio_next_track">N</button><button class="pt-btn small" data-omni-tool="audio_mix_mode">MIX ' + (m.mixMode ? 'ON' : 'OFF') + '</button></div></section><section class="omni-card"><h3>' + L('tools') + '</h3><div class="omni-tool-grid" id="omni-tool-grid">' + tools + '</div></section></main><aside><section class="omni-card"><h3>' + L('library') + '</h3><div class="omni-stat-grid"><div class="omni-stat">Queue<b>' + m.queue.length + '</b></div><div class="omni-stat">Tools<b>' + Object.keys(m.toolUsage).length + '/40</b></div><div class="omni-stat">Saved<b>' + Object.keys(m.bookmarks).length + '</b></div><div class="omni-stat">Events<b>' + h.events.length + '</b></div><div class="omni-stat">Locale<b>' + portalLanguage().toUpperCase() + '</b></div><div class="omni-stat">Privacy<b>' + (m.privacyLocal ? 'LOCAL' : 'OPEN') + '</b></div></div></section><section class="omni-card"><h3>' + L('export') + '</h3><textarea class="omni-output" id="omni-output" placeholder="' + L('local') + '"></textarea><button class="pt-btn small primary" data-omni-tool="export_hub">' + L('export') + '</button></section><section class="omni-card"><p>' + L('local') + '. SoundCloud, Facebook, YouTube, X/Twitter and TikTok are represented as offline-first local modes; no external API, login, upload or tracking request is made.</p></section></aside></div></div>';
          const lang = document.getElementById('omni-language'); if (lang) lang.addEventListener('change', () => { setSetting('language', lang.value); renderPortal(); });
          ptBodyEl.querySelectorAll('[data-omni-track]').forEach((b) => b.addEventListener('click', (e) => { e.preventDefault(); omniUseTrack(b.dataset.omniTrack); }));
          ptBodyEl.querySelectorAll('[data-omni-tool]').forEach((b) => b.addEventListener('click', (e) => { e.preventDefault(); portalToolAction(b.dataset.omniTool); }));
          const search = document.getElementById('omni-search'); const grid = document.getElementById('omni-tool-grid');
          if (search && grid) search.addEventListener('input', () => { const q = search.value.toLowerCase(); grid.querySelectorAll('.omni-tool').forEach((b) => { b.style.display = b.textContent.toLowerCase().includes(q) ? '' : 'none'; }); });
        }

        // ============================================================
        // FACEBOOK-STYLE COMMUNITY FEED
        // A local, touch-first social shell for the Zero Arcade portal.
        // Posts, likes and comments stay in the player profile; no network
        // requests or external social accounts are used.
        // ============================================================
        const SOCIAL_SEED_POSTS = [
          { id: 'seed_zero', author: 'Zero Arcade', role: 'official', text: 'Benvenuti nel nuovo feed dell\'arena. Pubblica il tuo record, sfida gli amici e resta aggiornato sulle missioni di oggi.', age: 18 * 60000, likes: 42, comments: [{ n: 'NovaKid', t: 'Finalmente un feed per i campioni!' }] },
          { id: 'seed_patch', author: 'Zero The Legend', role: 'legend', text: 'PATCH LIVE · Nuovi power-up, mini-giochi e sfide Legend Lab disponibili nel portale.', age: 2 * 3600000, likes: 128, comments: [{ n: 'BlobQueen', t: 'Il reflex challenge è velocissimo ⚡' }, { n: 'MrSplit', t: 'Ci vediamo in top 3.' }] },
          { id: 'seed_tip', author: 'Arena Tips', role: 'helper', text: 'TIP: usa SHIFT per il dash, conserva G per una fuga impossibile e guarda la minimappa prima di fare split.', age: 7 * 3600000, likes: 76, comments: [] },
        ];

        function socialInitial(name) {
          const clean = String(name || 'Z').trim();
          return (clean.charAt(0) || 'Z').toUpperCase();
        }
        function socialAvatar(name, cls, style) {
          const extra = cls ? ' ' + cls : '';
          const inline = style || 'background:radial-gradient(circle at 35% 25%,#fff,#00f0ff 35%,#7b2cff)';
          return '<div class="fb-avatar' + extra + '" style="' + inline + '">' + escapeHtml(socialInitial(name)) + '</div>';
        }
        function socialTime(ts) {
          const mins = Math.max(1, Math.floor((Date.now() - ts) / 60000));
          if (mins < 60) return mins + ' min fa';
          if (mins < 1440) return Math.floor(mins / 60) + ' h fa';
          return Math.floor(mins / 1440) + ' g fa';
        }
        function socialPosts() {
          const local = (profile.posts || []).map((p) => Object.assign({}, p, {
            author: profile.loggedIn ? profile.name : 'Ospite',
            role: 'user',
            created: p.created || Date.now(),
            likes: Number(p.likes) || 0,
            comments: Array.isArray(p.comments) ? p.comments : [],
          }));
          const seeded = SOCIAL_SEED_POSTS.map((p) => Object.assign({}, p, { created: Date.now() - p.age }));
          const all = seeded.concat(local);
          const q = String(socialSearch || '').trim().toLowerCase();
          return all.filter((p) => !q || (p.author + ' ' + p.text).toLowerCase().indexOf(q) >= 0)
            .sort((a, b) => b.created - a.created);
        }
        function socialLikeCount(post) {
          const liked = !!(profile.socialLikes && profile.socialLikes[post.id]);
          return Math.max(0, (Number(post.likes) || 0) + (liked ? 1 : 0));
        }
        function socialComments(post) {
          const base = Array.isArray(post.comments) ? post.comments.slice() : [];
          const extra = profile.socialComments && Array.isArray(profile.socialComments[post.id]) ? profile.socialComments[post.id] : [];
          return base.concat(extra);
        }
        function socialPostCard(post) {
          const liked = !!(profile.socialLikes && profile.socialLikes[post.id]);
          const comments = socialComments(post);
          const badge = post.role === 'official' ? '<span class="fb-post-badge">UFFICIALE</span>'
            : post.role === 'legend' ? '<span class="fb-post-badge">LEGEND</span>'
            : post.role === 'helper' ? '<span class="fb-post-badge">GUIDA</span>' : '';
          const commentHtml = comments.slice(-3).map((c) => '<div class="fb-comment">' + socialAvatar(c.n || 'Z', 'tiny') + '<span><b>' + escapeHtml(c.n || 'Giocatore') + '</b> ' + escapeHtml(c.t || '') + '</span></div>').join('');
          return '<article class="yt-video-card fb-post" data-social-post="' + escapeHtml(post.id) + '">' +
            '<div class="yt-video-thumb"><span class="yt-play">&#9654;</span><small>ZEROARCADE &#183; LIVE FEED</small></div>' +
            '<div class="fb-post-head">' + socialAvatar(post.author, 'tiny', post.role === 'legend' ? 'background:radial-gradient(circle at 35% 25%,#fff,#ffe600 35%,#ff2d6b)' : '') +
            '<div class="fb-post-head-info"><div class="fb-post-author">' + escapeHtml(post.author) + badge + '</div><span class="fb-post-time">' + socialTime(post.created) + ' · 🌐 Feed locale</span></div>' +
            '<button class="fb-mini-btn" data-social-more="' + escapeHtml(post.id) + '" aria-label="Altre opzioni">•••</button></div>' +
            '<div class="fb-post-text">' + escapeHtml(post.text || '') + '</div>' +
            '<div class="fb-post-stats"><span>💠 ' + socialLikeCount(post) + ' reazioni</span><span>' + comments.length + ' commenti</span></div>' +
            '<div class="fb-post-actions"><button class="fb-action-btn' + (liked ? ' active' : '') + '" data-social-like="' + escapeHtml(post.id) + '">' + (liked ? '💙 Mi piace' : '♡ Mi piace') + '</button>' +
            '<button class="fb-action-btn" data-social-focus="' + escapeHtml(post.id) + '">💬 Commenta</button>' +
            '<button class="fb-action-btn" data-social-share="' + escapeHtml(post.id) + '">↗ Condividi</button></div>' +
            '<div class="fb-comments">' + commentHtml + '<form class="fb-comment-form" data-social-comment-form="' + escapeHtml(post.id) + '" autocomplete="off"><input maxlength="180" placeholder="Scrivi un commento..." aria-label="Scrivi un commento" /><button type="submit">➤</button></form></div>' +
            '</article>';
        }
        function socialPublish(text) {
          const value = String(text || '').trim().slice(0, 500);
          if (!value) return;
          if (!Array.isArray(profile.posts)) profile.posts = [];
          profile.posts.unshift({ id: 'local_' + Date.now() + '_' + Math.floor(Math.random() * 9999), text: value, created: Date.now(), likes: 0, comments: [] });
          addProfileXp(8);
          addPassXp(4);
          unlock('post', 'Primo post pubblicato nel feed!');
          queueSave();
          notify('Post pubblicato nel feed', '#00ffa2');
          renderPortal();
        }
        function socialToggleLike(id) {
          if (!profile.socialLikes || typeof profile.socialLikes !== 'object') profile.socialLikes = {};
          if (profile.socialLikes[id]) delete profile.socialLikes[id];
          else profile.socialLikes[id] = true;
          queueSave();
          renderPortal();
        }
        function socialAddComment(id, text) {
          const value = String(text || '').trim().slice(0, 180);
          if (!value) return;
          if (!profile.socialComments || typeof profile.socialComments !== 'object') profile.socialComments = {};
          if (!Array.isArray(profile.socialComments[id])) profile.socialComments[id] = [];
          profile.socialComments[id].push({ n: profile.loggedIn ? profile.name : PT_T('guest'), t: value });
          if (profile.socialComments[id].length > 20) profile.socialComments[id].shift();
          queueSave();
          notify('Comment added', '#7fd4ff');
          renderPortal();
        }
        function socialShare(id) {
          const source = socialPosts().find((p) => p.id === id);
          if (!source) return;
          socialPublish('↗ Ho condiviso un post di ' + source.author + ':\n\n' + source.text);
          addCoins(5, 'Condivisione nel feed');
        }
        function socialGreet(name) {
          addChatMsg('', 'Hai salutato ' + name + ' 👋', 'sys');
          notify('Saluto inviato a ' + name, '#7fd4ff');
        }
        function socialBind(scope) {
          if (!scope) return;
          scope.querySelectorAll('[data-social-like]').forEach((b) => b.addEventListener('click', (e) => { e.preventDefault(); socialToggleLike(b.dataset.socialLike); }));
          scope.querySelectorAll('[data-social-share]').forEach((b) => b.addEventListener('click', (e) => { e.preventDefault(); socialShare(b.dataset.socialShare); }));
          scope.querySelectorAll('[data-social-focus]').forEach((b) => b.addEventListener('click', (e) => { e.preventDefault(); const form = scope.querySelector('[data-social-comment-form="' + b.dataset.socialFocus + '"] input'); if (form) form.focus(); }));
          scope.querySelectorAll('[data-social-comment-form]').forEach((form) => form.addEventListener('submit', (e) => { e.preventDefault(); const input = form.querySelector('input'); socialAddComment(form.dataset.socialCommentForm, input ? input.value : ''); }));
          scope.querySelectorAll('[data-social-more]').forEach((b) => b.addEventListener('click', (e) => { e.preventDefault(); notify('Le opzioni del post sono disponibili nel tuo feed locale', '#7fd4ff'); }));
          scope.querySelectorAll('[data-social-greet]').forEach((b) => b.addEventListener('click', (e) => { e.preventDefault(); socialGreet(b.dataset.socialGreet); }));
          const composer = scope.querySelector('[data-social-composer]');
          if (composer) composer.addEventListener('submit', (e) => { e.preventDefault(); const input = composer.querySelector('textarea'); socialPublish(input ? input.value : ''); });
        }
        function renderYouTubeLayout(section) {
          ensureSocial();
          const nm = profile.loggedIn ? profile.name : PT_T('guest');
          const friends = (profile.friends || []).slice(0, 6);
          const posts = socialPosts();
          let h = '<div class="yt-layout fb-layout">';
          h += '<aside class="yt-sidebar fb-left-rail"><div class="yt-channel-card fb-profile-card">' + socialAvatar(nm, 'large', avatarStyle()) + '<div class="fb-profile-name">' + escapeHtml(nm) + '</div><div class="fb-profile-meta">' + T('role') + ': ' + roleLabel() + ' · ' + T('level') + ' ' + (profile.level || 1) + '</div><div class="fb-profile-stats"><div>' + T('bestMass') + '<b>' + Math.round(profile.bestMass || 0) + '</b></div><div>' + PT_T('friends') + '<b>' + friends.length + '</b></div></div></div>';
          h += '<nav class="fb-side-menu" aria-label="Navigazione social">' +
            '<button data-go="social"><span class="fb-side-icon">⌂</span>' + PT_T('social') + '</button>' +
            '<button data-go="social"><span class="fb-side-icon">👥</span>' + PT_T('friends') + '</button>' +
            '<button data-go="games"><span class="fb-side-icon">🎮</span>' + PT_T('games') + '</button>' +
            '<button data-go="quests"><span class="fb-side-icon">🎯</span>' + PT_T('quests') + '</button>' +
            '<button data-go="dash"><span class="fb-side-icon">📊</span>' + PT_T('dash') + '</button>' +
            '<button data-go="settings" data-social-settings="1"><span class="fb-side-icon">⚙</span>' + T('settings') + '</button>' +
            '<button data-go="help"><span class="fb-side-icon">?</span>' + PT_T('help') + '</button></nav></aside>';
          h += '<main class="yt-main fb-feed"><div class="yt-section-heading fb-feed-heading"><div><h2 style="margin:0;font-family:Orbitron,sans-serif;color:#fff">' + (section === 'home' ? PT_T('home') : PT_T('social')) + '</h2><span>' + PT_T('community') + '</span></div><span>' + posts.length + ' ' + PT_T('postCount') + '</span></div>';
          h += '<div class="yt-shorts-shelf fb-stories"><button class="yt-short-card fb-story" data-go="dash">' + socialAvatar(nm, '') + '<b>' + PT_T('yourStory') + '</b></button>' + friends.slice(0, 3).map((f) => '<button class="yt-short-card fb-story" data-social-greet="' + escapeHtml(f.n) + '">' + socialAvatar(f.n, '') + '<b>' + escapeHtml(f.n) + '</b></button>').join('') + '</div>';
          h += '<form class="yt-upload-box fb-composer" data-social-composer autocomplete="off"><div class="fb-composer-top">' + socialAvatar(nm, 'tiny', avatarStyle()) + '<textarea maxlength="500" placeholder="' + PT_T('thinking') + ', ' + escapeHtml(nm) + '?" aria-label="' + PT_T('publish') + '"></textarea></div><div class="fb-composer-bottom"><span class="fb-composer-hint">🌐 ' + PT_T('local') + '</span><button class="fb-primary-btn" type="submit">' + PT_T('publish') + '</button></div></form>';
          h += posts.length ? posts.map(socialPostCard).join('') : '<div class="fb-post fb-empty">Nessun post trovato. Prova una ricerca diversa o pubblica il primo aggiornamento.</div>';
          h += '</main>';
          h += '<aside class="yt-trending fb-right-rail"><div class="fb-card"><h3>' + PT_T('trending') + '</h3>' + friends.map((f) => '<div class="fb-contact"><span class="fb-online ' + (f.on ? '' : 'fb-offline') + '"></span>' + socialAvatar(f.n, 'tiny') + '<div class="fb-contact-info"><b>' + escapeHtml(f.n) + '</b><span>' + T('level') + ' ' + f.lv + ' · ' + (f.on ? PT_T('online') : PT_T('offline')) + '</span></div></div>').join('') + '</div>';
          h += '<div class="fb-card"><h3>' + PT_T('suggested') + '</h3>' + ['PlasmaPop', 'GhostOrbit', 'CellDoc'].map((n) => '<div class="fb-suggestion">' + socialAvatar(n, 'tiny') + '<div class="fb-suggestion-info"><b>' + n + '</b><span>Giocatore dell\'arena</span></div><button class="fb-mini-btn" data-social-greet="' + n + '">' + PT_T('greet') + '</button></div>').join('') + '</div>';
          h += '<div class="fb-card"><h3>' + PT_T('trending') + '</h3><div class="fb-trend"><span>#GrowthOrbit</span><b>1.2k</b></div><div class="fb-trend"><span>#ZeroPass</span><b>842</b></div><div class="fb-trend"><span>#LegendLab</span><b>506</b></div><div class="fb-trend"><span>#DashMeta</span><b>311</b></div></div>';
          h += '</aside></div>';
          ptBodyEl.innerHTML = h;
          socialBind(ptBodyEl);
          ptBodyEl.querySelectorAll('[data-social-settings]').forEach((b) => b.addEventListener('click', (e) => { e.preventDefault(); openUserMenu(); }));
          legendBindTilt(ptBodyEl);
        }
        const PT_RENDER = {
          home: () => renderYouTubeLayout('home'), games: renderPtGames, arcade: renderPtArcade, crash: renderPtCrash,
          shop: renderPtShop, wallet: renderPtWallet, quests: renderPtQuests, pass: renderPtPass,
          music: renderPtMusic, social: renderPtSocial, omni: renderPtOmni, dash: renderPtDash, ranks: renderPtRanks,
          news: renderPtNews, help: renderPtHelp, admin: renderPtAdmin, legend: legendRenderLab, tutorials: renderPtTutorials,
        };

        // ===== Portal footer chats: local Facebook-style room + staff support =====
        let portalFbChatOpen = false;
        let portalSupportChatOpen = false;

        function portalStaffCanChat() {
          return hasPerm('mod');
        }
        function portalChatTime() {
          return 'Ora';
        }
        function portalChatActor() {
          return profile.loggedIn ? profile.name : PT_T('guest');
        }
        function portalFbPeers(h) {
          const names = [];
          (h.messages || []).forEach((m) => { if (m && m.peer) names.push(m.peer); });
          (profile.friends || []).forEach((f) => { if (f && f.n) names.push(f.n); });
          if (!names.length) names.push('NovaKid');
          return Array.from(new Set(names)).slice(0, 6);
        }
        function portalChatMessageMarkup(message, actor) {
          const from = String(message && (message.from || message.n || 'Zero') || 'Zero');
          return '<div class="portal-chat-message' + (from === actor ? ' me' : '') + '"><b>' + escapeHtml(from) + '</b>' +
            escapeHtml(message && (message.text || message.t || '') || '') + '<small>' + escapeHtml(message && (message.time || 'Ora') || 'Ora') + '</small></div>';
        }
        function portalSendFacebookMessage(peer, text) {
          const value = String(text || '').trim().slice(0, 240);
          if (!value) return;
          socialHubSendMessage(peer || 'NovaKid', value);
          portalFbChatOpen = true;
          renderPortalChatDocks();
        }
        function portalSendSupportMessage(text) {
          if (!portalStaffCanChat()) {
            notify('Chat support riservata a MOD e ADMIN', '#ff5c7a');
            return;
          }
          const value = String(text || '').trim().slice(0, 300);
          if (!value) return;
          const h = socialHubState();
          h.supportMessages.push({
            id: 'support_' + Date.now() + '_' + Math.floor(Math.random() * 9999),
            from: portalChatActor(), role: roleLabel(), text: value, time: portalChatTime()
          });
          h.supportMessages = h.supportMessages.slice(-40);
          portalSupportChatOpen = true;
          socialHubSave();
          notify('Messaggio inviato nella chat staff', '#b388ff');
          renderPortalChatDocks();
        }
        function renderPortalChatDocks() {
          const fbDock = document.getElementById('portal-fb-chat-dock');
          const fbPanel = document.getElementById('portal-fb-chat-panel');
          const fbToggle = document.getElementById('portal-fb-chat-toggle');
          const supportDock = document.getElementById('portal-support-dock');
          const supportPanel = document.getElementById('portal-support-panel');
          const supportToggle = document.getElementById('portal-support-toggle');
          if (!fbDock || !fbPanel || !fbToggle) return;
          const h = socialHubState();
          const actor = portalChatActor();
          const peers = portalFbPeers(h);
          const peer = h.activePeer && peers.indexOf(h.activePeer) >= 0 ? h.activePeer : peers[0];
          h.activePeer = peer;
          const chat = (h.messages || []).find((m) => m.peer === peer);
          const thread = chat && Array.isArray(chat.messages) ? chat.messages.slice(-30) : [];
          fbPanel.innerHTML = '<div class="portal-chat-head"><b>MESSENGER · ' + escapeHtml(peer) + '</b><span>● locale</span></div>' +
            '<div class="portal-chat-peer-list">' + peers.map((p) => '<button type="button" class="portal-chat-peer' + (p === peer ? ' on' : '') + '" data-portal-chat-peer="' + escapeHtml(p) + '">' + escapeHtml(p) + '</button>').join('') + '</div>' +
            '<div class="portal-chat-log">' + (thread.length ? thread.map((m) => portalChatMessageMarkup(m, actor)).join('') : '<div class="portal-chat-empty">Nessun messaggio in questa room.<br>Scrivi al tuo contatto dell’arena.</div>') + '</div>' +
            '<form class="portal-chat-form" id="portal-fb-chat-form" autocomplete="off"><input maxlength="240" placeholder="Scrivi a ' + escapeHtml(peer) + '..." aria-label="Messaggio Facebook chat" /><button type="submit">INVIA</button></form>';
          fbDock.classList.toggle('open', portalFbChatOpen);
          const fbLog = fbPanel.querySelector('.portal-chat-log');
          if (fbLog) fbLog.scrollTop = fbLog.scrollHeight;
          fbToggle.onclick = (e) => { e.preventDefault(); portalFbChatOpen = !portalFbChatOpen; renderPortalChatDocks(); };
          fbPanel.querySelectorAll('[data-portal-chat-peer]').forEach((b) => b.addEventListener('click', (e) => {
            e.preventDefault(); h.activePeer = b.dataset.portalChatPeer; socialHubSave(); renderPortalChatDocks();
          }));
          const fbForm = fbPanel.querySelector('#portal-fb-chat-form');
          if (fbForm) fbForm.addEventListener('submit', (e) => { e.preventDefault(); portalSendFacebookMessage(peer, fbForm.querySelector('input').value); });

          if (!supportDock || !supportPanel || !supportToggle) return;
          const allowed = portalStaffCanChat();
          supportDock.hidden = !allowed;
          supportDock.classList.toggle('open', allowed && portalSupportChatOpen);
          if (!allowed) { portalSupportChatOpen = false; return; }
          const supportMessages = Array.isArray(h.supportMessages) ? h.supportMessages.slice(-30) : [];
          supportPanel.innerHTML = '<div class="portal-chat-head"><b>STAFF SUPPORT ROOM</b><span>MOD + ADMIN</span></div>' +
            '<div class="portal-chat-log">' + (supportMessages.length ? supportMessages.map((m) => portalChatMessageMarkup(m, actor)).join('') : '<div class="portal-chat-empty">Canale supporto pronto.<br>Solo MOD e ADMIN possono scrivere qui.</div>') + '</div>' +
            '<form class="portal-chat-form" id="portal-support-chat-form" autocomplete="off"><input maxlength="300" placeholder="Scrivi al team staff..." aria-label="Messaggio supporto staff" /><button type="submit">INVIA</button></form>';
          const supportLog = supportPanel.querySelector('.portal-chat-log');
          if (supportLog) supportLog.scrollTop = supportLog.scrollHeight;
          supportToggle.onclick = (e) => { e.preventDefault(); if (!portalStaffCanChat()) return; portalSupportChatOpen = !portalSupportChatOpen; renderPortalChatDocks(); };
          const supportForm = supportPanel.querySelector('#portal-support-chat-form');
          if (supportForm) supportForm.addEventListener('submit', (e) => { e.preventDefault(); portalSendSupportMessage(supportForm.querySelector('input').value); });
        }

        function renderPortal() {
          if (!portalEl || !ptNavEl || !ptBodyEl) return;
          renderLanguagePicker();
          showLatestGlobalAnnouncement();
          ptNavEl.innerHTML = '<label class="pt-nav-label" for="pt-page-select">NAVIGAZIONE</label>' +
            '<select class="pt-page-select" id="pt-page-select" aria-label="Navigazione portale">' +
            PAGES.map((p) => '<option value="' + p.id + '"' + (p.id === ptPage ? ' selected' : '') + '>' + p.icon + '  ' + portalLabel(p.id) + '</option>').join('') +
            '</select>';
          const pageSelect = document.getElementById('pt-page-select');
          if (pageSelect) pageSelect.addEventListener('change', () => setPtPage(pageSelect.value));
          updateWalletUi();
          (PT_RENDER[ptPage] || renderPtHome)();
          ptBodyEl.querySelectorAll('[data-go]').forEach((b) =>
            b.addEventListener('click', (e) => { e.preventDefault(); playUiSound('tap'); setPtPage(b.dataset.go); }));
          ptBodyEl.querySelectorAll('[data-play]:not([data-bd])').forEach((b) =>
            b.addEventListener('click', (e) => { e.preventDefault(); launchGame(b.dataset.play); }));
          renderPortalChatDocks();
          // Apply the active locale to every visible portal surface, including
          // legacy cards and social widgets whose templates still contain
          // authored Italian fallback copy.
          translatePortalDom();
        }

        function setPtPage(id) {
          ptPage = id || 'home';
          if (ptPage !== 'arcade') clearMiniTimers();
          if (ptPage !== 'tutorials') tutorialStop();
          renderPortal();
          updateMusicContext();
          if (ptBodyEl) ptBodyEl.scrollTop = 0;
        }

        function openPortal(page) {
          portalOpen = true;
          if (page) ptPage = page;
          if (portalEl) portalEl.classList.add('visible');
          applyTheme();
          renderPortal();
          // One welcome announcement per page session, with a gesture-safe fallback.
          portalWelcomePending = !portalWelcomePlayed;
          updateMusicContext();
          announcePortalWelcome();
        }
        function closePortal() {
          tutorialStop();
          portalOpen = false;
          clearMiniTimers();
          if (portalEl) portalEl.classList.remove('visible');
          updateMusicContext();
          queueSave();
        }
        function togglePortal() {
          if (portalOpen) closePortal();
          else openPortal();
        }
        function launchGame(id) {
          const g = GAMES.find((x) => x.id === id);
          if (g && g.mini) { pushRecent(id); openMini(g.mini); return; }
          if (g && g.legend) { pushRecent(id); legendOpenLab(g.legend); return; }
          if (id === 'zero_crash') { pushRecent(id); setPtPage('crash'); return; }
          if (!g || !g.live) {
            notify('\u{1F512} ' + (g ? g.name : 'Gioco') + ' arriva presto su Zero Arcade', '#7fd4ff');
            playUiSound('err');
            return;
          }
          pushRecent(id);
          if (mode !== 'play') { closePortal(); return; }
          if (gameOver || !playerCells.length) resetGame();
          closePortal();
          initAudio();
          notify('\u25B6 Zero Arcade \u2014 buona caccia!', '#00f0ff');
        }

        function tryReviveWithToken() {
          if (mode !== 'play' || gameOver) return false;
          if (itemCount('extra_life') <= 0) return false;
          profile.inventory.extra_life = itemCount('extra_life') - 1;
          const reviveMass = Math.max(cfg.player.startSize || 20, peakMass * 0.4);
          playerCells = [makePlayerCell(player.x, player.y, reviveMass, 0)];
          syncPlayerAggregate();
          effects.shield = 8;
          godMode.active = true;
          godMode.timer = Math.min(3, cfg.abilities.godDuration || 3);
          notify('\u2764 Vita extra usata (' + itemCount('extra_life') + ' rimaste)', '#ff5c9e');
          shakeScreen(10, 0.3);
          queueSave();
          return true;
        }

        if (ptCloseBtn) ptCloseBtn.addEventListener('click', (e) => { e.preventDefault(); launchGame('growth_orbit'); });
        if (ptMenuToggle) ptMenuToggle.addEventListener('click', (e) => {
          e.preventDefault();
          if (portalEl) portalEl.classList.toggle('yt-nav-collapsed');
        });
        if (ptUserBtn) ptUserBtn.addEventListener('click', (e) => { e.preventDefault(); openUserMenu(); });
        if (ptWalletEl) ptWalletEl.addEventListener('click', (e) => { e.preventDefault(); setPtPage('wallet'); });
        if (portalBtnEl) portalBtnEl.addEventListener('click', (e) => { e.preventDefault(); openPortal(); });
        if (goPortalBtn) goPortalBtn.addEventListener('click', (e) => { e.preventDefault(); openPortal('home'); });
        if (ptSearchFormEl) ptSearchFormEl.addEventListener('submit', (e) => {
          e.preventDefault();
          socialSearch = ptGlobalSearchEl ? ptGlobalSearchEl.value.trim() : '';
          openPortal('social');
        });
        if (ptNotifyBtn) ptNotifyBtn.addEventListener('click', (e) => {
          e.preventDefault();
          openPortal('social');
          if (profile.inbox && profile.inbox.some((m) => !m.claimed)) notify('Hai notifiche e messaggi da leggere', '#7fd4ff');
        });
        if (ptLanguageToggleEl) {
          // Stop the portal shell from treating the toggle as a canvas gesture.
          // Keep click for keyboard activation, while pointerdown gives touch
          // users an immediate, dependable response.
          let togglePointerHandled = false;
          ptLanguageToggleEl.addEventListener('pointerdown', (e) => {
            e.preventDefault();
            e.stopPropagation();
            togglePointerHandled = true;
            toggleLanguagePicker();
            setTimeout(() => { togglePointerHandled = false; }, 0);
          }, { passive: false });
          ptLanguageToggleEl.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            if (togglePointerHandled) return;
            toggleLanguagePicker();
          });
          ptLanguageToggleEl.addEventListener('keydown', (e) => {
            if (e.code !== 'Enter' && e.code !== 'Space') return;
            e.preventDefault();
            toggleLanguagePicker();
          });
        }
        document.addEventListener('click', (e) => {
          if (!ptLanguagePickerEl) return;
          const path = typeof e.composedPath === 'function' ? e.composedPath() : [];
          if (ptLanguagePickerEl.contains(e.target) || path.indexOf(ptLanguagePickerEl) >= 0) return;
          toggleLanguagePicker(false);
        });

        normalizeProfileData();
        legendLoadBrandAsset();
        updateWalletUi();
        // The portal opens as the lobby; music starts only after leaving it for gameplay.
        if (mode === 'play') {
          portalWelcomePending = true;
          openPortal('home');
          showLanguageGate();
        }

        // --- Game Loop ---
        function gameLoop(timestamp) {
          if (!lastTime) lastTime = timestamp;
          let dt = (timestamp - lastTime) / 1000;
          lastTime = timestamp;
          if (dt > 0.11) dt = 0.11;
          if (dt > 0.0005) fpsSmooth += (1 / dt - fpsSmooth) * 0.08;

          // Pause / help / user menu freezes gameplay but keeps rendering
          if (mode === 'play' && (paused || helpOpen || userMenuOpen || portalOpen)) {
            render();
            requestAnimationFrame(gameLoop);
            return;
          }

          // Sync config changes in edit mode
          if (mode === 'edit') {
            if (playerCells.length !== 1) {
              playerCells = [makePlayerCell(0, 0, cfg.player.startSize || 20, 0)];
            }
            playerCells[0].mass = cfg.player.startSize || 20;
            playerCells[0].x = 0;
            playerCells[0].y = 0;
            syncPlayerAggregate();
            updateScoreDisplay();
            // Live preview of virus settings
            const wantVirus = Math.max(0, Math.round(cfg.mechanics.virusCount || 0));
            while (viruses.length < wantVirus) viruses.push(spawnVirus());
            while (viruses.length > wantVirus) viruses.pop();
            for (const v of viruses) {
              v.mass = cfg.mechanics.virusMass || 100;
              v.spin += v.spinSpeed * dt;
            }
            // Maintain particle count
            while (particles.length < cfg.arena.particleCount) particles.push(spawnParticle());
            while (particles.length > cfg.arena.particleCount) particles.pop();
            // Live preview of AI settings
            const desiredAI = Math.min(100, Math.max(0, Math.round(cfg.ai.count || 0)));
            if (aiCells.length !== desiredAI) {
              while (aiCells.length < desiredAI) aiCells.push(spawnAICell());
              while (aiCells.length > desiredAI) aiCells.pop();
            }
            const minS = Math.min(cfg.ai.minStartSize, cfg.ai.maxStartSize);
            const maxS = Math.max(cfg.ai.minStartSize, cfg.ai.maxStartSize);
            aiCells.forEach((a, i) => {
              const t = desiredAI > 1 ? i / (desiredAI - 1) : 0;
              a.mass = minS + (maxS - minS) * t;
            });
            lbTimer += dt;
            if (lbTimer > 0.3) {
              lbTimer = 0;
              updateLeaderboard();
            }
          }

          update(dt);
          render();
          requestAnimationFrame(gameLoop);
        }

        requestAnimationFrame(gameLoop);
      }

      // Astrocade invokes run() itself. A copy uploaded as index.html on an FTP
      // host has no platform launcher, so start the same game automatically.
      if (window.__growthOrbitStandalone) run('play');
    </script>
  <a href="index.php" style="position:fixed;left:14px;bottom:14px;z-index:99999;padding:10px 14px;border:1px solid #00eaff;border-radius:10px;background:rgba(2,8,20,.82);color:#00eaff;text-decoration:none;font:700 13px Inter,sans-serif">← PORTALE ZEROARCADE</a>
  </body>
</html>
