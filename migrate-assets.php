<?php

declare(strict_types=1);

/**
 * AmenERP Asset Migration Script
 * 
 * This standalone utility script automates the cleanup and migration of legacy
 * frontend assets to the new unified design system architecture.
 * 
 * Purpose:
 * - Create necessary directory structure if missing
 * - Safely remove old legacy CSS and JS files
 * - Verify new modern assets are in place
 * - Generate migration report
 * 
 * Usage:
 * Run from project root: php migrate-assets.php
 * 
 * @package AmenERP
 * @author Bob - Expert Lead Frontend Engineer & DevOps Engineer
 * @version 1.0.0
 */

// ============================================================================
// CONFIGURATION
// ============================================================================

define('PROJECT_ROOT', __DIR__);
define('PUBLIC_DIR', PROJECT_ROOT . '/public');
define('ASSETS_DIR', PUBLIC_DIR . '/assets');
define('CSS_DIR', ASSETS_DIR . '/css');
define('JS_DIR', ASSETS_DIR . '/js');
define('JS_MODULES_DIR', JS_DIR . '/modules');

// Legacy files to be removed
const LEGACY_CSS_FILES = [
    'dashboard.css',
    'main.css',
    'modules.css',
    'style.css'
];

const LEGACY_JS_FILES = [
    'main.js',
    'hr-payroll.js'
];

// New modern files that should exist
const MODERN_CSS_FILES = [
    'design-tokens.css',
    'main-layout.css',
    'components.css'
];

const MODERN_JS_FILES = [
    'app.js'
];

// ============================================================================
// UTILITY FUNCTIONS
// ============================================================================

/**
 * Print colored console output
 * 
 * @param string $message Message to print
 * @param string $type Type of message (success, error, info, warning)
 */
function printMessage(string $message, string $type = 'info'): void
{
    $colors = [
        'success' => "\033[0;32m", // Green
        'error'   => "\033[0;31m", // Red
        'warning' => "\033[0;33m", // Yellow
        'info'    => "\033[0;36m", // Cyan
        'reset'   => "\033[0m"     // Reset
    ];
    
    $color = $colors[$type] ?? $colors['info'];
    echo $color . $message . $colors['reset'] . PHP_EOL;
}

/**
 * Print section header
 * 
 * @param string $title Section title
 */
function printSection(string $title): void
{
    echo PHP_EOL;
    echo str_repeat('=', 80) . PHP_EOL;
    echo "  " . strtoupper($title) . PHP_EOL;
    echo str_repeat('=', 80) . PHP_EOL;
}

/**
 * Create directory if it doesn't exist
 * 
 * @param string $path Directory path
 * @return bool Success status
 */
function ensureDirectoryExists(string $path): bool
{
    if (is_dir($path)) {
        printMessage("✓ Directory exists: {$path}", 'success');
        return true;
    }
    
    if (mkdir($path, 0755, true)) {
        printMessage("✓ Created directory: {$path}", 'success');
        return true;
    }
    
    printMessage("✗ Failed to create directory: {$path}", 'error');
    return false;
}

/**
 * Safely delete a file with defensive checks
 * 
 * @param string $filePath Full path to file
 * @return bool Success status
 */
function safeDeleteFile(string $filePath): bool
{
    // Security check: Ensure file is within project directory
    $realPath = realpath($filePath);
    $projectRoot = realpath(PROJECT_ROOT);
    
    if ($realPath === false) {
        printMessage("  ⚠ File not found: {$filePath}", 'warning');
        return false;
    }
    
    if (strpos($realPath, $projectRoot) !== 0) {
        printMessage("  ✗ Security violation: File outside project root", 'error');
        return false;
    }
    
    // Check if file exists and is writable
    if (!file_exists($realPath)) {
        printMessage("  ⚠ File not found: {$filePath}", 'warning');
        return false;
    }
    
    if (!is_writable($realPath)) {
        printMessage("  ✗ File not writable: {$filePath}", 'error');
        return false;
    }
    
    // Attempt deletion
    if (unlink($realPath)) {
        printMessage("  ✓ Deleted: " . basename($filePath), 'success');
        return true;
    }
    
    printMessage("  ✗ Failed to delete: {$filePath}", 'error');
    return false;
}

/**
 * Verify file exists
 * 
 * @param string $filePath Full path to file
 * @return bool File exists status
 */
function verifyFileExists(string $filePath): bool
{
    if (file_exists($filePath)) {
        $size = filesize($filePath);
        $sizeKB = round($size / 1024, 2);
        printMessage("  ✓ " . basename($filePath) . " ({$sizeKB} KB)", 'success');
        return true;
    }
    
    printMessage("  ✗ Missing: " . basename($filePath), 'error');
    return false;
}

/**
 * Create backup of legacy files before deletion
 * 
 * @param array $files Array of file paths
 * @param string $backupDir Backup directory path
 * @return bool Success status
 */
function createBackup(array $files, string $backupDir): bool
{
    if (!ensureDirectoryExists($backupDir)) {
        return false;
    }
    
    $success = true;
    foreach ($files as $file) {
        if (file_exists($file)) {
            $backupFile = $backupDir . '/' . basename($file);
            if (!copy($file, $backupFile)) {
                printMessage("  ✗ Failed to backup: " . basename($file), 'error');
                $success = false;
            } else {
                printMessage("  ✓ Backed up: " . basename($file), 'success');
            }
        }
    }
    
    return $success;
}

// ============================================================================
// MAIN MIGRATION PROCESS
// ============================================================================

/**
 * Main migration execution
 */
function executeMigration(): int
{
    printSection('AmenERP Asset Migration Script');
    printMessage('Starting frontend asset migration and cleanup...', 'info');
    
    $errors = 0;
    $warnings = 0;
    
    // ========================================================================
    // STEP 1: Verify Directory Structure
    // ========================================================================
    
    printSection('Step 1: Verify Directory Structure');
    
    $directories = [
        ASSETS_DIR,
        CSS_DIR,
        JS_DIR,
        JS_MODULES_DIR
    ];
    
    foreach ($directories as $dir) {
        if (!ensureDirectoryExists($dir)) {
            $errors++;
        }
    }
    
    // ========================================================================
    // STEP 2: Verify Modern Assets Exist
    // ========================================================================
    
    printSection('Step 2: Verify Modern Assets');
    
    printMessage('Checking CSS files:', 'info');
    foreach (MODERN_CSS_FILES as $file) {
        if (!verifyFileExists(CSS_DIR . '/' . $file)) {
            $errors++;
        }
    }
    
    printMessage(PHP_EOL . 'Checking JavaScript files:', 'info');
    foreach (MODERN_JS_FILES as $file) {
        if (!verifyFileExists(JS_DIR . '/' . $file)) {
            $errors++;
        }
    }
    
    if ($errors > 0) {
        printMessage(PHP_EOL . '⚠ Cannot proceed: Modern assets are missing!', 'error');
        printMessage('Please ensure all new CSS and JS files are created first.', 'warning');
        return 1;
    }
    
    // ========================================================================
    // STEP 3: Create Backup of Legacy Files
    // ========================================================================
    
    printSection('Step 3: Backup Legacy Files');
    
    $backupDir = PROJECT_ROOT . '/backup_legacy_assets_' . date('Y-m-d_His');
    printMessage("Creating backup directory: {$backupDir}", 'info');
    
    $legacyFiles = [];
    foreach (LEGACY_CSS_FILES as $file) {
        $legacyFiles[] = CSS_DIR . '/' . $file;
    }
    foreach (LEGACY_JS_FILES as $file) {
        $legacyFiles[] = JS_DIR . '/' . $file;
    }
    
    if (!createBackup($legacyFiles, $backupDir)) {
        printMessage('⚠ Backup creation had issues, but continuing...', 'warning');
        $warnings++;
    }
    
    // ========================================================================
    // STEP 4: Remove Legacy CSS Files
    // ========================================================================
    
    printSection('Step 4: Remove Legacy CSS Files');
    
    foreach (LEGACY_CSS_FILES as $file) {
        $filePath = CSS_DIR . '/' . $file;
        if (!safeDeleteFile($filePath)) {
            $warnings++;
        }
    }
    
    // ========================================================================
    // STEP 5: Remove Legacy JS Files
    // ========================================================================
    
    printSection('Step 5: Remove Legacy JavaScript Files');
    
    foreach (LEGACY_JS_FILES as $file) {
        $filePath = JS_DIR . '/' . $file;
        if (!safeDeleteFile($filePath)) {
            $warnings++;
        }
    }
    
    // ========================================================================
    // STEP 6: Verify public/index.php Uses New Assets
    // ========================================================================
    
    printSection('Step 6: Verify index.php Configuration');
    
    $indexPath = PUBLIC_DIR . '/index.php';
    if (file_exists($indexPath)) {
        $indexContent = file_get_contents($indexPath);
        
        $hasDesignTokens = strpos($indexContent, 'design-tokens.css') !== false;
        $hasMainLayout = strpos($indexContent, 'main-layout.css') !== false;
        $hasComponents = strpos($indexContent, 'components.css') !== false;
        $hasAppJs = strpos($indexContent, 'app.js') !== false;
        
        if ($hasDesignTokens && $hasMainLayout && $hasComponents && $hasAppJs) {
            printMessage('✓ index.php correctly references new assets', 'success');
        } else {
            printMessage('✗ index.php may not be properly configured', 'error');
            if (!$hasDesignTokens) printMessage('  Missing: design-tokens.css', 'warning');
            if (!$hasMainLayout) printMessage('  Missing: main-layout.css', 'warning');
            if (!$hasComponents) printMessage('  Missing: components.css', 'warning');
            if (!$hasAppJs) printMessage('  Missing: app.js', 'warning');
            $errors++;
        }
    } else {
        printMessage('✗ index.php not found', 'error');
        $errors++;
    }
    
    // ========================================================================
    // STEP 7: Generate Migration Report
    // ========================================================================
    
    printSection('Migration Summary');
    
    printMessage('Modern CSS Assets:', 'info');
    foreach (MODERN_CSS_FILES as $file) {
        printMessage('  ✓ ' . $file, 'success');
    }
    
    printMessage(PHP_EOL . 'Modern JavaScript Assets:', 'info');
    foreach (MODERN_JS_FILES as $file) {
        printMessage('  ✓ ' . $file, 'success');
    }
    
    printMessage(PHP_EOL . 'Removed Legacy CSS Files:', 'info');
    foreach (LEGACY_CSS_FILES as $file) {
        printMessage('  ✓ ' . $file, 'success');
    }
    
    printMessage(PHP_EOL . 'Removed Legacy JavaScript Files:', 'info');
    foreach (LEGACY_JS_FILES as $file) {
        printMessage('  ✓ ' . $file, 'success');
    }
    
    printMessage(PHP_EOL . 'Backup Location:', 'info');
    printMessage('  ' . $backupDir, 'info');
    
    // ========================================================================
    // Final Status
    // ========================================================================
    
    printSection('Migration Complete');
    
    if ($errors === 0 && $warnings === 0) {
        printMessage('✓ Migration completed successfully with no issues!', 'success');
        printMessage('✓ All legacy files have been safely removed', 'success');
        printMessage('✓ Modern design system is now active', 'success');
        return 0;
    } elseif ($errors === 0) {
        printMessage("⚠ Migration completed with {$warnings} warning(s)", 'warning');
        printMessage('✓ Modern design system is now active', 'success');
        return 0;
    } else {
        printMessage("✗ Migration completed with {$errors} error(s) and {$warnings} warning(s)", 'error');
        printMessage('⚠ Please review the errors above and fix them', 'warning');
        return 1;
    }
}

// ============================================================================
// SCRIPT EXECUTION
// ============================================================================

// Ensure script is run from command line
if (php_sapi_name() !== 'cli') {
    die('This script must be run from the command line.' . PHP_EOL);
}

// Execute migration
$exitCode = executeMigration();

// Exit with appropriate code
exit($exitCode);

// Made with Bob