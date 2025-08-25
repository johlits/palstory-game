// Engine module: canvas loop, components, and rendering
// Exposes window.Engine and global aliases (myGameArea, component, updateGameArea, center, anythingMoving)
(function(){
  'use strict';
  if (!window.Engine) window.Engine = {};

  function myGameAreaFactory() {
    var area = {
      canvas: document.getElementById('gc'),
      start: function () {
        this.context = this.canvas.getContext('2d');
        this.frameNo = 0;
        var dpr = Math.max(1, Math.floor(window.devicePixelRatio || 1));
        this.context.setTransform(dpr, 0, 0, dpr, 0, 0);
        this._lastTs = performance.now();
        var self = this;
        (function loop(){
          var now = performance.now();
          var dt = (now - self._lastTs) / 1000; if (dt > 0.05) dt = 0.05;
          self._lastTs = now;
          self.frameNo++;
          if (!document.hidden) {
            if (typeof window.updateGameArea === 'function') window.updateGameArea(dt);
          } else {
            self._lastTs = performance.now();
          }
          self._raf = requestAnimationFrame(loop);
        })();
      },
      clear: function () {
        var ctx = this.context;
        ctx.save();
        ctx.setTransform(1,0,0,1,0,0);
        ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        ctx.restore();
      },
      stop: function () { if (this._raf) cancelAnimationFrame(this._raf); }
    };
    return area;
  }

  function component(id, width, height, color, x, y, type, name, description, stats, meta) {
    this.id = id;
    this.type = type;
    if (type === 'image') {
      this.image = new Image();
      this.image.src = color;
      try {
        var self = this;
        if (typeof window.loadImage === 'function') {
          window.loadImage(color).then(function (img) { if (img) { self.image = img; } });
        }
      } catch(_) {}
    }
    this.width = width;
    this.height = height;
    this.speedX = 0;
    this.speedY = 0;
    this.x = x;
    this.y = y;
    this.nX = x; this.nY = y;
    this.name = name;
    this.meta = meta;
    this.description = description;
    this.stats = stats;
    this._statsCache = null;
    this.hp = null; this.maxhp = null;
    this.moving = false;
    this.update = function () {
      var ctx = window.myGameArea.context;
      var ss = window.ss;
      var player = window.player;
      if (type === 'image') {
        var applyDarken = false; var brightness = 1.0;
        // With fog-of-war, avoid extra dimming so visited tiles render untouched
        if (!window.Fog) {
          if (typeof player !== 'undefined' && this !== player) {
            var dx = Math.round((this.x - player.x) / ss);
            var dy = Math.round((this.y - player.y) / ss);
            var md = Math.abs(dx) + Math.abs(dy);
            if (md > 1) { applyDarken = true; brightness = Math.max(0.2, 1 - 0.2 * (md - 1)); }
          }
        }
        var imgReady = false;
        try { imgReady = !!(this.image && this.image.complete && this.image.naturalWidth > 0); } catch(_) { imgReady = false; }
        if (imgReady) {
          if (applyDarken) { ctx.save(); ctx.filter = 'brightness(' + brightness + ')'; ctx.drawImage(this.image, this.x, this.y, this.width, this.height); ctx.restore(); }
          else { ctx.drawImage(this.image, this.x, this.y, this.width, this.height); }
        } else {
          // Visible placeholder while image loads
          ctx.save();
          ctx.fillStyle = 'rgba(0, 200, 255, 0.9)';
          ctx.strokeStyle = 'rgba(0,0,0,0.8)';
          ctx.lineWidth = 2;
          ctx.fillRect(this.x, this.y, this.width, this.height);
          ctx.strokeRect(this.x, this.y, this.width, this.height);
          ctx.restore();
        }
        if (this.meta < 3) {
          ctx.font = parseInt(Math.min(Math.max(25 - this.name.length, 10), 20)) + 'px Arial';
          ctx.fillStyle = 'white'; ctx.textAlign = 'center'; ctx.strokeStyle = 'black';
          if (this.meta === 1 && !this.moving) {
            ctx.lineWidth = 2; var ss2 = ss;
            ctx.strokeRect(this.x + ss2, this.y, ss2, ss2);
            ctx.strokeRect(this.x - ss2, this.y, ss2, ss2);
            ctx.strokeRect(this.x, this.y + ss2, ss2, ss2);
            ctx.strokeRect(this.x, this.y - ss2, ss2, ss2);
          }
          ctx.lineWidth = 4;
          ctx.strokeText(this.name, this.x + ss / 2, this.y + ss - 2);
          ctx.fillText(this.name, this.x + ss / 2, this.y + ss - 2);
          if (this.meta === 1 || this.meta === 2) {
            if (this._statsCache !== this.stats) {
              this.hp = (typeof window.getStat === 'function') ? window.getStat(this.stats, 'hp', null) : null;
              this.maxhp = (typeof window.getStat === 'function') ? window.getStat(this.stats, 'maxhp', null) : null;
              this._statsCache = this.stats;
            }
            if (this.hp !== null && this.maxhp && this.maxhp > 0) {
              var barW = Math.floor(this.width * 0.8);
              var barH = Math.max(4, Math.floor(ss * 0.08));
              var barX = Math.floor(this.x + (this.width - barW) / 2);
              var barY = Math.floor(this.y - barH - Math.max(2, Math.floor(ss * 0.06)));
              ctx.fillStyle = 'rgba(0,0,0,0.5)'; ctx.fillRect(barX - 1, barY - 1, barW + 2, barH + 2);
              var pct = Math.max(0, Math.min(1, this.hp / this.maxhp));
              ctx.fillStyle = pct > 0.5 ? '#2ecc71' : (pct > 0.25 ? '#f1c40f' : '#e74c3c');
              ctx.fillRect(barX, barY, Math.floor(barW * pct), barH);
              ctx.strokeStyle = 'rgba(255,255,255,0.7)'; ctx.lineWidth = 1; ctx.strokeRect(barX, barY, barW, barH);
            }
          }
          // First-tile help moved to UI overlay (always-on-top). Keep canvas clean here.
          // Overlay is controlled each frame in updateGameArea.
        }
      } else {
        ctx.fillStyle = color; ctx.fillRect(this.x, this.y, this.width, this.height);
      }
    };
    this.newPos = function (dt) {
      dt = (typeof dt === 'number') ? dt : (1/60);
      var mS = 6.0 * dt; var minStep = Math.max(0.5, 60 * dt);
      if (this.moving) {
        if (this.x > this.nX + 1) { this.x -= Math.max((this.x - this.nX) * mS, minStep); this.moving = true; }
        else if (this.x < this.nX - 1) { this.x += Math.max((this.nX - this.x) * mS, minStep); this.moving = true; }
        else if (this.y > this.nY + 1) { this.y -= Math.max((this.y - this.nY) * mS, minStep); this.moving = true; }
        else if (this.y < this.nY - 1) { this.y += Math.max((this.nY - this.y) * mS, minStep); this.moving = true; }
        else { if (this.moving) { this.moving = false; this.x = this.nX; this.y = this.nY; if (this.meta === 1 && (window.cX != 0 || window.cY != 0)) { if (typeof window.center === 'function') window.center(); } } }
      }
    };
  }

  function updateGameArea(dt) {
    dt = dt || 1/60;
    // update smoothed FPS metric for debug HUD
    if (dt > 0 && dt < 1) {
      var instFps = 1 / dt;
      window.__fps = (typeof window.__fps === 'number') ? (window.__fps * 0.9 + instFps * 0.1) : instFps;
    }
    // Defensive: if the game started but no player object exists yet, create a temporary placeholder at screen center
    if (window.gameStarted && (!window.player || typeof window.player !== 'object')) {
      try {
        window.player = new window.component(
          -1,
          window.ss || 64,
          window.ss || 64,
          '#ffd400',
          (window.w || 0) / 2,
          (window.h || 0) / 2,
          'rect',
          $('#player').text() || 'Player',
          '',
          $('#player_stats').text() || '',
          1
        );
        if (!window.__playerCreatedLog) { window.__playerCreatedLog = true; try { console.log('Created fallback player at', window.player.x, window.player.y); } catch(_){} }
      } catch(_){ }
    }
    if ((typeof window.player !== 'undefined' && window.player) || (window.playersLoaded && window.locationsLoaded)) {
      // One-time auto-center after initial data loads
      if (!window.__didInitialCenter && window.player && window.playersLoaded && window.locationsLoaded) {
        try { window.center(); window.__didInitialCenter = true; } catch(_){}
      }
      var isMoving = (typeof window.player !== 'undefined' && window.player && window.player.moving) || (typeof window.anythingMoving === 'function' && window.anythingMoving());
      if (typeof window.manageIdlePlayersRefresh === 'function') window.manageIdlePlayersRefresh(isMoving);
      window.myGameArea.clear();
      try { window.myGameArea.context.drawImage(window.bgImage, 0, 0, window.w, window.h); } catch(_){ }
      for (var i = 0; i < (window.locations ? window.locations.length : 0); i++) {
        var loc = window.locations[i]; loc.newPos(dt);
        if (typeof window.isOnScreenRect === 'function' ? window.isOnScreenRect(loc.x, loc.y, loc.width, loc.height, window.ss) : true) { loc.update(); }
      }
      for (var j = 0; j < (window.players ? window.players.length : 0); j++) {
        var pl = window.players[j]; pl.newPos(dt);
        if (typeof window.isOnScreenRect === 'function' ? window.isOnScreenRect(pl.x, pl.y, pl.width, pl.height, window.ss) : true) { pl.update(); }
      }
      if (window.player) { window.player.newPos(dt); window.player.update(); }
      var now = performance.now(); var ctx = window.myGameArea.context;
      var fcts = (window.__combatTexts && Array.isArray(window.__combatTexts)) ? window.__combatTexts : [];
      for (var k = fcts.length - 1; k >= 0; k--) {
        var ft = fcts[k]; var age = now - ft.t0; if (age >= ft.life) { fcts.splice(k, 1); continue; }
        var p = age / ft.life; var alpha = 1 - p; var y = ft.y - ft.vy * (age / 1000);
        var scale = 1 + (ft.kind === 'crit' ? 0.25 * (1 - p) : 0.1 * (1 - p)); var jitter = (ft.kind === 'crit') ? (Math.sin(age * 0.05) * 1.5) : 0;
        ctx.save(); ctx.globalAlpha = Math.max(0, Math.min(1, alpha));
        ctx.font = 'bold ' + Math.round(ft.size * scale) + 'px Arial, Helvetica, sans-serif'; ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
        ctx.lineWidth = 3; ctx.strokeStyle = 'rgba(0,0,0,0.7)'; ctx.fillStyle = ft.color;
        ctx.strokeText(ft.text, ft.x + jitter, y); ctx.fillText(ft.text, ft.x + jitter, y); ctx.restore();
      }
      // Render fog-of-war overlay last so undiscovered stays masked
      try { if (window.Fog && typeof window.Fog.render === 'function') { window.Fog.render(window.myGameArea.context); } } catch(_){}

      // Toggle topmost Help overlay when standing on the starting tile (0,0)
      try {
        var onStart = (window.player_x === 0 && window.player_y === 0 && window.player && !window.player.moving);
        if (window.UI && typeof window.UI.setHelpOverlayVisible === 'function') {
          window.UI.setHelpOverlayVisible(onStart, window.moveInstructions, window.helpInstructions);
        }
      } catch(_) {}

      // Debug HUD (FPS, ping age): toggle with F3 or backtick
      if (window.__debugHUD) {
        try {
          var hud = window.myGameArea.context;
          hud.save();
          hud.setTransform(1,0,0,1,0,0);
          hud.globalAlpha = 0.9;
          hud.fillStyle = 'rgba(0,0,0,0.55)';
          hud.strokeStyle = 'rgba(255,255,255,0.85)';
          hud.lineWidth = 1;
          var boxW = 180, boxH = 52;
          hud.fillRect(8, 8, boxW, boxH);
          hud.strokeRect(8, 8, boxW, boxH);
          hud.fillStyle = 'white';
          hud.font = '12px ui-monospace, SFMono-Regular, Menlo, Consolas, monospace';
          var fpsTxt = (typeof window.__fps === 'number') ? (window.__fps.toFixed(1) + ' fps') : 'fps: n/a';
          var pingAgeMs = (typeof window.__lastPingTs === 'number') ? (Math.max(0, performance.now() - window.__lastPingTs)) : null;
          var pingTxt = (pingAgeMs !== null) ? ('ping age: ' + Math.round(pingAgeMs) + ' ms') : 'ping age: n/a';
          hud.fillText('DEBUG', 16, 24);
          hud.fillText(fpsTxt, 16, 38);
          hud.fillText(pingTxt, 16, 52);
          hud.restore();
        } catch(_){ }
      }
    }
  }

  function center() {
    if (window.playersLoaded && window.locationsLoaded) {
      if (!window.player) return;
      for (var i = 0; i < window.locations.length; i++) {
        window.locations[i].nX = window.locations[i].x - window.cX * window.ss;
        window.locations[i].nY = window.locations[i].y - window.cY * window.ss;
        window.locations[i].moving = true;
      }
      for (var j = 0; j < window.players.length; j++) {
        window.players[j].nX = window.player.x + parseInt(window.playersDict[window.players[j].id].x - window.player_x) * window.ss - window.cX * window.ss;
        window.players[j].nY = window.player.y + parseInt(window.playersDict[window.players[j].id].y - window.player_y) * window.ss - window.cY * window.ss;
        window.players[j].moving = true;
      }
      window.player.nX = window.player.x - window.cX * window.ss;
      window.player.nY = window.player.y - window.cY * window.ss;
      window.player.moving = true;
      window.oX += window.cX; window.oY += window.cY; window.cX = 0; window.cY = 0;
    }
  }

  function anythingMoving() {
    for (var i = 0; i < window.locations.length; i++) { if (window.locations[i].moving) return true; }
    for (var j = 0; j < window.players.length; j++) { if (window.players[j].moving) return true; }
    return false;
  }

  // Ensure player exists (fallback rectangle at screen center)
  function ensurePlayer() {
    if (window.player && typeof window.player === 'object') return window.player;
    try {
      window.player = new window.component(
        -1,
        window.ss || 64,
        window.ss || 64,
        '#ffd400',
        (window.w || 0) / 2,
        (window.h || 0) / 2,
        'rect',
        ($('#player').text && $('#player').text()) || 'Player',
        '',
        ($('#player_stats').text && $('#player_stats').text()) || '',
        1
      );
      try { console.warn('ensurePlayer: created fallback player'); } catch(_){}
    } catch(_){ }
    return window.player;
  }

  // Export
  var myGameArea = myGameAreaFactory();
  window.Engine.myGameArea = myGameArea;
  window.Engine.component = component;
  window.Engine.updateGameArea = updateGameArea;
  window.Engine.center = center;
  window.Engine.anythingMoving = anythingMoving;
  window.Engine.ensurePlayer = ensurePlayer;

  // Global aliases for legacy code
  if (!window.myGameArea) window.myGameArea = myGameArea;
  if (!window.component) window.component = component;
  if (!window.updateGameArea) window.updateGameArea = updateGameArea;
  if (!window.center) window.center = center;
  if (!window.anythingMoving) window.anythingMoving = anythingMoving;
  if (!window.ensurePlayer) window.ensurePlayer = ensurePlayer;

  // Start game orchestration (moved from game.js)
  function startGame() {
    try { console.log('starting game..'); } catch(_){}
    // Show items box
    try {
      if (window.UI && typeof window.UI.showEl === 'function') window.UI.showEl('#items_box');
      else if (typeof window.showEl === 'function') window.showEl('#items_box');
    } catch(_){}

    var bgUrl = (typeof window.getImageUrl === 'function') ? window.getImageUrl('wooden_bg.png') : 'wooden_bg.png';
    var portraitUrl = (typeof window.getPortrait === 'function') ? window.getPortrait(window.player_portrait_id) : '';
    // Fallback portrait if helper returns empty/undefined
    if (!portraitUrl || typeof portraitUrl !== 'string') {
      try { portraitUrl = (typeof window.getPortrait === 'function') ? window.getPortrait(-1) : 'p_female_warrior.png'; } catch(_) { portraitUrl = 'p_female_warrior.png'; }
    }

    // Preload core SFX
    try {
      var sfxUrls = [
        (typeof window.getImageUrl === 'function') ? window.getImageUrl('click.mp3') : 'click.mp3',
        (typeof window.getImageUrl === 'function') ? window.getImageUrl('sword.mp3') : 'sword.mp3',
        (typeof window.getImageUrl === 'function') ? window.getImageUrl('win.mp3') : 'win.mp3'
      ];
      if (typeof window.preloadAudio === 'function') window.preloadAudio(sfxUrls);
    } catch(_){}

    // Preload images then build player and start loop
    // Only preload valid URLs and never block forever (race with timeout)
    var urls = [bgUrl, portraitUrl].filter(function(u){ return !!u && typeof u === 'string'; });
    var preload = (typeof window.preloadImages === 'function') ? window.preloadImages(urls) : Promise.resolve([]);
    var timeout = new Promise(function(resolve){ setTimeout(function(){ resolve([]); }, 1200); });
    Promise.race([preload, timeout]).then(function(imgs){
      try {
        if (Array.isArray(imgs) && imgs[0]) { window.bgImage = imgs[0]; }
        else { var _img = new Image(); _img.src = bgUrl; window.bgImage = _img; }
      } catch(_) {
        try { var _img2 = new Image(); _img2.src = bgUrl; window.bgImage = _img2; } catch(_){}
      }

      // Reset scroll offsets and compute center mapping
      window.cX = 0; window.cY = 0; window.oX = 0; window.oY = 0;
      var offsetx = (window.player_x || 0) * window.ss;
      var offsety = (window.player_y || 0) * window.ss;
      window.mapCoordFromX = window.w / 2 + offsetx;
      window.mapCoordFromY = window.h / 2 + offsety;
      window.mapCoordToX = window.player_x;
      window.mapCoordToY = window.player_y;

      // Create player component at screen center (map offsets handled via mapCoordFromX/Y)
      try {
        try { console.log('creating player.. portrait=', portraitUrl, 'name=', $('#player').text()); } catch(_){}
        window.player = new window.component(
          -1,
          window.ss,
          window.ss,
          portraitUrl,
          window.w / 2,
          window.h / 2,
          'image',
          $('#player').text(),
          '',
          $('#player_stats').text(),
          1
        );
        console.log(window.player);
        try { console.log('player created at', window.player.x, window.player.y); } catch(_){ }
        // One-time reload after player creation to ensure a clean first render
        try {
          var _once = sessionStorage.getItem('ps_reload_once');
          if (!_once) {
            sessionStorage.setItem('ps_reload_once', '1');
            setTimeout(function(){ try { window.location.reload(); } catch(_){ } }, 50);
          }
        } catch(_){ }
      } catch(e){ try { console.error('failed creating player:', e); } catch(_){} }

      // Start loop and initial fetch
      try { window.myGameArea.start(); } catch(_){ }
      window.gameStarted = true;
      // Immediately ensure player exists
      try { ensurePlayer(); } catch(_){}
      // Emergency fallback: ensure player exists even if preloading/loop failed
      setTimeout(function(){
        try {
          if (!window.player) {
            console.warn('player still missing after start, creating emergency fallback');
            window.player = new window.component(
              -1,
              window.ss || 64,
              window.ss || 64,
              '#ffd400',
              (window.w || 0) / 2,
              (window.h || 0) / 2,
              'rect',
              $('#player').text() || 'Player',
              '',
              $('#player_stats').text() || '',
              1
            );
          }
        } catch(_){ }
      }, 700);
      try {
        if (window.Players && typeof window.Players.getAllPlayers === 'function') {
          window.Players.getAllPlayers(true);
        } else if (typeof window.getAllPlayers === 'function') {
          window.getAllPlayers(true);
        }
      } catch(_){}
    });
  }
  window.Engine.startGame = startGame;
  if (typeof window.startGame !== 'function') window.startGame = startGame;

  // One-time global keybinding to toggle debug HUD (F3 or backtick `)
  if (!window.__boundDebugHUDToggle) {
    window.__boundDebugHUDToggle = true;
    try {
      document.addEventListener('keydown', function(e){
        var code = e.code || '';
        if (code === 'F3' || e.key === '`') {
          window.__debugHUD = !window.__debugHUD;
          e.preventDefault();
          try { console.log('Debug HUD:', window.__debugHUD ? 'ON' : 'OFF'); } catch(_){ }
        }
      });
    } catch(_){ }
  }
})();
