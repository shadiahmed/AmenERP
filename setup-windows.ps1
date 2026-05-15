# AmenERP Directory Structure Setup Script for Windows/XAMPP
# This script creates the complete directory structure following the Zero Framework architecture
# Run this script from the AmenERP root directory

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  AmenERP Directory Structure Setup" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Define directory structure
$directories = @(
    "config",
    "core",
    "modules",
    "modules/inventory",
    "modules/finance",
    "public",
    "public/assets",
    "public/assets/css",
    "public/assets/js",
    "public/assets/images"
)

# Create directories
Write-Host "Creating directory structure..." -ForegroundColor Yellow
Write-Host ""

foreach ($dir in $directories) {
    if (!(Test-Path -Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
        Write-Host "[OK] Created: $dir" -ForegroundColor Green
    } else {
        Write-Host "[!] Already exists: $dir" -ForegroundColor Gray
    }
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Directory Structure Created!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Next Steps:" -ForegroundColor Yellow
Write-Host "1. Run the setup-unix.sh script if deploying to Linux" -ForegroundColor White
Write-Host "2. Configure database credentials in config/config.php" -ForegroundColor White
Write-Host "3. Create the amenerp_db database in MySQL" -ForegroundColor White
Write-Host "4. Start building your ERP modules" -ForegroundColor White
Write-Host ""

# Made with Bob
