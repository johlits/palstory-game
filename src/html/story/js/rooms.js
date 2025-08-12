// Rooms module: wraps room-related actions
// Exposes: window.Rooms.getRoom, window.Rooms.createGame
(function () {
  if (!window.Rooms) window.Rooms = {};

  function getRoom(roomName) {
    return window.api.getRoom(roomName);
  }

  function createGame(name, expiration, regen) {
    return window.api.createRoom(name, expiration, regen);
  }

  window.Rooms.getRoom = getRoom;
  window.Rooms.createGame = createGame;

  // UI orchestration moved from game.js
  function _playClick() { try { if (typeof playSound==='function' && typeof getImageUrl==='function') playSound(getImageUrl('click.mp3')); } catch(_){} }

  function createGameUIFlow() {
    _playClick();
    try { $("#create_game_box").hide(); } catch(_){}
    var name = $("#create_game_room_name").val();
    $("#room").text(name);
    var expiration = $("#create_game_expiration").val();
    var regen = 0; // legacy
    try { console.log('creating game '+name+' (expiration '+expiration+')..'); } catch(_){}
    return createGame(name, expiration, regen)
      .then(function (response) {
        if (response && response[0] === 'ok') {
          return getRoomUIFlow();
        } else {
          try { console.log('error creating room'); } catch(_){}
          try { if (window.UI && window.UI.showCreateRoomBox) window.UI.showCreateRoomBox(); } catch(_){}
        }
      })
      .catch(function (err) {
        try { if (window.UI && window.UI.showCreateRoomBox) window.UI.showCreateRoomBox(); } catch(_){}
        try { console.error('error: ' + err); } catch(_){}
      });
  }

  function getRoomUIFlow() {
    try { console.log('getting room..'); } catch(_){}
    return getRoom($("#room").text())
      .then(function (response) {
        if (!response || response.length === 0) {
          try { if (window.UI && window.UI.showCreateRoomBox) window.UI.showCreateRoomBox(); } catch(_){}
          return;
        }
        if (response[0].name === "") {
          try { if (window.UI && window.UI.showCreateRoomBox) window.UI.showCreateRoomBox(); } catch(_){}
          return;
        }
        $("#room_id").text(response[0].id);
        $("#room_expire").text(response[0].expiration);
        $("#room_regen").text(response[0].regen);
        try { if (window.Players && window.Players.getPlayerUIFlow) return window.Players.getPlayerUIFlow(true); } catch(_){}
      })
      .catch(function (err) {
        try { console.error('error: ' + err); } catch(_){}
      });
  }

  window.Rooms.getRoomUIFlow = getRoomUIFlow;
  window.Rooms.createGameUIFlow = createGameUIFlow;
})();
