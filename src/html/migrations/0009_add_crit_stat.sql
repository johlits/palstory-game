-- 0009_add_crit_stat.sql
-- Add critical hit chance (crt) stat to existing players and monsters
-- Safe to run multiple times (idempotent)
START TRANSACTION;

-- Backfill crt=5 (5% base crit chance) for all existing players that don't have it
UPDATE game_players 
SET stats = CONCAT(stats, 'crt=5;')
WHERE stats NOT LIKE '%crt=%';

-- Note: Monsters get crt from their resource definitions or default to 5% in code
-- No need to backfill game_monsters table as stats are ephemeral (respawn from resources)

COMMIT;
