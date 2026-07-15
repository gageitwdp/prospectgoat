#!/usr/bin/env bash
set -euo pipefail

# One-shot Laravel deploy helper for shared hosting layouts.
# Layout expected by default:
#   ~/domains/portal.lezinproperties.com/lezinproperties -> Laravel app root
#   ~/domains/portal.lezinproperties.com/public_html     -> Web root
#
# Modes:
#   copy    : copy Laravel public/ contents into web root (default)
#   symlink : replace web root with symlink to app/public
#
# Environment:
#   development : default, keeps dev dependencies and clears caches
#   production  : installs without dev dependencies and builds caches
#
# Usage:
#   bash deploy-laravel-shared-hosting.sh
#   bash deploy-laravel-shared-hosting.sh --mode copy
#   bash deploy-laravel-shared-hosting.sh --mode symlink
#   bash deploy-laravel-shared-hosting.sh --environment production --mode copy
#   bash deploy-laravel-shared-hosting.sh --app-dir "$HOME/domains/portal.lezinproperties.com/lezinproperties" --web-dir "$HOME/domains/portal.lezinproperties.com/public_html"
#
# Optional flags:
#   --environment <development|production>
#   --force                 Skip confirmation prompt
#   --skip-migrate          Do not run migrations
#   --skip-optimize         Do not run cache optimization commands
#   --skip-storage-link     Do not run storage:link
#   --skip-frontend-build   Do not run npm install/build in production

APP_DIR="${HOME}/domains/portal.lezinproperties.com/lezinproperties"
WEB_DIR="${HOME}/domains/portal.lezinproperties.com/public_html"
MODE="copy"
ENVIRONMENT="development"
FORCE="false"
SKIP_MIGRATE="false"
SKIP_OPTIMIZE="false"
SKIP_STORAGE_LINK="false"
SKIP_FRONTEND_BUILD="false"
REQUIRED_PUBLIC_ASSETS=(
  "independent-operator.png"
  "KellerWilliams_Realty_Partners_Logo_CMYK.jpg"
  "lezin_properties_no_bg_full_logo.png"
)

print_help() {
  sed -n '1,40p' "$0"
}

log() {
  printf '\n[%s] %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$*"
}

verify_required_assets() {
  local base_dir="$1"
  local context="$2"
  local missing_assets=()

  for asset in "${REQUIRED_PUBLIC_ASSETS[@]}"; do
    if [[ ! -f "$base_dir/$asset" ]]; then
      missing_assets+=("$asset")
    fi
  done

  if (( ${#missing_assets[@]} > 0 )); then
    echo "Error: Missing required public assets in $context ($base_dir):" >&2
    for asset in "${missing_assets[@]}"; do
      echo "  - $asset" >&2
    done
    echo "Add these files to the Laravel public directory before deploying." >&2
    exit 1
  fi
}

require_cmd() {
  if ! command -v "$1" >/dev/null 2>&1; then
    echo "Error: required command not found: $1" >&2
    exit 1
  fi
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --app-dir)
      APP_DIR="$2"
      shift 2
      ;;
    --web-dir)
      WEB_DIR="$2"
      shift 2
      ;;
    --mode)
      MODE="$2"
      shift 2
      ;;
    --environment)
      ENVIRONMENT="$2"
      shift 2
      ;;
    --force)
      FORCE="true"
      shift
      ;;
    --skip-migrate)
      SKIP_MIGRATE="true"
      shift
      ;;
    --skip-optimize)
      SKIP_OPTIMIZE="true"
      shift
      ;;
    --skip-storage-link)
      SKIP_STORAGE_LINK="true"
      shift
      ;;
    --skip-frontend-build)
      SKIP_FRONTEND_BUILD="true"
      shift
      ;;
    -h|--help)
      print_help
      exit 0
      ;;
    *)
      echo "Unknown argument: $1" >&2
      print_help
      exit 1
      ;;
  esac
done

if [[ "$MODE" != "copy" && "$MODE" != "symlink" ]]; then
  echo "Error: --mode must be 'copy' or 'symlink'" >&2
  exit 1
fi

if [[ "$ENVIRONMENT" != "development" && "$ENVIRONMENT" != "production" ]]; then
  echo "Error: --environment must be 'development' or 'production'" >&2
  exit 1
fi

require_cmd php
require_cmd composer
require_cmd rsync

mkdir -p "$APP_DIR"
mkdir -p "$WEB_DIR"

log "Starting deploy"
log "App dir: $APP_DIR"
log "Web dir: $WEB_DIR"
log "Mode: $MODE"
log "Environment: $ENVIRONMENT"

if [[ "$FORCE" != "true" ]]; then
  echo
  read -r -p "This will modify '$APP_DIR' and '$WEB_DIR'. Continue? [y/N]: " answer
  if [[ ! "$answer" =~ ^[Yy]$ ]]; then
    echo "Canceled."
    exit 0
  fi
fi

if [[ -z "$(ls -A "$APP_DIR" 2>/dev/null || true)" ]]; then
  log "Creating new Laravel project in $APP_DIR"
  if [[ "$ENVIRONMENT" == "production" ]]; then
    composer create-project --no-dev laravel/laravel "$APP_DIR"
  else
    composer create-project laravel/laravel "$APP_DIR"
  fi
else
  if [[ -f "$APP_DIR/artisan" ]]; then
    log "Existing Laravel project detected. Running composer install/update dependencies"
    if [[ -f "$APP_DIR/composer.lock" ]]; then
      if [[ "$ENVIRONMENT" == "production" ]]; then
        composer install --working-dir="$APP_DIR" --no-interaction --prefer-dist --optimize-autoloader --no-dev
      else
        composer install --working-dir="$APP_DIR" --no-interaction --prefer-dist --optimize-autoloader
      fi
    else
      composer install --working-dir="$APP_DIR" --no-interaction
    fi
  else
    echo "Error: $APP_DIR is not empty and does not look like a Laravel app (missing artisan)." >&2
    echo "Either empty it or point --app-dir to a different folder." >&2
    exit 1
  fi
fi

if [[ ! -f "$APP_DIR/.env" && -f "$APP_DIR/.env.example" ]]; then
  log "Creating .env from .env.example"
  cp "$APP_DIR/.env.example" "$APP_DIR/.env"
fi

if [[ -f "$APP_DIR/.env" ]]; then
  log "Applying environment settings to .env"
  if grep -q '^APP_ENV=' "$APP_DIR/.env"; then
    sed -i "s/^APP_ENV=.*/APP_ENV=$ENVIRONMENT/" "$APP_DIR/.env"
  else
    echo "APP_ENV=$ENVIRONMENT" >> "$APP_DIR/.env"
  fi

  if [[ "$ENVIRONMENT" == "development" ]]; then
    app_debug_value="true"
  else
    app_debug_value="false"
  fi

  if grep -q '^APP_DEBUG=' "$APP_DIR/.env"; then
    sed -i "s/^APP_DEBUG=.*/APP_DEBUG=$app_debug_value/" "$APP_DIR/.env"
  else
    echo "APP_DEBUG=$app_debug_value" >> "$APP_DIR/.env"
  fi
fi

if [[ -f "$APP_DIR/artisan" ]]; then
  log "Generating app key"
  php "$APP_DIR/artisan" key:generate --force
else
  echo "Error: artisan file not found after setup." >&2
  exit 1
fi

if [[ "$SKIP_FRONTEND_BUILD" != "true" && "$ENVIRONMENT" == "production" && -f "$APP_DIR/package.json" ]]; then
  if command -v npm >/dev/null 2>&1; then
    log "Installing frontend dependencies"
    if [[ -f "$APP_DIR/package-lock.json" ]]; then
      npm ci --prefix "$APP_DIR" --no-audit --no-fund
    else
      npm install --prefix "$APP_DIR" --no-audit --no-fund
    fi

    log "Building frontend assets (Vite)"
    npm run build --prefix "$APP_DIR"
  else
    if [[ ! -f "$APP_DIR/public/build/manifest.json" ]]; then
      echo "Error: npm is not installed and prebuilt assets are missing." >&2
      echo "Expected file not found: $APP_DIR/public/build/manifest.json" >&2
      echo "Build assets locally/CI and upload public/build before deploying." >&2
      exit 1
    fi

    log "npm not available; using prebuilt frontend assets already present in public/build"
  fi
fi

log "Setting writable permissions on storage and bootstrap/cache"
chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" || true

log "Verifying required assets in Laravel public directory"
verify_required_assets "$APP_DIR/public" "Laravel public directory"

if [[ "$MODE" == "symlink" ]]; then
  if [[ -d "$WEB_DIR" || -L "$WEB_DIR" ]]; then
    backup_dir="${WEB_DIR}_backup_$(date +%F_%H%M%S)"
    log "Backing up current web root to $backup_dir"
    mv "$WEB_DIR" "$backup_dir"
  fi
  log "Creating symlink: $WEB_DIR -> $APP_DIR/public"
  ln -s "$APP_DIR/public" "$WEB_DIR"

  log "Verifying required assets in web root"
  verify_required_assets "$WEB_DIR" "web root"
else
  backup_dir="${WEB_DIR}_backup_$(date +%F_%H%M%S)"
  if [[ -n "$(ls -A "$WEB_DIR" 2>/dev/null || true)" ]]; then
    log "Backing up current web root contents to $backup_dir"
    mv "$WEB_DIR" "$backup_dir"
    mkdir -p "$WEB_DIR"
  fi

  log "Syncing Laravel public/ to web root"
  rsync -a --delete "$APP_DIR/public/" "$WEB_DIR/"

  index_file="$WEB_DIR/index.php"
  if [[ -f "$index_file" ]]; then
    log "Updating index.php paths for shared hosting layout"
    sed -i "s#require __DIR__.'/../vendor/autoload.php';#require __DIR__.'/../$(basename "$APP_DIR")/vendor/autoload.php';#" "$index_file"
    sed -i "s#\$app = require_once __DIR__.'/../bootstrap/app.php';#\$app = require_once __DIR__.'/../$(basename "$APP_DIR")/bootstrap/app.php';#" "$index_file"
  fi

  log "Verifying required assets in web root"
  verify_required_assets "$WEB_DIR" "web root"
fi

if [[ "$SKIP_STORAGE_LINK" != "true" ]]; then
  log "Creating storage symlink"
  php "$APP_DIR/artisan" storage:link || true
fi

if [[ "$SKIP_MIGRATE" != "true" ]]; then
  log "Running migrations"
  if [[ "$ENVIRONMENT" == "production" ]]; then
    php "$APP_DIR/artisan" migrate --force || {
      echo "Migration failed. Check database config in $APP_DIR/.env and rerun migrate manually." >&2
    }
  else
    php "$APP_DIR/artisan" migrate || {
      echo "Migration failed. Check database config in $APP_DIR/.env and rerun migrate manually." >&2
    }
  fi
fi

if [[ "$SKIP_OPTIMIZE" != "true" ]]; then
  if [[ "$ENVIRONMENT" == "production" ]]; then
    log "Running production optimization caches"
    php "$APP_DIR/artisan" config:cache || true
    php "$APP_DIR/artisan" route:cache || true
    php "$APP_DIR/artisan" view:cache || true
  else
    log "Clearing optimization caches for development"
    php "$APP_DIR/artisan" optimize:clear || true
  fi
fi

log "Deploy completed"
echo
php "$APP_DIR/artisan" --version || true
echo "App directory: $APP_DIR"
echo "Web directory: $WEB_DIR"
if [[ "$MODE" == "copy" ]]; then
  echo "Backup(s): ${WEB_DIR}_backup_YYYY-MM-DD_HHMMSS (if any existed)"
fi
