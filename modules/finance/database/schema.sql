-- ============================================================================
-- AmenERP Finance Module - Database Schema
-- ============================================================================
-- Description: Clean, optimized MySQL/MariaDB schema for double-entry accounting
-- Engine: InnoDB for ACID compliance and foreign key support
-- Charset: utf8mb4 for full Unicode support
-- ============================================================================

-- Drop tables if they exist (for clean reinstallation)
DROP TABLE IF EXISTS `ledger_entries`;
DROP TABLE IF EXISTS `transactions`;
DROP TABLE IF EXISTS `accounts`;

-- ============================================================================
-- Table: accounts
-- ============================================================================
-- Purpose: Tracks all financial accounts (assets, liabilities, equity, income, expenses)
-- Double-Entry Logic: Each account maintains a running balance
-- ============================================================================

CREATE TABLE `accounts` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL COMMENT 'Account name (e.g., Main Safe, CIB Bank Account)',
    `type` ENUM('asset', 'liability', 'equity', 'income', 'expense') NOT NULL COMMENT 'Account classification',
    `balance` DECIMAL(15, 2) NOT NULL DEFAULT 0.00 COMMENT 'Current account balance',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_type` (`type`),
    INDEX `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Chart of Accounts';

-- ============================================================================
-- Table: transactions
-- ============================================================================
-- Purpose: Groups related ledger entries into a single transaction
-- Double-Entry Logic: Each transaction must have balanced debits and credits
-- ============================================================================

CREATE TABLE `transactions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `description` VARCHAR(500) NOT NULL COMMENT 'Transaction description',
    `reference_number` VARCHAR(100) NULL COMMENT 'Optional reference (invoice #, receipt #, etc.)',
    `transaction_date` DATE NOT NULL COMMENT 'Date of the transaction',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_transaction_date` (`transaction_date`),
    INDEX `idx_reference_number` (`reference_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Transaction Headers';

-- ============================================================================
-- Table: ledger_entries
-- ============================================================================
-- Purpose: Individual double-entry ledger lines
-- Double-Entry Logic:
--   - Positive amount = Debit (increase for assets/expenses, decrease for liabilities/equity/income)
--   - Negative amount = Credit (decrease for assets/expenses, increase for liabilities/equity/income)
--   - Sum of all entries in a transaction must equal zero
-- ============================================================================

CREATE TABLE `ledger_entries` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `transaction_id` INT UNSIGNED NOT NULL COMMENT 'Foreign key to transactions table',
    `account_id` INT UNSIGNED NOT NULL COMMENT 'Foreign key to accounts table',
    `amount` DECIMAL(15, 2) NOT NULL COMMENT 'Positive=Debit, Negative=Credit',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_transaction_id` (`transaction_id`),
    INDEX `idx_account_id` (`account_id`),
    CONSTRAINT `fk_ledger_transaction` FOREIGN KEY (`transaction_id`) 
        REFERENCES `transactions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_ledger_account` FOREIGN KEY (`account_id`) 
        REFERENCES `accounts` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Double-Entry Ledger';

-- ============================================================================
-- SEED DATA: Basic Chart of Accounts
-- ============================================================================
-- Purpose: Provide a ready-to-use, user-friendly chart of accounts
-- Categories:
--   - Assets: Cash Safe, Bank Account
--   - Income: General Sales Income
--   - Expenses: Utilities Expense, Inventory Purchase Expense
-- ============================================================================

INSERT INTO `accounts` (`name`, `type`, `balance`) VALUES
-- Assets (what the business owns)
('Cash Safe', 'asset', 0.00),
('Bank Account', 'asset', 0.00),

-- Income (revenue streams)
('General Sales Income', 'income', 0.00),

-- Expenses (costs of doing business)
('Utilities Expense', 'expense', 0.00),
('Inventory Purchase Expense', 'expense', 0.00);

-- ============================================================================
-- VERIFICATION QUERIES (for testing)
-- ============================================================================
-- Uncomment to verify the schema after installation:
-- SELECT * FROM accounts ORDER BY type, name;
-- SELECT COUNT(*) as total_accounts FROM accounts;
-- ============================================================================

-- Made with Bob
