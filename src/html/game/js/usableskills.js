// Usable Skills module - displays unlocked skills that can be used in combat
(function(){
  'use strict';

  var SKILLS = {}; // Loaded from database
  var currentSkillId = null;

  function refresh() {
    try {
      var playerName = $("#player").text();
      var roomId = $("#room_id").text();
      if (!playerName || !roomId) return;

      // Load skills from database
      $.ajax({
        url: 'gameServer.php',
        type: 'get',
        data: { get_skills: '1' },
        dataType: 'json',
        success: function(skills) {
          if (Array.isArray(skills)) {
            SKILLS = {};
            skills.forEach(function(s) {
              SKILLS[s.skill_id] = {
                name: s.name,
                desc: s.description,
                mp_cost: s.mp_cost,
                cooldown_sec: s.cooldown_sec,
                damage_multiplier: s.damage_multiplier
              };
            });
          }
          // Get player stats to check unlocked skills
          if (window.api && typeof window.api.getPlayer === 'function') {
            window.api.getPlayer(playerName, roomId).then(function(resp){
              var player = Array.isArray(resp) ? resp[0] : resp;
              if (!player || !player.stats) return;
              var stats = parsePlayerStats(player.stats);
              renderSkillButtons(stats);
            }).catch(function(err){
              console.error('Failed to get player stats:', err);
            });
          }
        },
        error: function() {
          console.error('Failed to load skills from database');
        }
      });
    } catch (e) {
      console.error('UsableSkills.refresh error:', e);
    }
  }

  function parsePlayerStats(statsStr) {
    var stats = { unlocked_skills: [] };
    if (!statsStr) return stats;
    var parts = statsStr.split(';');
    for (var i = 0; i < parts.length; i++) {
      var p = parts[i];
      if (p.indexOf('unlocked_skills=') === 0) {
        var skillsStr = p.split('=')[1] || '';
        stats.unlocked_skills = skillsStr ? skillsStr.split(',') : [];
      }
    }
    return stats;
  }

  function renderSkillButtons(stats) {
    var $container = $('#skills_buttons_container');
    if (!$container.length) return;

    var html = '';
    var hasSkills = false;

    stats.unlocked_skills.forEach(function(skillId) {
      if (skillId && SKILLS[skillId]) {
        hasSkills = true;
        var skill = SKILLS[skillId];
        var btnClass = 'nes-btn is-primary';
        var title = skill.name + ' (MP ' + skill.mp_cost + ', CD ' + skill.cooldown_sec + 's)';
        // Create abbreviated button text (first 2 letters)
        var abbr = skill.name.substring(0, 2).toUpperCase();
        html += '<button type="button" class="' + btnClass + '" onclick="UsableSkills.showSkillInfo(\'' + skillId + '\')" title="' + title + '" style="margin:2px;">' + abbr + '</button>';
      }
    });

    if (!hasSkills) {
      html = '<span class="nes-text is-disabled">No skills unlocked yet.</span>';
    }

    $container.html(html);
  }

  function showSkillInfo(skillId) {
    try {
      currentSkillId = skillId;
      if (!SKILLS[skillId]) return;

      var skill = SKILLS[skillId];
      $('#skill_title').text(skill.name);
      $('#skill_desc').text(skill.desc + ' Deals ' + (skill.damage_multiplier * 100) + '% damage.');
      $('#skill_meta').text('Cost: ' + skill.mp_cost + ' MP • Cooldown: ' + skill.cooldown_sec + 's');
      
      $('#skill_info_box').removeClass('hidden');
      
      // Start auto-refresh for cooldown/MP status
      startSkillInfoAutorefresh(skillId);
    } catch (e) {
      console.error('showSkillInfo error:', e);
    }
  }

  function hideSkillInfo() {
    try {
      $('#skill_info_box').addClass('hidden');
      if (window.__skillInfoTimer) {
        clearInterval(window.__skillInfoTimer);
        window.__skillInfoTimer = null;
      }
    } catch (e) {
      console.error('hideSkillInfo error:', e);
    }
  }

  function startSkillInfoAutorefresh(skillId) {
    try {
      if (window.__skillInfoTimer) {
        clearInterval(window.__skillInfoTimer);
        window.__skillInfoTimer = null;
      }
      
      window.__skillInfoTimer = setInterval(function(){
        try {
          var box = document.getElementById('skill_info_box');
          if (!box || box.classList.contains('hidden')) {
            clearInterval(window.__skillInfoTimer);
            window.__skillInfoTimer = null;
            return;
          }
          
          var mp = parseInt($('#player_mp').text() || '0', 10) || 0;
          var skill = SKILLS[skillId];
          if (!skill) return;
          
          var $useBtn = $('#skill_use_btn');
          var $status = $('#skill_status');
          
          // Check cooldown (stored in window by combat.js)
          var cooldowns = window._skillCooldowns || {};
          var cdRemain = cooldowns[skillId] || 0;
          
          if (cdRemain > 0) {
            $useBtn.addClass('is-disabled').attr('disabled', 'disabled');
            $status.text('Cooldown: ' + cdRemain + 's');
          } else if (mp < skill.mp_cost) {
            $useBtn.addClass('is-disabled').attr('disabled', 'disabled');
            $status.text('Not enough MP');
          } else {
            $useBtn.removeClass('is-disabled').removeAttr('disabled');
            $status.text('Ready');
          }
        } catch (_) {}
      }, 500);
    } catch (e) {
      console.error('startSkillInfoAutorefresh error:', e);
    }
  }

  // Export public functions
  window.UsableSkills = {
    refresh: refresh,
    showSkillInfo: showSkillInfo,
    hideSkillInfo: hideSkillInfo,
    getCurrentSkillId: function() { return currentSkillId; }
  };

})();
