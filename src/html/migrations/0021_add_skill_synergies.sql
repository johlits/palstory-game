-- Migration 0021: Add skill synergies and combos system
-- Allows skills to have bonus effects when used after specific other skills

-- Add synergy columns to resources_skills table
ALTER TABLE resources_skills 
ADD COLUMN synergy_with VARCHAR(32) DEFAULT NULL COMMENT 'Skill ID that triggers synergy when used before this skill',
ADD COLUMN synergy_bonus VARCHAR(256) DEFAULT NULL COMMENT 'Bonus effect when synergy triggers (e.g., damage=+50%;cooldown=-2;status=stun)',
ADD COLUMN synergy_window_sec INT DEFAULT 5 COMMENT 'Time window in seconds to trigger synergy after prerequisite skill';

-- Add index for synergy lookups
ALTER TABLE resources_skills ADD INDEX idx_synergy_with (synergy_with);

-- Define some basic synergies for existing skills
-- Warrior synergies
UPDATE resources_skills SET 
  synergy_with = 'power_strike',
  synergy_bonus = 'damage=+50%',
  synergy_window_sec = 5
WHERE skill_id = 'cleave';

UPDATE resources_skills SET 
  synergy_with = 'shield_bash',
  synergy_bonus = 'damage=+30%;status=stun',
  synergy_window_sec = 5
WHERE skill_id = 'power_strike';

-- Rogue synergies
UPDATE resources_skills SET 
  synergy_with = 'backstab',
  synergy_bonus = 'damage=+60%;crit=+20%',
  synergy_window_sec = 4
WHERE skill_id = 'poison_strike';

UPDATE resources_skills SET 
  synergy_with = 'shadow_step',
  synergy_bonus = 'damage=+100%;crit=+30%',
  synergy_window_sec = 3
WHERE skill_id = 'backstab';

-- Mage synergies
UPDATE resources_skills SET 
  synergy_with = 'fireball',
  synergy_bonus = 'damage=+40%;status=burn',
  synergy_window_sec = 5
WHERE skill_id = 'inferno';

UPDATE resources_skills SET 
  synergy_with = 'frost_bolt',
  synergy_bonus = 'damage=+50%;status=freeze',
  synergy_window_sec = 5
WHERE skill_id = 'ice_storm';

-- Cleric synergies
UPDATE resources_skills SET 
  synergy_with = 'holy_light',
  synergy_bonus = 'healing=+50%;status=regen',
  synergy_window_sec = 6
WHERE skill_id = 'divine_blessing';

UPDATE resources_skills SET 
  synergy_with = 'smite',
  synergy_bonus = 'damage=+40%;mp_restore=10',
  synergy_window_sec = 5
WHERE skill_id = 'holy_wrath';

-- Ranger synergies
UPDATE resources_skills SET 
  synergy_with = 'aimed_shot',
  synergy_bonus = 'damage=+70%;pierce=true',
  synergy_window_sec = 4
WHERE skill_id = 'multi_shot';

UPDATE resources_skills SET 
  synergy_with = 'hunters_mark',
  synergy_bonus = 'damage=+50%;crit=+25%',
  synergy_window_sec = 8
WHERE skill_id = 'aimed_shot';

-- Hunter synergies
UPDATE resources_skills SET 
  synergy_with = 'trap',
  synergy_bonus = 'damage=+80%;status=bleed',
  synergy_window_sec = 5
WHERE skill_id = 'wild_strike';

UPDATE resources_skills SET 
  synergy_with = 'beast_call',
  synergy_bonus = 'damage=+60%;atk_boost=+10',
  synergy_window_sec = 10
WHERE skill_id = 'feral_rage';

-- Note: Synergy tracking will be stored in player stats as:
-- last_skills=skill_id1:timestamp1,skill_id2:timestamp2,skill_id3:timestamp3
-- This allows checking the last 3 skills used within their time windows
