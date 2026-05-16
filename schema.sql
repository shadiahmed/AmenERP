-- ============================================================================
-- AmenERP Database Schema
-- ============================================================================
-- 
-- This schema initializes the AmenERP database with core tables for inventory
-- management. Following project rules:
-- - InnoDB engine for ACID compliance and foreign key support
-- - created_at and updated_at timestamps on all tables
-- - utf8mb4 charset for full Unicode support
-- - Proper indexing for performance
-- 
-- @package AmenERP
-- @author Bob
-- @version 1.0.0
-- ============================================================================

-- Set character set and collation
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- ============================================================================
-- DROP EXISTING TABLES (if any)
-- ============================================================================
-- Drop in reverse order of dependencies to avoid foreign key constraint errors

DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;

-- ============================================================================
-- CATEGORIES TABLE
-- ============================================================================
-- Stores product categories for inventory organization

CREATE TABLE `categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_categories_name` (`name`),
  KEY `idx_categories_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Product categories for inventory management';

-- ============================================================================
-- PRODUCTS TABLE
-- ============================================================================
-- Stores product inventory with category relationships

CREATE TABLE `products` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(200) NOT NULL,
  `sku` VARCHAR(50) NOT NULL,
  `quantity` INT NOT NULL DEFAULT 0,
  `unit_price` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `status` ENUM('active', 'inactive', 'discontinued') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_products_sku` (`sku`),
  KEY `idx_products_category_id` (`category_id`),
  KEY `idx_products_status` (`status`),
  KEY `idx_products_name` (`name`),
  KEY `idx_products_created_at` (`created_at`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Product inventory with pricing and stock levels';

-- ============================================================================
-- SEED DATA - CATEGORIES
-- ============================================================================
-- Initial categories to populate the dashboard

INSERT INTO `categories` (`name`, `description`) VALUES
('Electronics', 'Electronic devices, components, and accessories including computers, phones, and peripherals'),
('Office Supplies', 'General office supplies including stationery, paper products, and desk accessories'),
('Furniture', 'Office and commercial furniture including desks, chairs, and storage solutions'),
('Software', 'Software licenses, subscriptions, and digital products'),
('Hardware', 'Computer hardware components, networking equipment, and IT infrastructure');

-- ============================================================================
-- SEED DATA - PRODUCTS
-- ============================================================================
-- Sample products to demonstrate the inventory system

INSERT INTO `products` (`category_id`, `name`, `sku`, `quantity`, `unit_price`, `status`) VALUES
-- Electronics
(1, 'Wireless Mouse Logitech M185', 'ELEC-MOUSE-001', 45, 15.99, 'active'),
(1, 'USB-C Hub 7-in-1', 'ELEC-HUB-002', 28, 34.50, 'active'),
(1, 'Bluetooth Keyboard K380', 'ELEC-KB-003', 32, 42.00, 'active'),
(1, 'Webcam HD 1080p', 'ELEC-CAM-004', 15, 68.99, 'active'),
(1, 'USB Flash Drive 64GB', 'ELEC-USB-005', 120, 12.50, 'active'),

-- Office Supplies
(2, 'A4 Copy Paper 500 Sheets', 'OFF-PAPER-001', 200, 5.99, 'active'),
(2, 'Ballpoint Pen Blue (Box of 50)', 'OFF-PEN-002', 85, 8.50, 'active'),
(2, 'Sticky Notes 3x3 Yellow', 'OFF-NOTE-003', 150, 2.99, 'active'),
(2, 'File Folders Letter Size (25pk)', 'OFF-FOLD-004', 60, 12.99, 'active'),
(2, 'Desk Organizer 5 Compartments', 'OFF-ORG-005', 22, 18.75, 'active'),

-- Furniture
(3, 'Ergonomic Office Chair', 'FURN-CHAIR-001', 12, 249.99, 'active'),
(3, 'Standing Desk Adjustable', 'FURN-DESK-002', 8, 399.00, 'active'),
(3, 'Filing Cabinet 4-Drawer', 'FURN-CAB-003', 5, 189.50, 'active'),
(3, 'Bookshelf 5-Tier', 'FURN-SHELF-004', 10, 79.99, 'active'),
(3, 'Conference Table 8-Person', 'FURN-TABLE-005', 3, 650.00, 'active'),

-- Software
(4, 'Microsoft Office 365 Business', 'SOFT-MS365-001', 50, 99.99, 'active'),
(4, 'Adobe Creative Cloud License', 'SOFT-ADOBE-002', 25, 54.99, 'active'),
(4, 'Antivirus Enterprise 1-Year', 'SOFT-AV-003', 100, 35.00, 'active'),
(4, 'Project Management Tool Annual', 'SOFT-PM-004', 30, 120.00, 'active'),
(4, 'Cloud Storage 1TB Annual', 'SOFT-CLOUD-005', 75, 89.99, 'active'),

-- Hardware
(5, 'Network Switch 24-Port Gigabit', 'HARD-SWITCH-001', 6, 189.99, 'active'),
(5, 'External Hard Drive 2TB', 'HARD-HDD-002', 35, 79.99, 'active'),
(5, 'RAM DDR4 16GB Module', 'HARD-RAM-003', 40, 65.00, 'active'),
(5, 'SSD 1TB NVMe M.2', 'HARD-SSD-004', 25, 110.00, 'active'),
(5, 'UPS Battery Backup 1500VA', 'HARD-UPS-005', 8, 145.50, 'active');

-- ============================================================================
-- VERIFICATION QUERIES
-- ============================================================================
-- Uncomment these to verify the schema after import

-- SELECT COUNT(*) as total_categories FROM categories;
-- SELECT COUNT(*) as total_products FROM products;
-- SELECT c.name as category, COUNT(p.id) as product_count 
-- FROM categories c 
-- LEFT JOIN products p ON c.id = p.category_id 
-- GROUP BY c.id, c.name 
-- ORDER BY c.name;

-- ============================================================================
-- NOTES
-- ============================================================================
-- 
-- To import this schema:
-- 1. Create the database: CREATE DATABASE amenerp_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- 2. Import this file: mysql -u root -p amenerp_db < schema.sql
-- 
-- Or use phpMyAdmin:
-- 1. Select the amenerp_db database
-- 2. Go to Import tab
-- 3. Choose this schema.sql file
-- 4. Click Go
-- 
-- ============================================================================

-- Made with Bob