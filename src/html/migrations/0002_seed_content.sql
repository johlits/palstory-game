-- 0002_seed_content.sql
START TRANSACTION;

-- Seed a starter room
INSERT INTO game_rooms (name, expiration, regen)
VALUES ('Starter Plains', DATE_ADD(NOW(), INTERVAL 30 DAY), 5);

-- Seed a starter player (demo)
INSERT INTO game_players (name, room_id, x, y, stats, resource_id)
SELECT 'Hero', r.id, 0, 0, 'hp=10;atk=2;def=1;spd=1;', -1
FROM game_rooms r
WHERE r.name = 'Starter Plains'
LIMIT 1;

-- Ensure a couple of item resources exist
INSERT IGNORE INTO resources_items (name, image, description, stats, banned)
VALUES
('Wooden Sword', 'woodensword.jpg', 'Simple starter weapon', 'type=weapon;atk=2;def=0;spd=1;', 0),
('Wooden Buckler', 'woodenbuckler.jpg', 'Simple starter shield', 'type=shield;atk=0;def=2;spd=0;', 0);

COMMIT;
