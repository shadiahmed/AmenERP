-- ============================================================================
-- AmenERP - Supplier Integration Migration
-- Add supplier_id Foreign Key to Products Table
-- ============================================================================
--
-- This migration extends the inventory products table to link each product
-- to its primary supplier. This enables:
-- - Tracking which supplier provides each product
-- - Automated reordering from preferred suppliers
-- - Supplier performance analysis by product
-- - Credit purchase recording with proper supplier attribution
--
-- IMPORTANT: This migration assumes:
-- 1. The 'products' table exists (from inventory module)
-- 2. The 'suppliers' table exists (from suppliers module)
-- 3. You want to link products to their primary/preferred supplier
--
-- @package AmenERP\Modules\Suppliers
-- @author Bob
-- @version 1.0.0
-- ============================================================================

-- ============================================================================
-- STEP 1: Add supplier_id Column to Products Table
-- ============================================================================
--
-- Add the supplier_id column after the category column
-- NULL is allowed because existing products may not have a supplier assigned yet
-- After migration, you can update existing products to assign suppliers
--
ALTER TABLE `products`
    ADD COLUMN `supplier_id` INT UNSIGNED NULL 
    COMMENT 'Primary supplier for this product (foreign key to suppliers table)'
    AFTER `category_id`;

-- ============================================================================
-- STEP 2: Add Index for Performance Optimization
-- ============================================================================
--
-- Create an index on supplier_id for faster queries when:
-- - Filtering products by supplier
-- - Joining products with suppliers table
-- - Analyzing supplier product catalogs
--
ALTER TABLE `products`
    ADD KEY `idx_products_supplier_id` (`supplier_id`);

-- ============================================================================
-- STEP 3: Add Foreign Key Constraint
-- ============================================================================
--
-- Establish referential integrity between products and suppliers
-- ON DELETE RESTRICT: Prevents deletion of a supplier if products reference it
-- ON UPDATE CASCADE: Automatically updates product.supplier_id if supplier.id changes
--
ALTER TABLE `products`
    ADD CONSTRAINT `fk_products_supplier`
        FOREIGN KEY (`supplier_id`)
        REFERENCES `suppliers` (`id`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE;

-- ============================================================================
-- VERIFICATION QUERIES
-- ============================================================================
--
-- Uncomment these queries to verify the migration was successful
--

-- Check the new column structure
-- DESCRIBE products;

-- Verify the foreign key constraint was created
-- SELECT 
--     TABLE_NAME,
--     COLUMN_NAME,
--     CONSTRAINT_NAME,
--     REFERENCED_TABLE_NAME,
--     REFERENCED_COLUMN_NAME
-- FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
-- WHERE TABLE_SCHEMA = 'amenerp_db'
--   AND TABLE_NAME = 'products'
--   AND REFERENCED_TABLE_NAME = 'suppliers';

-- Count products with and without supplier assignments
-- SELECT 
--     COUNT(*) AS total_products,
--     COUNT(supplier_id) AS products_with_supplier,
--     COUNT(*) - COUNT(supplier_id) AS products_without_supplier
-- FROM products;

-- ============================================================================
-- OPTIONAL: Sample Data Update
-- ============================================================================
--
-- After running this migration, you may want to assign suppliers to existing products
-- Example update queries (uncomment and modify as needed):
--

-- Assign supplier VND-2026-001 (Global Tech Supplies) to electronics products
-- UPDATE products 
-- SET supplier_id = (SELECT id FROM suppliers WHERE supplier_code = 'VND-2026-001')
-- WHERE category_id = 1;

-- Assign supplier VND-2026-002 (Premium Office Solutions) to office supplies
-- UPDATE products 
-- SET supplier_id = (SELECT id FROM suppliers WHERE supplier_code = 'VND-2026-002')
-- WHERE category_id = 2;

-- Assign supplier VND-2026-003 (Industrial Equipment Co.) to furniture and hardware
-- UPDATE products 
-- SET supplier_id = (SELECT id FROM suppliers WHERE supplier_code = 'VND-2026-003')
-- WHERE category_id IN (3, 5);

-- ============================================================================
-- ROLLBACK INSTRUCTIONS
-- ============================================================================
--
-- If you need to rollback this migration, run these commands in reverse order:
--
-- ALTER TABLE `products` DROP FOREIGN KEY `fk_products_supplier`;
-- ALTER TABLE `products` DROP KEY `idx_products_supplier_id`;
-- ALTER TABLE `products` DROP COLUMN `supplier_id`;
--
-- ============================================================================

-- Made with Bob