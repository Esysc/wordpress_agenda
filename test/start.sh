#!/bin/bash
set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$SCRIPT_DIR"

PLUGIN_SRC="$(cd "$SCRIPT_DIR/.." && pwd)"
BUILD_DIR="$SCRIPT_DIR/.build"
PLUGIN_DEST="$BUILD_DIR/acs-agenda-manager"

echo "================================================"
echo "  ACS Agenda Manager - Test Environment"
echo "================================================"
echo ""

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "❌ Error: Docker is not running. Please start Docker Desktop first."
    exit 1
fi

# Stage a clean copy of the plugin (exclude dev/test files)
echo "📦 Staging plugin files..."
rm -rf "$PLUGIN_DEST"
mkdir -p "$BUILD_DIR"
rsync -a "$PLUGIN_SRC"/ "$PLUGIN_DEST"/ \
    --delete \
    --exclude '.git/' \
    --exclude '.github/' \
    --exclude '.gitignore' \
    --exclude '.gitattributes' \
    --exclude '.distignore' \
    --exclude '.prettierignore' \
    --exclude '.prettierrc' \
    --exclude '.eslintrc.json' \
    --exclude '.secrets.baseline' \
    --exclude '.pre-commit-config.yaml' \
    --exclude 'node_modules/' \
    --exclude 'package.json' \
    --exclude 'package-lock.json' \
    --exclude 'test/' \
    --exclude 'test-results/' \
    --exclude 'wp-cli/' \
    --exclude 'themefiles/' \
    --exclude 'README.md' \
    --exclude 'CHANGELOG.md' \
    --exclude 'phpcs.xml' \
    --exclude 'composer.json' \
    --exclude 'composer.lock' \
    --exclude 'vendor/' \
    --exclude 'dist/' \
    --exclude 'build.sh' \
    --exclude '*.log' \
    --exclude '.DS_Store' \
    --exclude 'Thumbs.db'

# Keep only .mo files, exclude .po and .pot
find "$PLUGIN_DEST/lang" -type f \( -name "*.po" -o -name "*.pot" \) -delete 2>/dev/null || true

# Make setup script executable
chmod +x wp-cli/setup.sh

# Stop any existing containers
echo "🧹 Cleaning up any existing containers..."
docker compose down --remove-orphans 2>/dev/null || true

# Start fresh
echo "🚀 Starting WordPress environment..."
docker compose up -d wordpress db phpmyadmin

# Wait for WordPress to be healthy
echo "⏳ Waiting for WordPress to be ready (this may take a minute)..."
ATTEMPTS=0
MAX_ATTEMPTS=60

while [ $ATTEMPTS -lt $MAX_ATTEMPTS ]; do
    if docker compose exec -T wordpress curl -sf http://localhost/ > /dev/null 2>&1; then
        echo "✅ WordPress is ready!"
        break
    fi
    ATTEMPTS=$((ATTEMPTS + 1))
    sleep 2
    echo -n "."
done

if [ $ATTEMPTS -eq $MAX_ATTEMPTS ]; then
    echo ""
    echo "⚠️  WordPress is taking longer than expected. Proceeding anyway..."
fi

echo ""

# Run WP-CLI setup
echo "🔧 Running WordPress setup..."
docker compose run --rm wpcli

echo ""
echo "🎉 Done! Your test environment is ready."
echo ""
echo "   Open http://localhost:8080 in your browser"
echo ""
