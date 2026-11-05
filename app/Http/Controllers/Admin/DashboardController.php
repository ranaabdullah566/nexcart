<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
        $modules = collect($profile['modules'])->merge(
            collect(BusinessProfile::all())->pluck('modules')->flatten()->unique()
        )->unique()->filter(fn ($m) => BusinessProfile::hasModule($m))->values();

        return view('admin.dashboard', [
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
