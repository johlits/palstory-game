-- Migration: Add composite indexes for performance optimization
-- Date: 2025-11-30
-- Description: Adds indexes to frequently queried columns to improve query performance

-- Index for game_monsters: frequently queried by room_id + x + y
CREATE INDEX idx_game_monsters_room_pos 
ON game_monsters (room_id, x, y);

-- Index for game_locations: frequently queried by room_id + x + y
CREATE INDEX idx_game_locations_room_pos 
ON game_locations (room_id, x, y);

-- Index for game_players: frequently queried by room_id + name
CREATE INDEX idx_game_players_room_name 
ON game_players (room_id, name);

-- Index for game_items: frequently queried by owner_id + equipped
CREATE INDEX idx_game_items_owner_equipped 
ON game_items (owner_id, equipped);

-- Index for game_logs: frequently queried for rate limiting
CREATE INDEX idx_game_logs_rate_limit 
ON game_logs (room_id, player_name, action, ts);

-- Index for game_logs: cleanup by timestamp
CREATE INDEX idx_game_logs_ts 
ON game_logs (ts);
