-- ============================================================================
-- AmenERP - Supplier Management & Accounts Payable Module
-- Database Schema Definition
-- ============================================================================
--
-- This schema defines the complete database structure for managing supplier
-- accounts and accounts payable operations. It implements a mirror system to
-- the customer receivables module, tracking what the company OWES to vendors.
--
-- Core Tables:
-- 1. suppliers: Master vendor registry with outstanding liability balances
-- 2. vendor_payments: Payment disbursement records and audit trail
--
-- Integration:
-- - Links to general ledger (accounts, transactions, ledger_entries)
-- - Extends procurement/inventory with supplier_id foreign keys
--
-- Storage Engine: InnoDB for ACID compliance and foreign key support
-- Character Set: utf8mb4 for full Unicode support
--
-- @package AmenERP\Modules\Suppliers
-- @author Bob
-- @version 1.0.0
-- ============================================================================

-- ============================================================================
-- DROP EXISTING TABLES (Child Tables First)
-- ============================================================================

-- Drop child tables first to avoid foreign key constraint violations
DROP TABLE IF EXISTS `vendor_payments`;
DROP TABLE IF EXISTS `suppliers`;

-- ============================================================================
-- TABLE: suppliers
-- ============================================================================
--
-- Master supplier/vendor registry table
-- Tracks all vendors we purchase from and our outstanding payment obligations
--
-- Key Fields:
-- - supplier_code: Unique vendor identifier (e.g., 'VND-2026-001')
-- - outstanding_balance: Amount we OWE to this supplier (liability)
-- - status: active, inactive, suspended
--
-- Business Logic:
-- - outstanding_balance INCREASES when we receive goods/services on credit
-- - outstanding_balance DECREASES when we make payments to the vendor
-- - This is the opposite of customer receivables (what they owe us)
--
CREATE TABLE `suppliers` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `supplier_code` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Unique vendor identifier (e.g., VND-2026-001)',
    `supplier_name` VARCHAR(255) NOT NULL COMMENT 'Company or vendor business name',
    `contact_name` VARCHAR(255) NULL COMMENT 'Primary contact person name',
    `contact_email` VARCHAR(255) NULL COMMENT 'Primary contact email address',
    `contact_phone` VARCHAR(50) NULL COMMENT 'Primary contact phone number',
    `address` TEXT NULL COMMENT 'Supplier physical or mailing address',
    `tax_id` VARCHAR(50) NULL COMMENT 'Tax identification number (TIN/VAT)',
    `payment_terms` VARCHAR(100) NULL DEFAULT 'Net 30' COMMENT 'Standard payment terms (e.g., Net 30, Net 60)',
    `credit_limit` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Maximum credit we can receive from supplier',
    `outstanding_balance` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Current amount we OWE to supplier (liability)',
    `status` ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active' COMMENT 'Supplier account status',
    `notes` TEXT NULL COMMENT 'Internal notes about the supplier',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last modification timestamp',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_supplier_code` (`supplier_code`),
    KEY `idx_supplier_name` (`supplier_name`),
    KEY `idx_status` (`status`),
    KEY `idx_outstanding_balance` (`outstanding_balance`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Master supplier/vendor registry with accounts payable tracking';

-- ============================================================================
-- TABLE: vendor_payments
-- ============================================================================
--
-- Payment disbursement records and audit trail
-- Tracks all payments made to suppliers to settle outstanding balances
--
-- Key Fields:
-- - disbursement_number: Unique payment reference (e.g., 'DISB-2026-0001')
-- - amount_paid: Payment amount disbursed to vendor
-- - payment_method: How payment was made (check, wire transfer, etc.)
--
-- Business Logic:
-- - Each payment DECREASES supplier outstanding_balance
-- - Each payment DECREASES our Cash account (asset)
-- - Each payment DECREASES our Accounts Payable (liability)
-- - Links to transactions table for general ledger integration
--
CREATE TABLE `vendor_payments` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `supplier_id` INT UNSIGNED NOT NULL COMMENT 'Foreign key to suppliers table',
    `disbursement_number` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Unique payment reference (e.g., DISB-2026-0001)',
    `amount_paid` DECIMAL(15,2) NOT NULL COMMENT 'Payment amount disbursed',
    `payment_method` VARCHAR(50) NOT NULL COMMENT 'Payment method (check, wire_transfer, cash, etc.)',
    `payment_reference` VARCHAR(100) NULL COMMENT 'Check number, wire confirmation, etc.',
    `notes` TEXT NULL COMMENT 'Payment notes or remarks',
    `processed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Payment processing timestamp',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last modification timestamp',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_disbursement_number` (`disbursement_number`),
    KEY `idx_supplier_id` (`supplier_id`),
    KEY `idx_processed_at` (`processed_at`),
    KEY `idx_payment_method` (`payment_method`),
    CONSTRAINT `fk_vendor_payments_supplier` 
        FOREIGN KEY (`supplier_id`) 
        REFERENCES `suppliers` (`id`) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Vendor payment disbursement records and audit trail';

-- ============================================================================
-- SAMPLE DATA (Optional - for testing)
-- ============================================================================

-- Insert sample suppliers for testing
INSERT INTO `suppliers` (
    `supplier_code`,
    `supplier_name`,
    `contact_name`,
    `contact_email`,
    `contact_phone`,
    `address`,
    `payment_terms`,
    `credit_limit`,
    `outstanding_balance`,
    `status`
) VALUES
(
    'VND-2026-001',
    'Global Tech Supplies Inc.',
    'John Anderson',
    'john.anderson@globaltechsupplies.com',
    '+1-555-0101',
    '123 Tech Boulevard, Silicon Valley, CA 94025, USA',
    'Net 30',
    50000.00,
    12500.00,
    'active'
),
(
    'VND-2026-002',
    'Premium Office Solutions',
    'Sarah Martinez',
    'sarah.martinez@premiumoffice.com',
    '+1-555-0102',
    '456 Business Park Drive, New York, NY 10001, USA',
    'Net 45',
    25000.00,
    8750.00,
    'active'
),
(
    'VND-2026-003',
    'Industrial Equipment Co.',
    'Michael Chen',
    'michael.chen@industrialequip.com',
    '+1-555-0103',
    '789 Manufacturing Lane, Detroit, MI 48201, USA',
    'Net 60',
    100000.00,
    35000.00,
    'active'
);

-- ============================================================================
-- MIGRATION PATCH: Add supplier_id to purchase_orders table
-- ============================================================================
--
-- This migration extends the procurement/inventory system to link purchase
-- orders directly to supplier accounts for integrated accounts payable tracking.
--
-- If purchase_orders table exists, add supplier_id foreign key
-- If it doesn't exist yet, this will be included in the procurement schema
--
-- Uncomment and run this when purchase_orders table is created:
--
-- ALTER TABLE `purchase_orders`
--     ADD COLUMN `supplier_id` INT UNSIGNED NULL COMMENT 'Foreign key to suppliers table' AFTER `id`,
--     ADD KEY `idx_supplier_id` (`supplier_id`),
--     ADD CONSTRAINT `fk_purchase_orders_supplier`
--         FOREIGN KEY (`supplier_id`)
--         REFERENCES `suppliers` (`id`)
--         ON DELETE RESTRICT
--         ON UPDATE CASCADE;

-- ============================================================================
-- MIGRATION PATCH: Add supplier_id to inventory products table
-- ============================================================================
--
-- This migration allows tracking the primary supplier for each inventory item
-- Useful for reordering and supplier performance analysis
--
-- Uncomment and run this when needed:
--
-- ALTER TABLE `products`
--     ADD COLUMN `supplier_id` INT UNSIGNED NULL COMMENT 'Primary supplier for this product' AFTER `category`,
--     ADD KEY `idx_supplier_id` (`supplier_id`),
--     ADD CONSTRAINT `fk_products_supplier`
--         FOREIGN KEY (`supplier_id`)
--         REFERENCES `suppliers` (`id`)
--         ON DELETE SET NULL
--         ON UPDATE CASCADE;

-- ============================================================================
-- INDEXES FOR PERFORMANCE OPTIMIZATION
-- ============================================================================

-- Additional composite indexes for common queries
CREATE INDEX `idx_suppliers_status_balance` ON `suppliers` (`status`, `outstanding_balance`);
CREATE INDEX `idx_vendor_payments_supplier_date` ON `vendor_payments` (`supplier_id`, `processed_at`);

-- ============================================================================
-- VERIFICATION QUERIES
-- ============================================================================

-- Verify table structure
-- SHOW CREATE TABLE suppliers;
-- SHOW CREATE TABLE vendor_payments;

-- Verify sample data
-- SELECT * FROM suppliers;
-- SELECT * FROM vendor_payments;

-- Check foreign key constraints
-- SELECT 
--     TABLE_NAME,
--     COLUMN_NAME,
--     CONSTRAINT_NAME,
--     REFERENCED_TABLE_NAME,
--     REFERENCED_COLUMN_NAME
-- FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
-- WHERE TABLE_SCHEMA = 'amenerp_db'
--   AND TABLE_NAME IN ('suppliers', 'vendor_payments')
--   AND REFERENCED_TABLE_NAME IS NOT NULL;

-- ============================================================================
-- END OF SCHEMA
-- ============================================================================

-- Made with Bob