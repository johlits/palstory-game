-- Migration 0018: Add status effects system
-- Status effects are stored in player stats as effect_[name]=[expiry_timestamp]
-- This migration adds some skills that apply status effects

-- Add status effect skills for each job
INSERT INTO resources_skills (skill_id, name, description, mp_cost, cooldown_sec, damage_multiplier, unlock_cost, required_job, required_skills, skill_type, stat_modifiers, banned) VALUES
-- Warrior status effect skills
('shield_stance', 'Shield Stance', 'Active: Defensive stance that reduces incoming damage by 30% for 10 seconds.', 8, 15, 1.00, 2, 'warrior', 'shield_bash', 'active', NULL, 0),
('battle_shout', 'Battle Shout', 'Active: Rallying cry that increases ATK by 20% for 15 seconds.', 6, 20, 1.00, 2, 'warrior', 'war_cry', 'active', NULL, 0),

-- Rogue status effect skills
('poison_strike', 'Poison Strike', 'Active: Strike that poisons the enemy, dealing 5 damage per turn for 15 seconds.', 7, 12, 1.30, 2, 'rogue', 'poison_blade', 'active', NULL, 0),
('shadow_step', 'Shadow Step', 'Active: Increases EVD by 50% for 8 seconds.', 5, 18, 1.00, 2, 'rogue', 'evasion', 'active', NULL, 0),

-- Mage status effect skills
('frost_armor', 'Frost Armor', 'Active: Ice shield that reduces incoming damage by 25% and slows attackers for 12 seconds.', 9, 20, 1.00, 2, 'mage', 'frost_nova', 'active', NULL, 0),
('arcane_surge', 'Arcane Surge', 'Active: Magical surge that increases ATK by 30% for 10 seconds.', 10, 25, 1.00, 3, 'mage', 'arcane_missiles', 'active', NULL, 0),

-- Cleric status effect skills
('holy_shield', 'Holy Shield', 'Active: Divine protection that reduces incoming damage by 35% for 12 seconds.', 8, 18, 1.00, 2, 'cleric', 'holy_light', 'active', NULL, 0),
('regeneration', 'Regeneration', 'Active: Healing aura that restores 8 HP per turn for 15 seconds.', 10, 20, 1.00, 2, 'cleric', 'cleric_faith', 'active', NULL, 0),

-- Ranger status effect skills
('hunters_mark', 'Hunter''s Mark', 'Active: Mark target to increase your ATK by 25% against it for 12 seconds.', 6, 15, 1.00, 2, 'ranger', 'tracking_shot', 'active', NULL, 0),
('evasive_maneuvers', 'Evasive Maneuvers', 'Active: Increases SPD by 40% for 10 seconds.', 5, 16, 1.00, 2, 'ranger', 'ranger_awareness', 'active', NULL, 0),

-- Hunter status effect skills
('feral_instinct', 'Feral Instinct', 'Active: Primal power that increases ATK by 20% and SPD by 20% for 12 seconds.', 8, 18, 1.00, 2, 'hunter', 'wild_strike', 'active', NULL, 0),
('nature_ward', 'Nature''s Ward', 'Active: Natural protection that reduces incoming damage by 20% for 10 seconds.', 7, 16, 1.00, 2, 'hunter', 'hunter_survival', 'active', NULL, 0);

-- Note: Status effects are applied via special handling in combat.php
-- Effect format in player stats: effect_[name]=[expiry_timestamp];stacks=[count]
-- Common effects:
--   shield_X (damage reduction by X%)
--   regen_X (heal X HP per turn)
--   poison_X (take X damage per turn)
--   atk_boost_X (increase ATK by X%)
--   evd_boost_X (increase EVD by X%)
--   spd_boost_X (increase SPD by X%)
