# Keeply Local Windows Pilot

This pilot runs the complete Laravel application on one Windows computer without Cloud9 or an external hosting account.

## Runtime shape

- Web: Laravel development server bound only to `127.0.0.1`
- Database: `database/database.sqlite`
- Private documents and Action Packs: `storage/app/private`
- Queue: Laravel database worker on the `emails` and `default` queues
- Scheduler: Laravel `schedule:work`
- Process launch: detached from the PowerShell window, so closing that window does not stop Keeply
- Email: Laravel log mailer in `storage/logs/laravel.log`
- Sessions and cache: SQLite database tables

The launcher forces these local-safe settings only for its child processes. It does not rewrite `.env` or contact external services.

## Start

Open PowerShell in the application directory and run:

```powershell
.\scripts\start-local-pilot.ps1
```

Then open `http://127.0.0.1:8000` in a browser. To use another available port:

```powershell
.\scripts\start-local-pilot.ps1 -Port 8010
```

Use one port consistently because links written into queued reminder emails use that pilot URL.

## Stop

```powershell
.\scripts\stop-local-pilot.ps1
```

The stop script reads `storage/framework/local-pilot.json` and stops only the three process IDs created by the launcher.

## Logs

- Application and local email: `storage/logs/laravel.log`
- Web process: `storage/logs/pilot-web.log`
- Web errors: `storage/logs/pilot-web-error.log`
- Queue process: `storage/logs/pilot-queue.log`
- Queue errors: `storage/logs/pilot-queue-error.log`
- Scheduler process: `storage/logs/pilot-scheduler.log`
- Scheduler errors: `storage/logs/pilot-scheduler-error.log`

## Backup

Stop the pilot before copying data. Back up both:

1. `database/database.sqlite`
2. `storage/app/private`

Restoring only one of them can leave document metadata and private files out of sync.

## Local smoke gate

The start script:

1. locates PHP 8.3;
2. creates the local SQLite file when absent;
3. generates an application key only when it creates `.env` itself;
4. clears cached configuration;
5. applies migrations;
6. starts the web, queue, and scheduler processes;
7. confirms all three remain running; and
8. requests the landing page and verifies that it returns Keeply content.

## Limitations

- The site is private to this computer and has no public HTTPS URL.
- Emails are genuine queued Laravel messages, but are written to the application log rather than sent externally.
- SQLite and local files are suitable for a small private pilot, not multi-server production.
- The Laravel development server is not a production web server.
- Uptime, off-machine backups, external monitoring, and disaster recovery are not provided.

Because of these limitations, this completes a **local Windows pilot**, not backlog item K-063's original production-deployment acceptance gate. A later production deployment still needs a Laravel-capable host, managed relational database, private persistent storage, queue worker, scheduler, transactional email, HTTPS, and backups.
