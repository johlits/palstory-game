<?php 
// Show admin page by default; no redirect
require_once __DIR__ . '/game/config.php';
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
    <li><a href="<?= base_path('/admin.html') ?>">Admin Dashboard (HTML)</a></li>
    <li><a href="<?= base_path('/admin_players.php') ?>">Manage Players</a></li>
    <li><a href="<?= base_path('/admin_logs.php') ?>">View Logs</a></li>
    <li><a href="<?= base_path('/migration_runner.php') ?>">Run Migrations</a></li>
    <li><a href="<?= base_path('/health.php') ?>">Health Check</a></li>
    <li><a href="<?= base_path('/game/') ?>">Go to Game</a></li>
  </ul>
</body>
</html>