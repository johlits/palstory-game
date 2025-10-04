// Skill Tree module
(function(){
  'use strict';

  // Skills loaded from database
  var SKILLS = {};

  function refresh() {
    try {
      var playerName = $("#player").text();
      var roomId = $("#room_id").text();
      if (!playerName || !roomId) return;

      // Load skills from database first
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
                cost: s.unlock_cost,
                job: s.required_job,
                mp: s.mp_cost,
                cd: s.cooldown_sec,
                mult: s.damage_multiplier
              };
            });
          }
          // Get player stats to check skill points, job, and unlocked skills
          if (window.api && typeof window.api.getPlayer === 'function') {
            window.api.getPlayer(playerName, roomId).then(function(resp){
              if (!resp || !resp.stats) return;
              var stats = parsePlayerStats(resp.stats);
              renderSkillTree(stats);
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
      console.error('SkillTree.refresh error:', e);
    }
  }

  function parsePlayerStats(statsStr) {
    var stats = { skill_points: 0, job: 'none', unlocked_skills: [] };
    if (!statsStr) return stats;
    var parts = statsStr.split(';');
    for (var i = 0; i < parts.length; i++) {
      var p = parts[i];
      if (p.indexOf('skill_points=') === 0) {
        stats.skill_points = parseInt(p.split('=')[1] || '0', 10);
      } else if (p.indexOf('job=') === 0) {
        stats.job = p.split('=')[1] || 'none';
      } else if (p.indexOf('unlocked_skills=') === 0) {
        var unlocked = p.split('=')[1] || '';
        stats.unlocked_skills = unlocked ? unlocked.split(',') : [];
      }
    }
    return stats;
  }

  function renderSkillTree(stats) {
    var $content = $('#skill_tree_content');
    if (!$content.length) return;

    $('#skill_points_display').text(stats.skill_points);

    var html = '';
    
    // Group skills by job
    var jobGroups = {
      'all': [],
      'warrior': [],
      'rogue': [],
      'mage': [],
      'cleric': [],
      'ranger': []
    };

    for (var skillId in SKILLS) {
      var skill = SKILLS[skillId];
      jobGroups[skill.job].push({ id: skillId, data: skill });
    }

    // Render universal skills first
    if (jobGroups['all'].length > 0) {
      html += '<div style="margin-bottom:12px;"><span class="nes-text is-primary">Universal Skills</span></div>';
      jobGroups['all'].forEach(function(s){
        html += renderSkillRow(s.id, s.data, stats);
      });
    }

    // Render job-specific skills
    var jobOrder = ['warrior', 'rogue', 'mage', 'cleric', 'ranger'];
    jobOrder.forEach(function(job){
      if (jobGroups[job].length > 0) {
        var jobName = job.charAt(0).toUpperCase() + job.slice(1);
        html += '<div style="margin-top:16px; margin-bottom:8px;"><span class="nes-text is-warning">' + jobName + ' Skills</span></div>';
        jobGroups[job].forEach(function(s){
          html += renderSkillRow(s.id, s.data, stats);
        });
      }
    });

    $content.html(html);
  }

  function renderSkillRow(skillId, skill, stats) {
    var isUnlocked = stats.unlocked_skills.indexOf(skillId) >= 0;
    var canUnlock = !isUnlocked && stats.skill_points >= skill.cost;
    var wrongJob = skill.job !== 'all' && stats.job !== skill.job;
    var noJob = stats.job === 'none' && skill.job !== 'all';

    var statusClass = isUnlocked ? 'is-success' : (canUnlock && !wrongJob && !noJob ? 'is-primary' : 'is-disabled');
    var statusText = isUnlocked ? '✓ Unlocked' : (wrongJob ? '(Requires ' + skill.job + ')' : (noJob ? '(Select a job first)' : 'Cost: ' + skill.cost + ' SP'));

    var html = '<div style="margin-bottom:8px; padding:8px; border:1px solid #ccc; border-radius:4px;">';
    html += '<div style="display:flex; justify-content:space-between; align-items:center;">';
    html += '<div>';
    html += '<span class="nes-text ' + statusClass + '">' + skill.name + '</span>';
    html += '<div class="nes-text is-disabled" style="font-size:0.85em; margin-top:2px;">' + skill.desc + ' (MP ' + skill.mp + ', CD ' + skill.cd + 's)</div>';
    html += '</div>';
    html += '<div>';
    if (isUnlocked) {
      html += '<span class="nes-text is-success">✓</span>';
    } else if (canUnlock && !wrongJob && !noJob) {
      html += '<button class="nes-btn is-primary" onclick="SkillTree.unlockSkill(\'' + skillId + '\')">Unlock</button>';
    } else {
      html += '<span class="nes-text is-disabled">' + statusText + '</span>';
    }
    html += '</div>';
    html += '</div>';
    html += '</div>';
    return html;
  }

  function unlockSkill(skillId) {
    try {
      var playerName = $("#player").text();
      var roomId = $("#room_id").text();
      if (!playerName || !roomId) return;

      $.ajax({
        url: 'gameServer.php',
        type: 'post',
        data: {
          unlock_skill: '1',
          player_name: playerName,
          room_id: roomId,
          skill: skillId
        },
        dataType: 'json',
        success: function(resp){
          if (resp && resp.success) {
            try { playSound(getImageUrl('win.mp3')); } catch(_) {}
            $("#battle_log").prepend('<span class="nes-text is-success">Unlocked skill: ' + (SKILLS[skillId] ? SKILLS[skillId].name : skillId) + '!</span><br/>');
            refresh();
            // Refresh player to update stats
            if (typeof getPlayer === 'function') getPlayer(false);
          } else if (resp && resp.err) {
            var msg = 'Failed to unlock skill';
            if (resp.err === 'not_enough_skill_points') {
              msg = 'Not enough skill points (need ' + resp.required + ', have ' + resp.available + ')';
            } else if (resp.err === 'wrong_job') {
              msg = 'Wrong job (requires ' + resp.required_job + ')';
            } else if (resp.err === 'already_unlocked') {
              msg = 'Skill already unlocked';
            }
            $("#battle_log").prepend('<span class="nes-text is-error">' + msg + '</span><br/>');
          }
        },
        error: function(){
          $("#battle_log").prepend('<span class="nes-text is-error">Failed to unlock skill</span><br/>');
        }
      });
    } catch (e) {
      console.error('unlockSkill error:', e);
    }
  }

  if (!window.SkillTree) window.SkillTree = {};
  window.SkillTree.refresh = refresh;
  window.SkillTree.unlockSkill = unlockSkill;
})();
