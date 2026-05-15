<?php

declare(strict_types=1);

/**
 * AmenERP Configuration File
 * 
 * This file contains all application configuration constants.
 * Following the Zero Framework rule: No .env loaders, just clean native PHP constants.
 * 
 * SECURITY NOTE: In production, move this file outside the public directory
 * and adjust the include path in your entry point (public/index.php).
 * 
 * @package AmenERP\Config
 * @author Bob
 * @version 1.0.0
 */

// ============================================================================
// DATABASE CONFIGURATION
// ============================================================================

/**
 * Database host (typically 'localhost' for XAMPP)
 */
define('DB_HOST', 'localhost');

/**
 * Database name
 */
define('DB_NAME', 'amenerp_db');

/**
 * Database username
 * 
 * PRODUCTION: Change this to a dedicated database user with limited privileges
 */
define('DB_USER', 'root');

/**
 * Database password
 * 
 * PRODUCTION: Use a strong password and never commit it to version control
 */
define('DB_PASS', '');

/**
 * Database character set
 * Use utf8mb4 for full Unicode support including emojis
 */
define('DB_CHARSET', 'utf8mb4');

// ============================================================================
// APPLICATION CONFIGURATION
// ============================================================================

/**
 * Base URL of the application
 * 
 * DEVELOPMENT: http://localhost/AmenERP/public
 * PRODUCTION: https://yourdomain.com
 * 
 * No trailing slash
 */
define('BASE_URL', 'http://localhost/AmenERP/public');

/**
 * Application environment
 * 
 * Values: 'development' or 'production'
 * This affects error reporting and logging behavior
 */
define('ENVIRONMENT', 'development');

/**
 * Application timezone
 * See: https://www.php.net/manual/en/timezones.php
 */
define('APP_TIMEZONE', 'Africa/Cairo');

/**
 * Application name
 */
define('APP_NAME', 'AmenERP');

/**
 * Application version
 */
define('APP_VERSION', '1.0.0');

// ============================================================================
// ERROR REPORTING & LOGGING
// ============================================================================

if (ENVIRONMENT === 'development') {
    // Development: Display all errors for debugging
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    // Production: Log errors but don't display them
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/../logs/php-errors.log');
}

// ============================================================================
// SECURITY SETTINGS
// ============================================================================

/**
 * Session configuration
 */
ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_secure', ENVIRONMENT === 'production' ? '1' : '0');
ini_set('session.cookie_samesite', 'Strict');

/**
 * Set default timezone
 */
date_default_timezone_set(APP_TIMEZONE);

// ============================================================================
// PATH CONSTANTS
// ============================================================================

/**
 * Root directory path
 */
define('ROOT_PATH', dirname(__DIR__));

/**
 * Core directory path
 */
define('CORE_PATH', ROOT_PATH . '/core');

/**
 * Modules directory path
 */
define('MODULES_PATH', ROOT_PATH . '/modules');

/**
 * Public directory path
 */
define('PUBLIC_PATH', ROOT_PATH . '/public');

/**
 * Config directory path
 */
define('CONFIG_PATH', ROOT_PATH . '/config');

// ============================================================================
// PRODUCTION DEPLOYMENT CHECKLIST
// ============================================================================

/*
 * Before deploying to production:
 * 
 * 1. Change ENVIRONMENT to 'production'
 * 2. Update BASE_URL to your production domain
 * 3. Set strong DB_PASS and create dedicated database user
 * 4. Move this config.php outside the web root
 * 5. Enable HTTPS and set session.cookie_secure to '1'
 * 6. Create logs directory with write permissions
 * 7. Review and adjust session settings
 * 8. Set up proper file permissions (644 for files, 755 for directories)
 * 9. Disable directory listing in web server configuration
 * 10. Set up database backups
 */

// Made with Bob
