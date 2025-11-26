<?php

// Get player's stored items
function getStorage($db, $data)
{
  $player_name = clean($data['get_storage']);
  $room_id = intval(clean($data['room_id']));
  
  $result = array(
    'success' => false,
    'items' => array(),
    'slots_used' => 0,
    'slots_max' => 20,
    'message' => ''
  );
  
  // Get player
  $sp = $db->prepare("SELECT id, x, y, storage_slots FROM game_players WHERE room_id = ? AND name = ?");
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
  $storage_slots = intval($row['storage_slots']);
  $sp->close();
  
  // Check if player is at a town
  $sl = $db->prepare("SELECT rl.location_type FROM game_locations gl INNER JOIN resources_locations rl ON gl.resource_id = rl.id WHERE gl.room_id = ? AND gl.x = ? AND gl.y = ?");
  $sl->bind_param("iii", $room_id, $player_x, $player_y);
  
  if (!$sl->execute()) {
    $sl->close();
    $result['message'] = 'Cannot access storage here.';
    return $result;
  }
  
  $lr = $sl->get_result();
  if (mysqli_num_rows($lr) === 0) {
    $sl->close();
    $result['message'] = 'Cannot access storage here.';
    return $result;
  }
  
  $loc_row = mysqli_fetch_array($lr);
  $location_type = $loc_row['location_type'];
  $sl->close();
  
  // Only allow storage access at towns
  if ($location_type !== 'town') {
    $result['message'] = 'You can only access storage at towns.';
    return $result;
  }
  
  // Get stored items
  try {
    $si = $db->prepare("SELECT ps.id, ps.item_stats, ps.quantity, ps.stored_at, ri.id as resource_id, ri.name, ri.image, ri.description, ri.rarity 
                        FROM player_storage ps 
                        INNER JOIN resources_items ri ON ps.item_resource_id = ri.id 
                        WHERE ps.player_id = ? 
                        ORDER BY ps.stored_at DESC");
    $si->bind_param("i", $player_id);
    if ($si->execute()) {
      $r = $si->get_result();
      while ($item = mysqli_fetch_array($r)) {
        $result['items'][] = array(
          'storage_id' => intval($item['id']),
          'resource_id' => intval($item['resource_id']),
          'name' => $item['name'],
          'image' => $item['image'],
          'description' => $item['description'],
          'stats' => $item['item_stats'],
          'rarity' => $item['rarity'],
          'quantity' => intval($item['quantity']),
          'stored_at' => $item['stored_at']
        );
      }
    }
    $si->close();
  } catch (Throwable $_) {
    $result['message'] = 'Failed to load storage.';
    return $result;
  }
  
  $result['success'] = true;
  $result['slots_used'] = count($result['items']);
  $result['slots_max'] = $storage_slots;
  return $result;
}

// Deposit item into storage
function depositItem($db, $data)
{
  $player_name = clean($data['deposit_item']);
  $room_id = intval(clean($data['room_id']));
  $item_db_id = intval(clean($data['item_db_id']));
  
  $result = array(
    'success' => false,
    'message' => ''
  );
  
  // Rate limit: max 10 storage actions per 10 seconds
  $rlc = telemetryRateLimitCheck($db, $room_id, $player_name, ['deposit_item', 'withdraw_item'], 10, 10);
  if (!$rlc['ok']) {
    $result['message'] = 'Please wait before moving more items.';
    return $result;
  }
  
  // Get player
  $sp = $db->prepare("SELECT id, x, y, storage_slots FROM game_players WHERE room_id = ? AND name = ?");
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
  $storage_slots = intval($row['storage_slots']);
  $sp->close();
  
  // Check if player is at a town
  $sl = $db->prepare("SELECT rl.location_type FROM game_locations gl INNER JOIN resources_locations rl ON gl.resource_id = rl.id WHERE gl.room_id = ? AND gl.x = ? AND gl.y = ?");
  $sl->bind_param("iii", $room_id, $player_x, $player_y);
  
  if (!$sl->execute()) {
    $sl->close();
    $result['message'] = 'Cannot access storage here.';
    return $result;
  }
  
  $lr = $sl->get_result();
  if (mysqli_num_rows($lr) === 0) {
    $sl->close();
    $result['message'] = 'Cannot access storage here.';
    return $result;
  }
  
  $loc_row = mysqli_fetch_array($lr);
  $location_type = $loc_row['location_type'];
  $sl->close();
  
  if ($location_type !== 'town') {
    $result['message'] = 'You can only access storage at towns.';
    return $result;
  }
  
  // Check storage capacity
  $count_stmt = $db->prepare("SELECT COUNT(*) as cnt FROM player_storage WHERE player_id = ?");
  $count_stmt->bind_param("i", $player_id);
  $count_stmt->execute();
  $count_res = $count_stmt->get_result();
  $current_count = intval(mysqli_fetch_array($count_res)['cnt']);
  $count_stmt->close();
  
  if ($current_count >= $storage_slots) {
    $result['message'] = 'Storage is full! (' . $current_count . '/' . $storage_slots . ' slots)';
    return $result;
  }
  
  // Get item and verify ownership (must not be equipped)
  $gi = $db->prepare("SELECT gi.owner_id, gi.resource_id, gi.stats, gi.equipped, ri.name FROM game_items gi INNER JOIN resources_items ri ON gi.resource_id = ri.id WHERE gi.id = ?");
  $gi->bind_param("i", $item_db_id);
  
  if (!$gi->execute()) {
    $gi->close();
    $result['message'] = 'Item not found.';
    return $result;
  }
  
  $gr = $gi->get_result();
  if (mysqli_num_rows($gr) === 0) {
    $gi->close();
    $result['message'] = 'Item not found.';
    return $result;
  }
  
  $item_row = mysqli_fetch_array($gr);
  $gi->close();
  
  if (intval($item_row['owner_id']) !== $player_id) {
    $result['message'] = 'You do not own this item.';
    return $result;
  }
  
  if (intval($item_row['equipped']) === 1) {
    $result['message'] = 'Cannot store equipped items. Unequip first.';
    return $result;
  }
  
  $item_resource_id = intval($item_row['resource_id']);
  $item_stats = $item_row['stats'];
  $item_name = $item_row['name'];
  
  // Move item from inventory to storage
  $db->begin_transaction();
  
  try {
    // Remove from game_items
    $di = $db->prepare("DELETE FROM game_items WHERE id = ?");
    $di->bind_param("i", $item_db_id);
    $di->execute();
    $di->close();
    
    // Add to player_storage
    $ins = $db->prepare("INSERT INTO player_storage (player_id, item_resource_id, item_stats, quantity) VALUES (?, ?, ?, 1)");
    $ins->bind_param("iis", $player_id, $item_resource_id, $item_stats);
    $ins->execute();
    $ins->close();
    
    $db->commit();
  } catch (Throwable $e) {
    $db->rollback();
    $result['message'] = 'Failed to deposit item.';
    return $result;
  }
  
  touchPlayer($db, $room_id, $player_name);
  
  // Telemetry
  try {
    $det = json_encode(['item_name' => $item_name, 'item_db_id' => $item_db_id]);
    if ($det === false) { $det = '{}'; }
    if ($lg = $db->prepare("INSERT INTO game_logs (room_id, player_name, action, details) VALUES (?, ?, 'deposit_item', ?)")) {
      $lg->bind_param("iss", $room_id, $player_name, $det);
      $lg->execute();
      $lg->close();
    }
  } catch (Throwable $_) { }
  
  $result['success'] = true;
  $result['message'] = 'Deposited ' . $item_name . ' into storage.';
  $result['slots_used'] = $current_count + 1;
  $result['slots_max'] = $storage_slots;
  
  return $result;
}

// Withdraw item from storage
function withdrawItem($db, $data)
{
  $player_name = clean($data['withdraw_item']);
  $room_id = intval(clean($data['room_id']));
  $storage_id = intval(clean($data['storage_id']));
  
  $result = array(
    'success' => false,
    'message' => ''
  );
  
  // Rate limit: max 10 storage actions per 10 seconds
  $rlc = telemetryRateLimitCheck($db, $room_id, $player_name, ['deposit_item', 'withdraw_item'], 10, 10);
  if (!$rlc['ok']) {
    $result['message'] = 'Please wait before moving more items.';
    return $result;
  }
  
  // Get player
  $sp = $db->prepare("SELECT id, x, y FROM game_players WHERE room_id = ? AND name = ?");
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
  $sp->close();
  
  // Check if player is at a town
  $sl = $db->prepare("SELECT rl.location_type FROM game_locations gl INNER JOIN resources_locations rl ON gl.resource_id = rl.id WHERE gl.room_id = ? AND gl.x = ? AND gl.y = ?");
  $sl->bind_param("iii", $room_id, $player_x, $player_y);
  
  if (!$sl->execute()) {
    $sl->close();
    $result['message'] = 'Cannot access storage here.';
    return $result;
  }
  
  $lr = $sl->get_result();
  if (mysqli_num_rows($lr) === 0) {
    $sl->close();
    $result['message'] = 'Cannot access storage here.';
    return $result;
  }
  
  $loc_row = mysqli_fetch_array($lr);
  $location_type = $loc_row['location_type'];
  $sl->close();
  
  if ($location_type !== 'town') {
    $result['message'] = 'You can only access storage at towns.';
    return $result;
  }
  
  // Get stored item and verify ownership
  $gs = $db->prepare("SELECT ps.player_id, ps.item_resource_id, ps.item_stats, ri.name FROM player_storage ps INNER JOIN resources_items ri ON ps.item_resource_id = ri.id WHERE ps.id = ?");
  $gs->bind_param("i", $storage_id);
  
  if (!$gs->execute()) {
    $gs->close();
    $result['message'] = 'Item not found in storage.';
    return $result;
  }
  
  $sr = $gs->get_result();
  if (mysqli_num_rows($sr) === 0) {
    $gs->close();
    $result['message'] = 'Item not found in storage.';
    return $result;
  }
  
  $storage_row = mysqli_fetch_array($sr);
  $gs->close();
  
  if (intval($storage_row['player_id']) !== $player_id) {
    $result['message'] = 'You do not own this item.';
    return $result;
  }
  
  $item_resource_id = intval($storage_row['item_resource_id']);
  $item_stats = $storage_row['item_stats'];
  $item_name = $storage_row['name'];
  
  // Move item from storage to inventory
  $db->begin_transaction();
  
  try {
    // Remove from player_storage
    $ds = $db->prepare("DELETE FROM player_storage WHERE id = ?");
    $ds->bind_param("i", $storage_id);
    $ds->execute();
    $ds->close();
    
    // Add to game_items
    $ins = $db->prepare("INSERT INTO game_items (room_id, owner_id, resource_id, stats, equipped) VALUES (?, ?, ?, ?, 0)");
    $ins->bind_param("iiis", $room_id, $player_id, $item_resource_id, $item_stats);
    $ins->execute();
    $ins->close();
    
    $db->commit();
  } catch (Throwable $e) {
    $db->rollback();
    $result['message'] = 'Failed to withdraw item.';
    return $result;
  }
  
  touchPlayer($db, $room_id, $player_name);
  
  // Telemetry
  try {
    $det = json_encode(['item_name' => $item_name, 'storage_id' => $storage_id]);
    if ($det === false) { $det = '{}'; }
    if ($lg = $db->prepare("INSERT INTO game_logs (room_id, player_name, action, details) VALUES (?, ?, 'withdraw_item', ?)")) {
      $lg->bind_param("iss", $room_id, $player_name, $det);
      $lg->execute();
      $lg->close();
    }
  } catch (Throwable $_) { }
  
  $result['success'] = true;
  $result['message'] = 'Withdrew ' . $item_name . ' from storage.';
  
  return $result;
}

?>
