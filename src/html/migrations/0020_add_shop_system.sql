-- Migration 0020: Add shop and vendor system
-- Create shop inventory table for items available for purchase

CREATE TABLE IF NOT EXISTS shop_inventory (
  id INT AUTO_INCREMENT PRIMARY KEY,
  item_id VARCHAR(64) NOT NULL COMMENT 'References resources_items.name',
  price INT NOT NULL COMMENT 'Purchase price in gold',
  stock_unlimited TINYINT(1) DEFAULT 1 COMMENT '1 = unlimited stock, 0 = limited',
  available_at_level INT DEFAULT 1 COMMENT 'Minimum player level to see this item',
  category VARCHAR(32) DEFAULT 'general' COMMENT 'Shop category: weapon, armor, consumable, general',
  INDEX idx_item_id (item_id),
  INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed basic shop inventory
-- Weapons (using items that exist in resources_items)
INSERT INTO shop_inventory (item_id, price, stock_unlimited, available_at_level, category) VALUES
('Rusty Blade', 25, 1, 1, 'weapon'),
('Wooden Sword', 50, 1, 1, 'weapon'),
('Iron Shortsword', 150, 1, 3, 'weapon'),
('Copper Longsword', 300, 1, 5, 'weapon'),
('Steel Broadsword', 500, 1, 7, 'weapon'),
('Bronze Cutlass', 800, 1, 10, 'weapon');

-- Armor and Shields (using items that exist in resources_items)
INSERT INTO shop_inventory (item_id, price, stock_unlimited, available_at_level, category) VALUES
('Leather Armor', 100, 1, 1, 'armor'),
('Chainmail', 250, 1, 5, 'armor'),
('Plate Armor', 500, 1, 10, 'armor'),
('Wooden Buckler', 40, 1, 1, 'shield'),
('Tattered Hide Shield', 80, 1, 2, 'shield'),
('Iron Shield', 200, 1, 5, 'shield');

-- Consumables (if they exist in resources_items)
-- Note: Add consumables here when the consumable system is implemented

-- Sell price is calculated as 50% of purchase price in the server code
-- Players can sell any item they own for half its shop value (or base value if not in shop)
