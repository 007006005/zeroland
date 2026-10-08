import React, { useState } from 'react';
import { UserAccount, UserRole } from '../types/arcade';
import { X, User, Key, UserPlus, LogIn, Sparkles, CheckCircle2, Shield, Crown, Star, AlertCircle, HelpCircle } from 'lucide-react';
import { api } from '../utils/api';
import { sound } from '../utils/audio';

interface AccountModalProps {
  isOpen: boolean;
  onClose: () => void;
  user: UserAccount | null;
  onUserUpdate: (u: UserAccount) => void;
}

export const AccountModal: React.FC<AccountModalProps> = ({
  isOpen,
  onClose,
  user,
  onUserUpdate,
}) => {
  const [mode, setMode] = useState<'login' | 'register' | 'demo_roles'>('login');
  const [username, setUsername] = useState<string>('');
  const [password, setPassword] = useState<string>('');
  const [email, setEmail] = useState<string>('');
  const [avatar, setAvatar] = useState<string>('👾');
  const [loading, setLoading] = useState<boolean>(false);
  const [message, setMessage] = useState<{ text: string; type: 'success' | 'error' } | null>(null);

  if (!isOpen) return null;

  const avatars = ['👾', '🚀', '⚡', '🤖', '🦊', '🐱', '🕹️', '👑', '🔮', '🛡️', '⭐', '💎'];

  const demoAccounts = [
    { username: 'Founder_Retro', role: 'founder' as UserRole, label: 'Founder Supremo', badge: '👑 FOUNDER', color: 'from-amber-500 to-yellow-400 text-black' },
    { username: 'Admin_Nexus', role: 'admin' as UserRole, label: 'Amministratore', badge: '🛡️ ADMIN', color: 'from-red-600 to-cyan-600 text-white' },
    { username: 'Mod_Guardian', role: 'moderatore' as UserRole, label: 'Moderatore Staff', badge: '⚖️ MOD', color: 'from-indigo-600 to-purple-600 text-white' },
    { username: 'Helper_Guide', role: 'helper' as UserRole, label: 'Helper Supporto', badge: '💡 HELPER', color: 'from-emerald-600 to-teal-600 text-white' },
    { username: 'VIP_CyberStar', role: 'premium' as UserRole, label: 'Utente Premium VIP', badge: '⭐ VIP', color: 'from-yellow-400 to-amber-500 text-black' },
    { username: 'RetroPlayer', role: 'user' as UserRole, label: 'Utente Standard', badge: '🎮 USER', color: 'from-cyan-600 to-blue-600 text-white' },
  ];

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!username.trim()) return;
    setLoading(true);
    setMessage(null);
    try {
      const res = await api.login(username.trim(), password.trim() || undefined);
      if (res && res.success && res.user) {
        sound.playPowerup();
        onUserUpdate(res.user);
        setMessage({ text: 'Accesso effettuato con successo!', type: 'success' });
        setTimeout(() => {
          onClose();
        }, 1000);
      } else {
        setMessage({ text: res.error || 'Errore durante l\'accesso', type: 'error' });
      }
    } catch {
      setMessage({ text: 'Errore di connessione', type: 'error' });
    } finally {
      setLoading(false);
    }
  };

  const handleRegister = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!username.trim()) return;
    setLoading(true);
    setMessage(null);
    try {
      const res = await api.register(username.trim(), avatar, email.trim() || undefined, password.trim() || undefined);
      if (res && res.success && res.user) {
        sound.playPowerup();
        onUserUpdate(res.user);
        setMessage({ text: 'Account creato con 300 monete e 10 gemme di benvenuto!', type: 'success' });
        setTimeout(() => {
          onClose();
        }, 1200);
      } else {
        setMessage({ text: res.error || 'Errore creazione account', type: 'error' });
      }
    } catch {
      setMessage({ text: 'Errore di registrazione', type: 'error' });
    } finally {
      setLoading(false);
    }
  };

  const handleGuestLogin = async () => {
    setLoading(true);
    try {
      const res = await api.continueAsGuest();
      if (res && res.success && res.user) {
        sound.playCoin();
        onUserUpdate(res.user);
        setMessage({ text: 'Sessione Ospite attivata! Puoi giocare liberamente.', type: 'success' });
        setTimeout(() => {
          onClose();
        }, 800);
      }
    } catch {
      setMessage({ text: 'Errore accesso ospite', type: 'error' });
    } finally {
      setLoading(false);
    }
  };

  const handleDemoLogin = async (demoUsername: string) => {
    setLoading(true);
    try {
      const res = await api.login(demoUsername);
      if (res && res.success && res.user) {
        sound.playPowerup();
        onUserUpdate(res.user);
        setMessage({ text: `Accesso come ${res.user.role.toUpperCase()} completato!`, type: 'success' });
        setTimeout(() => {
          onClose();
        }, 800);
      } else {
        setMessage({ text: res.error || 'Errore accesso rapido', type: 'error' });
      }
    } catch {
      setMessage({ text: 'Errore accesso rapido', type: 'error' });
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
      <div className="bg-slate-900 border border-cyan-500/40 rounded-3xl max-w-lg w-full p-6 space-y-6 shadow-2xl relative animate-scaleUp">
        <button
          onClick={onClose}
          className="absolute top-4 right-4 p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition"
        >
          <X className="w-5 h-5" />
        </button>

        {/* Intestazione */}
        <div className="space-y-1">
          <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-cyan-500/10 border border-cyan-500/30 text-cyan-300 text-xs font-cyber font-bold">
            <User className="w-3.5 h-3.5" />
            AUTENTICAZIONE &amp; GESTIONE RUOLI
          </div>
          <h3 className="text-xl font-bold font-cyber text-white">
            {mode === 'login' ? 'Accedi al tuo Account' : mode === 'register' ? 'Registra Nuovo Pilota' : 'Cambia Ruolo Rapido (Demo Staff)'}
          </h3>
          <p className="text-xs text-slate-400">
            Supporta tutti i ruoli: Founder, Admin, Moderatore, Helper, Premium VIP, User e Ospite.
          </p>
        </div>

        {/* Tabs Modalità */}
        <div className="grid grid-cols-3 gap-1 bg-slate-950 p-1 rounded-xl border border-slate-800 text-xs font-cyber font-bold">
          <button
            type="button"
            onClick={() => { setMode('login'); setMessage(null); }}
            className={`py-1.5 rounded-lg transition ${
              mode === 'login' ? 'bg-cyan-500 text-black shadow' : 'text-slate-400 hover:text-white'
            }`}
          >
            Accedi
          </button>
          <button
            type="button"
            onClick={() => { setMode('register'); setMessage(null); }}
            className={`py-1.5 rounded-lg transition ${
              mode === 'register' ? 'bg-cyan-500 text-black shadow' : 'text-slate-400 hover:text-white'
            }`}
          >
            Registrati
          </button>
          <button
            type="button"
            onClick={() => { setMode('demo_roles'); setMessage(null); }}
            className={`py-1.5 rounded-lg transition ${
              mode === 'demo_roles' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-white'
            }`}
          >
            Ruoli Demo
          </button>
        </div>

        {message && (
          <div
            className={`p-3 rounded-xl border text-xs font-cyber flex items-center gap-2 ${
              message.type === 'success'
                ? 'bg-emerald-950/80 border-emerald-500/50 text-emerald-300'
                : 'bg-red-950/80 border-red-500/50 text-red-300'
            }`}
          >
            {message.type === 'success' ? <CheckCircle2 className="w-4 h-4 text-emerald-400" /> : <AlertCircle className="w-4 h-4 text-red-400" />}
            <span>{message.text}</span>
          </div>
        )}

        {/* MODALITÀ 1: LOGIN */}
        {mode === 'login' && (
          <form onSubmit={handleLogin} className="space-y-4">
            <div className="space-y-1.5">
              <label className="text-xs font-cyber text-slate-300">Nome Pilota o Email</label>
              <input
                type="text"
                required
                value={username}
                onChange={(e) => setUsername(e.target.value)}
                placeholder="Es. Founder_Retro o il tuo nick"
                className="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white font-cyber text-xs focus:outline-none focus:border-cyan-400"
              />
            </div>

            <div className="space-y-1.5">
              <label className="text-xs font-cyber text-slate-300">Password</label>
              <input
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                placeholder="Inserisci la tua password"
                className="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white font-cyber text-xs focus:outline-none focus:border-cyan-400"
              />
            </div>

            <div className="pt-2 space-y-2">
              <button
                type="submit"
                disabled={loading}
                className="w-full py-2.5 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-black font-cyber font-bold text-xs uppercase tracking-wider shadow-lg shadow-cyan-500/25 transition active:scale-95 disabled:opacity-50"
              >
                {loading ? 'Verifica in corso...' : 'Entra in Partita'}
              </button>

              <button
                type="button"
                onClick={handleGuestLogin}
                className="w-full py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-cyber text-xs uppercase tracking-wider border border-slate-700 transition"
              >
                Continua come Ospite (Senza Registrazione)
              </button>
            </div>
          </form>
        )}

        {/* MODALITÀ 2: REGISTRAZIONE */}
        {mode === 'register' && (
          <form onSubmit={handleRegister} className="space-y-4">
            <div className="space-y-1.5">
              <label className="text-xs font-cyber text-slate-300">Nome Pilota (min. 3 caratteri)</label>
              <input
                type="text"
                required
                minLength={3}
                value={username}
                onChange={(e) => setUsername(e.target.value)}
                placeholder="Es. NeoCyber"
                className="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white font-cyber text-xs focus:outline-none focus:border-cyan-400"
              />
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div className="space-y-1.5">
                <label className="text-xs font-cyber text-slate-300">Password</label>
                <input
                  type="password"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder="Password di sicurezza"
                  className="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white font-cyber text-xs focus:outline-none focus:border-cyan-400"
                />
              </div>

              <div className="space-y-1.5">
                <label className="text-xs font-cyber text-slate-300">Email (facoltativa)</label>
                <input
                  type="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder="pilota@retroarcade.io"
                  className="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white font-cyber text-xs focus:outline-none focus:border-cyan-400"
                />
              </div>
            </div>

            <div className="space-y-2">
              <label className="text-xs font-cyber text-slate-300">Scegli il tuo Avatar</label>
              <div className="flex flex-wrap gap-2">
                {avatars.map((av) => (
                  <button
                    type="button"
                    key={av}
                    onClick={() => setAvatar(av)}
                    className={`w-9 h-9 rounded-xl text-lg flex items-center justify-center transition ${
                      avatar === av
                        ? 'bg-cyan-500/20 border-2 border-cyan-400 shadow-md scale-105'
                        : 'bg-slate-950 border border-slate-800 hover:border-slate-700'
                    }`}
                  >
                    {av}
                  </button>
                ))}
              </div>
            </div>

            <div className="pt-2">
              <button
                type="submit"
                disabled={loading}
                className="w-full py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-black font-cyber font-bold text-xs uppercase tracking-wider shadow-lg shadow-emerald-500/25 transition active:scale-95 disabled:opacity-50"
              >
                {loading ? 'Creazione account...' : 'Crea Account (+300 Monete & +10 Gemme)'}
              </button>
            </div>
          </form>
        )}

        {/* MODALITÀ 3: RUOLI DEMO (ACCESSO RAPIDO) */}
        {mode === 'demo_roles' && (
          <div className="space-y-3">
            <p className="text-xs text-slate-400 font-cyber">
              Seleziona un profilo preconfigurato per testare immediatamente i poteri di amministrazione e le funzionalità di ciascun ruolo:
            </p>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
              {demoAccounts.map((acct) => (
                <button
                  key={acct.username}
                  type="button"
                  onClick={() => handleDemoLogin(acct.username)}
                  className="p-3 rounded-2xl bg-slate-950 border border-slate-800 hover:border-cyan-400/60 hover:bg-slate-900/90 transition text-left flex flex-col justify-between space-y-2 group"
                >
                  <div className="flex items-center justify-between">
                    <span className="font-bold text-white text-xs font-cyber">{acct.label}</span>
                    <span className={`text-[9px] font-cyber px-2 py-0.5 rounded font-bold bg-gradient-to-r ${acct.color}`}>
                      {acct.badge}
                    </span>
                  </div>
                  <div className="text-[11px] text-slate-400 font-mono flex items-center justify-between">
                    <span>User: {acct.username}</span>
                    <span className="text-cyan-400 group-hover:underline">Accedi &rarr;</span>
                  </div>
                </button>
              ))}
            </div>

            <div className="pt-2 border-t border-slate-800">
              <button
                type="button"
                onClick={handleGuestLogin}
                className="w-full py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-cyber text-xs uppercase border border-slate-700 transition flex items-center justify-center gap-1.5"
              >
                <span>👤</span>
                <span>Oppure naviga come Ospite anonimo</span>
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};
