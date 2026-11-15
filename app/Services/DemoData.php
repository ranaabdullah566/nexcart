<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Support\BusinessProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Spatie\MediaLibrary\HasMedia;

/**
 * Sample medicine catalogue, so a new pharmacy (or a sales demo) looks alive in one click.
 * Everything it creates is remembered by id, so "remove" deletes exactly that and never real products.
 */
class DemoData
{
    public function __construct(private StockService $stock) {}

    public function loaded(): bool
    {
        return (bool) setting('demo.ids');
    }

    /** @return int number of products created */
    public function load(string $type = 'pharmacy'): int
    {
        if ($this->loaded()) {
            throw new RuntimeException(__('Sample data is already loaded. Remove it first to load it again.'));
        }
        $this->reviewIds = [];
        $set = $this->dataset();
        $ids = ['products' => [], 'categories' => [], 'reviews' => [], 'articles' => []];

        DB::transaction(function () use ($set, &$ids) {
            $cats = [];
            foreach ($set['categories'] as $key => $name) {
                $cats[$key] = Category::create(['name' => $name, 'is_active' => true, 'sort_order' => count($cats)]);
                $ids['categories'][] = $cats[$key]->id;
            }
            foreach ($set['products'] as $i => $d) {
                $p = new Product;
                $p->forceFill([
                    'category_id' => $cats[$d['cat']]->id,
                    'name' => $d['name'], 'sku' => 'DEMO-PHA-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                    'type' => isset($d['variants']) ? 'variable' : 'simple', 'status' => 'active', 'short_description' => $d['short'] ?? null, 'description' => $d['desc'] ?? ($d['short'] ?? null),
                    'price' => isset($d['variants']) ? min(array_column($d['variants'], 1)) : $d['price'], 'compare_price' => $d['compare'] ?? null,
                    'track_stock' => true, 'stock_qty' => 0, 'low_stock_threshold' => $d['low'] ?? 5, 'requires_prescription' => $d['rx'] ?? false,
                    'is_featured' => $d['featured'] ?? false, 'specs' => $d['specs'] ?? null,
                ])->save();
                $ids['products'][] = $p->id;

                foreach ($d['variants'] ?? [] as $v => [$label, $price, $qty]) {
                    $variant = $p->variants()->create(['name' => $label, 'price' => $price, 'stock_qty' => 0, 'sort_order' => $v, 'sku' => $p->sku.'-'.($v + 1)]);
                    $this->receive($p, $qty, $variant, $d);
                }
                if (! isset($d['variants'])) {
                    $this->receive($p, $d['stock'] ?? 25, null, $d);
                }
                $this->sampleReviews($p, $i);
                $this->attachImage($p, $d['hue'] ?? ($i * 47) % 360, $d['name']);
            }
        });

        $ids['reviews'] = $this->reviewIds;
        $ids['articles'] = $this->sampleArticles();
        setting()->set('demo.ids', $ids);

        return count($ids['products']);
    }

    /** @return int number of products removed */
    public function remove(): int
    {
        $ids = (array) setting('demo.ids');
        $n = 0;

        DB::transaction(function () use ($ids, &$n) {
            Review::withTrashed()->whereIn('id', $ids['reviews'] ?? [])->forceDelete();
            Article::withTrashed()->whereIn('id', $ids['articles'] ?? [])->get()->each->forceDelete();
            foreach (Product::withTrashed()->whereIn('id', $ids['products'] ?? [])->get() as $p) {
                $p->forceDelete();   // also deletes its images
                $n++;
            }
            // shared things are only removed when nothing real hangs on them
            Category::whereIn('id', $ids['categories'] ?? [])->get()->each(fn ($c) => $c->products()->exists() ? null : $c->delete());
        });

        setting()->set('demo.ids', null);

        return $n;
    }

    /** Starter health-blog articles (general, non-prescriptive advice). Removed with the sample data. @return array<int, int> */
    private function sampleArticles(): array
    {
        $rows = [
            ['Staying hydrated in the heat', 'Wellness', 'Our pharmacy team', 'Simple habits that keep you safe when temperatures climb.', 'Hot weather makes your body lose water and salts faster than you may notice.

# Signs you need more fluids

- Dark yellow urine
- Headache or dizziness
- Dry mouth and unusual tiredness

# What helps

1. Sip water through the day instead of waiting until you are thirsty.
2. Use oral rehydration salts (ORS) after vomiting, diarrhoea or heavy sweating.
3. Limit very sugary drinks and keep out of direct sun at midday.

Children, older adults and people with long-term illnesses are more at risk. If someone is confused, faints or cannot keep fluids down, seek medical care urgently.', 205],
            ['Antibiotics: why finishing the course matters', 'Medicines', 'Our pharmacy team', 'Antibiotics only work when they are used the right way.', 'Antibiotics fight bacterial infections. They do not help with colds or flu, which are caused by viruses.

# Use them safely

- Take antibiotics only when a doctor prescribes them.
- Take the full course at the right times, even if you feel better early.
- Never share leftover antibiotics or save them for later.

# Why it matters

Stopping early or taking them when they are not needed lets bacteria survive and become resistant, which makes future infections harder to treat. If you have side effects such as a rash or severe stomach upset, contact your doctor or pharmacist.', 150],
            ['Storing your medicines properly', 'Medicines', 'Our pharmacy team', 'Heat, light and damp can spoil medicines long before the expiry date.', 'Most tablets and syrups should be kept in a cool, dry place away from direct sunlight, not in the bathroom or the kitchen next to the stove.

# Quick checklist

- Keep medicines in their original packs so the batch number and expiry stay with them.
- Store them out of children\'s reach.
- Follow the label: some medicines, like certain insulins and syrups, need a fridge.
- Check expiry dates every few months and return expired medicines to a pharmacy instead of throwing them in the open.

If a medicine looks discoloured, smells strange or the tablets are crumbling, do not use it and ask your pharmacist.', 95],
            ['Eating well with diabetes: the basics', 'Diabetes care', 'Our pharmacy team', 'Small, steady choices make blood sugar easier to manage.', 'Managing diabetes is a daily routine, and food is a big part of it. This is general guidance; your doctor or dietitian can tailor it to you.

# Helpful habits

- Eat at regular times and avoid long gaps between meals.
- Fill half your plate with vegetables, and choose whole grains over white rice and refined flour.
- Keep sweets, sugary drinks and fried snacks for rare occasions.
- Walk for about 30 minutes most days if your doctor agrees.

# Do not skip monitoring

Check your blood sugar as advised and keep a note of the readings to show your doctor. Never change or stop your medicines on your own.', 280],
            ['Caring for a child with a fever', 'Baby & mother care', 'Our pharmacy team', 'What parents can do at home, and when to call a doctor.', 'Fever is common in children and is often the body fighting an infection.

# At home

- Offer plenty of fluids and let your child rest.
- Dress them in light clothing; do not wrap them up tightly.
- Use only the medicine and dose your doctor or pharmacist recommends for your child\'s age and weight.

# See a doctor quickly if

1. The baby is under three months old and has any fever.
2. The fever lasts more than three days or keeps returning.
3. Your child is very drowsy, has trouble breathing, a stiff neck, a rash that does not fade, or seems to be getting worse.

Trust your instincts: if you are worried, get medical advice.', 330],
            ['Protecting your skin from the sun', 'Skin care', 'Our pharmacy team', 'Everyday sun care that is easy to keep up.', 'Strong sunshine can cause sunburn, dark patches and long-term skin damage.

# Everyday protection

- Apply sunscreen of SPF 30 or higher to exposed skin, and reapply every two hours when outdoors.
- Wear a hat and sunglasses, and seek shade between late morning and mid-afternoon.
- Moisturise after sun exposure; aloe-based gels can soothe mild redness.

Skin that blisters, a mole that changes shape or colour, or sunburn that does not settle should be checked by a doctor.', 25],
        ];
        $ids = [];
        foreach ($rows as $i => [$title, $tag, $author, $excerpt, $body, $hue]) {
            $a = new Article;
            $a->fill(['title' => $title, 'tag' => $tag, 'author' => $author, 'excerpt' => $excerpt, 'body' => $body, 'is_published' => true, 'published_at' => now()->subDays($i * 3 + 1)]);
            $a->save();
            $this->attachImage($a, $hue, $title, 'cover', 1200, 675);
            $ids[] = $a->id;
        }

        return $ids;
    }

    /** A few believable reviews on some products, so the home page wall is alive in a demo. Removed with the sample data. */
    private function sampleReviews(Product $p, int $i): void
    {
        $bank = [
            [5, 'Genuine and fast', 'Delivered the same day and the pharmacist even called to confirm the dose. Packaging was sealed.', 'Ayesha K.'],
            [5, 'Very reliable', 'I order my family’s medicines here every month. Prices are fair and everything is genuine.', 'Usman R.'],
            [4, 'Good service', 'Easy to order. Delivery took a little longer than promised but the rider was polite.', 'Hira S.'],
            [5, 'Saved me a trip', 'Uploaded my prescription at night and had the medicines by noon. Highly recommended.', 'Bilal A.'],
            [4, 'Fair prices', 'Found the same medicine at a lower price than the corner store. Will order again.', 'Maryam F.'],
            [5, 'Excellent', 'Everything arrived properly packed and the expiry was well over a year away.', 'Hassan M.'],
            [5, 'Helpful team', 'They suggested a cheaper alternative with the same salt. Great honesty.', 'Zainab T.'],
            [4, 'Smooth checkout', 'Cash on delivery made it easy for my parents. Simple site, no confusion.', 'Omar J.'],
        ];
        if ($i % 3 !== 0 || ! $bank) {
            return;
        }
        [$rating, $title, $body, $name] = $bank[intdiv($i, 3) % count($bank)];
        $r = Review::create(['product_id' => $p->id, 'name' => $name, 'rating' => $rating, 'title' => $title, 'body' => $body, 'status' => 'approved']);
        $this->reviewIds[] = $r->id;
    }

    private array $reviewIds = [];

    private function receive(Product $p, int $qty, $variant, array $d): void
    {
        if ($qty < 1) {
            return;
        }
        $batch = BusinessProfile::hasModule('batch_expiry')
            ? ['batch_no' => 'B'.date('ym').'-'.$p->id, 'expires_at' => now()->addDays($d['expires_in'] ?? 400)->toDateString()]
            : [];
        $this->stock->adjust($p, $qty, 'received', $variant, $batch, __('Sample stock'));
    }

    /** A generated gradient picture — no downloads, no licensing questions. */
    private function attachImage(HasMedia $p, int $hue, string $seed, string $collection = 'images', int $w = 640, int $h = 640): void
    {
        $file = app(DemoImages::class)->productFile($seed, '', $collection.'-'.$p->getKey());
        if ($file) {
            $p->addMedia($file)->toMediaCollection($collection);   // moves the temp file
        }
    }

    private function dataset(): array
    {
        $m = fn (string $comp, string $form, string $str, string $maker) => ['composition' => $comp, 'dosage_form' => $form, 'strength' => $str, 'manufacturer' => $maker];

        return [
            'categories' => ['pain' => 'Pain & fever', 'cold' => 'Cold, flu & allergy', 'stomach' => 'Stomach & digestion', 'vit' => 'Vitamins & supplements', 'diabetes' => 'Diabetes care', 'baby' => 'Baby & mother care', 'skin' => 'Skin care', 'devices' => 'Health devices', 'rx' => 'Prescription medicines'],
            'products' => [
                ['name' => 'Panadol 500mg (20 tablets)', 'cat' => 'pain', 'price' => 45, 'featured' => true, 'short' => 'Paracetamol for pain and fever.', 'specs' => $m('Paracetamol 500mg', 'Tablet', '500mg', 'GSK'), 'stock' => 120, 'hue' => 205],
                ['name' => 'Paracip 500mg (20 tablets)', 'cat' => 'pain', 'price' => 38, 'short' => 'Paracetamol for pain and fever — a lower-priced alternative.', 'specs' => $m('Paracetamol 500mg', 'Tablet', '500mg', 'Getz Pharma'), 'stock' => 90, 'hue' => 150],
                ['name' => 'Fevadol 500mg (20 tablets)', 'cat' => 'pain', 'price' => 52, 'short' => 'Paracetamol for pain and fever.', 'specs' => $m('Paracetamol 500mg', 'Tablet', '500mg', 'Searle'), 'stock' => 70, 'hue' => 275],
                ['name' => 'Brufen 400mg', 'cat' => 'pain', 'short' => 'Ibuprofen anti-inflammatory.', 'variants' => [['10 tablets', 70, 60], ['30 tablets', 190, 60]], 'specs' => $m('Ibuprofen 400mg', 'Tablet', '400mg', 'Abbott'), 'expires_in' => 40, 'hue' => 25],
                ['name' => 'Calpol Suspension 120ml', 'cat' => 'pain', 'price' => 160, 'short' => 'Paracetamol syrup for children.', 'specs' => $m('Paracetamol 120mg/5ml', 'Syrup', '120mg/5ml', 'GSK'), 'stock' => 50, 'hue' => 190],
                ['name' => 'Disprin (10 tablets)', 'cat' => 'pain', 'price' => 30, 'short' => 'Soluble aspirin.', 'specs' => $m('Aspirin 300mg', 'Tablet', '300mg', 'Reckitt'), 'stock' => 90, 'hue' => 15],
                ['name' => 'Arinac Forte (12 tablets)', 'cat' => 'cold', 'price' => 140, 'short' => 'Cold, flu and congestion relief.', 'specs' => $m('Ibuprofen + pseudoephedrine', 'Tablet', '200mg/30mg', 'Abbott'), 'stock' => 70, 'hue' => 160],
                ['name' => 'Avil 25mg (20 tablets)', 'cat' => 'cold', 'price' => 85, 'short' => 'Antihistamine for allergy relief.', 'specs' => $m('Pheniramine 25mg', 'Tablet', '25mg', 'Sanofi'), 'stock' => 65, 'hue' => 120],
                ['name' => 'Cough Syrup 100ml', 'cat' => 'cold', 'price' => 180, 'short' => 'Soothing syrup for dry cough.', 'specs' => $m('Dextromethorphan', 'Syrup', '15mg/5ml', 'Searle'), 'stock' => 40, 'hue' => 30],
                ['name' => 'Gaviscon Liquid 150ml', 'cat' => 'stomach', 'price' => 620, 'short' => 'Fast relief from heartburn and acidity.', 'specs' => $m('Sodium alginate', 'Syrup', '500mg/10ml', 'Reckitt'), 'stock' => 30, 'hue' => 95],
                ['name' => 'ORS Sachets (10 pack)', 'cat' => 'stomach', 'price' => 120, 'short' => 'Oral rehydration salts.', 'specs' => $m('Glucose + electrolytes', 'Other', '—', 'Searle'), 'stock' => 100, 'hue' => 175],
                ['name' => 'Vitamin C 1000mg (20 effervescent)', 'cat' => 'vit', 'price' => 280, 'compare' => 340, 'featured' => true, 'short' => 'Daily immune support.', 'specs' => $m('Ascorbic acid 1000mg', 'Tablet', '1000mg', 'Getz Pharma'), 'stock' => 60, 'hue' => 40],
                ['name' => 'Multivitamin Daily', 'cat' => 'vit', 'short' => 'Multivitamin and mineral.', 'variants' => [['30 capsules', 950, 35], ['60 capsules', 1700, 25]], 'specs' => $m('Vitamins A–E, zinc, iron', 'Capsule', 'Daily', 'Searle'), 'low' => 10, 'hue' => 330],
                ['name' => 'Calcium + D3 (30 tablets)', 'cat' => 'vit', 'price' => 690, 'short' => 'Bone and joint support.', 'specs' => $m('Calcium 500mg + D3', 'Tablet', '500mg', 'Getz Pharma'), 'stock' => 40, 'hue' => 55],
                ['name' => 'Glucometer Kit', 'cat' => 'diabetes', 'price' => 4200, 'featured' => true, 'short' => 'Meter, lancets and 10 test strips.', 'specs' => $m('—', 'Other', '—', 'Accu-Chek'), 'stock' => 20, 'hue' => 150],
                ['name' => 'Glucose Test Strips (50)', 'cat' => 'diabetes', 'price' => 2300, 'short' => 'Compatible with the Glucometer Kit.', 'specs' => $m('—', 'Other', '—', 'Accu-Chek'), 'stock' => 35, 'expires_in' => 200, 'hue' => 140],
                ['name' => 'Baby Gripe Water 120ml', 'cat' => 'baby', 'price' => 160, 'short' => 'Gentle relief for infant colic.', 'specs' => $m('Dill oil, sodium bicarbonate', 'Syrup', '—', 'Woodward'), 'stock' => 45, 'hue' => 170],
                ['name' => 'Baby Rash Cream 50g', 'cat' => 'baby', 'price' => 340, 'short' => 'Protects against diaper rash.', 'specs' => $m('Zinc oxide', 'Cream / Ointment', '—', 'Sudocrem'), 'stock' => 38, 'hue' => 320],
                ['name' => 'Betnovate-N Cream 15g', 'cat' => 'skin', 'price' => 110, 'short' => 'Antibiotic and steroid skin cream.', 'specs' => $m('Betamethasone + neomycin', 'Cream / Ointment', '0.1%', 'GSK'), 'stock' => 55, 'hue' => 345],
                ['name' => 'Sunblock SPF 50 (60ml)', 'cat' => 'skin', 'price' => 1450, 'short' => 'Broad-spectrum sun protection.', 'specs' => $m('—', 'Cream / Ointment', 'SPF 50', 'Local'), 'stock' => 25, 'hue' => 45],
                ['name' => 'Digital BP Monitor', 'cat' => 'devices', 'price' => 8900, 'compare' => 9800, 'featured' => true, 'short' => 'Upper-arm automatic blood pressure monitor.', 'specs' => $m('—', 'Other', '—', 'Omron'), 'stock' => 15, 'hue' => 200],
                ['name' => 'Infrared Thermometer', 'cat' => 'devices', 'price' => 3500, 'short' => 'Non-contact, 1-second reading.', 'specs' => $m('—', 'Other', '—', 'Local'), 'stock' => 30, 'hue' => 260],
                ['name' => 'Surgical Masks (box of 50)', 'cat' => 'devices', 'price' => 450, 'short' => '3-ply disposable masks.', 'specs' => $m('—', 'Other', '—', 'Local'), 'stock' => 100, 'hue' => 190],
                ['name' => 'Amoxicillin 500mg (12 capsules)', 'cat' => 'rx', 'price' => 320, 'rx' => true, 'short' => 'Antibiotic — prescription required.', 'specs' => $m('Amoxicillin 500mg', 'Capsule', '500mg', 'Searle'), 'stock' => 50, 'expires_in' => 300, 'hue' => 350],
                ['name' => 'Augmentin 625mg (14 tablets)', 'cat' => 'rx', 'price' => 780, 'rx' => true, 'short' => 'Antibiotic — prescription required.', 'specs' => $m('Amoxicillin + clavulanic acid', 'Tablet', '625mg', 'GSK'), 'stock' => 3, 'low' => 5, 'hue' => 300],
                ['name' => 'Metformin 500mg (30 tablets)', 'cat' => 'rx', 'price' => 95, 'rx' => true, 'short' => 'For type 2 diabetes — prescription required.', 'specs' => $m('Metformin 500mg', 'Tablet', '500mg', 'Getz Pharma'), 'stock' => 80, 'hue' => 20],
            ],
        ];
    }
}
