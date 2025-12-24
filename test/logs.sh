#!/bin/bash

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$SCRIPT_DIR" || exit 1

echo "📋 Showing logs (Ctrl+C to exit)..."
echo ""

docker compose logs -f wordpress
