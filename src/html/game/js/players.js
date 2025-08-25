// Players module: player actions and rendering of other players
// Exposes: window.Players.createPlayer, window.Players.getPlayer, window.Players.getAllPlayers
(function () {
  if (!window.Players) window.Players = {};

  // Initialize legacy polling globals if missing (moved from game.js)
  if (typeof window.PLAYERS_POLL_MIN_MS === 'undefined') window.PLAYERS_POLL_MIN_MS = 800;
  if (typeof window._playersFetchInFlight === 'undefined') window._playersFetchInFlight = false;
  if (typeof window._playersFetchLastTs === 'undefined') window._playersFetchLastTs = 0;
  if (typeof window._playersFetchPending === 'undefined') window._playersFetchPending = 0;
  if (typeof window._playersFetchWantLocations === 'undefined') window._playersFetchWantLocations = false;
  if (typeof window.PLAYERS_IDLE_INTERVAL_MS === 'undefined') window.PLAYERS_IDLE_INTERVAL_MS = 3500;
  if (typeof window._playersIdleTimer === 'undefined') window._playersIdleTimer = 0;

  function createPlayer(name, roomId, portraitId) {
    return window.api.createPlayer(name, roomId, portraitId);
  }

  // Idle players refresh (poll when idle) - moved from game.js
  function startIdlePlayersRefresh() {
    if (window._playersIdleTimer || !window.gameStarted || document.hidden) return;
    window._playersIdleTimer = setInterval(function () {
      if (!document.hidden && window.gameStarted) {
        getAllPlayers(false);
      }
    }, window.PLAYERS_IDLE_INTERVAL_MS);
  }

  function stopIdlePlayersRefresh() {
    if (window._playersIdleTimer) {
      clearInterval(window._playersIdleTimer);
      window._playersIdleTimer = 0;
    }
  }

  function manageIdlePlayersRefresh(isMoving) {
    if (document.hidden || !window.gameStarted || isMoving) {
      stopIdlePlayersRefresh();
    } else {
      startIdlePlayersRefresh();
    }
  }

  function getPlayer(name, roomId) {
    return window.api.getPlayer(name, roomId);
  }

  // Mirrors legacy getAllPlayers(getLocations = false) behavior.
  function getAllPlayers(getLocations) {
    // Throttle and in-flight guard use legacy globals
    window._playersFetchWantLocations = (window._playersFetchWantLocations || !!getLocations);
    var now = Date.now();
    if (window._playersFetchInFlight) {
      return;
    }
    var elapsed = now - window._playersFetchLastTs;
    if (elapsed < window.PLAYERS_POLL_MIN_MS) {
      if (!window._playersFetchPending) {
        window._playersFetchPending = setTimeout(function () {
          window._playersFetchPending = 0;
          getAllPlayers(window._playersFetchWantLocations);
        }, window.PLAYERS_POLL_MIN_MS - elapsed);
      }
      return;
    }

    if (window._playersFetchPending) { clearTimeout(window._playersFetchPending); window._playersFetchPending = 0; }
    window._playersFetchInFlight = true;
    console.log("get all players..");

    var roomId = $("#room_id").text();
    window.api.getAllPlayers(roomId)
      .then(function (response) {
        window._playersFetchInFlight = false;
        window._playersFetchLastTs = Date.now();
        var wantLoc = window._playersFetchWantLocations; // snapshot then reset
        window._playersFetchWantLocations = false;
        var pid = parseInt($("#player_id").text());
        if (response && response.length > 0) {
          $.each(response, function (index, item) {
            var tid = parseInt(item.id);
            if (tid != pid) {
              // Preload other players' portraits
              loadImage(getPortrait(item.resource_id));
              if (!window.playersDict[tid]) {

                var baseX = (window.player && typeof window.player.x === 'number') ? window.player.x : (window.w / 2);
                var baseY = (window.player && typeof window.player.y === 'number') ? window.player.y : (window.h / 2);
                var temp_player = new component(
                  tid,
                  ss,
                  ss,
                  getPortrait(item.resource_id),
                  baseX + parseInt(item.x - player_x) * ss,
                  baseY + parseInt(item.y - player_y) * ss,
                  "image",
                  item.name,
                  "",
                  item.stats,
                  2
                );

                window.playersDict[tid] = {
                  id: tid,
                  name: item.name,
                  x: parseInt(item.x),
                  y: parseInt(item.y),
                  stats: item.stats,
                  resource_id: parseInt(item.resource_id),
                  comp: temp_player
                };

                window.players.push(temp_player);

              } else {

                window.playersDict[tid] = {
                  id: tid,
                  name: item.name,
                  x: parseInt(item.x),
                  y: parseInt(item.y),
                  stats: item.stats,
                  resource_id: parseInt(item.resource_id),
                  comp: window.playersDict[tid].comp
                };
                // keep component stats in sync so health bar updates
                if (window.playersDict[tid].comp) {
                  window.playersDict[tid].comp.stats = item.stats;
                  if (typeof window.playersDict[tid].comp._statsCache !== 'undefined') {
                    window.playersDict[tid].comp._statsCache = null;
                  }
                }
              }
            }
          });
          window.playersLoaded = true;
          if (wantLoc && window.Locations && typeof window.Locations.getAllLocations === 'function') {
            window.Locations.getAllLocations(null, null, null, null);
          }
        } else {
          window.playersLoaded = true;
          if (wantLoc && window.Locations && typeof window.Locations.getAllLocations === 'function') {
            window.Locations.getAllLocations(null, null, null, null);
          }
        }
      })
      .catch(function (err) {
        window._playersFetchInFlight = false;
        window._playersFetchLastTs = Date.now();
        console.error("error: " + err);
      });
  }

  window.Players.createPlayer = createPlayer;
  window.Players.getPlayer = getPlayer;
  window.Players.getAllPlayers = getAllPlayers;
  // Export idle refresh controls
  window.Players.startIdlePlayersRefresh = startIdlePlayersRefresh;
  window.Players.stopIdlePlayersRefresh = stopIdlePlayersRefresh;
  // Global alias used by Engine.updateGameArea
  if (typeof window.manageIdlePlayersRefresh !== 'function') window.manageIdlePlayersRefresh = manageIdlePlayersRefresh;
  
  // UI orchestration moved from game.js
  function _uiShow(sel) {
    try {
      if (window.UI && typeof window.UI.showEl === 'function') return window.UI.showEl(sel);
      if (typeof window.showEl === 'function') return window.showEl(sel);
    } catch(_){}
  }
  function _playClick() { try { if (typeof playSound==='function' && typeof getImageUrl==='function') playSound(getImageUrl('click.mp3')); } catch(_){} }
  function _setGameLinkSafe() {
    try {
      if (window.UI && typeof window.UI.setGameLink === 'function') window.UI.setGameLink();
      else if (typeof window.setGameLink === 'function') window.setGameLink();
    } catch(_){}
  }

  function createPlayerUIFlow() {
    _playClick();
    try { $("#create_player_box").hide(); } catch(_){}
    var name = $("#player_name").val();
    try { console.log('name is '); } catch(_){}
    $("#player").text(name);
    $("#b_player").text(String(name || '').slice(0, 8));
    try { console.log(name); } catch(_){}
    var portraitId = parseInt($("#player_portrait").val());
    var roomId = $("#room_id").text();
    try { console.log('creating player '+name+' with room id '+roomId+'..'); } catch(_){}
    return createPlayer(name, roomId, portraitId)
      .then(function (response) {
        if (response && response[0] === 'ok') {
          return getPlayerUIFlow(true);
        } else {
          try { console.log('error creating player'); } catch(_){}
          try { if (window.UI && window.UI.showCreatePlayerBox) window.UI.showCreatePlayerBox(); } catch(_){}
        }
      })
      .catch(function (err) {
        try { if (window.UI && window.UI.showCreatePlayerBox) window.UI.showCreatePlayerBox(); } catch(_){}
        try { console.error('error: ' + err); } catch(_){}
      });
  }

  function getPlayerUIFlow(initGame) {
    if (typeof initGame === 'undefined') initGame = true;
    try { console.log('getting player..'); } catch(_){}
    return getPlayer($("#player").text(), $("#room_id").text())
      .then(function (response) {
        if (!response || response.length == 0) {
          try { if (window.UI && window.UI.showCreatePlayerBox) window.UI.showCreatePlayerBox(); } catch(_){}
          return;
        }
        $("#player_id").text(response[0].id);
        window.player_x = parseInt(response[0].x);
        $("#player_x").text(window.player_x);
        window.player_y = parseInt(response[0].y);
        $("#player_y").text(window.player_y);
        $("#player_bx").text(window.bX(response[0].x));
        $("#player_by").text(window.bY(response[0].y));

        if (initGame) {
          window.cX = response[0].x;
          window.cY = response[0].y;
          _setGameLinkSafe();
        }

        window.player_portrait_id = response[0].resource_id;

        var stats = response[0].stats;
        var fields = String(stats || '').split(';');
        window.playerAtk = 0; window.playerDef = 0; window.playerSpd = 0; window.playerEvd = 0;
        var _hp = 0, _maxhp = 0, _mp = 0, _maxmp = 0;
        for (var i = 0; i < fields.length; i++) {
          var field = fields[i];
          if (field.indexOf('atk') === 0) window.playerAtk = parseInt(field.split('=')[1]);
          else if (field.indexOf('def') === 0) window.playerDef = parseInt(field.split('=')[1]);
          else if (field.indexOf('spd') === 0) window.playerSpd = parseInt(field.split('=')[1]);
          else if (field.indexOf('evd') === 0) window.playerEvd = parseInt(field.split('=')[1]);
          else if (field.indexOf('lvl') === 0) {
            $("#player_lvl").text(field.split('=')[1]);
            var lvlx = parseInt(field.split('=')[1]);
            var lvlup = parseInt(10 + 3 * lvlx + Math.pow(10, 0.01 * lvlx));
            $("#player_expup").text(lvlup);
            $("#player_lv_progress").attr('max', lvlup);
          }
          else if (field.indexOf('exp') === 0) {
            $("#player_exp").text(field.split('=')[1]);
            $("#player_lv_progress").attr('value', field.split('=')[1]);
          }
          else if (field.indexOf('hp') === 0) {
            _hp = parseInt(field.split('=')[1]);
            $("#player_hp").text(_hp);
            $("#player_hp_progress").attr('value', _hp);
          }
          else if (field.indexOf('maxhp') === 0) {
            _maxhp = parseInt(field.split('=')[1]);
            $("#player_maxhp").text(_maxhp);
            $("#player_hp_progress").attr('max', _maxhp);
          }
          else if (field.indexOf('mp') === 0) {
            _mp = parseInt(field.split('=')[1]);
            $("#player_mp").text(_mp);
            $("#player_mp_progress").attr('value', _mp);
          }
          else if (field.indexOf('maxmp') === 0) {
            _maxmp = parseInt(field.split('=')[1]);
            $("#player_maxmp").text(_maxmp);
            $("#player_mp_progress").attr('max', _maxmp);
          }
          else if (field.indexOf('gold') === 0) {
            $("#player_gold").text(window.bG(field.split('=')[1]));
          }
        }
        $("#player_stats").text(stats);
        if (typeof window.player !== 'undefined' && window.player) {
          window.player.stats = stats;
          if (typeof window.player._statsCache !== 'undefined') window.player._statsCache = null;
        }
        // Fallbacks if MP missing in stats
        if (!_mp && !_maxmp) {
          $("#player_mp").text('0');
          $("#player_maxmp").text('0');
          $("#player_mp_progress").attr('value', 0).attr('max', 0);
        }

        _uiShow('#player_box');
        _uiShow('#compass');

        try { if (window.Items && typeof window.Items.getItems === 'function') window.Items.getItems(); } catch(_){}

        if (initGame) {
          try { if (typeof window.startGame === 'function') window.startGame(); } catch(_){}
        }
      })
      .catch(function (err) {
        try { console.error('error: ' + err); } catch(_){}
      });
  }

  window.Players.getPlayerUIFlow = getPlayerUIFlow;
  window.Players.createPlayerUIFlow = createPlayerUIFlow;
})();
