-- Add image column to resources_jobs and resources_skills
-- Migration: 0101_add_job_skill_images

ALTER TABLE `resources_jobs` ADD COLUMN IF NOT EXISTS `image` varchar(64) DEFAULT NULL AFTER `description`;

ALTER TABLE `resources_skills` ADD COLUMN IF NOT EXISTS `image` varchar(64) DEFAULT NULL AFTER `description`;
