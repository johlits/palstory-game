<?php
session_start();
require_once "./config.php";

// Check if user is logged in
if (!isset($_SESSION['secret']) || ($_SESSION['secret'] != admin_game_hash() && $_SESSION['secret'] != super_admin_game_hash())) {
  header("Location: create.php");
  exit;
}
?>
<!DOCTYPE html>
<html>

<head>
  <title>Uploaded Files</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link rel="manifest" href="assets/manifest.json" />

  <link rel="apple-touch-icon" sizes="512x512" href="assets/android/android-launchericon-512-512.png">
  <link rel="apple-touch-icon" sizes="192x192" href="assets/android/android-launchericon-192-192.png">
  <link rel="apple-touch-icon" sizes="180x180" href="assets/ios/180.png">
  <link rel="icon" type="image/png" sizes="32x32" href="assets/ios/32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="assets/ios/16.png">
  <link rel="shortcut icon" href="assets/favicon.ico">

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/styles.css">
</head>

<body id="top" class="create-page">

<div class="create-toplink"><a class="ylink" href="#top">Top</a></div>

<div class="create-header">
  <div><a href="create.php">← Back to Create</a></div>
</div>

<div class="center-abs box" style="max-width: 90vw; max-height: 80vh; overflow-y: auto;">
  <h1>Uploaded Images</h1>
  <p>All uploaded images for game resources</p>
  
  <div style="overflow-x: auto;">
    <table style="min-width: 600px; width: 100%;">
      <tr>
        <th style="width: 80px;">Preview</th>
        <th>File Name</th>
        <th style="width: 100px;">Type</th>
        <th style="width: 80px;">Size</th>
      </tr>

    <?
    $log_directory = '../uploads';
    $results_array = array();

    if (is_dir($log_directory)) {
      if ($handle = opendir($log_directory)) {
        while (($file = readdir($handle)) !== FALSE) {
          $results_array[] = $file;
        }
        closedir($handle);
      }
    }

    // Sort files alphabetically
    sort($results_array);

    // Output image files only
    for ($i = 0; $i < count($results_array); $i++) {
      if ($i > 1) { // Skip . and .. directories
        $file_path = $log_directory . '/' . $results_array[$i];
        $file_ext = strtolower(pathinfo($results_array[$i], PATHINFO_EXTENSION));

        // Skip non-image files
        if (!in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])) {
          continue;
        }

        $file_size = filesize($file_path);
        $file_type = 'Image';
        $preview = '<a href="' . getImageUrl($results_array[$i]) . '" target="_blank"><img src="' . getImageUrl($results_array[$i]) . '" width="50" height="50" style="object-fit: cover;"></a>';
        
        // Format file size
        if ($file_size < 1024) {
          $size_display = $file_size . ' B';
        } elseif ($file_size < 1024 * 1024) {
          $size_display = round($file_size / 1024, 1) . ' KB';
        } else {
          $size_display = round($file_size / (1024 * 1024), 1) . ' MB';
        }
        
        echo '<tr>';
        echo '<td>' . $preview . '</td>';
        echo '<td><a href="' . getImageUrl($results_array[$i]) . '" target="_blank">' . $results_array[$i] . '</a></td>';
        echo '<td>' . $file_type . '</td>';
        echo '<td>' . $size_display . '</td>';
        echo '</tr>';
      }
    }
    ?>
    </table>
  </div>
  
  <?
  if (count($results_array) <= 2) {
    echo '<p class="muted">No files uploaded yet.</p>';
  }
  ?>
  
  <div class="mt-16">
    <a class="nes-btn" href="create.php">← Back to Create</a>
  </div>
</div>

</body>

</html>
