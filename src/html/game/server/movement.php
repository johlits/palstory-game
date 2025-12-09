<?php

function spawnMonster($db, $monsterName, $room_id, $x, $y, $newloc)
{
  $smres = $db->prepare("SELECT * 
                        FROM resources_monsters 
                        WHERE name = ?");
  $smres->bind_param("s", $monsterName);

  if ($smres->execute()) {
    $smresr = $smres->get_result();
    $smresrc = mysqli_num_rows($smresr);
    if ($smresrc > 0) {
      while ($smresrow = mysqli_fetch_array($smresr)) {

        $monstats = parseMonsterStats($smresrow['stats']);
        $monresource = intval($smresrow['id']);

        // insert into game_monsters
        $im = $db->prepare("INSERT INTO game_monsters(room_id, x, y, stats, resource_id) 
				VALUES(?, ?, ?, ?, ?)");
        $im->bind_param("iiisi", $room_id, $x, $y, $monstats, $monresource);
        if (!$im->execute()) {
          $newloc = false;
        }
        $im->close();
      }
    }
  } else {
    $newloc = false;
  }
  $smres->close();
  return $newloc;
}

function spawnMonsterRoll($db, $room_id, $x, $y, $monsterSpawnRate, $locstats, $newloc)
{
  // Check if this is a safe location (town or rest_spot) - don't spawn monsters there
  $location_type = '';
  try {
    $sl = $db->prepare("SELECT rl.location_type FROM game_locations gl INNER JOIN resources_locations rl ON gl.resource_id = rl.id WHERE gl.room_id = ? AND gl.x = ? AND gl.y = ?");
    $sl->bind_param("iii", $room_id, $x, $y);
    if ($sl->execute()) {
      $lr = $sl->get_result();
      if ($lrow = mysqli_fetch_array($lr)) {
        $location_type = $lrow['location_type'];
      }
    }
    $sl->close();
  } catch (Throwable $_) {}
  
  // Don't spawn monsters on towns, rest spots, or dungeons (each location type has its own color)
  if ($location_type === 'town' || $location_type === 'rest_spot' || $location_type === 'dungeon') {
    return $newloc;
  }
  
  // if no monster, roll dice, spawn monster
  $sm = $db->prepare("SELECT * 
FROM game_monsters 
WHERE room_id = ? AND x = ? AND y = ?");
  $sm->bind_param("iii", $room_id, $x, $y);
  if ($sm->execute()) {
    $smr = $sm->get_result();
    $smrows = mysqli_num_rows($smr);
    if ($smrows == 0) {
      if (rand(1, 100) <= $monsterSpawnRate) {
        $locParsed = parseStatsToArray($locstats);
        if (isset($locParsed['spawns']) && $locParsed['spawns'] !== '') {
          $monsters = explode(',', $locParsed['spawns']);
          $monsterName = trim($monsters[rand(0, count($monsters) - 1)], " ");
          $newloc = spawnMonster($db, $monsterName, $room_id, $x, $y, $newloc);
        }
      }
    }
  } else {
    $newloc = false;
  }
  $sm->close();
  return $newloc;
}

function spawnLocation($db, $x, $y, $room_id, $diffx, $diffy, $monsterSpawnRate, $newloc)
{
  $room_lvl = max(1, abs($x) + abs($y));
  $srlocs = $db->prepare("SELECT * 
FROM resources_locations 
WHERE lvl_from <= ? AND lvl_to >= ?");
  $srlocs->bind_param("ii", $room_lvl, $room_lvl);
  if ($srlocs->execute()) {
    $srlocr = $srlocs->get_result();
    $srlocrc = mysqli_num_rows($srlocr);

    if ($srlocrc > 0) {
      $rloc = mysqli_fetch_all($srlocr)[rand(0, $srlocrc - 1)];

      $is = $db->prepare("INSERT INTO game_locations(room_id, x, y, stats, resource_id) 
VALUES(?, ?, ?, ?, ?)");
      $locstats = $rloc[6];
      // 10% chance a tile is gatherable once
      if (rand(1, 100) <= 10) { $locstats = rtrim($locstats, ';') . ";gather=1;"; }
      $rlocid = intval($rloc[0]);
      $is->bind_param("iiisi", $room_id, $x, $y, $locstats, $rlocid);
      if ($is->execute()) {

        if (!($x == 0 && $y == 0) && ($diffx + $diffy) != 0) {
          $newloc = spawnMonsterRoll($db, $room_id, $x, $y, $monsterSpawnRate, $locstats, $newloc);
        }

      } else {
        $newloc = false;
      }
      $is->close();
    } else {
      $newloc = false;
    }
  }
  $srlocs->close();
  return $newloc;
}

function performMove($db, $diffx, $diffy, $room_id, $x, $y, $monsterSpawnRate, $player_name, $prevx, $prevy)
{
  $arr = array();
  $newloc = true;
  $drawLocation = false;
  // Validate single-tile movement only (no diagonals, no teleportation)
  if ($diffx <= 1 && $diffy <= 1 && ($diffx + $diffy) <= 1 && ($diffx + $diffy) > 0) {

    $sls = $db->prepare("SELECT * 
				FROM game_locations 
				WHERE room_id = ? AND x = ? AND y = ?");
    $sls->bind_param("iii", $room_id, $x, $y);
    if ($sls->execute()) {
      $slr = $sls->get_result();
      $slrc = mysqli_num_rows($slr);

      if ($slrc == 0) {
        $drawLocation = spawnLocation($db, $x, $y, $room_id, $diffx, $diffy, $monsterSpawnRate, $newloc);
        $newloc = $drawLocation;
      } else {

        while ($slrow = mysqli_fetch_array($slr)) {
          $locstats = $slrow["stats"];
          break;
        }

        if (!($x == 0 && $y == 0) && ($diffx + $diffy) != 0) {
          $newloc = spawnMonsterRoll($db, $room_id, $x, $y, $monsterSpawnRate, $locstats, $newloc);
        }
      }
    }
    $sls->close();

    // Authoritative passability check on the target tile (after ensuring it exists or was spawned)
    try {
      $gstats = '';
      $stats = '';
      $lp = $db->prepare("SELECT game_locations.stats AS gstats, resources_locations.stats AS stats FROM game_locations LEFT JOIN resources_locations ON game_locations.resource_id = resources_locations.id WHERE game_locations.room_id = ? AND x = ? AND y = ? LIMIT 1");
      if ($lp) {
        $lp->bind_param("iii", $room_id, $x, $y);
        if ($lp->execute()) {
          $lpr = $lp->get_result();
          if ($lprow = mysqli_fetch_array($lpr)) { $gstats = strval($lprow['gstats']); $stats = strval($lprow['stats']); }
        }
        $lp->close();
      }
      if ($gstats !== '' || $stats !== '') {
        if (!isLocationPassable($gstats, $stats)) {
          // Telemetry: movement blocked by passability
          try {
            $detb = json_encode([ 'from' => [$prevx,$prevy], 'attempt' => [$x,$y], 'reason' => 'blocked' ]);
            if ($detb === false) { $detb = '{"reason":"blocked"}'; }
            if ($lgb = $db->prepare("INSERT INTO game_logs (room_id, player_name, action, details) VALUES (?, ?, 'move_blocked_passability', ?)")) {
              $lgb->bind_param("iss", $room_id, $player_name, $detb);
              $lgb->execute();
              $lgb->close();
            }
          } catch (Throwable $_) { }
          array_push($arr, 'err', 'blocked');
          return $arr;
        }
      }
    } catch (Throwable $_) { /* ignore passability errors; default to allow */ }

    if ($newloc == true) {
      $us = $db->prepare("UPDATE game_players 
            SET x=?, y=? 
            WHERE name = ? AND room_id = ?");
      $us->bind_param("iisi", $x, $y, $player_name, $room_id);
      if ($us->execute()) {
        // Build consolidated response with location and monster data
        $consolidatedResponse = [
          'status' => 'ok',
          'dx' => $x - $prevx,
          'dy' => $y - $prevy,
          'draw' => $drawLocation == true,
          'x' => $x,
          'y' => $y,
          'location' => null,
          'monsters' => [],
          'adjacent' => []
        ];
        
        // Fetch current location data
        try {
          $locQuery = $db->prepare("SELECT gl.x, gl.y, gl.stats as gstats, rl.name, rl.image, rl.description, rl.stats, rl.location_type 
            FROM game_locations gl 
            INNER JOIN resources_locations rl ON gl.resource_id = rl.id 
            WHERE gl.room_id = ? AND gl.x = ? AND gl.y = ?");
          if ($locQuery) {
            $locQuery->bind_param("iii", $room_id, $x, $y);
            if ($locQuery->execute()) {
              $locResult = $locQuery->get_result();
              if ($locRow = mysqli_fetch_assoc($locResult)) {
                $consolidatedResponse['location'] = $locRow;
              }
            }
            $locQuery->close();
          }
        } catch (Throwable $_) {}
        
        // Fetch monsters at current location
        try {
          $monQuery = $db->prepare("SELECT gm.id, gm.x, gm.y, gm.stats, rm.name, rm.image, rm.description 
            FROM game_monsters gm 
            INNER JOIN resources_monsters rm ON gm.resource_id = rm.id 
            WHERE gm.room_id = ? AND gm.x = ? AND gm.y = ?");
          if ($monQuery) {
            $monQuery->bind_param("iii", $room_id, $x, $y);
            if ($monQuery->execute()) {
              $monResult = $monQuery->get_result();
              while ($monRow = mysqli_fetch_assoc($monResult)) {
                $consolidatedResponse['monsters'][] = $monRow;
              }
            }
            $monQuery->close();
          }
        } catch (Throwable $_) {}
        
        // Fetch adjacent tiles data (for client-side caching)
        $dirs = [[1,0],[-1,0],[0,1],[0,-1]];
        foreach ($dirs as $d) {
          $nx = $x + $d[0];
          $ny = $y + $d[1];
          try {
            $adjQuery = $db->prepare("SELECT gl.x, gl.y, gl.stats as gstats, rl.name, rl.image, rl.description, rl.stats, rl.location_type 
              FROM game_locations gl 
              INNER JOIN resources_locations rl ON gl.resource_id = rl.id 
              WHERE gl.room_id = ? AND gl.x = ? AND gl.y = ?");
            if ($adjQuery) {
              $adjQuery->bind_param("iii", $room_id, $nx, $ny);
              if ($adjQuery->execute()) {
                $adjResult = $adjQuery->get_result();
                if ($adjRow = mysqli_fetch_assoc($adjResult)) {
                  $consolidatedResponse['adjacent'][] = $adjRow;
                }
              }
              $adjQuery->close();
            }
          } catch (Throwable $_) {}
        }
        
        // Legacy array format for backward compatibility
        array_push($arr, "ok");
        array_push($arr, $x - $prevx);
        array_push($arr, $y - $prevy);
        array_push($arr, $drawLocation == true ? "draw" : "");
        // Append consolidated data as 5th element
        array_push($arr, $consolidatedResponse);

        // After a successful move, ensure the four adjacent tiles around the player exist
        $dirs = [[1,0],[-1,0],[0,1],[0,-1]];
        foreach ($dirs as $d) {
          $nx = $x + $d[0];
          $ny = $y + $d[1];
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

        // Out-of-combat MP regen: +1 MP up to maxmp on successful non-fight move
        try {
          // Fetch current player stats for MP regen
          $stats_str = '';
          $gp = $db->prepare("SELECT stats FROM game_players WHERE name = ? AND room_id = ? LIMIT 1");
          if ($gp) {
            $gp->bind_param("si", $player_name, $room_id);
            if ($gp->execute()) {
              $gpr = $gp->get_result();
              if ($gprow = mysqli_fetch_array($gpr)) {
                $stats_str = strval($gprow['stats']);
              }
            }
            $gp->close();
          }
          if ($stats_str !== '') {
            // Parse stats using centralized utility
            $pstats = parsePlayerStats($stats_str);
            $mp = $pstats['mp'];
            $maxmp = $pstats['maxmp'];
            
            // Only regen if maxmp > 0
            if ($maxmp > 0 && $mp < $maxmp) {
              $mp = min($maxmp, $mp + 1);
              $new_stats = setPlayerStats(
                $pstats['lvl'], $pstats['exp'], $pstats['hp'], $pstats['maxhp'], 
                $mp, $maxmp, $pstats['atk'], $pstats['def'], $pstats['spd'], 
                $pstats['evd'], $pstats['gold'], $pstats['crt'], $pstats['skill_points'], 
                $pstats['job'], $pstats['unlocked_skills']
              );
              $up = $db->prepare("UPDATE game_players SET stats = ? WHERE name = ? AND room_id = ?");
              if ($up) {
                $up->bind_param("ssi", $new_stats, $player_name, $room_id);
                $up->execute();
                $up->close();
              }
            }
          }
        } catch (Throwable $_) { /* ignore regen errors */ }
      } else {
        array_push($arr, "err");
      }
      $us->close();
    } else {
      array_push($arr, "err");
    }
  } else {
    array_push($arr, "err");
  }
  return $arr;
}

function movePlayer($db, $data, $itemDropRate, $monsterSpawnRate)
{
  $player_name = clean($data['move_player']);
  $room_id = intval(clean($data['room_id']));
  $x = intval(clean($data['x']));
  $y = intval(clean($data['y']));

  // Validate coordinate bounds (prevent extreme coordinates)
  if ($x < -1000 || $x > 1000 || $y < -1000 || $y > 1000) {
    return array('err', 'invalid_coordinates');
  }

  // Basic rate limit: max 5 move requests per second per player (best-effort)
  try {
    if ($rl = $db->prepare("SELECT COUNT(*) AS c FROM game_logs WHERE player_name = ? AND action IN ('move_intent','move_resolved') AND ts > (NOW() - INTERVAL 1 SECOND)")) {
      $rl->bind_param("s", $player_name);
      if ($rl->execute()) {
        $res = $rl->get_result();
        if ($row = $res->fetch_assoc()) {
          if (intval($row['c']) >= 5) { $rl->close(); return array('err', 'rate_limited'); }
        }
      }
      $rl->close();
    }
  } catch (Throwable $_) { /* ignore */ }

  // Heartbeat on movement intent
  touchPlayer($db, $room_id, $player_name);

  // Telemetry: log movement intent (best-effort)
  try {
    $details = json_encode([ 'x' => $x, 'y' => $y ]);
    if ($details === false) { $details = '{"x":'.intval($x).',"y":'.intval($y).'}'; }
    if ($lg = $db->prepare("INSERT INTO game_logs (room_id, player_name, action, details) VALUES (?, ?, 'move_intent', ?)")) {
      $lg->bind_param("iss", $room_id, $player_name, $details);
      $lg->execute();
      $lg->close();
    }
  } catch (Throwable $_) { }

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
        $prevx = intval($row["x"]);
        $prevy = intval($row["y"]);
        $diffx = abs($x - $prevx);
        $diffy = abs($y - $prevy);

        $canMove = true;
        if ($diffx > 0 || $diffy > 0) {
          $sm = $db->prepare("SELECT * 
                FROM game_monsters 
                WHERE x = ? AND y = ? AND room_id = ?");
          $sm->bind_param("iii", $prevx, $prevy, $room_id);

          $arr = array();

          if ($sm->execute()) {
            $smr = $sm->get_result();
            $smrc = mysqli_num_rows($smr);
            if ($smrc > 0) {
              if (rand(1, 100) <= 50) {
                $canMove = false;
              }
            }
          }
          $sm->close();
        }

        if ($canMove) {
          $arr = performMove($db, $diffx, $diffy, $room_id, $x, $y, $monsterSpawnRate, $player_name, $prevx, $prevy);
          // Telemetry: resolved move (ok/err) with dx,dy and draw flag when available
          try {
            $dx = isset($arr[1]) ? intval($arr[1]) : 0; $dy = isset($arr[2]) ? intval($arr[2]) : 0; $res = (isset($arr[0]) && $arr[0] === 'ok') ? 'ok' : 'err';
            $draw = isset($arr[3]) ? strval($arr[3]) : '';
            $det = json_encode([ 'from' => [$prevx,$prevy], 'to' => [$x,$y], 'dx' => $dx, 'dy' => $dy, 'result' => $res, 'draw' => $draw ]);
            if ($det === false) { $det = '{"dx":'.intval($dx).',"dy":'.intval($dy).',"result":"'.($res).'"}'; }
            if ($lg2 = $db->prepare("INSERT INTO game_logs (room_id, player_name, action, details) VALUES (?, ?, 'move_resolved', ?)")) {
              $lg2->bind_param("iss", $room_id, $player_name, $det);
              $lg2->execute();
              $lg2->close();
            }
          } catch (Throwable $_) { }
        } else {
          $data['fight_monster'] = $player_name;
          $arr = fightMonster($db, $data, $itemDropRate); // return structured fight object
          // Telemetry: movement blocked by fight roll
          try {
            $det2 = json_encode([ 'from' => [$prevx,$prevy], 'attempt' => [$x,$y] ]);
            if ($det2 === false) { $det2 = '{"from":['.intval($prevx).','.intval($prevy).'],"attempt":['.intval($x).','.intval($y).']}' ; }
            if ($lg3 = $db->prepare("INSERT INTO game_logs (room_id, player_name, action, details) VALUES (?, ?, 'move_blocked_fight', ?)")) {
              $lg3->bind_param("iss", $room_id, $player_name, $det2);
              $lg3->execute();
              $lg3->close();
            }
          } catch (Throwable $_) { }
        }
      }
    }
  }
  $ss->close();
  return $arr;
}
