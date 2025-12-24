#!/bin/bash
#
# Build script for ACS Agenda Manager WordPress Plugin
# Creates a distributable zip file with only the necessary files
#
# Usage:
#   ./build.sh          - Build the zip package only
#   ./build.sh release  - Build and create a GitHub release
#

set -e

PLUGIN_SLUG="acs-agenda-manager"
VERSION=$(grep -o "Version: [0-9.]*" acs-agenda-manager.php | head -1 | cut -d' ' -f2)
BUILD_DIR="./dist"
PACKAGE_DIR="${BUILD_DIR}/${PLUGIN_SLUG}"
DO_RELEASE="${1:-}"

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

# Copy plugin files (only include necessary files)
echo "[*] Copying plugin files..."

# Use rsync with include-only logic for a clean copy
rsync -av --progress \
    --include='acs-agenda-manager.php' \
    --include='readme.txt' \
    --include='class/***' \
    --include='css/***' \
    --include='js/***' \
    --include='lang/***' \
    --include='templates/***' \
    --include='themefiles/***' \
    --exclude='*' \
    ./ "$PACKAGE_DIR/"

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
ZIP_FILE="${BUILD_DIR}/${PLUGIN_SLUG}-${VERSION:-dev}.zip"

echo ""
echo "================================================"
echo "  Build Complete!"
echo "================================================"
echo ""
echo "  Package: ${ZIP_FILE}"
echo "  Size: ${PACKAGE_SIZE}"
echo ""
echo "  You can upload this zip file to WordPress"
echo "  via Plugins > Add New > Upload Plugin"
echo ""
echo "================================================"

# GitHub Release (optional)
if [ "$DO_RELEASE" = "release" ]; then
    echo ""
    echo "[*] Creating GitHub Release..."

    # Check if gh CLI is installed
    if ! command -v gh &> /dev/null; then
        echo "[ERROR] GitHub CLI (gh) is not installed."
        echo "        Install it with: brew install gh"
        exit 1
    fi

    # Check if authenticated
    if ! gh auth status &> /dev/null; then
        echo "[ERROR] Not authenticated with GitHub CLI."
        echo "        Run: gh auth login"
        exit 1
    fi

    TAG="v${VERSION}"
    RELEASE_NOTES="## ACS Agenda Manager v${VERSION}

### Installation
1. Download \`${PLUGIN_SLUG}-${VERSION}.zip\`
2. Go to WordPress Admin > Plugins > Add New > Upload Plugin
3. Choose the downloaded zip file and click Install Now
4. Activate the plugin

### Changelog
See [CHANGELOG.md](CHANGELOG.md) for details."

    # Check if tag already exists
    if git rev-parse "$TAG" >/dev/null 2>&1; then
        echo "[WARN] Tag $TAG already exists. Deleting and recreating..."
        git tag -d "$TAG" 2>/dev/null || true
        git push origin --delete "$TAG" 2>/dev/null || true
    fi

    # Create and push tag
    echo "[*] Creating tag $TAG..."
    git tag -a "$TAG" -m "Release ${VERSION}"
    git push origin "$TAG"

    # Create GitHub release with the zip file
    echo "[*] Creating GitHub release..."
    gh release create "$TAG" \
        --title "ACS Agenda Manager ${VERSION}" \
        --notes "$RELEASE_NOTES" \
        "$ZIP_FILE"

    echo ""
    echo "================================================"
    echo "  GitHub Release Created!"
    echo "================================================"
    echo ""
    echo "  Tag: $TAG"
    echo "  URL: $(gh release view "$TAG" --json url -q .url)"
    echo ""
    echo "================================================"
fi
