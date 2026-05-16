-- ============================================================================
-- AmenERP Procurement Module Database Schema
-- ============================================================================
-- This schema defines the purchasing and procurement system tables.
-- Uses InnoDB engine for ACID compliance and foreign key support.
-- Integrates with inventory (products) and finance (accounts) modules.
-- ============================================================================

-- Drop tables if they exist (for clean reinstallation)
DROP TABLE IF EXISTS purchase_items;
DROP TABLE IF EXISTS purchase_orders;

-- ============================================================================
-- Table: purchase_orders
-- ============================================================================
-- Stores master purchase order records for receiving stock from suppliers.
-- Each order represents a complete procurement transaction with a unique PO number.
-- ============================================================================
CREATE TABLE purchase_orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    po_number VARCHAR(50) NOT NULL UNIQUE COMMENT 'Unique purchase order identifier',
    supplier_name VARCHAR(255) NOT NULL COMMENT 'Supplier or vendor name',
    total_amount DECIMAL(15, 2) NOT NULL DEFAULT 0.00 COMMENT 'Total purchase amount',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Order creation timestamp',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
    
    INDEX idx_po_number (po_number),
    INDEX idx_supplier_name (supplier_name),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Master purchase order records';

-- ============================================================================
-- Table: purchase_items
-- ============================================================================
-- Stores individual line items within each purchase order.
-- Each row represents one product purchased with quantity and cost details.
-- Links to products table for inventory tracking.
-- ============================================================================
CREATE TABLE purchase_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_order_id INT UNSIGNED NOT NULL COMMENT 'Foreign key to purchase_orders',
    product_id INT UNSIGNED NOT NULL COMMENT 'Foreign key to products table',
    quantity INT NOT NULL DEFAULT 1 COMMENT 'Quantity purchased',
    unit_cost DECIMAL(15, 2) NOT NULL COMMENT 'Cost per unit at time of purchase',
    line_total DECIMAL(15, 2) NOT NULL COMMENT 'Calculated line total (quantity * unit_cost)',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Item creation timestamp',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
    
    FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    
    INDEX idx_purchase_order_id (purchase_order_id),
    INDEX idx_product_id (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Individual line items within purchase orders';

-- ============================================================================
-- Initial Data / Sample Records (Optional)
-- ============================================================================
-- Uncomment below to insert sample data for testing
-- 
-- INSERT INTO purchase_orders (po_number, supplier_name, total_amount) VALUES
-- ('PO-2026-0001', 'ABC Suppliers Ltd', 5000.00),
-- ('PO-2026-0002', 'XYZ Wholesale Inc', 3250.75);
-- ============================================================================

-- Made with Bob