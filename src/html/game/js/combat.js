// Combat handling module: unified fight response processing
(function(){
  'use strict';

  var POWER_STRIKE_COST = 5; // keep in sync with server for now
  var FIREBALL_COST = 7;     // keep in sync with server for now

  function setPowerStrikeButtons(state) {
    try {
      var main = $('#powerStrikeBtn');
      var mini = $('#powerStrikeMiniBtn');
      var text = 'Power Strike';
      var disabled = false;
      var title = 'Power Strike';
      if (state && typeof state.text === 'string') text = state.text;
      if (state && typeof state.disabled === 'boolean') disabled = !!state.disabled;
      if (state && typeof state.title === 'string') title = state.title;
      // Main button: show full text and disabled state
      if (main && main.length) {
        main.text(text);
        if (disabled) main.addClass('is-disabled').attr('disabled', 'disabled');
        else main.removeClass('is-disabled').removeAttr('disabled');
        if (title) main.attr('title', title); else main.removeAttr('title');
      }
      // Mini button: keep label as "PS", use title for status, ALWAYS enabled (opens info)
      if (mini && mini.length) {
        mini.text('PS');
        // ensure enabled
        mini.removeClass('is-disabled').removeAttr('disabled');
        if (title) mini.attr('title', title); else mini.attr('title', 'Power Strike');
      }
    } catch (_) {}
  }

  function setFireballButtons(state) {
    try {
      var mini = $('#fireballMiniBtn');
      var title = 'Fireball (MP 7, CD 6s)';
      if (state && typeof state.title === 'string') title = state.title;
      if (mini && mini.length) {
        mini.text('FB');
        mini.removeClass('is-disabled').removeAttr('disabled');
        if (title) mini.attr('title', title); else mini.attr('title', 'Fireball');
      }
    } catch (_) {}
  }

  function startPowerStrikeCountdown(seconds) {
    try {
      if (window._psTimer) { clearInterval(window._psTimer); window._psTimer = null; }
      var remain = parseInt(seconds || 0, 10);
      if (remain > 0) {
        window._psRemain = remain;
        setPowerStrikeButtons({ text: 'Power Strike (' + remain + 's)', title: 'Power Strike (' + remain + 's)', disabled: true });
        window._psTimer = setInterval(function(){
          remain -= 1;
          window._psRemain = Math.max(0, remain);
          if (remain <= 0) {
            clearInterval(window._psTimer); window._psTimer = null;
            // Re-enable; MP check will run on next response. Fallback to enable now.
            setPowerStrikeButtons({ text: 'Power Strike', title: 'Power Strike', disabled: false });
            window._psRemain = 0;
          } else {
            setPowerStrikeButtons({ text: 'Power Strike (' + remain + 's)', title: 'Power Strike (' + remain + 's)', disabled: true });
          }
        }, 1000);
      }
    } catch (_) {}
  }

  function startFireballCountdown(seconds) {
    try {
      if (window._fbTimer) { clearInterval(window._fbTimer); window._fbTimer = null; }
      var remain = parseInt(seconds || 0, 10);
      if (remain > 0) {
        window._fbRemain = remain;
        setFireballButtons({ title: 'Fireball (' + remain + 's)' });
        window._fbTimer = setInterval(function(){
          remain -= 1;
          window._fbRemain = Math.max(0, remain);
          if (remain <= 0) {
            clearInterval(window._fbTimer); window._fbTimer = null;
            setFireballButtons({ title: 'Fireball (MP 7, CD 6s)' });
            window._fbRemain = 0;
          } else {
            setFireballButtons({ title: 'Fireball (' + remain + 's)' });
          }
        }, 1000);
      }
    } catch (_) {}
  }

  function updatePowerStrikeFromResponse(resp) {
    try {
      var cds = (resp && resp.cooldowns) ? resp.cooldowns : {};
      var psRemain = parseInt(cds && cds.power_strike ? cds.power_strike : 0, 10) || 0;
      window._psRemain = psRemain;
      var mp = 0;
      if (resp && resp.player && typeof resp.player.mp !== 'undefined') {
        mp = parseInt(resp.player.mp || 0, 10) || 0;
      } else {
        // fallback from DOM
        mp = parseInt($('#player_mp').text() || '0', 10) || 0;
      }
      if (psRemain > 0) {
        startPowerStrikeCountdown(psRemain);
      } else if (mp < POWER_STRIKE_COST) {
        setPowerStrikeButtons({ text: 'Power Strike (MP ' + POWER_STRIKE_COST + ')', title: 'Need ' + POWER_STRIKE_COST + ' MP', disabled: true });
      } else {
        setPowerStrikeButtons({ text: 'Power Strike', title: 'Power Strike (MP 5, CD 5s)', disabled: false });
        if (window._psTimer) { clearInterval(window._psTimer); window._psTimer = null; }
      }
    } catch (_) {}
  }

  function updateFireballFromResponse(resp) {
    try {
      var cds = (resp && resp.cooldowns) ? resp.cooldowns : {};
      var fbRemain = parseInt(cds && cds.fireball ? cds.fireball : 0, 10) || 0;
      window._fbRemain = fbRemain;
      var mp = 0;
      if (resp && resp.player && typeof resp.player.mp !== 'undefined') {
        mp = parseInt(resp.player.mp || 0, 10) || 0;
      } else {
        mp = parseInt($('#player_mp').text() || '0', 10) || 0;
      }
      if (fbRemain > 0) {
        startFireballCountdown(fbRemain);
      } else if (mp < FIREBALL_COST) {
        setFireballButtons({ title: 'Need ' + FIREBALL_COST + ' MP' });
      } else {
        setFireballButtons({ title: 'Fireball (MP 7, CD 6s)' });
        if (window._fbTimer) { clearInterval(window._fbTimer); window._fbTimer = null; }
      }
    } catch (_) {}
  }

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

  // Use a combat skill (basic universal skills before class selection)
  function useSkill(skillName) {
    try {
      var playerName = $("#player").text();
      var roomId = $("#room_id").text();
      var skill = (typeof skillName === 'string' && skillName) ? skillName : 'power_strike';
      if (window.api && typeof window.api.fightMonster === 'function') {
        return window.api.fightMonster(playerName, roomId, skill)
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

      // Surface structured errors if present
      try {
        if (resp && Array.isArray(resp.errors) && resp.errors.length) {
          resp.errors.forEach(function(e){
            var msg = '';
            if (e && e.type === 'skill_on_cooldown') {
              msg = 'Skill on cooldown: ' + (e.skill || '') + ' (' + (e.seconds || 0) + 's)';
            } else if (e && e.type === 'invalid_skill') {
              msg = 'Invalid skill: ' + (e.skill || '');
            }
            if (msg) { $("#battle_log").prepend('<span class="nes-text is-disabled">' + msg + '</span><br/>'); }
          });
        }
      } catch(_) {}

      if (Array.isArray(events) && events.length) {
        events.forEach(function (ev) {
          try {
            var t = ev && (ev.type || ev.t);
            if (t === 'hit' || t === 'crit') {
              var isCrit = (t === 'crit') || !!ev.crit;
              var amount = parseInt(ev.amount || ev.dmg || 0, 10);
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
            } else if (t === 'skill_used') {
              var sname = (ev.name || ev.skill || 'skill');
              var mps = (typeof ev.mp_spent !== 'undefined') ? ev.mp_spent : null;
              var line = 'You used ' + String(sname).replace('_',' ') + (mps !== null ? (' (-' + mps + ' MP)') : '') + '!';
              $("#battle_log").prepend('<span class="nes-text is-primary">' + line + '</span><br/>');
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
        // Immediately hide monster panel (server has deleted it) and refresh monsters
        try {
          window.currentMonster = null;
          if (typeof hideEl === 'function') hideEl('#monster_box'); else $('#monster_box').hide();
          $('#monster_box').addClass('hidden').css({ display: 'none', visibility: 'hidden', opacity: 0 });
        } catch(_) {}
      } else {
        if (typeof playSound === 'function') playSound(getImageUrl('sword.mp3'));
      }

      // Update skill button state from response (cooldowns + MP)
      updatePowerStrikeFromResponse(resp);
      updateFireballFromResponse(resp);

      // Refresh player and monsters, then unlock movement
      if (typeof getPlayer === 'function') getPlayer(false);
      var refreshMonsters = function(){ return Promise.resolve(); };
      try {
        if (window.Monsters && typeof window.Monsters.getMonsters === 'function') {
          refreshMonsters = function(){ return window.Monsters.getMonsters(window.player_x, window.player_y); };
        }
      } catch(_) {}
      refreshMonsters().then(function(){
        // Refresh all monster positions for border rendering
        try {
          if (window.Monsters && typeof window.Monsters.getAllMonsters === 'function') {
            window.Monsters.getAllMonsters();
          }
        } catch(_) {}
        window.canMove = true;
        try { if (window.Locations && typeof window.Locations.updateCurrentTile === 'function') { window.Locations.updateCurrentTile(window.player_x, window.player_y); } } catch(_) {}
        if (window.Movement && typeof window.Movement.flush === 'function') { window.Movement.flush(); }
      });
    } catch (e) {
      try { console.error('handleFightResponse error:', e); } catch (_) {}
    }
  }

  if (typeof window.handleFightResponse !== 'function') {
    window.handleFightResponse = handleFightResponse;
  }

  function useSkillFromPanel() {
    try {
      if (window.UsableSkills && typeof UsableSkills.getCurrentSkillId === 'function') {
        var skillId = UsableSkills.getCurrentSkillId();
        if (skillId) {
          useSkill(skillId);
        }
      }
    } catch (e) {
      console.error('useSkillFromPanel error:', e);
    }
  }

  if (!window.Combat) window.Combat = {};
  if (typeof window.Combat.attack !== 'function') {
    window.Combat.attack = attack;
  }
  if (typeof window.Combat.useSkill !== 'function') {
    window.Combat.useSkill = useSkill;
  }
  if (typeof window.Combat.useSkillFromPanel !== 'function') {
    window.Combat.useSkillFromPanel = useSkillFromPanel;
  }
})();
