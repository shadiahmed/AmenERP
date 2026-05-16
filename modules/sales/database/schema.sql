-- ============================================================================
-- AmenERP Sales Module Database Schema
-- ============================================================================
-- This schema defines the sales and invoicing system tables.
-- Uses InnoDB engine for ACID compliance and foreign key support.
-- Integrates with inventory (products) and finance (accounts) modules.
-- ============================================================================

-- Drop tables if they exist (for clean reinstallation)
DROP TABLE IF EXISTS sales_items;
DROP TABLE IF EXISTS sales_orders;

-- ============================================================================
-- Table: sales_orders
-- ============================================================================
-- Stores master invoice/sales order records.
-- Each order represents a complete sale transaction with a unique invoice number.
-- ============================================================================
CREATE TABLE sales_orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) NOT NULL UNIQUE COMMENT 'Unique invoice identifier',
    customer_name VARCHAR(255) NOT NULL COMMENT 'Customer or buyer name',
    total_amount DECIMAL(15, 2) NOT NULL DEFAULT 0.00 COMMENT 'Total invoice amount',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Order creation timestamp',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
    
    INDEX idx_invoice_number (invoice_number),
    INDEX idx_customer_name (customer_name),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Master sales order/invoice records';

-- ============================================================================
-- Table: sales_items
-- ============================================================================
-- Stores individual line items within each sales order.
-- Each row represents one product sold with quantity and price details.
-- Links to products table for inventory tracking.
-- ============================================================================
CREATE TABLE sales_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sales_order_id INT UNSIGNED NOT NULL COMMENT 'Foreign key to sales_orders',
    product_id INT UNSIGNED NOT NULL COMMENT 'Foreign key to products table',
    quantity INT NOT NULL DEFAULT 1 COMMENT 'Quantity sold',
    unit_price DECIMAL(15, 2) NOT NULL COMMENT 'Price per unit at time of sale',
    line_total DECIMAL(15, 2) NOT NULL COMMENT 'Calculated line total (quantity * unit_price)',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Item creation timestamp',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
    
    FOREIGN KEY (sales_order_id) REFERENCES sales_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    
    INDEX idx_sales_order_id (sales_order_id),
    INDEX idx_product_id (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Individual line items within sales orders';

-- ============================================================================
-- Initial Data / Sample Records (Optional)
-- ============================================================================
-- Uncomment below to insert sample data for testing
-- 
-- INSERT INTO sales_orders (invoice_number, customer_name, total_amount) VALUES
-- ('INV-2026-0001', 'John Doe', 1500.00),
-- ('INV-2026-0002', 'Jane Smith', 750.50);
-- ============================================================================

-- Made with Bob