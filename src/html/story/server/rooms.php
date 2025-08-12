<?php

function getRoom($db, $data)
{
  $room_name = clean($data['get_room']);

  $ss = $db->prepare("SELECT * 
				FROM game_rooms 
				WHERE name = ?");
  $ss->bind_param("s", $room_name);

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

function purgeRooms($db, $data)
{
  $ss = $db->prepare("SELECT * 
				FROM game_rooms 
				WHERE expiration < NOW()");

  $arr = array();
  if ($ss->execute()) {
    $r = $ss->get_result();
    $rc = mysqli_num_rows($r);
    if ($rc > 0) {
      while ($row = mysqli_fetch_array($r)) {
        $room_id = intval($row["id"]);

        $di = $db->prepare("DELETE FROM game_items 
				WHERE room_id = ?");
        $di->bind_param("i", $room_id);
        if ($di->execute()) {
          $dl = $db->prepare("DELETE FROM game_locations 
				WHERE room_id = ?");
          $dl->bind_param("i", $room_id);
          if ($dl->execute()) {
            $dm = $db->prepare("DELETE FROM game_monsters 
				WHERE room_id = ?");
            $dm->bind_param("i", $room_id);
            if ($dm->execute()) {
              $dp = $db->prepare("DELETE FROM game_players 
				WHERE room_id = ?");
              $dp->bind_param("i", $room_id);
              if ($dp->execute()) {
                $dr = $db->prepare("DELETE FROM game_rooms 
				WHERE id = ?");
                $dr->bind_param("i", $room_id);
                if ($dr->execute()) {
                  array_push($arr, $room_id);
                }
              }
            }
          }
        }
      }
    }
  }
  $ss->close();
  return $arr;
}

function createRoom($db, $data)
{
  $name = clean($data['create_room']);
  $expiration = clean($data['expiration']);
  $regen = intval(clean($data['regen']));

  $arr = array();

  $dexp = new DateTime($expiration);
  $dnow = new DateTime(date('Y-m-d'));
  $dDiff = $dnow->diff($dexp);
  $diffInDays = (int) $dDiff->format("%r%a");
  $daysMin = 1;
  $daysMax = 365;

  if ($diffInDays >= $daysMin && $diffInDays <= $daysMax) {

    $is = $db->prepare("INSERT INTO game_rooms( name, expiration, regen ) 
				VALUES(?, ?, ?)");
    $is->bind_param("ssi", $name, $expiration, $regen);
    if ($is->execute()) {
      array_push($arr, "ok");
    } else {
      array_push($arr, "err");
    }
    $is->close();

  } else {
    array_push($arr, $diffInDays < $daysMin ? "date min " . $daysMin : "date max " . $daysMax);
  }
  return $arr;
}
