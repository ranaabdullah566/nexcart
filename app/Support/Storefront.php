<?php

namespace App\Support;

use App\Models\Category;
use Illuminate\Support\Collection;

/** Data the storefront header / footer need on every page. */
class Storefront
{
    /** Top-level categories with their children and live product counts, for the mega menu. */
    public static function tree(): Collection
    {
        return Category::where('is_active', true)->whereNull('parent_id')->orderBy('sort_order')->orderBy('name')
            ->with(['children' => fn ($q) => $q->where('is_active', true)->withCount(['products' => fn ($p) => $p->active()])])
            ->withCount(['products' => fn ($p) => $p->active()])->limit(12)->get();
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
