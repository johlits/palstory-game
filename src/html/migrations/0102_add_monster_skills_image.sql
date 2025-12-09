-- Add image column to monster_skills
-- Migration: 0102_add_monster_skills_image

ALTER TABLE `monster_skills` ADD COLUMN IF NOT EXISTS `image` varchar(64) DEFAULT NULL AFTER `skill_id`;
