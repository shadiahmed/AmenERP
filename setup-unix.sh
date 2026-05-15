#!/bin/bash

# AmenERP Directory Structure Setup Script for Unix/Linux
# This script creates the complete directory structure following the Zero Framework architecture
# Run this script from the AmenERP root directory
# Usage: chmod +x setup-unix.sh && ./setup-unix.sh

echo "========================================"
echo "  AmenERP Directory Structure Setup"
echo "========================================"
echo ""

# Define directory structure
directories=(
    "config"
    "core"
    "modules"
    "modules/inventory"
    "modules/finance"
    "public"
    "public/assets"
    "public/assets/css"
    "public/assets/js"
    "public/assets/images"
)

# Create directories
echo "Creating directory structure..."
echo ""

for dir in "${directories[@]}"; do
    if [ ! -d "$dir" ]; then
        mkdir -p "$dir"
        echo "[✓] Created: $dir"
    else
        echo "[!] Already exists: $dir"
    fi
done

# Set proper permissions (755 for directories)
echo ""
echo "Setting directory permissions..."
chmod -R 755 config core modules public 2>/dev/null
echo "[✓] Permissions set to 755"

echo ""
echo "========================================"
echo "  Directory Structure Created!"
echo "========================================"
echo ""
echo "Next Steps:"
echo "1. Configure database credentials in config/config.php"
echo "2. Create the 'amenerp_db' database in MySQL"
echo "3. Ensure PHP 8.x and MySQL are installed"
echo "4. Start building your ERP modules!"
echo ""

# Made with Bob
