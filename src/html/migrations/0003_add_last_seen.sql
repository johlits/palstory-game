-- Migration 0003: add last_seen to game_players for automatic session/heartbeat persistence
-- Idempotent: uses information_schema checks; works without IF NOT EXISTS support

START TRANSACTION;

-- Add column last_seen if missing
SET @col_missing := (
  SELECT CASE WHEN COUNT(*) = 0 THEN 1 ELSE 0 END
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'game_players' AND COLUMN_NAME = 'last_seen'
);
SET @sql := IF(@col_missing = 1,
  'ALTER TABLE game_players ADD COLUMN last_seen TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER resource_id',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add index on last_seen if missing
SET @idx_missing := (
  SELECT CASE WHEN COUNT(*) = 0 THEN 1 ELSE 0 END
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'game_players' AND INDEX_NAME = 'idx_game_players_last_seen'
);
SET @sql := IF(@idx_missing = 1,
  'CREATE INDEX idx_game_players_last_seen ON game_players (last_seen)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

COMMIT;
