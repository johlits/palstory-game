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
              locationsDict[key] = landscape;
            }
          })
          .catch(function (err) {
            console.error("error: " + err);
          });
      }
    });
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
            locationsDict["" + item.x + "," + item.y] = landscape;
          });
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
})();
