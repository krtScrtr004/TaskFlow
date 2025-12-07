#!/bin/bash

# TaskFlow Dependency Installation Script
# This script automates the installation of all required dependencies

set -e  # Exit on error

echo "=================================="
echo "TaskFlow Dependency Installation"
echo "=================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored messages
print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_info() {
    echo -e "${YELLOW}ℹ $1${NC}"
}

# Check if Composer is installed
echo "Checking for Composer..."
if ! command -v composer &> /dev/null; then
    print_error "Composer is not installed."
    print_info "Please install Composer from https://getcomposer.org/"
    exit 1
fi
print_success "Composer found: $(composer --version | head -n 1)"
echo ""

# Check if pnpm is installed
echo "Checking for pnpm..."
if ! command -v pnpm &> /dev/null; then
    print_error "pnpm is not installed."
    print_info "Installing pnpm via npm..."
    
    # Check if npm is installed
    if ! command -v npm &> /dev/null; then
        print_error "npm is not installed. Please install Node.js and npm first."
        print_info "Download from https://nodejs.org/"
        exit 1
    fi
    
    npm install -g pnpm
    print_success "pnpm installed successfully"
fi
print_success "pnpm found: $(pnpm --version)"
echo ""

# Install PHP dependencies via Composer
echo "Installing PHP dependencies..."
print_info "Running: composer install"
if composer install --no-interaction --prefer-dist --optimize-autoloader; then
    print_success "PHP dependencies installed successfully"
else
    print_error "Failed to install PHP dependencies"
    exit 1
fi
echo ""

# Install Node.js dependencies via pnpm
echo "Installing Node.js dependencies..."
print_info "Running: pnpm install"
if pnpm install; then
    print_success "Node.js dependencies installed successfully"
else
    print_error "Failed to install Node.js dependencies"
    exit 1
fi
echo ""

# Check for .env file
echo "Checking environment configuration..."
if [ ! -f .env ]; then
    print_info ".env file not found"
    if [ -f .env.example ]; then
        print_info "Copying .env.example to .env"
        cp .env.example .env
        print_success ".env file created"
        print_info "Please update .env with your configuration settings"
    else
        print_info "No .env.example found. Please create .env manually"
    fi
else
    print_success ".env file exists"
fi
echo ""

# Summary
echo "=================================="
echo "Installation Summary"
echo "=================================="
print_success "All dependencies installed successfully!"
echo ""
print_info "Next steps:"
echo "  1. Configure your .env file with database and API credentials"
echo "  2. Import the database schema from database/taskflow.sql"
echo "  3. Import the event scheduler from database/event-scheduler.sql"
echo "  4. Start your development server"
echo ""
print_success "Setup complete! Happy coding! 🚀"
