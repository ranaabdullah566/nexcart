# Changelog

All notable changes to NexCart. Versions follow [semver](https://semver.org).

## Unreleased

### Added: card quantity stepper and “Save Rs X”
- A card whose product is in the basket swaps *Add to cart* for **− qty +** (− at 1 removes it); it follows the basket everywhere (drawer, cart page, other cards of the same product). Stock and “max per order” limits still apply and show a message. Only for simple products — products with pack sizes keep *Choose options*.
- Discounted cards show a green **Save Rs X** line under the price (the line is only reserved in a row when some card in it has a saving).

### Improved: product card UX
- Softer rounded card with a hover lift and a gentle picture zoom; the name turns blue on hover.
- **Add to cart** gives feedback: a spinner while it works, then a green *Added to cart ✓* for 1.6 s.
- **Low stock** shows an *Only N left in stock* chip on the picture (5 or fewer, when stock is tracked). **Out of stock** cards grey the picture and the button.
- Phones: the picture keeps only the heart (compare and quick view are hidden to avoid crowding the two narrow columns), badges and paddings are smaller.

### Fixed: pictures not showing when the site is opened on another address than APP_URL
- Picture links were built from `APP_URL` (e.g. `http://localhost:8000`), so opening the admin or store at `http://127.0.0.1:8000` made the browser block every uploaded picture (the Content-Security-Policy only allows images from the page's own address). Links now follow the address the visitor is on. E-mails and queued jobs still use `APP_URL`, so keep it set to your real domain in production.

### Changed: smaller card text
- Every text on product cards is about a notch smaller (category, name, tags, price, cut price, button and badges).

### Changed: no salt line on cards
- Product cards no longer show the composition (salt) line; the form / strength chips stay. The composition is still on the product page, drives the *same salt* alternatives, and search still finds products by it.

### Changed: product cards and the home slider
- Cards: the star-rating line is gone (and its *Show ratings* option); price and cut price sit on **one line**; the small icon row under *Add to cart* (heart, compare, cart, quick view) is replaced by three round icons on the picture — save, compare, quick view (always visible on touch, fade in on hover with a mouse); the extra cart icon is dropped.
- A row of cards no longer reserves space for the salt line or the tags line when **no card in that row** has one (a row where only some have them keeps the alignment).
- Home product rows are a real **slider**: no scrollbar, round arrows, page dots, auto-advance every 5.5 s that loops, pauses while hovered / focused / touched and is off for reduced-motion users.

### Changed: card badges
- Product cards no longer say *Genuine* (picture badge) or *Genuine product* (line above the price). The top-right badges are now **Out of stock**, **Rx**, **Featured** (products marked featured) and **New** (added in the last 14 days; Storefront options → Product cards → *“New” badge days*, 0 turns it off — set 0 or a small number after a big import, because every freshly imported product counts as new).

### Added: sample pictures
- Admin → Home page → **Add sample pictures** (or `php artisan nexcart:demo-images [--products=60]`) draws pictures with GD — no internet needed: a bottle, capsule, tablet or pack on a soft gradient for products (chosen from the name / dosage form), one for every category that has products, and wide art for hero / banner / call-to-action sections. Only things **without** a picture get one; uploaded pictures are never touched. **Remove sample pictures** (`--remove`) deletes exactly the drawn ones. The sample medicines (`nexcart:demo`) now use the same drawings.

### Added: sample home sections
- Admin → Home page → **Add sample sections** (or `php artisan nexcart:home-demo`) adds one switched-on sample of every kind — hero, highlights, category tiles, three product rows, promo banner, call-to-action, steps, customer quotes, reviews, blog, FAQ, text — so each block can be seen and tested. **Remove sample sections** (or `--remove`) deletes exactly those and nothing else. Reviews and blog stay hidden until a review is approved / an article is published; `php artisan nexcart:demo` loads sample medicines, reviews and articles for that.

### Fixed: WhatsApp button
- Official WhatsApp green with a white glyph, a white ring and a soft pulse (off for reduced motion). The `wa.me` link now carries the country code (`03004462436` → `923004462436`, code from Settings → phone code; before, a local number with a leading 0 opened a wrong chat).

### Changed: footer columns
- Four headed columns after the brand: **Store** (Menus → Footer – Shop, else All products / Deals), **Support** (Footer – Help and Footer – Company links, else Track an order / Contact us / My account), **Categories** (at most 5, busiest first) and **Contact Us** (phone, e-mail, address, hours, then the map and *Get directions*). The brand column keeps the logo, text and social icons. Titles are editable in Storefront options → Footer.

### Redesigned: footer
- Slim one-line sign-up bar with just an e-mail box (the big card is gone); *Install app* and *Cookie settings* removed from the footer.
- New layout: logo + short text, **contact details** (phone, e-mail, address, opening hours — from Settings), **social icons** (Facebook, Instagram, TikTok, X, YouTube, LinkedIn, WhatsApp — from Settings), a **Categories** column (busiest first), Shop / Help / Company columns and a **Google Map** with a *Get directions* button.
- Admin → Storefront options → 

### Changed: header search and clean-up
- The header search is wider and the instant dropdown now lists matching **categories** (only ones with products) above the **medicines**; searching in the shop also matches category names. The Ctrl+K search shows categories too.
- Removed the *Track an order* link from the top bar and the *Trending* chips under the header (their Storefront options are gone). On wide screens the Shop icon next to the search is hidden (the menu bar already has *Shop*).

### Changed: 20 products per page, and only categories with products are listed
- The shop shows 20 products per page by default (Storefront options → Products per page; a value you saved earlier is kept).
- Header menu, drawer, footer, shop sidebar and tiles, and the home *categories* section list only categories that have at least one active product. Empty categories stay in the admin and their direct links still work.

### Changed: categories are one flat level, and every category is shown
- Header menu, mobile drawer, footer, shop sidebar and the round tiles above the shop grid now list **all** active categories (they used to stop at 12 top-level ones and show 2nd / 3rd levels). The header *All categories* menu is a searchable multi-column list with product counts; the drawer has the same finder; the sidebar list scrolls inside its card.
- Admin → Categories no longer has *Parent category*, levels or *Sub* buttons; a parent sent by an old form is ignored. Migration `flatten_categories` turns existing sub-categories into normal categories (nothing is deleted). Category picture fallback now uses only the category's own products.

### Added: Product sync (Admin → Catalog → Product sync)
- Upload the weekly product sheet (.xlsx or .csv; columns sku, name, price, active, maximumSalesQuantity, isWeighted, baseWeight, baseWeightUnit, pricePerBaseUnit, barcode 1–5, category 1). The **SKU is the key**: new SKUs are added, known SKUs are updated, identical rows are skipped, so the same file can be uploaded every week. Options: update existing, use the sheet's *active* column for visibility, hide synced products missing from the sheet.
- Everything in the sheet is stored (barcodes, max per order, weighted / base weight / unit / price per base unit; unknown extra columns in `products.source_data`). Categories are created when missing. Stock, pictures and descriptions edited in the admin are never overwritten. Each run is kept in a history with counts and skipped rows; *Download current sheet* exports the same layout.
- Storefront: search matches barcodes; `maximumSalesQuantity` caps the quantity per order in the cart. Migration `add_sheet_sync_fields`; new dependency `openspout/openspout`.

### Removed: Brand module
- Brands are gone everywhere: Admin → Brands (menu, pages, Recycle Bin entry), the *Brand* field on products, the *brand* column of the product CSV, the *By brand* report, the shop's brand filter and pills, brand names on product cards / product page / compare / structured data, and the home page *Brands strip* section. Migration `drop_brands` deletes brand home sections, drops `products.brand_id` and the `brands` table (back up first — brand names are discarded). Old CSV files that still have a `brand` column import fine (the column is ignored).

### Shop sidebar — image-led filters (inspired by ronin.pk's round category pictures)
- **Shop by category** card: every top-level category has a round, ring-bordered picture, name and product count; it expands to its **sub-categories** (smaller pictures) and a third level as pills; counts include everything below a category; the current branch is highlighted and opened. The same pictures appear as **round category tiles** above the grid.
- **Pictures**: Admin → Categories → *Picture* (JPG / PNG / WebP, up to 3 MB); without an upload the first product picture of that branch is used, then an icon. Category pictures use `Category::imageUrl()`.
- New controls: brand **pills** with counts, **Over the counter / Prescription** segmented cards, **switches** for in stock / on sale / featured, a **dual-handle price slider** with Min / Max boxes and quick ranges (Under 500, 500–1,000 …). Each group is a soft white card; phones keep the slide-in filter sheet.

### Shop page redesign (using the UI/UX Pro Max skill)
- The skill's design-system search for "pharmacy online medicine e-commerce marketplace" recommended a **marketplace pattern** (search bar is the call to action, categories, trust) with an **accessible style** (16px+ text, visible 3px focus rings, 44px touch targets, WCAG contrast, reduced-motion). The shop follows it: a hero with a prominent *search in this list* field, scrolling category chips (sub-categories or siblings, with counts), a sticky toolbar (result count, sort, grid / list), removable active-filter chips with *Clear all*, an animated progress "you have seen n of N" pager with 44px page buttons, and a richer empty state (clear filters, upload prescription, popular categories).
- On phones the filters open as a **slide-in sheet** with a sticky *Clear all* / *Show n results* footer (nothing reloads until you press it); on desktop they stay in the sidebar. Cards fade in with a short stagger (transform / opacity only) and not at all with reduced motion.
- The skill's colour (green / blue) and Google-font suggestions were not applied: the store keeps its own palette (Admin → Theme) and the CSP only allows bundled fonts.

### Shop list view
- The grid / list toggle's *list* mode was broken by the fixed-size card layout (details stacked in the middle, actions hidden). It is now a proper row: image | category, name, composition, chips, rating, price | button and quick icons; on phones the image sits beside the details and the buttons span the row below.

### Checkout — rebuilt from Shopify's own checkout measurements
- The checkout now uses the measurements of Shopify's current one-page checkout (read from its checkout shell): 10px scale, system font, 18px section headings, 48px floating-label fields, 580px form column + 480px summary on a `#F5F5F5` panel, `#DEDEDE` borders, `#005BD1` accent, 64px thumbnails with a 22px quantity badge, 12px pay button, and the 1000px breakpoint where the summary turns into an "Order summary" bar. No breadcrumbs, no site header / footer.
- Fields and behaviour match too: Contact (+ "Email me with news and offers", which now really subscribes the address), Delivery with **Ship / Pick up**, Country/Region, **First name + Last name** (joined into the stored name on the server), Address, *Apartment, suite, etc.*, City, Postal code, Phone; shipping methods and delivery times as grouped radio rows; payment methods with the description opening under the chosen one; the button reads **Pay now** for card gateways and **Complete order** otherwise.

### Mini cart, cart page, recently viewed
- **Mini cart**: glass drawer with an animated free-delivery bar and glowing knob, line cards with Rx tag and per-line total / old price, quantity stepper, a "Complete your order" suggestion strip (one-tap add), savings line, shimmer Checkout button and trust row. `/cart.json` now returns `savings`, `free_over`, `suggest` and per-line `was` / `rx` (page HTML carries the light version).
- **Cart page**: gradient hero with a step tracker, free-delivery progress card, large line cards (stepper, **Save for later**, remove, old price), sticky glass summary with **discount code preview** and estimated total, an empty-cart state, and a *Recommended for you* slider of the standard product cards.
- **Recently viewed** now renders the standard product cards (same size, buttons and slider as everywhere else); the browser keeps only the ids and `/recent?ids=` returns the cards.

### Checkout — full Shopify layout
- The checkout is its own page layout (no site header / footer): logo, *Cart › Information › Shipping › Payment* breadcrumbs, one long form on a white left side (Contact → Delivery → Shipping method → Delivery time → Prescription → Payment → notes) and the grey order summary on the right with the same full-height edge. Floating-label fields, grouped radio rows with a blue selected state, "All transactions are secure and encrypted", a big **Complete order** button with *Return to cart*, and the store's legal links underneath. On phones the summary becomes the collapsible "Show order summary" bar under the logo. Accent colour follows the design's blue. All server rules, live shipping / coupon updates, captcha and honeypot are unchanged.

### Checkout summary and total (Shopify layout)
- The order summary follows Shopify's checkout: grey panel, product thumbnails with a grey quantity badge, a discount-code field whose Apply button turns dark once you type, *Subtotal · n items*, *Shipping* ("Enter shipping address" until a city is chosen), *Estimated taxes* (or "Including Rs x in taxes" when tax is included), a large **Total** with the currency code, *Total savings* when a code is used, and a big Place order button. On phones it collapses into Shopify's "Show order summary · total" bar. All figures still update live when the city, delivery method or code changes.

### Shop sidebar and header search (tuqqa.com style)
- The shop filters are now plain (no card): a filter search box, small-caps section headings, a 3-level **category tree** with expanders and counts, **brand** list with counts, **medicine type** (OTC / Rx), **availability & offers** (in stock, on sale, featured), product-field selects, **price** min / max and a *Clear all* button. New query options `type[]`, `on_sale`, `featured`.
- The header search is a fixed ~520px wide on desktop (it no longer stretches to the icons).

### Product cards — one size everywhere
- Every card now has the same slots with fixed heights (image 4:3, category line, two-line name, composition, chips, rating, seller, price + old price, button, quick icons); empty slots are kept as invisible placeholders, so cards on home, shop, category, saved, product-page rails and the quick lists are identical in size and price / button sit on the same line. Long text is trimmed with an ellipsis instead of growing the card. A shop-page bug at phone width (card columns squeezed to 41px by a leftover wide-layout rule) is fixed.

### Fixes from a full-site check
- **Contrast (WCAG AA):** the orange buttons / category labels (4.27:1), green flags, sale badges, struck-through prices, cancelled-order status, WhatsApp tile, mobile bottom bar and admin delta chips were just under 4.5:1. Default orange is now `#bb4f09`, green `#28803a`, sale `#c9401a`; the mobile bottom bar is solid white. An automated axe-core pass over 30 storefront, account and admin pages (desktop + phone) now reports no violations.
- **Mobile account sign-out:** the account sidebar's Sign out was a blank box on phones while the real button (Profile page) was hidden by a CSS rule that never applied. Fixed.

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
