<?php

// Get shop inventory (items available for purchase)
function getShopInventory($db, $data)
{
  $player_name = clean($data['get_shop']);
  $room_id = intval(clean($data['room_id']));
  
  $result = array(
    'success' => false,
    'items' => array(),
    'message' => ''
  );
  
  // Get player level to filter items
  $player_lvl = 1;
  try {
    $sp = $db->prepare("SELECT stats FROM game_players WHERE room_id = ? AND name = ?");
    $sp->bind_param("is", $room_id, $player_name);
    if ($sp->execute()) {
      $rp = $sp->get_result();
      if ($row = mysqli_fetch_array($rp)) {
        $stats_str = $row['stats'];
        $parts = explode(';', $stats_str);
        foreach ($parts as $p) {
          if (str_starts_with($p, 'lvl=')) {
            $player_lvl = intval(explode('=', $p)[1]);
            break;
          }
        }
      }
    }
    $sp->close();
  } catch (Throwable $_) {}
  
  // Get shop inventory items available at player's level
  try {
    $si = $db->prepare("SELECT si.item_id, si.price, si.category, ri.name, ri.description, ri.stats, ri.rarity 
                        FROM shop_inventory si 
                        INNER JOIN resources_items ri ON si.item_id = ri.name 
                        WHERE si.available_at_level <= ? 
                        ORDER BY si.category, si.price");
    $si->bind_param("i", $player_lvl);
    if ($si->execute()) {
      $r = $si->get_result();
      while ($row = mysqli_fetch_array($r)) {
        $result['items'][] = array(
          'item_id' => $row['item_id'],
          'name' => $row['name'],
          'description' => $row['description'],
          'price' => intval($row['price']),
          'category' => $row['category'],
          'stats' => $row['stats'],
          'rarity' => $row['rarity']
        );
      }
    }
    $si->close();
  } catch (Throwable $_) {
    $result['message'] = 'Failed to load shop inventory.';
    return $result;
  }
  
  $result['success'] = true;
  return $result;
}

// Buy item from shop
function buyItem($db, $data)
{
  $player_name = clean($data['buy_item']);
  $room_id = intval(clean($data['room_id']));
  $item_id = clean($data['item_id']);
  
  $result = array(
    'success' => false,
    'message' => ''
  );
  
  // Rate limit: max 5 purchases per 10 seconds
  $rlc = telemetryRateLimitCheck($db, $room_id, $player_name, ['buy_item'], 10, 5);
  if (!$rlc['ok']) {
    $result['message'] = 'Please wait before making another purchase.';
    return $result;
  }
  
  // Get player
  $sp = $db->prepare("SELECT id, x, y, stats FROM game_players WHERE room_id = ? AND name = ?");
  $sp->bind_param("is", $room_id, $player_name);
  
  if (!$sp->execute()) {
    $sp->close();
    $result['message'] = 'Player not found.';
    return $result;
  }
  
  $rp = $sp->get_result();
  if (mysqli_num_rows($rp) === 0) {
    $sp->close();
    $result['message'] = 'Player not found.';
    return $result;
  }
  
  $row = mysqli_fetch_array($rp);
  $player_id = intval($row['id']);
  $player_x = intval($row['x']);
  $player_y = intval($row['y']);
  $stats_str = $row['stats'];
  $sp->close();
  
  // Parse player stats to get gold
  $player_gold = 0;
  $parts = explode(';', $stats_str);
  foreach ($parts as $p) {
    if (str_starts_with($p, 'gold=')) {
      $player_gold = intval(explode('=', $p)[1]);
      break;
    }
  }
  
  // Check if player is at a town
  $sl = $db->prepare("SELECT rl.location_type, rl.name FROM game_locations gl INNER JOIN resources_locations rl ON gl.resource_id = rl.id WHERE gl.room_id = ? AND gl.x = ? AND gl.y = ?");
  $sl->bind_param("iii", $room_id, $player_x, $player_y);
  
  if (!$sl->execute()) {
    $sl->close();
    $result['message'] = 'Cannot shop here.';
    return $result;
  }
  
  $lr = $sl->get_result();
  if (mysqli_num_rows($lr) === 0) {
    $sl->close();
    $result['message'] = 'Cannot shop here.';
    return $result;
  }
  
  $loc_row = mysqli_fetch_array($lr);
  $location_type = $loc_row['location_type'];
  $sl->close();
  
  // Only allow shopping at towns
  if ($location_type !== 'town') {
    $result['message'] = 'You can only shop at towns.';
    return $result;
  }
  
  // Get item price from shop
  $item_price = 0;
  $item_name = '';
  try {
    $pi = $db->prepare("SELECT si.price, ri.name FROM shop_inventory si INNER JOIN resources_items ri ON si.item_id = ri.name WHERE si.item_id = ?");
    $pi->bind_param("s", $item_id);
    if ($pi->execute()) {
      $pr = $pi->get_result();
      if ($prow = mysqli_fetch_array($pr)) {
        $item_price = intval($prow['price']);
        $item_name = $prow['name'];
      }
    }
    $pi->close();
  } catch (Throwable $_) {}
  
  if ($item_price === 0) {
    $result['message'] = 'Item not available in shop.';
    return $result;
  }
  
  // Check if player has enough gold
  if ($player_gold < $item_price) {
    $result['message'] = 'Not enough gold. Need ' . $item_price . ' gold.';
    return $result;
  }
  
  // Deduct gold and add item to player inventory
  $new_gold = $player_gold - $item_price;
  
  // Update player gold
  $stats_parts = explode(';', $stats_str);
  $new_stats_parts = array();
  foreach ($stats_parts as $p) {
    if ($p === '') continue;
    if (str_starts_with($p, 'gold=')) {
      $new_stats_parts[] = 'gold=' . $new_gold;
    } else {
      $new_stats_parts[] = $p;
    }
  }
  $new_stats = implode(';', $new_stats_parts) . ';';
  
  $up = $db->prepare("UPDATE game_players SET stats = ? WHERE id = ?");
  $up->bind_param("si", $new_stats, $player_id);
  $up->execute();
  $up->close();
  
  // Add item to inventory (game_items doesn't have x,y columns - items are in player inventory)
  $ii = $db->prepare("INSERT INTO game_items (room_id, owner_id, resource_id, stats) 
                      SELECT ?, ?, id, stats FROM resources_items WHERE name = ? LIMIT 1");
  $ii->bind_param("iis", $room_id, $player_id, $item_id);
  $ii->execute();
  $ii->close();
  
  touchPlayer($db, $room_id, $player_name);
  
  // Telemetry
  try {
    $det = json_encode(['item_id' => $item_id, 'price' => $item_price, 'gold_remaining' => $new_gold]);
    if ($det === false) { $det = '{"item_id":"'.addslashes($item_id).'","price":'.intval($item_price).'}'; }
    if ($lg = $db->prepare("INSERT INTO game_logs (room_id, player_name, action, details) VALUES (?, ?, 'buy_item', ?)")) {
      $lg->bind_param("iss", $room_id, $player_name, $det);
      $lg->execute();
      $lg->close();
    }
  } catch (Throwable $_) { }
  
  $result['success'] = true;
  $result['message'] = 'Purchased ' . $item_name . ' for ' . $item_price . ' gold!';
  $result['gold_remaining'] = $new_gold;
  
  return $result;
}

// Sell item to shop
function sellItem($db, $data)
{
  $player_name = clean($data['sell_item']);
  $room_id = intval(clean($data['room_id']));
  $item_db_id = intval(clean($data['item_db_id']));
  
  $result = array(
    'success' => false,
    'message' => ''
  );
  
  // Rate limit: max 5 sales per 10 seconds
  $rlc = telemetryRateLimitCheck($db, $room_id, $player_name, ['sell_item'], 10, 5);
  if (!$rlc['ok']) {
    $result['message'] = 'Please wait before selling another item.';
    return $result;
  }
  
  // Get player
  $sp = $db->prepare("SELECT id, x, y, stats FROM game_players WHERE room_id = ? AND name = ?");
  $sp->bind_param("is", $room_id, $player_name);
  
  if (!$sp->execute()) {
    $sp->close();
    $result['message'] = 'Player not found.';
    return $result;
  }
  
  $rp = $sp->get_result();
  if (mysqli_num_rows($rp) === 0) {
    $sp->close();
    $result['message'] = 'Player not found.';
    return $result;
  }
  
  $row = mysqli_fetch_array($rp);
  $player_id = intval($row['id']);
  $player_x = intval($row['x']);
  $player_y = intval($row['y']);
  $stats_str = $row['stats'];
  $sp->close();
  
  // Check if player is at a town
  $sl = $db->prepare("SELECT rl.location_type FROM game_locations gl INNER JOIN resources_locations rl ON gl.resource_id = rl.id WHERE gl.room_id = ? AND gl.x = ? AND gl.y = ?");
  $sl->bind_param("iii", $room_id, $player_x, $player_y);
  
  if (!$sl->execute()) {
    $sl->close();
    $result['message'] = 'Cannot sell here.';
    return $result;
  }
  
  $lr = $sl->get_result();
  if (mysqli_num_rows($lr) === 0) {
    $sl->close();
    $result['message'] = 'Cannot sell here.';
    return $result;
  }
  
  $loc_row = mysqli_fetch_array($lr);
  $location_type = $loc_row['location_type'];
  $sl->close();
  
  // Only allow selling at towns
  if ($location_type !== 'town') {
    $result['message'] = 'You can only sell items at towns.';
    return $result;
  }
  
  // Get item and verify ownership
  $item_name = '';
  $item_id = '';
  $sell_price = 0;
  
  try {
    $gi = $db->prepare("SELECT gi.owner_id, ri.name FROM game_items gi INNER JOIN resources_items ri ON gi.resource_id = ri.id WHERE gi.id = ?");
    $gi->bind_param("i", $item_db_id);
    if ($gi->execute()) {
      $gr = $gi->get_result();
      if ($grow = mysqli_fetch_array($gr)) {
        if (intval($grow['owner_id']) !== $player_id) {
          $gi->close();
          $result['message'] = 'You do not own this item.';
          return $result;
        }
        $item_name = $grow['name'];
        $item_id = $grow['name']; // Use name as item_id for shop_inventory lookup
      } else {
        $gi->close();
        $result['message'] = 'Item not found.';
        return $result;
      }
    }
    $gi->close();
  } catch (Throwable $_) {
    $result['message'] = 'Failed to verify item.';
    return $result;
  }
  
  // Calculate sell price (50% of shop price, or base value)
  try {
    $ps = $db->prepare("SELECT price FROM shop_inventory WHERE item_id = ?");
    $ps->bind_param("s", $item_id);
    if ($ps->execute()) {
      $psr = $ps->get_result();
      if ($psrow = mysqli_fetch_array($psr)) {
        $sell_price = intval(intval($psrow['price']) * 0.5);
      } else {
        // Not in shop, use base value of 10 gold
        $sell_price = 10;
      }
    }
    $ps->close();
  } catch (Throwable $_) {
    $sell_price = 10; // Fallback
  }
  
  // Remove item from inventory
  $di = $db->prepare("DELETE FROM game_items WHERE id = ?");
  $di->bind_param("i", $item_db_id);
  $di->execute();
  $di->close();
  
  // Add gold to player
  $player_gold = 0;
  $parts = explode(';', $stats_str);
  foreach ($parts as $p) {
    if (str_starts_with($p, 'gold=')) {
      $player_gold = intval(explode('=', $p)[1]);
      break;
    }
  }
  
  $new_gold = $player_gold + $sell_price;
  
  $stats_parts = explode(';', $stats_str);
  $new_stats_parts = array();
  foreach ($stats_parts as $p) {
    if ($p === '') continue;
    if (str_starts_with($p, 'gold=')) {
      $new_stats_parts[] = 'gold=' . $new_gold;
    } else {
      $new_stats_parts[] = $p;
    }
  }
  $new_stats = implode(';', $new_stats_parts) . ';';
  
  $up = $db->prepare("UPDATE game_players SET stats = ? WHERE id = ?");
  $up->bind_param("si", $new_stats, $player_id);
  $up->execute();
  $up->close();
  
  touchPlayer($db, $room_id, $player_name);
  
  // Telemetry
  try {
    $det = json_encode(['item_id' => $item_id, 'sell_price' => $sell_price, 'gold_total' => $new_gold]);
    if ($det === false) { $det = '{"item_id":"'.addslashes($item_id).'","sell_price":'.intval($sell_price).'}'; }
    if ($lg = $db->prepare("INSERT INTO game_logs (room_id, player_name, action, details) VALUES (?, ?, 'sell_item', ?)")) {
      $lg->bind_param("iss", $room_id, $player_name, $det);
      $lg->execute();
      $lg->close();
    }
  } catch (Throwable $_) { }
  
  $result['success'] = true;
  $result['message'] = 'Sold ' . $item_name . ' for ' . $sell_price . ' gold!';
  $result['gold_total'] = $new_gold;
  
  return $result;
}

?>
