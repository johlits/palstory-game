// Locations module: fetch and render locations around the player
// Exposes: window.Locations.ensureAdjacentTilesVisible, window.Locations.getAllLocations
(function () {
  if (!window.Locations) window.Locations = {};

  var _lastNeighborsTs = 0;
  var _debounceMs = 150;
  var _inFlight = false;
  var _queued = null;
  var _debounceTimer = null;
  function _now(){ try { return performance.now(); } catch(_){ return Date.now(); } }

  function ensureAdjacentTilesVisible(cx, cy) {
    if (!window.player) return; // player not ready yet
    var now = _now();
    if (now - _lastNeighborsTs < 100) return; // throttle to 10 Hz
    _lastNeighborsTs = now;
    var room_id = $("#room_id").text();
    var neighbors = [
      { dx: 1, dy: 0 },
      { dx: -1, dy: 0 },
      { dx: 0, dy: 1 },
      { dx: 0, dy: -1 },
    ];

    neighbors.forEach(function (n) {
      var nx = cx + n.dx;
      var ny = cy + n.dy;
      var key = "" + nx + "," + ny;
      if (window.locationsDict && window.locationsDict[key]) return; // already visible

      if (window.api && typeof window.api.getLocation === 'function') {
        window.api.getLocation(room_id, nx, ny)
          .then(function (response) {
            if (response && response.length > 0) {
              var baseNX = (window.player && typeof window.player.nX === 'number') ? window.player.nX : (window.w / 2);
              var baseNY = (window.player && typeof window.player.nY === 'number') ? window.player.nY : (window.h / 2);
              var px = baseNX + (nx - player_x) * ss;
              var py = baseNY + (ny - player_y) * ss;
              var landscape = new component(
                -1,
                ss,
                ss,
                getImageUrl(response[0].image),
                px,
                py,
                "image",
                response[0].name,
                response[0].description,
                response[0].stats,
                3
              );
              locations.push(landscape);
              // Store world coordinates for O(1) lookup in render loop
              landscape.worldX = nx;
              landscape.worldY = ny;
              // attach per-tile game stats (gstats) for UI logic like gather availability
              try { landscape.gstats = response[0].gstats || ""; } catch(_) { landscape.gstats = ""; }
              try { landscape.location_type = response[0].location_type || ""; } catch(_) { landscape.location_type = ""; }
              locationsDict[key] = landscape;
            }
          })
          .catch(function (err) {
            console.error("error: " + err);
          });
      }
    });
  }

  // Return a short human-readable reason string if tile is blocked; otherwise null
  function getBlockReason(x, y) {
    try {
      var key = '' + x + ',' + y;
      var tile = (window.locationsDict && window.locationsDict[key]) ? window.locationsDict[key] : null;
      if (!tile) return null;
      var gs = '' + (typeof tile.gstats !== 'undefined' ? (tile.gstats || '') : '');
      var st = '' + (typeof tile.stats !== 'undefined' ? (tile.stats || '') : '');
      if (/(^|;)blocked=1(;|$)/.test(gs)) return 'Blocked';
      if (/(^|;)passable=0(;|$)/.test(gs)) return 'Impassable';
      if (/(^|;)walk=0(;|$)/.test(gs)) return 'No walking';
      if (/(^|;)blocked=1(;|$)/.test(st)) return 'Blocked';
      if (/(^|;)impassable=1(;|$)/.test(st)) return 'Impassable';
      return null;
    } catch(_) { return null; }
  }

  // Basic passability check using per-tile stats
  // Accepts multiple flags for flexibility:
  // - gstats: walk=0 | passable=0 | blocked=1
  // - stats:  impassable=1 | blocked=1
  function isPassable(x, y) {
    try {
      var key = '' + x + ',' + y;
      var tile = (window.locationsDict && window.locationsDict[key]) ? window.locationsDict[key] : null;
      if (!tile) return true; // unknown tiles are assumed passable (server will validate)
      var gs = '' + (typeof tile.gstats !== 'undefined' ? (tile.gstats || '') : '');
      var st = '' + (typeof tile.stats !== 'undefined' ? (tile.stats || '') : '');
      // Normalize to simple semicolon-separated key=value list
      var blocked = false;
      if (/(^|;)walk=0(;|$)/.test(gs)) blocked = true;
      if (/(^|;)passable=0(;|$)/.test(gs)) blocked = true;
      if (/(^|;)blocked=1(;|$)/.test(gs)) blocked = true;
      if (!blocked) {
        if (/(^|;)impassable=1(;|$)/.test(st)) blocked = true;
        if (/(^|;)blocked=1(;|$)/.test(st)) blocked = true;
      }
      return !blocked;
    } catch(_) { return true; }
  }

  function getAllLocations(newX, newY, dX, dY) {
    // Debounce/concurrency control: coalesce rapid calls and only run last one
    var args = Array.prototype.slice.call(arguments);
    var now = _now();
    if (_inFlight || (now - (getAllLocations._lastCallTs || 0) < _debounceMs)) {
      _queued = args; // keep the most recent args
      if (!_debounceTimer) {
        _debounceTimer = setTimeout(function(){
          _debounceTimer = null;
          if (_inFlight) return; // will be handled on completion
          var q = _queued; _queued = null;
          if (q) getAllLocations.apply(null, q);
        }, _debounceMs);
      }
      return;
    }
    getAllLocations._lastCallTs = now;
    _inFlight = true;
    console.log("get all locations..");
    var roomId = $("#room_id").text();
    if (!(window.api && typeof window.api.getAllLocations === 'function')) return;
    // Begin a new load cycle
    try { window.locationsLoaded = false; } catch(_){}

    window.api.getAllLocations(roomId)
      .then(function (response) {
        var list = Array.isArray(response) ? response : [];
        if (list.length > 0) {
          locations = [];
          locationsDict = {};
          var baseX = (window.player && typeof window.player.x === 'number') ? window.player.x : (w / 2);
          var baseY = (window.player && typeof window.player.y === 'number') ? window.player.y : (h / 2);
          $.each(list, function (index, item) {
            loadImageQueued(getImageUrl(item.image));
            var landscape = new component(
              -1,
              ss,
              ss,
              getImageUrl(item.image),
              baseX + parseInt(item.x - player_x + dX) * ss,
              baseY + parseInt(item.y - player_y + dY) * ss,
              "image",
              item.name,
              item.description,
              item.stats,
              3
            );
            locations.push(landscape);
            // Store world coordinates for O(1) lookup in render loop
            landscape.worldX = parseInt(item.x);
            landscape.worldY = parseInt(item.y);
            try { landscape.gstats = item.gstats || ""; } catch(_) { landscape.gstats = ""; }
            try { landscape.location_type = item.location_type || ""; } catch(_) { landscape.location_type = ""; }
            locationsDict["" + item.x + "," + item.y] = landscape;
          });
          locationsLoaded = true;
          // Fetch all monsters for border rendering
          try {
            if (window.Monsters && typeof window.Monsters.getAllMonsters === 'function') {
              window.Monsters.getAllMonsters();
            }
          } catch(_) {}
          if (newX === null) {
            if (window.player) { ensureAdjacentTilesVisible(player_x, player_y); }
            canMove = true;
            if (window.player) {
              if (typeof center === 'function') { center(); }
            }
            try { if (window.player) Locations.showLocationBox(window.player_x, window.player_y); } catch(_) {}
          } else {
            ensureAdjacentTilesVisible(newX, newY);
            try { Locations.showLocationBox(newX, newY); } catch(_) {}
            if (window.Monsters && typeof window.Monsters.getMonsters === 'function') {
              window.Monsters.getMonsters(newX, newY);
            } else if (typeof getMonsters === 'function') {
              getMonsters(newX, newY);
            }
            if (window.Monsters && typeof window.Monsters.preloadNearbyMonsters === 'function') {
              window.Monsters.preloadNearbyMonsters(newX, newY);
            } else if (typeof preloadNearbyMonsters === 'function') {
              preloadNearbyMonsters(newX, newY);
            }
          }
          // After locations are loaded, update Gather, Rest, Respawn, and Shop buttons based on current tile
          try { if (window.player) Locations.updateGatherButton(window.player_x, window.player_y); } catch(_) {}
          try { if (window.player) Locations.updateRestButton(window.player_x, window.player_y); } catch(_) {}
          try { if (window.player) Locations.updateRespawnButton(window.player_x, window.player_y); } catch(_) {}
          try { if (window.player) Locations.updateShopButton(window.player_x, window.player_y); } catch(_) {}
        } else {
          locationsLoaded = true;
          if (newX === null) {
            if (window.player) { ensureAdjacentTilesVisible(player_x, player_y); }
            canMove = true;
            if (window.player) {
              if (typeof move === 'function') { move("na"); }
              if (typeof center === 'function') { center(); }
            }
          } else {
            ensureAdjacentTilesVisible(newX, newY);
            if (window.Monsters && typeof window.Monsters.getMonsters === 'function') {
              window.Monsters.getMonsters(newX, newY);
            } else if (typeof getMonsters === 'function') {
              getMonsters(newX, newY);
            }
            if (window.Monsters && typeof window.Monsters.preloadNearbyMonsters === 'function') {
              window.Monsters.preloadNearbyMonsters(newX, newY);
            } else if (typeof preloadNearbyMonsters === 'function') {
              preloadNearbyMonsters(newX, newY);
            }
          }
          try { if (window.player) Locations.updateGatherButton(window.player_x, window.player_y); } catch(_) {}
          try { if (window.player) Locations.updateRestButton(window.player_x, window.player_y); } catch(_) {}
          try { if (window.player) Locations.updateRespawnButton(window.player_x, window.player_y); } catch(_) {}
          try { if (window.player) Locations.updateShopButton(window.player_x, window.player_y); } catch(_) {}
        }
      })
      .catch(function (err) {
        console.error("error: " + err);
        try { if (window.locationsLoaded !== true) window.locationsLoaded = true; } catch(_){}
      })
      .finally(function(){
        _inFlight = false;
        try { if (window.locationsLoaded !== true) window.locationsLoaded = true; } catch(_){}
        var q = _queued; _queued = null;
        if (q) { // run the latest queued call after current completes
          // slight delay to allow UI to settle
          setTimeout(function(){ getAllLocations.apply(null, q); }, _debounceMs);
        }
      });
  }

  window.Locations.ensureAdjacentTilesVisible = ensureAdjacentTilesVisible;
  window.Locations.getAllLocations = getAllLocations;
  window.Locations.isPassable = isPassable;
  window.Locations.getBlockReason = getBlockReason;
  // Populate #location_box for a given tile (defaults to current tile)
  window.Locations.showLocationBox = function(cx, cy){
    try {
      var x = (typeof cx === 'number') ? cx : window.player_x;
      var y = (typeof cy === 'number') ? cy : window.player_y;
      var key = '' + x + ',' + y;
      var location = (window.locationsDict && window.locationsDict[key]) ? window.locationsDict[key] : null;
      if (!location) return;
      if (window.UI && typeof window.UI.showEl === 'function') { window.UI.showEl('#location_box'); }
      else { try { $('#location_box').removeClass('hidden'); } catch(_) {} }
      try { $(".location_name").text(location.name); } catch(_) {}
      try { $("#location_image").attr("src", location.image.currentSrc); } catch(_) {}
      try { $("#location_description").text(location.description); } catch(_) {}
      try {
        var location_stats = location.stats || '';
        var location_fields = String(location_stats).split(";");
        var spawnsText = 'None';
        for (var i = 0; i < location_fields.length; i++) {
          var field = location_fields[i];
          if (field.indexOf('spawns') === 0) { spawnsText = field.split('=')[1].split(',').join(', '); break; }
        }
        $("#location_spawns").text(spawnsText);
      } catch(_) {}
    } catch(_) {}
  };
  window.Locations.updateGatherButton = function(cx, cy){
    try {
      var btn = document.getElementById('gatherBtn'); if (!btn) return;
      // default hide
      if (btn.classList) btn.classList.add('hidden'); else btn.style.display = 'none';
      var key = '' + cx + ',' + cy;
      var tile = (window.locationsDict && window.locationsDict[key]) ? window.locationsDict[key] : null;
      var gstats = (tile && typeof tile.gstats !== 'undefined') ? ('' + tile.gstats) : undefined;
      var gatherable = /(^|;)gather=1(;|$)/.test(gstats);
      if (gatherable) { if (btn.classList) btn.classList.remove('hidden'); else btn.style.display = ''; if (window.UI && typeof window.UI.updateLocationActionsButton === 'function') window.UI.updateLocationActionsButton(); return; }
      // If gstats explicitly says not gatherable, keep hidden and do not fetch
      if (typeof gstats === 'string' && /(^|;)gather=0(;|$)/.test(gstats)) { return; }
      // If we truly don't have gstats yet (undefined), fetch the current tile to update it
      if ((!tile || typeof tile.gstats === 'undefined') && window.api && typeof window.api.getLocation === 'function') {
        var room_id = $('#room_id').text();
        window.api.getLocation(room_id, cx, cy).then(function(resp){
          if (resp && resp.length > 0) {
            // ensure dict entry exists and attach gstats
            if (!tile) {
              var baseX = (window.player && typeof window.player.x === 'number') ? window.player.x : (w / 2);
              var baseY = (window.player && typeof window.player.y === 'number') ? window.player.y : (h / 2);
              var landscape = new component(-1, ss, ss, getImageUrl(resp[0].image), baseX, baseY, 'image', resp[0].name, resp[0].description, resp[0].stats, 3);
              locations.push(landscape);
              locationsDict[key] = landscape;
              tile = landscape;
            }
            try { tile.gstats = (typeof resp[0].gstats !== 'undefined') ? (resp[0].gstats || '') : ''; } catch(_) { tile.gstats = ''; }
            try { tile.location_type = (typeof resp[0].location_type !== 'undefined') ? (resp[0].location_type || '') : ''; } catch(_) { tile.location_type = ''; }
            var gs2 = tile.gstats || '';
            if (/(^|;)gather=1(;|$)/.test('' + gs2)) { if (btn.classList) btn.classList.remove('hidden'); else btn.style.display = ''; if (window.UI && typeof window.UI.updateLocationActionsButton === 'function') window.UI.updateLocationActionsButton(); }
          }
        }).catch(function(_){});
      }
    } catch (e) { /* noop */ }
  };
  window.Locations.gather = function() {
    try {
      var playerName = $("#player").text();
      var roomId = $("#room_id").text();
      if (!window.api || typeof window.api.gatherResource !== 'function') return;
      if (typeof window.playSound === 'function') { window.playSound(window.getImageUrl("click.mp3")); }
      window.api.gatherResource(playerName, roomId)
        .then(function(resp){
          console.log('gather resp', resp);
          if (window.Items && typeof window.Items.getItems === 'function') {
            window.Items.getItems();
          }
          var wasOk = (Array.isArray(resp) && resp[0] === 'ok');
          if (wasOk) {
            // Clear gather flag locally (server consumed it) and hide button
            try {
              var key = '' + window.player_x + ',' + window.player_y;
              if (window.locationsDict && window.locationsDict[key]) {
                window.locationsDict[key].gstats = '';
              }
            } catch (_) {}
            try { Locations.updateGatherButton(window.player_x, window.player_y); } catch(_) {}
          }

          // Show gather result popup
          try {
            var box = document.getElementById('gatherBox');
            var dlg = document.getElementById('gather-dialog');
            if (box && dlg) {
              var msg = 'You search the area...';
              if (Array.isArray(resp)) {
                if (resp[0] === 'ok' && resp[1] === 'item' && resp[2]) {
                  msg = 'You found: ' + resp[2] + '!';
                } else if (resp[0] === 'ok' && resp[1] === 'none') {
                  msg = 'You found nothing this time.';
                } else if (resp[0] === 'no_gather_here') {
                  msg = 'There is nothing to gather here.';
                  // Not gatherable: hide button for this tile
                  try {
                    var key0 = '' + window.player_x + ',' + window.player_y;
                    if (window.locationsDict && window.locationsDict[key0]) { window.locationsDict[key0].gstats = 'gather=0'; }
                    var btn0 = document.getElementById('gatherBtn'); if (btn0) { if (btn0.classList) btn0.classList.add('hidden'); else btn0.style.display = 'none'; }
                    Locations.updateGatherButton(window.player_x, window.player_y);
                  } catch(_) {}
                } else if (resp[0] === 'blocked_by_monster') {
                  msg = 'A monster blocks your way! Defeat it before gathering.';
                } else if (resp[0] === 'cooldown') {
                  msg = 'You need a moment before gathering again.';
                } else if (resp[0] === 'no_resources' || resp[0] === 'no_drops') {
                  msg = 'There is nothing useful to gather here.';
                  // Not gatherable: hide button for this tile
                  try {
                    var key1 = '' + window.player_x + ',' + window.player_y;
                    if (window.locationsDict && window.locationsDict[key1]) { window.locationsDict[key1].gstats = 'gather=0'; }
                    var btn1 = document.getElementById('gatherBtn'); if (btn1) { if (btn1.classList) btn1.classList.add('hidden'); else btn1.style.display = 'none'; }
                    Locations.updateGatherButton(window.player_x, window.player_y);
                  } catch(_) {}
                } else if (resp[0] === 'err_db' || resp[0] === 'err') {
                  msg = 'Something went wrong while gathering. Please try again.';
                }
              } else if (typeof resp === 'string') {
                // Some servers may return a plain message string; use it directly
                msg = resp;
              }
              box.textContent = msg;
              // If the final message indicates no useful resources, hide the button as a fallback
              try {
                var lower = ('' + msg).toLowerCase();
                if (lower.indexOf('there is nothing useful to gather here') !== -1 ||
                    lower.indexOf('there is nothing to gather here') !== -1) {
                  var keyN = '' + window.player_x + ',' + window.player_y;
                  if (window.locationsDict && window.locationsDict[keyN]) { window.locationsDict[keyN].gstats = 'gather=0'; }
                  Locations.updateGatherButton(window.player_x, window.player_y);
                }
              } catch(_) {}
              // Use success vs error sound
              if (typeof window.playSound === 'function') {
                if (Array.isArray(resp) && resp[0] === 'ok') { window.playSound(window.getImageUrl('coin.mp3')); }
                else { window.playSound(window.getImageUrl('click.mp3')); }
              }
              dlg.showModal();
            }
          } catch(_) {}
        })
        .catch(function(err){ console.error('gather error: ' + err); });
    } catch(e) { console.error(e); }
  };

  // Rest at current location to restore HP/MP
  window.Locations.rest = function() {
    try {
      var playerName = $("#player").text();
      var roomId = $("#room_id").text();
      if (!window.api || typeof window.api.restAtLocation !== 'function') return;
      if (typeof window.playSound === 'function') { window.playSound(window.getImageUrl("click.mp3")); }
      
      window.api.restAtLocation(playerName, roomId)
        .then(function(resp){
          console.log('rest resp', resp);
          
          // Update player stats if successful
          if (resp && resp.success && resp.player) {
            try {
              // Refresh player display
              if (window.Players && typeof window.Players.getPlayers === 'function') {
                window.Players.getPlayers();
              }
            } catch (_) {}
          }
          
          // Show rest result notification
          try {
            var msg = resp.message || 'Rest complete.';
            if (resp.success) {
              msg += ' (HP +' + resp.hp_restored + ', MP +' + resp.mp_restored + ')';
            }
            
            // Create a simple notification (reuse gather dialog for now)
            var box = document.getElementById('gatherBox');
            var dlg = document.getElementById('gather-dialog');
            if (box && dlg) {
              var msgEl = box.querySelector('.gather-message');
              if (msgEl) msgEl.textContent = msg;
              
              if (typeof window.playSound === 'function') {
                if (resp.success) { window.playSound(window.getImageUrl('coin.mp3')); }
                else { window.playSound(window.getImageUrl('click.mp3')); }
              }
              dlg.showModal();
            } else {
              // Fallback: alert
              alert(msg);
            }
          } catch(_) {
            console.log('Rest result:', resp.message);
          }
        })
        .catch(function(err){ console.error('rest error: ' + err); });
    } catch(e) { console.error(e); }
  };

  // Update rest button visibility based on location type
  window.Locations.updateRestButton = function(x, y) {
    try {
      var key = '' + x + ',' + y;
      var loc = window.locationsDict[key];
      var btn = document.getElementById('restBtn');
      if (!btn) return;
      
      // Show rest button if at a town or rest_spot
      if (loc && loc.location_type && (loc.location_type === 'town' || loc.location_type === 'rest_spot')) {
        if (btn.classList) btn.classList.remove('hidden');
        else btn.style.display = '';
      } else {
        if (btn.classList) btn.classList.add('hidden');
        else btn.style.display = 'none';
      }
      if (window.UI && typeof window.UI.updateLocationActionsButton === 'function') window.UI.updateLocationActionsButton();
    } catch(_) {}
  };

  // Set respawn point at current location
  window.Locations.setRespawn = function() {
    try {
      var playerName = $("#player").text();
      var roomId = $("#room_id").text();
      if (!window.api || typeof window.api.setRespawnPoint !== 'function') return;
      if (typeof window.playSound === 'function') { window.playSound(window.getImageUrl("click.mp3")); }
      
      window.api.setRespawnPoint(playerName, roomId)
        .then(function(resp){
          console.log('set respawn resp', resp);
          
          // Show result notification
          try {
            var msg = resp.message || 'Respawn point set.';
            
            // Reuse gather dialog for notification
            var box = document.getElementById('gatherBox');
            var dlg = document.getElementById('gather-dialog');
            if (box && dlg) {
              var msgEl = box.querySelector('.gather-message');
              if (msgEl) msgEl.textContent = msg;
              
              if (typeof window.playSound === 'function') {
                if (resp.success) { window.playSound(window.getImageUrl('coin.mp3')); }
                else { window.playSound(window.getImageUrl('click.mp3')); }
              }
              dlg.showModal();
            } else {
              // Fallback: alert
              alert(msg);
            }
          } catch(_) {
            console.log('Set respawn result:', resp.message);
          }
        })
        .catch(function(err){ console.error('set respawn error: ' + err); });
    } catch(e) { console.error(e); }
  };

  // Update respawn button visibility based on location type
  window.Locations.updateRespawnButton = function(x, y) {
    try {
      var key = '' + x + ',' + y;
      var loc = window.locationsDict[key];
      var btn = document.getElementById('respawnBtn');
      if (!btn) return;
      
      // Show respawn button only at towns
      if (loc && loc.location_type && loc.location_type === 'town') {
        if (btn.classList) btn.classList.remove('hidden');
        else btn.style.display = '';
      } else {
        if (btn.classList) btn.classList.add('hidden');
        else btn.style.display = 'none';
      }
      if (window.UI && typeof window.UI.updateLocationActionsButton === 'function') window.UI.updateLocationActionsButton();
    } catch(_) {}
  };

  // Update shop button visibility based on location type
  window.Locations.updateShopButton = function(x, y) {
    try {
      var key = '' + x + ',' + y;
      var loc = window.locationsDict[key];
      var btn = document.getElementById('shopBtn');
      if (!btn) return;
      
      // Show shop button only at towns
      if (loc && loc.location_type && loc.location_type === 'town') {
        if (btn.classList) btn.classList.remove('hidden');
        else btn.style.display = '';
      } else {
        if (btn.classList) btn.classList.add('hidden');
        else btn.style.display = 'none';
      }
      if (window.UI && typeof window.UI.updateLocationActionsButton === 'function') window.UI.updateLocationActionsButton();
    } catch(_) {}
  };

  // Update storage button visibility based on location type
  window.Locations.updateStorageButton = function(x, y) {
    try {
      var key = '' + x + ',' + y;
      var loc = window.locationsDict[key];
      var btn = document.getElementById('storageBtn');
      if (!btn) return;
      
      // Show storage button only at towns
      if (loc && loc.location_type && loc.location_type === 'town') {
        if (btn.classList) btn.classList.remove('hidden');
        else btn.style.display = '';
      } else {
        if (btn.classList) btn.classList.add('hidden');
        else btn.style.display = 'none';
      }
      if (window.UI && typeof window.UI.updateLocationActionsButton === 'function') window.UI.updateLocationActionsButton();
    } catch(_) {}
  };

  // Update all location-related UI elements for current tile
  window.Locations.updateCurrentTile = function(x, y) {
    try {
      var px = (typeof x === 'number') ? x : window.player_x;
      var py = (typeof y === 'number') ? y : window.player_y;
      
      // Update location box
      if (window.Locations && typeof window.Locations.showLocationBox === 'function') {
        window.Locations.showLocationBox(px, py);
      }
      
      // Update all buttons
      if (window.Locations && typeof window.Locations.updateGatherButton === 'function') {
        window.Locations.updateGatherButton(px, py);
      }
      if (window.Locations && typeof window.Locations.updateRestButton === 'function') {
        window.Locations.updateRestButton(px, py);
      }
      if (window.Locations && typeof window.Locations.updateRespawnButton === 'function') {
        window.Locations.updateRespawnButton(px, py);
      }
      if (window.Locations && typeof window.Locations.updateShopButton === 'function') {
        window.Locations.updateShopButton(px, py);
      }
      if (window.Locations && typeof window.Locations.updateStorageButton === 'function') {
        window.Locations.updateStorageButton(px, py);
      }
      // Update the Actions button visibility
      if (window.UI && typeof window.UI.updateLocationActionsButton === 'function') {
        window.UI.updateLocationActionsButton();
      }
    } catch(e) {
      console.error('Error updating current tile:', e);
    }
  };
})();
