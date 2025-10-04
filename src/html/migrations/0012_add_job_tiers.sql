-- 0012_add_job_tiers.sql
-- Add job tier system with 2nd, 3rd, and 4th tier job advancements
-- Tier 1: Level 1, Tier 2: Level 10, Tier 3: Level 20, Tier 4: Level 30, Tier 5: Level 40
START TRANSACTION;

-- Add tier and required_base_job columns to resources_jobs (idempotent)
SET @tier_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'resources_jobs' 
                    AND COLUMN_NAME = 'tier');

SET @sql_tier = IF(@tier_exists = 0, 
                   'ALTER TABLE resources_jobs ADD COLUMN tier INT NOT NULL DEFAULT 1 COMMENT ''Job tier: 1=base, 2=advanced, 3=expert, 4=master, 5=legendary'' AFTER min_level',
                   'SELECT ''Column tier already exists'' AS message');

PREPARE stmt FROM @sql_tier;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @req_job_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                       WHERE TABLE_SCHEMA = DATABASE() 
                       AND TABLE_NAME = 'resources_jobs' 
                       AND COLUMN_NAME = 'required_base_job');

SET @sql_req = IF(@req_job_exists = 0, 
                  'ALTER TABLE resources_jobs ADD COLUMN required_base_job VARCHAR(32) DEFAULT NULL COMMENT ''Required base job for advancement (NULL for tier 1)'' AFTER tier',
                  'SELECT ''Column required_base_job already exists'' AS message');

PREPARE stmt FROM @sql_req;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update existing jobs to tier 1
UPDATE resources_jobs SET tier = 1, required_base_job = NULL WHERE tier = 1;

-- Insert Tier 2 jobs (Level 10)
INSERT IGNORE INTO resources_jobs (job_id, name, description, stat_modifiers, min_level, tier, required_base_job) VALUES
-- Warrior -> Knight or Berserker
('knight', 'Knight', 'Noble defender with exceptional DEF and HP. Protects allies with unwavering resolve.', '+DEF +HP +ATK', 10, 2, 'warrior'),
('berserker', 'Berserker', 'Raging warrior with devastating ATK. Sacrifices defense for overwhelming offense.', '+ATK +ATK +SPD', 10, 2, 'warrior'),

-- Rogue -> Assassin or Shadow Dancer
('assassin', 'Assassin', 'Master of lethal strikes with high CRT and ATK. Eliminates targets with precision.', '+CRT +ATK +SPD', 10, 2, 'rogue'),
('shadow_dancer', 'Shadow Dancer', 'Elusive fighter with supreme EVD and SPD. Dances through enemy attacks.', '+EVD +SPD +EVD', 10, 2, 'rogue'),

-- Mage -> Sorcerer or Elementalist
('sorcerer', 'Sorcerer', 'Master of arcane power with immense ATK. Commands devastating spells.', '+ATK +ATK +MP', 10, 2, 'mage'),
('elementalist', 'Elementalist', 'Wielder of elemental forces with balanced magic. Controls fire, ice, and lightning.', '+ATK +SPD +MP', 10, 2, 'mage'),

-- Cleric -> Paladin or Priest
('paladin', 'Paladin', 'Holy warrior with high DEF and ATK. Smites evil with divine power.', '+DEF +ATK +HP', 10, 2, 'cleric'),
('priest', 'Priest', 'Divine healer with high MP and DEF. Supports allies with holy magic.', '+MP +DEF +HP', 10, 2, 'cleric'),

-- Ranger -> Sniper or Beastmaster
('sniper', 'Sniper', 'Expert marksman with deadly precision. High ATK and CRT from range.', '+ATK +CRT +SPD', 10, 2, 'ranger'),
('beastmaster', 'Beastmaster', 'Master of beasts with balanced stats. Commands animal companions.', '+ATK +SPD +DEF', 10, 2, 'ranger'),

-- Hunter -> Tracker or Trapper
('tracker', 'Tracker', 'Expert tracker with enhanced SPD and EVD. Never loses prey.', '+SPD +EVD +ATK', 10, 2, 'hunter'),
('trapper', 'Trapper', 'Cunning trapper with high ATK and DEF. Sets deadly ambushes.', '+ATK +DEF +SPD', 10, 2, 'hunter');

-- Insert Tier 3 jobs (Level 20)
INSERT IGNORE INTO resources_jobs (job_id, name, description, stat_modifiers, min_level, tier, required_base_job) VALUES
-- Knight -> Crusader
('crusader', 'Crusader', 'Holy crusader with divine protection. Unbreakable defender of the faith.', '+DEF +HP +ATK +MP', 20, 3, 'warrior'),
-- Berserker -> Warlord
('warlord', 'Warlord', 'Legendary warrior with supreme combat prowess. Dominates the battlefield.', '+ATK +ATK +SPD +CRT', 20, 3, 'warrior'),

-- Assassin -> Ninja
('ninja', 'Ninja', 'Shadow master with lethal precision. Strikes from darkness with deadly force.', '+CRT +ATK +SPD +EVD', 20, 3, 'rogue'),
-- Shadow Dancer -> Phantom
('phantom', 'Phantom', 'Ethereal fighter who cannot be touched. Supreme evasion and speed.', '+EVD +SPD +EVD +CRT', 20, 3, 'rogue'),

-- Sorcerer -> Archmage
('archmage', 'Archmage', 'Master of all magic with overwhelming power. Commands reality itself.', '+ATK +ATK +MP +SPD', 20, 3, 'mage'),
-- Elementalist -> Storm Caller
('storm_caller', 'Storm Caller', 'Master of storms and elements. Unleashes nature\'s fury.', '+ATK +SPD +MP +CRT', 20, 3, 'mage'),

-- Paladin -> Templar
('templar', 'Templar', 'Elite holy warrior with divine might. Channels celestial power.', '+DEF +ATK +HP +MP', 20, 3, 'cleric'),
-- Priest -> High Priest
('high_priest', 'High Priest', 'Supreme divine caster with holy mastery. Channels divine miracles.', '+MP +DEF +HP +ATK', 20, 3, 'cleric'),

-- Sniper -> Deadeye
('deadeye', 'Deadeye', 'Perfect marksman who never misses. Every shot is lethal.', '+ATK +CRT +SPD +EVD', 20, 3, 'ranger'),
-- Beastmaster -> Beast Lord
('beast_lord', 'Beast Lord', 'Supreme commander of beasts. Leads powerful animal armies.', '+ATK +SPD +DEF +HP', 20, 3, 'ranger'),

-- Tracker -> Pathfinder
('pathfinder', 'Pathfinder', 'Master scout with unmatched mobility. Finds any path to victory.', '+SPD +EVD +ATK +CRT', 20, 3, 'hunter'),
-- Trapper -> Saboteur
('saboteur', 'Saboteur', 'Master of traps and ambush. Controls the battlefield with cunning.', '+ATK +DEF +SPD +CRT', 20, 3, 'hunter');

-- Insert Tier 4 jobs (Level 30)
INSERT IGNORE INTO resources_jobs (job_id, name, description, stat_modifiers, min_level, tier, required_base_job) VALUES
-- Crusader -> Divine Champion
('divine_champion', 'Divine Champion', 'Chosen of the gods with divine power. Unstoppable holy warrior.', '+DEF +HP +ATK +MP +CRT', 30, 4, 'warrior'),
-- Warlord -> Conqueror
('conqueror', 'Conqueror', 'Legendary conqueror who bends all to their will. Supreme combat master.', '+ATK +ATK +SPD +CRT +HP', 30, 4, 'warrior'),

-- Ninja -> Shadow Reaper
('shadow_reaper', 'Shadow Reaper', 'Death incarnate from the shadows. No one escapes their blade.', '+CRT +ATK +SPD +EVD +ATK', 30, 4, 'rogue'),
-- Phantom -> Void Walker
('void_walker', 'Void Walker', 'Walker between dimensions. Untouchable and unstoppable.', '+EVD +SPD +EVD +CRT +ATK', 30, 4, 'rogue'),

-- Archmage -> Spellweaver
('spellweaver', 'Spellweaver', 'Weaver of reality through magic. Bends the laws of nature.', '+ATK +ATK +MP +SPD +CRT', 30, 4, 'mage'),
-- Storm Caller -> Elemental Lord
('elemental_lord', 'Elemental Lord', 'Lord of all elements. Commands fire, ice, lightning, and earth.', '+ATK +SPD +MP +CRT +DEF', 30, 4, 'mage'),

-- Templar -> Exalted
('exalted', 'Exalted', 'Exalted warrior blessed by divinity. Channels pure holy power.', '+DEF +ATK +HP +MP +SPD', 30, 4, 'cleric'),
-- High Priest -> Oracle
('oracle', 'Oracle', 'Divine oracle who sees all futures. Commands fate itself.', '+MP +DEF +HP +ATK +CRT', 30, 4, 'cleric'),

-- Deadeye -> Sharpshooter
('sharpshooter', 'Sharpshooter', 'Legendary marksman of impossible skill. Every shot is perfect.', '+ATK +CRT +SPD +EVD +ATK', 30, 4, 'ranger'),
-- Beast Lord -> Primal King
('primal_king', 'Primal King', 'King of all beasts and nature. Commands primal forces.', '+ATK +SPD +DEF +HP +CRT', 30, 4, 'ranger'),

-- Pathfinder -> Wayfinder
('wayfinder', 'Wayfinder', 'Master of all paths and terrains. Unmatched mobility and awareness.', '+SPD +EVD +ATK +CRT +DEF', 30, 4, 'hunter'),
-- Saboteur -> Strategist
('strategist', 'Strategist', 'Master tactician who controls every battle. Perfect planning and execution.', '+ATK +DEF +SPD +CRT +HP', 30, 4, 'hunter');

-- Insert Tier 5 jobs (Level 40 - Legendary)
INSERT IGNORE INTO resources_jobs (job_id, name, description, stat_modifiers, min_level, tier, required_base_job) VALUES
-- Divine Champion -> Godslayer
('godslayer', 'Godslayer', 'Legendary warrior who slays even gods. Unmatched in all combat.', '+DEF +HP +ATK +MP +CRT +SPD', 40, 5, 'warrior'),
-- Conqueror -> Warbringer
('warbringer', 'Warbringer', 'Bringer of war and destruction. Unstoppable force of devastation.', '+ATK +ATK +SPD +CRT +HP +DEF', 40, 5, 'warrior'),

-- Shadow Reaper -> Nightblade
('nightblade', 'Nightblade', 'Blade of eternal night. Death itself fears this assassin.', '+CRT +ATK +SPD +EVD +ATK +CRT', 40, 5, 'rogue'),
-- Void Walker -> Dimensionalist
('dimensionalist', 'Dimensionalist', 'Master of dimensions and reality. Exists beyond mortal comprehension.', '+EVD +SPD +EVD +CRT +ATK +MP', 40, 5, 'rogue'),

-- Spellweaver -> Reality Bender
('reality_bender', 'Reality Bender', 'Bender of reality itself. Magic is their will made manifest.', '+ATK +ATK +MP +SPD +CRT +DEF', 40, 5, 'mage'),
-- Elemental Lord -> Primordial
('primordial', 'Primordial', 'Primordial force of nature. Commands the very essence of elements.', '+ATK +SPD +MP +CRT +DEF +HP', 40, 5, 'mage'),

-- Exalted -> Celestial
('celestial', 'Celestial', 'Celestial being of pure divinity. Channels the power of heaven.', '+DEF +ATK +HP +MP +SPD +CRT', 40, 5, 'cleric'),
-- Oracle -> Divinity
('divinity', 'Divinity', 'Ascended to near-godhood. Wields divine power without limit.', '+MP +DEF +HP +ATK +CRT +EVD', 40, 5, 'cleric'),

-- Sharpshooter -> Apex Predator
('apex_predator', 'Apex Predator', 'Apex predator of all realms. No prey escapes their sight.', '+ATK +CRT +SPD +EVD +ATK +DEF', 40, 5, 'ranger'),
-- Primal King -> Nature\'s Wrath
('natures_wrath', 'Nature\'s Wrath', 'Embodiment of nature\'s fury. Commands all living things.', '+ATK +SPD +DEF +HP +CRT +MP', 40, 5, 'ranger'),

-- Wayfinder -> Worldwalker
('worldwalker', 'Worldwalker', 'Walker of all worlds and paths. Transcends physical limits.', '+SPD +EVD +ATK +CRT +DEF +MP', 40, 5, 'hunter'),
-- Strategist -> Grandmaster
('grandmaster', 'Grandmaster', 'Grandmaster of all tactics and combat. Perfect in every way.', '+ATK +DEF +SPD +CRT +HP +EVD', 40, 5, 'hunter');

COMMIT;
