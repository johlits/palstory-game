<?php

function fightMonster($db, $data, $itemDropRate)
{
  $player_name = clean($data['fight_monster']);
  $room_id = intval(clean($data['room_id']));
  // Structured result init (moved up so rate limit can return proper shape)
  $result = array(
    "type" => "fight",
    "events" => array(),
    "log" => array(),
    "outcome" => "ongoing",
    "rewards" => array("gold" => 0, "exp" => 0, "leveledUp" => false, "newLevel" => null),
    "drops" => array(),
    "player" => null,
    "monster" => null,
    "cooldowns" => array(),
    "errors" => array()
  );

  // Basic rate limit (configurable): max COMBAT_RL_MAX_ACTIONS per COMBAT_RL_WINDOW_SEC per player per room (best-effort)
  $rlc = telemetryRateLimitCheck($db, $room_id, $player_name, ['combat_start','skill_used'], COMBAT_RL_WINDOW_SEC, COMBAT_RL_MAX_ACTIONS);
  if (!$rlc['ok']) {
    $result["errors"][] = array("type" => "rate_limited", "window_sec" => COMBAT_RL_WINDOW_SEC);
    $result["log"][] = "Too many combat actions. Please wait a moment.";
    return $result;
  }

  // Heartbeat on combat action
  touchPlayer($db, $room_id, $player_name);

  // Optional skill usage (universal basic skill prior to class selection)
  $requested_skill = '';
  if (isset($data['skill'])) {
    $requested_skill = strtolower(trim(strval($data['skill'])));
  }
  // Load skill definitions from database
  $skill_defs = array();
  try {
    $skill_query = $db->prepare("SELECT skill_id, mp_cost, damage_multiplier, cooldown_sec FROM resources_skills WHERE banned = 0");
    if ($skill_query && $skill_query->execute()) {
      $skill_result = $skill_query->get_result();
      while ($skill_row = mysqli_fetch_array($skill_result)) {
        $skill_defs[$skill_row['skill_id']] = array(
          'mp_cost' => intval($skill_row['mp_cost']),
          'mult' => floatval($skill_row['damage_multiplier']),
          'cd_sec' => intval($skill_row['cooldown_sec'])
        );
      }
      $skill_query->close();
    }
  } catch (Throwable $_) {
    // Fallback to basic skills if DB read fails
    $skill_defs = array(
      'power_strike' => array('mp_cost' => 5, 'mult' => 1.5, 'cd_sec' => 5),
      'fireball' => array('mp_cost' => 7, 'mult' => 1.6, 'cd_sec' => 6)
    );
  }

  $sp = $db->prepare("SELECT * 
				FROM game_players 
				WHERE room_id = ? AND name = ?");
  $sp->bind_param("is", $room_id, $player_name);

  // Result was initialized above

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

      // Parse player stats using centralized utility
      $pstats = parsePlayerStats($player_stats);
      $player_lvl = $pstats['lvl'];
      $player_exp = $pstats['exp'];
      $player_atk = $pstats['atk'];
      $player_def = $pstats['def'];
      $player_spd = $pstats['spd'];
      $player_evd = $pstats['evd'];
      $player_crt = $pstats['crt'];
      $player_hp = $pstats['hp'];
      $player_maxhp = $pstats['maxhp'];
      $player_mp = $pstats['mp'];
      $player_maxmp = $pstats['maxmp'];
      $player_gold = $pstats['gold'];
      $player_skill_points = $pstats['skill_points'];
      $player_job = $pstats['job'];
      $player_unlocked_skills = $pstats['unlocked_skills'];
      $cooldowns = $pstats['cooldowns'];

      // Get equipped item stats using centralized utility
      $itemStats = ['atk' => 0, 'def' => 0, 'spd' => 0, 'evd' => 0, 'crt' => 0];
      $se = $db->prepare("SELECT stats FROM game_items WHERE owner_id = ? AND equipped = 1");
      $se->bind_param("i", $player_id);
      if ($se->execute()) {
        $r = $se->get_result();
        $equippedItems = [];
        while ($row = mysqli_fetch_array($r)) {
          $equippedItems[] = $row["stats"];
        }
        $itemStats = sumEquippedItemStats($equippedItems);
      }
      $se->close();
      $itemAtk = $itemStats['atk'];
      $itemDef = $itemStats['def'];
      $itemSpd = $itemStats['spd'];
      $itemEvd = $itemStats['evd'];
      $itemCrt = $itemStats['crt'];

      // Apply passive skill bonuses
      $passiveBonuses = getPassiveSkillBonuses($db, $player_unlocked_skills);
      $passiveAtk = $passiveBonuses['atk'];
      $passiveDef = $passiveBonuses['def'];
      $passiveSpd = $passiveBonuses['spd'];
      $passiveEvd = $passiveBonuses['evd'];
      $passiveCrt = $passiveBonuses['crt'];
      $passiveMaxHp = $passiveBonuses['maxhp'];
      $passiveMaxMp = $passiveBonuses['maxmp'];

      $sm = $db->prepare("SELECT gm.id, gm.stats, rm.name, rm.id as resource_id, rm.mp as base_mp, rm.maxmp as base_maxmp  
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
            $monster_resource_id = intval($rmrow["resource_id"]);
            $monster_base_mp = intval($rmrow["base_mp"]);
            $monster_base_maxmp = intval($rmrow["base_maxmp"]);
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

          // Parse monster stats using centralized utility
          $mstats = parseMonsterStatsToArray($monster_stats);
          $monster_atk = $mstats['atk'];
          $monster_def = $mstats['def'];
          $monster_spd = $mstats['spd'];
          $monster_evd = $mstats['evd'];
          $monster_crt = $mstats['crt'];
          $monster_hp = $mstats['hp'];
          $monster_maxhp = $mstats['maxhp'];
          $monster_mp = $mstats['mp'] ?: $monster_base_mp;
          $monster_maxmp = $mstats['maxmp'] ?: $monster_base_maxmp;
          $monster_drops = $mstats['drops'];
          $monster_gold = $mstats['gold'];
          $monster_exp = $mstats['exp'];
          $monster_cooldowns = $mstats['cooldowns'];
          
          // Load monster skills from database
          $monster_skills = array();
          try {
            $mskill_query = $db->prepare("SELECT ms.skill_id, rs.mp_cost, rs.damage_multiplier, rs.cooldown_sec FROM monster_skills ms INNER JOIN resources_skills rs ON ms.skill_id = rs.skill_id WHERE ms.monster_resource_id = ? AND rs.banned = 0");
            if ($mskill_query) {
              $mskill_query->bind_param("i", $monster_resource_id);
              if ($mskill_query->execute()) {
                $mskill_result = $mskill_query->get_result();
                while ($mskill_row = mysqli_fetch_array($mskill_result)) {
                  $monster_skills[] = array(
                    'skill_id' => $mskill_row['skill_id'],
                    'mp_cost' => intval($mskill_row['mp_cost']),
                    'mult' => floatval($mskill_row['damage_multiplier']),
                    'cd_sec' => intval($mskill_row['cooldown_sec'])
                  );
                }
              }
              $mskill_query->close();
            }
          } catch (Throwable $_) { }

          // Parse and apply active status effects
          $activeEffects = parseStatusEffects($player_stats_str);
          $effectModifiers = getStatusEffectModifiers($activeEffects);
          
          // Calculate effective stats with items, passive skills, and active status effects
          $effectiveAtk = $player_atk + $itemAtk + $passiveAtk + $effectModifiers['atk'];
          $effectiveDef = $player_def + $itemDef + $passiveDef + $effectModifiers['def'];
          $effectiveSpd = $player_spd + $itemSpd + $passiveSpd + $effectModifiers['spd'];
          $effectiveEvd = $player_evd + $itemEvd + $passiveEvd + $effectModifiers['evd'];
          $effectiveCrt = $player_crt + $itemCrt + $passiveCrt;
          $effectiveMaxHp = $player_maxhp + $passiveMaxHp;
          $effectiveMaxMp = $player_maxmp + $passiveMaxMp;

          if ($effectiveEvd >= $monster_evd) {
            $monster_dodge = 1;
            $player_dodge = min(99, $effectiveEvd / $monster_evd);
          } else {
            $player_dodge = 1;
            $monster_dodge = min(99, $monster_evd / $effectiveEvd);
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
          for ($i = min($effectiveSpd, $monster_spd); $i <= max($effectiveSpd, $monster_spd); $i++) {
            if ($i % $monster_spd == 0) {

              if (rand(0, 100) > $monster_dodge) {
                // player attack
                // Resolve skill if requested and resources allow (once per round)
                $using_skill = false;
                $synergy = array('triggered' => false, 'bonus' => '', 'synergy_skill' => ''); // Initialize synergy result
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
                    
                    // Check for skill synergy
                    $synergy = checkSkillSynergy($db, $player_stats_str, $skill_name);
                    if ($synergy['triggered']) {
                      $result["events"][] = array("t" => "synergy", "skill" => $skill_name, "with" => $synergy['synergy_skill']);
                      $result["log"][] = "⚡ SYNERGY! " . ucfirst(str_replace('_',' ', $synergy['synergy_skill'])) . " → " . ucfirst(str_replace('_',' ', $skill_name)) . "!";
                    }
                    
                    // Track this skill usage for future synergies
                    $player_stats_str = trackSkillUsage($player_stats_str, $skill_name);
                    
                    // Apply status effects for buff/debuff skills
                    $effect_applied = false;
                    $effect_name = '';
                    $effect_duration = 0;
                    switch ($skill_name) {
                      case 'shield_stance': $effect_name = 'shield_30'; $effect_duration = 10; break;
                      case 'battle_shout': $effect_name = 'atk_boost_20'; $effect_duration = 15; break;
                      case 'poison_strike': $effect_name = 'poison_5'; $effect_duration = 15; break;
                      case 'shadow_step': $effect_name = 'evd_boost_50'; $effect_duration = 8; break;
                      case 'frost_armor': $effect_name = 'shield_25'; $effect_duration = 12; break;
                      case 'arcane_surge': $effect_name = 'atk_boost_30'; $effect_duration = 10; break;
                      case 'holy_shield': $effect_name = 'shield_35'; $effect_duration = 12; break;
                      case 'regeneration': $effect_name = 'regen_8'; $effect_duration = 15; break;
                      case 'hunters_mark': $effect_name = 'atk_boost_25'; $effect_duration = 12; break;
                      case 'evasive_maneuvers': $effect_name = 'spd_boost_40'; $effect_duration = 10; break;
                      case 'feral_instinct': $effect_name = 'atk_boost_20'; $effect_duration = 12; break;
                      case 'nature_ward': $effect_name = 'shield_20'; $effect_duration = 10; break;
                    }
                    if ($effect_name !== '') {
                      $player_stats_str = addStatusEffect($player_stats_str, $effect_name, $effect_duration);
                      $effect_applied = true;
                      $result["log"][] = "Effect applied: " . ucfirst(str_replace('_', ' ', $effect_name)) . " for " . $effect_duration . "s!";
                    }
                  } else {
                    // Not enough MP; skill ignored
                    $result["log"][] = "Not enough MP to use skill.";
                  }
                }

                $base_force = $effectiveAtk + rand(0, $effectiveAtk);
                $player_force = $using_skill ? intval(round($base_force * $skill_multiplier)) : $base_force;
                $monster_force = $monster_def + rand(0, $monster_def);
                $hit = max(1, $player_force - $monster_force); // Minimum 1 damage
                
                // Apply synergy bonus if triggered
                if ($using_skill && isset($synergy) && $synergy['triggered']) {
                  $synergy_result = applySynergyBonus($synergy['bonus'], $hit, $effectiveCrt);
                  $hit = $synergy_result['damage'];
                  $effectiveCrt = $synergy_result['crit']; // Update crit chance for this attack
                  if (isset($synergy_result['mp_restore'])) {
                    $player_mp = min($effectiveMaxMp, $player_mp + $synergy_result['mp_restore']);
                    $result["log"][] = "Synergy restored " . $synergy_result['mp_restore'] . " MP!";
                  }
                }
                
                // Critical hit check
                $is_crit = false;
                $total_crit = min(95, $effectiveCrt); // cap at 95%
                if (rand(1, 100) <= $total_crit) {
                  $is_crit = true;
                  $hit = intval(round($hit * 2.0)); // 2x damage on crit
                }
                
                // Apply poison DoT to monster if player has poison effect active
                if ($effectModifiers['poison'] > 0) {
                  $poison_dmg = $effectModifiers['poison'];
                  $monster_hp = $monster_hp - $poison_dmg;
                  $playerDamageDealt += $poison_dmg;
                  $result["log"][] = $monster_name . " takes " . $poison_dmg . " poison damage!";
                }
                
                $monster_hp = $monster_hp - $hit;
                $playerDamageDealt += $hit;
                $result["events"][] = array("t" => "player_hit", "dmg" => $hit, "crit" => $is_crit, "monster_hp" => max(0, $monster_hp));
                $result["log"][] = $player_name . ($is_crit ? " crits for " : " hits for ") . $hit . " damage" . ($is_crit ? "!" : ".");

                // Persist MP change immediately if we used a skill and combat continues
                if ($using_skill && $monster_hp > 0) {
                  try {
                    $upx = $db->prepare("UPDATE game_players SET stats = ? WHERE id = ?");
                    $player_stats_strx = setPlayerStats($player_lvl, $player_exp, $player_hp, $player_maxhp, $player_mp, $player_maxmp, $player_atk, $player_def, $player_spd, $player_evd, $player_gold, $player_crt, $player_skill_points, $player_job, $player_unlocked_skills);
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
                    // Award 1 skill point per level
                    $player_skill_points += 1;
                    $result["rewards"]["leveledUp"] = true;
                    $result["rewards"]["newLevel"] = $player_lvl;
                    $result["rewards"]["skillPoints"] = 1;
                  }

                  // update player stats
                  $up = $db->prepare("UPDATE game_players SET stats = ? WHERE id = ?");
                  $player_stats_str = setPlayerStats($player_lvl, $player_exp, $player_hp, $player_maxhp, $player_mp, $player_maxmp, $player_atk, $player_def, $player_spd, $player_evd, $player_gold, $player_crt, $player_skill_points, $player_job, $player_unlocked_skills);
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
                  // Append monster MP and cooldowns
                  $monster_stats_str .= "mp=" . intval($monster_mp) . ";maxmp=" . intval($monster_maxmp) . ";";
                  foreach ($monster_cooldowns as $k => $v) { $monster_stats_str .= $k . "=" . intval($v) . ";"; }
                  $um->bind_param("si", $monster_stats_str, $monster_id);
                  $um->execute();
                  $um->close();
                  // keep latest snapshot while ongoing
                  $result["monster"] = array(
                    "id" => $monster_id,
                    "name" => $monster_name,
                    "hp" => $monster_hp,
                    "maxhp" => $monster_maxhp,
                    "mp" => $monster_mp,
                    "maxmp" => $monster_maxmp
                  );
                }
              } else {
                $result["events"][] = array("t" => "player_miss");
                $result["log"][] = $player_name . " missed!";
              }
            }
            if ($i % $effectiveSpd == 0) {

              if (rand(0, 100) > $player_dodge) {
                // monster attack
                // Monster AI: try to use a skill (30% chance if has MP and skills)
                $monster_using_skill = false;
                $monster_skill_name = '';
                $monster_skill_mult = 1.0;
                if (count($monster_skills) > 0 && $monster_maxmp > 0 && rand(1, 100) <= 30) {
                  // Pick a random skill that's off cooldown and affordable
                  $available_skills = array();
                  foreach ($monster_skills as $msk) {
                    $mcd_key = 'cd_' . $msk['skill_id'];
                    $mcd_until = isset($monster_cooldowns[$mcd_key]) ? intval($monster_cooldowns[$mcd_key]) : 0;
                    if ($mcd_until <= $now_ts && $monster_mp >= $msk['mp_cost']) {
                      $available_skills[] = $msk;
                    }
                  }
                  if (count($available_skills) > 0) {
                    $chosen_skill = $available_skills[array_rand($available_skills)];
                    $monster_using_skill = true;
                    $monster_skill_name = $chosen_skill['skill_id'];
                    $monster_skill_mult = $chosen_skill['mult'];
                    $monster_mp = max(0, $monster_mp - $chosen_skill['mp_cost']);
                    $monster_cooldowns['cd_' . $monster_skill_name] = $now_ts + $chosen_skill['cd_sec'];
                    $result["events"][] = array("t" => "monster_skill_used", "name" => $monster_skill_name, "mp_spent" => $chosen_skill['mp_cost']);
                    $result["log"][] = $monster_name . " used " . ucfirst(str_replace('_',' ', $monster_skill_name)) . "!";
                  }
                }
                
                $base_monster_force = $monster_atk + rand(0, $monster_atk);
                $monster_force = $monster_using_skill ? intval(round($base_monster_force * $monster_skill_mult)) : $base_monster_force;
                $player_force = $effectiveDef + rand(0, $effectiveDef);
                $hit = max(1, $monster_force - $player_force); // Minimum 1 damage
                // Monster critical hit check
                $is_monster_crit = false;
                if (rand(1, 100) <= min(95, $monster_crt)) {
                  $is_monster_crit = true;
                  $hit = intval(round($hit * 2.0)); // 2x damage on crit
                }
                
                // Apply damage reduction from shield effects
                if ($effectModifiers['damage_reduction'] > 0) {
                  $reduction_pct = min(75, $effectModifiers['damage_reduction']); // Cap at 75%
                  $reduced = intval($hit * $reduction_pct / 100);
                  $hit = max(0, $hit - $reduced);
                  if ($reduced > 0) {
                    $result["log"][] = "Shield absorbs " . $reduced . " damage!";
                  }
                }
                
                $player_hp = $player_hp - $hit;
                $monsterDamageDealt += $hit;
                
                // Apply regeneration healing
                if ($effectModifiers['regen'] > 0) {
                  $heal = $effectModifiers['regen'];
                  $player_hp = min($player_maxhp, $player_hp + $heal);
                  $result["log"][] = $player_name . " regenerates " . $heal . " HP!";
                }
                $result["events"][] = array("t" => "monster_hit", "dmg" => $hit, "crit" => $is_monster_crit, "player_hp" => max(0, $player_hp));
                $result["log"][] = $monster_name . ($is_monster_crit ? " crits for " : " hits for ") . $hit . " damage" . ($is_monster_crit ? "!" : ".");
                if ($player_hp <= 0) {

                  $result["events"][] = array("t" => "player_died", "name" => $player_name);
                  $result["log"][] = $player_name . " died.";
                  
                  // Get respawn point
                  $respawn_x = 0;
                  $respawn_y = 0;
                  try {
                    $rsp = $db->prepare("SELECT respawn_x, respawn_y FROM game_players WHERE id = ?");
                    $rsp->bind_param("i", $player_id);
                    if ($rsp->execute()) {
                      $rsp_result = $rsp->get_result();
                      if ($rsp_row = mysqli_fetch_array($rsp_result)) {
                        $respawn_x = intval($rsp_row['respawn_x']);
                        $respawn_y = intval($rsp_row['respawn_y']);
                      }
                    }
                    $rsp->close();
                  } catch (Throwable $_) {}
                  
                  // Death penalties: lose 50% gold and 25% of current level EXP
                  $player_gold = intval($player_gold * 0.5);
                  // Calculate EXP loss (25% of current level progress)
                  $exp_for_current_level = ($player_lvl - 1) * 100; // Assuming 100 EXP per level
                  $exp_in_current_level = $player_exp - $exp_for_current_level;
                  $exp_loss = intval($exp_in_current_level * 0.25);
                  $player_exp = max($exp_for_current_level, $player_exp - $exp_loss);
                  
                  // Restore HP/MP
                  $player_hp = $player_maxhp;
                  $player_mp = $player_maxmp;

                  // Update player stats and position (items are kept)
                  $up = $db->prepare("UPDATE game_players SET stats = ?, x = ?, y = ? WHERE id = ?");
                  $player_stats_str = setPlayerStats($player_lvl, $player_exp, $player_hp, $player_maxhp, $player_mp, $player_maxmp, $player_atk, $player_def, $player_spd, $player_evd, $player_gold, $player_crt, $player_skill_points, $player_job, $player_unlocked_skills);
                  // Clean expired effects and cooldowns on death
                  $player_stats_str = cleanExpiredEffects($player_stats_str);
                  $up->bind_param("siii", $player_stats_str, $respawn_x, $respawn_y, $player_id);
                  $up->execute();
                  $up->close();

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
                  $player_stats_str = setPlayerStats($player_lvl, $player_exp, $player_hp, $player_maxhp, $player_mp, $player_maxmp, $player_atk, $player_def, $player_spd, $player_evd, $player_gold, $player_crt, $player_skill_points, $player_job, $player_unlocked_skills);
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
