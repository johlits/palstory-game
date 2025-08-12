<?php

function getLocation($db, $data)
{
  $room_id = intval(clean($data['get_location']));
  $x = intval(clean($data['x']));
  $y = intval(clean($data['y']));

  $ss = $db->prepare("SELECT * 
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

function getAllLocations($db, $data)
{
  $room_id = intval(clean($data['get_all_locations']));

  $ss = $db->prepare("SELECT * 
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
