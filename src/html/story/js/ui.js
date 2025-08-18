// UI helpers module (safe to load before game.js)
(function(){
  'use strict';
  // Ensure UI toggle globals exist
  if (typeof window.locationToggle === 'undefined') window.locationToggle = 0;
  if (typeof window.itemToggle === 'undefined') window.itemToggle = 0;
  if (typeof window.itemInfoBox === 'undefined') window.itemInfoBox = 0;
  if (typeof window.monsterToggle === 'undefined') window.monsterToggle = 0;

  function showEl(sel) { try { $(sel).removeClass('hidden'); } catch (e) {} }
  function hideEl(sel) { try { $(sel).addClass('hidden'); } catch (e) {} }

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
    var url = "/story/game.php?room=" + $('#room').text() + "&player=" + $('#player').text();
    window.location.href = url;
  }

  function setGameLink() {
    var url = "/story/game.php?room=" + $('#room').text() + "&player=" + $('#player').text();
    $('#game_link').attr('href', url);
  }

  if (!window.UI) window.UI = {};
  window.UI.showEl = showEl;
  window.UI.hideEl = hideEl;
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
    var url = "/story/game.php?room=" + $('#room').text() + "&player=" + $('#player').text();
    window.location.href = url;
  };

  window.UI.setGameLink = function () {
    var url = "/story/game.php?room=" + $('#room').text() + "&player=" + $('#player').text();
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
    if (window.locationToggle != 1) {
      $("#location_box").css("z-index", "2");
      $("#monster_box").css("z-index", "1");
      showEl("#location_data_box");
      try { speak($("#location_description").text()); } catch (_) {}
      window.locationToggle = 1;
      hideEl("#location_stats_box");
      showEl("#location_info_box");
      hideEl("#location_name_box");
    } else {
      window.locationToggle = 0;
      hideEl("#location_data_box");
      showEl("#location_name_box");
    }
  };

  window.UI.toggleLocationStats = function () {
    try { playSound(getImageUrl("click.mp3")); } catch (_) {}
    if (window.locationToggle != 2) {
      $("#location_box").css("z-index", "2");
      $("#monster_box").css("z-index", "1");
      showEl("#location_data_box");
      window.locationToggle = 2;
      hideEl("#location_info_box");
      showEl("#location_stats_box");
      hideEl("#location_name_box");
    } else {
      window.locationToggle = 0;
      hideEl("#location_data_box");
      showEl("#location_name_box");
    }
  };

  window.UI.toggleItemsTable = function () {
    try { playSound(getImageUrl("click.mp3")); } catch (_) {}
    if (window.itemToggle == 0) {
      window.itemToggle = 1;
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
    if ($box.hasClass('hidden')) { showEl('#skills_box'); }
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
      default:
        return;
    }
    $('#skill_title').text(title);
    $('#skill_desc').text(desc + ' Costs ' + cost + ' MP. Cooldown ' + cd + 's.');
    $('#skill_meta').text('Cost: ' + cost + ' MP • Cooldown: ' + cd + 's');

    // Compute current state
    var mp = parseInt($('#player_mp').text() || '0', 10) || 0;
    var remain = window._psRemain ? parseInt(window._psRemain, 10) || 0 : 0;
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
  };

  window.UI.hideSkillInfo = function () {
    try { playSound(getImageUrl("click.mp3")); } catch (_) {}
    hideEl('#skill_info_box');
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
    if (window.monsterToggle != 1) {
      $("#location_box").css("z-index", "1");
      $("#monster_box").css("z-index", "2");
      showEl("#monster_data_box");
      window.monsterToggle = 1;
      hideEl("#monster_battle_box");
      hideEl("#monster_stats_box");
      showEl("#monster_info_box");
      hideEl("#monster_name_box");
      try { speak($("#monster_description").text()); } catch (_) {}
    } else {
      window.monsterToggle = 0;
      hideEl("#monster_data_box");
      showEl("#monster_name_box");
    }
  };

  window.UI.toggleMonsterStats = function () {
    try { playSound(getImageUrl("click.mp3")); } catch (_) {}
    if (window.monsterToggle != 2) {
      $("#location_box").css("z-index", "1");
      $("#monster_box").css("z-index", "2");
      showEl("#monster_data_box");
      window.monsterToggle = 2;
      hideEl("#monster_battle_box");
      hideEl("#monster_info_box");
      showEl("#monster_stats_box");
      hideEl("#monster_name_box");
    } else {
      window.monsterToggle = 0;
      hideEl("#monster_data_box");
      showEl("#monster_name_box");
    }
  };

  window.UI.toggleBattleLog = function () {
    try { playSound(getImageUrl("click.mp3")); } catch (_) {}
    if (window.monsterToggle != 3) {
      $("#location_box").css("z-index", "1");
      $("#monster_box").css("z-index", "2");
      showEl("#monster_data_box");
      window.monsterToggle = 3;
      hideEl("#monster_info_box");
      hideEl("#monster_stats_box");
      hideEl("#monster_name_box");
      showEl("#monster_battle_box");
    } else {
      window.monsterToggle = 0;
      hideEl("#monster_data_box");
      showEl("#monster_name_box");
    }
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
})();
