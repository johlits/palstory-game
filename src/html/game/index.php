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
  <meta name="viewport" content="width=device-width, initial-scale=1">

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

  <style>
    /* Ensure the start page background image and stacking context */
    body.start-page {
      position: relative;
      min-height: 100vh;
      background-image: var(--start-bg);
      background-size: cover;
      background-position: center center;
      background-repeat: no-repeat;
      overflow-x: hidden; /* avoid horizontal spill */
      overflow-y: auto;   /* allow vertical scroll if needed */
    }

    /* Place content above decorative backgrounds */
    #screen_1 { position: relative; z-index: 3; }

    /* Sun shafts: individual layers with independent animations */
    #bg-sunshafts-a {
      position: fixed;
      inset: -10vmax;
      z-index: 0;
      pointer-events: none;
      background-size: 140vmax 140vmax;
      background-position: 0% 0%;
      filter: brightness(1);
      will-change: background-position, opacity, filter;
      background: repeating-linear-gradient(
        116deg,
        rgba(255, 244, 214, 0.24) 0px,
        rgba(255, 244, 214, 0.24) 115px,
        rgba(255, 244, 214, 0.00) 210px,
        rgba(255, 244, 214, 0.00) 430px
      );
      opacity: .75;
      animation: shaftsScrollA 23s linear infinite, shaftsPulseA 13s ease-in-out infinite;
      animation-delay: 0s, 0s;
    }

    #bg-sunshafts-b {
      position: fixed;
      inset: -10vmax;
      z-index: 0;
      pointer-events: none;
      background-size: 140vmax 140vmax;
      background-position: 0% 0%;
      filter: brightness(1);
      will-change: background-position, opacity, filter;
      background: repeating-linear-gradient(
        119deg,
        rgba(255, 255, 255, 0.12) 55px,
        rgba(255, 255, 255, 0.12) 185px,
        rgba(255, 255, 255, 0.00) 275px,
        rgba(255, 255, 255, 0.00) 455px
      );
      opacity: .55;
      animation: shaftsScrollB 37s linear infinite, shaftsPulseB 19s ease-in-out infinite;
      animation-delay: 3s, 7s;
    }

    #bg-sunshafts-c {
      position: fixed;
      inset: -10vmax;
      z-index: 0;
      pointer-events: none;
      background-size: 140vmax 140vmax;
      background-position: 0% 0%;
      filter: brightness(1);
      will-change: background-position, opacity, filter;
      background: repeating-linear-gradient(
        113deg,
        rgba(255, 253, 240, 0.14) 25px,
        rgba(255, 253, 240, 0.14) 165px,
        rgba(255, 253, 240, 0.00) 255px,
        rgba(255, 253, 240, 0.00) 475px
      );
      opacity: .45;
      animation: shaftsScrollC 41s linear infinite, shaftsPulseC 29s ease-in-out infinite;
      animation-delay: 8s, 14s;
    }

    /* Independent scroll animations for each layer */

    @keyframes shaftsScrollA {
      0%   { background-position: 0% 0%; }
      30%  { background-position: 3% 1%; }
      70%  { background-position: 5% 3%; }
      100% { background-position: 0% 0%; }
    }

    @keyframes shaftsScrollB {
      0%   { background-position: 0% 0%; }
      40%  { background-position: 2% 2%; }
      80%  { background-position: 4% 1%; }
      100% { background-position: 0% 0%; }
    }

    @keyframes shaftsScrollC {
      0%   { background-position: 0% 0%; }
      20%  { background-position: 1% 1%; }
      60%  { background-position: 3% 2%; }
      100% { background-position: 0% 0%; }
    }

    /* Ultra-smooth pulse animations with minimal brightness changes */
    @keyframes shaftsPulseA {
      0%, 100% { opacity: 0.35; filter: brightness(0.98); }
      50%      { opacity: 0.75; filter: brightness(1.12); }
    }

    @keyframes shaftsPulseB {
      0%, 100% { opacity: 0.25; filter: brightness(0.96); }
      50%      { opacity: 0.65; filter: brightness(1.08); }
    }

    @keyframes shaftsPulseC {
      0%, 100% { opacity: 0.3; filter: brightness(0.97); }
      50%      { opacity: 0.7; filter: brightness(1.1); }
    }

    /* Leaf shade removed */

    /* Respect users who prefer reduced motion */
    @media (prefers-reduced-motion: reduce) {
      #bg-sunshafts, #bg-sunshafts-a, #bg-sunshafts-b, #bg-sunshafts-c { animation: none; }
    }

    /* Layout tweaks specific to the start page */
    .start-page .p-header {
      position: fixed; /* ensure fixed header behavior */
      inset: 16px 16px auto 16px; /* respect original spacing */
      left: 16px; right: 16px; /* explicitly constrain within viewport */
      width: auto; max-width: calc(100vw - 32px); margin: 0; box-sizing: border-box;
      overflow-x: hidden; /* avoid any right-side spill */
    }

    .start-page #screen_1 .p-card.p-panel {
      margin-top: 20px; /* raise login box more */
    }

    /* Dust particles removed */

    /* Fine dust specks removed */
  </style>
  
</head>

<body class="start-page" style="--start-bg: url('<? echo getImageUrl($bg); ?>')">

  <!-- Decorative background overlays -->
  <div id="bg-sunshafts-a" aria-hidden="true"></div>
  <div id="bg-sunshafts-b" aria-hidden="true"></div>
  <div id="bg-sunshafts-c" aria-hidden="true"></div>

  <!-- Header -->
  <header class="p-header p-panel">
    <nav class="p-nav">
      <a class="p-link" href="/story/game/create.php">Admin</a>
      <a class="p-link" href="/story/game/credits.html">Credits</a>
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
        <a class="p-demo" href="/story/game/board.php?room=room<? echo rand(0,999); ?>&player=user<? echo rand(0,999); ?>">Try a demo room</a>
      </div>
    </div>
  </div>


</body>

</html>

<script src="js/vendor/jquery-2.2.4.min.js"></script>
<script>
  function login() {
    window.location = "/story/game/board.php?room=" + $("#room_name").val() + "&player=" + $("#player_name").val();
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

</script>