<?php

namespace App\Support;

use App\Models\HomeSection;

/** Starter home page for a new store, tuned to the chosen business type. */
class HomeDefaults
{
    private const COPY = [
        'general' => [
            'eyebrow' => 'New season', 'title' => 'Everything you need, delivered', 'subtitle' => 'Discover our latest products at honest prices.', 'button' => 'Shop now',
            'ur' => ['eyebrow' => 'نیا سیزن', 'title' => 'جو کچھ آپ کو چاہیے، آپ کے دروازے پر', 'subtitle' => 'ہماری تازہ ترین مصنوعات مناسب قیمت پر دیکھیں۔', 'button_label' => 'ابھی خریدیں'],
            'ur_Latn' => ['eyebrow' => 'Naya season', 'title' => 'Jo kuch aap ko chahiye, aap ke darwaze par', 'subtitle' => 'Hamare taza tareen products munasib qeemat par dekhein.', 'button_label' => 'Abhi khareedein'],
        ],
        'fastfood' => [
            'eyebrow' => 'Hot & fresh', 'title' => 'Crave it. Click it. Eat it.', 'subtitle' => 'Fresh from the kitchen to your door in minutes.', 'button' => 'Order now',
            'ur' => ['eyebrow' => 'گرما گرم اور تازہ', 'title' => 'من چاہا، کلک کریں، کھائیں۔', 'subtitle' => 'کچن سے سیدھا آپ کے دروازے تک، چند منٹ میں۔', 'button_label' => 'ابھی آرڈر کریں'],
            'ur_Latn' => ['eyebrow' => 'Garma garam aur taza', 'title' => 'Man chaha, click karein, khayein.', 'subtitle' => 'Kitchen se seedha aap ke darwaze tak, chand minute mein.', 'button_label' => 'Abhi order karein'],
        ],
        'pharmacy' => [
            'eyebrow' => 'Trusted care', 'title' => 'Your pharmacy, now online', 'subtitle' => 'Genuine medicines and health products, delivered safely.', 'button' => 'Shop medicines',
            'ur' => ['eyebrow' => 'بھروسے کی دیکھ بھال', 'title' => 'آپ کی فارمیسی، اب آن لائن', 'subtitle' => 'اصل ادویات اور صحت کی مصنوعات، محفوظ ڈیلیوری کے ساتھ۔', 'button_label' => 'ادویات خریدیں'],
            'ur_Latn' => ['eyebrow' => 'Bharose ki dekh bhaal', 'title' => 'Aap ki pharmacy, ab online', 'subtitle' => 'Asal adwiya aur sehat ke products, mehfooz delivery ke saath.', 'button_label' => 'Adwiya khareedein'],
        ],
        'medical_store' => [
            'eyebrow' => 'Medical supplies', 'title' => 'Quality medical equipment, ready when you need it', 'subtitle' => 'Reliable devices and supplies for home and clinic.', 'button' => 'Browse items',
            'ur' => ['eyebrow' => 'طبی سامان', 'title' => 'معیاری طبی آلات، جب آپ کو ضرورت ہو', 'subtitle' => 'گھر اور کلینک کے لیے قابلِ اعتماد آلات اور سامان۔', 'button_label' => 'آئٹمز دیکھیں'],
            'ur_Latn' => ['eyebrow' => 'Tibbi saman', 'title' => 'Miyari tibbi aalaat, jab aap ko zaroorat ho', 'subtitle' => 'Ghar aur clinic ke liye qabil-e-aitmaad aalaat aur saman.', 'button_label' => 'Items dekhein'],
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
        $c = self::COPY[$type] ?? self::COPY['general'];

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
