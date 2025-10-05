-- 0013_add_monster_skills.sql
-- Add monster skills system: monster_skills table and required_skills column
-- Safe to run multiple times (idempotent)
START TRANSACTION;

-- Create monster_skills table to link monsters to skills
CREATE TABLE IF NOT EXISTS monster_skills (
  id INT NOT NULL AUTO_INCREMENT,
  monster_resource_id INT NOT NULL COMMENT 'FK to resources_monsters.id',
  skill_id VARCHAR(32) NOT NULL COMMENT 'FK to resources_skills.skill_id',
  PRIMARY KEY (id),
  UNIQUE KEY uniq_monster_skill (monster_resource_id, skill_id),
  INDEX idx_monster_resource_id (monster_resource_id),
  INDEX idx_skill_id (skill_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add required_skills column to resources_skills (idempotent)
SET @req_skills_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                          WHERE TABLE_SCHEMA = DATABASE() 
                          AND TABLE_NAME = 'resources_skills' 
                          AND COLUMN_NAME = 'required_skills');

SET @sql_req_skills = IF(@req_skills_exists = 0, 
                         'ALTER TABLE resources_skills ADD COLUMN required_skills VARCHAR(256) DEFAULT NULL COMMENT ''Comma-separated list of skill_ids required to unlock this skill'' AFTER required_job',
                         'SELECT ''Column required_skills already exists'' AS message');

PREPARE stmt FROM @sql_req_skills;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add MP column to resources_monsters (idempotent) so monsters can use skills
SET @monster_mp_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                          WHERE TABLE_SCHEMA = DATABASE() 
                          AND TABLE_NAME = 'resources_monsters' 
                          AND COLUMN_NAME = 'mp');

SET @sql_monster_mp = IF(@monster_mp_exists = 0, 
                         'ALTER TABLE resources_monsters ADD COLUMN mp INT NOT NULL DEFAULT 0 COMMENT ''Monster MP for skill usage'' AFTER stats',
                         'SELECT ''Column mp already exists'' AS message');

PREPARE stmt FROM @sql_monster_mp;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @monster_maxmp_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                             WHERE TABLE_SCHEMA = DATABASE() 
                             AND TABLE_NAME = 'resources_monsters' 
                             AND COLUMN_NAME = 'maxmp');

SET @sql_monster_maxmp = IF(@monster_maxmp_exists = 0, 
                            'ALTER TABLE resources_monsters ADD COLUMN maxmp INT NOT NULL DEFAULT 0 COMMENT ''Monster max MP'' AFTER mp',
                            'SELECT ''Column maxmp already exists'' AS message');

PREPARE stmt FROM @sql_monster_maxmp;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

COMMIT;
