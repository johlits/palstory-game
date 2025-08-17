<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');

require_once "./config.php";

// Load modularized server code
require_once __DIR__ . '/server/utils.php';
require_once __DIR__ . '/server/rooms.php';
require_once __DIR__ . '/server/players.php';
require_once __DIR__ . '/server/resources.php';
require_once __DIR__ . '/server/locations.php';
require_once __DIR__ . '/server/monsters.php';
require_once __DIR__ . '/server/movement.php';
require_once __DIR__ . '/server/combat.php';

// API

$data = $_REQUEST;
$monsterSpawnRate = 50;
$itemDropRate = 50;

if (isset($data['get_room'])) {
  echo json_encode(getRoom($db, $data));
} else if (isset($data['get_music'])) {
  echo json_encode(getMusic());
} else if (isset($data['get_players'])) {
  echo json_encode(getPlayers($db, $data));
} else if (isset($data['purge_rooms'])) {
  echo json_encode(purgeRooms($db, $data));
} else if (isset($data['get_player'])) {
  echo json_encode(getPlayer($db, $data));
} else if (isset($data['create_room'])) {
  echo json_encode(createRoom($db, $data));
} else if (isset($data['create_player'])) {
  echo json_encode(createPlayer($db, $data));
} else if (isset($data['get_location'])) {
  echo json_encode(getLocation($db, $data));
} else if (isset($data['get_items'])) {
  echo json_encode(getItems($db, $data));
} else if (isset($data['get_monster'])) {
  echo json_encode(getMonster($db, $data));
} else if (isset($data['get_all_locations'])) {
  echo json_encode(getAllLocations($db, $data));
} else if (isset($data['drop_item'])) {
  echo json_encode(dropItem($db, $data));
} else if (isset($data['unequip_item'])) {
  echo json_encode(unequipItem($db, $data));
} else if (isset($data['equip_item'])) {
  echo json_encode(equipItem($db, $data));
} else if (isset($data['fight_monster'])) {
  echo json_encode(fightMonster($db, $data, $itemDropRate));
} else if (isset($data['move_player'])) {
  echo json_encode(movePlayer($db, $data, $itemDropRate, $monsterSpawnRate));
} else if (isset($data['get_resource_info'])) {
  echo json_encode(getResourceInfo($db));
} else if (isset($data['gather_resource'])) {
  echo json_encode(gatherResource($db, $data, $itemDropRate));
} else if (isset($data['ping_player'])) {
  // Lightweight heartbeat to auto-save last_seen without mutating gameplay state
  $player_name = clean($data['ping_player']);
  $room_id = intval(clean($data['room_id'] ?? 0));
  touchPlayer($db, $room_id, $player_name);
  // Telemetry: log ping (best-effort)
  try {
    $details = json_encode([ 'src' => 'client_heartbeat' ]);
    if ($details === false) { $details = '{"src":"client_heartbeat"}'; }
    if ($lg = $db->prepare("INSERT INTO game_logs (room_id, player_name, action, details) VALUES (?, ?, 'ping', ?)")) {
      $lg->bind_param("iss", $room_id, $player_name, $details);
      $lg->execute();
      $lg->close();
    }
  } catch (Throwable $_) { }
  echo json_encode(["ok"]);
}

mysqli_close($db);

?>
