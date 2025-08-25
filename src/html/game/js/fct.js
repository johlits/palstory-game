// Floating Combat Text (FCT) module
(function(){
  'use strict';
  var combatTexts = [];

  function addCombatText(x, y, text, kind) {
    var color = '#e74c3c';
    var size = 22;
    var vy = 40;
    if (kind === 'heal') { color = '#27ae60'; size = 22; }
    if (kind === 'crit') { color = '#f1c40f'; size = 28; vy = 50; }
    var entry = {
      x: x,
      y: y,
      text: String(text),
      kind: kind || 'dmg',
      color: color,
      size: size,
      vy: vy,
      life: 900,
      t0: (typeof performance !== 'undefined' && performance.now) ? performance.now() : Date.now(),
    };
    combatTexts.push(entry);
  }

  function showDamage(comp, amount, isCrit) {
    try {
      var cx = comp.x + comp.width / 2;
      var cy = comp.y - Math.max(16, comp.height * 0.25);
      addCombatText(cx, cy, (isCrit ? '✦ ' : '') + '-' + amount, isCrit ? 'crit' : 'dmg');
    } catch (_) { }
  }

  function showHeal(comp, amount) {
    try {
      var cx = comp.x + comp.width / 2;
      var cy = comp.y - Math.max(16, comp.height * 0.25);
      addCombatText(cx, cy, '+' + amount, 'heal');
    } catch (_) { }
  }

  // Expose to global so game.js keeps working during migration
  if (typeof window.addCombatText !== 'function') window.addCombatText = addCombatText;
  if (typeof window.showDamage !== 'function') window.showDamage = showDamage;
  if (typeof window.showHeal !== 'function') window.showHeal = showHeal;
  // Also expose the internal list if renderer wants to consume it
  if (!window.__combatTexts) window.__combatTexts = combatTexts;
})();
