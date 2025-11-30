<?php

function getPlayers($db, $data)
{
  $room_id = clean($data['get_players']);

  $ss = $db->prepare("SELECT * 
				FROM game_players 
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

function getPlayer($db, $data)
{
  $player_name = clean($data['get_player']);
  $room_id = clean($data['room_id']);

  $ss = $db->prepare("SELECT * 
				FROM game_players 
				WHERE name = ? AND room_id = ?");
  $ss->bind_param("si", $player_name, $room_id);

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

function createPlayer($db, $data)
{
  $name = clean($data['create_player']);
  $portrait = -intval(clean($data['player_portrait']));
  $room_id = intval(clean($data['room_id']));

  $arr = array();
  if ($portrait <= -1 && $portrait >= -8) {

    $x = 0;
    $y = 0;
    $stats = "lvl=1;exp=0;hp=100;maxhp=100;mp=50;maxmp=50;atk=10;def=10;spd=10;evd=10;gold=0;crt=5;skill_points=3;job=none;unlocked_skills=;";

    $is = $db->prepare("INSERT INTO game_players( name, room_id, x, y, stats, resource_id ) 
				VALUES(?, ?, ?, ?, ?, ?)");
    $is->bind_param("siiisi", $name, $room_id, $x, $y, $stats, $portrait);
    if ($is->execute()) {
      array_push($arr, "ok");
      // Touch right after creation
      touchPlayer($db, $room_id, $name);

      // Telemetry: player created
      try {
        $det = json_encode([ 'room_id' => $room_id, 'portrait' => $portrait ]);
        if ($det === false) { $det = '{"room_id":'.intval($room_id).',"portrait":'.intval($portrait).'}'; }
        if ($lg = $db->prepare("INSERT INTO game_logs (room_id, player_name, action, details) VALUES (?, ?, 'player_create', ?)")) {
          $lg->bind_param("iss", $room_id, $name, $det);
          $lg->execute();
          $lg->close();
        }
      } catch (Throwable $_) { }

      // Ensure the spawn tile (0,0) and its four adjacent tiles exist at player creation
      // Create center tile if missing (diffx=0,diffy=0 to avoid monster spawn)
      $check = $db->prepare("SELECT 1 FROM game_locations WHERE room_id = ? AND x = ? AND y = ?");
      $cx = 0; $cy = 0;
      $check->bind_param("iii", $room_id, $cx, $cy);
      if ($check->execute()) {
        $cr = $check->get_result();
        if (mysqli_num_rows($cr) == 0) {
          // newloc starts as true; we ignore the return value here
          $tmp = true;
          spawnLocation($db, $cx, $cy, $room_id, 0, 0, 0, $tmp);
        }
      }
      $check->close();

      // Adjacent tiles: (1,0), (-1,0), (0,1), (0,-1)
      $dirs = [[1,0],[-1,0],[0,1],[0,-1]];
      foreach ($dirs as $d) {
        $nx = $cx + $d[0];
        $ny = $cy + $d[1];
        $s = $db->prepare("SELECT 1 FROM game_locations WHERE room_id = ? AND x = ? AND y = ?");
        $s->bind_param("iii", $room_id, $nx, $ny);
        if ($s->execute()) {
          $sr = $s->get_result();
          if (mysqli_num_rows($sr) == 0) {
            $tmp = true;
            // diffx/diffy set to 0 to avoid spawning monsters for adjacent generation
            spawnLocation($db, $nx, $ny, $room_id, 0, 0, 0, $tmp);
          }
        }
        $s->close();
      }
    } else {
      array_push($arr, "err");
    }
    $is->close();
  }
  return $arr;
}

function getItems($db, $data)
{
  $player_id = intval(clean($data['get_items']));
  $room_id = intval(clean($data['room_id']));

  $ss = $db->prepare("SELECT gi.id, gi.stats, gi.equipped, ri.name, ri.image, ri.description, ri.rarity 
				FROM game_items gi INNER JOIN resources_items ri ON gi.resource_id = ri.id 
				WHERE gi.room_id = ? AND gi.owner_id = ?");
  $ss->bind_param("ii", $room_id, $player_id);

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

function dropItem($db, $data)
{
  $item_id = intval(clean($data['drop_item']));
  $player_id = intval(clean($data['player_id']));
  $arr = array();

  // Place item on ground (set owner_id to NULL) instead of deleting
  $di = $db->prepare("UPDATE game_items SET owner_id = NULL, equipped = 0 WHERE id = ? AND owner_id = ?");
  $di->bind_param("ii", $item_id, $player_id);

  if ($di->execute()) {
    array_push($arr, "ok");
    // Touch owner
    $q = $db->prepare("SELECT name, room_id FROM game_players WHERE id = ?");
    $q->bind_param("i", $player_id);
    if ($q->execute()) { 
      $res = $q->get_result(); 
      if ($row = mysqli_fetch_array($res)) { 
        $room_id = intval($row['room_id']); $player_name = $row['name'];
        touchPlayer($db, $room_id, $player_name);
        // Telemetry: item_drop
        try {
          $det = json_encode([ 'item_id' => $item_id, 'player_id' => $player_id ]);
          if ($det === false) { $det = '{"item_id":'.intval($item_id).',"player_id":'.intval($player_id).'}'; }
          if ($lg = $db->prepare("INSERT INTO game_logs (room_id, player_name, action, details) VALUES (?, ?, 'item_drop', ?)")) {
            $lg->bind_param("iss", $room_id, $player_name, $det);
            $lg->execute();
            $lg->close();
          }
        } catch (Throwable $_) { }
      }
    }
    $q->close();
  } else {
    array_push($arr, "err");
  }

  $di->close();
  return $arr;
}

function unequipItem($db, $data)
{
  $item_id = intval(clean($data['unequip_item']));
  $player_id = intval(clean($data['player_id']));
  $arr = array();

  $ui = $db->prepare("UPDATE game_items SET equipped=0 WHERE id = ? AND owner_id = ?");
  $ui->bind_param("ii", $item_id, $player_id);

  if ($ui->execute()) {
    array_push($arr, "ok");
    // Touch owner
    $q = $db->prepare("SELECT name, room_id FROM game_players WHERE id = ?");
    $q->bind_param("i", $player_id);
    if ($q->execute()) { 
      $res = $q->get_result(); 
      if ($row = mysqli_fetch_array($res)) { 
        $room_id = intval($row['room_id']); $player_name = $row['name'];
        touchPlayer($db, $room_id, $player_name);
        // Telemetry: item_unequip
        try {
          $det = json_encode([ 'item_id' => $item_id, 'player_id' => $player_id ]);
          if ($det === false) { $det = '{"item_id":'.intval($item_id).',"player_id":'.intval($player_id).'}'; }
          if ($lg = $db->prepare("INSERT INTO game_logs (room_id, player_name, action, details) VALUES (?, ?, 'item_unequip', ?)")) {
            $lg->bind_param("iss", $room_id, $player_name, $det);
            $lg->execute();
            $lg->close();
          }
        } catch (Throwable $_) { }
      }
    }
    $q->close();
  } else {
    array_push($arr, "err");
  }

  $ui->close();
  return $arr;
}

function equipItem($db, $data)
{
  $item_id = intval(clean($data['equip_item']));
  $player_id = intval(clean($data['player_id']));
  $arr = array();
  $ids = array();
  $types = array();

  $se = $db->prepare("SELECT id, stats, equipped FROM game_items WHERE owner_id = ?");
  $se->bind_param("i", $player_id);

  $item_type = '';
  if ($se->execute()) {
    $r = $se->get_result();
    while ($row = mysqli_fetch_array($r)) {
      $itemStats = parseItemStatsToArray($row["stats"]);
      $type = $itemStats['type'];
      if ($type !== '') {
        if (intval($row["id"]) == $item_id) {
          $item_type = $type;
        } else if (intval($row["equipped"]) == 1) {
          array_push($ids, intval($row["id"]));
          array_push($types, $type);
        }
      }
    }
  }
  $se->close();

  if ($item_type != '') {

    for ($i = 0; $i < count($ids); $i++) {
      $temp_type = $types[$i];
      if ($temp_type == $item_type) {

        $temp_id = $ids[$i];
        $uis = $db->prepare("UPDATE game_items SET equipped=0 WHERE id = ? AND owner_id = ?");
        $uis->bind_param("ii", $temp_id, $player_id);
        $uis->execute();
        $uis->close();
      }
    }

    $ui = $db->prepare("UPDATE game_items SET equipped=1 WHERE id = ? AND owner_id = ?");
    $ui->bind_param("ii", $item_id, $player_id);

    if ($ui->execute()) {
      array_push($arr, "ok");
      // Touch owner
      $q = $db->prepare("SELECT name, room_id FROM game_players WHERE id = ?");
      $q->bind_param("i", $player_id);
      if ($q->execute()) { 
        $res = $q->get_result(); 
        if ($row = mysqli_fetch_array($res)) { 
          $room_id = intval($row['room_id']); $player_name = $row['name'];
          touchPlayer($db, $room_id, $player_name);
          // Telemetry: item_equip
          try {
            $det = json_encode([ 'item_id' => $item_id, 'player_id' => $player_id, 'item_type' => $item_type ]);
            if ($det === false) { $det = '{"item_id":'.intval($item_id).',"player_id":'.intval($player_id).',"item_type":"'.addslashes($item_type).'"}'; }
            if ($lg = $db->prepare("INSERT INTO game_logs (room_id, player_name, action, details) VALUES (?, ?, 'item_equip', ?)")) {
              $lg->bind_param("iss", $room_id, $player_name, $det);
              $lg->execute();
              $lg->close();
            }
          } catch (Throwable $_) { }
        }
      }
      $q->close();
    } else {
      array_push($arr, "err");
    }

    $ui->close();
  } else {
    array_push($arr, "err: item type " . $item_type);
  }
  return $arr;
}

// Rest at a rest spot or town to restore HP/MP
function restAtLocation($db, $data)
{
  $player_name = clean($data['rest_player']);
  $room_id = intval(clean($data['room_id']));
  
  $result = array(
    'success' => false,
    'message' => '',
    'hp_restored' => 0,
    'mp_restored' => 0,
    'player' => null
  );
  
  // Rate limit: max 1 rest per 5 seconds per player
  $rlc = telemetryRateLimitCheck($db, $room_id, $player_name, ['rest'], 5, 1);
  if (!$rlc['ok']) {
    $result['message'] = 'You must wait before resting again.';
    return $result;
  }
  
  // Get player
  $sp = $db->prepare("SELECT id, x, y, stats FROM game_players WHERE room_id = ? AND name = ?");
  $sp->bind_param("is", $room_id, $player_name);
  
  if (!$sp->execute()) {
    $sp->close();
    $result['message'] = 'Player not found.';
    return $result;
  }
  
  $rp = $sp->get_result();
  if (mysqli_num_rows($rp) === 0) {
    $sp->close();
    $result['message'] = 'Player not found.';
    return $result;
  }
  
  $row = mysqli_fetch_array($rp);
  $player_id = intval($row['id']);
  $player_x = intval($row['x']);
  $player_y = intval($row['y']);
  $stats_str = $row['stats'];
  $sp->close();
  
  // Parse player stats
  $lvl=1;$exp=0;$hp=1;$maxhp=1;$mp=0;$maxmp=0;$atk=0;$def=0;$spd=0;$evd=0;$crt=5;$gold=0;$skill_points=0;$job='none';$unlocked_skills='';
  $parts = explode(';', $stats_str);
  foreach ($parts as $p) {
    if ($p === '') continue;
    if (str_starts_with($p, 'lvl=')) { $lvl = intval(explode('=', $p)[1]); }
    else if (str_starts_with($p, 'exp=')) { $exp = intval(explode('=', $p)[1]); }
    else if (str_starts_with($p, 'hp=')) { $hp = intval(explode('=', $p)[1]); }
    else if (str_starts_with($p, 'maxhp=')) { $maxhp = intval(explode('=', $p)[1]); }
    else if (str_starts_with($p, 'mp=')) { $mp = intval(explode('=', $p)[1]); }
    else if (str_starts_with($p, 'maxmp=')) { $maxmp = intval(explode('=', $p)[1]); }
    else if (str_starts_with($p, 'atk=')) { $atk = intval(explode('=', $p)[1]); }
    else if (str_starts_with($p, 'def=')) { $def = intval(explode('=', $p)[1]); }
    else if (str_starts_with($p, 'spd=')) { $spd = intval(explode('=', $p)[1]); }
    else if (str_starts_with($p, 'evd=')) { $evd = intval(explode('=', $p)[1]); }
    else if (str_starts_with($p, 'crt=')) { $crt = intval(explode('=', $p)[1]); }
    else if (str_starts_with($p, 'gold=')) { $gold = intval(explode('=', $p)[1]); }
    else if (str_starts_with($p, 'skill_points=')) { $skill_points = intval(explode('=', $p)[1]); }
    else if (str_starts_with($p, 'job=')) { $job = explode('=', $p)[1]; }
    else if (str_starts_with($p, 'unlocked_skills=')) { $unlocked_skills = explode('=', $p)[1]; }
  }
  
  // Apply passive skill bonuses to maxhp and maxmp
  $passiveBonuses = getPassiveSkillBonuses($db, $unlocked_skills);
  $effectiveMaxHp = $maxhp + $passiveBonuses['maxhp'];
  $effectiveMaxMp = $maxmp + $passiveBonuses['maxmp'];
  
  // Check if player is at a rest spot or town
  $sl = $db->prepare("SELECT rl.location_type, rl.name FROM game_locations gl INNER JOIN resources_locations rl ON gl.resource_id = rl.id WHERE gl.room_id = ? AND gl.x = ? AND gl.y = ?");
  $sl->bind_param("iii", $room_id, $player_x, $player_y);
  
  if (!$sl->execute()) {
    $sl->close();
    $result['message'] = 'Cannot rest here.';
    return $result;
  }
  
  $lr = $sl->get_result();
  if (mysqli_num_rows($lr) === 0) {
    $sl->close();
    $result['message'] = 'Cannot rest here.';
    return $result;
  }
  
  $loc_row = mysqli_fetch_array($lr);
  $location_type = $loc_row['location_type'];
  $location_name = $loc_row['name'];
  $sl->close();
  
  // Only allow rest at towns or rest_spots
  if ($location_type !== 'town' && $location_type !== 'rest_spot') {
    $result['message'] = 'You can only rest at towns or rest spots.';
    return $result;
  }
  
  // Check if there are monsters at this location
  $cm = $db->prepare("SELECT 1 FROM game_monsters WHERE room_id = ? AND x = ? AND y = ? LIMIT 1");
  $cm->bind_param("iii", $room_id, $player_x, $player_y);
  if ($cm->execute()) {
    $cmr = $cm->get_result();
    if (mysqli_num_rows($cmr) > 0) {
      $cm->close();
      $result['message'] = 'Cannot rest while enemies are nearby!';
      return $result;
    }
  }
  $cm->close();
  
  // Calculate restoration
  $old_hp = $hp;
  $old_mp = $mp;
  $hp = $effectiveMaxHp;
  $mp = $effectiveMaxMp;
  $hp_restored = $hp - $old_hp;
  $mp_restored = $mp - $old_mp;
  
  // Cleanse negative status effects (poison, etc.) when resting
  $stats_str_clean = $stats_str;
  $cleansed_effects = array();
  try {
    $effects = parseStatusEffects($stats_str);
    foreach ($effects as $effect_name => $expiry) {
      // Remove negative effects
      if (str_starts_with($effect_name, 'poison_')) {
        $stats_str_clean = removeStatusEffect($stats_str_clean, $effect_name);
        $cleansed_effects[] = $effect_name;
      }
    }
  } catch (Throwable $_) {}
  
  // Update player stats
  $new_stats = setPlayerStats($lvl, $exp, $hp, $maxhp, $mp, $maxmp, $atk, $def, $spd, $evd, $gold, $crt, $skill_points, $job, $unlocked_skills);
  // Clean any expired effects
  $new_stats = cleanExpiredEffects($new_stats);
  $up = $db->prepare("UPDATE game_players SET stats = ? WHERE id = ?");
  $up->bind_param("si", $new_stats, $player_id);
  $up->execute();
  $up->close();
  
  touchPlayer($db, $room_id, $player_name);
  
  // Telemetry: rest action
  try {
    $det = json_encode([
      'location' => $location_name,
      'location_type' => $location_type,
      'hp_restored' => $hp_restored,
      'mp_restored' => $mp_restored
    ]);
    if ($det === false) { $det = '{"location":"'.addslashes($location_name).'","hp_restored":'.intval($hp_restored).',"mp_restored":'.intval($mp_restored).'}'; }
    if ($lg = $db->prepare("INSERT INTO game_logs (room_id, player_name, action, details) VALUES (?, ?, 'rest', ?)")) {
      $lg->bind_param("iss", $room_id, $player_name, $det);
      $lg->execute();
      $lg->close();
    }
  } catch (Throwable $_) { }
  
  $result['success'] = true;
  $message = 'You rest at ' . $location_name . ' and feel refreshed!';
  if (count($cleansed_effects) > 0) {
    $message .= ' Negative effects cleansed.';
  }
  $result['message'] = $message;
  $result['hp_restored'] = $hp_restored;
  $result['mp_restored'] = $mp_restored;
  $result['cleansed_effects'] = $cleansed_effects;
  $result['player'] = array(
    'hp' => $hp,
    'maxhp' => $effectiveMaxHp,
    'mp' => $mp,
    'maxmp' => $effectiveMaxMp
  );
  
  return $result;
}

// Set respawn point at current location (must be at a town)
function setRespawnPoint($db, $data)
{
  $player_name = clean($data['set_respawn']);
  $room_id = intval(clean($data['room_id']));
  
  $result = array(
    'success' => false,
    'message' => ''
  );
  
  // Get player
  $sp = $db->prepare("SELECT id, x, y FROM game_players WHERE room_id = ? AND name = ?");
  $sp->bind_param("is", $room_id, $player_name);
  
  if (!$sp->execute()) {
    $sp->close();
    $result['message'] = 'Player not found.';
    return $result;
  }
  
  $rp = $sp->get_result();
  if (mysqli_num_rows($rp) === 0) {
    $sp->close();
    $result['message'] = 'Player not found.';
    return $result;
  }
  
  $row = mysqli_fetch_array($rp);
  $player_id = intval($row['id']);
  $player_x = intval($row['x']);
  $player_y = intval($row['y']);
  $sp->close();
  
  // Check if player is at a town
  $sl = $db->prepare("SELECT rl.location_type, rl.name FROM game_locations gl INNER JOIN resources_locations rl ON gl.resource_id = rl.id WHERE gl.room_id = ? AND gl.x = ? AND gl.y = ?");
  $sl->bind_param("iii", $room_id, $player_x, $player_y);
  
  if (!$sl->execute()) {
    $sl->close();
    $result['message'] = 'Cannot set respawn point here.';
    return $result;
  }
  
  $lr = $sl->get_result();
  if (mysqli_num_rows($lr) === 0) {
    $sl->close();
    $result['message'] = 'Cannot set respawn point here.';
    return $result;
  }
  
  $loc_row = mysqli_fetch_array($lr);
  $location_type = $loc_row['location_type'];
  $location_name = $loc_row['name'];
  $sl->close();
  
  // Only allow setting respawn at towns
  if ($location_type !== 'town') {
    $result['message'] = 'You can only set your respawn point at towns.';
    return $result;
  }
  
  // Update respawn point
  $up = $db->prepare("UPDATE game_players SET respawn_x = ?, respawn_y = ? WHERE id = ?");
  $up->bind_param("iii", $player_x, $player_y, $player_id);
  $up->execute();
  $up->close();
  
  touchPlayer($db, $room_id, $player_name);
  
  // Telemetry: set respawn
  try {
    $det = json_encode([
      'location' => $location_name,
      'x' => $player_x,
      'y' => $player_y
    ]);
    if ($det === false) { $det = '{"location":"'.addslashes($location_name).'","x":'.intval($player_x).',"y":'.intval($player_y).'}'; }
    if ($lg = $db->prepare("INSERT INTO game_logs (room_id, player_name, action, details) VALUES (?, ?, 'set_respawn', ?)")) {
      $lg->bind_param("iss", $room_id, $player_name, $det);
      $lg->execute();
      $lg->close();
    }
  } catch (Throwable $_) { }
  
  $result['success'] = true;
  $result['message'] = 'Respawn point set at ' . $location_name . '.';
  $result['respawn_x'] = $player_x;
  $result['respawn_y'] = $player_y;
  
  return $result;
}
