-- ============================================================================
-- AmenERP HR & Payroll Module - Database Schema
-- ============================================================================
-- Description: Complete database schema for HR and Payroll management
-- Engine: InnoDB for ACID compliance and foreign key support
-- Author: Senior Database Administrator
-- Created: 2026-05-16
-- ============================================================================

-- Drop tables in correct order (child tables first, then parent tables)
DROP TABLE IF EXISTS `payroll_details`;
DROP TABLE IF EXISTS `payroll_runs`;
DROP TABLE IF EXISTS `employees`;

-- ============================================================================
-- Table: employees
-- Description: Core employee master data with salary and status information
-- ============================================================================
CREATE TABLE `employees` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `employee_code` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Unique employee identifier (e.g., EMP-001)',
    `full_name` VARCHAR(255) NOT NULL COMMENT 'Employee full legal name',
    `role_title` VARCHAR(100) NOT NULL COMMENT 'Job title or position',
    `monthly_base_salary` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Base monthly salary amount',
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active' COMMENT 'Employment status',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Record last update timestamp',
    INDEX `idx_employee_code` (`employee_code`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Employee master data table';

-- ============================================================================
-- Table: payroll_runs
-- Description: Master payroll batch processing records by month
-- ============================================================================
CREATE TABLE `payroll_runs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `payroll_month` VARCHAR(7) NOT NULL COMMENT 'Payroll period in YYYY-MM format (e.g., 2026-05)',
    `total_gross` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Total gross payroll amount for the batch',
    `processed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Payroll processing timestamp',
    UNIQUE KEY `idx_payroll_month` (`payroll_month`),
    INDEX `idx_processed_at` (`processed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Payroll batch processing master records';

-- ============================================================================
-- Table: payroll_details
-- Description: Individual employee payroll line items with allowances/deductions
-- ============================================================================
CREATE TABLE `payroll_details` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `payroll_run_id` INT UNSIGNED NOT NULL COMMENT 'Reference to parent payroll batch',
    `employee_id` INT UNSIGNED NOT NULL COMMENT 'Reference to employee record',
    `base_salary` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Employee base salary for this period',
    `allowances` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Additional allowances (bonuses, overtime, etc.)',
    `deductions` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Deductions (taxes, insurance, advances, etc.)',
    `net_paid` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Final net amount paid to employee',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
    CONSTRAINT `fk_payroll_details_run` 
        FOREIGN KEY (`payroll_run_id`) 
        REFERENCES `payroll_runs`(`id`) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    CONSTRAINT `fk_payroll_details_employee` 
        FOREIGN KEY (`employee_id`) 
        REFERENCES `employees`(`id`) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE,
    INDEX `idx_payroll_run_id` (`payroll_run_id`),
    INDEX `idx_employee_id` (`employee_id`),
    INDEX `idx_created_at` (`created_at`),
    UNIQUE KEY `idx_unique_employee_run` (`payroll_run_id`, `employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Detailed payroll line items per employee';

-- ============================================================================
-- End of HR & Payroll Schema
-- ============================================================================

-- Made with Bob
