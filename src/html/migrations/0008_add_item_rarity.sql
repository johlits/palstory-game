-- 0008_add_item_rarity.sql
-- Add rarity field to resources_items table
-- Rarity tiers: common (0), uncommon (1), rare (2), epic (3), legendary (4)
START TRANSACTION;

-- Add rarity column with default value of 0 (common)
-- Check if column exists first, skip if already exists
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME = 'resources_items' 
                   AND COLUMN_NAME = 'rarity');

SET @sql = IF(@col_exists = 0, 
              'ALTER TABLE resources_items ADD COLUMN rarity TINYINT(1) NOT NULL DEFAULT 0 COMMENT ''Item rarity: 0=common, 1=uncommon, 2=rare, 3=epic, 4=legendary''',
              'SELECT ''Column rarity already exists'' AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update existing items with appropriate rarities
UPDATE resources_items SET rarity = 0 WHERE name = 'Wooden Sword';
UPDATE resources_items SET rarity = 0 WHERE name = 'Wooden Buckler';

-- Add some new items with varied rarities for testing
INSERT IGNORE INTO resources_items (name, image, description, stats, banned, rarity)
VALUES
('Iron Sword', 'ironsword.jpg', 'A sturdy iron blade', 'type=weapon;atk=5;def=0;spd=0;', 0, 1),
('Steel Sword', 'steelsword.jpg', 'A well-crafted steel weapon', 'type=weapon;atk=8;def=0;spd=1;', 0, 2),
('Dragonbane', 'dragonbane.jpg', 'A legendary sword forged to slay dragons', 'type=weapon;atk=15;def=2;spd=3;', 0, 4),
('Leather Armor', 'leatherarmor.jpg', 'Basic leather protection', 'type=armor;atk=0;def=3;spd=0;', 0, 0),
('Chainmail', 'chainmail.jpg', 'Interlocking metal rings', 'type=armor;atk=0;def=6;spd=-1;', 0, 1),
('Plate Armor', 'platearmor.jpg', 'Heavy steel plates', 'type=armor;atk=0;def=10;spd=-2;', 0, 2),
('Enchanted Robes', 'enchantedrobes.jpg', 'Mystical robes imbued with magic', 'type=armor;atk=1;def=5;spd=2;', 0, 3),
('Health Potion', 'healthpotion.jpg', 'Restores 50 HP', 'type=consumable;heal=50;', 0, 0),
('Greater Health Potion', 'greaterhealthpotion.jpg', 'Restores 150 HP', 'type=consumable;heal=150;', 0, 2);

COMMIT;
