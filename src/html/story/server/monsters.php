<?php

function getMonster($db, $data)
{
  $room_id = intval(clean($data['get_monster']));
  $x = intval(clean($data['x']));
  $y = intval(clean($data['y']));

  $ss = $db->prepare("SELECT rm.name, rm.description, gm.stats, rm.image, gm.id  
				FROM game_monsters gm INNER JOIN resources_monsters rm ON gm.resource_id = rm.id 
				WHERE gm.room_id = ? AND gm.x = ? AND gm.y = ?");
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
