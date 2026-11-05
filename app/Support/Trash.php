<?php

namespace App\Support;

use App\Models\Brand;
use App\Models\CatalogAttribute;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\DeliverySlot;
use App\Models\HomeSection;
use App\Models\MenuItem;
use App\Models\ModifierGroup;
use App\Models\Page;
use App\Models\Product;
use App\Models\Review;
use App\Models\ShippingZone;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * The Recycle Bin registry: every kind of record an admin can delete, how to label it and who may restore or erase it.
 * (Orders are never deletable — they are accounting records — so they are not listed.)
 */
class Trash
{
    /** @return array<string, array{label:string, icon:string, model:class-string<Model>, name:\Closure, permission:string}> */
    public static function types(): array
    {
        return [
            'products' => ['label' => 'Products', 'icon' => 'box-seam-fill', 'model' => Product::class, 'name' => fn ($m) => $m->name, 'permission' => 'products.delete'],
            'categories' => ['label' => 'Categories', 'icon' => 'diagram-3-fill', 'model' => Category::class, 'name' => fn ($m) => $m->name, 'permission' => 'categories.delete'],
            'brands' => ['label' => 'Brands', 'icon' => 'award-fill', 'model' => Brand::class, 'name' => fn ($m) => $m->name, 'permission' => 'products.delete'],
            'attributes' => ['label' => 'Product fields', 'icon' => 'sliders2', 'model' => CatalogAttribute::class, 'name' => fn ($m) => $m->label, 'permission' => 'catalog_settings.manage'],
            'modifier-groups' => ['label' => 'Add-ons', 'icon' => 'plus-slash-minus', 'model' => ModifierGroup::class, 'name' => fn ($m) => $m->name, 'permission' => 'catalog_settings.manage'],
            'coupons' => ['label' => 'Coupons', 'icon' => 'ticket-perforated-fill', 'model' => Coupon::class, 'name' => fn ($m) => $m->code, 'permission' => 'sales_settings.manage'],
            'shipping' => ['label' => 'Shipping zones', 'icon' => 'truck', 'model' => ShippingZone::class, 'name' => fn ($m) => $m->name, 'permission' => 'sales_settings.manage'],
            'delivery-slots' => ['label' => 'Delivery slots', 'icon' => 'calendar2-week', 'model' => DeliverySlot::class, 'name' => fn ($m) => $m->label.' · '.$m->range(), 'permission' => 'sales_settings.manage'],
            'home-sections' => ['label' => 'Home page sections', 'icon' => 'layout-text-window-reverse', 'model' => HomeSection::class, 'name' => fn ($m) => $m->title ?: ucfirst($m->type), 'permission' => 'storefront.manage'],
            'pages' => ['label' => 'Pages', 'icon' => 'file-earmark-text-fill', 'model' => Page::class, 'name' => fn ($m) => $m->title, 'permission' => 'storefront.manage'],
            'menu-items' => ['label' => 'Menu links', 'icon' => 'list-nested', 'model' => MenuItem::class, 'name' => fn ($m) => $m->label, 'permission' => 'storefront.manage'],
            'reviews' => ['label' => 'Reviews', 'icon' => 'chat-heart-fill', 'model' => Review::class, 'name' => fn ($m) => ($m->product?->name ?? '—').' · '.$m->rating.'★ '.$m->name, 'permission' => 'reviews.manage'],
            'staff' => ['label' => 'Staff', 'icon' => 'person-badge-fill', 'model' => User::class, 'name' => fn ($m) => $m->name.' ('.$m->email.')', 'permission' => 'staff.delete'],
        ];
    }

    /** Types the given user may see and act on. */
    public static function allowedFor(?User $user): array
    {
        return array_filter(static::types(), fn ($t) => $user?->can($t['permission']));
    }

    public static function query(string $type)
    {
        $model = static::types()[$type]['model'];

        return $model::onlyTrashed();
    }

    /** Delete for good everything that has been in the bin longer than N days. @return int records erased */
    public static function purgeOlderThan(int $days): int
    {
        $n = 0;
        foreach (array_keys(static::types()) as $type) {
            foreach (static::query($type)->where('deleted_at', '<', now()->subDays($days))->get() as $record) {
                $record->forceDelete();
                $n++;
            }
        }

        return $n;
    }
}
