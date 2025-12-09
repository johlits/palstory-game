// Job Selection module
(function(){
  'use strict';

  // Jobs loaded from database
  var JOBS = {};
  var REQUIRED_LEVEL = 1;

  function refresh() {
    try {
      var playerName = $("#player").text();
      var roomId = $("#room_id").text();
      if (!playerName || !roomId) return;

      // Load jobs from database first
      $.ajax({
        url: 'gameServer.php',
        type: 'get',
        data: { get_jobs: '1' },
        dataType: 'json',
        success: function(jobs) {
          console.log('Loaded jobs from database:', jobs);
          if (Array.isArray(jobs)) {
            JOBS = {};
            var minLevel = 999;
            jobs.forEach(function(j) {
              JOBS[j.job_id] = {
                name: j.name,
                desc: j.description,
                modifiers: j.stat_modifiers,
                min_level: j.min_level,
                tier: j.tier,
                required_base_job: j.required_base_job
              };
              // Track minimum level from tier 1 jobs
              if (j.tier === 1 && j.min_level < minLevel) {
                minLevel = j.min_level;
              }
            });
            REQUIRED_LEVEL = minLevel;
            console.log('Processed JOBS object:', JOBS);
            console.log('Set REQUIRED_LEVEL to:', REQUIRED_LEVEL);
          } else {
            console.error('Jobs is not an array:', jobs);
          }
          // Get player stats to check level and current job
          if (window.api && typeof window.api.getPlayer === 'function') {
            console.log('Calling window.api.getPlayer...');
            window.api.getPlayer(playerName, roomId).then(function(resp){
              console.log('getPlayer response:', resp);
              // getPlayer returns an array, get first element
              var player = Array.isArray(resp) ? resp[0] : resp;
              if (!player || !player.stats) {
                console.error('No player or stats from getPlayer');
                return;
              }
              var stats = parsePlayerStats(player.stats);
              console.log('Parsed stats:', stats);
              renderJobSelection(stats);
            }).catch(function(err){
              console.error('Failed to get player stats:', err);
            });
          } else {
            console.error('window.api.getPlayer not available');
          }
        },
        error: function() {
          console.error('Failed to load jobs from database');
        }
      });
    } catch (e) {
      console.error('JobSelection.refresh error:', e);
    }
  }

  function parsePlayerStats(statsStr) {
    var stats = { lvl: 1, job: 'none' };
    if (!statsStr) return stats;
    var parts = statsStr.split(';');
    for (var i = 0; i < parts.length; i++) {
      var p = parts[i];
      if (p.indexOf('lvl=') === 0) {
        stats.lvl = parseInt(p.split('=')[1] || '1', 10);
      } else if (p.indexOf('job=') === 0) {
        stats.job = p.split('=')[1] || 'none';
      }
    }
    return stats;
  }

  function getBaseJobFromCurrent(jobId) {
    if (!jobId || jobId === 'none') return 'none';
    var current = jobId;
    var maxIterations = 10;
    var iterations = 0;
    
    while (iterations < maxIterations) {
      var jobData = JOBS[current];
      if (!jobData) return jobId;
      
      if (jobData.tier === 1) {
        return current; // Found base job
      }
      
      if (jobData.required_base_job) {
        current = jobData.required_base_job;
        iterations++;
      } else {
        break;
      }
    }
    
    return jobId; // Fallback
  }

  function renderJobSelection(stats) {
    console.log('renderJobSelection called with stats:', stats);
    console.log('Current JOBS:', JOBS);
    var $content = $('#job_selection_content');
    if (!$content.length) {
      console.error('job_selection_content element not found');
      return;
    }

    var jobName = stats.job === 'none' ? 'None' : (JOBS[stats.job] ? JOBS[stats.job].name : stats.job);
    $('#player_job_name').text(jobName);

    var html = '';
    
    console.log('stats.job value:', stats.job, 'type:', typeof stats.job);
    console.log('stats.job !== "none":', stats.job !== 'none');
    console.log('stats.lvl:', stats.lvl, 'REQUIRED_LEVEL:', REQUIRED_LEVEL);

    if (stats.job !== 'none') {
      // Has a job - show current job and advancement options
      var currentJobData = JOBS[stats.job];
      var currentTier = currentJobData ? currentJobData.tier : 1;
      html += '<div class="nes-text is-success" style="margin-top:8px;">Current Job: ' + jobName + ' (Tier ' + currentTier + ')</div>';
      
      // Show advancement options
      var advancements = [];
      for (var jobId in JOBS) {
        var job = JOBS[jobId];
        if (job.required_base_job && getBaseJobFromCurrent(stats.job) === job.required_base_job && job.tier > currentTier) {
          advancements.push({id: jobId, data: job});
        }
      }
      
      if (advancements.length > 0) {
        html += '<div class="nes-text is-primary" style="margin-top:12px; margin-bottom:8px;">Available Advancements:</div>';
        advancements.forEach(function(adv) {
          var canAdvance = stats.lvl >= adv.data.min_level;
          var btnClass = canAdvance ? 'is-primary' : 'is-disabled';
          var btnDisabled = canAdvance ? '' : ' disabled';
          html += '<div style="margin-bottom:12px; padding:8px; border:2px solid ' + (canAdvance ? '#209cee' : '#555') + '; border-radius:4px; background:' + (canAdvance ? '#1a1d20' : '#0d0f12') + ';">';
          html += '<div style="margin-bottom:4px;"><span class="nes-text ' + (canAdvance ? 'is-primary' : 'is-disabled') + '" style="font-weight:bold;">' + adv.data.name + ' (Tier ' + adv.data.tier + ')</span></div>';
          html += '<div style="font-size:0.85em; margin-bottom:4px; color:#d3d3d3;">' + adv.data.desc + '</div>';
          html += '<div style="font-size:0.85em; margin-bottom:4px; color:#d3d3d3;">Modifiers: ' + adv.data.modifiers + '</div>';
          html += '<div style="font-size:0.8em; margin-bottom:8px; color:#999;">Required Level: ' + adv.data.min_level + (canAdvance ? '' : ' (You are level ' + stats.lvl + ')') + '</div>';
          html += '<button class="nes-btn ' + btnClass + '"' + btnDisabled + ' onclick="JobSelection.selectJob(\'' + adv.id + '\');">Advance to ' + adv.data.name + '</button>';
          html += '</div>';
        });
      } else {
        html += '<div class="nes-text is-disabled" style="margin-top:8px; font-size:0.9em;">No further advancements available for your job path.</div>';
      }
    } else if (stats.lvl < REQUIRED_LEVEL) {
      // Level too low
      html += '<div class="nes-text is-warning" style="margin-top:8px;">Reach level ' + REQUIRED_LEVEL + ' to select a job.</div>';
      html += '<div class="nes-text is-disabled" style="margin-top:4px;">Current level: ' + stats.lvl + '</div>';
    } else {
      // Can select a job - show only tier 1 jobs
      html += '<div class="nes-text is-primary" style="margin-bottom:12px;">Choose your starting job:</div>';
      for (var jobId in JOBS) {
        var job = JOBS[jobId];
        if (job.tier === 1) {
          html += '<div style="margin-bottom:12px; padding:8px; border:2px solid #209cee; border-radius:4px; background:#1a1d20;">';
          html += '<div style="margin-bottom:4px;"><span class="nes-text is-primary" style="font-weight:bold;">' + job.name + '</span></div>';
          html += '<div style="font-size:0.85em; margin-bottom:4px; color:#d3d3d3;">' + job.desc + '</div>';
          html += '<div style="font-size:0.85em; margin-bottom:8px; color:#d3d3d3;">Modifiers: ' + job.modifiers + '</div>';
          html += '<button class="nes-btn is-primary" onclick="JobSelection.selectJob(\'' + jobId + '\');">Select ' + job.name + '</button>';
          html += '</div>';
        }
      }
    }
    
    console.log('Final HTML to render:', html);
    $content.html(html);
  }

  function selectJob(jobId) {
    try {
      if (!JOBS[jobId]) {
        console.error('Invalid job:', jobId);
        return;
      }

      var playerName = $("#player").text();
      var roomId = $("#room_id").text();
      if (!playerName || !roomId) return;

      // Confirm selection
      var jobName = JOBS[jobId].name;
      if (!confirm('Are you sure you want to become a ' + jobName + '? This choice is permanent!')) {
        return;
      }

      $.ajax({
        url: 'gameServer.php',
        type: 'post',
        data: {
          select_job: '1',
          player_name: playerName,
          room_id: roomId,
          job: jobId
        },
        dataType: 'json',
        success: function(resp){
          if (resp && resp.success) {
            try { playSound(getImageUrl('win.mp3')); } catch(_) {}
            $("#battle_log").prepend('<span class="nes-text is-success">You are now a ' + jobName + '!</span><br/>');
            refresh();
            // Refresh player to update stats
            if (typeof getPlayer === 'function') getPlayer(false);
          } else if (resp && resp.err) {
            var msg = 'Failed to select job';
            if (resp.err === 'level_too_low') {
              msg = 'You must be level ' + resp.required_level + ' to select a job';
            } else if (resp.err === 'already_has_job') {
              msg = 'You already have a job: ' + resp.current_job;
            } else if (resp.err === 'invalid_job') {
              msg = 'Invalid job selection';
            }
            $("#battle_log").prepend('<span class="nes-text is-error">' + msg + '</span><br/>');
          }
        },
        error: function(){
          $("#battle_log").prepend('<span class="nes-text is-error">Failed to select job</span><br/>');
        }
      });
    } catch (e) {
      console.error('selectJob error:', e);
    }
  }

  if (!window.JobSelection) window.JobSelection = {};
  window.JobSelection.refresh = refresh;
  window.JobSelection.selectJob = selectJob;
})();
