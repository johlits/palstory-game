-- 0013_backfill_missing_stats.sql
-- Backfill missing crt, skill_points, job, unlocked_skills for any players that don't have them
-- Safe to run multiple times (idempotent)
START TRANSACTION;

-- Add missing stats to players who don't have them
UPDATE game_players 
SET stats = CONCAT(
  stats,
  CASE WHEN stats NOT LIKE '%crt=%' THEN 'crt=5;' ELSE '' END,
  CASE WHEN stats NOT LIKE '%skill_points=%' THEN 'skill_points=0;' ELSE '' END,
  CASE WHEN stats NOT LIKE '%job=%' THEN 'job=none;' ELSE '' END,
  CASE WHEN stats NOT LIKE '%unlocked_skills=%' THEN 'unlocked_skills=;' ELSE '' END
)
WHERE stats NOT LIKE '%crt=%' 
   OR stats NOT LIKE '%skill_points=%' 
   OR stats NOT LIKE '%job=%' 
   OR stats NOT LIKE '%unlocked_skills=%';

COMMIT;
