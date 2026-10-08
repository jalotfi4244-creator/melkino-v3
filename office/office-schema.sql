-- ============================================================
-- دفتر ملکینو شهر — جدول‌های دیتابیس (فقط جدول‌های جدید دفتر)
-- تولید خودکار از روی office_ensure_tables() — بدون دست‌زدن به جدول‌های سایت
-- ایمپورت در phpMyAdmin هاست یا: mysql DB < office-schema.sql
-- قابل اجرای مجدد است (CREATE TABLE IF NOT EXISTS)
-- ============================================================
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `office_customers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL DEFAULT '',
  `phone` varchar(20) NOT NULL DEFAULT '',
  `kind` varchar(20) NOT NULL DEFAULT 'buyer',
  `budget` bigint(20) unsigned NOT NULL DEFAULT 0,
  `min_area` int(10) unsigned NOT NULL DEFAULT 0,
  `neighborhood` varchar(120) NOT NULL DEFAULT '',
  `notes` text NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_office_customers_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `office_requests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL DEFAULT 0,
  `name` varchar(120) NOT NULL DEFAULT '',
  `phone` varchar(20) NOT NULL DEFAULT '',
  `kind` varchar(10) NOT NULL DEFAULT 'buy',
  `budget` bigint(20) unsigned NOT NULL DEFAULT 0,
  `min_area` int(10) unsigned NOT NULL DEFAULT 0,
  `neighborhood` varchar(120) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'new',
  `created_by` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_office_requests_phone` (`phone`),
  KEY `idx_office_requests_status` (`status`),
  KEY `idx_office_requests_customer` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `office_visits` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ad_id` varchar(64) NOT NULL DEFAULT '',
  `customer_id` int(10) unsigned NOT NULL DEFAULT 0,
  `name` varchar(120) NOT NULL DEFAULT '',
  `phone` varchar(20) NOT NULL DEFAULT '',
  `visit_at` datetime DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'scheduled',
  `notes` text NOT NULL,
  `created_by` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_office_visits_ad` (`ad_id`),
  KEY `idx_office_visits_status` (`status`),
  KEY `idx_office_visits_at` (`visit_at`),
  KEY `idx_office_visits_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `office_followups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entity` varchar(20) NOT NULL DEFAULT 'ad',
  `entity_id` varchar(64) NOT NULL DEFAULT '',
  `title` varchar(180) NOT NULL DEFAULT '',
  `note` text NOT NULL,
  `due_date` date DEFAULT NULL,
  `done` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_office_followups_due` (`done`,`due_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `office_calls` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL DEFAULT '',
  `phone` varchar(20) NOT NULL DEFAULT '',
  `ad_id` varchar(64) NOT NULL DEFAULT '',
  `direction` varchar(10) NOT NULL DEFAULT 'in',
  `duration` int(10) unsigned NOT NULL DEFAULT 0,
  `note` text NOT NULL,
  `called_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_office_calls_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `office_settings` (
  `k` varchar(80) NOT NULL,
  `v` text NOT NULL,
  PRIMARY KEY (`k`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

