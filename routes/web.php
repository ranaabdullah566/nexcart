<?php

use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HomeSectionController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\ModifierGroupController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductCsvController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Auth\LoginController;
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
Route::get('/sitemap.xml', [Store\SeoController::class, 'sitemap'])->name('store.sitemap');
Route::get('/robots.txt', [Store\SeoController::class, 'robots']);
Route::get('/manifest.webmanifest', [Store\SeoController::class, 'manifest'])->name('store.manifest');
