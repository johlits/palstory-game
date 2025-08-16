-- 0004_add_telemetry.sql
-- Create simple telemetry table for action logs (idempotent)

START TRANSACTION;

-- Create table if missing
SET @tbl_missing := (
  SELECT CASE WHEN COUNT(*) = 0 THEN 1 ELSE 0 END
  FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'game_logs'
);
SET @sql := IF(@tbl_missing = 1,
  'CREATE TABLE game_logs (
     id BIGINT NOT NULL AUTO_INCREMENT,
     ts TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
     room_id INT NULL,
     player_name VARCHAR(256) NULL,
     action VARCHAR(64) NOT NULL,
     details TEXT NULL,
     PRIMARY KEY (id),
     INDEX idx_logs_ts (ts),
     INDEX idx_logs_action (action),
     INDEX idx_logs_room (room_id)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

COMMIT;
