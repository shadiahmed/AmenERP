<?php

declare(strict_types=1);

/**
 * InventoryModel Class
 * 
 * Handles all database operations for the inventory module.
 * Provides methods to fetch products, categories, and inventory statistics.
 * All queries use PDO prepared statements for security.
 * 
 * @package AmenERP\Modules\Inventory
 * @author Bob
 * @version 1.0.0
 */
class InventoryModel
{
    /**
     * Database instance
     * 
     * @var Database
     */
    private Database $db;

    /**
     * Low stock threshold
     * Products with quantity below this are considered low stock
     * 
     * @var int
     */
    private const LOW_STOCK_THRESHOLD = 20;

    /**
     * Constructor
     * Initializes the database connection using singleton pattern
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get all products with their category information
     * 
     * Returns a joined result set with product details and category names.
     * Ordered by product name for consistent display.
     * 
     * @return array<int, array<string, mixed>> Array of products with category data
     * @throws PDOException If query fails
     * 
     * @example
     * $model = new InventoryModel();
     * $products = $model->getAllProducts();
     * foreach ($products as $product) {
     *     echo $product['name'] . ' - ' . $product['category_name'];
     * }
     */
    public function getAllProducts(): array
    {
        $sql = "
            SELECT 
                p.id,
                p.name,
                p.sku,
                p.quantity,
                p.unit_price,
                p.status,
                p.created_at,
                p.updated_at,
                c.id AS category_id,
                c.name AS category_name
            FROM products p
            INNER JOIN categories c ON p.category_id = c.id
            ORDER BY p.name ASC
        ";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Get products filtered by status
     * 
     * @param string $status Product status ('active', 'inactive', 'discontinued')
     * @return array<int, array<string, mixed>> Array of filtered products
     * @throws PDOException If query fails
     */
    public function getProductsByStatus(string $status): array
    {
        $sql = "
            SELECT 
                p.id,
                p.name,
                p.sku,
                p.quantity,
                p.unit_price,
                p.status,
                p.created_at,
                p.updated_at,
                c.id AS category_id,
                c.name AS category_name
            FROM products p
            INNER JOIN categories c ON p.category_id = c.id
            WHERE p.status = :status
            ORDER BY p.name ASC
        ";

        $stmt = $this->db->query($sql, ['status' => $status]);
        return $stmt->fetchAll();
    }

    /**
     * Get products by category
     * 
     * @param int $categoryId Category ID to filter by
     * @return array<int, array<string, mixed>> Array of products in the category
     * @throws PDOException If query fails
     */
    public function getProductsByCategory(int $categoryId): array
    {
        $sql = "
            SELECT 
                p.id,
                p.name,
                p.sku,
                p.quantity,
                p.unit_price,
                p.status,
                p.created_at,
                p.updated_at,
                c.id AS category_id,
                c.name AS category_name
            FROM products p
            INNER JOIN categories c ON p.category_id = c.id
            WHERE p.category_id = :category_id
            ORDER BY p.name ASC
        ";

        $stmt = $this->db->query($sql, ['category_id' => $categoryId]);
        return $stmt->fetchAll();
    }

    /**
     * Get inventory summary statistics
     * 
     * Returns key metrics for the inventory dashboard:
     * - total_items: Total number of products in inventory
     * - total_value: Total monetary value of all inventory (quantity × unit_price)
     * - low_stock_count: Number of products below the low stock threshold
     * - active_products: Number of active products
     * - total_categories: Number of product categories
     * 
     * @return array<string, mixed> Associative array of statistics
     * @throws PDOException If query fails
     * 
     * @example
     * $model = new InventoryModel();
     * $stats = $model->getSummaryStats();
     * echo "Total Items: " . $stats['total_items'];
     * echo "Low Stock: " . $stats['low_stock_count'];
     */
    public function getSummaryStats(): array
    {
        $sql = "
            SELECT 
                COUNT(p.id) AS total_items,
                COALESCE(SUM(p.quantity * p.unit_price), 0) AS total_value,
                SUM(CASE WHEN p.quantity < :low_stock_threshold THEN 1 ELSE 0 END) AS low_stock_count,
                SUM(CASE WHEN p.status = 'active' THEN 1 ELSE 0 END) AS active_products,
                (SELECT COUNT(DISTINCT id) FROM categories) AS total_categories
            FROM products p
        ";

        $stmt = $this->db->query($sql, ['low_stock_threshold' => self::LOW_STOCK_THRESHOLD]);
        $result = $stmt->fetch();

        // Ensure numeric types are properly cast
        return [
            'total_items' => (int) $result['total_items'],
            'total_value' => (float) $result['total_value'],
            'low_stock_count' => (int) $result['low_stock_count'],
            'active_products' => (int) $result['active_products'],
            'total_categories' => (int) $result['total_categories']
        ];
    }

    /**
     * Get low stock products
     * 
     * Returns products that are below the low stock threshold.
     * Useful for generating alerts and reorder reports.
     * 
     * @return array<int, array<string, mixed>> Array of low stock products
     * @throws PDOException If query fails
     */
    public function getLowStockProducts(): array
    {
        $sql = "
            SELECT 
                p.id,
                p.name,
                p.sku,
                p.quantity,
                p.unit_price,
                p.status,
                c.name AS category_name
            FROM products p
            INNER JOIN categories c ON p.category_id = c.id
            WHERE p.quantity < :low_stock_threshold
            AND p.status = 'active'
            ORDER BY p.quantity ASC, p.name ASC
        ";

        $stmt = $this->db->query($sql, ['low_stock_threshold' => self::LOW_STOCK_THRESHOLD]);
        return $stmt->fetchAll();
    }

    /**
     * Get all categories
     * 
     * @return array<int, array<string, mixed>> Array of all categories
     * @throws PDOException If query fails
     */
    public function getAllCategories(): array
    {
        $sql = "
            SELECT 
                id,
                name,
                description,
                created_at,
                updated_at
            FROM categories
            ORDER BY name ASC
        ";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Get category statistics
     * 
     * Returns product count and total value for each category.
     * 
     * @return array<int, array<string, mixed>> Array of category statistics
     * @throws PDOException If query fails
     */
    public function getCategoryStats(): array
    {
        $sql = "
            SELECT 
                c.id,
                c.name,
                COUNT(p.id) AS product_count,
                COALESCE(SUM(p.quantity), 0) AS total_quantity,
                COALESCE(SUM(p.quantity * p.unit_price), 0) AS total_value
            FROM categories c
            LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
            GROUP BY c.id, c.name
            ORDER BY c.name ASC
        ";

        $stmt = $this->db->query($sql);
        $results = $stmt->fetchAll();

        // Cast numeric values
        return array_map(function ($row) {
            return [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'product_count' => (int) $row['product_count'],
                'total_quantity' => (int) $row['total_quantity'],
                'total_value' => (float) $row['total_value']
            ];
        }, $results);
    }

    /**
     * Get a single product by ID
     * 
     * @param int $productId Product ID
     * @return array<string, mixed>|null Product data or null if not found
     * @throws PDOException If query fails
     */
    public function getProductById(int $productId): ?array
    {
        $sql = "
            SELECT 
                p.id,
                p.name,
                p.sku,
                p.quantity,
                p.unit_price,
                p.status,
                p.created_at,
                p.updated_at,
                c.id AS category_id,
                c.name AS category_name
            FROM products p
            INNER JOIN categories c ON p.category_id = c.id
            WHERE p.id = :product_id
        ";

        $stmt = $this->db->query($sql, ['product_id' => $productId]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    /**
     * Search products by name or SKU
     * 
     * Performs a case-insensitive search on product name and SKU.
     * 
     * @param string $searchTerm Search term
     * @return array<int, array<string, mixed>> Array of matching products
     * @throws PDOException If query fails
     */
    public function searchProducts(string $searchTerm): array
    {
        $sql = "
            SELECT
                p.id,
                p.name,
                p.sku,
                p.quantity,
                p.unit_price,
                p.status,
                p.created_at,
                p.updated_at,
                c.id AS category_id,
                c.name AS category_name
            FROM products p
            INNER JOIN categories c ON p.category_id = c.id
            WHERE p.name LIKE :search_name
            OR p.sku LIKE :search_sku
            ORDER BY p.name ASC
        ";

        $searchPattern = '%' . $searchTerm . '%';
        $stmt = $this->db->query($sql, [
            'search_name' => $searchPattern,
            'search_sku' => $searchPattern
        ]);
        return $stmt->fetchAll();
    }

    /**
     * Add a new product to inventory
     * 
     * Inserts a new product record with automatic timestamp generation.
     * Uses PDO prepared statements for security against SQL injection.
     * 
     * @param array<string, mixed> $data Product data array with keys:
     *                                    - name: Product name (string)
     *                                    - sku: Stock Keeping Unit (string)
     *                                    - category_id: Category ID (int)
     *                                    - quantity: Stock quantity (int)
     *                                    - unit_price: Price per unit (float)
     *                                    - status: Product status (string: active|inactive|discontinued)
     * @return bool True on success, false on failure
     * @throws PDOException If database operation fails
     * 
     * @example
     * $model = new InventoryModel();
     * $result = $model->addProduct([
     *     'name' => 'Laptop Computer',
     *     'sku' => 'TECH-001',
     *     'category_id' => 1,
     *     'quantity' => 50,
     *     'unit_price' => 999.99,
     *     'status' => 'active'
     * ]);
     */
    public function addProduct(array $data): bool
    {
        $sql = "
            INSERT INTO products (
                name,
                sku,
                category_id,
                quantity,
                unit_price,
                status,
                created_at,
                updated_at
            ) VALUES (
                :name,
                :sku,
                :category_id,
                :quantity,
                :unit_price,
                :status,
                NOW(),
                NOW()
            )
        ";

        $params = [
            'name' => $data['name'],
            'sku' => $data['sku'],
            'category_id' => $data['category_id'],
            'quantity' => $data['quantity'],
            'unit_price' => $data['unit_price'],
            'status' => $data['status']
        ];

        $stmt = $this->db->query($sql, $params);
        
        // Return true if at least one row was affected
        return $stmt->rowCount() > 0;
    }

    /**
     * Update an existing product in inventory
     * 
     * Updates product information with automatic timestamp generation.
     * Uses PDO prepared statements for security against SQL injection.
     * 
     * @param int $productId Product ID to update
     * @param array<string, mixed> $data Product data array with keys:
     *                                    - name: Product name (string)
     *                                    - sku: Stock Keeping Unit (string)
     *                                    - category_id: Category ID (int)
     *                                    - quantity: Stock quantity (int)
     *                                    - unit_price: Price per unit (float)
     *                                    - status: Product status (string: active|inactive|discontinued)
     * @return bool True on success, false if no rows affected or product not found
     * @throws PDOException If database operation fails
     * 
     * @example
     * $model = new InventoryModel();
     * $result = $model->updateProduct(5, [
     *     'name' => 'Updated Laptop Computer',
     *     'sku' => 'TECH-001-V2',
     *     'category_id' => 1,
     *     'quantity' => 75,
     *     'unit_price' => 1099.99,
     *     'status' => 'active'
     * ]);
     */
    public function updateProduct(int $productId, array $data): bool
    {
        $sql = "
            UPDATE products
            SET
                name = :name,
                sku = :sku,
                category_id = :category_id,
                quantity = :quantity,
                unit_price = :unit_price,
                status = :status,
                updated_at = NOW()
            WHERE id = :product_id
        ";

        $params = [
            'product_id' => $productId,
            'name' => $data['name'],
            'sku' => $data['sku'],
            'category_id' => $data['category_id'],
            'quantity' => $data['quantity'],
            'unit_price' => $data['unit_price'],
            'status' => $data['status']
        ];

        $stmt = $this->db->query($sql, $params);
        
        // Return true if at least one row was affected
        return $stmt->rowCount() > 0;
    }

    /**
     * Delete a product from inventory
     * 
     * Permanently removes a product record from the database.
     * Uses PDO prepared statements for security against SQL injection.
     * 
     * WARNING: This is a hard delete operation. Consider implementing soft deletes
     * (status = 'deleted') for production systems to maintain audit trails.
     * 
     * @param int $productId Product ID to delete
     * @return bool True on success, false if no rows affected or product not found
     * @throws PDOException If database operation fails (e.g., foreign key constraint)
     * 
     * @example
     * $model = new InventoryModel();
     * try {
     *     $result = $model->deleteProduct(5);
     *     if ($result) {
     *         echo "Product deleted successfully";
     *     }
     * } catch (PDOException $e) {
     *     echo "Cannot delete: " . $e->getMessage();
     * }
     */
    public function deleteProduct(int $productId): bool
    {
        $sql = "
            DELETE FROM products
            WHERE id = :product_id
        ";

        $params = [
            'product_id' => $productId
        ];

        $stmt = $this->db->query($sql, $params);
        
        // Return true if at least one row was affected
        return $stmt->rowCount() > 0;
    }

    /**
     * Check if a SKU already exists in the database
     *
     * Used for real-time validation to prevent duplicate SKU entries.
     * Performs a case-insensitive search for the SKU.
     *
     * @param string $sku SKU to check
     * @param int|null $excludeProductId Optional product ID to exclude from check (for edit operations)
     * @return bool True if SKU exists, false otherwise
     * @throws PDOException If query fails
     *
     * @example
     * $model = new InventoryModel();
     * if ($model->skuExists('PROD-001')) {
     *     echo "SKU already exists";
     * }
     */
    public function skuExists(string $sku, ?int $excludeProductId = null): bool
    {
        $sql = "
            SELECT COUNT(*) as count
            FROM products
            WHERE UPPER(sku) = UPPER(:sku)
        ";

        $params = ['sku' => $sku];

        // Exclude specific product ID (useful for edit operations)
        if ($excludeProductId !== null) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeProductId;
        }

        $stmt = $this->db->query($sql, $params);
        $result = $stmt->fetch();

        return (int) $result['count'] > 0;
    }

    /**
     * Get the low stock threshold constant
     *
     * @return int Low stock threshold value
     */
    public function getLowStockThreshold(): int
    {
        return self::LOW_STOCK_THRESHOLD;
    }
}

// Made with Bob