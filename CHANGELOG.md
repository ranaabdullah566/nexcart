# Changelog

All notable changes to NexCart. Versions follow [semver](https://semver.org).

## 1.0.0
First release.

- Storefront: futuristic NEXUS UI, dark / light, English / اردو (RTL) / Roman Urdu, home-page builder, shop with filters and instant search, product page with variants and add-ons, cart drawer, wishlist, SEO and PWA.
- Checkout and orders: one-page checkout, coupons, tax, shipping zones, store pickup, cash on delivery, bank transfer and Stripe, invoices, order tracking, customer accounts.
- Business modules: kitchen display, riders and ETA (fast food); prescription review, batches / expiry and delivery slots (pharmacy); reorder.
- Growth: live theme editor, pages and menus, e-mail templates, reports, reviews, cart recovery, loyalty points.
- Productization: web installer, license keys, demo data, backups, health page, Docker deployment.

## Unreleased — Safety net & security
- Products now create/edit in the popup like every other module.
- Everything soft-deletes; **Recycle Bin** to restore or permanently delete, optional auto-purge.
- Delete confirmation dialog on every delete; opcodesio/log-viewer (owner only); Activity log.
- Security: login lockout, TOTP two-factor, password policy, idle sign-out, nonce CSP, scanner/IP ban, extra headers, rate limits. See docs/SECURITY.md.
