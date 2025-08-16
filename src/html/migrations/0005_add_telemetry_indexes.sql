-- 0005_add_telemetry_indexes.sql
-- Add helpful indexes for telemetry queries (idempotent)

START TRANSACTION;

-- Add idx_logs_player on player_name if missing
SET @idx_missing := (
  SELECT CASE WHEN COUNT(*) = 0 THEN 1 ELSE 0 END
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'game_logs' AND INDEX_NAME = 'idx_logs_player'
);
SET @sql := IF(@idx_missing = 1,
  'CREATE INDEX idx_logs_player ON game_logs (player_name)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add idx_logs_action_ts on (action, ts) if missing
SET @idx2_missing := (
  SELECT CASE WHEN COUNT(*) = 0 THEN 1 ELSE 0 END
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'game_logs' AND INDEX_NAME = 'idx_logs_action_ts'
);
SET @sql2 := IF(@idx2_missing = 1,
  'CREATE INDEX idx_logs_action_ts ON game_logs (action, ts)',
  'SELECT 1'
);
PREPARE stmt2 FROM @sql2; EXECUTE stmt2; DEALLOCATE PREPARE stmt2;

COMMIT;
