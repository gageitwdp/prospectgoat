# Production Config Checklist

Use this checklist when preparing the Laravel app on your server.

## 1. Environment file

- Copy `.env.production.example` to `.env` in the Laravel app root.
- Fill every `replace_me_*` value.
- Set a unique `APP_KEY` using `php artisan key:generate --force`.
- Confirm `APP_ENV=production` and `APP_DEBUG=false`.
- Confirm `APP_URL` matches the exact domain.

## 2. Database

- Verify database exists.
- Verify database user has only required privileges.
- Confirm `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.
- Run migrations with force mode.

## 3. Session, cache, queue

- Use durable drivers for production:
- `SESSION_DRIVER=database` or redis.
- `CACHE_STORE=database` or redis.
- `QUEUE_CONNECTION=database` or redis.
- If using database drivers, run related table migrations.

## 4. Mail

- Confirm SMTP host, port, username, and password.
- Set sender as a verified domain mailbox.
- Send a test mail from the app.

## 5. Files and permissions

- Ensure write access for `storage` and `bootstrap/cache`.
- Run `php artisan storage:link` when public storage is needed.
- Verify uploads are not publicly exposed outside intended paths.

## 6. Performance and optimization

- Run config, route, and view cache commands.
- Ensure Composer dependencies are installed with optimized autoloading.
- If using queues, run a queue worker under a process manager.

## 7. Security hardening

- Serve only the Laravel `public` directory from the web root.
- Enforce HTTPS with redirect at web server level.
- Add strict file permissions and avoid 777.
- Rotate credentials if they were ever committed or shared.

## 8. Scheduler and background jobs

- Add cron entry to run scheduler every minute.
- Confirm scheduled tasks run successfully.
- Confirm queue workers auto-restart on deploy.

## 9. Monitoring and backup

- Configure error monitoring (for example Sentry).
- Configure uptime and HTTP health checks.
- Verify automated database and file backups.
- Test restore process at least once.

## 10. Post-deploy verification

- Verify home page and key portal flows.
- Verify login/logout/session behavior.
- Verify file upload/download behavior.
- Verify email sending.
- Verify required root-level public assets return HTTP 200:
- `https://portal.prospectgoat.com/independent-operator.png`
- `https://portal.prospectgoat.com/KellerWilliams_Realty_Partners_Logo_CMYK.jpg`
- `https://portal.prospectgoat.com/favicon.ico`
- Check logs for warnings/errors after first traffic.
