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
          console.log('Loaded skills from database:', skills);
          if (Array.isArray(skills)) {
            SKILLS = {};
            skills.forEach(function(s) {
              SKILLS[s.skill_id] = {
                name: s.name,
                desc: s.description,
                mp: s.mp_cost,
                cd: s.cooldown_sec,
                mult: s.damage_multiplier,
                cost: s.unlock_cost,
                job: s.required_job,
                type: s.skill_type || 'active',
                modifiers: s.stat_modifiers || null
              };
            });
            console.log('Processed SKILLS:', SKILLS);
          } else {
            console.error('Skills is not an array:', skills);
          }
          // Get player stats to check skill points, job, and unlocked skills
          if (window.api && typeof window.api.getPlayer === 'function') {
            window.api.getPlayer(playerName, roomId).then(function(resp){
              console.log('getPlayer response:', resp);
              // getPlayer returns an array, get first element
              var player = Array.isArray(resp) ? resp[0] : resp;
              if (!player || !player.stats) {
                console.error('No player or stats');
                return;
              }
              var stats = parsePlayerStats(player.stats);
              console.log('Parsed player stats:', stats);
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
    
    // Filter skills: show universal + player's job skills only
    var playerJob = stats.job || 'none';
    console.log('Player job:', playerJob);
    console.log('All SKILLS:', SKILLS);
    var relevantSkills = [];
    
    for (var skillId in SKILLS) {
      var skill = SKILLS[skillId];
      console.log('Checking skill:', skillId, 'job:', skill.job, 'matches:', skill.job === 'all' || skill.job === playerJob);
      // Show universal skills or skills matching player's job
      if (skill.job === 'all' || skill.job === playerJob) {
        relevantSkills.push({ id: skillId, data: skill, isUniversal: skill.job === 'all' });
      }
    }
    console.log('Relevant skills:', relevantSkills);

    if (relevantSkills.length === 0) {
      html += '<div class="nes-text is-disabled">Select a job to see available skills.</div>';
    } else {
      // Render universal skills first
      var universalSkills = relevantSkills.filter(function(s){ return s.isUniversal; });
      if (universalSkills.length > 0) {
        html += '<div style="margin-bottom:12px;"><span class="nes-text is-primary">Universal Skills</span></div>';
        universalSkills.forEach(function(s){
          html += renderSkillRow(s.id, s.data, stats);
        });
      }

      // Render job-specific skills
      var jobSkills = relevantSkills.filter(function(s){ return !s.isUniversal; });
      if (jobSkills.length > 0) {
        var jobName = playerJob.charAt(0).toUpperCase() + playerJob.slice(1);
        html += '<div style="margin-top:16px; margin-bottom:8px;"><span class="nes-text is-warning">' + jobName + ' Skills</span></div>';
        jobSkills.forEach(function(s){
          html += renderSkillRow(s.id, s.data, stats);
        });
      }
    }

    $content.html(html);
  }

  function renderSkillRow(skillId, skill, stats) {
    var isUnlocked = stats.unlocked_skills.indexOf(skillId) >= 0;
    var canUnlock = !isUnlocked && stats.skill_points >= skill.cost;
    var wrongJob = skill.job !== 'all' && stats.job !== skill.job;
    var noJob = stats.job === 'none' && skill.job !== 'all';

    var statusClass = isUnlocked ? 'is-success' : (canUnlock && !wrongJob && !noJob ? 'is-primary' : 'is-disabled');
    var statusText = isUnlocked ? '✓ Unlocked' : (wrongJob ? '(Requires ' + skill.job + ')' : (noJob ? '(Select a job first)' : 'Cost: ' + skill.cost + ' SP'));

    var isPassive = skill.type === 'passive';
    var skillTypeLabel = isPassive ? '[PASSIVE]' : '';
    var skillDetails = isPassive ? skill.desc : (skill.desc + ' (MP ' + skill.mp + ', CD ' + skill.cd + 's)');
    
    var html = '<div style="margin-bottom:8px; padding:8px; border:1px solid #ccc; border-radius:4px; background-color:' + (isPassive ? '#1a1d20' : '#0d0f12') + ';">';
    html += '<div style="display:flex; justify-content:space-between; align-items:center;">';
    html += '<div>';
    html += '<span class="nes-text ' + statusClass + '">' + (isPassive ? '⚡ ' : '') + skill.name + ' ' + skillTypeLabel + '</span>';
    html += '<div style="font-size:0.85em; margin-top:2px; color:#d3d3d3;">' + skillDetails + '</div>';
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
