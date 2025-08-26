<?php

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

function setPlayerStats($lvl, $exp, $hp, $maxhp, $mp, $maxmp, $atk, $def, $spd, $evd, $gold)
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
