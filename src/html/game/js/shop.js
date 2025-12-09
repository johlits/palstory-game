// Shop module: handle shop UI and transactions
(function () {
  if (!window.Shop) window.Shop = {};

  var _shopData = null;
  var _currentTab = 'buy'; // 'buy' or 'sell'

  // Open shop dialog
  window.Shop.open = function() {
    try {
      var playerName = $("#player").text();
      var roomId = $("#room_id").text();
      
      if (!window.api || typeof window.api.getShop !== 'function') {
        console.error('Shop API not available');
        return;
      }
      
      if (typeof window.playSound === 'function') {
        window.playSound(window.getImageUrl("click.mp3"));
      }
      
      // Fetch shop inventory
      window.api.getShop(playerName, roomId)
        .then(function(resp){
          console.log('shop resp', resp);
          
          if (resp && resp.success) {
            _shopData = resp;
            _renderShop();
            
            var dlg = document.getElementById('shop-dialog');
            if (dlg && typeof dlg.showModal === 'function') {
              dlg.showModal();
            }
          } else {
            alert(resp.message || 'Failed to load shop.');
          }
        })
        .catch(function(err){
          console.error('shop error: ' + err);
          alert('Failed to load shop.');
        });
    } catch(e) {
      console.error(e);
    }
  };

  // Close shop dialog
  window.Shop.close = function() {
    try {
      var dlg = document.getElementById('shop-dialog');
      if (dlg && typeof dlg.close === 'function') {
        dlg.close();
      }
    } catch(e) {
      console.error(e);
    }
  };

  // Switch tab (buy/sell)
  window.Shop.switchTab = function(tab) {
    _currentTab = tab;
    _renderShop();
  };

  // Render shop UI
  function _renderShop() {
    try {
      var buyTab = document.getElementById('shop-tab-buy');
      var sellTab = document.getElementById('shop-tab-sell');
      var buyPanel = document.getElementById('shop-panel-buy');
      var sellPanel = document.getElementById('shop-panel-sell');
      
      if (!buyTab || !sellTab || !buyPanel || !sellPanel) return;
      
      // Update tab styles
      if (_currentTab === 'buy') {
        buyTab.classList.add('is-primary');
        sellTab.classList.remove('is-primary');
        buyPanel.style.display = '';
        sellPanel.style.display = 'none';
        _renderBuyPanel();
      } else {
        buyTab.classList.remove('is-primary');
        sellTab.classList.add('is-primary');
        buyPanel.style.display = 'none';
        sellPanel.style.display = '';
        _renderSellPanel();
      }
    } catch(e) {
      console.error(e);
    }
  }

  // Render buy panel
  function _renderBuyPanel() {
    try {
      var container = document.getElementById('shop-buy-items');
      if (!container || !_shopData || !_shopData.items) return;
      
      container.innerHTML = '';
      
      if (_shopData.items.length === 0) {
        container.innerHTML = '<p class="nes-text">No items available.</p>';
        return;
      }
      
      // Group by category
      var categories = {};
      _shopData.items.forEach(function(item) {
        var cat = item.category || 'general';
        if (!categories[cat]) categories[cat] = [];
        categories[cat].push(item);
      });
      
      // Render each category
      Object.keys(categories).sort().forEach(function(cat) {
        var catDiv = document.createElement('div');
        catDiv.className = 'shop-category';
        
        var catTitle = document.createElement('h4');
        catTitle.className = 'nes-text is-primary';
        catTitle.textContent = cat.charAt(0).toUpperCase() + cat.slice(1);
        catDiv.appendChild(catTitle);
        
        categories[cat].forEach(function(item) {
          var itemDiv = document.createElement('div');
          itemDiv.className = 'shop-item';
          itemDiv.style.cssText = 'display:flex;justify-content:space-between;align-items:center;margin:8px 0;padding:8px;border:2px solid #ccc;';
          
          var infoDiv = document.createElement('div');
          infoDiv.style.flex = '1';
          
          var nameSpan = document.createElement('span');
          nameSpan.className = 'nes-text';
          nameSpan.textContent = item.name;
          if (typeof item.rarity !== 'undefined') {
            // Rarity: 0=common, 1=uncommon, 2=rare, 3=epic, 4=legendary
            var rarity = parseInt(item.rarity);
            var rarityClass = 'is-disabled'; // common
            if (rarity === 4) rarityClass = 'is-warning'; // legendary
            else if (rarity === 3) rarityClass = 'is-primary'; // epic
            else if (rarity === 2) rarityClass = 'is-success'; // rare
            else if (rarity === 1) rarityClass = ''; // uncommon (no special color)
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
          
          var priceDiv = document.createElement('div');
          priceDiv.style.cssText = 'display:flex;align-items:center;gap:8px;';
          
          var priceSpan = document.createElement('span');
          priceSpan.className = 'nes-text is-warning';
          priceSpan.textContent = item.price + ' gold';
          priceDiv.appendChild(priceSpan);
          
          var buyBtn = document.createElement('button');
          buyBtn.className = 'nes-btn is-success';
          buyBtn.textContent = 'Buy';
          buyBtn.onclick = function() { Shop.buyItem(item.item_id, item.name, item.price); };
          priceDiv.appendChild(buyBtn);
          
          itemDiv.appendChild(priceDiv);
          catDiv.appendChild(itemDiv);
        });
        
        container.appendChild(catDiv);
      });
    } catch(e) {
      console.error(e);
    }
  }

  // Render sell panel
  function _renderSellPanel() {
    try {
      var container = document.getElementById('shop-sell-items');
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
            container.innerHTML = '<p class="nes-text">No items to sell.</p>';
            return;
          }
          
          items.forEach(function(item) {
            var itemDiv = document.createElement('div');
            itemDiv.className = 'shop-item';
            itemDiv.style.cssText = 'display:flex;justify-content:space-between;align-items:center;margin:8px 0;padding:8px;border:2px solid #ccc;';
            
            var infoDiv = document.createElement('div');
            infoDiv.style.flex = '1';
            
            var nameSpan = document.createElement('span');
            nameSpan.className = 'nes-text';
            nameSpan.textContent = item.name;
            if (typeof item.rarity !== 'undefined') {
              // Rarity: 0=common, 1=uncommon, 2=rare, 3=epic, 4=legendary
              var rarity = parseInt(item.rarity);
              var rarityClass = 'is-disabled'; // common
              if (rarity === 4) rarityClass = 'is-warning'; // legendary
              else if (rarity === 3) rarityClass = 'is-primary'; // epic
              else if (rarity === 2) rarityClass = 'is-success'; // rare
              else if (rarity === 1) rarityClass = ''; // uncommon (no special color)
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
            
            // Show if equipped
            if (item.equipped && parseInt(item.equipped) === 1) {
              var equippedSpan = document.createElement('span');
              equippedSpan.className = 'nes-text is-primary';
              equippedSpan.textContent = ' [Equipped]';
              equippedSpan.style.fontSize = '0.8em';
              infoDiv.appendChild(equippedSpan);
            }
            
            itemDiv.appendChild(infoDiv);
            
            var sellBtn = document.createElement('button');
            sellBtn.className = 'nes-btn is-warning';
            sellBtn.textContent = 'Sell';
            sellBtn.onclick = function() { Shop.sellItem(item.id, item.name); };
            itemDiv.appendChild(sellBtn);
            
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

  // Buy item
  window.Shop.buyItem = function(itemId, itemName, price) {
    try {
      var playerName = $("#player").text();
      var roomId = $("#room_id").text();
      
      if (!confirm('Buy ' + itemName + ' for ' + price + ' gold?')) {
        return;
      }
      
      if (typeof window.playSound === 'function') {
        window.playSound(window.getImageUrl("click.mp3"));
      }
      
      window.api.buyItem(playerName, roomId, itemId)
        .then(function(resp) {
          console.log('buy resp', resp);
          
          if (resp && resp.success) {
            alert(resp.message || 'Purchase successful!');
            
            // Refresh player stats to update gold
            if (window.Players && typeof window.Players.getPlayers === 'function') {
              window.Players.getPlayers();
            }
            
            // Refresh inventory
            if (window.Items && typeof window.Items.getItems === 'function') {
              window.Items.getItems();
            }
            
            if (typeof window.playSound === 'function') {
              window.playSound(window.getImageUrl("coin.mp3"));
            }
          } else {
            alert(resp.message || 'Purchase failed.');
          }
        })
        .catch(function(err) {
          console.error('buy error: ' + err);
          alert('Purchase failed.');
        });
    } catch(e) {
      console.error(e);
    }
  };

  // Sell item
  window.Shop.sellItem = function(itemDbId, itemName) {
    try {
      var playerName = $("#player").text();
      var roomId = $("#room_id").text();
      
      if (!confirm('Sell ' + itemName + '?')) {
        return;
      }
      
      if (typeof window.playSound === 'function') {
        window.playSound(window.getImageUrl("click.mp3"));
      }
      
      window.api.sellItem(playerName, roomId, itemDbId)
        .then(function(resp) {
          console.log('sell resp', resp);
          
          if (resp && resp.success) {
            alert(resp.message || 'Sold successfully!');
            
            // Refresh player stats to update gold
            if (window.Players && typeof window.Players.getPlayers === 'function') {
              window.Players.getPlayers();
            }
            
            // Refresh sell panel
            _renderSellPanel();
            
            if (typeof window.playSound === 'function') {
              window.playSound(window.getImageUrl("coin.mp3"));
            }
          } else {
            alert(resp.message || 'Sale failed.');
          }
        })
        .catch(function(err) {
          console.error('sell error: ' + err);
          alert('Sale failed.');
        });
    } catch(e) {
      console.error(e);
    }
  };
})();
