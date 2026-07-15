# lezinproperties
lezin properties portal

## One-shot Laravel deploy script

This repository includes a helper script for shared hosting deployments:

- `deploy-laravel-shared-hosting.sh`

### What it does

- Creates a Laravel project in `~/domains/portal.lezinproperties.com/lezinproperties` if empty
- Or runs `composer install` if Laravel already exists
- Creates `.env` and generates app key
- Defaults deployment behavior to development mode (`APP_ENV=development`, `APP_DEBUG=true`)
- Sets writable permissions on `storage` and `bootstrap/cache`
- Publishes `public/` into `~/domains/portal.lezinproperties.com/public_html` (copy mode) or symlinks web root
- Optionally runs migrations, `storage:link`, and environment-aware optimization commands

### Usage

```bash
chmod +x deploy-laravel-shared-hosting.sh
./deploy-laravel-shared-hosting.sh --mode copy --force
```

The script defaults already target:

- `~/domains/portal.lezinproperties.com/lezinproperties` as app dir
- `~/domains/portal.lezinproperties.com/public_html` as web dir

Production mode example:

```bash
./deploy-laravel-shared-hosting.sh --environment production --mode copy --force
```

Optional flags:

- `--app-dir <path>`
- `--web-dir <path>`
- `--mode copy|symlink`
- `--environment development|production`
- `--skip-migrate`
- `--skip-optimize`
- `--skip-storage-link`
- `--skip-frontend-build`
- `--force`

### Fix for "Vite manifest not found"

If you see a runtime error about missing `public/build/manifest.json`, frontend assets were not built on the server before serving Blade views that call Vite.

This repo now:

- Builds assets automatically in production deploys when `npm` is available
- Avoids hard-failing the default welcome view when the manifest is missing

To force a production build manually:

```bash
npm install
npm run build
```

### If npm does not exist on the server

Use a local or CI machine to build assets, then upload the generated `public/build` folder to the server app directory before running deploy.

Local steps:

```bash
cd /path/to/lezinproperties
npm install
npm run build
rsync -avz public/build/ user@server:/home/u417948420/domains/portal.lezinproperties.com/lezinproperties/public/build/
```

Server deploy step:

```bash
cd /home/u417948420/domains/portal.lezinproperties.com/lezinproperties
bash deploy-laravel-shared-hosting.sh --environment production --mode copy --force --skip-frontend-build
```

The deploy script now validates this automatically:

- If npm is missing and `public/build/manifest.json` is missing, deployment fails fast with a clear error.
- If npm is missing and prebuilt assets exist, deployment continues.

### Required root-level public assets

The deploy script now fails fast if required static assets used by footer branding are missing from `public/`, and verifies they are present in the served web root after publish.

Current required assets:

- `independent-operator.png`
- `KellerWilliams_Realty_Partners_Logo_CMYK.jpg`
- `lezin_properties_no_bg_full_logo.png`

This prevents silent deploys that later return `404` for footer logos.

## Production configuration files

Generated artifacts for server-side setup:

- `.env.production.example` (production environment template)
- `PRODUCTION_CONFIG_CHECKLIST.md` (deployment and validation checklist)
