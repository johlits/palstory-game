-- 0011_add_skills_jobs_tables.sql
-- Create resources_skills and resources_jobs tables to store skill and job definitions
-- Safe to run multiple times (idempotent)
START TRANSACTION;

-- Jobs table
CREATE TABLE IF NOT EXISTS resources_jobs (
  id INT NOT NULL AUTO_INCREMENT,
  job_id VARCHAR(32) NOT NULL,
  name VARCHAR(64) NOT NULL,
  description TEXT NOT NULL,
  stat_modifiers VARCHAR(256) NOT NULL COMMENT 'e.g., +ATK +DEF',
  min_level INT NOT NULL DEFAULT 1 COMMENT 'Minimum level to select this job',
  banned TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_job_id (job_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Skills table
CREATE TABLE IF NOT EXISTS resources_skills (
  id INT NOT NULL AUTO_INCREMENT,
  skill_id VARCHAR(32) NOT NULL,
  name VARCHAR(64) NOT NULL,
  description TEXT NOT NULL,
  mp_cost INT NOT NULL DEFAULT 0,
  cooldown_sec INT NOT NULL DEFAULT 0,
  damage_multiplier DECIMAL(3,2) NOT NULL DEFAULT 1.00 COMMENT 'Damage multiplier (e.g., 1.50 for 150%)',
  unlock_cost INT NOT NULL DEFAULT 1 COMMENT 'Skill points required to unlock',
  required_job VARCHAR(32) NOT NULL DEFAULT 'all' COMMENT 'Job requirement: all, warrior, rogue, etc.',
  banned TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_skill_id (skill_id),
  INDEX idx_required_job (required_job)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert jobs
INSERT INTO resources_jobs (job_id, name, description, stat_modifiers, min_level) VALUES
('warrior', 'Warrior', 'Strong melee fighter with high ATK and DEF. Masters of close combat and heavy weapons.', '+ATK +DEF', 1),
('rogue', 'Rogue', 'Agile assassin with high SPD, EVD, and ATK. Excels at quick strikes and evasion.', '+SPD +EVD +ATK', 1),
('mage', 'Mage', 'Powerful spellcaster with high ATK but low DEF. Commands devastating magical attacks.', '+ATK -DEF', 1),
('cleric', 'Cleric', 'Holy warrior with balanced stats and high DEF. Protects allies and smites evil.', '+DEF', 1),
('ranger', 'Ranger', 'Ranged attacker with high SPD and ATK. Expert marksman and wilderness survivor.', '+SPD +ATK', 1),
('hunter', 'Hunter', 'Master tracker with balanced ATK and SPD. Specializes in hunting monsters and survival.', '+ATK +SPD +EVD', 1);

-- Insert skills (from skills.md and combat.php)
INSERT INTO resources_skills (skill_id, name, description, mp_cost, cooldown_sec, damage_multiplier, unlock_cost, required_job) VALUES
-- Universal skills
('power_strike', 'Power Strike', 'Heavy attack for 150% damage.', 5, 5, 1.50, 1, 'all'),

-- Warrior skills
('heavy_swing', 'Heavy Swing', 'Slow, crushing blow for 180% damage.', 8, 8, 1.80, 1, 'warrior'),
('shield_bash', 'Shield Bash', 'Bash with shield for 130% damage and stun chance.', 4, 6, 1.30, 1, 'warrior'),

-- Rogue skills
('quick_stab', 'Quick Stab', 'Fast strike for 120% damage.', 3, 3, 1.20, 1, 'rogue'),
('backstab', 'Backstab', 'Precise strike for 170% damage.', 6, 6, 1.70, 1, 'rogue'),
('shadow_strike', 'Shadow Strike', 'Strike from shadows for 160% damage.', 5, 5, 1.60, 1, 'rogue'),

-- Mage skills
('arcane_bolt', 'Arcane Bolt', 'Focused bolt for 130% damage.', 4, 3, 1.30, 1, 'mage'),
('fireball', 'Fireball', 'Searing blast for 160% damage.', 7, 6, 1.60, 1, 'mage'),
('ice_shard', 'Ice Shard', 'Frozen projectile for 140% damage.', 5, 4, 1.40, 1, 'mage'),

-- Cleric skills
('smite', 'Smite', 'Holy strike for 140% damage.', 5, 5, 1.40, 1, 'cleric'),
('divine_hammer', 'Divine Hammer', 'Blessed weapon strike for 155% damage.', 6, 6, 1.55, 1, 'cleric'),

-- Ranger skills
('quick_shot', 'Quick Shot', 'Swift shot for 120% damage.', 3, 3, 1.20, 1, 'ranger'),
('aimed_shot', 'Aimed Shot', 'Carefully aimed shot for 150% damage.', 6, 5, 1.50, 1, 'ranger'),
('piercing_arrow', 'Piercing Arrow', 'Armor-piercing shot for 165% damage.', 7, 7, 1.65, 1, 'ranger'),

-- Hunter skills
('tracking_shot', 'Tracking Shot', 'Guided shot for 135% damage.', 4, 4, 1.35, 1, 'hunter'),
('beast_strike', 'Beast Strike', 'Savage attack for 155% damage.', 6, 5, 1.55, 1, 'hunter'),
('trap_attack', 'Trap Attack', 'Set trap and strike for 145% damage.', 5, 6, 1.45, 1, 'hunter');

COMMIT;
