# Owner’s quick guide

Everything is in the left menu. Languages (English / اردو / Roman Urdu) and light / dark mode are in the top bar,
next to **Visit site** (opens your shop in a new tab) and the **bell** (your alerts).

## First day
1. **Store settings** — name, tagline, contact, WhatsApp and social links, currency, tax, timezone, payment methods and reminders.
2. **Shipping** — zones by city with delivery prices, free-over amounts and store pickup. No zone = nobody can check out.
3. **Catalog** — categories, brands, products (simple or with pack-size variants), product fields, inventory.
4. **Storefront** — *Home page* sections, *Theme* (live preview), *Storefront options*, *Interface text*, *Pages*, *Menus*, *Email templates*. See [CUSTOMIZING.md](CUSTOMIZING.md).
5. **Security** — turn on two-factor for yourself, then decide if the whole team needs it. Add reCAPTCHA / Google sign-in keys if you want them.
6. **System health** — turn red/orange items green. **Backups** — switch on nightly backups.

## Every day
- **Orders** — click an order to move it along; status e-mails go to the customer by themselves. Unpaid bank/cash orders can be marked paid.
- **Alerts** (bell) — new orders, low stock and every message the system showed you, kept until you clear them. Alerts are personal: reading yours does not mark them read for others.
- **Customers** — people with an account can be added, edited and deleted here; guests who only ordered show with a *Create account* button.
- **Delivery:** assign a *rider* in the order; riders use *Deliveries* on their phone.
- **Pharmacy:** *Prescriptions* — approve or send back with a reason; medicines are not prepared until approved. *Delivery slots* limit orders per time window. *Inventory → Batches* shows what expires soon.
- **Reviews** — approve or reply.

## Reports
*Overview* plus seven reports — **Sales, Products, Customers, Inventory, Finance & tax, Operations, Marketing**. Pick 7 days, 30 days, 90 days, a year or your own dates. Each table has a CSV button.
Numbers follow one rule everywhere: *revenue* counts every order that is not cancelled or refunded (tax and shipping included), days follow your store’s timezone, and profit uses each product’s current cost.

## Deleting and restoring
Everything you delete goes to the **Recycle Bin** first (orders are never deleted — they are accounting records).
Restore an item, delete it for good, empty a whole list, or set the bin to empty itself after N days.
A deleted item still reserves its unique value (a coupon code, an SKU, a staff e-mail) until you restore or erase it.

## Staff, roles and permissions
**Staff** adds people. **Roles & permissions** lets you build your own role (for example *cashier*) and tick exactly what it may do, area by area. The built-in roles start like this and you may edit all of them except *owner*:

| Role | Starts with |
|------|-------------|
| owner | everything — always, and it cannot be edited or deleted |
| manager | everything except Store settings, System, Roles and deleting staff |
| staff | view catalog and customers, work orders and inventory |
| rider | see and update their assigned deliveries |
| pharmacist | review prescriptions, adjust stock |

A role cannot be deleted while people hold it. Updating NexCart never undoes the changes you made to roles.

## Keeping watch
- **Activity log** — who created, changed, deleted or restored what (with the old and new values), sign-ins, failures and security changes. Filter by person, event, record type or date; export to CSV.
- **Server logs** — technical error log (owner only).
- **Security** — your password, two-factor (QR code + recovery codes), team rules (idle sign-out, require two-factor), reCAPTCHA and Google sign-in keys.

## If something looks wrong
Open **System health** first. It names the problem (e-mail not configured, scheduler not running, missing image link) and says what to do.
