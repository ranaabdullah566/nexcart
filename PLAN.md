# NexCart — Product & Build Plan

Ek online pharmacy platform — online medicine sale ke liye. (Shuru mein multiple business types
ka plan tha; ab product sirf pharmacy-only hai, v1.2.0.)

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

1. **Profile system** — Setup wizard ek *pharmacy profile* apply karta hai (modules, product fields,
   checkout steps, terminology, theme). Shuru mein multiple business types the; ab sirf pharmacy hai,
   lekin profile ka mechanism (`config/business_profiles.php`) wahi hai.
2. **Feature modules (toggle from admin)** — Variants (pack sizes), Prescriptions, Batch & Expiry,
   Delivery slots, Pickup, Riders, Coupons, Reviews, Multi-language.
3. **Dynamic product schema (EAV-lite)** — Custom attributes (composition / salt, dosage form,
   strength, manufacturer, Rx required) jo admin se add / edit ho sakti hain.
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
- Dynamic attributes (composition, dosage form, strength …)
- Inventory (stock, low-stock alerts, batch/expiry for pharmacy)
- Import/export CSV

### Phase 3 — Storefront (Futuristic UI) ✅
- Design system: dark/light, glassmorphism, gradients, smooth motion, 3D accents
- Homepage section builder, collection pages, filters/search (instant), product page
- Cart drawer, quick view, SEO, responsive/PWA

### Phase 4 — Cart, Checkout & Orders ✅
- Cart engine, coupons/discounts, tax, shipping zones & rates
- Shopify-style one-page checkout
- Payment drivers (COD + 1-2 gateways), order lifecycle, invoices/PDF
- Customer accounts, order tracking, addresses

### Phase 5 — Business-specific modules ✅
- (Fast food modules were built here and removed again in Phase 9 — NexCart is pharmacy-only.)
- Pharmacy: prescription upload & verification, Rx-only products, batch / expiry
- Common: delivery slots, pickup, riders, reorder

### Phase 6 — Admin customization & growth ✅ (multi-currency and SMS/WhatsApp APIs deferred)
- Theme editor (live preview), page builder, menus, banners, email templates
- Reports & analytics dashboard, notifications (email/SMS/WhatsApp)
- Reviews, abandoned cart, multi-language (loyalty points were removed in Phase 9)

### Phase 7 — Productization (sell karne ke liye) ✅ (multi-tenant SaaS deferred — see docs/SELLING.md)
- Installer, license keys, sample medicine data
- Docs, update mechanism, backups, Docker deploy
- Optional: multi-tenant SaaS mode, subscriptions

### Phase 8 — Safety, security & storefront overhaul ✅ (v1.1.0)
- Soft delete everywhere + Recycle Bin, activity log, alerts inbox, roles & permissions editor, customers CRUD
- Reports hub (7 reports + CSV), log viewer, Laravel Notify
- Security layer: lockout, 2FA, password policy, CSP, scanner guard, honeypot, reCAPTCHA, Google sign-in
- Marketplace-style storefront on every customer page, mega menu, new cards / product page / cart / checkout / account / auth / wizard
- Everything customisable from the admin: Storefront options, Interface text, menu places, 11 home section types, SEO fields
- Form validation in browser + server with messages under every field; 100-record demo seeder
- Docs: README, CUSTOMIZING, SECURITY, ADMIN-GUIDE, DEPLOYMENT, UPGRADE refreshed

### Phase 9 — Pharmacy-only ✅ (v1.2.0)
- Sirf pharmacy profile; fast food / medical store / general profiles, Kitchen display, Add-ons, Loyalty points, Wishlist and Compare hata diye
- Pharmacy demo data (24 medicines, pack sizes, Rx items), setup wizard mein business-type step nahi

### Phase 10 — Pharmacy storefront, guests, blog, reviews and QA ✅ (v1.2.0)
- "Future" design layer (aurora, glass, spotlight / tilt cards, command palette, scroll progress), light-mode contrast, branded error pages
- Pharmacy UX: upload prescription without a cart + admin *Prescription requests*, "Deliver to" city picker, trending searches, composition / pack chips, same-composition alternatives, pharmacy home defaults
- Customer reviews wall (automatic), health blog with scheduling, Contact page + Messages inbox, newsletter sign-up
- Guests: orders attach to the account created / signed into from the same browser, recently viewed, install-app button, offline page
- Browser QA of every storefront and admin page (light / dark × desktop / tablet / phone, axe accessibility, interactive and permission checks): [docs/QA-REPORT.md](docs/QA-REPORT.md), re-runnable from `scripts/qa`

## 5. Ideas not built yet
- Store locator / multi-branch stock, app-store badges (needs a native app)
- Newsletter *sending* (the sign-up list and CSV export exist; sending campaigns is not built)
- Vendors / marketplace mode (sellers, commissions, payouts) — only if you want to run a multi-seller marketplace
- SMS / WhatsApp order notifications
- Editable order statuses and invoice design, admin screen for the admin’s own labels in other languages
- Multi-currency, multi-tenant SaaS (see docs/SELLING.md)
