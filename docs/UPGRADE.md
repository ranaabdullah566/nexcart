# Upgrading

NexCart never updates itself: you stay in control of when your live store changes.

1. **Read** the new version’s notes in `CHANGELOG.md`.
2. **Back up** — Admin → Backups → *Back up now*, then download the file (or run `php artisan nexcart:backup`).
3. **Replace the application files** with the new release (keep your `.env` and the `storage/` folder). With Docker: `git pull && docker compose up -d --build`.
   Run `composer install --no-dev` if you replaced the code without the `vendor/` folder.
4. **Run** `php artisan nexcart:update` (the Docker entrypoint does the migration part for you). It
   takes a safety backup, puts the site in maintenance mode, runs the migrations, adds any new permissions, rebuilds caches and brings the site back.
5. Open **System health** and check there is nothing new in red.

## What an update keeps

- Your **roles and permissions** — roles you changed in *Roles & permissions* stay as you left them. New permissions are added to the owner role, and to other built-in roles only where their defaults include them.
  To deliberately restore the factory roles: `php artisan nexcart:sync-permissions --reset-roles`.
- Your **Storefront options, Interface text, menus, pages and home sections** (all stored in the database or in `storage/app/ui-overrides`).
- Uploaded images, prescriptions and backups in `storage/`.

If something goes wrong: `php artisan nexcart:restore <backup-file>` puts the database and uploads back exactly as they were
(then restore the old application files).

## Update notices

Set `NEXCART_UPDATE_URL` to a JSON feed such as `{"version":"1.1.0","notes":"Faster checkout","url":"https://…/nexcart-1.1.0.zip"}`
and System health will tell the owner when a newer version exists. It only *tells* — nothing is downloaded or installed.

## Version numbers

`MAJOR.MINOR.PATCH`. Patch = fixes. Minor = new features, safe to upgrade. Major = may need manual steps (always written up in the changelog).

## Coming from 1.0.0 to 1.1.0

Nothing manual: `nexcart:update` runs the new migrations (soft deletes, two-factor columns, Google ID, alerts, SEO fields, extra permissions).
Existing deleted records stay deleted; from now on deletes go to the Recycle Bin. Staff passwords are not forced to change, but
new passwords must be at least 10 characters with letters and numbers.
