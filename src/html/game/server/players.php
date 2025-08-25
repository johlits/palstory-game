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
    $stats = "lvl=1;exp=0;hp=100;maxhp=100;mp=50;maxmp=50;atk=10;def=10;spd=10;evd=10;gold=0;";

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

  $ss = $db->prepare("SELECT gi.id, gi.stats, gi.equipped, ri.name, ri.image, ri.description 
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

  $di = $db->prepare("DELETE FROM game_items WHERE id = ? AND owner_id = ?");
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

  $se = $db->prepare("SELECT * 
				FROM game_items WHERE owner_id = ?");
  $se->bind_param("i", $player_id);

  $item_type = '';
  if ($se->execute()) {
    $r = $se->get_result();
    $rc = mysqli_num_rows($r);
    if ($rc > 0) {
      while ($row = mysqli_fetch_array($r)) {
        $item_stats_parts = explode(';', $row["stats"]);
        for ($i = 0; $i < count($item_stats_parts); $i++) {
          if (str_starts_with($item_stats_parts[$i], "type=")) {

            if (intval($row["id"]) == $item_id) {
              $item_type = explode('=', $item_stats_parts[$i])[1];
            } else if (intval($row["equipped"]) == 1) {
              array_push($ids, intval($row["id"]));
              array_push($types, explode('=', $item_stats_parts[$i])[1]);
            }
          }
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
