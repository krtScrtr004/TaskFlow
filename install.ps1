# TaskFlow Dependency Installation Script (PowerShell)
# This script automates the installation of all required dependencies

$ErrorActionPreference = "Continue"

Write-Host "==================================" -ForegroundColor Cyan
Write-Host "TaskFlow Dependency Installation" -ForegroundColor Cyan
Write-Host "==================================" -ForegroundColor Cyan
Write-Host ""

# Check if Composer is installed
Write-Host "Checking for Composer..." -ForegroundColor White
$composerCheck = Get-Command composer -ErrorAction SilentlyContinue
if (-not $composerCheck) {
    Write-Host "[ERROR] Composer is not installed." -ForegroundColor Red
    Write-Host "[INFO] Please install Composer from https://getcomposer.org/" -ForegroundColor Yellow
    exit 1
}
$composerVersion = composer --version 2>&1 | Select-Object -First 1
Write-Host "[OK] Composer found: $composerVersion" -ForegroundColor Green
Write-Host ""

# Check if pnpm is installed
Write-Host "Checking for pnpm..." -ForegroundColor White
$pnpmCheck = Get-Command pnpm -ErrorAction SilentlyContinue
if (-not $pnpmCheck) {
    Write-Host "[ERROR] pnpm is not installed." -ForegroundColor Red
    Write-Host "[INFO] Installing pnpm via npm..." -ForegroundColor Yellow
    
    # Check if npm is installed
    $npmCheck = Get-Command npm -ErrorAction SilentlyContinue
    if (-not $npmCheck) {
        Write-Host "[ERROR] npm is not installed. Please install Node.js and npm first." -ForegroundColor Red
        Write-Host "[INFO] Download from https://nodejs.org/" -ForegroundColor Yellow
        exit 1
    }
    
    Write-Host "Running: npm install -g pnpm" -ForegroundColor Gray
    npm install -g pnpm
    if ($LASTEXITCODE -ne 0) {
        Write-Host "[ERROR] Failed to install pnpm" -ForegroundColor Red
        exit 1
    }
    Write-Host "[OK] pnpm installed successfully" -ForegroundColor Green
} else {
    $pnpmVersion = pnpm --version 2>&1
    Write-Host "[OK] pnpm found: $pnpmVersion" -ForegroundColor Green
}
Write-Host ""

# Install PHP dependencies via Composer
Write-Host "Installing PHP dependencies..." -ForegroundColor White
Write-Host "[INFO] Running: composer install" -ForegroundColor Yellow
composer install --no-interaction --prefer-dist --optimize-autoloader
if ($LASTEXITCODE -eq 0) {
    Write-Host "[OK] PHP dependencies installed successfully" -ForegroundColor Green
} else {
    Write-Host "[ERROR] Failed to install PHP dependencies" -ForegroundColor Red
    exit 1
}
Write-Host ""

# Install Node.js dependencies via pnpm
Write-Host "Installing Node.js dependencies..." -ForegroundColor White
Write-Host "[INFO] Running: pnpm install" -ForegroundColor Yellow
pnpm install
if ($LASTEXITCODE -eq 0) {
    Write-Host "[OK] Node.js dependencies installed successfully" -ForegroundColor Green
} else {
    Write-Host "[ERROR] Failed to install Node.js dependencies" -ForegroundColor Red
    exit 1
}
Write-Host ""

# Check for .env file
Write-Host "Checking environment configuration..." -ForegroundColor White
if (-not (Test-Path ".env")) {
    Write-Host "[INFO] .env file not found" -ForegroundColor Yellow
    if (Test-Path ".env.example") {
        Write-Host "[INFO] Copying .env.example to .env" -ForegroundColor Yellow
        Copy-Item ".env.example" ".env"
        Write-Host "[OK] .env file created" -ForegroundColor Green
        Write-Host "[INFO] Please update .env with your configuration settings" -ForegroundColor Yellow
    } else {
        Write-Host "[INFO] No .env.example found. Please create .env manually" -ForegroundColor Yellow
    }
} else {
    Write-Host "[OK] .env file exists" -ForegroundColor Green
}
Write-Host ""

# Summary
Write-Host "==================================" -ForegroundColor Cyan
Write-Host "Installation Summary" -ForegroundColor Cyan
Write-Host "==================================" -ForegroundColor Cyan
Write-Host "[OK] All dependencies installed successfully!" -ForegroundColor Green
Write-Host ""
Write-Host "[INFO] Next steps:" -ForegroundColor Yellow
Write-Host "  1. Configure your .env file with database and API credentials"
Write-Host "  2. Import the database schema from database/taskflow.sql"
Write-Host "  3. Import the event scheduler from database/event-scheduler.sql"
Write-Host "  4. Start your development server"
Write-Host ""
Write-Host "[OK] Setup complete! Happy coding!" -ForegroundColor Green
