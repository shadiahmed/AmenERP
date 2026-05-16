-- ============================================================================
-- AmenERP Customer Accounts & Receivables Module - Database Schema
-- ============================================================================
-- Description: Clean, optimized MySQL/MariaDB schema for customer management
--              and accounts receivable tracking with full transactional support
-- Engine: InnoDB for ACID compliance and foreign key support
-- Charset: utf8mb4 for full Unicode support
-- ============================================================================

-- Set character set and collation
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- ============================================================================
-- DROP EXISTING TABLES (if any)
-- ============================================================================
-- Drop in reverse order of dependencies to avoid foreign key constraint errors

DROP TABLE IF EXISTS `customer_receipts`;
DROP TABLE IF EXISTS `customers`;

-- ============================================================================
-- Table: customers
-- ============================================================================
-- Purpose: Stores customer master data with credit management capabilities
-- Features:
--   - Unique customer codes for easy reference (e.g., CUST-001)
--   - Credit limit tracking for credit sales authorization
--   - Outstanding balance tracking for accounts receivable management
--   - Full contact information for customer relationship management
-- ============================================================================

CREATE TABLE `customers` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `customer_code` VARCHAR(50) NOT NULL COMMENT 'Unique customer identifier (e.g., CUST-001)',
    `company_name` VARCHAR(255) NOT NULL COMMENT 'Customer company or business name',
    `contact_name` VARCHAR(255) NOT NULL COMMENT 'Primary contact person name',
    `email` VARCHAR(255) NULL COMMENT 'Customer email address',
    `phone` VARCHAR(50) NULL COMMENT 'Customer phone number',
    `address` TEXT NULL COMMENT 'Customer physical address',
    `credit_limit` DECIMAL(15, 2) NOT NULL DEFAULT 0.00 COMMENT 'Maximum credit allowed for customer',
    `outstanding_balance` DECIMAL(15, 2) NOT NULL DEFAULT 0.00 COMMENT 'Current accounts receivable balance',
    `status` ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active' COMMENT 'Customer account status',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_customer_code` (`customer_code`),
    INDEX `idx_company_name` (`company_name`),
    INDEX `idx_status` (`status`),
    INDEX `idx_outstanding_balance` (`outstanding_balance`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Customer master data with credit management';

-- ============================================================================
-- Table: customer_receipts
-- ============================================================================
-- Purpose: Tracks all customer payment receipts and cash collections
-- Features:
--   - Links payments to specific customers
--   - Tracks payment methods (cash, check, bank transfer, etc.)
--   - Maintains audit trail of all payment transactions
--   - Prevents deletion of customers with payment history (ON DELETE RESTRICT)
-- ============================================================================

CREATE TABLE `customer_receipts` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `customer_id` INT UNSIGNED NOT NULL COMMENT 'Foreign key to customers table',
    `receipt_number` VARCHAR(50) NOT NULL COMMENT 'Unique receipt identifier (e.g., RCP-001)',
    `amount_paid` DECIMAL(15, 2) NOT NULL COMMENT 'Payment amount received',
    `payment_method` VARCHAR(50) NOT NULL COMMENT 'Payment method (cash, check, bank_transfer, credit_card, etc.)',
    `payment_reference` VARCHAR(100) NULL COMMENT 'Check number, transaction ID, or other reference',
    `notes` TEXT NULL COMMENT 'Additional payment notes or remarks',
    `processed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Payment processing timestamp',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_receipt_number` (`receipt_number`),
    INDEX `idx_customer_id` (`customer_id`),
    INDEX `idx_processed_at` (`processed_at`),
    INDEX `idx_payment_method` (`payment_method`),
    CONSTRAINT `fk_receipts_customer` FOREIGN KEY (`customer_id`) 
        REFERENCES `customers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Customer payment receipts and collections';

-- ============================================================================
-- ALTER EXISTING TABLE: sales_orders
-- ============================================================================
-- Purpose: Add customer linkage to existing sales orders for credit invoice tracking
-- Note: This modification allows linking sales orders to customer accounts
--       for proper accounts receivable management
-- ============================================================================

-- Add customer_id column to sales_orders if the table exists
-- This allows credit sales to be tracked against customer accounts
ALTER TABLE `sales_orders` 
ADD COLUMN `customer_id` INT UNSIGNED NULL COMMENT 'Optional link to customer for credit sales' AFTER `customer_name`,
ADD INDEX `idx_customer_id` (`customer_id`),
ADD CONSTRAINT `fk_sales_orders_customer` FOREIGN KEY (`customer_id`) 
    REFERENCES `customers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

-- ============================================================================
-- SEED DATA: Sample Customers
-- ============================================================================
-- Purpose: Provide sample customer data for testing and demonstration
-- ============================================================================

INSERT INTO `customers` (`customer_code`, `company_name`, `contact_name`, `email`, `phone`, `address`, `credit_limit`, `outstanding_balance`, `status`) VALUES
('CUST-001', 'ABC Trading Company', 'Ahmed Al-Mansouri', 'ahmed@abctrading.om', '+968-9123-4567', 'Building 123, Way 456, Al Khuwair, Muscat', 50000.00, 0.00, 'active'),
('CUST-002', 'XYZ Enterprises LLC', 'Fatima Al-Balushi', 'fatima@xyzenterprises.om', '+968-9234-5678', 'Office 45, Al Qurum Commercial Center, Muscat', 75000.00, 0.00, 'active'),
('CUST-003', 'Global Solutions WLL', 'Mohammed Al-Harthi', 'mohammed@globalsolutions.om', '+968-9345-6789', 'Tower 2, Floor 5, Muscat Business District', 100000.00, 0.00, 'active'),
('CUST-004', 'Tech Innovations Co.', 'Sara Al-Kindi', 'sara@techinnovations.om', '+968-9456-7890', 'Building 789, Al Ghubra Industrial Area, Muscat', 30000.00, 0.00, 'active'),
('CUST-005', 'Retail Masters LLC', 'Ali Al-Rawahi', 'ali@retailmasters.om', '+968-9567-8901', 'Shop 12, Muscat Grand Mall, Seeb', 25000.00, 0.00, 'active');

-- ============================================================================
-- VERIFICATION QUERIES (for testing)
-- ============================================================================
-- Uncomment to verify the schema after installation:
-- 
-- SELECT * FROM customers ORDER BY customer_code;
-- SELECT COUNT(*) as total_customers FROM customers;
-- SELECT SUM(credit_limit) as total_credit_extended FROM customers WHERE status = 'active';
-- SELECT SUM(outstanding_balance) as total_receivables FROM customers;
-- 
-- ============================================================================

-- ============================================================================
-- NOTES
-- ============================================================================
-- 
-- To import this schema:
-- 1. Ensure the main database exists: amenerp_db
-- 2. Ensure the sales_orders table exists (from sales module)
-- 3. Import this file: mysql -u root -p amenerp_db < modules/customers/database/schema.sql
-- 
-- Or use phpMyAdmin:
-- 1. Select the amenerp_db database
-- 2. Go to Import tab
-- 3. Choose this schema.sql file
-- 4. Click Go
-- 
-- Foreign Key Dependencies:
-- - customer_receipts depends on customers
-- - sales_orders.customer_id depends on customers (optional link)
-- 
-- ============================================================================

-- Made with Bob