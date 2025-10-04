-- 0010_add_skill_system.sql
-- Add skill points, job, and unlocked skills to existing players
-- Safe to run multiple times (idempotent)
START TRANSACTION;

-- Backfill skill_points=0, job=none, unlocked_skills= for all existing players that don't have them
UPDATE game_players 
SET stats = CONCAT(stats, 'skill_points=0;job=none;unlocked_skills=;')
WHERE stats NOT LIKE '%skill_points=%';

COMMIT;
