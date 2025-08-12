// Combat handling module: unified fight response processing
(function(){
  'use strict';

  // Start a combat round against the current monster
  function attack() {
    try {
      var playerName = $("#player").text();
      var roomId = $("#room_id").text();
      if (window.api && typeof window.api.fightMonster === 'function') {
        return window.api.fightMonster(playerName, roomId)
          .then(function (response) {
            handleFightResponse(response);
          })
          .catch(function (err) {
            console.error("error: " + err);
          });
      } else {
        console.error('API not available');
      }
    } catch (e) {
      console.error(e);
    }
  }

  function handleFightResponse(resp, opts) {
    try {
      opts = opts || {};
      var fromMove = !!opts.fromMove;

      var isStructured = resp && typeof resp === 'object' && !Array.isArray(resp) && (resp.type === 'fight' || resp.events || resp.log);
      var logs = [];
      var events = [];
      var outcome = null;

      if (isStructured) {
        logs = Array.isArray(resp.log) ? resp.log : [];
        events = Array.isArray(resp.events) ? resp.events : [];
        outcome = resp.outcome || null;
      } else if (Array.isArray(resp)) {
        var startIdx = (resp.length > 0 && resp[0] === 'fight') ? 1 : 0;
        logs = resp.slice(startIdx);
      } else {
        return;
      }

      var wasSlain = false;
      var died = false;

      if (outcome === 'win') wasSlain = true;
      if (outcome === 'lose') died = true;

      if (Array.isArray(logs)) {
        logs.forEach(function (line) {
          $("#battle_log").prepend('<span>' + line + '</span><br/>' );
        });
      }

      if (Array.isArray(events) && events.length) {
        events.forEach(function (ev) {
          try {
            var t = ev && ev.type;
            if (t === 'hit' || t === 'crit') {
              var isCrit = (t === 'crit') || !!ev.crit;
              var amount = parseInt(ev.amount || 0, 10);
              if (amount > 0) {
                if (ev.source === 'player') {
                  if (typeof showDamage === 'function' && typeof player !== 'undefined') showDamage(player, amount, isCrit);
                } else if (ev.target === 'player') {
                  if (typeof showDamage === 'function' && typeof player !== 'undefined') showDamage(player, amount, isCrit);
                }
              }
            } else if (t === 'heal') {
              var healAmt = parseInt(ev.amount || 0, 10);
              if (healAmt > 0 && ev.target === 'player') {
                if (typeof showHeal === 'function' && typeof player !== 'undefined') showHeal(player, healAmt);
              }
            } else if (t === 'death') {
              if (ev.target === 'monster') wasSlain = true;
              if (ev.target === 'player') died = true;
            }
          } catch (_) { }
        });
      }

      if (died) {
        document.getElementById('lose-dialog').showModal();
      } else if (wasSlain) {
        if (typeof playSound === 'function') playSound(getImageUrl('win.mp3'));
        // Populate victory details (rewards, drops)
        try {
          var box = document.getElementById('winBattleBox');
          if (box) {
            var rewards = (resp && resp.rewards) ? resp.rewards : null;
            var drops = Array.isArray(resp && resp.drops) ? resp.drops : [];
            var parts = [];
            if (rewards) {
              var exp = parseInt(rewards.exp || 0, 10);
              var gold = parseInt(rewards.gold || 0, 10);
              if (exp > 0 || gold > 0) {
                var rg = [];
                if (exp > 0) rg.push(exp + ' EXP');
                if (gold > 0) rg.push(gold + ' Gold');
                parts.push('<div>Rewards: ' + rg.join(' • ') + '</div>');
              }
              if (rewards.leveledUp) {
                var newLvl = (typeof rewards.newLevel !== 'undefined' && rewards.newLevel !== null) ? rewards.newLevel : '';
                parts.push('<div class="nes-text is-success">Level Up! ' + (newLvl !== '' ? ('New level: ' + newLvl) : '') + '</div>');
              }
            }
            if (drops && drops.length) {
              var dropLines = drops.map(function(d){ return '- ' + (d && d.name ? String(d.name) : 'Unknown item'); });
              parts.push('<div>Items Dropped:</div><pre class="drops-list">' + dropLines.join('\n') + '</pre>');
            }
            if (!parts.length) {
              parts.push('<div>You are victorious!</div>');
            }
            box.innerHTML = parts.join('');
          }
        } catch (_) { }
        document.getElementById('win-dialog').showModal();
      } else {
        if (typeof playSound === 'function') playSound(getImageUrl('sword.mp3'));
      }

      if (typeof getPlayer === 'function') getPlayer(false);
      if (fromMove) { window.canMove = true; }
      if (window.Movement && typeof window.Movement.move === 'function') {
        window.Movement.move('na');
      } else if (typeof move === 'function') {
        move('na');
      }
    } catch (e) {
      try { console.error('handleFightResponse error:', e); } catch (_) {}
    }
  }

  if (typeof window.handleFightResponse !== 'function') {
    window.handleFightResponse = handleFightResponse;
  }

  if (!window.Combat) window.Combat = {};
  if (typeof window.Combat.attack !== 'function') {
    window.Combat.attack = attack;
  }
})();
