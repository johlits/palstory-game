-- 0001_init.sql
-- Core schema initialization. Safe to run multiple times.
START TRANSACTION;

CREATE TABLE IF NOT EXISTS schema_migrations (
  version VARCHAR(64) PRIMARY KEY,
  applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  checksum VARCHAR(64) NOT NULL
);

-- Players
CREATE TABLE IF NOT EXISTS game_players (
  id INT NOT NULL AUTO_INCREMENT,
  name VARCHAR(256) NOT NULL,
  room_id INT NOT NULL,
  x INT NOT NULL,
  y INT NOT NULL,
  stats TEXT NOT NULL,
  resource_id INT DEFAULT -1,
  PRIMARY KEY (id),
  INDEX idx_players_room (room_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Rooms
CREATE TABLE IF NOT EXISTS game_rooms (
  id INT NOT NULL AUTO_INCREMENT,
  name VARCHAR(256) NOT NULL,
  expiration DATETIME NOT NULL,
  regen INT NOT NULL,
  PRIMARY KEY (id),
  INDEX idx_rooms_exp (expiration)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Items placed/owned
CREATE TABLE IF NOT EXISTS game_items (
  id INT NOT NULL AUTO_INCREMENT,
  room_id INT NOT NULL,
  stats TEXT NOT NULL,
  resource_id INT NOT NULL,
  owner_id INT NOT NULL,
  equipped TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  INDEX idx_items_room (room_id),
  INDEX idx_items_owner (owner_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Locations (map tiles / objects)
CREATE TABLE IF NOT EXISTS game_locations (
  id INT NOT NULL AUTO_INCREMENT,
  room_id INT NOT NULL,
  x INT NOT NULL,
  y INT NOT NULL,
  stats TEXT NOT NULL,
  resource_id INT NOT NULL,
  PRIMARY KEY (id),
  INDEX idx_locations_room_xy (room_id, x, y)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Monsters
CREATE TABLE IF NOT EXISTS game_monsters (
  id INT NOT NULL AUTO_INCREMENT,
  room_id INT NOT NULL,
  x INT NOT NULL,
  y INT NOT NULL,
  stats TEXT NOT NULL,
  resource_id INT NOT NULL,
  PRIMARY KEY (id),
  INDEX idx_monsters_room_xy (room_id, x, y)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Item resources
CREATE TABLE IF NOT EXISTS resources_items (
  id INT NOT NULL AUTO_INCREMENT,
  name VARCHAR(64) NOT NULL,
  image VARCHAR(64) NOT NULL,
  description TEXT NOT NULL,
  stats TEXT NOT NULL,
  banned TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_resources_items_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;
