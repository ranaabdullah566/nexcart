<?php

namespace App\Support;

use App\Models\Category;
use Illuminate\Support\Collection;

/** Data the storefront header / footer need on every page. */
class Storefront
{
    /** Every active category that has at least one live product (one flat level), with its count, for the header menu, the drawer, the footer and the shop sidebar. */
    public static function tree(): Collection
    {
        return once(fn () => Category::where('is_active', true)->orderBy('sort_order')->orderBy('name')
            ->withCount(['products' => fn ($p) => $p->active()])->whereHas('products', fn ($p) => $p->active())->get());
    }

    /** The owner's Google Maps link, only if it really is an https Google Maps address (it is an href, so nothing else may get through). */
    public static function mapUrl(): ?string
    {
        $u = trim((string) sf('footer.map_url'));
        $host = strtolower((string) parse_url($u, PHP_URL_HOST));
        $ok = str_starts_with($u, 'https://') && in_array($host, ['maps.app.goo.gl', 'goo.gl', 'maps.google.com', 'www.google.com', 'google.com', 'maps.google.com.pk', 'www.google.com.pk'], true);

        return $ok && ($host !== 'goo.gl' || str_starts_with((string) parse_url($u, PHP_URL_PATH), '/maps')) && ($host !== 'www.google.com' && $host !== 'google.com' || str_starts_with((string) parse_url($u, PHP_URL_PATH), '/maps')) ? $u : null;
    }

    /** What the embedded map shows: the owner's place text, else the store address. */
    public static function mapQuery(): ?string
    {
        return filled($q = trim((string) sf('footer.map_query'))) ? $q : (filled($a = setting('store.address')) ? $a : null);
    }

    /** @return array<string, array{url:string, label:string, icon:string}> social links the owner filled in */
    public static function socials(): array
    {
        $icons = ['facebook' => 'facebook', 'instagram' => 'instagram', 'tiktok' => 'tiktok', 'x' => 'twitter-x', 'youtube' => 'youtube', 'linkedin' => 'linkedin'];
        $out = [];
        foreach ($icons as $k => $icon) {
            if (filled($url = setting("store.{$k}"))) {
                $out[$k] = ['url' => $url, 'label' => ucfirst($k), 'icon' => $icon];
            }
        }
        if (filled($wa = setting('store.whatsapp'))) {
            $out['whatsapp'] = ['url' => 'https://wa.me/'.preg_replace('/\D/', '', $wa), 'label' => 'WhatsApp', 'icon' => 'whatsapp'];
        }

        return $out;
    }

    /** A fitting Bootstrap icon for a medicine category, by its name (works in any language that uses the English words). */
    public static function categoryIcon(?string $name): string
    {
        $n = mb_strtolower((string) $name);
        foreach ([
            'bandaid' => ['pain', 'fever', 'relief', 'analges'],
            'wind' => ['cold', 'flu', 'cough', 'allerg', 'respir'],
            'moisture' => ['stomach', 'digest', 'gastr', 'acid'],
            'capsule' => ['vitamin', 'supplement', 'mineral', 'nutri'],
            'droplet-half' => ['diabet', 'sugar', 'insulin', 'glucose'],
            'emoji-smile' => ['baby', 'mother', 'kid', 'child', 'infant'],
            'sun' => ['skin', 'derma', 'beauty', 'hair', 'sun'],
            'heart-pulse' => ['heart', 'blood', 'pressure', 'cardio', 'device', 'monitor'],
            'prescription2' => ['prescription', 'rx', 'antibiotic'],
            'eyedropper' => ['eye', 'ear', 'drop'],
            'shield-plus' => ['first aid', 'protect', 'mask', 'sanit', 'immun'],
            'gender-female' => ['women', 'female', 'feminine'],
            'lungs' => ['asthma', 'lung', 'breath'],
        ] as $icon => $words) {
            foreach ($words as $w) {
                if (str_contains($n, $w)) {
                    return $icon;
                }
            }
        }

        return 'capsule-pill';
    }
}
