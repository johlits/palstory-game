(function () {
  // Simple API layer to wrap all server calls in one place
  // Exposed globally as window.api
  var BASE_URL = 'gameServer.php';

  function _req(params, options) {
    return new Promise(function (resolve, reject) {
      $.ajax({
        url: BASE_URL,
        type: 'get',
        data: params,
        dataType: (options && options.dataType) || 'json',
        success: function (resp) { resolve(resp); },
        error: function (http, status, error) { reject(error || status || 'error'); }
      });
    });
  }

  var api = {
    // Combat
    fightMonster: function (playerName, roomId, skill) {
      var params = { fight_monster: playerName, room_id: roomId };
      if (typeof skill === 'string' && skill) params.skill = skill;
      return _req(params);
    },

    // Movement
    movePlayer: function (playerName, roomId, dir) {
      return _req({ move_player: playerName, room_id: roomId, dir: dir });
    },

    // Locations
    getAllLocations: function (roomId) {
      return _req({ get_all_locations: roomId });
    },
    getLocation: function (roomId, x, y) {
      return _req({ get_location: roomId, x: x, y: y });
    },
    getRoom: function (roomName) {
      return _req({ get_room: roomName });
    },

    // Monsters
    getMonster: function (roomId, x, y) {
      return _req({ get_monster: roomId, x: x, y: y });
    },

    // Music
    getMusic: function () {
      return _req({ get_music: 1 });
    },

    // Players
    createPlayer: function (name, roomId, portraitId) {
      return _req({ create_player: name, room_id: roomId, player_portrait: portraitId });
    },
    getPlayer: function (name, roomId) {
      return _req({ get_player: name, room_id: roomId });
    },
    getAllPlayers: function (roomId) {
      // Use legacy key expected by backend
      return _req({ get_players: roomId });
    },

    // Items
    getItems: function (playerId, roomId) {
      return _req({ get_items: playerId, room_id: roomId });
    },
    unequipItem: function (itemId, playerId) {
      return _req({ unequip_item: itemId, player_id: playerId });
    },
    equipItem: function (itemId, playerId) {
      return _req({ equip_item: itemId, player_id: playerId });
    },
    dropItem: function (itemId, playerId) {
      return _req({ drop_item: itemId, player_id: playerId });
    },
    // Gathering
    gatherResource: function(playerName, roomId) {
      return _req({ gather_resource: playerName, room_id: roomId });
    },
    // Rooms
    createRoom: function (name, expiration, regen) {
      return _req({ create_room: name, expiration: expiration, regen: regen });
    },

    // Heartbeat
    pingPlayer: function(playerName, roomId) {
      return _req({ ping_player: playerName, room_id: roomId });
    }
  };

  window.api = api;
})();
