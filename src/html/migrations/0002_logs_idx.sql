-- 0002_logs_idx.sql
-- Add composite index to speed up admin_logs queries
-- Idempotent: checks information_schema before creating

-- Only proceed if the game_logs table exists
SET @tbl_exists := (
  SELECT COUNT(1)
  FROM information_schema.tables
  WHERE table_schema = DATABASE()
    AND table_name = 'game_logs'
);

-- If table exists, check whether the index already exists
SET @idx_missing := (
  SELECT CASE WHEN COUNT(1) = 0 THEN 1 ELSE 0 END
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'game_logs'
    AND index_name = 'idx_logs_action_room_ts'
);

-- Create index only when table exists and index is missing; otherwise no-op
SET @sql := IF(@tbl_exists = 1 AND @idx_missing = 1,
  'CREATE INDEX idx_logs_action_room_ts ON game_logs (action, room_id, ts);',
  'DO 1');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
