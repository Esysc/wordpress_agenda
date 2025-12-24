#!/bin/bash
set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
E2E_DIR="$SCRIPT_DIR/e2e"

echo "================================================"
echo "  ACS Agenda Manager - E2E Tests"
echo "================================================"
echo ""

# Check if Docker environment is running
if ! docker ps | grep -q "acs_agenda_wordpress"; then
    echo "❌ Docker test environment is not running!"
    echo "   Please run ./start.sh first"
    exit 1
fi

# Check if WordPress is accessible
echo "[*] Checking WordPress availability..."
if ! curl -s -o /dev/null -w "%{http_code}" http://localhost:8080 | grep -q "200\|302"; then
    echo "❌ WordPress is not accessible at http://localhost:8080"
    echo "   Please wait for the environment to fully start"
    exit 1
fi
echo "[OK] WordPress is accessible"

cd "$E2E_DIR"

# Install dependencies if needed
if [ ! -d "node_modules" ]; then
    echo ""
    echo "[*] Installing npm dependencies..."
    npm install
fi

# Install Playwright browsers if needed
if [ ! -d "$HOME/.cache/ms-playwright" ] && [ ! -d "node_modules/.cache/ms-playwright" ]; then
    echo ""
    echo "[*] Installing Playwright browsers..."
    npx playwright install chromium
fi

echo ""
echo "[*] Running E2E tests..."
echo ""

# Run tests with any additional arguments passed to this script
npm test -- "$@"

echo ""
echo "================================================"
echo "  Tests completed! Run 'npm run report' in"
echo "  test/e2e to view the HTML report"
echo "================================================"
