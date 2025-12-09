// Storage module: handle storage UI and item transfers
(function () {
  if (!window.Storage) window.Storage = {};

  var _storageData = null;
  var _currentTab = 'stored'; // 'stored' or 'deposit'

  // Open storage dialog
  window.Storage.open = function() {
    try {
      var playerName = $("#player").text();
      var roomId = $("#room_id").text();
      
      if (!window.api || typeof window.api.getStorage !== 'function') {
        console.error('Storage API not available');
        return;
      }
      
      if (typeof window.playSound === 'function') {
        window.playSound(window.getImageUrl("click.mp3"));
      }
      
      // Fetch storage contents
      window.api.getStorage(playerName, roomId)
        .then(function(resp){
          console.log('storage resp', resp);
          
          if (resp && resp.success) {
            _storageData = resp;
            _renderStorage();
            
            var dlg = document.getElementById('storage-dialog');
            if (dlg && typeof dlg.showModal === 'function') {
              dlg.showModal();
            }
          } else {
            alert(resp.message || 'Failed to load storage.');
          }
        })
        .catch(function(err){
          console.error('storage error: ' + err);
          alert('Failed to load storage.');
        });
    } catch(e) {
      console.error(e);
    }
  };

  // Close storage dialog
  window.Storage.close = function() {
    try {
      var dlg = document.getElementById('storage-dialog');
      if (dlg && typeof dlg.close === 'function') {
        dlg.close();
      }
    } catch(e) {
      console.error(e);
    }
  };

  // Switch tab (stored/deposit)
  window.Storage.switchTab = function(tab) {
    _currentTab = tab;
    _renderStorage();
  };

  // Render storage UI
  function _renderStorage() {
    try {
      var storedTab = document.getElementById('storage-tab-stored');
      var depositTab = document.getElementById('storage-tab-deposit');
      var storedPanel = document.getElementById('storage-panel-stored');
      var depositPanel = document.getElementById('storage-panel-deposit');
      var slotsInfo = document.getElementById('storage-slots-info');
      
      if (!storedTab || !depositTab || !storedPanel || !depositPanel) return;
      
      // Update slots info
      if (slotsInfo && _storageData) {
        slotsInfo.textContent = 'Storage: ' + _storageData.slots_used + '/' + _storageData.slots_max + ' slots';
      }
      
      // Update tab styles
      if (_currentTab === 'stored') {
        storedTab.classList.add('is-primary');
        depositTab.classList.remove('is-primary');
        storedPanel.style.display = '';
        depositPanel.style.display = 'none';
        _renderStoredPanel();
      } else {
        storedTab.classList.remove('is-primary');
        depositTab.classList.add('is-primary');
        storedPanel.style.display = 'none';
        depositPanel.style.display = '';
        _renderDepositPanel();
      }
    } catch(e) {
      console.error(e);
    }
  }

  // Render stored items panel (withdraw)
  function _renderStoredPanel() {
    try {
      var container = document.getElementById('storage-stored-items');
      if (!container || !_storageData) return;
      
      container.innerHTML = '';
      
      if (!_storageData.items || _storageData.items.length === 0) {
        container.innerHTML = '<p class="nes-text">Storage is empty.</p>';
        return;
      }
      
      _storageData.items.forEach(function(item) {
        var itemDiv = document.createElement('div');
        itemDiv.className = 'storage-item';
        itemDiv.style.cssText = 'display:flex;justify-content:space-between;align-items:center;margin:8px 0;padding:8px;border:2px solid #ccc;';
        
        var infoDiv = document.createElement('div');
        infoDiv.style.flex = '1';
        
        var nameSpan = document.createElement('span');
        nameSpan.className = 'nes-text';
        nameSpan.textContent = item.name;
        if (typeof item.rarity !== 'undefined') {
          var rarity = parseInt(item.rarity);
          var rarityClass = 'is-disabled'; // common
          if (rarity === 4) rarityClass = 'is-warning'; // legendary
          else if (rarity === 3) rarityClass = 'is-primary'; // epic
          else if (rarity === 2) rarityClass = 'is-success'; // rare
          else if (rarity === 1) rarityClass = ''; // uncommon
          nameSpan.className += ' ' + rarityClass;
        }
        infoDiv.appendChild(nameSpan);
        
        if (item.description) {
          var descSpan = document.createElement('small');
          descSpan.textContent = item.description;
          descSpan.style.display = 'block';
          descSpan.style.color = '#666';
          infoDiv.appendChild(descSpan);
        }
        
        itemDiv.appendChild(infoDiv);
        
        var withdrawBtn = document.createElement('button');
        withdrawBtn.className = 'nes-btn is-success';
        withdrawBtn.textContent = 'Withdraw';
        withdrawBtn.onclick = function() { Storage.withdrawItem(item.storage_id, item.name); };
        itemDiv.appendChild(withdrawBtn);
        
        container.appendChild(itemDiv);
      });
    } catch(e) {
      console.error(e);
    }
  }

  // Render deposit panel (items from inventory)
  function _renderDepositPanel() {
    try {
      var container = document.getElementById('storage-deposit-items');
      if (!container) return;
      
      container.innerHTML = '<p class="nes-text">Loading inventory...</p>';
      
      // Fetch player inventory
      var playerId = window.player_id || -1;
      var roomId = $("#room_id").text();
      
      if (playerId <= 0 || !window.api || typeof window.api.getItems !== 'function') {
        container.innerHTML = '<p class="nes-text is-error">Failed to load inventory.</p>';
        return;
      }
      
      window.api.getItems(playerId, roomId)
        .then(function(items) {
          container.innerHTML = '';
          
          if (!items || items.length === 0) {
            container.innerHTML = '<p class="nes-text">No items to deposit.</p>';
            return;
          }
          
          items.forEach(function(item) {
            var itemDiv = document.createElement('div');
            itemDiv.className = 'storage-item';
            itemDiv.style.cssText = 'display:flex;justify-content:space-between;align-items:center;margin:8px 0;padding:8px;border:2px solid #ccc;';
            
            var infoDiv = document.createElement('div');
            infoDiv.style.flex = '1';
            
            var nameSpan = document.createElement('span');
            nameSpan.className = 'nes-text';
            nameSpan.textContent = item.name;
            if (typeof item.rarity !== 'undefined') {
              var rarity = parseInt(item.rarity);
              var rarityClass = 'is-disabled'; // common
              if (rarity === 4) rarityClass = 'is-warning'; // legendary
              else if (rarity === 3) rarityClass = 'is-primary'; // epic
              else if (rarity === 2) rarityClass = 'is-success'; // rare
              else if (rarity === 1) rarityClass = ''; // uncommon
              nameSpan.className += ' ' + rarityClass;
            }
            infoDiv.appendChild(nameSpan);
            
            if (item.description) {
              var descSpan = document.createElement('small');
              descSpan.textContent = item.description;
              descSpan.style.display = 'block';
              descSpan.style.color = '#666';
              infoDiv.appendChild(descSpan);
            }
            
            // Show if equipped (cannot deposit equipped items)
            var isEquipped = item.equipped && parseInt(item.equipped) === 1;
            if (isEquipped) {
              var equippedSpan = document.createElement('span');
              equippedSpan.className = 'nes-text is-primary';
              equippedSpan.textContent = ' [Equipped]';
              equippedSpan.style.fontSize = '0.8em';
              infoDiv.appendChild(equippedSpan);
            }
            
            itemDiv.appendChild(infoDiv);
            
            var depositBtn = document.createElement('button');
            depositBtn.className = 'nes-btn is-warning';
            depositBtn.textContent = 'Deposit';
            if (isEquipped) {
              depositBtn.disabled = true;
              depositBtn.title = 'Unequip first';
            }
            depositBtn.onclick = function() { Storage.depositItem(item.id, item.name); };
            itemDiv.appendChild(depositBtn);
            
            container.appendChild(itemDiv);
          });
        })
        .catch(function(err) {
          console.error('Failed to load inventory:', err);
          container.innerHTML = '<p class="nes-text is-error">Failed to load inventory.</p>';
        });
    } catch(e) {
      console.error(e);
    }
  }

  // Deposit item into storage
  window.Storage.depositItem = function(itemDbId, itemName) {
    try {
      var playerName = $("#player").text();
      var roomId = $("#room_id").text();
      
      if (!confirm('Deposit ' + itemName + ' into storage?')) {
        return;
      }
      
      if (typeof window.playSound === 'function') {
        window.playSound(window.getImageUrl("click.mp3"));
      }
      
      window.api.depositItem(playerName, roomId, itemDbId)
        .then(function(resp) {
          console.log('deposit resp', resp);
          
          if (resp && resp.success) {
            // Update local storage data
            if (_storageData) {
              _storageData.slots_used = resp.slots_used || (_storageData.slots_used + 1);
              _storageData.slots_max = resp.slots_max || _storageData.slots_max;
            }
            
            // Refresh storage view
            Storage.open();
            
            // Refresh inventory
            if (window.Items && typeof window.Items.getItems === 'function') {
              window.Items.getItems();
            }
            
            if (typeof window.playSound === 'function') {
              window.playSound(window.getImageUrl("coin.mp3"));
            }
          } else {
            alert(resp.message || 'Deposit failed.');
          }
        })
        .catch(function(err) {
          console.error('deposit error: ' + err);
          alert('Deposit failed.');
        });
    } catch(e) {
      console.error(e);
    }
  };

  // Withdraw item from storage
  window.Storage.withdrawItem = function(storageId, itemName) {
    try {
      var playerName = $("#player").text();
      var roomId = $("#room_id").text();
      
      if (!confirm('Withdraw ' + itemName + ' from storage?')) {
        return;
      }
      
      if (typeof window.playSound === 'function') {
        window.playSound(window.getImageUrl("click.mp3"));
      }
      
      window.api.withdrawItem(playerName, roomId, storageId)
        .then(function(resp) {
          console.log('withdraw resp', resp);
          
          if (resp && resp.success) {
            // Refresh storage view
            Storage.open();
            
            // Refresh inventory
            if (window.Items && typeof window.Items.getItems === 'function') {
              window.Items.getItems();
            }
            
            if (typeof window.playSound === 'function') {
              window.playSound(window.getImageUrl("coin.mp3"));
            }
          } else {
            alert(resp.message || 'Withdraw failed.');
          }
        })
        .catch(function(err) {
          console.error('withdraw error: ' + err);
          alert('Withdraw failed.');
        });
    } catch(e) {
      console.error(e);
    }
  };

  // Update storage button visibility based on location type
  window.Storage.updateStorageButton = function() {
    try {
      var btn = document.getElementById('storage-btn');
      if (!btn) return;
      
      var playerName = $("#player").text();
      var roomId = $("#room_id").text();
      
      // Get current location type from gstats or stats
      var locationType = null;
      if (window.gstats && window.gstats.location_type) {
        locationType = window.gstats.location_type;
      } else if (window.stats && window.stats.location_type) {
        locationType = window.stats.location_type;
      }
      
      // Show storage button only at towns
      if (locationType === 'town') {
        btn.style.display = '';
      } else {
        btn.style.display = 'none';
      }
    } catch(e) {
      console.error(e);
    }
  };
})();
