-- Blossom Counseling - ONE-SHOT setup for Hostinger phpMyAdmin
-- How to use:
--   1. Hostinger hPanel > Databases > phpMyAdmin > click your database (left side).
--   2. Open the "SQL" tab, paste this whole file, click "Go".
-- Safe to run more than once (uses IF NOT EXISTS / INSERT IGNORE).
-- NOTE: no CREATE DATABASE / USE here on purpose - Hostinger already created the DB.
-- Blog posts, gallery photos and categories are seeded automatically by the
-- admin panel the first time you open /admin.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL DEFAULT 'Admin',
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `avatar` VARCHAR(255) NULL,
  `last_login_at` DATETIME NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `admin_id` INT UNSIGNED NOT NULL,
  `code_hash` VARCHAR(255) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `inquiries` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(30) NOT NULL DEFAULT '',
  `email` VARCHAR(150) NOT NULL DEFAULT '',
  `subject` VARCHAR(150) NOT NULL DEFAULT '',
  `message` TEXT NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'new',
  `admin_notes` TEXT NULL,
  `ip` VARCHAR(45) NOT NULL DEFAULT '',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (`status`), INDEX (`created_at`), INDEX (`ip`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `appointments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(30) NOT NULL DEFAULT '',
  `email` VARCHAR(150) NOT NULL DEFAULT '',
  `service` VARCHAR(100) NOT NULL DEFAULT '',
  `appointment_type` VARCHAR(100) NOT NULL DEFAULT '',
  `message` TEXT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
  `preferred_date` DATE NULL,
  `scheduled_at` DATETIME NULL,
  `payment_amount` DECIMAL(10,2) NULL,
  `payment_mode` VARCHAR(20) NULL,
  `completed_at` DATETIME NULL,
  `admin_notes` TEXT NULL,
  `ip` VARCHAR(45) NOT NULL DEFAULT '',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (`status`), INDEX (`created_at`), INDEX (`ip`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `appointment_id` INT UNSIGNED NOT NULL,
  `kind` VARCHAR(10) NOT NULL DEFAULT 'payment',
  `amount` DECIMAL(10,2) NOT NULL,
  `mode` VARCHAR(20) NOT NULL DEFAULT 'Cash',
  `reference` VARCHAR(100) NOT NULL DEFAULT '',
  `note` VARCHAR(255) NOT NULL DEFAULT '',
  `paid_at` DATETIME NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (`appointment_id`), INDEX (`paid_at`), INDEX (`mode`),
  CONSTRAINT `fk_payments_appointment` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `blog_posts` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(220) NOT NULL UNIQUE,
  `category` VARCHAR(60) NOT NULL DEFAULT 'General',
  `excerpt` VARCHAR(400) NOT NULL DEFAULT '',
  `content` MEDIUMTEXT NOT NULL,
  `image` VARCHAR(1000) NULL,
  `read_time` TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `status` VARCHAR(20) NOT NULL DEFAULT 'published',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (`status`), INDEX (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `gallery_items` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `type` VARCHAR(10) NOT NULL DEFAULT 'image',
  `title` VARCHAR(150) NOT NULL DEFAULT '',
  `category` VARCHAR(30) NOT NULL DEFAULT 'personal',
  `file` VARCHAR(1000) NULL,
  `video_id` VARCHAR(20) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (`type`), INDEX (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `type` VARCHAR(10) NOT NULL,
  `slug` VARCHAR(30) NOT NULL,
  `name` VARCHAR(60) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `type_slug` (`type`, `slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tracks which one-time seeds (blogs, gallery, categories) have already run
CREATE TABLE IF NOT EXISTS `app_flags` (
  `name` VARCHAR(60) PRIMARY KEY
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- Default admin account.
--   Email:    admin@gmail.com
--   Password: admin123      <-- CHANGE THIS after first login!
INSERT IGNORE INTO `admins` (`name`, `email`, `password`)
VALUES ('Admin', 'admin@gmail.com', '$2y$12$taU/UlZkA.55g1W3cVmiduZGuySa9ZAgxeRqVOD12UbL3xF1ztife');
