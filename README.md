# Keeply

Keeply is an ownership-resolution application built with PHP and Laravel. It helps users understand their warranty position, organise evidence, track product-problem follow-ups, and produce factual Action Packs.

## Foundation status

The Laravel application skeleton is installed and bootable. Product features, authentication screens, and domain migrations are intentionally handled in later checkpoints.

- PHP 8.3
- Laravel 12
- Blade and Vite
- SQLite for the foundation smoke check
- MySQL/MariaDB target before Keeply domain migrations

## Local setup

1. Copy `.env.example` to `.env` if `.env` does not exist.
2. Run `composer install`.
3. Run `php artisan key:generate`.
4. Run `php artisan migrate`.
5. Run `npm install` when frontend work begins.
6. Start the application with `php artisan serve`.

The local XAMPP PHP 8.1 runtime is not compatible with Laravel 12. Use the installed PHP 8.3 command-line runtime.

## Product documents

The product plan, validation protocol, technical blueprint, and checkpoint backlog live one directory above this application.

## Security

Do not commit `.env`, production credentials, user documents, or generated private Action Packs. All product documents must eventually use authorised private download routes rather than the public directory.

## Local Windows pilot

Run the full local pilot with its web process, database queue worker, and scheduler:

```powershell
.\scripts\start-local-pilot.ps1
```

Open `http://127.0.0.1:8000`, then stop all three Keeply processes with:

```powershell
.\scripts\stop-local-pilot.ps1
```

See `docs/LOCAL_WINDOWS_PILOT.md` for architecture, logs, backup steps, and limitations.
