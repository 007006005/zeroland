<?php
/**
 * api/registry.php - Catalogo giochi e skin (unica fonte di verità lato server).
 * Per aggiungere un gioco: inserisci una voce in arcade_games() e crea la pagina.
 * La pagina del gioco DEVE iniziare con require_login_page().
 */

function arcade_games(): array {
    return [
        'zero_agar' => [
            'title'   => 'ZeroAgar Arena',
            'icon'    => '🟢',
            'desc'    => 'Arena in tempo reale: mangia, cresci e domina la classifica contro altri giocatori.',
            'type'    => 'Multiplayer realtime',
            'url'     => 'zeroagar.php',
            'score'   => 'Record massa',
        ],
        'agar_io' => [
            'title'   => 'Agar.io Classic',
            'icon'    => '🟢',
            'desc'    => 'Nuova arena Agar.io: raccogli massa, cresci, elimina i rivali e scala la classifica.',
            'type'    => 'Arena HTML5',
            'url'     => 'agario.php',
            'score'   => 'Record massa',
        ],
        'tris' => [
            'title'   => 'Tris Online',
            'icon'    => '❌',
            'desc'    => 'Sfida un altro giocatore a Tris: crea una stanza o entra in una già aperta.',
            'type'    => 'Multiplayer 1 contro 1',
            'url'     => 'play.php?game=tris',
            'score'   => 'Vittorie',
        ],
        'forza4' => [
            'title'   => 'Forza 4',
            'icon'    => '🔴',
            'desc'    => 'Allinea quattro gettoni prima del tuo avversario. Partite a turni in tempo reale.',
            'type'    => 'Multiplayer 1 contro 1',
            'url'     => 'play.php?game=forza4',
            'score'   => 'Vittorie',
        ],

        'neon_snake' => ['title'=>'Neon Snake','icon'=>'🐍','desc'=>'Serpente neon con record personale.','type'=>'Arcade','url'=>'neon_snake.php','score'=>'Punteggio'],
        'zero_breakout' => ['title'=>'Zero Breakout','icon'=>'🧱','desc'=>'Distruggi i mattoni con la palla al plasma.','type'=>'Arcade','url'=>'zero_breakout.php','score'=>'Punteggio'],
        'pixel_invaders' => ['title'=>'Pixel Invaders','icon'=>'👾','desc'=>'Difendi il nucleo dagli invasori pixel.','type'=>'Arcade','url'=>'pixel_invaders.php','score'=>'Punteggio'],
        'cyber_pong' => ['title'=>'Cyber Pong','icon'=>'🏓','desc'=>'Pong cyber contro il bot.','type'=>'Sport','url'=>'cyber_pong.php','score'=>'Punteggio'],
        'blob_sumo' => ['title'=>'Blob Sumo','icon'=>'🤼','desc'=>'Spingi il rivale fuori dall’arena.','type'=>'Arena','url'=>'blob_sumo.php','score'=>'Punteggio'],
        'orbit_miner' => ['title'=>'Orbit Miner','icon'=>'⛏','desc'=>'Estrai cristalli e accumula punti.','type'=>'Strategy','url'=>'orbit_miner.php','score'=>'Punteggio'],
        'laser_maze' => ['title'=>'Laser Maze','icon'=>'🔦','desc'=>'Porta il laser al nucleo evitando le pareti.','type'=>'Puzzle','url'=>'laser_maze.php','score'=>'Punteggio'],
        'stack_tower' => ['title'=>'Stack Tower','icon'=>'🏗','desc'=>'Impila piattaforme in equilibrio.','type'=>'Arcade','url'=>'stack_tower.php','score'=>'Punteggio'],
        'zero_tetrix' => ['title'=>'Zero Tetrix','icon'=>'🟦','desc'=>'Tetrix neon a tempo.','type'=>'Puzzle','url'=>'zero_tetrix.php','score'=>'Punteggio'],
        'beat_orbit' => ['title'=>'Beat Orbit','icon'=>'🎵','desc'=>'Segui il ritmo e colpisci gli impulsi.','type'=>'Arcade','url'=>'beat_orbit.php','score'=>'Punteggio'],

    ];
}

/** Giochi a stanza (turni) gestiti da api/rooms.php. */
function room_games(): array {
    return ['tris', 'forza4'];
}

function arcade_skins(): array {
    return [
        'default' => ['name' => 'Classica', 'icon' => '⚪', 'price' => 0,    'currency' => 'coins', 'color' => '#00f0ff'],
        'neon'    => ['name' => 'Neon',     'icon' => '🔵', 'price' => 500,  'currency' => 'coins', 'color' => '#3b82f6'],
        'cyber'   => ['name' => 'Cyber',    'icon' => '🔴', 'price' => 1000, 'currency' => 'coins', 'color' => '#ef4444'],
        'gold'    => ['name' => 'Gold',     'icon' => '🟡', 'price' => 20,   'currency' => 'gems',  'color' => '#facc15'],
        'dark'    => ['name' => 'Dark',     'icon' => '🟣', 'price' => 35,   'currency' => 'gems',  'color' => '#a855f7'],
    ];
}
