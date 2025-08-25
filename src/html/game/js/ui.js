// UI helpers module (safe to load before game.js)
(function(){
  'use strict';
  // Ensure UI toggle globals exist
  if (typeof window.locationToggle === 'undefined') window.locationToggle = 0;
  if (typeof window.itemToggle === 'undefined') window.itemToggle = 0;
  if (typeof window.itemInfoBox === 'undefined') window.itemInfoBox = 0;
  if (typeof window.monsterToggle === 'undefined') window.monsterToggle = 0;

  // New independent toggle states
  if (typeof window.locationInfoOpen === 'undefined') window.locationInfoOpen = false;
  if (typeof window.locationStatsOpen === 'undefined') window.locationStatsOpen = false;
  if (typeof window.monsterInfoOpen === 'undefined') window.monsterInfoOpen = false;
  if (typeof window.monsterStatsOpen === 'undefined') window.monsterStatsOpen = false;
  if (typeof window.monsterBattleOpen === 'undefined') window.monsterBattleOpen = false;

  function showEl(sel) { try { $(sel).removeClass('hidden'); } catch (e) {} }
  function hideEl(sel) { try { $(sel).addClass('hidden'); } catch (e) {} }

  // Small helper to check visibility based on our hidden class
  function isElVisible(sel){
    try {
      var el = document.querySelector(sel);
      if (!el) return false;
      return !el.classList.contains('hidden');
    } catch(_) { return false; }
  }

  // Keep container/name visibility in sync for Location box
  function updateLocationContainers(){
    try {
      var anyOpen = (window.locationInfoOpen || window.locationStatsOpen);
      if (anyOpen) {
        showEl('#location_data_box');
        hideEl('#location_name_box');
      } else {
        hideEl('#location_data_box');
        showEl('#location_name_box');
      }
      // Legacy integer mirror (best-effort)
      if (!anyOpen) window.locationToggle = 0;
      else if (window.locationInfoOpen) window.locationToggle = 1;
      else if (window.locationStatsOpen) window.locationToggle = 2;
    } catch(_) {}
  }

  // Keep container/name visibility in sync for Monster box
  function updateMonsterContainers(){
    try {
      var anyOpen = (window.monsterInfoOpen || window.monsterStatsOpen || window.monsterBattleOpen);
      if (anyOpen) {
        showEl('#monster_data_box');
        hideEl('#monster_name_box');
      } else {
        hideEl('#monster_data_box');
        showEl('#monster_name_box');
      }
      // Legacy integer mirror (best-effort)
      if (!anyOpen) window.monsterToggle = 0;
      else if (window.monsterBattleOpen) window.monsterToggle = 3;
      else if (window.monsterStatsOpen) window.monsterToggle = 2;
      else if (window.monsterInfoOpen) window.monsterToggle = 1;
    } catch(_) {}
  }

  function showCreatePlayerBox() {
    if (!window.gameStarted) {
      try { if (typeof window.previewPortrait === 'function') window.previewPortrait(); } catch (e) {}
      showEl('#create_player_box');
    }
  }

  function showCreateRoomBox() {
    if (!window.gameStarted) {
      showEl('#create_game_box');
    }
  }

  function gameOver() {
    var url = "/story/game/game.php?room=" + $('#room').text() + "&player=" + $('#player').text();
    window.location.href = url;
  }

  function setGameLink() {
    var url = "/story/game/game.php?room=" + $('#room').text() + "&player=" + $('#player').text();
    $('#game_link').attr('href', url);
  }

  if (!window.UI) window.UI = {};
  window.UI.showEl = showEl;
  window.UI.hideEl = hideEl;
  // Dynamic z-index management for HUD panels
  var BASE_PANEL_Z = 10;
  if (typeof window.__uiTopZIndex === 'undefined') window.__uiTopZIndex = BASE_PANEL_Z + 1;
  window.UI.raisePanel = function(selOrEl){
    try {
      var el = (typeof selOrEl === 'string') ? document.querySelector(selOrEl) : selOrEl;
      if (!el) return;
      window.__uiTopZIndex = Math.max(window.__uiTopZIndex || BASE_PANEL_Z, BASE_PANEL_Z) + 1;
      el.style.zIndex = String(window.__uiTopZIndex);
    } catch (_) {}
  };
  function setupPanelHoverZ(){
    try {
      var sels = ['#items_box', '#monster_box', '#skills_box', '#location_box', '#skill_info_box'];
      sels.forEach(function(sel){
        var el = document.querySelector(sel);
        if (!el || el.__hoverBound) return;
        el.__hoverBound = true;
        el.addEventListener('mouseenter', function(){ try { window.UI.raisePanel(el); } catch(_) {} });
      });
    } catch(_) {}
  }
  window.UI.setupPanelHoverZ = setupPanelHoverZ;
  // Ensure move buttons start hidden
  window.UI.resetMoveButtons = function(){ try { hideEl('#moveSuccessBtn'); hideEl('#moveDisabledBtn'); } catch(_) {} };
  // Help overlay controller (always-on-top)
  window.UI.setHelpOverlayVisible = function(visible, line1, line2){
    try {
      var el = document.getElementById('help-overlay'); if (!el) return;
      if (typeof line1 === 'string') { el.querySelector('.h1').textContent = line1; }
      if (typeof line2 === 'string') { el.querySelector('.h2').textContent = line2; }
      el.style.display = visible ? 'block' : 'none';
    } catch(_) {}
  };
  // Legacy global aliases (for older modules like audio.js)
  if (typeof window.showEl !== 'function') window.showEl = showEl;
  if (typeof window.hideEl !== 'function') window.hideEl = hideEl;
  window.UI.showCreatePlayerBox = showCreatePlayerBox;
  window.UI.showCreateRoomBox = showCreateRoomBox;
  window.UI.gameOver = gameOver;
  window.UI.setGameLink = setGameLink;
  // Toggle helpers (delegate UI-only logic from game.js)
  window.UI.toggleStats = function () {
    try { playSound(getImageUrl("click.mp3")); } catch (_) {}
    if (!window.showStats) {
      window.showStats = true;
      showEl("#player_bstats");
      $("#showStatsBtn").prop("value", "Hide Stats");
    } else {
      window.showStats = false;
      hideEl("#player_bstats");
      $("#showStatsBtn").prop("value", "Show Stats");
    }
  };

  // High-level UI flows previously in game.js
  window.UI.showCreateRoomBox = function () {
    if (!window.gameStarted) {
      try { window.UI.showEl('#create_game_box'); } catch (_) {
        try { $('#create_game_box').removeClass('hidden'); } catch (_) {}
      }
      // Prefill expiration with +7 days if empty
      try {
        var $inp = $('#create_game_expiration');
        if ($inp && !$inp.val()) {
          var d = new Date();
          d.setDate(d.getDate() + 7);
          var v = d.toISOString().slice(0, 10);
          $inp.val(v);
        }
      } catch (_) {}
    }
  };

  window.UI.gameOver = function () {
    var url = "/story/game/game.php?room=" + $('#room').text() + "&player=" + $('#player').text();
    window.location.href = url;
  };

  window.UI.setGameLink = function () {
    var url = "/story/game/game.php?room=" + $('#room').text() + "&player=" + $('#player').text();
    $('#game_link').attr('href', url);
  };

  // Movement indicator setup
  (function setupMoveIndicator(){
    try {
      if (document.getElementById('moving-indicator')) return;
      var el = document.createElement('div');
      el.id = 'moving-indicator';
      el.textContent = 'Moving…';
      el.style.position = 'fixed';
      el.style.bottom = '8px';
      el.style.right = '8px';
      el.style.padding = '4px 8px';
      el.style.background = 'rgba(0,0,0,0.55)';
      el.style.color = '#fff';
      el.style.fontSize = '12px';
      el.style.borderRadius = '4px';
      el.style.boxShadow = '0 1px 3px rgba(0,0,0,0.3)';
      el.style.display = 'none';
      el.style.zIndex = '9999';
      document.body.appendChild(el);

      function show() { el.style.display = 'block'; document.body.classList.add('ps-moving'); window.uiMoving = true; }
      function hide() { el.style.display = 'none'; document.body.classList.remove('ps-moving'); window.uiMoving = false; }

      window.addEventListener('palstory:move:start', function(){ show(); });
      window.addEventListener('palstory:move:complete', function(){ hide(); });
    } catch (_) {}
  })();

  // Always-on-top Help overlay (overlaps everything)
  (function setupHelpOverlay(){
    try {
      if (document.getElementById('help-overlay')) return;
      var el = document.createElement('div');
      el.id = 'help-overlay';
      el.style.position = 'fixed';
      el.style.left = '50%';
      el.style.transform = 'translateX(-50%)';
      el.style.bottom = '10%';
      el.style.padding = '12px 16px';
      el.style.background = 'rgba(0,0,0,0.70)';
      el.style.color = '#fff';
      el.style.fontFamily = "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, 'Apple Color Emoji', 'Segoe UI Emoji'";
      el.style.fontSize = '16px';
      el.style.borderRadius = '8px';
      el.style.boxShadow = '0 6px 20px rgba(0,0,0,0.45)';
      el.style.zIndex = '100000';
      el.style.pointerEvents = 'none';
      el.style.textAlign = 'center';
      el.style.display = 'none';
      var l1 = document.createElement('div'); l1.className = 'h1'; l1.textContent = window.moveInstructions || 'Use arrow keys (or WASD) to move';
      var l2 = document.createElement('div'); l2.className = 'h2'; l2.style.opacity = '0.9'; l2.style.marginTop = '4px'; l2.textContent = window.helpInstructions || 'Or click a tile and press Move • Press H for help';
      el.appendChild(l1); el.appendChild(l2);
      document.body.appendChild(el);
    } catch(_) {}
  })();

  // On initial load, hide move buttons by default
  try { window.UI.resetMoveButtons(); } catch(_) {}

  // Initialize panel visibility (closed by default) to avoid auto-showing siblings
  (function initIndependentToggles(){
    try {
      // Close all sub-panels initially
      hideEl('#location_info_box');
      hideEl('#location_stats_box');
      hideEl('#monster_info_box');
      hideEl('#monster_stats_box');
      hideEl('#monster_battle_box');
      // Reset flags
      window.locationInfoOpen = false;
      window.locationStatsOpen = false;
      window.monsterInfoOpen = false;
      window.monsterStatsOpen = false;
      window.monsterBattleOpen = false;
      // Sync containers with flags
      updateLocationContainers();
      updateMonsterContainers();
    } catch(_) {}
  })();

  // Canvas interaction handlers (extracted from game.js init)
  window.UI.onCanvasMouseMove = function (gc, evt) {
    try {
      var mousePos = (typeof window.getMousePos === 'function') ? window.getMousePos(gc, evt) : { x: 0, y: 0 };
      var tX = Math.round((mousePos.x - window.mapCoordFromX) / window.ss - 0.5) + window.mapCoordToX;
      var tY = Math.round((mousePos.y - window.mapCoordFromY) / window.ss - 0.5) + window.mapCoordToY;
      if (window.mX !== tX + window.player_x || window.mY !== tY + window.player_y) {
        window.mX = tX + window.player_x;
        window.mY = tY + window.player_y;
        try { $("#mouse_x").text(window.bX(window.mX)); } catch (_) {}
        try { $("#mouse_y").text(window.bY(window.mY)); } catch (_) {}
      }
    } catch (_) {}
  };

  window.UI.onCanvasClick = function (evt) {
    try {
      var loc = window.locationsDict["" + window.mX + "," + window.mY];
      if (loc) {
        var location = window.locationsDict["" + window.mX + "," + window.mY];
        window.UI.showEl("#location_box");
        $(".location_name").text(location.name);
        $("#location_image").attr("src", location.image.currentSrc);
        $("#location_description").text(location.description);

        var location_stats = location.stats;
        var location_fields = String(location_stats || '').split(";");
        $("#location_spawns").text("None");
        for (var index = 0; index < location_fields.length; index++) {
          var field = location_fields[index];
          if (field.indexOf("spawns") === 0) {
            $("#location_spawns").text(field.split("=")[1].split(",").join(", "));
          }
        }
      }

      if (Math.abs(window.player_x - window.mX) + Math.abs(window.player_y - window.mY) === 1) {
        window.UI.showEl("#location_box");
        if (!loc) {
          $(".location_name").text('???');
          window.UI.hideEl("#locationStatsPrimaryBtn");
          window.UI.showEl("#locationStatsDisabledBtn");
          window.UI.hideEl("#locationInfoBtn");
          window.UI.showEl("#locationInfoDisabledBtn");
        } else {
          window.UI.hideEl("#locationStatsDisabledBtn");
          window.UI.showEl("#locationStatsPrimaryBtn");
          window.UI.hideEl("#locationInfoDisabledBtn");
          window.UI.showEl("#locationInfoBtn");
        }
        window.UI.hideEl("#moveDisabledBtn");
        window.UI.showEl("#moveSuccessBtn");
        if (window.mX < window.player_x) window.moveDirection = "left";
        if (window.mX > window.player_x) window.moveDirection = "right";
        if (window.mY < window.player_y) window.moveDirection = "up";
        if (window.mY > window.player_y) window.moveDirection = "down";
      } else {
        window.UI.hideEl("#moveSuccessBtn");
        window.UI.hideEl("#moveDisabledBtn");
        if (!loc) {
          window.UI.hideEl("#location_box");
        }
      }
    } catch (_) {}
  };

  window.UI.toggleLocationInfo = function () {
    try { playSound(getImageUrl("click.mp3")); } catch (_) {}
    try { if (window.UI && typeof UI.raisePanel === 'function') UI.raisePanel('#location_box'); } catch(_) {}
    // Toggle independent state
    window.locationInfoOpen = !window.locationInfoOpen;
    if (window.locationInfoOpen) {
      showEl('#location_box');
      showEl('#location_info_box');
      try { speak($("#location_description").text()); } catch (_) {}
    } else {
      hideEl('#location_info_box');
    }
    updateLocationContainers();
  };

  window.UI.toggleLocationStats = function () {
    try { playSound(getImageUrl("click.mp3")); } catch (_) {}
    try { if (window.UI && typeof UI.raisePanel === 'function') UI.raisePanel('#location_box'); } catch(_) {}
    // Toggle independent state
    window.locationStatsOpen = !window.locationStatsOpen;
    if (window.locationStatsOpen) {
      showEl('#location_box');
      showEl('#location_stats_box');
    } else {
      hideEl('#location_stats_box');
    }
    updateLocationContainers();
  };

  window.UI.toggleItemsTable = function () {
    try { playSound(getImageUrl("click.mp3")); } catch (_) {}
    if (window.itemToggle == 0) {
      window.itemToggle = 1;
      try { if (window.UI && typeof UI.raisePanel === 'function') UI.raisePanel('#items_box'); } catch(_) {}
      showEl("#items_table");
      if (window.itemInfoBox == 1) {
        hideEl("#items_table");
        showEl("#item_info_box");
        showEl("#items_description_btn");
      } else {
        showEl("#items_table");
        hideEl("#item_info_box");
        hideEl("#items_description_btn");
      }
    } else {
      window.itemToggle = 0;
      hideEl("#items_table");
      hideEl("#item_info_box");
      hideEl("#items_description_btn");
    }
  };

  // Toggle skills panel within items_box
  window.UI.toggleSkills = function () {
    try { playSound(getImageUrl("click.mp3")); } catch (_) {}
    var $box = $("#skills_box");
    if (!$box.length) return;
    if ($box.hasClass('hidden')) { showEl('#skills_box'); try { if (window.UI && typeof UI.raisePanel === 'function') UI.raisePanel('#skills_box'); } catch(_) {} }
    else { hideEl('#skills_box'); }
  };

  // Show skill info panel and set Use button state
  window.UI.showSkillInfo = function (skillId) {
    try { playSound(getImageUrl("click.mp3")); } catch (_) {}
    if (!skillId) return;
    var title = '', desc = '', cost = 0, cd = 0, useBtnId = '', statusId = '';
    switch (skillId) {
      case 'power_strike':
        title = 'Power Strike';
        desc = 'A heavy attack that deals 150% damage.';
        cost = 5; cd = 5;
        useBtnId = '#skill_use_btn_power_strike';
        statusId = '#skill_status_power_strike';
        break;
      case 'fireball':
        title = 'Fireball';
        desc = 'A searing blast that deals 160% damage.';
        cost = 7; cd = 6;
        useBtnId = '#skill_use_btn_fireball';
        statusId = '#skill_status_fireball';
        break;
      default:
        return;
    }
    // Toggle visibility of Use buttons and status rows for known skills
    try {
      $('#skill_use_btn_power_strike, #skill_status_power_strike').addClass('hidden');
      $('#skill_use_btn_fireball, #skill_status_fireball').addClass('hidden');
      $(useBtnId + ', ' + statusId).removeClass('hidden');
    } catch(_) {}
    $('#skill_title').text(title);
    $('#skill_desc').text(desc + ' Costs ' + cost + ' MP. Cooldown ' + cd + 's.');
    $('#skill_meta').text('Cost: ' + cost + ' MP • Cooldown: ' + cd + 's');

    // Compute current state
    var mp = parseInt($('#player_mp').text() || '0', 10) || 0;
    var remain = 0;
    if (skillId === 'power_strike') {
      remain = window._psRemain ? (parseInt(window._psRemain, 10) || 0) : 0;
    } else if (skillId === 'fireball') {
      remain = window._fbRemain ? (parseInt(window._fbRemain, 10) || 0) : 0;
    }
    var $useBtn = $(useBtnId);
    var $status = $(statusId);
    if ($useBtn && $useBtn.length) {
      if (remain > 0) {
        $useBtn.addClass('is-disabled').attr('disabled', 'disabled');
        $status.text('Cooldown ' + remain + 's');
      } else if (mp < cost) {
        $useBtn.addClass('is-disabled').attr('disabled', 'disabled');
        $status.text('Need ' + cost + ' MP');
      } else {
        $useBtn.removeClass('is-disabled').removeAttr('disabled');
        $status.text('');
      }
    }
    showEl('#skill_info_box');
    try { if (window.UI && typeof UI.raisePanel === 'function') UI.raisePanel('#skill_info_box'); } catch(_) {}
    try { startSkillInfoAutorefresh(skillId); } catch(_) {}
  };

  window.UI.hideSkillInfo = function () {
    try { playSound(getImageUrl("click.mp3")); } catch (_) {}
    hideEl('#skill_info_box');
    try { stopSkillInfoAutorefresh(); } catch(_) {}
  };

  window.UI.toggleItemsDescription = function () {
    try { playSound(getImageUrl("click.mp3")); } catch (_) {}
    if (window.itemInfoBox == 0) {
      window.itemInfoBox = 1;
      showEl("#item_info_box");
      showEl("#items_description_btn");
      hideEl("#items_table");
    } else {
      window.itemInfoBox = 0;
      hideEl("#item_info_box");
      hideEl("#items_description_btn");
      showEl("#items_table");
    }
  };

  window.UI.toggleMonsterInfo = function () {
    try { playSound(getImageUrl("click.mp3")); } catch (_) {}
    try { if (window.UI && typeof UI.raisePanel === 'function') UI.raisePanel('#monster_box'); } catch(_) {}
    window.monsterInfoOpen = !window.monsterInfoOpen;
    if (window.monsterInfoOpen) {
      showEl('#monster_box');
      showEl('#monster_info_box');
      try { speak($("#monster_description").text()); } catch (_) {}
    } else {
      hideEl('#monster_info_box');
    }
    updateMonsterContainers();
  };

  window.UI.toggleMonsterStats = function () {
    try { playSound(getImageUrl("click.mp3")); } catch (_) {}
    try { if (window.UI && typeof UI.raisePanel === 'function') UI.raisePanel('#monster_box'); } catch(_) {}
    window.monsterStatsOpen = !window.monsterStatsOpen;
    if (window.monsterStatsOpen) {
      showEl('#monster_box');
      showEl('#monster_stats_box');
    } else {
      hideEl('#monster_stats_box');
    }
    updateMonsterContainers();
  };

  window.UI.toggleBattleLog = function () {
    try { playSound(getImageUrl("click.mp3")); } catch (_) {}
    try { if (window.UI && typeof UI.raisePanel === 'function') UI.raisePanel('#monster_box'); } catch(_) {}
    window.monsterBattleOpen = !window.monsterBattleOpen;
    if (window.monsterBattleOpen) {
      showEl('#monster_box');
      showEl('#monster_battle_box');
    } else {
      hideEl('#monster_battle_box');
    }
    updateMonsterContainers();
  };

  window.UI.toggleDebug = function () {
    if (!window.showDebug) {
      window.showDebug = true;
      showEl("#debug");
    } else {
      window.showDebug = false;
      hideEl("#debug");
    }
  };

  // Keyboard handler: attach once, delegate to existing globals
  function setupKeyboardHandlers() {
    if (window.__kbSetup) return;
    window.__kbSetup = true;
    document.onkeydown = function (event) {
      switch (event.keyCode) {
        case 37: case 65: if (window.Movement && typeof Movement.move === 'function') Movement.move("left"); break;
        case 38: case 87: if (window.Movement && typeof Movement.move === 'function') Movement.move("up"); break;
        case 39: case 68: if (window.Movement && typeof Movement.move === 'function') Movement.move("right"); break;
        case 40: case 83: if (window.Movement && typeof Movement.move === 'function') Movement.move("down"); break;
        case 13: {
          // Enter: close any open primary dialogs (win/gather) or confirm defeat
          var handled = false;
          try {
            var winDlg = document.getElementById('win-dialog');
            if (winDlg && winDlg.open) { try { playSound(getImageUrl('click.mp3')); } catch(_) {} winDlg.close(); handled = true; }
          } catch(_) {}
          try {
            if (!handled) {
              var gDlg = document.getElementById('gather-dialog');
              if (gDlg && gDlg.open) { try { playSound(getImageUrl('click.mp3')); } catch(_) {} gDlg.close(); handled = true; }
            }
          } catch(_) {}
          try {
            if (!handled) {
              var loseDlg = document.getElementById('lose-dialog');
              if (loseDlg && loseDlg.open) { try { playSound(getImageUrl('click.mp3')); } catch(_) {} if (window.UI && typeof UI.gameOver === 'function') UI.gameOver(); handled = true; }
            }
          } catch(_) {}
          if (handled) { try { event.preventDefault(); } catch(_) {} }
          break;
        }
        case 67: if (window.UI && typeof UI.toggleStats === 'function') UI.toggleStats(); break;
        case 73: if (window.UI && typeof UI.toggleItemsTable === 'function') UI.toggleItemsTable(); break;
        case 90: if (window.UI && typeof UI.toggleLocationInfo === 'function') UI.toggleLocationInfo(); break;
        case 88: if (window.UI && typeof UI.toggleLocationStats === 'function') UI.toggleLocationStats(); break;
        case 86: if ($("#monster_box").is(':visible') && window.UI && typeof UI.toggleMonsterInfo === 'function') UI.toggleMonsterInfo(); break;
        case 66: if ($("#monster_box").is(':visible') && window.UI && typeof UI.toggleMonsterStats === 'function') UI.toggleMonsterStats(); break;
        case 78: if ($("#monster_box").is(':visible') && window.UI && typeof UI.toggleBattleLog === 'function') UI.toggleBattleLog(); break;
        case 77: if ($("#monster_box").is(':visible') && window.Combat && typeof Combat.attack === 'function') Combat.attack(); break;
        case 72:
          if ($("#help-dialog").is(':visible')) { $("#close_help_btn").click(); }
          else { try { playSound(getImageUrl("click.mp3")); } catch (_) {} document.getElementById('help-dialog').showModal(); }
          break;
      }
    };
  }
  window.UI.setupKeyboardHandlers = setupKeyboardHandlers;
  // Auto-attach on load for backward compatibility
  setupKeyboardHandlers();
  setupPanelHoverZ();

  // Skill Info auto-refresh loop (cooldown/MP while panel is open)
  function startSkillInfoAutorefresh(skillId){
    try {
      if (window.__skillInfoTimer) { clearInterval(window.__skillInfoTimer); window.__skillInfoTimer = null; }
      window.__skillInfoSkill = skillId || window.__skillInfoSkill || 'power_strike';
      var id = window.__skillInfoSkill;
      window.__skillInfoTimer = setInterval(function(){
        try {
          var box = document.getElementById('skill_info_box');
          if (!box || box.classList.contains('hidden') || box.style.display === 'none') {
            clearInterval(window.__skillInfoTimer); window.__skillInfoTimer = null; return;
          }
          if (id === 'power_strike') {
            var mp = parseInt($('#player_mp').text() || '0', 10) || 0;
            var remain = window._psRemain ? (parseInt(window._psRemain, 10) || 0) : 0;
            var cost = 5;
            var $useBtn = $('#skill_use_btn_power_strike');
            var $status = $('#skill_status_power_strike');
            if ($useBtn && $useBtn.length) {
              if (remain > 0) {
                $useBtn.addClass('is-disabled').attr('disabled', 'disabled');
                $status.text('Cooldown ' + remain + 's');
              } else if (mp < cost) {
                $useBtn.addClass('is-disabled').attr('disabled', 'disabled');
                $status.text('Need ' + cost + ' MP');
              } else {
                $useBtn.removeClass('is-disabled').removeAttr('disabled');
                $status.text('');
              }
            }
          } else if (id === 'fireball') {
            var mp2 = parseInt($('#player_mp').text() || '0', 10) || 0;
            var remain2 = window._fbRemain ? (parseInt(window._fbRemain, 10) || 0) : 0;
            var cost2 = 7;
            var $useBtn2 = $('#skill_use_btn_fireball');
            var $status2 = $('#skill_status_fireball');
            if ($useBtn2 && $useBtn2.length) {
              if (remain2 > 0) {
                $useBtn2.addClass('is-disabled').attr('disabled', 'disabled');
                $status2.text('Cooldown ' + remain2 + 's');
              } else if (mp2 < cost2) {
                $useBtn2.addClass('is-disabled').attr('disabled', 'disabled');
                $status2.text('Need ' + cost2 + ' MP');
              } else {
                $useBtn2.removeClass('is-disabled').removeAttr('disabled');
                $status2.text('');
              }
            }
          }
        } catch(_) {}
      }, 300);
    } catch(_) {}
  }
  function stopSkillInfoAutorefresh(){
    try { if (window.__skillInfoTimer) { clearInterval(window.__skillInfoTimer); window.__skillInfoTimer = null; } } catch(_) {}
  }
})();
