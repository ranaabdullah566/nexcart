<?php

use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DeliveryController;
use App\Http\Controllers\Admin\DeliverySlotController;
use App\Http\Controllers\Admin\HomeSectionController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\KitchenController;
use App\Http\Controllers\Admin\ModifierGroupController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PrescriptionController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductCsvController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ShippingController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\Store;
use Illuminate\Support\Facades\Route;

Route::get('/setup', [SetupController::class, 'show'])->name('setup');
Route::post('/setup', [SetupController::class, 'store'])->name('setup.store');

Route::get('/lang/{locale}', LocaleController::class)->name('locale');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');
});
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:10,1');
});
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'staff'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::resource('staff', StaffController::class)->except('show')->parameters(['staff' => 'staff']);

    // Catalog
    Route::resource('categories', CategoryController::class)->except('show');
    Route::resource('brands', BrandController::class)->except('show');
    Route::resource('attributes', AttributeController::class)->except('show');
    Route::resource('modifier-groups', ModifierGroupController::class)->except('show')->parameters(['modifier-groups' => 'modifierGroup']);
    Route::get('products/export', [ProductCsvController::class, 'export'])->name('products.export');
    Route::post('products/import', [ProductCsvController::class, 'import'])->name('products.import');
    Route::delete('products/{product}/images/{media}', [ProductController::class, 'destroyImage'])->name('products.images.destroy');
    Route::resource('products', ProductController::class)->except('show');

    // Sales
    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('orders/{order}/status', [OrderController::class, 'status'])->name('orders.status');
    Route::post('orders/{order}/paid', [OrderController::class, 'paid'])->name('orders.paid');
    Route::get('orders/{order}/prescriptions/{media}', [OrderController::class, 'prescription'])->name('orders.prescription');
    Route::post('orders/{order}/rider', [OrderController::class, 'rider'])->name('orders.rider');
    Route::get('kitchen', [KitchenController::class, 'index'])->name('kitchen.index');
    Route::get('kitchen/feed', [KitchenController::class, 'feed'])->name('kitchen.feed');
    Route::post('kitchen/{order}/advance', [KitchenController::class, 'advance'])->name('kitchen.advance');
    Route::get('deliveries', [DeliveryController::class, 'index'])->name('deliveries.index');
    Route::post('deliveries/{order}/advance', [DeliveryController::class, 'advance'])->name('deliveries.advance');
    Route::get('prescriptions', [PrescriptionController::class, 'index'])->name('prescriptions.index');
    Route::get('prescriptions/{order}', [PrescriptionController::class, 'show'])->name('prescriptions.show');
    Route::post('prescriptions/{order}/review', [PrescriptionController::class, 'review'])->name('prescriptions.review');
    Route::post('delivery-slots/settings', [DeliverySlotController::class, 'settings'])->name('delivery-slots.settings');
    Route::resource('delivery-slots', DeliverySlotController::class)->except('show')->parameters(['delivery-slots' => 'deliverySlot']);
    Route::resource('coupons', CouponController::class)->except('show');
    Route::resource('shipping', ShippingController::class)->except('show')->parameters(['shipping' => 'shipping']);
    Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');

    // Storefront
    Route::post('home-sections/{homeSection}/toggle', [HomeSectionController::class, 'toggle'])->name('home-sections.toggle');
    Route::post('home-sections/{homeSection}/move', [HomeSectionController::class, 'move'])->name('home-sections.move');
    Route::resource('home-sections', HomeSectionController::class)->except('show')->parameters(['home-sections' => 'homeSection']);

    // Inventory
    Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('inventory/adjust', [InventoryController::class, 'create'])->name('inventory.adjust');
    Route::post('inventory/adjust', [InventoryController::class, 'store'])->name('inventory.store');
});

// Storefront
Route::get('/', Store\HomeController::class)->name('store.home');
Route::get('/shop', [Store\ShopController::class, 'index'])->name('store.shop');
Route::get('/category/{category}', [Store\ShopController::class, 'index'])->name('store.category');
Route::get('/product/{slug}', [Store\ProductController::class, 'show'])->name('store.product');
Route::get('/product/{slug}/quick', [Store\ProductController::class, 'quick'])->name('store.quick');
Route::get('/search/suggest', [Store\SearchController::class, 'suggest'])->name('store.suggest');
Route::get('/cart', [Store\CartController::class, 'show'])->name('store.cart');
Route::get('/cart.json', [Store\CartController::class, 'json'])->name('store.cart.json');
Route::post('/cart/add', [Store\CartController::class, 'add'])->name('store.cart.add');
Route::patch('/cart/{key}', [Store\CartController::class, 'update'])->name('store.cart.update');
Route::delete('/cart/{key}', [Store\CartController::class, 'destroy'])->name('store.cart.remove');
Route::get('/wishlist', [Store\WishlistController::class, 'index'])->name('store.wishlist');
Route::post('/wishlist/{product}', [Store\WishlistController::class, 'toggle'])->name('store.wishlist.toggle');
Route::get('/checkout', [Store\CheckoutController::class, 'show'])->name('store.checkout');
Route::post('/checkout/quote', [Store\CheckoutController::class, 'quote'])->name('store.checkout.quote');
Route::post('/checkout', [Store\CheckoutController::class, 'place'])->middleware('throttle:20,1')->name('store.checkout.place');
Route::get('/order/{number}/{token}', [Store\OrderController::class, 'show'])->name('store.order');
Route::get('/order/{number}/{token}/invoice', [Store\OrderController::class, 'invoice'])->name('store.invoice');
Route::post('/order/{number}/{token}/pay', [Store\OrderController::class, 'pay'])->middleware('throttle:10,1')->name('store.order.pay');
Route::post('/order/{number}/{token}/reorder', [Store\OrderController::class, 'reorder'])->middleware('throttle:20,1')->name('store.order.reorder');
Route::post('/order/{number}/{token}/prescription', [Store\OrderController::class, 'prescription'])->middleware('throttle:10,1')->name('store.order.prescription');
Route::get('/order/{number}/{token}/paid', [Store\OrderController::class, 'stripeReturn'])->name('store.stripe.return');
Route::get('/track', [Store\OrderController::class, 'trackForm'])->name('store.track');
Route::post('/track', [Store\OrderController::class, 'trackFind'])->middleware('throttle:10,1')->name('store.track.find');
Route::middleware('auth')->group(function () {
    Route::get('/account', [Store\AccountController::class, 'index'])->name('store.account');
    Route::put('/account', [Store\AccountController::class, 'update'])->name('store.account.update');
});
Route::get('/sitemap.xml', [Store\SeoController::class, 'sitemap'])->name('store.sitemap');
Route::get('/robots.txt', [Store\SeoController::class, 'robots']);
Route::get('/manifest.webmanifest', [Store\SeoController::class, 'manifest'])->name('store.manifest');
