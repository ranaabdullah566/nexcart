# Changelog

All notable changes to NexCart. Versions follow [semver](https://semver.org).

## Unreleased

### Reports
- New **Pharmacy (Rx)** report: prescription orders and their share, prescription revenue, approval rate, review waiting time, prescription vs other revenue by day, review results, top prescription medicines, reviews per pharmacist and the call-back requests. New permission `reports.pharmacy` (given by migration to every role that can already see reports).
- One period picker for every report (Today, Yesterday, 7 / 30 / 90 days, This month, Last month, This year, 1 year, Custom) with quick chips, plus **payment-method and city filters** on all order-based reports.
- **Print / PDF** (clean print layout with the store name, period and filters), **Export all (CSV)** with every table of a report in one file, plain-language highlights ("Revenue is up 12% …"), KPI cards with change vs the previous period (also for average order and items sold), a dashed **previous-period line** on *Revenue by day*, charts in the design's colours with whole-number axes.

### Storefront — five per row and sliders
- Product rows now show **5 cards per row** on desktop (home, saved and other full-width pages; 4 / 3 / 2 on smaller screens). Shop and category pages, which share the row with the filter sidebar, show one card fewer (4). Storefront options → *Products per row* is now 5 by default (3–6 selectable).
- Every row on the home page is a **slider** (products, categories, brands, blog, reviews, testimonials) with arrow buttons, swipe on phones and scroll-snap; arrows hide when everything already fits. Product-page rails use the same sliders. New home product sections default to 12 items.

### Admin
- **Add reviews from the admin** (Reviews → *Add review*): choose a product, name, rating, title, text, date and status. Staff-written reviews are marked *Added by staff*, can be edited later, and are never shown as a verified buyer; customers' own reviews stay unchangeable. New column `reviews.added_by` — run `php artisan migrate`.
- **New dashboard**: attention chips (orders, prescriptions, requests, messages, reviews), 8 KPI cards with trend vs the previous period, a 7/14/30/90-day sales trend (revenue line + order bars), orders by status, top products, sales by category, payment methods, busiest hours / weekdays, top cities, new customers, rating breakdown, top customers, recent orders, stock alerts and latest reviews. Sections follow the user's permissions.

### Mobile / layout fixes
- Footer was a five-column grid on phones (page scrolled sideways); header overflowed at 360px; product-page Save / Compare / Share / Ask tiles now fit; admin content can no longer scroll sideways. Checked storefront and admin at 360 / 390 / 768 / 1024px for horizontal overflow.

### Security — public forms
- New `SanitizeInput` middleware: every submitted text loses NUL / control characters and invalid UTF-8 and is trimmed (passwords untouched).
- New `NoMarkup` rule on names, addresses, notes, messages, titles and reviews: HTML tags, `javascript:`, inline event handlers and character-entity tricks are refused (ordinary `<3`, `5 > 3` are fine). Output stays escaped as before.
- Rate limits added to cart updates, order pages and invoices (token guessing), account area, compare, setup and sign-out; password length capped at 200.
- Missing `maxlength` / phone `pattern` attributes added to checkout, track, password and profile fields so browser checks match the server rules.
- `tests/Feature/FormSecurityTest.php` covers markup, control characters, lengths, file types, honeypot, rate limits and login lock-out.

### Theme — marketplace design settings
- Admin → Theme → **Marketplace design**: 10 colours (hex + picker), 5 ready-made colour sets, body / heading fonts (bundled fonts only), heading weight, text size, corner roundness, button shape, card shadow, page width, and "use in admin too". Live preview, contrast warnings, and *Reset to default*. Values are validated against allow-lists, so nothing can inject CSS.

### Admin — marketplace look
- The admin panel follows the same look as the storefront: white pages, navy sidebar, orange buttons, blue links, flat cards and light table headers (`admin-tq.css`). It is light-only; switch it off with Storefront options → *Admin panel in the same marketplace look* (`design.admin_marketplace`) to get the dark glass admin back.

### Customer account area
- `/account` is now a proper panel with a sidebar (tabs on phones): **Dashboard** (stats, orders on the way, quick actions), **My orders** (filter, track, invoice, order again), **Addresses** (up to 10, default address feeds checkout; the first order fills it automatically), **Prescriptions** (status and private file download), **Saved medicines**, **My reviews** and **Profile & password**. New table `user_addresses` — run `php artisan migrate`.

### Categories — 3 levels
- Categories are now capped at **3 levels** (category › sub-category › sub-sub-category). Admin → Categories shows each row's level with an indent, has a **Sub** button to add a child, and the parent list offers only categories that still have room; moving a branch that would exceed 3 levels is refused. The storefront menu (desktop mega menu and phone drawer) shows all three levels.

### Storefront — marketplace design
- The whole storefront (home, shop, product, cart, checkout, account, blog, contact, prescription upload, error pages, setup wizard) now follows a clean marketplace look: white pages, navy header / footer, orange buttons, blue prices. It is light-only and switchable in Admin → Storefront → *Design → Marketplace look* (`design.marketplace`); turn it off to get the previous glass / dark-mode theme back.
- **Saved medicines** (`/saved`, `/wishlist` redirects) and **Compare** (`/compare`) are back, for guests too, with header icons and counts, card buttons and a product-page tile. New Storefront options: `header.show_saved`, `header.show_compare`, `cards.show_wish`, `cards.show_compare`, `product.show_save`, `product.show_compare`, `product.show_disclaimer`, `product.disclaimer`.
- Product cards show brand / category, Genuine / Rx flag, Add-to-cart and four quick buttons; the buy box shows savings and a live total; login and sign-up are a split layout with *Browse as guest*.
- Hero title supports a highlighted part with `{curly braces}`.
- New Urdu / Roman Urdu strings for the above.

## 1.2.0
NexCart is now **pharmacy-only**: an online medicine store. Upgrading runs one migration that drops the removed tables (add-ons and loyalty data are discarded) — take a backup first, see [docs/UPGRADE.md](docs/UPGRADE.md).

### Removed
- Business types: fast food, medical store and general store. There is one pharmacy profile; the setup wizard no longer asks for a business type.
- **Kitchen display** and **Add-ons** (modifier groups) — medicines use pack-size variants instead.
- **Loyalty points** (earning, spending at checkout, settings, report, account counter).
- **Wishlist** and **Compare** (removed here, restored as *Saved medicines* and *Compare* in the next release — see Unreleased).

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

### Marketplace header
- The header now follows the classic marketplace layout: a navy announcement strip, a **white** header row with a big search box and an attached orange search button, a language pill, labelled icons (Shop, Basket, Sign in), then a nav row with a solid **All categories** button, *Deals* in orange and the links on the right (Track an order, *Deliver to*, *Upload prescription*). The mega menu opens as a clean white panel; on phones the hamburger sits left, the store name in the middle and the icons right, with the search box under it.
- It stays white in dark mode (switch off under Storefront → Header → *White marketplace-style header* to follow the theme). The mega menu also stays open when you click it after hovering (it used to close).

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
