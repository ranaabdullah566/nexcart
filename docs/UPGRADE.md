# Upgrading

NexCart never updates itself: you stay in control of when your live store changes.

1. **Read** the new version’s notes in `CHANGELOG.md`.
2. **Back up** — Admin → Backups → *Back up now*, then download the file (or run `php artisan nexcart:backup`).
3. **Replace the application files** with the new release (keep your `.env` and the `storage/` folder). With Docker: `git pull && docker compose up -d --build`.
4. **Run** `php artisan nexcart:update` (the Docker entrypoint does the migration part for you). It
   takes a safety backup, puts the site in maintenance mode, runs the migrations, refreshes roles and permissions, rebuilds caches and brings the site back.
5. Open **System health** and check there is nothing new in red.

If something goes wrong: `php artisan nexcart:restore <backup-file>` puts the database and uploads back exactly as they were
(then restore the old application files).

## Update notices

Set `NEXCART_UPDATE_URL` to a JSON feed such as `{"version":"1.1.0","notes":"Faster checkout","url":"https://…/nexcart-1.1.0.zip"}`
and System health will tell the owner when a newer version exists. It only *tells* — nothing is downloaded or installed.

## Version numbers

`MAJOR.MINOR.PATCH`. Patch = fixes. Minor = new features, safe to upgrade. Major = may need manual steps (always written up in the changelog).
