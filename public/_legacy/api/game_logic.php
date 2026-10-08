<?php
/**
 * Logica di gioco condivisa lato server (punteggi, livelli, ricompense, obiettivi).
 * Tutto ciò che assegna monete/XP viene calcolato QUI, mai fidandosi del browser.
 */

const SOUND_PACKS = ['retro_synth', 'chiptune_8bit', 'cyber_overdrive', 'quantum_ambient'];
// Obiettivi che il client può segnalare (eventi di gameplay non verificabili dal server)
const CLIENT_TRUSTED_ACHIEVEMENTS = ['speed_demon', 'zero_split_master'];

function calculate_level($xp): int {
    return (int)max(1, floor(pow(max(0, $xp) / 100, 0.6)) + 1);
}

function achievement_catalog(): array {
    static $cat = null;
    if ($cat === null) {
        $cat = [];
        foreach (db()->query('SELECT * FROM achievements')->fetchAll() as $r) $cat[$r['id']] = $r;
    }
    return $cat;
}

/** Assegna un obiettivo (una sola volta) con le ricompense del catalogo. */
function grant_achievement(array &$u, string $id): ?array {
    $cat = achievement_catalog();
    if (!isset($cat[$id]) || in_array($id, $u['achievements'], true)) return null;
    $a = $cat[$id];
    $u['achievements'][] = $id;
    $u['coins'] += (int)$a['reward_coins'];
    $u['xp']    += (int)$a['reward_xp'];
    $u['level']  = calculate_level($u['xp']);
    return [
        'id' => $id, 'title' => $a['title'], 'icon' => $a['icon'],
        'reward_coins' => (int)$a['reward_coins'], 'reward_xp' => (int)$a['reward_xp'],
    ];
}

/** Controlla gli obiettivi calcolabili dal server. Ritorna quelli appena sbloccati. */
function evaluate_achievements(array &$u, array $ctx = []): array {
    $s = $u['stats'];
    $checks = [
        'first_blood'        => ($s['total_kills'] ?? 0) >= 1,
        'coin_collector'     => ($s['total_coins_earned'] ?? 0) >= 200,
        'boss_hunter'        => ($s['bosses_defeated'] ?? 0) >= 1,
        'master_survivor'    => ($ctx['score'] ?? 0) >= 20000,
        'zero_titan_arena'   => ($s['zero_agar']['peak_mass'] ?? 0) >= 1500,
        'zero_apex_predator' => ($ctx['game_id'] ?? '') === 'zero_agar' && ($ctx['match_rivals'] ?? 0) >= 5,
        'shopaholic'         => ($s['items_purchased'] ?? 0) >= 2,
        'streak_3_days'      => ($u['streak'] ?? 0) >= 3 && !empty($ctx['daily']),
        'streak_7_days'      => ($u['streak'] ?? 0) >= 7 && !empty($ctx['daily']),
    ];
    $new = [];
    foreach ($checks as $id => $ok) {
        if ($ok && ($g = grant_achievement($u, $id)) !== null) $new[] = $g;
    }
    return $new;
}

function update_leaderboard(array $u, string $gameId, int $score): void {
    // score_date prima di score: MySQL valuta gli assegnamenti da sinistra a destra
    db()->prepare(
        'INSERT INTO leaderboards (game_id, user_id, username, avatar, score, score_date)
         VALUES (?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
           score_date = IF(VALUES(score) > score, VALUES(score_date), score_date),
           score = GREATEST(score, VALUES(score)),
           username = VALUES(username),
           avatar = VALUES(avatar)'
    )->execute([$gameId, $u['id'], $u['username'], $u['avatar'], $score, date('Y-m-d')]);
}

/**
 * Registra il risultato di una partita con controlli anti-abuso.
 * Ritorna ['error'=>..] oppure i dati di risposta.
 */
function apply_game_result(array &$u, array $in): array {
    $gameId = (string)($in['game_id'] ?? '');
    if (!in_array($gameId, ARCADE_GAMES, true)) return ['error' => 'Gioco non valido'];

    $final = array_key_exists('final', $in) ? !empty($in['final']) : true;
    $now = time();
    $last = (int)($u['last_submit'][$gameId] ?? 0);
    // Il salvataggio finale a fine partita viene sempre accettato; quelli intermedi hanno un limite di frequenza
    if (!$final && $last && ($now - $last) < 3) return ['error' => 'Troppe richieste: attendi qualche secondo'];

    $elapsed = $last ? min(600, $now - $last) : 600;
    $maxCoins = min(1500, 60 + 12 * $elapsed);

    $score  = max(0, min(5000000, (int)($in['score'] ?? 0)));
    $coinsReq = max(0, (int)($in['coins_earned'] ?? 0));
    $coins  = min($coinsReq, $maxCoins);
    $kills  = max(0, min(200, (int)($in['kills'] ?? 0)));
    $dist   = max(0, min(1000000, (int)($in['distance'] ?? 0)));
    $boss   = !empty($in['boss_defeated']);
    $u['last_submit'][$gameId] = $now;

    $u['coins'] += $coins;
    $earnedXp = ($final ? intdiv($score, 10) : 0) + ($coins * 2) + ($boss && $final ? 200 : 0);
    $u['xp'] += $earnedXp;
    $u['level'] = calculate_level($u['xp']);

    $st = &$u['stats'];
    if ($final) $st['games_played']++;
    $st['total_kills'] += $kills;
    $st['total_distance'] += $dist;
    $st['total_coins_earned'] = ($st['total_coins_earned'] ?? 0) + $coins;
    if ($boss && $final) $st['bosses_defeated']++;

    $isNewHigh = false;
    if ($score > ($st['highscores'][$gameId] ?? 0)) {
        $st['highscores'][$gameId] = $score;
        $isNewHigh = true;
        update_leaderboard($u, $gameId, $score);
    }

    if ($gameId === 'zero_agar') {
        $mass = max(0, min(1000000, (int)($in['mass'] ?? intdiv($score, 10))));
        if ($mass > ($st['zero_agar']['peak_mass'] ?? 0)) $st['zero_agar']['peak_mass'] = $mass;
        $st['zero_agar']['rivals_eaten'] += $kills;
        $st['zero_agar']['food_eaten'] += max(0, min(5000, (int)($in['particles_delta'] ?? 0)));

        $clan = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', (string)($in['clan'] ?? '')), 0, 6));
        if (strlen($clan) >= 2) $u['clan'] = $clan;
        $skin = (string)($in['skin'] ?? '');
        if (preg_match('/^#[0-9a-fA-F]{6}$/', $skin)) $u['equipped']['cell_skin'] = strtolower($skin);
    }
    unset($st);

    return [
        'clamped'      => $coinsReq > $coins,
        'is_new_high'  => $isNewHigh,
        'earned_xp'    => $earnedXp,
        'coins'        => $coins,
        'game_id'      => $gameId,
        'ctx'          => [
            'score' => $score, 'game_id' => $gameId,
            'match_rivals' => max(0, min(500, (int)($in['match_rivals'] ?? $kills))),
        ],
    ];
}

function claim_daily(array &$u): array {
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $lastClaim = $u['last_daily_claim'] ?? null;
    if ($lastClaim === $today) {
        return ['error' => 'Ricompensa giornaliera già riscossa oggi. Torna domani!'];
    }
    $u['streak'] = ($lastClaim === $yesterday) ? $u['streak'] + 1 : 1;
    $bonus = min(500, 50 * $u['streak']);
    $u['coins'] += $bonus;
    $u['xp'] += 100;
    $u['level'] = calculate_level($u['xp']);
    $u['last_daily_claim'] = $today;
    $u['last_login'] = date('c');
    return ['bonus' => $bonus];
}

function spin_wheel_once(array &$u): array {
    if (!empty($u['last_spin']) && substr($u['last_spin'], 0, 10) === date('Y-m-d')) {
        return ['error' => 'Hai già usato la ruota oggi. Torna domani!'];
    }
    $rewards = [
        ['label' => '50 Monete', 'coins' => 50, 'weight' => 35],
        ['label' => '150 Monete', 'coins' => 150, 'weight' => 25],
        ['label' => '5 Gemme Cosmiche', 'gems' => 5, 'weight' => 15],
        ['label' => '500 Monete & 10 Gemme', 'coins' => 500, 'gems' => 10, 'weight' => 15],
        ['label' => 'JACKPOT LEGGENDARIO: 2000 Monete!', 'coins' => 2000, 'gems' => 25, 'weight' => 10],
    ];
    $rand = random_int(1, 100);
    $cum = 0;
    $won = $rewards[0];
    foreach ($rewards as $r) {
        $cum += $r['weight'];
        if ($rand <= $cum) { $won = $r; break; }
    }
    if (isset($won['coins'])) $u['coins'] += $won['coins'];
    if (isset($won['gems']))  $u['gems']  += $won['gems'];
    $u['last_spin'] = date('c');
    return ['won' => $won];
}

/** Filtro chat: pulizia caratteri, parole vietate, scorciatoie emoji (nessun HTML: si esegue l'escape in output). */
function chat_filter_text(string $raw): string {
    $t = preg_replace('/[\x00-\x1F\x7F]/u', '', trim($raw));
    $t = $t === null ? '' : $t;
    foreach (['spam', 'cheat', 'botfarm'] as $bad) $t = str_ireplace($bad, '***', $t);
    $t = str_replace([':gg:', ':fire:', ':rocket:'], ['🎉 GG!', '🔥', '🚀'], $t);
    return mb_substr($t, 0, 140);
}
