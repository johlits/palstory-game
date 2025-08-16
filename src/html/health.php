<?php
// health.php - simple health and DB connectivity check
header('Content-Type: application/json');

try {
  // Reuse environment like story/config.php
  $server = $_ENV['DB_SERVER'] ?? 'database';
  $user = $_ENV['DB_USERNAME'] ?? 'root';
  $pass = $_ENV['DB_PASSWORD'] ?? '';
  $dbn  = $_ENV['DB_NAME'] ?? 'story';

  $db = @mysqli_connect($server, $user, $pass);
  if (!$db) {
    echo json_encode([ 'status' => 'error', 'db' => 'connect_failed', 'error' => mysqli_connect_error() ]);
    exit;
  }
  @mysqli_select_db($db, $dbn);

  $ok = $db->query('SELECT 1 AS ok');
  if (!$ok) {
    echo json_encode([ 'status' => 'error', 'db' => 'query_failed', 'error' => $db->error ]);
    exit;
  }

  echo json_encode([
    'status' => 'ok',
    'app' => 'palstory',
    'db' => 'ok',
    'time' => date('c')
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([ 'status' => 'error', 'error' => $e->getMessage() ]);
}
