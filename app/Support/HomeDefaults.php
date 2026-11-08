<?php

namespace App\Support;

use App\Models\HomeSection;

/** Starter home page for a new store, for the pharmacy. */
class HomeDefaults
{
    private const COPY = [
        'pharmacy' => [
            'eyebrow' => 'Trusted care', 'title' => 'Your pharmacy, now online', 'subtitle' => 'Genuine medicines and health products, delivered safely.', 'button' => 'Shop medicines',
            'ur' => ['eyebrow' => 'بھروسے کی دیکھ بھال', 'title' => 'آپ کی فارمیسی، اب آن لائن', 'subtitle' => 'اصل ادویات اور صحت کی مصنوعات، محفوظ ڈیلیوری کے ساتھ۔', 'button_label' => 'ادویات خریدیں'],
            'ur_Latn' => ['eyebrow' => 'Bharose ki dekh bhaal', 'title' => 'Aap ki pharmacy, ab online', 'subtitle' => 'Asal adwiya aur sehat ke products, mehfooz delivery ke saath.', 'button_label' => 'Adwiya khareedein'],
        ],
    ];

    private const FEATURES = [
        ['icon' => 'truck', 'title' => 'Fast delivery', 'text' => 'Quick and careful delivery to your door.', 'ur' => ['title' => 'تیز ڈیلیوری', 'text' => 'آپ کے دروازے تک تیز اور احتیاط سے ڈیلیوری۔'], 'ur_Latn' => ['title' => 'Tez delivery', 'text' => 'Aap ke darwaze tak tez aur ehtiyat se delivery.']],
        ['icon' => 'shield-check', 'title' => 'Secure checkout', 'text' => 'Your details stay private and safe.', 'ur' => ['title' => 'محفوظ چیک آؤٹ', 'text' => 'آپ کی معلومات نجی اور محفوظ رہتی ہیں۔'], 'ur_Latn' => ['title' => 'Mehfooz checkout', 'text' => 'Aap ki maloomat nijji aur mehfooz rehti hain.']],
        ['icon' => 'headset', 'title' => 'Friendly support', 'text' => 'We are one message away.', 'ur' => ['title' => 'دوستانہ سپورٹ', 'text' => 'ہم ایک پیغام کی دوری پر ہیں۔'], 'ur_Latn' => ['title' => 'Dostana support', 'text' => 'Hum ek paighaam ki doori par hain.']],
    ];

    public static function seed(string $type): void
    {
        if (HomeSection::query()->exists()) {
            return;
        }
        $c = self::COPY['pharmacy'];

        $make = function (string $sectionType, array $settings, array $tr = [], string $title = '') {
            $section = new HomeSection([
                'type' => $sectionType, 'title' => $title, 'settings' => $settings,
                'sort_order' => (int) HomeSection::max('sort_order') + 1, 'is_active' => true,
            ]);
            $section->translations = $tr ?: null;
            $section->save();

            return $section;
        };

        $make('hero', ['eyebrow' => $c['eyebrow'], 'title' => $c['title'], 'subtitle' => $c['subtitle'], 'button_label' => $c['button'], 'button_url' => '/shop'],
            ['ur' => $c['ur'], 'ur_Latn' => $c['ur_Latn']], $c['title']);
        $make('categories', ['title' => 'Shop by category', 'limit' => 8],
            ['ur' => ['title' => 'کیٹیگری کے مطابق خریداری'], 'ur_Latn' => ['title' => 'Category ke mutabiq khareedari']], 'Shop by category');
        $make('products', ['title' => 'New arrivals', 'source' => 'new', 'limit' => 8],
            ['ur' => ['title' => 'نئی آمد'], 'ur_Latn' => ['title' => 'Nayi aamad']], 'New arrivals');
        $make('features', ['items' => array_map(fn ($f) => array_intersect_key($f, array_flip(['icon', 'title', 'text'])), self::FEATURES)],
            ['ur' => ['items' => array_map(fn ($f) => $f['ur'], self::FEATURES)], 'ur_Latn' => ['items' => array_map(fn ($f) => $f['ur_Latn'], self::FEATURES)]], 'Why shop with us');
    }
}
