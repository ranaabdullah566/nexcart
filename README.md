# NexCart

Online pharmacy platform (Laravel 13 + MySQL) for selling medicines on the web — prescriptions,
batch & expiry tracking, delivery slots and riders. Marketplace-style storefront and a NEXUS admin
(Bootstrap 5.3), dark/light themes, English / اردو (RTL) / Roman Urdu. See [PLAN.md](PLAN.md) for the roadmap.

## What you get

**Storefront** — header with search, language and basket, an “All categories” mega menu, hero carousel with a
“Top deals” panel, product cards with discount badges and quick view, product page with
trust badges, tabs and related products, filterable shop (grid or list), cart with a free-delivery bar,
one-page checkout, order tracking, customer accounts, content pages, cookie bar, WhatsApp
and back-to-top buttons. Every page uses the same look and works on phones.

**Admin** — medicines (pack sizes, custom fields such as composition / strength, SEO), categories, brands, inventory with batches and
expiry, orders, customers (add / edit / delete), coupons, shipping zones, delivery slots, reviews, reports
(sales, products, customers, inventory, finance & tax, operations, marketing — all exportable to CSV),
roles & permissions you can build yourself, an alerts inbox, an activity log, a Recycle Bin and backups.

**Make it yours (no code)** — Admin → **Storefront** switches parts of the shop on or off and changes their
wording per language; **Interface text** rewords any word in any language; **Menus** fill the menu bar, top bar
and footer columns; **Home page** has 12 section types (including an automatic customer-reviews wall); **Theme** sets colours, logo and mode.
See [docs/CUSTOMIZING.md](docs/CUSTOMIZING.md).

**Pharmacy tools** — prescription upload and pharmacist review, batches / expiry, delivery slots, riders,
store pickup, reorder, customer reviews, abandoned-cart reminders.

**Security** — login lockout, two-factor (authenticator app), password policy, idle sign-out,
Content-Security-Policy, scanner blocking, reCAPTCHA, Google sign-in for customers, password reset by e-mail, audit trail.
See [docs/SECURITY.md](docs/SECURITY.md). No site can be promised unhackable — keep everything updated.

## Documentation

| Guide | For |
|-------|-----|
| [docs/INSTALL.md](docs/INSTALL.md) | Web installer, Docker, command line |
| [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) | Going-live checklist |
| [docs/ADMIN-GUIDE.md](docs/ADMIN-GUIDE.md) | Owner’s guide to every admin screen |
| [docs/CUSTOMIZING.md](docs/CUSTOMIZING.md) | Changing the storefront, wording, menus and home page |
| [docs/SECURITY.md](docs/SECURITY.md) | Protections that are built in, and what you must still do |
| [docs/UPGRADE.md](docs/UPGRADE.md) | Updating and rolling back |
| [docs/LICENSING.md](docs/LICENSING.md) | Issuing license keys (vendor) |
| [docs/SELLING.md](docs/SELLING.md) | One deployment per client, handing over a store |

## Run locally

```bash
composer install
cp .env.example .env && php artisan key:generate
# set DB_* in .env (MySQL), and APP_URL to the URL you serve on (needed for product image URLs)
php artisan migrate
php artisan storage:link
php artisan serve
```

Open the site once: `/install` (database) and `/setup` (store details, owner account). Production: see [docs/INSTALL.md](docs/INSTALL.md).

### Try it with 100 records

```bash
php artisan db:seed --class=DemoRecordsSeeder
```

Adds 100 products (some with sizes), 100 customers and 100 orders, plus categories, brands and shipping.
Every demo customer signs in with the password `Demo-pass-123`. Running it again adds another batch.
For a small sample medicine catalogue use `php artisan nexcart:demo` (and `--remove` to undo).

## After pulling updates

```bash
composer install && php artisan migrate && php artisan nexcart:sync-permissions
```

`nexcart:sync-permissions` creates new permissions. Roles you changed in **Roles & permissions** keep your
choices; the owner always receives everything. To deliberately put the built-in roles back to factory defaults:
`php artisan nexcart:sync-permissions --reset-roles`.

## Scheduled tasks

Add one cron entry on the server; Laravel then runs everything below by itself:

```cron
* * * * * cd /path/to/nexcart && php artisan schedule:run >> /dev/null 2>&1
```

| When | Task |
|------|------|
| every minute | scheduler heartbeat (shown on System health) |
| hourly | abandoned-cart reminder e-mails |
| 02:30 | nightly backup (if switched on) |
| 03:15 | empties old Recycle Bin items (if an auto-empty period is set) |
| daily | removes alerts older than 90 days |

## Main packages

`spatie/laravel-permission` (roles), `spatie/laravel-medialibrary` (images), `spatie/laravel-activitylog` (audit),
`opcodesio/log-viewer` (server logs, owner only), `laravel/socialite` (Google sign-in), `mckenziearts/laravel-notify`
(notifications), `bacon/bacon-qr-code` (two-factor QR).

## Tests

```bash
php artisan test
```

## Where things live

| Path | What |
|------|------|
| `app/Http/Controllers/Store`, `resources/views/store`, `resources/views/layouts/store.blade.php` | storefront |
| `app/Http/Controllers/Admin`, `resources/views/admin` | admin panel |
| `app/Support/Sf.php` | every Storefront option (one schema drives the admin form) |
| `public/assets/css/store-market*.css`, `public/assets/js/store-market.js` | storefront look and behaviour |
| `public/assets/js/nx-validate.js` | form validation shown under each field (browser + server) |
| `lang/*.json`, `storage/app/ui-overrides/*.json` | built-in translations and the owner’s own wording |
