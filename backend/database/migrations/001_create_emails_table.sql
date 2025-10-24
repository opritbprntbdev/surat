-- Create database if not exists
CREATE DATABASE IF NOT EXISTS `gmail_clone` 
DEFAULT CHARACTER SET utf8mb4 
DEFAULT COLLATE utf8mb4_unicode_ci;

USE `gmail_clone`;

-- Create emails table
CREATE TABLE IF NOT EXISTS `emails` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from_name` varchar(255) NOT NULL,
  `from_email` varchar(255) NOT NULL,
  `to_email` varchar(255) NOT NULL,
  `cc_email` varchar(255) DEFAULT NULL,
  `bcc_email` varchar(255) DEFAULT NULL,
  `subject` varchar(500) NOT NULL,
  `preview` text,
  `content` longtext NOT NULL,
  `category` varchar(50) DEFAULT 'primary',
  `labels` json DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `is_starred` tinyint(1) DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_is_starred` (`is_starred`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create email_attachments table
CREATE TABLE IF NOT EXISTS `email_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_email_id` (`email_id`),
  CONSTRAINT `fk_email_attachments_email` FOREIGN KEY (`email_id`) REFERENCES `emails` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data
INSERT INTO `emails` (`from_name`, `from_email`, `to_email`, `subject`, `preview`, `content`, `category`, `labels`, `is_read`, `is_starred`, `created_at`, `updated_at`) VALUES
('John Doe', 'john@example.com', 'me@example.com', 'Welcome to Gmail Clone', 'This is your first email...', 'This is your first email. Welcome to the Gmail Clone application!', 'primary', '[]', 0, 0, NOW(), NOW()),
('Jane Smith', 'jane@example.com', 'me@example.com', 'Meeting Tomorrow', 'Don\'t forget about our meeting...', 'Don\'t forget about our meeting tomorrow at 10 AM. See you there!', 'primary', '["Work"]', 1, 1, NOW(), NOW()),
('Support Team', 'support@company.com', 'me@example.com', 'Your Account Update', 'We have updated your account settings...', 'We have updated your account settings. Please review the changes.', 'updates', '[]', 0, 0, NOW(), NOW());
