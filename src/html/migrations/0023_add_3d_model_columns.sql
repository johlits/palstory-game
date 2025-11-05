-- 0023_add_3d_model_columns.sql
-- Add 3D model columns to resource tables for GLB file support
START TRANSACTION;

-- Add 3D model column to resources_locations table
SET @column_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'resources_locations'
      AND COLUMN_NAME = 'model_3d'
);

SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE resources_locations ADD COLUMN model_3d VARCHAR(64) NULL AFTER stats',
    'SELECT ''Column model_3d already exists in resources_locations'' as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add 3D model column to resources_monsters table
SET @column_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'resources_monsters'
      AND COLUMN_NAME = 'model_3d'
);

SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE resources_monsters ADD COLUMN model_3d VARCHAR(64) NULL AFTER stats',
    'SELECT ''Column model_3d already exists in resources_monsters'' as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add 3D model column to resources_items table
SET @column_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'resources_items'
      AND COLUMN_NAME = 'model_3d'
);

SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE resources_items ADD COLUMN model_3d VARCHAR(64) NULL AFTER stats',
    'SELECT ''Column model_3d already exists in resources_items'' as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

COMMIT;
