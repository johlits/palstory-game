// Monsters module (safe to load before game.js). Uses legacy get_monster endpoint.
(function(){
  'use strict';

  function safeGetImageUrl(path) {
    try { if (typeof getImageUrl === 'function') return getImageUrl(path); } catch (e) {}
    return path;
  }
  function preloadImg(url) {
    try {
      if (typeof window.loadImageQueued === 'function') {
        window.loadImageQueued(url);
        return;
      }
    } catch (e) {}
    try { var img = new Image(); img.src = url; } catch (e2) {}
  }

  function preloadNearbyMonsters(cx, cy) {
    try {
      var coords = [ [cx, cy], [cx+1, cy], [cx-1, cy], [cx, cy+1], [cx, cy-1] ];
      coords.forEach(function (pt) {
        var roomId = $('#room_id').text();
        if (window.api && typeof window.api.getMonster === 'function') {
          window.api.getMonster(roomId, pt[0], pt[1])
            .then(function(resp){
              if (resp && resp.length) {
                for (var i = 0; i < resp.length; i++) {
                  if (resp[i] && resp[i].image) {
                    preloadImg(safeGetImageUrl(resp[i].image));
                  }
                }
              }
            })
            .catch(function(){ /* silent */ });
        } else {
          // Legacy fallback
          $.ajax({
            url: 'gameServer.php',
            type: 'get',
            data: 'get_monster=' + roomId + '&x=' + pt[0] + '&y=' + pt[1],
            dataType: 'json',
            success: function (resp) {
              if (resp && resp.length) {
                for (var i = 0; i < resp.length; i++) {
                  if (resp[i] && resp[i].image) {
                    preloadImg(safeGetImageUrl(resp[i].image));
                  }
                }
              }
            },
            error: function () { /* silent */ }
          });
        }
      });
    } catch (e) { /* ignore */ }
  }

  function getMonsters(newX, newY) {
    return new Promise(function(resolve){
      try {
        var location = window.locationsDict && window.locationsDict['' + newX + ',' + newY];
        if (location) {
          $('#location_box').show();
          $('.location_name').text(location.name);
          try { if (location.image && location.image.currentSrc) $('#location_image').attr('src', location.image.currentSrc); } catch (e) {}
          $('#location_description').text(location.description);
          $('#locationStatsPrimaryBtn').show();
          $('#locationStatsDisabledBtn').hide();
          $('#locationInfoBtn').show();
          $('#locationInfoDisabledBtn').hide();

          var location_stats = location.stats || '';
          var location_fields = location_stats.split(';');
          $('#location_spawns').text('None');
          for (var idx = 0; idx < location_fields.length; idx++) {
            var field = location_fields[idx];
            if (field.indexOf('spawns') === 0) {
              $('#location_spawns').text(field.split('=')[1].split(',').join(', '));
            }
          }
        }

        var roomId = $('#room_id').text();
        var p;
        if (window.api && typeof window.api.getMonster === 'function') {
          p = window.api.getMonster(roomId, newX, newY);
        } else {
          // Legacy fallback to raw AJAX
          p = new Promise(function(res){
            $.ajax({
              url: 'gameServer.php',
              type: 'get',
              data: 'get_monster=' + roomId + '&x=' + newX + '&y=' + newY,
              dataType: 'json',
              success: function (r) { res(r); },
              error: function () { res([]); }
            });
          });
        }
        p.then(function (response) {
          if (Array.isArray(response) && response.length > 0) {
            try { console.log('Monsters.getMonsters: found', response.length, 'at', newX, newY); } catch (e) {}
            var currentMonsterIdTemp = -1;
            response.forEach(function (item) {
              if (!item) return;
              preloadImg(safeGetImageUrl(item.image));
              currentMonsterIdTemp = parseInt(item.id);
              window.currentMonster = {
                name: item.name,
                description: item.description,
                stats: item.stats,
                image: safeGetImageUrl(item.image)
              };
            });
            // Force show monster box and bring to front defensively
            try {
              if (typeof showEl === 'function') showEl('#monster_box'); else $('#monster_box').show();
              $('#monster_box').removeClass('hidden');
              $('#monster_box').css({ display: 'block', visibility: 'visible', opacity: 1, 'z-index': 9999, position: 'relative' });
            } catch(e3) {}
            if (typeof window.currentMonsterId === 'undefined') window.currentMonsterId = -1;
            if (currentMonsterIdTemp != window.currentMonsterId) {
              window.currentMonsterId = currentMonsterIdTemp;
              $('#battle_log').empty();
              $('#winBattleBox').empty();
            }
            $('.monster_name').text(window.currentMonster.name);
            $('#monster_image').attr('src', window.currentMonster.image);
            $('#monster_description').text(window.currentMonster.description);

            var monster_fields = (window.currentMonster.stats || '').split(';');
            for (var i = 0; i < monster_fields.length; i++) {
              var field = monster_fields[i];
              if (field.indexOf('drops') === 0) {
                $('#monster_drops').text(field.split('=')[1].split(',').join(', '));
              } else if (field.indexOf('gold') === 0) {
                $('#monster_gold').text(field.split('=')[1]);
              } else if (field.indexOf('exp') === 0) {
                $('#monster_exp').text(field.split('=')[1]);
              } else if (field.indexOf('atk') === 0) {
                $('#monster_atk').text(field.split('=')[1]);
              } else if (field.indexOf('def') === 0) {
                $('#monster_def').text(field.split('=')[1]);
              } else if (field.indexOf('spd') === 0) {
                $('#monster_spd').text(field.split('=')[1]);
              } else if (field.indexOf('evd') === 0) {
                $('#monster_evd').text(field.split('=')[1]);
              } else if (field.indexOf('hp') === 0) {
                $('.monster_hp').text(field.split('=')[1]);
                $('.monster_hp_progress').attr('value', field.split('=')[1]);
              } else if (field.indexOf('maxhp') === 0) {
                $('.monster_maxhp').text(field.split('=')[1]);
                $('.monster_hp_progress').attr('max', field.split('=')[1]);
              }
            }
          } else {
            try { console.log('Monsters.getMonsters: none at', newX, newY); } catch (e) {}
            window.currentMonster = null;
            try {
              if (typeof hideEl === 'function') hideEl('#monster_box'); else $('#monster_box').hide();
              $('#monster_box').addClass('hidden');
              $('#monster_box').css({ display: 'none', visibility: 'hidden', opacity: 0 });
            } catch (e) {}
          }
          resolve();
        }).catch(function(){ try { resolve(); } catch (e) {} });
      } catch (e) { try { resolve(); } catch (e2) {} }
    });
  }

  if (!window.Monsters) window.Monsters = {};
  window.Monsters.preloadNearbyMonsters = preloadNearbyMonsters;
  window.Monsters.getMonsters = getMonsters;
})();
