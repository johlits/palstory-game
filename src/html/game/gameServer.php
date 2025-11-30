<?php
// Suppress warnings to prevent breaking JSON responses
error_reporting(E_ERROR | E_PARSE);

// CORS configuration - restrict to allowed origins
$allowed_origins = [
    'http://localhost',
    'http://localhost:8080',
    'http://127.0.0.1',
    'http://127.0.0.1:8080',
    'https://palplanner.com',
    'https://www.palplanner.com'
];

$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: " . $origin);
} else {
    // Fallback for development - allow if DISABLE_CORS env is set
    if (getenv('DISABLE_CORS') || (isset($_ENV['DISABLE_CORS']) && $_ENV['DISABLE_CORS'])) {
        header("Access-Control-Allow-Origin: *");
    }
}
header("Access-Control-Allow-Headers: Content-Type, X-CSRF-Token");
header("Access-Control-Allow-Credentials: true");
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
require_once __DIR__ . '/server/skills.php';
require_once __DIR__ . '/server/shop.php';
require_once __DIR__ . '/server/storage.php';

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
} else if (isset($data['get_all_monsters'])) {
  echo json_encode(getAllMonsters($db, $data));
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
} else if (isset($data['select_job'])) {
  echo json_encode(selectJob($db, $data));
} else if (isset($data['unlock_skill'])) {
  echo json_encode(unlockSkill($db, $data));
} else if (isset($data['get_jobs'])) {
  echo json_encode(getJobs($db));
} else if (isset($data['get_skills'])) {
  echo json_encode(getSkills($db));
} else if (isset($data['rest_player'])) {
  echo json_encode(restAtLocation($db, $data));
} else if (isset($data['set_respawn'])) {
  echo json_encode(setRespawnPoint($db, $data));
} else if (isset($data['get_shop'])) {
  echo json_encode(getShopInventory($db, $data));
} else if (isset($data['buy_item'])) {
  echo json_encode(buyItem($db, $data));
} else if (isset($data['sell_item'])) {
  echo json_encode(sellItem($db, $data));
} else if (isset($data['get_storage'])) {
  echo json_encode(getStorage($db, $data));
} else if (isset($data['deposit_item'])) {
  echo json_encode(depositItem($db, $data));
} else if (isset($data['withdraw_item'])) {
  echo json_encode(withdrawItem($db, $data));
}

mysqli_close($db);

?>
