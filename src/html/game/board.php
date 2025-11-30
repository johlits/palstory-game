<!DOCTYPE html>
<html>

<head>
  <title>Play</title>
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
  <link rel="manifest" href="assets/manifest.json" />
  <link rel="apple-touch-icon" sizes="512x512" href="assets/android/android-launchericon-512-512.png">
  <link rel="apple-touch-icon" sizes="192x192" href="assets/android/android-launchericon-192-192.png">
  <link rel="apple-touch-icon" sizes="180x180" href="assets/ios/180.png">
  <link rel="icon" type="image/png" sizes="32x32" href="assets/ios/32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="assets/ios/16.png">
  <link rel="shortcut icon" href="assets/favicon.ico">
  <!-- Fonts: Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/styles.css">
  <style>
    /* Ensure full-viewport layout on mobile and desktop */
    html, body {
      margin: 0;
      padding: 0;
      height: 100%;
      overflow: hidden; /* prevent bounce/scroll during gameplay */
      -webkit-tap-highlight-color: transparent;
      background-color: #000; /* fallback while image loads */
    }

    /* Background sizing rules without overriding image source */
    body.game-page {
      background-repeat: no-repeat;
      background-position: center center;
      background-size: cover;         /* scale to cover */
      min-height: 100dvh;             /* safe mobile viewport height */
    }

    /* Animated backdrop for menus (room/player creation) */
    #bg-anim {
      position: fixed;
      inset: 0;
      z-index: -1;              /* behind everything */
      pointer-events: none;      /* do not block clicks */
      background: linear-gradient(120deg, #0d0d0d, #1a237e, #004d40, #880e4f, #0d0d0d);
      background-size: 400% 400%;
      animation: bgGradient 22s ease infinite;
    }

    /* Subtle moving glow particles overlay */
    #bg-anim::before {
      content: "";
      position: absolute;
      inset: 0;
      background-image:
        radial-gradient(1200px 600px at 10% 20%, rgba(255,255,255,0.05), transparent 60%),
        radial-gradient(800px 500px at 80% 30%, rgba(255,255,255,0.04), transparent 60%),
        radial-gradient(900px 600px at 50% 80%, rgba(255,255,255,0.03), transparent 60%);
      filter: blur(0.5px);
      animation: bgParallax 40s linear infinite;
    }

    @keyframes bgGradient {
      0%   { background-position: 0% 50%; }
      50%  { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }

    @keyframes bgParallax {
      0%   { transform: translate3d(0,0,0); }
      50%  { transform: translate3d(-2%, -1%, 0); }
      100% { transform: translate3d(0,0,0); }
    }

    /* Stars canvas layered above gradient, below game canvas */
    #bg-stars {
      position: fixed;
      inset: 0;
      z-index: 0;
      pointer-events: none;
      opacity: 0;                 /* hidden during gameplay */
      transition: opacity 250ms ease;
    }

    /* Canvas should occupy the full viewport area visually */
    #gc {
      display: block;                 /* remove inline gap */
      width: 100vw;                   /* CSS pixels */
      height: 100dvh;                 /* avoid browser UI bars issues */
      touch-action: none;             /* disable default gestures */
      -ms-touch-action: none;
      transition: opacity 250ms ease;
    }

    /* Ensure UI is above canvas if needed */
    .box, dialog {
      position: relative;
      z-index: 2;
    }

    /* When menus are active, gracefully fade out the canvas to showcase bg */
    body.menu-active #gc { opacity: 0; }
    body.menu-active #bg-stars { opacity: 1; }
    
    /* Hide menu backgrounds when game is active */
    body:not(.menu-active) #bg-anim { display: none; }
    body:not(.menu-active) #bg-stars { display: none; }

    /* Orientation overlay to block gameplay in portrait */
    #rotate-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.9);
      color: #fff;
      display: none;
      align-items: center;
      justify-content: center;
      text-align: center;
      z-index: 2147483647; /* above all in-page z-index values */
      padding: 1rem;
    }
    #rotate-overlay-inner {
      max-width: 22rem;
      font-size: 0.95rem;
      line-height: 1.4;
    }
    #rotate-overlay h2 {
      margin-top: 0;
      margin-bottom: 0.5rem;
      font-size: 1.2rem;
    }

    /* When orientation is blocked, also visually suppress dialogs/UI layers */
    body.orientation-blocked dialog {
      visibility: hidden;
      pointer-events: none;
    }
    body.orientation-blocked .box {
      pointer-events: none;
    }
  </style>
  
  <?php require_once 'config.php'; ?>
  <script>
    // Set BASE_PATH for client-side URL generation
    var BASE_PATH = <?= json_encode(base_path('')) ?>;
  </script>

</head>

<body class="game-page" onload="init()">

  <div id="rotate-overlay">
    <div id="rotate-overlay-inner">
      <h2>Rotate your device</h2>
      <p>
        For the best PalStory experience, please rotate your device to
        <strong>landscape</strong> before continuing.
      </p>
    </div>
  </div>

  <div id="bg-anim" aria-hidden="true"></div>
  <canvas id="bg-stars" aria-hidden="true"></canvas>

  <canvas id="gc" width="200" height="100">
  </canvas>

  <section>
  <dialog class="nes-dialog is-dark is-rounded" id="win-dialog">
    <form method="dialog">
      <p class="title">Victory!</p>
      <div id="winBattleBox">Alert: this is a dialog.1</div>
      <menu class="dialog-menu">
        <button class="nes-btn is-primary" onclick="playSound(getImageUrl('click.mp3'));">Okay</button>
      </menu>
    </form>
  </dialog>
</section>

<section>
  <dialog class="nes-dialog is-dark is-rounded" id="gather-dialog">
    <form method="dialog">
      <p class="title">Gather</p>
      <div id="gatherBox">You search the area...</div>
      <menu class="dialog-menu">
        <button class="nes-btn is-primary" onclick="playSound(getImageUrl('click.mp3'));"><span>Okay</span></button>
      </menu>
    </form>
  </dialog>
</section>

<section>
  <dialog class="nes-dialog is-dark is-rounded" id="shop-dialog" style="max-width: 600px; width: 90%;">
    <form method="dialog">
      <p class="title">Shop</p>
      <div class="nes-container with-title" style="margin-bottom: 16px;">
        <p class="title">Tabs</p>
        <button type="button" id="shop-tab-buy" class="nes-btn is-primary" onclick="Shop.switchTab('buy')">Buy</button>
        <button type="button" id="shop-tab-sell" class="nes-btn" onclick="Shop.switchTab('sell')">Sell</button>
      </div>
      
      <div id="shop-panel-buy" style="max-height: 400px; overflow-y: auto;">
        <div id="shop-buy-items"></div>
      </div>
      
      <div id="shop-panel-sell" style="max-height: 400px; overflow-y: auto; display: none;">
        <div id="shop-sell-items"></div>
      </div>
      
      <menu class="dialog-menu">
        <button class="nes-btn" onclick="playSound(getImageUrl('click.mp3')); Shop.close();"><span>Close</span></button>
      </menu>
    </form>
  </dialog>
</section>

<section>
  <dialog class="nes-dialog is-dark is-rounded" id="storage-dialog" style="max-width: 600px; width: 90%;">
    <form method="dialog">
      <p class="title">Storage</p>
      <div class="nes-container with-title" style="margin-bottom: 16px;">
        <p class="title">Tabs</p>
        <button type="button" id="storage-tab-stored" class="nes-btn is-primary" onclick="Storage.switchTab('stored')">Stored Items</button>
        <button type="button" id="storage-tab-deposit" class="nes-btn" onclick="Storage.switchTab('deposit')">Deposit</button>
        <span id="storage-slots-info" class="nes-text is-disabled" style="margin-left: 16px;">Storage: 0/20 slots</span>
      </div>
      
      <div id="storage-panel-stored" style="max-height: 400px; overflow-y: auto;">
        <div id="storage-stored-items"></div>
      </div>
      
      <div id="storage-panel-deposit" style="max-height: 400px; overflow-y: auto; display: none;">
        <div id="storage-deposit-items"></div>
      </div>
      
      <menu class="dialog-menu">
        <button class="nes-btn" onclick="playSound(getImageUrl('click.mp3')); Storage.close();"><span>Close</span></button>
      </menu>
    </form>
  </dialog>
</section>

<section>
  <dialog class="nes-dialog is-dark is-rounded" id="help-dialog">
    <form method="dialog">
      <p class="title">Help</p>
      <div>
        <ul class="help-list">
          <li>WASD or arrow keys - Movement</li>
          <li>I - Toggle items</li>
          <li>Z - Toggle location info</li>
          <li>X - Toggle location stats</li>
          <li>C - Toggle character info</li>
          <li>V - Toggle monster info</li>
          <li>B - Toggle monster stats</li>
          <li>N - Toggle monster battle log</li>
          <li>M - Attack</li>
          <li>O - Options</li>
          <li>H - Toggle help</li>
        </ul>
      </div>
      <menu class="dialog-menu">
        <button id="close_help_btn" class="nes-btn is-primary" onclick="playSound(getImageUrl('click.mp3'));">Okay</button>
      </menu>
    </form>
  </dialog>
</section>

<section>
  <dialog class="nes-dialog is-dark is-rounded" id="lose-dialog">
    <form method="dialog">
      <p class="title">Defeat...</p>
      <div>You died. Better luck next time.</div>
      <menu class="dialog-menu">
        <button class="nes-btn is-primary" onclick="UI.gameOver();">Okay</button>
      </menu>
    </form>
  </dialog>
</section>

  <div id="player_box" class="box hidden">
    <div class="shadow">
    <!-- <a href="#" class="nes-badge is-splited" style="width: 300px;">
      <span id="room" class="is-dark"><? echo $_GET["room"]; ?></span>
      <span class="is-dark room_expire"></span>
    </a><br/> -->
    <!-- Room: &nbsp;(<span id="player_bx"></span>, <span id="player_by"></span>)<br /> -->
    <a href="#" class="nes-badge is-splited" onclick="UI.toggleStats()">
      <span class="is-dark" id="b_player"><? echo substr($_GET["player"], 0, 8); ?></span>
      <span class="is-dark"><span id="player_bx"></span> <span id="player_by"></span></span>
    </a>
    <span id="audioBtns" style="margin-left:8px; display:inline-flex; gap:6px; vertical-align:middle;">
      <button id="optionsBtn" type="button" class="nes-btn" title="Options (O)" onclick="if(window.UI&&typeof UI.openOptions==='function'){UI.openOptions();}">Options</button>
    </span>
    <div class="statusbar_outer"><div class="statusbar_text">HP: <span id="player_hp"></span>/<span id="player_maxhp"></span></div><progress id="player_hp_progress" class="nes-progress is-success statusbar" value="100" max="100"></progress></div>
    <div class="statusbar_outer"><div class="statusbar_text">MP: <span id="player_mp"></span>/<span id="player_maxmp"></span></div><progress id="player_mp_progress" class="nes-progress is-primary statusbar" value="0" max="0"></progress></div>
    <!-- <div class="statusbar_outer"><div class="statusbar_text">SP: <span id="player_sp"></span>/<span id="player_maxsp"></span></div><progress id="player_sp_progress" class="nes-progress is-primary statusbar" value="100" max="100"></progress></div> -->
    <div class="statusbar_outer"><div class="statusbar_text">LV: <span id="player_lvl"></span> (<span id="player_exp"></span>/<span id="player_expup"></span>)</div><progress id="player_lv_progress" class="nes-progress is-primary statusbar" value="100" max="100"></progress></div>
</div>


    <div id="player_bstats" class="shadow hidden">
    <span class="nes-text is-warning">Player Stats</span><br/>
    <div class="player_bstat">ATK: <span id="player_atk"></span></div>
    <div class="player_bstat">DEF: <span id="player_def"></span></div>
    <div class="player_bstat">SPD: <span id="player_spd"></span></div>
    <div class="player_bstat">EVD: <span id="player_evd"></span></div>
    <span>Gold: <span id="player_gold"></span></span>
    <br/>
    </div>

    <!-- <button id="showStatsBtn" type="button" class="nes-btn" onclick="toggleStats()">Stats</button> -->
    <!-- <button id="bgmOffBtn" type="button" class="nes-btn is-error" style="display: none;" onclick="getMusic(0)">BGM</button>
    <button id="bgmOnBtn" type="button" class="nes-btn is-success" onclick="getMusic(1)">BGM</button>
    <button id="sfxOffBtn" type="button" class="nes-btn is-error" onclick="getSfx(0)">SFX</button>
    <button id="sfxOnBtn" type="button" class="nes-btn is-success" style="display: none;" onclick="getSfx(1)">SFX</button> -->
    
    <div id="debug" class="shadow hidden">
    <br/>
    <b class="nes-text is-error">DEBUG</b><br/>
    Player id: <span id="player_id"></span><br/>
    Player name: <span id="player"><? echo $_GET["player"]; ?></span><br/>
    Room id: <span id="room_id"></span><br/>
    Room expires: <span id="room_expire"></span><br/>
    Room regen: <span id="room_regen"></span><br/>
    Position: <span id="player_x"></span>, <span id="player_y"></span><br/>
    Stats: <span id="player_stats"></span>
    </div>

    <!-- <button type="button" class="nes-btn is-error" onclick="toggleDebug()">Debug</button> -->

    
  </div>

  <div id="compass" class="box shadow hidden">
  <span>
      <a id="game_link" href="#"><span id="room"><? echo $_GET["room"]; ?></span></a>
      <span><span id="mouse_x">W0</span> <span id="mouse_y">N0</span></span>
</span>
</div>

<div id="items_box" class="hidden">

<div class="box">
<div id="items_table" class="hidden">
<div class="nes-table-responsive">
  <table class="nes-table is-bordered is-dark" id="items_table_body">
  <thead>
      <tr>
        <th>Image</th>
        <th>Name</th>
        <th>ATK</th>
        <th>DEF</th>
        <th>SPD</th>
        <th>EVD</th>
        <th>Type</th>
        <th>Equip</th>
        <th>Drop</th>
      </tr>
    </thead>
    <tbody>
    </tbody>
  </table>
</div>
</div>

<div id="item_info_box" class="hidden">
<img id="item_image" src="">
<span class="item_name nes-text is-warning justify"></span><br/>
<span id="item_description" class="justify"></span>
</div>

<div>
<button type="button" class="nes-btn" onclick="UI.toggleItemsTable()">Items</button>
<button type="button" class="nes-btn" onclick="UI.toggleSkills()">Skills</button>
<button type="button" class="nes-btn" onclick="UI.toggleSkillTree()">Skill Tree</button>
<button type="button" class="nes-btn" onclick="UI.toggleJobSelection()">Job</button>
<button id="items_description_btn" type="button" class="nes-btn hidden" onclick="UI.toggleItemsDescription()">Close description</button>
<!-- <button type="button" class="nes-btn" onclick="toggleItemsStats()">Stats</button> -->
</div>

<div id="skills_box" class="hidden" style="margin-top:8px;">
  <span class="nes-text is-warning">Usable Skills</span>
  <div id="skills_buttons_container" style="margin-top:6px;">
    <!-- Skill buttons will be populated dynamically -->
    <span class="nes-text is-disabled">No skills unlocked yet.</span>
  </div>
  <div id="skill_info_box" class="hidden" style="margin-top:8px;">
    <div class="shadow">
      <span class="nes-text is-warning" id="skill_title"></span>
      <div id="skill_desc" class="justify" style="margin-top:6px;"></div>
      <div id="skill_meta" class="nes-text is-disabled" style="margin-top:6px;"></div>
      <div style="margin-top:8px; display:flex; gap:8px; flex-wrap:wrap;">
        <button id="skill_use_btn" type="button" class="nes-btn is-success" onclick="Combat.useSkillFromPanel()" title="Use Skill">Use</button>
        <button type="button" class="nes-btn" onclick="UI.hideSkillInfo()">Close</button>
        <span id="skill_status" class="nes-text is-disabled" style="align-self:center;"></span>
      </div>
    </div>
  </div>
</div>

<div id="skill_tree_box" class="hidden" style="margin-top:8px;">
  <div class="shadow">
    <span class="nes-text is-warning">Skill Tree</span>
    <div style="margin-top:6px;">
      <span class="nes-text is-disabled">Skill Points: <span id="skill_points_display">0</span></span>
    </div>
    <div id="skill_tree_content" style="margin-top:8px; max-height:60vh; overflow-y:auto; overflow-x:hidden;">
      <!-- Skill tree will be populated by JavaScript -->
    </div>
    <div style="margin-top:8px;">
      <button type="button" class="nes-btn" onclick="UI.toggleSkillTree()">Close</button>
    </div>
  </div>
</div>

<div id="job_selection_box" class="hidden" style="margin-top:8px;">
  <div class="shadow">
    <span class="nes-text is-warning">Job Selection</span>
    <div id="current_job_display" style="margin-top:6px;">
      <span class="nes-text is-disabled">Current Job: <span id="player_job_name">None</span></span>
    </div>
    <div id="job_selection_content" style="margin-top:8px; max-height:60vh; overflow-y:auto; overflow-x:hidden;">
      <!-- Job selection will be populated by JavaScript -->
    </div>
    <div style="margin-top:8px;">
      <button type="button" class="nes-btn" onclick="UI.toggleJobSelection()">Close</button>
    </div>
  </div>
</div>
</div>
</div>

<div id="location_box" class="hidden">
<div id="location_name_box" class="shadow">
<span class="location_name nes-text is-warning"></span>
</div>
  <div id="location_data_box" class="box shadow hidden">

  <div id="location_info_box">
  <img id="location_image" src="">
  <div class="info_text">
    <span class="location_name nes-text is-warning"></span>
    <span id="location_description" class="justify"></span>
  </div>
  </div>

<div id="location_stats_box">
  <span class="nes-text is-warning">Location Stats</span><br/>
  <span class="location_bstat">Name: <span class="location_name"></span></span><br/>
    <span class="location_bstat  nes-text is-disabled">Spawns: <span id="location_spawns"></span></span>
    </div>
</div>

<div class="box location-actions-bar">
  <button id="locationInfoDisabledBtn" type="button" class="nes-btn is-disabled hidden">Info</button>
  <button id="locationInfoBtn" type="button" class="nes-btn" onclick="UI.toggleLocationInfo()">Info</button>
  <button id="locationStatsDisabledBtn" type="button" class="nes-btn is-disabled hidden">Stats</button>
  <button id="locationStatsPrimaryBtn" type="button" class="nes-btn is-primary" onclick="UI.toggleLocationStats()">Stats</button>
  <button id="moveDisabledBtn" type="button" class="nes-btn is-disabled">Move</button>
  <button id="moveSuccessBtn" type="button" class="nes-btn is-success" onclick="Movement.move(window.moveDirection)">Move</button>
  <div id="locationActionsMenu" class="dropup-container">
    <button id="locationActionsBtn" type="button" class="nes-btn hidden" onclick="UI.toggleLocationActions()">Actions</button>
    <div id="locationActionsDropup" class="dropup-menu hidden">
      <button id="gatherBtn" type="button" class="nes-btn hidden" onclick="Locations.gather(); UI.closeLocationActions();">Gather</button>
      <button id="restBtn" type="button" class="nes-btn is-warning hidden" onclick="Locations.rest(); UI.closeLocationActions();">Rest</button>
      <button id="respawnBtn" type="button" class="nes-btn is-primary hidden" onclick="Locations.setRespawn(); UI.closeLocationActions();">Set Respawn</button>
      <button id="shopBtn" type="button" class="nes-btn is-success hidden" onclick="Shop.open(); UI.closeLocationActions();">Shop</button>
      <button id="storageBtn" type="button" class="nes-btn is-warning hidden" onclick="Storage.open(); UI.closeLocationActions();">Storage</button>
    </div>
  </div>
</div>
  </div>

  <div id="monster_box" class="hidden">

<div id="monster_name_box" class="shadow">
<span class="monster_name nes-text is-warning" class="monster_name"></span>
</div>

<div id="monster_data_box" class="box shadow hidden">

<div id="monster_info_box">
<img id="monster_image" src="">
<div class="info_text">
  <span class="monster_name nes-text is-warning"></span>
  <span id="monster_description" class="justify"></span>
  </div>
</div>

<div id="monster_battle_box" class="hidden">
<span class="nes-text is-warning">Battle Log</span><br/>
<span class="monster_bstat">Name: <span class="monster_name"></span></span><br/>
<div class="statusbar_outer"><div class="statusbar_text">HP: <span class="monster_hp"></span>/<span class="monster_maxhp"></span></div><progress class="monster_hp_progress nes-progress is-success statusbar" value="100" max="100"></progress></div>
<div id="battle_log"></div>
</div>

<div id="monster_stats_box">
<span class="nes-text is-warning">Monster Stats</span><br/>
<span class="monster_bstat">Name: <span class="monster_name"></span></span><br/>
<div class="statusbar_outer"><div class="statusbar_text">HP: <span class="monster_hp"></span>/<span class="monster_maxhp"></span></div><progress class="monster_hp_progress nes-progress is-success statusbar" value="100" max="100"></progress></div>

<div id="monster_bstats">
    <div class="player_bstat">ATK: <span id="monster_atk"></span></div>
    <div class="player_bstat">DEF: <span id="monster_def"></span></div>
    <div class="player_bstat">SPD: <span id="monster_spd"></span></div>
    <div class="player_bstat">EVD: <span id="monster_evd"></span></div>
    </div><br/><br/>

    <span class="monster_bstat nes-text is-disabled">Drops: <span id="monster_drops"></span></span><br/>
    <span class="monster_bstat nes-text is-disabled">Gold: <span id="monster_gold"></span></span><br/>
    <span class="monster_bstat nes-text is-disabled">Exp: <span id="monster_exp"></span></span>
  </div>
</div>


<div class="box text-right">
<button type="button" class="nes-btn" onclick="UI.toggleMonsterInfo()">Info</button>
<button type="button" class="nes-btn is-primary" onclick="UI.toggleMonsterStats()">Stats</button>
<button type="button" class="nes-btn is-warning" onclick="UI.toggleBattleLog()">Battle Log</button>
<button type="button" class="nes-btn is-error" onclick="Combat.attack()">Attack</button>
</div>
</div>

  <div id="create_game_box" class="box hidden">
    <div class="stack">
      <span class="nes-text is-primary">Create game</span>
      <div class="stack">
        <label for="create_game_room_name">Room name:</label>
        <input type="text" id="create_game_room_name" class="nes-input" <? if (isset($_GET["room"])) { echo 'disabled value="' . $_GET["room"] . '"'; } ?>>
      </div>
      <div class="stack">
        <label for="create_game_expiration">Expiration:</label>
        <input type="date" id="create_game_expiration" name="create_game_expiration" value="<? echo date('Y-m-d', strtotime('+7 days')); ?>">
      </div>
      <!--
      <div class="stack">
        <label for="create_game_regen">Stamina regen (per hour):</label>
        <input type="number" id="create_game_regen" name="create_game_regen" min="1" max="100" value="10">
      </div>
      -->
      <button id="create_game_btn" onclick="Rooms.createGameUIFlow()" type="button" class="nes-btn is-success mt-8">Start game!</button>
    </div>
  </div>

  <div id="create_player_box" class="box hidden">
    <div class="grid grid-2">
      <div class="stack">
        <span class="nes-text is-primary">Create player</span>
        <div class="stack">
          <label for="player_name">Player name:</label>
          <input type="text" id="player_name" class="nes-input" <? if (isset($_GET["player"])) { echo 'disabled value="' . $_GET["player"] . '"'; } ?>>
        </div>
        <div class="stack">
          <label for="player_portrait">Player portrait:</label>
          <input type="number" id="player_portrait" name="player_portrait" onchange="previewPortrait()" min="1" max="8" value="1">
        </div>
        <button id="create_player_btn" onclick="Players.createPlayerUIFlow()" type="button" class="nes-btn is-success mt-8">Create player!</button>
      </div>
      <div class="stack">
        <img id="player_portrait_preview" src="">
      </div>
    </div>
  </div>
</body>
</html>

<!-- jQuery 3.7.1 (upgraded for security) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script>window.jQuery || document.write('<script src="js/vendor/jquery-3.7.1.min.js"><\\/script>')</script>
<script src="config.js"></script>
<!-- Modularized scripts (loaded before game.js) -->
<script src="js/utils.js"></script>
<script src="js/assets.js"></script>
<script src="js/audio.js"></script>
<script src="js/fct.js"></script>
<script src="js/combat.js"></script>
<script src="js/api.js"></script>
<script src="js/items.js"></script>
<script src="js/players.js"></script>
<script src="js/rooms.js"></script>
<script src="js/locations.js"></script>
<script src="js/shop.js"></script>
<script src="js/storage.js"></script>
<script src="js/movement.js"></script>
<script src="js/monsters.js"></script>
<script src="js/ui.js"></script>
<script src="js/skilltree.js"></script>
<script src="js/jobselection.js"></script>
<script src="js/usableskills.js"></script>
<script src="js/fog.js"></script>
<script src="js/engine.js"></script>
<script src="js/app.js"></script>

<script>
  // Orientation overlay: block gameplay in portrait
  (function () {
    function isLandscape() {
      return window.innerWidth > window.innerHeight;
    }

    function updateRotateOverlay() {
      var overlay = document.getElementById('rotate-overlay');
      if (!overlay) return;
      var landscape = isLandscape();
      overlay.style.display = landscape ? 'none' : 'flex';
      document.body.classList.toggle('orientation-blocked', !landscape);
    }

    window.addEventListener('resize', updateRotateOverlay);
    window.addEventListener('orientationchange', updateRotateOverlay);
    window.addEventListener('load', updateRotateOverlay);
  })();

  // Toggle animated background and canvas fade during menu (create room/player) views
  (function () {
    var body = document.body;
    var boxes = [
      document.getElementById('create_game_box'),
      document.getElementById('create_player_box')
    ];

    function updateMenuState() {
      var menuVisible = boxes.some(function (el) {
        if (!el) return false;
        var isVisible = !el.classList.contains('hidden');
        console.log('Box', el.id, 'visible:', isVisible);
        return isVisible;
      });
      console.log('Menu visible:', menuVisible, '- Setting menu-active to:', menuVisible);
      body.classList.toggle('menu-active', menuVisible);
      console.log('Body classes:', body.className);
    }

    // Observe class changes on the boxes to auto-toggle
    var mo = new MutationObserver(updateMenuState);
    boxes.forEach(function (el) {
      if (!el) return;
      mo.observe(el, { attributes: true, attributeFilter: ['class'] });
    });

    // Run once on load in case a box is already visible
    window.addEventListener('load', updateMenuState);
  })();

  // Falling glittering stars animation (runs only when menu is visible)
  (function () {
    var canvas = document.getElementById('bg-stars');
    if (!canvas) return;
    var ctx = canvas.getContext('2d');
    var dpr = Math.max(1, window.devicePixelRatio || 1);
    var stars = [];
    var running = false; // driven by body.menu-active
    var width = 0, height = 0;

    function resize() {
      width = Math.floor(window.innerWidth);
      height = Math.floor(window.innerHeight);
      canvas.width = Math.floor(width * dpr);
      canvas.height = Math.floor(height * dpr);
      canvas.style.width = width + 'px';
      canvas.style.height = height + 'px';
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      seed();
    }

    function seed() {
      var target = Math.min(220, Math.floor((width * height) / 18000));
      stars = new Array(target).fill(0).map(function () {
        return makeStar();
      });
    }

    function makeStar() {
      var size = Math.random() * 1.8 + 0.6; // px
      var speed = Math.random() * 0.6 + 0.25; // px/frame
      var twinkleSpeed = Math.random() * 0.04 + 0.01;
      return {
        x: Math.random() * width,
        y: Math.random() * height,
        r: size,
        vx: (Math.random() - 0.5) * 0.15,
        vy: speed,
        a: Math.random() * 0.8 + 0.2, // alpha
        ta: Math.random() * Math.PI * 2, // twinkle phase
        ts: twinkleSpeed,
        hue: 200 + Math.random() * 60 // bluish to teal
      };
    }

    function step() {
      if (running) {
        ctx.clearRect(0, 0, width, height);
        for (var i = 0; i < stars.length; i++) {
          var s = stars[i];
          s.x += s.vx;
          s.y += s.vy;
          s.ta += s.ts;
          // wrap
          if (s.y - s.r > height) { s.y = -s.r; s.x = Math.random() * width; }
          if (s.x < -5) s.x = width + 5; else if (s.x > width + 5) s.x = -5;

          // twinkle alpha
          var tw = (Math.sin(s.ta) + 1) * 0.5; // 0..1
          var alpha = Math.max(0, Math.min(1, s.a * (0.6 + 0.7 * tw)));

          // glow gradient
          var g = ctx.createRadialGradient(s.x, s.y, 0, s.x, s.y, s.r * 3);
          g.addColorStop(0, 'hsla(' + s.hue + ', 80%, 90%, ' + (alpha) + ')');
          g.addColorStop(0.4, 'hsla(' + s.hue + ', 80%, 70%, ' + (alpha * 0.6) + ')');
          g.addColorStop(1, 'hsla(' + s.hue + ', 80%, 50%, 0)');
          ctx.fillStyle = g;
          ctx.beginPath();
          ctx.arc(s.x, s.y, s.r * 3, 0, Math.PI * 2);
          ctx.fill();

          // bright core sparkle
          ctx.fillStyle = 'hsla(' + s.hue + ', 100%, 98%, ' + Math.min(1, alpha * 1.2) + ')';
          ctx.beginPath();
          ctx.arc(s.x, s.y, Math.max(0.5, s.r * 0.6), 0, Math.PI * 2);
          ctx.fill();
        }
      }
      requestAnimationFrame(step);
    }

    // Hook into the existing menu visibility toggling
    function updateRunning() {
      running = document.body.classList.contains('menu-active');
    }

    window.addEventListener('resize', resize);
    window.addEventListener('orientationchange', resize);
    window.addEventListener('load', function () { resize(); updateRunning(); });
    // Observe body class changes to start/stop animation work
    new MutationObserver(updateRunning).observe(document.body, { attributes: true, attributeFilter: ['class'] });

    requestAnimationFrame(step);
  })();
</script>