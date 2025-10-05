-- Migration 0017: Add rest spots and town system
-- Add location_type column to distinguish between combat zones, towns, and rest spots
-- Add rest action endpoint for HP/MP restoration

ALTER TABLE resources_locations 
ADD COLUMN location_type ENUM('wilderness', 'town', 'rest_spot') NOT NULL DEFAULT 'wilderness' COMMENT 'Location type: wilderness (combat/gather), town (safe zone with services), rest_spot (HP/MP restoration)';

-- Add some town and rest spot locations
INSERT INTO resources_locations (id, name, image, description, lvl_from, lvl_to, stats, location_type, banned) VALUES
(100, 'Starter Village', 'startervillage.jpg', 'A peaceful village where adventurers begin their journey. The village offers a safe haven with an inn for rest, a blacksmith for equipment, and friendly villagers eager to help newcomers.', 1, 100, 'safe=1;', 'town', 0),
(101, 'Woodland Camp', 'woodlandcamp.jpg', 'A small campsite nestled in the forest. Travelers can rest here to restore their health and mana before continuing their journey.', 1, 10, 'rest=1;', 'rest_spot', 0),
(102, 'Mountain Refuge', 'mountainrefuge.jpg', 'A sturdy shelter built into the mountainside. Weary adventurers can find respite from the harsh elements and recover their strength.', 5, 15, 'rest=1;', 'rest_spot', 0),
(103, 'Desert Oasis', 'desertoasis.jpg', 'A lush oasis in the middle of the desert. The cool water and shade provide perfect conditions for rest and recovery.', 10, 20, 'rest=1;', 'rest_spot', 0),
(104, 'Riverside Town', 'riversidetown.jpg', 'A bustling town built along the riverbank. Merchants, craftsmen, and adventurers gather here to trade goods and share stories. The town offers complete safety and various services.', 5, 100, 'safe=1;', 'town', 0),
(105, 'Highland Sanctuary', 'highlandsanctuary.jpg', 'A sacred sanctuary high in the mountains. Pilgrims and adventurers alike seek this place for meditation and healing.', 15, 30, 'rest=1;', 'rest_spot', 0);

-- Add player_spawn_location_id column to game_rooms to track where players respawn after death
ALTER TABLE game_rooms
ADD COLUMN spawn_location_id INT DEFAULT NULL COMMENT 'Resource location ID where players spawn/respawn in this room';

-- Set default spawn location for existing rooms (Starter Village)
UPDATE game_rooms SET spawn_location_id = 100 WHERE spawn_location_id IS NULL;
