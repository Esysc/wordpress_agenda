#!/bin/bash

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$SCRIPT_DIR" || exit 1

echo "🧹 Cleaning up test environment..."
echo ""
echo "⚠️  This will remove all containers, volumes, and data!"
read -p "   Are you sure? (y/N) " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Yy]$ ]]; then
    docker compose down -v --remove-orphans
    echo ""
    echo "✅ All test data has been removed."
else
    echo "❌ Cancelled."
fi
