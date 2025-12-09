<?php
header("Access-Control-Allow-Origin: *");

require_once "./config.php";

// Delete and Ban functions - execute directly to database

function deleteItem($db, $data) {
  $resource_id = intval(clean($data['delete_item']));
  $secret = clean($data['secret']);
  $arr = array();
  if ($secret == admin_game_hash()) {
    $stmt = $db->prepare("DELETE FROM resources_items WHERE id = ?");
    $stmt->bind_param("i", $resource_id);
    if ($stmt->execute()) {
      array_push($arr, "deleted");
    } else {
      array_push($arr, "delete failed: " . $db->error);
    }
    $stmt->close();
  } else {
    array_push($arr, "unauthorized");
  }
  return $arr;
}

function deleteMonster($db, $data) {
  $resource_id = intval(clean($data['delete_monster']));
  $secret = clean($data['secret']);
  $arr = array();
  if ($secret == admin_game_hash()) {
    $stmt = $db->prepare("DELETE FROM resources_monsters WHERE id = ?");
    $stmt->bind_param("i", $resource_id);
    if ($stmt->execute()) {
      array_push($arr, "deleted");
    } else {
      array_push($arr, "delete failed: " . $db->error);
    }
    $stmt->close();
  } else {
    array_push($arr, "unauthorized");
  }
  return $arr;
}

function deleteLocation($db, $data) {
  $resource_id = intval(clean($data['delete_location']));
  $secret = clean($data['secret']);
  $arr = array();
  if ($secret == admin_game_hash()) {
    $stmt = $db->prepare("DELETE FROM resources_locations WHERE id = ?");
    $stmt->bind_param("i", $resource_id);
    if ($stmt->execute()) {
      array_push($arr, "deleted");
    } else {
      array_push($arr, "delete failed: " . $db->error);
    }
    $stmt->close();
  } else {
    array_push($arr, "unauthorized");
  }
  return $arr;
}

function banItem($db, $data) {
  $resource_id = intval(clean($data['ban_item']));
  $secret = clean($data['secret']);
  $arr = array();
  if ($secret == super_admin_game_hash()) {
    $stmt = $db->prepare("UPDATE resources_items SET banned = 1 WHERE id = ?");
    $stmt->bind_param("i", $resource_id);
    if ($stmt->execute()) {
      array_push($arr, "banned");
    } else {
      array_push($arr, "ban failed: " . $db->error);
    }
    $stmt->close();
  } else {
    array_push($arr, "unauthorized");
  }
  return $arr;
}

function banMonster($db, $data) {
  $resource_id = intval(clean($data['ban_monster']));
  $secret = clean($data['secret']);
  $arr = array();
  if ($secret == super_admin_game_hash()) {
    $stmt = $db->prepare("UPDATE resources_monsters SET banned = 1 WHERE id = ?");
    $stmt->bind_param("i", $resource_id);
    if ($stmt->execute()) {
      array_push($arr, "banned");
    } else {
      array_push($arr, "ban failed: " . $db->error);
    }
    $stmt->close();
  } else {
    array_push($arr, "unauthorized");
  }
  return $arr;
}

function banLocation($db, $data) {
  $resource_id = intval(clean($data['ban_location']));
  $secret = clean($data['secret']);
  $arr = array();
  if ($secret == super_admin_game_hash()) {
    $stmt = $db->prepare("UPDATE resources_locations SET banned = 1 WHERE id = ?");
    $stmt->bind_param("i", $resource_id);
    if ($stmt->execute()) {
      array_push($arr, "banned");
    } else {
      array_push($arr, "ban failed: " . $db->error);
    }
    $stmt->close();
  } else {
    array_push($arr, "unauthorized");
  }
  return $arr;
}

// Job functions
function deleteJob($db, $data) {
  $resource_id = intval(clean($data['delete_job']));
  $secret = clean($data['secret']);
  $arr = array();
  if ($secret == admin_game_hash()) {
    $stmt = $db->prepare("DELETE FROM resources_jobs WHERE id = ?");
    $stmt->bind_param("i", $resource_id);
    if ($stmt->execute()) {
      array_push($arr, "deleted");
    } else {
      array_push($arr, "delete failed: " . $db->error);
    }
    $stmt->close();
  } else {
    array_push($arr, "unauthorized");
  }
  return $arr;
}

function banJob($db, $data) {
  $resource_id = intval(clean($data['ban_job']));
  $secret = clean($data['secret']);
  $arr = array();
  if ($secret == super_admin_game_hash()) {
    $stmt = $db->prepare("UPDATE resources_jobs SET banned = 1 WHERE id = ?");
    $stmt->bind_param("i", $resource_id);
    if ($stmt->execute()) {
      array_push($arr, "banned");
    } else {
      array_push($arr, "ban failed: " . $db->error);
    }
    $stmt->close();
  } else {
    array_push($arr, "unauthorized");
  }
  return $arr;
}

// Skill functions
function deleteSkill($db, $data) {
  $resource_id = intval(clean($data['delete_skill']));
  $secret = clean($data['secret']);
  $arr = array();
  if ($secret == admin_game_hash()) {
    $stmt = $db->prepare("DELETE FROM resources_skills WHERE id = ?");
    $stmt->bind_param("i", $resource_id);
    if ($stmt->execute()) {
      array_push($arr, "deleted");
    } else {
      array_push($arr, "delete failed: " . $db->error);
    }
    $stmt->close();
  } else {
    array_push($arr, "unauthorized");
  }
  return $arr;
}

function banSkill($db, $data) {
  $resource_id = intval(clean($data['ban_skill']));
  $secret = clean($data['secret']);
  $arr = array();
  if ($secret == super_admin_game_hash()) {
    $stmt = $db->prepare("UPDATE resources_skills SET banned = 1 WHERE id = ?");
    $stmt->bind_param("i", $resource_id);
    if ($stmt->execute()) {
      array_push($arr, "banned");
    } else {
      array_push($arr, "ban failed: " . $db->error);
    }
    $stmt->close();
  } else {
    array_push($arr, "unauthorized");
  }
  return $arr;
}

// Monster Skill functions
function deleteMonsterSkill($db, $data) {
  $id = intval(clean($data['delete_monster_skill']));
  $secret = clean($data['secret']);
  $arr = array();
  if ($secret == admin_game_hash()) {
    $stmt = $db->prepare("DELETE FROM monster_skills WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
      array_push($arr, "deleted");
    } else {
      array_push($arr, "delete failed: " . $db->error);
    }
    $stmt->close();
  } else {
    array_push($arr, "unauthorized");
  }
  return $arr;
}

// Shop Inventory functions
function deleteShopItem($db, $data) {
  $id = intval(clean($data['delete_shop_item']));
  $secret = clean($data['secret']);
  $arr = array();
  if ($secret == admin_game_hash()) {
    $stmt = $db->prepare("DELETE FROM shop_inventory WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
      array_push($arr, "deleted");
    } else {
      array_push($arr, "delete failed: " . $db->error);
    }
    $stmt->close();
  } else {
    array_push($arr, "unauthorized");
  }
  return $arr;
}

function shopItemName($db, $data) {
  $item_id = clean($data['shop_item_id']);
  $price = intval(clean($data['shop_price']));
  $stock_unlimited = intval(clean($data['shop_stock_unlimited']));
  $available_level = intval(clean($data['shop_available_level']));
  $category = clean($data['shop_category']);
  $edit_id = intval(clean($data['shop_edit_id']));

  $arr = array();

  // Validation
  if (empty($item_id)) {
    array_push($arr, "Please select an item");
  } else if ($price < 1) {
    array_push($arr, "Price must be at least 1");
  } else if ($available_level < 1 || $available_level > 100) {
    array_push($arr, "Available level must be between 1 and 100");
  } else if (!in_array($category, ['general', 'weapon', 'armor', 'shield', 'consumable'])) {
    array_push($arr, "Invalid category");
  } else {
    $escaped_item_id = mysqli_real_escape_string($db, $item_id);
    $escaped_category = mysqli_real_escape_string($db, $category);

    if ($edit_id > 0) {
      // Execute UPDATE directly for existing shop item
      $updatestmt = $db->prepare("UPDATE shop_inventory SET item_id = ?, price = ?, stock_unlimited = ?, available_at_level = ?, category = ? WHERE id = ?");
      $updatestmt->bind_param("siiisi", $item_id, $price, $stock_unlimited, $available_level, $category, $edit_id);
      if ($updatestmt->execute()) {
        array_push($arr, "updated");
      } else {
        array_push($arr, "update failed: " . $db->error);
      }
      $updatestmt->close();
    } else {
      // Check for duplicate
      $checkstmt = $db->prepare("SELECT id FROM shop_inventory WHERE item_id = ?");
      $checkstmt->bind_param("s", $item_id);
      $checkstmt->execute();
      $result = $checkstmt->get_result();
      
      if (mysqli_num_rows($result) > 0) {
        array_push($arr, "This item is already in the shop");
      } else {
        // Execute INSERT directly for new shop item
        $insertstmt = $db->prepare("INSERT INTO shop_inventory(item_id, price, stock_unlimited, available_at_level, category) VALUES(?, ?, ?, ?, ?)");
        $insertstmt->bind_param("siiis", $item_id, $price, $stock_unlimited, $available_level, $category);
        if ($insertstmt->execute()) {
          array_push($arr, "created");
        } else {
          array_push($arr, "insert failed: " . $db->error);
        }
        $insertstmt->close();
      }
      $checkstmt->close();
    }
  }
  return $arr;
}

function monsterSkillName($db, $data) {
  $monster_id = intval(clean($data['mskill_monster_id']));
  $skill_id = clean($data['mskill_skill_id']);
  $image = clean($data['mskill_image']);
  $edit_id = intval(clean($data['mskill_edit_id']));

  $arr = array();

  // Validation
  if ($monster_id <= 0) {
    array_push($arr, "Please select a monster");
  } else if (empty($skill_id)) {
    array_push($arr, "Please select a skill");
  } else {
    $escaped_skill_id = mysqli_real_escape_string($db, $skill_id);
    $escaped_image = $image ? "'" . mysqli_real_escape_string($db, $image) . "'" : "NULL";

    if ($edit_id > 0) {
      // Execute UPDATE directly for existing monster skill
      $updatestmt = $db->prepare("UPDATE monster_skills SET monster_resource_id = ?, skill_id = ?, image = ? WHERE id = ?");
      $image_val = $image ?: null;
      $updatestmt->bind_param("issi", $monster_id, $skill_id, $image_val, $edit_id);
      if ($updatestmt->execute()) {
        array_push($arr, "updated");
      } else {
        array_push($arr, "update failed: " . $db->error);
      }
      $updatestmt->close();
    } else {
      // Check for duplicate
      $checkstmt = $db->prepare("SELECT id FROM monster_skills WHERE monster_resource_id = ? AND skill_id = ?");
      $checkstmt->bind_param("is", $monster_id, $skill_id);
      $checkstmt->execute();
      $result = $checkstmt->get_result();
      
      if (mysqli_num_rows($result) > 0) {
        array_push($arr, "This monster already has this skill assigned");
      } else {
        // Execute INSERT directly for new monster skill
        $insertstmt = $db->prepare("INSERT INTO monster_skills(monster_resource_id, skill_id, image) VALUES(?, ?, ?)");
        $image_val = $image ?: null;
        $insertstmt->bind_param("iss", $monster_id, $skill_id, $image_val);
        if ($insertstmt->execute()) {
          array_push($arr, "created");
        } else {
          array_push($arr, "insert failed: " . $db->error);
        }
        $insertstmt->close();
      }
      $checkstmt->close();
    }
  }
  return $arr;
}

function itemName($db, $data, $min_name_length, $max_name_length, $min_image_length, $max_image_length, $min_description_length) {
  $item_name = clean($data['item_name']);
  $item_image = clean($data['item_image']);
  $item_description = clean($data['item_description']);
  $item_stats = clean($data['item_stats']);
  $item_model = clean($data['item_model']);
  $edit_id = isset($data['item_edit_id']) ? intval(clean($data['item_edit_id'])) : 0;

  // Check if item with this name already exists
  $selectstmt = $db->prepare("SELECT * FROM resources_items WHERE name = ?");
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
  } else if ($selectstmt->execute()) {
    $result = $selectstmt->get_result();
    $row_count = mysqli_num_rows($result);

    if ($row_count == 0) {
      // Execute INSERT directly for new item
      $insertstmt = $db->prepare("INSERT INTO resources_items(name, image, description, stats, model_3d) VALUES(?, ?, ?, ?, ?)");
      $insertstmt->bind_param("sssss", $item_name, $item_image, $item_description, $item_stats, $item_model);
      if ($insertstmt->execute()) {
        array_push($arr, "created");
      } else {
        array_push($arr, "insert failed: " . $db->error);
      }
      $insertstmt->close();
    } else {
      // Item with this name exists - execute UPDATE directly
      $updatestmt = $db->prepare("UPDATE resources_items SET image = ?, description = ?, stats = ?, model_3d = ? WHERE name = ?");
      $updatestmt->bind_param("sssss", $item_image, $item_description, $item_stats, $item_model, $item_name);
      if ($updatestmt->execute()) {
        array_push($arr, "updated");
      } else {
        array_push($arr, "update failed: " . $db->error);
      }
      $updatestmt->close();
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
  $monster_model = clean($data['monster_model']);

  // Check if monster with this name already exists
  $selectstmt = $db->prepare("SELECT * FROM resources_monsters WHERE name = ?");
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
  } else if ($selectstmt->execute()) {
    $result = $selectstmt->get_result();
    $row_count = mysqli_num_rows($result);

    if ($row_count == 0) {
      // Execute INSERT directly for new monster
      $insertstmt = $db->prepare("INSERT INTO resources_monsters(name, image, description, stats, model_3d) VALUES(?, ?, ?, ?, ?)");
      $insertstmt->bind_param("sssss", $monster_name, $monster_image, $monster_description, $monster_stats, $monster_model);
      if ($insertstmt->execute()) {
        array_push($arr, "created");
      } else {
        array_push($arr, "insert failed: " . $db->error);
      }
      $insertstmt->close();
    } else {
      // Monster with this name exists - execute UPDATE directly
      $updatestmt = $db->prepare("UPDATE resources_monsters SET image = ?, description = ?, stats = ?, model_3d = ? WHERE name = ?");
      $updatestmt->bind_param("sssss", $monster_image, $monster_description, $monster_stats, $monster_model, $monster_name);
      if ($updatestmt->execute()) {
        array_push($arr, "updated");
      } else {
        array_push($arr, "update failed: " . $db->error);
      }
      $updatestmt->close();
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
  $location_model = clean($data['location_model']);

  // Check if location with this name already exists
  $selectstmt = $db->prepare("SELECT * FROM resources_locations WHERE name = ?");
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
  } else if ($selectstmt->execute()) {
    $result = $selectstmt->get_result();
    $row_count = mysqli_num_rows($result);

    if ($row_count == 0) {
      // Execute INSERT directly for new location
      $insertstmt = $db->prepare("INSERT INTO resources_locations(name, image, description, lvl_from, lvl_to, stats, model_3d) VALUES(?, ?, ?, ?, ?, ?, ?)");
      $location_from_int = intval($location_from);
      $location_to_int = intval($location_to);
      $insertstmt->bind_param("sssiiss", $location_name, $location_image, $location_description, $location_from_int, $location_to_int, $location_stats, $location_model);
      if ($insertstmt->execute()) {
        array_push($arr, "created");
      } else {
        array_push($arr, "insert failed: " . $db->error);
      }
      $insertstmt->close();
    } else {
      // Location with this name exists - execute UPDATE directly
      $updatestmt = $db->prepare("UPDATE resources_locations SET image = ?, description = ?, lvl_from = ?, lvl_to = ?, stats = ?, model_3d = ? WHERE name = ?");
      $location_from_int = intval($location_from);
      $location_to_int = intval($location_to);
      $updatestmt->bind_param("ssiisss", $location_image, $location_description, $location_from_int, $location_to_int, $location_stats, $location_model, $location_name);
      if ($updatestmt->execute()) {
        array_push($arr, "updated");
      } else {
        array_push($arr, "update failed: " . $db->error);
      }
      $updatestmt->close();
    }
  }
  $selectstmt->close();
  return $arr;
}

function jobName($db, $data, $min_name_length, $max_name_length, $min_description_length) {
  $job_id = clean($data['job_job_id']);
  $job_name = clean($data['job_name']);
  $job_image = clean($data['job_image']);
  $job_description = clean($data['job_description']);
  $job_stat_modifiers = clean($data['job_stat_modifiers']);
  $job_min_level = intval(clean($data['job_min_level']));
  $job_tier = intval(clean($data['job_tier']));
  $job_required_base_job = clean($data['job_required_base_job']);

  $selectstmt = $db->prepare("SELECT * FROM resources_jobs WHERE job_id = ?");
  $selectstmt->bind_param("s", $job_id);

  $arr = array();

  // Validation
  if (strlen($job_id) < $min_name_length) {
    array_push($arr, "job_id too short (min " . $min_name_length . ")");
  } else if (strlen($job_id) > 32) {
    array_push($arr, "job_id too long (max 32)");
  } else if (!preg_match('/^[a-z_]+$/', $job_id)) {
    array_push($arr, "job_id must be lowercase letters and underscores only");
  } else if (strlen($job_name) < $min_name_length) {
    array_push($arr, "name too short (min " . $min_name_length . ")");
  } else if (strlen($job_name) > $max_name_length) {
    array_push($arr, "name too long (max " . $max_name_length . ")");
  } else if (strlen($job_description) < $min_description_length) {
    array_push($arr, "description too short (min " . $min_description_length . ")");
  } else if ($job_tier < 1 || $job_tier > 5) {
    array_push($arr, "tier must be between 1 and 5");
  } else if ($job_min_level < 1 || $job_min_level > 100) {
    array_push($arr, "min_level must be between 1 and 100");
  } else if ($selectstmt->execute()) {
    $result = $selectstmt->get_result();
    $row_count = mysqli_num_rows($result);

    $escaped_job_id = mysqli_real_escape_string($db, $job_id);
    $escaped_name = mysqli_real_escape_string($db, $job_name);
    $escaped_image = $job_image ? "'" . mysqli_real_escape_string($db, $job_image) . "'" : "NULL";
    $escaped_description = mysqli_real_escape_string($db, $job_description);
    $escaped_stat_modifiers = mysqli_real_escape_string($db, $job_stat_modifiers);
    $escaped_required_base_job = $job_required_base_job ? "'" . mysqli_real_escape_string($db, $job_required_base_job) . "'" : "NULL";

    if ($row_count == 0) {
      // Execute INSERT directly for new job
      $insertstmt = $db->prepare("INSERT INTO resources_jobs(job_id, name, image, description, stat_modifiers, min_level, tier, required_base_job) VALUES(?, ?, ?, ?, ?, ?, ?, ?)");
      $job_image_val = $job_image ?: null;
      $job_required_base_job_val = $job_required_base_job ?: null;
      $insertstmt->bind_param("sssssiss", $job_id, $job_name, $job_image_val, $job_description, $job_stat_modifiers, $job_min_level, $job_tier, $job_required_base_job_val);
      if ($insertstmt->execute()) {
        array_push($arr, "created");
      } else {
        array_push($arr, "insert failed: " . $db->error);
      }
      $insertstmt->close();
    } else {
      // Execute UPDATE directly for existing job
      $updatestmt = $db->prepare("UPDATE resources_jobs SET name = ?, image = ?, description = ?, stat_modifiers = ?, min_level = ?, tier = ?, required_base_job = ? WHERE job_id = ?");
      $job_image_val = $job_image ?: null;
      $job_required_base_job_val = $job_required_base_job ?: null;
      $updatestmt->bind_param("ssssiiss", $job_name, $job_image_val, $job_description, $job_stat_modifiers, $job_min_level, $job_tier, $job_required_base_job_val, $job_id);
      if ($updatestmt->execute()) {
        array_push($arr, "updated");
      } else {
        array_push($arr, "update failed: " . $db->error);
      }
      $updatestmt->close();
    }
  }
  $selectstmt->close();
  return $arr;
}

function skillName($db, $data, $min_name_length, $max_name_length, $min_description_length) {
  $skill_id = clean($data['skill_skill_id']);
  $skill_name = clean($data['skill_name']);
  $skill_image = clean($data['skill_image']);
  $skill_description = clean($data['skill_description']);
  $skill_mp_cost = intval(clean($data['skill_mp_cost']));
  $skill_cooldown = intval(clean($data['skill_cooldown']));
  $skill_damage_multiplier = floatval(clean($data['skill_damage_multiplier']));
  $skill_unlock_cost = intval(clean($data['skill_unlock_cost']));
  $skill_required_job = clean($data['skill_required_job']) ?: 'all';
  $skill_required_skills = clean($data['skill_required_skills']);
  $skill_type = clean($data['skill_type']) ?: 'active';
  $skill_stat_modifiers = clean($data['skill_stat_modifiers']);
  $skill_synergy_with = clean($data['skill_synergy_with']);
  $skill_synergy_bonus = clean($data['skill_synergy_bonus']);
  $skill_synergy_window = intval(clean($data['skill_synergy_window'])) ?: 5;

  $selectstmt = $db->prepare("SELECT * FROM resources_skills WHERE skill_id = ?");
  $selectstmt->bind_param("s", $skill_id);

  $arr = array();

  // Validation
  if (strlen($skill_id) < $min_name_length) {
    array_push($arr, "skill_id too short (min " . $min_name_length . ")");
  } else if (strlen($skill_id) > 32) {
    array_push($arr, "skill_id too long (max 32)");
  } else if (!preg_match('/^[a-z_]+$/', $skill_id)) {
    array_push($arr, "skill_id must be lowercase letters and underscores only");
  } else if (strlen($skill_name) < $min_name_length) {
    array_push($arr, "name too short (min " . $min_name_length . ")");
  } else if (strlen($skill_name) > $max_name_length) {
    array_push($arr, "name too long (max " . $max_name_length . ")");
  } else if (strlen($skill_description) < $min_description_length) {
    array_push($arr, "description too short (min " . $min_description_length . ")");
  } else if ($skill_mp_cost < 0 || $skill_mp_cost > 999) {
    array_push($arr, "mp_cost must be between 0 and 999");
  } else if ($skill_cooldown < 0 || $skill_cooldown > 999) {
    array_push($arr, "cooldown must be between 0 and 999");
  } else if ($skill_damage_multiplier < 0 || $skill_damage_multiplier > 10) {
    array_push($arr, "damage_multiplier must be between 0 and 10");
  } else if ($skill_unlock_cost < 1 || $skill_unlock_cost > 10) {
    array_push($arr, "unlock_cost must be between 1 and 10");
  } else if ($skill_type !== 'active' && $skill_type !== 'passive') {
    array_push($arr, "skill_type must be 'active' or 'passive'");
  } else if ($selectstmt->execute()) {
    $result = $selectstmt->get_result();
    $row_count = mysqli_num_rows($result);

    $escaped_skill_id = mysqli_real_escape_string($db, $skill_id);
    $escaped_name = mysqli_real_escape_string($db, $skill_name);
    $escaped_image = $skill_image ? "'" . mysqli_real_escape_string($db, $skill_image) . "'" : "NULL";
    $escaped_description = mysqli_real_escape_string($db, $skill_description);
    $escaped_required_job = mysqli_real_escape_string($db, $skill_required_job);
    $escaped_required_skills = $skill_required_skills ? "'" . mysqli_real_escape_string($db, $skill_required_skills) . "'" : "NULL";
    $escaped_stat_modifiers = $skill_stat_modifiers ? "'" . mysqli_real_escape_string($db, $skill_stat_modifiers) . "'" : "NULL";
    $escaped_synergy_with = $skill_synergy_with ? "'" . mysqli_real_escape_string($db, $skill_synergy_with) . "'" : "NULL";
    $escaped_synergy_bonus = $skill_synergy_bonus ? "'" . mysqli_real_escape_string($db, $skill_synergy_bonus) . "'" : "NULL";

    if ($row_count == 0) {
      // Execute INSERT directly for new skill
      $insertstmt = $db->prepare("INSERT INTO resources_skills(skill_id, name, image, description, mp_cost, cooldown_sec, damage_multiplier, unlock_cost, required_job, required_skills, skill_type, stat_modifiers, synergy_with, synergy_bonus, synergy_window_sec) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
      $skill_image_val = $skill_image ?: null;
      $skill_required_skills_val = $skill_required_skills ?: null;
      $skill_stat_modifiers_val = $skill_stat_modifiers ?: null;
      $skill_synergy_with_val = $skill_synergy_with ?: null;
      $skill_synergy_bonus_val = $skill_synergy_bonus ?: null;
      $insertstmt->bind_param("sssiiidissssssi", $skill_id, $skill_name, $skill_image_val, $skill_description, $skill_mp_cost, $skill_cooldown, $skill_damage_multiplier, $skill_unlock_cost, $skill_required_job, $skill_required_skills_val, $skill_type, $skill_stat_modifiers_val, $skill_synergy_with_val, $skill_synergy_bonus_val, $skill_synergy_window);
      if ($insertstmt->execute()) {
        array_push($arr, "created");
      } else {
        array_push($arr, "insert failed: " . $db->error);
      }
      $insertstmt->close();
    } else {
      // Execute UPDATE directly for existing skill
      $updatestmt = $db->prepare("UPDATE resources_skills SET name = ?, image = ?, description = ?, mp_cost = ?, cooldown_sec = ?, damage_multiplier = ?, unlock_cost = ?, required_job = ?, required_skills = ?, skill_type = ?, stat_modifiers = ?, synergy_with = ?, synergy_bonus = ?, synergy_window_sec = ? WHERE skill_id = ?");
      $skill_image_val = $skill_image ?: null;
      $skill_required_skills_val = $skill_required_skills ?: null;
      $skill_stat_modifiers_val = $skill_stat_modifiers ?: null;
      $skill_synergy_with_val = $skill_synergy_with ?: null;
      $skill_synergy_bonus_val = $skill_synergy_bonus ?: null;
      $updatestmt->bind_param("sssiiidissssssis", $skill_name, $skill_image_val, $skill_description, $skill_mp_cost, $skill_cooldown, $skill_damage_multiplier, $skill_unlock_cost, $skill_required_job, $skill_required_skills_val, $skill_type, $skill_stat_modifiers_val, $skill_synergy_with_val, $skill_synergy_bonus_val, $skill_synergy_window, $skill_id);
      if ($updatestmt->execute()) {
        array_push($arr, "updated");
      } else {
        array_push($arr, "update failed: " . $db->error);
      }
      $updatestmt->close();
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

// All endpoints now generate SQL instead of executing database operations
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
} else if (isset($data['delete_job'])) {
  echo json_encode(deleteJob($db, $data));
} else if (isset($data['ban_job'])) {
  echo json_encode(banJob($db, $data));
} else if (isset($data['job_job_id'])) {
  echo json_encode(jobName($db, $data, $min_name_length, $max_name_length, $min_description_length));
} else if (isset($data['delete_skill'])) {
  echo json_encode(deleteSkill($db, $data));
} else if (isset($data['ban_skill'])) {
  echo json_encode(banSkill($db, $data));
} else if (isset($data['skill_skill_id'])) {
  echo json_encode(skillName($db, $data, $min_name_length, $max_name_length, $min_description_length));
} else if (isset($data['delete_monster_skill'])) {
  echo json_encode(deleteMonsterSkill($db, $data));
} else if (isset($data['mskill_monster_id'])) {
  echo json_encode(monsterSkillName($db, $data));
} else if (isset($data['delete_shop_item'])) {
  echo json_encode(deleteShopItem($db, $data));
} else if (isset($data['shop_item_id'])) {
  echo json_encode(shopItemName($db, $data));
}

mysqli_close($db);

?>