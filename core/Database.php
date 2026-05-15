<?php

declare(strict_types=1);

/**
 * Database Class - Singleton PDO Wrapper
 * 
 * Provides a secure database connection using PDO with prepared statements
 * to prevent SQL injection attacks. Follows the Singleton pattern to ensure
 * only one database connection exists throughout the application lifecycle.
 * 
 * @package AmenERP\Core
 * @author Bob
 * @version 1.0.0
 */
class Database
{
    /**
     * Singleton instance
     * 
     * @var Database|null
     */
    private static ?Database $instance = null;

    /**
     * PDO connection object
     * 
     * @var PDO|null
     */
    private ?PDO $connection = null;

    /**
     * Database configuration
     * 
     * @var array<string, string>
     */
    private array $config = [];

    /**
     * Private constructor to prevent direct instantiation
     * Establishes PDO connection with secure settings
     * 
     * @throws PDOException If connection fails
     */
    private function __construct()
    {
        // Load configuration from config.php
        if (!defined('DB_HOST')) {
            throw new RuntimeException('Database configuration not loaded. Include config.php first.');
        }

        $this->config = [
            'host' => DB_HOST,
            'name' => DB_NAME,
            'user' => DB_USER,
            'pass' => DB_PASS,
            'charset' => DB_CHARSET
        ];

        $this->connect();
    }

    /**
     * Establish PDO connection with security settings
     * 
     * @return void
     * @throws PDOException If connection fails
     */
    private function connect(): void
    {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $this->config['host'],
            $this->config['name'],
            $this->config['charset']
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => false
        ];

        try {
            $this->connection = new PDO(
                $dsn,
                $this->config['user'],
                $this->config['pass'],
                $options
            );
        } catch (PDOException $e) {
            // Log error in production, display in development
            if (ENVIRONMENT === 'development') {
                throw new PDOException('Database connection failed: ' . $e->getMessage());
            } else {
                error_log('Database connection failed: ' . $e->getMessage());
                throw new PDOException('Database connection failed. Please contact support.');
            }
        }
    }

    /**
     * Get singleton instance of Database
     * 
     * @return Database The singleton instance
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Execute a prepared statement query
     * 
     * This method automatically handles parameter binding to prevent SQL injection.
     * Use named placeholders (:name) or positional placeholders (?) in your SQL.
     * 
     * @param string $sql SQL query with placeholders
     * @param array<int|string, mixed> $params Parameters to bind (optional)
     * @return PDOStatement Executed statement object
     * @throws PDOException If query execution fails
     * 
     * @example
     * // Named placeholders
     * $db->query('SELECT * FROM users WHERE email = :email', ['email' => $email]);
     * 
     * // Positional placeholders
     * $db->query('SELECT * FROM users WHERE id = ?', [$userId]);
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            // Log error in production, display in development
            if (ENVIRONMENT === 'development') {
                throw new PDOException('Query failed: ' . $e->getMessage() . ' | SQL: ' . $sql);
            } else {
                error_log('Query failed: ' . $e->getMessage() . ' | SQL: ' . $sql);
                throw new PDOException('Database query failed. Please try again.');
            }
        }
    }

    /**
     * Get the last inserted ID
     * 
     * @return string The last insert ID
     */
    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }

    /**
     * Begin a database transaction
     * 
     * @return bool True on success
     */
    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    /**
     * Commit the current transaction
     * 
     * @return bool True on success
     */
    public function commit(): bool
    {
        return $this->connection->commit();
    }

    /**
     * Roll back the current transaction
     * 
     * @return bool True on success
     */
    public function rollback(): bool
    {
        return $this->connection->rollBack();
    }

    /**
     * Get the raw PDO connection (use sparingly)
     * 
     * @return PDO The PDO connection object
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }

    /**
     * Prevent cloning of the singleton instance
     * 
     * @return void
     */
    private function __clone(): void
    {
        // Prevent cloning
    }

    /**
     * Prevent unserialization of the singleton instance
     * 
     * @return void
     * @throws Exception Always throws exception
     */
    public function __wakeup(): void
    {
        throw new Exception('Cannot unserialize singleton');
    }
}

// Made with Bob
