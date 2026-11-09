# NexCart

Online pharmacy platform (Laravel 13 + MySQL) for selling medicines on the web — prescriptions,
batch & expiry tracking, delivery slots and riders, a health blog and customer reviews. A futuristic glass storefront
and a NEXUS admin (Bootstrap 5.3), dark / light themes, English / اردو (RTL) / Roman Urdu.
See [PLAN.md](PLAN.md) for the roadmap and [CHANGELOG.md](CHANGELOG.md) for what changed.

## What you get

**Storefront** — a glass / aurora design (light and dark) on every page, with a header that has search, a
“Deliver to” city picker, an **Upload prescription** button and trending searches, an “All categories” mega menu,
a command palette (`Ctrl/⌘ K` or `/`), a hero with floating capsules and a “Top deals” panel, medicine cards that show
the composition (salt), dosage form and pack sizes, a product page with “same composition, other brands”, a filterable shop,
a cart with a free-delivery bar, one-page checkout, order tracking, customer accounts, an automatic **customer-reviews wall**,
a **health blog**, a **Contact** page, an e-mail sign-up box, recently viewed medicines, an *Install app* button and branded
error pages. Everything works on phones, in Urdu right-to-left, and respects *reduce motion*.

**Admin** — medicines (pack sizes, custom fields such as composition / strength, SEO), categories, brands, inventory with batches and
expiry, orders, prescriptions and **prescription requests**, customers (add / edit / delete), coupons, shipping zones, delivery slots,
reviews, the **health blog** (with scheduling), a **messages** inbox with the newsletter list, reports
(sales, products, customers, inventory, finance & tax, operations, marketing — all exportable to CSV),
roles & permissions you can build yourself, an alerts inbox, an activity log, a Recycle Bin and backups.

**Make it yours (no code)** — Admin → **Storefront** switches parts of the shop on or off and changes their
wording per language; **Interface text** rewords any word in any language; **Menus** fill the menu bar, top bar
and footer columns; **Home page** has 13 section types (including an automatic customer-reviews wall and the latest health-blog articles); **Theme** sets colours, logo and mode.
See [docs/CUSTOMIZING.md](docs/CUSTOMIZING.md).

**Pharmacy tools** — prescription upload at checkout *or* straight from a photo (`/upload-prescription`), pharmacist review, batches / expiry,
delivery slots, riders, store pickup, reorder, customer reviews, abandoned-cart reminders.

**Guests** — nobody needs an account: guest checkout, order tracking by e-mail, prescription upload, contact form and newsletter.
After a guest order, creating an account or signing in attaches *that browser's* orders to it.

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
| [docs/QA-REPORT.md](docs/QA-REPORT.md) | What was tested on the storefront and admin, and the results |
| [scripts/qa/README.md](scripts/qa/README.md) | Re-running the browser QA crawl |

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

Adds 100 medicines (some with pack sizes), 100 customers and 100 orders, plus categories, brands and shipping.
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
php artisan test          # 246 tests; uses its own in-memory database, cache and session
```

Browser QA (every storefront and admin page, light / dark, desktop / tablet / phone, with accessibility checks) lives in
`scripts/qa` — see its README. The last results are in [docs/QA-REPORT.md](docs/QA-REPORT.md).

## Where things live

| Path | What |
|------|------|
| `app/Http/Controllers/Store`, `resources/views/store`, `resources/views/layouts/store.blade.php` | storefront |
| `app/Http/Controllers/Admin`, `resources/views/admin` | admin panel |
| `app/Support/Sf.php` | every Storefront option (one schema drives the admin form) |
| `public/assets/css/store-market*.css`, `public/assets/js/store-market.js` | storefront look and behaviour |
| `public/assets/css/store-future.css`, `public/assets/js/store-future.js` | the glass / aurora design layer, command palette, city picker, recently viewed, install button |
| `resources/views/errors` | branded error pages (work without a database) |
| `scripts/qa` | browser QA crawl and seed data |
| `public/assets/js/nx-validate.js` | form validation shown under each field (browser + server) |
| `lang/*.json`, `storage/app/ui-overrides/*.json` | built-in translations and the owner’s own wording |
