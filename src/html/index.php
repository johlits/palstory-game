<?php
// If ?admin is not present, redirect to /game/
if (!isset($_GET['admin'])) {
    // Use absolute path; adjust to '/game' if you prefer no trailing slash
    header('Location: /story/game/');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Palstory Admin</title>
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; margin: 2rem; line-height: 1.5; }
    h1 { margin-bottom: 0.5rem; }
    .note { color: #666; margin-bottom: 1rem; }
    ul { padding-left: 1.25rem; }
    a { color: #166088; text-decoration: none; }
    a:hover { text-decoration: underline; }
  </style>
</head>
<body>
  <h1>Palstory Admin</h1>
  <div class="note">You’re seeing this page because the <code>?admin</code> parameter is present.</div>

  <ul>
    <li><a href="admin.html">Admin Dashboard (HTML)</a></li>
    <li><a href="admin_players.php">Manage Players</a></li>
    <li><a href="admin_logs.php">View Logs</a></li>
    <li><a href="migration_runner.php">Run Migrations</a></li>
    <li><a href="health.php">Health Check</a></li>
    <li><a href="/story/game/">Go to Game</a></li>
  </ul>
</body>
</html>