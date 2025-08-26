// Movement module (safe to load before game.js)
(function(){
  'use strict';

  // Movement state: single buffered direction and enable flag
  var state = {
    bufferedDir: null,
    enabled: true,
    cooldownUntil: 0,
  };

  function dispatch(evtName, detail) {
    try { window.dispatchEvent(new CustomEvent('palstory:' + evtName, { detail: detail || {} })); } catch(_) {}
  }

  function ensureAdj(x, y) {
    if (window.Locations && typeof window.Locations.ensureAdjacentTilesVisible === 'function') {
      window.Locations.ensureAdjacentTilesVisible(x, y);
    } else if (typeof ensureAdjacentTilesVisible === 'function') {
      ensureAdjacentTilesVisible(x, y);
    }
  }

  function getAllLoc(x, y, dx, dy) {
    if (window.Locations && typeof window.Locations.getAllLocations === 'function') {
      return window.Locations.getAllLocations(x, y, dx, dy);
    } else if (typeof getAllLocations === 'function') {
      return getAllLocations(x, y, dx, dy);
    }
  }

  function refreshPlayers() {
    if (window.Players && typeof window.Players.getAllPlayers === 'function') {
      try { window.Players.getAllPlayers(false); } catch (e) {}
    } else if (typeof getAllPlayers === 'function') {
      try { getAllPlayers(false); } catch (e2) {}
    }
  }

  function getMons(x, y) {
    if (window.Monsters && typeof window.Monsters.getMonsters === 'function') {
      return window.Monsters.getMonsters(x, y);
    } else if (typeof getMonsters === 'function') {
      return Promise.resolve().then(function(){ getMonsters(x, y); });
    }
    return Promise.resolve();
  }

  function move(dir) {
    // Disable movement when tab hidden
    if (document.hidden) return;
    if (!state.enabled) return;
    // Validate direction
    if (!(dir === 'up' || dir === 'down' || dir === 'left' || dir === 'right')) {
      return;
    }

    // If something is moving, buffer a single intent
    if (window.player && (window.player.moving || (typeof window.anythingMoving === 'function' && window.anythingMoving()))) {
      if (!state.bufferedDir) { state.bufferedDir = dir; }
      return;
    }
    if (window.canMove !== true) { if (!state.bufferedDir) { state.bufferedDir = dir; } return; }
    if (!window.player) { console.warn('move ignored: player not ready'); return; }

    console.log('move ' + dir);
    refreshPlayers();
    window.canMove = false;
    dispatch('move:start', { dir: dir, x: window.player_x, y: window.player_y });

    var room_id = $('#room_id').text();
    var nx = window.player_x;
    var ny = window.player_y;

    if (dir === 'up') ny = ny - 1;
    if (dir === 'down') ny = ny + 1;
    if (dir === 'left') nx = nx - 1;
    if (dir === 'right') nx = nx + 1;

    // Client-side guard: prevent stepping into clearly impassable tiles we already know about
    try {
      if (window.Locations && typeof window.Locations.isPassable === 'function') {
        var passable = window.Locations.isPassable(nx, ny);
        if (!passable) {
          // restore move availability and emit a blocked event
          window.canMove = true;
          dispatch('move:complete', { result: 'blocked', x: window.player_x, y: window.player_y, nx: nx, ny: ny });
          // subtle feedback: sound and optional floating text
          try { if (typeof window.playSound === 'function') { window.playSound(window.getImageUrl('click.mp3')); } } catch(_) {}
          try {
            if (Array.isArray(window.__combatTexts) && window.player) {
              window.__combatTexts.push({ text: 'Blocked', x: window.player.x + (window.ss/2), y: window.player.y - 8, color: '#e74c3c', size: 14, t0: performance.now(), life: 600, vy: 22, kind: 'normal' });
            }
          } catch(_) {}
          return; // do not call server for obviously blocked tiles
        }
      }
    } catch(_) {}

    $.ajax({
      url: 'gameServer.php',
      type: 'get',
      data: 'move_player=' + $('#player').text() + '&room_id=' + room_id + '&x=' + nx + '&y=' + ny,
      dataType: 'json',
      success: function (response) {
        if (response && response[0] == 'ok') {
          var dx = parseInt(response[1]);
          var dy = parseInt(response[2]);

          if (dy === -1) { window.player.nX = window.player.x; window.player.nY = window.player.y - window.ss; window.cY--; }
          if (dy === 1)  { window.player.nX = window.player.x; window.player.nY = window.player.y + window.ss; window.cY++; }
          if (dx === -1) { window.player.nX = window.player.x - window.ss; window.player.nY = window.player.y; window.cX--; }
          if (dx === 1)  { window.player.nX = window.player.x + window.ss; window.player.nY = window.player.y; window.cX++; }
          // Trigger player animation toward the target
          window.player.moving = true;

          window.player_x = window.player_x + dx;
          window.player_y = window.player_y + dy;
          $('#player_x').text(window.player_x);
          $('#player_y').text(window.player_y);
          $('#player_bx').text(window.bX(window.player_x));
          $('#player_by').text(window.bY(window.player_y));

          if (response[3] === 'draw') {
            // new location
            $.ajax({
              url: 'gameServer.php',
              type: 'get',
              data: 'get_location=' + $('#room_id').text() + '&x=' + window.player_x + '&y=' + window.player_y,
              dataType: 'json',
              success: function (resp) {
                if (resp && resp.length > 0) {
                  var baseNX = (window.player && typeof window.player.nX === 'number') ? window.player.nX : (window.w / 2);
                  var baseNY = (window.player && typeof window.player.nY === 'number') ? window.player.nY : (window.h / 2);
                  var landscape = new window.component(
                    -1,
                    window.ss,
                    window.ss,
                    window.getImageUrl(resp[0].image),
                    baseNX,
                    baseNY,
                    'image',
                    resp[0].name,
                    resp[0].description,
                    resp[0].stats,
                    3
                  );
                  window.locations.push(landscape);
                  window.locationsDict['' + resp[0].x + ',' + resp[0].y] = landscape;
                  ensureAdj(window.player_x, window.player_y);
                  getMons(window.player_x, window.player_y).then(function(){ window.canMove = true; dispatch('move:complete', { result: 'draw', x: window.player_x, y: window.player_y }); try { if (window.Locations && typeof window.Locations.updateGatherButton === 'function') { window.Locations.updateGatherButton(window.player_x, window.player_y); } } catch(_) {} maybeFlushBuffer(); });
                } else {
                  window.canMove = true; dispatch('move:complete', { result: 'no_draw', x: window.player_x, y: window.player_y }); try { if (window.Locations && typeof window.Locations.updateGatherButton === 'function') { window.Locations.updateGatherButton(window.player_x, window.player_y); } } catch(_) {} maybeFlushBuffer();
                }
              },
              error: function () { window.canMove = true; dispatch('move:complete', { result: 'error' }); maybeFlushBuffer(); }
            });
          } else {
            var location = window.locationsDict['' + window.player_x + ',' + window.player_y];
            if (!location) {
              console.log('no new location, but exists in db, get all locations');
              getAllLoc(window.player_x, window.player_y, dx, dy);
            } else {
              console.log('no new location, but get monsters');
              ensureAdj(window.player_x, window.player_y);
              getMons(window.player_x, window.player_y).then(function(){ window.canMove = true; dispatch('move:complete', { result: 'no_draw', x: window.player_x, y: window.player_y }); try { if (window.Locations && typeof window.Locations.updateGatherButton === 'function') { window.Locations.updateGatherButton(window.player_x, window.player_y); } } catch(_) {} maybeFlushBuffer(); });
            }
          }
        } else if ((Array.isArray(response) && response[0] === 'fight') || (response && typeof response === 'object' && !Array.isArray(response) && response.type === 'fight')) {
          if (typeof window.handleFightResponse === 'function') {
            window.handleFightResponse(response, { fromMove: true });
          }
        } else {
          // Distinguish server-side blocked from generic error
          if (Array.isArray(response) && response[0] === 'err' && response[1] === 'blocked') {
            window.canMove = true;
            dispatch('move:complete', { result: 'blocked', x: window.player_x, y: window.player_y, nx: nx, ny: ny });
            try { if (typeof window.playSound === 'function') { window.playSound(window.getImageUrl('click.mp3')); } } catch(_) {}
            try {
              if (Array.isArray(window.__combatTexts) && window.player) {
                window.__combatTexts.push({ text: 'Blocked', x: window.player.x + (window.ss/2), y: window.player.y - 8, color: '#e74c3c', size: 14, t0: performance.now(), life: 600, vy: 22, kind: 'normal' });
              }
            } catch(_) {}
            maybeFlushBuffer();
            return;
          }

          window.canMove = true; dispatch('move:complete', { result: 'err' });
          try {
            if (response && response[1] === 'rate_limited') {
              state.cooldownUntil = Date.now() + 250; // brief backoff
              setTimeout(maybeFlushBuffer, 260);
            } else {
              maybeFlushBuffer();
            }
          } catch (e) { maybeFlushBuffer(); }
          try { console.error(response && response[1]); } catch (e) {}
        }
      },
      error: function () { window.canMove = true; dispatch('move:complete', { result: 'error' }); maybeFlushBuffer(); }
    });
  }

  function maybeFlushBuffer() {
    if (document.hidden || !state.enabled) { state.bufferedDir = null; return; }
    if (state.cooldownUntil && Date.now() < state.cooldownUntil) { return; }
    if (!state.bufferedDir) return;
    // Try to fire immediately if idle; otherwise retry shortly
    var dir = state.bufferedDir;
    if (window.canMove === true && window.player && !window.player.moving && !(typeof window.anythingMoving === 'function' && window.anythingMoving())) {
      state.bufferedDir = null;
      move(dir);
    } else {
      // Retry once after a frame (~50ms)
      setTimeout(function(){
        if (window.canMove === true && window.player && !window.player.moving && !(typeof window.anythingMoving === 'function' && window.anythingMoving())) {
          var d = state.bufferedDir; state.bufferedDir = null; if (d) move(d);
        }
      }, 50);
    }
  }

  // Visibility handling: disable moves while hidden
  document.addEventListener('visibilitychange', function(){
    state.enabled = !document.hidden;
    if (document.hidden) {
      state.bufferedDir = null; // drop buffer when hidden
    }
  });

  if (!window.Movement) window.Movement = {};
  window.Movement.move = move;
  window.Movement.flush = maybeFlushBuffer;
  window.Movement.state = state;
})();
