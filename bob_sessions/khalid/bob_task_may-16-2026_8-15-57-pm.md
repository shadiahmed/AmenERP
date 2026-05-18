**User:**

<task>
Act as a Senior Database Administrator. Write a clean, standalone database schema script for our new Customer Accounts & Receivables module to be located at `modules/customers/database/schema.sql`.

Requirements:
1. Use the InnoDB engine for full transactional ACID safety and strict foreign key relationships.
2. Include standard `DROP TABLE IF EXISTS` logic for clean installation testing (drop child tables before parent tables).
3. Define two main database structures:
   - `customers`: Primary key `id` (INT UNSIGNED AUTO_INCREMENT), `customer_code` (VARCHAR(50) UNIQUE, e.g., CUST-001), `company_name` (VARCHAR(255)), `contact_name` (VARCHAR(255)), `credit_limit` (DECIMAL(15,2) DEFAULT 0.00), `outstanding_balance` (DECIMAL(15,2) DEFAULT 0.00), along with standard `created_at` and `updated_at` timestamps.
   - `customer_receipts`: Primary key `id` (INT UNSIGNED AUTO_INCREMENT), links to `customers(id)` ON DELETE RESTRICT. Store `receipt_number` (VARCHAR(50) UNIQUE), `amount_paid` (DECIMAL(15,2)), `payment_method` (VARCHAR(50)), and `processed_at` (TIMESTAMP DEFAULT CURRENT_TIMESTAMP).
4. Modify our existing `sales_orders` concept contextually by adding an optional `customer_id` column (INT UNSIGNED NULL) to link credit invoices back to specific customer profiles.
5. Add high-performance database indexes on foreign keys, lookups, and `customer_code`.

Provide the complete file cleanly without truncation or text placeholders.

Act as a Principal Software Engineer. Write the complete, strict business logic model at `modules/customers/models/CustomerModel.php`.

Requirements:
1. Ensure strict type declarations (`declare(strict_types=1);`) and robust documentation.
2. Initialize using our native `Database::getInstance()` connection wrapper.
3. Define our global corporate account constants at the class head:
   - `ACCOUNTS_RECEIVABLE_ID = 5` (Our Accounts Receivable Asset account mapping)
   - `CASH_ACCOUNT_ID = 1` (Our primary Cash Liquid Asset account source)
4. Implement a comprehensive transactional method: `public function recordCustomerPayment(int $customerId, float $amount, string $method): array` wrapped inside a single PDO database transaction block.
5. Inside the transaction logic:
   - Validate that the payment `$amount` is strictly greater than zero and does not exceed the customer's current `outstanding_balance`.
   - Update the `customers` table by DECREASING the `outstanding_balance` asset layer (`outstanding_balance = outstanding_balance - :amount`).
   - Insert an tracking entry into the `customer_receipts` table.
   - Inject a financial row into the system's `transactions` log and create matching journal rows in `ledger_entries`: DEBIT the Cash Safe account (positive asset cash increment) and CREDIT the Accounts Receivable account (reducing outstanding debt assets) by the payment total.
   - Dynamically update the running balances directly inside the central `accounts` table.
6. Provide helper methods: `getAllCustomers()`, `getCustomerStatement(int $customerId)`, and `verifyCreditAvailability(int $customerId, float $orderTotal)`.

Provide the complete file cleanly. Do not truncate the code.@/project-rules.md
</task>
<environment_details>
# VSCode Visible Files
modules\home\INTEGRATION_GUIDE.md

# VSCode Open Tabs
modules/home/models/DashboardModel.php,public/assets/css/dashboard.css,modules/home/index.php,modules/home/ROUTING.md,modules/home/INTEGRATION_GUIDE.md,public/index.php

# Current Time
Current time in ISO 8601 UTC format: 2026-05-16T16:10:37.997Z
User time zone: Asia/Muscat, UTC+4:00

# Current Cost
$0.00

# Review Exclusions
The following file patterns are excluded from code reviews:
- .vscode/**

# Current Mode
<slug>code</slug>
<name>💻 Code</name>


# Current Workspace Directory (c:/xampp/htdocs/AmenERP) Files
LICENSE
project-rules.md
README.md
schema.sql
setup-unix.sh
setup-windows.ps1
config/
config/config.php
core/
core/Csrf.php
core/Database.php
core/Router.php
modules/
modules/finance/
modules/finance/index.php
modules/finance/controllers/
modules/finance/controllers/RecordExpenseController.php
modules/finance/controllers/RecordIncomeController.php
modules/finance/database/
modules/finance/database/schema.sql
modules/finance/models/
modules/finance/models/FinanceModel.php
modules/finance/models/ReportModel.php
modules/home/
modules/home/index.php
modules/home/INTEGRATION_GUIDE.md
modules/home/ROUTING.md
modules/home/models/
modules/home/models/DashboardModel.php
modules/hr/
modules/hr/index.php
modules/hr/controllers/
modules/hr/controllers/ProcessPayrollController.php
modules/hr/database/
modules/hr/database/schema.sql
modules/hr/models/
modules/hr/models/HRModel.php
modules/inventory/
modules/inventory/add.php
modules/inventory/index.php
modules/inventory/api/
modules/inventory/api/check-sku.php
modules/inventory/api/search.php
modules/inventory/controllers/
modules/inventory/controllers/AddProductController.php
modules/inventory/controllers/DeleteProductController.php
modules/inventory/controllers/EditProductController.php
modules/inventory/database/
modules/inventory/database/schema.sql
modules/inventory/models/
modules/inventory/models/InventoryModel.php
modules/procurement/
modules/procurement/index.php
modules/procurement/controllers/
modules/procurement/controllers/ProcessPurchaseController.php
modules/procurement/database/
modules/procurement/database/schema.sql
modules/procurement/models/
modules/procurement/models/ProcurementModel.php
modules/sales/
modules/sales/index.php
modules/sales/INTEGRATION_GUIDE.md
modules/sales/README.md
modules/sales/ROUTING.md
modules/sales/controllers/
modules/sales/controllers/ProcessSaleController.php
modules/sales/controllers/ViewInvoiceController.php
modules/sales/database/
modules/sales/database/schema.sql
modules/sales/models/
modules/sales/models/SalesModel.php
public/
public/.htaccess
public/index.php
public/assets/
public/assets/css/
public/assets/css/dashboard.css
public/assets/css/main.css
public/assets/css/style.css
public/assets/js/
public/assets/js/hr-payroll.js
public/assets/js/main.js
You have not created a todo list yet. Create one with `update_todo_list` if your task is complicated or involves multiple steps.
</environment_details>

---

**Assistant:**

I'll help you create a comprehensive Customer Accounts & Receivables module with a clean database schema and robust business logic model. Let me start by creating the necessary directory structure and files.

<update_todo_list>
<todos>
[ ] Create modules/customers/database directory structure
[ ] Write database schema script (schema.sql)
[ ] Write CustomerModel.php with transactional payment logic
[ ] Verify all files are complete and properly formatted
</todos>
</update_todo_list>

---

**User:**

[update_todo_list] Result:
Todo list updated successfully.
<environment_details>
# VSCode Visible Files
modules\home\INTEGRATION_GUIDE.md

# VSCode Open Tabs
modules/home/models/DashboardModel.php,public/assets/css/dashboard.css,modules/home/index.php,modules/home/ROUTING.md,modules/home/INTEGRATION_GUIDE.md,public/index.php

# Current Time
Current time in ISO 8601 UTC format: 2026-05-16T16:11:01.143Z
User time zone: Asia/Muscat, UTC+4:00

# Current Cost
$0.01

# Review Exclusions
The following file patterns are excluded from code reviews:
- .vscode/**

# Current Mode
<slug>code</slug>
<name>💻 Code</name>
====

REMINDERS

Below is your current list of reminders for this task. Keep them updated as you progress.

| # | Content | Status |
|---|---------|--------|
| 1 | Create modules/customers/database directory structure | Pending |
| 2 | Write database schema script (schema.sql) | Pending |
| 3 | Write CustomerModel.php with transactional payment logic | Pending |
| 4 | Verify all files are complete and properly formatted | Pending |


IMPORTANT: When task status changes, remember to call the `update_todo_list` tool to update your progress.

</environment_details>

---

**Assistant:**

I'll start by creating the database schema file. First, let me check the existing database structure to ensure consistency with the project's conventions.

<read_file>
<args>
<file>
<path>schema.sql</path>
</file>
<file>
<path>modules/finance/database/schema.sql</path>
</file>
<file>
<path>modules/sales/database/schema.sql</path>
</file>
</args>
</read_file>

---

**User:**

[read_file for 'schema.sql', 'modules/finance/database/schema.sql', 'modules/sales/database/schema.sql'] Result:
<files>
<file><path>schema.sql</path>
<content lines="1-151">
  1 | -- ============================================================================
  2 | -- AmenERP Database Schema
  3 | -- ============================================================================
  4 | -- 
  5 | -- This schema initializes the AmenERP database with core tables for inventory
  6 | -- management. Following project rules:
  7 | -- - InnoDB engine for ACID compliance and foreign key support
  8 | -- - created_at and updated_at timestamps on all tables
  9 | -- - utf8mb4 charset for full Unicode support
 10 | -- - Proper indexing for performance
 11 | -- 
 12 | -- @package AmenERP
 13 | -- @author Bob
 14 | -- @version 1.0.0
 15 | -- ============================================================================
 16 | 
 17 | -- Set character set and collation
 18 | SET NAMES utf8mb4;
 19 | SET CHARACTER SET utf8mb4;
 20 | 
 21 | -- ============================================================================
 22 | -- DROP EXISTING TABLES (if any)
 23 | -- ============================================================================
 24 | -- Drop in reverse order of dependencies to avoid foreign key constraint errors
 25 | 
 26 | DROP TABLE IF EXISTS `products`;
 27 | DROP TABLE IF EXISTS `categories`;
 28 | 
 29 | -- ============================================================================
 30 | -- CATEGORIES TABLE
 31 | -- ============================================================================
 32 | -- Stores product categories for inventory organization
 33 | 
 34 | CREATE TABLE `categories` (
 35 |   `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
 36 |   `name` VARCHAR(100) NOT NULL,
 37 |   `description` TEXT NULL,
 38 |   `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 39 |   `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 40 |   PRIMARY KEY (`id`),
 41 |   UNIQUE KEY `uk_categories_name` (`name`),
 42 |   KEY `idx_categories_created_at` (`created_at`)
 43 | ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Product categories for inventory management';
 44 | 
 45 | -- ============================================================================
 46 | -- PRODUCTS TABLE
 47 | -- ============================================================================
 48 | -- Stores product inventory with category relationships
 49 | 
 50 | CREATE TABLE `products` (
 51 |   `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
 52 |   `category_id` INT UNSIGNED NOT NULL,
 53 |   `name` VARCHAR(200) NOT NULL,
 54 |   `sku` VARCHAR(50) NOT NULL,
 55 |   `quantity` INT NOT NULL DEFAULT 0,
 56 |   `unit_price` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
 57 |   `status` ENUM('active', 'inactive', 'discontinued') NOT NULL DEFAULT 'active',
 58 |   `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 59 |   `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 60 |   PRIMARY KEY (`id`),
 61 |   UNIQUE KEY `uk_products_sku` (`sku`),
 62 |   KEY `idx_products_category_id` (`category_id`),
 63 |   KEY `idx_products_status` (`status`),
 64 |   KEY `idx_products_name` (`name`),
 65 |   KEY `idx_products_created_at` (`created_at`),
 66 |   CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
 67 | ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Product inventory with pricing and stock levels';
 68 | 
 69 | -- ============================================================================
 70 | -- SEED DATA - CATEGORIES
 71 | -- ============================================================================
 72 | -- Initial categories to populate the dashboard
 73 | 
 74 | INSERT INTO `categories` (`name`, `description`) VALUES
 75 | ('Electronics', 'Electronic devices, components, and accessories including computers, phones, and peripherals'),
 76 | ('Office Supplies', 'General office supplies including stationery, paper products, and desk accessories'),
 77 | ('Furniture', 'Office and commercial furniture including desks, chairs, and storage solutions'),
 78 | ('Software', 'Software licenses, subscriptions, and digital products'),
 79 | ('Hardware', 'Computer hardware components, networking equipment, and IT infrastructure');
 80 | 
 81 | -- ============================================================================
 82 | -- SEED DATA - PRODUCTS
 83 | -- ============================================================================
 84 | -- Sample products to demonstrate the inventory system
 85 | 
 86 | INSERT INTO `products` (`category_id`, `name`, `sku`, `quantity`, `unit_price`, `status`) VALUES
 87 | -- Electronics
 88 | (1, 'Wireless Mouse Logitech M185', 'ELEC-MOUSE-001', 45, 15.99, 'active'),
 89 | (1, 'USB-C Hub 7-in-1', 'ELEC-HUB-002', 28, 34.50, 'active'),
 90 | (1, 'Bluetooth Keyboard K380', 'ELEC-KB-003', 32, 42.00, 'active'),
 91 | (1, 'Webcam HD 1080p', 'ELEC-CAM-004', 15, 68.99, 'active'),
 92 | (1, 'USB Flash Drive 64GB', 'ELEC-USB-005', 120, 12.50, 'active'),
 93 | 
 94 | -- Office Supplies
 95 | (2, 'A4 Copy Paper 500 Sheets', 'OFF-PAPER-001', 200, 5.99, 'active'),
 96 | (2, 'Ballpoint Pen Blue (Box of 50)', 'OFF-PEN-002', 85, 8.50, 'active'),
 97 | (2, 'Sticky Notes 3x3 Yellow', 'OFF-NOTE-003', 150, 2.99, 'active'),
 98 | (2, 'File Folders Letter Size (25pk)', 'OFF-FOLD-004', 60, 12.99, 'active'),
 99 | (2, 'Desk Organizer 5 Compartments', 'OFF-ORG-005', 22, 18.75, 'active'),
100 | 
101 | -- Furniture
102 | (3, 'Ergonomic Office Chair', 'FURN-CHAIR-001', 12, 249.99, 'active'),
103 | (3, 'Standing Desk Adjustable', 'FURN-DESK-002', 8, 399.00, 'active'),
104 | (3, 'Filing Cabinet 4-Drawer', 'FURN-CAB-003', 5, 189.50, 'active'),
105 | (3, 'Bookshelf 5-Tier', 'FURN-SHELF-004', 10, 79.99, 'active'),
106 | (3, 'Conference Table 8-Person', 'FURN-TABLE-005', 3, 650.00, 'active'),
107 | 
108 | -- Software
109 | (4, 'Microsoft Office 365 Business', 'SOFT-MS365-001', 50, 99.99, 'active'),
110 | (4, 'Adobe Creative Cloud License', 'SOFT-ADOBE-002', 25, 54.99, 'active'),
111 | (4, 'Antivirus Enterprise 1-Year', 'SOFT-AV-003', 100, 35.00, 'active'),
112 | (4, 'Project Management Tool Annual', 'SOFT-PM-004', 30, 120.00, 'active'),
113 | (4, 'Cloud Storage 1TB Annual', 'SOFT-CLOUD-005', 75, 89.99, 'active'),
114 | 
115 | -- Hardware
116 | (5, 'Network Switch 24-Port Gigabit', 'HARD-SWITCH-001', 6, 189.99, 'active'),
117 | (5, 'External Hard Drive 2TB', 'HARD-HDD-002', 35, 79.99, 'active'),
118 | (5, 'RAM DDR4 16GB Module', 'HARD-RAM-003', 40, 65.00, 'active'),
119 | (5, 'SSD 1TB NVMe M.2', 'HARD-SSD-004', 25, 110.00, 'active'),
120 | (5, 'UPS Battery Backup 1500VA', 'HARD-UPS-005', 8, 145.50, 'active');
121 | 
122 | -- ============================================================================
123 | -- VERIFICATION QUERIES
124 | -- ============================================================================
125 | -- Uncomment these to verify the schema after import
126 | 
127 | -- SELECT COUNT(*) as total_categories FROM categories;
128 | -- SELECT COUNT(*) as total_products FROM products;
129 | -- SELECT c.name as category, COUNT(p.id) as product_count 
130 | -- FROM categories c 
131 | -- LEFT JOIN products p ON c.id = p.category_id 
132 | -- GROUP BY c.id, c.name 
133 | -- ORDER BY c.name;
134 | 
135 | -- ============================================================================
136 | -- NOTES
137 | -- ============================================================================
138 | -- 
139 | -- To import this schema:
140 | -- 1. Create the database: CREATE DATABASE amenerp_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
141 | -- 2. Import this file: mysql -u root -p amenerp_db < schema.sql
142 | -- 
143 | -- Or use phpMyAdmin:
144 | -- 1. Select the amenerp_db database
145 | -- 2. Go to Import tab
146 | -- 3. Choose this schema.sql file
147 | -- 4. Click Go
148 | -- 
149 | -- ============================================================================
150 | 
151 | -- Made with Bob
</content>
</file>
<file><path>modules/finance/database/schema.sql</path>
<content lines="1-108">
  1 | -- ============================================================================
  2 | -- AmenERP Finance Module - Database Schema
  3 | -- ============================================================================
  4 | -- Description: Clean, optimized MySQL/MariaDB schema for double-entry accounting
  5 | -- Engine: InnoDB for ACID compliance and foreign key support
  6 | -- Charset: utf8mb4 for full Unicode support
  7 | -- ============================================================================
  8 | 
  9 | -- Drop tables if they exist (for clean reinstallation)
 10 | DROP TABLE IF EXISTS `ledger_entries`;
 11 | DROP TABLE IF EXISTS `transactions`;
 12 | DROP TABLE IF EXISTS `accounts`;
 13 | 
 14 | -- ============================================================================
 15 | -- Table: accounts
 16 | -- ============================================================================
 17 | -- Purpose: Tracks all financial accounts (assets, liabilities, equity, income, expenses)
 18 | -- Double-Entry Logic: Each account maintains a running balance
 19 | -- ============================================================================
 20 | 
 21 | CREATE TABLE `accounts` (
 22 |     `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
 23 |     `name` VARCHAR(255) NOT NULL COMMENT 'Account name (e.g., Main Safe, CIB Bank Account)',
 24 |     `type` ENUM('asset', 'liability', 'equity', 'income', 'expense') NOT NULL COMMENT 'Account classification',
 25 |     `balance` DECIMAL(15, 2) NOT NULL DEFAULT 0.00 COMMENT 'Current account balance',
 26 |     `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 27 |     `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 28 |     PRIMARY KEY (`id`),
 29 |     INDEX `idx_type` (`type`),
 30 |     INDEX `idx_name` (`name`)
 31 | ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Chart of Accounts';
 32 | 
 33 | -- ============================================================================
 34 | -- Table: transactions
 35 | -- ============================================================================
 36 | -- Purpose: Groups related ledger entries into a single transaction
 37 | -- Double-Entry Logic: Each transaction must have balanced debits and credits
 38 | -- ============================================================================
 39 | 
 40 | CREATE TABLE `transactions` (
 41 |     `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
 42 |     `description` VARCHAR(500) NOT NULL COMMENT 'Transaction description',
 43 |     `reference_number` VARCHAR(100) NULL COMMENT 'Optional reference (invoice #, receipt #, etc.)',
 44 |     `transaction_date` DATE NOT NULL COMMENT 'Date of the transaction',
 45 |     `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 46 |     `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 47 |     PRIMARY KEY (`id`),
 48 |     INDEX `idx_transaction_date` (`transaction_date`),
 49 |     INDEX `idx_reference_number` (`reference_number`)
 50 | ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Transaction Headers';
 51 | 
 52 | -- ============================================================================
 53 | -- Table: ledger_entries
 54 | -- ============================================================================
 55 | -- Purpose: Individual double-entry ledger lines
 56 | -- Double-Entry Logic:
 57 | --   - Positive amount = Debit (increase for assets/expenses, decrease for liabilities/equity/income)
 58 | --   - Negative amount = Credit (decrease for assets/expenses, increase for liabilities/equity/income)
 59 | --   - Sum of all entries in a transaction must equal zero
 60 | -- ============================================================================
 61 | 
 62 | CREATE TABLE `ledger_entries` (
 63 |     `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
 64 |     `transaction_id` INT UNSIGNED NOT NULL COMMENT 'Foreign key to transactions table',
 65 |     `account_id` INT UNSIGNED NOT NULL COMMENT 'Foreign key to accounts table',
 66 |     `amount` DECIMAL(15, 2) NOT NULL COMMENT 'Positive=Debit, Negative=Credit',
 67 |     `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 68 |     `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 69 |     PRIMARY KEY (`id`),
 70 |     INDEX `idx_transaction_id` (`transaction_id`),
 71 |     INDEX `idx_account_id` (`account_id`),
 72 |     CONSTRAINT `fk_ledger_transaction` FOREIGN KEY (`transaction_id`) 
 73 |         REFERENCES `transactions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
 74 |     CONSTRAINT `fk_ledger_account` FOREIGN KEY (`account_id`) 
 75 |         REFERENCES `accounts` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
 76 | ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Double-Entry Ledger';
 77 | 
 78 | -- ============================================================================
 79 | -- SEED DATA: Basic Chart of Accounts
 80 | -- ============================================================================
 81 | -- Purpose: Provide a ready-to-use, user-friendly chart of accounts
 82 | -- Categories:
 83 | --   - Assets: Cash Safe, Bank Account
 84 | --   - Income: General Sales Income
 85 | --   - Expenses: Utilities Expense, Inventory Purchase Expense
 86 | -- ============================================================================
 87 | 
 88 | INSERT INTO `accounts` (`name`, `type`, `balance`) VALUES
 89 | -- Assets (what the business owns)
 90 | ('Cash Safe', 'asset', 0.00),
 91 | ('Bank Account', 'asset', 0.00),
 92 | 
 93 | -- Income (revenue streams)
 94 | ('General Sales Income', 'income', 0.00),
 95 | 
 96 | -- Expenses (costs of doing business)
 97 | ('Utilities Expense', 'expense', 0.00),
 98 | ('Inventory Purchase Expense', 'expense', 0.00);
 99 | 
100 | -- ============================================================================
101 | -- VERIFICATION QUERIES (for testing)
102 | -- ============================================================================
103 | -- Uncomment to verify the schema after installation:
104 | -- SELECT * FROM accounts ORDER BY type, name;
105 | -- SELECT COUNT(*) as total_accounts FROM accounts;
106 | -- ============================================================================
107 | 
108 | -- Made with Bob
</content>
</file>
<file><path>modules/sales/database/schema.sql</path>
<content lines="1-66">
 1 | -- ============================================================================
 2 | -- AmenERP Sales Module Database Schema
 3 | -- ============================================================================
 4 | -- This schema defines the sales and invoicing system tables.
 5 | -- Uses InnoDB engine for ACID compliance and foreign key support.
 6 | -- Integrates with inventory (products) and finance (accounts) modules.
 7 | -- ============================================================================
 8 | 
 9 | -- Drop tables if they exist (for clean reinstallation)
10 | DROP TABLE IF EXISTS sales_items;
11 | DROP TABLE IF EXISTS sales_orders;
12 | 
13 | -- ============================================================================
14 | -- Table: sales_orders
15 | -- ============================================================================
16 | -- Stores master invoice/sales order records.
17 | -- Each order represents a complete sale transaction with a unique invoice number.
18 | -- ============================================================================
19 | CREATE TABLE sales_orders (
20 |     id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
21 |     invoice_number VARCHAR(50) NOT NULL UNIQUE COMMENT 'Unique invoice identifier',
22 |     customer_name VARCHAR(255) NOT NULL COMMENT 'Customer or buyer name',
23 |     total_amount DECIMAL(15, 2) NOT NULL DEFAULT 0.00 COMMENT 'Total invoice amount',
24 |     created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Order creation timestamp',
25 |     updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
26 |     
27 |     INDEX idx_invoice_number (invoice_number),
28 |     INDEX idx_customer_name (customer_name),
29 |     INDEX idx_created_at (created_at)
30 | ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Master sales order/invoice records';
31 | 
32 | -- ============================================================================
33 | -- Table: sales_items
34 | -- ============================================================================
35 | -- Stores individual line items within each sales order.
36 | -- Each row represents one product sold with quantity and price details.
37 | -- Links to products table for inventory tracking.
38 | -- ============================================================================
39 | CREATE TABLE sales_items (
40 |     id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
41 |     sales_order_id INT UNSIGNED NOT NULL COMMENT 'Foreign key to sales_orders',
42 |     product_id INT UNSIGNED NOT NULL COMMENT 'Foreign key to products table',
43 |     quantity INT NOT NULL DEFAULT 1 COMMENT 'Quantity sold',
44 |     unit_price DECIMAL(15, 2) NOT NULL COMMENT 'Price per unit at time of sale',
45 |     line_total DECIMAL(15, 2) NOT NULL COMMENT 'Calculated line total (quantity * unit_price)',
46 |     created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Item creation timestamp',
47 |     updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
48 |     
49 |     FOREIGN KEY (sales_order_id) REFERENCES sales_orders(id) ON DELETE CASCADE,
50 |     FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
51 |     
52 |     INDEX idx_sales_order_id (sales_order_id),
53 |     INDEX idx_product_id (product_id)
54 | ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Individual line items within sales orders';
55 | 
56 | -- ============================================================================
57 | -- Initial Data / Sample Records (Optional)
58 | -- ============================================================================
59 | -- Uncomment below to insert sample data for testing
60 | -- 
61 | -- INSERT INTO sales_orders (invoice_number, customer_name, total_amount) VALUES
62 | -- ('INV-2026-0001', 'John Doe', 1500.00),
63 | -- ('INV-2026-0002', 'Jane Smith', 750.50);
64 | -- ============================================================================
65 | 
66 | -- Made with Bob
</content>
</file>
</files>
<environment_details>
# VSCode Visible Files
modules\home\INTEGRATION_GUIDE.md

# VSCode Open Tabs
modules/home/models/DashboardModel.php,public/assets/css/dashboard.css,modules/home/index.php,modules/home/ROUTING.md,modules/home/INTEGRATION_GUIDE.md,public/index.php

# Current Time
Current time in ISO 8601 UTC format: 2026-05-16T16:11:10.368Z
User time zone: Asia/Muscat, UTC+4:00

# Current Cost
$0.02

# Review Exclusions
The following file patterns are excluded from code reviews:
- .vscode/**

# Current Mode
<slug>code</slug>
<name>💻 Code</name>
====

REMINDERS

Below is your current list of reminders for this task. Keep them updated as you progress.

| # | Content | Status |
|---|---------|--------|
| 1 | Create modules/customers/database directory structure | Pending |
| 2 | Write database schema script (schema.sql) | Pending |
| 3 | Write CustomerModel.php with transactional payment logic | Pending |
| 4 | Verify all files are complete and properly formatted | Pending |


IMPORTANT: When task status changes, remember to call the `update_todo_list` tool to update your progress.

</environment_details>

---

**Assistant:**

Now I'll create the complete database schema for the customers module. Let me write the schema.sql file:

<write_to_file>
<path>modules/customers/database/schema.sql</path>
<content>
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
</content>
<line_count>145</line_count>
</write_to_file>

---

**User:**

[write_to_file for 'modules/customers/database/schema.sql'] Result:
<file_write_result>
<path>modules/customers/database/schema.sql</path>
<operation>created</operation>
<notice>
<i>You do not need to re-read the file, as you have seen all changes</i>
<i>Proceed with the task using these changes as the new baseline.</i>
</notice>
</file_write_result>

<environment_details>
# VSCode Visible Files
modules\customers\database\schema.sql

# VSCode Open Tabs
modules/home/models/DashboardModel.php,public/assets/css/dashboard.css,modules/home/index.php,modules/home/ROUTING.md,modules/home/INTEGRATION_GUIDE.md,modules/customers/database/schema.sql,public/index.php

# Current Time
Current time in ISO 8601 UTC format: 2026-05-16T16:11:44.055Z
User time zone: Asia/Muscat, UTC+4:00

# Current Cost
$0.06

# Review Exclusions
The following file patterns are excluded from code reviews:
- .vscode/**

# Current Mode
<slug>code</slug>
<name>💻 Code</name>
====

REMINDERS

Below is your current list of reminders for this task. Keep them updated as you progress.

| # | Content | Status |
|---|---------|--------|
| 1 | Create modules/customers/database directory structure | Pending |
| 2 | Write database schema script (schema.sql) | Pending |
| 3 | Write CustomerModel.php with transactional payment logic | Pending |
| 4 | Verify all files are complete and properly formatted | Pending |


IMPORTANT: When task status changes, remember to call the `update_todo_list` tool to update your progress.

</environment_details>

---

**Assistant:**

<update_todo_list>
<todos>
[x] Create modules/customers/database directory structure
[x] Write database schema script (schema.sql)
[-] Write CustomerModel.php with transactional payment logic
[ ] Verify all files are complete and properly formatted
</todos>
</update_todo_list>

---

**User:**

[update_todo_list] Result:
Todo list updated successfully.
<environment_details>
# VSCode Visible Files
modules\customers\database\schema.sql

# VSCode Open Tabs
modules/home/models/DashboardModel.php,public/assets/css/dashboard.css,modules/home/index.php,modules/home/ROUTING.md,modules/home/INTEGRATION_GUIDE.md,modules/customers/database/schema.sql,public/index.php

# Current Time
Current time in ISO 8601 UTC format: 2026-05-16T16:11:49.833Z
User time zone: Asia/Muscat, UTC+4:00

# Current Cost
$0.09

# Review Exclusions
The following file patterns are excluded from code reviews:
- .vscode/**

# Current Mode
<slug>code</slug>
<name>💻 Code</name>
====

REMINDERS

Below is your current list of reminders for this task. Keep them updated as you progress.

| # | Content | Status |
|---|---------|--------|
| 1 | Create modules/customers/database directory structure | Completed |
| 2 | Write database schema script (schema.sql) | Completed |
| 3 | Write CustomerModel.php with transactional payment logic | In Progress |
| 4 | Verify all files are complete and properly formatted | Pending |


IMPORTANT: When task status changes, remember to call the `update_todo_list` tool to update your progress.

</environment_details>

---

**Assistant:**

Now I'll examine the existing model files to understand the project's coding patterns and conventions before creating the CustomerModel.php:

<read_file>
<args>
<file>
<path>modules/finance/models/FinanceModel.php</path>
</file>
<file>
<path>modules/sales/models/SalesModel.php</path>
</file>
<file>
<path>core/Database.php</path>
</file>
</args>
</read_file>

---

**User:**

[read_file for 'modules/finance/models/FinanceModel.php', 'modules/sales/models/SalesModel.php', 'core/Database.php'] Result:
<files>
<file><path>modules/finance/models/FinanceModel.php</path>
<content lines="1-382">
  1 | <?php
  2 | 
  3 | declare(strict_types=1);
  4 | 
  5 | /**
  6 |  * FinanceModel Class
  7 |  * 
  8 |  * Handles all database operations for the finance module.
  9 |  * Implements double-entry bookkeeping with ACID-compliant transactions.
 10 |  * All queries use PDO prepared statements for security.
 11 |  * 
 12 |  * @package AmenERP\Modules\Finance
 13 |  * @author Bob
 14 |  * @version 1.0.0
 15 |  */
 16 | class FinanceModel
 17 | {
 18 |     /**
 19 |      * Database instance
 20 |      * 
 21 |      * @var Database
 22 |      */
 23 |     private Database $db;
 24 | 
 25 |     /**
 26 |      * Constructor
 27 |      * Initializes the database connection using singleton pattern
 28 |      */
 29 |     public function __construct()
 30 |     {
 31 |         $this->db = Database::getInstance();
 32 |     }
 33 | 
 34 |     /**
 35 |      * Get all financial accounts categorized by their type
 36 |      * 
 37 |      * Returns all accounts organized by type (asset, liability, equity, income, expense).
 38 |      * Useful for displaying the chart of accounts and financial reports.
 39 |      * 
 40 |      * @return array<string, array<int, array<string, mixed>>> Accounts grouped by type
 41 |      * @throws PDOException If query fails
 42 |      * 
 43 |      * @example
 44 |      * $model = new FinanceModel();
 45 |      * $accounts = $model->getAccounts();
 46 |      * foreach ($accounts['asset'] as $account) {
 47 |      *     echo $account['name'] . ': ' . $account['balance'];
 48 |      * }
 49 |      */
 50 |     public function getAccounts(): array
 51 |     {
 52 |         $sql = "
 53 |             SELECT 
 54 |                 id,
 55 |                 name,
 56 |                 type,
 57 |                 balance,
 58 |                 created_at,
 59 |                 updated_at
 60 |             FROM accounts
 61 |             ORDER BY type ASC, name ASC
 62 |         ";
 63 | 
 64 |         $stmt = $this->db->query($sql);
 65 |         $results = $stmt->fetchAll();
 66 | 
 67 |         // Group accounts by type for easier display
 68 |         $categorized = [
 69 |             'asset' => [],
 70 |             'liability' => [],
 71 |             'equity' => [],
 72 |             'income' => [],
 73 |             'expense' => []
 74 |         ];
 75 | 
 76 |         foreach ($results as $account) {
 77 |             $type = $account['type'];
 78 |             $categorized[$type][] = [
 79 |                 'id' => (int) $account['id'],
 80 |                 'name' => $account['name'],
 81 |                 'type' => $account['type'],
 82 |                 'balance' => (float) $account['balance'],
 83 |                 'created_at' => $account['created_at'],
 84 |                 'updated_at' => $account['updated_at']
 85 |             ];
 86 |         }
 87 | 
 88 |         return $categorized;
 89 |     }
 90 | 
 91 |     /**
 92 |      * Get recent ledger entries with transaction details
 93 |      * 
 94 |      * Returns a list of recent transactions joined with their ledger distribution
 95 |      * details for a clear audit trail. Shows the complete double-entry flow.
 96 |      * 
 97 |      * @param int $limit Maximum number of transactions to return (default: 50)
 98 |      * @return array<int, array<string, mixed>> Array of ledger entries with transaction data
 99 |      * @throws PDOException If query fails
100 |      * 
101 |      * @example
102 |      * $model = new FinanceModel();
103 |      * $ledger = $model->getRecentLedger(20);
104 |      * foreach ($ledger as $entry) {
105 |      *     echo $entry['transaction_description'] . ' - ' . $entry['account_name'] . ': ' . $entry['amount'];
106 |      * }
107 |      */
108 |     public function getRecentLedger(int $limit = 50): array
109 |     {
110 |         $sql = "
111 |             SELECT 
112 |                 le.id AS entry_id,
113 |                 le.amount,
114 |                 le.created_at AS entry_created_at,
115 |                 t.id AS transaction_id,
116 |                 t.description AS transaction_description,
117 |                 t.reference_number,
118 |                 t.transaction_date,
119 |                 t.created_at AS transaction_created_at,
120 |                 a.id AS account_id,
121 |                 a.name AS account_name,
122 |                 a.type AS account_type,
123 |                 a.balance AS account_balance
124 |             FROM ledger_entries le
125 |             INNER JOIN transactions t ON le.transaction_id = t.id
126 |             INNER JOIN accounts a ON le.account_id = a.id
127 |             ORDER BY t.transaction_date DESC, t.id DESC, le.id ASC
128 |             LIMIT :limit
129 |         ";
130 | 
131 |         $stmt = $this->db->query($sql, ['limit' => $limit]);
132 |         $results = $stmt->fetchAll();
133 | 
134 |         // Cast numeric values for consistency
135 |         return array_map(function ($row) {
136 |             return [
137 |                 'entry_id' => (int) $row['entry_id'],
138 |                 'amount' => (float) $row['amount'],
139 |                 'entry_created_at' => $row['entry_created_at'],
140 |                 'transaction_id' => (int) $row['transaction_id'],
141 |                 'transaction_description' => $row['transaction_description'],
142 |                 'reference_number' => $row['reference_number'],
143 |                 'transaction_date' => $row['transaction_date'],
144 |                 'transaction_created_at' => $row['transaction_created_at'],
145 |                 'account_id' => (int) $row['account_id'],
146 |                 'account_name' => $row['account_name'],
147 |                 'account_type' => $row['account_type'],
148 |                 'account_balance' => (float) $row['account_balance']
149 |             ];
150 |         }, $results);
151 |     }
152 | 
153 |     /**
154 |      * Get monthly financial metrics (inflow and outflow)
155 |      * 
156 |      * Calculates the total income and expense amounts for a specific month
157 |      * by aggregating ledger entries filtered by transaction date.
158 |      * 
159 |      * Monthly Inflow: Sum of absolute values from entries hitting 'income' type accounts
160 |      * Monthly Outflow: Sum of absolute values from entries hitting 'expense' type accounts
161 |      * 
162 |      * @param string $monthYmd Month in YYYY-MM format (e.g., '2026-05')
163 |      * @return array<string, float> Associative array with 'inflow' and 'outflow' keys
164 |      * @throws PDOException If query fails
165 |      * 
166 |      * @example
167 |      * $model = new FinanceModel();
168 |      * $metrics = $model->getMonthlyMetrics('2026-05');
169 |      * echo "Inflow: $" . number_format($metrics['inflow'], 2);
170 |      * echo "Outflow: $" . number_format($metrics['outflow'], 2);
171 |      */
172 |     public function getMonthlyMetrics(string $monthYmd): array
173 |     {
174 |         // Initialize default return values
175 |         $metrics = [
176 |             'inflow' => 0.00,
177 |             'outflow' => 0.00
178 |         ];
179 | 
180 |         // Query 1: Calculate Monthly Inflow (income account entries)
181 |         $inflowSql = "
182 |             SELECT 
183 |                 COALESCE(SUM(ABS(le.amount)), 0) AS total_inflow
184 |             FROM ledger_entries le
185 |             INNER JOIN transactions t ON le.transaction_id = t.id
186 |             INNER JOIN accounts a ON le.account_id = a.id
187 |             WHERE a.type = 'income'
188 |                 AND t.transaction_date LIKE CONCAT(:month, '%')
189 |         ";
190 | 
191 |         $inflowStmt = $this->db->query($inflowSql, ['month' => $monthYmd]);
192 |         $inflowResult = $inflowStmt->fetch();
193 |         
194 |         if ($inflowResult && isset($inflowResult['total_inflow'])) {
195 |             $metrics['inflow'] = (float) $inflowResult['total_inflow'];
196 |         }
197 | 
198 |         // Query 2: Calculate Monthly Outflow (expense account entries)
199 |         $outflowSql = "
200 |             SELECT 
201 |                 COALESCE(SUM(ABS(le.amount)), 0) AS total_outflow
202 |             FROM ledger_entries le
203 |             INNER JOIN transactions t ON le.transaction_id = t.id
204 |             INNER JOIN accounts a ON le.account_id = a.id
205 |             WHERE a.type = 'expense'
206 |                 AND t.transaction_date LIKE CONCAT(:month, '%')
207 |         ";
208 | 
209 |         $outflowStmt = $this->db->query($outflowSql, ['month' => $monthYmd]);
210 |         $outflowResult = $outflowStmt->fetch();
211 |         
212 |         if ($outflowResult && isset($outflowResult['total_outflow'])) {
213 |             $metrics['outflow'] = (float) $outflowResult['total_outflow'];
214 |         }
215 | 
216 |         return $metrics;
217 |     }
218 | 
219 |     /**
220 |      * Add a simple transaction using double-entry bookkeeping
221 |      * 
222 |      * This is the Double-Entry Engine Wrapper. It creates a complete transaction
223 |      * with balanced ledger entries and updates account balances atomically.
224 |      * 
225 |      * The method:
226 |      * 1. Starts a database transaction for ACID compliance
227 |      * 2. Inserts a master record into transactions table
228 |      * 3. Creates a debit entry (negative amount) for the source account
229 |      * 4. Creates a credit entry (positive amount) for the destination account
230 |      * 5. Updates cached balances in both accounts
231 |      * 6. Commits the transaction if all operations succeed
232 |      * 7. Rolls back if any error occurs
233 |      * 
234 |      * @param string $description Transaction description
235 |      * @param int $fromAccountId Source account ID (money leaves this account)
236 |      * @param int $toAccountId Destination account ID (money enters this account)
237 |      * @param float $amount Transaction amount (must be positive)
238 |      * @param string $date Transaction date in YYYY-MM-DD format
239 |      * @return bool True on success, false on failure
240 |      * @throws PDOException If database operation fails
241 |      * 
242 |      * @example
243 |      * $model = new FinanceModel();
244 |      * try {
245 |      *     $result = $model->addSimpleTransaction(
246 |      *         'Payment for office supplies',
247 |      *         1, // Cash Safe account
248 |      *         4, // Utilities Expense account
249 |      *         500.00,
250 |      *         '2026-05-16'
251 |      *     );
252 |      *     if ($result) {
253 |      *         echo "Transaction recorded successfully";
254 |      *     }
255 |      * } catch (PDOException $e) {
256 |      *     echo "Transaction failed: " . $e->getMessage();
257 |      * }
258 |      */
259 |     public function addSimpleTransaction(
260 |         string $description,
261 |         int $fromAccountId,
262 |         int $toAccountId,
263 |         float $amount,
264 |         string $date
265 |     ): bool {
266 |         try {
267 |             // Start database transaction for ACID compliance
268 |             $this->db->beginTransaction();
269 | 
270 |             // Step 1: Insert master transaction record
271 |             $transactionSql = "
272 |                 INSERT INTO transactions (
273 |                     description,
274 |                     transaction_date,
275 |                     created_at,
276 |                     updated_at
277 |                 ) VALUES (
278 |                     :description,
279 |                     :transaction_date,
280 |                     NOW(),
281 |                     NOW()
282 |                 )
283 |             ";
284 | 
285 |             $this->db->query($transactionSql, [
286 |                 'description' => $description,
287 |                 'transaction_date' => $date
288 |             ]);
289 | 
290 |             // Get the transaction ID for ledger entries
291 |             $transactionId = (int) $this->db->lastInsertId();
292 | 
293 |             // Step 2: Insert debit entry for source account (subtract amount)
294 |             $debitSql = "
295 |                 INSERT INTO ledger_entries (
296 |                     transaction_id,
297 |                     account_id,
298 |                     amount,
299 |                     created_at,
300 |                     updated_at
301 |                 ) VALUES (
302 |                     :transaction_id,
303 |                     :account_id,
304 |                     :amount,
305 |                     NOW(),
306 |                     NOW()
307 |                 )
308 |             ";
309 | 
310 |             $this->db->query($debitSql, [
311 |                 'transaction_id' => $transactionId,
312 |                 'account_id' => $fromAccountId,
313 |                 'amount' => -$amount // Negative for debit (money leaving)
314 |             ]);
315 | 
316 |             // Step 3: Insert credit entry for destination account (add amount)
317 |             $creditSql = "
318 |                 INSERT INTO ledger_entries (
319 |                     transaction_id,
320 |                     account_id,
321 |                     amount,
322 |                     created_at,
323 |                     updated_at
324 |                 ) VALUES (
325 |                     :transaction_id,
326 |                     :account_id,
327 |                     :amount,
328 |                     NOW(),
329 |                     NOW()
330 |                 )
331 |             ";
332 | 
333 |             $this->db->query($creditSql, [
334 |                 'transaction_id' => $transactionId,
335 |                 'account_id' => $toAccountId,
336 |                 'amount' => $amount // Positive for credit (money entering)
337 |             ]);
338 | 
339 |             // Step 4: Update cached balance for source account (subtract)
340 |             $updateFromSql = "
341 |                 UPDATE accounts
342 |                 SET 
343 |                     balance = balance - :amount,
344 |                     updated_at = NOW()
345 |                 WHERE id = :account_id
346 |             ";
347 | 
348 |             $this->db->query($updateFromSql, [
349 |                 'amount' => $amount,
350 |                 'account_id' => $fromAccountId
351 |             ]);
352 | 
353 |             // Step 5: Update cached balance for destination account (add)
354 |             $updateToSql = "
355 |                 UPDATE accounts
356 |                 SET 
357 |                     balance = balance + :amount,
358 |                     updated_at = NOW()
359 |                 WHERE id = :account_id
360 |             ";
361 | 
362 |             $this->db->query($updateToSql, [
363 |                 'amount' => $amount,
364 |                 'account_id' => $toAccountId
365 |             ]);
366 | 
367 |             // Commit the transaction - all operations succeeded
368 |             $this->db->commit();
369 | 
370 |             return true;
371 | 
372 |         } catch (PDOException $e) {
373 |             // Rollback on any error to maintain data integrity
374 |             $this->db->rollback();
375 | 
376 |             // Re-throw the exception for proper error handling
377 |             throw $e;
378 |         }
379 |     }
380 | }
381 | 
382 | // Made with Bob
</content>
</file>
<file><path>modules/sales/models/SalesModel.php</path>
<content lines="1-527">
  1 | <?php
  2 | 
  3 | declare(strict_types=1);
  4 | 
  5 | /**
  6 |  * SalesModel Class
  7 |  *
  8 |  * Handles all database operations for the sales module with automated integration.
  9 |  * Implements ACID-compliant transactions that span multiple modules:
 10 |  * - Creates sales orders and line items
 11 |  * - Updates inventory stock levels automatically
 12 |  * - Records financial transactions directly (double-entry bookkeeping)
 13 |  *
 14 |  * All operations are wrapped in a single database transaction to ensure data integrity.
 15 |  * If any step fails, the entire operation is rolled back.
 16 |  *
 17 |  * @package AmenERP\Modules\Sales
 18 |  * @author Bob
 19 |  * @version 1.0.1
 20 |  */
 21 | class SalesModel
 22 | {
 23 |     /**
 24 |      * Database instance
 25 |      *
 26 |      * @var Database
 27 |      */
 28 |     private Database $db;
 29 | 
 30 |     /**
 31 |      * Default income account ID (General Sales Income)
 32 |      *
 33 |      * @var int
 34 |      */
 35 |     private const SALES_INCOME_ACCOUNT_ID = 3;
 36 | 
 37 |     /**
 38 |      * Default cash/bank account ID (Cash Safe or Bank Account)
 39 |      *
 40 |      * @var int
 41 |      */
 42 |     private const CASH_ACCOUNT_ID = 1;
 43 | 
 44 |     /**
 45 |      * Constructor
 46 |      * Initializes database connection
 47 |      */
 48 |     public function __construct()
 49 |     {
 50 |         $this->db = Database::getInstance();
 51 |     }
 52 | 
 53 |     /**
 54 |      * Create a new sales order with automated multi-module integration
 55 |      * 
 56 |      * This is the core integration method that executes a complete sales transaction:
 57 |      * 
 58 |      * Action A: Insert master sales_order and line items into sales_items
 59 |      * Action B: Update inventory stock levels for each product sold
 60 |      * Action C: Record financial transaction via FinanceModel
 61 |      * 
 62 |      * All operations are wrapped in a database transaction. If any step fails
 63 |      * (e.g., insufficient stock, database error), the entire operation is rolled back.
 64 |      * 
 65 |      * @param string $customerName Customer or buyer name
 66 |      * @param array<int, array<string, mixed>> $items Array of items with keys:
 67 |      *                                                - product_id: Product ID (int)
 68 |      *                                                - quantity: Quantity to sell (int)
 69 |      *                                                - unit_price: Price per unit (float)
 70 |      * @param int|null $cashAccountId Optional cash account ID (defaults to CASH_ACCOUNT_ID)
 71 |      * @return array<string, mixed> Result array with keys:
 72 |      *                              - success: bool
 73 |      *                              - sales_order_id: int (if successful)
 74 |      *                              - invoice_number: string (if successful)
 75 |      *                              - message: string (error message if failed)
 76 |      * @throws PDOException If database operation fails
 77 |      * 
 78 |      * @example
 79 |      * $model = new SalesModel();
 80 |      * $result = $model->createSalesOrder('John Doe', [
 81 |      *     ['product_id' => 1, 'quantity' => 2, 'unit_price' => 50.00],
 82 |      *     ['product_id' => 3, 'quantity' => 1, 'unit_price' => 150.00]
 83 |      * ]);
 84 |      * 
 85 |      * if ($result['success']) {
 86 |      *     echo "Order created: " . $result['invoice_number'];
 87 |      * } else {
 88 |      *     echo "Error: " . $result['message'];
 89 |      * }
 90 |      */
 91 |     public function createSalesOrder(
 92 |         string $customerName,
 93 |         array $items,
 94 |         ?int $cashAccountId = null
 95 |     ): array {
 96 |         // Use default cash account if not specified
 97 |         $cashAccountId = $cashAccountId ?? self::CASH_ACCOUNT_ID;
 98 | 
 99 |         try {
100 |             // Start database transaction for ACID compliance
101 |             $this->db->beginTransaction();
102 | 
103 |             // Validate that we have items to sell
104 |             if (empty($items)) {
105 |                 throw new Exception('Cannot create sales order: No items provided');
106 |             }
107 | 
108 |             // Calculate total amount and validate inventory availability
109 |             $totalAmount = 0.00;
110 |             foreach ($items as $item) {
111 |                 // Validate required fields
112 |                 if (!isset($item['product_id'], $item['quantity'], $item['unit_price'])) {
113 |                     throw new Exception('Invalid item data: Missing required fields');
114 |                 }
115 | 
116 |                 // Validate quantity is positive
117 |                 if ($item['quantity'] <= 0) {
118 |                     throw new Exception('Invalid quantity: Must be greater than zero');
119 |                 }
120 | 
121 |                 // Check inventory availability BEFORE creating the order
122 |                 $stockCheckSql = "SELECT quantity FROM products WHERE id = :product_id";
123 |                 $stockStmt = $this->db->query($stockCheckSql, ['product_id' => $item['product_id']]);
124 |                 $stockResult = $stockStmt->fetch();
125 | 
126 |                 if (!$stockResult) {
127 |                     throw new Exception("Product ID {$item['product_id']} not found");
128 |                 }
129 | 
130 |                 $availableStock = (int) $stockResult['quantity'];
131 |                 if ($availableStock < $item['quantity']) {
132 |                     throw new Exception(
133 |                         "Insufficient stock for product ID {$item['product_id']}: " .
134 |                         "Available: {$availableStock}, Required: {$item['quantity']}"
135 |                     );
136 |                 }
137 | 
138 |                 // Calculate line total
139 |                 $lineTotal = $item['quantity'] * $item['unit_price'];
140 |                 $totalAmount += $lineTotal;
141 |             }
142 | 
143 |             // Generate unique invoice number
144 |             $invoiceNumber = $this->generateInvoiceNumber();
145 | 
146 |             // ACTION A: Insert master sales order record
147 |             $orderSql = "
148 |                 INSERT INTO sales_orders (
149 |                     invoice_number,
150 |                     customer_name,
151 |                     total_amount,
152 |                     created_at,
153 |                     updated_at
154 |                 ) VALUES (
155 |                     :invoice_number,
156 |                     :customer_name,
157 |                     :total_amount,
158 |                     NOW(),
159 |                     NOW()
160 |                 )
161 |             ";
162 | 
163 |             $this->db->query($orderSql, [
164 |                 'invoice_number' => $invoiceNumber,
165 |                 'customer_name' => $customerName,
166 |                 'total_amount' => $totalAmount
167 |             ]);
168 | 
169 |             // Get the sales order ID
170 |             $salesOrderId = (int) $this->db->lastInsertId();
171 | 
172 |             // ACTION A & B: Insert line items and update inventory
173 |             foreach ($items as $item) {
174 |                 $lineTotal = $item['quantity'] * $item['unit_price'];
175 | 
176 |                 // Insert sales item
177 |                 $itemSql = "
178 |                     INSERT INTO sales_items (
179 |                         sales_order_id,
180 |                         product_id,
181 |                         quantity,
182 |                         unit_price,
183 |                         line_total,
184 |                         created_at,
185 |                         updated_at
186 |                     ) VALUES (
187 |                         :sales_order_id,
188 |                         :product_id,
189 |                         :quantity,
190 |                         :unit_price,
191 |                         :line_total,
192 |                         NOW(),
193 |                         NOW()
194 |                     )
195 |                 ";
196 | 
197 |                 $this->db->query($itemSql, [
198 |                     'sales_order_id' => $salesOrderId,
199 |                     'product_id' => $item['product_id'],
200 |                     'quantity' => $item['quantity'],
201 |                     'unit_price' => $item['unit_price'],
202 |                     'line_total' => $lineTotal
203 |                 ]);
204 | 
205 |                 // ACTION B: Update inventory stock (deduct sold quantity)
206 |                 $inventoryUpdateSql = "
207 |                     UPDATE products
208 |                     SET 
209 |                         quantity = quantity - :sold_qty,
210 |                         updated_at = NOW()
211 |                     WHERE id = :product_id
212 |                 ";
213 | 
214 |                 $this->db->query($inventoryUpdateSql, [
215 |                     'sold_qty' => $item['quantity'],
216 |                     'product_id' => $item['product_id']
217 |                 ]);
218 | 
219 |                 // Verify stock didn't go negative (safety check)
220 |                 $verifyStockSql = "SELECT quantity FROM products WHERE id = :product_id";
221 |                 $verifyStmt = $this->db->query($verifyStockSql, ['product_id' => $item['product_id']]);
222 |                 $verifyResult = $verifyStmt->fetch();
223 | 
224 |                 if ($verifyResult && (int) $verifyResult['quantity'] < 0) {
225 |                     throw new Exception(
226 |                         "Stock validation failed for product ID {$item['product_id']}: " .
227 |                         "Stock would become negative"
228 |                     );
229 |                 }
230 |             }
231 | 
232 |             // ACTION C: Record financial transaction manually (avoid nested transactions)
233 |             // Money flows FROM Sales Income Account TO Cash/Bank Account
234 |             $transactionDescription = "Sales Invoice: {$invoiceNumber} - Customer: {$customerName}";
235 |             $transactionDate = date('Y-m-d');
236 | 
237 |             // Insert master transaction record
238 |             $financeSql = "
239 |                 INSERT INTO transactions (
240 |                     description,
241 |                     transaction_date,
242 |                     created_at,
243 |                     updated_at
244 |                 ) VALUES (
245 |                     :description,
246 |                     :transaction_date,
247 |                     NOW(),
248 |                     NOW()
249 |                 )
250 |             ";
251 | 
252 |             $this->db->query($financeSql, [
253 |                 'description' => $transactionDescription,
254 |                 'transaction_date' => $transactionDate
255 |             ]);
256 | 
257 |             $transactionId = (int) $this->db->lastInsertId();
258 | 
259 |             // Insert debit entry for Sales Income account (money leaving)
260 |             $debitSql = "
261 |                 INSERT INTO ledger_entries (
262 |                     transaction_id,
263 |                     account_id,
264 |                     amount,
265 |                     created_at,
266 |                     updated_at
267 |                 ) VALUES (
268 |                     :transaction_id,
269 |                     :account_id,
270 |                     :amount,
271 |                     NOW(),
272 |                     NOW()
273 |                 )
274 |             ";
275 | 
276 |             $this->db->query($debitSql, [
277 |                 'transaction_id' => $transactionId,
278 |                 'account_id' => self::SALES_INCOME_ACCOUNT_ID,
279 |                 'amount' => -$totalAmount // Negative for debit
280 |             ]);
281 | 
282 |             // Insert credit entry for Cash account (money entering)
283 |             $creditSql = "
284 |                 INSERT INTO ledger_entries (
285 |                     transaction_id,
286 |                     account_id,
287 |                     amount,
288 |                     created_at,
289 |                     updated_at
290 |                 ) VALUES (
291 |                     :transaction_id,
292 |                     :account_id,
293 |                     :amount,
294 |                     NOW(),
295 |                     NOW()
296 |                 )
297 |             ";
298 | 
299 |             $this->db->query($creditSql, [
300 |                 'transaction_id' => $transactionId,
301 |                 'account_id' => $cashAccountId,
302 |                 'amount' => $totalAmount // Positive for credit
303 |             ]);
304 | 
305 |             // Update Sales Income account balance (subtract)
306 |             $updateIncomeSql = "
307 |                 UPDATE accounts
308 |                 SET
309 |                     balance = balance - :amount,
310 |                     updated_at = NOW()
311 |                 WHERE id = :account_id
312 |             ";
313 | 
314 |             $this->db->query($updateIncomeSql, [
315 |                 'amount' => $totalAmount,
316 |                 'account_id' => self::SALES_INCOME_ACCOUNT_ID
317 |             ]);
318 | 
319 |             // Update Cash account balance (add)
320 |             $updateCashSql = "
321 |                 UPDATE accounts
322 |                 SET
323 |                     balance = balance + :amount,
324 |                     updated_at = NOW()
325 |                 WHERE id = :account_id
326 |             ";
327 | 
328 |             $this->db->query($updateCashSql, [
329 |                 'amount' => $totalAmount,
330 |                 'account_id' => $cashAccountId
331 |             ]);
332 | 
333 |             // Commit the transaction - all operations succeeded
334 |             $this->db->commit();
335 | 
336 |             return [
337 |                 'success' => true,
338 |                 'sales_order_id' => $salesOrderId,
339 |                 'invoice_number' => $invoiceNumber,
340 |                 'total_amount' => $totalAmount,
341 |                 'message' => 'Sales order created successfully'
342 |             ];
343 | 
344 |         } catch (Exception $e) {
345 |             // Rollback on any error to maintain data integrity
346 |             $this->db->rollback();
347 | 
348 |             return [
349 |                 'success' => false,
350 |                 'message' => $e->getMessage()
351 |             ];
352 |         }
353 |     }
354 | 
355 |     /**
356 |      * Generate a unique invoice number
357 |      * 
358 |      * Format: INV-YYYY-NNNN (e.g., INV-2026-0001)
359 |      * 
360 |      * @return string Unique invoice number
361 |      * @throws PDOException If query fails
362 |      */
363 |     private function generateInvoiceNumber(): string
364 |     {
365 |         $year = date('Y');
366 |         $prefix = "INV-{$year}-";
367 | 
368 |         // Get the highest invoice number for current year
369 |         $sql = "
370 |             SELECT invoice_number
371 |             FROM sales_orders
372 |             WHERE invoice_number LIKE :prefix
373 |             ORDER BY invoice_number DESC
374 |             LIMIT 1
375 |         ";
376 | 
377 |         $stmt = $this->db->query($sql, ['prefix' => $prefix . '%']);
378 |         $result = $stmt->fetch();
379 | 
380 |         if ($result) {
381 |             // Extract the numeric part and increment
382 |             $lastNumber = (int) substr($result['invoice_number'], -4);
383 |             $nextNumber = $lastNumber + 1;
384 |         } else {
385 |             // First invoice of the year
386 |             $nextNumber = 1;
387 |         }
388 | 
389 |         // Format with leading zeros (4 digits)
390 |         return $prefix . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
391 |     }
392 | 
393 |     /**
394 |      * Get all sales orders with summary information
395 |      * 
396 |      * @param int $limit Maximum number of orders to return (default: 50)
397 |      * @return array<int, array<string, mixed>> Array of sales orders
398 |      * @throws PDOException If query fails
399 |      */
400 |     public function getAllSalesOrders(int $limit = 50): array
401 |     {
402 |         $sql = "
403 |             SELECT 
404 |                 id,
405 |                 invoice_number,
406 |                 customer_name,
407 |                 total_amount,
408 |                 created_at,
409 |                 updated_at
410 |             FROM sales_orders
411 |             ORDER BY created_at DESC
412 |             LIMIT :limit
413 |         ";
414 | 
415 |         $stmt = $this->db->query($sql, ['limit' => $limit]);
416 |         $results = $stmt->fetchAll();
417 | 
418 |         // Cast numeric values
419 |         return array_map(function ($row) {
420 |             return [
421 |                 'id' => (int) $row['id'],
422 |                 'invoice_number' => $row['invoice_number'],
423 |                 'customer_name' => $row['customer_name'],
424 |                 'total_amount' => (float) $row['total_amount'],
425 |                 'created_at' => $row['created_at'],
426 |                 'updated_at' => $row['updated_at']
427 |             ];
428 |         }, $results);
429 |     }
430 | 
431 |     /**
432 |      * Get sales order details with line items
433 |      * 
434 |      * @param int $salesOrderId Sales order ID
435 |      * @return array<string, mixed>|null Order details with items array, or null if not found
436 |      * @throws PDOException If query fails
437 |      */
438 |     public function getSalesOrderById(int $salesOrderId): ?array
439 |     {
440 |         // Get order header
441 |         $orderSql = "
442 |             SELECT 
443 |                 id,
444 |                 invoice_number,
445 |                 customer_name,
446 |                 total_amount,
447 |                 created_at,
448 |                 updated_at
449 |             FROM sales_orders
450 |             WHERE id = :sales_order_id
451 |         ";
452 | 
453 |         $orderStmt = $this->db->query($orderSql, ['sales_order_id' => $salesOrderId]);
454 |         $order = $orderStmt->fetch();
455 | 
456 |         if (!$order) {
457 |             return null;
458 |         }
459 | 
460 |         // Get line items
461 |         $itemsSql = "
462 |             SELECT 
463 |                 si.id,
464 |                 si.product_id,
465 |                 si.quantity,
466 |                 si.unit_price,
467 |                 si.line_total,
468 |                 p.name AS product_name,
469 |                 p.sku AS product_sku
470 |             FROM sales_items si
471 |             INNER JOIN products p ON si.product_id = p.id
472 |             WHERE si.sales_order_id = :sales_order_id
473 |             ORDER BY si.id ASC
474 |         ";
475 | 
476 |         $itemsStmt = $this->db->query($itemsSql, ['sales_order_id' => $salesOrderId]);
477 |         $items = $itemsStmt->fetchAll();
478 | 
479 |         return [
480 |             'id' => (int) $order['id'],
481 |             'invoice_number' => $order['invoice_number'],
482 |             'customer_name' => $order['customer_name'],
483 |             'total_amount' => (float) $order['total_amount'],
484 |             'created_at' => $order['created_at'],
485 |             'updated_at' => $order['updated_at'],
486 |             'items' => array_map(function ($item) {
487 |                 return [
488 |                     'id' => (int) $item['id'],
489 |                     'product_id' => (int) $item['product_id'],
490 |                     'product_name' => $item['product_name'],
491 |                     'product_sku' => $item['product_sku'],
492 |                     'quantity' => (int) $item['quantity'],
493 |                     'unit_price' => (float) $item['unit_price'],
494 |                     'line_total' => (float) $item['line_total']
495 |                 ];
496 |             }, $items)
497 |         ];
498 |     }
499 | 
500 |     /**
501 |      * Get sales statistics for dashboard
502 |      * 
503 |      * @return array<string, mixed> Sales statistics
504 |      * @throws PDOException If query fails
505 |      */
506 |     public function getSalesStats(): array
507 |     {
508 |         $sql = "
509 |             SELECT 
510 |                 COUNT(id) AS total_orders,
511 |                 COALESCE(SUM(total_amount), 0) AS total_revenue,
512 |                 COALESCE(AVG(total_amount), 0) AS average_order_value
513 |             FROM sales_orders
514 |         ";
515 | 
516 |         $stmt = $this->db->query($sql);
517 |         $result = $stmt->fetch();
518 | 
519 |         return [
520 |             'total_orders' => (int) $result['total_orders'],
521 |             'total_revenue' => (float) $result['total_revenue'],
522 |             'average_order_value' => (float) $result['average_order_value']
523 |         ];
524 |     }
525 | }
526 | 
527 | // Made with Bob
</content>
</file>
<file><path>core/Database.php</path>
<content lines="1-224">
  1 | <?php
  2 | 
  3 | declare(strict_types=1);
  4 | 
  5 | /**
  6 |  * Database Class - Singleton PDO Wrapper
  7 |  * 
  8 |  * Provides a secure database connection using PDO with prepared statements
  9 |  * to prevent SQL injection attacks. Follows the Singleton pattern to ensure
 10 |  * only one database connection exists throughout the application lifecycle.
 11 |  * 
 12 |  * @package AmenERP\Core
 13 |  * @author Bob
 14 |  * @version 1.0.0
 15 |  */
 16 | class Database
 17 | {
 18 |     /**
 19 |      * Singleton instance
 20 |      * 
 21 |      * @var Database|null
 22 |      */
 23 |     private static ?Database $instance = null;
 24 | 
 25 |     /**
 26 |      * PDO connection object
 27 |      * 
 28 |      * @var PDO|null
 29 |      */
 30 |     private ?PDO $connection = null;
 31 | 
 32 |     /**
 33 |      * Database configuration
 34 |      * 
 35 |      * @var array<string, string>
 36 |      */
 37 |     private array $config = [];
 38 | 
 39 |     /**
 40 |      * Private constructor to prevent direct instantiation
 41 |      * Establishes PDO connection with secure settings
 42 |      * 
 43 |      * @throws PDOException If connection fails
 44 |      */
 45 |     private function __construct()
 46 |     {
 47 |         // Load configuration from config.php
 48 |         if (!defined('DB_HOST')) {
 49 |             throw new RuntimeException('Database configuration not loaded. Include config.php first.');
 50 |         }
 51 | 
 52 |         $this->config = [
 53 |             'host' => DB_HOST,
 54 |             'name' => DB_NAME,
 55 |             'user' => DB_USER,
 56 |             'pass' => DB_PASS,
 57 |             'charset' => DB_CHARSET
 58 |         ];
 59 | 
 60 |         $this->connect();
 61 |     }
 62 | 
 63 |     /**
 64 |      * Establish PDO connection with security settings
 65 |      * 
 66 |      * @return void
 67 |      * @throws PDOException If connection fails
 68 |      */
 69 |     private function connect(): void
 70 |     {
 71 |         $dsn = sprintf(
 72 |             'mysql:host=%s;dbname=%s;charset=%s',
 73 |             $this->config['host'],
 74 |             $this->config['name'],
 75 |             $this->config['charset']
 76 |         );
 77 | 
 78 |         $options = [
 79 |             PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
 80 |             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
 81 |             PDO::ATTR_EMULATE_PREPARES   => false,
 82 |             PDO::ATTR_PERSISTENT         => false
 83 |         ];
 84 | 
 85 |         try {
 86 |             $this->connection = new PDO(
 87 |                 $dsn,
 88 |                 $this->config['user'],
 89 |                 $this->config['pass'],
 90 |                 $options
 91 |             );
 92 |         } catch (PDOException $e) {
 93 |             // Log error in production, display in development
 94 |             if (ENVIRONMENT === 'development') {
 95 |                 throw new PDOException('Database connection failed: ' . $e->getMessage());
 96 |             } else {
 97 |                 error_log('Database connection failed: ' . $e->getMessage());
 98 |                 throw new PDOException('Database connection failed. Please contact support.');
 99 |             }
100 |         }
101 |     }
102 | 
103 |     /**
104 |      * Get singleton instance of Database
105 |      * 
106 |      * @return Database The singleton instance
107 |      */
108 |     public static function getInstance(): Database
109 |     {
110 |         if (self::$instance === null) {
111 |             self::$instance = new self();
112 |         }
113 | 
114 |         return self::$instance;
115 |     }
116 | 
117 |     /**
118 |      * Execute a prepared statement query
119 |      * 
120 |      * This method automatically handles parameter binding to prevent SQL injection.
121 |      * Use named placeholders (:name) or positional placeholders (?) in your SQL.
122 |      * 
123 |      * @param string $sql SQL query with placeholders
124 |      * @param array<int|string, mixed> $params Parameters to bind (optional)
125 |      * @return PDOStatement Executed statement object
126 |      * @throws PDOException If query execution fails
127 |      * 
128 |      * @example
129 |      * // Named placeholders
130 |      * $db->query('SELECT * FROM users WHERE email = :email', ['email' => $email]);
131 |      * 
132 |      * // Positional placeholders
133 |      * $db->query('SELECT * FROM users WHERE id = ?', [$userId]);
134 |      */
135 |     public function query(string $sql, array $params = []): PDOStatement
136 |     {
137 |         try {
138 |             $stmt = $this->connection->prepare($sql);
139 |             $stmt->execute($params);
140 |             return $stmt;
141 |         } catch (PDOException $e) {
142 |             // Log error in production, display in development
143 |             if (ENVIRONMENT === 'development') {
144 |                 throw new PDOException('Query failed: ' . $e->getMessage() . ' | SQL: ' . $sql);
145 |             } else {
146 |                 error_log('Query failed: ' . $e->getMessage() . ' | SQL: ' . $sql);
147 |                 throw new PDOException('Database query failed. Please try again.');
148 |             }
149 |         }
150 |     }
151 | 
152 |     /**
153 |      * Get the last inserted ID
154 |      * 
155 |      * @return string The last insert ID
156 |      */
157 |     public function lastInsertId(): string
158 |     {
159 |         return $this->connection->lastInsertId();
160 |     }
161 | 
162 |     /**
163 |      * Begin a database transaction
164 |      * 
165 |      * @return bool True on success
166 |      */
167 |     public function beginTransaction(): bool
168 |     {
169 |         return $this->connection->beginTransaction();
170 |     }
171 | 
172 |     /**
173 |      * Commit the current transaction
174 |      * 
175 |      * @return bool True on success
176 |      */
177 |     public function commit(): bool
178 |     {
179 |         return $this->connection->commit();
180 |     }
181 | 
182 |     /**
183 |      * Roll back the current transaction
184 |      * 
185 |      * @return bool True on success
186 |      */
187 |     public function rollback(): bool
188 |     {
189 |         return $this->connection->rollBack();
190 |     }
191 | 
192 |     /**
193 |      * Get the raw PDO connection (use sparingly)
194 |      * 
195 |      * @return PDO The PDO connection object
196 |      */
197 |     public function getConnection(): PDO
198 |     {
199 |         return $this->connection;
200 |     }
201 | 
202 |     /**
203 |      * Prevent cloning of the singleton instance
204 |      * 
205 |      * @return void
206 |      */
207 |     private function __clone(): void
208 |     {
209 |         // Prevent cloning
210 |     }
211 | 
212 |     /**
213 |      * Prevent unserialization of the singleton instance
214 |      * 
215 |      * @return void
216 |      * @throws Exception Always throws exception
217 |      */
218 |     public function __wakeup(): void
219 |     {
220 |         throw new Exception('Cannot unserialize singleton');
221 |     }
222 | }
223 | 
224 | // Made with Bob
</content>
</file>
</files>
<environment_details>
# VSCode Visible Files
modules\customers\database\schema.sql

# VSCode Open Tabs
modules/home/models/DashboardModel.php,public/assets/css/dashboard.css,modules/home/index.php,modules/home/ROUTING.md,modules/home/INTEGRATION_GUIDE.md,modules/customers/database/schema.sql,public/index.php

# Current Time
Current time in ISO 8601 UTC format: 2026-05-16T16:11:57.490Z
User time zone: Asia/Muscat, UTC+4:00

# Current Cost
$0.13

# Review Exclusions
The following file patterns are excluded from code reviews:
- .vscode/**

# Current Mode
<slug>code</slug>
<name>💻 Code</name>
====

REMINDERS

Below is your current list of reminders for this task. Keep them updated as you progress.

| # | Content | Status |
|---|---------|--------|
| 1 | Create modules/customers/database directory structure | Completed |
| 2 | Write database schema script (schema.sql) | Completed |
| 3 | Write CustomerModel.php with transactional payment logic | In Progress |
| 4 | Verify all files are complete and properly formatted | Pending |


IMPORTANT: When task status changes, remember to call the `update_todo_list` tool to update your progress.

</environment_details>

---

**Assistant:**

Now I'll create the comprehensive CustomerModel.php with all the required functionality:

<write_to_file>
<path>modules/customers/models/CustomerModel.php</path>
<content>
<?php

declare(strict_types=1);

/**
 * CustomerModel Class
 * 
 * Handles all database operations for the customer accounts and receivables module.
 * Implements ACID-compliant transactions for customer payment processing with
 * automated integration across multiple modules:
 * - Updates customer outstanding balances
 * - Records payment receipts
 * - Creates financial transactions (double-entry bookkeeping)
 * - Updates account balances in the general ledger
 * 
 * All operations are wrapped in database transactions to ensure data integrity.
 * If any step fails, the entire operation is rolled back.
 * 
 * @package AmenERP\Modules\Customers
 * @author Bob
 * @version 1.0.0
 */
class CustomerModel
{
    /**
     * Database instance
     * 
     * @var Database
     */
    private Database $db;

    /**
     * Accounts Receivable Asset Account ID
     * This account tracks money owed to us by customers
     * 
     * @var int
     */
    private const ACCOUNTS_RECEIVABLE_ID = 5;

    /**
     * Cash Account ID (Primary Liquid Asset)
     * This account tracks cash received from customers
     * 
     * @var int
     */
    private const CASH_ACCOUNT_ID = 1;

    /**
     * Constructor
     * Initializes database connection using singleton pattern
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Record a customer payment with full transactional integrity
     * 
     * This is the core payment processing method that executes a complete
     * customer payment transaction across multiple modules:
     * 
     * Action A: Validate payment amount and customer balance
     * Action B: Update customer outstanding_balance (reduce receivables)
     * Action C: Insert payment receipt record for audit trail
     * Action D: Create financial transaction in general ledger
     * Action E: Update account balances (Cash and Accounts Receivable)
     * 
     * All operations are wrapped in a database transaction. If any step fails,
     * the entire operation is rolled back to maintain data integrity.
     * 
     * @param int $customerId Customer ID receiving the payment
     * @param float $amount Payment amount received (must be positive)
     * @param string $method Payment method (cash, check, bank_transfer, credit_card, etc.)
     * @param string|null $reference Optional payment reference (check number, transaction ID, etc.)
     * @param string|null $notes Optional payment notes or remarks
     * @return array<string, mixed> Result array with keys:
     *                              - success: bool
     *                              - receipt_id: int (if successful)
     *                              - receipt_number: string (if successful)
     *                              - message: string
     * @throws PDOException If database operation fails
     * 
     * @example
     * $model = new CustomerModel();
     * $result = $model->recordCustomerPayment(
     *     customerId: 1,
     *     amount: 5000.00,
     *     method: 'bank_transfer',
     *     reference: 'TXN-123456',
     *     notes: 'Payment for Invoice INV-2026-0001'
     * );
     * 
     * if ($result['success']) {
     *     echo "Payment recorded: " . $result['receipt_number'];
     * } else {
     *     echo "Error: " . $result['message'];
     * }
     */
    public function recordCustomerPayment(
        int $customerId,
        float $amount,
        string $method,
        ?string $reference = null,
        ?string $notes = null
    ): array {
        try {
            // Start database transaction for ACID compliance
            $this->db->beginTransaction();

            // ACTION A: Validate payment amount
            if ($amount <= 0) {
                throw new Exception('Payment amount must be greater than zero');
            }

            // ACTION A: Get customer details and validate balance
            $customerSql = "
                SELECT 
                    id,
                    customer_code,
                    company_name,
                    outstanding_balance,
                    status
                FROM customers
                WHERE id = :customer_id
                FOR UPDATE
            ";

            $customerStmt = $this->db->query($customerSql, ['customer_id' => $customerId]);
            $customer = $customerStmt->fetch();

            if (!$customer) {
                throw new Exception("Customer ID {$customerId} not found");
            }

            // Validate customer status
            if ($customer['status'] !== 'active') {
                throw new Exception(
                    "Cannot process payment: Customer account is {$customer['status']}"
                );
            }

            $outstandingBalance = (float) $customer['outstanding_balance'];

            // Validate payment doesn't exceed outstanding balance
            if ($amount > $outstandingBalance) {
                throw new Exception(
                    "Payment amount ({$amount}) exceeds outstanding balance ({$outstandingBalance}). " .
                    "Maximum payment allowed: {$outstandingBalance}"
                );
            }

            // ACTION B: Update customer outstanding balance (reduce receivables)
            $updateCustomerSql = "
                UPDATE customers
                SET 
                    outstanding_balance = outstanding_balance - :amount,
                    updated_at = NOW()
                WHERE id = :customer_id
            ";

            $this->db->query($updateCustomerSql, [
                'amount' => $amount,
                'customer_id' => $customerId
            ]);

            // ACTION C: Generate unique receipt number and insert receipt record
            $receiptNumber = $this->generateReceiptNumber();

            $receiptSql = "
                INSERT INTO customer_receipts (
                    customer_id,
                    receipt_number,
                    amount_paid,
                    payment_method,
                    payment_reference,
                    notes,
                    processed_at,
                    created_at,
                    updated_at
                ) VALUES (
                    :customer_id,
                    :receipt_number,
                    :amount_paid,
                    :payment_method,
                    :payment_reference,
                    :notes,
                    NOW(),
                    NOW(),
                    NOW()
                )
            ";

            $this->db->query($receiptSql, [
                'customer_id' => $customerId,
                'receipt_number' => $receiptNumber,
                'amount_paid' => $amount,
                'payment_method' => $method,
                'payment_reference' => $reference,
                'notes' => $notes
            ]);

            $receiptId = (int) $this->db->lastInsertId();

            // ACTION D: Record financial transaction in general ledger
            // Money flows: DEBIT Cash (increase asset), CREDIT Accounts Receivable (decrease asset)
            $transactionDescription = sprintf(
                "Customer Payment: %s - %s - Receipt: %s",
                $customer['customer_code'],
                $customer['company_name'],
                $receiptNumber
            );
            $transactionDate = date('Y-m-d');

            // Insert master transaction record
            $transactionSql = "
                INSERT INTO transactions (
                    description,
                    reference_number,
                    transaction_date,
                    created_at,
                    updated_at
                ) VALUES (
                    :description,
                    :reference_number,
                    :transaction_date,
                    NOW(),
                    NOW()
                )
            ";

            $this->db->query($transactionSql, [
                'description' => $transactionDescription,
                'reference_number' => $receiptNumber,
                'transaction_date' => $transactionDate
            ]);

            $transactionId = (int) $this->db->lastInsertId();

            // Insert DEBIT entry for Cash account (money entering - positive asset increase)
            $debitCashSql = "
                INSERT INTO ledger_entries (
                    transaction_id,
                    account_id,
                    amount,
                    created_at,
                    updated_at
                ) VALUES (
                    :transaction_id,
                    :account_id,
                    :amount,
                    NOW(),
                    NOW()
                )
            ";

            $this->db->query($debitCashSql, [
                'transaction_id' => $transactionId,
                'account_id' => self::CASH_ACCOUNT_ID,
                'amount' => $amount // Positive for debit (cash increase)
            ]);

            // Insert CREDIT entry for Accounts Receivable (money leaving - negative asset decrease)
            $creditReceivableSql = "
                INSERT INTO ledger_entries (
                    transaction_id,
                    account_id,
                    amount,
                    created_at,
                    updated_at
                ) VALUES (
                    :transaction_id,
                    :account_id,
                    :amount,
                    NOW(),
                    NOW()
                )
            ";

            $this->db->query($creditReceivableSql, [
                'transaction_id' => $transactionId,
                'account_id' => self::ACCOUNTS_RECEIVABLE_ID,
                'amount' => -$amount // Negative for credit (receivables decrease)
            ]);

            // ACTION E: Update account balances in the accounts table
            // Update Cash account balance (increase)
            $updateCashAccountSql = "
                UPDATE accounts
                SET 
                    balance = balance + :amount,
                    updated_at = NOW()
                WHERE id = :account_id
            ";

            $this->db->query($updateCashAccountSql, [
                'amount' => $amount,
                'account_id' => self::CASH_ACCOUNT_ID
            ]);

            // Update Accounts Receivable balance (decrease)
            $updateReceivableAccountSql = "
                UPDATE accounts
                SET 
                    balance = balance - :amount,
                    updated_at = NOW()
                WHERE id = :account_id
            ";

            $this->db->query($updateReceivableAccountSql, [
                'amount' => $amount,
                'account_id' => self::ACCOUNTS_RECEIVABLE_ID
            ]);

            // Commit the transaction - all operations succeeded
            $this->db->commit();

            return [
                'success' => true,
                'receipt_id' => $receiptId,
                'receipt_number' => $receiptNumber,
                'customer_code' => $customer['customer_code'],
                'company_name' => $customer['company_name'],
                'amount_paid' => $amount,
                'new_balance' => $outstandingBalance - $amount,
                'message' => 'Payment recorded successfully'
            ];

        } catch (Exception $e) {
            // Rollback on any error to maintain data integrity
            $this->db->rollback();

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate a unique receipt number
     * 
     * Format: RCP-YYYY-NNNN (e.g., RCP-2026-0001)
     * 
     * @return string Unique receipt number
     * @throws PDOException If query fails
     */
    private function generateReceiptNumber(): string
    {
        $year = date('Y');
        $prefix = "RCP-{$year}-";

        // Get the highest receipt number for current year
        $sql = "
            SELECT receipt_number
            FROM customer_receipts
            WHERE receipt_number LIKE :prefix
            ORDER BY receipt_number DESC
            LIMIT 1
        ";

        $stmt = $this->db->query($sql, ['prefix' => $prefix . '%']);
        $result = $stmt->fetch();

        if ($result) {
            // Extract the numeric part and increment
            $lastNumber = (int) substr($result['receipt_number'], -4);
            $nextNumber = $lastNumber + 1;
        } else {
            // First receipt of the year
            $nextNumber = 1;
        }

        // Format with leading zeros (4 digits)
        return $prefix . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get all customers with their current balances
     * 
     * Returns a list of all customers ordered by customer code.
     * Useful for displaying customer lists and account summaries.
     * 
     * @param string|null $status Filter by status (active, inactive, suspended) or null for all
     * @return array<int, array<string, mixed>> Array of customers
     * @throws PDOException If query fails
     * 
     * @example
     * $model = new CustomerModel();
     * $customers = $model->getAllCustomers('active');
     * foreach ($customers as $customer) {
     *     echo $customer['customer_code'] . ': ' . $customer['company_name'];
     * }
     */
    public function getAllCustomers(?string $status = null): array
    {
        $sql = "
            SELECT 
                id,
                customer_code,
                company_name,
                contact_name,
                email,
                phone,
                address,
                credit_limit,
                outstanding_balance,
                status,
                created_at,
                updated_at
            FROM customers
        ";

        $params = [];

        if ($status !== null) {
            $sql .= " WHERE status = :status";
            $params['status'] = $status;
        }

        $sql .= " ORDER BY customer_code ASC";

        $stmt = $this->db->query($sql, $params);
        $results = $stmt->fetchAll();

        // Cast numeric values for consistency
        return array_map(function ($row) {
            return [
                'id' => (int) $row['id'],
                'customer_code' => $row['customer_code'],
                'company_name' => $row['company_name'],
                'contact_name' => $row['contact_name'],
                'email' => $row['email'],
                'phone' => $row['phone'],
                'address' => $row['address'],
                'credit_limit' => (float) $row['credit_limit'],
                'outstanding_balance' => (float) $row['outstanding_balance'],
                'credit_available' => (float) $row['credit_limit'] - (float) $row['outstanding_balance'],
                'status' => $row['status'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at']
            ];
        }, $results);
    }

    /**
     * Get customer statement with transaction history
     * 
     * Returns detailed customer information along with all payment receipts
     * and outstanding invoices for a complete account statement.
     * 
     * @param int $customerId Customer ID
     * @return array<string, mixed>|null Customer statement data or null if not found
     * @throws PDOException If query fails
     * 
     * @example
     * $model = new CustomerModel();
     * $statement = $model->getCustomerStatement(1);
     * if ($statement) {
     *     echo "Customer: " . $statement['company_name'];
     *     echo "Balance: " . $statement['outstanding_balance'];
     *     foreach ($statement['receipts'] as $receipt) {
     *         echo $receipt['receipt_number'] . ': ' . $receipt['amount_paid'];
     *     }
     * }
     */
    public function getCustomerStatement(int $customerId): ?array
    {
        // Get customer details
        $customerSql = "
            SELECT 
                id,
                customer_code,
                company_name,
                contact_name,
                email,
                phone,
                address,
                credit_limit,
                outstanding_balance,
                status,
                created_at,
                updated_at
            FROM customers
            WHERE id = :customer_id
        ";

        $customerStmt = $this->db->query($customerSql, ['customer_id' => $customerId]);
        $customer = $customerStmt->fetch();

        if (!$customer) {
            return null;
        }

        // Get payment receipts
        $receiptsSql = "
            SELECT 
                id,
                receipt_number,
                amount_paid,
                payment_method,
                payment_reference,
                notes,
                processed_at,
                created_at
            FROM customer_receipts
            WHERE customer_id = :customer_id
            ORDER BY processed_at DESC
        ";

        $receiptsStmt = $this->db->query($receiptsSql, ['customer_id' => $customerId]);
        $receipts = $receiptsStmt->fetchAll();

        // Get linked sales orders (credit invoices)
        $invoicesSql = "
            SELECT 
                id,
                invoice_number,
                total_amount,
                created_at
            FROM sales_orders
            WHERE customer_id = :customer_id
            ORDER BY created_at DESC
        ";

        $invoicesStmt = $this->db->query($invoicesSql, ['customer_id' => $customerId]);
        $invoices = $invoicesStmt->fetchAll();

        return [
            'id' => (int) $customer['id'],
            'customer_code' => $customer['customer_code'],
            'company_name' => $customer['company_name'],
            'contact_name' => $customer['contact_name'],
            'email' => $customer['email'],
            'phone' => $customer['phone'],
            'address' => $customer['address'],
            'credit_limit' => (float) $customer['credit_limit'],
            'outstanding_balance' => (float) $customer['outstanding_balance'],
            'credit_available' => (float) $customer['credit_limit'] - (float) $customer['outstanding_balance'],
            'status' => $customer['status'],
            'created_at' => $customer['created_at'],
            'updated_at' => $customer['updated_at'],
            'receipts' => array_map(function ($receipt) {
                return [
                    'id' => (int) $receipt['id'],
                    'receipt_number' => $receipt['receipt_number'],
                    'amount_paid' => (float) $receipt['amount_paid'],
                    'payment_method' => $receipt['payment_method'],
                    'payment_reference' => $receipt['payment_reference'],
                    'notes' => $receipt['notes'],
                    'processed_at' => $receipt['processed_at'],
                    'created_at' => $receipt['created_at']
                ];
            }, $receipts),
            'invoices' => array_map(function ($invoice) {
                return [
                    'id' => (int) $invoice['id'],
                    'invoice_number' => $invoice['invoice_number'],
                    'total_amount' => (float) $invoice['total_amount'],
                    'created_at' => $invoice['created_at']
                ];
            }, $invoices),
            'total_receipts' => count($receipts),
            'total_invoices' => count($invoices)
        ];
    }

    /**
     * Verify credit availability for a customer order
     * 
     * Checks if a customer has sufficient credit available to place an order
     * of the specified amount. Returns detailed credit information.
     * 
     * @param int $customerId Customer ID
     * @param float $orderTotal Proposed order total amount
     * @return array<string, mixed> Credit verification result with keys:
     *                              - approved: bool
     *                              - credit_limit: float
     *                              - outstanding_balance: float
     *                              - credit_available: float
     *                              - order_total: float
     *                              - remaining_credit: float (after order)
     *                              - message: string
     * @throws PDOException If query fails
     * 
     * @example
     * $model = new CustomerModel();
     * $verification = $model->verifyCreditAvailability(1, 15000.00);
     * if ($verification['approved']) {
     *     echo "Credit approved. Remaining: " . $verification['remaining_credit'];
     * } else {
     *     echo "Credit denied: " . $verification['message'];
     * }
     */
    public function verifyCreditAvailability(int $customerId, float $orderTotal): array
    {
        // Get customer credit information
        $sql = "
            SELECT 
                customer_code,
                company_name,
                credit_limit,
                outstanding_balance,
                status
            FROM customers
            WHERE id = :customer_id
        ";

        $stmt = $this->db->query($sql, ['customer_id' => $customerId]);
        $customer = $stmt->fetch();

        if (!$customer) {
            return [
                'approved' => false,
                'message' => "Customer ID {$customerId} not found"
            ];
        }

        // Check customer status
        if ($customer['status'] !== 'active') {
            return [
                'approved' => false,
                'customer_code' => $customer['customer_code'],
                'company_name' => $customer['company_name'],
                'message' => "Customer account is {$customer['status']}"
            ];
        }

        $creditLimit = (float) $customer['credit_limit'];
        $outstandingBalance = (float) $customer['outstanding_balance'];
        $creditAvailable = $creditLimit - $outstandingBalance;
        $remainingCredit = $creditAvailable - $orderTotal;

        // Verify sufficient credit
        if ($orderTotal > $creditAvailable) {
            return [
                'approved' => false,
                'customer_code' => $customer['customer_code'],
                'company_name' => $customer['company_name'],
                'credit_limit' => $creditLimit,
                'outstanding_balance' => $outstandingBalance,
                'credit_available' => $creditAvailable,
                'order_total' => $orderTotal,
                'credit_shortage' => $orderTotal - $creditAvailable,
                'message' => sprintf(
                    'Insufficient credit: Available %.2f, Required %.2f, Shortage %.2f',
                    $creditAvailable,
                    $orderTotal,
                    $orderTotal - $creditAvailable
                )
            ];
        }

        return [
            'approved' => true,
            'customer_code' => $customer['customer_code'],
            'company_name' => $customer['company_name'],
            'credit_limit' => $creditLimit,
            'outstanding_balance' => $outstandingBalance,
            'credit_available' => $creditAvailable,
            'order_total' => $orderTotal,
            'remaining_credit' => $remainingCredit,
            'message' => 'Credit approved'
        ];
    }

    /**
     * Get customer by ID
     * 
     * @param int $customerId Customer ID
     * @return array<string, mixed>|null Customer data or null if not found
     * @throws PDOException If query fails
     */
    public function getCustomerById(int $customerId): ?array
    {
        $sql = "
            SELECT 
                id,
                customer_code,
                company_name,
                contact_name,
                email,
                phone,
                address,
                credit_limit,
                outstanding_balance,
                status,
                created_at,
                updated_at
            FROM customers
            WHERE id = :customer_id
        ";

        $stmt = $this->db->query($sql, ['customer_id' => $customerId]);
        $customer = $stmt->fetch();

        if (!$customer) {
            return null;
        }

        return [
            'id' => (int) $customer['id'],
            'customer_code' => $customer['customer_code'],
            'company_name' => $customer['company_name'],
            'contact_name' => $customer['contact_name'],
            'email' => $customer['email'],
            'phone' => $customer['phone'],
            'address' => $customer['address'],
            'credit_limit' => (float) $customer['credit_limit'],
            'outstanding_balance' => (float) $customer['outstanding_balance'],
            'credit_available' => (float) $customer['credit_limit'] - (float) $customer['outstanding_balance'],
            'status' => $customer['status'],
            'created_at' => $customer['created_at'],
            'updated_at' => $customer['updated_at']
        ];
    }

    /**
     * Get accounts receivable summary statistics
     * 
     * Returns aggregate statistics for all customer accounts including
     * total receivables, total credit extended, and customer counts.
     * 
     * @return array<string, mixed> Receivables statistics
     * @throws PDOException If query fails
     * 
     * @example
     * $model = new CustomerModel();
     * $stats = $model->getReceivablesStats();
     * echo "Total Receivables: " . $stats['total_receivables'];
     * echo "Active Customers: " . $stats['active_customers'];
     */
    public function getReceivablesStats(): array
    {
        $sql = "
            SELECT 
                COUNT(*) AS total_customers,
                COUNT(CASE WHEN status = 'active' THEN 1 END) AS active_customers,
                COALESCE(SUM(credit_limit), 0) AS total_credit_extended,
                COALESCE(SUM(outstanding_balance), 0) AS total_receivables,
                COALESCE(SUM(credit_limit - outstanding_balance), 0) AS total_credit_available
            FROM customers
        ";

        $stmt = $this->db->query($sql);
        $result = $stmt->fetch();

        return [
            'total_customers' => (int) $result['total_customers'],
            'active_customers' => (int) $result['active_customers'],
            'total_credit_extended' => (float) $result['total_credit_extended'],
            'total_receivables' => (float) $result['total_receivables'],
            'total_credit_available' => (float) $result['total_credit_available'],
            'credit_utilization_percentage' => $result['total_credit_extended'] > 0
                ? round(($result['total_receivables'] / $result['total_credit_extended']) * 100, 2)
                : 0.00
        ];
    }
}

// Made with Bob
</content>
<line_count>827</line_count>
</write_to_file>

---

**User:**

[write_to_file for 'modules/customers/models/CustomerModel.php'] Result:
<file_write_result>
<path>modules/customers/models/CustomerModel.php</path>
<operation>created</operation>
<notice>
<i>You do not need to re-read the file, as you have seen all changes</i>
<i>Proceed with the task using these changes as the new baseline.</i>
</notice>
</file_write_result>

<environment_details>
# VSCode Visible Files
modules\customers\models\CustomerModel.php

# VSCode Open Tabs
modules/home/models/DashboardModel.php,public/assets/css/dashboard.css,modules/home/index.php,modules/home/ROUTING.md,modules/home/INTEGRATION_GUIDE.md,modules/customers/database/schema.sql,modules/customers/models/CustomerModel.php,public/index.php

# Current Time
Current time in ISO 8601 UTC format: 2026-05-16T16:13:18.005Z
User time zone: Asia/Muscat, UTC+4:00

# Current Cost
$0.21

# Review Exclusions
The following file patterns are excluded from code reviews:
- .vscode/**

# Current Mode
<slug>code</slug>
<name>💻 Code</name>
====

REMINDERS

Below is your current list of reminders for this task. Keep them updated as you progress.

| # | Content | Status |
|---|---------|--------|
| 1 | Create modules/customers/database directory structure | Completed |
| 2 | Write database schema script (schema.sql) | Completed |
| 3 | Write CustomerModel.php with transactional payment logic | In Progress |
| 4 | Verify all files are complete and properly formatted | Pending |


IMPORTANT: When task status changes, remember to call the `update_todo_list` tool to update your progress.

</environment_details>

---

**Assistant:**

<update_todo_list>
<todos>
[x] Create modules/customers/database directory structure
[x] Write database schema script (schema.sql)
[x] Write CustomerModel.php with transactional payment logic
[-] Verify all files are complete and properly formatted
</todos>
</update_todo_list>

---

**User:**

[update_todo_list] Result:
Todo list updated successfully.
<environment_details>
# VSCode Visible Files
modules\customers\models\CustomerModel.php

# VSCode Open Tabs
modules/home/models/DashboardModel.php,public/assets/css/dashboard.css,modules/home/index.php,modules/home/ROUTING.md,modules/home/INTEGRATION_GUIDE.md,modules/customers/database/schema.sql,modules/customers/models/CustomerModel.php,public/index.php

# Current Time
Current time in ISO 8601 UTC format: 2026-05-16T16:13:25.377Z
User time zone: Asia/Muscat, UTC+4:00

# Current Cost
$0.30

# Review Exclusions
The following file patterns are excluded from code reviews:
- .vscode/**

# Current Mode
<slug>code</slug>
<name>💻 Code</name>
====

REMINDERS

Below is your current list of reminders for this task. Keep them updated as you progress.

| # | Content | Status |
|---|---------|--------|
| 1 | Create modules/customers/database directory structure | Completed |
| 2 | Write database schema script (schema.sql) | Completed |
| 3 | Write CustomerModel.php with transactional payment logic | Completed |
| 4 | Verify all files are complete and properly formatted | In Progress |


IMPORTANT: When task status changes, remember to call the `update_todo_list` tool to update your progress.

</environment_details>

---

**Assistant:**

Now let me verify both files are complete and properly formatted by reading them back:

<read_file>
<args>
<file>
<path>modules/customers/database/schema.sql</path>
<line_range>1-50</line_range>
<line_range>140-145</line_range>
</file>
<file>
<path>modules/customers/models/CustomerModel.php</path>
<line_range>1-60</line_range>
<line_range>820-827</line_range>
</file>
</args>
</read_file>

---

**User:**

[read_file for 'modules/customers/database/schema.sql', 'modules/customers/models/CustomerModel.php'] Result:
<files>
<file><path>modules/customers/database/schema.sql</path>
<content lines="1-50">
 1 | -- ============================================================================
 2 | -- AmenERP Customer Accounts & Receivables Module - Database Schema
 3 | -- ============================================================================
 4 | -- Description: Clean, optimized MySQL/MariaDB schema for customer management
 5 | --              and accounts receivable tracking with full transactional support
 6 | -- Engine: InnoDB for ACID compliance and foreign key support
 7 | -- Charset: utf8mb4 for full Unicode support
 8 | -- ============================================================================
 9 | 
10 | -- Set character set and collation
11 | SET NAMES utf8mb4;
12 | SET CHARACTER SET utf8mb4;
13 | 
14 | -- ============================================================================
15 | -- DROP EXISTING TABLES (if any)
16 | -- ============================================================================
17 | -- Drop in reverse order of dependencies to avoid foreign key constraint errors
18 | 
19 | DROP TABLE IF EXISTS `customer_receipts`;
20 | DROP TABLE IF EXISTS `customers`;
21 | 
22 | -- ============================================================================
23 | -- Table: customers
24 | -- ============================================================================
25 | -- Purpose: Stores customer master data with credit management capabilities
26 | -- Features:
27 | --   - Unique customer codes for easy reference (e.g., CUST-001)
28 | --   - Credit limit tracking for credit sales authorization
29 | --   - Outstanding balance tracking for accounts receivable management
30 | --   - Full contact information for customer relationship management
31 | -- ============================================================================
32 | 
33 | CREATE TABLE `customers` (
34 |     `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
35 |     `customer_code` VARCHAR(50) NOT NULL COMMENT 'Unique customer identifier (e.g., CUST-001)',
36 |     `company_name` VARCHAR(255) NOT NULL COMMENT 'Customer company or business name',
37 |     `contact_name` VARCHAR(255) NOT NULL COMMENT 'Primary contact person name',
38 |     `email` VARCHAR(255) NULL COMMENT 'Customer email address',
39 |     `phone` VARCHAR(50) NULL COMMENT 'Customer phone number',
40 |     `address` TEXT NULL COMMENT 'Customer physical address',
41 |     `credit_limit` DECIMAL(15, 2) NOT NULL DEFAULT 0.00 COMMENT 'Maximum credit allowed for customer',
42 |     `outstanding_balance` DECIMAL(15, 2) NOT NULL DEFAULT 0.00 COMMENT 'Current accounts receivable balance',
43 |     `status` ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active' COMMENT 'Customer account status',
44 |     `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
45 |     `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
46 |     PRIMARY KEY (`id`),
47 |     UNIQUE KEY `uk_customer_code` (`customer_code`),
48 |     INDEX `idx_company_name` (`company_name`),
49 |     INDEX `idx_status` (`status`),
50 |     INDEX `idx_outstanding_balance` (`outstanding_balance`),
</content>
<content lines="140-145">
140 | -- 
141 | -- Foreign Key Dependencies:
142 | -- - customer_receipts depends on customers
143 | -- - sales_orders.customer_id depends on customers (optional link)
144 | -- 
145 | -- ============================================================================
</content>
</file>
<file><path>modules/customers/models/CustomerModel.php</path><error>Error reading file: Line with index 819 does not exist in 'c:\xampp\htdocs\AmenERP\modules\customers\models\CustomerModel.php'. Note that line indexing is zero-based</error></file>
</files>
<environment_details>
# VSCode Visible Files
modules\customers\models\CustomerModel.php

# VSCode Open Tabs
modules/home/models/DashboardModel.php,public/assets/css/dashboard.css,modules/home/index.php,modules/home/ROUTING.md,modules/home/INTEGRATION_GUIDE.md,modules/customers/database/schema.sql,modules/customers/models/CustomerModel.php,public/index.php

# Current Time
Current time in ISO 8601 UTC format: 2026-05-16T16:13:37.213Z
User time zone: Asia/Muscat, UTC+4:00

# Current Cost
$0.39

# Review Exclusions
The following file patterns are excluded from code reviews:
- .vscode/**

# Current Mode
<slug>code</slug>
<name>💻 Code</name>
====

REMINDERS

Below is your current list of reminders for this task. Keep them updated as you progress.

| # | Content | Status |
|---|---------|--------|
| 1 | Create modules/customers/database directory structure | Completed |
| 2 | Write database schema script (schema.sql) | Completed |
| 3 | Write CustomerModel.php with transactional payment logic | Completed |
| 4 | Verify all files are complete and properly formatted | In Progress |


IMPORTANT: When task status changes, remember to call the `update_todo_list` tool to update your progress.

</environment_details>

---

**Assistant:**

<read_file>
<args>
<file>
<path>modules/customers/models/CustomerModel.php</path>
<line_range>1-60</line_range>
<line_range>800-827</line_range>
</file>
</args>
</read_file>

---

**User:**

[read_file for 'modules/customers/models/CustomerModel.php'. Reading multiple files at once is more efficient for the LLM. If other files are relevant to your current task, please read them simultaneously.] Result:
<files>
<file><path>modules/customers/models/CustomerModel.php</path><error>Error reading file: Line with index 799 does not exist in 'c:\xampp\htdocs\AmenERP\modules\customers\models\CustomerModel.php'. Note that line indexing is zero-based</error></file>
</files>
<environment_details>
# VSCode Visible Files
modules\customers\models\CustomerModel.php

# VSCode Open Tabs
modules/home/models/DashboardModel.php,public/assets/css/dashboard.css,modules/home/index.php,modules/home/ROUTING.md,modules/home/INTEGRATION_GUIDE.md,modules/customers/database/schema.sql,modules/customers/models/CustomerModel.php,public/index.php

# Current Time
Current time in ISO 8601 UTC format: 2026-05-16T16:13:51.761Z
User time zone: Asia/Muscat, UTC+4:00

# Current Cost
$0.49

# Review Exclusions
The following file patterns are excluded from code reviews:
- .vscode/**

# Current Mode
<slug>code</slug>
<name>💻 Code</name>
====

REMINDERS

Below is your current list of reminders for this task. Keep them updated as you progress.

| # | Content | Status |
|---|---------|--------|
| 1 | Create modules/customers/database directory structure | Completed |
| 2 | Write database schema script (schema.sql) | Completed |
| 3 | Write CustomerModel.php with transactional payment logic | Completed |
| 4 | Verify all files are complete and properly formatted | In Progress |


IMPORTANT: When task status changes, remember to call the `update_todo_list` tool to update your progress.

</environment_details>

---

**Assistant:**

<execute_command>
<command>powershell -Command "Get-Content 'modules/customers/models/CustomerModel.php' | Measure-Object -Line"</command>
</execute_command>

---

**User:**

[execute_command for 'powershell -Command "Get-Content 'modules/customers/models/CustomerModel.php' | Measure-Object -Line"'] Result:
Command executed in terminal  within working directory 'c:/xampp/htdocs/AmenERP'. Exit code: 0
Output:

Lines Words Characters Property
----- ----- ---------- --------
  692                          



<environment_details>
# VSCode Visible Files
modules\customers\models\CustomerModel.php

# VSCode Open Tabs
modules/home/models/DashboardModel.php,public/assets/css/dashboard.css,modules/home/index.php,modules/home/ROUTING.md,modules/home/INTEGRATION_GUIDE.md,modules/customers/database/schema.sql,modules/customers/models/CustomerModel.php,public/index.php

# Current Time
Current time in ISO 8601 UTC format: 2026-05-16T16:14:01.369Z
User time zone: Asia/Muscat, UTC+4:00

# Current Cost
$0.58

# Review Exclusions
The following file patterns are excluded from code reviews:
- .vscode/**

# Current Mode
<slug>code</slug>
<name>💻 Code</name>
====

REMINDERS

Below is your current list of reminders for this task. Keep them updated as you progress.

| # | Content | Status |
|---|---------|--------|
| 1 | Create modules/customers/database directory structure | Completed |
| 2 | Write database schema script (schema.sql) | Completed |
| 3 | Write CustomerModel.php with transactional payment logic | Completed |
| 4 | Verify all files are complete and properly formatted | In Progress |


IMPORTANT: When task status changes, remember to call the `update_todo_list` tool to update your progress.

</environment_details>

---

**Assistant:**

<read_file>
<args>
<file>
<path>modules/customers/models/CustomerModel.php</path>
<line_range>1-60</line_range>
<line_range>680-692</line_range>
</file>
</args>
</read_file>

---

**User:**

[read_file for 'modules/customers/models/CustomerModel.php'. Reading multiple files at once is more efficient for the LLM. If other files are relevant to your current task, please read them simultaneously.] Result:
<files>
<file><path>modules/customers/models/CustomerModel.php</path>
<content lines="1-60">
 1 | <?php
 2 | 
 3 | declare(strict_types=1);
 4 | 
 5 | /**
 6 |  * CustomerModel Class
 7 |  * 
 8 |  * Handles all database operations for the customer accounts and receivables module.
 9 |  * Implements ACID-compliant transactions for customer payment processing with
10 |  * automated integration across multiple modules:
11 |  * - Updates customer outstanding balances
12 |  * - Records payment receipts
13 |  * - Creates financial transactions (double-entry bookkeeping)
14 |  * - Updates account balances in the general ledger
15 |  * 
16 |  * All operations are wrapped in database transactions to ensure data integrity.
17 |  * If any step fails, the entire operation is rolled back.
18 |  * 
19 |  * @package AmenERP\Modules\Customers
20 |  * @author Bob
21 |  * @version 1.0.0
22 |  */
23 | class CustomerModel
24 | {
25 |     /**
26 |      * Database instance
27 |      * 
28 |      * @var Database
29 |      */
30 |     private Database $db;
31 | 
32 |     /**
33 |      * Accounts Receivable Asset Account ID
34 |      * This account tracks money owed to us by customers
35 |      * 
36 |      * @var int
37 |      */
38 |     private const ACCOUNTS_RECEIVABLE_ID = 5;
39 | 
40 |     /**
41 |      * Cash Account ID (Primary Liquid Asset)
42 |      * This account tracks cash received from customers
43 |      * 
44 |      * @var int
45 |      */
46 |     private const CASH_ACCOUNT_ID = 1;
47 | 
48 |     /**
49 |      * Constructor
50 |      * Initializes database connection using singleton pattern
51 |      */
52 |     public function __construct()
53 |     {
54 |         $this->db = Database::getInstance();
55 |     }
56 | 
57 |     /**
58 |      * Record a customer payment with full transactional integrity
59 |      * 
60 |      * This is the core payment processing method that executes a complete
</content>
<content lines="680-692">
680 |                 company_name,
681 |                 contact_name,
682 |                 email,
683 |                 phone,
684 |                 address,
685 |                 credit_limit,
686 |                 outstanding_balance,
687 |                 status,
688 |                 created_at,
689 |                 updated_at
690 |             FROM customers
691 |             WHERE id = :customer_id
692 |         ";
</content>
</file>
</files>
<environment_details>
# VSCode Visible Files
modules\customers\models\CustomerModel.php

# VSCode Open Tabs
modules/home/models/DashboardModel.php,public/assets/css/dashboard.css,modules/home/index.php,modules/home/ROUTING.md,modules/home/INTEGRATION_GUIDE.md,modules/customers/database/schema.sql,modules/customers/models/CustomerModel.php,public/index.php

# Current Time
Current time in ISO 8601 UTC format: 2026-05-16T16:14:10.147Z
User time zone: Asia/Muscat, UTC+4:00

# Current Cost
$0.68

# Review Exclusions
The following file patterns are excluded from code reviews:
- .vscode/**

# Current Mode
<slug>code</slug>
<name>💻 Code</name>
====

REMINDERS

Below is your current list of reminders for this task. Keep them updated as you progress.

| # | Content | Status |
|---|---------|--------|
| 1 | Create modules/customers/database directory structure | Completed |
| 2 | Write database schema script (schema.sql) | Completed |
| 3 | Write CustomerModel.php with transactional payment logic | Completed |
| 4 | Verify all files are complete and properly formatted | In Progress |


IMPORTANT: When task status changes, remember to call the `update_todo_list` tool to update your progress.

</environment_details>

---

**Assistant:**

I notice the CustomerModel.php file appears to be incomplete (only 692 lines instead of the expected 827). Let me check what happened and complete the file:

<read_file>
<args>
<file>
<path>modules/customers/models/CustomerModel.php</path>
<line_range>650-692</line_range>
</file>
</args>
</read_file>

---

**User:**

[read_file for 'modules/customers/models/CustomerModel.php'. Reading multiple files at once is more efficient for the LLM. If other files are relevant to your current task, please read them simultaneously.] Result:
<files>
<file><path>modules/customers/models/CustomerModel.php</path>
<content lines="650-692">
650 |                 )
651 |             ];
652 |         }
653 | 
654 |         return [
655 |             'approved' => true,
656 |             'customer_code' => $customer['customer_code'],
657 |             'company_name' => $customer['company_name'],
658 |             'credit_limit' => $creditLimit,
659 |             'outstanding_balance' => $outstandingBalance,
660 |             'credit_available' => $creditAvailable,
661 |             'order_total' => $orderTotal,
662 |             'remaining_credit' => $remainingCredit,
663 |             'message' => 'Credit approved'
664 |         ];
665 |     }
666 | 
667 |     /**
668 |      * Get customer by ID
669 |      * 
670 |      * @param int $customerId Customer ID
671 |      * @return array<string, mixed>|null Customer data or null if not found
672 |      * @throws PDOException If query fails
673 |      */
674 |     public function getCustomerById(int $customerId): ?array
675 |     {
676 |         $sql = "
677 |             SELECT 
678 |                 id,
679 |                 customer_code,
680 |                 company_name,
681 |                 contact_name,
682 |                 email,
683 |                 phone,
684 |                 address,
685 |                 credit_limit,
686 |                 outstanding_balance,
687 |                 status,
688 |                 created_at,
689 |                 updated_at
690 |             FROM customers
691 |             WHERE id = :customer_id
692 |         ";
</content>
</file>
</files>
<environment_details>
# VSCode Visible Files
modules\customers\models\CustomerModel.php

# VSCode Open Tabs
modules/home/models/DashboardModel.php,public/assets/css/dashboard.css,modules/home/index.php,modules/home/ROUTING.md,modules/home/INTEGRATION_GUIDE.md,modules/customers/database/schema.sql,modules/customers/models/CustomerModel.php,public/index.php

# Current Time
Current time in ISO 8601 UTC format: 2026-05-16T16:14:28.441Z
User time zone: Asia/Muscat, UTC+4:00

# Current Cost
$0.78

# Review Exclusions
The following file patterns are excluded from code reviews:
- .vscode/**

# Current Mode
<slug>code</slug>
<name>💻 Code</name>
====

REMINDERS

Below is your current list of reminders for this task. Keep them updated as you progress.

| # | Content | Status |
|---|---------|--------|
| 1 | Create modules/customers/database directory structure | Completed |
| 2 | Write database schema script (schema.sql) | Completed |
| 3 | Write CustomerModel.php with transactional payment logic | Completed |
| 4 | Verify all files are complete and properly formatted | In Progress |


IMPORTANT: When task status changes, remember to call the `update_todo_list` tool to update your progress.

</environment_details>

---

**Assistant:**

The file was truncated. Let me complete it by appending the missing methods:

<insert_content>
<path>modules/customers/models/CustomerModel.php</path>
<line>0</line>
<content>

        $stmt = $this->db->query($sql, ['customer_id' => $customerId]);
        $customer = $stmt->fetch();

        if (!$customer) {
            return null;
        }

        return [
            'id' => (int) $customer['id'],
            'customer_code' => $customer['customer_code'],
            'company_name' => $customer['company_name'],
            'contact_name' => $customer['contact_name'],
            'email' => $customer['email'],
            'phone' => $customer['phone'],
            'address' => $customer['address'],
            'credit_limit' => (float) $customer['credit_limit'],
            'outstanding_balance' => (float) $customer['outstanding_balance'],
            'credit_available' => (float) $customer['credit_limit'] - (float) $customer['outstanding_balance'],
            'status' => $customer['status'],
            'created_at' => $customer['created_at'],
            'updated_at' => $customer['updated_at']
        ];
    }

    /**
     * Get accounts receivable summary statistics
     * 
     * Returns aggregate statistics for all customer accounts including
     * total receivables, total credit extended, and customer counts.
     * 
     * @return array<string, mixed> Receivables statistics
     * @throws PDOException If query fails
     * 
     * @example
     * $model = new CustomerModel();
     * $stats = $model->getReceivablesStats();
     * echo "Total Receivables: " . $stats['total_receivables'];
     * echo "Active Customers: " . $stats['active_customers'];
     */
    public function getReceivablesStats(): array
    {
        $sql = "
            SELECT 
                COUNT(*) AS total_customers,
                COUNT(CASE WHEN status = 'active' THEN 1 END) AS active_customers,
                COALESCE(SUM(credit_limit), 0) AS total_credit_extended,
                COALESCE(SUM(outstanding_balance), 0) AS total_receivables,
                COALESCE(SUM(credit_limit - outstanding_balance), 0) AS total_credit_available
            FROM customers
        ";

        $stmt = $this->db->query($sql);
        $result = $stmt->fetch();

        return [
            'total_customers' => (int) $result['total_customers'],
            'active_customers' => (int) $result['active_customers'],
            'total_credit_extended' => (float) $result['total_credit_extended'],
            'total_receivables' => (float) $result['total_receivables'],
            'total_credit_available' => (float) $result['total_credit_available'],
            'credit_utilization_percentage' => $result['total_credit_extended'] > 0
                ? round(($result['total_receivables'] / $result['total_credit_extended']) * 100, 2)
                : 0.00
        ];
    }
}

// Made with Bob
</content>
</insert_content>

---

**User:**

[insert_content for 'modules/customers/models/CustomerModel.php'] Result:
<file_write_result>
<path>modules/customers/models/CustomerModel.php</path>
<operation>modified</operation>
<notice>
<i>You do not need to re-read the file, as you have seen all changes</i>
<i>Proceed with the task using these changes as the new baseline.</i>
</notice>
</file_write_result>

<environment_details>
# VSCode Visible Files
modules\customers\models\CustomerModel.php

# VSCode Open Tabs
modules/home/models/DashboardModel.php,public/assets/css/dashboard.css,modules/home/index.php,modules/home/ROUTING.md,modules/home/INTEGRATION_GUIDE.md,modules/customers/database/schema.sql,modules/customers/models/CustomerModel.php,public/index.php

# Current Time
Current time in ISO 8601 UTC format: 2026-05-16T16:14:51.353Z
User time zone: Asia/Muscat, UTC+4:00

# Current Cost
$0.88

# Review Exclusions
The following file patterns are excluded from code reviews:
- .vscode/**

# Current Mode
<slug>code</slug>
<name>💻 Code</name>
====

REMINDERS

Below is your current list of reminders for this task. Keep them updated as you progress.

| # | Content | Status |
|---|---------|--------|
| 1 | Create modules/customers/database directory structure | Completed |
| 2 | Write database schema script (schema.sql) | Completed |
| 3 | Write CustomerModel.php with transactional payment logic | Completed |
| 4 | Verify all files are complete and properly formatted | In Progress |


IMPORTANT: When task status changes, remember to call the `update_todo_list` tool to update your progress.

</environment_details>

---

**Assistant:**

<execute_command>
<command>powershell -Command "Get-Content 'modules/customers/models/CustomerModel.php' | Measure-Object -Line"</command>
</execute_command>

---

**User:**

[execute_command for 'powershell -Command "Get-Content 'modules/customers/models/CustomerModel.php' | Measure-Object -Line"'] Result:
Command executed in terminal  within working directory 'c:/xampp/htdocs/AmenERP'. Exit code: 0
Output:

Lines Words Characters Property
----- ----- ---------- --------
  754                          



<environment_details>
# VSCode Visible Files
modules\customers\models\CustomerModel.php

# VSCode Open Tabs
modules/home/models/DashboardModel.php,public/assets/css/dashboard.css,modules/home/index.php,modules/home/ROUTING.md,modules/home/INTEGRATION_GUIDE.md,modules/customers/database/schema.sql,modules/customers/models/CustomerModel.php,public/index.php

# Current Time
Current time in ISO 8601 UTC format: 2026-05-16T16:15:01.402Z
User time zone: Asia/Muscat, UTC+4:00

# Current Cost
$0.99

# Review Exclusions
The following file patterns are excluded from code reviews:
- .vscode/**

# Current Mode
<slug>code</slug>
<name>💻 Code</name>
====

REMINDERS

Below is your current list of reminders for this task. Keep them updated as you progress.

| # | Content | Status |
|---|---------|--------|
| 1 | Create modules/customers/database directory structure | Completed |
| 2 | Write database schema script (schema.sql) | Completed |
| 3 | Write CustomerModel.php with transactional payment logic | Completed |
| 4 | Verify all files are complete and properly formatted | In Progress |


IMPORTANT: When task status changes, remember to call the `update_todo_list` tool to update your progress.

</environment_details>

---

**Assistant:**

Perfect! Now let me verify the final structure by checking both files are complete:

<execute_command>
<command>powershell -Command "Get-Content 'modules/customers/database/schema.sql' | Measure-Object -Line"</command>
</execute_command>

---

**User:**

[execute_command for 'powershell -Command "Get-Content 'modules/customers/database/schema.sql' | Measure-Object -Line"'] Result:
Command executed in terminal  within working directory 'c:/xampp/htdocs/AmenERP'. Exit code: 0
Output:

Lines Words Characters Property
----- ----- ---------- --------
  133                          



<environment_details>
# VSCode Visible Files
modules\customers\models\CustomerModel.php

# VSCode Open Tabs
modules/home/models/DashboardModel.php,public/assets/css/dashboard.css,modules/home/index.php,modules/home/ROUTING.md,modules/home/INTEGRATION_GUIDE.md,modules/customers/database/schema.sql,modules/customers/models/CustomerModel.php,public/index.php

# Current Time
Current time in ISO 8601 UTC format: 2026-05-16T16:15:12.081Z
User time zone: Asia/Muscat, UTC+4:00

# Current Cost
$1.09

# Review Exclusions
The following file patterns are excluded from code reviews:
- .vscode/**

# Current Mode
<slug>code</slug>
<name>💻 Code</name>
====

REMINDERS

Below is your current list of reminders for this task. Keep them updated as you progress.

| # | Content | Status |
|---|---------|--------|
| 1 | Create modules/customers/database directory structure | Completed |
| 2 | Write database schema script (schema.sql) | Completed |
| 3 | Write CustomerModel.php with transactional payment logic | Completed |
| 4 | Verify all files are complete and properly formatted | In Progress |


IMPORTANT: When task status changes, remember to call the `update_todo_list` tool to update your progress.

</environment_details>

---

**Assistant:**

<update_todo_list>
<todos>
[x] Create modules/customers/database directory structure
[x] Write database schema script (schema.sql)
[x] Write CustomerModel.php with transactional payment logic
[x] Verify all files are complete and properly formatted
</todos>
</update_todo_list>

---

**User:**

[TASK RESUMPTION] Resuming task...
<environment_details>
# VSCode Visible Files
modules\customers\models\CustomerModel.php

# VSCode Open Tabs
modules/home/models/DashboardModel.php,public/assets/css/dashboard.css,modules/home/index.php,modules/home/ROUTING.md,modules/home/INTEGRATION_GUIDE.md,modules/customers/database/schema.sql,modules/customers/models/CustomerModel.php,public/index.php

# Current Time
Current time in ISO 8601 UTC format: 2026-05-16T16:15:45.063Z
User time zone: Asia/Muscat, UTC+4:00

# Current Cost
$1.20

# Review Exclusions
The following file patterns are excluded from code reviews:
- .vscode/**

# Current Mode
<slug>code</slug>
<name>💻 Code</name>


# Current Workspace Directory (c:/xampp/htdocs/AmenERP) Files
LICENSE
project-rules.md
README.md
schema.sql
setup-unix.sh
setup-windows.ps1
config/
config/config.php
core/
core/Csrf.php
core/Database.php
core/Router.php
modules/
modules/customers/
modules/customers/database/
modules/customers/database/schema.sql
modules/customers/models/
modules/customers/models/CustomerModel.php
modules/finance/
modules/finance/index.php
modules/finance/controllers/
modules/finance/controllers/RecordExpenseController.php
modules/finance/controllers/RecordIncomeController.php
modules/finance/database/
modules/finance/database/schema.sql
modules/finance/models/
modules/finance/models/FinanceModel.php
modules/finance/models/ReportModel.php
modules/home/
modules/home/index.php
modules/home/INTEGRATION_GUIDE.md
modules/home/ROUTING.md
modules/home/models/
modules/home/models/DashboardModel.php
modules/hr/
modules/hr/index.php
modules/hr/controllers/
modules/hr/controllers/ProcessPayrollController.php
modules/hr/database/
modules/hr/database/schema.sql
modules/hr/models/
modules/hr/models/HRModel.php
modules/inventory/
modules/inventory/add.php
modules/inventory/index.php
modules/inventory/api/
modules/inventory/api/check-sku.php
modules/inventory/api/search.php
modules/inventory/controllers/
modules/inventory/controllers/AddProductController.php
modules/inventory/controllers/DeleteProductController.php
modules/inventory/controllers/EditProductController.php
modules/inventory/database/
modules/inventory/database/schema.sql
modules/inventory/models/
modules/inventory/models/InventoryModel.php
modules/procurement/
modules/procurement/index.php
modules/procurement/controllers/
modules/procurement/controllers/ProcessPurchaseController.php
modules/procurement/database/
modules/procurement/database/schema.sql
modules/procurement/models/
modules/procurement/models/ProcurementModel.php
modules/sales/
modules/sales/index.php
modules/sales/INTEGRATION_GUIDE.md
modules/sales/README.md
modules/sales/ROUTING.md
modules/sales/controllers/
modules/sales/controllers/ProcessSaleController.php
modules/sales/controllers/ViewInvoiceController.php
modules/sales/database/
modules/sales/database/schema.sql
modules/sales/models/
modules/sales/models/SalesModel.php
public/
public/.htaccess
public/index.php
public/assets/
public/assets/css/
public/assets/css/dashboard.css
public/assets/css/main.css
public/assets/css/style.css
public/assets/js/
public/assets/js/hr-payroll.js
public/assets/js/main.js
====

REMINDERS

Below is your current list of reminders for this task. Keep them updated as you progress.

| # | Content | Status |
|---|---------|--------|
| 1 | Create modules/customers/database directory structure | Completed |
| 2 | Write database schema script (schema.sql) | Completed |
| 3 | Write CustomerModel.php with transactional payment logic | Completed |
| 4 | Verify all files are complete and properly formatted | Completed |


IMPORTANT: When task status changes, remember to call the `update_todo_list` tool to update your progress.

</environment_details>

---

**Assistant:**

<attempt_completion>
<result>
Successfully created the complete Customer Accounts & Receivables module with the following deliverables:

## 1. Database Schema (`modules/customers/database/schema.sql`)
✅ **133 lines** - Complete and production-ready
- InnoDB engine with full ACID transactional support
- Two main tables: `customers` and `customer_receipts`
- Modified `sales_orders` table to add optional `customer_id` foreign key
- Proper DROP TABLE logic (child tables before parent tables)
- High-performance indexes on foreign keys, lookups, and customer_code
- Sample seed data with 5 customers (Omani business examples)
- Full UTF-8 support with utf8mb4 charset

**Key Features:**
- `customers` table: customer_code (UNIQUE), company_name, contact_name, credit_limit, outstanding_balance, status
- `customer_receipts` table: receipt_number (UNIQUE), amount_paid, payment_method, processed_at
- Foreign key constraints with ON DELETE RESTRICT for data integrity

## 2. Business Logic Model (`modules/customers/models/CustomerModel.php`)
✅ **754 lines** - Complete with strict type declarations
- Strict PHP type declarations (`declare(strict_types=1);`)
- Database connection via `Database::getInstance()`
- Global account constants: `ACCOUNTS_RECEIVABLE_ID = 5`, `CASH_ACCOUNT_ID = 1`

**Core Transactional Method:**
- `recordCustomerPayment()`: Full ACID-compliant payment processing
  - Validates payment amount and customer balance
  - Updates customer outstanding_balance
  - Inserts payment receipt record
  - Creates financial transaction with double-entry bookkeeping
  - Updates Cash and Accounts Receivable account balances
  - All wrapped in a single PDO transaction with automatic rollback on failure

**Helper Methods:**
- `getAllCustomers()`: Retrieve all customers with optional status filter
- `getCustomerStatement()`: Complete customer account statement with receipts and invoices
- `verifyCreditAvailability()`: Credit limit validation for new orders
- `getCustomerById()`: Fetch individual customer details
- `getReceivablesStats()`: Aggregate statistics for dashboard reporting

Both files follow the project's established patterns, include comprehensive documentation, and are ready for immediate integration into the AmenERP system.
</result>
</attempt_completion>

