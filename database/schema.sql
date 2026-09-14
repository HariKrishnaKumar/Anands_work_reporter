-- Daily Work Report Database Schema
-- For XAMPP MariaDB/MySQL

CREATE DATABASE IF NOT EXISTS `daily_work_report`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `daily_work_report`;

-- Users table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `display_name` VARCHAR(150) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_username` (`username`),
    UNIQUE KEY `uk_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Work Reports table
CREATE TABLE IF NOT EXISTS `work_reports` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `work_date` DATE NOT NULL,
    `description` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY `fk_report_user` (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_date` (`user_id`, `work_date` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Work Report Files table
CREATE TABLE IF NOT EXISTS `work_report_files` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `work_report_id` INT NOT NULL,
    `original_filename` VARCHAR(255) NOT NULL,
    `mime_type` VARCHAR(100) NOT NULL,
    `file_size` INT NOT NULL,
    `file_data` LONGBLOB NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY `fk_file_report` (`work_report_id`) REFERENCES `work_reports`(`id`) ON DELETE CASCADE,
    INDEX `idx_report` (`work_report_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed test users (password: password123)
-- bcrypt hash of 'password123' (generated with PHP 8.2)
INSERT INTO `users` (`username`, `email`, `display_name`, `password_hash`) VALUES
('hari', 'hari@test.com', 'Hari Krishna', '$2y$10$MxJXKFXViqodRwv4atHUvezQrpYo7yDGrMQokgS7TV8pLGzvvclVW'),
('anand', 'anand@test.com', 'Anand Kumar', '$2y$10$MxJXKFXViqodRwv4atHUvezQrpYo7yDGrMQokgS7TV8pLGzvvclVW'),
('priya', 'priya@test.com', 'Priya Sharma', '$2y$10$MxJXKFXViqodRwv4atHUvezQrpYo7yDGrMQokgS7TV8pLGzvvclVW');
