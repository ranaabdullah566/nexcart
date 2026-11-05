# NexCart

Fully dynamic, white-label e-commerce platform (Laravel 13 + MySQL) for many business types —
fast food, pharmacy, medical store, general online store. UI: **NEXUS** design system on Bootstrap 5.3,
dark/light themes, English / اردو (RTL) / Roman Urdu. See [PLAN.md](PLAN.md) for the roadmap.

## Documentation

| Guide | For |
|-------|-----|
| [docs/INSTALL.md](docs/INSTALL.md) | Web installer, Docker, command line |
| [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) | Going-live checklist |
| [docs/UPGRADE.md](docs/UPGRADE.md) | Updating and rolling back |
| [docs/LICENSING.md](docs/LICENSING.md) | Issuing license keys (vendor) |
| [docs/SELLING.md](docs/SELLING.md) | One deployment per client, handing over a store |
| [docs/ADMIN-GUIDE.md](docs/ADMIN-GUIDE.md) | Owner’s quick guide |

## Run locally

```bash
composer install
cp .env.example .env && php artisan key:generate
# set DB_* in .env (MySQL), and APP_URL to the URL you serve on (needed for product image URLs)
php artisan migrate
php artisan storage:link
php artisan serve
```

Open the site once: `/install` (database) and `/setup` (business type, owner account). Production: see [docs/INSTALL.md](docs/INSTALL.md).

## After pulling updates

```bash
composer install && php artisan migrate && php artisan nexcart:sync-permissions
```

`nexcart:sync-permissions` creates new permissions and resets the built-in roles to their defaults.

## Scheduled tasks

Cart-reminder e-mails run from Laravel's scheduler. Add one cron entry on the server:

```cron
* * * * * cd /path/to/nexcart && php artisan schedule:run >> /dev/null 2>&1
```

## Tests

```bash
php artisan test
```
