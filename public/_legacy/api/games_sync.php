<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/game_logic.php';

$cur = require_login_api();
$in = read_json_post();
$action = (string)($in['action'] ?? 'sync');

// Riga utente bloccata per tutta l'operazione (niente doppi accrediti)
$u = user_lock($cur['id']);
if (!$u) json_response(['success' => false, 'error' => 'Utente non trovato nel database'], 404);

switch ($action) {

    case 'submit_game_result':
        $r = apply_game_result($u, $in);
        if (isset($r['error'])) json_response(['success' => false, 'error' => $r['error']], 429);
        $newAch = evaluate_achievements($u, $r['ctx']);
        user_commit($u);
        json_response([
            'success'          => true,
            'is_new_highscore' => $r['is_new_high'],
            'new_high'         => $u['stats']['highscores'][$r['game_id']] ?? 0,
            'earned_xp'        => $r['earned_xp'],
            'coins_earned'     => $r['coins'],
            'coins_clamped'    => $r['clamped'],
            'total_coins'      => $u['coins'],
            'level'            => $u['level'],
            'new_achievements' => array_column($newAch, 'id'),
            'achievements'     => $newAch,
            'user'             => $u,
        ]);

    case 'claim_daily_reward':
        $r = claim_daily($u);
        if (isset($r['error'])) json_response(['success' => false, 'error' => $r['error']], 400);
        $newAch = evaluate_achievements($u, ['daily' => true]);
        user_commit($u);
        json_response([
            'success'      => true,
            'streak'       => $u['streak'],
            'coins_reward' => $r['bonus'],
            'total_coins'  => $u['coins'],
            'message'      => "Ricompensa del giorno {$u['streak']} riscossa: +{$r['bonus']} monete!",
            'achievements' => $newAch,
            'user'         => $u,
        ]);

    case 'buy_item':
        $itemId = (string)($in['item_id'] ?? '');
        $st = db()->prepare('SELECT * FROM shop_items WHERE id = ?');
        $st->execute([$itemId]);
        $item = $st->fetch();
        if (!$item) json_response(['success' => false, 'error' => 'Oggetto non trovato nel negozio'], 404);
        if (in_array($itemId, $u['unlocked_items'], true)) {
            json_response(['success' => false, 'error' => 'Oggetto già posseduto'], 400);
        }
        $price = (int)$item['price'];
        if ($u['coins'] < $price) {
            json_response(['success' => false, 'error' => 'Monete insufficienti! Ti servono ' . ($price - $u['coins']) . ' monete in più'], 400);
        }
        $u['coins'] -= $price;
        $u['unlocked_items'][] = $itemId;
        $u['stats']['items_purchased'] = ($u['stats']['items_purchased'] ?? 0) + 1;
        if ($item['type'] === 'ship_skin') $u['equipped']['ship_skin'] = $itemId;
        elseif ($item['type'] === 'trail') $u['equipped']['trail'] = $itemId;
        $newAch = evaluate_achievements($u);
        user_commit($u);
        json_response([
            'success'      => true,
            'message'      => "Hai sbloccato '{$item['name']}'!",
            'item'         => $item,
            'achievements' => $newAch,
            'user'         => $u,
        ]);

    case 'equip_item':
        $itemId = (string)($in['item_id'] ?? '');
        $slot = (string)($in['slot'] ?? 'ship_skin');
        if (!in_array($slot, ['ship_skin', 'trail', 'sound_pack'], true)) {
            json_response(['success' => false, 'error' => 'Slot non valido'], 400);
        }
        if ($slot === 'sound_pack') {
            if (!in_array($itemId, SOUND_PACKS, true)) json_response(['success' => false, 'error' => 'Pacchetto audio inesistente'], 400);
        } elseif ($itemId !== 'default') {
            if (!in_array($itemId, $u['unlocked_items'], true)) {
                json_response(['success' => false, 'error' => 'Non possiedi questo oggetto'], 403);
            }
            $st = db()->prepare('SELECT type FROM shop_items WHERE id = ?');
            $st->execute([$itemId]);
            if ($st->fetchColumn() !== $slot) {
                json_response(['success' => false, 'error' => "Questo oggetto non va nello slot '$slot'"], 400);
            }
        }
        $u['equipped'][$slot] = $itemId;
        user_commit($u);
        json_response(['success' => true, 'message' => 'Oggetto equipaggiato con successo', 'equipped' => $u['equipped']]);

    case 'save_cloud_checkpoint':
        $cp = is_array($in['checkpoint'] ?? null) ? $in['checkpoint'] : [];
        $gid = (string)($cp['game_id'] ?? 'space_defender');
        if (!in_array($gid, ARCADE_GAMES, true)) json_response(['success' => false, 'error' => 'Gioco non valido'], 400);
        $powerups = [];
        foreach (array_slice(is_array($cp['powerups'] ?? null) ? $cp['powerups'] : [], 0, 10) as $p) {
            if (is_string($p)) $powerups[] = mb_substr($p, 0, 40);
        }
        $u['cloud_checkpoint'] = [
            'timestamp' => time(),
            'game_id'   => $gid,
            'level'     => max(1, min(9999, (int)($cp['level'] ?? 1))),
            'score'     => max(0, min(5000000, (int)($cp['score'] ?? 0))),
            'lives'     => max(0, min(99, (int)($cp['lives'] ?? 3))),
            'powerups'  => $powerups,
        ];
        user_commit($u);
        json_response(['success' => true, 'message' => 'Checkpoint salvato nel cloud', 'checkpoint' => $u['cloud_checkpoint']]);

    case 'load_cloud_checkpoint':
        $cp = $u['cloud_checkpoint'] ?? null;
        user_commit($u);
        if (!$cp) json_response(['success' => false, 'error' => 'Nessun checkpoint cloud trovato'], 404);
        json_response(['success' => true, 'checkpoint' => $cp]);

    case 'unlock_achievement':
        $achId = (string)($in['achievement_id'] ?? '');
        if (in_array($achId, $u['achievements'], true)) {
            user_commit($u);
            json_response(['success' => true, 'already_unlocked' => true]);
        }
        // Gli altri obiettivi si sbloccano solo dal server, in base alle statistiche reali
        if (!in_array($achId, CLIENT_TRUSTED_ACHIEVEMENTS, true)) {
            json_response(['success' => false, 'error' => 'Questo obiettivo si sblocca automaticamente giocando'], 403);
        }
        $g = grant_achievement($u, $achId);
        if (!$g) json_response(['success' => false, 'error' => 'Obiettivo non valido'], 400);
        user_commit($u);
        json_response(['success' => true, 'achievement' => $g, 'user' => $u]);

    case 'spin_wheel':
        $r = spin_wheel_once($u);
        if (isset($r['error'])) json_response(['success' => false, 'error' => $r['error']], 400);
        user_commit($u);
        json_response(['success' => true, 'won' => $r['won'], 'user' => $u]);

    case 'clan_action':
        $tag = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', (string)($in['clan_tag'] ?? '')), 0, 6));
        if (strlen($tag) < 2) json_response(['success' => false, 'error' => 'Il tag clan deve contenere almeno 2 caratteri'], 400);
        $sub = (($in['clan_action'] ?? 'join') === 'create') ? 'Leader' : 'Membro';
        $u['clan'] = $tag;
        $u['clan_info'] = ['tag' => $tag, 'joined_at' => date('c'), 'role' => $sub];
        user_commit($u);
        json_response(['success' => true, 'clan' => $tag, 'user' => $u]);

    case 'toggle_favorite':
        $gid = (string)($in['game_id'] ?? '');
        if (!in_array($gid, ARCADE_GAMES, true)) json_response(['success' => false, 'error' => 'Gioco non valido'], 400);
        $idx = array_search($gid, $u['favorite_games'], true);
        if ($idx !== false) {
            array_splice($u['favorite_games'], $idx, 1);
            $isFav = false;
        } else {
            $u['favorite_games'][] = $gid;
            $isFav = true;
        }
        user_commit($u);
        json_response(['success' => true, 'game_id' => $gid, 'is_favorite' => $isFav, 'favorite_games' => $u['favorite_games'], 'user' => $u]);

    default:
        user_commit($u);
        json_response(['success' => true, 'user' => $u, 'leaderboards' => load_leaderboards()]);
}
