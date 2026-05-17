-- ============================================================================
-- Manufacturing & Production Module - Database Schema
-- ============================================================================
-- Description: Complete schema for Bills of Materials (BOM), BOM Items,
--              and Production Runs tracking for manufacturing operations.
-- Engine: InnoDB for ACID compliance and foreign key integrity
-- Author: Senior Database Architect
-- Date: 2026-05-17
-- ============================================================================

-- ============================================================================
-- DROP TABLES (Child tables first to respect foreign key constraints)
-- ============================================================================

DROP TABLE IF EXISTS `production_runs`;
DROP TABLE IF EXISTS `bom_items`;
DROP TABLE IF EXISTS `bills_of_materials`;

-- ============================================================================
-- TABLE: bills_of_materials (BOM Header)
-- ============================================================================
-- Purpose: Stores the master Bill of Materials records for finished products.
--          Each BOM defines what components are needed to manufacture a product.
-- ============================================================================

CREATE TABLE `bills_of_materials` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` INT UNSIGNED NOT NULL COMMENT 'Foreign key to products table - the finished product',
    `bom_code` VARCHAR(50) NOT NULL COMMENT 'Unique identifier for this BOM',
    `name` VARCHAR(255) NOT NULL COMMENT 'Descriptive name for this BOM',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_bom_code` (`bom_code`),
    KEY `idx_product_id` (`product_id`),
    
    CONSTRAINT `fk_bom_product` 
        FOREIGN KEY (`product_id`) 
        REFERENCES `products` (`id`) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Bill of Materials header table - defines finished products and their manufacturing recipes';

-- ============================================================================
-- TABLE: bom_items (BOM Ingredients/Components)
-- ============================================================================
-- Purpose: Stores the individual components (raw materials) required for each BOM.
--          Links components from the products table with required quantities.
-- ============================================================================

CREATE TABLE `bom_items` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `bom_id` INT UNSIGNED NOT NULL COMMENT 'Foreign key to bills_of_materials',
    `component_id` INT UNSIGNED NOT NULL COMMENT 'Foreign key to products table - the raw material/component',
    `quantity_required` DECIMAL(10,4) NOT NULL COMMENT 'Quantity of this component needed per unit of finished product',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    KEY `idx_bom_id` (`bom_id`),
    KEY `idx_component_id` (`component_id`),
    KEY `idx_bom_component` (`bom_id`, `component_id`),
    
    CONSTRAINT `fk_bom_items_bom` 
        FOREIGN KEY (`bom_id`) 
        REFERENCES `bills_of_materials` (`id`) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    
    CONSTRAINT `fk_bom_items_component` 
        FOREIGN KEY (`component_id`) 
        REFERENCES `products` (`id`) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='BOM line items - defines components and quantities required for manufacturing';

-- ============================================================================
-- TABLE: production_runs (Batch Tracking)
-- ============================================================================
-- Purpose: Tracks individual production batches/runs for manufacturing.
--          Records the lifecycle from pending through completion or cancellation.
-- ============================================================================

CREATE TABLE `production_runs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `bom_id` INT UNSIGNED NOT NULL COMMENT 'Foreign key to bills_of_materials',
    `batch_number` VARCHAR(50) NOT NULL COMMENT 'Unique batch identifier for tracking',
    `quantity_to_produce` INT NOT NULL COMMENT 'Number of units to manufacture in this run',
    `status` ENUM('pending', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'pending' COMMENT 'Current production status',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_batch_number` (`batch_number`),
    KEY `idx_bom_id` (`bom_id`),
    KEY `idx_status` (`status`),
    KEY `idx_created_at` (`created_at`),
    
    CONSTRAINT `fk_production_runs_bom` 
        FOREIGN KEY (`bom_id`) 
        REFERENCES `bills_of_materials` (`id`) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Production run tracking - manages manufacturing batches from start to completion';

-- ============================================================================
-- END OF SCHEMA
-- ============================================================================

-- Made with Bob
