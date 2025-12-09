-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: palplanner.com.mysql.service.one.com:3306
-- Generation Time: Dec 09, 2025 at 06:10 PM
-- Server version: 10.6.23-MariaDB-ubu2204
-- PHP Version: 8.1.2-1ubuntu2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `palplanner_comstory`
--

-- --------------------------------------------------------

--
-- Table structure for table `game_items`
--

CREATE TABLE IF NOT EXISTS `game_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `room_id` int(11) NOT NULL,
  `stats` text NOT NULL,
  `resource_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `equipped` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `game_locations`
--

CREATE TABLE IF NOT EXISTS `game_locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `room_id` int(11) NOT NULL,
  `x` int(11) NOT NULL,
  `y` int(11) NOT NULL,
  `stats` text NOT NULL,
  `resource_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `game_logs`
--

CREATE TABLE IF NOT EXISTS `game_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `ts` timestamp NOT NULL DEFAULT current_timestamp(),
  `room_id` int(11) DEFAULT NULL,
  `player_name` varchar(256) DEFAULT NULL,
  `action` varchar(64) NOT NULL,
  `details` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `game_monsters`
--

CREATE TABLE IF NOT EXISTS `game_monsters` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `room_id` int(11) NOT NULL,
  `x` int(11) NOT NULL,
  `y` int(11) NOT NULL,
  `stats` text NOT NULL,
  `resource_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `game_players`
--

CREATE TABLE IF NOT EXISTS `game_players` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(256) NOT NULL,
  `room_id` int(11) NOT NULL,
  `x` int(11) NOT NULL,
  `y` int(11) NOT NULL,
  `stats` text NOT NULL,
  `resource_id` int(11) DEFAULT -1,
  `last_seen` timestamp NOT NULL DEFAULT current_timestamp(),
  `respawn_x` int(11) DEFAULT 0 COMMENT 'X coordinate for respawn after death',
  `respawn_y` int(11) DEFAULT 0 COMMENT 'Y coordinate for respawn after death',
  `storage_slots` int(11) NOT NULL DEFAULT 20 COMMENT 'Max storage capacity'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `game_rooms`
--

CREATE TABLE IF NOT EXISTS `game_rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(256) NOT NULL,
  `expiration` datetime NOT NULL,
  `regen` int(11) NOT NULL,
  `spawn_location_id` int(11) DEFAULT NULL COMMENT 'Resource location ID where players spawn/respawn in this room'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resources_items`
--

CREATE TABLE IF NOT EXISTS `resources_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(64) NOT NULL,
  `image` varchar(64) NOT NULL,
  `description` text NOT NULL,
  `stats` text NOT NULL,
  `model_3d` varchar(64) DEFAULT NULL,
  `banned` tinyint(1) NOT NULL DEFAULT 0,
  `rarity` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Item rarity: 0=common, 1=uncommon, 2=rare, 3=epic, 4=legendary'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resources_locations`
--

CREATE TABLE IF NOT EXISTS `resources_locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(64) NOT NULL,
  `image` varchar(64) NOT NULL,
  `description` text NOT NULL,
  `lvl_from` int(11) NOT NULL,
  `lvl_to` int(11) NOT NULL,
  `stats` text NOT NULL,
  `model_3d` varchar(64) DEFAULT NULL,
  `banned` tinyint(1) NOT NULL DEFAULT 0,
  `location_type` enum('wilderness','town','rest_spot','dungeon') NOT NULL DEFAULT 'wilderness' COMMENT 'Location type: wilderness (combat/gather), town (safe zone with services), rest_spot (HP/MP restoration), dungeon (special instances)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resources_monsters`
--

CREATE TABLE IF NOT EXISTS `resources_monsters` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(64) NOT NULL,
  `image` varchar(64) NOT NULL,
  `description` text NOT NULL,
  `stats` text NOT NULL,
  `model_3d` varchar(64) DEFAULT NULL,
  `mp` int(11) NOT NULL DEFAULT 0 COMMENT 'Monster MP for skill usage',
  `maxmp` int(11) NOT NULL DEFAULT 0 COMMENT 'Monster max MP',
  `banned` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `monster_skills`
--

CREATE TABLE IF NOT EXISTS `monster_skills` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `monster_resource_id` int(11) NOT NULL COMMENT 'FK to resources_monsters.id',
  `skill_id` varchar(32) NOT NULL COMMENT 'FK to resources_skills.skill_id'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `player_storage`
--

CREATE TABLE IF NOT EXISTS `player_storage` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `player_id` int(11) NOT NULL COMMENT 'Owner player ID',
  `item_resource_id` int(11) NOT NULL COMMENT 'References resources_items.id',
  `item_stats` text NOT NULL COMMENT 'Item stats JSON (same format as game_items.stats)',
  `quantity` int(11) NOT NULL DEFAULT 1 COMMENT 'Stack count for stackable items',
  `stored_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resources_jobs`
--

CREATE TABLE IF NOT EXISTS `resources_jobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `job_id` varchar(32) NOT NULL,
  `name` varchar(64) NOT NULL,
  `description` text NOT NULL,
  `stat_modifiers` varchar(256) NOT NULL COMMENT 'e.g., +ATK +DEF',
  `min_level` int(11) NOT NULL DEFAULT 1 COMMENT 'Minimum level to select this job',
  `tier` int(11) NOT NULL DEFAULT 1 COMMENT 'Job tier: 1=base, 2=advanced, 3=expert, 4=master, 5=legendary',
  `required_base_job` varchar(32) DEFAULT NULL COMMENT 'Required base job for advancement (NULL for tier 1)',
  `banned` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resources_skills`
--

CREATE TABLE IF NOT EXISTS `resources_skills` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `skill_id` varchar(32) NOT NULL,
  `name` varchar(64) NOT NULL,
  `description` text NOT NULL,
  `mp_cost` int(11) NOT NULL DEFAULT 0,
  `cooldown_sec` int(11) NOT NULL DEFAULT 0,
  `damage_multiplier` decimal(3,2) NOT NULL DEFAULT 1.00 COMMENT 'Damage multiplier (e.g., 1.50 for 150%)',
  `unlock_cost` int(11) NOT NULL DEFAULT 1 COMMENT 'Skill points required to unlock',
  `required_job` varchar(32) NOT NULL DEFAULT 'all' COMMENT 'Job requirement: all, warrior, rogue, etc.',
  `required_skills` varchar(256) DEFAULT NULL COMMENT 'Comma-separated list of skill_ids required to unlock this skill',
  `banned` tinyint(1) NOT NULL DEFAULT 0,
  `skill_type` enum('active','passive') NOT NULL DEFAULT 'active' COMMENT 'Skill type: active (used in combat) or passive (always on)',
  `stat_modifiers` varchar(256) DEFAULT NULL COMMENT 'Stat bonuses for passive skills (e.g., atk=+5;def=+3;maxhp=+10)',
  `synergy_with` varchar(32) DEFAULT NULL COMMENT 'Skill ID that triggers synergy when used before this skill',
  `synergy_bonus` varchar(256) DEFAULT NULL COMMENT 'Bonus effect when synergy triggers (e.g., damage=+50%;cooldown=-2;status=stun)',
  `synergy_window_sec` int(11) DEFAULT 5 COMMENT 'Time window in seconds to trigger synergy after prerequisite skill'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `schema_migrations`
--

CREATE TABLE IF NOT EXISTS `schema_migrations` (
  `version` varchar(64) NOT NULL PRIMARY KEY,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `checksum` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shop_inventory`
--

CREATE TABLE IF NOT EXISTS `shop_inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `item_id` varchar(64) NOT NULL COMMENT 'References resources_items.name',
  `price` int(11) NOT NULL COMMENT 'Purchase price in gold',
  `stock_unlimited` tinyint(1) DEFAULT 1 COMMENT '1 = unlimited stock, 0 = limited',
  `available_at_level` int(11) DEFAULT 1 COMMENT 'Minimum player level to see this item',
  `category` varchar(32) DEFAULT 'general' COMMENT 'Shop category: weapon, armor, consumable, general'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;
