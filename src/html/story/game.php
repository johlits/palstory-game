<!DOCTYPE html>
<html>

<head>
  <title>Play</title>
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
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

    /* Canvas should occupy the full viewport area visually */
    #gc {
      display: block;                 /* remove inline gap */
      width: 100vw;                   /* CSS pixels */
      height: 100dvh;                 /* avoid browser UI bars issues */
      touch-action: none;             /* disable default gestures */
      -ms-touch-action: none;
    }
  </style>

</head>

<body class="game-page" onload="init()">

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
      <button id="sfxOffBtn" type="button" class="nes-btn is-error" title="Disable SFX" onclick="AudioCtl.getSfx(0)">🔇</button>
      <button id="sfxOnBtn" type="button" class="nes-btn is-success hidden" title="Enable SFX" onclick="AudioCtl.getSfx(1)">🔊</button>
      <button id="t2sOffBtn" type="button" class="nes-btn is-error hidden" title="Disable Text-to-Speech" onclick="AudioCtl.getT2s(0)">🔕</button>
      <button id="t2sOnBtn" type="button" class="nes-btn is-success" title="Enable Text-to-Speech" onclick="AudioCtl.getT2s(1)">🗣️</button>
      <button id="bgmOffBtn" type="button" class="nes-btn is-error hidden" title="Disable BGM" onclick="AudioCtl.getMusic(0)">⏹️</button>
      <button id="bgmOnBtn" type="button" class="nes-btn is-success" title="Enable BGM" onclick="AudioCtl.getMusic(1)">🎵</button>
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
<button id="items_description_btn" type="button" class="nes-btn hidden" onclick="UI.toggleItemsDescription()">Close description</button>
<!-- <button type="button" class="nes-btn" onclick="toggleItemsStats()">Stats</button> -->
</div>

<div id="skills_box" class="hidden" style="margin-top:8px;">
  <span class="nes-text is-warning">Skills</span><br/>
  <button id="powerStrikeMiniBtn" type="button" class="nes-btn is-primary" onclick="UI.showSkillInfo('power_strike')" title="Power Strike (MP 5, CD 5s)">PS</button>
  <button id="fireballMiniBtn" type="button" class="nes-btn is-error" onclick="UI.showSkillInfo('fireball')" title="Fireball (MP 7, CD 6s)">FB</button>
  <div id="skill_info_box" class="hidden" style="margin-top:8px;">
    <div class="shadow">
      <span class="nes-text is-warning" id="skill_title">Power Strike</span>
      <div id="skill_desc" class="justify" style="margin-top:6px;">A heavy attack that deals 150% damage. Costs 5 MP. Cooldown 5s.</div>
      <div id="skill_meta" class="nes-text is-disabled" style="margin-top:6px;">Cost: 5 MP • Cooldown: 5s</div>
      <div style="margin-top:8px; display:flex; gap:8px;">
        <button id="skill_use_btn_power_strike" type="button" class="nes-btn is-success" onclick="Combat.useSkill('power_strike')" title="Use Power Strike">Use</button>
        <button id="skill_use_btn_fireball" type="button" class="nes-btn is-success hidden" onclick="Combat.useSkill('fireball')" title="Use Fireball">Use</button>
        <button type="button" class="nes-btn" onclick="UI.hideSkillInfo()">Close</button>
        <span id="skill_status_power_strike" class="nes-text is-disabled" style="align-self:center;"></span>
        <span id="skill_status_fireball" class="nes-text is-disabled hidden" style="align-self:center;"></span>
      </div>
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

<div class="box">
  <button id="locationInfoDisabledBtn" type="button" class="nes-btn is-disabled hidden">Info</button>
  <button id="locationInfoBtn" type="button" class="nes-btn" onclick="UI.toggleLocationInfo()">Info</button>
  <button id="locationStatsDisabledBtn" type="button" class="nes-btn is-disabled hidden">Stats</button>
  <button id="locationStatsPrimaryBtn" type="button" class="nes-btn is-primary" onclick="UI.toggleLocationStats()">Stats</button>
  <button id="moveDisabledBtn" type="button" class="nes-btn is-disabled">Move</button>
  <button id="moveSuccessBtn" type="button" class="nes-btn is-success" onclick="Movement.move(window.moveDirection)">Move</button>
  <button id="gatherBtn" type="button" class="nes-btn hidden" onclick="Locations.gather()">Gather</button>
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

<script src="js/vendor/jquery-2.2.4.min.js"></script>
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
<script src="js/movement.js"></script>
<script src="js/monsters.js"></script>
<script src="js/ui.js"></script>
<script src="js/fog.js"></script>
<script src="js/engine.js"></script>
<script src="js/app.js"></script>