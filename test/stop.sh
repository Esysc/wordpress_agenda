#!/bin/bash

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$SCRIPT_DIR" || exit 1

echo "🛑 Stopping test environment..."
docker compose down

echo "✅ Test environment stopped."
echo ""
echo "   To remove all data, run: ./clean.sh"
echo ""
