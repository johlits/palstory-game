<?
require_once "./config.php";
  $bg = "riverwood.jpg";
  $srlocs = $db->prepare("SELECT * 
FROM resources_locations");
  if ($srlocs->execute()) {
    $srlocr = $srlocs->get_result();
    $srlocrc = mysqli_num_rows($srlocr);

    if ($srlocrc > 0) {
      $rloc = mysqli_fetch_all($srlocr)[rand(0, $srlocrc - 1)];
      $bg = $rloc[2];
    }
  }
  $srlocs->close();
?>
<!DOCTYPE html>
<html>

<head>
  <title>PalStory</title>
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">

  <link rel="manifest" href="assets/manifest.json" />

  <link rel="apple-touch-icon" sizes="512x512" href="assets/android/android-launchericon-512-512.png">
  <link rel="apple-touch-icon" sizes="192x192" href="assets/android/android-launchericon-192-192.png">
  <link rel="apple-touch-icon" sizes="180x180" href="assets/ios/180.png">
  <link rel="icon" type="image/png" sizes="32x32" href="assets/ios/32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="assets/ios/16.png">
  <link rel="shortcut icon" href="assets/favicon.ico">
  
  <!-- Fonts: Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  
  <!-- Modern UI styles -->
  <link rel="stylesheet" href="css/styles.css">
  <link rel="stylesheet" href="css/effects.css">
  
  <style>
    #rotate-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.9);
      color: #fff;
      display: none;
      align-items: center;
      justify-content: center;
      text-align: center;
      z-index: 9999;
      padding: 1rem;
    }
    #rotate-overlay-inner {
      max-width: 22rem;
      font-size: 0.95rem;
      line-height: 1.4;
    }
    #rotate-overlay h2 {
      margin-top: 0;
      margin-bottom: 0.5rem;
      font-size: 1.2rem;
    }
  </style>
</head>

<body class="start-page" style="--start-bg: url('<? echo getImageUrl($bg); ?>')">

  <div id="rotate-overlay">
    <div id="rotate-overlay-inner">
      <h2>Rotate your device</h2>
      <p>
        For the best PalStory experience, please rotate your device to
        <strong>landscape</strong> before starting the game.
      </p>
    </div>
  </div>

  <!-- Decorative background overlays -->
  <div id="bg-particles-back" aria-hidden="true"></div>
  <div id="bg-sunshafts-a" aria-hidden="true"></div>
  <div id="bg-sunshafts-b" aria-hidden="true"></div>
  <div id="bg-sunshafts-c" aria-hidden="true"></div>
  <div id="bg-particles" aria-hidden="true"></div>
  <div id="bg-leaves" aria-hidden="true"></div>
  <div id="bg-particles-large" aria-hidden="true"></div>

  <!-- Header -->
  <header class="p-header p-panel">
    <nav class="p-nav">
      <a class="p-link" href="<?= base_path('/game/create.php') ?>">Admin</a>
      <a class="p-link" href="<?= base_path('/game/credits.html') ?>">Credits</a>
    </nav>
    <?php $headerItems = fetch_header_items(); ?>
    <div class="p-header-center" id="header_center">
      <?php if (!empty($headerItems)) { ?>
        <?php foreach ($headerItems as $i => $item) { ?>
          <span class="p-header-item"><?php echo $item; ?></span>
          <?php if ($i < count($headerItems) - 1) { ?><span class="p-header-sep">•</span><?php } ?>
        <?php } ?>
      <?php } ?>
    </div>
    <div class="theme-toggle">
      <span id="resource_info"></span>
    </div>
  </header>

  <div id="screen_1" class="p-center">
    <div class="p-card p-panel">
      <div id="header_logo">
        <img class="p-logo" src="<?echo getImageUrl('palstory_logo.png');?>" alt="PalStory" width="162" height="83">
        <p class="p-sub">Create a room, pick a name, and start your adventure.</p>
      </div>
      <div id="header_text">
        <h1 class="p-title">PalStory</h1>
      </div>

      <div class="p-form">
        <label class="p-label" for="room_name">Game name</label>
        <? if (isset($_GET["room"])) { ?>
          <input class="p-input" type="text" id="room_name" name="room_name" value="<? echo $_GET["room"]; ?>">
          <label class="p-label" for="player_name">Player name</label>
          <input class="p-input" type="text" id="player_name" name="player_name" value="<? echo isset($_GET["player"]) ? $_GET["player"] : ""; ?>" autofocus>
        <? } else { ?>
          <input class="p-input" type="text" id="room_name" name="room_name" autofocus>
          <label class="p-label" for="player_name">Player name</label>
          <input class="p-input" type="text" id="player_name" name="player_name" value="<? echo isset($_GET["player"]) ? $_GET["player"] : ""; ?>">
        <? } ?>
      </div>

      <div class="p-actions">
        <button id="login_btn" class="p-btn" onclick="login()">Play</button>
        <a class="p-demo" href="<?= base_path('/game/board.php') ?>?room=room<? echo rand(0,999); ?>&player=user<? echo rand(0,999); ?>">Try a demo room</a>
      </div>
    </div>
  </div>


</body>

</html>

<!-- jQuery via CDN with local fallback (upgraded to 3.7.1 for security) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script>window.jQuery || document.write('<script src="js/vendor/jquery-3.7.1.min.js"><\\/script>')</script>
<script>
  var BASE_PATH = <?= json_encode(base_path('')) ?>;
  var PALSTORY_PENDING_START = false;

  function isLandscape() {
    return window.innerWidth > window.innerHeight;
  }

  function updateRotateOverlay() {
    var overlay = document.getElementById('rotate-overlay');
    if (!overlay) return;
    if (isLandscape()) {
      overlay.style.display = 'none';
    } else {
      overlay.style.display = 'flex';
    }
  }

  function tryEnterGame() {
    var room = $("#room_name").val();
    var player = $("#player_name").val();
    if (!room || !player) {
      return;
    }
    if (!isLandscape()) {
      // Show overlay and wait for rotation; we remember intent via PALSTORY_PENDING_START
      updateRotateOverlay();
      return;
    }
    window.location = BASE_PATH + "/game/board.php?room=" + room + "&player=" + player;
  }

  function login() {
    PALSTORY_PENDING_START = true;
    tryEnterGame();
  }

  document.getElementById("room_name").addEventListener("keypress", function (event) {
    if (event.key === "Enter") {
      event.preventDefault();
      document.getElementById('player_name').focus();
    }
  });

  document.getElementById("player_name").addEventListener("keypress", function (event) {
    if (event.key === "Enter") {
      event.preventDefault();
      document.getElementById('login_btn').click();
    }
  });

  document.getElementById("login_btn").addEventListener("keypress", function (event) {
    if (event.key === "Enter") {
      event.preventDefault();
      document.getElementById('login_btn').click();
    }
  });

  $(window).resize(function() {
    if ($(this).height() < 360) {
      $('#header_logo').hide();
      $('#header_text').hide();
    }
    else if ($(this).height() < 420) {
      $('#header_logo').hide();
      $('#header_text').show();
    } else {
      $('#header_text').hide();
      $('#header_logo').show();
    }
  });

  $(document).ready(function(){

    if ($(document).height() < 360) {
      $('#header_logo').hide();
      $('#header_text').hide();
    }
    else if ($(document).height() < 420) {
      $('#header_logo').hide();
      $('#header_text').show();
    } else {
      $('#header_text').hide();
      $('#header_logo').show();
    }

    $.ajax({
    url: "gameServer.php",
    type: "get",
    data: "get_resource_info=1",
    dataType: "json",
    success: function (response, status, http) {
      console.log(response);
      if (response.length === 3) {
        $("#resource_info").html("I/M/L: " + response[0][0] + ", " + response[1][0] + ", " + response[2][0]);
      } 
    },
    error: function (http, status, error) {
      console.error("error: " + error);
    },
  });

    // Pure CSS implementation with optimized performance

  }); 

  // Orientation gate: only allow entering the game in landscape
  updateRotateOverlay();
  window.addEventListener('resize', function() {
    updateRotateOverlay();
    if (PALSTORY_PENDING_START && isLandscape()) {
      tryEnterGame();
    }
  });
  window.addEventListener('orientationchange', function() {
    updateRotateOverlay();
    if (PALSTORY_PENDING_START && isLandscape()) {
      tryEnterGame();
    }
  });

</script>