# 1) Safety backup of current env
cp .env .env.backup.$(date +%F-%H%M%S)

# 2) Put app in maintenance mode while switching
php artisan down || true

# 3) Ensure latest code (if you deploy via git)
git pull origin main

# 4) Install PHP deps optimized for prod
composer install --no-dev --optimize-autoloader --no-interaction

# 5) Build frontend assets (npm exists on your host)
npm install
npm run build

# 6) Set production values in .env
sed -i 's/^APP_ENV=.*/APP_ENV=development/' .env
sed -i 's/^APP_DEBUG=.*/APP_DEBUG=true/' .env

# Optional but recommended if missing:
grep -q '^APP_URL=' .env || echo 'APP_URL=https://portal.lezinproperties.com' >> .env

# 7) Run DB migrations safely
php artisan migrate --force

# 8) Clear stale caches then build prod caches
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 9) Bring app back up
php artisan up
