-- ============================================================================
-- AmenERP Procurement Module - Supplier Integration Migration
-- ============================================================================
-- This migration adds supplier integration to the procurement system, enabling
-- credit purchases to be linked to registered supplier accounts.
-- 
-- Changes:
-- 1. Add supplier_id column to purchase_orders table
-- 2. Add payment_type column to track cash vs credit purchases
-- 3. Add foreign key constraint to suppliers table
-- 4. Add indexes for performance optimization
-- ============================================================================

-- Add supplier_id column (nullable for backward compatibility with existing records)
ALTER TABLE purchase_orders
ADD COLUMN supplier_id INT UNSIGNED NULL COMMENT 'Foreign key to suppliers table for credit purchases'
AFTER supplier_name;

-- Add payment_type column to distinguish cash vs credit purchases
ALTER TABLE purchase_orders
ADD COLUMN payment_type ENUM('cash', 'credit') NOT NULL DEFAULT 'cash' COMMENT 'Payment method: cash (immediate) or credit (on account)'
AFTER total_amount;

-- Add foreign key constraint to ensure referential integrity
-- ON DELETE RESTRICT prevents deletion of suppliers with active purchase orders
ALTER TABLE purchase_orders
ADD CONSTRAINT fk_purchase_orders_supplier_id
FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
ON DELETE RESTRICT
ON UPDATE CASCADE;

-- Add index on supplier_id for query performance
ALTER TABLE purchase_orders
ADD INDEX idx_supplier_id (supplier_id);

-- Add index on payment_type for filtering
ALTER TABLE purchase_orders
ADD INDEX idx_payment_type (payment_type);

-- ============================================================================
-- Data Integrity Notes:
-- ============================================================================
-- 1. Existing purchase orders will have supplier_id = NULL and payment_type = 'cash'
-- 2. New credit purchases MUST have a valid supplier_id
-- 3. Cash purchases can optionally have a supplier_id for tracking purposes
-- 4. The supplier_name column is retained for backward compatibility and audit trail
-- ============================================================================

-- Made with Bob