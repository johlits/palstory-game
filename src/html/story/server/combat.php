<?php

function fightMonster($db, $data, $itemDropRate)
{
  $player_name = clean($data['fight_monster']);
  $room_id = intval(clean($data['room_id']));

  // Heartbeat on combat action
  touchPlayer($db, $room_id, $player_name);

  $sp = $db->prepare("SELECT * 
				FROM game_players 
				WHERE room_id = ? AND name = ?");
  $sp->bind_param("is", $room_id, $player_name);

  // Structured result we will return
  $result = array(
    "type" => "fight",
    "events" => array(),   // timeline of events
    "log" => array(),      // human-readable lines
    "outcome" => "ongoing", // win|lose|ongoing
    "rewards" => array("gold" => 0, "exp" => 0, "leveledUp" => false, "newLevel" => null),
    "drops" => array(),
    "player" => null,      // will be filled with updated stats
    "monster" => null      // will be filled with updated state
  );

  if ($sp->execute()) {

    $rp = $sp->get_result();
    $rpc = mysqli_num_rows($rp);
    if ($rpc > 0) {

      while ($rprow = mysqli_fetch_array($rp)) {
        $player_id = intval($rprow["id"]);
        $player_stats = $rprow["stats"];
        $player_x = intval($rprow["x"]);
        $player_y = intval($rprow["y"]);
        break;
      }

      // Initialize player stat defaults to avoid undefined variable notices
      $player_lvl = 1;
      $player_exp = 0;
      $player_atk = 1;
      $player_def = 0;
      $player_spd = 1;
      $player_evd = 0;
      $player_hp = 10;
      $player_maxhp = 10;
      $player_gold = 0;

      $player_stats_parts = explode(';', $player_stats);
      for ($i = 0; $i < count($player_stats_parts); $i++) {
        if (str_starts_with($player_stats_parts[$i], "lvl=")) {
          $player_lvl = intval(explode('=', $player_stats_parts[$i])[1]);
        }
        if (str_starts_with($player_stats_parts[$i], "exp=")) {
          $player_exp = intval(explode('=', $player_stats_parts[$i])[1]);
        }
        if (str_starts_with($player_stats_parts[$i], "atk=")) {
          $player_atk = intval(explode('=', $player_stats_parts[$i])[1]);
        }
        if (str_starts_with($player_stats_parts[$i], "def=")) {
          $player_def = intval(explode('=', $player_stats_parts[$i])[1]);
        }
        if (str_starts_with($player_stats_parts[$i], "spd=")) {
          $player_spd = intval(explode('=', $player_stats_parts[$i])[1]);
        }
        if (str_starts_with($player_stats_parts[$i], "evd=")) {
          $player_evd = intval(explode('=', $player_stats_parts[$i])[1]);
        }
        if (str_starts_with($player_stats_parts[$i], "hp=")) {
          $player_hp = intval(explode('=', $player_stats_parts[$i])[1]);
        }
        if (str_starts_with($player_stats_parts[$i], "maxhp=")) {
          $player_maxhp = intval(explode('=', $player_stats_parts[$i])[1]);
        }
        if (str_starts_with($player_stats_parts[$i], "gold=")) {
          $player_gold = intval(explode('=', $player_stats_parts[$i])[1]);
        }
      }

      $itemAtk = 0;
      $itemDef = 0;
      $itemSpd = 0;
      $itemEvd = 0;

      $se = $db->prepare("SELECT * 
				FROM game_items WHERE owner_id = ? AND equipped = 1");
      $se->bind_param("i", $player_id);
      if ($se->execute()) {
        $r = $se->get_result();
        $rc = mysqli_num_rows($r);
        if ($rc > 0) {
          while ($row = mysqli_fetch_array($r)) {
            $item_stats_parts = explode(';', $row["stats"]);
            for ($i = 0; $i < count($item_stats_parts); $i++) {
              if (str_starts_with($item_stats_parts[$i], "atk=")) {
                $itemAtk += intval(explode('=', $item_stats_parts[$i])[1]);
              }
              if (str_starts_with($item_stats_parts[$i], "def=")) {
                $itemDef += intval(explode('=', $item_stats_parts[$i])[1]);
              }
              if (str_starts_with($item_stats_parts[$i], "spd=")) {
                $itemSpd += intval(explode('=', $item_stats_parts[$i])[1]);
              }
              if (str_starts_with($item_stats_parts[$i], "evd=")) {
                $itemEvd += intval(explode('=', $item_stats_parts[$i])[1]);
              }
            }
          }
        }
      }
      $se->close();

      $sm = $db->prepare("SELECT gm.id, gm.stats, rm.name  
				FROM game_monsters gm INNER JOIN resources_monsters rm ON gm.resource_id = rm.id 
				WHERE gm.room_id = ? AND gm.x = ? AND gm.y = ?");
      $sm->bind_param("iii", $room_id, $player_x, $player_y);

      if ($sm->execute()) {

        $rm = $sm->get_result();
        $rmc = mysqli_num_rows($rm);
        if ($rmc > 0) {

          while ($rmrow = mysqli_fetch_array($rm)) {
            $monster_id = intval($rmrow["id"]);
            $monster_stats = $rmrow["stats"];
            $monster_name = $rmrow["name"];
            break;
          }

          // Telemetry: combat start (best-effort)
          try {
            $det0 = json_encode([
              'player' => $player_name,
              'room_id' => $room_id,
              'monster' => [ 'id' => $monster_id, 'name' => $monster_name ],
              'pos' => [ $player_x, $player_y ]
            ]);
            if ($det0 === false) { $det0 = '{"player":"'.addslashes($player_name).'","room_id":'.intval($room_id).',"monster":{"id":'.intval($monster_id).',"name":"'.addslashes($monster_name).'"}}'; }
            if ($lg0 = $db->prepare("INSERT INTO game_logs (room_id, player_name, action, details) VALUES (?, ?, 'combat_start', ?)")) {
              $lg0->bind_param("iss", $room_id, $player_name, $det0);
              $lg0->execute();
              $lg0->close();
            }
          } catch (Throwable $_) { }

          // Initialize monster defaults to avoid notices if any key is missing
          $monster_atk = 1;
          $monster_def = 0;
          $monster_spd = 1;
          $monster_evd = 0;
          $monster_hp = 1;
          $monster_maxhp = 1;
          $monster_drops = '';
          $monster_gold = 0;
          $monster_exp = 0;

          $monster_stats_parts = explode(';', $monster_stats);
          for ($i = 0; $i < count($monster_stats_parts); $i++) {
            if (str_starts_with($monster_stats_parts[$i], "atk=")) {
              $monster_atk = intval(explode('=', $monster_stats_parts[$i])[1]);
            }
            if (str_starts_with($monster_stats_parts[$i], "def=")) {
              $monster_def = intval(explode('=', $monster_stats_parts[$i])[1]);
            }
            if (str_starts_with($monster_stats_parts[$i], "spd=")) {
              $monster_spd = intval(explode('=', $monster_stats_parts[$i])[1]);
            }
            if (str_starts_with($monster_stats_parts[$i], "evd=")) {
              $monster_evd = intval(explode('=', $monster_stats_parts[$i])[1]);
            }
            if (str_starts_with($monster_stats_parts[$i], "hp=")) {
              $monster_hp = intval(explode('=', $monster_stats_parts[$i])[1]);
            }
            if (str_starts_with($monster_stats_parts[$i], "maxhp=")) {
              $monster_maxhp = intval(explode('=', $monster_stats_parts[$i])[1]);
            }
            if (str_starts_with($monster_stats_parts[$i], "drops=")) {
              $monster_drops = explode('=', $monster_stats_parts[$i])[1];
            }
            if (str_starts_with($monster_stats_parts[$i], "gold=")) {
              $monster_gold = intval(explode('=', $monster_stats_parts[$i])[1]);
            }
            if (str_starts_with($monster_stats_parts[$i], "exp=")) {
              $monster_exp = intval(explode('=', $monster_stats_parts[$i])[1]);
            }
          }

          if ($player_evd + $itemEvd >= $monster_evd) {
            $monster_dodge = 1;
            $player_dodge = min(99, ($player_evd + $itemEvd) / $monster_evd);
          } else {
            $player_dodge = 1;
            $monster_dodge = min(99, $monster_evd / ($player_evd + $itemEvd));
          }

          // fight here
          $ticks = 0; // simple action ticks (not strict turns)
          $playerDamageDealt = 0;
          $monsterDamageDealt = 0;
          for ($i = min(($player_spd + $itemSpd), $monster_spd); $i <= max(($player_spd + $itemSpd), $monster_spd); $i++) {
            if ($i % $monster_spd == 0) {

              if (rand(0, 100) > $monster_dodge) {
                // player attack
                $player_force = ($player_atk + $itemAtk) + rand(0, ($player_atk + $itemAtk));
                $monster_force = $monster_def + rand(0, $monster_def);
                $hit = max(0, $player_force - $monster_force);
                $monster_hp = $monster_hp - $hit;
                $playerDamageDealt += $hit;
                $result["events"][] = array("t" => "player_hit", "dmg" => $hit, "crit" => false, "monster_hp" => max(0, $monster_hp));
                $result["log"][] = $player_name . " hits for " . $hit . " damage.";
                if ($monster_hp <= 0) {
                  $result["events"][] = array("t" => "monster_slain", "name" => $monster_name);
                  $result["log"][] = $monster_name . " was slain!";

                  // drops
                  $monster_drops_parts = explode(',', $monster_drops);
                  if (rand(1, 100) <= $itemDropRate) {
                    $spawnItemName = trim($monster_drops_parts[rand(0, count($monster_drops_parts) - 1)], " ");

                    $sir = $db->prepare("SELECT id, stats, name FROM resources_items WHERE name = ?");
                    $sir->bind_param("s", $spawnItemName);

                    if ($sir->execute()) {

                      $sires = $sir->get_result();
                      $sircnt = mysqli_num_rows($sires);
                      if ($sircnt > 0) {
                        while ($sirrow = mysqli_fetch_array($sires)) {
                          $item_resource_id = intval($sirrow["id"]);
                          $item_resource_stats = parseItemStats($sirrow["stats"]);
                          $item_resource_name = $sirrow["name"];

                          $result["drops"][] = array("name" => $item_resource_name, "resource_id" => $item_resource_id);
                          $result["log"][] = "Dropped " . $item_resource_name . "!";

                          $ii = $db->prepare("INSERT INTO game_items( room_id, stats, resource_id, owner_id ) 
				VALUES(?, ?, ?, ?)");
                          $ii->bind_param("isii", $room_id, $item_resource_stats, $item_resource_id, $player_id);
                          $ii->execute();
                          $ii->close();

                          // Telemetry: combat end (lose)
                  try {
                    $detl = json_encode([
                      'outcome' => 'lose',
                      'player' => $player_name,
                      'monster' => ['id' => $monster_id, 'name' => $monster_name],
                      'summary' => [ 'ticks' => $ticks, 'damage_dealt' => $playerDamageDealt, 'damage_taken' => $monsterDamageDealt ]
                    ]);
                    if ($detl === false) { $detl = '{"outcome":"lose","player":"'.addslashes($player_name).'","monster":{"id":'.intval($monster_id).',"name":"'.addslashes($monster_name).'"},"summary":{"ticks":'.intval($ticks).',"damage_dealt":'.intval($playerDamageDealt).',"damage_taken":'.intval($monsterDamageDealt).'}}'; }
                    if ($lgl = $db->prepare("INSERT INTO game_logs (room_id, player_name, action, details) VALUES (?, ?, 'combat_end', ?)")) {
                      $lgl->bind_param("iss", $room_id, $player_name, $detl);
                      $lgl->execute();
                      $lgl->close();
                    }
                  } catch (Throwable $_) { }
                  break;
                        }
                      }
                    }
                    $sir->close();

                  }

                  $result["rewards"]["gold"] = $monster_gold;
                  $result["rewards"]["exp"] = $monster_exp;
                  $result["log"][] = "Gained " . $monster_gold . " gold!";
                  $result["log"][] = "Gained " . $monster_exp . " EXP!";

                  $player_gold = $player_gold + $monster_gold;
                  $player_exp = $player_exp + $monster_exp;

                  $lvl_cutoff = 10 + 3 * $player_lvl + pow(10, 0.01 * $player_lvl);
                  if ($player_exp >= $lvl_cutoff) {
                    $player_exp = intval($player_exp - $lvl_cutoff);
                    $player_lvl = $player_lvl + 1;

                    // stat increase
                    $stat_incr = rand(1, 4);
                    if ($stat_incr == 1) {
                      $player_atk += 1;
                    } else if ($stat_incr == 2) {
                      $player_def += 1;
                    } else if ($stat_incr == 3) {
                      $player_spd += 1;
                    } else if ($stat_incr == 4) {
                      $player_evd += 1;
                    }

                    $player_maxhp += 10 + intval($player_maxhp * 0.01);
                    $player_hp = $player_maxhp;
                    $result["rewards"]["leveledUp"] = true;
                    $result["rewards"]["newLevel"] = $player_lvl;
                  }

                  // update player stats
                  $up = $db->prepare("UPDATE game_players SET stats = ? WHERE id = ?");
                  $player_stats_str = setPlayerStats($player_lvl, $player_exp, $player_hp, $player_maxhp, $player_atk, $player_def, $player_spd, $player_evd, $player_gold);
                  $up->bind_param("si", $player_stats_str, $player_id);
                  $up->execute();
                  $up->close();

                  $dm = $db->prepare("DELETE FROM game_monsters WHERE id = ?");
                  $dm->bind_param("i", $monster_id);
                  $dm->execute();
                  $dm->close();
                  // Touch after win
                  touchPlayer($db, $room_id, $player_name);
                  $result["outcome"] = "win";
                  $result["monster"] = array(
                    "id" => $monster_id,
                    "name" => $monster_name,
                    "hp" => 0,
                    "maxhp" => $monster_maxhp
                  );
                  $result["player"] = array(
                    "name" => $player_name,
                    "lvl" => $player_lvl,
                    "exp" => $player_exp,
                    "hp" => $player_hp,
                    "maxhp" => $player_maxhp,
                    "atk" => $player_atk,
                    "def" => $player_def,
                    "spd" => $player_spd,
                    "evd" => $player_evd,
                    "gold" => $player_gold
                  );

                  // Telemetry: combat end (win)
                  try {
                    $detw = json_encode([
                      'outcome' => 'win',
                      'player' => $player_name,
                      'monster' => ['id' => $monster_id, 'name' => $monster_name],
                      'rewards' => ['gold' => $monster_gold, 'exp' => $monster_exp],
                      'summary' => [ 'ticks' => $ticks, 'damage_dealt' => $playerDamageDealt, 'damage_taken' => $monsterDamageDealt ]
                    ]);
                    if ($detw === false) { $detw = '{"outcome":"win","player":"'.addslashes($player_name).'","monster":{"id":'.intval($monster_id).',"name":"'.addslashes($monster_name).'"},"rewards":{"gold":'.intval($monster_gold).',"exp":'.intval($monster_exp).'},"summary":{"ticks":'.intval($ticks).',"damage_dealt":'.intval($playerDamageDealt).',"damage_taken":'.intval($monsterDamageDealt).'}}'; }
                    if ($lgw = $db->prepare("INSERT INTO game_logs (room_id, player_name, action, details) VALUES (?, ?, 'combat_end', ?)")) {
                      $lgw->bind_param("iss", $room_id, $player_name, $detw);
                      $lgw->execute();
                      $lgw->close();
                    }
                  } catch (Throwable $_) { }
                  break;
                } else {
                  // update monster stats
                  $um = $db->prepare("UPDATE game_monsters SET stats = ? WHERE id = ?");
                  $monster_stats_str = setMonsterStats($monster_hp, $monster_maxhp, $monster_atk, $monster_def, $monster_spd, $monster_evd, $monster_drops, $monster_gold, $monster_exp);
                  $um->bind_param("si", $monster_stats_str, $monster_id);
                  $um->execute();
                  $um->close();
                  // keep latest snapshot while ongoing
                  $result["monster"] = array(
                    "id" => $monster_id,
                    "name" => $monster_name,
                    "hp" => $monster_hp,
                    "maxhp" => $monster_maxhp
                  );
                }
              } else {
                $result["events"][] = array("t" => "player_miss");
                $result["log"][] = $player_name . " missed!";
              }
            }
            if ($i % ($player_spd + $itemSpd) == 0) {

              if (rand(0, 100) > $player_dodge) {
                // monster attack
                $monster_force = $monster_atk + rand(0, $monster_atk);
                $player_force = ($player_def + $itemDef) + rand(0, ($player_def + $itemDef));
                $hit = max(0, $monster_force - $player_force);
                $player_hp = $player_hp - $hit;
                $monsterDamageDealt += $hit;
                $result["events"][] = array("t" => "monster_hit", "dmg" => $hit, "crit" => false, "player_hp" => max(0, $player_hp));
                $result["log"][] = $monster_name . " hits for " . $hit . " damage.";
                if ($player_hp <= 0) {

                  $result["events"][] = array("t" => "player_died", "name" => $player_name);
                  $result["log"][] = $player_name . " died.";
                  $reset_player_x = 0;
                  $reset_player_y = 0;
                  $player_hp = $player_maxhp;
                  $player_exp = 0;
                  $player_gold = 0;

                  // update player stats
                  $up = $db->prepare("UPDATE game_players SET stats = ?, x = ?, y = ? WHERE id = ?");
                  $player_stats_str = setPlayerStats($player_lvl, $player_exp, $player_hp, $player_maxhp, $player_atk, $player_def, $player_spd, $player_evd, $player_gold);
                  $up->bind_param("siii", $player_stats_str, $reset_player_x, $reset_player_y, $player_id);
                  $up->execute();
                  $up->close();

                  $di = $db->prepare("DELETE FROM game_items 
                    WHERE owner_id = ?");
                  $di->bind_param("i", $player_id);
                  if ($di->execute()) {
                    $dp = $db->prepare("DELETE FROM game_players 
                    WHERE id = ?");
                    $dp->bind_param("i", $player_id);
                    $dp->execute();
                    $dp->close();
                  }
                  $di->close();

                  $result["outcome"] = "lose";
                  // Touch after defeat/reset
                  touchPlayer($db, $room_id, $player_name);
                  $result["player"] = array(
                    "name" => $player_name,
                    "lvl" => $player_lvl,
                    "exp" => $player_exp,
                    "hp" => $player_hp,
                    "maxhp" => $player_maxhp,
                    "atk" => $player_atk,
                    "def" => $player_def,
                    "spd" => $player_spd,
                    "evd" => $player_evd,
                    "gold" => $player_gold
                  );
                  break;

                } else {
                  // update player stats
                  $up = $db->prepare("UPDATE game_players SET stats = ? WHERE id = ?");
                  $player_stats_str = setPlayerStats($player_lvl, $player_exp, $player_hp, $player_maxhp, $player_atk, $player_def, $player_spd, $player_evd, $player_gold);
                  $up->bind_param("si", $player_stats_str, $player_id);
                  $up->execute();
                  $up->close();
                  // keep latest snapshot while ongoing
                  $result["player"] = array(
                    "name" => $player_name,
                    "lvl" => $player_lvl,
                    "exp" => $player_exp,
                    "hp" => $player_hp,
                    "maxhp" => $player_maxhp,
                    "atk" => $player_atk,
                    "def" => $player_def,
                    "spd" => $player_spd,
                    "evd" => $player_evd,
                    "gold" => $player_gold
                  );
                }
              } else {
                $result["events"][] = array("t" => "monster_miss");
                $result["log"][] = $monster_name . " missed!";
              }
            }
          }

        }
      }

      $sm->close();

    }
  }
  $sp->close();
  // Telemetry: if outcome is terminal (win/lose), it was logged above. If still ongoing, no end event is emitted.
  return $result;
}
