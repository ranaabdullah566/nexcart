# NexCart — Product & Build Plan

Ek fully dynamic, white-label e-commerce platform jo multiple business types
(Fastfood, Pharmacy, Medical Store, Online Store, Grocery, Fashion, ...) ko sell
kiya ja sake.

## 1. Stack

| Layer | Choice | Kyun |
|---|---|---|
| Framework | **Laravel 13** (latest stable), PHP 8.3 | Requirement |
| DB | **MySQL 8** | Requirement |
| UI system | **NEXUS** (own design system: `theme.css` + `nexus.js`) on **Bootstrap 5.3**, Bootstrap Icons, jQuery | Same look across all projects; dark + light, glass UI, popups, toasts |
| Tables / forms / charts | DataTables (BS5, responsive), Select2, Chart.js | Search/sort/pages, searchable selects, dashboards |
| Admin panel | Blade + NEXUS (custom, permission-aware) | Full control, popups for add/edit |
| Storefront | Blade + NEXUS tokens (+ small JS/Alpine where needed) | Fast, SEO friendly |
| Auth / RBAC | Laravel auth + `spatie/laravel-permission` | Roles: Owner, Manager, Staff, Rider, Pharmacist |
| Media | `spatie/laravel-medialibrary` | Image conversions, WebP |
| Settings | DB-backed settings (`spatie/laravel-settings` style) | Admin se sab kuch change |
| Queue / Cache | Database queue → Redis (production) | Emails, invoices, stock alerts |
| Payments | Driver-based (Stripe, COD, Bank Transfer, local gateways) | Plug & play |
| Tests | PHPUnit (Laravel default) | Har phase ke saath tests |

> Fonts: Inter (text), Space Grotesk (headings), JetBrains Mono (numbers), Noto Naskh Arabic (Urdu).
> Languages: English, اردو (RTL), Roman Urdu — `lang/*.json`.

## 2. Core architecture decisions

1. **Business Profile system** — Installation ke waqt (setup wizard) business type
   choose hota hai: Fastfood / Pharmacy / Medical / General Store. Har profile ek
   *preset* hai jo modules, product fields, checkout steps, terminology aur theme
   on/off karta hai. Code ek hi rehta hai, behaviour config se badalta hai.
2. **Feature modules (toggle from admin)** — Variants, Modifiers/Add-ons (fastfood),
   Prescription upload (pharmacy), Batch & Expiry (pharmacy), Delivery slots,
   Table/Pickup, Coupons, Reviews, Wishlist, Multi-currency, Multi-language.
3. **Dynamic product schema (EAV-lite)** — Custom attributes + attribute sets per
   business type (e.g. pharmacy: salt/composition, dosage form, Rx required;
   fastfood: spice level, prep time).
4. **Single-tenant first, multi-tenant ready** — Har client ka apna deployment
   (simple, secure, easy to sell). Saare settings `store_id` aware rakhenge taake
   baad me SaaS (multi-tenant) me upgrade ho sake.
5. **Theme engine** — Design tokens (colors, radius, fonts, glass/neon effects) DB
   me; admin live-preview ke saath change kare. Section-based homepage builder.
6. **API-first internals** — Services/Actions layer; future mobile app ke liye REST API.

## 3. Checkout (Shopify jesa)

- Single page, 2-column: left form, right sticky order summary
- Express checkout row → Contact → Delivery (ship / pickup) → Shipping method →
  Payment → Pay now
- Guest checkout, auto-save address, inline validation, discount code field
- Order summary live update (shipping, tax, discount)
- Business-specific steps config se (e.g. Rx upload, delivery slot, order notes)
- Abandoned checkout recovery

## 4. Phases

### Phase 0 — Foundation ✅
- Laravel 13 project, MySQL config, env, git, `PLAN.md`
- Base packages, coding standards (Pint), PHPUnit, CI-ready structure
- Folder architecture (`app/Domain/*` ya modules)

### Phase 1 — Core platform & Admin shell ✅
- Filament install, custom futuristic admin theme
- Auth, roles & permissions, staff management
- Settings system (store info, logo, currency, tax, timezone)
- Setup wizard + Business Profile presets
- Activity log, media library

### Phase 2 — Catalog ✅
- Categories (nested), brands, tags
- Products: simple/variable, variants, SKU, price/compare price, images
- Dynamic attributes & attribute sets, modifiers/add-ons
- Inventory (stock, low-stock alerts, batch/expiry for pharmacy)
- Import/export CSV

### Phase 3 — Storefront (Futuristic UI) ✅
- Design system: dark/light, glassmorphism, gradients, smooth motion, 3D accents
- Homepage section builder, collection pages, filters/search (instant), product page
- Cart drawer, wishlist, quick view, SEO, responsive/PWA

### Phase 4 — Cart, Checkout & Orders ✅
- Cart engine, coupons/discounts, tax, shipping zones & rates
- Shopify-style one-page checkout
- Payment drivers (COD + 1-2 gateways), order lifecycle, invoices/PDF
- Customer accounts, order tracking, addresses

### Phase 5 — Business-specific modules
- Fastfood: modifiers, kitchen display, delivery zones, rider assignment, ETA
- Pharmacy/Medical: prescription upload & verification, Rx-only products, expiry
- Common: delivery slots, pickup, reorder

### Phase 6 — Admin customization & growth
- Theme editor (live preview), page builder, menus, banners, email templates
- Reports & analytics dashboard, notifications (email/SMS/WhatsApp)
- Reviews, loyalty/wallet, abandoned cart, multi-language & currency

### Phase 7 — Productization (sell karne ke liye)
- Installer, license keys, demo data per business type
- Docs, update mechanism, backups, Docker deploy
- Optional: multi-tenant SaaS mode, subscriptions

## 5. Is session ka agla step
Phase 0 complete → Phase 1 start.
