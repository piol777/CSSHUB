<?php
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/database.php';
guard_role('professor');

$currentPage = 'live';

$mySections = $pdo->prepare("
    SELECT cs.id, cs.subject_name, cs.year_level, cs.section_label, cs.course_id, c.code AS course_code
    FROM class_sections cs
    JOIN courses c ON c.id = cs.course_id
    WHERE cs.professor_id = ?
    ORDER BY cs.created_at DESC
");
$mySections->execute([$_SESSION['user_id']]);
$mySections = $mySections->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Studio - CDSGA HUB</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css?v=2">
    <link rel="stylesheet" href="../assets/css/studio.css">
</head>
<body class="dashboard-page">

    <?php include __DIR__ . '/../includes/professor_nav.php'; ?>

    <div class="studio-layout">
        <aside class="studio-chat-panel">
            <div class="studio-panel-title">Live chat</div>
            <div class="studio-chat-messages" id="studioChatMessages">
                <div class="studio-chat-empty">No messages yet.</div>
            </div>
            <div class="studio-chat-input-row">
                <input type="text" id="studioChatInput" placeholder="Send message..">
                <button type="button" id="studioChatSendBtn">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                </button>
            </div>
        </aside>

        <div class="studio-stage-wrap">
            <div class="studio-stage" id="studioStage">
                <canvas id="studioCanvas"></canvas>
                <div class="studio-stage-empty" id="studioStageEmpty">Add a source below to get started</div>
                <div class="studio-live-badge-label">Students see exactly this</div>
                <div class="studio-viewer-count">
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    <span id="studioViewerCount">0</span>
                </div>
            </div>

            <div class="studio-bottom-row">
                <div class="studio-watching-panel">
                    <div class="studio-panel-title">Watching List</div>
                    <div class="studio-watching-list" id="studioWatchingList">
                        <div class="studio-empty-small">No one watching yet.</div>
                    </div>
                </div>

                <div class="studio-sources-panel">
                    <div class="studio-panel-title">Sources</div>
                    <div class="studio-sources-list" id="studioSourcesList">
                        <div class="studio-empty-small">No sources added yet.</div>
                    </div>
                    <button type="button" class="studio-add-source-btn" id="studioAddSourceBtn">+</button>
                    <div class="studio-source-menu" id="studioSourceMenu">
                        <button type="button" data-source-type="camera">📷 Camera</button>
                        <button type="button" data-source-type="window">🖥️ Window Capture</button>
                        <button type="button" data-source-type="screen">🖵 Screen Capture</button>
                    </div>
                </div>
            </div>
        </div>

        <aside class="studio-controls-panel">
            <div class="studio-panel-title">Controls</div>

            <label class="studio-control-label">Section</label>
            <select id="studioSectionSelect" class="studio-control-select">
                <option value="">Select Section</option>
                <?php foreach ($mySections as $s): ?>
                    <option value="<?= $s['id'] ?>" data-course-id="<?= $s['course_id'] ?>" data-year-level="<?= $s['year_level'] ?>" data-section-label="<?= sanitize($s['section_label']) ?>">
                        <?= sanitize($s['course_code']) ?> <?= $s['year_level'] ?>-<?= sanitize($s['section_label']) ?> — <?= sanitize($s['subject_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label class="studio-control-label">Mode</label>
            <select id="studioModeSelect" class="studio-control-select">
                <option value="">Select Live Mode</option>
                <option value="gaming">Gaming</option>
                <option value="live_class">Live Class</option>
                <option value="other">Other</option>
            </select>
            <input type="text" id="studioModeOtherInput" class="studio-control-select" placeholder="Type your mode..." style="display:none;" maxlength="50">

            <select id="studioGameSelect" class="studio-control-select" style="display:none;">
                <option value="">Select Game</option>
                <optgroup label="🔥 MOBA">
                    <option>Mobile Legends: Bang Bang</option>
                    <option>League of Legends</option>
                    <option>Dota 2</option>
                    <option>Honor of Kings</option>
                    <option>Pokémon UNITE</option>
                    <option>Wild Rift</option>
                </optgroup>
                <optgroup label="🔫 FPS / SHOOTER">
                    <option>Counter-Strike 2</option>
                    <option>Valorant</option>
                    <option>Call of Duty</option>
                    <option>Call of Duty: Mobile</option>
                    <option>PUBG: BATTLEGROUNDS</option>
                    <option>PUBG Mobile</option>
                    <option>Garena Free Fire</option>
                    <option>Apex Legends</option>
                    <option>Overwatch 2</option>
                    <option>Rainbow Six Siege</option>
                    <option>Fortnite</option>
                    <option>Marvel Rivals</option>
                    <option>Delta Force</option>
                    <option>Battlefield</option>
                    <option>The Finals</option>
                    <option>Escape from Tarkov</option>
                </optgroup>
                <optgroup label="🎮 BATTLE ROYALE">
                    <option>Call of Duty: Warzone</option>
                    <option>Naraka: Bladepoint</option>
                    <option>Blood Strike</option>
                    <option>Farlight 84</option>
                </optgroup>
                <optgroup label="⚔️ RPG / OPEN WORLD">
                    <option>Genshin Impact</option>
                    <option>Honkai: Star Rail</option>
                    <option>Zenless Zone Zero</option>
                    <option>Wuthering Waves</option>
                    <option>Elden Ring</option>
                    <option>Black Myth: Wukong</option>
                    <option>Cyberpunk 2077</option>
                    <option>The Witcher 3</option>
                    <option>Baldur's Gate 3</option>
                    <option>Hogwarts Legacy</option>
                    <option>Monster Hunter Wilds</option>
                    <option>Final Fantasy VII Rebirth</option>
                    <option>Dragon's Dogma 2</option>
                    <option>Assassin's Creed Shadows</option>
                </optgroup>
                <optgroup label="🏀 SPORTS">
                    <option>NBA 2K</option>
                    <option>NBA 2K Mobile</option>
                    <option>EA SPORTS FC</option>
                    <option>EA SPORTS FC Mobile</option>
                    <option>eFootball</option>
                    <option>WWE 2K</option>
                    <option>Madden NFL</option>
                    <option>MLB The Show</option>
                    <option>Rocket League</option>
                </optgroup>
                <optgroup label="🚗 RACING">
                    <option>Forza Horizon 5</option>
                    <option>Forza Motorsport</option>
                    <option>Gran Turismo 7</option>
                    <option>Need for Speed</option>
                    <option>Assetto Corsa</option>
                    <option>Assetto Corsa Competizione</option>
                    <option>BeamNG.drive</option>
                    <option>The Crew Motorfest</option>
                    <option>CarX Street</option>
                    <option>Asphalt Legends Unite</option>
                    <option>Real Racing 3</option>
                </optgroup>
                <optgroup label="🧟 SURVIVAL / HORROR">
                    <option>Minecraft</option>
                    <option>Terraria</option>
                    <option>Rust</option>
                    <option>ARK: Survival Ascended</option>
                    <option>Palworld</option>
                    <option>Sons of the Forest</option>
                    <option>Dead by Daylight</option>
                    <option>Phasmophobia</option>
                    <option>Lethal Company</option>
                    <option>Resident Evil 4</option>
                    <option>Resident Evil Village</option>
                    <option>Resident Evil 7</option>
                    <option>Dying Light 2</option>
                </optgroup>
                <optgroup label="🌍 SANDBOX / SOCIAL">
                    <option>Roblox</option>
                    <option>Grand Theft Auto V</option>
                    <option>Grand Theft Auto Online</option>
                    <option>Garry's Mod</option>
                    <option>VRChat</option>
                    <option>The Sims 4</option>
                    <option>Rec Room</option>
                </optgroup>
                <optgroup label="🧠 STRATEGY">
                    <option>Clash of Clans</option>
                    <option>Clash Royale</option>
                    <option>Civilization VII</option>
                    <option>Age of Empires IV</option>
                    <option>Teamfight Tactics</option>
                    <option>Hearthstone</option>
                    <option>Marvel Snap</option>
                </optgroup>
                <optgroup label="👻 PARTY / CASUAL / MULTIPLAYER">
                    <option>Among Us</option>
                    <option>Fall Guys</option>
                    <option>Stumble Guys</option>
                    <option>Goose Goose Duck</option>
                    <option>Human: Fall Flat</option>
                    <option>Gang Beasts</option>
                    <option>Content Warning</option>
                    <option>It Takes Two</option>
                    <option>Overcooked! 2</option>
                </optgroup>
                <optgroup label="🗡️ SOULSLIKE / ACTION">
                    <option>Sekiro</option>
                    <option>Dark Souls III</option>
                    <option>Lies of P</option>
                    <option>Nioh 2</option>
                    <option>Wo Long: Fallen Dynasty</option>
                </optgroup>
                <optgroup label="🎴 GACHA / ANIME">
                    <option>Arknights</option>
                    <option>Blue Archive</option>
                    <option>Love and Deepspace</option>
                    <option>Pokémon GO</option>
                    <option>Pokémon Masters EX</option>
                </optgroup>
                <optgroup label="📱 SIKAT NA MOBILE GAMES">
                    <option>Brawl Stars</option>
                    <option>Subway Surfers</option>
                    <option>Candy Crush Saga</option>
                    <option>8 Ball Pool</option>
                    <option>eFootball Mobile</option>
                    <option>Delta Force Mobile</option>
                </optgroup>
                <optgroup label="🏆 MALALAKING PC/CONSOLE GAMES">
                    <option>Red Dead Redemption 2</option>
                    <option>God of War</option>
                    <option>God of War Ragnarök</option>
                    <option>Marvel's Spider-Man Remastered</option>
                    <option>Marvel's Spider-Man 2</option>
                    <option>Ghost of Tsushima</option>
                    <option>The Last of Us Part I</option>
                    <option>The Last of Us Part II</option>
                    <option>Helldivers 2</option>
                </optgroup>
            </select>

            <button type="button" class="studio-ctrl-btn studio-start-btn" id="studioStartBtn">Start</button>
            <button type="button" class="studio-ctrl-btn studio-stop-btn" id="studioStopBtn" disabled>Stop</button>
            <button type="button" class="studio-ctrl-btn studio-settings-btn" id="studioSettingsBtn">Settings</button>
        </aside>
    </div>

    <script>
        const LIVE_SERVER_URL = 'http://localhost:3001';
        const CURRENT_USER_NAME = <?= json_encode('Prof. ' . $_SESSION['first_name'] . ' ' . $_SESSION['last_name']) ?>;
    </script>
    <script src="http://localhost:3001/socket.io/socket.io.js"></script>
    <script src="../assets/js/studio.js"></script>
</body>
</html>