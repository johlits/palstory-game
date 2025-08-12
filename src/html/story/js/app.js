// App module: high-level orchestration (init, resize, startup)
(function(){
  'use strict';
  if (!window.App) window.App = {};

  // Initialize canvas, attach handlers, init audio prefs, then start game init flow
  window.App.init = function () {
    try { window.gc = document.getElementById('gc'); } catch(_){ window.gc = document.getElementById('gc'); }
    // CSS pixel size
    window.w = window.innerWidth;
    window.h = window.innerHeight;
    // HiDPI scaling
    var dpr = Math.max(1, Math.floor(window.devicePixelRatio || 1));
    if (window.gc) {
      window.gc.width = Math.floor(window.w * dpr);
      window.gc.height = Math.floor(window.h * dpr);
      try { window.gc.focus(); } catch(_){}
    }
    window.ss = Math.min(window.w, window.h) / 10;

    // Canvas interaction handlers
    if (window.gc) {
      window.gc.addEventListener('mousemove', function (evt) {
        if (window.UI && typeof window.UI.onCanvasMouseMove === 'function') {
          return window.UI.onCanvasMouseMove(window.gc, evt);
        }
      }, false);

      window.gc.addEventListener('click', function (evt) {
        if (window.UI && typeof window.UI.onCanvasClick === 'function') {
          return window.UI.onCanvasClick(evt);
        }
      }, false);
    }

    // Keep canvas full-screen and scale entities on viewport/orientation changes
    window.addEventListener('resize', window.App.handleResize, { passive: true });

    // Pause/resume idle players polling on tab visibility changes
    document.addEventListener('visibilitychange', function () {
      var isMoving = (typeof window.player !== 'undefined' && window.player && window.player.moving) ||
                     (typeof window.anythingMoving === 'function' ? window.anythingMoving() : false);
      if (typeof window.manageIdlePlayersRefresh === 'function') window.manageIdlePlayersRefresh(isMoving);
    }, { passive: true });

    // Load audio preferences and set up BGM unlock
    if (window.AudioCtl && typeof window.AudioCtl.initPreferences === 'function') {
      window.AudioCtl.initPreferences();
    }

    // Begin game init flow
    if (typeof window.App.initGame === 'function') window.App.initGame();
  };

  // Handle responsive resizing for mobile/desktop
  window.App.handleResize = function () {
    if (!window.gc) return;

    var oldW = window.w;
    var oldH = window.h;
    var oldSS = window.ss;

    // New CSS pixel size
    window.w = window.innerWidth;
    window.h = window.innerHeight;
    // Resize canvas drawing buffer to match viewport (avoids blur) with HiDPI
    var dpr = Math.max(1, Math.floor(window.devicePixelRatio || 1));
    window.gc.width = Math.floor(window.w * dpr);
    window.gc.height = Math.floor(window.h * dpr);

    // Re-apply DPR scaling to 2D context if available
    if (window.myGameArea && window.myGameArea.context) {
      window.myGameArea.context.setTransform(dpr, 0, 0, dpr, 0, 0);
    }

    window.ss = Math.min(window.w, window.h) / 10;
    var scale = oldSS ? (window.ss / oldSS) : 1;
    var dx = (window.w - oldW) / 2;
    var dy = (window.h - oldH) / 2;

    // Helper to scale and recenter a component
    var scaleComp = function (c) {
      if (!c) return;
      c.x = c.x * scale + dx;
      c.y = c.y * scale + dy;
      if (typeof c.nX !== 'undefined') c.nX = c.nX * scale + dx;
      if (typeof c.nY !== 'undefined') c.nY = c.nY * scale + dy;
      c.width = window.ss;
      c.height = window.ss;
    };

    // Scale all on-screen entities
    try {
      for (var i = 0; i < window.locations.length; i++) scaleComp(window.locations[i]);
      for (var j = 0; j < window.players.length; j++) scaleComp(window.players[j]);
      scaleComp(window.player);
    } catch(_){ }

    // Recenter map coordinate helpers around new viewport
    // Maintain the same tile offsets relative to the player
    try {
      var offsetx = window.player_x * window.ss;
      var offsety = window.player_y * window.ss;
      window.mapCoordFromX = window.w / 2 + offsetx;
      window.mapCoordFromY = window.h / 2 + offsety;
    } catch(_){ }
  };

  // Maintenance: purge stale rooms then load current room
  window.App.initGame = function () {
    try { console.log('init game..'); } catch(_){}
    if (typeof window.App.purge === 'function') window.App.purge().finally(function(){
      if (window.Rooms && typeof window.Rooms.getRoomUIFlow === 'function') {
        window.Rooms.getRoomUIFlow();
      }
    });
  };

  window.App.purge = function () {
    return new Promise(function(resolve){
      try {
        $.ajax({
          url: 'gameServer.php',
          type: 'get',
          data: 'purge_rooms=1',
          dataType: 'json',
          success: function (response) { try { console.log('purged ' + response.length + ' rooms..'); } catch(_){} resolve(); },
          error: function (http, status, error) { try { console.error('error: ' + error); } catch(_){} resolve(); }
        });
      } catch(_) { resolve(); }
    });
  };

  // Legacy globals and shared state initializers (moved from game.js)
  // Ensure core globals exist for modules that rely on them
  if (typeof window.locations === 'undefined') window.locations = [];
  if (typeof window.locationsDict === 'undefined') window.locationsDict = {};
  if (typeof window.itemsDict === 'undefined') window.itemsDict = {};
  if (typeof window.playersDict === 'undefined') window.playersDict = {};
  if (typeof window.players === 'undefined') window.players = [];
  if (typeof window.player_portrait_id === 'undefined') window.player_portrait_id = '-1';
  if (typeof window.moveDirection === 'undefined') window.moveDirection = '';
  if (typeof window.cX === 'undefined') window.cX = 0;
  if (typeof window.cY === 'undefined') window.cY = 0;
  if (typeof window.oX === 'undefined') window.oX = 0;
  if (typeof window.oY === 'undefined') window.oY = 0;
  if (typeof window.currentMonsterId === 'undefined') window.currentMonsterId = -1;
  if (typeof window.canMove === 'undefined') window.canMove = false;
  if (typeof window.gameStarted === 'undefined') window.gameStarted = false;

  // Default on-screen helper instructions for start tile (drawn in Engine.component.update)
  if (typeof window.moveInstructions === 'undefined' || !window.moveInstructions) {
    window.moveInstructions = 'Use arrow keys (or WASD) to move';
  }
  if (typeof window.helpInstructions === 'undefined' || !window.helpInstructions) {
    window.helpInstructions = 'Or click a tile and press Move • Press H for help';
  }

  // Expose legacy function names used by HTML and older modules
  if (typeof window.init !== 'function') window.init = function(){ return window.App.init(); };
  if (typeof window.handleResize !== 'function') window.handleResize = function(){ return window.App.handleResize(); };
  if (typeof window.purge !== 'function') window.purge = function(){ return window.App.purge(); };
  if (typeof window.initGame !== 'function') window.initGame = function(){ return window.App.initGame(); };
  if (typeof window.getPlayer !== 'function') window.getPlayer = function(initGame){
    if (window.Players && typeof window.Players.getPlayerUIFlow === 'function') {
      return window.Players.getPlayerUIFlow(typeof initGame === 'undefined' ? true : initGame);
    }
  };
})();
