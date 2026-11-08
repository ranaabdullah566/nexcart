<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\BusinessProfile;
use App\Support\Locales;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $profile = BusinessProfile::current();
        $modules = collect($profile['modules'])->unique()->filter(fn ($m) => BusinessProfile::hasModule($m))->values();

        $canSales = auth()->user()->can('orders.view');
        $live = fn () => Order::whereNotIn('status', ['cancelled', 'refunded']);
        // "today" and each chart bar are days on the STORE's clock, not UTC
        $tz = setting('store.timezone', config('app.timezone'));
        $days = collect(range(13, 0))->map(fn ($i) => now($tz)->subDays($i)->startOfDay());
        $startOfToday = now($tz)->startOfDay()->utc();
        $byDay = $canSales ? $live()->where('placed_at', '>=', $days->first()->copy()->utc())->get(['placed_at', 'total'])->groupBy(fn ($o) => $o->placed_at->copy()->timezone($tz)->format('Y-m-d')) : collect();

        return view('admin.dashboard', [
            'canSales' => $canSales,
            'sales' => $canSales ? [
                'today' => $live()->where('placed_at', '>=', $startOfToday)->count(),
                'revenue30' => (float) $live()->where('placed_at', '>=', now()->subDays(30))->sum('total'),
                'open' => Order::whereNotIn('status', ['completed', 'cancelled', 'refunded'])->count(),
                'avg' => (float) ($live()->where('placed_at', '>=', now()->subDays(30))->avg('total') ?? 0),
            ] : null,
            'chart' => ['labels' => $days->map->format('d M')->all(), 'revenue' => $days->map(fn ($d) => round((float) $byDay->get($d->format('Y-m-d'), collect())->sum('total'), 2))->all(), 'orders' => $days->map(fn ($d) => $byDay->get($d->format('Y-m-d'), collect())->count())->all()],
            'recentOrders' => $canSales ? Order::latest('id')->limit(6)->get() : collect(),
            'profile' => $profile,
            'staffCount' => User::whereHas('roles')->count(),
            'modules' => $modules,
            'languages' => Locales::enabled(),
            'lowStock' => auth()->user()->can('inventory.view')
                ? Product::with('variants')->where('track_stock', true)->get()->filter->isLowStock()->sortBy(fn ($p) => $p->totalStock())->take(6)->values()
                : collect(),
            'productCount' => Product::count(),
        ]);
    }
}
