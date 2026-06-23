#!/bin/zsh
set -euo pipefail

ROOT="$(cd "$(dirname "$0")" && pwd)"
cd "$ROOT"

echo "========================================"
echo "Bid.cars worker"
echo "========================================"
echo ""

if ! command -v node >/dev/null 2>&1; then
    echo "ERROR: Node.js is not installed or not in PATH."
    exit 1
fi

if [[ ! -d "$ROOT/node_modules/playwright" ]]; then
    echo "Installing dependencies..."
    npm install
fi

if [[ ! -f "$ROOT/import.config.json" ]]; then
    echo "ERROR: import.config.json is missing."
    exit 1
fi

export BID_CARS_HEADLESS=0
node worker.mjs

echo ""
read -r '?Press Enter to close this window... '