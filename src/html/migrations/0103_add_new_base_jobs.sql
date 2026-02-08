-- Add new base jobs: bowman, barbarian, thief, monk
-- Migration: 0103_add_new_base_jobs

INSERT INTO `resources_jobs` (`job_id`, `name`, `description`, `image`, `stat_modifiers`, `min_level`, `tier`, `required_base_job`, `banned`) VALUES
('bowman', 'Bowman', 'Skilled archer with high ATK and SPD. Strikes enemies from afar with deadly precision.', NULL, '+ATK +SPD', 1, 1, NULL, 0),
('barbarian', 'Barbarian', 'Fierce brute with overwhelming ATK and HP. Crushes foes with raw strength and fury.', NULL, '+ATK +HP', 1, 1, NULL, 0),
('thief', 'Thief', 'Cunning pickpocket with high SPD and EVD. Steals from enemies and escapes unscathed.', NULL, '+SPD +EVD', 1, 1, NULL, 0),
('monk', 'Monk', 'Disciplined martial artist with balanced ATK and DEF. Masters unarmed combat and inner focus.', NULL, '+ATK +DEF +SPD', 1, 1, NULL, 0);
