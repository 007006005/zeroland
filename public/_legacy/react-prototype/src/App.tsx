import React, { useState, useEffect } from 'react';
import { UserAccount, ShopItem, Achievement, DailyChallenge, LeaderboardEntry } from './types/arcade';
import { api } from './utils/api';
import { sound } from './utils/audio';
import { Navbar } from './components/Navbar';
import { GamesHub } from './components/GamesHub';
import { UserDashboard } from './components/UserDashboard';
import { AdminPanel } from './components/AdminPanel';
import { PhpInspector } from './components/PhpInspector';
import { ShopModal } from './components/ShopModal';
import { LeaderboardPanel } from './components/LeaderboardPanel';
import { AchievementsPanel } from './components/AchievementsPanel';
import { DailyQuestsModal } from './components/DailyQuestsModal';
import { AccountModal } from './components/AccountModal';
import { ArcadeChat } from './components/ArcadeChat';
import confetti from 'canvas-confetti';
import { Sparkles, Trophy, CheckCircle2, Flame, Shield } from 'lucide-react';

export default function App() {
  const [user, setUser] = useState<UserAccount | null>(null);
  const [shopItems, setShopItems] = useState<ShopItem[]>([]);
  const [achievementsCatalog, setAchievementsCatalog] = useState<Achievement[]>([]);
  const [leaderboards, setLeaderboards] = useState<Record<string, LeaderboardEntry[]>>({});
  const [dailyChallenges, setDailyChallenges] = useState<DailyChallenge[]>([]);

  const [activeTab, setActiveTab] = useState<'games' | 'dashboard' | 'php_inspector' | 'shop' | 'leaderboard' | 'achievements'>('games');
  const [isMuted, setIsMuted] = useState<boolean>(false);
  const [isDailyOpen, setIsDailyOpen] = useState<boolean>(false);
  const [isAccountOpen, setIsAccountOpen] = useState<boolean>(false);
  const [isAdminOpen, setIsAdminOpen] = useState<boolean>(false);

  // Notifiche toast in tempo reale
  const [toast, setToast] = useState<{
    title: string;
    description: string;
    icon?: string;
  } | null>(null);

  useEffect(() => {
    fetchInitialData();

    // Listener per SSO cross-domain e postMessage sync da master-elettroerosioni.it
    const handleWindowMessage = (event: MessageEvent) => {
      if (!event.data) return;
      if (event.data.type === 'ZEROARCADE_AUTH_SYNC' && event.data.user) {
        setUser((prev) => prev ? { ...prev, username: event.data.user.name, coins: event.data.user.coins } : prev);
        showToast('SINCRONIZZAZIONE SSO', `Account collegato: ${event.data.user.name}`, '🔄');
      } else if (event.data.type === 'ZEROARCADE_GAME_SYNC' && event.data.data) {
        const d = event.data.data;
        handleGameComplete('zero_agar', {
          score: (d.mass || 0) * 10,
          coinsEarned: d.deltaCoins || 0,
          kills: d.rivals || 0,
        });
      }
    };
    window.addEventListener('message', handleWindowMessage);
    return () => window.removeEventListener('message', handleWindowMessage);
  }, []);

  const handleShareInChat = async (text: string, scoreData: any) => {
    if (!user) return;
    try {
      await fetch('/api/chat', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          type: 'score_share',
          text,
          username: user.username,
          avatar: user.avatar,
          score_data: scoreData,
          role: user.role,
        }),
      });
      showToast('CONDIVISO IN CHAT', 'Il tuo record è visibile a tutti i piloti!', '💬');
    } catch (err) {
      console.error('Error sharing in chat:', err);
    }
  };

  const fetchInitialData = async () => {
    try {
      const data = await api.getMe();
      if (data && data.success) {
        setUser(data.user);
        setShopItems(data.shop_items);
        setAchievementsCatalog(data.achievements_catalog);
        setLeaderboards(data.leaderboards);
        setDailyChallenges(data.daily_challenges);
      }
    } catch (err) {
      console.error('Error fetching initial data:', err);
    }
  };

  const showToast = (title: string, description: string, icon = '🏆') => {
    setToast({ title, description, icon });
    setTimeout(() => {
      setToast(null);
    }, 4500);
  };

  const toggleMute = () => {
    const nextMute = !isMuted;
    setIsMuted(nextMute);
    sound.setMuted(nextMute);
  };

  const handleLogout = async () => {
    localStorage.removeItem('arcade_session_token');
    // Passa a modalità ospite
    try {
      const res = await api.continueAsGuest();
      if (res && res.success && res.user) {
        setUser(res.user);
        showToast('DISCONNESSO', 'Sessione terminata. Ora stai navigando come Ospite.', '👋');
      }
    } catch {
      setUser(null);
    }
    setActiveTab('games');
  };

  // Callback completamento partita da qualsiasi gioco arcade
  const handleGameComplete = async (
    gameId: string,
    result: {
      score: number;
      coinsEarned: number;
      kills?: number;
      distance?: number;
      bossDefeated?: boolean;
    }
  ) => {
    try {
      const res = await api.submitGameResult(gameId, result);
      if (res && res.success) {
        setUser(res.user);

        if (res.is_new_highscore) {
          sound.playPowerup();
          confetti({ particleCount: 70, spread: 60, origin: { y: 0.7 } });
          showToast(
            'NUOVO RECORD PERSONALE!',
            `Hai stabilito un nuovo record di ${res.new_high.toLocaleString()} punti!`,
            '🔥'
          );
        }

        if (res.new_achievements && res.new_achievements.length > 0) {
          sound.playCoin();
          confetti({ particleCount: 80, spread: 80, origin: { y: 0.5 } });
          res.new_achievements.forEach((achId) => {
            const achDef = achievementsCatalog.find((a) => a.id === achId);
            showToast(
              'OBIETTIVO SBLOCCATO!',
              achDef ? `${achDef.title}: +${achDef.reward_coins} monete!` : 'Nuovo trofeo sbloccato!',
              '🏆'
            );
          });
        }
      }
    } catch (err) {
      console.error('Error submitting game result:', err);
    }
  };

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100 flex flex-col font-sans selection:bg-cyan-500 selection:text-black">
      {/* Top Navigation */}
      <Navbar
        user={user}
        activeTab={activeTab}
        setActiveTab={setActiveTab}
        isMuted={isMuted}
        toggleMute={toggleMute}
        openDailyModal={() => setIsDailyOpen(true)}
        openAccountModal={() => setIsAccountOpen(true)}
        openAdminPanel={() => setIsAdminOpen(true)}
      />

      {/* Main Content Area */}
      <main className="flex-1 max-w-7xl w-full mx-auto px-4 py-6">
        {activeTab === 'games' && (
          <GamesHub
            user={user}
            onUserUpdate={(updated) => setUser(updated)}
            onGameComplete={handleGameComplete}
            onShareInChat={handleShareInChat}
          />
        )}

        {activeTab === 'dashboard' && (
          <UserDashboard
            user={user}
            onUserUpdate={(updated) => setUser(updated)}
            onLogout={handleLogout}
            openAccountModal={() => setIsAccountOpen(true)}
            openAdminPanel={() => setIsAdminOpen(true)}
          />
        )}

        {activeTab === 'php_inspector' && <PhpInspector />}

        {activeTab === 'shop' && (
          <ShopModal
            user={user}
            shopItems={shopItems}
            onUserUpdate={(updated) => setUser(updated)}
          />
        )}

        {activeTab === 'leaderboard' && (
          <LeaderboardPanel
            leaderboards={leaderboards}
            currentUsername={user?.username}
          />
        )}

        {activeTab === 'achievements' && (
          <AchievementsPanel
            user={user}
            achievementsCatalog={achievementsCatalog}
          />
        )}
      </main>

      {/* Toast Notification */}
      {toast && (
        <div className="fixed bottom-6 right-6 z-50 bg-slate-900 border border-cyan-500/50 rounded-2xl p-4 shadow-[0_0_25px_rgba(6,182,212,0.3)] max-w-sm flex items-start gap-3 animate-slideUp">
          <div className="w-10 h-10 rounded-xl bg-cyan-500/20 border border-cyan-400 flex items-center justify-center text-xl">
            {toast.icon || '🏆'}
          </div>
          <div className="flex-1 space-y-0.5">
            <h4 className="text-xs font-bold font-arcade text-cyan-300">{toast.title}</h4>
            <p className="text-xs text-slate-300 leading-tight">{toast.description}</p>
          </div>
        </div>
      )}

      {/* Modals */}
      <DailyQuestsModal
        isOpen={isDailyOpen}
        onClose={() => setIsDailyOpen(false)}
        user={user}
        dailyChallenges={dailyChallenges}
        onRewardClaimed={(updated) => setUser(updated)}
      />

      <AccountModal
        isOpen={isAccountOpen}
        onClose={() => setIsAccountOpen(false)}
        user={user}
        onUserUpdate={(updated) => setUser(updated)}
      />

      {/* Admin Panel Modal */}
      {isAdminOpen && user && (
        <AdminPanel
          currentUser={user}
          onClose={() => setIsAdminOpen(false)}
          onRefreshUserData={fetchInitialData}
        />
      )}

      {/* Real-time Global Arcade Chat */}
      <ArcadeChat user={user} />

      {/* Footer */}
      <footer className="border-t border-slate-800/80 bg-slate-950 py-6 text-center text-xs font-cyber text-slate-500">
        <div className="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-3">
          <div className="flex items-center gap-2">
            <span>🕹️ Retro Arcade Portal</span>
            <span>&bull;</span>
            <span className="text-cyan-400 font-bold">50 Funzioni PHP &amp; ZeroAgar MMO</span>
            <span>&bull;</span>
            <span className="text-amber-400 font-bold">RBAC Staff &amp; Dashboard</span>
          </div>
          <p className="text-slate-600">
            Founder, Admin, Moderatore, Helper, Premium VIP, User e Ospite senza registrazione
          </p>
        </div>
      </footer>
    </div>
  );
}
