-- Blossom Counseling Admin Panel - Database Schema
-- Creates the database and the admins table.
-- Run this once (e.g. via `mysql -u root -p < database/schema.sql`)
-- then run database/install.php in a browser or via `php database/install.php`
-- to seed the default admin account with a properly hashed password.

CREATE DATABASE IF NOT EXISTS `blossom_counseling`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `blossom_counseling`;

CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL DEFAULT 'Admin',
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `last_login_at` DATETIME NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Password reset codes (6-digit code is stored hashed, expires in 10 minutes).
-- The admin panel also creates this table automatically on first use.
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `admin_id` INT UNSIGNED NOT NULL,
  `code_hash` VARCHAR(255) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---- Admin sections (these are also created automatically on first admin visit) ----
-- admins.avatar (profile photo) is added automatically by the admin panel on first visit.

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

-- Managed categories for gallery photos and blog articles (also created automatically by the admin panel)
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `type` VARCHAR(10) NOT NULL,
  `slug` VARCHAR(30) NOT NULL,
  `name` VARCHAR(60) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `type_slug` (`type`, `slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
