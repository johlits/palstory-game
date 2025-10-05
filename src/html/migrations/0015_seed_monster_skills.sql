-- 0015_seed_monster_skills.sql
-- Assign skills to monsters and set their MP pools
-- Safe to run multiple times (idempotent with INSERT IGNORE and conditional UPDATE)
START TRANSACTION;

-- Update monster MP pools (only if currently 0)
UPDATE resources_monsters SET mp = 30, maxmp = 30 WHERE id IN (5,6) AND maxmp = 0; -- Goblingrunt, Rat Swarm (low level)
UPDATE resources_monsters SET mp = 40, maxmp = 40 WHERE id IN (7,8) AND maxmp = 0; -- Skeleton Warrior, Zombie Rotter
UPDATE resources_monsters SET mp = 50, maxmp = 50 WHERE id IN (9,10) AND maxmp = 0; -- Giant Spiderling, Gnoll Marauder
UPDATE resources_monsters SET mp = 60, maxmp = 60 WHERE id IN (11,12,13,14) AND maxmp = 0; -- Ogre Brute, Harpy Screecher, Goblin Shaman, Dire Wolf
UPDATE resources_monsters SET mp = 80, maxmp = 80 WHERE id IN (15,16,17,18) AND maxmp = 0; -- Flesh Golem, Wraith Stalker, Specter Haunter, Shadow Assassin
UPDATE resources_monsters SET mp = 100, maxmp = 100 WHERE id >= 19 AND id <= 30 AND maxmp = 0; -- Mid-tier monsters
UPDATE resources_monsters SET mp = 150, maxmp = 150 WHERE id >= 31 AND id <= 50 AND maxmp = 0; -- High-tier monsters
UPDATE resources_monsters SET mp = 200, maxmp = 200 WHERE id >= 51 AND maxmp = 0; -- Boss-tier monsters

-- Assign basic attack skills to low-level monsters
INSERT IGNORE INTO monster_skills (monster_resource_id, skill_id) VALUES
-- Goblingrunt (5): basic melee
(5, 'power_strike'),
-- Rat Swarm (6): quick attacks
(6, 'quick_stab'),
-- Skeleton Warrior (7): warrior skills
(7, 'heavy_swing'),
(7, 'shield_bash'),
-- Zombie Rotter (8): slow but strong
(8, 'heavy_swing'),
-- Giant Spiderling (9): poison
(9, 'poison_blade'),
(9, 'quick_stab'),
-- Gnoll Marauder (10): hunter skills
(10, 'tracking_shot'),
(10, 'wild_strike');

-- Assign skills to mid-level monsters
INSERT IGNORE INTO monster_skills (monster_resource_id, skill_id) VALUES
-- Ogre Brute (11): heavy hitter
(11, 'crushing_blow'),
(11, 'cleave'),
-- Harpy Screecher (12): fast attacks
(12, 'quick_shot'),
(12, 'multi_shot'),
-- Goblin Shaman (13): magic user
(13, 'fireball'),
(13, 'arcane_bolt'),
(13, 'lightning_bolt'),
-- Dire Wolf (14): hunter
(14, 'beast_strike'),
(14, 'wild_strike'),
-- Flesh Golem (15): tank
(15, 'shield_wall'),
(15, 'heavy_swing'),
-- Wraith Stalker (16): shadow
(16, 'shadow_strike'),
(16, 'backstab'),
-- Specter Haunter (17): magic
(17, 'ice_shard'),
(17, 'frost_nova'),
-- Shadow Assassin (18): rogue
(18, 'backstab'),
(18, 'assassinate'),
(18, 'shadow_strike');

-- Assign skills to high-level monsters
INSERT IGNORE INTO monster_skills (monster_resource_id, skill_id) VALUES
-- Imp Trickster (19): magic
(19, 'fireball'),
(19, 'arcane_missiles'),
-- Hellhound (20): fire
(20, 'fireball'),
(20, 'beast_strike'),
-- Manticore (21): powerful
(21, 'piercing_arrow'),
(21, 'explosive_arrow'),
-- Minotaur Mauler (22): warrior
(22, 'crushing_blow'),
(22, 'berserker_rage'),
-- Medusa (23): magic
(23, 'ice_shard'),
(23, 'frost_nova'),
-- Naga Serpent (24): magic
(24, 'lightning_bolt'),
(24, 'arcane_missiles'),
-- Wyvern Drake (25): dragon
(25, 'fireball'),
(25, 'meteor'),
-- Banshee Wailer (26): magic
(26, 'frost_nova'),
(26, 'blizzard'),
-- Gargoyle Sentry (27): tank
(27, 'shield_wall'),
(27, 'iron_will'),
-- Succubus Temptress (28): magic
(28, 'arcane_bolt'),
(28, 'arcane_missiles'),
-- Chimaera (29): multi-attack
(29, 'cleave'),
(29, 'meteor'),
-- Cyclops (30): heavy
(30, 'crushing_blow'),
(30, 'berserker_rage');

-- Assign skills to boss-tier monsters
INSERT IGNORE INTO monster_skills (monster_resource_id, skill_id) VALUES
-- Hydra (31): multi-head
(31, 'cleave'),
(31, 'poison_blade'),
-- Lamia Enchantress (32): magic
(32, 'arcane_missiles'),
(32, 'lightning_bolt'),
-- Mummy (33): undead
(33, 'smite'),
(33, 'divine_wrath'),
-- Roc (34): aerial
(34, 'snipe'),
(34, 'barrage'),
-- Sphinx (35): magic
(35, 'arcane_missiles'),
(35, 'meteor'),
-- Troll Berserker (36): warrior
(36, 'berserker_rage'),
(36, 'crushing_blow'),
-- Wendigo (37): hunter
(37, 'feral_rage'),
(37, 'apex_hunt'),
-- Yeti (38): ice
(38, 'blizzard'),
(38, 'frost_nova'),
-- Harpy Queen (39): aerial
(39, 'barrage'),
(39, 'explosive_arrow'),
-- Minotaur Chieftain (40): warrior
(40, 'berserker_rage'),
(40, 'iron_will');

COMMIT;
