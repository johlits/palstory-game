-- 0022_add_storage_system.sql
-- Add storage system for players to store items at towns
-- Storage is shared across all towns (like a bank)
START TRANSACTION;

-- Create player_storage table for stored items
-- Similar structure to game_items but for storage
CREATE TABLE IF NOT EXISTS player_storage (
  id INT NOT NULL AUTO_INCREMENT,
  player_id INT NOT NULL COMMENT 'Owner player ID',
  item_resource_id INT NOT NULL COMMENT 'References resources_items.id',
  item_stats TEXT NOT NULL COMMENT 'Item stats JSON (same format as game_items.stats)',
  quantity INT NOT NULL DEFAULT 1 COMMENT 'Stack count for stackable items',
  stored_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_storage_player (player_id),
  INDEX idx_storage_resource (item_resource_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add storage_slots column to game_players for max storage capacity
-- Default 20 slots, can be upgraded later
SET @storage_slots_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                              WHERE TABLE_SCHEMA = DATABASE() 
                              AND TABLE_NAME = 'game_players' 
                              AND COLUMN_NAME = 'storage_slots');

SET @sql_storage = IF(@storage_slots_exists = 0, 
                      'ALTER TABLE game_players ADD COLUMN storage_slots INT NOT NULL DEFAULT 20 COMMENT ''Max storage capacity''',
                      'SELECT ''Column storage_slots already exists'' AS message');

PREPARE stmt FROM @sql_storage;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

COMMIT;
