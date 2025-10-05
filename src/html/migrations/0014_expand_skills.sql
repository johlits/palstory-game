-- 0014_expand_skills.sql
-- Expand skills: add 5-10 skills per job with tier-appropriate balance
-- Safe to run multiple times (idempotent with INSERT IGNORE)
START TRANSACTION;

-- Insert additional skills for each job
INSERT IGNORE INTO resources_skills (skill_id, name, description, mp_cost, cooldown_sec, damage_multiplier, unlock_cost, required_job, required_skills) VALUES

-- Warrior skills (total: 10)
('cleave', 'Cleave', 'Sweeping attack hitting multiple foes for 140% damage.', 6, 7, 1.40, 1, 'warrior', NULL),
('shield_wall', 'Shield Wall', 'Defensive stance reducing damage taken, counter for 110% damage.', 5, 10, 1.10, 1, 'warrior', NULL),
('war_cry', 'War Cry', 'Intimidating shout boosting ATK, strike for 125% damage.', 4, 8, 1.25, 1, 'warrior', NULL),
('crushing_blow', 'Crushing Blow', 'Devastating strike for 200% damage. High MP cost.', 12, 12, 2.00, 2, 'warrior', 'heavy_swing'),
('iron_will', 'Iron Will', 'Unbreakable defense, counter for 150% damage.', 8, 15, 1.50, 2, 'warrior', 'shield_bash'),
('berserker_rage', 'Berserker Rage', 'Furious assault for 220% damage. Requires mastery.', 15, 15, 2.20, 3, 'warrior', 'crushing_blow'),

-- Rogue skills (total: 10)
('poison_blade', 'Poison Blade', 'Venomous strike for 140% damage over time.', 5, 5, 1.40, 1, 'rogue', NULL),
('evasion', 'Evasion', 'Dodge and counter for 115% damage.', 3, 6, 1.15, 1, 'rogue', NULL),
('dual_strike', 'Dual Strike', 'Twin blade attack for 165% damage.', 7, 7, 1.65, 2, 'rogue', 'quick_stab'),
('assassinate', 'Assassinate', 'Lethal strike for 250% damage. High crit chance.', 15, 18, 2.50, 3, 'rogue', 'backstab,shadow_strike'),
('smoke_bomb', 'Smoke Bomb', 'Vanish and strike for 180% damage.', 8, 10, 1.80, 2, 'rogue', 'shadow_strike'),

-- Mage skills (total: 10)
('lightning_bolt', 'Lightning Bolt', 'Electric strike for 145% damage.', 6, 5, 1.45, 1, 'mage', NULL),
('frost_nova', 'Frost Nova', 'Freezing blast for 135% damage, slows enemy.', 5, 6, 1.35, 1, 'mage', NULL),
('meteor', 'Meteor', 'Devastating spell for 210% damage. High MP cost.', 14, 14, 2.10, 3, 'mage', 'fireball'),
('arcane_missiles', 'Arcane Missiles', 'Rapid magical strikes for 155% damage.', 7, 6, 1.55, 2, 'mage', 'arcane_bolt'),
('blizzard', 'Blizzard', 'Massive ice storm for 190% damage.', 12, 12, 1.90, 3, 'mage', 'ice_shard,frost_nova'),

-- Cleric skills (total: 10)
('holy_light', 'Holy Light', 'Divine blast for 135% damage.', 4, 4, 1.35, 1, 'cleric', NULL),
('judgement', 'Judgement', 'Righteous strike for 165% damage.', 7, 7, 1.65, 2, 'cleric', 'smite'),
('wrath', 'Wrath', 'Divine fury for 180% damage.', 9, 9, 1.80, 2, 'cleric', 'divine_hammer'),
('holy_nova', 'Holy Nova', 'Radiant explosion for 145% damage.', 6, 6, 1.45, 1, 'cleric', NULL),
('divine_wrath', 'Divine Wrath', 'Ultimate holy power for 230% damage.', 16, 16, 2.30, 3, 'cleric', 'wrath,judgement'),

-- Ranger skills (total: 10)
('multi_shot', 'Multi Shot', 'Fire multiple arrows for 140% damage.', 5, 5, 1.40, 1, 'ranger', NULL),
('explosive_arrow', 'Explosive Arrow', 'Explosive shot for 175% damage.', 8, 8, 1.75, 2, 'ranger', 'aimed_shot'),
('volley', 'Volley', 'Rain of arrows for 160% damage.', 7, 7, 1.60, 2, 'ranger', 'multi_shot'),
('snipe', 'Snipe', 'Perfect shot for 240% damage. Requires focus.', 14, 15, 2.40, 3, 'ranger', 'aimed_shot,piercing_arrow'),
('barrage', 'Barrage', 'Continuous fire for 195% damage.', 11, 11, 1.95, 3, 'ranger', 'volley,explosive_arrow'),

-- Hunter skills (total: 10)
('wild_strike', 'Wild Strike', 'Primal attack for 140% damage.', 5, 5, 1.40, 1, 'hunter', NULL),
('ambush', 'Ambush', 'Surprise attack for 170% damage.', 7, 8, 1.70, 2, 'hunter', 'tracking_shot'),
('ensnare', 'Ensnare', 'Trap and strike for 155% damage.', 6, 7, 1.55, 2, 'hunter', 'trap_attack'),
('feral_rage', 'Feral Rage', 'Savage fury for 200% damage.', 12, 12, 2.00, 3, 'hunter', 'beast_strike,wild_strike'),
('apex_hunt', 'Apex Hunt', 'Ultimate hunter strike for 225% damage.', 15, 15, 2.25, 3, 'hunter', 'ambush,feral_rage');

COMMIT;
