<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\BusinessProfile;
use App\Support\DashboardStats;
use App\Support\Locales;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $u = auth()->user();
        $stats = new DashboardStats;
        $canSales = $u->can('orders.view');
        $canCustomers = $u->can('customers.view');
        $canStock = $u->can('inventory.view');
        $hasReviews = $u->can('reviews.manage') && BusinessProfile::hasModule('reviews');

        $sales = $canSales ? $stats->sales() : null;

        return view('admin.dashboard', [
            'canSales' => $canSales,
            'sales' => $sales,
            'customers' => $canCustomers || $canSales ? $stats->customers() : null,
            'reviews' => $hasReviews ? $stats->reviews() : null,
            'stock' => $canStock ? $stats->stock() : null,
            'attention' => $stats->attention(),
            'recentOrders' => $canSales ? Order::latest('id')->limit(6)->get() : collect(),
            'profile' => BusinessProfile::current(),
            'staffCount' => User::whereHas('roles')->count(),
            'languages' => Locales::enabled(),
            'productCount' => Product::count(),
        ]);
    }
}
