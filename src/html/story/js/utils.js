// Utilities module (safe to load before game.js)
(function(){
  'use strict';
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

  // Stat extractor for semicolon stats strings, e.g. "hp=10;maxhp=20"
  function getStat(stats, key, defVal) {
    if (typeof defVal === 'undefined') defVal = null;
    if (!stats || typeof stats !== 'string') return defVal;
    var parts = stats.split(';');
    for (var i = 0; i < parts.length; i++) {
      var p = parts[i];
      if (p.indexOf(key + '=') === 0) {
        var v = p.split('=')[1];
        var n = parseInt(v, 10);
        return isNaN(n) ? defVal : n;
      }
    }
    return defVal;
  }
  if (typeof window.getStat !== 'function') window.getStat = getStat;

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
    var url = (typeof window.getImageUrl === 'function') ? window.getImageUrl('p_birdman.png') : 'p_birdman.png';
    var n = parseInt(id);
    switch (n) {
      case -1: url = window.getImageUrl ? window.getImageUrl('p_female_warrior.png') : 'p_female_warrior.png'; break;
      case -2: url = window.getImageUrl ? window.getImageUrl('p_female_bowman.png') : 'p_female_bowman.png'; break;
      case -3: url = window.getImageUrl ? window.getImageUrl('p_male_barbarian.png') : 'p_male_barbarian.png'; break;
      case -4: url = window.getImageUrl ? window.getImageUrl('p_male_priest.png') : 'p_male_priest.png'; break;
      case -5: url = window.getImageUrl ? window.getImageUrl('p_female_paladin.png') : 'p_female_paladin.png'; break;
      case -6: url = window.getImageUrl ? window.getImageUrl('p_male_thief.png') : 'p_male_thief.png'; break;
      case -7: url = window.getImageUrl ? window.getImageUrl('p_female_mage.png') : 'p_female_mage.png'; break;
      case -8: url = window.getImageUrl ? window.getImageUrl('p_male_monk.png') : 'p_male_monk.png'; break;
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
