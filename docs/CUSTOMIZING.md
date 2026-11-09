# Customizing the storefront from the admin

Everything below is in the admin panel. No code, no files to edit. Changes show on the store straight away.

## Admin → Storefront (options)

One page, one tab per area. Each switch turns a part of the shop on or off; each text box changes its words.
Text boxes have one field per language: a language you leave empty falls back to **English**, and English falls
back to the **built-in wording** (which is already translated).

| Tab | You can change |
|-----|----------------|
| Top bar | on/off, text, the small links on the right |
| Header & menu | sticky header; “Deliver to” city picker and its cities; Upload prescription button; trending searches; Shop icon; language switcher; light/dark toggle; search hint and button; the “All categories” mega menu and its “Popular” tiles; Deals link; Track-order link |
| Home page | the “Top deals” panel on the hero (on/off, title, how many); hero auto-slide and speed |
| Product cards & listing | image shape and fit, products per row, products per page, category / rating / discount / quick view, the Add-to-cart wording |
| Product page | trust lines under the photos, small badges under the buttons, details table, Share / WhatsApp, the “Delivery & returns” tab (name, lines, policy cards), related products (title, how many), sticky buy bar on phones |
| Footer | social icons, contact lines, text under the logo, the three column titles, copyright line, legal links |
| Cookie bar & buttons | the cookie bar and its wording, the WhatsApp and back-to-top buttons |
| Checkout & invoice | ask for area / postal code / order notes; logo, tax number and footer text on invoices |
| Home page SEO | the title and description Google shows for the home page |

**List fields** (trust lines, badges, delivery & returns) take one item per line, parts separated by `|`:

```
shield-check|Secure checkout
truck|Delivery to your city
```

The first part is a [Bootstrap Icons](https://icons.getbootstrap.com) name. Delivery & returns lines are
`icon|title|text`. Leave a list empty to hide it; clear your custom text to get the built-in lines back.

## Admin → Interface text

Rewords **any** word or sentence the shop and admin show — for example “Cart” → “Basket” — in every language,
English included. Pick a language, search, type the new wording, save. Clear a box to go back to the built-in
wording; **Reset this language** removes all your changes. Your wording is stored in
`storage/app/ui-overrides/<language>.json` (keep that folder writable and in your backups).

## Admin → Menus

Six places hold links. A place with at least one active link shows **your** links instead of the built-in ones:

| Place | Where it appears |
|-------|------------------|
| Menu bar | next to “Deals” / “Shop” under the header |
| Top bar (right side) | the small links at the top right |
| Footer column 1 / 2 / 3 | the three link columns in the footer |
| Footer legal links | the line at the very bottom |

A link can point to a page, a category or any address.

## Admin → Home page

Add, reorder, hide and edit sections. Eleven types:

| Type | Use it for |
|------|------------|
| Hero banner | the big slider; several hero sections become slides |
| Category tiles | round tiles for chosen categories |
| Product row | newest, featured or one category |
| Brands strip | shop-by-brand pills |
| Promo banner / Call-to-action banner | a message with one or two buttons and a background image |
| Highlights | up to 4 icon + title + text items (shown as a trust strip under the hero) |
| How it works | up to 6 numbered or icon steps |
| Customer quotes | up to 6 testimonials |
| Questions & answers | up to 10 FAQ items |
| Text block | any paragraph |

Every text can be translated per language inside the section’s form.

## Admin → Pages, SEO and the Theme

- **Pages** (About, Returns, …) — plain text with a few conventions: `# Heading`, `- bullet`, `1. numbered`, a blank line for a new paragraph, and a pasted `https://…` becomes a link. Published pages are listed in the sidebar of every other content page.
- **SEO** — products, categories and pages have a *Search title* and *Search description*; empty means the name and description are used.
- **Theme** — colours, logo, favicon, corner roundness, default light/dark mode, with a live preview.

## For developers: adding an option

Every Storefront option is one line in `app/Support/Sf.php` (`schema()`): key, tab, type (`bool`, `int`, `select`,
`string`, `text`, `lines`), label and default. The admin form, validation and saving are generated from it.
Read it in Blade with `sf('group.key')` (switches and numbers) or `sft('group.key')` (text in the visitor’s language).
