#!/bin/bash

echo "=========================================="
echo "Laravel Privilege Manager - Setup Check"
echo "=========================================="
echo ""

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check PHP version
echo "Checking PHP version..."
php_version=$(php -r "echo phpversion();")
required_version="8.1.0"
if [[ $(php -r "echo version_compare('$php_version', '$required_version', '>=') ? 1 : 0;") -eq 1 ]]; then
    echo -e "${GREEN}✓${NC} PHP $php_version (required: $required_version+)"
else
    echo -e "${RED}✗${NC} PHP $php_version (required: $required_version+)"
fi

echo ""

# Check if composer.json exists
echo "Checking package structure..."
if [ -f "composer.json" ]; then
    echo -e "${GREEN}✓${NC} composer.json found"
else
    echo -e "${RED}✗${NC} composer.json not found"
fi

# Check src directory
if [ -d "src" ]; then
    echo -e "${GREEN}✓${NC} src/ directory found"
else
    echo -e "${RED}✗${NC} src/ directory not found"
fi

# Check config directory
if [ -d "config" ]; then
    echo -e "${GREEN}✓${NC} config/ directory found"
else
    echo -e "${RED}✗${NC} config/ directory not found"
fi

# Check docs directory
if [ -d "docs" ]; then
    echo -e "${GREEN}✓${NC} docs/ directory found"
else
    echo -e "${RED}✗${NC} docs/ directory not found"
fi

# Check key files
echo ""
echo "Checking key files..."

files=(
    "src/Services/PrivilegeService.php"
    "src/Middleware/CheckPrivilege.php"
    "src/Models/Contracts/PrivilegeUserContract.php"
    "src/Models/UserPrivilege.php"
    "src/Models/Menu.php"
    "src/Helpers/privilege_helpers.php"
    "src/Providers/PrivilegeManagerServiceProvider.php"
    "config/privilege-manager.php"
    "README.md"
    "LICENSE"
)

for file in "${files[@]}"; do
    if [ -f "$file" ]; then
        echo -e "${GREEN}✓${NC} $file"
    else
        echo -e "${RED}✗${NC} $file missing"
    fi
done

echo ""
echo "=========================================="
echo "Setup check complete!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Copy this package to your vendor directory"
echo "2. Run: php artisan vendor:publish"
echo "3. Update your User model"
echo "4. See docs/INDEX.md for complete guide"
echo ""
