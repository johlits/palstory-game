<?php
// admin_players.php - simple admin endpoint to list players and last_seen
header('Content-Type: application/json');

$envToken = getenv('MIGRATE_TOKEN');
$reqToken = $_GET['token'] ?? '';
if (!$envToken || !hash_equals($envToken, $reqToken)) {
  http_response_code(401);
  echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
  exit;
}

try {
  $server = $_ENV['DB_SERVER'] ?? 'database';
  $user = $_ENV['DB_USERNAME'] ?? 'root';
  $pass = $_ENV['DB_PASSWORD'] ?? '';
  $dbn  = $_ENV['DB_NAME'] ?? 'story';

  $db = mysqli_connect($server, $user, $pass, $dbn);
  if (!$db) { throw new Exception('DB connect failed: ' . mysqli_connect_error()); }

  $q = $db->prepare("SELECT id, name, room_id, x, y, last_seen FROM game_players ORDER BY last_seen DESC");
  $players = [];
  if ($q && $q->execute()) {
    $res = $q->get_result();
    while ($row = mysqli_fetch_assoc($res)) {
      $players[] = [
        'id' => (int)$row['id'],
        'name' => $row['name'],
        'room_id' => (int)$row['room_id'],
        'x' => (int)$row['x'],
        'y' => (int)$row['y'],
        'last_seen' => $row['last_seen'],
      ];
    }
    $q->close();
  }

  echo json_encode([
    'status' => 'ok',
    'players' => $players,
    'count' => count($players),
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
