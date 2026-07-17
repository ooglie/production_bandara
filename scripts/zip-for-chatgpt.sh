#!/usr/bin/env bash
set -euo pipefail

ROOT="${1:-$(pwd)}"
ROOT="$(cd "$ROOT" && pwd)"

PROJECT_NAME="$(basename "$ROOT")"
STAMP="$(date +%Y%m%d_%H%M%S)"
OUT="$ROOT/${PROJECT_NAME}_chatgpt_source_${STAMP}.zip"

TMP="$(mktemp -d)"
STAGE="$TMP/${PROJECT_NAME}_chatgpt_source"

cleanup() {
  rm -rf "$TMP"
}
trap cleanup EXIT

mkdir -p "$STAGE"

copy_path() {
  local rel="$1"

  if [ ! -e "$ROOT/$rel" ]; then
    return 0
  fi

  mkdir -p "$STAGE/$(dirname "$rel")"

  rsync -a \
    --exclude='.DS_Store' \
    --exclude='Thumbs.db' \
    --exclude='.git' \
    --exclude='vendor' \
    --exclude='node_modules' \
    --exclude='storage/logs' \
    --exclude='storage/framework/cache' \
    --exclude='storage/framework/sessions' \
    --exclude='storage/framework/views' \
    --exclude='storage/app/public' \
    --exclude='bootstrap/cache/*.php' \
    --exclude='public/storage' \
    --exclude='public/build' \
    --exclude='public/hot' \
    --exclude='public/uploads' \
    --exclude='*.zip' \
    --exclude='*.tar' \
    --exclude='*.gz' \
    --exclude='*.sql' \
    "$ROOT/$rel" "$STAGE/$(dirname "$rel")/"
}

# Core Laravel application code
copy_path "app"
copy_path "routes"
copy_path "config"
copy_path "database"
copy_path "resources"
copy_path "tests"

# Public source assets only, not uploaded/generated files
copy_path "public"

# Laravel bootstrap and entry files
copy_path "artisan"
copy_path "bootstrap/app.php"
copy_path "bootstrap/providers.php"

# Dependency/config files
copy_path "composer.json"
copy_path "composer.lock"
copy_path "package.json"
copy_path "package-lock.json"
copy_path "pnpm-lock.yaml"
copy_path "yarn.lock"
copy_path "vite.config.js"
copy_path "webpack.mix.js"
copy_path "tailwind.config.js"
copy_path "postcss.config.js"
copy_path "phpunit.xml"
copy_path "pint.json"
copy_path "README.md"

# Safe env reference only. Do not package real .env.
copy_path ".env.example"

# Add package information
{
  echo "ChatGPT source package"
  echo "Generated: $(date)"
  echo "Project root: $ROOT"
  echo

  if git -C "$ROOT" rev-parse --is-inside-work-tree >/dev/null 2>&1; then
    echo "Git branch: $(git -C "$ROOT" rev-parse --abbrev-ref HEAD 2>/dev/null || true)"
    echo "Git commit: $(git -C "$ROOT" rev-parse HEAD 2>/dev/null || true)"
    echo
    echo "Git status:"
    git -C "$ROOT" status --short 2>/dev/null || true
  else
    echo "Git repository: not detected"
  fi

  echo
  echo "Excluded intentionally:"
  echo "- .env"
  echo "- vendor"
  echo "- node_modules"
  echo "- storage logs/cache/uploads"
  echo "- generated PDFs/uploads"
  echo "- SQL dumps"
  echo "- existing zip/tar/gz archives"
} > "$STAGE/_CHATGPT_PACKAGE_INFO.txt"

# Create file list manifest
(
  cd "$STAGE"
  find . -type f | sort > "_CHATGPT_FILE_LIST.txt"
)

# Create zip
(
  cd "$TMP"
  zip -qry "$OUT" "$(basename "$STAGE")"
)

echo "Created:"
echo "$OUT"
echo
echo "Size:"
du -h "$OUT" | awk '{print $1}'
