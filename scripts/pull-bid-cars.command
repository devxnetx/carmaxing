#!/bin/zsh
set -euo pipefail

exec "$(cd "$(dirname "$0")" && pwd)/bid-cars-worker/pull-bid-cars.command"