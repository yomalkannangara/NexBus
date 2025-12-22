-- ============================================
-- Profile Image Column Migration
-- ============================================
-- Description: Add profile_image column to users table for profile pictures
-- Date: 2025-12-19
-- Instructions: Run this SQL in phpMyAdmin or MySQL command line
-- ============================================

-- Add profile_image column
ALTER TABLE `users` 
ADD COLUMN `profile_image` VARCHAR(255) NULL DEFAULT NULL 
COMMENT 'Path to user profile image' 
AFTER `status`;

-- Optional: Add profile_image_updated_at for tracking
-- ALTER TABLE `users` 
-- ADD COLUMN `profile_image_updated_at` TIMESTAMP NULL 
-- AFTER `profile_image`;
