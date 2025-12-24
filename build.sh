#!/bin/bash
#
# Build script for ACS Agenda Manager WordPress Plugin
# Creates a distributable zip file with only the necessary files
#

set -e

PLUGIN_SLUG="acs-agenda-manager"
VERSION=$(grep -o "Version: [0-9.]*" acs-agenda-manager.php | head -1 | cut -d' ' -f2)
BUILD_DIR="./dist"
PACKAGE_DIR="${BUILD_DIR}/${PLUGIN_SLUG}"

echo "================================================"
echo "  ACS Agenda Manager - Build Script"
echo "  Version: ${VERSION:-unknown}"
echo "================================================"
echo ""

# Clean previous build
if [ -d "$BUILD_DIR" ]; then
    echo "[*] Cleaning previous build..."
    rm -rf "$BUILD_DIR"
fi

# Create build directory
echo "[*] Creating build directory..."
mkdir -p "$PACKAGE_DIR"

# Copy plugin files (excluding dev/test files)
echo "[*] Copying plugin files..."

# Use rsync with exclusions for a clean copy
rsync -av --progress \
    --exclude='.git' \
    --exclude='.gitignore' \
    --exclude='.github' \
    --exclude='.vscode' \
    --exclude='.idea' \
    --exclude='.pre-commit-config.yaml' \
    --exclude='.prettierignore' \
    --exclude='.secrets.baseline' \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='test' \
    --exclude='test-results' \
    --exclude='tests' \
    --exclude='dist' \
    --exclude='build.sh' \
    --exclude='package.json' \
    --exclude='package-lock.json' \
    --exclude='composer.json' \
    --exclude='composer.lock' \
    --exclude='phpcs.xml' \
    --exclude='phpunit.xml' \
    --exclude='*.log' \
    --exclude='*.map' \
    --exclude='.DS_Store' \
    --exclude='Thumbs.db' \
    --exclude='*.zip' \
    --exclude='.env*' \
    --exclude='docker-compose*.yml' \
    --exclude='Dockerfile*' \
    --exclude='Makefile' \
    --exclude='.editorconfig' \
    --exclude='.eslintrc*' \
    --exclude='.prettierrc*' \
    --exclude='.stylelintrc*' \
    --exclude='*.md' \
    --exclude='CHANGELOG.md' \
    ./ "$PACKAGE_DIR/"

# Keep readme.txt (WordPress standard) but we can also keep README.md if needed
# The rsync above excludes *.md, so let's copy readme.txt explicitly if it exists
if [ -f "readme.txt" ]; then
    cp readme.txt "$PACKAGE_DIR/"
fi

# Create the zip file
echo "[*] Creating zip package..."
cd "$BUILD_DIR"
zip -r "${PLUGIN_SLUG}-${VERSION:-dev}.zip" "$PLUGIN_SLUG" -x "*.DS_Store" -x "*__MACOSX*"
cd ..

# Show package contents
echo ""
echo "[*] Package contents:"
unzip -l "${BUILD_DIR}/${PLUGIN_SLUG}-${VERSION:-dev}.zip" | head -40
echo "..."

# Show package size
PACKAGE_SIZE=$(du -h "${BUILD_DIR}/${PLUGIN_SLUG}-${VERSION:-dev}.zip" | cut -f1)
echo ""
echo "================================================"
echo "  Build Complete!"
echo "================================================"
echo ""
echo "  Package: ${BUILD_DIR}/${PLUGIN_SLUG}-${VERSION:-dev}.zip"
echo "  Size: ${PACKAGE_SIZE}"
echo ""
echo "  You can upload this zip file to WordPress"
echo "  via Plugins > Add New > Upload Plugin"
echo ""
echo "================================================"
