# SIMANO deployment lokal

## Production build

```powershell
npm ci
npm run build
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize
```

Set `APP_ENV=production`, `APP_DEBUG=false`, `APP_FORCE_HTTPS=true`, `SESSION_SECURE_COOKIE=true`, and `DB_CONNECTION=mysql` in `.env`. Keep `APP_KEY`, SSO secret, and DB password outside source control.

## Apache

Set Apache `DocumentRoot` to `D:/Apache24/htdocs/simano/public`. Enable `mod_rewrite`, `AllowOverride All`, and PHP. Never expose project root as document root.

## Scheduler

Windows Task Scheduler: run every minute:

```text
D:\Apache24\php\php.exe D:\Apache24\htdocs\simano\artisan schedule:run
```

Linux cron:

```text
* * * * * cd /path/to/simano && php artisan schedule:run >> /dev/null 2>&1
```

Inspect schedule with `php artisan schedule:list`.

## MariaDB backup

Set `DB_DUMP_BINARY` to full `mysqldump.exe` path when it is not in `PATH`, then test:

```powershell
php artisan app:backup-database
```

Backups are written to `storage/app/backups`. Copy them to separate protected storage and test restore periodically.
