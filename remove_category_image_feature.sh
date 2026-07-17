#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

rm -f \
  "$PROJECT_ROOT/app/Services/CategoryCollageService.php" \
  "$PROJECT_ROOT/app/Console/Commands/GenerateCategoryCollagesCommand.php"

echo "Removed obsolete category image collage service and command files."
