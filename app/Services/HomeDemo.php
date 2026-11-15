<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Category;
use App\Models\HomeSection;
use App\Models\Product;
use App\Models\Review;

/**
 * One sample section of every kind, switched on, so each home page block can be seen and tested.
 * The ids are remembered (setting demo.home_ids) so "remove" deletes exactly these and never real sections.
 */
class HomeDemo
{
    public function loaded(): bool
    {
        return ! empty(setting('demo.home_ids'));
    }

    /** @return array{added:int, warnings:array<int,string>} */
    public function load(): array
    {
        if ($this->loaded()) {
            throw new \RuntimeException(__('Sample sections are already added. Remove them first to add them again.'));
        }

        $cat = Category::where('is_active', true)->whereHas('products', fn ($p) => $p->active())->orderBy('name')->first();
        $order = (int) HomeSection::max('sort_order');
        $ids = [];
        $add = function (string $type, string $title, array $settings, array $tr = []) use (&$ids, &$order) {
            $s = new HomeSection(['type' => $type, 'title' => $title, 'settings' => $settings + ['demo' => true], 'sort_order' => ++$order, 'is_active' => true]);
            $s->translations = $tr ?: null;
            $s->save();
            $ids[] = $s->id;
        };

        $add('hero', 'Sample hero', ['eyebrow' => 'Sample · Trusted care', 'title' => 'Sample hero: your pharmacy, {now online}', 'subtitle' => 'Genuine medicines and health products, delivered safely to your door.', 'button_label' => 'Shop medicines', 'button_url' => '/shop', 'button2_label' => 'Upload prescription', 'button2_url' => '/upload-prescription']);
        $add('features', 'Sample highlights', ['items' => [
            ['icon' => 'patch-check', 'title' => 'Sample: 100% genuine', 'text' => 'Medicines from licensed sources only.'],
            ['icon' => 'truck', 'title' => 'Sample: Fast delivery', 'text' => 'Quick and careful delivery.'],
            ['icon' => 'cash-coin', 'title' => 'Sample: Pay on delivery', 'text' => 'Pay in cash when it arrives.'],
            ['icon' => 'headset', 'title' => 'Sample: Pharmacist help', 'text' => 'Ask us anything, any time.'],
        ]]);
        $add('categories', 'Sample category tiles', ['title' => 'Sample: shop by need', 'limit' => 8, 'category_ids' => []]);
        $add('products', 'Sample featured products', ['title' => 'Sample: featured picks', 'source' => 'featured', 'limit' => 8]);
        $add('products', 'Sample new arrivals', ['title' => 'Sample: new arrivals', 'source' => 'new', 'limit' => 8]);
        if ($cat) {
            $add('products', 'Sample category row', ['title' => 'Sample: '.$cat->name, 'source' => 'category', 'category_id' => $cat->id, 'limit' => 8]);
        }
        $add('banner', 'Sample promo banner', ['eyebrow' => 'Sample offer', 'title' => 'Sample: up to 20% off vitamins', 'subtitle' => 'This week only, on selected health products.', 'button_label' => 'See offers', 'button_url' => '/shop?sort=discount']);
        $add('cta', 'Sample call-to-action', ['eyebrow' => 'Sample · No cart needed', 'title' => 'Sample: have a prescription? Just send a photo.', 'subtitle' => 'Our pharmacist will call you, confirm the medicines and deliver.', 'button_label' => 'Upload prescription', 'button_url' => '/upload-prescription', 'button2_label' => 'Contact us', 'button2_url' => '/contact']);
        $add('steps', 'Sample steps', ['title' => 'Sample: how ordering works', 'subtitle' => 'Three simple steps from prescription to doorstep.', 'items' => [
            ['icon' => 'search', 'title' => 'Search or upload', 'text' => 'Find your medicine, or send a photo of the prescription.'],
            ['icon' => 'person-check', 'title' => 'Pharmacist confirms', 'text' => 'We verify the prescription and confirm availability.'],
            ['icon' => 'house-heart', 'title' => 'Delivered home', 'text' => 'Safe delivery to your door. Pay when it arrives.'],
        ]]);
        $add('testimonials', 'Sample customer quotes', ['title' => 'Sample: what customers say', 'subtitle' => 'Kind words from people we looked after.', 'items' => [
            ['title' => 'Ayesha K.', 'role' => 'Lahore', 'text' => 'My prescription was confirmed in minutes and arrived the same evening.'],
            ['title' => 'Bilal R.', 'role' => 'Karachi', 'text' => 'Genuine medicines and a pharmacist who actually picks up the phone.'],
            ['title' => 'Sana M.', 'role' => 'Islamabad', 'text' => 'Easy ordering for my parents, and delivery is always careful.'],
        ]]);
        $add('reviews', 'Sample product reviews', ['title' => 'Sample: reviews from our shoppers', 'subtitle' => 'Real reviews from people who ordered from us.', 'limit' => 9, 'min_rating' => 4, 'show_summary' => true]);
        $add('blog', 'Sample health blog', ['title' => 'Sample: from our health blog', 'subtitle' => 'Simple, practical advice from our pharmacy team.', 'limit' => 3]);
        $add('faq', 'Sample FAQ', ['title' => 'Sample: questions people ask', 'subtitle' => 'Quick answers before you order.', 'items' => [
            ['title' => 'Do I need a prescription?', 'text' => 'Only for prescription (Rx) medicines. Over-the-counter items can be ordered directly.'],
            ['title' => 'How fast is delivery?', 'text' => 'Most orders arrive the same or the next day, depending on your area.'],
            ['title' => 'Can I pay on delivery?', 'text' => 'Yes. Pay in cash when your medicines arrive.'],
        ]]);
        $add('text', 'Sample text block', ['title' => 'Sample: about our pharmacy', 'body' => "We are a licensed pharmacy.\n\nEvery order is checked by a pharmacist before it leaves our shop. This block is plain text you can use for an introduction, a notice or opening hours."]);

        setting()->set('demo.home_ids', $ids);
        if (app(DemoImages::class)->loaded()) {
            app(DemoImages::class)->sections();   // pictures were added already: the new hero / banner / CTA get theirs too
        }

        $warn = [];
        if (! Review::query()->where('status', 'approved')->exists()) {
            $warn[] = __('The reviews section stays hidden until at least one review is approved (Admin → Reviews, or load the sample medicines).');
        }
        if (! Article::live()->exists()) {
            $warn[] = __('The blog section stays hidden until at least one article is published (Admin → Blog, or load the sample medicines).');
        }
        if (! Product::active()->exists()) {
            $warn[] = __('There are no products yet, so the product rows are empty.');
        }

        return ['added' => count($ids), 'warnings' => $warn];
    }

    /** @return int sections removed */
    public function remove(): int
    {
        $ids = (array) setting('demo.home_ids');
        $n = HomeSection::withTrashed()->whereIn('id', $ids)->get()->each->forceDelete()->count();
        setting()->set('demo.home_ids', null);

        return $n;
    }
}
