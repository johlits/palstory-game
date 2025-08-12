<?php
header("Access-Control-Allow-Origin: *");

require_once "./config.php";

function deleteItem($db, $data) {
  $resource_id = intval(clean($data['delete_item']));
  $secret = clean($data['secret']);
  $arr = array();
  if ($secret == admin_game()) {
    $selectstmt = $db->prepare("SELECT * FROM resources_items WHERE banned = 1 
				AND id = ?");
    $selectstmt->bind_param("i", $resource_id);
    if ($selectstmt->execute()) {
      $result = $selectstmt->get_result();
      $rc = mysqli_num_rows($result);
      if ($rc > 0) {
        $deletestmt = $db->prepare("DELETE FROM resources_items 
				WHERE id = ?");
        $deletestmt->bind_param("i", $resource_id);
        if ($deletestmt->execute()) {
          array_push($arr, "ok");
        } else {
          array_push($arr, "err");
        }
        $deletestmt->close();
      }
      else {
        array_push($arr, "not found");
      }
    }
    $selectstmt->close();
  } else {
    array_push($arr, "unauthorized");
  }
  return $arr;
}

function deleteMonster($db, $data) {
  $resource_id = intval(clean($data['delete_monster']));
  $secret = clean($data['secret']);
  $arr = array();
  if ($secret == admin_game()) {
    $selectstmt = $db->prepare("SELECT * FROM resources_monsters WHERE banned = 1 
				AND id = ?");
    $selectstmt->bind_param("i", $resource_id);
    if ($selectstmt->execute()) {
      $result = $selectstmt->get_result();
      $rc = mysqli_num_rows($result);
      if ($rc > 0) {
        $deletestmt = $db->prepare("DELETE FROM resources_monsters 
				WHERE id = ?");
        $deletestmt->bind_param("i", $resource_id);
        if ($deletestmt->execute()) {
          array_push($arr, "ok");
        } else {
          array_push($arr, "err");
        }
        $deletestmt->close();
      }
      else {
        array_push($arr, "not found");
      }
    }
    $selectstmt->close();
  } else {
    array_push($arr, "unauthorized");
  }
  return $arr;
}

function deleteLocation($db, $data) {
  $resource_id = intval(clean($data['delete_location']));
  $secret = clean($data['secret']);
  $arr = array();
  if ($secret == admin_game()) {
    $selectstmt = $db->prepare("SELECT * FROM resources_locations WHERE banned = 1 
				AND id = ?");
    $selectstmt->bind_param("i", $resource_id);
    if ($selectstmt->execute()) {
      $result = $selectstmt->get_result();
      $rc = mysqli_num_rows($result);
      if ($rc > 0) {
        $deletestmt = $db->prepare("DELETE FROM resources_locations 
				WHERE id = ?");
        $deletestmt->bind_param("i", $resource_id);
        if ($deletestmt->execute()) {
          array_push($arr, "ok");
        } else {
          array_push($arr, "err");
        }
        $deletestmt->close();
      }
      else {
        array_push($arr, "not found");
      }
    }
    $selectstmt->close();
  } else {
    array_push($arr, "unauthorized");
  }
  return $arr;
}

function banItem($db, $data) {
  $resource_id = intval(clean($data['ban_item']));
  $secret = clean($data['secret']);
  $arr = array();
  if ($secret == super_admin_game()) {
    $updatestmt = $db->prepare("UPDATE resources_items SET banned = 1 
				WHERE id = ?");
    $updatestmt->bind_param("i", $resource_id);
    if ($updatestmt->execute()) {
      array_push($arr, "ok");
    } else {
      array_push($arr, "err");
    }
    $updatestmt->close();
  } else {
    array_push($arr, "unauthorized");
  }
  return $arr;
}

function banMonster($db, $data) {
  $resource_id = intval(clean($data['ban_monster']));
  $secret = clean($data['secret']);
  $arr = array();
  if ($secret == super_admin_game()) {
    $updatestmt = $db->prepare("UPDATE resources_monsters SET banned = 1 
				WHERE id = ?");
    $updatestmt->bind_param("i", $resource_id);
    if ($updatestmt->execute()) {
      array_push($arr, "ok");
    } else {
      array_push($arr, "err");
    }
    $updatestmt->close();
  } else {
    array_push($arr, "unauthorized");
  }
  return $arr;
}

function banLocation($db, $data) {
  $resource_id = intval(clean($data['ban_location']));
  $secret = clean($data['secret']);
  $arr = array();
  if ($secret == super_admin_game()) {
    $updatestmt = $db->prepare("UPDATE resources_locations SET banned = 1 
				WHERE id = ?");
    $updatestmt->bind_param("i", $resource_id);
    if ($updatestmt->execute()) {
      array_push($arr, "ok");
    } else {
      array_push($arr, "err");
    }
    $updatestmt->close();
  } else {
    array_push($arr, "unauthorized");
  }
  return $arr;
}

function itemName($db, $data, $min_name_length, $max_name_length, $min_image_length, $max_image_length, $min_description_length) {
  $item_name = clean($data['item_name']);
  $item_image = clean($data['item_image']);
  $item_description = clean($data['item_description']);
  $item_stats = clean($data['item_stats']);

  $selectstmt = $db->prepare("SELECT * 
				FROM resources_items 
				WHERE name = ?");
  $selectstmt->bind_param("s", $item_name);

  $arr = array();

  $stats_parts = explode(";", $item_stats);
  for ($i = 0; $i < count($stats_parts); $i++) {
    if (str_starts_with($stats_parts[$i], "atk=")) {
      $atk = explode('=', $stats_parts[$i])[1];
      $atk_parts = explode("-", $atk);
      if (count($atk_parts) == 2) {
        if (!is_numeric($atk_parts[0]) || !is_numeric($atk_parts[1])) {
          unset($atk);
        }
      } else if (count($atk_parts) == 1) {
        if (!is_numeric($atk)) {
          unset($atk);
        }
      } else {
        unset($atk);
      }
    } else if (str_starts_with($stats_parts[$i], "def=")) {
      $def = explode('=', $stats_parts[$i])[1];
      $def_parts = explode("-", $def);
      if (count($def_parts) == 2) {
        if (!is_numeric($def_parts[0]) || !is_numeric($def_parts[1])) {
          unset($def);
        }
      } else if (count($def_parts) == 1) {
        if (!is_numeric($def)) {
          unset($def);
        }
      } else {
        unset($def);
      }
    } else if (str_starts_with($stats_parts[$i], "spd=")) {
      $spd = explode('=', $stats_parts[$i])[1];
      $spd_parts = explode("-", $spd);
      if (count($spd_parts) == 2) {
        if (!is_numeric($spd_parts[0]) || !is_numeric($spd_parts[1])) {
          unset($spd);
        }
      } else if (count($spd_parts) == 1) {
        if (!is_numeric($spd)) {
          unset($spd);
        }
      } else {
        unset($spd);
      }
    } else if (str_starts_with($stats_parts[$i], "evd=")) {
      $evd = explode('=', $stats_parts[$i])[1];
      $evd_parts = explode("-", $evd);
      if (count($evd_parts) == 2) {
        if (!is_numeric($evd_parts[0]) || !is_numeric($evd_parts[1])) {
          unset($evd);
        }
      } else if (count($evd_parts) == 1) {
        if (!is_numeric($evd)) {
          unset($evd);
        }
      } else {
        unset($evd);
      }
    } else if (str_starts_with($stats_parts[$i], "type=")) {
      $type = strtolower(explode('=', $stats_parts[$i])[1]);
      if (strlen($type) < 1) {
        unset($type);
      }
    } else if (trim($stats_parts[$i]) != '') {
      $unknown_parameter = $stats_parts[$i];
    }
  }

  // VALIDATION HERE
  if (isset($unknown_parameter)) {
    array_push($arr, "unknown parameter " . $unknown_parameter);
  } else if (!isset($atk)) {
    array_push($arr, "atk must be set");
  } else if (!isset($def)) {
    array_push($arr, "def must be set");
  } else if (!isset($spd)) {
    array_push($arr, "spd must be set");
  } else if (!isset($evd)) {
    array_push($arr, "evd must be set");
  } else if (!isset($type)) {
    array_push($arr, "type must be set");
  } else if (strlen($item_name) < $min_name_length) {
    array_push($arr, "name too short (min " . $min_name_length . ")");
  } else if (strlen($item_name) > $max_name_length) {
    array_push($arr, "name too long (max " . $max_name_length . ")");
  } else if (strlen($item_image) < $min_image_length) {
    array_push($arr, "no image provided");
  } else if (strlen($item_image) > $max_image_length) {
    array_push($arr, "image name too long (max " . $max_image_length . ")");
  } else if (strlen($item_description) < $min_description_length) {
    array_push($arr, "description too short (min " . $min_description_length . ")");
  } else if (!file_exists('uploads/' . $item_image)) {
    array_push($arr, "no such image");
  } else if ($selectstmt->execute()) {
    $result = $selectstmt->get_result();
    $row_count = mysqli_num_rows($result);

    if ($row_count == 0) {
      $insertstmt = $db->prepare("INSERT INTO resources_items(name, image, description, stats) 
				VALUES(?, ?, ?, ?)");
      $insertstmt->bind_param("ssss", $item_name, $item_image, $item_description, $item_stats);
      if ($insertstmt->execute()) {
        array_push($arr, "ok");
      }
      $insertstmt->close();
    } else {
      array_push($arr, "already exists");
    }
  }
  $selectstmt->close();
  return $arr;
}

function monsterName($db, $data, $min_name_length, $max_name_length, $min_image_length, $max_image_length, $min_description_length) {
  $monster_name = clean($data['monster_name']);
  $monster_image = clean($data['monster_image']);
  $monster_description = clean($data['monster_description']);
  $monster_stats = clean($data['monster_stats']);

  $selectstmt = $db->prepare("SELECT * 
				FROM resources_monsters 
				WHERE name = ?");
  $selectstmt->bind_param("s", $monster_name);

  $arr = array();
  $drops = '';
  $atkMax = 0;
  $defMax = 0;
  $spdMax = 0;
  $evdMax = 0;
  $hpMax = 0;
  $goldMax = 0;
  $expMax = 0;

  // VALIDATION HERE
  $stats_parts = explode(";", $monster_stats);
  for ($i = 0; $i < count($stats_parts); $i++) {
    if (str_starts_with($stats_parts[$i], "atk=")) {
      $atk = explode('=', $stats_parts[$i])[1];
      $atk_parts = explode("-", $atk);
      if (count($atk_parts) == 2) {
        if (!is_numeric($atk_parts[0]) || !is_numeric($atk_parts[1])) {
          unset($atk);
        } else {
          $atkMax = intval($atk_parts[1]);
        }
      } else if (count($atk_parts) == 1) {
        if (!is_numeric($atk)) {
          unset($atk);
        } else {
          $atkMax = intval($atk);
        }
      } else {
        unset($atk);
      }
    } else if (str_starts_with($stats_parts[$i], "def=")) {
      $def = explode('=', $stats_parts[$i])[1];
      $def_parts = explode("-", $def);
      if (count($def_parts) == 2) {
        if (!is_numeric($def_parts[0]) || !is_numeric($def_parts[1])) {
          unset($def);
        } else {
          $defMax = intval($def_parts[1]);
        }
      } else if (count($def_parts) == 1) {
        if (!is_numeric($def)) {
          unset($def);
        } else {
          $defMax = intval($def);
        }
      } else {
        unset($def);
      }
    } else if (str_starts_with($stats_parts[$i], "spd=")) {
      $spd = explode('=', $stats_parts[$i])[1];
      $spd_parts = explode("-", $spd);
      if (count($spd_parts) == 2) {
        if (!is_numeric($spd_parts[0]) || !is_numeric($spd_parts[1])) {
          unset($spd);
        } else {
          $spdMax = intval($spd_parts[1]);
        }
      } else if (count($spd_parts) == 1) {
        if (!is_numeric($spd)) {
          unset($spd);
        } else {
          $spdMax = intval($spd);
        }
      } else {
        unset($spd);
      }
    } else if (str_starts_with($stats_parts[$i], "evd=")) {
      $evd = explode('=', $stats_parts[$i])[1];
      $evd_parts = explode("-", $evd);
      if (count($evd_parts) == 2) {
        if (!is_numeric($evd_parts[0]) || !is_numeric($evd_parts[1])) {
          unset($evd);
        } else {
          $evdMax = intval($evd_parts[1]);
        }
      } else if (count($evd_parts) == 1) {
        if (!is_numeric($evd)) {
          unset($evd);
        } else {
          $evdMax = intval($evd);
        }
      } else {
        unset($evd);
      }
    } else if (str_starts_with($stats_parts[$i], "hp=")) {
      $hp = explode('=', $stats_parts[$i])[1];
      $hp_parts = explode("-", $hp);
      if (count($hp_parts) == 2) {
        if (!is_numeric($hp_parts[0]) || !is_numeric($hp_parts[1])) {
          unset($hp);
        } else {
          $hpMax = intval($hp_parts[1]);
        }
      } else if (count($hp_parts) == 1) {
        if (!is_numeric($hp)) {
          unset($hp);
        } else {
          $hpMax = intval($hp);
        }
      } else {
        unset($hp);
      }
    } else if (str_starts_with($stats_parts[$i], "gold=")) {
      $gold = explode('=', $stats_parts[$i])[1];
      $gold_parts = explode("-", $gold);
      if (count($gold_parts) == 2) {
        if (!is_numeric($gold_parts[0]) || !is_numeric($gold_parts[1])) {
          unset($gold);
        } else {
          $goldMax = intval($gold_parts[1]);
        }
      } else if (count($gold_parts) == 1) {
        if (!is_numeric($gold)) {
          unset($gold);
        } else {
          $goldMax = intval($gold);
        }
      } else {
        unset($gold);
      }
    } else if (str_starts_with($stats_parts[$i], "exp=")) {
      $exp = explode('=', $stats_parts[$i])[1];
      if (!is_numeric($exp)) {
        unset($exp);
      } else {
        $expMax = intval($exp);
      }
    } else if (str_starts_with($stats_parts[$i], "drops=")) {
      $drops = explode('=', $stats_parts[$i])[1];
      $drops_parts = explode(',', $drops);

      // check strength of every drop
      for ($j = 0; $j < count($drops_parts); $j++) {
        $se = $db->prepare("SELECT * 
				FROM resources_items WHERE name = ?");
        $drop_name = $drops_parts[$j];
        $se->bind_param("s", $drop_name);

        if ($se->execute()) {
          $r = $se->get_result();
          $rc = mysqli_num_rows($r);
          if ($rc > 0) {
            while ($irow = mysqli_fetch_array($r)) {
              $item_stats_parts = explode(';', $irow["stats"]);
              for ($k = 0; $k < count($item_stats_parts); $k++) {
                if (str_starts_with($item_stats_parts[$k], "atk=")) {
                  $iAtkStat = explode('=', $item_stats_parts[$k])[1];
                  $iAtkParts = explode("-", $iAtkStat);
                  if (count($iAtkParts) == 2) {
                    $iAtk = intval($iAtkParts[1]);
                  } else if (count($iAtkParts) == 1) {
                    $iAtk = intval($iAtkStat);
                  } else {
                    $iAtk = 0;
                  }
                }
                if (str_starts_with($item_stats_parts[$k], "def=")) {
                  $iDefStat = explode('=', $item_stats_parts[$k])[1];
                  $iDefParts = explode("-", $iDefStat);
                  if (count($iDefParts) == 2) {
                    $iDef = intval($iDefParts[1]);
                  } else if (count($iDefParts) == 1) {
                    $iDef = intval($iDefStat);
                  } else {
                    $iDef = 0;
                  }
                }
                if (str_starts_with($item_stats_parts[$k], "spd=")) {
                  $iSpdStat = explode('=', $item_stats_parts[$k])[1];
                  $iSpdParts = explode("-", $iSpdStat);
                  if (count($iSpdParts) == 2) {
                    $iSpd = intval($iSpdParts[1]);
                  } else if (count($iSpdParts) == 1) {
                    $iSpd = intval($iSpdStat);
                  } else {
                    $iSpd = 0;
                  }
                }
                if (str_starts_with($item_stats_parts[$k], "evd=")) {
                  $iEvdStat = explode('=', $item_stats_parts[$k])[1];
                  $iEvdParts = explode("-", $iEvdStat);
                  if (count($iEvdParts) == 2) {
                    $iEvd = intval($iEvdParts[1]);
                  } else if (count($iEvdParts) == 1) {
                    $iEvd = intval($iEvdStat);
                  } else {
                    $iEvd = 0;
                  }
                }
              }

              $monster_strength = $atkMax + $defMax + $spdMax + $evdMax + ($hpMax / 10);
              $drop_strength = $iAtk + $iDef + $iSpd + $iEvd;
              if ($drop_strength > $monster_strength) {
                $invalid_drop = $drop_name;
              }

            }
          } else {
            $unknown_drop = $drop_name;
          }
        }
        $se->close();
      }
    } else if (trim($stats_parts[$i]) != '') {
      $unknown_parameter = $stats_parts[$i];
    }
  }

  $monster_strength = $atkMax + $defMax + $spdMax + $evdMax + ($hpMax / 10);

  // VALIDATION HERE
  if (isset($unknown_parameter)) {
    array_push($arr, "unknown parameter " . $unknown_parameter);
  } else if ($goldMax > $monster_strength * 0.5) {
    array_push($arr, "gold too high (max: " . (intval($monster_strength * 0.5)) . ")");
  } else if ($expMax > $monster_strength * 0.5) {
    array_push($arr, "exp too high (max: " . (intval($monster_strength * 0.5)) . ")");
  } else if (isset($unknown_drop)) {
    array_push($arr, "unknown drop " . $unknown_drop);
  } else if (isset($invalid_drop)) {
    array_push($arr, "overpowered drop " . $invalid_drop);
  } else if (!isset($atk)) {
    array_push($arr, "atk must be set");
  } else if (!isset($def)) {
    array_push($arr, "def must be set");
  } else if (!isset($spd)) {
    array_push($arr, "spd must be set");
  } else if (!isset($evd)) {
    array_push($arr, "evd must be set");
  } else if (!isset($hp)) {
    array_push($arr, "hp must be set");
  } else if (!isset($gold)) {
    array_push($arr, "gold must be set");
  } else if (!isset($exp)) {
    array_push($arr, "exp must be set");
  } else if (!isset($drops)) {
    array_push($arr, "drops must be set");
  } else if (strlen($monster_name) < $min_name_length) {
    array_push($arr, "name too short (min " . $min_name_length . ")");
  } else if (strlen($monster_name) > $max_name_length) {
    array_push($arr, "name too long (max " . $max_name_length . ")");
  } else if (strlen($monster_image) < $min_image_length) {
    array_push($arr, "no image provided");
  } else if (strlen($monster_image) > $max_image_length) {
    array_push($arr, "image name too long (max " . $max_image_length . ")");
  } else if (strlen($monster_description) < $min_description_length) {
    array_push($arr, "description too short (min " . $min_description_length . ")");
  } else if (!file_exists('uploads/' . $monster_image)) {
    array_push($arr, "no such image");
  } else if ($selectstmt->execute()) {
    $result = $selectstmt->get_result();
    $row_count = mysqli_num_rows($result);

    if ($row_count == 0) {
      $insertstmt = $db->prepare("INSERT INTO resources_monsters(name, image, description, stats) 
				VALUES(?, ?, ?, ?)");
      $insertstmt->bind_param("ssss", $monster_name, $monster_image, $monster_description, $monster_stats);
      if ($insertstmt->execute()) {
        array_push($arr, "ok");
      }
      $insertstmt->close();
    } else {
      array_push($arr, "already exists");
    }
  }
  $selectstmt->close();
  return $arr;
}

function locationName($db, $data, $min_name_length, $max_name_length, $min_image_length, $max_image_length, $min_description_length) {
  $location_name = clean($data['location_name']);
  $location_image = clean($data['location_image']);
  $location_description = clean($data['location_description']);
  $location_from = clean($data['location_from']);
  $location_to = clean($data['location_to']);
  $location_stats = clean($data['location_stats']);

  $selectstmt = $db->prepare("SELECT * 
				FROM resources_locations 
				WHERE name = ?");
  $selectstmt->bind_param("s", $location_name);

  $arr = array();

  // VALIDATION HERE

  $spawns = '';

  $stats_parts = explode(";", $location_stats);
  for ($i = 0; $i < count($stats_parts); $i++) {

    if (str_starts_with($stats_parts[$i], "spawns=")) {
      $spawnsVal = explode('=', $stats_parts[$i])[1];
      $minLvl = intval($location_from);
      // check strength of every monster

      $spawns_parts = explode(',', $spawnsVal);

      for ($j = 0; $j < count($spawns_parts); $j++) {
        $se = $db->prepare("SELECT * 
				FROM resources_monsters WHERE name = ?");
        $spawn_name = $spawns_parts[$j];
        $se->bind_param("s", $spawn_name);

        if ($se->execute()) {
          $r = $se->get_result();
          $rc = mysqli_num_rows($r);
          if ($rc > 0) {
            while ($mrow = mysqli_fetch_array($r)) {
              $monster_stats_parts = explode(';', $mrow["stats"]);
              for ($k = 0; $k < count($monster_stats_parts); $k++) {
                if (str_starts_with($monster_stats_parts[$k], "atk=")) {
                  $mAtkStat = explode('=', $monster_stats_parts[$k])[1];
                  $mAtkParts = explode("-", $mAtkStat);
                  if (count($mAtkParts) == 2) {
                    $mAtk = intval($mAtkParts[1]);
                  } else if (count($mAtkParts) == 1) {
                    $mAtk = intval($mAtkStat);
                  } else {
                    $mAtk = 0;
                  }
                }
                if (str_starts_with($monster_stats_parts[$k], "def=")) {
                  $mDefStat = explode('=', $monster_stats_parts[$k])[1];
                  $mDefParts = explode("-", $mDefStat);
                  if (count($mDefParts) == 2) {
                    $mDef = intval($mDefParts[1]);
                  } else if (count($mDefParts) == 1) {
                    $mDef = intval($mDefStat);
                  } else {
                    $mDef = 0;
                  }
                }
                if (str_starts_with($monster_stats_parts[$k], "spd=")) {
                  $mSpdStat = explode('=', $monster_stats_parts[$k])[1];
                  $mSpdParts = explode("-", $mSpdStat);
                  if (count($mSpdParts) == 2) {
                    $mSpd = intval($mSpdParts[1]);
                  } else if (count($mSpdParts) == 1) {
                    $mSpd = intval($mSpdStat);
                  } else {
                    $mSpd = 0;
                  }
                }
                if (str_starts_with($monster_stats_parts[$k], "evd=")) {
                  $mEvdStat = explode('=', $monster_stats_parts[$k])[1];
                  $mEvdParts = explode("-", $mEvdStat);
                  if (count($mEvdParts) == 2) {
                    $mEvd = intval($mEvdParts[1]);
                  } else if (count($mEvdParts) == 1) {
                    $mEvd = intval($mEvdStat);
                  } else {
                    $mEvd = 0;
                  }
                }
                if (str_starts_with($monster_stats_parts[$k], "hp=")) {
                  $mHpStat = explode('=', $monster_stats_parts[$k])[1];
                  $mHpParts = explode("-", $mHpStat);
                  if (count($mHpParts) == 2) {
                    $mHp = intval($mHpParts[1]);
                  } else if (count($mHpParts) == 1) {
                    $mHp = intval($mHpStat);
                  } else {
                    $mHp = 0;
                  }
                }
              }

              $monster_strength = $mAtk + $mDef + $mSpd + $mEvd + ($mHp / 10);
              if ($monster_strength * 0.03 > $minLvl) {
                $invalid_monster = $spawn_name;
              }

            }
          } else {
            $unknown_monster = $spawn_name;
          }
        }
        $se->close();
      }
    } else if (trim($stats_parts[$i]) != '') {
      $unknown_parameter = $stats_parts[$i];
    }
  }

  if (isset($unknown_parameter)) {
    array_push($arr, "unknown parameter " . $unknown_parameter);
  } else if (isset($unknown_monster)) {
    array_push($arr, "unknown monster " . $unknown_monster);
  } else if (isset($invalid_monster)) {
    array_push($arr, "overpowered monster " . $invalid_monster);
  } else if (!isset($spawns)) {
    array_push($arr, "spawns must be set");
  } else if (strlen($location_name) < $min_name_length) {
    array_push($arr, "name too short (min " . $min_name_length . ")");
  } else if (strlen($location_name) > $max_name_length) {
    array_push($arr, "name too long (max " . $max_name_length . ")");
  } else if (strlen($location_image) < $min_image_length) {
    array_push($arr, "no image provided");
  } else if (strlen($location_image) > $max_image_length) {
    array_push($arr, "image name too long (max " . $max_image_length . ")");
  } else if (strlen($location_description) < $min_description_length) {
    array_push($arr, "description too short (min " . $min_description_length . ")");
  } else if ((int) $location_from > (int) $location_to) {
    array_push($arr, "invalid level range");
  } else if (!file_exists('uploads/' . $location_image)) {
    array_push($arr, "no such image");
  } else if ($selectstmt->execute()) {
    $result = $selectstmt->get_result();
    $row_count = mysqli_num_rows($result);

    if ($row_count == 0) {
      $insertstmt = $db->prepare("INSERT INTO resources_locations(name, image, description, lvl_from, lvl_to, stats) 
				VALUES(?, ?, ?, ?, ?, ?)");
      $insertstmt->bind_param("sssiis", $location_name, $location_image, $location_description, $location_from, $location_to, $location_stats);
      if ($insertstmt->execute()) {
        array_push($arr, "ok");
      }
      else {
        array_push($arr, "err");
      }
      $insertstmt->close();
    } else {
      array_push($arr, "already exists");
    }
  }
  $selectstmt->close();
  return $arr;
}

// API

$data = $_REQUEST;
$min_name_length = 2;
$max_name_length = 64;
$min_description_length = 16;
$min_image_length = 1;
$max_image_length = 64;

if (isset($data['delete_item'])) {
  echo json_encode(deleteItem($db, $data));
} else if (isset($data['delete_monster'])) {
  echo json_encode(deleteMonster($db, $data));
} else if (isset($data['delete_location'])) {
  echo json_encode(deleteLocation($db, $data));
} else if (isset($data['ban_item'])) { 
  echo json_encode(banItem($db, $data));
} else if (isset($data['ban_monster'])) {
  echo json_encode(banMonster($db, $data));
} else if (isset($data['ban_location'])) {
  echo json_encode(banLocation($db, $data));
} else if (isset($data['item_name'])) {
  echo json_encode(itemName($db, $data, $min_name_length, $max_name_length, $min_image_length, $max_image_length, $min_description_length));
} else if (isset($data['monster_name'])) {
  echo json_encode(monsterName($db, $data, $min_name_length, $max_name_length, $min_image_length, $max_image_length, $min_description_length));
} else if (isset($data['location_name'])) {
  echo json_encode(locationName($db, $data, $min_name_length, $max_name_length, $min_image_length, $max_image_length, $min_description_length));
}

mysqli_close($db);

?>