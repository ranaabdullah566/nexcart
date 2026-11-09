<?php

namespace App\Support;

/**
 * Every storefront option the owner can change from Admin → Storefront.
 * One schema drives the admin form, validation, saving and reading, so adding an option is a single line here.
 *
 * Types: bool, int, select, string (one value for all languages), text / lines (one value per language;
 * "lines" is a list, one item per line, parts separated by |).
 */
class Sf
{
    public const GROUPS = [
        'topbar' => ['Top bar', 'megaphone'], 'header' => ['Header & menu', 'layout-text-sidebar-reverse'], 'home' => ['Home page', 'house-door'],
        'cards' => ['Product cards & listing', 'grid-3x3-gap'], 'product' => ['Product page', 'box-seam'], 'footer' => ['Footer', 'layout-text-window'],
        'cookie' => ['Cookie bar & buttons', 'shield-check'], 'checkout' => ['Checkout & invoice', 'receipt'], 'seo' => ['Home page SEO', 'search'],
    ];

    /** @return array<string, array{group:string, type:string, label:string, default:mixed, options?:array, min?:int, max?:int, hint?:string}> */
    public static function schema(): array
    {
        $b = fn (string $g, string $l, bool $d = true, ?string $h = null) => ['group' => $g, 'type' => 'bool', 'label' => $l, 'default' => $d, 'hint' => $h];
        $t = fn (string $g, string $l, string $d, ?string $h = null) => ['group' => $g, 'type' => 'text', 'label' => $l, 'default' => $d, 'hint' => $h];
        $n = fn (string $g, string $l, int $d, int $min, int $max) => ['group' => $g, 'type' => 'int', 'label' => $l, 'default' => $d, 'min' => $min, 'max' => $max];
        $lines = fn (string $g, string $l, string $d, string $h) => ['group' => $g, 'type' => 'lines', 'label' => $l, 'default' => $d, 'hint' => $h];

        return [
            'topbar.enabled' => $b('topbar', 'Show the top bar'),
            'topbar.text' => $t('topbar', 'Top bar text', '', 'Leave empty to show the announcement / tagline. The announcement bar (Settings) still wins when it is on.'),
            'topbar.show_links' => $b('topbar', 'Show the small links on the right (from Menus → Top bar, or Track an order + phone)'),

            'design.marketplace' => $b('header', 'Marketplace design (white, navy and orange — like a classic online marketplace)', true, 'Switch off for the dark glass look. The marketplace design is always light.'),
            'design.admin_marketplace' => $b('header', 'Admin panel in the same marketplace look (light, navy sidebar)', true, 'Switch off for the dark glass admin. The marketplace admin is always light.'),
            'header.light' => $b('header', 'White marketplace-style header (stays white in dark mode)', true, 'Switch off to let the header follow the light / dark theme.'),
            'header.sticky' => $b('header', 'Keep the header at the top while scrolling'),
            'header.show_shop' => $b('header', 'Shop icon'), 'header.show_saved' => $b('header', 'Saved (wishlist) icon'), 'header.show_compare' => $b('header', 'Compare icon'),
            'header.show_lang' => $b('header', 'Language switcher'), 'header.show_theme' => $b('header', 'Light / dark toggle'),
            'header.search_placeholder' => $t('header', 'Search box hint', 'Search products, categories…'), 'header.search_button' => $t('header', 'Search button text', 'Search'),
            'header.show_mega' => $b('header', 'Show the “All categories” mega menu'), 'header.mega_label' => $t('header', 'Mega menu button text', 'All categories'),
            'header.mega_recommended' => $b('header', 'Show the “Popular” tiles inside the mega menu'), 'header.mega_rec_title' => $t('header', 'Popular tiles title', 'Popular right now'),
            'header.mega_rec_sub' => $t('header', 'Popular tiles subtitle', 'A quick way into what shoppers look for most.'),
            'header.show_location' => $b('header', 'Show the “Deliver to” city picker'), 'header.cities' => $t('header', 'Cities in the picker (comma separated)', 'Karachi, Lahore, Islamabad, Rawalpindi, Faisalabad, Multan, Peshawar, Quetta'),
            'header.show_rx' => $b('header', 'Show the “Upload prescription” button'), 'header.rx_label' => $t('header', 'Upload prescription button text', 'Upload prescription'),
            'header.show_trending' => $b('header', 'Show trending searches under the header'), 'header.trending' => $t('header', 'Trending searches (comma separated)', 'Paracetamol, Fever, Vitamin C, Cough, Diabetes'), 'header.trending_label' => $t('header', 'Trending label', 'Trending'),
            'header.show_blog' => $b('header', 'Show the “Health blog” link in the menu bar'), 'header.blog_label' => $t('header', 'Health blog link text', 'Health blog'),
            'footer.show_newsletter' => $b('footer', 'Show the e-mail sign-up box'), 'footer.newsletter_title' => $t('footer', 'Sign-up title', 'Get health tips & offers'), 'footer.newsletter_sub' => $t('footer', 'Sign-up text', 'One useful email now and then. Unsubscribe any time.'),
            'header.show_deals' => $b('header', 'Show the Deals link'), 'header.deals_label' => $t('header', 'Deals link text', 'Deals'),
            'header.show_shop_link' => $b('header', 'Show the Shop link in the menu bar'), 'header.show_track' => $b('header', 'Show the “Track an order” link'), 'header.track_label' => $t('header', 'Track link text', 'Track an order'),

            'home.hero_deals' => $b('home', 'Show the “Top deals” panel on the hero'), 'home.hero_deals_title' => $t('home', 'Top deals panel title', 'Top deals'),
            'home.hero_deals_count' => $n('home', 'How many deals in the panel', 3, 1, 6),
            'home.hero_autoplay' => $b('home', 'Slide the hero automatically'), 'home.hero_seconds' => $n('home', 'Seconds per slide', 6, 3, 30),

            'cards.ratio' => ['group' => 'cards', 'type' => 'select', 'label' => 'Image shape', 'default' => '1 / 1', 'options' => ['1 / 1' => 'Square', '4 / 3' => 'Landscape 4:3', '3 / 4' => 'Portrait 3:4', '16 / 9' => 'Wide 16:9']],
            'cards.fit' => ['group' => 'cards', 'type' => 'select', 'label' => 'Image fit', 'default' => 'contain', 'options' => ['contain' => 'Show the whole picture', 'cover' => 'Fill the box (crop)']],
            'cards.cols' => ['group' => 'cards', 'type' => 'select', 'label' => 'Products per row (desktop)', 'default' => '6', 'options' => ['3' => '3', '4' => '4', '5' => '5', '6' => '6']],
            'cards.per_page' => $n('cards', 'Products per page in the shop', 12, 4, 48),
            'cards.show_category' => $b('cards', 'Show the category name'), 'cards.show_rating' => $b('cards', 'Show ratings'), 'cards.show_discount' => $b('cards', 'Show the discount badge'),
            'cards.show_quick' => $b('cards', 'Quick view button'), 'cards.show_wish' => $b('cards', 'Save (heart) button'), 'cards.show_compare' => $b('cards', 'Compare button'),
            'cards.add_label' => $t('cards', 'Add-to-cart button text', 'Add to cart'),

            'product.show_trust' => $b('product', 'Show the trust lines under the photos'),
            'product.trust_items' => $lines('product', 'Trust lines', "shield-check|Secure checkout\ntruck|Delivery to your city", 'One per line: icon|text  (icons: Bootstrap Icons names, e.g. shield-check, truck, award)'),
            'product.show_listed' => $b('product', 'Show “Listed … ago”'),
            'product.show_meta' => $b('product', 'Show the details table (SKU, availability, brand…)'),
            'product.show_disclaimer' => $b('product', 'Show the notice under the photos'), 'product.disclaimer' => $t('product', 'Notice under the photos', 'Always read the leaflet and follow your doctor’s or pharmacist’s advice. Prescription medicines are supplied only against a valid prescription.'),
            'product.show_share' => $b('product', 'Share button'), 'product.show_save' => $b('product', 'Save button'), 'product.show_compare' => $b('product', 'Compare button'), 'product.show_whatsapp' => $b('product', 'WhatsApp “Ask us” button'),
            'product.bb_trust' => $lines('product', 'Small badges under the buttons', "patch-check-fill|Genuine products\nlock-fill|Private & secure\ntruck|Fast delivery", 'One per line: icon|text. Leave empty to hide.'),
            'product.show_care' => $b('product', 'Show the “Delivery & returns” tab'), 'product.care_label' => $t('product', 'That tab’s name', 'Delivery & returns'),
            'product.care_items' => $lines('product', 'Delivery & returns text', "truck|Delivery|Shipping cost and delivery time are shown at checkout for your city.\nshield-lock|Secure payment|Your details stay private and safe.\narrow-counterclockwise|Returns|Problem with your order? Contact us and we will make it right.", 'One per line: icon|title|text'),
            'product.care_policies' => $b('product', 'Show footer pages as policy cards in that tab'), 'product.care_policies_title' => $t('product', 'Policy cards title', 'Policies that protect your order'),
            'product.show_related' => $b('product', 'Show related products'), 'product.related_title' => $t('product', 'Related products title', 'Related products'),
            'product.related_sub' => $t('product', 'Related products subtitle', 'Hand-picked to go with this item.'), 'product.related_count' => $n('product', 'How many related products', 8, 2, 16),
            'product.show_sticky' => $b('product', 'Sticky buy bar on phones'),

            'footer.show_socials' => $b('footer', 'Show social icons'), 'footer.show_contact' => $b('footer', 'Show email & phone in the footer'),
            'footer.about' => $t('footer', 'Short text under the logo', '', 'Leave empty to use the tagline.'),
            'footer.shop_title' => $t('footer', 'Column 1 title', 'Shop'), 'footer.help_title' => $t('footer', 'Column 2 title', 'Help'), 'footer.company_title' => $t('footer', 'Column 3 title', 'Company'),
            'footer.copyright' => $t('footer', 'Copyright line', '', 'Leave empty for “© year store name. All rights reserved.”'),
            'footer.show_legal' => $b('footer', 'Show the small legal links (from Menus → Footer)'),

            'cookie.enabled' => $b('cookie', 'Ask visitors about cookies'), 'cookie.title' => $t('cookie', 'Cookie bar title', 'Your privacy, your choice'),
            'cookie.body' => $t('cookie', 'Cookie bar text', 'We use essential cookies to keep the store secure and working. With your permission, optional cookies help us improve it.'),
            'cookie.accept_label' => $t('cookie', 'Accept button', 'Accept all'), 'cookie.essential_label' => $t('cookie', 'Essential-only button', 'Essential only'),
            'float.whatsapp' => $b('cookie', 'WhatsApp floating button (needs a WhatsApp number in Settings)'), 'float.top' => $b('cookie', 'Back-to-top button'),

            'checkout.show_area' => $b('checkout', 'Ask for the area'), 'checkout.show_postal' => $b('checkout', 'Ask for the postal code'), 'checkout.show_notes' => $b('checkout', 'Ask for order notes'),
            'invoice.show_logo' => $b('invoice', 'Show the logo on invoices'), 'invoice.tax_id' => ['group' => 'checkout', 'type' => 'string', 'label' => 'Your tax / NTN number on invoices', 'default' => '', 'max' => 40],
            'invoice.footer' => $t('checkout', 'Invoice footer text', '', 'e.g. “Goods once sold are not returnable without the invoice.”'),

            'seo.title' => $t('seo', 'Home page title', '', 'Leave empty to use “Store name — tagline”.'), 'seo.description' => $t('seo', 'Home page description', '', 'Shown in Google results and when the link is shared.'),
        ];
    }

    public static function get(string $key): mixed
    {
        $def = static::schema()[$key] ?? null;
        if (! $def) {
            return null;
        }
        $v = setting('sf.'.$key);

        return $v === null ? $def['default'] : match ($def['type']) {
            'bool' => (bool) $v, 'int' => (int) $v, default => $v
        };
    }

    public static function on(string $key): bool
    {
        return (bool) static::get($key);
    }

    /** Text for the visitor's language: their language, then the owner's English, then the built-in default (itself translatable). */
    public static function text(string $key): string
    {
        $stored = (array) setting('sft.'.$key, []);
        $v = $stored[app()->getLocale()] ?? null;
        $v = filled($v) ? $v : ($stored['en'] ?? null);
        if (filled($v)) {
            return (string) $v;
        }
        $default = (string) (static::schema()[$key]['default'] ?? '');

        return $default === '' ? '' : __($default);
    }

    /** @return array<int, array<int, string>> each line split on | (built-in defaults get their words translated) */
    public static function lines(string $key): array
    {
        $stored = (array) setting('sft.'.$key, []);
        $custom = filled($stored[app()->getLocale()] ?? null) || filled($stored['en'] ?? null);
        $raw = $custom ? static::text($key) : (string) (static::schema()[$key]['default'] ?? '');
        $rows = [];
        foreach (preg_split('/\r\n|\r|\n/', $raw) as $line) {
            if (trim($line) === '') {
                continue;
            }
            $parts = array_map('trim', explode('|', $line));
            $rows[] = $custom ? $parts : array_map(fn ($p, $i) => $i === 0 ? $p : __($p), $parts, array_keys($parts));
        }

        return $rows;
    }

    /** Raw value of a localized field for the admin form. */
    public static function stored(string $key, string $locale): string
    {
        return (string) (((array) setting('sft.'.$key, []))[$locale] ?? '');
    }

    public static function icon(?string $name, string $fallback = 'check-circle'): string
    {
        return preg_match('/^[a-z0-9-]{2,40}$/', (string) $name) ? $name : $fallback;
    }
}
