#!/bin/bash
#
# Generate WordPress.org plugin screenshots
# This script captures screenshots from your running WordPress test environment
#
# Prerequisites:
#   1. Start the test environment: ./start.sh
#   2. Ensure WordPress is accessible at http://localhost:8080
#
# Usage:
#   ./generate-screenshots.sh
#

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
E2E_DIR="${SCRIPT_DIR}/e2e"
OUTPUT_DIR="${SCRIPT_DIR}/../.wordpress-org"

echo ""
echo "================================================"
echo "  WordPress.org Screenshot Generator"
echo "================================================"
echo ""

# Check if test environment is running
if ! curl -s http://localhost:8080 > /dev/null 2>&1; then
    echo "[!] WordPress test environment is not running."
    echo "    Start it with: ./start.sh"
    echo ""
    exit 1
fi

echo "[*] WordPress is running at http://localhost:8080"

# Install dependencies if needed
cd "$E2E_DIR"
if [ ! -d "node_modules" ]; then
    echo "[*] Installing npm dependencies..."
    npm install
fi

# Check for ts-node
if ! npm list ts-node > /dev/null 2>&1; then
    echo "[*] Installing ts-node..."
    npm install --save-dev ts-node typescript
fi

# Generate screenshots directly (skip e2e test auth)
echo "[*] Generating screenshots..."
echo ""

npx ts-node screenshots.ts

echo ""
echo "[✓] Screenshots generated in: ${OUTPUT_DIR}"
echo ""
ls -la "${OUTPUT_DIR}"/*.png 2>/dev/null || echo "    (no screenshots found)"
echo ""
