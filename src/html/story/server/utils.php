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

function setPlayerStats($lvl, $exp, $hp, $maxhp, $atk, $def, $spd, $evd, $gold)
{
  return "lvl=" . $lvl . ";exp=" . $exp . ";hp=" . $hp . ";maxhp=" . $maxhp . ";atk=" . $atk . ";def=" . $def . ";spd=" . $spd . ";evd=" . $evd . ";gold=" . $gold . ";";
}

function setMonsterStats($hp, $maxhp, $atk, $def, $spd, $evd, $drops, $gold, $exp)
{
  return "hp=" . $hp . ";maxhp=" . $maxhp . ";atk=" . $atk . ";def=" . $def . ";spd=" . $spd . ";evd=" . $evd . ";drops=" . $drops . ";gold=" . $gold . ";exp=" . $exp . ";";
}

function verifyLocationStats($locationStats)
{
  return $locationStats . ";";
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
