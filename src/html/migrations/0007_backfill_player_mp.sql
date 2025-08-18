-- 0007_backfill_player_mp.sql
-- Backfill MP for players that do not yet have mp/maxmp in their serialized stats.
-- Idempotent and safe to re-run.

START TRANSACTION;

-- Add mp=50 if missing
UPDATE game_players
SET stats = CONCAT(stats, IF(RIGHT(stats,1)=';','', ';'), 'mp=50')
WHERE stats NOT LIKE '%mp=%';

-- Add maxmp=50 if missing
UPDATE game_players
SET stats = CONCAT(stats, IF(RIGHT(stats,1)=';','', ';'), 'maxmp=50')
WHERE stats NOT LIKE '%maxmp=%';

COMMIT;
