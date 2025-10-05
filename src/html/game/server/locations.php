<?php

function getLocation($db, $data)
{
  $room_id = intval(clean($data['get_location']));
  $x = intval(clean($data['x']));
  $y = intval(clean($data['y']));

  $ss = $db->prepare("SELECT game_locations.id AS game_loc_id,
                             game_locations.room_id,
                             game_locations.x,
                             game_locations.y,
                             game_locations.stats AS gstats,
                             game_locations.resource_id,
                             resources_locations.name,
                             resources_locations.description,
                             resources_locations.image,
                             resources_locations.stats AS stats,
                             resources_locations.location_type
				FROM game_locations INNER JOIN resources_locations ON game_locations.resource_id = resources_locations.id 
				WHERE room_id = ? AND x = ? AND y = ?");
  $ss->bind_param("iii", $room_id, $x, $y);

  $arr = array();
  if ($ss->execute()) {
    $r = $ss->get_result();
    $rc = mysqli_num_rows($r);
    if ($rc > 0) {
      while ($row = mysqli_fetch_array($r)) {
        array_push($arr, $row);
        break;
      }
    }
  }
  $ss->close();
  return $arr;
}

// MVP Non-combat gather: pick a monster from location spawns and use its drops as gatherable resources
function gatherResource($db, $data, $itemDropRate)
{
  $player_name = clean($data['gather_resource']);
  $room_id = intval(clean($data['room_id']));

  $resp = array();

  // Basic anti-spam: limit to ~3 gathers/sec logged per player
  try {
    if ($rl = $db->prepare("SELECT COUNT(*) AS c FROM game_logs WHERE player_name = ? AND action = 'gather' AND ts > (NOW() - INTERVAL 1 SECOND)")) {
      $rl->bind_param("s", $player_name);
      if ($rl->execute()) {
        $r = $rl->get_result();
        if ($row = mysqli_fetch_array($r)) {
          if (intval($row['c']) >= 3) { array_push($resp, "cooldown"); $rl->close(); return $resp; }
        }
      }
      $rl->close();
    }
  } catch (Throwable $_) { }

  // Load player to get id and position
  $sp = $db->prepare("SELECT id, x, y FROM game_players WHERE room_id = ? AND name = ?");
  $sp->bind_param("is", $room_id, $player_name);
  $player_id = -1; $x = 0; $y = 0;
  if ($sp->execute()) {
    $pr = $sp->get_result();
    if ($prow = mysqli_fetch_array($pr)) { $player_id = intval($prow['id']); $x = intval($prow['x']); $y = intval($prow['y']); }
  }
  $sp->close();
  if ($player_id <= 0) { array_push($resp, "err"); return $resp; }

  // If a monster is present on this tile, disallow gathering (must fight first)
  $cm = $db->prepare("SELECT 1 FROM game_monsters WHERE room_id = ? AND x = ? AND y = ? LIMIT 1");
  $cm->bind_param("iii", $room_id, $x, $y);
  if ($cm->execute()) {
    $cmr = $cm->get_result();
    if (mysqli_num_rows($cmr) > 0) { $cm->close(); array_push($resp, "blocked_by_monster"); return $resp; }
  }
  $cm->close();

  // Ensure location exists and fetch its resource stats (expects spawns=monsterA,monsterB,...)
  // Use LEFT JOIN so we can detect tiles with missing/invalid resource and treat as no_resources instead of err
  $sl = $db->prepare("SELECT game_locations.stats AS gstats, resources_locations.stats AS stats FROM game_locations LEFT JOIN resources_locations ON game_locations.resource_id = resources_locations.id WHERE game_locations.room_id = ? AND x = ? AND y = ?");
  $sl->bind_param("iii", $room_id, $x, $y);
  $locstats = '';
  $gstats = '';
  if ($sl->execute()) {
    $lr = $sl->get_result();
    if ($lrow = mysqli_fetch_array($lr)) { $locstats = $lrow['stats']; $gstats = $lrow['gstats']; }
  }
  $sl->close();
  if ($locstats === '' || $locstats === null) { array_push($resp, "no_resources"); return $resp; }

  // Require gatherable flag on the tile
  $gatherable = false;
  $gparts = explode(';', $gstats . ';');
  for ($i=0; $i<count($gparts); $i++) { if (str_starts_with($gparts[$i], 'gather=')) { $gatherable = (intval(explode('=', $gparts[$i])[1]) === 1); break; } }
  if (!$gatherable) { array_push($resp, "no_gather_here"); return $resp; }

  // Parse spawns list
  $spawns = array();
  $parts = explode(';', verifyLocationStats($locstats));
  for ($i = 0; $i < count($parts); $i++) {
    if (str_starts_with($parts[$i], 'spawns=')) {
      $spawns = array_map('trim', explode(',', explode('=', $parts[$i])[1]));
      break;
    }
  }
  if (count($spawns) == 0) { array_push($resp, "no_resources"); return $resp; }

  // Choose a random monster and use its drops list as gatherable items
  $monsterName = $spawns[rand(0, count($spawns) - 1)];
  $sm = $db->prepare("SELECT stats FROM resources_monsters WHERE name = ? LIMIT 1");
  $sm->bind_param("s", $monsterName);
  $drops = '';
  if ($sm->execute()) {
    $mr = $sm->get_result();
    if ($mrow = mysqli_fetch_array($mr)) {
      $mstats = $mrow['stats'];
      $mparts = explode(';', $mstats);
      for ($j = 0; $j < count($mparts); $j++) {
        if (str_starts_with($mparts[$j], 'drops=')) { $drops = explode('=', $mparts[$j])[1]; break; }
      }
    }
  }
  $sm->close();
  if ($drops === '') { array_push($resp, "no_drops"); return $resp; }

  $droplist = array_map('trim', explode(',', $drops));
  $itemName = $droplist[rand(0, count($droplist) - 1)];

  // Roll chance similar to combat itemDropRate
  if (rand(1, 100) > $itemDropRate) {
    // No drop: still consume the gather flag once
    try {
      $ng = $db->prepare("UPDATE game_locations SET stats = REPLACE(stats, 'gather=1;', '') WHERE room_id = ? AND x = ? AND y = ?");
      if ($ng) { $ng->bind_param("iii", $room_id, $x, $y); $ng->execute(); $ng->close(); }
    } catch (Throwable $_) { }
    touchPlayer($db, $room_id, $player_name);
    try {
      if ($lg = $db->prepare("INSERT INTO game_logs (room_id, player_name, action, details) VALUES (?, ?, 'gather', '{\"result\":\"none\"}')")) {
        $lg->bind_param("is", $room_id, $player_name);
        $lg->execute();
        $lg->close();
      }
    } catch (Throwable $_) { }
    array_push($resp, "ok", "none");
    return $resp;
  }

  // Lookup item resource and resolve stats
  $si = $db->prepare("SELECT id, stats FROM resources_items WHERE name = ? LIMIT 1");
  $si->bind_param("s", $itemName);
  $item_resource_id = -1; $item_stats_tpl = '';
  if ($si->execute()) {
    $ir = $si->get_result();
    if ($irow = mysqli_fetch_array($ir)) { $item_resource_id = intval($irow['id']); $item_stats_tpl = $irow['stats']; }
  }
  $si->close();
  if ($item_resource_id <= 0) {
    // Item configured in monster drops but missing from resources_items; consume flag and treat as no drop
    try {
      $ng = $db->prepare("UPDATE game_locations SET stats = REPLACE(stats, 'gather=1;', '') WHERE room_id = ? AND x = ? AND y = ?");
      if ($ng) { $ng->bind_param("iii", $room_id, $x, $y); $ng->execute(); $ng->close(); }
    } catch (Throwable $_) { }
    touchPlayer($db, $room_id, $player_name);
    try {
      $det = json_encode([ 'item' => $itemName, 'reason' => 'missing_resource_item', 'x' => $x, 'y' => $y ]);
      if ($det === false) { $det = '{"item":"'.addslashes($itemName).'","reason":"missing_resource_item","x":'.intval($x).',"y":'.intval($y).'}'; }
      if ($lg = $db->prepare("INSERT INTO game_logs (room_id, player_name, action, details) VALUES (?, ?, 'gather_warn', ?)")) {
        $lg->bind_param("iss", $room_id, $player_name, $det);
        $lg->execute();
        $lg->close();
      }
    } catch (Throwable $_) { }
    array_push($resp, "ok", "none");
    return $resp;
  }

  $final_stats = parseItemStats($item_stats_tpl);

  // Insert into game_items
  $ii = $db->prepare("INSERT INTO game_items( room_id, stats, resource_id, owner_id ) VALUES(?, ?, ?, ?)");
  $ii->bind_param("isii", $room_id, $final_stats, $item_resource_id, $player_id);
  if ($ii->execute()) {
    // consume gather flag on this tile
    try {
      $ng = $db->prepare("UPDATE game_locations SET stats = REPLACE(stats, 'gather=1;', '') WHERE room_id = ? AND x = ? AND y = ?");
      if ($ng) { $ng->bind_param("iii", $room_id, $x, $y); $ng->execute(); $ng->close(); }
    } catch (Throwable $_) { }
    // Touch and telemetry
    touchPlayer($db, $room_id, $player_name);
    try {
      $det = json_encode([ 'item' => $itemName, 'resource_id' => $item_resource_id, 'x' => $x, 'y' => $y ]);
      if ($det === false) { $det = '{"item":"'.addslashes($itemName).'","resource_id":'.intval($item_resource_id).',"x":'.intval($x).',"y":'.intval($y).'}'; }
      if ($lg = $db->prepare("INSERT INTO game_logs (room_id, player_name, action, details) VALUES (?, ?, 'gather', ?)")) {
        $lg->bind_param("iss", $room_id, $player_name, $det);
        $lg->execute();
        $lg->close();
      }
    } catch (Throwable $_) { }
    array_push($resp, "ok", "item", $itemName);
  } else {
    // Insert failed (DB)
    try {
      $det = json_encode([ 'item' => $itemName, 'resource_id' => $item_resource_id, 'reason' => 'insert_failed', 'x' => $x, 'y' => $y ]);
      if ($det === false) { $det = '{"item":"'.addslashes($itemName).'","resource_id":'.intval($item_resource_id).',"reason":"insert_failed","x":'.intval($x).',"y":'.intval($y).'}'; }
      if ($lg = $db->prepare("INSERT INTO game_logs (room_id, player_name, action, details) VALUES (?, ?, 'gather_error', ?)")) {
        $lg->bind_param("iss", $room_id, $player_name, $det);
        $lg->execute();
        $lg->close();
      }
    } catch (Throwable $_) { }
    array_push($resp, "err_db");
  }
  $ii->close();

  return $resp;
}

function getAllLocations($db, $data)
{
  $room_id = intval(clean($data['get_all_locations']));

  $ss = $db->prepare("SELECT game_locations.id AS game_loc_id,
                             game_locations.room_id,
                             game_locations.x,
                             game_locations.y,
                             game_locations.stats AS gstats,
                             game_locations.resource_id,
                             resources_locations.name,
                             resources_locations.description,
                             resources_locations.image,
                             resources_locations.stats AS stats,
                             resources_locations.location_type
				FROM game_locations INNER JOIN resources_locations ON game_locations.resource_id = resources_locations.id 
				WHERE room_id = ?");
  $ss->bind_param("i", $room_id);

  $arr = array();
  if ($ss->execute()) {
    $r = $ss->get_result();
    $rc = mysqli_num_rows($r);
    if ($rc > 0) {
      while ($row = mysqli_fetch_array($r)) {
        array_push($arr, $row);
      }
    }
  }
  $ss->close();
  return $arr;
}
