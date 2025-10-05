-- Migration 0016: Add passive skills and buffs system
-- Add skill_type column to distinguish active vs passive skills
-- Add stat_modifiers column for passive skill bonuses

ALTER TABLE resources_skills 
ADD COLUMN skill_type ENUM('active', 'passive') NOT NULL DEFAULT 'active' COMMENT 'Skill type: active (used in combat) or passive (always on)',
ADD COLUMN stat_modifiers VARCHAR(256) DEFAULT NULL COMMENT 'Stat bonuses for passive skills (e.g., atk=+5;def=+3;maxhp=+10)';

-- Add some passive skills for each job
-- Warrior passives
INSERT INTO resources_skills (skill_id, name, description, mp_cost, cooldown_sec, damage_multiplier, unlock_cost, required_job, required_skills, skill_type, stat_modifiers, banned) VALUES
('warrior_vitality', 'Warrior Vitality', 'Passive: +20 max HP, +2 DEF', 0, 0, 1.00, 1, 'warrior', NULL, 'passive', 'maxhp=+20;def=+2', 0),
('warrior_strength', 'Warrior Strength', 'Passive: +3 ATK, +1 CRT', 0, 0, 1.00, 1, 'warrior', NULL, 'passive', 'atk=+3;crt=+1', 0),
('warrior_endurance', 'Warrior Endurance', 'Passive: +30 max HP, +5 DEF', 0, 0, 1.00, 2, 'warrior', 'warrior_vitality', 'passive', 'maxhp=+30;def=+5', 0),
('warrior_power', 'Warrior Power', 'Passive: +5 ATK, +2 CRT', 0, 0, 1.00, 2, 'warrior', 'warrior_strength', 'passive', 'atk=+5;crt=+2', 0);

-- Rogue passives
INSERT INTO resources_skills (skill_id, name, description, mp_cost, cooldown_sec, damage_multiplier, unlock_cost, required_job, required_skills, skill_type, stat_modifiers, banned) VALUES
('rogue_agility', 'Rogue Agility', 'Passive: +3 SPD, +2 EVD', 0, 0, 1.00, 1, 'rogue', NULL, 'passive', 'spd=+3;evd=+2', 0),
('rogue_precision', 'Rogue Precision', 'Passive: +2 ATK, +3 CRT', 0, 0, 1.00, 1, 'rogue', NULL, 'passive', 'atk=+2;crt=+3', 0),
('rogue_reflexes', 'Rogue Reflexes', 'Passive: +5 SPD, +5 EVD', 0, 0, 1.00, 2, 'rogue', 'rogue_agility', 'passive', 'spd=+5;evd=+5', 0),
('rogue_deadly_aim', 'Rogue Deadly Aim', 'Passive: +4 ATK, +5 CRT', 0, 0, 1.00, 2, 'rogue', 'rogue_precision', 'passive', 'atk=+4;crt=+5', 0);

-- Mage passives
INSERT INTO resources_skills (skill_id, name, description, mp_cost, cooldown_sec, damage_multiplier, unlock_cost, required_job, required_skills, skill_type, stat_modifiers, banned) VALUES
('mage_intellect', 'Mage Intellect', 'Passive: +15 max MP, +2 ATK', 0, 0, 1.00, 1, 'mage', NULL, 'passive', 'maxmp=+15;atk=+2', 0),
('mage_focus', 'Mage Focus', 'Passive: +10 max HP, +2 CRT', 0, 0, 1.00, 1, 'mage', NULL, 'passive', 'maxhp=+10;crt=+2', 0),
('mage_arcane_mastery', 'Mage Arcane Mastery', 'Passive: +25 max MP, +4 ATK', 0, 0, 1.00, 2, 'mage', 'mage_intellect', 'passive', 'maxmp=+25;atk=+4', 0),
('mage_mental_fortress', 'Mage Mental Fortress', 'Passive: +20 max HP, +4 CRT', 0, 0, 1.00, 2, 'mage', 'mage_focus', 'passive', 'maxhp=+20;crt=+4', 0);

-- Cleric passives
INSERT INTO resources_skills (skill_id, name, description, mp_cost, cooldown_sec, damage_multiplier, unlock_cost, required_job, required_skills, skill_type, stat_modifiers, banned) VALUES
('cleric_faith', 'Cleric Faith', 'Passive: +15 max HP, +10 max MP', 0, 0, 1.00, 1, 'cleric', NULL, 'passive', 'maxhp=+15;maxmp=+10', 0),
('cleric_devotion', 'Cleric Devotion', 'Passive: +2 ATK, +2 DEF', 0, 0, 1.00, 1, 'cleric', NULL, 'passive', 'atk=+2;def=+2', 0),
('cleric_divine_blessing', 'Cleric Divine Blessing', 'Passive: +25 max HP, +20 max MP', 0, 0, 1.00, 2, 'cleric', 'cleric_faith', 'passive', 'maxhp=+25;maxmp=+20', 0),
('cleric_holy_resilience', 'Cleric Holy Resilience', 'Passive: +4 ATK, +4 DEF', 0, 0, 1.00, 2, 'cleric', 'cleric_devotion', 'passive', 'atk=+4;def=+4', 0);

-- Ranger passives
INSERT INTO resources_skills (skill_id, name, description, mp_cost, cooldown_sec, damage_multiplier, unlock_cost, required_job, required_skills, skill_type, stat_modifiers, banned) VALUES
('ranger_marksmanship', 'Ranger Marksmanship', 'Passive: +3 ATK, +2 CRT', 0, 0, 1.00, 1, 'ranger', NULL, 'passive', 'atk=+3;crt=+2', 0),
('ranger_awareness', 'Ranger Awareness', 'Passive: +2 SPD, +3 EVD', 0, 0, 1.00, 1, 'ranger', NULL, 'passive', 'spd=+2;evd=+3', 0),
('ranger_sharpshooter', 'Ranger Sharpshooter', 'Passive: +5 ATK, +4 CRT', 0, 0, 1.00, 2, 'ranger', 'ranger_marksmanship', 'passive', 'atk=+5;crt=+4', 0),
('ranger_evasion', 'Ranger Evasion', 'Passive: +4 SPD, +5 EVD', 0, 0, 1.00, 2, 'ranger', 'ranger_awareness', 'passive', 'spd=+4;evd=+5', 0);

-- Hunter passives
INSERT INTO resources_skills (skill_id, name, description, mp_cost, cooldown_sec, damage_multiplier, unlock_cost, required_job, required_skills, skill_type, stat_modifiers, banned) VALUES
('hunter_survival', 'Hunter Survival', 'Passive: +15 max HP, +2 DEF', 0, 0, 1.00, 1, 'hunter', NULL, 'passive', 'maxhp=+15;def=+2', 0),
('hunter_tracking', 'Hunter Tracking', 'Passive: +2 ATK, +2 SPD', 0, 0, 1.00, 1, 'hunter', NULL, 'passive', 'atk=+2;spd=+2', 0),
('hunter_wilderness_mastery', 'Hunter Wilderness Mastery', 'Passive: +25 max HP, +4 DEF', 0, 0, 1.00, 2, 'hunter', 'hunter_survival', 'passive', 'maxhp=+25;def=+4', 0),
('hunter_predator_instinct', 'Hunter Predator Instinct', 'Passive: +4 ATK, +4 SPD', 0, 0, 1.00, 2, 'hunter', 'hunter_tracking', 'passive', 'atk=+4;spd=+4', 0);

-- Universal passive (available to all)
INSERT INTO resources_skills (skill_id, name, description, mp_cost, cooldown_sec, damage_multiplier, unlock_cost, required_job, required_skills, skill_type, stat_modifiers, banned) VALUES
('toughness', 'Toughness', 'Passive: +10 max HP, +1 DEF', 0, 0, 1.00, 1, 'all', NULL, 'passive', 'maxhp=+10;def=+1', 0),
('combat_training', 'Combat Training', 'Passive: +2 ATK, +1 CRT', 0, 0, 1.00, 1, 'all', NULL, 'passive', 'atk=+2;crt=+1', 0);
