<?php

namespace App\Support;

/**
 * Owner-editable look of the marketplace design (storefront and admin): colours, fonts, shape.
 * Values live in settings as design.<key>; this class validates them, supplies defaults / presets
 * and turns them into CSS variables (Admin → Theme → Marketplace design).
 */
class Design
{
    /** colour key => [label, default, help] */
    public const COLORS = [
        'navy' => ['Header strip, footer & admin sidebar', '#0c3b68', 'The dark brand colour.'],
        'blue' => ['Links & prices', '#1b74bb', 'Links, prices and highlights.'],
        'orange' => ['Buttons (calls to action)', '#c9590b', 'Add to cart, Search, Checkout and other main buttons.'],
        'deal' => ['Sale / discount', '#d9481c', 'Discount badges and saved items.'],
        'green' => ['Success / in stock', '#2f8a3b', 'In-stock and success messages.'],
        'bg' => ['Page background', '#f5f8fc', ''],
        'card' => ['Cards & header', '#ffffff', ''],
        'ink' => ['Text', '#2b3136', ''],
        'muted' => ['Secondary text', '#5d646b', ''],
        'line' => ['Borders', '#e1e7ee', ''],
    ];

    /** font key => [label, CSS stack] — only fonts bundled with the app (the CSP blocks external fonts). */
    public const FONTS = [
        'inter' => ['Inter — clean & modern', "'Inter', 'Segoe UI', system-ui, -apple-system, Roboto, Arial, sans-serif"],
        'space' => ['Space Grotesk — techy', "'Space Grotesk', 'Inter', system-ui, sans-serif"],
        'system' => ['System default — fastest', "system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif"],
        'serif' => ['Serif — classic', "Georgia, 'Times New Roman', serif"],
        'mono' => ['Monospace — code style', "'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo, monospace"],
    ];

    public const BUTTONS = ['square' => ['Square', '4px'], 'rounded' => ['Rounded', '9px'], 'pill' => ['Pill', '999px']];

    public const SHADOWS = ['none' => ['Flat (no shadow)', 'none'], 'soft' => ['Soft', '0 1px 2px rgba(12,59,104,.06), 0 6px 18px -10px rgba(12,59,104,.22)'], 'raised' => ['Raised', '0 2px 4px rgba(12,59,104,.08), 0 14px 30px -12px rgba(12,59,104,.35)']];

    public const WIDTHS = ['1140' => 'Narrow (1140px)', '1280' => 'Standard (1280px)', '1440' => 'Wide (1440px)'];

    public const PRESETS = [
        'tuqqa' => ['Navy & orange (default)', ['navy' => '#0c3b68', 'blue' => '#1b74bb', 'orange' => '#c9590b', 'deal' => '#d9481c', 'green' => '#2f8a3b', 'bg' => '#f5f8fc']],
        'emerald' => ['Emerald pharmacy', ['navy' => '#064e3b', 'blue' => '#0f766e', 'orange' => '#b45309', 'deal' => '#be123c', 'green' => '#15803d', 'bg' => '#f3faf7']],
        'royal' => ['Royal purple', ['navy' => '#2e1065', 'blue' => '#6d28d9', 'orange' => '#be185d', 'deal' => '#c2410c', 'green' => '#15803d', 'bg' => '#f7f5fc']],
        'crimson' => ['Crimson care', ['navy' => '#4a0d1c', 'blue' => '#b42343', 'orange' => '#0f6b8f', 'deal' => '#c2410c', 'green' => '#15803d', 'bg' => '#fbf6f7']],
        'slate' => ['Slate & teal', ['navy' => '#1e293b', 'blue' => '#0369a1', 'orange' => '#0f766e', 'deal' => '#c2410c', 'green' => '#15803d', 'bg' => '#f4f6f9']],
    ];

    public static function defaults(): array
    {
        $d = [];
        foreach (self::COLORS as $k => $c) {
            $d[$k] = $c[1];
        }

        return $d + ['font' => 'inter', 'heading_font' => 'same', 'heading_weight' => '700', 'font_size' => '16', 'radius' => '12', 'button' => 'rounded', 'shadow' => 'soft', 'width' => '1280', 'apply_admin' => true];
    }

    /** The saved values over the defaults (invalid saved values fall back to the default). */
    public static function values(): array
    {
        $out = self::defaults();
        foreach ($out as $k => $default) {
            $v = setting("design.$k");
            if ($v !== null && $v !== '' && self::valid($k, $v)) {
                $out[$k] = is_bool($default) ? filter_var($v, FILTER_VALIDATE_BOOLEAN) : (string) $v;
            }
        }

        return $out;
    }

    public static function valid(string $key, mixed $v): bool
    {
        $v = (string) $v;

        return match (true) {
            isset(self::COLORS[$key]) => (bool) preg_match('/^#[0-9a-f]{6}$/i', $v),
            $key === 'font' => isset(self::FONTS[$v]),
            $key === 'heading_font' => $v === 'same' || isset(self::FONTS[$v]),
            $key === 'heading_weight' => in_array($v, ['500', '600', '700', '800'], true),
            $key === 'font_size' => ctype_digit($v) && $v >= 14 && $v <= 19,
            $key === 'radius' => ctype_digit($v) && $v <= 28,
            $key === 'button' => isset(self::BUTTONS[$v]),
            $key === 'shadow' => isset(self::SHADOWS[$v]),
            $key === 'width' => isset(self::WIDTHS[$v]),
            $key === 'apply_admin' => in_array($v, ['0', '1', 'true', 'false'], true),
            default => false,
        };
    }

    public static function isCustom(): bool
    {
        return self::values() != self::defaults();
    }

    /** Relative luminance 0..1 of a #rrggbb colour. */
    public static function luminance(string $hex): float
    {
        $c = array_map(fn ($h) => hexdec($h) / 255, str_split(ltrim($hex, '#'), 2));
        $c = array_map(fn ($v) => $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4, $c);

        return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
    }

    public static function contrast(string $a, string $b): float
    {
        [$x, $y] = [self::luminance($a), self::luminance($b)];

        return (max($x, $y) + 0.05) / (min($x, $y) + 0.05);
    }

    /** CSS variables for one request. $admin = the admin panel (honours "apply to admin"). */
    public static function css(bool $admin = false): string
    {
        $v = self::values();
        if ($admin && ! $v['apply_admin']) {
            return '';
        }
        $body = self::FONTS[$v['font']][1];
        $head = $v['heading_font'] === 'same' ? 'inherit' : self::FONTS[$v['heading_font']][1];
        $on = self::contrast($v['orange'], '#ffffff') >= 3.0 ? '#ffffff' : '#111111';

        $vars = [
            '--tq-navy' => $v['navy'], '--tq-blue' => $v['blue'], '--tq-orange' => $v['orange'], '--tq-deal' => $v['deal'], '--tq-green' => $v['green'],
            '--tq-bg' => $v['bg'], '--tq-card' => $v['card'], '--tq-ink' => $v['ink'], '--tq-muted' => $v['muted'], '--tq-line' => $v['line'],
            '--tq-navy-2' => 'color-mix(in srgb, var(--tq-navy) 82%, #000)',
            '--tq-blue-2' => 'color-mix(in srgb, var(--tq-blue) 82%, #000)',
            '--tq-orange-2' => 'color-mix(in srgb, var(--tq-orange) 84%, #000)',
            '--tq-orange-soft' => 'color-mix(in srgb, var(--tq-orange) 10%, #fff)',
            '--tq-sky' => 'color-mix(in srgb, var(--tq-blue) 10%, #fff)',
            '--tq-green-soft' => 'color-mix(in srgb, var(--tq-green) 12%, #fff)',
            '--tq-on-cta' => $on,
            '--tq-font' => $body, '--tq-font-head' => $head, '--tq-head-weight' => $v['heading_weight'],
            '--tq-r' => $v['radius'].'px', '--tq-r-sm' => max(2, (int) round($v['radius'] * .66)).'px',
            '--tq-btn-r' => self::BUTTONS[$v['button']][1], '--tq-shadow' => self::SHADOWS[$v['shadow']][1],
            // tokens the older stylesheets read
            '--bg' => 'var(--tq-bg)', '--surface' => 'var(--tq-card)', '--surface-solid' => 'var(--tq-card)', '--text' => 'var(--tq-ink)', '--text-2' => 'var(--tq-muted)',
            '--border' => 'var(--tq-line)', '--acc-1' => 'var(--tq-blue)', '--acc-2' => 'var(--tq-navy)', '--acc-ink' => 'var(--tq-blue-2)',
            '--font-ui' => 'var(--tq-font)', '--font-display' => 'var(--tq-font)', '--radius' => 'var(--tq-r)', '--sf-r' => 'var(--tq-r)', '--fx-r' => 'var(--tq-r)',
        ];
        $decl = '';
        foreach ($vars as $k => $val) {
            $decl .= "$k:$val;";
        }
        $scope = $admin ? 'html body.nx-tq' : 'html body.sf-tq';

        $rules = "$scope{{$decl}font-family:var(--tq-font)}"
            ."html{font-size:{$v['font_size']}px}"
            ."$scope h1,$scope h2,$scope h3,$scope h4,$scope h5,$scope h6{font-family:var(--tq-font-head);font-weight:var(--tq-head-weight)}"
            ."$scope .btn,$scope [class*=\"btn\"],$scope .tq-act,$scope .mk-search button{border-radius:var(--tq-btn-r)}"
            ."$scope .card,$scope .mk-card,$scope .mk-box,$scope .nx-stat{box-shadow:var(--tq-shadow)}"
            ."$scope .btn-primary,$scope .btn-success,$scope .mk-search button[type=submit]{color:var(--tq-on-cta) !important}";
        if (! $admin) {
            $rules .= ".sf-container{max-width:{$v['width']}px}";
        }

        return $rules;
    }
}
