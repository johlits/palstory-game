-- 0006_add_roomid_ts_index.sql
-- Add composite index to accelerate queries by room and time (idempotent)

START TRANSACTION;

-- Add idx_logs_room_ts on (room_id, ts) if missing
SET @idx_missing := (
  SELECT CASE WHEN COUNT(*) = 0 THEN 1 ELSE 0 END
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'game_logs' AND INDEX_NAME = 'idx_logs_room_ts'
);
SET @sql := IF(@idx_missing = 1,
  'CREATE INDEX idx_logs_room_ts ON game_logs (room_id, ts)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

COMMIT;
