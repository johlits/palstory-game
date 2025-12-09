<?php

// ============================================================================
// CENTRALIZED STAT PARSING UTILITIES
// ============================================================================

/**
 * Parse a semicolon-delimited stat string into an associative array.
 * Example: "hp=100;atk=10;def=5" => ['hp' => '100', 'atk' => '10', 'def' => '5']
 * 
 * @param string $stats The stat string to parse
 * @return array Associative array of stat key => value pairs
 */
function parseStatsToArray($stats) {
  $result = [];
  if (!$stats || !is_string($stats)) return $result;
  
  $parts = explode(';', $stats);
  foreach ($parts as $part) {
    $part = trim($part);
    if ($part === '' || strpos($part, '=') === false) continue;
    $kv = explode('=', $part, 2);
    if (count($kv) === 2) {
      $result[trim($kv[0])] = trim($kv[1]);
    }
  }
  return $result;
}

/**
 * Get a single stat value from a stat string.
 * 
 * @param string $stats The stat string
 * @param string $key The stat key to retrieve
 * @param mixed $default Default value if key not found
 * @return mixed The stat value or default
 */
function getStat($stats, $key, $default = null) {
  $parsed = parseStatsToArray($stats);
  return isset($parsed[$key]) ? $parsed[$key] : $default;
}

/**
 * Get a stat as an integer.
 * 
 * @param string $stats The stat string
 * @param string $key The stat key to retrieve
 * @param int $default Default value if key not found
 * @return int The stat value as integer
 */
function getStatInt($stats, $key, $default = 0) {
  $val = getStat($stats, $key, null);
  return $val !== null ? intval($val) : $default;
}

/**
 * Convert an associative array back to a stat string.
 * Example: ['hp' => 100, 'atk' => 10] => "hp=100;atk=10;"
 * 
 * @param array $statsArray Associative array of stats
 * @return string The stat string
 */
function arrayToStatString($statsArray) {
  if (!is_array($statsArray) || count($statsArray) === 0) return '';
  
  $parts = [];
  foreach ($statsArray as $key => $value) {
    $parts[] = $key . '=' . $value;
  }
  return implode(';', $parts) . ';';
}

/**
 * Update or add a stat in a stat string.
 * 
 * @param string $stats The original stat string
 * @param string $key The stat key to update
 * @param mixed $value The new value
 * @return string Updated stat string
 */
function setStat($stats, $key, $value) {
  $parsed = parseStatsToArray($stats);
  $parsed[$key] = $value;
  return arrayToStatString($parsed);
}

/**
 * Remove a stat from a stat string.
 * 
 * @param string $stats The original stat string
 * @param string $key The stat key to remove
 * @return string Updated stat string
 */
function removeStat($stats, $key) {
  $parsed = parseStatsToArray($stats);
  unset($parsed[$key]);
  return arrayToStatString($parsed);
}

/**
 * Parse a range value (e.g., "10-20") and return a random value within the range.
 * If not a range, returns the value as-is.
 * 
 * @param string $value The value to parse
 * @return int The resolved value
 */
function resolveStatRange($value) {
  if (str_contains($value, '-')) {
    $range = explode('-', $value);
    if (count($range) === 2) {
      return rand(intval($range[0]), intval($range[1]));
    }
  }
  return intval($value);
}

/**
 * Extract all cooldown stats (cd_*) from a stat string.
 * 
 * @param string $stats The stat string
 * @return array Associative array of cooldown key => timestamp
 */
function extractCooldowns($stats) {
  $cooldowns = [];
  $parsed = parseStatsToArray($stats);
  foreach ($parsed as $key => $value) {
    if (str_starts_with($key, 'cd_')) {
      $cooldowns[$key] = intval($value);
    }
  }
  return $cooldowns;
}

/**
 * Build player stats array from a stat string with defaults.
 * Returns a standardized array with all expected player stat keys.
 * 
 * @param string $stats The stat string
 * @return array Associative array with all player stats
 */
function parsePlayerStats($stats) {
  $parsed = parseStatsToArray($stats);
  
  return [
    'lvl' => isset($parsed['lvl']) ? intval($parsed['lvl']) : 1,
    'exp' => isset($parsed['exp']) ? intval($parsed['exp']) : 0,
    'hp' => isset($parsed['hp']) ? intval($parsed['hp']) : 10,
    'maxhp' => isset($parsed['maxhp']) ? intval($parsed['maxhp']) : 10,
    'mp' => isset($parsed['mp']) ? intval($parsed['mp']) : 0,
    'maxmp' => isset($parsed['maxmp']) ? intval($parsed['maxmp']) : 0,
    'atk' => isset($parsed['atk']) ? intval($parsed['atk']) : 1,
    'def' => isset($parsed['def']) ? intval($parsed['def']) : 0,
    'spd' => isset($parsed['spd']) ? intval($parsed['spd']) : 1,
    'evd' => isset($parsed['evd']) ? intval($parsed['evd']) : 0,
    'crt' => isset($parsed['crt']) ? intval($parsed['crt']) : 5,
    'gold' => isset($parsed['gold']) ? intval($parsed['gold']) : 0,
    'skill_points' => isset($parsed['skill_points']) ? intval($parsed['skill_points']) : 0,
    'job' => isset($parsed['job']) ? $parsed['job'] : 'none',
    'unlocked_skills' => isset($parsed['unlocked_skills']) ? $parsed['unlocked_skills'] : '',
    'cooldowns' => extractCooldowns($stats),
    '_raw' => $parsed  // Keep raw parsed data for any custom fields
  ];
}

/**
 * Build monster stats array from a stat string with defaults.
 * 
 * @param string $stats The stat string
 * @return array Associative array with all monster stats
 */
function parseMonsterStatsToArray($stats) {
  $parsed = parseStatsToArray($stats);
  
  return [
    'hp' => isset($parsed['hp']) ? intval($parsed['hp']) : 1,
    'maxhp' => isset($parsed['maxhp']) ? intval($parsed['maxhp']) : 1,
    'mp' => isset($parsed['mp']) ? intval($parsed['mp']) : 0,
    'maxmp' => isset($parsed['maxmp']) ? intval($parsed['maxmp']) : 0,
    'atk' => isset($parsed['atk']) ? intval($parsed['atk']) : 1,
    'def' => isset($parsed['def']) ? intval($parsed['def']) : 0,
    'spd' => isset($parsed['spd']) ? intval($parsed['spd']) : 1,
    'evd' => isset($parsed['evd']) ? intval($parsed['evd']) : 0,
    'crt' => isset($parsed['crt']) ? intval($parsed['crt']) : 5,
    'drops' => isset($parsed['drops']) ? $parsed['drops'] : '',
    'gold' => isset($parsed['gold']) ? intval($parsed['gold']) : 0,
    'exp' => isset($parsed['exp']) ? intval($parsed['exp']) : 0,
    'cooldowns' => extractCooldowns($stats),
    '_raw' => $parsed
  ];
}

/**
 * Build item stats array from a stat string.
 * 
 * @param string $stats The stat string
 * @return array Associative array with item stats
 */
function parseItemStatsToArray($stats) {
  $parsed = parseStatsToArray($stats);
  
  return [
    'atk' => isset($parsed['atk']) ? intval($parsed['atk']) : 0,
    'def' => isset($parsed['def']) ? intval($parsed['def']) : 0,
    'spd' => isset($parsed['spd']) ? intval($parsed['spd']) : 0,
    'evd' => isset($parsed['evd']) ? intval($parsed['evd']) : 0,
    'crt' => isset($parsed['crt']) ? intval($parsed['crt']) : 0,
    'type' => isset($parsed['type']) ? $parsed['type'] : '',
    '_raw' => $parsed
  ];
}

/**
 * Sum item stats from multiple equipped items.
 * 
 * @param array $items Array of item stat strings or parsed arrays
 * @return array Summed stats ['atk' => total, 'def' => total, ...]
 */
function sumEquippedItemStats($items) {
  $totals = ['atk' => 0, 'def' => 0, 'spd' => 0, 'evd' => 0, 'crt' => 0];
  
  foreach ($items as $item) {
    $stats = is_array($item) ? $item : parseItemStatsToArray($item);
    $totals['atk'] += $stats['atk'];
    $totals['def'] += $stats['def'];
    $totals['spd'] += $stats['spd'];
    $totals['evd'] += $stats['evd'];
    $totals['crt'] += $stats['crt'];
  }
  
  return $totals;
}

// ============================================================================
// LEGACY STAT FUNCTIONS (refactored to use centralized parsing)
// ============================================================================

function parseItemStats($stats)
{
  $statparts = explode(';', $stats);
  $truestats = "";
  $atkSet = false;
  $defSet = false;
  $spdSet = false;
  $evdSet = false;
  $typeSet = false;

  for ($i = 0; $i < count($statparts); $i++) {
    if ($atkSet == false && str_starts_with($statparts[$i], 'atk=')) {
      $atkSet = true;
      $statval = explode('=', $statparts[$i])[1];
      if (str_contains($statval, '-')) {
        $truestats = $truestats . "atk=" . rand(intval(explode('-', $statval)[0]), intval(explode('-', $statval)[1])) . ";";
      } else {
        $truestats = $truestats . "atk=" . $statval . ";";
      }
    }
    if ($defSet == false && str_starts_with($statparts[$i], 'def=')) {
      $statval = explode('=', $statparts[$i])[1];
      if (str_contains($statval, '-')) {
        $truestats = $truestats . "def=" . rand(intval(explode('-', $statval)[0]), intval(explode('-', $statval)[1])) . ";";
      } else {
        $truestats = $truestats . "def=" . $statval . ";";
      }
    }
    if ($spdSet == false && str_starts_with($statparts[$i], 'spd=')) {
      $spdSet = true;
      $statval = explode('=', $statparts[$i])[1];
      if (str_contains($statval, '-')) {
        $truestats = $truestats . "spd=" . rand(intval(explode('-', $statval)[0]), intval(explode('-', $statval)[1])) . ";";
      } else {
        $truestats = $truestats . "spd=" . $statval . ";";
      }
    }
    if ($evdSet == false && str_starts_with($statparts[$i], 'evd=')) {
      $evdSet = true;
      $statval = explode('=', $statparts[$i])[1];
      if (str_contains($statval, '-')) {
        $truestats = $truestats . "evd=" . rand(intval(explode('-', $statval)[0]), intval(explode('-', $statval)[1])) . ";";
      } else {
        $truestats = $truestats . "evd=" . $statval . ";";
      }
    }
    if ($typeSet == false && str_starts_with($statparts[$i], 'type=')) {
      $typeSet = true;
      $statval = explode('=', $statparts[$i])[1];
      $truestats = $truestats . "type=" . $statval . ";";
    }
  }

  return $truestats;
}

function parseMonsterStats($stats)
{
  $statparts = explode(';', $stats);
  $truestats = "";
  $atkSet = false;
  $defSet = false;
  $spdSet = false;
  $evdSet = false;
  $dropsSet = false;
  $hpSet = false;
  $goldSet = false;
  $expSet = false;

  for ($i = 0; $i < count($statparts); $i++) {
    if ($atkSet == false && str_starts_with($statparts[$i], 'atk=')) {
      $atkSet = true;
      $statval = explode('=', $statparts[$i])[1];
      if (str_contains($statval, '-')) {
        $truestats = $truestats . "atk=" . rand(intval(explode('-', $statval)[0]), intval(explode('-', $statval)[1])) . ";";
      } else {
        $truestats = $truestats . "atk=" . $statval . ";";
      }
    }
    if ($defSet == false && str_starts_with($statparts[$i], 'def=')) {
      $statval = explode('=', $statparts[$i])[1];
      if (str_contains($statval, '-')) {
        $truestats = $truestats . "def=" . rand(intval(explode('-', $statval)[0]), intval(explode('-', $statval)[1])) . ";";
      } else {
        $truestats = $truestats . "def=" . $statval . ";";
      }
    }
    if ($spdSet == false && str_starts_with($statparts[$i], 'spd=')) {
      $spdSet = true;
      $statval = explode('=', $statparts[$i])[1];
      if (str_contains($statval, '-')) {
        $truestats = $truestats . "spd=" . rand(intval(explode('-', $statval)[0]), intval(explode('-', $statval)[1])) . ";";
      } else {
        $truestats = $truestats . "spd=" . $statval . ";";
      }
    }
    if ($evdSet == false && str_starts_with($statparts[$i], 'evd=')) {
      $evdSet = true;
      $statval = explode('=', $statparts[$i])[1];
      if (str_contains($statval, '-')) {
        $truestats = $truestats . "evd=" . rand(intval(explode('-', $statval)[0]), intval(explode('-', $statval)[1])) . ";";
      } else {
        $truestats = $truestats . "evd=" . $statval . ";";
      }
    }
    if ($dropsSet == false && str_starts_with($statparts[$i], 'drops=')) {
      $dropsSet = true;
      $statval = explode('=', $statparts[$i])[1];
      $truestats = $truestats . "drops=" . $statval . ";";
    }
    if ($hpSet == false && str_starts_with($statparts[$i], 'hp=')) {
      $hpSet = true;
      $statval = explode('=', $statparts[$i])[1];
      if (str_contains($statval, '-')) {
        $hp = rand(intval(explode('-', $statval)[0]), intval(explode('-', $statval)[1]));
        $truestats = $truestats . "hp=" . $hp . ";";
        $truestats = $truestats . "maxhp=" . $hp . ";";
      } else {
        $truestats = $truestats . "hp=" . $statval . ";";
        $truestats = $truestats . "maxhp=" . $statval . ";";
      }
    }
    if ($goldSet == false && str_starts_with($statparts[$i], 'gold=')) {
      $goldSet = true;
      $statval = explode('=', $statparts[$i])[1];
      if (str_contains($statval, '-')) {
        $truestats = $truestats . "gold=" . rand(intval(explode('-', $statval)[0]), intval(explode('-', $statval)[1])) . ";";
      } else {
        $truestats = $truestats . "gold=" . $statval . ";";
      }
    }
    if ($expSet == false && str_starts_with($statparts[$i], 'exp=')) {
      $expSet = true;
      $statval = explode('=', $statparts[$i])[1];
      $truestats = $truestats . "exp=" . $statval . ";";
    }
  }
  return $truestats;
}

function setPlayerStats($lvl, $exp, $hp, $maxhp, $mp, $maxmp, $atk, $def, $spd, $evd, $gold, $crt = 5, $skill_points = 0, $job = 'none', $unlocked_skills = '')
{
  return "lvl=" . $lvl . 
         ";exp=" . $exp . 
         ";hp=" . $hp . 
         ";maxhp=" . $maxhp . 
         ";mp=" . $mp . 
         ";maxmp=" . $maxmp . 
         ";atk=" . $atk . 
         ";def=" . $def . 
         ";spd=" . $spd . 
         ";evd=" . $evd . 
         ";crt=" . $crt . 
         ";skill_points=" . $skill_points . 
         ";job=" . $job . 
         ";unlocked_skills=" . $unlocked_skills . 
         ";gold=" . $gold . ";";
}

function setMonsterStats($hp, $maxhp, $atk, $def, $spd, $evd, $drops, $gold, $exp)
{
  return "hp=" . $hp . ";maxhp=" . $maxhp . ";atk=" . $atk . ";def=" . $def . ";spd=" . $spd . ";evd=" . $evd . ";drops=" . $drops . ";gold=" . $gold . ";exp=" . $exp . ";";
}

function verifyLocationStats($locationStats)
{
  return $locationStats . ";";
}

// Server-side passability helper: returns true if tile is passable
// Interprets flags present in either game-level stats (gstats) or resource-level stats:
// - walk=0 => not passable
// - passable=0 => not passable
// - blocked=1 => not passable
// - impassable=1 => not passable
function isLocationPassable($gstats, $stats)
{
  $gs = strval($gstats);
  $rs = strval($stats);
  $all = verifyLocationStats($gs) . verifyLocationStats($rs);
  $parts = explode(';', $all);
  $walk = null; $passable = null; $blocked = null; $impassable = null;
  foreach ($parts as $p) {
    if ($p === '') continue;
    if (str_starts_with($p, 'walk=')) { $walk = intval(explode('=', $p)[1]); }
    else if (str_starts_with($p, 'passable=')) { $passable = intval(explode('=', $p)[1]); }
    else if (str_starts_with($p, 'blocked=')) { $blocked = intval(explode('=', $p)[1]); }
    else if (str_starts_with($p, 'impassable=')) { $impassable = intval(explode('=', $p)[1]); }
  }
  if ($walk === 0) return false;
  if ($passable === 0) return false;
  if ($blocked === 1) return false;
  if ($impassable === 1) return false;
  return true;
}

// Heartbeat helper: update player's last_seen timestamp for auto-save/session tracking
function touchPlayer($db, $room_id, $player_name)
{
  if (!$db) return;
  // Only bump last_seen if it's older than 5 seconds to reduce write churn
  $q = $db->prepare("UPDATE game_players SET last_seen = CURRENT_TIMESTAMP WHERE room_id = ? AND name = ? AND (last_seen IS NULL OR last_seen < (NOW() - INTERVAL 5 SECOND))");
  if ($q) {
    $q->bind_param("is", $room_id, $player_name);
    $q->execute();
    $q->close();
  }
}

// Calculate passive skill bonuses for a player
// Returns an associative array of stat bonuses: ['atk' => +5, 'def' => +3, ...]
function getPassiveSkillBonuses($db, $unlocked_skills)
{
  $bonuses = array(
    'atk' => 0, 'def' => 0, 'spd' => 0, 'evd' => 0, 'crt' => 0,
    'maxhp' => 0, 'maxmp' => 0
  );
  
  if (!$db || $unlocked_skills === '' || $unlocked_skills === null) {
    return $bonuses;
  }
  
  $unlocked_array = explode(',', $unlocked_skills);
  if (count($unlocked_array) === 0) {
    return $bonuses;
  }
  
  try {
    // Build safe IN clause for skill_id
    $placeholders = implode(',', array_fill(0, count($unlocked_array), '?'));
    $sql = "SELECT stat_modifiers FROM resources_skills WHERE skill_id IN ($placeholders) AND skill_type = 'passive' AND banned = 0";
    $stmt = $db->prepare($sql);
    
    if ($stmt) {
      // Bind all skill IDs dynamically
      $types = str_repeat('s', count($unlocked_array));
      $stmt->bind_param($types, ...$unlocked_array);
      
      if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
          $modifiers = $row['stat_modifiers'];
          if ($modifiers !== null && $modifiers !== '') {
            // Parse stat modifiers (e.g., "atk=+5;def=+3;maxhp=+10")
            $parts = explode(';', $modifiers);
            foreach ($parts as $part) {
              if ($part === '') continue;
              if (strpos($part, '=') !== false) {
                list($stat, $value) = explode('=', $part, 2);
                $stat = trim($stat);
                $value = trim($value);
                // Parse value (supports +5, -3, etc.)
                if (preg_match('/^([+\-]?\d+)$/', $value, $matches)) {
                  $num = intval($matches[1]);
                  if (isset($bonuses[$stat])) {
                    $bonuses[$stat] += $num;
                  }
                }
              }
            }
          }
        }
      }
      $stmt->close();
    }
  } catch (Throwable $_) {
    // Return empty bonuses on error
  }
  
  return $bonuses;
}

// Status Effects Helpers

// Parse active status effects from player stats string
// Returns array of effects: ['effect_name' => expiry_timestamp, ...]
function parseStatusEffects($stats_str) {
  $effects = array();
  if (!$stats_str) return $effects;
  
  $parts = explode(';', $stats_str);
  foreach ($parts as $p) {
    if ($p === '') continue;
    if (str_starts_with($p, 'effect_')) {
      $kv = explode('=', $p, 2);
      if (count($kv) === 2) {
        $effect_name = substr($kv[0], 7); // Remove 'effect_' prefix
        $expiry = intval($kv[1]);
        if ($expiry > time()) {
          $effects[$effect_name] = $expiry;
        }
      }
    }
  }
  return $effects;
}

// Add a status effect to player stats
// Returns updated stats string
function addStatusEffect($stats_str, $effect_name, $duration_sec) {
  $expiry = time() + $duration_sec;
  // Remove existing effect if present
  $stats_str = removeStatusEffect($stats_str, $effect_name);
  // Add new effect
  return $stats_str . 'effect_' . $effect_name . '=' . $expiry . ';';
}

// Remove a status effect from player stats
// Returns updated stats string
function removeStatusEffect($stats_str, $effect_name) {
  if (!$stats_str) return '';
  
  $parts = explode(';', $stats_str);
  $new_parts = array();
  $effect_key = 'effect_' . $effect_name;
  
  foreach ($parts as $p) {
    if ($p === '') continue;
    if (!str_starts_with($p, $effect_key . '=')) {
      $new_parts[] = $p;
    }
  }
  
  return implode(';', $new_parts) . ';';
}

// Clean expired status effects from stats string
// Returns updated stats string
function cleanExpiredEffects($stats_str) {
  if (!$stats_str) return '';
  
  $parts = explode(';', $stats_str);
  $new_parts = array();
  $now = time();
  
  foreach ($parts as $p) {
    if ($p === '') continue;
    if (str_starts_with($p, 'effect_')) {
      $kv = explode('=', $p, 2);
      if (count($kv) === 2) {
        $expiry = intval($kv[1]);
        if ($expiry > $now) {
          $new_parts[] = $p; // Keep active effect
        }
        // Skip expired effects
      }
    } else {
      $new_parts[] = $p; // Keep non-effect stats
    }
  }
  
  return implode(';', $new_parts) . ';';
}

// Calculate stat modifiers from active status effects
// Returns array: ['atk' => modifier, 'def' => modifier, ...]
function getStatusEffectModifiers($effects) {
  $modifiers = array(
    'atk' => 0,
    'def' => 0,
    'spd' => 0,
    'evd' => 0,
    'damage_reduction' => 0, // Percentage
    'regen' => 0, // HP per turn
    'poison' => 0 // Damage per turn
  );
  
  foreach ($effects as $effect_name => $expiry) {
    // Parse effect name for modifiers
    // Format: effecttype_value (e.g., shield_30, atk_boost_20)
    if (str_starts_with($effect_name, 'shield_')) {
      $value = intval(substr($effect_name, 7));
      $modifiers['damage_reduction'] += $value;
    } else if (str_starts_with($effect_name, 'regen_')) {
      $value = intval(substr($effect_name, 6));
      $modifiers['regen'] += $value;
    } else if (str_starts_with($effect_name, 'poison_')) {
      $value = intval(substr($effect_name, 7));
      $modifiers['poison'] += $value;
    } else if (str_starts_with($effect_name, 'atk_boost_')) {
      $value = intval(substr($effect_name, 10));
      $modifiers['atk'] += $value;
    } else if (str_starts_with($effect_name, 'def_boost_')) {
      $value = intval(substr($effect_name, 10));
      $modifiers['def'] += $value;
    } else if (str_starts_with($effect_name, 'spd_boost_')) {
      $value = intval(substr($effect_name, 10));
      $modifiers['spd'] += $value;
    } else if (str_starts_with($effect_name, 'evd_boost_')) {
      $value = intval(substr($effect_name, 10));
      $modifiers['evd'] += $value;
    }
  }
  
  return $modifiers;
}

// Telemetry-based rate limit helper (best-effort)
// Checks how many matching actions a player performed in a room within a window.
// Returns an array: [ 'ok' => bool, 'count' => int, 'window_sec' => int, 'cutoff' => 'YYYY-mm-dd HH:ii:ss' ]
function telemetryRateLimitCheck($db, $room_id, $player_name, $actions, $window_sec, $max_actions)
{
  $out = [ 'ok' => true, 'count' => 0, 'window_sec' => intval($window_sec), 'cutoff' => null ];
  try {
    if (!$db) { return $out; }
    if (!is_array($actions) || count($actions) === 0) { return $out; }
    // Whitelist action tokens to prevent injection; only allow lowercase letters and underscores
    $safe = [];
    foreach ($actions as $a) {
      $a = strval($a);
      if (preg_match('/^[a-z_]+$/', $a)) { $safe[] = $a; }
    }
    if (count($safe) === 0) { return $out; }
    // Build action IN list safely (quoted literals), other fields use prepared params
    $escaped = array_map(function($s) use ($db) { return "'" . $db->real_escape_string($s) . "'"; }, $safe);
    $in_list = implode(",", $escaped);
    $win = max(1, intval($window_sec));
    $cutoff = date('Y-m-d H:i:s', time() - $win);
    $out['cutoff'] = $cutoff;
    $sql = "SELECT COUNT(*) AS c FROM game_logs WHERE action IN (".$in_list.") AND room_id = ? AND ts > ? AND player_name = ?";
    if ($st = $db->prepare($sql)) {
      $st->bind_param("iss", $room_id, $cutoff, $player_name);
      if ($st->execute()) {
        $res = $st->get_result();
        if ($row = $res->fetch_assoc()) {
          $cnt = intval($row['c']);
          $out['count'] = $cnt;
          if ($cnt >= intval($max_actions)) {
            $out['ok'] = false;
          }
        }
      }
      $st->close();
    }
  } catch (Throwable $_) { /* ignore */ }
  return $out;
}

/**
 * Track a skill usage in player's recent skill history
 * Stores last 3 skills with timestamps in player stats as: last_skills=skill1:ts1,skill2:ts2,skill3:ts3
 */
function trackSkillUsage($stats_str, $skill_id) {
  $now = time();
  $parts = explode(';', $stats_str);
  $last_skills = '';
  $new_parts = array();
  
  // Find and parse existing last_skills
  foreach ($parts as $p) {
    if ($p === '') continue;
    if (str_starts_with($p, 'last_skills=')) {
      $last_skills = explode('=', $p, 2)[1];
    } else {
      $new_parts[] = $p;
    }
  }
  
  // Parse existing skills
  $skill_history = array();
  if ($last_skills !== '') {
    $entries = explode(',', $last_skills);
    foreach ($entries as $entry) {
      if (strpos($entry, ':') !== false) {
        list($sid, $ts) = explode(':', $entry, 2);
        $skill_history[] = array('skill' => $sid, 'ts' => intval($ts));
      }
    }
  }
  
  // Add new skill
  $skill_history[] = array('skill' => $skill_id, 'ts' => $now);
  
  // Keep only last 3
  $skill_history = array_slice($skill_history, -3);
  
  // Rebuild last_skills string
  $entries = array();
  foreach ($skill_history as $sh) {
    $entries[] = $sh['skill'] . ':' . $sh['ts'];
  }
  $new_parts[] = 'last_skills=' . implode(',', $entries);
  
  return implode(';', $new_parts) . ';';
}

/**
 * Check if a skill synergy is triggered
 * Returns array with 'triggered' (bool) and 'bonus' (string) if synergy found
 */
function checkSkillSynergy($db, $stats_str, $skill_id) {
  $result = array('triggered' => false, 'bonus' => '', 'synergy_skill' => '');
  
  try {
    // Load skill synergy definition (gracefully handle if columns don't exist yet)
    $synergy_query = $db->prepare("SELECT synergy_with, synergy_bonus, synergy_window_sec FROM resources_skills WHERE skill_id = ? AND synergy_with IS NOT NULL LIMIT 1");
    if (!$synergy_query) return $result;
    
    $synergy_query->bind_param("s", $skill_id);
    if (!@$synergy_query->execute()) {
      @$synergy_query->close();
      return $result;
    }
    
    $synergy_result = $synergy_query->get_result();
    if (mysqli_num_rows($synergy_result) === 0) {
      $synergy_query->close();
      return $result;
    }
    
    $synergy_row = mysqli_fetch_array($synergy_result);
    $required_skill = $synergy_row['synergy_with'];
    $bonus = $synergy_row['synergy_bonus'];
    $window_sec = intval($synergy_row['synergy_window_sec']);
    $synergy_query->close();
    
    // Parse last_skills from player stats
    $parts = explode(';', $stats_str);
    $last_skills = '';
    foreach ($parts as $p) {
      if (str_starts_with($p, 'last_skills=')) {
        $last_skills = explode('=', $p, 2)[1];
        break;
      }
    }
    
    if ($last_skills === '') return $result;
    
    // Check if required skill was used within time window
    $now = time();
    $entries = explode(',', $last_skills);
    foreach ($entries as $entry) {
      if (strpos($entry, ':') !== false) {
        list($sid, $ts) = explode(':', $entry, 2);
        $ts = intval($ts);
        if ($sid === $required_skill && ($now - $ts) <= $window_sec) {
          $result['triggered'] = true;
          $result['bonus'] = $bonus;
          $result['synergy_skill'] = $required_skill;
          break;
        }
      }
    }
  } catch (Throwable $_) { /* ignore */ }
  
  return $result;
}

/**
 * Apply synergy bonus to combat calculations
 * Parses bonus string (e.g., "damage=+50%;crit=+20%") and returns modified values
 */
function applySynergyBonus($bonus_str, $base_damage, $base_crit = 0) {
  $result = array('damage' => $base_damage, 'crit' => $base_crit, 'effects' => array());
  
  if ($bonus_str === '') return $result;
  
  try {
    $bonuses = explode(';', $bonus_str);
    foreach ($bonuses as $b) {
      if (strpos($b, '=') === false) continue;
      list($key, $val) = explode('=', $b, 2);
      $key = trim($key);
      $val = trim($val);
      
      if ($key === 'damage') {
        // Parse percentage or flat bonus
        if (strpos($val, '%') !== false) {
          $pct = floatval(str_replace('%', '', $val));
          $result['damage'] = intval($base_damage * (1 + $pct / 100));
        } else {
          $result['damage'] = $base_damage + intval($val);
        }
      } elseif ($key === 'crit') {
        // Crit chance bonus
        if (strpos($val, '%') !== false) {
          $pct = floatval(str_replace('%', '', $val));
          $result['crit'] = $base_crit + $pct;
        } else {
          $result['crit'] = $base_crit + intval($val);
        }
      } elseif ($key === 'status') {
        // Status effect to apply
        $result['effects'][] = $val;
      } elseif ($key === 'cooldown') {
        // Cooldown reduction (stored for later)
        $result['cooldown_reduction'] = intval($val);
      } elseif ($key === 'mp_restore') {
        // MP restoration
        $result['mp_restore'] = intval($val);
      } elseif ($key === 'healing') {
        // Healing bonus (for healing skills)
        if (strpos($val, '%') !== false) {
          $pct = floatval(str_replace('%', '', $val));
          $result['healing_mult'] = 1 + $pct / 100;
        }
      }
    }
  } catch (Throwable $_) { /* ignore */ }
  
  return $result;
}
