# Changelog

All notable changes to NexCart. Versions follow [semver](https://semver.org).

## 1.2.0
NexCart is now **pharmacy-only**: an online medicine store. Upgrading runs one migration that drops the removed tables (add-ons and loyalty data are discarded) — take a backup first, see [docs/UPGRADE.md](docs/UPGRADE.md).

### Removed
- Business types: fast food, medical store and general store. There is one pharmacy profile; the setup wizard no longer asks for a business type.
- **Kitchen display** and **Add-ons** (modifier groups) — medicines use pack-size variants instead.
- **Loyalty points** (earning, spending at checkout, settings, report, account counter).
- **Wishlist** and **Compare** (pages, buttons, header / footer / bottom-bar links, and their Storefront options).

### Changed
- Pharmacy profile now includes variants, pickup, riders and reviews as built-in modules.
- Sample data (`nexcart:demo`, Admin → System health, setup wizard) is a 24-item medicine catalogue with pack sizes and prescription items; `--type` is gone.
- 100-record seeder now creates medicines.
- Home page, product fields and report CSV no longer mention points.

### Storefront look
- New **Future** design layer on every customer page: animated aurora + grid backdrop, glass header that lights up on scroll, scroll-progress bar, deep-aurora hero with gradient headline, glass cards with cursor spotlight, rotating neon border and 3D tilt, staggered reveal, button ripples, floating glass dock on phones.
- **Command palette** — press `Ctrl/⌘ K` or `/` (or tap the search icon on phones) for instant medicine search and quick links.
- Branded **error pages** (404, 403, 419, 429, 500, 503) that work even when the database is down.
- Light-mode contrast fixes; checkout step numbers, alerts and the track-order placeholder corrected.
- Honours light/dark mode, the owner's theme colours, RTL and *reduce motion*.

### Pharmacy storefront (inspired by Pakistani pharmacy sites)
- **Upload prescription** (`/upload-prescription`): customers send a photo / PDF without building a cart; drag-and-drop with previews. Staff see it under **Prescription requests** (call back, close, delete → Recycle Bin) and get an alert. Files are private.
- **Deliver to** city picker in the header (remembered in the browser, prefills checkout and the prescription form), a header **Upload prescription** button, a phone floating button, and **Trending searches** chips. All switchable under Storefront → Header.
- Product cards show the **composition (salt)**, dosage form, strength and a *Pack sizes* chip; search also matches composition.
- Product page: **Same composition, other brands** rail, cheapest first.
- Category tiles pick a matching icon; new default home: trust strip, *Shop by need*, *Most ordered*, a prescription banner, new arrivals and a 3-step *How ordering works*; hero has a second *Upload prescription* button and floating capsules.

### Customer reviews wall
- New home section type **Customer reviews (automatic)**: shows real, approved reviews as a masonry wall with an overall rating, star distribution bars, *Verified buyer* badge, city (from the order), the product link and the store's reply. Pick the minimum star rating, how many to show and whether to show the summary. Hidden until at least one review is approved, and follows the Reviews module switch. Sample data now includes a few reviews (removed with it).

### Health blog
- **Health blog** at `/blog` and `/blog/{slug}`: latest-first cards with a large lead article, topic chips with counts, search, pagination, reading time, share buttons (WhatsApp, Facebook, X, copy link), related articles, a medical-advice disclaimer and a "Need medicines?" box. Included in the sitemap with SEO title / description and an Article og-image.
- Admin → **Health blog**: write articles with topic, author, summary, cover image, publish date (a future date **schedules** it), draft/published, search-engine fields and Urdu / Roman Urdu translations. Deleted articles go to the Recycle Bin.
- New home section type **Health blog (latest articles)** and a *Health blog* link in the menu bar (switchable under Storefront → Header); both stay hidden until there is an article.
- Sample data adds six general-advice articles with covers (removed with it).

### Guest experience
- **Contact page** (`/contact`): no account needed; shows phone, WhatsApp, e-mail, address and opening hours (new *Store address* and *Opening hours* settings). Messages land in **Admin → Messages** (new / read / replied, reply by e-mail, delete → Recycle Bin) and alert the team.
- **E-mail sign-up** box in the footer (switchable): duplicates are harmless, every address can unsubscribe with one link, **Admin → Messages → Subscribers** downloads active addresses as a formula-safe CSV.
- **Guest orders follow you**: after a guest order, creating an account or signing in adds *that browser's* orders to the account (never every order with the same e-mail), and the register form is prefilled. The checkout says "No account needed", and the order page offers "Create a free account".
- **Recently viewed** medicines (home, product and cart pages) — stored on the device only, with a Clear button.
- **Install app** button (Android / desktop prompt; iPhone shows the Add-to-Home-Screen steps) and a branded offline page.

### Quality
- Browser QA of 31 storefront pages and 76 admin pages (186 + 456 page loads: dark / light × desktop / tablet / phone) with axe accessibility checks, plus interactive and permission checks — see [docs/QA-REPORT.md](docs/QA-REPORT.md) and `scripts/qa`.
- Fixes from it: accent colour auto-darkened for text on the light theme, heading structure (admin pages now have an `h1`), tab and radio-group ARIA, names for icon-only buttons and links, no buttons inside hidden links, phone overflow fixes, quick-view opened directly redirects to the product page.
- `phpunit.xml` forces its own in-memory database, cache and session; the demo-data tests use a fake disk.

### Kept
- Prescriptions + pharmacist review, batches / expiry, delivery slots, riders, coupons, pickup, customer reviews, licence keys, 2FA, reports, backups, activity log, Recycle Bin, alerts, Google sign-in, reCAPTCHA, password reset.

## 1.1.0
A new marketplace-style storefront, a storefront you can customize from the admin, a safety net for deletes, and a security layer. Upgrading from 1.0.0 needs no manual steps — see [docs/UPGRADE.md](docs/UPGRADE.md).

### Storefront
- New look on **every** customer page: header with big search, language and basket, “All categories” mega menu, mobile drawer and bottom bar, hero carousel with a “Top deals” panel, trust strip, category tiles, new product cards, product page (gallery, buy box, tabs, policy cards, related rail, sticky buy bar), shop listing (filter panel, chips, sort, grid / list), cart (free-delivery progress), checkout (steps, numbered cards), order confirmation, account dashboard, order tracking, wishlist, content pages, sign in / sign up / two-factor, and the installer + setup wizard.
- **Compare** up to four products side by side.
- Cookie bar, WhatsApp and back-to-top buttons, social links, footer columns.
- Five new home sections: brands strip, how-it-works steps, call-to-action banner, customer quotes, questions & answers.

### Customize without code
- **Storefront options** (79 switches, numbers and per-language texts), **Interface text** (reword any word in any language), six **menu places**, SEO title / description on products, categories and pages, checkout and invoice switches, WhatsApp and six social links. See [docs/CUSTOMIZING.md](docs/CUSTOMIZING.md).
- Pages accept simple formatting (`# Heading`, lists, links) that is escaped first.

### Admin
- Products, customers and roles open in the popup like every other module; customers can be added, edited and deleted.
- **Recycle Bin** — everything soft-deletes; restore, erase, empty, auto-empty after N days. Delete confirmations everywhere.
- **Roles & permissions** — build custom roles with per-area permissions (reports, Recycle Bin, activity log, …). Updates no longer reset roles you changed.
- **Alerts inbox** with a bell (new orders, low stock, every notification), **Activity log** with filters and CSV, **Visit site** button, server logs (owner only).
- **Reports hub** — Sales, Products, Customers, Inventory, Finance & tax, Operations, Marketing, each with charts, tables and CSV.
- Laravel Notify powers every notification (drawn in the NEXUS toast style so the Content-Security-Policy stays strict).
- A form checker shows a message under every field in the browser and from the server.
- `php artisan db:seed --class=DemoRecordsSeeder` adds 100 products, customers and orders for trying things out.

### Security
- **Forgot password by e-mail** for customers and staff: one-time link, 60 minutes, no hint whether an address exists, signs out every device, never skips two-factor.
- Sign-in lockout, two-factor (authenticator app + recovery codes, optional for the whole team), password policy, idle sign-out, nonce-based Content-Security-Policy, scanner blocking, honeypot, more headers and rate limits, `no-store` on private pages, current-password check for account changes. Optional Google reCAPTCHA and Google sign-in for customers. See [docs/SECURITY.md](docs/SECURITY.md).

### Fixes
- Dashboard “today” and the 14-day chart used UTC days instead of the store’s timezone.
- Cancelling an order now returns its coupon use; a single-use code works again.
- Order numbers can no longer collide when two checkouts happen at once.
- A very negative add-on can no longer make a cart line negative.
- `nexcart:sync-permissions` / `nexcart:update` no longer overwrite customised roles (`--reset-roles` restores the factory ones on purpose).
- Titles with `&` in them were double-escaped in the page head.

## 1.0.0
First release.

- Storefront: futuristic NEXUS UI, dark / light, English / اردو (RTL) / Roman Urdu, home-page builder, shop with filters and instant search, product page with variants and add-ons, cart drawer, wishlist, SEO and PWA.
- Checkout and orders: one-page checkout, coupons, tax, shipping zones, store pickup, cash on delivery, bank transfer and Stripe, invoices, order tracking, customer accounts.
- Business modules: kitchen display, riders and ETA (fast food); prescription review, batches / expiry and delivery slots (pharmacy); reorder.
- Growth: live theme editor, pages and menus, e-mail templates, reports, reviews, cart recovery, loyalty points.
- Productization: web installer, license keys, demo data, backups, health page, Docker deployment.
