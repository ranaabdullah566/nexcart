<?php

namespace App\Support;

use App\Models\HomeSection;

/** Starter home page for a new store, for the pharmacy. */
class HomeDefaults
{
    private const COPY = [
        'pharmacy' => [
            'eyebrow' => 'Trusted care', 'title' => 'Your pharmacy, now online', 'subtitle' => 'Genuine medicines and health products, delivered safely.', 'button' => 'Shop medicines', 'button2' => 'Upload prescription',
            'ur' => ['eyebrow' => 'بھروسے کی دیکھ بھال', 'title' => 'آپ کی فارمیسی، اب آن لائن', 'subtitle' => 'اصل ادویات اور صحت کی مصنوعات، محفوظ ڈیلیوری کے ساتھ۔', 'button_label' => 'ادویات خریدیں', 'button2_label' => 'نسخہ بھیجیں'],
            'ur_Latn' => ['eyebrow' => 'Bharose ki dekh bhaal', 'title' => 'Aap ki pharmacy, ab online', 'subtitle' => 'Asal adwiya aur sehat ke products, mehfooz delivery ke saath.', 'button_label' => 'Adwiya khareedein', 'button2_label' => 'Nuskha bhejein'],
        ],
    ];

    private const FEATURES = [
        ['icon' => 'patch-check', 'title' => '100% genuine', 'text' => 'Medicines from licensed sources only.', 'ur' => ['title' => '100٪ اصلی', 'text' => 'صرف لائسنس یافتہ ذرائع سے ادویات۔'], 'ur_Latn' => ['title' => '100% asli', 'text' => 'Sirf licensed zarai se adwiya.']],
        ['icon' => 'prescription2', 'title' => 'Pharmacist verified', 'text' => 'Every prescription is checked by a pharmacist.', 'ur' => ['title' => 'فارماسسٹ سے تصدیق شدہ', 'text' => 'ہر نسخہ فارماسسٹ چیک کرتا ہے۔'], 'ur_Latn' => ['title' => 'Pharmacist se tasdeeq shuda', 'text' => 'Har nuskha pharmacist check karta hai.']],
        ['icon' => 'truck', 'title' => 'Fast delivery', 'text' => 'Quick and careful delivery to your door.', 'ur' => ['title' => 'تیز ڈیلیوری', 'text' => 'آپ کے دروازے تک تیز اور احتیاط سے ڈیلیوری۔'], 'ur_Latn' => ['title' => 'Tez delivery', 'text' => 'Aap ke darwaze tak tez aur ehtiyat se delivery.']],
        ['icon' => 'cash-coin', 'title' => 'Pay on delivery', 'text' => 'Pay in cash when your medicines arrive.', 'ur' => ['title' => 'ڈیلیوری پر ادائیگی', 'text' => 'دوائیں ملنے پر نقد ادائیگی کریں۔'], 'ur_Latn' => ['title' => 'Delivery par adaigi', 'text' => 'Adwiya milne par naqd adaigi karein.']],
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

        $make('hero', ['eyebrow' => $c['eyebrow'], 'title' => $c['title'], 'subtitle' => $c['subtitle'], 'button_label' => $c['button'], 'button_url' => '/shop', 'button2_label' => $c['button2'], 'button2_url' => '/upload-prescription'],
            ['ur' => $c['ur'], 'ur_Latn' => $c['ur_Latn']], $c['title']);
        $make('features', ['items' => array_map(fn ($f) => array_intersect_key($f, array_flip(['icon', 'title', 'text'])), self::FEATURES)],
            ['ur' => ['items' => array_map(fn ($f) => $f['ur'], self::FEATURES)], 'ur_Latn' => ['items' => array_map(fn ($f) => $f['ur_Latn'], self::FEATURES)]], 'Why shop with us');
        $make('categories', ['title' => 'Shop by need', 'limit' => 8],
            ['ur' => ['title' => 'ضرورت کے مطابق خریداری'], 'ur_Latn' => ['title' => 'Zaroorat ke mutabiq khareedari']], 'Shop by need');
        $make('products', ['title' => 'Most ordered', 'source' => 'featured', 'limit' => 8],
            ['ur' => ['title' => 'سب سے زیادہ منگوائی جانے والی'], 'ur_Latn' => ['title' => 'Sab se ziyada mangwayi jane wali']], 'Most ordered');
        $make('cta', ['eyebrow' => 'No cart needed', 'title' => 'Have a prescription? Just send a photo.', 'subtitle' => 'Our pharmacist will call you, confirm the medicines and deliver to your door.', 'button_label' => 'Upload prescription', 'button_url' => '/upload-prescription'],
            ['ur' => ['eyebrow' => 'کارٹ کی ضرورت نہیں', 'title' => 'نسخہ ہے؟ بس تصویر بھیج دیں۔', 'subtitle' => 'ہمارا فارماسسٹ آپ کو کال کرے گا، ادویات کی تصدیق کرے گا اور دروازے تک پہنچائے گا۔', 'button_label' => 'نسخہ بھیجیں'],
                'ur_Latn' => ['eyebrow' => 'Cart ki zaroorat nahi', 'title' => 'Nuskha hai? Bas tasveer bhej dein.', 'subtitle' => 'Hamara pharmacist aap ko call karega, adwiya ki tasdeeq karega aur darwaze tak pohanchayega.', 'button_label' => 'Nuskha bhejein']], 'Prescription banner');
        $make('products', ['title' => 'New arrivals', 'source' => 'new', 'limit' => 8],
            ['ur' => ['title' => 'نئی آمد'], 'ur_Latn' => ['title' => 'Nayi aamad']], 'New arrivals');
        $steps = [
            ['icon' => 'search', 'title' => 'Search or upload', 'text' => 'Find your medicine, or send a photo of the prescription.', 'ur' => ['title' => 'تلاش کریں یا بھیجیں', 'text' => 'دوا تلاش کریں یا نسخے کی تصویر بھیجیں۔'], 'ur_Latn' => ['title' => 'Talash karein ya bhejein', 'text' => 'Dawa talash karein ya nuskhe ki tasveer bhejein.']],
            ['icon' => 'person-check', 'title' => 'Pharmacist confirms', 'text' => 'We verify the prescription and confirm availability.', 'ur' => ['title' => 'فارماسسٹ تصدیق کرتا ہے', 'text' => 'ہم نسخے اور دستیابی کی تصدیق کرتے ہیں۔'], 'ur_Latn' => ['title' => 'Pharmacist tasdeeq karta hai', 'text' => 'Hum nuskhe aur dastiyabi ki tasdeeq karte hain.']],
            ['icon' => 'house-heart', 'title' => 'Delivered home', 'text' => 'Safe delivery to your door. Pay when it arrives.', 'ur' => ['title' => 'گھر تک ڈیلیوری', 'text' => 'دروازے تک محفوظ ڈیلیوری۔ ملنے پر ادائیگی کریں۔'], 'ur_Latn' => ['title' => 'Ghar tak delivery', 'text' => 'Darwaze tak mehfooz delivery. Milne par adaigi karein.']],
        ];
        $make('steps', ['title' => 'How ordering works', 'subtitle' => 'Three simple steps from prescription to doorstep.', 'items' => array_map(fn ($f) => array_intersect_key($f, array_flip(['icon', 'title', 'text'])), $steps)],
            ['ur' => ['title' => 'آرڈر کیسے کریں', 'items' => array_map(fn ($f) => $f['ur'], $steps)], 'ur_Latn' => ['title' => 'Order kaise karein', 'items' => array_map(fn ($f) => $f['ur_Latn'], $steps)]], 'How ordering works');
    }
}
