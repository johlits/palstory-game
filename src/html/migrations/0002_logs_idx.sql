-- 0002_logs_idx.sql
-- Add composite index to speed up admin_logs queries
-- Idempotent: checks information_schema before creating

SET @idx_exists := (
  SELECT COUNT(1)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'game_logs'
    AND index_name = 'idx_logs_action_room_ts'
);

SET @sql := IF(@idx_exists = 0,
  'CREATE INDEX idx_logs_action_room_ts ON game_logs (action, room_id, ts);',
  'SELECT 1');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
