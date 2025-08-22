<?php

function fightMonster($db, $data, $itemDropRate)
{
  $player_name = clean($data['fight_monster']);
  $room_id = intval(clean($data['room_id']));

  // Heartbeat on combat action
  touchPlayer($db, $room_id, $player_name);

  // Optional skill usage (universal basic skill prior to class selection)
  $requested_skill = '';
  if (isset($data['skill'])) {
    $requested_skill = strtolower(trim(strval($data['skill'])));
  }
  // Whitelist and config for skills (can be moved to DB/JSON later)
  $skill_defs = array(
    'power_strike' => array('mp_cost' => 5, 'mult' => 1.5, 'cd_sec' => 5),
    'fireball' => array('mp_cost' => 7, 'mult' => 1.6, 'cd_sec' => 6)
  );

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
    "monster" => null,     // will be filled with updated state
    "cooldowns" => array(), // seconds remaining by skill key
    "errors" => array()     // optional structured errors
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
      $player_mp = 0;
      $player_maxmp = 0;
      $player_gold = 0;

      $player_stats_parts = explode(';', $player_stats);
      $cooldowns = array(); // preserve and mutate cd_* keys from player stats
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
        if (str_starts_with($player_stats_parts[$i], "mp=")) {
          $player_mp = intval(explode('=', $player_stats_parts[$i])[1]);
        }
        if (str_starts_with($player_stats_parts[$i], "maxmp=")) {
          $player_maxmp = intval(explode('=', $player_stats_parts[$i])[1]);
        }
        if (str_starts_with($player_stats_parts[$i], "gold=")) {
          $player_gold = intval(explode('=', $player_stats_parts[$i])[1]);
        }
        // Capture cooldown keys like cd_power_strike=epoch_seconds;
        if (str_starts_with($player_stats_parts[$i], "cd_")) {
          $kv = explode('=', $player_stats_parts[$i]);
          if (count($kv) == 2) {
            $cooldowns[$kv[0]] = intval($kv[1]);
          }
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
          $skill_used_this_round = false;
          $skill_name = $requested_skill;
          $skill_cost = 0;
          $skill_multiplier = 1.0;
          $skill_cd_sec = 0;
          $now_ts = time();
          // Validate skill against whitelist; unknown skills are rejected
          if ($skill_name !== '' && !array_key_exists($skill_name, $skill_defs)) {
            $result["errors"][] = array("type" => "invalid_skill", "skill" => $skill_name);
            $result["log"][] = "Unknown skill: " . $skill_name . ".";
            $skill_name = '';
          }
          if ($skill_name !== '' && array_key_exists($skill_name, $skill_defs)) {
            $skill_cost = intval($skill_defs[$skill_name]['mp_cost']);
            $skill_multiplier = floatval($skill_defs[$skill_name]['mult']);
            $skill_cd_sec = intval($skill_defs[$skill_name]['cd_sec']);
            // Check cooldown
            $cd_key = 'cd_' . $skill_name;
            $until = isset($cooldowns[$cd_key]) ? intval($cooldowns[$cd_key]) : 0;
            if ($until > $now_ts) {
              $remain = max(0, $until - $now_ts);
              $result["errors"][] = array("type" => "skill_on_cooldown", "skill" => $skill_name, "seconds" => $remain);
              $result["log"][] = ucfirst(str_replace('_',' ', $skill_name)) . " is on cooldown (" . $remain . "s).";
              $skill_name = '';
            }
          }
          for ($i = min(($player_spd + $itemSpd), $monster_spd); $i <= max(($player_spd + $itemSpd), $monster_spd); $i++) {
            if ($i % $monster_spd == 0) {

              if (rand(0, 100) > $monster_dodge) {
                // player attack
                // Resolve skill if requested and resources allow (once per round)
                $using_skill = false;
                if (!$skill_used_this_round && $skill_name !== '' && $skill_multiplier > 1.0) {
                  if ($player_maxmp > 0 && $player_mp >= $skill_cost) {
                    $using_skill = true;
                    $skill_used_this_round = true;
                    $player_mp = max(0, $player_mp - $skill_cost);
                    // Start cooldown
                    try {
                      $cooldowns['cd_' . $skill_name] = $now_ts + max(0, $skill_cd_sec);
                    } catch (Throwable $_) { }
                    // Telemetry: skill used (best-effort)
                    try {
                      $dets = json_encode([ 'skill' => $skill_name, 'mp_cost' => $skill_cost ]);
                      if ($dets === false) { $dets = '{"skill":"'.addslashes($skill_name).'","mp_cost":'.intval($skill_cost).'}'; }
                      if ($lgs = $db->prepare("INSERT INTO game_logs (room_id, player_name, action, details) VALUES (?, ?, 'skill_used', ?)")) {
                        $lgs->bind_param("iss", $room_id, $player_name, $dets);
                        $lgs->execute();
                        $lgs->close();
                      }
                    } catch (Throwable $_) { }
                    $result["events"][] = array("t" => "skill_used", "name" => $skill_name, "mp_spent" => $skill_cost);
                    $result["log"][] = $player_name . " used " . ucfirst(str_replace('_',' ', $skill_name)) . " (-".$skill_cost." MP)!";
                  } else {
                    // Not enough MP; skill ignored
                    $result["log"][] = "Not enough MP to use skill.";
                  }
                }

                $base_force = ($player_atk + $itemAtk) + rand(0, ($player_atk + $itemAtk));
                $player_force = $using_skill ? intval(round($base_force * $skill_multiplier)) : $base_force;
                $monster_force = $monster_def + rand(0, $monster_def);
                $hit = max(0, $player_force - $monster_force);
                $monster_hp = $monster_hp - $hit;
                $playerDamageDealt += $hit;
                $result["events"][] = array("t" => "player_hit", "dmg" => $hit, "crit" => false, "monster_hp" => max(0, $monster_hp));
                $result["log"][] = $player_name . " hits for " . $hit . " damage.";

                // Persist MP change immediately if we used a skill and combat continues
                if ($using_skill && $monster_hp > 0) {
                  try {
                    $upx = $db->prepare("UPDATE game_players SET stats = ? WHERE id = ?");
                    $player_stats_strx = setPlayerStats($player_lvl, $player_exp, $player_hp, $player_maxhp, $player_mp, $player_maxmp, $player_atk, $player_def, $player_spd, $player_evd, $player_gold);
                    // Append cooldowns to stats string so they persist
                    foreach ($cooldowns as $k => $v) { $player_stats_strx .= $k . "=" . intval($v) . ";"; }
                    $upx->bind_param("si", $player_stats_strx, $player_id);
                    $upx->execute();
                    $upx->close();
                  } catch (Throwable $_) { }
                }
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
                    // MP growth and refill on level up
                    if ($player_maxmp <= 0) { $player_maxmp = 50; }
                    $player_maxmp += 5 + intval($player_maxmp * 0.01);
                    $player_mp = $player_maxmp;
                    $result["rewards"]["leveledUp"] = true;
                    $result["rewards"]["newLevel"] = $player_lvl;
                  }

                  // update player stats
                  $up = $db->prepare("UPDATE game_players SET stats = ? WHERE id = ?");
                  $player_stats_str = setPlayerStats($player_lvl, $player_exp, $player_hp, $player_maxhp, $player_mp, $player_maxmp, $player_atk, $player_def, $player_spd, $player_evd, $player_gold);
                  foreach ($cooldowns as $k => $v) { $player_stats_str .= $k . "=" . intval($v) . ";"; }
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
                    "mp" => $player_mp,
                    "maxmp" => $player_maxmp,
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
                  $player_mp = $player_maxmp;
                  $player_exp = 0;
                  $player_gold = 0;

                  // update player stats
                  $up = $db->prepare("UPDATE game_players SET stats = ?, x = ?, y = ? WHERE id = ?");
                  $player_stats_str = setPlayerStats($player_lvl, $player_exp, $player_hp, $player_maxhp, $player_mp, $player_maxmp, $player_atk, $player_def, $player_spd, $player_evd, $player_gold);
                  foreach ($cooldowns as $k => $v) { $player_stats_str .= $k . "=" . intval($v) . ";"; }
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
                    "mp" => $player_mp,
                    "maxmp" => $player_maxmp,
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
                  $player_stats_str = setPlayerStats($player_lvl, $player_exp, $player_hp, $player_maxhp, $player_mp, $player_maxmp, $player_atk, $player_def, $player_spd, $player_evd, $player_gold);
                  // Append cooldowns to ensure persistence across ongoing rounds
                  foreach ($cooldowns as $k => $v) { $player_stats_str .= $k . "=" . intval($v) . ";"; }
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
                    "mp" => $player_mp,
                    "maxmp" => $player_maxmp,
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
  // Expose cooldowns (seconds remaining) for client UI
  try {
    if (isset($cooldowns) && is_array($cooldowns)) {
      $now_calc = time();
      foreach ($cooldowns as $k => $until) {
        if (str_starts_with($k, 'cd_')) {
          $skill_key = substr($k, 3);
          $remain = intval($until) - $now_calc;
          if ($remain > 0) {
            $result['cooldowns'][$skill_key] = $remain;
          }
        }
      }
    }
  } catch (Throwable $_) { }
  // Telemetry: if outcome is terminal (win/lose), it was logged above. If still ongoing, no end event is emitted.
  return $result;
}
