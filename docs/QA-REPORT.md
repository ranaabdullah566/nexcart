# Storefront QA report

Automated crawl with Playwright + axe-core (WCAG 2 A / AA + best practice).

**Scope:** 31 storefront pages — home, shop (search / empty / deals / filtered), category, product (simple, variable, prescription), blog (list / topic / empty / article), contact, prescription upload, CMS page, track, empty cart, cart, checkout (guest and signed in), login, register, forgot password, account, order (guest and signed in), invoice, 404 —
in **dark and light** × **desktop (1440), tablet (820), phone (390)** = **186 page loads**, signed out, signed out with a cart, and signed in.
Plus Urdu (RTL) and Roman Urdu spot checks: 7 pages × 2 themes × 2 viewports × 2 languages.

## Result

| Check | Before fixes | After fixes |
|---|---|---|
| Page errors / console errors | 0 | 0 |
| Failed requests (4xx/5xx/network) | 0 | 0 |
| Broken images | 0 | 0 |
| Pages with horizontal overflow | 6 (phone, product tabs) | 6 — the tab bar is an intentional horizontal scroller |
| Urdu RTL pages with overflow / wrong direction | 0 | 0 |
| Colour contrast (mostly light theme) | 90 | 0 (re-checked on light and dark) |
| Heading order skipped | 168 | 0 |
| Links with no accessible name | 54 | 0 |
| Hidden links containing focusable buttons | 54 | 0 |
| Controls with no name / label | 10 | 0 |
| Invalid ARIA (radiogroup, star ratings) | 18 | 0 |
| Duplicate landmark names | 6 | 0 |
| Quick-view fragment opened directly (no title / lang) | 6 | 0 (redirects to the product page) |
| Tap targets under 30 px on phones | 140 | 66 (text links grown; the rest are inline links) |

## What was fixed

- **Light theme contrast:** the owner's accent colour is now darkened automatically for text (`--acc-ink`, ≥ 4.7:1) while buttons and gradients keep the brand colour; orange and green status text, badges, tags and the invoice button were darkened.
- **Cards:** the quick-view button no longer sits inside a hidden link; image links have no focusable children.
- **Names and labels:** account dropdown and icon-only sign-in link, cart thumbnail links, account profile fields, delivery-day radio group, search landmarks, star ratings (`role="img"`).
- **Headings:** footer column titles and the hero "Top deals" title are no longer skipped heading levels; the lead blog card uses the right level.
- **Phones:** newsletter box no longer overflows; small links and the quantity box are larger.
- **Safety net:** phpunit now forces its own in-memory database, cache and session so a stray environment can never touch a real database.

## Not covered

- Real devices and real browsers other than Chromium; screen-reader listening tests.
- Admin panel pages (only functionally tested by the PHPUnit suite).
- Stripe / reCAPTCHA / Google sign-in flows (need real keys).

---

# Admin panel QA

Same method as above: Playwright + axe-core on **76 admin pages** (every list, create and edit page, the order and prescription pages, the 7 report tabs, settings, storefront, theme, system, trash, server logs) × dark / light × desktop / tablet / phone = **456 page loads**, signed in as the owner, followed by interactive and permission checks.

| Check | Before fixes | After fixes |
|---|---|---|
| Pages that failed to load (non-200) | 0 | 0 |
| JavaScript errors | 0 | 0 |
| Failed requests | 3 (aborted by navigation) | 0 |
| Colour contrast (light theme) | 154 | 0 on our pages |
| Heading structure (no `h1`, skipped levels) | 66 | 0 |
| Tab lists with invalid ARIA (product, settings, storefront) | 12 + 12 | 0 (tabs still switch panes) |
| Icon buttons without a name (menu order, activity filter, theme preview) | 9 | 0 |
| Duplicate navigation landmark | 6 | 0 |
| Phone overflow (long blog slug, wide badges) | 2 pages | 0 |

**Interactive checks (dark/light × desktop/phone):** "New" opens the popup; an empty submit shows inline errors; a valid submit closes the popup and lists the record; delete asks for confirmation and can be cancelled; a confirmed delete removes the row; theme toggle; the phone menu opens the sidebar; table search filters rows; the pharmacist approves a prescription; an order moves to its next status; settings save round-trips. No JavaScript errors.

**Permissions:** pharmacist is refused settings, staff, system and product creation (403) and can open prescriptions and orders; rider is refused settings and prescriptions and can open deliveries (riders do see the order list by design); guests are sent to sign in; a shopper account is refused at `/admin` (403).

**Left as is — third-party:** the *Server logs* page is the Log Viewer package (Tailwind): it has 3 unnamed buttons, 1 unnamed link, a zoom-disabling viewport tag and one low-contrast label. They would need to be fixed upstream.

