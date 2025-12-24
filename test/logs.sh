#!/bin/bash

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$SCRIPT_DIR"

echo "📋 Showing logs (Ctrl+C to exit)..."
echo ""

docker compose logs -f wordpress
