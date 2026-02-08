// Utilities module (safe to load before game.js)
(function(){
  'use strict';

  // ============================================================================
  // CENTRALIZED STAT PARSING UTILITIES
  // ============================================================================

  /**
   * Parse a semicolon-delimited stat string into an object.
   * Example: "hp=100;atk=10;def=5" => {hp: '100', atk: '10', def: '5'}
   * @param {string} stats - The stat string to parse
   * @returns {Object} Object with stat key/value pairs
   */
  function parseStatsToObject(stats) {
    var result = {};
    if (!stats || typeof stats !== 'string') return result;
    
    var parts = stats.split(';');
    for (var i = 0; i < parts.length; i++) {
      var part = parts[i].trim();
      if (part === '' || part.indexOf('=') === -1) continue;
      var kv = part.split('=');
      if (kv.length >= 2) {
        result[kv[0].trim()] = kv.slice(1).join('=').trim(); // Handle values with '=' in them
      }
    }
    return result;
  }

  /**
   * Get a single stat value from a stat string.
   * @param {string} stats - The stat string
   * @param {string} key - The stat key to retrieve
   * @param {*} defVal - Default value if key not found
   * @returns {string|*} The stat value or default
   */
  function getStatRaw(stats, key, defVal) {
    if (typeof defVal === 'undefined') defVal = null;
    var parsed = parseStatsToObject(stats);
    return parsed.hasOwnProperty(key) ? parsed[key] : defVal;
  }

  /**
   * Get a stat as an integer.
   * @param {string} stats - The stat string
   * @param {string} key - The stat key to retrieve
   * @param {number} defVal - Default value if key not found
   * @returns {number} The stat value as integer
   */
  function getStat(stats, key, defVal) {
    if (typeof defVal === 'undefined') defVal = null;
    var val = getStatRaw(stats, key, null);
    if (val === null) return defVal;
    var n = parseInt(val, 10);
    return isNaN(n) ? defVal : n;
  }

  /**
   * Convert an object back to a stat string.
   * Example: {hp: 100, atk: 10} => "hp=100;atk=10;"
   * @param {Object} statsObj - Object with stats
   * @returns {string} The stat string
   */
  function objectToStatString(statsObj) {
    if (!statsObj || typeof statsObj !== 'object') return '';
    var parts = [];
    for (var key in statsObj) {
      if (statsObj.hasOwnProperty(key)) {
        parts.push(key + '=' + statsObj[key]);
      }
    }
    return parts.length > 0 ? parts.join(';') + ';' : '';
  }

  /**
   * Update or add a stat in a stat string.
   * @param {string} stats - The original stat string
   * @param {string} key - The stat key to update
   * @param {*} value - The new value
   * @returns {string} Updated stat string
   */
  function setStat(stats, key, value) {
    var parsed = parseStatsToObject(stats);
    parsed[key] = value;
    return objectToStatString(parsed);
  }

  /**
   * Remove a stat from a stat string.
   * @param {string} stats - The original stat string
   * @param {string} key - The stat key to remove
   * @returns {string} Updated stat string
   */
  function removeStat(stats, key) {
    var parsed = parseStatsToObject(stats);
    delete parsed[key];
    return objectToStatString(parsed);
  }

  /**
   * Extract all cooldown stats (cd_*) from a stat string.
   * @param {string} stats - The stat string
   * @returns {Object} Object of cooldown key => timestamp
   */
  function extractCooldowns(stats) {
    var cooldowns = {};
    var parsed = parseStatsToObject(stats);
    for (var key in parsed) {
      if (parsed.hasOwnProperty(key) && key.indexOf('cd_') === 0) {
        cooldowns[key] = parseInt(parsed[key], 10) || 0;
      }
    }
    return cooldowns;
  }

  /**
   * Parse player stats with defaults.
   * @param {string} stats - The stat string
   * @returns {Object} Object with all player stats
   */
  function parsePlayerStats(stats) {
    var p = parseStatsToObject(stats);
    return {
      lvl: parseInt(p.lvl, 10) || 1,
      exp: parseInt(p.exp, 10) || 0,
      hp: parseInt(p.hp, 10) || 10,
      maxhp: parseInt(p.maxhp, 10) || 10,
      mp: parseInt(p.mp, 10) || 0,
      maxmp: parseInt(p.maxmp, 10) || 0,
      atk: parseInt(p.atk, 10) || 1,
      def: parseInt(p.def, 10) || 0,
      spd: parseInt(p.spd, 10) || 1,
      evd: parseInt(p.evd, 10) || 0,
      crt: parseInt(p.crt, 10) || 5,
      gold: parseInt(p.gold, 10) || 0,
      skill_points: parseInt(p.skill_points, 10) || 0,
      job: p.job || 'none',
      unlocked_skills: p.unlocked_skills || '',
      cooldowns: extractCooldowns(stats),
      _raw: p
    };
  }

  /**
   * Parse monster stats with defaults.
   * @param {string} stats - The stat string
   * @returns {Object} Object with all monster stats
   */
  function parseMonsterStats(stats) {
    var p = parseStatsToObject(stats);
    return {
      hp: parseInt(p.hp, 10) || 1,
      maxhp: parseInt(p.maxhp, 10) || 1,
      mp: parseInt(p.mp, 10) || 0,
      maxmp: parseInt(p.maxmp, 10) || 0,
      atk: parseInt(p.atk, 10) || 1,
      def: parseInt(p.def, 10) || 0,
      spd: parseInt(p.spd, 10) || 1,
      evd: parseInt(p.evd, 10) || 0,
      crt: parseInt(p.crt, 10) || 5,
      drops: p.drops || '',
      gold: parseInt(p.gold, 10) || 0,
      exp: parseInt(p.exp, 10) || 0,
      cooldowns: extractCooldowns(stats),
      _raw: p
    };
  }

  /**
   * Parse item stats with defaults.
   * @param {string} stats - The stat string
   * @returns {Object} Object with item stats
   */
  function parseItemStats(stats) {
    var p = parseStatsToObject(stats);
    return {
      atk: parseInt(p.atk, 10) || 0,
      def: parseInt(p.def, 10) || 0,
      spd: parseInt(p.spd, 10) || 0,
      evd: parseInt(p.evd, 10) || 0,
      crt: parseInt(p.crt, 10) || 0,
      type: p.type || '',
      _raw: p
    };
  }

  /**
   * Sum stats from multiple items.
   * @param {Array} items - Array of stat strings or parsed objects
   * @returns {Object} Summed stats
   */
  function sumItemStats(items) {
    var totals = { atk: 0, def: 0, spd: 0, evd: 0, crt: 0 };
    for (var i = 0; i < items.length; i++) {
      var item = items[i];
      var stats = (typeof item === 'string') ? parseItemStats(item) : item;
      totals.atk += stats.atk || 0;
      totals.def += stats.def || 0;
      totals.spd += stats.spd || 0;
      totals.evd += stats.evd || 0;
      totals.crt += stats.crt || 0;
    }
    return totals;
  }

  // Export Stats utilities
  if (!window.Stats) window.Stats = {};
  window.Stats.parse = parseStatsToObject;
  window.Stats.get = getStat;
  window.Stats.getRaw = getStatRaw;
  window.Stats.set = setStat;
  window.Stats.remove = removeStat;
  window.Stats.toString = objectToStatString;
  window.Stats.extractCooldowns = extractCooldowns;
  window.Stats.parsePlayer = parsePlayerStats;
  window.Stats.parseMonster = parseMonsterStats;
  window.Stats.parseItem = parseItemStats;
  window.Stats.sumItems = sumItemStats;

  // Legacy global alias
  if (typeof window.getStat !== 'function') window.getStat = getStat;

  // ============================================================================
  // OTHER UTILITIES
  // ============================================================================

  // Escape for RegExp
  function _escRe(s) { return String(s || '').replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }

  // Only define if not already present (to avoid clashes during incremental migration)
  if (typeof window._escRe !== 'function') {
    window._escRe = _escRe;
  }

  // Mouse position helper
  function getMousePos(canvas, evt) {
    try {
      var rect = canvas.getBoundingClientRect();
      return {
        x: evt.clientX - rect.left,
        y: evt.clientY - rect.top,
      };
    } catch (_) {
      return { x: 0, y: 0 };
    }
  }
  if (typeof window.getMousePos !== 'function') {
    window.getMousePos = getMousePos;
  }

  // Coordinate label helpers (moved from game.js)
  function bX(x) { return x <= 0 ? 'W' + (-x) : 'E' + x; }
  function bY(y) { return y <= 0 ? 'N' + (-y) : 'S' + y; }
  function bG(num) {
    var digits = num >= 1000000 ? 2 : (num >= 1000 ? 1 : 0);
    var rx = /\.0+$|(\.[0-9]*[1-9])0+$/;
    var scientificNumbers = [
      { value: 1e9, symbol: 'B' },
      { value: 1e6, symbol: 'M' },
      { value: 1e3, symbol: 'K' }
    ];
    var item = scientificNumbers.slice().reverse().find(function (item) { return num >= item.value; });
    return item ? (num / item.value).toFixed(digits).replace(rx, '$1') + item.symbol : '0';
  }
  if (typeof window.bX !== 'function') window.bX = bX;
  if (typeof window.bY !== 'function') window.bY = bY;
  if (typeof window.bG !== 'function') window.bG = bG;

  // Viewport culling helper
  function isOnScreenRect(x, y, width, height, margin) {
    var m = typeof margin === 'number' ? margin : 0;
    var w = window.w || 0;
    var h = window.h || 0;
    return !(x + width < -m || x > w + m || y + height < -m || y > h + m);
  }
  if (typeof window.isOnScreenRect !== 'function') window.isOnScreenRect = isOnScreenRect;

  // Portrait helpers
  function getPortrait(id) {
    var url = (typeof window.getImageUrl === 'function') ? window.getImageUrl('p_male_birdman.jpg') : 'p_male_birdman.jpg';
    var n = parseInt(id);
    switch (n) {
      case -1: url = window.getImageUrl ? window.getImageUrl('p_female_warrior.jpg') : 'p_female_warrior.jpg'; break;
      case -2: url = window.getImageUrl ? window.getImageUrl('p_female_bowman.jpg') : 'p_female_bowman.jpg'; break;
      case -3: url = window.getImageUrl ? window.getImageUrl('p_male_barbarian.jpg') : 'p_male_barbarian.jpg'; break;
      case -4: url = window.getImageUrl ? window.getImageUrl('p_male_priest.jpg') : 'p_male_priest.jpg'; break;
      case -5: url = window.getImageUrl ? window.getImageUrl('p_female_paladin.jpg') : 'p_female_paladin.jpg'; break;
      case -6: url = window.getImageUrl ? window.getImageUrl('p_male_thief.jpg') : 'p_male_thief.jpg'; break;
      case -7: url = window.getImageUrl ? window.getImageUrl('p_female_mage.jpg') : 'p_female_mage.jpg'; break;
      case -8: url = window.getImageUrl ? window.getImageUrl('p_male_monk.jpg') : 'p_male_monk.jpg'; break;
    }
    return url;
  }
  if (typeof window.getPortrait !== 'function') window.getPortrait = getPortrait;

  function previewPortrait() {
    try {
      var pid = $('#player_portrait').val();
      $('#player_portrait_preview').attr('src', getPortrait('-' + pid));
    } catch(_){}
  }
  if (typeof window.previewPortrait !== 'function') window.previewPortrait = previewPortrait;
})();
