-- Migration 0019: Add respawn point system
-- Allow players to set their respawn location at towns

-- Add respawn_x and respawn_y columns to game_players
-- These store the coordinates where the player will respawn after death
ALTER TABLE game_players
ADD COLUMN respawn_x INT DEFAULT 0 COMMENT 'X coordinate for respawn after death',
ADD COLUMN respawn_y INT DEFAULT 0 COMMENT 'Y coordinate for respawn after death';

-- Set default respawn point for existing players to spawn (0,0)
UPDATE game_players SET respawn_x = 0, respawn_y = 0 WHERE respawn_x IS NULL;

-- Note: Players can set their respawn point by using the "Set Respawn" action at towns
-- Death penalty: lose 50% of gold and 25% of current level EXP (not total EXP)
-- Items are kept on death (changed from previous harsh penalty)
