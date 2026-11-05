# NexCart

Fully dynamic, white-label e-commerce platform (Laravel 13 + MySQL) for many business types —
fast food, pharmacy, medical store, general online store. UI: **NEXUS** design system on Bootstrap 5.3,
dark/light themes, English / اردو (RTL) / Roman Urdu. See [PLAN.md](PLAN.md) for the roadmap.

## Run locally

```bash
composer install
cp .env.example .env && php artisan key:generate
# set DB_* in .env (MySQL), and APP_URL to the URL you serve on (needed for product image URLs)
php artisan migrate
php artisan storage:link
php artisan serve
```

Open `/setup` once to pick the business type and create the owner account.

## After pulling updates

```bash
composer install && php artisan migrate && php artisan nexcart:sync-permissions
```

`nexcart:sync-permissions` creates new permissions and resets the built-in roles to their defaults.

## Tests

```bash
php artisan test
```
