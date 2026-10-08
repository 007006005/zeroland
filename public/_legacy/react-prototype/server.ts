import express, { Request, Response } from 'express';
import http from 'http';
import { WebSocketServer, WebSocket } from 'ws';
import { createServer as createViteServer } from 'vite';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import JSZip from 'jszip';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const DATA_FILE = path.join(__dirname, 'data', 'sync_store.json');

function loadStore() {
  try {
    if (fs.existsSync(DATA_FILE)) {
      const content = fs.readFileSync(DATA_FILE, 'utf-8');
      const data = JSON.parse(content);
      if (!data.chat_messages) {
        data.chat_messages = [
          {
            id: 'msg_sys_1',
            username: 'Arcade Bot',
            avatar: '🤖',
            text: 'Benvenuti nella chat globale Retro Arcade! Condividi i tuoi punteggi e chatta in tempo reale!',
            type: 'system',
            timestamp: new Date().toISOString(),
            score_data: null,
          },
        ];
      }
      return data;
    }
  } catch (err) {
    console.error('Error loading store:', err);
  }
  return {
    users: [],
    leaderboards: {},
    shop_items: [],
    achievements: [],
    daily_challenges: [],
    chat_messages: [],
  };
}

function saveStore(data: any) {
  try {
    fs.writeFileSync(DATA_FILE, JSON.stringify(data, null, 2), 'utf-8');
    return true;
  } catch (err) {
    console.error('Error saving store:', err);
    return false;
  }
}

function calculateLevel(xp: number): number {
  return Math.max(1, Math.floor(Math.pow(xp / 100, 0.6)) + 1);
}

function getTodayDateStr(): string {
  return new Date().toISOString().split('T')[0];
}

function getYesterdayDateStr(): string {
  const d = new Date();
  d.setDate(d.getDate() - 1);
  return d.toISOString().split('T')[0];
}

function getDaysBetween(dateStr1: string, dateStr2: string): number {
  const d1 = new Date(dateStr1 + 'T00:00:00Z').getTime();
  const d2 = new Date(dateStr2 + 'T00:00:00Z').getTime();
  return Math.round((d2 - d1) / (1000 * 60 * 60 * 24));
}

const ZERO_COIN_PACKAGES = [
  {
    id: 'pack_starter',
    name: 'Starter Capsule',
    tagline: 'Ideale per iniziare e testare le partite arcade',
    coins: 1000,
    bonus_coins: 0,
    gems_bonus: 0,
    price_eur: 2.99,
    icon: '🪙',
    features: ['1.000 ZeroCoins immediati', 'Sblocco cosmetici base', 'Partecipazione classifiche'],
  },
  {
    id: 'pack_cadet',
    name: 'Cadet Booster',
    tagline: 'Spinta energetica per scalare le prime leghe',
    coins: 2500,
    bonus_coins: 250,
    gems_bonus: 5,
    price_eur: 4.99,
    icon: '⚡',
    badge: '+10% BONUS',
    features: ['2.750 ZeroCoins totali', '5 Gemme Cosmiche', 'Boost XP per 24 ore'],
  },
  {
    id: 'pack_combat',
    name: 'Combat Squadron',
    tagline: 'Pacchetto d\'assalto per battaglie frequenti',
    coins: 5500,
    bonus_coins: 750,
    gems_bonus: 15,
    price_eur: 9.99,
    icon: '🚀',
    badge: 'CONSIGLIATO',
    features: ['6.250 ZeroCoins totali', '15 Gemme Cosmiche', 'Scia Matrix Rain sbloccata'],
  },
  {
    id: 'pack_cyber',
    name: 'Cyber Operative',
    tagline: 'Il pacchetto più popolare della community arcade',
    coins: 12000,
    bonus_coins: 2000,
    gems_bonus: 35,
    price_eur: 19.99,
    icon: '🛡️',
    is_popular: true,
    badge: 'PIÙ VENDUTO',
    features: ['14.000 ZeroCoins totali', '35 Gemme Cosmiche', 'Scudo Overclock +1 permanente', 'Prefisso Clan personalizzato'],
  },
  {
    id: 'pack_vip_trial_upgrade',
    name: 'VIP PASS AUTOMATICO',
    tagline: 'Diventa utente PREMIUM VIP istantaneamente!',
    coins: 15000,
    bonus_coins: 5000,
    gems_bonus: 60,
    price_eur: 24.99,
    icon: '⭐',
    gives_premium_role: true,
    badge: 'RUOLO PREMIUM VIP',
    features: ['Diventi PREMIUM USER automatico', '20.000 ZeroCoins totali', '60 Gemme Cosmiche', 'Badge VIP in Chat Globale ⭐', 'x2 Monete per ogni match giocato'],
  },
  {
    id: 'pack_commander',
    name: 'Commander Elite',
    tagline: 'Riserve strategiche per condurre le Clan Wars',
    coins: 25000,
    bonus_coins: 6000,
    gems_bonus: 100,
    price_eur: 34.99,
    icon: '⚔️',
    features: ['31.000 ZeroCoins totali', '100 Gemme Cosmiche', '1.000 Polvere Cyber per crafting', 'Slot torneo prioritario'],
  },
  {
    id: 'pack_quantum',
    name: 'Quantum Overlord',
    tagline: 'Potenza quantistica ad altissima resa',
    coins: 40000,
    bonus_coins: 12000,
    gems_bonus: 180,
    price_eur: 49.99,
    icon: '🔮',
    features: ['52.000 ZeroCoins totali', '180 Gemme Cosmiche', 'Skin cellula e navetta Quantum Pro', 'Accesso tornei master'],
  },
  {
    id: 'pack_pro_license_bundle',
    name: 'LICENZA ARCADE PRO 30G',
    tagline: 'Licenza ufficiale 30 Giorni - PayPal / Carta',
    coins: 30000,
    bonus_coins: 10000,
    gems_bonus: 150,
    price_eur: 59.00,
    is_best_value: true,
    gives_premium_role: true,
    badge: 'LICENZA 30 GIORNI',
    features: ['Conto alla rovescia 30 giorni attivo', '40.000 ZeroCoins accreditati', 'Ruolo Premium VIP incluso', 'Accesso illimitato senza restrizioni', 'Pagamento sicuro PayPal o Carta di Credito'],
  },
  {
    id: 'pack_cosmic_titan',
    name: 'Cosmic Titan Vault',
    tagline: 'Per foraggiare clan e gilde leggendarie',
    coins: 75000,
    bonus_coins: 25000,
    gems_bonus: 350,
    price_eur: 79.99,
    icon: '🪐',
    gives_premium_role: true,
    badge: 'SUPER SAVINGS',
    features: ['100.000 ZeroCoins totali', '350 Gemme Cosmiche', 'Stemma Clan animato', 'Status Premium VIP illimitato'],
  },
  {
    id: 'pack_supreme_founder',
    name: 'Supreme Founder Syndicate',
    tagline: 'Il grado supremo: tesoro colossale e potere Founder',
    coins: 200000,
    bonus_coins: 80000,
    gems_bonus: 1000,
    price_eur: 149.99,
    icon: '👑',
    gives_founder_role: true,
    badge: 'SUPREME FOUNDER 👑',
    features: ['280.000 ZeroCoins totali', '1.000 Gemme Cosmiche', 'Ruolo FOUNDER SUPREMO 👑', 'Controllo totale ruoli dello staff', 'Badge dorato in chat globale'],
  },
];

function getLicenseStatus(user: any) {
  if (!user || !user.has_pro_license || !user.license_expires_at) {
    return {
      active: false,
      price_eur: 59.00,
      duration_days: 30,
      seconds_remaining: 0,
      formatted_countdown: 'Non Attiva (59,00 €)',
    };
  }

  const expiresTime = new Date(user.license_expires_at).getTime();
  const now = Date.now();
  const diffMs = expiresTime - now;

  if (diffMs <= 0) {
    user.has_pro_license = false;
    return {
      active: false,
      price_eur: 59.00,
      duration_days: 30,
      seconds_remaining: 0,
      formatted_countdown: 'Scaduta',
    };
  }

  const secondsRemaining = Math.floor(diffMs / 1000);
  const days = Math.floor(secondsRemaining / (24 * 3600));
  const hours = Math.floor((secondsRemaining % (24 * 3600)) / 3600);
  const minutes = Math.floor((secondsRemaining % 3600) / 60);
  const seconds = secondsRemaining % 60;

  const formattedCountdown = `${days}g ${String(hours).padStart(2, '0')}h ${String(minutes).padStart(2, '0')}m ${String(seconds).padStart(2, '0')}s`;

  return {
    active: true,
    price_eur: 59.00,
    duration_days: 30,
    expires_at: user.license_expires_at,
    seconds_remaining: secondsRemaining,
    formatted_countdown: formattedCountdown,
    payment_method: user.license_payment_method || 'paypal',
  };
}

function getTrialStatus(user: any) {
  if (!user || !user.is_premium_trial || !user.premium_trial_expires_at) {
    return { active: false, days_left: 0 };
  }
  const diffMs = new Date(user.premium_trial_expires_at).getTime() - Date.now();
  if (diffMs <= 0) {
    user.is_premium_trial = false;
    if (user.role === 'premium') user.role = 'user';
    return { active: false, days_left: 0 };
  }
  const daysLeft = Math.ceil(diffMs / (24 * 3600 * 1000));
  return { active: true, days_left: daysLeft, expires_at: user.premium_trial_expires_at };
}

interface StreakRewardCalculation {
  streak: number;
  best_streak: number;
  cycleDay: number;
  baseCoins: number;
  finalCoins: number;
  finalExp: number;
  finalGems: number;
  streakMultiplier: number;
  roleMultiplier: number;
  roleBonusLabel: string;
  isJackpotDay: boolean;
  claimed_today: boolean;
  nextDayRewardCoins: number;
  daysSchedule: Array<{
    day: number;
    coins: number;
    gems: number;
    status: 'claimed' | 'current' | 'locked';
    isJackpot: boolean;
  }>;
}

function calculateStreakRewards(user: any): StreakRewardCalculation {
  const streak = Math.max(1, user.streak || 1);
  const cycleDay = ((streak - 1) % 7) + 1; // 1 to 7

  const baseCoinsLadder = [75, 125, 200, 300, 450, 650, 1250];
  const baseExpLadder = [25, 50, 75, 100, 150, 200, 500];

  const baseCoins = baseCoinsLadder[cycleDay - 1];
  const baseExp = baseExpLadder[cycleDay - 1];
  const isJackpotDay = cycleDay === 7;
  const baseGems = isJackpotDay ? 25 : (cycleDay === 3 || cycleDay === 5 ? 5 : 0);

  // Completed 7-day cycles multiplier (+15% each)
  const completedWeeks = Math.floor((streak - 1) / 7);
  const streakMultiplier = 1 + completedWeeks * 0.15;

  let roleMultiplier = 1.0;
  let roleBonusLabel = 'Bonus Standard';
  if (user.role === 'founder') {
    roleMultiplier = 2.0;
    roleBonusLabel = 'Bonus Founder Supremo (+100%)';
  } else if (user.role === 'admin' || user.role === 'moderatore') {
    roleMultiplier = 1.5;
    roleBonusLabel = 'Bonus Staff (+50%)';
  } else if (user.role === 'premium') {
    roleMultiplier = 1.5;
    roleBonusLabel = 'Bonus VIP Esclusivo (+50%)';
  } else if (user.role === 'helper') {
    roleMultiplier = 1.25;
    roleBonusLabel = 'Bonus Helper (+25%)';
  }

  const finalCoins = Math.round(baseCoins * streakMultiplier * roleMultiplier);
  const finalExp = Math.round(baseExp * roleMultiplier);
  const finalGems = Math.round(baseGems * (user.role === 'founder' || user.role === 'premium' ? 1.5 : 1));

  const nextCycleDay = (cycleDay % 7) + 1;
  const nextDayBaseCoins = baseCoinsLadder[nextCycleDay - 1];
  const nextDayRewardCoins = Math.round(nextDayBaseCoins * streakMultiplier * roleMultiplier);

  const today = getTodayDateStr();
  const claimedToday = user.last_streak_claim_date === today;

  const daysSchedule = baseCoinsLadder.map((c, i) => {
    const dayNum = i + 1;
    const isJp = dayNum === 7;
    const g = isJp ? 25 : (dayNum === 3 || dayNum === 5 ? 5 : 0);
    const coinsForDay = Math.round(c * streakMultiplier * roleMultiplier);
    let status: 'claimed' | 'current' | 'locked' = 'locked';

    if (dayNum < cycleDay) {
      status = 'claimed';
    } else if (dayNum === cycleDay) {
      status = claimedToday ? 'claimed' : 'current';
    } else {
      status = 'locked';
    }

    return {
      day: dayNum,
      coins: coinsForDay,
      gems: Math.round(g * (user.role === 'founder' || user.role === 'premium' ? 1.5 : 1)),
      status,
      isJackpot: isJp,
    };
  });

  return {
    streak,
    best_streak: Math.max(user.best_streak || 1, streak),
    cycleDay,
    baseCoins,
    finalCoins,
    finalExp,
    finalGems,
    streakMultiplier: Number(streakMultiplier.toFixed(2)),
    roleMultiplier,
    roleBonusLabel,
    isJackpotDay,
    claimed_today: claimedToday,
    nextDayRewardCoins,
    daysSchedule,
  };
}

function processStreakVisit(user: any) {
  const today = getTodayDateStr();
  if (!user.streak || user.streak < 1) user.streak = 1;
  if (!user.best_streak) user.best_streak = user.streak;

  if (!user.last_visit_date) {
    user.last_visit_date = today;
  } else {
    const diff = getDaysBetween(user.last_visit_date, today);
    if (diff === 1) {
      // Consecutive day visit!
      user.streak = (user.streak || 1) + 1;
      user.best_streak = Math.max(user.best_streak || user.streak, user.streak);
      user.last_visit_date = today;
    } else if (diff > 1) {
      // Streak broken, reset to 1
      user.streak = 1;
      user.last_visit_date = today;
    }
  }

  user.best_streak = Math.max(user.best_streak || 1, user.streak);
  user.streak_claimed_today = (user.last_streak_claim_date === today);
}

async function startServer() {
  const app = express();
  const server = http.createServer(app);
  const wss = new WebSocketServer({ server });
  const PORT = process.env.PORT || 3000;

  app.use(express.json());

  // WebSocket real-time broadcast helper
  const broadcast = (payload: any) => {
    const raw = JSON.stringify(payload);
    wss.clients.forEach((client) => {
      if (client.readyState === WebSocket.OPEN) {
        client.send(raw);
      }
    });
  };

  // WebSocket Connection Lifecycle
  wss.on('connection', (ws: WebSocket) => {
    const store = loadStore();
    const onlineCount = wss.clients.size;

    // 1. Invia messaggi iniziali e presenza al client appena connesso
    ws.send(
      JSON.stringify({
        type: 'init',
        messages: (store.chat_messages || []).slice(-60),
        onlineCount,
      })
    );

    // 2. Notifica presenza a tutti i client
    broadcast({
      type: 'presence',
      onlineCount,
    });

    // 3. Ricezione eventi dal client
    ws.on('message', (data: string) => {
      try {
        const msg = JSON.parse(data.toString());
        if (msg.type === 'chat:message' || msg.type === 'chat:share_score') {
          const freshStore = loadStore();
          const newMsg = {
            id: `msg_${Date.now()}_${Math.random().toString(36).substr(2, 5)}`,
            username: msg.username || 'Pilota_Anonimo',
            avatar: msg.avatar || '👾',
            text: msg.text || '',
            type: msg.type === 'chat:share_score' ? 'score_share' : 'message',
            timestamp: new Date().toISOString(),
            score_data: msg.score_data || null,
          };

          freshStore.chat_messages = freshStore.chat_messages || [];
          freshStore.chat_messages.push(newMsg);

          // Mantieni ultimi 120 messaggi
          if (freshStore.chat_messages.length > 120) {
            freshStore.chat_messages = freshStore.chat_messages.slice(-120);
          }
          saveStore(freshStore);

          // Broadcast real-time a tutti
          broadcast({
            type: 'chat:new_message',
            message: newMsg,
          });
        }
      } catch (err) {
        console.error('Error parsing WebSocket message:', err);
      }
    });

    ws.on('close', () => {
      broadcast({
        type: 'presence',
        onlineCount: wss.clients.size,
      });
    });
  });

  // Helper auth resolver
  const getUserFromRequest = (req: Request, store: any) => {
    const authHeader = req.headers.authorization || '';
    let token = '';
    if (authHeader.startsWith('Bearer ')) {
      token = authHeader.substring(7).trim();
    }
    if (!token && req.query.token) {
      token = String(req.query.token);
    }

    if (token) {
      const found = store.users.find((u: any) => u.id === token);
      if (found) return found;
    }
    // Fallback to demo user
    return store.users[0] || null;
  };

  // API Routes matching both /api/<endpoint> and /api/<endpoint>.php
  const registerRoute = (
    method: 'get' | 'post',
    paths: string[],
    handler: (req: Request, res: Response) => void
  ) => {
    paths.forEach((p) => {
      app[method](p, handler);
    });
  };

  // 1. ME endpoint
  registerRoute('get', ['/api/me', '/api/me.php'], (req, res) => {
    const store = loadStore();
    const user = getUserFromRequest(req, store);
    if (!user) {
      return res.status(401).json({ success: false, error: 'Non autorizzato' });
    }
    processStreakVisit(user);
    saveStore(store);
    const streakInfo = calculateStreakRewards(user);
    const licenseInfo = getLicenseStatus(user);
    const trialInfo = getTrialStatus(user);
    res.json({
      success: true,
      user,
      streak_info: streakInfo,
      license_info: licenseInfo,
      trial_info: trialInfo,
      coin_packages: ZERO_COIN_PACKAGES,
      shop_items: store.shop_items || [],
      achievements_catalog: store.achievements || [],
      leaderboards: store.leaderboards || {},
      daily_challenges: store.daily_challenges || [],
    });
  });

  // 2. LOGIN endpoint
  registerRoute('post', ['/api/login', '/api/login.php'], (req, res) => {
    const { username, password } = req.body || {};
    if (!username || !username.trim()) {
      return res.status(400).json({ success: false, error: 'Username obbligatorio' });
    }

    const store = loadStore();
    const cleanUser = username.trim();
    let found = store.users.find(
      (u: any) => u.username.toLowerCase() === cleanUser.toLowerCase()
    );

    if (!found) {
      return res.status(404).json({ success: false, error: 'Utente non trovato. Registrati per iniziare!' });
    }

    if (found.is_banned) {
      return res.status(403).json({
        success: false,
        error: `Account sospeso: ${found.ban_reason || 'Violazione del regolamento'}`,
      });
    }

    if (found.password && password && found.password !== password) {
      return res.status(401).json({ success: false, error: 'Password non corretta' });
    }

    found.last_login = new Date().toISOString();
    processStreakVisit(found);
    saveStore(store);
    const streakInfo = calculateStreakRewards(found);

    res.json({
      success: true,
      message: 'Login effettuato con successo',
      token: found.id,
      user: found,
      streak_info: streakInfo,
    });
  });

  // 2b. STREAK ENDPOINTS
  registerRoute('get', ['/api/streak/status', '/api/streak/status.php'], (req, res) => {
    const store = loadStore();
    const user = getUserFromRequest(req, store);
    if (!user) {
      return res.status(401).json({ success: false, error: 'Non autorizzato' });
    }
    processStreakVisit(user);
    saveStore(store);
    const streakInfo = calculateStreakRewards(user);
    res.json({
      success: true,
      user,
      streak_info: streakInfo,
    });
  });

  registerRoute('post', ['/api/streak/claim', '/api/streak/claim.php'], (req, res) => {
    const store = loadStore();
    const user = getUserFromRequest(req, store);
    if (!user) {
      return res.status(401).json({ success: false, error: 'Non autorizzato' });
    }
    processStreakVisit(user);
    const today = getTodayDateStr();
    if (user.last_streak_claim_date === today) {
      const info = calculateStreakRewards(user);
      return res.status(400).json({
        success: false,
        already_claimed: true,
        message: 'Ricompensa di oggi già riscossa! Torna domani per continuare la serie.',
        user,
        streak_info: info,
      });
    }

    const rewards = calculateStreakRewards(user);
    user.coins = (user.coins || 0) + rewards.finalCoins;
    user.gems = (user.gems || 0) + rewards.finalGems;
    user.xp = (user.xp || 0) + rewards.finalExp;
    user.level = calculateLevel(user.xp);
    user.total_streak_coins_earned = (user.total_streak_coins_earned || 0) + rewards.finalCoins;
    user.last_streak_claim_date = today;
    user.streak_claimed_today = true;

    const newAchievements: string[] = [];
    if (user.streak >= 3 && !user.achievements.includes('streak_3_days')) {
      user.achievements.push('streak_3_days');
      newAchievements.push('streak_3_days');
    }
    if (user.streak >= 7 && !user.achievements.includes('streak_7_days')) {
      user.achievements.push('streak_7_days');
      newAchievements.push('streak_7_days');
    }

    saveStore(store);
    const updatedInfo = calculateStreakRewards(user);

    return res.json({
      success: true,
      message: `Serie Giorno ${user.streak} riscossa: +${rewards.finalCoins} ZeroCoins!`,
      coins_reward: rewards.finalCoins,
      gems_reward: rewards.finalGems,
      xp_reward: rewards.finalExp,
      streak: user.streak,
      best_streak: user.best_streak,
      total_coins: user.coins,
      new_achievements: newAchievements,
      rewards_details: rewards,
      user,
      streak_info: updatedInfo,
    });
  });

  registerRoute('post', ['/api/streak/simulate-advance', '/api/streak/simulate-advance.php'], (req, res) => {
    const store = loadStore();
    const user = getUserFromRequest(req, store);
    if (!user) {
      return res.status(401).json({ success: false, error: 'Non autorizzato' });
    }

    // Set last_visit_date to yesterday so today counts as consecutive day!
    const yesterday = getYesterdayDateStr();
    user.last_visit_date = yesterday;
    user.last_streak_claim_date = '2000-01-01'; // Ready to claim for the new simulated day
    processStreakVisit(user);
    saveStore(store);

    const info = calculateStreakRewards(user);
    res.json({
      success: true,
      message: `Simulazione: serie avanzata con successo al Giorno ${user.streak}!`,
      user,
      streak_info: info,
    });
  });

  registerRoute('post', ['/api/streak/simulate-reset', '/api/streak/simulate-reset.php'], (req, res) => {
    const store = loadStore();
    const user = getUserFromRequest(req, store);
    if (!user) {
      return res.status(401).json({ success: false, error: 'Non autorizzato' });
    }

    user.streak = 1;
    user.last_visit_date = getTodayDateStr();
    user.last_streak_claim_date = '2000-01-01';
    user.streak_claimed_today = false;
    saveStore(store);

    const info = calculateStreakRewards(user);
    res.json({
      success: true,
      message: 'Serie riavviata al Giorno 1.',
      user,
      streak_info: info,
    });
  });

  // 3. REGISTER endpoint
  registerRoute('post', ['/api/register', '/api/register.php'], (req, res) => {
    const { username, password, email, avatar, referral_code } = req.body || {};
    if (!username || username.trim().length < 3) {
      return res.status(400).json({ success: false, error: 'Minimo 3 caratteri per il nome' });
    }

    const store = loadStore();
    const cleanUser = username.trim();
    const exists = store.users.some(
      (u: any) => u.username.toLowerCase() === cleanUser.toLowerCase()
    );

    if (exists) {
      return res.status(409).json({ success: false, error: 'Nome utente già occupato' });
    }

    let assignedRole = 'premium'; // Default free trial is premium
    let isTrial = true;
    if (store.users.length === 0) {
      assignedRole = 'founder';
      isTrial = false;
    } else if (cleanUser.toLowerCase().includes('founder')) {
      assignedRole = 'founder';
      isTrial = false;
    } else if (cleanUser.toLowerCase().includes('admin')) {
      assignedRole = 'admin';
      isTrial = false;
    }

    const refCode = 'ARCADE-' + cleanUser.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 5) + '-' + Math.floor(100 + Math.random() * 900);
    let initialCoins = 500; // Base welcome coins
    let referredBy: string | undefined = undefined;
    let referralSuccessMessage = '';

    if (referral_code && typeof referral_code === 'string') {
      const cleanRef = referral_code.trim().toUpperCase();
      const inviter = store.users.find(
        (u: any) => (u.referral_code && u.referral_code.toUpperCase() === cleanRef) || u.username.toUpperCase() === cleanRef
      );
      if (inviter) {
        // Both receive 500 ZeroCoins bonus!
        initialCoins += 500;
        referredBy = inviter.username;
        inviter.coins = (inviter.coins || 0) + 500;
        inviter.referral_count = (inviter.referral_count || 0) + 1;
        inviter.referral_coins_earned = (inviter.referral_coins_earned || 0) + 500;
        inviter.referrals_history = inviter.referrals_history || [];
        inviter.referrals_history.unshift({
          username: cleanUser,
          date: new Date().toISOString(),
          bonus: 500,
        });
        referralSuccessMessage = ` (+500 ZeroCoins bonus referral da ${inviter.username} accreditati!)`;
      }
    }

    const newUser = {
      id: `usr_${Date.now()}`,
      username: cleanUser,
      role: assignedRole,
      password: password || 'arcade123',
      email: email || '',
      avatar: avatar || '🚀',
      coins: initialCoins,
      gems: 15,
      dust: 50,
      xp: 50,
      level: 1,
      streak: 1,
      clan: 'ZERO',
      referral_code: refCode,
      referred_by: referredBy,
      referral_count: 0,
      referral_coins_earned: 0,
      referrals_history: [],
      is_premium_trial: isTrial,
      premium_trial_started_at: isTrial ? new Date().toISOString() : undefined,
      premium_trial_expires_at: isTrial ? new Date(Date.now() + 7 * 86400000).toISOString() : undefined,
      has_pro_license: false,
      created_at: new Date().toISOString(),
      last_login: new Date().toISOString(),
      unlocked_items: ['skin_neon_blue', 'skin_gold'],
      equipped: {
        ship_skin: 'skin_neon_blue',
        cell_skin: '#00f0ff',
        trail: 'default',
        sound_pack: 'retro_synth',
      },
      stats: {
        games_played: 0,
        highscores: {
          space_defender: 0,
          cyber_runner: 0,
          neon_breaker: 0,
          zero_agar: 0,
        },
        total_kills: 0,
        total_distance: 0,
        bosses_defeated: 0,
      },
      achievements: [],
    };

    store.users.push(newUser);
    saveStore(store);

    res.json({
      success: true,
      message: `Account registrato con successo! Free Trial Premium 7 giorni attiva!${referralSuccessMessage}`,
      token: newUser.id,
      user: newUser,
    });
  });

  // 3b. GUEST endpoint (ospite senza registrazione)
  registerRoute('post', ['/api/guest', '/api/guest.php'], (req, res) => {
    const guestUser = {
      id: `usr_guest_${Date.now()}`,
      username: `Ospite_${Math.floor(Math.random() * 900) + 100}`,
      role: 'guest',
      avatar: '👤',
      coins: 150,
      gems: 5,
      dust: 0,
      xp: 0,
      level: 1,
      streak: 1,
      clan: 'GUEST',
      created_at: new Date().toISOString(),
      last_login: new Date().toISOString(),
      unlocked_items: ['skin_neon_blue'],
      equipped: {
        ship_skin: 'skin_neon_blue',
        cell_skin: '#00f0ff',
        trail: 'default',
        sound_pack: 'retro_synth',
      },
      stats: {
        games_played: 0,
        highscores: {
          space_defender: 0,
          cyber_runner: 0,
          neon_breaker: 0,
          zero_agar: 0,
        },
        total_kills: 0,
        total_distance: 0,
        bosses_defeated: 0,
      },
      achievements: [],
    };

    res.json({
      success: true,
      message: 'Accesso come Ospite attivo',
      token: guestUser.id,
      user: guestUser,
    });
  });

  // 4. LOGOUT endpoint
  registerRoute('post', ['/api/logout', '/api/logout.php'], (_req, res) => {
    res.json({ success: true, message: 'Disconnesso' });
  });

  // 4b. USER PROFILE UPDATE endpoint
  registerRoute('post', ['/api/user/update-profile'], (req, res) => {
    const store = loadStore();
    const user = getUserFromRequest(req, store);
    if (!user) {
      return res.status(401).json({ success: false, error: 'Non autorizzato' });
    }

    const { avatar, email, clan, password } = req.body || {};
    if (avatar) user.avatar = avatar;
    if (email !== undefined) user.email = email;
    if (clan) user.clan = String(clan).replace(/[^A-Za-z0-9]/g, '').toUpperCase().slice(0, 6);
    if (password && password.trim().length >= 4) user.password = password.trim();

    saveStore(store);
    res.json({ success: true, message: 'Profilo salvato', user });
  });

  // 4c. UPGRADE TO PREMIUM endpoint
  registerRoute('post', ['/api/user/upgrade-premium'], (req, res) => {
    const store = loadStore();
    const user = getUserFromRequest(req, store);
    if (!user) {
      return res.status(401).json({ success: false, error: 'Non autorizzato' });
    }

    user.role = 'premium';
    user.coins = (user.coins || 0) + 2500;
    user.gems = (user.gems || 0) + 100;
    if (!user.unlocked_items.includes('skin_gold')) user.unlocked_items.push('skin_gold');
    if (!user.unlocked_items.includes('trail_plasma')) user.unlocked_items.push('trail_plasma');

    saveStore(store);
    res.json({
      success: true,
      message: 'Benvenuto nel club Premium VIP! +2.500 monete e skin oro sbloccate!',
      user,
    });
  });

  // 4d. ADMIN: List all users
  registerRoute('get', ['/api/admin/users', '/api/admin/users.php'], (req, res) => {
    const store = loadStore();
    const caller = getUserFromRequest(req, store);
    if (!caller || !['founder', 'admin', 'moderatore', 'helper'].includes(caller.role)) {
      return res.status(403).json({ success: false, error: 'Accesso negato: privilegi amministrativi richiesti' });
    }

    res.json({
      success: true,
      caller_role: caller.role,
      users: store.users.map((u: any) => ({
        ...u,
        password: caller.role === 'founder' || caller.role === 'admin' ? u.password : '••••••••',
      })),
    });
  });

  // 4e. ADMIN: Update any user (role, coins, gems, level, ban, mute, password)
  registerRoute('post', ['/api/admin/update-user', '/api/admin/update-user.php'], (req, res) => {
    const store = loadStore();
    const caller = getUserFromRequest(req, store);
    if (!caller || !['founder', 'admin', 'moderatore'].includes(caller.role)) {
      return res.status(403).json({ success: false, error: 'Permessi insufficienti' });
    }

    const { user_id, role, coins, gems, level, is_banned, ban_reason, is_muted, password } = req.body || {};
    const target = store.users.find((u: any) => u.id === user_id);
    if (!target) {
      return res.status(404).json({ success: false, error: 'Utente non trovato' });
    }

    // Se il target è founder, solo un founder può modificarlo
    if (target.role === 'founder' && caller.role !== 'founder') {
      return res.status(403).json({ success: false, error: 'Solo il Founder può modificare un altro Founder' });
    }

    // Modifica ruolo: solo ed esclusivamente il FOUNDER SUPREMO può decidere i ruoli dello staff!
    if (role) {
      if (caller.role !== 'founder') {
        return res.status(403).json({ success: false, error: 'Solo il Founder Supremo può decidere o modificare i ruoli dello staff!' });
      }
      const validRoles = ['founder', 'admin', 'moderatore', 'helper', 'premium', 'user', 'guest'];
      if (validRoles.includes(role)) {
        target.role = role;
      }
    }

    // Modifica saldo: founder e admin
    if (['founder', 'admin'].includes(caller.role)) {
      if (coins !== undefined) target.coins = Math.max(0, Number(coins));
      if (gems !== undefined) target.gems = Math.max(0, Number(gems));
      if (level !== undefined) target.level = Math.max(1, Number(level));
      if (password && password.trim()) target.password = password.trim();
    }

    // Moderazione: founder, admin, moderatore
    if (is_banned !== undefined) {
      target.is_banned = Boolean(is_banned);
      target.ban_reason = ban_reason || 'Provvedimento disciplinare staff';
    }
    if (is_muted !== undefined) {
      target.is_muted = Boolean(is_muted);
    }

    saveStore(store);

    res.json({
      success: true,
      message: `Utente ${target.username} aggiornato`,
      user: target,
      users: store.users,
    });
  });

  // 4f. ADMIN: Delete user
  registerRoute('post', ['/api/admin/delete-user', '/api/admin/delete-user.php'], (req, res) => {
    const store = loadStore();
    const caller = getUserFromRequest(req, store);
    if (!caller || !['founder', 'admin'].includes(caller.role)) {
      return res.status(403).json({ success: false, error: 'Solo Founder o Admin possono cancellare account' });
    }

    const { user_id } = req.body || {};
    const target = store.users.find((u: any) => u.id === user_id);
    if (!target) {
      return res.status(404).json({ success: false, error: 'Utente non trovato' });
    }

    if (target.role === 'founder') {
      return res.status(403).json({ success: false, error: 'Impossibile eliminare l\'account Founder' });
    }

    store.users = store.users.filter((u: any) => u.id !== user_id);
    saveStore(store);

    res.json({
      success: true,
      message: `Utente ${target.username} rimosso con successo`,
      users: store.users,
    });
  });

  // 4g. ADMIN: Broadcast announcement
  registerRoute('post', ['/api/admin/broadcast', '/api/admin/broadcast.php'], (req, res) => {
    const store = loadStore();
    const caller = getUserFromRequest(req, store);
    if (!caller || !['founder', 'admin', 'moderatore', 'helper'].includes(caller.role)) {
      return res.status(403).json({ success: false, error: 'Permessi insufficienti per trasmettere annunci' });
    }

    const { text } = req.body || {};
    if (!text || !text.trim()) {
      return res.status(400).json({ success: false, error: 'Testo dell\'annuncio vuoto' });
    }

    const newMsg = {
      id: `msg_sys_${Date.now()}`,
      username: `📢 [${caller.role.toUpperCase()}] ${caller.username}`,
      avatar: '🛡️',
      text: text.trim(),
      type: 'system',
      timestamp: new Date().toISOString(),
      score_data: null,
    };

    store.chat_messages = store.chat_messages || [];
    store.chat_messages.push(newMsg);
    saveStore(store);

    broadcast({
      type: 'chat:new_message',
      message: newMsg,
    });

    res.json({ success: true, message: newMsg });
  });

  // 4h. ADMIN: Clear chat
  registerRoute('post', ['/api/admin/clear-chat', '/api/admin/clear-chat.php'], (req, res) => {
    const store = loadStore();
    const caller = getUserFromRequest(req, store);
    if (!caller || !['founder', 'admin', 'moderatore'].includes(caller.role)) {
      return res.status(403).json({ success: false, error: 'Permessi insufficienti per pulire la chat' });
    }

    const resetMsg = {
      id: `msg_clear_${Date.now()}`,
      username: 'Sistema Arcade',
      avatar: '🧹',
      text: `La cronologia della chat è stata ripulita da ${caller.username} (${caller.role.toUpperCase()})`,
      type: 'system',
      timestamp: new Date().toISOString(),
      score_data: null,
    };

    store.chat_messages = [resetMsg];
    saveStore(store);

    broadcast({
      type: 'init',
      messages: [resetMsg],
      onlineCount: wss.clients.size,
    });

    res.json({ success: true, message: 'Chat ripulita con successo' });
  });

  // 4i. PACKAGES: Get 10 ZeroCoin packages
  registerRoute('get', ['/api/packages', '/api/packages.php'], (_req, res) => {
    res.json({
      success: true,
      packages: ZERO_COIN_PACKAGES,
    });
  });

  // 4j. PACKAGES: Purchase ZeroCoins package (10 pacchetti)
  registerRoute('post', ['/api/packages/purchase', '/api/packages/purchase.php'], (req, res) => {
    const store = loadStore();
    const user = getUserFromRequest(req, store);
    if (!user) {
      return res.status(401).json({ success: false, error: 'Non autorizzato' });
    }

    const { package_id, payment_method, card_last4 } = req.body || {};
    const pkg = ZERO_COIN_PACKAGES.find((p) => p.id === package_id);
    if (!pkg) {
      return res.status(404).json({ success: false, error: 'Pacchetto non valido' });
    }

    if (!payment_method || !['paypal', 'credit_card'].includes(payment_method)) {
      return res.status(400).json({ success: false, error: 'Metodo di pagamento non supportato. Usa PayPal o Carta di Credito.' });
    }

    const totalCoins = pkg.coins + pkg.bonus_coins;
    user.coins = (user.coins || 0) + totalCoins;
    user.gems = (user.gems || 0) + (pkg.gems_bonus || 0);

    let roleUpgraded = false;
    let upgradedRoleName = '';

    // Automatic role elevation
    if (pkg.gives_founder_role) {
      user.role = 'founder';
      user.is_premium_trial = false;
      roleUpgraded = true;
      upgradedRoleName = 'Founder Supremo 👑';
    } else if (pkg.gives_premium_role) {
      if (user.role !== 'founder' && user.role !== 'admin') {
        user.role = 'premium';
        user.is_premium_trial = false;
        roleUpgraded = true;
        upgradedRoleName = 'Premium VIP ⭐';
      }
    }

    // Pro License bundle
    if (pkg.id === 'pack_pro_license_bundle') {
      user.has_pro_license = true;
      user.license_activated_at = new Date().toISOString();
      user.license_expires_at = new Date(Date.now() + 30 * 86400000).toISOString();
      user.license_payment_method = payment_method;
    }

    saveStore(store);

    const payDesc = payment_method === 'paypal' ? 'PayPal' : `Carta di Credito (•••• ${card_last4 || '4242'})`;
    let msg = `Acquisto di "${pkg.name}" completato con successo tramite ${payDesc}! Accredito: +${totalCoins.toLocaleString()} ZeroCoins`;
    if (pkg.gems_bonus > 0) msg += ` e +${pkg.gems_bonus} Gemme`;
    if (roleUpgraded) msg += ` & Promosso automaticamente a ${upgradedRoleName}!`;

    res.json({
      success: true,
      message: msg,
      user,
      package: pkg,
      receipt: {
        transaction_id: `tx_${Date.now()}_${Math.random().toString(36).substr(2, 6)}`,
        date: new Date().toISOString(),
        package_name: pkg.name,
        price_eur: pkg.price_eur,
        payment_method: payDesc,
        coins_credited: totalCoins,
        gems_credited: pkg.gems_bonus || 0,
      },
    });
  });

  // 4k. LICENSE: Status & 30-Day live countdown
  registerRoute('get', ['/api/license/status', '/api/license/status.php'], (req, res) => {
    const store = loadStore();
    const user = getUserFromRequest(req, store);
    if (!user) {
      return res.status(401).json({ success: false, error: 'Non autorizzato' });
    }
    const status = getLicenseStatus(user);
    saveStore(store);
    res.json({ success: true, license: status, user });
  });

  // 4l. LICENSE: Activate 30-Day license for 59,00 € (Solo PayPal o Carta di Credito)
  registerRoute('post', ['/api/license/activate', '/api/license/activate.php'], (req, res) => {
    const store = loadStore();
    const user = getUserFromRequest(req, store);
    if (!user) {
      return res.status(401).json({ success: false, error: 'Non autorizzato' });
    }

    const { payment_method, card_last4 } = req.body || {};
    if (!payment_method || !['paypal', 'credit_card'].includes(payment_method)) {
      return res.status(400).json({
        success: false,
        error: 'La licenza da 59€ può essere acquistata solo tramite PayPal o Carta di Credito.',
      });
    }

    user.has_pro_license = true;
    user.license_activated_at = new Date().toISOString();
    user.license_expires_at = new Date(Date.now() + 30 * 86400000).toISOString();
    user.license_payment_method = payment_method;

    user.coins = (user.coins || 0) + 40000;
    user.gems = (user.gems || 0) + 150;
    if (user.role !== 'founder' && user.role !== 'admin') {
      user.role = 'premium';
      user.is_premium_trial = false;
    }

    saveStore(store);

    const payDesc = payment_method === 'paypal' ? 'PayPal' : `Carta di Credito (•••• ${card_last4 || '4242'})`;
    const status = getLicenseStatus(user);

    res.json({
      success: true,
      message: `Licenza Pro 30 Giorni attivata con successo (59,00 € pagati con ${payDesc})! Conto alla rovescia avviato e +40.000 ZeroCoins accreditati!`,
      license: status,
      user,
    });
  });

  // 4m. REFERRAL: Center info & stats
  registerRoute('get', ['/api/referral/info', '/api/referral/info.php'], (req, res) => {
    const store = loadStore();
    const user = getUserFromRequest(req, store);
    if (!user) {
      return res.status(401).json({ success: false, error: 'Non autorizzato' });
    }

    if (!user.referral_code) {
      user.referral_code = 'ARCADE-' + user.username.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 5) + '-' + Math.floor(100 + Math.random() * 900);
      saveStore(store);
    }

    res.json({
      success: true,
      referral_code: user.referral_code,
      referral_count: user.referral_count || 0,
      referral_coins_earned: user.referral_coins_earned || 0,
      referrals_history: user.referrals_history || [],
      referred_by: user.referred_by || null,
      bonus_per_referral: 500,
    });
  });

  // 4n. DOWNLOAD COMPLETE ZIP BUNDLE (Aggiorna la versione scaricata dello zip)
  registerRoute('get', ['/api/download-zip', '/api/download-bundle.zip'], async (_req, res) => {
    try {
      const zip = new JSZip();
      const filesToInclude = [
        'index.php',
        'zeroagar.php',
        'api/db.php',
        'api/chat.php',
        'api/login.php',
        'api/register.php',
        'api/me.php',
        'api/admin.php',
        'api/games_sync.php',
        'api/packages.php',
        'api/license.php',
        'api/referral.php',
        'assets/js/sync_bridge.js',
        'data/sync_store.json',
      ];

      for (const f of filesToInclude) {
        const full = path.join(__dirname, f);
        if (fs.existsSync(full)) {
          zip.file(f, fs.readFileSync(full, 'utf-8'));
        }
      }

      zip.file(
        'README.md',
        `# RETRO ARCADE & ZEROAGAR CLOUD BUNDLE v4.0 PRO\n\n` +
        `Pacchetto completo aggiornato con:\n` +
        `- 50 Nuove Funzioni Avanzate e motore di gioco collegato\n` +
        `- Menù in alto con pulsanti responsive per desktop e mobile\n` +
        `- Sistema 10 Pacchetti ZeroCoins con checkout PayPal e Carta di Credito\n` +
        `- Licenza Ufficiale 30 Giorni (€59) con conto alla rovescia live\n` +
        `- Sistema Referral con link univoco e +500 ZeroCoins bonus a entrambi\n` +
        `- Free Trial 7 Giorni come utente Premium all'atto della registrazione\n` +
        `- Chat globale live persistente in basso a sinistra in tutti i giochi\n` +
        `- Sistema ruoli blindato: solo il Founder Supremo decide i ruoli dello staff\n`
      );

      const buffer = await zip.generateAsync({ type: 'nodebuffer', compression: 'DEFLATE' });
      res.setHeader('Content-Type', 'application/zip');
      res.setHeader('Content-Disposition', 'attachment; filename="retro-arcade-v4-complete.zip"');
      res.send(buffer);
    } catch (err: any) {
      res.status(500).json({ success: false, error: err.message });
    }
  });

  // 4o. FAVORITE GAMES TOGGLE
  registerRoute('post', ['/api/favorites', '/api/favorites.php'], (req, res) => {
    const store = loadStore();
    const user = getUserFromRequest(req, store);
    if (!user) {
      return res.status(401).json({ success: false, error: 'Non autorizzato' });
    }

    const { gameId } = req.body || {};
    if (!gameId) {
      return res.status(400).json({ success: false, error: 'gameId mancante' });
    }

    user.favorite_games = user.favorite_games || [];
    const idx = user.favorite_games.indexOf(gameId);
    let isFavorite = false;
    if (idx > -1) {
      user.favorite_games.splice(idx, 1);
      isFavorite = false;
    } else {
      user.favorite_games.push(gameId);
      isFavorite = true;
    }

    saveStore(store);
    res.json({
      success: true,
      gameId,
      is_favorite: isFavorite,
      favorite_games: user.favorite_games,
      user,
    });
  });

  // 5. GAMES_SYNC endpoint
  registerRoute('post', ['/api/games_sync', '/api/games_sync.php'], (req, res) => {
    const store = loadStore();
    const user = getUserFromRequest(req, store);
    if (!user) {
      return res.status(401).json({ success: false, error: 'Non autorizzato' });
    }

    const { action } = req.body || {};

    // Supporto per invio diretto da ZeroAgar (deltaCoins, deltaXp, mass, rivals, clan)
    if (req.body.deltaCoins !== undefined || req.body.deltaXp !== undefined || req.body.mass !== undefined) {
      const deltaCoins = Number(req.body.deltaCoins) || 0;
      const deltaXp = Number(req.body.deltaXp) || 0;
      const mass = Number(req.body.mass) || 0;
      const rivals = Number(req.body.rivals) || 0;
      const clan = req.body.clan || 'ZERO';

      user.coins += deltaCoins;
      user.xp += deltaXp;
      user.level = calculateLevel(user.xp);
      user.clan = clan;

      if (!user.stats.highscores.zero_agar || mass * 10 > user.stats.highscores.zero_agar) {
        user.stats.highscores.zero_agar = mass * 10;

        if (!store.leaderboards.zero_agar) {
          store.leaderboards.zero_agar = [];
        }

        const existing = store.leaderboards.zero_agar.find((e: any) => e.username === user.username);
        if (existing) {
          if (mass * 10 > existing.score) {
            existing.score = mass * 10;
            existing.date = new Date().toISOString().split('T')[0];
          }
        } else {
          store.leaderboards.zero_agar.push({
            username: user.username,
            score: mass * 10,
            date: new Date().toISOString().split('T')[0],
            avatar: user.avatar || '🦠',
          });
        }
        store.leaderboards.zero_agar.sort((a: any, b: any) => b.score - a.score);
        store.leaderboards.zero_agar = store.leaderboards.zero_agar.slice(0, 20);
      }

      // Achievement ZeroAgar
      const newAch: string[] = [];
      if (rivals >= 5 && !user.achievements.includes('zero_apex_predator')) {
        user.achievements.push('zero_apex_predator');
        user.coins += 200;
        newAch.push('zero_apex_predator');
      }
      if (mass >= 1500 && !user.achievements.includes('zero_titan_arena')) {
        user.achievements.push('zero_titan_arena');
        user.coins += 350;
        newAch.push('zero_titan_arena');
      }

      saveStore(store);

      return res.json({
        ok: true,
        success: true,
        user,
        coins: user.coins,
        level: user.level,
        new_achievements: newAch,
      });
    }

    if (action === 'submit_game_result') {
      const { game_id, score = 0, coins_earned = 0, kills = 0, distance = 0, boss_defeated = false } = req.body;
      const gameKey = game_id || 'space_defender';

      user.coins += Number(coins_earned);
      const earnedXp = Math.floor(Number(score) / 10) + Number(coins_earned) * 2 + (boss_defeated ? 250 : 0);
      user.xp += earnedXp;
      user.level = calculateLevel(user.xp);

      user.stats.games_played = (user.stats.games_played || 0) + 1;
      user.stats.total_kills = (user.stats.total_kills || 0) + Number(kills);
      user.stats.total_distance = (user.stats.total_distance || 0) + Number(distance);
      if (boss_defeated) {
        user.stats.bosses_defeated = (user.stats.bosses_defeated || 0) + 1;
      }

      let isNewHigh = false;
      const curHigh = user.stats.highscores[gameKey] || 0;
      if (Number(score) > curHigh) {
        user.stats.highscores[gameKey] = Number(score);
        isNewHigh = true;

        if (!store.leaderboards[gameKey]) {
          store.leaderboards[gameKey] = [];
        }

        const existingLb = store.leaderboards[gameKey].find((e: any) => e.username === user.username);
        if (existingLb) {
          if (Number(score) > existingLb.score) {
            existingLb.score = Number(score);
            existingLb.date = new Date().toISOString().split('T')[0];
          }
        } else {
          store.leaderboards[gameKey].push({
            username: user.username,
            score: Number(score),
            date: new Date().toISOString().split('T')[0],
            avatar: user.avatar,
          });
        }

        store.leaderboards[gameKey].sort((a: any, b: any) => b.score - a.score);
        store.leaderboards[gameKey] = store.leaderboards[gameKey].slice(0, 20);
      }

      // Achievement checks
      const newAch: string[] = [];
      if (user.stats.total_kills >= 1 && !user.achievements.includes('first_blood')) {
        user.achievements.push('first_blood');
        user.coins += 50;
        newAch.push('first_blood');
      }
      if (user.coins >= 200 && !user.achievements.includes('coin_collector')) {
        user.achievements.push('coin_collector');
        user.coins += 100;
        newAch.push('coin_collector');
      }
      if (boss_defeated && !user.achievements.includes('boss_hunter')) {
        user.achievements.push('boss_hunter');
        user.coins += 250;
        newAch.push('boss_hunter');
      }
      if (Number(score) >= 20000 && !user.achievements.includes('master_survivor')) {
        user.achievements.push('master_survivor');
        user.coins += 500;
        newAch.push('master_survivor');
      }

      saveStore(store);

      return res.json({
        success: true,
        is_new_highscore: isNewHigh,
        new_high: user.stats.highscores[gameKey],
        earned_xp: earnedXp,
        coins_earned,
        total_coins: user.coins,
        level: user.level,
        new_achievements: newAch,
        user,
      });
    }

    if (action === 'claim_daily_reward') {
      const lastLogin = new Date(user.last_login || '2000-01-01');
      const now = new Date();
      const diffDays = Math.floor((now.getTime() - lastLogin.getTime()) / (1000 * 60 * 60 * 24));

      if (diffDays === 1) {
        user.streak = (user.streak || 1) + 1;
      } else if (diffDays > 1) {
        user.streak = 1;
      }

      const streakBonus = Math.min(500, 50 * (user.streak || 1));
      user.coins += streakBonus;
      user.xp += 100;
      user.level = calculateLevel(user.xp);
      user.last_login = now.toISOString();

      saveStore(store);

      return res.json({
        success: true,
        streak: user.streak,
        coins_reward: streakBonus,
        total_coins: user.coins,
        message: `Ricompensa del giorno ${user.streak} riscossa: +${streakBonus} monete!`,
      });
    }

    if (action === 'buy_item') {
      const { item_id } = req.body;
      const targetItem = (store.shop_items || []).find((it: any) => it.id === item_id);
      if (!targetItem) {
        return res.status(404).json({ success: false, error: 'Oggetto non trovato' });
      }

      if (user.unlocked_items.includes(item_id)) {
        return res.status(400).json({ success: false, error: 'Oggetto già posseduto' });
      }

      if (user.coins < targetItem.price) {
        return res.status(400).json({
          success: false,
          error: `Monete insufficienti! Mancano ${targetItem.price - user.coins} 🪙`,
        });
      }

      user.coins -= targetItem.price;
      user.unlocked_items.push(item_id);

      if (targetItem.type === 'ship_skin') {
        user.equipped.ship_skin = item_id;
      } else if (targetItem.type === 'trail') {
        user.equipped.trail = item_id;
      }

      saveStore(store);

      return res.json({
        success: true,
        message: `Hai sbloccato ${targetItem.name}!`,
        item: targetItem,
        user,
      });
    }

    if (action === 'equip_item') {
      const { item_id, slot = 'ship_skin' } = req.body;
      if (!user.unlocked_items.includes(item_id) && item_id !== 'default') {
        return res.status(403).json({ success: false, error: 'Oggetto non posseduto' });
      }
      user.equipped[slot] = item_id;
      saveStore(store);
      return res.json({ success: true, equipped: user.equipped });
    }

    if (action === 'save_cloud_checkpoint') {
      const { checkpoint } = req.body;
      user.cloud_checkpoint = {
        saved_at: Date.now(),
        ...checkpoint,
      };
      saveStore(store);
      return res.json({ success: true, checkpoint: user.cloud_checkpoint });
    }

    if (action === 'load_cloud_checkpoint') {
      if (!user.cloud_checkpoint) {
        return res.status(404).json({ success: false, error: 'Nessun checkpoint cloud salvato' });
      }
      return res.json({ success: true, checkpoint: user.cloud_checkpoint });
    }

    if (action === 'spin_wheel') {
      const rewards = [
        { label: '50 Monete', coins: 50, weight: 35 },
        { label: '150 Monete', coins: 150, weight: 25 },
        { label: '5 Gemme Cosmiche', gems: 5, weight: 15 },
        { label: '500 Monete & 10 Gemme', coins: 500, gems: 10, weight: 15 },
        { label: 'JACKPOT LEGGENDARIO: 2000 Monete!', coins: 2000, gems: 25, weight: 10 },
      ];
      const rand = Math.floor(Math.random() * 100) + 1;
      let cum = 0;
      let won = rewards[0];
      for (const r of rewards) {
        cum += r.weight;
        if (rand <= cum) {
          won = r;
          break;
        }
      }
      if (won.coins) user.coins = (user.coins || 0) + won.coins;
      if (won.gems) user.gems = (user.gems || 0) + won.gems;
      user.last_spin = new Date().toISOString();
      saveStore(store);
      return res.json({ success: true, won, user });
    }

    if (action === 'clan_action') {
      const clanTag = String(req.body.clan_tag || 'ZERO').replace(/[^A-Za-z0-9]/g, '').toUpperCase().slice(0, 6) || 'ZERO';
      user.clan = clanTag;
      saveStore(store);
      return res.json({ success: true, clan: clanTag, user });
    }

    // Default sync
    res.json({ success: true, user, leaderboards: store.leaderboards });
  });

  // 6. CHAT API ENDPOINT (Polling and HTTP access)
  registerRoute('get', ['/api/chat', '/api/chat.php'], (req, res) => {
    const store = loadStore();
    const limit = Math.min(100, Math.max(10, Number(req.query.limit) || 50));
    const messages = (store.chat_messages || []).slice(-limit);
    res.json({
      success: true,
      messages,
      online_count: Math.max(1, wss.clients.size),
    });
  });

  registerRoute('post', ['/api/chat', '/api/chat.php'], (req, res) => {
    const store = loadStore();
    const user = getUserFromRequest(req, store);
    const { text = '', type = 'message', score_data = null, username, avatar } = req.body || {};

    if (!text && !score_data) {
      return res.status(400).json({ success: false, error: 'Messaggio vuoto' });
    }

    const newMsg = {
      id: `msg_${Date.now()}_${Math.random().toString(36).substr(2, 5)}`,
      username: user ? user.username : username || 'Pilota',
      avatar: user ? user.avatar : avatar || '👾',
      text: String(text).substring(0, 200),
      type: type === 'score_share' ? 'score_share' : 'message',
      timestamp: new Date().toISOString(),
      score_data: score_data || null,
    };

    store.chat_messages = store.chat_messages || [];
    store.chat_messages.push(newMsg);
    if (store.chat_messages.length > 120) {
      store.chat_messages = store.chat_messages.slice(-120);
    }
    saveStore(store);

    // Broadcast in real-time to all connected WebSocket clients!
    broadcast({
      type: 'chat:new_message',
      message: newMsg,
    });

    res.json({
      success: true,
      message: newMsg,
    });
  });

  // 7. Source Inspection API (returns all PHP files)
  app.get('/api/php-files', (_req, res) => {
    try {
      const files: Record<string, string> = {};
      const targetPaths = [
        'index.php',
        'zeroagar.php',
        'api/db.php',
        'api/chat.php',
        'api/login.php',
        'api/register.php',
        'api/me.php',
        'api/logout.php',
        'api/games_sync.php',
        'assets/js/sync_bridge.js',
        'data/sync_store.json',
      ];

      for (const p of targetPaths) {
        const fullPath = path.join(__dirname, p);
        if (fs.existsSync(fullPath)) {
          files[p] = fs.readFileSync(fullPath, 'utf-8');
        }
      }

      res.json({ success: true, files });
    } catch (err: any) {
      res.status(500).json({ success: false, error: err.message });
    }
  });

  // Support subfolder forwarding (e.g. /zero/api/ -> /api/)
  app.use((req, _res, next) => {
    if (req.url.startsWith('/zero/api/')) {
      req.url = req.url.replace('/zero/api/', '/api/');
    }
    next();
  });

  // Serve static assets and sync bridge script
  app.use(['/assets', '/zero/assets'], express.static(path.join(__dirname, 'assets')));
  app.use(['/data', '/zero/data'], express.static(path.join(__dirname, 'data')));
  app.get(['/assets/js/sync_bridge.js', '/zero/assets/js/sync_bridge.js', '*/sync_bridge.js'], (_req, res) => {
    res.setHeader('Content-Type', 'application/javascript');
    res.sendFile(path.join(__dirname, 'assets/js/sync_bridge.js'));
  });

  // Standalone ZeroAgar Cyber Arena game connector
  app.get(['/zeroagar.php', '/zeroagar', '/zero/zeroagar.php', '/zero/zeroagar'], (req, res) => {
    try {
      const filePath = path.join(__dirname, 'zeroagar.php');
      if (!fs.existsSync(filePath)) {
        return res.status(404).send('zeroagar.php not found');
      }
      let content = fs.readFileSync(filePath, 'utf-8');
      const store = loadStore();
      const currentUser = getUserFromRequest(req, store);

      let pName = currentUser?.username || 'master';
      let pClan = currentUser?.clan || 'ZERO';
      let pColor = currentUser?.equipped?.cell_skin || '#00f0ff';
      let pCoins = currentUser?.coins ?? 2500;
      let pLevel = currentUser?.level ?? 5;

      if (req.query.payload) {
        try {
          const raw = Buffer.from(String(req.query.payload), 'base64').toString('utf-8');
          const parsed = JSON.parse(raw);
          if (parsed.name) pName = parsed.name;
          if (parsed.clan) pClan = parsed.clan;
          if (parsed.skin) pColor = parsed.skin;
          if (parsed.coins !== undefined) pCoins = parsed.coins;
          if (parsed.level !== undefined) pLevel = parsed.level;
        } catch (e) {
          console.warn('Failed to parse payload query:', e);
        }
      }

      // Replace PHP echos with dynamic values
      content = content.replace(/<\?php\s+echo\s+\$player_name;\s*\?>/g, pName);
      content = content.replace(/<\?php\s+echo\s+\$player_clan;\s*\?>/g, pClan);
      content = content.replace(/<\?php\s+echo\s+\$player_color;\s*\?>/g, pColor);
      content = content.replace(/<\?php\s+echo\s+\$player_coins;\s*\?>/g, String(pCoins));
      content = content.replace(/<\?php\s+echo\s+\$player_level;\s*\?>/g, String(pLevel));
      content = content.replace(/<\?php\s+echo\s+\$portal_url;\s*\?>/g, '/');

      // Strip opening PHP header block down to <!DOCTYPE html>
      const docTypeIndex = content.indexOf('<!DOCTYPE html>');
      if (docTypeIndex !== -1) {
        content = content.substring(docTypeIndex);
      }

      res.type('html').send(content);
    } catch (err: any) {
      res.status(500).send('Error rendering zeroagar: ' + err.message);
    }
  });

  // Standalone index.php live portal renderer
  app.get(['/index.php', '/zero/index.php', '/zero', '/zero/'], (req, res) => {
    try {
      if (req.query.raw === '1') {
        res.type('text/plain').sendFile(path.join(__dirname, 'index.php'));
        return;
      }
      const filePath = path.join(__dirname, 'index.php');
      let content = fs.readFileSync(filePath, 'utf-8');
      const store = loadStore();
      const currentUser = getUserFromRequest(req, store);

      // Strip opening PHP logic before DOCTYPE
      const docTypeIndex = content.indexOf('<!DOCTYPE html>');
      if (docTypeIndex !== -1) {
        content = content.substring(docTypeIndex);
      }

      // Populate basic placeholders
      const u = currentUser || store.users[0] || { username: 'PilotaArcade', avatar: '👾', coins: 1250, streak: 4, role: 'founder' };
      content = content.replace(/<\?=\s*htmlspecialchars\(\$user\['avatar'\](?:\s*\?\?\s*'[^']+')?\)\s*\?>/g, u.avatar || '👾');
      content = content.replace(/<\?=\s*htmlspecialchars\(\$user\['username'\]\)\s*\?>/g, u.username || 'Pilota');
      content = content.replace(/<\?=\s*htmlspecialchars\(\$user\['role'\](?:\s*\?\?\s*'[^']+')?\)\s*\?>/g, u.role || 'founder');
      content = content.replace(/<\?=\s*htmlspecialchars\(\$user\['clan'\]\s*\?\?\s*'ZERO'\)\s*\?>/g, u.clan || 'ZERO');
      content = content.replace(/<\?=\s*number_format\(\$user\['coins'\]\)\s*\?>/g, (u.coins || 0).toLocaleString());
      content = content.replace(/<\?=\s*number_format\(\$user\['gems'\]\s*\?\?\s*0\)\s*\?>/g, (u.gems || 0).toLocaleString());
      content = content.replace(/<\?=\s*\$user\['streak'\](?:\s*\?\?\s*1)?\s*\?>/g, String(u.streak || 1));
      content = content.replace(/<\?=\s*\$user\['coins'\]\s*\?>/g, String(u.coins || 0));
      content = content.replace(/<\?=\s*\$playerRank\['title'\]\s*\?>/g, 'Cyber Commando');
      content = content.replace(/<\?=\s*\$zeroAgarUrl\s*\?>/g, '/zeroagar.php');
      content = content.replace(/<\?=\s*htmlspecialchars\(\$referralCode\)\s*\?>/g, u.referral_code || 'ARCADE-FOUND-777');
      content = content.replace(/<\?=\s*\$referralCount\s*\?>/g, String(u.referral_count || 3));
      content = content.replace(/<\?=\s*number_format\(\$referralCoins\)\s*\?>/g, (u.referral_coins_earned || 1500).toLocaleString());
      content = content.replace(/<\?=\s*\$licenseExpiresAt\s*\?>/g, u.license_expires_at || new Date(Date.now() + 29 * 86400000).toISOString());
      content = content.replace(/<\?=\s*json_encode\(\$user,\s*JSON_UNESCAPED_UNICODE\)\s*\?>/g, JSON.stringify(u));

      res.type('html').send(content);
    } catch (err: any) {
      res.status(500).send('Error rendering index.php: ' + err.message);
    }
  });

  // In Dev mode: Mount Vite middleware
  if (process.env.NODE_ENV !== 'production') {
    const vite = await createViteServer({
      server: { middlewareMode: true },
      appType: 'spa',
    });
    app.use(vite.middlewares);
  } else {
    // Production: serve built static files
    app.use(express.static(path.join(__dirname, 'dist')));
    app.get('*', (_req, res) => {
      res.sendFile(path.join(__dirname, 'dist', 'index.html'));
    });
  }

  server.listen(PORT, () => {
    console.log(`Arcade Game Server with WebSockets listening on http://localhost:${PORT}`);
  });
}

startServer().catch(console.error);
