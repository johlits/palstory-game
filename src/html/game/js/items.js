// Items module: handles fetching and rendering the player's items list
// Exposes: window.Items.getItems(), window.Items.getRarityInfo()
(function () {
  if (!window.Items) window.Items = {};

  // Rarity definitions: 0=common, 1=uncommon, 2=rare, 3=epic, 4=legendary
  var RARITY_NAMES = ['Common', 'Uncommon', 'Rare', 'Epic', 'Legendary'];
  var RARITY_COLORS = ['#9e9e9e', '#4caf50', '#2196f3', '#9c27b0', '#ff9800'];

  function getRarityInfo(rarity) {
    var r = parseInt(rarity) || 0;
    if (r < 0 || r >= RARITY_NAMES.length) r = 0;
    return { name: RARITY_NAMES[r], color: RARITY_COLORS[r] };
  }

  function getItems() {
    try {
      console.log("getting items..");
      var playerId = $("#player_id").text();
      var roomId = $("#room_id").text();
      if (window.api && typeof window.api.getItems === 'function') {
        return window.api.getItems(playerId, roomId)
          .then(function (response) {
            // Clear table body
            try { $("#items_table_body").empty(); } catch (e) {
              $("#items_table_body").find('tbody tr').remove();
            }

            // Reset item bonuses (globals from game.js)
            window.playerItemAtk = 0;
            window.playerItemDef = 0;
            window.playerItemSpd = 0;
            window.playerItemEvd = 0;

            if (response && response.length > 0) {
              $.each(response, function (idx, item) {
                if (item && item.image && typeof window.getImageUrl === 'function') {
                  if (typeof window.loadImage === 'function') {
                    window.loadImage(window.getImageUrl(item.image));
                  }
                }

                var itemId = parseInt(item.id);
                var itemName = item.name || '';
                var itemStatsStr = item.stats || '';
                var itemDescription = item.description || '';
                var itemImage = item.image || '';
                var equipped = parseInt(item.equipped) === 1;
                var itemRarity = parseInt(item.rarity) || 0;
                var rarityInfo = getRarityInfo(itemRarity);

                var itemAtk = 0, itemDef = 0, itemSpd = 0, itemEvd = 0, itemType = '';
                var fields = itemStatsStr.split(";");
                for (var i = 0; i < fields.length; i++) {
                  var f = fields[i];
                  if (f.indexOf("atk=") === 0) itemAtk = parseInt((f.split("=")[1] || '0')) || 0;
                  if (f.indexOf("def=") === 0) itemDef = parseInt((f.split("=")[1] || '0')) || 0;
                  if (f.indexOf("spd=") === 0) itemSpd = parseInt((f.split("=")[1] || '0')) || 0;
                  if (f.indexOf("evd=") === 0) itemEvd = parseInt((f.split("=")[1] || '0')) || 0;
                  if (f.indexOf("type=") === 0) itemType = (f.split("=")[1] || '');
                }

                if (equipped) {
                  window.playerItemAtk += itemAtk;
                  window.playerItemDef += itemDef;
                  window.playerItemSpd += itemSpd;
                  window.playerItemEvd += itemEvd;
                }

                window.itemsDict[itemId] = {
                  name: itemName,
                  image: itemImage,
                  stats: itemStatsStr,
                  description: itemDescription,
                  equipped: equipped,
                  rarity: itemRarity
                };

                var rowHtml = '' +
                  '<tr>' +
                    '<td>' + (itemImage ? ('<img src="' + window.getImageUrl(itemImage) + '" alt="" width="32" height="32"/>') : '') + '</td>' +
                    '<td><span style="color: ' + rarityInfo.color + '; font-weight: 600;">' + itemName + '</span></td>' +
                    '<td>' + itemAtk + '</td>' +
                    '<td>' + itemDef + '</td>' +
                    '<td>' + itemSpd + '</td>' +
                    '<td>' + itemEvd + '</td>' +
                    '<td>' + itemType + '</td>' +
                    '<td><input type="checkbox" id="ie' + itemId + '" ' + (equipped ? 'checked' : '') + ' /></td>' +
                    '<td><button id="ir' + itemId + '" class="drop-btn">Drop</button></td>' +
                  '</tr>';
                $("#items_table_body").append(rowHtml);

                // Equip toggle handler (equip on check, unequip on uncheck)
                $('#ie' + itemId).off('change').on('change', function () {
                  if (typeof window.playSound === 'function') {
                    window.playSound(window.getImageUrl("click.mp3"));
                  }
                  var no = parseInt(this.id.slice(2));
                  var doEquip = this.checked;
                  if (window.api) {
                    var p = doEquip && typeof window.api.equipItem === 'function'
                      ? window.api.equipItem(no, $("#player_id").text())
                      : (typeof window.api.unequipItem === 'function' ? window.api.unequipItem(no, $("#player_id").text()) : Promise.resolve());
                    Promise.resolve(p)
                      .then(function (resp) {
                        if (resp && resp[0] === "ok") { getItems(); }
                      })
                      .catch(function (err) { console.error("error: " + err); });
                  }
                });

                // Drop button handler
                $('#ir' + itemId).off('click').on('click', function () {
                  if (typeof window.playSound === 'function') {
                    window.playSound(window.getImageUrl("click.mp3"));
                  }
                  var no = parseInt(this.id.slice(2));
                  if (window.api && typeof window.api.dropItem === 'function') {
                    window.api.dropItem(no, $("#player_id").text())
                      .then(function (resp) {
                        if (resp && resp[0] === "ok") { getItems(); }
                      })
                      .catch(function (err) { console.error("error: " + err); });
                  }
                });
              });
            } else {
              try { $("#items_table_body").empty(); } catch (e) {
                $("#items_table_body").find('tbody tr').remove();
              }
            }

            // Update player stat boxes
            $("#player_atk").html("<span>" + (window.playerAtk + window.playerItemAtk) + "</span><br/><span style='color: #bbb'>(" + window.playerAtk + "+" + window.playerItemAtk + ")</span>");
            $("#player_def").html("<span>" + (window.playerDef + window.playerItemDef) + "</span><br/><span style='color: #bbb'>(" + window.playerDef + "+" + window.playerItemDef + ")</span>");
            $("#player_spd").html("<span>" + (window.playerSpd + window.playerItemSpd) + "</span><br/><span style='color: #bbb'>(" + window.playerSpd + "+" + window.playerItemSpd + ")</span>");
            $("#player_evd").html("<span>" + (window.playerEvd + window.playerItemEvd) + "</span><br/><span style='color: #bbb'>(" + window.playerEvd + "+" + window.playerItemEvd + ")</span>");
          })
          .catch(function (err) {
            console.error("error: " + err);
          });
      } else {
        console.error('API not available');
        return Promise.resolve();
      }
    } catch (e) {
      console.error(e);
      return Promise.reject(e);
    }
  }

  window.Items.getItems = getItems;
  window.Items.getRarityInfo = getRarityInfo;
})();
