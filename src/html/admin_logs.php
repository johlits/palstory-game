<?php
// admin_logs.php - list recent telemetry logs with pagination/sorting and CSV export

$envToken = getenv('MIGRATE_TOKEN');
$reqToken = $_GET['token'] ?? '';
if (!$envToken || !hash_equals($envToken, $reqToken)) {
  header('Content-Type: application/json');
  http_response_code(401);
  echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
  exit;
}

// Legacy: limit; New: page_size + page
$limit = isset($_GET['page_size']) ? max(1, min(1000, intval($_GET['page_size']))) : (isset($_GET['limit']) ? max(1, min(1000, intval($_GET['limit']))) : 200);
$page  = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Optional filters
$action = isset($_GET['action']) ? trim($_GET['action']) : '';
$player = isset($_GET['player']) ? trim($_GET['player']) : '';
$roomId = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;
$sinceMin = isset($_GET['since_min']) ? max(0, intval($_GET['since_min'])) : 0; // minutes

// Sorting (whitelisted)
$sortBy = isset($_GET['sort_by']) ? trim($_GET['sort_by']) : 'id';
$sortDir = strtolower($_GET['sort_dir'] ?? 'desc');
$allowedSort = ['id' => 'id', 'ts' => 'ts', 'action' => 'action', 'player_name' => 'player_name', 'room_id' => 'room_id'];
if (!isset($allowedSort[$sortBy])) { $sortBy = 'id'; }
if (!in_array($sortDir, ['asc','desc'], true)) { $sortDir = 'desc'; }

$format = isset($_GET['format']) ? strtolower(trim($_GET['format'])) : 'json';

try {
  $server = $_ENV['DB_SERVER'] ?? 'database';
  $user = $_ENV['DB_USERNAME'] ?? 'root';
  $pass = $_ENV['DB_PASSWORD'] ?? '';
  $dbn  = $_ENV['DB_NAME'] ?? 'story';

  $db = mysqli_connect($server, $user, $pass, $dbn);
  if (!$db) { throw new Exception('DB connect failed: ' . mysqli_connect_error()); }

  // Build dynamic WHERE with prepared params
  $where = [];
  $types = '';
  $params = [];
  if ($action !== '') { $where[] = 'action = ?'; $types .= 's'; $params[] = $action; }
  if ($player !== '') { $where[] = 'player_name = ?'; $types .= 's'; $params[] = $player; }
  if ($roomId > 0) { $where[] = 'room_id = ?'; $types .= 'i'; $params[] = $roomId; }
  if ($sinceMin > 0) { $where[] = 'ts >= (NOW() - INTERVAL ? MINUTE)'; $types .= 'i'; $params[] = $sinceMin; }

  // Total count for pagination
  $countSql = 'SELECT COUNT(*) AS c FROM game_logs' . (count($where) ? (' WHERE ' . implode(' AND ', $where)) : '');
  $cq = $db->prepare($countSql);
  if ($cq === false) { throw new Exception('prepare count failed'); }
  if ($types !== '') {
    $bindc = [$types];
    foreach ($params as $i => $p) { $bindc[] = &$params[$i]; }
    call_user_func_array([$cq, 'bind_param'], $bindc);
  }
  $total = 0;
  if ($cq->execute()) {
    $cres = $cq->get_result();
    if ($row = $cres->fetch_assoc()) { $total = intval($row['c']); }
  }
  $cq->close();

  // Data query with sort + limit/offset
  $sql = 'SELECT id, ts, room_id, player_name, action, details FROM game_logs'
       . (count($where) ? (' WHERE ' . implode(' AND ', $where)) : '')
       . ' ORDER BY ' . $allowedSort[$sortBy] . ' ' . strtoupper($sortDir)
       . ' LIMIT ? OFFSET ?';
  $types2 = $types . 'ii';
  $params2 = $params;
  $params2[] = $limit;
  $params2[] = $offset;

  $q = $db->prepare($sql);
  if ($q === false) { throw new Exception('prepare failed'); }
  // Bind params dynamically
  $bind = [$types2];
  foreach ($params2 as $i => $p) { $bind[] = &$params2[$i]; }
  call_user_func_array([$q, 'bind_param'], $bind);
  $rows = [];
  if ($q && $q->execute()) {
    $res = $q->get_result();
    while ($row = mysqli_fetch_assoc($res)) {
      $rows[] = [
        'id' => (int)$row['id'],
        'ts' => $row['ts'],
        'room_id' => isset($row['room_id']) ? (int)$row['room_id'] : null,
        'player_name' => $row['player_name'],
        'action' => $row['action'],
        'details' => $row['details'],
      ];
    }
    $q->close();
  }

  if ($format === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="logs.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['id','ts','room_id','player_name','action','details']);
    foreach ($rows as $r) {
      fputcsv($out, [$r['id'], $r['ts'], $r['room_id'], $r['player_name'], $r['action'], $r['details']]);
    }
    fclose($out);
    exit;
  }

  header('Content-Type: application/json');
  echo json_encode([
    'status' => 'ok',
    'count' => count($rows),
    'total' => $total,
    'page' => $page,
    'page_size' => $limit,
    'sort_by' => $sortBy,
    'sort_dir' => $sortDir,
    'logs' => $rows,
  ]);
} catch (Throwable $e) {
  header('Content-Type: application/json');
  http_response_code(500);
  echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

